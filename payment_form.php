<?php
require_once __DIR__ . '/CamPay.php';

$paymentResult = null;
$paymentError  = null;
$reference     = null;
$errors        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount      = trim($_POST['amount']      ?? '');
    $phone       = trim($_POST['phone']       ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($amount === '' || !is_numeric($amount) || (int)$amount <= 0) {
        $errors[] = 'Le montant doit être un nombre entier positif.';
    }
    if (!preg_match('/^237[0-9]{9}$/', $phone)) {
        $errors[] = 'Le numéro doit être au format 237XXXXXXXXX (ex : 237691234567).';
    }
    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    }

    if (empty($errors)) {
        $externalReference = 'ORDER-' . strtoupper(bin2hex(random_bytes(6))) . '-' . time();
        try {
            $campay    = new CamPay();
            $reference = $campay->collectPayment((string)(int)$amount, $phone, $description, $externalReference);
            $paymentResult = $campay->waitForPayment($reference, 10, 5);
        } catch (CamPayTimeoutException $e) {
            $paymentError = '⏳ Paiement en cours. Vérifiez le statut ultérieurement. (Réf. : ' . htmlspecialchars($reference ?? 'N/A') . ')';
        } catch (CamPayAuthException $e) {
            $paymentError = '🔐 Erreur d\'authentification : ' . htmlspecialchars($e->getMessage());
        } catch (CamPayApiException $e) {
            $paymentError = '❌ Erreur API : ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement Mobile Money — CamPay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h1 class="h3 text-center text-primary mb-4">💳 Paiement Mobile Money</h1>

      <?php if ($paymentResult): ?>
        <?php $status = $paymentResult['status'] ?? 'PENDING'; ?>
        <div class="alert alert-<?= $status === 'SUCCESSFUL' ? 'success' : ($status === 'FAILED' ? 'danger' : 'warning') ?>">
          <strong><?= $status === 'SUCCESSFUL' ? '✅ Paiement réussi !' : ($status === 'FAILED' ? '❌ Paiement échoué.' : '⏳ En attente...') ?></strong><br>
          Référence : <code><?= htmlspecialchars($paymentResult['reference'] ?? $reference) ?></code>
        </div>
      <?php endif; ?>

      <?php if ($paymentError): ?>
        <div class="alert alert-warning"><?= $paymentError ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold">Montant (XAF)</label>
              <input type="number" name="amount" class="form-control" placeholder="500" min="1" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Numéro Mobile Money</label>
              <input type="tel" name="phone" class="form-control" placeholder="237691234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
              <div class="form-text">Format : 237 + 9 chiffres (MTN ou Orange)</div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Description</label>
              <input type="text" name="description" class="form-control" placeholder="Achat produit #42" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" required>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg">💸 Payer maintenant</button>
            </div>
          </form>
        </div>
      </div>
      <p class="text-center text-muted small mt-3">Environnement de démonstration — CamPay Demo API</p>
    </div>
  </div>
</div>
</body>
</html>