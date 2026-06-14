<?php






function generarCodigoTicket(): string {
    do {
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $codigo = 'TKT-' . date('ymd') . '-' . $random;
        $existe = dbQueryOne("SELECT id FROM tickets WHERE codigo = ?", [$codigo]);
    } while ($existe);
    return $codigo;
}



function crearTicket(int $usuarioId, string $asunto, string $descripcion, string $categoria = 'otro', string $prioridad = 'normal'): ?int {
    $codigo = generarCodigoTicket();
    
    dbExecute(
        "INSERT INTO tickets (codigo, usuario_id, asunto, descripcion, categoria, prioridad) 
         VALUES (?, ?, ?, ?, ?, ?)",
        [$codigo, $usuarioId, $asunto, $descripcion, $categoria, $prioridad]
    );
    
    $ticketId = (int) dbLastInsertId();
    
    
    agregarMensajeTicket($ticketId, $usuarioId, $descripcion);
    
    
    iniciarBotTicket($ticketId, $usuarioId, $categoria);
    
    return $ticketId;
}



function obtenerTicketsUsuario(int $usuarioId, ?string $estado = null, int $pagina = 1, int $porPagina = 10): array {
    $offset = ($pagina - 1) * $porPagina;
    
    $where = ['usuario_id = ?'];
    $params = [$usuarioId];
    
    if ($estado) {
        $where[] = 'estado = ?';
        $params[] = $estado;
    }
    
    $whereSQL = implode(' AND ', $where);
    
    $total = dbQueryOne("SELECT COUNT(*) AS t FROM tickets WHERE $whereSQL", $params)['t'] ?? 0;
    
    $sql = "SELECT t.*, 
            (SELECT COUNT(*) FROM mensajes_ticket WHERE ticket_id = t.id AND leido = 0 AND es_admin = 1) AS mensajes_no_leidos,
            u.nombre AS admin_nombre
            FROM tickets t
            LEFT JOIN usuarios u ON t.asignado_a = u.id
            WHERE $whereSQL
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = db()->prepare($sql);
    $index = 1;
    foreach ($params as $v) $stmt->bindValue($index++, $v);
    $stmt->bindValue($index++, (int)$porPagina, PDO::PARAM_INT);
    $stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'tickets' => $stmt->fetchAll(),
        'total' => $total,
        'paginas' => (int) ceil($total / $porPagina)
    ];
}



function obtenerTicket(int $ticketId): ?array {
    $ticket = dbQueryOne(
        "SELECT t.*, u.nombre AS usuario_nombre, u.email, a.nombre AS admin_nombre
         FROM tickets t
         JOIN usuarios u ON t.usuario_id = u.id
         LEFT JOIN usuarios a ON t.asignado_a = a.id
         WHERE t.id = ?",
        [$ticketId]
    );
    
    if (!$ticket) return null;
    
    
    $mensajes = dbQuery(
        "SELECT m.*, u.nombre AS usuario_nombre, u.email
         FROM mensajes_ticket m
         JOIN usuarios u ON m.usuario_id = u.id
         WHERE m.ticket_id = ?
         ORDER BY m.created_at ASC",
        [$ticketId]
    );
    
    $ticket['mensajes'] = $mensajes;
    return $ticket;
}



function agregarMensajeTicket(int $ticketId, int $usuarioId, string $mensaje, bool $esAdmin = false, ?string $archivoUrl = null, bool $esBot = false): int {
    dbExecute(
        "INSERT INTO mensajes_ticket (ticket_id, usuario_id, es_admin, mensaje, archivo_url, es_bot)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$ticketId, $usuarioId, $esAdmin ? 1 : 0, $mensaje, $archivoUrl, $esBot ? 1 : 0]
    );
    
    
    dbExecute("UPDATE tickets SET updated_at = NOW() WHERE id = ?", [$ticketId]);
    
    return (int) dbLastInsertId();
}



function cambiarEstadoTicket(int $ticketId, string $nuevoEstado, ?int $adminId = null): bool {
    $estados = ['abierto', 'en_proceso', 'resuelto', 'reabierto', 'cerrado'];
    
    if (!in_array($nuevoEstado, $estados)) return false;
    
    $updateSQL = "UPDATE tickets SET estado = ?, updated_at = NOW()";
    $params = [$nuevoEstado, $ticketId];
    
    if ($nuevoEstado === 'resuelto' || $nuevoEstado === 'cerrado') {
        $updateSQL = "UPDATE tickets SET estado = ?, resuelto_at = NOW(), updated_at = NOW()" . 
                     ($adminId ? ", cerrado_por = ?" : "");
        $params = [$nuevoEstado, $ticketId];
        if ($adminId) array_splice($params, 1, 0, [$adminId]);
    }
    
    $updateSQL .= " WHERE id = ?";
    
    return dbExecute($updateSQL, $params) > 0;
}



function asignarTicket(int $ticketId, int $adminId): bool {
    $admin = dbQueryOne("SELECT id FROM usuarios WHERE id = ? AND rol = 'admin'", [$adminId]);
    
    if (!$admin) return false;
    
    return dbExecute(
        "UPDATE tickets SET asignado_a = ?, estado = 'en_proceso' WHERE id = ?",
        [$adminId, $ticketId]
    ) > 0;
}



