<?php

/**
 * Portail captive PPPoE expiré : renouvellement + paiement mobile money.
 */

function pppoe_is_transaction($trx)
{
    if (!$trx) {
        return false;
    }
    if (strpos((string) ($trx->mac_address ?? ''), 'PPPOE:') === 0) {
        return true;
    }
    $meta = json_decode((string) ($trx->gateway_response ?? '{}'), true);

    return is_array($meta) && (
        ($meta['service'] ?? '') === 'pppoe'
        || strtoupper((string) ($meta['service_type'] ?? '')) === 'PPPOE'
    );
}

function pppoe_transaction_login($trx)
{
    if (!$trx) {
        return '';
    }
    if (strpos((string) ($trx->mac_address ?? ''), 'PPPOE:') === 0) {
        return substr((string) $trx->mac_address, 6);
    }
    $meta = json_decode((string) ($trx->gateway_response ?? '{}'), true);
    if (is_array($meta) && !empty($meta['pppoe_username'])) {
        return trim((string) $meta['pppoe_username']);
    }
    $login = trim((string) ($trx->voucher_code ?? ''));
    if ($login !== '' && $login !== '**********') {
        return $login;
    }

    return '';
}

function pppoe_find_customer_for_renewal($pppoeUsername, $phone = '')
{
    $customer = pppoe_find_customer_by_login($pppoeUsername);
    if ($customer) {
        return $customer;
    }
    $phone = trim((string) $phone);
    if ($phone === '') {
        return null;
    }
    $formattedPhone = class_exists('Lang') ? Lang::phoneFormat($phone) : $phone;

    return ORM::for_table('tbl_customers')->where('phonenumber', $formattedPhone)->find_one();
}

function pppoe_find_customer_by_login($login)
{
    $login = trim((string) $login);
    if ($login === '') {
        return null;
    }
    $customer = ORM::for_table('tbl_customers')->where('pppoe_username', $login)->find_one();
    if (!$customer) {
        $customer = ORM::for_table('tbl_customers')->where('username', $login)->find_one();
    }

    return $customer ?: null;
}

function pppoe_get_plan($planId, $routerName)
{
    return ORM::for_table('tbl_plans')
        ->where('id', (int) $planId)
        ->where('type', 'PPPOE')
        ->where('enabled', 1)
        ->where('routers', $routerName)
        ->where_not_equal('name_plan', 'EXPIRE')
        ->find_one();
}

function pppoe_json_error($message, $code = 400)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
    }
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function pppoe_respond_error($message)
{
    if (!empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        pppoe_json_error($message);
    }
    if (function_exists('hotspot_throwError')) {
        hotspot_throwError($message);
    }
    pppoe_json_error($message);
}

function pppoe_respond_success(array $payload)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array_merge(['ok' => true], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

function pppoe_portal()
{
    global $config;

    $routerName = trim((string) ($_GET['router'] ?? $_GET['routername'] ?? ''));
    $login = trim((string) ($_GET['user'] ?? $_GET['pppoe_username'] ?? ''));
    $clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    if ($login === '' && $clientIp !== '') {
        $fieldRow = ORM::for_table('tbl_customers_fields')
            ->where('field_name', 'pppoe_expired_ip')
            ->where('field_value', $clientIp)
            ->find_one();
        if ($fieldRow) {
            $login = User::getAttribute('pppoe_expired_user', (int) $fieldRow['customer_id'], '');
            if ($routerName === '') {
                $routerName = User::getAttribute('pppoe_expired_router', (int) $fieldRow['customer_id'], '');
            }
        }
    }

    $backendUrl = Mikrotik::resolvePppoeCaptiveBackendUrl(is_array($config) ? $config : []);
    if ($backendUrl === '' && defined('APP_URL')) {
        $backendUrl = rtrim(APP_URL, '/');
    }

    $templateFile = dirname(__DIR__, 2) . '/ui/ui/templates/pppoe-expired-portal.html';
    if (!is_file($templateFile)) {
        http_response_code(500);
        echo 'Portail PPPoE introuvable.';
        exit;
    }

    $gateways = function_exists('hotspot_getAvailablePaymentGateways')
        ? hotspot_getAvailablePaymentGateways()
        : [];
    $gatewayOptions = '';
    foreach ($gateways as $gw) {
        $value = htmlspecialchars((string) ($gw['value'] ?? ''), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) ($gw['name'] ?? $value), ENT_QUOTES, 'UTF-8');
        $gatewayOptions .= '<option value="' . $value . '">' . $label . '</option>';
    }
    if ($gatewayOptions === '') {
        $gatewayOptions = '<option value="campay">CamPay</option>';
    }

    $title = htmlspecialchars((string) ($config['CompanyName'] ?? 'Renouvellement PPPoE'), ENT_QUOTES, 'UTF-8');
    $html = file_get_contents($templateFile);
    $html = str_replace(
        ['{{PORTAL_TITLE}}', '{{PPPOE_USER}}', '{{GATEWAY_OPTIONS}}', '{{APP_URL_JSON}}', '{{ROUTER_JSON}}'],
        [
            $title,
            htmlspecialchars($login, ENT_QUOTES, 'UTF-8'),
            $gatewayOptions,
            json_encode($backendUrl, JSON_UNESCAPED_SLASHES),
            json_encode($routerName, JSON_UNESCAPED_UNICODE),
        ],
        $html
    );

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    echo $html;
    exit;
}

