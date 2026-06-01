<?php

/**
 * Central security helpers — rate limits, secrets, headers, service tokens.
 */
class WifiZoneSecurity
{
    public static function bootstrap(array &$config): void
    {
        global $_app_stage, $isApi;
        self::ensureAppKey();
        if (!isset($config['csrf_enabled']) || $config['csrf_enabled'] === '' || $config['csrf_enabled'] === 'no') {
            $config['csrf_enabled'] = 'yes';
            self::persistConfigValue('csrf_enabled', 'yes');
        }
        $jwt = WifiZoneCore::config('wifizone_jwt_secret', '');
        if ($jwt === '' || $jwt === 'change-me') {
            self::persistConfigValue('wifizone_jwt_secret', bin2hex(random_bytes(32)));
        }
        if (empty($isApi) && php_sapi_name() !== 'cli') {
            self::sendSecurityHeaders();
        }
    }

    public static function env(string $key, string $default = ''): string
    {
        if (function_exists('wz_env')) {
            return (string) wz_env($key, $default);
        }
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : (string) $value;
    }

    public static function appKey(): string
    {
        $fromEnv = self::env('APP_KEY', '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        return WifiZoneCore::config('wifizone_app_key', '');
    }

    public static function legacySecret(): string
    {
        global $db_pass;
        return (string) $db_pass;
    }

    public static function cookieSecret(): string
    {
        $key = self::appKey();
        return $key !== '' ? $key : self::legacySecret();
    }

    public static function apiSecret(): string
    {
        $fromEnv = self::env('API_SECRET', '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        $fromDb = WifiZoneCore::config('api_secret', '');
        if ($fromDb !== '') {
            return $fromDb;
        }
        return self::legacySecret();
    }

    public static function signCookiePayload(string $payload): string
    {
        return sha1($payload . '.' . self::cookieSecret());
    }

    public static function verifyCookieSignature(array $parts): bool
    {
        if (count($parts) < 3) {
            return false;
        }
        $payload = $parts[0] . '.' . $parts[1];
        $secrets = array_unique([self::cookieSecret(), self::legacySecret()]);
        foreach ($secrets as $secret) {
            if (hash_equals(sha1($payload . '.' . $secret), $parts[2])) {
                return true;
            }
        }
        return false;
    }

    public static function ensureAppKey(): void
    {
        if (self::appKey() !== '') {
            return;
        }
        self::persistConfigValue('wifizone_app_key', bin2hex(random_bytes(32)));
    }

    public static function persistConfigValue(string $setting, string $value): void
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $setting;
            $row->value = $value;
            $row->save();
        }
    }

    public static function serviceToken(string $service): string
    {
        $envKey = strtoupper($service) . '_TOKEN';
        $fromEnv = self::env($envKey, '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        return WifiZoneCore::config('wifizone_' . $service . '_token', '');
    }

    public static function requireServiceToken(string $service, bool $allowLocalhost = true): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }
        if ($allowLocalhost && self::isTrustedLocalRequest()) {
            return;
        }
        $expected = self::serviceToken($service);
        if ($expected === '') {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Service token not configured. Set ' . strtoupper($service) . '_TOKEN in environment.';
            exit;
        }
        $provided = $_SERVER['HTTP_X_SERVICE_TOKEN'] ?? $_GET['token'] ?? '';
        if (!hash_equals($expected, (string) $provided)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }
    }

    public static function isTrustedLocalRequest(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($ip, ['127.0.0.1', '::1'], true);
    }

    public static function rateLimit(string $scope, string $identifier, int $maxHits, int $windowSeconds): bool
    {
        if ($identifier === '') {
            $identifier = 'unknown';
        }
        $now = time();
        $row = ORM::for_table('wifizone_rate_limit')
            ->where('scope', $scope)
            ->where('identifier', $identifier)
            ->find_one();
        if (!$row) {
            $row = ORM::for_table('wifizone_rate_limit')->create();
            $row->scope = $scope;
            $row->identifier = $identifier;
            $row->hits = 1;
            $row->window_start = $now;
            $row->save();
            return true;
        }
        if (($now - (int) $row->window_start) >= $windowSeconds) {
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

    public static function clientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function enforceLoginRateLimit(string $scope, string $username): bool
    {
        $id = self::clientIp() . ':' . strtolower(trim($username));
        return self::rateLimit($scope, $id, 10, 900);
    }

    public static function verifyProvisionRequest(): ?string
    {
        if (trim(_post('website')) !== '') {
            return Lang::T('Invalid request');
        }
        $ip = self::clientIp();
        if (!self::rateLimit('provision_submit', $ip, 5, 3600)) {
            return Lang::T('Too_many_attempts__Please_try_again_later_');
        }
        return null;
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function blockDestructiveGetRequests(array $routes = []): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        $handler = $routes[0] ?? '';
        $action = $routes[1] ?? '';
        if ($action !== 'delete') {
            return;
        }
        $protected = [
            'services', 'routers', 'customers', 'pool', 'plan', 'coupons',
            'odp', 'bandwidth', 'mail', 'paymentgateway', 'pluginmanager',
        ];
        if (!in_array($handler, $protected, true)) {
            return;
        }
        if (!Admin::getID() && !User::getID()) {
            return;
        }
        r2(getUrl($handler), 'e', Lang::T('Invalid request method'));
    }

    public static function scopeAdminId(array $admin): ?int
    {
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return null;
        }
        return (int) ($admin['id'] ?? 0);
    }
}
