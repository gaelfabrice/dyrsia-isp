<?php
/**
 * Router for PHP built-in server (php -S … router.php).
 * Serves static files when present; otherwise forwards to index.php.
 */

if (php_sapi_name() !== 'cli') {
    $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
    $isDev = (getenv('APP_STAGE') ?: '') === 'Dev';
    $longRequest = !empty($_POST['ajax_deploy'])
        || !empty($_POST['send_mikrotik'])
        || !empty($_GET['fetch_router_setup'])
        || strpos($uri, 'pppoe-setup') !== false
        || strpos($uri, 'settings/hotspot') !== false
        || strpos($uri, 'services/sync') !== false
        || ($isDev && (strpos($uri, 'settings/') !== false || strpos($uri, 'services/') !== false));
    if ($longRequest) {
        @ini_set('max_execution_time', '600');
        @set_time_limit(600);
        @ini_set('default_socket_timeout', '120');
        @ignore_user_abort(true);
    }
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && $uri !== '' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Bare /index.php has no controller; treat as home.
if ($uri === '/index.php' && !isset($_GET['_route'])) {
    $_SERVER['REQUEST_URI'] = '/';
}

require __DIR__ . '/index.php';
