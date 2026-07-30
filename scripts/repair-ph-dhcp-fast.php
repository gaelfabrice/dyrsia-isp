#!/usr/bin/env php
<?php
declare(strict_types=1);

chdir(dirname(__DIR__));
require_once 'init.php';

$name = $argv[1] ?? 'PH';
$row = ORM::for_table('tbl_routers')->where('name', $name)->find_one();
if (!$row) {
    fwrite(STDERR, "Routeur introuvable.\n");
    exit(1);
}

$pass = Mikrotik::routerPassword($row['password']);
$client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 45);
if (!$client) {
    fwrite(STDERR, "API impossible.\n");
    exit(2);
}

$config = [];
WifiZoneHotspot::loadHotspotConfigForDeploy($config, $name);

$result = Mikrotik::ensureHotspotDhcpCoexistenceEssential($client, $config);
$actions = $result['actions'] ?? [];
$errors = $result['errors'] ?? [];

echo 'ok=' . (empty($errors) ? 'yes' : 'no') . "\n";
foreach ($actions as $a) {
    echo '  ' . $a . "\n";
}
if ($errors !== []) {
    echo 'ERR: ' . implode(' | ', $errors) . "\n";
}

echo "\n=== INPUT top rules ===\n";
$n = 0;
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $r) {
    if ($r->getType() === 'trap') {
        continue;
    }
    if ((string) $r->getProperty('chain') !== 'input') {
        continue;
    }
    $n++;
    if ($n > 14) {
        break;
    }
    echo $n . ': ' . $r->getProperty('action') . ' in=' . $r->getProperty('in-interface')
        . ' dport=' . $r->getProperty('dst-port') . ' dst=' . $r->getProperty('dst-address')
        . ' cmt=' . $r->getProperty('comment') . "\n";
}
