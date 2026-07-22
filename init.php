<?php

/**
 *  wifizones — Hotspot & MikroTik billing
 **/

// Polyfills for PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || strpos($haystack, $needle) !== false;
    }
}

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

if (!function_exists('wifizone_bootstrap_long_request_limits')) {
    function wifizone_bootstrap_long_request_limits()
    {
        static $applied = false;
        if ($applied || php_sapi_name() === 'cli') {
            return;
        }
        $applied = true;

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $route = strtolower(trim((string) ($_GET['_route'] ?? $_POST['_route'] ?? '')));
        $isDev = (getenv('APP_STAGE') ?: '') === 'Dev';
        $longRequest = preg_match('#(?:^|/)(settings/(hotspot|pppoe-setup)|services/|routers/(add-post|edit-post|test-connection))#', $route) === 1
            || str_contains($uri, 'pppoe-setup')
            || str_contains($uri, 'settings/hotspot')
            || str_contains($uri, 'services/sync')
            || str_contains($uri, 'routers/add-post')
            || str_contains($uri, 'routers/edit-post')
            || str_contains($uri, 'routers/test-connection')
            || !empty($_GET['fetch_router_setup'])
            || !empty($_POST['ajax_deploy'])
            || !empty($_POST['send_mikrotik'])
            || !empty($_POST['sync_hotspot_plans'])
            || ($isDev && (str_contains($uri, 'settings/') || str_contains($uri, 'services/') || str_contains($uri, 'routers/')));

        if (!$longRequest) {
            return;
        }

        @ini_set('max_execution_time', '600');
        @set_time_limit(600);
        @ini_set('default_socket_timeout', '120');
        @ignore_user_abort(true);
    }
}
wifizone_bootstrap_long_request_limits();

$root_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
if (!isset($isApi)) {
    $isApi = false;
}
// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    global $root_path;
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}
spl_autoload_register('_autoloader');

if (!file_exists($root_path . 'config.php')) {
    $root_path .= '..' . DIRECTORY_SEPARATOR;
    if (!file_exists($root_path . 'config.php')) {
        r2('./install');
    }
}

if (!file_exists($root_path .  File::pathFixer('system/orm.php'))) {
    echo $root_path . "orm.php file not found";
    die();
}

$DEVICE_PATH = $root_path . File::pathFixer('system/devices');
$UPLOAD_PATH = $root_path . File::pathFixer('system/uploads');
$CACHE_PATH = $root_path . File::pathFixer('system/cache');
$PAGES_PATH = $root_path . File::pathFixer('pages');
$PLUGIN_PATH = $root_path . File::pathFixer('system/plugin');
$WIDGET_PATH = $root_path . File::pathFixer('system/widgets');
$PAYMENTGATEWAY_PATH = $root_path . File::pathFixer('system/paymentgateway');
$UI_PATH = 'ui';

if (!file_exists($UPLOAD_PATH . File::pathFixer('/notifications.default.json'))) {
    echo $UPLOAD_PATH . File::pathFixer("/notifications.default.json file not found");
    die();
}

require_once $root_path . 'config.php';

if (!defined('APP_URL') || !isset($db_host, $db_user, $db_name, $db_pass, $_app_stage)) {
    $sampleConfig = $root_path . 'config.sample.php';
    if (file_exists($sampleConfig)) {
        require_once $sampleConfig;
    }
}

$db_password = $db_password ?? null;
$db_pass = $db_pass ?? ($db_password ?? '');
$db_host = $db_host ?? (getenv('DB_HOST') ?: 'localhost');
$db_user = $db_user ?? (getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root'));
$db_name = $db_name ?? (getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'wifizones'));
if ($db_pass === null || $db_pass === '') {
    $envPass = getenv('DB_PASSWORD');
    if ($envPass === false || $envPass === '') {
        $envPass = getenv('DB_PASS');
    }
    $db_pass = ($db_password ?? null) ?: ($envPass !== false ? $envPass : '');
}
$_app_stage = $_app_stage ?? (getenv('APP_STAGE') ?: 'Live');

if (!defined('APP_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    define('APP_URL', $protocol . $host . $baseDir);
}

if ($_app_stage != 'Live' && $_app_stage != 'Demo' && $_app_stage != 'demo') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} elseif ($_app_stage == 'Live') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

if ($db_password != null && ($db_pass == null || $db_pass === '')) {
    $db_pass = $db_password;
}
if ($db_pass != null && $db_pass !== '') {
    $db_password = $db_pass;
}

// FreeRADIUS : même MySQL que l'app si config.php legacy ne définit pas radius_*
$radius_host = $radius_host ?? $db_host;
$radius_user = $radius_user ?? $db_user;
$radius_pass = $radius_pass ?? $db_pass;
$radius_name = $radius_name ?? $db_name;
$radius_password = $radius_password ?? $radius_pass;

require_once $root_path . File::pathFixer('system/orm.php');

