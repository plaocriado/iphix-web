<?php
$pageTitle = 'Inicio';
$metaDesc  = 'iphix — Compra y venta de dispositivos electrónicos de segunda mano. Móviles, tablets y portátiles con garantía.';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$categorias   = dbQuery("SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.estado = 'disponible' AND p.activo = 1) AS total FROM categorias c WHERE c.padre_id IS NULL AND c.activa = 1 ORDER BY c.orden");
$destacados   = dbQuery("SELECT p.*, c.nombre AS cat_nombre FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.destacado = 1 AND p.estado = 'disponible' AND p.activo = 1 LIMIT 8");
$recientes    = dbQuery("SELECT p.*, c.nombre AS cat_nombre FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.estado = 'disponible' AND p.activo = 1 ORDER BY p.created_at DESC LIMIT 4");
$totalProductos = dbQueryOne("SELECT COUNT(*) AS total FROM productos WHERE estado = 'disponible' AND activo = 1")['total'] ?? 0;

require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid-lines"></div>
  <div class="container" style="position:relative;z-index:1">
    <div class="row align-items-center gy-5">
      <div class="col-lg-6">
        <div class="hero-badge"><span>Nueva temporada 2026</span></div>
        <h1>Tu próximo <span class="highlight">dispositivo</span><br>al mejor precio</h1>
        <p class="hero-desc">Móviles, tablets y portátiles de segunda mano revisados y garantizados. Ahorra hasta un 60% sin renunciar a la calidad.</p>
        <div class="hero-actions">
          <a href="/pages/productos.php" class="btn-iphix btn-primary-iphix">
            <i class="bi bi-grid-fill"></i> Ver catálogo
          </a>
          <a href="#categorias" class="btn-iphix btn-outline-iphix">
            <i class="bi bi-tag"></i> Categorías
          </a>
          <a href="/pages/soporte.php" class="btn-iphix btn-outline-iphix">
            <i class="bi bi-ticket-detailed"></i> Centro de Soporte
          </a>
        </div>
        <div class="hero-stats">
          <div>
            <div class="hero-stat-number" data-count="<?= $totalProductos ?>"><?= $totalProductos ?></div>
            <div class="hero-stat-label">Dispositivos</div>
          </div>
          <div>
            <div class="hero-stat-number" data-count="60">60%</div>
            <div class="hero-stat-label">Ahorro máx.</div>
          </div>
          <div>
            <div class="hero-stat-number" data-count="2">2</div>
            <div class="hero-stat-label">Años garantía</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-visual">
          <!-- Carrusel de ofertas -->
          <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner carousel-iphix">
              <?php foreach ($destacados as $i => $prod): ?>
              <?php if ($i >= 3) break; ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <div class="carousel-slide">
                  <div style="flex-shrink:0">
                    <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>"
                         alt="<?= e($prod['nombre']) ?>"
                         style="width:140px;height:140px;object-fit:cover;border-radius:var(--radius-md);background:#111620"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMTExNjIwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZpbGw9IiM0YTU1NjgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7F0Jk8L3RleHQ+PC9zdmc+'">
                  </div>
                  <div style="flex:1;min-width:0">
                    <div style="font-size:0.75rem;color:var(--color-primary);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.4rem"><?= e($prod['cat_nombre']) ?></div>
                    <h3 style="font-size:1.1rem;margin-bottom:0.75rem;color:var(--color-text)"><?= e($prod['nombre']) ?></h3>
                    <?php if ($prod['precio_oferta']): ?>
                      <div style="display:flex;align-items:baseline;gap:0.5rem;margin-bottom:1rem">
                        <span style="font-size:1.6rem;font-weight:800;font-family:var(--font-display);color:var(--color-text)"><?= formatearPrecio($prod['precio_oferta']) ?></span>
                        <span style="font-size:0.9rem;text-decoration:line-through;color:var(--color-text-dim)"><?= formatearPrecio($prod['precio_venta']) ?></span>
                      </div>
                    <?php else: ?>
                      <div style="font-size:1.6rem;font-weight:800;font-family:var(--font-display);color:var(--color-text);margin-bottom:1rem"><?= formatearPrecio($prod['precio_venta']) ?></div>
                    <?php endif; ?>
                    <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" class="btn-iphix btn-primary-iphix btn-sm-iphix">Ver dispositivo <i class="bi bi-arrow-right"></i></a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" style="width:40px;left:-20px">
              <span style="background:var(--color-bg-card);border:1px solid var(--color-border);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--color-text)"><i class="bi bi-chevron-left"></i></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" style="width:40px;right:-20px">
              <span style="background:var(--color-bg-card);border:1px solid var(--color-border);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--color-text)"><i class="bi bi-chevron-right"></i></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== BANDAS INFO ===== -->
