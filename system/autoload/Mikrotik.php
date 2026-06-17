<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

use PEAR2\Net\RouterOS;

class Mikrotik
{
    public static function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    /**
     * Split "host", "host:8728" or "[ipv6]:8728" into host + API port.
     *
     * @return array{host: string, port: int}
     */
    public static function parseEndpoint($ipAddress, $defaultPort = 8728)
    {
        $ipAddress = trim((string) $ipAddress);
        $defaultPort = (int) $defaultPort ?: 8728;

        if ($ipAddress === '') {
            return ['host' => '', 'port' => $defaultPort];
        }

        if (preg_match('/^\[([^\]]+)\]:(\d+)$/', $ipAddress, $m)) {
            return ['host' => $m[1], 'port' => (int) $m[2]];
        }

        if (substr_count($ipAddress, ':') === 1) {
            [$host, $port] = explode(':', $ipAddress, 2);
            $host = trim($host);
            if ($host !== '' && ctype_digit($port)) {
                return ['host' => $host, 'port' => (int) $port];
            }
        }

        return ['host' => $ipAddress, 'port' => $defaultPort];
    }

    /**
     * @return array<int, array{port: int, ssl: bool, label: string}>
     */
    private static function mikrotikConnectionAttempts($port)
    {
        $port = (int) $port ?: 8728;
        return [
            ['port' => $port, 'ssl' => false, 'label' => 'API'],
        ];
    }

    private static function isRetriableMikrotikConnectionError(Throwable $e)
    {
        if ($e instanceof RouterOS\DataFlowException
            && $e->getCode() === RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
            return false;
        }

        $message = strtolower($e->getMessage());
        if (strpos($message, 'invalid username or password') !== false) {
            return false;
        }

        if ($e instanceof RouterOS\SocketException
            && $e->getCode() === RouterOS\SocketException::CODE_SERVICE_INCOMPATIBLE) {
            return true;
        }

        return strpos($message, 'not a compatible routeros service') !== false
            || strpos($message, 'error connecting to routeros') !== false;
    }

    /**
     * Fast pre-flight TCP reachability test so we never block on a long OS-level
     * connect timeout (which would exceed PHP max_execution_time and yield HTTP 500).
     *
     * @return true|string  true if reachable, otherwise a short error string.
     */
    private static function probeTcp($host, $port, $timeout = 4)
    {
        $host = trim((string) $host);
        $port = (int) $port;
        if ($host === '' || $port <= 0) {
            return 'hôte ou port invalide';
        }

        $errno = 0;
        $errstr = '';
        $prev = error_reporting(error_reporting() & ~E_WARNING);
        $sock = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            max(1, (float) $timeout),
            STREAM_CLIENT_CONNECT
        );
        error_reporting($prev);

        if ($sock === false) {
            $reason = trim($errstr) !== '' ? trim($errstr) : ('erreur ' . $errno);
            return 'TCP injoignable ' . $host . ':' . $port . ' — ' . $reason;
        }

