<?php

/**
 * MyPVit Mobile Money Payment Gateway
 * Documentation: https://docs.mypvit.pro/fr/intro/getting-started
 */
class MyPVitGateway
{
    public static function apiRoot()
    {
        return 'https://api.mypvit.pro';
    }

    public static function configValue($key, $default = '')
    {
        global $config;
        return trim((string) ($config[$key] ?? $default));
    }

    public static function secretKey()
    {
        return self::configValue('mypvit_secret_key');
    }

    public static function codeUrl()
    {
        return self::configValue('mypvit_code_url');
    }

    /** Code URL dédié au check status (MyPVit → APIs → CHECK STATUS v1). */
    public static function statusCodeUrl()
    {
        $status = self::configValue('mypvit_status_code_url');
        return $status !== '' ? $status : self::codeUrl();
    }

    /** Code URL dédié à renew-secret (MyPVit → APIs → RENEW SECRET KEY), distinct du code REST. */
    public static function renewSecretCodeUrl()
    {
        $renew = self::configValue('mypvit_renew_secret_code_url');
        return $renew !== '' ? $renew : self::codeUrl();
    }

    public static function headers()
    {
        return [
            'Content-Type: application/json',
            'X-Secret: ' . self::secretKey(),
            'X-Callback-MediaType: application/json',
        ];
    }

