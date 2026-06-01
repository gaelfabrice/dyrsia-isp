<?php
/**
 * Router for PHP built-in server (php -S … router.php).
 * Serves static files when present; otherwise forwards to index.php.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && $uri !== '' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Bare /index.php has no controller; treat as home.
if ($uri === '/index.php' && !isset($_GET['_route'])) {
    $_SERVER['REQUEST_URI'] = '/';
}

require __DIR__ . '/index.php';
