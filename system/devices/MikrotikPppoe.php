<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikPppoe
{
    /** @var RouterOS\Client|null */
    private static $sharedClient = null;

    public static function useSharedClient($client)
    {
        self::$sharedClient = $client;
    }

    public static function clearSharedClient()
    {
        self::$sharedClient = null;
    }

    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik PPPOE',
            'description' => 'To handle connection between wifizones with Mikrotik PPPOE',
            'author' => 'ibnux',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpwifizones/',
                'Telegram' => 'https://t.me/phpwifizones',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }

    function add_customer($customer, $plan)
    {
        $client = $this->routerClient($plan['routers']);
        $this->removeDuplicatePppoeSecrets($client, $customer);
        $cid = $this->getIdByCustomer($customer, $client);
        $isExp = $this->isExpirePlan($plan) || ORM::for_table('tbl_plans')->select('id')->where('plan_expired', $plan['id'])->find_one();
        if (empty($cid)) {
            $this->addPpoeUser($client, $plan, $customer, $isExp);
            $cid = $this->getIdByCustomer($customer, $client);
        } else {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $networkPass = Password::networkCleartext($customer);
            if ($networkPass !== '') {
                $setRequest->setArgument('password', $networkPass);
            }
            if (!empty($customer['pppoe_username'])) {
                $setRequest->setArgument('name', $customer['pppoe_username']);
            } else {
                $setRequest->setArgument('name', $customer['username']);
            }
            $unsetIP = false;
            if (!empty($customer['pppoe_ip']) && !$isExp) {
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } else {
                $unsetIP = true;
            }
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            $client->sendSync($setRequest);

            if ($unsetIP) {
                $unsetRequest = new RouterOS\Request('/ppp/secret/unset');
                $unsetRequest->setArgument('.id', $cid);
                $unsetRequest->setArgument('value-name', 'remote-address');
                $client->sendSync($unsetRequest);
            }
        }

        $this->removeDuplicatePppoeSecrets($client, $customer, $cid);

        if ($this->isExpirePlan($plan)) {
            $this->suspendPppoeSession($client, $customer, (string) $plan['name_plan'], $plan);
        } else {
            $this->reactivatePppoeSession($client, $customer, $plan);
        }
    }

	function sync_customer($customer, $plan)
    {
        $this->add_customer($customer, $plan);
    }

    function remove_customer($customer, $plan)
    {
        $expiredPlan = $this->resolveExpiredPlan($plan);
        if ($expiredPlan) {
            // Suspension normale : profil EXPIRE + blocage firewall.
            $this->add_customer($customer, $expiredPlan);
            return;
        }

        // Hard delete (compte / forfait sans plan_expired) : couper la session + secret.
        $client = $this->routerClient($plan['routers'] ?? '');
        if (!$client) {
            return;
        }
        $this->forgetExpiredPppoeClient($customer);
        foreach ($this->pppoeLoginNames($customer) as $name) {
            try {
                $this->removePpoeActive($client, $name);
            } catch (Throwable $e) {
            }
            try {
                $this->removeAddressListEntries($client, 'pppoe-expired', $name);
            } catch (Throwable $e) {
            }
            try {
                $this->removePpoeUser($client, $name);
            } catch (Throwable $e) {
            }
        }
    }

    private function isExpirePlan($plan)
    {
        return isset($plan['name_plan']) && strtoupper((string) $plan['name_plan']) === 'EXPIRE';
    }

    private function resolveExpiredPlan($plan)
    {
        if (!empty($plan['plan_expired'])) {
            $expiredPlan = ORM::for_table('tbl_plans')->find_one($plan['plan_expired']);
            if ($expiredPlan) {
                return $expiredPlan->as_array();
            }
        }
        $expiredPlan = ORM::for_table('tbl_plans')
            ->where('type', 'PPPOE')
            ->where('routers', $plan['routers'])
            ->where('name_plan', 'EXPIRE')
            ->find_one();
        return $expiredPlan ? $expiredPlan->as_array() : null;
    }

    private function pppoeLoginNames($customer)
    {
        $names = [];
        if (!empty($customer['pppoe_username'])) {
            $names[] = $customer['pppoe_username'];
        }
        if (!empty($customer['username'])) {
            $names[] = $customer['username'];
        }
        return array_values(array_unique(array_filter($names)));
    }

    private function markPppoeCustomerConnectedLocal($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return;
        }
        if (method_exists('Mikrotik', 'markPppoeCustomerConnected')) {
            Mikrotik::markPppoeCustomerConnected($customerId);
            return;
        }
        if (trim((string) User::getAttribute('pppoe_first_connected', $customerId)) !== '') {
            return;
        }
        User::setAttribute('pppoe_first_connected', date('Y-m-d H:i:s'), $customerId);
    }

    private function resolvePlanRateLimit($plan, $bw)
    {
        if ($this->isExpirePlan($plan) || strtoupper(trim((string) ($plan['name_plan'] ?? ''))) === 'EXPIRE') {
            return Mikrotik::pppoeExpireSystemRateLimit();
        }
        if (!$bw) {
            return '';
        }
        $bwRow = is_object($bw) && method_exists($bw, 'as_array') ? $bw->as_array() : (array) $bw;

        return Mikrotik::hotspotPlanRateLimit($bwRow);
    }

    private function suspendPppoeSession($client, $customer, $profileName = 'EXPIRE', $plan = [])
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        foreach ($this->pppoeLoginNames($customer) as $name) {
            $activeRequest = new RouterOS\Request('/ppp/active/print');
            $activeRequest->setArgument('.proplist', '.id,name,address,profile');
            $activeRequest->setQuery(RouterOS\Query::where('name', $name));
            $foundActive = false;
            foreach ($client->sendSync($activeRequest) as $active) {
                $foundActive = true;
                $this->markPppoeCustomerConnectedLocal((int) ($customer['id'] ?? 0));
                $sessionId = $active->getProperty('.id');
                $ip = trim((string) $active->getProperty('address'));
                if ($ip !== '') {
                    $this->ensureAddressListEntry($client, 'pppoe-expired', $ip, $name);
                    $this->rememberExpiredPppoeClient($customer, $plan, $ip, $name);
                }
                if (!empty($sessionId) && $profileName !== '') {
                    $this->setActivePppoeProfile($client, $sessionId, $profileName, $ip, $name);
                }
            }
            if (!$foundActive) {
                $storedIp = trim((string) User::getAttribute('pppoe_expired_ip', (int) ($customer['id'] ?? 0)));
                if ($storedIp !== '') {
                    $this->ensureAddressListEntry($client, 'pppoe-expired', $storedIp, $name);
                }
            }
        }

        // Isolation firewall (raw/mangle) : une seule fois par sync — voir ensurePppoeExpiredCaptive().
    }

    /**
     * Lift suspension on active sessions without dropping PPPoE (firewall list + profile).
     */
    private function reactivatePppoeSession($client, $customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $this->forgetExpiredPppoeClient($customer);
        $this->unsuspendPppoeSession($client, $customer);
        $profileName = trim((string) ($plan['name_plan'] ?? ''));
        if ($profileName === '' || $this->isExpirePlan($plan)) {
            return;
        }
        foreach ($this->pppoeLoginNames($customer) as $name) {
            $activeRequest = new RouterOS\Request('/ppp/active/print');
            $activeRequest->setArgument('.proplist', '.id');
            $activeRequest->setQuery(RouterOS\Query::where('name', $name));
            foreach ($client->sendSync($activeRequest) as $active) {
                $this->markPppoeCustomerConnectedLocal((int) ($customer['id'] ?? 0));
                $sessionId = $active->getProperty('.id');
                if (!empty($sessionId)) {
                    $this->setActivePppoeProfile($client, $sessionId, $profileName);
                }
            }
        }
    }

    private function setActivePppoeProfile($client, $sessionId, $profileName, $ip = '', $login = '')
    {
        $setRequest = new RouterOS\Request('/ppp/active/set');
        $setRequest->setArgument('numbers', $sessionId);
        $setRequest->setArgument('profile', $profileName);
        $client->sendSync($setRequest);

        if (strtoupper(trim((string) $profileName)) === 'EXPIRE') {
            $ip = trim((string) $ip);
            if ($ip === '') {
                $activePrint = new RouterOS\Request('/ppp/active/print');
                $activePrint->setArgument('.proplist', 'address');
                $activePrint->setQuery(RouterOS\Query::where('.id', $sessionId));
                $ip = trim((string) $client->sendSync($activePrint)->getProperty('address'));
            }
            if ($ip !== '') {
                Mikrotik::ensureAddressListEntry($client, 'pppoe-expired', $ip, (string) $login);
            }
        }
    }

    private function rememberExpiredPppoeClient($customer, $plan, $ip, $loginName = '')
    {
        $customerId = (int) ($customer['id'] ?? 0);
        if ($customerId <= 0 || $ip === '') {
            return;
        }
        User::setAttribute('pppoe_expired_ip', $ip, $customerId);
        $router = trim((string) (is_array($plan) ? ($plan['routers'] ?? '') : ''));
        if ($router !== '') {
            User::setAttribute('pppoe_expired_router', $router, $customerId);
        }
        $login = trim((string) $loginName);
        if ($login === '') {
            $login = !empty($customer['pppoe_username']) ? (string) $customer['pppoe_username'] : (string) ($customer['username'] ?? '');
        }
        if ($login !== '') {
            User::setAttribute('pppoe_expired_user', $login, $customerId);
        }
    }

    private function forgetExpiredPppoeClient($customer)
    {
        $customerId = (int) ($customer['id'] ?? 0);
        if ($customerId <= 0) {
            return;
        }
        User::setAttribute('pppoe_expired_ip', '', $customerId);
        User::setAttribute('pppoe_expired_router', '', $customerId);
        User::setAttribute('pppoe_expired_user', '', $customerId);
    }

    private function unsuspendPppoeSession($client, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        foreach ($this->pppoeLoginNames($customer) as $name) {
            $this->removeAddressListEntries($client, 'pppoe-expired', $name);
            $activeRequest = new RouterOS\Request('/ppp/active/print');
            $activeRequest->setArgument('.proplist', 'address');
            $activeRequest->setQuery(RouterOS\Query::where('name', $name));
            foreach ($client->sendSync($activeRequest) as $active) {
                $ip = trim((string) $active->getProperty('address'));
                if ($ip !== '') {
                    $this->removeAddressListEntryByIp($client, 'pppoe-expired', $ip);
                }
            }
        }
    }

    private function ensureAddressListEntry($client, $listName, $ip, $comment)
    {
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
        $addRequest->setArgument('comment', $comment);
        $client->sendSync($addRequest);
    }

    private function removeAddressListEntryByIp($client, $listName, $ip)
    {
        $printRequest = new RouterOS\Request('/ip/firewall/address-list/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(
            RouterOS\Query::where('list', $listName)
                ->andWhere('address', $ip)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($id)) {
            return;
        }
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    private function removeAddressListEntries($client, $listName, $commentMatch)
    {
        $printRequest = new RouterOS\Request('/ip/firewall/address-list/print');
        $printRequest->setArgument('.proplist', '.id,comment,address');
        $printRequest->setQuery(RouterOS\Query::where('list', $listName));
        foreach ($client->sendSync($printRequest) as $entry) {
            $comment = (string) $entry->getProperty('comment');
            if ($comment !== $commentMatch) {
                continue;
            }
            $id = $entry->getProperty('.id');
            if (empty($id)) {
                continue;
            }
            $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
            $removeRequest->setArgument('numbers', $id);
            $client->sendSync($removeRequest);
        }
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $client = $this->routerClient($plan['routers']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $cid = $client->sendSync($printRequest)->getProperty('.id');
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
            //disconnect then
            $this->removePpoeActive($client, $from);
        }
    }

    function add_plan($plan)
    {
        $client = $this->routerClient($plan['routers']);

        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        $rate = $this->resolvePlanRateLimit($plan, $bw);
        $pool = ORM::for_table("tbl_pool")->where("pool_name", $plan['pool'])->find_one();
        $localAddress = Mikrotik::resolvePoolGatewayAddress($pool);
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $args = [
            'name' => $plan['name_plan'],
            'local-address' => $localAddress,
            'remote-address' => $pool['pool_name'],
        ];
        if ($rate !== '') {
            $args['rate-limit'] = $rate;
        }
        foreach ($args as $key => $value) {
            $addRequest->setArgument($key, $value);
        }
        $client->sendSync($addRequest);
    }

    /**
     * Login PPPoE canonique sur le routeur (pppoe_username prioritaire).
     */
    private function canonicalPppoeLogin($customer)
    {
        if (!empty($customer['pppoe_username'])) {
            return trim((string) $customer['pppoe_username']);
        }

        return trim((string) ($customer['username'] ?? ''));
    }

    /**
     * Supprime les secrets PPPoE en double (username vs pppoe_username).
     */
    private function removeDuplicatePppoeSecrets($client, $customer, $keepId = null)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }

        $keepId = $keepId !== null ? trim((string) $keepId) : trim((string) $this->getIdByCustomer($customer, $client));
        $canonical = $this->canonicalPppoeLogin($customer);
        $seenIds = [];

        foreach ($this->pppoeLoginNames($customer) as $name) {
            if ($name === '') {
                continue;
            }
            $printRequest = new RouterOS\Request('/ppp/secret/print');
            $printRequest->setArgument('.proplist', '.id,name');
            $printRequest->setQuery(RouterOS\Query::where('name', $name));
            foreach ($client->sendSync($printRequest) as $row) {
                $secretId = trim((string) $row->getProperty('.id'));
                if ($secretId === '') {
                    continue;
                }
                $seenIds[$secretId] = trim((string) $row->getProperty('name'));
            }
        }

        if ($keepId === '' && $canonical !== '' && isset($seenIds) && count($seenIds) === 1) {
            $keepId = (string) array_key_first($seenIds);
        }

        foreach ($seenIds as $secretId => $secretName) {
            if ($keepId !== '' && $secretId === $keepId) {
                continue;
            }
            if ($keepId !== '' && $secretName === $canonical) {
                continue;
            }
            try {
                $this->removePpoeActive($client, $secretName);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                $removeRequest = new RouterOS\Request('/ppp/secret/remove');
                $removeRequest->setArgument('numbers', $secretId);
                $client->sendSync($removeRequest);
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Function to ID by username from Mikrotik
     */
    function getIdByCustomer($customer, $client){
        $canonical = $this->canonicalPppoeLogin($customer);
        $foundId = '';

        foreach ($this->pppoeLoginNames($customer) as $name) {
            if ($name === '') {
                continue;
            }
            $printRequest = new RouterOS\Request('/ppp/secret/print');
            $printRequest->setQuery(RouterOS\Query::where('name', $name));
            $id = $client->sendSync($printRequest)->getProperty('.id');
            if (!empty($id)) {
                if ($name === $canonical || $foundId === '') {
                    $foundId = $id;
                }
            }
        }

        return $foundId !== '' ? $foundId : null;
    }

    function update_plan($old_name, $new_plan)
    {
        $client = $this->routerClient($new_plan['routers']);

        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $old_name['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $this->add_plan($new_plan);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($new_plan['id_bw']);
            $rate = $this->resolvePlanRateLimit($new_plan, $bw);
            $pool = ORM::for_table("tbl_pool")->where("pool_name", $new_plan['pool'])->find_one();
            $localAddress = Mikrotik::resolvePoolGatewayAddress($pool);
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $setRequest->setArgument('numbers', $profileID);
            $setRequest->setArgument('local-address', $localAddress);
            $setRequest->setArgument('remote-address', $pool['pool_name']);
            if ($rate !== '') {
                $setRequest->setArgument('rate-limit', $rate);
            }
            $setRequest->setArgument('on-up', $new_plan['on_login']);
            $setRequest->setArgument('on-down', $new_plan['on_logout']);
            $client->sendSync($setRequest);
        }
    }

    function remove_plan($plan)
    {
        if (strcasecmp(trim((string) ($plan['name_plan'] ?? '')), 'EXPIRE') === 0) {
            return;
        }
        $routerName = trim((string) ($plan['routers'] ?? ''));
        if (!$this->info($routerName)) {
            _log(
                'PPPoE remove_plan: routeur introuvable « ' . $routerName . ' » pour forfait « '
                . trim((string) ($plan['name_plan'] ?? '')) . ' » — profil MikroTik ignoré.',
                'PPPoE',
                0
            );

            return;
        }
        $client = $this->routerClient($routerName);
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if ($profileID === null || $profileID === '') {
            return;
        }

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function add_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $client = $this->routerClient($pool['routers']);
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $pool['pool_name'])
                ->setArgument('ranges', $pool['range_ip'])
        );
    }

    function update_pool($old_pool, $new_pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $client = $this->routerClient($new_pool['routers']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $old_pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($poolID)) {
            $this->add_pool($new_pool);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('name', $new_pool['pool_name'])
                    ->setArgument('ranges', $new_pool['range_ip'])
            );
        }
    }

    function remove_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $client = $this->routerClient($pool['routers']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }


    function online_customer($customer, $router_name)
    {
        $client = $this->routerClient($router_name);
        $printRequest = new RouterOS\Request(
            '/ppp active print',
            RouterOS\Query::where('name', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if(empty($id)){
            $printRequest = new RouterOS\Request(
                '/ppp active print',
                RouterOS\Query::where('name', $customer['pppoe_username'])
            );
            $id = $client->sendSync($printRequest)->getProperty('.id');
        }
        return $id;
    }

    function info($name)
    {
        global $admin;

        return Mikrotik::resolveRouterRecord($name, $admin ?? null);
    }

    function routerClient($routerName)
    {
        if (self::$sharedClient !== null) {
            return self::$sharedClient;
        }
        $mikrotik = $this->info($routerName);
        if (!$mikrotik) {
            $hint = trim((string) $routerName);
            throw new Exception(
                Lang::T('Router not found')
                . ($hint !== '' ? ' (' . $hint . ')' : '')
                . ' — vérifiez Réseau → Routeurs et le champ Routeur du forfait.'
            );
        }
        $password = Mikrotik::routerPassword($mikrotik['password']);
        return Mikrotik::getClient(
            $mikrotik['ip_address'],
            $mikrotik['username'],
            $password
        );
    }

    function getClient($ip, $user, $pass, $timeout = 5, $fallback = true, $failOnUnreachable = false)
    {
        return Mikrotik::getClient($ip, $user, $pass, $timeout, $fallback, $failOnUnreachable);
    }

    function removePpoeUser($client, $username)
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

    function addPpoeUser($client, $plan, $customer, $isExp = false)
    {
        $setRequest = new RouterOS\Request('/ppp/secret/add');
        $setRequest->setArgument('service', 'pppoe');
        $setRequest->setArgument('profile', $plan['name_plan']);
        $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
        $networkPass = Password::networkCleartext($customer);
        if ($networkPass !== '') {
            $setRequest->setArgument('password', $networkPass);
        }
        if (!empty($customer['pppoe_username'])) {
            $setRequest->setArgument('name', $customer['pppoe_username']);
        } else {
            $setRequest->setArgument('name', $customer['username']);
        }
        if (!empty($customer['pppoe_ip']) && !$isExp) {
            $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
        }
        $client->sendSync($setRequest);
    }

    function removePpoeActive($client, $username)
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
            return null;
        }

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    function getIpHotspotUser($client, $username)
    {
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

    function addIpToAddressList($client, $ip, $listName, $comment = '')
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

    function removeIpFromAddressList($client, $ip)
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
}
