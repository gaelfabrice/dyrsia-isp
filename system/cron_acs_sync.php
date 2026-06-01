<?php
// Set timezone untuk konsistensi
date_default_timezone_set('Asia/Jakarta');  // Sesuaikan dengan timezone Anda

// Set environment for CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SCRIPT_NAME'] = '/cron.php';
}

// Include ORM
require_once __DIR__ . '/orm.php';

// Load .env file untuk CLI
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip komentar
        if (strpos($line, '=') === false) continue; // Skip baris tanpa =
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Database config dari environment
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_DATABASE');
$db_user = getenv('DB_USERNAME');
$db_password = getenv('DB_PASSWORD');

// Initialize database connection
ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);

// Check if CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

ini_set('memory_limit', '1024M');  // Increase untuk handle 1000+ devices safely
set_time_limit(0);  // No timeout untuk large dataset

// Auto create log folder if not exists
$log_dir = __DIR__ . '/uploads/log_acs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    @chown($log_dir, 'nginx');
    @chgrp($log_dir, 'nginx');
}

// Auto delete old logs (older than 7 days)
$old_logs = glob($log_dir . '/sync_*.log');
foreach ($old_logs as $old_log) {
    if (filemtime($old_log) < strtotime('-7 days')) {
        unlink($old_log);
    }
}

// Create/open log file
$log_file = $log_dir . '/sync_' . date('Y-m-d') . '.log';

// Check file size - rotate if > 10MB
if (file_exists($log_file) && filesize($log_file) > 10485760) {
    rename($log_file, $log_file . '.' . date('His') . '.bak');
}

$log_handle = fopen($log_file, 'a');
@chmod($log_file, 0644);
@chown($log_file, 'nginx');
@chgrp($log_file, 'nginx');

// Function to write log
function write_log($message)
{
    global $log_handle;
    $timestamp = date('H:i:s');
    fwrite($log_handle, "[{$timestamp}] {$message}\n");
    echo $message . "\n";  // Also output to console
}

write_log("=== Starting ACS Device Sync ===");
write_log("Time: " . date('Y-m-d H:i:s'));

// Check if specific server_id passed as argument
$target_server_id = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $target_server_id = intval($argv[1]);
    write_log("Target Server ID: {$target_server_id} (Force Sync Mode)");
}

// Get all active servers OR specific server if passed
$servers_query = ORM::for_table('tbl_acs_servers')
    ->where('status', 'active');

if ($target_server_id !== null) {
    $servers_query->where('id', $target_server_id);
}

$servers = $servers_query->find_many();

if ($target_server_id !== null) {
    write_log("Found " . count($servers) . " server (Force Sync Mode)");
} else {
    write_log("Found " . count($servers) . " active servers (Auto Cron Mode)");
}

// Get parameters
$parameters = ORM::for_table('tbl_acs_parameters')
    ->where_raw('(param_type = ? OR param_type = ?)', ['display', 'both'])
    ->where('param_category', 'basic')
    ->find_many();

write_log("Found " . count($parameters) . " parameters");