        fclose($sock);
        return true;
    }

    /**
     * Non-blocking DNS lookup for firewall rules (avoid gethostbyname stalls during deploy).
     */
    private static function resolveHostIpv4Fast($host)
    {
        $host = trim((string) $host);
        if ($host === '') {
            return null;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }
        $records = @dns_get_record($host, DNS_A);
        if (is_array($records)) {
            foreach ($records as $rec) {
                if (!empty($rec['ip']) && filter_var($rec['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $rec['ip'];
                }
            }
        }
        return null;
    }

    /**
     * PPPoE profile local-address must be the router gateway, never a client IP from the pool range.
     */
    public static function resolvePoolGatewayAddress($pool)
    {
        if (is_object($pool) && method_exists($pool, 'as_array')) {
            $pool = $pool->as_array();
        }
        if (!is_array($pool)) {
            $pool = [];
        }

        $localIp = trim((string) ($pool['local_ip'] ?? ''));
        $range = trim((string) ($pool['range_ip'] ?? ''));
        $gateway = self::deriveGatewayFromPoolRange($range);

        if ($localIp !== '' && filter_var($localIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($range !== '' && self::ipv4InPoolRange($localIp, $range)) {
                return $gateway !== '' ? $gateway : $localIp;
            }

            return $localIp;
        }

        return $gateway !== '' ? $gateway : '10.10.10.1';
    }

    private static function deriveGatewayFromPoolRange($range)
    {
        $range = trim((string) $range);
        if ($range === '') {
            return '';
        }
        if (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)\s*-\s*\d+\.\d+\.\d+\.(\d+)$/i', $range, $m)) {
            return $m[1] . '1';
        }
        if (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)\s*-\s*(\d+)$/i', $range, $m)) {
            return ((int) $m[2] > 1) ? ($m[1] . '1') : '';
        }

        return '';
    }

    private static function ipv4InPoolRange($ip, $range)
    {
        $ipLong = ip2long((string) $ip);
        if ($ipLong === false) {
            return false;
        }
        $range = trim((string) $range);
        if (preg_match('/^(\d+\.\d+\.\d+\.\d+)\s*-\s*(\d+\.\d+\.\d+\.\d+)$/i', $range, $m)) {
            $start = ip2long($m[1]);
            $end = ip2long($m[2]);
        } elseif (preg_match('/^(\d+\.\d+\.\d+\.)(\d+)\s*-\s*(\d+)$/i', $range, $m)) {
            $start = ip2long($m[1] . $m[2]);
            $end = ip2long($m[1] . $m[3]);
        } else {
            return false;
        }

        return $start !== false && $end !== false && $ipLong >= $start && $ipLong <= $end;
    }

    private static function formatMikrotikConnectionHelp($host, $port)
    {
        $hints = [
            'Vérifiez sur le routeur : /ip service print — le service « api » doit être activé (port 8728) ou « api-ssl » (port 8729).',
            'Commandes : /ip service enable api puis /ip service set api port=8728 disabled=no',
            'Utilisateur API : System → Users → groupe « full » ou « api » (pas seulement winbox).',
        ];

        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', (string) $host)) {
            $hints[] = 'IP privée (' . $host . ') : le serveur DYRSIA doit être sur le même réseau/VPN que le MikroTik. Depuis wifizones.org, utilisez l’IP publique du routeur ou un tunnel VPN.';
        }

        return Lang::T('Cannot connect to MikroTik')
            . ' (' . $host . ':' . $port . '). '
            . implode(' ', $hints);
    }

    public static function routerPassword($storedPassword)
    {
        $password = (string) $storedPassword;
        if (function_exists('lcg_decrypt')) {
            $password = rtrim(lcg_decrypt($password));
        } elseif (class_exists('Encryption') && method_exists('Encryption', 'decrypt')) {
            $password = rtrim(Encryption::decrypt($password));
        }

        return $password;
    }

    public static function getClient($ip, $user, $pass, $timeout = 5)
    {
        global $_app_stage, $admin;
        if ($_app_stage == 'demo' || DemoShowcase::blocksRouterSync($admin ?? null)) {
            return null;
        }

        $endpoint = self::parseEndpoint($ip);
        if ($endpoint['host'] === '') {
            throw new Exception(Lang::T('Router IP address is empty'));
        }

        $user = trim((string) $user);
        $pass = (string) $pass;
        $attempts = self::mikrotikConnectionAttempts($endpoint['port']);
        $lastError = null;
        $prevErrorLevel = error_reporting(error_reporting() & ~E_DEPRECATED);

        foreach ($attempts as $attempt) {
            $probeTimeout = min(5, max(2, (int) ceil($timeout / 3)));
            $probe = self::probeTcp($endpoint['host'], $attempt['port'], $probeTimeout);
            if ($probe !== true) {
                throw new Exception(
                    self::formatMikrotikConnectionHelp($endpoint['host'], $attempt['port'])
                    . ' (' . $probe . ')'
                );
            }
            try {
                $client = new RouterOS\Client(
                    $endpoint['host'],
                    $user,
                    $pass,
                    $attempt['port'],
                    false,
                    $timeout,
                    $attempt['ssl']
                        ? PEAR2\Net\Transmitter\NetworkStream::CRYPTO_TLS
                        : PEAR2\Net\Transmitter\NetworkStream::CRYPTO_OFF
                );
                error_reporting($prevErrorLevel);
                return $client;
            } catch (RouterOS\DataFlowException $e) {
                $lastError = $e;
                error_reporting($prevErrorLevel);
                if ($e->getCode() === RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
                    throw new Exception(
                        Lang::T('Cannot connect to MikroTik')
                        . ' (' . $endpoint['host'] . ':' . $attempt['port'] . '): '
                        . Lang::T('Invalid API username or password')
                        . '. '
                        . Lang::T('Create or verify a user under System → Users with API rights (group full or api).')
                    );
                }
                if (!self::isRetriableMikrotikConnectionError($e)) {
                    break;
                }
            } catch (Throwable $e) {
                $lastError = $e;
                if (!self::isRetriableMikrotikConnectionError($e)) {
                    break;
                }
            } catch (Exception $e) {
                $lastError = $e;
                if (!self::isRetriableMikrotikConnectionError($e)) {
                    break;
                }
            }
        }

        error_reporting($prevErrorLevel ?? E_ALL);

        if ($lastError instanceof RouterOS\DataFlowException
            && $lastError->getCode() === RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
            throw new Exception(
                Lang::T('Cannot connect to MikroTik')
                . ' (' . $endpoint['host'] . ':' . $endpoint['port'] . '): '
                . Lang::T('Invalid API username or password')
                . '. '
                . Lang::T('Create or verify a user under System → Users with API rights (group full or api).')
            );
        }

        $detail = $lastError ? $lastError->getMessage() : 'connexion impossible';
        if (strpos($detail, Lang::T('Cannot connect to MikroTik')) !== false) {
            throw new Exception($detail);
        }
        throw new Exception(self::formatMikrotikConnectionHelp($endpoint['host'], $endpoint['port']) . ' (' . $detail . ')');
    }

    public static function isUserLogin($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    public static function logMeIn($client, $user, $pass, $ip, $mac)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $user)
                ->setArgument('password', $pass)
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac)
        );
    }

    public static function logMeOut($client, $user)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $user)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    public static function addHotspotPlan($client, $name, $sharedusers, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('shared-users', $sharedusers)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setHotspotPlan($client, $name, $sharedusers, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            Mikrotik::addHotspotPlan($client, $name, $sharedusers, $rate);
        } else {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', $sharedusers)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function setHotspotExpiredPlan($client, $name, $pool)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $name)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '512K/512K')
            );
        } else {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '512K/512K')
            );
        }
    }

    public static function removeHotspotPlan($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            return null;
        }

        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    /**
     * Build MikroTik hotspot profile rate-limit from a tbl_bandwidth row.
     */
    public static function hotspotPlanRateLimit($bw)
    {
        if (!$bw) {
            return '';
        }

        $unitdown = ($bw['rate_down_unit'] ?? '') === 'Kbps' ? 'K' : 'M';
        $unitup = ($bw['rate_up_unit'] ?? '') === 'Kbps' ? 'K' : 'M';
        if (($bw['rate_up'] ?? '0') == '0' || ($bw['rate_down'] ?? '0') == '0') {
            return '';
        }

        $rate = $bw['rate_up'] . $unitup . '/' . $bw['rate_down'] . $unitdown;
        if (!empty(trim((string) ($bw['burst'] ?? '')))) {
            $rate .= ' ' . trim((string) $bw['burst']);
        }

        return $rate;
    }

    /**
     * Upsert enabled Hotspot plans on the router and remove orphan user profiles.
     *
     * @return array{ok: bool, upserted: int, removed: int, errors: array<int, string>}
     */
    public static function syncHotspotPlans($client, $routerName, $admin = null)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'upserted' => 0, 'removed' => 0, 'errors' => []];
        }

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return ['ok' => false, 'upserted' => 0, 'removed' => 0, 'errors' => ['Router name required']];
        }

        $plansQuery = ORM::for_table('tbl_plans')
            ->where('type', 'Hotspot')
            ->where('enabled', 1)
            ->where('routers', $routerName)
            ->where('is_radius', 0);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $plansQuery->where('admin_id', (int) ($admin['id'] ?? 0));
        }

        $expectedNames = [];
        $upserted = 0;
        $errors = [];

        foreach ($plansQuery->find_many() as $plan) {
            $name = trim((string) ($plan['name_plan'] ?? ''));
            if ($name === '') {
                continue;
            }
            $expectedNames[] = $name;

            $bw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
            $rate = self::hotspotPlanRateLimit($bw);
            $sharedUsers = (int) ($plan['shared_users'] ?? 1);
            if ($sharedUsers < 1) {
                $sharedUsers = 1;
            }

            try {
                self::setHotspotPlan($client, $name, $sharedUsers, $rate);
                $upserted++;
            } catch (Throwable $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        $protectedProfiles = ['default'];
        $removed = 0;

        try {
            $printRequest = new RouterOS\Request('/ip/hotspot/user/profile/print');
            $printRequest->setArgument('.proplist', '.id,name');
            $profiles = $client->sendSync($printRequest);

            foreach ($profiles as $profile) {
                $profileName = trim((string) $profile->getProperty('name'));
                if ($profileName === '' || in_array($profileName, $protectedProfiles, true)) {
                    continue;
                }
                if (in_array($profileName, $expectedNames, true)) {
                    continue;
                }

                $profileId = $profile->getProperty('.id');
                if (empty($profileId)) {
                    continue;
                }

                try {
                    $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
                    $client->sendSync($removeRequest->setArgument('numbers', $profileId));
                    $removed++;
                } catch (Throwable $e) {
                    $errors[] = 'remove ' . $profileName . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    $errors[] = 'remove ' . $profileName . ': ' . $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'list profiles: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'list profiles: ' . $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'upserted' => $upserted,
            'removed' => $removed,
            'errors' => $errors,
        ];
    }

    /**
     * Whether a plan row is the protected PPPoE EXPIRE system plan.
     */
    public static function isPppoeSystemExpirePlan($plan)
    {
        if (is_object($plan) && method_exists($plan, 'as_array')) {
            $plan = $plan->as_array();
        }
        if (!is_array($plan)) {
            return false;
        }

        return strtoupper((string) ($plan['type'] ?? '')) === 'PPPOE'
            && strtoupper(trim((string) ($plan['name_plan'] ?? ''))) === 'EXPIRE';
    }

    /**
     * Ensure EXPIRE PPPoE plan exists in DB and is linked from other PPPoE plans on the router.
     *
     * @return array{ok: bool, plan_id: int, linked: int, errors: array<int, string>}
     */
    public static function ensurePppoeExpiredPlanDb($routerName, $admin = null)
    {
        global $_app_stage;
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return ['ok' => false, 'plan_id' => 0, 'linked' => 0, 'errors' => ['Router name required']];
        }
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'plan_id' => 0, 'linked' => 0, 'errors' => []];
        }

        $refPlan = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('enabled', 1)
            ->where_not_equal('name_plan', 'EXPIRE')
            ->find_one();
        if (!$refPlan) {
            $refPlan = ORM::for_table('tbl_plans')
                ->where('type', 'PPPOE')
                ->where('routers', $routerName)
                ->where_not_equal('name_plan', 'EXPIRE')
                ->find_one();
        }

        $expirePlan = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('name_plan', 'EXPIRE')
            ->find_one();

        if (!$expirePlan) {
            if (!$refPlan) {
                return ['ok' => false, 'plan_id' => 0, 'linked' => 0, 'errors' => ['Aucun forfait PPPoE sur ce routeur']];
            }
            $adminId = is_array($admin) ? (int) ($admin['id'] ?? $refPlan['admin_id']) : (int) $refPlan['admin_id'];
            $expirePlan = ORM::for_table('tbl_plans')->create();
            $expirePlan->admin_id = $adminId;
            $expirePlan->name_plan = 'EXPIRE';
            $expirePlan->id_bw = $refPlan['id_bw'];
            $expirePlan->price = 0;
            $expirePlan->type = 'PPPOE';
            $expirePlan->validity = 1;
            $expirePlan->validity_unit = 'Days';
            $expirePlan->routers = $routerName;
            $expirePlan->pool = $refPlan['pool'];
            $expirePlan->enabled = 1;
            $expirePlan->allow_purchase = 'no';
            $expirePlan->prepaid = 'yes';
            $expirePlan->plan_type = 'Personal';
            $expirePlan->device = 'MikrotikPppoe';
            $expirePlan->plan_expired = 0;
            $expirePlan->expired_date = 0;
            $expirePlan->save();
        } else {
            $changed = false;
            if ((int) $expirePlan->enabled !== 1) {
                $expirePlan->enabled = 1;
                $changed = true;
            }
            if ((string) $expirePlan->allow_purchase !== 'no') {
                $expirePlan->allow_purchase = 'no';
                $changed = true;
            }
            if ((float) $expirePlan->price != 0.0) {
                $expirePlan->price = 0;
                $changed = true;
            }
            if ($refPlan) {
                if (empty($expirePlan->pool)) {
                    $expirePlan->pool = $refPlan['pool'];
                    $changed = true;
                }
                if (empty($expirePlan->id_bw)) {
                    $expirePlan->id_bw = $refPlan['id_bw'];
                    $changed = true;
                }
            }
            if ($changed) {
                $expirePlan->save();
            }
        }

        $adminId = is_array($admin) ? (int) ($admin['id'] ?? $expirePlan['admin_id']) : (int) $expirePlan['admin_id'];
        $linked = 0;
        $linkQuery = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('enabled', 1)
            ->where_not_equal('id', (int) $expirePlan->id)
            ->where_not_equal('name_plan', 'EXPIRE');
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $linkQuery->where('admin_id', $adminId);
        }
        foreach ($linkQuery->find_many() as $planRow) {
            if ((int) $planRow['plan_expired'] !== (int) $expirePlan->id) {
                $planRow->plan_expired = (int) $expirePlan->id;
                $planRow->save();
                $linked++;
            }
        }

        return [
            'ok' => true,
            'plan_id' => (int) $expirePlan->id,
            'linked' => $linked,
            'errors' => [],
        ];
    }

    /**
     * Ensure EXPIRE PPPoE plan exists in DB, linked to active plans, and synced on MikroTik.
     *
     * @return array{ok: bool, plan_id: int, linked: int, errors: array<int, string>}
     */
    public static function ensurePppoeExpiredPlan($client, $routerName, $admin = null)
    {
        global $_app_stage;
        $dbResult = self::ensurePppoeExpiredPlanDb($routerName, $admin);
        if (!$dbResult['ok'] || $dbResult['plan_id'] === 0) {
            return $dbResult;
        }

        $expirePlan = ORM::for_table('tbl_plans')->find_one($dbResult['plan_id']);
        if (!$expirePlan) {
            return ['ok' => false, 'plan_id' => 0, 'linked' => 0, 'errors' => ['EXPIRE plan not found']];
        }

        $errors = [];
        if ($_app_stage != 'demo' && $_app_stage != 'Demo' && $client) {
            try {
                $deviceFile = Package::getDevice($expirePlan->as_array());
                if (file_exists($deviceFile)) {
                    require_once $deviceFile;
                    $device = new MikrotikPppoe();
                    if (!method_exists($device, 'add_plan')) {
                        $device->update_plan(['name_plan' => 'EXPIRE'], $expirePlan->as_array());
                    } else {
                        $existing = ORM::for_table('tbl_plans')->find_one($expirePlan->id);
                        $printRequest = new RouterOS\Request('/ppp/profile/print');
                        $printRequest->setArgument('.proplist', '.id');
                        $printRequest->setQuery(RouterOS\Query::where('name', 'EXPIRE'));
                        $profileId = $client->sendSync($printRequest)->getProperty('.id');
                        if (empty($profileId)) {
                            $device->add_plan($expirePlan->as_array());
                        } else {
                            $device->update_plan(['name_plan' => 'EXPIRE'], $expirePlan->as_array());
                        }
                    }
                }
            } catch (Throwable $e) {
                $errors[] = 'EXPIRE profile: ' . $e->getMessage();
            }
        }

        return [
            'ok' => empty($errors),
            'plan_id' => (int) $expirePlan->id,
            'linked' => $dbResult['linked'],
            'errors' => $errors,
        ];
    }

    /**
     * Upsert PPPoE profiles on MikroTik and remove orphan profiles.
     *
     * @return array{ok: bool, upserted: int, removed: int, errors: array<int, string>}
     */
    public static function syncPppoePlans($client, $routerName, $admin = null)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'upserted' => 0, 'removed' => 0, 'errors' => []];
        }

        $routerName = trim((string) $routerName);
        self::ensurePppoeExpiredPlanDb($routerName, $admin);

        $plansQuery = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('enabled', 1)
            ->where('routers', $routerName)
            ->where('is_radius', 0);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $plansQuery->where('admin_id', (int) ($admin['id'] ?? 0));
        }

        $expectedNames = ['default', 'EXPIRE'];
        $upserted = 0;
        $errors = [];
        $expiredOnUp = ':if ($remote-address!="") do={ /ip firewall address-list add list=pppoe-expired address=$remote-address comment=$user }';
        $expiredOnDown = ':if ($remote-address!="") do={ /ip firewall address-list remove [find list=pppoe-expired address=$remote-address] }';

        foreach ($plansQuery->find_many() as $plan) {
            $name = trim((string) ($plan['name_plan'] ?? ''));
            if ($name === '') {
                continue;
            }
            $expectedNames[] = $name;

            $bw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
            $rate = ($name === 'EXPIRE') ? '128k/128k' : self::hotspotPlanRateLimit($bw);
            $pool = ORM::for_table('tbl_pool')->where('pool_name', $plan['pool'])->find_one();
            $localAddress = self::resolvePoolGatewayAddress($pool ? $pool->as_array() : []);
            $remoteAddress = $pool['pool_name'] ?? '';

            $isExpiredProfile = ($name === 'EXPIRE')
                || ORM::for_table('tbl_plans')->where('plan_expired', (int) $plan['id'])->find_one();

            try {
                $printRequest = new RouterOS\Request('/ppp/profile/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('name', $name));
                $profileId = $client->sendSync($printRequest)->getProperty('.id');

                $args = [
                    'name' => $name,
                    'local-address' => $localAddress,
                    'remote-address' => $remoteAddress,
                    'rate-limit' => $rate,
                ];
                if ($isExpiredProfile) {
                    $args['on-up'] = $expiredOnUp;
                    $args['on-down'] = $expiredOnDown;
                }

                if (empty($profileId)) {
                    $addRequest = new RouterOS\Request('/ppp/profile/add');
                    foreach ($args as $key => $value) {
                        $addRequest->setArgument($key, $value);
                    }
                    $client->sendSync($addRequest);
                    $profileId = $client->sendSync(
                        (new RouterOS\Request('/ppp/profile/print'))
                            ->setArgument('.proplist', '.id')
                            ->setQuery(RouterOS\Query::where('name', $name))
                    )->getProperty('.id');
                } else {
                    $setRequest = new RouterOS\Request('/ppp/profile/set');
                    $setRequest->setArgument('numbers', $profileId);
                    foreach ($args as $key => $value) {
                        $setRequest->setArgument($key, $value);
                    }
                    $client->sendSync($setRequest);
                }
                if (!$isExpiredProfile && !empty($profileId)) {
                    foreach (['on-up', 'on-down'] as $scriptName) {
                        try {
                            $unsetRequest = new RouterOS\Request('/ppp/profile/unset');
                            $unsetRequest->setArgument('numbers', $profileId);
                            $unsetRequest->setArgument('value-name', $scriptName);
                            $client->sendSync($unsetRequest);
                        } catch (Throwable $e) {
                        }
                    }
                }
                $upserted++;
            } catch (Throwable $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        $removed = 0;
        try {
            $printRequest = new RouterOS\Request('/ppp/profile/print');
            $printRequest->setArgument('.proplist', '.id,name');
            $profiles = $client->sendSync($printRequest);
            foreach ($profiles as $profile) {
                $profileName = trim((string) $profile->getProperty('name'));
                if ($profileName === '' || in_array($profileName, $expectedNames, true)) {
                    continue;
                }
                $profileId = $profile->getProperty('.id');
                if (empty($profileId)) {
                    continue;
                }
                try {
                    $removeRequest = new RouterOS\Request('/ppp/profile/remove');
                    $client->sendSync($removeRequest->setArgument('numbers', $profileId));
                    $removed++;
                } catch (Throwable $e) {
                    $errors[] = 'remove ' . $profileName . ': ' . $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'list ppp profiles: ' . $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'upserted' => $upserted,
            'removed' => $removed,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve backend IPv4 for PPPoE captive NAT/DNS (APP_URL host).
     */
    public static function resolveAppBackendIpv4($appUrl)
    {
        $appUrl = trim((string) $appUrl);
        if ($appUrl === '') {
            return null;
        }
        $host = parse_url($appUrl, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $host;
        }
        $ip = self::resolveHostIpv4Fast($host);
        return ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? $ip : null;
    }

    public static function resolveAppBackendPort($appUrl, $forHttps = false)
    {
        $appUrl = trim((string) $appUrl);
        $port = (int) parse_url($appUrl, PHP_URL_PORT);
        if ($port > 0) {
            return $port;
        }
        if ($forHttps) {
            return 443;
        }

        return 80;
    }

    private static function ensureNatRuleByComment($client, $comment, array $args)
    {
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/nat/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
            if ($client->sendSync($printRequest)->getProperty('.id')) {
                return true;
            }
            $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
            foreach ($args as $key => $value) {
                $addRequest->setArgument($key, $value);
            }
            $addRequest->setArgument('comment', $comment);
            $client->sendSync($addRequest);

            return true;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    private static function ensurePppoeCaptiveDetectionDns($client, $backendIp)
    {
        $backendIp = trim((string) $backendIp);
        if ($backendIp === '') {
            return [];
        }

        $errors = [];
        $hosts = [
            'connectivitycheck.gstatic.com',
            'clients3.google.com',
            'www.msftconnecttest.com',
            'msftconnecttest.com',
            'captive.apple.com',
            'www.apple.com',
            'detectportal.firefox.com',
            'neverssl.com',
        ];
        foreach ($hosts as $host) {
            $result = self::ensureHotspotDnsStatic($client, $host, $backendIp);
            if (empty($result['ok'])) {
                $errors[] = $host . ': ' . implode(' | ', $result['errors'] ?? ['dns static']);
            }
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/dns/set'))
                    ->setArgument('allow-remote-requests', 'yes')
            );
        } catch (Throwable $e) {
            $errors[] = 'dns allow-remote-requests: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Sync pppoe-expired address-list entries into customer fields (for captive portal intercept).
     */
    public static function syncPppoeExpiredClientMeta($client, $routerName)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return 0;
        }

        $routerName = trim((string) $routerName);
        $updated = 0;
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/address-list/print');
            $printRequest->setArgument('.proplist', 'address,comment');
            $printRequest->setQuery(RouterOS\Query::where('list', 'pppoe-expired'));
            foreach ($client->sendSync($printRequest) as $row) {
                $ip = trim((string) $row->getProperty('address'));
                $login = trim((string) $row->getProperty('comment'));
                if ($ip === '' || $login === '') {
                    continue;
                }
                $customer = ORM::for_table('tbl_customers')->where('pppoe_username', $login)->find_one();
                if (!$customer) {
                    $customer = ORM::for_table('tbl_customers')->where('username', $login)->find_one();
                }
                if (!$customer) {
                    continue;
                }
                User::setAttribute('pppoe_expired_ip', $ip, (int) $customer->id);
                if ($routerName !== '') {
                    User::setAttribute('pppoe_expired_router', $routerName, (int) $customer->id);
                }
                User::setAttribute('pppoe_expired_user', $login, (int) $customer->id);
                $updated++;
            }
        } catch (Throwable $e) {
            _log('[PPPoE captive] sync meta: ' . $e->getMessage());
        }

        return $updated;
    }

    /**
     * Firewall + walled-garden for expired PPPoE clients (address-list pppoe-expired).
     *
     * @return array{ok: bool, errors: array<int, string>, portal_url: string}
     */
    public static function ensurePppoeExpiredCaptive($client, $portalUrl, $appUrl = '', $routerName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'portal_url' => $portalUrl];
        }

        $portalUrl = trim((string) $portalUrl);
        $appUrl = trim((string) ($appUrl ?: (defined('APP_URL') ? APP_URL : '')));
        $errors = [];

        if ($portalUrl !== '') {
            $wg = self::ensureHotspotWalledGarden($client, $portalUrl);
            if (empty($wg['ok'])) {
                $errors = array_merge($errors, $wg['errors'] ?? ['walled-garden portal']);
            }
        }
        if ($appUrl !== '') {
            $extras = self::ensureHotspotCaptiveExtrasWalledGarden($client, $appUrl);
            if (empty($extras['ok'])) {
                $errors = array_merge($errors, $extras['errors'] ?? ['walled-garden extras']);
            }
        }

        $commentTag = 'DYRSIA-PPPOE-EXPIRED';
        $allowHosts = [];
        if ($portalUrl !== '') {
            $portalHost = parse_url($portalUrl, PHP_URL_HOST);
            if ($portalHost) {
                $allowHosts[] = $portalHost;
            }
        }
        if ($appUrl !== '') {
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            if ($appHost) {
                $allowHosts[] = $appHost;
            }
        }
        foreach (['wifizones.org', 'www.wifizones.org', 'campay.net', 'demo.campay.net', 'cdn.jsdelivr.net'] as $host) {
            $allowHosts[] = $host;
        }
        $allowHosts = array_values(array_unique(array_filter($allowHosts)));

        $firstForwardRuleId = null;
        try {
            $firstRuleRequest = new RouterOS\Request('/ip/firewall/filter/print');
            $firstRuleRequest->setArgument('.proplist', '.id');
            $firstRuleRequest->setQuery(RouterOS\Query::where('chain', 'forward'));
            $firstForwardRuleId = $client->sendSync($firstRuleRequest)->getProperty('.id');
        } catch (Throwable $e) {
            $firstForwardRuleId = null;
        }

        foreach ($allowHosts as $host) {
            $comment = $commentTag . ' allow ' . $host;
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
                if ($client->sendSync($printRequest)->getProperty('.id')) {
                    continue;
                }
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                $addRequest->setArgument('chain', 'forward');
                $addRequest->setArgument('action', 'accept');
                $addRequest->setArgument('src-address-list', 'pppoe-expired');
                $resolvedIp = self::resolveHostIpv4Fast($host);
                if ($resolvedIp && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
                    $addRequest->setArgument('dst-address', $resolvedIp);
                } else {
                    continue;
                }
                $addRequest->setArgument('comment', $comment);
                if (!empty($firstForwardRuleId)) {
                    $addRequest->setArgument('place-before', $firstForwardRuleId);
                }
                $client->sendSync($addRequest);
            } catch (Throwable $e) {
                $errors[] = $comment . ': ' . $e->getMessage();
            }
        }

        $rules = [
            [
                'chain' => 'forward',
                'action' => 'accept',
                'comment' => $commentTag . ' allow established',
                'connection-state' => 'established,related',
                'src-address-list' => 'pppoe-expired',
            ],
            [
                'chain' => 'forward',
                'action' => 'accept',
                'comment' => $commentTag . ' allow DNS',
                'protocol' => 'udp',
                'dst-port' => '53',
                'src-address-list' => 'pppoe-expired',
            ],
            [
                'chain' => 'forward',
                'action' => 'drop',
                'comment' => $commentTag . ' block internet',
                'src-address-list' => 'pppoe-expired',
            ],
        ];

        foreach ($rules as $rule) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $rule['comment']));
                $existing = $client->sendSync($printRequest)->getProperty('.id');
                if (!empty($existing)) {
                    continue;
                }
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                foreach ($rule as $key => $value) {
                    if ($key === 'comment') {
                        continue;
                    }
                    $addRequest->setArgument($key, $value);
                }
                $addRequest->setArgument('comment', $rule['comment']);
                if (!empty($firstForwardRuleId)) {
                    $addRequest->setArgument('place-before', $firstForwardRuleId);
                }
                $client->sendSync($addRequest);
            } catch (Throwable $e) {
                $errors[] = $rule['comment'] . ': ' . $e->getMessage();
            }
        }

        self::removeFirewallRulesByComment($client, $commentTag . ' redirect http to portal');

        $backendIp = self::resolveAppBackendIpv4($appUrl);
        if ($backendIp && in_array($backendIp, ['127.0.0.1', '0.0.0.0'], true)) {
            $errors[] = $commentTag . ': APP_URL pointe vers localhost — utilisez l\'URL publique (ex. https://wifizones.org)';
            $backendIp = null;
        }
        if ($backendIp) {
            $allowHosts[] = $backendIp;
            $allowHosts = array_values(array_unique(array_filter($allowHosts)));

            $httpPort = self::resolveAppBackendPort($appUrl, false);
            $httpsPort = self::resolveAppBackendPort($appUrl, true);

            $natRules = [
                $commentTag . ' redirect http captive' => [
                    'chain' => 'dstnat',
                    'protocol' => 'tcp',
                    'dst-port' => '80',
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'dst-nat',
                    'to-addresses' => $backendIp,
                    'to-ports' => (string) $httpPort,
                ],
                $commentTag . ' redirect https captive' => [
                    'chain' => 'dstnat',
                    'protocol' => 'tcp',
                    'dst-port' => '443',
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'dst-nat',
                    'to-addresses' => $backendIp,
                    'to-ports' => (string) $httpsPort,
                ],
                $commentTag . ' redirect dns' => [
                    'chain' => 'dstnat',
                    'protocol' => 'udp',
                    'dst-port' => '53',
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'redirect',
                    'to-ports' => '53',
                ],
            ];
            foreach ($natRules as $comment => $args) {
                $result = self::ensureNatRuleByComment($client, $comment, $args);
                if ($result !== true) {
                    $errors[] = $comment . ': ' . $result;
                }
            }

            $dnsErrors = self::ensurePppoeCaptiveDetectionDns($client, $backendIp);
            $errors = array_merge($errors, $dnsErrors);

            foreach ($allowHosts as $host) {
                if (!filter_var($host, FILTER_VALIDATE_IP)) {
                    continue;
                }
                $comment = $commentTag . ' allow ' . $host;
                try {
                    $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                    $printRequest->setArgument('.proplist', '.id');
                    $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
                    if ($client->sendSync($printRequest)->getProperty('.id')) {
                        continue;
                    }
                    $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                    $addRequest->setArgument('chain', 'forward');
                    $addRequest->setArgument('action', 'accept');
                    $addRequest->setArgument('src-address-list', 'pppoe-expired');
                    $addRequest->setArgument('dst-address', $host);
                    $addRequest->setArgument('comment', $comment);
                    if (!empty($firstForwardRuleId)) {
                        $addRequest->setArgument('place-before', $firstForwardRuleId);
                    }
                    $client->sendSync($addRequest);
                } catch (Throwable $e) {
                    $errors[] = $comment . ': ' . $e->getMessage();
                }
            }
        } elseif ($appUrl !== '') {
            $errors[] = $commentTag . ': IP backend introuvable pour ' . $appUrl . ' (vérifiez APP_URL)';
        }

        if ($routerName !== '') {
            self::syncPppoeExpiredClientMeta($client, $routerName);
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'portal_url' => $portalUrl,
            'backend_ip' => $backendIp ?? null,
        ];
    }

    public static function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    public static function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                );
            } else if ($plan['limit_type'] == "Data_Limit") {
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            } else if ($plan['limit_type'] == "Both_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $customer['username'])
                    ->setArgument('profile', $plan['name_plan'])
                    ->setArgument('comment', $customer['fullname'])
                    ->setArgument('email', $customer['email'])
                    ->setArgument('password', $customer['password'])
            );
        }
    }

    public static function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setHotspotUserPackage($client, $user, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
        $client->sendSync($setRequest);
    }

    public static function removeHotspotActiveUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function removePpoeUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function addPpoeUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ppp/secret/add');
        if (!empty($customer['pppoe_password'])) {
            $pass = $customer['pppoe_password'];
        } else {
            $pass = $customer['password'];
        }
        $client->sendSync(
            $addRequest
                ->setArgument('name', $customer['username'])
                ->setArgument('service', 'pppoe')
                ->setArgument('profile', $plan['name_plan'])
                ->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'])
                ->setArgument('password', $pass)
        );
    }

    public static function setPpoeUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setPpoeUserPlan($client, $user, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
        $client->sendSync($setRequest);
    }

    public static function removePpoeActive($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function removePool($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }

    public static function addPool($client, $name, $ip_address)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('ranges', $ip_address)
        );
    }

    public static function setPool($client, $name, $ip_address)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');

        if (empty($poolID)) {
            self::addPool($client, $name, $ip_address);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('ranges', $ip_address)
            );
        }
    }


    public static function addPpoePlan($client, $name, $pool, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('local-address', $pool)
                ->setArgument('remote-address', $pool)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setPpoePlan($client, $name, $pool, $rate)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            self::addPpoePlan($client, $name, $pool, $rate);
        } else {
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('local-address', $pool)
                    ->setArgument('remote-address', $pool)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function removePpoePlan($client, $name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    public static function sendSMS($client, $to, $message)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $smsRequest = new RouterOS\Request('/tool sms send');
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        $client->sendSync($smsRequest);
    }

    public static function getIpHotspotUser($client, $username){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }

    public static function addIpToAddressList($client, $ip, $listName, $comment = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/firewall/address-list/add');
        $client->sendSync(
            $addRequest
                ->setArgument('address', $ip)
                ->setArgument('comment', $comment)
                ->setArgument('list', $listName)
        );
    }

    public static function removeIpFromAddressList($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip firewall address-list print .proplist=.id',
            RouterOS\Query::where('address', $ip)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    /**
     * Ensure a directory exists on the router file store.
     */
    public static function ensureRouterDirectory($client, $directory)
    {
        $directory = trim((string) $directory, '/');
        if ($directory === '') {
            return;
        }
        try {
            $client->sendSync(
                (new RouterOS\Request('/file/make-directory'))->setArgument('name', $directory)
            );
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * Remove a file from the router file store.
     */
    public static function removeRouterFile($client, $path)
    {
        $path = trim((string) $path);
        // A trailing slash denotes a directory — never remove it (would wipe contents).
        if ($path === '' || substr($path, -1) === '/') {
            return;
        }
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'name,type')
                    ->setQuery(RouterOS\Query::where('name', $path))
            );
            foreach ($responses as $response) {
                if ((string) $response->getProperty('type') === 'directory') {
                    return;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
        try {
            $client->sendSync(
                (new RouterOS\Request('/file/remove'))->setArgument('numbers', $path)
            );
            usleep(300000);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    public static function waitRouterFileSize($client, $path, $expectedSize, $attempts = 12)
    {
        $path = trim((string) $path);
        $expectedSize = (int) $expectedSize;
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            if (self::getRouterFileSize($client, $path) === $expectedSize) {
                return true;
            }
            usleep(250000);
        }
        return false;
    }

    public static function waitRouterFileRemoved($client, $path, $attempts = 12)
    {
        $path = trim((string) $path);
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            if (self::getRouterFileSize($client, $path) <= 0) {
                return true;
            }
            self::removeRouterFile($client, $path);
            usleep(250000);
        }
        return self::getRouterFileSize($client, $path) <= 0;
    }

    /**
     * Rename a router file (RouterOS has no dedicated rename command).
     */
    public static function renameRouterFile($client, $from, $to)
    {
        $from = trim((string) $from);
        $to = trim((string) $to);
        if ($from === '' || $to === '' || $from === $to) {
            return $from === $to;
        }
        if (self::getRouterFileSize($client, $from) <= 0) {
            return false;
        }

        // Delete target file if it exists (RouterOS refuses to rename over existing files)
        // Only delete files, not directories (directories have size=0 or empty)
        $targetSize = self::getRouterFileSize($client, $to);
        if ($targetSize > 0) {
            self::removeRouterFile($client, $to);
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/set'))
                    ->setArgument('numbers', $from)
                    ->setArgument('name', $to)
            );
            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                    return false;
                }
            }
            usleep(500000);
            return self::getRouterFileSize($client, $to) > 0;
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * RouterOS /tool fetch sometimes saves HTML as login.html.txt — fix name after download.
     *
     * @return string|null Final path when the expected file exists, null otherwise.
     */
    public static function normalizeRouterFetchedFile($client, $expectedPath)
    {
        $expectedPath = trim((string) $expectedPath);
        if ($expectedPath === '') {
            return null;
        }

        if (self::getRouterFileSize($client, $expectedPath) > 0) {
            $wrongTxt = $expectedPath . '.txt';
            if (self::getRouterFileSize($client, $wrongTxt) > 0) {
                self::removeRouterFile($client, $wrongTxt);
            }
            return $expectedPath;
        }

        $wrongPaths = [
            $expectedPath . '.txt',
        ];
        foreach ($wrongPaths as $wrongPath) {
            if (self::getRouterFileSize($client, $wrongPath) > 0) {
                if (self::renameRouterFile($client, $wrongPath, $expectedPath)) {
                    return $expectedPath;
                }
            }
        }

        return null;
    }

    public static function replaceRouterFile($client, $from, $to)
    {
        $from = trim((string) $from);
        $to = trim((string) $to);
        if ($from === '' || $to === '') {
            return false;
        }
        if (self::getRouterFileSize($client, $from) <= 0) {
            return false;
        }
        if ($from === $to) {
            return true;
        }
        self::removeRouterFile($client, $to);
        self::removeRouterFile($client, $to . '.txt');
        return self::renameRouterFile($client, $from, $to);
    }

    /**
     * Write large router files in API-safe chunks (RouterOS limits single /file/set payloads).
     */
    public static function tryRouterFileWriteChunked($client, $path, $contents)
    {
        $path = trim((string) $path);
        $contents = (string) $contents;
        $length = strlen($contents);
        if ($path === '' || $length === 0) {
            return false;
        }

        if ($length <= 8000) {
            $util = new RouterOS\Util($client);
            return self::tryRouterFileWrite($util, $path, $contents);
        }

        // Large files (>8KB): use PEAR2 Util->filePutContents which handles
        // chunking automatically. This works over low-MTU/lossy tunnels where
        // MikroTik /tool fetch (inbound 16KB) times out.
        self::removeRouterFile($client, $path);
        self::removeRouterFile($client, $path . '.txt');

        $util = new RouterOS\Util($client);
        try {
            $util->filePutContents($path, $contents);
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        usleep(300000);

        // Verify write succeeded
        $writtenSize = self::getRouterFileSize($client, $path);
        if ($writtenSize >= (int) ($length * 0.9)) {
            return true;
        }

        // Check if RouterOS created a .txt suffix
        $txtSize = self::getRouterFileSize($client, $path . '.txt');
        if ($txtSize >= (int) ($length * 0.9)) {
            return self::renameRouterFile($client, $path . '.txt', $path);
        }

        return false;
    }

    /**
     * After /file/print file=X, RouterOS creates an empty placeholder that is
     * usually named "X.txt" (sometimes "X"). Return whichever name exists so we
     * can write contents to it via /file/set.
     */
    private static function findRouterPlaceholderName($client, $path)
    {
        // Try exact name first
        if (self::getRouterFileSize($client, $path) >= 0) {
            return $path;
        }

        // Try .txt suffix (RouterOS default for created files)
        $txtPath = $path . '.txt';
        if (self::getRouterFileSize($client, $txtPath) >= 0) {
            return $txtPath;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function buildHotspotLoginFetchUrls($apiUrl, $appUrl, $fetchTs = null)
    {
        $fetchTs = $fetchTs ?? time();
        $bases = array_values(array_unique(array_filter([
            rtrim((string) $apiUrl, '/'),
            rtrim((string) $appUrl, '/'),
        ], function ($base) {
            return self::isRouterFetchableUrl($base);
        })));
        if (empty($bases)) {
            return [];
        }

        $urls = [];
        foreach ([
            $bases[0] . '/hotspot_login.html?ts=' . $fetchTs,
            $bases[0] . '/system/uploads/mikrotik_hotspot/login.html?ts=' . $fetchTs,
            $bases[0] . '/index.php?_route=plugin/hotspot_login_file&ts=' . $fetchTs,
        ] as $url) {
            if (self::isRouterFetchableUrl($url)) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Normalise l'URL API WireGuard (10.0.0.x) : port web VPS = 80, pas 8000/8080 dev.
     */
    public static function normalizeHotspotBackendApiUrl($apiUrl)
    {
        $apiUrl = trim((string) $apiUrl);
        $parts = parse_url($apiUrl);
        if (!$parts || empty($parts['host'])) {
            return $apiUrl;
        }
        $host = $parts['host'];
        $scheme = $parts['scheme'] ?? 'http';
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if (filter_var($host, FILTER_VALIDATE_IP) && preg_match('/^10\.0\.0\./', $host)) {
            // 8080 = dev Mac ; 8000 = Docker VPS (docker-compose.server.yml) — ne pas écraser
            if ($port === null || $port === 8080) {
                $port = ($scheme === 'https') ? 443 : 80;
            }
        } elseif ($port === null) {
            $port = ($scheme === 'https') ? 443 : 80;
        }
        $url = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        return $url;
    }

    /**
     * @return array{ok: bool, errors?: array<int, string>}
     */
    public static function removeFirewallRulesByComment($client, $comment)
    {
        $comment = trim((string) $comment);
        if ($comment === '') {
            return ['ok' => true];
        }
        $errors = [];
        foreach (['/ip/firewall/nat', '/ip/firewall/filter'] as $chainPath) {
            try {
                $rows = $client->sendSync(
                    (new RouterOS\Request($chainPath . '/print'))
                        ->setArgument('.proplist', '.id,comment')
                );
                foreach ($rows as $row) {
                    if ((string) $row->getProperty('comment') !== $comment) {
                        continue;
                    }
                    $client->sendSync(
                        (new RouterOS\Request($chainPath . '/remove'))
                            ->setArgument('numbers', $row->getProperty('.id'))
                    );
                }
            } catch (Throwable $e) {
                $errors[] = $chainPath . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $chainPath . ': ' . $e->getMessage();
            }
        }

        return empty($errors) ? ['ok' => true] : ['ok' => false, 'errors' => $errors];
    }

    /**
     * Autorise le serveur API dans le walled-garden hotspot (avant fetch login.html).
     *
     * @return array{ok: bool, errors?: array<int, string>}
     */
    public static function ensureHotspotWalledGarden($client, $apiUrl)
    {
        $apiUrl = trim((string) $apiUrl);
        $apiHost = parse_url($apiUrl, PHP_URL_HOST);
        if (!$apiHost) {
            return ['ok' => false, 'errors' => ['Hotspot API URL invalide']];
        }
        $apiPort = parse_url($apiUrl, PHP_URL_PORT);
        $apiScheme = parse_url($apiUrl, PHP_URL_SCHEME);
        if (!$apiPort) {
            $apiPort = $apiScheme === 'https' ? 443 : 80;
        }

        $apiIsIp = filter_var($apiHost, FILTER_VALIDATE_IP) !== false;
        $queryField = $apiIsIp ? 'dst-address' : 'dst-host';
        $walledGardenPaths = ['/ip/hotspot/walled-garden/ip', '/ip hotspot walled-garden ip'];
        $errors = [];

        foreach ($walledGardenPaths as $wgPath) {
            try {
                $walledGarden = $client->sendSync(
                    (new RouterOS\Request($wgPath . '/print'))
                        ->setArgument('.proplist', '.id,' . $queryField . ',dst-port')
                        ->setQuery(RouterOS\Query::where($queryField, $apiHost))
                );
                $updated = false;
                foreach ($walledGarden as $row) {
                    if ((string) $row->getProperty('dst-port') === (string) $apiPort) {
                        return ['ok' => true];
                    }
                    $client->sendSync(
                        (new RouterOS\Request($wgPath . '/set'))
                            ->setArgument('numbers', $row->getProperty('.id'))
                            ->setArgument('dst-port', (string) $apiPort)
                            ->setArgument('protocol', 'tcp')
                            ->setArgument('action', 'accept')
                            ->setArgument('comment', 'WifiZone hotspot API ' . $apiUrl)
                    );
                    $updated = true;
                    break;
                }
                if (!$updated) {
                    $client->sendSync(
                        (new RouterOS\Request($wgPath . '/add'))
                            ->setArgument($queryField, $apiHost)
                            ->setArgument('protocol', 'tcp')
                            ->setArgument('dst-port', (string) $apiPort)
                            ->setArgument('action', 'accept')
                            ->setArgument('comment', 'WifiZone hotspot API ' . $apiUrl)
                    );
                }

                return ['ok' => true];
            } catch (Throwable $e) {
                $errors[] = $wgPath . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $wgPath . ': ' . $e->getMessage();
            }
        }

        return ['ok' => false, 'errors' => $errors];
    }

    /**
     * IP sur une interface RouterOS (ex. bridge → 192.168.88.5).
     */
    public static function resolveRouterInterfaceIp($client, $interfaceName)
    {
        $interfaceName = trim((string) $interfaceName);
        if ($interfaceName === '') {
            return '';
        }
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/address/print'))
                    ->setArgument('.proplist', 'address,interface,actual-interface')
                    ->setQuery(RouterOS\Query::where('interface', $interfaceName))
            );
            foreach ($responses as $row) {
                $address = (string) $row->getProperty('address');
                if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $address, $match)) {
                    return $match[1];
                }
            }
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/address/print'))
                    ->setArgument('.proplist', 'address,interface,actual-interface')
            );
            foreach ($responses as $row) {
                $iface = (string) $row->getProperty('interface');
                $actual = (string) $row->getProperty('actual-interface');
                if ($iface !== $interfaceName && $actual !== $interfaceName) {
                    continue;
                }
                $address = (string) $row->getProperty('address');
                if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $address, $match)) {
                    return $match[1];
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * IP du serveur hotspot sur le routeur (ex. 192.168.88.5 affiché dans le portail captif).
     */
    public static function getHotspotServerAddress($client, $hotspotName = '')
    {
        $hotspotName = trim((string) $hotspotName);
        $fallback = '';
        $candidates = [];

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', 'name,interface,address,profile')
            );
            foreach ($responses as $row) {
                $name = trim((string) $row->getProperty('name'));
                $ip = '';
                $address = trim((string) $row->getProperty('address'));
                if ($address !== '' && preg_match('/(\d+\.\d+\.\d+\.\d+)/', $address, $match)) {
                    $ip = $match[1];
                }
                if ($ip === '') {
                    $ip = self::resolveRouterInterfaceIp($client, (string) $row->getProperty('interface'));
                }
                if ($ip === '') {
                    continue;
                }
                $candidates[] = ['name' => $name, 'ip' => $ip];
                if ($fallback === '') {
                    $fallback = $ip;
                }
                if ($hotspotName === '' || strcasecmp($name, $hotspotName) === 0) {
                    return $ip;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        if ($hotspotName !== '' && $fallback === '') {
            foreach ($candidates as $candidate) {
                if (stripos($candidate['name'], $hotspotName) !== false || stripos($hotspotName, $candidate['name']) !== false) {
                    return $candidate['ip'];
                }
            }
        }

        if ($fallback !== '') {
            return $fallback;
        }

        try {
            $networks = $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/network/print'))
                    ->setArgument('.proplist', 'address,gateway')
            );
            foreach ($networks as $row) {
                foreach (['gateway', 'address'] as $field) {
                    $value = trim((string) $row->getProperty($field));
                    if ($value !== '' && preg_match('/(\d+\.\d+\.\d+\.\d+)/', $value, $match)) {
                        return $match[1];
                    }
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * Proxy NAT : clients hotspot joignent le routeur sur un port dédié (8080),
     * redirigé vers le serveur DYRSIA (évite le conflit avec le portail captif sur :80).
     *
     * @return array{ok: bool, captive_url?: string, errors?: array<int, string>}
     */
    public static function ensureHotspotApiNatProxy($client, $listenIp, $backendHost, $backendPort = 80)
    {
        $listenIp = trim((string) $listenIp);
        $backendHost = trim((string) $backendHost);
        $backendPort = (int) $backendPort;
        if ($listenIp === '' || $backendHost === '' || $backendPort <= 0) {
            return ['ok' => false, 'errors' => ['IP hotspot ou serveur API invalide']];
        }

        $listenPort = 8080;
        $captiveUrl = 'http://' . $listenIp . ':' . $listenPort;
        if ($listenIp === $backendHost && $listenPort === $backendPort) {
            return ['ok' => true, 'captive_url' => $captiveUrl];
        }

        $proxyComment = 'WifiZone hotspot API proxy';
        $snatComment = 'WifiZone hotspot API SNAT';
        $inputComment = 'WifiZone hotspot API input';
        $errors = [];

        try {
            self::removeFirewallRulesByComment($client, $proxyComment);
            self::removeFirewallRulesByComment($client, $snatComment);
            self::removeFirewallRulesByComment($client, $inputComment);

            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/add'))
                    ->setArgument('chain', 'dstnat')
                    ->setArgument('protocol', 'tcp')
                    ->setArgument('dst-address', $listenIp)
                    ->setArgument('dst-port', (string) $listenPort)
                    ->setArgument('action', 'dst-nat')
                    ->setArgument('to-addresses', $backendHost)
                    ->setArgument('to-ports', (string) $backendPort)
                    ->setArgument('comment', $proxyComment)
            );

            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/add'))
                    ->setArgument('chain', 'srcnat')
                    ->setArgument('protocol', 'tcp')
                    ->setArgument('dst-address', $backendHost)
                    ->setArgument('dst-port', (string) $backendPort)
                    ->setArgument('action', 'masquerade')
                    ->setArgument('comment', $snatComment)
            );

            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/add'))
                    ->setArgument('chain', 'input')
                    ->setArgument('protocol', 'tcp')
                    ->setArgument('dst-address', $listenIp)
                    ->setArgument('dst-port', (string) $listenPort)
                    ->setArgument('action', 'accept')
                    ->setArgument('comment', $inputComment)
            );

            $verify = $client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'comment,dst-port,to-addresses,to-ports')
                    ->setQuery(RouterOS\Query::where('comment', $proxyComment))
            );
            $verified = false;
            foreach ($verify as $row) {
                if ((string) $row->getProperty('dst-port') === (string) $listenPort) {
                    $verified = true;
                    break;
                }
            }
            if (!$verified) {
                return ['ok' => false, 'errors' => ['Règle NAT proxy non créée sur le MikroTik (droits firewall ?)']];
            }

            $wgBackend = self::ensureHotspotWalledGarden($client, 'http://' . $backendHost . ($backendPort === 80 ? '' : ':' . $backendPort));
            if (empty($wgBackend['ok'])) {
                $errors = array_merge($errors, $wgBackend['errors'] ?? ['walled-garden backend']);
            }
            $wgCaptive = self::ensureHotspotWalledGarden($client, $captiveUrl);
            if (empty($wgCaptive['ok'])) {
                $errors = array_merge($errors, $wgCaptive['errors'] ?? ['walled-garden captive']);
            }

            if (!empty($errors)) {
                return ['ok' => false, 'errors' => $errors, 'captive_url' => $captiveUrl];
            }

            return ['ok' => true, 'captive_url' => $captiveUrl];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        } catch (Exception $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Met à jour APP_URL / liens de paiement dans login.html pour le réseau captif.
     */
    public static function patchHotspotLoginCaptiveApi($html, $captiveApiUrl, $dnsName = '')
    {
        $html = (string) $html;
        $captiveApiUrl = rtrim(trim((string) $captiveApiUrl), '/');
        $dnsName = trim((string) $dnsName);
        if ($captiveApiUrl === '') {
            return $html;
        }

        $html = preg_replace(
            '/const APP_URL = .*?;/s',
            'const APP_URL = ' . json_encode($captiveApiUrl) . ';',
            $html,
            1
        ) ?? $html;

        $dnsJs = 'const HOTSPOT_DNS_NAME = ' . json_encode($dnsName) . ';';
        if (preg_match('/const HOTSPOT_DNS_NAME = .*?;/s', $html)) {
            $html = preg_replace('/const HOTSPOT_DNS_NAME = .*?;/s', $dnsJs, $html, 1) ?? $html;
        } elseif (strpos($html, 'let CLIENT_MAC') !== false) {
            $html = str_replace('let CLIENT_MAC = \'\';', 'let CLIENT_MAC = \'\';' . "\n    " . $dnsJs, $html);
        }

        if (strpos($html, 'HOTSPOT_EMBEDDED_PLANS') !== false) {
            $html = preg_replace_callback(
                '/const HOTSPOT_EMBEDDED_PLANS = (\[[\s\S]*?\]);/',
                static function ($matches) use ($captiveApiUrl) {
                    $plans = json_decode($matches[1], true);
                    if (!is_array($plans)) {
                        return $matches[0];
                    }
                    foreach ($plans as &$plan) {
                        if (!empty($plan['paymentlink']) && is_string($plan['paymentlink'])) {
                            $parts = parse_url($plan['paymentlink']);
                            $query = $parts['query'] ?? '';
                            if ($query !== '') {
                                $plan['paymentlink'] = $captiveApiUrl . '/index.php?' . $query;
                            }
                        }
                    }
                    unset($plan);

                    return 'const HOTSPOT_EMBEDDED_PLANS = ' . json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
                },
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Entrée DNS statique sur le routeur : dns-name hotspot → IP du serveur DYRSIA.
     *
     * @return array{ok: bool, skipped?: bool, errors?: array<int, string>}
     */
    public static function ensureHotspotDnsStatic($client, $dnsName, $serverIp)
    {
        $dnsName = strtolower(trim((string) $dnsName));
        $serverIp = trim((string) $serverIp);
        if ($dnsName === '' || $serverIp === '' || !filter_var($serverIp, FILTER_VALIDATE_IP)) {
            return ['ok' => true, 'skipped' => true];
        }

        try {
            $existing = $client->sendSync(
                (new RouterOS\Request('/ip/dns/static/print'))
                    ->setArgument('.proplist', '.id,address')
                    ->setQuery(RouterOS\Query::where('name', $dnsName))
            );
            $existingId = null;
            $currentIp = '';
            foreach ($existing as $row) {
                $existingId = $row->getProperty('.id');
                $currentIp = (string) $row->getProperty('address');
                break;
            }
            if ($existingId !== null) {
                if ($currentIp === $serverIp) {
                    return ['ok' => true];
                }
                $client->sendSync(
                    (new RouterOS\Request('/ip/dns/static/set'))
                        ->setArgument('numbers', $existingId)
                        ->setArgument('address', $serverIp)
                );

                return ['ok' => true];
            }

            $client->sendSync(
                (new RouterOS\Request('/ip/dns/static/add'))
                    ->setArgument('name', $dnsName)
                    ->setArgument('address', $serverIp)
                    ->setArgument('comment', 'WifiZone hotspot DNS')
            );

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        } catch (Exception $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * @return array<int, string>
     */
    private static function localServerHosts()
    {
        static $hosts = null;
        if ($hosts !== null) {
            return $hosts;
        }

        $hosts = ['127.0.0.1', 'localhost', '::1'];
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $hosts[] = strtolower((string) $_SERVER['SERVER_ADDR']);
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $hosts[] = strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']));
        }
        if (defined('APP_URL')) {
            $appHost = parse_url(APP_URL, PHP_URL_HOST);
            if (is_string($appHost) && $appHost !== '') {
                $hosts[] = strtolower($appHost);
            }
        }

        if (stripos(PHP_OS_FAMILY, 'Windows') === false) {
            $output = @shell_exec("ifconfig 2>/dev/null | awk '/inet / {print $2}'");
            if (is_string($output) && $output !== '') {
                foreach (preg_split('/\s+/', trim($output)) as $ip) {
                    $ip = strtolower(trim($ip));
                    if ($ip !== '') {
                        $hosts[] = $ip;
                    }
                }
            }
        }

        $hosts = array_values(array_unique(array_filter($hosts)));

        return $hosts;
    }

    private static function isLocalHotspotFetchUrl($url)
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        return in_array($host, self::localServerHosts(), true);
    }

    /**
     * Vérifie que login.html est prêt avant un fetch RouterOS.
     * Évite un HTTP vers soi-même : le serveur PHP intégré est mono-thread et se bloquerait.
     *
     * @return true|string
     */
    public static function verifyHotspotFetchUrl($url, $timeout = 5)
    {
        $url = trim((string) $url);
        if ($url === '' || !self::isRouterFetchableUrl($url)) {
            return 'URL de fetch invalide ou en localhost';
        }

        if (self::isLocalHotspotFetchUrl($url)) {
            global $UPLOAD_PATH;
            if (empty($UPLOAD_PATH)) {
                return true;
            }
            $loginFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot' . DIRECTORY_SEPARATOR . 'login.html';
            if (is_file($loginFile) && is_readable($loginFile) && filesize($loginFile) > 0) {
                return true;
            }

            return 'login.html introuvable ou vide sur ce serveur (' . $loginFile . ')';
        }

        if (!function_exists('curl_init')) {
            return true;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return true;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => max(1, (int) $timeout),
            CURLOPT_TIMEOUT => max(2, (int) $timeout),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RANGE => '0-0',
        ]);
        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 400) {
            return true;
        }

        if ($curlError !== '') {
            return 'URL injoignable (' . $url . ') : ' . $curlError;
        }

        return 'URL injoignable (' . $url . ') : HTTP ' . $httpCode;
    }

    /**
     * @return string|null Error message on failure, null on success.
     */
    private static function attemptToolFetch($client, $url, $dstPath)
    {
        // Never remove when dst-path is a directory (ends with '/') — RouterOS would
        // delete the whole directory and all its contents.
        if (substr($dstPath, -1) !== '/') {
            self::removeRouterFile($client, $dstPath);
            self::removeRouterFile($client, $dstPath . '.txt');
        }

        $mode = stripos($url, 'https://') === 0 ? 'https' : 'http';
        $fetchAttempts = [
            (new RouterOS\Request('/tool/fetch'))
                ->setArgument('url', $url)
                ->setArgument('dst-path', $dstPath)
                ->setArgument('mode', $mode)
                ->setArgument('output', 'file')
                ->setArgument('check-certificate', 'no'),
            (new RouterOS\Request('/tool/fetch'))
                ->setArgument('url', $url)
                ->setArgument('dst-path', $dstPath)
                ->setArgument('output', 'file')
                ->setArgument('check-certificate', 'no'),
        ];

        $responses = null;
        $attemptErrors = [];
        foreach ($fetchAttempts as $request) {
            try {
                $responses = $client->sendSync($request);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'unknown parameter') !== false) {
                    continue;
                }
                $attemptErrors[] = $msg;
                continue;
            } catch (Exception $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'unknown parameter') !== false) {
                    continue;
                }
                $attemptErrors[] = $msg;
                continue;
            }

            $status = '';
            $message = '';
            $responseError = null;
            foreach ($responses as $response) {
                $status = $status ?: (string) $response->getProperty('status');
                $message = $message ?: (string) $response->getProperty('message');
                if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                    $responseError = trim($message) !== '' ? $message : 'fetch RouterOS error';
                }
            }

            if ($responseError !== null) {
                if (stripos($responseError, 'unknown parameter') !== false) {
                    continue;
                }
                return $responseError;
            }

            if ($status !== '' && stripos($status, 'fail') !== false) {
                $failMessage = trim($message) !== '' ? $message : ('fetch status: ' . $status);
                if (stripos($failMessage, 'unknown parameter') !== false) {
                    continue;
                }
                return $failMessage;
            }

            break;
        }

        if ($responses === null) {
            return implode(' | ', array_filter($attemptErrors)) ?: 'fetch RouterOS impossible';
        }

        for ($attempt = 0; $attempt < 12; $attempt++) {
            usleep(250000);
            if (self::normalizeRouterFetchedFile($client, $dstPath) !== null) {
                return null;
            }
        }

        return 'fichier non créé après fetch';
    }

    /**
     * Download a remote file onto the router via /tool fetch.
     *
     * @return string|null Error message on failure, null on success.
     */
    public static function fetchUrlToRouterFile($client, $url, $dstPath)
    {
        $url = trim((string) $url);
        $dstPath = trim((string) $dstPath);
        if ($url === '' || $dstPath === '') {
            return 'URL ou chemin destination vide';
        }

        $fetchError = self::attemptToolFetch($client, $url, $dstPath);
        if ($fetchError === null) {
            return null;
        }

        // Fallback: dst-path as directory — RouterOS uses the URL filename (login.html).
        $dir = dirname($dstPath);
        if ($dir !== '.' && $dir !== '') {
            $dirPath = rtrim($dir, '/') . '/';
            $dirFetchError = self::attemptToolFetch($client, $url, $dirPath);
            if ($dirFetchError === null && self::normalizeRouterFetchedFile($client, $dstPath) !== null) {
                return null;
            }
            if ($dirFetchError !== null) {
                return $fetchError . ' | ' . $dirFetchError;
            }
            return $fetchError . ' | fichier créé avec un nom incorrect après fetch';
        }

        return $fetchError;
    }

    public static function isRouterFetchableUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }
        $host = strtolower($host);
        // Loopback can never be reached by a remote router (it would resolve to the
        // router itself), so it is the only host family we reject outright.
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Reject loopback (127/8) and link-local (169.254/16). Private LAN/VPN ranges
            // (10/8, 192.168/16, 172.16/12) are allowed on purpose: the billing server and
            // the MikroTik are very commonly on the same LAN or VPN, which is the only way
            // the router can fetch large assets (login.html >4 KB) that the API cannot write.
            if (preg_match('/^(127\.|169\.254\.)/', $host)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $fetchUrls
     * @return array<int, string>
     */
    public static function filterRouterFetchUrls(array $fetchUrls)
    {
        $filtered = [];
        foreach ($fetchUrls as $url) {
            if (self::isRouterFetchableUrl($url)) {
                $filtered[] = $url;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * @return bool
     */
    public static function tryRouterFileWrite($util, $path, $contents)
    {
        try {
            $util->filePutContents($path, null);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $util->filePutContents($path, $contents, true);
    }

    /**
     * @return int
     */
    public static function getRouterFileSize($client, $path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return 0;
        }
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'name,size')
                    ->setQuery(RouterOS\Query::where('name', $path))
            );
            foreach ($responses as $response) {
                $size = $response->getProperty('size');
                if ($size !== null && $size !== '') {
                    return (int) $size;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'name,size')
            );
            foreach ($responses as $response) {
                $name = (string) $response->getProperty('name');
                if ($name !== $path) {
                    continue;
                }
                $size = $response->getProperty('size');
                if ($size !== null && $size !== '') {
                    return (int) $size;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return 0;
    }

    /**
     * Upload hotspot/login.html — API par blocs en priorité (rapide, pas de HTTP).
     * Fallback /tool fetch si l'écriture API échoue (fichiers >4 Ko).
     *
     * @param array<int, string> $fetchUrls
     * @return array{ok: bool, path?: string, method?: string, errors?: array<int, string>}
     */
    public static function deployHotspotLoginHtml($client, $html, array $fetchUrls = [])
    {
        $html = (string) $html;
        $length = strlen($html);
        $fetchUrls = array_slice(self::filterRouterFetchUrls($fetchUrls), 0, 2);
        $errors = [];

        self::ensureRouterDirectory($client, 'hotspot');

        $tmpPath = 'hotspot/dyrsia-login-new.html';
        $finalPath = 'hotspot/login.html';

        // 1) API write first — fast and reliable even over low-MTU VPN tunnels
        //    (outgoing data fragments correctly, unlike inbound /tool fetch of 16KB).
        self::removeRouterFile($client, $tmpPath);
        self::removeRouterFile($client, $tmpPath . '.txt');
        if (self::tryRouterFileWriteChunked($client, $tmpPath, $html)) {
            if (self::renameRouterFile($client, $tmpPath, $finalPath)) {
                return ['ok' => true, 'path' => $finalPath, 'method' => 'api'];
            }
            $errors[] = $finalPath . ' (api): fichier écrit mais remplacement final impossible';
        } else {
            $errors[] = $finalPath . ': écriture API refusée (' . $length . ' octets)';
        }

        // 2) Fallback: /tool fetch (HTTP) — only if API write failed.
        if (!empty($fetchUrls)) {
            foreach ($fetchUrls as $url) {
                self::removeRouterFile($client, $tmpPath);
                self::removeRouterFile($client, $tmpPath . '.txt');
                $fetchError = self::fetchUrlToRouterFile($client, $url, $tmpPath);
                $normalizedPath = self::normalizeRouterFetchedFile($client, $tmpPath);
                $fetchedSize = $normalizedPath !== null ? self::getRouterFileSize($client, $normalizedPath) : 0;
                if ($fetchError === null && $fetchedSize >= (int) ($length * 0.9)) {
                    if (self::renameRouterFile($client, $normalizedPath, $finalPath)) {
                        return ['ok' => true, 'path' => $finalPath, 'method' => 'fetch'];
                    }
                    $errors[] = $finalPath . ' (fetch): nouveau fichier reçu mais remplacement final impossible';
                    continue;
                }
                $errors[] = $finalPath . ' (fetch): ' . ($fetchError ?? ('taille reçue ' . $fetchedSize . ' < attendue ' . $length));
            }
        }

        return ['ok' => false, 'errors' => $errors];
    }

    /**
     * @param array<int, string> $fetchUrls
     * @return array{ok: bool, path?: string, method?: string, errors?: array<int, string>}
     */
    public static function deployHotspotAssetFile($client, $filename, $binary, array $fetchUrls = [])
    {
        $filename = trim((string) $filename);
        $binary = (string) $binary;
        if ($filename === '' || $binary === '') {
            return ['ok' => false, 'errors' => ['fichier asset vide']];
        }

        $fetchUrls = self::filterRouterFetchUrls($fetchUrls);
        $paths = ['hotspot/' . $filename, $filename];
        $errors = [];

        self::ensureRouterDirectory($client, 'hotspot');

        foreach ($paths as $path) {
            if (self::tryRouterFileWriteChunked($client, $path, $binary)) {
                return ['ok' => true, 'path' => $path, 'method' => 'api'];
            }

            foreach ($fetchUrls as $url) {
                $fetchError = self::fetchUrlToRouterFile($client, $url, $path);
                if ($fetchError === null && self::normalizeRouterFetchedFile($client, $path) !== null) {
                    return ['ok' => true, 'path' => $path, 'method' => 'fetch'];
                }
                $errors[] = $path . ' (fetch): ' . ($fetchError ?? 'nom de fichier incorrect');
            }

            $errors[] = $path . ': écriture API refusée (' . strlen($binary) . ' octets)';
        }

        return ['ok' => false, 'errors' => $errors];
    }

    public static function patchHotspotLoginHelpSection($html, array $help)
    {
        $html = (string) $html;
        $title = trim((string) ($help['title'] ?? ''));
        $text = trim((string) ($help['text'] ?? ''));
        $whatsapp = trim((string) ($help['whatsapp'] ?? ''));
        $whatsappLabel = trim((string) ($help['whatsapp_label'] ?? ''));

        if ($title !== '') {
            $html = preg_replace('/<h3>\s*Assistance\s*&amp;\s*Connexion\s*à\s*domicile\s*<\/h3>/is', '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>', $html, 1) ?? $html;
        }
        if ($text !== '') {
            $html = preg_replace('/<p>\s*Une question \? Un besoin technique \?\s*<\/p>/is', '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>', $html, 1) ?? $html;
        }
        if ($whatsappLabel !== '') {
            $html = preg_replace('/WhatsApp\s*—\s*Nous contacter/is', htmlspecialchars($whatsappLabel, ENT_QUOTES, 'UTF-8'), $html, 1) ?? $html;
        }
        if ($whatsapp !== '') {
            $digits = preg_replace('/\D/', '', $whatsapp);
            if ($digits !== '') {
                $html = preg_replace('/https?:\/\/wa\.me\/[0-9]+/i', 'https://wa.me/' . $digits, $html, 1) ?? $html;
            }
        }

        return $html;
    }

    public static function ensureHotspotCaptiveExtrasWalledGarden($client, $appUrl)
    {
        $hosts = array_filter(array_unique([
            parse_url(self::normalizeHotspotBackendApiUrl($appUrl), PHP_URL_HOST),
            'cdn.jsdelivr.net',
            'wa.me',
            'api.whatsapp.com',
            'web.whatsapp.com',
        ]));
        foreach ($hosts as $host) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/walled-garden/ip/add'))
                        ->setArgument('dst-host', $host)
                        ->setArgument('action', 'accept')
                        ->setArgument('comment', 'DYRSIA hotspot captive extras')
                );
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        return ['ok' => true, 'errors' => []];
    }

    public static function hotspotCaptiveWalledGardenRouterOsScript($appUrl, $apiUrl = '')
    {
        $hosts = [];
        foreach ([$appUrl, $apiUrl, 'https://wifizones.org', 'https://www.wifizones.org'] as $url) {
            $host = parse_url(self::normalizeHotspotBackendApiUrl($url), PHP_URL_HOST);
            if ($host) {
                $hosts[] = strtolower($host);
            }
        }
        $hosts = array_values(array_unique(array_filter(array_merge($hosts, [
            'cdn.jsdelivr.net',
            'wa.me',
            'api.whatsapp.com',
            'web.whatsapp.com',
        ]))));

        $lines = ['# DYRSIA Hotspot captive portal walled-garden'];
        foreach ($hosts as $host) {
            $lines[] = '/ip hotspot walled-garden ip add action=accept dst-host="' . str_replace('"', '', $host) . '" comment="DYRSIA hotspot captive"';
        }

        return implode("\n", $lines);
    }
}
