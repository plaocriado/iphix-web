<?php
$pageTitle   = 'Pedidos';
$currentPage = 'pedidos';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $pedidoId  = (int)($_POST['pedido_id'] ?? 0);
        $nuevoEst  = $_POST['nuevo_estado'] ?? '';
        $estadosValidos = ['pendiente','pagado','procesando','enviado','entregado','cancelado','reembolsado'];
        if ($pedidoId && in_array($nuevoEst, $estadosValidos)) {
            dbExecute('UPDATE pedidos SET estado=? WHERE id=?', [$nuevoEst, $pedidoId]);
            $success = "Estado del pedido actualizado a: $nuevoEst";
        }
    }
}

$estado  = $_GET['estado'] ?? '';
$search  = trim($_GET['q'] ?? '');
$pagina  = max(1,(int)($_GET['p'] ?? 1));
$porPag  = 20;
$offset  = ($pagina-1)*$porPag;

$where = ['1=1'];
$params = [];
if ($estado) { $where[] = 'p.estado = ?'; $params[] = $estado; }
if ($search) { $where[] = '(p.codigo LIKE ? OR u.email LIKE ? OR u.nombre LIKE ? OR u.apellidos LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%","%$search%"]); }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = dbQueryOne("SELECT COUNT(*) AS t FROM pedidos p JOIN usuarios u ON p.usuario_id=u.id $whereSQL", $params)['t'] ?? 0;
$totalPags = (int)ceil($total/$porPag);

$sql = "SELECT 
    p.id,
    p.codigo,
    p.usuario_id,
    p.estado,
    p.total,
    p.created_at,
    u.nombre,
    u.apellidos,
    u.email
FROM pedidos p
JOIN usuarios u ON p.usuario_id = u.id
$whereSQL
ORDER BY p.created_at DESC
LIMIT ? OFFSET ?";

$stmt = db()->prepare($sql);
$index = 1;
foreach ($params as $v) $stmt->bindValue($index++, $v);
$stmt->bindValue($index++, (int)$porPag, PDO::PARAM_INT);
$stmt->bindValue($index++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

$estados = ['pendiente','pagado','procesando','enviado','entregado','cancelado','reembolsado'];
?>

<?php foreach ($errors as $err): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<!-- Filtros por estado -->
<div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem">
  <a href="/admin/pedidos.php" class="btn-admin btn-admin-sm <?= !$estado?'btn-admin-primary':'btn-admin-outline' ?>">Todos (<?= dbQueryOne('SELECT COUNT(*) AS t FROM pedidos')['t'] ?>)</a>
  <?php foreach ($estados as $est):
    $count = dbQueryOne("SELECT COUNT(*) AS t FROM pedidos WHERE estado=?",[$est])['t'];
    $badgeCls = match($est){ 'pagado','entregado'=>'btn-admin-success','pendiente'=>'', 'cancelado','reembolsado'=>'btn-admin-danger', default=>'' };
  ?>
  <a href="?estado=<?= $est ?>" class="btn-admin btn-admin-sm <?= $estado===$est?'btn-admin-primary':'btn-admin-outline' ?>"><?= ucfirst($est) ?> (<?= $count ?>)</a>
  <?php endforeach; ?>
</div>

<!-- Búsqueda -->
<div class="admin-table-wrap" style="margin-bottom:1rem">
  <div style="padding:1rem 1.25rem">
    <form method="GET" style="display:flex;gap:0.75rem">
      <?php if ($estado): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
      <div class="topbar-search" style="flex:1">
        <i class="bi bi-search topbar-search-icon"></i>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por código, cliente, email...">
      </div>
      <button type="submit" class="btn-admin btn-admin-outline"><i class="bi bi-search"></i> Buscar</button>
    </form>
  </div>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
  <div class="admin-table-header">
    <div class="admin-table-title">Pedidos (<?= $total ?>)</div>
  </div>
  <table class="admin-table">
    <thead><tr><th>Código</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Cambiar estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pedidos as $p):
      $badge = match($p['estado']){
        'pagado','procesando'=>'badge-blue','enviado'=>'badge-blue','entregado'=>'badge-green',
        'cancelado','reembolsado'=>'badge-red','pendiente'=>'badge-yellow', default=>'badge-gray'
      };
    ?>
    <tr>
      <td><strong><?= e($p['codigo']) ?></strong></td>
      <td>
        <div style="font-weight:600;color:var(--a-text)"><?= e($p['nombre'].' '.$p['apellidos']) ?></div>
        <div style="font-size:0.75rem"><?= e($p['email']) ?></div>
      </td>
      <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
      <td><strong><?= number_format($p['total'],2,',','.').' €' ?></strong></td>
      <td><span class="badge-a <?= $badge ?>"><?= ucfirst($p['estado']) ?></span></td>
      <td>
        <form method="POST" style="display:flex;gap:0.5rem;align-items:center">
          <?= campoCSRF() ?>
          <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
          <select name="nuevo_estado" class="admin-form-input" style="padding:0.3rem 0.5rem;font-size:0.82rem;width:auto">
            <?php foreach ($estados as $est): ?>
            <option value="<?= $est ?>" <?= $p['estado']===$est?'selected':'' ?>><?= ucfirst($est) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="actualizar_estado" class="btn-admin btn-admin-success btn-admin-sm" title="Aplicar"><i class="bi bi-check2"></i></button>
        </form>
      </td>
      <td>
        <button class="action-btn" onclick='verDetallePedido(<?= json_encode($p) ?>)' title="Ver detalle"><i class="bi bi-eye-fill"></i></button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($pedidos)): ?>
    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--a-text-muted)">No hay pedidos con estos filtros</td></tr>
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

