<?php

class WifiZoneBackup
{
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
        global $db_host, $db_user, $db_pass, $db_name;
        $cmd = sprintf(
            'mysqldump -h%s -u%s %s %s > %s 2>/dev/null',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            $db_pass !== '' ? '-p' . escapeshellarg($db_pass) : '',
            escapeshellarg($db_name),
            escapeshellarg($targetFile)
        );
        exec($cmd);
        return file_exists($targetFile);
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
}
