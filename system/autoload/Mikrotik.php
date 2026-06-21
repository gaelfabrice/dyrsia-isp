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
        static $cache = [];
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return null;
        }
        if (array_key_exists($host, $cache)) {
            return $cache[$host];
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $cache[$host] = $host;

            return $host;
        }
        $records = @dns_get_record($host, DNS_A);
        if (is_array($records)) {
            foreach ($records as $rec) {
                if (!empty($rec['ip']) && filter_var($rec['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $cache[$host] = $rec['ip'];

                    return $rec['ip'];
                }
            }
        }
        $cache[$host] = null;

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

    /**
     * Liste les pools IP d'un routeur (MikroTik + base locale), pour les formulaires PPPoE.
     *
     * @return array<int, array{name:string,ranges:string,local_ip:string,source:string}>
     */
    public static function fetchRouterIpPools($routerName, $admin = null)
    {
        global $_app_stage;

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return [];
        }

        $merged = [];
        $dbQuery = ORM::for_table('tbl_pool')->where('routers', $routerName);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $dbQuery->where('admin_id', (int) ($admin['id'] ?? 0));
        }
        foreach ($dbQuery->find_many() as $row) {
            $name = trim((string) ($row['pool_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $merged[$name] = [
                'name' => $name,
                'ranges' => trim((string) ($row['range_ip'] ?? '')),
                'local_ip' => trim((string) ($row['local_ip'] ?? '')),
                'source' => 'db',
            ];
        }

        if ($routerName === 'radius' || strtolower((string) $_app_stage) === 'demo') {
            return array_values($merged);
        }
        if (class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin)) {
            return array_values($merged);
        }

        $router = self::resolveRouterRecord($routerName, $admin);
        if (!$router) {
            throw new Exception(Lang::T('Router not found') . ' (' . $routerName . ')');
        }
        if (is_object($router) && method_exists($router, 'as_array')) {
            $router = $router->as_array();
        }

        $client = self::getClient(
            $router['ip_address'],
            $router['username'],
            self::routerPassword($router['password']),
            15
        );
        if (!$client) {
            throw new Exception(Lang::T('Cannot connect to MikroTik'));
        }
        $responses = $client->sendSync(
            (new RouterOS\Request('/ip/pool/print'))
                ->setArgument('.proplist', 'name,ranges')
        );
        foreach ($responses as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $name = trim((string) $row->getProperty('name'));
            if ($name === '') {
                continue;
            }
            $ranges = trim((string) $row->getProperty('ranges'));
            if (isset($merged[$name])) {
                if ($merged[$name]['ranges'] === '' && $ranges !== '') {
                    $merged[$name]['ranges'] = $ranges;
                }
                $merged[$name]['source'] = 'both';
                continue;
            }
            $merged[$name] = [
                'name' => $name,
                'ranges' => $ranges,
                'local_ip' => '',
                'source' => 'mikrotik',
            ];
        }

        ksort($merged, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($merged);
    }

    /**
     * Garantit qu'un pool existe en base et sur le MikroTik avant création d'un forfait PPPoE.
     *
     * @param string $mode existing = pool déjà sur le routeur (sans création) | new = créer le pool | auto = legacy
     */
    public static function ensureRouterIpPool($routerName, $poolName, $rangeIp = '', $localIp = '', $admin = null, $mode = 'auto')
    {
        global $_app_stage;

        $routerName = trim((string) $routerName);
        $poolName = trim((string) $poolName);
        $rangeIp = trim((string) $rangeIp);
        $localIp = trim((string) $localIp);
        $mode = in_array($mode, ['existing', 'new', 'auto'], true) ? $mode : 'auto';

        if ($routerName === '' || $poolName === '') {
            throw new InvalidArgumentException(Lang::T('All field is required'));
        }
        if (Validator::Length($poolName, 30, 2) == false) {
            throw new InvalidArgumentException('Name should be between 3 to 30 characters');
        }

        $scopedPool = ORM::for_table('tbl_pool')->where('pool_name', $poolName);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $scopedPool->where('admin_id', (int) ($admin['id'] ?? 0));
        }
        $existingAny = $scopedPool->find_one();

        $scopedPoolForRouter = ORM::for_table('tbl_pool')
            ->where('pool_name', $poolName)
            ->where('routers', $routerName);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $scopedPoolForRouter->where('admin_id', (int) ($admin['id'] ?? 0));
        }
        $existingForRouter = $scopedPoolForRouter->find_one();

        // En mode « nouveau », le nom ne doit pas déjà exister pour un autre routeur.
        // En mode « existant », on réutilise un pool déjà sur le MikroTik sans cette erreur.
        if ($mode !== 'existing' && $existingAny && trim((string) ($existingAny['routers'] ?? '')) !== $routerName) {
            throw new Exception(Lang::T('Pool Name Already Exist'));
        }

        $existing = $existingForRouter ?: $existingAny;

        $remotePools = [];
        if ($routerName !== 'radius' && strtolower((string) $_app_stage) !== 'demo'
            && !(class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin))) {
            foreach (self::fetchRouterIpPools($routerName, $admin) as $poolRow) {
                $remotePools[$poolRow['name']] = $poolRow;
            }
        }

        $onMikrotik = isset($remotePools[$poolName]);

        if ($mode === 'existing') {
            if ($routerName !== 'radius' && !$onMikrotik && !$existingForRouter) {
                throw new InvalidArgumentException(
                    Lang::T('Select Pool') . ' — ' . Lang::T('Pool not found on the selected router')
                );
            }
            if ($rangeIp === '' && isset($remotePools[$poolName])) {
                $rangeIp = trim((string) ($remotePools[$poolName]['ranges'] ?? ''));
            }
            if ($rangeIp === '' && $existingForRouter) {
                $rangeIp = trim((string) ($existingForRouter['range_ip'] ?? ''));
            }
            if ($localIp === '' && $existingForRouter) {
                $localIp = trim((string) ($existingForRouter['local_ip'] ?? ''));
            }
            if ($localIp === '' && $rangeIp !== '') {
                $localIp = self::resolvePoolGatewayAddress(['local_ip' => '', 'range_ip' => $rangeIp]);
            }
            if ($existingForRouter) {
                return $poolName;
            }
            if ($rangeIp === '' && $routerName === 'radius') {
                throw new InvalidArgumentException(Lang::T('All field is required'));
            }
            $record = ORM::for_table('tbl_pool')->create();
            if (is_array($admin)) {
                $record->admin_id = (int) ($admin['id'] ?? 0);
            }
            $record->pool_name = $poolName;
            $record->range_ip = $rangeIp;
            $record->local_ip = $localIp;
            $record->routers = $routerName;
            $record->save();
            return $poolName;
        }

        if ($mode === 'new') {
            if ($onMikrotik) {
                throw new Exception(Lang::T('Pool Name Already Exist') . ' — ' . Lang::T('Select Pool'));
            }
            if ($existingAny) {
                throw new Exception(Lang::T('Pool Name Already Exist'));
            }
            if ($rangeIp === '') {
                throw new InvalidArgumentException(
                    Lang::T('Range IP') . ' — ' . Lang::T('Required for a new pool on MikroTik')
                );
            }
            if ($localIp !== '' && $rangeIp !== '') {
                $resolved = self::resolvePoolGatewayAddress(['local_ip' => $localIp, 'range_ip' => $rangeIp]);
                if ($localIp !== $resolved && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $localIp = $resolved;
                }
            } else {
                $localIp = self::resolvePoolGatewayAddress(['local_ip' => '', 'range_ip' => $rangeIp]);
            }
            $record = ORM::for_table('tbl_pool')->create();
            if (is_array($admin)) {
                $record->admin_id = (int) ($admin['id'] ?? 0);
            }
            $record->pool_name = $poolName;
            $record->range_ip = $rangeIp;
            $record->local_ip = $localIp;
            $record->routers = $routerName;
            $record->save();
            $needsMikrotikCreate = $routerName !== 'radius'
                && strtolower((string) $_app_stage) !== 'demo'
                && !(class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin));
            if ($needsMikrotikCreate) {
                require_once $GLOBALS['DEVICE_PATH'] . DIRECTORY_SEPARATOR . 'MikrotikPppoe.php';
                (new MikrotikPppoe())->add_pool($record->as_array());
            }
            return $poolName;
        }

        // mode auto (legacy)
        if ($rangeIp === '' && isset($remotePools[$poolName])) {
            $rangeIp = trim((string) ($remotePools[$poolName]['ranges'] ?? ''));
        }
        if ($rangeIp === '' && $existing) {
            $rangeIp = trim((string) ($existing['range_ip'] ?? ''));
        }
        if ($localIp === '' && $existing) {
            $localIp = trim((string) ($existing['local_ip'] ?? ''));
        }

        $onMikrotik = isset($remotePools[$poolName]);
        $needsMikrotikCreate = !$onMikrotik && $routerName !== 'radius'
            && $_app_stage != 'demo'
            && !(class_exists('DemoShowcase') && DemoShowcase::blocksRouterSync($admin));

        if ($needsMikrotikCreate && $rangeIp === '') {
            throw new InvalidArgumentException(
                Lang::T('Range IP') . ' — ' . Lang::T('Required for a new pool on MikroTik')
            );
        }

        if ($localIp !== '' && $rangeIp !== '') {
            $resolved = self::resolvePoolGatewayAddress(['local_ip' => $localIp, 'range_ip' => $rangeIp]);
            if ($localIp !== $resolved && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $localIp = $resolved;
            }
        } elseif ($rangeIp !== '') {
            $localIp = self::resolvePoolGatewayAddress(['local_ip' => '', 'range_ip' => $rangeIp]);
        }

        if ($existing) {
            if ($needsMikrotikCreate && $rangeIp !== '') {
                require_once $GLOBALS['DEVICE_PATH'] . DIRECTORY_SEPARATOR . 'MikrotikPppoe.php';
                (new MikrotikPppoe())->add_pool([
                    'pool_name' => $poolName,
                    'range_ip' => $rangeIp,
                    'local_ip' => $localIp,
                    'routers' => $routerName,
                ]);
            }
            return $poolName;
        }

        if ($rangeIp === '' && $routerName === 'radius') {
            throw new InvalidArgumentException(Lang::T('All field is required'));
        }

        $record = ORM::for_table('tbl_pool')->create();
        if (is_array($admin)) {
            $record->admin_id = (int) ($admin['id'] ?? 0);
        }
        $record->pool_name = $poolName;
        $record->range_ip = $rangeIp;
        $record->local_ip = $localIp;
        $record->routers = $routerName;
        $record->save();

        if ($needsMikrotikCreate) {
            require_once $GLOBALS['DEVICE_PATH'] . DIRECTORY_SEPARATOR . 'MikrotikPppoe.php';
            (new MikrotikPppoe())->add_pool($record->as_array());
        }

        return $poolName;
    }

    private static function formatMikrotikConnectionHelp($host, $port)
    {
        $hints = [
            'Vérifiez sur le routeur : /ip service print — le service « api » doit être activé (port 8728) ou « api-ssl » (port 8729).',
            'Commandes : /ip service enable api puis /ip service set api port=8728 disabled=no',
            'Utilisateur API : System → Users → groupe « full » ou « api » (pas seulement winbox).',
        ];

        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', (string) $host)) {
            $hints[] = 'IP privée (' . $host . ') : le serveur DYRSIA doit être sur le même réseau/VPN que le MikroTik. Depuis wifizones.org, utilisez l’IP WireGuard du routeur (ex. 10.0.0.3), pas l’IP LAN (192.168.88.x).';
            if (preg_match('/^10\.0\.0\./', (string) $host)) {
                $hints[] = 'Si l’API MikroTik est restreinte à 10.0.0.0/24 (/ip service set api address=10.0.0.0/24), seul un peer WireGuard peut se connecter — vérifiez le tunnel Dyrsia-VPN et le pare-feu input port 8728.';
            }
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

    /**
     * Find tbl_routers row by name, description or IP prefix (scoped to admin when provided).
     *
     * @param array|null $admin
     * @return ORM|null
     */
    public static function resolveRouterRecord($routerName, $admin = null)
    {
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return null;
        }

        $scoped = static function () use ($admin) {
            $query = ORM::for_table('tbl_routers');
            if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
                $query->where('admin_id', (int) ($admin['id'] ?? 0));
            }

            return $query;
        };

        $mikrotik = $scoped()->where('name', $routerName)->find_one();
        if ($mikrotik) {
            return $mikrotik;
        }

        $mikrotik = $scoped()->where('description', $routerName)->find_one();
        if ($mikrotik) {
            return $mikrotik;
        }

        $routerIp = explode(':', $routerName)[0];
        if ($routerIp !== '' && filter_var($routerIp, FILTER_VALIDATE_IP)) {
            $mikrotik = $scoped()->where_like('ip_address', $routerIp . '%')->find_one();
            if ($mikrotik) {
                return $mikrotik;
            }
        }

        return null;
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

        $unitdownRaw = strtolower(trim((string) ($bw['rate_down_unit'] ?? '')));
        $unitupRaw = strtolower(trim((string) ($bw['rate_up_unit'] ?? '')));
        if ($unitdownRaw === 'bps' || $unitupRaw === 'bps') {
            $up = (int) ($bw['rate_up'] ?? 0);
            $down = (int) ($bw['rate_down'] ?? 0);
            if ($up <= 0 || $down <= 0) {
                return '';
            }

            return $down . '/' . $up;
        }

        $unitdown = ($bw['rate_down_unit'] ?? '') === 'Kbps' ? 'K' : 'M';
        $unitup = ($bw['rate_up_unit'] ?? '') === 'Kbps' ? 'K' : 'M';
        if (($bw['rate_up'] ?? '0') == '0' || ($bw['rate_down'] ?? '0') == '0') {
            return '';
        }

        // MikroTik rate-limit: rx/tx (download/upload from the subscriber perspective).
        $rate = $bw['rate_down'] . $unitdown . '/' . $bw['rate_up'] . $unitup;
        if (!empty(trim((string) ($bw['burst'] ?? '')))) {
            $rate .= ' ' . trim((string) $bw['burst']);
        }

        return $rate;
    }

    /**
     * Profil EXPIRE : pas de rate-limit (suspension via firewall pppoe-expired).
     */
    public static function pppoeExpireSystemRateLimit()
    {
        return '';
    }

    /**
     * Script MikroTik on-up profil EXPIRE : ajoute l'IP client à pppoe-expired.
     * $remote-address peut être vide au moment on-up sur certains firmwares → fallback $address.
     */
    public static function pppoeExpiredProfileOnUpScript()
    {
        return ':local ip "$remote-address"; '
            . ':if ([:len $ip]=0) do={ :set ip "$address" }; '
            . ':if ([:len $ip]>0) do={ '
            . ':if ([len [/ip firewall address-list find list=pppoe-expired address=$ip]]=0) do={ '
            . '/ip firewall address-list add list=pppoe-expired address=$ip comment=$user '
            . '} }';
    }

    /**
     * Script MikroTik on-down profil EXPIRE : retire l'IP de pppoe-expired.
     */
    public static function pppoeExpiredProfileOnDownScript()
    {
        return ':local ip "$remote-address"; '
            . ':if ([:len $ip]=0) do={ :set ip "$address" }; '
            . ':if ([:len $ip]>0) do={ '
            . '/ip firewall address-list remove [find list=pppoe-expired address=$ip] '
            . '}';
    }

    /**
     * Toute session PPPoE profil EXPIRE (active ou secret) → liste pppoe-expired + coupure flux.
     *
     * @return array{ok: bool, enforced: int, errors: array<int, string>}
     */
    public static function sweepActiveExpirePppoeSessions($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'enforced' => 0, 'errors' => []];
        }

        $enforced = 0;
        $errors = [];
        $seenIps = [];
        $flushIps = [];

        $enforceIp = static function ($ip, $login) use ($client, &$enforced, &$seenIps, &$flushIps) {
            $ip = trim((string) $ip);
            if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP) || isset($seenIps[$ip])) {
                return;
            }
            $seenIps[$ip] = true;
            $flushIps[] = $ip;
            self::ensureAddressListEntry($client, 'pppoe-expired', $ip, $login);
            self::rememberPppoeExpiredClientMeta($login, $ip);
            $enforced++;
        };

        try {
            $activePrint = new RouterOS\Request('/ppp/active/print');
            $activePrint->setArgument('.proplist', 'name,address,profile');
            foreach ($client->sendSync($activePrint) as $row) {
                $profile = strtoupper(trim((string) $row->getProperty('profile')));
                if ($profile !== 'EXPIRE') {
                    continue;
                }
                $enforceIp($row->getProperty('address'), $row->getProperty('name'));
            }
        } catch (Throwable $e) {
            $errors[] = 'sweep active EXPIRE: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'sweep active EXPIRE: ' . $e->getMessage();
        }

        if ($flushIps !== []) {
            self::flushConnectionTrackingForIps($client, $flushIps);
        }

        return ['ok' => empty($errors), 'enforced' => $enforced, 'errors' => $errors];
    }

    /**
     * Retire le rate-limit d'un profil PPP (ex. EXPIRE après ancienne sync 1/1).
     */
    public static function unsetPppoeProfileRateLimit($client, $profileName)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return;
        }
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            return;
        }
        try {
            $printRequest = new RouterOS\Request('/ppp/profile/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('name', $profileName));
            $profileId = $client->sendSync($printRequest)->getProperty('.id');
            if (empty($profileId)) {
                return;
            }
            $unsetRequest = new RouterOS\Request('/ppp/profile/unset');
            $unsetRequest->setArgument('numbers', $profileId);
            $unsetRequest->setArgument('value-name', 'rate-limit');
            $client->sendSync($unsetRequest);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /** @var bool */
    private static $pppoeExpiredIsolationEnsured = false;

    public static function resetPppoeSyncRuntimeState()
    {
        self::$pppoeExpiredIsolationEnsured = false;
    }

    /**
     * Réutilise une connexion API MikroTik pour tous les sync_customer PPPoE (évite 1 TCP/API par client).
     */
    public static function withPppoeSharedClient($client, callable $callback)
    {
        $driver = self::pppoeDevice();
        MikrotikPppoe::useSharedClient($client);
        try {
            return $callback($driver);
        } finally {
            MikrotikPppoe::clearSharedClient();
        }
    }

    /**
     * Coupe les sessions actives pour une IP (script routeur, 1 passe au lieu de N×API).
     */
    public static function removeConnectionTrackingForIp($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return 0;
        }
        $ip = trim((string) $ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return 0;
        }

        $safeIp = str_replace('"', '', $ip);
        self::runRouterOneShotScript(
            $client,
            'dyrsia_rmconn',
            '/ip firewall connection remove [find src-address="' . $safeIp . '"]' . "\n"
            . '/ip firewall connection remove [find dst-address="' . $safeIp . '"]'
        );

        return 1;
    }

    /**
     * @param array<int, string> $ips
     */
    public static function flushConnectionTrackingForIps($client, array $ips)
    {
        $ips = array_values(array_unique(array_filter(array_map('trim', $ips))));
        if ($ips === []) {
            return;
        }
        $lines = [];
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                continue;
            }
            $safeIp = str_replace('"', '', $ip);
            $lines[] = '/ip firewall connection remove [find src-address="' . $safeIp . '"]';
            $lines[] = '/ip firewall connection remove [find dst-address="' . $safeIp . '"]';
        }
        if ($lines === []) {
            return;
        }
        self::runRouterOneShotScript($client, 'dyrsia_flush_conn', implode("\n", $lines));
    }

    public static function isPppoeExpiredCaptiveConfigured($client)
    {
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/nat/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('comment', 'DYRSIA-PPPOE-EXPIRED redirect http captive'));

            return (bool) $client->sendSync($printRequest)->getProperty('.id');
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Isolation stricte clients PPPoE expirés : marquage mangle + exclusion fasttrack + drop forward.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    public static function ensurePppoeExpiredFasttrackBypass($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => []];
        }

        $commentTag = 'DYRSIA-PPPOE-EXPIRED';
        $connMark = 'dyrsia-pppoe-expired';
        $errors = [];

        try {
            $printRequest = new RouterOS\Request('/ip/firewall/mangle/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('comment', $commentTag . ' mangle mark'));
            $mangleExists = (bool) $client->sendSync($printRequest)->getProperty('.id');
            if (!$mangleExists) {
                $addRequest = new RouterOS\Request('/ip/firewall/mangle/add');
                $addRequest->setArgument('chain', 'prerouting');
                $addRequest->setArgument('action', 'mark-connection');
                $addRequest->setArgument('new-connection-mark', $connMark);
                $addRequest->setArgument('passthrough', 'yes');
                $addRequest->setArgument('src-address-list', 'pppoe-expired');
                $addRequest->setArgument('comment', $commentTag . ' mangle mark');
                $client->sendSync($addRequest);
            }
        } catch (Throwable $e) {
            $errors[] = 'mangle mark: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'mangle mark: ' . $e->getMessage();
        }

        $dropMarkedExists = false;
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('comment', $commentTag . ' drop marked conn'));
            $dropMarkedExists = (bool) $client->sendSync($printRequest)->getProperty('.id');
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        if (!$dropMarkedExists) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id,action,chain,connection-mark');
                foreach ($client->sendSync($printRequest) as $row) {
                    if ((string) $row->getProperty('chain') !== 'forward') {
                        continue;
                    }
                    if ((string) $row->getProperty('action') !== 'fasttrack-connection') {
                        continue;
                    }
                    $ruleId = $row->getProperty('.id');
                    $currentMark = trim((string) $row->getProperty('connection-mark'));
                    if ($currentMark === 'no-mark' || $currentMark === ('!' . $connMark)) {
                        continue;
                    }
                    if (empty($ruleId)) {
                        continue;
                    }
                    $setRequest = new RouterOS\Request('/ip/firewall/filter/set');
                    $setRequest->setArgument('numbers', $ruleId);
                    $setRequest->setArgument('connection-mark', 'no-mark');
                    $setRequest->setArgument('hw-offload', 'no');
                    $client->sendSync($setRequest);
                }
            } catch (Throwable $e) {
                $errors[] = 'fasttrack patch: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'fasttrack patch: ' . $e->getMessage();
            }
        }

        $firstForwardRuleId = null;
        try {
            $firstRuleRequest = new RouterOS\Request('/ip/firewall/filter/print');
            $firstRuleRequest->setArgument('.proplist', '.id');
            $firstRuleRequest->setQuery(RouterOS\Query::where('chain', 'forward'));
            $firstForwardRuleId = $client->sendSync($firstRuleRequest)->getProperty('.id');
        } catch (Throwable $e) {
            $firstForwardRuleId = null;
        }

        foreach ([
            [
                'comment' => $commentTag . ' hard drop forward',
                'chain' => 'forward',
                'action' => 'drop',
                'src-address-list' => 'pppoe-expired',
            ],
            [
                'comment' => $commentTag . ' drop marked conn',
                'chain' => 'forward',
                'action' => 'drop',
                'connection-mark' => $connMark,
            ],
        ] as $rule) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $rule['comment']));
                if ($client->sendSync($printRequest)->getProperty('.id')) {
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
            } catch (Exception $e) {
                $errors[] = $rule['comment'] . ': ' . $e->getMessage();
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    /**
     * Blocage radical avant fasttrack/NAT : raw prerouting sur pppoe-expired.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    public static function ensurePppoeExpiredRawIsolation($client, $backendIp = '', $backendHttpPort = 0)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => []];
        }

        $commentTag = 'DYRSIA-PPPOE-EXPIRED';
        $expiredList = 'pppoe-expired';
        $allowList = 'pppoe-expired-allow';
        $errors = [];

        $backendIp = trim((string) $backendIp);
        $backendHttpPort = (int) $backendHttpPort;
        if ($backendIp !== '' && filter_var($backendIp, FILTER_VALIDATE_IP)) {
            self::ensureAddressListEntry($client, $allowList, $backendIp, $commentTag . ' backend');
        }
        foreach (['10.10.10.1', '10.0.0.1'] as $localGw) {
            self::ensureAddressListEntry($client, $allowList, $localGw, $commentTag . ' local');
        }

        $httpPorts = ['80', '443'];
        if ($backendHttpPort > 0 && $backendHttpPort !== 80 && $backendHttpPort !== 443) {
            $httpPorts[] = (string) $backendHttpPort;
        }

        $requiredRawComments = [
            $commentTag . ' raw allow backend',
            $commentTag . ' raw allow dns',
            $commentTag . ' raw allow http captive',
            $commentTag . ' raw block internet',
        ];
        if ($backendIp !== '' && filter_var($backendIp, FILTER_VALIDATE_IP)) {
            $requiredRawComments[] = $commentTag . ' raw allow backend tcp';
        }
        $rawComplete = true;
        foreach ($requiredRawComments as $requiredComment) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/raw/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $requiredComment));
                if (!$client->sendSync($printRequest)->getProperty('.id')) {
                    $rawComplete = false;
                    break;
                }
            } catch (Throwable $e) {
                $rawComplete = false;
                break;
            } catch (Exception $e) {
                $rawComplete = false;
                break;
            }
        }

        if (!$rawComplete) {
            self::removeRawFirewallRulesByCommentPrefix($client, $commentTag . ' raw ');
        }

        $rawRules = [
            [
                'comment' => $commentTag . ' raw allow backend',
                'chain' => 'prerouting',
                'action' => 'accept',
                'src-address-list' => $expiredList,
                'dst-address-list' => $allowList,
            ],
        ];
        if ($backendIp !== '' && filter_var($backendIp, FILTER_VALIDATE_IP)) {
            $rawRules[] = [
                'comment' => $commentTag . ' raw allow backend tcp',
                'chain' => 'prerouting',
                'action' => 'accept',
                'src-address-list' => $expiredList,
                'dst-address' => $backendIp,
                'protocol' => 'tcp',
            ];
        }
        $rawRules[] = [
            'comment' => $commentTag . ' raw allow dns',
            'chain' => 'prerouting',
            'action' => 'accept',
            'src-address-list' => $expiredList,
            'protocol' => 'udp',
            'dst-port' => '53',
        ];
        $rawRules[] = [
            'comment' => $commentTag . ' raw allow http captive',
            'chain' => 'prerouting',
            'action' => 'accept',
            'src-address-list' => $expiredList,
            'protocol' => 'tcp',
            'dst-port' => implode(',', $httpPorts),
        ];
        $rawRules[] = [
            'comment' => $commentTag . ' raw block internet',
            'chain' => 'prerouting',
            'action' => 'drop',
            'src-address-list' => $expiredList,
        ];

        foreach ($rawRules as $rule) {
            if ($rawComplete) {
                continue;
            }
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/raw/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $rule['comment']));
                if ($client->sendSync($printRequest)->getProperty('.id')) {
                    continue;
                }
                $addRequest = new RouterOS\Request('/ip/firewall/raw/add');
                foreach ($rule as $key => $value) {
                    if ($key === 'comment') {
                        continue;
                    }
                    $addRequest->setArgument($key, $value);
                }
                $addRequest->setArgument('comment', $rule['comment']);
                $client->sendSync($addRequest);
            } catch (Throwable $e) {
                $errors[] = $rule['comment'] . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $rule['comment'] . ': ' . $e->getMessage();
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    /**
     * Isolation complète : raw (prioritaire) + mangle/fasttrack + forward.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    public static function ensurePppoeExpiredIsolation($client, $backendIp = '', $backendHttpPort = 0)
    {
        if (self::$pppoeExpiredIsolationEnsured) {
            return ['ok' => true, 'errors' => []];
        }

        $errors = [];
        foreach ([
            self::ensurePppoeExpiredRawIsolation($client, $backendIp, $backendHttpPort),
            self::ensurePppoeExpiredFasttrackBypass($client),
        ] as $result) {
            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
            }
        }

        self::$pppoeExpiredIsolationEnsured = true;

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    private static function removeRawFirewallRulesByCommentPrefix($client, $prefix)
    {
        $prefix = trim((string) $prefix);
        if ($prefix === '') {
            return;
        }
        $safePrefix = str_replace('"', '', $prefix);
        self::runRouterOneShotScript(
            $client,
            'dyrsia_rm_raw',
            '/ip firewall raw remove [find comment~"' . $safePrefix . '"]'
        );
    }

    /**
     * Enregistre l'IP expirée en base pour l'interception du portail captive (boot.php).
     */
    public static function rememberPppoeExpiredClientMeta($login, $ip, $routerName = '')
    {
        $login = trim((string) $login);
        $ip = trim((string) $ip);
        if ($login === '' || $ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }
        $customer = ORM::for_table('tbl_customers')->where('pppoe_username', $login)->find_one();
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('username', $login)->find_one();
        }
        if (!$customer) {
            return;
        }
        $customerId = (int) $customer->id;
        User::setAttribute('pppoe_expired_ip', $ip, $customerId);
        if ($routerName !== '') {
            User::setAttribute('pppoe_expired_router', $routerName, $customerId);
        }
        User::setAttribute('pppoe_expired_user', $login, $customerId);
    }

    public static function ensureAddressListEntry($client, $listName, $ip, $comment = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return;
        }
        $listName = trim((string) $listName);
        $ip = trim((string) $ip);
        if ($listName === '' || $ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/address-list/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(
                RouterOS\Query::where('list', $listName)
                    ->andWhere('address', $ip)
            );
            if ($client->sendSync($printRequest)->getProperty('.id')) {
                return;
            }
            $addRequest = new RouterOS\Request('/ip/firewall/address-list/add');
            $addRequest->setArgument('list', $listName);
            $addRequest->setArgument('address', $ip);
            if ($comment !== '') {
                $addRequest->setArgument('comment', $comment);
            }
            $client->sendSync($addRequest);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * Force la suspension PPPoE (profil EXPIRE + liste firewall + coupure flux) pour tous les clients expirés du routeur.
     *
     * @return array{ok: bool, enforced: int, errors: array<int, string>}
     */
    public static function syncExpiredPppoeSuspensions($client, $routerName, $admin = null, $secretsAlreadySynced = false)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'enforced' => 0, 'errors' => []];
        }

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return ['ok' => false, 'enforced' => 0, 'errors' => ['Nom routeur manquant']];
        }

        self::ensurePppoeExpiredPlanDb($routerName, $admin);
        $expirePlan = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('name_plan', 'EXPIRE')
            ->find_one();
        if (!$expirePlan) {
            return ['ok' => false, 'enforced' => 0, 'errors' => ['Forfait EXPIRE introuvable sur ' . $routerName]];
        }

        global $config;
        $cfg = is_array($config) ? $config : [];
        $backendUrl = self::resolvePppoeCaptiveBackendUrl($cfg);
        $backendIp = self::resolveAppBackendIpv4($backendUrl);
        $backendPort = self::resolveAppBackendPort($backendUrl, false);
        $isolation = self::ensurePppoeExpiredIsolation($client, $backendIp ?: '', $backendPort);
        $errors = $isolation['errors'] ?? [];
        $enforced = 0;

        if (!$secretsAlreadySynced) {
            $now = date('Y-m-d H:i:s');
            $recharges = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where_raw("LOWER(type) = 'pppoe'")
                ->where_raw("(status = 'off' OR CONCAT(expiration, ' ', time) <= ?)", [$now])
                ->order_by_desc('id')
                ->find_many();

            $seenCustomers = [];
            self::withPppoeSharedClient($client, static function ($driver) use ($recharges, $expirePlan, &$seenCustomers, &$enforced, &$errors) {
                foreach ($recharges as $tur) {
                    $customerId = (int) $tur['customer_id'];
                    if ($customerId <= 0 || isset($seenCustomers[$customerId])) {
                        continue;
                    }
                    $seenCustomers[$customerId] = true;
                    $customer = ORM::for_table('tbl_customers')->find_one($customerId);
                    if (!$customer) {
                        continue;
                    }
                    try {
                        $driver->sync_customer($customer->as_array(), $expirePlan->as_array());
                        $enforced++;
                    } catch (Throwable $e) {
                        $login = $customer['pppoe_username'] ?? $customer['username'] ?? ('#' . $customerId);
                        $errors[] = $login . ': ' . $e->getMessage();
                    } catch (Exception $e) {
                        $login = $customer['pppoe_username'] ?? $customer['username'] ?? ('#' . $customerId);
                        $errors[] = $login . ': ' . $e->getMessage();
                    }
                }

                return null;
            });
        }

        $sweep = $secretsAlreadySynced
            ? ['ok' => true, 'enforced' => 0, 'errors' => []]
            : self::sweepActiveExpirePppoeSessions($client);
        if (!empty($sweep['errors'])) {
            $errors = array_merge($errors, $sweep['errors']);
        }

        return [
            'ok' => empty($errors),
            'enforced' => $enforced + (int) ($sweep['enforced'] ?? 0),
            'errors' => $errors,
        ];
    }

    /**
     * Renforce les suspensions PPPoE expirées sur tous les routeurs (cron / post-expiration).
     */
    public static function reinforceExpiredPppoeOnAllRouters()
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $routerNames = [];
        foreach (ORM::for_table('tbl_user_recharges')
            ->distinct()
            ->select('routers')
            ->where_raw("LOWER(type) = 'pppoe'")
            ->where_raw("(status = 'off' OR CONCAT(expiration, ' ', time) <= ?)", [$now])
            ->find_many() as $row) {
            $name = trim((string) ($row['routers'] ?? ''));
            if ($name !== '' && strcasecmp($name, 'radius') !== 0) {
                $routerNames[$name] = true;
            }
        }

        $total = 0;
        foreach (array_keys($routerNames) as $routerName) {
            $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
            if (!$router) {
                continue;
            }
            try {
                $password = self::routerPassword($router['password']);
                $client = self::getClient($router['ip_address'], $router['username'], $password, 30);
                if (!$client) {
                    continue;
                }
                $result = self::syncExpiredPppoeSuspensions($client, $routerName, null);
                $total += (int) ($result['enforced'] ?? 0);
            } catch (Throwable $e) {
                _log('[PPPoE expire enforce] ' . $routerName . ': ' . $e->getMessage());
            } catch (Exception $e) {
                _log('[PPPoE expire enforce] ' . $routerName . ': ' . $e->getMessage());
            }
        }

        return $total;
    }

    /**
     * Bandwidth « PPPOE-EXPIRED » (profil EXPIRE).
     */
    public static function findPppoeExpiredBandwidth($admin = null)
    {
        $query = ORM::for_table('tbl_bandwidth')
            ->where_raw("UPPER(TRIM(name_bw)) = 'PPPOE-EXPIRED'");
        $bw = $query->find_one();
        if ($bw) {
            return $bw;
        }

        return ORM::for_table('tbl_bandwidth')
            ->where_raw("UPPER(TRIM(name_bw)) LIKE '%EXPIRED%'")
            ->find_one();
    }

    public static function ensurePppoeExpiredBandwidthDb($admin = null)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return null;
        }

        $bw = self::findPppoeExpiredBandwidth($admin);
        if ($bw) {
            return $bw;
        }

        $bw = ORM::for_table('tbl_bandwidth')->create();
        $bw->name_bw = 'PPPOE-EXPIRED';
        $bw->rate_down = 1;
        $bw->rate_up = 1;
        $bw->rate_down_unit = 'Kbps';
        $bw->rate_up_unit = 'Kbps';
        $bw->burst = '';
        $bw->save();

        return $bw;
    }

    /**
     * Rate-limit MikroTik pour un forfait PPPoE (EXPIRE = 1 bps système).
     */
    public static function pppoeProfileRateLimit(array $planRow, $routerName = '', $admin = null)
    {
        $name = strtoupper(trim((string) ($planRow['name_plan'] ?? '')));
        if ($name === 'EXPIRE') {
            return self::pppoeExpireSystemRateLimit();
        }

        $bw = null;
        if (!empty($planRow['id_bw'])) {
            $bw = ORM::for_table('tbl_bandwidth')->find_one($planRow['id_bw']);
        }

        return self::hotspotPlanRateLimit($bw ? $bw->as_array() : null);
    }

    /**
     * Rate-limit EXPIRE (1 bps système).
     */
    public static function resolvePppoeExpireRateLimit($routerName, $admin = null)
    {
        return self::pppoeExpireSystemRateLimit();
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

        $profileMap = [];
        try {
            $printRequest = new RouterOS\Request('/ip/hotspot/user/profile/print');
            $printRequest->setArgument('.proplist', '.id,name,shared-users,rate-limit');
            foreach ($client->sendSync($printRequest) as $profile) {
                $profileName = trim((string) $profile->getProperty('name'));
                if ($profileName === '') {
                    continue;
                }
                $profileMap[$profileName] = [
                    'id' => $profile->getProperty('.id'),
                    'shared-users' => (string) $profile->getProperty('shared-users'),
                    'rate-limit' => (string) $profile->getProperty('rate-limit'),
                ];
            }
        } catch (Throwable $e) {
            $errors[] = 'list profiles: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'list profiles: ' . $e->getMessage();
        }

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
            $sharedUsersStr = (string) $sharedUsers;
            $rateStr = (string) $rate;

            try {
                if (isset($profileMap[$name])) {
                    $existing = $profileMap[$name];
                    if ($existing['shared-users'] === $sharedUsersStr && $existing['rate-limit'] === $rateStr) {
                        continue;
                    }
                    $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
                    $client->sendSync(
                        $setRequest
                            ->setArgument('numbers', $existing['id'])
                            ->setArgument('shared-users', $sharedUsersStr)
                            ->setArgument('rate-limit', $rateStr)
                    );
                    $profileMap[$name]['shared-users'] = $sharedUsersStr;
                    $profileMap[$name]['rate-limit'] = $rateStr;
                } else {
                    self::addHotspotPlan($client, $name, $sharedUsers, $rateStr);
                    $profileMap[$name] = [
                        'id' => null,
                        'shared-users' => $sharedUsersStr,
                        'rate-limit' => $rateStr,
                    ];
                }
                $upserted++;
            } catch (Throwable $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        $protectedProfiles = ['default'];
        $removed = 0;
        $expectedLookup = array_fill_keys($expectedNames, true);

        foreach ($profileMap as $profileName => $meta) {
            if ($profileName === '' || in_array($profileName, $protectedProfiles, true)) {
                continue;
            }
            if (isset($expectedLookup[$profileName])) {
                continue;
            }
            $profileId = $meta['id'] ?? null;
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

        self::ensurePppoeExpiredBandwidthDb($admin);

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
            $expiredBw = self::findPppoeExpiredBandwidth($admin);
            $expirePlan = ORM::for_table('tbl_plans')->create();
            $expirePlan->admin_id = $adminId;
            $expirePlan->name_plan = 'EXPIRE';
            $expirePlan->id_bw = $expiredBw ? (int) $expiredBw->id : (int) $refPlan['id_bw'];
            $expirePlan->price = 0;
            $expirePlan->type = 'PPPOE';
            $expirePlan->validity = 1;
            $expirePlan->validity_unit = 'Days';
            $expirePlan->routers = $routerName;
            $expirePlan->pool = $refPlan['pool'];
            $expirePlan->enabled = 1;
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
            $expiredBw = self::findPppoeExpiredBandwidth($admin);
            if ($expiredBw && (int) $expirePlan->id_bw !== (int) $expiredBw->id) {
                $expirePlan->id_bw = (int) $expiredBw->id;
                $changed = true;
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
            $plansQuery->where_raw('(admin_id = ? OR name_plan = ?)', [(int) ($admin['id'] ?? 0), 'EXPIRE']);
        }

        $expectedNames = ['default', 'EXPIRE'];
        $upserted = 0;
        $errors = [];
        $expiredOnUp = self::pppoeExpiredProfileOnUpScript();
        $expiredOnDown = self::pppoeExpiredProfileOnDownScript();

        foreach ($plansQuery->find_many() as $plan) {
            $name = trim((string) ($plan['name_plan'] ?? ''));
            if ($name === '') {
                continue;
            }
            $expectedNames[] = $name;

            $rate = self::pppoeProfileRateLimit($plan->as_array(), $routerName, $admin);
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
                ];
                if ($rate !== '') {
                    $args['rate-limit'] = $rate;
                }
                if ($isExpiredProfile) {
                    $args['on-up'] = $expiredOnUp;
                    $args['on-down'] = $expiredOnDown;
                    if ($localAddress !== '') {
                        $args['dns-server'] = $localAddress;
                    }
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
                if ($name === 'EXPIRE') {
                    self::unsetPppoeProfileRateLimit($client, $name);
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
     * Load MikrotikPppoe driver (system/devices, not PSR autoload).
     *
     * @return MikrotikPppoe
     */
    public static function pppoeDevice()
    {
        global $DEVICE_PATH;
        $deviceFile = $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikPppoe.php';
        if (!is_file($deviceFile)) {
            throw new Exception('Driver MikrotikPppoe introuvable');
        }
        require_once $deviceFile;

        return new MikrotikPppoe();
    }

    /**
     * @return array<int, string>
     */
    public static function pppoeLoginNamesForCustomer(array $customer)
    {
        $names = [];
        if (!empty($customer['pppoe_username'])) {
            $names[] = trim((string) $customer['pppoe_username']);
        }
        if (!empty($customer['username'])) {
            $names[] = trim((string) $customer['username']);
        }

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * Collect canonical PPPoE logins expected on a router (all tenants).
     *
     * @return array<string, true>
     */
    public static function collectPppoeExpectedLogins($routerName)
    {
        $routerName = trim((string) $routerName);
        $expected = [];
        $recharges = ORM::for_table('tbl_user_recharges')
            ->where('routers', $routerName)
            ->where_raw("LOWER(type) = 'pppoe'")
            ->find_many();
        foreach ($recharges as $tur) {
            $customer = ORM::for_table('tbl_customers')->find_one((int) $tur['customer_id']);
            if (!$customer) {
                continue;
            }
            foreach (self::pppoeLoginNamesForCustomer($customer->as_array()) as $login) {
                $expected[$login] = true;
            }
        }

        return $expected;
    }

    /**
     * Active recharge → forfait courant ; expiré / off → profil EXPIRE.
     */
    public static function resolvePppoeEffectivePlan(array $recharge, array $plan, $routerName, $admin = null)
    {
        $expirationRaw = trim((string) ($recharge['expiration'] ?? '') . ' ' . (string) ($recharge['time'] ?? ''));
        $expiresAt = $expirationRaw !== '' ? strtotime($expirationRaw) : false;
        $isActive = (($recharge['status'] ?? '') === 'on')
            && ($expiresAt === false || $expiresAt > time());

        if ($isActive) {
            return $plan;
        }

        self::ensurePppoeExpiredPlanDb($routerName, $admin);
        $expiredPlan = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('name_plan', 'EXPIRE')
            ->find_one();

        return $expiredPlan ? $expiredPlan->as_array() : $plan;
    }

    /**
     * Upsert PPPoE client secrets from recharges and remove orphan / legacy secrets.
     *
     * @return array{ok: bool, synced: int, removed: int, disconnected: int, errors: array<int, string>}
     */
    public static function syncPppoeSecrets($client, $routerName, $admin = null, $removeOrphans = true)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'synced' => 0, 'removed' => 0, 'disconnected' => 0, 'errors' => []];
        }

        $routerName = trim((string) $routerName);
        $errors = [];
        $synced = 0;
        $removed = 0;
        $disconnected = 0;

        $expectedLogins = self::collectPppoeExpectedLogins($routerName);

        $planIds = [];
        $plansQuery = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $routerName)
            ->where('is_radius', 0);
        if (is_array($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $plansQuery->where('admin_id', (int) ($admin['id'] ?? 0));
        }
        foreach ($plansQuery->find_many() as $planRow) {
            $planIds[] = (int) $planRow['id'];
        }

        $customersToSync = [];
        if (!empty($planIds)) {
            $rechargesQuery = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where_raw("LOWER(type) = 'pppoe'")
                ->where_in('plan_id', $planIds)
                ->order_by_desc('id');
            foreach ($rechargesQuery->find_many() as $tur) {
                $customerId = (int) $tur['customer_id'];
                if ($customerId <= 0 || isset($customersToSync[$customerId])) {
                    continue;
                }
                $customer = ORM::for_table('tbl_customers')->find_one($customerId);
                if (!$customer) {
                    continue;
                }
                $plan = ORM::for_table('tbl_plans')->find_one((int) $tur['plan_id']);
                if (!$plan) {
                    continue;
                }
                $customersToSync[$customerId] = [
                    'customer' => $customer->as_array(),
                    'plan' => self::resolvePppoeEffectivePlan($tur->as_array(), $plan->as_array(), $routerName, $admin),
                ];
            }
        }

        if ($removeOrphans) {
            try {
                $printRequest = new RouterOS\Request('/ppp/secret/print');
                $printRequest->setArgument('.proplist', '.id,name,service');
                foreach ($client->sendSync($printRequest) as $row) {
                    $name = trim((string) $row->getProperty('name'));
                    if ($name === '') {
                        continue;
                    }
                    $service = strtolower(trim((string) $row->getProperty('service')));
                    if ($service !== '' && $service !== 'pppoe' && $service !== 'any') {
                        continue;
                    }

                    $isLegacyName = strpos($name, ' | ') !== false;
                    $isExpected = isset($expectedLogins[$name]);
                    if ($isExpected && !$isLegacyName) {
                        continue;
                    }

                    $secretId = $row->getProperty('.id');
                    if (empty($secretId)) {
                        continue;
                    }

                    try {
                        if (self::removePpoeActive($client, $name)) {
                            $disconnected++;
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'disconnect ' . $name . ': ' . $e->getMessage();
                    } catch (Exception $e) {
                        $errors[] = 'disconnect ' . $name . ': ' . $e->getMessage();
                    }

                    try {
                        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
                        $removeRequest->setArgument('numbers', $secretId);
                        $client->sendSync($removeRequest);
                        $removed++;
                    } catch (Throwable $e) {
                        $errors[] = 'remove secret ' . $name . ': ' . $e->getMessage();
                    } catch (Exception $e) {
                        $errors[] = 'remove secret ' . $name . ': ' . $e->getMessage();
                    }
                }
            } catch (Throwable $e) {
                $errors[] = 'list ppp secrets: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'list ppp secrets: ' . $e->getMessage();
            }
        }

        try {
            self::withPppoeSharedClient($client, static function ($driver) use ($customersToSync, &$synced, &$errors) {
                foreach ($customersToSync as $entry) {
                    try {
                        $driver->sync_customer($entry['customer'], $entry['plan']);
                        $synced++;
                    } catch (Throwable $e) {
                        $login = $entry['customer']['pppoe_username'] ?? $entry['customer']['username'] ?? '?';
                        $errors[] = $login . ': ' . $e->getMessage();
                    } catch (Exception $e) {
                        $login = $entry['customer']['pppoe_username'] ?? $entry['customer']['username'] ?? '?';
                        $errors[] = $login . ': ' . $e->getMessage();
                    }
                }

                return null;
            });
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'synced' => $synced,
                'removed' => $removed,
                'disconnected' => $disconnected,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'synced' => $synced,
                'removed' => $removed,
                'disconnected' => $disconnected,
                'errors' => array_merge($errors, [$e->getMessage()]),
            ];
        }

        $sweep = self::sweepActiveExpirePppoeSessions($client);
        if (!empty($sweep['errors'])) {
            $errors = array_merge($errors, $sweep['errors']);
        }

        return [
            'ok' => empty($errors),
            'synced' => $synced,
            'removed' => $removed,
            'disconnected' => $disconnected,
            'errors' => $errors,
            'expired_swept' => (int) ($sweep['enforced'] ?? 0),
        ];
    }

    /**
     * Block bridged IP traffic on PPPoE ports (clients must authenticate via PPPoE).
     *
     * @return array{ok: bool, added: bool, errors: array<int, string>}
     */
    public static function ensurePppoeBridgeForwardBlock($client, $bridgeName)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'added' => false, 'errors' => []];
        }

        $bridgeName = trim((string) $bridgeName);
        if ($bridgeName === '') {
            return ['ok' => false, 'added' => false, 'errors' => ['Nom du bridge PPPoE manquant.']];
        }

        $comment = 'DYRSIA: block IP bypass PPPoE';
        $errors = [];
        $added = false;

        try {
            $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
            $exists = (bool) $client->sendSync($printRequest)->getProperty('.id');
            if (!$exists) {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id,chain,in-interface,action');
                $printRequest->setQuery(
                    RouterOS\Query::where('chain', 'forward')
                        ->andWhere('in-interface', $bridgeName)
                        ->andWhere('action', 'drop')
                );
                $exists = (bool) $client->sendSync($printRequest)->getProperty('.id');
            }

            if (!$exists) {
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                $addRequest->setArgument('chain', 'forward');
                $addRequest->setArgument('in-interface', $bridgeName);
                $addRequest->setArgument('action', 'drop');
                $addRequest->setArgument('comment', $comment);
                $client->sendSync($addRequest);
                $added = true;
            }
        } catch (Throwable $e) {
            $errors[] = 'firewall bridge block: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'firewall bridge block: ' . $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'added' => $added,
            'errors' => $errors,
        ];
    }

    /**
     * Profiles + client secrets + anti-bypass firewall for one PPPoE router.
     *
     * @return array{ok: bool, plans: array<string, mixed>, secrets: array<string, mixed>, firewall: array<string, mixed>}
     */
    public static function fullPppoeRouterSync($client, $routerName, $admin = null, $bridgeName = '')
    {
        global $config;

        self::resetPppoeSyncRuntimeState();

        $routerName = trim((string) $routerName);
        if ($bridgeName === '') {
            $bridgeName = trim((string) ($config['pppoe_setup_bridge'] ?? 'bridge-pppoe'));
        }

        $planSync = self::syncPppoePlans($client, $routerName, $admin);
        $secretSync = self::syncPppoeSecrets($client, $routerName, $admin, true);
        $firewall = self::ensurePppoeBridgeForwardBlock($client, $bridgeName);

        $captive = ['ok' => true, 'errors' => []];
        $backendUrl = self::resolvePppoeCaptiveBackendUrl(is_array($config) ? $config : []);
        $portalUrl = self::buildPppoeCaptivePortalUrl($routerName, is_array($config) ? $config : []);
        if ($backendUrl !== '' && $portalUrl !== '') {
            $captive = self::ensurePppoeExpiredCaptive($client, $portalUrl, $backendUrl, $routerName);
        } elseif ($backendUrl === '') {
            $captive = [
                'ok' => false,
                'errors' => [
                    'URL backend captive introuvable — Settings → Hotspot → Hotspot API URL (ex. http://10.0.0.2:8080 ou https://wifizones.org)',
                ],
            ];
        }

        $suspensions = self::syncExpiredPppoeSuspensions($client, $routerName, $admin, true);

        $errors = array_merge(
            $planSync['errors'] ?? [],
            $secretSync['errors'] ?? [],
            $firewall['errors'] ?? [],
            $captive['errors'] ?? [],
            $suspensions['errors'] ?? []
        );

        return [
            'ok' => empty($errors),
            'plans' => $planSync,
            'secrets' => $secretSync,
            'firewall' => $firewall,
            'captive' => $captive,
            'suspensions' => $suspensions,
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

    /**
     * IP WireGuard / VPN locale (ex. 10.0.0.2 sur le Mac dev).
     */
    public static function detectLocalVpnIpv4()
    {
        if (stripos(PHP_OS_FAMILY, 'Windows') === false) {
            $output = @shell_exec("ifconfig 2>/dev/null | awk '/inet / {print $2}'");
            if (is_string($output) && $output !== '') {
                foreach (preg_split('/\s+/', trim($output)) as $ip) {
                    $ip = trim($ip);
                    if (preg_match('/^10\.0\.0\.\d+$/', $ip)) {
                        return $ip;
                    }
                }
            }
        }
        if (!empty($_SERVER['SERVER_ADDR']) && preg_match('/^10\.0\.0\.\d+$/', (string) $_SERVER['SERVER_ADDR'])) {
            return (string) $_SERVER['SERVER_ADDR'];
        }

        return null;
    }

    /**
     * URL du serveur DYRSIA joignable depuis le MikroTik (pas localhost).
     */
    public static function resolvePppoeCaptiveBackendUrl(array $appConfig = [])
    {
        global $config;
        $cfg = !empty($appConfig) ? $appConfig : (is_array($config) ? $config : []);

        $candidates = [];
        foreach (['pppoe_captive_url', 'hotspot_api_url'] as $key) {
            $value = trim((string) ($cfg[$key] ?? ''));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }
        if (defined('APP_URL')) {
            $candidates[] = APP_URL;
        }

        foreach ($candidates as $url) {
            $url = self::normalizePppoeCaptiveBackendUrl(trim($url));
            if ($url !== '' && self::isRouterFetchableUrl($url)) {
                return rtrim($url, '/');
            }
        }

        if (defined('APP_URL')) {
            $appHost = strtolower((string) parse_url(APP_URL, PHP_URL_HOST));
            if (in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)) {
                $vpnIp = self::detectLocalVpnIpv4();
                if ($vpnIp) {
                    $scheme = parse_url(APP_URL, PHP_URL_SCHEME) ?: 'http';
                    $port = (int) parse_url(APP_URL, PHP_URL_PORT);
                    if ($port <= 0) {
                        $port = ($scheme === 'https') ? 443 : 80;
                    }
                    $url = $scheme . '://' . $vpnIp;
                    if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                        $url .= ':' . $port;
                    }
                    if (self::isRouterFetchableUrl($url)) {
                        return rtrim($url, '/');
                    }
                }
            }
        }

        return '';
    }

    /**
     * Normalise l'URL backend PPPoE (VPS 10.0.0.1 → port 80 ; dev 10.0.0.2 garde 8080).
     */
    public static function normalizePppoeCaptiveBackendUrl($url)
    {
        $url = trim((string) $url);
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return $url;
        }
        $host = $parts['host'];
        $scheme = $parts['scheme'] ?? 'http';
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if (filter_var($host, FILTER_VALIDATE_IP) && preg_match('/^10\.0\.0\.1$/', $host)) {
            if ($port === null || $port === 8080) {
                $port = ($scheme === 'https') ? 443 : 80;
            }
        } elseif ($port === null) {
            $port = ($scheme === 'https') ? 443 : 80;
        }
        $out = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $out .= ':' . $port;
        }

        return $out;
    }

    public static function buildPppoeCaptivePortalUrl($routerName, array $appConfig = [])
    {
        $base = self::resolvePppoeCaptiveBackendUrl($appConfig);
        $routerName = trim((string) $routerName);
        if ($base === '' || $routerName === '') {
            return '';
        }

        return rtrim($base, '/')
            . '/index.php?_route=plugin/pppoe_portal&router='
            . rawurlencode($routerName);
    }

    private static function ensureNatRuleByComment($client, $comment, array $args, $placeBefore = null)
    {
        try {
            $printRequest = new RouterOS\Request('/ip/firewall/nat/print');
            $printRequest->setArgument('.proplist', 'comment,chain,action,protocol,dst-port,src-address-list,dst-address,to-addresses,to-ports');
            $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
            $existing = $client->sendSync($printRequest);
            foreach ($existing as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $matches = true;
                foreach ($args as $key => $value) {
                    if ((string) $row->getProperty($key) !== (string) $value) {
                        $matches = false;
                        break;
                    }
                }
                if ($matches) {
                    return true;
                }
            }

            self::removeFirewallRulesByComment($client, $comment);
            $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
            foreach ($args as $key => $value) {
                $addRequest->setArgument($key, $value);
            }
            $addRequest->setArgument('comment', $comment);
            if (!empty($placeBefore)) {
                $addRequest->setArgument('place-before', $placeBefore);
            }
            $client->sendSync($addRequest);

            return true;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * Cible NAT captive : backend DYRSIA direct (pas le proxy hotspot — mauvais port en dev).
     *
     * @return array{ip: string, port: int, via_proxy: bool}
     */
    private static function resolvePppoeCaptiveNatDestination($client, $backendIp, $httpPort)
    {
        $backendIp = trim((string) $backendIp);
        $httpPort = (int) $httpPort;

        return [
            'ip' => $backendIp,
            'port' => $httpPort > 0 ? $httpPort : 80,
            'via_proxy' => false,
        ];
    }

    /**
     * SNAT obligatoire quand le backend est joign directement (réponses via le routeur).
     */
    private static function ensurePppoeExpiredCaptiveBackendSnat($client, $backendIp, $httpPort, $httpsPort, $commentTag)
    {
        $backendIp = trim((string) $backendIp);
        if ($backendIp === '') {
            return;
        }
        $comment = $commentTag . ' SNAT backend';
        self::removeFirewallRulesByComment($client, $comment);
        try {
            $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
            $addRequest->setArgument('chain', 'srcnat');
            $addRequest->setArgument('protocol', 'tcp');
            $addRequest->setArgument('dst-address', $backendIp);
            $addRequest->setArgument('src-address-list', 'pppoe-expired');
            $addRequest->setArgument('action', 'masquerade');
            $addRequest->setArgument('comment', $comment);
            $client->sendSync($addRequest);
        } catch (Throwable $e) {
        } catch (Exception $e) {
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
     * Autorise les clients expirés à interroger le DNS du routeur (static → backend).
     */
    private static function ensurePppoeExpiredCaptiveDnsInput($client, $commentTag)
    {
        foreach ([
            $commentTag . ' input allow dns udp' => ['protocol' => 'udp'],
            $commentTag . ' input allow dns tcp' => ['protocol' => 'tcp'],
        ] as $comment => $extra) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $comment));
                if ($client->sendSync($printRequest)->getProperty('.id')) {
                    continue;
                }
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                $addRequest->setArgument('chain', 'input');
                $addRequest->setArgument('action', 'accept');
                $addRequest->setArgument('src-address-list', 'pppoe-expired');
                $addRequest->setArgument('dst-port', '53');
                foreach ($extra as $key => $value) {
                    $addRequest->setArgument($key, $value);
                }
                $addRequest->setArgument('comment', $comment);
                $client->sendSync($addRequest);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }
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
        global $config;
        $cfg = is_array($config) ? $config : [];
        $resolvedBackend = self::resolvePppoeCaptiveBackendUrl($cfg);
        if ($resolvedBackend !== '') {
            $appUrl = $resolvedBackend;
            $portalHost = strtolower((string) parse_url($portalUrl, PHP_URL_HOST));
            if ($portalUrl === '' || in_array($portalHost, ['localhost', '127.0.0.1', '::1'], true)) {
                $builtPortal = self::buildPppoeCaptivePortalUrl($routerName, $cfg);
                if ($builtPortal !== '') {
                    $portalUrl = $builtPortal;
                }
            }
        } else {
            $appUrl = trim((string) ($appUrl ?: (defined('APP_URL') ? APP_URL : '')));
        }
        $errors = [];

        $backendIp = self::resolveAppBackendIpv4($appUrl);
        $backendHttpPort = self::resolveAppBackendPort($appUrl, false);
        if (self::isPppoeExpiredCaptiveConfigured($client)) {
            if ($routerName !== '') {
                self::syncPppoeExpiredClientMeta($client, $routerName);
            }
            $isolation = self::ensurePppoeExpiredIsolation($client, $backendIp ?: '', $backendHttpPort);
            if (!empty($isolation['errors'])) {
                $errors = array_merge($errors, $isolation['errors']);
            }

            return [
                'ok' => empty($errors),
                'errors' => $errors,
                'portal_url' => $portalUrl,
                'backend_ip' => $backendIp,
            ];
        }

        if ($portalUrl !== '') {
            self::pruneHotspotWalledGardenBatch($client);
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
        self::removeFirewallRulesByComment($client, $commentTag . ' allow established');

        $backendIp = self::resolveAppBackendIpv4($appUrl);
        $backendHttpPort = self::resolveAppBackendPort($appUrl, false);
        if ($backendIp && in_array($backendIp, ['127.0.0.1', '0.0.0.0'], true)) {
            $backendIp = null;
        }
        if ($backendIp) {
            $allowHosts[] = $backendIp;
            $allowHosts = array_values(array_unique(array_filter($allowHosts)));

            $httpPort = $backendHttpPort;
            $httpsPort = self::resolveAppBackendPort($appUrl, true);
            if (strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'http') {
                $httpsPort = $httpPort;
            }

            $natTarget = self::resolvePppoeCaptiveNatDestination($client, $backendIp, $httpPort);
            $redirectIp = $natTarget['ip'];
            $redirectHttpPort = $natTarget['port'];
            $redirectHttpsPort = $natTarget['via_proxy'] ? $natTarget['port'] : $httpsPort;

            $firstDstNatRuleId = null;
            try {
                $firstDstNatRuleId = $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/nat/print'))
                        ->setArgument('.proplist', '.id')
                        ->setQuery(RouterOS\Query::where('chain', 'dstnat'))
                )->getProperty('.id');
            } catch (Throwable $e) {
                $firstDstNatRuleId = null;
            }

            $natRules = [
                $commentTag . ' redirect http captive' => [
                    'chain' => 'dstnat',
                    'protocol' => 'tcp',
                    'dst-port' => '80',
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'dst-nat',
                    'to-addresses' => $redirectIp,
                    'to-ports' => (string) $redirectHttpPort,
                ],
                $commentTag . ' redirect https captive' => [
                    'chain' => 'dstnat',
                    'protocol' => 'tcp',
                    'dst-port' => '443',
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'dst-nat',
                    'to-addresses' => $redirectIp,
                    'to-ports' => (string) $redirectHttpsPort,
                ],
                $commentTag . ' redirect backend port 80' => [
                    'chain' => 'dstnat',
                    'protocol' => 'tcp',
                    'dst-port' => '80',
                    'dst-address' => $backendIp,
                    'src-address-list' => 'pppoe-expired',
                    'action' => 'dst-nat',
                    'to-addresses' => $backendIp,
                    'to-ports' => (string) $httpPort,
                ],
            ];
            self::removeFirewallRulesByComment($client, $commentTag . ' redirect dns');
            foreach (array_keys($natRules) as $natComment) {
                self::removeFirewallRulesByComment($client, $natComment);
            }
            foreach ($natRules as $comment => $args) {
                $result = self::ensureNatRuleByComment($client, $comment, $args, $firstDstNatRuleId);
                if ($result !== true) {
                    $errors[] = $comment . ': ' . $result;
                }
            }

            if (empty($natTarget['via_proxy'])) {
                self::ensurePppoeExpiredCaptiveBackendSnat($client, $backendIp, $httpPort, $httpsPort, $commentTag);
            }

            $dnsErrors = self::ensurePppoeCaptiveDetectionDns($client, $backendIp);
            $errors = array_merge($errors, $dnsErrors);
            self::ensurePppoeExpiredCaptiveDnsInput($client, $commentTag);

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
            $hint = 'Configurez Settings → Hotspot → Hotspot API URL (ex. http://10.0.0.2:8080 en VPN dev, https://wifizones.org en prod).';
            if ($resolvedBackend === '' && defined('APP_URL') && in_array(
                strtolower((string) parse_url(APP_URL, PHP_URL_HOST)),
                ['localhost', '127.0.0.1', '::1'],
                true
            )) {
                $vpnIp = self::detectLocalVpnIpv4();
                $hint = $vpnIp
                    ? 'APP_URL = localhost — utilisez Hotspot API URL : http://' . $vpnIp . ':' . max(80, (int) parse_url(APP_URL, PHP_URL_PORT) ?: 8080)
                    : 'APP_URL = localhost — indiquez Hotspot API URL avec l\'IP VPN du serveur DYRSIA (ex. http://10.0.0.2:8080).';
            }
            $errors[] = $commentTag . ': IP backend introuvable pour ' . $appUrl . '. ' . $hint;
        }

        if ($routerName !== '') {
            self::syncPppoeExpiredClientMeta($client, $routerName);
        }

        $isolation = self::ensurePppoeExpiredIsolation($client, $backendIp ?: '', $backendHttpPort);
        if (!empty($isolation['errors'])) {
            $errors = array_merge($errors, $isolation['errors']);
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
        if (empty($id)) {
            return false;
        }

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);

        return true;
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
            $txtFrom = $from . '.txt';
            if (self::getRouterFileSize($client, $txtFrom) > 0 || self::routerFileExists($client, $txtFrom)) {
                $from = $txtFrom;
            } elseif (!self::routerFileExists($client, $from)) {
                return false;
            }
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
     * RouterOS 7 often stores API-created files as "name.txt" after /file/print file=name.
     */
    public static function routerFileExists($client, $path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'name,type')
                    ->setQuery(RouterOS\Query::where('name', $path))
            );
            foreach ($responses as $response) {
                $name = (string) $response->getProperty('name');
                $type = (string) $response->getProperty('type');
                if ($name === $path && $type !== 'directory') {
                    return true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * Resolve the actual router filename after /file/print file=… (plain name or .txt suffix).
     *
     * @return string|null
     */
    private static function resolveRouterWritePath($client, $path, $createIfMissing = false)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $txtPath = $path . '.txt';
        if (self::routerFileExists($client, $path)) {
            return $path;
        }
        if (self::routerFileExists($client, $txtPath)) {
            return $txtPath;
        }
        if (!$createIfMissing) {
            return null;
        }

        self::removeRouterFile($client, $path);
        self::removeRouterFile($client, $txtPath);

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('file', $path)
            );
            foreach ($responses->getAllOfType(RouterOS\Response::TYPE_ERROR) as $response) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }

        usleep(800000);

        if (self::routerFileExists($client, $path)) {
            return $path;
        }
        if (self::routerFileExists($client, $txtPath)) {
            return $txtPath;
        }

        return null;
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

        self::removeRouterFile($client, $path);
        self::removeRouterFile($client, $path . '.txt');

        // RouterOS 7 accepts login.html (~20 KB) in one /file/set. Prefer that — append
        // scripts are often denied for API users (Dyrsia-access) even when /file/set works.
        $singleShotLimit = 65536;
        if ($length <= $singleShotLimit) {
            if (!self::tryRouterFileWrite($client, $path, $contents)) {
                return false;
            }
            $writePath = self::resolveRouterWritePath($client, $path, false);
            if ($writePath !== null && $writePath !== $path) {
                return self::renameRouterFile($client, $writePath, $path);
            }

            return true;
        }

        $chunkSize = 2800;
        $offset = 0;
        $first = true;
        $writePath = null;
        $accumulated = '';
        while ($offset < $length) {
            $chunk = substr($contents, $offset, $chunkSize);
            if ($chunk === '') {
                break;
            }
            $offset += strlen($chunk);
            $accumulated .= $chunk;
            if ($first) {
                $written = self::tryRouterFileWrite($client, $path, $accumulated);
                $writePath = self::resolveRouterWritePath($client, $path, false);
                $first = false;
            } else {
                $appendPath = $writePath ?? self::resolveRouterWritePath($client, $path, false) ?? $path;
                $written = self::tryRouterFileUpdate($client, $appendPath, $accumulated);
            }
            if (!$written) {
                return false;
            }
            usleep(120000);
        }

        usleep(200000);

        $finalPath = $writePath ?? self::resolveRouterWritePath($client, $path, false) ?? $path;
        $writtenSize = self::getRouterFileSize($client, $finalPath);
        if ($writtenSize >= (int) ($length * 0.9)) {
            if ($finalPath !== $path) {
                return self::renameRouterFile($client, $finalPath, $path);
            }

            return true;
        }

        $txtPath = $path . '.txt';
        if ($finalPath !== $txtPath) {
            $txtSize = self::getRouterFileSize($client, $txtPath);
            if ($txtSize >= (int) ($length * 0.9)) {
                return self::renameRouterFile($client, $txtPath, $path);
            }
        }

        return false;
    }

    /**
     * Overwrite an existing router file (no recreate — used for growing uploads).
     *
     * @return bool
     */
    private static function tryRouterFileUpdate($client, $writePath, $contents)
    {
        $writePath = trim((string) $writePath);
        $contents = (string) $contents;
        if ($writePath === '' || !self::routerFileExists($client, $writePath)) {
            return false;
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/set'))
                    ->setArgument('numbers', $writePath)
                    ->setArgument('contents', $contents)
            );
            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                    return false;
                }
            }
            usleep(400000);
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return self::getRouterFileSize($client, $writePath) === strlen($contents);
    }

    /**
     * Append binaire via script RouterOS (:frombase64, ROS7+ ; :fromhex fallback ROS6).
     */
    private static function routerFileAppendBase64Chunk($client, $path, $chunk)
    {
        $path = trim((string) $path);
        $chunk = (string) $chunk;
        if ($path === '' || $chunk === '') {
            return true;
        }

        $pathEsc = str_replace(['\\', '"'], ['\\\\', '\\"'], $path);
        $b64 = base64_encode($chunk);
        $hex = bin2hex($chunk);
        $scriptName = 'dyrsia_append_' . substr(md5($path . $b64), 0, 10);
        $source = ':do {'
            . ' :local f [/file find name="' . $pathEsc . '"];'
            . ' :if ([:len $f]=0) do={ :error "missing file"; };'
            . ' :local cur [/file get $f contents];'
            . ' :local add "";'
            . ' :do { :set add [:tochar [:frombase64 "' . $b64 . '"]]; } on-error={'
            . '   :set add [:tochar [:fromhex "' . $hex . '"]];'
            . ' };'
            . ' :if ([:len $add]=0) do={ :error "decode failed"; };'
            . ' /file set $f contents=($cur . $add);'
            . ' } on-error={ :error $message; };';

        return self::runRouterOneShotScript($client, $scriptName, $source);
    }

    private static function runRouterOneShotScript($client, $scriptName, $source)
    {
        $scriptName = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $scriptName);
        if ($scriptName === '') {
            $scriptName = 'dyrsia_tmp';
        }

        try {
            $existing = $client->sendSync(
                (new RouterOS\Request('/system/script/print'))
                    ->setArgument('.proplist', '.id')
                    ->setQuery(RouterOS\Query::where('name', $scriptName))
            );
            foreach ($existing as $row) {
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/system/script/remove'))
                            ->setArgument('numbers', $id)
                    );
                }
            }

            $client->sendSync(
                (new RouterOS\Request('/system/script/add'))
                    ->setArgument('name', $scriptName)
                    ->setArgument('source', $source)
            );
            $responses = $client->sendSync(
                (new RouterOS\Request('/system/script/run'))
                    ->setArgument('number', $scriptName)
            );
            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                    return false;
                }
            }
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        try {
            $existing = $client->sendSync(
                (new RouterOS\Request('/system/script/print'))
                    ->setArgument('.proplist', '.id')
                    ->setQuery(RouterOS\Query::where('name', $scriptName))
            );
            foreach ($existing as $row) {
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/system/script/remove'))
                            ->setArgument('numbers', $id)
                    );
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return true;
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
    public static function buildHotspotLoginFetchUrls($apiUrl, $appUrl, $fetchTs = null, array $preferredBases = [], $includePublicDeploy = false)
    {
        $fetchTs = $fetchTs ?? time();
        if ($includePublicDeploy) {
            foreach (['https://wifizones.org', 'https://www.wifizones.org'] as $publicBase) {
                if (!in_array($publicBase, $preferredBases, true)) {
                    array_unshift($preferredBases, $publicBase);
                }
            }
        }
        $bases = [];
        foreach (array_merge($preferredBases, [$apiUrl, $appUrl]) as $base) {
            $base = rtrim((string) $base, '/');
            if ($base === '' || !self::isRouterFetchableUrl($base)) {
                continue;
            }
            if (!in_array($base, $bases, true)) {
                $bases[] = $base;
            }
        }
        if (empty($bases)) {
            return [];
        }

        $urls = [];
        foreach ([
            $bases[0] . '/system/uploads/mikrotik_hotspot/login.html?ts=' . $fetchTs,
            $bases[0] . '/index.php?_route=plugin/hotspot_login_file&ts=' . $fetchTs,
            $bases[0] . '/hotspot_login.html?ts=' . $fetchTs,
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
                $removeRequest = new RouterOS\Request($chainPath . '/remove');
                $removeRequest->setQuery(RouterOS\Query::where('comment', $comment));
                $client->sendSync($removeRequest);
            } catch (Throwable $e) {
                $errors[] = $chainPath . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $chainPath . ': ' . $e->getMessage();
            }
        }

        return empty($errors) ? ['ok' => true] : ['ok' => false, 'errors' => $errors];
    }

    private static function hotspotWalledGardenIpPrintPath($client)
    {
        foreach (['/ip/hotspot/walled-garden/ip', '/ip hotspot walled-garden ip'] as $wgPath) {
            try {
                $client->sendSync(
                    (new RouterOS\Request($wgPath . '/print'))
                        ->setArgument('.proplist', '.id')
                        ->setArgument('.count-only', 'true')
                );
                return $wgPath;
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        return '/ip/hotspot/walled-garden/ip';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function fetchHotspotWalledGardenIpRows($client)
    {
        $wgPath = self::hotspotWalledGardenIpPrintPath($client);
        $rows = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request($wgPath . '/print'))
                    ->setArgument('.proplist', '.id,dst-host,dst-address,dst-port,protocol,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $rows[] = [
                    'id' => (string) $row->getProperty('.id'),
                    'dst-host' => strtolower(trim((string) $row->getProperty('dst-host'))),
                    'dst-address' => trim((string) $row->getProperty('dst-address')),
                    'dst-port' => trim((string) $row->getProperty('dst-port')),
                ];
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $rows;
    }

    private static function hotspotWalledGardenHostKey($host, $port, $isIp)
    {
        $host = strtolower(trim((string) $host));
        $port = trim((string) $port);
        return ($isIp ? 'ip:' : 'host:') . $host . ':' . $port;
    }

    private static function hotspotWalledGardenRowMatches($row, $host, $port, $isIp)
    {
        $field = $isIp ? 'dst-address' : 'dst-host';
        $value = $isIp ? trim((string) $host) : strtolower(trim((string) $host));
        $rowValue = $isIp ? trim((string) ($row['dst-address'] ?? '')) : strtolower(trim((string) ($row['dst-host'] ?? '')));
        if ($rowValue === '' || strcasecmp($rowValue, $value) !== 0) {
            return false;
        }
        $rowPort = trim((string) ($row['dst-port'] ?? ''));
        return $rowPort === '' || $rowPort === (string) $port;
    }

    /**
     * @param array<int, string> $apiUrls
     * @return array{ok: bool, errors?: array<int, string>}
     */
    public static function ensureHotspotWalledGardenBatch($client, array $apiUrls)
    {
        $targets = [];
        foreach ($apiUrls as $apiUrl) {
            $apiUrl = trim((string) $apiUrl);
            $apiHost = parse_url($apiUrl, PHP_URL_HOST);
            if (!$apiHost) {
                continue;
            }
            $apiPort = parse_url($apiUrl, PHP_URL_PORT);
            $apiScheme = parse_url($apiUrl, PHP_URL_SCHEME);
            if (!$apiPort) {
                $apiPort = $apiScheme === 'https' ? 443 : 80;
            }
            $isIp = filter_var($apiHost, FILTER_VALIDATE_IP) !== false;
            $key = self::hotspotWalledGardenHostKey($apiHost, $apiPort, $isIp);
            $targets[$key] = [
                'host' => $apiHost,
                'port' => (string) $apiPort,
                'url' => $apiUrl,
                'isIp' => $isIp,
            ];
        }
        if ($targets === []) {
            return ['ok' => false, 'errors' => ['Hotspot API URL invalide']];
        }

        $wgPath = self::hotspotWalledGardenIpPrintPath($client);
        $existing = self::fetchHotspotWalledGardenIpRows($client);
        $errors = [];

        foreach ($targets as $target) {
            $found = false;
            foreach ($existing as $row) {
                if (self::hotspotWalledGardenRowMatches($row, $target['host'], $target['port'], $target['isIp'])) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }

            $field = $target['isIp'] ? 'dst-address' : 'dst-host';
            try {
                $client->sendSync(
                    (new RouterOS\Request($wgPath . '/add'))
                        ->setArgument($field, $target['host'])
                        ->setArgument('protocol', 'tcp')
                        ->setArgument('dst-port', $target['port'])
                        ->setArgument('action', 'accept')
                        ->setArgument('comment', 'WifiZone hotspot API ' . $target['url'])
                );
                $existing[] = [
                    'id' => '',
                    'dst-host' => $target['isIp'] ? '' : strtolower($target['host']),
                    'dst-address' => $target['isIp'] ? $target['host'] : '',
                    'dst-port' => $target['port'],
                ];
            } catch (Throwable $e) {
                $errors[] = $target['url'] . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $target['url'] . ': ' . $e->getMessage();
            }
        }

        return empty($errors) ? ['ok' => true] : ['ok' => false, 'errors' => $errors];
    }

    private static function hotspotApiNatProxyConfigured($client, $listenIp, $listenPort, $backendHost, $backendPort, $proxyComment, $snatComment, $inputComment)
    {
        $hasProxy = false;
        $hasSnat = false;
        $hasInput = false;
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'comment,dst-address,dst-port,to-addresses,to-ports,action')
            ) as $row) {
                $comment = (string) $row->getProperty('comment');
                if ($comment === $proxyComment
                    && (string) $row->getProperty('dst-address') === $listenIp
                    && (string) $row->getProperty('dst-port') === (string) $listenPort
                    && (string) $row->getProperty('to-addresses') === $backendHost
                    && (string) $row->getProperty('to-ports') === (string) $backendPort) {
                    $hasProxy = true;
                }
                if ($comment === $snatComment
                    && (string) $row->getProperty('dst-address') === $backendHost
                    && (string) $row->getProperty('dst-port') === (string) $backendPort
                    && (string) $row->getProperty('action') === 'masquerade') {
                    $hasSnat = true;
                }
            }
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', 'comment,dst-address,dst-port,action')
            ) as $row) {
                if ((string) $row->getProperty('comment') === $inputComment
                    && (string) $row->getProperty('dst-address') === $listenIp
                    && (string) $row->getProperty('dst-port') === (string) $listenPort
                    && (string) $row->getProperty('action') === 'accept') {
                    $hasInput = true;
                }
            }
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return $hasProxy && $hasSnat && $hasInput;
    }

    /**
     * Autorise le serveur API dans le walled-garden hotspot (avant fetch login.html).
     *
     * @return array{ok: bool, errors?: array<int, string>}
     */
    public static function ensureHotspotWalledGarden($client, $apiUrl)
    {
        return self::ensureHotspotWalledGardenBatch($client, [(string) $apiUrl]);
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
            if (self::hotspotApiNatProxyConfigured($client, $listenIp, $listenPort, $backendHost, $backendPort, $proxyComment, $snatComment, $inputComment)) {
                return ['ok' => true, 'captive_url' => $captiveUrl];
            }

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

            $wgBackend = self::ensureHotspotWalledGardenBatch($client, [
                'http://' . $backendHost . ($backendPort === 80 ? '' : ':' . $backendPort),
                $captiveUrl,
            ]);
            if (empty($wgBackend['ok'])) {
                $errors = array_merge($errors, $wgBackend['errors'] ?? ['walled-garden backend']);
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
     * @param array<int, string> $urls
     * @return true|string
     */
    public static function verifyHotspotFetchUrls(array $urls, $timeout = 5)
    {
        $urls = array_values(array_filter(array_map('trim', $urls)));
        if (empty($urls)) {
            return 'Aucune URL de fetch valide pour login.html';
        }

        $errors = [];
        foreach ($urls as $url) {
            $result = self::verifyHotspotFetchUrl($url, $timeout);
            if ($result === true) {
                return true;
            }
            $errors[] = (string) $result;
        }

        return implode(' — ', $errors);
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
     * Admin lancé depuis localhost (php -S) : le routeur ne peut pas fetcher login.html
     * depuis cette machine — déploiement API MikroTik uniquement.
     */
    public static function isLocalHotspotDevEnvironment($apiUrl = null)
    {
        foreach (array_filter([
            $apiUrl,
            defined('APP_URL') ? APP_URL : '',
        ]) as $url) {
            $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return true;
            }
        }

        return false;
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
    public static function tryRouterFileWrite($client, $path, $contents)
    {
        $path = trim((string) $path);
        $contents = (string) $contents;
        if ($path === '') {
            return false;
        }

        $writePath = self::resolveRouterWritePath($client, $path, true);
        if ($writePath === null) {
            return false;
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/file/set'))
                    ->setArgument('numbers', $writePath)
                    ->setArgument('contents', '')
            );
            usleep(400000);
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/set'))
                    ->setArgument('numbers', $writePath)
                    ->setArgument('contents', $contents)
            );
            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                    return false;
                }
            }
            usleep(400000);
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return self::getRouterFileSize($client, $writePath) === strlen($contents);
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
        $fetchUrls = self::filterRouterFetchUrls($fetchUrls);
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

    /**
     * Nettoie en une passe les doublons walled-garden (centaines d'entrées = sync très lente).
     */
    public static function pruneHotspotWalledGardenBatch($client)
    {
        $lines = [
            '/ip hotspot walled-garden ip remove [find dst-host="wa.me"]',
            '/ip hotspot walled-garden ip remove [find dst-host="api.whatsapp.com"]',
            '/ip hotspot walled-garden ip remove [find dst-host="web.whatsapp.com"]',
            '/ip hotspot walled-garden ip remove [find dst-host~"whatsapp"]',
            '/ip hotspot walled-garden remove [find dst-host="wa.me"]',
            '/ip hotspot walled-garden remove [find dst-host~"whatsapp"]',
            '/ip hotspot walled-garden ip remove [find comment="DYRSIA hotspot captive extras"]',
        ];

        return self::runRouterOneShotScript($client, 'dyrsia_prune_wg', implode("\n", $lines));
    }

    /**
     * Retire des hôtes du walled-garden hotspot (ex. WhatsApp après désactivation).
     * Utilise un filtre API (une requête par liste) au lieu d'une suppression par entrée.
     */
    public static function removeHotspotWalledGardenHosts($client, array $hosts)
    {
        $hosts = array_values(array_filter(array_unique(array_map('strtolower', $hosts))));
        if ($hosts === []) {
            return ['ok' => true, 'removed' => 0];
        }

        $lines = [];
        foreach ($hosts as $host) {
            if ($host === '') {
                continue;
            }
            $escaped = str_replace('"', '', $host);
            $lines[] = '/ip hotspot walled-garden ip remove [find dst-host="' . $escaped . '"]';
            $lines[] = '/ip hotspot walled-garden remove [find dst-host="' . $escaped . '"]';
        }
        if ($lines !== []) {
            self::runRouterOneShotScript($client, 'dyrsia_rm_wg_hosts', implode("\n", $lines));
        }

        return ['ok' => true, 'removed' => count($hosts)];
    }

    /**
     * Compte les entrées walled-garden pour un hôte (dst-host).
     */
    private static function countHotspotWalledGardenHost($client, $wgPath, $host)
    {
        try {
            $rows = $client->sendSync(
                (new RouterOS\Request($wgPath . '/print'))
                    ->setArgument('.proplist', '.id,dst-host')
                    ->setQuery(RouterOS\Query::where('dst-host', $host))
            );
            $count = 0;
            foreach ($rows as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $count++;
            }

            return $count;
        } catch (Throwable $e) {
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function ensureHotspotCaptiveExtrasWalledGarden($client, $appUrl)
    {
        self::pruneHotspotWalledGardenBatch($client);
        self::removeHotspotWalledGardenHosts($client, ['wa.me', 'api.whatsapp.com', 'web.whatsapp.com']);

        $hosts = array_filter(array_unique([
            parse_url(self::normalizeHotspotBackendApiUrl($appUrl), PHP_URL_HOST),
            'cdn.jsdelivr.net',
        ]));
        $existingHosts = [];
        foreach (self::fetchHotspotWalledGardenIpRows($client) as $row) {
            $host = strtolower(trim((string) ($row['dst-host'] ?? '')));
            if ($host !== '') {
                $existingHosts[$host] = true;
            }
        }

        $wgPath = self::hotspotWalledGardenIpPrintPath($client);
        foreach ($hosts as $host) {
            $host = strtolower(trim((string) $host));
            if ($host === '' || isset($existingHosts[$host])) {
                continue;
            }
            try {
                $client->sendSync(
                    (new RouterOS\Request($wgPath . '/add'))
                        ->setArgument('dst-host', $host)
                        ->setArgument('action', 'accept')
                        ->setArgument('comment', 'DYRSIA hotspot captive extras')
                );
                $existingHosts[$host] = true;
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
        ]))));

        $lines = ['# DYRSIA Hotspot captive portal walled-garden'];
        foreach ($hosts as $host) {
            $lines[] = '/ip hotspot walled-garden ip add action=accept dst-host="' . str_replace('"', '', $host) . '" comment="DYRSIA hotspot captive"';
        }

        return implode("\n", $lines);
    }

    /**
     * Lit interfaces, pools, profils et serveurs hotspot pour l'assistant Hotspot Setup.
     */
    public static function fetchHotspotSetupSnapshot($client, $preferredHotspotName = '')
    {
        $preferredHotspotName = trim((string) $preferredHotspotName);
        $snapshot = [
            'ok' => true,
            'interfaces' => [],
            'pools' => [],
            'hotspots' => [],
            'profiles' => [],
            'networks' => [],
            'suggested' => [
                'hotspot_name' => '',
                'hotspot_interface' => '',
                'hotspot_profile' => 'default',
                'hotspot_local_address' => '10.0.0.1/24',
                'hotspot_masquerade' => '1',
                'hotspot_address_pool' => '10.0.0.1-10.0.0.254',
                'hotspot_pool_name' => '',
                'hotspot_pool_range' => '10.0.0.1-10.0.0.254',
                'hotspot_smtp_server' => '0.0.0.0',
                'hotspot_dns_server' => '8.8.8.8',
                'hotspot_dns_name' => '',
            ],
            'errors' => [],
        ];

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/print'))
                    ->setArgument('.proplist', 'name,type,disabled,running,comment')
            );
            foreach ($responses as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($name === '' || $name === 'lo') {
                    continue;
                }
                $type = trim((string) $row->getProperty('type'));
                $snapshot['interfaces'][] = [
                    'name' => $name,
                    'type' => $type,
                    'disabled' => (string) $row->getProperty('disabled') === 'true',
                    'label' => $name . ' (' . $type . ')',
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'interfaces: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'interfaces: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/pool/print'))
                    ->setArgument('.proplist', 'name,ranges,next-pool,comment')
            );
            foreach ($responses as $row) {
                $name = trim((string) $row->getProperty('name'));
                if ($name === '') {
                    continue;
                }
                $snapshot['pools'][] = [
                    'name' => $name,
                    'ranges' => trim((string) $row->getProperty('ranges')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'pools: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'pools: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/print'))
                    ->setArgument('.proplist', 'name,dns-name,smtp-server,dns-server,hotspot-address')
            );
            foreach ($responses as $row) {
                $name = trim((string) $row->getProperty('name'));
                if ($name === '') {
                    continue;
                }
                $snapshot['profiles'][] = [
                    'name' => $name,
                    'dns_name' => trim((string) $row->getProperty('dns-name')),
                    'smtp_server' => trim((string) $row->getProperty('smtp-server')),
                    'dns_server' => trim((string) $row->getProperty('dns-server')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'profiles: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'profiles: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', 'name,interface,profile,address-pool,dns-name')
            );
            foreach ($responses as $row) {
                $snapshot['hotspots'][] = [
                    'name' => trim((string) $row->getProperty('name')),
                    'interface' => trim((string) $row->getProperty('interface')),
                    'profile' => trim((string) $row->getProperty('profile')),
                    'address_pool' => trim((string) $row->getProperty('address-pool')),
                    'dns_name' => trim((string) $row->getProperty('dns-name')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'hotspots: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'hotspots: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/network/print'))
                    ->setArgument('.proplist', 'address,profile,comment')
            );
            foreach ($responses as $row) {
                $address = trim((string) $row->getProperty('address'));
                if ($address === '') {
                    continue;
                }
                $snapshot['networks'][] = [
                    'address' => $address,
                    'profile' => trim((string) $row->getProperty('profile')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'networks: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'networks: ' . $e->getMessage();
        }

        $profileNames = array_column($snapshot['profiles'], 'name');
        if (!in_array('default', $profileNames, true)) {
            array_unshift($snapshot['profiles'], [
                'name' => 'default',
                'dns_name' => '',
                'smtp_server' => '0.0.0.0',
                'dns_server' => '8.8.8.8',
            ]);
        }

        usort($snapshot['interfaces'], static function ($a, $b) {
            $order = ['ether' => 1, 'wlan' => 2, 'bridge' => 3, 'vlan' => 4, 'bond' => 5, 'vrrp' => 6];
            $oa = $order[$a['type']] ?? 99;
            $ob = $order[$b['type']] ?? 99;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        $snapshot['suggested']['hotspot_masquerade'] = self::hotspotMasqueradeEnabled($client) ? '1' : '0';

        foreach ($snapshot['hotspots'] as $hs) {
            $poolName = trim((string) ($hs['address_pool'] ?? ''));
            if ($poolName === '') {
                continue;
            }
            $poolKnown = false;
            foreach ($snapshot['pools'] as $pool) {
                if (($pool['name'] ?? '') === $poolName) {
                    $poolKnown = true;
                    break;
                }
            }
            if (!$poolKnown) {
                $snapshot['pools'][] = [
                    'name' => $poolName,
                    'ranges' => '',
                ];
            }
        }

        $activeHotspot = null;
        if (!empty($snapshot['hotspots'])) {
            if ($preferredHotspotName !== '') {
                foreach ($snapshot['hotspots'] as $hs) {
                    if (strcasecmp((string) ($hs['name'] ?? ''), $preferredHotspotName) === 0) {
                        $activeHotspot = $hs;
                        break;
                    }
                }
            }
            if ($activeHotspot === null) {
                $activeHotspot = $snapshot['hotspots'][0];
            }
        }

        if ($activeHotspot !== null) {
            $hs = $activeHotspot;
            $snapshot['suggested']['hotspot_name'] = $hs['name'];
            $snapshot['suggested']['hotspot_interface'] = $hs['interface'];
            $snapshot['suggested']['hotspot_profile'] = $hs['profile'] !== '' ? $hs['profile'] : 'default';
            $snapshot['suggested']['hotspot_pool_name'] = $hs['address_pool'];
            if ($hs['dns_name'] !== '') {
                $snapshot['suggested']['hotspot_dns_name'] = $hs['dns_name'];
            }
            foreach ($snapshot['pools'] as $pool) {
                if ($pool['name'] === $hs['address_pool'] && $pool['ranges'] !== '') {
                    $snapshot['suggested']['hotspot_address_pool'] = $pool['ranges'];
                    $snapshot['suggested']['hotspot_pool_range'] = $pool['ranges'];
                    break;
                }
            }
        } elseif (!empty($snapshot['pools'])) {
            $snapshot['suggested']['hotspot_pool_name'] = $snapshot['pools'][0]['name'];
            if ($snapshot['pools'][0]['ranges'] !== '') {
                $snapshot['suggested']['hotspot_address_pool'] = $snapshot['pools'][0]['ranges'];
                $snapshot['suggested']['hotspot_pool_range'] = $snapshot['pools'][0]['ranges'];
            }
        }

        if (!empty($snapshot['networks'])) {
            $snapshot['suggested']['hotspot_local_address'] = $snapshot['networks'][0]['address'];
        }

        $profileName = $snapshot['suggested']['hotspot_profile'] ?: 'default';
        foreach ($snapshot['profiles'] as $prof) {
            if ($prof['name'] !== $profileName) {
                continue;
            }
            if ($prof['smtp_server'] !== '') {
                $snapshot['suggested']['hotspot_smtp_server'] = $prof['smtp_server'];
            }
            if ($prof['dns_server'] !== '') {
                $snapshot['suggested']['hotspot_dns_server'] = $prof['dns_server'];
            }
            if ($prof['dns_name'] !== '' && $snapshot['suggested']['hotspot_dns_name'] === '') {
                $snapshot['suggested']['hotspot_dns_name'] = $prof['dns_name'];
            }
            break;
        }

        if (!empty($snapshot['errors'])) {
            $snapshot['ok'] = count($snapshot['interfaces']) > 0 || count($snapshot['pools']) > 0;
        }

        return $snapshot;
    }

    /**
     * Crée ou met à jour le serveur hotspot MikroTik selon l'assistant Hotspot Setup.
     *
     * @return array{ok: bool, errors?: array<int, string>, actions?: array<int, string>, hotspot_name?: string}
     */
    public static function applyHotspotSetupFromConfig($client, array $config)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => false, 'errors' => ['Indisponible en mode démo.']];
        }
        if (class_exists('DemoShowcase')) {
            global $admin;
            if (DemoShowcase::blocksRouterSync($admin ?? null)) {
                return ['ok' => false, 'errors' => ['Compte vitrine démo : configuration hotspot MikroTik désactivée.']];
            }
        }

        $interface = trim((string) ($config['hotspot_interface'] ?? ''));
        $poolName = trim((string) ($config['hotspot_pool_name'] ?? ''));
        $poolRange = trim((string) ($config['hotspot_address_pool'] ?? $config['hotspot_pool_range'] ?? ''));
        $profileName = trim((string) ($config['hotspot_profile'] ?? 'default'));
        $profileName = $profileName !== '' ? $profileName : 'default';
        $hotspotName = trim((string) ($config['hotspot_name'] ?? ''));
        $localAddress = trim((string) ($config['hotspot_local_address'] ?? ''));
        $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
        $smtpServer = trim((string) ($config['hotspot_smtp_server'] ?? '0.0.0.0'));
        $dnsServer = trim((string) ($config['hotspot_dns_server'] ?? '8.8.8.8'));
        $masquerade = !empty($config['hotspot_masquerade']) && (string) $config['hotspot_masquerade'] !== '0';
        $loginMethods = trim((string) ($config['hotspot_login_methods'] ?? 'chap'));

        if ($interface === '') {
            return ['ok' => false, 'errors' => ['Interface hotspot manquante (étape 2 de l\'assistant).']];
        }
        if ($poolName === '' || $poolRange === '') {
            return ['ok' => false, 'errors' => ['Pool IP manquant : renseignez le nom du pool et la plage (étape 2).']];
        }

        if ($hotspotName === '') {
            $hotspotName = 'dyrsia-' . preg_replace('/[^a-z0-9_-]/i', '', $interface);
            if ($hotspotName === 'dyrsia-') {
                $hotspotName = 'dyrsia-hotspot';
            }
        }

        $errors = [];
        $actions = [];

        try {
            self::setPool($client, $poolName, $poolRange);
            $actions[] = 'pool « ' . $poolName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        }

        if ($localAddress !== '') {
            try {
                self::ensureHotspotInterfaceAddress($client, $interface, $localAddress);
                $actions[] = 'adresse ' . $localAddress . ' sur ' . $interface;
            } catch (Throwable $e) {
                $errors[] = 'adresse IP: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'adresse IP: ' . $e->getMessage();
            }
        }

        try {
            self::ensureHotspotProfileConfigured($client, $profileName, $dnsName, $smtpServer, $dnsServer, $loginMethods);
            $actions[] = 'profil « ' . $profileName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'profil: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'profil: ' . $e->getMessage();
        }

        if ($localAddress !== '') {
            try {
                self::ensureHotspotNetworkEntry($client, $localAddress, $profileName, $dnsServer);
                $actions[] = 'réseau hotspot';
            } catch (Throwable $e) {
                $errors[] = 'réseau hotspot: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'réseau hotspot: ' . $e->getMessage();
            }
        }

        try {
            self::ensureHotspotServerEntry($client, $hotspotName, $interface, $profileName, $poolName);
            $actions[] = 'serveur « ' . $hotspotName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'serveur hotspot: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'serveur hotspot: ' . $e->getMessage();
        }

        if ($masquerade) {
            try {
                self::ensureHotspotSrcNatMasquerade($client, $interface);
                $actions[] = 'masquerade NAT';
            } catch (Throwable $e) {
                $errors[] = 'masquerade: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'masquerade: ' . $e->getMessage();
            }
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
            'hotspot_name' => $hotspotName,
        ];
    }

    private static function parseHotspotLocalNetwork($localAddress)
    {
        $localAddress = trim((string) $localAddress);
        if (!preg_match('#^(\d+\.\d+\.\d+\.\d+)/(\d+)$#', $localAddress, $match)) {
            return null;
        }
        $ip = $match[1];
        $prefix = (int) $match[2];
        if ($prefix < 0 || $prefix > 32) {
            return null;
        }
        $long = ip2long($ip);
        if ($long === false) {
            return null;
        }
        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix)) & 0xFFFFFFFF;
        $network = long2ip($long & $mask);

        return [
            'address' => $network . '/' . $prefix,
            'gateway' => $ip,
        ];
    }

    private static function routerEntityId($client, $path, $field, $value)
    {
        $responses = $client->sendSync(
            (new RouterOS\Request($path . '/print'))
                ->setArgument('.proplist', '.id')
                ->setQuery(RouterOS\Query::where($field, $value))
        );
        foreach ($responses as $row) {
            $id = $row->getProperty('.id');
            if ($id !== null && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    private static function ensureHotspotInterfaceAddress($client, $interface, $localAddress)
    {
        $interface = trim((string) $interface);
        $localAddress = trim((string) $localAddress);
        if ($interface === '' || $localAddress === '') {
            return;
        }

        $responses = $client->sendSync(
            (new RouterOS\Request('/ip/address/print'))
                ->setArgument('.proplist', '.id,address,interface')
                ->setQuery(RouterOS\Query::where('interface', $interface))
        );
        foreach ($responses as $row) {
            $address = trim((string) $row->getProperty('address'));
            if ($address === $localAddress || strpos($address, explode('/', $localAddress)[0] . '/') === 0) {
                return;
            }
        }

        $client->sendSync(
            (new RouterOS\Request('/ip/address/add'))
                ->setArgument('address', $localAddress)
                ->setArgument('interface', $interface)
                ->setArgument('comment', 'DYRSIA hotspot')
        );
    }

    private static function normalizeHotspotLoginBy($loginMethods)
    {
        $allowed = ['chap', 'http-chap', 'cookie', 'https', 'http-pap', 'mac', 'trial'];
        $methods = array_values(array_unique(array_filter(array_map('trim', explode(',', strtolower((string) $loginMethods))))));
        $methods = array_values(array_intersect($methods, $allowed));
        if (empty($methods)) {
            $methods = ['chap', 'http-chap', 'cookie'];
        }

        return implode(',', $methods);
    }

    private static function ensureHotspotProfileConfigured($client, $profileName, $dnsName, $smtpServer, $dnsServer, $loginMethods)
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            $profileName = 'default';
        }

        $profileId = self::routerEntityId($client, '/ip/hotspot/profile', 'name', $profileName);
        if ($profileId === null) {
            $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/add'))
                    ->setArgument('name', $profileName)
            );
            $profileId = self::routerEntityId($client, '/ip/hotspot/profile', 'name', $profileName);
        }

        $setRequest = (new RouterOS\Request('/ip/hotspot/profile/set'))
            ->setArgument('numbers', $profileId)
            ->setArgument('html-directory', 'hotspot')
            ->setArgument('login-by', self::normalizeHotspotLoginBy($loginMethods))
            ->setArgument('smtp-server', $smtpServer !== '' ? $smtpServer : '0.0.0.0')
            ->setArgument('dns-server', $dnsServer !== '' ? $dnsServer : '8.8.8.8')
            ->setArgument('use-radius', 'no');
        if ($dnsName !== '') {
            $setRequest->setArgument('dns-name', $dnsName);
        }
        $client->sendSync($setRequest);
    }

    private static function ensureHotspotNetworkEntry($client, $localAddress, $profileName, $dnsServer)
    {
        $network = self::parseHotspotLocalNetwork($localAddress);
        if ($network === null) {
            throw new Exception('Adresse locale invalide : ' . $localAddress);
        }

        $networkId = self::routerEntityId($client, '/ip/hotspot/network', 'address', $network['address']);
        $setRequest = (new RouterOS\Request($networkId ? '/ip/hotspot/network/set' : '/ip/hotspot/network/add'))
            ->setArgument('address', $network['address'])
            ->setArgument('gateway', $network['gateway'])
            ->setArgument('profile', $profileName)
            ->setArgument('comment', 'DYRSIA hotspot');
        if ($dnsServer !== '') {
            $setRequest->setArgument('dns-server', $dnsServer);
        }
        if ($networkId) {
            $setRequest->setArgument('numbers', $networkId);
        }
        $client->sendSync($setRequest);
    }

    private static function ensureHotspotServerEntry($client, $hotspotName, $interface, $profileName, $poolName)
    {
        $serverId = self::routerEntityId($client, '/ip/hotspot', 'name', $hotspotName);
        if ($serverId === null) {
            $serverId = self::routerEntityId($client, '/ip/hotspot', 'interface', $interface);
        }

        $args = [
            'name' => $hotspotName,
            'interface' => $interface,
            'profile' => $profileName,
            'address-pool' => $poolName,
            'disabled' => 'no',
        ];

        if ($serverId !== null) {
            $setRequest = (new RouterOS\Request('/ip/hotspot/set'))
                ->setArgument('numbers', $serverId);
            foreach ($args as $key => $value) {
                $setRequest->setArgument($key, $value);
            }
            $client->sendSync($setRequest);

            return;
        }

        $addRequest = new RouterOS\Request('/ip/hotspot/add');
        foreach ($args as $key => $value) {
            $addRequest->setArgument($key, $value);
        }
        $client->sendSync($addRequest);
    }

    private static function ensureHotspotSrcNatMasquerade($client, $interface)
    {
        $interface = trim((string) $interface);
        $comment = 'DYRSIA hotspot masquerade';
        $responses = $client->sendSync(
            (new RouterOS\Request('/ip/firewall/nat/print'))
                ->setArgument('.proplist', '.id,comment,action,out-interface')
        );
        foreach ($responses as $row) {
            if ((string) $row->getProperty('comment') === $comment
                && (string) $row->getProperty('action') === 'masquerade') {
                return;
            }
        }

        $addRequest = (new RouterOS\Request('/ip/firewall/nat/add'))
            ->setArgument('chain', 'srcnat')
            ->setArgument('action', 'masquerade')
            ->setArgument('comment', $comment);
        if ($interface !== '') {
            $addRequest->setArgument('out-interface', $interface);
        }
        $client->sendSync($addRequest);
    }

    private static function hotspotMasqueradeEnabled($client)
    {
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'chain,action,disabled')
            );
            foreach ($responses as $row) {
                if ((string) $row->getProperty('chain') === 'srcnat'
                    && (string) $row->getProperty('action') === 'masquerade'
                    && (string) $row->getProperty('disabled') !== 'true') {
                    return true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * Valeurs par défaut assistant PPPoE Setup (scripts/mikrotik-pppoe-setup.rsc).
     *
     * @return array<string, string>
     */
    public static function pppoeSetupDefaults()
    {
        return [
            'pppoe_setup_router' => '',
            'pppoe_setup_bridge_name' => 'bridge-pppoe',
            'pppoe_setup_bridge_ports' => 'ether2,ether3,ether4,ether5',
            'pppoe_setup_gateway' => '10.10.10.1/24',
            'pppoe_setup_pool_name' => 'pppoe-pool',
            'pppoe_setup_pool_range' => '10.10.10.2-10.10.10.254',
            'pppoe_setup_profile_default' => 'default',
            'pppoe_setup_profile_expire' => 'EXPIRE',
            'pppoe_setup_expire_rate_limit' => '',
            'pppoe_setup_dns_servers' => '8.8.8.8,1.1.1.1',
            'pppoe_setup_dns_allow_remote' => '1',
            'pppoe_setup_service_name' => 'internet',
            'pppoe_setup_server_interface' => 'bridge-pppoe',
            'pppoe_setup_one_session' => '1',
            'pppoe_setup_max_mru' => '1480',
            'pppoe_setup_max_mtu' => '1480',
            'pppoe_setup_expired_list' => 'pppoe-expired',
            'pppoe_setup_nat_interface' => 'ether1',
            'pppoe_setup_nat_masquerade' => '1',
        ];
    }

    /**
     * @return array{on-up: string, on-down: string}
     */
    private static function pppoeExpiredProfileScripts($listName)
    {
        $listName = trim((string) $listName);
        if ($listName === '') {
            $listName = 'pppoe-expired';
        }
        $listEsc = str_replace('"', '\\"', $listName);

        return [
            'on-up' => ':if ($remote-address!="") do={ /ip firewall address-list add list="' . $listEsc . '" address=$remote-address comment=$user }',
            'on-down' => ':if ($remote-address!="") do={ /ip firewall address-list remove [find list="' . $listEsc . '" address=$remote-address] }',
        ];
    }

    /**
     * Lit bridge, pools, profils PPP et serveur PPPoE pour l'assistant PPPoE Setup.
     */
    public static function fetchPppoeSetupSnapshot($client)
    {
        $snapshot = [
            'ok' => true,
            'interfaces' => [],
            'bridge_ports' => [],
            'pools' => [],
            'profiles' => [],
            'servers' => [],
            'addresses' => [],
            'suggested' => self::pppoeSetupDefaults(),
            'errors' => [],
        ];

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/print'))
                    ->setArgument('.proplist', 'name,type,disabled,running')
            );
            foreach ($responses as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                $type = trim((string) $row->getProperty('type'));
                if ($name === '' || $name === 'lo') {
                    continue;
                }
                if (!in_array($type, ['ether', 'bridge', 'vlan', 'bond', 'sfp'], true)) {
                    continue;
                }
                $snapshot['interfaces'][] = [
                    'name' => $name,
                    'type' => $type,
                    'label' => $name . ' (' . $type . ')',
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'interfaces: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'interfaces: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', 'bridge,interface')
            );
            foreach ($responses as $row) {
                $bridge = trim((string) $row->getProperty('bridge'));
                $iface = trim((string) $row->getProperty('interface'));
                if ($bridge === '' || $iface === '') {
                    continue;
                }
                if (!isset($snapshot['bridge_ports'][$bridge])) {
                    $snapshot['bridge_ports'][$bridge] = [];
                }
                $snapshot['bridge_ports'][$bridge][] = $iface;
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'bridge_ports: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'bridge_ports: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/pool/print'))
                    ->setArgument('.proplist', 'name,ranges')
            );
            foreach ($responses as $row) {
                $name = trim((string) $row->getProperty('name'));
                if ($name === '') {
                    continue;
                }
                $snapshot['pools'][] = [
                    'name' => $name,
                    'ranges' => trim((string) $row->getProperty('ranges')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'pools: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'pools: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ppp/profile/print'))
                    ->setArgument('.proplist', 'name,local-address,remote-address,rate-limit,dns-server')
            );
            foreach ($responses as $row) {
                $name = trim((string) $row->getProperty('name'));
                if ($name === '') {
                    continue;
                }
                $snapshot['profiles'][] = [
                    'name' => $name,
                    'local_address' => trim((string) $row->getProperty('local-address')),
                    'remote_address' => trim((string) $row->getProperty('remote-address')),
                    'rate_limit' => trim((string) $row->getProperty('rate-limit')),
                    'dns_server' => trim((string) $row->getProperty('dns-server')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'profiles: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'profiles: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/pppoe-server/server/print'))
                    ->setArgument('.proplist', 'service-name,interface,default-profile,disabled,one-session-per-host,max-mru,max-mtu')
            );
            foreach ($responses as $row) {
                $snapshot['servers'][] = [
                    'service_name' => trim((string) $row->getProperty('service-name')),
                    'interface' => trim((string) $row->getProperty('interface')),
                    'default_profile' => trim((string) $row->getProperty('default-profile')),
                    'disabled' => (string) $row->getProperty('disabled') === 'true',
                    'one_session_per_host' => trim((string) $row->getProperty('one-session-per-host')),
                    'max_mru' => trim((string) $row->getProperty('max-mru')),
                    'max_mtu' => trim((string) $row->getProperty('max-mtu')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'pppoe-server: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'pppoe-server: ' . $e->getMessage();
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/address/print'))
                    ->setArgument('.proplist', 'address,interface')
            );
            foreach ($responses as $row) {
                $address = trim((string) $row->getProperty('address'));
                $iface = trim((string) $row->getProperty('interface'));
                if ($address === '' || $iface === '') {
                    continue;
                }
                $snapshot['addresses'][] = [
                    'address' => $address,
                    'interface' => $iface,
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'addresses: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'addresses: ' . $e->getMessage();
        }

        try {
            $dns = $client->sendSync(
                (new RouterOS\Request('/ip/dns/print'))
                    ->setArgument('.proplist', 'servers,allow-remote-requests')
            );
            foreach ($dns as $row) {
                $servers = trim((string) $row->getProperty('servers'));
                if ($servers !== '') {
                    $snapshot['suggested']['pppoe_setup_dns_servers'] = $servers;
                }
                $snapshot['suggested']['pppoe_setup_dns_allow_remote'] =
                    (string) $row->getProperty('allow-remote-requests') === 'true' ? '1' : '0';
                break;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        $bridgeName = 'bridge-pppoe';
        foreach (array_keys($snapshot['bridge_ports']) as $name) {
            if (stripos($name, 'pppoe') !== false) {
                $bridgeName = $name;
                break;
            }
        }
        if (!empty($snapshot['bridge_ports'][$bridgeName])) {
            $snapshot['suggested']['pppoe_setup_bridge_name'] = $bridgeName;
            $snapshot['suggested']['pppoe_setup_bridge_ports'] = implode(',', $snapshot['bridge_ports'][$bridgeName]);
            $snapshot['suggested']['pppoe_setup_server_interface'] = $bridgeName;
        }

        foreach ($snapshot['addresses'] as $addr) {
            if (($addr['interface'] ?? '') === $bridgeName && !empty($addr['address'])) {
                $snapshot['suggested']['pppoe_setup_gateway'] = $addr['address'];
                break;
            }
        }

        foreach ($snapshot['pools'] as $pool) {
            $name = (string) ($pool['name'] ?? '');
            if (stripos($name, 'pppoe') !== false || $name === 'pppoe-pool') {
                $snapshot['suggested']['pppoe_setup_pool_name'] = $name;
                if (!empty($pool['ranges'])) {
                    $snapshot['suggested']['pppoe_setup_pool_range'] = $pool['ranges'];
                }
                break;
            }
        }

        if (!empty($snapshot['servers'])) {
            $srv = $snapshot['servers'][0];
            if (!empty($srv['service_name'])) {
                $snapshot['suggested']['pppoe_setup_service_name'] = $srv['service_name'];
            }
            if (!empty($srv['interface'])) {
                $snapshot['suggested']['pppoe_setup_server_interface'] = $srv['interface'];
            }
            if (!empty($srv['default_profile'])) {
                $snapshot['suggested']['pppoe_setup_profile_default'] = $srv['default_profile'];
            }
            if (!empty($srv['max_mru'])) {
                $snapshot['suggested']['pppoe_setup_max_mru'] = $srv['max_mru'];
            }
            if (!empty($srv['max_mtu'])) {
                $snapshot['suggested']['pppoe_setup_max_mtu'] = $srv['max_mtu'];
            }
            $snapshot['suggested']['pppoe_setup_one_session'] =
                ($srv['one_session_per_host'] ?? '') === 'yes' ? '1' : '0';
        }

        foreach ($snapshot['profiles'] as $profile) {
            $name = (string) ($profile['name'] ?? '');
            if (strcasecmp($name, 'EXPIRE') === 0 && !empty($profile['rate_limit'])) {
                $snapshot['suggested']['pppoe_setup_expire_rate_limit'] = $profile['rate_limit'];
            }
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'chain,action,out-interface,comment')
            );
            foreach ($responses as $row) {
                if ((string) $row->getProperty('chain') === 'srcnat'
                    && (string) $row->getProperty('action') === 'masquerade') {
                    $out = trim((string) $row->getProperty('out-interface'));
                    if ($out !== '') {
                        $snapshot['suggested']['pppoe_setup_nat_interface'] = $out;
                        $snapshot['suggested']['pppoe_setup_nat_masquerade'] = '1';
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        usort($snapshot['interfaces'], static function ($a, $b) {
            $order = ['ether' => 1, 'sfp' => 2, 'bridge' => 3, 'vlan' => 4];
            $oa = $order[$a['type']] ?? 99;
            $ob = $order[$b['type']] ?? 99;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        return $snapshot;
    }

    /**
     * Applique la configuration PPPoE Setup sur le MikroTik (équivalent mikrotik-pppoe-setup.rsc).
     *
     * @return array{ok: bool, errors?: array<int, string>, actions?: array<int, string>}
     */
    public static function applyPppoeSetupFromConfig($client, array $config)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => false, 'errors' => ['Indisponible en mode démo.']];
        }

        $defaults = self::pppoeSetupDefaults();
        $get = static function ($key) use ($config, $defaults) {
            $value = trim((string) ($config[$key] ?? $defaults[$key] ?? ''));

            return $value;
        };

        $bridgeName = $get('pppoe_setup_bridge_name');
        $bridgePorts = array_values(array_unique(array_filter(array_map('trim', explode(',', $get('pppoe_setup_bridge_ports'))))));
        $gateway = $get('pppoe_setup_gateway');
        $poolName = $get('pppoe_setup_pool_name');
        $poolRange = $get('pppoe_setup_pool_range');
        $profileDefault = $get('pppoe_setup_profile_default') ?: 'default';
        $profileExpire = $get('pppoe_setup_profile_expire') ?: 'EXPIRE';
        $routerName = $get('pppoe_setup_router');
        global $admin;
        $expireRate = self::resolvePppoeExpireRateLimit($routerName, $admin ?? null);
        $dnsServers = $get('pppoe_setup_dns_servers');
        $dnsAllowRemote = !empty($config['pppoe_setup_dns_allow_remote']) && (string) $config['pppoe_setup_dns_allow_remote'] !== '0';
        $serviceName = $get('pppoe_setup_service_name') ?: 'internet';
        $serverInterface = $get('pppoe_setup_server_interface') ?: $bridgeName;
        $oneSession = !empty($config['pppoe_setup_one_session']) && (string) $config['pppoe_setup_one_session'] !== '0';
        $maxMru = $get('pppoe_setup_max_mru') ?: '1480';
        $maxMtu = $get('pppoe_setup_max_mtu') ?: '1480';
        $expiredList = $get('pppoe_setup_expired_list') ?: 'pppoe-expired';
        $natInterface = $get('pppoe_setup_nat_interface');
        $natMasquerade = !empty($config['pppoe_setup_nat_masquerade']) && (string) $config['pppoe_setup_nat_masquerade'] !== '0';

        if ($bridgeName === '') {
            return ['ok' => false, 'errors' => ['Nom du bridge PPPoE manquant.']];
        }
        if (empty($bridgePorts)) {
            return ['ok' => false, 'errors' => ['Ports bridge PPPoE manquants (ex. ether2,ether3,ether4,ether5).']];
        }
        if ($poolName === '' || $poolRange === '') {
            return ['ok' => false, 'errors' => ['Pool PPPoE manquant (nom et plage IP).']];
        }
        if ($serverInterface === '') {
            return ['ok' => false, 'errors' => ['Interface serveur PPPoE manquante.']];
        }

        $localGateway = self::resolvePoolGatewayAddress([
            'local_ip' => explode('/', $gateway)[0] ?? '',
            'range_ip' => $poolRange,
        ]);
        if ($localGateway === '') {
            $localGateway = '10.10.10.1';
        }

        $errors = [];
        $actions = [];
        $expiredScripts = self::pppoeExpiredProfileScripts($expiredList);

        try {
            if (!self::routerEntityId($client, '/interface/bridge', 'name', $bridgeName)) {
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/add'))
                        ->setArgument('name', $bridgeName)
                        ->setArgument('comment', 'DYRSIA PPPoE LAN')
                );
            }
            $actions[] = 'bridge « ' . $bridgeName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'bridge: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'bridge: ' . $e->getMessage();
        }

        foreach ($bridgePorts as $port) {
            try {
                $existing = $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/port/print'))
                        ->setArgument('.proplist', '.id,bridge,interface')
                        ->setQuery(RouterOS\Query::where('interface', $port))
                );
                $found = false;
                foreach ($existing as $row) {
                    if ((string) $row->getProperty('bridge') === $bridgeName) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $client->sendSync(
                        (new RouterOS\Request('/interface/bridge/port/add'))
                            ->setArgument('bridge', $bridgeName)
                            ->setArgument('interface', $port)
                    );
                }
            } catch (Throwable $e) {
                $errors[] = 'port ' . $port . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'port ' . $port . ': ' . $e->getMessage();
            }
        }
        $actions[] = 'ports ' . implode(', ', $bridgePorts);

        if ($gateway !== '') {
            try {
                self::ensureHotspotInterfaceAddress($client, $bridgeName, $gateway);
                $actions[] = 'passerelle ' . $gateway;
            } catch (Throwable $e) {
                $errors[] = 'passerelle: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'passerelle: ' . $e->getMessage();
            }
        }

        try {
            self::setPool($client, $poolName, $poolRange);
            $actions[] = 'pool « ' . $poolName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        }

        foreach ([
            $profileDefault => ['rate' => '', 'expire' => false],
            $profileExpire => ['rate' => $expireRate, 'expire' => true],
        ] as $profileName => $meta) {
            try {
                $profileId = self::routerEntityId($client, '/ppp/profile', 'name', $profileName);
                $args = [
                    'name' => $profileName,
                    'local-address' => $localGateway,
                    'remote-address' => $poolName,
                ];
                if (!empty($meta['rate'])) {
                    $args['rate-limit'] = $meta['rate'];
                }
                if (!empty($dnsServers)) {
                    $args['dns-server'] = $dnsServers;
                }
                if (!empty($meta['expire'])) {
                    $args['on-up'] = $expiredScripts['on-up'];
                    $args['on-down'] = $expiredScripts['on-down'];
                }

                if ($profileId === null) {
                    $add = new RouterOS\Request('/ppp/profile/add');
                    foreach ($args as $k => $v) {
                        $add->setArgument($k, $v);
                    }
                    $client->sendSync($add);
                } else {
                    $set = new RouterOS\Request('/ppp/profile/set');
                    $set->setArgument('numbers', $profileId);
                    foreach ($args as $k => $v) {
                        $set->setArgument($k, $v);
                    }
                    $client->sendSync($set);
                }
                if ($profileName === $profileExpire) {
                    self::unsetPppoeProfileRateLimit($client, $profileName);
                }
                $actions[] = 'profil « ' . $profileName . ' »';
            } catch (Throwable $e) {
                $errors[] = 'profil ' . $profileName . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'profil ' . $profileName . ': ' . $e->getMessage();
            }
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/dns/set'))
                    ->setArgument('allow-remote-requests', $dnsAllowRemote ? 'yes' : 'no')
                    ->setArgument('servers', $dnsServers)
            );
            $actions[] = 'DNS routeur';
        } catch (Throwable $e) {
            $errors[] = 'dns: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'dns: ' . $e->getMessage();
        }

        try {
            $listExists = false;
            $responses = $client->sendSync(
                (new RouterOS\Request('/ip/firewall/address-list/print'))
                    ->setArgument('.proplist', 'list')
                    ->setQuery(RouterOS\Query::where('list', $expiredList))
            );
            foreach ($responses as $row) {
                if ((string) $row->getProperty('list') === $expiredList) {
                    $listExists = true;
                    break;
                }
            }
            if (!$listExists) {
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/address-list/add'))
                        ->setArgument('list', $expiredList)
                        ->setArgument('comment', 'DYRSIA clients PPPoE expires')
                );
            }
            $actions[] = 'liste « ' . $expiredList . ' »';
        } catch (Throwable $e) {
            $errors[] = 'address-list: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'address-list: ' . $e->getMessage();
        }

        try {
            $serverId = null;
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/pppoe-server/server/print'))
                    ->setArgument('.proplist', '.id,interface,service-name')
            );
            foreach ($responses as $row) {
                $iface = (string) $row->getProperty('interface');
                $svc = (string) $row->getProperty('service-name');
                if ($iface === $serverInterface || $svc === $serviceName) {
                    $serverId = $row->getProperty('.id');
                    break;
                }
            }

            $serverArgs = [
                'service-name' => $serviceName,
                'interface' => $serverInterface,
                'default-profile' => $profileDefault,
                'disabled' => 'no',
                'one-session-per-host' => $oneSession ? 'yes' : 'no',
                'max-mru' => $maxMru,
                'max-mtu' => $maxMtu,
                'comment' => 'DYRSIA PPPoE server',
            ];

            if ($serverId === null) {
                $add = new RouterOS\Request('/interface/pppoe-server/server/add');
                foreach ($serverArgs as $k => $v) {
                    $add->setArgument($k, $v);
                }
                $client->sendSync($add);
            } else {
                $set = new RouterOS\Request('/interface/pppoe-server/server/set');
                $set->setArgument('numbers', $serverId);
                foreach ($serverArgs as $k => $v) {
                    $set->setArgument($k, $v);
                }
                $client->sendSync($set);
            }
            $actions[] = 'serveur PPPoE « ' . $serviceName . ' » sur ' . $serverInterface;
        } catch (Throwable $e) {
            $errors[] = 'serveur PPPoE: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'serveur PPPoE: ' . $e->getMessage();
        }

        if ($natMasquerade && $natInterface !== '') {
            try {
                $natId = null;
                $responses = $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/nat/print'))
                        ->setArgument('.proplist', '.id,chain,action,out-interface,comment')
                );
                foreach ($responses as $row) {
                    if ((string) $row->getProperty('chain') === 'srcnat'
                        && (string) $row->getProperty('action') === 'masquerade'
                        && (string) $row->getProperty('out-interface') === $natInterface) {
                        $natId = $row->getProperty('.id');
                        break;
                    }
                }
                if ($natId === null) {
                    $client->sendSync(
                        (new RouterOS\Request('/ip/firewall/nat/add'))
                            ->setArgument('chain', 'srcnat')
                            ->setArgument('action', 'masquerade')
                            ->setArgument('out-interface', $natInterface)
                            ->setArgument('comment', 'DYRSIA PPPoE NAT')
                    );
                }
                $actions[] = 'NAT masquerade sur ' . $natInterface;
            } catch (Throwable $e) {
                $errors[] = 'NAT: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'NAT: ' . $e->getMessage();
            }
        }

        $bridgeBlock = self::ensurePppoeBridgeForwardBlock($client, $bridgeName);
        if (!empty($bridgeBlock['added'])) {
            $actions[] = 'firewall anti-contournement sur ' . $bridgeName;
        }
        if (!empty($bridgeBlock['errors'])) {
            $errors = array_merge($errors, $bridgeBlock['errors']);
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
        ];
    }
}
