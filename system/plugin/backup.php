<?php
register_menu("Backup/Restore", true, "backup_list", 'SETTINGS', '');
register_hook('cronjob', 'backup_cron');

function backup_dir(): string
{
    global $UPLOAD_PATH;
    $dir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'backup';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
    }

    return $dir;
}

function backup_stage_locked(): bool
{
    global $_app_stage;

    return strtolower((string) $_app_stage) === 'demo';
}

function backup_resolve_sql_file(string $fileName): ?string
{
    $fileName = basename(trim($fileName));
    if ($fileName === '' || !preg_match('/\.sql$/i', $fileName)) {
        return null;
    }

    $backupDir = backup_dir();
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($filePath)) {
        return null;
    }

    $realFile = realpath($filePath);
    $realDir = realpath($backupDir);
    if ($realFile === false || $realDir === false) {
        return $filePath;
    }

    $prefix = $realDir . DIRECTORY_SEPARATOR;
    if (!str_starts_with($realFile, $prefix) && $realFile !== $realDir) {
        return null;
    }

    return $realFile;
}

function backup_resolve_full_file(string $fileName): ?string
{
    $fileName = basename(trim($fileName));
    if ($fileName === '' || !preg_match('/\.wzb\.zip$/i', $fileName)) {
        return null;
    }

    $backupDir = backup_dir();
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($filePath)) {
        return null;
    }

    $realFile = realpath($filePath);
    $realDir = realpath($backupDir);
    if ($realFile === false || $realDir === false) {
        return $filePath;
    }

    $prefix = $realDir . DIRECTORY_SEPARATOR;
    if (!str_starts_with($realFile, $prefix) && $realFile !== $realDir) {
        return null;
    }

    return $realFile;
}

function backup_list(): void
{
    global $ui;
    _admin();
    $ui->assign('_title', 'Backup/Restore');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    $backupDir = backup_dir();
    $backupFiles = scandir($backupDir);
    $backupFiles = array_diff($backupFiles, ['..', '.', '']);

    $backupFiles = array_filter($backupFiles, static function ($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });

    usort($backupFiles, static function ($a, $b) use ($backupDir) {
        return filemtime("$backupDir/$b") - filemtime("$backupDir/$a");
    });

    // Calculate the size and creation date of each backup file
    $backupFilesWithInfo = [];
    foreach ($backupFiles as $file) {
        $filePath = "$backupDir/$file";
        $size = backup_getFileSize($filePath);
        $creationDate = date('Y-m-d H:i:s', filemtime($filePath));
        $backupFilesWithInfo[] = [
            'file' => $file,
            'size' => $size,
            'creation_date' => $creationDate
        ];
    }

    $fullBackupFilesWithInfo = [];
    if (class_exists('WifiZoneBackup')) {
        foreach (WifiZoneBackup::listFullBackups() as $filePath) {
            $file = basename($filePath);
            $fullBackupFilesWithInfo[] = [
                'file' => $file,
                'size' => backup_getFileSize($filePath),
                'creation_date' => date('Y-m-d H:i:s', filemtime($filePath)),
            ];
        }
    }

    $ui->assign('csrf_token', Csrf::getToken());
    $ui->assign('backupFiles', $backupFilesWithInfo);
    $ui->assign('fullBackupFiles', $fullBackupFilesWithInfo);
    $ui->display('backup.tpl');
}

function backup_getFileSize($filePath): string
{
    $size = filesize($filePath);

    if ($size === false) {
        return 'Unable to determine file size.';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;

    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }

    return round($size, 2) . ' ' . $units[$index];
}

