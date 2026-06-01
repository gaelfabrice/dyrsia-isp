<?php

if (!isset($_SERVER['SERVER_PORT'])) {
    $_SERVER['SERVER_PORT'] = 80;
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

include __DIR__ . '/../init.php';

AdminSubscription::ensureSchema();
Tenant::ensureSchema();
$deleted = Tenant::cleanupOrphanTenants();

AdminSubscription::syncStatuses();

echo "Tenant cleanup completed. Orphan tenants removed: " . $deleted . " " . date('Y-m-d H:i:s') . PHP_EOL;
