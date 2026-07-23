<?php

/**
 * PPPoE admin recharge — Cash / XAF 0 (SuperAdmin) + Mobile Money modal flow.
 */
class PlanRechargePayment
{
    public const METHOD_CASH = 'cash';
    public const METHOD_ZERO = 'zero';
    public const METHOD_MOBILE_MONEY = 'mobile_money';

    public static function ensureSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS wifizone_plan_recharge_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            customer_id INT NOT NULL,
            plan_id INT NOT NULL,
            server VARCHAR(128) NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            using_method VARCHAR(32) NOT NULL DEFAULT 'mobile_money',
            gateway VARCHAR(32) NOT NULL DEFAULT '',
            gateway_reference VARCHAR(128) DEFAULT NULL,
            phone VARCHAR(32) DEFAULT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            payload MEDIUMTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME NULL,
            KEY idx_admin (admin_id),
            KEY idx_status (status),
            KEY idx_reference (gateway_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** @return list<array{value: string, label: string}> */
    public static function pppoePaymentOptions($adminType)
    {
        if ($adminType === 'SuperAdmin') {
            global $config;
            $currency = trim((string) ($config['currency_code'] ?? 'XAF'));

            return [
                ['value' => self::METHOD_CASH, 'label' => Lang::T('Cash')],
                ['value' => self::METHOD_ZERO, 'label' => $currency . ' 0'],
                ['value' => self::METHOD_MOBILE_MONEY, 'label' => Lang::T('Mobile Money')],
            ];
        }

        return [
            ['value' => self::METHOD_MOBILE_MONEY, 'label' => Lang::T('Mobile Money')],
        ];
    }

    public static function assignRechargeUi($ui, $admin, $planType = '')
    {
        global $config;
        $legacyUsings = explode(',', $config['payment_usings'] ?? '');
        $legacyUsings = array_values(array_filter(array_unique(array_map('trim', $legacyUsings))));
        if (count($legacyUsings) === 0) {
            $legacyUsings[] = Lang::T('Cash');
        }

        $ui->assign('legacy_usings', $legacyUsings);
        $ui->assign('pppoe_payment_options', self::pppoePaymentOptions($admin['user_type']));
        $ui->assign('mobile_gateway_label', self::gatewayLabel());
        $ui->assign('mobile_gateway_configured', MobileMoneyGateway::isConfigured());
        $ui->assign('recharge_plan_type', strtoupper(trim((string) $planType)));
        $ui->assign('is_superadmin_recharge', ($admin['user_type'] ?? '') === 'SuperAdmin');
    }

    public static function defaultPppoeUsingForAdmin($adminType)
    {
        if ($adminType === 'SuperAdmin') {
            return self::METHOD_CASH;
        }

        return self::METHOD_MOBILE_MONEY;
    }

    public static function isSuccessfulGatewayStatus($status)
    {
        $status = strtoupper(trim((string) $status));

        return in_array($status, ['SUCCESSFUL', 'SUCCESS', 'COMPLETED', 'PAID', 'APPROVED'], true);
    }

    public static function isFailedGatewayStatus($status)
    {
        $status = strtoupper(trim((string) $status));

        return in_array($status, ['FAILED', 'CANCELLED', 'CANCELED', 'REJECTED', 'DECLINED'], true);
    }

    public static function adminCanRechargeCustomer($admin, $customerId, $plan)
    {
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return true;
        }

        $adminId = (int) ($admin['id'] ?? 0);
        $customer = ORM::for_table('tbl_customers')->find_one((int) $customerId);
        if ($customer && (int) ($customer->admin_id ?? 0) === $adminId) {
            return true;
        }

        $planAdminId = is_array($plan) ? (int) ($plan['admin_id'] ?? 0) : (int) ($plan->admin_id ?? 0);
        if ($planAdminId === $adminId) {
            return true;
        }

        if (in_array($admin['user_type'] ?? '', ['Agent', 'Sales'], true)) {
            $rootId = (int) ($admin['root'] ?? 0);
            if ($rootId > 0 && $customer && (int) ($customer->admin_id ?? 0) === $rootId) {
                return true;
            }
        }

        return false;
    }

    public static function findPaymentForAdmin($paymentId, $admin)
    {
        self::ensureSchema();
        $payment = ORM::for_table('wifizone_plan_recharge_payments')->find_one((int) $paymentId);
        if (!$payment) {
            return null;
        }
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return $payment;
        }
        if ((int) $payment->admin_id === (int) ($admin['id'] ?? 0)) {
            return $payment;
        }

