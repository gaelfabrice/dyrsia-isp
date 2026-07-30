#!/usr/bin/env php
<?php
declare(strict_types=1);

chdir(dirname(__DIR__));
require_once 'init.php';

$name = $argv[1] ?? 'PH';
$row = ORM::for_table('tbl_routers')->where('name', $name)->find_one();
if (!$row) {
    exit(1);
}

$pass = Mikrotik::routerPassword($row['password']);
$client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 45);
if (!$client) {
    exit(2);
}

echo "=== jump rule #7 ===\n";
$n = 0;
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $r) {
    if ($r->getType() === 'trap') {
        continue;
    }
    if ((string) $r->getProperty('chain') !== 'input') {
        continue;
    }
    $n++;
    if ($n !== 7) {
        continue;
    }
    foreach (['action', 'jump-target', 'comment', 'in-interface'] as $p) {
        echo $p . '=' . (string) $r->getProperty($p) . "\n";
    }
}

echo "\n=== wlan before ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/interface/wireless/print')) as $r) {
    if ($r->getType() === 'trap') {
        continue;
    }
    echo 'running=' . $r->getProperty('running') . ' disabled=' . $r->getProperty('disabled')
        . ' mode=' . $r->getProperty('mode') . ' bridge-mode=' . $r->getProperty('bridge-mode') . "\n";
}

echo "\n=== enable wlan + repair ===\n";
try {
    $client->sendSync((new PEAR2\Net\RouterOS\Request('/interface/enable'))->setArgument('numbers', 'wlan1'));
    $client->sendSync(
        (new PEAR2\Net\RouterOS\Request('/interface/wireless/set'))
            ->setArgument('numbers', 'wlan1')
            ->setArgument('disabled', 'no')
            ->setArgument('mode', 'ap-bridge')
            ->setArgument('bridge-mode', 'disabled')
    );
} catch (Throwable $e) {
    echo 'enable error: ' . $e->getMessage() . "\n";
}

global $config;
$cfg = is_array($config) ? $config : [];
$result = Mikrotik::ensureHotspotCoexistenceAfterPppoe($client, $cfg);
echo 'repair ok=' . ($result['ok'] ? 'yes' : 'no') . "\n";
foreach ($result['actions'] as $a) {
    echo '  - ' . $a . "\n";
}
if (!empty($result['errors'])) {
    echo 'errors: ' . implode(' | ', $result['errors']) . "\n";
}

sleep(2);
echo "\n=== wlan after ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/interface/wireless/print')) as $r) {
    if ($r->getType() === 'trap') {
        continue;
    }
    echo 'running=' . $r->getProperty('running') . ' disabled=' . $r->getProperty('disabled') . "\n";
}

echo "\n=== registration-table ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/interface/wireless/registration-table/print')) as $r) {
    if ($r->getType() === 'trap') {
        continue;
    }
    echo $r->getProperty('mac-address') . ' ' . $r->getProperty('signal-strength') . "\n";
}
