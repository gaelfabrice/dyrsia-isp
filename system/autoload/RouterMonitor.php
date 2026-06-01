<?php

/**
 * Surveillance quotidienne des routeurs (ping) + alertes UI / WhatsApp.
 */
class RouterMonitor
{
    public static function ensureSchema()
    {
        try {
            ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_router_alerts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                router_id INT UNSIGNED NOT NULL,
                admin_id INT UNSIGNED NULL,
                router_name VARCHAR(64) NOT NULL,
                last_check DATETIME NOT NULL,
                dismissed TINYINT(1) NOT NULL DEFAULT 0,
                whatsapp_sent TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_router_dismissed (router_id, dismissed),
                KEY idx_admin_dismissed (admin_id, dismissed)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            _log('RouterMonitor schema: ' . $e->getMessage());
        }
    }

    /**
     * Exécute la vérification au plus une fois par jour (sauf $force).
     */
    public static function maybeRunDailyCheck($force = false)
    {
        global $config;

        if (empty($config['router_check']) || $config['router_check'] != '1') {
            return ['skipped' => true, 'reason' => 'disabled'];
        }

        self::ensureSchema();

        $today = date('Y-m-d');
        $lastRun = self::getConfigValue('router_check_last_date', '');

        if (!$force && $lastRun === $today) {
            return ['skipped' => true, 'reason' => 'already_ran_today'];
        }

        $result = self::checkAllRouters();
        self::setConfigValue('router_check_last_date', $today);

        return $result;
    }

    public static function checkAllRouters()
    {
        $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $offline = 0;
        $online = 0;
        $checked = 0;

        foreach ($routers as $router) {
            $previous = $router->status ?? 'Offline';
            $isOnline = self::isRouterReachable($router->ip_address);
            $now = date('Y-m-d H:i:s');

            if ($isOnline) {
                $router->status = 'Online';
                $router->last_seen = $now;
                $online++;
                self::clearAlertsForRouter($router->id);
            } else {
                $router->status = 'Offline';
                $offline++;
                self::handleOfflineRouter($router, $previous, $now);
            }

            $router->save();
            $checked++;
        }

        return [
            'checked' => $checked,
            'online' => $online,
            'offline' => $offline,
        ];
    }

    public static function isRouterReachable($ipAddress)
    {
        $parsed = self::parseIpPort($ipAddress);
        $host = $parsed['host'];
        $port = $parsed['port'];

        if ($host === '') {
            return false;
        }

        if (self::pingHost($host)) {
            return true;
        }

        return self::checkTcpPort($host, $port);
    }

    public static function pingHost($host, $timeout = 2)
    {
        $host = preg_replace('/[^a-zA-Z0-9.\-]/', '', $host);
        if ($host === '') {
            return false;
        }

        if (!function_exists('exec') || stripos((string) ini_get('disable_functions'), 'exec') !== false) {
            return false;
        }

        if (stripos(PHP_OS, 'WIN') === 0) {
            $cmd = 'ping -n 1 -w ' . intval($timeout * 1000) . ' ' . escapeshellarg($host);
        } else {
            $wait = max(1, intval($timeout));
            $cmd = 'ping -c 1 -W ' . $wait . ' ' . escapeshellarg($host) . ' 2>/dev/null';
        }

        exec($cmd, $output, $code);

        return $code === 0;
    }

    public static function checkTcpPort($host, $port, $timeout = 3)
    {
        try {
            if (is_callable('fsockopen') && stripos((string) ini_get('disable_functions'), 'fsockopen') === false) {
                $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
                if ($fp) {
                    fclose($fp);
                    return true;
                }
            } elseif (is_callable('stream_socket_client') && stripos((string) ini_get('disable_functions'), 'stream_socket_client') === false) {
                $conn = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
                if ($conn) {
                    fclose($conn);
                    return true;
                }
            }
        } catch (Throwable $e) {
            _log('RouterMonitor TCP: ' . $e->getMessage());
        }

        return false;
    }

    public static function parseIpPort($ipAddress)
    {
        $ipAddress = trim((string) $ipAddress);
        if ($ipAddress === '') {
            return ['host' => '', 'port' => 8728];
        }
        if (strpos($ipAddress, ':') !== false) {
            [$host, $port] = explode(':', $ipAddress, 2);
            return ['host' => trim($host), 'port' => intval($port) ?: 8728];
        }

        return ['host' => $ipAddress, 'port' => 8728];
    }

