<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

_admin();
$isAdmin = ($admin['user_type'] != 'SuperAdmin');
$adminId = intval($admin['id']);
$ui->assign('_title', Lang::T('Dashboard'));
$ui->assign('_admin', $admin);

if (isset($_GET['refresh'])) {
    $files = scandir($CACHE_PATH);
    foreach ($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (is_file($CACHE_PATH . DIRECTORY_SEPARATOR . $file) && $ext == 'temp') {
            unlink($CACHE_PATH . DIRECTORY_SEPARATOR . $file);
        }
    }
    r2(getUrl('dashboard'), 's', 'Data Refreshed');
}

$tipeUser = _req("user");
if (empty($tipeUser)) {
    $tipeUser = 'Admin';
}
$ui->assign('tipeUser', $tipeUser);

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

$current_date = date('Y-m-d');
$ui->assign('start_date', $start_date);
$ui->assign('current_date', $current_date);

$tipeUser = $admin['user_type'];
if (in_array($tipeUser, ['SuperAdmin', 'Admin'])) {
    $tipeUser = 'Admin';
}

$widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', $tipeUser)->order_by_asc("orders")->findArray();
$uniqueWidgets = [];
$seenWidgets = [];
foreach ($widgets as $widget) {
    if (in_array($widget['widget'], ['graph_monthly_registered_customers', 'graph_monthly_sales', 'info_payment_gateway', 'graph_customers_insight'])) {
        continue;
    }
    $widgetKey = trim($widget['widget']) . ':' . intval($widget['position']);
    if (isset($seenWidgets[$widgetKey])) {
        continue;
    }
    $seenWidgets[$widgetKey] = true;
    $uniqueWidgets[] = $widget;
}
$widgets = $uniqueWidgets;
// Dashboard admin filter
$ui->assign('admin_filter_id', 0);

if ($admin['user_type'] != 'SuperAdmin') {
    $ui->assign('admin_filter_id', $admin['id']);
}
$count = count($widgets);
for ($i = 0; $i < $count; $i++) {
    try{
        if(file_exists($WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'].".php")){
            require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'].".php";
            $widgets[$i]['content'] = (new $widgets[$i]['widget'])->getWidget($widgets[$i]);
        }else{
            $widgets[$i]['content'] = "Widget not found";
        }
    } catch (Throwable $e) {
        $widgets[$i]['content'] = $e->getMessage();
    }
}

$ui->assign('widgets', $widgets);

/* ================= WALLET BALANCE LOGIC (এখানে বসানো হয়েছে) ================= */

if ($admin['user_type'] == 'SuperAdmin') {
    // যদি সুপার এডমিন হয়, তবে সব এডমিনের মোট ব্যালেন্স দেখাবে
    $w_balance = ORM::for_table('admin_wallet')->sum('balance') ?: 0;
} else {
    // যদি নরমাল এডমিন হয়, তবে শুধুমাত্র তার নিজের ব্যালেন্স দেখাবে
    $wallet = ORM::for_table('admin_wallet')->where('admin_id', $adminId)->find_one();
    $w_balance = ($wallet) ? $wallet->balance : 0;
}

// স্মার্টলি টেমপ্লেটে ডাটা পাঠানো হচ্ছে
$ui->assign('w_balance', $w_balance);

/* ================= WALLET COMMISSION ================= */

if ($admin['user_type'] == 'SuperAdmin') {
    $w_commission = ORM::for_table('admin_wallet')->sum('commission_balance') ?: 0;
} else {
    $wallet = ORM::for_table('admin_wallet')
        ->where('admin_id', $adminId)
        ->find_one();

    $w_commission = ($wallet) ? $wallet->commission_balance : 0;
}

$ui->assign('w_commission', $w_commission);

/* ========================================================================= */

if ($admin['user_type'] == 'Admin') {
    $adminSubscription = AdminSubscription::getForAdmin((int) $admin['id']);
    $adminSubscriptionDate = $adminSubscription->status === 'trial' ? $adminSubscription->trial_end : $adminSubscription->subscription_end;
    $ui->assign('admin_subscription', $adminSubscription);
    $ui->assign('admin_subscription_days_remaining', AdminSubscription::daysRemaining($adminSubscriptionDate));
} else {
    $ui->assign('admin_subscription', null);
    $ui->assign('admin_subscription_days_remaining', 0);
}

run_hook('view_dashboard'); #HOOK
$ui->display('admin/dashboard.tpl');