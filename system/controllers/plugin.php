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
    'hotspot_login', 'hotspot_login_file', 'hotspot_portal', 'hotspot_mikrotik_auth', 'hotspot_ticker', 'hotspot_plan', 'hotspot_log', 'hotspot_voucher_check', 'hotspot_account_check', 'hotspot_recover_plan', 'hotspot_prepare_login', 'hotspot_pay', 'hotspot_verify', 'hotspot_pg_campay_verify',
    'pppoe_portal', 'pppoe_plan', 'pppoe_pay', 'pppoe_verify',
    'wifizone_reseller_api',
];
if (in_array($pluginFn, $publicPlugins, true)) {
    wifizone_hotspot_plugin_cors();
} else {
    _admin();
}

$superAdminOnlyPlugins = ['hotspot_mac_update'];
if (in_array($pluginFn, $superAdminOnlyPlugins, true) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
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
        global $UPLOAD_PATH, $config;
        $routerName = WifiZoneHotspot::resolvePublicRouterName('');
        if ($routerName === '') {
            $message = 'Paramètre router requis pour servir login.html.';
            if (!headers_sent()) {
                header('HTTP/1.1 400 Bad Request');
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Length: ' . strlen($message));
            }
            echo $message;
            exit;
        }
        WifiZoneHotspot::loadHotspotConfigForRouter($config, $routerName);
        $ownerId = WifiZoneHotspot::routerAdminId($routerName);
        $file = WifiZoneHotspot::hotspotLoginHtmlPath($ownerId, $UPLOAD_PATH);
        if (!is_file($file) || !is_readable($file)) {
            $templateFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'mikrotik-hotspot-login.html';
            $loginDir = WifiZoneHotspot::hotspotLoginHtmlDir($ownerId, $UPLOAD_PATH);
            if (is_file($templateFile)) {
                if (!is_dir($loginDir)) {
                    @mkdir($loginDir, 0755, true);
                }
                @copy($templateFile, $file);
            }
        }
        if (!is_file($file) || !is_readable($file)) {
            $message = 'login.html introuvable pour le routeur « ' . $routerName . ' » — enregistrez Paramètres Hotspot pour générer la page.';
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
        $customer->pppoe_password = $password;
        $customer->pppoe_username = '';
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
            'password' => HotspotCustomer::defaultPassword(),
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
            'password' => HotspotCustomer::defaultPassword(),
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
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (function_exists('hotspot_process_expired_recharges_if_due')) {
                hotspot_process_expired_recharges_if_due();
            }

            $phone = preg_replace('/\D/', '', trim(_post('phone') ?: _get('phone')));
            if (strlen($phone) === 12 && str_starts_with($phone, '237')) {
                $phone = substr($phone, 3);
            }
            if (strlen($phone) !== 9) {
                echo json_encode(['success' => false, 'message' => 'Le numéro doit contenir 9 chiffres']);
                exit;
            }

            $routerName = hotspot_normalize_router_name(trim((string) (_post('routername') ?: _get('routername'))));
            $requestMac = class_exists('Mikrotik')
                ? Mikrotik::normalizeHotspotMacAddress((string) (_post('mac_address') ?: _post('mac') ?: _get('mac') ?: ''))
                : trim((string) (_post('mac_address') ?: _post('mac') ?: ''));
            if (class_exists('HotspotCustomer') && HotspotCustomer::isPlaceholderHotspotMac($requestMac)) {
                $requestMac = '';
            }
            $customer = HotspotCustomer::findByPhoneForHotspot($phone);
            if (!$customer) {
                $customer = HotspotCustomer::findByPhoneForHotspot('237' . $phone);
            }

            $recharge = null;
            if ($customer) {
                $recharge = hotspot_customer_has_active_recharge($customer->id, $routerName, true);
                if (!$recharge) {
                    $recharge = hotspot_customer_has_active_recharge($customer->id, '', false);
                }
            }

            if (!$recharge && function_exists('hotspot_find_paid_payment_by_phone')) {
                $paidTrx = hotspot_find_paid_payment_by_phone($phone, $routerName);
                if (!$paidTrx) {
                    $paidTrx = hotspot_find_paid_payment_by_phone($phone, '');
                }
                if ($paidTrx && function_exists('hotspot_retry_activate_payment')) {
                    hotspot_retry_activate_payment($paidTrx);
                    if (!$customer) {
                        $customer = HotspotCustomer::findByPhoneForHotspot($phone);
                    }
                    if ($customer) {
                        $recharge = hotspot_customer_has_active_recharge($customer->id, $routerName, true)
                            ?: hotspot_customer_has_active_recharge($customer->id, '', false);
                    }
                }
            }

            $planId = 0;
            if ($recharge) {
                $planId = (int) (is_object($recharge) ? ($recharge->plan_id ?? 0) : ($recharge['plan_id'] ?? 0));
            }
            $plan = $planId > 0 ? ORM::for_table('tbl_plans')->where('id', $planId)->find_one() : null;
            if (!$recharge || !$plan) {
                echo json_encode(['success' => false, 'message' => 'Aucun forfait actif trouvé pour ce numéro sur ce routeur']);
                exit;
            }

            $effectiveRouter = trim((string) (is_object($recharge) ? ($recharge->routers ?? '') : ($recharge['routers'] ?? $routerName)));
            if ($effectiveRouter === '') {
                $effectiveRouter = trim((string) (is_object($plan) ? ($plan->routers ?? '') : ($plan['routers'] ?? '')));
            }

            if (!$customer) {
                $customerId = (int) (is_object($recharge) ? ($recharge->customer_id ?? 0) : ($recharge['customer_id'] ?? 0));
                $customer = $customerId > 0 ? ORM::for_table('tbl_customers')->where('id', $customerId)->find_one() : null;
            }

            $login = trim((string) (is_object($recharge) ? ($recharge->username ?? '') : ($recharge['username'] ?? '')));
            if ($login === '' && $customer) {
                $login = trim((string) (is_object($customer) ? ($customer->username ?? '') : ($customer['username'] ?? '')));
            }

            // MAC connue et différente du verrou : autre appareil. MAC absente ($(mac) non substitué) : on rend les identifiants.
            if ($requestMac !== '' && !Mikrotik::hotspotPlanAllowsSharing($plan)) {
                $macCheck = HotspotCustomer::assertSingleDeviceMacAccess(
                    $recharge,
                    $plan,
                    $requestMac,
                    $effectiveRouter,
                    false
                );
                if (!$macCheck['ok'] && (($macCheck['action'] ?? '') === 'denied' || ($macCheck['action'] ?? '') === 'router_denied')) {
                    echo json_encode([
                        'success' => false,
                        'message' => HotspotCustomer::voucherAlreadyUsedMessage(),
                        'code' => 'voucher_already_used',
                        'shared_users' => 1,
                    ]);
                    exit;
                }
            }

            $expiresLabel = '';
            $expiration = is_object($recharge) ? ($recharge->expiration ?? '') : ($recharge['expiration'] ?? '');
            $expTime = is_object($recharge) ? ($recharge->time ?? '') : ($recharge['time'] ?? '');
            if (!empty($expiration)) {
                $expTs = strtotime(trim($expiration . ' ' . $expTime));
                $expiresLabel = $expTs ? date('d/m/Y H:i', $expTs) : (string) $expiration;
            }

            $sharedUsers = Mikrotik::hotspotSharedUsersLimit($plan);
            $planName = is_object($plan) ? ($plan->name_plan ?? $plan->name ?? '') : ($plan['name_plan'] ?? $plan['name'] ?? '');
            $planPrice = is_object($plan) ? ($plan->price ?? '') : ($plan['price'] ?? '');
            $planValidity = is_object($plan)
                ? trim(($plan->validity ?? '') . ' ' . ($plan->validity_unit ?? ''))
                : trim(($plan['validity'] ?? '') . ' ' . ($plan['validity_unit'] ?? ''));

            echo json_encode([
                'success' => true,
                'message' => $sharedUsers > 1
                    ? 'Forfait retrouvé — même identifiant utilisable sur jusqu\'à ' . $sharedUsers . ' appareils'
                    : 'Forfait retrouvé',
                'username' => $login,
                'password' => HotspotCustomer::defaultPassword(),
                'shared_users' => $sharedUsers,
                'expires_label' => $expiresLabel,
                'package' => [
                    'name' => $planName,
                    'price' => $planPrice,
                    'validity' => $planValidity,
                    'router' => $effectiveRouter,
                ],
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Impossible de récupérer le forfait. Réessayez dans un instant.',
            ]);
        }
        exit;
    }
}

