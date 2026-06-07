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
    require_once $GLOBALS['WIDGET_PATH'] . DIRECTORY_SEPARATOR . 'customer_expired.php';
    $ui->assign('customer_expiry_widget', (new customer_expired())->getWidget());
    $ui->display('admin/monitoring_expiry.tpl');
    return;
}

$ui->assign('_title', Lang::T('Monitoring'));
$ui->assign('_system_menu', 'monitoring');

if (DemoShowcase::isActive($admin)) {
    DemoShowcase::applyMonitoring($ui);
    require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_registered_customers.php';
    $ui->assign('monthly_registered_widget', (new graph_monthly_registered_customers())->getWidget());
    $ui->display('admin/monitoring.tpl');
    return;
}

$customerQuery = ORM::for_table('tbl_customers');
if ($isAdmin) {
    $customerQuery->where('created_by', $adminId);
}
$c_all = $customerQuery->count();

$hotspotQuery = ORM::for_table('tbl_user_recharges')->where_raw("LOWER(type) = 'hotspot'")->select('customer_id')->distinct();
if ($isAdmin) {
    $hotspotQuery->where('admin_id', $adminId);
}
$h_all = count($hotspotQuery->find_array());

$pppoeQuery = ORM::for_table('tbl_user_recharges')->where_raw("LOWER(type) = 'pppoe'")->select('customer_id')->distinct();
if ($isAdmin) {
    $pppoeQuery->where('admin_id', $adminId);
}
$p_all = count($pppoeQuery->find_array());

$h_act_q = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'Hotspot');
if ($isAdmin) {
    $h_act_q->where('admin_id', $adminId);
}
$h_act = $h_act_q->count();

$p_act_q = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'PPPoE');
if ($isAdmin) {
    $p_act_q->where('admin_id', $adminId);
}
$p_act = $p_act_q->count();

$ui->assign('c_all', $c_all ?: 0);
$ui->assign('h_all', $h_all ?: 0);
$ui->assign('p_all', $p_all ?: 0);
$ui->assign('h_act', $h_act ?: 0);
$ui->assign('p_act', $p_act ?: 0);
require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_registered_customers.php';
$ui->assign('monthly_registered_widget', (new graph_monthly_registered_customers())->getWidget());
$ui->display('admin/monitoring.tpl');
