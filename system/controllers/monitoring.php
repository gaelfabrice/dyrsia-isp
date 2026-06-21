<?php

_admin();
$ui->assign('_admin', $admin);

Tenant::moveCustomerExpiryToMonitoring();

$action = $routes['1'] ?? 'index';
$isAdmin = ($admin['user_type'] != 'SuperAdmin');
$adminId = intval($admin['id']);

if ($action === 'expiry') {
    $ui->assign('_title', Lang::T('Customer Expiry Status'));
    $ui->assign('_system_menu', 'monitoring');
    if (DemoShowcase::isActive($admin)) {
        DemoShowcase::assignMonitoringExpiry($ui);
        $ui->display('admin/monitoring_expiry.tpl');
        return;
    }
    require_once $GLOBALS['WIDGET_PATH'] . DIRECTORY_SEPARATOR . 'customer_expired.php';
    (new customer_expired())->prepareMonitoringPage();
    $ui->display('admin/monitoring_expiry.tpl');
    return;
}

function monitoring_scope_recharges($query, $admin)
{
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function monitoring_scope_customers($query, $admin)
{
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('created_by', $admin['id']);
    }
    return $query;
}

function monitoring_scope_routers($query, $admin)
{
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function monitoring_trend_pct($current, $previous)
{
    $current = (float) $current;
    $previous = (float) $previous;
    if ($previous <= 0) {
        return $current > 0 ? 100.0 : 0.0;
    }
    return round((($current - $previous) / $previous) * 100, 0);
}

function monitoring_month_series($admin, $type, $routerName = '')
{
    $db = ORM::get_db();
    $params = [$type];
    $sql = "SELECT MONTH(recharged_on) AS m, COUNT(*) AS c
            FROM tbl_user_recharges
            WHERE LOWER(type) = LOWER(?) AND recharged_on >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)";
    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= " AND admin_id = ?";
        $params[] = $admin['id'];
    }
    if ($routerName !== '') {
        $sql .= " AND routers = ?";
        $params[] = $routerName;
    }
    $sql .= " GROUP BY YEAR(recharged_on), MONTH(recharged_on) ORDER BY YEAR(recharged_on), MONTH(recharged_on)";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row['m']] = (int) $row['c'];
    }

    $labels = [];
    $data = [];
    for ($i = 11; $i >= 0; $i--) {
        $ts = strtotime("-{$i} months");
        $labels[] = date('M', $ts);
        $data[] = $map[(int) date('n', $ts)] ?? 0;
    }
    return ['labels' => $labels, 'data' => $data];
}

function monitoring_sparkline_customers($admin)
{
    $db = ORM::get_db();
    $params = [];
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
            FROM tbl_customers
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= " AND created_by = ?";
        $params[] = $admin['id'];
    }
    $sql .= " GROUP BY ym ORDER BY ym ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map(function ($r) {
        return (int) $r['c'];
    }, $rows);
    while (count($data) < 7) {
        array_unshift($data, 0);
    }
    return array_slice($data, -7);
}

function monitoring_sparkline($admin, $type, $routerName = '')
{
    $db = ORM::get_db();
    $params = [];
    $sql = "SELECT DATE_FORMAT(recharged_on, '%Y-%m') AS ym, COUNT(*) AS c
            FROM tbl_user_recharges
            WHERE recharged_on >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND LOWER(type) = LOWER(?)";
    $params[] = $type;
    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= " AND admin_id = ?";
        $params[] = $admin['id'];
    }
    if ($routerName !== '') {
        $sql .= " AND routers = ?";
        $params[] = $routerName;
    }
    $sql .= " GROUP BY ym ORDER BY ym ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map(function ($r) {
        return (int) $r['c'];
    }, $rows);
    while (count($data) < 7) {
        array_unshift($data, 0);
    }
    return array_slice($data, -7);
}

function monitoring_recent_connections($admin, $routerName = '', $limit = 8)
{
    $query = ORM::for_table('tbl_user_recharges')
        ->select_many(['username', 'type', 'status', 'recharged_on', 'recharged_time', 'routers'])
        ->order_by_desc('recharged_on')
        ->order_by_desc('recharged_time')
        ->limit($limit);
    monitoring_scope_recharges($query, $admin);
    if ($routerName !== '') {
        $query->where('routers', $routerName);
    }
    $rows = [];
    foreach ($query->find_many() as $row) {
        $type = strtolower((string) $row['type']);
        $label = $type === 'pppoe' ? 'PPPoE' : 'Hotspot';
        $status = strtolower((string) $row['status']);
        if ($status === 'on') {
            $text = "{$label} {$row['username']} connecté";
        } elseif ($status === 'off') {
            $text = "{$label} {$row['username']} déconnecté";
        } else {
            $text = "{$label} {$row['username']} — {$row['status']}";
        }
        $time = trim($row['recharged_on'] . ' ' . $row['recharged_time']);
        $rows[] = [
            'text' => $text,
            'time' => $time !== '' ? date('H:i', strtotime($time)) : '—',
            'type' => $label,
            'status' => $status,
        ];
    }
    return $rows;
}

