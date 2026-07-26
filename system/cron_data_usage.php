<?php

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? 80;
require_once __DIR__ . '/../init.php';

function cron_data_usage_install()
{
    $db = ORM::get_db();
    $db->exec("CREATE TABLE IF NOT EXISTS `api_data_usage` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `admin_id` int(11) DEFAULT NULL,
      `username` varchar(64) NOT NULL,
      `router_name` varchar(64) DEFAULT 'Unknown',
      `download_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `upload_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `total_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
      `status` varchar(20) DEFAULT 'Disconnected',
      `log_date` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `admin_date` (`admin_id`, `log_date`),
      KEY `username_date` (`username`, `log_date`),
      KEY `router_name` (`router_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS `api_data_usage_meta` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `meta_key` varchar(128) NOT NULL,
      `meta_value` LONGTEXT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `meta_key` (`meta_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try {
        $columns = $db->query("SHOW COLUMNS FROM api_data_usage LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            $db->exec("ALTER TABLE `api_data_usage` ADD COLUMN `admin_id` int(11) DEFAULT NULL AFTER `id`");
            $db->exec("ALTER TABLE `api_data_usage` ADD KEY `admin_date` (`admin_id`, `log_date`)");
        }
    } catch (Exception $e) {
    }
    try {
        $columns = $db->query("SHOW COLUMNS FROM tbl_routers LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            $db->exec("ALTER TABLE `tbl_routers` ADD COLUMN `admin_id` int(11) DEFAULT NULL AFTER `id`");
        }
    } catch (Exception $e) {
    }
}

function cron_data_usage_resolve_admin_id($username, $routerAdminId)
{
    if ($routerAdminId > 0) {
        return (int) $routerAdminId;
    }
    $customer = ORM::for_table('tbl_customers')
        ->where_raw('username = ? OR pppoe_username = ?', [$username, $username])
        ->find_one();
    if ($customer && (int) ($customer['created_by'] ?? 0) > 0) {
        return (int) $customer['created_by'];
    }

    return null;
}

cron_data_usage_install();

function cron_data_usage_collect_pppoe_users($client)
{
    $users = [];

    try {
        $pppoeRequest = new PEAR2\Net\RouterOS\Request('/interface/print');
        $pppoeRequest->setArgument('.proplist', 'name,type,tx-byte,rx-byte');
        $pppoeRequest->setQuery(PEAR2\Net\RouterOS\Query::where('type', 'pppoe-in'));
        foreach ($client->sendSync($pppoeRequest) as $iface) {
            $ifaceName = (string) $iface->getProperty('name');
            if (!preg_match('/pppoe[-<]([^>]+)>?/', $ifaceName, $matches)) {
                continue;
            }
            $username = trim($matches[1]);
            if ($username === '') {
                continue;
            }
            $users[$username] = [
                'download' => (double) $iface->getProperty('tx-byte'),
                'upload' => (double) $iface->getProperty('rx-byte'),
                'status' => 'Connected',
            ];
        }
    } catch (Exception $e) {
    }

    if (!empty($users)) {
        return $users;
    }

    try {
        $pppActiveRequest = new PEAR2\Net\RouterOS\Request('/ppp/active/print');
        $pppActiveRequest->setArgument('.proplist', 'name,bytes-in,bytes-out');
        foreach ($client->sendSync($pppActiveRequest) as $active) {
            $username = trim((string) $active->getProperty('name'));
            if ($username === '') {
                continue;
            }
            $download = (double) $active->getProperty('bytes-out');
            $upload = (double) $active->getProperty('bytes-in');
            if ($download <= 0 && $upload <= 0) {
                continue;
            }
            $users[$username] = [
                'download' => $download,
                'upload' => $upload,
                'status' => 'Connected',
            ];
        }
    } catch (Exception $e) {
    }

    return $users;
}

function cron_data_usage_live_sessions_cache_key($admin = null)
{
    if (is_array($admin) && (($admin['user_type'] ?? '') !== 'SuperAdmin')) {
        return 'live_sessions_admin_' . (int) $admin['id'];
    }
    return 'live_sessions_all';
}

function cron_data_usage_read_live_sessions_cache($admin = null, $maxAge = 45)
{
    $metaKey = cron_data_usage_live_sessions_cache_key($admin);
    try {
        $metaRow = ORM::for_table('api_data_usage_meta')->where('meta_key', $metaKey)->find_one();
        if (!$metaRow) {
            return null;
        }
        $payload = json_decode((string) $metaRow->meta_value, true);
        if (!is_array($payload) || !isset($payload['at'], $payload['sessions']) || !is_array($payload['sessions'])) {
            return null;
        }
        if ((time() - strtotime((string) $payload['at'])) > $maxAge) {
            return null;
        }
        return $payload['sessions'];
    } catch (Exception $e) {
        return null;
    }
}

function cron_data_usage_write_live_sessions_cache($admin, array $sessions)
{
    $metaKey = cron_data_usage_live_sessions_cache_key($admin);
    $payload = json_encode([
        'at' => date('Y-m-d H:i:s'),
        'sessions' => $sessions,
    ]);
    try {
        $db = ORM::get_db();
        $stmt = $db->prepare("INSERT INTO api_data_usage_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
        $stmt->execute([$metaKey, $payload, $payload]);
    } catch (Exception $e) {
    }
}

function cron_data_usage_collect_router_live_sessions($client, $routerName)
{
    $live = [];
    try {
        $pppActiveRequest = new PEAR2\Net\RouterOS\Request('/ppp/active/print');
        $pppActiveRequest->setArgument('.proplist', 'name');
        foreach ($client->sendSync($pppActiveRequest) as $active) {
            $username = trim((string) $active->getProperty('name'));
            if ($username === '') {
                continue;
            }
            $key = strtolower($routerName . '|' . $username);
            $live[$key] = [
                'username' => $username,
                'router' => $routerName,
                'status' => 'Connected',
            ];
        }
    } catch (Exception $e) {
    }
    try {
        $hotspotRequest = new PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
        $hotspotRequest->setArgument('.proplist', 'user');
        foreach ($client->sendSync($hotspotRequest) as $active) {
            $username = trim((string) $active->getProperty('user'));
            if ($username === '') {
                continue;
            }
            $key = strtolower($routerName . '|' . $username);
            if (!isset($live[$key])) {
                $live[$key] = [
                    'username' => $username,
                    'router' => $routerName,
                    'status' => 'Connected',
                ];
            }
        }
    } catch (Exception $e) {
    }
    return $live;
}

function cron_data_usage_fetch_live_sessions($admin = null, $maxAge = 45)
{
    if (class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin)) {
        return [];
    }

    $cached = cron_data_usage_read_live_sessions_cache($admin, $maxAge);
    if (is_array($cached)) {
        return $cached;
    }

    $routerQuery = ORM::for_table('tbl_routers')->where('enabled', 1);
    if (is_array($admin) && (($admin['user_type'] ?? '') !== 'SuperAdmin')) {
        $routerQuery->where('admin_id', (int) $admin['id']);
    }
    $routers = $routerQuery->find_many();
    $live = [];

    if (!class_exists('Mikrotik')) {
        return $live;
    }

    foreach ($routers as $router) {
        $routerName = (string) $router['name'];
        try {
            $password = Mikrotik::routerPassword($router['password']);
            $client = Mikrotik::getClient($router['ip_address'], $router['username'], $password, 4);
            if (!$client) {
                continue;
            }
            $live = array_merge($live, cron_data_usage_collect_router_live_sessions($client, $routerName));
        } catch (Throwable $e) {
        }
    }

    cron_data_usage_write_live_sessions_cache($admin, $live);

    return $live;
}

function cron_data_usage_sync()
{
    $db = ORM::get_db();
    $currentTime = date('Y-m-d H:i:s');
    $db->exec("DELETE FROM `api_data_usage` WHERE `log_date` < NOW() - INTERVAL 365 DAY");
    try {
        $db->exec("UPDATE api_data_usage u
            INNER JOIN tbl_routers r ON r.name = u.router_name
            SET u.admin_id = r.admin_id
            WHERE r.admin_id > 0");
        $db->exec("UPDATE api_data_usage u
            INNER JOIN tbl_customers c ON (
                u.username = c.pppoe_username OR u.username = c.username
            )
            INNER JOIN tbl_routers r ON r.name = u.router_name AND r.admin_id = c.created_by
            SET u.admin_id = c.created_by
            WHERE (u.admin_id IS NULL OR u.admin_id = 0) AND c.created_by > 0");
    } catch (Exception $e) {
    }
    $routers = ORM::for_table('tbl_routers')->where('enabled', 1)->find_many();
    $lastRows = $db->query("SELECT meta_key, meta_value FROM api_data_usage_meta WHERE meta_key LIKE 'last_router_counters_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalInserted = 0;
    $errors = [];

    foreach ($routers as $router) {
        $routerName = $router['name'];
        $routerAdminId = (int) ($router['admin_id'] ?? 0);
        $metaKey = 'last_router_counters_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
        $lastCounters = isset($lastRows[$metaKey]) ? json_decode($lastRows[$metaKey], true) : [];
        if (!is_array($lastCounters)) {
            $lastCounters = [];
        }
        $currentCounters = [];
        try {
            $password = class_exists('Mikrotik')
                ? Mikrotik::routerPassword($router['password'])
                : $router['password'];
            if (!class_exists('Mikrotik')) {
                $errors[] = $routerName . ': classe Mikrotik indisponible';
                continue;
            }
            $client = Mikrotik::getClient($router['ip_address'], $router['username'], $password, 20);
            if (!$client) {
                $errors[] = $routerName . ': connexion API impossible';
                continue;
            }
            $users = cron_data_usage_collect_pppoe_users($client);
            $hotspotRequest = new PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
            foreach ($client->sendSync($hotspotRequest) as $active) {
                $username = $active->getProperty('user');
                if ($username !== '' && !isset($users[$username])) {
                    $users[$username] = [
                        'download' => (double) $active->getProperty('bytes-out'),
                        'upload' => (double) $active->getProperty('bytes-in'),
                        'status' => 'Connected',
                    ];
                }
            }
            foreach ($users as $username => $metrics) {
                if ($username === '') {
                    continue;
                }
                $currentCounters[$username] = ['dl' => $metrics['download'], 'ul' => $metrics['upload']];
                $old = $lastCounters[$username] ?? null;
                $diffDl = $old && $metrics['download'] >= $old['dl'] ? $metrics['download'] - $old['dl'] : $metrics['download'];
                $diffUl = $old && $metrics['upload'] >= $old['ul'] ? $metrics['upload'] - $old['ul'] : $metrics['upload'];
                if ($diffDl <= 0 && $diffUl <= 0) {
                    continue;
                }
                $usageAdminId = cron_data_usage_resolve_admin_id($username, $routerAdminId);
                $stmt = $db->prepare("INSERT INTO api_data_usage (admin_id, username, router_name, download_bytes, upload_bytes, total_bytes, status, log_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$usageAdminId, $username, $routerName, $diffDl, $diffUl, $diffDl + $diffUl, $metrics['status'], $currentTime]);
                $totalInserted++;
            }
            $json = json_encode($currentCounters);
            $stmt = $db->prepare("INSERT INTO api_data_usage_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
            $stmt->execute([$metaKey, $json, $json]);
            $statusKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
            $statusJson = json_encode(['ok' => true, 'at' => $currentTime]);
            $stmt->execute([$statusKey, $statusJson, $statusJson]);
        } catch (Exception $e) {
            $errors[] = $routerName . ': ' . $e->getMessage();
            $statusKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
            $statusJson = json_encode(['ok' => false, 'at' => $currentTime, 'error' => $e->getMessage()]);
            $stmt = $db->prepare("INSERT INTO api_data_usage_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
            $stmt->execute([$statusKey, $statusJson, $statusJson]);
            continue;
        }
    }

    return [
        'inserted' => $totalInserted,
        'errors' => $errors,
    ];
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $result = cron_data_usage_sync();
    echo "Data usage sync completed. Rows inserted: " . (int) ($result['inserted'] ?? 0) . PHP_EOL;
    if (!empty($result['errors'])) {
        echo implode(PHP_EOL, $result['errors']) . PHP_EOL;
    }
}
