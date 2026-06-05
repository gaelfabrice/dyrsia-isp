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
    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    if (strlen($phone) === 9) {
        return '237' . $phone;
    }
    if (!preg_match('/^237/', $phone) && strlen($phone) >= 9) {
        return '237' . ltrim($phone, '0');
    }
    return $phone;
}

function hotspot_pg_campay_activate_user($trx, $operator = 'CamPay')
{
    $phone = $trx->phone_number;
    $fullname = 'Hotspot User';
    $address = 'Hotspot';
    $gatewayMeta = json_decode($trx->gateway_response ?? '{}', true);
    if (!empty($gatewayMeta['customer_name'])) {
        $fullname = $gatewayMeta['customer_name'];
    }
    if (!empty($gatewayMeta['customer_address'])) {
        $address = $gatewayMeta['customer_address'];
    }
    $routername = $trx->router_name;
    $planid = $trx->plan_id;
    $mac_address = $trx->mac_address;

    if (!function_exists('hotspot_cleanMac')) {
        function hotspot_cleanMac($mac)
        {
            return strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', (string) $mac));
        }
    }

    $voucherCode = hotspot_cleanMac($mac_address);
    $username = $voucherCode;
    $password = $voucherCode;
    $formattedPhone = Lang::phoneFormat($phone);

    $serverHost = $_SERVER['HTTP_HOST'] ?? 'hotspot.local';
    $email = ($serverHost === 'localhost') ? "$phone@$serverHost.com" : "$phone@$serverHost";

    $customer = ORM::for_table('tbl_customers')
        ->where_raw('(username = ? OR phonenumber = ?)', [$username, $formattedPhone])
        ->find_one();

    if (!$customer) {
        $customer = ORM::for_table('tbl_customers')->create();
        $customer->username = $username;
        $customer->password = $password;
        $customer->fullname = $fullname !== '' ? $fullname : 'Hotspot User';
        $customer->address = $address !== '' ? $address : 'N/A';
        $customer->phonenumber = $formattedPhone;
        $customer->email = $email;
        $customer->pppoe_password = '0';
        $customer->service_type = 'Hotspot';
        $customer->status = 'Active';
    } else {
        $customer->username = $username;
        $customer->password = $password;
        if ($fullname !== '') {
            $customer->fullname = $fullname;
        }
        if ($address !== '') {
            $customer->address = $address;
        }
        if ($phone !== '') {
            $customer->phonenumber = $formattedPhone;
        }
    }
    $customer->save();

    if (!Package::rechargeUser($customer->id, $routername, $planid, 'CamPay', $operator)) {
        _log('[CamPay Hotspot] Activation failed for trx ' . $trx->transaction_ref);
        return false;
    }

    $expiration = ORM::for_table('tbl_user_recharges')
        ->where('plan_id', $planid)
        ->where('username', $customer['username'])
        ->where('status', 'on')
        ->find_one();

    $expired = date('Y-m-d H:i:s', strtotime('+1 day'));
    if ($expiration) {
        $expired = $expiration->expiration . ' ' . date('h:i A', strtotime($expiration->time));
        if (function_exists('hotspot_sendMessage')) {
            hotspot_sendMessage($phone, $expiration->namebp, $voucherCode, $expiration->expiration);
        }
    }

    $trx->voucher_code = $username;
    $trx->payment_method = 'CamPay - ' . $operator;
    $trx->payment_date = date('Y-m-d H:i:s');
    $trx->transaction_status = 'paid';
    $trx->expired_date = date('Y-m-d H:i:s', strtotime($expired));
    $trx->save();

    return true;
}

function hotspot_pg_campay_sync_transaction($trx)
{
    if (!$trx || empty($trx->transaction_id)) {
        return $trx;
    }

    if (in_array($trx->transaction_status, ['paid', 'failed', 'cancelled'], true)) {
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

    $trx->gateway_response = json_encode($result);

    if ($status === 'SUCCESSFUL') {
        if (!hotspot_pg_campay_activate_user($trx, $operator)) {
            $trx->transaction_status = 'paid';
            $trx->save();
        }
    } elseif ($status === 'FAILED') {
        $trx->transaction_status = 'failed';
        $trx->payment_date = date('Y-m-d H:i:s');
        $trx->save();
    } else {
        $trx->save();
    }

    return ORM::for_table('tbl_hotspot_payments')->find_one($trx->id);
}

function hotspot_processPayment_campay($data)
{
    global $config;

    $phone = $data['phone'];
    $mac_address = $data['mac_address'];
    $ip_address = $data['ip_address'];
    $routername = $data['routername'];
    $txref = $data['txref'];
    $planid = $data['planid'];
    $plan_name = $data['plan_name'];
    $amount = $data['amount'];

    if (empty($config['campay_username']) || empty($config['campay_password'])) {
        $message = urlencode(Lang::T('Payment gateway not configured. Please contact') . ' ' . ($config['CompanyName'] ?? 'admin'));
        Message::sendTelegram('CamPay Hotspot: gateway not configured');
        header('Location: ' . U . 'plugin/hotspot_verify&message=' . $message);
        exit;
    }

    if (!function_exists('hotspot_cleanMac')) {
        function hotspot_cleanMac($mac)
        {
            return strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', (string) $mac));
        }
    }

    $username = hotspot_cleanMac($mac_address);
    $formattedPhone = Lang::phoneFormat($phone);
    $customerCheck = ORM::for_table('tbl_customers')
        ->where_raw('(username = ? OR phonenumber = ?)', [$username, $formattedPhone])
        ->find_one();

    if ($customerCheck) {
        $activePlan = ORM::for_table('tbl_user_recharges')
            ->where('username', $customerCheck->username)
            ->where('status', 'on')
            ->find_one();
        if ($activePlan) {
            $message = urlencode(Lang::T('You already have an active plan for this username/phone number.'));
            header('Location: ' . U . 'plugin/hotspot_verify&message=' . $message);
            exit;
        }
    }

    $token = hotspot_pg_campay_get_token();
    if (!$token) {
        $message = urlencode(Lang::T('Payment gateway authentication failed. Please try again.'));
        header('Location: ' . U . 'plugin/hotspot_verify&message=' . $message);
        exit;
    }

    $campayPhone = hotspot_pg_campay_format_phone($phone);
    $currency = $config['campay_currency'] ?? 'XAF';
    $payload = [
        'amount' => (string) intval($amount),
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
        $message = urlencode(Lang::T('Connection error. Please try again.'));
        header('Location: ' . U . 'plugin/hotspot_verify&message=' . $message);
        exit;
    }

    $result = json_decode($response, true);
    if ($httpCode !== 200 || empty($result['reference'])) {
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;
        _log('CamPay Hotspot collect failed: HTTP ' . $httpCode . ' - ' . $errorMsg);
        Message::sendTelegram("CamPay Hotspot collect failed:\n" . json_encode($result, JSON_PRETTY_PRINT));
        $message = urlencode(Lang::T('Failed to initiate payment.') . ' ' . $errorMsg);
        header('Location: ' . U . 'plugin/hotspot_verify&message=' . $message);
        exit;
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

    header('Location: ' . U . 'plugin/hotspot_verify&reference=' . urlencode($txref));
    exit;
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
