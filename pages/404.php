<?php
http_response_code(404);
$pageTitle = 'Página no encontrada';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';
?>
<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem">
  <div>
    <div style="font-size:8rem;font-weight:900;font-family:var(--font-display);background:linear-gradient(135deg,var(--color-primary),var(--color-accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;margin-bottom:1rem">404</div>
    <h2 style="margin-bottom:0.75rem">Página no encontrada</h2>
    <p style="color:var(--color-text-muted);margin-bottom:2rem">La página que buscas no existe o ha sido movida.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <a href="/" class="btn-iphix btn-primary-iphix"><i class="bi bi-house-fill"></i> Ir al inicio</a>
      <a href="/pages/productos.php" class="btn-iphix btn-outline-iphix"><i class="bi bi-grid-fill"></i> Ver catálogo</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
