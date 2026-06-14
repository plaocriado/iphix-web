-- =====================================================
-- BASE DE DATOS: iphix
-- Plataforma Web de Reparación y Venta de Dispositivos
-- Autor: Pedro Lao | IES Inca Garcilaso
-- =====================================================



-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    rol ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    token_reset VARCHAR(255) DEFAULT NULL,
    token_reset_expira DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: categorias
-- =====================================================
CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    padre_id INT UNSIGNED DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    icono VARCHAR(100) DEFAULT NULL,
    orden INT UNSIGNED DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (padre_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_padre (padre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: productos
-- =====================================================
CREATE TABLE productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    descripcion TEXT DEFAULT NULL,
    especificaciones TEXT DEFAULT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    marca VARCHAR(100) DEFAULT NULL,
    modelo VARCHAR(150) DEFAULT NULL,
    estado ENUM('disponible','en_reparacion','vendido','reservado') NOT NULL DEFAULT 'disponible',
    precio_compra DECIMAL(10,2) DEFAULT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 1,
    imagen_principal VARCHAR(255) DEFAULT 'default.jpg',
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_estado (estado),
    INDEX idx_categoria (categoria_id),
    INDEX idx_destacado (destacado),
    FULLTEXT INDEX ft_busqueda (nombre, descripcion, marca, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: imagenes_producto
-- =====================================================
CREATE TABLE imagenes_producto (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt VARCHAR(255) DEFAULT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: direcciones_usuario
-- =====================================================
CREATE TABLE direcciones_usuario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nombre_destinatario VARCHAR(200) NOT NULL,
    linea1 VARCHAR(255) NOT NULL,
    linea2 VARCHAR(255) DEFAULT NULL,
    ciudad VARCHAR(100) NOT NULL,
    provincia VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    pais VARCHAR(100) NOT NULL DEFAULT 'España',
    principal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pedidos
-- =====================================================
CREATE TABLE pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT UNSIGNED NOT NULL,
    estado ENUM('pendiente','pagado','procesando','enviado','entregado','cancelado','reembolsado') NOT NULL DEFAULT 'pendiente',
    subtotal DECIMAL(10,2) NOT NULL,
    gastos_envio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    nombre_destinatario VARCHAR(200) NOT NULL,
    direccion_envio VARCHAR(255) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    provincia VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    pais VARCHAR(100) NOT NULL DEFAULT 'España',
    stripe_payment_intent VARCHAR(255) DEFAULT NULL,
    stripe_payment_status VARCHAR(50) DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: lineas_pedido
-- =====================================================
CREATE TABLE lineas_pedido (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    nombre_producto VARCHAR(255) NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    INDEX idx_pedido (pedido_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: piezas
-- =====================================================
CREATE TABLE piezas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    referencia VARCHAR(100) DEFAULT NULL UNIQUE,
    descripcion TEXT DEFAULT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 5,
    precio_unitario DECIMAL(10,2) DEFAULT NULL,
    proveedor VARCHAR(255) DEFAULT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_referencia (referencia),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: movimientos_piezas
-- =====================================================
CREATE TABLE movimientos_piezas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pieza_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','salida') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    usuario_id INT UNSIGNED DEFAULT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pieza_id) REFERENCES piezas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pieza (pieza_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: transacciones (ingresos y gastos)
-- =====================================================
CREATE TABLE transacciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso','gasto') NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    importe DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    pedido_id INT UNSIGNED DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    fecha DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: contacto (formulario de contacto)
-- =====================================================
CREATE TABLE contacto (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    email VARCHAR(255) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    respondido TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sesiones_usuario (log de accesos)
-- =====================================================
CREATE TABLE sesiones_usuario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

INSERT INTO usuarios (nombre, apellidos, email, password, rol, activo) VALUES
('Pedro', 'Lao', 'admin@iphix.es', '$2y$12$hashed_placeholder_admin', 'admin', 1),
('María', 'García López', 'maria@email.com', '$2y$12$hashed_placeholder_cliente', 'cliente', 1),
('Carlos', 'Martínez Ruiz', 'carlos@email.com', '$2y$12$hashed_placeholder_cliente2', 'cliente', 1);

-- Nota: Los hashes anteriores son placeholders. Ejecutar el script PHP
-- database/hash_passwords.php para generar hashes reales antes del despliegue.

-- Categorías principales
INSERT INTO categorias (nombre, slug, padre_id, descripcion, icono, orden) VALUES
('Móviles', 'moviles', NULL, 'Smartphones y teléfonos móviles de segunda mano', 'bi-phone', 1),
('Tablets', 'tablets', NULL, 'Tablets y iPads de segunda mano', 'bi-tablet', 2),
('Portátiles', 'portatiles', NULL, 'Ordenadores portátiles de segunda mano', 'bi-laptop', 3),
('Accesorios', 'accesorios', NULL, 'Accesorios y complementos para dispositivos', 'bi-headphones', 4);

-- Subcategorías Móviles
INSERT INTO categorias (nombre, slug, padre_id, icono, orden) VALUES
('Apple iPhone', 'moviles-apple', 1, 'bi-apple', 1),
('Samsung Galaxy', 'moviles-samsung', 1, 'bi-phone-fill', 2),
('Xiaomi', 'moviles-xiaomi', 1, 'bi-phone', 3),
('Huawei', 'moviles-huawei', 1, 'bi-phone', 4),
('Google Pixel', 'moviles-google', 1, 'bi-google', 5);

-- Subcategorías Tablets
INSERT INTO categorias (nombre, slug, padre_id, icono, orden) VALUES
('iPad Apple', 'tablets-ipad', 2, 'bi-tablet-fill', 1),
('Samsung Galaxy Tab', 'tablets-samsung', 2, 'bi-tablet', 2);

-- Subcategorías Portátiles
INSERT INTO categorias (nombre, slug, padre_id, icono, orden) VALUES
('MacBook', 'portatiles-macbook', 3, 'bi-laptop-fill', 1),
('Windows', 'portatiles-windows', 3, 'bi-windows', 2),
('Chromebook', 'portatiles-chromebook', 3, 'bi-pc-display', 3);

-- Productos de ejemplo
INSERT INTO productos (nombre, slug, descripcion, especificaciones, categoria_id, marca, modelo, estado, precio_compra, precio_venta, precio_oferta, stock, destacado) VALUES
('iPhone 13 Pro 256GB Grafito', 'iphone-13-pro-256gb-grafito', 'iPhone 13 Pro en excelente estado, batería al 89%. Sin arañazos visibles. Liberado para cualquier operadora.', 'Pantalla: 6.1" Super Retina XDR OLED\nProcesador: A15 Bionic\nRAM: 6GB\nAlmacenamiento: 256GB\nCámara: Triple 12MP\nBatería: 3095 mAh (89%)\nSistema: iOS 17\nColor: Grafito\nEstado: Muy bueno', 5, 'Apple', 'iPhone 13 Pro', 'disponible', 420.00, 649.00, 599.00, 1, 1),

('Samsung Galaxy S23 128GB Negro', 'samsung-galaxy-s23-128gb-negro', 'Galaxy S23 en perfecto estado. Batería al 92%. Incluye cargador original.', 'Pantalla: 6.1" Dynamic AMOLED\nProcesador: Snapdragon 8 Gen 2\nRAM: 8GB\nAlmacenamiento: 128GB\nCámara: Triple\nBatería: 3900 mAh (92%)\nSistema: Android 14\nColor: Negro fantasma', 6, 'Samsung', 'Galaxy S23', 'disponible', 320.00, 499.00, NULL, 2, 1),

('iPhone 12 64GB Azul', 'iphone-12-64gb-azul', 'iPhone 12 en buen estado. Pantalla sin ralladuras. Batería al 81%.', 'Pantalla: 6.1" Super Retina XDR OLED\nProcesador: A14 Bionic\nRAM: 4GB\nAlmacenamiento: 64GB\nCámara: Dual 12MP\nBatería: 2815 mAh (81%)\nSistema: iOS 16\nColor: Azul\nEstado: Bueno', 5, 'Apple', 'iPhone 12', 'disponible', 200.00, 329.00, 299.00, 1, 0),

('MacBook Air M2 8GB 256GB Plata', 'macbook-air-m2-8gb-256gb-plata', 'MacBook Air M2 en excelente estado. Sin arañazos. Batería con 45 ciclos.', 'Pantalla: 13.6" Liquid Retina\nProcesador: Apple M2\nRAM: 8GB\nAlmacenamiento: 256GB SSD\nBatería: 45 ciclos\nSistema: macOS Sonoma\nColor: Plata\nAño: 2022', 13, 'Apple', 'MacBook Air M2', 'disponible', 750.00, 1099.00, 999.00, 1, 1),

('iPad Pro 11" 128GB WiFi Gris', 'ipad-pro-11-128gb-wifi-gris', 'iPad Pro 11 pulgadas en excelente estado. Pantalla Liquid Retina impecable.', 'Pantalla: 11" Liquid Retina\nProcesador: M2\nRAM: 8GB\nAlmacenamiento: 128GB\nConectividad: WiFi 6\nCámara: 12MP Ultra Wide\nSistema: iPadOS 17\nColor: Gris Espacial', 10, 'Apple', 'iPad Pro 11', 'disponible', 550.00, 749.00, NULL, 1, 1),

('Xiaomi 13T Pro 256GB Negro', 'xiaomi-13t-pro-256gb-negro', 'Xiaomi 13T Pro en perfecto estado. Cargador Turbo 120W incluido.', 'Pantalla: 6.67" AMOLED 144Hz\nProcesador: Dimensity 9200+\nRAM: 12GB\nAlmacenamiento: 256GB\nCámara: Leica Triple\nBatería: 5000 mAh (95%)\nColor: Negro alpino', 7, 'Xiaomi', '13T Pro', 'disponible', 280.00, 449.00, 399.00, 2, 0),

('HP Pavilion 15 i5-1235U 16GB', 'hp-pavilion-15-i5-1235u-16gb', 'Portátil HP Pavilion en buen estado. Teclado en perfecto estado. Pantalla sin píxeles muertos.', 'Pantalla: 15.6" FHD IPS\nProcesador: Intel Core i5-1235U\nRAM: 16GB DDR4\nAlmacenamiento: 512GB SSD\nGráficos: Intel Iris Xe\nSistema: Windows 11 Home\nColor: Plata natural', 14, 'HP', 'Pavilion 15', 'disponible', 380.00, 549.00, NULL, 1, 0),

('Samsung Galaxy Tab S9 FE WiFi', 'samsung-galaxy-tab-s9-fe-wifi', 'Galaxy Tab S9 FE en excelente estado. Con S Pen incluido.', 'Pantalla: 10.9" TFT LCD\nProcesador: Exynos 1380\nRAM: 6GB\nAlmacenamiento: 128GB\nCámara: 8MP + 10MP\nBatería: 8000 mAh\nIncluye S Pen', 11, 'Samsung', 'Galaxy Tab S9 FE', 'disponible', 220.00, 349.00, 319.00, 1, 0);

-- Piezas de reparación
INSERT INTO piezas (nombre, referencia, descripcion, categoria, stock, stock_minimo, precio_unitario, proveedor) VALUES
('Pantalla iPhone 13 OLED Original', 'PNT-IP13-OLED', 'Pantalla OLED original para iPhone 13, con digitalizador incluido', 'Pantallas', 8, 3, 89.00, 'iFixit España'),
('Pantalla iPhone 12 OLED', 'PNT-IP12-OLED', 'Pantalla OLED para iPhone 12, calidad OEM', 'Pantallas', 12, 3, 65.00, 'iFixit España'),
('Batería iPhone 13 3227mAh', 'BAT-IP13', 'Batería de repuesto para iPhone 13, capacidad original', 'Baterías', 15, 5, 28.00, 'MobileRepairs SL'),
('Batería Samsung S23', 'BAT-S23', 'Batería original Samsung Galaxy S23', 'Baterías', 10, 5, 32.00, 'MobileRepairs SL'),
('Conector carga iPhone 13 Lightning', 'CON-IP13-LGT', 'Puerto de carga Lightning para iPhone 13', 'Conectores', 20, 8, 12.00, 'iFixit España'),
('Pantalla MacBook Air M2 13.6"', 'PNT-MBA-M2', 'Pantalla Liquid Retina para MacBook Air M2', 'Pantallas', 3, 2, 280.00, 'Apple Service'),
('Teclado MacBook Air M2 ES', 'TEC-MBA-M2-ES', 'Teclado en español para MacBook Air M2', 'Teclados', 5, 2, 145.00, 'Apple Service'),
('Pantalla Samsung Galaxy S23', 'PNT-S23-AMOLED', 'Pantalla Dynamic AMOLED 2X para Samsung Galaxy S23', 'Pantallas', 6, 3, 78.00, 'Samsung Parts EU');

-- Movimientos de piezas de ejemplo
INSERT INTO movimientos_piezas (pieza_id, tipo, cantidad, motivo, usuario_id) VALUES
(1, 'entrada', 10, 'Compra inicial de stock', 1),
(2, 'entrada', 15, 'Compra inicial de stock', 1),
(3, 'entrada', 20, 'Compra inicial de stock', 1),
(1, 'salida', 2, 'Reparación iPhone 13 cliente', 1),
(3, 'salida', 5, 'Cambio de batería múltiple', 1),
(4, 'entrada', 10, 'Reposición stock', 1);

-- Pedido de ejemplo
INSERT INTO pedidos (codigo, usuario_id, estado, subtotal, gastos_envio, total, nombre_destinatario, direccion_envio, ciudad, provincia, codigo_postal, pais, stripe_payment_intent, stripe_payment_status) VALUES
('IPH-2025-0001', 2, 'entregado', 649.00, 0.00, 649.00, 'María García López', 'Calle Mayor 15, 2ºA', 'Córdoba', 'Córdoba', '14001', 'España', 'pi_test_example123', 'succeeded');

INSERT INTO lineas_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) VALUES
(1, 1, 'iPhone 13 Pro 256GB Grafito', 1, 649.00, 649.00);

-- Transacciones de ejemplo
INSERT INTO transacciones (tipo, concepto, importe, categoria, pedido_id, fecha) VALUES
('ingreso', 'Venta iPhone 13 Pro - Pedido IPH-2025-0001', 649.00, 'Ventas', 1, '2025-03-15'),
('gasto', 'Compra pantallas iPhone 13 OLED x10', 890.00, 'Piezas', NULL, '2025-03-01'),
('gasto', 'Compra baterías iPhone 13 x20', 560.00, 'Piezas', NULL, '2025-03-01'),
('ingreso', 'Servicio reparación Samsung S22', 120.00, 'Reparaciones', NULL, '2025-03-10'),
('gasto', 'Cuota mensual hosting VPS', 25.00, 'Operativos', NULL, '2025-03-05'),
('gasto', 'Material embalaje y envíos', 45.00, 'Operativos', NULL, '2025-03-08'),
('ingreso', 'Venta Samsung Galaxy S23 - Efectivo', 499.00, 'Ventas', NULL, '2025-03-20'),
('gasto', 'Compra Xiaomi 13T Pro segunda mano', 280.00, 'Dispositivos', NULL, '2025-03-18');

-- Mensaje de contacto de ejemplo
INSERT INTO contacto (nombre, email, asunto, mensaje) VALUES
('Juan Pérez', 'juan@email.com', 'Consulta sobre iPhone 12', '¿El iPhone 12 azul viene con cargador? ¿Se puede pagar en efectivo en la tienda?');

COMMIT;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista: resumen de ventas por mes
CREATE OR REPLACE VIEW v_ventas_mes AS
SELECT
    YEAR(fecha) AS anio,
    MONTH(fecha) AS mes,
    MONTHNAME(fecha) AS nombre_mes,
    SUM(CASE WHEN tipo = 'ingreso' THEN importe ELSE 0 END) AS total_ingresos,
    SUM(CASE WHEN tipo = 'gasto' THEN importe ELSE 0 END) AS total_gastos,
    SUM(CASE WHEN tipo = 'ingreso' THEN importe ELSE -importe END) AS beneficio
FROM transacciones
GROUP BY YEAR(fecha), MONTH(fecha);

-- Vista: stock bajo de piezas
CREATE OR REPLACE VIEW v_piezas_stock_bajo AS
SELECT id, nombre, referencia, stock, stock_minimo, proveedor
FROM piezas
WHERE stock <= stock_minimo AND activa = 1;

-- Vista: pedidos con datos de usuario
CREATE OR REPLACE VIEW v_pedidos_detalle AS
SELECT
    p.id, p.codigo, p.estado, p.total, p.created_at,
    u.nombre, u.apellidos, u.email,
    p.ciudad, p.provincia
FROM pedidos p
JOIN usuarios u ON p.usuario_id = u.id;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER //

-- Actualizar stock al procesar un pedido
CREATE PROCEDURE sp_reducir_stock(IN p_producto_id INT, IN p_cantidad INT)
BEGIN
    UPDATE productos
    SET stock = stock - p_cantidad
    WHERE id = p_producto_id AND stock >= p_cantidad;
    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;
END //

-- Generar código de pedido
CREATE PROCEDURE sp_generar_codigo_pedido(OUT p_codigo VARCHAR(20))
BEGIN
    DECLARE ultimo INT;
    SELECT COUNT(*) + 1 INTO ultimo FROM pedidos;
    SET p_codigo = CONCAT('IPH-', YEAR(NOW()), '-', LPAD(ultimo, 4, '0'));
END //

DELIMITER ;