function backup_add($is_CLi = false)
{
    global $UPLOAD_PATH, $root_path, $db_user, $db_pass, $db_host, $db_name, $_app_stage, $config;
    include "{$root_path}config.php";
    $backupDir = backup_dir();
    if (!$is_CLi) {
        _admin();
        $admin = Admin::_info();
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (backup_stage_locked()) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Backup is disabled in Demo mode'));
        }

        if (isset($_POST['createBackup'])) {
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

            $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} --result-file={$backupFile} 2>&1";
            $output = shell_exec($command);
            if (file_exists($backupFile)) {
                // Cloud upload
                if (isset($config['cloud_upload']) && $config['cloud_upload']) {
                    try {
                        backup_uploadToCloud($backupFile);
                    } catch (Throwable $e) {
                        _log(Lang::T('Cloud backup upload failed') . ': ' . $e->getMessage());
                        r2(
                            U . 'plugin/backup_list',
                            'w',
                            Lang::T('Database backup created successfully.')
                                . ' '
                                . Lang::T('Cloud backup upload failed')
                                . ': '
                                . $e->getMessage()
                        );
                    }
                } elseif (!empty($config['backup_telegram_upload'])) {
                    backup_sendToTelegram($backupFile);
                }
                r2(U . 'plugin/backup_list', 's', Lang::T("Database backup created successfully."));
            } else {
                // Log the error
                _log(Lang::T("Error creating backup: ") . $output);
                sendTelegram(Lang::T("Error creating backup: ") . $output);
                r2(U . 'plugin/backup_list', 'e', Lang::T("Error creating database backup. Check the log for details."));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T("Invalid request method."));
        }
    } else {
        // CLI mode
        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} --result-file={$backupFile} 2>&1";
        $output = shell_exec($command);
        if (file_exists($backupFile)) {
            return true;
        }

        // Log the error
        _log(Lang::T("Error creating backup: ") . $output);
        sendTelegram(Lang::T("Error creating backup: ") . $output);
        echo "Error creating database backup. Check the log for details.\n\n";
        return false;
    }
}

function backup_create_full(): void
{
    global $_app_stage;
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Backup is disabled in Demo mode'));
    }

    $csrfToken = _post('csrf_token');
    if (!Csrf::check($csrfToken)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }

    try {
        $filePath = WifiZoneBackup::createFullBackup('manual');
        _log('[' . $admin['username'] . ']: Full backup created ' . basename($filePath), $admin['user_type']);
        r2(U . 'plugin/backup_list', 's', Lang::T('Full backup created successfully') . ': ' . basename($filePath));
    } catch (Throwable $e) {
        _log('backup_create_full: ' . $e->getMessage());
        r2(U . 'plugin/backup_list', 'e', Lang::T('Error creating full backup') . ': ' . $e->getMessage());
    }
}

function backup_download_full(): void
{
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot download backup in Demo mode'));
    }

    $csrfToken = $_GET['token'] ?? '';
    if (!Csrf::check($csrfToken)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }

    $fileName = basename((string) ($_GET['file'] ?? ''));
    $filePath = backup_resolve_full_file($fileName);
    if ($filePath === null) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('The file does not exist.'));
    }

    header('Cache-Control: public');
    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename=' . $fileName);
    header('Content-Type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

function backup_delete_full(): void
{
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot delete backup in Demo mode'));
    }

    $csrfToken = $_GET['token'] ?? '';
    if (!Csrf::check($csrfToken)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }

    $fileName = basename((string) ($_GET['file'] ?? ''));
    $filePath = backup_resolve_full_file($fileName);
    if ($filePath === null) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Backup file does not exist or is not in the backup directory.'));
    }

    if (@unlink($filePath)) {
        r2(U . 'plugin/backup_list', 's', Lang::T('Backup file deleted successfully.'));
    }

    r2(U . 'plugin/backup_list', 'e', Lang::T('Error deleting backup file. Could not unlink the file.'));
}

function backup_download(): void
{
    global $UPLOAD_PATH, $_app_stage;
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot download database in Demo mode'));
    }

    if (!empty($_GET['file'])) {

        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = basename($_GET['file']);
        $filePath = backup_resolve_sql_file($fileName);

        if (!empty($fileName) && $filePath !== null) {

            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");

            readfile($filePath);
            exit;
        }

        r2(U . 'plugin/backup_list', 'e', Lang::T("The file does not exist."));
    }
}

