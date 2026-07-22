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
        $settings = AdminSubscription::settings();
        $ui->assign('isp_settings', $settings);
        $ui->assign('setting_labels', AdminSubscription::settingLabels());
        $ui->assign('settings_rows', ORM::for_table('isp_settings')->order_by_desc('updated_at')->find_many());
        $ui->assign('isp_settings_updated_at', AdminSubscription::settingsUpdatedAt());
        AdminSubscription::clearPricingUiCache($ui);
        $ui->assign('csrf_token', Csrf::getToken());
        $ui->display('admin/superadmin/isp-settings.tpl');
        break;

    case 'isp-settings-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(
                getUrl('superadmin/isp-settings'),
                'e',
                'Session expirée ou onglet obsolète. Rechargez la page ISP Settings puis enregistrez à nouveau.'
            );
        }
        $businessPrice = max(0, (float) str_replace([' ', ','], '', (string) _post('business_price')));
        $proPrice = max(0, (float) str_replace([' ', ','], '', (string) _post('pro_price_per_router')));
        if ($businessPrice <= 0 || $proPrice <= 0) {
            r2(getUrl('superadmin/isp-settings'), 'e', 'Les tarifs Business et Pro doivent être supérieurs à 0 F CFA.');
        }
        AdminSubscription::updateSetting('business_price', $businessPrice, (int) $admin['id']);
        AdminSubscription::updateSetting('pro_price_per_router', $proPrice, (int) $admin['id']);
        AdminSubscription::clearPricingUiCache($ui);
        Csrf::generateAndStoreToken();
        $saved = AdminSubscription::settings();
        r2(
            getUrl('superadmin/isp-settings'),
            's',
            'Tarifs enregistrés — Forfait Business : '
            . number_format($saved['business_price'], 0, ',', ' ')
            . ' F CFA · Forfait Pro : '
            . number_format($saved['pro_price_per_router'], 0, ',', ' ')
            . ' F CFA / routeur'
        );
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
        SuperAdminNotifications::markInstanceAlertsRead();
        $status = _req('status');
        $ui->assign('_title', Lang::T('Instances'));
        $ui->assign('instances', AdminSubscription::instances($status));
        $ui->assign('subscription_stats', AdminSubscription::platformStats());
        $ui->assign('status_filter', $status);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/superadmin/instances.tpl');
        break;

    case 'notifications':
        if (!empty(_get('testTg'))) {
            $sample = SuperAdminNotifications::formatInstanceCreatedTelegram([
                'full_name' => 'Jean Dupont',
                'email' => 'admin@isp.com',
                'business_name' => 'Mombasa Fiber',
                'country_code' => 'CM',
                'phone_number' => '677123456',
                'slug' => 'wizfiber',
            ]);
            $result = SuperAdminNotifications::sendTelegramMessage($sample);
            r2(
                getUrl('superadmin/notifications'),
                's',
                Lang::T('Test Telegram has been send') . '<br>Result: ' . ($result ?: '—')
            );
        }
        $ui->assign('_system_menu', 'superadmin_notifications');
        $ui->assign('_title', 'Telegram Config');
        $ui->assign('telegram_settings', SuperAdminNotifications::telegramSettings());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/superadmin/notifications.tpl');
        break;

    case 'notifications-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('superadmin/notifications'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        SuperAdminNotifications::saveTelegramSettings(_post('telegram_bot'), _post('telegram_chat_id'));
        Csrf::generateAndStoreToken();
        r2(getUrl('superadmin/notifications'), 's', Lang::T('Settings Saved Successfully'));
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

    case 'referrals':
        Referral::ensureSchema();
        $ui->assign('_title', 'Parrainage — Retraits');
        $ui->assign('_system_menu', 'superadmin_referrals');
        $ui->assign('referral_platform_stats', Referral::platformStats());
        $ui->assign('referral_pending_withdrawals', Referral::allPendingWithdrawals());
        $ui->assign('referral_all_withdrawals', Referral::allWithdrawals(100));
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/superadmin/referrals.tpl');
        break;

    case 'referral-action':
        Referral::ensureSchema();
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('superadmin/referrals'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        $withdrawalId = (int) _post('withdrawal_id');
        $note = trim((string) _post('note'));
        try {
            if (_post('do') === 'approve') {
                Referral::approveWithdrawal($withdrawalId, $note);
                Csrf::generateAndStoreToken();
                r2(getUrl('superadmin/referrals'), 's', 'Retrait approuvé avec succès.');
            }
            if (_post('do') === 'reject') {
                Referral::rejectWithdrawal($withdrawalId, $note);
                Csrf::generateAndStoreToken();
                r2(getUrl('superadmin/referrals'), 's', 'Retrait rejeté. Le solde a été remboursé.');
            }
        } catch (RuntimeException $e) {
            r2(getUrl('superadmin/referrals'), 'e', $e->getMessage());
        }
        r2(getUrl('superadmin/referrals'), 'e', Lang::T('Invalid request'));
        break;

    default:
        r2(getUrl('superadmin/admin-subscriptions'));
}
