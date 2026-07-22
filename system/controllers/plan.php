<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Recharge Account'));
$ui->assign('_system_menu', 'plan');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$appUrl = APP_URL;

function plan_scoped_router_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function plan_scoped_plan_query($admin)
{
    $query = ORM::for_table('tbl_plans');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function plan_list_apply_filters($query, $admin, $search, $router, $plan, $type, $status = null)
{
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('tbl_user_recharges.admin_id', $admin['id']);
    }
    if ($search != '') {
        $query->where_raw(
            '(`tbl_user_recharges`.`username` LIKE ? OR `tbl_customers`.`fullname` LIKE ? OR `tbl_customers`.`phonenumber` LIKE ? OR `tbl_user_recharges`.`routers` LIKE ?)',
            ["%$search%", "%$search%", "%$search%", "%$search%"]
        );
    }
    if (!empty($router)) {
        $query->where('tbl_user_recharges.routers', $router);
    }
    if (!empty($plan)) {
        $query->where('tbl_user_recharges.plan_id', $plan);
    }
    if (!empty($type)) {
        $query->where('tbl_user_recharges.type', $type);
    }
    if (!empty($status) && $status != '-') {
        if ($status === 'on') {
            $query->where('tbl_user_recharges.status', 'on')
                ->where_raw("CONCAT(tbl_user_recharges.expiration, ' ', tbl_user_recharges.time) > NOW()");
        } elseif ($status === 'off') {
            $query->where_raw("(tbl_user_recharges.status = 'off' OR CONCAT(tbl_user_recharges.expiration, ' ', tbl_user_recharges.time) <= NOW())");
        } else {
            $query->where('tbl_user_recharges.status', $status);
        }
    }
    return $query;
}

