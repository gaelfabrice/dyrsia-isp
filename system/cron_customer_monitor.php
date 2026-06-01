<?php
/**
 * Cron Customer Online Monitor
 * 
 * Jalankan setiap 1 menit via cron:
 * * * * * * cd /data/html/system && php82 cron_customer_monitor.php >/dev/null
 * 
 * Fungsi:
 * - Check status online/offline customer via MikroTik
 * - Kirim Telegram alert jika customer offline > 2 menit
 * - Kirim Telegram alert jika customer online kembali > 1 menit
 */

// Set default values for CLI mode
if (!isset($_SERVER['SERVER_PORT'])) {
    $_SERVER['SERVER_PORT'] = 80;
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// Load .env untuk CLI mode
if (php_sapi_name() === 'cli' || !getenv('DB_USERNAME')) {
    $env_file = realpath(__DIR__ . '/../') . '/.env';
    if (file_exists($env_file)) {
        $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($env_lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                putenv($line);
            }
        }
    }
}

include __DIR__ . "/../init.php";

// Lock file untuk mencegah double run
$lockFile = "$CACHE_PATH/customer_monitor.lock";

if (!is_dir($CACHE_PATH)) {
    echo "Directory '$CACHE_PATH' does not exist. Exiting...\n";
    exit;
}

$lock = fopen($lockFile, 'c');

if ($lock === false) {
    echo "Failed to open lock file. Exiting...\n";
    exit;
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Customer monitor is already running. Exiting...\n";
    fclose($lock);
    exit;
}

// Cek apakah CLI atau web
$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}

echo "=== Customer Online Monitor ===\n";
echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";

// Konfigurasi delay (dalam detik)
$offlineDelaySeconds = 120;  // 2 menit untuk alert offline
$onlineDelaySeconds = 60;    // 1 menit untuk alert online kembali

// Cek apakah fitur check online diaktifkan
if ($config['check_customer_online'] != 'yes') {
    echo "Customer online check is disabled. Exiting...\n";
    flock($lock, LOCK_UN);
    fclose($lock);
    unlink($lockFile);
    exit;
}

// Pastikan tabel status tracking ada
ensureStatusTableExists();

// Ambil semua customer dengan koordinat dan paket aktif
$customers = getActiveCustomersWithCoordinates();
echo "Found " . count($customers) . " customer(s) with coordinates\n";

if (empty($customers)) {
    echo "No customers to monitor. Exiting...\n";
    flock($lock, LOCK_UN);
    fclose($lock);
    unlink($lockFile);
    exit;
}

// Group customers by router untuk efisiensi
$customersByRouter = groupCustomersByRouter($customers);

// Process setiap router
$statusResults = [];
foreach ($customersByRouter as $routerName => $routerData) {
    echo "\nChecking router: $routerName (" . count($routerData['customers']) . " customers)\n";
    
    $results = checkCustomersOnRouter($routerData);
    $statusResults = array_merge($statusResults, $results);
}

// Process status changes dan kirim Telegram
$alertsSent = 0;
foreach ($statusResults as $result) {
    $alertSent = processCustomerStatus($result, $offlineDelaySeconds, $onlineDelaySeconds);
    if ($alertSent) {
        $alertsSent++;
    }
}

echo "\n=== Summary ===\n";
echo "Customers checked: " . count($statusResults) . "\n";
echo "Alerts sent: $alertsSent\n";

// Cleanup
flock($lock, LOCK_UN);
fclose($lock);
unlink($lockFile);

echo "Customer monitor finished.\n";

// ==================== FUNCTIONS ====================

/**
 * Pastikan tabel tracking status ada
 */
