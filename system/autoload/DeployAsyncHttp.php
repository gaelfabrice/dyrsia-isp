<?php

/**
 * Réponse JSON async puis travail long (déploiement MikroTik) sans bloquer le navigateur.
 */
class DeployAsyncHttp
{
    /**
     * Chemin PHP CLI (Docker Apache mod_php : PHP_BINARY pointe souvent vers httpd).
     */
    public static function resolvePhpCliBinary(): string
    {
        $fromEnv = getenv('PHP_CLI_PATH');
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            $fromEnv = trim($fromEnv);
            if (@is_executable($fromEnv)) {
                return $fromEnv;
            }
        }
        foreach (['/usr/local/bin/php', '/usr/bin/php'] as $candidate) {
            if (@is_executable($candidate)) {
                return $candidate;
            }
        }
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $bin = (string) PHP_BINARY;
            $lower = strtolower($bin);
            if (str_contains($lower, 'php') && !str_contains($lower, 'apache') && !str_contains($lower, 'httpd')) {
                return $bin;
            }
        }

        return 'php';
    }

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
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
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

        return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
    }
}