<section style="background:var(--color-bg-card);border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border);padding:1.25rem 0">
  <div class="container">
    <div class="row g-3 text-center">
      <?php foreach ([
        ['bi-shield-check','Garantía 6 meses','En todos los dispositivos'],
        ['bi-truck','Envío gratuito','+de 50 € de compra'],
        ['bi-arrow-counterclockwise','Devolución 14 días','Sin preguntas'],
        ['bi-patch-check','Revisados','Por técnicos certificados'],
      ] as $item): ?>
      <div class="col-6 col-md-3">
        <div style="display:flex;align-items:center;gap:0.75rem;justify-content:center">
          <i class="bi <?= $item[0] ?>" style="font-size:1.4rem;color:var(--color-primary)"></i>
          <div style="text-align:left">
            <div style="font-size:0.85rem;font-weight:700;color:var(--color-text);font-family:var(--font-display)"><?= $item[1] ?></div>
            <div style="font-size:0.75rem;color:var(--color-text-muted)"><?= $item[2] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== CATEGORÍAS ===== -->
<section class="section" id="categorias">
  <div class="container">
    <div class="reveal">
      <div class="section-header-line"></div>
      <h2 class="section-title">Explora por categoría</h2>
      <p class="section-subtitle">Encuentra exactamente lo que buscas</p>
    </div>
    <div class="row g-3">
      <?php
      $iconos = ['bi-phone','bi-tablet','bi-laptop','bi-headphones'];
      foreach ($categorias as $i => $cat): ?>
      <div class="col-6 col-md-3 reveal reveal-delay-<?= $i+1 ?>">
        <a href="/pages/productos.php?cat=<?= e($cat['slug']) ?>" class="cat-card">
          <i class="bi <?= $iconos[$i] ?? 'bi-device-hdd' ?> cat-icon"></i>
          <div class="cat-name"><?= e($cat['nombre']) ?></div>
          <div class="cat-count"><?= $cat['total'] ?> disponibles</div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== PRODUCTOS DESTACADOS ===== -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="reveal d-flex justify-content-between align-items-end mb-4">
      <div>
        <div class="section-header-line"></div>
        <h2 class="section-title mb-0">Dispositivos destacados</h2>
      </div>
      <a href="/pages/productos.php" class="btn-iphix btn-outline-iphix btn-sm-iphix">Ver todos <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="product-grid">
      <?php foreach ($destacados as $i => $prod): ?>
      <?php
        $precio = $prod['precio_oferta'] ?? $prod['precio_venta'];
        $descuento = $prod['precio_oferta'] ? round((1 - $prod['precio_oferta'] / $prod['precio_venta']) * 100) : 0;
      ?>
      <div class="product-card reveal reveal-delay-<?= ($i % 4) + 1 ?>">
        <div class="product-card-img">
          <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>" alt="<?= e($prod['nombre']) ?>" loading="lazy">
          <?php if ($prod['precio_oferta']): ?>
            <span class="product-badge badge-oferta">-<?= $descuento ?>%</span>
          <?php elseif ($prod['destacado']): ?>
            <span class="product-badge badge-destacado"><i class="bi bi-star-fill"></i> Top</span>
          <?php endif; ?>
          <div class="product-actions-overlay">
            <button class="product-action-btn" title="Vista rápida" onclick="window.location='/pages/detalle.php?slug=<?= e($prod['slug']) ?>'"><i class="bi bi-eye"></i></button>
            <button class="product-action-btn" title="Añadir al carrito" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i></button>
          </div>
        </div>
        <div class="product-card-body">
          <div class="product-card-cat"><?= e($prod['cat_nombre']) ?></div>
          <div class="product-card-title">
            <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" style="color:inherit"><?= e($prod['nombre']) ?></a>
          </div>
          <div class="product-card-price">
            <span class="price-current"><?= formatearPrecio($precio) ?></span>
            <?php if ($prod['precio_oferta']): ?>
              <span class="price-old"><?= formatearPrecio($prod['precio_venta']) ?></span>
              <span class="price-discount">-<?= $descuento ?>%</span>
            <?php endif; ?>
          </div>
          <div class="product-card-footer">
            <button class="btn-cart" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i> Añadir al carrito</button>
            <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" class="product-action-btn" style="border-radius:var(--radius-sm)"><i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== ÚLTIMAS INCORPORACIONES ===== -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="reveal d-flex justify-content-between align-items-end mb-4">
      <div>
        <div class="section-header-line" style="background:linear-gradient(90deg,var(--color-accent),var(--color-primary))"></div>
        <h2 class="section-title mb-0">Recién llegados</h2>
      </div>
      <a href="/pages/productos.php?orden=reciente" class="btn-iphix btn-outline-iphix btn-sm-iphix">Ver todos <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($recientes as $i => $prod): ?>
      <?php $precio = $prod['precio_oferta'] ?? $prod['precio_venta']; ?>
      <div class="col-md-6 col-lg-3 reveal reveal-delay-<?= $i+1 ?>">
        <div class="product-card" style="display:flex;flex-direction:row;border-radius:var(--radius-md)">
          <div style="width:90px;height:90px;flex-shrink:0;background:#111620;overflow:hidden;border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
            <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>" alt="<?= e($prod['nombre']) ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy">
          </div>
          <div style="padding:0.75rem;flex:1;min-width:0">
            <div class="product-card-cat" style="font-size:0.7rem"><?= e($prod['cat_nombre']) ?></div>
            <div class="product-card-title" style="font-size:0.85rem;margin-bottom:0.4rem">
              <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" style="color:inherit"><?= e($prod['nombre']) ?></a>
            </div>
            <div style="font-size:1rem;font-weight:800;font-family:var(--font-display);color:var(--color-text)"><?= formatearPrecio($precio) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== NOSOTROS ===== -->
