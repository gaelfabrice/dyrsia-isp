<?php
/**
 * Smoke test Finance + ventes (évite les crashes method_exists / as_array).
 * Usage: php scripts/verify-finance-smoke.php
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

function ok(callable $fn, string $label): void
{
    global $failures;
    try {
        $fn();
        echo "OK  $label\n";
    } catch (Throwable $e) {
        $failures++;
        echo "FAIL $label\n      " . get_class($e) . ': ' . $e->getMessage() . "\n      " . $e->getFile() . ':' . $e->getLine() . "\n";
    }
}

$admin = ORM::for_table('tbl_users')->where('user_type', 'Admin')->find_one();
$super = ORM::for_table('tbl_users')->where('user_type', 'SuperAdmin')->find_one();

if (!$admin) {
    echo "WARN: no Admin user — DB integration tests skipped\n";
    exit(0);
}

$adminArr = $admin->as_array();
$adminId = (int) $admin->id;

ok(static function () use ($adminId) {
    Withdrawal::ensureSchema();
    Withdrawal::getProfile($adminId);
    Withdrawal::availableBalance($adminId);
    Withdrawal::salesBreakdown($adminId);
    Withdrawal::sumApproved($adminId);
    Withdrawal::sumBlocked($adminId);
    Withdrawal::requestsForAdmin($adminId);
    Withdrawal::commissionLabel();
    Withdrawal::minAmount();
}, 'finance/withdrawals data paths');

ok(static function () {
    Withdrawal::platformStats();
    Withdrawal::profilesForSuperAdmin(10);
    Withdrawal::pendingForSuperAdmin();
    Withdrawal::allRequestsForSuperAdmin(null, 20);
}, 'finance/reversement (SuperAdmin stats)');

ok(static function () use ($adminArr) {
    $today = date('Y-m-d');
    WifiZoneSales::sumIncomeForDay($adminArr, $today);
    WifiZoneSales::sumIncomeForPeriod($adminArr, date('Y-m-01'), $today);
    WifiZoneSales::sumHotspotPaymentsIncome($adminArr, '1970-01-01', $today, true);
}, 'finance dashboard income (WifiZoneSales)');

ok(static function () use ($adminArr) {
    DashboardCommand::recentPayments($adminArr, 10);
    if (method_exists('DashboardCommand', 'build')) {
        DashboardCommand::build($adminArr);
    }
}, 'dashboard recent payments + build');

ok(static function () use ($adminId) {
    WifiZoneTime::apply(['admin_id' => $adminId]);
    Lang::dateAndTimeFormat('2026-07-26', '08:48:00');
    Lang::dateAndTimeFormat('2026-07-26', '03:05:00');
}, 'timezone display formatting');

if ($super) {
    ok(static function () use ($super) {
        foreach (Withdrawal::searchAdmins('') as $sa) {
            Withdrawal::getProfile((int) $sa->id);
            Withdrawal::availableBalance((int) $sa->id);
        }
    }, 'SuperAdmin search admins + balances');
}

echo $failures === 0 ? "\nFinance smoke: all passed.\n" : "\nFinance smoke: $failures failure(s).\n";
exit($failures > 0 ? 1 : 0);
