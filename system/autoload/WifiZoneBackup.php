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
        $backupDir = dirname($targetFile);
        if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            return false;
        }

        @unlink($targetFile);

        $errors = [];
        if (self::dumpDatabaseWithMysqldump($targetFile, $errors)) {
            return true;
        }

        if (self::dumpDatabaseWithPdo($targetFile, $errors)) {
            return true;
        }

        error_log('WifiZoneBackup::dumpDatabase failed: ' . implode(' | ', $errors));
        @unlink($targetFile);
        return false;
    }

    private static function dumpDatabaseWithMysqldump(string $targetFile, array &$errors): bool
    {
        global $db_host, $db_user, $db_pass, $db_name, $db_port;

        if (!function_exists('shell_exec') && !function_exists('exec') && !function_exists('proc_open')) {
            $errors[] = 'shell_exec/exec disabled';
            return false;
        }

        $mysqldump = self::resolveMysqldumpBinary();
        if ($mysqldump === '') {
            $errors[] = 'mysqldump binary not found';
            return false;
        }

        $portArg = '';
        if (!empty($db_port) && (string) $db_port !== '3306') {
            $portArg = ' -P' . escapeshellarg((string) $db_port);
        }

        $passwordArg = $db_pass !== '' ? '-p' . escapeshellarg((string) $db_pass) : '';
        $cmd = sprintf(
            '%s -h%s%s -u%s %s %s --single-transaction --quick --routines --triggers --result-file=%s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg((string) $db_host),
            $portArg,
            escapeshellarg((string) $db_user),
            $passwordArg,
            escapeshellarg((string) $db_name),
            escapeshellarg($targetFile)
        );

        $output = '';
        if (function_exists('shell_exec')) {
            $output = (string) shell_exec($cmd);
        } elseif (function_exists('exec')) {
            $lines = [];
            $code = 1;
            exec($cmd, $lines, $code);
            $output = implode("\n", $lines);
            if ($code !== 0 && $output === '') {
                $output = 'mysqldump exit code ' . $code;
            }
        }

        if (self::isValidDumpFile($targetFile)) {
            return true;
        }

        $errors[] = 'mysqldump failed: ' . trim($output !== '' ? $output : 'empty dump file');
        @unlink($targetFile);
        return false;
    }

    private static function dumpDatabaseWithPdo(string $targetFile, array &$errors): bool
    {
        global $db_host, $db_user, $db_pass, $db_name, $db_port;

        try {
            $host = (string) $db_host;
            $name = (string) $db_name;
            $port = trim((string) ($db_port ?? ''));
            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
            if ($port !== '' && $port !== '3306') {
                $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
            }

            $pdo = new PDO($dsn, (string) $db_user, (string) $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $fh = fopen($targetFile, 'wb');
            if ($fh === false) {
                $errors[] = 'unable to open dump target for writing';
                return false;
            }

            fwrite($fh, "-- DYRSIA PDO backup\n");
            fwrite($fh, '-- Generated: ' . date('c') . "\n");
            fwrite($fh, "SET NAMES utf8mb4;\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);
            foreach ($tables as $row) {
                $table = (string) $row[0];
                $quoted = '`' . str_replace('`', '``', $table) . '`';

                $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
                if (!$create || empty($create[1])) {
                    continue;
                }

                fwrite($fh, "DROP TABLE IF EXISTS {$quoted};\n");
                fwrite($fh, $create[1] . ";\n\n");

                $result = $pdo->query('SELECT * FROM ' . $quoted, PDO::FETCH_ASSOC);
                $buffer = [];
                $bufferBytes = 0;
                while ($data = $result->fetch()) {
                    $values = [];
                    foreach ($data as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_int($value) || is_float($value)) {
                            $values[] = (string) $value;
                        } else {
                            $values[] = $pdo->quote((string) $value);
                        }
                    }
                    $line = '(' . implode(',', $values) . ')';
                    $buffer[] = $line;
                    $bufferBytes += strlen($line);
                    if (count($buffer) >= 100 || $bufferBytes >= 1048576) {
                        fwrite($fh, 'INSERT INTO ' . $quoted . ' VALUES ' . implode(",\n", $buffer) . ";\n");
                        $buffer = [];
                        $bufferBytes = 0;
                    }
                }
                if ($buffer) {
                    fwrite($fh, 'INSERT INTO ' . $quoted . ' VALUES ' . implode(",\n", $buffer) . ";\n");
                }
                fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);

            if (self::isValidDumpFile($targetFile)) {
                return true;
            }

            $errors[] = 'PDO dump produced an empty/invalid file';
            @unlink($targetFile);
            return false;
        } catch (Throwable $e) {
            $errors[] = 'PDO dump failed: ' . $e->getMessage();
            @unlink($targetFile);
            return false;
        }
    }

    private static function resolveMysqldumpBinary(): string
    {
        $candidates = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/www/server/mysql/bin/mysqldump',
            '/www/server/mysql-8.0/bin/mysqldump',
            '/www/server/mysql-5.7/bin/mysqldump',
            '/opt/alt/mariadb/bin/mysqldump',
            '/usr/mariadb/bin/mysqldump',
        ];

        $which = '';
        if (function_exists('shell_exec')) {
            $which = trim((string) @shell_exec('command -v mysqldump 2>/dev/null'));
        }
        if ($which !== '') {
            array_unshift($candidates, $which);
        }

        foreach ($candidates as $binary) {
            if ($binary === 'mysqldump') {
                continue;
            }
            if (is_file($binary) && is_executable($binary)) {
                return $binary;
            }
        }

        // Keep bare command as last resort when PATH is available.
        if (function_exists('shell_exec')) {
            $probe = trim((string) @shell_exec('mysqldump --version 2>/dev/null'));
            if ($probe !== '') {
                return 'mysqldump';
            }
        }

        return '';
    }

    private static function isValidDumpFile(string $targetFile): bool
    {
        return is_file($targetFile) && filesize($targetFile) >= 32;
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
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        @ignore_user_abort(true);

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
            throw new RuntimeException('Impossible de créer le dossier temporaire de sauvegarde');
        }
        mkdir($uploadsExportDir, 0755, true);
        mkdir($metaDir, 0755, true);

        try {
            $sqlFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (!self::dumpDatabase($sqlFile)) {
                throw new RuntimeException(
                    'Échec du dump MySQL (mysqldump introuvable ou accès refusé). Vérifiez mysqldump et les identifiants DB.'
                );
            }

            $rootFilesIncluded = [];
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
                if (!@copy($source, $target)) {
                    throw new RuntimeException('Impossible de copier le fichier: ' . $file);
                }
                $rootFilesIncluded[] = $file;
            }

            if (is_dir(self::uploadsPath())) {
                self::copyDirectory(
                    self::uploadsPath(),
                    $uploadsExportDir,
                    ['backup', '_sysfrm_tmp_', 'cache', 'compiled'],
                    true
                );
            }

            $manifest = [
                'format' => 'wifizone-full-backup-v1',
                'created_at' => date('c'),
                'label' => $slug,
                'php_version' => PHP_VERSION,
                'db_name' => self::dbName(),
                'includes' => [
                    'database_sql' => 'database.sql',
                    'root_files' => $rootFilesIncluded,
                    'uploads' => true,
                ],
            ];
            file_put_contents(
                $metaDir . DIRECTORY_SEPARATOR . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            self::packDirectoryToZip($tempDir, $packagePath);

            if (!is_file($packagePath) || filesize($packagePath) < 64) {
                throw new RuntimeException('Archive ZIP vide ou invalide après création');
            }

            self::pruneFullBackups(self::resolveRetainCount());

            return $packagePath;
        } catch (Throwable $e) {
            @unlink($packagePath);
            throw $e;
        } finally {
            self::deleteDirectory($tempDir);
        }
    }

    public static function queueFullBackupJob(string $label = 'manual'): array
    {
        $jobId = 'full_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $job = [
            'id' => $jobId,
            'label' => $label,
            'status' => 'queued',
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'file' => null,
            'size' => null,
            'error' => null,
        ];
        self::writeFullBackupJob($jobId, $job);

        return $job;
    }

    public static function fullBackupJobPath(string $jobId): string
    {
        $jobId = basename($jobId);
        return self::backupDirectory() . DIRECTORY_SEPARATOR . 'job_' . $jobId . '.json';
    }

    public static function readFullBackupJob(string $jobId): ?array
    {
        $path = self::fullBackupJobPath($jobId);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    public static function writeFullBackupJob(string $jobId, array $job): void
    {
        $job['updated_at'] = date('c');
        $path = self::fullBackupJobPath($jobId);
        file_put_contents($path, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * @return array<string,mixed>
     */
    public static function runFullBackupJob(string $jobId): array
    {
        $job = self::readFullBackupJob($jobId);
        if ($job === null) {
            throw new RuntimeException('Job sauvegarde introuvable: ' . $jobId);
        }
        if (($job['status'] ?? '') === 'completed' && !empty($job['file'])) {
            return ['ok' => true, 'job' => $job];
        }

        $job['status'] = 'running';
        $job['error'] = null;
        self::writeFullBackupJob($jobId, $job);

        try {
            $path = self::createFullBackup((string) ($job['label'] ?? 'manual'));
            $telegram = self::sendBackupFileToTelegram($path, 'Sauvegarde complète');
            $job['status'] = 'completed';
            $job['file'] = basename($path);
            $job['size'] = filesize($path);
            $job['error'] = null;
            $job['telegram'] = $telegram;
            self::writeFullBackupJob($jobId, $job);
            return ['ok' => true, 'job' => $job, 'path' => $path, 'telegram' => $telegram];
        } catch (Throwable $e) {
            $job['status'] = 'failed';
            $job['error'] = $e->getMessage();
            self::writeFullBackupJob($jobId, $job);
            return ['ok' => false, 'job' => $job, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a backup file to Telegram when upload is enabled / auto backup is on.
     *
     * @return array{ok:bool,skipped?:bool,reason?:string,error?:string,size_mb?:float}
     */
    public static function sendBackupFileToTelegram(string $filePath, string $label = 'Backup'): array
    {
        global $config;

        if (!is_file($filePath)) {
            return ['ok' => false, 'error' => 'file_not_found'];
        }

        if (!class_exists('Message')) {
            return ['ok' => false, 'error' => 'message_class_missing'];
        }

        if (empty($config['backup_telegram_upload']) && !Message::isBackupAutoEnabled()) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'telegram_upload_disabled'];
        }

        $creds = Message::resolveBackupTelegramCredentials();
        if ($creds['bot'] === '' || $creds['chat'] === '') {
            return ['ok' => false, 'skipped' => true, 'reason' => 'telegram_not_configured'];
        }

        $fileName = basename($filePath);
        $sizeMb = round(filesize($filePath) / 1024 / 1024, 2);
        if ($sizeMb > self::TELEGRAM_MAX_MB) {
            Message::sendTelegram(
                "[DYRSIA] {$label} trop volumineux pour Telegram ({$sizeMb} Mo).\nFichier conservé sur le serveur:\n{$fileName}",
                $creds['chat'],
                '',
                $creds['bot']
            );
            return ['ok' => false, 'skipped' => true, 'reason' => 'file_too_large', 'size_mb' => $sizeMb];
        }

        $host = trim((string) (getenv('APP_URL') ?: ($config['CompanyName'] ?? 'DYRSIA')));
        $caption = "📦 {$label} DYRSIA\n"
            . '📅 ' . date('Y-m-d H:i:s') . "\n"
            . '🌐 ' . $host . "\n"
            . '📦 ' . $sizeMb . " Mo\n"
            . '🗃 ' . $fileName;

        $response = Message::sendTelegramDocument($filePath, $caption, $creds['chat'], $creds['bot']);
        if (!Message::isTelegramSuccess($response)) {
            Message::sendTelegram(
                "[DYRSIA] {$label} créé mais envoi Telegram échoué.\nFichier local: {$fileName}",
                $creds['chat'],
                '',
                $creds['bot']
            );
            return ['ok' => false, 'error' => 'telegram_send_failed', 'size_mb' => $sizeMb];
        }

        return ['ok' => true, 'size_mb' => $sizeMb];
    }

    public static function spawnFullBackupJob(string $jobId): bool
    {
        $jobPath = self::fullBackupJobPath($jobId);
        if (!is_file($jobPath)) {
            return false;
        }

        $root = self::rootPath();
        $php = class_exists('DeployAsyncHttp')
            ? DeployAsyncHttp::resolvePhpCliBinary()
            : 'php';
        $script = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run-full-backup.php';
        if (!is_file($script)) {
            return false;
        }

        $logFile = $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'full_backup_worker.log';
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0755, true);
        }

        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobId)
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return pclose(popen('start /B ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobId), 'r')) !== false;
        }

        if (function_exists('exec')) {
            @exec($cmd, $out, $code);
            return $code === 0;
        }
        if (function_exists('shell_exec')) {
            @shell_exec($cmd);
            return true;
        }

        return false;
    }

    public static function restoreFullBackup($packagePath): array
    {
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

    private static function packDirectoryToZip(string $sourceDir, string $zipPath): void
    {
        @unlink($zipPath);
        $errors = [];

        if (class_exists('ZipArchive')) {
            try {
                self::createZipFromDirectoryWithZipArchive($sourceDir, $zipPath);
                if (is_file($zipPath) && filesize($zipPath) >= 64) {
                    return;
                }
                $errors[] = 'ZipArchive a produit une archive vide';
            } catch (Throwable $e) {
                $errors[] = 'ZipArchive: ' . $e->getMessage();
                @unlink($zipPath);
            }
        } else {
            $errors[] = 'extension php-zip absente';
        }

        if (self::createZipFromDirectoryWithShell($sourceDir, $zipPath)) {
            return;
        }
        $errors[] = 'commande zip système indisponible/échec';

        try {
            self::createZipFromDirectoryStorePhp($sourceDir, $zipPath);
            if (is_file($zipPath) && filesize($zipPath) >= 64) {
                return;
            }
            $errors[] = 'ZIP PHP pur a produit une archive vide';
        } catch (Throwable $e) {
            $errors[] = 'ZIP PHP pur: ' . $e->getMessage();
            @unlink($zipPath);
        }

        throw new RuntimeException(
            'Impossible de créer l\'archive ZIP. '
            . implode(' | ', $errors)
            . '. Astuce serveur: activez l\'extension php-zip (ou installez la commande zip).'
        );
    }

    private static function createZipFromDirectoryWithZipArchive(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible d\'ouvrir l\'archive ZipArchive');
        }

        $added = self::addDirectoryToZipArchive($zip, $sourceDir, '', []);
        if ($added < 1) {
            $zip->close();
            @unlink($zipPath);
            throw new RuntimeException('Aucun fichier à empaqueter dans la sauvegarde complète');
        }

        if ($zip->close() !== true) {
            @unlink($zipPath);
            throw new RuntimeException('Échec finalisation archive ZIP');
        }
    }

    private static function createZipFromDirectoryWithShell(string $sourceDir, string $zipPath): bool
    {
        if (!function_exists('exec') && !function_exists('shell_exec') && !function_exists('proc_open')) {
            return false;
        }

        $zipBin = self::resolveZipBinary();
        if ($zipBin === '') {
            return false;
        }

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $zipPathAbs = $zipPath;
        // zip adds .zip if missing; our file already ends with .wzb.zip
        $cmd = 'cd ' . escapeshellarg($sourceDir)
            . ' && ' . escapeshellarg($zipBin)
            . ' -rq ' . escapeshellarg($zipPathAbs)
            . ' . 2>&1';

        $output = '';
        $code = 1;
        if (function_exists('exec')) {
            $lines = [];
            exec($cmd, $lines, $code);
            $output = implode("\n", $lines);
        } elseif (function_exists('shell_exec')) {
            $output = (string) shell_exec($cmd . '; echo __EXIT:$?');
            if (preg_match('/__EXIT:(\d+)/', $output, $m)) {
                $code = (int) $m[1];
            } else {
                $code = is_file($zipPathAbs) ? 0 : 1;
            }
        }

        if ($code === 0 && is_file($zipPathAbs) && filesize($zipPathAbs) >= 64) {
            return true;
        }

        error_log('WifiZoneBackup shell zip failed: ' . trim($output));
        @unlink($zipPathAbs);
        return false;
    }

    private static function resolveZipBinary(): string
    {
        $candidates = [
            '/usr/bin/zip',
            '/bin/zip',
            '/usr/local/bin/zip',
            'zip',
        ];
        if (function_exists('shell_exec')) {
            $which = trim((string) @shell_exec('command -v zip 2>/dev/null'));
            if ($which !== '') {
                array_unshift($candidates, $which);
            }
        }
        foreach ($candidates as $bin) {
            if ($bin === 'zip') {
                continue;
            }
            if (is_file($bin) && is_executable($bin)) {
                return $bin;
            }
        }
        if (function_exists('shell_exec')) {
            $probe = trim((string) @shell_exec('zip -v 2>/dev/null | head -n 1'));
            if ($probe !== '') {
                return 'zip';
            }
        }
        return '';
    }

    private static function resolveUnzipBinary(): string
    {
        $candidates = [
            '/usr/bin/unzip',
            '/bin/unzip',
            '/usr/local/bin/unzip',
            'unzip',
        ];
        if (function_exists('shell_exec')) {
            $which = trim((string) @shell_exec('command -v unzip 2>/dev/null'));
            if ($which !== '') {
                array_unshift($candidates, $which);
            }
        }
        foreach ($candidates as $bin) {
            if ($bin === 'unzip') {
                continue;
            }
            if (is_file($bin) && is_executable($bin)) {
                return $bin;
            }
        }
        if (function_exists('shell_exec')) {
            $probe = trim((string) @shell_exec('unzip -v 2>/dev/null | head -n 1'));
            if ($probe !== '') {
                return 'unzip';
            }
        }
        return '';
    }

    /**
     * Pure-PHP ZIP writer (STORE, no compression) — works without php-zip / zip binary.
     */
    private static function createZipFromDirectoryStorePhp(string $sourceDir, string $zipPath): void
    {
        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $entries = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }
            $relative = ltrim(substr($realPath, strlen($sourceDir)), DIRECTORY_SEPARATOR);
            if ($relative === '') {
                continue;
            }
            $entries[] = [
                'name' => str_replace('\\', '/', $relative),
                'path' => $realPath,
            ];
        }
        if (!$entries) {
            throw new RuntimeException('Aucun fichier à empaqueter');
        }

        $out = fopen($zipPath, 'wb');
        if ($out === false) {
            throw new RuntimeException('Impossible d\'écrire l\'archive ZIP');
        }

        $central = '';
        $offset = 0;
        $count = 0;
        foreach ($entries as $entry) {
            $name = $entry['name'];
            $data = file_get_contents($entry['path']);
            if ($data === false) {
                fclose($out);
                @unlink($zipPath);
                throw new RuntimeException('Lecture impossible: ' . $name);
            }
            $size = strlen($data);
            $crc = hexdec(hash('crc32b', $data));
            $nameLen = strlen($name);

            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLen, 0);
            fwrite($out, $local);
            fwrite($out, $name);
            fwrite($out, $data);

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLen,
                0,
                0,
                0,
                0,
                0,
                $offset
            );
            $central .= $name;

            $offset += 30 + $nameLen + $size;
            $count++;
        }

        $centralOffset = $offset;
        $centralSize = strlen($central);
        fwrite($out, $central);
        fwrite($out, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0));
        fclose($out);
    }

    private static function addDirectoryToZipArchive(ZipArchive $zip, string $sourceDir, string $zipPrefix, array $excludeNames): int
    {
        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        if (!is_dir($sourceDir)) {
            return 0;
        }

        $added = 0;
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

            $parts = explode(DIRECTORY_SEPARATOR, $relative);
            if ($parts && in_array($parts[0], $excludeNames, true)) {
                continue;
            }

            $base = basename($relative);
            if (preg_match('/\.(log|tmp)$/i', $base) || preg_match('/^backup_.*\.sql(\.aes)?$/i', $base)) {
                continue;
            }

            $zipName = $zipPrefix !== ''
                ? rtrim(str_replace('\\', '/', $zipPrefix), '/') . '/' . str_replace('\\', '/', $relative)
                : str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                $zip->addEmptyDir($zipName);
                continue;
            }

            if (!$zip->addFile($realPath, $zipName)) {
                $contents = @file_get_contents($realPath);
                if ($contents === false || !$zip->addFromString($zipName, $contents)) {
                    throw new RuntimeException('Impossible d\'ajouter au ZIP: ' . $zipName);
                }
            }
            $added++;
        }

        return $added;
    }

    private static function extractZipToDirectory(string $zipPath, string $targetDir): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $ok = $zip->extractTo($targetDir);
                $zip->close();
                if ($ok) {
                    return;
                }
            }
        }

        $unzip = self::resolveUnzipBinary();
        if ($unzip !== '' && (function_exists('exec') || function_exists('shell_exec'))) {
            $cmd = escapeshellarg($unzip) . ' -oqq ' . escapeshellarg($zipPath)
                . ' -d ' . escapeshellarg($targetDir) . ' 2>&1';
            $output = '';
            $code = 1;
            if (function_exists('exec')) {
                $lines = [];
                exec($cmd, $lines, $code);
                $output = implode("\n", $lines);
            } else {
                $output = (string) shell_exec($cmd . '; echo __EXIT:$?');
                if (preg_match('/__EXIT:(\d+)/', $output, $m)) {
                    $code = (int) $m[1];
                }
            }
            if ($code === 0 && is_file($targetDir . DIRECTORY_SEPARATOR . 'database.sql')) {
                return;
            }
            error_log('WifiZoneBackup unzip failed: ' . trim($output));
        }

        throw new RuntimeException(
            'Impossible d\'extraire l\'archive ZIP. Activez php-zip ou installez unzip sur le serveur.'
        );
    }

    private static function copyDirectory(string $source, string $target, array $excludeNames = [], bool $skipRuntimeNoise = false): void
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
            if ($skipRuntimeNoise && (preg_match('/\.(log|tmp)$/i', $item) || preg_match('/^backup_.*\.sql(\.aes)?$/i', $item))) {
                continue;
            }
            $src = $source . DIRECTORY_SEPARATOR . $item;
            $dst = $target . DIRECTORY_SEPARATOR . $item;
            if (is_dir($src)) {
                self::copyDirectory($src, $dst, [], $skipRuntimeNoise);
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
