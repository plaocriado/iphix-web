<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$usuarios = [
    ['admin@iphix.es',    'CAMBIA_ESTA_CONTRASEÑA'],
    ['maria@email.com',   'CAMBIA_ESTA_CONTRASEÑA'],
    ['carlos@email.com',  'CAMBIA_ESTA_CONTRASEÑA'],
];

echo "=== iphix — Generador de hashes de contraseñas ===\n\n";

foreach ($usuarios as [$email, $password]) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $rows = dbExecute('UPDATE usuarios SET password = ? WHERE email = ?', [$hash, $email]);
    if ($rows > 0) {
        echo "Contraseña actualizada para: $email\n";
    } else {
        echo "Usuario no encontrado: $email\n";
    }
}

echo "\nProceso completado.\n";