    public static function saveSetting($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
            $row->value = $value;
            $row->save();
        }
        global $config;
        $config[$key] = $value;
    }

    public static function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        $prefix = self::configValue('mypvit_phone_prefix', '241');
        if ($prefix === '') {
            $prefix = '241';
        }
        if (preg_match('/^(' . preg_quote($prefix, '/') . ')/', $phone)) {
            return $phone;
        }
        // Retirer un indicatif pays incorrect (ex. 237) avant d'appliquer +241
        if ($prefix === '241' && preg_match('/^237(\d{9})$/', $phone, $m)) {
            $phone = $m[1];
        }
        if (strlen($phone) === 9) {
            return $prefix . $phone;
        }
        return $prefix . ltrim($phone, '0');
    }

    public static function localPhoneDigits($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        $prefix = self::configValue('mypvit_phone_prefix', '241');
        if ($prefix !== '' && preg_match('/^' . preg_quote($prefix, '/') . '(\d{9})$/', $digits, $m)) {
            return $m[1];
        }
        if (strlen($digits) === 9) {
            return $digits;
        }
        if (strlen($digits) > 9) {
            return substr($digits, -9);
        }
        return $digits;
    }

    public static function detectOperator($phone)
    {
        $override = self::configValue('mypvit_default_operator_code');
        if ($override !== '') {
            return $override;
        }

        $prefix = self::configValue('mypvit_phone_prefix', '241');
        $local = self::localPhoneDigits($phone);

        if ($prefix === '241') {
            return self::detectGabonOperator($local);
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if (preg_match('/^237/', $digits)) {
            $local = substr($digits, 3);
            if (preg_match('/^(67|68)/', $local) || preg_match('/^65[0-4]/', $local)) {
                return 'CMR_MTN';
            }
            if (preg_match('/^(69|65[5-9])/', $local)) {
                return 'CMR_ORANGE';
            }
            return 'CMR_ORANGE';
        }
        return 'MOOV_MONEY';
    }

    private static function detectGabonOperator($local)
    {
        if (preg_match('/^(07|74|77|62)/', $local)) {
            return 'GAB_AIRTEL';
        }
        if (preg_match('/^(06|65|66)/', $local)) {
            return 'MOOV_MONEY';
        }
        return 'MOOV_MONEY';
    }

    public static function makeReference($prefix, $id)
    {
        $ref = strtoupper($prefix) . str_pad((string) (int) $id, 15 - strlen($prefix), '0', STR_PAD_LEFT);
        return substr($ref, 0, 15);
    }

    public static function validateAmount($amount)
    {
        $amount = (int) $amount;
        if ($amount <= 500) {
            return 'Le montant minimum MyPVit est de 501 XAF.';
        }
        return null;
    }

    public static function validateCallbackUrlCode($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return 'Code URL callback MyPVit requis (max 12 caractères, depuis MyPVit → Urls).';
        }
        if (strlen($code) > 12) {
            return 'Code URL callback invalide : utilisez le code MyPVit (max 12 caractères), pas l\'URL complète du webhook.';
        }
        if (preg_match('/^https?:\/\//i', $code)) {
            return 'Collez le code callback MyPVit, pas l\'URL ngrok. L\'URL se configure dans MyPVit → Urls.';
        }
        return null;
    }

    public static function request($method, $url, $body = null, $headers = null)
    {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => $headers ?: self::headers(),
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = is_array($body) ? json_encode($body) : (string) $body;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        return [
            'http_code' => $httpCode,
            'body' => $response,
            'error' => $curlError,
            'json' => json_decode((string) $response, true),
        ];
    }

    public static function initiatePayment(array $payload)
    {
        $codeUrl = self::codeUrl();
        if ($codeUrl === '' || self::secretKey() === '') {
            return ['ok' => false, 'message' => Lang::T('Payment gateway not configured')];
        }

        if (!empty($payload['callback_url_code'])) {
            $callbackError = self::validateCallbackUrlCode($payload['callback_url_code']);
            if ($callbackError) {
                return ['ok' => false, 'message' => $callbackError];
            }
        }
        if (!empty($payload['free_info'])) {
            $payload['free_info'] = substr((string) $payload['free_info'], 0, 15);
        }

        $url = self::apiRoot() . '/v2/' . rawurlencode($codeUrl) . '/rest';
        $result = self::request('POST', $url, $payload);
        if ($result['error']) {
            _log('MyPVit cURL error: ' . $result['error']);
            return ['ok' => false, 'message' => Lang::T('Connection error. Please try again.')];
        }

        $data = is_array($result['json']) ? $result['json'] : [];
        $status = strtoupper((string) ($data['status'] ?? ''));
        $renew = null;
        $authFailed = ($result['http_code'] === 403)
            || (($data['error'] ?? '') === 'AUTHENTICATION_FAILED')
            || stripos((string) ($data['message'] ?? ''), 'authentication failed') !== false;

        if ($authFailed) {
            $renew = self::renewSecret();
            if (!empty($renew['ok'])) {
                _log('MyPVit: secret key auto-renewed after AUTHENTICATION_FAILED');
                $result = self::request('POST', $url, $payload);
                $data = is_array($result['json']) ? $result['json'] : [];
                $status = strtoupper((string) ($data['status'] ?? ''));
            }
        }

        if (!in_array($status, ['PENDING', 'SUCCESS'], true)) {
            $message = $data['message'] ?? $result['body'];
            if ($authFailed && empty($renew['ok'])) {
                $message = 'Clé secrète MyPVit expirée. Renouvelez-la dans Réglages → Passerelle de paiement → MyPVit (bouton Renouveler la clé).';
            }
            _log('MyPVit payment init failed: HTTP ' . $result['http_code'] . ' - ' . $message);
            return ['ok' => false, 'message' => Lang::T('Failed to initiate payment.') . ' ' . $message];
        }

        return [
            'ok' => true,
            'status' => $status,
            'reference_id' => $data['reference_id'] ?? '',
            'merchant_reference_id' => $data['merchant_reference_id'] ?? ($payload['reference'] ?? ''),
            'operator' => $data['operator'] ?? '',
            'raw' => $data,
        ];
    }

    public static function merchantReferenceFromMeta($gatewayMeta)
    {
        if (is_string($gatewayMeta)) {
            $gatewayMeta = json_decode($gatewayMeta, true);
        }
        if (!is_array($gatewayMeta)) {
            return '';
        }

        $candidates = [
            $gatewayMeta['mypvit_merchant_reference'] ?? '',
            $gatewayMeta['payload']['reference'] ?? '',
            $gatewayMeta['response']['merchant_reference_id'] ?? '',
            $gatewayMeta['merchant_reference'] ?? '',
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    public static function requestStatus($transactionId)
    {
        $transactionId = trim((string) $transactionId);
        if ($transactionId === '') {
            return [
                'http_code' => 400,
                'body' => '',
                'error' => 'empty transaction id',
                'json' => ['error' => 'EMPTY_TRANSACTION_ID'],
            ];
        }

        $codeUrl = self::statusCodeUrl();
        $account = self::configValue('mypvit_operation_account_code');
        $query = http_build_query([
            'transactionId' => $transactionId,
            'accountOperationCode' => $account,
            'transactionOperation' => 'PAYMENT',
        ]);
        $url = self::apiRoot() . '/' . rawurlencode($codeUrl) . '/status?' . $query;
        $headers = [
            'X-Secret: ' . self::secretKey(),
        ];

        return self::request('GET', $url, null, $headers);
    }

    public static function isStatusNotFound($result)
    {
        if (!is_array($result)) {
            return true;
        }
        $json = $result['json'] ?? [];
        if (($result['http_code'] ?? 0) === 404) {
            return true;
        }
        if (is_array($json) && ($json['error'] ?? '') === 'TRANSACTION_NOT_FOUND') {
            return true;
        }

        return false;
    }

    /**
     * MyPVit status API accepts the merchant reference (HS..., WZ..., REF...) — not always PAY... id.
     */
    public static function fetchStatus($transactionId, $gatewayMeta = null)
    {
        $merchantRef = self::merchantReferenceFromMeta($gatewayMeta);
        $ids = array_values(array_unique(array_filter([
            trim((string) $merchantRef),
            trim((string) $transactionId),
        ])));

        $lastResult = null;
        foreach ($ids as $id) {
            $lastResult = self::requestStatus($id);
            if (!self::isStatusNotFound($lastResult)) {
                if (is_array($lastResult['json'] ?? null)) {
                    $lastResult['json']['_status_query_id'] = $id;
                }
                return $lastResult;
            }
        }

        return $lastResult ?: [
            'http_code' => 404,
            'body' => '',
            'error' => 'not found',
            'json' => ['error' => 'TRANSACTION_NOT_FOUND'],
        ];
    }

    public static function diagnoseIntegration()
    {
        $report = [
            'configured' => MobileMoneyGateway::isConfigured('mypvit'),
            'operation_account' => self::configValue('mypvit_operation_account_code'),
            'environment' => self::configValue('mypvit_environment', 'test'),
            'callback_code' => self::configValue('mypvit_callback_url_code'),
            'recent_payments' => [],
            'api_ok' => false,
            'message' => '',
        ];

        if (!$report['configured']) {
            $report['message'] = 'Configuration MyPVit incomplète.';
            return $report;
        }

        $payments = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'mypvit')
            ->order_by_desc('id')
            ->limit(5)
            ->find_many();

        foreach ($payments as $payment) {
            $merchantRef = self::merchantReferenceFromMeta($payment->gateway_response);
            $status = self::fetchStatus((string) $payment->transaction_id, $payment->gateway_response);
            $json = is_array($status['json'] ?? null) ? $status['json'] : [];
            $report['recent_payments'][] = [
                'local_id' => (int) $payment->id,
                'pay_id' => (string) $payment->transaction_id,
                'merchant_ref' => $merchantRef,
                'local_status' => (string) $payment->transaction_status,
                'mypvit_status' => (string) ($json['status'] ?? 'UNKNOWN'),
                'amount' => (int) $payment->amount,
                'date' => (string) ($json['date'] ?? $payment->payment_date ?? $payment->created_date),
                'operation_account' => (string) ($json['merchant_operation_account_code'] ?? ''),
            ];
        }

        if (!empty($report['recent_payments'])) {
            $first = $report['recent_payments'][0];
            $report['api_ok'] = $first['mypvit_status'] !== 'UNKNOWN';
            $report['message'] = $report['api_ok']
                ? 'Les transactions sont bien enregistrées chez MyPVit. Consultez MyPVit → Reporting (compte ' . ($first['operation_account'] ?: $report['operation_account']) . ').'
                : 'Impossible de retrouver la dernière transaction sur l\'API MyPVit. Vérifiez le compte d\'opération test et les codes API.';
        } else {
            $report['message'] = 'Aucun paiement hotspot MyPVit en base. Effectuez un test depuis le portail captif.';
        }

        return $report;
    }

    public static function sendCallbackAck($transactionId)
    {
        $transactionId = trim((string) $transactionId);
        if ($transactionId === '') {
            return;
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'responseCode' => 200,
            'transactionId' => $transactionId,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function mapStatus($status)
    {
        $status = strtoupper((string) $status);
        if ($status === 'SUCCESS') {
            return 'SUCCESSFUL';
        }
        if ($status === 'FAILED') {
            return 'FAILED';
        }
        return 'PENDING';
    }

    public static function renewSecret()
    {
        $codeUrl = self::renewSecretCodeUrl();
        $password = self::configValue('mypvit_api_password');
        $account = self::configValue('mypvit_operation_account_code');
        $reception = self::configValue('mypvit_secret_reception_url_code');
        if ($codeUrl === '' || $password === '' || $account === '') {
            return ['ok' => false, 'message' => 'Configuration MyPVit incomplète pour renew-secret (code renew, mot de passe, compte)'];
        }

        $v2 = self::renewSecretV2($codeUrl, $account, $password);
        if ($v2['ok']) {
            return $v2;
        }

        if ($reception === '') {
            return ['ok' => false, 'message' => 'Code URL réception clé MyPVit requis pour renew v1 (ex. Z5NBU), ou utilisez le code renew v2 depuis MyPVit → APIs.'];
        }

        return self::renewSecretV1($codeUrl, $account, $password, $reception);
    }

    private static function renewSecretV2($codeUrl, $account, $password)
    {
        $url = self::apiRoot() . '/v2/' . rawurlencode($codeUrl) . '/renew-secret';
        $fields = http_build_query([
            'operationAccountCode' => $account,
            'password' => $password,
        ]);
        $result = self::postForm($url, $fields);
        $json = $result['json'];
        $httpCode = $result['http_code'];
        $response = $result['body'];

        if ($httpCode >= 200 && $httpCode < 300 && is_array($json) && !empty($json['secret'])) {
            self::saveSetting('mypvit_secret_key', $json['secret']);
            return ['ok' => true, 'message' => 'Clé secrète MyPVit renouvelée (API v2)'];
        }

        if ($httpCode === 404) {
            return ['ok' => false, 'message' => 'renew-secret v2 introuvable pour ce code — vérifiez le code dans MyPVit → APIs → RENEW SECRET KEY v2 (attention O vs 0).'];
        }

        return ['ok' => false, 'message' => is_array($json) ? ($json['message'] ?? $response) : $response, 'http_code' => $httpCode];
    }

    private static function renewSecretV1($codeUrl, $account, $password, $reception)
    {
        $url = self::apiRoot() . '/' . rawurlencode($codeUrl) . '/renew-secret';
        $fields = http_build_query([
            'operationAccountCode' => $account,
            'receptionUrlCode' => $reception,
            'password' => $password,
        ]);
        $result = self::postForm($url, $fields);
        $json = $result['json'];
        $httpCode = $result['http_code'];
        $response = $result['body'];

        if ($httpCode >= 200 && $httpCode < 300) {
            if (is_array($json) && !empty($json['secret'])) {
                self::saveSetting('mypvit_secret_key', $json['secret']);
                return ['ok' => true, 'message' => 'Clé secrète MyPVit renouvelée (API v1)'];
            }
            return ['ok' => true, 'message' => $json['message'] ?? 'Clé secrète envoyée à votre webhook MyPVit'];
        }

        return ['ok' => false, 'message' => self::formatRenewSecretError($json, $httpCode, $response, $reception), 'http_code' => $httpCode];
    }

    private static function postForm($url, $fields)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => (string) $response,
            'json' => json_decode((string) $response, true),
        ];
    }

    private static function formatRenewSecretError($json, $httpCode, $response, $receptionCode)
    {
        $message = is_array($json) ? ($json['message'] ?? '') : '';
        if ($message === '') {
            $message = (string) $response;
        }
        if (is_array($json) && ($json['error'] ?? '') === 'API_NOT_FOUND') {
            return 'Renouvellement impossible : vérifiez le code URL réception clé ('
                . $receptionCode
                . ') dans MyPVit → Urls — type « Réception de clé secrète », URL .../callback/mypvit_secret, statut Actif. '
                . 'Renouvelez depuis MyPVit → APIs → RENEW SECRET KEY → Valider.';
        }
        return $message;
    }
}

