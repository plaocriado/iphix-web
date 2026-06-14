<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
        
        if (empty($_SESSION['iniciada'])) {
            session_regenerate_id(true);
            $_SESSION['iniciada'] = true;
        }
    }
}

function generarCSRF(): string {
    iniciarSesion();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function validarCSRF(string $token): bool {
    iniciarSesion();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function campoCSRF(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generarCSRF(), ENT_QUOTES, 'UTF-8') . '">';
}

function usuarioActual(): ?array {
    iniciarSesion();
    return $_SESSION['usuario'] ?? null;
}

function estaLogueado(): bool {
    return usuarioActual() !== null;
}

function esAdmin(): bool {
    $usuario = usuarioActual();
    return $usuario && $usuario['rol'] === 'admin';
}

function requiereLogin(): void {
    if (!estaLogueado()) {
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requiereAdmin(): void {
    requiereLogin();
    if (!esAdmin()) {
        header('Location: /?error=acceso_denegado');
        exit;
    }
}

function intentarLogin(string $email, string $password): array {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['exito' => false, 'error' => 'Email no válido'];
    }

    $usuario = dbQueryOne(
        'SELECT * FROM usuarios WHERE email = ? AND activo = 1',
        [$email]
    );

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        return ['exito' => false, 'error' => 'Credenciales incorrectas'];
    }

    
    iniciarSesion();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'       => $usuario['id'],
        'nombre'   => $usuario['nombre'],
        'apellidos'=> $usuario['apellidos'],
        'email'    => $usuario['email'],
        'rol'      => $usuario['rol'],
    ];

    
    dbExecute(
        'INSERT INTO sesiones_usuario (usuario_id, ip, user_agent) VALUES (?, ?, ?)',
        [$usuario['id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $_SERVER['HTTP_USER_AGENT'] ?? '']
    );

    return ['exito' => true, 'usuario' => $_SESSION['usuario']];
}

function cerrarSesion(): void {
    iniciarSesion();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

function registrarUsuario(array $datos): array {
    $nombre   = trim($datos['nombre'] ?? '');
    $apellidos= trim($datos['apellidos'] ?? '');
    $email    = filter_var(trim($datos['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $datos['password'] ?? '';
    $confirm  = $datos['confirm_password'] ?? '';

    
    if (empty($nombre) || empty($apellidos)) {
        return ['exito' => false, 'error' => 'Nombre y apellidos son obligatorios'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['exito' => false, 'error' => 'Email no válido'];
    }
    if (strlen($password) < 8) {
        return ['exito' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres'];
    }
    if ($password !== $confirm) {
        return ['exito' => false, 'error' => 'Las contraseñas no coinciden'];
    }

    
    $existe = dbQueryOne('SELECT id FROM usuarios WHERE email = ?', [$email]);
    if ($existe) {
        return ['exito' => false, 'error' => 'Ya existe una cuenta con ese email'];
    }

    
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    dbExecute(
        'INSERT INTO usuarios (nombre, apellidos, email, password) VALUES (?, ?, ?, ?)',
        [$nombre, $apellidos, $email, $hash]
    );
    $id = dbLastInsertId();

    return ['exito' => true, 'id' => $id];
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizarEntero(mixed $val): int {
    return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizarDecimal(mixed $val): float {
    return (float) filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

function obtenerCarrito(): array {
    iniciarSesion();
    return $_SESSION['carrito'] ?? [];
}

function añadirAlCarrito(int $productoId, int $cantidad = 1): bool {
    iniciarSesion();
    $producto = dbQueryOne(
        'SELECT id, nombre, precio_venta, precio_oferta, imagen_principal, stock FROM productos WHERE id = ? AND estado = "disponible" AND activo = 1',
        [$productoId]
    );
    if (!$producto) return false;

    $precio = $producto['precio_oferta'] ?? $producto['precio_venta'];
    if (!isset($_SESSION['carrito'][$productoId])) {
        $_SESSION['carrito'][$productoId] = [
            'id'      => $producto['id'],
            'nombre'  => $producto['nombre'],
            'precio'  => $precio,
            'imagen'  => $producto['imagen_principal'],
            'cantidad'=> 0,
            'stock'   => $producto['stock'],
        ];
    }
    $_SESSION['carrito'][$productoId]['cantidad'] += $cantidad;
    if ($_SESSION['carrito'][$productoId]['cantidad'] > $producto['stock']) {
        $_SESSION['carrito'][$productoId]['cantidad'] = $producto['stock'];
    }
    return true;
}

function eliminarDelCarrito(int $productoId): void {
    iniciarSesion();
    unset($_SESSION['carrito'][$productoId]);
}

function totalCarrito(): float {
    $total = 0;
    foreach (obtenerCarrito() as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    return $total;
}

function cantidadCarrito(): int {
    return array_sum(array_column(obtenerCarrito(), 'cantidad'));
}

function vaciarCarrito(): void {
    iniciarSesion();
    $_SESSION['carrito'] = [];
}

function formatearPrecio(float $precio): string {
    return number_format($precio, 2, ',', '.') . ' €';
}

function formatearFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}

function formatearFechaHora(string $fecha): string {
    return date('d/m/Y H:i', strtotime($fecha));
}

iniciarSesion();
