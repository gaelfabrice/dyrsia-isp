<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

function customersRowForUi($customer)
{
    if (!$customer) {
        return $customer;
    }
    $row = is_array($customer) ? $customer : (method_exists($customer, 'as_array') ? $customer->as_array() : (array) $customer);
    $row['network_password'] = Password::networkCleartext($row);

    return $row;
}

/**
 * Recharge active (ou dernière) : routeur + expiration.
 *
 * @param array<int, int|string> $customerIds
 * @return array<int, array{router_name: string, expiration: string, time: string}>
 */
function customersRechargeSummaryByIds(array $customerIds)
{
    $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));
    if ($customerIds === []) {
        return [];
    }

    $rows = ORM::for_table('tbl_user_recharges')
        ->where_in('customer_id', $customerIds)
        ->order_by_desc('id')
        ->find_many();

    $active = [];
    $latest = [];
    foreach ($rows as $row) {
        $cid = (int) $row->customer_id;
        $summary = [
            'router_name' => trim((string) $row->routers),
            'expiration' => trim((string) ($row->expiration ?? '')),
            'time' => trim((string) ($row->time ?? '')),
        ];

        if (!isset($latest[$cid])) {
            $latest[$cid] = $summary;
        }
        if ((string) $row->status === 'on' && !isset($active[$cid])) {
            $active[$cid] = $summary;
        }
    }

    $map = [];
    foreach ($customerIds as $cid) {
        $picked = $active[$cid] ?? $latest[$cid] ?? null;
        $map[$cid] = $picked ?? [
            'router_name' => '',
            'expiration' => '',
            'time' => '',
        ];
    }

    return $map;
}

function customersFindScoped($id, $admin)
{
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    if (($admin['user_type'] ?? '') === 'SuperAdmin') {
        return ORM::for_table('tbl_customers')->find_one($id);
    }

    return ORM::for_table('tbl_customers')
        ->where('id', $id)
        ->where('created_by', $admin['id'])
        ->find_one();
}

/** Périmètre propriétaire pour unicité login / PPPoE (multi-admin). */
function customersTenantOwnerId($admin, $customerRow = null)
{
    if ($customerRow !== null) {
        return (int) ($customerRow['created_by'] ?? $customerRow->created_by ?? 0);
    }

    return (int) ($admin['id'] ?? 0);
}

/**
 * Client existant (même admin) utilisant ce login comme username ou pppoe_username.
 *
 * @param array|object|null $customerRow client en cours d’édition (pour owner id)
 */
function customersFindLoginConflict($login, $admin, $excludeCustomerId = 0, $customerRow = null)
{
    $login = trim((string) $login);
    if ($login === '') {
        return null;
    }
    $ownerId = customersTenantOwnerId($admin, $customerRow);
    if ($ownerId <= 0) {
        return null;
    }

    $query = ORM::for_table('tbl_customers')
        ->where('created_by', $ownerId)
        ->where_raw('(username = ? OR pppoe_username = ?)', [$login, $login]);
    if ($excludeCustomerId > 0) {
        $query->where_not_equal('id', (int) $excludeCustomerId);
    }

    return $query->find_one();
}

/** @return true|string true on success, error message otherwise */
function customersDeleteOne($id, $admin)
{
    global $_app_stage;

    if (!in_array($admin['user_type'] ?? '', ['SuperAdmin', 'Admin'], true)) {
        return Lang::T('You do not have permission to access this page');
    }

    $c = customersFindScoped($id, $admin);
    if (!$c) {
        return Lang::T('Data Not Found');
    }

    run_hook('delete_customer');

    ORM::for_table('tbl_customers_fields')->where('customer_id', $id)->delete_many();

    $turs = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', $id)
        ->find_many();
    if (count($turs) === 0) {
        $turs = ORM::for_table('tbl_user_recharges')
            ->where('username', $c['username'])
            ->find_many();
    }

    foreach ($turs as $tur) {
        $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
        $routerName = trim((string) ($tur['routers'] ?? ''));
        $rechargeLogin = trim((string) ($tur['username'] ?? ''));
        if ($rechargeLogin === '') {
            $rechargeLogin = trim((string) ($c['username'] ?? ''));
        }
        if ($p && $_app_stage != 'demo') {
            $dvc = Package::getDevice($p);
            if (file_exists($dvc)) {
                require_once $dvc;
                try {
                    [$customerRow, $planRow] = Package::deviceSyncRows(
                        $p,
                        $routerName !== '' ? $routerName : (string) ($p['routers'] ?? ''),
                        $c->as_array(),
                        $rechargeLogin
                    );
                    // Force hard cut (no expired-plan swap): kill session now.
                    $planRow['plan_expired'] = 0;
                    $deviceClass = Package::resolveDeviceClass($planRow);
                    if ($deviceClass !== '' && class_exists($deviceClass)) {
                        $device = new $deviceClass();
                        if (method_exists($device, 'remove_customer')) {
                            $device->remove_customer($customerRow, $planRow);
                        }
                        if (method_exists($device, 'disconnect_customer')) {
                            $device->disconnect_customer($customerRow, $planRow['routers'] ?? $routerName);
                        }
                    }
                    if (class_exists('Mikrotik')
                        && strtolower(trim((string) ($planRow['type'] ?? $tur['type'] ?? ''))) === 'hotspot'
                        && $rechargeLogin !== '') {
                        Mikrotik::disconnectHotspotUserOnRouter(
                            trim((string) ($planRow['routers'] ?? $routerName)),
                            $rechargeLogin
                        );
                    }
                } catch (Throwable $e) {
                    _log('[Customer Delete] Router remove u' . $rechargeLogin . ': ' . $e->getMessage(), 'Error');
                }
            }
        } elseif ($rechargeLogin !== '' && class_exists('Mikrotik') && $_app_stage != 'demo') {
            // No plan row: still try to kick hotspot session by recharge login.
            try {
                Mikrotik::disconnectHotspotUserOnRouter($routerName, $rechargeLogin);
            } catch (Throwable $e) {
                _log('[Customer Delete] Fallback disconnect u' . $rechargeLogin . ': ' . $e->getMessage(), 'Error');
            }
        }
        try {
            $tur->status = 'off';
            $tur->save();
            $tur->delete();
        } catch (Throwable $e) {
            _log('[Customer Delete] recharge row: ' . $e->getMessage(), 'Error');
        }
    }

    // Final kick by customer usernames (covers orphan sessions without recharge rows).
    if ($_app_stage != 'demo' && class_exists('Mikrotik')) {
        foreach (array_filter([
            trim((string) ($c['username'] ?? '')),
            trim((string) ($c['pppoe_username'] ?? '')),
        ]) as $login) {
            try {
                Mikrotik::disconnectHotspotUserOnRouter('', $login);
            } catch (Throwable $e) {
            }
        }
    }

    try {
        $c->delete();

        return true;
    } catch (Throwable $e) {
        _log('[Customer Delete] ID ' . $id . ': ' . $e->getMessage(), 'Error');

        return Lang::T('Failed to delete customer') . ': ' . $e->getMessage();
    }
}