function mypvit_validate_config()
{
    if (!MobileMoneyGateway::isConfigured('mypvit')) {
        Message::sendTelegram('MyPVit payment gateway not configured');
        r2(U . 'order/package', 'w', Lang::T('Admin has not yet setup MyPVit payment gateway, please contact admin'));
    }
}

function mypvit_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'MyPVit Mobile Money - Payment Gateway');
    $ui->assign('_c', $config);
    $ui->assign('mobile_gateway_conflict', MobileMoneyGateway::activeMobile());
    $ui->assign('mypvit_diagnostic', MyPVitGateway::diagnoseIntegration());
    $ui->display('mypvit.tpl');
}

function mypvit_save_config()
{
    global $admin;

    $settings = [
        'mypvit_environment' => _post('mypvit_environment') ?: 'test',
        'mypvit_code_url' => trim(_post('mypvit_code_url')),
        'mypvit_status_code_url' => trim(_post('mypvit_status_code_url')),
        'mypvit_renew_secret_code_url' => trim(_post('mypvit_renew_secret_code_url')),
        'mypvit_secret_key' => trim(_post('mypvit_secret_key')),
        'mypvit_operation_account_code' => trim(_post('mypvit_operation_account_code')),
        'mypvit_callback_url_code' => trim(_post('mypvit_callback_url_code')),
        'mypvit_secret_reception_url_code' => trim(_post('mypvit_secret_reception_url_code')),
        'mypvit_api_password' => trim(_post('mypvit_api_password')),
        'mypvit_phone_prefix' => trim(_post('mypvit_phone_prefix') ?: '241'),
        'mypvit_default_operator_code' => trim(_post('mypvit_default_operator_code')),
        'mypvit_currency' => _post('mypvit_currency') ?: 'XAF',
    ];

    $callbackError = MyPVitGateway::validateCallbackUrlCode($settings['mypvit_callback_url_code']);
    if ($callbackError) {
        r2(U . 'paymentgateway/mypvit', 'e', $callbackError);
    }

    foreach ($settings as $key => $value) {
        MyPVitGateway::saveSetting($key, $value);
    }

    MobileMoneyGateway::deactivateOtherMobile('mypvit');

    if (_post('renew_secret') === '1') {
        $renew = MyPVitGateway::renewSecret();
        if (!$renew['ok']) {
            r2(U . 'paymentgateway/mypvit', 'w', Lang::T('Settings Saved Successfully') . ' — ' . ($renew['message'] ?? 'Renew secret failed'));
        }
    }

    _log('[' . $admin['username'] . ']: MyPVit ' . Lang::T('Settings Saved Successfully'), $admin['user_type']);
    MobileMoneyGateway::syncHotspotCaptivePaymentUi();
    r2(U . 'paymentgateway/mypvit', 's', Lang::T('Settings Saved Successfully'));
}

