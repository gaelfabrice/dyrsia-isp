<?php

/**
 * Fonctions admin/auth/CSRF — chargées depuis init.php et en secours depuis boot.php
 * si init.php est tronqué ou mal déployé sur le serveur.
 */

if (!function_exists('_auth')) {
    function _auth($login = true)
    {
        if (User::getID()) {
            return true;
        }
        if ($login) {
            r2(getUrl('login'));
        }

        return false;
    }
}

if (!function_exists('_admin')) {
    function _admin($login = true)
    {
        if (Admin::getID()) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                wifizone_verify_csrf();
            }

            return true;
        }
        if ($login) {
            r2(getUrl('login'));
        }

        return false;
    }
}

if (!function_exists('wifizone_verify_csrf')) {
    function wifizone_verify_csrf()
    {
        global $config, $isApi, $routes;
        if (!empty($isApi)) {
            return;
        }
        if (($config['csrf_enabled'] ?? 'yes') !== 'yes') {
            return;
        }
        $handler = $routes[0] ?? '';
        $action = $routes[1] ?? '';
        $publicPlugins = [
            'hotspot_login', 'hotspot_pay', 'hotspot_verify', 'hotspot_pg_campay_verify', 'hotspot_prepare_login',
            'hotspot_recover_plan', 'hotspot_plan',
            'pppoe_portal', 'pppoe_plan', 'pppoe_pay', 'pppoe_verify',
            'wifizone_reseller_api', 'hotspot_resellers_login',
        ];
        if ($handler === 'plugin' && in_array($action, $publicPlugins, true)) {
            return;
        }
        if ($handler === 'home' || $handler === 'login' || $handler === 'provision' || $handler === 'ref') {
            return;
        }
        if ($handler === 'routers' && $action === 'test-connection') {
            return;
        }
        $token = _post('csrf_token') ?: _req('csrf_token');
        if ($token === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = trim((string) $_SERVER['HTTP_X_CSRF_TOKEN']);
        }
        if ($token === '') {
            if (wifizone_json_response_requested()) {
                wifizone_json_error(Lang::T('Invalid or Expired CSRF Token') . '.', 403);
            }
            r2(getUrl('dashboard'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        if (!Csrf::check($token)) {
            if (wifizone_json_response_requested()) {
                wifizone_json_error(Lang::T('Token has expired. Please log in again.'), 403);
            }
            r2(getUrl('dashboard'), 'e', Lang::T('Token has expired. Please log in again.'));
        }
    }
}

if (!function_exists('wifizone_json_response_requested')) {
    function wifizone_json_response_requested()
    {
        global $routes;
        $handler = $routes[0] ?? '';
        $action = $routes[1] ?? '';
        if ($handler === 'routers' && $action === 'test-connection') {
            return true;
        }
        if ($handler === 'settings' && $action === 'pppoe-setup' && !empty($_POST['ajax_deploy'])) {
            return true;
        }
        if ($handler === 'settings' && $action === 'hotspot' && !empty($_POST['ajax_hotspot_deploy'])) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        return strpos($accept, 'application/json') !== false;
    }
}

if (!function_exists('wifizone_json_error')) {
    function wifizone_json_error($message, $httpCode = 400)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }
}
