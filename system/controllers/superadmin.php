<?php

_admin();
$ui->assign('_system_menu', 'isp_reseller');
if ($admin['user_type'] !== 'SuperAdmin') {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = $routes['1'] ?? 'admin-subscriptions';
AdminSubscription::ensureSchema();

switch ($action) {
    case 'isp-settings':
        $ui->assign('_title', Lang::T('ISP Settings'));
        $ui->assign('isp_settings', AdminSubscription::settings());
        $ui->assign('settings_rows', ORM::for_table('isp_settings')->order_by_desc('updated_at')->find_many());
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/superadmin/isp-settings.tpl');
        break;

    case 'isp-settings-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('superadmin/isp-settings'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        AdminSubscription::updateSetting('business_price', _post('business_price'), (int) $admin['id']);
        AdminSubscription::updateSetting('pro_price_per_router', _post('pro_price_per_router'), (int) $admin['id']);
        r2(getUrl('superadmin/isp-settings'), 's', Lang::T('Settings Saved Successfully'));
        break;

    case 'admin-subscriptions':
        $ui->assign('_title', Lang::T('Admin Subscriptions'));
        $ui->assign('subscriptions', AdminSubscription::allWithAdmins());
        $ui->assign('subscription_stats', AdminSubscription::platformStats());
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/superadmin/admin-subscriptions.tpl');
        break;

    case 'instances':
        $status = _req('status');
        $ui->assign('_title', Lang::T('Instances'));
        $ui->assign('instances', AdminSubscription::instances($status));
        $ui->assign('subscription_stats', AdminSubscription::platformStats());
        $ui->assign('status_filter', $status);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/superadmin/instances.tpl');
        break;

    case 'subscription-action':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('superadmin/admin-subscriptions'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        $adminId = (int) _post('admin_id');
        if (_post('do') === 'suspend') {
            AdminSubscription::suspend($adminId);
            r2(getUrl('superadmin/admin-subscriptions'), 's', Lang::T('Subscription suspended'));
        }
        if (_post('do') === 'extend') {
            AdminSubscription::extend($adminId);
            r2(getUrl('superadmin/admin-subscriptions'), 's', Lang::T('Subscription extended'));
        }
        r2(getUrl('superadmin/admin-subscriptions'), 'e', Lang::T('Invalid request'));
        break;

    default:
        r2(getUrl('superadmin/admin-subscriptions'));
}
