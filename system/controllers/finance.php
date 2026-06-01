<?php

_admin();
$ui->assign('_title', Lang::T('Finance'));
$ui->assign('_system_menu', 'finance');
$ui->assign('_admin', $admin);

$current_date = date('Y-m-d');
$start_date = date('Y-m-01');
$isAdmin = ($admin['user_type'] != 'SuperAdmin');
$adminId = intval($admin['id']);

$dailyQuery = ORM::for_table('tbl_transactions')
    ->where('recharged_on', $current_date)
    ->where_not_equal('method', 'Customer - Balance')
    ->where_not_equal('method', 'Recharge Balance - Administrator');

$monthlyQuery = ORM::for_table('tbl_transactions')
    ->where_not_equal('method', 'Customer - Balance')
    ->where_not_equal('method', 'Recharge Balance - Administrator')
    ->where_gte('recharged_on', $start_date)
    ->where_lte('recharged_on', $current_date);

if ($isAdmin) {
    $dailyQuery->where('admin_id', $adminId);
    $monthlyQuery->where('admin_id', $adminId);
}

$w_balance = 0;
$w_commission = 0;
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
}

$ui->assign('iday', $dailyQuery->sum('price') ?: 0);
$ui->assign('imonth', $monthlyQuery->sum('price') ?: 0);
$ui->assign('w_balance', $w_balance);
$ui->assign('w_commission', $w_commission);
require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_sales.php';
$ui->assign('monthly_sales_widget', (new graph_monthly_sales())->getWidget());
$ui->display('admin/finance.tpl');