function mypvit_create_transaction($trx, $user)
{
    global $config;

    mypvit_validate_config();

    $phone = MyPVitGateway::formatPhone($user['phonenumber']);
    $amountError = MyPVitGateway::validateAmount($trx['price']);
    if ($amountError) {
        r2(U . 'order/package', 'e', $amountError);
    }

    $reference = MyPVitGateway::makeReference('WZ', $trx['id']);
    $payload = [
        'amount' => (float) $trx['price'],
        'product' => substr((string) $trx['plan_name'], 0, 30),
        'reference' => $reference,
        'service' => 'RESTFUL',
        'callback_url_code' => MyPVitGateway::configValue('mypvit_callback_url_code'),
        'customer_account_number' => $phone,
        'merchant_operation_account_code' => MyPVitGateway::configValue('mypvit_operation_account_code'),
        'transaction_type' => 'PAYMENT',
        'owner_charge' => 'MERCHANT',
        'operator_code' => MyPVitGateway::detectOperator($phone),
        'free_info' => substr('WZ' . (int) $trx['id'], 0, 15),
    ];

    $init = MyPVitGateway::initiatePayment($payload);
    if (!$init['ok']) {
        Message::sendTelegram('MyPVit payment initialization failed: ' . ($init['message'] ?? ''));
        r2(U . 'order/package', 'e', $init['message']);
    }

    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    if (!$d) {
        r2(U . 'order/package', 'e', Lang::T('Transaction record not found.'));
    }

    $d->gateway_trx_id = $init['reference_id'] ?: $reference;
    $d->pg_url_payment = '';
    $d->pg_request = json_encode([
        'payload' => $payload,
        'response' => $init['raw'],
        'merchant_reference' => $reference,
    ]);
    $d->expired_date = date('Y-m-d H:i:s', strtotime('+30 MINUTES'));
    $d->save();

    r2(
        U . 'order/view/' . $trx['id'] . '/check',
        'i',
        Lang::T('Payment request sent to your phone.') . ' ' .
        Lang::T('Please confirm the payment on your mobile device, then click \'Check Status\'.')
    );
}

