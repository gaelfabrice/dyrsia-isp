<?php

/**
 * Hotspot: username généré + mot de passe clair fixe 123456.
 * password ET pppoe_password = 123456 en clair (aucun hash).
 * Même valeur poussée sur MikroTik et renvoyée au portail captif.
 */
class HotspotCustomer
{
    public static function defaultPassword()
    {
        return '123456';
    }

    /** Applique 123456 en clair sur password + pppoe_password. */
    public static function applyPlainCredentials($customer, $save = true)
    {
        if (!$customer) {
            return null;
        }
        $plain = self::defaultPassword();
        $customer->password = $plain;
        $customer->pppoe_password = $plain;
        if ($save) {
            $customer->save();
        }

        return $customer;
    }

    public static function networkPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function loginPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function activationNetworkPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function clearActivationNetworkPassword()
    {
    }

    public static function prepareForHotspotActivation($customer, $save = true)
    {
        if (!$customer) {
            return ['customer' => null, 'password' => ''];
        }
        $customer = self::applyPlainCredentials($customer, $save);

        return [
            'customer' => $customer,
            'password' => self::defaultPassword(),
        ];
    }

    public static function forceMikrotikHotspotPassword($username, $routerName, $password = null)
    {
        global $_app_stage;

        $username = trim((string) $username);
        $routerName = trim((string) $routerName);
        $password = self::defaultPassword();
        if ($username === '' || $routerName === '') {
            return false;
        }
        if ($_app_stage === 'Demo') {
            return true;
        }

        $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if (!$router) {
            return false;
        }

        try {
            $client = Mikrotik::getClient(
                $router['ip_address'],
                $router['username'],
                $router['password'],
                30,
                true,
                true
            );
            if (!$client) {
                return false;
            }

            $printRequest = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(\PEAR2\Net\RouterOS\Query::where('name', $username));
            $userId = $client->sendSync($printRequest)->getProperty('.id');
            if ($userId === null || $userId === '') {
                return false;
            }

            $setRequest = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $userId);
            $setRequest->setArgument('password', $password);
            $client->sendSync($setRequest);

            return true;
        } catch (Throwable $e) {
            _log('[Hotspot] forceMikrotikHotspotPassword failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function refreshForDeviceSync($customerId)
    {
        $customer = ORM::for_table('tbl_customers')->where('id', (int) $customerId)->find_one();
        if (!$customer) {
            return null;
        }
        if (trim((string) ($customer->service_type ?? '')) === 'Hotspot') {
            return self::applyPlainCredentials($customer);
        }

        return $customer;
    }

    public static function ensureValidNetworkCredentials($customer, $save = true)
    {
        return self::applyPlainCredentials($customer, $save);
    }

    public static function networkPasswordFromPayment($trx = null, $customer = null)
    {
        return self::defaultPassword();
    }

    public static function storePaymentNetworkPassword($trx, $password = null)
    {
        if (!$trx) {
            return;
        }
        $meta = json_decode((string) ($trx->gateway_response ?? '{}'), true);
        if (!is_array($meta)) {
            $meta = [];
        }
        $meta['network_password'] = self::defaultPassword();
        $trx->gateway_response = json_encode($meta, JSON_UNESCAPED_UNICODE);
    }

    public static function resolveCustomerFromPayment($trx)
    {
        if (!$trx) {
            return null;
        }

        $customer = self::findByPhone($trx->phone_number ?? '');
        if ($customer) {
            return self::ensureValidUsername($customer);
        }

        $code = trim((string) ($trx->voucher_code ?? ''));
        if ($code !== '' && $code !== '**********' && !self::isMacUsername($code)) {
            $customer = ORM::for_table('tbl_customers')->where('username', $code)->find_one();
            if ($customer) {
                return $customer;
            }
        }

        return null;
    }

    public static function credentialsFromPayment($trx)
    {
        $customer = self::resolveCustomerFromPayment($trx);

        return [
            'username' => $customer ? (string) $customer->username : '',
            'password' => self::defaultPassword(),
        ];
    }

    public static function isMacUsername($username)
    {
        $username = trim((string) $username);
        if ($username === '') {
            return false;
        }

        return (bool) preg_match('/^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/', $username);
    }

    public static function generateUsername($length = 10)
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        do {
            $username = '';
            for ($i = 0; $i < $length; $i++) {
                $username .= $alphabet[random_int(0, $max)];
            }
            $exists = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        } while ($exists);

        return $username;
    }

    public static function ensureValidUsername($customer)
    {
        if (!$customer || !self::isMacUsername($customer->username)) {
            return $customer;
        }

        $oldUsername = (string) $customer->username;
        $customer->username = self::generateUsername(10);
        self::applyPlainCredentials($customer, false);
        $customer->save();

        foreach (ORM::for_table('tbl_user_recharges')->where('customer_id', $customer->id)->where('username', $oldUsername)->find_many() as $recharge) {
            $recharge->username = $customer->username;
            $recharge->save();
        }
        foreach (ORM::for_table('tbl_transactions')->where('user_id', $customer->id)->where('username', $oldUsername)->find_many() as $transaction) {
            $transaction->username = $customer->username;
            $transaction->save();
        }
        foreach (ORM::for_table('tbl_hotspot_payments')->where('voucher_code', $oldUsername)->find_many() as $payment) {
            $payment->voucher_code = $customer->username;
            $payment->save();
        }

        return $customer;
    }

    public static function fixAllMacUsernames()
    {
        $fixed = 0;
        foreach (ORM::for_table('tbl_customers')->where('service_type', 'Hotspot')->find_many() as $customer) {
            if (self::isMacUsername($customer->username)) {
                self::ensureValidUsername($customer);
                $fixed++;
            }
        }

        return $fixed;
    }

    public static function findByPhone($phone)
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) === 9) {
            $local = $digits;
        } elseif (strlen($digits) > 9) {
            $local = substr($digits, -9);
        } else {
            return null;
        }

        $formattedPhone = Lang::phoneFormat($local);
        $customer = null;
        if ($formattedPhone !== '') {
            $customer = ORM::for_table('tbl_customers')->where('phonenumber', $formattedPhone)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('phonenumber', $local)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('username', $local)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where_like('phonenumber', '%' . $local)->find_one();
        }

        return $customer ?: null;
    }

    public static function findOrCreate($phone, $fullname = 'Client Hotspot', $address = 'Hotspot')
    {
        $formattedPhone = Lang::phoneFormat($phone);
        $customer = self::findByPhone($phone);
        $plain = self::defaultPassword();

        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->create();
            $customer->username = self::generateUsername(10);
            $customer->password = $plain;
            $customer->pppoe_password = $plain;
            $customer->fullname = $fullname !== '' ? $fullname : 'Client Hotspot';
            $customer->address = $address !== '' ? $address : 'Hotspot';
            $customer->phonenumber = $formattedPhone;
            $customer->email = '';
            $customer->balance = 0;
            $customer->service_type = 'Hotspot';
            $customer->account_type = 'Personal';
            $customer->status = 'Active';
            $customer->created_at = date('Y-m-d H:i:s');
            $customer->save();
        } else {
            if ($fullname !== '' && ($customer->fullname === '' || $customer->fullname === 'Hotspot User')) {
                $customer->fullname = $fullname;
            }
            if ($address !== '' && ($customer->address === '' || $customer->address === 'N/A' || $customer->address === 'Hotspot')) {
                $customer->address = $address;
            }
            if ($formattedPhone !== '' && $customer->phonenumber === '') {
                $customer->phonenumber = $formattedPhone;
            }
            $customer->password = $plain;
            $customer->pppoe_password = $plain;
            $customer->save();
        }

        return self::ensureValidUsername($customer);
    }

    public static function loginUsernameFromPayment($trx)
    {
        $customer = self::resolveCustomerFromPayment($trx);

        return $customer ? (string) $customer->username : '';
    }
}
