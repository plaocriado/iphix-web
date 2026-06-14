<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$q     = trim($_GET['q'] ?? '');
$limit = max(1, min(12, (int)($_GET['limit'] ?? 6)));

if (strlen($q) < 2) { echo json_encode([]); exit; }

$results = dbQuery(
    "SELECT id, nombre, slug, imagen_principal, precio_venta, precio_oferta, marca
     FROM productos
     WHERE (nombre LIKE ? OR marca LIKE ? OR modelo LIKE ?) AND estado = 'disponible' AND activo = 1
     LIMIT ?",
    ["%$q%", "%$q%", "%$q%", $limit]
);

echo json_encode($results);
