#!/usr/bin/env php
<?php

/**
 * Export Campay / SMTP / Telegram settings from tbl_appconfig into
 * system/install/appconfig.production.json (used by build-dist.sh).
 *
 * Usage: php scripts/export-production-appconfig.php
 */

$root = dirname(__DIR__);
require $root . '/init.php';

$keys = [
    'campay_username',
    'campay_password',
    'campay_environment',
    'campay_currency',
    'payment_gateway',
    'smtp_host',
    'smtp_port',
    'smtp_user',
    'smtp_pass',
    'smtp_ssltls',
    'mail_from',
    'mail_reply_to',
    'telegram_bot',
    'telegram_target_id',
    'superadmin_telegram_bot',
    'superadmin_telegram_chat_id',
    'backup_auto',
    'backup_backup_time',
    'backup_clear_old',
    'backup_retain_count',
    'backup_telegram_upload',
    'backup_telegram_chatId',
];

$out = [];
foreach ($keys as $key) {
    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    if ($row && trim((string) $row->value) !== '') {
        $out[$key] = $row->value;
    }
}

if (empty($out['telegram_bot']) && !empty($out['superadmin_telegram_bot'])) {
    $out['telegram_bot'] = $out['superadmin_telegram_bot'];
}
if (empty($out['telegram_target_id']) && !empty($out['superadmin_telegram_chat_id'])) {
    $out['telegram_target_id'] = $out['superadmin_telegram_chat_id'];
}

$target = $root . '/system/install/appconfig.production.json';
file_put_contents(
    $target,
    json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

fwrite(STDOUT, "Wrote " . count($out) . " settings to {$target}\n");
