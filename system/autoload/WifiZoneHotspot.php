<?php

class WifiZoneHotspot
{
    public static function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return self::handleSignedPost();
        }
        if (WifiZoneCore::config('wifizone_hotspot_legacy_get', 'yes') === 'yes' && isset($_GET['username'], $_GET['password'], $_GET['ip'], $_GET['mac'], $_GET['router'])) {
            return self::processLogin(
                $_GET['username'],
                $_GET['password'],
                $_GET['ip'],
                $_GET['mac'],
                $_GET['router']
            );
        }
        hotspot_throwError(Lang::T('An error occurred while logging in: missing parameters'));
        return false;
    }

    public static function handleSignedPost()
    {
        $username = _post('username');
        $password = _post('password');
        $ip = _post('ip');
        $mac = _post('mac');
        $router = _post('router');
        $ts = (int) _post('ts');
        $sig = _post('sig');

        if ($username === '' || $router === '') {
            hotspot_throwError(Lang::T('An error occurred while logging in: missing parameters'));
            return false;
        }

        if (!self::checkRateLimit('hotspot_login', self::clientIp() . ':' . $mac, 30, 60)) {
            hotspot_throwError(Lang::T('Too many login attempts. Please try again later.'));
            return false;
        }

        if (WifiZoneCore::config('wifizone_hotspot_hmac', 'yes') === 'yes') {
            $secret = self::routerSecret($router);
            if ($secret === '') {
                hotspot_throwError(Lang::T('Router login secret not configured'));
                return false;
            }
            if (abs(time() - $ts) > 300) {
                hotspot_throwError(Lang::T('Login request expired'));
                return false;
            }
            $payload = $username . '|' . $ip . '|' . $mac . '|' . $router . '|' . $ts;
            $expected = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $sig)) {
                hotspot_throwError(Lang::T('Invalid login signature'));
                return false;
            }
        }

        return self::processLogin($username, $password, $ip, $mac, $router);
    }

    public static function processLogin($username, $password, $ip, $mac, $router_name)
    {
        $username = htmlspecialchars($username);
        $password = htmlspecialchars($password);
        $ip = htmlspecialchars($ip);
        $mac_address = htmlspecialchars($mac);
        $router_name = htmlspecialchars($router_name);

        $customer = ['username' => $username, 'password' => $password];
        $plan = ORM::for_table('tbl_user_recharges')->where('username', $username)->where('status', 'on')->find_one();
        if (!$plan) {
            hotspot_throwError(Lang::T('An error occurred while logging in: plan not found'));
            return false;
        }
        $p = ORM::for_table('tbl_plans')->where('routers', $router_name)->where('id', $plan->plan_id)->find_one();
        if (!$p) {
            hotspot_throwError(Lang::T('An error occurred while logging in: plan not found'));
            return false;
        }
        $dvc = Package::getDevice($p);
        if (!file_exists($dvc)) {
            hotspot_throwError(Lang::T('Devices Not Found'));
            return false;
        }
        require_once $dvc;
        try {
            (new $p['device'])->connect_customer($customer, $ip, $mac_address, $router_name);
            hotspot_loginSuccess(Lang::T('Login Request Successfully'));
            return true;
        } catch (Exception $e) {
            hotspot_throwError(Lang::T('An error occurred while logging in: ') . $e->getMessage());
            return false;
        }
    }

    public static function routerSecret($routerName)
    {
        $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if ($router && !empty($router->login_secret)) {
            return $router->login_secret;
        }
        return WifiZoneCore::config('api_secret', '');
    }

    public static function generateRouterSecret($routerId)
    {
        $secret = bin2hex(random_bytes(16));
        $router = ORM::for_table('tbl_routers')->find_one($routerId);
        if ($router) {
            $router->login_secret = $secret;
            $router->save();
        }
        return $secret;
    }

    public static function checkRateLimit($scope, $identifier, $maxHits, $windowSeconds)
    {
        $now = time();
        $row = ORM::for_table('wifizone_rate_limit')->where('scope', $scope)->where('identifier', $identifier)->find_one();
        if (!$row) {
            $row = ORM::for_table('wifizone_rate_limit')->create();
            $row->scope = $scope;
            $row->identifier = $identifier;
            $row->hits = 1;
            $row->window_start = $now;
            $row->save();
            return true;
        }
        if ($now - (int) $row->window_start > $windowSeconds) {
            $row->hits = 1;
            $row->window_start = $now;
            $row->save();
            return true;
        }
        if ((int) $row->hits >= $maxHits) {
            return false;
        }
        $row->hits = (int) $row->hits + 1;
        $row->save();
        return true;
    }

    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** Clé tbl_appconfig du routeur Hotspot sélectionné (par admin). */
    public static function loginRouterConfigKey($adminId)
    {
        return 'hotspot_login_router_admin_' . max(0, (int) $adminId);
    }

    /**
     * @return array<string, true> Noms de routeurs visibles pour cet admin
     */
    public static function validRouterNamesForAdmin($admin)
    {
        $names = [];
        if (!is_array($admin) || empty($admin['id'])) {
            return $names;
        }
        $query = ORM::for_table('tbl_routers');
        if (!empty($admin['id'])) {
            $query->where('admin_id', (int) $admin['id']);
        }
        foreach ($query->find_many() as $router) {
            $name = trim((string) ($router->name ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
        }

        return $names;
    }

    public static function saveLoginRouterForAdmin($adminId, $routerName)
    {
        $adminId = (int) $adminId;
        if ($adminId <= 0) {
            return;
        }
        $routerName = trim((string) $routerName);
        $key = self::loginRouterConfigKey($adminId);
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($row) {
            $row->value = $routerName;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
            $row->value = $routerName;
            $row->save();
        }
    }

    /**
     * Routeur Hotspot mémorisé pour l'admin connecté (évite le partage global entre comptes).
     */
    public static function loadLoginRouterForAdmin($admin, &$config = null)
    {
        if (!is_array($admin) || empty($admin['id'])) {
            return trim((string) (is_array($config) ? ($config['hotspot_login_router'] ?? '') : ''));
        }

        $adminId = (int) $admin['id'];
        $valid = self::validRouterNamesForAdmin($admin);
        $key = self::loginRouterConfigKey($adminId);
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        $saved = $row ? trim((string) $row->value) : '';

        if ($saved !== '' && isset($valid[$saved])) {
            if (is_array($config)) {
                $config['hotspot_login_router'] = $saved;
            }
            return $saved;
        }

        // Migration ponctuelle : clé globale legacy uniquement si le routeur appartient à cet admin.
        $legacy = trim((string) (is_array($config) ? ($config['hotspot_login_router'] ?? '') : ''));
        if ($legacy === '' && is_array($config)) {
            $legacyRow = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_login_router')->find_one();
            $legacy = $legacyRow ? trim((string) $legacyRow->value) : '';
        }
        if ($legacy !== '' && isset($valid[$legacy])) {
            self::saveLoginRouterForAdmin($adminId, $legacy);
            if (is_array($config)) {
                $config['hotspot_login_router'] = $legacy;
            }
            return $legacy;
        }

        if ($saved !== '' && !isset($valid[$saved])) {
            self::saveLoginRouterForAdmin($adminId, '');
        }

        if (is_array($config)) {
            $config['hotspot_login_router'] = '';
        }

        return '';
    }

    public static function routerAdminId($routerName)
    {
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return 0;
        }
        if (function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }
        $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();

        return $router && !empty($router->admin_id) ? (int) $router->admin_id : 0;
    }

    /**
     * Forfaits Hotspot actifs pour un routeur.
     * Isolés au propriétaire du routeur (admin_id) — jamais globaux entre comptes.
     * Sans nom de routeur : aucun forfait (évite de lister les offres d'autres comptes).
     */
    public static function plansQueryForRouter($routerName)
    {
        $routerName = trim((string) $routerName);
        if (function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }

        $query = ORM::for_table('tbl_plans')
            ->where('type', 'Hotspot')
            ->where('enabled', 1)
            ->where_raw('1 = 0'); // défaut : vide tant que le routeur n'est pas valide

        if ($routerName === '') {
            return $query;
        }

        $ownerId = self::routerAdminId($routerName);
        if ($ownerId <= 0) {
            return $query;
        }

        return ORM::for_table('tbl_plans')
            ->where('type', 'Hotspot')
            ->where('enabled', 1)
            ->where('routers', $routerName)
            ->where('admin_id', $ownerId);
    }

    /**
     * Portail captive servi par DYRSIA quand le login HTTP interne MikroTik (64874) ne répond pas.
     */
    public static function renderCaptivePortalHtml($clientIp = '', $clientMac = '', $routerName = '')
    {
        global $config, $root_path;

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            $routerName = trim((string) ($config['hotspot_login_router'] ?? ''));
        }

        $gatewayIp = trim((string) ($config['hotspot_local_address'] ?? '10.10.0.1'));
        if (strpos($gatewayIp, '/') !== false) {
            $gatewayIp = (string) explode('/', $gatewayIp)[0];
        }
        $captiveApiUrl = 'http://' . $gatewayIp . ':8080';

        $templateFile = $root_path . 'ui/ui/templates/mikrotik-hotspot-login.html';
        $uploadFile = $root_path . 'system/uploads/mikrotik_hotspot/login.html';
        $htmlFile = is_file($templateFile) ? $templateFile : $uploadFile;
        if (!is_file($htmlFile)) {
            return null;
        }

        $html = file_get_contents($htmlFile);
        if ($html === false || $html === '') {
            return null;
        }

        $clientIp = trim((string) $clientIp);
        $clientMac = strtoupper(trim((string) $clientMac));

        if (($clientIp === '' || $clientMac === '') && $routerName !== '') {
            $resolved = self::resolveHotspotClientFromRouter($routerName, $clientIp, $clientMac);
            $clientIp = $resolved['ip'] ?? $clientIp;
            $clientMac = $resolved['mac'] ?? $clientMac;
        }

        $linkLogin = rtrim($captiveApiUrl, '/') . '/index.php?_route=plugin/hotspot_mikrotik_auth';
        $replacements = [
            '$(ip)' => $clientIp !== '' ? $clientIp : '0.0.0.0',
            '$(mac)' => $clientMac !== '' ? $clientMac : '00:00:00:00:00:00',
            '$(link-login-only)' => $linkLogin,
            '$(link-orig)' => 'http://www.google.com/',
            '$(link-login)' => $linkLogin,
            '$(chap-id)' => '',
            '$(chap-challenge)' => '',
        ];
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        $html = Mikrotik::patchHotspotLoginCaptiveApi($html, $captiveApiUrl, trim((string) ($config['hotspot_dns_name'] ?? '')));

        if ($routerName !== '') {
            $routerJs = 'const HOTSPOT_ROUTER_NAME = ' . json_encode($routerName) . ';';
            if (strpos($html, 'const HOTSPOT_ROUTER_NAME') !== false) {
                $html = preg_replace('/const HOTSPOT_ROUTER_NAME = .*?;/', $routerJs, $html, 1);
            } else {
                $html = str_replace('let CLIENT_MAC = \'\';', 'let CLIENT_MAC = \'\';' . "\n    " . $routerJs, $html);
            }
        }

        $embeddedPlans = [];
        try {
            foreach (self::plansQueryForRouter($routerName)->find_many() as $plan) {
                $planId = (int) ($plan['id'] ?? 0);
                $price = (string) ($plan['price'] ?? '');
                $embeddedPlans[] = [
                    'planid' => $planId,
                    'planId' => $planId,
                    'planname' => (string) ($plan['name_plan'] ?? $plan['name'] ?? ''),
                    'price' => $price,
                    'currency' => $config['currency_code'] ?? 'XAF',
                    'validity' => trim((string) ($plan['validity'] ?? '') . ' ' . (string) ($plan['validity_unit'] ?? '')),
                    'paymentlink' => rtrim($captiveApiUrl, '/') . '/index.php?_route=plugin/hotspot_pay&routername='
                        . rawurlencode($routerName) . '&planid=' . $planId . '&amount=' . rawurlencode($price),
                    'routername' => $routerName,
                    'routerName' => $routerName,
                ];
            }
        } catch (Exception $e) {
            $embeddedPlans = [];
        }

        $embeddedPlansJs = 'const HOTSPOT_EMBEDDED_PLANS = ' . json_encode($embeddedPlans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        if (strpos($html, 'const HOTSPOT_EMBEDDED_PLANS') !== false) {
            $html = preg_replace('/const HOTSPOT_EMBEDDED_PLANS = .*?;/s', $embeddedPlansJs, $html, 1);
        } else {
            $html = str_replace('let CLIENT_MAC = \'\';', 'let CLIENT_MAC = \'\';' . "\n    " . $embeddedPlansJs, $html);
        }

        $plansHtml = Mikrotik::buildHotspotPlansListHtml($embeddedPlans, $routerName);
        $html = preg_replace(
            '/<div class="plans" id="plansList">\s*.*?<\/div>/s',
            '<div class="plans" id="plansList" data-plans-ready="0">' . "\n" . $plansHtml . "\n" . '</div>',
            $html,
            1
        ) ?? $html;

        $html = MobileMoneyGateway::patchModernHotspotChapLogin($html);
        $html = MobileMoneyGateway::repairHotspotLoginHtml($html);

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $html;
        exit;
    }

    /**
     * Authentification voucher depuis le portail servi par DYRSIA (POST natif MikroTik).
     */
    public static function handleMikrotikNativeAuth()
    {
        if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', 'POST') !== 0) {
            http_response_code(405);
            exit;
        }

        global $config;
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $routerName = trim((string) ($config['hotspot_login_router'] ?? ''));

        $clientIp = trim((string) ($_POST['ip'] ?? ''));
        if ($clientIp === '' || !filter_var($clientIp, FILTER_VALIDATE_IP)) {
            $resolved = self::resolveHotspotClientFromRouter($routerName, '', '');
            $clientIp = $resolved['ip'] ?? '';
        }
        if ($clientIp === '') {
            $clientIp = self::clientIp();
        }

        $clientMac = strtoupper(trim((string) ($_POST['mac'] ?? '')));
        if ($clientMac === '' || !preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $clientMac)) {
            $resolved = self::resolveHotspotClientFromRouter($routerName, $clientIp, '');
            $clientMac = $resolved['mac'] ?? '';
        }

        if ($username === '' || $routerName === '') {
            hotspot_throwError(Lang::T('An error occurred while logging in: missing parameters'));
            return false;
        }

        return self::processLogin($username, $password, $clientIp, $clientMac, $routerName);
    }

    /**
     * @return array{ip?: string, mac?: string}
     */
    public static function resolveHotspotClientFromRouter($routerName, $preferIp = '', $preferMac = '')
    {
        $result = [];
        $preferIp = trim((string) $preferIp);
        $preferMac = strtoupper(trim((string) $preferMac));
        if ($preferIp !== '' && $preferMac !== '') {
            return ['ip' => $preferIp, 'mac' => $preferMac];
        }

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return $result;
        }

        try {
            $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
            if (!$router) {
                return $result;
            }
            $client = Mikrotik::getClient($router->ip_address, $router->username, $router->password);
            $hosts = [];
            foreach ($client->sendSync(
                (new PEAR2\Net\RouterOS\Request('/ip/hotspot/host/print'))
                    ->setArgument('.proplist', 'mac-address,address,authorized')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('authorized') === 'true') {
                    continue;
                }
                $hosts[] = [
                    'mac' => strtoupper(trim((string) $row->getProperty('mac-address'))),
                    'ip' => trim((string) $row->getProperty('address')),
                ];
            }

            if ($preferIp !== '') {
                foreach ($hosts as $host) {
                    if ($host['ip'] === $preferIp) {
                        return ['ip' => $host['ip'], 'mac' => $host['mac']];
                    }
                }
            }
            if ($preferMac !== '') {
                foreach ($hosts as $host) {
                    if ($host['mac'] === $preferMac) {
                        return ['ip' => $host['ip'], 'mac' => $host['mac']];
                    }
                }
            }
            if (count($hosts) === 1) {
                return ['ip' => $hosts[0]['ip'], 'mac' => $hosts[0]['mac']];
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $result;
    }
}
