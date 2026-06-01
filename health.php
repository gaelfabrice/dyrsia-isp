<?php

header('Content-Type: application/json');
require_once __DIR__ . '/init.php';
WifiZoneSecurity::requireServiceToken('health', true);
echo json_encode([
    'status' => 'ok',
    'time' => date('c'),
    'checks' => WifiZoneCore::healthCheck(),
]);
