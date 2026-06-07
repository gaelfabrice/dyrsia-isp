<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)

 **/

try {
    require_once 'init.php';
} catch (Throwable $e) {
    die(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
} catch (Exception $e) {
    die(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

function _notify($msg, $type = 'e')
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

$ui = new Smarty();
$ui->assign('_kolaps', $_COOKIE['kolaps']);
if (!empty($config['theme']) && $config['theme'] != 'default') {
    $_theme = APP_URL . '/' . $UI_PATH . '/themes/' . $config['theme'];
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'theme' => File::pathFixer($UI_PATH . '/themes/' . $config['theme']),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
} else {
    $_theme = APP_URL . '/' . $UI_PATH . '/ui';
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
}
$ui->assign('_theme', $_theme);
$ui->addTemplateDir($PAYMENTGATEWAY_PATH . File::pathFixer('/ui/'), 'pg');
$ui->addTemplateDir($PLUGIN_PATH . File::pathFixer('/ui/'), 'plugin');
$ui->setCompileDir(File::pathFixer($UI_PATH . '/compiled/'));
$ui->setConfigDir(File::pathFixer($UI_PATH . '/conf/'));
$ui->setCacheDir(File::pathFixer($UI_PATH . '/cache/'));
$ui->assign('app_url', APP_URL);
$ui->assign('_domain', str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST)));
$ui->assign('_url', APP_URL . '/?_route=');
$ui->assign('_path', __DIR__);
$ui->assign('_c', $config);
$ui->assign('user_language', $_SESSION['user_language'] ?? $config['language'] ?? 'english');
$ui->assign('current_language', $config['language'] ?? 'english');
$ui->assign('wifizone_languages', WifiZoneCore::allowedLanguages());
$ui->assign('UPLOAD_PATH', str_replace($root_path, '',  $UPLOAD_PATH));
$ui->assign('CACHE_PATH', str_replace($root_path, '',  $CACHE_PATH));
$ui->assign('PAGES_PATH', str_replace($root_path, '',  $PAGES_PATH));
$ui->assign('_system_menu', 'dashboard');
if (!isset($_SESSION['csrf_token'])) {
    Csrf::generateAndStoreToken();
}
$ui->assign('csrf_token', Csrf::getToken());
try {
    AdminSubscription::ensureSchema();
    $ispSettings = AdminSubscription::settings();
    $ui->assign('isp_settings', $ispSettings);
    $ui->assign('subscription_settings', $ispSettings);
    $ui->assign('isp_settings_updated_at', AdminSubscription::settingsUpdatedAt());
} catch (Throwable $e) {
    $ui->assign('isp_settings', AdminSubscription::defaultSettings());
    $ui->assign('subscription_settings', AdminSubscription::defaultSettings());
}
$ui->registerPlugin('function', 'csrf_field', function () {
    return csrf_field();
});

function _msglog($type, $msg)
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

if (isset($_SESSION['notify'])) {
    $notify = $_SESSION['notify'];
    $ntype = $_SESSION['ntype'];
    $ui->assign('notify', $notify);
    $ui->assign('notify_t', $ntype);
    unset($_SESSION['notify']);
    unset($_SESSION['ntype']);
}

if (!isset($_GET['_route'])) {
    $req = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $len = strlen(ltrim(parse_url(APP_URL, PHP_URL_PATH), '/'));
    if ($len > 0) {
        $req = ltrim(substr($req, $len), '/');
    }
} else {
    // Routing Engine
    $req = _get('_route');
}

if ($req === 'index.php') {
    $req = '';
}

$tenantDashboardRoute = false;
if (preg_match('/^dashboard_tenant=([a-z0-9][a-z0-9-]*)$/i', $req, $tenantRouteMatch)) {
    $tenantDashboardRoute = true;
    if (!Tenant::bootstrapBySlug($tenantRouteMatch[1])) {
        if (empty($_SERVER['HTTP_SEC_FETCH_DEST']) || $_SERVER['HTTP_SEC_FETCH_DEST'] != 'document') {
            header('HTTP/1.0 404 Not Found');
            header('Content-Type: text/plain; charset=utf-8');
            echo '404 Tenant not found';
            die();
        }
        r2(getUrl('provision'), 'e', Lang::T('Instance not found. Deploy a new one below.'));
    }
    $req = 'dashboard';
}

$routes = explode('/', $req);
$ui->assign('_routes', $routes);
WifiZoneSecurity::blockDestructiveGetRequests($routes);
$handler = $routes[0];
if ($handler == '') {
    $handler = 'default';
}

Tenant::restoreFromSession();
$currentTenant = Tenant::current();
Tenant::applyLocaleConfig($currentTenant);
if ($currentTenant) {
    $ui->assign('wifizone_tenant', $currentTenant);
    $ui->assign('wifizone_tenant_slug', $currentTenant['slug']);
    $ui->assign('wifizone_tenant_name', $currentTenant['business_name']);
}
try {
    DemoShowcase::ensureAccount();
    WifiZoneWallet::ensureSchema();
    $admin = Admin::_info();
    if (Impersonate::isActive() && (Impersonate::info()['mode'] ?? '') === 'customer') {
        $admin = null;
        Impersonate::assignUi($ui);
    } elseif ($admin) {
        $admin = Impersonate::resolveActingAdmin($admin);
        $admin = Impersonate::adminToArray($admin);
        $ui->assign('_admin', $admin);
        Tenant::applyAdminBranding($admin);
        Impersonate::assignUi($ui);
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            $ui->assign('withdrawal_pending_count', Withdrawal::pendingCount());
            $ui->assign('withdrawal_notifications', Withdrawal::pendingNotifications(8));
        }
    }
    if ($currentTenant && $admin) {
        Tenant::validateAdminTenant($admin);
    } elseif ($currentTenant && !$admin && $tenantDashboardRoute) {
        $_SESSION['tenant_login_redirect'] = Tenant::dashboardUrl($currentTenant['slug']);
        r2(getUrl('admin') . '&tenant=' . urlencode($currentTenant['slug']), 'e', Lang::T('Please sign in to access your dashboard'));
    }
    DemoShowcase::bootstrapSession($admin ?? null);
    if ($admin) {
        if (DemoShowcase::isActive($admin)) {
            $routerActions = ['add', 'edit', 'delete', 'test-connection'];
            if ($handler === 'routers' && in_array((string) ($routes[1] ?? ''), $routerActions, true)) {
                DemoShowcase::assertRouterMutationAllowed();
            }
        }
        AdminSubscription::enforceSubscriptionGate($admin, $handler, $routes[1] ?? '');
    }
    $sys_render = $root_path . File::pathFixer('system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        $menus = array();
        // "name" => $name,
        // "admin" => $admin,
        // "position" => $position,
        // "function" => $function
        $ui->assign('_system_menu', $routes[0]);
        foreach ($menu_registered as $menu) {
            $menuName = trim($menu['name']);
            if (in_array($menuName, ['WifiZone', 'PPPoE Online', 'Hotspot Online', 'Add Customer', 'OLT Management', 'Conformité RGPD', 'Hotspot Payment Token', 'Admin Wallet', 'Hotspot System Settings'])) {
                continue;
            }
            if (in_array($menuName, ['FTTH'])) {
                $menu['position'] = 'PLANS';
            }
            if ($menu['admin'] && _admin(false)) {
                if (count($menu['auth']) == 0 || in_array($admin['user_type'], $menu['auth'])) {
                    $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . getUrl('plugin/' . $menu['function']) . '">';
                    if (!empty($menu['icon'])) {
                        $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                    }
                    if (!empty($menu['label'])) {
                        $menus[$menu['position']] .= '<span class="pull-right-container">';
                        $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
                    }
                    $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
                }
            } else if (!$menu['admin'] && _auth(false)) {
                $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . getUrl('plugin/' . $menu['function']) . '">';
                if (!empty($menu['icon'])) {
                    $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                }
                if (!empty($menu['label'])) {
                    $menus[$menu['position']] .= '<span class="pull-right-container">';
                    $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
                }
                $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
            }
        }
        foreach ($menus as $k => $v) {
            $ui->assign('_MENU_' . $k, $v);
        }
        unset($menus, $menu_registered);
        include($sys_render);
        if (function_exists('wifizone_log_admin_request')) {
            wifizone_log_admin_request($admin, $routes);
        }
    } else {
        if( empty($_SERVER["HTTP_SEC_FETCH_DEST"]) || $_SERVER["HTTP_SEC_FETCH_DEST"] != 'document' ){
            // header 404
            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/html; charset=utf-8");
            echo "404 Not Found";
            die();
        }else{
            r2(getUrl('login'));
        }
    }
} catch (Throwable $e) {
    WifiZoneSecurity::logException($e, __DIR__ . '/../system/uploads/runtime_error.log');
    $isProvisionAjax = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (_post('ajax') == '1')
        && (($routes[0] ?? '') === 'provision');
    if ($isProvisionAjax) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => Lang::T('Provisioning failed') . ': ' . WifiZoneSecurity::formatExceptionForDisplay($e),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    WifiZoneSecurity::renderExceptionPage($e, $ui);
}
