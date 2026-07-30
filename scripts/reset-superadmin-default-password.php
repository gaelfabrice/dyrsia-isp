#!/usr/bin/env php
<?php
/**
 * Réinitialise le mot de passe SuperAdmin Fab610 au mot de passe initial documenté.
 *
 * Usage:
 *   php scripts/reset-superadmin-default-password.php
 *   php scripts/reset-superadmin-default-password.php --force
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/init.php';

$force = in_array('--force', $argv ?? [], true);

if (!class_exists('SuperAdminAccount')) {
    fwrite(STDERR, "SuperAdminAccount introuvable.\n");
    exit(1);
}

$pwd = SuperAdminAccount::defaultInitialPassword();
$user = SuperAdminAccount::DEFAULT_USERNAME;

if (!$force && php_sapi_name() === 'cli') {
    fwrite(STDOUT, "Compte: {$user}\n");
    fwrite(STDOUT, "Nouveau mot de passe: {$pwd}\n");
    fwrite(STDOUT, "Continuer ? [y/N] ");
    $line = trim((string) fgets(STDIN));
    if (strtolower($line) !== 'y') {
        fwrite(STDOUT, "Annulé.\n");
        exit(0);
    }
}

if (!SuperAdminAccount::applyDefaultPasswordToFab610(true)) {
    fwrite(STDERR, "Aucun SuperAdmin « {$user} » trouvé.\n");
    exit(1);
}

fwrite(STDOUT, "OK — mot de passe SuperAdmin réinitialisé.\n");
fwrite(STDOUT, "Identifiant: {$user}\n");
fwrite(STDOUT, "Mot de passe: {$pwd}\n");
fwrite(STDOUT, "Changez-le après connexion : Paramètres → Changer mot de passe.\n");
