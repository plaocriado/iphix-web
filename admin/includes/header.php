<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requiereAdmin();
$adminUser = usuarioActual();
$adminInitials = strtoupper(substr($adminUser['nombre'],0,1).substr($adminUser['apellidos'],0,1));

$mensajesNuevos = dbQueryOne('SELECT COUNT(*) AS t FROM contacto WHERE leido = 0')['t'] ?? 0;
$pedidosPendientes = dbQueryOne("SELECT COUNT(*) AS t FROM pedidos WHERE estado = 'pendiente'")['t'] ?? 0;
$piezasStockBajo = dbQueryOne("SELECT COUNT(*) AS t FROM piezas WHERE stock <= stock_minimo AND activa = 1")['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= generarCSRF() ?>">
  <title>iphix Admin — <?= e($pageTitle ?? 'Panel') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
  <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
<div class="admin-wrapper">
<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">iphix</div>
    <button class="sidebar-toggle" id="sidebarToggle" title="Colapsar"><i class="bi bi-layout-sidebar-reverse"></i></button>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Principal</div>
    <a href="/admin/" class="nav-item <?= ($currentPage??'')==='' ? 'active':'' ?>">
      <i class="bi bi-speedometer2 nav-icon"></i><span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Tienda</div>
    <a href="/admin/productos.php" class="nav-item <?= ($currentPage??'')==='productos' ? 'active':'' ?>">
      <i class="bi bi-phone nav-icon"></i><span class="nav-label">Productos</span>
    </a>
    <a href="/pages/productos.php" class="nav-item <?= ($currentPage??'')==='categorias' ? 'active':'' ?>">
      <i class="bi bi-grid nav-icon"></i><span class="nav-label">Categorías</span>
    </a>
    <a href="/admin/pedidos.php" class="nav-item <?= ($currentPage??'')==='pedidos' ? 'active':'' ?>">
      <i class="bi bi-bag-check nav-icon"></i>
      <span class="nav-label">Pedidos</span>
      <?php if ($pedidosPendientes > 0): ?><span class="nav-badge"><?= $pedidosPendientes ?></span><?php endif; ?>
    </a>

    <div class="nav-section-label">Almacén</div>
    <a href="/admin/piezas.php" class="nav-item <?= ($currentPage??'')==='piezas' ? 'active':'' ?>">
      <i class="bi bi-tools nav-icon"></i>
      <span class="nav-label">Piezas</span>
      <?php if ($piezasStockBajo > 0): ?><span class="nav-badge" style="background:rgba(255,190,11,0.15);color:var(--a-warning)"><?= $piezasStockBajo ?></span><?php endif; ?>
    </a>

    <div class="nav-section-label">Gestión</div>
    <a href="/admin/usuarios.php" class="nav-item <?= ($currentPage??'')==='usuarios' ? 'active':'' ?>">
      <i class="bi bi-people nav-icon"></i><span class="nav-label">Usuarios</span>
    </a>
    <a href="/admin/contacto.php" class="nav-item <?= ($currentPage??'')==='contacto' ? 'active':'' ?>">
      <i class="bi bi-envelope nav-icon"></i>
      <span class="nav-label">Mensajes</span>
      <?php if ($mensajesNuevos > 0): ?><span class="nav-badge"><?= $mensajesNuevos ?></span><?php endif; ?>
    </a>
    <a href="/admin/tickets.php" class="nav-item <?= ($currentPage??'')==='tickets' ? 'active':'' ?>">
      <i class="bi bi-ticket-detailed"></i>
      <span class="nav-label">Tickets de Soporte</span>
    </a>
    <a href="/admin/finanzas.php" class="nav-item <?= ($currentPage??'')==='finanzas' ? 'active':'' ?>">
      <i class="bi bi-graph-up-arrow nav-icon"></i><span class="nav-label">Finanzas</span>
    </a>

    <div class="nav-section-label">Sistema</div>
    <a href="/" target="_blank" class="nav-item">
      <i class="bi bi-box-arrow-up-right nav-icon"></i><span class="nav-label">Ver tienda</span>
    </a>
    <a href="/pages/logout.php" class="nav-item" style="color:var(--a-danger)">
      <i class="bi bi-box-arrow-right nav-icon"></i><span class="nav-label">Cerrar sesión</span>
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-avatar-sm"><?= $adminInitials ?></div>
    <div class="user-info">
      <div class="user-name"><?= e($adminUser['nombre'].' '.$adminUser['apellidos']) ?></div>
      <div class="user-role">Administrador</div>
    </div>
  </div>
</aside>
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- MAIN -->
<main class="admin-main" id="adminMain">
  <!-- TOPBAR -->
  <header class="admin-topbar">
    <div style="display:flex;align-items:center;gap:1rem">
      <button class="topbar-btn d-lg-none" id="mobileSidebarBtn"><i class="bi bi-list"></i></button>
      <div class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    </div>
    <div class="topbar-search d-none d-md-flex">
      <i class="bi bi-search topbar-search-icon"></i>
      <input type="text" placeholder="Buscar en el panel...">
    </div>
    <div class="topbar-actions">
      <a href="/admin/contacto.php" class="topbar-btn" title="Mensajes">
        <i class="bi bi-envelope"></i>
        <?php if ($mensajesNuevos > 0): ?><span class="topbar-notif-dot"></span><?php endif; ?>
      </a>
      <a href="/admin/pedidos.php" class="topbar-btn" title="Pedidos pendientes">
        <i class="bi bi-bag-check"></i>
        <?php if ($pedidosPendientes > 0): ?><span class="topbar-notif-dot"></span><?php endif; ?>
      </a>
    </div>
  </header>
  <!-- CONTENT -->
  <div class="admin-content">
