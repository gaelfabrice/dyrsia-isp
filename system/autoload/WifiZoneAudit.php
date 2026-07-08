<?php

class WifiZoneAudit
{
    public static function log($action, $entityType, $entityId = '', $payload = [])
    {
        $actorId = 0;
        $actorType = 'system';
        if (!empty($_SESSION['aid'])) {
            $actorId = (int) $_SESSION['aid'];
            $actorType = 'admin';
        } elseif (!empty($_SESSION['uid'])) {
            $actorId = (int) $_SESSION['uid'];
            $actorType = 'customer';
        }
        $ip = WifiZoneHotspot::clientIp();
        try {
            $row = ORM::for_table('wifizone_audit_log')->create();
            $row->actor_id = $actorId;
            $row->actor_type = $actorType;
            $row->action = $action;
            $row->entity_type = $entityType;
            $row->entity_id = (string) $entityId;
            $row->payload = json_encode($payload);
            $row->ip = $ip;
            $row->save();
        } catch (Exception $e) {
            WifiZoneLogger::note("audit log write for $action on $entityType/$entityId", $e);
        }
        _log("Audit: $action on $entityType/$entityId", 'Audit', $actorId);
    }

    public static function exportCustomerData($customerId)
    {
        $customer = ORM::for_table('tbl_customers')->find_one($customerId);
        if (!$customer) {
            return null;
        }
        return [
            'customer' => $customer->as_array(),
            'recharges' => ORM::for_table('tbl_user_recharges')->where('customer_id', $customerId)->find_array(),
            'transactions' => ORM::for_table('tbl_transactions')->where('username', $customer->username)->find_array(),
            'devices' => ORM::for_table('wifizone_customer_devices')->where('customer_id', $customerId)->find_array(),
        ];
    }

    public static function eraseCustomerData($customerId)
    {
        $customer = ORM::for_table('tbl_customers')->find_one($customerId);
        if (!$customer) {
            return false;
        }
        self::log('gdpr_erase', 'customer', $customerId, ['username' => $customer->username]);
        ORM::for_table('wifizone_customer_devices')->where('customer_id', $customerId)->delete_many();
        $customer->email = '';
        $customer->phonenumber = '';
        $customer->address = 'GDPR_ERASED';
        $customer->fullname = 'GDPR_ERASED';
        $customer->save();
        return true;
    }
}
