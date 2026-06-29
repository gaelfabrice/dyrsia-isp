<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

$pluginFn = $routes[1] ?? '';

// A missing plugin function must not pollute the session flash (notify). Background
// dashboard polls to removed endpoints (e.g. old hotspot_ticker) would otherwise leave
// a stale "Function not found" message that surfaces on the next real action.
$plugin_not_found = function () {
    if (function_exists('wifizone_json_response_requested') && wifizone_json_response_requested()) {
        wifizone_json_error('Function not found', 404);
    }
    if (!headers_sent()) {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'Function not found';
    exit;
};

if ($pluginFn === '') {
    $plugin_not_found();
}

if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $pluginFn)) {
    $plugin_not_found();
}

$publicPlugins = [
    'hotspot_login', 'hotspot_login_file', 'hotspot_ticker', 'hotspot_plan', 'hotspot_log', 'hotspot_voucher_check', 'hotspot_account_check', 'hotspot_recover_plan', 'hotspot_pay', 'hotspot_verify', 'hotspot_pg_campay_verify',
    'pppoe_portal', 'pppoe_plan', 'pppoe_pay', 'pppoe_verify',
    'wifizone_reseller_api',
];
if (in_array($pluginFn, $publicPlugins, true)) {
    wifizone_hotspot_plugin_cors();
} else {
    _admin();
}

if ($pluginFn === 'hotspot_log' && !function_exists('hotspot_log')) {
    function hotspot_log()
    {
        header('Content-Type: application/json');
        $code = trim(_post('code') ?: _get('code'));
        $message = trim(_post('message') ?: _get('message'));
        $mac = trim(_post('mac') ?: _get('mac'));
        $ip = trim(_post('ip') ?: _get('ip'));
        $router = trim(_post('router') ?: _get('router'));
        if ($message === '') {
            $message = 'Erreur de connexion voucher';
        }
        $safeCode = $code !== '' ? substr(preg_replace('/[^A-Za-z0-9_.@\-]/', '', $code), 0, 80) : 'vide';
        _log('Hotspot captive portal: ' . $message . ' | code=' . $safeCode . ' | mac=' . $mac . ' | ip=' . $ip . ' | router=' . $router, 'Hotspot', 0);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($pluginFn === 'hotspot_login_file' && !function_exists('hotspot_login_file')) {
    function hotspot_login_file()
    {
        global $UPLOAD_PATH;
        $loginDir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';
        $file = $loginDir . DIRECTORY_SEPARATOR . 'login.html';
        if (!is_file($file) || !is_readable($file)) {
            $templateFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'mikrotik-hotspot-login.html';
            if (is_file($templateFile)) {
                if (!is_dir($loginDir)) {
                    @mkdir($loginDir, 0755, true);
                }
                @copy($templateFile, $file);
            }
        }
        if (!is_file($file) || !is_readable($file)) {
            $message = 'login.html introuvable — enregistrez Paramètres Hotspot pour générer la page.';
            if (!headers_sent()) {
                header('HTTP/1.1 404 Not Found');
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Length: ' . strlen($message));
            }
            echo $message;
            exit;
        }
        $size = filesize($file);
        ob_implicit_flush(true);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            if ($size !== false) {
                header('Content-Length: ' . $size);
            }
        }
        readfile($file);
        exit;
    }
}

if ($pluginFn === 'hotspot_ticker' && !function_exists('hotspot_ticker')) {
    function hotspot_ticker()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => '',
            'items' => [],
            'data' => [],
        ]);
        exit;
    }
}

if ($pluginFn === 'hotspot_voucher_check' && !function_exists('hotspot_voucher_check')) {
    function hotspot_voucher_check()
    {
        header('Content-Type: application/json');
        $code = trim(_post('voucher') ?: _post('code') ?: _get('voucher') ?: _get('code'));
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Code voucher requis']);
            exit;
        }
        $voucher = ORM::for_table('tbl_voucher')->where_raw('BINARY code = ?', [$code])->where('status', 0)->find_one();
        if (!$voucher) {
            _log('Hotspot voucher invalid: ' . $code, 'Hotspot', 0);
            echo json_encode(['success' => false, 'message' => 'Voucher invalide ou introuvable']);
            exit;
        }
        $plan = ORM::for_table('tbl_plans')->where('id', $voucher['id_plan'])->find_one();
        if (!$plan) {
            echo json_encode(['success' => false, 'message' => 'Forfait du voucher introuvable']);
            exit;
        }
        $password = HotspotCustomer::defaultPassword();
        $username = HotspotCustomer::generateUsername(10);
        $customer = ORM::for_table('tbl_customers')->create();
        $customer->username = $username;
        $customer->password = $password;
        $customer->pppoe_username = '';
        $customer->pppoe_password = '';
        $customer->pppoe_ip = '';
        $customer->email = '';
        $customer->account_type = 'Personal';
        $customer->fullname = 'Voucher ' . $code;
        $customer->address = '';
        $customer->created_by = 0;
        $customer->phonenumber = '';
        $customer->service_type = 'Hotspot';
        $customer->status = 'Active';
        $customer->created_at = date('Y-m-d H:i:s');
        $customer->save();
        if (!Package::rechargeUser($customer->id(), $voucher['routers'], $voucher['id_plan'], 'Voucher', $code)) {
            $customer->delete();
            _log('Hotspot voucher activation failed: ' . $code, 'Hotspot', 0);
            echo json_encode(['success' => false, 'message' => 'Activation du voucher impossible']);
            exit;
        }
        $voucher->status = '1';
        $voucher->used_date = date('Y-m-d H:i:s');
        $voucher->user = $username;
        $voucher->save();
        echo json_encode([
            'success' => true,
            'message' => 'Voucher activé',
            'username' => $username,
            'password' => $password,
            'package' => [
                'name' => $plan['name_plan'] ?? $plan['name'] ?? '',
                'price' => $plan['price'] ?? '',
                'validity' => trim(($plan['validity'] ?? '') . ' ' . ($plan['validity_unit'] ?? '')),
                'router' => $plan['routers'] ?? '',
            ],
        ]);
        exit;
    }
}

