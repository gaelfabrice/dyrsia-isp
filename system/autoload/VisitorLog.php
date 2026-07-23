<?php

/**
 * Journal des visites (SuperAdmin dashboard) — IP, géoloc, URL, bot vs humain.
 */
class VisitorLog
{
    public const TYPE_HUMAN = 'human';
    public const TYPE_BOT = 'bot';

    /** @var list<string> */
    private static $skipHandlers = [
        'autoload',
        'autoload_user',
        'widgets',
        'cron',
        'callback',
        'healthz',
        'health',
        'plugin',
        'search_user',
        'radius',
        'mypvit_secret',
    ];

    /** @var list<string> */
    private static $botPatterns = [
        'bot',
        'crawl',
        'spider',
        'slurp',
        'facebookexternalhit',
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'googlebot',
        'bingbot',
        'yandex',
        'baiduspider',
        'duckduckbot',
        'semrush',
        'ahrefs',
        'petalbot',
        'headless',
        'phantomjs',
        'lighthouse',
        'scrapy',
        'httpclient',
        'libwww',
        'go-http-client',
        'java/',
        'okhttp',
        'postman',
        'insomnia',
        'monitor',
        'uptime',
        'pingdom',
        'gtmetrix',
        'archive.org',
        'mediapartners',
    ];

    public static function ensureSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS wifizone_visitor_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            country VARCHAR(128) NOT NULL DEFAULT '',
            city VARCHAR(128) NOT NULL DEFAULT '',
            visited_path VARCHAR(512) NOT NULL DEFAULT '',
            visited_route VARCHAR(255) NOT NULL DEFAULT '',
            user_agent VARCHAR(512) NOT NULL DEFAULT '',
            visitor_type VARCHAR(16) NOT NULL DEFAULT 'human',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_created (created_at),
            KEY idx_type (visitor_type),
            KEY idx_ip (ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS wifizone_ip_geo (
            ip VARCHAR(45) NOT NULL PRIMARY KEY,
            country VARCHAR(128) NOT NULL DEFAULT '',
            city VARCHAR(128) NOT NULL DEFAULT '',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * @param array<int, string> $routes
     */
    public static function maybeRecord(array $routes, bool $isBackgroundRequest = false)
    {
        if (php_sapi_name() === 'cli' || $isBackgroundRequest) {
            return;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET') {
            return;
        }

        $handler = (string) ($routes[0] ?? '');
        if ($handler === '' || in_array($handler, self::$skipHandlers, true)) {
            return;
        }

        $dest = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        if ($dest !== '' && !in_array($dest, ['document', 'iframe'], true)) {
            return;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return;
        }

        try {
            self::ensureSchema();
            self::record($routes);
            if (mt_rand(1, 250) === 1) {
                self::purgeOlderThan(90);
            }
        } catch (Throwable $e) {
            if (function_exists('_log')) {
                _log('VisitorLog failed: ' . $e->getMessage(), 'Error');
            }
        }
    }

    /**
     * @param array<int, string> $routes
     */
    public static function record(array $routes)
    {
        $ip = self::normalizeIp(WifiZoneSecurity::clientIp());
        $userAgent = substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 512);
        $visitorType = self::detectVisitorType($userAgent);
        $route = self::routeLabel($routes);
        $path = self::visitedPath($route);
        $geo = self::resolveGeo($ip);

        $row = ORM::for_table('wifizone_visitor_logs')->create();
        $row->ip = $ip;
        $row->country = (string) ($geo['country'] ?? '');
        $row->city = (string) ($geo['city'] ?? '');
        $row->visited_path = $path;
        $row->visited_route = $route;
        $row->user_agent = $userAgent;
        $row->visitor_type = $visitorType;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    public static function detectVisitorType($userAgent)
    {
        $ua = strtolower(trim((string) $userAgent));
        if ($ua === '') {
            return self::TYPE_BOT;
        }

        foreach (self::$botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return self::TYPE_BOT;
            }
        }

        return self::TYPE_HUMAN;
    }

    /**
     * @param array<int, string> $routes
     */
    public static function routeLabel(array $routes)
    {
        $parts = array_values(array_filter(array_map('strval', $routes), static function ($part) {
            return trim($part) !== '';
        }));

        return implode('/', $parts);
    }

    public static function visitedPath($route)
    {
        $route = trim((string) $route);
        if ($route === '') {
            return '/';
        }

        $query = trim((string) ($_SERVER['QUERY_STRING'] ?? ''));
        if ($query !== '') {
            $safeQuery = preg_replace('/(^|&)(password|pass|token|csrf_token|key|secret)=[^&]*/i', '$1$2=***', $query);
            if (is_string($safeQuery) && strlen($safeQuery) > 400) {
                $safeQuery = substr($safeQuery, 0, 400) . '…';
            }

            return '/?_route=' . $route . ($safeQuery !== '' ? '&' . $safeQuery : '');
        }

        return '/?_route=' . $route;
    }

    /** @return array{country: string, city: string} */
    public static function resolveGeo($ip)
    {
        $ip = self::normalizeIp($ip);
        if (!self::isPublicIp($ip)) {
            return ['country' => 'Local', 'city' => '—'];
        }

        $cached = ORM::for_table('wifizone_ip_geo')->find_one($ip);
        if ($cached && !empty($cached->updated_at)) {
            $age = time() - strtotime((string) $cached->updated_at);
            if ($age < 86400 * 30) {
                return [
                    'country' => (string) ($cached->country ?? ''),
                    'city' => (string) ($cached->city ?? ''),
                ];
            }
        }

        $geo = self::fetchGeoFromApi($ip);
        if (!$cached) {
            $cached = ORM::for_table('wifizone_ip_geo')->create();
            $cached->ip = $ip;
        }
        $cached->country = (string) ($geo['country'] ?? '');
        $cached->city = (string) ($geo['city'] ?? '');
        $cached->updated_at = date('Y-m-d H:i:s');
        $cached->save();

        return $geo;
    }

    /** @return array{country: string, city: string} */
    private static function fetchGeoFromApi($ip)
    {
        $empty = ['country' => '', 'city' => ''];
        if (!self::isPublicIp($ip)) {
            return ['country' => 'Local', 'city' => '—'];
        }

        $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,city';
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
                'header' => "User-Agent: DYRSIA-VisitorLog/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return $empty;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return $empty;
        }

        return [
            'country' => substr(trim((string) ($data['country'] ?? '')), 0, 128),
            'city' => substr(trim((string) ($data['city'] ?? '')), 0, 128),
        ];
    }

