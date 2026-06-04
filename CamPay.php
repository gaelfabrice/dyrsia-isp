<?php

/**
 * CamPay.php — Classe d'intégration CamPay Mobile Money
 * Prérequis : PHP 7.4+, extension cURL activée
 */

class CamPayApiException extends RuntimeException {}
class CamPayAuthException extends CamPayApiException {}
class CamPayTimeoutException extends CamPayApiException {}

class CamPay
{
    private const BASE_URL  = 'https://demo.campay.net/api';
    private const USERNAME  = '7pku11-BipO09x8lMaVeeDnJ2yIyISnpxs9c0lHh3J0hSIQBkZ_OEYHKgHeTEgyY0u6M8VRK9R1sLHHUV5bh7Q';
    private const PASSWORD  = 'a82z4ASrGdYn0PVy4VsFOLzuAIMIexOKTICdi4dhTeAEgEErZNUQFuPEVDZKnHNjU5GrDg-OKDpQ3oOZUnIfHQ';

    private const SESSION_TOKEN_KEY     = 'campay_token';
    private const SESSION_TOKEN_EXP_KEY = 'campay_token_expires_at';

    /** Récupère le token (mis en cache en session pendant 1h) */
    public function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (
            !empty($_SESSION[self::SESSION_TOKEN_KEY]) &&
            !empty($_SESSION[self::SESSION_TOKEN_EXP_KEY]) &&
            time() < $_SESSION[self::SESSION_TOKEN_EXP_KEY] - 60
        ) {
            return $_SESSION[self::SESSION_TOKEN_KEY];
        }

        $response = $this->httpRequest('POST', '/token/', [
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
        ]);

        if (empty($response['token'])) {
            throw new CamPayAuthException('Impossible d\'obtenir le token CamPay.');
        }

        $_SESSION[self::SESSION_TOKEN_KEY]     = $response['token'];
        $_SESSION[self::SESSION_TOKEN_EXP_KEY] = time() + (int)($response['expires_in'] ?? 3600);

        return $response['token'];
    }

    /** Initie un paiement Mobile Money, retourne la référence de transaction */
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
            throw new CamPayApiException('Échec de l\'initiation du paiement : ' . $errorMsg);
        }

        return $response['reference'];
    }

    /** Vérifie le statut d'une transaction */
    public function getTransactionStatus(string $reference): array
    {
        $token    = $this->getToken();
        $response = $this->httpRequest('GET', '/transaction/' . urlencode($reference) . '/', [], $token);

        if (!isset($response['status'])) {
            throw new CamPayApiException('Réponse inattendue : ' . json_encode($response));
        }

        return $response;
    }

    /** Poll le statut jusqu'à SUCCESSFUL ou FAILED */
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

        throw new CamPayTimeoutException(
            sprintf('Paiement toujours en attente après %d tentatives (réf. %s).', $maxAttempts, $reference)
        );
    }

    /** Effectue une requête HTTP via cURL */
    private function httpRequest(string $method, string $endpoint, array $data = [], ?string $token = null): array
    {
        $url     = self::BASE_URL . $endpoint;
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
            throw new CamPayApiException('Erreur cURL : ' . $curlError);
        }

        $decoded = json_decode($rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CamPayApiException('Réponse non-JSON (HTTP ' . $httpCode . ') : ' . $rawResponse);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = $decoded['detail'] ?? $decoded['message'] ?? $decoded['error'] ?? $rawResponse;
            throw new CamPayApiException('Erreur HTTP ' . $httpCode . ' : ' . $errorMsg);
        }

        return $decoded;
    }
}