<?php
/**
 * Supprime les ventes hotspot/CamPay dupliquées (webhook + poll).
 *
 * Usage:
 *   php scripts/purge-duplicate-hotspot-sales.php --dry-run
 *   php scripts/purge-duplicate-hotspot-sales.php
 */

require dirname(__DIR__) . '/init.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$result = WifiZoneSales::purgeDuplicateHotspotSales($dryRun);

echo ($dryRun ? '[dry-run] ' : '')
    . 'Doublons à supprimer : ' . $result['deleted']
    . ' | ventes conservées : ' . $result['kept']
    . "\n";