    public static function purgeOlderThan($days)
    {
        $days = max(7, (int) $days);
        ORM::raw_execute(
            'DELETE FROM wifizone_visitor_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)'
        );
    }

    private static function timeAgo($datetime)
    {
        $ts = strtotime((string) $datetime);
        if (!$ts) {
            return (string) $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return $diff . 's';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'm';
        }

        return floor($diff / 86400) . 'j';
    }

    public static function dashboardFeed($admin, int $page = 1, int $perPage = 15)
    {
        if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
            return [
                'visitor_logs' => [],
                'visitor_total_entries' => 0,
                'visitor_current_page' => 1,
                'visitor_total_pages' => 1,
                'visitor_pagination_pages' => [],
                'visitor_prev_page' => 1,
                'visitor_next_page' => 1,
                'visitor_stats' => ['today' => 0, 'bots_today' => 0, 'humans_today' => 0],
            ];
        }

        if (DemoShowcase::isActive($admin)) {
            return self::demoFeed($page, $perPage);
        }

        self::ensureSchema();
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = (int) ORM::for_table('wifizone_visitor_logs')->count();
        $rows = ORM::for_table('wifizone_visitor_logs')
            ->order_by_desc('id')
            ->limit($perPage)
            ->offset($offset)
            ->find_many();

