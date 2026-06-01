<?php

if (!isset($_SERVER['SERVER_PORT'])) {
    $_SERVER['SERVER_PORT'] = 80;
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

include __DIR__ . '/../init.php';

AdminSubscription::ensureSchema();
AdminSubscription::syncStatuses();

echo "Admin subscriptions sync completed " . date('Y-m-d H:i:s') . PHP_EOL;
