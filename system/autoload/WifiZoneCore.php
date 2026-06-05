<?php

/**
 * WifiZone — noyau d'installation et configuration
 */
class WifiZoneCore
{
    public static function boot()
    {
        self::installSchema();
        self::ensureUsersLoginTokenColumn();
        self::ensureConfig();
        self::applyLocaleDefaults();
        self::applyBrandDefaults();
    }

    public static function ensureUsersLoginTokenColumn()
    {
        try {
            global $db;
            if (!isset($db)) {
                return;
            }
            $cols = $db->query("SHOW COLUMNS FROM tbl_users LIKE 'login_token'")->fetchAll(PDO::FETCH_ASSOC);
            if (count($cols) === 0) {
                ORM::raw_execute('ALTER TABLE tbl_users ADD COLUMN login_token VARCHAR(40) NULL DEFAULT NULL');
            }
        } catch (Throwable $e) {
            error_log('wifizone ensureUsersLoginTokenColumn: ' . $e->getMessage());
        }
    }

    /**
     * Application display name: DYRSIA.
     */
    public static function applyBrandDefaults()
    {
        $name = trim((string) self::config('CompanyName', ''));
        $legacy = preg_match('/wifizones|phpnux/i', $name);
        if ($name === '' || $legacy) {
            self::setConfig('CompanyName', 'DYRSIA');
        }
        if (self::config('wifizone_brand_v1') !== 'yes') {
            self::setConfig('wifizone_brand_v1', 'yes');
        }
    }

    /**
     * English by default, currency XAF (Franc CFA).
     */
    public static function applyLocaleDefaults()
    {
        if (self::config('wifizone_project_locale_v1') !== 'yes') {
            self::setConfig('language', 'english');
            self::setConfig('currency_code', 'XAF');
            self::setConfig('wifizone_project_locale_v1', 'yes');
        } else {
            $cur = ORM::for_table('tbl_appconfig')->where('setting', 'currency_code')->find_one();
            if ($cur && in_array(strtoupper(trim($cur->value)), ['RP', 'IDR', 'BDT', 'USD', '₹', ''], true)) {
                self::setConfig('currency_code', 'XAF');
            }
        }

        if (!ORM::for_table('tbl_appconfig')->where('setting', 'language')->find_one()) {
            self::setConfig('language', 'english');
        }
        if (!ORM::for_table('tbl_appconfig')->where('setting', 'currency_code')->find_one()) {
            self::setConfig('currency_code', 'XAF');
        }
    }

    public static function allowedLanguages()
    {
        return [
            'english' => 'English',
            'french' => 'Français',
        ];
    }

    public static function config($key, $default = '')
    {
        global $config;
        return $config[$key] ?? $default;
    }

