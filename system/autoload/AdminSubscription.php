<?php

class AdminSubscription
{
    public static function ensureSchema()
    {
        $db = ORM::get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS `admin_subscriptions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `plan_type` enum('business','pro') DEFAULT NULL,
            `status` enum('trial','active','expired','grace') NOT NULL DEFAULT 'trial',
            `trial_start` datetime DEFAULT NULL,
            `trial_end` datetime DEFAULT NULL,
            `subscription_start` datetime DEFAULT NULL,
            `subscription_end` datetime DEFAULT NULL,
            `grace_end` datetime DEFAULT NULL,
            `routers_count` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `admin_id` (`admin_id`),
            KEY `status` (`status`),
            KEY `subscription_end` (`subscription_end`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS `isp_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(64) NOT NULL,
            `setting_value` decimal(15,2) NOT NULL DEFAULT 0.00,
            `updated_by` int(11) DEFAULT NULL,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS `admin_subscription_invoices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `invoice_no` varchar(40) NOT NULL,
            `plan_type` varchar(20) NOT NULL,
            `routers_count` int(11) NOT NULL DEFAULT 0,
            `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
            `status` enum('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `paid_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `invoice_no` (`invoice_no`),
            KEY `admin_status` (`admin_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS `admin_subscription_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `invoice_id` int(11) DEFAULT NULL,
            `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
            `method` varchar(64) NOT NULL DEFAULT 'Manual',
            `reference` varchar(128) DEFAULT NULL,
            `status` enum('pending','paid','failed') NOT NULL DEFAULT 'paid',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `admin_date` (`admin_id`, `created_at`),
            KEY `invoice_id` (`invoice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::seedSettings();
    }

    public static function seedSettings()
    {
        foreach (['business_price' => '5000.00', 'pro_price_per_router' => '2000.00'] as $key => $value) {
            $setting = ORM::for_table('isp_settings')->where('setting_key', $key)->find_one();
            if (!$setting) {
                $setting = ORM::for_table('isp_settings')->create();
                $setting->setting_key = $key;
                $setting->setting_value = $value;
                $setting->updated_at = date('Y-m-d H:i:s');
                $setting->save();
            }
        }
    }

    /** Jours d'essai Mode Démo (page d'accueil). */
    public static function demoTrialDays()
    {
        return 5;
    }

    /** Routes admin autorisées lorsque l'abonnement / la démo est expiré(e). */
    public static function subscriptionGateRoutes()
    {
        return [
            'subscription',
            'subscription-post',
            'subscription-pay',
            'subscription-verify',
            'subscription-demo-ack',
        ];
    }

    public static function isSubscriptionRoute($handler, $action)
    {
        return $handler === 'admin'
            && in_array((string) $action, self::subscriptionGateRoutes(), true);
    }

    /** True si l'admin doit souscrire avant d'accéder au reste de l'application. */
    public static function mustSubscribeToContinue($adminId)
    {
        if (DemoShowcase::isShowcaseUser((int) $adminId)) {
            return false;
        }
        self::ensureSchema();
        self::syncStatuses();
        $sub = ORM::for_table('admin_subscriptions')->where('admin_id', (int) $adminId)->find_one();
        if (!$sub) {
            return true;
        }
        if ($sub->status === 'active') {
            return false;
        }
        if ($sub->status === 'trial' && !empty($sub->trial_end) && strtotime($sub->trial_end) > time()) {
            return false;
        }

        return true;
    }

    /**
     * Redirige vers la page d'abonnement si la démo ou l'abonnement est expiré(e).
     * Exception : déconnexion et pages de souscription / paiement.
     */
    public static function enforceSubscriptionGate($admin, $handler, $action)
    {
        if (!$admin || ($admin['user_type'] ?? '') !== 'Admin') {
            return;
        }
        if ($handler === 'logout') {
            return;
        }
        if (self::isSubscriptionRoute($handler, $action)) {
            return;
        }
        if (!self::mustSubscribeToContinue((int) ($admin['id'] ?? 0))) {
            return;
        }
        r2(
            getUrl('admin/subscription'),
            'w',
            'Votre Mode Démo a expiré. Choisissez un forfait pour continuer.'
        );
    }

    public static function normalizeSignupIntent($intent)
    {
        $intent = strtolower(trim((string) $intent));
        return in_array($intent, ['demo', 'business', 'pro'], true) ? $intent : 'demo';
    }

    public static function ensureTrial($adminId, $signupIntent = 'demo')
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        if ($adminId < 1) {
            return null;
        }
        $signupIntent = self::normalizeSignupIntent($signupIntent);
        $sub = ORM::for_table('admin_subscriptions')->where('admin_id', $adminId)->find_one();
        if ($sub) {
            return $sub;
        }
        $now = date('Y-m-d H:i:s');
        $sub = ORM::for_table('admin_subscriptions')->create();
        $sub->admin_id = $adminId;
        $sub->plan_type = null;
        $sub->status = 'trial';
        $sub->trial_start = $now;
        $sub->trial_end = date('Y-m-d H:i:s', strtotime('+' . self::demoTrialDays() . ' days'));
        $sub->routers_count = self::routerCount($adminId);
        $sub->created_at = $now;
        $sub->updated_at = $now;
        $sub->save();
        return $sub;
    }

    public static function subscriptionUrl($plan = '', $checkout = false)
    {
        $url = 'admin/subscription';
        $params = [];
        if (in_array($plan, ['business', 'pro'], true)) {
            $params[] = 'plan=' . $plan;
        }
        if ($checkout) {
            $params[] = 'checkout=1';
        }
        if (!empty($params)) {
            $url .= '&' . implode('&', $params);
        }
        return getUrl($url);
    }

    public static function getForAdmin($adminId)
    {
        self::ensureSchema();
        self::syncStatuses();
        return self::ensureTrial((int) $adminId);
    }

    public static function defaultSettings()
    {
        return [
            'business_price' => 5000.0,
            'pro_price_per_router' => 2000.0,
        ];
    }

    public static function settingLabels()
    {
        return [
            'business_price' => 'Forfait Business',
            'pro_price_per_router' => 'Forfait Pro',
        ];
    }

    public static function settingLabel($key)
    {
        $labels = self::settingLabels();
        return $labels[$key] ?? $key;
    }

    public static function settings()
    {
        self::ensureSchema();
        $settings = self::defaultSettings();
        $rows = ORM::for_table('isp_settings')->find_many();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = (float) $row->setting_value;
        }
        return $settings;
    }

