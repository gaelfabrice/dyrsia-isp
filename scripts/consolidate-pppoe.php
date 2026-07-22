#!/usr/bin/env php
<?php
/**
 * Consolidation PPPoE : infra + profils + clients + NAT/DNS/firewall.
 * Usage: php scripts/consolidate-pppoe.php [router_name]
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

@set_time_limit(0);
@ini_set('max_execution_time', '0');
chdir(dirname(__DIR__));
require_once 'init.php';

global $config, $admin;

$routerName = $argv[1] ?? trim((string) ($config['pppoe_setup_router'] ?? ''));
if ($routerName === '') {
    fwrite(STDERR, "Usage: php scripts/consolidate-pppoe.php ROUTER_NAME\n");
    exit(1);
}

$config['pppoe_setup_router'] = $routerName;
$config = Mikrotik::normalizePppoeSetupConfig($config);

$router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
if (!$router) {
    fwrite(STDERR, "Routeur introuvable: {$routerName}\n");
    exit(1);
}

echo "Consolidation PPPoE → {$routerName} ({$router['ip_address']})…\n";

$probe = Mikrotik::probeApiReachable($router['ip_address'], 10);
if ($probe !== true) {
    fwrite(STDERR, "Routeur injoignable: {$probe}\n");
    fwrite(STDERR, "→ Activez WireGuard (IP 10.0.0.2), puis : php scripts/diagnose-mikrotik.php {$routerName}\n");
    exit(1);
}

$started = microtime(true);
try {
    $result = Mikrotik::deployPppoeComplete(null, $config, $router->as_array(), $admin ?? null);
} catch (Throwable $e) {
    fwrite(STDERR, 'ÉCHEC : ' . $e->getMessage() . "\n");
    exit(1);
}
$elapsed = round(microtime(true) - $started, 1);

if (!empty($result['actions'])) {
    foreach ($result['actions'] as $action) {
        echo "  · {$action}\n";
    }
}

if (empty($result['ok'])) {
    fwrite(STDERR, "ÉCHEC ({$elapsed}s): " . implode(' | ', $result['errors'] ?? ['inconnu']) . "\n");
    exit(1);
}

echo "OK ({$elapsed}s) — infrastructure + forfaits synchronisés.\n";
exit(0);
