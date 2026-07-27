#!/usr/bin/env php
<?php
/**
 * Worker CLI — exécute un déploiement hotspot async (Send complet / login.html).
 *
 * Usage: php scripts/hotspot-deploy-worker.php /path/to/hotspot_deploy_{admin}_{job}.json
 */
declare(strict_types=1);

@set_time_limit(600);
@ini_set('max_execution_time', '600');
@ini_set('default_socket_timeout', '120');

chdir(dirname(__DIR__));
require_once 'init.php';

$jobPath = $argv[1] ?? '';
if ($jobPath === '') {
    fwrite(STDERR, "Usage: php scripts/hotspot-deploy-worker.php <job.json>\n");
    exit(1);
}

HotspotDeployRunner::runJob($jobPath);
