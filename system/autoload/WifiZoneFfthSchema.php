<?php

/**
 * FTTH / network mapping tables (ODC, ODP, OLT, etc.)
 */
class WifiZoneFfthSchema
{
    public static function install()
    {
        $db = ORM::get_db();
        $charset = 'utf8mb4 COLLATE utf8mb4_general_ci';
        $queries = [
            "CREATE TABLE IF NOT EXISTS `tbl_olt` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `brand` varchar(50) DEFAULT NULL,
                `model` varchar(100) DEFAULT NULL,
                `serial_number` varchar(100) DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `coordinates` varchar(100) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `total_ports` int(11) NOT NULL DEFAULT 8,
                `used_ports` int(11) NOT NULL DEFAULT 0,
                `status` enum('Active','Inactive','Maintenance') NOT NULL DEFAULT 'Active',
                `description` text DEFAULT NULL,
                `parent_type` enum('router','olt') DEFAULT NULL,
                `parent_router_id` int(11) DEFAULT NULL,
                `parent_olt_id` int(11) DEFAULT NULL,
                `port_used_name` varchar(50) DEFAULT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `ports_data` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_name` (`name`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_olt_ports` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `olt_id` int(11) NOT NULL,
                `port_number` int(11) NOT NULL,
                `port_name` varchar(50) NOT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `is_used` tinyint(1) NOT NULL DEFAULT 0,
                `connected_device_type` varchar(50) DEFAULT NULL,
                `connected_device_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_olt_port` (`olt_id`,`port_number`),
                KEY `idx_olt_id` (`olt_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_odc` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `box_id` varchar(50) DEFAULT NULL,
                `slot_number` int(11) NOT NULL DEFAULT 1,
                `name` varchar(100) NOT NULL,
                `description` text DEFAULT NULL,
                `coordinates` varchar(100) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `total_ports` int(11) NOT NULL DEFAULT 8,
                `used_ports` int(11) NOT NULL DEFAULT 0,
                `status` enum('Active','Inactive','Maintenance') NOT NULL DEFAULT 'Active',
                `parent_type` enum('olt','odc') DEFAULT NULL,
                `parent_olt_id` int(11) DEFAULT NULL,
                `parent_odc_id` int(11) DEFAULT NULL,
                `port_used_name` varchar(50) DEFAULT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `ports_data` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `router_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_name` (`name`),
                KEY `idx_coordinates` (`coordinates`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_odc_ports` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `odc_id` int(11) NOT NULL,
                `port_number` int(11) NOT NULL,
                `port_name` varchar(50) NOT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `is_used` tinyint(1) NOT NULL DEFAULT 0,
                `connected_device_type` varchar(50) DEFAULT NULL,
                `connected_device_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_odc_port` (`odc_id`,`port_number`),
                KEY `idx_odc_id` (`odc_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_odp` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `box_id` varchar(50) DEFAULT NULL,
                `slot_number` int(11) NOT NULL DEFAULT 1,
                `name` varchar(100) NOT NULL,
                `description` text DEFAULT NULL,
                `coordinates` varchar(100) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `total_ports` int(11) NOT NULL DEFAULT 8,
                `used_ports` int(11) NOT NULL DEFAULT 0,
                `status` enum('Active','Inactive','Maintenance') NOT NULL DEFAULT 'Active',
                `parent_type` enum('odc','odp') DEFAULT NULL,
                `parent_odc_id` int(11) DEFAULT NULL,
                `parent_odp_id` int(11) DEFAULT NULL,
                `port_used_name` varchar(50) DEFAULT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `ports_data` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `odc_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_name` (`name`),
                KEY `idx_coordinates` (`coordinates`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_odp_ports` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `odp_id` int(11) NOT NULL,
                `port_number` int(11) NOT NULL,
                `port_name` varchar(50) NOT NULL,
                `port_label` varchar(100) DEFAULT NULL,
                `is_used` tinyint(1) NOT NULL DEFAULT 0,
                `connected_device_type` varchar(50) DEFAULT NULL,
                `connected_device_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_odp_port` (`odp_id`,`port_number`),
                KEY `idx_odp_id` (`odp_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_tiang` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `tipe` enum('Besi','Beton','Kayu','Galvanis','Lainnya') NOT NULL DEFAULT 'Besi',
                `tinggi` decimal(5,2) DEFAULT NULL,
                `coordinates` varchar(100) NOT NULL,
                `address` text DEFAULT NULL,
                `status` enum('Aktif','Perbaikan','Rusak') NOT NULL DEFAULT 'Aktif',
                `pemilik` enum('Sendiri','PLN','Telkom','Bersama','Lainnya') NOT NULL DEFAULT 'Sendiri',
                `biaya_sewa` decimal(12,2) DEFAULT 0.00,
                `slack_kabel` decimal(10,2) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_coordinates` (`coordinates`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_kabel` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) DEFAULT NULL,
                `device_1_type` varchar(50) NOT NULL,
                `device_1_id` int(11) NOT NULL,
                `device_2_type` varchar(50) NOT NULL,
                `device_2_id` int(11) NOT NULL,
                `coordinates_path` text NOT NULL,
                `panjang` decimal(10,2) NOT NULL DEFAULT 0.00,
                `jumlah_core` int(11) NOT NULL DEFAULT 12,
                `jenis_kabel` varchar(50) DEFAULT 'ADSS',
                `cable_color` varchar(20) DEFAULT '#78716c',
                `jumlah_sambungan` int(11) NOT NULL DEFAULT 0,
                `sambungan_data` text DEFAULT NULL,
                `description` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_device_1` (`device_1_type`,`device_1_id`),
                KEY `idx_device_2` (`device_2_type`,`device_2_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_homepass` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) DEFAULT NULL,
                `coordinates` varchar(100) NOT NULL,
                `address` text DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `status` enum('Prospek','Pending','Bermasalah','Tidak Minat','Rumah Kosong','Sudah Langganan') NOT NULL DEFAULT 'Prospek',
                `kategori` enum('Rumah','Ruko','Kantor','Apartemen','Kos-kosan','Lainnya') NOT NULL DEFAULT 'Rumah',
                `catatan` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `surveyor` varchar(100) DEFAULT NULL,
                `tanggal_survey` date DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_coordinates` (`coordinates`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
            "CREATE TABLE IF NOT EXISTS `tbl_customer_online_status` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `username` varchar(100) DEFAULT NULL,
                `status` enum('online','isolir','offline','off_isolir') NOT NULL DEFAULT 'offline',
                `current_ip` varchar(45) DEFAULT NULL,
                `last_seen` datetime DEFAULT NULL,
                `status_changed_at` datetime DEFAULT NULL,
                `offline_alert_sent` tinyint(1) NOT NULL DEFAULT 0,
                `online_alert_sent` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime DEFAULT current_timestamp(),
                `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_id` (`customer_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset",
        ];

        foreach ($queries as $sql) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                if (class_exists('WifiZoneLogger')) {
                    WifiZoneLogger::logPluginError('ffth_schema', $e);
                }
            }
        }

        self::ensureCustomerColumns();
    }

    private static function ensureCustomerColumns()
    {
        foreach (
            [
                ['tbl_customers', 'odp_id', 'INT(11) DEFAULT NULL'],
                ['tbl_customers', 'foto_lokasi', 'VARCHAR(255) DEFAULT NULL'],
                ['tbl_customers', 'coordinates', 'VARCHAR(100) DEFAULT NULL'],
            ] as $col
        ) {
            try {
                $db = ORM::get_db();
                $stmt = $db->prepare("SHOW COLUMNS FROM `{$col[0]}` LIKE ?");
                $stmt->execute([$col[1]]);
                if ($stmt->rowCount() === 0) {
                    $db->exec("ALTER TABLE `{$col[0]}` ADD COLUMN `{$col[1]}` {$col[2]}");
                }
            } catch (Exception $e) {
                // ignore
            }
        }
    }
}