function pppoe_plan()
{
    global $config;

    if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? 'GET', 'POST') !== 0) {
        echo json_encode(['ResultCode' => '201', 'message' => 'Invalid Request method'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $routerInput = trim((string) ($_POST['routername'] ?? ''));
    if ($routerInput === '') {
        echo json_encode(['ResultCode' => '202', 'message' => 'Router name required'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $router = function_exists('hotspot_resolve_router') ? hotspot_resolve_router($routerInput) : null;
    if (!$router) {
        echo json_encode(['ResultCode' => '205', 'message' => 'Router Not Found: ' . $routerInput], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $routerName = (string) $router['name'];
    $currency = $config['currency_code'] ?? 'XAF';
    $plans = ORM::for_table('tbl_plans')
        ->where('type', 'PPPOE')
        ->where('routers', $routerName)
        ->where('enabled', 1)
        ->where_not_equal('name_plan', 'EXPIRE')
        ->find_many();

    $data = [];
    foreach ($plans as $row) {
        $data[] = [
            'planname' => (string) $row['name_plan'],
            'currency' => $currency,
            'price' => (string) $row['price'],
            'validity' => trim((string) $row['validity'] . ' ' . (string) $row['validity_unit']),
            'planId' => (int) $row['id'],
            'routerName' => $routerName,
        ];
    }

    echo json_encode([
        'ResultCode' => '200',
        'message' => 'Success',
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function pppoe_activate_transaction($trx, $operator = 'PPPoE Portal')
{
    $login = pppoe_transaction_login($trx);
    $customer = pppoe_find_customer_by_login($login);
    if (!$customer) {
        _log('[PPPoE portal] Customer not found for login: ' . $login);
        return false;
    }

    $routerName = (string) ($trx->router_name ?? '');
    $planId = (int) ($trx->plan_id ?? 0);
    if ($routerName === '' || $planId <= 0) {
        return false;
    }

    $gateway = ucfirst((string) ($trx->payment_gateway ?? 'CamPay'));
    if (!Package::rechargeUser((int) $customer->id, $routerName, $planId, $gateway, $operator)) {
        _log('[PPPoE portal] rechargeUser failed for ' . $login);
        return false;
    }

    User::setAttribute('pppoe_expired_ip', '', (int) $customer->id);
    User::setAttribute('pppoe_expired_router', '', (int) $customer->id);
    User::setAttribute('pppoe_expired_user', '', (int) $customer->id);

    $trx->voucher_code = $login;
    $trx->payment_method = $gateway . ' - ' . $operator;
    $trx->payment_date = date('Y-m-d H:i:s');
    $trx->transaction_status = 'paid';
    $trx->save();

    return true;
}

function pppoe_activate_after_payment($trx, $operator = 'CamPay')
{
    return pppoe_activate_transaction($trx, $operator);
}

function pppoe_sync_transaction($trx)
{
    if (!pppoe_is_transaction($trx)) {
        return $trx;
    }

    $gateway = strtolower((string) ($trx->payment_gateway ?? ''));
    if ($gateway === 'campay' && function_exists('hotspot_pg_campay_get_token')) {
        return pppoe_pg_campay_sync_transaction($trx);
    }
    if ($gateway === 'mypvit' && function_exists('hotspot_pg_mypvit_sync_transaction')) {
        return pppoe_pg_mypvit_sync_transaction($trx);
    }

    return $trx;
}

function pppoe_pg_mypvit_sync_transaction($trx)
{
    if (!function_exists('hotspot_pg_mypvit_sync_transaction')) {
        return $trx;
    }

    $originalActivate = null;
    if (function_exists('hotspot_pg_mypvit_activate_user')) {
        // mypvit plugin uses its own activate — we rely on campay-style sync below when possible.
    }

    $synced = hotspot_pg_mypvit_sync_transaction($trx);
    if ($synced && pppoe_is_transaction($synced) && (string) $synced->transaction_status === 'paid') {
        $login = pppoe_transaction_login($synced);
        if ($login !== '' && (string) ($synced->voucher_code ?? '') === '**********') {
            pppoe_activate_transaction($synced, 'MyPVit');
        }
    }

    return ORM::for_table('tbl_hotspot_payments')->find_one($trx->id);
}

function pppoe_pay()
{
    global $config;

    if (!empty($config['maintenance_mode']) && function_exists('displayMaintenanceMessage')) {
        displayMaintenanceMessage();
        exit;
    }

    $payload = function_exists('hotspot_payment_payload') ? hotspot_payment_payload() : $_POST;
    if (empty($payload['pay'])) {
        pppoe_respond_error('Requête de paiement invalide.');
    }

    $routername = trim((string) ($payload['routername'] ?? ''));
    $planid = (int) ($payload['planid'] ?? 0);
    $phone = trim((string) ($payload['phone'] ?? ''));
    $pppoeLogin = trim((string) ($payload['pppoe_username'] ?? ''));
    $gateway = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($payload['payment_gateway'] ?? 'campay'));

    if ($routername === '' || $planid <= 0 || $phone === '' || $pppoeLogin === '') {
        pppoe_respond_error('Paramètres manquants (routeur, forfait, téléphone, identifiant PPPoE).');
    }

    $router = function_exists('hotspot_resolve_router') ? hotspot_resolve_router($routername) : null;
    if (!$router) {
        pppoe_respond_error('Routeur introuvable.');
    }
    $routername = (string) $router['name'];

    $plan = pppoe_get_plan($planid, $routername);
    if (!$plan) {
        pppoe_respond_error('Forfait PPPoE invalide.');
    }

    $data = [
        'phone' => $phone,
        'routername' => $routername,
        'planid' => $planid,
        'plan_name' => (string) $plan['name_plan'],
        'amount' => $plan['price'],
        'pppoe_username' => $pppoeLogin,
        'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'txref' => 'pppoe-' . time() . '-' . bin2hex(random_bytes(4)),
    ];

    $fn = 'pppoe_processPayment_' . $gateway;
    if (!function_exists($fn)) {
        pppoe_respond_error('Passerelle « ' . $gateway . ' » non disponible pour PPPoE.');
    }
    $fn($data);
}

function pppoe_verify()
{
    $reference = trim((string) ($_GET['reference'] ?? ''));
    $wantsJson = (isset($_GET['format']) && $_GET['format'] === 'json')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!$wantsJson) {
        if ($reference === '') {
            echo 'Référence de paiement manquante.';
            exit;
        }
        header('Location: ' . U . 'plugin/pppoe_portal');
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    if ($reference === '') {
        echo json_encode(['status' => 'error', 'message' => 'No reference supplied.']);
        exit;
    }

    $check = ORM::for_table('tbl_hotspot_payments')
        ->where('transaction_ref', $reference)
        ->find_one();
    if (!$check) {
        echo json_encode(['status' => 'not_found', 'message' => 'Transaction not found.']);
        exit;
    }

    if (pppoe_is_transaction($check)) {
        $check = pppoe_sync_transaction($check);
    } elseif (function_exists('hotspot_pg_campay_sync_transaction') && (string) $check->payment_gateway === 'campay') {
        $check = hotspot_pg_campay_sync_transaction($check);
    }

    $status = (string) ($check->transaction_status ?? 'pending');
    $payload = [
        'status' => $status,
        'reference' => $reference,
    ];

    if ($status === 'paid') {
        $login = pppoe_transaction_login($check);
        if ($login === '') {
            $login = (string) ($check->voucher_code ?? '');
        }
        $payload['pppoe_username'] = $login;
        $payload['username'] = $login;
        $payload['message'] = 'Forfait réactivé.';
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