function backup_delete(): void
{
    global $UPLOAD_PATH, $_app_stage;
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot delete database in Demo mode'));
    }

    if (isset($_GET['file'])) {
        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = basename((string) $_GET['file']);
        $filePath = backup_resolve_sql_file($fileName);

        if ($filePath !== null) {

            if (unlink($filePath)) {
                r2(U . 'plugin/backup_list', 's', Lang::T("Backup file deleted successfully."));
            } else {
                r2(U . 'plugin/backup_list', 'e', Lang::T("Error deleting backup file. Could not unlink the file."));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T("Backup file does not exist or is not in the backup directory."));
        }
    } else {
        r2(U . 'plugin/backup_list', 'e', Lang::T("No file specified for deletion."));
    }
}
function backup_restore(): void
{
    global $db_user, $db_pass, $db_host, $db_name;

    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Database restore is disabled in Demo mode'));
    }

    if (isset($_GET['file'])) {
        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = basename((string) $_GET['file']);
        $filePath = backup_resolve_sql_file($fileName);

        if ($filePath !== null) {
            // Capture both output and error from shell command
            $command = "mysql --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} < {$filePath} 2>&1";
            $output = @shell_exec($command);

            if ($output === null) {
                r2(U . 'plugin/backup_list', 's', 'Database restored successfully.');
            } else {
                _log('backup_restore: Error restoring database - ' . htmlspecialchars($output));
                r2(U . 'plugin/backup_list', 'e', 'Error restoring the database: ' . htmlspecialchars($output));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', 'Backup file not found.');
        }
    } else {
        r2(U . 'plugin/backup_list', 'e', 'No backup file specified.');
    }
}

function backup_restore_full(): void
{
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Database restore is disabled in Demo mode'));
    }

    $csrfToken = $_GET['token'] ?? '';
    if (!Csrf::check($csrfToken)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }

    $fileName = basename((string) ($_GET['file'] ?? ''));
    $filePath = backup_resolve_full_file($fileName);
    if ($filePath === null) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Backup file not found.'));
    }

    try {
        $result = WifiZoneBackup::restoreFullBackup($filePath);
        _log('[' . $admin['username'] . ']: Full backup restored ' . basename($filePath), $admin['user_type']);
        $message = Lang::T('Full backup restored successfully');
        if (!empty($result['rescue_backup'])) {
            $message .= '. ' . Lang::T('Rescue backup created') . ': ' . $result['rescue_backup'];
        }
        r2(U . 'plugin/backup_list', 's', $message);
    } catch (Throwable $e) {
        _log('backup_restore_full: ' . $e->getMessage());
        r2(U . 'plugin/backup_list', 'e', Lang::T('Error restoring full backup') . ': ' . htmlspecialchars($e->getMessage()));
    }
}
function backup_settingsPost(): void
{
    $admin = Admin::_info();

    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot change settings in Demo mode'));
    }

    if (_post('save') === 'save') {
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        if (isset($_POST['backup_retain_count']) && $_POST['backup_clear_old'] === 1) {
            $retainCount = $_POST['backup_retain_count'];

            if (empty($retainCount) || !is_numeric($retainCount) || $retainCount < 1) {
                r2(U . 'plugin/backup_list', 'e', 'Backup Retention Count cannot be empty and must be greater than 0');
                return;
            }
        }

        if (isset($_POST['cloud_upload']) && $_POST['cloud_upload'] == 1) {
            if (!empty($_POST['backup_dropbox_upload']) && trim((string) ($_POST['backup_dropbox_token'] ?? '')) === '') {
                r2(U . 'plugin/backup_list', 'e', Lang::T('Dropbox Token cannot be empty'));
                return;
            }
            if (!empty($_POST['backup_gdrive_upload'])) {
                $gdriveMissing = trim((string) ($_POST['backup_gdrive_client_id'] ?? '')) === ''
                    || trim((string) ($_POST['backup_gdrive_client_secret'] ?? '')) === ''
                    || trim((string) ($_POST['backup_gdrive_refresh_token'] ?? '')) === '';
                if ($gdriveMissing) {
                    r2(U . 'plugin/backup_list', 'e', Lang::T('Google Drive credentials cannot be empty'));
                    return;
                }
            }
        }

        $settings = [
            'backup_auto' => $_POST['backup_auto'] ? 1 : 0,
            'backup_clear_old' => $_POST['backup_clear_old'] ? 1 : 0,
            'backup_backup_time' => $_POST['backup_backup_time'],
            'backup_retain_count' => $_POST['backup_retain_count'],
            'backup_retain_days' => $_POST['backup_retain_days'],
            'cloud_upload' => $_POST['cloud_upload'] ? 1 : 0,
            'backup_dropbox_upload' => $_POST['backup_dropbox_upload'] ? 1 : 0,
            'backup_dropbox_token' => $_POST['backup_dropbox_token'],
            'backup_gdrive_upload' => $_POST['backup_gdrive_upload'] ? 1 : 0,
            'backup_gdrive_client_id' => trim((string) ($_POST['backup_gdrive_client_id'] ?? '')),
            'backup_gdrive_client_secret' => trim((string) ($_POST['backup_gdrive_client_secret'] ?? '')),
            'backup_gdrive_refresh_token' => trim((string) ($_POST['backup_gdrive_refresh_token'] ?? '')),
            'backup_gdrive_folder_id' => trim((string) ($_POST['backup_gdrive_folder_id'] ?? '')),
            'backup_telegram_upload' => $_POST['backup_telegram_upload'] ? 1 : 0,
            'backup_telegram_chatId' => $_POST['backup_telegram_chatId'],
        ];

        // Update or insert settings in the database
        backup_updateOrInsertSettingsInTheDatabase($settings, $admin);
        r2(U . 'plugin/backup_list', 's', Lang::T('Settings Saved Successfully'));
    }
}

