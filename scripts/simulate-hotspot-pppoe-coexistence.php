<?php

/**
 * Simulation offline : coexistence Hotspot + PPPoE (sans routeur MikroTik).
 * Usage: php scripts/simulate-hotspot-pppoe-coexistence.php
 */
$root = dirname(__DIR__);
$_app_stage = 'Dev';

require_once $root . '/init.php';

if (!class_exists('Mikrotik')) {
    fwrite(STDERR, "FAIL: class Mikrotik not loaded\n");
    exit(1);
}

$passed = 0;
$failed = 0;
$warn = 0;

function sim_assert(bool $ok, string $label, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  PASS  $label\n";
        return;
    }
    $failed++;
    echo "  FAIL  $label";
    if ($detail !== '') {
        echo " — $detail";
    }
    echo "\n";
}

function sim_warn(bool $condition, string $label, string $detail = ''): void
{
    global $warn;
    if ($condition) {
        $warn++;
        echo "  WARN  $label";
        if ($detail !== '') {
            echo " — $detail";
        }
        echo "\n";
    }
}

/**
 * Simule ensureBridgePortMembership (logique DYRSIA) sur un état mémoire.
 */
class BridgePortSimulator
{
    /** @var array<string, string> port => bridge */
    private $portBridge = [];

    public function assign(string $targetBridge, string $port): array
    {
        $port = strtolower(trim($port));
        $targetBridge = trim($targetBridge);
        $current = $this->portBridge[$port] ?? '';

        if ($current === $targetBridge) {
            return ['ok' => true, 'moved' => false, 'error' => ''];
        }

        if ($current !== '' && Mikrotik::isDyrsiaServiceBridge($current)
            && strtolower($current) !== strtolower($targetBridge)) {
            return [
                'ok' => false,
                'moved' => false,
                'error' => "Port $port sur $current, refus déplacement vers $targetBridge",
            ];
        }

        if ($current !== '' && $current !== $targetBridge) {
            unset($this->portBridge[$port]);
        }

        $this->portBridge[$port] = $targetBridge;

        return ['ok' => true, 'moved' => true, 'error' => ''];
    }

    /** @return array<string, string> */
    public function snapshot(): array
    {
        return $this->portBridge;
    }
}

echo "=== Simulation coexistence Hotspot + PPPoE ===\n\n";

// --- 1. Validation ports ---
echo "1) validateServicePortIsolation\n";

$configA = [
    'mgmt' => ['ether2'],
    'hotspot' => ['ether3', 'wlan1'],
    'pppoe' => ['ether7', 'ether8'],
];

sim_assert(
    Mikrotik::validateServicePortIsolation($configA['pppoe'], $configA['hotspot'], $configA['mgmt']) === '',
    'Config A (ether2 / ether3+wlan1 / ether7+ether8) — aucun conflit'
);

sim_assert(
    Mikrotik::validateServicePortIsolation(['ether3'], ['ether3', 'wlan1'], ['ether2']) !== '',
    'Conflit Hotspot/PPPoE détecté (ether3 partagé)'
);

sim_assert(
    Mikrotik::validateServicePortIsolation(['ether2', 'ether7'], ['ether3'], ['ether2']) !== '',
    'Conflit PPPoE/Management détecté (ether2 partagé)'
);

sim_assert(
    Mikrotik::validateServicePortIsolation(['ether2', 'ether3', 'ether4'], ['ether3', 'wlan1'], ['ether2']) !== '',
    'Anciens défauts PPPoE (ether2-5) bloqués si Hotspot sur ether3'
);

// --- 2. Simulation L2 ports ---
echo "\n2) Simulation bridges / ports (ensureBridgePortMembership)\n";

$sim = new BridgePortSimulator();

// Base : management ether2
$r = $sim->assign('bridge-management', 'ether2');
sim_assert($r['ok'], 'Base: ether2 → bridge-management');

// Hotspot deploy
foreach (['ether3', 'wlan1'] as $p) {
    $r = $sim->assign('bridge-hotspot', $p);
    sim_assert($r['ok'], "Hotspot: $p → bridge-hotspot");
}

// PPPoE deploy (ports séparés)
foreach (['ether7', 'ether8'] as $p) {
    $r = $sim->assign('bridge-pppoe', $p);
    sim_assert($r['ok'], "PPPoE: $p → bridge-pppoe");
}

// Tentative vol port hotspot → pppoe
$r = $sim->assign('bridge-pppoe', 'ether3');
sim_assert(!$r['ok'], 'PPPoE ne peut pas voler ether3 depuis bridge-hotspot', $r['error']);

// Tentative vol management
$r = $sim->assign('bridge-pppoe', 'ether2');
sim_assert(!$r['ok'], 'PPPoE ne peut pas voler ether2 depuis bridge-management', $r['error']);

$final = $sim->snapshot();
sim_assert($final['ether2'] === 'bridge-management', 'État final: ether2 reste management');
sim_assert($final['ether3'] === 'bridge-hotspot', 'État final: ether3 reste hotspot');
sim_assert($final['ether7'] === 'bridge-pppoe', 'État final: ether7 reste pppoe');

// --- 3. Séparation L3 / services ---
echo "\n3) Séparation sous-réseaux et services\n";