// Suppress PHP 8.x deprecation warnings for PEAR2 library
$_pear2_prev_err_level = error_reporting();
error_reporting($_pear2_prev_err_level & ~E_DEPRECATED);
require_once $root_path . File::pathFixer('system/autoload/PEAR2/Autoload.php');
error_reporting($_pear2_prev_err_level);

include $root_path . File::pathFixer('system/autoload/Hookers.php');
ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_pass);
ORM::configure('return_result_sets', true);
if ($_app_stage != 'Live') {
    ORM::configure('logging', true);
}
if ($isApi) {
    define('U', APP_URL . '/system/api.php?r=');
} else {
    define('U', APP_URL . '/?_route=');
}

// notification message
if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . "notifications.json")) {
    $_notifmsg = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.json'), true);
}
$_notifmsg_default = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true);

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}

if(empty($config['dashboard_Admin'])){
    $config['dashboard_Admin'] = "12.7,5.12";
}

if(empty($config['dashboard_Agent'])){
    $config['dashboard_Agent'] = "12.7,5.12";
}

if(empty($config['dashboard_Sales'])){
    $config['dashboard_Sales'] = "12.7,5.12";
}

if(empty($config['dashboard_Customer'])){
    $config['dashboard_Customer'] = "6,6";
}


$_c =  $config;

if (!defined('WIFIZONES_APP_NAME')) {
    define('WIFIZONES_APP_NAME', 'wifizones');
}
if (empty($http_proxy) && !empty($config['http_proxy'])) {
    $http_proxy = $config['http_proxy'];
    if (empty($http_proxyauth) && !empty($config['http_proxyauth'])) {
        $http_proxyauth = $config['http_proxyauth'];
    }
}
date_default_timezone_set($config['timezone']);

WifiZoneCore::boot();
WifiZoneSecurity::bootstrap($config);
$_c = $config;
try {
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'user_id'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN user_id INT NULL");
    }
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'action'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN action VARCHAR(100) NULL");
    }
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'details'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN details TEXT NULL");
    }
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'ip_address'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN ip_address VARCHAR(100) NULL");
    }
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'created_at'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN created_at DATETIME NULL");
    }
    $logColumns = ORM::for_table('tbl_logs')->raw_query("SHOW COLUMNS FROM tbl_logs LIKE 'updated_at'")->find_one();
    if (!$logColumns) {
        ORM::raw_execute("ALTER TABLE tbl_logs ADD COLUMN updated_at DATETIME NULL");
    }
    ORM::raw_execute("ALTER TABLE tbl_logs MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT ''");
    ORM::raw_execute("ALTER TABLE tbl_logs MODIFY COLUMN description MEDIUMTEXT NULL");
    ORM::raw_execute("ALTER TABLE tbl_logs MODIFY COLUMN userid INT NOT NULL DEFAULT 0");
    ORM::raw_execute("ALTER TABLE tbl_logs MODIFY COLUMN ip MEDIUMTEXT NULL");
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_print_temp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        print_id VARCHAR(100) NULL,
        token VARCHAR(100) NULL,
        code VARCHAR(100) NULL,
        username VARCHAR(100) NULL,
        password VARCHAR(100) NULL,
        plan_id INT NULL,
        plan_name VARCHAR(150) NULL,
        price DECIMAL(15,2) DEFAULT 0,
        data_limit VARCHAR(100) NULL,
        validity VARCHAR(100) NULL,
        router VARCHAR(150) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $printColumns = ORM::for_table('tbl_print_temp')->raw_query("SHOW COLUMNS FROM tbl_print_temp LIKE 'print_id'")->find_one();
    if (!$printColumns) {
        ORM::raw_execute("ALTER TABLE tbl_print_temp ADD COLUMN print_id VARCHAR(100) NULL");
    }
    $printColumns = ORM::for_table('tbl_print_temp')->raw_query("SHOW COLUMNS FROM tbl_print_temp LIKE 'token'")->find_one();
    if (!$printColumns) {
        ORM::raw_execute("ALTER TABLE tbl_print_temp ADD COLUMN token VARCHAR(100) NULL");
    }
    $printSeed = ORM::for_table('tbl_print_temp')->where('print_id', '')->find_one();
    if (!$printSeed) {
        ORM::raw_execute("INSERT INTO tbl_print_temp (print_id, token, created_at) VALUES ('', 'READY', NOW())");
    }
} catch (Throwable $e) {
}
function wifizone_extend_sync_time_limit()
{
    wifizone_bootstrap_long_request_limits();
}
wifizone_extend_sync_time_limit();
wifizone_register_activity_logging();
wifizone_cleanup_ghost_activity_logs();
WifiZoneLogger::loadPlugins($PLUGIN_PATH);
if (function_exists('wifizone_ensure_kpi_widget')) {
    wifizone_ensure_kpi_widget();
}
if (function_exists('wifizone_ensure_setup_widget')) {
    wifizone_ensure_setup_widget();
}

