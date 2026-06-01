<?php

class WifiZoneNotify
{
    public static function processRenewalReminders()
    {
        $days = array_map('intval', explode(',', WifiZoneCore::config('wifizone_renewal_notify_days', '7,3,1')));
        foreach ($days as $d) {
            if ($d <= 0) {
                continue;
            }
            $target = date('Y-m-d', strtotime("+$d days"));
            $recharges = ORM::for_table('tbl_user_recharges')
                ->where('status', 'on')
                ->where('expiration', $target)
                ->find_many();
            foreach ($recharges as $r) {
                $customer = ORM::for_table('tbl_customers')->find_one($r->customer_id);
                if (!$customer) {
                    continue;
                }
                $msg = Lang::T('Your package expires in') . " $d " . Lang::T('day') . '(s): ' . $r->namebp;
                self::notifyCustomer($customer, $msg);
            }
        }
    }

    public static function notifyCustomer($customer, $message)
    {
        try {
            if (class_exists('Message')) {
                if (!empty($customer->phonenumber)) {
                    Message::sendSMS($customer->phonenumber, $message);
                    if (method_exists('Message', 'sendWhatsapp')) {
                        Message::sendWhatsapp($customer->phonenumber, $message);
                    }
                }
                if (!empty($customer->email) && method_exists('Message', 'sendEmail')) {
                    Message::sendEmail($customer->email, Lang::T('Package reminder'), $message);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public static function checkGenieacsOffline()
    {
        try {
            $minutes = (int) WifiZoneCore::config('wifizone_genieacs_alert_minutes', 30);
            if ($minutes <= 0) {
                return;
            }
            $threshold = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
            $devices = ORM::for_table('tbl_acs_devices')
                ->where_raw('(last_contact IS NULL OR last_contact < ?)', [$threshold])
                ->limit(50)
                ->find_many();
            foreach ($devices as $d) {
                $msg = "ONU offline > {$minutes}min: " . ($d->device_id ?? $d->id);
                if (class_exists('Message')) {
                    Message::sendTelegram($msg);
                }
            }
        } catch (Exception $e) {
            // GenieACS tables optional
        }
    }

    public static function linkCustomerToDevice($customerId, $mac = null, $acsDeviceId = null)
    {
        if ($mac) {
            $mac = strtolower(preg_replace('/[^a-fA-F0-9:]/', '', $mac));
        }
        $row = ORM::for_table('wifizone_customer_devices')->create();
        $row->customer_id = $customerId;
        $row->mac = $mac;
        $row->acs_device_id = $acsDeviceId;
        $row->save();
        WifiZoneAudit::log('link_device', 'customer', $customerId, ['mac' => $mac, 'acs' => $acsDeviceId]);
        return $row->id;
    }
}
