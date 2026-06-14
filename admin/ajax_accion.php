<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requiereAdmin();

header('Content-Type: application/json');
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$id     = (int)($data['id'] ?? 0);

if (!validarCSRF($data['csrf'] ?? '')) { echo json_encode(['ok'=>false]); exit; }

switch ($action) {
    case 'marcar_leido':
        dbExecute('UPDATE contacto SET leido=1 WHERE id=?', [$id]);
        echo json_encode(['ok'=>true]);
        break;
    case 'marcar_respondido':
        dbExecute('UPDATE contacto SET leido=1, respondido=1 WHERE id=?', [$id]);
        echo json_encode(['ok'=>true]);
        break;
    default:
        echo json_encode(['ok'=>false]);
}