if ((!empty($radius_user) && $config['radius_enable']) || _post('radius_enable') || _post('hotspot_use_radius')) {
    if (!empty($radius_password)) {
        // compability for old version
        $radius_pass = $radius_password;
    }
    ORM::configure("mysql:host=$radius_host;dbname=$radius_name", null, 'radius');
    ORM::configure('username', $radius_user, 'radius');
    ORM::configure('password', $radius_pass, 'radius');
    ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'), 'radius');
    ORM::configure('return_result_sets', true, 'radius');
}

/**
 * Connexion ORM FreeRADIUS (NAS sync hotspot) même si radius_enable venait d'être activé dans la même requête.
 */
function wifizone_ensure_radius_orm()
{
    global $radius_host, $radius_user, $radius_pass, $radius_name, $radius_password, $db_host, $db_user, $db_pass, $db_name;
    if (empty($radius_host)) {
        $radius_host = $db_host ?? 'localhost';
    }
    if (empty($radius_user)) {
        $radius_user = $db_user ?? 'root';
    }
    if (empty($radius_name)) {
        $radius_name = $db_name ?? 'wifizones';
    }
    if (empty($radius_pass) && !empty($radius_password)) {
        $radius_pass = $radius_password;
    }
    if (empty($radius_pass) && isset($db_pass)) {
        $radius_pass = $db_pass;
    }
    if (empty($radius_user) || empty($radius_host) || empty($radius_name)) {
        return false;
    }
    if (!empty($radius_password)) {
        $radius_pass = $radius_password;
    }
    try {
        ORM::configure("mysql:host=$radius_host;dbname=$radius_name", null, 'radius');
        ORM::configure('username', $radius_user, 'radius');
        ORM::configure('password', $radius_pass, 'radius');
        ORM::configure('driver_options', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], 'radius');
        ORM::configure('return_result_sets', true, 'radius');
        ORM::for_table('nas', 'radius')->limit(1)->find_one();

        return true;
    } catch (Throwable $e) {
        return false;
    } catch (Exception $e) {
        return false;
    }
}


// Language: user choice (session/cookie) overrides site default (english)
if (!empty($_SESSION['user_language']) && wifizone_is_allowed_language($_SESSION['user_language'])) {
    $config['language'] = $_SESSION['user_language'];
} elseif (!empty($_COOKIE['user_language']) && wifizone_is_allowed_language($_COOKIE['user_language'])) {
    $config['language'] = $_COOKIE['user_language'];
} elseif (User::getID() > 0) {
    $lang = User::getAttribute('Language');
    if (!empty($lang) && wifizone_is_allowed_language($lang)) {
        $config['language'] = $lang;
    }
}

if (empty($config['language']) || !wifizone_is_allowed_language($config['language'])) {
    $config['language'] = 'english';
}

if (empty($_SESSION['user_language'])) {
    $_SESSION['user_language'] = $config['language'];
}
$lan_file = $root_path . File::pathFixer('system/lan/' . $config['language'] . '.json');
if (file_exists($lan_file)) {
    $_L = json_decode(file_get_contents($lan_file), true);
} else {
    $_L = ['author' => 'Auto Generated by wifizones Script'];
    file_put_contents($lan_file, json_encode($_L));
}

function safedata($value)
{
    if (is_array($value)) {
        return array_map('safedata', $value);
    }
    if (is_string($value)) {
        return trim($value);
    }
    return $value;
}

function _post($param, $defvalue = '')
{
    if (!isset($_POST[$param])) {
        return $defvalue;
    } else {
        return safedata($_POST[$param]);
    }
}

function _get($param, $defvalue = '')
{
    if (!isset($_GET[$param])) {
        return $defvalue;
    } else {
        return safedata($_GET[$param]);
    }
}

function _req($param, $defvalue = '')
{
    if (!isset($_REQUEST[$param])) {
        return $defvalue;
    } else {
        return safedata($_REQUEST[$param]);
    }
}


function _auth($login = true)
{
    if (User::getID()) {
        return true;
    } else {
        if ($login) {
            r2(getUrl('login'));
        } else {
            return false;
        }
    }
}

function _admin($login = true)
{
    if (Admin::getID()) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            wifizone_verify_csrf();
        }
        return true;
    } else {
        if ($login) {
            r2(getUrl('login'));
        } else {
            return false;
        }
    }
}

