<?php

/**
 * nuxbill_campay_install.php
 * Exemple d'intégration du widget CamPay dans NuxBill.
 *
 * INSTALLATION EN 3 ÉTAPES :
 * 1. Copiez CamPayWidget.php et campay_config.php dans votre dossier NuxBill
 *    (ex : /var/www/nuxbill/plugins/campay/)
 * 2. Modifiez campay_config.php avec vos credentials
 * 3. Incluez ce fichier (ou son contenu) dans votre page de paiement NuxBill
 */

require_once __DIR__ . '/CamPayWidget.php';

// Chargement de la config (username, password, base_url)
$config = require __DIR__ . '/campay_config.php';

// Instanciation du widget
$campay = new CamPayWidget($config);

// --- Utilisation 1 : Afficher le formulaire de paiement intégré ---
// Appelez simplement renderForm() là où vous voulez le widget dans votre page NuxBill.
// Exemple avec options personnalisées :
$campay->renderForm([
    'title'              => 'Payer votre facture NuxBill',
    'button_label'       => '💸 Payer maintenant',
    'description'        => 'Abonnement NuxBill',   // pré-rempli
    'show_bootstrap_cdn' => true,                    // false si Bootstrap déjà chargé
]);


// --- Utilisation 2 : Paiement programmatique (sans formulaire HTML) ---
// Utile si NuxBill gère lui-même le formulaire et vous passe les données.
/*
try {
    $reference = $campay->collectPayment(
        amount: '2000',
        phoneNumber: '237691234567',
        description: 'Facture NuxBill #' . $invoiceId,
        externalReference: 'NUX-' . $invoiceId . '-' . time()
    );

    $result = $campay->waitForPayment($reference, 12, 5);

    if ($result['status'] === 'SUCCESSFUL') {
        // Marquer la facture comme payée dans NuxBill
        // update_invoice_status($invoiceId, 'paid');
        echo "Paiement confirmé. Réf : " . $result['reference'];
    } else {
        echo "Paiement échoué.";
    }

} catch (CamPayWidgetTimeoutException $e) {
    // Paiement toujours en attente — vérifier plus tard via getTransactionStatus()
    error_log('[CamPay] Timeout : ' . $e->getMessage());
} catch (CamPayWidgetException $e) {
    error_log('[CamPay] Erreur : ' . $e->getMessage());
}
*/