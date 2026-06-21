<?php

/**
 * Compte vitrine « demo / wifizone » : données fictives en session, sans sync routeur.
 */
class DemoShowcase
{
    public const USERNAME = 'demo';
    public const PASSWORD = 'wifizone';

    public static function ensureAccount()
    {
        $user = ORM::for_table('tbl_users')
            ->where('username', self::USERNAME)
            ->find_one();
        if ($user) {
            return self::syncExistingAccount($user);
        }

        $now = date('Y-m-d H:i:s');
        $user = ORM::for_table('tbl_users')->create();
        $user->root = 0;
        $user->username = self::USERNAME;
        $user->fullname = 'Compte Démo';
        $user->password = Password::_crypt(self::PASSWORD);
        $user->phone = '';
        $user->email = 'demo@wifizone.local';
        $user->user_type = 'Admin';
        $user->status = 'Active';
        $user->data = json_encode(['showcase_demo' => true], JSON_UNESCAPED_UNICODE);
        $user->creationdate = $now;
        $user->save();

        $adminId = (int) $user->id();
        self::ensureSubscription($adminId, $now);

        return $adminId;
    }

    private static function syncExistingAccount($user)
    {
        $adminId = (int) $user->id();
        $data = self::decodeData($user->data ?? '');
        $data['showcase_demo'] = true;
        $dirty = false;

        if (($user->status ?? '') !== 'Active') {
            $user->status = 'Active';
            $dirty = true;
        }
        if (($user->user_type ?? '') !== 'Admin') {
            $user->user_type = 'Admin';
            $dirty = true;
        }
        if (!Password::_verify(self::PASSWORD, (string) ($user->password ?? ''))) {
            $user->password = Password::_crypt(self::PASSWORD);
            $dirty = true;
        }
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ((string) ($user->data ?? '') !== $encoded) {
            $user->data = $encoded;
            $dirty = true;
        }
        if ($dirty) {
            $user->save();
        }

        self::ensureSubscription($adminId, date('Y-m-d H:i:s'));

        return $adminId;
    }

    private static function ensureSubscription($adminId, $now)
    {
        AdminSubscription::ensureSchema();
        $sub = ORM::for_table('admin_subscriptions')->where('admin_id', $adminId)->find_one();
        if (!$sub) {
            $sub = ORM::for_table('admin_subscriptions')->create();
            $sub->admin_id = $adminId;
            $sub->plan_type = 'business';
            $sub->status = 'active';
            $sub->subscription_start = $now;
            $sub->subscription_end = date('Y-m-d H:i:s', strtotime('+10 years'));
            $sub->routers_count = 0;
            $sub->created_at = $now;
            $sub->updated_at = $now;
            $sub->save();
            return;
        }
        if (($sub->status ?? '') !== 'active') {
            $sub->status = 'active';
            $sub->subscription_end = date('Y-m-d H:i:s', strtotime('+10 years'));
            $sub->updated_at = $now;
            $sub->save();
        }
    }

    public static function isShowcaseUser($adminOrId = null)
    {
        if ($adminOrId === null) {
            global $admin;
            $adminOrId = $admin ?? null;
        }
        if (is_array($adminOrId)) {
            $username = strtolower(trim((string) ($adminOrId['username'] ?? '')));
            if ($username === self::USERNAME) {
                return true;
            }
            $data = self::decodeData($adminOrId['data'] ?? '');
            return !empty($data['showcase_demo']);
        }
        if (is_numeric($adminOrId)) {
            $row = ORM::for_table('tbl_users')->find_one((int) $adminOrId);
            return $row ? self::isShowcaseUser($row->as_array()) : false;
        }
        return false;
    }

    public static function isActive($adminOrId = null)
    {
        if (!self::isShowcaseUser($adminOrId)) {
            return false;
        }
        $aid = self::resolveAdminId($adminOrId);
        return $aid > 0 && (int) ($_SESSION['aid'] ?? 0) === $aid;
    }

