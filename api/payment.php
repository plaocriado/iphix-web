<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requiereLogin();

header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

switch ($action) {
    
    case 'create_intent':
        $carrito = obtenerCarrito();
        if (empty($carrito)) { echo json_encode(['error'=>'Carrito vacío']); exit; }

        $amount = (int) round((totalCarrito() + (totalCarrito() >= 50 ? 0 : 4.99)) * 100);

        $ch = curl_init('https://api.stripe.com/v1/payment_intents');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
            CURLOPT_POSTFIELDS     => http_build_query([
                'amount'   => $amount,
                'currency' => 'eur',
                'metadata' => ['user_id' => usuarioActual()['id']],
            ]),
        ]);
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $intent = json_decode($resp, true);
        if ($status !== 200 || empty($intent['client_secret'])) {
            echo json_encode(['error' => $intent['error']['message'] ?? 'Error Stripe']); exit;
        }
        echo json_encode(['client_secret' => $intent['client_secret']]);
        break;

    
    case 'confirm_order':
        $pi_id   = trim($data['payment_intent_id'] ?? '');
        $usuario = usuarioActual();
        $carrito = obtenerCarrito();
        $envio   = $_SESSION['checkout_envio'] ?? null;

        if (!$pi_id || empty($carrito) || !$envio) {
            echo json_encode(['exito'=>false,'error'=>'Datos incompletos']); exit;
        }

        
        $ch = curl_init("https://api.stripe.com/v1/payment_intents/$pi_id");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_USERPWD=>STRIPE_SECRET_KEY.':']);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (($resp['status'] ?? '') !== 'succeeded') {
            echo json_encode(['exito'=>false,'error'=>'Pago no confirmado']); exit;
        }

        try {
            db()->beginTransaction();

            
            $totalPedidos = dbQueryOne('SELECT COUNT(*) AS t FROM pedidos')['t'] + 1;
            $codigo = 'IPH-' . date('Y') . '-' . str_pad($totalPedidos, 4, '0', STR_PAD_LEFT);

            $subtotal = totalCarrito();
            $gastos   = $subtotal >= 50 ? 0 : 4.99;
            $total    = $subtotal + $gastos;

            
            dbExecute(
                "INSERT INTO pedidos (codigo, usuario_id, estado, subtotal, gastos_envio, total, nombre_destinatario, direccion_envio, ciudad, provincia, codigo_postal, pais, stripe_payment_intent, stripe_payment_status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$codigo, $usuario['id'], 'pagado', $subtotal, $gastos, $total,
                 $envio['nombre'], $envio['linea1'], $envio['ciudad'], $envio['provincia'], $envio['cp'], $envio['pais'],
                 $pi_id, 'succeeded']
            );
            $pedidoId = dbLastInsertId();

            
            foreach ($carrito as $item) {
                dbExecute(
                    'INSERT INTO lineas_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?,?)',
                    [$pedidoId, $item['id'], $item['nombre'], $item['cantidad'], $item['precio'], $item['precio'] * $item['cantidad']]
                );
                dbExecute('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?', [$item['cantidad'], $item['id'], $item['cantidad']]);
            }

            
            dbExecute(
                'INSERT INTO transacciones (tipo, concepto, importe, categoria, pedido_id, fecha) VALUES (?,?,?,?,?,?)',
                ['ingreso', "Venta - Pedido $codigo", $total, 'Ventas', $pedidoId, date('Y-m-d')]
            );

            db()->commit();

            vaciarCarrito();
            unset($_SESSION['checkout_envio']);
            $_SESSION['ultimo_pedido'] = $codigo;

            echo json_encode(['exito'=>true, 'codigo'=>$codigo]);
        } catch (Exception $e) {
            db()->rollBack();
            echo json_encode(['exito'=>false, 'error'=>'Error al procesar el pedido. Contacta con soporte.']);
        }
        break;

    default:
        echo json_encode(['exito'=>false,'error'=>'Acción desconocida']);
}
