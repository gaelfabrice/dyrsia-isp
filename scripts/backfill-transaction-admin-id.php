<?php
/**
 * Backfill admin_id on hotspot/PPPoE transactions and recharges created from captive portal (admin_id = 0).
 *
 * Usage: php scripts/backfill-transaction-admin-id.php [--dry-run]
 */

require_once dirname(__DIR__) . '/init.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

if (!function_exists('hotspot_normalize_router_name')) {
    require_once $root_path . 'system/plugin/hotspot.php';
}

$updatedTransactions = 0;
$updatedRecharges = 0;

foreach (ORM::for_table('tbl_transactions')->where('admin_id', 0)->find_many() as $trx) {
    $router = trim((string) ($trx->routers ?? ''));
    if ($router === '' || in_array($router, ['balance', 'Custom Balance'], true)) {
        continue;
    }
    $normalized = function_exists('hotspot_normalize_router_name')
        ? hotspot_normalize_router_name($router)
        : $router;
    $ownerId = class_exists('WifiZoneHotspot') ? WifiZoneHotspot::routerAdminId($normalized) : 0;
    if ($ownerId <= 0) {
        continue;
    }
    echo "trx #{$trx->id} router={$router} -> admin_id={$ownerId}\n";
    if (!$dryRun) {
        $trx->admin_id = $ownerId;
        if ($normalized !== '' && $normalized !== $router) {
            $trx->routers = $normalized;
        }
        $trx->save();
    }
    $updatedTransactions++;
}

foreach (ORM::for_table('tbl_user_recharges')->where('admin_id', 0)->find_many() as $recharge) {
    $router = trim((string) ($recharge->routers ?? ''));
    if ($router === '') {
        continue;
    }
    $normalized = function_exists('hotspot_normalize_router_name')
        ? hotspot_normalize_router_name($router)
        : $router;
    $ownerId = class_exists('WifiZoneHotspot') ? WifiZoneHotspot::routerAdminId($normalized) : 0;
    if ($ownerId <= 0) {
        continue;
    }
    echo "recharge #{$recharge->id} router={$router} -> admin_id={$ownerId}\n";
    if (!$dryRun) {
        $recharge->admin_id = $ownerId;
        if ($normalized !== '' && $normalized !== $router) {
            $recharge->routers = $normalized;
        }
        $recharge->save();
    }
    $updatedRecharges++;
}

foreach (ORM::for_table('tbl_hotspot_payments')->find_many() as $payment) {
    $router = trim((string) ($payment->router_name ?? ''));
    if ($router === '') {
        continue;
    }
    $normalized = hotspot_normalize_router_name($router);
    if ($normalized === '' || $normalized === $router) {
        continue;
    }
    echo "hotspot payment #{$payment->id} router_name {$router} -> {$normalized}\n";
    if (!$dryRun) {
        $payment->router_name = $normalized;
        $payment->save();
    }
}

echo ($dryRun ? '[dry-run] ' : '') . "Updated transactions: {$updatedTransactions}, recharges: {$updatedRecharges}\n";
