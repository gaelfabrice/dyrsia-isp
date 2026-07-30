<?php

/**
 * Anti-intrusion : blocage IP, détection de sondes, bots et honeypot.
 */
class WifiZoneIntrusion
{
    public const HONEYPOT_FIELD = 'wz_company_url';

    /** @var string[] */
    private const PROBE_PATTERNS = [
        '/\.env',
        '/\.git',
        '/wp-admin',
        '/wp-login',
        '/wp-content',
        '/xmlrpc\.php',
        '/phpmyadmin',
        '/pma/',
        '/adminer',
        '/vendor/phpunit',
        '/\.well-known/security\.txt',
        '/actuator',
        '/shell\.php',
        '/c99\.php',
        '/r57\.php',
        '/config\.php\.bak',
        '/backup\.sql',
        '/database\.sql',
        '/\.aws/',
        '/server-status',
        '/telescope',
        '/_ignition',
        '/solr/',
        '/cgi-bin/',
    ];

    /** @var string[] */
    private const BAD_BOT_UA_FRAGMENTS = [
        'semrushbot',
        'ahrefsbot',
        'petalbot',
        'dotbot',
        'mj12bot',
        'bytespider',
        'python-requests',
        'scrapy/',
        'go-http-client',
        'masscan',
        'zgrab',
        'libwww-perl',
        'httpx/',
        'sqlmap',
        'nikto',
        'acunetix',
        'netsparker',
        'nessus',
        'openvas',
        'dirbuster',
        'gobuster',
        'wfuzz',
        'nmap',
        'masscan',
        'zmeu',
        'sitemapgenerator',
        'dataforseo',
        'serpstatbot',
        'blexbot',
        'megaindex',
    ];

    /** @var string[] */
    private const ALLOWED_BOT_UA_FRAGMENTS = [
        'googlebot',
        'bingbot',
        'applebot',
        'duckduckbot',
        'yandexbot',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'slackbot',
    ];

    public static function guard(): void
    {
        if (!self::isEnabled() || self::shouldBypass()) {
            return;
        }

        $ip = WifiZoneSecurity::clientIp();
        if (self::isAllowlisted($ip)) {
            return;
        }

        if (self::isIpBlocked($ip)) {
            self::deny(403);
        }

        if (self::detectProbeUri()) {
            self::blockIp($ip, 86400, 'probe');
            self::deny(403);
        }

        global $isApi;
        if (empty($isApi) && self::isBlockedBotUserAgent(self::isAuthSurface())) {
            self::addStrike($ip, 'bot_ua', 2);
            self::deny(403);
        }

        if (!self::enforceGlobalRateLimit($ip)) {
            self::addStrike($ip, 'rate_global', 1);
            self::deny(429);
        }

        if (self::isAuthSurface() && !self::enforceAuthSurfaceRateLimit($ip)) {
            self::addStrike($ip, 'rate_auth', 2);
            self::deny(429);
        }

        if (self::honeypotTripped()) {
            self::blockIp($ip, 43200, 'honeypot');
            self::deny(403);
        }
    }

    public static function isEnabled(): bool
    {
        $v = WifiZoneCore::config('wifizone_intrusion_guard', 'yes');

        return $v === '' || $v === 'yes' || $v === '1' || $v === 'true';
    }

    public static function verifyLoginPost(): bool
    {
        if (!self::isEnabled() || self::shouldBypass()) {
            return true;
        }
        $ip = WifiZoneSecurity::clientIp();
        if (self::isAllowlisted($ip) || self::isIpBlocked($ip)) {
            return !self::isIpBlocked($ip);
        }
        if (self::honeypotTripped()) {
            self::blockIp($ip, 43200, 'honeypot_login');
            self::logEvent('honeypot_login', $ip);

            return false;
        }

        return true;
    }

    public static function recordAuthFailure(string $context = 'auth'): void
    {
        if (!self::isEnabled() || self::shouldBypass()) {
            return;
        }
        $ip = WifiZoneSecurity::clientIp();
        if (self::isAllowlisted($ip)) {
            return;
        }
        self::addStrike($ip, 'auth_fail_' . $context, 1);
    }

    public static function honeypotTripped(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return false;
        }
        $hp = trim((string) ($_POST[self::HONEYPOT_FIELD] ?? ''));

