<?php

/**
 * CamPay Mobile Money Payment Gateway for DYRSIA ISP
 * 
 * Supporte MTN Mobile Money et Orange Money au Cameroun
 * 
 * Documentation API: https://documenter.getpostman.com/view/2391374/T1LV8PVA
 * 
 * @author DYRSIA
 */

function campay_validate_config()
{
    global $config;
    if (empty($config['campay_username']) || empty($config['campay_password'])) {
        Message::sendTelegram("CamPay payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup CamPay payment gateway, please contact admin"));
    }
}

function campay_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'CamPay Mobile Money - Payment Gateway');
    $ui->assign('_c', $config);
    $ui->display('campay.tpl');
}

function campay_save_config()
{
    global $admin;
    
    $settings = [
        'campay_username'    => _post('campay_username'),
        'campay_password'    => _post('campay_password'),
        'campay_environment' => _post('campay_environment') ?: 'demo',
        'campay_currency'    => _post('campay_currency') ?: 'XAF',
    ];

    foreach ($settings as $key => $value) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($d) {
            $d->value = $value;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $key;
            $d->value = $value;
            $d->save();
        }
    }

    _log('[' . $admin['username'] . ']: CamPay ' . Lang::T('Settings Saved Successfully'), $admin['user_type']);
    MobileMoneyGateway::deactivateOtherMobile('campay');
    MobileMoneyGateway::syncHotspotCaptivePaymentUi();
    r2(U . 'paymentgateway/campay', 's', Lang::T('Settings Saved Successfully'));
}

function campay_get_token()
{
    global $config;
    
    $baseUrl = ($config['campay_environment'] === 'prod') 
        ? 'https://www.campay.net/api' 
        : 'https://demo.campay.net/api';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/token/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'username' => $config['campay_username'],
            'password' => $config['campay_password'],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        _log("CamPay Token Error: HTTP $httpCode - $response");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['token'] ?? null;
}

function campay_detect_operator($phone)
{
    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    if (preg_match('/^237/', $phone)) {
        $phone = substr($phone, 3);
    }
    if (preg_match('/^(67|68)/', $phone) || preg_match('/^65[0-4]/', $phone)) {
        return 'MTN';
    }
    if (preg_match('/^(69|65[5-9])/', $phone)) {
        return 'Orange';
    }
    return null;
}

function campay_minimum_amount($phone)
{
    $operator = campay_detect_operator($phone);
    if ($operator === 'Orange') {
        return 10;
    }
    if ($operator === 'MTN') {
        return 2;
    }
    return 2;
}

function campay_validate_collect_amount($phone, $amount)
{
    $min = campay_minimum_amount($phone);
    $amount = (int) $amount;
    if ($amount < $min) {
        $operator = campay_detect_operator($phone) ?: 'Mobile Money';
        return "Le montant minimum CamPay pour {$operator} est {$min} XAF. Ce forfait est à {$amount} XAF.";
    }
    return null;
}

function campay_create_transaction($trx, $user)
{
    global $config;
    
    $token = campay_get_token();
    if (!$token) {
        Message::sendTelegram("CamPay: Failed to get authentication token");
        r2(U . 'order/package', 'e', Lang::T("Payment gateway authentication failed. Please try again."));
    }
    
    $baseUrl = ($config['campay_environment'] === 'prod') 
        ? 'https://www.campay.net/api' 
        : 'https://demo.campay.net/api';
    
    $externalRef = 'DYRSIA-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $trx['id'];
    $currency = $config['campay_currency'] ?: 'XAF';
    
    // Préparer le numéro de téléphone (format 237XXXXXXXXX)
    $phone = preg_replace('/[^0-9]/', '', $user['phonenumber']);
    if (strlen($phone) === 9) {
        $phone = '237' . $phone;
    } elseif (!preg_match('/^237/', $phone)) {
        $phone = '237' . ltrim($phone, '0');
    }

    $amountError = campay_validate_collect_amount($phone, $trx['price']);
    if ($amountError) {
        r2(U . 'order/package', 'e', $amountError);
    }
    
    $payload = [
        'amount'             => (int) intval($trx['price']),
        'currency'           => $currency,
        'from'               => $phone,
        'description'        => $trx['plan_name'] . ' - ' . $config['CompanyName'],
        'external_reference' => $externalRef,
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/collect/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        _log("CamPay cURL Error: $curlError");
        Message::sendTelegram("CamPay cURL Error: $curlError");
        r2(U . 'order/package', 'e', Lang::T("Connection error. Please try again."));
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200 || empty($result['reference'])) {
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;
        _log("CamPay Collect Error: HTTP $httpCode - $errorMsg");
        Message::sendTelegram("CamPay payment initialization failed:\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to initiate payment.") . ' ' . $errorMsg);
    }
    
    // Mettre à jour l'enregistrement de transaction
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    
    if (!$d) {
        r2(U . 'order/package', 'e', Lang::T("Transaction record not found."));
    }
    
    $d->gateway_trx_id = $result['reference'];
    $d->pg_url_payment = '';
    $d->pg_request = json_encode([
        'payload' => $payload,
        'response' => $result,
        'external_ref' => $externalRef,
    ]);
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+30 MINUTES"));
    $d->save();
    
    // Rediriger vers la page de vérification du statut (paiement USSD initié sur le téléphone)
    r2(U . 'order/view/' . $trx['id'] . '/check', 'i', 
        Lang::T("Payment request sent to your phone.") . ' ' .
        Lang::T("Please confirm the payment on your mobile device, then click 'Check Status'."));
}