function plan_list_base_query($admin, $search, $router, $plan, $type, $status = null, $withSelect = true)
{
    $query = ORM::for_table('tbl_user_recharges')
        ->left_outer_join('tbl_customers', ['tbl_user_recharges.customer_id', '=', 'tbl_customers.id']);
    if ($withSelect) {
        $query->select('tbl_user_recharges.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->select('tbl_customers.address', 'address')
            ->select('tbl_customers.phonenumber', 'phonenumber');
    }
    return plan_list_apply_filters($query, $admin, $search, $router, $plan, $type, $status);
}

function plan_allowed_voucher_generators($admin)
{
    if ($admin['user_type'] == 'SuperAdmin') {
        return null;
    }
    $ids = [(int) $admin['id']];
    foreach (ORM::for_table('tbl_users')->select('id')->where('root', (int) $admin['id'])->findArray() as $u) {
        $ids[] = (int) $u['id'];
    }
    if (!empty($admin['root'])) {
        $ids[] = (int) $admin['root'];
    }
    return array_values(array_unique(array_filter($ids)));
}

function plan_apply_voucher_scope($query, $admin)
{
    $ids = plan_allowed_voucher_generators($admin);
    if ($ids !== null) {
        $query->where_in('tbl_voucher.generated_by', $ids);
    }
    return $query;
}

$select2_customer = <<<EOT
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    $('#personSelect').select2({
        theme: "bootstrap",
        ajax: {
            url: function(params) {
                if(params.term != undefined){
                    return '{$appUrl}/?_route=autoload/customer_select2&s='+params.term;
                }else{
                    return '{$appUrl}/?_route=autoload/customer_select2';
                }
            }
        }
    });
});
</script>
EOT;
getUrl('docs');
switch ($action) {
    case 'sync':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        set_time_limit(-1);
        $turs = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();
        $log = '';
        $router = '';
        foreach ($turs as $tur) {
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            if ($p) {
                $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
                if ($c) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            if (method_exists($dvc, 'sync_customer')) {
                                (new $p['device'])->sync_customer($c, $p);
                            } else {
                                (new $p['device'])->add_customer($c, $p);
                            }
                        } else {
                            throw new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    $log .= "DONE : $tur[username], $tur[namebp], $tur[type], $tur[routers]<br>";
                } else {
                    $log .= "Customer NOT FOUND : $tur[username], $tur[namebp], $tur[type], $tur[routers]<br>";
                }
            } else {
                $log .= "PLAN NOT FOUND : $tur[username], $tur[namebp], $tur[type], $tur[routers]<br>";
            }
        }
        r2(getUrl('plan/list'), 's', $log);
    case 'recharge':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xfooter', $select2_customer);
        if (isset($routes['2']) && !empty($routes['2'])) {
            $ui->assign('cust', ORM::for_table('tbl_customers')->find_one($routes['2']));
        }
        $usings = explode(',', $config['payment_usings']);
        $usings = array_filter(array_unique($usings));
        if (count($usings) == 0) {
            $usings[] = Lang::T('Cash');
        }
        $ui->assign('usings', $usings);
        run_hook('view_recharge'); #HOOK
        $ui->display('admin/plan/recharge.tpl');
        break;

    case 'recharge-confirm':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($msg == '') {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            $plan = ORM::for_table('tbl_plans')->find_one($planId);
            list($bills, $add_cost) = User::getBills($id_customer);
            $add_inv = User::getAttribute("Invoice", $id_customer);
            if (!empty($add_inv)) {
                $plan['price'] = $add_inv;
            }

            // Tax calculation start
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

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
            // Tax calculation stop
            $total_cost = $plan['price'] + $add_cost + $tax;

            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < $total_cost) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            $usings = explode(',', $config['payment_usings']);
            $usings = array_filter(array_unique($usings));
            if (count($usings) == 0) {
                $usings[] = Lang::T('Cash');
            }
            if ($tax_enable === 'yes') {
                $ui->assign('tax', $tax);
            }
            $ui->assign('usings', $usings);
            $ui->assign('bills', $bills);
            $ui->assign('add_cost', $add_cost);
            $ui->assign('cust', $cust);
            $ui->assign('gateway', $gateway);
            $ui->assign('channel', $channel);
            $ui->assign('server', $server);
            $ui->assign('using', $using);
            $ui->assign('plan', $plan);
            $ui->assign('add_inv', $add_inv);
            $ui->display('admin/plan/recharge-confirm.tpl');
        } else {
            r2(getUrl('plan/recharge'), 'e', $msg);
        }
        break;

    case 'recharge-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');
        $svoucher = _post('svoucher');

        $plan = ORM::for_table('tbl_plans')->find_one($planId);

        // ভাউচার চেক
        if (!empty(App::getVoucherValue($svoucher))) {
            $username = App::getVoucherValue($svoucher);
            $in = ORM::for_table('tbl_transactions')->where('username', $username)->order_by_desc('id')->find_one();
            Package::createInvoice($in);
            $ui->display('admin/plan/invoice.tpl');
            die();
        }

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($msg == '') {
            $gateway = ucwords($using);
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            list($bills, $add_cost) = User::getBills($id_customer);

            // Tax calculation start
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

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
            // Tax calculation stop
            
            $total_cost = $plan['price'] + $add_cost + $tax;

            // কাস্টমার ব্যালেন্স চেক (যদি ইউজার নিজের ব্যালেন্স দিয়ে করে)
            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < $total_cost) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            
            if ($using == 'zero') {
                $add_cost = 0;
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            
            // ===== Admin Wallet Balance Check =====
$current_admin_id = $admin['id'];

if ($admin['user_type'] != 'SuperAdmin') {

    if ($current_admin_id > 0) {

        $aw = ORM::for_table('admin_wallet')
            ->where('admin_id', $current_admin_id)
            ->find_one();

        if ($aw) {

            $admin_balance = (float)$aw->balance;

            if ($admin_balance < $total_cost) {
                r2(getUrl('plan/recharge'), 'e',
                    Lang::T('You have insufficient wallet balance')
                );
                exit;
            }
        } else {

            r2(getUrl('plan/recharge'), 'e',
                Lang::T('Admin wallet not found')
            );
            exit;
        }
    }
}

            // রিচার্জ প্রক্রিয়া শুরু
            Package::$lastDeviceSyncError = '';
            if (Package::rechargeUser($id_customer, $server, $planId, $gateway, $channel)) {
                
                // --- অ্যাডমিন ওয়ালেট থেকে টাকা কাটার লজিক শুরু ---
                $current_admin_id = $admin['id']; // আপনার সিস্টেমের ডিফল্ট অ্যাডমিন আইডি
                
                if ($current_admin_id > 0) {
                    $aw = ORM::for_table('admin_wallet')->where('admin_id', $current_admin_id)->find_one();
                    
                    if ($aw) {
                        $old_bal = $aw->balance;
                        // প্যাকেজের দাম + ট্যাক্স অ্যাডমিন ওয়ালেট থেকে বিয়োগ হবে
                        $new_bal = $old_bal - $total_cost;
                        
                        if ($new_bal < 0) {
    r2(getUrl('plan/recharge'), 'e', Lang::T('You have insufficient wallet balance'));
    exit;
}
                        
                        // ডাটাবেজ আপডেট
                        ORM::for_table('admin_wallet')
                            ->where('admin_id', $current_admin_id)
                            ->raw_execute("UPDATE admin_wallet SET balance = balance - $total_cost, updated_at = NOW() WHERE admin_id = $current_admin_id");

                        // ওয়ালেট লগ এন্ট্রি
                        $log = ORM::for_table('admin_wallet_logs')->create();
                        $log->admin_id = $current_admin_id;
                        $log->type = 'Debit';
                        $log->old_balance = $old_bal;
                        $log->amount = $total_cost;
                        $log->total_balance = $new_bal;
                        $log->note = "Admin recharged customer: " . $cust['username'] . " | Plan: " . $plan['name'];
                        $log->created_at = date('Y-m-d H:i:s');
                        $log->save();
                        // ===== COMMISSION ADD START =====

if ($admin['user_type'] != 'SuperAdmin') {

    add_commission_on_sale(
        $current_admin_id,
        $plan['price'],
        $plan['name']
    );

}

// ===== COMMISSION ADD END =====
                    }
                }
                // --- অ্যাডমিন ওয়ালেট লজিক শেষ ---

                if (Package::$lastDeviceSyncError !== '') {
                    r2(
                        getUrl('customers/view/') . $id_customer,
                        'w',
                        Lang::T('Data Created Successfully')
                        . ' — forfait en base, mais sync MikroTik échouée : '
                        . Package::$lastDeviceSyncError
                        . '. Relancez Sync sur la fiche client.'
                    );
                }

                $in = ORM::for_table('tbl_transactions')->where('username', $cust['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                App::setVoucher($svoucher, $cust['username']);
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/recharge'), 'e', "Failed to recharge account");
            }
        } else {
            r2(getUrl('plan/recharge'), 'e', $msg);
        }
        break;

    case 'view':
        $id = $routes['2'];
        $in = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        $ui->assign('in', $in);
        if (!empty($routes['3']) && $routes['3'] == 'send') {
            $c = ORM::for_table('tbl_customers')->where('username', $in['username'])->find_one();
            if ($c) {
                Message::sendInvoice($c, $in);
                r2(getUrl('plan/view/') . $id, 's', "Success send to customer");
            }
            r2(getUrl('plan/view/') . $id, 'd', "Customer not found");
        }
        Package::createInvoice($in);
        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        $logo = '';
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png';
            $imgsize = getimagesize($logo);
            $width = $imgsize[0];
            $height = $imgsize[1];
            $ui->assign('wlogo', $width);
            $ui->assign('hlogo', $height);
        }

        $ui->assign('public_url', getUrl("voucher/invoice/$id/".md5($id. $db_pass)));
        $ui->assign('logo', $logo);
        $ui->assign('_title', 'View Invoice');
        $ui->display('admin/plan/invoice.tpl');
        break;


    case 'print':
        $content = _post('content');
        if (!empty($content)) {
            if (_post('nux') == 'print') {
                //header("Location: nux://print?text=".urlencode($content));
                $ui->assign('nuxprint', "nux://print?text=" . urlencode($content));
            }
            $ui->assign('content', $content);
        } else {
            $id = _post('id');
            if (empty($id)) {
                $id = $routes['2'];
            }
            $d = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
            $ui->assign('in', $d);
            $ui->assign('date', Lang::dateAndTimeFormat($d['recharged_on'], $d['recharged_time']));
        }
        run_hook('print_invoice'); #HOOK
        $ui->display('admin/plan/invoice-print.tpl');
        break;

    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            if (in_array($admin['user_type'], array('SuperAdmin', 'Admin'))) {
                $ps = ORM::for_table('tbl_plans')
                    ->where('type', $p['type'])
                    ->where('is_radius', $p['is_radius'])
                    ->find_many();
            } else {
                $ps = ORM::for_table('tbl_plans')
                    ->where("enabled", 1)
                    ->where('is_radius', $p['is_radius'])
                    ->where('type', $p['type'])
                    ->find_many();
            }
            $ui->assign('p', $ps);
            run_hook('view_edit_customer_plan'); #HOOK
            $ui->assign('_title', 'Edit Plan');
            $ui->display('admin/plan/edit.tpl');
        } else {
            r2(getUrl('plan/list'), 'e', Lang::T('Account Not Found'));
        }
        break;
        
        case 'delete-selected':

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
    }

    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];

    if (empty($ids)) {
        r2(getUrl('plan/list'), 'e', 'No items selected');
        exit;
    }

    foreach ($ids as $id) {

        $d = ORM::for_table('tbl_user_recharges')->find_one($id);

        if ($d) {

            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            $c = User::_info($d['customer_id']);

            if ($p && $c) {
                $dvc = Package::getDevice($p);

                if ($_app_stage != 'demo' && file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        (new $p['device'])->remove_customer($c, $p);
                    } catch (Throwable $e) {
                        r2(getUrl('plan/list'), 'e', WifiZoneSecurity::safeExceptionMessage($e));
                        exit;
                    }
                }
            }

            $d->delete();
        }
    }

    r2(getUrl('plan/list'), 's', 'Selected Customers Deleted Successfully');
    break;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            run_hook('delete_customer_active_plan'); #HOOK
            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            $c = User::_info($d['customer_id']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        (new $p['device'])->remove_customer($c, $p);
                    } catch (Throwable $e) {
                        r2(getUrl('plan/list'), 'e', WifiZoneSecurity::safeExceptionMessage($e));
                        exit;
                    }
                } else {
                    throw new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();
            _log('[' . $admin['username'] . ']: ' . 'Delete Plan for Customer ' . $c['username'] . '  [' . $in['plan_name'] . '][' . Lang::moneyFormat($in['price']) . ']', $admin['user_type'], $admin['id']);
            r2(getUrl('plan/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'edit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = _post('id');
        $csrf_token = _post('csrf_token');
        if ($csrf_token === '') {
            $csrf_token = _req('token');
        }
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/edit/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $id_plan = _post('id_plan');
        $recharged_on = _post('recharged_on');
        $expiration = _post('expiration');
        $time = _post('time');
        $adjust_days = (int) _post('adjust_days');

        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        $oldPlanID = $d['plan_id'];
        $p = ORM::for_table('tbl_plans')->find_one($oldPlanID);
        $newPlan = ORM::for_table('tbl_plans')->where('id', $id_plan)->find_one();
        if ($newPlan) {
        } else {
            $msg .= ' Plan Not Found<br>';
        }
        if ($msg == '') {
            run_hook('edit_customer_plan'); #HOOK
            if ($adjust_days != 0) {
                $sign = $adjust_days > 0 ? '+' : '-';
                $days = abs($adjust_days);
                $expiration = date('Y-m-d', strtotime($d['expiration'] . " $sign$days days"));
            }
            $d->expiration = $expiration;
            $d->time = $time;
            if ($d['status'] == 'off') {
                if (strtotime($expiration . ' ' . $time) > time()) {
                    $d->status = 'on';
                }
            }
            // plan different then do something
            if ($oldPlanID != $id_plan) {
                $d->plan_id = $newPlan['id'];
                $d->namebp = $newPlan['name_plan'];
                $p = $newPlan;
                $customer = User::_info($d['customer_id']);
                //remove from old plan
                if ($d['status'] == 'on') {
                    $oldPlan = ORM::for_table('tbl_plans')->find_one($oldPlanID);
                    $dvc = Package::getDevice($oldPlan);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            $oldPlan['plan_expired'] = 0;
                            try {
                                (new $oldPlan['device'])->remove_customer($customer, $oldPlan);
                            } catch (Throwable $e) {
                                r2(getUrl('plan/list'), 'e', WifiZoneSecurity::safeExceptionMessage($e));
                                exit;
                            }
                        } else {
                            throw new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    //add new plan
                    $dvc = Package::getDevice($newPlan);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            (new $newPlan['device'])->add_customer($customer, $newPlan);
                        } else {
                            throw new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                }
            }
            $d->save();
            // sync router when active so the new expiry is applied on the device
            if ($d['status'] == 'on') {
                $customer = User::_info($d['customer_id']);
                $dvc = Package::getDevice($p);
                if ($_app_stage != 'demo' && file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        if (method_exists($p['device'], 'sync_customer')) {
                            (new $p['device'])->sync_customer($customer, $p);
                        } else {
                            (new $p['device'])->add_customer($customer, $p);
                        }
                    } catch (Throwable $e) {
                        _log('Plan edit sync error: ' . $e->getMessage(), 'Error');
                    }
                }
            }
            _log('[' . $admin['username'] . ']: ' . 'Edit Plan for Customer ' . $d['username'] . ' to [' . $d['namebp'] . '][' . Lang::moneyFormat($p['price']) . ']', $admin['user_type'], $admin['id']);
            r2(getUrl('plan/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('plan/edit/') . $id, 'e', $msg);
        }
        break;

    case 'voucher':
        $ui->assign('_title', Lang::T('Voucher Cards'));
        $search = _req('search');
        $router = _req('router');
        $customer = _req('customer');
        $plan = _req('plan');
        $status = _req('status');
        $ui->assign('router', $router);
        $ui->assign('customer', $customer);
        $ui->assign('status', $status);
        $ui->assign('plan', $plan);
        $ui->assign('_system_menu', 'cards');

        $query = ORM::for_table('tbl_plans')
            ->inner_join('tbl_voucher', ['tbl_plans.id', '=', 'tbl_voucher.id_plan']);
        plan_apply_voucher_scope($query, $admin);

        if (!empty($router)) {
            $query->where('tbl_voucher.routers', $router);
        }

        if ($status == '1' || $status == '0') {
            $query->where('tbl_voucher.status', $status);
        }

        if (!empty($plan)) {
            $query->where('tbl_voucher.id_plan', $plan);
        }

        if (!empty($customer)) {
            $query->where('tbl_voucher.user', $customer);
        }

        $append_url = "&search=" . urlencode($search) . "&router=" . urlencode($router) . "&customer=" . urlencode($customer) . "&plan=" . urlencode($plan) . "&status=" . urlencode($status);

        // option customers
        $customersQuery = ORM::for_table('tbl_voucher')->distinct()->select("user")->whereNotEqual("user", '0');
        plan_apply_voucher_scope($customersQuery, $admin);
        $ui->assign('customers', $customersQuery->findArray());
        // option plans
        $plansQuery = ORM::for_table('tbl_voucher')->distinct()->select("id_plan");
        plan_apply_voucher_scope($plansQuery, $admin);
        $plns = $plansQuery->findArray();
        if (count($plns) > 0) {
            $ui->assign('plans', ORM::for_table('tbl_plans')->selects(["id", 'name_plan'])->where_in('id', array_column($plns, 'id_plan'))->findArray());
        } else {
            $ui->assign('plans', []);
        }
        $routersQuery = ORM::for_table('tbl_voucher')->distinct()->select("routers");
        plan_apply_voucher_scope($routersQuery, $admin);
        $ui->assign('routers', array_column($routersQuery->findArray(), 'routers'));

        if ($search != '') {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                $query->where_like('tbl_voucher.code', '%' . $search . '%');
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query->where_in('generated_by', $sales)
                    ->where_like('tbl_voucher.code', '%' . $search . '%');
            }
        } else {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query->where_in('generated_by', $sales);
            }
        }
        $d = Paginator::findMany($query, ["search" => $search], 10, $append_url);
        // extract admin
        $admins = [];
        foreach ($d as $k) {
            if (!empty($k['generated_by'])) {
                $admins[] = $k['generated_by'];
            }
        }
        if (count($admins) > 0) {
            $adms = ORM::for_table('tbl_users')->where_in('id', $admins)->find_many();
            unset($admins);
            foreach ($adms as $adm) {
                $tipe = $adm['user_type'];
                if ($tipe == 'Sales') {
                    $tipe = ' [S]';
                } else if ($tipe == 'Agent') {
                    $tipe = ' [A]';
                } else {
                    $tipe == '';
                }
                $admins[$adm['id']] = $adm['fullname'] . $tipe;
            }
        }

        $ui->assign('admins', $admins);
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        $ui->assign('page', $page);
        run_hook('view_list_voucher'); #HOOK
        $ui->display('admin/voucher/list.tpl');
        break;

    case 'add-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Add Vouchers'));
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = plan_scoped_plan_query($admin)->where('enabled', '1')->find_many();
        $ui->assign('p', $p);
        $r = plan_scoped_router_query($admin)->where('enabled', '1')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_voucher'); #HOOK
        $ui->display('admin/voucher/add.tpl');
        break;

    case 'remove-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $time3months = strtotime('-3 months');
        $d = ORM::for_table('tbl_voucher')->where_equal('status', '1')
            ->where_raw("UNIX_TIMESTAMP(used_date) < $time3months")
            ->findMany();
        if ($d) {
            $jml = 0;
            foreach ($d as $v) {
                if (!ORM::for_table('tbl_user_recharges')->where_equal("method", 'Voucher - ' . $v['code'])->findOne()) {
                    $v->delete();
                    $jml++;
                }
            }
            r2(getUrl('plan/voucher'), 's', "$jml " . Lang::T('Data Deleted Successfully'));
        }
    case 'print-voucher':
        $from_id = _post('from_id');
        $planid = _post('planid');
        $pagebreak = _post('pagebreak');
        $limit = _post('limit');
        $vpl = _post('vpl');
        $selected_datetime = _post('selected_datetime');
        if (empty($vpl)) {
            $vpl = 3;
        }
        if ($pagebreak < 1)
            $pagebreak = 12;

        if ($limit < 1)
            $limit = $pagebreak * 2;
        if (empty($from_id)) {
            $from_id = 0;
        }

        if ($from_id > 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id);
        } else if ($from_id == 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid);
        } else if ($from_id > 0 && $planid == 0) {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id);
        } else if ($from_id > 0 && $planid == 0 && $selected_datetime != '') {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_raw("DATE(created_at) = ?", [$selected_datetime])
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id);
        } else {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0');
        }
        if (!empty($selected_datetime)) {
            $v = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_voucher.created_at', $selected_datetime)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0');
        }
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $v = $v->find_many();
            $vc = $vc->count();
        } else {
            $sales = [];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $sales[] = $admin['id'];
            $v = $v->where_in('generated_by', $sales)->find_many();
            $vc = $vc->where_in('generated_by', $sales)->count();
        }
        $template = file_get_contents("pages/Voucher.html");
        $template = str_replace('[[company_name]]', $config['CompanyName'], $template);

        $ui->assign('_title', Lang::T('Hotspot Voucher'));
        $ui->assign('from_id', $from_id);
        $ui->assign('vpl', $vpl);
        $ui->assign('pagebreak', $pagebreak);

        $plans = plan_scoped_plan_query($admin)->find_many();
        $ui->assign('plans', $plans);
        $ui->assign('limit', $limit);
        $ui->assign('planid', $planid);

        $createdate = ORM::for_table('tbl_voucher')
            ->select_expr(
                "CASE WHEN DATE(created_at) = CURDATE() THEN 'Today' ELSE DATE(created_at) END",
                'created_datetime'
            )
            ->where_not_equal('created_at', '0')
            ->select_expr('COUNT(*)', 'voucher_count')
            ->group_by('created_datetime')
            ->order_by_desc('created_datetime')
            ->find_array();

        $ui->assign('createdate', $createdate);

        $voucher = [];
        $n = 1;
        foreach ($v as $vs) {
            $temp = $template;
            $temp = str_replace('[[qrcode]]', '<img src="qrcode/?data=' . $vs['code'] . '">', $temp);
            $temp = str_replace('[[price]]', Lang::moneyFormat($vs['price']), $temp);
            $temp = str_replace('[[voucher_code]]', $vs['code'], $temp);
            $temp = str_replace('[[plan]]', $vs['name_plan'], $temp);
            $temp = str_replace('[[counter]]', $n, $temp);
            $voucher[] = $temp;
            $n++;
        }

        $ui->assign('voucher', $voucher);
        $ui->assign('vc', $vc);
        $ui->assign('selected_datetime', $selected_datetime);

        //for counting pagebreak
        $ui->assign('jml', 0);
        run_hook('view_print_voucher'); #HOOK
        $ui->display('admin/print/voucher.tpl');
        break;
    case 'voucher-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('plan/add-voucher/'), 'e', 'You cannot perform this action in Demo mode');
        }

        $type = _post('type');
        $plan = _post('plan');
        $voucher_format = _post('voucher_format');
        $prefix = _post('prefix');
        $server = _post('server');
        $numbervoucher = _post('numbervoucher');
        $lengthcode = _post('lengthcode');
        $printNow = _post('print_now', 'no');
        $voucherPerPage = _post('voucher_per_page', '36');

        $msg = '';
        if (empty($type) || empty($plan) || empty($server) || empty($numbervoucher) || empty($lengthcode)) {
            $msg .= Lang::T('All fields are required') . '<br>';
        }
        if (!Validator::UnsignedNumber($numbervoucher)) {
            $msg .= 'The Number of Vouchers must be a number' . '<br>';
        }
        if (!Validator::UnsignedNumber($lengthcode)) {
            $msg .= 'The Length Code must be a number' . '<br>';
        }
        if ($admin['user_type'] != 'SuperAdmin') {
            $allowedRouter = plan_scoped_router_query($admin)->where('name', $server)->find_one();
            if (!$allowedRouter) {
                $msg .= Lang::T('Router not found or not allowed') . '<br>';
            }
            $allowedPlan = ORM::for_table('tbl_plans')->where('id', $plan)->where('routers', $server)->find_one();
            if (!$allowedPlan) {
                $msg .= Lang::T('Plan not found or not allowed') . '<br>';
            }
        }

        if ($msg == '') {
            // Update or create voucher prefix
            if (!empty($prefix)) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'voucher_prefix')->find_one();
                if ($d) {
                    $d->value = $prefix;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = 'voucher_prefix';
                    $d->value = $prefix;
                    $d->save();
                }
            }

            run_hook('create_voucher'); // HOOK
            $vouchers = [];
            $newVoucherIds = [];

            if ($voucher_format == 'numbers') {
                if ($lengthcode < 6) {
                    $msg .= 'The Length Code must be more than 6 for numbers' . '<br>';
                }
                $vouchers = generateUniqueNumericVouchers($numbervoucher, $lengthcode);
            } else {
                for ($i = 0; $i < $numbervoucher; $i++) {
                    $code = strtoupper(substr(md5(time() . rand(10000, 99999)), 0, $lengthcode));
                    if ($voucher_format == 'low') {
                        $code = strtolower($code);
                    } else if ($voucher_format == 'rand') {
                        $code = Lang::randomUpLowCase($code);
                    }
                    $vouchers[] = $code;
                }
            }

            foreach ($vouchers as $code) {
                $d = ORM::for_table('tbl_voucher')->create();
                $d->type = $type;
                $d->routers = $server;
                $d->id_plan = $plan;
                $d->code = "$prefix$code";
                $d->user = '0';
                $d->status = '0';
                $d->generated_by = $admin['id'];
                $d->admin_id = $admin['user_type'] == 'SuperAdmin' ? null : $admin['id'];
                $d->save();
                $newVoucherIds[] = $d->id();

                $selectedPlan = ORM::for_table('tbl_plans')->find_one($plan);
                if ($_app_stage != 'demo' && $selectedPlan) {
                    $dvc = Package::getDevice($selectedPlan);
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        try {
                            $router = ORM::for_table('tbl_routers')->where('name', $selectedPlan['routers'])->find_one();
                            $routerHost = '';
                            $routerPort = 8728;
                            if ($router) {
                                $routerAddress = explode(':', $router['ip_address']);
                                $routerHost = $routerAddress[0];
                                $routerPort = !empty($routerAddress[1]) ? (int) $routerAddress[1] : 8728;
                            }
                            $connection = $routerHost !== '' ? @fsockopen($routerHost, $routerPort, $errno, $errstr, 2) : false;
                            if ($connection) {
                                fclose($connection);
                                $loginUsername = HotspotCustomer::generateUsername(10);
                                $voucherCustomer = [
                                    'fullname' => 'Voucher',
                                    'email' => '',
                                    'username' => $loginUsername,
                                    'password' => HotspotCustomer::defaultPassword(),
                                ];
                                (new $selectedPlan['device'])->add_customer($voucherCustomer, $selectedPlan);
                                $d->user = $loginUsername;
                                $d->save();
                            } else {
                                _log('Voucher MikroTik sync skipped [' . $d->code . ']: router unreachable', $admin['user_type'], $admin['id']);
                            }
                        } catch (Throwable $e) {
                            _log('Voucher MikroTik sync failed [' . $d->code . ']: ' . $e->getMessage(), $admin['user_type'], $admin['id']);
                        }
                    }
                }
            }

            if ($printNow == 'yes' && count($newVoucherIds) > 0) {
                $template = file_get_contents("pages/Voucher.html");
                $template = str_replace('[[company_name]]', $config['CompanyName'], $template);

                $vouchersToPrint = ORM::for_table('tbl_voucher')
                    ->left_outer_join('tbl_plans', ['tbl_plans.id', '=', 'tbl_voucher.id_plan'])
                    ->where_in('tbl_voucher.id', $newVoucherIds)
                    ->find_many();

                $voucherHtmls = [];
                $n = 1;

                foreach ($vouchersToPrint as $vs) {
                    $temp = $template;
                    $temp = str_replace('[[qrcode]]', '<img src="qrcode/?data=' . $vs['code'] . '">', $temp);
                    $temp = str_replace('[[price]]', Lang::moneyFormat($vs['price']), $temp);
                    $temp = str_replace('[[voucher_code]]', $vs['code'], $temp);
                    $temp = str_replace('[[plan]]', $vs['name_plan'], $temp);
                    $temp = str_replace('[[counter]]', $n, $temp);
                    $voucherHtmls[] = $temp;
                    $n++;
                }

                $vc = count($voucherHtmls);
                $ui->assign('voucher', $voucherHtmls);
                $ui->assign('vc', $vc);
                $ui->assign('jml', 0);
                $ui->assign('from_id', 0);
                $ui->assign('vpl', '3');
                $ui->assign('pagebreak', $voucherPerPage);
                $ui->display('admin/print/voucher.tpl');
            }

            if ($numbervoucher == 1) {
                r2(getUrl('plan/voucher-view/') . $d->id(), 's', Lang::T('Create Vouchers Successfully'));
            }

            r2(getUrl('plan/voucher'), 's', Lang::T('Create Vouchers Successfully'));
        } else {
            r2(getUrl('plan/add-voucher/') . $id, 'e', $msg);
        }
        break;

    case 'voucher-delete-many':
        header('Content-Type: application/json');

        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $voucherIds = json_decode($_POST['voucherIds'], true);

            if (is_array($voucherIds) && !empty($voucherIds)) {
                $voucherIds = array_map('intval', $voucherIds);

                try {
                    ORM::for_table('tbl_voucher')
                        ->where_in('id', $voucherIds)
                        ->delete_many();
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => Lang::T('Failed to delete vouchers.')]);
                    exit;
                }

                // Return success response
                echo json_encode(['status' => 'success', 'message' => Lang::T("Vouchers Deleted Successfully.")]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid or missing voucher IDs.")]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid request method.")]);
        }
        break;

    case 'voucher-view':
        $id = $routes[2];
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $voucher = ORM::for_table('tbl_voucher')->find_one($id);
        } else {
            $sales = [];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $sales[] = $admin['id'];
            $voucher = ORM::for_table('tbl_voucher')
                ->find_one($id);
            if (!in_array($voucher['generated_by'], $sales)) {
                r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
            }
        }
        if (!$voucher) {
            r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
        }
        $plan = ORM::for_table('tbl_plans')->find_one($voucher['id_plan']);
        if ($voucher && $plan) {
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('print', $content);
            $config['printer_cols'] = 30;
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('_title', Lang::T('View'));
            $ui->assign('whatsapp', urlencode("```$content```"));
            $ui->display('admin/voucher/view.tpl');
        } else {
            r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
        }
        break;
    case 'voucher-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        run_hook('delete_voucher'); #HOOK
        $d = ORM::for_table('tbl_voucher')->find_one($id);
        if ($d) {
            $d->delete();
            r2(getUrl('plan/voucher'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'refill':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        r2(getUrl('plan/list') . '&open_refill=1');
        break;

    case 'refill-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $code = Text::alphanumeric(_post('code'), "-_.,");
        $user = ORM::for_table('tbl_customers')->where('id', _post('id_customer'))->find_one();
        $v1 = ORM::for_table('tbl_voucher')->where_raw('BINARY code = ?', [$code])->where('status', 0)->find_one();

        run_hook('refill_customer'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->user = $user['username'];
                $v1->save();
                $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/list'), 'e', "Failed to refill account");
            }
        } else {
            r2(getUrl('plan/list'), 'e', Lang::T('Voucher Not Valid'));
        }
        break;
    case 'deposit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Refill Balance'));
        $ui->assign('xfooter', $select2_customer);
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('type', 'Balance')->find_many());
        } else {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->find_many());
        }
        run_hook('view_deposit'); #HOOK
        $ui->display('admin/plan/deposit.tpl');
        break;
    case 'deposit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $user = _post('id_customer');
        $amount = _post('amount');
        $plan = _post('id_plan');
        $note = _post('note');
        $svoucher = _req('svoucher');
        $c = ORM::for_table('tbl_customers')->find_one($user);
        if (App::getVoucherValue($svoucher)) {
            $in = ORM::for_table('tbl_transactions')->find_one(App::getVoucherValue($svoucher));
            Package::createInvoice($in);
            $ui->display('admin/plan/invoice.tpl');
            die();
        }

        run_hook('deposit_customer'); #HOOK
        if (!empty($user) && strlen($amount) > 0 && $amount != 0) {
            $plan = [];
            $plan['name_plan'] = Lang::T('Balance');
            $plan['price'] = $amount;
            $trxId = Package::rechargeBalance($c, $plan, "Deposit", $admin['fullname'], $note);
            if ($trxId > 0) {
                $in = ORM::for_table('tbl_transactions')->find_one($trxId);
                Package::createInvoice($in);
                if (!empty($svoucher)) {
                    App::setVoucher($svoucher, $trxId);
                }
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/refill'), 'e', "Failed to refill account");
            }
        } else if (!empty($user) && !empty($plan)) {
            $p = ORM::for_table('tbl_plans')->find_one($plan);
            $trxId = Package::rechargeBalance($c, $p, "Deposit", $admin['fullname'], $note);
            if ($trxId > 0) {
                $in = ORM::for_table('tbl_transactions')->find_one($trxId);
                Package::createInvoice($in);
                if (!empty($svoucher)) {
                    App::setVoucher($svoucher, $trxId);
                }
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/refill'), 'e', "Failed to refill account");
            }
        } else {
            r2(getUrl('plan/refill'), 'e', "All field is required");
        }
        break;
    case 'extend':
        $id = $routes[2];
        $days = (int) $routes[3];
        $svoucher = _get('svoucher');
        if (App::getVoucherValue($svoucher)) {
            r2(getUrl('plan'), 's', "Extend already done");
        }
        $tur = ORM::for_table('tbl_user_recharges')->find_one($id);
        if (!$tur) {
            r2(getUrl('plan'), 'e', "Data Not Found");
        }
        if (!$tur) {
            r2(getUrl('plan'), 'e', "Data Not Found");
        }
        $status = $tur['status'];
        if (strtotime($tur['expiration'] . ' ' . $tur['time']) > time()) {
            // not expired yet, extend from current expiration
            $expiration = date('Y-m-d', strtotime($tur['expiration'] . " +$days day"));
        } else {
            // expired, extend from today
            $expiration = date('Y-m-d', strtotime(" +$days day"));
        }
        App::setVoucher($svoucher, $id);
        $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
        if ($c) {
            $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
            if ($p) {
                $dvc = Package::getDevice($p);
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        global $isChangePlan;
                        $isChangePlan = true;
                        try {
                            (new $p['device'])->add_customer($c, $p);
                        } catch (Throwable $e) {
                            r2(getUrl('plan'), 'e', WifiZoneSecurity::safeExceptionMessage($e));
                        }
                    } else {
                        throw new Exception(Lang::T("Devices Not Found"));
                    }
                }
                $tur->expiration = $expiration;
                $tur->status = "on";
                $tur->save();
            } else {
                r2(getUrl('plan'), 'e', "Plan not found");
            }
        } else {
            r2(getUrl('plan'), 'e', "Customer not found");
        }
        Message::sendTelegram("#u$tur[username] #id$tur[customer_id]  #extend by $admin[fullname] #" . $p['type'] . " \n" . $p['name_plan'] .
            "\nLocation: " . $p['routers'] .
            "\nCustomer: " . $c['fullname'] .
            "\nNew Expired: " . Lang::dateAndTimeFormat($expiration, $tur['time']));
        _log("$admin[fullname] extend Customer $tur[customer_id] $tur[username] #$tur[customer_id] for $days days", $admin['user_type'], $admin['id']);
        r2(getUrl('plan'), 's', "Extend until $expiration");
        break;
    default:
        // ১. শুরুতেই 'show' বা লিমিট ভ্যালু রিসিভ করা
        $show = _req('show');
        // যদি ভুল করে ভ্যালু না আসে বা ড্যাশবোর্ড থেকে রিডাইরেক্ট হয়ে আসে