/**
 * @param array $settings
 * @param false|ORM|null $admin
 * @return void
 */
function backup_updateOrInsertSettingsInTheDatabase(array $settings, false|ORM|null $admin): void
{
    foreach ($settings as $key => $value) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($d) {
            $d->value = $value;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $key;
            $d->value = $value;
            $d->save();
        }
    }
    _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type']);
}

function backup_cron(): void
{
    global $config, $UPLOAD_PATH;

    if (isset($config['backup_auto']) && $config['backup_auto']) {
        $backupDir = "$UPLOAD_PATH/backup";
        $lastBackupFile = "$backupDir/last_backup_time.txt";

        // Ensure backup directory exists and is writable
        if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            _log(Lang::T("Failed to create backup directory: $backupDir"));
            sendTelegram(Lang::T("Failed to create backup directory: $backupDir"));
            echo Lang::T("Failed to create backup directory: $backupDir\n\n");
            return;
        }

        if (!is_writable($backupDir)) {
            _log(Lang::T("Backup directory is not writable: $backupDir"));
            sendTelegram(Lang::T("Backup directory is not writable: $backupDir"));
            echo Lang::T("Backup directory is not writable: $backupDir\n\n");
            return;
        }

        // Get or create last backup time
        $lastBackupTime = 0;
        if (file_exists($lastBackupFile)) {
            $lastBackupTime = (int) file_get_contents($lastBackupFile);
        }

        $currentTime = time();
        $lastBackupDate = date('Y-m-d', $lastBackupTime ?: 0);
        $currentDate = date('Y-m-d');

        $shouldBackup = false;
        $backupType = '';

        switch ($config['backup_backup_time']) {
            case 'everyday':
                if ($lastBackupDate !== $currentDate) {
                    $shouldBackup = true;
                    $backupType = 'Daily';
                }
                break;

            case 'everyweek':
                if ((!$lastBackupTime || ($currentTime - $lastBackupTime) >= 7 * 24 * 3600) && date('w') == 0) {
                    $shouldBackup = true;
                    $backupType = 'Weekly';
                }
                break;

            case 'everymonth':
                if (date('j') == 1 && $lastBackupDate !== $currentDate) {
                    $shouldBackup = true;
                    $backupType = 'Monthly';
                }
                break;
        }

        if ($shouldBackup) {
            _log(Lang::T("Initiating $backupType backup"));
            sendTelegram(Lang::T("Initiating $backupType backup"));
            echo Lang::T("Initiating $backupType backup\n\n");

            try {
                if (!backup_add(true)) {
                    throw new \RuntimeException('Backup failed');
                }
                file_put_contents($lastBackupFile, $currentTime);

                // Get the latest backup file
                $files = glob("$backupDir/*.sql");
                if (empty($files)) {
                    throw new \RuntimeException('No backup files found');
                }
                usort($files, static function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latestBackupFile = $files[0];

                // Cloud upload
                if (isset($config['cloud_upload']) && $config['cloud_upload']) {
                    backup_uploadToCloud($latestBackupFile);
                } elseif (!empty($config['backup_telegram_upload'])) {
                    backup_sendToTelegram($latestBackupFile);
                }

                _log(Lang::T("$backupType backup completed successfully"));
                sendTelegram(Lang::T("$backupType backup completed successfully"));
                echo Lang::T("Backup completed successfully\n\n");
            } catch (Exception $e) {
                _log(Lang::T("Backup failed: ") . $e->getMessage());
                sendTelegram(Lang::T("Backup failed: ") . $e->getMessage());
                echo Lang::T("Backup failed: ") . $e->getMessage() . "\n\n";
            }
        }
        // Handle old backup cleanup
        if (!empty($config['backup_clear_old'])) {
            $retainCount = isset($config['backup_retain_count']) ? (int) $config['backup_retain_count'] : 5;
            $files = glob("$backupDir/*.sql");

            if ($files === false) {
                _log(Lang::T("Failed to list backup files"));
                return;
            }

            usort($files, static function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            if (count($files) > $retainCount) {
                $filesToDelete = array_slice($files, $retainCount);
                foreach ($filesToDelete as $file) {
                    if (@unlink($file)) {
                        _log(Lang::T("Deleted old backup file: ") . basename($file));
                        sendTelegram(Lang::T("Deleted old backup file: ") . basename($file));
                    } else {
                        _log(Lang::T("Failed to delete old backup file: ") . basename($file));
                    }
                }
            }
        }
        echo "\n";
    }
}

function backup_gdrive_fetch_access_token(array $config): string
{
    $clientId = trim((string) ($config['backup_gdrive_client_id'] ?? ''));
    $clientSecret = trim((string) ($config['backup_gdrive_client_secret'] ?? ''));
    $refreshToken = trim((string) ($config['backup_gdrive_refresh_token'] ?? ''));

    if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
        throw new \RuntimeException(Lang::T('Google Drive credentials are incomplete'));
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        throw new \RuntimeException('Google Drive token error (HTTP ' . $status . '): ' . $error . ' ' . $response);
    }

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        throw new \RuntimeException('Google Drive token response invalid: ' . $response);
    }

    return (string) $data['access_token'];
}

