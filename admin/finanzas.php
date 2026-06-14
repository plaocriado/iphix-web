<?php
$pageTitle   = 'Finanzas';
$currentPage = 'finanzas';
require_once __DIR__ . '/includes/header.php';

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_transaccion'])) {
    if (!validarCSRF($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $tipo     = $_POST['tipo']     ?? 'ingreso';
        $concepto = trim(strip_tags($_POST['concepto'] ?? ''));
        $importe  = (float)str_replace(',','.',($_POST['importe'] ?? 0));
        $cat      = trim(strip_tags($_POST['categoria'] ?? ''));
        $fecha    = $_POST['fecha'] ?? date('Y-m-d');
        $notas    = trim(strip_tags($_POST['notas'] ?? ''));
        if (!$concepto || !$importe) { $errors[] = 'Concepto e importe son obligatorios.'; }
        else {
            dbExecute('INSERT INTO transacciones (tipo,concepto,importe,categoria,notas,fecha) VALUES(?,?,?,?,?,?)',
                [$tipo,$concepto,$importe,$cat,$notas,$fecha]);
            $success = 'Transacción registrada.';
        }
    }
}

$mesAct = dbQueryOne("SELECT
  SUM(CASE WHEN tipo='ingreso' THEN importe ELSE 0 END) AS ingresos,
  SUM(CASE WHEN tipo='gasto'  THEN importe ELSE 0 END) AS gastos
  FROM transacciones WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())");
$ingresosM = $mesAct['ingresos'] ?? 0;
$gastosM   = $mesAct['gastos']   ?? 0;
$beneficioM= $ingresosM - $gastosM;

$pagina = max(1,(int)($_GET['p'] ?? 1));
$porPag = 25;
$offset = ($pagina-1)*$porPag;
$transacciones = dbQuery("SELECT * FROM transacciones ORDER BY fecha DESC, id DESC LIMIT $porPag OFFSET $offset");
$totalT = dbQueryOne('SELECT COUNT(*) AS t FROM transacciones')['t'] ?? 0;
$totalPags = (int)ceil($totalT/$porPag);
?>

<?php foreach ($errors as $err): ?><div class="alert-iphix alert-error mb-3"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert-iphix alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card stat-card-glow-green">
    <div class="stat-card-icon stat-icon-green"><i class="bi bi-arrow-up-circle-fill"></i></div>
    <div class="stat-value"><?= number_format($ingresosM,2,',','.').' €' ?></div>
    <div class="stat-label">Ingresos este mes</div>
  </div></div>
  <div class="col-md-4"><div class="stat-card stat-card-glow-red">
    <div class="stat-card-icon stat-icon-red"><i class="bi bi-arrow-down-circle-fill"></i></div>
    <div class="stat-value"><?= number_format($gastosM,2,',','.').' €' ?></div>
    <div class="stat-label">Gastos este mes</div>
  </div></div>
  <div class="col-md-4"><div class="stat-card stat-card-glow-<?= $beneficioM >= 0 ? 'blue' : 'red' ?>">
    <div class="stat-card-icon stat-icon-<?= $beneficioM >= 0 ? 'blue' : 'red' ?>"><i class="bi bi-wallet2"></i></div>
    <div class="stat-value" style="color:<?= $beneficioM >= 0 ? 'var(--a-accent)' : 'var(--a-danger)' ?>"><?= number_format($beneficioM,2,',','.').' €' ?></div>
    <div class="stat-label">Beneficio neto</div>
  </div></div>
</div>

<div class="row g-4">
  <!-- Formulario -->
  <div class="col-lg-4">
    <div class="admin-form-card">
      <h3 style="font-size:1rem;margin-bottom:1.25rem"><i class="bi bi-plus-circle-fill" style="color:var(--a-primary)"></i> Registrar transacción</h3>
      <form method="POST">
        <?= campoCSRF() ?>
        <div class="admin-form-group">
          <label class="admin-form-label">Tipo</label>
          <div style="display:flex;gap:0.5rem">
            <label style="flex:1;display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0.75rem;border-radius:8px;border:1px solid var(--a-border);cursor:pointer;font-size:0.88rem">
              <input type="radio" name="tipo" value="ingreso" checked style="accent-color:var(--a-accent)"> Ingreso
            </label>
            <label style="flex:1;display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0.75rem;border-radius:8px;border:1px solid var(--a-border);cursor:pointer;font-size:0.88rem">
              <input type="radio" name="tipo" value="gasto" style="accent-color:var(--a-danger)"> Gasto
            </label>
          </div>
        </div>
        <div class="admin-form-group">
          <label class="admin-form-label">Concepto *</label>
          <input type="text" name="concepto" class="admin-form-input" required placeholder="Descripción del movimiento">
        </div>
        <div class="admin-form-group">
          <label class="admin-form-label">Importe (€) *</label>
          <input type="number" name="importe" class="admin-form-input" step="0.01" min="0.01" required placeholder="0.00">
        </div>
        <div class="admin-form-group">
          <label class="admin-form-label">Categoría</label>
          <select name="categoria" class="admin-form-input">
            <option value="">Sin categoría</option>
            <?php foreach (['Ventas','Reparaciones','Dispositivos','Piezas','Operativos','Envíos','Otros'] as $c): ?>
            <option><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="admin-form-group">
          <label class="admin-form-label">Fecha</label>
          <input type="date" name="fecha" class="admin-form-input" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="admin-form-group">
          <label class="admin-form-label">Notas</label>
          <textarea name="notas" class="admin-form-input" rows="2" placeholder="Notas opcionales..."></textarea>
        </div>
        <button type="submit" name="nueva_transaccion" class="btn-admin btn-admin-primary w-100"><i class="bi bi-plus-lg"></i> Registrar</button>
      </form>
    </div>
  </div>

  <!-- Tabla -->
  <div class="col-lg-8">
    <div class="admin-table-wrap">
      <div class="admin-table-header">
        <div class="admin-table-title">Historial de transacciones</div>
      </div>
      <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th>Tipo</th><th>Importe</th></tr></thead>
        <tbody>
        <?php foreach ($transacciones as $t): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
          <td><strong style="color:var(--a-text)"><?= e($t['concepto']) ?></strong><?php if ($t['notas']): ?><div style="font-size:0.75rem"><?= e(substr($t['notas'],0,60)) ?></div><?php endif; ?></td>
          <td><?php if ($t['categoria']): ?><span class="badge-a badge-gray"><?= e($t['categoria']) ?></span><?php endif; ?></td>
          <td><span class="badge-a <?= $t['tipo']==='ingreso'?'badge-green':'badge-red' ?>"><?= ucfirst($t['tipo']) ?></span></td>
          <td style="font-weight:700;color:<?= $t['tipo']==='ingreso'?'var(--a-accent)':'var(--a-danger)' ?>">
            <?= $t['tipo']==='ingreso'?'+':'-' ?><?= number_format($t['importe'],2,',','.').' €' ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($totalPags > 1): ?>
      <div style="padding:1rem 1.25rem;display:flex;justify-content:center;border-top:1px solid var(--a-border)">
        <div class="pagination-iphix">
          <?php if ($pagina>1): ?><a href="?p=<?= $pagina-1 ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
          <?php for($pp=max(1,$pagina-2);$pp<=min($totalPags,$pagina+2);$pp++): ?>
          <a href="?p=<?= $pp ?>" class="page-btn <?= $pp===$pagina?'active':'' ?>"><?= $pp ?></a>
          <?php endfor; ?>
          <?php if ($pagina<$totalPags): ?><a href="?p=<?= $pagina+1 ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
