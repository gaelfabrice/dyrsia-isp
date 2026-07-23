#!/usr/bin/env php
<?php

/**
 * Run daily DB backup and send to Telegram (manual / test).
 *
 * Usage:
 *   php scripts/run-daily-backup.php
 *   php scripts/run-daily-backup.php --force
 */

$root = dirname(__DIR__);
require $root . '/init.php';

$force = in_array('--force', $argv ?? [], true);
$result = WifiZoneBackup::runScheduledTelegramBackup($force);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
exit(!empty($result['success']) ? 0 : 1);
