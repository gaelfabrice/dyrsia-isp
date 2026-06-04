<?php

/**
 * CamPayWidget.php — Widget CamPay Mobile Money (configurable)
 * Prérequis : PHP 7.4+, extension cURL activée
 *
 * Usage :
 *   $campay = new CamPayWidget([
 *       'username' => 'VOTRE_USERNAME',
 *       'password' => 'VOTRE_PASSWORD',
 *       'base_url' => 'https://demo.campay.net/api', // démo ou prod
 *   ]);
 */

class CamPayWidgetException extends RuntimeException {}
class CamPayWidgetAuthException extends CamPayWidgetException {}
class CamPayWidgetTimeoutException extends CamPayWidgetException {}

class CamPayWidget
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $sessionTokenKey;
    private string $sessionTokenExpKey;

    public function __construct(array $config)
    {
        if (empty($config['username']) || empty($config['password'])) {
            throw new CamPayWidgetException('Les clés "username" et "password" sont obligatoires.');
        }
        $this->username           = $config['username'];
        $this->password           = $config['password'];
        $this->baseUrl            = rtrim($config['base_url'] ?? 'https://demo.campay.net/api', '/');
        $this->sessionTokenKey    = 'campay_token_' . md5($this->username);
        $this->sessionTokenExpKey = 'campay_token_exp_' . md5($this->username);
    }

    // --- Authentification ---

    public function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (
            !empty($_SESSION[$this->sessionTokenKey]) &&
            !empty($_SESSION[$this->sessionTokenExpKey]) &&
            time() < $_SESSION[$this->sessionTokenExpKey] - 60
        ) {
            return $_SESSION[$this->sessionTokenKey];
        }

        $response = $this->httpRequest('POST', '/token/', [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if (empty($response['token'])) {
            throw new CamPayWidgetAuthException('Impossible d\'obtenir le token. Vérifiez vos credentials.');
        }

        $_SESSION[$this->sessionTokenKey]    = $response['token'];
        $_SESSION[$this->sessionTokenExpKey] = time() + (int)($response['expires_in'] ?? 3600);

        return $response['token'];
    }

    // --- Paiement ---

    public function collectPayment(string $amount, string $phoneNumber, string $description, string $externalReference): string
    {
        $token    = $this->getToken();
        $response = $this->httpRequest('POST', '/collect/', [
            'amount'             => $amount,
            'from'               => $phoneNumber,
            'description'        => $description,
            'external_reference' => $externalReference,
        ], $token);

        if (empty($response['reference'])) {
            $errorMsg = $response['message'] ?? $response['error'] ?? 'Erreur inconnue.';
            throw new CamPayWidgetException('Échec du paiement : ' . $errorMsg);
        }

        return $response['reference'];
    }

    public function getTransactionStatus(string $reference): array
    {
        $token    = $this->getToken();
        $response = $this->httpRequest('GET', '/transaction/' . urlencode($reference) . '/', [], $token);

        if (!isset($response['status'])) {
            throw new CamPayWidgetException('Réponse inattendue : ' . json_encode($response));
        }

        return $response;
    }

    public function waitForPayment(string $reference, int $maxAttempts = 10, int $intervalSeconds = 5): array
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->getTransactionStatus($reference);
            $status = $result['status'] ?? 'PENDING';

            if (in_array($status, ['SUCCESSFUL', 'FAILED'], true)) {
                return $result;
            }
            if ($attempt < $maxAttempts) { sleep($intervalSeconds); }
        }

        throw new CamPayWidgetTimeoutException(
            sprintf('Paiement en attente après %d tentatives (réf. %s).', $maxAttempts, $reference)
        );
    }

    // --- Rendu du formulaire HTML intégrable ---

    /**
     * Affiche le formulaire de paiement directement dans votre page.
     *
     * @param array $options
     *   - title             : Titre du widget (défaut : "Paiement Mobile Money")
     *   - button_label      : Texte du bouton (défaut : "💸 Payer maintenant")
     *   - description       : Description pré-remplie
     *   - amount            : Montant pré-rempli
     *   - show_bootstrap_cdn: Inclure Bootstrap CDN (défaut : true)
     */
    public function renderForm(array $options = []): void
    {
        $title         = htmlspecialchars($options['title']        ?? 'Paiement Mobile Money');
        $buttonLabel   = $options['button_label']                  ?? '💸 Payer maintenant';
        $defaultDesc   = htmlspecialchars($options['description']  ?? '');
        $defaultAmount = htmlspecialchars($options['amount']       ?? '');
        $showBootstrap = $options['show_bootstrap_cdn']            ?? true;

        $paymentResult = null;
        $paymentError  = null;
        $reference     = null;
        $errors        = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campay_submit'])) {
            $amount      = trim($_POST['campay_amount']      ?? '');
            $phone       = trim($_POST['campay_phone']       ?? '');
            $description = trim($_POST['campay_description'] ?? '');

            if ($amount === '' || !is_numeric($amount) || (int)$amount <= 0) {
                $errors[] = 'Le montant doit être un nombre entier positif.';
            }
            if (!preg_match('/^237[0-9]{9}$/', $phone)) {
                $errors[] = 'Numéro invalide. Format attendu : 237XXXXXXXXX (ex : 237691234567).';
            }
            if ($description === '') {
                $errors[] = 'La description est obligatoire.';
            }

            if (empty($errors)) {
                $externalRef = 'ORDER-' . strtoupper(bin2hex(random_bytes(6))) . '-' . time();
                try {
                    $reference     = $this->collectPayment((string)(int)$amount, $phone, $description, $externalRef);
                    $paymentResult = $this->waitForPayment($reference, 10, 5);
                } catch (CamPayWidgetTimeoutException $e) {
                    $paymentError = '⏳ Paiement en cours. Vérifiez le statut plus tard. (Réf. : ' . htmlspecialchars($reference ?? 'N/A') . ')';
                } catch (CamPayWidgetAuthException $e) {
                    $paymentError = '🔐 Erreur d\'authentification : ' . htmlspecialchars($e->getMessage());
                } catch (CamPayWidgetException $e) {
                    $paymentError = '❌ Erreur API : ' . htmlspecialchars($e->getMessage());
                }
            }
        }

        if ($showBootstrap): ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<?php endif; ?>

