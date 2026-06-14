<?php
$pageTitle  = 'Dashboard';
$currentPage = '';
require_once __DIR__ . '/includes/header.php';

$totalVentas   = dbQueryOne("SELECT COALESCE(SUM(importe),0) AS t FROM transacciones WHERE tipo='ingreso' AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")['t'];
$totalGastos   = dbQueryOne("SELECT COALESCE(SUM(importe),0) AS t FROM transacciones WHERE tipo='gasto'  AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")['t'];
$pedidosMes    = dbQueryOne("SELECT COUNT(*) AS t FROM pedidos WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")['t'];
$productosDisp = dbQueryOne("SELECT COUNT(*) AS t FROM productos WHERE estado='disponible' AND activo=1")['t'];
$totalClientes = dbQueryOne("SELECT COUNT(*) AS t FROM usuarios WHERE rol='cliente'")['t'];
$mensajesSin   = dbQueryOne("SELECT COUNT(*) AS t FROM contacto WHERE leido=0")['t'];
$beneficioMes  = $totalVentas - $totalGastos;

$meses6 = dbQuery("
  SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes, DATE_FORMAT(fecha,'%b %Y') AS label,
         SUM(CASE WHEN tipo='ingreso' THEN importe ELSE 0 END) AS ingresos,
         SUM(CASE WHEN tipo='gasto'  THEN importe ELSE 0 END) AS gastos
  FROM transacciones WHERE fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(fecha,'%Y-%m') ORDER BY mes ASC");

$topProductos = dbQuery("
  SELECT p.nombre, p.imagen_principal, SUM(lp.cantidad) AS vendidos, SUM(lp.subtotal) AS total_vendido
  FROM lineas_pedido lp JOIN productos p ON lp.producto_id = p.id
  JOIN pedidos pe ON lp.pedido_id = pe.id WHERE pe.estado != 'cancelado'
  GROUP BY p.id ORDER BY vendidos DESC LIMIT 5");

$ultimosPedidos = dbQuery("SELECT p.*, u.nombre, u.apellidos FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.created_at DESC LIMIT 8");

$stockBajo = dbQuery("
    SELECT
        id,
        nombre,
        referencia,
        stock,
        stock_minimo,
        proveedor
    FROM piezas
    WHERE stock <= stock_minimo
      AND activa = 1
");
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card stat-card-glow-blue">
      <div class="stat-card-icon stat-icon-blue"><i class="bi bi-currency-euro"></i></div>
      <div class="stat-value"><?= number_format($totalVentas, 0, ',', '.') ?>€</div>
      <div class="stat-label">Ingresos este mes</div>
      <span class="stat-change stat-up"><i class="bi bi-arrow-up"></i> Mes actual</span>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card stat-card-glow-green">
      <div class="stat-card-icon stat-icon-green"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="stat-value"><?= number_format($beneficioMes, 0, ',', '.') ?>€</div>
      <div class="stat-label">Beneficio neto mes</div>
      <span class="stat-change <?= $beneficioMes >= 0 ? 'stat-up' : 'stat-down' ?>">
        <i class="bi bi-arrow-<?= $beneficioMes >= 0 ? 'up' : 'down' ?>"></i> Este mes
      </span>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card stat-card-glow-yellow">
      <div class="stat-card-icon stat-icon-yellow"><i class="bi bi-bag-check"></i></div>
      <div class="stat-value"><?= $pedidosMes ?></div>
      <div class="stat-label">Pedidos este mes</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card stat-card-glow-red">
      <div class="stat-card-icon stat-icon-red"><i class="bi bi-phone"></i></div>
      <div class="stat-value"><?= $productosDisp ?></div>
      <div class="stat-label">Productos disponibles</div>
    </div>
  </div>
</div>

<!-- Segunda fila stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-card-icon stat-icon-blue"><i class="bi bi-people"></i></div>
      <div class="stat-value"><?= $totalClientes ?></div>
      <div class="stat-label">Clientes registrados</div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-card-icon stat-icon-red"><i class="bi bi-envelope-exclamation"></i></div>
      <div class="stat-value"><?= $mensajesSin ?></div>
      <div class="stat-label">Mensajes sin leer</div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="stat-card">
      <div class="stat-card-icon stat-icon-yellow"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="stat-value"><?= count($stockBajo) ?></div>
      <div class="stat-label">Piezas stock bajo</div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Gráfica ventas -->
  <div class="col-lg-8">
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <div class="chart-title">Ingresos vs Gastos</div>
          <div class="chart-subtitle">Últimos 6 meses</div>
        </div>
      </div>
      <canvas id="ventasChart" height="120"></canvas>
    </div>
  </div>
  <!-- Top productos -->
  <div class="col-lg-4">
    <div class="chart-card" style="height:100%">
      <div class="chart-card-header">
        <div class="chart-title">Top productos vendidos</div>
      </div>
      <?php if (empty($topProductos)): ?>
      <p style="color:var(--a-text-muted);font-size:0.88rem">Sin datos de ventas aún.</p>
      <?php else: ?>
      <?php foreach ($topProductos as $tp): ?>
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.9rem">
        <img src="/assets/img/productos/<?= e($tp['imagen_principal']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;background:#111620;flex-shrink:0" alt="">
        <div style="flex:1;min-width:0">
          <div style="font-size:0.82rem;font-weight:600;color:var(--a-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($tp['nombre']) ?></div>
          <div class="progress-bar-wrap" style="margin-top:4px">
            <div class="progress-bar-fill" style="width:<?= min(100, $tp['vendidos']*20) ?>%"></div>
          </div>
        </div>
        <span style="font-size:0.8rem;color:var(--a-text-muted);flex-shrink:0"><?= $tp['vendidos'] ?>u</span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Últimos pedidos -->
  <div class="col-lg-8">
    <div class="admin-table-wrap">
      <div class="admin-table-header">
        <div class="admin-table-title">Últimos pedidos</div>
        <a href="/admin/pedidos.php" class="btn-admin btn-admin-outline btn-admin-sm">Ver todos</a>
      </div>
      <table class="admin-table">
        <thead><tr><th>Código</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($ultimosPedidos as $p): ?>
        <?php $badge = match($p['estado']) {
          'pagado','procesando'=>'badge-green','enviado'=>'badge-blue','entregado'=>'badge-green',
          'cancelado','reembolsado'=>'badge-red','pendiente'=>'badge-yellow', default=>'badge-gray'
        }; ?>
        <tr>
          <td><strong><?= e($p['codigo']) ?></strong></td>
          <td><?= e($p['nombre'].' '.$p['apellidos']) ?></td>
          <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
          <td><strong><?= number_format($p['total'],2,',','.').' €' ?></strong></td>
          <td><span class="badge-a <?= $badge ?>"><?= ucfirst($p['estado']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Alertas stock bajo -->
  <div class="col-lg-4">
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-title" style="color:var(--a-warning)"><i class="bi bi-exclamation-triangle"></i> Piezas con stock bajo</div>
      </div>
      <?php if (empty($stockBajo)): ?>
      <div style="text-align:center;padding:1.5rem;color:var(--a-accent)"><i class="bi bi-check2-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i> Todo el stock está OK</div>
      <?php else: ?>
      <?php foreach ($stockBajo as $p): ?>
      <div class="stock-alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <div style="font-weight:600;font-size:0.88rem"><?= e($p['nombre']) ?></div>
          <div style="font-size:0.78rem;opacity:0.8">Stock: <?= $p['stock'] ?> / Mín: <?= $p['stock_minimo'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <a href="/admin/piezas.php" class="btn-admin btn-admin-outline btn-admin-sm" style="margin-top:0.75rem">Gestionar piezas</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const labels = <?= json_encode(array_column($meses6, 'label')) ?>;
const ingresos = <?= json_encode(array_map(fn($r) => (float)$r['ingresos'], $meses6)) ?>;
const gastos   = <?= json_encode(array_map(fn($r) => (float)$r['gastos'],   $meses6)) ?>;

new Chart(document.getElementById('ventasChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { label:'Ingresos', data:ingresos, backgroundColor:'rgba(0,212,255,0.7)', borderRadius:6, borderSkipped:false },
      { label:'Gastos',   data:gastos,   backgroundColor:'rgba(255,77,109,0.5)',  borderRadius:6, borderSkipped:false }
    ]
  },
  options: {
    responsive:true,
    plugins:{ legend:{labels:{color:'#8892a4',font:{size:12}}}, tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2).replace('.',',')} €`}} },
    scales:{
      x:{ticks:{color:'#8892a4'},grid:{color:'rgba(255,255,255,0.04)'}},
      y:{ticks:{color:'#8892a4',callback:v=>v+'€'},grid:{color:'rgba(255,255,255,0.04)'}}
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
