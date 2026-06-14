<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$q      = trim($_GET['q'] ?? '');
$oferta = !empty($_GET['oferta']);
$pagina = max(1,(int)($_GET['p'] ?? 1));
$porPag = 12;
$offset = ($pagina-1)*$porPag;

$where  = ["p.estado='disponible'","p.activo=1"];
$params = [];
if ($q) {
    $where[] = "(p.nombre LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ? OR p.descripcion LIKE ?)";
    $params  = ["%$q%","%$q%","%$q%","%$q%"];
}
if ($oferta) { $where[] = 'p.precio_oferta IS NOT NULL'; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$total = dbQueryOne("SELECT COUNT(*) AS t FROM productos p JOIN categorias c ON p.categoria_id = c.id $whereSQL", $params)['t'] ?? 0;
$totalPags = (int)ceil($total/$porPag);

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
ORDER BY p.destacado DESC, p.created_at DESC
LIMIT ? OFFSET ?";

$stmt = db()->prepare($sql);
$index = 1;
foreach ($params as $v) $stmt->bindValue($index++, $v);
$stmt->bindValue($index++, (int)$porPag, PDO::PARAM_INT);
$stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$resultados = $stmt->fetchAll();

$pageTitle = $q ? "Resultados para \"$q\"" : 'Buscador';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:3rem;padding-bottom:4rem">
  <!-- Barra búsqueda grande -->
  <div style="max-width:600px;margin:0 auto 2.5rem">
    <h1 style="font-size:1.6rem;text-align:center;margin-bottom:1.25rem">
      <?= $q ? 'Resultados para <span style="color:var(--color-primary)">'.e($q).'</span>' : 'Buscar dispositivos' ?>
    </h1>
    <form method="GET" action="/pages/buscar.php">
      <div class="search-bar" style="padding:0.4rem 0.4rem 0.4rem 1.25rem">
        <i class="bi bi-search search-bar-icon" style="font-size:1.1rem"></i>
        <input type="text" name="q" class="global-search-input" value="<?= e($q) ?>" placeholder="Nombre, marca, modelo..." style="font-size:1rem">
        <button type="submit" class="btn-iphix btn-primary-iphix btn-sm-iphix">Buscar</button>
      </div>
      <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;justify-content:center">
        <input type="checkbox" id="soloOfertas" name="oferta" value="1" <?= $oferta?'checked':'' ?> style="accent-color:var(--color-primary)">
        <label for="soloOfertas" style="font-size:0.88rem;color:var(--color-text-muted);cursor:pointer">Mostrar solo ofertas</label>
      </div>
    </form>
  </div>

  <?php if ($q): ?>
  <p style="color:var(--color-text-muted);margin-bottom:1.5rem;font-size:0.9rem">
    <?= $total ?> resultado<?= $total!==1?'s':'' ?> encontrado<?= $total!==1?'s':'' ?>
    <?= $oferta ? ' en oferta' : '' ?>
  </p>
  <?php endif; ?>

  <?php if (empty($resultados) && $q): ?>
  <div style="text-align:center;padding:4rem 2rem">
    <i class="bi bi-search" style="font-size:4rem;color:var(--color-text-dim);display:block;margin-bottom:1rem"></i>
    <h3 style="color:var(--color-text-muted)">Sin resultados para "<?= e($q) ?>"</h3>
    <p style="margin-bottom:2rem">Prueba con otro término o navega por nuestras categorías</p>
    <a href="/pages/productos.php" class="btn-iphix btn-primary-iphix"><i class="bi bi-grid-fill"></i> Ver catálogo completo</a>
  </div>
  <?php elseif (!empty($resultados)): ?>
  <div class="product-grid">
    <?php foreach ($resultados as $prod): ?>
    <?php $precio = $prod['precio_oferta'] ?? $prod['precio_venta']; $desc = $prod['precio_oferta'] ? round((1-$prod['precio_oferta']/$prod['precio_venta'])*100):0; ?>
    <div class="product-card">
      <div class="product-card-img">
        <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>">
          <img src="/assets/img/productos/<?= e($prod['imagen_principal']) ?>" alt="<?= e($prod['nombre']) ?>" loading="lazy">
        </a>
        <?php if ($prod['precio_oferta']): ?><span class="product-badge badge-oferta">-<?= $desc ?>%</span><?php endif; ?>
        <div class="product-actions-overlay">
          <a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" class="product-action-btn"><i class="bi bi-eye"></i></a>
          <button class="product-action-btn" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i></button>
        </div>
      </div>
      <div class="product-card-body">
        <div class="product-card-cat"><?= e($prod['cat_nombre']) ?></div>
        <div class="product-card-title"><a href="/pages/detalle.php?slug=<?= e($prod['slug']) ?>" style="color:inherit"><?= e($prod['nombre']) ?></a></div>
        <div class="product-card-price">
          <span class="price-current"><?= formatearPrecio($precio) ?></span>
          <?php if ($prod['precio_oferta']): ?><span class="price-old"><?= formatearPrecio($prod['precio_venta']) ?></span><span class="price-discount">-<?= $desc ?>%</span><?php endif; ?>
        </div>
        <div class="product-card-footer">
          <button class="btn-cart" onclick="Cart.add(<?= $prod['id'] ?>)"><i class="bi bi-bag-plus"></i> Añadir</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPags > 1): ?>
  <div style="display:flex;justify-content:center;margin-top:2.5rem">
    <div class="pagination-iphix">
      <?php if ($pagina>1): ?><a href="?<?= http_build_query(array_merge($_GET,['p'=>$pagina-1])) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
      <?php for($pp=max(1,$pagina-2);$pp<=min($totalPags,$pagina+2);$pp++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['p'=>$pp])) ?>" class="page-btn <?= $pp===$pagina?'active':'' ?>"><?= $pp ?></a>
      <?php endfor; ?>
      <?php if ($pagina<$totalPags): ?><a href="?<?= http_build_query(array_merge($_GET,['p'=>$pagina+1])) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