_admin();
$ui->assign('_title', Lang::T('Customer'));
$ui->assign('_system_menu', 'customers');

$action = $routes['1'] ?? '';
$ui->assign('_admin', $admin);

if ($action == 'disable') {

    $id_customer = $routes['2'];

    $c = User::_info($id_customer);
    if ($admin['user_type'] != 'SuperAdmin' && $c['created_by'] != $admin['id']) {
    r2(getUrl('customers/list'), 'e', 'Access Denied');
}

    if (!$c) {
        r2(getUrl('customers/list'), 'e', 'Customer not found');
    }

    // সব active plan বের করো
    $bs = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', $id_customer)
        ->where('status', 'on')
        ->findMany();

    if ($bs) {

        foreach ($bs as $b) {

            $p = ORM::for_table('tbl_plans')
                ->where('id', $b['plan_id'])
                ->find_one();

            if ($p) {

                $dvc = Package::getDevice($p);

                if ($_app_stage != 'demo' && file_exists($dvc)) {

                    require_once $dvc;

                    // 🔥 MAIN: REMOVE USER FROM MIKROTIK
                    (new $p['device'])->remove_customer($c, $p);
                }

                // শুধু status change (date change না)
                $b->status = 'off';
                $b->save();
            }
        }

        // customer status
        $cu = ORM::for_table('tbl_customers')->find_one($id_customer);
        $cu->status = 'Disabled';
        $cu->save();

        r2(getUrl('customers/view/') . $id_customer, 's', 'User Disabled');
    }

    r2(getUrl('customers/view/') . $id_customer, 'e', 'No Active Plan Found');
}

