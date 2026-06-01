<?php

/**
 * Auto-test WifiZone modules (CLI, sans base si indisponible)
 */
$root = dirname(__DIR__);
$_app_stage = 'Dev';
$errors = [];

try {
    require_once $root . '/init.php';
} catch (Throwable $e) {
    echo "INIT FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

$classes = [
    'WifiZoneCore', 'WifiZoneLogger', 'WifiZoneHotspot', 'WifiZoneCache',
    'WifiZoneAudit', 'WifiZonePayment', 'WifiZoneApi', 'WifiZoneNotify',
    'WifiZoneRadius', 'WifiZoneBackup', 'WifiZoneWallet', 'Csrf',
];

foreach ($classes as $c) {
    if (!class_exists($c)) {
        $errors[] = "Missing class $c";
    }
}

if (!function_exists('wifizone_verify_csrf')) {
    $errors[] = 'Missing wifizone_verify_csrf()';
}
if (!function_exists('csrf_field')) {
    $errors[] = 'Missing csrf_field()';
}
if (!function_exists('wifizone_ensure_kpi_widget')) {
    $errors[] = 'Missing wifizone_ensure_kpi_widget()';
}

try {
    $token = WifiZoneApi::issueJwt(1);
    if (!WifiZoneApi::verifyJwt($token)) {
        $errors[] = 'JWT verify failed';
    }
} catch (Throwable $e) {
    $errors[] = 'JWT: ' . $e->getMessage();
}

$files = [
    'system/plugin/wifizone.php',
    'system/widgets/wifizone_kpi.php',
    'system/cron_wifizone.php',
    'system/wifizone_api.php',
    'health.php',
    'docker-compose.yml',
    'system/lan/french.json',
];

foreach ($files as $f) {
    if (!file_exists($root . '/' . $f)) {
        $errors[] = "Missing file $f";
    }
}

foreach (glob($root . '/system/autoload/WifiZone*.php') as $f) {
    $out = shell_exec('php -l ' . escapeshellarg($f) . ' 2>&1');
    if (strpos($out, 'No syntax errors') === false) {
        $errors[] = "Syntax: $f — $out";
    }
}

if ($errors) {
    echo "FAILED:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

echo "WifiZone self-test OK — " . count($classes) . " classes, " . date('c') . "\n";
exit(0);