function wifizone_verify_csrf()
{
    global $config, $isApi, $routes;
    if (!empty($isApi)) {
        return;
    }
    if (($config['csrf_enabled'] ?? 'yes') !== 'yes') {
        return;
    }
    $handler = $routes[0] ?? '';
    $action = $routes[1] ?? '';
    $publicPlugins = [
        'hotspot_login', 'hotspot_pay', 'hotspot_verify', 'hotspot_pg_campay_verify',
        'pppoe_portal', 'pppoe_plan', 'pppoe_pay', 'pppoe_verify',
        'wifizone_reseller_api', 'hotspot_resellers_login',
    ];
    if ($handler === 'plugin' && in_array($action, $publicPlugins, true)) {
        return;
    }
    if ($handler === 'home' || $handler === 'login' || $handler === 'provision' || $handler === 'ref') {
        return;
    }
    // AJAX router test: session auth is enough; HTML redirect breaks JSON parsing
    if ($handler === 'routers' && $action === 'test-connection') {
        return;
    }
    $token = _post('csrf_token') ?: _req('csrf_token');
    if ($token === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = trim($_SERVER['HTTP_X_CSRF_TOKEN']);
    }
    if ($token === '') {
        if (wifizone_json_response_requested()) {
            wifizone_json_error(Lang::T('Invalid or Expired CSRF Token') . '.', 403);
        }
        r2(getUrl('dashboard'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
    if (!Csrf::check($token)) {
        if (wifizone_json_response_requested()) {
            wifizone_json_error(Lang::T('Token has expired. Please log in again.'), 403);
        }
        r2(getUrl('dashboard'), 'e', Lang::T('Token has expired. Please log in again.'));
    }
}

function wifizone_json_response_requested()
{
    global $routes;
    $handler = $routes[0] ?? '';
    $action = $routes[1] ?? '';
    if ($handler === 'routers' && $action === 'test-connection') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'application/json') !== false;
}

function wifizone_json_error($message, $httpCode = 400)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

/**
 * Expired PPPoE clients hitting any HTTP(S) page via MikroTik NAT are routed to the portal.
 */
function wifizone_pppoe_expired_pool_prefix()
{
    global $config;
    $gateway = trim((string) ($config['pppoe_setup_gateway'] ?? '10.10.10.1'));
    if (preg_match('/^(\d+\.\d+\.\d+\.)/', $gateway, $matches)) {
        return $matches[1];
    }

    return '10.10.10.';
}

function wifizone_pppoe_portal_route_for_ip($clientIp)
{
    $customerId = 0;
    $router = trim((string) ($_GET['router'] ?? $_GET['routername'] ?? ''));
    $login = trim((string) ($_GET['user'] ?? $_GET['pppoe_username'] ?? ''));

    $fieldRow = ORM::for_table('tbl_customers_fields')
        ->where('field_name', 'pppoe_expired_ip')
        ->where('field_value', $clientIp)
        ->find_one();
    if ($fieldRow) {
        $customerId = (int) $fieldRow['customer_id'];
    }

    if ($customerId > 0) {
        if ($login === '') {
            $login = User::getAttribute('pppoe_expired_user', $customerId, '');
        }
        if ($router === '') {
            $router = User::getAttribute('pppoe_expired_router', $customerId, '');
        }
        $customer = ORM::for_table('tbl_customers')->find_one($customerId);
        if ($customer) {
            if ($login === '') {
                $login = (string) ($customer['pppoe_username'] ?: $customer['username']);
            }
            if ($router === '') {
                $lastRecharge = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customerId)
                    ->where_raw("LOWER(type) = 'pppoe'")
                    ->order_by_desc('id')
                    ->find_one();
                if ($lastRecharge) {
                    $router = (string) $lastRecharge['routers'];
                }
            }
        }
    }

    if ($router === '' && $clientIp !== '') {
        $lastExpired = ORM::for_table('tbl_user_recharges')
            ->where_raw("LOWER(type) = 'pppoe'")
            ->where_raw("(status = 'off' OR CONCAT(expiration, ' ', time) <= NOW())")
            ->order_by_desc('id')
            ->find_one();
        if ($lastExpired) {
            $router = (string) $lastExpired['routers'];
            if ($login === '') {
                $login = (string) $lastExpired['username'];
            }
        }
    }

    if ($router === '') {
        return null;
    }

    $_GET['router'] = $router;
    $_GET['routername'] = $router;
    $_GET['user'] = $login;
    $_GET['pppoe_username'] = $login;
    $_REQUEST['router'] = $router;
    $_REQUEST['user'] = $login;

    return 'plugin/pppoe_portal';
}

/**
 * Requête HTTP redirigée par NAT MikroTik (SNAT masque l'IP client → IP routeur 10.0.0.x).
 */
function wifizone_pppoe_captive_snat_redirect_request($remoteIp)
{
    global $config;
    if (!preg_match('/^10\.0\.0\.\d+$/', (string) $remoteIp)) {
        return false;
    }
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        return false;
    }
    if (strpos($host, ':') !== false) {
        $host = (string) parse_url('http://' . $host, PHP_URL_HOST);
    }
    $backendUrl = class_exists('Mikrotik')
        ? Mikrotik::resolvePppoeCaptiveBackendUrl(is_array($config) ? $config : [])
        : '';
    if ($backendUrl === '' && defined('APP_URL')) {
        $backendUrl = (string) APP_URL;
    }
    $backendHost = strtolower((string) parse_url($backendUrl, PHP_URL_HOST));
    if ($backendHost === '') {
        return true;
    }
    if ($host === $backendHost) {
        return false;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return $host !== $backendHost;
    }

    return true;
}