function campay_payment_notification()
{
    global $config;
    
    $payload = @file_get_contents("php://input");
    $logFile = "pages/campay-webhook.html";
    file_put_contents($logFile, date('Y-m-d H:i:s') . "<pre>$payload</pre>\n", FILE_APPEND);
    
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request']);
        exit();
    }
    
    $data = json_decode($payload, true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit();
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
    $reference = $data['reference'] ?? '';
    $status = $data['status'] ?? '';
    $amount = $data['amount'] ?? 0;
    $externalRef = $data['external_reference'] ?? '';
    $operator = $data['operator'] ?? '';
    $operatorRef = $data['operator_reference'] ?? '';
    
    if (empty($reference)) {
        _log("CamPay Webhook: No reference in payload");
        exit();
    }

    if (preg_match('/^ISP-SUB-(\d+)/', (string) $externalRef, $subMatch)) {
        campay_admin_subscription_webhook((int) $subMatch[1], $reference, $status, $data);
        exit();
    }

    if (preg_match('/^PPPOE-RCH-(\d+)/', (string) $externalRef, $rchMatch) || class_exists('PlanRechargePayment')) {
        if (class_exists('PlanRechargePayment')) {
            PlanRechargePayment::ensureSchema();
            $planRecharge = ORM::for_table('wifizone_plan_recharge_payments')
                ->where('gateway_reference', $reference)
                ->find_one();
            if (!$planRecharge && preg_match('/^PPPOE-RCH-(\d+)/', (string) $externalRef, $rchMatch)) {
                $planRecharge = ORM::for_table('wifizone_plan_recharge_payments')->find_one((int) $rchMatch[1]);
            }
            if ($planRecharge) {
                PlanRechargePayment::handleGatewayWebhook($reference, $status, $data);
                _log("CamPay Webhook: PPPoE plan recharge handled for $reference");
                exit();
            }
        }
    }

    $hotspotPayment = ORM::for_table('tbl_hotspot_payments')
        ->where('transaction_id', $reference)
        ->find_one();
    if ($hotspotPayment && function_exists('hotspot_pg_campay_sync_transaction')) {
        hotspot_pg_campay_sync_transaction($hotspotPayment);
        _log("CamPay Webhook: Hotspot payment handled for $reference");
        exit();
    }

    $adminPayment = ORM::for_table('admin_subscription_payments')
        ->where('reference', $reference)
        ->where('status', 'pending')
        ->find_one();
    if ($adminPayment) {
        campay_admin_subscription_webhook((int) $adminPayment->id, $reference, $status, $data);
        exit();
    }
    
    $trx = ORM::for_table('tbl_payment_gateway')
        ->where('gateway_trx_id', $reference)
        ->find_one();
    
    if (!$trx) {
        _log("CamPay Webhook: Transaction not found for reference $reference");
        exit();
    }
    
    if (in_array($trx->status, ['2', '3', '4'])) {
        _log("CamPay Webhook: Transaction $reference already processed (status: {$trx->status})");
        exit();
    }
    
    $pgRequest = json_decode($trx->pg_request, true);
    $userId = 0;
    $routerName = $trx->routers;
    $planId = $trx->plan_id;
    
    // Retrouver l'utilisateur
    $user = ORM::for_table('tbl_customers')->where('username', $trx->username)->find_one();
    if ($user) {
        $userId = $user->id;
    }
    
    if (strtoupper($status) === 'SUCCESSFUL') {
        if (!Package::rechargeUser($userId, $routerName, $planId, 'CamPay', $operator)) {
            _log("CamPay Webhook: Failed to recharge user $userId for transaction $reference");
            Message::sendTelegram("CamPay: Paiement reçu mais échec activation\nRef: $reference\nUser: {$trx->username}");
        } else {
            campay_update_transaction($trx, 2, $data, $operator, 'SUCCESSFUL');
            Message::sendTelegram("CamPay: Paiement réussi\nRef: $reference\nMontant: $amount XAF\nUser: {$trx->username}");
            _log("CamPay Webhook: Payment successful for $reference");
        }
    } elseif (strtoupper($status) === 'FAILED') {
        campay_update_transaction($trx, 4, $data, $operator, 'FAILED');
        _log("CamPay Webhook: Payment failed for $reference");
        Message::sendTelegram("CamPay: Paiement échoué\nRef: $reference\nUser: {$trx->username}");
    } else {
        _log("CamPay Webhook: Unknown status '$status' for $reference");
    }
}

