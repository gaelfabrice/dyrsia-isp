<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

$isSubscriptionPage = isset($routes['1']) && in_array($routes['1'], ['subscription', 'subscription-post'], true);

if (Admin::getID() && !$isSubscriptionPage) {
    $slug = $_GET['tenant'] ?? ($_SESSION['tenant_slug'] ?? '');
    if ($slug !== '' && Tenant::bootstrapBySlug($slug)) {
        r2(Tenant::dashboardUrl($slug), "s", Lang::T("You are already logged in"));
    }
    r2(getUrl('dashboard'), "s", Lang::T("You are already logged in"));
}

$tenantSlugParam = isset($_GET['tenant']) ? Tenant::normalizeSlug($_GET['tenant']) : '';
if ($tenantSlugParam !== '') {
    $tenantForLogin = Tenant::findBySlug($tenantSlugParam);
    if ($tenantForLogin) {
        Tenant::setCurrent($tenantForLogin);
        $ui->assign('tenant_login', $tenantForLogin->as_array());
        $ui->assign('tenant_dashboard_url', Tenant::dashboardUrl($tenantForLogin->slug));
    }
}

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

switch ($do) {
    case 'subscription':
        _admin();
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        AdminSubscription::ensureSchema();
        $subscription = AdminSubscription::getForAdmin((int) $admin['id']);
        $settings = AdminSubscription::settings();
        $ui->assign('_title', Lang::T('Admin Subscription'));
        $ui->assign('_system_menu', 'isp_reseller');
        $ui->assign('subscription', $subscription);
        $ui->assign('subscription_days_remaining', AdminSubscription::daysRemaining($subscription->status === 'trial' ? $subscription->trial_end : $subscription->subscription_end));
        $ui->assign('subscription_settings', $settings);
        $ui->assign('subscription_stats', AdminSubscription::statsForAdmin((int) $admin['id']));
        $ui->assign('subscription_invoices', AdminSubscription::invoicesForAdmin((int) $admin['id']));
        $ui->assign('subscription_payments', AdminSubscription::paymentsForAdmin((int) $admin['id']));
        $ui->assign('router_count', AdminSubscription::routerCount((int) $admin['id']));
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/subscription.tpl');
        break;

    case 'subscription-post':
        _admin();
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('admin/subscription'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            AdminSubscription::subscribe((int) $admin['id'], _post('plan_type'), _post('routers_count'));
            r2(getUrl('admin/subscription'), 's', Lang::T('Subscription activated successfully'));
        } catch (Exception $e) {
            r2(getUrl('admin/subscription'), 'e', $e->getMessage());
        }
        break;

    case 'post':
        $username = _post('username');
        $password = _post('password');
        //csrf token
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            _alert(Lang::T('Invalid or Expired CSRF Token') . ".", 'danger', "admin");
        }
        run_hook('admin_login'); #HOOK
        if ($username != '' and $password != '') {
            if (!WifiZoneSecurity::enforceLoginRateLimit('admin_login', $username)) {
                _log($username . ' ' . Lang::T('Failed Login') . ' [rate limit]', 'Admin');
                _alert(Lang::T('Too_many_attempts__Please_try_again_later_'), 'danger', "admin");
            }
            $d = ORM::for_table('tbl_users')->where('username', $username)->find_one();
            if (!$d && Validator::Email($username)) {
                $d = ORM::for_table('tbl_users')->where('email', $username)->find_one();
            }
            if ($d) {
                $d_pass = $d['password'];
                if ($d['status'] != 'Active') {
                    _alert(Lang::T('This account status') . ' : ' . Lang::T($d['status']), 'danger', "admin");
                }
                if (Password::_verify($password, $d_pass) == true) {
                    $adminId = (int) $d['id'];
                    if ($adminId <= 0) {
                        _alert(Lang::T('Invalid Username or Password') . '.', 'danger', "admin");
                    }
                    $newHash = Password::upgradeStoredHash($password, $d_pass);
                    if ($newHash !== null) {
                        $d->password = $newHash;
                    }
                    $_SESSION['aid'] = $adminId;
                    $_SESSION['user_type'] = $d['user_type'];
                    $token = Admin::setCookie($adminId);
                    $d->last_login = date('Y-m-d H:i:s');
                    $d->save();
                    _log($username . ' ' . Lang::T('Login Successful'), $d['user_type'], $adminId);
                    if ($isApi) {
                        if ($token) {
                            showResult(true, Lang::T('Login Successful'), ['token' => "a." . $token]);
                        } else {
                            showResult(false, Lang::T('Invalid Username or Password'));
                        }
                    }
                    if (!empty($_SESSION['tenant_slug'])) {
                        _alert(Lang::T('Login Successful'), 'success', 'dashboard_tenant=' . $_SESSION['tenant_slug']);
                    } else {
                        _alert(Lang::T('Login Successful'), 'success', "dashboard");
                    }
                } else {
                    _log($username . ' ' . Lang::T('Failed Login'), $d['user_type']);
                    _alert(Lang::T('Invalid Username or Password') . ".", 'danger', "admin");
                }
            } else {
                _alert(Lang::T('Invalid Username or Password') . "..", 'danger', "admin");
            }
        } else {
            _alert(Lang::T('Invalid Username or Password') . "...", 'danger', "admin");
        }

        break;
    default:
        run_hook('view_login'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/admin/login.tpl');
        break;
}
