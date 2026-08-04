#!/usr/bin/env php
<?php
declare(strict_types=1);
chdir(dirname(__DIR__));
require_once 'init.php';

$row = ORM::for_table('tbl_routers')->where('name', 'paul009')->find_one();
if (!$row) {
    fwrite(STDERR, "paul009 not found\n");
    exit(1);
}
$config = [];
WifiZoneHotspot::loadHotspotConfigForDeploy($config, 'paul009');
Mikrotik::mergePppoeSetupConfigForIsolation($config);

echo "Config DB isolation:\n";
echo '  hotspot: ' . ($config['hotspot_bridge_ports'] ?? '') . "\n";
echo '  pppoe: ' . ($config['pppoe_setup_bridge_ports'] ?? '') . "\n";
echo '  hotspot conflict: ' . (Mikrotik::resolveHotspotIsolationConflict($config, 'paul009') ?: '(none)') . "\n";
echo '  pppoe conflict: ' . (Mikrotik::resolvePppoeIsolationConflict($config, 'paul009') ?: '(none)') . "\n";

$pass = Mikrotik::routerPassword($row->password);
$client = Mikrotik::getClient($row->ip_address, $row->username, $pass, 25, true, false, 60);
if (!$client) {
    fwrite(STDERR, "API connect failed\n");
    exit(2);
}

$ref = new ReflectionClass('Mikrotik');
$m1 = $ref->getMethod('detectRuntimeHotspotPortConflict');
$m1->setAccessible(true);
$m2 = $ref->getMethod('detectRuntimePppoePortConflict');
$m2->setAccessible(true);
$pppoePorts = Mikrotik::parseInterfacePortsList($config['pppoe_setup_bridge_ports'] ?? '');
$hotspotPorts = Mikrotik::resolveHotspotBridgePorts($config);

echo "\nRuntime checks:\n";
echo '  pppoe deploy block: ' . ($m1->invoke(null, $client, $pppoePorts, $config) ?: '(none)') . "\n";
echo '  hotspot deploy block: ' . ($m2->invoke(null, $client, $hotspotPorts, $config) ?: '(none)') . "\n";

$snap = Mikrotik::fetchPppoeSetupSnapshot($client, true);
echo "\nBridge ports on router:\n";
foreach ($snap['bridge_ports'] ?? [] as $b => $ports) {
    echo "  {$b}: " . implode(', ', $ports) . "\n";
}
echo 'Suggested hotspot ports: ' . ($snap['suggested']['hotspot_bridge_ports'] ?? '') . "\n";
echo 'Suggested pppoe ports: ' . ($snap['suggested']['pppoe_setup_bridge_ports'] ?? '') . "\n";