function monitoring_top_hotspots($admin, $routerName = '', $limit = 5)
{
    $db = ORM::get_db();
    $params = [];
    $sql = "SELECT routers AS name, COUNT(DISTINCT customer_id) AS clients
            FROM tbl_user_recharges
            WHERE LOWER(type) = 'hotspot'";
    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= " AND admin_id = ?";
        $params[] = $admin['id'];
    }
    if ($routerName !== '') {
        $sql .= " AND routers = ?";
        $params[] = $routerName;
    }
    $sql .= " GROUP BY routers ORDER BY clients DESC LIMIT " . intval($limit);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function monitoring_alerts_count($admin)
{
    $query = ORM::for_table('tbl_routers')->where('enabled', 1)->where('status', 'Offline');
    monitoring_scope_routers($query, $admin);
    return (int) $query->count();
}

function monitoring_build_payload($admin, $routerName = '')
{
    $customerQuery = ORM::for_table('tbl_customers');
    monitoring_scope_customers($customerQuery, $admin);
    $c_all = (int) $customerQuery->count();

    $hotspotAllQuery = ORM::for_table('tbl_user_recharges')->where_raw("LOWER(type) = 'hotspot'")->select('customer_id')->distinct();
    monitoring_scope_recharges($hotspotAllQuery, $admin);
    if ($routerName !== '') {
        $hotspotAllQuery->where('routers', $routerName);
    }
    $h_all = count($hotspotAllQuery->find_array());

    $pppoeAllQuery = ORM::for_table('tbl_user_recharges')->where_raw("LOWER(type) = 'pppoe'")->select('customer_id')->distinct();
    monitoring_scope_recharges($pppoeAllQuery, $admin);
    if ($routerName !== '') {
        $pppoeAllQuery->where('routers', $routerName);
    }
    $p_all = count($pppoeAllQuery->find_array());

    $hTotalQuery = ORM::for_table('tbl_user_recharges')->where('type', 'Hotspot');
    monitoring_scope_recharges($hTotalQuery, $admin);
    if ($routerName !== '') {
        $hTotalQuery->where('routers', $routerName);
    }
    $h_total = (int) $hTotalQuery->count();

    $hActQuery = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'Hotspot');
    monitoring_scope_recharges($hActQuery, $admin);
    if ($routerName !== '') {
        $hActQuery->where('routers', $routerName);
    }
    $h_act = (int) $hActQuery->count();

    $pTotalQuery = ORM::for_table('tbl_user_recharges')->where('type', 'PPPoE');
    monitoring_scope_recharges($pTotalQuery, $admin);
    if ($routerName !== '') {
        $pTotalQuery->where('routers', $routerName);
    }
    $p_total = (int) $pTotalQuery->count();

    $pActQuery = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'PPPoE');
    monitoring_scope_recharges($pActQuery, $admin);
    if ($routerName !== '') {
        $pActQuery->where('routers', $routerName);
    }
    $p_act = (int) $pActQuery->count();

    $db = ORM::get_db();
    $custTrendParams = [];
    $custTrendSql = "SELECT
        SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS cur,
        SUM(CASE WHEN created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS prev
        FROM tbl_customers WHERE 1=1";
    if ($admin['user_type'] != 'SuperAdmin') {
        $custTrendSql .= " AND created_by = ?";
        $custTrendParams[] = $admin['id'];
    }
    $stmt = $db->prepare($custTrendSql);
    $stmt->execute($custTrendParams);
    $custTrend = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cur' => 0, 'prev' => 0];

    $hsTrendParams = ['hotspot'];
    $hsTrendSql = "SELECT
        SUM(CASE WHEN recharged_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS cur,
        SUM(CASE WHEN recharged_on >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            AND recharged_on < DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS prev
        FROM tbl_user_recharges WHERE LOWER(type) = LOWER(?)";
    if ($admin['user_type'] != 'SuperAdmin') {
        $hsTrendSql .= " AND admin_id = ?";
        $hsTrendParams[] = $admin['id'];
    }
    if ($routerName !== '') {
        $hsTrendSql .= " AND routers = ?";
        $hsTrendParams[] = $routerName;
    }
    $stmt = $db->prepare($hsTrendSql);
    $stmt->execute($hsTrendParams);
    $hsTrend = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cur' => 0, 'prev' => 0];

    $ppTrendParams = $hsTrendParams;
    $ppTrendParams[0] = 'pppoe';
    $stmt = $db->prepare($hsTrendSql);
    $stmt->execute($ppTrendParams);
    $ppTrend = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cur' => 0, 'prev' => 0];

    $hotspotMonth = monitoring_month_series($admin, 'hotspot', $routerName);
    $pppoeMonth = monitoring_month_series($admin, 'pppoe', $routerName);
    $hTotalVal = max($h_total, $h_act);
    $pTotalVal = max($p_total, $p_act);

    return [
        'c_all' => $c_all,
        'h_all' => $h_all,
        'p_all' => $p_all,
        'h_act' => $h_act,
        'h_total' => $hTotalVal,
        'h_off' => max(0, $hTotalVal - $h_act),
        'h_pct' => $hTotalVal > 0 ? (int) round(($h_act / $hTotalVal) * 100) : 0,
        'p_act' => $p_act,
        'p_total' => $pTotalVal,
        'p_off' => max(0, $pTotalVal - $p_act),
        'p_pct' => $pTotalVal > 0 ? (int) round(($p_act / $pTotalVal) * 100) : 0,
        'alerts' => monitoring_alerts_count($admin),
        'trends' => [
            'customers' => monitoring_trend_pct($custTrend['cur'], $custTrend['prev']),
            'hotspot' => monitoring_trend_pct($hsTrend['cur'], $hsTrend['prev']),
            'pppoe' => monitoring_trend_pct($ppTrend['cur'], $ppTrend['prev']),
        ],
        'sparklines' => [
            'customers' => monitoring_sparkline_customers($admin),
            'hotspot' => monitoring_sparkline($admin, 'hotspot', $routerName),
            'pppoe' => monitoring_sparkline($admin, 'pppoe', $routerName),
        ],
        'chart' => [
            'labels' => $hotspotMonth['labels'],
            'hotspot' => $hotspotMonth['data'],
            'pppoe' => $pppoeMonth['data'],
        ],
        'recent' => monitoring_recent_connections($admin, $routerName),
        'top_hotspots' => monitoring_top_hotspots($admin, $routerName),
    ];
}

function monitoring_demo_payload()
{
    return [
        'c_all' => 2,
        'h_all' => 5,
        'p_all' => 0,
        'h_act' => 5,
        'h_total' => 8,
        'h_off' => 3,
        'p_act' => 0,
        'p_total' => 4,
        'p_off' => 4,
        'h_pct' => 62,
        'p_pct' => 0,
        'alerts' => 0,
        'trends' => ['customers' => 0, 'hotspot' => 25, 'pppoe' => 0],
        'sparklines' => [
            'customers' => [1, 1, 1, 2, 2, 2, 2],
            'hotspot' => [2, 3, 3, 4, 4, 5, 5],
            'pppoe' => [0, 0, 0, 0, 0, 0, 0],
        ],
        'chart' => [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            'hotspot' => [0, 1, 1, 2, 3, 4, 5, 6, 7, 8, 9, 7],
            'pppoe' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        ],
        'recent' => [
            ['text' => 'Hotspot #12 connecté', 'time' => '14:32', 'type' => 'Hotspot', 'status' => 'on'],
            ['text' => 'PPPoE #04 tentative échouée', 'time' => '14:10', 'type' => 'PPPoE', 'status' => 'off'],
            ['text' => 'Hotspot #07 déconnecté', 'time' => '13:55', 'type' => 'Hotspot', 'status' => 'off'],
            ['text' => 'Hotspot #03 connecté', 'time' => '13:40', 'type' => 'Hotspot', 'status' => 'on'],
        ],
        'top_hotspots' => [
            ['name' => 'Salle A', 'clients' => 12],
            ['name' => 'Bibliothèque', 'clients' => 8],
            ['name' => 'Hall principal', 'clients' => 5],
        ],
    ];
}

$ui->assign('_title', Lang::T('Monitoring'));
$ui->assign('_system_menu', 'monitoring');

$routerFilter = trim((string) _get('router', ''));
$routerQuery = ORM::for_table('tbl_routers')->where('enabled', 1)->order_by_asc('name');
monitoring_scope_routers($routerQuery, $admin);
$routers = $routerQuery->find_many();

if (DemoShowcase::isActive($admin)) {
    $mon = DemoShowcase::monitoringPayload();
    $ui->assign('routers', DemoShowcase::uiRouters());
} else {
    $mon = monitoring_build_payload($admin, $routerFilter);
    $ui->assign('routers', $routers);
}
$ui->assign('mon', $mon);
$ui->assign('router_filter', $routerFilter);
$ui->display('admin/monitoring.tpl');