    public static function settingsUpdatedAt()
    {
        self::ensureSchema();
        $row = ORM::for_table('isp_settings')->order_by_desc('updated_at')->find_one();
        return $row ? (string) $row->updated_at : '';
    }

    /** Vide les templates Smarty liés aux tarifs ISP (après modification SuperAdmin). */
    public static function clearPricingUiCache($smarty = null)
    {
        global $ui;
        $smarty = $smarty ?? $ui ?? null;
        if (!$smarty instanceof Smarty) {
            return;
        }
        foreach ([
            'customer/landing.tpl',
            'admin/subscription.tpl',
            'admin/dashboard.tpl',
            'admin/superadmin/isp-settings.tpl',
        ] as $tpl) {
            try {
                $smarty->clearCompiledTemplate($tpl);
            } catch (Throwable $e) {
            }
        }
        try {
            $smarty->clearCompiledTemplate();
        } catch (Throwable $e) {
        }
    }

    public static function updateSetting($key, $value, $updatedBy)
    {
        self::ensureSchema();
        if (!in_array($key, ['business_price', 'pro_price_per_router'], true)) {
            return false;
        }
        $setting = ORM::for_table('isp_settings')->where('setting_key', $key)->find_one();
        if (!$setting) {
            $setting = ORM::for_table('isp_settings')->create();
            $setting->setting_key = $key;
        }
        $setting->setting_value = max(0, (float) $value);
        $setting->updated_by = (int) $updatedBy;
        $setting->updated_at = date('Y-m-d H:i:s');
        $setting->save();
        self::clearPricingUiCache();
        return true;
    }

