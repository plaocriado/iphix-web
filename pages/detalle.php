<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /pages/productos.php'); exit; }

$prod = dbQueryOne(
    "SELECT p.*, c.nombre AS cat_nombre, c.slug AS cat_slug FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.slug = ? AND p.activo = 1",
    [$slug]
);
if (!$prod) { header('HTTP/1.0 404 Not Found'); echo '<h1>Producto no encontrado</h1>'; exit; }

$imagenes = dbQuery('SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden', [$prod['id']]);
$relacionados = dbQuery(
    "SELECT p.*, c.nombre AS cat_nombre FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = ? AND p.id != ? AND p.estado = 'disponible' AND p.activo = 1 LIMIT 4",
    [$prod['categoria_id'], $prod['id']]
);

$precio    = $prod['precio_oferta'] ?? $prod['precio_venta'];
$descuento = $prod['precio_oferta'] ? round((1 - $prod['precio_oferta'] / $prod['precio_venta']) * 100) : 0;

$specs = [];
if ($prod['especificaciones']) {
    foreach (explode("\n", $prod['especificaciones']) as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) $specs[trim($parts[0])] = trim($parts[1]);
    }
}

$pageTitle = $prod['nombre'];
$metaDesc  = substr(strip_tags($prod['descripcion'] ?? ''), 0, 160);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem">
  <!-- Breadcrumb -->
  <nav style="margin-bottom:2rem">
    <ol style="list-style:none;display:flex;gap:0.5rem;font-size:0.84rem;color:var(--color-text-muted);flex-wrap:wrap">
      <li><a href="/" style="color:var(--color-text-muted)">Inicio</a></li>
      <li style="color:var(--color-text-dim)">/</li>
      <li><a href="/pages/productos.php?cat=<?= e($prod['cat_slug']) ?>" style="color:var(--color-text-muted)"><?= e($prod['cat_nombre']) ?></a></li>
      <li style="color:var(--color-text-dim)">/</li>
      <li style="color:var(--color-text)"><?= e($prod['nombre']) ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <!-- Galería -->
    <div class="col-lg-6">
      <div class="product-gallery-main mb-3">
        <img id="mainImage"
             src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>"
             alt="<?= e($prod['nombre']) ?>">
      </div>
      <?php if ($imagenes): ?>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>"
             class="product-thumb active" alt="Principal"
             onclick="document.getElementById('mainImage').src=this.src;document.querySelectorAll('.product-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')">
        <?php foreach ($imagenes as $img): ?>
        <img src="/assets/img/productos/<?= e($img['url']) ?>"
             class="product-thumb" alt="<?= e($img['alt'] ?? '') ?>"
             onclick="document.getElementById('mainImage').src=this.src;document.querySelectorAll('.product-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="col-lg-6">
      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <span class="badge-estado product-badge" style="position:static">
          <?= $prod['marca'] ?? '' ?>
        </span>
        <?php if ($prod['precio_oferta']): ?>
          <span class="product-badge badge-oferta" style="position:static">-<?= $descuento ?>% OFERTA</span>
        <?php endif; ?>
        <?php if ($prod['destacado']): ?>
          <span class="product-badge badge-destacado" style="position:static"><i class="bi bi-star-fill"></i> Destacado</span>
        <?php endif; ?>
      </div>

      <h1 style="font-size:clamp(1.4rem,3vw,2rem);margin-bottom:1.25rem;line-height:1.3"><?= e($prod['nombre']) ?></h1>

      <!-- Precio -->
      <div style="margin-bottom:1.75rem;padding:1.25rem;background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.12);border-radius:var(--radius-md)">
        <div style="display:flex;align-items:baseline;gap:0.75rem;flex-wrap:wrap">
          <span style="font-size:2.2rem;font-weight:800;font-family:var(--font-display);color:var(--color-text)"><?= formatearPrecio($precio) ?></span>
          <?php if ($prod['precio_oferta']): ?>
            <span style="font-size:1.1rem;text-decoration:line-through;color:var(--color-text-dim)"><?= formatearPrecio($prod['precio_venta']) ?></span>
            <span style="background:rgba(255,77,109,0.15);color:var(--color-danger);font-size:0.85rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:999px">Ahorras <?= formatearPrecio($prod['precio_venta'] - $prod['precio_oferta']) ?></span>
          <?php endif; ?>
        </div>
        <p style="font-size:0.82rem;color:var(--color-text-muted);margin:0.4rem 0 0">IVA incluido · Envío gratis +50€</p>
      </div>

      <!-- Stock y estado -->
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
        <?php if ($prod['stock'] > 0): ?>
          <span style="display:flex;align-items:center;gap:0.4rem;font-size:0.88rem;color:var(--color-accent);font-weight:600">
            <span style="width:8px;height:8px;background:var(--color-accent);border-radius:50%;animation:pulse 2s infinite"></span>
            En stock (<?= $prod['stock'] ?> unidad<?= $prod['stock'] > 1 ? 'es' : '' ?>)
          </span>
        <?php else: ?>
          <span style="color:var(--color-danger);font-size:0.88rem;font-weight:600"><i class="bi bi-x-circle"></i> Sin stock</span>
        <?php endif; ?>
        <span style="color:var(--color-text-dim)">·</span>
        <span style="font-size:0.85rem;color:var(--color-text-muted)">Envío en 24-48h</span>
      </div>

      <!-- Acciones -->
      <?php if ($prod['stock'] > 0): ?>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.75rem">
        <button class="btn-iphix btn-primary-iphix flex-grow-1" onclick="Cart.add(<?= $prod['id'] ?>)">
          <i class="bi bi-bag-plus-fill"></i> Añadir al carrito
        </button>
        <a href="/pages/carrito.php?comprar_ahora=<?= $prod['id'] ?>" class="btn-iphix btn-accent-iphix flex-grow-1">
          <i class="bi bi-lightning-charge-fill"></i> Comprar ahora
        </a>
      </div>
      <?php endif; ?>

      <!-- Descripción -->
      <?php if ($prod['descripcion']): ?>
      <div style="margin-bottom:1.75rem">
        <h3 style="font-size:0.9rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-muted);margin-bottom:0.75rem">Descripción</h3>
        <p style="color:var(--color-text-muted);line-height:1.8;font-size:0.95rem"><?= nl2br(e($prod['descripcion'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Mini specs -->
      <?php if (!empty($specs)): ?>
      <div style="background:var(--color-bg-card);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:1rem;margin-bottom:1.5rem">
        <h3 style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-muted);margin-bottom:0.75rem">Especificaciones clave</h3>
        <ul class="product-specs">
          <?php foreach (array_slice($specs, 0, 5, true) as $k => $v): ?>
          <li><span class="spec-label"><?= e($k) ?></span><span class="spec-value"><?= e($v) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($specs) > 5): ?>
        <button onclick="document.getElementById('allSpecs').style.display='block';this.style.display='none'" style="background:none;border:none;color:var(--color-primary);font-size:0.85rem;cursor:pointer;padding:0.5rem 0">Ver todas las especificaciones <i class="bi bi-chevron-down"></i></button>
        <ul class="product-specs" id="allSpecs" style="display:none">
          <?php foreach (array_slice($specs, 5, null, true) as $k => $v): ?>
          <li><span class="spec-label"><?= e($k) ?></span><span class="spec-value"><?= e($v) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Garantías -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
        <?php foreach ([
          ['bi-shield-check','Garantía 6 meses'],
          ['bi-arrow-counterclockwise','Devolución 14 días'],
          ['bi-truck','Envío rápido'],
          ['bi-patch-check','Revisado por técnico'],
        ] as $g): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;color:var(--color-text-muted)">
          <i class="bi <?= $g[0] ?>" style="color:var(--color-accent)"></i> <?= $g[1] ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Productos relacionados -->
  <?php if ($relacionados): ?>
  <div style="margin-top:4rem">
    <div class="section-header-line"></div>
    <h2 class="section-title" style="margin-bottom:1.5rem">También te puede interesar</h2>
    <div class="product-grid">
      <?php foreach ($relacionados as $rel): ?>
      <?php $rprecio = $rel['precio_oferta'] ?? $rel['precio_venta']; ?>
      <div class="product-card">
        <div class="product-card-img">
          <a href="/pages/detalle.php?slug=<?= e($rel['slug']) ?>">
            <img src="/assets/img/productos/<?= e($rel['imagen_principal']) ?>" alt="<?= e($rel['nombre']) ?>" loading="lazy">
          </a>
        </div>
        <div class="product-card-body">
          <div class="product-card-cat"><?= e($rel['cat_nombre']) ?></div>
          <div class="product-card-title"><a href="/pages/detalle.php?slug=<?= e($rel['slug']) ?>" style="color:inherit"><?= e($rel['nombre']) ?></a></div>
          <div class="product-card-price"><span class="price-current"><?= formatearPrecio($rprecio) ?></span></div>
          <div class="product-card-footer">
            <button class="btn-cart" onclick="Cart.add(<?= $rel['id'] ?>)"><i class="bi bi-bag-plus"></i> Añadir</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
