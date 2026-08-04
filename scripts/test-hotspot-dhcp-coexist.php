#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Test réparation DHCP hotspot après PPPoE sur un routeur MikroTik.
 *
 * Usage: php scripts/test-hotspot-dhcp-coexist.php [ROUTER_NAME]
 */
chdir(dirname(__DIR__));
require_once 'init.php';

$routerName = $argv[1] ?? 'PH';
$row = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
if (!$row) {
    fwrite(STDERR, "Routeur « {$routerName} » introuvable.\n");
    exit(1);
}

$probe = Mikrotik::probeApiReachable($row['ip_address'], 20);
echo "=== Routeur {$routerName} ({$row['ip_address']}) ===\n";
echo 'Probe API: ' . (is_bool($probe) ? ($probe ? 'OK' : 'FAIL') : $probe) . "\n";
if ($probe !== true) {
    exit(2);
}

$pass = Mikrotik::routerPassword($row['password']);
$client = Mikrotik::getClient($row['ip_address'], $row['username'], $pass, 25, true, false, 60);
if (!$client) {
    fwrite(STDERR, "Connexion API impossible.\n");
    exit(3);
}

$config = [];
WifiZoneHotspot::loadHotspotConfigForDeploy($config, $routerName);

echo "\n--- Avant réparation ---\n";
printDhcpState($client);

echo "\n--- ensureHotspotDhcpCoexistenceFast ---\n";
$t0 = microtime(true);
$result = Mikrotik::ensureHotspotDhcpCoexistenceFast($client, $config);
$elapsed = round(microtime(true) - $t0, 1);
echo 'ok=' . (!empty($result['ok']) ? 'yes' : 'no') . " ({$elapsed}s)\n";
foreach ($result['actions'] ?? [] as $action) {
    echo '  + ' . $action . "\n";
}
if (!empty($result['errors'])) {
    echo 'ERR: ' . implode(' | ', $result['errors']) . "\n";
}

echo "\n--- Après réparation ---\n";
$checks = printDhcpState($client);
$failed = validateDhcpCoexistence($client, $checks);

echo "\n=== Résultat ===\n";
if ($failed === []) {
    echo "PASS — DHCP hotspot prêt (coexistence PPPoE).\n";
    exit(0);
}

foreach ($failed as $issue) {
    echo "FAIL — {$issue}\n";
}
exit(4);

