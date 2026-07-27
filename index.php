<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', '1');
}

session_start();

if (php_sapi_name() !== 'cli') {
    $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
    $route = strtolower(trim((string) ($_GET['_route'] ?? $_POST['_route'] ?? '')));
    $isDev = (getenv('APP_STAGE') ?: '') === 'Dev';
    $longRequest = preg_match('#(?:^|/)(settings/(hotspot|pppoe-setup)|services/)#', $route) === 1
        || strpos($uri, 'pppoe-setup') !== false
        || strpos($uri, 'settings/hotspot') !== false
        || strpos($uri, 'services/sync') !== false
        || !empty($_GET['fetch_router_setup'])
        || !empty($_POST['ajax_deploy'])
        || !empty($_POST['ajax_hotspot_deploy'])
        || !empty($_POST['send_mikrotik'])
        || !empty($_POST['sync_hotspot_plans'])
        || ($isDev && (strpos($uri, 'settings/') !== false || strpos($uri, 'services/') !== false));
    if ($longRequest) {
        @ini_set('max_execution_time', '600');
        @set_time_limit(600);
        @ini_set('default_socket_timeout', '120');
        @ignore_user_abort(true);
    }
}

if(isset($_GET['nux-mac']) && !empty($_GET['nux-mac'])){
    $_SESSION['nux-mac'] = $_GET['nux-mac'];
}

if(isset($_GET['nux-ip']) && !empty($_GET['nux-ip'])){
    $_SESSION['nux-ip'] = $_GET['nux-ip'];
}

if(isset($_GET['nux-router']) && !empty($_GET['nux-router'])){
    $_SESSION['nux-router'] = $_GET['nux-router'];
}

//get chap id and chap challenge
if(isset($_GET['nux-key']) && !empty($_GET['nux-key'])){
    $_SESSION['nux-key'] = $_GET['nux-key'];
}
//get mikrotik hostname
if(isset($_GET['nux-hostname']) && !empty($_GET['nux-hostname'])){
    $_SESSION['nux-hostname'] = $_GET['nux-hostname'];
}
require_once 'system/vendor/autoload.php';
require_once 'system/boot.php';
App::_run();