    public static function validatePlanSelection($adminId, $planType, $routersRequested)
    {
        $planType = strtolower(trim($planType));
        if (!in_array($planType, ['business', 'pro'], true)) {
            throw new InvalidArgumentException(Lang::T('Invalid subscription plan'));
        }
        $routersRequested = max(1, (int) $routersRequested);
        $currentRouters = self::routerCount($adminId);
        if ($planType === 'business' && $currentRouters > 3) {
            throw new RuntimeException(Lang::T('Business plan allows a maximum of 3 routers'));
        }
        if ($planType === 'pro' && $routersRequested < 1) {
            throw new RuntimeException(Lang::T('Number of routers is required for Pro plan'));
        }
        return [
            'plan_type' => $planType,
            'routers_count' => $planType === 'pro' ? $routersRequested : min(3, max($currentRouters, 1)),
        ];
    }

    public static function planLabel($planType)
    {
        return strtolower((string) $planType) === 'pro' ? 'Forfait Pro' : 'Forfait Business';
    }

    /**
     * Crée une facture impayée + paiement CamPay en attente (sans activer l'abonnement).
     *
     * @return array{payment: object, invoice: object, amount: float, plan_label: string, external_ref: string}
     */
    public static function initiatePayment($adminId, $planType, $routersRequested)
    {
        self::ensureSchema();
        $selection = self::validatePlanSelection($adminId, $planType, $routersRequested);
        $amount = self::calculateAmount($selection['plan_type'], $selection['routers_count']);
        if ($amount <= 0) {
            throw new RuntimeException(Lang::T('Invalid subscription amount'));
        }
        $invoice = self::createInvoice($adminId, $selection['plan_type'], $selection['routers_count'], $amount, 'unpaid');
        $payment = self::recordPayment(
            $adminId,
            $invoice ? (int) $invoice->id() : null,
            $amount,
            'CamPay',
            'pending-' . (int) $invoice->id(),
            'pending'
        );
        $externalRef = 'ISP-SUB-' . (int) $payment->id();
        $payment->reference = $externalRef;
        $payment->save();
        return [
            'payment' => $payment,
            'invoice' => $invoice,
            'amount' => $amount,
            'plan_label' => self::planLabel($selection['plan_type']),
            'external_ref' => $externalRef,
            'plan_type' => $selection['plan_type'],
            'routers_count' => $selection['routers_count'],
        ];
    }

    public static function setCampayReference($paymentId, $campayReference)
    {
        $payment = ORM::for_table('admin_subscription_payments')->find_one((int) $paymentId);
        if (!$payment || $payment->status !== 'pending') {
            return false;
        }
        $payment->reference = trim((string) $campayReference);
        $payment->save();
        return true;
    }

    public static function getPaymentForAdmin($paymentId, $adminId)
    {
        self::ensureSchema();
        return ORM::for_table('admin_subscription_payments')
            ->where('id', (int) $paymentId)
            ->where('admin_id', (int) $adminId)
            ->find_one();
    }

    public static function activateFromPayment($paymentId, $campayPayload = [])
    {
        self::ensureSchema();
        $payment = ORM::for_table('admin_subscription_payments')->find_one((int) $paymentId);
        if (!$payment) {
            return ['ok' => false, 'message' => Lang::T('Payment not found')];
        }
        if ($payment->status === 'paid') {
            return ['ok' => true, 'message' => Lang::T('Subscription activated successfully'), 'already' => true];
        }
        if ($payment->status !== 'pending') {
            return ['ok' => false, 'message' => Lang::T('Transaction failed.')];
        }
        $invoice = $payment->invoice_id
            ? ORM::for_table('admin_subscription_invoices')->find_one((int) $payment->invoice_id)
            : null;
        if (!$invoice) {
            return ['ok' => false, 'message' => Lang::T('Invoice not found')];
        }
        $sub = self::applySubscription(
            (int) $payment->admin_id,
            $invoice->plan_type,
            (int) $invoice->routers_count
        );
        $now = date('Y-m-d H:i:s');
        $invoice->status = 'paid';
        $invoice->paid_at = $now;
        $invoice->save();
        $payment->status = 'paid';
        $payment->method = 'CamPay';
        if (!empty($campayPayload)) {
            $payment->reference = (string) ($campayPayload['reference'] ?? $payment->reference);
        }
        $payment->save();
        $admin = ORM::for_table('tbl_users')->find_one((int) $payment->admin_id);
        if ($admin) {
            self::sendActivationNotifications($admin, $sub, $invoice);
        }
        if (isset($_SESSION['signup_checkout_plan'])) {
            unset($_SESSION['signup_checkout_plan']);
        }
        return [
            'ok' => true,
            'message' => Lang::T('Subscription activated successfully'),
            'subscription_end' => $sub->subscription_end,
            'plan_label' => self::planLabel($sub->plan_type),
        ];
    }