// Process each server
foreach ($servers as $server) {
    write_log("Processing server: {$server->name}");

    // Fetch devices - handle domain vs IP properly
    if (filter_var($server->host, FILTER_VALIDATE_IP)) {
        // IP address: use HTTP with port
        $url = "http://{$server->host}:{$server->port}/devices";
    } else {
        // Domain: use HTTPS without port
        $url = "https://{$server->host}/devices";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // Increase to 5 minutes for large dataset (200-500 devices)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);  // Add connection timeout 30 seconds
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($server->username && $server->password) {
        curl_setopt($ch, CURLOPT_USERPWD, $server->username . ':' . $server->password);
    }

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);  // Capture error for better debugging
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        write_log("Failed to fetch from {$server->name}");
        write_log("  Error: " . ($curl_error ?: 'Unknown error'));
        write_log("  HTTP Code: {$http_code}");
        continue;
    }

    $devices = json_decode($response, true);
    if (!is_array($devices)) {
        write_log("Invalid response from {$server->name}");
        write_log("  Response preview: " . substr($response, 0, 200));
        continue;
    }

    write_log("Fetched " . count($devices) . " devices");

    $processed = [];

    // Tambah counter untuk tracking
    $update_count = 0;
    $insert_count = 0;
    
    // Array untuk batch processing
    $batch_data = [];

    // Process each device
    foreach ($devices as $device) {
        $device_id = $device['_id'] ?? 'unknown';
        $processed[] = $device_id;

        // Determine status
        $status = 'offline';
        if (isset($device['_lastInform'])) {
            $last_inform_time = strtotime($device['_lastInform']);
            $diff_minutes = (time() - $last_inform_time) / 60;
            if ($diff_minutes <= 5) {
                $status = 'online';
            }
        }

        // Get last inform
        $last_inform = null;
        if (isset($device['_lastInform'])) {
            $last_inform = date('Y-m-d H:i:s', strtotime($device['_lastInform']));
        }

        // Build device data
        $device_data = [
            'device_id' => $device_id,
            'id_raw' => $device_id
        ];

        // Extract basic info
        if (isset($device['_tags']) && is_array($device['_tags'])) {
            $device_data['tags'] = $device['_tags'][0] ?? '';
            $device_data['lokasi'] = $device['_tags'][1] ?? '';
        }

        // Process parameters with multiple path support
        foreach ($parameters as $param) {
            $path = $param->param_path;
            $value = 'N/A';

            // Check if multiple paths (comma separated)
            if (strpos($path, ',') !== false) {
                // Multiple paths - try each one
                $paths = explode(',', $path);
                foreach ($paths as $single_path) {
                    $single_path = trim($single_path);

                    // Try to get value from this path
                    if (strpos($single_path, 'VirtualParameters.') === 0) {
                        $vp_name = str_replace('VirtualParameters.', '', $single_path);
                        if (isset($device['VirtualParameters'][$vp_name]['_value'])) {
                            $value = $device['VirtualParameters'][$vp_name]['_value'];
                            break; // Found value, stop checking other paths
                        }
                    }
                }
            } else {
                // Single path - existing logic
                if ($path == '_id') {
                    $value = $device['_id'] ?? 'N/A';
                } elseif ($path == '_lastInform') {
                    $value = $device['_lastInform'] ?? 'N/A';
                } elseif (strpos($path, 'VirtualParameters.') === 0) {
                    $vp_name = str_replace('VirtualParameters.', '', $path);
                    if (isset($device['VirtualParameters'][$vp_name]['_value'])) {
                        $value = $device['VirtualParameters'][$vp_name]['_value'];
                    }
                } elseif (strpos($path, '.') !== false) {
                    // Navigate nested path
                    $parts = explode('.', $path);
                    $temp = $device;
                    foreach ($parts as $part) {
                        if (isset($temp[$part])) {
                            $temp = $temp[$part];
                        } else {
                            $temp = null;
                            break;
                        }
                    }
                    if ($temp !== null) {
                        $value = isset($temp['_value']) ? $temp['_value'] : $temp;
                    }
                }
            }

            $device_data[$param->param_key] = $value;
        }

        $device_data_json = json_encode($device_data);
        $tags_json = json_encode($device['_tags'] ?? []);

        // Kumpulkan data untuk batch processing
        $batch_data[] = [
            'server_id' => $server->id,
            'device_id' => $device_id,
            'status' => $status,
            'last_inform' => $last_inform,
            'tags' => $tags_json,
            'device_data' => $device_data_json
        ];
    }

    // Batch INSERT/UPDATE menggunakan INSERT ... ON DUPLICATE KEY UPDATE
    if (!empty($batch_data)) {
        $batch_size = 100; // Proses 100 device per batch
        $chunks = array_chunk($batch_data, $batch_size);
        
        foreach ($chunks as $chunk) {
            $values = [];
            $params = [];
            
            foreach ($chunk as $data) {
                $values[] = "(?, ?, ?, ?, ?, ?, NOW())";
                $params[] = $data['server_id'];
                $params[] = $data['device_id'];
                $params[] = $data['status'];
                $params[] = $data['last_inform'];
                $params[] = $data['tags'];
                $params[] = $data['device_data'];
            }
            
            $sql = "INSERT INTO tbl_acs_devices 
                    (server_id, device_id, status, last_inform, tags, device_data, last_sync) 
                    VALUES " . implode(", ", $values) . "
                    ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        last_inform = VALUES(last_inform),
                        tags = VALUES(tags),
                        device_data = VALUES(device_data),
                        last_sync = NOW()";
            
            try {
                $pdo = ORM::get_db();
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $update_count += $stmt->rowCount();
            } catch (Exception $e) {
                write_log("Batch error: " . $e->getMessage());
            }
        }
    }

    write_log("Processed " . count($processed) . " devices via batch (Affected rows: {$update_count})");

    // Clean old devices
    if (!empty($processed)) {
        $deleted = ORM::for_table('tbl_acs_devices')
            ->where('server_id', $server->id)
            ->where_not_in('device_id', $processed)
            ->delete_many();

        if ($deleted > 0) {
            echo "Cleaned up $deleted old devices\n";
        }
    }

    // ===== TAMBAHAN: SUMMON OFFLINE DEVICES =====
    write_log("--- Checking offline devices to summon ---");

    // Get offline devices that haven't been summoned in last 15 minutes
    $offline_to_summon = ORM::for_table('tbl_acs_devices')
        ->where('server_id', $server->id)
        ->where('status', 'offline')
        ->where_raw('(last_summon IS NULL OR last_summon < DATE_SUB(NOW(), INTERVAL 15 MINUTE))')
        ->limit(3) // Reduce ke 3 untuk safety dengan cron tiap menit
        ->find_many();

    if (count($offline_to_summon) > 0) {
        write_log("Found " . count($offline_to_summon) . " offline devices to summon");

        // TAMBAH: Counter untuk tracking
        $success_count = 0;
        $failed_count = 0;

        foreach ($offline_to_summon as $device_record) {
            $device_id = $device_record->device_id;
            write_log("  Summoning: {$device_id}");

            // Encode device ID seperti UI (line 690 di genieacs_devices.php)
            $device_id_encoded = urlencode($device_id);

            // Build summon URL dengan ?connection_request seperti UI (line 701)
            if (filter_var($server->host, FILTER_VALIDATE_IP)) {
                $summon_url = "http://{$server->host}:{$server->port}/devices/{$device_id_encoded}/tasks?connection_request";
            } else {
                $summon_url = "https://{$server->host}/devices/{$device_id_encoded}/tasks?connection_request";
            }

            // Send empty array seperti UI (line 700)
            $ch = curl_init($summon_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '[]');  // Empty array seperti UI, bukan object
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            if ($server->username && $server->password) {
                curl_setopt($ch, CURLOPT_USERPWD, $server->username . ':' . $server->password);
            }

            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);  // Capture error message
            curl_close($ch);

            if ($http_code == 200 || $http_code == 202) {
                write_log("  Summoning: {$device_id} [OK]");
                $success_count++;  // TAMBAH
                // Update last_summon timestamp
                $device_record->set('last_summon', date('Y-m-d H:i:s'));
                $device_record->save();
            } else {
                write_log("  Summoning: {$device_id} [FAILED: HTTP {$http_code}]");
                $failed_count++;  // TAMBAH
                // Debug output
                echo "    URL: {$summon_url}\n";
                if ($curl_error) {
                    echo "    Error: {$curl_error}\n";
                }
            }

            // Delay between summons
            sleep(3);  // Reduce ke 3 detik karena limit sudah dikurangi

            // Log failed summons untuk monitoring
            if ($failed_count > 0 && $curl_error) {
                error_log("Summon failed for {$device_id}: {$curl_error}");
            }
        }

        // TAMBAH: Summary hasil summon
        write_log("Summon Summary: {$success_count} success, {$failed_count} failed");
    } else {
        echo "No offline devices need summoning\n";
    }
}

// Final summary
$end_time = microtime(true);
$execution_time = round($end_time - microtime(true), 2);
write_log("=== Sync Completed in {$execution_time} seconds ===");

// Close log file
if (isset($log_handle)) {
    fclose($log_handle);
}