if ($pluginFn === 'hotspot_prepare_login') {
    function hotspot_prepare_login()
    {
        header('Content-Type: application/json; charset=utf-8');
        wifizone_hotspot_plugin_cors();

        $username = trim((string) (_post('username') ?: _req('username') ?: ''));
        $routerName = trim((string) (_post('router') ?: _post('routername') ?: _req('router') ?: ''));
        $mac = trim((string) (_post('mac_address') ?: _post('mac') ?: _req('mac') ?: ''));

        if ($username === '' || $routerName === '') {
            echo json_encode(['ok' => false, 'message' => 'Paramètres manquants']);
            exit;
        }
        if (function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }

        $recharge = ORM::for_table('tbl_user_recharges')
            ->where('username', $username)
            ->where('status', 'on')
            ->where('type', 'Hotspot')
            ->order_by_desc('id')
            ->find_one();
        if (!$recharge || !Package::isRechargeActive($recharge)) {
            echo json_encode(['ok' => false, 'message' => 'Forfait actif introuvable']);
            exit;
        }

        $plan = ORM::for_table('tbl_plans')->find_one((int) $recharge['plan_id']);
        if (!$plan) {
            echo json_encode(['ok' => false, 'message' => 'Forfait introuvable']);
            exit;
        }

        if (Mikrotik::hotspotPlanAllowsSharing($plan)) {
            echo json_encode(['ok' => true, 'sharing' => true]);
            exit;
        }

        $mac = Mikrotik::normalizeHotspotMacAddress($mac);
        // Contrôle MAC en base uniquement (rapide) — pas d'appel API MikroTik ici.
        $check = HotspotCustomer::assertSingleDeviceMacAccess($recharge, $plan, $mac, $routerName, false);
        if (!$check['ok']) {
            echo json_encode([
                'ok' => false,
                'message' => HotspotCustomer::voucherAlreadyUsedMessage(),
                'code' => 'voucher_already_used',
            ]);
            exit;
        }

        echo json_encode(['ok' => true, 'sharing' => false, 'locked_mac' => $check['locked_mac'] ?? '']);
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
            ->where_in('transaction_status', ['pending', 'failed'])
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