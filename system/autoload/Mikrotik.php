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
        $attempts = [
            ['port' => $port, 'ssl' => false, 'label' => 'API'],
        ];

        if ($port === 8728) {
            $attempts[] = ['port' => 8729, 'ssl' => true, 'label' => 'API-SSL'];
        } elseif ($port === 8729) {
            $attempts[] = ['port' => 8728, 'ssl' => false, 'label' => 'API'];
        }

        return $attempts;
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

        $connectTimeout = max(1, min((int) $timeout, 4));

        foreach ($attempts as $attempt) {
            $reach = self::probeTcp($endpoint['host'], $attempt['port'], $connectTimeout);
            if ($reach !== true) {
                $lastError = new Exception(
                    self::formatMikrotikConnectionHelp($endpoint['host'], $attempt['port'])
                    . ' (' . $reach . ')'
                );
                continue;
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
                        . ' (« ' . $user . ' »). '
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
                . ' (« ' . $user . ' »). '
                . Lang::T('Create or verify a user under System → Users with API rights (group full or api).')
            );
        }

        $detail = $lastError ? $lastError->getMessage() : 'connexion impossible';
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

        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
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

        $util = new RouterOS\Util($client);
        try {
            $util->filePutContents($dstPath, null);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        $mode = stripos($url, 'https://') === 0 ? 'https' : 'http';
        $request = (new RouterOS\Request('/tool/fetch'))
            ->setArgument('url', $url)
            ->setArgument('dst-path', $dstPath)
            ->setArgument('mode', $mode)
            ->setArgument('check-certificate', 'no');

        try {
            $responses = $client->sendSync($request);
        } catch (Throwable $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $status = '';
        $message = '';
        foreach ($responses as $response) {
            $status = $status ?: (string) $response->getProperty('status');
            $message = $message ?: (string) $response->getProperty('message');
            if ($response->getType() === RouterOS\Response::TYPE_ERROR) {
                return trim($message) !== '' ? $message : 'fetch RouterOS error';
            }
        }

        if ($status !== '' && stripos($status, 'fail') !== false) {
            return trim($message) !== '' ? $message : ('fetch status: ' . $status);
        }

        sleep(2);

        $size = self::getRouterFileSize($client, $dstPath);
        if ($size > 0) {
            return null;
        }

        return 'fichier non créé après fetch';
    }

    /**
     * @return int
     */
    public static function getRouterFileSize($client, $path)
    {
        try {
            $responses = $client->sendSync(
                (new RouterOS\Request('/file/print'))
                    ->setArgument('.proplist', 'size')
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

        return 0;
    }

    /**
     * Upload hotspot/login.html — fichiers >4 Ko via /tool fetch (limite API RouterOS).
     *
     * @param array<int, string> $fetchUrls
     * @return array{ok: bool, path?: string, method?: string, errors?: array<int, string>}
     */
    public static function deployHotspotLoginHtml($client, $html, array $fetchUrls = [])
    {
        $html = (string) $html;
        $paths = ['hotspot/login.html', 'login.html'];
        $errors = [];
        $util = new RouterOS\Util($client);

        self::ensureRouterDirectory($client, 'hotspot');

        foreach ($paths as $path) {
            foreach ($fetchUrls as $url) {
                $fetchError = self::fetchUrlToRouterFile($client, $url, $path);
                if ($fetchError === null) {
                    return ['ok' => true, 'path' => $path, 'method' => 'fetch'];
                }
                $errors[] = $path . ' (fetch): ' . $fetchError;
            }

            try {
                $util->filePutContents($path, null);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }

            if ($util->filePutContents($path, $html, true)) {
                return ['ok' => true, 'path' => $path, 'method' => 'api'];
            }

            $errors[] = $path . ': écriture API refusée (' . strlen($html) . ' octets, limite ~4–60 Ko)';
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

        $paths = ['hotspot/' . $filename, $filename];
        $errors = [];
        $util = new RouterOS\Util($client);

        self::ensureRouterDirectory($client, 'hotspot');

        foreach ($paths as $path) {
            foreach ($fetchUrls as $url) {
                $fetchError = self::fetchUrlToRouterFile($client, $url, $path);
                if ($fetchError === null) {
                    return ['ok' => true, 'path' => $path, 'method' => 'fetch'];
                }
                $errors[] = $path . ' (fetch): ' . $fetchError;
            }

            try {
                $util->filePutContents($path, null);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }

            if ($util->filePutContents($path, $binary, true)) {
                return ['ok' => true, 'path' => $path, 'method' => 'api'];
            }

            $errors[] = $path . ': écriture API refusée (' . strlen($binary) . ' octets)';
        }

        return ['ok' => false, 'errors' => $errors];
    }
}
