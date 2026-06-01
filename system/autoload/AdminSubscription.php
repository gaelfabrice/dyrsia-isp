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

    public static function ensureTrial($adminId)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        if ($adminId < 1) {
            return null;
        }
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
        $sub->trial_end = date('Y-m-d H:i:s', strtotime('+7 days'));
        $sub->routers_count = self::routerCount($adminId);
        $sub->created_at = $now;
        $sub->updated_at = $now;
        $sub->save();
        return $sub;
    }

    public static function getForAdmin($adminId)
    {
        self::ensureSchema();
        self::syncStatuses();
        return self::ensureTrial((int) $adminId);
    }

    public static function settings()
    {
        self::ensureSchema();
        $rows = ORM::for_table('isp_settings')->find_many();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = (float) $row->setting_value;
        }
        return $settings;
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
        return true;
    }

    public static function subscribe($adminId, $planType, $routersRequested)
    {
        self::ensureSchema();
        $planType = strtolower(trim($planType));
        if (!in_array($planType, ['business', 'pro'], true)) {
            throw new InvalidArgumentException(Lang::T('Invalid subscription plan'));
        }
        $routersRequested = max(1, (int) $routersRequested);
        $currentRouters = self::routerCount($adminId);
        if ($planType === 'business' && $currentRouters > 3) {
            throw new RuntimeException(Lang::T('Business plan allows a maximum of 3 routers'));
        }
        $sub = self::ensureTrial($adminId);
        $now = date('Y-m-d H:i:s');
        $sub->plan_type = $planType;
        $sub->status = 'active';
        $sub->subscription_start = $now;
        $sub->subscription_end = date('Y-m-d H:i:s', strtotime('+30 days'));
        $sub->grace_end = null;
        $sub->routers_count = $planType === 'pro' ? $routersRequested : min(3, max($currentRouters, 1));
        $sub->updated_at = $now;
        $sub->save();
        $amount = self::calculateAmount($planType, $sub->routers_count);
        $invoice = self::createInvoice($adminId, $planType, $sub->routers_count, $amount, 'paid');
        self::recordPayment($adminId, $invoice ? (int) $invoice->id() : null, $amount, 'Manual', 'subscription-' . date('YmdHis'), 'paid');
        return $sub;
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
        $now = date('Y-m-d H:i:s');
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'grace', grace_end = DATE_ADD(NOW(), INTERVAL 24 HOUR), updated_at = NOW() WHERE status = 'active' AND subscription_end IS NOT NULL AND subscription_end < NOW()");
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'expired', updated_at = NOW() WHERE status = 'grace' AND grace_end IS NOT NULL AND grace_end < NOW()");
        ORM::raw_execute("UPDATE admin_subscriptions SET status = 'expired', updated_at = NOW() WHERE status = 'trial' AND trial_end IS NOT NULL AND trial_end < NOW()");
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
