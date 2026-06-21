<?php

/**
 * WifiZone — noyau d'installation et configuration
 */
class WifiZoneCore
{
    public static function boot()
    {
        self::purgeMacOsMetadataIfDue();
        self::installSchema();
        self::ensureUsersLoginTokenColumn();
        self::ensureConfig();
        self::applyLocaleDefaults();
        self::applyBrandDefaults();
        self::applyBandwidthDefaults();
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
        }
        if (self::config('wifizone_locale_fr_v2') !== 'yes') {
            $lang = ORM::for_table('tbl_appconfig')->where('setting', 'language')->find_one();
            if (!$lang || trim($lang->value) === '' || $lang->value === 'english') {
                self::setConfig('language', 'french');
            }
            self::setConfig('wifizone_locale_fr_v2', 'yes');
        }
        if (!ORM::for_table('tbl_appconfig')->where('setting', 'user_notification_reminder')->find_one()) {
            self::setConfig('user_notification_reminder', 'wa');
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

    /**
     * Profils débit par défaut (Hotspot / PPPoE). Insérés une seule fois si tbl_bandwidth est vide.
     * Modifiables ensuite via Réseau → Bandwidth.
     *
     * @return list<array{name_bw: string, rate_down: int, rate_down_unit: string, rate_up: int, rate_up_unit: string, burst: string}>
     */
    public static function defaultBandwidthProfiles()
    {
        return [
            [
                'name_bw' => '4M/2M',
                'rate_down' => 4,
                'rate_down_unit' => 'Mbps',
                'rate_up' => 2,
                'rate_up_unit' => 'Mbps',
                'burst' => '4M/4M 8M/8M 3M/3M 16/16 8 2M/2M',
            ],
            [
                'name_bw' => '8M/4M',
                'rate_down' => 8,
                'rate_down_unit' => 'Mbps',
                'rate_up' => 4,
                'rate_up_unit' => 'Mbps',
                'burst' => '8M/8M 16M/16M 6M/6M 16/16 8 4M/4M',
            ],
            [
                'name_bw' => '12M/6M',
                'rate_down' => 12,
                'rate_down_unit' => 'Mbps',
                'rate_up' => 6,
                'rate_up_unit' => 'Mbps',
                'burst' => '6M/6M 12M/12M 4608k/4608k 16/16 8 3M/3M',
            ],
            [
                'name_bw' => '20M/10M',
                'rate_down' => 20,
                'rate_down_unit' => 'Mbps',
                'rate_up' => 10,
                'rate_up_unit' => 'Mbps',
                'burst' => '10M/10M 20M/20M 7680k/7680k 16/16 8 5M/5M',
            ],
            [
                'name_bw' => '2M/1M',
                'rate_down' => 2,
                'rate_down_unit' => 'Mbps',
                'rate_up' => 1,
                'rate_up_unit' => 'Mbps',
                'burst' => '',
            ],
            [
                'name_bw' => '512k/256k',
                'rate_down' => 512,
                'rate_down_unit' => 'Kbps',
                'rate_up' => 256,
                'rate_up_unit' => 'Kbps',
                'burst' => '128k/128k',
            ],
        ];
    }

    public static function applyBandwidthDefaults()
    {
        try {
            if (self::config('wifizone_bandwidth_defaults_v1') === 'yes') {
                return;
            }
            $count = (int) ORM::for_table('tbl_bandwidth')->count();
            if ($count > 0) {
                self::setConfig('wifizone_bandwidth_defaults_v1', 'yes');

                return;
            }
            foreach (self::defaultBandwidthProfiles() as $profile) {
                $existing = ORM::for_table('tbl_bandwidth')
                    ->where('name_bw', $profile['name_bw'])
                    ->find_one();
                if ($existing) {
                    continue;
                }
                $row = ORM::for_table('tbl_bandwidth')->create();
                $row->name_bw = $profile['name_bw'];
                $row->rate_down = $profile['rate_down'];
                $row->rate_down_unit = $profile['rate_down_unit'];
                $row->rate_up = $profile['rate_up'];
                $row->rate_up_unit = $profile['rate_up_unit'];
                $row->burst = $profile['burst'];
                $row->save();
            }
            self::setConfig('wifizone_bandwidth_defaults_v1', 'yes');
        } catch (Throwable $e) {
            error_log('wifizone applyBandwidthDefaults: ' . $e->getMessage());
        }
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
        self::ensureColumn('tbl_pool', 'admin_id', 'INT NULL DEFAULT NULL');
        self::ensureColumn('tbl_port_pool', 'admin_id', 'INT NULL DEFAULT NULL');
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
            'wifizone_renewal_notify_days' => '7,3,24h',
            'wifizone_plugin_telegram_errors' => 'yes',
            'wifizone_ops_alerts' => 'yes',
            'router_check' => '1',
            'check_customer_online' => 'no',
            'country_code_phone' => '237',
            'hotspot_message' => '1',
            'hotspot_message_via' => 'both',
            'hotspot_help_whatsapp' => '33761951914',
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

    /**
     * macOS AppleDouble / Finder metadata (._*, .DS_Store, __MACOSX).
     */
    public static function isMacOsMetadataName($name)
    {
        $name = (string) $name;
        if ($name === '' || $name === '.' || $name === '..') {
            return false;
        }

        return $name === '.DS_Store'
            || $name === '__MACOSX'
            || str_starts_with($name, '._');
    }

    /**
     * @param list<string>|null $paths
     * @return int Number of files/directories removed
     */
    public static function purgeMacOsMetadata(?array $paths = null)
    {
        global $root_path, $UPLOAD_PATH, $CACHE_PATH;

        if ($paths === null) {
            $paths = array_values(array_filter([
                $UPLOAD_PATH ?? null,
                $CACHE_PATH ?? null,
                isset($root_path) ? $root_path . 'ui' . DIRECTORY_SEPARATOR . 'compiled' : null,
                isset($root_path) ? $root_path . 'ui' . DIRECTORY_SEPARATOR . 'cache' : null,
                isset($root_path) ? $root_path . 'pages' : null,
            ], static function ($path) {
                return is_string($path) && $path !== '' && is_dir($path);
            }));
        }

        $removed = 0;
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $item) {
                    $base = $item->getBasename();
                    if (!self::isMacOsMetadataName($base)) {
                        continue;
                    }
                    if ($item->isDir()) {
                        if (@rmdir($item->getPathname())) {
                            $removed++;
                        }
                    } elseif (@unlink($item->getPathname())) {
                        $removed++;
                    }
                }
            } catch (Throwable $e) {
                error_log('wifizone purgeMacOsMetadata: ' . $e->getMessage());
            }
        }

        return $removed;
    }

    public static function purgeMacOsMetadataIfDue($intervalSeconds = 3600)
    {
        global $CACHE_PATH;

        $cacheDir = $CACHE_PATH ?? null;
        if (!is_string($cacheDir) || $cacheDir === '' || !is_dir($cacheDir)) {
            self::purgeMacOsMetadata();

            return;
        }

        $marker = $cacheDir . DIRECTORY_SEPARATOR . 'macos_metadata_cleanup.txt';
        if (is_file($marker) && (time() - filemtime($marker)) < (int) $intervalSeconds) {
            return;
        }

        self::purgeMacOsMetadata();
        @touch($marker);
    }
}
