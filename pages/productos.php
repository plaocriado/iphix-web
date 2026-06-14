<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$catSlug   = trim($_GET['cat']    ?? '');
$marca     = trim($_GET['marca']  ?? '');
$estado    = trim($_GET['estado'] ?? '');
$precioMin = max(0,   (int)($_GET['precio_min'] ?? 0));
$precioMax = min(9999,(int)($_GET['precio_max'] ?? 9999));
$orden     = $_GET['orden'] ?? 'reciente';
$oferta    = !empty($_GET['oferta']);
$pagina    = max(1, (int)($_GET['p'] ?? 1));
$porPagina = 12;
$offset    = ($pagina - 1) * $porPagina;

$categoria = null;
if ($catSlug) {
    $categoria = dbQueryOne('SELECT * FROM categorias WHERE slug = ? AND activa = 1', [$catSlug]);
    
    $catIds = $categoria ? [$categoria['id']] : [];
    if ($categoria) {
        $hijos = dbQuery('SELECT id FROM categorias WHERE padre_id = ?', [$categoria['id']]);
        foreach ($hijos as $h) $catIds[] = $h['id'];
    }
}

$where = ['p.estado = "disponible"', 'p.activo = 1'];
$params = [];
if (!empty($catIds)) {
    $placeholders = implode(',', array_fill(0, count($catIds), '?'));
    $where[] = "p.categoria_id IN ($placeholders)";
    $params = array_merge($params, $catIds);
}
if ($marca) { $where[] = 'p.marca = ?'; $params[] = $marca; }
if ($estado && $estado !== 'disponible') {  }
if ($precioMin > 0)   { $where[] = 'COALESCE(p.precio_oferta, p.precio_venta) >= ?'; $params[] = $precioMin; }
if ($precioMax < 9999){ $where[] = 'COALESCE(p.precio_oferta, p.precio_venta) <= ?'; $params[] = $precioMax; }
if ($oferta)          { $where[] = 'p.precio_oferta IS NOT NULL'; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$ordenSQL = match($orden) {
    'precio_asc'  => 'COALESCE(p.precio_oferta, p.precio_venta) ASC',
    'precio_desc' => 'COALESCE(p.precio_oferta, p.precio_venta) DESC',
    'nombre'      => 'p.nombre ASC',
    default       => 'p.created_at DESC',
};

$totalRows = dbQueryOne("SELECT COUNT(*) AS t FROM productos p JOIN categorias c ON p.categoria_id = c.id $whereSQL", $params)['t'] ?? 0;
$totalPaginas = (int) ceil($totalRows / $porPagina);

$sql = "SELECT 
    p.id,
    p.nombre,
    p.slug,
    p.descripcion,
    p.especificaciones,
    p.categoria_id,
    p.marca,
    p.modelo,
    p.estado,
    p.precio_venta,
    p.precio_oferta,
    p.stock,
    p.imagen_principal,
    p.destacado,
    p.created_at,
    c.nombre AS cat_nombre
FROM productos p
JOIN categorias c ON p.categoria_id = c.id
$whereSQL
ORDER BY $ordenSQL
LIMIT ? OFFSET ?";

$stmt = db()->prepare($sql);
$index = 1;
foreach ($params as $v) $stmt->bindValue($index++, $v);
$stmt->bindValue($index++, (int)$porPagina, PDO::PARAM_INT);
$stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$marcas = dbQuery("SELECT DISTINCT marca FROM productos WHERE estado = 'disponible' AND activo = 1 AND marca IS NOT NULL ORDER BY marca");

$categoriasSidebar = dbQuery("SELECT * FROM categorias WHERE padre_id IS NULL AND activa = 1 ORDER BY orden");

$pageTitle = $categoria ? $categoria['nombre'] : 'Todos los productos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:3rem;padding-bottom:4rem">
  <!-- Breadcrumb -->
  <nav style="margin-bottom:2rem">
    <ol style="list-style:none;display:flex;gap:0.5rem;font-size:0.85rem;color:var(--color-text-muted)">
      <li><a href="/" style="color:var(--color-text-muted)">Inicio</a></li>
      <li style="color:var(--color-text-dim)">/</li>
      <li style="color:var(--color-text)"><?= $categoria ? e($categoria['nombre']) : 'Todos los productos' ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- SIDEBAR FILTROS -->
    <div class="col-lg-3">
      <div class="filter-sidebar">
        <form method="GET" id="filtrosForm">
          <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>

          <!-- Categorías -->
          <div class="filter-group">
            <div class="filter-title">Categoría</div>
            <?php foreach ($categoriasSidebar as $c): ?>
            <div style="margin-bottom:0.35rem">
              <a href="/pages/productos.php?cat=<?= e($c['slug']) ?>"
                 style="font-size:0.88rem;color:<?= $catSlug === $c['slug'] ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>;display:flex;justify-content:space-between;padding:0.3rem 0">
                <?= e($c['nombre']) ?>
                <?php if ($catSlug === $c['slug']): ?><i class="bi bi-check2"></i><?php endif; ?>
              </a>
            </div>
            <?php endforeach; ?>
            <a href="/pages/productos.php" style="font-size:0.82rem;color:var(--color-text-dim)">Ver todas</a>
          </div>

          <!-- Precio -->
          <div class="filter-group">
            <div class="filter-title">Precio</div>
            <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem">
              <span id="precio_min_label"><?= $precioMin ?> €</span>
              <span id="precio_max_label"><?= $precioMax >= 9999 ? '∞' : $precioMax . ' €' ?></span>
            </div>
            <input type="range" id="precio_min" name="precio_min" min="0" max="2000" step="25" value="<?= $precioMin ?>" class="w-100 mb-2">
            <input type="range" id="precio_max" name="precio_max" min="0" max="2000" step="25" value="<?= min($precioMax, 2000) ?>" class="w-100">
          </div>

          <!-- Marca -->
          <div class="filter-group">
            <div class="filter-title">Marca</div>
            <?php foreach ($marcas as $m): ?>
            <div class="form-check" style="margin-bottom:0.3rem">
              <input class="form-check-input" type="radio" name="marca" id="marca_<?= e($m['marca']) ?>" value="<?= e($m['marca']) ?>" <?= $marca === $m['marca'] ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="form-check-label" for="marca_<?= e($m['marca']) ?>"><?= e($m['marca']) ?></label>
            </div>
            <?php endforeach; ?>
            <?php if ($marca): ?><a href="?" onclick="document.getElementById('filtrosForm').reset()" style="font-size:0.8rem;color:var(--color-danger)">Quitar filtro</a><?php endif; ?>
          </div>

          <!-- Ofertas -->
          <div class="filter-group">
            <div class="filter-title">Ofertas</div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="oferta" id="soloOfertas" value="1" <?= $oferta ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="form-check-label" for="soloOfertas">Solo en oferta</label>
            </div>
          </div>

          <button type="submit" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center">
            <i class="bi bi-funnel-fill"></i> Aplicar filtros
          </button>
          <a href="/pages/productos.php<?= $catSlug ? '?cat='.$catSlug : '' ?>" class="btn-iphix btn-outline-iphix w-100 mt-2" style="justify-content:center">
            <i class="bi bi-x-circle"></i> Limpiar
          </a>
        </form>
      </div>
    </div>

    <!-- GRID PRODUCTOS -->
    <div class="col-lg-9">
      <!-- Header resultados -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem">
        <div>
          <h2 style="font-size:1.3rem;margin-bottom:0.15rem"><?= $categoria ? e($categoria['nombre']) : 'Todos los productos' ?></h2>
          <p style="font-size:0.85rem;margin:0"><?= $totalRows ?> resultado<?= $totalRows !== 1 ? 's' : '' ?></p>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem">
          <span style="font-size:0.82rem;color:var(--color-text-muted)">Ordenar:</span>
          <select name="orden" form="filtrosForm" class="form-input" style="width:auto;padding:0.4rem 0.75rem;font-size:0.85rem" onchange="this.form.submit()">
            <option value="reciente"    <?= $orden === 'reciente'    ? 'selected' : '' ?>>Más recientes</option>
            <option value="precio_asc"  <?= $orden === 'precio_asc'  ? 'selected' : '' ?>>Precio: menor a mayor</option>
            <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
            <option value="nombre"      <?= $orden === 'nombre'      ? 'selected' : '' ?>>Nombre A-Z</option>
          </select>
        </div>
      </div>

      <?php if (empty($products)): ?>
      <div style="text-align:center;padding:4rem 2rem">
        <i class="bi bi-inbox" style="font-size:3.5rem;color:var(--color-text-dim);display:block;margin-bottom:1rem"></i>
        <h3 style="color:var(--color-text-muted);font-size:1.2rem">No hay productos con estos filtros</h3>
        <p>Prueba a cambiar los criterios de búsqueda</p>
        <a href="/pages/productos.php" class="btn-iphix btn-outline-iphix mt-2">Ver todos los productos</a>
      </div>
      <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $prod): ?>
        <?php
          $precio = $prod['precio_oferta'] ?? $prod['precio_venta'];
          $descuento = $prod['precio_oferta'] ? round((1 - $prod['precio_oferta'] / $prod['precio_venta']) * 100) : 0;
        ?>
        <div class="product-card">
          <div class="product-card-img">
            <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>">
              <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>" alt="<?= e($prod['nombre']) ?>" loading="lazy">
            </a>
            <?php if ($prod['precio_oferta']): ?>
              <span class="product-badge badge-oferta">-<?= $descuento ?>%</span>
            <?php endif; ?>
            <div class="product-actions-overlay">
              <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" class="product-action-btn" title="Ver detalle"><i class="bi bi-eye"></i></a>
              <button class="product-action-btn" title="Añadir al carrito" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i></button>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-card-cat"><?= e($prod['cat_nombre']) ?> · <?= e($prod['marca'] ?? '') ?></div>
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
              <button class="btn-cart" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i> Añadir</button>
              <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" class="product-action-btn" style="border-radius:var(--radius-sm)"><i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Paginación -->
      <?php if ($totalPaginas > 1): ?>
      <div style="display:flex;justify-content:center;margin-top:2.5rem">
        <div class="pagination-iphix">
          <?php if ($pagina > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina - 1])) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['p' => $p])) ?>" class="page-btn <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina + 1])) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