function backup_upload_to_google_drive(string $filePath, string $fileName, array $config): void
{
    $accessToken = backup_gdrive_fetch_access_token($config);
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        throw new \RuntimeException("Failed to read file: $filePath");
    }

    $metadata = ['name' => $fileName];
    $folderId = trim((string) ($config['backup_gdrive_folder_id'] ?? ''));
    if ($folderId !== '') {
        $metadata['parents'] = [$folderId];
    }

    $boundary = 'wz_backup_' . bin2hex(random_bytes(8));
    $body = "--{$boundary}\r\n"
        . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
        . json_encode($metadata, JSON_UNESCAPED_UNICODE) . "\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: application/octet-stream\r\n\r\n"
        . $fileContent . "\r\n"
        . "--{$boundary}--";

    $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || ($status !== 200 && $status !== 201)) {
        throw new \RuntimeException("Google Drive upload failed (HTTP $status): $error - $response");
    }

    _log(Lang::T('Backup file uploaded to Google Drive successfully') . ': ' . $fileName);
}

function backup_uploadToCloud(string $filePath): void
{
    global $config;
    $fileName = basename($filePath);

    if (!file_exists($filePath)) {
        throw new \RuntimeException("File not found: $filePath");
    }

    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        throw new \RuntimeException("Failed to read file: $filePath");
    }

    $accessToken = $config['backup_dropbox_token'] ?? '';

    if (!empty($accessToken) && ($config['backup_dropbox_upload'] ?? 0) == 1) {

        // Upload to Dropbox
        $url = 'https://content.dropboxapi.com/2/files/upload';
        $headers = [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . json_encode([
                'path' => "/$fileName",
                'mode' => 'overwrite'
            ])
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($status !== 200) {
            throw new \RuntimeException("Dropbox upload failed (HTTP $status): $error - $response");
        }
    }

    if (($config['backup_gdrive_upload'] ?? 0) == 1) {
        backup_upload_to_google_drive($filePath, $fileName, $config);
    }

    // Send to Telegram if configured
    if (!empty($config['backup_telegram_upload'])) {
        backup_sendToTelegram($filePath);
    }
}

