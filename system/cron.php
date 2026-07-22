<?php

require_once __DIR__ . '/../init.php';

if (php_sapi_name() !== 'cli') {
    WifiZoneSecurity::requireServiceToken('cron', true);
}

$lockFile = "$CACHE_PATH/router_monitor.lock";

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
    echo "Script is already running. Exiting...\n";
    fclose($lock);
    exit;
}


$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}
echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = [];
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

$_c = $config;

run_hook('cronjob'); #HOOK
Package::processExpiredRecharges(['silent' => false, 'reinforce_routers' => true]);

//Cek interim-update radiusrest
if ($config['frrest_interim_update'] != 0) {

    $r_a = ORM::for_table('rad_acct')
        ->whereRaw("BINARY acctstatustype = 'Start' OR acctstatustype = 'Interim-Update'")
        ->where_lte('dateAdded', date("Y-m-d H:i:s"))->find_many();

    foreach ($r_a as $ra) {
        $interval = $_c['frrest_interim_update'] * 60;
        $timeUpdate = strtotime($ra['dateAdded']) + $interval;
        $timeNow = strtotime(date("Y-m-d H:i:s"));
        if ($timeNow >= $timeUpdate) {
            $ra->acctstatustype = 'Stop';
            $ra->save();
        }
    }
}

if ($config['router_check']) {
    echo "Router daily monitor (ping)...\n";
    $monitorResult = RouterMonitor::maybeRunDailyCheck(false);
    if (!empty($monitorResult['skipped'])) {
        echo "Skipped: " . ($monitorResult['reason'] ?? 'unknown') . "\n";
    } else {
        echo "Checked: " . ($monitorResult['checked'] ?? 0)
            . ", Online: " . ($monitorResult['online'] ?? 0)
            . ", Offline: " . ($monitorResult['offline'] ?? 0) . "\n";
    }
}

if (is_file(__DIR__ . '/cron_data_usage.php')) {
    echo "Data usage sync...\n";
    require_once __DIR__ . '/cron_data_usage.php';
    $usageResult = cron_data_usage_sync();
    echo "Data usage rows inserted: " . (int) ($usageResult['inserted'] ?? 0) . "\n";
    if (!empty($usageResult['errors'])) {
        echo "Data usage errors:\n";
        foreach ($usageResult['errors'] as $usageError) {
            echo "  - $usageError\n";
        }
    }
}

flock($lock, LOCK_UN);
fclose($lock);
unlink($lockFile);

$timestampFile = "$UPLOAD_PATH/cron_last_run.txt";
file_put_contents($timestampFile, time());

run_hook('cronjob_end'); #HOOK
echo "Cron job finished and completed successfully.\n";