    public static function clientIp()
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $raw = trim(explode(',', (string) $_SERVER[$key])[0]);
            if ($raw !== '') {
                return $raw;
            }
        }
        return '127.0.0.1';
    }

    public static function onLogin($adminId)
    {
        if (!self::isShowcaseUser((int) $adminId)) {
            return;
        }
        $_SESSION['demo_login_nonce'] = uniqid('demo_', true);
        self::regenerateStats(self::clientIp());
        $_SESSION['demo_showcase_notice'] = true;
    }

    public static function bootstrapSession($admin = null)
    {
        global $ui;
        if (!self::isActive($admin)) {
            return;
        }
        $ip = self::clientIp();
        $storedIp = (string) ($_SESSION['demo_showcase_ip'] ?? '');
        if ($storedIp === '' || $storedIp !== $ip || empty($_SESSION['demo_showcase_stats'])) {
            self::regenerateStats($ip);
        }
        if (isset($ui)) {
            $ui->assign('demo_showcase_active', true);
            $ui->assign('demo_showcase_stats', self::stats());
            $ui->assign('demo_showcase_dataset', self::dataset());
        }
    }

    public static function stats()
    {
        return is_array($_SESSION['demo_showcase_stats'] ?? null)
            ? $_SESSION['demo_showcase_stats']
            : self::regenerateStats(self::clientIp());
    }

    public static function dataset()
    {
        return is_array($_SESSION['demo_showcase_dataset'] ?? null)
            ? $_SESSION['demo_showcase_dataset']
            : (self::regenerateStats(self::clientIp())['dataset'] ?? []);
    }

    public static function regenerateStats($ip)
    {
        $nonce = (string) ($_SESSION['demo_login_nonce'] ?? '');
        $seed = crc32((string) $ip . '|' . $nonce . '|' . date('Y-m-d'));
        mt_srand($seed);

        $routersTotal = mt_rand(2, 5);
        $routersConnected = mt_rand(max(1, $routersTotal - 1), $routersTotal);
        $hAct = mt_rand(35, 180);
        $hExp = mt_rand(8, 55);
        $pAct = mt_rand(18, 95);
        $pExp = mt_rand(5, 32);
        $ftthAct = mt_rand(6, 40);
        $ftthExp = mt_rand(2, 12);
        $uAct = $hAct + $pAct + $ftthAct;
        $uAll = $uAct + $hExp + $pExp + $ftthExp;
        $cAll = mt_rand($uAll, $uAll + mt_rand(10, 40));
        $iday = mt_rand(8500, 48000);
        $imonth = mt_rand(180000, 920000);
        $wBalance = mt_rand(45000, 320000);
        $wCommission = mt_rand(12000, 85000);
        $withdrawalsApproved = mt_rand(80000, 450000);
        $hotspotOnline = self::randInt((int) ($hAct * 0.35), (int) ($hAct * 0.85));
        $pppoeOnline = self::randInt((int) ($pAct * 0.4), (int) ($pAct * 0.9));
        $ftthOnline = self::randInt((int) ($ftthAct * 0.5), (int) ($ftthAct * 0.95));

        $routerNames = self::buildRouters($routersTotal, $routersConnected);
        $customers = self::buildCustomers($cAll, $routerNames);
        $dataset = [
            'customers' => $customers,
            'routers' => $routerNames,
            'plans_hotspot' => self::buildPlans('Hotspot', $routerNames, mt_rand(5, 8)),
            'plans_pppoe' => self::buildPlans('PPPoE', $routerNames, mt_rand(4, 6)),
            'plans_ftth' => self::buildPlans('FTTH', $routerNames, mt_rand(3, 5)),
            'monthly_sales' => self::buildMonthlySales($imonth),
            'monthly_registered' => self::buildMonthlyRegistered($cAll),
            'withdrawals' => self::buildWithdrawals($withdrawalsApproved),
            'transactions' => self::buildTransactions($iday, $imonth),
            'subscription' => self::buildSubscriptionBundle($routersTotal, $imonth),
            'expiry' => self::buildExpiryBundle($customers, $routerNames, $hExp, $pExp + $ftthExp),
        ];

        $stats = [
            'routers_total' => $routersTotal,
            'routers_connected' => $routersConnected,
            'h_act' => $hAct,
            'h_exp' => $hExp,
            'p_act' => $pAct,
            'p_exp' => $pExp,
            'ftth_act' => $ftthAct,
            'ftth_exp' => $ftthExp,
            'u_act' => $uAct,
            'u_all' => $uAll,
            'c_all' => $cAll,
            'iday' => $iday,
            'imonth' => $imonth,
            'w_balance' => $wBalance,
            'w_commission' => $wCommission,
            'withdrawals_approved' => $withdrawalsApproved,
            'hotspot_online' => $hotspotOnline,
            'pppoe_online' => $pppoeOnline,
            'ftth_online' => $ftthOnline,
            'staff_count' => mt_rand(1, 4),
            'network_revenue' => mt_rand(120000, 680000),
            'dataset' => $dataset,
        ];

        $_SESSION['demo_showcase_ip'] = (string) $ip;
        $_SESSION['demo_showcase_stats'] = $stats;
        $_SESSION['demo_showcase_dataset'] = $dataset;
        self::syncShowcaseSubscriptionMeta($routersTotal);
        mt_srand();

        return $stats;
    }

    public static function injectPaginatedList($ui, array $rows, $perPage = 30, $appendUrl = '')
    {
        global $routes;
        $page = max(1, (int) _get('p', 1));
        $total = count($rows);
        $lastpage = max(1, (int) ceil($total / $perPage));
        $startpoint = ($page - 1) * $perPage;
        $url = getUrl(implode('/', $routes));
        if ($appendUrl !== '') {
            $url .= $appendUrl . '&p=';
        } else {
            $url .= '&p=';
        }
        $url = Text::fixUrl($url);
        $ui->assign('paginator', [
            'count' => $lastpage,
            'limit' => $perPage,
            'startpoint' => $startpoint,
            'url' => $url,
            'page' => min($page, $lastpage),
            'pages' => range(1, min($lastpage, 5)),
            'prev' => max(0, $page - 1),
            'next' => min($lastpage, $page + 1),
        ]);
        $ui->assign('d', array_slice($rows, $startpoint, $perPage));
    }

    public static function injectCustomersList($ui, $perPage, $appendUrl)
    {
        self::injectPaginatedList($ui, self::dataset()['customers'] ?? [], $perPage, $appendUrl);
    }

    public static function injectRoutersList($ui, $perPage = 20)
    {
        self::injectPaginatedList($ui, self::dataset()['routers'] ?? [], $perPage);
    }

    public static function injectPlansList($ui, $type, $perPage = 20, $appendUrl = '')
    {
        $dataset = self::dataset();
        $rows = $dataset['plans_hotspot'] ?? [];
        if ($type === 'PPPoE') {
            $rows = array_merge($dataset['plans_pppoe'] ?? [], $dataset['plans_ftth'] ?? []);
        } elseif ($type === 'FTTH') {
            $rows = $dataset['plans_ftth'] ?? [];
        }
        self::injectPaginatedList($ui, $rows, $perPage, $appendUrl);
    }

    public static function applyMonitoring($ui)
    {
        $s = self::stats();
        $ui->assign('c_all', $s['c_all']);
        $ui->assign('h_all', $s['h_act'] + $s['h_exp']);
        $ui->assign('p_all', $s['p_act'] + $s['p_exp'] + $s['ftth_act'] + $s['ftth_exp']);
        $ui->assign('h_act', $s['hotspot_online']);
        $ui->assign('p_act', $s['pppoe_online'] + $s['ftth_online']);
    }

    public static function applyFinance($ui)
    {
        $s = self::stats();
        $ui->assign('iday', $s['iday']);
        $ui->assign('imonth', $s['imonth']);
        $ui->assign('w_balance', $s['w_balance']);
        $ui->assign('w_commission', $s['w_commission']);
    }

    public static function monthlySalesData()
    {
        return self::dataset()['monthly_sales'] ?? [];
    }

    public static function monthlyRegisteredData()
    {
        return self::dataset()['monthly_registered'] ?? [];
    }

    private static function buildCustomers($count, array $routers)
    {
        $first = ['Amadou', 'Fatou', 'Jean', 'Marie', 'Serge', 'Aminata', 'Paul', 'Claire', 'Ibrahim', 'Grace', 'Eric', 'Sandrine', 'Kevin', 'Nadia', 'Brice'];
        $last = ['Diallo', 'Ngono', 'Mbarga', 'Fouda', 'Tchinda', 'Essomba', 'Njoya', 'Kamdem', 'Abena', 'Manga', 'Owona', 'Bella', 'Tchoumi', 'Nguema', 'Atangana'];
        $types = ['Hotspot', 'PPPoE', 'Others'];
        $rows = [];
        $limit = min(max((int) $count, 12), 40);
        for ($i = 1; $i <= $limit; $i++) {
            $type = $types[mt_rand(0, count($types) - 1)];
            $active = mt_rand(1, 100) > 18;
            $username = 'client' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $pppoeUser = $type === 'PPPoE' ? 'pppoe_' . $username : '';
            $pppoeIp = $type === 'PPPoE' ? '10.20.' . mt_rand(1, 254) . '.' . mt_rand(2, 250) : '';
            $rows[] = [
                'id' => 90000 + $i,
                'username' => $username,
                'photo' => '/admin.default.png',
                'account_type' => 'Personal',
                'fullname' => $first[mt_rand(0, count($first) - 1)] . ' ' . $last[mt_rand(0, count($last) - 1)],
                'balance' => mt_rand(0, 25000),
                'phonenumber' => '6' . mt_rand(70, 99) . mt_rand(100000, 999999),
                'email' => $username . '@demo.wifizone.local',
                'coordinates' => '',
                'service_type' => $type,
                'pppoe_username' => $pppoeUser,
                'pppoe_ip' => $pppoeIp,
                'status' => $active ? 'Active' : 'Inactive',
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 240) . ' days')),
                'address' => 'Quartier ' . ['Akwa', 'Bonamoussadi', 'Makepe', 'Logpom', 'Bastos'][mt_rand(0, 4)],
            ];
        }
        return $rows;
    }

    private static function buildRouters($total, $connected)
    {
        $zones = ['Nord', 'Centre', 'Sud', 'Est', 'Ouest', 'Makepe', 'Logpom'];
        $rows = [];
        for ($i = 1; $i <= $total; $i++) {
            $online = $i <= $connected;
            $name = 'MikroTik-' . $zones[($i - 1) % count($zones)];
            $rows[] = [
                'id' => 80000 + $i,
                'name' => $name,
                'ip_address' => '10.10.' . $i . '.1:8728',
                'username' => 'admin',
                'description' => 'Routeur vitrine zone ' . $zones[($i - 1) % count($zones)],
                'status' => $online ? 'Online' : 'Offline',
                'last_seen' => $online ? date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 45) . ' minutes')) : date('Y-m-d H:i:s', strtotime('-' . mt_rand(2, 48) . ' hours')),
                'enabled' => 1,
                'coordinates' => '',
            ];
        }
        return $rows;
    }

    private static function buildPlans($type, array $routers, $count)
    {
        $routerName = $routers[0]['name'] ?? 'MikroTik-Centre';
        $templates = [
            'Hotspot' => [
                ['1 Heure', 500, '1', 'Hrs', '5M/2M'],
                ['3 Heures', 1000, '3', 'Hrs', '10M/5M'],
                ['24 Heures', 2000, '1', 'Days', '15M/8M'],
                ['1 Semaine', 5000, '7', 'Days', '20M/10M'],
                ['VIP Illimité', 15000, '30', 'Days', '30M/15M'],
            ],
            'PPPoE' => [
                ['PPPoE Starter 5M', 5000, '30', 'Days', '5M/2M'],
                ['PPPoE Pro 10M', 10000, '30', 'Days', '10M/5M'],
                ['PPPoE Business 20M', 18000, '30', 'Days', '20M/10M'],
                ['PPPoE Premium 50M', 35000, '30', 'Days', '50M/25M'],
            ],
            'FTTH' => [
                ['FTTH Fibre 20M', 25000, '30', 'Days', '20M/20M'],
                ['FTTH Fibre 50M', 45000, '30', 'Days', '50M/50M'],
                ['FTTH Fibre 100M', 75000, '30', 'Days', '100M/100M'],
                ['FTTH Pro PME', 120000, '30', 'Days', '200M/200M'],
            ],
        ];
        $list = $templates[$type] ?? $templates['Hotspot'];
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $tpl = $list[$i % count($list)];
            $rows[] = [
                'id' => 70000 + ($i + 1) + (crc32($type) % 1000),
                'name_plan' => $tpl[0],
                'prepaid' => 'yes',
                'plan_type' => 'Business',
                'name_bw' => $tpl[4],
                'typebp' => $type === 'FTTH' ? 'FTTH' : 'Personal',
                'price' => $tpl[1],
                'price_old' => mt_rand(0, 1) ? (string) ($tpl[1] + mt_rand(500, 2000)) : '',
                'validity' => $tpl[2],
                'validity_unit' => $tpl[3],
                'time_limit' => $tpl[2],
                'time_unit' => $tpl[3],
                'data_limit' => $type === 'Hotspot' ? mt_rand(0, 5000) : 0,
                'data_unit' => 'MB',
                'is_radius' => 0,
                'routers' => $routers[$i % max(1, count($routers))]['name'] ?? $routerName,
                'device' => $type === 'Hotspot' ? 'MikrotikHotspot' : 'MikrotikPppoe',
                'plan_expired' => 0,
                'expired_date' => 20,
                'enabled' => 1,
                'type' => $type === 'FTTH' ? 'PPPoE' : $type,
            ];
        }
        return $rows;
    }

    private static function buildMonthlySales($monthTotal)
    {
        $month = (int) date('n');
        $sales = [];
        $remaining = max(0, (int) $monthTotal);
        for ($m = 1; $m <= 12; $m++) {
            if ($m < $month) {
                if ($m === $month - 1) {
                    $val = max(0, $remaining);
                } else {
                    $cap = max(1, (int) round($monthTotal * 0.25));
                    $hi = min($remaining, max($cap, 80000));
                    $lo = min(80000, $hi);
                    $val = self::randInt($lo, $hi);
                }
                $remaining -= $val;
            } elseif ($m === $month) {
                $val = max(0, $remaining);
            } else {
                $val = self::randInt(60000, 180000);
            }
            $sales[] = ['month' => $m, 'totalSales' => max(0, $val)];
        }
        return $sales;
    }

    private static function buildMonthlyRegistered($totalCustomers)
    {
        $month = (int) date('n');
        $rows = [];
        $remaining = $totalCustomers;
        for ($m = 1; $m <= $month; $m++) {
            $count = ($m === $month) ? max(1, $remaining) : self::randInt(2, max(3, (int) ($totalCustomers / 6)));
            $remaining -= $count;
            $rows[] = ['date' => $m, 'count' => max(1, $count)];
        }
        return $rows;
    }

    private static function buildWithdrawals($approvedTotal)
    {
        $names = ['Amadou Diallo', 'Fatou Njie', 'Jean Mbarga', 'Marie Essomba', 'Serge Kamdem'];
        $rows = [];
        $count = mt_rand(3, 6);
        $remaining = $approvedTotal;
        for ($i = 1; $i <= $count; $i++) {
            $amount = ($i === $count) ? max(5000, $remaining) : self::randInt(10000, (int) max(10000, $approvedTotal / 2));
            $remaining -= $amount;
            $rows[] = (object) [
                'id' => 60000 + $i,
                'amount' => $amount,
                'operator' => mt_rand(0, 1) ? 'MTN MoMo' : 'Orange Money',
                'status' => 'approved',
                'beneficiary_name' => $names[mt_rand(0, count($names) - 1)],
                'beneficiary_phone' => '6' . mt_rand(70, 99) . mt_rand(100000, 999999),
                'client_note' => 'Retrait démo',
                'admin_comment' => 'Versement validé automatiquement',
                'reference' => 'WD-DEMO-' . mt_rand(10000, 99999),
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 45) . ' days')),
            ];
        }
        return $rows;
    }

    private static function buildTransactions($daily, $monthly)
    {
        $methods = ['CamPay MTN', 'CamPay Orange', 'Voucher', 'Cash'];
        $rows = [];
        for ($i = 1; $i <= 8; $i++) {
            $rows[] = [
                'id' => 50000 + $i,
                'price' => mt_rand(500, 15000),
                'method' => $methods[mt_rand(0, count($methods) - 1)],
                'recharged_on' => date('Y-m-d', strtotime('-' . mt_rand(0, 20) . ' days')),
                'username' => 'client' . str_pad((string) mt_rand(1, 30), 3, '0', STR_PAD_LEFT),
            ];
        }
        return $rows;
    }

    public static function applyTopWidgetStats($ui)
    {
        $s = self::stats();
        $ui->assign('iday', number_format($s['iday'], 0, '.', ' '));
        $ui->assign('imonth', number_format($s['imonth'], 0, '.', ' '));
        $ui->assign('u_act', (string) $s['u_act']);
        $ui->assign('u_all', (string) $s['u_all']);
        $ui->assign('c_all', (string) $s['c_all']);
        $ui->assign('h_all', (string) ($s['h_act'] + $s['h_exp']));
        $ui->assign('p_all', (string) ($s['p_act'] + $s['p_exp']));
        $ui->assign('h_act', (string) $s['h_act']);
        $ui->assign('p_act', (string) $s['p_act']);
        $ui->assign('h_exp', (string) $s['h_exp']);
        $ui->assign('p_exp', (string) $s['p_exp']);
        $ui->assign('w_balance', $s['w_balance']);
        $ui->assign('w_commission', $s['w_commission']);
        $ui->assign('staff_count', $s['staff_count']);
        $ui->assign('pppoeOnline', $s['pppoe_online']);
        $ui->assign('hotspotOnline', $s['hotspot_online']);
        $ui->assign('demo_showcase_active', true);
    }

    public static function uiRouters()
    {
        return self::dataset()['routers'] ?? [];
    }

    public static function uiCustomers()
    {
        return self::dataset()['customers'] ?? [];
    }

    public static function monitoringPayload()
    {
        $s = self::stats();
        $dataset = self::dataset();
        $routers = $dataset['routers'] ?? [];
        $hTotal = (int) ($s['h_act'] + $s['h_exp']);
        $pTotal = (int) ($s['p_act'] + $s['p_exp'] + $s['ftth_act'] + $s['ftth_exp']);
        $hPct = $hTotal > 0 ? (int) round(($s['hotspot_online'] / $hTotal) * 100) : 0;
        $pPct = $pTotal > 0 ? (int) round((($s['pppoe_online'] + $s['ftth_online']) / $pTotal) * 100) : 0;

        return [
            'c_all' => (int) $s['c_all'],
            'h_all' => $hTotal,
            'p_all' => $pTotal,
            'h_act' => (int) $s['hotspot_online'],
            'h_total' => $hTotal,
            'h_off' => (int) $s['h_exp'],
            'p_act' => (int) ($s['pppoe_online'] + $s['ftth_online']),
            'p_total' => $pTotal,
            'p_off' => (int) ($s['p_exp'] + $s['ftth_exp']),
            'h_pct' => $hPct,
            'p_pct' => $pPct,
            'alerts' => self::randInt(0, 3),
            'trends' => [
                'customers' => self::randInt(2, 18),
                'hotspot' => self::randInt(5, 35),
                'pppoe' => self::randInt(3, 22),
            ],
            'sparklines' => [
                'customers' => self::buildSparkline((int) $s['c_all'], 7),
                'hotspot' => self::buildSparkline((int) $s['hotspot_online'], 7),
                'pppoe' => self::buildSparkline((int) ($s['pppoe_online'] + $s['ftth_online']), 7),
            ],
            'chart' => [
                'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                'hotspot' => self::buildMonthlySeries((int) $s['h_act']),
                'pppoe' => self::buildMonthlySeries((int) ($s['p_act'] + $s['ftth_act'])),
            ],
            'recent' => self::buildRecentConnections($dataset['customers'] ?? []),
            'top_hotspots' => self::buildTopHotspots($routers),
        ];
    }

    public static function assignMonitoringExpiry($ui)
    {
        $expiry = self::dataset()['expiry'] ?? ['expired' => [], 'coming' => [], 'rows' => []];
        $page = max(1, (int) _get('exp_page', 1));
        $perPage = 10;
        $rows = $expiry['rows'] ?? [];
        $totalEntries = count($rows);
        $maxPages = max(1, (int) ceil($totalEntries / $perPage));
        $page = min($page, $maxPages);
        $offset = ($page - 1) * $perPage;

        $ui->assign('exp_total_expired', count($expiry['expired'] ?? []));
        $ui->assign('exp_total_coming', count($expiry['coming'] ?? []));
        $ui->assign('exp_rows', array_slice($rows, $offset, $perPage));
        $ui->assign('exp_total_entries', $totalEntries);
        $ui->assign('exp_current_page', $page);
        $ui->assign('exp_max_pages', $maxPages);
        $logPage = max(1, (int) _get('log_page', 1));
        $ui->assign('exp_prev_url', getUrl('monitoring/expiry&exp_page=' . max(1, $page - 1) . '&log_page=' . $logPage));
        $ui->assign('exp_next_url', getUrl('monitoring/expiry&exp_page=' . min($maxPages, $page + 1) . '&log_page=' . $logPage));
        $ui->assign('exp_today_label', date('d M Y'));
    }

    public static function assignCustomerExpiryWidget($ui)
    {
        $expiry = self::dataset()['expiry'] ?? ['expired' => [], 'coming' => []];
        $page = max(1, (int) _get('exp_page', 1));
        $perPage = 5;
        $already = array_slice($expiry['expired'] ?? [], ($page - 1) * $perPage, $perPage);
        $coming = array_slice($expiry['coming'] ?? [], ($page - 1) * $perPage, $perPage);
        $totalAlready = count($expiry['expired'] ?? []);
        $totalComing = count($expiry['coming'] ?? []);
        $maxPages = max(1, (int) ceil(max($totalAlready, $totalComing) / $perPage));

        $ui->assign('already_expired', $already);
        $ui->assign('coming_expired', $coming);
        $ui->assign('total_already', $totalAlready);
        $ui->assign('total_coming', $totalComing);
        $ui->assign('current_page', $page);
        $ui->assign('max_pages', $maxPages);
        $ui->assign('prev_page', max(1, $page - 1));
        $ui->assign('next_page', min($maxPages, $page + 1));
    }

    public static function subscriptionStats()
    {
        $bundle = self::dataset()['subscription'] ?? [];
        return $bundle['stats'] ?? [
            'routers' => (int) (self::stats()['routers_total'] ?? 0),
            'router_limit' => 3,
            'invoice_total' => 0,
            'paid_total' => 0,
        ];
    }

    public static function subscriptionInvoices()
    {
        return self::dataset()['subscription']['invoices'] ?? [];
    }

    public static function subscriptionPayments()
    {
        return self::dataset()['subscription']['payments'] ?? [];
    }

    public static function dataUsageApiPayload($startDate, $endDate, $serviceType = '', $routerFilter = '', $search = '')
    {
        $s = self::stats();
        $dataset = self::dataset();
        $customers = $dataset['customers'] ?? [];
        $routers = $dataset['routers'] ?? [];
        $startDate = $startDate ?: date('Y-01-01');
        $endDate = $endDate ?: date('Y-m-d');

        $connectedKeys = [];
        $onlineTarget = (int) ($s['hotspot_online'] + $s['pppoe_online'] + $s['ftth_online']);
        foreach ($customers as $customer) {
            if (($customer['status'] ?? '') !== 'Active') {
                continue;
            }
            $username = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
            if ($username === '') {
                continue;
            }
            $connectedKeys[strtolower($username)] = true;
            if (count($connectedKeys) >= $onlineTarget) {
                break;
            }
        }

        $usageRows = self::buildDataUsageRows($customers, $routers, $connectedKeys, $serviceType, $routerFilter, $search);
        $totalDl = 0.0;
        $totalUl = 0.0;
        foreach ($usageRows as $row) {
            $totalDl += (double) ($row['dl_bytes'] ?? 0);
            $totalUl += (double) ($row['ul_bytes'] ?? 0);
        }

        $dayMap = [];
        foreach ($usageRows as $row) {
            $day = $row['log_day'];
            if (!isset($dayMap[$day])) {
                $dayMap[$day] = ['dl' => 0.0, 'ul' => 0.0];
            }
            $dayMap[$day]['dl'] += round(((double) $row['dl_bytes']) / 1048576, 2);
            $dayMap[$day]['ul'] += round(((double) $row['ul_bytes']) / 1048576, 2);
        }

        if (function_exists('reports_data_usage_fill_chart_series')) {
            [$chartLabels, $chartDownload, $chartUpload] = reports_data_usage_fill_chart_series($dayMap, $startDate, $endDate);
        } else {
            [$chartLabels, $chartDownload, $chartUpload] = self::fillChartSeries($dayMap, $startDate, $endDate);
        }

        $fmt = static function ($bytes) {
            return function_exists('reports_data_usage_format')
                ? reports_data_usage_format($bytes)
                : self::formatBytes($bytes);
        };

        $clientsBreakdown = [];
        $byUser = [];
        foreach ($usageRows as $row) {
            $username = $row['username'];
            if (!isset($byUser[$username])) {
                $byUser[$username] = [
                    'username' => $username,
                    'fullname' => $row['fullname'],
                    'phonenumber' => $row['phonenumber'],
                    'service_type' => $row['service_type'],
                    'router' => $row['router'],
                    'dl_bytes' => 0.0,
                    'ul_bytes' => 0.0,
                ];
            }
            $byUser[$username]['dl_bytes'] += (double) $row['dl_bytes'];
            $byUser[$username]['ul_bytes'] += (double) $row['ul_bytes'];
        }
        foreach ($byUser as $row) {
            $ttl = (double) $row['dl_bytes'] + (double) $row['ul_bytes'];
            $clientsBreakdown[] = [
                'username' => $row['username'],
                'fullname' => $row['fullname'],
                'phonenumber' => $row['phonenumber'],
                'service_type' => $row['service_type'],
                'router' => $row['router'],
                'status' => isset($connectedKeys[strtolower($row['username'])]) ? 'Connected' : 'Disconnected',
                'download' => $fmt($row['dl_bytes']),
                'upload' => $fmt($row['ul_bytes']),
                'total' => $fmt($ttl),
                'total_bytes' => $ttl,
            ];
        }
        usort($clientsBreakdown, static function ($a, $b) {
            return ($b['total_bytes'] ?? 0) <=> ($a['total_bytes'] ?? 0);
        });

        $topUsers = [];
        foreach (array_slice($clientsBreakdown, 0, 5) as $row) {
            $topUsers[] = [
                'username' => $row['username'],
                'fullname' => $row['fullname'],
                'download_formatted' => $row['download'],
                'total_formatted' => $row['total'],
            ];
        }

        $byRouter = [];
        foreach ($usageRows as $row) {
            $router = $row['router'] ?: 'Unknown';
            if (!isset($byRouter[$router])) {
                $byRouter[$router] = ['ttl' => 0.0, 'dl' => 0.0];
            }
            $byRouter[$router]['ttl'] += (double) $row['dl_bytes'] + (double) $row['ul_bytes'];
            $byRouter[$router]['dl'] += (double) $row['dl_bytes'];
        }
        uasort($byRouter, static function ($a, $b) {
            return $b['ttl'] <=> $a['ttl'];
        });
        $topRouters = [];
        foreach (array_slice($byRouter, 0, 5, true) as $name => $metrics) {
            $topRouters[] = [
                'name' => $name,
                'traffic_formatted' => $fmt($metrics['ttl']),
                'download_formatted' => $fmt($metrics['dl']),
            ];
        }

        $byService = [];
        foreach ($clientsBreakdown as $row) {
            $service = $row['service_type'] ?: 'Autre';
            if (!isset($byService[$service])) {
                $byService[$service] = 0.0;
            }
            $byService[$service] += (double) ($row['total_bytes'] ?? 0);
        }
        arsort($byService);
        $topServices = [];
        foreach (array_slice($byService, 0, 5, true) as $name => $bytes) {
            $topServices[] = [
                'name' => $name,
                'traffic_formatted' => $fmt($bytes),
            ];
        }

        $formattedData = [];
        foreach ($usageRows as $row) {
            $key = strtolower((string) $row['username']);
            $formattedData[] = [
                'username' => $row['username'],
                'router' => $row['router'],
                'status' => isset($connectedKeys[$key]) ? 'Connected' : 'Disconnected',
                'date' => $row['log_day'],
                'metrics' => [
                    'download' => $fmt($row['dl_bytes']),
                    'upload' => $fmt($row['ul_bytes']),
                    'total' => $fmt((double) $row['dl_bytes'] + (double) $row['ul_bytes']),
                    'raw_download_mb' => round(((double) $row['dl_bytes']) / 1048576, 2),
                    'raw_upload_mb' => round(((double) $row['ul_bytes']) / 1048576, 2),
                ],
            ];
        }

        $activeClients = 0;
        foreach (array_unique(array_column($clientsBreakdown, 'username')) as $username) {
            if (isset($connectedKeys[strtolower((string) $username)])) {
                $activeClients++;
            }
        }
        $uniqueUsers = count($byUser);
        $saturation = $uniqueUsers > 0 ? round(min(100, ($activeClients / $uniqueUsers) * 100), 1) : 0;
        $peakMbps = round(self::randInt(12, 85) + (mt_rand(0, 99) / 100), 2);

        $routersStatus = [];
        foreach ($routers as $router) {
            $online = (($router['status'] ?? '') === 'Online');
            $routersStatus[] = [
                'name' => $router['name'],
                'ip' => $router['ip_address'],
                'status' => $online ? 'online' : 'offline',
                'last_sync' => $online ? date('d/m/Y H:i', strtotime('-' . self::randInt(2, 45) . ' minutes')) : 'Jamais',
                'last_sync_raw' => $online ? date('Y-m-d H:i:s', strtotime('-' . self::randInt(2, 45) . ' minutes')) : null,
                'error' => $online ? '' : 'Routeur hors ligne (démo)',
            ];
        }

        return [
            'status' => 'success',
            'resolved_username' => $search,
            'summary' => [
                'download' => $fmt($totalDl),
                'upload' => $fmt($totalUl),
                'combined' => $fmt($totalDl + $totalUl),
                'download_bytes' => $totalDl,
                'upload_bytes' => $totalUl,
                'peak_mbps' => $peakMbps,
                'active_clients' => $activeClients,
                'unique_users' => $uniqueUsers,
                'saturation_pct' => $saturation,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'download_mb' => $chartDownload,
                'upload_mb' => $chartUpload,
            ],
            'top_users' => $topUsers,
            'top_routers' => $topRouters,
            'top_services' => $topServices,
            'clients_breakdown' => $clientsBreakdown,
            'routers_status' => $routersStatus,
            'data' => $formattedData,
        ];
    }

    public static function blocksRouterSync($adminOrId = null)
    {
        return self::isShowcaseUser($adminOrId);
    }

    public static function assertRouterMutationAllowed()
    {
        if (self::isActive()) {
            r2(getUrl('dashboard'), 'w', 'Compte démo : ajout ou synchronisation de routeur désactivé.');
        }
    }

    private static function resolveAdminId($adminOrId)
    {
        if (is_array($adminOrId)) {
            return (int) ($adminOrId['id'] ?? 0);
        }
        if (is_numeric($adminOrId)) {
            return (int) $adminOrId;
        }
        return (int) ($_SESSION['aid'] ?? 0);
    }

    private static function decodeData($raw)
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** mt_rand sûr pour PHP 8+ (min <= max). */
    private static function randInt($min, $max)
    {
        $min = (int) $min;
        $max = (int) $max;
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return $min === $max ? $min : mt_rand($min, $max);
    }

    private static function formatBytes($bytes)
    {
        $bytes = (double) $bytes;
        if ($bytes <= 0) {
            return '0 Bytes';
        }
        $base = log($bytes, 1024);
        $suffixes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $floor = min((int) floor($base), 4);
        return round(pow(1024, $base - $floor), 2) . ' ' . $suffixes[$floor];
    }

    private static function syncShowcaseSubscriptionMeta($routersTotal)
    {
        $aid = (int) ($_SESSION['aid'] ?? 0);
        if ($aid <= 0 || !self::isShowcaseUser($aid)) {
            return;
        }
        try {
            AdminSubscription::ensureSchema();
            $sub = ORM::for_table('admin_subscriptions')->where('admin_id', $aid)->find_one();
            if (!$sub) {
                return;
            }
            $sub->plan_type = 'business';
            $sub->status = 'active';
            $sub->routers_count = (int) $routersTotal;
            if (empty($sub->subscription_end) || strtotime((string) $sub->subscription_end) < time()) {
                $sub->subscription_end = date('Y-m-d H:i:s', strtotime('+10 years'));
            }
            $sub->updated_at = date('Y-m-d H:i:s');
            $sub->save();
        } catch (Exception $e) {
        }
    }

    private static function buildSparkline($latest, $points)
    {
        $latest = max(1, (int) $latest);
        $values = [];
        for ($i = 0; $i < $points; $i++) {
            $values[] = self::randInt(max(1, (int) ($latest * 0.55)), $latest);
        }
        $values[$points - 1] = $latest;
        return $values;
    }

    private static function buildMonthlySeries($maxValue)
    {
        $maxValue = max(1, (int) $maxValue);
        $month = (int) date('n');
        $series = [];
        for ($m = 1; $m <= 12; $m++) {
            if ($m <= $month) {
                $series[] = self::randInt(max(1, (int) ($maxValue * 0.35)), $maxValue);
            } else {
                $series[] = 0;
            }
        }
        return $series;
    }

    private static function buildRecentConnections(array $customers)
    {
        $events = [];
        $plans = ['1 Heure', '24 Heures', 'PPPoE Pro 10M', 'FTTH Fibre 20M'];
        foreach (array_slice($customers, 0, 8) as $customer) {
            $online = ($customer['status'] ?? '') === 'Active';
            $events[] = [
                'text' => ($customer['service_type'] ?? 'Client') . ' · ' . ($customer['fullname'] ?? $customer['username']) . ($online ? ' connecté' : ' déconnecté'),
                'time' => date('H:i', strtotime('-' . self::randInt(5, 240) . ' minutes')),
                'type' => $customer['service_type'] ?? 'Hotspot',
                'status' => $online ? 'on' : 'off',
            ];
        }
        return $events;
    }

    private static function buildTopHotspots(array $routers)
    {
        $rows = [];
        foreach (array_slice($routers, 0, 5) as $router) {
            $rows[] = [
                'name' => $router['name'],
                'clients' => self::randInt(3, 28),
            ];
        }
        return $rows;
    }

    private static function buildSubscriptionBundle($routersTotal, $monthlyRevenue)
    {
        $businessPrice = 5000.0;
        if (class_exists('AdminSubscription')) {
            $settings = AdminSubscription::settings();
            $businessPrice = (float) ($settings['business_price'] ?? $businessPrice);
        }
        $paidTotal = $businessPrice * self::randInt(2, 4);
        $now = date('Y-m-d H:i:s');
        $invoices = [];
        $payments = [];
        for ($i = 1; $i <= 3; $i++) {
            $created = date('Y-m-d H:i:s', strtotime('-' . ($i * 47) . ' days'));
            $invoices[] = [
                'invoice_no' => 'INV-DEMO-' . date('Ym', strtotime($created)) . '-' . $i,
                'plan_type' => 'business',
                'amount' => $businessPrice,
                'status' => $i === 1 ? 'paid' : ($i === 2 ? 'paid' : 'unpaid'),
                'created_at' => $created,
            ];
            if ($i < 3) {
                $payments[] = [
                    'amount' => $businessPrice,
                    'method' => $i % 2 ? 'CamPay MTN' : 'CamPay Orange',
                    'reference' => 'PAY-DEMO-' . mt_rand(100000, 999999),
                    'status' => 'paid',
                    'created_at' => $created,
                ];
            }
        }
        return [
            'stats' => [
                'routers' => (int) $routersTotal,
                'router_limit' => 3,
                'invoice_total' => $businessPrice * 3,
                'paid_total' => $paidTotal,
            ],
            'invoices' => $invoices,
            'payments' => $payments,
        ];
    }

    private static function buildExpiryBundle(array $customers, array $routers, $expiredCount, $comingCount)
    {
        $routerName = $routers[0]['name'] ?? 'MikroTik-Centre';
        $plans = ['1 Heure', '24 Heures', 'PPPoE Pro 10M', 'FTTH Fibre 20M'];
        $expired = [];
        $coming = [];
        $rows = [];
        $nowTs = time();
        $idx = 0;
        foreach ($customers as $customer) {
            if ($idx >= ($expiredCount + $comingCount)) {
                break;
            }
            $username = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
            $isExpired = ($customer['status'] ?? '') !== 'Active' || $idx < (int) $expiredCount;
            if ($isExpired) {
                $expirationAt = date('Y-m-d H:i:s', $nowTs - self::randInt(3600, 86400 * 14));
                $status = 'expired';
            } else {
                $expirationAt = date('Y-m-d H:i:s', $nowTs + self::randInt(86400, 86400 * 5));
                $status = self::randInt(0, 1) ? 'soon' : 'coming';
            }
            $record = (object) [
                'id' => (int) ($customer['id'] ?? (90000 + $idx)),
                'username' => $username,
                'fullname' => $customer['fullname'] ?? $username,
                'phonenumber' => $customer['phonenumber'] ?? '',
                'email' => $customer['email'] ?? '',
                'namebp' => $plans[$idx % count($plans)],
                'routers' => $customer['routers'] ?? $routerName,
                'expiration' => substr($expirationAt, 0, 10),
                'time' => substr($expirationAt, 11, 8),
                'recharged_on' => date('Y-m-d', strtotime('-30 days')),
                'recharged_time' => '10:00:00',
            ];
            $row = [
                'id' => (int) $record->id,
                'username' => (string) $record->username,
                'fullname' => (string) $record->fullname,
                'phonenumber' => (string) $record->phonenumber,
                'email' => (string) $record->email,
                'namebp' => (string) $record->namebp,
                'routers' => (string) $record->routers,
                'expiration_at' => $expirationAt,
                'status' => $status,
            ];
            $rows[] = $row;
            if ($status === 'expired') {
                $expired[] = $record;
            } else {
                $coming[] = $record;
            }
            $idx++;
        }
        usort($rows, static function ($a, $b) {
            $prio = ['expired' => 0, 'soon' => 1, 'coming' => 2];
            $pa = $prio[$a['status']] ?? 3;
            $pb = $prio[$b['status']] ?? 3;
            return $pa <=> $pb ?: strcmp($a['expiration_at'], $b['expiration_at']);
        });
        return compact('expired', 'coming', 'rows');
    }

    private static function buildDataUsageRows(array $customers, array $routers, array $connectedKeys, $serviceType, $routerFilter, $search)
    {
        $rows = [];
        $routerNames = array_column($routers, 'name');
        $search = strtolower(trim((string) $search));
        $end = new DateTime(date('Y-m-d'));
        foreach ($customers as $customer) {
            $username = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
            $service = $customer['service_type'] ?? 'Hotspot';
            if ($serviceType !== '' && strcasecmp($serviceType, $service) !== 0 && !($serviceType === 'Others' && !in_array($service, ['Hotspot', 'PPPoE'], true))) {
                continue;
            }
            $router = $routerNames ? $routerNames[mt_rand(0, count($routerNames) - 1)] : 'MikroTik-Centre';
            if ($routerFilter !== '' && strcasecmp($routerFilter, $router) !== 0) {
                continue;
            }
            if ($search !== '') {
                $hay = strtolower(implode(' ', [
                    $username,
                    $customer['fullname'] ?? '',
                    $customer['phonenumber'] ?? '',
                ]));
                if (strpos($hay, $search) === false) {
                    continue;
                }
            }
            $days = self::randInt(1, 4);
            for ($d = 0; $d < $days; $d++) {
                $day = (clone $end)->modify('-' . $d . ' days')->format('Y-m-d');
                $dl = (double) self::randInt(50, 1800) * 1048576;
                $ul = (double) self::randInt(20, 650) * 1048576;
                $rows[] = [
                    'username' => $username,
                    'fullname' => $customer['fullname'] ?? '—',
                    'phonenumber' => $customer['phonenumber'] ?? '',
                    'service_type' => $service,
                    'router' => $router,
                    'log_day' => $day,
                    'dl_bytes' => $dl,
                    'ul_bytes' => $ul,
                ];
            }
        }
        return $rows;
    }

    private static function fillChartSeries(array $dayMap, $startDate, $endDate, $maxDays = 90)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        $totalDays = (int) $start->diff($end)->days + 1;
        if ($totalDays > $maxDays) {
            $start = (clone $end)->modify('-' . ($maxDays - 1) . ' days');
        }
        $labels = [];
        $download = [];
        $upload = [];
        $cursor = clone $start;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $key;
            $download[] = (float) ($dayMap[$key]['dl'] ?? 0);
            $upload[] = (float) ($dayMap[$key]['ul'] ?? 0);
            $cursor->modify('+1 day');
        }
        return [$labels, $download, $upload];
    }
}