    protected static function handleOfflineRouter($router, $previousStatus, $checkedAt)
    {
        $wasOnline = strtolower((string) $previousStatus) === 'online';
        $alert = ORM::for_table('tbl_router_alerts')
            ->where('router_id', $router->id)
            ->where('dismissed', 0)
            ->find_one();

        if ($alert) {
            $alert->last_check = $checkedAt;
            $alert->router_name = $router->name;
            $alert->save();
            return;
        }

        $alert = ORM::for_table('tbl_router_alerts')->create();
        $alert->router_id = $router->id;
        $alert->admin_id = $router->admin_id ?? null;
        $alert->router_name = $router->name;
        $alert->last_check = $checkedAt;
        $alert->dismissed = 0;
        $alert->whatsapp_sent = 0;
        $alert->created_at = $checkedAt;
        $alert->save();

        self::notifyWhatsapp($router, $checkedAt);
        $alert->whatsapp_sent = 1;
        $alert->save();

        $message = "Router offline: {$router->name} ({$router->ip_address})";
        if ($wasOnline) {
            $message .= ' (passage Online → Offline)';
        }
        $message .= " — check {$checkedAt}";
        sendTelegram($message);
    }

    protected static function clearAlertsForRouter($routerId)
    {
        ORM::for_table('tbl_router_alerts')
            ->where('router_id', $routerId)
            ->where('dismissed', 0)
            ->delete_many();
    }

    public static function notifyWhatsapp($router, $checkedAt)
    {
        global $config;

        $phone = self::resolveWhatsappPhone($router->admin_id);
        if ($phone === '') {
            return false;
        }

        $msg = "🚨 Alerte routeur : {$router->name}\n";
        $msg .= "Statut : OFFLINE\n";
        $msg .= "Dernier check : {$checkedAt}";

        if (!empty($config['whatsapp_gateway_url']) && !empty($config['whatsapp_gateway_secret'])) {
            Message::sendWhatsapp($phone, $msg);
            return true;
        }

        if (!empty($config['wa_url'])) {
            Message::sendWhatsapp($phone, $msg);
            return true;
        }

        _log('RouterMonitor: WhatsApp non configuré (plugin Gateway ou wa_url).');
        return false;
    }

    public static function resolveWhatsappPhone($adminId = null)
    {
        global $config;

        if (!empty($config['router_alert_whatsapp_phone'])) {
            return trim($config['router_alert_whatsapp_phone']);
        }

        if ($adminId) {
            $admin = ORM::for_table('tbl_users')->find_one($adminId);
            if ($admin && !empty($admin->phone)) {
                return trim($admin->phone);
            }
        }

        if (!empty($config['phone'])) {
            return trim($config['phone']);
        }

        return '';
    }

    public static function getAlertsForAdmin($admin)
    {
        self::ensureSchema();

        $query = ORM::for_table('tbl_router_alerts')
            ->where('dismissed', 0)
            ->order_by_desc('last_check');

        if ($admin['user_type'] != 'SuperAdmin') {
            $query->where_raw('(admin_id = ? OR admin_id IS NULL)', [$admin['id']]);
        }

        $rows = $query->find_many();
        $alerts = [];

        foreach ($rows as $row) {
            $alerts[] = [
                'id' => (int) $row->id,
                'router_id' => (int) $row->router_id,
                'router_name' => $row->router_name,
                'last_check' => $row->last_check,
                'message' => '⚠️ Le routeur ' . $row->router_name . ' est actuellement OFFLINE.',
            ];
        }

        return $alerts;
    }

    public static function dismissAlert($alertId, $admin)
    {
        self::ensureSchema();
        $alert = ORM::for_table('tbl_router_alerts')->find_one($alertId);
        if (!$alert) {
            return false;
        }
        if ($admin['user_type'] != 'SuperAdmin' && $alert->admin_id && (int) $alert->admin_id !== (int) $admin['id']) {
            return false;
        }
        $alert->dismissed = 1;
        $alert->save();
        return true;
    }

    protected static function getConfigValue($key, $default = '')
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        return $row ? $row->value : $default;
    }

    protected static function setConfigValue($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
            $row->value = $value;
            $row->save();
        }
    }
}
