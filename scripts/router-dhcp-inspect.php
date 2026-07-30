<?php
/**
 * Inspect DHCP/firewall state on jasoncross router (10.0.0.6).
 * DELETE THIS FILE AFTER DEBUGGING.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../system/autoload/PEAR2/Autoload.php';
\PEAR2\Autoload::initialize(__DIR__ . '/../system/autoload');

$ip = '10.0.0.6';
$port = 8728;
$user = 'Dyrsia-api';
$pass = 'Dyrsia-api';

try {
    $client = new \PEAR2\Net\RouterOS\Client($ip, $user, $pass, $port, false, 10);

    echo "=== Bridge settings ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/interface/bridge/settings/print'));
    foreach ($resp as $row) {
        echo 'use-ip-firewall: ' . $row->getProperty('use-ip-firewall') . "\n";
        echo 'allow-fast-path: ' . $row->getProperty('allow-fast-path') . "\n";
        echo 'use-ip-firewall-for-vlan: ' . $row->getProperty('use-ip-firewall-for-vlan') . "\n";
    }

    echo "\n=== Bridges ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/interface/bridge/print'));
    foreach ($resp as $row) {
        echo $row->getProperty('name') . ' fast-forward=' . $row->getProperty('fast-forward') . "\n";
    }

    echo "\n=== DHCP servers ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/ip/dhcp-server/print'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo 'name=' . $row->getProperty('name') . ' interface=' . $row->getProperty('interface') . ' pool=' . $row->getProperty('address-pool') . ' disabled=' . $row->getProperty('disabled') . "\n";
    }

    echo "\n=== IP Pools ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/ip/pool/print'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo 'name=' . $row->getProperty('name') . ' ranges=' . $row->getProperty('ranges') . "\n";
    }

    echo "\n=== DHCP networks ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/ip/dhcp-server/network/print'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo 'address=' . $row->getProperty('address') . ' gateway=' . $row->getProperty('gateway') . ' dns=' . $row->getProperty('dns-server') . "\n";
    }

    echo "\n=== Firewall filter rules ===\n";
    $resp = $client->sendSync((new \PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print'))->setArgument('.proplist', '.id,chain,protocol,dst-port,action,in-interface,comment'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo '#' . $row->getProperty('.id') . ' chain=' . $row->getProperty('chain') . ' proto=' . $row->getProperty('protocol') . ' dst-port=' . $row->getProperty('dst-port') . ' action=' . $row->getProperty('action') . ' in-if=' . $row->getProperty('in-interface') . ' comment=' . $row->getProperty('comment') . "\n";
    }

    echo "\n=== Hotspot servers ===\n";
    $resp = $client->sendSync(new \PEAR2\Net\RouterOS\Request('/ip/hotspot/print'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo 'name=' . $row->getProperty('name') . ' interface=' . $row->getProperty('interface') . ' profile=' . $row->getProperty('profile') . ' address-pool=' . $row->getProperty('address-pool') . ' disabled=' . $row->getProperty('disabled') . "\n";
    }

    echo "\n=== Walled-garden IP ===\n";
    $resp = $client->sendSync((new \PEAR2\Net\RouterOS\Request('/ip/hotspot/walled-garden/ip/print'))->setArgument('.proplist', 'protocol,dst-port,action,comment'));
    foreach ($resp as $row) {
        if ($row->getType() === 'trap') continue;
        echo 'proto=' . $row->getProperty('protocol') . ' dst-port=' . $row->getProperty('dst-port') . ' action=' . $row->getProperty('action') . ' comment=' . $row->getProperty('comment') . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
