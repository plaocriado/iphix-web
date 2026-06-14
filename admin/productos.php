<?php
$pageTitle   = 'Gestión de Productos';
$currentPage = 'productos';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'crear' || $action === 'editar') {
            $nombre      = trim(strip_tags($_POST['nombre'] ?? ''));
            $descripcion = trim(strip_tags($_POST['descripcion'] ?? ''));
            $specs       = trim($_POST['especificaciones'] ?? '');
            $catId       = (int)($_POST['categoria_id'] ?? 0);
            $marca       = trim(strip_tags($_POST['marca'] ?? ''));
            $modelo      = trim(strip_tags($_POST['modelo'] ?? ''));
            $estado      = $_POST['estado'] ?? 'disponible';
            $pventa      = (float)str_replace(',','.',($_POST['precio_venta']   ?? 0));
            $poferta     = !empty($_POST['precio_oferta']) ? (float)str_replace(',','.',($_POST['precio_oferta'])) : null;
            $stock       = max(0,(int)($_POST['stock'] ?? 0));
            $destacado   = isset($_POST['destacado']) ? 1 : 0;

            if (!$nombre || !$catId || !$pventa) { $errors[] = 'Nombre, categoría y precio son obligatorios.'; }
            else {
                
                $slug = strtolower(preg_replace('/[^a-z0-9]+/','',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$nombre).'-'.$marca.'-'.$modelo.'-'.uniqid()));
                
                $imagen = 'default.jpg';
                if (!empty($_FILES['imagen']['name'])) {
                    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                        $imagen = $slug . '.' . $ext;
                        move_uploaded_file($_FILES['imagen']['tmp_name'], UPLOADS_PATH . $imagen);
                    }
                }

                if ($action === 'crear') {
                    dbExecute("INSERT INTO productos (nombre,slug,descripcion,especificaciones,categoria_id,marca,modelo,estado,precio_venta,precio_oferta,stock,imagen_principal,destacado) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        [$nombre,$slug,$descripcion,$specs,$catId,$marca,$modelo,$estado,$pventa,$poferta,$stock,$imagen,$destacado]);
                    $success = 'Producto creado correctamente.';
                } else {
                    $prodId = (int)($_POST['prod_id'] ?? 0);
                    $sql = "UPDATE productos SET nombre=?,descripcion=?,especificaciones=?,categoria_id=?,marca=?,modelo=?,estado=?,precio_venta=?,precio_oferta=?,stock=?,destacado=?";
                    $params = [$nombre,$descripcion,$specs,$catId,$marca,$modelo,$estado,$pventa,$poferta,$stock,$destacado];
                    if ($imagen !== 'default.jpg') { $sql .= ",imagen_principal=?"; $params[] = $imagen; }
                    $sql .= " WHERE id=?"; $params[] = $prodId;
                    dbExecute($sql, $params);
                    $success = 'Producto actualizado.';
                }
            }
        } elseif ($_POST['_action'] === 'eliminar') {
            $prodId = (int)($_POST['prod_id'] ?? 0);
            dbExecute('UPDATE productos SET activo=0 WHERE id=?', [$prodId]);
            $success = 'Producto eliminado.';
        } elseif ($_POST['_action'] === 'toggle_destacado') {
            $prodId = (int)($_POST['prod_id'] ?? 0);
            dbExecute('UPDATE productos SET destacado = NOT destacado WHERE id=?', [$prodId]);
            $success = 'Producto actualizado.';
        }
    }
}

$search  = trim($_GET['q'] ?? '');
$catFil  = (int)($_GET['cat'] ?? 0);
$pagina  = max(1,(int)($_GET['p'] ?? 1));
$porPag  = 20;
$offset  = ($pagina-1)*$porPag;

