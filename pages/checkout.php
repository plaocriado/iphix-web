<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requiereLogin();

$carrito = obtenerCarrito();
if (empty($carrito)) { header('Location: /pages/carrito.php'); exit; }

$usuario = usuarioActual();
$total   = totalCarrito();
$gastos  = $total >= 50 ? 0 : 4.99;
$totalFinal = $total + $gastos;

$errors = [];
$step   = (int)($_GET['step'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso_envio'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $_SESSION['checkout_envio'] = [
            'nombre'    => trim(strip_tags($_POST['nombre_completo'] ?? '')),
            'linea1'    => trim(strip_tags($_POST['direccion'] ?? '')),
            'ciudad'    => trim(strip_tags($_POST['ciudad'] ?? '')),
            'provincia' => trim(strip_tags($_POST['provincia'] ?? '')),
            'cp'        => trim(strip_tags($_POST['codigo_postal'] ?? '')),
            'pais'      => 'España',
        ];
        foreach ($_SESSION['checkout_envio'] as $v) {
            if (empty($v)) { $errors[] = 'Por favor, rellena todos los campos de dirección.'; break; }
        }
        if (empty($errors)) {
            header('Location: /pages/checkout.php?step=2'); exit;
        }
    }
}

$envio = $_SESSION['checkout_envio'] ?? null;

$pageTitle = 'Finalizar compra';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:3rem;padding-bottom:4rem;max-width:960px">
  <h1 style="font-size:1.8rem;margin-bottom:0.25rem"><i class="bi bi-credit-card-fill" style="color:var(--color-primary)"></i> Finalizar compra</h1>
  <p style="color:var(--color-text-muted);margin-bottom:2rem">Estás a un paso de tener tu dispositivo</p>

  <!-- Steps -->
  <div class="checkout-step-indicator mb-4">
    <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>">1</div>
    <div class="step-line <?= $step >= 2 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>">2</div>
    <div class="step-line <?= $step >= 3 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
  </div>
  <div style="display:flex;gap:3rem;font-size:0.8rem;color:var(--color-text-muted);margin-top:-1.5rem;margin-bottom:2rem">
    <span style="color:<?= $step >= 1 ? 'var(--color-primary)' : '' ?>">Dirección</span>
    <span style="color:<?= $step >= 2 ? 'var(--color-primary)' : '' ?>">Pago</span>
    <span style="color:<?= $step >= 3 ? 'var(--color-primary)' : '' ?>">Confirmación</span>
  </div>

  <?php foreach ($errors as $e): ?>
  <div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($e) ?></div>
  <?php endforeach; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <?php if ($step === 1): ?>
      <!-- PASO 1: DIRECCIÓN -->
      <div class="card-iphix card-iphix-glow">
        <h3 style="font-size:1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem"><i class="bi bi-geo-alt-fill" style="color:var(--color-primary)"></i> Dirección de envío</h3>
        <form method="POST">
          <?= campoCSRF() ?>
          <div class="form-group">
            <label class="form-label">Nombre completo</label>
            <input type="text" name="nombre_completo" class="form-input" value="<?= e($envio['nombre'] ?? $usuario['nombre'].' '.$usuario['apellidos']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Dirección</label>
            <input type="text" name="direccion" class="form-input" placeholder="Calle, número, piso, puerta..." value="<?= e($envio['linea1'] ?? '') ?>" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Ciudad</label>
                <input type="text" name="ciudad" class="form-input" value="<?= e($envio['ciudad'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Provincia</label>
                <input type="text" name="provincia" class="form-input" value="<?= e($envio['provincia'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Código postal</label>
                <input type="text" name="codigo_postal" class="form-input" pattern="[0-9]{5}" maxlength="5" value="<?= e($envio['cp'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">País</label>
                <input type="text" class="form-input" value="España" readonly style="opacity:0.7">
              </div>
            </div>
          </div>
          <button type="submit" name="paso_envio" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;margin-top:0.5rem">
            Continuar al pago <i class="bi bi-arrow-right"></i>
          </button>
        </form>
      </div>

      <?php elseif ($step === 2): ?>
      <!-- PASO 2: PAGO (Stripe) -->
      <div class="card-iphix card-iphix-glow">
        <h3 style="font-size:1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem"><i class="bi bi-credit-card-fill" style="color:var(--color-primary)"></i> Datos de pago</h3>
        <div id="stripe-form">
          <div class="form-group">
            <label class="form-label">Titular de la tarjeta</label>
            <input type="text" id="card-holder" class="form-input" placeholder="Nombre como aparece en la tarjeta" value="<?= e($usuario['nombre'].' '.$usuario['apellidos']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Datos de la tarjeta</label>
            <div id="card-element" style="background:rgba(255,255,255,0.04);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:0.9rem 1rem;transition:border-color 0.2s">
              <!-- Stripe Card Element se monta aquí -->
            </div>
            <div id="card-errors" style="color:var(--color-danger);font-size:0.82rem;margin-top:0.4rem"></div>
          </div>

          <div class="alert-iphix alert-info mb-3">
            <i class="bi bi-info-circle-fill"></i>
            <span>Modo demo: usa la tarjeta <strong>4242 4242 4242 4242</strong>, cualquier fecha futura y CVC.</span>
          </div>

          <button id="pay-btn" class="btn-iphix btn-primary-iphix w-100" style="justify-content:center;padding:1rem">
            <i class="bi bi-lock-fill"></i> Pagar <?= formatearPrecio($totalFinal) ?>
          </button>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;justify-content:center;margin-top:1.25rem">
          <i class="bi bi-shield-lock" style="color:var(--color-text-dim)"></i>
          <span style="font-size:0.8rem;color:var(--color-text-dim)">Pago 100% seguro con Stripe. No guardamos datos de tarjeta.</span>
        </div>
      </div>

      <?php elseif ($step === 3): ?>
      <!-- PASO 3: CONFIRMACIÓN -->
      <div class="card-iphix" style="text-align:center;padding:3rem 2rem">
        <div style="width:80px;height:80px;background:rgba(0,255,157,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem;color:var(--color-accent)">
          <i class="bi bi-check2-circle"></i>
        </div>
        <h2 style="color:var(--color-accent);margin-bottom:0.5rem">¡Pedido confirmado!</h2>
        <p style="color:var(--color-text-muted);margin-bottom:1.5rem">Hemos recibido tu pedido y te enviaremos un email de confirmación.</p>
        <?php if (isset($_SESSION['ultimo_pedido'])): ?>
        <div style="background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.15);border-radius:var(--radius-md);padding:1rem;margin-bottom:1.5rem">
          <span style="font-size:0.85rem;color:var(--color-text-muted)">Número de pedido</span>
          <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--color-primary)"><?= e($_SESSION['ultimo_pedido']) ?></div>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
          <a href="/pages/perfil.php?tab=pedidos" class="btn-iphix btn-outline-iphix"><i class="bi bi-box-seam"></i> Ver mis pedidos</a>
          <a href="/" class="btn-iphix btn-primary-iphix"><i class="bi bi-house-fill"></i> Volver al inicio</a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Resumen lateral -->
    <div class="col-lg-5">
      <div class="card-iphix" style="position:sticky;top:100px">
        <h3 style="font-size:0.95rem;margin-bottom:1.25rem">Resumen</h3>
        <?php foreach ($carrito as $item): ?>
        <div style="display:flex;gap:0.75rem;align-items:center;margin-bottom:0.75rem">
          <img src="/assets/img/productos/<?= e($item['imagen']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:var(--radius-sm);background:#111620;flex-shrink:0" alt="">
          <div style="flex:1;min-width:0">
            <div style="font-size:0.85rem;font-weight:600;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($item['nombre']) ?></div>
            <div style="font-size:0.78rem;color:var(--color-text-muted)">Cantidad: <?= $item['cantidad'] ?></div>
          </div>
          <div style="font-size:0.9rem;font-weight:700;color:var(--color-text);flex-shrink:0"><?= formatearPrecio($item['precio'] * $item['cantidad']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="height:1px;background:var(--color-border);margin:1rem 0"></div>
        <div style="display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:0.5rem">
          <span style="color:var(--color-text-muted)">Subtotal</span><span><?= formatearPrecio($total) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:0.75rem">
          <span style="color:var(--color-text-muted)">Envío</span>
          <span style="color:<?= $gastos === 0 ? 'var(--color-accent)' : '' ?>"><?= $gastos === 0 ? 'Gratis' : formatearPrecio($gastos) ?></span>
        </div>
        <div style="height:1px;background:var(--color-border);margin-bottom:0.75rem"></div>
        <div style="display:flex;justify-content:space-between;">
          <span style="font-weight:700;font-family:var(--font-display)">Total</span>
          <span style="font-size:1.3rem;font-weight:800;font-family:var(--font-display)"><?= formatearPrecio($totalFinal) ?></span>
        </div>
        <?php if ($envio && $step >= 2): ?>
        <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--color-border)">
          <div style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-muted);margin-bottom:0.5rem">Enviar a</div>
          <div style="font-size:0.88rem;color:var(--color-text);line-height:1.6">
            <?= e($envio['nombre']) ?><br><?= e($envio['linea1']) ?><br><?= e($envio['cp'].' '.$envio['ciudad'].', '.$envio['provincia']) ?>
          </div>
          <?php if ($step === 2): ?>
          <a href="/pages/checkout.php?step=1" style="font-size:0.8rem;color:var(--color-primary);display:inline-block;margin-top:0.4rem">Cambiar</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($step === 2): ?>
