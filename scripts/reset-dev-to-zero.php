<?php
/**
 * Remise à zéro dev : supprime toutes les données d'exploitation.
 * Conserve le schéma MySQL + 1 SuperAdmin (admin/admin) + réglages système minimaux.
 *
 * Usage: php scripts/reset-dev-to-zero.php --yes
 */

if (!in_array('--yes', $argv ?? [], true)) {
    fwrite(STDERR, "Usage: php scripts/reset-dev-to-zero.php --yes\n");
    fwrite(STDERR, "ATTENTION: supprime routeurs, clients, ventes, logs, config hotspot/PPPoE, etc.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/init.php';

function clear_dir_files(string $dir): int
{
    $cleared = 0;
    if (!is_dir($dir)) {
        return 0;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }
        $name = $item->getFilename();
        if ($name === 'index.html' || $name === 'notifications.default.json') {
            continue;
        }
        if (@unlink($item->getPathname())) {
            $cleared++;
        }
    }

    return $cleared;
}

$tables = [];
foreach (ORM::get_db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
    $tables[] = $row[0];
}

$keepStructure = ['tbl_users', 'tbl_appconfig', 'tbl_widgets'];
$truncate = array_values(array_filter($tables, static function ($t) {
    return strpos($t, 'tbl_') === 0
        || strpos($t, 'wifizone_') === 0
        || strpos($t, 'admin_') === 0
        || strpos($t, 'referral_') === 0
        || strpos($t, 'hotspot_') === 0
        || strpos($t, 'api_') === 0
        || strpos($t, 'rad') === 0
        || $t === 'nas'
        || $t === 'nasreload'
        || $t === 'isp_settings'
        || $t === 'olt_devices'
        || $t === 'olt_onu';
}));

echo "=== Remise à zéro DYRSIA (dev) ===\n";

try {
    ORM::raw_execute('SET FOREIGN_KEY_CHECKS=0');
} catch (Throwable $e) {
}

$truncated = 0;
foreach ($truncate as $table) {
    try {
        ORM::raw_execute("TRUNCATE TABLE `{$table}`");
        $truncated++;
        echo "TRUNCATE {$table}\n";
    } catch (Throwable $e) {
        echo "SKIP {$table}: " . $e->getMessage() . "\n";
    }
}

try {
    ORM::raw_execute('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $e) {
}

// SuperAdmin par défaut (mot de passe: admin)
ORM::raw_execute(
    "INSERT INTO tbl_users (id, root, username, fullname, password, user_type, status, last_login, creationdate)
     VALUES (1, 0, 'admin', 'Administrator', 'd033e22ae348aeb5660fc2140aec35850c4da997', 'SuperAdmin', 'Active', NULL, NOW())"
);
ORM::raw_execute('ALTER TABLE tbl_users AUTO_INCREMENT = 2');
echo "OK tbl_users → admin / admin (SuperAdmin)\n";

$coreConfig = [
    ['CompanyName', 'DYRSIA'],
    ['currency_code', 'FCFA'],
    ['language', 'french'],
    ['show-logo', '1'],
    ['nstyle', 'blue'],
    ['timezone', 'Africa/Douala'],
    ['dec_point', ','],
    ['thousands_sep', ' '],
    ['rtl', '0'],
    ['address', ''],
    ['phone', ''],
    ['date_format', 'd M Y'],
    ['note', ''],
    ['payment_gateway', ''],
    ['hotspot_enabled', '0'],
    ['pppoe_enabled', '0'],
];

$id = 1;
foreach ($coreConfig as [$setting, $value]) {
    ORM::raw_execute(
        'INSERT INTO tbl_appconfig (id, setting, value) VALUES (?, ?, ?)',
        [$id++, $setting, $value]
    );
}
echo "OK tbl_appconfig → réglages système minimaux (sans hotspot/PPPoE/routeurs)\n";

// Widgets dashboard par défaut (Admin)
$widgets = [
    [1, 1, 1, 'Admin', 1, 'Top Widget', 'top_widget', ''],
    [2, 2, 1, 'Admin', 1, 'Default Info', 'default_info_row', ''],
    [3, 1, 2, 'Admin', 1, 'Graph Monthly Registered Customers', 'graph_monthly_registered_customers', ''],
    [4, 2, 2, 'Admin', 1, 'Graph Monthly Sales', 'graph_monthly_sales', ''],
    [7, 1, 3, 'Admin', 1, 'Cron Monitor', 'cron_monitor', ''],
    [8, 2, 3, 'Admin', 1, 'Mikrotik Cron Monitor', 'mikrotik_cron_monitor', ''],
];
foreach ($widgets as $w) {
    ORM::raw_execute(
        'INSERT INTO tbl_widgets (id, orders, position, user, enabled, title, widget, content) VALUES (?,?,?,?,?,?,?,?)',
        $w
    );
}
ORM::raw_execute('ALTER TABLE tbl_widgets AUTO_INCREMENT = 100');
echo "OK tbl_widgets → widgets Admin par défaut\n";

$cacheFiles = 0;
foreach ([
    $root . '/system/cache',
    $root . '/ui/cache',
    $root . '/ui/compiled',
    $root . '/system/uploads/mikrotik_hotspot',
    $root . '/system/uploads/mikrotik_hotspot/admin_73',
] as $dir) {
    $cacheFiles += clear_dir_files($dir);
}
echo "OK cache/uploads: {$cacheFiles} fichier(s) supprimé(s)\n";

$logFile = $root . '/php_dev_stdout.log';
if (is_file($logFile)) {
    file_put_contents($logFile, '');
    echo "OK php_dev_stdout.log vidé\n";
}

echo "\n=== Terminé ===\n";
echo "Connexion: admin / admin\n";
echo "État: 0 routeur, 0 client, 0 vente, config hotspot/PPPoE vide.\n";
echo "Relancez: ./dev-server.sh\n";
