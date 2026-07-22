<?php

function pppoe_pg_campay_respond_error($message)
{
    if (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message]);
        exit;
    }
    header('Location: ' . U . 'plugin/pppoe_verify&message=' . urlencode($message));
    exit;
}

function pppoe_pg_campay_respond_success($txref, $result = [])
{
    if (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'reference' => $txref,
            'operator' => $result['operator'] ?? '',
            'ussd_code' => $result['ussd_code'] ?? '',
        ]);
        exit;
    }
    header('Location: ' . U . 'plugin/pppoe_verify&reference=' . urlencode($txref));
    exit;
}

function pppoe_pg_campay_sync_transaction($trx)
{
    if (!$trx || empty($trx->transaction_id)) {
        return $trx;
    }
    if (in_array($trx->transaction_status, ['paid', 'failed', 'cancelled'], true)) {
        return $trx;
    }
    if (!function_exists('hotspot_pg_campay_get_token')) {
        return $trx;
    }

    $token = hotspot_pg_campay_get_token();
    if (!$token) {
        return $trx;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => hotspot_pg_campay_api_base() . '/transaction/' . urlencode($trx->transaction_id) . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return $trx;
    }

    $result = json_decode($response, true);
    $status = strtoupper($result['status'] ?? 'PENDING');
    $operator = $result['operator'] ?? 'CamPay';
    $trx->gateway_response = json_encode(array_merge(
        json_decode($trx->gateway_response ?? '{}', true) ?: [],
        ['campay_sync' => $result]
    ));

    if ($status === 'SUCCESSFUL') {
        pppoe_activate_after_payment($trx, $operator);
    } elseif ($status === 'FAILED') {
        $trx->transaction_status = 'failed';
        $trx->payment_date = date('Y-m-d H:i:s');
        $trx->save();
    } else {
        $trx->save();
    }

    return ORM::for_table('tbl_hotspot_payments')->find_one($trx->id);
}

function pppoe_processPayment_campay($data)
{
    global $config;

    if (!function_exists('campay_validate_collect_amount')) {
        require_once dirname(__DIR__) . '/paymentgateway/campay.php';
    }
    if (!function_exists('hotspot_pg_campay_get_token')) {
        require_once __DIR__ . '/hotspot_pg-campay.php';
    }

    $phone = $data['phone'];
    $routername = $data['routername'];
    $txref = $data['txref'];
    $planid = $data['planid'];
    $plan_name = $data['plan_name'];
    $amount = $data['amount'];
    $pppoeUsername = $data['pppoe_username'];

    if (empty($config['campay_username']) || empty($config['campay_password'])) {
        pppoe_pg_campay_respond_error(Lang::T('Payment gateway not configured. Please contact') . ' ' . ($config['CompanyName'] ?? 'admin'));
    }

    $customer = pppoe_find_customer_for_renewal($pppoeUsername, $phone);
    if (!$customer) {
        pppoe_pg_campay_respond_error('Identifiant PPPoE introuvable. Vérifiez votre login ou contactez le support.');
    }

    if (!pppoe_customer_can_renew($customer)) {
        pppoe_pg_campay_respond_error('Vous avez déjà un forfait PPPoE actif.');
    }

    $amountError = campay_validate_collect_amount($phone, $amount);
    if ($amountError) {
        pppoe_pg_campay_respond_error($amountError);
    }

    $token = hotspot_pg_campay_get_token();
    if (!$token) {
        pppoe_pg_campay_respond_error(Lang::T('Payment gateway authentication failed. Please try again.'));
    }

    $campayPhone = hotspot_pg_campay_format_phone($phone);
    $currency = $config['campay_currency'] ?? 'XAF';
    $payload = [
        'amount' => (int) intval($amount),
        'currency' => $currency,
        'from' => $campayPhone,
        'description' => 'PPPoE ' . $plan_name . ' - ' . ($config['CompanyName'] ?? 'WifiZone'),
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
        pppoe_pg_campay_respond_error(Lang::T('Connection error. Please try again.'));
    }

    $result = json_decode($response, true);
    if ($httpCode !== 200 || empty($result['reference'])) {
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;
        pppoe_pg_campay_respond_error(Lang::T('Failed to initiate payment.') . ' ' . $errorMsg);
    }

    $trx = ORM::for_table('tbl_hotspot_payments')->create();
    $trx->transaction_id = $result['reference'];
    $trx->transaction_ref = $txref;
    $trx->amount = $amount;
    $trx->phone_number = $phone;
    $trx->plan_id = $planid;
    $trx->plan_name = $plan_name;
    $trx->mac_address = '';
    $trx->ip_address = $data['ip_address'] ?? '';
    $trx->router_name = $routername;
    $trx->voucher_code = $pppoeUsername;
    $trx->transaction_status = 'pending';
    $trx->payment_gateway = 'campay';
    $trx->gateway_response = json_encode([
        'service_type' => 'PPPOE',
        'pppoe_username' => $pppoeUsername,
        'customer_id' => $customer->id,
        'payload' => $payload,
        'response' => $result,
    ]);
    $trx->save();

    pppoe_pg_campay_respond_success($txref, $result);
}
