<?php

    if (defined('WIFIZONE_HOTSPOT_PLUGIN_LOADED')) {
        return;
    }
    define('WIFIZONE_HOTSPOT_PLUGIN_LOADED', true);

    /**
     * Bismillahir Rahmanir Raheem
     * 
     * PHP Mikrotik Billing (https://gitlab.com/smbilling/smb)
     *
     * Advanced Hotspot System For SMBilling
     *
     * @author: STCN Digital Shop <stcablenetwork10@gmail.com>
     * Website: https://shop.stcncrm.xyz
     * Telegram: https://t.me/smbilling/
     *
     **/

    use PEAR2\Net\RouterOS;

    $db = ORM::getDb();
    $tableExists = false;
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('tbl_hotspot_payments', $tables) || in_array('tbl_hotspot_vouchers', $tables)) {
        $tableExists = true;
    }

    // Create the tbl_hotspot_payments table if it doesn't exist
    if (!in_array('tbl_hotspot_payments', $tables)) {
        try {
            $db->exec("
                CREATE TABLE `tbl_hotspot_payments` (
                    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
                    `transaction_id` VARCHAR(1000) NULL,
                    `transaction_ref` VARCHAR(1000) NOT NULL,
                    `router_name` VARCHAR(1000) NOT NULL,
                    `plan_id` INT(11) NOT NULL,
                    `plan_name` VARCHAR(1000) NOT NULL,
                    `voucher_code` VARCHAR(255) NOT NULL,
                    `amount` INT(11) NOT NULL,
                    `phone_number` VARCHAR(255) NOT NULL,
                    `transaction_status` VARCHAR(255) NOT NULL,
                    `gateway_response` TEXT,
                    `payment_gateway` VARCHAR(255),
                    `payment_method` VARCHAR(255),
                    `ip_address` VARCHAR(255),
                    `mac_address` VARCHAR(255),
                    `mac_status` ENUM('Active','Banned') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
                    `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `payment_date` DATETIME DEFAULT NULL,
                    `expired_date` DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        } catch (PDOException $e) {
            echo "Error creating tbl_hotspot_payments table: " . $e->getMessage();
        }
    }

    // Create the tbl_hotspot_vouchers table if it doesn't exist
    if (!in_array('tbl_hotspot_vouchers', $tables)) {
        try {
            $db->exec("
                CREATE TABLE `tbl_hotspot_vouchers` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `code` VARCHAR(255) NOT NULL,
                    `server` VARCHAR(255) NOT NULL,
                    `plan_id` INT(11) NOT NULL,
                    `price` DECIMAL(10,2) NOT NULL,
                    `validity` VARCHAR(255) NULL,
                    `validity_unit` VARCHAR(255) NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
                    `used_date` DATETIME NULL DEFAULT NULL,
                    `generated_by` INT NOT NULL DEFAULT 0 COMMENT 'id admin',
                    `is_admin` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=admin, 0=reseller',
                    `reseller_id` INT NOT NULL DEFAULT 0 COMMENT 'reseller id',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        } catch (PDOException $e) {
            echo "Error creating tbl_hotspot_vouchers table: " . $e->getMessage();
        }
    }

    try {

        $cols = [];
        $res = $db->query("SHOW COLUMNS FROM tbl_hotspot_vouchers");

        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $row['Field'];
        }

        // ================= reseller_id =================
        if (!in_array('reseller_id', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD reseller_id INT DEFAULT 0
                AFTER generated_by
            ");
        }

        // ================= is_admin =================
        if (!in_array('is_admin', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD is_admin TINYINT(1) NOT NULL DEFAULT 1
                AFTER generated_by
            ");
        }

        // ================= validity =================
        if (!in_array('validity', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD validity VARCHAR(255) NULL
                AFTER price
            ");
        }

        // ================= validity_unit =================
        if (!in_array('validity_unit', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD validity_unit VARCHAR(255) NULL
                AFTER validity
            ");
        }

        // ================= mac_lock =================
        if (!in_array('mac_lock', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD mac_lock TINYINT(1) DEFAULT 0
            ");
        }

        // ================= mac_address =================
        if (!in_array('mac_address', $cols)) {
            $db->exec("
                ALTER TABLE tbl_hotspot_vouchers
                ADD mac_address VARCHAR(255) NULL
            ");
        }

    } catch (Exception $e) {
        // silent fail
    }

    // ================= RESELLERS =================
    if (!in_array('tbl_hotspot_resellers', $tables)) {
        try {
            $db->exec("
            CREATE TABLE `tbl_hotspot_resellers` (
                `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) UNIQUE,
                `password` VARCHAR(255),
                `fullname` VARCHAR(255),
                `phone` VARCHAR(50),
                `balance` DECIMAL(15,2) DEFAULT 0,
                `commission_total` DECIMAL(15,2) DEFAULT 0,
                `total_sold_tokens` INT(11) DEFAULT 0,
                `status` ENUM('active','suspended') DEFAULT 'active',
                `last_login` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (PDOException $e) {
            echo "Error creating tbl_hotspot_resellers table: " . $e->getMessage();
        }
    }

    // ================= TOPUPS =================
    if (!in_array('tbl_hotspot_resellers_topups', $tables)) {
        try {
            $db->exec("
            CREATE TABLE `tbl_hotspot_resellers_topups` (
                `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
                `reseller_id` INT(11),
                `old_balance` DECIMAL(15,2),
                `new_balance` DECIMAL(15,2),
                `total_balance` DECIMAL(15,2),
                `added_by` VARCHAR(100),
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (PDOException $e) {
            echo "Error creating tbl_hotspot_resellers_topups table: " . $e->getMessage();
        }
    }

    register_menu(" Hotspot", true, "hotspot_overview", 'AFTER_MESSAGE', 'ion ion-android-wifi', '', "");
    register_menu("Hotspot System Settings", true, "hotspot_config", 'SETTINGS', '', '', "");
    register_menu(" Hotspot Voucher", true, "hotspot_voucher", 'AFTER_MESSAGE', 'ion ion-card', '', "");
    register_hook('cronjob', 'hotspot_cron');

    /**
     * Clear hotspot overview JSON cache (call after a payment is confirmed).
     */
    function hotspot_invalidate_overview_cache()
    {
        $cachePath = 'system/cache/';
        if (!is_dir($cachePath)) {
            return;
        }
        foreach (glob($cachePath . 'hotspot_overview_*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    function hotspot_sync_pending_campay_payments($limit = 15, $curlTimeout = 30)
    {
        if (!function_exists('hotspot_pg_campay_sync_transaction')) {
            return;
        }
        $pending = ORM::for_table('tbl_hotspot_payments')
            ->where('payment_gateway', 'campay')
            ->where('transaction_status', 'pending')
            ->where_not_equal('transaction_id', '')
            ->order_by_desc('id')
            ->limit(max(1, (int) $limit))
            ->find_many();
        foreach ($pending as $trx) {
            try {
                hotspot_pg_campay_sync_transaction($trx, (int) $curlTimeout);
            } catch (Throwable $e) {
                _log('[Hotspot] CamPay sync skipped for trx #' . (int) $trx->id . ': ' . $e->getMessage());
            }
        }
    }

    function hotspot_format_trx_display($id)
    {
        $id = trim((string) $id);
        if ($id === '') {
            return '—';
        }
        if (strlen($id) > 22) {
            return substr($id, 0, 8) . '…' . substr($id, -6);
        }

        return $id;
    }

    function hotspot_payment_failure_reason($payment)
    {
        if (!$payment) {
            return '';
        }
        if (function_exists('hotspot_pg_campay_payment_failure_reason')
            && strtolower(trim((string) ($payment->payment_gateway ?? ''))) === 'campay') {
            return hotspot_pg_campay_payment_failure_reason($payment);
        }

        return '';
    }

    function hotspot_overview()
    {
        global $ui;
        _admin();
        $ui->assign('_title', 'Advanced Hotspot System');
        $ui->assign('_system_menu', '');
        $admin = Admin::_info();
        $ui->assign('_admin', $admin);

        // Access Control
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (!function_exists('smarty_function_hotspot_trx_short')) {
            function smarty_function_hotspot_trx_short($params, $smarty)
            {
                return htmlspecialchars(hotspot_format_trx_display($params['id'] ?? ''), ENT_QUOTES, 'UTF-8');
            }
        }
        if (!function_exists('smarty_function_hotspot_payment_failure_reason')) {
            function smarty_function_hotspot_payment_failure_reason($params, $smarty)
            {
                $payment = $params['payment'] ?? null;
                return htmlspecialchars(hotspot_payment_failure_reason($payment), ENT_QUOTES, 'UTF-8');
            }
        }
        $ui->registerPlugin('function', 'hotspot_trx_short', 'smarty_function_hotspot_trx_short');
        $ui->registerPlugin('function', 'hotspot_payment_failure_reason', 'smarty_function_hotspot_payment_failure_reason');

        // Refresh must redirect immediately — never run slow sync/API work first.
        if (isset($_GET['refresh'])) {
            hotspot_invalidate_overview_cache();
            r2(U . 'plugin/hotspot_overview', 's', 'Data Refreshed');
        }

        $CACHE_PATH = 'system/cache/';
        hotspot_sync_pending_campay_payments(3, 10);

        $cacheFile = $CACHE_PATH . "hotspot_overview_" . hotspot_overview_cache_key($admin) . ".json";
        $cacheTime = 120; 

        // Cache Loading Logic
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            // Safety check for array_map to prevent type errors
            $raw_data = $cacheData['payments_raw'] ?? [];
            $payments = [];
            if (!empty($raw_data)) {
                $payments = array_map(function ($p) {
                    return ORM::for_table('tbl_hotspot_payments')->create($p);
                }, $raw_data);
            }

            foreach ($cacheData as $key => $value) {
                if ($key !== 'payments_raw') $ui->assign($key, $value);
            }
            $ui->assign('payments', $payments);
            $latestRow = ORM::for_table('tbl_hotspot_payments')->order_by_desc('id')->find_one();
            $ui->assign('hotspot_latest_payment_id', $latestRow ? (int) $latestRow->id : 0);
            $ui->assign('can_delete_hotspot_history', hotspot_can_delete_transaction_history($admin));
            $ui->display('[plugin]hotspot_overview.tpl');
            exit;
        }

        // --- FRESH DATA RETRIEVAL ---

        // 1. Transaction History
        $payments_res = hotspot_payments_query_for_admin($admin)->order_by_desc('created_date')->find_many();
        $payments_array = [];
        if ($payments_res) {
            foreach ($payments_res as $p) {
                $payments_array[] = $p->as_array();
            }
        }

        // 2. Payment Stats
        $successfulPayments = hotspot_payments_query_for_admin($admin)->where('transaction_status', 'paid')->count();
        $failedPayments = hotspot_payments_query_for_admin($admin)->where('transaction_status', 'failed')->count();
        $pendingPayments = hotspot_payments_query_for_admin($admin)->where('transaction_status', 'pending')->count();
        $cancelledPayments = hotspot_payments_query_for_admin($admin)->where('transaction_status', 'cancelled')->count();

        // 3. Voucher Inventory (Table: tbl_hotspot_vouchers)
        $total_vouchers = ORM::for_table('tbl_hotspot_vouchers')->count();
        $used_vouchers = ORM::for_table('tbl_hotspot_vouchers')->where('is_used', 1)->count();
        $available_vouchers = ORM::for_table('tbl_hotspot_vouchers')->where('is_used', 0)->count();
        $expired_vouchers = $failedPayments + $cancelledPayments;

        // 4. Monthly Sales
        $monthlySales = hotspot_payments_query_for_admin($admin)
            ->select_expr('YEAR(created_date)', 'year')
            ->select_expr('MONTH(created_date)', 'month')
            ->select_expr('SUM(amount)', 'total_sales')
            ->where('transaction_status', 'paid')
            ->group_by('year')->group_by('month')
            ->find_array();

        $monthlyData = [];
        foreach ($monthlySales as $sale) {
            $monthlyData[$sale['month'] . '-' . $sale['year']] = $sale['total_sales'];
        }

        // 5. Weekly Sales
        $startDate = date('Y-m-d', strtotime('this week Monday'));
        $endDate = date('Y-m-d', strtotime('this week Sunday'));
        $weeklySales = hotspot_payments_query_for_admin($admin)
            ->select_expr('DAYOFWEEK(created_date)', 'day_idx')
            ->select_expr('SUM(amount)', 'total_sales')
            ->where('transaction_status', 'paid')
            ->where_gte('created_date', $startDate)
            ->where_lte('created_date', $endDate)
            ->group_by('day_idx')
            ->find_array();

        $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weeklyValues = array_fill(0, 7, 0);
        foreach ($weeklySales as $sale) {
            $weeklyValues[$sale['day_idx'] - 1] = $sale['total_sales'];
        }

        // 6. Daily Sales
        $today = date('Y-m-d');
        $dailySalesSum = hotspot_payments_query_for_admin($admin)
            ->where('transaction_status', 'paid')
            ->where_raw("DATE(created_date) = ?", [$today])
            ->sum('amount');

        // Prepare Final Cache Data
        $cacheData = [
            'successfulPayments' => $successfulPayments,
            'failedPayments'     => $failedPayments,
            'pendingPayments'    => $pendingPayments,
            'cancelledPayments'  => $cancelledPayments,
            'total_vouchers'     => $total_vouchers,
            'used_vouchers'      => $used_vouchers,
            'available_vouchers' => $available_vouchers,
            'expired_vouchers'   => $expired_vouchers,
            'dailySalesData'     => json_encode([$today => $dailySalesSum ?: 0]),
            'chartData'          => json_encode(['labels' => $daysOfWeek, 'data' => $weeklyValues]),
            'monthlyData'        => json_encode($monthlyData),
            'payments_raw'       => $payments_array, 
            'xheader'            => ''
        ];

        file_put_contents($cacheFile, json_encode($cacheData));

        // Assign to Smarty
        foreach ($cacheData as $key => $value) {
            if ($key !== 'payments_raw') $ui->assign($key, $value);
        }
        
        $ui->assign('payments', $payments_res);
        $latestRow = ORM::for_table('tbl_hotspot_payments')->order_by_desc('id')->find_one();
        $ui->assign('hotspot_latest_payment_id', $latestRow ? (int) $latestRow->id : 0);
        $ui->assign('can_delete_hotspot_history', hotspot_can_delete_transaction_history($admin));
        $ui->display('[plugin]hotspot_overview.tpl');
    }

    /**
     * Lightweight JSON ping for hotspot overview auto-refresh.
     */
    function hotspot_overview_ping()
    {
        _admin();
        header('Content-Type: application/json; charset=utf-8');
        $latest = ORM::for_table('tbl_hotspot_payments')->order_by_desc('id')->find_one();
        echo json_encode([
            'pending' => (int) ORM::for_table('tbl_hotspot_payments')->where('transaction_status', 'pending')->count(),
            'paid' => (int) ORM::for_table('tbl_hotspot_payments')->where('transaction_status', 'paid')->count(),
            'latest_id' => $latest ? (int) $latest->id : 0,
            'latest_status' => $latest ? (string) $latest->transaction_status : '',
        ]);
        exit;
    }

    function hotspot_can_delete_transaction_history($admin)
    {
        return false;
    }

    /**
     * Resolve MikroTik identity / router label to a tbl_routers row (canonical name).
     */
    function hotspot_resolve_router($routername)
    {
        $routername = trim((string) $routername);
        if ($routername === '') {
            return null;
        }

        $router = ORM::for_table('tbl_routers')->where('name', $routername)->find_one();
        if ($router) {
            return $router;
        }

        $router = ORM::for_table('tbl_routers')->where('description', $routername)->find_one();
        if ($router) {
            return $router;
        }

        $routerIp = explode(':', $routername)[0];
        if ($routerIp !== '') {
            $router = ORM::for_table('tbl_routers')->where_like('ip_address', $routerIp . '%')->find_one();
            if ($router) {
                return $router;
            }
        }

        return null;
    }

    /**
     * Nom canonique MikroTik (tbl_routers.name) pour comparer forfaits / paiements par routeur.
     */
    function hotspot_admin_router_names($admin)
    {
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return null;
        }

        $adminId = (int) ($admin['id'] ?? 0);
        if ($adminId <= 0) {
            return [];
        }

        $names = [];
        foreach (ORM::for_table('tbl_routers')->where('admin_id', $adminId)->find_many() as $router) {
            $name = trim((string) ($router->name ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
            $description = trim((string) ($router->description ?? ''));
            if ($description !== '') {
                $names[] = $description;
            }
        }

        return array_values(array_unique($names));
    }

    function hotspot_payments_query_for_admin($admin)
    {
        $q = ORM::for_table('tbl_hotspot_payments');
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return $q;
        }

        $adminId = (int) ($admin['id'] ?? 0);
        if ($adminId <= 0) {
            return $q->where_raw('1 = 0');
        }

        $routerNames = hotspot_admin_router_names($admin);
        $matchValues = is_array($routerNames) ? $routerNames : [];
        foreach (ORM::for_table('tbl_routers')->where('admin_id', $adminId)->find_many() as $router) {
            $ip = trim(explode(':', (string) ($router->ip_address ?? ''))[0]);
            if ($ip !== '') {
                $matchValues[] = $ip;
            }
        }
        $matchValues = array_values(array_unique(array_filter($matchValues)));

        $planIds = [];
        foreach (ORM::for_table('tbl_plans')->where('admin_id', $adminId)->where('type', 'Hotspot')->find_many() as $plan) {
            $planIds[] = (int) $plan->id;
        }

        if ($matchValues === [] && $planIds === []) {
            return $q->where_raw('1 = 0');
        }

        $parts = [];
        $params = [];
        if ($matchValues !== []) {
            $parts[] = 'router_name IN (' . implode(',', array_fill(0, count($matchValues), '?')) . ')';
            $params = array_merge($params, $matchValues);
        }
        if ($planIds !== []) {
            $parts[] = 'plan_id IN (' . implode(',', array_fill(0, count($planIds), '?')) . ')';
            $params = array_merge($params, $planIds);
        }

        return $q->where_raw('(' . implode(' OR ', $parts) . ')', $params);
    }

    function hotspot_overview_cache_key($admin)
    {
        return md5(($admin['user_type'] ?? '') . ':' . (int) ($admin['id'] ?? 0));
    }

    function hotspot_normalize_router_name($routerInput)
    {
        $routerInput = trim((string) $routerInput);
        if ($routerInput === '' || preg_match('/^\$\(/', $routerInput)) {
            $routerInput = WifiZoneHotspot::resolvePublicRouterName('');
        }
        if ($routerInput === '') {
            return '';
        }
        $row = hotspot_resolve_router($routerInput);

        return $row ? trim((string) $row['name']) : $routerInput;
    }

    function hotspot_customer_has_active_recharge($customerId, $routerName = '')
    {
        if ((int) $customerId <= 0) {
            return null;
        }
        $routerName = hotspot_normalize_router_name($routerName);
        $q = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', (int) $customerId)
            ->where('status', 'on')
            ->where('type', 'Hotspot')
            ->order_by_desc('id');
        if ($routerName !== '') {
            $q->where('routers', $routerName);
        }
        foreach ($q->find_many() as $recharge) {
            if (Package::isRechargeActive($recharge)) {
                return $recharge;
            }
        }

        return null;
    }

    function hotspot_cleanup_stale_recharge($customerId, $routerName = '')
    {
        if ((int) $customerId <= 0) {
            return;
        }
        $routerName = hotspot_normalize_router_name($routerName);
        $q = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', (int) $customerId)
            ->where('status', 'on')
            ->where('type', 'Hotspot');
        if ($routerName !== '') {
            $q->where('routers', $routerName);
        }
        foreach ($q->find_many() as $stale) {
            if (!Package::isRechargeActive($stale)) {
                $stale->status = 'off';
                $stale->save();
            }
        }
    }

    function hotspot_find_paid_payment_by_phone($phone, $routerName = '')
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 9) {
            return null;
        }
        $local = substr($digits, -9);
        $routerName = hotspot_normalize_router_name($routerName);
        $q = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_status', 'paid')
            ->order_by_desc('id');
        if ($routerName !== '') {
            $q->where('router_name', $routerName);
        }
        foreach ($q->limit(50)->find_many() as $trx) {
            $trxDigits = preg_replace('/\D/', '', (string) $trx->phone_number);
            if ($trxDigits !== '' && substr($trxDigits, -9) === $local) {
                return $trx;
            }
        }

        return null;
    }

    /**
     * Retry Mikrotik/DB activation when CamPay succeeded but recharge was not created.
     */
    function hotspot_retry_activate_payment($trx)
    {
        if (!$trx) {
            return false;
        }

        $status = (string) $trx->transaction_status;
        if (!in_array($status, ['paid', 'pending', 'failed'], true)) {
            return false;
        }

        $customer = HotspotCustomer::findByPhone($trx->phone_number ?? '');
        if ($customer && hotspot_customer_has_active_recharge($customer->id, (string) ($trx->router_name ?? ''))) {
            return true;
        }

        $gateway = (string) ($trx->payment_gateway ?? '');
        if ($gateway === 'campay' && function_exists('hotspot_pg_campay_activate_user')) {
            return hotspot_pg_campay_activate_user($trx, 'CamPay');
        }
        if ($gateway === 'mypvit' && function_exists('hotspot_pg_mypvit_activate_user')) {
            return hotspot_pg_mypvit_activate_user($trx, 'MyPVit');
        }

        return false;
    }

    function hotspot_plan()
    {
        global $config;
        $currency = $config['currency_code'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $response = [
                'ResultCode' => "201",
                'message' => "Invalid Request method"
            ];
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit;
        }

        $routerInput = trim((string) ($_POST['routername'] ?? ''));

        if ($routerInput === '') {
            $response = [
                'ResultCode' => "202",
                'message' => "Please fill all fields (router name)"
            ];
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit;
        }

        $checkrouter = hotspot_resolve_router($routerInput);
        if (!$checkrouter) {
            $response = [
                'ResultCode' => "205",
                'message' => "Router Not Found: " . $routerInput
            ];
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit;
        }

        $routername = $checkrouter['name'];

        $cacheDir = 'system/cache/';
        $cacheKey = WifiZoneHotspot::hotspotPlanCacheKey($routername);
        $cacheFile = "$cacheDir$cacheKey.json";
        $cacheTime = 0;

        // Create cache directory if not exists
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Check if cache file exists and is still valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            echo file_get_contents($cacheFile);
            exit;
        }

        $hotspotplan = WifiZoneHotspot::plansQueryForRouter($routername)->find_many();

        if (count($hotspotplan) > 0) {
            $response = [
                'ResultCode' => "200",
                'message' => "Success",
                'data' => []
            ];

            foreach ($hotspotplan as $row) {
                $limittype = $row->typebp;
                $bandwidth = $row->id_bw;
                $bandwidthrow = ORM::for_table('tbl_bandwidth')->where('id', $bandwidth)->find_one();

                if ($bandwidthrow) {
                    $rate_down = $bandwidthrow->rate_down;
                    $rate_down_unit = $bandwidthrow->rate_down_unit;
                    $rate_up = $bandwidthrow->rate_up;
                    $rate_up_unit = $bandwidthrow->rate_up_unit;
                    $downlimit = "$rate_down $rate_down_unit";
                    $uplimit = "$rate_up $rate_up_unit";
                } else {
                    $downlimit = 'Unlimited';
                    $uplimit = 'Unlimited';
                }

                $paymentlink = U . "plugin/hotspot_pay&planid=" . $row->id . "&routername=" . $routername;
                $planId = $row->id;
                switch ($limittype) {
                        case 'Unlimited':
                            $validity = "{$row->validity} {$row->validity_unit}";
                            $data = [
                                'plantype' => "Time Limit",
                                'planname' => $row->name_plan,
                                'currency' => $currency,
                                'price' => $row->price,
                                'downlimit' => $downlimit,
                                'uplimit' => $uplimit,
                                'timelimit' => "Unlimited",
                                'validity' => $validity,
                                'device' => $row->shared_users,
                                'datalimit' => 'Unlimited',
                                'paymentlink' => $paymentlink,
                                'planid' => $planId,
                                'planId' => $planId,
                                'routerName' => $routername
                            ];
                            $response['data'][] = $data;
                            break;
                        case 'Limited':
                            $limit_type = $row->limit_type;
                            switch ($limit_type) {
                                case 'Time_Limit':
                                    $timelimit = "{$row->time_limit} {$row->time_unit}";
                                    $validity = "{$row->validity} {$row->validity_unit}";
                                    $data = [
                                        'plantype' => "Time Limit",
                                        'planname' => $row->name_plan,
                                        'currency' => $currency,
                                        'price' => $row->price,
                                        'downlimit' => $downlimit,
                                        'uplimit' => $uplimit,
                                        'timelimit' => $timelimit,
                                        'validity' => $validity,
                                        'device' => $row->shared_users,
                                        'datalimit' => 'Unlimited',
                                        'paymentlink' => $paymentlink,
                                        'planid' => $planId,
                                'planId' => $planId,
                                        'routerName' => $routername
                                    ];
                                    $response['data'][] = $data;
                                    break;
                                case 'Data_Limit':
                                    $datalimit = "{$row->data_limit} {$row->data_unit}";
                                    $validity = "{$row->validity} {$row->validity_unit}";
                                    $data = [
                                        'plantype' => "Data Limit",
                                        'planname' => $row->name_plan,
                                        'currency' => $currency,
                                        'price' => $row->price,
                                        'downlimit' => $downlimit,
                                        'uplimit' => $uplimit,
                                        'timelimit' => 'Unlimited',
                                        'validity' => $validity,
                                        'device' => $row->shared_users,
                                        'datalimit' => $datalimit,
                                        'paymentlink' => $paymentlink,
                                        'planid' => $planId,
                                'planId' => $planId,
                                        'routerName' => $routername
                                    ];
                                    $response['data'][] = $data;
                                    break;
                                case 'Both_Limit':
                                    $timelimit = "{$row->time_limit} {$row->time_unit}";
                                    $datalimit = "{$row->data_limit} {$row->data_unit}";
                                    $validity = "{$row->validity} {$row->validity_unit}";
                                    $data = [
                                        'plantype' => "Time & Data Limit",
                                        'planname' => $row->name_plan,
                                        'currency' => $currency,
                                        'price' => $row->price,
                                        'downlimit' => $downlimit,
                                        'uplimit' => $uplimit,
                                        'timelimit' => $timelimit,
                                        'validity' => $validity,
                                        'device' => $row->shared_users,
                                        'datalimit' => $datalimit,
                                        'paymentlink' => $paymentlink,
                                        'planid' => $planId,
                                'planId' => $planId,
                                        'routerName' => $routername
                                    ];
                                    $response['data'][] = $data;
                                    break;
                                default:
                                    _log(
                                        'Hotspot plan skipped (unknown limit type): '
                                        . (string) ($row->name_plan ?? '')
                                        . ' / ' . (string) ($row->limit_type ?? ''),
                                        'Hotspot',
                                        0
                                    );
                                    break;
                            }
                            break;
                        default:
                            _log(
                                'Hotspot plan skipped (unknown bandwidth type): '
                                . (string) ($row->name_plan ?? '')
                                . ' / ' . (string) $limittype,
                                'Hotspot',
                                0
                            );
                            break;
                    }
            }

            $responseJson = json_encode($response, JSON_PRETTY_PRINT);
            file_put_contents($cacheFile, $responseJson); // Save to cache file
            echo $responseJson;
        } else {
            $response = [
                'ResultCode' => "204",
                'message' => "No Hotspot Plan Found"
            ];
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }
    function hotspot_wants_json_response($payload = null)
    {
        if (is_array($payload)) {
            if (!empty($payload['ajax']) || !empty($payload['pay'])) {
                return true;
            }
        }
        if (!empty($_GET['ajax']) || !empty($_POST['ajax']) || !empty($_GET['pay']) || !empty($_POST['pay'])) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return strpos($accept, 'application/json') !== false;
    }

    function hotspot_respond_json(array $data, $httpCode = 200)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code((int) $httpCode);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    function hotspot_pay()
    {
        global $config;

        $paymentInput = hotspot_payment_payload();
        $wantsJson = hotspot_wants_json_response($paymentInput);

        if ($config['maintenance_mode']) {
            if ($wantsJson) {
                hotspot_respond_json(['ok' => false, 'message' => Lang::T('Service is under maintenance. Please try again later.')], 503);
            }
            displayMaintenanceMessage();
            die();
        }

        $payment_gateways = hotspot_getAvailablePaymentGateways();

        $routername = '';
        $planid = '';
        $mac_address = '';
        $ip_address = '';
        $amount = '';
        $plan_name = '';
        $validity = '';

        if (!empty($paymentInput['planid']) || !empty($paymentInput['routername'])) {
            $routername = hotspot_normalize_router_name((string) ($paymentInput['routername'] ?? ''));
            $planid = (string) ($paymentInput['planid'] ?? '');
            $mac_address = (string) ($paymentInput['mac'] ?? $paymentInput['mac_address'] ?? '');
            $ip_address = (string) ($paymentInput['ip'] ?? $paymentInput['ip_address'] ?? '');

            $mac_address = hotspot_validateMacAddress($mac_address);

            $plan = hotspot_getHotspotPlan(
                $planid,
                $routername,
                (string) ($paymentInput['plan_name'] ?? '')
            );
            if (!$plan) {
                _log(
                    'Hotspot pay: plan introuvable planid=' . $planid
                    . ' router=' . $routername
                    . ' plan_name=' . (string) ($paymentInput['plan_name'] ?? ''),
                    'Hotspot',
                    0
                );
                hotspot_throwError(Lang::T("Invalid plan selected."));
            }

            $amount = $plan['price'];
            $plan_name = $plan['name_plan'];
            $planid = (string) $plan['id'];
            $validity = $plan['validity'] . ' ' . $plan['validity_unit'];
        }

        if (!empty($paymentInput['pay'])) {
            $payment_data = hotspot_validateAndPreparePaymentData($paymentInput);

            if (!isset($paymentInput['type'])) {
                hotspot_throwError(Lang::T("Payment type is required."));
            }

            $type = $paymentInput['type'];
            $gateway = preg_replace('/[^a-zA-Z0-9_]/', '', $payment_data['payment_gateway']);

            if ($type === 'token') {
                $token = $paymentInput['payment_token'] ?? null;
                if (empty($token)) {
                    hotspot_throwError(Lang::T("Payment token is required."));
                }
                if (!ctype_digit($token)) {
                    hotspot_throwError(Lang::T("Invalid token value, Token must be only numeric value."));
                }

                $function_name = "hotspot_processPayment_tokens";
                if (function_exists($function_name)) {
                    $result = $function_name($payment_data);
                    if (!$result) {
                        hotspot_throwError(Lang::T("Failed to process payment using payment token. Please try again."));
                    }
                    echo $result;
                } else {
                    sendTelegram("Error: Token payment processing function not found, Please Check the system");
                    hotspot_throwError(Lang::T("We are currently experiencing problems trying to connect to this module. Please go back and try again, or report this issue to ") . ' <a href="tel:' . ($config['phone'] ?? 'Not Available') . '">' . ($config['phone'] ?? 'Not Available') . '</a><br><br>' . Lang::T("Thanks."));
                }
            } else {
                $function_name = "hotspot_processPayment_$gateway";
                if (function_exists($function_name)) {
                    $function_name($payment_data);
                } else {
                    hotspot_throwError(Lang::T("$gateway payment processing function not found. Please go back and try again, or report this issue to ") . ' <a href="tel:' . ($config['phone'] ?? 'Not Available') . '">' . ($config['phone'] ?? 'Not Available') . '</a><br><br>' . Lang::T("Thanks."));
                }
            }
        } else {
            if ($wantsJson || strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', 'POST') === 0) {
                $receivedKeys = implode(',', array_keys($paymentInput));
                _log(
                    'Hotspot pay: missing pay param, keys=' . ($receivedKeys !== '' ? $receivedKeys : 'aucun')
                        . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '')
                        . ' ct=' . ($_SERVER['CONTENT_TYPE'] ?? ''),
                    'Hotspot',
                    0
                );
                hotspot_respond_json(['ok' => false, 'message' => 'Requête de paiement incomplète. Réessayez depuis le portail captif.']);
            }
            hotspot_displayPaymentForm($payment_gateways, $planid, $plan_name, $amount, $routername, $validity, $mac_address, $ip_address);
        }
    }


    function hotspot_getAvailablePaymentGateways()
    {
        $payment_gateway_files = glob('system/plugin/hotspot_pg-*.php');
        $payment_gateways = [];

        foreach ($payment_gateway_files as $file) {
            $parts = explode('-', basename($file, '.php'));
            $gateway_identifier = $parts[1] ?? 'unknown';
            $payment_gateways[] = [
                'filename' => basename($file),
                'value' => $gateway_identifier,
                'name' => str_replace('_', ' ', ucfirst($gateway_identifier))
            ];
        }
        return $payment_gateways;
    }

    function hotspot_getEmailAddress($phone)
    {
        $serverHost = $_SERVER['HTTP_HOST'];

        $email = ($serverHost === 'localhost') ? "$phone@$serverHost.com" : "$phone@$serverHost";
        return $email;
    }

    function hotspot_getHotspotPlan($planid, $routerName = '', $planName = '')
    {
        $planid = (int) $planid;
        $rawRouter = trim((string) $routerName);
        $routerName = hotspot_normalize_router_name($routerName);
        $planName = trim((string) $planName);
        $ownerId = ($routerName !== '' && class_exists('WifiZoneHotspot'))
            ? WifiZoneHotspot::routerAdminId($routerName)
            : 0;

        $baseQuery = static function () use ($ownerId) {
            $q = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->where('enabled', 1);
            if ($ownerId > 0) {
                $q->where('admin_id', $ownerId);
            }

            return $q;
        };

        if ($planid > 0) {
            $byId = $baseQuery()->where('id', $planid)->find_one();
            if (!$byId && $ownerId <= 0) {
                $candidate = ORM::for_table('tbl_plans')
                    ->where('type', 'Hotspot')
                    ->where('enabled', 1)
                    ->where('id', $planid)
                    ->find_one();
                if ($candidate) {
                    $planRouter = hotspot_normalize_router_name((string) $candidate->routers);
                    $expectedOwner = class_exists('WifiZoneHotspot') ? WifiZoneHotspot::routerAdminId($planRouter) : 0;
                    if ($expectedOwner <= 0 || (int) $candidate->admin_id === $expectedOwner) {
                        $byId = $candidate;
                    }
                }
            }

            if ($byId) {
                $planRouter = hotspot_normalize_router_name((string) $byId->routers);
                if ($routerName !== '' && strcasecmp($planRouter, $routerName) !== 0) {
                    // Identité MikroTik ($(identity), ex. 92KG) ≠ nom DYRSIA (ex. rbo) : faire confiance au planid.
                    if ($rawRouter !== '' && hotspot_resolve_router($rawRouter) === null) {
                        return $byId;
                    }

                    _log(
                        'Hotspot pay: forfait #' . $planid
                        . ' routeur=' . $planRouter
                        . ' attendu=' . $routerName,
                        'Hotspot',
                        0
                    );

                    return null;
                }

                return $byId;
            }
        }

        if ($routerName !== '' && $planName !== '') {
            foreach (WifiZoneHotspot::plansQueryForRouter($routerName)->find_many() as $candidate) {
                if (strcasecmp((string) $candidate->name_plan, $planName) === 0) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    function hotspot_plan_router_name($plan)
    {
        if (!$plan) {
            return '';
        }

        return hotspot_normalize_router_name((string) ($plan->routers ?? $plan['routers'] ?? ''));
    }

    function hotspot_resolve_payment_phone(array $data)
    {
        foreach (['msisdn', 'hmobile', 'phonenumber', 'mobile', 'from', 'phone', 'n'] as $alias) {
            if (!isset($data[$alias]) || $data[$alias] === '' || $data[$alias] === null) {
                continue;
            }
            $value = preg_replace('/\D/', '', (string) $data[$alias]);
            if ($value === '') {
                continue;
            }
            if ($alias === 'n' && preg_match('/^(?:237)?[62]\d{8}$/', $value)) {
                return $value;
            }
            if ($alias !== 'n') {
                return $value;
            }
        }

        if (!empty($data['pd'])) {
            $decoded = base64_decode((string) $data['pd'], true);
            if ($decoded !== false) {
                $digits = preg_replace('/\D/', '', $decoded);
                if ($digits !== '') {
                    return $digits;
                }
            }
        }

        return '';
    }

    function hotspot_payment_payload()
    {
        $payload = is_array($_POST) ? $_POST : [];
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $raw = file_get_contents('php://input');

        if (is_string($raw) && $raw !== '') {
            if (stripos($contentType, 'application/json') !== false) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $payload = array_merge($json, $payload);
                }
            } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false || $contentType === '' || strpos($raw, '=') !== false) {
                $parsed = [];
                parse_str($raw, $parsed);
                if (is_array($parsed)) {
                    $payload = array_merge($parsed, $payload);
                }
            }
        }

        if (is_array($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key === '_route' || $key === 'format') {
                    continue;
                }
                if (!isset($payload[$key]) || $payload[$key] === '' || $payload[$key] === null) {
                    $payload[$key] = $value;
                }
            }
        }

        $payload['phone'] = hotspot_resolve_payment_phone($payload);

        return $payload;
    }

    function hotspot_validateAndPreparePaymentData($post_data)
    {
        $post_data['phone'] = hotspot_resolve_payment_phone($post_data);

        foreach (['routername', 'planid', 'amount'] as $field) {
            if (empty($post_data[$field])) {
                hotspot_throwError(ucfirst($field) . ' ' . Lang::T(" is required."));
            }
        }

        if ($post_data['phone'] === '') {
            $receivedKeys = implode(',', array_keys($post_data));
            _log(
                'Hotspot pay: msisdn/phone missing, keys=' . $receivedKeys
                    . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '')
                    . ' qs=' . ($_SERVER['QUERY_STRING'] ?? ''),
                'Hotspot',
                0
            );
            hotspot_throwError('Numéro incorrect ou non reçu. Entrez 9 chiffres (ex: 677123456). Champs reçus: ' . ($receivedKeys !== '' ? $receivedKeys : 'aucun'));
        }

        $phone = $post_data['phone'];
        $phone = hotspot_formatPhoneNumber($phone);

        $countryCode = preg_replace('/\D/', '', (string) ($config['country_code_phone'] ?? ''));
        if ($countryCode === '') {
            $countryCode = '237';
        }
        if ($countryCode === '237' && !preg_match('/^237[62]\d{8}$/', $phone)) {
            hotspot_throwError('Numéro incorrect. Entrez 9 chiffres (ex: 677123456).');
        } elseif (!is_numeric($phone)) {
            hotspot_throwError(Lang::T('Phone number is invalid, please check and try again.'));
        }

        if (substr($phone, 0, 3) == '220') {
            // continue
        } elseif (strlen($phone) < 9) {
            hotspot_throwError(Lang::T("Phone number is invalid, please check and try again."));
        }


        $mac_address = $post_data['mac_address'] ?? null;
        if (!$mac_address) {
            $mac_address = hotspot_getMacAddressByPhone($phone);
        }
        $mac = hotspot_validateMacAddress($mac_address);
        $routername = hotspot_normalize_router_name($post_data['routername']);
        $plan = hotspot_getHotspotPlan(
            $post_data['planid'],
            $routername,
            (string) ($post_data['plan_name'] ?? '')
        );
        if (!$plan) {
            hotspot_throwError(Lang::T("Invalid plan selected."));
        }
        $email = hotspot_getEmailAddress($phone);
        $plan_name = $plan['name_plan'];
        $routername = hotspot_plan_router_name($plan);
        return [
            'routername' => $routername,
            'planid' => (string) $plan['id'],
            'plan_name' => $plan_name,
            'payment_gateway' => $post_data['payment_gateway'],
            'phone' => $phone,
            'email' => $email,
            'amount' => (string) ($plan['price'] ?? $post_data['amount']),
            'mac_address' => $mac,
            'ip_address' => $post_data['ip_address'],
            'txref' => uniqid('trx'),
            'status' => 'pending',
            'payment_token' => $post_data['payment_token'],
        ];
    }

    function hotspot_formatPhoneNumber($phone)
    {
        global $config;

        $phone = preg_replace('/\D/', '', (string) $phone);
        $countryCode = preg_replace('/\D/', '', (string) ($config['country_code_phone'] ?? ''));

        if ($countryCode === '') {
            $countryCode = '237';
        }

        if ($phone === '') {
            return $phone;
        }

        // Déjà au format international Cameroun (237 + 9 chiffres)
        if (preg_match('/^' . preg_quote($countryCode, '/') . '[62]\d{8}$/', $phone)) {
            return $phone;
        }

        // Retirer l'indicatif s'il est présent (ex. 237677123456 ou 237677123 tronqué)
        if (strpos($phone, $countryCode) === 0) {
            $phone = substr($phone, strlen($countryCode));
        }

        $phone = ltrim($phone, '0');

        // Numéro local 9 chiffres (ex. 677123456)
        if (strlen($phone) === 9) {
            return $countryCode . $phone;
        }

        // Legacy Gambie
        if ($countryCode === '220' && strlen($phone) === 7) {
            return $countryCode . $phone;
        }

        return $countryCode . $phone;
    }


    function hotspot_getMacAddressByPhone($phone)
    {
        $mac_record = ORM::for_table('tbl_hotspot_payments')->where('phone_number', $phone)->select('mac_address')->find_one();
        return $mac_record?->mac_address;
    }
    function hotspot_replaceCountryCode($phone)
    {
        global $config;
        $phone = (string) $phone;

        if (empty($phone)) {
            return $phone;
        }

        if (!empty($config['country_code_phone'])) {
            $countryCode = preg_quote($config['country_code_phone'], '/');
            $phone = ($countryCode != '220') ? preg_replace("/^$countryCode/", '0', $phone) : preg_replace("/^$countryCode/", '', $phone);
        } else {
            $countryCodes = ['234', '254', '233', '251', '256', '220'];
            foreach ($countryCodes as $countryCode) {
                // Check if phone starts with the current country code
                if (strpos($phone, $countryCode) === 0) {
                    // Replace the country code with '0' if the country code is not 220
                    if ($countryCode != '220') {
                        $phone = preg_replace('/^' . preg_quote($countryCode, '/') . '/', '0', $phone);
                        break;
                    }
                }
            }
        }

        return $phone;
    }

    function hotspot_validateMacAddress($mac_address)
    {
        global $config;
        $mac_regex = "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/";

        if ($mac_address && !preg_match($mac_regex, $mac_address)) {
            $mac_address = "22:12:59:0C:45:58";
        }

        $blockedMacs = ORM::for_table('tbl_hotspot_payments')->where('mac_status', 'Banned')->select('mac_address')->find_many();

        $blockedMacArray = [];
        foreach ($blockedMacs as $blockedMac) {
            $blockedMacArray[] = $blockedMac->mac_address;
        }

        if ($mac_address && in_array($mac_address, $blockedMacArray)) {
            hotspot_throwError(Lang::T("This device has been blocked from accessing this service, Please Go back and try again, or Report this issue to ") . ' ' . ' <a href="tel:' . $config['phone'] . '">' . $config['phone'] . '</a>' . '<br>' . '<br>' . Lang::T("Thanks."));
        }

        return $mac_address;
    }

    function hotspot_displayPaymentForm($payment_gateways, $planid, $plan_name, $amount, $routername, $validity, $mac_address, $ip_address)
    {
        global $ui, $config;
        $ui->assign('_title', Lang::T("Hotspot Payments"));
        $ui->assign('companyName', $config['CompanyName']);
        $ui->assign('payment_gateways', $payment_gateways);
        $ui->assign('planid', $planid);
        $ui->assign('plan_name', $plan_name);
        $ui->assign('amount', $amount);
        $ui->assign('routername', $routername);
        $ui->assign('validity', $validity);
        $ui->assign('mac', $mac_address);
        $ui->assign('ip', $ip_address);
        $ui->display('[plugin]hotspot_pay.tpl');
    }

    function hotspot_savePayment($transaction_id, $transaction_ref, $amount, $phone, $planid, $plan_name, $mac_address, $ip_address,  $routername, $status, $paymentGateway, $failedMessage, $location)
    {

        if (
            empty($transaction_id) || empty($transaction_ref) || empty($amount) || empty($phone) ||
            empty($planid) || empty($plan_name) || empty($mac_address) || empty($ip_address) || empty($routername) ||
            empty($status) || empty($paymentGateway)
        ) {
            hotspot_throwError(Lang::T("Invalid input provided"));
            return;
        }

        $trx = ORM::for_table('tbl_hotspot_payments')->create();

    $trx->transaction_id = $transaction_id;
    $trx->transaction_ref = $transaction_ref;
    $trx->amount = $amount;
    $trx->phone_number = $phone;
    $trx->plan_id = $planid;
    $trx->plan_name = $plan_name;
    $trx->mac_address = $mac_address;
    $trx->ip_address = $ip_address;
    $trx->router_name = $routername;
    $trx->voucher_code = '**********';
    $trx->transaction_status = $status;
    $trx->payment_gateway = $paymentGateway;
        try {
            $trx->save();
            return $location;
        } catch (Exception $e) {
            _log(Lang::T("Failed to save transaction: ") . $e->getMessage());
            hotspot_throwError($failedMessage);
            exit;
        }
    }


    function hotspot_throwError($message)
    {
        $isAjax = hotspot_wants_json_response();
        if ($isAjax) {
            if (!headers_sent()) {
                header('Access-Control-Allow-Origin: *');
                header('Content-Type: application/json; charset=utf-8');
                header('HTTP/1.1 400 Bad Request');
            }
            echo json_encode(['ok' => false, 'message' => strip_tags((string) $message)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Construct the HTML content
        $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error: Bad Request</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f1f1f1;
                text-align: center;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 100px auto;
                padding: 20px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            h1 {
                color: #333;
                margin-top: 0;
                font-size: 24px;
            }
            p {
                color: #777;
                font-size: 16px;
            }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                font-size: 16px;
                color: #fff;
                background-color: #007bff;
                border: none;
                border-radius: 4px;
                text-decoration: none;
                cursor: pointer;
            }
            .btn:hover {
                background-color: #0056b3;
            }
            /* Responsive Styles */
            @media screen and (max-width: 600px) {
                .container {
                    margin: 50px auto;
                    padding: 10px;
                }
                h1 {
                    font-size: 20px;
                }
                p {
                    font-size: 14px;
                }
                .btn {
                    font-size: 14px;
                    padding: 8px 16px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>An Error Occured</h1>
            <p> ' . $message . '</p>
            <a href="javascript:history.back()" class="btn">Go Back</a>
        </div>
    </body>
    </html>';


        // Set the appropriate headers
        header('Content-Type: text/html');
        header('HTTP/1.1 400 Bad Request');

        // Output the HTML page
        echo $html;
        exit;
    }


    function hotspot_verify()
    {
        global $ui, $config;

        $reference = isset($_GET['reference']) ? trim((string) $_GET['reference']) : '';
        $message = isset($_GET['message']) ? (string) $_GET['message'] : '';
        $wantsJson = (isset($_GET['format']) && $_GET['format'] === 'json')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            if ($reference === '') {
                echo json_encode(['status' => 'error', 'message' => Lang::T('No reference supplied.')]);
                exit;
            }
            $check = ORM::for_table('tbl_hotspot_payments')
                ->where('transaction_ref', $reference)
                ->find_one();
            if (!$check) {
                echo json_encode(['status' => 'not_found', 'message' => Lang::T('Transaction not found.')]);
                exit;
            }
            if ((string) $check->transaction_status === 'pending' || (string) $check->transaction_status === 'failed') {
                $gateway = strtolower(trim((string) ($check->payment_gateway ?? '')));
                if ($gateway === 'campay' && function_exists('hotspot_pg_campay_sync_transaction')) {
                    $check = hotspot_pg_campay_sync_transaction($check);
                } elseif ($gateway === 'mypvit' && function_exists('hotspot_pg_mypvit_sync_transaction')) {
                    $check = hotspot_pg_mypvit_sync_transaction($check);
                }
            }
            if ((string) $check->transaction_status === 'pending' && function_exists('hotspot_retry_activate_payment')) {
                hotspot_retry_activate_payment($check);
                $check = ORM::for_table('tbl_hotspot_payments')->find_one($check->id) ?: $check;
            }
            $status = (string) $check->transaction_status;
            $payload = [
                'status' => $status,
                'reference' => $reference,
                'message' => $message,
            ];
            if ($status === 'paid') {
                $credentials = HotspotCustomer::credentialsFromPayment($check);
                if ($credentials['username'] !== '') {
                    $payload['username'] = $credentials['username'];
                    $payload['voucher_code'] = $credentials['username'];
                }
                $payload['password'] = $credentials['password'];
                $payload['auto_login'] = true;
            } elseif ($status === 'failed') {
                $failReason = hotspot_payment_failure_reason($check);
                $payload['message'] = $failReason !== ''
                    ? $failReason
                    : 'Paiement non confirmé sur votre téléphone. Aucun débit — réessayez et validez la demande Mobile Money avec votre code PIN.';
            }
            echo json_encode($payload);
            exit;
        }

        $ui->assign('_title', Lang::T("Hotspot Payment Verification"));

        $reference = isset($_GET['reference']) ? $_GET['reference'] : '';
        $message = isset($_GET['message']) ? $_GET['message'] : '';

        if ($message) {
            hotspot_verify_display_error($message);
        }

        if (!$reference) {
            hotspot_verify_display_error(Lang::T("No reference supplied."));
        }

        $check = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_ref', $reference)
            ->find_one();

        if ($check) {
            $status = $check->transaction_status;

            switch ($status) {
                case 'paid':
                    hotspot_verify_display_success($check);
                    break;
                case 'failed':
                    hotspot_verify_display_error(Lang::T("Transaction with this Reference ID: [$reference] has been processed and failed."));
                    break;
                case 'cancelled':
                    hotspot_verify_display_error(Lang::T("Transaction with this Reference ID: [$reference] has been processed and cancelled."));
                    break;
                default:
                    $ui->assign('companyName', $config['CompanyName']);
                    $ui->assign('msg', $message);
                    $ui->display('[plugin]hotspot_verify.tpl');
                    break;
            }
        } else {
            hotspot_verify_display_error(Lang::T("Transaction with this Reference ID: [$reference] not found."));
        }
    }

    function hotspot_verify_display_error($message)
    {
        $html = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Error: Bad Request</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f1f1f1;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    .container {
                        max-width: 600px;
                        margin: 100px auto;
                        padding: 20px;
                        background-color: #fff;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    }
                    h1 {
                        color: #333;
                        margin-top: 0;
                        font-size: 24px;
                    }
                    p {
                        color: #777;
                        font-size: 16px;
                    }
                    /* Responsive Styles */
                    @media screen and (max-width: 600px) {
                        .container {
                            margin: 50px auto;
                            padding: 10px;
                        }
                        h1 {
                            font-size: 20px;
                        }
                        p {
                            font-size: 14px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>An Error Occurred</h1>
                    <p>' . $message . '</p>
                </div>
            </body>
            </html>';

        // Set the appropriate headers
        header('Content-Type: text/html');
        header('HTTP/1.1 400 Bad Request');

        // Output the HTML page
        echo $html;
        exit();
    }


    function hotspot_verify_display_success($transaction)
    {
        global $config;
        $customerName = $transaction->customer_name ?? 'N/A';
    $customerPhone = $transaction->phone_number ?? 'N/A';
    $customerAddress = $transaction->customer_address ?? 'N/A';

    $orderSummary = [
        Lang::T("Order Number") => $transaction->id,
        Lang::T("Transaction ID") => $transaction->transaction_id,
        Lang::T("Transaction Ref") => $transaction->transaction_ref,
        Lang::T("Package") => $transaction->plan_name,
        Lang::T("Expiry") => $transaction->expired_date,
        Lang::T("Amount Paid") => $config['currency_code'] . number_format($transaction->amount, 2),
        Lang::T("Payment Method") => $transaction->payment_gateway,

        Lang::T("Customer Name") => $customerName,
        Lang::T("Phone") => $customerPhone,
        Lang::T("Address") => $customerAddress
    ];

        $voucherCode = $transaction->voucher_code;
        $ipAddress = $transaction->ip_address;
        $macAddress = $transaction->mac_address;
        $router = $transaction->router_name;


        $html = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Successful</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f1f1f1;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    .container {
                        max-width: 600px;
                        margin: 100px auto;
                        padding: 20px;
                        background-color: #fff;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    }
                    h1 {
                        color: #28a745;
                        margin-top: 0;
                        font-size: 24px;
                        animation: fadeIn 1s ease-in-out;
                    }
                    p {
                        color: #777;
                        font-size: 16px;
                        animation: fadeIn 1.5s ease-in-out;
                    }
                    .checkmark {
                        width: 80px;
                        height: 80px;
                        margin: 0 auto 20px auto;
                        border-radius: 50%;
                        background-color: #28a745;
                        animation: scaleUp 0.5s ease-in-out;
                        position: relative;
                    }
                    .checkmark::after {
                        content: "";
                        display: block;
                        width: 40px;
                        height: 20px;
                        border: 5px solid #fff;
                        border-width: 0 0 5px 5px;
                        transform: rotate(-45deg);
                        position: absolute;
                        top: 20px;
                        left: 20px;
                        animation: drawCheck 0.5s ease-in-out 0.5s forwards;
                    }
                    .btn {
                        display: inline-block;
                        margin-top: 20px;
                        padding: 10px 20px;
                        font-size: 16px;
                        color: #fff;
                        background-color: #007bff;
                        border: none;
                        border-radius: 4px;
                        text-decoration: none;
                        cursor: pointer;
                        animation: fadeIn 2s ease-in-out;
                    }
                    .btn:hover {
                        background-color: #0056b3;
                    }
                    .order-summary {
                        text-align: left;
                        margin: 20px auto;
                        padding: 20px;
                        background-color: #f9f9f9;
                        border-radius: 8px;
                        animation: fadeIn 2.5s ease-in-out;
                    }
                    .order-summary h2 {
                        color: #333;
                        font-size: 20px;
                        margin-bottom: 10px;
                    }
                    .order-summary table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .order-summary table, .order-summary th, .order-summary td {
                        border: 1px solid #ddd;
                    }
                    .order-summary th, .order-summary td {
                        padding: 8px;
                        text-align: left;
                    }
                    .order-summary th {
                        background-color: #f2f2f2;
                    }
                    .small-btn {
                        padding: 5px 10px;
                        font-size: 14px;
                    }
                    /* Animations */
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes scaleUp {
                        from { transform: scale(0); }
                        to { transform: scale(1); }
                    }
                    @keyframes drawCheck {
                        from { width: 0; height: 0; }
                        to { width: 40px; height: 20px; }
                    }
                    /* Responsive Styles */
                    @media screen and (max-width: 600px) {
                        .container {
                            margin: 50px auto;
                            padding: 10px;
                        }
                        h1 {
                            font-size: 20px;
                        }
                        p {
                            font-size: 14px;
                        }
                        .btn {
                            font-size: 14px;
                            padding: 8px 16px;
                        }
                        .order-summary {
                            padding: 10px;
                        }
                        .order-summary h2 {
                            font-size: 18px;
                        }
                        .order-summary th, .order-summary td {
                            font-size: 14px;
                            padding: 6px;
                        }
                        .small-btn {
                            padding: 3px 8px;
                            font-size: 12px;
                        }
                    }
                </style>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    function copyVoucherCode() {
                        var voucherCode = document.getElementById("voucherCode").innerText;
                        navigator.clipboard.writeText(voucherCode).then(function() {
                            Swal.fire({
                                icon: "success",
                                title: "Voucher code copied!",
                                text: "The voucher code has been copied to the clipboard.",
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }, function(err) {
                            console.error("Could not copy text: ", err);
                        });
                    }
                </script>
            </head>
            <body>
                <div class="container">
                    <div class="checkmark"></div>
                    <h1>Success!</h1>
                    <p>Your payment has been successfully processed.</p>
                    <div class="order-summary">
                        <h2>Package Summary</h2>
                        <table>
                            <tr>
                                <th>Item</th>
                                <th>Details</th>
                            </tr>';

        foreach ($orderSummary as $item => $details) {
            $html .= '<tr>
                                <td>' . $item . '</td>
                                <td>' . $details . '</td>
                            </tr>';
        }

        if ($voucherCode !== '**********') {
            $html .= '<tr>
                        <td>' . Lang::T("Voucher Code") . '</td>
                        <td>
                            <span id="voucherCode">' . $voucherCode . '</span>
    &nbsp;&nbsp;&nbsp;<button onclick="copyVoucherCode()" class="btn small-btn">Copy</button>
                        </td>
                    </tr>';
        }

        $html .= '</table>
                    </div>
                    <div style="text-align: center;">
                            <div class="countdown-timer" id="countdown">Connecting you in 5 seconds...</div>
                        </div>
                    <a href="index.php?_route=plugin/hotspot_login&username=' . $voucherCode . '&password=' . $voucherCode . '&ip=' . $ipAddress . '&mac=' . $macAddress . '&router=' . $router . '" class="btn">Connect Now</a>

                </div>
                
                <script>
                        // Countdown timer
            var seconds = 5;
            function countdown() {
                var timer = setInterval(function () {
                    seconds--;
                    document.getElementById("countdown").innerHTML = "Connecting you in " + seconds + " seconds...";
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = "index.php?_route=plugin/hotspot_login&username=' . $voucherCode . '&password=' . $voucherCode . '&ip=' . $ipAddress . '&mac=' . $macAddress . '&router=' . $router . '"; // Replace with your desired URL
                    }
                }, 1000);
            }
            countdown();

        </script>
            </body>
            </html>';

        // Set the appropriate headers
        header('Content-Type: text/html');
        header('HTTP/1.1 200 OK');

        // Output the HTML page
        echo $html;
        exit();
    }


    function hotspot_block_mac()
    {
        _admin();
        if (isset($_GET['block']) || isset($_GET['unblock'])) {
            $mac_address = _get('mac');
            if (empty($mac_address)) {
                r2(U . "plugin/hotspot_overview", 'e', Lang::T("Error: Mac Address not found."));
                return;
            }

            $users = ORM::for_table('tbl_hotspot_payments')->where('mac_address', $mac_address)->find_many();

            if ($users) {
                try {
                    foreach ($users as $user) {
                        if (isset($_GET['block'])) {
                            $user->mac_status = 'Banned';
                            $successMessage = Lang::T("Device with Mac Address " . $mac_address . " has been Banned Successfully");
                        } elseif (isset($_GET['unblock'])) {
                            $user->mac_status = 'Active';
                            $successMessage = Lang::T("Device with Mac Address " . $mac_address . " has been Unblocked Successfully");
                        }
                        $user->save();
                    }
                    r2(U . "plugin/hotspot_overview", 's', Lang::T($successMessage));
                    exit();
                } catch (Exception $e) {
                    r2(U . "plugin/hotspot_overview", 'e', Lang::T("Error: " . $e->getMessage()));
                }
            } else {
                r2(U . "plugin/hotspot_overview", 'e', Lang::T("Error: Mac Address not found."));
            }
        }
    }

    function hotspot_delete_transactions()
    {
        _admin();
        $admin = Admin::_info();

        if (!hotspot_can_delete_transaction_history($admin)) {
            echo "FORBIDDEN";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "INVALID";
            exit;
        }

        try {
            if (!empty($_POST['all'])) {
                ORM::for_table('tbl_hotspot_payments')->delete_many();
            } else {
                $ids = $_POST['ids'] ?? [];

                if (empty($ids)) {
                    echo "NO DATA";
                    exit;
                }

                if (!is_array($ids)) {
                    $ids = explode(',', $ids);
                }

                foreach ($ids as $id) {
                    $row = ORM::for_table('tbl_hotspot_payments')
                        ->where('id', $id)
                        ->find_one();

                    if ($row) {
                        $row->delete();
                    }
                }
            }

            hotspot_invalidate_overview_cache();

            echo "OK";

        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage();
        }

        exit;
    }

    function hotspot_generate_voucher_code()
    {
        global $config;
        if ($config['hotspot_voucher_type'] === 'random') {
            return strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
        } elseif ($config['hotspot_voucher_type'] === 'number') {
            return rand(1000000000, 9999999999);
        } else {
        }
    }

    function hotspot_config()
    {
        global $ui, $admin, $config, $_app_stage;

        $ui->assign('_title', Lang::T("Hotspot System General Settings"));
        $ui->assign('_system_menu', 'settings');

        $admin = Admin::_info();
        $ui->assign('_admin', $admin);
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (!empty(_get('testWa'))) {
            if ($_app_stage == 'Demo') {
                r2(U . 'plugin/hotspot_config#collapseHotspotWa', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            $result = Message::sendWhatsapp(_get('testWa'), Lang::T('wifizones Test Whatsapp'));
            r2(U . 'plugin/hotspot_config#collapseHotspotWa', 's', Lang::T('Test Whatsapp has been send') . '<br>Result: ' . $result);
        }
        if (!empty(_get('testSms'))) {
            if ($_app_stage == 'Demo') {
                r2(U . 'plugin/hotspot_config#collapseHotspotSms', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            $result = Message::sendSMS(_get('testSms'), Lang::T('wifizones Test SMS'));
            r2(U . 'plugin/hotspot_config#collapseHotspotSms', 's', Lang::T('Test SMS has been send') . '<br>Result: ' . $result);
        }
        if (!empty(_get('testEmail'))) {
            if ($_app_stage == 'Demo') {
                r2(U . 'plugin/hotspot_config#collapseHotspotEmail', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            Message::sendEmail(_get('testEmail'), Lang::T('wifizones Test Email'), Lang::T('wifizones Test Email Body'));
            r2(U . 'plugin/hotspot_config#collapseHotspotEmail', 's', Lang::T('Test Email has been send'));
        }
        if (!empty(_get('testTg'))) {
            if ($_app_stage == 'Demo') {
                r2(U . 'plugin/hotspot_config#collapseHotspotTg', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            $result = Message::sendTelegram(Lang::T('wifizones Test Telegram'));
            r2(
                U . 'plugin/hotspot_config#collapseHotspotTg',
                Message::isTelegramSuccess($result) ? 's' : 'e',
                Message::isTelegramSuccess($result) ? 'Succès' : 'Échec'
            );
        }

        $ui->assign('notify_tg_ok', !empty($config['telegram_bot']) && !empty($config['telegram_target_id']));
        $ui->assign('notify_sms_ok', !empty($config['sms_url']));
        $ui->assign('notify_wa_ok', !empty($config['wa_url']));
        $ui->assign('notify_email_ok', !empty($config['smtp_host']) || !empty($config['mail_from']));
        $ui->assign('settings_app_url', getUrl('settings/app'));

        $UPLOAD_PATH = 'system' . DIRECTORY_SEPARATOR . 'uploads';
        $notifications_file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "hotspot_message.json";

        if (!file_exists($notifications_file)) {
            $default_content = [
                "hotspot_message_content" => "Dear Customer,\r\nYour  [[package]]  subscription has been activated.\r\nVoucher Code is:  [[login_code]]\r\nAccount expires on: [[expiry]]\r\n\r\n[[company]]",
                "voucher_send" => "Dear Customer,\r\nHere is your Voucher Details:\r\nData Limit:  [[data]]\r\nVoucher Code is:  [[code]]\r\nDuration: [[validity]]\r\n\r\n[[company]]",
                "voucher_template" => "<style type=\"text/css\">
                    .voucher-container {
                        width: 250px;
                        height: 70px;
                        border: 1px solid #000;
                        font-family: Arial, sans-serif;
                        font-size: 10px;
                        margin-bottom: 5px;
                        display: flex;
                        background-color: #f7f7f7;
                    }
                    .price-bar {
                        width: 15px;
                        background-color: #ff8c00;
                        color: white;
                        text-align: center;
                        font-weight: bold;
                        padding: 5px 2px;
                        writing-mode: vertical-rl;
                        transform: rotate(180deg);
                    }
                    .details {
                        flex: 1;
                        padding: 5px;
                    }
                    .details .code {
                        font-size: 14px;
                        font-weight: bold;
                        text-align: center;
                        margin-bottom: 2px;
                    }
                    .details .info {
                        font-size: 9px;
                        margin-bottom: 2px;
                    }
                    .qrcode {
                        width: 50px;
                        height: 50px;
                        margin: auto;
                        padding: 5px;
                    }
                    .qrcode img {
                        width: 100%;
                        height: auto;
                    }
                </style>
                <div class=\"voucher-container\">
                    <div class=\"price-bar\">[[currency]][[plan_price]]</div>
                    <div class=\"details\">
                        <div class=\"code\">[[code]]</div>
                        <div class=\"info\">Data Limit: [[data]] Duration: [[validity]]</div>
                        <div class=\"info\">Login: [[url]] </div><br>
                        <div class=\"info\">Thank you for choosing our service</div>
                    </div>
                    <div class=\"qrcode\">[[qrcode]]</div>
                </div>",
            ];

            if (is_writable($UPLOAD_PATH)) {
                $result = file_put_contents($notifications_file, json_encode($default_content, JSON_PRETTY_PRINT));
                if ($result === false) {
                    _log('[' . $admin['username'] . ']: ' . Lang::T('Failed to write JSON to file'), $admin['user_type']);
                    r2(U . "plugin/hotspot_overview", 'e', Lang::T('Failed to save default notifications settings due to file write error'));
                } else {
                    _log('[' . $admin['username'] . ']: ' . Lang::T('Default notifications file created successfully'), $admin['user_type']);
                }
            } else {
                _log('[' . $admin['username'] . ']: ' . Lang::T('Failed to write default notifications file due to file permissions'), $admin['user_type']);
                r2(U . "plugin/hotspot_overview", 'e', Lang::T('Failed to save default notifications settings due to file permissions'));
            }
        }


        if (_post('save') == 'save') {
            $hotspot_voucher_mode = isset($_POST['hotspot_voucher_mode']) ? 1 : 0;
            $hotspot_voucher_type = isset($_POST['hotspot_voucher_type']) ? htmlspecialchars($_POST['hotspot_voucher_type']) : '';
            $hotspot_payment_type = isset($_POST['hotspot_payment_type']) ? htmlspecialchars($_POST['hotspot_payment_type']) : '';
            $hotspot_message = isset($_POST['hotspot_message']) ? 1 : 0;
            $hotspot_message_via = isset($_POST['hotspot_message_via']) ? htmlspecialchars($_POST['hotspot_message_via']) : '';
            $hotspot_url = isset($_POST['hotspot_url']) ? htmlspecialchars($_POST['hotspot_url']) : '';
            $voucher_template = $_POST['voucher_template'] ?? '';
            $hotspot_cev = isset($_POST['hotspot_cev']) ? 1 : 0;
            $hotspot_cev_batch = $_POST['hotspot_cev_batch'] ?? 10;

            $settings = [
                'hotspot_voucher_mode' => $hotspot_voucher_mode,
                'hotspot_voucher_type' => $hotspot_voucher_type,
                'hotspot_payment_type' => $hotspot_payment_type,
                'hotspot_message' => $hotspot_message,
                'hotspot_message_via' => $hotspot_message_via,
                'hotspot_url' => $hotspot_url,
                'hotspot_cev' => $hotspot_cev,
                'hotspot_cev_batch' => $hotspot_cev_batch,
            ];

            // Update or insert settings in the database
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

            // Save voucher template and hotspot message content
            if (is_writable($UPLOAD_PATH)) {
                $content_to_save = [
                    "hotspot_message_content" => htmlspecialchars($_POST['hotspot_message_content']),
                    "voucher_send" => htmlspecialchars($_POST['voucher_send']),
                    "voucher_template" => $voucher_template,
                ];
                file_put_contents($notifications_file, json_encode($content_to_save, JSON_PRETTY_PRINT));
            } else {
                _log('[' . $admin['username'] . ']: ' . Lang::T('Failed to write notifications file'), $admin['user_type']);
                _alert(Lang::T('Failed to save notifications settings due to file permissions'), 'danger', "plugin/hotspot_config");
            }

            _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type']);
            r2(U . 'plugin/hotspot_config', 's', Lang::T('Settings Saved Successfully'));
        }

        if (file_exists($notifications_file)) {
            $json_data = file_get_contents($notifications_file);
            if ($json_data !== false) {
                $json_data_array = json_decode($json_data, true);
                $ui->assign('_json', $json_data_array);
            } else {
                _log('[' . $admin['username'] . ']: ' . Lang::T('Failed to read notifications file'), $admin['user_type']);
            }
        }

        $paymentGateway = hotspot_getAvailablePaymentGateways();
        if (!$paymentGateway) {
            $ui->assign('message', '<em>' . Lang::T("Payment Gateway is missing, you can purchase payment gateway plugin from ") . ' <a href="https://shop.stcncrm.xyz">shop.stcncrm.xyz</a>' . ' ' . ' ' . Lang::T("or Contact ") . ' ' . '<a href="https://t.me/smbilling">Mahmud</a>' . ' ' . Lang::T("for more informations") . '</em>');
        }

        $ui->assign('_c', $config);
        $ui->assign('companyName', $config['CompanyName']);
        $ui->display('[plugin]hotspot_config.tpl');
    }


    function hotspot_resellers()
    {
        global $ui;

        _admin();

        // ================= ADD RESELLER =================
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = trim(_post('username'));
            $password = _post('password');
            $fullname = _post('fullname');
            $phone    = _post('phone');
            $balance  = floatval(_post('balance'));

            if ($username == '' || $password == '') {
                r2(U . "plugin/hotspot_resellers", 'e', "Username & Password required");
                exit;
            }

            // duplicate check
            $exists = ORM::for_table('tbl_hotspot_resellers')
                ->where('username', $username)
                ->find_one();

            if ($exists) {
                r2(U . "plugin/hotspot_resellers", 'e', "Username already exists");
                exit;
            }

            // insert reseller
            $r = ORM::for_table('tbl_hotspot_resellers')->create();

            $r->username = $username;
            $r->password = password_hash($password, PASSWORD_BCRYPT);
            $r->fullname = $fullname;
            $r->phone    = $phone;
            $r->balance  = $balance;
            $r->status   = 'active';
            $r->created_at = date('Y-m-d H:i:s');

            $r->save();
            
            $db = ORM::get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
        'Reseller Create',
        "New reseller created: {$username}",
        $_SESSION['aid'] ?? 0,
        $ip
    ]);

            r2(U . "plugin/hotspot_resellers", 's', "Reseller Added Successfully");
            exit;
        }

        // ================= RESELLER ACTIONS =================
        if (isset($_GET['res_action'])) {
            $id = (int) _get('id');
            $action = _get('res_action');
            $r = ORM::for_table('tbl_hotspot_resellers')->find_one($id);

            if ($r) {
                switch ($action) {
                    case 'suspend':
                        $r->status = 'suspended';
                        $r->save();
                        break;
                    case 'active':
                        $r->status = 'active';
                        $r->save();
                        break;
                    case 'delete':
                        $r->delete();
                        break;
                }

                $db = ORM::get_db();
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
                    'Reseller Action',
                    "Action: {$action} on reseller ID {$id}",
                    $_SESSION['aid'] ?? 0,
                    $ip
                ]);
            }

            r2(U . "plugin/hotspot_resellers", 's', "Action Completed");
        }

        // ================= SEARCH =================
        $search = _get('search');

        $q = ORM::for_table('tbl_hotspot_resellers');

        if ($search != '') {
            $q->where_raw(
                "username LIKE ? OR fullname LIKE ? OR phone LIKE ?",
                ["%$search%", "%$search%", "%$search%"]
            );
        }

        $resellers = $q->find_array();

        // ================= STATS =================
        foreach ($resellers as &$r) {

            $reseller_id = $r['id'];

    // TOKENS
    $r['tokens'] = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->count();

    $r['tokens_used'] = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'used')
        ->count();

    $r['tokens_active'] = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'active')
        ->count();

    $r['tokens_unused'] = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'none')
        ->count();


    // VOUCHERS (SAFE ONLY is_used)
    $r['vouchers'] = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->count();

    $r['vouchers_used'] = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->where('is_used', 1)
        ->count();

    $r['vouchers_unused'] = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->where('is_used', 0)
        ->count();
        }

    // Currency Code
    $currency_code = ORM::for_table('tbl_appconfig')
        ->where('setting', 'currency_code')
        ->find_one();


        // ================= ASSIGN =================
        $ui->assign('_title', 'Hotspot Resellers');

        $ui->assign('resellers', $resellers);

        $ui->assign('totalResellers', ORM::for_table('tbl_hotspot_resellers')->count());
        $ui->assign('totalTokens', ORM::for_table('tbl_hotspot_tokens')->count());
        $ui->assign('totalVouchers', ORM::for_table('tbl_hotspot_vouchers')->count());
        $ui->assign('totalBalance', ORM::for_table('tbl_hotspot_resellers')->sum('balance'));

        $ui->assign('totalActive', ORM::for_table('tbl_hotspot_resellers')->where('status', 'active')->count());
        $ui->assign('totalSuspended', ORM::for_table('tbl_hotspot_resellers')->where('status', 'suspended')->count());
        $ui->assign(
        'currency_code',
        $currency_code ? $currency_code->value : '৳'
    );

        $ui->display('[plugin]hotspot_resellers.tpl');
    }

    function hotspot_resellers_topup_reports()
    {
        global $ui;

        _admin();
        
        $currency = ORM::for_table('tbl_appconfig')
        ->where('setting', 'currency_code')
        ->find_one();

    $currency_code = $currency ? $currency->value : 'BDT';

    $ui->assign('currency_code', $currency_code);

        $search = _get('search');

        // ================= MAIN QUERY =================
        $q = ORM::for_table('tbl_hotspot_resellers_topups')
            ->table_alias('t')
            ->left_outer_join('tbl_hotspot_resellers', ['t.reseller_id', '=', 'r.id'], 'r');

        if ($search != '') {
            $q->where_raw(
                "(r.username LIKE ? OR r.fullname LIKE ? OR r.phone LIKE ?)",
                ["%$search%", "%$search%", "%$search%"]
            );
        }

        $reports = $q
            ->select('t.*')
            ->select('r.username')
            ->select('r.fullname')
            ->select('r.phone')
            ->order_by_desc('t.id')
            ->find_array();

        // ================= FIX LOGIC (IMPORTANT) =================
        foreach ($reports as &$r) {

        $old = (float) $r['old_balance'];
        $new = (float) $r['new_balance'];

        $diff = $new - $old;

        $r['added'] = ($diff >= 0)
            ? '+ ' . number_format($diff, 2)
            : '- ' . number_format(abs($diff), 2);

        $r['total_balance'] = number_format($r['total_balance'], 2);
    }

    unset($r);

        // ================= EXPORT PDF =================
        if (_get('export') == 'pdf') {

            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=topup_report.html");

            echo "<h3>Topup Report</h3>";
            echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Reseller</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Old</th>
                <th>Added</th>
                <th>Total</th>
            </tr>";

            foreach ($reports as $r) {
                echo "<tr>
                    <td>{$r['username']}</td>
                    <td>{$r['fullname']}</td>
                    <td>{$r['phone']}</td>
                    <td>{$r['old_balance']}</td>
                    <td>{$r['added']}</td>
                    <td>{$r['total_balance']}</td>
                </tr>";
            }

            echo "</table>";
            exit;
        }

        // ================= EXPORT EXCEL =================
        if (_get('export') == 'excel') {

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="topup_report.csv"');

            $output = fopen("php://output", "w");

            fputcsv($output, ['Reseller','Name','Phone','Old Balance','Added','Total']);

            foreach ($reports as $r) {
                fputcsv($output, [
                    $r['username'],
                    $r['fullname'],
                    $r['phone'],
                    $r['old_balance'],
                    $r['added'],
                    $r['total_balance']
                ]);
            }

            fclose($output);
            exit;
        }

        // ================= VIEW =================
        $ui->assign('search', $search);
        $ui->assign('reports', $reports);
        $ui->display('[plugin]hotspot_resellers_topup_reports.tpl');
    }

    function hotspot_reseller_view()
    {
        global $ui;

        _admin();

        $id = _get('id');

        $reseller = ORM::for_table('tbl_hotspot_resellers')->find_one($id);

        if (!$reseller) {
            r2(U . "plugin/hotspot_resellers", 'e', "Reseller not found");
            return;
        }

        $ui->assign('r', $reseller);
        $ui->display('[plugin]hotspot_reseller_view.tpl');
    }

    function hotspot_reseller_edit()
    {
        global $ui;

        _admin();

        $id = _get('id');

        $r = ORM::for_table('tbl_hotspot_resellers')->find_one($id);

        if (!$r) {
            r2(U . "plugin/hotspot_resellers", 'e', "Reseller not found");
            return;
        }

        // UPDATE FORM SUBMIT
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $admin = Admin::_info();

        $r->username = _post('username');
        // password update (only if filled)
    $password = _post('password');

    if (!empty($password)) {
        $r->password = password_hash($password, PASSWORD_BCRYPT);
    }
        $r->fullname = _post('fullname');
        $r->phone = _post('phone');

        // old balance
    $old_balance = floatval($r->balance);

    // 🔥 IMPORTANT: STRING হিসেবে নাও (যাতে + / - থাকে)
    $input_balance = trim(_post('balance'));

    // যদি empty হয়
    if ($input_balance == '') {
        r2(U . "plugin/hotspot_resellers", 'e', "No balance change given");
        return;
    }

    // 🔥 TOTAL CALCULATION
    $total_balance = $old_balance + (float)$input_balance;

    // negative prevent
    if ($total_balance < 0) {
        r2(U . "plugin/hotspot_resellers", 'e', "Insufficient balance");
        return;
    }

    // ================= SAVE MAIN BALANCE =================
    $r->balance = $total_balance;
    $r->save();

    // ================= LOG =================
    $db = ORM::get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
        'Balance Update',
        "Reseller ID {$r->id}: {$old_balance} → {$total_balance}",
        $admin['id'] ?? ($_SESSION['aid'] ?? 0),
        $ip
    ]);

    // ================= SAVE LOG (DB FIX) =================
    ORM::for_table('tbl_hotspot_resellers_topups')->create()->set([
        'reseller_id'   => $r->id,
        'old_balance'   => $old_balance,

        // 🔥 EXACT INPUT (UI same)
        'new_balance'   => $input_balance,

        // 🔥 FINAL TOTAL
        'total_balance' => $total_balance,

        'added_by'      => $admin['username'] ?? 'admin',
        'created_at'    => date('Y-m-d H:i:s')
    ])->save();

        r2(U . "plugin/hotspot_resellers", 's', "Balance Updated Successfully");
        return;
    }

        $ui->assign('r', $r);
        $ui->display('[plugin]hotspot_reseller_edit.tpl');
    }

    function hotspot_resellers_login()
    {
        global $ui;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $username = _post('username');
            $password = _post('password');

            $reseller = ORM::for_table('tbl_hotspot_resellers')
                ->where('username', $username)
                ->find_one();

            if (!$reseller) {
                r2(U . "plugin/hotspot_resellers_login", 'e', "Invalid username");
                return;
            }

            if (!password_verify($password, $reseller->password)) {
                r2(U . "plugin/hotspot_resellers_login", 'e', "Invalid password");
                return;
            }

            if ($reseller->status != 'active') {
                r2(U . "plugin/hotspot_resellers_login", 'e', "Account suspended");
                return;
            }

            // ================= SESSION SET =================
            $_SESSION['reseller_login'] = true;
            $_SESSION['reseller_id'] = $reseller->id;
            $_SESSION['reseller_name'] = $reseller->fullname;
            
            // ================= LOG =================
    $db = ORM::get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
        'Reseller Login',
        "Reseller logged in: {$username}",
        $reseller->id,
        $ip
    ]);

            r2(U . "plugin/hotspot_resellers_dashboard", 's', "Login Success");
            return;
        }

        $ui->display('[plugin]hotspot_resellers_login.tpl');
    }

    function hotspot_resellers_auth()
    {
        if (!isset($_SESSION['reseller_login']) || $_SESSION['reseller_login'] !== true) {
            r2(U . "plugin/hotspot_resellers_login", 'e', "Login required");
            exit;
        }
    }

    function hotspot_resellers_logout()
    {
        unset($_SESSION['reseller_login']);
        unset($_SESSION['reseller_id']);
        unset($_SESSION['reseller_name']);

        session_destroy();

        r2(U . "plugin/hotspot_resellers_login", 's', "Logged out");
    }

    function hotspot_resellers_dashboard()
    {
        global $ui;

        session_start();
        
        

        if (!isset($_SESSION['reseller_id'])) {
            r2(U . "plugin/hotspot_resellers_login", 'e', "Please login first");
            exit;
        }

        $reseller_id = $_SESSION['reseller_id'];
        
    $currency = ORM::for_table('tbl_appconfig')
        ->where('setting', 'currency_code')
        ->find_one();

    $currency_code = $currency ? $currency->value : 'BDT';

    $today = date('Y-m-d');
    $thisMonth = date('Y-m');

    // TOKEN SELL (USED ONLY + RESELLER)
    $today_token_sell = ORM::for_table('tbl_hotspot_tokens')
        ->where('status', 'used')
        ->where('reseller_id', $admin['id'])
        ->where_raw("DATE(created_at) = CURDATE()")
        ->sum('value');

    // VOUCHER SELL (USED ONLY + RESELLER)
    $today_voucher_sell = ORM::for_table('tbl_hotspot_vouchers')
        ->where('is_used', 1)
        ->where('reseller_id', $admin['id'])
        ->where_raw("DATE(created_at) = CURDATE()")
        ->sum('price');

    // TOTAL TODAY
    $today_total_sell = $today_token_sell + $today_voucher_sell;


    // MONTHLY SELL
    $monthly_token_sell = ORM::for_table('tbl_hotspot_tokens')
        ->where('status', 'used')
        ->where('reseller_id', $admin['id'])
        ->where_raw("MONTH(created_at) = MONTH(CURDATE())")
        ->sum('value');

    $monthly_voucher_sell = ORM::for_table('tbl_hotspot_vouchers')
        ->where('is_used', 1)
        ->where('reseller_id', $admin['id'])
        ->where_raw("MONTH(created_at) = MONTH(CURDATE())")
        ->sum('price');

    $monthly_total_sell = $monthly_token_sell + $monthly_voucher_sell;


    // SMARTY ASSIGN
    $ui->assign('today_total_sell', $today_total_sell);
    $ui->assign('monthly_total_sell', $monthly_total_sell);

        // ================= RESLLER INFO =================
        $resellers = ORM::for_table('tbl_hotspot_resellers')
            ->find_one($reseller_id);
            
            // ================= BALANCE =================
        $balance = $resellers['balance'] ?? 0;

        if (!$resellers) {
            session_destroy();
            r2(U . "plugin/hotspot_resellers_login", 'e', "Session expired");
            exit;
        }

        // ================= STATS =================
        $totalVoucher = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->count();
            
            // ================= VOUCHER STATUS COUNT (FIXED) =================
    $usedVouchers = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->where('is_used', 1)
        ->count();

    $unusedVouchers = ORM::for_table('tbl_hotspot_vouchers')
        ->where('generated_by', $reseller_id)
        ->where('is_used', 0)
        ->count();
        
            // TOKEN STATUS COUNT
    $totalTokens = ORM::for_table('tbl_hotspot_tokens')
            ->where('generated_by', $reseller_id)
            ->count();
            
            
    $activeTokens = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'active')
        ->count();

    $usedTokens = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'used')
        ->count();

    $unusedTokens = ORM::for_table('tbl_hotspot_tokens')
        ->where('generated_by', $reseller_id)
        ->where('status', 'none')
        ->count();

        // ================= FILTER =================
    $tsearch   = _get('tsearch');
    $tplan_id  = _get('tplan_id');
    $tstatus   = _get('tstatus');

    // ================= TOKEN LIST (FILTERED) =================
    $tq = ORM::for_table('tbl_hotspot_tokens')
        ->table_alias('t')
        ->select('t.*')
        ->select('p.name_plan')
        ->left_outer_join('tbl_plans', ['t.plan_id', '=', 'p.id'], 'p')
        ->where('t.generated_by', $reseller_id);

    if ($tsearch != '') {
        $tq->where_raw("t.token_number LIKE ?", ["%$tsearch%"]);
    }

    if ($tplan_id != '') {
        $tq->where('t.plan_id', $tplan_id);
    }

    if ($tstatus != '') {
        $tq->where('t.status', $tstatus);
    }

    $tokens = $tq->order_by_desc('t.id')->find_array();

    // ================= TOTAL COMMISSION =================
    $totalCommission = ORM::for_table('tbl_hotspot_vouchers')
        ->where('reseller_id', $reseller_id)
        ->sum('price');

    // ================= TODAY COMMISSION =================
    $todayCommission = ORM::for_table('tbl_hotspot_vouchers')
        ->where('reseller_id', $reseller_id)
        ->where_raw("DATE(created_at) = '$today'")
        ->sum('price');

        // ================= VOUCHER FILTER =================
    $vsearch = _get('vsearch');
    $vplan_id = _get('vplan_id');
    $vstatus  = _get('vstatus');

    // ================= VOUCHER LIST =================
    $vq = ORM::for_table('tbl_hotspot_vouchers')
        ->table_alias('v')
        ->select('v.*')
        ->select('p.name_plan')
        ->left_outer_join('tbl_plans', ['v.plan_id', '=', 'p.id'], 'p')
        ->where('v.generated_by', $reseller_id);

    if ($vsearch != '') {
        $vq->where_raw("v.code LIKE ?", ["%$vsearch%"]);
    }

    if ($vplan_id != '') {
        $vq->where('v.plan_id', $vplan_id);
    }

    if ($vstatus != '') {

        if ($vstatus == 'used') {
            $vq->where('v.is_used', 1);
        }

        if ($vstatus == 'unused') {
            $vq->where('v.is_used', 0);
        }
    }

    $vouchers = $vq->order_by_desc('v.id')->limit(10)->find_array();
    $ui->assign('v_search', $v_search);
    $ui->assign('v_plan', $v_plan);

        // ================= ROUTERS =================
        $routers = ORM::for_table('tbl_routers')->find_many();

    // ================= PLAN LIST =================
    $plans = ORM::for_table('tbl_plans')
        ->where('type', 'Hotspot')
        ->find_array();

    $ui->assign('plans', $plans);
    $ui->assign('search', $search);
    $ui->assign('plan_filter', $plan_filter);

        // ================= ASSIGN =================
        $ui->assign('_title', 'Reseller Dashboard');
        $ui->assign('resellers', $resellers);
        $ui->assign('totalVoucher', $totalVoucher);
        $ui->assign('totalTokens', $totalTokens);
        $ui->assign('tokens', $tokens);
        $ui->assign('vouchers', $vouchers);
        $ui->assign('routers', $routers);
        $ui->assign('balance', $balance);
        $ui->assign('currency_code', $currency_code);
    $ui->assign('todayCommission', $todayCommission);
    $ui->assign('monthlyCommission', $monthlyCommission);
    $ui->assign('activeTokens', $activeTokens);
    $ui->assign('usedTokens', $usedTokens);
    $ui->assign('unusedTokens', $unusedTokens);

    $ui->assign('usedVouchers', $usedVouchers);
    $ui->assign('unusedVouchers', $unusedVouchers);

        $ui->display('[plugin]hotspot_resellers_dashboard.tpl');
    }

    function reseller_generate_token()
    {
        session_start();

        if (!isset($_SESSION['reseller_id'])) {
            r2(U . "plugin/hotspot_resellers_login", 'e', "Login required");
            exit;
        }

        $reseller_id = $_SESSION['reseller_id'];

        $plan_id = _post('plan_id');
        $qty = intval(_post('qty'));

        $reseller = ORM::for_table('tbl_hotspot_resellers')->find_one($reseller_id);
        $plan = ORM::for_table('tbl_plans')->find_one($plan_id);

        if (!$reseller || !$plan) {
            r2(U . "plugin/hotspot_resellers_dashboard", 'e', "Invalid request");
            return;
        }

        $total_cost = $plan->price * $qty;

        if ($reseller->balance < $total_cost) {
            r2(U . "plugin/hotspot_resellers_dashboard", 'e', "Insufficient balance");
            return;
        }

        // balance deduct
        $reseller->balance -= $total_cost;

        // commission
        $commission = $total_cost * 0.10;
        $reseller->commission_total += $commission;
        $reseller->total_sold_tokens += $qty;

        $reseller->save();
        
        // ================= LOG =================
    $db = ORM::get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
        'Token Generate',
        "Generated {$qty} tokens by reseller {$reseller_id}",
        $reseller_id,
        $ip
    ]);

        if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $pin_length = intval(_post('pin_length'));
    if ($pin_length < 4) $pin_length = 4;
    if ($pin_length > 20) $pin_length = 20;

    $generated_tokens = [];

    // ================= TOKEN CREATE =================
    for ($i = 0; $i < $qty; $i++) {

        $token = ORM::for_table('tbl_hotspot_tokens')->create();

        // numeric token generate
        $token_number = '';
        for ($j = 0; $j < $pin_length; $j++) {
            $token_number .= rand(0, 9);
        }

        // ensure unique
        while (ORM::for_table('tbl_hotspot_tokens')->where('token_number', $token_number)->find_one()) {
            $token_number = '';
            for ($j = 0; $j < $pin_length; $j++) {
                $token_number .= rand(0, 9);
            }
        }

        // SAVE
        $token->token_number = $token_number;
        $token->serial_number = strtoupper(uniqid("SN"));
        $token->plan_id = $plan_id;
        $token->value = $plan->price;
        $token->generated_by = $reseller_id;
        $token->reseller_id = $reseller_id; // ✅ MUST ADD
        $token->status = 'none'; // unused
        $token->used_count = 0;
        $token->created_at = date('Y-m-d H:i:s');

        $token->save();

        // 🔥 IMPORTANT (store token)
        $generated_tokens[] = $token_number;
    }

        $print_id = rand(100000, 999999);
        foreach ($generated_tokens as $t) {
            ORM::for_table('tbl_print_temp')->create()->set([
                'print_id' => $print_id,
                'token' => $t,
                'created_at' => date('Y-m-d H:i:s'),
            ])->save();
        }

        system_log($reseller_id, 'TOKEN_GENERATED', "Qty: $qty, Plan: $plan_id");

        if (_post('print_now') == 1 && !empty($generated_tokens)) {
            $_SESSION['print_tokens'] = $generated_tokens;
            session_write_close();
            r2(U . "plugin/reseller_print_page", 's', "Opening print page...");
            exit;
        }

        r2(U . "plugin/hotspot_resellers_dashboard", 's', "Token generated successfully");
    }

    function reseller_print_page()
    {
        session_start();

        $tokens = $_SESSION['print_tokens'] ?? [];

        if (!$tokens) {
            echo "No tokens found!";
            exit;
        }

        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Print Tokens</title>
            <style>
                body { font-family: Arial; padding: 20px; }
                .token {
                    font-size: 22px;
                    margin: 10px;
                    padding: 10px;
                    border: 1px dashed #000;
                    display: inline-block;
                }
            </style>
        </head>
        <body>

        <h2>Generated Tokens</h2>';

        foreach ($tokens as $t) {
            echo "<div class='token'>$t</div>";
        }

        echo "<script>
            window.onload = function () {
                window.print();
            }
        </script>

        </body>
        </html>";
    }

    function hotspot_resellers_print_tokens()
    {
        global $ui;

        session_start();

        if (!isset($_SESSION['reseller_id'])) {
            echo "Login required";
            exit;
        }

        $reseller_id = $_SESSION['reseller_id'];

        $search = _get('search');
        $plan_id = _get('plan_id');

        $q = ORM::for_table('tbl_hotspot_tokens')
            ->table_alias('t')
            ->select('t.*')
            ->select('p.name_plan')
            ->left_outer_join('tbl_plans', ['t.plan_id','=','p.id'], 'p')
            ->where('t.generated_by', $reseller_id);

        if ($search != '') {
            $q->where_like('t.token_number', "%$search%");
        }

        if ($plan_id != '') {
            $q->where('t.plan_id', $plan_id);
        }

        $tokens = $q->order_by_desc('t.id')->find_array();

        $ui->assign('tokens', $tokens);
        $ui->display('[plugin]hotspot_resellers_print_tokens.tpl');
    }

    function hotspot_resellers_print_vouchers()
    {
        global $ui;

        session_start();

        if (!isset($_SESSION['reseller_id'])) {
            echo "Login required";
            exit;
        }

        $reseller_id = $_SESSION['reseller_id'];

        $search = _get('v_search');
        $plan_id = _get('v_plan');

        $q = ORM::for_table('tbl_hotspot_vouchers')
            ->table_alias('v')
            ->select('v.*')
            ->select('p.name_plan')
            ->left_outer_join('tbl_plans', ['v.plan_id','=','p.id'], 'p')
            ->where('v.reseller_id', $reseller_id);

        if ($search != '') {
            $q->where_like('v.code', "%$search%");
        }

        if ($plan_id != '') {
            $q->where('v.plan_id', $plan_id);
        }

        $vouchers = $q->order_by_desc('v.id')->find_array();

        $ui->assign('vouchers', $vouchers);
        $ui->display('[plugin]hotspot_resellers_print_vouchers.tpl');
    }

    function _auth_reseller()
    {
        if (!isset($_SESSION['reseller_id'])) {
            r2(U . "resellers-login", 'e', "Login required");
            exit;
        }
    }

    function reseller_generate_voucher()
    {
        global $db, $config;

        session_start();

        if (!isset($_SESSION['reseller_id'])) {
            r2(U . "plugin/hotspot_resellers_login", 'e', "Session expired");
            exit;
        }

        $reseller_id = $_SESSION['reseller_id'];

        $router   = _post('router');
        $plan_id  = _post('plan_id');
        $qty      = intval(_post('qty'));
        $pin_length = intval(_post('pin_length'));

        if ($qty < 1) $qty = 1;
        if ($pin_length < 4) $pin_length = 6;

        // Plan
        $plan = ORM::for_table('tbl_plans')->find_one($plan_id);
        if (!$plan) {
            r2(U . 'dashboard', 'e', 'Plan not found');
            exit;
        }

        // Reseller
        $reseller = ORM::for_table('tbl_hotspot_resellers')->find_one($reseller_id);
        if (!$reseller) {
            r2(U . 'dashboard', 'e', 'Reseller not found');
            exit;
        }

        // Cost
        $total_cost = $plan->price * $qty;

        if ($reseller->balance < $total_cost) {
            r2(U . 'dashboard', 'e', 'Insufficient balance');
            exit;
        }

        // 🔥 LOOP START
        for ($i = 1; $i <= $qty; $i++) {

            // PIN GENERATE
            $code = '';
            for ($x = 0; $x < $pin_length; $x++) {
                $code .= rand(0, 9);
            }

            // DB SAVE
            $v = ORM::for_table('tbl_hotspot_vouchers')->create();
            $v->server       = $router;
            $v->plan_id      = $plan_id;
            $v->code         = $code;
            $v->price        = $plan->price;
            $v->is_used      = 0;
            $v->generated_by = $reseller_id;   // 🔥 IMPORTANT TRACK
            $v->is_admin     = 0;              // 🔥 reseller flag
            $v->mac_lock     = 0;
            $v->created_at   = date('Y-m-d H:i:s');
            $v->save();

            // 🔥 MICROTIK SYNC (IMPORTANT)
            hotspot_resellers_push_to_mikrotik($router, $code, $plan);
        }

        // BALANCE UPDATE
        $reseller->balance = $reseller->balance - $total_cost;
        $reseller->save();
        
        // ================= LOG =================
    $db = ORM::get_db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db->prepare("
        INSERT INTO tbl_logs (date, type, description, userid, ip)
        VALUES (NOW(), ?, ?, ?, ?)
    ")->execute([
        'Voucher Generate',
        "Generated {$qty} vouchers (Plan {$plan_id})",
        $reseller_id,
        $ip
    ]);

        // LOG (optional but useful)
        _log("Reseller [$reseller_id] generated $qty vouchers on router [$router]");

        r2(U . "plugin/hotspot_resellers_dashboard", 's', 'Voucher generated successfully');
    }

    function hotspot_resellers_push_to_mikrotik($router_name, $username, $plan)
    {
        $router = ORM::for_table('tbl_routers')
            ->where('name', $router_name)
            ->find_one();

        if (!$router) return;

        try {
            $ipport = explode(':', $router['ip_address']);
            $ip   = $ipport[0];
            $port = $ipport[1] ?? 8728;

            $client = new RouterOS\Client(
                $ip,
                $router['username'],
                $router['password'],
                $port
            );

            $request = new RouterOS\Request('/ip/hotspot/user/add');
            $request->setArgument('name', $username);
            $request->setArgument('password', $username);
            $request->setArgument('profile', $plan->name_plan);

            $client->sendSync($request);

        } catch (Exception $e) {
            _log("Mikrotik Error: " . $e->getMessage());
        }
    }

    function system_log($user_id, $action, $details = '')
    {
        $action = trim((string) $action);
        $details = trim((string) $details);
        if ($action === '' && $details === '') {
            return;
        }
        if (preg_match('/Qty:\s*,\s*Plan/i', $details) || preg_match('/Old:\s*,\s*New:\s*,\s*Change/i', $details)) {
            return;
        }
        $description = $action;
        if ($details !== '') {
            $description .= ': ' . $details;
        }
        _log($description, 'Hotspot', (int) $user_id);
    }

    function hotspot_logs()
    {
        global $ui;

        _admin();

        $logs = ORM::for_table('tbl_logs')
            ->order_by_desc('id')
            ->find_array();

        $ui->assign('logs', $logs);
        $ui->display('[plugin]hotspot_resellers_logs.tpl');
    }

    function hotspot_login()
    {
        WifiZoneHotspot::handleLogin();
    }

    function hotspot_portal()
    {
        global $config;
        wifizone_hotspot_plugin_cors();
        $routerName = WifiZoneHotspot::resolvePublicRouterName(trim((string) ($_GET['routername'] ?? '')));
        if ($routerName === '') {
            try {
                $sessionAdmin = Admin::_info();
                if ($sessionAdmin) {
                    $sessionAdmin = Impersonate::resolveActingAdmin($sessionAdmin);
                    $sessionAdmin = Impersonate::adminToArray($sessionAdmin);
                    $routerName = WifiZoneHotspot::loadLoginRouterForAdmin($sessionAdmin, $config);
                }
            } catch (Throwable $e) {
            }
        }
        WifiZoneHotspot::renderCaptivePortalHtml('', '', $routerName);
    }

    function hotspot_mikrotik_auth()
    {
        wifizone_hotspot_plugin_cors();
        WifiZoneHotspot::handleMikrotikNativeAuth();
    }

    function hotspot_loginSuccess($message)
    {
        $html = "<!DOCTYPE html>\n        <html lang=\"en\">\n        <head>\n            <meta charset=\"UTF-8\">\n            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n            <title>Connection Success</title>\n            <style>\n                body {\n                    font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;\n                    background-color: #f7f9fc;\n                    color: #333;\n                    margin: 0;\n                    padding: 0;\n                    display: flex;\n                    align-items: center;\n                    justify-content: center;\n                    height: 100vh;\n                }\n                .container {\n                    max-width: 500px;\n                    padding: 30px;\n                    background-color: #ffffff;\n                    border-radius: 10px;\n                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);\n                    text-align: center;\n                    transition: transform 0.3s, box-shadow 0.3s;\n                }\n                .container:hover {\n                    transform: translateY(-5px);\n                    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);\n                }\n                h1 {\n                    font-size: 28px;\n                    color: #2c3e50;\n                    margin-top: 0;\n                }\n                p {\n                    font-size: 16px;\n                    color: #7f8c8d;\n                    margin: 15px 0 0;\n                }\n                .button {\n                    display: inline-block;\n                    margin-top: 20px;\n                    padding: 10px 20px;\n                    font-size: 16px;\n                    color: #ffffff;\n                    background-color: #3498db;\n                    border: none;\n                    border-radius: 5px;\n                    cursor: pointer;\n                    text-decoration: none;\n                    transition: background-color 0.3s;\n                }\n                .button:hover {\n                    background-color: #2980b9;\n                }\n                /* Responsive Styles */\n                @media screen and (max-width: 600px) {\n                    .container {\n                        padding: 20px;\n                    }\n                    h1 {\n                        font-size: 24px;\n                    }\n                    p {\n                        font-size: 14px;\n                    }\n                    .button {\n                        font-size: 14px;\n                        padding: 8px 16px;\n                    }\n                }\n            </style>\n            <script>\n                function openHomepage() {\n                    window.location.href = \"https://www.google.com\"; // Replace with your desired URL\n                }\n            </script>\n        </head>\n        <body>\n            <div class=\"container\">\n                <h1>Connected Successfully</h1>\n                <p>$message</p>\n                <button class=\"button\" onclick=\"openHomepage()\">Go to Google.com</button>\n            </div>\n        </body>\n        </html>";

        // Set the appropriate headers
        header('Content-Type: text/html');
        header('HTTP/1.1 200 Success');

        // Output the HTML page
        echo $html;
        exit();
    }


    function hotspot_update()
    {
        include "config.php";

        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (isset($_GET['db'])) {
            try {
                function hotspot_updateColumnExists($table, $column)
                {
                    try {
                        $columns = ORM::for_table($table)
                            ->raw_query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])
                            ->find_many();
                        return count($columns) > 0;
                    } catch (Exception $e) {
                        _log(Lang::T("Error checking column existence: " . $e->getMessage()));
                        return false;
                    }
                }

                $columns_to_add = [
                    'tbl_hotspot_payments' => [
                        ['column' => 'ip_address', 'after_column' => 'payment_method', 'type' => 'VARCHAR(50)', 'default' => NULL],
                        ['column' => 'is_processed', 'after_column' => 'ip_address', 'type' => 'TINYINT(1)', 'default' => 0]
                    ],
                    'tbl_hotspot_vouchers' => [
                        ['column' => 'validity', 'after_column' => 'price', 'type' => 'VARCHAR(50)', 'default' => NULL],
                        ['column' => 'validity_unit', 'after_column' => 'validity', 'type' => 'VARCHAR(50)', 'default' => NULL],
                        ['column' => 'is_admin', 'after_column' => 'generated_by', 'type' => 'TINYINT(1)', 'default' => 1]
                    ]
                ];

                foreach ($columns_to_add as $table => $columns) {
                    foreach ($columns as $columnData) {
                        $column = $columnData['column'];
                        $after_column = $columnData['after_column'];
                        $type = $columnData['type'];
                        $default = $columnData['default'];

                        if (!hotspot_updateColumnExists($table, $column)) {
                            $defaultSQL = ($default !== NULL) ? "DEFAULT " . (is_numeric($default) ? $default : "'{$default}'") : "DEFAULT NULL";

                            $query = "ALTER TABLE `$table` ADD `$column` $type $defaultSQL AFTER `$after_column`;";

                            ORM::get_db()->exec($query);
                            _log(Lang::T("Added column `$column` to table `$table` with default `$default`"));
                        } else {
                            _log(Lang::T("Column `$column` already exists in `$table`"));
                        }
                    }
                }

                if (class_exists('WifiZoneFfthSchema')) {
                    WifiZoneFfthSchema::install();
                }
                try {
                    ORM::get_db()->exec("ALTER TABLE hotspot_login_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                } catch (Exception $ignored) {
                }

                r2(U . "plugin/hotspot_config", 's', Lang::T("Hotspot database update successful"));
            } catch (Exception $e) {
                _log(Lang::T("Hotspot database update failed: " . $e->getMessage()));
                r2(U . "plugin/hotspot_config", 'e', Lang::T("Hotspot database update failed, check log for error: " . htmlspecialchars($e->getMessage())));
            }
        } else {
            r2(U . "plugin/hotspot_config", 'e', Lang::T("Invalid Parameter"));
        }
    }


    function hotspot_scheduleCredentialsNotify($phone, $package, $login_code, $expiry)
    {
        register_shutdown_function(static function () use ($phone, $package, $login_code, $expiry) {
            try {
                hotspot_sendMessage($phone, $package, $login_code, $expiry);
            } catch (Throwable $e) {
                _log('Hotspot notify deferred: ' . $e->getMessage());
            }
        });
    }

    /**
     * Envoi secondaire des identifiants (SMS puis WhatsApp).
     * La connexion Internet se fait via le portail captive (auto-login), pas via ce message.
     */
    function hotspot_sendMessage($phone, $package, $login_code, $expiry)
    {
        global $config;
        $UPLOAD_PATH = 'system' . DIRECTORY_SEPARATOR . 'uploads';
        $notifications_file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'hotspot_message.json';

        $default_message = "DYRSIA — Forfait [[package]] activé.\r\nIdentifiant : [[login_code]]\r\nExpire : [[expiry]]\r\n(Conservez ce SMS si vous vous reconnectez plus tard.)";

        if (file_exists($notifications_file)) {
            $json_data = file_get_contents($notifications_file);
            if ($json_data !== false) {
                $json_data_array = json_decode($json_data, true);
                $messageContent = $json_data_array['hotspot_message_content'] ?? $default_message;
            } else {
                $messageContent = $default_message;
            }
        } else {
            $messageContent = $default_message;
        }

        if (empty($config['hotspot_message']) || (string) $config['hotspot_message'] === '0') {
            return;
        }

        $message = str_replace('[[company]]', $config['CompanyName'] ?? 'DYRSIA', $messageContent);
        $message = str_replace('[[package]]', $package, $message);
        $message = str_replace('[[expiry]]', $expiry, $message);
        $message = str_replace('[[login_code]]', $login_code, $message);

        $sendVia = strtolower(trim((string) ($config['hotspot_message_via'] ?? 'sms')));
        $enableSms = in_array($sendVia, ['sms', 'both'], true);
        $enableWa = in_array($sendVia, ['wa', 'both'], true);

        // SMS d'abord (tous les numéros Mobile Money), WhatsApp en secours seulement.
        $channels = [];
        if ($enableSms) {
            $channels[] = ['sms', 'Message::sendSMS', [$phone, $message]];
        }
        if ($enableWa) {
            $channels[] = ['wa', 'Message::sendWhatsapp', [$phone, $message]];
        }

        foreach ($channels as [$label, $method, $args]) {
            try {
                call_user_func_array($method, $args);
            } catch (Throwable $e) {
                _log("Hotspot notify $label failed for $phone: " . $e->getMessage());
            }
        }
    }

    function hotspot_voucher()
    {
        global $ui, $admin, $routes, $config;
        $ui->assign('_title', 'Hotspot Voucher Code Generator');
        $ui->assign('_system_menu', '');
        $admin = Admin::_info();
        $ui->assign('_admin', $admin);

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        $paymentGateway = hotspot_getAvailablePaymentGateways();
        if (!$paymentGateway) {
            $ui->assign('message', '<em>' . Lang::T("Payment Gateway is missing, you can purchase payment gateway plugin from ") . ' <a href="https://shop.stcncrm.xyz">shop.stcncrm.xyz</a>' . ' ' . ' ' . Lang::T("or Contact ") . ' ' . '<a href="https://t.me/smbilling">Mahmud</a>' . ' ' . Lang::T("for more informations") . '</em>');
        }
        
        $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $router = $routes['2'] ?? '';

        if (empty($router) && !empty($routers)) {
            $router = $routers[0]['name'];
        }

        $ui->assign('routers', $routers);
        $ui->assign('router', $router);
        
        // ===== GLOBAL MAC LOCK STATUS LOAD =====
    $globalLock = ORM::for_table('tbl_hotspot_vouchers')
        ->select_expr('MAX(mac_lock)', 'lock_status')
        ->find_one();

    $ui->assign('global_mac_lock', $globalLock['lock_status'] ?? 0);

        $dbVouchers = hotspot_getVouchers($router);
        $ui->assign('csrf_token',   hotspot_generateCsrfToken());
        $ui->assign('d', $dbVouchers);
        $ui->assign('_c', $config);
        $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">');
        $ui->assign('companyName', $config['CompanyName']);
        $ui->display('[plugin]hotspot_voucher.tpl');
    }

    function hotspot_getVouchers($router)
    {
        // Initialize an empty array for Mikrotik vouchers
        $mikrotikVouchers = [];

        if ($router) {
            $mikrotik = ORM::for_table('tbl_routers')->where('name', $router)->find_one();
            if ($mikrotik) {
                $iport = explode(":", $mikrotik['ip_address']);
                
                $iport = explode(":", $mikrotik['ip_address']);
                $ip = $iport[0];
                $port = ($iport[1]) ? $iport[1] : 8728;

                // রাউটার অনলাইন কি না তা মাত্র ২ সেকেন্ডে চেক করবে
                $connection_check = @fsockopen($ip, $port, $errno, $errstr, 2);

                if (!$connection_check) {
                    // রাউটার অফলাইন থাকলে কানেক্ট করার চেষ্টা না করে লগ লিখে রাখবে
                    _log("Router Offline: [$router] - Skipping live sync to save time.");
                } else {
                    // পোর্ট খোলা থাকলে কানেক্ট করবে
                    fclose($connection_check);
                    try {
                        $client = new RouterOS\Client($ip, $mikrotik['username'], $mikrotik['password'], $port);

                        $request = new RouterOS\Request('/ip/hotspot/user/print');
                        $response = $client->sendSync($request);

                        foreach ($response as $entry) {
                            $mikrotikVouchers[$entry->getProperty('name')] = [
                                'name' => $entry->getProperty('name'),
                                'profile' => $entry->getProperty('profile'),
                                'uptime' => $entry->getProperty('uptime'),
                                'limit-uptime' => $entry->getProperty('limit-uptime'),
                                'limit-bytes-total' => $entry->getProperty('limit-bytes-total'),
                                'is_used' => ($entry->getProperty('uptime') !== null && $entry->getProperty('uptime') !== '0s') ||
                                    ($entry->getProperty('bytes-in') !== null && $entry->getProperty('bytes-in') > 0) ||
                                    ($entry->getProperty('bytes-out') !== null && $entry->getProperty('bytes-out') > 0),
                            ];
                        }
                    } catch (Exception $e) {
                        _log("Mikrotik sync failed for [$router]: " . $e->getMessage());
                    }
                }
                // --- পরিবর্তন এখানে শেষ ---
            }
        } else {
            return false;
        }
        // Fetch data from the database
        $dbVouchers = ORM::for_table('tbl_plans')
            ->where('routers', $router)
            ->inner_join('tbl_hotspot_vouchers', ['tbl_plans.id', '=', 'tbl_hotspot_vouchers.plan_id'])
            ->select('tbl_plans.*')
            ->select('tbl_hotspot_vouchers.id', 'id')
            ->select('tbl_hotspot_vouchers.code', 'code')
            ->select('tbl_hotspot_vouchers.server', 'server')
            ->select('tbl_hotspot_vouchers.generated_by', 'generated_by')
            ->select('tbl_hotspot_vouchers.is_admin', 'is_admin')
            ->select('tbl_hotspot_vouchers.created_at', 'created_at')
            ->select('tbl_hotspot_vouchers.mac_address', 'mac_address')
            ->select('tbl_hotspot_vouchers.mac_lock', 'mac_lock')
            ->find_many();

        // Gather all generated_by IDs for both admins and resellers
        $adminIds = [];
        $resellerIds = [];

        foreach ($dbVouchers as $voucher) {
            if ($voucher['is_admin']) {
                $adminIds[] = $voucher['generated_by'];
            } else {
                $resellerIds[] = $voucher['generated_by'];
            }
        }

        // Fetch all admin usernames in one query
        $adminUsernames = [];
        if (!empty($adminIds)) {
            $adminUsers = ORM::for_table('tbl_users')
                ->select('id')
                ->select('fullname')
                ->where_in('id', $adminIds)
                ->find_many();

            foreach ($adminUsers as $admin) {
                $adminUsernames[$admin['id']] = $admin['fullname'];
            }
        }

        $resellerUsernames = [];
        if (!empty($resellerIds)) {
            $resellerUsers = ORM::for_table('tbl_hotspot_resellers')
                ->select('id')
                ->select('fullname')
                ->where_in('id', $resellerIds)
                ->find_many();

            foreach ($resellerUsers as $reseller) {
                $resellerUsernames[$reseller['id']] = $reseller['fullname'];
            }
        }

        foreach ($dbVouchers as &$voucher) {
            $voucherName = $voucher['code'];

            $voucher['is_used'] = isset($mikrotikVouchers[$voucherName]) ? $mikrotikVouchers[$voucherName]['is_used'] : false;

            if ($voucher['is_admin']) {
                $voucher['generated_by'] = $adminUsernames[$voucher['generated_by']] ?? 'Unknown Admin';
                $voucher['admin_id'] = $admin['id'];
            } else {
                $voucher['generated_by'] = $resellerUsernames[$voucher['generated_by']] ?? 'Unknown Reseller';
                $voucher['admin_id'] = $reseller['id'];
            }
        }
        return $dbVouchers;
    }


    function hotspot_generateVoucherCode($length, $format)
    {
        $characters = '';
        $characters = match ($format) {
            'numbers' => '0123456789',
            'up' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'low' => 'abcdefghijklmnopqrstuvwxyz',
            'rand' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            default => '0123456789',
        };

        $voucher_code = '';
        for ($i = 0; $i < $length; $i++) {
            $voucher_code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $voucher_code;
    }

    function hotspot_voucherPrint($voucherIds = null)
    {
        global $config;

        // Build the query to fetch vouchers
        $query = ORM::for_table('tbl_hotspot_vouchers')
            ->inner_join('tbl_plans', ['tbl_hotspot_vouchers.plan_id', '=', 'tbl_plans.id'])
            ->left_outer_join('tbl_users', ['tbl_hotspot_vouchers.generated_by', '=', 'tbl_users.id'])
            ->select_many([
                'code' => 'tbl_hotspot_vouchers.code',
                'is_used' => 'tbl_hotspot_vouchers.is_used',
                'server' => 'tbl_hotspot_vouchers.server',
                'generated_by' => 'tbl_hotspot_vouchers.generated_by',
                'admin_name' => 'tbl_users.fullname',
                'plan_price' => 'tbl_plans.price',
                'plan_name' => 'tbl_plans.name_plan',
                'validity' => 'tbl_plans.validity',
                'validity_unit' => 'tbl_plans.validity_unit',
                'data_limit' => 'tbl_plans.data_limit',
                'data_unit' => 'tbl_plans.data_unit',
                'id' => 'tbl_hotspot_vouchers.id',
            ]);

        if ($voucherIds === null) {
            $vouchers = $query->find_many();
        } else {
            $query->where_in('tbl_hotspot_vouchers.id', $voucherIds);
            $vouchers = $query->find_many();
        }

        if (empty($vouchers)) {
            r2(U . "plugin/hotspot_voucher", 'e', Lang::T("No vouchers found for IDs: ") . implode(', ', $voucherIds));
            exit;
        }

        $currency = htmlspecialchars($config['currency_code']);
        $vouchers_per_page = 50;
        $html = '';

        $voucher_count = 0;
        $UPLOAD_PATH = 'system' . DIRECTORY_SEPARATOR . 'uploads';
        $notifications_file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "hotspot_message.json";

        if (file_exists($notifications_file)) {
            $json_data = file_get_contents($notifications_file);
            $json_data_array = json_decode($json_data, true);

            if ($json_data_array && isset($json_data_array['voucher_template'])) {
                $template = htmlspecialchars_decode($json_data_array['voucher_template']);
            } else {
                // Fallback template if JSON file does not contain template
                $template = '<style type="text/css">
                    .voucher-container {
                        width: 250px;
                        height: 70px;
                        border: 1px solid #000;
                        font-family: Arial, sans-serif;
                        font-size: 10px;
                        margin-bottom: 5px;
                        display: flex;
                        background-color: #f7f7f7;
                    }
                    .price-bar {
                        width: 15px;
                        background-color: #ff8c00;
                        color: white;
                        text-align: center;
                        font-weight: bold;
                        padding: 5px 2px;
                        writing-mode: vertical-rl;
                        transform: rotate(180deg);
                    }
                    .details {
                        flex: 1;
                        padding: 5px;
                    }
                    .details .code {
                        font-size: 14px;
                        font-weight: bold;
                        text-align: center;
                        margin-bottom: 2px;
                    }
                    .details .info {
                        font-size: 9px;
                        margin-bottom: 2px;
                    }
                    .qrcode {
                        width: 50px;
                        height: 50px;
                        margin: auto;
                        padding: 5px;
                    }
                    .qrcode img {
                        width: 100%;
                        height: auto;
                    }
                </style>
                <div class="voucher-container">
                    <div class="price-bar">[[currency]][[plan_price]]</div>
                    <div class="details">
                        <div class="code">[[code]]</div>
                        <div class="info">Data Limit: [[data]] Duration: [[validity]]</div>
                        <div class="info">Login: [[url]] </div><br>
                        <div class="info">Thank you for choosing our service</div>
                    </div>
                    <div class="qrcode">[[qrcode]]</div>
                </div>';
            }
        } else {
            // Default template if JSON file does not exist
            $template = '<style type="text/css">
                .voucher-container {
                    width: 250px;
                    height: 70px;
                    border: 1px solid #000;
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    margin-bottom: 5px;
                    display: flex;
                    background-color: #f7f7f7;
                }
                .price-bar {
                    width: 15px;
                    background-color: #ff8c00;
                    color: white;
                    text-align: center;
                    font-weight: bold;
                    padding: 5px 2px;
                    writing-mode: vertical-rl;
                    transform: rotate(180deg);
                }
                .details {
                    flex: 1;
                    padding: 5px;
                }
                .details .code {
                    font-size: 14px;
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 2px;
                }
                .details .info {
                    font-size: 9px;
                    margin-bottom: 2px;
                }
                .qrcode {
                    width: 50px;
                    height: 50px;
                    margin: auto;
                    padding: 5px;
                }
                .qrcode img {
                    width: 100%;
                    height: auto;
                }
            </style>
            <div class="voucher-container">
                <div class="price-bar">[[currency]][[plan_price]]</div>
                <div class="details">
                    <div class="code">[[code]]</div>
                    <div class="info">Data Limit: [[data]] Duration: [[validity]]</div>
                    <div class="info">Login: [[url]] </div><br>
                    <div class="info">Thank you for choosing our service</div>
                </div>
                <div class="qrcode">[[qrcode]]</div>
            </div>';
        }

        foreach ($vouchers as $voucher) {
            $voucher_count++;
            $validity = htmlspecialchars("{$voucher->validity} {$voucher->validity_unit}");
            $dataLimit = $voucher->data_limit;
            $dataUnit = $voucher->data_unit;
            $data = ($dataLimit == '0') ? 'Unlimited' : htmlspecialchars("$dataLimit$dataUnit");
            $qrCode = "<img src=\"qrcode/?data={$voucher->code}\" alt=\"QR Code\">";
            $hotspot_url = htmlspecialchars($config['hotspot_url']);

            $current_voucher = str_replace(
                ['[[currency]]', '[[plan_price]]', '[[code]]', '[[data]]', '[[validity]]', '[[url]]', '[[qrcode]]'],
                [$currency, htmlspecialchars($voucher->plan_price), htmlspecialchars($voucher->code), $data, $validity, $hotspot_url, $qrCode],
                $template
            );

            $html .= $current_voucher;

            if ($voucher_count % $vouchers_per_page == 0 && $voucher_count < count($vouchers)) {
                $html .= '<div class="pagebreak"></div>';
            }
        }

        if (empty($html)) {
            r2(U . "plugin/hotspot_voucher", 'e', Lang::T("Error generating voucher preview. No content."));
            exit;
        }

        // Render the HTML for preview
        echo "<div style=\"display: flex; flex-wrap: wrap; justify-content: space-between;\">$html</div>";
        echo '<button onclick="window.print()">Print</button>';
    }


    function hotspot_voucher_print()
    {
        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
            $voucherIds = json_decode($_POST['voucherIds'], true) ?? [$_GET['voucher_id']];

            if (is_array($voucherIds) && !empty($voucherIds)) {
                hotspot_voucherPrint($voucherIds);
            } else {
                r2(U . "plugin/hotspot_voucher", 'e', Lang::T("No voucher ID provided."));
                exit;
            }
        } else {
            r2(U . "plugin/hotspot_voucher", 'e', Lang::T("Invalid request method"));
        }
    }

    function hotspot_voucher_delete()
    {
        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode([
                'status' => 'error',
                'message' => Lang::T('You do not have permission to access this page')
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $voucherIds = json_decode($_POST['voucherIds'], true);

            if (is_array($voucherIds) && !empty($voucherIds)) {

                $vouchers = ORM::for_table('tbl_hotspot_vouchers')
                    ->where_in('id', $voucherIds)
                    ->find_many();

                if ($vouchers) {
                    foreach ($vouchers as $voucher) {
                        $server = $voucher['server'];
                        $voucherCode = $voucher['code'];

                        // Remove the voucher from Mikrotik router
                        if (!hotspot_removeVoucherFromRouter($server, $voucherCode)) {
                            // echo json_encode([
                            //     'status' => 'error',
                            //     'message' => Lang::T("Failed to remove voucher from router: $voucherCode")
                            // ]);
                            // exit;
                        }
                    }

                    // Delete vouchers from the database
                    ORM::for_table('tbl_hotspot_vouchers')
                        ->where_in('id', $voucherIds)
                        ->delete_many();

                    echo json_encode([
                        'status' => 'success',
                        'message' => Lang::T("Vouchers Deleted Successfully.")
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => Lang::T("No vouchers found to delete.")
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => Lang::T("Invalid or missing voucher IDs.")
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => Lang::T("Invalid request method")
            ]);
        }
        exit;
    }

    function hotspot_removeVoucherFromRouter($server, $voucherCode)
    {
        try {
            // Get router information
            $mikrotik = ORM::for_table('tbl_routers')->where('name', $server)->find_one();
            if (!$mikrotik) {
                _log(Lang::T("Router [$server] not found"));
                return false;
            }

            $iport = explode(":", $mikrotik['ip_address']);
            $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);

            $request = new RouterOS\Request('/ip/hotspot/user/print');
            $request->setQuery(RouterOS\Query::where('name', $voucherCode));

            $responses = $client->sendSync($request);

            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_DATA) {
                    $id = $response->getProperty('.id');
                    $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
                    $removeRequest->setArgument('numbers', $id);
                    $client->sendSync($removeRequest);

                    _log(Lang::T("Voucher [$voucherCode] deleted from router [$server]"));
                    return true;
                }
            }
            _log(Lang::T("Voucher [$voucherCode] not found on router [$server]"));
            return false;
        } catch (Exception $e) {
            _log(Lang::T("Failed to remove voucher from Mikrotik: " . $e->getMessage()));
            return false;
        }
    }

    function hotspot_voucher_sendVoucher()
    {
        global $config;
        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $voucherId = $_POST['voucherId'] ?? null;
            $phoneNumber = $_POST['phoneNumber'] ?? null;
            $sendVia = $_POST['method'] ?? 'sms';

            $UPLOAD_PATH = 'system' . DIRECTORY_SEPARATOR . 'uploads';
            $notifications_file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "hotspot_message.json";

            $default_message = "Dear Customer,\r\nHere is your Voucher Details:\r\nData Limit:  [[data]]\r\nVoucher Code is:  [[code]]\r\nDuration: [[validity]]\r\n\r\n[[company]]";

            if (file_exists($notifications_file)) {
                $json_data = file_get_contents($notifications_file);
                if ($json_data !== false) {
                    $json_data_array = json_decode($json_data, true);
                    $messageContent = $json_data_array['voucher_send'] ?? $default_message;
                } else {
                    $messageContent = $default_message;
                }
            } else {
                $messageContent = $default_message;
            }


            if (!$voucherId || !$phoneNumber) {
                _log("Debug: Voucher ID: $voucherId, Phone Number: $phoneNumber");
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid or missing voucher ID or phone number."));
                exit;
            }

            if ($voucherId && $phoneNumber) {
                $voucher = ORM::for_table('tbl_hotspot_vouchers')->find_one($voucherId);
                $plan = ORM::for_table('tbl_plans')->find_one($voucher->plan_id);
                $expiry = "{$plan->validity}{$plan->validity_unit}";
                $dataLimit = $plan->data_limit;
                $dataUnit = $plan->data_unit;
                $data = ($dataLimit == '0') ? 'Unlimited' : htmlspecialchars("$dataLimit$dataUnit");

                if ($voucher) {
                    $voucherCode = $voucher->code;
                    // Replace placeholders with actual values
                    $message = str_replace('[[company]]', $config['CompanyName'], $messageContent);
                    $message = str_replace('[[data]]', $data, $message);
                    $message = str_replace('[[validity]]', $expiry, $message);
                    $message = str_replace('[[code]]', $voucherCode, $message);

                    $channels = [
                        'sms' => [
                            'enabled' => $sendVia == 'sms' || $sendVia == 'both',
                            'method' => 'Message::sendSMS',
                            'args' => [$phoneNumber, $message]
                        ],
                        'whatsapp' => [
                            'enabled' => $sendVia == 'wa' || $sendVia == 'both',
                            'method' => 'Message::sendWhatsapp',
                            'args' => [$phoneNumber, $message]
                        ]
                    ];

                    try {
                        foreach ($channels as $channel => $channelData) {
                            if ($channelData['enabled']) {
                                try {
                                    call_user_func_array($channelData['method'], $channelData['args']);
                                } catch (Exception $e) {
                                    _log(Lang::T("Failed to send voucher code via $channel: " . $e->getMessage()));
                                }
                            }
                        }

                        r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Voucher code has been send successfully to ") . $phoneNumber);
                    } catch (Exception $e) {
                        r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Failed to send voucher code to ") . $phoneNumber . ' ' . $e->getMessage());
                        _log(Lang::T("Failed to send voucher code to ") . $phoneNumber . ' ' . $e->getMessage());
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Voucher not found.']);
                    r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Voucher not found."));
                }
            } else {
                r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Invalid or missing voucher ID or phone number."));
            }
        } else {
            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid request method"));
            exit;
        }
        exit;
    }

    function hotspot_voucher_getVoucher()
    {
        global $routes;
        $router = $routes['2'];
        $mikrotik = ORM::for_table('tbl_routers')->where('name', $router)->find_one();
        $iport = explode(":", $mikrotik['ip_address']);
        $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
        try {
            $request = new RouterOS\Request('/ip/hotspot/user/print');
            $response = $client->sendSync($request);

            $vouchers = [];
            foreach ($response as $entry) {
                $vouchers[] = [
                    'name' => $entry->getProperty('name'),
                    'profile' => $entry->getProperty('profile'),
                    'uptime' => $entry->getProperty('uptime'),
                    'limit-uptime' => $entry->getProperty('limit-uptime'),
                    'limit-bytes-total' => $entry->getProperty('limit-bytes-total'),
                ];
            }
        } catch (Exception $e) {
            _log(Lang::T("Failed to retrieve vouchers from Mikrotik: " . $e->getMessage()));
        }
        header('Content-Type: application/json');
        echo json_encode($vouchers);
    }

    function hotspot_voucher_getData()
    {
        if (isset($_POST['server'])) {
            $server = htmlspecialchars($_POST['server'], ENT_QUOTES, 'UTF-8');

            $vouchers = ORM::for_table('tbl_plans')
                ->where('routers', $server)
                ->inner_join('tbl_hotspot_vouchers', ['tbl_plans.id', '=', 'tbl_hotspot_vouchers.plan_id'])
                ->left_outer_join('tbl_users', ['tbl_hotspot_vouchers.generated_by', '=', 'tbl_users.id'])
                ->select('tbl_plans.*')
                ->select('tbl_hotspot_vouchers.id', 'id')
                ->select('tbl_hotspot_vouchers.code', 'code')
                ->select('tbl_hotspot_vouchers.is_used', 'is_used')
                ->select('tbl_hotspot_vouchers.server', 'server')
                ->select('tbl_hotspot_vouchers.generated_by', 'generated_by')
                ->select('tbl_users.fullname', 'admin_name')
                ->find_many();

            $vouchersArray = [];
            foreach ($vouchers as $voucher) {
                $vouchersArray[] = $voucher->as_array();
            }
            echo json_encode(['success' => true, 'data' => $vouchersArray]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }

    function hotspot_cron()
    {
        global $config;

        if ($config['hotspot_cev']) {
            $batchSize = $config['hotspot_cev_batch'] ?? 10;
            $totalDeletedCount = 0;
            $iterationCount = 0;
            $maxIterations = 100;

            do {
                // Fetch expired and unprocessed vouchers
                $expiredVouchers = ORM::for_table('tbl_hotspot_payments')
                    ->where('transaction_status', 'paid')
                    ->where('is_processed', 0)
                    ->where_raw("expired_date IS NOT NULL AND expired_date < NOW()")
                    ->limit($batchSize)
                    ->find_many();

                if (empty($expiredVouchers)) {
                    break;
                }

                echo "Processing " . count($expiredVouchers) . " hotspot system expired vouchers.\n";

                $deletedCount = 0;

                foreach ($expiredVouchers as $voucher) {
                    $customer = ORM::for_table('tbl_customers')
                        ->where('username', $voucher->voucher_code)
                        ->find_one();

                    if ($customer) {
                        $customer->delete();
                        $deletedCount++;
                        echo "Customer with voucher code {$voucher->voucher_code} deleted successfully.\n";
                    } else {
                        echo "Customer not found for voucher code: {$voucher->voucher_code}\n";
                    }

                    $voucher->is_processed = 1;
                    $voucher->save();
                }

                $totalDeletedCount += $deletedCount;

                echo "$deletedCount hotspot system expired vouchers have been deleted in this batch.\n";
                $iterationCount++;
            } while (count($expiredVouchers) == $batchSize && $iterationCount < $maxIterations);

            echo "Total $totalDeletedCount hotspot system expired vouchers have been deleted.\n";

            if ($iterationCount >= $maxIterations) {
                echo "Warning: Reached maximum iterations, some expired vouchers might not have been processed.\n";
            }
        }
    }

    function hotspot_generateCsrfToken($expiryTime = 3600)
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        $_SESSION['csrf_token_expiry'] = $expiryTime;

        return $token;
    }

    function hotspot_validateCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            _log(Lang::T("CSRF token not set in session."));
            return false;
        }

        if (is_null($token)) {
            _log(Lang::T("Token passed is null."));
            return false;
        }

        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > $_SESSION['csrf_token_expiry']) {
            _log(Lang::T("CSRF token has expired."));
            return false;
        }


        return hash_equals($_SESSION['csrf_token'], $token);
    }

    function hotspot_GenerateVoucher()
    {
        global $config;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $server = _post('server') ?? '';
            $plan =  _post('plan') ?? '';
            $numbervoucher = intval($_POST['numbervoucher'] ?? 1);
            $voucher_format = _post('voucher_format') ?? 'numbers';
            $prefix = _post('prefix') ?? '';
            $lengthcode = intval($_POST['lengthcode'] ?? 6);
            $batch = intval($_POST['batch'] ?? 1);
            $print = intval($_POST['print_now'] ?? 0);
            $phone = _post('phone') ?? '08023********';
            $email = _post('email') ?? '';
            $generate_by = _post('generate_by') ?? '';
            $is_admin = intval($_POST['is_admin'] ?? 0);
            $activate = intval($_POST['activate'] ?? 0);
            $csrf_token = _post('csrf_token');

            if (!hotspot_validateCsrfToken($csrf_token)) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid CSRF token."));
                return;
            }

            if (empty($email)) {
                $email = hotspot_getEmailAddress($phone);
            }

            if (empty($server) || empty($plan)) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Server and Plan are required."));
                return;
            }

            $planDetails = ORM::for_table('tbl_plans')->find_one($plan);
            if (!$planDetails) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid plan selected."));
                return;
            }

            $plan_price = $planDetails->price;

            if (!$is_admin) {
                $reseller = ORM::for_table('tbl_hotspot_resellers')->where('id', $generate_by)->find_one();
                if (!$reseller) {
                    r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid reseller account."));
                    return;
                }

                $reseller_status = $reseller->status;
                if ($reseller_status != 'Active') {
                    r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Your account is not active, contact admin"));
                    return;
                }

                // Calculate the total cost for vouchers
                $totalVoucherCost = $plan_price * $numbervoucher * $batch;

                // Check if the reseller has sufficient balance
                if (is_numeric($reseller->balance) && $reseller->balance < $totalVoucherCost) {
                    r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Your account balance is low, Please recharge your account"));
                    return;
                }
            }

            $dVoucherIds = [];

            try {
                $mikrotik = ORM::for_table('tbl_routers')->where('name', $server)->find_one();
                $iport = explode(":", $mikrotik['ip_address']);
                $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
            } catch (Exception $e) {
                _log("Mikrotik connection failed: " . $e->getMessage());
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Mikrotik connection failed, check logs for more info"));
                return;
            }

            // Loop through the batch
            for ($b = 0; $b < $batch; $b++) {
                $batchVouchers = [];
                // Generate vouchers for each batch
                for ($i = 0; $i < $numbervoucher; $i++) {
                    $voucher_code = hotspot_generateVoucherCode($lengthcode, $voucher_format);
                    $final_code = "$prefix$voucher_code";

                    // Check if vouchers should be activated
                    if ($activate) {
                        try {
                            $c = ORM::for_table('tbl_customers')->create();
                            $username = $config['hotspot_voucher_mode'] ? $final_code : $phone;
                            $c->username = $username;
                            $c->password = $username;
                            $c->pppoe_password = '0';
                            $c->email = $email;
                            $c->fullname = "Hotspot $phone";
                            $c->address = '';
                            $c->created_by = '1';
                            $c->phonenumber = Lang::phoneFormat($phone);
                            $c->service_type = 'Hotspot';
                            $c->save();

                            if (!Package::rechargeUser($c['id'], $server, $plan, 'Generated', $generate_by)) {
                                throw new Exception('Failed to activate the package.');
                            }

                            $expiration = ORM::for_table('tbl_user_recharges')->where('plan_id', $plan)->where('username', $c['username'])->where('status', 'on')->find_one();
                            if (!$expiration) {
                                throw new Exception('Failed to retrieve expiration details.');
                            }

                            $expired_date = $expiration->expiration;
                            $expired_time = $expiration->time;
                            $expired = $expired_date . " " . date("h:i A", strtotime($expired_time));
                            $plan_name = $expiration->namebp;
                            $loginCode = $config['hotspot_voucher_mode'] ? $final_code : $phone;
                            if (function_exists('hotspot_scheduleCredentialsNotify')) {
                                hotspot_scheduleCredentialsNotify($phone, $plan_name, $loginCode, $expired);
                            } elseif (function_exists('hotspot_sendMessage')) {
                                hotspot_sendMessage($phone, $plan_name, $loginCode, $expired);
                            }
                        } catch (Exception $e) {
                            _log("Failed to process voucher: " . $e->getMessage());
                            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("An error occurred while generating vouchers, check logs for more info"));
                            return;
                        }
                    } else {
                        try {
                            $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
                            if ($planDetails->typebp == "Limited") {
                                if ($planDetails->limit_type == "Time_Limit") {
                                    $timelimit = ($planDetails->time_unit == 'Hrs')
                                        ? "{$planDetails->time_limit}:00:00"
                                        : "00:{$planDetails->time_limit}:00";
                                    $client->sendSync(
                                        $addRequest
                                            ->setArgument('name', $final_code)
                                            ->setArgument('profile', $planDetails->name_plan)
                                            ->setArgument('password', $final_code)
                                            ->setArgument('comment', 'Generated Hotspot Voucher ' . date('Y-m-d H:i:s'))
                                            ->setArgument('email', '')
                                            ->setArgument('limit-uptime', $timelimit)
                                    );
                                } else if ($planDetails->limit_type == "Data_Limit") {
                                    $datalimit = ($planDetails->data_unit == 'GB')
                                        ? "{$planDetails->data_limit}000000000"
                                        : "{$planDetails->data_limit}000000";
                                    $client->sendSync(
                                        $addRequest
                                            ->setArgument('name', $final_code)
                                            ->setArgument('profile', $planDetails->name_plan)
                                            ->setArgument('password', $final_code)
                                            ->setArgument('comment', 'Generated Hotspot Voucher ' . date('Y-m-d H:i:s'))
                                            ->setArgument('email', '')
                                            ->setArgument('limit-bytes-total', $datalimit)
                                    );
                                } else if ($planDetails->limit_type == "Both_Limit") {
                                    $timelimit = ($planDetails->time_unit == 'Hrs')
                                        ? "{$planDetails->time_limit}:00:00"
                                        : "00:{$planDetails->time_limit}:00";
                                    $datalimit = ($planDetails->data_unit == 'GB')
                                        ? "{$planDetails->data_limit}000000000"
                                        : "{$planDetails->data_limit}000000";
                                    $client->sendSync(
                                        $addRequest
                                            ->setArgument('name', $final_code)
                                            ->setArgument('profile', $planDetails->name_plan)
                                            ->setArgument('password', $final_code)
                                            ->setArgument('comment', 'Generated Hotspot Voucher ' . date('Y-m-d H:i:s'))
                                            ->setArgument('email', '')
                                            ->setArgument('limit-uptime', $timelimit)
                                            ->setArgument('limit-bytes-total', $datalimit)
                                    );
                                }
                            } else {
                                $client->sendSync(
                                    $addRequest
                                        ->setArgument('name', $final_code)
                                        ->setArgument('profile', $planDetails->name_plan)
                                        ->setArgument('comment', 'Generated Hotspot Voucher ' . date('Y-m-d H:i:s'))
                                        ->setArgument('email', '')
                                        ->setArgument('password', $final_code)
                                );
                            }
                        } catch (Exception $e) {
                            _log("Failed to add voucher to Mikrotik: " . $e->getMessage());
                            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Failed to add voucher to Mikrotik, check logs for more info"));
                            return;
                        }
                    }

                    $voucher = ORM::for_table('tbl_hotspot_vouchers')->create();
                    $voucher->code = $final_code;
                    $voucher->server = $server;
                    $voucher->plan_id = $plan;
                    $voucher->price = $plan_price;
                    $voucher->validity = $planDetails->validity;
                    $voucher->validity_unit = $planDetails->validity_unit;
                    $voucher->is_used = 0;
                    $voucher->used_date = NULL;
                    $voucher->created_at = date('Y-m-d H:i:s');
                    $voucher->generated_by = $generate_by;
                    $voucher->is_admin = $is_admin;
                    try {
                        $voucher->save();
                        $dVoucherIds[] = $voucher->id;
                    } catch (Exception $e) {
                        _log(Lang::T("Failed to save voucher: ") . $e->getMessage());
                        r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("An error occurred while while saving vouchers, check logs for more info"));
                        return;
                    }

                    $trx = ORM::for_table('tbl_hotspot_payments')->create();
                    $trx->transaction_id = "TRN" . mt_rand(10000000, 99999999);
                    $trx->transaction_ref = uniqid('trx');
                    $trx->amount = $plan_price;
                    $trx->phone_number = $phone;
                    $trx->plan_id = $plan;
                    $trx->plan_name = $planDetails->name_plan;
                    $trx->router_name = $server;
                    $trx->voucher_code = $final_code;
                    $trx->transaction_status = 'paid';
                    $trx->payment_gateway = 'Generated';
                    $trx->payment_method = $generate_by;
                    try {
                        $trx->save();
                    } catch (Exception $e) {
                        _log(Lang::T("An error occurred while saving transactions: ") . $e->getMessage());
                        r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("An error occurred while saving transactions, check logs for more info"));
                        return;
                    }

                    $batchVouchers[] = $final_code;
                }

                $generatedVouchers[] = $batchVouchers;
            }

            // If reseller, deduct balance after voucher creation
            if (!$is_admin) {
                $reseller->balance -= $totalVoucherCost;
                $reseller->save();
            }

            switch ($print) {
                case 1:
                    hotspot_voucherPrint($dVoucherIds);
                    break;
                default:
                    r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Vouchers Created Successfully"));
                    break;
            }
        } else {
            echo Lang::T("Invalid Request Method");
        }
    }
    // ================== MAC RESET ==================
    // ================== MAC RESET ==================
    function hotspot_voucher_reset_mac()
    {
        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('No permission'), 'danger', "dashboard");
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            r2(U . "plugin/hotspot_voucher", 'e', "Invalid request");
            return;
        }

        $d = ORM::for_table('tbl_hotspot_vouchers')->find_one($id);

        if (!$d) {
            r2(U . "plugin/hotspot_voucher", 'e', "Voucher not found");
            return;
        }

        // ================== DB RESET ==================
        $d->mac_lock = 0;
        $d->mac_address = '';
        $d->save();

        // ================== MIKROTIK RESET ==================
        try {
            $router = ORM::for_table('tbl_routers')
                ->where('name', $d->server)
                ->find_one();

            if ($router) {

                $iport = explode(":", $router['ip_address']);
                $ip = $iport[0];
                $port = $iport[1] ?? 8728;

                $client = new RouterOS\Client(
                    $ip,
                    $router['username'],
                    $router['password'],
                    $port
                );

                // ================== FIND USER ==================
                $print = new RouterOS\Request('/ip/hotspot/user/print');
                $print->setQuery(RouterOS\Query::where('name', $d->code));

                $res = $client->sendSync($print);

                foreach ($res as $r) {

                    if ($r->getType() === RouterOS\Response::TYPE_DATA) {

                        $id = $r->getProperty('.id');

                        // ================== RESET MAC ==================
                        $set = new RouterOS\Request('/ip/hotspot/user/set');
                        $set->setArgument('numbers', $id);
                        $set->setArgument('mac-address', '00:00:00:00:00:00');

                        $client->sendSync($set);

                        _log("MAC RESET OK: " . $d->code);
                    }
                }
            }

        } catch (Exception $e) {
            _log("MikroTik MAC reset error: " . $e->getMessage());
        }

        r2(U . "plugin/hotspot_voucher", 's', "MAC Reset Done Successfully");
    }


    // ================== GLOBAL LOCK ==================
    function hotspot_voucher_bulk_lock()
    {
        $lock_status = isset($_POST['lock_status']) ? intval($_POST['lock_status']) : 0;

        $vouchers = ORM::for_table('tbl_hotspot_vouchers')->find_many();

        foreach ($vouchers as $v) {

            $server = trim($v->server);
            $code   = trim($v->code);

            $router = ORM::for_table('tbl_routers')->where('name', $server)->find_one();
            if (!$router) continue;

            $iport = explode(":", $router['ip_address']);
            $ip = $iport[0];
            $port = $iport[1] ?? 8728;

            try {
                $client = new RouterOS\Client($ip, $router['username'], $router['password'], $port);

                // ================== FIND USER ==================
                $print = new RouterOS\Request('/ip/hotspot/user/print');
                $res = $client->sendSync($print);

                foreach ($res as $r) {

                    $user = trim($r->getProperty('name'));

                    if ($user != $code) continue;

                    $id = $r->getProperty('.id');

                    // ================== LOCK ==================
                    if ($lock_status == 1) {

                        // 🔥 ACTIVE TABLE থেকে MAC বের করো
                        $activeReq = new RouterOS\Request('/ip/hotspot/active/print');
                        $activeRes = $client->sendSync($activeReq);

                        $mac = null;

                        foreach ($activeRes as $a) {
                            $activeUser = trim($a->getProperty('user'));

                            if ($activeUser == $code) {
                                $mac = $a->getProperty('mac-address');
                                break;
                            }
                        }

                        if ($mac) {
                            // 🔒 Router এ MAC bind
                            $set = new RouterOS\Request('/ip/hotspot/user/set');
                            $set->setArgument('numbers', $id);
                            $set->setArgument('mac-address', $mac);
                            $client->sendSync($set);

                            $v->mac_address = $mac;
                        }

                        $v->mac_lock = 1;

                    } else {
                        // ================== UNLOCK ==================
                        $set = new RouterOS\Request('/ip/hotspot/user/set');
                        $set->setArgument('numbers', $id);
                        $set->setArgument('mac-address', "00:00:00:00:00:00");
                        $client->sendSync($set);

                        $v->mac_address = NULL;
                        $v->mac_lock = 0;
                    }

                    $v->save();
                }

            } catch (Exception $e) {
                _log("MAC Lock error: " . $e->getMessage());
            }
        }

        if ($lock_status == 1) {
            r2(U . 'plugin/hotspot_voucher', 's', 'ALL MAC LOCK ENABLED');
        } else {
            r2(U . 'plugin/hotspot_voucher', 's', 'ALL MAC LOCK DISABLED');
        }
    }