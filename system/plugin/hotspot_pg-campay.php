<?php

/**
 * CamPay Mobile Money gateway for Hotspot captive portal payments (Cameroon).
 * Uses the same API credentials as Settings → Payment Gateway → CamPay.
 */

function hotspot_pg_campay_api_base()
{
    global $config;
    return ($config['campay_environment'] ?? 'demo') === 'prod'
        ? 'https://www.campay.net/api'
        : 'https://demo.campay.net/api';
}

function hotspot_pg_campay_get_token()
{
    global $config;

    if (empty($config['campay_username']) || empty($config['campay_password'])) {
        return null;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => hotspot_pg_campay_api_base() . '/token/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'username' => $config['campay_username'],
            'password' => $config['campay_password'],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        _log('CamPay Hotspot token error: HTTP ' . $httpCode . ' - ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    return $data['token'] ?? null;
}

function hotspot_pg_campay_format_phone($phone)
{
    if (function_exists('hotspot_formatPhoneNumber')) {
        return hotspot_formatPhoneNumber($phone);
    }

    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    if (preg_match('/^237[62]\d{8}$/', $phone)) {
        return $phone;
    }
    if (strpos($phone, '237') === 0) {
        $phone = substr($phone, 3);
    }
    $phone = ltrim($phone, '0');
    if (strlen($phone) === 9) {
        return '237' . $phone;
    }
    return '237' . $phone;
}

function hotspot_pg_campay_is_ajax()
{
    if (function_exists('hotspot_wants_json_response') && hotspot_wants_json_response()) {
        return true;
    }

    if (!empty($_POST['ajax']) || !empty($_GET['ajax']) || !empty($_POST['pay']) || !empty($_GET['pay'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return strpos($accept, 'application/json') !== false;
}

function hotspot_pg_campay_ussd_for_phone($phone)
{
    $digits = preg_replace('/\D/', '', (string) $phone);
    $local = strlen($digits) >= 9 ? substr($digits, -9) : $digits;
    if ($local !== '' && $local[0] === '6') {
        if (preg_match('/^6[5-8]/', $local)) {
            return ['operator' => 'MTN', 'ussd_code' => '*126#'];
        }
        if (preg_match('/^6[69]/', $local)) {
            return ['operator' => 'Orange', 'ussd_code' => '#150*50#'];
        }
    }

    return ['operator' => 'Mobile Money', 'ussd_code' => '*126#'];
}

function hotspot_pg_campay_respond_error($message)
{
    // Toujours JSON : le portail captif utilise fetch() et ne suit pas les redirects HTML.
    if (!headers_sent()) {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function hotspot_pg_campay_respond_success($txref, $result = [], $phone = '')
{
    global $config;
    $hint = hotspot_pg_campay_ussd_for_phone($phone);
    $operator = trim((string) ($result['operator'] ?? ''));
    $ussd = trim((string) ($result['ussd_code'] ?? ''));
    if ($operator === '') {
        $operator = $hint['operator'];
    }
    if ($ussd === '') {
        $ussd = $hint['ussd_code'];
    }

    if (!headers_sent()) {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'ok' => true,
        'reference' => $txref,
        'operator' => $operator,
        'ussd_code' => $ussd,
        'currency' => $config['campay_currency'] ?? 'XAF',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function hotspot_pg_campay_is_successful_status($status)
{
    return MobileMoneyGateway::isSuccessfulGatewayStatus($status);
}

function hotspot_pg_campay_is_failed_status($status)
{
    return MobileMoneyGateway::isFailedGatewayStatus($status);
}

function hotspot_pg_campay_extract_failure_reason(array $campayData = [], $fallback = '')
{
    foreach (['reason', 'message', 'detail', 'failure_reason', 'error', 'description'] as $key) {
        $value = trim((string) ($campayData[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    $status = strtoupper(trim((string) ($campayData['status'] ?? '')));
    if ($status !== '' && MobileMoneyGateway::isFailedGatewayStatus($status)) {
        return 'CamPay : transaction refusée ou non confirmée sur le téléphone.';
    }

    return trim((string) $fallback);
}

function hotspot_pg_campay_payment_failure_reason($trx)
{
    if (!$trx) {
        return '';
    }

    $meta = json_decode((string) ($trx->gateway_response ?? '{}'), true);
    if (!is_array($meta)) {
        return '';
    }

    if (!empty($meta['campay_failure_reason'])) {
        return (string) $meta['campay_failure_reason'];
    }

    if (!empty($meta['campay_poll']) && is_array($meta['campay_poll'])) {
        return hotspot_pg_campay_extract_failure_reason($meta['campay_poll']);
    }

    if (!empty($meta['response']) && is_array($meta['response'])) {
        return hotspot_pg_campay_extract_failure_reason($meta['response']);
    }

    if ((string) ($trx->transaction_status ?? '') === 'failed') {
        return 'Paiement non confirmé — aucun débit Mobile Money.';
    }

    return '';
}

function hotspot_pg_campay_customer_has_active_plan($trx)
{
    if (!$trx) {
        return false;
    }
    $customer = HotspotCustomer::resolveCustomerFromPayment($trx);
    if (!$customer) {
        return false;
    }
    if (function_exists('hotspot_customer_has_active_recharge')) {
        return hotspot_customer_has_active_recharge(
            (int) $customer->id,
            (string) ($trx->router_name ?? '')
        );
    }

    $recharge = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', (int) $customer->id)
        ->where('plan_id', (int) ($trx->plan_id ?? 0))
        ->where('routers', (string) ($trx->router_name ?? ''))
        ->where('status', 'on')
        ->find_one();

    return $recharge && Package::isRechargeActive($recharge);
}

function hotspot_pg_campay_activate_user($trx, $operator = 'CamPay')
{
    $phone = $trx->phone_number;
    $fullname = 'Hotspot User';
    $address = 'Hotspot';
    $gatewayMeta = json_decode($trx->gateway_response ?? '{}', true);
    if (!is_array($gatewayMeta)) {
        $gatewayMeta = [];
    }
    if (!empty($gatewayMeta['customer_name'])) {
        $fullname = $gatewayMeta['customer_name'];
    }
    if (!empty($gatewayMeta['customer_address'])) {
        $address = $gatewayMeta['customer_address'];
    }
    $routername = function_exists('hotspot_normalize_router_name')
        ? hotspot_normalize_router_name((string) $trx->router_name)
        : trim((string) $trx->router_name);
    $planid = $trx->plan_id;
    $mac_address = $trx->mac_address;

    if (!function_exists('hotspot_cleanMac')) {
        function hotspot_cleanMac($mac)
        {
            return strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', (string) $mac));
        }
    }

    $formattedPhone = Lang::phoneFormat($phone);

    $previousGlobalTrx = $GLOBALS['trx'] ?? null;
    unset($GLOBALS['trx']);

    $customer = null;
    $networkPassword = '';

    try {
        $customer = HotspotCustomer::findOrCreate($phone, $fullname, $address);
        $prepared = HotspotCustomer::prepareForHotspotActivation($customer);
        $customer = $prepared['customer'];
        $networkPassword = HotspotCustomer::defaultPassword();

        if (!Package::rechargeUser($customer->id, $routername, $planid, 'CamPay', $operator)) {
            if (hotspot_pg_campay_customer_has_active_plan($trx)) {
                _log('[CamPay Hotspot] rechargeUser returned false but active recharge exists for trx ' . $trx->transaction_ref);
            } else {
                _log('[CamPay Hotspot] Activation failed for trx ' . $trx->transaction_ref);
                return false;
            }
        }

        HotspotCustomer::forceMikrotikHotspotPassword($customer->username, $routername, $networkPassword);
    } finally {
        HotspotCustomer::clearActivationNetworkPassword();
        if ($previousGlobalTrx !== null) {
            $GLOBALS['trx'] = $previousGlobalTrx;
        } else {
            unset($GLOBALS['trx']);
        }
    }

    $customer = ORM::for_table('tbl_customers')->find_one($customer->id);
    HotspotCustomer::storePaymentNetworkPassword($trx, $networkPassword);

    $expiration = ORM::for_table('tbl_user_recharges')
        ->where('plan_id', $planid)
        ->where('customer_id', $customer->id)
        ->where('status', 'on')
        ->find_one();

    $expired = date('Y-m-d H:i:s', strtotime('+1 day'));
    if ($expiration) {
        $expired = $expiration->expiration . ' ' . date('h:i A', strtotime($expiration->time));
        if (function_exists('hotspot_scheduleCredentialsNotify')) {
            hotspot_scheduleCredentialsNotify(
                $phone,
                $expiration->namebp,
                $customer->username,
                $expired
            );
        }
    }

    $trx->router_name = $routername;
    $trx->voucher_code = $customer->username;
    $trx->payment_method = 'CamPay - ' . $operator;
    $trx->payment_date = date('Y-m-d H:i:s');
    $trx->transaction_status = 'paid';
    $trx->expired_date = date('Y-m-d H:i:s', strtotime($expired));
    $trx->save();

    if (function_exists('hotspot_invalidate_overview_cache')) {
        hotspot_invalidate_overview_cache();
    }

    return true;
}

function hotspot_pg_campay_sync_transaction($trx, $curlTimeout = 30)
{
    if (!$trx || empty($trx->transaction_id)) {
        return $trx;
    }

    if ((string) $trx->transaction_status === 'paid') {
        return $trx;
    }

    $token = hotspot_pg_campay_get_token();
    if (!$token) {
        return $trx;
    }

    $curlTimeout = max(5, min(30, (int) $curlTimeout));

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => hotspot_pg_campay_api_base() . '/transaction/' . urlencode($trx->transaction_id) . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_TIMEOUT => $curlTimeout,
        CURLOPT_CONNECTTIMEOUT => min(5, $curlTimeout),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return $trx;
    }

    $result = json_decode($response, true);
    $status = strtoupper(trim((string) ($result['status'] ?? 'PENDING')));
    $operator = $result['operator'] ?? 'CamPay';

    $existingMeta = json_decode((string) ($trx->gateway_response ?? '{}'), true);
    if (!is_array($existingMeta)) {
        $existingMeta = [];
    }
    $existingMeta['campay_poll'] = $result;
    $existingMeta['campay_poll_at'] = date('Y-m-d H:i:s');
    $trx->gateway_response = json_encode($existingMeta, JSON_UNESCAPED_UNICODE);

    if (hotspot_pg_campay_is_successful_status($status)) {
        if ((string) $trx->transaction_status === 'failed') {
            $trx->transaction_status = 'pending';
        }
        try {
            hotspot_pg_campay_activate_user($trx, $operator);
        } catch (Throwable $e) {
            _log('[CamPay Hotspot] Activation exception for trx ' . $trx->transaction_ref . ': ' . $e->getMessage());
            Package::$lastDeviceSyncError = $e->getMessage();
        }
        if ((string) $trx->transaction_status === 'pending') {
            if (hotspot_pg_campay_customer_has_active_plan($trx)) {
                $credentials = HotspotCustomer::credentialsFromPayment($trx);
                if ($credentials['username'] !== '') {
                    $trx->voucher_code = $credentials['username'];
                }
                $trx->payment_method = 'CamPay - ' . $operator;
                $trx->payment_date = date('Y-m-d H:i:s');
                $trx->transaction_status = 'paid';
            }
            Message::sendTelegram(
                'CamPay Hotspot: paiement OK mais activation en attente — trx '
                . ($trx->transaction_ref ?? $trx->transaction_id)
            );
            $trx->save();
        }
    } elseif (hotspot_pg_campay_is_failed_status($status)) {
        if (!hotspot_pg_campay_customer_has_active_plan($trx)) {
            $existingMeta['campay_failure_reason'] = hotspot_pg_campay_extract_failure_reason(
                is_array($result) ? $result : [],
                'CamPay : transaction refusée, expirée ou annulée sur le téléphone (aucun débit).'
            );
            $trx->gateway_response = json_encode($existingMeta, JSON_UNESCAPED_UNICODE);
            $trx->transaction_status = 'failed';
            $trx->payment_date = date('Y-m-d H:i:s');
        }
        $trx->save();
    } else {
        if ((string) $trx->transaction_status === 'failed') {
            $trx->transaction_status = 'pending';
        }
        $trx->save();
    }

    return ORM::for_table('tbl_hotspot_payments')->find_one($trx->id);
}

function hotspot_processPayment_campay($data)
{
    global $config;

    if (!function_exists('campay_validate_collect_amount')) {
        require_once dirname(__DIR__) . '/paymentgateway/campay.php';
    }

    $phone = $data['phone'];
    $mac_address = $data['mac_address'];
    $ip_address = $data['ip_address'];
    $routername = function_exists('hotspot_normalize_router_name')
        ? hotspot_normalize_router_name($data['routername'] ?? '')
        : trim((string) ($data['routername'] ?? ''));
    $txref = $data['txref'];
    $planid = $data['planid'];
    $plan_name = $data['plan_name'];
    $amount = $data['amount'];

    if (empty($config['campay_username']) || empty($config['campay_password'])) {
        Message::sendTelegram('CamPay Hotspot: gateway not configured');
        hotspot_pg_campay_respond_error(Lang::T('Payment gateway not configured. Please contact') . ' ' . ($config['CompanyName'] ?? 'admin'));
    }

    if (!function_exists('hotspot_cleanMac')) {
        function hotspot_cleanMac($mac)
        {
            return strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', (string) $mac));
        }
    }

    if (function_exists('hotspot_cleanup_stale_recharge')) {
        $customerCheck = class_exists('HotspotCustomer') ? HotspotCustomer::findByPhone($phone) : null;
        if ($customerCheck) {
            hotspot_cleanup_stale_recharge((int) $customerCheck->id, $routername);
        }
    }

    $amountError = campay_validate_collect_amount($phone, $amount);
    if ($amountError) {
        hotspot_pg_campay_respond_error($amountError);
    }

    $token = hotspot_pg_campay_get_token();
    if (!$token) {
        hotspot_pg_campay_respond_error(Lang::T('Payment gateway authentication failed. Please try again.'));
    }

    $campayPhone = hotspot_pg_campay_format_phone($phone);
    $currency = $config['campay_currency'] ?? 'XAF';
    $payload = [
        'amount' => (int) intval($amount),
        'currency' => $currency,
        'from' => $campayPhone,
        'description' => $plan_name . ' - ' . ($config['CompanyName'] ?? 'Hotspot'),
        'external_reference' => $txref,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => hotspot_pg_campay_api_base() . '/collect/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        _log('CamPay Hotspot collect curl error: ' . $curlError);
        hotspot_pg_campay_respond_error(Lang::T('Connection error. Please try again.'));
    }

    $result = json_decode($response, true);
    if ($httpCode !== 200 || empty($result['reference'])) {
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;
        _log('CamPay Hotspot collect failed: HTTP ' . $httpCode . ' - ' . $errorMsg);
        Message::sendTelegram("CamPay Hotspot collect failed:\n" . json_encode($result, JSON_PRETTY_PRINT));
        if (stripos((string) $errorMsg, 'demo system') !== false) {
            hotspot_pg_campay_respond_error('CamPay est en mode DEMO (max 25 XAF). Passez en mode Production dans Paramètres → Passerelle de paiement → CamPay, ou testez un forfait ≤ 25 XAF.');
        }
        hotspot_pg_campay_respond_error(Lang::T('Failed to initiate payment.') . ' ' . $errorMsg);
    }

    $trx = ORM::for_table('tbl_hotspot_payments')->create();
    $trx->transaction_id = $result['reference'];
    $trx->transaction_ref = $txref;
    $trx->amount = $amount;
    $trx->phone_number = $phone;
    $trx->plan_id = $planid;
    $trx->plan_name = $plan_name;
    $trx->mac_address = $mac_address;
    $trx->ip_address = $ip_address;
    $trx->router_name = $routername;
    $trx->voucher_code = '**********';
    $trx->transaction_status = 'pending';
    $trx->payment_gateway = 'campay';
    $trx->gateway_response = json_encode([
        'payload' => $payload,
        'response' => $result,
        'customer_name' => $_POST['fullname'] ?? 'Client Hotspot',
        'customer_address' => $_POST['address'] ?? 'Hotspot',
    ]);
    $trx->save();

    if (function_exists('hotspot_invalidate_overview_cache')) {
        hotspot_invalidate_overview_cache();
    }

    hotspot_pg_campay_respond_success($txref, $result, $phone);
}

function hotspot_pg_campay_verify()
{
    $payload = @file_get_contents('php://input');
    if ($payload) {
        @file_put_contents('pages/campay-hotspot-webhook.html', date('Y-m-d H:i:s') . '<pre>' . htmlspecialchars($payload) . '</pre>' . "\n", FILE_APPEND);
    }

    $data = json_decode($payload ?: '{}', true);
    $reference = $data['reference'] ?? ($_GET['reference'] ?? '');

    if ($reference === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No reference']);
        exit;
    }

    $trx = ORM::for_table('tbl_hotspot_payments')
        ->where('transaction_id', $reference)
        ->find_one();

    if (!$trx) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    hotspot_pg_campay_sync_transaction($trx);

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}
