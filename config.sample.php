<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', true, 403);
    exit;
}

function wz_env($key, $default = '')
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

$appUrl = rtrim(wz_env('APP_URL'), '/');
if ($appUrl !== '') {
    define('APP_URL', $appUrl);
} else {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define('APP_URL', $protocol . $host . $baseDir);
}

$_app_stage = wz_env('APP_STAGE', '');
if ($_app_stage === '') {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_app_stage = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) ? 'Dev' : 'Live';
}

$app_key = wz_env('APP_KEY', '');
$cron_token = wz_env('CRON_TOKEN', '');
$health_token = wz_env('HEALTH_TOKEN', '');
$api_secret_env = wz_env('API_SECRET', '');

$db_host = wz_env('DB_HOST', 'localhost');
$db_port = wz_env('DB_PORT', '');
$db_user = wz_env('DB_USERNAME', wz_env('DB_USER', 'root'));
$db_pass = wz_env('DB_PASSWORD', wz_env('DB_PASS', ''));
$db_name = wz_env('DB_DATABASE', wz_env('DB_NAME', 'wifizones'));
$db_password = $db_pass;

$radius_host = wz_env('RADIUS_HOST', $db_host);
$radius_user = wz_env('RADIUS_USERNAME', $db_user);
$radius_pass = wz_env('RADIUS_PASSWORD', $db_pass);
$radius_name = wz_env('RADIUS_DATABASE', $db_name);
$radius_password = $radius_pass;

if ($_app_stage != 'Live') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
