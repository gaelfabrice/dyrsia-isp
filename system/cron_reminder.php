<?php

/**
 * Rappels d'expiration client (J-7, J-3, 24 h).
 * Peut être planifié seul (1×/jour) ou laissé à cron_wifizone.php (recommandé).
 * 0 7 * * * php /path/to/system/cron_reminder.php
 */
if (php_sapi_name() !== 'cli') {
    echo '<pre>';
}

require_once __DIR__ . '/../init.php';

echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

run_hook('cronjob_reminder');
WifiZoneNotify::processRenewalReminders();
echo "Renewal reminders done.\n";