function campay_update_transaction($trx, $status, $data, $channel, $response)
{
    $trx->pg_paid_response = json_encode($data);
    $trx->payment_method = 'CamPay';
    $trx->payment_channel = $channel;
    $trx->paid_date = date('Y-m-d H:i:s');
    $trx->status = $status;
    $trx->save();
}

function campay_get_status($transaction, $user)
{
    global $config;
    
    $token = campay_get_token();
    if (!$token) {
        r2(U . "order/view/" . $transaction['id'], 'd', Lang::T("Unable to verify the transaction. Authentication failed."));
        return;
    }
    
    $baseUrl = ($config['campay_environment'] === 'prod') 
        ? 'https://www.campay.net/api' 
        : 'https://demo.campay.net/api';
    
    $reference = $transaction['gateway_trx_id'];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/transaction/' . urlencode($reference) . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Token ' . $token,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        _log("CamPay Status Error: HTTP $httpCode - $response");
        r2(U . "order/view/" . $transaction['id'], 'd', Lang::T("Unable to verify the transaction, try again later."));
        return;
    }
    
    $result = json_decode($response, true);
    $status = strtoupper($result['status'] ?? 'PENDING');
    $amount = $result['amount'] ?? 0;
    $operator = $result['operator'] ?? '';
    
    if ($status === 'SUCCESSFUL') {
        if ($transaction['status'] == 2) {
            r2(U . "order/view/" . $transaction['id'], 's', Lang::T("Transaction has already been paid."));
            return;
        }
        
        if (!Package::rechargeUser($user['id'], $transaction['routers'], $transaction['plan_id'], 'CamPay', $operator)) {
            _log("CamPay: Failed to recharge user {$user['id']} for transaction {$transaction['id']}");
            r2(U . "order/view/" . $transaction['id'], 'd', Lang::T("Failed to activate your package, try again later."));
            return;
        }
        
        $trxObj = ORM::for_table('tbl_payment_gateway')->find_one($transaction['id']);
        if ($trxObj) {
            $trxObj->pg_paid_response = json_encode($result);
            $trxObj->payment_method = 'CamPay';
            $trxObj->payment_channel = $operator;
            $trxObj->paid_date = date('Y-m-d H:i:s');
            $trxObj->status = 2;
            $trxObj->save();
        }
        
        r2(U . "order/view/" . $transaction['id'], 's', Lang::T("Transaction successful.") . " ✅");
        
    } elseif ($status === 'FAILED') {
        $trxObj = ORM::for_table('tbl_payment_gateway')->find_one($transaction['id']);
        if ($trxObj) {
            $trxObj->pg_paid_response = json_encode($result);
            $trxObj->status = 4;
            $trxObj->save();
        }
        r2(U . "order/view/" . $transaction['id'], 'e', Lang::T("Transaction failed.") . " ❌");
        
    } elseif ($status === 'PENDING') {
        r2(U . "order/view/" . $transaction['id'], 'w', 
            Lang::T("Transaction is still pending.") . " ⏳ " .
            Lang::T("Please confirm the payment on your mobile device."));
        
    } else {
        _log("CamPay Status: Unknown status '$status' for reference $reference");
        r2(U . "order/view/" . $transaction['id'], 'd', Lang::T("Unknown transaction status."));
    }
}

