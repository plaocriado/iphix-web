<?php
$pageTitle   = 'Gestión de Usuarios';
$currentPage = 'usuarios';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $action   = $_POST['_action'] ?? '';
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);

        if ($action === 'toggle_activo') {
            if ($usuarioId !== usuarioActual()['id']) {
                dbExecute('UPDATE usuarios SET activo = NOT activo WHERE id=?', [$usuarioId]);
                $success = 'Estado del usuario actualizado.';
            } else { $errors[] = 'No puedes desactivarte a ti mismo.'; }
        } elseif ($action === 'cambiar_rol') {
            $rol = $_POST['nuevo_rol'] ?? 'cliente';
            if (in_array($rol,['admin','cliente']) && $usuarioId !== usuarioActual()['id']) {
                dbExecute('UPDATE usuarios SET rol=? WHERE id=?', [$rol,$usuarioId]);
                $success = 'Rol actualizado.';
            } else { $errors[] = 'Operación no permitida.'; }
        } elseif ($action === 'crear_admin') {
            $resultado = registrarUsuario($_POST);
            if ($resultado['exito']) {
                dbExecute('UPDATE usuarios SET rol="admin" WHERE id=?',[$resultado['id']]);
                $success = 'Administrador creado correctamente.';
            } else { $errors[] = $resultado['error']; }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$rol    = $_GET['rol'] ?? '';
$pagina = max(1,(int)($_GET['p'] ?? 1));
$porPag = 20;
$offset = ($pagina-1)*$porPag;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(nombre LIKE ? OR apellidos LIKE ? OR email LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($rol)    { $where[] = 'rol=?'; $params[] = $rol; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$total     = dbQueryOne("SELECT COUNT(*) AS t FROM usuarios $whereSQL",$params)['t'] ?? 0;
$totalPags = (int)ceil($total/$porPag);

$stmt = db()->prepare("SELECT u.*, (SELECT COUNT(*) FROM pedidos WHERE usuario_id=u.id) AS total_pedidos FROM usuarios u $whereSQL ORDER BY u.created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k+1,$v);
$stmt->bindValue(':lim',$porPag,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>

<?php foreach ($errors as $err): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <p style="color:var(--a-text-muted);margin:0"><?= $total ?> usuarios registrados</p>
  <button class="btn-admin btn-admin-primary" onclick="openModal('modalCrearAdmin')"><i class="bi bi-person-plus-fill"></i> Crear administrador</button>
</div>

<!-- Filtros -->
<div class="admin-table-wrap" style="margin-bottom:1rem">
  <div style="padding:1rem 1.25rem">
    <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
      <div class="topbar-search" style="flex:1;min-width:200px">
        <i class="bi bi-search topbar-search-icon"></i>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre, email...">
      </div>
      <select name="rol" class="admin-form-input" style="width:auto;padding:0.4rem 0.75rem" onchange="this.form.submit()">
        <option value="">Todos los roles</option>
        <option value="cliente" <?= $rol==='cliente'?'selected':'' ?>>Clientes</option>
        <option value="admin"   <?= $rol==='admin'  ?'selected':'' ?>>Administradores</option>
      </select>
      <button type="submit" class="btn-admin btn-admin-outline"><i class="bi bi-funnel"></i></button>
      <a href="/admin/usuarios.php" class="btn-admin btn-admin-outline"><i class="bi bi-x"></i></a>
    </form>
  </div>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Usuario</th><th>Email</th><th>Pedidos</th><th>Rol</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u):
      $initials = strtoupper(substr($u['nombre'],0,1).substr($u['apellidos'],0,1));
      $esMismo  = $u['id'] === usuarioActual()['id'];
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:0.75rem">
          <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--a-primary),var(--a-accent));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem;color:#000;flex-shrink:0;font-family:var(--a-font-d,'Syne',sans-serif)"><?= $initials ?></div>
          <div>
            <strong style="color:var(--a-text)"><?= e($u['nombre'].' '.$u['apellidos']) ?></strong>
            <?php if ($esMismo): ?><span class="badge-a badge-blue" style="font-size:0.65rem;margin-left:0.3rem">Tú</span><?php endif; ?>
            <?php if ($u['telefono']): ?><div style="font-size:0.75rem"><?= e($u['telefono']) ?></div><?php endif; ?>
          </div>
        </div>
      </td>
      <td style="font-size:0.88rem"><?= e($u['email']) ?></td>
      <td>
        <a href="/admin/pedidos.php?q=<?= urlencode($u['email']) ?>" style="color:var(--a-primary);font-weight:600"><?= $u['total_pedidos'] ?></a>
      </td>
      <td>
        <?php if (!$esMismo): ?>
        <form method="POST" style="display:flex;gap:0.4rem;align-items:center">
          <?= campoCSRF() ?>
          <input type="hidden" name="_action" value="cambiar_rol">
          <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
          <select name="nuevo_rol" class="admin-form-input" style="padding:0.25rem 0.5rem;font-size:0.82rem;width:auto" onchange="this.form.submit()">
            <option value="cliente" <?= $u['rol']==='cliente'?'selected':'' ?>>Cliente</option>
            <option value="admin"   <?= $u['rol']==='admin'  ?'selected':'' ?>>Admin</option>
          </select>
        </form>
        <?php else: ?>
        <span class="badge-a badge-blue">Admin</span>
        <?php endif; ?>
      </td>
      <td>
        <span class="badge-a <?= $u['activo']?'badge-green':'badge-red' ?>"><?= $u['activo']?'Activo':'Inactivo' ?></span>
      </td>
      <td style="font-size:0.82rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
      <td>
        <?php if (!$esMismo): ?>
        <form method="POST" style="display:inline">
          <?= campoCSRF() ?>
          <input type="hidden" name="_action" value="toggle_activo">
          <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
          <button type="submit" class="action-btn <?= $u['activo']?'danger':'' ?>" title="<?= $u['activo']?'Desactivar':'Activar' ?>">
            <i class="bi bi-person-<?= $u['activo']?'dash':'check' ?>-fill"></i>
          </button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($usuarios)): ?>
    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--a-text-muted)">No se encontraron usuarios</td></tr>
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

<!-- Modal crear admin -->
<div class="modal-overlay" id="modalCrearAdmin">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Crear cuenta de administrador</div>
      <button class="modal-close" onclick="closeModal('modalCrearAdmin')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <?= campoCSRF() ?>
      <input type="hidden" name="_action" value="crear_admin">
      <div class="row g-3">
        <div class="col-6"><div class="admin-form-group">
          <label class="admin-form-label">Nombre *</label>
          <input type="text" name="nombre" class="admin-form-input" required>
        </div></div>
        <div class="col-6"><div class="admin-form-group">
          <label class="admin-form-label">Apellidos *</label>
          <input type="text" name="apellidos" class="admin-form-input" required>
        </div></div>
        <div class="col-12"><div class="admin-form-group">
          <label class="admin-form-label">Email *</label>
          <input type="email" name="email" class="admin-form-input" required>
        </div></div>
        <div class="col-6"><div class="admin-form-group">
          <label class="admin-form-label">Contraseña *</label>
          <input type="password" name="password" class="admin-form-input" minlength="8" required>
        </div></div>
        <div class="col-6"><div class="admin-form-group">
          <label class="admin-form-label">Confirmar contraseña *</label>
          <input type="password" name="confirm_password" class="admin-form-input" required>
        </div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin btn-admin-outline" onclick="closeModal('modalCrearAdmin')">Cancelar</button>
        <button type="submit" class="btn-admin btn-admin-primary"><i class="bi bi-person-plus-fill"></i> Crear admin</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); }));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
