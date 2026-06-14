</div><!-- /main-content -->

<footer class="footer">
  <div class="container">
    <div class="row g-4">
      <!-- Brand -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-logo">iphix</div>
        <p class="footer-desc">Tu tienda de confianza de dispositivos electrónicos de segunda mano. Móviles, tablets y portátiles con garantía.</p>
        <div class="footer-social">
          <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <!-- Tienda -->
      <div class="col-lg-2 col-md-3 col-6">
        <div class="footer-heading">Tienda</div>
        <ul class="footer-links">
          <li><a href="/pages/productos.php?cat=moviles">Móviles</a></li>
          <li><a href="/pages/productos.php?cat=tablets">Tablets</a></li>
          <li><a href="/pages/productos.php?cat=portatiles">Portátiles</a></li>
          <li><a href="/pages/buscar.php?oferta=1">Ofertas</a></li>
        </ul>
      </div>
      <!-- Ayuda -->
      <div class="col-lg-2 col-md-3 col-6">
        <div class="footer-heading">Ayuda</div>
        <ul class="footer-links">
          <li><a href="/pages/perfil.php?tab=pedidos">Mis pedidos</a></li>
          <li><a href="/#contacto">Contacto</a></li>
          <li><a href="/#nosotros">Sobre nosotros</a></li>
          <li><a href="#">Garantía</a></li>
        </ul>
      </div>
      <!-- Contacto -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-heading">Contacto</div>
        <ul class="footer-links" style="list-style:none">
          <li style="display:flex;gap:0.6rem;margin-bottom:0.6rem;align-items:flex-start">
            <i class="bi bi-geo-alt-fill" style="color:var(--color-primary);flex-shrink:0;margin-top:2px"></i>
            <span style="color:var(--color-text-muted);font-size:0.9rem">Córdoba, España</span>
          </li>
          <li style="display:flex;gap:0.6rem;margin-bottom:0.6rem;align-items:center">
            <i class="bi bi-envelope-fill" style="color:var(--color-primary);flex-shrink:0"></i>
            <a href="mailto:info@iphix.es" style="color:var(--color-text-muted);font-size:0.9rem">info@iphix.es</a>
          </li>
          <li style="display:flex;gap:0.6rem;align-items:center">
            <i class="bi bi-phone-fill" style="color:var(--color-primary);flex-shrink:0"></i>
            <span style="color:var(--color-text-muted);font-size:0.9rem">+34 600 000 000</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span class="footer-bottom-text">© <?= date('Y') ?> iphix. Todos los derechos reservados.</span>
      <div style="display:flex;gap:1.5rem">
        <a href="#" style="color:var(--color-text-dim);font-size:0.82rem">Privacidad</a>
        <a href="#" style="color:var(--color-text-dim);font-size:0.82rem">Términos</a>
        <a href="#" style="color:var(--color-text-dim);font-size:0.82rem">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS Principal -->
<script src="/assets/js/main.js"></script>

<?php if (isset($_SESSION['flash'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  Toast.<?= $_SESSION['flash']['tipo'] ?>('<?= addslashes($_SESSION['flash']['mensaje']) ?>');
});
</script>
<?php unset($_SESSION['flash']); endif; ?>
</body>
</html>
