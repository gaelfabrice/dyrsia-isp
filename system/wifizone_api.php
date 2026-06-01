<?php

/**
 * WifiZone Mobile API — JWT
 */
session_start();
$isApi = true;
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$admin = WifiZoneApi::authenticateRequest();
if (!$admin) {
    WifiZoneApi::jsonResponse(false, 'Unauthorized', []);
}

$req = _get('r') ?: 'me';
$routes = explode('/', $req);
$action = $routes[0] ?? 'me';

switch ($action) {
    case 'me':
        WifiZoneApi::jsonResponse(true, '', $admin);
        break;
    case 'customers':
        $customersQ = ORM::for_table('tbl_customers');
        $scopeId = WifiZoneSecurity::scopeAdminId($admin);
        if ($scopeId !== null) {
            $customersQ->where('created_by', $scopeId);
        }
        WifiZoneApi::jsonResponse(true, '', $customersQ->limit(100)->find_array());
        break;
    case 'routers':
        $routersQ = ORM::for_table('tbl_routers')->where('enabled', 1);
        $scopeId = WifiZoneSecurity::scopeAdminId($admin);
        if ($scopeId !== null) {
            $routersQ->where('admin_id', $scopeId);
        }
        WifiZoneApi::jsonResponse(true, '', $routersQ->find_array());
        break;
    case 'recharge':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            WifiZoneApi::jsonResponse(false, 'POST required');
        }
        $customerId = (int) _post('customer_id');
        $scopeId = WifiZoneSecurity::scopeAdminId($admin);
        if ($scopeId !== null) {
            $owned = ORM::for_table('tbl_customers')
                ->where('id', $customerId)
                ->where('created_by', $scopeId)
                ->find_one();
            if (!$owned) {
                WifiZoneApi::jsonResponse(false, 'Forbidden');
            }
        }
        $ok = Package::rechargeUser($customerId, _post('router'), (int) _post('plan_id'), 'MobileAPI', 'api-' . time());
        WifiZoneApi::jsonResponse($ok, $ok ? 'Recharged' : 'Failed');
        break;
    case 'register_fcm':
        $token = _post('fcm_token');
        if ($token) {
            if (!ORM::for_table('wifizone_fcm_tokens')->where('token', $token)->find_one()) {
                $row = ORM::for_table('wifizone_fcm_tokens')->create();
                $row->token = $token;
                $row->admin_id = $admin['id'];
                $row->save();
            }
            WifiZoneApi::jsonResponse(true, 'FCM registered');
        }
        WifiZoneApi::jsonResponse(false, 'Token required');
        break;
    default:
        WifiZoneApi::jsonResponse(false, 'Unknown action');
}
