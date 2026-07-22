#!/usr/bin/env php
<?php
/**
 * Diagnostic connexion API MikroTik (VPN + login).
 * Usage: php scripts/diagnose-mikrotik.php [router_name]
 */
declare(strict_types=1);

@set_time_limit(0);
chdir(dirname(__DIR__));
require_once 'init.php';

$name = $argv[1] ?? 'HP';
$row = ORM::for_table('tbl_routers')->where('name', $name)->find_one();
if (!$row) {
    fwrite(STDERR, "Routeur « {$name} » introuvable.\n");
    exit(1);
}

$endpoint = Mikrotik::parseEndpoint($row['ip_address']);
$host = $endpoint['host'];
$port = (int) $endpoint['port'];
$pass = Mikrotik::routerPassword($row['password']);

echo "Routeur : {$name}\n";
echo "Endpoint : {$host}:{$port}\n";
echo "Utilisateur : {$row['username']}\n\n";

$wg = trim((string) shell_exec("ifconfig 2>/dev/null | awk '/inet 10\\.0\\.0\\./ {print \$2}' | head -1") ?? '');
echo 'IP VPN locale : ' . ($wg !== '' ? $wg : 'aucune (WireGuard inactif ?)') . "\n";

$probe = Mikrotik::probeApiReachable($row['ip_address'], 8);
echo 'Probe TCP : ' . (is_bool($probe) ? ($probe ? 'OK' : 'échec') : $probe) . "\n";

if ($probe !== true) {
    echo "\n→ Activez WireGuard, attendez 10 s, relancez ce script.\n";
    exit(1);
}

try {
    $client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 30);
    if (!$client) {
        throw new RuntimeException('getClient a retourné null');
    }
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/system/identity/print')) as $r) {
        if ($r->getType() !== 'trap') {
            echo 'Identité : ' . $r->getProperty('name') . "\n";
            break;
        }
    }
    echo "\nAPI MikroTik : OK — vous pouvez consolider (pppoe-setup ou consolidate-pppoe.php).\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\nAPI login : ÉCHEC\n" . $e->getMessage() . "\n");
    exit(1);
}