<!-- Modal detalle pedido -->
<div class="modal-overlay" id="modalPedido">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Detalle del Pedido</div>
      <button class="modal-close" onclick="document.getElementById('modalPedido').classList.remove('active')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="modalPedidoContent" style="font-size:0.88rem;color:var(--a-text-muted)"></div>
  </div>
</div>

<script>
function verDetallePedido(p) {
  document.getElementById('modalPedidoContent').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem">
      <div><span style="color:var(--a-text-dim);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em">Código</span><div style="color:var(--a-primary);font-weight:700;font-size:1.1rem">${p.codigo}</div></div>
      <div><span style="color:var(--a-text-dim);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em">Total</span><div style="color:var(--a-text);font-weight:700;font-size:1.1rem">${parseFloat(p.total).toFixed(2).replace('.',',')} €</div></div>
      <div><span style="color:var(--a-text-dim);font-size:0.75rem;text-transform:uppercase">Cliente</span><div style="color:var(--a-text)">${p.nombre} ${p.apellidos}</div></div>
      <div><span style="color:var(--a-text-dim);font-size:0.75rem;text-transform:uppercase">Email</span><div style="color:var(--a-text)">${p.email}</div></div>
    </div>
    <div style="background:rgba(255,255,255,0.03);border-radius:8px;padding:1rem;margin-bottom:1rem">
      <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--a-text-dim);margin-bottom:0.5rem">Dirección de envío</div>
      <div>${p.nombre_destinatario}<br>${p.direccion_envio}<br>${p.codigo_postal} ${p.ciudad}, ${p.provincia}</div>
    </div>
    ${p.stripe_payment_intent ? `<div style="font-size:0.78rem;color:var(--a-text-dim)">Payment Intent: <code style="color:var(--a-primary)">${p.stripe_payment_intent}</code></div>` : ''}
    ${p.notas ? `<div style="margin-top:0.75rem"><strong>Notas:</strong> ${p.notas}</div>` : ''}
  `;
  document.getElementById('modalPedido').classList.add('active');
}
document.getElementById('modalPedido').addEventListener('click', e => {
  if (e.target === document.getElementById('modalPedido')) e.target.classList.remove('active');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
