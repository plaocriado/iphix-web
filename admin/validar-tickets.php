<?php



require_once __DIR__ . '/../includes/config.php';


$estilos = <<<CSS
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f5f5;
        padding: 2rem;
        color: #333;
    }
    .container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
        color: #0066cc;
        border-bottom: 3px solid #0066cc;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }
    h2 {
        color: #333;
        margin-top: 2rem;
        font-size: 1.2rem;
    }
    .check-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin: 0.5rem 0;
        border-radius: 6px;
        background: #f9f9f9;
        border-left: 4px solid #999;
    }
    .check-item.ok {
        background: #e8f5e9;
        border-left-color: #2e7d32;
    }
    .check-item.error {
        background: #ffebee;
        border-left-color: #c62828;
    }
    .check-item.warning {
        background: #fff3e0;
        border-left-color: #e65100;
    }
    .check-icon {
        font-size: 1.5rem;
        margin-right: 1rem;
        min-width: 30px;
    }
    .check-text {
        flex: 1;
    }
    .check-title {
        font-weight: 600;
        display: block;
    }
    .check-desc {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.3rem;
    }
    .summary {
        margin-top: 2rem;
        padding: 1.5rem;
        border-radius: 6px;
        background: #e3f2fd;
        border: 2px solid #0066cc;
    }
    .summary.error {
        background: #ffebee;
        border-color: #c62828;
    }
    .summary.success {
        background: #e8f5e9;
        border-color: #2e7d32;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }
    .stat-box {
        background: #f5f5f5;
        padding: 1rem;
        border-radius: 6px;
        text-align: center;
        border-top: 3px solid #0066cc;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: 600;
        color: #0066cc;
    }
    .stat-label {
        font-size: 0.9rem;
        color: #666;
    }
    .footer {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #e0e0e0;
        font-size: 0.85rem;
        color: #999;
    }
</style>
CSS;


try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $bd_conectada = true;
} catch (Exception $e) {
    $bd_conectada = false;
    $bd_error = $e->getMessage();
}


$tablas_requeridas = ['tickets', 'mensajes_ticket', 'respuestas_bot'];
$tablas_existentes = [];

if ($bd_conectada) {
    $resultado = $pdo->query("SHOW TABLES FROM " . DB_NAME);
    $tablas_existentes = $resultado->fetchAll(PDO::FETCH_COLUMN);
}


$archivos_requeridos = [
    '/includes/tickets.php' => 'Funciones de tickets',
    '/pages/soporte.php' => 'Página de usuario',
    '/admin/tickets.php' => 'Panel administrativo',
    '/api/tickets.php' => 'API AJAX',
    '/assets/css/tickets.css' => 'Estilos CSS',
    '/database/tickets_schema.sql' => 'SQL de instalación',
    '/TICKETS_README.md' => 'Documentación',
];

$archivos_existentes = [];
$ruta_base = __DIR__ . '/..';

foreach ($archivos_requeridos as $archivo => $desc) {
    $archivos_existentes[$archivo] = file_exists($ruta_base . $archivo);
}


$tickets_count = 0;
$usuarios_tickets = 0;
$mensajes_count = 0;

if ($bd_conectada && in_array('tickets', $tablas_existentes)) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
    $tickets_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT usuario_id) FROM tickets");
    $usuarios_tickets = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM mensajes_ticket");
    $mensajes_count = $stmt->fetchColumn();
}


$todo_ok = $bd_conectada && 
           count(array_filter($archivos_existentes)) === count($archivos_requeridos) &&
           count(array_filter(function($tabla) use ($tablas_existentes) {
               return in_array($tabla, $tablas_existentes);
           }, array_keys(array_flip($tablas_requeridas)))) === 3;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador - Sistema de Tickets</title>
    <?= $estilos ?>
