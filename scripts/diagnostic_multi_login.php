#!/usr/bin/env php
<?php
/**
 * Diagnostic wifizones / WifiZone — multi-connexion (shared-users)
 * Usage: php scripts/diagnostic_multi_login.php
 */
$root = dirname(__DIR__);
require $root . '/init.php';

echo "=== DIAGNOSTIC NUXBILL MULTI-CONNEXION ===\n\n";

$version = defined('APP_VERSION') ? APP_VERSION : ($config['version'] ?? 'inconnue');
echo "Application : wifizones / WifiZone\n";
echo "Version     : {$version}\n";
echo "Langue      : " . ($config['language'] ?? 'english') . "\n\n";

// 1. Table tbl_plans
echo "--- 1. Colonnes tbl_plans (multi-session) ---\n";
$wanted = ['shared_users', 'shared', 'max_login', 'max_sessions', 'multi_login', 'allow_multi'];
$foundCols = [];
try {
    $cols = ORM::raw_execute('SHOW COLUMNS FROM tbl_plans');
    // ORM raw may not return rows easily — use PDO if available
    $db = ORM::get_db();
    $stmt = $db->query('SHOW COLUMNS FROM tbl_plans');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['Field'], $wanted, true)) {
            $foundCols[] = $row['Field'];
            echo "  OK colonne '{$row['Field']}' ({$row['Type']})\n";
        }
    }
} catch (Throwable $e) {
    echo "  ERREUR : " . $e->getMessage() . "\n";
}
if (empty($foundCols)) {
    echo "  AUCUNE colonne multi-connexion standard trouvée.\n";
} elseif (in_array('shared_users', $foundCols, true)) {
    echo "  => Support natif via colonne shared_users.\n";
}

// 2. Fichiers API / MikroTik
echo "\n--- 2. Code shared-users / voucher ---\n";
$scanDirs = [
    $root . '/system/autoload',
    $root . '/system/devices',
    $root . '/system/plugin',
    $root . '/system/controllers',
];
$patterns = ['shared-users' => false, 'shared_users' => false, 'Simultaneous-Use' => false];
foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $content = @file_get_contents($path);
        if ($content === false) {
            continue;
        }
        foreach (array_keys($patterns) as $pat) {
            if (strpos($content, $pat) !== false) {
                $patterns[$pat] = true;
                $rel = str_replace($root . '/', '', $path);
                echo "  OK '{$pat}' dans {$rel}\n";
            }
        }
    }
}

// 3. UI plans hotspot
echo "\n--- 3. Interface admin plans Hotspot ---\n";
$uiFiles = [
    'ui/ui/admin/hotspot/add.tpl' => 'Shared Users (création)',
    'ui/ui/admin/hotspot/edit.tpl' => 'Shared Users (édition)',
];
foreach ($uiFiles as $rel => $label) {
    $path = $root . '/' . $rel;
    if (file_exists($path) && strpos(file_get_contents($path), 'sharedusers') !== false) {
        echo "  OK {$label} : {$rel}\n";
    } else {
        echo "  -- {$label} : non trouvé\n";
    }
}

// 4. Plans existants
echo "\n--- 4. Plans Hotspot (shared_users) ---\n";
try {
    $plans = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->limit(15)->find_many();
    if (count($plans) === 0) {
        echo "  Aucun plan Hotspot en base.\n";
    }
    foreach ($plans as $plan) {
        $su = $plan['shared_users'] ?? '?';
        $dev = $plan['device'] ?? '';
        echo sprintf(
            "  - ID %d | %s | shared_users=%s | device=%s | routeur=%s\n",
            $plan['id'],
            $plan['name_plan'],
            $su,
            $dev,
            $plan['routers']
        );
    }
} catch (Throwable $e) {
    echo "  ERREUR : " . $e->getMessage() . "\n";
}

// 5. Config notifications globales (hotspot utilise ces clés)
echo "\n--- 5. Canaux notification (tbl_appconfig) ---\n";
$keys = [
    'telegram_bot' => 'Telegram bot',
    'telegram_target_id' => 'Telegram destinataire',
    'sms_url' => 'SMS',
    'wa_url' => 'WhatsApp',
    'smtp_host' => 'Email SMTP',
    'user_notification_expired' => 'Notif. expiration client',
    'user_notification_payment' => 'Notif. paiement client',
    'user_notification_reminder' => 'Notif. rappel client',
    'hotspot_message' => 'Hotspot: messages actifs',
    'hotspot_message_via' => 'Hotspot: canal (sms/wa/both)',
];
foreach ($keys as $key => $label) {
    $val = $config[$key] ?? '';
    if ($key === 'telegram_bot' || $key === 'smtp_pass') {
        $status = !empty($val) ? 'configuré (masqué)' : 'vide';
    } else {
        $status = ($val === '' || $val === null) ? 'vide' : $val;
    }
    echo "  {$label} [{$key}] : {$status}\n";
}

echo "\n=== CONCLUSION ===\n";
if (in_array('shared_users', $foundCols, true) && $patterns['shared-users']) {
    echo "Multi-connexion : OUI (native wifizones).\n";
    echo "Activation : Services > Hotspot > ajouter/modifier un plan > champ « Utilisateurs partagés » (Shared Users).\n";
    echo "Valeur N = jusqu'à N appareils simultanés sur le même compte/voucher (profil MikroTik shared-users).\n";
    echo "Pousser le plan vers le routeur (sync) après modification.\n";
} else {
    echo "Multi-connexion : partielle ou à vérifier manuellement.\n";
}
echo "\nHotspot System Settings utilise les mêmes clés globales que Paramètres > Paramètres généraux pour SMS/WA/Email/Telegram.\n";
echo "=== FIN ===\n";