function mypvit_payment_notification()
{
    ob_start();
    $payload = @file_get_contents('php://input');
    global $root_path;
    $logDir = ($root_path ?? dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR) . 'pages';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents(
        $logDir . DIRECTORY_SEPARATOR . 'mypvit-webhook.html',
        date('Y-m-d H:i:s') . ' ACK-required<pre>' . htmlspecialchars((string) $payload) . '</pre>' . "\n",
        FILE_APPEND
    );

    $data = json_decode((string) $payload, true);
    if (!is_array($data)) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $transactionId = (string) ($data['transactionId'] ?? '');
    $merchantRef = (string) ($data['merchantReferenceId'] ?? '');
    $status = strtoupper((string) ($data['status'] ?? ''));

    if ($merchantRef === '' && $transactionId === '') {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Missing transaction reference']);
        exit;
    }

    try {
        require_once dirname(__DIR__) . '/plugin/hotspot_pg-mypvit.php';
        $hotspotTrx = hotspot_pg_mypvit_find_transaction($transactionId, $merchantRef);
        if ($hotspotTrx) {
            hotspot_pg_mypvit_apply_webhook($hotspotTrx, $status, $data, $transactionId, $merchantRef);
        } elseif (preg_match('/^ISPSUB(\d+)/', $merchantRef, $match)) {
            mypvit_admin_subscription_webhook((int) $match[1], $transactionId, MyPVitGateway::mapStatus($status), $data);
        } else {
            $adminPayment = ORM::for_table('admin_subscription_payments')
                ->where('reference', $transactionId)
                ->where('status', 'pending')
                ->find_one();
            if ($adminPayment) {
                mypvit_admin_subscription_webhook((int) $adminPayment->id, $transactionId, MyPVitGateway::mapStatus($status), $data);
            } else {
                $trx = null;
                if ($transactionId !== '') {
                    $trx = ORM::for_table('tbl_payment_gateway')->where('gateway_trx_id', $transactionId)->find_one();
                }
                if (!$trx && $merchantRef !== '') {
                    $trx = ORM::for_table('tbl_payment_gateway')
                        ->where_raw('pg_request LIKE ?', ['%' . $merchantRef . '%'])
                        ->find_one();
                }
                if (!$trx) {
                    _log('MyPVit Webhook: transaction not found for ' . $transactionId . ' / ' . $merchantRef);
                } elseif (!in_array($trx->status, ['2', '3', '4'], true)) {
                    $user = ORM::for_table('tbl_customers')->where('username', $trx->username)->find_one();
                    $userId = $user ? (int) $user->id : 0;
                    $operator = $data['operator'] ?? MyPVitGateway::detectOperator($user['phonenumber'] ?? '');

                    if ($status === 'SUCCESS') {
                        if (!Package::rechargeUser($userId, $trx->routers, $trx->plan_id, 'MyPVit', $operator)) {
                            Message::sendTelegram("MyPVit: paiement reçu mais activation échouée\nRef: $transactionId");
                        } else {
                            mypvit_update_transaction($trx, 2, $data, $operator);
                        }
                    } elseif ($status === 'FAILED') {
                        mypvit_update_transaction($trx, 4, $data, $operator);
                    }
                }
            }
        }
    } catch (Throwable $e) {
        _log('MyPVit Webhook error: ' . WifiZoneSecurity::formatExceptionForLog($e));
    }

    ob_end_clean();
    MyPVitGateway::sendCallbackAck($transactionId);
    exit;
}

