<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['comprar_ahora'])) {
    añadirAlCarrito((int)$_GET['comprar_ahora']);
}

$carrito = obtenerCarrito();
$total   = totalCarrito();
$gastos  = $total >= 50 ? 0 : 4.99;
$totalFinal = $total + $gastos;

$pageTitle = 'Carrito de compra';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:3rem;padding-bottom:4rem">
  <div class="row justify-content-center">
    <div class="col-12">
      <h1 style="font-size:2rem;margin-bottom:0.25rem">
        <i class="bi bi-bag" style="color:var(--color-primary)"></i> Carrito de compra
      </h1>
      <p style="color:var(--color-text-muted);margin-bottom:2rem"><?= count($carrito) ?> producto<?= count($carrito) !== 1 ? 's' : '' ?></p>
    </div>
  </div>

  <?php if (empty($carrito)): ?>
  <div style="text-align:center;padding:5rem 2rem">
    <i class="bi bi-bag-x" style="font-size:5rem;color:var(--color-text-dim);display:block;margin-bottom:1.5rem"></i>
    <h2 style="color:var(--color-text-muted);font-size:1.5rem;margin-bottom:0.75rem">Tu carrito está vacío</h2>
    <p style="margin-bottom:2rem">¡Descubre nuestros dispositivos y encuentra una oferta!</p>
    <a href="/pages/productos.php" class="btn-iphix btn-primary-iphix"><i class="bi bi-grid-fill"></i> Ver catálogo</a>
  </div>
  <?php else: ?>
  <div class="row g-4">
    <!-- Items -->
    <div class="col-lg-8">
      <?php foreach ($carrito as $id => $item): ?>
      <div class="cart-item" data-id="<?= $id ?>">
        <img src="/assets/img/productos/<?= e($item['imagen']) ?>" alt="<?= e($item['nombre']) ?>" class="cart-item-img">
        <div style="flex:1;min-width:0">
          <div style="font-family:var(--font-display);font-weight:600;font-size:0.95rem;color:var(--color-text);margin-bottom:0.4rem"><?= e($item['nombre']) ?></div>
          <div style="font-size:1.1rem;font-weight:800;font-family:var(--font-display);color:var(--color-text)"><?= formatearPrecio($item['precio']) ?></div>
        </div>
        <div class="cart-qty-control">
          <button class="cart-qty-btn" data-action="-" title="Reducir">-</button>
          <span class="cart-qty-value"><?= $item['cantidad'] ?></span>
          <button class="cart-qty-btn" data-action="+" title="Aumentar">+</button>
        </div>
        <div style="text-align:right;min-width:80px">
          <div style="font-size:1rem;font-weight:700;color:var(--color-text);margin-bottom:0.4rem"><?= formatearPrecio($item['precio'] * $item['cantidad']) ?></div>
          <button onclick="Cart.remove(<?= $id ?>)" style="background:none;border:none;color:var(--color-danger);font-size:0.82rem;cursor:pointer"><i class="bi bi-trash3"></i></button>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Envío info -->
      <?php if ($total < 50): ?>
      <div class="alert-iphix alert-info">
        <i class="bi bi-truck"></i>
        <span>Añade <strong><?= formatearPrecio(50 - $total) ?></strong> más para conseguir envío gratis</span>
      </div>
      <?php else: ?>
      <div class="alert-iphix alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <span>¡Tienes <strong>envío gratis</strong> en este pedido!</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Resumen -->
    <div class="col-lg-4">
      <div class="card-iphix card-iphix-glow" style="position:sticky;top:100px">
        <h3 style="font-size:1.1rem;margin-bottom:1.5rem">Resumen del pedido</h3>
        <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.25rem">
          <div style="display:flex;justify-content:space-between;font-size:0.9rem">
            <span style="color:var(--color-text-muted)">Subtotal</span>
            <span><?= formatearPrecio($total) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:0.9rem">
            <span style="color:var(--color-text-muted)">Gastos de envío</span>
            <span style="color:<?= $gastos === 0 ? 'var(--color-accent)' : 'var(--color-text)' ?>"><?= $gastos === 0 ? 'GRATIS' : formatearPrecio($gastos) ?></span>
          </div>
          <div style="height:1px;background:var(--color-border)"></div>
          <div style="display:flex;justify-content:space-between">
            <span style="font-weight:700;font-family:var(--font-display)">Total</span>
            <span class="cart-total-value" style="font-size:1.3rem;font-weight:800;font-family:var(--font-display)"><?= formatearPrecio($totalFinal) ?></span>
          </div>
        </div>

        <?php if (estaLogueado()): ?>
        <a href="/pages/checkout.php" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;padding:1rem">
          <i class="bi bi-credit-card-fill"></i> Proceder al pago
        </a>
        <?php else: ?>
        <a href="/pages/login.php?redirect=/pages/checkout.php" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;padding:1rem">
          <i class="bi bi-box-arrow-in-right"></i> Inicia sesión para pagar
        </a>
        <p style="font-size:0.8rem;text-align:center;margin-top:0.75rem;color:var(--color-text-muted)">
          ¿Sin cuenta? <a href="/pages/login.php?modo=registro&redirect=/pages/checkout.php">Regístrate gratis</a>
        </p>
        <?php endif; ?>

        <a href="/pages/productos.php" style="display:block;text-align:center;margin-top:1rem;font-size:0.85rem;color:var(--color-text-muted)">
          <i class="bi bi-arrow-left"></i> Seguir comprando
        </a>

        <!-- Seguridad -->
        <div style="border-top:1px solid var(--color-border);padding-top:1rem;margin-top:1.25rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
          <?php foreach (['bi-shield-lock','bi-credit-card','bi-truck'] as $ic): ?>
          <i class="bi <?= $ic ?>" style="color:var(--color-text-dim);font-size:1.3rem" title="Pago seguro"></i>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
