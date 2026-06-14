<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tickets.php';

requiereLogin();

$usuario = usuarioActual();
$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    if ($action === 'get_pregunta') {
        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        
        if (!$ticketId) {
            throw new Exception('Ticket inválido');
        }
        
        
        $ticket = dbQueryOne("SELECT id, usuario_id FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket || $ticket['usuario_id'] !== $usuario['id']) {
            throw new Exception('Acceso denegado');
        }
        
        $pregunta = obtenerSiguientePreguntaBot($ticketId);
        
        if ($pregunta) {
            $response['success'] = true;
            $response['pregunta'] = $pregunta;
            $response['total_pasos'] = (int)dbQueryOne(
                "SELECT COUNT(*) AS t FROM respuestas_bot WHERE ticket_id = ?", 
                [$ticketId]
            )['t'];
        } else {
            $response['success'] = true;
            $response['pregunta'] = null;
            $response['message'] = 'Todas las preguntas completadas';
        }
    }
    
    elseif ($action === 'get_historial') {
        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        
        if (!$ticketId) {
            throw new Exception('Ticket inválido');
        }
        
        $ticket = dbQueryOne("SELECT id, usuario_id FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket || $ticket['usuario_id'] !== $usuario['id']) {
            throw new Exception('Acceso denegado');
        }
        
        $respuestas = obtenerRespuestasBot($ticketId);
        
        $response['success'] = true;
        $response['respuestas'] = $respuestas;
    }
    
    elseif ($action === 'get_stats') {
        
        $stats = [];
        $estados = ['abierto', 'en_proceso', 'resuelto', 'cerrado'];
        
        foreach ($estados as $estado) {
            $count = (int)dbQueryOne(
                "SELECT COUNT(*) AS t FROM tickets WHERE usuario_id = ? AND estado = ?",
                [$usuario['id'], $estado]
            )['t'];
            $stats[$estado] = $count;
        }
        
        $response['success'] = true;
        $response['stats'] = $stats;
    }
    
    else {
        throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
