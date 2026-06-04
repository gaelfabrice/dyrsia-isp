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

function reports_data_usage_apply_scope(&$sql, &$params, $admin, $prefix = 'u')
{
    if ($admin['user_type'] != 'SuperAdmin') {
        $sql .= " AND {$prefix}.admin_id = ?";
        $params[] = $admin['id'];
        return;
    }
    $adminId = _req('admin_id');
    if ($adminId !== '' && $adminId !== null) {
        $sql .= " AND {$prefix}.admin_id = ?";
        $params[] = $adminId;
    }
}

switch ($action) {
    case 'data-usage-api':
        reports_data_usage_install();
        header('Content-Type: application/json');
        $db = ORM::get_db();
        if (_get('get_top_users') == 1) {
            $params = [];
            $sql = "SELECT u.username,
                           SUM(u.download_bytes) as total_dl_bytes,
                           SUM(u.upload_bytes) as total_ul_bytes,
                           MAX(u.status) as last_status,
                           MAX(u.log_date) as last_log,
                           c.fullname,
                           c.phonenumber
                    FROM api_data_usage u
                    LEFT JOIN tbl_customers c ON u.username COLLATE utf8mb4_general_ci = c.username COLLATE utf8mb4_general_ci
                    WHERE u.log_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            reports_data_usage_apply_scope($sql, $params, $admin, 'u');
            $sql .= " GROUP BY u.username, c.fullname, c.phonenumber ORDER BY total_dl_bytes DESC LIMIT 10";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $topUsers = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $totalDl = (double) $row['total_dl_bytes'];
                $topUsers[] = [
                    'username' => $row['username'],
                    'fullname' => !empty($row['fullname']) ? $row['fullname'] : 'N/A',
                    'phonenumber' => !empty($row['phonenumber']) ? $row['phonenumber'] : 'N/A',
                    'download_formatted' => reports_data_usage_format($totalDl),
                    'download_raw_mb' => round(($totalDl / 1048576), 2),
                    'status' => $row['last_status'],
                    'date' => date('h:i A', strtotime($row['last_log']))
                ];
            }
            echo json_encode(['status' => 'success', 'top_users' => $topUsers]);
            exit;
        }
        $search = _req('q');
        $routerFilter = _req('router');
        $startDate = _req('start_date', date('Y-01-01'));
        $endDate = _req('end_date', date('Y-m-d'));
        $targetUsername = $search;
        if (!empty($search)) {
            $customerQuery = ORM::for_table('tbl_customers')
                ->where_raw("(`username` = ? OR `fullname` LIKE ? OR `phonenumber` LIKE ?)", [$search, "%$search%", "%$search%"]);
            if ($admin['user_type'] != 'SuperAdmin') {
                $customerQuery->where('created_by', $admin['id']);
            }
            $customer = $customerQuery->find_one();
            if ($customer) {
                $targetUsername = $customer['username'];
            }
        }
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        $sql = "SELECT u.username,
                       DATE(u.log_date) as log_day,
                       MAX(u.status) as current_status,
                       SUM(u.download_bytes) as dl_bytes,
                       SUM(u.upload_bytes) as ul_bytes,
                       SUM(u.total_bytes) as ttl_bytes
                FROM api_data_usage u
                WHERE u.log_date >= ? AND u.log_date <= ?";
        reports_data_usage_apply_scope($sql, $params, $admin, 'u');
        if (!empty($targetUsername)) {
            $sql .= " AND u.username = ?";
            $params[] = $targetUsername;
        }
        if (!empty($routerFilter)) {
            $sql .= " AND u.router_name = ?";
            $params[] = $routerFilter;
        }
        $sql .= " GROUP BY u.username, DATE(u.log_date) ORDER BY log_day DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $formattedData = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $formattedData[] = [
                'username' => $row['username'],
                'status' => $row['current_status'],
                'date' => $row['log_day'],
                'metrics' => [
                    'download' => reports_data_usage_format($row['dl_bytes']),
                    'upload' => reports_data_usage_format($row['ul_bytes']),
                    'total' => reports_data_usage_format($row['ttl_bytes']),
                    'raw_download_mb' => round(($row['dl_bytes'] / 1048576), 2),
                    'raw_upload_mb' => round(($row['ul_bytes'] / 1048576), 2)
                ]
            ];
        }
        echo json_encode(['status' => 'success', 'resolved_username' => $targetUsername, 'data' => $formattedData]);
        exit;

    case 'data-usage':
        reports_data_usage_install();
        $routerQuery = ORM::for_table('tbl_routers')->where('enabled', 1);
        if ($admin['user_type'] != 'SuperAdmin') {
            $routerQuery->where('admin_id', $admin['id']);
        }
        $admins = [];
        if ($admin['user_type'] == 'SuperAdmin') {
            $admins = ORM::for_table('tbl_users')->select('id')->select('fullname')->select('username')->order_by_asc('fullname')->find_many();
        }
        $ui->assign('routers', $routerQuery->find_many());
        $ui->assign('admins', $admins);
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
        $tps = !empty($_GET['tps']) ? $_GET['tps'] : $types;
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $plns = !empty($_GET['plns']) ? $_GET['plns'] : $plans;
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $mts = !empty($_GET['mts']) ? $_GET['mts'] : $methods;
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $rts = !empty($_GET['rts']) ? $_GET['rts'] : $routers;
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
        if (empty($reset_day)) { $reset_day = 1; }
        
        if (date("d") >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
        }
        
        $tps = !empty($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = !empty($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = !empty($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = !empty($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $urlquery = str_replace('_route=reports', '', $_SERVER['QUERY_STRING']);

        // --- স্টাফদের ডাটা আগে লোড করছি যাতে ফিল্টারে Fullname ব্যবহার করা যায় ---
        $all_staff = [];
        $staff_map = []; // ইউজারনেম দিয়ে ফুল নেম খোঁজার জন্য
        foreach (ORM::for_table('tbl_users')->find_array() as $ad) {
            $all_staff[] = ['username' => $ad['username'], 'fullname' => $ad['fullname'] . ' (Admin)'];
            $staff_map[$ad['username']] = $ad['fullname'];
        }
        foreach (ORM::for_table('tbl_hotspot_resellers')->find_array() as $rs) {
            $all_staff[] = ['username' => $rs['username'], 'fullname' => $rs['fullname'] . ' (Reseller)'];
            $staff_map[$rs['username']] = $rs['fullname'];
        }

        // --- ডাটা লোড করার মূল কুয়েরি ---
        $query = ORM::for_table('tbl_transactions')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber')
            ->left_outer_join('tbl_customers', array('tbl_transactions.username', '=', 'tbl_customers.username'))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`tbl_transactions`.`recharged_on`,' ',`tbl_transactions`.`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`tbl_transactions`.`recharged_on`,' ',`tbl_transactions`.`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('tbl_transactions.id');
            
            if ($admin['user_type'] != 'SuperAdmin') {
    $query->where('tbl_transactions.admin_id', $admin['id']);
}

        if (count($tps) > 0) {
            $query->where_in('tbl_transactions.type', $tps);
        }
        
        // --- আপডেট করা ফিল্টার: Username এবং Fullname দুটি দিয়েই খুঁজবে ---
        if (count($mts) > 0) {
            $cond = [];
            $param = [];
            foreach ($mts as $mt) {
                // ১. প্রথমে ইউজারনেম বা পেমেন্ট মেথড দিয়ে খুঁজবে
                $cond[] = "`tbl_transactions`.`method` LIKE ?";
                $param[] = "%$mt%";
                
                // ২. যদি ড্রপডাউনে স্টাফ সিলেক্ট করা হয়, তবে তার Fullname দিয়েও খুঁজবে
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

        // ডাটা লোড
        $d = Paginator::findMany($query, [], 100, $urlquery);
        
        // --- টোটাল প্রাইস ($dr) এর হিসাব ---
        $dr_query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"));

        if (count($mts) > 0) {
            $cond_dr = []; 
            $param_dr = [];
            foreach ($mts as $mt) {
                $cond_dr[] = "`method` LIKE ?";
                $param_dr[] = "%$mt%";
                
                if (isset($staff_map[$mt]) && !empty($staff_map[$mt])) {
                    $cond_dr[] = "`method` LIKE ?";
                    $param_dr[] = "%" . $staff_map[$mt] . "%";
                }
            }
            if (!empty($cond_dr)) {
                $dr_query->where_raw("(" . implode(" OR ", $cond_dr) . ")", $param_dr);
            }
        }
        $dr = $dr_query->sum('price');

        // --- ভিউ টেমপ্লেটে ডাটা পাঠানো ---
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
        $ui->assign('mts', $mts);
        $ui->assign('tps', $tps);
        $ui->assign('rts', $rts);
        $ui->assign('plns', $plns);
        $ui->assign('d', $d);
        $ui->assign('dr', $dr);
        $ui->assign('mdate', $mdate);
        run_hook('view_daily_reports');
        $ui->display('admin/reports/list.tpl');
        break;
}
