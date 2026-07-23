<?php

class WifiZoneBackup
{
    public const TELEGRAM_MAX_MB = 49;
    public const DEFAULT_RETAIN = 7;

    public static function createEncryptedBackup($password = null)
    {
        global $root_path, $UPLOAD_PATH;
        $password = $password ?: WifiZoneCore::config('wifizone_backup_password', 'wifizone');
        $sqlFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'backup_' . date('Ymd_His') . '.sql';
        self::dumpDatabase($sqlFile);
        $encrypted = $sqlFile . '.aes';
        self::encryptFile($sqlFile, $encrypted, $password);
        @unlink($sqlFile);
        $job = ORM::for_table('wifizone_backup_jobs')->create();
        $job->job_type = 'full';
        $job->status = 'completed';
        $job->file_path = $encrypted;
        $job->completed_at = date('Y-m-d H:i:s');
        $job->save();
        return $encrypted;
    }

    public static function dumpDatabase($targetFile)
    {
        global $db_host, $db_user, $db_pass, $db_name, $db_port;

        $backupDir = dirname($targetFile);
        if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            return false;
        }

        $portArg = '';
        if (!empty($db_port) && (string) $db_port !== '3306') {
            $portArg = ' -P' . escapeshellarg((string) $db_port);
        }

        $passwordArg = $db_pass !== '' ? '-p' . escapeshellarg($db_pass) : '';
        $cmd = sprintf(
            'mysqldump -h%s%s -u%s %s %s --single-transaction --quick --result-file=%s 2>&1',
            escapeshellarg((string) $db_host),
            $portArg,
            escapeshellarg((string) $db_user),
            $passwordArg,
            escapeshellarg((string) $db_name),
            escapeshellarg($targetFile)
        );

        $output = shell_exec($cmd);
        if (!is_file($targetFile) || filesize($targetFile) < 32) {
            error_log('WifiZoneBackup::dumpDatabase failed: ' . (string) $output);
            @unlink($targetFile);
            return false;
        }

        return true;
    }

    public static function encryptFile($source, $dest, $password)
    {
        $data = file_get_contents($source);
        $iv = random_bytes(16);
        $key = hash('sha256', $password, true);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        file_put_contents($dest, $iv . $encrypted);
    }

    public static function exportConfigOnly()
    {
        $settings = ORM::for_table('tbl_appconfig')->find_array();
        global $UPLOAD_PATH;
        $file = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'config_export_' . date('Ymd') . '.json';
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
        return $file;
    }

    public static function backupDirectory()
    {
        global $UPLOAD_PATH;

        $dir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'backup';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create backup directory');
        }

        return $dir;
    }

    /**
     * Daily mysqldump + Telegram delivery (once per calendar day).
     *
     * @return array<string,mixed>
     */
    public static function runScheduledTelegramBackup($force = false)
    {
        global $UPLOAD_PATH, $config;

        if (!Message::isBackupAutoEnabled() && !$force) {
            return ['skipped' => true, 'reason' => 'disabled'];
        }

        $creds = Message::resolveBackupTelegramCredentials();
        if ($creds['bot'] === '' || $creds['chat'] === '') {
            return ['skipped' => true, 'reason' => 'telegram_not_configured'];
        }

        $stamp = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'backup_telegram_last_run.txt';
        $today = date('Y-m-d');
        if (!$force && is_file($stamp) && trim((string) file_get_contents($stamp)) === $today) {
            return ['skipped' => true, 'reason' => 'already_ran_today'];
        }

        $backupDir = self::backupDirectory();
        $baseName = 'backup_' . date('Y-m-d_H-i-s');
        $sqlFile = $backupDir . DIRECTORY_SEPARATOR . $baseName . '.sql';

        if (!self::dumpDatabase($sqlFile)) {
            Message::sendTelegram(
                "[DYRSIA] Échec sauvegarde automatique\nmysqldump a échoué.\nVérifier mysqldump et accès MySQL.",
                $creds['chat'],
                '',
                $creds['bot']
            );
            return ['success' => false, 'error' => 'dump_failed'];
        }

        $uploadFile = $sqlFile;
        $uploadName = basename($sqlFile);
        $gzFile = $sqlFile . '.gz';

        if (function_exists('gzencode')) {
            $raw = file_get_contents($sqlFile);
            if ($raw !== false) {
                $compressed = gzencode($raw, 9);
                if ($compressed !== false && file_put_contents($gzFile, $compressed) !== false) {
                    if (filesize($gzFile) < filesize($sqlFile)) {
                        $uploadFile = $gzFile;
                        $uploadName = basename($gzFile);
                    } else {
                        @unlink($gzFile);
                    }
                }
            }
        }

        $sizeMb = round(filesize($uploadFile) / 1024 / 1024, 2);
        if ($sizeMb > self::TELEGRAM_MAX_MB) {
            Message::sendTelegram(
                "[DYRSIA] Sauvegarde DB trop volumineuse pour Telegram ({$sizeMb} Mo).\nFichier conservé sur le serveur:\n{$uploadName}",
                $creds['chat'],
                '',
                $creds['bot']
            );
            if ($uploadFile !== $sqlFile && is_file($uploadFile)) {
                @unlink($uploadFile);
            }
            file_put_contents($stamp, $today);
            self::pruneOldBackups($backupDir, self::resolveRetainCount());

            return ['success' => false, 'error' => 'file_too_large', 'size_mb' => $sizeMb, 'file' => $uploadName];
        }

        $host = trim((string) (getenv('APP_URL') ?: ($config['CompanyName'] ?? 'DYRSIA')));
        $caption = "🗄 Sauvegarde DYRSIA\n"
            . '📅 ' . date('Y-m-d H:i:s') . "\n"
            . '🌐 ' . $host . "\n"
            . '📦 ' . $sizeMb . " Mo\n"
            . '🗃 ' . $uploadName;

        $response = Message::sendTelegramDocument($uploadFile, $caption, $creds['chat'], $creds['bot']);
        if ($uploadFile !== $sqlFile && is_file($uploadFile)) {
            @unlink($uploadFile);
        }

        file_put_contents($stamp, $today);
        self::pruneOldBackups($backupDir, self::resolveRetainCount());

        if (!Message::isTelegramSuccess($response)) {
            Message::sendTelegram(
                "[DYRSIA] Sauvegarde créée mais envoi Telegram échoué.\nFichier local: {$uploadName}",
                $creds['chat'],
                '',
                $creds['bot']
            );

            return ['success' => false, 'error' => 'telegram_failed', 'file' => $uploadName, 'size_mb' => $sizeMb];
        }

        return ['success' => true, 'file' => $uploadName, 'size_mb' => $sizeMb];
    }

    public static function resolveRetainCount()
    {
        global $config;

        $count = (int) ($config['backup_retain_count'] ?? 0);
        if ($count < 1) {
            $count = self::DEFAULT_RETAIN;
        }

        return $count;
    }

    public static function pruneOldBackups($backupDir, $retainCount)
    {
        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql*');
        if ($files === false || count($files) <= $retainCount) {
            return;
        }

        usort($files, static function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach (array_slice($files, $retainCount) as $file) {
            @unlink($file);
        }
    }
}