    public static function markPaymentFailed($paymentId)
    {
        $payment = ORM::for_table('admin_subscription_payments')->find_one((int) $paymentId);
        if ($payment && $payment->status === 'pending') {
            $payment->status = 'failed';
            $payment->save();
            if ($payment->invoice_id) {
                $invoice = ORM::for_table('admin_subscription_invoices')->find_one((int) $payment->invoice_id);
                if ($invoice && $invoice->status === 'unpaid') {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }
            }
        }
    }

    public static function applySubscription($adminId, $planType, $routersCount)
    {
        self::ensureSchema();
        $selection = self::validatePlanSelection($adminId, $planType, $routersCount);
        $sub = self::ensureTrial($adminId);
        $now = date('Y-m-d H:i:s');
        $base = (!empty($sub->subscription_end) && strtotime($sub->subscription_end) > time())
            ? $sub->subscription_end
            : $now;
        $sub->plan_type = $selection['plan_type'];
        $sub->status = 'active';
        if (empty($sub->subscription_start) || $sub->status !== 'active') {
            $sub->subscription_start = $now;
        }
        $sub->subscription_end = date('Y-m-d H:i:s', strtotime($base . ' +30 days'));
        $sub->grace_end = null;
        $sub->routers_count = $selection['routers_count'];
        $sub->updated_at = $now;
        $sub->save();
        return $sub;
    }

    public static function sendActivationNotifications($admin, $sub, $invoice)
    {
        global $config;
        $planLabel = self::planLabel($sub->plan_type);
        $endDate = date('d/m/Y', strtotime($sub->subscription_end));
        $amount = number_format((float) $invoice->amount, 0, ',', ' ');
        $company = $config['CompanyName'] ?? 'DYRSIA';
        $username = $admin->username ?? '';
        $smsText = "{$company}: Votre {$planLabel} est actif jusqu'au {$endDate}. Montant: {$amount} XAF. Merci!";
        $phone = trim((string) ($admin->phone ?? ''));
        if ($phone !== '') {
            try {
                Message::sendSMS($phone, $smsText);
            } catch (Throwable $e) {
                _log('Subscription SMS failed: ' . $e->getMessage());
            }
        }
        $safePlan = htmlspecialchars($planLabel, ENT_QUOTES, 'UTF-8');
        $safeEnd = htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8');
        $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $body = <<<HTML
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Abonnement activé</title></head>
<body style="font-family:Inter,system-ui,sans-serif;background:#0f172a;color:#f8fafc;padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#111827;border:1px solid #1e293b;border-radius:16px;padding:28px;">
<h2 style="margin:0 0 12px;color:#22c55e;">✅ Abonnement activé</h2>
<p>Bonjour <strong>{$safeUser}</strong>,</p>
<p>Votre <strong>{$safePlan}</strong> est maintenant actif jusqu'au <strong>{$safeEnd}</strong>.</p>
<p>Montant payé : <strong>{$amount} XAF</strong></p>
<p style="color:#94a3b8;font-size:13px;">Vous pouvez gérer vos routeurs depuis votre tableau de bord.</p>
</div></body></html>
HTML;
        $email = trim((string) ($admin->email ?? ''));
        if ($email !== '') {
            try {
                Message::sendEmail($email, $company . ' — Abonnement activé', $body, null, true);
            } catch (Throwable $e) {
                _log('Subscription email failed: ' . $e->getMessage());
            }
        }
        try {
            Message::sendTelegram("Abonnement activé\nAdmin: {$username}\nPlan: {$planLabel}\nFin: {$endDate}\nMontant: {$amount} XAF");
        } catch (Throwable $e) {
        }
    }


    public static function calculateAmount($planType, $routersCount)
    {
        $settings = self::settings();
        if ($planType === 'pro') {
            return max(1, (int) $routersCount) * (float) ($settings['pro_price_per_router'] ?? 0);
        }
        return (float) ($settings['business_price'] ?? 0);
    }

