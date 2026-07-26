<?php

/**
 * MyPVit Mobile Money gateway for Hotspot captive portal payments.
 */

function hotspot_pg_mypvit_is_ajax()
{
    return !empty($_POST['ajax'])
        || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

function hotspot_pg_mypvit_respond_error($message)
{
    if (hotspot_pg_mypvit_is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => $message]);
        exit;
    }
    r2(getUrl('login'), 'e', $message);
}

function hotspot_pg_mypvit_respond_success($txref, $result = [])
{
    if (hotspot_pg_mypvit_is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'reference' => $result['reference_id'] ?? ($result['reference'] ?? $txref),
            'operator' => $result['operator'] ?? MyPVitGateway::detectOperator($_POST['phone'] ?? ''),
            'message' => Lang::T('Payment request sent to your phone.'),
        ]);
        exit;
    }
    r2(getUrl('login'), 's', Lang::T('Payment request sent to your phone.'));
}

function hotspot_pg_mypvit_find_transaction($transactionId, $merchantRef = '')
{
    $ids = array_values(array_unique(array_filter([
        trim((string) $transactionId),
        trim((string) $merchantRef),
    ])));
    if (!$ids) {
        return null;
    }

    foreach ($ids as $id) {
        $trx = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'mypvit')
            ->where('transaction_id', $id)
            ->find_one();
        if ($trx) {
            return $trx;
        }

        $trx = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'mypvit')
            ->where('transaction_ref', $id)
            ->find_one();
        if ($trx) {
            return $trx;
        }
    }

    foreach ($ids as $id) {
        $trx = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'mypvit')
            ->where_raw('gateway_response LIKE ?', ['%' . $id . '%'])
            ->order_by_desc('id')
            ->find_one();
        if ($trx) {
            return $trx;
        }
    }

    return null;
}

function hotspot_pg_mypvit_attach_webhook_meta($trx, $transactionId, $merchantRef, array $data)
{
    $meta = json_decode((string) $trx->gateway_response, true);
    if (!is_array($meta)) {
        $meta = [];
    }
    if ($transactionId !== '') {
        $meta['mypvit_webhook_transaction_id'] = $transactionId;
    }
    if ($merchantRef !== '') {
        $meta['mypvit_webhook_merchant_reference'] = $merchantRef;
    }
    $meta['mypvit_last_webhook'] = $data;
    $trx->gateway_response = json_encode($meta);
    $trx->save();
}

function hotspot_pg_mypvit_apply_webhook($trx, $status, array $data, $transactionId = '', $merchantRef = '')
{
    require_once dirname(__DIR__) . '/paymentgateway/mypvit.php';

    hotspot_pg_mypvit_attach_webhook_meta($trx, $transactionId, $merchantRef, $data);

    $mapped = MyPVitGateway::mapStatus($status);
    if ($mapped === 'SUCCESSFUL' && $trx->transaction_status === 'pending') {
        $operator = (string) ($data['operator'] ?? 'MyPVit');
        hotspot_pg_mypvit_activate_user($trx, $operator);
        return true;
    }
    if ($mapped === 'FAILED' && $trx->transaction_status === 'pending') {
        $trx->transaction_status = 'failed';
        $trx->save();
    }

    return false;
}