</head>
<body>
    <div class="container">
        <h1>🔍 Validador de Instalación - Sistema de Tickets</h1>

        <!-- RESUMEN -->
        <div class="summary <?= $todo_ok ? 'success' : 'error' ?>">
            <strong><?= $todo_ok ? '✅ INSTALACIÓN CORRECTA' : '❌ PROBLEMAS ENCONTRADOS' ?></strong>
            <p><?= $todo_ok 
                ? 'Todos los componentes están correctamente instalados y funcionando.'
                : 'Por favor, revisa los problemas indicados abajo y realiza las correcciones necesarias.'
            ?></p>
        </div>

        <!-- ESTADÍSTICAS -->
        <?php if ($tickets_count > 0 || $usuarios_tickets > 0): ?>
        <h2>📊 Estadísticas</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?= $tickets_count ?></div>
                <div class="stat-label">Tickets creados</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $usuarios_tickets ?></div>
                <div class="stat-label">Usuarios con tickets</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $mensajes_count ?></div>
                <div class="stat-label">Mensajes totales</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- VERIFICACIÓN DE BD -->
        <h2>🗄️ Base de datos</h2>
        <div class="check-item <?= $bd_conectada ? 'ok' : 'error' ?>">
            <div class="check-icon"><?= $bd_conectada ? '✅' : '❌' ?></div>
            <div class="check-text">
                <span class="check-title">Conexión a BD</span>
                <span class="check-desc">
                    <?= $bd_conectada 
                        ? 'Conectado a: ' . DB_NAME . ' en ' . DB_HOST
                        : 'Error: ' . $bd_error
                    ?>
                </span>
            </div>
        </div>

        <!-- VERIFICACIÓN DE TABLAS -->
        <h2>📋 Tablas requeridas</h2>
        <?php foreach ($tablas_requeridas as $tabla): ?>
        <div class="check-item <?= in_array($tabla, $tablas_existentes) ? 'ok' : 'error' ?>">
            <div class="check-icon"><?= in_array($tabla, $tablas_existentes) ? '✅' : '❌' ?></div>
            <div class="check-text">
                <span class="check-title">Tabla: <code><?= $tabla ?></code></span>
                <span class="check-desc">
                    <?= in_array($tabla, $tablas_existentes) 
                        ? 'Tabla existe y está accesible'
                        : 'FALTA: Ejecuta el SQL en database/tickets_schema.sql'
                    ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- VERIFICACIÓN DE ARCHIVOS -->
        <h2>📁 Archivos requeridos</h2>
        <?php foreach ($archivos_requeridos as $archivo => $descripcion): ?>
        <div class="check-item <?= $archivos_existentes[$archivo] ? 'ok' : 'error' ?>">
            <div class="check-icon"><?= $archivos_existentes[$archivo] ? '✅' : '❌' ?></div>
            <div class="check-text">
                <span class="check-title"><?= $descripcion ?></span>
                <span class="check-desc">
                    <?= $archivos_existentes[$archivo] 
                        ? 'Archivo presente: ' . $archivo
                        : 'FALTA: ' . $archivo . ' no encontrado'
                    ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- VERIFICACIÓN DE FUNCIONES -->
        <h2>⚙️ Funciones PHP</h2>
        <?php 
        $funciones_requeridas = [
            'crearTicket' => 'Crear tickets',
            'obtenerTicketsUsuario' => 'Obtener tickets de usuario',
            'obtenerTicket' => 'Obtener ticket específico',
            'agregarMensajeTicket' => 'Agregar mensaje',
            'cambiarEstadoTicket' => 'Cambiar estado',
            'asignarTicket' => 'Asignar a admin',
            'obtenerTicketsAdmin' => 'Panel admin',
        ];
        
        if (file_exists($ruta_base . '/includes/tickets.php')) {
            include $ruta_base . '/includes/tickets.php';
            foreach ($funciones_requeridas as $funcion => $desc):
        ?>
        <div class="check-item <?= function_exists($funcion) ? 'ok' : 'error' ?>">
            <div class="check-icon"><?= function_exists($funcion) ? '✅' : '❌' ?></div>
            <div class="check-text">
                <span class="check-title"><?= $desc ?></span>
                <span class="check-desc">
                    <?= function_exists($funcion) 
                        ? 'Función disponible: ' . $funcion . '()'
                        : 'FALTA: Función ' . $funcion . '() no encontrada'
                    ?>
                </span>
            </div>
        </div>
            <?php endforeach;
        } ?>

        <!-- ACCIONES RECOMENDADAS -->
        <h2>📋 Próximos pasos</h2>
        <?php if ($todo_ok): ?>
        <div class="check-item ok">
            <div class="check-icon">✅</div>
            <div class="check-text">
                <span class="check-title">¡Instalación completada!</span>
                <span class="check-desc">
                    Puedes acceder a:
                    <ul>
                        <li><a href="/pages/soporte.php" target="_blank">Centro de Soporte (Usuario)</a></li>
                        <li><a href="/admin/tickets.php" target="_blank">Panel de Tickets (Admin)</a></li>
                        <li><a href="/TICKETS_README.md" target="_blank">Documentación completa</a></li>
                    </ul>
                </span>
            </div>
        </div>
        <?php else: ?>
        <div class="check-item error">
            <div class="check-icon">❌</div>
            <div class="check-text">
                <span class="check-title">Se encontraron problemas</span>
                <span class="check-desc">
                    Revisa los items en rojo arriba y:
                    <ol>
                        <li>Si falta BD: Ejecuta el SQL en database/tickets_schema.sql</li>
                        <li>Si faltan archivos: Verifica que están en la ruta correcta</li>
                        <li>Si faltan funciones: Incluye tickets.php en header.php</li>
                        <li>Recarga esta página para validar cambios</li>
                    </ol>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>🔍 Validador del Sistema de Tickets v1.0 | Última verificación: <?= date('d/m/Y H:i:s') ?></p>
            <p>Para más información, consulta: TICKETS_README.md y TICKETS_INSTALACION.md</p>
        </div>
    </div>
</body>
</html>
