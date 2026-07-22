<?php

/**
 * Métriques « Command Center » pour le dashboard admin.
 */
class DashboardCommand
{
    public static function gather($admin): array
    {
        global $UPLOAD_PATH, $config;

        $isScoped = ($admin['user_type'] ?? '') !== 'SuperAdmin';
        $adminId = (int) ($admin['id'] ?? 0);

        if (DemoShowcase::isActive($admin)) {
            $s = DemoShowcase::stats();
            return self::demoPayload($s, $config);
        }

        $totalAdmin = 1;
        if (!$isScoped) {
            $totalAdmin = (int) ORM::for_table('tbl_users')
                ->where_in('user_type', ['Admin', 'SuperAdmin', 'Agent', 'Report', 'Sales'])
                ->count();
        }

        $rechargesQ = ORM::for_table('tbl_user_recharges')->where('status', 'on');
        $salesTodayQ = ORM::for_table('tbl_transactions')->where('recharged_on', date('Y-m-d'));
        $routersQ = ORM::for_table('tbl_routers')->where('enabled', 1);
        if ($isScoped) {
            $rechargesQ->where('admin_id', $adminId);
            $salesTodayQ->where('admin_id', $adminId);
            $routersQ->where('admin_id', $adminId);
        }

        $activeCustomers = (int) $rechargesQ->count();
        $salesToday = (float) ($salesTodayQ->sum('price') ?: 0);
        $routers = $routersQ->find_many();
        $routersTotal = count($routers);
        $offlineRouters = self::countOfflineRouters($routers);

        $serviceStats = self::serviceStats($admin, $isScoped, $adminId);
        $totalCustomers = self::totalCustomers($admin, $isScoped, $adminId);
        $networkUsage = $totalCustomers > 0
            ? min(100, round(($activeCustomers / $totalCustomers) * 100, 1))
            : 0;

        $voucher = self::voucherStock($admin, $isScoped, $adminId);
        $traffic = self::trafficLast7Days($admin, $isScoped, $adminId);
        $dataUsage = [
            'download_mb' => round(array_sum($traffic['download']), 1),
            'upload_mb' => round(array_sum($traffic['upload']), 1),
            'labels' => $traffic['labels'],
            'download' => $traffic['download'],
            'upload' => $traffic['upload'],
        ];
        $dataUsage['combined_mb'] = round($dataUsage['download_mb'] + $dataUsage['upload_mb'], 1);

        $cronLastRun = WifiZoneOps::getCronLastRunTimestamp();
        $cronStale = !WifiZoneOps::isCronHeartbeatFresh(3600);

        $totalActive = $serviceStats['hotspot_active'] + $serviceStats['pppoe_active'];
        $totalExpired = $serviceStats['hotspot_expired'] + $serviceStats['pppoe_expired'];
        $networkSummary = self::networkSummary($routers, $offlineRouters, $routersTotal);
        $notifications = self::notificationStatus($config);
        $reseller = self::resellerSummary($admin, $isScoped, $adminId);

        return [
            'total_admin' => $totalAdmin,
            'active_customers' => $activeCustomers,
            'customer_growth' => self::customerGrowth($admin, $isScoped, $adminId),
            'sales_today' => $salesToday,
            'currency' => WifiZoneCore::config('currency_code', 'XAF'),
            'offline_routers' => $offlineRouters,
            'routers_total' => $routersTotal,
            'routers_online' => max(0, $routersTotal - $offlineRouters),
            'hotspot_active' => $serviceStats['hotspot_active'],
            'hotspot_expired' => $serviceStats['hotspot_expired'],
            'pppoe_active' => $serviceStats['pppoe_active'],
            'pppoe_expired' => $serviceStats['pppoe_expired'],
            'total_active' => $totalActive,
            'total_expired' => $totalExpired,
            'network_usage' => $networkUsage,
            'network_summary' => $networkSummary,
            'notification_status' => $notifications,
            'reseller_plan' => $reseller['plan'],
            'commission_rate' => $reseller['commission_rate'],
            'active_resellers' => $reseller['active_resellers'],
            'total_commission' => $reseller['total_commission'],
            'voucher_stock' => $voucher['rows'],
            'total_unused' => $voucher['unused'],
            'total_used' => $voucher['used'],
            'traffic_labels' => $traffic['labels'],
            'traffic_download' => $traffic['download'],
            'traffic_upload' => $traffic['upload'],
            'data_usage' => $dataUsage,
            'cron_last_run' => $cronLastRun,
            'cron_stale' => $cronStale,
        ];
    }

