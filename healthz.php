<?php

/**
 * Render / load-balancer health probe.
 * Must stay lightweight: do not require full init.php (DB boot can fail during deploy).
 */
header('Content-Type: application/json');

$root = __DIR__;
$uploadPath = $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'uploads';

$checks = [
    'app' => is_file($root . '/init.php') && is_file($root . '/index.php'),
    'config' => is_file($root . '/config.php') || is_file($root . '/config.sample.php'),
    'writable_uploads' => is_dir($uploadPath) && is_writable($uploadPath),
    'database' => false,
    'cron' => false,
];

if ($checks['writable_uploads']) {
    $cronFile = $uploadPath . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
    if (is_file($cronFile)) {
        $mtime = filemtime($cronFile);
        $checks['cron'] = (time() - $mtime) < 900;
        $checks['cron_last_run'] = date('c', $mtime);
    }
}

if ($checks['app'] && $checks['config']) {
    try {
        if (!function_exists('wz_env')) {
            if (is_file($root . '/config.php')) {
                require_once $root . '/config.php';
            }
            if (!isset($db_host) && is_file($root . '/config.sample.php')) {
                require_once $root . '/config.sample.php';
            }
        }

        $dbHost = $db_host ?? getenv('DB_HOST');
        $dbName = $db_name ?? getenv('DB_DATABASE') ?: getenv('DB_NAME');
        $dbUser = $db_user ?? getenv('DB_USERNAME') ?: getenv('DB_USER');
        $dbPass = $db_pass ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS');

        if ($dbHost && $dbName && $dbUser !== false && $dbUser !== '') {
            $port = getenv('DB_PORT') ?: '3306';
            $dsn = "mysql:host={$dbHost};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, (string) $dbUser, (string) ($dbPass ?: ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo->query('SELECT 1');
            $checks['database'] = true;
        }
    } catch (Throwable $e) {
        $checks['database_error'] = 'unavailable';
    }
}

$ok = $checks['app'] && $checks['writable_uploads'];
$healthy = $ok && $checks['database'];

http_response_code($ok ? 200 : 503);
echo json_encode([
    'status' => $healthy ? 'ok' : ($ok ? 'degraded' : 'error'),
    'time' => date('c'),
    'checks' => $checks,
]);
