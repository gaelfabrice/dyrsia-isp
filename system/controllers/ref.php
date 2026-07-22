<?php

/**
 * Point d'entrée des liens de parrainage.
 * URL : /?_route=ref/CODE
 *
 * Stocke le code en session puis redirige vers la page de création d'instance.
 */

Referral::ensureSchema();

$code = trim((string) ($routes['1'] ?? ''));

if ($code === '') {
    r2(getUrl('provision'), 'e', Lang::T('Lien de parrainage invalide.'));
}

$refCode = Referral::findReferrerByCode($code);

if (!$refCode) {
    r2(getUrl('provision'), 'e', Lang::T('Ce lien de parrainage n\'existe pas ou a expiré.'));
}

if (!Referral::isReferrerActive((int) $refCode->admin_id)) {
    r2(getUrl('provision'), 'e', Lang::T('Ce lien de parrainage n\'est pas actif (le parrain n\'a pas d\'abonnement actif).'));
}

Referral::storeReferralSession($code);

$signupIntent = AdminSubscription::normalizeSignupIntent(_get('intent') ?: _get('plan'));
$intentParam = $signupIntent !== 'demo' ? '&intent=' . urlencode($signupIntent) : '';

r2(getUrl('provision' . $intentParam), 's', Lang::T('Lien de parrainage valide ! Créez votre compte ci-dessous.'));
