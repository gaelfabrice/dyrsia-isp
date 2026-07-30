<?php

/**
 * Worker CLI pour le déploiement PPPoE async — survit à la fin de la requête HTTP (dev-server, Nginx).
 */
class PppoeDeployRunner
{
    public static function spawnBackground(string $jobPath): void
    {
        $jobPath = trim($jobPath);
        if ($jobPath === '' || !is_file($jobPath)) {
            return;
        }

        $root = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
        $php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $script = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'pppoe-deploy-worker.php';
        if (!is_file($script)) {
            self::markJobFailed($jobPath, 'Worker PPPoE introuvable (scripts/pppoe-deploy-worker.php).');

            return;
        }

        $logFile = $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'pppoe_deploy_worker.log';
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobPath)
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1';

        $spawned = false;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $spawned = pclose(popen('start /B ' . $cmd, 'r')) !== false;
        } else {
            exec($cmd . ' & echo $!', $spawnOut, $spawnCode);
            $spawned = $spawnCode === 0 && !empty($spawnOut[0]) && ctype_digit(trim((string) $spawnOut[0]));
        }
        if (!$spawned) {
            @file_put_contents(
                $logFile,
                date('c') . " spawn failed: {$cmd}\n",
                FILE_APPEND
            );
            self::markJobFailed(
                $jobPath,
                'Impossible de lancer le worker PPPoE (exec désactivé ?). '
                . 'Relancez ./dev-server.sh depuis Terminal avec WireGuard actif.'
            );
        }
    }

    public static function runJob(string $jobPath): void
    {
        global $config, $_app_stage;

        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('default_socket_timeout', '120');

        $jobPath = trim($jobPath);
        if ($jobPath === '' || !is_file($jobPath)) {
            exit(1);
        }

        $raw = @file_get_contents($jobPath);
        $job = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($job)) {
            self::markJobFailed($jobPath, 'Tâche de déploiement PPPoE illisible.');
            exit(1);
        }

        if ((string) ($job['status'] ?? '') !== 'running') {
            exit(0);
        }

        register_shutdown_function(static function () use ($jobPath) {
            $raw = @file_get_contents($jobPath);
            $job = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($job) || (string) ($job['status'] ?? '') !== 'running') {
                return;
            }
            $err = error_get_last();
            $msg = 'Déploiement PPPoE interrompu (processus worker).';
            if ($err && !empty($err['message'])) {
                $msg .= ' ' . $err['message'];
            }
            self::markJobFailed($jobPath, $msg);
        });

        $adminId = (int) ($job['admin_id'] ?? 0);
        $routerName = trim((string) ($job['router'] ?? ''));
        if ($adminId <= 0 || $routerName === '') {
            self::markJobFailed($jobPath, 'Tâche PPPoE incomplète (admin ou routeur manquant).');
            exit(1);
        }

        $adminRow = ORM::for_table('tbl_users')->find_one($adminId);
        $admin = Impersonate::adminToArray($adminRow);
        if (!$admin || !in_array($admin['user_type'] ?? '', ['SuperAdmin', 'Admin'], true)) {
            self::markJobFailed($jobPath, 'Compte admin introuvable pour le déploiement PPPoE.');
            exit(1);
        }

        if ($_app_stage == 'Demo' || DemoShowcase::isActive($admin)) {
            self::markJobFailed($jobPath, 'Déploiement PPPoE désactivé (démo).');
            exit(1);
        }

        $mikrotik = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if (!$mikrotik) {
            $mikrotik = ORM::for_table('tbl_routers')->where('description', $routerName)->find_one();
        }
        if (!$mikrotik || !self::routerOwnedByAdmin($admin, $mikrotik)) {
            self::markJobFailed($jobPath, 'Routeur « ' . $routerName . ' » introuvable pour ce compte.');
            exit(1);
        }
        $mikrotik = $mikrotik->as_array();

        $setupConfig = is_array($config) ? $config : [];
        foreach (Mikrotik::pppoeSetupDefaults() as $key => $defaultValue) {
            if (!isset($setupConfig[$key]) || $setupConfig[$key] === '') {
                $setupConfig[$key] = $defaultValue;
            }
        }

        $started = microtime(true);
        self::updateJobProgress($jobPath, 'Worker CLI : vérification VPN / API MikroTik…');

        try {
            Mikrotik::resetPppoeSyncRuntimeState();
            $probe = null;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                if ($attempt > 0) {
                    usleep(2000000);
                }
                $probe = Mikrotik::probeApiReachable($mikrotik['ip_address'], 8);
                if ($probe === true) {
                    break;
                }
            }
            if ($probe !== true) {
                self::markJobFailed(
                    $jobPath,
                    'Routeur injoignable (' . $probe . '). Vérifiez le VPN WireGuard puis réessayez.'
                );
                exit(1);
            }

            self::updateJobProgress($jobPath, 'Infrastructure PPPoE sur le routeur (bridge, pool, serveur)…');
            $result = Mikrotik::deployPppoeComplete(null, $setupConfig, $mikrotik, $admin);
            $elapsed = round(microtime(true) - $started, 1);

            if (empty($result['ok'])) {
                $errors = $result['errors'] ?? ['échec inconnu'];
                self::finishDeployResult($jobPath, [
                    'ok' => false,
                    'message' => 'Échec déploiement PPPoE : ' . implode(' | ', $errors),
                    'errors' => $errors,
                    'actions' => $result['actions'] ?? [],
                    'elapsed' => $elapsed,
                ]);
                exit(1);
            }

            $actions = $result['actions'] ?? [];
            self::finishDeployResult($jobPath, [
                'ok' => true,
                'message' => 'PPPoE déployé sur ' . $routerName . ' (' . Mikrotik::resolvePppoeBridgeName($setupConfig) . ') en '
                    . $elapsed . ' s — infra + forfaits. '
                    . (count($actions) ? implode(', ', $actions) : ''),
                'actions' => $actions,
                'errors' => [],
                'elapsed' => $elapsed,
            ]);
            exit(0);
        } catch (Throwable $e) {
            self::markJobFailed($jobPath, 'Échec envoi PPPoE : ' . $e->getMessage());
            exit(1);
        } catch (Exception $e) {
            self::markJobFailed($jobPath, 'Échec envoi PPPoE : ' . $e->getMessage());
            exit(1);
        }
    }

    public static function updateJobProgress(string $jobPath, string $message): void
    {
        $jobPath = trim($jobPath);
        if ($jobPath === '' || !is_file($jobPath)) {
            return;
        }
        $raw = @file_get_contents($jobPath);
        $job = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($job) || (string) ($job['status'] ?? '') !== 'running') {
            return;
        }
        $job['message'] = $message;
        $job['updated_at'] = time();
        @file_put_contents($jobPath, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function finishDeployResult(string $jobPath, array $payload): void
    {
        $jobPath = trim($jobPath);
        if ($jobPath === '') {
            return;
        }
        $startedAt = 0;
        $raw = @file_get_contents($jobPath);
        $existing = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($existing)) {
            $startedAt = (int) ($existing['started_at'] ?? 0);
        }
        @file_put_contents($jobPath, json_encode([
            'status' => 'done',
            'ok' => !empty($payload['ok']),
            'message' => (string) ($payload['message'] ?? ''),
            'actions' => $payload['actions'] ?? [],
            'errors' => $payload['errors'] ?? [],
            'elapsed' => $payload['elapsed'] ?? ($startedAt > 0 ? max(0, time() - $startedAt) : null),
            'finished_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function markJobFailed(string $jobPath, string $message): void
    {
        self::finishDeployResult($jobPath, [
            'ok' => false,
            'message' => $message,
            'errors' => [$message],
            'actions' => [],
        ]);
    }

    public static function staleJobThresholdSeconds(): int
    {
        return 1200;
    }

    private static function routerOwnedByAdmin(array $admin, $routerRow): bool
    {
        if (!$routerRow) {
            return false;
        }
        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return true;
        }
        if (empty($admin['id'])) {
            return false;
        }

        return (int) ($routerRow->admin_id ?? 0) === (int) $admin['id'];
    }
}
