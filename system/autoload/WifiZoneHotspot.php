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
}
