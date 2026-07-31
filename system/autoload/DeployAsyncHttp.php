<?php

/**
 * Réponse JSON async puis travail long (déploiement MikroTik) sans bloquer le navigateur.
 */
class DeployAsyncHttp
{
    public static function sendJsonAndCloseConnection(string $json): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Length: ' . strlen($json));
            header('Connection: close');
            header('X-Accel-Buffering: no');
            header('Cache-Control: no-store');
        }
        echo $json;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        @flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * true = le client a reçu la réponse ; on peut enchaîner runJob() dans ce processus PHP.
     */
    public static function canRunHeavyWorkInSameProcess(): bool
    {
        if (PHP_SAPI === 'cli-server') {
            return true;
        }

        return function_exists('fastcgi_finish_request');
    }
}