        return null;
    }

    public static function gatewayLabel()
    {
        $gateway = MobileMoneyGateway::activeMobile();
        if ($gateway === 'campay') {
            return 'CamPay';
        }
        if ($gateway === 'mypvit') {
            return 'MyPVit';
        }

        return ucfirst($gateway !== '' ? $gateway : 'Mobile Money');
    }

    public static function isMobileMoneyMethod($using)
    {
        return strtolower(trim((string) $using)) === self::METHOD_MOBILE_MONEY;
    }

    /** @return array{plan: array, cust: array, tax: float, add_cost: float, bills: array, total: float, using: string} */
    public static function buildRechargeContext($customerId, $planId, $using, $admin)
    {
        $cust = User::_info($customerId);
        $plan = ORM::for_table('tbl_plans')->find_one($planId);
        if (!$cust || !$plan) {
            throw new InvalidArgumentException(Lang::T('Customer or plan not found'));
        }

        if (!self::adminCanRechargeCustomer($admin, $customerId, $plan)) {
            throw new InvalidArgumentException(Lang::T('You do not have permission to access this page'));
        }

        if (strtoupper((string) $plan['type']) !== 'PPPOE') {
            throw new InvalidArgumentException(Lang::T('This payment flow is only available for PPPoE plans'));
        }

        list($bills, $add_cost) = User::getBills($customerId);
        $add_inv = User::getAttribute('Invoice', $customerId);
        if (!empty($add_inv)) {
            $plan['price'] = $add_inv;
        }

        global $config;
        $tax_enable = ($config['enable_tax'] ?? 'no') === 'yes';
        $tax_rate_setting = $config['tax_rate'] ?? null;
        $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;
        $tax_rate = ($tax_rate_setting === 'custom') ? $custom_tax_rate : $tax_rate_setting;
        $tax = $tax_enable ? Package::tax($plan['price'], $tax_rate) : 0;

        $using = strtolower(trim((string) $using));
        if ($using === self::METHOD_ZERO) {
            $total = 0;
        } elseif ($using === 'balance' && ($config['enable_balance'] ?? 'no') === 'yes') {
            $total = $plan['price'] + $add_cost + $tax;
            if ($cust['balance'] < $total) {
                throw new InvalidArgumentException(Lang::T('insufficient balance'));
            }
        } else {
            $total = $plan['price'] + $add_cost + $tax;
        }

        return [
            'plan' => $plan->as_array(),
            'cust' => $cust,
            'tax' => (float) $tax,
            'add_cost' => (float) $add_cost,
            'bills' => $bills,
            'total' => (float) $total,
            'using' => $using,
            'add_inv' => $add_inv,
        ];
    }

    public static function assertPppoePaymentAllowed($using, $adminType, $planType)
    {
        if (strtoupper(trim((string) $planType)) !== 'PPPOE') {
            return;
        }

        $allowed = array_column(self::pppoePaymentOptions($adminType), 'value');
        $using = strtolower(trim((string) $using));
        if ($using === 'balance') {
            return;
        }
        if (!in_array($using, $allowed, true)) {
            throw new InvalidArgumentException(Lang::T('Invalid payment method for PPPoE recharge'));
        }
    }

    public static function createPendingPayment($admin, $customerId, $planId, $server, $using, $amount)
    {
        self::ensureSchema();
        $gateway = MobileMoneyGateway::activeMobile();
        if ($gateway === '') {
            throw new RuntimeException(Lang::T('Payment gateway not configured. Please contact admin'));
        }

        $row = ORM::for_table('wifizone_plan_recharge_payments')->create();
        $row->admin_id = (int) $admin['id'];
        $row->customer_id = (int) $customerId;
        $row->plan_id = (int) $planId;
        $row->server = trim((string) $server);
        $row->amount = round((float) $amount, 2);
        $row->using_method = self::METHOD_MOBILE_MONEY;
        $row->gateway = $gateway;
        $row->status = 'pending';
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();

        return $row;
    }

    public static function findReusablePendingPayment($admin, $customerId, $planId, $server)
    {
        self::ensureSchema();
        $cutoff = date('Y-m-d H:i:s', time() - 3600);

        return ORM::for_table('wifizone_plan_recharge_payments')
            ->where('admin_id', (int) $admin['id'])
            ->where('customer_id', (int) $customerId)
            ->where('plan_id', (int) $planId)
            ->where('server', trim((string) $server))
            ->where_in('status', ['pending', 'failed'])
            ->where_raw('created_at >= ?', [$cutoff])
            ->order_by_desc('id')
            ->find_one();
    }

    /** @return array<string, mixed> */
    public static function collectOrResumePayment($admin, $customerId, $planId, $server, $phone)
    {
        $ctx = self::buildRechargeContext($customerId, $planId, self::METHOD_MOBILE_MONEY, $admin);
        if ($ctx['total'] <= 0) {
            throw new InvalidArgumentException(Lang::T('Invalid subscription amount'));
        }

        $existing = self::findReusablePendingPayment($admin, $customerId, $planId, $server);
        if ($existing && trim((string) $existing->gateway_reference) !== '') {
            if ($existing->status === 'failed') {
                $existing->status = 'pending';
                $existing->save();
            }

            $check = self::checkMobileStatus((int) $existing->id, $admin);
            $check['payment_id'] = (int) $existing->id;
            if (($check['status'] ?? '') === 'paid') {
                return $check;
            }

            $payload = json_decode((string) ($existing->payload ?? ''), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            return [
                'ok' => true,
                'payment_id' => (int) $existing->id,
                'operator' => (string) ($payload['operator'] ?? ''),
                'ussd' => (string) ($payload['ussd'] ?? ''),
                'amount' => (float) $existing->amount,
                'plan_label' => (string) ($ctx['plan']['name_plan'] ?? ''),
                'gateway_label' => self::gatewayLabel(),
                'message' => 'Vérification du paiement déjà lancé — en attente de confirmation CamPay.',
            ];
        }

        $payment = self::createPendingPayment($admin, $customerId, $planId, $server, self::METHOD_MOBILE_MONEY, $ctx['total']);

        return self::initiateMobileCollect((int) $payment->id(), $phone, $admin);
    }

    /** @return array<string, mixed> */
    public static function initiateMobileCollect($paymentId, $phone, $admin)
    {
        self::ensureSchema();
        if (!MobileMoneyGateway::isConfigured()) {
            return ['ok' => false, 'message' => Lang::T('Payment gateway not configured. Please contact admin')];
        }

        $payment = self::findPaymentForAdmin($paymentId, $admin);
        if (!$payment) {
            return ['ok' => false, 'message' => Lang::T('Payment not found')];
        }
        if ($payment->status === 'paid') {
            return ['ok' => false, 'message' => Lang::T('Transaction has already been paid.')];
        }
        if ($payment->status === 'failed') {
            if (trim((string) $payment->gateway_reference) !== '') {
                return self::checkMobileStatus((int) $payment->id, $admin);
            }

            return ['ok' => false, 'message' => Lang::T('Failed_Payments')];
        }

        $ctx = self::buildRechargeContext(
            (int) $payment->customer_id,
            (int) $payment->plan_id,
            self::METHOD_MOBILE_MONEY,
            $admin
        );
        $plan = $ctx['plan'];
        $externalRef = 'PPPOE-RCH-' . (int) $payment->id . '-' . strtoupper(bin2hex(random_bytes(3)));

        $gateway = (string) $payment->gateway;
        if (!MobileMoneyGateway::requireFile($gateway)) {
            self::markFailed($payment);

            return ['ok' => false, 'message' => Lang::T('Payment gateway not configured. Please contact admin')];
        }

        $collectFn = $gateway . '_plan_recharge_collect_data';
        if (!function_exists($collectFn)) {
            self::markFailed($payment);

            return ['ok' => false, 'message' => Lang::T('Payment gateway not configured. Please contact admin')];
        }

        $collectCtx = [
            'payment' => $payment,
            'amount' => (int) round((float) $payment->amount),
            'external_ref' => $externalRef,
            'plan_label' => (string) ($plan['name_plan'] ?? 'PPPoE'),
            'customer' => $ctx['cust'],
            'server' => (string) $payment->server,
        ];

        $result = call_user_func($collectFn, $collectCtx, $admin, $phone);
        if (empty($result['ok'])) {
            if ($payment->status === 'pending') {
                self::markFailed($payment);
            }

            return $result;
        }

        $payment->gateway_reference = (string) ($result['reference'] ?? '');
        $payment->phone = (string) ($result['phone'] ?? $phone);
        $payment->status = 'pending';
        $result['external_ref'] = $externalRef;
        $payment->payload = json_encode($result);
        $payment->save();

        return [
            'ok' => true,
            'payment_id' => (int) $payment->id,
            'operator' => (string) ($result['operator'] ?? ''),
            'ussd' => (string) ($result['ussd'] ?? ''),
            'amount' => (float) $payment->amount,
            'plan_label' => (string) ($plan['name_plan'] ?? ''),
            'gateway_label' => self::gatewayLabel(),
            'message' => Lang::T('Payment request sent to your phone.'),
        ];
    }

    public static function checkMobileStatus($paymentId, $admin)
    {
        self::ensureSchema();
        $payment = self::findPaymentForAdmin($paymentId, $admin);
        if (!$payment) {
            return ['ok' => false, 'pending' => false, 'message' => Lang::T('Payment not found')];
        }

        if ($payment->status === 'paid') {
            return [
                'ok' => true,
                'pending' => false,
                'status' => 'paid',
                'message' => Lang::T('Data Created Successfully'),
                'redirect' => self::invoiceUrlForPayment($payment),
            ];
        }

        $gateway = (string) $payment->gateway;
        if ($payment->status === 'failed' && trim((string) $payment->gateway_reference) !== '') {
            $remote = self::fetchRemoteStatus($payment, $admin);
            if (self::isSuccessfulGatewayStatus($remote['status'] ?? '')) {
                return self::finalizePaidRecharge($payment, $admin, $remote);
            }

            return [
                'ok' => false,
                'pending' => false,
                'status' => 'failed',
                'message' => Lang::T('Failed_Payments'),
            ];
        }

        if ($payment->status !== 'pending') {
            return [
                'ok' => false,
                'pending' => false,
                'status' => 'failed',
                'message' => Lang::T('Failed_Payments'),
            ];
        }

        $remote = self::fetchRemoteStatus($payment, $admin);
        $status = strtoupper((string) ($remote['status'] ?? 'PENDING'));

        if (self::isSuccessfulGatewayStatus($status)) {
            return self::finalizePaidRecharge($payment, $admin, $remote);
        }
        if (self::isFailedGatewayStatus($status)) {
            self::markFailed($payment);

            return [
                'ok' => false,
                'pending' => false,
                'status' => 'failed',
                'message' => Lang::T('Failed_Payments'),
            ];
        }

        return [
            'ok' => false,
            'pending' => true,
            'status' => 'pending',
            'message' => Lang::T('Please confirm the payment on your mobile device, then click Check Status.'),
        ];
    }

    /** @return array<string, mixed> */
    public static function fetchRemoteStatus($payment, $admin)
    {
        $gateway = (string) $payment->gateway;
        if (!MobileMoneyGateway::requireFile($gateway)) {
            return ['status' => 'PENDING'];
        }

        $checkFn = $gateway . '_plan_recharge_check_status';
        if (!function_exists($checkFn)) {
            return ['status' => 'PENDING'];
        }

        return call_user_func($checkFn, $payment, $admin);
    }

    /**
     * Webhook CamPay / MyPVit — activation même si le navigateur a été fermé.
     *
     * @param array<string, mixed> $data
     */
    public static function handleGatewayWebhook($reference, $status, array $data = [])
    {
        self::ensureSchema();
        $reference = trim((string) $reference);
        if ($reference === '') {
            return false;
        }

        $payment = ORM::for_table('wifizone_plan_recharge_payments')
            ->where('gateway_reference', $reference)
            ->find_one();
        if (!$payment) {
            $externalRef = (string) ($data['external_reference'] ?? '');
            if (preg_match('/^PPPOE-RCH-(\d+)/', $externalRef, $m)) {
                $payment = ORM::for_table('wifizone_plan_recharge_payments')->find_one((int) $m[1]);
            }
        }
        if (!$payment || $payment->status === 'paid') {
            return false;
        }

        $admin = ORM::for_table('tbl_users')->find_one((int) $payment->admin_id);
        if (!$admin) {
            return false;
        }
        $adminArr = $admin->as_array();

        if (self::isSuccessfulGatewayStatus($status)) {
            self::finalizePaidRecharge($payment, $adminArr, $data);

            return true;
        }
        if (self::isFailedGatewayStatus($status)) {
            self::markFailed($payment);
        }

        return false;
    }

    /** @return array<string, mixed> */
    public static function finalizePaidRecharge($payment, $actingAdmin, array $remote = [])
    {
        $payment = ORM::for_table('wifizone_plan_recharge_payments')->find_one((int) $payment->id);
        if (!$payment) {
            return ['ok' => false, 'pending' => false, 'message' => Lang::T('Payment not found')];
        }

        if ($payment->status === 'paid') {
            return [
                'ok' => true,
                'pending' => false,
                'status' => 'paid',
                'message' => Lang::T('Data Created Successfully'),
                'redirect' => self::invoiceUrlForPayment($payment),
            ];
        }

        try {
            $ctx = self::buildRechargeContext(
                (int) $payment->customer_id,
                (int) $payment->plan_id,
                self::METHOD_MOBILE_MONEY,
                $actingAdmin
            );
        } catch (Throwable $e) {
            _log('Plan recharge finalize context failed #' . (int) $payment->id . ': ' . $e->getMessage());

            return [
                'ok' => false,
                'pending' => true,
                'status' => 'pending',
                'message' => 'Paiement reçu — activation en cours. Réessayez dans quelques secondes.',
            ];
        }

        $plan = $ctx['plan'];
        $gatewayLabel = self::gatewayLabel();
        $operator = (string) ($remote['operator'] ?? 'Mobile Money');

        global $admin;
        $previousAdmin = $admin;
        $admin = $actingAdmin;

        Package::$lastDeviceSyncError = '';
        $recharged = Package::rechargeUser(
            (int) $payment->customer_id,
            (string) $payment->server,
            (int) $payment->plan_id,
            $gatewayLabel,
            $operator
        );
        $admin = $previousAdmin;

        if (!$recharged) {
            _log('Plan recharge Package::rechargeUser failed #' . (int) $payment->id . ' customer=' . (int) $payment->customer_id);
            if (function_exists('Message')) {
                Message::sendTelegram('CamPay PPPoE: paiement OK mais activation échouée — recharge #' . (int) $payment->id);
            }

            return [
                'ok' => false,
                'pending' => true,
                'status' => 'pending',
                'message' => 'Paiement reçu. Activation en cours — ne quittez pas, nouvelle tentative automatique…',
            ];
        }

        if (($actingAdmin['user_type'] ?? '') !== 'SuperAdmin' && function_exists('add_commission_on_sale')) {
            add_commission_on_sale((int) $actingAdmin['id'], $plan['price'], $plan['name_plan']);
        }

        $payment->status = 'paid';
        $payment->paid_at = date('Y-m-d H:i:s');
        $payment->save();

        $cust = ORM::for_table('tbl_customers')->find_one((int) $payment->customer_id);
        $username = $cust ? (string) $cust->username : ('#' . (int) $payment->customer_id);
        $adminLabel = (string) ($actingAdmin['username'] ?? $actingAdmin['fullname'] ?? 'admin');
        _log(
            '[' . $adminLabel . ']: Mobile Money recharge — ' . $username
            . ' [' . ($plan['name_plan'] ?? '') . '][' . Lang::moneyFormat((float) ($plan['price'] ?? 0)) . '] via ' . $gatewayLabel,
            (string) ($actingAdmin['user_type'] ?? 'Admin'),
            (int) ($actingAdmin['id'] ?? 0)
        );

        $message = Lang::T('Data Created Successfully');
        if (Package::$lastDeviceSyncError !== '') {
            $message .= ' — forfait en base, mais sync MikroTik échouée : ' . Package::$lastDeviceSyncError;
        }

        return [
            'ok' => true,
            'pending' => false,
            'status' => 'paid',
            'message' => $message,
            'redirect' => self::invoiceUrlForPayment($payment),
        ];
    }

    public static function invoiceUrlForPayment($payment)
    {
        $cust = ORM::for_table('tbl_customers')->find_one((int) $payment->customer_id);
        if (!$cust) {
            return getUrl('customers/view/' . (int) $payment->customer_id);
        }
        $trx = ORM::for_table('tbl_transactions')
            ->where('username', $cust->username)
            ->order_by_desc('id')
            ->find_one();
        if ($trx) {
            return getUrl('plan/view/' . (int) $trx->id);
        }

        return getUrl('customers/view/' . (int) $payment->customer_id);
    }

    public static function markFailed($payment)
    {
        $payment->status = 'failed';
        $payment->save();
    }
}
