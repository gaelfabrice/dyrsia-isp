<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

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

function router_check_status($ip_address, $username, $password, &$errorDetail = null)
{
    $oldTimeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 5);
    $errorDetail = null;
    
    try {
        (new MikrotikHotspot())->getClient($ip_address, $username, $password);
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
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
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
        // Capture all errors including fatal errors
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
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
            $routerStatus = router_check_status($ip_address, $username, $password, $errorDetail);
            
            $response = [
                'success' => $routerStatus == 'Online',
                'status' => $routerStatus,
                'ip_address' => $ip_address,
                'message' => $routerStatus == 'Online' ? Lang::T('Connexion réussie') : Lang::T('Connexion échouée')
            ];
            
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
        $id  = $routes['2'];
        run_hook('router_delete'); #HOOK
        $d = router_scoped_query($admin)->where('id', $id)->find_one();
        if ($d) {
            $d->delete();
            r2(getUrl('routers/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = router_normalize_ip_port(_post('ip_address'), _post('api_port'));
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }

            $d = router_scoped_query($admin)->where('ip_address', $ip_address)->find_one();
            if ($d) {
                $msg .= Lang::T('IP Router Already Exist') . '<br>';
            }
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
            $routerStatus = router_check_status($ip_address, $username, $password);
            if ($routerStatus != 'Online') {
                r2(getUrl('routers/add'), 'e', Lang::T('Router connection failed. Please verify IP, API port, username, password, MikroTik API service and firewall.'));
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
        $description = _post('description');
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

        $id = _post('id');
        $d = router_scoped_query($admin)->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $c = router_scoped_query($admin)->where('name', $name)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }
        $oldname = $d['name'];

        if($enabled || _post("testIt")){
            if ($d['ip_address'] != $ip_address) {
                $c = router_scoped_query($admin)->where('ip_address', $ip_address)->where_not_equal('id', $id)->find_one();
                if ($c) {
                    $msg .= 'IP Already Exists<br>';
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
                $routerStatus = router_check_status($ip_address, $username, $passwordToStore);
                if ($routerStatus != 'Online' && (_post('testIt') || $enabled)) {
                    r2(getUrl('routers/edit/') . $id, 'e', Lang::T('Router connection failed. Check API user, password, port 8728 and firewall.'));
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
        $d = Paginator::findMany($query, ['name' => $name]);
        $routerPermission = ['ok' => true, 'message' => ''];
        if ($admin['user_type'] !== 'SuperAdmin') {
            $routerPermission = AdminSubscription::canAddRouter((int) $admin['id']);
        }
        $ui->assign('router_add_permission', $routerPermission);
        $ui->assign('d', $d);
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