<section class="section" id="nosotros" style="background:var(--color-bg-card);border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border)">
  <div class="container">
    <div class="row align-items-center gy-5">
      <div class="col-lg-5 reveal">
        <div class="section-header-line"></div>
        <h2 class="section-title">¿Por qué elegirnos?</h2>
        <p style="color:var(--color-text-muted);line-height:1.8;margin-bottom:2rem">Somos una empresa local especializada en la reparación y venta de dispositivos electrónicos de segunda mano. Cada dispositivo que vendemos pasa por una revisión técnica exhaustiva.</p>
        <a href="#contacto" class="btn-iphix btn-primary-iphix"><i class="bi bi-chat-dots"></i> Contáctanos</a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach ([
            ['bi-tools','Revisión técnica','Cada dispositivo es comprobado por nuestros técnicos antes de la venta.'],
            ['bi-shield-check','Garantía incluida','Todos los productos incluyen garantía de 6 meses.'],
            ['bi-currency-euro','Mejor precio','Ahorra hasta un 60% respecto al precio de nuevo.'],
            ['bi-headset','Soporte local','Atención personalizada en Córdoba y online.'],
          ] as $i => $feat): ?>
          <div class="col-sm-6 reveal reveal-delay-<?= $i+1 ?>">
            <div class="info-card">
              <div class="info-icon"><i class="bi <?= $feat[0] ?>"></i></div>
              <h4 style="font-size:1rem;margin-bottom:0.5rem"><?= $feat[1] ?></h4>
              <p style="font-size:0.88rem;line-height:1.6"><?= $feat[2] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== CONTACTO ===== -->
<section class="section" id="contacto">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 reveal">
        <div class="contact-section">
          <div class="text-center mb-4">
            <div class="section-header-line mx-auto"></div>
            <h2 class="section-title">¿Tienes alguna pregunta?</h2>
            <p class="section-subtitle mb-0">Escríbenos y te responderemos en menos de 24 horas</p>
          </div>
          <?php
          $msgExito = $msgError = '';
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_contacto'])) {
            if (!validarCSRF($_POST['csrf_token'] ?? '')) {
              $msgError = 'Token de seguridad inválido.';
            } else {
              $nombre  = trim(strip_tags($_POST['nombre'] ?? ''));
              $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
              $asunto  = trim(strip_tags($_POST['asunto'] ?? ''));
              $mensaje = trim(strip_tags($_POST['mensaje'] ?? ''));
              if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
                $msgError = 'Por favor, rellena todos los campos.';
              } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msgError = 'El email no es válido.';
              } else {
                dbExecute('INSERT INTO contacto (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)', [$nombre, $email, $asunto, $mensaje]);
                $msgExito = '¡Mensaje enviado! Te responderemos pronto.';
              }
            }
          }
          ?>
          <?php if ($msgExito): ?><div class="alert-iphix alert-success"><i class="bi bi-check-circle-fill"></i><?= e($msgExito) ?></div><?php endif; ?>
          <?php if ($msgError): ?><div class="alert-iphix alert-error"><i class="bi bi-exclamation-triangle-fill"></i><?= e($msgError) ?></div><?php endif; ?>
          <form method="POST" action="#contacto">
            <?= campoCSRF() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input type="text" name="nombre" class="form-input" placeholder="Tu nombre" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-input" placeholder="tu@email.com" required>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label class="form-label">Asunto</label>
                  <input type="text" name="asunto" class="form-input" placeholder="¿En qué podemos ayudarte?" required>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label class="form-label">Mensaje</label>
                  <textarea name="mensaje" class="form-input" rows="4" placeholder="Cuéntanos tu consulta..." required></textarea>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" name="enviar_contacto" class="btn-iphix btn-primary-iphix w-100">
                  <i class="bi bi-send-fill"></i> Enviar mensaje
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