/**
 * Passerelle hotspot (ex. 10.10.0.1) — distincte du pool PPPoE expiré (10.10.10.x).
 */
function wifizone_hotspot_gateway_ip()
{
    global $config;
    $gateway = trim((string) ($config['hotspot_local_address'] ?? '10.10.0.1'));
    if (strpos($gateway, '/') !== false) {
        $gateway = (string) explode('/', $gateway)[0];
    }

    return $gateway;
}

function wifizone_is_hotspot_pool_ip($ip)
{
    $ip = trim((string) $ip);
    if (!preg_match('/^10\.10\.0\.\d+$/', $ip)) {
        return false;
    }

    return $ip !== wifizone_hotspot_gateway_ip();
}

function wifizone_request_targets_hotspot_gateway()
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        return false;
    }
    if (strpos($host, ':') !== false) {
        $host = (string) parse_url('http://' . $host, PHP_URL_HOST);
    }

    return $host === strtolower(wifizone_hotspot_gateway_ip());
}

function wifizone_hotspot_captive_should_intercept($currentRoute)
{
    if (php_sapi_name() === 'cli') {
        return false;
    }
    if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', 'OPTIONS') === 0) {
        return false;
    }

    $currentRoute = trim((string) $currentRoute);
    if ($currentRoute !== '' && stripos($currentRoute, 'plugin/') === 0) {
        $action = explode('/', $currentRoute, 2)[1] ?? '';
        if ($action !== 'hotspot_portal' && $action !== 'hotspot_mikrotik_auth') {
            return false;
        }
    }

    $uriPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    if (preg_match('#^/(ui/|favicon\.|robots\.txt)#i', $uriPath)) {
        return false;
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return false;
    }

    $remoteIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (preg_match('/^10\.10\.0\.\d+$/', $remoteIp) && $remoteIp !== wifizone_hotspot_gateway_ip()) {
        return true;
    }
    if (wifizone_request_targets_hotspot_gateway()) {
        return true;
    }
    if (preg_match('/^10\.0\.0\.\d+$/', $remoteIp)) {
        return wifizone_pppoe_captive_snat_redirect_request($remoteIp);
    }

    return false;
}

function wifizone_hotspot_captive_intercept($currentRoute)
{
    if (!wifizone_hotspot_captive_should_intercept($currentRoute)) {
        return $currentRoute;
    }

    $currentRoute = trim((string) $currentRoute);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $uriPath = ltrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'), '/');

    if ($method === 'POST' && ($uriPath === 'login' || stripos($currentRoute, 'hotspot_mikrotik_auth') === 0)) {
        return 'plugin/hotspot_mikrotik_auth';
    }

    if ($method === 'GET') {
        return 'plugin/hotspot_portal';
    }

    return $currentRoute;
}

function wifizone_pppoe_captive_intercept($currentRoute)
{
    if (php_sapi_name() === 'cli') {
        return $currentRoute;
    }
    $currentRoute = trim((string) $currentRoute);
    if ($currentRoute !== '' && stripos($currentRoute, 'plugin/pppoe') === 0) {
        return $currentRoute;
    }
    if ($currentRoute !== '' && stripos($currentRoute, 'plugin/hotspot') === 0) {
        return $currentRoute;
    }
    if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? 'GET', 'OPTIONS') === 0) {
        return $currentRoute;
    }

    $clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($clientIp === '' || !filter_var($clientIp, FILTER_VALIDATE_IP)) {
        return $currentRoute;
    }

    if (wifizone_is_hotspot_pool_ip($clientIp) || wifizone_request_targets_hotspot_gateway()) {
        return $currentRoute;
    }

    try {
        if (wifizone_pppoe_captive_snat_redirect_request($clientIp)) {
            if (wifizone_request_targets_hotspot_gateway()) {
                return $currentRoute;
            }
            $portalRoute = wifizone_pppoe_portal_route_for_ip('');
            if ($portalRoute !== null) {
                return $portalRoute;
            }
        }

        $fieldRow = ORM::for_table('tbl_customers_fields')
            ->where('field_name', 'pppoe_expired_ip')
            ->where('field_value', $clientIp)
            ->find_one();
        if ($fieldRow) {
            $customerId = (int) $fieldRow['customer_id'];
            $router = User::getAttribute('pppoe_expired_router', $customerId, '');
            $login = User::getAttribute('pppoe_expired_user', $customerId, '');
            if ($router === '' || $login === '') {
                $customer = ORM::for_table('tbl_customers')->find_one($customerId);
                if ($customer) {
                    if ($login === '') {
                        $login = (string) ($customer['pppoe_username'] ?: $customer['username']);
                    }
                    if ($router === '') {
                        $lastRecharge = ORM::for_table('tbl_user_recharges')
                            ->where('customer_id', $customerId)
                            ->where_raw("LOWER(type) = 'pppoe'")
                            ->order_by_desc('id')
                            ->find_one();
                        if ($lastRecharge) {
                            $router = (string) $lastRecharge['routers'];
                        }
                    }
                }
            }
            if ($router !== '' && $login !== '') {
                $_GET['router'] = $router;
                $_GET['routername'] = $router;
                $_GET['user'] = $login;
                $_GET['pppoe_username'] = $login;
                $_REQUEST['router'] = $router;
                $_REQUEST['user'] = $login;

                return 'plugin/pppoe_portal';
            }
        }

        $row = ORM::for_table('tbl_user_recharges')
            ->where('type', 'PPPOE')
            ->where_raw("(status = 'off' OR CONCAT(expiration, ' ', time) <= NOW())")
            ->where_raw(
                "customer_id IN (SELECT customer_id FROM tbl_customers_fields WHERE field_name = 'pppoe_expired_ip' AND field_value = ?)",
                [$clientIp]
            )
            ->order_by_desc('id')
            ->find_one();
        if (!$row) {
            if (strpos($clientIp, wifizone_pppoe_expired_pool_prefix()) === 0) {
                $portalRoute = wifizone_pppoe_portal_route_for_ip($clientIp);
                if ($portalRoute !== null) {
                    return $portalRoute;
                }
            }
            return $currentRoute;
        }

        $customerId = (int) $row['customer_id'];
        $router = User::getAttribute('pppoe_expired_router', $customerId, (string) $row['routers']);
        $login = User::getAttribute('pppoe_expired_user', $customerId, (string) $row['username']);
        $_GET['router'] = $router;
        $_GET['routername'] = $router;
        $_GET['user'] = $login;
        $_GET['pppoe_username'] = $login;
        $_REQUEST['router'] = $router;
        $_REQUEST['user'] = $login;

        return 'plugin/pppoe_portal';
    } catch (Throwable $e) {
        return $currentRoute;
    }
}

