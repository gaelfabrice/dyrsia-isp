#!/usr/bin/env php
<?php

/**
 * Configure daily DB backup + Telegram delivery in tbl_appconfig.
 *
 * Reads credentials from environment:
 *   BACKUP_TELEGRAM_BOT_TOKEN
 *   BACKUP_TELEGRAM_CHAT_ID
 *   BACKUP_AUTO (yes|1)
 *
 * Usage:
 *   BACKUP_TELEGRAM_BOT_TOKEN=... BACKUP_TELEGRAM_CHAT_ID=... php scripts/configure-backup-telegram.php
 */

$root = dirname(__DIR__);
require $root . '/init.php';

$bot = trim((string) (getenv('BACKUP_TELEGRAM_BOT_TOKEN') ?: ''));
$chat = trim((string) (getenv('BACKUP_TELEGRAM_CHAT_ID') ?: ''));
$auto = strtolower(trim((string) (getenv('BACKUP_AUTO') ?: 'yes')));

if ($bot === '' || $chat === '') {
    fwrite(STDERR, "Missing BACKUP_TELEGRAM_BOT_TOKEN or BACKUP_TELEGRAM_CHAT_ID\n");
    exit(1);
}

$settings = [
    'telegram_bot' => $bot,
    'backup_auto' => in_array($auto, ['1', 'yes', 'true', 'on'], true) ? '1' : '0',
    'backup_backup_time' => 'everyday',
    'backup_clear_old' => '1',
    'backup_retain_count' => '7',
    'backup_telegram_upload' => '1',
    'backup_telegram_chatId' => $chat,
];

foreach ($settings as $key => $value) {
    WifiZoneCore::setConfig($key, $value);
    echo "Set {$key}\n";
}

echo "Backup Telegram configuration applied.\n";