    public static function setConfig($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
            $row->value = $value;
            $row->save();
        }
        global $config;
        $config[$key] = $value;
    }

    public static function installSchema()
    {
        $db = ORM::get_db();
        $queries = [
            "CREATE TABLE IF NOT EXISTS wifizone_audit_log (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                actor_id INT NOT NULL DEFAULT 0,
                actor_type VARCHAR(32) NOT NULL DEFAULT 'admin',
                action VARCHAR(64) NOT NULL,
                entity_type VARCHAR(64) NOT NULL,
                entity_id VARCHAR(64) NOT NULL DEFAULT '',
                payload TEXT,
                ip VARCHAR(64),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_wallet_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                status ENUM('completed','pending','rejected') DEFAULT 'completed',
                approved_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_payment_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gateway VARCHAR(64) NOT NULL,
                reference VARCHAR(128) NOT NULL,
                customer_id INT NOT NULL,
                plan_id INT NOT NULL,
                router VARCHAR(128) NOT NULL,
                amount DECIMAL(15,2) DEFAULT 0,
                payload TEXT,
                attempts INT DEFAULT 0,
                status ENUM('pending','processing','done','failed') DEFAULT 'pending',
                last_error TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_ref (gateway, reference),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_reseller_api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reseller_id INT NOT NULL,
                api_key VARCHAR(64) NOT NULL UNIQUE,
                enabled TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reseller (reseller_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_rate_limit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(64) NOT NULL,
                identifier VARCHAR(128) NOT NULL,
                hits INT DEFAULT 1,
                window_start INT NOT NULL,
                UNIQUE KEY uk_scope (scope, identifier)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_fcm_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_backup_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_type VARCHAR(32) NOT NULL,
                status VARCHAR(32) NOT NULL,
                file_path VARCHAR(512),
                remote_target VARCHAR(128),
                scheduled_at DATETIME,
                completed_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_customer_devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                acs_device_id VARCHAR(255),
                mac VARCHAR(32),
                linked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_customer (customer_id),
                INDEX idx_mac (mac)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_network_nodes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                node_type VARCHAR(32) NOT NULL,
                name VARCHAR(128) NOT NULL,
                parent_id INT DEFAULT NULL,
                lat DECIMAL(10,7) DEFAULT NULL,
                lng DECIMAL(10,7) DEFAULT NULL,
                meta TEXT,
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($queries as $sql) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                WifiZoneLogger::logPluginError('schema', $e);
            }
        }
        if (class_exists('Withdrawal')) {
            Withdrawal::ensureSchema();
        }
        if (class_exists('WifiZoneFfthSchema')) {
            WifiZoneFfthSchema::install();
        }
        self::ensureColumn('tbl_routers', 'login_secret', 'VARCHAR(64) DEFAULT NULL');
        self::ensureColumn('tbl_routers', 'currency_code', 'VARCHAR(8) DEFAULT NULL');
        self::ensureColumn('tbl_routers', 'zone_name', 'VARCHAR(64) DEFAULT NULL');
        self::ensureColumn('admin_wallet_logs', 'transfer_type', "VARCHAR(32) DEFAULT NULL");
    }

    private static function ensureColumn($table, $column, $definition)
    {
        try {
            $cols = ORM::for_table($table)->raw_query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])->find_many();
            if (count($cols) === 0) {
                ORM::get_db()->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Exception $e) {
            // table may not exist yet
        }
    }

    public static function ensureConfig()
    {
        $defaults = [
            'wifizone_hotspot_hmac' => 'yes',
            'wifizone_hotspot_legacy_get' => 'yes',
            'wifizone_commission_limit_agent' => '50000',
            'wifizone_commission_limit_sales' => '25000',
            'wifizone_transfer_approval_threshold' => '10000',
            'wifizone_genieacs_alert_minutes' => '30',
            'wifizone_redis_enabled' => 'no',
            'wifizone_redis_host' => '127.0.0.1',
            'wifizone_redis_port' => '6379',
            'wifizone_jwt_secret' => bin2hex(random_bytes(16)),
            'wifizone_renewal_notify_days' => '7,3,1',
            'wifizone_plugin_telegram_errors' => 'no',
            'wifizone_withdraw_commission_hotspot' => '15',
            'wifizone_withdraw_commission_pppoe' => '10',
            'wifizone_withdraw_commission_default' => '10',
        ];
        foreach ($defaults as $k => $v) {
            $exists = ORM::for_table('tbl_appconfig')->where('setting', $k)->find_one();
            if (!$exists) {
                self::setConfig($k, $v);
            }
        }
    }

    public static function healthCheck()
    {
        $checks = [
            'database' => false,
            'cron_marker' => false,
            'writable_uploads' => false,
        ];
        try {
            ORM::for_table('tbl_appconfig')->find_one();
            $checks['database'] = true;
        } catch (Exception $e) {
        }
        global $UPLOAD_PATH;
        $checks['writable_uploads'] = is_writable($UPLOAD_PATH);
        $cronFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
        if (file_exists($cronFile)) {
            $checks['cron_marker'] = (time() - filemtime($cronFile)) < 900;
        }
        return $checks;
    }
}