$where = ['activo = 1'];
$params = [];
if ($search) { $where[] = '(nombre LIKE ? OR marca LIKE ? OR modelo LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($catFil) { $where[] = 'categoria_id = ?'; $params[] = $catFil; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = dbQueryOne("SELECT COUNT(*) AS t FROM productos p JOIN categorias c ON p.categoria_id=c.id $whereSQL", $params)['t'] ?? 0;
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
ORDER BY p.created_at DESC
LIMIT ? OFFSET ?";

$stmt = db()->prepare($sql);

$index = 1;
foreach ($params as $value) {
    $stmt->bindValue($index++, $value);
}

$stmt->bindValue($index++, (int)$porPag, PDO::PARAM_INT);
$stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$productos = $stmt->fetchAll();

$categorias = dbQuery('SELECT * FROM categorias WHERE activa=1 ORDER BY padre_id, orden');
$categoriaFlat = dbQuery('SELECT c.id, CONCAT(IFNULL(p.nombre,""),CASE WHEN c.padre_id IS NULL THEN "" ELSE " → " END, c.nombre) AS nombre_full FROM categorias c LEFT JOIN categorias p ON c.padre_id=p.id WHERE c.activa=1 ORDER BY c.padre_id, c.orden');
?>

<!-- Flash messages -->
<?php foreach ($errors as $e): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($e) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<!-- Header -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <div>
    <p style="color:var(--a-text-muted);font-size:0.88rem;margin:0"><?= $total ?> productos en total</p>
  </div>
  <button class="btn-admin btn-admin-primary" onclick="openModal('modalCrear')">
    <i class="bi bi-plus-lg"></i> Nuevo producto
  </button>
</div>

<!-- Filtros -->
<div class="admin-table-wrap" style="margin-bottom:1rem">
  <div style="padding:1rem 1.25rem">
    <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
      <div class="topbar-search" style="flex:1;min-width:200px">
        <i class="bi bi-search topbar-search-icon"></i>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre, marca...">
      </div>
      <select name="cat" class="admin-form-input" style="width:auto;padding:0.4rem 0.75rem">
        <option value="">Todas las categorías</option>
        <?php foreach ($categoriaFlat as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFil===$c['id']?'selected':'' ?>><?= e($c['nombre_full']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-admin btn-admin-outline"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="/admin/productos.php" class="btn-admin btn-admin-outline"><i class="bi bi-x"></i></a>
    </form>
  </div>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr>
      <th style="width:50px"></th>
      <th>Producto</th>
      <th>Categoría</th>
      <th>Precio</th>
      <th>Stock</th>
      <th>Estado</th>
      <th>Destacado</th>
      <th>Acciones</th>
    </tr></thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
    <?php
    $precioActual = $p['precio_oferta'] ?? $p['precio_venta'];
    $badgeEstado = match($p['estado']) {
      'disponible'=>'badge-green','en_reparacion'=>'badge-yellow','vendido'=>'badge-red', default=>'badge-gray'
    };
    ?>
    <tr>
      <td><img src="/assets/img/productos/<?= e($p['imagen_principal']) ?>" style="width:42px;height:42px;object-fit:cover;border-radius:6px;background:#0e1220" alt=""></td>
      <td>
        <strong style="font-size:0.88rem"><?= e($p['nombre']) ?></strong>
        <?php if ($p['marca']): ?><div style="font-size:0.75rem;color:var(--a-text-muted)"><?= e($p['marca']) ?><?= $p['modelo'] ? ' · '.$p['modelo']:'' ?></div><?php endif; ?>
      </td>
      <td><span class="badge-a badge-gray"><?= e($p['cat_nombre']) ?></span></td>
      <td>
        <strong><?= number_format($precioActual,2,',','.').' €' ?></strong>
        <?php if ($p['precio_oferta']): ?><div style="font-size:0.75rem;text-decoration:line-through;color:var(--a-text-muted)"><?= number_format($p['precio_venta'],2,',','.').' €' ?></div><?php endif; ?>
      </td>
      <td><span style="color:<?= $p['stock']>0?'var(--a-accent)':'var(--a-danger)' ?>;font-weight:600"><?= $p['stock'] ?></span></td>
      <td><span class="badge-a <?= $badgeEstado ?>"><?= ucfirst(str_replace('_',' ',$p['estado'])) ?></span></td>
      <td>
        <form method="POST" style="display:inline">
          <?= campoCSRF() ?>
          <input type="hidden" name="_action" value="toggle_destacado">
          <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
          <button type="submit" class="action-btn" title="<?= $p['destacado']?'Quitar destacado':'Marcar destacado' ?>" style="color:<?= $p['destacado']?'var(--a-warning)':'' ?>">
            <i class="bi bi-star<?= $p['destacado']?'-fill':'' ?>"></i>
          </button>
        </form>
      </td>
      <td>
        <div class="table-actions">
          <a href="/pages/detalle.php?slug=<?= e($p['slug']) ?>" target="_blank" class="action-btn" title="Ver en tienda"><i class="bi bi-eye"></i></a>
          <button class="action-btn success" title="Editar" onclick='editProducto(<?= json_encode($p) ?>)'><i class="bi bi-pencil-fill"></i></button>
          <form method="POST" style="display:inline">
            <?= campoCSRF() ?>
            <input type="hidden" name="_action" value="eliminar">
            <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
            <button type="submit" class="action-btn danger" title="Eliminar" onclick="return confirm('¿Eliminar este producto?')"><i class="bi bi-trash3-fill"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($productos)): ?>
    <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--a-text-muted)"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:0.75rem"></i>No se encontraron productos</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <?php if ($totalPags > 1): ?>
  <div style="padding:1rem 1.25rem;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--a-border)">
    <span style="font-size:0.82rem;color:var(--a-text-muted)">Página <?= $pagina ?> de <?= $totalPags ?></span>
    <div class="pagination-iphix">
      <?php if ($pagina>1): ?><a href="?<?= http_build_query(array_merge($_GET,['p'=>$pagina-1])) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
      <?php for($pp=max(1,$pagina-2);$pp<=min($totalPags,$pagina+2);$pp++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['p'=>$pp])) ?>" class="page-btn <?= $pp===$pagina?'active':'' ?>"><?= $pp ?></a>
      <?php endfor; ?>
      <?php if ($pagina<$totalPags): ?><a href="?<?= http_build_query(array_merge($_GET,['p'=>$pagina+1])) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL CREAR/EDITAR -->
