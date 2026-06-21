<?php

header('Content-Type: application/json');

$checks = ['app' => true, 'database' => false, 'cron' => false, 'writable_uploads' => false];

try {
    require_once __DIR__ . '/init.php';
    ORM::for_table('tbl_appconfig')->find_one();
    $checks['database'] = true;
    global $UPLOAD_PATH;
    $checks['writable_uploads'] = is_writable($UPLOAD_PATH);
    $cronFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
    if (is_file($cronFile)) {
        $mtime = filemtime($cronFile);
        $checks['cron'] = (time() - $mtime) < 900;
        $checks['cron_last_run'] = date('c', $mtime);
    }
} catch (Throwable $e) {
    $checks['error'] = 'init_failed';
}

$ok = $checks['app'] && $checks['writable_uploads'];
$healthy = $ok && $checks['database'];
http_response_code($ok ? 200 : 503);
echo json_encode([
    'status' => $healthy ? 'ok' : ($ok ? 'degraded' : 'error'),
    'time' => date('c'),
    'checks' => $checks,
]);
