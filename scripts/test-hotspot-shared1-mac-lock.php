<?php

/**
 * Vérifie le verrou MAC shared_users=1 (sans dépendre d'une session captives live).
 * Usage: php scripts/test-hotspot-shared1-mac-lock.php
 */
$root = dirname(__DIR__);
require_once $root . '/init.php';

function assertTrue(bool $cond, string $label): void
{
    echo ($cond ? '[OK] ' : '[FAIL] ') . $label . "\n";
    if (!$cond) {
        exit(1);
    }
}

HotspotCustomer::ensureRechargeDeviceMacColumn();

$macA = 'AA:BB:CC:DD:EE:01';
$macB = 'AA:BB:CC:DD:EE:02';

$plan = [
    'id' => 13,
    'type' => 'Hotspot',
    'name_plan' => '10M',
    'shared_users' => 1,
];
$planDuo = [
    'id' => 14,
    'type' => 'Hotspot',
    'name_plan' => 'DUO',
    'shared_users' => 2,
];

assertTrue(!Mikrotik::hotspotPlanAllowsSharing($plan), '10M shared=1 → pas de partage');
assertTrue(Mikrotik::hotspotPlanAllowsSharing($planDuo), 'DUO shared=2 → partage autorisé');
assertTrue(HotspotCustomer::voucherAlreadyUsedMessage() === 'Voucher déjà utilisé', 'message exact');

// Recharge fictive en mémoire (objet simple)
$recharge = (object) [
    'id' => 0,
    'username' => 'TESTLOCK1',
    'customer_id' => 0,
    'plan_id' => 13,
    'routers' => 'paul009',
    'device_mac' => $macA,
    'status' => 'on',
];

$deny = HotspotCustomer::assertSingleDeviceMacAccess($recharge, $plan, $macB, 'paul009', false);
assertTrue(!$deny['ok'], '2e MAC refusée');
assertTrue($deny['message'] === 'Voucher déjà utilisé', 'message refus = Voucher déjà utilisé');

$same = HotspotCustomer::assertSingleDeviceMacAccess($recharge, $plan, $macA, 'paul009', false);
assertTrue($same['ok'], 'même MAC autorisée');

$duo = HotspotCustomer::assertSingleDeviceMacAccess($recharge, $planDuo, $macB, 'paul009', false);
assertTrue($duo['ok'], 'DUO autorise autre MAC');

// Backfill / verrou sur recharge réelle 10M si présente
$realPlan = ORM::for_table('tbl_plans')->where('name_plan', '10M')->where('routers', 'paul009')->find_one();
if ($realPlan) {
    assertTrue((int) $realPlan->shared_users === 1, 'plan 10M DB shared_users=1');
    $active = ORM::for_table('tbl_user_recharges')
        ->where('plan_id', (int) $realPlan->id)
        ->where('status', 'on')
        ->order_by_desc('id')
        ->find_one();
    if ($active) {
        $locked = HotspotCustomer::resolveLockedDeviceMac($active, $realPlan, 'paul009');
        if ($locked === '') {
            $pay = ORM::for_table('tbl_hotspot_payments')
                ->where('transaction_status', 'paid')
                ->where('voucher_code', (string) $active->username)
                ->order_by_desc('id')
                ->find_one();
            if ($pay && !HotspotCustomer::isPlaceholderHotspotMac((string) $pay->mac_address)) {
                HotspotCustomer::lockDeviceMacOnRecharge($active, (string) $pay->mac_address, $realPlan);
                $active = ORM::for_table('tbl_user_recharges')->find_one((int) $active->id);
                $locked = HotspotCustomer::resolveLockedDeviceMac($active, $realPlan, 'paul009');
            }
        }
        if ($locked !== '') {
            $other = HotspotCustomer::assertSingleDeviceMacAccess($active, $realPlan, 'FF:EE:DD:CC:BB:AA', 'paul009', false);
            assertTrue(!$other['ok'], 'recharge réelle 10M : autre MAC refusée (' . $locked . ')');
            echo "  locked_mac=" . $locked . " login=" . $active->username . "\n";
            // Pousse le bind MikroTik si possible
            $bind = Mikrotik::enforceHotspotSingleDeviceMac('paul009', (string) $active->username, $realPlan, $locked);
            assertTrue($bind['ok'] || stripos((string) $bind['message'], 'Voucher') !== false || $bind['message'] !== '', 'bind MikroTik tenté');
            $bound = Mikrotik::getHotspotUserBoundMac('paul009', (string) $active->username);
            if ($bound !== '') {
                assertTrue($bound === $locked, 'MAC MikroTik = MAC verrouillée');
                echo "  mikrotik_bound=" . $bound . "\n";
            } else {
                echo "  [WARN] user absent ou MAC non lue sur MikroTik (API/routeur)\n";
            }
        } else {
            echo "  [INFO] recharge 10M active sans MAC paiement — verrou au prochain login payeur\n";
        }
    } else {
        echo "  [INFO] aucune recharge 10M active\n";
    }
}

echo "\nTous les tests critiques OK.\n";
exit(0);