<div class="modal-overlay" id="modalCrear">
  <div class="modal-box" style="max-width:680px">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Nuevo producto</div>
      <button class="modal-close" onclick="closeModal('modalCrear')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= campoCSRF() ?>
      <input type="hidden" name="_action" id="formAction" value="crear">
      <input type="hidden" name="prod_id" id="formProdId" value="">
      <div class="row g-3">
        <div class="col-12">
          <div class="admin-form-group">
            <label class="admin-form-label">Nombre *</label>
            <input type="text" name="nombre" id="fNombre" class="admin-form-input" required placeholder="Ej: iPhone 13 Pro 256GB Grafito">
          </div>
        </div>
        <div class="col-md-6">
          <div class="admin-form-group">
            <label class="admin-form-label">Categoría *</label>
            <select name="categoria_id" id="fCat" class="admin-form-input" required>
              <option value="">Seleccionar...</option>
              <?php foreach ($categoriaFlat as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nombre_full']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="admin-form-group">
            <label class="admin-form-label">Marca</label>
            <input type="text" name="marca" id="fMarca" class="admin-form-input" placeholder="Apple, Samsung...">
          </div>
        </div>
        <div class="col-md-3">
          <div class="admin-form-group">
            <label class="admin-form-label">Modelo</label>
            <input type="text" name="modelo" id="fModelo" class="admin-form-input" placeholder="iPhone 13 Pro">
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-form-group">
            <label class="admin-form-label">Precio venta (€) *</label>
            <input type="number" name="precio_venta" id="fPventa" class="admin-form-input" step="0.01" min="0" required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-form-group">
            <label class="admin-form-label">Precio oferta (€)</label>
            <input type="number" name="precio_oferta" id="fPoferta" class="admin-form-input" step="0.01" min="0" placeholder="Opcional">
          </div>
        </div>
        <div class="col-md-2">
          <div class="admin-form-group">
            <label class="admin-form-label">Stock</label>
            <input type="number" name="stock" id="fStock" class="admin-form-input" min="0" value="1">
          </div>
        </div>
        <div class="col-md-2">
          <div class="admin-form-group">
            <label class="admin-form-label">Estado</label>
            <select name="estado" id="fEstado" class="admin-form-input">
              <option value="disponible">Disponible</option>
              <option value="en_reparacion">En reparación</option>
              <option value="reservado">Reservado</option>
              <option value="vendido">Vendido</option>
            </select>
          </div>
        </div>
        <div class="col-12">
          <div class="admin-form-group">
            <label class="admin-form-label">Descripción</label>
            <textarea name="descripcion" id="fDesc" class="admin-form-input" rows="3" placeholder="Descripción del producto..."></textarea>
          </div>
        </div>
        <div class="col-12">
          <div class="admin-form-group">
            <label class="admin-form-label">Especificaciones (una por línea: Clave: Valor)</label>
            <textarea name="especificaciones" id="fSpecs" class="admin-form-input" rows="4" placeholder="Pantalla: 6.1&quot; OLED&#10;Procesador: A15 Bionic&#10;RAM: 6GB"></textarea>
          </div>
        </div>
        <div class="col-12">
          <div class="admin-form-group">
            <label class="admin-form-label">Imagen principal</label>
            <input type="file" name="imagen" class="admin-form-input" accept="image/jpeg,image/png,image/webp">
          </div>
        </div>
        <div class="col-12">
          <div style="display:flex;align-items:center;gap:0.5rem">
            <input type="checkbox" name="destacado" id="fDestacado" style="accent-color:var(--a-primary);width:16px;height:16px">
            <label for="fDestacado" class="admin-form-label" style="margin:0">Marcar como producto destacado</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('modalCrear')">Cancelar</button>
        <button type="submit" class="btn-admin btn-admin-primary"><i class="bi bi-floppy-fill"></i> Guardar producto</button>
      </div>
    </form>
  </div>
</div>

<script>
function editProducto(p) {
  document.getElementById('modalTitle').textContent = 'Editar producto';
  document.getElementById('formAction').value = 'editar';
  document.getElementById('formProdId').value = p.id;
  document.getElementById('fNombre').value  = p.nombre;
  document.getElementById('fCat').value     = p.categoria_id;
  document.getElementById('fMarca').value   = p.marca || '';
  document.getElementById('fModelo').value  = p.modelo || '';
  document.getElementById('fPventa').value  = p.precio_venta;
  document.getElementById('fPoferta').value = p.precio_oferta || '';
  document.getElementById('fStock').value   = p.stock;
  document.getElementById('fEstado').value  = p.estado;
  document.getElementById('fDesc').value    = p.descripcion || '';
  document.getElementById('fSpecs').value   = p.especificaciones || '';
  document.getElementById('fDestacado').checked = p.destacado == 1;
  openModal('modalCrear');
}

function openModal(id) {
  document.getElementById(id).classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('active');
  document.body.style.overflow = '';
  document.getElementById('formAction').value = 'crear';
  document.getElementById('formProdId').value = '';
  document.getElementById('modalTitle').textContent = 'Nuevo producto';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) closeModal(o.id); });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