function campay_admin_subscription_collect_data($ctx, $admin, $phone)
{
    global $config;

    campay_validate_config();

    $paymentId = (int) $ctx['payment']->id;
    $token = campay_get_token();
    if (!$token) {
        AdminSubscription::markPaymentFailed($paymentId);
        return ['ok' => false, 'message' => Lang::T('Payment gateway authentication failed. Please try again.')];
    }

    $baseUrl = ($config['campay_environment'] === 'prod')
        ? 'https://www.campay.net/api'
        : 'https://demo.campay.net/api';

    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    if (strlen($phone) === 9) {
        $phone = '237' . $phone;
    } elseif (!preg_match('/^237/', $phone)) {
        $phone = '237' . ltrim($phone, '0');
    }

    $amountError = campay_validate_collect_amount($phone, $ctx['amount']);
    if ($amountError) {
        AdminSubscription::markPaymentFailed($paymentId);
        return ['ok' => false, 'message' => $amountError];
    }

    $payload = [
        'amount' => (int) $ctx['amount'],
        'currency' => $config['campay_currency'] ?: 'XAF',
        'from' => $phone,
        'description' => $ctx['plan_label'] . ' - ' . ($config['CompanyName'] ?? 'DYRSIA'),
        'external_reference' => $ctx['external_ref'],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/collect/',
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
        AdminSubscription::markPaymentFailed($paymentId);
        return ['ok' => false, 'message' => Lang::T('Connection error. Please try again.')];
    }

    $result = json_decode($response, true);
    if ($httpCode !== 200 || empty($result['reference'])) {
        AdminSubscription::markPaymentFailed($paymentId);
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;
        return ['ok' => false, 'message' => Lang::T('Failed to initiate payment.') . ' ' . $errorMsg];
    }

    AdminSubscription::setCampayReference($paymentId, $result['reference']);

    $operator = (string) ($result['operator'] ?? '');
    $ussd = (string) ($result['ussd_code'] ?? '');
    if ($operator === '' || $ussd === '') {
        $info = MobileMoneyGateway::operatorInfoForPhone($phone, 'campay');
        if ($operator === '') {
            $operator = $info['operator'];
        }
        if ($ussd === '') {
            $ussd = $info['ussd'];
        }
    }

    MobileMoneyGateway::rememberSubscriptionUssd($paymentId, $operator, $ussd);

    return [
        'ok' => true,
        'payment_id' => $paymentId,
        'operator' => $operator,
        'ussd' => $ussd,
        'amount' => (float) $ctx['amount'],
        'plan_label' => (string) $ctx['plan_label'],
        'phone' => $phone,
    ];
}

function campay_admin_subscription_collect($ctx, $admin, $phone)
{
    $result = campay_admin_subscription_collect_data($ctx, $admin, $phone);
    if (!$result['ok']) {
        r2(getUrl('admin/subscription'), 'e', $result['message']);
    }
    MobileMoneyGateway::rememberSubscriptionUssd((int) $result['payment_id'], $result['operator'], $result['ussd']);
    r2(getUrl('admin/subscription') . '&payment_id=' . (int) $result['payment_id']);
}

function campay_admin_subscription_check_status($paymentId, $adminId)
{
    global $config;

    $payment = AdminSubscription::getPaymentForAdmin((int) $paymentId, (int) $adminId);
    if (!$payment) {
        return ['ok' => false, 'message' => Lang::T('Payment not found')];
    }
    if ($payment->status === 'paid') {
        return ['ok' => true, 'message' => Lang::T('Subscription activated successfully'), 'paid' => true];
    }
    if ($payment->status !== 'pending') {
        return ['ok' => false, 'message' => Lang::T('Transaction failed.')];
    }

    $reference = trim((string) $payment->reference);
    if ($reference === '' || strpos($reference, 'pending-') === 0 || strpos($reference, 'ISP-SUB-') === 0) {
        return ['ok' => false, 'pending' => true, 'message' => Lang::T('Transaction is still pending.') . ' ⏳'];
    }

    $token = campay_get_token();
    if (!$token) {
        return ['ok' => false, 'message' => Lang::T('Unable to verify the transaction. Authentication failed.')];
    }

    $baseUrl = ($config['campay_environment'] === 'prod')
        ? 'https://www.campay.net/api'
        : 'https://demo.campay.net/api';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/transaction/' . urlencode($reference) . '/',
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
        return ['ok' => false, 'pending' => true, 'message' => Lang::T('Unable to verify the transaction, try again later.')];
    }

    $result = json_decode($response, true);
    $status = strtoupper($result['status'] ?? 'PENDING');

    if ($status === 'SUCCESSFUL') {
        return AdminSubscription::activateFromPayment((int) $payment->id, $result);
    }
    if ($status === 'FAILED') {
        AdminSubscription::markPaymentFailed((int) $payment->id);
        return ['ok' => false, 'message' => Lang::T('Transaction failed.')];
    }

    return ['ok' => false, 'pending' => true, 'message' => Lang::T('Transaction is still pending.')];
}