function mypvit_update_transaction($trx, $status, $data, $channel)
{
    $trx->pg_paid_response = json_encode($data);
    $trx->payment_method = 'MyPVit';
    $trx->payment_channel = $channel;
    $trx->paid_date = date('Y-m-d H:i:s');
    $trx->status = $status;
    $trx->save();
}

function mypvit_get_status($transaction, $user)
{
    $transactionId = trim((string) ($transaction['gateway_trx_id'] ?? ''));
    if ($transactionId === '') {
        $pgRequest = json_decode((string) ($transaction['pg_request'] ?? ''), true);
        $transactionId = (string) ($pgRequest['response']['reference_id'] ?? '');
    }
    if ($transactionId === '') {
        r2(U . 'order/view/' . $transaction['id'], 'd', Lang::T('Unable to verify the transaction, try again later.'));
        return;
    }

    $result = MyPVitGateway::fetchStatus($transactionId, $transaction['pg_request'] ?? '');
    if ($result['http_code'] !== 200 || !is_array($result['json'])) {
        r2(U . 'order/view/' . $transaction['id'], 'd', Lang::T('Unable to verify the transaction, try again later.'));
        return;
    }

    $data = $result['json'];
    $status = MyPVitGateway::mapStatus($data['status'] ?? 'PENDING');
    $operator = $data['operator'] ?? MyPVitGateway::detectOperator($user['phonenumber'] ?? '');

    if ($status === 'SUCCESSFUL') {
        if ((int) $transaction['status'] === 2) {
            r2(U . 'order/view/' . $transaction['id'], 's', Lang::T('Transaction has already been paid.'));
            return;
        }
        if (!Package::rechargeUser($user['id'], $transaction['routers'], $transaction['plan_id'], 'MyPVit', $operator)) {
            r2(U . 'order/view/' . $transaction['id'], 'd', Lang::T('Failed to activate your package, try again later.'));
            return;
        }
        $trxObj = ORM::for_table('tbl_payment_gateway')->find_one($transaction['id']);
        if ($trxObj) {
            mypvit_update_transaction($trxObj, 2, $data, $operator);
        }
        r2(U . 'order/view/' . $transaction['id'], 's', Lang::T('Transaction successful.') . ' ✅');
    } elseif ($status === 'FAILED') {
        $trxObj = ORM::for_table('tbl_payment_gateway')->find_one($transaction['id']);
        if ($trxObj) {
            mypvit_update_transaction($trxObj, 4, $data, $operator);
        }
        r2(U . 'order/view/' . $transaction['id'], 'e', Lang::T('Transaction failed.') . ' ❌');
    } else {
        r2(
            U . 'order/view/' . $transaction['id'],
            'w',
            Lang::T('Transaction is still pending.') . ' ⏳ ' .
            Lang::T('Please confirm the payment on your mobile device.')
        );
    }
}