function hotspot_pg_mypvit_activate_user($trx, $operator = 'MyPVit')
{
    require_once dirname(__DIR__) . '/paymentgateway/mypvit.php';

    $gatewayResponse = json_decode((string) $trx->gateway_response, true);
    if (!is_array($gatewayResponse)) {
        $gatewayResponse = [];
    }
    $routername = $trx->router_name;
    $planid = $trx->plan_id;

    $previousGlobalTrx = $GLOBALS['trx'] ?? null;
    unset($GLOBALS['trx']);

    $networkPassword = '';

    try {
        $customer = HotspotCustomer::findOrCreate(
            $trx->phone_number,
            $gatewayResponse['customer_name'] ?? 'Client Hotspot',
            $gatewayResponse['customer_address'] ?? 'Hotspot'
        );
        $prepared = HotspotCustomer::prepareForHotspotActivation($customer);
        $customer = $prepared['customer'];
        $networkPassword = HotspotCustomer::defaultPassword();

        if (class_exists('WifiZoneTime') && $routername !== '' && $planid > 0) {
            WifiZoneTime::applyForRecharge($routername, ORM::for_table('tbl_plans')->find_one($planid), 0);
        }

        if (!Package::rechargeUser($customer->id, $routername, $planid, 'MyPVit', $operator)) {
            _log('[MyPVit Hotspot] Activation failed for trx ' . $trx->transaction_ref);
            return false;
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

    if ($expiration && function_exists('hotspot_scheduleCredentialsNotify')) {
        $expired = $expiration->expiration . ' ' . date('h:i A', strtotime($expiration->time));
        hotspot_scheduleCredentialsNotify(
            $trx->phone_number,
            $expiration->namebp,
            $customer->username,
            $expired
        );
    }

    $trx->transaction_status = 'paid';
    $trx->payment_method = 'MyPVit - ' . $operator;
    $trx->payment_date = date('Y-m-d H:i:s');
    $trx->voucher_code = $customer->username;
    $trx->save();
    if (function_exists('hotspot_invalidate_overview_cache')) {
        hotspot_invalidate_overview_cache();
    }
    return true;
}

function hotspot_pg_mypvit_sync_transaction($trx)
{
    require_once dirname(__DIR__) . '/paymentgateway/mypvit.php';
    if (!$trx || trim((string) $trx->transaction_id) === '') {
        return $trx;
    }

    $result = MyPVitGateway::fetchStatus((string) $trx->transaction_id, (string) $trx->gateway_response);
    if ($result['http_code'] !== 200 || !is_array($result['json'])) {
        return $trx;
    }

    $status = MyPVitGateway::mapStatus($result['json']['status'] ?? 'PENDING');
    $operator = $result['json']['operator'] ?? 'MyPVit';

    if ($status === 'SUCCESSFUL' && $trx->transaction_status === 'pending') {
        hotspot_pg_mypvit_activate_user($trx, $operator);
    } elseif ($status === 'FAILED' && $trx->transaction_status === 'pending') {
        $trx->transaction_status = 'failed';
        $trx->save();
    }

    return ORM::for_table('tbl_hotspot_payments')->find_one($trx->id);
}

function hotspot_processPayment_mypvit($data)
{
    global $config;

    require_once dirname(__DIR__) . '/paymentgateway/mypvit.php';

    if (!MobileMoneyGateway::isConfigured('mypvit')) {
        hotspot_pg_mypvit_respond_error(Lang::T('Payment gateway not configured. Please contact') . ' ' . ($config['CompanyName'] ?? 'admin'));
    }

    $phone = $data['phone'];
    $rawPhone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? $phone));
    if (strlen($rawPhone) >= 9) {
        $phone = MyPVitGateway::formatPhone($rawPhone);
    }
    $mac_address = $data['mac_address'];
    $ip_address = $data['ip_address'];
    $routername = function_exists('hotspot_normalize_router_name')
        ? hotspot_normalize_router_name($data['routername'] ?? '')
        : trim((string) ($data['routername'] ?? ''));
    $txref = $data['txref'];
    $planid = $data['planid'];
    $plan_name = $data['plan_name'];
    $amount = $data['amount'];

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

    $amountError = MyPVitGateway::validateAmount($amount);
    if ($amountError) {
        hotspot_pg_mypvit_respond_error($amountError);
    }

    $reference = MyPVitGateway::makeReference('HS', abs(crc32($txref)) % 999999999999);
    $payload = [
        'amount' => (float) $amount,
        'product' => substr((string) $plan_name, 0, 30),
        'reference' => $reference,
        'service' => 'RESTFUL',
        'callback_url_code' => MyPVitGateway::configValue('mypvit_callback_url_code'),
        'customer_account_number' => MyPVitGateway::formatPhone($phone),
        'merchant_operation_account_code' => MyPVitGateway::configValue('mypvit_operation_account_code'),
        'transaction_type' => 'PAYMENT',
        'owner_charge' => 'MERCHANT',
        'operator_code' => MyPVitGateway::detectOperator($phone),
        'free_info' => substr($reference, 0, 15),
    ];

    $init = MyPVitGateway::initiatePayment($payload);
    if (!$init['ok']) {
        hotspot_pg_mypvit_respond_error($init['message']);
    }

    $trx = ORM::for_table('tbl_hotspot_payments')->create();
    $trx->transaction_id = $init['reference_id'] ?: $reference;
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
    $trx->payment_gateway = 'mypvit';
    $trx->gateway_response = json_encode([
        'payload' => $payload,
        'response' => $init['raw'],
        'mypvit_reference_id' => $init['reference_id'] ?? '',
        'mypvit_merchant_reference' => $reference,
        'customer_name' => $_POST['fullname'] ?? 'Client Hotspot',
        'customer_address' => $_POST['address'] ?? 'Hotspot',
    ]);
    $trx->save();

    hotspot_pg_mypvit_respond_success($txref, array_merge($init['raw'], ['reference_id' => $init['reference_id']]));
}

function hotspot_pg_mypvit_verify()
{
    require_once dirname(__DIR__) . '/paymentgateway/mypvit.php';

    $payload = @file_get_contents('php://input');
    if ($payload) {
        @file_put_contents('pages/mypvit-hotspot-webhook.html', date('Y-m-d H:i:s') . '<pre>' . htmlspecialchars($payload) . '</pre>' . "\n", FILE_APPEND);
    }

    $data = json_decode($payload ?: '{}', true);
    $reference = $data['transactionId'] ?? ($_GET['reference'] ?? '');
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

    hotspot_pg_mypvit_sync_transaction($trx);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