function campay_admin_subscription_webhook($paymentId, $reference, $status, $data)
{
    AdminSubscription::setCampayReference((int) $paymentId, $reference);
    if (strtoupper((string) $status) === 'SUCCESSFUL') {
        AdminSubscription::activateFromPayment((int) $paymentId, $data);
        _log("CamPay ISP Subscription: Payment successful for payment #$paymentId");
    } elseif (strtoupper((string) $status) === 'FAILED') {
        AdminSubscription::markPaymentFailed((int) $paymentId);
        _log("CamPay ISP Subscription: Payment failed for payment #$paymentId");
    }
}

function campay_plan_recharge_collect_data($ctx, $admin, $phone)
{
    global $config;

    campay_validate_config();

    $token = campay_get_token();
    if (!$token) {
        return ['ok' => false, 'message' => Lang::T('Payment gateway authentication failed. Please try again.')];
    }

    $baseUrl = ($config['campay_environment'] === 'prod')
        ? 'https://www.campay.net/api'
        : 'https://demo.campay.net/api';

    $phone = preg_replace('/[^0-9]/', '', (string) $phone);
    if (strlen($phone) === 9) {
        $phone = '237' . $phone;
    } elseif (!preg_match('/^237/', $phone)) {
        $phone = '237' . ltrim($phone, '0');
    }

    $amountError = campay_validate_collect_amount($phone, $ctx['amount']);
    if ($amountError) {
        return ['ok' => false, 'message' => $amountError];
    }

    $payload = [
        'amount' => (int) $ctx['amount'],
        'currency' => $config['campay_currency'] ?: 'XAF',
        'from' => $phone,
        'description' => $ctx['plan_label'] . ' PPPoE - ' . ($config['CompanyName'] ?? 'DYRSIA'),
        'external_reference' => $ctx['external_ref'],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/collect/',
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
        return ['ok' => false, 'message' => Lang::T('Connection error. Please try again.')];
    }

    $result = json_decode($response, true);
    if ($httpCode !== 200 || empty($result['reference'])) {
        $errorMsg = $result['message'] ?? $result['detail'] ?? $response;

        return ['ok' => false, 'message' => Lang::T('Failed to initiate payment.') . ' ' . $errorMsg];
    }

    $operator = (string) ($result['operator'] ?? '');
    $ussd = (string) ($result['ussd_code'] ?? '');
    if ($operator === '' || $ussd === '') {
        $info = MobileMoneyGateway::operatorInfoForPhone($phone, 'campay');
        if ($operator === '') {
            $operator = $info['operator'];
        }
        if ($ussd === '') {
            $ussd = $info['ussd'];
        }
    }

    return [
        'ok' => true,
        'reference' => (string) $result['reference'],
        'operator' => $operator,
        'ussd' => $ussd,
        'phone' => $phone,
        'amount' => (int) $ctx['amount'],
    ];
}

function campay_plan_recharge_check_status($payment, $admin)
{
    global $config;

    $reference = trim((string) $payment->gateway_reference);
    if ($reference === '') {
        return ['status' => 'PENDING'];
    }

    $token = campay_get_token();
    if (!$token) {
        return ['status' => 'PENDING'];
    }

    $baseUrl = ($config['campay_environment'] === 'prod')
        ? 'https://www.campay.net/api'
        : 'https://demo.campay.net/api';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/transaction/' . urlencode($reference) . '/',
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
        return ['status' => 'PENDING'];
    }

    $result = json_decode($response, true);

    return [
        'status' => strtoupper((string) ($result['status'] ?? 'PENDING')),
        'operator' => (string) ($result['operator'] ?? ''),
        'raw' => $result,
    ];
}
