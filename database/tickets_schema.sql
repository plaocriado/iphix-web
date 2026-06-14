-- =====================================================
-- SISTEMA DE TICKETS DE SOPORTE
-- =====================================================

-- =====================================================
-- TABLA: tickets
-- =====================================================
CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT UNSIGNED NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria ENUM('compra','tecnico','envio','factura','otro') NOT NULL DEFAULT 'otro',
    prioridad ENUM('baja','normal','alta','critica') NOT NULL DEFAULT 'normal',
    estado ENUM('abierto','en_proceso','resuelto','reabierto','cerrado') NOT NULL DEFAULT 'abierto',
    tiempo_resolucion INT UNSIGNED DEFAULT NULL, -- segundos
    asignado_a INT UNSIGNED DEFAULT NULL,
    cerrado_por INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resuelto_at DATETIME DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (cerrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_categoria (categoria),
    INDEX idx_created (created_at),
    INDEX idx_asignado (asignado_a)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: mensajes_ticket
-- =====================================================
CREATE TABLE IF NOT EXISTS mensajes_ticket (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    es_admin TINYINT(1) NOT NULL DEFAULT 0,
    mensaje TEXT NOT NULL,
    archivo_url VARCHAR(255) DEFAULT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    es_bot TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_leido (leido),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: respuestas_bot
-- =====================================================
CREATE TABLE IF NOT EXISTS respuestas_bot (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    paso INT UNSIGNED NOT NULL DEFAULT 1,
    pregunta VARCHAR(500) NOT NULL,
    respuesta_usuario TEXT DEFAULT NULL,
    completado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_paso (paso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