function printDhcpState($client): array
{
    $checks = [
        'dhcp_server_ok' => false,
        'dhcp_network_ok' => false,
        'hotspot_ok' => false,
        'wlan_enabled' => false,
        'input_dhcp_before_drop' => false,
        'input_dhcp_before_hs_jump' => false,
        'hs_input_dhcp' => false,
        'walled_garden_dhcp' => false,
        'raw_dhcp_rules' => false,
    ];

    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/dhcp-server/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if ((string) $row->getProperty('name') !== 'dyrsia-hotspot-dhcp') {
            continue;
        }
        $invalid = strtolower((string) $row->getProperty('invalid')) === 'true';
        $disabled = strtolower((string) $row->getProperty('disabled')) === 'true';
        $iface = (string) $row->getProperty('interface');
        $pool = (string) $row->getProperty('address-pool');
        echo "DHCP: name=dyrsia-hotspot-dhcp iface={$iface} pool={$pool} invalid="
            . ($invalid ? 'yes' : 'no') . ' disabled=' . ($disabled ? 'yes' : 'no') . "\n";
        $checks['dhcp_server_ok'] = !$invalid && !$disabled && $iface === 'bridge-hotspot' && $pool === 'hs-pool';
    }

    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/dhcp-server/network/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if ((string) $row->getProperty('address') === '10.10.0.0/24') {
            echo 'Network: 10.10.0.0/24 gw=' . (string) $row->getProperty('gateway') . "\n";
            $checks['dhcp_network_ok'] = (string) $row->getProperty('gateway') === '10.10.0.1';
        }
    }

    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/hotspot/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        echo 'Hotspot: ' . (string) $row->getProperty('name') . ' iface=' . (string) $row->getProperty('interface')
            . ' disabled=' . (string) $row->getProperty('disabled') . "\n";
        $hsName = trim((string) $row->getProperty('name'));
        $hsIface = trim((string) $row->getProperty('interface'));
        if ($hsName !== '' && $hsIface !== ''
            && strtolower((string) $row->getProperty('disabled')) !== 'true'
            && $hsIface === 'bridge-hotspot') {
            $checks['hotspot_ok'] = true;
        }
    }

    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/interface/wireless/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        $name = (string) $row->getProperty('name');
        $disabled = strtolower((string) $row->getProperty('disabled')) === 'true';
        echo "WiFi: {$name} disabled=" . ($disabled ? 'yes' : 'no') . ' running=' . (string) $row->getProperty('running') . "\n";
        if ($name === 'wlan1' && !$disabled) {
            $checks['wlan_enabled'] = true;
        }
    }
    try {
        foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/interface/wifi/print')) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $name = (string) $row->getProperty('name');
            $disabled = strtolower((string) $row->getProperty('disabled')) === 'true';
            echo "WiFi(ROS7): {$name} disabled=" . ($disabled ? 'yes' : 'no') . ' running=' . (string) $row->getProperty('running') . "\n";
            if (preg_match('/^wifi\d+$/i', $name) && !$disabled) {
                $checks['wlan_enabled'] = true;
            }
        }
    } catch (Throwable $e) {
    } catch (Exception $e) {
    }

    $inputRules = [];
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if ((string) $row->getProperty('chain') !== 'input') {
            continue;
        }
        $inputRules[] = [
            'action' => (string) $row->getProperty('action'),
            'in' => (string) $row->getProperty('in-interface'),
            'dport' => (string) $row->getProperty('dst-port'),
            'dst' => (string) $row->getProperty('dst-address'),
            'comment' => (string) $row->getProperty('comment'),
        ];
    }

    $firstDrop = null;
    $firstHsJump = null;
    $firstDhcp = null;
    foreach ($inputRules as $i => $rule) {
        $n = $i + 1;
        if ($firstDrop === null && ($rule['action'] === 'drop' || stripos($rule['comment'], 'drop all') !== false)) {
            $firstDrop = $n;
        }
        if ($firstHsJump === null && $rule['action'] === 'jump') {
            $firstHsJump = $n;
        }
        if ($firstDhcp === null && $rule['dport'] === '67'
            && stripos($rule['comment'], 'DYRSIA hotspot DHCP') !== false) {
            $firstDhcp = $n;
        }
    }
    echo "INPUT: first_dhcp_rule=#{$firstDhcp} first_hs_jump=#{$firstHsJump} first_drop=#{$firstDrop}\n";
    if ($firstDhcp !== null && $firstDrop !== null) {
        $checks['input_dhcp_before_drop'] = $firstDhcp < $firstDrop;
    }
    if ($firstDhcp !== null && $firstHsJump !== null) {
        $checks['input_dhcp_before_hs_jump'] = $firstDhcp < $firstHsJump;
    }

    $hsInputDhcp = 0;
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/filter/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if ((string) $row->getProperty('chain') !== 'hs-input') {
            continue;
        }
        if (!in_array((string) $row->getProperty('dst-port'), ['67', '68'], true)) {
            continue;
        }
        if ((string) $row->getProperty('comment') === 'DYRSIA hotspot DHCP hs-input') {
            $hsInputDhcp++;
        }
    }
    echo "hs-input DHCP rules: {$hsInputDhcp}\n";
    $checks['hs_input_dhcp'] = $hsInputDhcp >= 2;

    $wgDhcp = 0;
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/hotspot/walled-garden/ip/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if ((string) $row->getProperty('comment') === 'DYRSIA hotspot DHCP') {
            $wgDhcp++;
        }
    }
    echo "walled-garden DHCP rules: {$wgDhcp}\n";
    $checks['walled_garden_dhcp'] = $wgDhcp >= 2;

    $rawDhcp = 0;
    foreach ($client->sendSync(new PEAR2\Net\RouterOS\Request('/ip/firewall/raw/print')) as $row) {
        if ($row->getType() === 'trap') {
            continue;
        }
        if (strpos((string) $row->getProperty('comment'), 'DYRSIA hotspot DHCP raw') === 0) {
            $rawDhcp++;
        }
    }
    echo "raw prerouting DHCP rules: {$rawDhcp}\n";
    $checks['raw_dhcp_rules'] = $rawDhcp >= 2;

    return $checks;
}

function validateDhcpCoexistence($client, array $checks): array
{
    $failed = [];
    $labels = [
        'dhcp_server_ok' => 'serveur DHCP dyrsia-hotspot-dhcp actif sur bridge-hotspot',
        'dhcp_network_ok' => 'réseau DHCP 10.10.0.0/24 passerelle 10.10.0.1',
        'hotspot_ok' => 'serveur hotspot actif sur bridge-hotspot',
        'wlan_enabled' => 'interface Wi‑Fi hotspot activée (wifi1 ou wlan1)',
        'input_dhcp_before_drop' => 'règles DHCP input avant drop global',
        'input_dhcp_before_hs_jump' => 'règles DHCP input avant jump hotspot',
        'hs_input_dhcp' => 'règles DHCP dans chaîne hs-input',
        'walled_garden_dhcp' => 'walled-garden DHCP udp 67/68',
        'raw_dhcp_rules' => 'raw prerouting DHCP udp 67/68',
    ];
    foreach ($labels as $key => $label) {
        if (empty($checks[$key])) {
            $failed[] = $label;
        }
    }

    return $failed;
}