<div class="campay-widget py-3">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <h2 class="h4 text-center text-primary mb-4">💳 <?= $title ?></h2>

      <?php if ($paymentResult):
        $status = $paymentResult['status'] ?? 'PENDING'; ?>
        <div class="alert alert-<?= $status === 'SUCCESSFUL' ? 'success' : ($status === 'FAILED' ? 'danger' : 'warning') ?>">
          <strong><?= $status === 'SUCCESSFUL' ? '✅ Paiement réussi !' : ($status === 'FAILED' ? '❌ Paiement échoué.' : '⏳ En attente...') ?></strong><br>
          Référence : <code><?= htmlspecialchars($paymentResult['reference'] ?? $reference) ?></code>
        </div>
      <?php endif; ?>

      <?php if ($paymentError): ?>
        <div class="alert alert-warning"><?= $paymentError ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold">Montant (XAF)</label>
              <input type="number" name="campay_amount" class="form-control" placeholder="500" min="1"
                     value="<?= htmlspecialchars($_POST['campay_amount'] ?? $defaultAmount) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Numéro Mobile Money</label>
              <input type="tel" name="campay_phone" class="form-control" placeholder="237691234567"
                     value="<?= htmlspecialchars($_POST['campay_phone'] ?? '') ?>" required>
              <div class="form-text">Format : 237 + 9 chiffres (MTN ou Orange)</div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Description</label>
              <input type="text" name="campay_description" class="form-control" placeholder="Facture #42"
                     value="<?= htmlspecialchars($_POST['campay_description'] ?? $defaultDesc) ?>" required>
            </div>
            <div class="d-grid">
              <button type="submit" name="campay_submit" value="1" class="btn btn-primary btn-lg">
                <?= $buttonLabel ?>
              </button>
            </div>
          </form>
        </div>
      </div>
      <p class="text-center text-muted small mt-3">Sécurisé par CamPay Mobile Money</p>
    </div>
  </div>
</div>
<?php
    }

    // --- HTTP interne ---

    private function httpRequest(string $method, string $endpoint, array $data = [], ?string $token = null): array
    {
        $url     = $this->baseUrl . $endpoint;
        $ch      = curl_init();
        $headers = ['Content-Type: application/json'];

        if ($token !== null) { $headers[] = 'Authorization: Token ' . $token; }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new CamPayWidgetException('Erreur cURL : ' . $curlError);
        }

        $decoded = json_decode($rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CamPayWidgetException('Réponse non-JSON (HTTP ' . $httpCode . ') : ' . $rawResponse);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = $decoded['detail'] ?? $decoded['message'] ?? $decoded['error'] ?? $rawResponse;
            throw new CamPayWidgetException('Erreur HTTP ' . $httpCode . ' : ' . $errorMsg);
        }

        return $decoded;
    }
}