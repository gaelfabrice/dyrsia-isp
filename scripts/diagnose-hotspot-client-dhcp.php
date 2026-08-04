#!/usr/bin/env php
<?php
declare(strict_types=1);

chdir(dirname(__DIR__));
require_once 'init.php';

$routerName = $argv[1] ?? 'paul009';
$row = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
if (!$row) {
    fwrite(STDERR, "Routeur « {$routerName} » introuvable.\n");
    exit(1);
}

$probe = Mikrotik::probeApiReachable($row['ip_address'], 20);
if ($probe !== true) {
    fwrite(STDERR, "API unreachable: " . var_export($probe, true) . "\n");
    exit(2);
}

$pass = Mikrotik::routerPassword($row['password']);
$client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 90);
if (!$client) {
    exit(3);
}

function rosPrint($client, string $path, string $proplist = ''): void
{
    echo "\n=== {$path} ===\n";
    $req = new PEAR2\Net\RouterOS\Request($path);
    if ($proplist !== '') {
        $req->setArgument('.proplist', $proplist);
    }
    foreach ($client->sendSync($req) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        $parts = [];
        foreach ($row->getIterator() as $k => $v) {
            if ($k === '.id' || $v === '' || $v === null) {
                continue;
            }
            $parts[] = $k . '=' . $v;
        }
        echo implode(' ', $parts) . "\n";
    }
}

echo "Routeur: {$routerName} ({$row['ip_address']})\n";

rosPrint($client, '/interface/bridge/print', 'name,fast-forward,disabled');
rosPrint($client, '/interface/bridge/port/print', 'bridge,interface,hw,disabled');
rosPrint($client, '/interface/bridge/settings/print');
rosPrint($client, '/interface/wifi/print', 'name,disabled,running,configuration,master-interface');
rosPrint($client, '/interface/wifi/datapath/print', 'name,bridge,disabled');
rosPrint($client, '/ip/address/print', 'address,interface,disabled');
rosPrint($client, '/ip/pool/print', 'name,ranges,used');
rosPrint($client, '/ip/dhcp-server/print', 'name,interface,address-pool,disabled,invalid,dynamic');
rosPrint($client, '/ip/dhcp-server/network/print', 'address,gateway,dns-server');
rosPrint($client, '/ip/dhcp-server/lease/print', 'address,mac-address,server,status,expires-after,host-name');
rosPrint($client, '/ip/hotspot/print', 'name,interface,address-pool,profile,disabled');
rosPrint($client, '/ip/hotspot/host/print', 'mac-address,address,server,authorized,found-by');
rosPrint($client, '/ip/hotspot/active/print', 'user,address,mac-address,server');

echo "\n=== input filter (dhcp + hotspot jump) ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $row) {
    if ($row->getType() === 'trap') {
        continue;
    }
    $chain = (string) $row->getProperty('chain');
    if (!in_array($chain, ['input', 'forward', 'hs-unauth', 'hs-input'], true)) {
        continue;
    }
    $action = (string) $row->getProperty('action');
    $comment = (string) $row->getProperty('comment');
    $dport = (string) $row->getProperty('dst-port');
    $in = (string) $row->getProperty('in-interface');
    if ($chain === 'input' && $action === 'jump') {
        echo "# input jump dst={$row->getProperty('dst-address')} in={$in} -> {$row->getProperty('jump-target')}\n";
    }
    if (strpos($comment, 'DYRSIA hotspot DHCP') === 0 || $action === 'jump' || ($dport === '67' || $dport === '68')) {
        echo "{$chain} {$action} in={$in} dport={$dport} comment={$comment}\n";
    }
}

echo "\n=== /ip service (dhcp) ===\n";
foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/service/print')) as $row) {
    if ($row->getType() === 'trap') {
        continue;
    }
    $name = (string) $row->getProperty('name');
    if ($name === 'dhcp' || $name === 'bootp') {
        echo $name . ' disabled=' . (string) $row->getProperty('disabled') . "\n";
    }
}
