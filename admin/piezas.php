<?php
$pageTitle   = 'Gestión de Piezas';
$currentPage = 'piezas';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'crear' || $action === 'editar') {
            $nombre      = trim(strip_tags($_POST['nombre']      ?? ''));
            $referencia  = trim(strip_tags($_POST['referencia']  ?? '')) ?: null;
            $descripcion = trim(strip_tags($_POST['descripcion'] ?? '')) ?: null;
            $categoria   = trim(strip_tags($_POST['categoria']   ?? '')) ?: null;
            $stock       = max(0,(int)($_POST['stock'] ?? 0));
            $stockMin    = max(0,(int)($_POST['stock_minimo'] ?? 5));
            $precio      = !empty($_POST['precio_unitario']) ? (float)str_replace(',','.',($_POST['precio_unitario'])) : null;
            $proveedor   = trim(strip_tags($_POST['proveedor']   ?? '')) ?: null;

            if (!$nombre) { $errors[] = 'El nombre es obligatorio.'; }
            else {
                if ($action === 'crear') {
                    dbExecute('INSERT INTO piezas (nombre,referencia,descripcion,categoria,stock,stock_minimo,precio_unitario,proveedor) VALUES(?,?,?,?,?,?,?,?)',
                        [$nombre,$referencia,$descripcion,$categoria,$stock,$stockMin,$precio,$proveedor]);
                    
                    if ($stock > 0) {
                        $pid = dbLastInsertId();
                        dbExecute('INSERT INTO movimientos_piezas (pieza_id,tipo,cantidad,motivo,usuario_id) VALUES(?,?,?,?,?)',
                            [$pid,'entrada',$stock,'Stock inicial',usuarioActual()['id']]);
                    }
                    $success = 'Pieza creada correctamente.';
                } else {
                    $piezaId = (int)($_POST['pieza_id'] ?? 0);
                    $piezaActual = dbQueryOne('SELECT stock FROM piezas WHERE id=?',[$piezaId]);
                    dbExecute('UPDATE piezas SET nombre=?,referencia=?,descripcion=?,categoria=?,stock=?,stock_minimo=?,precio_unitario=?,proveedor=? WHERE id=?',
                        [$nombre,$referencia,$descripcion,$categoria,$stock,$stockMin,$precio,$proveedor,$piezaId]);
                    
                    if ($piezaActual && $stock != $piezaActual['stock']) {
                        $diff = $stock - $piezaActual['stock'];
                        dbExecute('INSERT INTO movimientos_piezas (pieza_id,tipo,cantidad,motivo,usuario_id) VALUES(?,?,?,?,?)',
                            [$piezaId,$diff>0?'entrada':'salida',abs($diff),'Ajuste manual de stock',usuarioActual()['id']]);
                    }
                    $success = 'Pieza actualizada.';
                }
            }
        } elseif ($action === 'movimiento') {
            $piezaId  = (int)($_POST['pieza_id'] ?? 0);
            $tipo     = $_POST['tipo_mov'] ?? 'entrada';
            $cantidad = max(1,(int)($_POST['cantidad_mov'] ?? 0));
            $motivo   = trim(strip_tags($_POST['motivo'] ?? ''));
            if ($piezaId && $cantidad) {
                if ($tipo === 'entrada') {
                    dbExecute('UPDATE piezas SET stock = stock + ? WHERE id=?',[$cantidad,$piezaId]);
                } else {
                    $actual = dbQueryOne('SELECT stock FROM piezas WHERE id=?',[$piezaId]);
                    if ($actual && $actual['stock'] >= $cantidad) {
                        dbExecute('UPDATE piezas SET stock = stock - ? WHERE id=?',[$cantidad,$piezaId]);
                    } else { $errors[] = 'Stock insuficiente para registrar la salida.'; }
                }
                if (empty($errors)) {
                    dbExecute('INSERT INTO movimientos_piezas (pieza_id,tipo,cantidad,motivo,usuario_id) VALUES(?,?,?,?,?)',
                        [$piezaId,$tipo,$cantidad,$motivo,usuarioActual()['id']]);
                    $success = 'Movimiento registrado correctamente.';
                }
            }
        } elseif ($action === 'eliminar') {
            $piezaId = (int)($_POST['pieza_id'] ?? 0);
            dbExecute('UPDATE piezas SET activa=0 WHERE id=?',[$piezaId]);
            $success = 'Pieza eliminada.';
        }
    }
}

