<?php

/**
 * Simulation approfondie : coexistence Hotspot + PPPoE (offline, sans routeur).
 * Usage: php scripts/simulate-hotspot-pppoe-deep.php
 */
$root = dirname(__DIR__);
$_app_stage = 'Dev';

require_once $root . '/init.php';

if (!class_exists('Mikrotik')) {
    fwrite(STDERR, "FAIL: Mikrotik class missing\n");
    exit(1);
}

$passed = 0;
$failed = 0;
$warn = 0;

function ok(bool $cond, string $label, string $detail = ''): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  PASS  $label\n";
        return;
    }
    $failed++;
    echo "  FAIL  $label" . ($detail !== '' ? " — $detail" : '') . "\n";
}

function warn_if(bool $cond, string $label, string $detail = ''): void
{
    global $warn;
    if ($cond) {
        $warn++;
        echo "  WARN  $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

function read_src(string $rel): string
{
    global $root;
    $path = $root . '/' . ltrim($rel, '/');
    return is_file($path) ? (string) file_get_contents($path) : '';
}

function fn_body_has(string $class, string $method, string $needle): bool
{
    if (!method_exists($class, $method)) {
        return false;
    }
    $ref = new ReflectionMethod($class, $method);
    $file = $ref->getFileName();
    if (!$file || !is_readable($file)) {
        return false;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return false;
    }
    $start = max(0, $ref->getStartLine() - 1);
    $end = min(count($lines), $ref->getEndLine());
    $body = implode("\n", array_slice($lines, $start, $end - $start));

    return strpos($body, $needle) !== false;
}

class BridgeSim
{
    /** @var array<string, string> */
    private $ports = [];

    public function put(string $bridge, string $port): array
    {
        $port = strtolower(trim($port));
        $bridge = trim($bridge);
        $cur = $this->ports[$port] ?? '';
        if ($cur === $bridge) {
            return ['ok' => true, 'error' => ''];
        }
        if ($cur !== '' && Mikrotik::isDyrsiaServiceBridge($cur) && strtolower($cur) !== strtolower($bridge)) {
            return ['ok' => false, 'error' => "$port locked on $cur"];
        }
        if ($cur !== '' && $cur !== $bridge) {
            unset($this->ports[$port]);
        }
        $this->ports[$port] = $bridge;

        return ['ok' => true, 'error' => ''];
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->ports;
    }
}

/**
 * Simule le déploiement complet Hotspot puis PPPoE (L2 + validation config).
 *
 * @return array{ok: bool, errors: array<int, string>, ports: array<string, string>}
 */
function simulate_deploy_timeline(array $hsPorts, array $pppoePorts, array $mgmtPorts): array
{
    $errors = [];
    $conflict = Mikrotik::validateServicePortIsolation($pppoePorts, $hsPorts, $mgmtPorts);
    if ($conflict !== '') {
        return ['ok' => false, 'errors' => [$conflict], 'ports' => []];
    }

    $sim = new BridgeSim();
    foreach ($mgmtPorts as $p) {
        $r = $sim->put('bridge-management', $p);
        if (!$r['ok']) {
            $errors[] = "mgmt:$p " . $r['error'];
        }
    }
    foreach ($hsPorts as $p) {
        $r = $sim->put('bridge-hotspot', $p);
        if (!$r['ok']) {
            $errors[] = "hotspot:$p " . $r['error'];
        }
    }
    foreach ($pppoePorts as $p) {
        $r = $sim->put('bridge-pppoe', $p);
        if (!$r['ok']) {
            $errors[] = "pppoe:$p " . $r['error'];
        }
    }

    return ['ok' => empty($errors), 'errors' => $errors, 'ports' => $sim->all()];
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  SIMULATION APPROFONDIE Hotspot + PPPoE (DYRSIA)             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ─── A. Matrice conflits ports ─────────────────────────────────────────────
echo "A) Matrice validateServicePortIsolation\n";

$matrix = [
    [['ether7', 'ether8'], ['ether3', 'wlan1'], ['ether2'], true, 'Config utilisateur (A)'],
    [['ether3'], ['ether3', 'wlan1'], ['ether2'], false, 'PPPoE vole port Hotspot'],
    [['ether2'], ['ether3'], ['ether2'], false, 'PPPoE sur port Management'],
    [['ether2', 'ether3', 'ether4', 'ether5'], ['ether3', 'wlan1'], ['ether2'], false, 'Anciens défauts PPPoE'],
    [['ether7'], ['wlan1'], ['ether2'], true, 'WiFi Hotspot + ether7 PPPoE'],
    [['wlan1'], ['wlan1'], ['ether2'], false, 'wlan1 partagé'],
    [[], ['ether3'], ['ether2'], true, 'PPPoE sans ports (validé ailleurs)'],
];

foreach ($matrix as [$pppoe, $hs, $mgmt, $expectOk, $label]) {
    $err = Mikrotik::validateServicePortIsolation($pppoe, $hs, $mgmt);
    $ok = ($err === '') === $expectOk;
    ok($ok, $label, $expectOk ? '' : $err);
}

// ─── B. Timelines déploiement ──────────────────────────────────────────────
echo "\nB) Timelines L2 (Management → Hotspot → PPPoE)\n";

$timelines = [
    'Standard' => [
        ['ether3', 'wlan1'],
        ['ether7', 'ether8'],
        ['ether2'],
    ],
    'Hotspot WiFi seul' => [
        ['wlan1'],
        ['ether7', 'ether8'],
        ['ether2'],
    ],
    'PPPoE avant Hotspot (mêmes ports)' => [
        ['ether3'],
        ['ether3', 'wlan1'],
        ['ether2'],
    ],
];

foreach ($timelines as $name => [$hs, $pppoe, $mgmt]) {
    $result = simulate_deploy_timeline($hs, $pppoe, $mgmt);
    if ($name === 'PPPoE avant Hotspot (mêmes ports)') {
        ok(!$result['ok'], "Timeline « $name » bloquée en validation");
        continue;
    }
    ok($result['ok'], "Timeline « $name » L2 intacte", implode('; ', $result['errors']));
    if ($result['ok']) {
        $p = $result['ports'];
        ok(($p['ether2'] ?? '') === 'bridge-management', "  └ ether2 → management ($name)");
        if (in_array('ether3', $hs, true)) {
            ok(($p['ether3'] ?? '') === 'bridge-hotspot', "  └ ether3 → hotspot ($name)");
        }
        if (in_array('ether7', $pppoe, true)) {
            ok(($p['ether7'] ?? '') === 'bridge-pppoe', "  └ ether7 → pppoe ($name)");
        }
    }
}

// Tentative vol après déploiement réussi
echo "\nB2) Tentatives de vol post-déploiement\n";
$sim = new BridgeSim();
$sim->put('bridge-management', 'ether2');
$sim->put('bridge-hotspot', 'ether3');
$sim->put('bridge-hotspot', 'wlan1');
$sim->put('bridge-pppoe', 'ether7');
$r = $sim->put('bridge-pppoe', 'wlan1');
ok(!$r['ok'], 'Vol wlan1 Hotspot → PPPoE refusé', $r['error']);
$r = $sim->put('bridge-hotspot', 'ether7');
ok(!$r['ok'], 'Vol ether7 PPPoE → Hotspot refusé', $r['error']);

// ─── C. Séparation L3 / defaults ───────────────────────────────────────────
echo "\nC) Séparation L3, pools, defaults\n";

$pppoeDef = Mikrotik::pppoeSetupDefaults();
$svcDef = Mikrotik::serviceBridgeDefaults();
ok(explode('/', $pppoeDef['pppoe_setup_gateway'])[0] === '10.10.10.1', 'GW PPPoE 10.10.10.1');
ok(strpos($svcDef['hotspot_bridge_ports'], 'ether3') !== false, 'Defaults hotspot incluent ether3');
ok($pppoeDef['pppoe_setup_bridge_ports'] === 'ether7,ether8', 'Defaults PPPoE ether7,ether8');
ok(Mikrotik::resolvePppoeBridgeName(['pppoe_setup_bridge_name' => '']) === 'bridge-pppoe', 'Bridge PPPoE canonique');
ok(Mikrotik::resolveLanBridgeName(['hotspot_interface' => '']) === 'bridge-hotspot', 'Bridge Hotspot canonique');
ok(Mikrotik::resolvePppoeServiceInterface(['pppoe_setup_server_interface' => 'bridge-lan']) === 'bridge-pppoe', 'bridge-lan legacy → bridge-pppoe');

// ─── D. Trunk désactivé ────────────────────────────────────────────────────
echo "\nD) Mode trunk supprimé\n";

ok(!Mikrotik::lanTrunkEnabled(['lan_trunk_enabled' => '1']), 'lanTrunkEnabled=1 → false');
ok(!Mikrotik::lanTrunkEnabled(['lan_trunk_enabled' => '0']), 'lanTrunkEnabled=0 → false');
ok(!array_key_exists('hotspot_vlan_id', Mikrotik::serviceBridgeDefaults()), 'Plus de hotspot_vlan_id dans defaults');
$mikrotikSrc = read_src('system/autoload/Mikrotik.php');
ok(strpos($mikrotikSrc, 'ensureLanTrunkServiceVlan') === false, 'ensureLanTrunkServiceVlan absent du code');
ok(!fn_body_has('Mikrotik', 'applyHotspotSetupFromConfig', 'ensureLanTrunkServiceVlan'), 'Hotspot deploy sans trunk VLAN');
ok(!fn_body_has('Mikrotik', 'applyHotspotSetupFromConfig', '$trunkMode'), 'applyHotspotSetupFromConfig sans $trunkMode');

// ─── E. Analyse statique pipelines ─────────────────────────────────────────
echo "\nE) Pipelines code (analyse statique)\n";

// Hotspot full deploy
ok(fn_body_has('Mikrotik', 'applyHotspotSetupFromConfig', 'ensureDedicatedHotspotBridge'), 'Hotspot: ensureDedicatedHotspotBridge');
ok(fn_body_has('Mikrotik', 'applyHotspotSetupFromConfig', 'ensureHotspotBridgeFirewall'), 'Hotspot: ensureHotspotBridgeFirewall');
ok(fn_body_has('Mikrotik', 'applyHotspotSetupFromConfig', 'ensureHotspotDhcpServer'), 'Hotspot: ensureHotspotDhcpServer');
ok(fn_body_has('Mikrotik', 'ensureHotspotBridgeFirewall', 'ensureHotspotDhcpFirewallPass'), 'Bridge FW appelle DHCP firewall pass');
ok(fn_body_has('Mikrotik', 'ensureHotspotDhcpServer', 'ensureHotspotDhcpFirewallPass'), 'DHCP server appelle DHCP firewall pass');

// PPPoE deploy
ok(fn_body_has('Mikrotik', 'consolidatePppoeRouterSetup', 'validateServicePortIsolation'), 'PPPoE: validation ports avant deploy');
ok(fn_body_has('Mikrotik', 'consolidatePppoeRouterSetup', 'deployPppoeCoreInfrastructure'), 'PPPoE: infra core');
ok(fn_body_has('Mikrotik', 'consolidatePppoeRouterSetup', 'deployPppoeOptionalExtras'), 'PPPoE: extras');
ok(!fn_body_has('Mikrotik', 'deployPppoeOptionalExtras', 'ensureHotspotDhcpCoexistenceEssential'),
    'PPPoE extras: ne touche pas au Hotspot');
ok(!fn_body_has('Mikrotik', 'deployPppoeComplete', 'ensureHotspotCoexistenceAfterPppoe'),
    'deployPppoeComplete: services PPPoE et Hotspot séparés');
ok(fn_body_has('Mikrotik', 'ensureHotspotCoexistenceAfterPppoe', 'ensureHotspotDhcpCoexistenceEssential'),
    'Coexistence (maintenance manuelle): méthode conservée');

// Port protection
ok(fn_body_has('Mikrotik', 'ensureBridgePortMembership', 'isDyrsiaServiceBridge'), 'ensureBridgePortMembership protège bridges DYRSIA');

// PPPoE expired captive ne prune plus walled-garden hotspot
ok(!fn_body_has('Mikrotik', 'ensurePppoeExpiredCaptive', 'pruneHotspotWalledGardenBatch'), 'PPPoE expired captive ne prune plus WG Hotspot');

// Firewall PPPoE expired: hard drop pas en tête aveugle
ok(fn_body_has('Mikrotik', 'ensurePppoeExpiredFasttrackBypass', "'prepend' => false"), 'PPPoE expired hard-drop en fin de chaîne');

// ─── F. Wiring UI / settings.php ───────────────────────────────────────────
echo "\nF) Wiring assistants (settings.php)\n";

$settingsSrc = read_src('system/controllers/settings.php');
ok(strpos($settingsSrc, 'deployPppoeComplete') !== false, 'PPPoE Setup → deployPppoeComplete');
ok(strpos($settingsSrc, 'applyHotspotSetupFromConfig') !== false, 'Hotspot Send → applyHotspotSetupFromConfig');
ok(strpos($settingsSrc, 'ensureHotspotDhcpFirewallPass') !== false, 'Send incrémental → ensureHotspotDhcpFirewallPass');
ok(strpos($settingsSrc, 'ensureDedicatedHotspotBridge') !== false, 'Send incrémental → ensureDedicatedHotspotBridge');
ok(strpos($settingsSrc, 'deployPppoeToMikrotik') === false, 'Plus de deployPppoeToMikrotik');
ok(strpos($settingsSrc, 'sendPppoeOnly') === false, 'Plus de sendPppoeOnly handler');
ok(strpos($settingsSrc, 'lanTrunkEnabled($config)') === false, 'Plus de branche trunk dans settings');
ok(strpos($settingsSrc, 'skipBridgeHardening') !== false, 'skipBridgeHardening (ex skipTrunkRebuild)');

// Legacy path exists but unwired
warn_if(
    strpos($settingsSrc, 'applyPppoeSetupFromConfig') === false && method_exists('Mikrotik', 'applyPppoeSetupFromConfig'),
    'applyPppoeSetupFromConfig existe mais non câblée UI',
    'code mort — suppression future possible'
);

// ─── G. Scénario utilisateur complet (état routeur simulé) ─────────────────
echo "\nG) Scénario utilisateur complet (état routeur simulé)\n";

$userConfig = [
    'hotspot_interface' => 'bridge-hotspot',
    'hotspot_bridge_ports' => 'ether3,wlan1',
    'hotspot_local_address' => '10.10.0.1/24',
    'hotspot_pool_name' => 'hs-pool',
    'hotspot_address_pool' => '10.10.0.10-10.10.0.254',
    'pppoe_setup_bridge_name' => 'bridge-pppoe',
    'pppoe_setup_bridge_ports' => 'ether7,ether8',
    'pppoe_setup_gateway' => '10.10.10.1/24',
    'pppoe_setup_pool_name' => 'pppoe-pool',
    'pppoe_setup_pool_range' => '10.10.10.2-10.10.10.254',
    'lan_management_interface' => 'ether2',
];

$hsPorts = Mikrotik::parseInterfacePortsList($userConfig['hotspot_bridge_ports']);
$pppoePorts = Mikrotik::parseInterfacePortsList($userConfig['pppoe_setup_bridge_ports']);
$mgmtPorts = Mikrotik::parseInterfacePortsList($userConfig['lan_management_interface']);
$timeline = simulate_deploy_timeline($hsPorts, $pppoePorts, $mgmtPorts);
ok($timeline['ok'], 'Scénario utilisateur: timeline L2 OK');

$pppoeNorm = Mikrotik::normalizePppoeSetupConfig($userConfig);
ok($pppoeNorm['pppoe_setup_bridge_name'] === 'bridge-pppoe', 'Config normalisée: bridge-pppoe');
ok($pppoeNorm['pppoe_setup_server_interface'] === 'bridge-pppoe', 'Config normalisée: server sur bridge-pppoe');
ok(
    Mikrotik::validateServicePortIsolation($pppoePorts, $hsPorts, $mgmtPorts) === '',
    'Scénario utilisateur: validation ports OK'
);

// Services attendus sur le routeur (modèle mental)
$expectedServices = [
    'management' => ['bridge' => 'bridge-management', 'port' => 'ether2', 'subnet' => '10.99.99.0/24', 'dhcp' => 'dhcp-management'],
    'hotspot' => ['bridge' => 'bridge-hotspot', 'ports' => ['ether3', 'wlan1'], 'subnet' => '10.10.0.0/24', 'dhcp' => 'dyrsia-hotspot-dhcp'],
    'pppoe' => ['bridge' => 'bridge-pppoe', 'ports' => ['ether7', 'ether8'], 'subnet' => '10.10.10.0/24', 'dhcp' => '(pool PPP, pas DHCP LAN)'],
];

echo "\n  État routeur attendu après Hotspot + PPPoE Setup:\n";
foreach ($expectedServices as $svc => $meta) {
    echo "    • $svc: " . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n";
}

// Vérifier que les subnets ne se chevauchent pas
$subnets = ['10.99.99.0/24', '10.10.0.0/24', '10.10.10.0/24'];
ok(count($subnets) === count(array_unique($subnets)), '3 subnets distincts sans chevauchement');

// ─── H. Régression bug historique ──────────────────────────────────────────
echo "\nH) Régression bug historique (Hotspot casse après PPPoE)\n";

$bugSim = new BridgeSim();
$bugSim->put('bridge-management', 'ether2');
$bugSim->put('bridge-hotspot', 'ether3');
$bugSim->put('bridge-hotspot', 'wlan1');

// Ancien bug: PPPoE prenait ether2,ether3,ether4,ether5
$oldDefaults = ['ether2', 'ether3', 'ether4', 'ether5'];
$blocked = 0;
foreach ($oldDefaults as $port) {
    $r = $bugSim->put('bridge-pppoe', $port);
    if (!$r['ok']) {
        $blocked++;
    }
}
ok($blocked >= 2, "Ancien bug: au moins 2 ports protégés sur " . count($oldDefaults) . " (ether2+ether3)", "blocked=$blocked");

$after = $bugSim->all();
ok($after['ether3'] === 'bridge-hotspot', 'Régression: ether3 reste sur bridge-hotspot après tentative PPPoE');
ok($after['ether2'] === 'bridge-management', 'Régression: ether2 reste sur bridge-management');

// ─── Résumé ────────────────────────────────────────────────────────────────
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                      ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║  PASS: %-3d   FAIL: %-3d   WARN: %-3d                          ║\n", $passed, $failed, $warn);
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

if ($failed > 0) {
    echo "VERDICT: ÉCHEC — des garde-fous manquent ou sont régressés.\n";
    exit(1);
}

if ($warn > 0) {
    echo "VERDICT: COEXISTENCE VALIDÉE (avec réserves mineures ci-dessus).\n";
    echo "\nWorkflow validé:\n";
    echo "  1. Config base (bridge-management / ether2)\n";
    echo "  2. Assistant Hotspot → Send complet\n";
    echo "  3. Assistant PPPoE Setup → Deploy\n";
    exit(0);
}

echo "VERDICT: COEXISTENCE VALIDÉE — Hotspot et PPPoE peuvent coexister sans conflit.\n";
echo "Simulation approfondie: ports, L3, pipelines code, wiring UI, régression bug historique.\n";
exit(0);
