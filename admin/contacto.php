<?php
$pageTitle   = 'Mensajes de Contacto';
$currentPage = 'contacto';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $action = $_POST['_action'] ?? '';
        $msgId  = (int)($_POST['msg_id'] ?? 0);
        if ($action === 'marcar_leido') {
            dbExecute('UPDATE contacto SET leido=1 WHERE id=?', [$msgId]);
            $success = 'Mensaje marcado como leído.';
        } elseif ($action === 'marcar_respondido') {
            dbExecute('UPDATE contacto SET leido=1, respondido=1 WHERE id=?', [$msgId]);
            $success = 'Mensaje marcado como respondido.';
        } elseif ($action === 'eliminar') {
            dbExecute('DELETE FROM contacto WHERE id=?', [$msgId]);
            $success = 'Mensaje eliminado.';
        }
    }
}

$filtro = $_GET['f'] ?? 'todos';
$pagina = max(1,(int)($_GET['p'] ?? 1));
$porPag = 20;
$offset = ($pagina-1)*$porPag;

$where = match($filtro) {
    'nuevos'     => 'WHERE leido=0',
    'respondidos'=> 'WHERE respondido=1',
    default      => ''
};

$total     = dbQueryOne("SELECT COUNT(*) AS t FROM contacto $where")['t'] ?? 0;
$totalPags = (int)ceil($total/$porPag);
$mensajes  = dbQuery("SELECT * FROM contacto $where ORDER BY created_at DESC LIMIT $porPag OFFSET $offset");

$contNuevos = dbQueryOne('SELECT COUNT(*) AS t FROM contacto WHERE leido=0')['t'] ?? 0;
?>

<?php foreach ($errors as $err): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<!-- Tabs filtro -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
  <?php foreach ([['todos','Todos'],['nuevos','Sin leer'],['respondidos','Respondidos']] as [$val,$label]): ?>
  <a href="?f=<?= $val ?>" class="btn-admin btn-admin-sm <?= $filtro===$val?'btn-admin-primary':'btn-admin-outline' ?>">
    <?= $label ?>
    <?php if ($val==='nuevos' && $contNuevos>0): ?><span style="background:var(--a-danger);color:#fff;border-radius:999px;font-size:0.65rem;padding:0.1rem 0.35rem;margin-left:0.3rem"><?= $contNuevos ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>De</th><th>Asunto</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($mensajes as $m): ?>
    <tr style="<?= !$m['leido'] ? 'background:rgba(0,212,255,0.03)':'' ?>">
      <td>
        <strong style="color:<?= !$m['leido'] ? 'var(--a-text)':'var(--a-text-muted)' ?>"><?= e($m['nombre']) ?></strong>
        <div style="font-size:0.75rem"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
      </td>
      <td>
        <div style="font-weight:<?= !$m['leido']?'600':'400' ?>;color:<?= !$m['leido']?'var(--a-text)':'var(--a-text-muted)' ?>"><?= e($m['asunto']) ?></div>
        <div style="font-size:0.78rem;color:var(--a-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px"><?= e(substr($m['mensaje'],0,80)) ?>...</div>
      </td>
      <td style="font-size:0.82rem;white-space:nowrap"><?= date('d/m/Y H:i',strtotime($m['created_at'])) ?></td>
      <td>
        <?php if (!$m['leido']): ?>
        <span class="badge-a badge-yellow"><i class="bi bi-dot"></i> Nuevo</span>
        <?php elseif ($m['respondido']): ?>
        <span class="badge-a badge-green"><i class="bi bi-check2-all"></i> Respondido</span>
        <?php else: ?>
        <span class="badge-a badge-gray">Leído</span>
        <?php endif; ?>
      </td>
      <td>
        <div class="table-actions">
          <button class="action-btn" title="Ver mensaje" onclick='verMensaje(<?= json_encode($m) ?>)'><i class="bi bi-eye-fill"></i></button>
          <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= urlencode($m['asunto']) ?>" class="action-btn success" title="Responder por email" onclick="marcarRespondido(<?= $m['id'] ?>)"><i class="bi bi-reply-fill"></i></a>
          <?php if (!$m['leido']): ?>
          <form method="POST" style="display:inline">
            <?= campoCSRF() ?>
            <input type="hidden" name="_action" value="marcar_leido">
            <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
            <button type="submit" class="action-btn" title="Marcar leído"><i class="bi bi-check2"></i></button>
          </form>
          <?php endif; ?>
          <form method="POST" style="display:inline">
            <?= campoCSRF() ?>
            <input type="hidden" name="_action" value="eliminar">
            <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
            <button type="submit" class="action-btn danger" title="Eliminar" onclick="return confirm('¿Eliminar este mensaje?')"><i class="bi bi-trash3-fill"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($mensajes)): ?>
    <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--a-text-muted)">
      <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:0.75rem"></i>
      No hay mensajes<?= $filtro !== 'todos' ? ' con este filtro':''?>
    </td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal ver mensaje -->
<div class="modal-overlay" id="modalMensaje">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title" id="msgModalAsunto">Mensaje</div>
      <button class="modal-close" onclick="document.getElementById('modalMensaje').classList.remove('active')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="msgModalBody" style="font-size:0.9rem"></div>
    <div class="modal-footer">
      <button type="button" class="btn-admin btn-admin-outline" onclick="document.getElementById('modalMensaje').classList.remove('active')">Cerrar</button>
      <a id="msgReplyBtn" href="#" class="btn-admin btn-admin-primary"><i class="bi bi-reply-fill"></i> Responder por email</a>
    </div>
  </div>
</div>

<script>
function verMensaje(m) {
  document.getElementById('msgModalAsunto').textContent = m.asunto;
  document.getElementById('msgModalBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem;padding:1rem;background:rgba(255,255,255,0.03);border-radius:8px">
      <div><span style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--a-text-dim)">De</span><div style="color:var(--a-text);font-weight:600">${m.nombre}</div></div>
      <div><span style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--a-text-dim)">Email</span><div><a href="mailto:${m.email}" style="color:var(--a-primary)">${m.email}</a></div></div>
      <div><span style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--a-text-dim)">Fecha</span><div>${new Date(m.created_at).toLocaleString('es-ES')}</div></div>
    </div>
    <div style="background:rgba(0,212,255,0.04);border:1px solid rgba(0,212,255,0.12);border-radius:8px;padding:1.25rem;line-height:1.8;color:var(--a-text)">${m.mensaje.replace(/\n/g,'<br>')}</div>
  `;
  document.getElementById('msgReplyBtn').href = `mailto:${m.email}?subject=Re: ${encodeURIComponent(m.asunto)}`;
  document.getElementById('modalMensaje').classList.add('active');
  // Marcar leído via fetch
  if (!m.leido) {
    fetch('/admin/ajax_accion.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'marcar_leido', id: m.id, csrf: document.querySelector('meta[name="csrf-token"]').content})
    });
  }
}
function marcarRespondido(id) {
  fetch('/admin/ajax_accion.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'marcar_respondido', id, csrf: document.querySelector('meta[name="csrf-token"]').content})
  });
}
document.getElementById('modalMensaje').addEventListener('click', e => {
  if (e.target === document.getElementById('modalMensaje')) e.target.classList.remove('active');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