$search  = trim($_GET['q'] ?? '');
$catFil  = trim($_GET['cat'] ?? '');
$pagina  = max(1,(int)($_GET['p'] ?? 1));
$porPag  = 20;
$offset  = ($pagina-1)*$porPag;

$where  = ['activa=1'];
$params = [];
if ($search) { $where[] = '(nombre LIKE ? OR referencia LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFil)  { $where[] = 'categoria=?'; $params[] = $catFil; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$total     = dbQueryOne("SELECT COUNT(*) AS t FROM piezas $whereSQL",$params)['t'] ?? 0;
$totalPags = (int)ceil($total/$porPag);

$stmt = db()->prepare("SELECT * FROM piezas $whereSQL ORDER BY (stock<=stock_minimo) DESC, nombre ASC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k+1,$v);
$stmt->bindValue(':lim',$porPag,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$piezas = $stmt->fetchAll();

$categoriasPiezas = dbQuery("SELECT DISTINCT categoria FROM piezas WHERE activa=1 AND categoria IS NOT NULL ORDER BY categoria");
$stockBajoCount   = dbQueryOne('SELECT COUNT(*) AS t FROM v_piezas_stock_bajo')['t'] ?? 0;
?>

<?php foreach ($errors as $err): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<?php if ($stockBajoCount > 0): ?>
<div class="alert-iphix alert-warning mb-3">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <span><strong><?= $stockBajoCount ?> pieza<?= $stockBajoCount>1?'s':'' ?></strong> con stock bajo o agotado. Revisa el inventario.</span>
</div>
<?php endif; ?>

<!-- Header acciones -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <p style="color:var(--a-text-muted);margin:0"><?= $total ?> piezas en inventario</p>
  <div style="display:flex;gap:0.5rem">
    <button class="btn-admin btn-admin-outline" onclick="openModal('modalMovimiento')"><i class="bi bi-arrow-left-right"></i> Movimiento</button>
    <button class="btn-admin btn-admin-primary" onclick="openModal('modalPieza')"><i class="bi bi-plus-lg"></i> Nueva pieza</button>
  </div>
</div>

<!-- Filtros -->
<div class="admin-table-wrap" style="margin-bottom:1rem">
  <div style="padding:1rem 1.25rem">
    <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
      <div class="topbar-search" style="flex:1;min-width:180px">
        <i class="bi bi-search topbar-search-icon"></i>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar nombre, referencia...">
      </div>
      <select name="cat" class="admin-form-input" style="width:auto;padding:0.4rem 0.75rem">
        <option value="">Todas las categorías</option>
        <?php foreach ($categoriasPiezas as $c): ?>
        <option value="<?= e($c['categoria']) ?>" <?= $catFil===$c['categoria']?'selected':'' ?>><?= e($c['categoria']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-admin btn-admin-outline"><i class="bi bi-funnel"></i></button>
      <a href="/admin/piezas.php" class="btn-admin btn-admin-outline"><i class="bi bi-x"></i></a>
    </form>
  </div>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr>
      <th>Nombre / Ref.</th><th>Categoría</th><th>Stock</th><th>Mín.</th><th>Precio unit.</th><th>Proveedor</th><th>Acciones</th>
    </tr></thead>
    <tbody>
    <?php foreach ($piezas as $p): ?>
    <?php $stockBajo = $p['stock'] <= $p['stock_minimo']; ?>
    <tr style="<?= $stockBajo ? 'background:rgba(255,190,11,0.03)':'' ?>">
      <td>
        <strong style="color:var(--a-text)"><?= e($p['nombre']) ?></strong>
        <?php if ($p['referencia']): ?><div style="font-size:0.75rem;color:var(--a-primary)"><?= e($p['referencia']) ?></div><?php endif; ?>
      </td>
      <td><?php if ($p['categoria']): ?><span class="badge-a badge-gray"><?= e($p['categoria']) ?></span><?php endif; ?></td>
      <td>
        <span style="font-size:1.05rem;font-weight:700;color:<?= $stockBajo?'var(--a-warning)':'var(--a-accent)' ?>">
          <?= $stockBajo?'<i class="bi bi-exclamation-triangle-fill"></i> ':'' ?><?= $p['stock'] ?>
        </span>
      </td>
      <td style="color:var(--a-text-muted)"><?= $p['stock_minimo'] ?></td>
      <td><?= $p['precio_unitario'] ? number_format($p['precio_unitario'],2,',','.').' €' : '—' ?></td>
      <td style="font-size:0.85rem"><?= e($p['proveedor'] ?? '—') ?></td>
      <td>
        <div class="table-actions">
          <button class="action-btn success" title="Editar" onclick='editPieza(<?= json_encode($p) ?>)'><i class="bi bi-pencil-fill"></i></button>
          <button class="action-btn" title="Registrar movimiento" onclick='preseleccionarMovimiento(<?= $p['id'] ?>, "<?= e($p['nombre']) ?>")'>
            <i class="bi bi-arrow-left-right"></i>
          </button>
          <form method="POST" style="display:inline">
            <?= campoCSRF() ?>
            <input type="hidden" name="_action" value="eliminar">
            <input type="hidden" name="pieza_id" value="<?= $p['id'] ?>">
            <button type="submit" class="action-btn danger" title="Eliminar" onclick="return confirm('¿Eliminar esta pieza?')"><i class="bi bi-trash3-fill"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($piezas)): ?>
    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--a-text-muted)">No hay piezas registradas</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  <?php if ($totalPags > 1): ?>
  <div style="padding:1rem 1.25rem;display:flex;justify-content:center;border-top:1px solid var(--a-border)">
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

<!-- MODAL CREAR/EDITAR PIEZA -->
<div class="modal-overlay" id="modalPieza">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title" id="modalPiezaTitle">Nueva pieza</div>
      <button class="modal-close" onclick="closeModal('modalPieza')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <?= campoCSRF() ?>
      <input type="hidden" name="_action" id="piezaAction" value="crear">
      <input type="hidden" name="pieza_id" id="piezaId" value="">
      <div class="row g-3">
        <div class="col-12"><div class="admin-form-group">
          <label class="admin-form-label">Nombre *</label>
          <input type="text" name="nombre" id="pNombre" class="admin-form-input" required placeholder="Ej: Pantalla iPhone 13 OLED">
        </div></div>
        <div class="col-md-6"><div class="admin-form-group">
          <label class="admin-form-label">Referencia</label>
          <input type="text" name="referencia" id="pRef" class="admin-form-input" placeholder="Ej: PNT-IP13-OLED">
        </div></div>
        <div class="col-md-6"><div class="admin-form-group">
          <label class="admin-form-label">Categoría</label>
          <select name="categoria" id="pCat" class="admin-form-input">
            <option value="">Sin categoría</option>
            <?php foreach (['Pantallas','Baterías','Conectores','Teclados','Cámaras','Carcasas','Otros'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div></div>
        <div class="col-md-4"><div class="admin-form-group">
          <label class="admin-form-label">Stock actual</label>
          <input type="number" name="stock" id="pStock" class="admin-form-input" min="0" value="0">
        </div></div>
        <div class="col-md-4"><div class="admin-form-group">
          <label class="admin-form-label">Stock mínimo</label>
          <input type="number" name="stock_minimo" id="pStockMin" class="admin-form-input" min="0" value="5">
        </div></div>
        <div class="col-md-4"><div class="admin-form-group">
          <label class="admin-form-label">Precio unitario (€)</label>
          <input type="number" name="precio_unitario" id="pPrecio" class="admin-form-input" step="0.01" min="0" placeholder="0.00">
        </div></div>
        <div class="col-12"><div class="admin-form-group">
          <label class="admin-form-label">Proveedor</label>
          <input type="text" name="proveedor" id="pProveedor" class="admin-form-input" placeholder="Nombre del proveedor">
        </div></div>
        <div class="col-12"><div class="admin-form-group">
          <label class="admin-form-label">Descripción</label>
          <textarea name="descripcion" id="pDesc" class="admin-form-input" rows="2"></textarea>
        </div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('modalPieza')">Cancelar</button>
        <button type="submit" class="btn-admin btn-admin-primary"><i class="bi bi-floppy-fill"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MOVIMIENTO -->
<div class="modal-overlay" id="modalMovimiento">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Registrar movimiento de stock</div>
      <button class="modal-close" onclick="closeModal('modalMovimiento')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <?= campoCSRF() ?>
      <input type="hidden" name="_action" value="movimiento">
      <div class="admin-form-group">
        <label class="admin-form-label">Pieza *</label>
        <select name="pieza_id" id="movPiezaId" class="admin-form-input" required>
          <option value="">Seleccionar pieza...</option>
          <?php foreach (dbQuery('SELECT id,nombre,stock FROM piezas WHERE activa=1 ORDER BY nombre') as $p): ?>
          <option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?> (stock: <?= $p['stock'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Tipo de movimiento</label>
        <div style="display:flex;gap:0.5rem">
          <label style="flex:1;display:flex;align-items:center;gap:0.5rem;padding:0.6rem 0.75rem;border-radius:8px;border:1px solid var(--a-border);cursor:pointer;font-size:0.9rem">
            <input type="radio" name="tipo_mov" value="entrada" checked style="accent-color:var(--a-accent)">
            <i class="bi bi-arrow-down-circle" style="color:var(--a-accent)"></i> Entrada
          </label>
          <label style="flex:1;display:flex;align-items:center;gap:0.5rem;padding:0.6rem 0.75rem;border-radius:8px;border:1px solid var(--a-border);cursor:pointer;font-size:0.9rem">
            <input type="radio" name="tipo_mov" value="salida" style="accent-color:var(--a-danger)">
            <i class="bi bi-arrow-up-circle" style="color:var(--a-danger)"></i> Salida
          </label>
        </div>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Cantidad *</label>
        <input type="number" name="cantidad_mov" class="admin-form-input" min="1" value="1" required>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Motivo</label>
        <input type="text" name="motivo" class="admin-form-input" placeholder="Ej: Compra a proveedor, Reparación cliente...">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('modalMovimiento')">Cancelar</button>
        <button type="submit" class="btn-admin btn-admin-primary"><i class="bi bi-check2"></i> Registrar movimiento</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); }));

function editPieza(p) {
  document.getElementById('modalPiezaTitle').textContent = 'Editar pieza';
  document.getElementById('piezaAction').value = 'editar';
  document.getElementById('piezaId').value   = p.id;
  document.getElementById('pNombre').value   = p.nombre;
  document.getElementById('pRef').value      = p.referencia || '';
  document.getElementById('pCat').value      = p.categoria || '';
  document.getElementById('pStock').value    = p.stock;
  document.getElementById('pStockMin').value = p.stock_minimo;
  document.getElementById('pPrecio').value   = p.precio_unitario || '';
  document.getElementById('pProveedor').value= p.proveedor || '';
  document.getElementById('pDesc').value     = p.descripcion || '';
  openModal('modalPieza');
}
function preseleccionarMovimiento(id, nombre) {
  document.getElementById('movPiezaId').value = id;
  openModal('modalMovimiento');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