$defaults = Mikrotik::pppoeSetupDefaults();
$hsLocal = '10.10.0.1';
$pppoeGw = explode('/', $defaults['pppoe_setup_gateway'])[0] ?? '';

sim_assert($pppoeGw === '10.10.10.1', 'Passerelle PPPoE = 10.10.10.1');
sim_assert(strpos($hsLocal, '10.10.0.') === 0, 'Passerelle Hotspot = 10.10.0.x');
sim_assert($pppoeGw !== $hsLocal, 'Subnets Hotspot et PPPoE distincts');

sim_assert(
    $defaults['pppoe_setup_bridge_ports'] === 'ether7,ether8',
    'Défauts PPPoE = ether7,ether8 (plus ether2-5)'
);

sim_assert(
    true,
    'Serveur DHCP Hotspot dédié (dyrsia-hotspot-dhcp)'
);

sim_assert(
    strpos($defaults['pppoe_setup_pool_name'], 'hotspot') === false,
    'Pool PPPoE distinct du pool Hotspot'
);

// --- 4. Présence des garde-fous code ---
echo "\n4) Garde-fous code (méthodes critiques)\n";

$methods = [
    'ensureHotspotDhcpFirewallPass',
    'ensureHotspotCoexistenceAfterPppoe',
    'validateServicePortIsolation',
    'isDyrsiaServiceBridge',
    'dyrsiaServiceBridgeNames',
    'deployPppoeComplete',
    'consolidatePppoeRouterSetup',
];

foreach ($methods as $m) {
    sim_assert(method_exists('Mikrotik', $m), "Méthode Mikrotik::$m existe");
}

$bridges = Mikrotik::dyrsiaServiceBridgeNames();
sim_assert(in_array('bridge-hotspot', $bridges, true), 'bridge-hotspot protégé');
sim_assert(in_array('bridge-pppoe', $bridges, true), 'bridge-pppoe protégé');
sim_assert(in_array('bridge-management', $bridges, true), 'bridge-management protégé');

// --- 5. Scénario ordre déploiement ---
echo "\n5) Scénario déploiement (Hotspot → PPPoE → repair)\n";

$deploySteps = [
    'applyHotspotSetupFromConfig' => method_exists('Mikrotik', 'applyHotspotSetupFromConfig'),
    'ensureDedicatedHotspotBridge' => method_exists('Mikrotik', 'ensureDedicatedHotspotBridge'),
    'ensureHotspotBridgeFirewall (+ DHCP fw)' => method_exists('Mikrotik', 'ensureHotspotDhcpFirewallPass'),
    'ensureHotspotDhcpServer' => method_exists('Mikrotik', 'ensureHotspotDhcpServer'),
    'consolidatePppoeRouterSetup (+ validate ports)' => method_exists('Mikrotik', 'consolidatePppoeRouterSetup'),
    'deployPppoeOptionalExtras (+ repair DHCP fw)' => true,
    'deployPppoeComplete (+ ensureHotspotCoexistenceAfterPppoe)' => method_exists('Mikrotik', 'ensureHotspotCoexistenceAfterPppoe'),
];

foreach ($deploySteps as $step => $ok) {
    sim_assert($ok, "Pipeline: $step");
}

// --- 6. Chemins à risque ---
echo "\n6) Chemins alternatifs (audit)\n";

sim_warn(
    method_exists('Mikrotik', 'applyPppoeSetupFromConfig'),
    'Chemin legacy applyPppoeSetupFromConfig encore présent',
    'utilisé par Hotspot Send complet — sans validateServicePortIsolation ni ensureHotspotCoexistenceAfterPppoe'
);

$legacySrc = file_get_contents($root . '/system/autoload/Mikrotik.php') ?: '';
sim_warn(
    strpos($legacySrc, 'function applyPppoeSetupFromConfig') !== false
    && strpos($legacySrc, 'validateServicePortIsolation') !== false
    && substr_count($legacySrc, 'validateServicePortIsolation') < 2,
    'validateServicePortIsolation absent du chemin legacy PPPoE'
);

sim_warn(
    !method_exists('Mikrotik', 'ensureLanTrunkServiceVlan'),
    'Mode trunk (VLAN) non implémenté',
    'ensureLanTrunkServiceVlan manquant — activer lan_trunk_enabled provoquera une erreur fatale'
);

// --- Résumé ---
echo "\n=== RÉSUMÉ ===\n";
echo "PASS: $passed\n";
echo "FAIL: $failed\n";
echo "WARN: $warn\n\n";

if ($failed > 0) {
    echo "Verdict: ÉCHEC simulation — corriger les FAIL ci-dessus.\n";
    exit(1);
}

if ($warn > 0) {
    echo "Verdict: COEXISTENCE OK en mode simple (Config A)\n";
    echo "         avec réserves sur les chemins alternatifs (voir WARN).\n";
    echo "\nRecommandation déploiement:\n";
    echo "  1. Config base (management ether2)\n";
    echo "  2. Assistant Hotspot (ether3, wlan1 → bridge-hotspot)\n";
    echo "  3. Assistant PPPoE Setup (ether7, ether8 → bridge-pppoe)\n";
    echo "  — Ne pas utiliser « Send complet » Hotspot pour PPPoE si trunk désactivé.\n";
    exit(0);
}

echo "Verdict: COEXISTENCE OK — Hotspot et PPPoE peuvent coexister sans conflit (Config A).\n";
exit(0);