/**
 * CORS for captive portal plugin routes (MikroTik login.html, file:// preview).
 */
function wifizone_hotspot_plugin_cors()
{
    if (headers_sent()) {
        return;
    }
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
    if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', 'OPTIONS') === 0) {
        http_response_code(204);
        exit;
    }
}

function csrf_field()
{
    if (!isset($_SESSION['csrf_token'])) {
        Csrf::generateAndStoreToken();
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

function wifizone_is_allowed_language($lang)
{
    return in_array($lang, ['english', 'french'], true);
}

/**
 * Switch UI language (english default, french optional).
 */
function wifizone_apply_language($lang)
{
    global $root_path, $config, $_L;
    if (!wifizone_is_allowed_language($lang)) {
        return false;
    }
    $_SESSION['user_language'] = $lang;
    $config['language'] = $lang;
    if (PHP_VERSION_ID >= 70300) {
        setcookie('user_language', $lang, [
            'expires' => time() + 86400 * 365,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie('user_language', $lang, time() + 86400 * 365, '/');
    }
    $lan_file = $root_path . File::pathFixer('system/lan/' . $lang . '.json');
    if (file_exists($lan_file)) {
        $_L = json_decode(file_get_contents($lan_file), true) ?: [];
    }
    if (User::getID() > 0) {
        User::setAttribute('Language', $lang);
    }
    return true;
}


function wifizone_activity_actor_id()
{
    if (!empty($_SESSION['impersonator']['superadmin_id'])) {
        return (int) $_SESSION['impersonator']['superadmin_id'];
    }
    if (!empty($_SESSION['aid'])) {
        return (int) $_SESSION['aid'];
    }
    if (!empty($_SESSION['uid'])) {
        return -1 * (int) $_SESSION['uid'];
    }
    return 0;
}

function wifizone_register_activity_logging()
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    set_error_handler(function ($errno, $errstr, $file, $line) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        $fatalOnly = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ];
        if (!in_array($errno, $fatalOnly, true)) {
            return false;
        }
        $types = [
            E_ERROR => 'Error',
            E_PARSE => 'Parse',
            E_CORE_ERROR => 'Core',
            E_USER_ERROR => 'UserError',
            E_RECOVERABLE_ERROR => 'Recoverable',
        ];
        $label = $types[$errno] ?? 'PHP';
        _log("[$label] $errstr @ $file:$line", 'Error', wifizone_activity_actor_id());
        return false;
    });

    set_exception_handler(function ($e) {
        _log(
            '[Exception] ' . WifiZoneSecurity::formatExceptionForLog($e),
            'Error',
            wifizone_activity_actor_id()
        );
    });

    register_shutdown_function(function () {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            if (stripos($e['message'], 'Cannot redeclare') !== false) {
                return;
            }
            _log(
                '[Fatal] ' . $e['message'] . ' @ ' . $e['file'] . ':' . $e['line'],
                'Error',
                wifizone_activity_actor_id()
            );
        }
    });
}

