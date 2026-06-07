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
}

cron_data_usage_install();
$db = ORM::get_db();
$currentTime = date('Y-m-d H:i:s');
$db->exec("DELETE FROM `api_data_usage` WHERE `log_date` < NOW() - INTERVAL 365 DAY");
$routers = ORM::for_table('tbl_routers')->where('enabled', 1)->find_many();
$lastRows = $db->query("SELECT meta_key, meta_value FROM api_data_usage_meta WHERE meta_key LIKE 'last_router_counters_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalInserted = 0;

foreach ($routers as $router) {
    $routerName = $router['name'];
    $routerAdminId = isset($router['admin_id']) ? (int) $router['admin_id'] : null;
    $metaKey = 'last_router_counters_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
    $lastCounters = isset($lastRows[$metaKey]) ? json_decode($lastRows[$metaKey], true) : [];
    if (!is_array($lastCounters)) {
        $lastCounters = [];
    }
    $currentCounters = [];
    try {
        $password = $router['password'];
        if (function_exists('lcg_decrypt')) {
            $password = rtrim(lcg_decrypt($password));
        } elseif (class_exists('Encryption') && method_exists('Encryption', 'decrypt')) {
            $password = rtrim(Encryption::decrypt($password));
        }
        if (!class_exists('Mikrotik')) {
            continue;
        }
        $client = Mikrotik::getClient($router['ip_address'], $router['username'], $password);
        if (!$client) {
            continue;
        }
        $users = [];
        $pppoeRequest = new PEAR2\Net\RouterOS\Request('/interface/print');
        $pppoeRequest->setQuery(PEAR2\Net\RouterOS\Query::where('type', 'pppoe-in'));
        foreach ($client->sendSync($pppoeRequest) as $iface) {
            $username = str_replace(['<pppoe-', '>', 'pppoe-'], '', $iface->getProperty('name'));
            if ($username !== '') {
                $users[$username] = ['download' => (double) $iface->getProperty('tx-byte'), 'upload' => (double) $iface->getProperty('rx-byte'), 'status' => 'Connected'];
            }
        }
        $hotspotRequest = new PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
        foreach ($client->sendSync($hotspotRequest) as $active) {
            $username = $active->getProperty('user');
            if ($username !== '' && !isset($users[$username])) {
                $users[$username] = ['download' => (double) $active->getProperty('bytes-out'), 'upload' => (double) $active->getProperty('bytes-in'), 'status' => 'Connected'];
            }
        }
        foreach ($users as $username => $metrics) {
            $currentCounters[$username] = ['dl' => $metrics['download'], 'ul' => $metrics['upload']];
            $old = $lastCounters[$username] ?? null;
            $diffDl = $old && $metrics['download'] >= $old['dl'] ? $metrics['download'] - $old['dl'] : $metrics['download'];
            $diffUl = $old && $metrics['upload'] >= $old['ul'] ? $metrics['upload'] - $old['ul'] : $metrics['upload'];
            if ($diffDl <= 0 && $diffUl <= 0) {
                continue;
            }
            $stmt = $db->prepare("INSERT INTO api_data_usage (admin_id, username, router_name, download_bytes, upload_bytes, total_bytes, status, log_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$routerAdminId, $username, $routerName, $diffDl, $diffUl, $diffDl + $diffUl, $metrics['status'], $currentTime]);
            $totalInserted++;
        }
        $json = json_encode($currentCounters);
        $stmt = $db->prepare("INSERT INTO api_data_usage_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
        $stmt->execute([$metaKey, $json, $json]);
        $statusKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
        $statusJson = json_encode(['ok' => true, 'at' => $currentTime]);
        $stmt->execute([$statusKey, $statusJson, $statusJson]);
    } catch (Exception $e) {
        $statusKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $routerName);
        $statusJson = json_encode(['ok' => false, 'at' => $currentTime, 'error' => $e->getMessage()]);
        $stmt = $db->prepare("INSERT INTO api_data_usage_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = ?");
        $stmt->execute([$statusKey, $statusJson, $statusJson]);
        continue;
    }
}

echo "Data usage sync completed. Rows inserted: " . $totalInserted . PHP_EOL;
