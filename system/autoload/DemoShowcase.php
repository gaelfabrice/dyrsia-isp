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
        $dataset = [
            'customers' => self::buildCustomers($cAll, $routerNames),
            'routers' => $routerNames,
            'plans_hotspot' => self::buildPlans('Hotspot', $routerNames, mt_rand(5, 8)),
            'plans_pppoe' => self::buildPlans('PPPoE', $routerNames, mt_rand(4, 6)),
            'plans_ftth' => self::buildPlans('FTTH', $routerNames, mt_rand(3, 5)),
            'monthly_sales' => self::buildMonthlySales($imonth),
            'monthly_registered' => self::buildMonthlyRegistered($cAll),
            'withdrawals' => self::buildWithdrawals($withdrawalsApproved),
            'transactions' => self::buildTransactions($iday, $imonth),
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
            'open_tickets' => mt_rand(0, 4),
            'network_revenue' => mt_rand(120000, 680000),
            'dataset' => $dataset,
        ];

        $_SESSION['demo_showcase_ip'] = (string) $ip;
        $_SESSION['demo_showcase_stats'] = $stats;
        $_SESSION['demo_showcase_dataset'] = $dataset;
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
        $ui->assign('open_tickets', $s['open_tickets']);
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
}
