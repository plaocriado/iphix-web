<?php
require_once __DIR__ . '/auth.php';
$usuario  = usuarioActual();
$csrfMeta = generarCSRF();
$cartCount = cantidadCarrito();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e($csrfMeta) ?>">
  <meta name="description" content="<?= e($metaDesc ?? 'iphix - Compra y venta de dispositivos electrónicos de segunda mano. Móviles, tablets y portátiles al mejor precio.') ?>">
  <title><?= e($pageTitle ?? 'iphix') ?> — Dispositivos de Segunda Mano</title>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Estilos propios -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-iphix">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between w-100 gap-3">
      <!-- Logo -->
      <a href="/" class="navbar-brand-iphix flex-shrink-0">iphix</a>

      <!-- Buscador central (md+) -->
      <div class="search-bar flex-grow-1 d-none d-lg-flex" style="max-width:400px">
        <i class="bi bi-search search-bar-icon"></i>
        <input type="text" class="global-search-input" placeholder="Buscar dispositivos, marcas...">
      </div>

      <!-- Nav links (lg+) -->
      <ul class="nav d-none d-lg-flex align-items-center gap-1">
        <li><a class="nav-link" href="/">Inicio</a></li>
        <li class="dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categorías</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/pages/productos.php?cat=moviles"><i class="bi bi-phone me-2"></i>Móviles</a></li>
            <li><a class="dropdown-item" href="/pages/productos.php?cat=tablets"><i class="bi bi-tablet me-2"></i>Tablets</a></li>
            <li><a class="dropdown-item" href="/pages/productos.php?cat=portatiles"><i class="bi bi-laptop me-2"></i>Portátiles</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/pages/productos.php"><i class="bi bi-grid me-2"></i>Ver todo</a></li>
          </ul>
        </li>
        <li><a class="nav-link" href="/pages/buscar.php">Ofertas</a></li>
      </ul>

      <!-- Iconos acción -->
      <div class="navbar-icons d-flex align-items-center gap-1">
        <!-- Buscar (mobile) -->
        <a href="/pages/buscar.php" class="d-lg-none" title="Buscar"><i class="bi bi-search"></i></a>
        <!-- Carrito -->
        <a href="/pages/carrito.php" title="Carrito" style="position:relative">
          <i class="bi bi-bag"></i>
          <span class="cart-badge" style="<?= $cartCount === 0 ? 'display:none' : '' ?>"><?= $cartCount ?></span>
        </a>
        <!-- Usuario -->
        <?php if ($usuario): ?>
          <div class="dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" title="Mi cuenta" style="display:flex;align-items:center;gap:0.4rem;color:var(--color-text-muted);padding:0.4rem 0.6rem;border-radius:8px;background:rgba(255,255,255,0.05)">
              <span style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-accent));display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:800;color:#000;font-family:var(--font-display)">
                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
              </span>
              <span class="d-none d-md-inline" style="font-size:0.88rem;font-weight:500;color:var(--color-text)"><?= e($usuario['nombre']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/pages/perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
              <li><a class="dropdown-item" href="/pages/perfil.php?tab=pedidos"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
              <?php if ($usuario['rol'] === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/" style="color:var(--color-primary)"><i class="bi bi-speedometer2 me-2"></i>Panel Admin</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/pages/logout.php" style="color:var(--color-danger)"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="/pages/login.php" class="btn-iphix btn-outline-iphix btn-sm-iphix d-none d-md-inline-flex">
            <i class="bi bi-person"></i> Entrar
          </a>
        <?php endif; ?>
        <!-- Toggle mobile -->
        <button class="navbar-toggler d-lg-none" id="mobileMenuToggle" style="background:rgba(255,255,255,0.05);border:1px solid var(--color-border);border-radius:8px;padding:0.4rem 0.6rem;color:var(--color-text)">
          <i class="bi bi-list fs-5"></i>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" style="display:none;padding-top:1rem;border-top:1px solid var(--color-border);margin-top:0.75rem">
      <div class="search-bar mb-3">
        <i class="bi bi-search search-bar-icon"></i>
        <input type="text" class="global-search-input" placeholder="Buscar...">
      </div>
      <nav style="display:flex;flex-direction:column;gap:0.25rem">
        <a href="/" class="nav-link">Inicio</a>
        <a href="/pages/productos.php?cat=moviles" class="nav-link">Móviles</a>
        <a href="/pages/productos.php?cat=tablets" class="nav-link">Tablets</a>
        <a href="/pages/productos.php?cat=portatiles" class="nav-link">Portátiles</a>
        <a href="/pages/productos.php" class="nav-link">Ver todo</a>
        <?php if (!$usuario): ?>
          <a href="/pages/login.php" class="btn-iphix btn-outline-iphix mt-2" style="justify-content:center">Iniciar sesión</a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</nav>

<script>
document.getElementById('mobileMenuToggle')?.addEventListener('click', () => {
  const menu = document.getElementById('mobileMenu');
  menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
});
</script>

<div class="main-content">
