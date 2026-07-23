<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

$action = $routes['1'] ?? '';

// JSON endpoint: reject unauthenticated before _admin() redirect returns HTML
if ($action === 'test-connection' && $_SERVER['REQUEST_METHOD'] === 'POST' && !Admin::getID()) {
    wifizone_json_error(Lang::T('Please sign in to access this page'), 401);
}

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');
$ui->assign('_admin', $admin);

// Connexion MikroTik (VPN/API) : éviter le Fatal "Maximum execution time of 15 seconds"
if (in_array($action, ['add-post', 'edit-post', 'test-connection', 'delete'], true)) {
    @ini_set('max_execution_time', '120');
    @set_time_limit(120);
    @ini_set('default_socket_timeout', '30');
}

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "MikrotikHotspot.php";

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

try {
    $db = ORM::getDb();
    $columns = $db->query("SHOW COLUMNS FROM tbl_routers LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        ORM::raw_execute("ALTER TABLE tbl_routers ADD COLUMN admin_id INT NULL DEFAULT NULL AFTER id");
        ORM::raw_execute("UPDATE tbl_routers SET admin_id = " . intval($admin['id']) . " WHERE admin_id IS NULL");
    }
} catch (Exception $e) {
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

function router_check_status($ip_address, $username, $password, &$errorDetail = null, $timeout = 15, $fallback = true)
{
    $oldTimeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', $timeout);
    $errorDetail = null;

    try {
        (new MikrotikHotspot())->getClient($ip_address, $username, $password, $timeout, $fallback, true);
        ini_set('default_socket_timeout', $oldTimeout);
        return 'Online';
    } catch (Throwable $e) {
        ini_set('default_socket_timeout', $oldTimeout);
        $errorDetail = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'class' => get_class($e),
        ];
        _log('[Router Connection Error] IP: ' . $ip_address . ' | User: ' . $username . ' | Error: ' . $e->getMessage(), 'Router', 0);
        return 'Offline';
    } catch (Exception $e) {
        ini_set('default_socket_timeout', $oldTimeout);
        $errorDetail = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'class' => get_class($e),
        ];
        _log('[Router Connection Error] IP: ' . $ip_address . ' | User: ' . $username . ' | Error: ' . $e->getMessage(), 'Router', 0);
        return 'Offline';
    }
}

function router_normalize_ip_port($ip_address, $api_port = '8728')
{
    $ip_address = trim($ip_address);
    $api_port = trim($api_port);
    if ($api_port == '') {
        $api_port = '8728';
    }
    if ($ip_address != '' && strpos($ip_address, ':') === false) {
        $ip_address .= ':' . $api_port;
    }
    return $ip_address;
}

function router_scoped_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if (!empty($admin['id'])) {
        $query->where('admin_id', (int) $admin['id']);
    }

    return $query;
}

function router_normalize_mac($mac)
{
    $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $mac));
    if (strlen($mac) !== 12) {
        return '';
    }

    return implode(':', str_split($mac, 2));
}

function router_validate_mac($mac)
{
    $normalized = router_normalize_mac($mac);
    if ($normalized === '') {
        return '';
    }
    if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $normalized)) {
        return '';
    }

    return $normalized;
}

function router_find_mac_conflict($macAddress, $excludeId = 0)
{
    $macAddress = router_validate_mac($macAddress);
    if ($macAddress === '') {
        return '';
    }

    $excludeId = (int) $excludeId;
    $query = ORM::for_table('tbl_routers')->where('description', $macAddress);
    if ($excludeId > 0) {
        $query->where_not_equal('id', $excludeId);
    }
    if ($query->find_one()) {
        return 'Cette adresse MAC est déjà enregistrée sur un autre compte.';
    }

    return '';
}

function router_fetch_mac_from_device($ip_address, $username, $password, $timeout = 8)
{
    try {
        $client = (new MikrotikHotspot())->getClient($ip_address, $username, $password, $timeout, false, true);
        if (!$client || !class_exists('Mikrotik')) {
            return '';
        }

        return Mikrotik::fetchRouterMacAddress($client);
    } catch (Throwable $e) {
        return '';
    } catch (Exception $e) {
        return '';
    }
}