/**
 * Remove empty log rows created by accidental plugin bootstrap calls.
 */
function wifizone_cleanup_ghost_activity_logs()
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;
    try {
        ORM::raw_execute(
            "UPDATE tbl_logs SET
                description = TRIM(CONCAT(COALESCE(action, ''), CASE WHEN COALESCE(details, '') != '' THEN CONCAT(': ', details) ELSE '' END)),
                type = CASE WHEN type IS NULL OR TRIM(type) = '' THEN 'Hotspot' ELSE type END,
                date = COALESCE(NULLIF(TRIM(date), ''), created_at, NOW())
             WHERE (description IS NULL OR TRIM(description) = '')
               AND (action IS NOT NULL AND TRIM(action) != '')"
        );
        ORM::raw_execute(
            "DELETE FROM tbl_logs WHERE (description IS NULL OR TRIM(description) = '')
             AND (type IS NULL OR TRIM(type) = '')
             AND (action IS NULL OR TRIM(action) = '')
             AND (details IS NULL OR TRIM(details) = '')"
        );
        ORM::raw_execute(
            "DELETE FROM tbl_logs WHERE description LIKE '%VOUCHER_GENERATED%Qty: , Plan%'
             OR description LIKE '%BALANCE_UPDATE%Old: , New: , Change:%'
             OR description LIKE '%TOKEN_GENERATED%Qty: , Plan:%'
             OR details LIKE 'Qty: , Plan%'
             OR description LIKE '%Cannot redeclare function hotspot_%'"
        );
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * SQL fragment: hide noise from dashboard Activity Log widget.
 */
function wifizone_activity_log_widget_scope_sql()
{
    return "(type IS NULL OR type NOT IN ('Error'))
        AND (description IS NULL OR (
            description NOT LIKE '%VOUCHER_GENERATED%Qty: , Plan%'
            AND description NOT LIKE '%BALANCE_UPDATE%Old: , New: , Change:%'
            AND description NOT LIKE '%Cannot redeclare function hotspot_%'
        ))";
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function wifizone_normalize_log_display_row(array $row)
{
    $desc = trim((string) ($row['description'] ?? ''));
    if ($desc === '') {
        $action = trim((string) ($row['action'] ?? ''));
        $details = trim((string) ($row['details'] ?? ''));
        if ($action !== '') {
            $desc = $action . ($details !== '' ? ' — ' . $details : '');
        }
    }
    if ($desc === '') {
        $desc = Lang::T('System activity');
    }
    $date = trim((string) ($row['date'] ?? ''));
    if ($date === '' && !empty($row['created_at'])) {
        $date = $row['created_at'];
    }
    $row['description'] = $desc;
    $row['date'] = $date;
    return $row;
}

function wifizone_normalize_log_userid($userid)
{
    if ($userid === '' || $userid === '0' || $userid === 0 || $userid === null) {
        return wifizone_activity_actor_id();
    }
    if (is_string($userid) && preg_match('/^A:(\d+)$/i', $userid, $m)) {
        return (int) $m[1];
    }
    if (is_string($userid) && preg_match('/^C:(\d+)$/i', $userid, $m)) {
        return -1 * (int) $m[1];
    }
    return (int) $userid;
}

function _log($description, $type = '', $userid = '')
{
    try {
        wifizone_write_log_row($description, $type, $userid);
    } catch (Throwable $e) {
        error_log('wifizone _log failed: ' . $e->getMessage());
    }
}

function wifizone_write_log_row($description, $type = '', $userid = '')
{
    $userid = wifizone_normalize_log_userid($userid);
    $sessionId = session_id();
    $prefix = '';
    if ($sessionId) {
        $prefix = '[sid:' . substr($sessionId, 0, 12) . '] ';
    }
    if (!empty($_SESSION['impersonator']['target_label'])) {
        $prefix .= '[as:' . $_SESSION['impersonator']['target_label'] . '] ';
    }

    $d = ORM::for_table('tbl_logs')->create();
    $d->date = date('Y-m-d H:i:s');
    $d->type = $type !== '' ? $type : 'System';
    $d->description = $prefix . $description;
    $d->userid = $userid;
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))   //to check ip is pass from cloudflare tunnel
    {
        $d->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $d->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //to check ip from share internet
    {
        $d->ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER["REMOTE_ADDR"])) {
        $d->ip = $_SERVER["REMOTE_ADDR"];
    } else if (php_sapi_name() == 'cli') {
        $d->ip = 'CLI';
    } else {
        $d->ip = 'Unknown';
    }
    $d->save();
}

/**
 * Auto-log admin panel mutations (POST/DELETE and state-changing GET).
 */