function backup_sendToTelegram(string $filePath): void
{
    global $config;

    if (!is_file($filePath)) {
        throw new \RuntimeException("File not found: $filePath");
    }

    if (empty($config['backup_telegram_upload']) && !Message::isBackupAutoEnabled()) {
        return;
    }

    $fileName = basename($filePath);
    $chatId = trim((string) ($config['backup_telegram_chatId'] ?? ''));
    if ($chatId === '') {
        $chatId = null;
    }

    $sizeMb = round(filesize($filePath) / 1024 / 1024, 2);
    if ($sizeMb > WifiZoneBackup::TELEGRAM_MAX_MB) {
        _log(Lang::T('Telegram backup upload skipped: file too large') . " ({$sizeMb} MB)");
        Message::sendTelegram(Lang::T('Backup file too large for Telegram') . " ({$sizeMb} MB): {$fileName}", $chatId);
        return;
    }

    $caption = Lang::T('Database Backup') . ": {$fileName}\n" . date('Y-m-d H:i:s') . " — {$sizeMb} MB";
    $response = Message::sendTelegramDocument($filePath, $caption, $chatId);

    if (!Message::isTelegramSuccess($response)) {
        _log(Lang::T('Telegram backup upload failed'));
        Message::sendTelegram(Lang::T('Failed to send backup file via Telegram'));
        return;
    }

    _log(Lang::T('Backup file sent via Telegram successfully'));
    Message::sendTelegram(Lang::T('Backup file sent via Telegram successfully'));
}
function backup_upload_form(): void
{
    global $UPLOAD_PATH, $_app_stage;
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot upload database in Demo mode'));
    }

    $upload_path = backup_dir();
    if (isset($_FILES['file'])) {
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        // Check for upload errors
        if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('No file selected'));
            return;
        }
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['sql'];
        $allowed_size = 1024 * 1024 * 50; // 50 MB
        $new_file_name = 'backup_' . date('Y-m-d_H-i-s') . '.' . $file_ext;
        if ($file_size > $allowed_size) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('File size is too large. Maximum allowed size is 50MB'));
            exit;
        }

        if (!in_array($file_ext, $allowed_extensions)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid file type. Only SQL files are allowed'));
            exit;
        }

        if (move_uploaded_file($file_tmp, "$upload_path/$new_file_name")) {
            r2(U . 'plugin/backup_list', 's', Lang::T('File uploaded successfully'));
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Failed to upload file'));
        }
    } else {
        _alert(Lang::T('No file selected'), 'danger', "plugin/backup_list");
    }
}

function backup_upload_full_form(): void
{
    _admin();
    $admin = Admin::_info();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }
    if (backup_stage_locked()) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('You cannot upload backup in Demo mode'));
    }

    $uploadPath = backup_dir();
    if (!isset($_FILES['file'])) {
        _alert(Lang::T('No file selected'), 'danger', 'plugin/backup_list');
    }

    $csrfToken = _post('csrf_token');
    if (!Csrf::check($csrfToken)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }

    if ((int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('No file selected'));
    }

    $fileName = (string) ($_FILES['file']['name'] ?? '');
    $fileSize = (int) ($_FILES['file']['size'] ?? 0);
    $fileTmp = (string) ($_FILES['file']['tmp_name'] ?? '');
    $allowedSize = 1024 * 1024 * 1024;
    if ($fileSize > $allowedSize) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('File size is too large. Maximum allowed size is 1GB'));
    }

    if (!preg_match('/\.zip$/i', $fileName)) {
        r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid file type. Only full backup packages are allowed'));
    }

    $newFileName = WifiZoneBackup::FULL_BACKUP_PREFIX . 'uploaded_' . date('Y-m-d_H-i-s') . '.'
        . WifiZoneBackup::FULL_BACKUP_EXTENSION;
    if (move_uploaded_file($fileTmp, $uploadPath . DIRECTORY_SEPARATOR . $newFileName)) {
        _log('[' . $admin['username'] . ']: Full backup uploaded ' . $newFileName, $admin['user_type']);
        r2(U . 'plugin/backup_list', 's', Lang::T('File uploaded successfully'));
    }

    r2(U . 'plugin/backup_list', 'e', Lang::T('Failed to upload file'));
}
