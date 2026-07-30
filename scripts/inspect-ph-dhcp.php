#!/usr/bin/env php
<?php
declare(strict_types=1);

chdir(dirname(__DIR__));
require_once 'init.php';

$name = $argv[1] ?? 'PH';
$row = ORM::for_table('tbl_routers')->where('name', $name)->find_one();
if (!$row) {
    fwrite(STDERR, "Routeur « {$name} » introuvable.\n");
    exit(1);
}

$probe = Mikrotik::probeApiReachable($row['ip_address'], 20);
echo "Probe: " . (is_bool($probe) ? ($probe ? 'OK' : 'FAIL') : $probe) . "\n";
if ($probe !== true) {
    exit(2);
}

$pass = Mikrotik::routerPassword($row['password']);
$client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 45);
if (!$client) {
    fwrite(STDERR, "Connexion API impossible.\n");
    exit(3);
}

$sections = [
    'Bridge settings' => '/interface/bridge/settings/print',
    'Bridge ports' => '/interface/bridge/port/print',
    'DHCP servers' => '/ip/dhcp-server/print',
    'DHCP leases (dynamic)' => '/ip/dhcp-server/lease/print',
    'DHCP networks' => '/ip/dhcp-server/network/print',
    'Pools' => '/ip/pool/print',
    'Addresses' => '/ip/address/print',
    'Hotspot' => '/ip/hotspot/print',
    'Wireless' => '/interface/wireless/print',
    'Firewall raw' => '/ip/firewall/raw/print',
];

foreach ($sections as $title => $path) {
    echo "\n=== {$title} ===\n";
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request($path)) as $rowObj) {
        if ($rowObj->getType() === 'trap') {
            continue;
        }
        if ($path === '/ip/dhcp-server/lease/print' && $rowObj->getProperty('dynamic') !== 'true') {
            continue;
        }
        $parts = [];
        foreach ($rowObj->getIterator() as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . $value;
        }
        if ($parts !== []) {
            echo implode(' | ', $parts) . "\n";
        }
    }
}

echo "\n=== INPUT filter (ordered) ===\n";
$n = 0;
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $rowObj) {
    if ($rowObj->getType() === 'trap') {
        continue;
    }
    if ((string) $rowObj->getProperty('chain') !== 'input') {
        continue;
    }
    $n++;
    printf(
        "%d: %s in=%s proto=%s sport=%s dport=%s dst=%s cmt=%s\n",
        $n,
        (string) $rowObj->getProperty('action'),
        (string) $rowObj->getProperty('in-interface'),
        (string) $rowObj->getProperty('protocol'),
        (string) $rowObj->getProperty('src-port'),
        (string) $rowObj->getProperty('dst-port'),
        (string) $rowObj->getProperty('dst-address'),
        (string) $rowObj->getProperty('comment')
    );
}

echo "\n=== DYRSIA hotspot DHCP rules ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $rowObj) {
    if ($rowObj->getType() === 'trap') {
        continue;
    }
    if ((string) $rowObj->getProperty('comment') !== 'DYRSIA hotspot DHCP') {
        continue;
    }
    echo (string) $rowObj->getProperty('chain') . ' in=' . (string) $rowObj->getProperty('in-interface')
        . ' dport=' . (string) $rowObj->getProperty('dst-port') . "\n";
}