    public static function createInvoice($adminId, $planType, $routersCount, $amount, $status = 'unpaid')
    {
        self::ensureSchema();
        $invoice = ORM::for_table('admin_subscription_invoices')->create();
        $invoice->admin_id = (int) $adminId;
        $invoice->invoice_no = 'ISP-' . date('Ymd') . '-' . strtoupper(substr(sha1($adminId . microtime(true)), 0, 8));
        $invoice->plan_type = $planType;
        $invoice->routers_count = (int) $routersCount;
        $invoice->amount = max(0, (float) $amount);
        $invoice->status = in_array($status, ['unpaid', 'paid', 'cancelled'], true) ? $status : 'unpaid';
        $invoice->created_at = date('Y-m-d H:i:s');
        $invoice->paid_at = $invoice->status === 'paid' ? date('Y-m-d H:i:s') : null;
        $invoice->save();
        return $invoice;
    }

    public static function recordPayment($adminId, $invoiceId, $amount, $method, $reference, $status = 'paid')
    {
        self::ensureSchema();
        $payment = ORM::for_table('admin_subscription_payments')->create();
        $payment->admin_id = (int) $adminId;
        $payment->invoice_id = $invoiceId ? (int) $invoiceId : null;
        $payment->amount = max(0, (float) $amount);
        $payment->method = trim($method) ?: 'Manual';
        $payment->reference = trim($reference);
        $payment->status = in_array($status, ['pending', 'paid', 'failed'], true) ? $status : 'paid';
        $payment->created_at = date('Y-m-d H:i:s');
        $payment->save();
        return $payment;
    }

    public static function routerCount($adminId)
    {
        return (int) ORM::for_table('tbl_routers')->where('admin_id', (int) $adminId)->count();
    }

    public static function canAddRouter($adminId)
    {
        $sub = self::getForAdmin($adminId);
        if (!$sub) {
            return ['ok' => false, 'message' => Lang::T('Subscription not found')];
        }
        if ($sub->status === 'trial') {
            return ['ok' => false, 'message' => Lang::T('Ajout de routeur non autorisé pendant la période d\'essai')];
        }
        if (in_array($sub->status, ['expired', 'grace'], true)) {
            return ['ok' => false, 'message' => Lang::T('Your subscription has expired')];
        }
        if ($sub->plan_type === 'business' && self::routerCount($adminId) >= 3) {
            return ['ok' => false, 'message' => Lang::T('Business plan limit reached: maximum 3 routers')];
        }
        return ['ok' => true, 'message' => ''];
    }

    public static function syncRouterCount($adminId)
    {
        $sub = self::ensureTrial($adminId);
        if ($sub) {
            $sub->routers_count = self::routerCount($adminId);
            $sub->updated_at = date('Y-m-d H:i:s');
            $sub->save();
        }
    }

    public static function syncStatuses()
    {
        self::ensureSchema();
        self::normalizeTrialPeriods();
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'grace', grace_end = DATE_ADD(NOW(), INTERVAL 24 HOUR), updated_at = NOW() WHERE status = 'active' AND subscription_end IS NOT NULL AND subscription_end < NOW()");
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'expired', updated_at = NOW() WHERE status = 'grace' AND grace_end IS NOT NULL AND grace_end < NOW()");
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'expired', updated_at = NOW() WHERE status = 'trial' AND trial_end IS NOT NULL AND trial_end < NOW()");
    }

    /** Aligne les essais existants (ex. 14 jours) sur la durée Mode Démo actuelle. */
    public static function normalizeTrialPeriods()
    {
        self::ensureSchema();
        $days = (int) self::demoTrialDays();
        ORM::raw_execute(
            "UPDATE admin_subscriptions SET trial_end = DATE_ADD(trial_start, INTERVAL {$days} DAY), updated_at = NOW()
             WHERE status = 'trial' AND trial_start IS NOT NULL
             AND (trial_end IS NULL OR trial_end > DATE_ADD(trial_start, INTERVAL {$days} DAY))"
        );
    }

    public static function trialTotalDays($sub)
    {
        if (!$sub || ($sub->status ?? '') !== 'trial') {
            return self::demoTrialDays();
        }
        if (!empty($sub->trial_start) && !empty($sub->trial_end)) {
            $days = (int) ceil((strtotime($sub->trial_end) - strtotime($sub->trial_start)) / 86400);

            return max(1, min($days, self::demoTrialDays()));
        }

        return self::demoTrialDays();
    }

