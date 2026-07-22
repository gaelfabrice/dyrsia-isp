<?php

/**
 * Orchestration ops DYRSIA : cron, alertes Telegram, widgets dashboard.
 */
class WifiZoneOps
{
    public const CRON_HEARTBEAT_SETTING = 'wifizone_cron_last_run';

    /**
     * Timestamp Unix du dernier cron OK (DB partagée, repli fichier local).
     */
    public static function getCronLastRunTimestamp(): int
    {
        global $UPLOAD_PATH, $config;

        $fromConfig = trim((string) ($config[self::CRON_HEARTBEAT_SETTING] ?? ''));
        if ($fromConfig !== '') {
            if (is_numeric($fromConfig)) {
                return (int) $fromConfig;
            }
            $parsed = strtotime($fromConfig);
            if ($parsed !== false) {
                return (int) $parsed;
            }
        }

        try {
            $row = ORM::for_table('tbl_appconfig')->where('setting', self::CRON_HEARTBEAT_SETTING)->find_one();
            if ($row) {
                $raw = trim((string) ($row->value ?? ''));
                if ($raw !== '') {
                    if (is_numeric($raw)) {
                        return (int) $raw;
                    }
                    $parsed = strtotime($raw);
                    if ($parsed !== false) {
                        return (int) $parsed;
                    }
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        $cronFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
        if (!is_file($cronFile)) {
            return 0;
        }

        $raw = trim((string) @file_get_contents($cronFile));
        if ($raw === '') {
            return (int) @filemtime($cronFile);
        }
        if (is_numeric($raw)) {
            return (int) $raw;
        }
        $parsed = strtotime($raw);

        return $parsed !== false ? (int) $parsed : (int) @filemtime($cronFile);
    }

    public static function isCronHeartbeatFresh(int $maxAgeSeconds = 3600): bool
    {
        $last = self::getCronLastRunTimestamp();

        return $last > 0 && (time() - $last) <= max(60, $maxAgeSeconds);
    }

    /**
     * Heartbeat cron : MySQL (Render / multi-conteneurs) + fichier (VPS legacy).
     */
    public static function recordCronHeartbeat(): void
    {
        global $UPLOAD_PATH;

        $now = time();
        @file_put_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt', (string) $now);
        try {
            WifiZoneSecurity::persistConfigValue(self::CRON_HEARTBEAT_SETTING, (string) $now);
            global $config;
            if (is_array($config)) {
                $config[self::CRON_HEARTBEAT_SETTING] = (string) $now;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    public static function runMainCron()
    {
        global $UPLOAD_PATH, $config;

        echo "=== WifiZone main cron " . date('c') . " ===\n";

        $purged = WifiZoneCore::purgeMacOsMetadata();
        if ($purged > 0) {
            echo "Removed {$purged} macOS metadata file(s).\n";
        }

        self::recordCronHeartbeat();

        WifiZonePayment::processPendingQueue(30);
        self::alertFailedPayments();

        Withdrawal::ensureSchema();
        Withdrawal::expireStaleRequests(false);

        WifiZoneNotify::processRenewalReminders();
        WifiZoneNotify::checkGenieacsOffline();

        Package::processExpiredRecharges(['silent' => true, 'min_interval' => 300, 'reinforce_routers' => true]);

        if (!empty($config['router_check']) && (string) $config['router_check'] === '1') {
            $monitor = RouterMonitor::maybeRunDailyCheck(false);
            echo 'Router monitor: ' . json_encode($monitor) . "\n";
        }

        self::runGenieacsSyncIfDue();
        self::runCustomerMonitorIfDue();
        self::runDailyLegacyCronIfDue();
        self::processBackupJobs();
        self::alertCronHealth();

        echo "WifiZone cron OK " . date('c') . "\n";
    }

    public static function runGenieacsSyncIfDue()
    {
        global $UPLOAD_PATH;

        try {
            $count = ORM::for_table('tbl_acs_servers')->count();
            if ($count < 1) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        $stamp = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_acs_last_run.txt';
        if (is_file($stamp) && (time() - filemtime($stamp)) < 3600) {
            return;
        }

        $php = PHP_BINARY ?: 'php';
        $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cron_acs_sync.php';
        if (!is_file($script)) {
            return;
        }

        echo "GenieACS sync…\n";
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1';
        exec($cmd, $output, $code);
        if ($code === 0) {
            touch($stamp);
        }
        echo implode("\n", array_slice($output, -5)) . "\n";
    }

    public static function runCustomerMonitorIfDue()
    {
        global $config, $UPLOAD_PATH;

        if (empty($config['check_customer_online']) || $config['check_customer_online'] !== 'yes') {
            return;
        }

        $stamp = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_customer_monitor_last_run.txt';
        if (is_file($stamp) && (time() - filemtime($stamp)) < 55) {
            return;
        }

        $php = PHP_BINARY ?: 'php';
        $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cron_customer_monitor.php';
        if (!is_file($script)) {
            return;
        }

        echo "Customer monitor…\n";
        exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $code);
        if ($code === 0) {
            touch($stamp);
        }
    }

    public static function runDailyLegacyCronIfDue()
    {
        global $UPLOAD_PATH;

        $stamp = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_legacy_daily.txt';
        if (is_file($stamp) && date('Y-m-d', filemtime($stamp)) === date('Y-m-d')) {
            return;
        }

        $php = PHP_BINARY ?: 'php';
        $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cron.php';
        if (!is_file($script)) {
            return;
        }

        echo "Daily legacy cron (data usage)…\n";
        exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $code);
        touch($stamp);
    }

    public static function processBackupJobs()
    {
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
    }

    public static function alertFailedPayments()
    {
        global $config;

        if (empty($config['telegram_bot']) || empty($config['telegram_target_id'])) {
            return;
        }

        try {
            $rows = ORM::for_table('wifizone_payment_queue')
                ->where('status', 'failed')
                ->where_gte('updated_at', date('Y-m-d H:i:s', strtotime('-1 hour')))
                ->limit(10)
                ->find_many();
            foreach ($rows as $row) {
                $key = 'pay_fail_alert_' . $row->id;
                if (self::wasOpsAlertSent($key)) {
                    continue;
                }
                Message::sendTelegram(
                    "[DYRSIA] Paiement échoué\nGateway: {$row->gateway}\nRef: {$row->reference}\nMontant: {$row->amount}"
                );
                self::markOpsAlertSent($key);
            }
        } catch (Throwable $e) {
        }
    }

    public static function alertCronHealth()
    {
        global $config, $UPLOAD_PATH;

        if (empty($config['telegram_bot']) || empty($config['telegram_target_id'])) {
            return;
        }

        $last = self::getCronLastRunTimestamp();
        if ($last <= 0) {
            return;
        }
        $age = time() - $last;
        if ($age <= 900) {
            return;
        }

        if (self::wasOpsAlertSent('cron_stale_' . date('Y-m-d-H'))) {
            return;
        }

        Message::sendTelegram(
            '[DYRSIA] Cron inactif depuis ' . round($age / 60) . " min.\nVérifier cron_wifizone.php sur le serveur."
        );
        self::markOpsAlertSent('cron_stale_' . date('Y-m-d-H'));
    }

    private static function wasOpsAlertSent($key)
    {
        global $UPLOAD_PATH;
        $dir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'ops_alerts';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return is_file($dir . DIRECTORY_SEPARATOR . md5($key) . '.sent');
    }

    private static function markOpsAlertSent($key)
    {
        global $UPLOAD_PATH;
        $dir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'ops_alerts';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . md5($key) . '.sent', date('c'));
    }

    public static function ensureKpiWidget()
    {
        try {
            $exists = ORM::for_table('tbl_widgets')
                ->where('widget', 'wifizone_kpi')
                ->where('user', 'Admin')
                ->find_one();
            if ($exists) {
                return;
            }
            $d = ORM::for_table('tbl_widgets')->create();
            $d->orders = 1;
            $d->position = 1;
            $d->user = 'Admin';
            $d->enabled = 1;
            $d->title = 'KPI DYRSIA';
            $d->widget = 'wifizone_kpi';
            $d->content = '';
            $d->save();
        } catch (Throwable $e) {
            error_log('ensureKpiWidget: ' . $e->getMessage());
        }
    }

    public static function ensureSetupWidget()
    {
        try {
            $exists = ORM::for_table('tbl_widgets')
                ->where('widget', 'wifizone_setup')
                ->where('user', 'Admin')
                ->find_one();
            if ($exists) {
                return;
            }
            $d = ORM::for_table('tbl_widgets')->create();
            $d->orders = 0;
            $d->position = 1;
            $d->user = 'Admin';
            $d->enabled = 1;
            $d->title = 'Setup wizard';
            $d->widget = 'wifizone_setup';
            $d->content = '';
            $d->save();
        } catch (Throwable $e) {
            error_log('ensureSetupWidget: ' . $e->getMessage());
        }
    }
}
