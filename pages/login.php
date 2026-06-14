<?php
require_once __DIR__ . '/../includes/auth.php';
if (estaLogueado()) { header('Location: /'); exit; }

$modo    = $_GET['modo'] ?? 'login';
$redirect = $_GET['redirect'] ?? '/';
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } elseif (isset($_POST['login'])) {
        $resultado = intentarLogin($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($resultado['exito']) {
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => '¡Bienvenido de nuevo, ' . $resultado['usuario']['nombre'] . '!'];
            header('Location: ' . (filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : '/'));
            exit;
        } else {
            $errors[] = $resultado['error'];
        }
    } elseif (isset($_POST['registro'])) {
        $resultado = registrarUsuario($_POST);
        if ($resultado['exito']) {
            intentarLogin($_POST['email'], $_POST['password']);
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => '¡Cuenta creada con éxito! Bienvenido a iphix.'];
            header('Location: /'); exit;
        } else {
            $errors[] = $resultado['error'];
            $modo = 'registro';
        }
    }
}

$pageTitle = $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — iphix</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <!-- Logo -->
    <div style="text-align:center;margin-bottom:2rem">
      <a href="/" class="navbar-brand-iphix" style="font-size:2.2rem">iphix</a>
      <p style="margin-top:0.5rem;font-size:0.9rem;color:var(--color-text-muted)">
        <?= $modo === 'registro' ? 'Crea tu cuenta y empieza a ahorrar' : 'Accede a tu cuenta' ?>
      </p>
    </div>

    <!-- Tabs Login / Registro -->
    <div style="display:flex;background:rgba(255,255,255,0.04);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:4px;margin-bottom:2rem">
      <a href="?modo=login&redirect=<?= urlencode($redirect) ?>"
         style="flex:1;text-align:center;padding:0.6rem;border-radius:var(--radius-xl);font-size:0.9rem;font-weight:600;font-family:var(--font-display);transition:all 0.2s;<?= $modo !== 'registro' ? 'background:var(--color-primary);color:#000' : 'color:var(--color-text-muted)' ?>">
        Iniciar sesión
      </a>
      <a href="?modo=registro&redirect=<?= urlencode($redirect) ?>"
         style="flex:1;text-align:center;padding:0.6rem;border-radius:var(--radius-xl);font-size:0.9rem;font-weight:600;font-family:var(--font-display);transition:all 0.2s;<?= $modo === 'registro' ? 'background:var(--color-primary);color:#000' : 'color:var(--color-text-muted)' ?>">
        Crear cuenta
      </a>
    </div>

    <!-- Errores -->
    <?php foreach ($errors as $err): ?>
    <div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
    <?php endforeach; ?>

    <!-- FORM LOGIN -->
    <?php if ($modo !== 'registro'): ?>
    <form method="POST" action="">
      <?= campoCSRF() ?>
      <div class="form-group">
        <label class="form-label">Email</label>
        <div style="position:relative">
          <i class="bi bi-envelope" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim)"></i>
          <input type="email" name="email" class="form-input" placeholder="tu@email.com" style="padding-left:2.75rem" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem">
          <label class="form-label" style="margin:0">Contraseña</label>
          <a href="#" style="font-size:0.8rem;color:var(--color-text-muted)">¿Olvidaste la contraseña?</a>
        </div>
        <div style="position:relative">
          <i class="bi bi-lock" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim)"></i>
          <input type="password" name="password" id="pwLogin" class="form-input" placeholder="Tu contraseña" style="padding-left:2.75rem;padding-right:3rem" required autocomplete="current-password">
          <button type="button" onclick="togglePw('pwLogin',this)" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-text-dim);cursor:pointer"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button type="submit" name="login" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;padding:0.85rem">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
      </button>
    </form>

    <!-- FORM REGISTRO -->
    <?php else: ?>
    <form method="POST" action="">
      <?= campoCSRF() ?>
      <div class="row g-3">
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-input" placeholder="Tu nombre" required value="<?= e($_POST['nombre'] ?? '') ?>">
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Apellidos</label>
            <input type="text" name="apellidos" class="form-input" placeholder="Tus apellidos" required value="<?= e($_POST['apellidos'] ?? '') ?>">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <div style="position:relative">
          <i class="bi bi-envelope" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim)"></i>
          <input type="email" name="email" class="form-input" placeholder="tu@email.com" style="padding-left:2.75rem" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div style="position:relative">
          <i class="bi bi-lock" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim)"></i>
          <input type="password" name="password" id="pwReg1" class="form-input" placeholder="Mínimo 8 caracteres" style="padding-left:2.75rem;padding-right:3rem" required minlength="8">
          <button type="button" onclick="togglePw('pwReg1',this)" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-text-dim);cursor:pointer"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar contraseña</label>
        <div style="position:relative">
          <i class="bi bi-lock-fill" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--color-text-dim)"></i>
          <input type="password" name="confirm_password" id="pwReg2" class="form-input" placeholder="Repite la contraseña" style="padding-left:2.75rem;padding-right:3rem" required>
          <button type="button" onclick="togglePw('pwReg2',this)" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-text-dim);cursor:pointer"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:0.6rem;margin-bottom:1.25rem">
        <input type="checkbox" id="acepto" required style="margin-top:3px;accent-color:var(--color-primary)">
        <label for="acepto" style="font-size:0.85rem;color:var(--color-text-muted);line-height:1.5">Acepto los <a href="#">términos y condiciones</a> y la <a href="#">política de privacidad</a></label>
      </div>
      <button type="submit" name="registro" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;padding:0.85rem">
        <i class="bi bi-person-plus-fill"></i> Crear cuenta gratis
      </button>
    </form>
    <?php endif; ?>

    <p style="text-align:center;margin-top:1.5rem;font-size:0.85rem;color:var(--color-text-muted)">
      <?= $modo === 'registro' ? '¿Ya tienes cuenta?' : '¿No tienes cuenta?' ?>
      <a href="?modo=<?= $modo === 'registro' ? 'login' : 'registro' ?>&redirect=<?= urlencode($redirect) ?>" style="font-weight:600">
        <?= $modo === 'registro' ? 'Inicia sesión' : 'Regístrate gratis' ?>
      </a>
    </p>
  </div>
</div>
<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