    public static function activityLogs($admin, int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $scopeSql = wifizone_activity_log_widget_scope_sql();

        $countQuery = ORM::for_table('tbl_logs')->where_raw($scopeSql);
        if (!empty($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $countQuery->where('userid', (int) $admin['id']);
        }
        $total = (int) $countQuery->count();

        $query = ORM::for_table('tbl_logs')->where_raw($scopeSql)->order_by_desc('id');
        if (!empty($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $query->where('userid', (int) $admin['id']);
        }
        $rows = $query->limit($perPage)->offset($offset)->find_array();

        $logs = [];
        foreach ($rows as $row) {
            $norm = wifizone_normalize_log_display_row($row);
            $norm = Lang::translateLogRows([$norm])[0];
            $desc = (string) ($norm['description'] ?? '');
            $type = 'info';
            if (stripos($desc, 'login') !== false && stripos($desc, 'fail') === false) {
                $type = 'login';
            } elseif (stripos($desc, 'logout') !== false) {
                $type = 'logout';
            } elseif (stripos($desc, 'fail') !== false || stripos($desc, 'error') !== false) {
                $type = 'error';
            }
            $logs[] = [
                'time_ago' => self::timeAgo($norm['date'] ?? date('Y-m-d H:i:s')),
                'type' => $type,
                'username' => (string) ($norm['username'] ?? 'system'),
                'message' => $desc,
                'sid' => (string) ($norm['ip'] ?? ''),
            ];
        }

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $pages = [];
        $start = max(1, $page - 2);
        $end = min($totalPages, $start + 4);
        $start = max(1, $end - 4);
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = ['num' => $i, 'active' => $i === $page];
        }

        return [
            'activity_logs' => $logs,
            'total_entries' => $total,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'pagination_pages' => $pages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1),
        ];
    }

    private static function demoPayload(array $s, array $config): array
    {
        return [
            'total_admin' => 7,
            'active_customers' => (int) ($s['u_act'] ?? 3),
            'sales_today' => (float) ($s['iday'] ?? 0),
            'currency' => WifiZoneCore::config('currency_code', 'XAF'),
            'offline_routers' => max(0, ($s['routers_total'] ?? 0) - ($s['routers_connected'] ?? 0)),
            'hotspot_active' => (int) ($s['hotspot_active'] ?? 3),
            'hotspot_expired' => (int) ($s['hotspot_expired'] ?? 1),
            'pppoe_active' => (int) ($s['pppoe_active'] ?? 0),
            'pppoe_expired' => (int) ($s['pppoe_expired'] ?? 0),
            'customer_growth' => 2,
            'total_active' => 3,
            'total_expired' => 1,
            'routers_total' => 2,
            'routers_online' => 2,
            'network_usage' => 75,
            'network_summary' => [
                'primary_router' => 'Demo-Router — 192.168.88.1',
                'routers_label' => '2 / 2 online',
                'dns_servers' => '8.8.8.8 / 1.1.1.1',
                'gateway_online' => true,
            ],
            'notification_status' => [
                'email' => true,
                'sms' => false,
                'telegram' => false,
            ],
            'reseller_plan' => 'Business',
            'commission_rate' => 10,
            'active_resellers' => 3,
            'total_commission' => 12450,
            'voucher_stock' => [['package' => 'Demo', 'unused' => 10, 'used' => 2]],
            'total_unused' => 10,
            'total_used' => 2,
            'traffic_labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            'traffic_download' => [120, 190, 300, 250, 420, 380, 290],
            'traffic_upload' => [80, 110, 210, 180, 310, 270, 190],
            'data_usage' => [
                'download_mb' => 1950,
                'upload_mb' => 1350,
                'combined_mb' => 3300,
                'labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                'download' => [120, 190, 300, 250, 420, 380, 290],
                'upload' => [80, 110, 210, 180, 310, 270, 190],
            ],
            'cron_last_run' => time() - 300,
            'cron_stale' => false,
        ];
    }