function wifizone_log_admin_request($admin, $routes)
{
    if (empty($admin) || php_sapi_name() === 'cli') {
        return;
    }
    if (!empty($GLOBALS['wifizone_skip_request_log'])) {
        return;
    }

    $handler = $routes[0] ?? '';
    $action = $routes[1] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    $skipHandlers = ['autoload', 'autoload_user', 'cron', 'widgets'];
    if (in_array($handler, $skipHandlers, true)) {
        return;
    }

    if ($handler === 'dashboard' && $method === 'GET' && empty($_POST)) {
        return;
    }
    if ($handler === 'impersonate' && $action === 'search') {
        return;
    }
    if ($handler === 'logs' && in_array($action, ['list-csv', 'radius-csv', 'message-csv'], true)) {
        return;
    }

    $isMutation = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true);
    $isStateGet = ($method === 'GET' && preg_match('/delete|remove|trash|clear|enable|disable|toggle/i', $action . '/' . ($routes[2] ?? '')));
    if (!$isMutation && !$isStateGet) {
        return;
    }

    $routeLabel = trim($handler . '/' . $action . '/' . ($routes[2] ?? ''), '/');
    $userLabel = $admin['username'] ?? ('#' . ($admin['id'] ?? ''));
    $detail = '';
    if (!empty($_POST)) {
        $payload = $_POST;
        foreach (['password', 'password_confirm', 'pass', 'token', 'csrf_token', 'login_token'] as $secretKey) {
            if (isset($payload[$secretKey])) {
                $payload[$secretKey] = '***';
            }
        }
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false && $encoded !== '[]') {
            if (strlen($encoded) > 500) {
                $encoded = substr($encoded, 0, 500) . '…';
            }
            $detail = ' ' . $encoded;
        }
    }

    _log('[' . $method . '] ' . $routeLabel . ' — ' . $userLabel . $detail, 'Activity', wifizone_activity_actor_id());
}

function Lang($key)
{
    return Lang::T($key);
}

function alphanumeric($str, $tambahan = "")
{
    return Text::alphanumeric($str, $tambahan);
}

function showResult($success, $message = '', $result = [], $meta = [])
{
    header("Content-Type: Application/json");
    $json = json_encode(['success' => $success, 'message' => $message, 'result' => $result, 'meta' => $meta]);
    echo $json;
    die();
}

/**
 * make url canonical or standar
 */
function getUrl($url)
{
    return Text::url($url);
}

function generateUniqueNumericVouchers($totalVouchers, $length = 8)
{
    // Define characters allowed in the voucher code
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $vouchers = array();

    // Attempt to generate unique voucher codes
    for ($j = 0; $j < $totalVouchers; $j++) {
        do {
            $voucherCode = '';
            // Generate the voucher code
            for ($i = 0; $i < $length; $i++) {
                $voucherCode .= $characters[rand(0, $charactersLength - 1)];
            }
            // Check if the generated voucher code already exists in the array
            $isUnique = !in_array($voucherCode, $vouchers);
        } while (!$isUnique);

        $vouchers[] = $voucherCode;
    }

    return $vouchers;
}

function sendTelegram($txt)
{
    Message::sendTelegram($txt);
}

function sendSMS($phone, $txt)
{
    Message::sendSMS($phone, $txt);
}

function sendWhatsapp($phone, $txt)
{
    Message::sendWhatsapp($phone, $txt);
}

function r2($to, $ntype = 'e', $msg = '')
{
    global $isApi;
    if ($isApi) {
        showResult(
            ($ntype == 's') ? true : false,
            $msg
        );
    }
    if ($msg != '') {
        $_SESSION['ntype'] = $ntype;
        $_SESSION['notify'] = $msg;
    }
    if (!headers_sent()) {
        header('Location: ' . $to);
        exit;
    }
    $safeUrl = htmlspecialchars($to, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url='
        . $safeUrl
        . '"></head><body><script>location.replace('
        . json_encode($to)
        . ');</script><a href="'
        . $safeUrl
        . '">Continue</a></body></html>';
    exit;
}

function _alert($text, $type = 'success', $url = "home", $time = 3)
{
    global $ui, $isApi;
    if ($isApi) {
        showResult(
            ($type == 'success') ? true : false,
            $text
        );
    }
    if (!isset($ui)) return;
    if (strlen($url) > 4) {
        if (substr($url, 0, 4) != "http") {
            $url = getUrl($url);
        }
    } else {
        $url = getUrl($url);
    }
    $ui->assign('text', $text);
    $ui->assign('type', $type);
    $ui->assign('time', $time);
    $ui->assign('url', $url);
    $ui->display('admin/alert.tpl');
    die();
}


if (!isset($api_secret)) {
    $api_secret = WifiZoneSecurity::apiSecret();
}

function displayMaintenanceMessage(): void
{
    global $config, $ui;
    $date = $config['maintenance_date'];
    if ($date) {
        $ui->assign('date', $date);
    }
    http_response_code(503);
    $ui->assign('companyName', $config['CompanyName']);
    $ui->display('admin/maintenance.tpl');
    die();
}

function isTableExist($table)
{
    try {
        $record = ORM::forTable($table)->find_one();
        return $record !== false;
    } catch (Exception $e) {
        return false;
    }
}
