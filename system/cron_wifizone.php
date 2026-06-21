<?php

/**
 * Cron principal DYRSIA — toutes les 5 minutes.
 * Crontab: toutes les 5 minutes — php /path/to/system/cron_wifizone.php
 */
if (php_sapi_name() !== 'cli') {
    die("CLI only\n");
}

$root = dirname(__DIR__);
require_once $root . '/init.php';

WifiZoneOps::runMainCron();
