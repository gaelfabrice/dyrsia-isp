<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

$isSubscriptionPage = isset($routes['1']) && in_array($routes['1'], ['subscription', 'subscription-post', 'subscription-pay', 'subscription-verify', 'subscription-demo-ack'], true);

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
        if (_get('demo_ack') == '1') {
            unset($_SESSION['signup_checkout_plan']);
        }
        $checkoutPlan = '';
        if (in_array((string) _get('plan'), ['business', 'pro'], true)) {
            $checkoutPlan = (string) _get('plan');
        } elseif (in_array((string) ($_SESSION['signup_checkout_plan'] ?? ''), ['business', 'pro'], true)) {
            $checkoutPlan = (string) $_SESSION['signup_checkout_plan'];
        }
        $autoCheckout = $subscription->status === 'trial'
            && $checkoutPlan !== ''
            && ((_get('checkout') == '1') || !empty($_SESSION['signup_checkout_plan']));
        $ui->assign('checkout_plan', $checkoutPlan);
        $ui->assign('auto_checkout', $autoCheckout);
        $ui->assign('demo_trial_days', AdminSubscription::demoTrialDays());
        $ui->assign('subscription_demo_ack_url', getUrl('admin/subscription-demo-ack'));
        $ui->assign('_title', Lang::T('Admin Subscription'));
        $ui->assign('_system_menu', 'isp_reseller');
        $ui->assign('subscription', $subscription);
        $ui->assign('subscription_days_remaining', AdminSubscription::daysRemaining($subscription->status === 'trial' ? $subscription->trial_end : $subscription->subscription_end));
        $ui->assign('subscription_settings', $settings);
        $ui->assign('subscription_stats', AdminSubscription::statsForAdmin((int) $admin['id']));
        $ui->assign('subscription_invoices', AdminSubscription::invoicesForAdmin((int) $admin['id']));
        $ui->assign('subscription_payments', AdminSubscription::paymentsForAdmin((int) $admin['id']));
        $ui->assign('router_count', AdminSubscription::routerCount((int) $admin['id']));
        $ui->assign('admin_phone', trim((string) ($admin['phone'] ?? '')));
        $ui->assign('pending_payment_id', (int) (_get('payment_id') ?? 0));
        $ui->assign('subscription_verify_url', getUrl('admin/subscription-verify'));
        global $config;
        $ui->assign('campay_configured', !empty($config['campay_username']) && !empty($config['campay_password']));
        $adminPhoneLocal = preg_replace('/^237/', '', trim((string) ($admin['phone'] ?? '')));
        $ui->assign('admin_phone_local', $adminPhoneLocal);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/subscription.tpl');
        break;

    case 'subscription-pay':
    case 'subscription-post':
        _admin();
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('admin/subscription'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        global $PAYMENTGATEWAY_PATH;
        require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'campay.php';
        try {
            $phone = trim(_post('phone'));
            if ($phone === '') {
                $phone = trim((string) ($admin['phone'] ?? ''));
            }
            if ($phone === '') {
                r2(getUrl('admin/subscription'), 'e', Lang::T('Phone number is required for Mobile Money payment'));
            }
            if (strlen(preg_replace('/\D/', '', $phone)) >= 9) {
                $adminRow = ORM::for_table('tbl_users')->find_one((int) $admin['id']);
                if ($adminRow && trim((string) $adminRow->phone) === '') {
                    $adminRow->phone = $phone;
                    $adminRow->save();
                }
            }
            $ctx = AdminSubscription::initiatePayment(
                (int) $admin['id'],
                _post('plan_type'),
                _post('routers_count')
            );
            campay_admin_subscription_collect($ctx, $admin, $phone);
        } catch (Exception $e) {
            r2(getUrl('admin/subscription'), 'e', $e->getMessage());
        }
        break;

    case 'subscription-demo-ack':
        _admin();
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        unset($_SESSION['signup_checkout_plan']);
        if (_get('ajax') == '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'message' => 'Mode Démo conservé.']);
            exit;
        }
        r2(
            getUrl('admin/subscription&demo_ack=1'),
            'i',
            'Votre compte reste en Mode Démo (' . AdminSubscription::demoTrialDays() . ' jours). Vous pouvez souscrire à tout moment.'
        );
        break;

    case 'subscription-verify':
        _admin();
        if ($admin['user_type'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }
        global $PAYMENTGATEWAY_PATH;
        require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'campay.php';
        header('Content-Type: application/json');
        echo json_encode(campay_admin_subscription_check_status((int) _get('payment_id'), (int) $admin['id']));
        exit;

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
            if (!$d && strtolower(trim($username)) === DemoShowcase::USERNAME) {
                $d = ORM::for_table('tbl_users')->where('username', DemoShowcase::USERNAME)->find_one();
            }
            if (!$d && Validator::Email($username)) {
                $d = ORM::for_table('tbl_users')->where('email', $username)->find_one();
            }
            if ($d) {
                $d_pass = $d['password'];
                if ($d['status'] != 'Active' && !DemoShowcase::isShowcaseUser($d->as_array())) {
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
                    DemoShowcase::onLogin($adminId);
                    try {
                        $token = Admin::setCookie($adminId);
                        $d->last_login = date('Y-m-d H:i:s');
                        $d->save();
                        _log($username . ' ' . Lang::T('Login Successful'), $d['user_type'], $adminId);
                    } catch (Throwable $e) {
                        error_log('wifizone admin login post-save: ' . $e->getMessage());
                        $token = '';
                    }
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
