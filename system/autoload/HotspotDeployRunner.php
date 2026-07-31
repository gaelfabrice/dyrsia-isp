<?php

/**
 * Worker CLI pour le déploiement hotspot async — survit à la fin de la requête HTTP.
 */
class HotspotDeployRunner
{
    public static function spawnBackground(string $jobPath): bool
    {
        $jobPath = trim($jobPath);
        if ($jobPath === '' || !is_file($jobPath)) {
            return false;
        }

        $root = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
        $php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $script = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'hotspot-deploy-worker.php';
        if (!is_file($script)) {
            self::markJobFailed($jobPath, 'Worker hotspot introuvable (scripts/hotspot-deploy-worker.php).');

            return false;
        }

        $logFile = $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . 'hotspot_deploy_worker.log';
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobPath)
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1';

        $spawned = false;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $spawned = pclose(popen('start /B ' . $cmd, 'r')) !== false;
        } else {
            $spawned = self::spawnDetachedUnix($php, $script, $jobPath, $logFile);
            if (!$spawned) {
                @exec($cmd . ' > /dev/null 2>&1 &', $spawnOut, $spawnCode);
                $spawned = $spawnCode === 0;
                if (!$spawned) {
                    @exec($cmd . ' & echo $!', $spawnOut2, $spawnCode2);
                    $spawned = $spawnCode2 === 0 && !empty($spawnOut2[0]) && ctype_digit(trim((string) $spawnOut2[0]));
                }
            }
        }
        if (!$spawned) {
            @file_put_contents(
                $logFile,
                date('c') . " spawn failed: {$cmd}\n",
                FILE_APPEND
            );
        }

        return $spawned;
    }

    /**
     * Après réponse JSON au navigateur : exécuter le job (FPM) ou worker CLI (dev-server).
     */
    public static function dispatchJob(string $jobPath, bool $responseAlreadySent = false): void
    {
        if (getenv('WIFIZONE_HOTSPOT_DEPLOY_INLINE') === '1') {
            self::runJob($jobPath);

            return;
        }
        if ($responseAlreadySent && DeployAsyncHttp::canRunHeavyWorkInSameProcess()) {
            self::runJob($jobPath);

            return;
        }
        if (!$responseAlreadySent && self::shouldRunInlineWorker()) {
            self::runJob($jobPath);

            return;
        }
        if (self::spawnBackground($jobPath)) {
            return;
        }
        self::updateJobProgress(
            $jobPath,
            'Worker CLI indisponible — déploiement en cours dans ce processus…'
        );
        self::runJob($jobPath);
    }

    public static function shouldRunInlineWorker(): bool
    {
        if (getenv('WIFIZONE_HOTSPOT_DEPLOY_INLINE') === '1') {
            return true;
        }
        $disabled = strtolower((string) ini_get('disable_functions'));
        if ($disabled !== '' && str_contains($disabled, 'exec')) {
            return true;
        }

        return DeployAsyncHttp::canRunHeavyWorkInSameProcess();
    }

    public static function runJob(string $jobPath): void
    {
        global $config, $UPLOAD_PATH, $_app_stage;

        @ignore_user_abort(true);
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
            self::markJobFailed($jobPath, 'Tâche de déploiement illisible.');
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
            $msg = 'Déploiement hotspot interrompu (processus worker).';
            if ($err && !empty($err['message'])) {
                $msg .= ' ' . $err['message'];
            }
            self::markJobFailed($jobPath, $msg);
        });

        $adminId = (int) ($job['admin_id'] ?? 0);
        $routerName = trim((string) ($job['router'] ?? ''));
        $sendFullDeploy = !empty($job['send_full']);
        if ($adminId <= 0 || $routerName === '') {
            self::markJobFailed($jobPath, 'Tâche de déploiement incomplète (admin ou routeur manquant).');
            exit(1);
        }

        $adminRow = ORM::for_table('tbl_users')->find_one($adminId);
        $admin = Impersonate::adminToArray($adminRow);
        if (!$admin || !in_array($admin['user_type'] ?? '', ['SuperAdmin', 'Admin'], true)) {
            self::markJobFailed($jobPath, 'Compte admin introuvable pour le déploiement hotspot.');
            exit(1);
        }

        if ($_app_stage == 'Demo' || DemoShowcase::isActive($admin)) {
            self::markJobFailed($jobPath, 'Déploiement hotspot désactivé (démo).');
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

        $probe = Mikrotik::probeApiReachable($mikrotik['ip_address'], 5);
        if ($probe !== true) {
            self::markJobFailed(
                $jobPath,
                'API MikroTik injoignable (' . ($mikrotik['ip_address'] ?? '') . ') : ' . $probe
            );
            exit(1);
        }

        WifiZoneHotspot::loadHotspotConfigForDeploy($config, $routerName);

        $hotspotAdminId = WifiZoneHotspot::routerAdminId($routerName);
        if ($hotspotAdminId <= 0) {
            $hotspotAdminId = $adminId;
        }
        $loginFilePath = trim((string) ($job['login_file_path'] ?? ''));
        if ($loginFilePath === '') {
            $loginFilePath = WifiZoneHotspot::hotspotLoginHtmlPath($hotspotAdminId, $UPLOAD_PATH);
        }
        if (!is_file($loginFilePath)) {
            self::markJobFailed($jobPath, 'login.html local introuvable : ' . $loginFilePath);
            exit(1);
        }
        $renderedLoginHtml = file_get_contents($loginFilePath);
        if (!is_string($renderedLoginHtml) || $renderedLoginHtml === '') {
            self::markJobFailed($jobPath, 'login.html local vide ou illisible.');
            exit(1);
        }

        $hotspotDeployFinish = static function ($type, $message) use ($jobPath) {
            self::finishJob($jobPath, $type, $message);
            exit($type === 's' || $type === 'w' ? 0 : 1);
        };

        $pushHotspotPlansToMikrotik = static function ($targetRouter, $existingClient = null) use ($admin) {
            return self::pushPlansToMikrotik($admin, $targetRouter, $existingClient);
        };

        $hotspotDeployJobPath = $jobPath;
        $hotspotDeployProgress = static function ($message) use ($jobPath) {
            self::updateJobProgress($jobPath, (string) $message);
        };

        self::updateJobProgress(
            $jobPath,
            $sendFullDeploy
                ? 'Worker CLI : envoi complet Hotspot sur « ' . $routerName . ' »…'
                : 'Worker CLI : envoi login.html sur « ' . $routerName . ' »…'
        );

        $hotspotDeployExecuteReady = true;
        try {
            require dirname(__DIR__) . '/controllers/hotspot_deploy_execute.inc.php';
            self::markJobFailed($jobPath, 'Déploiement hotspot terminé sans résultat explicite.');
        } catch (Throwable $e) {
            $hotspotDeployFinish('e', 'Échec de l\'envoi vers MikroTik (' . $routerName . ') : ' . $e->getMessage());
        } catch (Exception $e) {
            $hotspotDeployFinish('e', 'Échec de l\'envoi vers MikroTik (' . $routerName . ') : ' . $e->getMessage());
        }
    }

    public static function pushPlansToMikrotik(array $admin, $routerName, $existingClient = null): array
    {
        global $_app_stage;

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return ['ok' => false, 'message' => 'Aucun routeur sélectionné.'];
        }
        if ($_app_stage == 'Demo' || DemoShowcase::isActive($admin)) {
            return ['ok' => false, 'message' => 'Synchronisation des forfaits désactivée (démo).'];
        }

        $mikrotik = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if (!$mikrotik || !self::routerOwnedByAdmin($admin, $mikrotik)) {
            return ['ok' => false, 'message' => Lang::T('Router not found')];
        }

        try {
            $client = $existingClient;
            if (!$client) {
                $client = Mikrotik::getClient(
                    $mikrotik['ip_address'],
                    $mikrotik['username'],
                    $mikrotik['password'],
                    45
                );
            }
            if (!$client) {
                return ['ok' => false, 'message' => 'Connexion MikroTik impossible.'];
            }

            $result = Mikrotik::syncHotspotPlans($client, $routerName, $admin);
            if (empty($result['ok'])) {
                return [
                    'ok' => false,
                    'message' => 'Synchronisation des forfaits Hotspot échouée : '
                        . implode(' | ', $result['errors'] ?? ['erreur inconnue']),
                ];
            }

            $dbPlanCount = (int) WifiZoneHotspot::plansQueryForRouter($routerName)->count();
            if ($dbPlanCount === 0) {
                return [
                    'ok' => false,
                    'message' => 'Aucun forfait Hotspot actif pour « ' . $routerName . ' ».',
                ];
            }

            return [
                'ok' => true,
                'message' => 'Forfaits synchronisés.',
                'result' => $result,
                'db_plan_count' => $dbPlanCount,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
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
        @file_put_contents($jobPath, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function finishJob(string $jobPath, string $type, string $message): void
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
            'ok' => in_array($type, ['s', 'w'], true),
            'notify_type' => $type,
            'message' => $message,
            'finished_at' => time(),
            'elapsed' => $startedAt > 0 ? max(0, time() - $startedAt) : null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function markJobFailed(string $jobPath, string $message): void
    {
        self::finishJob($jobPath, 'e', $message);
    }

    public static function staleJobThresholdSeconds(): int
    {
        return 720;
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
        $routerAdminId = is_array($routerRow)
            ? ($routerRow['admin_id'] ?? 0)
            : ($routerRow->admin_id ?? 0);

        return (int) $routerAdminId === (int) $admin['id'];
    }

    private static function spawnDetachedUnix(string $php, string $script, string $jobPath, string $logFile): bool
    {
        if (!function_exists('exec') || stripos((string) ini_get('disable_functions'), 'exec') !== false) {
            return false;
        }
        $shellCmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobPath)
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1';
        @exec('setsid sh -c ' . escapeshellarg($shellCmd) . ' < /dev/null & echo $!', $spawnOut, $spawnCode);

        return $spawnCode === 0 && !empty($spawnOut[0]) && ctype_digit(trim((string) $spawnOut[0]));
    }
}
