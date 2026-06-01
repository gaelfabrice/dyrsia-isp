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
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        $this->removeHotspotUser($client, $customer['username']);
        if ($isExp){
            $this->removeHotspotActiveUser($client, $customer['username']);
        }
        $this->addHotspotUser($client, $plan, $customer);
    }
    
    function sync_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $t = ORM::for_table('tbl_user_recharges')->where('username', $customer['username'])->where('status', 'on')->find_one();
        if ($t) {
            $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
            $printRequest->setArgument('.proplist', '.id,limit-uptime,limit-bytes-total');
            $printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
            $userInfo = $client->sendSync($printRequest);
            $id = $userInfo->getProperty('.id');
            $uptime = $userInfo->getProperty('limit-uptime');
            $data = $userInfo->getProperty('limit-bytes-total');
            if (!empty($id) && (!empty($uptime) || !empty($data))) {
                $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
                $setRequest->setArgument('numbers', $id);
                $setRequest->setArgument('profile', $t['namebp']);
                $client->sendSync($setRequest);
            } else {
                $this->add_customer($customer, $plan);
            }
        }
    }


    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if($p){
                $this->add_customer($customer, $p);
                $this->removeHotspotActiveUser($client, $customer['username']);
                return;
            }
        }
        $this->removeHotspotUser($client, $customer['username']);
        $this->removeHotspotActiveUser($client, $customer['username']);
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
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
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        if ($bw['rate_down_unit'] == 'Kbps') {
            $unitdown = 'K';
        } else {
            $unitdown = 'M';
        }
        if ($bw['rate_up_unit'] == 'Kbps') {
            $unitup = 'K';
        } else {
            $unitup = 'M';
        }
        $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
        if (!empty(trim($bw['burst']))) {
            $rate .= ' ' . $bw['burst'];
        }
        if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
            $rate = '';
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('shared-users', $plan['shared_users'])
                ->setArgument('rate-limit', $rate)
        );
    }

    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id =  $client->sendSync($printRequest)->getProperty('.id');
        return $id;
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $customer['username'])
                ->setArgument('password', $customer['password'])
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac_address)
        );
    }

    function disconnect_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }


    function update_plan($old_plan, $new_plan)
    {
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $old_plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $this->add_plan($new_plan);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($new_plan['id_bw']);
            if ($bw['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($bw['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
            if (!empty(trim($bw['burst']))) {
                $rate .= ' ' . $bw['burst'];
            }
            if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
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
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass)
    {
        return Mikrotik::getClient($ip, $user, $pass);
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

            // ===============================
            // Remove active session if exists
            // ===============================
            $activeRequest = new RouterOS\Request('/ip/hotspot/active/print');
            $activeRequest->setArgument('.proplist', '.id');
            $activeRequest->setQuery(RouterOS\Query::where('user', $customer['username']));
            $activeSessions = $client->sendSync($activeRequest);
            foreach ($activeSessions as $session) {
                $removeActive = new RouterOS\Request('/ip/hotspot/active/remove');
                $removeActive->setArgument('numbers', $session->getProperty('.id'));
                $client->sendSync($removeActive);
            }
            break; 
        }

        // ===============================
        // 2. If found, update username, profile, comment, etc.
        // ===============================
        if ($found && $existingUserID) {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $existingUserID);
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $targetComment);
            $setRequest->setArgument('password', $customer['password']);
            if (isset($customer['mac']) && $customer['mac'] != '') {
                $setRequest->setArgument('mac-address', $customer['mac']);
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
            ->setArgument('password', $customer['password'])
            ->setArgument('comment', $targetComment);

        if (isset($customer['email']) && $customer['email'] != '') {
            $addRequest->setArgument('email', $customer['email']);
        }

        if (isset($customer['mac']) && $customer['mac'] != '') {
            $addRequest->setArgument('mac-address', $customer['mac']);
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