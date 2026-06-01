<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

$pluginFn = $routes[1] ?? '';
if ($pluginFn === '') {
    r2(getUrl('dashboard'), 'e', 'Function not found');
}

if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $pluginFn)) {
    r2(getUrl('dashboard'), 'e', 'Function not found');
}

$publicPlugins = [
    'hotspot_login', 'hotspot_plan', 'hotspot_log', 'hotspot_voucher_check', 'hotspot_account_check', 'hotspot_recover_plan', 'hotspot_pay', 'hotspot_verify', 'hotspot_pg_bkash_verify',
    'wifizone_reseller_api',
];
if (!in_array($pluginFn, $publicPlugins, true)) {
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
        $password = '123456';
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $username = '';
            for ($i = 0; $i < 8; $i++) {
                $username .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        } while ($exists);
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
        $customer = ORM::for_table('tbl_customers')->where('phonenumber', $phone)->find_one();
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where_like('phonenumber', '%' . $phone)->find_one();
        }
        $recharge = $customer ? ORM::for_table('tbl_user_recharges')->where('customer_id', $customer['id'])->where('status', 'on')->order_by_desc('id')->find_one() : null;
        $plan = $recharge ? ORM::for_table('tbl_plans')->where('id', $recharge['plan_id'])->find_one() : null;
        if (!$customer || !$recharge || !$plan) {
            echo json_encode(['success' => false, 'message' => 'Aucun forfait actif trouvé pour ce numéro']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'message' => 'Forfait retrouvé',
            'username' => $recharge['username'] ?? $customer['username'],
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

if (function_exists($pluginFn)) {
    call_user_func($pluginFn);
} else {
    r2(getUrl('dashboard'), 'e', 'Function not found');
}