/* =========================
   ENABLE USER + MIKROTIK SYNC
========================= */
elseif ($action == 'enable') {

    $id_customer = $routes['2'];

    $c = User::_info($id_customer);
    if ($admin['user_type'] != 'SuperAdmin' && $c['created_by'] != $admin['id']) {
    r2(getUrl('customers/list'), 'e', 'Access Denied');
}

    if (!$c) {
        r2(getUrl('customers/list'), 'e', 'Customer not found');
    }

    $bs = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', $id_customer)
        ->findMany();

    if ($bs) {

        foreach ($bs as $b) {

            $p = ORM::for_table('tbl_plans')
                ->where('id', $b['plan_id'])
                ->find_one();

            if ($p) {

                $dvc = Package::getDevice($p);

                if ($_app_stage != 'demo' && file_exists($dvc)) {

                    require_once $dvc;

                    $device = new $p['device'];

                    // 🔥 FIX 1: REMOVE ACTIVE SESSION FIRST
                    if (method_exists($device, 'remove_customer')) {
                        $device->remove_customer($c, $p);
                    }

                    // 🔥 FIX 2: FORCE DISCONNECT (if supported)
                    if (method_exists($device, 'disconnect')) {
                        $device->disconnect($c, $p);
                    }

                    // 🔥 FIX 3: ADD AGAIN (fresh login required)
                    $device->add_customer($c, $p);
                }

                $b->status = 'on';
                $b->save();
            }
        }

        $cu = ORM::for_table('tbl_customers')->find_one($id_customer);
        $cu->status = 'Active';
        $cu->save();

        r2(getUrl('customers/view/') . $id_customer, 's', 'User Enabled');
    }

    r2(getUrl('customers/view/') . $id_customer, 'e', 'No Plan Found');
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

switch ($action) {
    case 'csv':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        $cs = ORM::for_table('tbl_customers')
            ->select('tbl_customers.id', 'id')
            ->select('tbl_customers.username', 'username')
            ->select('fullname')
            ->select('address')
            ->select('phonenumber')
            ->select('email')
            ->select('balance')
            ->select('service_type')
            ->order_by_asc('tbl_customers.id')
            ->find_array();

        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="phpwifizones_customers_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');

        $headers = [
            'id',
            'username',
            'fullname',
            'address',
            'phonenumber',
            'email',
            'balance',
            'service_type',
        ];

        if (!$h) {
            echo '"' . implode('","', $headers) . "\"\n";
            $h = true;
        }

        foreach ($cs as $c) {
            $row = [
                $c['id'],
                $c['username'],
                $c['fullname'],
                $c['address'],
                $c['phonenumber'],
                $c['email'],
                $c['balance'],
                $c['service_type'],
            ];
            echo '"' . implode('","', $row) . "\"\n";
        }
        break;
        //case csv-prepaid can be moved later to (plan.php)  php file dealing with prepaid users
    case 'csv-prepaid':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $cs = ORM::for_table('tbl_customers')
            ->select('tbl_customers.id', 'id')
            ->select('tbl_customers.username', 'username')
            ->select('fullname')
            ->select('address')
            ->select('phonenumber')
            ->select('email')
            ->select('balance')
            ->select('service_type')
            ->select('namebp')
            ->select('routers')
            ->select('status')
            ->select('method', 'Payment')
            ->left_outer_join('tbl_user_recharges', array('tbl_customers.id', '=', 'tbl_user_recharges.customer_id'))
            ->order_by_asc('tbl_customers.id')
            ->find_array();

        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="phpwifizones_prepaid_users' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');

        $headers = [
            'id',
            'username',
            'fullname',
            'address',
            'phonenumber',
            'email',
            'balance',
            'service_type',
            'namebp',
            'routers',
            'status',
            'Payment'
        ];

        if (!$h) {
            echo '"' . implode('","', $headers) . "\"\n";
            $h = true;
        }

        foreach ($cs as $c) {
            $row = [
                $c['id'],
                $c['username'],
                $c['fullname'],
                $c['address'],
                $c['phonenumber'],
                $c['email'],
                $c['balance'],
                $c['service_type'],
                $c['namebp'],
                $c['routers'],
                $c['status'],
                $c['Payment']
            ];
            echo '"' . implode('","', $row) . "\"\n";
        }
        break;
    case 'add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xheader', $leafletpickerHeader);
        $routersQuery = ORM::for_table('tbl_routers')->where('enabled', '1')->order_by_asc('name');
        AdminScope::applyRoutersQuery($routersQuery, $admin);
        $ui->assign('r', $routersQuery->find_many());
        $plansQuery = ORM::for_table('tbl_plans')
            ->where('enabled', 1)
            ->where_in('type', ['Hotspot', 'PPPOE', 'VPN'])
            ->order_by_asc('type')
            ->order_by_asc('name_plan');
        AdminScope::applyPlansQuery($plansQuery, $admin);
        $ui->assign('plans', $plansQuery->find_many());
        run_hook('view_add_customer'); #HOOK
        $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
        $ui->display('admin/customers/add.tpl');
        break;
    case 'recharge':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = $routes['2'];
        $plan_id = $routes['3'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('plan_id', $plan_id)->find_one();
        if ($b) {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            $plan = ORM::for_table('tbl_plans')->find_one($b['plan_id']);
			$add_inv = User::getAttribute("Invoice", $id_customer);
			if (!empty($add_inv)) {
				$plan['price'] = $add_inv;
			}
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : null;
            if ($tax_rate_setting === 'custom') {
                $tax_rate = $custom_tax_rate;
            } else {
                $tax_rate = $tax_rate_setting;
            }
            if ($tax_enable === 'yes') {
                $tax = Package::tax($plan['price'], $tax_rate);
            } else {
                $tax = 0;
            }
            list($bills, $add_cost) = User::getBills($id_customer);
            $total_cost = $plan['price'] + $add_cost + $tax;
            $planType = strtoupper((string) ($plan['type'] ?? ''));
            $using = ($planType === 'PPPOE')
                ? PlanRechargePayment::defaultPppoeUsingForAdmin($admin['user_type'])
                : PlanRechargePayment::METHOD_CASH;

            if ($using === 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < ($plan['price'] + $add_cost + $tax)) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            if ($using === PlanRechargePayment::METHOD_CASH) {
                $gateway = 'Cash';
            }
            if (PlanRechargePayment::isMobileMoneyMethod($using)) {
                if (!MobileMoneyGateway::isConfigured()) {
                    r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Payment gateway not configured. Please contact admin'));
                }
                $gateway = PlanRechargePayment::gatewayLabel();
            }
            $abills = User::getAttributes("Bill");
            if ($tax_enable === 'yes') {
                $ui->assign('tax', $tax);
            }
            PlanRechargePayment::assignRechargeUi($ui, $admin, $planType);
            $ui->assign('abills', $abills);
            $ui->assign('bills', $bills);
            $ui->assign('add_cost', $add_cost);
            $ui->assign('cust', $cust);
            $ui->assign('gateway', $gateway);
            $ui->assign('channel', $channel);
            $ui->assign('server', $b['routers']);
            $ui->assign('plan', $plan);
			$ui->assign('add_inv', $add_inv);
            $ui->assign('using', $using);
            $ui->assign('recharge_total', PlanRechargePayment::isMobileMoneyMethod($using) ? $total_cost : ($using === 'zero' ? 0 : $total_cost));
            $ui->assign('is_mobile_money_recharge', PlanRechargePayment::isMobileMoneyMethod($using) && $planType === 'PPPOE');

            if (!PlanRechargePayment::isMobileMoneyMethod($using) && $admin['user_type'] != 'SuperAdmin') {
    $admin_wallet = ORM::for_table('admin_wallet')->where('admin_id', $admin['id'])->find_one();
    if ($admin_wallet) {
        if ($admin_wallet->balance < $plan['price']) {
            r2(getUrl('customers/view/') . $id_customer, 'e', 'Admin Wallet Insufficient Balance. Current: ' . $admin_wallet->balance);
        }
    } else {
        r2(getUrl('customers/view/') . $id_customer, 'e', 'Admin Wallet not found!');
    }
}
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/plan/recharge-confirm.tpl');
        } else {
            r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        }
        break;
    case 'deactivate':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = $routes['2'];
        $plan_id = $routes['3'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('plan_id', $plan_id)->find_one();
        if ($b) {
            $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
            if ($p) {
                $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
                $c = User::_info($id_customer);
                $dvc = Package::getDevice($p);
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->remove_customer($c, $p);
                    } else {
                        throw new Exception(Lang::T("Devices Not Found"));
                    }
                }
                $b->status = 'off';
                $b->expiration = date('Y-m-d');
                $b->time = date('H:i:s');
                $b->save();
                _log('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for ' . $b['username'], 'User', $b['customer_id']);
                Message::sendTelegram('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for u' . $b['username']);
                r2(getUrl('customers/view/') . $id_customer, 's', 'Success deactivate customer to Mikrotik');
            }
        }
        r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'sync':
        $id_customer = $routes['2'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $bs = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('status', 'on')->findMany();
        if ($bs) {
            $routers = [];
            $errors = [];
            foreach ($bs as $b) {
                $c = ORM::for_table('tbl_customers')->find_one($id_customer);
                $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
                if ($p) {
                    if ($_app_stage != 'demo' && $_app_stage != 'Demo') {
                        if (trim((string) ($p['type'] ?? '')) === 'Hotspot' && class_exists('HotspotCustomer')) {
                            if (!HotspotCustomer::pushActiveRechargeToMikrotikWithRetry((int) $c->id, (string) $b['routers'], (int) $b['plan_id'], 3)) {
                                $errors[] = $b['routers'] . ': ' . (HotspotCustomer::$lastMikrotikSyncError ?: Package::$lastDeviceSyncError ?: 'sync failed');
                            } else {
                                $routers[] = $b['routers'];
                            }
                            continue;
                        }
                        if (Package::syncDeviceRecharge($c, $p, $b)) {
                            $routers[] = $b['routers'];
                        } else {
                            $errors[] = $b['routers'] . ': ' . (Package::$lastDeviceSyncError ?: 'sync failed');
                        }
                    } else {
                        $routers[] = $b['routers'];
                    }
                }
            }
            if ($errors && $routers) {
                r2(getUrl('customers/view/') . $id_customer, 'w', 'Sync partiel: ' . implode(', ', $routers) . '. Erreurs: ' . implode(' | ', $errors));
            }
            if ($errors) {
                r2(getUrl('customers/view/') . $id_customer, 'e', implode(' | ', $errors));
            }
            r2(getUrl('customers/view/') . $id_customer, 's', 'Sync success to ' . implode(", ", $routers));
        }
        r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'login':
        if ($admin['user_type'] !== 'SuperAdmin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = (int) ($routes['2'] ?? 0);
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        try {
            Impersonate::startAsCustomer($admin, $id);
            _alert(
                Lang::T('You are logged in as this customer') . '. ' . Lang::T('Use Exit impersonation when finished.'),
                'info',
                'home',
                10
            );
        } catch (RuntimeException $e) {
            _alert($e->getMessage(), 'danger', 'customers/view/' . $id);
        }
        break;
    case 'viewu':
        $customer = ORM::for_table('tbl_customers')->where('username', $routes['2'])->find_one();
    case 'view':
        $id = $routes['2'];
        run_hook('view_customer'); #HOOK
        if ($admin['user_type'] == 'SuperAdmin') {

    $customer = ORM::for_table('tbl_customers')
        ->find_one($id);

} else {

    $customer = ORM::for_table('tbl_customers')
        ->where('id', $id)
        ->where('created_by', $admin['id'])
        ->find_one();
}
        if ($customer) {
            // Fetch the Customers Attributes values from the tbl_customer_custom_fields table
            $customFields = ORM::for_table('tbl_customers_fields')
                ->where('customer_id', $customer['id'])
                ->find_many();
            $v = $routes['3'];
            if (empty($v)) {
                $v = 'activation';
            }
            switch ($v) {
                case 'order':
                    $v = 'order';
                    $query = ORM::for_table('tbl_payment_gateway')->where('user_id', $customer['id'])->order_by_desc('id');
                    $order = Paginator::findMany($query);

                    if (empty($order) || $order < 5) {
                        $query = ORM::for_table('tbl_payment_gateway')->where('username', $customer['username'])->order_by_desc('id');
                        $order = Paginator::findMany($query);
                    }

                    $ui->assign('order', $order);
                    break;
                case 'activation':
                    $query = ORM::for_table('tbl_transactions')->where('user_id', $customer['id'])->order_by_desc('id');
                    $activation = Paginator::findMany($query);

                    if (empty($activation) || $activation < 5) {
                        $query = ORM::for_table('tbl_transactions')->where('username', $customer['username'])->order_by_desc('id');
                        $activation = Paginator::findMany($query);
                    }

                    $ui->assign('activation', $activation);
                    break;
            }
            $ui->assign('packages', User::_billing($customer['id']));
            $ui->assign('v', $v);
            $ui->assign('d', customersRowForUi($customer));
            $ui->assign('customFields', $customFields);
            $ui->assign('xheader', $leafletpickerHeader);
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/customers/view.tpl');
        } else {
            r2(getUrl('customers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;
    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        run_hook('edit_customer'); #HOOK
        if ($admin['user_type'] == 'SuperAdmin') {

    $d = ORM::for_table('tbl_customers')
        ->find_one($id);

} else {

    $d = ORM::for_table('tbl_customers')
        ->where('id', $id)
        ->where('created_by', $admin['id'])
        ->find_one();
}
        // Fetch the Customers Attributes values from the tbl_customers_fields table
        $customFields = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $id)
            ->find_many();
        if ($d) {
            if (isset($routes['3']) && $routes['3'] == 'deletePhoto') {
                if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                    if (file_exists($UPLOAD_PATH . $d['photo']) && strpos($d['photo'], 'default') === false) {
                        unlink($UPLOAD_PATH . $d['photo']);
                        if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                            unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                        }
                    }
                    $d->photo = '/user.default.jpg';
                    $d->save();
                    $ui->assign('notify_t', 's');
                    $ui->assign('notify', 'You have successfully deleted the photo');
                } else {
                    $ui->assign('notify_t', 'e');
                    $ui->assign('notify', 'No photo found to delete');
                }
            }
            $ui->assign('d', customersRowForUi($d));
            $ui->assign('statuses', ORM::for_table('tbl_customers')->getEnum("status"));
            $ui->assign('customFields', $customFields);
            $ui->assign('xheader', $leafletpickerHeader);
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/customers/edit.tpl');
        } else {
            r2(getUrl('customers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;
        case 'delete-selected':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid request method')]);
            exit;
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid or Expired CSRF Token') . '.']);
            exit;
        }

        $ids = $_POST['customer_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('No customer selected')]);
            exit;
        }

        $deleted = 0;
        $errors = [];
        foreach ($ids as $customerId) {
            $result = customersDeleteOne($customerId, $admin);
            if ($result === true) {
                $deleted++;
            } else {
                $errors[] = '#' . $customerId . ': ' . $result;
            }
        }

        header('Content-Type: application/json');
        if ($deleted === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => $errors ? implode(' | ', $errors) : Lang::T('Failed to delete customer'),
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => Lang::T('User deleted Successfully') . ' (' . $deleted . ')',
            'errors' => $errors,
        ]);
        exit;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            r2(getUrl('customers/list'), 'e', Lang::T('Invalid request method'));
        }
        $id = (int) ($routes['2'] ?? 0);
        $csrf_token = _post('csrf_token');
        if ($csrf_token === '') {
            $csrf_token = _req('token');
        }
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        $result = customersDeleteOne($id, $admin);
        if ($result === true) {
            r2(getUrl('customers/list'), 's', Lang::T('User deleted Successfully'));
        }
        r2(getUrl('customers/view/') . $id, 'e', $result);
        break;

    case 'add-post':

        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/add'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = alphanumeric(_post('username'), ":+_.@-");
        $fullname = _post('fullname');
        $password = trim(_post('password'));
        $pppoe_username = trim(_post('pppoe_username'));
        $pppoe_password = trim(_post('pppoe_password'));
        $pppoe_ip = trim(_post('pppoe_ip'));
        $email = _post('email');
        $address = _post('address');
        $phonenumber = _post('phonenumber');
        $service_type = _post('service_type');
        $account_type = _post('account_type');
        $coordinates = _post('coordinates');
        $activate_plan_id = (int) _post('activate_plan_id');
        $activate_router = trim((string) _post('activate_router'));
        //post Customers Attributes
        $custom_field_names = (array) $_POST['custom_field_name'];
        $custom_field_values = (array) $_POST['custom_field_value'];
        //additional information
        $city = _post('city');
        $district = _post('district');
        $state = _post('state');
        $zip = _post('zip');

        run_hook('add_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 55, 2) == false) {
            $msg .= 'Username should be between 3 to 54 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 1) == false) {
            $msg .= 'Full Name should be between 2 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 36, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }
        if ($activate_plan_id > 0 || $activate_router !== '') {
            if ($activate_plan_id <= 0 || $activate_router === '') {
                $msg .= Lang::T('Select both router and plan to activate on MikroTik') . '<br>';
            } else {
                $activatePlan = ORM::for_table('tbl_plans')->find_one($activate_plan_id);
                if (!$activatePlan) {
                    $msg .= Lang::T('Plan Not Found') . '<br>';
                } elseif (trim((string) $activatePlan['routers']) !== ''
                    && strcasecmp(trim((string) $activatePlan['routers']), $activate_router) !== 0) {
                    $msg .= Lang::T('Selected plan is not assigned to this router') . '<br>';
                }
            }
        }

        if (customersFindLoginConflict($username, $admin)) {
            $msg .= Lang::T('Account already axist') . '<br>';
        }
        if ($pppoe_username !== '' && strcasecmp($pppoe_username, $username) !== 0
            && customersFindLoginConflict($pppoe_username, $admin)) {
            $msg .= Lang::T('PPPoE Username already used by another customer') . '<br>';
        }
        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = $username;
            Password::assignCustomerCredentials($d, $password, $pppoe_password);
            $d->pppoe_username = $pppoe_username;
            $d->pppoe_ip = $pppoe_ip;
            $d->email = $email;
            $d->account_type = $account_type;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->created_by = $admin['id'];
            $d->phonenumber = Lang::phoneFormat($phonenumber);
            $d->service_type = $service_type;
            $d->coordinates = $coordinates;
            $d->city = $city;
            $d->district = $district;
            $d->state = $state;
            $d->zip = $zip;
            $d->save();

            // ================= WALLET DEDUCTION START =================
            // প্যাকেজ ক্রিয়েট করার সময় যদি কোনো নির্দিষ্ট ফি থাকে তবে এখানে $deduct_amount সেট করুন
            // উদাহরণস্বরূপ: ইউজার ক্রিয়েট ফি যদি ২০ টাকা হয়
            $deduct_amount = 0; // যদি শুধু ইউজার ক্রিয়েটে টাকা কাটতে চান তবে এখানে সংখ্যা লিখুন (যেমন: 20)
            
            if ($deduct_amount > 0) {
                $wallet = ORM::for_table('admin_wallet')->where('admin_id', $admin['id'])->find_one();
                if ($wallet) {
                    if ($wallet->balance >= $deduct_amount) {
                        $wallet->balance -= $deduct_amount;
                        $wallet->save();
                    } else {
                        // ব্যালেন্স না থাকলে ইউজার ডিলিট করে এরর দিবে
                        $d->delete();
                        r2(getUrl('customers/add'), 'e', 'Insufficient Admin Wallet Balance!');
                    }
                }
            }
            // ================= WALLET DEDUCTION END =================

            // Retrieve the customer ID of the newly created customer
            $customerId = $d->id();

            // Retrieve the customer ID of the newly created customer
            $customerId = $d->id();
            // Save Customers Attributes details
            if (!empty($custom_field_names) && !empty($custom_field_values)) {
                $totalFields = min(count($custom_field_names), count($custom_field_values));
                for ($i = 0; $i < $totalFields; $i++) {
                    $name = $custom_field_names[$i];
                    $value = $custom_field_values[$i];

                    if (!empty($name)) {
                        $customField = ORM::for_table('tbl_customers_fields')->create();
                        $customField->customer_id = $customerId;
                        $customField->field_name = $name;
                        $customField->field_value = $value;
                        $customField->save();
                    }
                }
            }

            // Send welcome message
            if (isset($_POST['send_welcome_message']) && $_POST['send_welcome_message'] == true) {
                $welcomeMessage = Lang::getNotifText('welcome_message');
                $welcomeMessage = str_replace('[[company]]', $config['CompanyName'], $welcomeMessage);
                $welcomeMessage = str_replace('[[name]]', $d['fullname'], $welcomeMessage);
                $welcomeMessage = str_replace('[[username]]', $d['username'], $welcomeMessage);
                $welcomeMessage = str_replace('[[password]]', $password, $welcomeMessage);
                $welcomeMessage = str_replace('[[url]]', APP_URL . '/?_route=login', $welcomeMessage);

                $emailSubject = "Welcome to " . $config['CompanyName'];

                $channels = [
                    'sms' => [
                        'enabled' => isset($_POST['sms']),
                        'method' => 'sendSMS',
                        'args' => [$d['phonenumber'], $welcomeMessage]
                    ],
                    'whatsapp' => [
                        'enabled' => isset($_POST['wa']),
                        'method' => 'sendWhatsapp',
                        'args' => [$d['phonenumber'], $welcomeMessage]
                    ],
                    'email' => [
                        'enabled' => isset($_POST['mail']),
                        'method' => 'Message::sendEmail',
                        'args' => [$d['email'], $emailSubject, $welcomeMessage, $d['email']]
                    ]
                ];

                foreach ($channels as $channel => $message) {
                    if ($message['enabled']) {
                        try {
                            call_user_func_array($message['method'], $message['args']);
                        } catch (Exception $e) {
                            // Log the error and handle the failure
                            _log("Failed to send welcome message via $channel: " . $e->getMessage());
                        }
                    }
                }
            }
            $okMsg = Lang::T('Account Created Successfully');
            if ($activate_plan_id > 0 && $activate_router !== '') {
                Package::$lastDeviceSyncError = '';
                $recharged = Package::rechargeUser(
                    (int) $customerId,
                    $activate_router,
                    $activate_plan_id,
                    'Admin',
                    $admin['fullname'] ?? 'admin'
                );
                if ($recharged) {
                    if (Package::$lastDeviceSyncError !== '') {
                        r2(
                            getUrl('customers/view/') . $customerId,
                            'w',
                            $okMsg . ' — forfait activé en base, mais sync MikroTik échouée : '
                            . Package::$lastDeviceSyncError
                            . '. Utilisez Sync sur la fiche client quand le routeur est joignable.'
                        );
                    }
                    $okMsg .= ' — forfait synchronisé sur « ' . $activate_router . ' ».';
                } else {
                    r2(
                        getUrl('customers/view/') . $customerId,
                        'w',
                        $okMsg . ' — activation du forfait échouée. Rechargez manuellement (Services → Recharge).'
                    );
                }
            }
            r2(getUrl('customers/list'), 's', $okMsg);
        } else {
            r2(getUrl('customers/add'), 'e', $msg);
        }
        break;

    case 'edit-post':
        $id = _post('id');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/edit/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = alphanumeric(_post('username'), ":+_.@-");
        $fullname = _post('fullname');
        $account_type = _post('account_type');
        $password = trim(_post('password'));
        $pppoe_username = trim(_post('pppoe_username'));
        $pppoe_password = trim(_post('pppoe_password'));
        $pppoe_ip = trim(_post('pppoe_ip'));
        $email = _post('email');
        $address = _post('address');
        $phonenumber = Lang::phoneFormat(_post('phonenumber'));
        $service_type = _post('service_type');
        $coordinates = _post('coordinates');
        $status = _post('status');
        //additional information
        $city = _post('city');
        $district = _post('district');
        $state = _post('state');
        $zip = _post('zip');
        run_hook('edit_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 55, 2) == false) {
            $msg .= 'Username should be between 3 to 54 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 1) == false) {
            $msg .= 'Full Name should be between 2 to 25 characters' . '<br>';
        }

        if ($admin['user_type'] == 'SuperAdmin') {

    $c = ORM::for_table('tbl_customers')
        ->find_one($id);

} else {

    $c = ORM::for_table('tbl_customers')
        ->where('id', $id)
        ->where('created_by', $admin['id'])
        ->find_one();
}

        if (!$c) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        //lets find user Customers Attributes using id
        $customFields = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $id)
            ->find_many();

        $oldusername = $c['username'];
        $oldPppoeUsername = $c['pppoe_username'];
        $oldPppoePassword = $c['pppoe_password'];
        $oldPppoeIp = $c['pppoe_ip'];
        $oldPassPassword = $c['password'];
        $currentNetworkPassword = Password::networkCleartext($c);
        $userDiff = false;
        $pppoeDiff = false;
        $passDiff = false;
        $pppoeIpDiff = false;
        if ($oldusername != $username) {
            if (customersFindLoginConflict($username, $admin, (int) $id, $c)) {
                $msg .= Lang::T('Username already used by another customer') . '<br>';
            }
            $userDiff = true;
        }
        if ($oldPppoeUsername != $pppoe_username) {
            if ($pppoe_username !== '' && customersFindLoginConflict($pppoe_username, $admin, (int) $id, $c)) {
                $msg .= Lang::T('PPPoE Username already used by another customer') . '<br>';
            }
            $pppoeDiff = true;
        }

        if ($oldPppoeIp != $pppoe_ip) {
            $pppoeIpDiff = true;
        }
        if ($password != '' && !Password::isStoredHash($password) && $password !== $currentNetworkPassword) {
            $passDiff = true;
        }
        if ($pppoe_password !== '' && $pppoe_password !== $oldPppoePassword) {
            $passDiff = true;
        }

        if ($msg == '') {
            if (!empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
                if (function_exists('imagecreatetruecolor')) {
                    $hash = md5_file($_FILES['photo']['tmp_name']);
                    $subfolder = substr($hash, 0, 2);
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $imgPath = $folder . $hash . '.jpg';
                    if (!file_exists($imgPath)) {
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                    }
                    if (!file_exists($imgPath . '.thumb.jpg')) {
                        if (_post('faceDetect') == 'yes') {
                            try {
                                $detector = new svay\FaceDetector();
                                $detector->setTimeout(5000);
                                $detector->faceDetect($imgPath);
                                $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                            } catch (Exception $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            } catch (Throwable $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            }
                        } else {
                            File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                        }
                    }
                    if (file_exists($imgPath)) {
                        if ($c['photo'] != '' && strpos($c['photo'], 'default') === false) {
                            if (file_exists($UPLOAD_PATH . $c['photo'])) {
                                unlink($UPLOAD_PATH . $c['photo']);
                                if (file_exists($UPLOAD_PATH . $c['photo'] . '.thumb.jpg')) {
                                    unlink($UPLOAD_PATH . $c['photo'] . '.thumb.jpg');
                                }
                            }
                        }
                        $c->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name'])) unlink($_FILES['photo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }
            if ($userDiff) {
                $c->username = $username;
            }
            if ($password != '' && !Password::isStoredHash($password)) {
                Password::assignCustomerCredentials($c, $password, $pppoe_password);
            } elseif ($pppoe_password !== '') {
                $c->pppoe_password = $pppoe_password;
            }
            $c->pppoe_username = $pppoe_username;
            $c->pppoe_ip = $pppoe_ip;
            $c->fullname = $fullname;
            $c->email = $email;
            $c->account_type = $account_type;
            $c->address = $address;
            $c->status = $status;
            $c->phonenumber = $phonenumber;
            $c->service_type = $service_type;
            $c->coordinates = $coordinates;
            $c->city = $city;
            $c->district = $district;
            $c->state = $state;
            $c->zip = $zip;
            $c->save();


            // Update Customers Attributes values in tbl_customers_fields table
            foreach ($customFields as $customField) {
                $fieldName = $customField['field_name'];
                if (isset($_POST['custom_fields'][$fieldName])) {
                    $customFieldValue = $_POST['custom_fields'][$fieldName];
                    $customField->set('field_value', $customFieldValue);
                    $customField->save();
                }
            }

            // Add new Customers Attributess
            if (isset($_POST['custom_field_name']) && isset($_POST['custom_field_value'])) {
                $newCustomFieldNames = $_POST['custom_field_name'];
                $newCustomFieldValues = $_POST['custom_field_value'];

                // Check if the number of field names and values match
                if (count($newCustomFieldNames) == count($newCustomFieldValues)) {
                    $numNewFields = count($newCustomFieldNames);

                    for ($i = 0; $i < $numNewFields; $i++) {
                        $fieldName = $newCustomFieldNames[$i];
                        $fieldValue = $newCustomFieldValues[$i];

                        // Insert the new Customers Attributes
                        $newCustomField = ORM::for_table('tbl_customers_fields')->create();
                        $newCustomField->set('customer_id', $id);
                        $newCustomField->set('field_name', $fieldName);
                        $newCustomField->set('field_value', $fieldValue);
                        $newCustomField->save();
                    }
                }
            }

            // Delete Customers Attributess
            if (isset($_POST['delete_custom_fields'])) {
                $fieldsToDelete = $_POST['delete_custom_fields'];
                foreach ($fieldsToDelete as $fieldName) {
                    // Delete the Customers Attributes with the given field name
                    ORM::for_table('tbl_customers_fields')
                        ->where('field_name', $fieldName)
                        ->where('customer_id', $id)
                        ->delete_many();
                }
            }

            if ($userDiff || $pppoeDiff || $pppoeIpDiff || $passDiff) {
                $turs = ORM::for_table('tbl_user_recharges')->where('customer_id', $c['id'])->findMany();
                foreach ($turs as $tur) {
                    $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        // if has active package
                        if ($tur['status'] == 'on') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                if ($userDiff) {
                                    (new $p['device'])->change_username($p, $oldusername, $username);
                                }
                                if ($pppoeDiff && $tur['type'] == 'PPPOE') {
                                    if (empty($oldPppoeUsername) && !empty($pppoe_username)) {
                                        // admin just add pppoe username
                                        (new $p['device'])->change_username($p, $username, $pppoe_username);
                                    } else if (empty($pppoe_username) && !empty($oldPppoeUsername)) {
                                        // admin want to use customer username
                                        (new $p['device'])->change_username($p, $oldPppoeUsername, $username);
                                    } else {
                                        // regular change pppoe username
                                        (new $p['device'])->change_username($p, $oldPppoeUsername, $pppoe_username);
                                    }
                                }
                                (new $p['device'])->add_customer($c, $p);
                            } else {
                                throw new Exception(Lang::T("Devices Not Found"));
                            }
                        }
                    }
                    $tur->username = $username;
                    $tur->save();
                }
            }
            r2(getUrl('customers/view/') . $id, 's', 'User Updated Successfully');
        } else {
            r2(getUrl('customers/edit/') . $id, 'e', $msg);
        }
        break;

    default:
    run_hook('list_customers'); #HOOK
    $search = _req('search');
    $order = _req('order', 'username');
    
    // ড্যাশবোর্ড থেকে আসা সঠিক প্যারামিটার রিসিভ করা
    $f_status = _req('filter_status', 'All'); 
    $f_type = _req('filter_type', ''); 
    $orderby = _req('orderby', 'asc');
    
    $order_pos = [
        'username' => 0,
        'created_at' => 6,
        'balance' => 3,
        'status' => 5
    ];

    // পেজিনেশনের জন্য URL তৈরি
    $append_url = "&order=" . urlencode($order) . "&filter_status=" . urlencode($f_status) . "&filter_type=" . urlencode($f_type) . "&orderby=" . urlencode($orderby);

    $query = ORM::for_table('tbl_customers');
    
    if ($admin['user_type'] != 'SuperAdmin') {
    $query->where('created_by', $admin['id']);
}

    // লজিক ১: সার্ভিস টাইপ ফিল্টার (PPPoE বা Hotspot আলাদা করা)
    // TYPE FILTER (Recharge table থেকে)
if ($f_type != '') {

    $type_ids = ORM::for_table('tbl_user_recharges')
        ->where('type', $f_type) // Hotspot বা PPPoE
        ->select('customer_id')
        ->find_array();

    $ids = array_column($type_ids, 'customer_id');

    if (!empty($ids)) {
        $query->where_in('id', $ids);
    } else {
        $query->where('id', 0);
    }
}

    // লজিক ২: স্ট্যাটাস ফিল্টার (FIXED)
if ($f_status == 'Active') {

    // Active = যাদের valid recharge আছে
    $active_ids = ORM::for_table('tbl_user_recharges')
        ->where('status', 'on')
        ->where_raw("expiration >= NOW()")
        ->select('customer_id')
        ->find_array();

    $ids = array_column($active_ids, 'customer_id');

    if (!empty($ids)) {
        $query->where_in('id', $ids);
    } else {
        $query->where('id', 0);
    }

} elseif ($f_status == 'Expired') {

    // Expired = যাদের recharge আছে কিন্তু date শেষ
    $expired_ids = ORM::for_table('tbl_user_recharges')
        ->where_raw("expiration < NOW()")
        ->select('customer_id')
        ->find_array();

    $ids = array_column($expired_ids, 'customer_id');

    if (!empty($ids)) {
        $query->where_in('id', $ids);
    } else {
        $query->where('id', 0);
    }
}

    // লজিক ৩: সার্চ বক্স লজিক
    if ($search != '') {
        $like = '%' . $search . '%';
        $query->where_raw('(username LIKE ? OR fullname LIKE ? OR address LIKE ? OR phonenumber LIKE ? OR email LIKE ?)', [$like, $like, $like, $like, $like]);
    }

    // অর্ডার বা সর্টিং
    if ($order == 'lastname') {
        $query->order_by_expr("SUBSTR(fullname, INSTR(fullname, ' ')) $orderby");
    } else {
        if ($orderby == 'asc') {
            $query->order_by_asc($order);
        } else {
            $query->order_by_desc($order);
        }
    }

    // CSV এক্সপোর্ট
    if (_post('export', '') == 'csv') {
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $d = $query->findMany();
        set_time_limit(-1);
        header('Content-type: text/csv');
        header('Content-Disposition: attachment;filename="customers_list_' . date('Y-m-d') . '.csv"');
        $fp = fopen('php://output', 'wb');
        $customDefs = [];
        global $UPLOAD_PATH;
        $fieldPath = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'customer_field.json';
        if (file_exists($fieldPath)) {
            $customDefs = json_decode(file_get_contents($fieldPath), true) ?: [];
        }
        $header = ['id', 'username', 'fullname', 'address', 'phonenumber', 'email', 'balance', 'service_type'];
        foreach ($customDefs as $cf) {
            if (!empty($cf['name'])) {
                $header[] = 'cf_' . $cf['name'];
            }
        }
        fputcsv($fp, $header, ';');
        foreach ($d as $c) {
            $row = [$c['id'], $c['username'], $c['fullname'], str_replace("\n", ' ', $c['address']), $c['phonenumber'], $c['email'], $c['balance'], $c['service_type']];
            $cfJson = [];
            if (!empty($c['custom_fields'])) {
                $cfJson = json_decode($c['custom_fields'], true) ?: [];
            }
            foreach ($customDefs as $cf) {
                $name = $cf['name'] ?? '';
                $row[] = $cfJson[$name] ?? '';
            }
            fputcsv($fp, $row, ';');
        }
        fclose($fp);
        die();
    }

    // ডাটা রেন্ডার করা
    if (DemoShowcase::isActive($admin)) {
        DemoShowcase::injectCustomersList($ui, 30, $append_url);
    } else {
        $d = Paginator::findMany($query, ['search' => $search], 30, $append_url);
        $customerIds = [];
        foreach ($d as $customer) {
            $customerIds[] = (int) ($customer['id'] ?? 0);
        }
        $rechargeSummary = customersRechargeSummaryByIds($customerIds);
        foreach ($d as $k => $customer) {
            $contactPhone = trim((string) ($customer['phonenumber'] ?? ''));
            if ($contactPhone === '') {
                $payment = ORM::for_table('tbl_hotspot_payments')
                    ->where('transaction_status', 'paid')
                    ->where('voucher_code', $customer['username'])
                    ->order_by_desc('payment_date')
                    ->find_one();
                if ($payment && trim((string) $payment->phone_number) !== '') {
                    $contactPhone = trim((string) $payment->phone_number);
                }
            }
            $summary = $rechargeSummary[(int) ($customer['id'] ?? 0)] ?? [];
            $d[$k]['contact_phone'] = $contactPhone;
            $d[$k]['router_name'] = $summary['router_name'] ?? '';
            $d[$k]['expiration'] = $summary['expiration'] ?? '';
            $d[$k]['time'] = $summary['time'] ?? '';
        }
        $ui->assign('d', $d);
    }
    $ui->assign('filter', $f_status); 
    $ui->assign('filter_type', $f_type);
    $ui->assign('search', $search);
    $ui->assign('order', $order);
    $ui->assign('order_pos', $order_pos[$order]);
    $ui->assign('orderby', $orderby);
    $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
    
    $ui->display('admin/customers/list.tpl');
    break;
}
