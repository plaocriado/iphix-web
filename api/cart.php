<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
iniciarSesion();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $id  = (int)($data['producto_id'] ?? 0);
        $qty = max(1, (int)($data['cantidad'] ?? 1));
        if (!$id) { echo json_encode(['exito'=>false,'error'=>'ID inválido']); exit; }
        $ok = añadirAlCarrito($id, $qty);
        echo json_encode(['exito'=>$ok, 'cantidad_total'=>cantidadCarrito(), 'error'=>$ok?null:'Producto no disponible']);
        break;

    case 'remove':
        $id = (int)($data['producto_id'] ?? 0);
        eliminarDelCarrito($id);
        echo json_encode(['exito'=>true, 'cantidad_total'=>cantidadCarrito(), 'total'=>totalCarrito()]);
        break;

    case 'increase':
        $id = (int)($data['producto_id'] ?? 0);
        iniciarSesion();
        if (isset($_SESSION['carrito'][$id])) {
            $stock = $_SESSION['carrito'][$id]['stock'];
            if ($_SESSION['carrito'][$id]['cantidad'] < $stock) $_SESSION['carrito'][$id]['cantidad']++;
            $nq = $_SESSION['carrito'][$id]['cantidad'];
        } else { $nq = 0; }
        echo json_encode(['exito'=>true, 'nueva_cantidad'=>$nq, 'cantidad_total'=>cantidadCarrito(), 'total'=>totalCarrito()]);
        break;

    case 'decrease':
        $id = (int)($data['producto_id'] ?? 0);
        iniciarSesion();
        if (isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad']--;
            $nq = $_SESSION['carrito'][$id]['cantidad'];
            if ($nq <= 0) { unset($_SESSION['carrito'][$id]); $nq = 0; }
        } else { $nq = 0; }
        echo json_encode(['exito'=>true, 'nueva_cantidad'=>$nq, 'cantidad_total'=>cantidadCarrito(), 'total'=>totalCarrito()]);
        break;

    case 'get':
        $items = obtenerCarrito();
        echo json_encode(['exito'=>true, 'items'=>array_values($items), 'total'=>totalCarrito(), 'cantidad'=>cantidadCarrito()]);
        break;

    default:
        echo json_encode(['exito'=>false, 'error'=>'Acción no reconocida']);
}
