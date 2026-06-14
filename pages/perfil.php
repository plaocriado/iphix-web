<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requiereLogin();

$usuario = usuarioActual();
$tab     = $_GET['tab'] ?? 'datos';

$datos = dbQueryOne('SELECT * FROM usuarios WHERE id = ?', [$usuario['id']]);
$pedidos = dbQuery(
    "SELECT p.*, COUNT(lp.id) AS num_productos FROM pedidos p LEFT JOIN lineas_pedido lp ON p.id = lp.pedido_id WHERE p.usuario_id = ? GROUP BY p.id ORDER BY p.created_at DESC",
    [$usuario['id']]
);

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $nombre    = trim(strip_tags($_POST['nombre']    ?? ''));
        $apellidos = trim(strip_tags($_POST['apellidos'] ?? ''));
        $telefono  = trim(strip_tags($_POST['telefono']  ?? ''));
        if (!$nombre || !$apellidos) { $errors[] = 'Nombre y apellidos son obligatorios.'; }
        else {
            dbExecute('UPDATE usuarios SET nombre=?, apellidos=?, telefono=? WHERE id=?', [$nombre, $apellidos, $telefono, $usuario['id']]);
            $_SESSION['usuario']['nombre']    = $nombre;
            $_SESSION['usuario']['apellidos'] = $apellidos;
            $datos = dbQueryOne('SELECT * FROM usuarios WHERE id = ?', [$usuario['id']]);
            $success = 'Datos actualizados correctamente.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $actual  = $_POST['password_actual'] ?? '';
        $nueva   = $_POST['password_nueva'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if (!password_verify($actual, $datos['password'])) { $errors[] = 'La contraseña actual no es correcta.'; }
        elseif (strlen($nueva) < 8) { $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.'; }
        elseif ($nueva !== $confirm) { $errors[] = 'Las contraseñas no coinciden.'; }
        else {
            dbExecute('UPDATE usuarios SET password=? WHERE id=?', [password_hash($nueva, PASSWORD_BCRYPT, ['cost'=>12]), $usuario['id']]);
            $success = 'Contraseña actualizada correctamente.';
            $tab = 'seguridad';
        }
    }
}

$pageTitle = 'Mi cuenta';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:3rem;padding-bottom:4rem">
  <div class="row g-4">
    <!-- Sidebar -->
    <div class="col-lg-3">
      <div class="profile-sidebar">
        <div class="profile-avatar"><?= strtoupper(substr($datos['nombre'], 0, 1) . substr($datos['apellidos'], 0, 1)) ?></div>
        <h4 style="margin-bottom:0.2rem"><?= e($datos['nombre'].' '.$datos['apellidos']) ?></h4>
        <p style="font-size:0.85rem;margin-bottom:1.5rem"><?= e($datos['email']) ?></p>
        <nav style="display:flex;flex-direction:column;gap:0.25rem;text-align:left">
          <?php foreach ([
            ['datos',     'bi-person-fill',   'Mis datos'],
            ['pedidos',   'bi-box-seam',      'Mis pedidos'],
            ['seguridad', 'bi-shield-lock-fill','Seguridad'],
          ] as $item): ?>
          <a href="?tab=<?= $item[0] ?>"
             style="display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.9rem;border-radius:var(--radius-sm);font-size:0.9rem;font-weight:500;color:<?= $tab === $item[0] ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>;background:<?= $tab === $item[0] ? 'rgba(0,212,255,0.08)' : 'transparent' ?>;transition:all 0.15s">
            <i class="bi <?= $item[1] ?>"></i> <?= $item[2] ?>
          </a>
          <?php endforeach; ?>
          <a href="/pages/logout.php" style="display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.9rem;border-radius:var(--radius-sm);font-size:0.9rem;font-weight:500;color:var(--color-danger);margin-top:0.5rem">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
          </a>
        </nav>
      </div>
    </div>

    <!-- Contenido -->
    <div class="col-lg-9">
      <?php foreach ($errors as $err): ?>
      <div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
      <?php endforeach; ?>
      <?php if ($success): ?>
      <div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
      <?php endif; ?>

      <?php if ($tab === 'datos'): ?>
      <!-- DATOS PERSONALES -->
      <div class="card-iphix card-iphix-glow">
        <h3 style="font-size:1.1rem;margin-bottom:1.5rem"><i class="bi bi-person-fill" style="color:var(--color-primary)"></i> Mis datos personales</h3>
        <form method="POST">
          <?= campoCSRF() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-input" value="<?= e($datos['nombre']) ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Apellidos</label>
                <input type="text" name="apellidos" class="form-input" value="<?= e($datos['apellidos']) ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" value="<?= e($datos['email']) ?>" readonly style="opacity:0.6">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Teléfono (opcional)</label>
                <input type="tel" name="telefono" class="form-input" value="<?= e($datos['telefono'] ?? '') ?>" placeholder="+34 600 000 000">
              </div>
            </div>
          </div>
          <button type="submit" name="actualizar" class="btn-iphix btn-primary-iphix mt-2"><i class="bi bi-floppy-fill"></i> Guardar cambios</button>
        </form>
      </div>

      <?php elseif ($tab === 'pedidos'): ?>
      <!-- PEDIDOS -->
      <div class="admin-table-wrap">
        <div class="admin-table-header">
          <div class="admin-table-title"><i class="bi bi-box-seam" style="color:var(--color-primary)"></i> Mis pedidos</div>
        </div>
        <?php if (empty($pedidos)): ?>
        <div style="text-align:center;padding:3rem">
          <i class="bi bi-inbox" style="font-size:3rem;color:var(--color-text-dim);display:block;margin-bottom:1rem"></i>
          <p>Aún no has realizado ningún pedido.</p>
          <a href="/pages/productos.php" class="btn-iphix btn-outline-iphix mt-1">Ver catálogo</a>
        </div>
        <?php else: ?>
        <table class="admin-table">
          <thead><tr>
            <th>Pedido</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Estado</th><th>Acción</th>
          </tr></thead>
          <tbody>
          <?php foreach ($pedidos as $p): ?>
          <?php
          $statusClass = match($p['estado']) {
            'pendiente'  => 'status-pendiente',
            'pagado','procesando' => 'status-pagado',
            'enviado'   => 'status-enviado',
            'entregado' => 'status-entregado',
            default     => 'status-cancelado',
          };
          ?>
          <tr>
            <td><strong><?= e($p['codigo']) ?></strong></td>
            <td><?= formatearFecha($p['created_at']) ?></td>
            <td><?= $p['num_productos'] ?> art.</td>
            <td><strong><?= formatearPrecio($p['total']) ?></strong></td>
            <td><span class="order-status <?= $statusClass ?>"><?= ucfirst($p['estado']) ?></span></td>
            <td><a href="/pages/pedido.php?codigo=<?= e($p['codigo']) ?>" class="btn-iphix btn-outline-iphix btn-sm-iphix">Ver</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <?php elseif ($tab === 'seguridad'): ?>
      <!-- SEGURIDAD -->
      <div class="card-iphix card-iphix-glow">
        <h3 style="font-size:1.1rem;margin-bottom:1.5rem"><i class="bi bi-shield-lock-fill" style="color:var(--color-primary)"></i> Cambiar contraseña</h3>
        <form method="POST">
          <?= campoCSRF() ?>
          <div class="form-group">
            <label class="form-label">Contraseña actual</label>
            <input type="password" name="password_actual" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="password_nueva" class="form-input" minlength="8" required placeholder="Mínimo 8 caracteres">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar nueva contraseña</label>
            <input type="password" name="password_confirm" class="form-input" required>
          </div>
          <button type="submit" name="cambiar_password" class="btn-iphix btn-primary-iphix"><i class="bi bi-key-fill"></i> Cambiar contraseña</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