function router_find_global_conflict($name, $ipAddress, $excludeId = 0)
{
    $excludeId = (int) $excludeId;
    $name = trim((string) $name);
    $ipAddress = trim((string) $ipAddress);

    if ($name !== '') {
        $query = ORM::for_table('tbl_routers')->where('name', $name);
        if ($excludeId > 0) {
            $query->where_not_equal('id', $excludeId);
        }
        if ($query->find_one()) {
            return 'Ce nom de routeur est déjà enregistré sur un autre compte.';
        }
    }

    if ($ipAddress !== '') {
        $query = ORM::for_table('tbl_routers')->where('ip_address', $ipAddress);
        if ($excludeId > 0) {
            $query->where_not_equal('id', $excludeId);
        }
        if ($query->find_one()) {
            return 'Cette adresse IP MikroTik est déjà enregistrée sur un autre compte.';
        }
    }

    return '';
}

switch ($action) {
    case 'add':
        if ($admin['user_type'] !== 'SuperAdmin') {
            $routerPermission = AdminSubscription::canAddRouter((int) $admin['id']);
            if (!$routerPermission['ok']) {
                r2(getUrl('routers/list'), 'e', $routerPermission['message']);
            }
        }
        run_hook('view_add_routers'); #HOOK
        $ui->display('admin/routers/add.tpl');
        break;

    case 'test-connection':
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);
        // Start output buffering to capture any stray output (deprecation warnings etc.)
        ob_start();
        
        // Suppress all errors/warnings from output - we'll handle them internally
        $originalErrorReporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE); // Only fatal errors and parse errors
        ini_set('display_errors', 0);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // Clean any buffered output
                while (ob_get_level()) {
                    ob_end_clean();
                }
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Fatal Error: ' . $error['message'],
                    'error' => [
                        'message' => $error['message'],
                        'file' => basename($error['file']),
                        'line' => $error['line'],
                        'type' => 'FATAL_ERROR'
                    ],
                    'debug' => [
                        'php_version' => PHP_VERSION,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]
                ]);
            }
        });
        
        // Clean any buffered output before sending JSON header
        ob_end_clean();
        header('Content-Type: application/json');
        
        try {
            $ip_address = router_normalize_ip_port(_post('ip_address'), _post('api_port'));
            $username = _post('username');
            $password = _post('password');
            
            if ($ip_address == '' || $username == '') {
                echo json_encode([
                    'success' => false,
                    'message' => Lang::T('IP address and username are required')
                ]);
                exit;
            }
            
            // Check if PEAR2 library exists
            $pear2Path = $DEVICE_PATH . '/../autoload/PEAR2/Autoload.php';
            if (!file_exists($pear2Path)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'PEAR2 RouterOS library not found',
                    'error' => [
                        'message' => 'Missing file: ' . $pear2Path,
                        'file' => 'PEAR2/Autoload.php',
                        'line' => 0,
                        'type' => 'FILE_NOT_FOUND'
                    ],
                    'debug' => [
                        'device_path' => $DEVICE_PATH,
                        'expected_path' => $pear2Path,
                        'php_version' => PHP_VERSION,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]
                ]);
                exit;
            }
            
            $errorDetail = null;
            $routerStatus = router_check_status($ip_address, $username, $password, $errorDetail, 6, false);
            
            $response = [
                'success' => $routerStatus == 'Online',
                'status' => $routerStatus,
                'ip_address' => $ip_address,
                'message' => $routerStatus == 'Online' ? Lang::T('Connexion réussie') : Lang::T('Connexion échouée')
            ];

            if ($routerStatus == 'Online') {
                $macAddress = router_fetch_mac_from_device($ip_address, $username, $password, 6);
                if ($macAddress !== '') {
                    $response['mac_address'] = $macAddress;
                }
            }
            
            if ($errorDetail) {
                $response['error'] = $errorDetail;
                $response['message'] = $errorDetail['message'];
            }
            
            $response['debug'] = [
                'php_version' => PHP_VERSION,
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
                'pear2_exists' => file_exists($pear2Path),
            ];
            
            echo json_encode($response);
            
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'class' => get_class($e),
                ],
                'debug' => [
                    'php_version' => PHP_VERSION,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'class' => get_class($e),
                ],
                'debug' => [
                    'php_version' => PHP_VERSION,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]
            ]);
        }
        exit;

    case 'edit':
        $id  = $routes['2'];
        $d = router_scoped_query($admin)->where('id', $id)->find_one();
        if (!$d) {
            $d = router_scoped_query($admin)->where_equal('name', _get('name'))->find_one();
        }
        $ui->assign('xheader', $leafletpickerHeader);
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_router_edit'); #HOOK
            $ui->display('admin/routers/edit.tpl');
        } else {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id = intval($routes['2'] ?? 0);
        if ($id <= 0) {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('routers/list'), 'e', Lang::T('You cannot perform this action in Demo mode'));
        }

        $d = router_scoped_query($admin)->where('id', $id)->find_one();
        if (!$d) {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }

        run_hook('router_delete'); #HOOK
        try {
            $d->delete();
            if ($admin['user_type'] !== 'SuperAdmin') {
                AdminSubscription::syncRouterCount((int) $admin['id']);
            }
            r2(getUrl('routers/list'), 's', Lang::T('Data Deleted Successfully'));
        } catch (Throwable $e) {
            _log('[Router Delete] ID ' . $id . ': ' . $e->getMessage(), 'Error');
            r2(getUrl('routers/list'), 'e', Lang::T('Failed to delete router') . ': ' . $e->getMessage());
        }
        break;

    case 'add-post':
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);
        $name = _post('name');
        $ip_address = router_normalize_ip_port(_post('ip_address'), _post('api_port'));
        $username = _post('username');
        $password = _post('password');
        $description = router_validate_mac(_post('description'));
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }
        if ($description === '' && ($enabled || _post('testIt'))) {
            $description = router_fetch_mac_from_device($ip_address, $username, $password, 6);
        }
        if ($description === '') {
            $msg .= 'Adresse MAC requise (ex. 18:FD:74:CB:CB:BA). Testez la connexion pour la récupérer automatiquement.<br>';
        } else {
            $macConflict = router_find_mac_conflict($description);
            if ($macConflict !== '') {
                $msg .= $macConflict . '<br>';
            }
        }
        $conflict = router_find_global_conflict($name, $enabled || _post('testIt') ? $ip_address : '');
        if ($conflict !== '') {
            $msg .= $conflict . '<br>';
        }
        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            if ($admin['user_type'] !== 'SuperAdmin') {
                $routerPermission = AdminSubscription::canAddRouter((int) $admin['id']);
                if (!$routerPermission['ok']) {
                    r2(getUrl('routers/list'), 'e', $routerPermission['message']);
                }
            }
            run_hook('add_router'); #HOOK
            // Timeout court + sans fallback long (évite fatal 15s sur VPN lent)
            $routerStatus = router_check_status($ip_address, $username, $password, $errorDetail, 8, false);
            if ($routerStatus != 'Online') {
                r2(getUrl('routers/add'), 'e', Lang::T('Router connection failed. Please verify IP, API port, username, password, MikroTik API service and firewall.') . ($errorDetail ? ' (' . $errorDetail . ')' : ''));
            }
            $d = ORM::for_table('tbl_routers')->create();
            $d->admin_id = $admin['id'];
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            $d->status = $routerStatus;
            $d->last_seen = $routerStatus == 'Online' ? date('Y-m-d H:i:s') : null;
            $d->save();
            if ($admin['user_type'] !== 'SuperAdmin') {
                AdminSubscription::syncRouterCount((int) $admin['id']);
            }

            r2(getUrl('routers/list'), 's', Lang::T('Data Created Successfully') . ' - ' . Lang::T($routerStatus));
        } else {
            r2(getUrl('routers/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $name = _post('name');
        $ip_address = router_normalize_ip_port(_post('ip_address'), _post('api_port'));
        $username = _post('username');
        $password = _post('password');
        $description = router_validate_mac(_post('description'));
        $coordinates = _post('coordinates');
        $coverage = _post('coverage');
        $enabled = $_POST['enabled'];
        $msg = '';
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }
        if ($description === '' && ($enabled || _post('testIt'))) {
            $description = router_fetch_mac_from_device($ip_address, $username, $password, 6);
        }
        if ($description === '') {
            $msg .= 'Adresse MAC requise (ex. 18:FD:74:CB:CB:BA). Saisissez-la manuellement ou testez la connexion pour la récupérer.<br>';
        } else {
            $macConflict = router_find_mac_conflict($description, (int) _post('id'));
            if ($macConflict !== '') {
                $msg .= $macConflict . '<br>';
            }
        }

        $id = _post('id');
        $d = router_scoped_query($admin)->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $conflict = router_find_global_conflict($name, '', (int) $d['id']);
            if ($conflict !== '') {
                $msg .= $conflict . '<br>';
            }
        }
        $oldname = $d['name'];

        if($enabled || _post("testIt")){
            if ($d['ip_address'] != $ip_address) {
                $conflict = router_find_global_conflict('', $ip_address, (int) $d['id']);
                if ($conflict !== '') {
                    $msg .= $conflict . '<br>';
                }
            }
        }

        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('router_edit'); #HOOK
            $passwordToStore = $password;
            if ($passwordToStore === '' || $passwordToStore === null) {
                $passwordToStore = $d->password;
            }
            if ($enabled || _post('testIt')) {
                $routerStatus = router_check_status($ip_address, $username, $passwordToStore, $errorDetail, 8, false);
                if ($routerStatus != 'Online' && (_post('testIt') || $enabled)) {
                    r2(getUrl('routers/edit/') . $id, 'e', Lang::T('Router connection failed. Check API user, password, port 8728 and firewall.') . ($errorDetail ? ' (' . $errorDetail . ')' : ''));
                }
            } else {
                $routerStatus = $d->status ?: 'Offline';
            }
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $passwordToStore;
            $d->description = $description;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->enabled = $enabled;
            $d->status = $routerStatus;
            if ($routerStatus == 'Online') {
                $d->last_seen = date('Y-m-d H:i:s');
            }
            $d->save();
            if ($name != $oldname) {
                $p = ORM::for_table('tbl_plans')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_payment_gateway')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_pool')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_transactions')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_user_recharges')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_voucher')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
            }
            r2(getUrl('routers/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('routers/edit/') . $id, 'e', $msg);
        }
        break;

    case 'alerts':
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'alerts' => RouterMonitor::getAlertsForAdmin($admin),
        ]);
        exit;

    case 'dismiss-alert':
        header('Content-Type: application/json; charset=utf-8');
        $alertId = intval(_post('id'));
        $ok = RouterMonitor::dismissAlert($alertId, $admin);
        echo json_encode(['success' => $ok]);
        exit;

    case 'run-check':
        if ($admin['user_type'] != 'SuperAdmin') {
            r2(getUrl('routers/list'), 'e', Lang::T('You do not have permission to access this page'));
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('routers/list'), 'e', 'You cannot perform this action in Demo mode');
        }
        $result = RouterMonitor::maybeRunDailyCheck(true);
        r2(
            getUrl('routers/list'),
            's',
            'Vérification terminée — ' . ($result['online'] ?? 0) . ' en ligne, ' . ($result['offline'] ?? 0) . ' hors ligne.'
        );
        break;

    default:

        $name = _post('name');
        $name = _post('name');
        $query = router_scoped_query($admin)->order_by_desc('id');
        if ($name != '') {
            $query->where_like('name', '%' . $name . '%');
        }
        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::injectRoutersList($ui, 20);
        } else {
            $d = Paginator::findMany($query, ['name' => $name]);
            $ui->assign('d', $d);
        }
        $routerPermission = ['ok' => true, 'message' => ''];
        if ($admin['user_type'] !== 'SuperAdmin') {
            $routerPermission = AdminSubscription::canAddRouter((int) $admin['id']);
        }
        if (DemoShowcase::isActive($admin)) {
            $routerPermission = ['ok' => false, 'message' => 'Compte démo : ajout de routeur désactivé.'];
        }
        $ui->assign('router_add_permission', $routerPermission);
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