function obtenerTicketsAdmin(?string $estado = null, ?string $filtro = null, int $pagina = 1, int $porPagina = 15): array {
    $where = [];
    $params = [];
    
    if ($estado && $estado !== 'todos') {
        $where[] = "t.estado = ?";
        $params[] = $estado;
    }
    
    if ($filtro) {
        $where[] = "(t.codigo LIKE ? OR t.asunto LIKE ? OR u.email LIKE ?)";
        $params[] = "%$filtro%";
        $params[] = "%$filtro%";
        $params[] = "%$filtro%";
    }
    
    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
    $offset = ($pagina - 1) * $porPagina;
    
    $total = dbQueryOne("SELECT COUNT(*) AS t FROM tickets t JOIN usuarios u ON t.usuario_id = u.id $whereSQL", $params)['t'] ?? 0;
    
    $sql = "SELECT t.id, t.codigo, t.asunto, t.estado, t.prioridad, t.categoria, 
            t.created_at, t.updated_at, u.nombre, u.email,
            a.nombre AS admin_nombre,
            (SELECT COUNT(*) FROM mensajes_ticket WHERE ticket_id = t.id AND es_admin = 0) AS mensajes_usuario
            FROM tickets t
            JOIN usuarios u ON t.usuario_id = u.id
            LEFT JOIN usuarios a ON t.asignado_a = a.id
            $whereSQL
            ORDER BY 
                CASE WHEN t.prioridad = 'critica' THEN 1
                     WHEN t.prioridad = 'alta' THEN 2
                     WHEN t.prioridad = 'normal' THEN 3
                     ELSE 4 END ASC,
                t.updated_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = db()->prepare($sql);
    $index = 1;
    foreach ($params as $v) $stmt->bindValue($index++, $v);
    $stmt->bindValue($index++, (int)$porPagina, PDO::PARAM_INT);
    $stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'tickets' => $stmt->fetchAll(),
        'total' => $total,
        'paginas' => (int) ceil($total / $porPagina),
        'por_estado' => obtenerEstadisticasTickets()
    ];
}



function obtenerEstadisticasTickets(): array {
    $estados = ['abierto', 'en_proceso', 'resuelto', 'reabierto', 'cerrado'];
    $stats = [];
    
    foreach ($estados as $estado) {
        $count = dbQueryOne("SELECT COUNT(*) AS t FROM tickets WHERE estado = ?", [$estado])['t'] ?? 0;
        $stats[$estado] = $count;
    }
    
    return $stats;
}







function obtenerPreguntasBot(string $categoria): array {
    $preguntas = [
        'compra' => [
            "¿Cuál es tu número de pedido o referencia?",
            "¿Cuál es el problema específico con tu compra?",
            "¿Ya intentaste resolver el problema? Si es así, ¿cómo?"
        ],
        'tecnico' => [
            "¿Qué dispositivo tienes?",
            "¿Cuál es exactamente el problema técnico que experimentas?",
            "¿Qué pasos has seguido para intentar solucionarlo?",
            "¿Qué sistema operativo o versión tienes?"
        ],
        'envio' => [
            "¿Cuál es tu número de pedido o referencia?",
            "¿Cuál es el problema con el envío?",
            "¿Ya contactaste con el proveedor de logística?"
        ],
        'factura' => [
            "¿Cuál es tu número de pedido?",
            "¿Qué problema tienes con tu factura?",
            "¿Necesitas factura rectificativa o duplicado?"
        ],
        'otro' => [
            "¿Cuál es el tema de tu consulta?",
            "¿Puedes describirlo con más detalle?",
            "¿Hay algo más que debamos saber?"
        ]
    ];
    
    return $preguntas[$categoria] ?? $preguntas['otro'];
}



function iniciarBotTicket(int $ticketId, int $usuarioId, string $categoria): void {
    $preguntas = obtenerPreguntasBot($categoria);
    
    foreach ($preguntas as $paso => $pregunta) {
        dbExecute(
            "INSERT INTO respuestas_bot (ticket_id, paso, pregunta) VALUES (?, ?, ?)",
            [$ticketId, $paso + 1, $pregunta]
        );
    }
    
    
    if (!empty($preguntas)) {
        $mensajeBienvenida = "¡Hola! 👋 Soy el asistente de soporte. Voy a ayudarte a resolver tu consulta con algunas preguntas.\n\n" . 
                           $preguntas[0] . "\n\n_(Responde en el siguiente mensaje)_";
        
        agregarMensajeTicket($ticketId, $usuarioId, $mensajeBienvenida, false, null, true);
    }
}



function obtenerSiguientePreguntaBot(int $ticketId): ?array {
    return dbQueryOne(
        "SELECT * FROM respuestas_bot WHERE ticket_id = ? AND completado = 0 ORDER BY paso ASC LIMIT 1",
        [$ticketId]
    );
}



function completarPreguntaBot(int $ticketId, int $paso, string $respuesta): void {
    dbExecute(
        "UPDATE respuestas_bot SET respuesta_usuario = ?, completado = 1 WHERE ticket_id = ? AND paso = ?",
        [$respuesta, $ticketId, $paso]
    );
}



function obtenerRespuestasBot(int $ticketId): array {
    return dbQuery(
        "SELECT * FROM respuestas_bot WHERE ticket_id = ? ORDER BY paso ASC",
        [$ticketId]
    );
}
