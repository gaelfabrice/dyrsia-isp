<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Reports'));
$ui->assign('_system_menu', 'reports');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$mdate = date('Y-m-d');
$mtime = date('H:i:s');
$tdate = date('Y-m-d', strtotime('today - 30 days'));
$firs_day_month = date('Y-m-01');
$this_week_start = date('Y-m-d', strtotime('previous sunday'));
$before_30_days = date('Y-m-d', strtotime('today - 30 days'));
$month_n = date('n');

function reports_data_usage_install()
{
    $db = ORM::get_db();
    $db->exec("CREATE TABLE IF NOT EXISTS `api_data_usage` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `admin_id` int(11) DEFAULT NULL,
      `username` varchar(64) NOT NULL,
      `router_name` varchar(64) DEFAULT 'Unknown',
      `download_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `upload_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `total_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `status` varchar(20) DEFAULT 'Disconnected',
      `log_date` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `admin_date` (`admin_id`, `log_date`),
      KEY `username_date` (`username`, `log_date`),
      KEY `router_name` (`router_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS `api_data_usage_meta` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `meta_key` varchar(128) NOT NULL,
      `meta_value` LONGTEXT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `meta_key` (`meta_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try {
        $columns = $db->query("SHOW COLUMNS FROM api_data_usage LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            $db->exec("ALTER TABLE `api_data_usage` ADD COLUMN `admin_id` int(11) DEFAULT NULL AFTER `id`");
            $db->exec("ALTER TABLE `api_data_usage` ADD KEY `admin_date` (`admin_id`, `log_date`)");
        }
    } catch (Exception $e) {
    }
    try {
        $columns = $db->query("SHOW COLUMNS FROM tbl_routers LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            $db->exec("ALTER TABLE `tbl_routers` ADD COLUMN `admin_id` int(11) DEFAULT NULL AFTER `id`");
        }
    } catch (Exception $e) {
    }
}

function reports_data_usage_format($bytes)
{
    if ($bytes <= 0 || !is_numeric($bytes)) {
        return '0 Bytes';
    }
    $base = log($bytes, 1024);
    $suffixes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $floor = min((int) floor($base), 4);
    return round(pow(1024, $base - $floor), 2) . ' ' . $suffixes[$floor];
}

function reports_data_usage_fill_chart_series(array $dayMap, $startDate, $endDate, $maxDays = 90)
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
        $day = $cursor->format('Y-m-d');
        $labels[] = $day;
        $download[] = (double) ($dayMap[$day]['dl'] ?? 0);
        $upload[] = (double) ($dayMap[$day]['ul'] ?? 0);
        $cursor->modify('+1 day');
    }

    return [$labels, $download, $upload];
}

function reports_data_usage_customer_join()
{
    return " LEFT JOIN tbl_customers c ON (
        (
            u.username COLLATE utf8mb4_unicode_ci = c.username COLLATE utf8mb4_unicode_ci
            OR u.username COLLATE utf8mb4_unicode_ci = c.pppoe_username COLLATE utf8mb4_unicode_ci
        )
        AND EXISTS (
            SELECT 1 FROM tbl_routers r
            WHERE r.name COLLATE utf8mb4_unicode_ci = u.router_name COLLATE utf8mb4_unicode_ci
              AND r.enabled = 1
              AND r.admin_id = c.created_by
        )
    )";
}

function reports_data_usage_router_scope_sql($prefix = 'u')
{
    return "(
            {$prefix}.admin_id = ?
            OR EXISTS (
                SELECT 1 FROM tbl_routers r
                WHERE r.name COLLATE utf8mb4_unicode_ci = {$prefix}.router_name COLLATE utf8mb4_unicode_ci
                  AND r.enabled = 1
                  AND r.admin_id = ?
            )
        )";
}

function reports_data_usage_live_session_key($routerName, $username)
{
    return strtolower(trim((string) $routerName) . '|' . trim((string) $username));
}

function reports_data_usage_apply_scope(&$sql, &$params, $admin, $prefix = 'u', $usageFilter = null)
{
    $usageFilter = $usageFilter ?? trim((string) _req('usage_filter'));

    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= ' AND ' . reports_data_usage_router_scope_sql($prefix);
        $params[] = $admin['id'];
        $params[] = $admin['id'];
        if (strpos($usageFilter, 'customer:') === 0) {
            $customerId = (int) substr($usageFilter, 9);
            if ($customerId > 0) {
                $sql .= " AND c.id = ?";
                $params[] = $customerId;
            }
        }
        return;
    }

    if (strpos($usageFilter, 'admin:') === 0) {
        $adminId = (int) substr($usageFilter, 6);
        if ($adminId > 0) {
            $sql .= ' AND ' . reports_data_usage_router_scope_sql($prefix);
            $params[] = $adminId;
            $params[] = $adminId;
        }
    } elseif (strpos($usageFilter, 'customer:') === 0) {
        $customerId = (int) substr($usageFilter, 9);
        if ($customerId > 0) {
            $sql .= " AND c.id = ?";
            $params[] = $customerId;
        }
    }
}

function reports_data_usage_apply_service_filter(&$sql, &$params, $serviceType)
{
    if (empty($serviceType) || !in_array($serviceType, ['Hotspot', 'PPPoE', 'Others'], true)) {
        return;
    }
    if ($serviceType === 'Hotspot') {
        $sql .= " AND c.service_type = 'Hotspot' AND u.username COLLATE utf8mb4_unicode_ci = c.username COLLATE utf8mb4_unicode_ci";
        return;
    }
    if ($serviceType === 'PPPoE') {
        $sql .= " AND c.service_type = 'PPPoE' AND (
            u.username COLLATE utf8mb4_unicode_ci = c.pppoe_username COLLATE utf8mb4_unicode_ci
            OR (
                (c.pppoe_username IS NULL OR c.pppoe_username = '')
                AND u.username COLLATE utf8mb4_unicode_ci = c.username COLLATE utf8mb4_unicode_ci
            )
        )";
        return;
    }
    $sql .= " AND (c.id IS NULL OR c.service_type IS NULL OR c.service_type = '' OR c.service_type NOT IN ('Hotspot', 'PPPoE'))";
}

function reports_data_usage_base_filters($admin, $startDate, $endDate, $targetUsername, $routerFilter, $serviceType = '', $usageFilter = null)
{
    $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
    $sql = " FROM api_data_usage u" . reports_data_usage_customer_join() . " WHERE u.log_date >= ? AND u.log_date <= ?";
    reports_data_usage_apply_scope($sql, $params, $admin, 'u', $usageFilter);
    if (!empty($targetUsername)) {
        $sql .= " AND u.username = ?";
        $params[] = $targetUsername;
    }
    if (!empty($routerFilter)) {
        $sql .= " AND u.router_name = ?";
        $params[] = $routerFilter;
    }
    reports_data_usage_apply_service_filter($sql, $params, $serviceType);
    return [$sql, $params];
}

function reports_data_usage_router_status($admin)
{
    $routerQuery = ORM::for_table('tbl_routers')->where('enabled', 1);
    if ($admin['user_type'] != 'SuperAdmin') {
        $routerQuery->where('admin_id', $admin['id']);
    }
    $routers = $routerQuery->find_many();
    $db = ORM::get_db();
    $statusList = [];
    foreach ($routers as $router) {
        $name = $router['name'];
        $params = [$name];
        if ($admin['user_type'] != 'SuperAdmin') {
            $stmt = $db->prepare("SELECT MAX(log_date) AS last_sync, SUM(CASE WHEN status = 'Connected' THEN 1 ELSE 0 END) AS connected_rows FROM api_data_usage WHERE router_name = ? AND admin_id = ?");
            $params[] = (int) $admin['id'];
        } else {
            $stmt = $db->prepare("SELECT MAX(log_date) AS last_sync, SUM(CASE WHEN status = 'Connected' THEN 1 ELSE 0 END) AS connected_rows FROM api_data_usage WHERE router_name = ?");
        }
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $lastSync = $row['last_sync'] ?? null;
        $apiState = 'offline';
        if ($lastSync) {
            $age = time() - strtotime($lastSync);
            if ($age <= 900) {
                $apiState = 'online';
            } elseif ($age <= 3600) {
                $apiState = 'warning';
            }
        }
        $metaKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
        $metaRow = ORM::for_table('api_data_usage_meta')->where('meta_key', $metaKey)->find_one();
        $meta = $metaRow ? json_decode((string) $metaRow->meta_value, true) : null;
        if (is_array($meta) && isset($meta['ok'])) {
            $apiState = $meta['ok'] ? ($apiState === 'offline' ? 'warning' : $apiState) : 'offline';
        }
        $statusList[] = [
            'name' => $name,
            'ip' => $router['ip_address'],
            'status' => $apiState,
            'last_sync' => $lastSync ? date('d/m/Y H:i', strtotime($lastSync)) : Lang::T('Never'),
            'last_sync_raw' => $lastSync,
            'error' => (is_array($meta) && !empty($meta['error'])) ? (string) $meta['error'] : '',
        ];
    }
    return $statusList;
}

function reports_daily_staff_map()
{
    $staff_map = [];
    foreach (ORM::for_table('tbl_users')->find_array() as $ad) {
        $staff_map[$ad['username']] = $ad['fullname'];
    }
    foreach (ORM::for_table('tbl_hotspot_resellers')->find_array() as $rs) {
        $staff_map[$rs['username']] = $rs['fullname'];
    }
    return $staff_map;
}

function reports_daily_apply_filters($query, $admin, $sd, $ed, $ts, $te, $tps, $mts, $rts, $plns, $methods, $staff_map, $q = '')
{
    $query->whereRaw(
        "UNIX_TIMESTAMP(CONCAT(`tbl_transactions`.`recharged_on`,' ',`tbl_transactions`.`recharged_time`)) >= " . strtotime("$sd $ts")
    )->whereRaw(
        "UNIX_TIMESTAMP(CONCAT(`tbl_transactions`.`recharged_on`,' ',`tbl_transactions`.`recharged_time`)) <= " . strtotime("$ed $te")
    );

    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('tbl_transactions.admin_id', $admin['id']);
    }

    if (count($tps) > 0) {
        $query->where_in('tbl_transactions.type', $tps);
    }

    if (count($mts) > 0) {
        $cond = [];
        $param = [];
        foreach ($mts as $mt) {
            $cond[] = "`tbl_transactions`.`method` LIKE ?";
            $param[] = "%$mt%";
            if (isset($staff_map[$mt]) && !empty($staff_map[$mt])) {
                $cond[] = "`tbl_transactions`.`method` LIKE ?";
                $param[] = "%" . $staff_map[$mt] . "%";
            }
        }
        if (!empty($cond)) {
            $query->where_raw("(" . implode(" OR ", $cond) . ")", $param);
        }
    }

    if (count($rts) > 0) {
        $query->where_in('tbl_transactions.routers', $rts);
    }
    if (count($plns) > 0) {
        $query->where_in('tbl_transactions.plan_name', $plns);
    }

    if ($q !== '') {
        $query->where_raw(
            "(`tbl_transactions`.`username` LIKE ? OR `tbl_transactions`.`plan_name` LIKE ? OR `tbl_transactions`.`method` LIKE ? OR `tbl_customers`.`fullname` LIKE ? OR `tbl_customers`.`phonenumber` LIKE ?)",
            ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%"]
        );
    }

    return $query;
}

function reports_daily_sum_query($admin, $sd, $ed, $ts, $te, $tps, $mts, $rts, $plns, $methods, $staff_map, $q = '')
{
    $dr_query = ORM::for_table('tbl_transactions')
        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"));

    if ($admin['user_type'] != 'SuperAdmin') {
        $dr_query->where('admin_id', $admin['id']);
    }
    if (count($tps) > 0) {
        $dr_query->where_in('type', $tps);
    }
    if (count($mts) > 0) {
        $cond = [];
        $param = [];
        foreach ($mts as $mt) {
            $cond[] = "`method` LIKE ?";
            $param[] = "%$mt%";
            if (isset($staff_map[$mt]) && !empty($staff_map[$mt])) {
                $cond[] = "`method` LIKE ?";
                $param[] = "%" . $staff_map[$mt] . "%";
            }
        }
        if (!empty($cond)) {
            $dr_query->where_raw("(" . implode(" OR ", $cond) . ")", $param);
        }
    }
    if (count($rts) > 0) {
        $dr_query->where_in('routers', $rts);
    }
    if (count($plns) > 0) {
        $dr_query->where_in('plan_name', $plns);
    }
    if ($q !== '') {
        $dr_query->where_raw(
            "(`username` LIKE ? OR `plan_name` LIKE ? OR `method` LIKE ?)",
            ["%$q%", "%$q%", "%$q%"]
        );
    }

    return $dr_query;
}

function reports_data_usage_api_payload($admin)
{
    $search = _req('q');
    $routerFilter = _req('router');
    $serviceType = _req('service_type');
    $startDate = _req('start_date', date('Y-01-01'));
    $endDate = _req('end_date', date('Y-m-d'));

    if (DemoShowcase::isActive($admin)) {
        return DemoShowcase::dataUsageApiPayload($startDate, $endDate, $serviceType, $routerFilter, $search);
    }

    $db = ORM::get_db();
    $targetUsername = $search;
    if (!empty($search)) {
        $customerQuery = ORM::for_table('tbl_customers')
            ->where_raw("(`username` = ? OR `pppoe_username` = ? OR `fullname` LIKE ? OR `phonenumber` LIKE ?)", [$search, $search, "%$search%", "%$search%"]);
        if ($admin['user_type'] != 'SuperAdmin') {
            $customerQuery->where('created_by', $admin['id']);
        }
        $customer = $customerQuery->find_one();
        if ($customer) {
            $targetUsername = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
        }
    }

    [$baseFrom, $baseParams] = reports_data_usage_base_filters($admin, $startDate, $endDate, $targetUsername, $routerFilter, $serviceType);

    require_once __DIR__ . '/../cron_data_usage.php';
    $liveSessions = cron_data_usage_fetch_live_sessions($admin);

    // Summary KPIs
    $stmt = $db->prepare("SELECT COALESCE(SUM(u.download_bytes),0) AS dl, COALESCE(SUM(u.upload_bytes),0) AS ul, COUNT(DISTINCT u.username) AS unique_users" . $baseFrom);
    $stmt->execute($baseParams);
    $summaryRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalDl = (double) ($summaryRow['dl'] ?? 0);
    $totalUl = (double) ($summaryRow['ul'] ?? 0);
    $uniqueUsers = (int) ($summaryRow['unique_users'] ?? 0);
    $stmt = $db->prepare("SELECT DISTINCT u.username, u.router_name" . $baseFrom);
    $stmt->execute($baseParams);
    $activeClients = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $usageRow) {
        $liveKey = reports_data_usage_live_session_key($usageRow['router_name'] ?? '', $usageRow['username'] ?? '');
        if (isset($liveSessions[$liveKey])) {
            $activeClients++;
        }
    }

    $stmt = $db->prepare("SELECT MAX(hourly_bytes) AS peak_bytes FROM (SELECT SUM(u.total_bytes) AS hourly_bytes" . $baseFrom . " GROUP BY DATE(u.log_date), HOUR(u.log_date)) t");
    $stmt->execute($baseParams);
    $peakRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $peakBytes = (double) ($peakRow['peak_bytes'] ?? 0);
    $peakMbps = $peakBytes > 0 ? round(($peakBytes * 8) / 3600000, 2) : 0;

    $customerTotal = ORM::for_table('tbl_customers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $customerTotal->where('created_by', $admin['id']);
    }
    $totalCustomers = (int) $customerTotal->count();
    $saturation = $totalCustomers > 0 ? round(min(100, ($activeClients / $totalCustomers) * 100), 1) : 0;

    // Chart by day (remplit les jours sans trafic pour un graphique lisible)
    $stmt = $db->prepare("SELECT DATE(u.log_date) AS log_day, SUM(u.download_bytes) AS dl_bytes, SUM(u.upload_bytes) AS ul_bytes" . $baseFrom . " GROUP BY DATE(u.log_date) ORDER BY log_day ASC");
    $stmt->execute($baseParams);
    $dayMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dayMap[$row['log_day']] = [
            'dl' => round(((double) $row['dl_bytes']) / 1048576, 2),
            'ul' => round(((double) $row['ul_bytes']) / 1048576, 2),
        ];
    }
    [$chartLabels, $chartDownload, $chartUpload] = reports_data_usage_fill_chart_series($dayMap, $startDate, $endDate);

    // Top 5 users
    $stmt = $db->prepare("SELECT u.username, SUM(u.download_bytes) AS dl_bytes, SUM(u.upload_bytes) AS ul_bytes, MAX(c.fullname) AS fullname" . $baseFrom . " GROUP BY u.username ORDER BY dl_bytes DESC LIMIT 5");
    $stmt->execute($baseParams);
    $topUsers = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $topUsers[] = [
            'username' => $row['username'],
            'fullname' => $row['fullname'] ?: '—',
            'download_formatted' => reports_data_usage_format((double) $row['dl_bytes']),
            'total_formatted' => reports_data_usage_format((double) $row['dl_bytes'] + (double) $row['ul_bytes']),
        ];
    }

    // Top 5 routers
    $stmt = $db->prepare("SELECT u.router_name, SUM(u.total_bytes) AS ttl_bytes, SUM(u.download_bytes) AS dl_bytes" . $baseFrom . " GROUP BY u.router_name ORDER BY ttl_bytes DESC LIMIT 5");
    $stmt->execute($baseParams);
    $topRouters = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $topRouters[] = [
            'name' => $row['router_name'],
            'traffic_formatted' => reports_data_usage_format((double) $row['ttl_bytes']),
            'download_formatted' => reports_data_usage_format((double) $row['dl_bytes']),
        ];
    }

    // Top 5 services (Hotspot / PPPoE / plan actif)
    $stmt = $db->prepare("SELECT COALESCE(NULLIF(c.service_type, ''), 'Autre') AS service_name, SUM(u.total_bytes) AS ttl_bytes" . $baseFrom . " GROUP BY service_name ORDER BY ttl_bytes DESC LIMIT 5");
    $stmt->execute($baseParams);
    $topServices = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $topServices[] = [
            'name' => $row['service_name'],
            'traffic_formatted' => reports_data_usage_format((double) $row['ttl_bytes']),
        ];
    }

    // Consommation par client (PPPoE + Hotspot), agrégée sur la période
    $stmt = $db->prepare("SELECT u.username, u.router_name, MAX(c.fullname) AS fullname, MAX(c.phonenumber) AS phonenumber, COALESCE(NULLIF(MAX(c.service_type), ''), 'Autre') AS service_type, SUM(u.download_bytes) AS dl_bytes, SUM(u.upload_bytes) AS ul_bytes, SUM(u.total_bytes) AS ttl_bytes" . $baseFrom . " GROUP BY u.username, u.router_name ORDER BY ttl_bytes DESC LIMIT 200");
    $stmt->execute($baseParams);
    $clientsBreakdown = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $liveKey = reports_data_usage_live_session_key($row['router_name'] ?? '', $row['username'] ?? '');
        $clientsBreakdown[] = [
            'username' => $row['username'],
            'fullname' => $row['fullname'] ?: '—',
            'phonenumber' => $row['phonenumber'] ?: '',
            'service_type' => $row['service_type'],
            'router' => $row['router_name'],
            'status' => isset($liveSessions[$liveKey]) ? 'Connected' : 'Disconnected',
            'download' => reports_data_usage_format((double) $row['dl_bytes']),
            'upload' => reports_data_usage_format((double) $row['ul_bytes']),
            'total' => reports_data_usage_format((double) $row['ttl_bytes']),
            'total_bytes' => (double) $row['ttl_bytes'],
        ];
    }

    // Detail rows
    $stmt = $db->prepare("SELECT u.username, u.router_name, DATE(u.log_date) AS log_day, SUM(u.download_bytes) AS dl_bytes, SUM(u.upload_bytes) AS ul_bytes, SUM(u.total_bytes) AS ttl_bytes" . $baseFrom . " GROUP BY u.username, u.router_name, DATE(u.log_date) ORDER BY log_day DESC, u.username ASC LIMIT 500");
    $stmt->execute($baseParams);
    $formattedData = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $liveKey = reports_data_usage_live_session_key($row['router_name'] ?? '', $row['username'] ?? '');
        $formattedData[] = [
            'username' => $row['username'],
            'router' => $row['router_name'],
            'status' => isset($liveSessions[$liveKey]) ? 'Connected' : 'Disconnected',
            'date' => $row['log_day'],
            'metrics' => [
                'download' => reports_data_usage_format($row['dl_bytes']),
                'upload' => reports_data_usage_format($row['ul_bytes']),
                'total' => reports_data_usage_format($row['ttl_bytes']),
                'raw_download_mb' => round(((double) $row['dl_bytes']) / 1048576, 2),
                'raw_upload_mb' => round(((double) $row['ul_bytes']) / 1048576, 2),
            ],
        ];
    }

    return [
        'status' => 'success',
        'resolved_username' => $targetUsername,
        'summary' => [
            'download' => reports_data_usage_format($totalDl),
            'upload' => reports_data_usage_format($totalUl),
            'combined' => reports_data_usage_format($totalDl + $totalUl),
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
        'routers_status' => reports_data_usage_router_status($admin),
        'data' => $formattedData,
    ];
}

switch ($action) {
    case 'data-usage-sync':
        @set_time_limit(120);
        @ini_set('max_execution_time', '120');
        if (DemoShowcase::isActive($admin)) {
            r2(getUrl('reports/data-usage'), 'w', 'Compte vitrine démo : synchronisation MikroTik désactivée.');
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('reports/data-usage'), 'e', 'Action désactivée en mode démo.');
        }
        require_once __DIR__ . '/../cron_data_usage.php';
        $result = cron_data_usage_sync();
        $message = 'Synchronisation terminée : ' . (int) ($result['inserted'] ?? 0) . ' ligne(s) ajoutée(s).';
        if (!empty($result['errors'])) {
            $message .= ' Erreurs : ' . implode(' | ', $result['errors']);
            r2(getUrl('reports/data-usage'), 'w', $message);
        }
        r2(getUrl('reports/data-usage'), 's', $message);
        break;

    case 'data-usage-api':
        @set_time_limit(90);
        @ini_set('max_execution_time', '90');
        reports_data_usage_install();
        header('Content-Type: application/json');
        try {
            if (_get('get_top_users') == 1) {
                $payload = reports_data_usage_api_payload($admin);
                echo json_encode(['status' => 'success', 'top_users' => $payload['top_users']]);
                exit;
            }
            echo json_encode(reports_data_usage_api_payload($admin));
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;

    case 'data-usage':
        reports_data_usage_install();
        if (DemoShowcase::isActive($admin)) {
            $ui->assign('routers', DemoShowcase::uiRouters());
            $ui->assign('customers', DemoShowcase::uiCustomers());
            $ui->assign('admins', []);
        } else {
            $routerQuery = ORM::for_table('tbl_routers')->where('enabled', 1);
            if ($admin['user_type'] != 'SuperAdmin') {
                $routerQuery->where('admin_id', $admin['id']);
            }
            $admins = [];
            if ($admin['user_type'] == 'SuperAdmin') {
                $admins = ORM::for_table('tbl_users')
                    ->select('id')->select('fullname')->select('username')
                    ->where_in('user_type', ['Admin', 'SuperAdmin', 'Agent'])
                    ->order_by_asc('fullname')
                    ->find_many();
            }
            $customersQuery = ORM::for_table('tbl_customers')
                ->select('id')->select('username')->select('fullname')->select('service_type')
                ->order_by_asc('fullname');
            if ($admin['user_type'] != 'SuperAdmin') {
                $customersQuery->where('created_by', $admin['id']);
            }
            $ui->assign('routers', $routerQuery->find_many());
            $ui->assign('admins', $admins);
            $ui->assign('customers', $customersQuery->find_many());
        }
        $ui->assign('service_types', ['Hotspot', 'PPPoE', 'Others']);
        $ui->assign('_title', Lang::T('Data Usage'));
        $ui->display('admin/reports/data-usage.tpl');
        break;

    case 'ajax':
        $data = $routes['2'];
        $reset_day = $config['reset_day'];
        if (empty($reset_day)) {
            $reset_day = 1;
        }
        //first day of month
        if (date("d") >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
        }
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $tpSel = _req('tp');
        $rtSel = _req('rt');
        $mtSel = _req('mt');
        $tps = ($tpSel !== '' && $tpSel !== null) ? [$tpSel] : (!empty($_GET['tps']) ? (array) $_GET['tps'] : $types);
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $plns = !empty($_GET['plns']) ? (array) $_GET['plns'] : $plans;
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $mts = ($mtSel !== '' && $mtSel !== null) ? [$mtSel] : (!empty($_GET['mts']) ? (array) $_GET['mts'] : $methods);
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $rts = ($rtSel !== '' && $rtSel !== null) ? [$rtSel] : (!empty($_GET['rts']) ? (array) $_GET['rts'] : $routers);
        $result = [];
        switch ($data) {
            case 'type':
                foreach ($tps as $tp) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('type', $tp);
                        
                        if ($admin['user_type'] != 'SuperAdmin') {
    $query->where('admin_id', $admin['id']);
}
                        
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            $w = [];
                            $v = [];
                            foreach ($mts as $mt) {
                                $w[] ='method';
                                $v[] = "$mt - %";
                            }
                            $query->where_likes($w, $v);
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$tp ($count)";
                    }
                }
                break;
            case 'plan':
                foreach ($plns as $pln) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('plan_name', $pln);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            $w = [];
                            $v = [];
                            foreach ($mts as $mt) {
                                $w[] ='method';
                                $v[] = "$mt - %";
                            }
                            $query->where_likes($w, $v);
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$pln ($count)";
                    }
                }
                break;
            case 'method':
                foreach ($mts as $mt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where_like('method', "$mt - %");
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$mt ($count)";
                    }
                }
                break;
            case 'router':
                foreach ($rts as $rt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('routers', $rt);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$rt ($count)";
                    }
                }
                break;
            case 'revenue':
                $query = ORM::for_table('tbl_transactions')
                    ->select_expr('recharged_on', 'day')
                    ->select_expr('SUM(price)', 'revenue')
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                    ->group_by('recharged_on')
                    ->order_by_asc('recharged_on');
                if ($admin['user_type'] != 'SuperAdmin') {
                    $query->where('admin_id', $admin['id']);
                }
                if (count($tps) > 0) {
                    $query->where_in('type', $tps);
                }
                if (count($mts) > 0 && count($mts) != count($methods)) {
                    $w = [];
                    $v = [];
                    foreach ($mts as $mt) {
                        $w[] = 'method';
                        $v[] = "$mt - %";
                    }
                    $query->where_likes($w, $v);
                }
                if (count($rts) > 0) {
                    $query->where_in('routers', $rts);
                }
                if (count($plns) > 0) {
                    $query->where_in('plan_name', $plns);
                }
                $labels = [];
                $datas = [];
                foreach ($query->find_array() as $row) {
                    $labels[] = date('d M', strtotime($row['day']));
                    $datas[] = round((float) $row['revenue'], 2);
                }
                $result = ['labels' => $labels, 'data' => $datas];
                break;
            case 'line':
                $query = ORM::for_table('tbl_transactions')
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                    ->order_by_desc('id');
                if (count($tps) > 0) {
                    $query->where_in('type', $tps);
                }
                if (count($mts) > 0) {
                    if (count($mts) != count($methods)) {
                        $w = [];
                        $v = [];
                        foreach ($mts as $mt) {
                            $w[] ='method';
                            $v[] = "$mt - %";
                        }
                        $query->where_likes($w, $v);
                    }
                }
                if (count($rts) > 0) {
                    $query->where_in('routers', $rts);
                }
                if (count($plns) > 0) {
                    $query->where_in('plan_name', $plns);
                }
                $datas = $query->find_array();
                $period = new DatePeriod(
                    new DateTime($sd),
                    new DateInterval('P1D'),
                    new DateTime($ed)
                );
                $pos = 0;
                $dates = [];
                foreach ($period as $key => $value) {
                    $dates[] = $value->format('Y-m-d');
                }
                $dates = array_reverse($dates);
                $result = [];
                $temp;
                foreach ($dates as $date) {
                    $result['labels'][] = $date;
                    // type
                    foreach ($tps as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['type'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //plan
                    foreach ($plns as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['plan_name'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //method
                    foreach ($mts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && strpos($data['method'], $key) !== false) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }

                    foreach ($rts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['routers'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    $pos++;
                    if ($pos > 29) {
                        // only 30days
                        break;
                    }
                }
                foreach ($temp as $key => $value) {
                    $array = ['label' => $key];
                    $total = 0;
                    foreach ($value as $k => $v) {
                        $total += $v;
                        $array['data'][] = $v;
                    }
                    if($total>0){
                        $result['datas'][] = $array;
                    }
                }
                break;
            default:
                $result = ['labels' => [], 'datas' => []];
        }
        echo json_encode($result);
        die();
    case 'csv':
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $reset_day = $config['reset_day'] ?: 1;
        if (date('d') >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime('-1 MONTH'));
        }
        $tps = !empty($_GET['tps']) ? (array) $_GET['tps'] : $types;
        if (!empty($_GET['tp'])) {
            $tps = [$_GET['tp']];
        }
        $mts = !empty($_GET['mts']) ? (array) $_GET['mts'] : $methods;
        if (!empty($_GET['mt']) && $_GET['mt'] !== '') {
            $mts = [$_GET['mt']];
        }
        $rts = !empty($_GET['rts']) ? (array) $_GET['rts'] : $routers;
        if (!empty($_GET['rt']) && $_GET['rt'] !== '') {
            $rts = [$_GET['rt']];
        }
        $plns = !empty($_GET['plns']) ? (array) $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $q = trim((string) _req('q'));
        $staff_map = reports_daily_staff_map();
        $query = ORM::for_table('tbl_transactions')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber')
            ->left_outer_join('tbl_customers', ['tbl_transactions.username', '=', 'tbl_customers.username'])
            ->order_by_desc('tbl_transactions.id');
        reports_daily_apply_filters($query, $admin, $sd, $ed, $ts, $te, $tps, $mts, $rts, $plns, $methods, $staff_map, $q);
        $rows = $query->find_array();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            Lang::T('Username'),
            Lang::T('Full Name'),
            Lang::T('Address'),
            Lang::T('Phone Number'),
            Lang::T('Type'),
            Lang::T('Plan Name'),
            Lang::T('Plan Price'),
            Lang::T('Created On'),
            Lang::T('Expires On'),
            Lang::T('Method'),
            Lang::T('Routers'),
        ]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['username'],
                $row['fullname'] ?? '',
                $row['address'] ?? '',
                $row['phonenumber'] ?? '',
                $row['type'],
                $row['plan_name'],
                $row['price'],
                Lang::dateAndTimeFormat($row['recharged_on'], $row['recharged_time']),
                Lang::dateAndTimeFormat($row['expiration'], $row['time']),
                $row['method'],
                $row['routers'],
            ]);
        }
        fclose($out);
        exit;

    case 'by-date':
    case 'activation':
        $q = (_post('q') ? _post('q') : _get('q'));
        $keep = _post('keep');
        if (!empty($keep)) {
            ORM::raw_execute("DELETE FROM tbl_transactions WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keep DAY))");
            r2(getUrl('logs/list/'), 's', "Delete logs older than $keep days");
        }

        // মূল কুয়েরি যেখানে Join এবং Search লজিক থাকবে
        $query = ORM::for_table('tbl_transactions')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber')
            ->join('tbl_customers', array('tbl_transactions.username', '=', 'tbl_customers.username'))
            ->order_by_desc('tbl_transactions.id');
            
            if ($admin['user_type'] != 'SuperAdmin') {
    $query->where('admin_id', $admin['id']);
}

        if ($q != '') {
            // এই অংশটি পরিবর্তন করা হয়েছে যাতে একাধিক ফিল্ডে সার্চ কাজ করে
            $query->where_raw(
                "(`tbl_transactions`.`invoice` LIKE ? OR 
                  `tbl_transactions`.`username` LIKE ? OR 
                  `tbl_customers`.`fullname` LIKE ? OR 
                  `tbl_customers`.`phonenumber` LIKE ?)", 
                array("%$q%", "%$q%", "%$q%", "%$q%")
            );
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $d = Paginator::findMany($query);
        }

        $ui->assign('activation', $d);
        $ui->assign('q', $q);
        $ui->display('admin/reports/activation.tpl');
        break;

    case 'by-period':
        $ui->assign('mdate', $mdate);
        $ui->assign('mtime', $mtime);
        $ui->assign('tdate', $tdate);
        run_hook('view_reports_by_period'); #HOOK
        $ui->display('admin/reports/period.tpl');
        break;

    case 'period-view':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');
        $admin_id = _post('admin_id'); // <--- এই লাইনটি যোগ করুন

        // ডাটা লিস্ট বের করার কোয়েরি
        $d = ORM::for_table('tbl_transactions')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber')
            ->left_outer_join('tbl_customers', array('tbl_transactions.username', '=', 'tbl_customers.username'));
            
        // --- এই অংশটি পরিবর্তন করুন ---
        if ($admin['user_type'] != 'SuperAdmin') {
            $d->where('tbl_transactions.admin_id', $admin['id']);
        } else if (!empty($admin_id)) {
            // যদি সুপারএডমিন কোনো নির্দিষ্ট এডমিন সিলেক্ট করে
            $d->where('tbl_transactions.admin_id', $admin_id);
        }
        // --------------------------
            
        if ($stype != '') {
            $d->where('tbl_transactions.type', $stype);
        }

        $d->where_gte('tbl_transactions.recharged_on', $fdate);
        $d->where_lte('tbl_transactions.recharged_on', $tdate);
        $d->order_by_desc('tbl_transactions.id');
        $x = $d->find_many();

        // মোট টাকা (Total Sum) বের করার কোয়েরি
        $dr = ORM::for_table('tbl_transactions');
        
        // --- সামেশনের কোয়েরিতেও একই ফিল্টার যোগ করুন ---
        if ($admin['user_type'] != 'SuperAdmin') {
            $dr->where('admin_id', $admin['id']);
        } else if (!empty($admin_id)) {
            $dr->where('admin_id', $admin_id);
        }
        // --------------------------
        
        if ($stype != '') {
            $dr->where('type', $stype);
        }
        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
        $xy = $dr->sum('price');

        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('fdate', $fdate);
        $ui->assign('tdate', $tdate);
        $ui->assign('stype', $stype);
        run_hook('view_reports_period'); #HOOK
        $ui->display('admin/reports/period-view.tpl');
        break;

    case 'daily-report':
    default:
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');

        $reset_day = $config['reset_day'];
        if (empty($reset_day)) {
            $reset_day = 1;
        }
        if (date('d') >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime('-1 MONTH'));
        }

        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $q = trim((string) _req('q'));

        $tpSel = _req('tp');
        $rtSel = _req('rt');
        $mtSel = _req('mt');
        $tps = ($tpSel !== '' && $tpSel !== null) ? [$tpSel] : (!empty($_GET['tps']) ? (array) $_GET['tps'] : $types);
        $rts = ($rtSel !== '' && $rtSel !== null) ? [$rtSel] : (!empty($_GET['rts']) ? (array) $_GET['rts'] : $routers);
        $mts = ($mtSel !== '' && $mtSel !== null) ? [$mtSel] : (!empty($_GET['mts']) ? (array) $_GET['mts'] : $methods);
        $plns = !empty($_GET['plns']) ? (array) $_GET['plns'] : $plans;

        $urlquery = str_replace('_route=reports', '', $_SERVER['QUERY_STRING'] ?? '');
        $staff_map = reports_daily_staff_map();

        $all_staff = [];
        foreach (ORM::for_table('tbl_users')->find_array() as $ad) {
            $all_staff[] = ['username' => $ad['username'], 'fullname' => $ad['fullname'] . ' (Admin)'];
        }
        foreach (ORM::for_table('tbl_hotspot_resellers')->find_array() as $rs) {
            $all_staff[] = ['username' => $rs['username'], 'fullname' => $rs['fullname'] . ' (Reseller)'];
        }

        $query = ORM::for_table('tbl_transactions')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber')
            ->left_outer_join('tbl_customers', ['tbl_transactions.username', '=', 'tbl_customers.username'])
            ->order_by_desc('tbl_transactions.id');
        reports_daily_apply_filters($query, $admin, $sd, $ed, $ts, $te, $tps, $mts, $rts, $plns, $methods, $staff_map, $q);

        $searchParams = $q !== '' ? ['q' => $q] : [];
        $total_transactions = (int) $query->count();
        $d = Paginator::findMany($query, $searchParams, 5, $urlquery);
        $dr = (float) reports_daily_sum_query($admin, $sd, $ed, $ts, $te, $tps, $mts, $rts, $plns, $methods, $staff_map, $q)->sum('price');

        $ui->assign('_title', Lang::T('Reports_Analytics'));
        $ui->assign('all_staff', $all_staff);
        $ui->assign('methods', $methods);
        $ui->assign('types', $types);
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->assign('filter', $urlquery);
        $ui->assign('sd', $sd);
        $ui->assign('ed', $ed);
        $ui->assign('ts', $ts);
        $ui->assign('te', $te);
        $ui->assign('tp_sel', $tpSel);
        $ui->assign('rt_sel', $rtSel);
        $ui->assign('mt_sel', $mtSel);
        $ui->assign('mts', $mts);
        $ui->assign('tps', $tps);
        $ui->assign('rts', $rts);
        $ui->assign('plns', $plns);
        $ui->assign('q', $q);
        $ui->assign('d', $d);
        $ui->assign('dr', $dr);
        $ui->assign('total_transactions', $total_transactions);
        $ui->assign('mdate', $mdate);
        $ui->assign('currency', $_c['currency_code'] ?? 'XAF');
        run_hook('view_daily_reports');
        $ui->display('admin/reports/list.tpl');
        break;
}