        $logs = [];
        foreach ($rows as $row) {
            $logs[] = [
                'ip' => (string) ($row->ip ?? ''),
                'country' => (string) ($row->country ?? ''),
                'city' => (string) ($row->city ?? ''),
                'visited_path' => (string) ($row->visited_path ?? ''),
                'visited_route' => (string) ($row->visited_route ?? ''),
                'visitor_type' => (string) ($row->visitor_type ?? self::TYPE_HUMAN),
                'time_ago' => self::timeAgo((string) ($row->created_at ?? '')),
                'created_at' => (string) ($row->created_at ?? ''),
            ];
        }

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $stats = [
            'today' => (int) ORM::for_table('wifizone_visitor_logs')
                ->where_gte('created_at', $todayStart)
                ->where_lte('created_at', $todayEnd)
                ->count(),
            'bots_today' => (int) ORM::for_table('wifizone_visitor_logs')
                ->where_gte('created_at', $todayStart)
                ->where_lte('created_at', $todayEnd)
                ->where('visitor_type', self::TYPE_BOT)
                ->count(),
            'humans_today' => (int) ORM::for_table('wifizone_visitor_logs')
                ->where_gte('created_at', $todayStart)
                ->where_lte('created_at', $todayEnd)
                ->where('visitor_type', self::TYPE_HUMAN)
                ->count(),
        ];

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $pages = [];
        $start = max(1, $page - 2);
        $end = min($totalPages, $start + 4);
        $start = max(1, $end - 4);
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = ['num' => $i, 'active' => $i === $page];
        }

        return [
            'visitor_logs' => $logs,
            'visitor_total_entries' => $total,
            'visitor_current_page' => $page,
            'visitor_total_pages' => $totalPages,
            'visitor_pagination_pages' => $pages,
            'visitor_prev_page' => max(1, $page - 1),
            'visitor_next_page' => min($totalPages, $page + 1),
            'visitor_stats' => $stats,
        ];
    }

    /** @return array<string, mixed> */
    private static function demoFeed(int $page, int $perPage)
    {
        $rows = [
            ['ip' => '102.68.44.12', 'country' => 'Cameroon', 'city' => 'Douala', 'visited_path' => '/?_route=provision', 'visited_route' => 'provision', 'visitor_type' => self::TYPE_HUMAN, 'time_ago' => '3m'],
            ['ip' => '41.202.18.55', 'country' => 'Gabon', 'city' => 'Libreville', 'visited_path' => '/?_route=home', 'visited_route' => 'home', 'visitor_type' => self::TYPE_HUMAN, 'time_ago' => '8m'],
            ['ip' => '66.249.66.1', 'country' => 'United States', 'city' => 'Mountain View', 'visited_path' => '/?_route=provision', 'visited_route' => 'provision', 'visitor_type' => self::TYPE_BOT, 'time_ago' => '12m'],
            ['ip' => '197.155.10.88', 'country' => 'Cameroon', 'city' => 'Yaoundé', 'visited_path' => '/?_route=login', 'visited_route' => 'login', 'visitor_type' => self::TYPE_HUMAN, 'time_ago' => '19m'],
            ['ip' => '17.58.98.20', 'country' => 'United States', 'city' => 'Cupertino', 'visited_path' => '/?_route=home', 'visited_route' => 'home', 'visitor_type' => self::TYPE_BOT, 'time_ago' => '24m'],
        ];

        return [
            'visitor_logs' => $rows,
            'visitor_total_entries' => count($rows),
            'visitor_current_page' => 1,
            'visitor_total_pages' => 1,
            'visitor_pagination_pages' => [['num' => 1, 'active' => true]],
            'visitor_prev_page' => 1,
            'visitor_next_page' => 1,
            'visitor_stats' => ['today' => 128, 'bots_today' => 41, 'humans_today' => 87],
        ];
    }

    public static function normalizeIp($ip)
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return '0.0.0.0';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    public static function isPublicIp($ip)
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
