<?php

class WifiZoneBackup
{
    public const TELEGRAM_MAX_MB = 49;
    public const DEFAULT_RETAIN = 7;
    public const FULL_BACKUP_EXTENSION = 'wzb.zip';
    public const FULL_BACKUP_PREFIX = 'full_backup_';

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

    public static function importDatabase($sqlFile): bool
    {
        global $db_host, $db_user, $db_pass, $db_name, $db_port;

        if (!is_file($sqlFile)) {
            throw new RuntimeException('Backup SQL file not found');
        }

        $portArg = '';
        if (!empty($db_port) && (string) $db_port !== '3306') {
            $portArg = ' -P' . escapeshellarg((string) $db_port);
        }

        $passwordArg = $db_pass !== '' ? '-p' . escapeshellarg($db_pass) : '';
        $cmd = sprintf(
            'mysql -h%s%s -u%s %s %s < %s 2>&1',
            escapeshellarg((string) $db_host),
            $portArg,
            escapeshellarg((string) $db_user),
            $passwordArg,
            escapeshellarg((string) $db_name),
            escapeshellarg($sqlFile)
        );

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        if ($code !== 0) {
            throw new RuntimeException('Database import failed: ' . implode("\n", $output));
        }

        return true;
    }

    public static function createFullBackup($label = 'manual'): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required');
        }

        $backupDir = self::backupDirectory();
        $slug = preg_replace('/[^a-z0-9_-]+/i', '_', trim((string) $label));
        if ($slug === '') {
            $slug = 'manual';
        }

        $baseName = self::FULL_BACKUP_PREFIX . $slug . '_' . date('Y-m-d_H-i-s') . '.' . self::FULL_BACKUP_EXTENSION;
        $packagePath = $backupDir . DIRECTORY_SEPARATOR . $baseName;
        $tempDir = $backupDir . DIRECTORY_SEPARATOR . 'tmp_' . uniqid('full_', true);
        $rootExportDir = $tempDir . DIRECTORY_SEPARATOR . 'root';
        $uploadsExportDir = $tempDir . DIRECTORY_SEPARATOR . 'uploads';
        $metaDir = $tempDir . DIRECTORY_SEPARATOR . 'meta';

        if (!mkdir($rootExportDir, 0755, true) && !is_dir($rootExportDir)) {
            throw new RuntimeException('Unable to create backup staging directory');
        }
        mkdir($uploadsExportDir, 0755, true);
        mkdir($metaDir, 0755, true);

        try {
            $sqlFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (!self::dumpDatabase($sqlFile)) {
                throw new RuntimeException('Database dump failed');
            }

            foreach (self::rootFilesToBackup() as $file) {
                $source = self::rootPath() . DIRECTORY_SEPARATOR . $file;
                if (!is_file($source)) {
                    continue;
                }
                $target = $rootExportDir . DIRECTORY_SEPARATOR . $file;
                $parent = dirname($target);
                if (!is_dir($parent)) {
                    mkdir($parent, 0755, true);
                }
                copy($source, $target);
            }

            if (is_dir(self::uploadsPath())) {
                self::copyDirectory(self::uploadsPath(), $uploadsExportDir, ['backup']);
            }

            $manifest = [
                'format' => 'wifizone-full-backup-v1',
                'created_at' => date('c'),
                'label' => $slug,
                'php_version' => PHP_VERSION,
                'db_name' => self::dbName(),
                'includes' => [
                    'database_sql' => 'database.sql',
                    'root_files' => array_values(array_filter(self::rootFilesToBackup(), static function ($file) use ($rootExportDir) {
                        return is_file($rootExportDir . DIRECTORY_SEPARATOR . $file);
                    })),
                    'uploads' => true,
                ],
            ];
            file_put_contents(
                $metaDir . DIRECTORY_SEPARATOR . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            self::createZipFromDirectory($tempDir, $packagePath);
            self::pruneFullBackups(self::resolveRetainCount());

            return $packagePath;
        } finally {
            self::deleteDirectory($tempDir);
        }
    }

    public static function restoreFullBackup($packagePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required');
        }
        if (!is_file($packagePath)) {
            throw new RuntimeException('Backup package not found');
        }

        $backupDir = self::backupDirectory();
        $extractDir = $backupDir . DIRECTORY_SEPARATOR . 'restore_' . uniqid('', true);
        if (!mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
            throw new RuntimeException('Unable to create restore directory');
        }

        $rescuePath = '';
        try {
            self::extractZipToDirectory($packagePath, $extractDir);
            $manifestPath = $extractDir . DIRECTORY_SEPARATOR . 'meta' . DIRECTORY_SEPARATOR . 'manifest.json';
            $sqlPath = $extractDir . DIRECTORY_SEPARATOR . 'database.sql';
            $uploadsSource = $extractDir . DIRECTORY_SEPARATOR . 'uploads';
            $rootSource = $extractDir . DIRECTORY_SEPARATOR . 'root';

            if (!is_file($manifestPath) || !is_file($sqlPath)) {
                throw new RuntimeException('Invalid full backup package');
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (!is_array($manifest) || ($manifest['format'] ?? '') !== 'wifizone-full-backup-v1') {
                throw new RuntimeException('Unsupported backup package format');
            }

            $rescuePath = self::createFullBackup('pre_restore');
            self::importDatabase($sqlPath);

            if (is_dir($uploadsSource)) {
                self::prepareUploadsForRestore();
                self::copyDirectory($uploadsSource, self::uploadsPath(), ['backup']);
            }

            if (is_dir($rootSource)) {
                foreach (self::rootFilesToBackup() as $file) {
                    $source = $rootSource . DIRECTORY_SEPARATOR . $file;
                    if (!is_file($source)) {
                        continue;
                    }
                    $target = self::rootPath() . DIRECTORY_SEPARATOR . $file;
                    $parent = dirname($target);
                    if (!is_dir($parent)) {
                        mkdir($parent, 0755, true);
                    }
                    copy($source, $target);
                }
            }

            self::clearRuntimeCaches();

            return [
                'ok' => true,
                'rescue_backup' => basename($rescuePath),
            ];
        } finally {
            self::deleteDirectory($extractDir);
        }
    }

    public static function listFullBackups(): array
    {
        $backupDir = self::backupDirectory();
        $files = glob($backupDir . DIRECTORY_SEPARATOR . self::FULL_BACKUP_PREFIX . '*.' . self::FULL_BACKUP_EXTENSION);
        if ($files === false) {
            return [];
        }

        usort($files, static function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files;
    }

    public static function rootFilesToBackup(): array
    {
        return [
            'config.php',
            '.env',
            '.htaccess',
            'system/install/appconfig.production.json',
        ];
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

    public static function pruneFullBackups($retainCount): void
    {
        $files = self::listFullBackups();
        if (count($files) <= $retainCount) {
            return;
        }

        foreach (array_slice($files, $retainCount) as $file) {
            @unlink($file);
        }
    }

    private static function createZipFromDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create backup archive');
        }

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }
            $relative = ltrim(substr($realPath, strlen($sourceDir)), DIRECTORY_SEPARATOR);
            if ($relative === '') {
                continue;
            }
            if ($file->isDir()) {
                $zip->addEmptyDir(str_replace('\\', '/', $relative));
            } else {
                $zip->addFile($realPath, str_replace('\\', '/', $relative));
            }
        }

        $zip->close();
    }

    private static function extractZipToDirectory(string $zipPath, string $targetDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open backup archive');
        }
        if (!$zip->extractTo($targetDir)) {
            $zip->close();
            throw new RuntimeException('Unable to extract backup archive');
        }
        $zip->close();
    }

    private static function copyDirectory(string $source, string $target, array $excludeNames = []): void
    {
        if (!is_dir($source)) {
            return;
        }
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $items = scandir($source);
        if ($items === false) {
            throw new RuntimeException('Unable to scan directory: ' . $source);
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $excludeNames, true)) {
                continue;
            }
            $src = $source . DIRECTORY_SEPARATOR . $item;
            $dst = $target . DIRECTORY_SEPARATOR . $item;
            if (is_dir($src)) {
                self::copyDirectory($src, $dst, []);
            } else {
                $parent = dirname($dst);
                if (!is_dir($parent)) {
                    mkdir($parent, 0755, true);
                }
                copy($src, $dst);
            }
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                self::deleteDirectory($target);
            } else {
                @unlink($target);
            }
        }
        @rmdir($path);
    }

    private static function prepareUploadsForRestore(): void
    {
        $uploadsPath = self::uploadsPath();
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
            return;
        }

        $items = scandir($uploadsPath);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'backup') {
                continue;
            }
            $target = $uploadsPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                self::deleteDirectory($target);
            } else {
                @unlink($target);
            }
        }
    }

    private static function clearRuntimeCaches(): void
    {
        global $CACHE_PATH;

        $paths = [
            self::rootPath() . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'cache',
            self::rootPath() . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'compiled',
            (string) $CACHE_PATH,
        ];

        foreach ($paths as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $items = scandir($dir);
            if ($items === false) {
                continue;
            }
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === 'index.html') {
                    continue;
                }
                $target = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($target)) {
                    self::deleteDirectory($target);
                } else {
                    @unlink($target);
                }
            }
        }
    }

    private static function rootPath(): string
    {
        global $root_path;

        return rtrim((string) $root_path, DIRECTORY_SEPARATOR);
    }

    private static function uploadsPath(): string
    {
        global $UPLOAD_PATH;

        return rtrim((string) $UPLOAD_PATH, DIRECTORY_SEPARATOR);
    }

    private static function dbName(): string
    {
        global $db_name;

        return (string) $db_name;
    }
}
