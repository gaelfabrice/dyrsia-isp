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
    private static function mikrotikConnectionAttempts($port, $fallback = true)
    {
        $port = (int) $port ?: 8728;
        if ($port == 8728) {
            $attempts = [
                ['port' => 8728, 'ssl' => false, 'label' => 'API'],
            ];
            if ($fallback) {
                $attempts[] = ['port' => 8729, 'ssl' => true, 'label' => 'API-SSL'];
            }
            return $attempts;
        }
        if ($port == 8729) {
            $attempts = [
                ['port' => 8729, 'ssl' => true, 'label' => 'API-SSL'],
            ];
            if ($fallback) {
                $attempts[] = ['port' => 8728, 'ssl' => false, 'label' => 'API'];
            }
            return $attempts;
        }
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
     * Priorité d’une erreur de connexion pour choisir le message le plus utile
     * quand plusieurs ports sont tentés (ex. repli 8728 → 8729). Une erreur
     * « TCP OK mais API muette » est bien plus actionnable qu’un simple
     * « connexion refusée » sur le port de repli inexistant.
     */
    private static function connectionErrorRank(Throwable $e, $probeOk)
    {
        if ($e instanceof RouterOS\DataFlowException
            && $e->getCode() === RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
            return 40;
        }

        $message = strtolower((string) $e->getMessage());
        if (strpos($message, 'invalid username or password') !== false) {
            return 40;
        }

        if (($e instanceof RouterOS\SocketException
                && $e->getCode() === RouterOS\SocketException::CODE_SERVICE_INCOMPATIBLE)
            || strpos($message, 'not a compatible routeros service') !== false
            || strpos($message, 'no data within the time limit') !== false) {
            // Le port répond en TCP mais pas au protocole API : diagnostic précieux.
            return 30;
        }

        // Port joignable mais autre erreur API > port injoignable (repli mort).
        return $probeOk ? 20 : 10;
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

    /**
     * CIDR du réseau clients PPPoE (ex. 10.10.10.0/24) pour NAT et firewall bridge.
     */
    public static function resolvePppoePoolNetworkCidr($gateway, $poolRange = '')
    {
        $gateway = trim((string) $gateway);
        $poolRange = trim((string) $poolRange);

        if (preg_match('#^(\d+\.\d+\.\d+\.\d+)/(\d+)$#', $gateway, $m)) {
            $ipLong = ip2long($m[1]);
            $prefix = max(0, min(32, (int) $m[2]));
            if ($ipLong !== false && $prefix > 0) {
                $mask = ~((1 << (32 - $prefix)) - 1);
                $network = long2ip($ipLong & $mask);

                return $network . '/' . $prefix;
            }
        }

        if (preg_match('/^(\d+\.\d+\.\d+)\.\d+/i', $poolRange, $m)) {
            return $m[1] . '.0/24';
        }

        if (preg_match('/^(\d+\.\d+\.\d+)\.\d+$/', $gateway, $m)) {
            return $m[1] . '.0/24';
        }

        return '';
    }

    /**
     * NAT masquerade pour les clients PPPoE (pool + interface WAN).
     */
    private static function ensurePppoeInternetNat($client, $natInterface, $poolCidr = '')
    {
        $natInterface = trim((string) $natInterface);
        if ($natInterface === '') {
            return false;
        }

        $added = false;
        $rules = [
            ['comment' => 'DYRSIA PPPoE NAT', 'src' => ''],
            ['comment' => 'DYRSIA PPPoE NAT pool', 'src' => trim((string) $poolCidr)],
        ];

        foreach ($rules as $rule) {
            $src = $rule['src'];
            if ($src === '' && $poolCidr !== '') {
                continue;
            }
            if ($src === '' && $poolCidr === '') {
                $src = '';
            }

            $exists = false;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', '.id,chain,action,out-interface,src-address,comment')
                    ->setQuery(
                        RouterOS\Query::where('chain', 'srcnat')
                            ->andWhere('action', 'masquerade')
                            ->andWhere('comment', $rule['comment'])
                    )
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('.id') !== '') {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                continue;
            }

            $add = (new RouterOS\Request('/ip/firewall/nat/add'))
                ->setArgument('chain', 'srcnat')
                ->setArgument('action', 'masquerade')
                ->setArgument('out-interface', $natInterface)
                ->setArgument('comment', $rule['comment']);
            if ($src !== '') {
                $add->setArgument('src-address', $src);
            }
            self::sendSyncChecked($client, $add);
            $added = true;
        }

        if (!$added) {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', '.id')
                    ->setQuery(
                        RouterOS\Query::where('chain', 'srcnat')
                            ->andWhere('action', 'masquerade')
                            ->andWhere('out-interface', $natInterface)
                    )
            ) as $row) {
                if ($row->getType() !== 'trap' && (string) $row->getProperty('.id') !== '') {
                    return false;
                }
            }
        }

        return $added || true;
    }

    /**
     * Clients PPPoE actifs (profil ≠ EXPIRE) : retirer de pppoe-expired pour rétablir Internet.
     *
     * @return array{ok: bool, cleared: int, errors: array<int, string>}
     */
    public static function ensureActivePppoeSessionsUnblocked($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'cleared' => 0, 'errors' => []];
        }

        $cleared = 0;
        $errors = [];
        $seenIps = [];

        try {
            $activePrint = new RouterOS\Request('/ppp/active/print');
            $activePrint->setArgument('.proplist', 'name,address,profile');
            foreach ($client->sendSync($activePrint) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $profile = strtoupper(trim((string) $row->getProperty('profile')));
                if ($profile === '' || $profile === 'EXPIRE') {
                    continue;
                }
                $ip = trim((string) $row->getProperty('address'));
                if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP) || isset($seenIps[$ip])) {
                    continue;
                }
                $seenIps[$ip] = true;
                try {
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/ip/firewall/address-list/print'))
                            ->setArgument('.proplist', '.id')
                            ->setQuery(
                                RouterOS\Query::where('list', 'pppoe-expired')
                                    ->andWhere('address', $ip)
                            )
                    ) as $listRow) {
                        $listId = $listRow->getProperty('.id');
                        if ($listId !== null && $listId !== '') {
                            $client->sendSync(
                                (new RouterOS\Request('/ip/firewall/address-list/remove'))
                                    ->setArgument('numbers', $listId)
                            );
                            $cleared++;
                        }
                    }
                } catch (Throwable $e) {
                    $errors[] = $ip . ': ' . $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'active sessions: ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'cleared' => $cleared, 'errors' => $errors];
    }

    public static function deriveGatewayFromPoolRange($range)
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

    /**
     * Message court pour l’assistant Hotspot (évite le pavé de diagnostic en UI).
     */
    public static function shortConnectionErrorMessage($host, $port, $routerName = '')
    {
        $endpoint = trim((string) $host) . ':' . (int) $port;
        $target = trim((string) $routerName) !== ''
            ? '« ' . trim((string) $routerName) . ' » (' . $endpoint . ')'
            : $endpoint;

        return 'Connexion API impossible vers ' . $target
            . '. Vérifiez le service API (port 8728), le VPN WireGuard et les identifiants.';
    }

    /**
     * Message d’erreur ciblé selon la réponse du routeur (TCP OK mais login API refusé, etc.).
     */
    public static function classifyConnectionError(Throwable $e, $host, $port, $routerName = '', $apiUser = '')
    {
        $code = (int) $e->getCode();
        $message = strtolower(trim((string) $e->getMessage()));
        $routerLabel = trim((string) $routerName) !== '' ? '« ' . trim((string) $routerName) . ' »' : 'le routeur';
        $userHint = trim((string) $apiUser) !== '' ? ' (« ' . trim((string) $apiUser) . ' »)' : '';

        if ($code === RouterOS\SocketException::CODE_SERVICE_INCOMPATIBLE
            || strpos($message, 'compatible routeros') !== false
            || strpos($message, 'not a compatible') !== false
            || strpos($message, 'no data within the time limit') !== false) {
            return 'Le port ' . (int) $port . ' de ' . $routerLabel
                . ' est joignable via VPN, mais l’API MikroTik ne répond pas.'
                . ' Sur le routeur : /ip service enable api'
                . ' ; /ip service set api port=8728 disabled=no address=10.0.0.0/24'
                . ' ; pare-feu input : autoriser TCP 8728 depuis 10.0.0.0/24'
                . ' ; utilisateur API' . $userHint . ' avec groupe full ou api.';
        }

        if (strpos($message, 'invalid') !== false
            && (strpos($message, 'password') !== false || strpos($message, 'username') !== false || strpos($message, 'credential') !== false)) {
            return 'Identifiants API incorrects pour ' . $routerLabel . $userHint
                . '. Corrigez utilisateur/mot de passe dans Réseau → Routeurs.';
        }

        return self::shortConnectionErrorMessage($host, $port, $routerName);
    }

    /**
     * WAN probable sans appel API (sync rapide assistant).
     *
     * @param array<int, string> $physicalPorts
     */
    private static function guessWanInterfaceFromPhysicalPorts(array $physicalPorts)
    {
        foreach ($physicalPorts as $name) {
            if (strcasecmp($name, 'ether1') === 0) {
                return $name;
            }
        }

        return $physicalPorts[0] ?? 'ether1';
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

    public static function getClient($ip, $user, $pass, $timeout = 5, $fallback = true, $failOnUnreachable = false, $socketTimeout = 60)
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
        $attempts = self::mikrotikConnectionAttempts($endpoint['port'], $fallback);
        $lastError = null;
        $lastErrorRank = -1;
        $keepError = static function (Throwable $e, $probeOk) use (&$lastError, &$lastErrorRank) {
            $rank = self::connectionErrorRank($e, $probeOk);
            // Ne remplace que par une erreur au moins aussi informative : évite
            // qu’un repli SSL « connexion refusée » n’écrase le vrai diagnostic.
            if ($rank > $lastErrorRank) {
                $lastError = $e;
                $lastErrorRank = $rank;
            }
        };
        $prevErrorLevel = error_reporting(error_reporting() & ~E_DEPRECATED);

        foreach ($attempts as $attempt) {
            // TCP pre-flight: fail fast when the VPN/route is down so admin UI
            // never sits on max_execution_time=600 waiting for a dead socket.
            $probeTimeout = min(4, max(2, (int) $timeout));
            $probe = self::probeTcp($endpoint['host'], $attempt['port'], $probeTimeout);
            $probeOk = ($probe === true);
            if (!$probeOk) {
                $keepError(new Exception((string) $probe), false);
                // Unreachable TCP: do not attempt RouterOS Client (can hang far
                // beyond $timeout on macOS when the peer is blackholed).
                if ($failOnUnreachable || self::probeLooksHardUnreachable($probe)) {
                    continue;
                }
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
                self::setClientSocketTimeout($client, $socketTimeout);
                error_reporting($prevErrorLevel);
                return $client;
            } catch (RouterOS\DataFlowException $e) {
                $keepError($e, $probeOk);
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
                $keepError($e, $probeOk);
                if ($probeOk
                    && self::isRetriableMikrotikConnectionError($e)
                    && (int) $timeout < 30) {
                    sleep(2);
                    try {
                        $client = new RouterOS\Client(
                            $endpoint['host'],
                            $user,
                            $pass,
                            $attempt['port'],
                            false,
                            30,
                            $attempt['ssl']
                                ? PEAR2\Net\Transmitter\NetworkStream::CRYPTO_TLS
                                : PEAR2\Net\Transmitter\NetworkStream::CRYPTO_OFF
                        );
                        self::setClientSocketTimeout($client, max((int) $socketTimeout, 15));
                        error_reporting($prevErrorLevel);
                        return $client;
                    } catch (Throwable $retryErr) {
                        $keepError($retryErr, $probeOk);
                    }
                }
                if (!self::isRetriableMikrotikConnectionError($e)) {
                    break;
                }
            } catch (Exception $e) {
                $keepError($e, $probeOk);
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

        $tcpOk = self::probeTcp($endpoint['host'], $endpoint['port'], 3) === true;
        if ($tcpOk && (
            stripos($detail, 'compatible RouterOS') !== false
            || stripos($detail, 'compatible routeros') !== false
            || stripos($detail, 'no data within the time limit') !== false
        )) {
            throw new Exception(
                'Le port ' . (int) $endpoint['port'] . ' de ' . $endpoint['host']
                . ' est joignable (VPN OK) mais l’API MikroTik ne répond pas au login.'
                . ' Depuis Winbox sur le routeur : /ip service print ;'
                . ' /ip service enable api ;'
                . ' /ip service set api port=8728 disabled=no address=10.0.0.0/24 ;'
                . ' pare-feu input : accept tcp 8728 depuis 10.0.0.0/24 ;'
                . ' utilisateur « ' . $user . ' » avec groupe full ou api.'
                . ' Puis /ip service disable api et /ip service enable api si besoin.'
            );
        }

        if (!$tcpOk && stripos((string) $detail, 'TCP injoignable') !== false) {
            throw new Exception(
                'Routeur injoignable (' . $endpoint['host'] . ':' . (int) $endpoint['port'] . '). '
                . 'Activez le tunnel WireGuard Dyrsia-VPN sur ce Mac (interface utun, IP 10.0.0.2), '
                . 'puis vérifiez : ping ' . $endpoint['host'] . ' et nc -z ' . $endpoint['host'] . ' 8728.'
            );
        }

        throw new Exception(self::formatMikrotikConnectionHelp($endpoint['host'], $endpoint['port']) . ' (' . $detail . ')');
    }

    /**
     * Limite la durée d'attente de chaque requête API (évite les sync UI bloquées).
     */
    public static function setClientSocketTimeout($client, $seconds = 8)
    {
        if (!$client) {
            return;
        }
        try {
            $client->com->getTransmitter()->setTimeout(max(3, (int) $seconds));
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * Exécute une commande API et remonte les traps / erreurs RouterOS (sendSync ne lève pas toujours).
     *
     * @return \Iterator<int, RouterOS\Response>
     */
    private static function sendSyncChecked($client, RouterOS\Request $request)
    {
        $responses = $client->sendSync($request);
        $messages = [];
        foreach ($responses as $response) {
            $type = $response->getType();
            if ($type === RouterOS\Response::TYPE_ERROR || $type === 'trap') {
                $msg = trim((string) $response->getProperty('message'));
                if ($msg === '') {
                    $msg = trim((string) $response->getProperty('category'));
                }
                if ($msg !== '') {
                    $messages[] = $msg;
                }
            }
            $status = trim((string) $response->getProperty('status'));
            if ($status !== '' && stripos($status, 'fail') !== false) {
                $msg = trim((string) $response->getProperty('message'));
                $messages[] = $msg !== '' ? $msg : ('status: ' . $status);
            }
        }
        if (!empty($messages)) {
            throw new Exception(implode(' | ', array_unique($messages)));
        }

        return $responses;
    }

    private static function isRouterOsDisabledFlag($value)
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['true', 'yes', '1'], true);
    }

    /**
     * Crée ou met à jour le serveur PPPoE et vérifie sa présence sur le routeur.
     *
     * @return array{ok: bool, action?: string, error?: string}
     */
    private static function ensurePppoeServerOnInterface(
        $client,
        $serviceName,
        $serverInterface,
        $profileDefault,
        $oneSession,
        $maxMru,
        $maxMtu
    ) {
        $serviceName = trim((string) $serviceName) ?: 'internet';
        $serverInterface = trim((string) $serverInterface);
        if ($serverInterface === '') {
            return ['ok' => false, 'error' => 'Interface serveur PPPoE manquante.'];
        }

        $serverId = null;
        $serverOnIface = null;
        $serverByName = null;

        foreach ($client->sendSync(
            (new RouterOS\Request('/interface/pppoe-server/server/print'))
                ->setArgument('.proplist', '.id,interface,service-name,disabled')
                ->setQuery(RouterOS\Query::where('interface', $serverInterface))
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $id = $row->getProperty('.id');
            if ($id !== null && $id !== '') {
                $serverOnIface = $id;
                break;
            }
        }

        if ($serverOnIface === null) {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/pppoe-server/server/print'))
                    ->setArgument('.proplist', '.id,interface,service-name,disabled')
                    ->setQuery(RouterOS\Query::where('service-name', $serviceName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    $serverByName = $id;
                    break;
                }
            }
        }

        if ($serverByName !== null && $serverOnIface !== null && $serverByName !== $serverOnIface) {
            self::sendSyncChecked(
                $client,
                (new RouterOS\Request('/interface/pppoe-server/server/remove'))
                    ->setArgument('numbers', $serverByName)
            );
            $serverByName = null;
        }

        $serverId = $serverOnIface ?? $serverByName;

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
            self::sendSyncChecked($client, $add);
        } else {
            $set = new RouterOS\Request('/interface/pppoe-server/server/set');
            $set->setArgument('numbers', $serverId);
            foreach ($serverArgs as $k => $v) {
                $set->setArgument($k, $v);
            }
            self::sendSyncChecked($client, $set);
        }

        $verified = false;
        foreach ($client->sendSync(
            (new RouterOS\Request('/interface/pppoe-server/server/print'))
                ->setArgument('.proplist', 'interface,service-name,disabled')
                ->setQuery(RouterOS\Query::where('interface', $serverInterface))
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            if (strcasecmp(trim((string) $row->getProperty('interface')), $serverInterface) === 0
                && strcasecmp(trim((string) $row->getProperty('service-name')), $serviceName) === 0
                && !self::isRouterOsDisabledFlag($row->getProperty('disabled'))) {
                $verified = true;
                break;
            }
        }

        if (!$verified) {
            return ['ok' => false, 'error' => 'Le serveur PPPoE n\'a pas été confirmé sur « ' . $serverInterface . ' » après envoi API.'];
        }

        return [
            'ok' => true,
            'action' => 'serveur PPPoE « ' . $serviceName . ' » sur ' . $serverInterface,
        ];
    }

    private static function isBrokenClientMessage($message)
    {
        $message = strtolower((string) $message);
        foreach ([
            'transmitter is invalid',
            'no data within the time limit',
            'sending aborted',
            'connection reset',
            'broken pipe',
        ] as $needle) {
            if (strpos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /** @var string */
    private static $lastDeployClientError = '';

    public static function consumeLastDeployClientError()
    {
        $message = self::$lastDeployClientError;
        self::$lastDeployClientError = '';

        return $message;
    }

    /**
     * @param array<string, mixed>|object|null $routerRow
     */
    private static function openDeployClient($routerRow, $socketTimeout = 30)
    {
        if (!$routerRow) {
            return null;
        }
        self::$lastDeployClientError = '';
        $ip = is_array($routerRow) ? ($routerRow['ip_address'] ?? '') : ($routerRow->ip_address ?? '');
        $user = is_array($routerRow) ? ($routerRow['username'] ?? '') : ($routerRow->username ?? '');
        $storedPass = is_array($routerRow) ? ($routerRow['password'] ?? '') : ($routerRow->password ?? '');
        $pass = self::routerPassword($storedPass);
        try {
            $client = self::getClient($ip, $user, $pass, max(25, (int) $socketTimeout), true, true, (int) $socketTimeout);
            if ($client) {
                self::setClientSocketTimeout($client, (int) $socketTimeout);
            }

            return $client;
        } catch (Throwable $e) {
            self::$lastDeployClientError = $e->getMessage();

            return null;
        } catch (Exception $e) {
            self::$lastDeployClientError = $e->getMessage();

            return null;
        }
    }

    /**
     * @param array<string, mixed>|object|null $routerRow
     */
    private static function runDeployPhase($routerRow, &$client, callable $phase, $socketTimeout = 30)
    {
        $attempts = 0;
        $lastError = null;
        while ($attempts < 3) {
            $attempts++;
            try {
                if (($attempts > 1 || !$client) && $routerRow) {
                    $fresh = self::openDeployClient($routerRow, $socketTimeout);
                    if (!$fresh) {
                        throw new Exception('Connexion MikroTik impossible.');
                    }
                    $client = $fresh;
                } elseif (!$client) {
                    throw new Exception('Client MikroTik absent.');
                }

                return $phase($client);
            } catch (Throwable $e) {
                $lastError = $e;
                if ($attempts < 3 && $routerRow && self::isBrokenClientMessage($e->getMessage())) {
                    $client = null;
                    usleep(400000);
                    continue;
                }
                throw $e;
            } catch (Exception $e) {
                $lastError = $e;
                if ($attempts < 3 && $routerRow && self::isBrokenClientMessage($e->getMessage())) {
                    $client = null;
                    usleep(400000);
                    continue;
                }
                throw $e;
            }
        }

        throw $lastError ?? new Exception('Échec déploiement MikroTik.');
    }

    /**
     * Déploie bridge, pool, profils, serveur PPPoE et NAT en une seule session API.
     *
     * @param array<string, mixed> $params
     * @return array{actions: array<int, string>, errors: array<int, string>}
     */
    private static function deployPppoeCoreInfrastructure($client, array $params)
    {
        $actions = [];
        $bridgeName = (string) ($params['bridgeName'] ?? '');
        $bridgePorts = is_array($params['bridgePorts'] ?? null) ? $params['bridgePorts'] : [];
        $gateway = (string) ($params['gateway'] ?? '');
        $gatewayIface = (string) ($params['gatewayIface'] ?? $bridgeName);
        $poolName = (string) ($params['poolName'] ?? '');
        $poolRange = (string) ($params['poolRange'] ?? '');
        $profileDefault = (string) ($params['profileDefault'] ?? 'default');
        $profileExpire = (string) ($params['profileExpire'] ?? 'EXPIRE');
        $localGateway = (string) ($params['localGateway'] ?? '10.10.10.1');
        $dnsServers = (string) ($params['dnsServers'] ?? '');
        $expireRate = (string) ($params['expireRate'] ?? '');
        $expiredScripts = is_array($params['expiredScripts'] ?? null) ? $params['expiredScripts'] : ['on-up' => '', 'on-down' => ''];
        $serviceName = (string) ($params['serviceName'] ?? 'internet');
        $serverInterface = (string) ($params['serverInterface'] ?? $bridgeName);
        $oneSession = !empty($params['oneSession']);
        $maxMru = (string) ($params['maxMru'] ?? '1480');
        $maxMtu = (string) ($params['maxMtu'] ?? '1480');
        $natMasquerade = !empty($params['natMasquerade']);
        $natInterface = (string) ($params['natInterface'] ?? '');
        $skipBridge = !empty($params['skipBridge']);

        if (!$skipBridge) {
            if (!self::routerEntityId($client, '/interface/bridge', 'name', $bridgeName)) {
                self::sendSyncChecked(
                    $client,
                    (new RouterOS\Request('/interface/bridge/add'))
                        ->setArgument('name', $bridgeName)
                        ->setArgument('comment', 'DYRSIA PPPoE LAN')
                );
            }
            $actions[] = 'bridge « ' . $bridgeName . ' »';

            $existingBridgePorts = self::indexBridgeMemberPorts($client, $bridgeName);
            $portsAdded = [];
            foreach ($bridgePorts as $port) {
                $portKey = strtolower(trim((string) $port));
                if ($portKey === '' || !empty($existingBridgePorts[$portKey])) {
                    continue;
                }
                $membership = self::ensureBridgePortMembership($client, $bridgeName, $port);
                if (!empty($membership['ok'])) {
                    $existingBridgePorts[$portKey] = true;
                    $portsAdded[] = $port;
                } elseif (!empty($membership['error'])) {
                    throw new Exception('port ' . $port . ': ' . $membership['error']);
                }
            }
            if (!empty($portsAdded)) {
                $actions[] = 'ports ajoutés : ' . implode(', ', $portsAdded);
            } elseif (!empty($bridgePorts)) {
                $actions[] = 'ports bridge OK (' . implode(', ', $bridgePorts) . ')';
            }
        }

        if ($gateway !== '') {
            self::ensureInterfaceAddress($client, $gatewayIface, $gateway, 'DYRSIA PPPoE');
            $actions[] = 'passerelle ' . $gateway . ' sur ' . $gatewayIface;
        }

        self::setPool($client, $poolName, $poolRange);
        $actions[] = 'pool « ' . $poolName . ' »';

        foreach ([
            $profileDefault => ['rate' => '', 'expire' => false],
            $profileExpire => ['rate' => $expireRate, 'expire' => true],
        ] as $profileName => $meta) {
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
                self::sendSyncChecked($client, $add);
            } else {
                $set = new RouterOS\Request('/ppp/profile/set');
                $set->setArgument('numbers', $profileId);
                foreach ($args as $k => $v) {
                    $set->setArgument($k, $v);
                }
                self::sendSyncChecked($client, $set);
            }
            if ($profileName === $profileExpire && empty($meta['rate'])) {
                self::unsetPppoeProfileRateLimit($client, $profileName);
            }
            $actions[] = 'profil « ' . $profileName . ' »';
        }

        $serverResult = self::ensurePppoeServerOnInterface(
            $client,
            $serviceName,
            $serverInterface,
            $profileDefault,
            $oneSession,
            $maxMru,
            $maxMtu
        );
        if (empty($serverResult['ok'])) {
            throw new Exception($serverResult['error'] ?? 'serveur PPPoE non créé');
        }
        if (!empty($serverResult['action'])) {
            $actions[] = $serverResult['action'];
        }

        if ($natMasquerade && $natInterface !== '') {
            $poolCidr = self::resolvePppoePoolNetworkCidr($gateway, $poolRange);
            if (self::ensurePppoeInternetNat($client, $natInterface, $poolCidr)) {
                $actions[] = 'NAT Internet sur ' . $natInterface
                    . ($poolCidr !== '' ? ' (' . $poolCidr . ')' : '');
            }
        }

        return ['actions' => $actions, 'errors' => []];
    }

    /**
     * Compléments optionnels (DNS, liste expirés, firewall) — n'empêchent pas le succès PPPoE.
     *
     * @return array<int, string>
     */
    private static function deployPppoeOptionalExtras($client, array $params)
    {
        $actions = [];
        $dnsAllowRemote = !empty($params['dnsAllowRemote']);
        $dnsServers = (string) ($params['dnsServers'] ?? '');
        $expiredList = (string) ($params['expiredList'] ?? 'pppoe-expired');
        $blockIface = (string) ($params['blockIface'] ?? '');
        $poolCidr = (string) ($params['poolCidr'] ?? '');
        $hotspotInterface = trim((string) ($params['hotspotInterface'] ?? 'bridge-hotspot'));

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/dns/set'))
                    ->setArgument('allow-remote-requests', $dnsAllowRemote ? 'yes' : 'no')
                    ->setArgument('servers', $dnsServers)
            );
            $actions[] = 'DNS routeur';
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        try {
            $listExists = false;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/address-list/print'))
                    ->setArgument('.proplist', 'list')
                    ->setQuery(RouterOS\Query::where('list', $expiredList))
            ) as $row) {
                if ((string) $row->getProperty('list') === $expiredList) {
                    $listExists = true;
                    break;
                }
            }
            if (!$listExists) {
                self::sendSyncChecked(
                    $client,
                    (new RouterOS\Request('/ip/firewall/address-list/add'))
                        ->setArgument('list', $expiredList)
                        ->setArgument('comment', 'DYRSIA clients PPPoE expires')
                );
            }
            $actions[] = 'liste « ' . $expiredList . ' »';
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        if ($blockIface !== '') {
            $bridgeBlock = self::ensurePppoeBridgeForwardBlock($client, $blockIface, $poolCidr);
            if (!empty($bridgeBlock['added'])) {
                $actions[] = 'firewall anti-contournement sur ' . $blockIface
                    . ($poolCidr !== '' ? ' (hors ' . $poolCidr . ')' : '');
            } elseif (!empty($bridgeBlock['updated'])) {
                $actions[] = 'firewall bridge corrigé (clients PPPoE autorisés)';
            }
        }

        if ($hotspotInterface !== ''
            && self::routerHasHotspotCoexistence($client, ['hotspot_interface' => $hotspotInterface])) {
            $hsConfig = is_array($params['hotspotConfig'] ?? null) ? $params['hotspotConfig'] : [];
            if ($hotspotInterface !== '') {
                $hsConfig['hotspot_interface'] = $hotspotInterface;
            }
            $coexist = self::ensureHotspotDhcpCoexistenceEssential($client, $hsConfig);
            if (!empty($coexist['actions'])) {
                $actions = array_merge($actions, $coexist['actions']);
            }
            if (!empty($coexist['errors'])) {
                foreach ($coexist['errors'] as $coexistError) {
                    _log('[PPPoE hotspot DHCP] ' . $coexistError);
                }
            }
        }

        return $actions;
    }

    private static function ensureInterfaceAddress($client, $interface, $localAddress, $comment = 'DYRSIA')
    {
        $interface = trim((string) $interface);
        $localAddress = trim((string) $localAddress);
        if ($interface === '' || $localAddress === '') {
            return;
        }

        $targetIp = explode('/', $localAddress, 2)[0];
        foreach ($client->sendSync(
            (new RouterOS\Request('/ip/address/print'))
                ->setArgument('.proplist', '.id,address,interface')
                ->setQuery(RouterOS\Query::where('interface', $interface))
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $address = trim((string) $row->getProperty('address'));
            if ($address === $localAddress || ($targetIp !== '' && strpos($address, $targetIp . '/') === 0)) {
                return;
            }
        }

        self::sendSyncChecked(
            $client,
            (new RouterOS\Request('/ip/address/add'))
                ->setArgument('address', $localAddress)
                ->setArgument('interface', $interface)
                ->setArgument('comment', $comment)
        );
    }

    /**
     * Probe failed with a hard network error (VPN down / no route) — skip Client().
     */
    private static function probeLooksHardUnreachable($probe)
    {
        $probe = strtolower((string) $probe);
        foreach (['network is unreachable', 'no route to host', 'host is down', 'injoignable'] as $needle) {
            if (strpos($probe, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Quick TCP reachability check for admin UX (before long hotspot deploys).
     *
     * @return true|string
     */
    public static function probeApiReachable($ipAddress, $timeout = 3)
    {
        $endpoint = self::parseEndpoint($ipAddress);
        if ($endpoint['host'] === '') {
            return 'IP routeur vide';
        }

        return self::probeTcp($endpoint['host'], $endpoint['port'], max(1, (float) $timeout));
    }

    /**
     * Erreurs API RouterOS où une nouvelle connexion peut réussir.
     */
    private static function mikrotikClientErrorIsRetriable($message)
    {
        $message = strtolower((string) $message);
        $needles = [
            'transmitter is invalid',
            'failed while receiving initial length byte',
            'connection reset',
            'connection closed',
            'broken pipe',
            'unable to read',
            'no data within the time limit',
            'failed while receiving',
        ];
        foreach ($needles as $needle) {
            if (strpos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remplace le client API par une connexion fraîche (après trunk long ou socket mort).
     *
     * @param array<string, mixed>|object|null $routerRow
     */
    /** Budget global de reconnexions API par requête (évite une boucle sans fin). */
    private static $mikrotikReconnectBudget = 6;

    /** @var array<string, mixed>|null Routeur actif pendant un déploiement hotspot (reconnexion auto après script). */
    private static $mikrotikDeployRouterRow = null;

    public static function setMikrotikDeployRouterContext($routerRow)
    {
        if (is_object($routerRow) && method_exists($routerRow, 'as_array')) {
            $routerRow = $routerRow->as_array();
        }
        self::$mikrotikDeployRouterRow = is_array($routerRow) ? $routerRow : null;
    }

    public static function clearMikrotikDeployRouterContext()
    {
        self::$mikrotikDeployRouterRow = null;
    }

    public static function resetMikrotikReconnectBudget($budget = 6)
    {
        self::$mikrotikReconnectBudget = max(0, (int) $budget);
    }

    public static function refreshMikrotikClient(&$client, $routerRow, $timeout = 20)
    {
        if (self::$mikrotikReconnectBudget <= 0) {
            // Le routeur ferme l'API de façon répétée : abandonner vite plutôt
            // que de reconnecter en boucle (~10 s par tentative = page figée).
            throw new Exception(
                'Connexion API MikroTik instable : le routeur ferme la session API de façon répétée '
                . '(trop de reconnexions). Réessayez « Send login.html » seul, ou relancez « Send complet » '
                . 'dans un instant une fois le routeur stabilisé.'
            );
        }
        self::$mikrotikReconnectBudget--;

        if (is_object($routerRow) && method_exists($routerRow, 'as_array')) {
            $routerRow = $routerRow->as_array();
        }
        if (!is_array($routerRow)) {
            return false;
        }
        $ip = trim((string) ($routerRow['ip_address'] ?? ''));
        $user = trim((string) ($routerRow['username'] ?? ''));
        $pass = (string) ($routerRow['password'] ?? '');
        if ($ip === '' || $user === '') {
            return false;
        }
        try {
            $fresh = self::getClient($ip, $user, $pass, (int) $timeout);
            if ($fresh) {
                $client = $fresh;

                return true;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @param array<string, mixed>|object|null $routerRow
     */
    private static function throwIfMikrotikResultRetriable(array $result)
    {
        foreach ($result['errors'] ?? [] as $err) {
            if (self::mikrotikClientErrorIsRetriable((string) $err)) {
                throw new Exception((string) $err);
            }
        }
    }

    private static function mikrotikRethrowIfRetriable(Throwable $e)
    {
        if (self::mikrotikClientErrorIsRetriable($e->getMessage())) {
            throw $e;
        }
    }

    private static function mikrotikPauseAfterRouterScript(&$client, $routerRow, $timeout = 30)
    {
        sleep(2);
        self::refreshMikrotikClient($client, $routerRow, $timeout);
    }

    private static function runMikrotikClientStep(&$client, $routerRow, $timeout, callable $step)
    {
        try {
            return $step($client);
        } catch (Throwable $e) {
            if (self::mikrotikClientErrorIsRetriable($e->getMessage())) {
                sleep(2);
                if (self::refreshMikrotikClient($client, $routerRow, $timeout)) {
                    try {
                        return $step($client);
                    } catch (Throwable $retryError) {
                        if (self::mikrotikClientErrorIsRetriable($retryError->getMessage())) {
                            sleep(2);
                            if (self::refreshMikrotikClient($client, $routerRow, $timeout)) {
                                return $step($client);
                            }
                        }
                        throw $retryError;
                    }
                }
            }
            throw $e;
        } catch (Exception $e) {
            if (self::mikrotikClientErrorIsRetriable($e->getMessage())) {
                sleep(2);
                if (self::refreshMikrotikClient($client, $routerRow, $timeout)) {
                    try {
                        return $step($client);
                    } catch (Exception $retryError) {
                        if (self::mikrotikClientErrorIsRetriable($retryError->getMessage())) {
                            sleep(2);
                            if (self::refreshMikrotikClient($client, $routerRow, $timeout)) {
                                return $step($client);
                            }
                        }
                        throw $retryError;
                    }
                }
            }
            throw $e;
        }
    }

    /** @param array<int, string> $errors */
    private static function mikrotikSetupHasRetriableErrors(array $errors): bool
    {
        foreach ($errors as $err) {
            if (self::mikrotikClientErrorIsRetriable((string) $err)) {
                return true;
            }
        }

        return false;
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
     * MikroTik burst suffix stored in tbl_bandwidth.burst (without base rate).
     * Five parts: burst-limit burst-threshold burst-time priority limit-at.
     * Example: 8M/8M 3M/3M 16/16 8 2M/2M
     *
     * Legacy rows may include an extra leading token copied from presets
     * (six parts total); those are normalized to five parts.
     */
    public static function normalizeBandwidthBurst($burst)
    {
        $burst = trim((string) $burst);
        if ($burst === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $burst);
        $ratePair = '/^\d+(?:\.\d+)?[kKmMgG]?\/\d+(?:\.\d+)?[kKmMgG]?$/i';
        $burstTime = '/^\d+\/\d+$/';

        $validateFive = static function (array $five) use ($ratePair, $burstTime) {
            return count($five) === 5
                && preg_match($ratePair, $five[0])
                && preg_match($ratePair, $five[1])
                && preg_match($burstTime, $five[2])
                && ctype_digit((string) $five[3])
                && preg_match($ratePair, $five[4]);
        };

        if (count($parts) === 6 && $validateFive(array_slice($parts, 1))) {
            return implode(' ', array_slice($parts, 1));
        }

        if ($validateFive($parts)) {
            return implode(' ', $parts);
        }

        return '';
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
        $burstSuffix = self::normalizeBandwidthBurst($bw['burst'] ?? '');
        if ($burstSuffix !== '') {
            $rate .= ' ' . $burstSuffix;
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
    public static function pppoeExpiredProfileOnUpScript($listName = 'pppoe-expired')
    {
        $listName = self::sanitizePppoeExpiredListName($listName);

        return ':local ip $remote-address; '
            . ':if ([:len $ip]=0) do={ :set ip $address }; '
            . ':if ([:len $ip]>0) do={ '
            . ':if ([:len [/ip firewall address-list find list=' . $listName . ' address=$ip]]=0) do={ '
            . '/ip firewall address-list add list=' . $listName . ' address=$ip comment=$user '
            . '} }';
    }

    /**
     * Script MikroTik on-down profil EXPIRE : retire l'IP de pppoe-expired.
     */
    public static function pppoeExpiredProfileOnDownScript($listName = 'pppoe-expired')
    {
        $listName = self::sanitizePppoeExpiredListName($listName);

        return ':local ip $remote-address; '
            . ':if ([:len $ip]=0) do={ :set ip $address }; '
            . ':if ([:len $ip]>0) do={ '
            . '/ip firewall address-list remove [find list=' . $listName . ' address=$ip] '
            . '}';
    }

    private static function sanitizePppoeExpiredListName($listName): string
    {
        $listName = trim((string) $listName);
        if ($listName === '') {
            $listName = 'pppoe-expired';
        }
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $listName);

        return $safe !== '' ? $safe : 'pppoe-expired';
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
                'comment' => $commentTag . ' drop marked conn',
                'chain' => 'forward',
                'action' => 'drop',
                'connection-mark' => $connMark,
                'prepend' => true,
            ],
            [
                'comment' => $commentTag . ' hard drop forward',
                'chain' => 'forward',
                'action' => 'drop',
                'src-address-list' => 'pppoe-expired',
                'prepend' => false,
            ],
        ] as $rule) {
            try {
                $printRequest = new RouterOS\Request('/ip/firewall/filter/print');
                $printRequest->setArgument('.proplist', '.id');
                $printRequest->setQuery(RouterOS\Query::where('comment', $rule['comment']));
                if ($client->sendSync($printRequest)->getProperty('.id')) {
                    continue;
                }
                $prepend = !empty($rule['prepend']);
                unset($rule['prepend']);
                $comment = $rule['comment'];
                unset($rule['comment']);
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                foreach ($rule as $key => $value) {
                    $addRequest->setArgument($key, $value);
                }
                $addRequest->setArgument('comment', $comment);
                if ($prepend && !empty($firstForwardRuleId)) {
                    $addRequest->setArgument('place-before', $firstForwardRuleId);
                }
                $client->sendSync($addRequest);
            } catch (Throwable $e) {
                $errors[] = $comment . ': ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $comment . ': ' . $e->getMessage();
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
            $recharges = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where_raw("LOWER(type) = 'pppoe'")
                ->order_by_desc('id')
                ->find_many();

            $seenCustomers = [];
            self::withPppoeSharedClient($client, static function ($driver) use ($recharges, $expirePlan, &$seenCustomers, &$enforced, &$errors) {
                foreach ($recharges as $tur) {
                    $row = $tur->as_array();
                    if (($row['status'] ?? '') === 'on' && !Package::isRechargeExpired($row)) {
                        continue;
                    }
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
        $ownerId = WifiZoneHotspot::routerAdminId($routerName);
        if ($ownerId > 0) {
            $plansQuery->where('admin_id', $ownerId);
        } elseif (is_array($admin) && !empty($admin['id'])) {
            $plansQuery->where('admin_id', (int) $admin['id']);
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
        $ownerId = WifiZoneHotspot::routerAdminId($routerName);
        if ($ownerId > 0) {
            $plansQuery->where_raw('(admin_id = ? OR name_plan = ?)', [$ownerId, 'EXPIRE']);
        } elseif (is_array($admin) && !empty($admin['id'])) {
            $plansQuery->where_raw('(admin_id = ? OR name_plan = ?)', [(int) $admin['id'], 'EXPIRE']);
        }

        $expectedNames = ['default', 'EXPIRE'];
        $upserted = 0;
        $errors = [];
        global $config;
        $expiredList = trim((string) ($config['pppoe_setup_expired_list'] ?? 'pppoe-expired'));
        $expiredOnUp = self::pppoeExpiredProfileOnUpScript($expiredList);
        $expiredOnDown = self::pppoeExpiredProfileOnDownScript($expiredList);
        $pppoeDns = trim((string) ($config['pppoe_setup_dns_servers'] ?? '8.8.8.8,1.1.1.1'));

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
                } elseif ($pppoeDns !== '') {
                    $args['dns-server'] = $pppoeDns;
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
     * PPPoE : le client s'est-il connecté au moins une fois (session active détectée) ?
     */
    public static function pppoeCustomerEverConnected($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return false;
        }

        return trim((string) User::getAttribute('pppoe_first_connected', $customerId)) !== '';
    }

    /**
     * Enregistre la première connexion PPPoE réussie (session active sur le routeur).
     */
    public static function markPppoeCustomerConnected($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0 || self::pppoeCustomerEverConnected($customerId)) {
            return;
        }

        User::setAttribute('pppoe_first_connected', date('Y-m-d H:i:s'), $customerId);
    }

    /**
     * Active recharge → forfait courant ; expiré / off → profil EXPIRE.
     */
    public static function resolvePppoeEffectivePlan(array $recharge, array $plan, $routerName, $admin = null)
    {
        $isActive = (($recharge['status'] ?? '') === 'on') && !Package::isRechargeExpired($recharge);

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
     * @return array<string, true>
     */
    private static function indexBridgeMemberPorts($client, $bridgeName)
    {
        $bridgeName = trim((string) $bridgeName);
        $indexed = [];
        if ($bridgeName === '') {
            return $indexed;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', 'bridge,interface')
                    ->setQuery(RouterOS\Query::where('bridge', $bridgeName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $iface = strtolower(trim((string) $row->getProperty('interface')));
                if ($iface !== '') {
                    $indexed[$iface] = true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $indexed;
    }

    /**
     * Bridges DYRSIA dédiés — ne jamais voler leurs ports vers un autre service.
     *
     * @return array<int, string>
     */
    public static function dyrsiaServiceBridgeNames()
    {
        return ['bridge-hotspot', 'bridge-pppoe', 'bridge-management', 'bridge-lan'];
    }

    public static function isDyrsiaServiceBridge($bridgeName)
    {
        $bridgeName = strtolower(trim((string) $bridgeName));

        return $bridgeName !== '' && in_array($bridgeName, self::dyrsiaServiceBridgeNames(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function parseInterfacePortsList($raw)
    {
        $ports = [];
        foreach (preg_split('/[\s,;]+/', trim((string) $raw)) as $port) {
            $port = trim((string) $port);
            if ($port !== '' && !in_array($port, $ports, true)) {
                $ports[] = $port;
            }
        }

        return $ports;
    }

    /**
     * Vérifie qu'aucun port physique n'est partagé entre Hotspot, PPPoE et Management.
     */
    public static function validateServicePortIsolation(array $pppoePorts, array $hotspotPorts, array $managementPorts = [])
    {
        $pppoe = array_map('strtolower', $pppoePorts);
        $hotspot = array_map('strtolower', $hotspotPorts);
        $mgmt = array_map('strtolower', $managementPorts);

        foreach ($pppoe as $port) {
            if ($port === '') {
                continue;
            }
            if (in_array($port, $hotspot, true)) {
                return 'Conflit de ports : « ' . $port . ' » est configuré à la fois pour Hotspot et PPPoE. '
                    . 'Séparez les ports (ex. Hotspot: ether3,wifi1 — PPPoE: ether7,ether8).';
            }
            if (in_array($port, $mgmt, true)) {
                return 'Conflit de ports : « ' . $port . ' » est sur le bridge Management (ether2) '
                    . 'et ne doit pas être utilisé pour PPPoE.';
            }
        }

        return '';
    }

    /**
     * Place une interface physiquement sur le bridge cible (retire des autres bridges si besoin).
     */
    private static function ensureBridgePortMembership($client, $bridgeName, $portName)
    {
        $bridgeName = trim((string) $bridgeName);
        $portName = trim((string) $portName);
        if ($bridgeName === '' || $portName === '') {
            return ['ok' => false, 'moved' => false, 'error' => 'Bridge ou port manquant.'];
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', '.id,bridge,interface')
                    ->setQuery(RouterOS\Query::where('interface', $portName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $currentBridge = trim((string) $row->getProperty('bridge'));
                if ($currentBridge === $bridgeName) {
                    return ['ok' => true, 'moved' => false, 'error' => ''];
                }
                if ($currentBridge !== ''
                    && self::isDyrsiaServiceBridge($currentBridge)
                    && strtolower($currentBridge) !== strtolower($bridgeName)) {
                    return [
                        'ok' => false,
                        'moved' => false,
                        'error' => 'Port « ' . $portName . ' » est déjà sur « ' . $currentBridge
                            . ' » — impossible de le déplacer vers « ' . $bridgeName
                            . ' ». Hotspot, PPPoE et Management doivent avoir des ports distincts.',
                    ];
                }
                $portId = $row->getProperty('.id');
                if ($portId !== null && $portId !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/interface/bridge/port/remove'))
                            ->setArgument('numbers', $portId)
                    );
                }
            }

            $client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/add'))
                    ->setArgument('bridge', $bridgeName)
                    ->setArgument('interface', $portName)
            );

            return ['ok' => true, 'moved' => true, 'error' => ''];
        } catch (Throwable $e) {
            return ['ok' => false, 'moved' => false, 'error' => $e->getMessage()];
        } catch (Exception $e) {
            return ['ok' => false, 'moved' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Block bridged IP traffic on PPPoE ports (clients must authenticate via PPPoE).
     *
     * @return array{ok: bool, added: bool, updated: bool, errors: array<int, string>}
     */
    public static function ensurePppoeBridgeForwardBlock($client, $bridgeName, $allowedPoolCidr = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'added' => false, 'updated' => false, 'errors' => []];
        }

        $bridgeName = trim((string) $bridgeName);
        $allowedPoolCidr = trim((string) $allowedPoolCidr);
        if ($bridgeName === '') {
            return ['ok' => false, 'added' => false, 'updated' => false, 'errors' => ['Nom du bridge PPPoE manquant.']];
        }

        $comment = 'DYRSIA: block IP bypass PPPoE';
        $errors = [];
        $added = false;
        $updated = false;

        try {
            $legacyIds = [];
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,in-interface,action,src-address,comment')
                    ->setQuery(
                        RouterOS\Query::where('chain', 'forward')
                            ->andWhere('in-interface', $bridgeName)
                            ->andWhere('action', 'drop')
                    )
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $rowComment = trim((string) $row->getProperty('comment'));
                $src = trim((string) $row->getProperty('src-address'));
                $rowId = $row->getProperty('.id');
                if ($rowId === null || $rowId === '') {
                    continue;
                }
                $isLegacyBlanket = ($rowComment === $comment || $rowComment === '')
                    && ($src === '' || $src === '0.0.0.0/0');
                $isWrongNegation = $allowedPoolCidr !== ''
                    && $src !== ''
                    && $src !== ('!' . $allowedPoolCidr);
                if ($isLegacyBlanket || $isWrongNegation) {
                    $legacyIds[] = $rowId;
                }
            }
            foreach ($legacyIds as $legacyId) {
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/filter/remove'))
                        ->setArgument('numbers', $legacyId)
                );
                $updated = true;
            }

            if ($allowedPoolCidr === '') {
                return [
                    'ok' => empty($errors),
                    'added' => false,
                    'updated' => $updated,
                    'errors' => $errors,
                ];
            }

            $exists = false;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,src-address,comment')
                    ->setQuery(
                        RouterOS\Query::where('chain', 'forward')
                            ->andWhere('in-interface', $bridgeName)
                            ->andWhere('action', 'drop')
                            ->andWhere('comment', $comment)
                    )
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('src-address') === '!' . $allowedPoolCidr) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $addRequest = new RouterOS\Request('/ip/firewall/filter/add');
                $addRequest->setArgument('chain', 'forward');
                $addRequest->setArgument('in-interface', $bridgeName);
                $addRequest->setArgument('src-address', '!' . $allowedPoolCidr);
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
            'updated' => $updated,
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
            $bridgeName = self::resolvePppoeBridgeName(is_array($config) ? $config : []);
        }
        if ($bridgeName === '') {
            $bridgeName = 'bridge-pppoe';
        }

        $planSync = self::syncPppoePlans($client, $routerName, $admin);
        $secretSync = self::syncPppoeSecrets($client, $routerName, $admin, true);
        $poolCidr = self::resolvePppoePoolNetworkCidr(
            trim((string) ($config['pppoe_setup_gateway'] ?? '')),
            trim((string) ($config['pppoe_setup_pool_range'] ?? ''))
        );
        $blockIface = $bridgeName;
        if ($blockIface === '') {
            $blockIface = 'bridge-pppoe';
        }
        $firewall = self::ensurePppoeBridgeForwardBlock($client, $blockIface, $poolCidr);
        self::ensureActivePppoeSessionsUnblocked($client);

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
                        ->setArgument('password', (class_exists('HotspotCustomer') ? HotspotCustomer::defaultPassword() : '123456'))
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
                        ->setArgument('password', (class_exists('HotspotCustomer') ? HotspotCustomer::defaultPassword() : '123456'))
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
                        ->setArgument('password', (class_exists('HotspotCustomer') ? HotspotCustomer::defaultPassword() : '123456'))
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
                    ->setArgument('password', (class_exists('HotspotCustomer') ? HotspotCustomer::defaultPassword() : '123456'))
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
        $pass = Password::networkCleartext($customer);
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

        // Exact name only — getRouterFileSize() also matches "X.txt" when asking for "X",
        // which would delete the source we are about to rename.
        $targetSize = self::getRouterFileSizeExact($client, $to);
        if ($targetSize >= 0) {
            self::removeRouterFile($client, $to);
        }
        $toTxt = $to . '.txt';
        if ($from !== $toTxt && self::getRouterFileSizeExact($client, $toTxt) >= 0) {
            self::removeRouterFile($client, $toTxt);
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
            if (self::getRouterFileSizeExact($client, $to) > 0) {
                return true;
            }
            // RouterOS may keep/force the .txt suffix after rename.
            if (self::getRouterFileSizeExact($client, $toTxt) > 0) {
                return true;
            }

            return false;
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
        return self::getRouterFileSizeExact($client, $path) >= 0;
    }

    /**
     * Normalise une adresse MAC MikroTik (AA:BB:CC:DD:EE:FF).
     */
    public static function normalizeMacAddress($mac)
    {
        $mac = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', (string) $mac));
        if (strlen($mac) !== 12) {
            return '';
        }

        return implode(':', str_split($mac, 2));
    }

    /**
     * Lit l'adresse MAC matérielle du routeur via l'API RouterOS.
     */
    public static function fetchRouterMacAddress($client)
    {
        if (!$client) {
            return '';
        }

        $readMac = static function ($client, $path, $property = 'mac-address') {
            try {
                $responses = $client->sendSync(
                    (new RouterOS\Request($path))
                        ->setArgument('.proplist', $property)
                );
                foreach ($responses as $row) {
                    if ($row->getType() !== RouterOS\Response::TYPE_DATA) {
                        continue;
                    }
                    $mac = self::normalizeMacAddress((string) $row->getProperty($property));
                    if ($mac !== '') {
                        return $mac;
                    }
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }

            return '';
        };

        foreach ([
            ['/system/routerboard/print', 'mac-address'],
            ['/interface/ethernet/print', 'mac-address'],
            ['/interface/wifiwave2/print', 'mac-address'],
            ['/interface/wifi/print', 'mac-address'],
        ] as $candidate) {
            $mac = $readMac($client, $candidate[0], $candidate[1]);
            if ($mac !== '') {
                return $mac;
            }
        }

        return '';
    }

    /**
     * Exact router file lookup (no .txt alias) — used before /file/set numbers=…
     *
     * @return int Size in bytes, or -1 if the file does not exist on the router.
     */
    private static function getRouterFileSizeExact($client, $path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return -1;
        }

        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'name,size')
                    ->setQuery(RouterOS\Query::where('name', $path))
            );
            foreach ($responses as $response) {
                if ($response->getType() === RouterOS\Response::TYPE_ERROR
                    || $response->getType() === 'trap') {
                    continue;
                }
                $name = (string) $response->getProperty('name');
                if ($name !== $path) {
                    continue;
                }
                $size = $response->getProperty('size');
                if ($size !== null && $size !== '') {
                    return (int) $size;
                }

                return 0;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return -1;
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
        if (self::getRouterFileSizeExact($client, $txtPath) >= 0) {
            return $txtPath;
        }
        if (self::getRouterFileSizeExact($client, $path) >= 0) {
            return $path;
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

        if (self::getRouterFileSizeExact($client, $path) >= 0) {
            return $path;
        }
        if (self::getRouterFileSizeExact($client, $txtPath) >= 0) {
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

        // RouterOS 7 accepts login.html (~40 KB) in one /file/set when the placeholder
        // path is resolved correctly (.txt suffix). Fall back to chunked writes otherwise.
        $singleShotLimit = 65536;
        if ($length <= $singleShotLimit && self::tryRouterFileWrite($client, $path, $contents)) {
            $exactSize = self::getRouterFileSizeExact($client, $path);
            if ($exactSize >= (int) ($length * 0.9)) {
                return true;
            }
            $writePath = self::resolveRouterWritePath($client, $path, false);
            if ($writePath !== null && $writePath !== $path) {
                if (self::renameRouterFile($client, $writePath, $path)) {
                    return self::getRouterFileSizeExact($client, $path) >= (int) ($length * 0.9)
                        || self::getRouterFileSizeExact($client, $writePath) >= (int) ($length * 0.9);
                }
                // Rename failed but content is already on the router — keep it.
                return self::getRouterFileSizeExact($client, $writePath) >= (int) ($length * 0.9);
            }

            return $exactSize >= (int) ($length * 0.9);
        }

        self::removeRouterFile($client, $path);
        self::removeRouterFile($client, $path . '.txt');

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

        return self::getRouterFileSizeExact($client, $writePath) === strlen($contents);
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

    private static function runRouterOneShotScript(&$client, $scriptName, $source)
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

        if (self::$mikrotikDeployRouterRow !== null) {
            try {
                self::mikrotikPauseAfterRouterScript($client, self::$mikrotikDeployRouterRow, 35);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
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
    public static function buildHotspotLoginFetchUrls($apiUrl, $appUrl, $fetchTs = null, array $preferredBases = [], $includePublicDeploy = false, $routerName = '')
    {
        $fetchTs = $fetchTs ?? time();
        $apiUrl = rtrim(trim((string) $apiUrl), '/');
        $appUrl = rtrim(trim((string) $appUrl), '/');
        $routerName = trim((string) $routerName);
        $routerParam = $routerName !== '' ? '&router=' . rawurlencode($routerName) : '';

        // Priorité : API configurée (VPN 10.0.0.x) → bases préférées → APP_URL.
        // Ne jamais placer wifizones.org en premier : beaucoup de routeurs n'ont pas de DNS public.
        $bases = [];
        foreach (array_merge([$apiUrl], $preferredBases, [$appUrl]) as $base) {
            $base = rtrim(trim((string) $base), '/');
            if ($base === '' || !self::isRouterFetchableUrl($base)) {
                continue;
            }
            if (!in_array($base, $bases, true)) {
                $bases[] = $base;
            }
        }

        $apiHost = strtolower((string) parse_url($apiUrl, PHP_URL_HOST));
        $apiIsPrivateIp = $apiHost !== ''
            && filter_var($apiHost, FILTER_VALIDATE_IP)
            && (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $apiHost);

        // Fallback public uniquement si l'API n'est pas déjà une IP privée joignable (VPN/LAN).
        if ($includePublicDeploy && !$apiIsPrivateIp) {
            foreach (['https://wifizones.org', 'https://www.wifizones.org'] as $publicBase) {
                if (!in_array($publicBase, $bases, true) && self::isRouterFetchableUrl($publicBase)) {
                    $bases[] = $publicBase;
                }
            }
        }

        if (empty($bases)) {
            return [];
        }

        $uploadPath = '/system/uploads/mikrotik_hotspot/login.html?ts=' . $fetchTs;
        if ($routerName !== '') {
            $ownerId = WifiZoneHotspot::routerAdminId($routerName);
            if ($ownerId > 0) {
                $uploadPath = '/system/uploads/mikrotik_hotspot/admin_' . $ownerId . '/login.html?ts=' . $fetchTs;
            }
        }

        $paths = [
            $uploadPath,
            '/index.php?_route=plugin/hotspot_login_file&ts=' . $fetchTs . $routerParam,
            '/hotspot_login.html?ts=' . $fetchTs . $routerParam,
        ];

        $urls = [];
        foreach ($bases as $base) {
            foreach ($paths as $path) {
                $url = $base . $path;
                if (self::isRouterFetchableUrl($url)) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * APP_URL utilisable (corrige localhost. / localhost sans port en dev).
     */
    public static function normalizeLocalAppUrl($url = null)
    {
        $url = rtrim(trim((string) ($url ?? (defined('APP_URL') ? APP_URL : ''))), '/');
        if ($url === '') {
            return 'http://127.0.0.1:8082';
        }
        if (preg_match('#^https?://localhost\.?$#i', $url) || str_ends_with($url, '://localhost.')) {
            return 'http://127.0.0.1:8082';
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return $url;
        }
        $host = strtolower((string) $parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) && empty($parts['port'])) {
            $scheme = $parts['scheme'] ?? 'http';

            return $scheme . '://127.0.0.1:8082';
        }

        return $url;
    }

    /**
     * URL API pour login.html et paiements captifs.
     * - Dev (localhost / APP_STAGE=Dev) → APP_URL (ex. http://127.0.0.1:8082)
     * - Production → hotspot_api_url en base (ex. https://wifizones.org)
     */
    public static function resolveHotspotCaptiveApiUrl(array $config, bool $previewMode = false)
    {
        $configured = rtrim(trim((string) ($config['hotspot_api_url'] ?? '')), '/');
        $appUrl = self::normalizeLocalAppUrl();

        if ($previewMode || self::isLocalHotspotDevEnvironment($configured !== '' ? $configured : $appUrl)) {
            return self::normalizeHotspotBackendApiUrl($appUrl);
        }

        if ($configured !== '') {
            return self::normalizeHotspotBackendApiUrl($configured);
        }

        return self::normalizeHotspotBackendApiUrl($appUrl !== '' ? $appUrl : 'https://wifizones.org');
    }

    /**
     * URL du serveur PHP vue depuis le MikroTik (NAT proxy, fetch login.html).
     * Dev local : IP VPN (ex. http://10.0.0.2:8082), pas localhost ni wifizones.org.
     */
    public static function resolveHotspotBackendApiUrl(array $config)
    {
        $configured = rtrim(trim((string) ($config['hotspot_api_url'] ?? '')), '/');
        $appUrl = self::normalizeLocalAppUrl();

        if (!self::isLocalHotspotDevEnvironment($configured !== '' ? $configured : $appUrl)) {
            return self::resolveHotspotCaptiveApiUrl($config, false);
        }

        if ($configured !== '') {
            $host = strtolower((string) parse_url($configured, PHP_URL_HOST));
            // En dev : ignorer wifizones.org en base — utiliser l'IP VPN du Mac (10.0.0.2:8082).
            $isProdHost = ($host === 'wifizones.org' || str_ends_with($host, '.wifizones.org'));
            if ($host !== ''
                && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)
                && !$isProdHost) {
                return self::normalizeHotspotBackendApiUrl($configured);
            }
        }

        $vpnUrl = trim((string) (getenv('HOTSPOT_VPN_API_URL') ?: 'http://10.0.0.2:8082'));

        return self::normalizeHotspotBackendApiUrl($vpnUrl);
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
        // VPS WireGuard (10.0.0.1) : port web = 80. Dev Mac (10.0.0.2) : conserver 8080/8082.
        if (filter_var($host, FILTER_VALIDATE_IP) && preg_match('/^10\.0\.0\.1$/', $host)) {
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
                // RouterOS API : /remove n'accepte pas de query — print d'abord, puis remove par .id.
                $ids = [];
                foreach ($client->sendSync(
                    (new RouterOS\Request($chainPath . '/print'))
                        ->setArgument('.proplist', '.id')
                        ->setQuery(RouterOS\Query::where('comment', $comment))
                ) as $row) {
                    if ($row->getType() !== RouterOS\Response::TYPE_DATA) {
                        continue;
                    }
                    $id = $row->getProperty('.id');
                    if ($id !== null && $id !== '') {
                        $ids[] = $id;
                    }
                }
                if ($ids !== []) {
                    $client->sendSync(
                        (new RouterOS\Request($chainPath . '/remove'))
                            ->setArgument('numbers', implode(',', $ids))
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
                (new RouterOS\Request('/ip/dhcp-server/network/print'))
                    ->setArgument('.proplist', 'address,gateway')
            );
            foreach ($networks as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $gateway = trim((string) $row->getProperty('gateway'));
                if ($gateway !== '' && filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $gateway;
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
        $forwardComment = 'WifiZone hotspot API forward';
        $errors = [];

        try {
            if (self::hotspotApiNatProxyConfigured(
                $client,
                $listenIp,
                $listenPort,
                $backendHost,
                $backendPort,
                $proxyComment,
                $snatComment,
                $inputComment
            )) {
                $wgBackend = self::ensureHotspotWalledGardenBatch($client, [
                    'http://' . $backendHost . ($backendPort === 80 ? '' : ':' . $backendPort),
                    $captiveUrl,
                ]);
                if (empty($wgBackend['ok'])) {
                    return [
                        'ok' => false,
                        'errors' => $wgBackend['errors'] ?? ['walled-garden backend'],
                        'captive_url' => $captiveUrl,
                    ];
                }

                return ['ok' => true, 'captive_url' => $captiveUrl];
            }

            self::removeFirewallRulesByComment($client, $proxyComment);
            self::removeFirewallRulesByComment($client, $snatComment);
            self::removeFirewallRulesByComment($client, $inputComment);
            self::removeFirewallRulesByComment($client, $forwardComment);

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

            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/add'))
                    ->setArgument('chain', 'forward')
                    ->setArgument('protocol', 'tcp')
                    ->setArgument('dst-address', $backendHost)
                    ->setArgument('dst-port', (string) $backendPort)
                    ->setArgument('action', 'accept')
                    ->setArgument('comment', $forwardComment)
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
     * Forfaits hotspot embarqués dans login.html (sans appel API captif).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function collectHotspotEmbeddedPlans(array $config, $routerName, $apiUrl)
    {
        $routerName = trim((string) $routerName);
        $apiUrl = rtrim(trim((string) $apiUrl), '/');
        $embeddedPlans = [];

        try {
            $plansQuery = WifiZoneHotspot::plansQueryForRouter($routerName);
            foreach ($plansQuery->find_many() as $plan) {
                $planId = (int) ($plan['id'] ?? 0);
                $price = (string) ($plan['price'] ?? '');
                $embeddedPlans[] = [
                    'planid' => $planId,
                    'planId' => $planId,
                    'id' => $planId,
                    'planname' => (string) ($plan['name_plan'] ?? $plan['name'] ?? ''),
                    'price' => $price,
                    'currency' => $config['currency_code'] ?? 'XAF',
                    'validity' => trim((string) ($plan['validity'] ?? '') . ' ' . (string) ($plan['validity_unit'] ?? '')),
                    'paymentlink' => $apiUrl . '/index.php?_route=plugin/hotspot_pay&routername='
                        . rawurlencode($routerName) . '&planid=' . $planId . '&amount=' . rawurlencode($price),
                    'routername' => $routerName,
                    'routerName' => $routerName,
                ];
            }
        } catch (Exception $e) {
            return [];
        }

        return $embeddedPlans;
    }

    /**
     * HTML statique des forfaits (visible même si JS/API captif échoue).
     */
    public static function buildHotspotPlansListHtml(array $plans, $routerName)
    {
        if ($plans === []) {
            return '<div style="text-align:center;color:#f87171;padding:12px">Aucun forfait disponible</div>';
        }

        $routerName = htmlspecialchars(trim((string) $routerName), ENT_QUOTES, 'UTF-8');
        $chunks = [];
        foreach ($plans as $plan) {
            $planName = htmlspecialchars((string) ($plan['planname'] ?? ''), ENT_QUOTES, 'UTF-8');
            $price = htmlspecialchars((string) ($plan['price'] ?? ''), ENT_QUOTES, 'UTF-8');
            $currency = htmlspecialchars((string) ($plan['currency'] ?? 'XAF'), ENT_QUOTES, 'UTF-8');
            $validity = htmlspecialchars((string) ($plan['validity'] ?? ''), ENT_QUOTES, 'UTF-8');
            $planId = htmlspecialchars((string) ($plan['planid'] ?? ''), ENT_QUOTES, 'UTF-8');
            $chunks[] = '<div class="plan" role="button" tabindex="0" data-plan-name="' . $planName . '" data-plan-price="' . $price
                . '" data-plan-currency="' . $currency . '" data-plan-validity="' . $validity
                . '" data-plan-id="' . $planId . '" data-router-name="' . $routerName
                . '"><div><div class="plan-name">⚡ ' . $planName . '</div><div class="plan-detail">⏱️ '
                . $validity . '</div></div><div><span class="plan-price">' . $price . ' ' . $currency
                . '</span><span class="badge">ILLIMITÉ</span></div></div>';
        }

        return implode("\n", $chunks);
    }

    /**
     * Génère login.html prêt pour MikroTik (forfaits embarqués + liste HTML statique).
     */
    public static function buildHotspotLoginHtmlForDeploy(array $config, $routerName = '', $captiveApiUrl = '')
    {
        global $root_path;

        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            $routerName = trim((string) ($config['hotspot_login_router'] ?? ''));
        }

        $captiveApiUrl = rtrim(trim((string) $captiveApiUrl), '/');
        if ($captiveApiUrl === '') {
            $captiveApiUrl = self::resolveHotspotCaptiveApiUrl($config, false);
        }

        $templateFile = $root_path . 'ui/ui/templates/mikrotik-hotspot-login.html';
        if (!is_file($templateFile)) {
            return null;
        }

        $html = file_get_contents($templateFile);
        if ($html === false || $html === '') {
            return null;
        }

        $embeddedPlans = self::collectHotspotEmbeddedPlans($config, $routerName, $captiveApiUrl);
        $embeddedPlansJs = 'const HOTSPOT_EMBEDDED_PLANS = '
            . json_encode($embeddedPlans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        if (strpos($html, 'const HOTSPOT_EMBEDDED_PLANS') !== false) {
            $html = preg_replace('/const HOTSPOT_EMBEDDED_PLANS = .*?;/s', $embeddedPlansJs, $html, 1) ?? $html;
        } else {
            $html = str_replace('let CLIENT_MAC = \'\';', 'let CLIENT_MAC = \'\';' . "\n    " . $embeddedPlansJs, $html);
        }

        $plansHtml = self::buildHotspotPlansListHtml($embeddedPlans, $routerName);
        $html = preg_replace(
            '/<div class="plans" id="plansList">\s*.*?<\/div>/s',
            '<div class="plans" id="plansList">' . "\n" . $plansHtml . "\n" . '</div>',
            $html,
            1
        ) ?? $html;

        $routerNameJs = 'const HOTSPOT_ROUTER_NAME = ' . json_encode($routerName !== '' ? $routerName : 'HP') . ';';
        $html = preg_replace('/const HOTSPOT_ROUTER_NAME = .*?;/s', $routerNameJs, $html, 1) ?? $html;

        $paymentGateway = trim((string) ($config['hotspot_payment_gateway'] ?? 'campay'));
        if ($paymentGateway === '') {
            $paymentGateway = 'campay';
        }
        $html = preg_replace(
            '/const HOTSPOT_PAYMENT_GATEWAY = .*?;/',
            'const HOTSPOT_PAYMENT_GATEWAY = ' . json_encode($paymentGateway) . ';',
            $html,
            1
        ) ?? $html;

        $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
        $html = self::patchHotspotLoginCaptiveApi($html, $captiveApiUrl, $dnsName);
        $html = self::patchHotspotLoginHelpSection($html, [
            'title' => $config['hotspot_help_title'] ?? '',
            'text' => $config['hotspot_help_text'] ?? '',
            'contact' => $config['hotspot_contact'] ?? ($config['hotspot_help_whatsapp_label'] ?? ''),
            'contact_phone' => $config['hotspot_contact_phone'] ?? ($config['hotspot_help_whatsapp'] ?? ''),
        ]);
        $html = MobileMoneyGateway::patchModernHotspotChapLogin($html);
        $html = MobileMoneyGateway::repairHotspotLoginHtml($html);

        $html = preg_replace(
            '/<script\b[^>]*\bsrc=["\'][^"\']*sweetalert2[^"\']*["\'][^>]*>\s*<\/script>\s*/i',
            '',
            $html
        ) ?? $html;

        $sweetSrc = rtrim($captiveApiUrl, '/') . '/ui/ui/scripts/plugins/sweetalert2.min.js';
        $sweetLoader = '<script>window.addEventListener("load",function(){var s=document.createElement("script");s.src="'
            . str_replace('"', '\\"', $sweetSrc)
            . '";s.async=true;s.onerror=function(){};document.body.appendChild(s);});</script>';
        $html = str_replace('</body>', $sweetLoader . "\n</body>", $html);

        $minLines = array_filter(
            array_map('ltrim', preg_split('/\r\n|\r|\n/', $html)),
            static function ($line) {
                return $line !== '';
            }
        );

        return implode("\n", $minLines);
    }

    /**
     * URL proxy captif sur la passerelle hotspot (ex. http://10.10.0.1:8080).
     */
    public static function resolveHotspotCaptiveProxyUrl($gatewayIp, $proxyPort = 8080)
    {
        $gatewayIp = trim((string) $gatewayIp);
        if (strpos($gatewayIp, '/') !== false) {
            $gatewayIp = (string) explode('/', $gatewayIp, 2)[0];
        }
        if ($gatewayIp === '' || !filter_var($gatewayIp, FILTER_VALIDATE_IP)) {
            return '';
        }
        $proxyPort = (int) $proxyPort;
        if ($proxyPort <= 0) {
            $proxyPort = 8080;
        }

        return 'http://' . $gatewayIp . ($proxyPort === 80 ? '' : ':' . $proxyPort);
    }

    /**
     * Met à jour APP_URL, assets (SweetAlert) et liens de paiement dans login.html
     * pour le réseau captif (proxy NAT 10.x.x.1:8080 ou Hotspot API URL).
     */
    public static function patchHotspotLoginCaptiveApi($html, $captiveApiUrl, $dnsName = '', $backendApiUrl = '')
    {
        $html = (string) $html;
        $captiveApiUrl = rtrim(trim((string) $captiveApiUrl), '/');
        $dnsName = trim((string) $dnsName);
        if ($captiveApiUrl === '') {
            return $html;
        }

        $prevAppUrl = '';
        if (preg_match('/const APP_URL = (.*?);/s', $html, $appMatch)) {
            $decoded = json_decode(trim($appMatch[1]));
            if (is_string($decoded) && $decoded !== '') {
                $prevAppUrl = rtrim($decoded, '/');
            }
        }

        $html = preg_replace(
            '/const APP_URL = .*?;/s',
            'const APP_URL = ' . json_encode($captiveApiUrl) . ';',
            $html,
            1
        ) ?? $html;

        $proxyPort = 8080;
        $captiveParts = parse_url($captiveApiUrl);
        if (!empty($captiveParts['port'])) {
            $proxyPort = (int) $captiveParts['port'];
        }
        $backendApiUrl = rtrim(trim((string) $backendApiUrl), '/');
        $proxyJs = 'const HOTSPOT_PROXY_PORT = ' . $proxyPort . ';';
        $backendJs = 'const HOTSPOT_BACKEND_URL = ' . json_encode($backendApiUrl) . ';';
        if (preg_match('/const HOTSPOT_PROXY_PORT = .*?;/', $html)) {
            $html = preg_replace('/const HOTSPOT_PROXY_PORT = .*?;/', $proxyJs, $html, 1) ?? $html;
        } else {
            $html = preg_replace(
                '/(const APP_URL = .*?;)/s',
                '$1' . "\n    " . $proxyJs,
                $html,
                1
            ) ?? $html;
        }
        if (preg_match('/const HOTSPOT_BACKEND_URL = .*?;/', $html)) {
            $html = preg_replace('/const HOTSPOT_BACKEND_URL = .*?;/', $backendJs, $html, 1) ?? $html;
        } else {
            $html = preg_replace(
                '/(const HOTSPOT_PROXY_PORT = .*?;)/s',
                '$1' . "\n    " . $backendJs,
                $html,
                1
            ) ?? $html;
        }

        // Assets must use the same captive base as APP_URL — hotspot clients cannot
        // reach the VPN peer IP (10.0.0.2) directly; only the NAT proxy on the gateway.
        $sweetSrc = $captiveApiUrl . '/ui/ui/scripts/plugins/sweetalert2.min.js';
        $html = preg_replace(
            '/(<script\b[^>]*\bsrc=["\'])[^"\']*sweetalert2[^"\']*(["\'][^>]*>)/i',
            '$1' . htmlspecialchars($sweetSrc, ENT_QUOTES, 'UTF-8') . '$2',
            $html,
            1
        ) ?? $html;
        $html = str_replace(
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            $sweetSrc,
            $html
        );
        if ($prevAppUrl !== '' && $prevAppUrl !== $captiveApiUrl) {
            $html = str_replace($prevAppUrl . '/ui/', $captiveApiUrl . '/ui/', $html);
            $html = str_replace(
                str_replace('/', '\\/', $prevAppUrl) . '\\/ui\\/',
                str_replace('/', '\\/', $captiveApiUrl) . '\\/ui\\/',
                $html
            );
        }

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
    public static function verifyHotspotFetchUrl($url, $timeout = 5, $routerName = '')
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
            $routerName = trim((string) $routerName);
            if ($routerName === '') {
                $query = parse_url($url, PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    parse_str($query, $params);
                    $routerName = trim((string) ($params['router'] ?? $params['routername'] ?? ''));
                }
            }
            if ($routerName !== '') {
                $ownerId = WifiZoneHotspot::routerAdminId($routerName);
                if ($ownerId > 0) {
                    $loginFile = WifiZoneHotspot::hotspotLoginHtmlPath($ownerId, $UPLOAD_PATH);
                }
            } elseif (preg_match('#/mikrotik_hotspot/admin_(\d+)/login\.html#', $url, $matches)) {
                $loginFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot'
                    . DIRECTORY_SEPARATOR . 'admin_' . (int) $matches[1]
                    . DIRECTORY_SEPARATOR . 'login.html';
            }
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
    public static function verifyHotspotFetchUrls(array $urls, $timeout = 5, $routerName = '')
    {
        $urls = array_values(array_filter(array_map('trim', $urls)));
        if (empty($urls)) {
            return 'Aucune URL de fetch valide pour login.html';
        }

        $errors = [];
        foreach ($urls as $url) {
            $result = self::verifyHotspotFetchUrl($url, $timeout, $routerName);
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
     * Environnement dev local (php -S / APP_STAGE=Dev).
     * En prod (APP_URL = wifizones.org), retourne false → hotspot_api_url configurée en base.
     */
    public static function isLocalHotspotDevEnvironment($apiUrl = null)
    {
        global $_app_stage;

        $appUrl = self::normalizeLocalAppUrl();
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if (in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
        if (!empty($_app_stage) && strcasecmp((string) $_app_stage, 'Dev') === 0) {
            return true;
        }

        $apiUrl = trim((string) $apiUrl);
        $apiHost = strtolower((string) parse_url($apiUrl, PHP_URL_HOST));
        if ($apiHost !== ''
            && !in_array($apiHost, ['localhost', '127.0.0.1', '::1'], true)
            && self::isRouterFetchableUrl($apiUrl)) {
            return false;
        }

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

        return self::getRouterFileSizeExact($client, $writePath) === strlen($contents);
    }

    /**
     * @return int Size in bytes, or -1 if the file does not exist on the router.
     */
    public static function getRouterFileSize($client, $path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return -1;
        }

        $candidates = [$path];
        if (substr($path, -4) !== '.txt') {
            $candidates[] = $path . '.txt';
        }

        foreach ($candidates as $candidate) {
            try {
                $responses = $client->sendSync(
                    (new RouterOS\Request('/file/print'))
                        ->setArgument('.proplist', 'name,size')
                        ->setQuery(RouterOS\Query::where('name', $candidate))
                );
                foreach ($responses as $response) {
                    if ($response->getType() === RouterOS\Response::TYPE_ERROR
                        || $response->getType() === 'trap') {
                        continue;
                    }
                    $name = (string) $response->getProperty('name');
                    if ($name !== '' && $name !== $candidate) {
                        continue;
                    }
                    $size = $response->getProperty('size');
                    if ($size !== null && $size !== '') {
                        return (int) $size;
                    }
                    // File exists but size empty (placeholder) — treat as 0 bytes.
                    if ($name === $candidate) {
                        return 0;
                    }
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        // Never fall back to unfiltered /file/print — that can hang for minutes
        // over VPN when the router store has many files.
        return -1;
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
        // Éviter le fetch vers la passerelle hotspot (10.10.0.x) : Host unreachable depuis le routeur.
        $fetchUrls = array_values(array_filter(
            self::filterRouterFetchUrls($fetchUrls),
            static function ($url) {
                $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
                if ($host === '') {
                    return false;
                }
                if (preg_match('/^10\.10\./', $host) || preg_match('/^192\.168\.100\./', $host)) {
                    return false;
                }

                return true;
            }
        ));
        $errors = [];

        $deviceMode = self::ensureHotspotDeviceMode($client);
        if (empty($deviceMode['ok'])) {
            return [
                'ok' => false,
                'errors' => $deviceMode['errors'] ?? ['Hotspot bloqué par le mode appareil RouterOS 7.'],
            ];
        }

        self::ensureRouterDirectory($client, 'hotspot');

        $tmpPath = 'hotspot/dyrsia-login-new.html';
        $finalPath = 'hotspot/login.html';

        $promoteWrittenLogin = static function ($client, $fromPath, $finalPath, $length) {
            $fromExact = self::getRouterFileSizeExact($client, $fromPath);
            $fromTxt = self::getRouterFileSizeExact($client, $fromPath . '.txt');
            $source = null;
            if ($fromExact >= (int) ($length * 0.9)) {
                $source = $fromPath;
            } elseif ($fromTxt >= (int) ($length * 0.9)) {
                $source = $fromPath . '.txt';
            }
            if ($source === null) {
                return null;
            }
            if ($source === $finalPath || self::renameRouterFile($client, $source, $finalPath)) {
                if (self::getRouterFileSizeExact($client, $finalPath) >= (int) ($length * 0.9)
                    || self::getRouterFileSizeExact($client, $finalPath . '.txt') >= (int) ($length * 0.9)) {
                    return $finalPath;
                }
            }
            // Contenu présent même si le nom final reste .txt
            if (self::getRouterFileSizeExact($client, $source) >= (int) ($length * 0.9)) {
                return $source;
            }

            return null;
        };

        // 1) API write first — fast and reliable even over low-MTU VPN tunnels
        //    (outgoing data fragments correctly, unlike inbound /tool fetch of 16KB).
        self::removeRouterFile($client, $tmpPath);
        self::removeRouterFile($client, $tmpPath . '.txt');
        if (self::tryRouterFileWriteChunked($client, $tmpPath, $html)) {
            $promoted = $promoteWrittenLogin($client, $tmpPath, $finalPath, $length);
            if ($promoted !== null) {
                $mirror = self::mirrorHotspotLoginToFlashFallback($client, $html, $length);
                return [
                    'ok' => true,
                    'path' => $promoted,
                    'method' => 'api',
                    'flash_mirror' => $mirror,
                ];
            }
            $errors[] = $finalPath . ' (api): fichier écrit mais remplacement final impossible';
        } else {
            $errors[] = $finalPath . ': écriture API refusée (' . $length . ' octets)';
        }

        // 2) Fallback: /tool fetch (HTTP) — only if API write failed.
        //    Limiter les essais : 3 URLs max (évite les timeouts en série sur VPN).
        if (!empty($fetchUrls)) {
            foreach (array_slice($fetchUrls, 0, 3) as $url) {
                self::removeRouterFile($client, $tmpPath);
                self::removeRouterFile($client, $tmpPath . '.txt');
                $fetchError = self::fetchUrlToRouterFile($client, $url, $tmpPath);
                $normalizedPath = self::normalizeRouterFetchedFile($client, $tmpPath);
                $fetchedSize = 0;
                if ($normalizedPath !== null) {
                    $fetchedSize = max(
                        self::getRouterFileSizeExact($client, $normalizedPath),
                        self::getRouterFileSizeExact($client, $normalizedPath . '.txt')
                    );
                }
                if ($fetchError === null && $fetchedSize >= (int) ($length * 0.9)) {
                    $promoted = $promoteWrittenLogin($client, $normalizedPath ?? $tmpPath, $finalPath, $length);
                    if ($promoted !== null) {
                        $mirror = self::mirrorHotspotLoginToFlashFallback($client, $html, $length);
                        return [
                            'ok' => true,
                            'path' => $promoted,
                            'method' => 'fetch',
                            'flash_mirror' => $mirror,
                        ];
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
     * Si le profil pointe encore vers flash/hotspot, écraser aussi la page usine.
     *
     * @return array{ok: bool, path?: string, error?: string}
     */
    private static function mirrorHotspotLoginToFlashFallback($client, $html, $length)
    {
        $flashDir = 'flash/hotspot';
        $flashPath = $flashDir . '/login.html';
        try {
            self::ensureRouterDirectory($client, $flashDir);
            self::removeRouterFile($client, $flashPath);
            self::removeRouterFile($client, $flashPath . '.txt');
            $tmp = $flashDir . '/dyrsia-login-new.html';
            self::removeRouterFile($client, $tmp);
            self::removeRouterFile($client, $tmp . '.txt');
            if (!self::tryRouterFileWriteChunked($client, $tmp, $html)) {
                return ['ok' => false, 'error' => 'écriture flash/hotspot refusée'];
            }
            $source = null;
            if (self::getRouterFileSizeExact($client, $tmp) >= (int) ($length * 0.9)) {
                $source = $tmp;
            } elseif (self::getRouterFileSizeExact($client, $tmp . '.txt') >= (int) ($length * 0.9)) {
                $source = $tmp . '.txt';
            }
            if ($source === null) {
                return ['ok' => false, 'error' => 'fichier flash temporaire trop petit'];
            }
            if ($source !== $flashPath && !self::renameRouterFile($client, $source, $flashPath)) {
                // Contenu présent sous .txt — acceptable tant que le profil flash le lit.
                if (self::getRouterFileSizeExact($client, $source) >= (int) ($length * 0.9)) {
                    return ['ok' => true, 'path' => $source];
                }

                return ['ok' => false, 'error' => 'rename flash/hotspot/login.html échoué'];
            }
            $size = max(
                self::getRouterFileSizeExact($client, $flashPath),
                self::getRouterFileSizeExact($client, $flashPath . '.txt')
            );
            if ($size >= (int) ($length * 0.9)) {
                return ['ok' => true, 'path' => $flashPath];
            }

            return ['ok' => false, 'error' => 'flash/hotspot/login.html trop petit (' . $size . ')'];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
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
        $contactLabel = trim((string) ($help['contact'] ?? $help['whatsapp_label'] ?? ''));
        $contactPhone = trim((string) ($help['contact_phone'] ?? $help['whatsapp'] ?? ''));

        return self::patchHotspotLoginContactSection($html, $contactLabel, $contactPhone, $help);
    }

    /**
     * Contact téléphonique cliquable (tel:) sur la page captive — sans bouton WhatsApp.
     */
    public static function patchHotspotLoginContactSection($html, $contactLabel, $contactPhone, array $help = [])
    {
        $html = (string) $html;
        $title = trim((string) ($help['title'] ?? ''));
        $text = trim((string) ($help['text'] ?? ''));

        if ($title !== '') {
            $html = preg_replace('/<h3>\s*Assistance\s*&amp;\s*Connexion\s*à\s*domicile\s*<\/h3>/is', '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>', $html, 1) ?? $html;
        }
        if ($text !== '') {
            $html = preg_replace('/<p>\s*Une question \? Un besoin technique \?\s*<\/p>/is', '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>', $html, 1) ?? $html;
        }

        $contactLabel = trim((string) $contactLabel);
        if ($contactLabel === '') {
            $contactLabel = 'Assistance';
        }
        $safeLabel = htmlspecialchars($contactLabel, ENT_QUOTES, 'UTF-8');

        $html = preg_replace(
            '/<span\s+data-hotspot-contact-label>.*?<\/span>/is',
            '<span data-hotspot-contact-label>' . $safeLabel . '</span>',
            $html,
            1
        ) ?? $html;

        $digits = preg_replace('/\D/', '', (string) $contactPhone);
        if ($digits === '') {
            $html = preg_replace('/<a\s[^>]*data-hotspot-contact[^>]*>.*?<\/a>\s*/is', '', $html, 1) ?? $html;

            return $html;
        }

        $telHref = htmlspecialchars('tel:+' . $digits, ENT_QUOTES, 'UTF-8');
        $contactBlock = '<a href="' . $telHref . '" class="contact" data-hotspot-contact>'
            . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>'
            . '<span data-hotspot-contact-label>' . $safeLabel . '</span></a>';

        if (preg_match('/<a\s[^>]*data-hotspot-contact[^>]*>.*?<\/a>/is', $html)) {
            $html = preg_replace('/<a\s[^>]*data-hotspot-contact[^>]*>.*?<\/a>/is', $contactBlock, $html, 1) ?? $html;
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

    public static function ensureHotspotCaptiveExtrasWalledGarden($client, $appUrl, $skipPrune = false)
    {
        if (!$skipPrune) {
            self::pruneHotspotWalledGardenBatch($client);
            self::removeHotspotWalledGardenHosts($client, ['wa.me', 'api.whatsapp.com', 'web.whatsapp.com']);
        }

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
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
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
    public static function fetchHotspotSetupSnapshot($client, $preferredHotspotName = '', $lightweight = false)
    {
        $preferredHotspotName = trim((string) $preferredHotspotName);
        $snapshot = [
            'ok' => true,
            'interfaces' => [],
            'pools' => [],
            'hotspots' => [],
            'profiles' => [],
            'networks' => [],
            'suggested' => array_merge(self::serviceBridgeDefaults(), [
                'hotspot_name' => '',
                'hotspot_interface' => '',
                'hotspot_profile' => 'hotspot',
                'hotspot_local_address' => '10.10.0.1/24',
                'hotspot_masquerade' => '1',
                'hotspot_address_pool' => '10.10.0.10-10.10.0.254',
                'hotspot_pool_name' => 'hs-pool',
                'hotspot_pool_range' => '10.10.0.10-10.10.0.254',
                'hotspot_smtp_server' => '0.0.0.0',
                'hotspot_dns_server' => '8.8.8.8',
                'hotspot_dns_name' => '',
                'hotspot_login_methods' => 'http-pap,mac-cookie',
                'hotspot_cookie_lifetime' => '1d 00:00:00',
                'hotspot_idle_timeout' => '00:10:00',
                'hotspot_address_per_mac' => '1',
            ]),
            'bridge_ports' => [],
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
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', 'bridge,interface')
            );
            foreach ($responses as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
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
                    ->setArgument('.proplist', 'name,dns-name,smtp-server,dns-server,hotspot-address,login-by,idle-timeout,http-cookie-lifetime')
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
                    'login_by' => trim((string) $row->getProperty('login-by')),
                    'idle_timeout' => trim((string) $row->getProperty('idle-timeout')),
                    'http_cookie_lifetime' => trim((string) $row->getProperty('http-cookie-lifetime')),
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
                    ->setArgument('.proplist', 'name,interface,profile,address-pool,dns-name,address-per-mac')
            );
            foreach ($responses as $row) {
                $snapshot['hotspots'][] = [
                    'name' => trim((string) $row->getProperty('name')),
                    'interface' => trim((string) $row->getProperty('interface')),
                    'profile' => trim((string) $row->getProperty('profile')),
                    'address_pool' => trim((string) $row->getProperty('address-pool')),
                    'dns_name' => trim((string) $row->getProperty('dns-name')),
                    'address_per_mac' => trim((string) $row->getProperty('address-per-mac')),
                ];
            }
        } catch (Throwable $e) {
            $snapshot['errors'][] = 'hotspots: ' . $e->getMessage();
        } catch (Exception $e) {
            $snapshot['errors'][] = 'hotspots: ' . $e->getMessage();
        }

        if (!$lightweight) {
            try {
                $responses = $client->sendSync(
                    (new RouterOS\Request('/ip/dhcp-server/network/print'))
                        ->setArgument('.proplist', 'address,gateway,dns-server')
                );
                foreach ($responses as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $address = trim((string) $row->getProperty('address'));
                    if ($address === '') {
                        continue;
                    }
                    $snapshot['networks'][] = [
                        'address' => $address,
                        'gateway' => trim((string) $row->getProperty('gateway')),
                        'dns_server' => trim((string) $row->getProperty('dns-server')),
                    ];
                }
            } catch (Throwable $e) {
                $snapshot['errors'][] = 'networks: ' . $e->getMessage();
            } catch (Exception $e) {
                $snapshot['errors'][] = 'networks: ' . $e->getMessage();
            }
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

        $snapshot['suggested']['hotspot_masquerade'] = $lightweight
            ? '1'
            : (self::hotspotMasqueradeEnabled($client) ? '1' : '0');

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
            $snapshot['suggested']['hotspot_profile'] = $hs['profile'] !== '' ? $hs['profile'] : 'hotspot';
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
            $defaultPoolName = $snapshot['suggested']['hotspot_pool_name'] ?: 'hs-pool';
            $matchedPool = null;
            foreach ($snapshot['pools'] as $pool) {
                if ($pool['name'] === $defaultPoolName) {
                    $matchedPool = $pool;
                    break;
                }
            }
            if ($matchedPool !== null) {
                $snapshot['suggested']['hotspot_pool_name'] = $matchedPool['name'];
                if ($matchedPool['ranges'] !== '') {
                    $snapshot['suggested']['hotspot_address_pool'] = $matchedPool['ranges'];
                    $snapshot['suggested']['hotspot_pool_range'] = $matchedPool['ranges'];
                }
            } elseif ($snapshot['suggested']['hotspot_pool_name'] === '') {
                $snapshot['suggested']['hotspot_pool_name'] = $snapshot['pools'][0]['name'];
                if ($snapshot['pools'][0]['ranges'] !== '') {
                    $snapshot['suggested']['hotspot_address_pool'] = $snapshot['pools'][0]['ranges'];
                    $snapshot['suggested']['hotspot_pool_range'] = $snapshot['pools'][0]['ranges'];
                }
            }
        }

        if (!empty($snapshot['networks'])) {
            $snapshot['suggested']['hotspot_local_address'] = $snapshot['networks'][0]['address'];
        }

        $profileName = $snapshot['suggested']['hotspot_profile'] ?: 'hotspot';
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
            if ($prof['login_by'] !== '') {
                $snapshot['suggested']['hotspot_login_methods'] = self::normalizeHotspotLoginBy($prof['login_by']);
            }
            if ($prof['idle_timeout'] !== '') {
                $snapshot['suggested']['hotspot_idle_timeout'] = $prof['idle_timeout'];
            }
            if ($prof['http_cookie_lifetime'] !== '') {
                $snapshot['suggested']['hotspot_cookie_lifetime'] = $prof['http_cookie_lifetime'];
            } elseif (!$lightweight && self::hotspotLoginByIncludes($prof['login_by'] ?? '', 'mac-cookie')) {
                $macCookieTimeout = self::readHotspotUserProfileMacCookieTimeout($client, 'default');
                if ($macCookieTimeout !== '') {
                    $snapshot['suggested']['hotspot_cookie_lifetime'] = $macCookieTimeout;
                }
            }
            break;
        }

        if ($activeHotspot !== null && ($activeHotspot['address_per_mac'] ?? '') !== '') {
            $snapshot['suggested']['hotspot_address_per_mac'] = $activeHotspot['address_per_mac'];
        }

        $hotspotIface = trim((string) ($activeHotspot['interface'] ?? ($snapshot['suggested']['hotspot_interface'] ?? '')));
        if (!$lightweight && $hotspotIface !== '') {
            $bridgeDiag = self::readHotspotBridgeFirewallStatus($client, $hotspotIface);
            $snapshot['bridge_firewall'] = $bridgeDiag;
            if (!empty($bridgeDiag['is_bridge']) && empty($bridgeDiag['use_ip_firewall'])) {
                $enabled = self::readBridgeUseIpFirewall($client);
                if ($enabled === false) {
                    $snapshot['errors'][] = 'Attention : bridge settings use-ip-firewall=no'
                        . ' — relancez « Envoyer vers MikroTik » ou : /interface bridge settings set use-ip-firewall=yes';
                }
            }
        }

        if (!empty($snapshot['errors'])) {
            $snapshot['ok'] = count($snapshot['interfaces']) > 0 || count($snapshot['pools']) > 0;
        }

        self::applyRouterPortSuggestions($snapshot, $client, $lightweight);

        return $snapshot;
    }

    /**
     * Crée ou met à jour le serveur hotspot MikroTik selon l'assistant Hotspot Setup.
     *
     * @return array{ok: bool, errors?: array<int, string>, actions?: array<int, string>, hotspot_name?: string}
     */
    public static function applyHotspotSetupFromConfig($client, array $config, $routerRow = null, $skipBridgeHardening = false)
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
        $profileName = trim((string) ($config['hotspot_profile'] ?? 'hotspot'));
        $profileName = $profileName !== '' ? $profileName : 'hotspot';
        $hotspotName = trim((string) ($config['hotspot_name'] ?? ''));
        $localAddress = trim((string) ($config['hotspot_local_address'] ?? ''));
        $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
        $smtpServer = trim((string) ($config['hotspot_smtp_server'] ?? '0.0.0.0'));
        $dnsServer = trim((string) ($config['hotspot_dns_server'] ?? '8.8.8.8'));
        $masquerade = !empty($config['hotspot_masquerade']) && (string) $config['hotspot_masquerade'] !== '0';
        $loginMethods = self::captivePortalLoginBy();
        $cookieLifetime = self::normalizeHotspotCookieLifetime($config['hotspot_cookie_lifetime'] ?? '1d 00:00:00');
        $idleTimeout = trim((string) ($config['hotspot_idle_timeout'] ?? '00:10:00'));
        $addressPerMac = trim((string) ($config['hotspot_address_per_mac'] ?? '1'));
        if ($idleTimeout === '') {
            $idleTimeout = '00:10:00';
        }
        if ($addressPerMac === '' || !ctype_digit($addressPerMac)) {
            $addressPerMac = '1';
        }

        if ($interface === '') {
            return ['ok' => false, 'errors' => ['Interface hotspot manquante (étape 2 de l\'assistant).']];
        }

        $deviceMode = self::ensureHotspotDeviceMode($client);
        if (empty($deviceMode['ok'])) {
            return [
                'ok' => false,
                'errors' => $deviceMode['errors'] ?? ['Hotspot bloqué par le mode appareil RouterOS 7.'],
                'actions' => $deviceMode['actions'] ?? [],
            ];
        }

        $resolvedSimple = self::resolveSimpleHotspotInterface($client, $config);
        if ($resolvedSimple !== '') {
            $interface = $resolvedSimple;
        }
        if ($poolName === '' || $poolRange === '') {
            return ['ok' => false, 'errors' => ['Pool IP manquant : renseignez le nom du pool et la plage (étape 2).']];
        }

        $errors = [];
        $actions = [];

        // Sécurité réseau : la passerelle hotspot DOIT appartenir au sous-réseau du pool.
        // Une passerelle hors-pool (ex. 10.0.0.1/24 héritée alors que le pool est en
        // 10.10.0.0/24) crée une route connectée en double. Si elle tombe dans le
        // sous-réseau du VPN WireGuard (10.0.0.0/24), les réponses API partent par la
        // mauvaise interface et l'API du routeur devient injoignable (ECMP).
        if ($localAddress !== '' && $poolRange !== '') {
            $gwIp = $localAddress;
            $gwMask = '24';
            if (strpos($localAddress, '/') !== false) {
                [$gwIp, $gwMask] = explode('/', $localAddress, 2);
            }
            $gwIp = trim($gwIp);
            $gwMask = trim($gwMask) !== '' ? trim($gwMask) : '24';
            $derived = self::deriveGatewayFromPoolRange($poolRange);
            // Compare le /24 de la passerelle et celui du pool (préfixe des 3 octets).
            $gwPrefix = preg_match('/^(\d+\.\d+\.\d+\.)\d+$/', $gwIp, $gm) ? $gm[1] : '';
            $poolPrefix = preg_match('/^(\d+\.\d+\.\d+\.)\d+$/', $derived, $pm) ? $pm[1] : '';
            if ($derived !== '' && $poolPrefix !== '' && $gwPrefix !== $poolPrefix) {
                $localAddress = $derived . '/' . $gwMask;
                $actions[] = 'passerelle hotspot réalignée sur le pool (' . $localAddress . ')';
            }
        }

        if ($localAddress === '' && $poolRange !== '') {
            $derivedGw = self::deriveGatewayFromPoolRange($poolRange);
            if ($derivedGw !== '') {
                $localAddress = $derivedGw . '/24';
                $actions[] = 'passerelle hotspot déduite du pool (' . $localAddress . ')';
            }
        }

        $bridgePrep = self::ensureDedicatedHotspotBridge($client, $config);
        $errors = array_merge($errors, $bridgePrep['errors'] ?? []);
        $actions = array_merge($actions, $bridgePrep['actions'] ?? []);
        if (empty($bridgePrep['ok'])) {
            return ['ok' => false, 'errors' => $errors, 'actions' => $actions];
        }
        $bridgeIface = trim((string) ($bridgePrep['interface'] ?? ''));
        if ($bridgeIface !== '') {
            $interface = $bridgeIface;
        }

        if (self::isWlanInterfaceName($interface) || self::isBridgeInterface($client, $interface)) {
            $simplePrep = self::prepareSimpleWlanHotspotInterface($client, $interface);
            $errors = array_merge($errors, $simplePrep['errors'] ?? []);
            $actions = array_merge($actions, $simplePrep['actions'] ?? []);
            if (empty($simplePrep['ok'])) {
                return ['ok' => false, 'errors' => $errors, 'actions' => $actions];
            }
        }

        if ($hotspotName === '') {
            $hotspotName = 'dyrsia-' . preg_replace('/[^a-z0-9_-]/i', '', $interface);
            if ($hotspotName === 'dyrsia-') {
                $hotspotName = 'dyrsia-hotspot';
            }
        }

        $profileName = self::resolveHotspotProfileNameForSync($client, $hotspotName, $profileName);

        try {
            self::runMikrotikClientStep($client, $routerRow, 30, static function ($apiClient) use ($poolName, $poolRange) {
                self::setPool($apiClient, $poolName, $poolRange);
            });
            $actions[] = 'pool « ' . $poolName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'pool: ' . $e->getMessage();
        }

        if ($localAddress !== '') {
            try {
                self::runMikrotikClientStep($client, $routerRow, 30, static function ($apiClient) use ($interface, $localAddress) {
                    self::ensureHotspotInterfaceAddress($apiClient, $interface, $localAddress);
                });
                $actions[] = 'adresse ' . $localAddress . ' sur ' . $interface;
            } catch (Throwable $e) {
                $errors[] = 'adresse IP: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'adresse IP: ' . $e->getMessage();
            }
        }

        $useRadius = self::hotspotRadiusEnabled($config);
        $loginMethodsForProfile = $loginMethods;
        $radiusPrep = [];

        if ($useRadius) {
            $routerRow = is_array($routerRow) ? $routerRow : null;
            $radiusPrep = self::applyHotspotRadiusSetup($client, $config, $routerRow);
            if (empty($radiusPrep['ok'])) {
                $errors = array_merge($errors, $radiusPrep['errors'] ?? ['RADIUS hotspot']);
                self::refreshMikrotikClient($client, $routerRow, 30);
                foreach ($errors as $radiusErr) {
                    if (stripos((string) $radiusErr, 'FreeRADIUS indisponible') !== false) {
                        return ['ok' => false, 'errors' => $errors, 'actions' => $actions];
                    }
                }
            } else {
                $actions[] = 'RADIUS client → ' . ($radiusPrep['server_ip'] ?? '');
                $actions[] = 'NAS « ' . ($radiusPrep['nas_ip'] ?? '') . ' »';
                if (!empty($radiusPrep['flushed'])) {
                    $actions[] = 'sessions/cookies hotspot purgés';
                }
                self::refreshMikrotikClient($client, $routerRow, 30);
            }
        }

        try {
            self::runMikrotikClientStep($client, $routerRow, 30, static function ($apiClient) use (
                $profileName,
                $dnsName,
                $smtpServer,
                $dnsServer,
                $loginMethodsForProfile,
                $cookieLifetime,
                $idleTimeout,
                $useRadius,
                $localAddress
            ) {
                $hotspotAddress = '';
                $network = self::parseHotspotLocalNetwork($localAddress);
                if ($network !== null) {
                    $hotspotAddress = (string) ($network['gateway'] ?? '');
                }
                self::ensureHotspotProfileConfigured(
                    $apiClient,
                    $profileName,
                    $dnsName,
                    $smtpServer,
                    $dnsServer,
                    $loginMethodsForProfile,
                    $cookieLifetime,
                    $idleTimeout,
                    $useRadius,
                    $hotspotAddress
                );
            });
            $cookieLabel = self::hotspotLoginByIncludes($loginMethodsForProfile, 'mac-cookie')
                ? ('mac-cookie ' . $cookieLifetime)
                : ('cookie ' . $cookieLifetime);
            $actions[] = 'profil « ' . $profileName . ' »'
                . ($useRadius ? ' (use-radius=yes)' : '')
                . ', ' . $cookieLabel;
        } catch (Throwable $e) {
            $errors[] = 'profil: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'profil: ' . $e->getMessage();
        }

        if ($localAddress !== '') {
            try {
                self::runMikrotikClientStep($client, $routerRow, 30, static function ($apiClient) use ($localAddress, $profileName, $dnsServer) {
                    self::ensureHotspotNetworkEntry($apiClient, $localAddress, $profileName, $dnsServer);
                });
                $actions[] = 'réseau hotspot';
            } catch (Throwable $e) {
                $errors[] = 'réseau hotspot: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'réseau hotspot: ' . $e->getMessage();
            }
        }

        // IMPORTANT: Assurer use-ip-firewall=yes AVANT la création du serveur hotspot
        // pour que MikroTik génère les règles firewall correctement.
        // En mode incrémental (hotspot déjà déployé), ne pas retoucher le bridge :
        // ces commandes coupent souvent la socket API sans bénéfice.
        if (!$skipBridgeHardening) {
            try {
                self::refreshMikrotikClient($client, $routerRow, 20);
                $bridgeFwInterface = $interface;
                $bridgeFwResult = self::runMikrotikClientStep($client, $routerRow, 45, static function ($apiClient) use ($bridgeFwInterface) {
                    $result = self::ensureHotspotBridgeFirewall($apiClient, $bridgeFwInterface);
                    self::throwIfMikrotikResultRetriable($result);

                    return $result;
                });
                foreach ($bridgeFwResult['actions'] ?? [] as $action) {
                    $actions[] = $action;
                }
                $errors = array_merge($errors, $bridgeFwResult['errors'] ?? []);
            } catch (Throwable $e) {
                $errors[] = 'configuration bridge firewall: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'configuration bridge firewall: ' . $e->getMessage();
            }
            self::mikrotikPauseAfterRouterScript($client, $routerRow, 45);
        } else {
            $actions[] = 'bridge settings ignorés (hotspot déjà déployé)';
        }

        self::mikrotikPauseAfterRouterScript($client, $routerRow, 45);

        try {
            self::runMikrotikClientStep($client, $routerRow, 45, static function ($apiClient) use (
                $hotspotName,
                $interface,
                $profileName,
                $poolName,
                $addressPerMac
            ) {
                self::ensureHotspotServerEntry($apiClient, $hotspotName, $interface, $profileName, $poolName, $addressPerMac);
            });
            $actions[] = 'serveur « ' . $hotspotName . ' »';
        } catch (Throwable $e) {
            $errors[] = 'serveur hotspot: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'serveur hotspot: ' . $e->getMessage();
        }

        if (self::mikrotikSetupHasRetriableErrors($errors)) {
            self::mikrotikPauseAfterRouterScript($client, $routerRow, 45);
        }

        // DHCP sur le bridge/interface hotspot (obligatoire pour les clients Wi‑Fi).
        try {
            $dhcpResult = self::runMikrotikClientStep($client, $routerRow, 45, static function ($apiClient) use (
                $interface,
                $poolName,
                $localAddress,
                $hotspotName
            ) {
                return self::ensureHotspotDhcpServer($apiClient, $interface, $poolName, $localAddress, $hotspotName);
            });
            foreach ($dhcpResult['actions'] ?? [] as $action) {
                $actions[] = $action;
            }
            $errors = array_merge($errors, $dhcpResult['errors'] ?? []);
            if (!empty($dhcpResult['errors'])) {
                self::refreshMikrotikClient($client, $routerRow, 30);
                sleep(1);
            }
        } catch (Throwable $e) {
            $errors[] = 'DHCP hotspot: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'DHCP hotspot: ' . $e->getMessage();
        }

        try {
            self::refreshMikrotikClient($client, $routerRow, 30);
            $intercept = self::runMikrotikClientStep($client, $routerRow, 45, static function ($apiClient) use (
                $interface,
                $localAddress,
                $poolName,
                $hotspotName
            ) {
                return self::ensureHotspotInterceptIntegrity($apiClient, $interface, $localAddress, $poolName, $hotspotName);
            });
            foreach ($intercept['actions'] ?? [] as $action) {
                $actions[] = $action;
            }
            $errors = array_merge($errors, $intercept['errors'] ?? []);
        } catch (Throwable $e) {
            $errors[] = 'interception hotspot: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'interception hotspot: ' . $e->getMessage();
        }

        if ($masquerade) {
            try {
                self::runMikrotikClientStep($client, $routerRow, 30, static function ($apiClient) use ($interface, $localAddress) {
                    self::ensureHotspotSrcNatMasquerade($apiClient, $interface, $localAddress);
                });
                $actions[] = 'masquerade NAT';
            } catch (Throwable $e) {
                $errors[] = 'masquerade: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'masquerade: ' . $e->getMessage();
            }
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/dns/set'))
                    ->setArgument('allow-remote-requests', 'yes')
            );
            $actions[] = 'DNS allow-remote-requests=yes';
        } catch (Throwable $e) {
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
            'hotspot_name' => $hotspotName,
            'interface' => $interface,
            'radius_secret' => ($useRadius && !empty($radiusPrep['secret'])) ? $radiusPrep['secret'] : '',
        ];
    }

    /**
     * Mode simple (sans trunk) : wlan1 directement, pas vlan10-hotspot.
     */
    public static function resolveSimpleHotspotInterface($client, array $config)
    {
        $manual = trim((string) ($config['hotspot_interface'] ?? ''));
        if ($manual !== '' && !self::isVlanInterfaceName($manual)) {
            return $manual;
        }

        $bridge = trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot'));
        if ($bridge === '' || self::isVlanInterfaceName($bridge)) {
            $bridge = 'bridge-hotspot';
        }
        if (self::isBridgeInterface($client, $bridge)) {
            return $bridge;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/wireless/print'))
                    ->setArgument('.proplist', 'name,disabled')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('disabled') === 'true') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $manual !== '' && !self::isVlanInterfaceName($manual) ? $manual : 'wlan1';
    }

    /**
     * Crée bridge-hotspot (ou hotspot_interface) et y place les ports de hotspot_bridge_ports.
     *
     * @return array{ok: bool, interface: string, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureDedicatedHotspotBridge($client, array $config)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'interface' => '', 'errors' => [], 'actions' => []];
        }

        $bridgeName = trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot'));
        if ($bridgeName === '') {
            $bridgeName = 'bridge-hotspot';
        }

        // Interface physique seule (wlan1) : pas de bridge dédié.
        if (self::isWlanInterfaceName($bridgeName) || self::isVlanInterfaceName($bridgeName)) {
            return ['ok' => true, 'interface' => $bridgeName, 'errors' => [], 'actions' => []];
        }

        $portsRaw = trim((string) ($config['hotspot_bridge_ports'] ?? ''));
        $ports = [];
        if ($portsRaw !== '') {
            foreach (preg_split('/[\s,;]+/', $portsRaw) as $port) {
                $port = trim((string) $port);
                if ($port !== '' && !in_array($port, $ports, true)) {
                    $ports[] = $port;
                }
            }
        }

        $errors = [];
        $actions = [];

        try {
            $bridgeId = self::routerEntityId($client, '/interface/bridge', 'name', $bridgeName);
            if ($bridgeId === null) {
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/add'))
                        ->setArgument('name', $bridgeName)
                        ->setArgument('comment', 'DYRSIA hotspot bridge')
                );
                $actions[] = 'bridge « ' . $bridgeName . ' » créé';
                $bridgeId = self::routerEntityId($client, '/interface/bridge', 'name', $bridgeName);
            } else {
                $actions[] = 'bridge « ' . $bridgeName . ' » OK';
            }

            if ($bridgeId !== null) {
                try {
                    $client->sendSync(
                        (new RouterOS\Request('/interface/bridge/set'))
                            ->setArgument('numbers', $bridgeId)
                            ->setArgument('vlan-filtering', 'no')
                            ->setArgument('fast-forward', 'no')
                            ->setArgument('disabled', 'no')
                    );
                } catch (Throwable $e) {
                } catch (Exception $e) {
                }
            }

            foreach ($ports as $port) {
                $membership = self::ensureBridgePortMembership($client, $bridgeName, $port);
                if (!empty($membership['ok']) && !empty($membership['moved'])) {
                    $actions[] = 'port « ' . $port . ' » placé sur « ' . $bridgeName . ' »';
                } elseif (!empty($membership['ok'])) {
                    $actions[] = 'port « ' . $port . ' » déjà sur « ' . $bridgeName . ' »';
                } elseif (!empty($membership['error'])) {
                    $errors[] = 'port « ' . $port . ' » : ' . $membership['error'];
                }

                // Désactiver HW offload sur les ports hotspot (sinon DHCP/captive cassés).
                try {
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/interface/bridge/port/print'))
                            ->setArgument('.proplist', '.id,interface,bridge')
                            ->setQuery(RouterOS\Query::where('interface', $port))
                    ) as $row) {
                        if ($row->getType() === 'trap') {
                            continue;
                        }
                        if ((string) $row->getProperty('bridge') !== $bridgeName) {
                            continue;
                        }
                        $portId = $row->getProperty('.id');
                        if ($portId === null || $portId === '') {
                            continue;
                        }
                        $client->sendSync(
                            (new RouterOS\Request('/interface/bridge/port/set'))
                                ->setArgument('numbers', $portId)
                                ->setArgument('hw', 'no')
                                ->setArgument('disabled', 'no')
                        );
                    }
                } catch (Throwable $e) {
                } catch (Exception $e) {
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'bridge hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'bridge hotspot : ' . $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'interface' => $bridgeName,
            'errors' => $errors,
            'actions' => $actions,
        ];
    }

    /**
     * Prépare le mode simple : bridge plat (sans VLAN) ou wlan direct.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function prepareSimpleWlanHotspotInterface($client, $targetInterface)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $targetInterface = trim((string) $targetInterface);
        if ($targetInterface === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/vlan/print'))
                    ->setArgument('.proplist', '.id,name,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($name === '' || stripos($name, 'hotspot') === false) {
                    continue;
                }
                $vlanId = $row->getProperty('.id');
                if ($vlanId === null || $vlanId === '') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/interface/vlan/set'))
                        ->setArgument('numbers', $vlanId)
                        ->setArgument('disabled', 'yes')
                );
                $actions[] = 'interface VLAN « ' . $name . ' » désactivée (mode simple)';
            }
        } catch (Throwable $e) {
            $errors[] = 'vlan hotspot : ' . $e->getMessage();
        }

        if (self::isBridgeInterface($client, $targetInterface)) {
            try {
                $bridgeId = self::routerEntityId($client, '/interface/bridge', 'name', $targetInterface);
                if ($bridgeId !== null) {
                    $client->sendSync(
                        (new RouterOS\Request('/interface/bridge/set'))
                            ->setArgument('numbers', $bridgeId)
                            ->setArgument('vlan-filtering', 'no')
                            ->setArgument('fast-forward', 'no')
                    );
                    $actions[] = 'bridge « ' . $targetInterface . ' » : vlan-filtering=no';
                }
            } catch (Throwable $e) {
                $errors[] = 'bridge : ' . $e->getMessage();
            }

            global $config;
            $wlanPorts = self::listActiveWirelessInterfaceNames($client);

            if ($wlanPorts === []) {
                $wlanPorts = array_values(array_filter(
                    self::resolveHotspotBridgePorts(is_array($config) ? $config : []),
                    static function ($port) {
                        return self::isWlanInterfaceName($port);
                    }
                ));
            }

            foreach ($wlanPorts as $wlanInterface) {
                $wifiRos7 = self::routerEntityId($client, '/interface/wifi', 'name', $wlanInterface) !== null;

                if ($wifiRos7) {
                    $dp = self::ensureRouterWifi7DatapathForHotspotBridge($client, $targetInterface, $wlanInterface);
                    $actions = array_merge($actions, $dp['actions'] ?? []);
                    if (!empty($dp['errors'])) {
                        $errors = array_merge($errors, $dp['errors']);
                    }
                    continue;
                }

                $alreadyMember = false;
                try {
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/interface/bridge/port/print'))
                            ->setArgument('.proplist', '.id,interface,bridge')
                    ) as $row) {
                        if ($row->getType() === 'trap') {
                            continue;
                        }
                        if (strcasecmp(trim((string) $row->getProperty('interface')), $wlanInterface) !== 0) {
                            continue;
                        }
                        if ((string) $row->getProperty('bridge') === $targetInterface) {
                            $alreadyMember = true;
                            break;
                        }
                        $portId = $row->getProperty('.id');
                        if ($portId !== null && $portId !== '') {
                            $client->sendSync(
                                (new RouterOS\Request('/interface/bridge/port/remove'))
                                    ->setArgument('numbers', $portId)
                            );
                        }
                    }
                } catch (Throwable $e) {
                    $errors[] = 'lecture bridge port : ' . $e->getMessage();
                }

                try {
                    if (self::configureRouterWifiInterfaceForHotspotBridge($client, $wlanInterface)) {
                        $actions[] = $wlanInterface . ' : Wi‑Fi AP (mode bridge hotspot)';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'wireless ' . $wlanInterface . ' : ' . $e->getMessage();
                }

                if (!$alreadyMember) {
                    try {
                        $client->sendSync(
                            (new RouterOS\Request('/interface/bridge/port/add'))
                                ->setArgument('bridge', $targetInterface)
                                ->setArgument('interface', $wlanInterface)
                                ->setArgument('comment', 'DYRSIA hotspot simple')
                        );
                        $actions[] = $wlanInterface . ' ajouté au bridge « ' . $targetInterface . ' »';
                    } catch (Throwable $e) {
                        $errors[] = 'ajout ' . $wlanInterface . ' au bridge : ' . $e->getMessage();
                    }
                }

                try {
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/interface/bridge/port/print'))
                            ->setArgument('.proplist', '.id,interface,bridge')
                            ->setQuery(RouterOS\Query::where('interface', $wlanInterface))
                    ) as $row) {
                        if ($row->getType() === 'trap') {
                            continue;
                        }
                        if ((string) $row->getProperty('bridge') !== $targetInterface) {
                            continue;
                        }
                        $portId = $row->getProperty('.id');
                        if ($portId === null || $portId === '') {
                            continue;
                        }
                        $client->sendSync(
                            (new RouterOS\Request('/interface/bridge/port/set'))
                                ->setArgument('numbers', $portId)
                                ->setArgument('hw', 'no')
                                ->setArgument('disabled', 'no')
                        );
                        $actions[] = $wlanInterface . ' : hw=no sur « ' . $targetInterface . ' »';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'bridge port hw=no ' . $wlanInterface . ' : ' . $e->getMessage();
                }
            }

            return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
        }

        if (!self::isWlanInterfaceName($targetInterface)) {
            return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
        }

        if (self::routerEntityId($client, '/interface/wifi', 'name', $targetInterface) !== null) {
            $bridgeForDp = '';
            try {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/interface/bridge/port/print'))
                        ->setArgument('.proplist', 'bridge')
                        ->setQuery(RouterOS\Query::where('interface', $targetInterface))
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $bridgeForDp = trim((string) $row->getProperty('bridge'));
                    break;
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            if ($bridgeForDp !== '') {
                $dp = self::ensureRouterWifi7DatapathForHotspotBridge($client, $bridgeForDp, $targetInterface);
                $actions = array_merge($actions, $dp['actions'] ?? []);
                if (!empty($dp['errors'])) {
                    $errors = array_merge($errors, $dp['errors']);
                }
            } else {
                try {
                    if (self::configureRouterWifiInterfaceForHotspotBridge($client, $targetInterface)) {
                        $actions[] = $targetInterface . ' : Wi‑Fi AP (mode wlan direct)';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'wireless : ' . $e->getMessage();
                }
            }

            return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', '.id,interface,bridge')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (strcasecmp(trim((string) $row->getProperty('interface')), $targetInterface) !== 0) {
                    continue;
                }
                $portId = $row->getProperty('.id');
                if ($portId === null || $portId === '') {
                    continue;
                }
                $bridge = trim((string) $row->getProperty('bridge'));
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/port/remove'))
                        ->setArgument('numbers', $portId)
                );
                $actions[] = $targetInterface . ' retiré du bridge « ' . $bridge . ' » (wlan direct)';
            }
        } catch (Throwable $e) {
            $errors[] = 'retrait wlan du bridge : ' . $e->getMessage();
        }

        try {
            if (self::configureRouterWifiInterfaceForHotspotBridge($client, $targetInterface)) {
                $actions[] = $targetInterface . ' : Wi‑Fi AP (mode wlan direct)';
            }
        } catch (Throwable $e) {
            $errors[] = 'wireless : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function isWlanInterfaceName($name)
    {
        return preg_match('/^(wlan|wifi)/i', trim((string) $name)) === 1;
    }

    /**
     * RouterOS 7 : /interface/wifi (L009, hAP ax). Anciens : /interface/wireless ou wifiwave2.
     */
    private static function configureRouterWifiInterfaceForHotspotBridge($client, $wlanInterface)
    {
        $wlanInterface = trim((string) $wlanInterface);
        if ($wlanInterface === '') {
            return false;
        }

        $configured = false;
        $wifiId = self::routerEntityId($client, '/interface/wifi', 'name', $wlanInterface);
        if ($wifiId !== null) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wifi/set'))
                        ->setArgument('numbers', $wifiId)
                        ->setArgument('disabled', 'no')
                        ->setArgument('configuration.mode', 'ap')
                );
                $configured = true;
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        $wlanId = self::routerEntityId($client, '/interface/wireless', 'name', $wlanInterface);
        if ($wlanId !== null) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wireless/set'))
                        ->setArgument('numbers', $wlanId)
                        ->setArgument('disabled', 'no')
                        ->setArgument('mode', 'ap-bridge')
                        ->setArgument('bridge-mode', 'disabled')
                        ->setArgument('vlan-mode', 'no-tag')
                );
                $configured = true;
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        return $configured;
    }

    /**
     * RouterOS 7 (L009, ax) : le Wi‑Fi `/interface/wifi` doit utiliser un datapath vers le bridge hotspot
     * (sinon les clients s'associent mais ne reçoivent pas DHCP / hotspot).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function ensureRouterWifi7DatapathForHotspotBridge($client, $bridgeName, $wifiInterface)
    {
        $bridgeName = trim((string) $bridgeName);
        $wifiInterface = trim((string) $wifiInterface);
        if ($bridgeName === '' || $wifiInterface === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $wifiId = self::routerEntityId($client, '/interface/wifi', 'name', $wifiInterface);
        if ($wifiId === null) {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $datapathName = 'dyrsia-hotspot-dp';
        $configName = 'dyrsia-hotspot-ap';

        $ssid = '';
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/wifi/print'))
                    ->setArgument('.proplist', 'name,configuration.ssid,configuration')
                    ->setQuery(RouterOS\Query::where('name', $wifiInterface))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $ssid = trim((string) $row->getProperty('configuration.ssid'));
                if ($ssid === '') {
                    $ssid = trim((string) $row->getProperty('configuration.ssid'));
                }
                break;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
        if ($ssid === '') {
            $ssid = 'WIFI ZONE';
        }

        try {
            $dpId = self::routerEntityId($client, '/interface/wifi/datapath', 'name', $datapathName);
            if ($dpId === null) {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wifi/datapath/add'))
                        ->setArgument('name', $datapathName)
                        ->setArgument('bridge', $bridgeName)
                );
                $actions[] = 'Wi‑Fi datapath « ' . $datapathName . ' » → « ' . $bridgeName . ' »';
            } else {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wifi/datapath/set'))
                        ->setArgument('numbers', $dpId)
                        ->setArgument('bridge', $bridgeName)
                );
            }
        } catch (Throwable $e) {
            $errors[] = 'wifi datapath : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'wifi datapath : ' . $e->getMessage();
        }

        try {
            $cfgId = self::routerEntityId($client, '/interface/wifi/configuration', 'name', $configName);
            if ($cfgId === null) {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wifi/configuration/add'))
                        ->setArgument('name', $configName)
                        ->setArgument('mode', 'ap')
                        ->setArgument('ssid', $ssid)
                        ->setArgument('datapath', $datapathName)
                );
                $actions[] = 'Wi‑Fi configuration « ' . $configName . ' » (AP, SSID « ' . $ssid . ' »)';
            } else {
                $client->sendSync(
                    (new RouterOS\Request('/interface/wifi/configuration/set'))
                        ->setArgument('numbers', $cfgId)
                        ->setArgument('mode', 'ap')
                        ->setArgument('ssid', $ssid)
                        ->setArgument('datapath', $datapathName)
                );
            }

            $client->sendSync(
                (new RouterOS\Request('/interface/wifi/set'))
                    ->setArgument('numbers', $wifiId)
                    ->setArgument('configuration', $configName)
                    ->setArgument('disabled', 'no')
            );
            $actions[] = $wifiInterface . ' : configuration « ' . $configName . ' » (datapath bridge)';
        } catch (Throwable $e) {
            $errors[] = 'wifi configuration : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'wifi configuration : ' . $e->getMessage();
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', '.id,interface,bridge')
                    ->setQuery(RouterOS\Query::where('interface', $wifiInterface))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $portId = $row->getProperty('.id');
                if ($portId === null || $portId === '') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/port/remove'))
                        ->setArgument('numbers', $portId)
                );
                $actions[] = $wifiInterface . ' retiré du bridge (datapath RouterOS 7)';
            }
        } catch (Throwable $e) {
            $errors[] = 'retrait bridge port wifi : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'retrait bridge port wifi : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function isVlanInterfaceName($name)
    {
        return preg_match('/^vlan/i', trim((string) $name)) === 1;
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

        $targetIp = explode('/', $localAddress, 2)[0];
        foreach ($client->sendSync(
            (new RouterOS\Request('/ip/address/print'))
                ->setArgument('.proplist', '.id,address,interface')
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $iface = trim((string) $row->getProperty('interface'));
            if ($iface === '' || strcasecmp($iface, $interface) === 0) {
                continue;
            }
            $address = trim((string) $row->getProperty('address'));
            if ($address === $localAddress || strpos($address, $targetIp . '/') === 0) {
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/ip/address/remove'))
                            ->setArgument('numbers', $id)
                    );
                }
            }
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
        $allowed = ['http-pap', 'mac-cookie', 'cookie', 'https', 'mac', 'trial'];
        $methods = array_values(array_unique(array_filter(array_map('trim', explode(',', strtolower((string) $loginMethods))))));
        $normalized = [];
        foreach ($methods as $method) {
            // CHAP volontairement ignoré (portail captif = mot de passe clair / PAP).
            if ($method === 'chap' || $method === 'http-chap') {
                continue;
            }
            if ($method === 'cookie') {
                $method = 'mac-cookie';
            }
            if (in_array($method, $allowed, true) && !in_array($method, $normalized, true)) {
                $normalized[] = $method;
            }
        }
        if (empty($normalized) || !in_array('http-pap', $normalized, true)) {
            array_unshift($normalized, 'http-pap');
            $normalized = array_values(array_unique($normalized));
        }
        if (count($normalized) === 1 && $normalized[0] === 'http-pap') {
            $normalized[] = 'mac-cookie';
        }

        return self::orderHotspotLoginByMethods($normalized);
    }

    /**
     * Auth portail captif DYRSIA : PAP + cookie, sans http-chap
     * (sinon MikroTik exige une réponse CHAP JS et affiche
     * « web browser did not send challenge response »).
     */
    public static function captivePortalLoginBy()
    {
        return 'http-pap,mac-cookie';
    }

    /**
     * Ordre login-by : HTTP PAP en premier, puis MAC cookie.
     *
     * @param array<int, string> $methods
     */
    private static function orderHotspotLoginByMethods(array $methods)
    {
        $order = ['http-pap', 'mac-cookie'];
        $picked = [];
        foreach ($order as $method) {
            if (in_array($method, $methods, true)) {
                $picked[] = $method;
            }
        }
        foreach ($methods as $method) {
            if ($method === 'http-chap' || $method === 'chap') {
                continue;
            }
            if (!in_array($method, $picked, true)) {
                $picked[] = $method;
            }
        }

        return implode(',', $picked);
    }

    /**
     * Hotspot + RADIUS : PAP uniquement (pas de CHAP).
     */
    private static function normalizeHotspotLoginByForRadius($loginMethods)
    {
        return self::captivePortalLoginBy();
    }

    public static function hotspotRadiusEnabled(array $configLocal)
    {
        global $config;
        $merged = is_array($config) ? array_merge($config, $configLocal) : $configLocal;
        if (array_key_exists('hotspot_use_radius', $merged)) {
            return (string) $merged['hotspot_use_radius'] === '1';
        }

        return true;
    }

    /**
     * Entrée NAS FreeRADIUS + client /radius hotspot sur le MikroTik.
     *
     * @return array{ok: bool, errors?: array<int, string>, server_ip?: string, nas_ip?: string, secret?: string, flushed?: bool}
     */
    public static function applyHotspotRadiusSetup($client, array $config, $routerRow = null)
    {
        if (!self::hotspotRadiusEnabled($config)) {
            return ['ok' => true, 'skipped' => true];
        }

        $apiUrl = trim((string) ($config['hotspot_api_url'] ?? (defined('APP_URL') ? APP_URL : '')));
        $serverIp = self::resolveAppBackendIpv4($apiUrl);
        if ($serverIp === null || $serverIp === '') {
            return ['ok' => false, 'errors' => ['IP serveur RADIUS introuvable (Hotspot API URL doit être une IPv4 joignable, ex. http://10.0.0.2).']];
        }

        $routerName = trim((string) ($config['hotspot_login_router'] ?? ''));
        $routerIp = '';
        if (is_array($routerRow)) {
            $routerName = $routerName !== '' ? $routerName : trim((string) ($routerRow['name'] ?? ''));
            $routerIp = self::parseEndpoint((string) ($routerRow['ip_address'] ?? ''))['host'];
        }
        if ($routerIp === '' && $routerName !== '') {
            $row = self::info($routerName);
            if ($row) {
                $routerIp = self::parseEndpoint((string) ($row['ip_address'] ?? ''))['host'];
            }
        }
        if ($routerIp === '') {
            return ['ok' => false, 'errors' => ['Routeur hotspot introuvable pour l\'entrée NAS RADIUS.']];
        }

        $secret = trim((string) ($config['hotspot_radius_secret'] ?? ''));
        $nasResult = self::ensureHotspotNasRecord($routerName, $routerIp, $secret);
        if (empty($nasResult['ok'])) {
            return $nasResult;
        }
        $secret = (string) ($nasResult['secret'] ?? '');

        $errors = [];
        try {
            self::ensureHotspotRadiusClient($client, $serverIp, $secret);
        } catch (Throwable $e) {
            $errors[] = 'client RADIUS MikroTik : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'client RADIUS MikroTik : ' . $e->getMessage();
        }

        $flushed = false;
        try {
            self::flushHotspotAuthorizationState($client);
            $flushed = true;
        } catch (Throwable $e) {
            $errors[] = 'purge sessions hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'purge sessions hotspot : ' . $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'server_ip' => $serverIp,
            'nas_ip' => $routerIp,
            'secret' => $secret,
            'flushed' => $flushed,
        ];
    }

    /**
     * @return array{ok: bool, errors?: array<int, string>, secret?: string, nas_ip?: string}
     */
    public static function ensureHotspotNasRecord($routerName, $routerNasIp, $secret = '')
    {
        if (!function_exists('wifizone_ensure_radius_orm') || !wifizone_ensure_radius_orm()) {
            return ['ok' => false, 'errors' => ['FreeRADIUS indisponible : importez install/radius.sql (tables nas, radcheck…) et vérifiez radius_host / radius_user dans config.php ou .env (RADIUS_HOST, RADIUS_DATABASE).']];
        }

        $routerName = trim((string) $routerName);
        $routerNasIp = trim((string) $routerNasIp);
        if ($routerNasIp === '' || !filter_var($routerNasIp, FILTER_VALIDATE_IP)) {
            return ['ok' => false, 'errors' => ['IP NAS routeur invalide : ' . $routerNasIp]];
        }

        $secret = trim((string) $secret);
        $nas = null;
        if ($routerName !== '') {
            $nas = ORM::for_table('nas', 'radius')->where('routers', $routerName)->find_one();
        }
        if (!$nas) {
            $nas = ORM::for_table('nas', 'radius')->where('nasname', $routerNasIp)->find_one();
        }

        if ($secret === '') {
            if ($nas && trim((string) ($nas['secret'] ?? '')) !== '') {
                $secret = trim((string) $nas['secret']);
            } else {
                try {
                    $secret = bin2hex(random_bytes(16));
                } catch (Throwable $e) {
                    $secret = sha1($routerName . $routerNasIp . microtime(true));
                }
            }
        }

        if ($nas) {
            $nas->nasname = $routerNasIp;
            if ($routerName !== '') {
                $nas->shortname = trim((string) ($nas['shortname'] ?? '')) !== '' ? $nas->shortname : $routerName;
                $nas->routers = $routerName;
            }
            $nas->secret = $secret;
            if (trim((string) ($nas['description'] ?? '')) === '') {
                $nas->description = 'DYRSIA Hotspot Setup';
            }
            $nas->type = trim((string) ($nas['type'] ?? '')) !== '' ? $nas->type : 'other';
            $nas->save();
        } else {
            $nas = ORM::for_table('nas', 'radius')->create();
            $nas->nasname = $routerNasIp;
            $nas->shortname = $routerName !== '' ? $routerName : $routerNasIp;
            $nas->type = 'other';
            $nas->ports = null;
            $nas->secret = $secret;
            $nas->server = null;
            $nas->community = null;
            $nas->description = 'DYRSIA Hotspot Setup';
            $nas->routers = $routerName;
            $nas->save();
        }

        return ['ok' => true, 'secret' => $secret, 'nas_ip' => $routerNasIp];
    }

    /**
     * Supprime l'entrée NAS FreeRADIUS liée à un routeur (nom et/ou IP).
     */
    public static function removeHotspotNasRecord($routerName, $routerNasIp = null)
    {
        if (!function_exists('wifizone_ensure_radius_orm') || !wifizone_ensure_radius_orm()) {
            return 0;
        }

        $removed = 0;
        $routerName = trim((string) $routerName);
        $routerNasIp = trim((string) $routerNasIp);
        if ($routerNasIp !== '' && strpos($routerNasIp, ':') !== false) {
            $routerNasIp = self::parseEndpoint($routerNasIp)['host'];
        }

        if ($routerName !== '') {
            foreach (ORM::for_table('nas', 'radius')->where('routers', $routerName)->find_many() as $nas) {
                $nas->delete();
                $removed++;
            }
        }
        if ($routerNasIp !== '' && filter_var($routerNasIp, FILTER_VALIDATE_IP)) {
            foreach (ORM::for_table('nas', 'radius')->where('nasname', $routerNasIp)->find_many() as $nas) {
                $nas->delete();
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Renomme la référence NAS quand le routeur MikroTik est renommé dans DYRSIA.
     */
    public static function renameHotspotNasRouter($oldRouterName, $newRouterName)
    {
        if (!function_exists('wifizone_ensure_radius_orm') || !wifizone_ensure_radius_orm()) {
            return 0;
        }

        $oldRouterName = trim((string) $oldRouterName);
        $newRouterName = trim((string) $newRouterName);
        if ($oldRouterName === '' || $newRouterName === '' || $oldRouterName === $newRouterName) {
            return 0;
        }

        $updated = 0;
        foreach (ORM::for_table('nas', 'radius')->where('routers', $oldRouterName)->find_many() as $nas) {
            $nas->routers = $newRouterName;
            if (trim((string) ($nas['shortname'] ?? '')) === $oldRouterName) {
                $nas->shortname = $newRouterName;
            }
            $nas->save();
            $updated++;
        }

        return $updated;
    }

    public static function ensureHotspotRadiusClient($client, $serverIp, $secret)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return;
        }

        $serverIp = trim((string) $serverIp);
        $secret = trim((string) $secret);
        if ($serverIp === '' || $secret === '') {
            throw new Exception('Serveur RADIUS ou secret manquant');
        }

        $existingId = null;
        $existingSecret = '';
        foreach ($client->sendSync(
            (new RouterOS\Request('/radius/print'))
                ->setArgument('.proplist', '.id,service,address,secret')
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            if ((string) $row->getProperty('address') !== $serverIp) {
                continue;
            }
            $services = array_map('trim', explode(',', strtolower((string) $row->getProperty('service'))));
            if (!in_array('hotspot', $services, true) && (string) $row->getProperty('service') !== '') {
                continue;
            }
            $existingId = (string) $row->getProperty('.id');
            $existingSecret = (string) $row->getProperty('secret');
            break;
        }

        if ($existingId !== null && $existingId !== '' && $existingSecret === $secret) {
            return;
        }

        $request = ($existingId !== null && $existingId !== '')
            ? (new RouterOS\Request('/radius/set'))->setArgument('numbers', $existingId)
            : new RouterOS\Request('/radius/add');

        $client->sendSync(
            $request
                ->setArgument('service', 'hotspot')
                ->setArgument('address', $serverIp)
                ->setArgument('secret', $secret)
                ->setArgument('timeout', '3000ms')
                ->setArgument('comment', 'DYRSIA Hotspot Setup')
        );
    }

    /**
     * @return array<int, string>
     */
    private static function collectHotspotSessionIps($client, $username = '')
    {
        $ips = [];
        $username = trim((string) $username);

        try {
            $activeRequest = (new RouterOS\Request('/ip/hotspot/active/print'))
                ->setArgument('.proplist', 'user,address');
            if ($username !== '') {
                $activeRequest->setQuery(RouterOS\Query::where('user', $username));
            }
            foreach ($client->sendSync($activeRequest) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $ip = trim((string) $row->getProperty('address'));
                if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[$ip] = true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        try {
            $hostRequest = (new RouterOS\Request('/ip/hotspot/host/print'))
                ->setArgument('.proplist', 'address,to-address,authorized');
            foreach ($client->sendSync($hostRequest) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $authorized = strtolower(trim((string) $row->getProperty('authorized')));
                if ($authorized !== 'true' && $authorized !== 'yes') {
                    continue;
                }
                $ip = trim((string) $row->getProperty('address'));
                if ($ip === '') {
                    $ip = trim((string) $row->getProperty('to-address'));
                }
                if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[$ip] = true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return array_keys($ips);
    }

    /**
     * Déconnecte un client hotspot : active, cookies, hôtes autorisés + purge conntrack.
     *
     * @param array<int, string> $extraIps
     * @return array{ok: bool, ips: array<int, string>, enforced: int}
     */
    public static function disconnectHotspotUser($client, $username, array $extraIps = [])
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'ips' => [], 'enforced' => 0];
        }

        $username = trim((string) $username);
        $ips = [];
        $macs = [];
        foreach ($extraIps as $ip) {
            $ip = trim((string) $ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $ips[$ip] = true;
            }
        }

        try {
            $activeRequest = (new RouterOS\Request('/ip/hotspot/active/print'))
                ->setArgument('.proplist', '.id,user,address,mac-address');
            if ($username !== '') {
                $activeRequest->setQuery(RouterOS\Query::where('user', $username));
            }
            foreach ($client->sendSync($activeRequest) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ($username !== '' && strcasecmp(trim((string) $row->getProperty('user')), $username) !== 0) {
                    continue;
                }
                $ip = trim((string) $row->getProperty('address'));
                $mac = trim((string) $row->getProperty('mac-address'));
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
                if ($mac !== '') {
                    $macs[$mac] = true;
                }
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/ip/hotspot/active/remove'))
                            ->setArgument('numbers', $id)
                    );
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        if ($username !== '') {
            try {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/cookie/print'))
                        ->setArgument('.proplist', '.id,mac-address')
                        ->setQuery(RouterOS\Query::where('user', $username))
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $mac = trim((string) $row->getProperty('mac-address'));
                    if ($mac !== '') {
                        $macs[$mac] = true;
                    }
                    $id = $row->getProperty('.id');
                    if ($id !== null && $id !== '') {
                        $client->sendSync(
                            (new RouterOS\Request('/ip/hotspot/cookie/remove'))
                                ->setArgument('numbers', $id)
                        );
                    }
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        $lines = [];
        if ($username !== '') {
            $escapedUser = str_replace(['\\', '"'], '', $username);
            $lines[] = '/ip hotspot active remove [find user="' . $escapedUser . '"]';
            $lines[] = '/ip hotspot cookie remove [find user="' . $escapedUser . '"]';
        }
        foreach (array_keys($ips) as $ip) {
            $safeIp = str_replace('"', '', $ip);
            $lines[] = '/ip hotspot host remove [find address="' . $safeIp . '"]';
        }
        foreach (array_keys($macs) as $mac) {
            $safeMac = str_replace('"', '', $mac);
            $lines[] = '/ip hotspot host remove [find mac-address="' . $safeMac . '"]';
            $lines[] = '/ip hotspot cookie remove [find mac-address="' . $safeMac . '"]';
        }
        if ($lines !== []) {
            self::runRouterOneShotScript($client, 'dyrsia_hs_disc', implode("\n", $lines));
        }

        $ipList = array_keys($ips);
        if ($ipList !== []) {
            self::flushConnectionTrackingForIps($client, $ipList);
        }

        return ['ok' => true, 'ips' => $ipList, 'enforced' => count($ipList)];
    }

    /**
     * Hôtes autorisés sans session active (ex. mac-cookie / conntrack résiduel après expiration RADIUS).
     *
     * @return array{ok: bool, enforced: int}
     */
    public static function sweepOrphanHotspotSessions($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'enforced' => 0];
        }

        $activeIps = [];
        $activeMacs = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/active/print'))
                    ->setArgument('.proplist', 'address,mac-address')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $ip = trim((string) $row->getProperty('address'));
                $mac = strtolower(trim((string) $row->getProperty('mac-address')));
                if ($ip !== '') {
                    $activeIps[$ip] = true;
                }
                if ($mac !== '') {
                    $activeMacs[$mac] = true;
                }
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'enforced' => 0];
        } catch (Exception $e) {
            return ['ok' => false, 'enforced' => 0];
        }

        $flushIps = [];
        $lines = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/host/print'))
                    ->setArgument('.proplist', 'mac-address,address,to-address,authorized')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $authorized = strtolower(trim((string) $row->getProperty('authorized')));
                if ($authorized !== 'true' && $authorized !== 'yes') {
                    continue;
                }
                $mac = strtolower(trim((string) $row->getProperty('mac-address')));
                $ip = trim((string) $row->getProperty('address'));
                if ($ip === '') {
                    $ip = trim((string) $row->getProperty('to-address'));
                }
                if ($mac !== '' && isset($activeMacs[$mac])) {
                    continue;
                }
                if ($ip !== '' && isset($activeIps[$ip])) {
                    continue;
                }
                if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $flushIps[$ip] = true;
                }
                if ($mac !== '') {
                    $safeMac = str_replace('"', '', $mac);
                    $lines[] = '/ip hotspot host remove [find mac-address="' . $safeMac . '"]';
                    $lines[] = '/ip hotspot cookie remove [find mac-address="' . $safeMac . '"]';
                }
                if ($ip !== '') {
                    $safeIp = str_replace('"', '', $ip);
                    $lines[] = '/ip hotspot host remove [find address="' . $safeIp . '"]';
                }
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'enforced' => 0];
        } catch (Exception $e) {
            return ['ok' => false, 'enforced' => 0];
        }

        if ($lines !== []) {
            self::runRouterOneShotScript($client, 'dyrsia_hs_sweep', implode("\n", array_unique($lines)));
        }
        $ipList = array_keys($flushIps);
        if ($ipList !== []) {
            self::flushConnectionTrackingForIps($client, $ipList);
        }

        return ['ok' => true, 'enforced' => count($ipList)];
    }

    /**
     * @param array<int, string> $extraIps
     */
    public static function disconnectHotspotUserOnRouter($routerName, $username, array $extraIps = [])
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        }

        $routerName = trim((string) $routerName);
        $username = trim((string) $username);
        if ($username === '') {
            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        }

        if ($routerName === '' || strcasecmp($routerName, 'radius') === 0) {
            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('username', $username)
                ->order_by_desc('id')
                ->find_one();
            $routerName = trim((string) ($recharge['routers'] ?? ''));
        }
        if ($routerName === '' || strcasecmp($routerName, 'radius') === 0) {
            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        }

        $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if (!$router) {
            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        }

        try {
            $client = self::getClient(
                $router['ip_address'],
                $router['username'],
                self::routerPassword($router['password']),
                30
            );
            if (!$client) {
                return ['ok' => false, 'ips' => [], 'enforced' => 0];
            }
            $result = self::disconnectHotspotUser($client, $username, $extraIps);
            self::sweepOrphanHotspotSessions($client);

            return $result;
        } catch (Throwable $e) {
            _log('[Hotspot disconnect] ' . $routerName . ' / ' . $username . ': ' . $e->getMessage());

            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        } catch (Exception $e) {
            _log('[Hotspot disconnect] ' . $routerName . ' / ' . $username . ': ' . $e->getMessage());

            return ['ok' => false, 'ips' => [], 'enforced' => 0];
        }
    }

    /**
     * Renforce les déconnexions hotspot expirées (sessions fantômes + conntrack), comme PPPoE.
     */
    public static function reinforceExpiredHotspotOnAllRouters()
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $routerUsers = [];
        foreach (ORM::for_table('tbl_user_recharges')
            ->where_raw("LOWER(type) = 'hotspot'")
            ->where_raw("(status = 'off' OR CONCAT(expiration, ' ', time) <= ?)", [$now])
            ->find_many() as $row) {
            $routerName = trim((string) ($row['routers'] ?? ''));
            $username = trim((string) ($row['username'] ?? ''));
            if ($routerName === '' || strcasecmp($routerName, 'radius') === 0 || $username === '') {
                continue;
            }
            $routerUsers[$routerName][$username] = true;
        }

        $total = 0;
        foreach ($routerUsers as $routerName => $users) {
            $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
            if (!$router) {
                continue;
            }
            try {
                $client = self::getClient(
                    $router['ip_address'],
                    $router['username'],
                    self::routerPassword($router['password']),
                    30
                );
                if (!$client) {
                    continue;
                }
                foreach (array_keys($users) as $username) {
                    $result = self::disconnectHotspotUser($client, $username);
                    $total += (int) ($result['enforced'] ?? 0);
                }
                $sweep = self::sweepOrphanHotspotSessions($client);
                $total += (int) ($sweep['enforced'] ?? 0);
            } catch (Throwable $e) {
                _log('[Hotspot expire enforce] ' . $routerName . ': ' . $e->getMessage());
            } catch (Exception $e) {
                _log('[Hotspot expire enforce] ' . $routerName . ': ' . $e->getMessage());
            }
        }

        return $total;
    }

    public static function flushHotspotAuthorizationState($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return;
        }

        $ips = self::collectHotspotSessionIps($client);
        self::runRouterOneShotScript(
            $client,
            'dyrsia_flush_hs_auth',
            "/ip hotspot active remove [find]\n/ip hotspot cookie remove [find]\n/ip hotspot host remove [find authorized=yes]"
        );
        if ($ips !== []) {
            self::flushConnectionTrackingForIps($client, $ips);
        }
    }

    /**
     * Profil réellement utilisé par le serveur hotspot sur le routeur (ex. Dyrsia-hotspot).
     */
    public static function getHotspotServerProfileName($client, $hotspotName)
    {
        $hotspotName = trim((string) $hotspotName);
        if ($hotspotName === '') {
            return '';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', 'name,profile')
                    ->setQuery(RouterOS\Query::where('name', $hotspotName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $profile = trim((string) $row->getProperty('profile'));
                if ($profile !== '') {
                    return $profile;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * Force TOUS les profils hotspot à servir hotspot/login.html (DYRSIA), pas flash/hotspot MikroTik.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>, profile?: string}
     */
    public static function ensureHotspotCaptiveProfileReady($client, array $config, $hotspotName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $hotspotName = trim((string) $hotspotName);
        if ($hotspotName === '') {
            $hotspotName = trim((string) ($config['hotspot_name'] ?? ''));
        }

        $deviceMode = self::ensureHotspotDeviceMode($client);
        if (empty($deviceMode['ok'])) {
            return [
                'ok' => false,
                'errors' => array_merge($deviceMode['errors'] ?? [], $errors),
                'actions' => array_merge($deviceMode['actions'] ?? [], $actions),
            ];
        }
        $actions = array_merge($actions, $deviceMode['actions'] ?? []);

        $configuredProfile = trim((string) ($config['hotspot_profile'] ?? 'hotspot'));
        $primaryProfile = self::resolveHotspotProfileNameForSync($client, $hotspotName, $configuredProfile);

        $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
        $smtpServer = trim((string) ($config['hotspot_smtp_server'] ?? '0.0.0.0'));
        $dnsServer = trim((string) ($config['hotspot_dns_server'] ?? '8.8.8.8'));
        $loginMethods = self::captivePortalLoginBy();
        $cookieLifetime = self::normalizeHotspotCookieLifetime($config['hotspot_cookie_lifetime'] ?? '1d 00:00:00');
        $idleTimeout = trim((string) ($config['hotspot_idle_timeout'] ?? '00:10:00'));
        if ($idleTimeout === '') {
            $idleTimeout = '00:10:00';
        }
        $useRadius = self::hotspotRadiusEnabled($config);
        $loginMethodsForProfile = $loginMethods;

        $hotspotAddress = '';
        $localAddress = trim((string) ($config['hotspot_local_address'] ?? ''));
        $network = self::parseHotspotLocalNetwork($localAddress);
        if ($network !== null) {
            $hotspotAddress = (string) ($network['gateway'] ?? '');
        }
        if ($hotspotAddress === '' && $hotspotName !== '') {
            $listenIp = self::getHotspotServerAddress($client, $hotspotName);
            if ($listenIp !== '') {
                $hotspotAddress = $listenIp;
            }
        }

        $profileNames = self::listHotspotProfileNames($client);
        if ($primaryProfile !== '' && !in_array($primaryProfile, $profileNames, true)) {
            $profileNames[] = $primaryProfile;
        }
        if ($profileNames === []) {
            $profileNames = [$primaryProfile !== '' ? $primaryProfile : 'default'];
        }

        $fixed = [];
        foreach ($profileNames as $profileName) {
            try {
                self::ensureHotspotProfileConfigured(
                    $client,
                    $profileName,
                    $dnsName,
                    $smtpServer,
                    $dnsServer,
                    $loginMethodsForProfile,
                    $cookieLifetime,
                    $idleTimeout,
                    $useRadius,
                    $hotspotAddress
                );
                self::forceHotspotProfileHtmlDirectory($client, $profileName);
                $htmlDir = self::getHotspotProfileHtmlDirectory($client, $profileName);
                if ($htmlDir !== '' && !self::hotspotHtmlDirectoryIsDyrsia($htmlDir)) {
                    $errors[] = 'Profil « ' . $profileName . ' » utilise html-directory=« ' . $htmlDir
                        . ' » (page MikroTik par défaut). Attendu : « hotspot ».';
                    continue;
                }
                $fixed[] = $profileName;
            } catch (Throwable $e) {
                $errors[] = 'profil « ' . $profileName . ' » : ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'profil « ' . $profileName . ' » : ' . $e->getMessage();
            }
        }

        if ($fixed !== []) {
            $actions[] = 'login-by=' . self::captivePortalLoginBy()
                . ' + html-directory=hotspot sur ' . count($fixed) . ' profil(s) : ' . implode(', ', $fixed);
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
            'profile' => $primaryProfile,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function listHotspotProfileNames($client)
    {
        $names = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/print'))
                    ->setArgument('.proplist', 'name')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return array_values(array_unique($names));
    }

    private static function forceHotspotProfileHtmlDirectory($client, $profileName)
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            return;
        }
        $profileId = self::routerEntityId($client, '/ip/hotspot/profile', 'name', $profileName);
        if ($profileId === null) {
            return;
        }

        $setRequest = (new RouterOS\Request('/ip/hotspot/profile/set'))
            ->setArgument('numbers', $profileId)
            ->setArgument('html-directory', 'hotspot')
            ->setArgument('login-by', self::captivePortalLoginBy());
        try {
            // RouterOS 7 : si un override pointe vers flash/hotspot, la page usine reste affichée.
            $setRequest->setArgument('html-directory-override', '');
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
        $client->sendSync($setRequest);

        // Fallback script one-shot si l'API ignore html-directory-override / login-by.
        $escaped = str_replace(['\\', '"'], '', $profileName);
        try {
            self::runRouterOneShotScript(
                $client,
                'dyrsia_hs_htmldir',
                '/ip hotspot profile set [find name="' . $escaped . '"] html-directory=hotspot login-by=' . self::captivePortalLoginBy() . '; '
                . ':do { /ip hotspot profile set [find name="' . $escaped . '"] html-directory-override="" } on-error={}'
            );
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    private static function getHotspotProfileHtmlDirectory($client, $profileName)
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            return '';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/print'))
                    ->setArgument('.proplist', 'name,html-directory,html-directory-override')
                    ->setQuery(RouterOS\Query::where('name', $profileName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $override = trim((string) $row->getProperty('html-directory-override'));
                if ($override !== '') {
                    return $override;
                }

                return trim((string) $row->getProperty('html-directory'));
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    private static function normalizeHotspotHtmlDirectory($value)
    {
        $value = trim(strtolower((string) $value));
        $value = str_replace('\\', '/', $value);

        return trim($value, '/');
    }

    private static function hotspotHtmlDirectoryIsDyrsia($htmlDir)
    {
        $htmlDir = self::normalizeHotspotHtmlDirectory($htmlDir);
        if ($htmlDir === '' || $htmlDir === 'hotspot') {
            return true;
        }
        // flash/hotspot = page MikroTik usine
        if (strpos($htmlDir, 'flash/') === 0) {
            return false;
        }

        return $htmlDir === 'hotspot';
    }

    /**
     * Cible le profil du serveur hotspot existant plutôt que « default » si mal configuré dans DYRSIA.
     */
    private static function resolveHotspotProfileNameForSync($client, $hotspotName, $configuredProfile)
    {
        $configuredProfile = trim((string) $configuredProfile);
        if ($configuredProfile === '') {
            $configuredProfile = 'default';
        }

        $liveProfile = self::getHotspotServerProfileName($client, $hotspotName);
        if ($liveProfile !== '') {
            return $liveProfile;
        }

        return $configuredProfile;
    }

    /**
     * Normalise la durée cookie MikroTik (ex. 4:00:00 → 4h, 1d 00:00:00 → 1d).
     */
    public static function normalizeHotspotCookieLifetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '1d';
        }

        if (preg_match('/^(\d+)d(?:\s+\d{1,2}:\d{1,2}:\d{1,2})?$/i', $value, $dayMatch)) {
            return strtolower($dayMatch[1] . 'd');
        }

        if (preg_match('/^(\d+)h$/i', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^(\d+)m$/i', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^(\d+):(\d{1,2}):(\d{1,2})$/', $value, $match)) {
            $hours = (int) $match[1];
            $minutes = (int) $match[2];
            $seconds = (int) $match[3];
            if ($hours > 0 && $minutes === 0 && $seconds === 0) {
                return $hours . 'h';
            }
            if ($hours === 0 && $minutes > 0 && $seconds === 0) {
                return $minutes . 'm';
            }
            if ($hours === 0 && $minutes === 0 && $seconds > 0) {
                return $seconds . 's';
            }

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return $value;
    }

    private static function parseHotspotCookieLifetimeSeconds($value)
    {
        $value = trim(strtolower((string) $value));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d+)w$/', $value, $match)) {
            return (int) $match[1] * 604800;
        }
        if (preg_match('/^(\d+)d$/', $value, $match)) {
            return (int) $match[1] * 86400;
        }
        if (preg_match('/^(\d+)h$/', $value, $match)) {
            return (int) $match[1] * 3600;
        }
        if (preg_match('/^(\d+)m$/', $value, $match)) {
            return (int) $match[1] * 60;
        }
        if (preg_match('/^(\d+)s$/', $value, $match)) {
            return (int) $match[1];
        }
        if (preg_match('/^(\d+):(\d{1,2}):(\d{1,2})$/', $value, $match)) {
            return ((int) $match[1] * 3600) + ((int) $match[2] * 60) + (int) $match[3];
        }

        return null;
    }

    private static function hotspotCookieLifetimeMatches($expected, $actual)
    {
        $expectedSeconds = self::parseHotspotCookieLifetimeSeconds(self::normalizeHotspotCookieLifetime($expected));
        $actualSeconds = self::parseHotspotCookieLifetimeSeconds($actual);
        if ($expectedSeconds === null || $actualSeconds === null) {
            return trim((string) $expected) !== '' && strcasecmp(trim((string) $expected), trim((string) $actual)) === 0;
        }

        return $expectedSeconds === $actualSeconds;
    }

    private static function hotspotLoginByIncludes($loginBy, $method)
    {
        $method = strtolower(trim((string) $method));
        if ($method === '') {
            return false;
        }
        foreach (preg_split('/\s*,\s*/', strtolower(trim((string) $loginBy))) as $part) {
            if ($part === $method) {
                return true;
            }
        }

        return false;
    }

    private static function readHotspotProfileCookieLifetime($client, $profileName)
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            return '';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/print'))
                    ->setQuery(RouterOS\Query::where('name', $profileName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }

                $value = trim((string) $row->getProperty('http-cookie-lifetime'));
                if ($value !== '') {
                    return $value;
                }
            }

            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/profile/print'))
                    ->setArgument('.proplist', 'http-cookie-lifetime')
                    ->setQuery(RouterOS\Query::where('name', $profileName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }

                return trim((string) $row->getProperty('http-cookie-lifetime'));
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    private static function readHotspotUserProfileMacCookieTimeout($client, $profileName = 'default')
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            $profileName = 'default';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/user/profile/print'))
                    ->setQuery(RouterOS\Query::where('name', $profileName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }

                return trim((string) $row->getProperty('mac-cookie-timeout'));
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    private static function ensureHotspotUserProfilesMacCookieTimeout($client, $cookieLifetime)
    {
        $cookieLifetime = self::normalizeHotspotCookieLifetime($cookieLifetime);
        if ($cookieLifetime === '') {
            return;
        }

        foreach ($client->sendSync(
            (new RouterOS\Request('/ip/hotspot/user/profile/print'))
                ->setArgument('.proplist', '.id')
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $profileId = $row->getProperty('.id');
            if ($profileId === null || $profileId === '') {
                continue;
            }

            $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/user/profile/set'))
                    ->setArgument('numbers', $profileId)
                    ->setArgument('mac-cookie-timeout', $cookieLifetime)
                    ->setArgument('add-mac-cookie', 'yes')
            );
        }
    }

    /**
     * Applique la durée cookie selon login-by :
     * - mac-cookie → mac-cookie-timeout sur /ip/hotspot/user/profile
     * - cookie → http-cookie-lifetime sur /ip/hotspot/profile
     */
    private static function ensureHotspotAuthCookieLifetime($client, $profileName, $cookieLifetime, $loginBy)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return;
        }

        $profileName = trim((string) $profileName);
        $cookieLifetime = self::normalizeHotspotCookieLifetime($cookieLifetime);
        if ($cookieLifetime === '') {
            return;
        }

        $usesCookie = self::hotspotLoginByIncludes($loginBy, 'cookie');
        $usesMacCookie = self::hotspotLoginByIncludes($loginBy, 'mac-cookie');
        if (!$usesCookie && !$usesMacCookie) {
            return;
        }

        if ($usesMacCookie) {
            self::ensureHotspotUserProfilesMacCookieTimeout($client, $cookieLifetime);

            $current = self::readHotspotUserProfileMacCookieTimeout($client, 'default');
            if (!self::hotspotCookieLifetimeMatches($cookieLifetime, $current)) {
                self::runRouterOneShotScript(
                    $client,
                    'dyrsia_hs_maccookie',
                    '/ip hotspot user profile set [find] mac-cookie-timeout=' . $cookieLifetime . ' add-mac-cookie=yes'
                );

                $current = self::readHotspotUserProfileMacCookieTimeout($client, 'default');
                if (!self::hotspotCookieLifetimeMatches($cookieLifetime, $current)) {
                    $detail = $current !== '' ? ('valeur routeur : ' . $current) : 'valeur routeur vide';
                    throw new Exception('mac-cookie-timeout attendu « ' . $cookieLifetime . ' », ' . $detail);
                }
            }
        }

        if (!$usesCookie) {
            return;
        }

        if ($profileName === '') {
            return;
        }

        $profileId = self::routerEntityId($client, '/ip/hotspot/profile', 'name', $profileName);
        if ($profileId === null) {
            throw new Exception('Profil hotspot introuvable pour cookie : ' . $profileName);
        }

        $client->sendSync(
            (new RouterOS\Request('/ip/hotspot/profile/set'))
                ->setArgument('numbers', $profileId)
                ->setArgument('http-cookie-lifetime', $cookieLifetime)
        );

        $current = self::readHotspotProfileCookieLifetime($client, $profileName);
        if (self::hotspotCookieLifetimeMatches($cookieLifetime, $current)) {
            return;
        }

        $escapedName = str_replace(['\\', '"'], '', $profileName);
        self::runRouterOneShotScript(
            $client,
            'dyrsia_hs_cookie',
            '/ip hotspot profile set [find name="' . $escapedName . '"] http-cookie-lifetime=' . $cookieLifetime
        );

        $current = self::readHotspotProfileCookieLifetime($client, $profileName);
        if ($current !== '' && !self::hotspotCookieLifetimeMatches($cookieLifetime, $current)) {
            throw new Exception('http-cookie-lifetime attendu « ' . $cookieLifetime . ' », valeur routeur : ' . $current);
        }
    }

    private static function ensureHotspotProfileConfigured(
        $client,
        $profileName,
        $dnsName,
        $smtpServer,
        $dnsServer,
        $loginMethods,
        $cookieLifetime = '1d 00:00:00',
        $idleTimeout = '00:10:00',
        $useRadius = false,
        $hotspotAddress = ''
    )
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

        $loginBy = trim((string) $loginMethods);
        if ($loginBy === '') {
            $loginBy = $useRadius
                ? self::normalizeHotspotLoginByForRadius($loginMethods)
                : self::normalizeHotspotLoginBy($loginMethods !== '' ? $loginMethods : self::captivePortalLoginBy());
        }

        $cookieLifetime = self::normalizeHotspotCookieLifetime($cookieLifetime);

        $setRequest = (new RouterOS\Request('/ip/hotspot/profile/set'))
            ->setArgument('numbers', $profileId)
            ->setArgument('html-directory', 'hotspot')
            ->setArgument('login-by', $loginBy)
            ->setArgument('smtp-server', $smtpServer !== '' ? $smtpServer : '0.0.0.0')
            ->setArgument('dns-server', $dnsServer !== '' ? $dnsServer : '8.8.8.8')
            ->setArgument('use-radius', $useRadius ? 'yes' : 'no');
        try {
            $setRequest->setArgument('html-directory-override', '');
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
        if ($useRadius) {
            $setRequest->setArgument('radius-accounting', 'yes');
        } else {
            $setRequest->setArgument('radius-accounting', 'no');
        }
        if ($idleTimeout !== '') {
            $setRequest->setArgument('idle-timeout', $idleTimeout);
        }
        if ($dnsName !== '') {
            $setRequest->setArgument('dns-name', $dnsName);
        }
        $hotspotAddress = trim((string) $hotspotAddress);
        if ($hotspotAddress !== '' && filter_var($hotspotAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $setRequest->setArgument('hotspot-address', $hotspotAddress);
        }
        $client->sendSync($setRequest);

        self::ensureHotspotAuthCookieLifetime($client, $profileName, $cookieLifetime, $loginBy);
    }

    private static function ensureHotspotNetworkEntry($client, $localAddress, $profileName, $dnsServer)
    {
        $network = self::parseHotspotLocalNetwork($localAddress);
        if ($network === null) {
            throw new Exception('Adresse locale invalide : ' . $localAddress);
        }

        // RouterOS 7 : /ip hotspot network n'existe plus — réseau via /ip dhcp-server network.
        $dhcpDns = $network['gateway'];
        $networkId = self::routerEntityId($client, '/ip/dhcp-server/network', 'address', $network['address']);
        $setRequest = (new RouterOS\Request($networkId ? '/ip/dhcp-server/network/set' : '/ip/dhcp-server/network/add'))
            ->setArgument('address', $network['address'])
            ->setArgument('gateway', $network['gateway'])
            ->setArgument('dns-server', $dhcpDns);
        if ($networkId) {
            $setRequest->setArgument('numbers', $networkId);
        }
        $client->sendSync($setRequest);
    }

    private static function isBridgeInterface($client, $interface)
    {
        $interface = trim((string) $interface);
        if ($interface === '') {
            return false;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/print'))
                    ->setArgument('.proplist', 'name')
                    ->setQuery(RouterOS\Query::where('name', $interface))
            ) as $row) {
                if ($row->getType() !== 'trap') {
                    return true;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }

    private static function routerYesNoEnabled($value)
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['yes', 'true', '1', 'on'], true);
    }

    private static function readBridgeSettingValue($client, $property)
    {
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/interface/bridge/settings/print'))
            );

            if (is_object($responses) && method_exists($responses, 'getProperty')) {
                $direct = trim((string) $responses->getProperty($property));
                if ($direct !== '') {
                    return self::routerYesNoEnabled($direct);
                }
            }

            foreach ($responses as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $value = trim((string) $row->getProperty($property));
                if ($value !== '') {
                    return self::routerYesNoEnabled($value);
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return null;
    }

    private static function readBridgeUseIpFirewall($client)
    {
        return self::readBridgeSettingValue($client, 'use-ip-firewall');
    }

    private static function readBridgeAllowFastPath($client)
    {
        return self::readBridgeSettingValue($client, 'allow-fast-path');
    }

    /**
     * RouterOS 7 : use-ip-firewall est global (/interface bridge settings), pas par bridge.
     *
     * @return array{is_bridge: bool, use_ip_firewall: bool, use_ip_firewall_for_vlan: bool, name: string}
     */
    public static function readHotspotBridgeFirewallStatus($client, $interface)
    {
        $interface = trim((string) $interface);
        $result = [
            'name' => $interface,
            'is_bridge' => self::isBridgeInterface($client, $interface),
            'use_ip_firewall' => false,
            'use_ip_firewall_for_vlan' => false,
        ];
        if (!$result['is_bridge']) {
            return $result;
        }

        $enabled = self::readBridgeUseIpFirewall($client);
        if ($enabled !== null) {
            $result['use_ip_firewall'] = $enabled;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/settings/print'))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $vlanValue = strtolower(trim((string) $row->getProperty('use-ip-firewall-for-vlan')));
                $result['use_ip_firewall_for_vlan'] = ($vlanValue === 'true' || $vlanValue === 'yes');
                break;
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $result;
    }

    private static function normalizeRouterWanInterface($wan)
    {
        $wan = trim((string) $wan);
        if ($wan === '') {
            return 'ether1';
        }
        if (preg_match('/%([^%>\s]+)>?$/', $wan, $match)) {
            return trim($match[1]);
        }
        if (preg_match('/^<([^>]+)>$/', $wan, $match)) {
            return trim($match[1]);
        }

        return $wan;
    }

    private static function resolveWanOutInterface($client)
    {
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'chain,action,out-interface,disabled')
                    ->setQuery(
                        RouterOS\Query::where('chain', 'srcnat')
                            ->andWhere('action', 'masquerade')
                    )
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('chain') === 'srcnat'
                    && (string) $row->getProperty('action') === 'masquerade'
                    && (string) $row->getProperty('disabled') !== 'true') {
                    $out = trim((string) $row->getProperty('out-interface'));
                    if ($out !== '') {
                        return self::normalizeRouterWanInterface($out);
                    }
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/route/print'))
                    ->setArgument('.proplist', 'dst-address,active,immediate-gw')
                    ->setQuery(RouterOS\Query::where('dst-address', '0.0.0.0/0'))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('active'))) !== 'true') {
                    continue;
                }
                $immediateGw = trim((string) $row->getProperty('immediate-gw'));
                if ($immediateGw !== '') {
                    return self::normalizeRouterWanInterface($immediateGw);
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return 'ether1';
    }

    /**
     * Liste les interfaces physiques (ether/sfp) du routeur.
     *
     * @param array<int, array<string, mixed>> $interfaces
     * @return array<int, string>
     */
    public static function detectRouterPhysicalPorts(array $interfaces)
    {
        $physical = [];
        foreach ($interfaces as $iface) {
            if (!is_array($iface)) {
                continue;
            }
            $type = strtolower(trim((string) ($iface['type'] ?? '')));
            $name = trim((string) ($iface['name'] ?? ''));
            if ($name === '' || $name === 'lo') {
                continue;
            }
            if (in_array($type, ['ether', 'sfp', 'sfp-sfpplus', 'sfpplus', 'bond'], true)
                || preg_match('/^ether/i', $name)) {
                $physical[] = $name;
            }
        }
        usort($physical, static function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        return array_values(array_unique($physical));
    }

    /**
     * @param array<int, array<string, mixed>> $interfaces
     * @return array<int, string>
     */
    public static function detectRouterWirelessPorts(array $interfaces)
    {
        $wireless = [];
        foreach ($interfaces as $iface) {
            if (!is_array($iface)) {
                continue;
            }
            $type = strtolower(trim((string) ($iface['type'] ?? '')));
            $name = trim((string) ($iface['name'] ?? ''));
            if ($name === '' || $name === 'lo') {
                continue;
            }
            if ($type === 'wlan' || preg_match('/^wlan/i', $name)) {
                $wireless[] = $name;
            }
        }
        usort($wireless, static function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        return array_values(array_unique($wireless));
    }

    /**
     * Ports LAN suggérés pour le bridge trunk (hors WAN).
     *
     * @param array<int, array<string, mixed>> $interfaces
     * @param array<string, array<int, string>> $bridgePorts
     * @param array<int, string> $physicalPorts
     * @param array<int, string> $wirelessPorts
     * @return array<int, string>
     */
    private static function suggestRouterLanBridgePorts(
        array $interfaces,
        array $bridgePorts,
        array $physicalPorts,
        array $wirelessPorts,
        $wanInterface
    ) {
        $wanInterface = trim((string) $wanInterface);
        $isWan = static function ($portName) use ($wanInterface) {
            return $wanInterface !== '' && strcasecmp(trim((string) $portName), $wanInterface) === 0;
        };

        $bridgePriority = ['bridge-lan', 'bridge-hotspot', 'bridge-pppoe'];
        foreach ($bridgePriority as $bridgeName) {
            if (empty($bridgePorts[$bridgeName])) {
                continue;
            }
            $ports = array_values(array_filter($bridgePorts[$bridgeName], static function ($port) use ($isWan) {
                return !$isWan($port);
            }));
            if (!empty($ports)) {
                usort($ports, static function ($a, $b) {
                    return strnatcasecmp($a, $b);
                });

                return $ports;
            }
        }

        foreach ($bridgePorts as $ports) {
            if (!is_array($ports) || empty($ports)) {
                continue;
            }
            $filtered = array_values(array_filter($ports, static function ($port) use ($isWan) {
                return !$isWan($port);
            }));
            if (!empty($filtered)) {
                usort($filtered, static function ($a, $b) {
                    return strnatcasecmp($a, $b);
                });

                return $filtered;
            }
        }

        $candidates = array_merge($physicalPorts, $wirelessPorts);
        $candidates = array_values(array_filter($candidates, static function ($port) use ($isWan) {
            return !$isWan($port);
        }));
        if (empty($candidates) && !empty($physicalPorts)) {
            foreach ($physicalPorts as $port) {
                if (!$isWan($port)) {
                    $candidates[] = $port;
                }
            }
        }
        if (empty($candidates)) {
            foreach ($physicalPorts as $port) {
                if (strcasecmp($port, 'ether1') !== 0) {
                    $candidates[] = $port;
                }
            }
        }
        usort($candidates, static function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        return array_values(array_unique($candidates));
    }

    /**
     * Enrichit le snapshot assistant Hotspot/PPPoE (ports WAN/LAN).
     */
    public static function applyRouterPortSuggestions(array &$snapshot, $client, $lightweight = false)
    {
        $interfaces = is_array($snapshot['interfaces'] ?? null) ? $snapshot['interfaces'] : [];
        $bridgePorts = is_array($snapshot['bridge_ports'] ?? null) ? $snapshot['bridge_ports'] : [];
        $physical = self::detectRouterPhysicalPorts($interfaces);
        $wireless = self::detectRouterWirelessPorts($interfaces);

        if ($lightweight || !$client) {
            $wan = self::guessWanInterfaceFromPhysicalPorts($physical);
        } else {
            $wan = self::normalizeRouterWanInterface(self::resolveWanOutInterface($client));
            if ($wan === 'ether1' && !in_array('ether1', $physical, true) && !empty($physical)) {
                $wan = self::guessWanInterfaceFromPhysicalPorts($physical);
            }
        }

        $portBridgeMap = is_array($snapshot['port_bridge_map'] ?? null) ? $snapshot['port_bridge_map'] : [];
        if (empty($portBridgeMap) && !empty($bridgePorts)) {
            foreach ($bridgePorts as $bridge => $ports) {
                if (!is_array($ports)) {
                    continue;
                }
                foreach ($ports as $portName) {
                    $portBridgeMap[(string) $portName] = (string) $bridge;
                }
            }
        }

        $lanPorts = self::suggestRouterLanBridgePorts($interfaces, $bridgePorts, $physical, $wireless, $wan);
        $lanMembers = array_values(array_unique(array_merge($physical, $wireless)));

        $snapshot['physical_ports'] = $physical;
        $snapshot['wireless_ports'] = $wireless;
        $snapshot['physical_port_count'] = count($physical);
        $snapshot['wireless_port_count'] = count($wireless);
        $snapshot['wan_interface'] = $wan;
        $snapshot['lan_ports'] = $lanPorts;
        $snapshot['lan_port_count'] = count($lanPorts);
        $snapshot['trunk_member_ports'] = $lanMembers;
        $snapshot['port_bridge_map'] = $portBridgeMap;

        if (!isset($snapshot['suggested']) || !is_array($snapshot['suggested'])) {
            $snapshot['suggested'] = self::serviceBridgeDefaults();
        }
        if (!empty($lanPorts)) {
            $snapshot['suggested']['hotspot_bridge_ports'] = implode(',', $lanPorts);
        }
        if ($wan !== '') {
            $snapshot['suggested']['lan_wan_interface'] = $wan;
        }
    }

    /**
     * RouterOS 7+ (L009, hAP ax³, etc.) : le hotspot peut être bloqué par /system device-mode.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>, needs_physical_confirm?: bool}
     */
    public static function ensureHotspotDeviceMode($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $flags = self::readHotspotDeviceModeFlags($client);
        if ($flags === null) {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $actions = [];
        $errors = [];
        $needsPhysicalConfirm = false;

        if (self::deviceModeAllowsHotspot($flags)) {
            return ['ok' => true, 'errors' => [], 'actions' => $actions];
        }

        $board = trim((string) ($flags['board-name'] ?? ''));
        $mode = strtolower(trim((string) ($flags['mode'] ?? '')));
        self::setClientSocketTimeout($client, 15);
        try {
            $update = new RouterOS\Request('/system/device-mode/update');
            if (in_array($mode, ['home', 'basic'], true)) {
                $update->setArgument('mode', 'advanced');
                $actions[] = 'device-mode : demande mode=advanced (hotspot autorisé sur RouterOS 7)';
            } else {
                $update->setArgument('hotspot', 'yes');
                $update->setArgument('fetch', 'yes');
                $update->setArgument('scheduler', 'yes');
                $actions[] = 'device-mode : hotspot/fetch/scheduler=yes demandé';
            }
            $client->sendSync($update);
            $needsPhysicalConfirm = true;
        } catch (Throwable $e) {
            $needsPhysicalConfirm = true;
            $actions[] = 'device-mode : commande envoyée — confirmation physique requise sur le routeur';
        } catch (Exception $e) {
            $needsPhysicalConfirm = true;
            $actions[] = 'device-mode : commande envoyée — confirmation physique requise sur le routeur';
        }
        self::setClientSocketTimeout($client, 45);

        $flags = self::readHotspotDeviceModeFlags($client);
        if ($flags !== null && !self::deviceModeAllowsHotspot($flags)) {
            $errors[] = self::formatDeviceModeHotspotUserHint($flags, $board, $needsPhysicalConfirm);
        } elseif ($flags !== null && !empty($flags['flagged'])) {
            $actions[] = 'device-mode : confirmation physique possible (bouton Mode) — hotspot autorisé côté logiciel';
        }

        if ($errors === []) {
            self::reactivateHotspotServersAfterDeviceMode($client, $actions, $errors);
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
            'needs_physical_confirm' => $needsPhysicalConfirm && !empty($flags['flagged']),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private static function readHotspotDeviceModeFlags($client)
    {
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/system/device-mode/print'))
                    ->setArgument('.proplist', 'mode,hotspot,flagged,flagging-enabled')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }

                return [
                    'mode' => trim((string) $row->getProperty('mode')),
                    'hotspot' => trim((string) $row->getProperty('hotspot')),
                    'flagged' => trim((string) $row->getProperty('flagged')),
                    'flagging-enabled' => trim((string) $row->getProperty('flagging-enabled')),
                    'board-name' => self::readRouterBoardName($client),
                ];
            }
        } catch (Throwable $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    private static function readRouterBoardName($client)
    {
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/system/resource/print'))
                    ->setArgument('.proplist', 'board-name')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('board-name'));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * @param array<string, string> $flags
     */
    private static function deviceModeAllowsHotspot(array $flags)
    {
        $mode = strtolower(trim((string) ($flags['mode'] ?? '')));
        if (in_array($mode, ['home', 'basic'], true)) {
            return false;
        }

        $hotspot = strtolower(trim((string) ($flags['hotspot'] ?? '')));
        if (in_array($hotspot, ['false', 'no', 'off'], true)) {
            return false;
        }

        return in_array($hotspot, ['true', 'yes', 'on'], true);
    }

    /**
     * Instructions utilisateur (device-mode n’a pas de menu graphique dédié sur la plupart des routeurs).
     *
     * @param array<string, string> $flags
     */
    private static function formatDeviceModeHotspotUserHint(array $flags, $boardName = '', $awaitingPhysicalConfirm = false)
    {
        $mode = trim((string) ($flags['mode'] ?? ''));
        $lines = [
            'RouterOS 7 bloque le hotspot (device-mode, mode actuel : « ' . ($mode !== '' ? $mode : '?') . ' »).',
            'Ce réglage ne se trouve pas dans un menu Winbox : ouvrez le Terminal Winbox (ou SSH) sur le routeur et exécutez :',
            '/system device-mode update mode=advanced',
            'Le routeur affiche alors un délai (~5 min) : confirmez en coupant l’alimentation 10 s (rebrancher) ou en appuyant sur le bouton Reset/Mode du L009, puis vérifiez : /system device-mode print (mode=advanced, hotspot=yes).',
            'Ensuite relancez « Send complet » hotspot dans DYRSIA.',
        ];
        if ($boardName !== '') {
            $lines[0] .= ' Modèle : « ' . $boardName . ' ».';
        }
        if ($awaitingPhysicalConfirm) {
            $lines[] = 'Une demande de changement est peut‑être déjà en attente : faites la confirmation physique maintenant, sinon elle expire.';
        }

        return implode(' ', $lines);
    }

    private static function reactivateHotspotServersAfterDeviceMode($client, &$actions, &$errors)
    {
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', '.id,name,disabled,invalid')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $id = $row->getProperty('.id');
                if ($id === null || $id === '') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                $invalid = strtolower(trim((string) $row->getProperty('invalid'))) === 'true';
                $client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/set'))
                        ->setArgument('numbers', $id)
                        ->setArgument('disabled', 'yes')
                );
                $client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/set'))
                        ->setArgument('numbers', $id)
                        ->setArgument('disabled', 'no')
                );
                $label = $name !== '' ? $name : (string) $id;
                $actions[] = 'hotspot « ' . $label . ' » réactivé'
                    . ($invalid ? ' (après device-mode)' : '');
            }
        } catch (Throwable $e) {
            $errors[] = 'réactivation hotspot après device-mode : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'réactivation hotspot après device-mode : ' . $e->getMessage();
        }
    }

    /**
     * @return array{blocked: bool, message: string}
     */
    public static function hotspotServerDeviceModeStatus($client, $hotspotName = '')
    {
        $hotspotName = trim((string) $hotspotName);
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', 'name,invalid,.about,disabled')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($hotspotName !== '' && strcasecmp($name, $hotspotName) !== 0) {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('invalid'))) !== 'true') {
                    continue;
                }
                $about = strtolower(trim((string) $row->getProperty('.about')));
                if (strpos($about, 'device-mode') !== false || strpos($about, 'not allowed') !== false) {
                    $flags = self::readHotspotDeviceModeFlags($client);

                    return [
                        'blocked' => true,
                        'message' => $flags !== null
                            ? self::formatDeviceModeHotspotUserHint($flags, self::readRouterBoardName($client))
                            : 'Serveur hotspot inactif : device-mode RouterOS 7 (hotspot non autorisé).',
                    ];
                }

                return ['blocked' => true, 'message' => 'Serveur hotspot invalide : ' . $row->getProperty('.about')];
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return ['blocked' => false, 'message' => ''];
    }

    /**
     * Reglages bridge requis pour le hotspot (RouterOS 7).
     * use-ip-firewall-for-vlan=yes casse la redirection captive — doit rester a no.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function ensureHotspotBridgeSettings($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        try {
            $client->sendSync(
                (new RouterOS\Request('/interface/bridge/settings/set'))
                    ->setArgument('use-ip-firewall', 'yes')
                    ->setArgument('use-ip-firewall-for-vlan', 'no')
                    ->setArgument('allow-fast-path', 'no')
            );
            $actions[] = 'bridge settings : use-ip-firewall=yes, use-ip-firewall-for-vlan=no';
        } catch (Throwable $e) {
            $errors[] = 'bridge settings : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'bridge settings : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * RouterOS 7 : bridge settings globaux + fast-forward bridge (fast-path contourne le hotspot).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotBridgeFirewall($client, $interface)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $interface = trim((string) $interface);
        if ($interface === '' || !self::isBridgeInterface($client, $interface)) {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $bridgeSettings = self::ensureHotspotBridgeSettings($client);
        $errors = array_merge($errors, $bridgeSettings['errors'] ?? []);
        $actions = array_merge($actions, $bridgeSettings['actions'] ?? []);

        $needsIpFirewall = self::readBridgeUseIpFirewall($client) !== true;
        $needsNoFastPath = self::readBridgeAllowFastPath($client) !== false;

        if ($needsIpFirewall || $needsNoFastPath) {
            try {
                $setRequest = new RouterOS\Request('/interface/bridge/settings/set');
                if ($needsIpFirewall) {
                    $setRequest->setArgument('use-ip-firewall', 'yes');
                }
                if ($needsNoFastPath) {
                    $setRequest->setArgument('allow-fast-path', 'no');
                }
                $setRequest->setArgument('use-ip-firewall-for-vlan', 'no');
                $client->sendSync($setRequest);
            } catch (Throwable $e) {
                $errors[] = 'bridge settings API : ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'bridge settings API : ' . $e->getMessage();
            }
        }

        $dhcpFwResult = self::ensureHotspotDhcpFirewallPass($client, $interface);
        $errors = array_merge($errors, $dhcpFwResult['errors'] ?? []);
        $actions = array_merge($actions, $dhcpFwResult['actions'] ?? []);

        $hwResult = self::ensureHotspotBridgePortNoHwOffload($client, $interface);
        $errors = array_merge($errors, $hwResult['errors'] ?? []);
        $actions = array_merge($actions, $hwResult['actions'] ?? []);

        if ($needsIpFirewall) {
            $actions[] = 'bridge settings : use-ip-firewall=yes';
        }
        if ($needsNoFastPath) {
            $actions[] = 'bridge settings : allow-fast-path=no';
        }

        $bridgeId = self::routerEntityId($client, '/interface/bridge', 'name', $interface);
        if ($bridgeId !== null) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/set'))
                        ->setArgument('numbers', $bridgeId)
                        ->setArgument('fast-forward', 'no')
                );
                $actions[] = 'bridge « ' . $interface . ' » : fast-forward=no';
            } catch (Throwable $e) {
                $errors[] = 'bridge fast-forward API : ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'bridge fast-forward API : ' . $e->getMessage();
            }
        }

        self::ensureHotspotBridgeBootScript($client, $interface);

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Autorise DHCP (UDP 67/68) dans le firewall IP pour l'interface bridge hotspot.
     * Requis lorsque use-ip-firewall=yes : sans ces règles les clients restent en 169.254.x.x.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    /**
     * Interfaces à couvrir pour le DHCP hotspot (bridge + ports membres physiques).
     * Avec use-ip-firewall=yes, le trafic wlan1/ether arrive avec in-interface=port physique.
     *
     * @return array<int, string>
     */
    private static function collectHotspotDhcpFirewallInterfaces($client, $interface)
    {
        $interface = trim((string) $interface);
        $interfaces = [];
        if ($interface !== '') {
            $interfaces[] = $interface;
        }

        if ($interface !== '' && self::isBridgeInterface($client, $interface)) {
            foreach (self::listBridgeMemberInterfaceNames($client, $interface) as $member) {
                if ($member !== '' && !in_array($member, $interfaces, true)) {
                    $interfaces[] = $member;
                }
            }
        }

        return $interfaces;
    }

    /**
     * @return array<int, string>
     */
    private static function listBridgeMemberInterfaceNames($client, $bridgeName)
    {
        $bridgeName = trim((string) $bridgeName);
        if ($bridgeName === '') {
            return [];
        }

        $members = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', 'interface,bridge,disabled')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('bridge') !== $bridgeName) {
                    continue;
                }
                if (self::isRouterOsDisabledFlag($row->getProperty('disabled'))) {
                    continue;
                }
                $member = trim((string) $row->getProperty('interface'));
                if ($member !== '' && !in_array($member, $members, true)) {
                    $members[] = $member;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $members;
    }

    /**
     * Complète hotspot_bridge_ports depuis les membres réels du bridge routeur.
     *
     * @return array<string, mixed>
     */
    private static function enrichHotspotConfigFromRouter($client, array $config)
    {
        $iface = trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot'));
        if ($iface === '') {
            $iface = 'bridge-hotspot';
        }

        $portsRaw = trim((string) ($config['hotspot_bridge_ports'] ?? ''));
        if ($portsRaw === '') {
            $portsRaw = trim((string) ($config['lan_hotspot_access_ports'] ?? ''));
        }
        if ($portsRaw === '' && self::isBridgeInterface($client, $iface)) {
            $members = self::listBridgeMemberInterfaceNames($client, $iface);
            if ($members !== []) {
                $config['hotspot_bridge_ports'] = implode(',', $members);
            }
        }

        return $config;
    }

    private static function findFirstFirewallRuleIdInChain($client, $chain)
    {
        $chain = trim((string) $chain);
        if ($chain === '') {
            return null;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain')
                    ->setQuery(RouterOS\Query::where('chain', $chain))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $id = $row->getProperty('.id');
                if ($id !== null && $id !== '') {
                    return $id;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Replace une règle en tête de chaîne (avant jump Hotspot / drop).
     */
    private static function repositionFirewallFilterRuleToChainTop($client, $chain, $ruleId)
    {
        $chain = trim((string) $chain);
        $ruleId = trim((string) $ruleId);
        if ($chain === '' || $ruleId === '') {
            return false;
        }

        $firstId = self::findFirstFirewallRuleIdInChain($client, $chain);
        if ($firstId === null || $firstId === '' || $firstId === $ruleId) {
            return false;
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/move'))
                    ->setArgument('numbers', $ruleId)
                    ->setArgument('destination', $firstId)
            );

            return true;
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function ensureHotspotDhcpFirewallPassRule(
        $client,
        $chain,
        $port,
        $comment,
        array $extraArgs,
        &$actions,
        &$errors
    ) {
        $chain = trim((string) $chain);
        $port = trim((string) $port);
        if ($chain === '' || $port === '') {
            return;
        }

        try {
            $existingRuleId = null;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,protocol,dst-port,in-interface,dst-address,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('chain') !== $chain) {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('protocol'))) !== 'udp') {
                    continue;
                }
                if (trim((string) $row->getProperty('dst-port')) !== $port) {
                    continue;
                }
                if (trim((string) $row->getProperty('comment')) !== $comment) {
                    continue;
                }
                $match = true;
                foreach ($extraArgs as $key => $value) {
                    if (trim((string) $row->getProperty($key)) !== trim((string) $value)) {
                        $match = false;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
                $existingRuleId = $row->getProperty('.id');
                break;
            }

            if ($existingRuleId !== null && $existingRuleId !== '') {
                return;
            }

            $firstRuleId = self::findFirstFirewallRuleIdInChain($client, $chain);
            $addRequest = (new RouterOS\Request('/ip/firewall/filter/add'))
                ->setArgument('chain', $chain)
                ->setArgument('action', 'accept')
                ->setArgument('protocol', 'udp')
                ->setArgument('dst-port', $port)
                ->setArgument('comment', $comment);
            foreach ($extraArgs as $key => $value) {
                $addRequest->setArgument($key, $value);
            }
            if ($firstRuleId !== null && $firstRuleId !== '') {
                $addRequest->setArgument('place-before', $firstRuleId);
            }
            $client->sendSync($addRequest);
            $actions[] = 'firewall ' . $chain . ' udp/' . $port . ' accept (' . $comment . ')';
        } catch (Throwable $e) {
            $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' : ' . $e->getMessage();
        }
    }

    private static function ensureHotspotDhcpFirewallPassBroad($client, $gatewayIp, $wanInterface, &$actions, &$errors)
    {
        $gatewayIp = trim((string) $gatewayIp);
        $wanInterface = trim((string) $wanInterface);
        if ($wanInterface === '') {
            $wanInterface = self::resolveWanOutInterface($client);
        }
        if ($wanInterface === '') {
            $wanInterface = 'ether1';
        }

        $commentBroad = 'DYRSIA hotspot DHCP broad';
        foreach (['input', 'forward'] as $chain) {
            foreach (['67', '68'] as $port) {
                self::ensureHotspotDhcpFirewallPassRule(
                    $client,
                    $chain,
                    $port,
                    $commentBroad,
                    ['in-interface' => '!' . $wanInterface],
                    $actions,
                    $errors
                );
            }
        }

        if ($gatewayIp !== '' && filter_var($gatewayIp, FILTER_VALIDATE_IP)) {
            $commentGw = 'DYRSIA hotspot DHCP gateway';
            foreach (['input', 'forward'] as $chain) {
                foreach (['67', '68'] as $port) {
                    self::ensureHotspotDhcpFirewallPassRule(
                        $client,
                        $chain,
                        $port,
                        $commentGw,
                        ['dst-address' => $gatewayIp],
                        $actions,
                        $errors
                    );
                }
            }
        }
    }

    private static function ensureHotspotDhcpFirewallPassHotspotChain($client, &$actions, &$errors)
    {
        $comment = 'DYRSIA hotspot DHCP hs-input';
        foreach (['67', '68'] as $port) {
            self::ensureHotspotDhcpFirewallPassRule(
                $client,
                'hs-input',
                $port,
                $comment,
                [],
                $actions,
                $errors
            );
        }
    }

    /**
     * Réactive le Wi‑Fi (hAP lite : wlan1 slave du bridge peut afficher running=false).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function ensureHotspotWirelessRunning($client, $bridgeInterface)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $bridgeInterface = trim((string) $bridgeInterface);
        $errors = [];
        $actions = [];
        $wirelessNames = self::listActiveWirelessInterfaceNames($client);
        if ($wirelessNames === [] && self::isBridgeInterface($client, $bridgeInterface)) {
            foreach (self::listBridgeMemberInterfaceNames($client, $bridgeInterface) as $member) {
                if (self::isWlanInterfaceName($member)) {
                    $wirelessNames[] = $member;
                }
            }
        }

        foreach ($wirelessNames as $wlanInterface) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/interface/enable'))
                        ->setArgument('numbers', $wlanInterface)
                );
                if (self::configureRouterWifiInterfaceForHotspotBridge($client, $wlanInterface)) {
                    $actions[] = 'Wi‑Fi « ' . $wlanInterface . ' » activé (AP)';
                } else {
                    $actions[] = 'Wi‑Fi « ' . $wlanInterface . ' » activé';
                }
            } catch (Throwable $e) {
                $errors[] = 'Wi‑Fi « ' . $wlanInterface . ' » : ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'Wi‑Fi « ' . $wlanInterface . ' » : ' . $e->getMessage();
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function ensureHotspotDhcpFirewallPassForInterface($client, $interface, $comment, &$actions, &$errors)
    {
        $interface = trim((string) $interface);
        if ($interface === '') {
            return;
        }

        $chains = ['input', 'forward'];
        $ports = ['67', '68'];

        foreach ($chains as $chain) {
            foreach ($ports as $port) {
                try {
                    $existingRuleId = null;
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/ip/firewall/filter/print'))
                            ->setArgument('.proplist', '.id,chain,protocol,dst-port,in-interface,comment,action')
                    ) as $row) {
                        if ($row->getType() === 'trap') {
                            continue;
                        }
                        if ((string) $row->getProperty('chain') !== $chain) {
                            continue;
                        }
                        if (strtolower(trim((string) $row->getProperty('protocol'))) !== 'udp') {
                            continue;
                        }
                        if (trim((string) $row->getProperty('dst-port')) !== $port) {
                            continue;
                        }
                        if (trim((string) $row->getProperty('in-interface')) !== $interface) {
                            continue;
                        }
                        if (trim((string) $row->getProperty('comment')) !== $comment) {
                            continue;
                        }
                        $existingRuleId = $row->getProperty('.id');
                        break;
                    }
                    if ($existingRuleId !== null && $existingRuleId !== '') {
                        continue;
                    }

                    $placeBefore = self::findFirstFirewallRuleIdInChain($client, $chain);

                    $addRequest = (new RouterOS\Request('/ip/firewall/filter/add'))
                        ->setArgument('chain', $chain)
                        ->setArgument('action', 'accept')
                        ->setArgument('protocol', 'udp')
                        ->setArgument('dst-port', $port)
                        ->setArgument('in-interface', $interface)
                        ->setArgument('comment', $comment);
                    if ($placeBefore !== null && $placeBefore !== '') {
                        $addRequest->setArgument('place-before', $placeBefore);
                    }
                    $client->sendSync($addRequest);
                    $actions[] = 'firewall filter ' . $chain . ' udp/' . $port . ' accept sur « ' . $interface . ' » (DHCP)';
                } catch (Throwable $e) {
                    $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' « ' . $interface . ' » : ' . $e->getMessage();
                } catch (Exception $e) {
                    $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' « ' . $interface . ' » : ' . $e->getMessage();
                }
            }
        }
    }

    public static function ensureHotspotDhcpFirewallPass($client, $interface)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $interface = trim((string) $interface);
        if ($interface === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $comment = 'DYRSIA hotspot DHCP';
        $interfaces = self::collectHotspotDhcpFirewallInterfaces($client, $interface);

        foreach ($interfaces as $iface) {
            self::ensureHotspotDhcpFirewallPassForInterface($client, $iface, $comment, $actions, $errors);
        }

        $gatewayIp = '';
        $localAddress = self::resolveHotspotLocalAddressFromRouter($client, $interface);
        if ($localAddress !== '') {
            $gatewayIp = explode('/', $localAddress, 2)[0];
        }
        self::ensureHotspotDhcpFirewallPassBroad($client, $gatewayIp, self::resolveWanOutInterface($client), $actions, $errors);
        self::ensureHotspotDhcpFirewallPassHotspotChain($client, $actions, $errors);
        self::ensureHotspotDhcpHsUnauthReturnRules($client, $actions, $errors);
        self::repositionHotspotDhcpFirewallRulesBatch($client, $actions);

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Règles return UDP 67/68 dans hs-unauth / hs-unauth-to (portail avant auth).
     */
    private static function ensureHotspotDhcpHsUnauthReturnRules($client, &$actions, &$errors)
    {
        $comment = 'DYRSIA hotspot DHCP';
        $chains = [
            'hs-unauth' => 'dst-port',
            'hs-unauth-to' => 'src-port',
        ];
        foreach ($chains as $chain => $portKey) {
            foreach (['67', '68'] as $port) {
                self::ensureHotspotDhcpFilterReturnRule(
                    $client,
                    $chain,
                    $portKey,
                    $port,
                    $comment,
                    $actions,
                    $errors
                );
            }
        }
    }

    private static function ensureHotspotDhcpFilterReturnRule(
        $client,
        $chain,
        $portKey,
        $port,
        $comment,
        &$actions,
        &$errors
    ) {
        $chain = trim((string) $chain);
        $portKey = trim((string) $portKey);
        $port = trim((string) $port);
        if ($chain === '' || $portKey === '' || $port === '') {
            return;
        }

        try {
            $existingRuleId = null;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,protocol,action,' . $portKey . ',comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('chain') !== $chain) {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('protocol'))) !== 'udp') {
                    continue;
                }
                if (trim((string) $row->getProperty($portKey)) !== $port) {
                    continue;
                }
                if (trim((string) $row->getProperty('comment')) !== $comment) {
                    continue;
                }
                $existingRuleId = $row->getProperty('.id');
                break;
            }

            if ($existingRuleId !== null && $existingRuleId !== '') {
                return;
            }

            $firstRuleId = self::findFirstFirewallRuleIdInChain($client, $chain);
            $addRequest = (new RouterOS\Request('/ip/firewall/filter/add'))
                ->setArgument('chain', $chain)
                ->setArgument('action', 'return')
                ->setArgument('protocol', 'udp')
                ->setArgument($portKey, $port)
                ->setArgument('comment', $comment);
            if ($firstRuleId !== null && $firstRuleId !== '') {
                $addRequest->setArgument('place-before', $firstRuleId);
            }
            $client->sendSync($addRequest);
            $actions[] = 'firewall ' . $chain . ' udp/' . $port . ' return (DHCP hotspot)';
        } catch (Throwable $e) {
            $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'firewall DHCP ' . $chain . '/' . $port . ' : ' . $e->getMessage();
        }
    }

    /**
     * Raw prerouting : DHCP avant conn-tracking / isolation PPPoE (use-ip-firewall=yes).
     * N'utilise que le bridge maître — les ports esclaves (wlan1, ether3) rendent les règles invalides.
     */
    private static function ensureHotspotDhcpRawPass($client, $interface, &$actions, &$errors)
    {
        $interface = trim((string) $interface);
        if ($interface === '') {
            return;
        }

        self::cleanupInvalidHotspotDhcpRawRules($client, $actions);

        $wanInterface = self::resolveWanOutInterface($client);
        if ($wanInterface === '') {
            $wanInterface = 'ether1';
        }

        $commentBroad = 'DYRSIA hotspot DHCP raw broad';
        foreach (['67', '68'] as $port) {
            self::ensureHotspotRawRuleByComment(
                $client,
                $commentBroad . ' ' . $port,
                [
                    'chain' => 'prerouting',
                    'action' => 'accept',
                    'protocol' => 'udp',
                    'dst-port' => $port,
                    'in-interface' => '!' . $wanInterface,
                ],
                $actions,
                $errors
            );
        }

        if (self::isBridgeInterface($client, $interface)) {
            foreach (['67', '68'] as $port) {
                self::ensureHotspotRawRuleByComment(
                    $client,
                    'DYRSIA hotspot DHCP raw ' . $interface . ' ' . $port,
                    [
                        'chain' => 'prerouting',
                        'action' => 'accept',
                        'protocol' => 'udp',
                        'dst-port' => $port,
                        'in-interface' => $interface,
                    ],
                    $actions,
                    $errors
                );
            }
        }
    }

    /**
     * Supprime les règles raw DHCP invalides (ports esclaves d'un bridge).
     */
    private static function cleanupInvalidHotspotDhcpRawRules($client, &$actions = null)
    {
        try {
            $removed = 0;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/raw/print'))
                    ->setArgument('.proplist', '.id,comment,invalid')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $comment = (string) $row->getProperty('comment');
                if (strpos($comment, 'DYRSIA hotspot DHCP raw') !== 0) {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('invalid'))) !== 'true') {
                    continue;
                }
                $id = $row->getProperty('.id');
                if ($id === null || $id === '') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/raw/remove'))
                        ->setArgument('numbers', $id)
                );
                $removed++;
            }
            if ($removed > 0 && $actions !== null) {
                $actions[] = 'raw DHCP invalides supprimées (' . $removed . ')';
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * Vérifie que le DHCP hotspot est opérationnel après déploiement PPPoE.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    private static function verifyHotspotDhcpCoexistence($client, $interface)
    {
        $interface = trim((string) $interface);
        $errors = [];
        $dhcpName = self::hotspotDhcpServerName();
        $dhcpOk = false;

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/dhcp-server/print'))
                    ->setArgument('.proplist', 'name,interface,disabled,invalid')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('name') !== $dhcpName) {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('disabled'))) === 'true') {
                    $errors[] = 'Serveur DHCP « ' . $dhcpName . ' » désactivé.';
                    break;
                }
                if (strtolower(trim((string) $row->getProperty('invalid'))) === 'true') {
                    $errors[] = 'Serveur DHCP « ' . $dhcpName . ' » invalide.';
                    break;
                }
                if ($interface !== '' && (string) $row->getProperty('interface') !== $interface) {
                    $errors[] = 'Serveur DHCP sur « ' . $row->getProperty('interface') . ' » au lieu de « ' . $interface . ' ».';
                    break;
                }
                $dhcpOk = true;
                break;
            }
            if (!$dhcpOk && $errors === []) {
                $errors[] = 'Serveur DHCP « ' . $dhcpName . ' » introuvable.';
            }

            $firstDhcp = null;
            $firstDrop = null;
            $n = 0;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', 'chain,action,protocol,dst-port,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('chain') !== 'input') {
                    continue;
                }
                $n++;
                $comment = (string) $row->getProperty('comment');
                $dport = trim((string) $row->getProperty('dst-port'));
                $action = strtolower(trim((string) $row->getProperty('action')));
                if ($firstDhcp === null && $dport === '67' && strpos($comment, 'DYRSIA hotspot DHCP') === 0) {
                    $firstDhcp = $n;
                }
                if ($firstDrop === null && ($action === 'drop' || stripos($comment, 'drop all') !== false)) {
                    $firstDrop = $n;
                }
            }
            if ($firstDhcp === null) {
                $errors[] = 'Aucune règle firewall input DHCP hotspot (DYRSIA).';
            } elseif ($firstDrop !== null && $firstDhcp >= $firstDrop) {
                $errors[] = 'Règles DHCP hotspot après le drop firewall (input #' . $firstDhcp . ' vs drop #' . $firstDrop . ').';
            }
        } catch (Throwable $e) {
            $errors[] = 'Vérification DHCP hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Vérification DHCP hotspot : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    private static function ensureHotspotRawRuleByComment(
        $client,
        $comment,
        array $args,
        &$actions,
        &$errors
    ) {
        $comment = trim((string) $comment);
        if ($comment === '') {
            return;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/raw/print'))
                    ->setArgument('.proplist', '.id,comment')
                    ->setQuery(RouterOS\Query::where('comment', $comment))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('comment') === $comment) {
                    return;
                }
            }

            $firstRawId = null;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/raw/print'))
                    ->setArgument('.proplist', '.id,chain')
                    ->setQuery(RouterOS\Query::where('chain', 'prerouting'))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $firstRawId = $row->getProperty('.id');
                break;
            }

            $addRequest = new RouterOS\Request('/ip/firewall/raw/add');
            foreach ($args as $key => $value) {
                $addRequest->setArgument($key, $value);
            }
            $addRequest->setArgument('comment', $comment);
            if ($firstRawId !== null && $firstRawId !== '') {
                $addRequest->setArgument('place-before', $firstRawId);
            }
            $client->sendSync($addRequest);
            $actions[] = 'raw prerouting DHCP (' . $comment . ')';
        } catch (Throwable $e) {
            $errors[] = 'raw DHCP (' . $comment . ') : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'raw DHCP (' . $comment . ') : ' . $e->getMessage();
        }
    }

    /**
     * Repositionne en une passe les règles DYRSIA hotspot DHCP en tête de chaîne.
     */
    private static function repositionHotspotDhcpFirewallRulesBatch($client, &$actions = null)
    {
        $chains = ['input', 'forward', 'hs-input', 'hs-unauth', 'hs-unauth-to'];
        $rulesByChain = [];
        $firstByChain = [];

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $chain = trim((string) $row->getProperty('chain'));
                $ruleId = trim((string) $row->getProperty('.id'));
                if ($chain === '' || $ruleId === '') {
                    continue;
                }
                if (!isset($firstByChain[$chain])) {
                    $firstByChain[$chain] = $ruleId;
                }
                $comment = (string) $row->getProperty('comment');
                if (strpos($comment, 'DYRSIA hotspot DHCP') === 0) {
                    $rulesByChain[$chain][] = $ruleId;
                }
            }
        } catch (Throwable $e) {
            return;
        } catch (Exception $e) {
            return;
        }

        foreach ($chains as $chain) {
            if (empty($rulesByChain[$chain]) || empty($firstByChain[$chain])) {
                continue;
            }
            $firstId = (string) $firstByChain[$chain];
            $moved = 0;
            foreach (array_reverse($rulesByChain[$chain]) as $ruleId) {
                if ($ruleId === $firstId) {
                    continue;
                }
                try {
                    $client->sendSync(
                        (new RouterOS\Request('/ip/firewall/filter/move'))
                            ->setArgument('numbers', $ruleId)
                            ->setArgument('destination', $firstId)
                    );
                    $moved++;
                } catch (Throwable $e) {
                } catch (Exception $e) {
                }
            }
            if ($moved > 0 && $actions !== null) {
                $actions[] = 'firewall batch : ' . $moved . ' règle(s) DHCP en tête (' . $chain . ')';
            }
        }
    }

    /**
     * Réparation DHCP hotspot rapide après PPPoE (sans setup bridge complet ~3 min).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotDhcpCoexistenceFast($client, array $config)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $resolved = self::resolveHotspotServiceOnRouter($client, $config);
        if (empty($resolved['active'])) {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $config = self::mergeHotspotCoexistenceConfig($config, $resolved);
        $config = self::enrichHotspotConfigFromRouter($client, $config);

        $hsIface = trim((string) ($config['hotspot_interface'] ?? $resolved['interface'] ?? 'bridge-hotspot'));
        if ($hsIface === '') {
            $hsIface = 'bridge-hotspot';
        }

        $actions = [];
        $errors = [];

        $bridgePrep = self::ensureDedicatedHotspotBridge($client, $config);
        if (!empty($bridgePrep['interface'])) {
            $hsIface = (string) $bridgePrep['interface'];
        }
        $actions = array_merge($actions, $bridgePrep['actions'] ?? []);
        $errors = array_merge($errors, $bridgePrep['errors'] ?? []);

        $bridgeSettings = self::ensureHotspotBridgeSettings($client);
        $actions = array_merge($actions, $bridgeSettings['actions'] ?? []);
        $errors = array_merge($errors, $bridgeSettings['errors'] ?? []);

        $hwResult = self::ensureHotspotBridgePortNoHwOffload($client, $hsIface);
        $actions = array_merge($actions, $hwResult['actions'] ?? []);
        $errors = array_merge($errors, $hwResult['errors'] ?? []);

        $bridgeId = self::routerEntityId($client, '/interface/bridge', 'name', $hsIface);
        if ($bridgeId !== null) {
            try {
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/set'))
                        ->setArgument('numbers', $bridgeId)
                        ->setArgument('fast-forward', 'no')
                );
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        if (self::isBridgeInterface($client, $hsIface)) {
            $wlanPrep = self::prepareSimpleWlanHotspotInterface($client, $hsIface);
            $actions = array_merge($actions, $wlanPrep['actions'] ?? []);
            $errors = array_merge($errors, $wlanPrep['errors'] ?? []);

            $wlanRun = self::ensureHotspotWirelessRunning($client, $hsIface);
            $actions = array_merge($actions, $wlanRun['actions'] ?? []);
            $errors = array_merge($errors, $wlanRun['errors'] ?? []);
        }

        $poolName = trim((string) ($config['hotspot_pool_name'] ?? ''));
        $poolRange = trim((string) ($config['hotspot_address_pool'] ?? $config['hotspot_pool_range'] ?? ''));
        $localAddress = trim((string) ($config['hotspot_local_address'] ?? ''));
        $hotspotName = trim((string) ($config['hotspot_name'] ?? ''));

        $dhcpResult = self::ensureHotspotDhcpServer(
            $client,
            $hsIface,
            $poolName,
            $localAddress,
            $hotspotName,
            $poolRange
        );
        $actions = array_merge($actions, $dhcpResult['actions'] ?? []);
        $errors = array_merge($errors, $dhcpResult['errors'] ?? []);

        $wgDhcp = self::ensureHotspotWalledGardenDhcp($client);
        $actions = array_merge($actions, $wgDhcp['actions'] ?? []);
        $errors = array_merge($errors, $wgDhcp['errors'] ?? []);

        self::ensureHotspotDhcpRawPass($client, $hsIface, $actions, $errors);

        $anchorsPresent = self::hotspotFirewallAnchorsPresent($client);
        if (empty($anchorsPresent['filter']) || empty($anchorsPresent['nat'])) {
            $anchors = self::ensureHotspotFirewallAnchors($client, $hotspotName);
            $actions = array_merge($actions, $anchors['actions'] ?? []);
            $errors = array_merge($errors, $anchors['errors'] ?? []);
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Réparation DHCP hotspot minimale après PPPoE (~15–30 s) — sans reconfig bridge complète.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotDhcpCoexistenceEssential($client, array $config)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $resolved = self::resolveHotspotServiceOnRouter($client, $config);
        if (empty($resolved['active'])) {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $config = self::mergeHotspotCoexistenceConfig($config, $resolved);
        $config = self::enrichHotspotConfigFromRouter($client, $config);

        $hsIface = trim((string) ($config['hotspot_interface'] ?? $resolved['interface'] ?? 'bridge-hotspot'));
        if ($hsIface === '') {
            $hsIface = 'bridge-hotspot';
        }

        $actions = [];
        $errors = [];

        $bridgeSettings = self::ensureHotspotBridgeSettings($client);
        $actions = array_merge($actions, $bridgeSettings['actions'] ?? []);
        $errors = array_merge($errors, $bridgeSettings['errors'] ?? []);

        $hwResult = self::ensureHotspotBridgePortNoHwOffload($client, $hsIface);
        $actions = array_merge($actions, $hwResult['actions'] ?? []);
        $errors = array_merge($errors, $hwResult['errors'] ?? []);

        if (self::isBridgeInterface($client, $hsIface)) {
            $wlanRun = self::ensureHotspotWirelessRunning($client, $hsIface);
            $actions = array_merge($actions, $wlanRun['actions'] ?? []);
            $errors = array_merge($errors, $wlanRun['errors'] ?? []);
        }

        $poolName = trim((string) ($config['hotspot_pool_name'] ?? ''));
        $poolRange = trim((string) ($config['hotspot_address_pool'] ?? $config['hotspot_pool_range'] ?? ''));
        $localAddress = trim((string) ($config['hotspot_local_address'] ?? ''));
        $hotspotName = trim((string) ($config['hotspot_name'] ?? ''));

        $dhcpResult = self::ensureHotspotDhcpServer(
            $client,
            $hsIface,
            $poolName,
            $localAddress,
            $hotspotName,
            $poolRange
        );
        $actions = array_merge($actions, $dhcpResult['actions'] ?? []);
        $errors = array_merge($errors, $dhcpResult['errors'] ?? []);

        $wgDhcp = self::ensureHotspotWalledGardenDhcp($client);
        $actions = array_merge($actions, $wgDhcp['actions'] ?? []);
        $errors = array_merge($errors, $wgDhcp['errors'] ?? []);

        self::ensureHotspotDhcpRawPass($client, $hsIface, $actions, $errors);
        self::repositionHotspotDhcpFirewallRulesBatch($client, $actions);

        $verify = self::verifyHotspotDhcpCoexistence($client, $hsIface);
        if (empty($verify['ok'])) {
            $errors = array_merge($errors, $verify['errors'] ?? []);
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Première règle drop/reject d'une chaîne — insertion DHCP avant blocage global.
     */
    private static function findFirewallDropRuleId($client, $chain)
    {
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,action,comment')
                    ->setQuery(RouterOS\Query::where('chain', $chain))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $action = strtolower(trim((string) $row->getProperty('action')));
                $comment = strtolower(trim((string) $row->getProperty('comment')));
                if ($action === 'drop' || $action === 'reject'
                    || strpos($comment, 'drop') !== false) {
                    return $row->getProperty('.id');
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * hw-offload sur les ports bridge peut faire passer le trafic sans firewall CPU.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function ensureHotspotBridgePortNoHwOffload($client, $bridgeInterface)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $bridgeInterface = trim((string) $bridgeInterface);
        if ($bridgeInterface === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/interface/bridge/port/print'))
                    ->setArgument('.proplist', '.id,interface,hw,bridge')
                    ->setArgument('?bridge', $bridgeInterface)
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $portId = $row->getProperty('.id');
                if ($portId === null || $portId === '') {
                    continue;
                }
                $hw = strtolower(trim((string) $row->getProperty('hw')));
                if ($hw !== 'yes' && $hw !== 'true') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/interface/bridge/port/set'))
                        ->setArgument('numbers', $portId)
                        ->setArgument('hw', 'no')
                );
                $iface = trim((string) $row->getProperty('interface'));
                $actions[] = 'bridge port « ' . ($iface !== '' ? $iface : $portId) . ' » : hw=no';
            }
        } catch (Throwable $e) {
            $errors[] = 'bridge port hw-offload : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'bridge port hw-offload : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Réapplique les réglages bridge hotspot au démarrage du routeur.
     */
    private static function ensureHotspotBridgeBootScript($client, $interface)
    {
        $interface = trim((string) $interface);
        if ($interface === '') {
            return;
        }

        $escapedIface = str_replace(['\\', '"'], '', $interface);
        $onEvent = ':delay 5s' . "\r\n"
            . '/interface bridge settings set use-ip-firewall=yes use-ip-firewall-for-vlan=no allow-fast-path=no' . "\r\n"
            . '/interface bridge set ' . $escapedIface . ' fast-forward=no' . "\r\n"
            . '/interface bridge port set [find bridge=' . $escapedIface . '] hw=no' . "\r\n"
            . '/interface enable [find where name~"^(wlan|wifi)"]' . "\r\n"
            . '/interface wireless set [find where name~"^wlan"] disabled=no mode=ap-bridge bridge-mode=disabled vlan-mode=no-tag' . "\r\n"
            . ':if ([:len [/interface wifi find]]>0) do={' . "\r\n"
            . '  :if ([:len [/interface wifi datapath find where name="dyrsia-hotspot-dp"]]=0) do={ /interface wifi datapath add name=dyrsia-hotspot-dp bridge=' . $escapedIface . ' }' . "\r\n"
            . '  :if ([:len [/interface wifi configuration find where name="dyrsia-hotspot-ap"]]=0) do={ /interface wifi configuration add name=dyrsia-hotspot-ap mode=ap datapath=dyrsia-hotspot-dp }' . "\r\n"
            . '  :foreach i in=[/interface wifi find] do={ /interface wifi set $i configuration=dyrsia-hotspot-ap disabled=no }' . "\r\n"
            . '  /interface bridge port remove [find where interface~"^wifi"]' . "\r\n"
            . '}' . "\r\n"
            . ':local gw [/ip address get [find interface=' . $escapedIface . '] address]' . "\r\n"
            . ':if ([:len $gw]>0) do={ :set gw [:pick $gw 0 [:find $gw "/"]] }' . "\r\n"
            . '/ip firewall filter add place-before=[find where chain=input] chain=input action=accept protocol=udp dst-port=67 in-interface=!ether1 comment="DYRSIA hotspot DHCP broad" disabled=no' . "\r\n"
            . '/ip firewall filter add place-before=[find where chain=input] chain=input action=accept protocol=udp dst-port=68 in-interface=!ether1 comment="DYRSIA hotspot DHCP broad" disabled=no';

        try {
            $schedulerName = 'dyrsia_hs_boot';
            foreach ($client->sendSync(
                (new RouterOS\Request('/system/script/print'))
                    ->setArgument('.proplist', '.id')
                    ->setArgument('?name', 'dyrsia_hs_boot')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $scriptId = $row->getProperty('.id');
                if ($scriptId !== null && $scriptId !== '') {
                    $client->sendSync(
                        (new RouterOS\Request('/system/script/remove'))
                            ->setArgument('numbers', $scriptId)
                    );
                }
            }

            $schedulerId = self::routerEntityId($client, '/system/scheduler', 'name', $schedulerName);
            if ($schedulerId !== null) {
                $client->sendSync(
                    (new RouterOS\Request('/system/scheduler/set'))
                        ->setArgument('numbers', $schedulerId)
                        ->setArgument('on-event', $onEvent)
                        ->setArgument('start-time', 'startup')
                        ->setArgument('interval', '0s')
                        ->setArgument('comment', 'DYRSIA hotspot bridge hardening')
                );
            } else {
                $client->sendSync(
                    (new RouterOS\Request('/system/scheduler/add'))
                        ->setArgument('name', $schedulerName)
                        ->setArgument('on-event', $onEvent)
                        ->setArgument('start-time', 'startup')
                        ->setArgument('interval', '0s')
                        ->setArgument('comment', 'DYRSIA hotspot bridge hardening')
                );
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * DHCP client (UDP 67/68) doit passer en walled-garden quand use-ip-firewall=yes.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotWalledGardenDhcp($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $comment = 'DYRSIA hotspot DHCP';
        $actions = [];
        $errors = [];
        $rules = [
            ['protocol' => 'udp', 'dst-port' => '67'],
            ['protocol' => 'udp', 'dst-port' => '68'],
        ];

        try {
            foreach ($rules as $rule) {
                $exists = false;
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/walled-garden/ip/print'))
                        ->setArgument('.proplist', 'protocol,dst-port,comment')
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    if ((string) $row->getProperty('comment') === $comment
                        && (string) $row->getProperty('protocol') === $rule['protocol']
                        && (string) $row->getProperty('dst-port') === $rule['dst-port']) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/walled-garden/ip/add'))
                        ->setArgument('action', 'accept')
                        ->setArgument('protocol', $rule['protocol'])
                        ->setArgument('dst-port', $rule['dst-port'])
                        ->setArgument('comment', $comment)
                );
                $actions[] = 'walled-garden DHCP udp/' . $rule['dst-port'];
            }
        } catch (Throwable $e) {
            self::mikrotikRethrowIfRetriable($e);
            $errors[] = 'walled-garden DHCP : ' . $e->getMessage();
        } catch (Exception $e) {
            self::mikrotikRethrowIfRetriable($e);
            $errors[] = 'walled-garden DHCP : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Vérifie que les chaînes firewall hotspot dynamiques existent.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function isHotspotFirewallChain($chain, $comment = '')
    {
        $chain = strtolower((string) $chain);
        $comment = strtolower((string) $comment);
        if (strpos($chain, 'hotspot') !== false || strpos($comment, 'hotspot') !== false) {
            return true;
        }

        return preg_match('/^hs(-|$)/', $chain) === 1 || $chain === 'pre-hotspot';
    }

    private static function hotspotFirewallAnchorsPresent($client)
    {
        $hasFilter = false;
        $hasNat = false;
        foreach ($client->sendSync(
            (new RouterOS\Request('/ip/firewall/filter/print'))
                ->setArgument('.proplist', 'chain,comment,dynamic')
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $chain = (string) $row->getProperty('chain');
            $comment = (string) $row->getProperty('comment');
            if (self::isHotspotFirewallChain($chain, $comment)) {
                $hasFilter = true;
                break;
            }
        }
        foreach ($client->sendSync(
            (new RouterOS\Request('/ip/firewall/nat/print'))
                ->setArgument('.proplist', 'chain,comment,dynamic')
        ) as $row) {
            if ($row->getType() === 'trap') {
                continue;
            }
            $chain = (string) $row->getProperty('chain');
            $comment = (string) $row->getProperty('comment');
            if (self::isHotspotFirewallChain($chain, $comment)) {
                $hasNat = true;
                break;
            }
        }

        return ['filter' => $hasFilter, 'nat' => $hasNat];
    }

    /**
     * Regenere les regles hotspot (redirect HTTP) apres bridge/VLAN/login.html.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotCaptivePortalOperational($client, $hotspotName = '', $gatewayIp = '', $bridgeName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $bridgeName = trim((string) $bridgeName);
        $gatewayIp = trim((string) $gatewayIp);

        global $config;

        $bridgeSettings = self::ensureHotspotBridgeSettings($client);
        $errors = array_merge($errors, $bridgeSettings['errors'] ?? []);
        $actions = array_merge($actions, $bridgeSettings['actions'] ?? []);

        if ($bridgeName !== '') {
            $bridgeFw = self::ensureHotspotBridgeFirewall($client, $bridgeName);
            $errors = array_merge($errors, $bridgeFw['errors'] ?? []);
            $actions = array_merge($actions, $bridgeFw['actions'] ?? []);
        }

        $dhcpWg = self::ensureHotspotWalledGardenDhcp($client);
        $errors = array_merge($errors, $dhcpWg['errors'] ?? []);
        $actions = array_merge($actions, $dhcpWg['actions'] ?? []);

        if ($gatewayIp !== '' && filter_var($gatewayIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $gatewayWg = self::ensureHotspotWalledGardenGateway($client, $gatewayIp);
            $errors = array_merge($errors, $gatewayWg['errors'] ?? []);
            $actions = array_merge($actions, $gatewayWg['actions'] ?? []);
        }

        $inputBypass = self::removeHotspotInputGatewayBypassRules($client);
        $errors = array_merge($errors, $inputBypass['errors'] ?? []);
        $actions = array_merge($actions, $inputBypass['actions'] ?? []);

        $reactivated = self::reactivateHotspotServer($client, $hotspotName);
        if (!empty($reactivated['actions'])) {
            $actions = array_merge($actions, $reactivated['actions']);
        }
        $errors = array_merge($errors, $reactivated['errors'] ?? []);

        if (!self::hotspotHttpRedirectPresent($client)) {
            $reactivated = self::reactivateHotspotServer($client, $hotspotName);
            $actions[] = 'hotspot reactive (regeneration redirect HTTP)';
            $actions = array_merge($actions, $reactivated['actions'] ?? []);
            $errors = array_merge($errors, $reactivated['errors'] ?? []);
            usleep(800000);
        }

        if (!self::hotspotHttpRedirectPresent($client)) {
            $actions[] = 'Redirection HTTP hotspot non detectee — verifiez /ip firewall nat print dynamic et /ip hotspot print sur le routeur.';
        }

        $loginSize = self::getRouterFileSize($client, 'hotspot/login.html');
        if ($loginSize <= 0) {
            $actions[] = 'attention: hotspot/login.html absent (deploye a la fin du Send complet)';
        } elseif ($loginSize < 400) {
            $errors[] = 'hotspot/login.html trop petit (' . $loginSize . ' octets) — page captive probablement incomplete.';
        } else {
            $actions[] = 'login.html present (' . $loginSize . ' octets)';
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * Verification finale apres deploiement login.html (Send complet).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function verifyHotspotCaptivePortalReady($client, $hotspotName = '', $gatewayIp = '', $bridgeName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        $hotspotName = trim((string) $hotspotName);

        $deviceModeBlock = self::hotspotServerDeviceModeStatus($client, $hotspotName);
        if (!empty($deviceModeBlock['blocked'])) {
            $deviceMode = self::ensureHotspotDeviceMode($client);
            $actions = array_merge($actions, $deviceMode['actions'] ?? []);
            if (empty($deviceMode['ok'])) {
                $errors[] = $deviceModeBlock['message'];
                $errors = array_merge($errors, $deviceMode['errors'] ?? []);
            } else {
                $deviceModeBlock = self::hotspotServerDeviceModeStatus($client, $hotspotName);
                if (!empty($deviceModeBlock['blocked'])) {
                    $errors[] = $deviceModeBlock['message'];
                }
            }
        }

        $loginSize = self::getRouterFileSize($client, 'hotspot/login.html');
        if ($loginSize <= 0) {
            $errors[] = 'hotspot/login.html absent sur le routeur apres envoi.';
        } elseif ($loginSize < 400) {
            $errors[] = 'hotspot/login.html trop petit (' . $loginSize . ' octets).';
        } else {
            $actions[] = 'login.html present (' . $loginSize . ' octets)';
        }

        $profileName = self::getHotspotServerProfileName($client, $hotspotName);
        if ($profileName !== '') {
            $htmlDir = self::getHotspotProfileHtmlDirectory($client, $profileName);
            if ($htmlDir !== '' && !self::hotspotHtmlDirectoryIsDyrsia($htmlDir)) {
                $errors[] = 'Profil « ' . $profileName . ' » : html-directory=« ' . $htmlDir
                    . ' » — relancez Send login.html pour basculer sur « hotspot ».';
            } else {
                $actions[] = 'html-directory=hotspot (profil « ' . $profileName . ' »)';
            }
        }

        try {
            $serverFound = false;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', '.id,name,disabled,invalid,.about')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $name = trim((string) $row->getProperty('name'));
                if ($hotspotName !== '' && strcasecmp($name, $hotspotName) !== 0) {
                    continue;
                }
                $serverFound = true;
                if (strtolower(trim((string) $row->getProperty('invalid'))) === 'true') {
                    $about = trim((string) $row->getProperty('.about'));
                    $block = self::hotspotServerDeviceModeStatus($client, $name);
                    if (!empty($block['blocked'])) {
                        $errors[] = $block['message'];
                    } else {
                        $errors[] = 'Serveur hotspot « ' . $name . ' » invalide'
                            . ($about !== '' ? ' : ' . $about : '') . '.';
                    }
                }
                if ((string) $row->getProperty('disabled') === 'true') {
                    $errors[] = 'Serveur hotspot « ' . ($name !== '' ? $name : $hotspotName) . ' » désactivé.';
                }
                break;
            }
            if (!$serverFound) {
                $errors[] = $hotspotName !== ''
                    ? 'Serveur hotspot « ' . $hotspotName . ' » introuvable.'
                    : 'Aucun serveur hotspot sur le routeur.';
            }
        } catch (Throwable $e) {
            $errors[] = 'verification hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'verification hotspot : ' . $e->getMessage();
        }

        if (!self::hotspotHttpRedirectPresent($client)) {
            $actions[] = 'Redirection HTTP hotspot non detectee (regles generees au premier client WiFi).';
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    /**
     * Supprime les règles input qui acceptent tcp/80,443 vers la passerelle hotspot.
     * Elles envoient le client sur 10.10.0.1:80 directement (rien n'écoute) au lieu
     * du serveur login interne (64873) → « connexion refusée » sur /login.
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function removeHotspotInputGatewayBypassRules($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,protocol,dst-port,comment')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if ((string) $row->getProperty('chain') !== 'input') {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('protocol'))) !== 'tcp') {
                    continue;
                }
                $comment = strtolower(trim((string) $row->getProperty('comment')));
                if (strpos($comment, 'hotspot client gateway') === false) {
                    continue;
                }
                $dstPort = trim((string) $row->getProperty('dst-port'));
                if ($dstPort === '' || strpos($dstPort, '80') === false) {
                    continue;
                }
                $ruleId = $row->getProperty('.id');
                if ($ruleId === null || $ruleId === '') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/filter/remove'))
                        ->setArgument('numbers', $ruleId)
                );
                $actions[] = 'input gateway tcp/' . $dstPort . ' supprimé (bloquait portail)';
            }
        } catch (Throwable $e) {
            $errors[] = 'input gateway bypass : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'input gateway bypass : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function ensureHotspotWalledGardenGateway($client, $gatewayIp)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $gatewayIp = trim((string) $gatewayIp);
        if ($gatewayIp === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $comment = 'DYRSIA hotspot gateway';
        $errors = [];
        $actions = [];
        $wgPath = self::hotspotWalledGardenIpPrintPath($client);

        // Do NOT whitelist gateway tcp/80 or tcp/443 in walled-garden: RouterOS
        // turns those into hs-unauth "return" rules that bypass the hotspot login
        // redirect. Clients then hit 10.10.0.1:80 directly where nothing listens
        // (webfig is off 80, login proxy is on internal ports) → captive portal
        // shows blank / connection refused. API access uses :8080 (separate rule).
        try {
            foreach (self::fetchHotspotWalledGardenIpRows($client) as $row) {
                if ((string) ($row['dst-address'] ?? '') !== $gatewayIp) {
                    continue;
                }
                $port = (string) ($row['dst-port'] ?? '');
                if (!in_array($port, ['80', '443'], true)) {
                    continue;
                }
                $id = (string) ($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $client->sendSync(
                    (new RouterOS\Request($wgPath . '/remove'))
                        ->setArgument('numbers', $id)
                );
                $actions[] = 'walled-garden passerelle tcp/' . $port . ' supprime (bloquait portail)';
            }
        } catch (Throwable $e) {
            $errors[] = 'walled-garden passerelle : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'walled-garden passerelle : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    private static function reactivateHotspotServer($client, $hotspotName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $errors = [];
        $actions = [];
        try {
            $serverId = null;
            $hotspotName = trim((string) $hotspotName);
            if ($hotspotName !== '') {
                $serverId = self::routerEntityId($client, '/ip/hotspot', 'name', $hotspotName);
            }
            if ($serverId === null) {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/hotspot/print'))
                        ->setArgument('.proplist', '.id,name,disabled')
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $serverId = $row->getProperty('.id');
                    if ($serverId !== null && $serverId !== '') {
                        break;
                    }
                }
            }
            if ($serverId === null || $serverId === '') {
                $errors[] = 'Serveur hotspot introuvable pour regeneration firewall.';
                return ['ok' => false, 'errors' => $errors, 'actions' => $actions];
            }

            $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/set'))
                    ->setArgument('numbers', $serverId)
                    ->setArgument('disabled', 'yes')
            );
            usleep(500000);
            $client->sendSync(
                (new RouterOS\Request('/ip/hotspot/set'))
                    ->setArgument('numbers', $serverId)
                    ->setArgument('disabled', 'no')
            );
            $actions[] = 'serveur hotspot reactive (regeneration redirect HTTP)';
        } catch (Throwable $e) {
            $errors[] = 'reactivation hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'reactivation hotspot : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function hotspotHttpRedirectPresent($client)
    {
        try {
            $hasHotspotChain = false;
            $hasHsUnauthRedirect = false;
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/nat/print'))
                    ->setArgument('.proplist', 'chain,action,protocol,dst-port,jump-target,comment,dynamic')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $chain = strtolower(trim((string) $row->getProperty('chain')));
                $action = strtolower(trim((string) $row->getProperty('action')));
                $dstPort = trim((string) $row->getProperty('dst-port'));
                $jumpTarget = strtolower(trim((string) $row->getProperty('jump-target')));

                if ($chain === 'hotspot') {
                    if ($action === 'redirect' && in_array($dstPort, ['53', '80', '443', '8080', '3128'], true)) {
                        $hasHotspotChain = true;
                    }
                    if ($action === 'jump' && strpos($jumpTarget, 'hs-') === 0) {
                        $hasHotspotChain = true;
                    }
                }

                if (strpos($chain, 'hs-') === 0 && $action === 'redirect') {
                    if ($dstPort === '' || in_array($dstPort, ['53', '80', '443', '8080', '3128'], true)) {
                        $hasHsUnauthRedirect = true;
                    }
                }
            }

            return $hasHotspotChain || $hasHsUnauthRedirect;
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }

    public static function ensureHotspotFirewallAnchors($client, $hotspotName = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $actions = [];
        $errors = [];
        try {
            $anchors = self::hotspotFirewallAnchorsPresent($client);
            $needsReactivate = !$anchors['filter'] || !$anchors['nat'] || !self::hotspotHttpRedirectPresent($client);
            if ($needsReactivate) {
                $reactivated = self::reactivateHotspotServer($client, $hotspotName);
                $actions = array_merge($actions, $reactivated['actions'] ?? []);
                $errors = array_merge($errors, $reactivated['errors'] ?? []);
                $anchors = self::hotspotFirewallAnchorsPresent($client);
            }

            if (!$anchors['filter'] || !$anchors['nat']) {
                $missing = [];
                if (!$anchors['filter']) {
                    $missing[] = 'filter';
                }
                if (!$anchors['nat']) {
                    $missing[] = 'nat';
                }
                $errors[] = 'Règles firewall hotspot absentes (' . implode(', ', $missing) . ') — '
                    . 'use-ip-firewall=yes requis avant activation du serveur hotspot.';
            }
        } catch (Throwable $e) {
            $errors[] = 'firewall hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'firewall hotspot : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function hotspotDhcpServerName()
    {
        return 'dyrsia-hotspot-dhcp';
    }

    /**
     * Détecte un Hotspot actif sur le routeur (serveur /ip/hotspot, DHCP canonique ou DHCP sur l'interface LAN).
     *
     * @return array{
     *     active: bool,
     *     interface: string,
     *     hotspot_name: string,
     *     pool_name: string,
     *     local_address: string
     * }
     */
    private static function resolveHotspotServiceOnRouter($client, array $config = [])
    {
        $iface = trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot'));
        if ($iface === '') {
            $iface = 'bridge-hotspot';
        }

        $result = [
            'active' => false,
            'interface' => $iface,
            'hotspot_name' => trim((string) ($config['hotspot_name'] ?? '')),
            'pool_name' => trim((string) ($config['hotspot_pool_name'] ?? '')),
            'pool_range' => trim((string) ($config['hotspot_address_pool'] ?? $config['hotspot_pool_range'] ?? '')),
            'local_address' => trim((string) ($config['hotspot_local_address'] ?? '')),
        ];

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/print'))
                    ->setArgument('.proplist', 'name,interface,address-pool,disabled')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('disabled'))) === 'true') {
                    continue;
                }
                $result['active'] = true;
                $hsIface = trim((string) $row->getProperty('interface'));
                if ($hsIface !== '') {
                    $result['interface'] = $hsIface;
                }
                $hsName = trim((string) $row->getProperty('name'));
                if ($hsName !== '' && $result['hotspot_name'] === '') {
                    $result['hotspot_name'] = $hsName;
                }
                $pool = trim((string) $row->getProperty('address-pool'));
                if ($pool !== '' && strtolower($pool) !== 'none' && $result['pool_name'] === '') {
                    $result['pool_name'] = $pool;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        if (self::routerEntityId($client, '/ip/dhcp-server', 'name', self::hotspotDhcpServerName()) !== null) {
            $result['active'] = true;
        }

        if (!$result['active']) {
            try {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/dhcp-server/print'))
                        ->setArgument('.proplist', 'name,interface,disabled')
                        ->setQuery(RouterOS\Query::where('interface', $result['interface']))
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    if (strtolower(trim((string) $row->getProperty('disabled'))) === 'true') {
                        continue;
                    }
                    $result['active'] = true;
                    break;
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        if ($result['local_address'] === '') {
            try {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/address/print'))
                        ->setArgument('.proplist', 'address,interface')
                        ->setQuery(RouterOS\Query::where('interface', $result['interface']))
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $addr = trim((string) $row->getProperty('address'));
                    if ($addr !== '' && strpos($addr, '/') !== false) {
                        $result['local_address'] = $addr;
                        break;
                    }
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        if ($result['pool_name'] === '') {
            $resolvedPool = self::resolveHotspotServerPoolName(
                $client,
                $result['interface'],
                $result['hotspot_name']
            );
            if ($resolvedPool !== '') {
                $result['pool_name'] = $resolvedPool;
            }
        }

        if ($result['pool_range'] === '' && $result['pool_name'] !== '') {
            $result['pool_range'] = self::resolveHotspotPoolRangeFromRouter($client, $result['pool_name']);
        }

        return $result;
    }

    private static function routerHasHotspotCoexistence($client, array $config = [])
    {
        return !empty(self::resolveHotspotServiceOnRouter($client, $config)['active']);
    }

    /**
     * Complète la config Hotspot depuis le routeur (PPPoE deploy n'envoie pas toujours tous les champs).
     *
     * @return array<string, mixed>
     */
    private static function mergeHotspotCoexistenceConfig(array $config, array $resolved)
    {
        if (trim((string) ($config['hotspot_interface'] ?? '')) === '' && !empty($resolved['interface'])) {
            $config['hotspot_interface'] = (string) $resolved['interface'];
        }
        if (trim((string) ($config['hotspot_name'] ?? '')) === '' && !empty($resolved['hotspot_name'])) {
            $config['hotspot_name'] = (string) $resolved['hotspot_name'];
        }
        if (trim((string) ($config['hotspot_pool_name'] ?? '')) === '' && !empty($resolved['pool_name'])) {
            $config['hotspot_pool_name'] = (string) $resolved['pool_name'];
        }
        if (trim((string) ($config['hotspot_local_address'] ?? '')) === '' && !empty($resolved['local_address'])) {
            $config['hotspot_local_address'] = (string) $resolved['local_address'];
        }
        if (trim((string) ($config['hotspot_address_pool'] ?? '')) === '' && !empty($resolved['pool_range'])) {
            $config['hotspot_address_pool'] = (string) $resolved['pool_range'];
        }

        return $config;
    }

    private static function resolveHotspotLocalAddressFromRouter($client, $interface)
    {
        $interface = trim((string) $interface);
        if ($interface === '') {
            return '';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/address/print'))
                    ->setArgument('.proplist', 'address,interface')
                    ->setQuery(RouterOS\Query::where('interface', $interface))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $addr = trim((string) $row->getProperty('address'));
                if ($addr !== '' && strpos($addr, '/') !== false) {
                    return $addr;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    private static function resolveHotspotPoolRangeFromRouter($client, $poolName)
    {
        $poolName = trim((string) $poolName);
        if ($poolName === '') {
            return '';
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/pool/print'))
                    ->setArgument('.proplist', 'name,ranges')
                    ->setQuery(RouterOS\Query::where('name', $poolName))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $ranges = trim((string) $row->getProperty('ranges'));
                if ($ranges !== '') {
                    return $ranges;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * @return array{address: string, gateway: string}|null
     */
    private static function resolveHotspotDhcpNetworkFromRouter($client, $interface, $localAddress = '')
    {
        $network = self::parseHotspotLocalNetwork($localAddress);
        if ($network !== null) {
            return $network;
        }

        $localAddress = self::resolveHotspotLocalAddressFromRouter($client, $interface);
        $network = self::parseHotspotLocalNetwork($localAddress);
        if ($network !== null) {
            return $network;
        }

        $gateway = explode('/', $localAddress, 2)[0] ?? '';
        if ($gateway === '') {
            return null;
        }

        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/dhcp-server/network/print'))
                    ->setArgument('.proplist', 'address,gateway')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (trim((string) $row->getProperty('gateway')) === $gateway) {
                    $address = trim((string) $row->getProperty('address'));
                    if ($address !== '') {
                        return [
                            'address' => $address,
                            'gateway' => $gateway,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Replace une règle firewall existante avant le premier drop/reject si PPPoE l'a repoussée.
     */
    private static function repositionFirewallFilterRuleBeforeDrops($client, $chain, $ruleId)
    {
        $chain = trim((string) $chain);
        $ruleId = trim((string) $ruleId);
        if ($chain === '' || $ruleId === '') {
            return false;
        }

        $dropId = self::findFirewallDropRuleId($client, $chain);
        if ($dropId === null || $dropId === '' || $dropId === $ruleId) {
            return false;
        }

        $ruleIndex = null;
        $dropIndex = null;
        $index = 0;
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain')
                    ->setQuery(RouterOS\Query::where('chain', $chain))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $id = (string) $row->getProperty('.id');
                if ($id === $ruleId) {
                    $ruleIndex = $index;
                }
                if ($id === $dropId) {
                    $dropIndex = $index;
                }
                $index++;
            }
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        if ($ruleIndex === null || $dropIndex === null || $ruleIndex <= $dropIndex) {
            return false;
        }

        try {
            $client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/move'))
                    ->setArgument('numbers', $ruleId)
                    ->setArgument('destination', $dropId)
            );

            return true;
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Aligne address-pool du serveur /ip hotspot sur le pool DHCP DYRSIA.
     */
    private static function ensureHotspotServerPool($client, $poolName, $hotspotName = '', $interface = '')
    {
        $poolName = trim((string) $poolName);
        $hotspotName = trim((string) $hotspotName);
        $interface = trim((string) $interface);
        if ($poolName === '' || strtolower($poolName) === 'none') {
            return;
        }

        $serverId = null;
        if ($hotspotName !== '') {
            $serverId = self::routerEntityId($client, '/ip/hotspot', 'name', $hotspotName);
        }
        if ($serverId === null && $interface !== '') {
            $serverId = self::routerEntityId($client, '/ip/hotspot', 'interface', $interface);
        }
        if ($serverId === null) {
            return;
        }

        $client->sendSync(
            (new RouterOS\Request('/ip/hotspot/set'))
                ->setArgument('numbers', $serverId)
                ->setArgument('address-pool', $poolName)
        );
    }

    /**
     * Pool réellement utilisé par le serveur hotspot sur le routeur.
     */
    private static function resolveHotspotServerPoolName($client, $interface = '', $hotspotName = '')
    {
        $interface = trim((string) $interface);
        $hotspotName = trim((string) $hotspotName);

        try {
            $request = (new RouterOS\Request('/ip/hotspot/print'))
                ->setArgument('.proplist', 'name,interface,address-pool');
            if ($hotspotName !== '') {
                $request->setQuery(RouterOS\Query::where('name', $hotspotName));
            } elseif ($interface !== '') {
                $request->setQuery(RouterOS\Query::where('interface', $interface));
            } else {
                return '';
            }

            foreach ($client->sendSync($request) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $pool = trim((string) $row->getProperty('address-pool'));
                if ($pool !== '' && strtolower($pool) !== 'none') {
                    return $pool;
                }
            }
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return '';
    }

    /**
     * Serveur DHCP canonique : dyrsia-hotspot-dhcp (pool = config DYRSIA / hotspot-pool).
     * Désactive les anciens serveurs (ex. dhcp + defautl-dhcp).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotDhcpServer($client, $interface, $poolName = '', $localAddress = '', $hotspotName = '', $poolRange = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $interface = trim((string) $interface);
        $poolName = trim((string) $poolName);
        $poolRange = trim((string) $poolRange);
        $dhcpName = self::hotspotDhcpServerName();
        if ($interface === '') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        if ($localAddress === '') {
            $localAddress = self::resolveHotspotLocalAddressFromRouter($client, $interface);
        }

        if ($poolName === '') {
            $dyrsiaId = self::routerEntityId($client, '/ip/dhcp-server', 'name', $dhcpName);
            if ($dyrsiaId !== null) {
                try {
                    foreach ($client->sendSync(
                        (new RouterOS\Request('/ip/dhcp-server/print'))
                            ->setArgument('.proplist', 'address-pool')
                            ->setQuery(RouterOS\Query::where('name', $dhcpName))
                    ) as $row) {
                        if ($row->getType() === 'trap') {
                            continue;
                        }
                        $poolName = trim((string) $row->getProperty('address-pool'));
                        break;
                    }
                } catch (Throwable $e) {
                } catch (Exception $e) {
                }
            }
        }
        if ($poolName === '') {
            $poolName = self::resolveHotspotServerPoolName($client, $interface, $hotspotName);
        }

        if ($poolName === '' || strtolower($poolName) === 'none') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        if ($poolRange === '') {
            $poolRange = self::resolveHotspotPoolRangeFromRouter($client, $poolName);
        }

        $network = self::resolveHotspotDhcpNetworkFromRouter($client, $interface, $localAddress);
        $errors = [];
        $actions = [];

        try {
            if ($poolRange !== '') {
                self::setPool($client, $poolName, $poolRange);
                $actions[] = 'pool DHCP « ' . $poolName . ' » (' . $poolRange . ')';
            }

            $dyrsiaId = self::routerEntityId($client, '/ip/dhcp-server', 'name', $dhcpName);
            if ($dyrsiaId !== null) {
                $client->sendSync(
                    (new RouterOS\Request('/ip/dhcp-server/set'))
                        ->setArgument('numbers', $dyrsiaId)
                        ->setArgument('interface', $interface)
                        ->setArgument('address-pool', $poolName)
                        ->setArgument('lease-time', '30m')
                        ->setArgument('disabled', 'no')
                );
                $actions[] = 'DHCP « ' . $dhcpName . ' » actif (pool « ' . $poolName . ' »)';
            } else {
                $client->sendSync(
                    (new RouterOS\Request('/ip/dhcp-server/add'))
                        ->setArgument('name', $dhcpName)
                        ->setArgument('interface', $interface)
                        ->setArgument('address-pool', $poolName)
                        ->setArgument('lease-time', '30m')
                        ->setArgument('disabled', 'no')
                );
                $actions[] = 'DHCP « ' . $dhcpName . ' » créé (pool « ' . $poolName . ' »)';
            }

            self::ensureHotspotServerPool($client, $poolName, $hotspotName, $interface);

            if ($network !== null) {
                $networkId = self::routerEntityId($client, '/ip/dhcp-server/network', 'address', $network['address']);
                $networkRequest = (new RouterOS\Request($networkId ? '/ip/dhcp-server/network/set' : '/ip/dhcp-server/network/add'))
                    ->setArgument('address', $network['address'])
                    ->setArgument('gateway', $network['gateway'])
                    ->setArgument('dns-server', $network['gateway']);
                if ($networkId) {
                    $networkRequest->setArgument('numbers', $networkId);
                }
                $client->sendSync($networkRequest);
                $actions[] = 'réseau DHCP « ' . $network['address'] . ' » (passerelle ' . $network['gateway'] . ')';
            } else {
                $errors[] = 'DHCP hotspot : réseau introuvable pour « ' . $interface
                    . ' » — configurez hotspot_local_address (ex. 10.10.0.1/24).';
            }

            if ($network !== null) {
                foreach ($client->sendSync(
                    (new RouterOS\Request('/ip/dhcp-server/print'))
                        ->setArgument('.proplist', '.id,name,interface,disabled')
                        ->setQuery(RouterOS\Query::where('interface', $interface))
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    $name = trim((string) $row->getProperty('name'));
                    if ($name === $dhcpName) {
                        continue;
                    }
                    $id = $row->getProperty('.id');
                    if ($id === null || $id === '') {
                        continue;
                    }
                    if (strtolower(trim((string) $row->getProperty('disabled'))) === 'true') {
                        continue;
                    }
                    $client->sendSync(
                        (new RouterOS\Request('/ip/dhcp-server/set'))
                            ->setArgument('numbers', $id)
                            ->setArgument('disabled', 'yes')
                    );
                    $actions[] = 'DHCP legacy « ' . ($name !== '' ? $name : $interface) . ' » désactivé';
                }
            }

            $dhcpFwResult = self::ensureHotspotDhcpFirewallPass($client, $interface);
            $errors = array_merge($errors, $dhcpFwResult['errors'] ?? []);
            $actions = array_merge($actions, $dhcpFwResult['actions'] ?? []);
        } catch (Throwable $e) {
            self::mikrotikRethrowIfRetriable($e);
            $errors[] = 'DHCP hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            self::mikrotikRethrowIfRetriable($e);
            $errors[] = 'DHCP hotspot : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function removeHotspotIpBindingBypass($client)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $removed = 0;
        try {
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/hotspot/ip-binding/print'))
                    ->setArgument('.proplist', '.id,type')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (strtolower(trim((string) $row->getProperty('type'))) === 'bypass') {
                    $removed++;
                }
            }
            if ($removed > 0) {
                self::runRouterOneShotScript(
                    $client,
                    'dyrsia_hs_nobypass',
                    '/ip hotspot ip-binding remove [find type=bypass]'
                );
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'errors' => ['ip-binding bypass : ' . $e->getMessage()], 'actions' => []];
        } catch (Exception $e) {
            return ['ok' => false, 'errors' => ['ip-binding bypass : ' . $e->getMessage()], 'actions' => []];
        }

        $actions = [];
        if ($removed > 0) {
            $actions[] = 'ip-binding bypass supprimés (' . $removed . ')';
        }

        return ['ok' => true, 'errors' => [], 'actions' => $actions];
    }

    /**
     * Supprime les règles forward manuelles qui contournent le hotspot (accept LAN → WAN).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function removeHotspotBypassForwardRules($client, $localAddress = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => true, 'errors' => [], 'actions' => []];
        }

        $network = self::parseHotspotLocalNetwork($localAddress);
        $hotspotNetwork = $network !== null ? (string) ($network['address'] ?? '') : '';

        $errors = [];
        $actions = [];
        try {
            $toRemove = [];
            foreach ($client->sendSync(
                (new RouterOS\Request('/ip/firewall/filter/print'))
                    ->setArgument('.proplist', '.id,chain,action,comment,src-address,src-address-list,dynamic,disabled,hotspot,connection-state,in-interface')
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                if (!self::isHotspotBypassForwardRule($row, $hotspotNetwork)) {
                    continue;
                }
                $ruleId = $row->getProperty('.id');
                if ($ruleId !== null && $ruleId !== '') {
                    $toRemove[] = $ruleId;
                }
            }
            foreach ($toRemove as $ruleId) {
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/filter/remove'))
                        ->setArgument('numbers', $ruleId)
                );
            }
            if (!empty($toRemove)) {
                $actions[] = 'règles forward bypass hotspot supprimées (' . count($toRemove) . ')';
            }
        } catch (Throwable $e) {
            $errors[] = 'forward bypass hotspot : ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'forward bypass hotspot : ' . $e->getMessage();
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function isHotspotBypassForwardRule($row, $hotspotNetwork)
    {
        if ((string) $row->getProperty('chain') !== 'forward') {
            return false;
        }
        if ((string) $row->getProperty('action') !== 'accept') {
            return false;
        }
        if ((string) $row->getProperty('dynamic') === 'true') {
            return false;
        }
        if ((string) $row->getProperty('disabled') === 'true') {
            return false;
        }

        $comment = strtolower(trim((string) $row->getProperty('comment')));
        foreach ([
            'bridge lan to internet',
            'allow all forward',
            'permettre tout',
            'place hotspot rules here',
        ] as $needle) {
            if ($comment !== '' && strpos($comment, $needle) !== false) {
                return true;
            }
        }

        if (strpos($comment, 'pppoe-expired') !== false || strpos($comment, 'dyrsia-pppoe') !== false) {
            return false;
        }
        if (trim((string) $row->getProperty('src-address-list')) === 'pppoe-expired') {
            return false;
        }

        $hotspot = trim((string) $row->getProperty('hotspot'));
        if ($hotspot !== '' && stripos($hotspot, 'auth') !== false) {
            return false;
        }

        $connectionState = trim((string) $row->getProperty('connection-state'));
        if ($connectionState !== '') {
            return false;
        }

        $srcAddress = trim((string) $row->getProperty('src-address'));
        $srcList = trim((string) $row->getProperty('src-address-list'));
        $inInterface = trim((string) $row->getProperty('in-interface'));

        if ($srcAddress === '' && $srcList === '' && $inInterface === '') {
            return true;
        }

        if ($hotspotNetwork !== '' && $srcAddress !== '' && self::hotspotNetworkMatchesRuleSrc($srcAddress, $hotspotNetwork)) {
            return true;
        }

        return false;
    }

    private static function hotspotNetworkMatchesRuleSrc($ruleSrc, $hotspotNetwork)
    {
        $ruleSrc = trim((string) $ruleSrc);
        $hotspotNetwork = trim((string) $hotspotNetwork);
        if ($ruleSrc === $hotspotNetwork) {
            return true;
        }

        if (!preg_match('#^(\d+\.\d+\.\d+\.\d+)/(\d+)$#', $ruleSrc, $ruleMatch)
            || !preg_match('#^(\d+\.\d+\.\d+\.\d+)/(\d+)$#', $hotspotNetwork, $netMatch)) {
            return false;
        }

        $rulePrefix = (int) $ruleMatch[2];
        $netPrefix = (int) $netMatch[2];
        if ($rulePrefix > $netPrefix) {
            return false;
        }

        $ruleLong = ip2long($ruleMatch[1]);
        $netLong = ip2long($netMatch[1]);
        if ($ruleLong === false || $netLong === false) {
            return false;
        }

        $mask = $rulePrefix === 0 ? 0 : (-1 << (32 - $rulePrefix)) & 0xFFFFFFFF;

        return ($ruleLong & $mask) === ($netLong & $mask);
    }

    /**
     * Garantit que le trafic hotspot passe bien par le pare-feu captive (bridge + DHCP + bypass).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotInterceptIntegrity($client, $interface, $localAddress = '', $poolName = '', $hotspotName = '', $poolRange = '')
    {
        $errors = [];
        $actions = [];
        foreach ([
            self::ensureHotspotBridgeFirewall($client, $interface),
            self::ensureHotspotWalledGardenDhcp($client),
            self::ensureHotspotFirewallAnchors($client, $hotspotName),
            self::ensureHotspotDhcpServer($client, $interface, $poolName, $localAddress, $hotspotName, $poolRange),
            self::removeHotspotBypassForwardRules($client, $localAddress),
            self::removeHotspotIpBindingBypass($client),
        ] as $result) {
            $errors = array_merge($errors, $result['errors'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'actions' => $actions];
    }

    private static function ensureHotspotServerEntry($client, $hotspotName, $interface, $profileName, $poolName, $addressPerMac = '1')
    {
        $serverId = self::routerEntityId($client, '/ip/hotspot', 'name', $hotspotName);
        if ($serverId === null) {
            $serverId = self::routerEntityId($client, '/ip/hotspot', 'interface', $interface);
        }

        $addressPerMac = trim((string) $addressPerMac);
        if ($addressPerMac === '' || !ctype_digit($addressPerMac)) {
            $addressPerMac = '1';
        }

        $args = [
            'name' => $hotspotName,
            'interface' => $interface,
            'profile' => $profileName,
            'address-pool' => $poolName,
            'addresses-per-mac' => $addressPerMac,
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

    private static function ensureHotspotSrcNatMasquerade($client, $hotspotInterface, $localAddress = '')
    {
        $hotspotInterface = trim((string) $hotspotInterface);
        $localAddress = trim((string) $localAddress);
        $network = self::parseHotspotLocalNetwork($localAddress);
        $srcNetwork = $network !== null ? $network['address'] : '';
        $wanInterface = self::resolveWanOutInterface($client);
        $comment = 'DYRSIA hotspot masquerade';

        $responses = $client->sendSync(
            (new RouterOS\Request('/ip/firewall/nat/print'))
                ->setArgument('.proplist', '.id,comment,action,out-interface,src-address')
        );
        foreach ($responses as $row) {
            if ((string) $row->getProperty('comment') !== $comment
                || (string) $row->getProperty('action') !== 'masquerade') {
                continue;
            }
            $existingOut = trim((string) $row->getProperty('out-interface'));
            $existingSrc = trim((string) $row->getProperty('src-address'));
            if ($existingOut === $wanInterface
                && ($srcNetwork === '' || $existingSrc === '' || $existingSrc === $srcNetwork)) {
                return;
            }
            $id = $row->getProperty('.id');
            if ($id !== null && $id !== '') {
                $client->sendSync(
                    (new RouterOS\Request('/ip/firewall/nat/remove'))
                        ->setArgument('numbers', $id)
                );
            }
        }

        $addRequest = (new RouterOS\Request('/ip/firewall/nat/add'))
            ->setArgument('chain', 'srcnat')
            ->setArgument('action', 'masquerade')
            ->setArgument('comment', $comment)
            ->setArgument('out-interface', $wanInterface);
        if ($srcNetwork !== '') {
            $addRequest->setArgument('src-address', $srcNetwork);
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
            'pppoe_setup_bridge_ports' => 'ether7,ether8',
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
        return [
            'on-up' => self::pppoeExpiredProfileOnUpScript($listName),
            'on-down' => self::pppoeExpiredProfileOnDownScript($listName),
        ];
    }

    /**
     * Lit bridge, pools, profils PPP et serveur PPPoE pour l'assistant PPPoE Setup.
     */
    public static function fetchPppoeSetupSnapshot($client, $lightweight = false)
    {
        $snapshot = [
            'ok' => true,
            'interfaces' => [],
            'physical_ports' => [],
            'wireless_ports' => [],
            'physical_port_count' => 0,
            'port_bridge_map' => [],
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
                if ($row->getType() === 'trap') {
                    continue;
                }
                $serviceName = trim((string) $row->getProperty('service-name'));
                $iface = trim((string) $row->getProperty('interface'));
                if ($serviceName === '' && $iface === '') {
                    continue;
                }
                $snapshot['servers'][] = [
                    'service_name' => $serviceName,
                    'interface' => $iface,
                    'default_profile' => trim((string) $row->getProperty('default-profile')),
                    'disabled' => self::isRouterOsDisabledFlag($row->getProperty('disabled')),
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

        if (!$lightweight) {
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
        }

        if (!$lightweight) {
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

        if (!$lightweight) {
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

        foreach ($snapshot['interfaces'] as $iface) {
            $name = (string) ($iface['name'] ?? '');
            $type = (string) ($iface['type'] ?? '');
            if ($name === '') {
                continue;
            }
            if ($type === 'wlan' || preg_match('/^wlan/i', $name)) {
                $snapshot['wireless_ports'][] = $name;
                continue;
            }
            if (in_array($type, ['ether', 'sfp', 'bond'], true) || preg_match('/^ether/i', $name)) {
                $snapshot['physical_ports'][] = $name;
            }
        }
        $snapshot['physical_port_count'] = count($snapshot['physical_ports']);
        foreach ($snapshot['bridge_ports'] as $bridge => $ports) {
            foreach ($ports as $portName) {
                $snapshot['port_bridge_map'][$portName] = $bridge;
            }
        }

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

    /**
     * Valeurs par défaut bridges séparés (Management / Hotspot / PPPoE).
     *
     * @return array<string, string>
     */
    public static function serviceBridgeDefaults()
    {
        return [
            'lan_wan_interface' => 'ether1',
            'lan_management_bridge_name' => 'bridge-management',
            'lan_management_interface' => 'ether2',
            'lan_management_address' => '10.99.99.1/24',
            'lan_hotspot_access_ports' => 'ether3,wlan1',
            'hotspot_bridge_ports' => 'ether3,wlan1',
            'lan_pppoe_access_ports' => 'ether7,ether8',
            'lan_unused_ports' => '',
        ];
    }

    /** @deprecated Utiliser serviceBridgeDefaults() */
    public static function lanTrunkDefaults()
    {
        return self::serviceBridgeDefaults();
    }

    /** @deprecated Le mode trunk VLAN n'existe plus */
    public static function lanTrunkEnabled(array $config)
    {
        return false;
    }

    public static function resolveLanBridgeName(array $config)
    {
        $name = trim((string) ($config['hotspot_interface'] ?? ''));

        return $name !== '' ? $name : 'bridge-hotspot';
    }

    /**
     * Ports physiques / WiFi du bridge Hotspot.
     *
     * @return array<int, string>
     */
    public static function resolveHotspotBridgePorts(array $config)
    {
        $keys = ['hotspot_bridge_ports', 'lan_hotspot_access_ports'];
        $ports = [];
        foreach ($keys as $key) {
            $raw = trim((string) ($config[$key] ?? ''));
            if ($raw === '') {
                continue;
            }
            foreach (preg_split('/[\s,;]+/', $raw) as $port) {
                $port = trim((string) $port);
                if ($port !== '' && !in_array($port, $ports, true)) {
                    $ports[] = $port;
                }
            }
            if ($ports !== []) {
                break;
            }
        }

        if ($ports === []) {
            $defaults = self::serviceBridgeDefaults();
            $fallback = trim((string) ($defaults['lan_hotspot_access_ports'] ?? 'wlan1'));
            foreach (preg_split('/[\s,;]+/', $fallback) as $port) {
                $port = trim((string) $port);
                if ($port !== '' && !in_array($port, $ports, true)) {
                    $ports[] = $port;
                }
            }
        }

        return $ports;
    }

    /** @deprecated Utiliser resolveHotspotBridgePorts() */
    public static function resolveLanTrunkBridgePorts(array $config)
    {
        return self::resolveHotspotBridgePorts($config);
    }

    /**
     * @return array<int, string>
     */
    private static function listActiveWirelessInterfaceNames($client)
    {
        $paths = [
            '/interface/wireless/print',
            '/interface/wifiwave2/print',
            '/interface/wifi/print',
        ];
        $names = [];

        foreach ($paths as $path) {
            try {
                foreach ($client->sendSync(
                    (new RouterOS\Request($path))
                        ->setArgument('.proplist', 'name,disabled')
                ) as $row) {
                    if ($row->getType() === 'trap') {
                        continue;
                    }
                    if ((string) $row->getProperty('disabled') === 'true') {
                        continue;
                    }
                    $name = trim((string) $row->getProperty('name'));
                    if ($name !== '' && !in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        return $names;
    }

    public static function resolvePppoeBridgeName(array $config)
    {
        $name = trim((string) ($config['pppoe_setup_bridge_name'] ?? ''));
        if ($name === '' || strcasecmp($name, 'bridge-lan') === 0) {
            $name = trim((string) ($config['lan_bridge_name'] ?? ''));
        }
        if ($name === '' || strcasecmp($name, 'bridge-lan') === 0) {
            $name = 'bridge-pppoe';
        }

        return $name;
    }

    public static function resolvePppoeServiceInterface(array $config)
    {
        $iface = trim((string) ($config['pppoe_setup_server_interface'] ?? ''));
        if ($iface !== '' && strcasecmp($iface, 'bridge-lan') !== 0) {
            return $iface;
        }

        return self::resolvePppoeBridgeName($config);
    }

    public static function normalizePppoeSetupConfig(array $config)
    {
        $defaults = self::pppoeSetupDefaults();
        foreach ($defaults as $key => $defaultValue) {
            if ((!isset($config[$key]) || $config[$key] === '') && $defaultValue !== '') {
                $config[$key] = $defaultValue;
            }
        }
        $config['pppoe_setup_bridge_name'] = self::resolvePppoeBridgeName($config);
        $iface = trim((string) ($config['pppoe_setup_server_interface'] ?? ''));
        if ($iface === '' || strcasecmp($iface, 'bridge-lan') === 0) {
            $config['pppoe_setup_server_interface'] = $config['pppoe_setup_bridge_name'];
        }

        return $config;
    }

    public static function persistPppoeSetupConfig(array $config)
    {
        foreach (self::pppoeSetupDefaults() as $key => $defaultValue) {
            if (!array_key_exists($key, $config)) {
                continue;
            }
            $value = $config[$key];
            if ($key === 'pppoe_setup_router' && trim((string) $value) === '') {
                continue;
            }
            $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if ($row) {
                $row->value = $value;
                $row->save();
            } else {
                $row = ORM::for_table('tbl_appconfig')->create();
                $row->setting = $key;
                $row->value = $value;
                $row->save();
            }
        }
    }

    /**
     * Déploie l'infrastructure PPPoE (bridge, pool, profils, serveur, NAT, firewall).
     *
     * @param array<string, mixed>|object|null $routerRow
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function consolidatePppoeRouterSetup($client, array $config, $routerRow, $admin = null)
    {
        global $_app_stage;
        if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
            return ['ok' => false, 'errors' => ['Indisponible en mode démo.'], 'actions' => []];
        }

        $config = self::normalizePppoeSetupConfig($config);
        $routerArray = is_array($routerRow)
            ? $routerRow
            : (is_object($routerRow) && method_exists($routerRow, 'as_array') ? $routerRow->as_array() : (array) $routerRow);

        $defaults = self::pppoeSetupDefaults();
        $get = static function ($key) use ($config, $defaults) {
            return trim((string) ($config[$key] ?? $defaults[$key] ?? ''));
        };

        $bridgeName = self::resolvePppoeBridgeName($config);
        $bridgePorts = array_values(array_unique(array_filter(array_map('trim', explode(',', $get('pppoe_setup_bridge_ports'))))));
        $gateway = $get('pppoe_setup_gateway');
        $poolName = $get('pppoe_setup_pool_name');
        $poolRange = $get('pppoe_setup_pool_range');
        $profileDefault = $get('pppoe_setup_profile_default') ?: 'default';
        $profileExpire = $get('pppoe_setup_profile_expire') ?: 'EXPIRE';
        $routerName = $get('pppoe_setup_router');
        $expireRate = self::resolvePppoeExpireRateLimit($routerName, $admin);
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
            return ['ok' => false, 'errors' => ['Nom du bridge PPPoE manquant.'], 'actions' => []];
        }
        if (empty($bridgePorts)) {
            return ['ok' => false, 'errors' => ['Ports bridge PPPoE manquants.'], 'actions' => []];
        }

        $hotspotPorts = self::parseInterfacePortsList($config['hotspot_bridge_ports'] ?? '');
        if ($hotspotPorts === [] && trim((string) ($config['lan_hotspot_access_ports'] ?? '')) !== '') {
            $hotspotPorts = self::parseInterfacePortsList($config['lan_hotspot_access_ports']);
        }
        $mgmtPorts = self::parseInterfacePortsList($config['lan_management_interface'] ?? 'ether2');
        if ($mgmtPorts === []) {
            $mgmtPorts = ['ether2'];
        }
        $portConflict = self::validateServicePortIsolation($bridgePorts, $hotspotPorts, $mgmtPorts);
        if ($portConflict !== '') {
            return ['ok' => false, 'errors' => [$portConflict], 'actions' => []];
        }

        if ($poolName === '' || $poolRange === '') {
            return ['ok' => false, 'errors' => ['Pool PPPoE manquant (nom et plage IP).'], 'actions' => []];
        }

        $localGateway = self::resolvePoolGatewayAddress([
            'local_ip' => explode('/', $gateway)[0] ?? '',
            'range_ip' => $poolRange,
        ]);
        if ($localGateway === '') {
            $localGateway = explode('/', $gateway)[0] ?? '10.10.10.1';
        }

        $poolCidr = self::resolvePppoePoolNetworkCidr($gateway, $poolRange);
        $blockIface = $bridgeName;
        if ($blockIface === '') {
            $blockIface = 'bridge-pppoe';
        }

        $coreParams = [
            'bridgeName' => $bridgeName,
            'bridgePorts' => $bridgePorts,
            'gateway' => $gateway,
            'gatewayIface' => $bridgeName,
            'poolName' => $poolName,
            'poolRange' => $poolRange,
            'profileDefault' => $profileDefault,
            'profileExpire' => $profileExpire,
            'localGateway' => $localGateway,
            'dnsServers' => $dnsServers,
            'expireRate' => $expireRate,
            'expiredScripts' => self::pppoeExpiredProfileScripts($expiredList),
            'serviceName' => $serviceName,
            'serverInterface' => $serverInterface,
            'oneSession' => $oneSession,
            'maxMru' => $maxMru,
            'maxMtu' => $maxMtu,
            'natMasquerade' => $natMasquerade,
            'natInterface' => $natInterface,
            'skipBridge' => false,
        ];
        $extraParams = [
            'dnsAllowRemote' => $dnsAllowRemote,
            'dnsServers' => $dnsServers,
            'expiredList' => $expiredList,
            'blockIface' => $blockIface,
            'poolCidr' => $poolCidr,
            'hotspotInterface' => trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot')),
            'hotspotConfig' => $config,
        ];

        $actions = [];
        $errors = [];

        if (self::routerHasHotspotCoexistence($client, $config)) {
            try {
                $protect = self::runDeployPhase($routerArray, $client, static function ($apiClient) use ($config) {
                    return self::ensureHotspotDhcpCoexistenceEssential($apiClient, $config);
                }, 45);
                if (!empty($protect['actions'])) {
                    $actions[] = 'Hotspot protégé (avant PPPoE) : '
                        . implode(', ', array_slice($protect['actions'], 0, 4))
                        . (count($protect['actions']) > 4 ? '…' : '');
                }
                if (!empty($protect['errors'])) {
                    $errors = array_merge($errors, $protect['errors']);
                }
            } catch (Throwable $e) {
                $errors[] = 'Protection hotspot avant PPPoE : ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = 'Protection hotspot avant PPPoE : ' . $e->getMessage();
            }
        }

        try {
            $coreResult = self::runDeployPhase($routerArray, $client, static function ($apiClient) use ($coreParams) {
                return self::deployPppoeCoreInfrastructure($apiClient, $coreParams);
            }, 45);
            $actions = array_merge($actions, $coreResult['actions'] ?? []);
            $errors = array_merge($errors, $coreResult['errors'] ?? []);

            $extraActions = self::runDeployPhase($routerArray, $client, static function ($apiClient) use ($extraParams) {
                return self::deployPppoeOptionalExtras($apiClient, $extraParams);
            }, 30);
            if (is_array($extraActions)) {
                $actions = array_merge($actions, $extraActions);
            }

            $coexistResult = self::runDeployPhase($routerArray, $client, static function ($apiClient) use ($config) {
                return self::ensureHotspotCoexistenceAfterPppoe($apiClient, $config);
            }, 45);
            if (!empty($coexistResult['actions'])) {
                $actions[] = 'Hotspot protégé après infra PPPoE : ' . implode(', ', $coexistResult['actions']);
            }
            if (!empty($coexistResult['errors'])) {
                $errors = array_merge($errors, $coexistResult['errors']);
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
        ];
    }

    /**
     * Répare Hotspot après déploiement PPPoE (firewall DHCP, walled-garden, serveur DHCP).
     *
     * @return array{ok: bool, errors: array<int, string>, actions: array<int, string>}
     */
    public static function ensureHotspotCoexistenceAfterPppoe($client, array $config)
    {
        return self::ensureHotspotDhcpCoexistenceEssential($client, $config);
    }

    /**
     * Déploiement assistant PPPoE : infrastructure MikroTik + profils forfaits (pas les clients).
     *
     * @param array<string, mixed>|object|null $routerRow
     * @return array{
     *     ok: bool,
     *     errors: array<int, string>,
     *     actions: array<int, string>,
     *     plans?: array<string, mixed>,
     *     captive?: array<string, mixed>
     * }
     */
    public static function deployPppoeComplete($client, array $setupConfig, $routerRow, $admin = null)
    {
        global $config;
        $appConfig = is_array($config) ? $config : [];
        $setupConfig = self::normalizePppoeSetupConfig(array_merge($appConfig, $setupConfig));
        self::resetMikrotikReconnectBudget(6);
        $routerArray = is_array($routerRow)
            ? $routerRow
            : (is_object($routerRow) && method_exists($routerRow, 'as_array') ? $routerRow->as_array() : (array) $routerRow);
        $routerName = trim((string) ($setupConfig['pppoe_setup_router'] ?? ($routerArray['name'] ?? '')));

        WifiZoneHotspot::loadHotspotConfigForDeploy($setupConfig, $routerName);

        $infra = self::consolidatePppoeRouterSetup($client, $setupConfig, $routerArray, $admin);
        $actions = $infra['actions'] ?? [];
        $errors = $infra['errors'] ?? [];

        if (empty($infra['ok']) || $routerName === '') {
            return [
                'ok' => false,
                'errors' => $errors ?: ['Échec infrastructure PPPoE.'],
                'actions' => $actions,
            ];
        }

        if (!$client && !empty($routerArray)) {
            $client = self::openDeployClient($routerArray, 45);
        }

        if (!$client) {
            $detail = self::consumeLastDeployClientError();

            return [
                'ok' => false,
                'errors' => array_merge($errors, [$detail ?: 'Connexion MikroTik impossible pour la sync forfaits.']),
                'actions' => $actions,
            ];
        }

        self::setClientSocketTimeout($client, 45);

        $coexistBefore = self::ensureHotspotCoexistenceAfterPppoe($client, $setupConfig);
        if (!empty($coexistBefore['actions'])) {
            $actions[] = 'Hotspot préservé (avant PPPoE) : ' . implode(', ', $coexistBefore['actions']);
        }
        if (!empty($coexistBefore['errors'])) {
            $errors = array_merge($errors, $coexistBefore['errors']);
        }

        self::ensurePppoeExpiredPlanDb($routerName, $admin);
        $planSync = self::syncPppoePlans($client, $routerName, $admin);
        if (!empty($planSync['upserted'])) {
            $actions[] = 'profils forfaits : ' . (int) $planSync['upserted'];
        }
        if (!empty($planSync['removed'])) {
            $actions[] = 'profils orphelins supprimés : ' . (int) $planSync['removed'];
        }

        $captive = ['ok' => true, 'errors' => []];
        $backendUrl = self::resolvePppoeCaptiveBackendUrl($appConfig);
        $portalUrl = self::buildPppoeCaptivePortalUrl($routerName, $appConfig);
        if ($backendUrl !== '' && $portalUrl !== '') {
            $captive = self::ensurePppoeExpiredCaptive($client, $portalUrl, $backendUrl, $routerName);
            if (!empty($captive['ok'])) {
                $actions[] = 'portail clients expirés';
            }
        }

        $errors = array_merge(
            $errors,
            $planSync['errors'] ?? [],
            $captive['errors'] ?? []
        );

        $coexistence = self::ensureHotspotCoexistenceAfterPppoe($client, $setupConfig);
        if (!empty($coexistence['actions'])) {
            $actions[] = 'Hotspot réparé (après PPPoE) : ' . implode(', ', $coexistence['actions']);
        }
        if (!empty($coexistence['errors'])) {
            $errors = array_merge($errors, $coexistence['errors']);
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'actions' => $actions,
            'plans' => $planSync,
            'captive' => $captive,
        ];
    }
}