    private static function countOfflineRouters($routers): int
    {
        $offline = 0;
        foreach ($routers as $r) {
            $parts = explode(':', (string) $r->ip_address);
            $host = $parts[0];
            $port = $parts[1] ?? 8728;
            $fp = @fsockopen($host, (int) $port, $errno, $errstr, 1);
            if (!$fp) {
                $offline++;
            } else {
                fclose($fp);
            }
        }
        return $offline;
    }

    private static function serviceStats($admin, bool $isScoped, int $adminId): array
    {
        $scope = function ($type) use ($isScoped, $adminId) {
            $q = ORM::for_table('tbl_user_recharges')->table_alias('tur')
                ->where('tur.type', $type);
            if ($isScoped) {
                $q->where('tur.admin_id', $adminId);
            }
            return $q;
        };

        return [
            'hotspot_active' => (int) $scope('Hotspot')->where('tur.status', 'on')->count(),
            'hotspot_expired' => (int) $scope('Hotspot')->where('tur.status', 'off')->count(),
            'pppoe_active' => (int) $scope('PPPOE')->where('tur.status', 'on')->count(),
            'pppoe_expired' => (int) $scope('PPPOE')->where('tur.status', 'off')->count(),
        ];
    }

    private static function totalCustomers($admin, bool $isScoped, int $adminId): int
    {
        $q = ORM::for_table('tbl_customers');
        if ($isScoped) {
            $q->where('created_by', $adminId);
        }
        return (int) $q->count();
    }

    private static function voucherStock($admin, bool $isScoped, int $adminId): array
    {
        if (($GLOBALS['config']['disable_voucher'] ?? '') === 'yes') {
            return ['rows' => [], 'unused' => 0, 'used' => 0];
        }
        $plansQ = ORM::for_table('tbl_plans')->select('id')->select('name_plan');
        if ($isScoped) {
            $rootId = !empty($admin['root']) ? (int) $admin['root'] : $adminId;
            $plansQ->where('admin_id', $rootId);
        }
        $rows = [];
        $unusedTotal = 0;
        $usedTotal = 0;
        foreach ($plansQ->find_many() as $plan) {
            $unused = (int) ORM::for_table('tbl_voucher')->where('id_plan', $plan->id)->where('status', 0)->count();
            $used = (int) ORM::for_table('tbl_voucher')->where('id_plan', $plan->id)->where('status', 1)->count();
            if ($unused <= 0 && $used <= 0) {
                continue;
            }
            $rows[] = ['package' => $plan->name_plan, 'unused' => $unused, 'used' => $used];
            $unusedTotal += $unused;
            $usedTotal += $used;
        }
        return ['rows' => $rows, 'unused' => $unusedTotal, 'used' => $usedTotal];
    }

