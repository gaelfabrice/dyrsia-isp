<?php
/**
 * Diagnostic Data Usage (CLI)
 * Usage: php scripts/diag_data_usage.php
 */
$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
require_once $root . 'init.php';

echo "=== Data Usage Diagnostic ===\n";
echo 'Time: ' . date('Y-m-d H:i:s') . "\n\n";

$db = ORM::get_db();

// Tables
foreach (['api_data_usage', 'api_data_usage_meta'] as $table) {
    try {
        $count = (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "Table $table: $count rows\n";
    } catch (Throwable $e) {
        echo "Table $table: MISSING — " . $e->getMessage() . "\n";
    }
}

// Routers
$routers = ORM::for_table('tbl_routers')->where('enabled', 1)->find_many();
echo "\nEnabled routers: " . count($routers) . "\n";
foreach ($routers as $router) {
    $adminId = $router['admin_id'] ?? 'NULL';
    echo "  - {$router['name']} | {$router['ip_address']} | admin_id=$adminId\n";

    $metaKey = 'router_api_status_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $router['name']);
    $meta = ORM::for_table('api_data_usage_meta')->where('meta_key', $metaKey)->find_one();
    if ($meta) {
        $status = json_decode((string) $meta->meta_value, true);
        echo "    API status: " . json_encode($status) . "\n";
    } else {
        echo "    API status: never synced\n";
    }
}

// Cron last run
global $UPLOAD_PATH;
$cronFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
if (file_exists($cronFile)) {
    $ts = trim((string) file_get_contents($cronFile));
    echo "\nCron last run: $ts (" . (is_numeric($ts) ? date('Y-m-d H:i:s', (int) $ts) : $ts) . ")\n";
} else {
    echo "\nCron last run: file missing ($cronFile)\n";
}

// Test sync
echo "\nRunning sync...\n";
require_once $root . 'system/cron_data_usage.php';
$result = cron_data_usage_sync();
echo 'Inserted: ' . (int) ($result['inserted'] ?? 0) . "\n";
if (!empty($result['errors'])) {
    echo "Errors:\n";
    foreach ($result['errors'] as $err) {
        echo "  - $err\n";
    }
} else {
    echo "No sync errors.\n";
}