function mypvit_admin_subscription_collect_data($ctx, $admin, $phone)
{
    mypvit_validate_config();

    $paymentId = (int) $ctx['payment']->id;
    $phone = MyPVitGateway::formatPhone($phone);
    $amountError = MyPVitGateway::validateAmount($ctx['amount']);
    if ($amountError) {
        AdminSubscription::markPaymentFailed($paymentId);
        return ['ok' => false, 'message' => $amountError];
    }

    $reference = MyPVitGateway::makeReference('ISPSUB', $paymentId);
    $payload = [
        'amount' => (float) $ctx['amount'],
        'product' => substr((string) $ctx['plan_label'], 0, 30),
        'reference' => $reference,
        'service' => 'RESTFUL',
        'callback_url_code' => MyPVitGateway::configValue('mypvit_callback_url_code'),
        'customer_account_number' => $phone,
        'merchant_operation_account_code' => MyPVitGateway::configValue('mypvit_operation_account_code'),
        'transaction_type' => 'PAYMENT',
        'owner_charge' => 'MERCHANT',
        'operator_code' => MyPVitGateway::detectOperator($phone),
        'free_info' => substr('SUB' . $paymentId, 0, 15),
    ];

    $init = MyPVitGateway::initiatePayment($payload);
    if (!$init['ok']) {
        AdminSubscription::markPaymentFailed($paymentId);
        return ['ok' => false, 'message' => $init['message']];
    }

    AdminSubscription::setCampayReference($paymentId, $init['reference_id'] ?: $reference);
    $ussdInfo = MobileMoneyGateway::operatorInfoForPhone($phone, 'mypvit');

    MobileMoneyGateway::rememberSubscriptionUssd($paymentId, $ussdInfo['operator'], $ussdInfo['ussd']);

    return [
        'ok' => true,
        'payment_id' => $paymentId,
        'operator' => $ussdInfo['operator'],
        'ussd' => $ussdInfo['ussd'],
        'amount' => (float) $ctx['amount'],
        'plan_label' => (string) $ctx['plan_label'],
        'phone' => $phone,
    ];
}