    private static function trafficLast7Days($admin, bool $isScoped, int $adminId): array
    {
        $labels = [];
        $download = [];
        $upload = [];
        try {
            $db = ORM::get_db();
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $labels[] = date('D', strtotime($day));
                $params = [$day . ' 00:00:00', $day . ' 23:59:59'];
                $sql = 'SELECT COALESCE(SUM(u.download_bytes),0) AS dl, COALESCE(SUM(u.upload_bytes),0) AS ul
                    FROM api_data_usage u
                    LEFT JOIN tbl_customers c ON (
                        u.username COLLATE utf8mb4_general_ci = c.username COLLATE utf8mb4_general_ci
                        OR u.username COLLATE utf8mb4_general_ci = c.pppoe_username COLLATE utf8mb4_general_ci
                    )
                    WHERE u.log_date >= ? AND u.log_date <= ?';
                if ($isScoped) {
                    $sql .= ' AND (
                        u.admin_id = ?
                        OR c.created_by = ?
                        OR EXISTS (
                            SELECT 1 FROM tbl_routers r
                            WHERE r.name = u.router_name AND r.enabled = 1 AND r.admin_id = ?
                        )
                    )';
                    $params[] = $adminId;
                    $params[] = $adminId;
                    $params[] = $adminId;
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['dl' => 0, 'ul' => 0];
                $download[] = round(((float) $row['dl']) / 1048576, 1);
                $upload[] = round(((float) $row['ul']) / 1048576, 1);
            }
        } catch (Exception $e) {
            for ($i = 6; $i >= 0; $i--) {
                $labels[] = date('D', strtotime("-{$i} days"));
                $download[] = 0;
                $upload[] = 0;
            }
        }
        return ['labels' => $labels, 'download' => $download, 'upload' => $upload];
    }

    private static function customerGrowth($admin, bool $isScoped, int $adminId): int
    {
        $thisMonthStart = date('Y-m-01 00:00:00');
        $lastMonthStart = date('Y-m-01 00:00:00', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t 23:59:59', strtotime('-1 month'));

        $countInRange = function ($start, $end) use ($isScoped, $adminId) {
            $q = ORM::for_table('tbl_customers')->where_gte('created_at', $start)->where_lte('created_at', $end);
            if ($isScoped) {
                $q->where('created_by', $adminId);
            }
            return (int) $q->count();
        };

        return $countInRange($thisMonthStart, date('Y-m-d 23:59:59')) - $countInRange($lastMonthStart, $lastMonthEnd);
    }

    private static function networkSummary($routers, int $offline, int $total): array
    {
        global $config;
        $primary = '—';
        if (!empty($routers)) {
            $r = $routers[0];
            $primary = trim((string) ($r->name ?? 'Router')) . ' — ' . trim((string) ($r->ip_address ?? ''));
        }
        $online = max(0, $total - $offline);
        return [
            'primary_router' => $primary,
            'routers_label' => $online . ' / ' . $total . ' online',
            'dns_servers' => trim((string) ($config['dns_servers'] ?? '')) ?: '8.8.8.8 / 1.1.1.1',
            'gateway_online' => $total > 0 && $offline === 0,
        ];
    }

    private static function notificationStatus(array $config): array
    {
        return [
            'email' => !empty($config['smtp_host']) || !empty($config['mail_from']),
            'sms' => !empty($config['sms_url']) || !empty($config['mikrotik_sms_command']),
            'telegram' => !empty($config['telegram_bot']),
        ];
    }

    private static function resellerSummary($admin, bool $isScoped, int $adminId): array
    {
        $plan = 'Standard';
        $commissionRate = 10.0;
        $activeResellers = 0;
        $totalCommission = 0.0;

        if (($admin['user_type'] ?? '') === 'Admin') {
            $sub = AdminSubscription::getForAdmin($adminId);
            if (!empty($sub->plan_type)) {
                $plan = ucfirst((string) $sub->plan_type);
            } elseif ($sub->status === 'trial') {
                $plan = 'Demo';
            }
        }

        try {
            WifiZoneWallet::ensureSchema();
            if (!$isScoped) {
                $activeResellers = (int) ORM::for_table('tbl_users')->where('user_type', 'Admin')->where('status', 'Active')->count();
                $totalCommission = (float) (ORM::for_table('admin_wallet')->where_gte('updated_at', date('Y-m-01 00:00:00'))->sum('commission_balance') ?: 0);
            } else {
                $wallet = ORM::for_table('admin_wallet')->where('admin_id', $adminId)->find_one();
                if ($wallet && isset($wallet->commission_rate)) {
                    $commissionRate = (float) $wallet->commission_rate;
                }
                $totalCommission = (float) ($wallet->commission_balance ?? 0);
            }
        } catch (Exception $e) {
            // keep defaults
        }

        return [
            'plan' => $plan,
            'commission_rate' => $commissionRate,
            'active_resellers' => $activeResellers,
            'total_commission' => $totalCommission,
        ];
    }

    private static function timeAgo(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (!$ts) {
            return $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return $diff . 's';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'm';
        }
        return floor($diff / 86400) . 'j';
    }
}
