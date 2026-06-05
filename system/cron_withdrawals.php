<?php

/**
 * Expiration automatique des demandes de retrait (> 24h sans action SuperAdmin).
 * Crontab recommandé : toutes les 12 heures — php /path/to/system/cron_withdrawals.php
 */
if (php_sapi_name() !== 'cli') {
    die("CLI only\n");
}

$root = dirname(__DIR__);
require_once $root . '/init.php';

Withdrawal::ensureSchema();
$expired = Withdrawal::expireStaleRequests(true);

echo 'Withdrawal cron OK — ' . $expired . ' demande(s) expirée(s) — ' . date('c') . "\n";
