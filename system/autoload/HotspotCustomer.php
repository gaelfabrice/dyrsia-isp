<?php

/**
 * Hotspot customer credentials: 10-char username + default password.
 */
class HotspotCustomer
{
    public static function defaultPassword()
    {
        return '123456';
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
        if ($customer->password === $oldUsername || self::isMacUsername((string) $customer->password)) {
            $customer->password = self::defaultPassword();
        }
        $customer->save();

        $recharges = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customer->id)
            ->where('username', $oldUsername)
            ->find_many();
        foreach ($recharges as $recharge) {
            $recharge->username = $customer->username;
            $recharge->save();
        }

        $transactions = ORM::for_table('tbl_transactions')
            ->where('user_id', $customer->id)
            ->where('username', $oldUsername)
            ->find_many();
        foreach ($transactions as $transaction) {
            $transaction->username = $customer->username;
            $transaction->save();
        }

        $payments = ORM::for_table('tbl_hotspot_payments')
            ->where('voucher_code', $oldUsername)
            ->find_many();
        foreach ($payments as $payment) {
            $payment->voucher_code = $customer->username;
            $payment->save();
        }

        return $customer;
    }

    public static function fixAllMacUsernames()
    {
        $fixed = 0;
        $customers = ORM::for_table('tbl_customers')
            ->where('service_type', 'Hotspot')
            ->find_many();
        foreach ($customers as $customer) {
            if (self::isMacUsername($customer->username)) {
                self::ensureValidUsername($customer);
                $fixed++;
            }
        }

        return $fixed;
    }

    public static function findOrCreate($phone, $fullname = 'Client Hotspot', $address = 'Hotspot')
    {
        $formattedPhone = Lang::phoneFormat($phone);
        $customer = null;
        if ($formattedPhone !== '') {
            $customer = ORM::for_table('tbl_customers')->where('phonenumber', $formattedPhone)->find_one();
            if (!$customer) {
                $digits = preg_replace('/\D/', '', (string) $phone);
                if (strlen($digits) >= 9) {
                    $customer = ORM::for_table('tbl_customers')
                        ->where_like('phonenumber', '%' . substr($digits, -9))
                        ->find_one();
                }
            }
        }

        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->create();
            $customer->username = self::generateUsername(10);
            $customer->password = self::defaultPassword();
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
            $customer->save();
        }

        return self::ensureValidUsername($customer);
    }

    public static function loginUsernameFromPayment($trx)
    {
        if (!$trx) {
            return '';
        }
        $code = trim((string) $trx->voucher_code);
        if ($code !== '' && $code !== '**********' && !self::isMacUsername($code)) {
            return $code;
        }
        $phone = Lang::phoneFormat($trx->phone_number ?? '');
        if ($phone === '') {
            return '';
        }
        $customer = ORM::for_table('tbl_customers')->where('phonenumber', $phone)->find_one();
        if (!$customer) {
            $digits = preg_replace('/\D/', '', (string) ($trx->phone_number ?? ''));
            if (strlen($digits) >= 9) {
                $customer = ORM::for_table('tbl_customers')
                    ->where_like('phonenumber', '%' . substr($digits, -9))
                    ->find_one();
            }
        }
        if ($customer) {
            $customer = self::ensureValidUsername($customer);
            return (string) $customer->username;
        }

        return '';
    }
}