    public static function daysRemaining($date)
    {
        if (empty($date)) {
            return 0;
        }
        return max(0, (int) ceil((strtotime($date) - time()) / 86400));
    }

    public static function allWithAdmins()
    {
        self::ensureSchema();
        self::syncStatuses();
        return ORM::for_table('admin_subscriptions')
            ->table_alias('s')
            ->select('s.*')
            ->select('u.username', 'admin_username')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.email', 'admin_email')
            ->join('tbl_users', ['s.admin_id', '=', 'u.id'], 'u')
            ->order_by_desc('s.updated_at')
            ->find_many();
    }

    public static function invoicesForAdmin($adminId)
    {
        self::ensureSchema();
        return ORM::for_table('admin_subscription_invoices')->where('admin_id', (int) $adminId)->order_by_desc('id')->find_many();
    }

    public static function paymentsForAdmin($adminId)
    {
        self::ensureSchema();
        return ORM::for_table('admin_subscription_payments')->where('admin_id', (int) $adminId)->order_by_desc('id')->find_many();
    }

    public static function statsForAdmin($adminId)
    {
        $sub = self::getForAdmin($adminId);
        $routerCount = self::routerCount($adminId);
        $limit = $sub && $sub->plan_type === 'business' ? 3 : (int) ($sub->routers_count ?: $routerCount);
        return [
            'routers' => $routerCount,
            'router_limit' => $limit,
            'paid_total' => (float) (ORM::for_table('admin_subscription_payments')->where('admin_id', (int) $adminId)->where('status', 'paid')->sum('amount') ?: 0),
            'invoice_total' => (float) (ORM::for_table('admin_subscription_invoices')->where('admin_id', (int) $adminId)->sum('amount') ?: 0),
        ];
    }

    public static function platformStats()
    {
        self::ensureSchema();
        self::syncStatuses();
        $stats = ['total' => 0, 'trial' => 0, 'active' => 0, 'grace' => 0, 'expired' => 0];
        foreach (ORM::for_table('admin_subscriptions')->select('status')->select_expr('COUNT(*)', 'total')->group_by('status')->find_array() as $row) {
            $stats[$row['status']] = (int) $row['total'];
            $stats['total'] += (int) $row['total'];
        }
        return $stats;
    }

    public static function instances($status = '')
    {
        self::ensureSchema();
        self::syncStatuses();
        $query = ORM::for_table('tbl_tenants')
            ->table_alias('t')
            ->select('t.*')
            ->select('u.username', 'admin_username')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.email', 'admin_email')
            ->select('s.plan_type', 'subscription_plan')
            ->select('s.status', 'subscription_status')
            ->select('s.trial_end', 'trial_end')
            ->select('s.subscription_end', 'subscription_end')
            ->select('s.routers_count', 'subscribed_routers')
            ->left_outer_join('tbl_users', ['t.admin_user_id', '=', 'u.id'], 'u')
            ->left_outer_join('admin_subscriptions', ['t.admin_user_id', '=', 's.admin_id'], 's')
            ->order_by_desc('t.created_at');
        if (in_array($status, ['trial', 'active', 'grace', 'expired'], true)) {
            $query->where('s.status', $status);
        }
        return $query->find_many();
    }

    public static function suspend($adminId)
    {
        $sub = self::ensureTrial($adminId);
        $sub->status = 'expired';
        $sub->updated_at = date('Y-m-d H:i:s');
        $sub->save();
    }

    public static function extend($adminId)
    {
        $sub = self::ensureTrial($adminId);
        $base = (!empty($sub->subscription_end) && strtotime($sub->subscription_end) > time()) ? $sub->subscription_end : date('Y-m-d H:i:s');
        $sub->status = 'active';
        if (empty($sub->plan_type)) {
            $sub->plan_type = 'business';
        }
        if (empty($sub->subscription_start)) {
            $sub->subscription_start = date('Y-m-d H:i:s');
        }
        $sub->subscription_end = date('Y-m-d H:i:s', strtotime($base . ' +30 days'));
        $sub->grace_end = null;
        $sub->updated_at = date('Y-m-d H:i:s');
        $sub->save();
    }
}
