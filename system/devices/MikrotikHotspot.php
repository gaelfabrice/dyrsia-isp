<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikHotspot
{

    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik Hotspot',
            'description' => 'To handle connection between wifizones with Mikrotik Hotspot',
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
        $this->ensureHotspotPlanProfile($client, $plan);
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        if ($isExp) {
            $this->removeHotspotActiveUser($client, $customer['username']);
        }
        $this->addHotspotUser($client, $plan, $customer);
    }
    
    function sync_customer($customer, $plan)
    {
        $customerRow = is_array($customer) ? $customer : $customer->as_array();
        $planRow = is_array($plan) ? $plan : $plan->as_array();
        $routerName = trim((string) ($planRow['routers'] ?? ''));

        $rechargeQuery = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', (int) ($customerRow['id'] ?? 0))
            ->where('status', 'on')
            ->order_by_desc('id');
        if ($routerName !== '') {
            $rechargeQuery->where('routers', $routerName);
        }
        $recharge = $rechargeQuery->find_one();

        if (!$recharge) {
            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('username', (string) ($customerRow['username'] ?? ''))
                ->where('status', 'on')
                ->order_by_desc('id')
                ->find_one();
        }

        if ($recharge && class_exists('Package')) {
            [$customerRow, $planRow] = Package::deviceSyncRows(
                $planRow,
                (string) ($recharge->routers ?? $routerName),
                $customerRow,
                (string) $recharge->username
            );
        }

        $this->add_customer($customerRow, $planRow);
    }


    function remove_customer($customer, $plan)
    {
        $client = $this->routerClient($plan['routers']);
        $username = trim((string) ($customer['username'] ?? ''));
        $planType = strtolower(trim((string) ($plan['type'] ?? '')));

        // Hotspot prépayé : à l'expiration, supprimer le user (pas de profil « expiré » sur le routeur).
        if ($planType !== 'hotspot' && !empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if ($p) {
                $this->add_customer($customer, $p);
                Mikrotik::disconnectHotspotUser($client, $username);
                Mikrotik::sweepOrphanHotspotSessions($client);

                return;
            }
        }

        // Kill active session + MAC cookie first, then delete the hotspot user.
        Mikrotik::disconnectHotspotUser($client, $username);
        $this->removeHotspotUser($client, $username);
        Mikrotik::disconnectHotspotUser($client, $username);
        Mikrotik::sweepOrphanHotspotSessions($client);
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $client = $this->routerClient($plan['routers']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        if (!empty($id)) {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $id);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
            //disconnect then
            $this->removeHotspotActiveUser($client, $from);
        }
    }

    function add_plan($plan)
    {
        $client = $this->routerClient($plan['routers']);
        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        $rate = Mikrotik::hotspotPlanRateLimit($bw);
        $sharedUsers = (int) ($plan['shared_users'] ?? 1);
        if ($sharedUsers < 1) {
            $sharedUsers = 1;
        }
        Mikrotik::setHotspotPlan($client, $plan['name_plan'], $sharedUsers, $rate);
    }

    function online_customer($customer, $router_name)
    {
        $client = $this->routerClient($router_name);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id =  $client->sendSync($printRequest)->getProperty('.id');
        return $id;
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
        $client = $this->routerClient($router_name);
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $customer['username'])
                ->setArgument('password', HotspotCustomer::defaultPassword())
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac_address)
        );
    }

    function disconnect_customer($customer, $router_name)
    {
        Mikrotik::disconnectHotspotUserOnRouter($router_name, $customer['username']);
    }


    function update_plan($old_plan, $new_plan)
    {
        $client = $this->routerClient($new_plan['routers']);

        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $old_plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $this->add_plan($new_plan);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($new_plan['id_bw']);
            $rate = Mikrotik::hotspotPlanRateLimit($bw ? $bw->as_array() : null);
            if ($bw && (($bw['rate_up'] ?? '0') == '0' || ($bw['rate_down'] ?? '0') == '0')) {
                $rate = '';
            }
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('name', $new_plan['name_plan'])
                    ->setArgument('shared-users', $new_plan['shared_users'])
                    ->setArgument('rate-limit', $rate)
                    ->setArgument('on-login', $new_plan['on_login'])
                    ->setArgument('on-logout', $new_plan['on_logout'])
            );
        }
    }

    function remove_plan($plan)
    {
        $routerName = trim((string) ($plan['routers'] ?? ''));
        $mikrotik = $this->info($routerName);
        if (!$mikrotik) {
            // Forfait orphelin (routeur renommé/supprimé) : on laisse la suppression DB continuer.
            _log(
                'Hotspot remove_plan: routeur introuvable « ' . $routerName . ' » pour forfait « '
                . trim((string) ($plan['name_plan'] ?? '')) . ' » — profil MikroTik ignoré.',
                'Hotspot',
                0
            );

            return;
        }

        $client = $this->routerClient($routerName);
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if ($profileID === null || $profileID === '') {
            return;
        }
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function info($name)
    {
        global $admin;

        $mikrotik = Mikrotik::resolveRouterRecord($name, $admin ?? null);
        if (!$mikrotik) {
            $mikrotik = Mikrotik::resolveRouterRecord($name, null);
        }

        return $mikrotik;
    }

    function routerClient($routerName)
    {
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
        $client = Mikrotik::getClient(
            $mikrotik['ip_address'],
            $mikrotik['username'],
            $password,
            30,
            true,
            true
        );
        if ($client === null) {
            throw new Exception(
                Lang::T('Cannot connect to MikroTik')
                . ' — sync routeur désactivée (mode démo) ou API injoignable (VPN / IP / port 8728).'
            );
        }

        return $client;
    }

    /**
     * Serveur hotspot MikroTik (pas « all ») pour lier le user au bon /ip/hotspot.
     */
    function hotspotServerNameForPlan($client, $plan)
    {
        $router = trim((string) (is_array($plan) ? ($plan['routers'] ?? '') : ($plan->routers ?? '')));

        return Mikrotik::resolveHotspotServerName($client, $router);
    }

    /**
     * Crée le profil hotspot sur le routeur si absent (requis avant /ip hotspot user add).
     */
    function ensureHotspotPlanProfile($client, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo' || !$client) {
            return;
        }
        $planId = (int) ($plan['id'] ?? 0);
        $bw = null;
        if (!empty($plan['id_bw'])) {
            $bw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
        }
        if (!$bw && $planId > 0) {
            $planRow = ORM::for_table('tbl_plans')->find_one($planId);
            if ($planRow && !empty($planRow->id_bw)) {
                $bw = ORM::for_table('tbl_bandwidth')->find_one($planRow->id_bw);
            }
        }
        $rate = Mikrotik::hotspotPlanRateLimit($bw);
        $sharedUsers = (int) ($plan['shared_users'] ?? 1);
        if ($sharedUsers < 1) {
            $sharedUsers = 1;
        }
        $profileName = trim((string) ($plan['name_plan'] ?? ''));
        if ($profileName === '') {
            return;
        }
        Mikrotik::setHotspotPlan($client, $profileName, $sharedUsers, $rate);
    }

    function getClient($ip, $user, $pass, $timeout = 5, $fallback = true, $failOnUnreachable = false)
    {
        return Mikrotik::getClient($ip, $user, $pass, $timeout, $fallback, $failOnUnreachable);
    }

    function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        if ($userID === null || $userID === '') {
            return null;
        }
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        if (!$client) {
            throw new Exception('Client API MikroTik indisponible (connexion nulle).');
        }

        $this->ensureHotspotPlanProfile($client, $plan);
        $allowsSharing = Mikrotik::hotspotPlanAllowsSharing($plan);

        $networkPass = HotspotCustomer::defaultPassword();
        if (Password::isStoredHash($networkPass) || $networkPass === '') {
            throw new Exception('Mot de passe réseau invalide pour ' . ($customer['username'] ?? ''));
        }

        // ===============================
        // CUSTOM COMMENT SYSTEM (Updated)
        // ===============================
        $fullname = isset($customer['fullname']) && !empty($customer['fullname']) ? $customer['fullname'] : 'N/A';
        $phone = isset($customer['phonenumber']) && !empty($customer['phonenumber']) ? $customer['phonenumber'] : (isset($customer['phone']) ? $customer['phone'] : 'N/A');
        $address = isset($customer['address']) && !empty($customer['address']) ? $customer['address'] : 'N/A';

        // Fetch the absolute latest recharge info from DB for this user
        $recharge = ORM::for_table('tbl_user_recharges')
            ->where('username', $customer['username'])
            ->order_by_desc('id')
            ->find_one();

        // Properly combining Date and Time columns
        $created_time = ($recharge && !empty($recharge['recharged_on'])) 
            ? trim($recharge['recharged_on'] . ' ' . $recharge['recharged_time']) 
            : date('Y-m-d H:i:s');
            
        $expired_time = ($recharge && !empty($recharge['expiration'])) 
            ? trim($recharge['expiration'] . ' ' . $recharge['time']) 
            : 'N/A';
            
        $method = ($recharge && !empty($recharge['method'])) 
            ? $recharge['method'] 
            : 'Manual';

        $targetComment = "Name: $fullname | Phone: $phone | Address: $address | Created: $created_time | Expired: $expired_time | Method: $method";

        $hotspotServer = $this->hotspotServerNameForPlan($client, $plan);

        $deviceMac = '';
        if (!$allowsSharing) {
            if (isset($customer['mac']) && trim((string) $customer['mac']) !== '') {
                $deviceMac = Mikrotik::normalizeHotspotMacAddress((string) $customer['mac']);
            }
        }

        // ===============================
        // 1. Check existing users by Username
        // ===============================
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id,name');
        $printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
        $existingUsers = $client->sendSync($printRequest);

        $found = false;
        $existingUserID = null;

        foreach ($existingUsers as $user) {
            $found = true;
            $existingUserID = $user->getProperty('.id');

            // Un seul appareil (shared=1) : couper les autres sessions à la resync.
            if (!$allowsSharing) {
                $activeRequest = new RouterOS\Request('/ip/hotspot/active/print');
                $activeRequest->setArgument('.proplist', '.id');
                $activeRequest->setQuery(RouterOS\Query::where('user', $customer['username']));
                $activeSessions = $client->sendSync($activeRequest);
                foreach ($activeSessions as $session) {
                    $removeActive = new RouterOS\Request('/ip/hotspot/active/remove');
                    $removeActive->setArgument('numbers', $session->getProperty('.id'));
                    $client->sendSync($removeActive);
                }
            }
            break;
        }

        if (!$allowsSharing && $deviceMac === '' && $found) {
            $deviceMac = Mikrotik::getHotspotUserBoundMacOnClient($client, $customer['username']);
        }
        $bindMac = !$allowsSharing && $deviceMac !== '';

        // ===============================
        // 2. If found, update username, profile, comment, etc.
        // ===============================
        if ($found && $existingUserID) {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $existingUserID);
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $targetComment);
            $setRequest->setArgument('password', $networkPass);
            if ($hotspotServer !== '') {
                $setRequest->setArgument('server', $hotspotServer);
            }
            if ($bindMac) {
                $setRequest->setArgument('mac-address', $deviceMac);
            } elseif ($allowsSharing) {
                $setRequest->setArgument('mac-address', '');
            }
            $client->sendSync($setRequest);
            return;
        }

        // ===============================
        // 3. If not found, create new user (Limited/Unlimited)
        // ===============================
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        $addRequest
            ->setArgument('name', $customer['username'])
            ->setArgument('profile', $plan['name_plan'])
            ->setArgument('password', $networkPass)
            ->setArgument('comment', $targetComment);
        if ($hotspotServer !== '') {
            $addRequest->setArgument('server', $hotspotServer);
        }

        if (isset($customer['email']) && $customer['email'] != '') {
            $addRequest->setArgument('email', $customer['email']);
        }

        if ($bindMac) {
            $addRequest->setArgument('mac-address', $deviceMac);
        }

        // Handle Limited plans
        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                $timelimit = ($plan['time_unit'] == 'Hrs') ? $plan['time_limit'] . ":00:00" : "00:" . $plan['time_limit'] . ":00";
                $addRequest->setArgument('limit-uptime', $timelimit);
            } elseif ($plan['limit_type'] == "Data_Limit") {
                $datalimit = ($plan['data_unit'] == 'GB') ? $plan['data_limit'] . "000000000" : $plan['data_limit'] . "000000";
                $addRequest->setArgument('limit-bytes-total', $datalimit);
            } elseif ($plan['limit_type'] == "Both_Limit") {
                $timelimit = ($plan['time_unit'] == 'Hrs') ? $plan['time_limit'] . ":00:00" : "00:" . $plan['time_limit'] . ":00";
                $datalimit = ($plan['data_unit'] == 'GB') ? $plan['data_limit'] . "000000000" : $plan['data_limit'] . "000000";
                $addRequest->setArgument('limit-uptime', $timelimit);
                $addRequest->setArgument('limit-bytes-total', $datalimit);
            }
        }

        $client->sendSync($addRequest);
    }

    // ===============================
    // Optional: just update password for existing user
    // ===============================
    function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }

        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $existingUsers = $client->sendSync($printRequest);

        foreach ($existingUsers as $u) {
            $id = $u->getProperty('.id');
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $id);
            $setRequest->setArgument('password', $pass);
            $client->sendSync($setRequest);
        }
    }

    function setHotspotUserPackage($client, $username, $plan_name)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan_name);
        $client->sendSync($setRequest);
    }

    function removeHotspotActiveUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        if (!empty($id)) {
            $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
            $removeRequest->setArgument('numbers', $id);
            $client->sendSync($removeRequest);
        }
    }

    function getIpHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }
}