<?php
require_once __DIR__ . '/../includes/auth.php';
cerrarSesion();
header('Location: /?logout=1');
exit;