        return $hp !== '';
    }

    private static function shouldBypass(): bool
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }

        global $_app_stage;
        if (WifiZoneSecurity::isTrustedLocalRequest() && ($_app_stage ?? 'Live') !== 'Live') {
            return true;
        }

        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (preg_match('/^10\.10\.0\.\d+$/', $remote) || preg_match('/^10\.0\.0\.\d+$/', $remote)) {
            return true;
        }

        $route = self::currentRoute();
        if ($route === '') {
            $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            if (in_array($script, ['cron.php', 'cronjob.php'], true)) {
                return true;
            }
        }

        $skipRoutes = ['cron', 'autoload', 'autoload_user', 'widgets'];
        $handler = explode('/', $route, 2)[0] ?? '';
        if (in_array($handler, $skipRoutes, true)) {
            return true;
        }

        if (str_starts_with($route, 'plugin/hotspot') || str_starts_with($route, 'plugin/pppoe')) {
            return true;
        }
        if (preg_match('#^admin/(2fa|2fa-post|2fa-qr)(/|$)#', $route)) {
            return true;
        }

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        if (str_contains($uri, 'admin/2fa')) {
            return true;
        }
        if (preg_match('#/(install/|system/api\.php)#', $uri)) {
            return true;
        }

        return false;
    }

    private static function currentRoute(): string
    {
        $route = trim((string) ($_GET['_route'] ?? $_POST['_route'] ?? ''));

        return strtolower($route);
    }

    private static function isAuthSurface(): bool
    {
        $route = self::currentRoute();
        if ($route === 'admin' || str_starts_with($route, 'admin/')) {
            return true;
        }
        if ($route === 'login' || str_starts_with($route, 'login/')) {
            return true;
        }
        if ($route === 'provision' || str_starts_with($route, 'provision/')) {
            return true;
        }

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));

        return preg_match('#_route=(admin|login|provision)(/|$|&)#', $uri) === 1;
    }

    private static function isAllowlisted(string $ip): bool
    {
        $fromEnv = trim(WifiZoneSecurity::env('WIFIZONE_INTRUSION_ALLOWLIST', ''));
        $fromCfg = trim((string) WifiZoneCore::config('wifizone_intrusion_allowlist', ''));
        $raw = $fromEnv !== '' ? $fromEnv : $fromCfg;
        if ($raw === '') {
            return false;
        }
        foreach (preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            if ($entry === $ip) {
                return true;
            }
        }

        return false;
    }

    private static function isIpBlocked(string $ip): bool
    {
        try {
            $row = ORM::for_table('wifizone_ip_block')->where('ip', $ip)->find_one();
            if (!$row) {
                return false;
            }
            $until = (int) $row->blocked_until;
            if ($until <= time()) {
                $row->delete();

                return false;
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function blockIp(string $ip, int $seconds, string $reason): void
    {
        if ($ip === '' || $ip === '0.0.0.0') {
            return;
        }
        $until = time() + max(60, $seconds);
        try {
            $row = ORM::for_table('wifizone_ip_block')->where('ip', $ip)->find_one();
            if (!$row) {
                $row = ORM::for_table('wifizone_ip_block')->create();
                $row->ip = $ip;
                $row->strikes = 1;
            } else {
                $row->strikes = (int) $row->strikes + 1;
            }
            $row->reason = substr($reason, 0, 120);
            $row->blocked_until = $until;
            $row->updated_at = time();
            $row->save();
            self::logEvent('ip_block', $ip, ['reason' => $reason, 'until' => $until]);
        } catch (Throwable $e) {
            error_log('WifiZoneIntrusion::blockIp: ' . $e->getMessage());
        }
    }

    private static function addStrike(string $ip, string $reason, int $weight = 1): void
    {
        if ($ip === '') {
            return;
        }
        $scope = 'intrusion_strike';
        $window = 900;
        $maxBeforeBan = 8;
        $banSeconds = 3600;

        for ($i = 0; $i < $weight; $i++) {
            WifiZoneSecurity::rateLimitHit($scope, $ip, $maxBeforeBan, $window);
        }

        if (!WifiZoneSecurity::rateLimitAllowed($scope, $ip, $maxBeforeBan, $window)) {
            self::blockIp($ip, $banSeconds, 'strikes:' . $reason);
        } else {
            self::logEvent('strike', $ip, ['reason' => $reason, 'weight' => $weight]);
        }
    }

    private static function detectProbeUri(): bool
    {
        $uri = strtolower(rawurldecode((string) ($_SERVER['REQUEST_URI'] ?? '')));
        if ($uri === '') {
            return false;
        }
        foreach (self::PROBE_PATTERNS as $pattern) {
            if (str_contains($uri, $pattern)) {
                self::logEvent('probe', WifiZoneSecurity::clientIp(), ['uri' => substr($uri, 0, 200)]);

                return true;
            }
        }

        return false;
    }

    private static function userAgent(): string
    {
        return strtolower(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    }

    private static function isBlockedBotUserAgent(bool $strict): bool
    {
        $ua = self::userAgent();
        if ($ua === '') {
            return $strict;
        }
        foreach (self::ALLOWED_BOT_UA_FRAGMENTS as $allowed) {
            if (str_contains($ua, $allowed)) {
                return false;
            }
        }
        foreach (self::BAD_BOT_UA_FRAGMENTS as $bad) {
            if (str_contains($ua, $bad)) {
                return true;
            }
        }
        if ($strict && preg_match('#^(curl|wget|python|java|php|ruby|perl)/#', $ua)) {
            return true;
        }

        return false;
    }

    private static function enforceGlobalRateLimit(string $ip): bool
    {
        global $isApi;
        $max = !empty($isApi) ? 240 : 180;
        $window = 60;

        return WifiZoneSecurity::rateLimit('http_global', $ip, $max, $window);
    }

    private static function enforceAuthSurfaceRateLimit(string $ip): bool
    {
        return WifiZoneSecurity::rateLimit('http_auth_surface', $ip, 40, 60);
    }

    private static function deny(int $code): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            if ($code === 429) {
                header('Retry-After: 120');
            }
        }
        echo 'Forbidden';
        exit;
    }

    private static function logEvent(string $action, string $ip, array $extra = []): void
    {
        $payload = array_merge(['ip' => $ip, 'ua' => substr(self::userAgent(), 0, 160)], $extra);
        try {
            if (class_exists('WifiZoneAudit')) {
                WifiZoneAudit::log('intrusion_' . $action, 'security', $ip, $payload);
            }
        } catch (Throwable $e) {
        }
        if (function_exists('_log')) {
            try {
                _log('[Intrusion] ' . $action . ' ' . $ip . ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE), 'Security', 0);
            } catch (Throwable $e) {
            }
        }
    }
}
