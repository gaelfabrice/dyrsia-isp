<?php

/**
 * WifiZone cron — file d'attente paiements, rappels, GenieACS, sauvegardes planifiées
 * Crontab: */5 * * * * php /path/to/system/cron_wifizone.php
 */
if (php_sapi_name() !== 'cli') {
    die("CLI only\n");
}

$root = dirname(__DIR__);
require_once $root . '/init.php';

global $UPLOAD_PATH;
file_put_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt', date('c'));

WifiZonePayment::processPendingQueue(30);
WifiZoneNotify::processRenewalReminders();
WifiZoneNotify::checkGenieacsOffline();

$jobs = ORM::for_table('wifizone_backup_jobs')->where('status', 'scheduled')
    ->where_lte('scheduled_at', date('Y-m-d H:i:s'))->find_many();
foreach ($jobs as $job) {
    try {
        if ($job->job_type === 'config') {
            $file = WifiZoneBackup::exportConfigOnly();
        } else {
            $file = WifiZoneBackup::createEncryptedBackup();
        }
        $job->status = 'completed';
        $job->file_path = $file;
        $job->completed_at = date('Y-m-d H:i:s');
        $job->save();
    } catch (Throwable $e) {
        $job->status = 'failed';
        $job->save();
        WifiZoneLogger::logPluginError('cron_backup', $e);
    }
}

echo "WifiZone cron OK " . date('c') . "\n";
