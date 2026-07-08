<?php

/**
 * PHPUnit bootstrap.
 *
 * The application normally boots through init.php, which requires a database
 * connection and a full runtime config. To keep unit tests fast and isolated,
 * we register a lightweight autoloader that loads the standalone classes from
 * system/autoload/ directly, without touching the database.
 */

define('WIFIZONE_ROOT', dirname(__DIR__));

spl_autoload_register(static function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = WIFIZONE_ROOT . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR
        . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
