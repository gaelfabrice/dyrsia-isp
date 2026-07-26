<?php
/**
 * Vérifie l'absence de crash method_exists(array) sur Retraits / ventes dédupliquées.
 * Usage: php scripts/verify-withdrawal-crash.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
putenv('APP_STAGE=Dev');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=wifizones');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');

require $root . '/init.php';

$failures = 0;

function assert_true(bool $cond, string $msg): void
{
    global $failures;
    if ($cond) {
        echo "OK  $msg\n";
        return;
    }
    $failures++;
    echo "FAIL $msg\n";
}

function assert_no_throw(callable $fn, string $msg): void
{
    global $failures;
    try {
        $fn();
        echo "OK  $msg\n";
    } catch (Throwable $e) {
        $failures++;
        echo "FAIL $msg — " . $e->getMessage() . "\n";
    }
}

// --- Unit: normalizeSaleRow + dedupe avec tableaux (repro crash prod) ---
$sampleRows = [
    [
        'id' => 1,
        'type' => 'Hotspot',
        'method' => 'CamPay - Orange',
        'price' => 500,
        'note' => 'hotspot_payment:42',
        'username' => 'u1',
        'plan_name' => '4M',
        'routers' => 'HP',
        'recharged_on' => '2026-07-26',
        'recharged_time' => '03:05:00',
    ],
    [
        'id' => 2,
        'type' => 'PPPOE',
        'method' => 'CamPay - CamPay',
        'price' => 1000,
        'note' => '',
        'username' => 'u2',
        'plan_name' => '12M',
        'routers' => 'R1',
        'recharged_on' => '2026-07-26',
        'recharged_time' => '03:05:00',
    ],
    [
        'id' => 3,
        'type' => 'Hotspot',
        'method' => 'CamPay - Orange',
        'price' => 500,
        'note' => 'hotspot_payment:42',
        'username' => 'u1',
        'plan_name' => '4M',
        'routers' => 'HP',
        'recharged_on' => '2026-07-26',
        'recharged_time' => '03:05:00',
    ],
];

assert_no_throw(static function () use ($sampleRows) {
    $deduped = WifiZoneSales::dedupeSaleRows($sampleRows);
    foreach ($deduped as $row) {
        WifiZoneSales::normalizeSaleRow($row);
        Withdrawal::isWithdrawableSale($row);
        WifiZoneSales::rowSaleAmount($row);
    }
}, 'dedupeSaleRows + normalizeSaleRow + isWithdrawableSale on arrays');

assert_true(count(WifiZoneSales::dedupeSaleRows($sampleRows)) === 2, 'dedupe removes duplicate hotspot_payment:42');

// --- Integration DB (si disponible) ---
assert_no_throw(static function () {
    $admin = ORM::for_table('tbl_users')->where('user_type', 'Admin')->find_one();
    if (!$admin) {
        echo "SKIP DB: no Admin user\n";
        return;
    }
    $adminId = (int) $admin->id;
    Withdrawal::ensureSchema();
    $sales = Withdrawal::salesBreakdown($adminId);
    assert(is_array($sales) && isset($sales['gross'], $sales['net'], $sales['commission']));
    $balance = Withdrawal::availableBalance($adminId);
    assert(is_float($balance) || is_int($balance));
    $stats = Withdrawal::platformStats();
    assert(is_array($stats));
}, 'Withdrawal::salesBreakdown + availableBalance + platformStats (DB)');

assert_no_throw(static function () {
    $admin = ORM::for_table('tbl_users')->where_in('user_type', ['Admin', 'SuperAdmin'])->find_one();
    if (!$admin) {
        echo "SKIP DB: no admin for dashboard\n";
        return;
    }
    $adminArr = $admin->as_array();
    DashboardCommand::recentPayments($adminArr, 5);
}, 'DashboardCommand::recentPayments after dedupe (DB)');

assert_no_throw(static function () {
    $formatted = Lang::dateAndTimeFormat('2026-07-26', '03:05:00', ['admin_id' => 1]);
    assert($formatted !== '');
}, 'Lang::dateAndTimeFormat with WifiZoneTime');

assert_no_throw(static function () {
    $row = [
        'status' => 'on',
        'expiration' => '2026-07-26',
        'time' => '03:15:00',
        'admin_id' => 1,
    ];
    Package::isRechargeExpired($row);
    Package::rechargeExpiresAt($row);
}, 'Package expiry helpers on array recharge');

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) failed.\n";
exit($failures > 0 ? 1 : 0);
