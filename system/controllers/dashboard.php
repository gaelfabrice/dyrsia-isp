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

$action = $routes['1'] ?? '';
if ($action === 'update-mac' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!Csrf::check(_post('csrf_token'))) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    $mac = strtoupper(trim((string) (_post('mac') ?: '')));
    $username = trim((string) (_post('username') ?: ''));
    if ($mac === '' || $username === '') {
        echo json_encode(['success' => false, 'message' => Lang::T('Please fill both MAC Address and Username')]);
        exit;
    }
    if (!preg_match('/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/', $mac)) {
        echo json_encode(['success' => false, 'message' => Lang::T('Invalid MAC address format')]);
        exit;
    }
    $customer = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => Lang::T('Customer not found')]);
        exit;
    }
    if ($admin['user_type'] !== 'SuperAdmin' && (int) $customer->created_by !== (int) $admin['id']) {
        echo json_encode(['success' => false, 'message' => Lang::T('Access Denied')]);
        exit;
    }
    $field = ORM::for_table('tbl_customers_fields')
        ->where('customer_id', (int) $customer->id)
        ->where('field_name', 'mac')
        ->find_one();
    if (!$field) {
        $field = ORM::for_table('tbl_customers_fields')->create();
        $field->customer_id = (int) $customer->id;
        $field->field_name = 'mac';
    }
    $field->field_value = $mac;
    $field->save();
    _log('MAC updated for ' . $username . ' → ' . $mac, 'Customer', (int) $admin['id']);
    echo json_encode(['success' => true, 'message' => Lang::T('MAC address updated successfully')]);
    exit;
}

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

$tipeUser = $admin['user_type'];
if (in_array($tipeUser, ['SuperAdmin', 'Admin'])) {
    $tipeUser = 'Admin';
}

/* ================= WALLET BALANCE LOGIC ================= */

$w_balance = 0;
$w_commission = 0;

if (DemoShowcase::isActive($admin)) {
    $demoStats = DemoShowcase::stats();
    $w_balance = $demoStats['w_balance'];
    $w_commission = $demoStats['w_commission'];
} else {
    WifiZoneWallet::ensureSchema();
    try {
        if ($admin['user_type'] == 'SuperAdmin') {
            $w_balance = ORM::for_table('admin_wallet')->sum('balance') ?: 0;
            $w_commission = ORM::for_table('admin_wallet')->sum('commission_balance') ?: 0;
        } else {
            $wallet = ORM::for_table('admin_wallet')->where('admin_id', $adminId)->find_one();
            if ($wallet) {
                $w_balance = $wallet->balance;
                $w_commission = $wallet->commission_balance;
            }
        }
    } catch (Exception $e) {
        $w_balance = 0;
        $w_commission = 0;
    }
}

$ui->assign('w_balance', $w_balance);
$ui->assign('w_commission', $w_commission);

/* ========================================================================= */

if ($admin['user_type'] == 'Admin') {
    $adminSubscription = AdminSubscription::getForAdmin((int) $admin['id']);
    $adminSubscriptionDate = $adminSubscription->status === 'trial' ? $adminSubscription->trial_end : $adminSubscription->subscription_end;
    $ui->assign('admin_subscription', $adminSubscription);
    $ui->assign('admin_subscription_days_remaining', AdminSubscription::daysRemaining($adminSubscriptionDate));
    $ui->assign('admin_demo_trial_days', AdminSubscription::demoTrialDays());
    $ui->assign('subscription_settings', AdminSubscription::settings());
} else {
    $ui->assign('admin_subscription', null);
    $ui->assign('admin_subscription_days_remaining', 0);
    $ui->assign('admin_demo_trial_days', AdminSubscription::demoTrialDays());
    $ui->assign('subscription_settings', AdminSubscription::settings());
}

$reset_day = $config['reset_day'];
if (empty($reset_day)) {
    $reset_day = 1;
}
if (date('d') >= $reset_day) {
    $start_date = date('Y-m-' . $reset_day);
} else {
    $start_date = date('Y-m-' . $reset_day, strtotime('-1 MONTH'));
}
$current_date = date('Y-m-d');
$ui->assign('start_date', $start_date);
$ui->assign('end_date', $current_date);
$ui->assign('current_date', $current_date);
$ui->assign('tipeUser', $tipeUser);

$logPage = isset($_GET['log_page']) ? max((int) $_GET['log_page'], 1) : 1;
$command = DashboardCommand::gather($admin);
$activity = DashboardCommand::activityLogs($admin, $logPage, 10);
foreach ($command as $key => $value) {
    $ui->assign($key, $value);
}
$ui->assign('dashboard_log_base_url', getUrl('dashboard'));
foreach ($activity as $key => $value) {
    $ui->assign($key, $value);
}
$versionFile = dirname(__DIR__, 2) . '/version.json';
$appVersion = '2025.3.20';
if (is_readable($versionFile)) {
    $versionData = json_decode((string) file_get_contents($versionFile), true);
    if (!empty($versionData['version'])) {
        $appVersion = $versionData['version'];
    }
}
$ui->assign('app_version', $appVersion);

run_hook('view_dashboard'); #HOOK
$ui->display('admin/dashboard.tpl');