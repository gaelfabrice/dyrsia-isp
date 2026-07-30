<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

/**
 * used for ajax
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

function autoload_scoped_router_query($admin)
{
    return AdminScope::applyRoutersQuery(ORM::for_table('tbl_routers'), $admin);
}

function autoload_scoped_customer_query($admin)
{
    $query = ORM::for_table('tbl_customers');
    if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
        $query->where('created_by', (int) ($admin['id'] ?? 0));
    }

    return $query;
}

switch ($action) {
    case 'router-pools':
        header('Content-Type: application/json; charset=utf-8');
        $router = trim((string) (_get('router') ?: _post('router')));
        if ($router === '') {
            echo json_encode(['ok' => false, 'message' => Lang::T('Select Routers'), 'pools' => []]);
            die();
        }
        if (class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin)) {
            echo json_encode([
                'ok' => false,
                'message' => Lang::T('Demo mode — router sync is disabled'),
                'pools' => Mikrotik::fetchRouterIpPools($router, $admin),
            ]);
            die();
        }
        try {
            $pools = Mikrotik::fetchRouterIpPools($router, $admin);
            echo json_encode(['ok' => true, 'pools' => $pools, 'router' => $router]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage(), 'pools' => []]);
        }
        die();
    case 'pool':
        $routers = _get('routers');
        if (empty($routers)) {
            $d = ORM::for_table('tbl_pool')->find_many();
        } else {
            $d = ORM::for_table('tbl_pool')->where('routers', $routers)->find_many();
        }
        $ui->assign('routers', $routers);
        $ui->assign('d', $d);
        $ui->display('admin/autoload/pool.tpl');
        break;
    case 'bw_name':
        $bw = ORM::for_table('tbl_bandwidth')->select("name_bw")->find_one($routes['2']);
        echo $bw['name_bw'];
        die();
    case 'balance':
        $balance = ORM::for_table('tbl_customers')->select("balance")->find_one($routes['2'])['balance'];
        if ($routes['3'] == '1') {
            echo Lang::moneyFormat($balance);
        } else {
            echo $balance;
        }
        die();
    case 'server':
        $d = autoload_scoped_router_query($admin)->where('enabled', '1')->find_many();
        $jenis = trim((string) (_post('jenis') ?: ''));
        $showRadius = !empty($config['radius_enable']);
        if ($showRadius) {
            $radiusPlans = ORM::for_table('tbl_plans')->where('is_radius', 1);
            if ($jenis !== '') {
                $radiusPlans->where('type', $jenis);
            }
            AdminScope::applyPlansQuery($radiusPlans, $admin);
            $showRadius = (int) $radiusPlans->count() > 0;
        }
        $ui->assign('d', $d);
        $ui->assign('show_radius', $showRadius);

        $ui->display('admin/autoload/server.tpl');
        break;
    case 'pppoe_ip_used':
        if (!empty(_get('ip'))) {
            $ipQuery = ORM::for_table('tbl_customers')
                ->select("username")
                ->where_not_equal('id', _get('id'))
                ->where("pppoe_ip", _get('ip'));
            if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
                $ipQuery->where('created_by', (int) ($admin['id'] ?? 0));
            }
            $cs = $ipQuery->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("IP has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'pppoe_username_used':
        if (!empty(_get('u'))) {
            $login = trim((string) _get('u'));
            $uQuery = ORM::for_table('tbl_customers')
                ->select('username')
                ->where_not_equal('id', (int) _get('id'))
                ->where_raw('(username = ? OR pppoe_username = ?)', [$login, $login]);
            if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
                $uQuery->where('created_by', (int) ($admin['id'] ?? 0));
            }
            $cs = $uQuery->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("Username has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'plan':
        $server = _post('server');
        $jenis = _post('jenis');
        $d = [];
        if ($server !== '') {
            $planQuery = ORM::for_table('tbl_plans')->where('type', $jenis);
            AdminScope::applyPlansQuery($planQuery, $admin);
            if ($server === 'radius') {
                $d = $planQuery->where('is_radius', 1)->find_many();
            } else {
                $d = $planQuery->where('routers', $server)->where('is_radius', 0)->find_many();
            }
        }
        $ui->assign('d', $d);

        $ui->display('admin/autoload/plan.tpl');
        break;
    case 'customer_is_active':
        if ($config['check_customer_online'] == 'yes') {
            $c = ORM::for_table('tbl_customers')->where('username', $routes['2'])->find_one();
            $p = ORM::for_table('tbl_plans')->find_one($routes['3']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                        ini_set('default_socket_timeout', 5);
                        if ((new $p['device'])->online_customer($c, $p['routers'])) {
                            echo '<span style="color: green;" title="online">&bull;</span>';
                        }else{
                            echo '<span style="color: yellow;" title="offline">&bull;</span>';
                        }
                    } catch (Exception $e) {
                        echo '<span style="color: red;" title="'.$e->getMessage().'">&bull;</span>';
                    }
                }
            }
        }
        break;
    case 'plan_is_active':
        $ds = ORM::for_table('tbl_user_recharges')->where('customer_id', $routes['2'])->find_array();
        if ($ds) {
            $ps = [];
            $c = ORM::for_table('tbl_customers')->find_one($routes['2']);
            foreach ($ds as $d) {
                if ($d['status'] == 'on') {
                    if ($config['check_customer_online'] == 'yes') {
                        $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
                        $dvc = Package::getDevice($p);
                        $status = "";
                        if ($_app_stage != 'Demo') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                try {
                                    //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                                    ini_set('default_socket_timeout', 5);
                                    if ((new $p['device'])->online_customer($c, $p['routers'])) {
                                        $status = '<span style="color: green;" title="online">&bull;</span>';
                                    }else{
                                        $status = '<span style="color: yellow;" title="offline">&bull;</span>';
                                    }
                                } catch (Exception $e) {
                                    $status = '<span style="color: red;" title="'.$e->getMessage().'">&bull;</span>';
                                }
                            }
                        }
                    }
                    $ps[] = ('<span class="label label-primary m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . ' ' . $status . '</span>');
                } else {
                    $ps[] = ('<span class="label label-danger m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . '</span>');
                }
            }
            echo implode("<br>", $ps);
        } else {
            die('');
        }
        break;
    case 'customer_select2':
        $s = trim((string) (_get('s') ?: ''));
        $query = autoload_scoped_customer_query($admin);
        if ($s !== '') {
            $like = '%' . $s . '%';
            $query->where_raw(
                '(`username` LIKE ? OR `fullname` LIKE ? OR `phonenumber` LIKE ? OR `email` LIKE ?)',
                [$like, $like, $like, $like]
            );
        }
        $c = $query->limit(30)->find_many();
        header('Content-Type: application/json; charset=utf-8');
        $json = [];
        foreach ($c as $cust) {
            $json[] = [
                'id' => (int) $cust['id'],
                'text' => $cust['username'] . ' - ' . $cust['fullname'] . ' - ' . $cust['email'],
            ];
        }
        echo json_encode(['results' => $json]);
        die();
    default:
        $ui->display('admin/404.tpl');
}