function mypvit_admin_subscription_collect($ctx, $admin, $phone)
{
    $result = mypvit_admin_subscription_collect_data($ctx, $admin, $phone);
    if (!$result['ok']) {
        r2(getUrl('admin/subscription'), 'e', $result['message']);
    }
    MobileMoneyGateway::rememberSubscriptionUssd((int) $result['payment_id'], $result['operator'], $result['ussd']);
    r2(getUrl('admin/subscription') . '&payment_id=' . (int) $result['payment_id']);
}

function mypvit_admin_subscription_check_status($paymentId, $adminId)
{
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
    if ($reference === '' || strpos($reference, 'pending-') === 0) {
        return ['ok' => false, 'pending' => true, 'message' => Lang::T('Transaction is still pending.') . ' ⏳'];
    }

    $result = MyPVitGateway::fetchStatus($reference, '');
    if ($result['http_code'] !== 200 || !is_array($result['json'])) {
        return ['ok' => false, 'pending' => true, 'message' => Lang::T('Unable to verify the transaction, try again later.')];
    }

    $status = MyPVitGateway::mapStatus($result['json']['status'] ?? 'PENDING');
    if ($status === 'SUCCESSFUL') {
        return AdminSubscription::activateFromPayment((int) $payment->id, $result['json']);
    }
    if ($status === 'FAILED') {
        AdminSubscription::markPaymentFailed((int) $payment->id);
        return ['ok' => false, 'message' => Lang::T('Transaction failed.')];
    }

    return ['ok' => false, 'pending' => true, 'message' => Lang::T('Transaction is still pending.')];
}

function mypvit_admin_subscription_webhook($paymentId, $reference, $status, $data)
{
    if ($reference !== '') {
        AdminSubscription::setCampayReference((int) $paymentId, $reference);
    }
    if ($status === 'SUCCESSFUL') {
        AdminSubscription::activateFromPayment((int) $paymentId, $data);
        _log("MyPVit ISP Subscription: payment successful for payment #$paymentId");
    } elseif ($status === 'FAILED') {
        AdminSubscription::markPaymentFailed((int) $paymentId);
        _log("MyPVit ISP Subscription: payment failed for payment #$paymentId");
    }
}

function mypvit_secret_delivery()
{
    $payload = @file_get_contents('php://input');
    @file_put_contents('pages/mypvit-secret.html', date('Y-m-d H:i:s') . '<pre>' . htmlspecialchars((string) $payload) . '</pre>' . "\n", FILE_APPEND);

    $data = json_decode((string) $payload, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $secret = trim((string) ($data['secret'] ?? $data['secret_key'] ?? ''));
    if ($secret !== '') {
        MyPVitGateway::saveSetting('mypvit_secret_key', $secret);
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
