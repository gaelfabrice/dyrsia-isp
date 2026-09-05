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
        if (!Mikrotik::hotspotPlanAllowsSharing($p)) {
            $macCheck = HotspotCustomer::assertSingleDeviceMacAccess($plan, $p, $mac_address, $router_name, true);
            if (!$macCheck['ok']) {
                hotspot_throwError(HotspotCustomer::voucherAlreadyUsedMessage());
                return false;
            }
        } elseif (!Mikrotik::hotspotSharedUsersAllowLogin($username, $router_name, $p, $ip, $mac_address)) {
            hotspot_throwError(Lang::T('Maximum number of devices for this plan is already in use'));
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
     * @return array<int, string>
     */
    public static function hotspotSettingKeys()
    {
        return [
            'hotspot_page_title',
            'hotspot_page_tagline',
            'hotspot_api_url',
            'hotspot_login_color',
            'hotspot_card_shape',
            'hotspot_card_display',
            'hotspot_plan_order',
            'hotspot_banner_text',
            'hotspot_help_title',
            'hotspot_help_text',
            'hotspot_contact',
            'hotspot_contact_phone',
            'hotspot_help_whatsapp',
            'hotspot_help_whatsapp_label',
            'hotspot_chat_service',
            'hotspot_name',
            'hotspot_interface',
            'hotspot_profile',
            'hotspot_dns_name',
            'hotspot_local_address',
            'hotspot_masquerade',
            'hotspot_address_pool',
            'hotspot_dns_server',
            'hotspot_pool_mode',
            'hotspot_pool_name',
            'hotspot_pool_range',
            'hotspot_login_methods',
            'hotspot_cookie_lifetime',
            'hotspot_idle_timeout',
            'hotspot_keepalive_timeout',
            'hotspot_smtp_server',
            'hotspot_use_radius',
            'hotspot_radius_secret',
            'hotspot_bridge_ports',
            'lan_hotspot_access_ports',
            'lan_management_bridge_name',
            'lan_management_interface',
            'lan_management_address',
            'lan_wan_interface',
        ];
    }

    public static function hotspotConfigStorageKey($settingKey, $adminId)
    {
        return trim((string) $settingKey) . '_admin_' . max(0, (int) $adminId);
    }

    /**
     * Propriétaire des réglages hotspot (SuperAdmin édite le compte du routeur).
     */
    public static function resolveHotspotAdminId($admin, $routerName = '')
    {
        $routerName = trim((string) $routerName);
        if ($routerName !== '') {
            $ownerId = self::routerAdminId($routerName);
            if ($ownerId > 0) {
                return $ownerId;
            }
        }

        return max(0, (int) (is_array($admin) ? ($admin['id'] ?? 0) : 0));
    }

    public static function clearHotspotConfigInMemory(&$config)
    {
        if (!is_array($config)) {
            return;
        }
        foreach (self::hotspotSettingKeys() as $key) {
            unset($config[$key]);
        }
    }

    /**
     * Supprime la config hotspot copiée par erreur depuis les clés globales (migration bug multi-tenant).
     */
    public static function purgeMisassignedHotspotConfig($adminId)
    {
        $adminId = (int) $adminId;
        if ($adminId <= 0) {
            return 0;
        }

        $ownsRouter = (bool) ORM::for_table('tbl_routers')->where('admin_id', $adminId)->find_one();
        if ($ownsRouter) {
            return 0;
        }

        $removed = 0;
        foreach (self::hotspotSettingKeys() as $key) {
            $storageKey = self::hotspotConfigStorageKey($key, $adminId);
            $scoped = ORM::for_table('tbl_appconfig')->where('setting', $storageKey)->find_one();
            if (!$scoped) {
                continue;
            }
            $legacy = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if (!$legacy) {
                continue;
            }
            if (trim((string) $legacy->value) !== trim((string) $scoped->value)) {
                continue;
            }
            $scoped->delete();
            $removed++;
        }

        return $removed;
    }

    /**
     * Config hotspot de l'admin connecté (assistant, aperçu, enregistrement).
     */
    public static function loadHotspotConfigForSessionAdmin($admin, &$config, $routerName = '')
    {
        $adminId = (int) (is_array($admin) ? ($admin['id'] ?? 0) : 0);
        self::clearHotspotConfigInMemory($config);
        if ($adminId > 0) {
            self::purgeMisassignedHotspotConfig($adminId);
            self::loadHotspotConfigForAdmin($adminId, $config, $routerName);
        }
    }

    /**
     * Config hotspot du propriétaire du routeur (déploiement MikroTik / portail public).
     */
    public static function loadHotspotConfigForDeploy(&$config, $routerName)
    {
        self::clearHotspotConfigInMemory($config);
        self::loadHotspotConfigForRouter($config, $routerName);
    }

    public static function saveHotspotSettingForAdmin($adminId, $settingKey, $value)
    {
        $adminId = (int) $adminId;
        $settingKey = trim((string) $settingKey);
        if ($adminId <= 0 || $settingKey === '' || $settingKey === 'hotspot_login_router') {
            return;
        }
        $storageKey = self::hotspotConfigStorageKey($settingKey, $adminId);
        $row = ORM::for_table('tbl_appconfig')->where('setting', $storageKey)->find_one();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $storageKey;
            $row->value = $value;
            $row->save();
        }
    }

    /**
     * Charge la config hotspot isolée par admin (clés scopées uniquement).
     */
    public static function loadHotspotConfigForAdmin($adminId, &$config, $routerName = '')
    {
        $adminId = (int) $adminId;
        if ($adminId <= 0) {
            return;
        }
        if (!is_array($config)) {
            $config = [];
        }

        $legacyOwnerId = 0;
        $legacyRouterRow = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_login_router')->find_one();
        $legacyRouterName = $legacyRouterRow ? trim((string) $legacyRouterRow->value) : '';
        if ($legacyRouterName !== '') {
            $legacyOwnerId = self::routerAdminId($legacyRouterName);
        }

        foreach (self::hotspotSettingKeys() as $key) {
            $storageKey = self::hotspotConfigStorageKey($key, $adminId);
            $row = ORM::for_table('tbl_appconfig')->where('setting', $storageKey)->find_one();
            if ($row) {
                $config[$key] = $row->value;
                continue;
            }
            // Migration legacy : uniquement pour le propriétaire du routeur global historique.
            if ($legacyOwnerId !== $adminId) {
                continue;
            }
            $legacy = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            $legacyValue = $legacy ? trim((string) $legacy->value) : '';
            if ($legacyValue !== '') {
                $config[$key] = $legacyValue;
                self::saveHotspotSettingForAdmin($adminId, $key, $legacyValue);
            }
        }
    }

    public static function loadHotspotConfigForRouter(&$config, $routerName)
    {
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return;
        }
        $ownerId = self::routerAdminId($routerName);
        if ($ownerId <= 0) {
            return;
        }
        self::loadHotspotConfigForAdmin($ownerId, $config, $routerName);
        $config['hotspot_login_router'] = $routerName;
    }

    public static function hotspotLoginHtmlPath($adminId, $uploadPath = null)
    {
        global $UPLOAD_PATH;
        $base = $uploadPath ?? $UPLOAD_PATH ?? '';
        $dir = rtrim((string) $base, '/\\') . DIRECTORY_SEPARATOR . 'mikrotik_hotspot'
            . DIRECTORY_SEPARATOR . 'admin_' . max(0, (int) $adminId);

        return $dir . DIRECTORY_SEPARATOR . 'login.html';
    }

    public static function hotspotLoginHtmlDir($adminId, $uploadPath = null)
    {
        return dirname(self::hotspotLoginHtmlPath($adminId, $uploadPath));
    }

    /**
     * Routeur obligatoire sur les endpoints publics (pas de fallback global).
     */
    public static function resolvePublicRouterName($routerInput = '')
    {
        $routerInput = trim((string) $routerInput);
        if ($routerInput === '' || preg_match('/^\$\(/', $routerInput)) {
            $routerInput = trim((string) (
                $_GET['router'] ?? $_GET['routername'] ?? $_POST['router'] ?? $_POST['routername'] ?? ''
            ));
        }
        if ($routerInput === '') {
            return '';
        }

        $router = ORM::for_table('tbl_routers')->where('name', $routerInput)->find_one();
        if (!$router) {
            $router = ORM::for_table('tbl_routers')->where('description', $routerInput)->find_one();
        }
        if (!$router) {
            $routerIp = explode(':', $routerInput)[0];
            if ($routerIp !== '') {
                $router = ORM::for_table('tbl_routers')->where_like('ip_address', $routerIp . '%')->find_one();
            }
        }

        return $router ? trim((string) $router->name) : '';
    }

    /**
     * @return array<string, true> Noms de routeurs visibles pour cet admin
     */
    public static function validRouterNamesForAdmin($admin)
    {
        $names = [];
        if (!is_array($admin)) {
            return $names;
        }
        $query = ORM::for_table('tbl_routers');
        if (($admin['user_type'] ?? '') !== 'SuperAdmin' && !empty($admin['id'])) {
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
            $routerOwnerId = self::routerAdminId($saved);
            if (($admin['user_type'] ?? '') !== 'SuperAdmin' && $routerOwnerId > 0 && $routerOwnerId !== $adminId) {
                self::saveLoginRouterForAdmin($adminId, '');
                $saved = '';
            }
        }
        if ($saved !== '' && isset($valid[$saved])) {
            if (is_array($config)) {
                $config['hotspot_login_router'] = $saved;
            }
            return $saved;
        }

        // Migration legacy : routeur global uniquement si ce compte en est propriétaire.
        $legacy = '';
        $legacyRow = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_login_router')->find_one();
        if ($legacyRow) {
            $legacy = trim((string) $legacyRow->value);
        }
        if ($legacy !== '' && isset($valid[$legacy]) && self::routerAdminId($legacy) === $adminId) {
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

    public static function detachPlansFromDeletedRouter($routerName, $adminId)
    {
        $routerName = trim((string) $routerName);
        $adminId = (int) $adminId;
        if ($routerName === '' || $adminId <= 0) {
            return 0;
        }

        $updated = 0;
        foreach (ORM::for_table('tbl_plans')
            ->where('admin_id', $adminId)
            ->where('routers', $routerName)
            ->find_many() as $plan) {
            $plan->routers = '';
            $plan->save();
            $updated++;
        }

        if ($updated > 0) {
            self::clearHotspotPlanCache();
        }

        return $updated;
    }

    /**
     * Nettoie les références hotspot quand un routeur est supprimé ou remplacé.
     */
    public static function purgeAdminRouterReferences($routerName, $adminId)
    {
        $routerName = trim((string) $routerName);
        $adminId = (int) $adminId;
        if ($routerName === '' || $adminId <= 0) {
            return;
        }

        $key = self::loginRouterConfigKey($adminId);
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($row && trim((string) $row->value) === $routerName) {
            self::saveLoginRouterForAdmin($adminId, '');
        }
    }

    /**
     * Réinitialise la config matérielle hotspot après changement de routeur
     * (évite de réutiliser interface/nom d'un ancien MikroTik).
     */
    public static function resetHotspotDeployDefaultsForAdmin($adminId)
    {
        $adminId = (int) $adminId;
        if ($adminId <= 0) {
            return;
        }

        $defaults = [
            'hotspot_interface' => 'bridge-hotspot',
            'hotspot_name' => '',
            'hotspot_pool_mode' => 'existing',
        ];
        foreach ($defaults as $setting => $value) {
            self::saveHotspotSettingForAdmin($adminId, $setting, $value);
        }
        self::clearHotspotPlanCache();
    }

    public static function clearHotspotPlanCache()
    {
        foreach (glob('system/cache/hotspot_plan_*.json') ?: [] as $cacheFile) {
            @unlink($cacheFile);
        }
    }

    public static function hotspotPlanCacheKey($routerName)
    {
        $routerName = trim((string) $routerName);
        $ownerId = self::routerAdminId($routerName);

        return 'hotspot_plan_' . md5($routerName . '|' . $ownerId);
    }

    public static function routerAdminId($routerName)
    {
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return 0;
        }

        $row = self::resolveRouterRow($routerName);

        return ($row && !empty($row['admin_id'])) ? (int) $row['admin_id'] : 0;
    }

    /**
     * Résout un identifiant routeur (nom, description, IP) vers tbl_routers.
     *
     * @return array<string, mixed>|null
     */
    public static function resolveRouterRow($routerInput)
    {
        $routerInput = trim((string) $routerInput);
        if ($routerInput === '') {
            return null;
        }

        if (function_exists('hotspot_resolve_router')) {
            $row = hotspot_resolve_router($routerInput);

            return $row ? (is_array($row) ? $row : $row->as_array()) : null;
        }

        $router = ORM::for_table('tbl_routers')->where('name', $routerInput)->find_one();
        if ($router) {
            return $router->as_array();
        }

        $router = ORM::for_table('tbl_routers')->where('description', $routerInput)->find_one();
        if ($router) {
            return $router->as_array();
        }

        $routerIp = explode(':', $routerInput)[0];
        if ($routerIp !== '') {
            $router = ORM::for_table('tbl_routers')->where_like('ip_address', $routerIp . '%')->find_one();
            if ($router) {
                return $router->as_array();
            }
        }

        return null;
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
            ->where_raw('(enabled = 1 OR enabled = ?)', ['1'])
            ->where_raw('1 = 0'); // défaut : vide tant que le routeur n'est pas valide

        if ($routerName === '') {
            return $query;
        }

        $ownerId = self::routerAdminId($routerName);
        $query = ORM::for_table('tbl_plans')
            ->where('type', 'Hotspot')
            ->where_raw('(enabled = 1 OR enabled = ?)', ['1'])
            ->where('routers', $routerName);
        if ($ownerId > 0) {
            $query->where('admin_id', $ownerId);
        }
        try {
            $query->order_by_asc('tbl_plans.display_order')->order_by_asc('tbl_plans.id');
        } catch (Throwable $e) {
            $query->order_by_asc('tbl_plans.id');
        }

        return $query;
    }

    /**
     * Portail captive servi par DYRSIA quand le login HTTP interne MikroTik (64874) ne répond pas.
     */
    public static function renderCaptivePortalHtml($clientIp = '', $clientMac = '', $routerName = '')
    {
        global $config, $root_path;

        $routerName = self::resolvePublicRouterName($routerName);
        if ($routerName === '') {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Paramètre router/routername requis.';
            exit;
        }

        self::loadHotspotConfigForRouter($config, $routerName);
        $ownerId = self::routerAdminId($routerName);

        $gatewayIp = trim((string) ($config['hotspot_local_address'] ?? '10.10.0.1'));
        if (strpos($gatewayIp, '/') !== false) {
            $gatewayIp = (string) explode('/', $gatewayIp)[0];
        }
        $backendApiUrl = Mikrotik::resolveHotspotBackendApiUrl($config);
        $captiveApiUrl = Mikrotik::resolveHotspotCaptiveProxyUrl($gatewayIp);
        if ($captiveApiUrl === '') {
            $captiveApiUrl = rtrim($backendApiUrl, '/');
        }

        $templateFile = $root_path . 'ui/ui/templates/mikrotik-hotspot-login.html';
        $uploadFile = $ownerId > 0
            ? self::hotspotLoginHtmlPath($ownerId, $root_path . 'system/uploads')
            : $root_path . 'system/uploads/mikrotik_hotspot/login.html';
        $htmlFile = is_file($uploadFile) ? $uploadFile : (is_file($templateFile) ? $templateFile : $uploadFile);
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

        $html = Mikrotik::patchHotspotLoginCaptiveApi(
            $html,
            $captiveApiUrl,
            trim((string) ($config['hotspot_dns_name'] ?? '')),
            $backendApiUrl
        );

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
        $routerName = self::resolvePublicRouterName(
            trim((string) ($_POST['router'] ?? $_POST['routername'] ?? ''))
        );
        if ($routerName !== '') {
            self::loadHotspotConfigForRouter($config, $routerName);
        }

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

    /**
     * Supprime toutes les traces DYRSIA d'un routeur (NAS RADIUS, config hotspot, cache).
     * À appeler avant suppression du routeur ou réattribution à un autre compte/IP.
     */
    public static function purgeRouterFromPlatform($routerName, $adminId, $routerNasIp = null)
    {
        $routerName = trim((string) $routerName);
        $adminId = (int) $adminId;
        if ($routerName === '' || $adminId <= 0) {
            return;
        }

        self::detachPlansFromDeletedRouter($routerName, $adminId);
        self::purgeAdminRouterReferences($routerName, $adminId);
        Mikrotik::removeHotspotNasRecord($routerName, $routerNasIp);

        $cacheFile = self::hotspotPlanCachePath($routerName);
        if ($cacheFile !== '' && is_file($cacheFile)) {
            @unlink($cacheFile);
        }
        self::clearHotspotPlanCache();
    }

    /**
     * Purge complète d'un compte Admin : config hotspot, login.html, jobs async, NAS de ses routeurs.
     */
    public static function purgeAdminAccountCompletely($adminId, $uploadPath = null)
    {
        global $root_path, $UPLOAD_PATH;

        $adminId = (int) $adminId;
        if ($adminId <= 0) {
            return;
        }

        if ($uploadPath === null) {
            $uploadPath = $UPLOAD_PATH ?? ($root_path . 'system' . DIRECTORY_SEPARATOR . 'uploads');
        }

        foreach (ORM::for_table('tbl_routers')->where('admin_id', $adminId)->find_many() as $router) {
            $routerName = trim((string) ($router->name ?? ''));
            $routerIp = Mikrotik::parseEndpoint((string) ($router->ip_address ?? ''))['host'];
            if ($routerName !== '') {
                self::purgeRouterFromPlatform($routerName, $adminId, $routerIp);
            }
        }

        foreach (self::hotspotSettingKeys() as $key) {
            $storageKey = self::hotspotConfigStorageKey($key, $adminId);
            $row = ORM::for_table('tbl_appconfig')->where('setting', $storageKey)->find_one();
            if ($row) {
                $row->delete();
            }
        }
        $loginRouterKey = self::loginRouterConfigKey($adminId);
        $loginRow = ORM::for_table('tbl_appconfig')->where('setting', $loginRouterKey)->find_one();
        if ($loginRow) {
            $loginRow->delete();
        }

        try {
            ORM::raw_execute(
                'DELETE FROM tbl_appconfig WHERE setting LIKE ?',
                ['%_admin_' . $adminId]
            );
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        $loginDir = self::hotspotLoginHtmlDir($adminId, $uploadPath);
        if (is_dir($loginDir)) {
            foreach (glob($loginDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($loginDir);
        }

        $cacheDir = realpath(__DIR__ . '/../cache') ?: (__DIR__ . '/../cache');
        foreach (glob(rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'hotspot_deploy_' . $adminId . '_*.json') ?: [] as $jobFile) {
            @unlink($jobFile);
        }

        self::clearHotspotPlanCache();
    }

    private static function hotspotPlanCachePath($routerName)
    {
        $key = self::hotspotPlanCacheKey($routerName);
        $cacheDir = realpath(__DIR__ . '/../cache') ?: (__DIR__ . '/../cache');

        return rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.json';
    }
}