if ($pluginFn === 'hotspot_account_check' && !function_exists('hotspot_account_check')) {
    function hotspot_account_check()
    {
        header('Content-Type: application/json');
        $username = trim(_post('username') ?: _get('username'));
        $password = trim(_post('password') ?: _get('password'));
        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Identifiant et mot de passe requis']);
            exit;
        }
        $recharge = ORM::for_table('tbl_user_recharges')->where('username', $username)->where('status', 'on')->find_one();
        $plan = $recharge ? ORM::for_table('tbl_plans')->where('id', $recharge['plan_id'])->find_one() : null;
        if (!$recharge || !$plan) {
            _log('Hotspot account invalid: ' . $username, 'Hotspot', 0);
            echo json_encode(['success' => false, 'message' => 'Compte ou forfait actif introuvable']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'message' => 'Compte valide',
            'username' => $username,
            'password' => $password,
            'package' => [
                'name' => $plan['name_plan'] ?? $plan['name'] ?? '',
                'price' => $plan['price'] ?? '',
                'validity' => trim(($plan['validity'] ?? '') . ' ' . ($plan['validity_unit'] ?? '')),
                'router' => $plan['routers'] ?? '',
            ],
        ]);
        exit;
    }
}

if ($pluginFn === 'hotspot_recover_plan' && !function_exists('hotspot_recover_plan')) {
    function hotspot_recover_plan()
    {
        header('Content-Type: application/json');
        $phone = preg_replace('/\D/', '', trim(_post('phone') ?: _get('phone')));
        if (strlen($phone) !== 9) {
            echo json_encode(['success' => false, 'message' => 'Le numéro doit contenir 9 chiffres']);
            exit;
        }

        $routerName = trim((string) (_post('routername') ?: _get('routername')));
        $customer = HotspotCustomer::findByPhone($phone);

        $recharge = null;
        if ($customer) {
            $recharge = hotspot_customer_has_active_recharge($customer->id, $routerName);
        }

        if (!$recharge && function_exists('hotspot_find_paid_payment_by_phone')) {
            $paidTrx = hotspot_find_paid_payment_by_phone($phone, $routerName);
            if ($paidTrx && function_exists('hotspot_retry_activate_payment')) {
                hotspot_retry_activate_payment($paidTrx);
                if (!$customer) {
                    $customer = HotspotCustomer::findByPhone($phone);
                }
                if ($customer) {
                    $recharge = hotspot_customer_has_active_recharge($customer->id, $routerName);
                }
            }
        }

        if (!$recharge) {
            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('username', $phone)
                ->where('status', 'on')
                ->where('type', 'Hotspot')
                ->order_by_desc('id')
                ->find_one();
            if ($recharge && !Package::isRechargeActive($recharge)) {
                $recharge = null;
            }
        }

        $plan = $recharge ? ORM::for_table('tbl_plans')->where('id', $recharge['plan_id'])->find_one() : null;
        if (!$recharge || !$plan) {
            echo json_encode(['success' => false, 'message' => 'Aucun forfait actif trouvé pour ce numéro']);
            exit;
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('id', $recharge['customer_id'])->find_one();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Forfait retrouvé',
            'username' => $recharge['username'] ?: ($customer['username'] ?? ''),
            'password' => Password::networkCleartext($customer) ?: HotspotCustomer::defaultPassword(),
            'package' => [
                'name' => $plan['name_plan'] ?? $plan['name'] ?? '',
                'price' => $plan['price'] ?? '',
                'validity' => trim(($plan['validity'] ?? '') . ' ' . ($plan['validity_unit'] ?? '')),
                'router' => $plan['routers'] ?? '',
            ],
        ]);
        exit;
    }
}

if ($pluginFn === 'pppoe_verify') {
    $reference = trim((string) ($_GET['reference'] ?? ''));
    if ($reference !== '') {
        $pending = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_ref', $reference)
            ->find_one();
        if ($pending && function_exists('pppoe_is_transaction') && pppoe_is_transaction($pending) && function_exists('pppoe_sync_transaction')) {
            pppoe_sync_transaction($pending);
        }
    }
}

if ($pluginFn === 'hotspot_verify') {
    $reference = trim((string) ($_GET['reference'] ?? ''));
    if ($reference !== '') {
        $pendingCampay = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_ref', $reference)
            ->where('payment_gateway', 'campay')
            ->where('transaction_status', 'pending')
            ->find_one();
        if ($pendingCampay && function_exists('hotspot_pg_campay_sync_transaction')) {
            hotspot_pg_campay_sync_transaction($pendingCampay);
        }
        $pendingMypvit = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'mypvit')
            ->where('transaction_status', 'pending')
            ->where_raw('(transaction_ref = ? OR transaction_id = ?)', [$reference, $reference])
            ->find_one();
        if ($pendingMypvit && function_exists('hotspot_pg_mypvit_sync_transaction')) {
            hotspot_pg_mypvit_sync_transaction($pendingMypvit);
        }
        $existingTrx = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_ref', $reference)
            ->find_one();
        if ($existingTrx
            && (string) $existingTrx->transaction_status === 'paid'
            && function_exists('hotspot_retry_activate_payment')) {
            hotspot_retry_activate_payment($existingTrx);
        }
    }
}

if (function_exists($pluginFn)) {
    call_user_func($pluginFn);
} else {
    $plugin_not_found();
}