if (empty($show) || $show == '') {
    $show = 25; 
}
        if (empty($show)) { 
            $show = 25; 
        } elseif ($show == 'all') {
            $show = 999999; // 'all' সিলেক্ট করলে সব ডেটা এক পেজে দেখানোর জন্য
        }

        $ui->assign('_title', Lang::T('Customer List'));

        Package::processExpiredRecharges(['silent' => true, 'min_interval' => 60]);
        
        // ডাটা রিসিভ করা (URL বা Form থেকে)
        // প্যাগিনেশনে যেন সার্চ ডেটা না হারায় তাই _req ব্যবহার করা হলো
        $search = _req('search'); 
        $status = _req('status');
        $router = _req('router');
        $plan   = _req('plan');
        $type   = _req('type'); // Hotspot বা PPPoE

        // প্যাগিনেশন লিঙ্কের জন্য URL তৈরি (এখানে &show যুক্ত করা হয়েছে)
        $append_url = "&search=" . urlencode($search)
                    . "&status=" . urlencode($status)
                    . "&router=" . urlencode($router)
                    . "&plan="   . urlencode($plan)
                    . "&type="   . urlencode($type)
                    . "&show="   . ($show == 999999 ? 'all' : $show);
            
        $ui->assign('append_url', $append_url);
        $ui->assign('plan', $plan);
        $ui->assign('status', $status);
        $ui->assign('router', $router);
        $ui->assign('search', $search);
        $ui->assign('type', $type);
        
        // টেমপ্লেটে ড্রপডাউন ভ্যালু পাঠানোর জন্য (all হলে all পাঠাবে, না হলে সংখ্যা)
        $ui->assign('show', ($show == 999999 ? 'all' : $show)); 

        // রাউটার এবং প্ল্যান ড্রপডাউন ডাটা
        $ui->assign('routers', array_column(ORM::for_table('tbl_user_recharges')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));

        $plns = ORM::for_table('tbl_user_recharges')->distinct()->select("plan_id")->findArray();
        $ids = array_column($plns, 'plan_id');
        if (count($ids)) {
            $ui->assign('plans', ORM::for_table('tbl_plans')->select("id")->select('name_plan')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('plans', []);
        }

        // মূল কুয়েরি
        $active_count = (int) plan_list_base_query($admin, $search, $router, $plan, $type, 'on', false)->count();
        $expired_count = (int) plan_list_base_query($admin, $search, $router, $plan, $type, 'off', false)->count();
        $ui->assign('active_count', $active_count);
        $ui->assign('expired_count', $expired_count);
        $ui->assign('total_count', $active_count + $expired_count);

        $query = plan_list_base_query($admin, $search, $router, $plan, $type, $status);
        $query->order_by_desc('tbl_user_recharges.id');

        // প্যাগিনেশন এবং ডাটা ফেচ (এখানে ডায়নামিক $show ব্যবহার করা হয়েছে)
        $d = Paginator::findMany($query, [
            'search' => $search, 
            'status' => $status, 
            'type'   => $type,
            'router' => $router,
            'plan'   => $plan,
            'show'   => ($show == 999999 ? 'all' : $show)
        ], $show, $append_url);

        $rows = [];
        foreach ($d as $row) {
            $item = is_array($row) ? $row : $row->as_array();
            $item['is_active'] = Package::isRechargeActive($item);
            $rows[] = $item;
        }
        $d = $rows;
        
        run_hook('view_list_billing'); #HOOK
        
        $ui->assign('d', $d);
        if ($_c['disable_voucher'] != 'yes') {
            $ui->assign('xfooter', $select2_customer);
            $ui->assign('voucher_refill_enabled', true);
            $ui->assign('open_refill', _get('open_refill') == '1');
        } else {
            $ui->assign('voucher_refill_enabled', false);
            $ui->assign('open_refill', false);
        }
        $ui->display('admin/plan/active.tpl');
        break;
}