<!-- Stripe JS -->
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
const elements = stripe.elements({appearance:{theme:'night',variables:{colorBackground:'rgba(255,255,255,0.04)',colorText:'#e8ecf4',colorTextPlaceholder:'#4a5568',fontFamily:'"DM Sans", sans-serif',borderRadius:'12px'}}});
const cardElement = elements.create('card');
cardElement.mount('#card-element');
cardElement.on('change', e => { document.getElementById('card-errors').textContent = e.error ? e.error.message : ''; });

document.getElementById('pay-btn').addEventListener('click', async () => {
  const btn = document.getElementById('pay-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';
  try {
    const res = await fetch('/api/payment.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'create_intent', amount: <?= round($totalFinal * 100) ?>})
    });
    const {client_secret, error} = await res.json();
    if (error) throw new Error(error);
    const {paymentIntent, error: stripeError} = await stripe.confirmCardPayment(client_secret, {
      payment_method: {
        card: cardElement,
        billing_details: { name: document.getElementById('card-holder').value }
      }
    });
    if (stripeError) throw new Error(stripeError.message);
    if (paymentIntent.status === 'succeeded') {
      const confirm = await fetch('/api/payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'confirm_order', payment_intent_id: paymentIntent.id})
      });
      const confirmData = await confirm.json();
      if (confirmData.exito) window.location = '/pages/checkout.php?step=3';
      else throw new Error(confirmData.error || 'Error al confirmar pedido');
    }
  } catch (err) {
    document.getElementById('card-errors').textContent = err.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-lock-fill"></i> Pagar <?= formatearPrecio($totalFinal) ?>';
  }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