function ensureStatusTableExists()
{
    $tableExists = ORM::raw_execute("SHOW TABLES LIKE 'tbl_customer_online_status'");
    $statement = ORM::get_last_statement();
    $result = $statement->fetch();
    
    if (!$result) {
        echo "Creating tbl_customer_online_status table...\n";
        ORM::raw_execute("
            CREATE TABLE `tbl_customer_online_status` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `customer_id` INT(11) NOT NULL,
                `username` VARCHAR(100) DEFAULT NULL,
                `status` ENUM('online','isolir','offline','off_isolir') NOT NULL DEFAULT 'offline',
                `current_ip` VARCHAR(45) DEFAULT NULL,
                `last_seen` DATETIME DEFAULT NULL,
                `status_changed_at` DATETIME DEFAULT NULL,
                `offline_alert_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `online_alert_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_id` (`customer_id`),
                KEY `status` (`status`),
                KEY `status_changed_at` (`status_changed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        echo "Table created successfully.\n";
    } else {
        // Cek apakah perlu update struktur tabel (migrasi dari versi lama)
        $columnExists = ORM::raw_execute("SHOW COLUMNS FROM `tbl_customer_online_status` LIKE 'current_ip'");
        $colStatement = ORM::get_last_statement();
        $colResult = $colStatement->fetch();
        
        if (!$colResult) {
            echo "Updating tbl_customer_online_status table structure...\n";
            ORM::raw_execute("ALTER TABLE `tbl_customer_online_status` ADD COLUMN `current_ip` VARCHAR(45) DEFAULT NULL AFTER `status`");
            ORM::raw_execute("ALTER TABLE `tbl_customer_online_status` MODIFY COLUMN `status` ENUM('online','isolir','offline','off_isolir') NOT NULL DEFAULT 'offline'");
            echo "Table structure updated.\n";
        }
    }
}

/**
 * Ambil customer aktif dengan koordinat (termasuk yang isolir)
 */
function getActiveCustomersWithCoordinates()
{
    $customers = ORM::for_table('tbl_customers')
        ->select('tbl_customers.*')
        ->select('tbl_user_recharges.routers', 'router_name')
        ->select('tbl_user_recharges.plan_id', 'plan_id')
        ->select('tbl_user_recharges.status', 'recharge_status')
        ->select('tbl_user_recharges.namebp', 'namebp')
        ->join('tbl_user_recharges', ['tbl_customers.id', '=', 'tbl_user_recharges.customer_id'])
        ->where('tbl_customers.status', 'Active')
        ->where_not_equal('tbl_customers.coordinates', '')
        ->order_by_desc('tbl_user_recharges.id')
        ->group_by('tbl_customers.id')
        ->find_array();
    
    return $customers;
}

/**
 * Group customers by router
 */
function groupCustomersByRouter($customers)
{
    $grouped = [];
    
    foreach ($customers as $customer) {
        $routerName = $customer['router_name'];
        
        if (!isset($grouped[$routerName])) {
            // Ambil plan untuk router ini
            $plan = ORM::for_table('tbl_plans')->find_one($customer['plan_id']);
            
            $grouped[$routerName] = [
                'plan' => $plan ? $plan->as_array() : null,
                'customers' => []
            ];
        }
        
        $grouped[$routerName]['customers'][] = $customer;
    }
    
    return $grouped;
}

/**
 * Check status customers pada router tertentu
 */
function checkCustomersOnRouter($routerData)
{
    global $_app_stage;
    
    $results = [];
    $plan = $routerData['plan'];
    $customers = $routerData['customers'];
    
    if (!$plan) {
        // Tidak ada plan, set semua sebagai unknown
        foreach ($customers as $customer) {
            $results[] = [
                'customer_id' => $customer['id'],
                'customer' => $customer,
                'in_active' => null,
                'current_ip' => null
            ];
        }
        return $results;
    }
    
    $dvc = Package::getDevice($plan);
    
    if ($_app_stage == 'Demo' || !file_exists($dvc)) {
        // Demo mode atau device tidak ada
        foreach ($customers as $customer) {
            $results[] = [
                'customer_id' => $customer['id'],
                'customer' => $customer,
                'in_active' => null,
                'current_ip' => null
            ];
        }
        return $results;
    }
    
    require_once $dvc;
    
    // Set timeout pendek
    ini_set('default_socket_timeout', 5);
    
    // Get router info untuk koneksi langsung
    $mikrotik = ORM::for_table('tbl_routers')->where('name', $plan['routers'])->find_one();
    
    if (!$mikrotik) {
        foreach ($customers as $customer) {
            $results[] = [
                'customer_id' => $customer['id'],
                'customer' => $customer,
                'in_active' => null,
                'current_ip' => null
            ];
        }
        return $results;
    }
    
    try {
        // Connect ke MikroTik untuk ambil semua active connections sekali saja
        $iport = explode(":", $mikrotik['ip_address']);
        $client = new PEAR2\Net\RouterOS\Client(
            $iport[0], 
            $mikrotik['username'], 
            $mikrotik['password'], 
            isset($iport[1]) ? $iport[1] : null
        );
        
        // Ambil semua PPP active connections
        $printRequest = new PEAR2\Net\RouterOS\Request('/ppp/active/print');
        $responses = $client->sendSync($printRequest);
        
        // Build array of active connections dengan IP
        $activeConnections = [];
        foreach ($responses as $response) {
            $name = $response->getProperty('name');
            $address = $response->getProperty('address');
            if (!empty($name)) {
                $activeConnections[strtolower($name)] = $address ?: '';
            }
        }
        
        // Check setiap customer
        foreach ($customers as $customer) {
            $pppoeUsername = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
            $pppoeUsernameLower = strtolower($pppoeUsername);
            
            $inActive = isset($activeConnections[$pppoeUsernameLower]);
            $currentIp = $inActive ? $activeConnections[$pppoeUsernameLower] : null;
            
            $results[] = [
                'customer_id' => $customer['id'],
                'customer' => $customer,
                'in_active' => $inActive,
                'current_ip' => $currentIp
            ];
        }
        
    } catch (Exception $e) {
        echo "Error connecting to router: " . $e->getMessage() . "\n";
        // Error pada router, set semua customer sebagai unknown
        foreach ($customers as $customer) {
            $results[] = [
                'customer_id' => $customer['id'],
                'customer' => $customer,
                'in_active' => null,
                'current_ip' => null
            ];
        }
    }
    
    return $results;
}

/**
 * Process status customer dan kirim Telegram jika perlu
 */
function processCustomerStatus($result, $offlineDelay, $onlineDelay)
{
    $customerId = $result['customer_id'];
    $customer = $result['customer'];
    $inActive = $result['in_active'];
    $currentIp = $result['current_ip'];
    $now = date('Y-m-d H:i:s');
    $alertSent = false;
    
    // Skip jika status unknown (null)
    if ($inActive === null) {
        return false;
    }
    
    // Tentukan status berdasarkan kombinasi active connection + recharge status
    // recharge_status: 'on' = aktif, 'off' = isolir
    $rechargeStatus = isset($customer['recharge_status']) ? $customer['recharge_status'] : 'on';
    $isIsolir = ($rechargeStatus == 'off');
    
    /*
     * Logic 4 status:
     * 1. online    = Ada di active connection + recharge_status = 'on'
     * 2. isolir    = Ada di active connection + recharge_status = 'off'
     * 3. offline   = Tidak ada di active connection + recharge_status = 'on'
     * 4. off_isolir = Tidak ada di active connection + recharge_status = 'off'
     */
    if ($inActive && !$isIsolir) {
        $newStatus = 'online';
    } elseif ($inActive && $isIsolir) {
        $newStatus = 'isolir';
    } elseif (!$inActive && !$isIsolir) {
        $newStatus = 'offline';
    } else {
        $newStatus = 'off_isolir';
    }
    
    // Get atau create status tracking
    $tracking = ORM::for_table('tbl_customer_online_status')
        ->where('customer_id', $customerId)
        ->find_one();
    
    if (!$tracking) {
        // Buat record baru
        $tracking = ORM::for_table('tbl_customer_online_status')->create();
        $tracking->customer_id = $customerId;
        $tracking->username = $customer['username'];
        $tracking->status = $newStatus;
        $tracking->current_ip = $currentIp;
        $tracking->last_seen = $inActive ? $now : null;
        $tracking->status_changed_at = $now;
        $tracking->offline_alert_sent = 0;
        $tracking->online_alert_sent = 0;
        $tracking->save();
        
        echo "  [NEW] Customer #{$customerId} ({$customer['username']}) - " . strtoupper($newStatus) . ($currentIp ? " (IP: {$currentIp})" : "") . "\n";
        return false;
    }
    
    $previousStatus = $tracking->status;
    
    // Update current_ip dan last_seen jika ada di active connection
    if ($inActive) {
        $tracking->current_ip = $currentIp;
        $tracking->last_seen = $now;
    }
    
    // Cek apakah status berubah
    if ($previousStatus !== $newStatus) {
        // Status berubah!
        echo "  [CHANGED] Customer #{$customerId} - {$previousStatus} -> {$newStatus}" . ($currentIp ? " (IP: {$currentIp})" : "") . "\n";
        
        $tracking->status = $newStatus;
        $tracking->status_changed_at = $now;
        
        // Reset flags berdasarkan perubahan status
        if ($newStatus === 'offline' || $newStatus === 'off_isolir') {
            // Baru offline/off_isolir - reset flags
            $tracking->offline_alert_sent = 0;
            $tracking->online_alert_sent = 0;
        } elseif ($newStatus === 'online' || $newStatus === 'isolir') {
            // Baru online/isolir - reset online flag saja
            $tracking->online_alert_sent = 0;
        }
        
        $tracking->save();
    } else {
        // Status sama, update IP jika berubah
        if ($currentIp && $tracking->current_ip !== $currentIp) {
            $tracking->current_ip = $currentIp;
        }
        $tracking->save();
        
        // Cek apakah perlu kirim alert (untuk semua status termasuk isolir)
        $statusChangedAt = strtotime($tracking->status_changed_at);
        $nowTimestamp = strtotime($now);
        $elapsedSeconds = $nowTimestamp - $statusChangedAt;
        
        if (($newStatus === 'offline' || $newStatus === 'off_isolir') && !$tracking->offline_alert_sent) {
            // Cek apakah sudah lewat delay offline
            if ($elapsedSeconds >= $offlineDelay) {
                echo "  [ALERT] Customer #{$customerId} - " . strtoupper($newStatus) . " for " . round($elapsedSeconds/60, 1) . " minutes - Sending Telegram...\n";
                
                $sent = sendCustomerTelegramAlert($customer, $newStatus, $tracking->status_changed_at, $tracking->current_ip);
                if ($sent) {
                    $tracking->offline_alert_sent = 1;
                    $tracking->save();
                    $alertSent = true;
                }
            }
        } elseif (($newStatus === 'online' || $newStatus === 'isolir') && !$tracking->online_alert_sent && $tracking->offline_alert_sent) {
            // Cek apakah sudah lewat delay online (dan sebelumnya pernah kirim offline alert)
            if ($elapsedSeconds >= $onlineDelay) {
                echo "  [ALERT] Customer #{$customerId} - " . strtoupper($newStatus) . " for " . round($elapsedSeconds/60, 1) . " minutes - Sending Telegram...\n";
                
                $sent = sendCustomerTelegramAlert($customer, $newStatus, $now, $tracking->current_ip);
                if ($sent) {
                    $tracking->online_alert_sent = 1;
                    $tracking->save();
                    $alertSent = true;
                }
            }
        }
    }
    
    return $alertSent;
}

/**
 * Kirim Telegram alert untuk customer
 */
function sendCustomerTelegramAlert($customer, $alertType, $timestamp, $currentIp = null)
{
    // Get ODP name jika ada
    $odpName = '-';
    if (!empty($customer['odp_id'])) {
        $odp = ORM::for_table('tbl_odp')->find_one($customer['odp_id']);
        if ($odp) {
            $odpName = $odp->name;
        }
    }
    
    // Prepare data
    $pppoeUsername = !empty($customer['pppoe_username']) ? $customer['pppoe_username'] : $customer['username'];
    $ipAddress = !empty($currentIp) ? $currentIp : (!empty($customer['pppoe_ip']) ? $customer['pppoe_ip'] : '-');
    $address = !empty($customer['address']) ? $customer['address'] : '-';
    $namaPaket = !empty($customer['namebp']) ? $customer['namebp'] : '-';
    
    // Tentukan status paket berdasarkan recharge_status
    $rechargeStatus = isset($customer['recharge_status']) ? $customer['recharge_status'] : 'on';
    $statusPaket = ($rechargeStatus == 'off') ? 'Isolir' : 'Aktif';
    
    // Format timestamp
    $formattedTime = date('d/m/Y H:i', strtotime($timestamp));
    
    // Build message berdasarkan alertType
    if ($alertType === 'offline') {
        $emoji = '🚨';
        $title = 'PELANGGAN OFFLINE';
        $timeLabel = 'Offline sejak';
    } elseif ($alertType === 'off_isolir') {
        $emoji = '🚨';
        $title = 'PELANGGAN ISOLIR OFFLINE';
        $timeLabel = 'Offline sejak';
    } elseif ($alertType === 'online') {
        $emoji = '✅';
        $title = 'PELANGGAN ONLINE KEMBALI';
        $timeLabel = 'Online sejak';
    } elseif ($alertType === 'isolir') {
        $emoji = '✅';
        $title = 'PELANGGAN ISOLIR ONLINE KEMBALI';
        $timeLabel = 'Online sejak';
    } else {
        $emoji = 'ℹ️';
        $title = 'STATUS PELANGGAN';
        $timeLabel = 'Waktu';
    }
    
    $message = "{$emoji} {$title}\n";
    $message .= "━━━━━━━━━━━━━━━\n";
    $message .= "👤 Nama: {$customer['fullname']}\n";
    $message .= "📍 Alamat: {$address}\n";
    $message .= "📦 Paket: {$namaPaket}\n";
    $message .= "📋 Status Paket: {$statusPaket}\n";
    $message .= "🌐 PPPoE: {$pppoeUsername}\n";
    $message .= "🖥️ IP: " . ($ipAddress !== '-' ? "http://{$ipAddress}" : '-') . "\n";
    $message .= "📡 ODP: {$odpName}\n";
    $message .= "⏰ {$timeLabel}: {$formattedTime}\n";
    $message .= "━━━━━━━━━━━━━━━";
    
    // Send via wifizones Message class
    try {
        if (class_exists('Message') && method_exists('Message', 'sendTelegram')) {
            Message::sendTelegram($message);
            return true;
        } else {
            echo "    Warning: Message class not available\n";
            return false;
        }
    } catch (Exception $e) {
        echo "    Error sending Telegram: " . $e->getMessage() . "\n";
        return false;
    }
}
