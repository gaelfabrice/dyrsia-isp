<?php

class WifiZonePayment
{
    public static function enqueue($gateway, $reference, $customerId, $planId, $router, $amount, $payload = [])
    {
        $existing = ORM::for_table('wifizone_payment_queue')
            ->where('gateway', $gateway)
            ->where('reference', $reference)
            ->find_one();
        if ($existing) {
            return $existing->id;
        }
        $row = ORM::for_table('wifizone_payment_queue')->create();
        $row->gateway = $gateway;
        $row->reference = $reference;
        $row->customer_id = $customerId;
        $row->plan_id = $planId;
        $row->router = $router;
        $row->amount = $amount;
        $row->payload = json_encode($payload);
        $row->status = 'pending';
        $row->save();
        return $row->id;
    }

    public static function processWebhook($gateway, $data)
    {
        $reference = $data['reference'] ?? $data['transaction_ref'] ?? '';
        if ($reference === '') {
            return false;
        }
        run_hook('wifizone_payment_webhook', ['gateway' => $gateway, 'data' => $data]);
        $row = ORM::for_table('wifizone_payment_queue')
            ->where('gateway', $gateway)
            ->where('reference', $reference)
            ->find_one();
        if ($row && $row->status === 'pending') {
            self::activateQueued($row);
        }
        return true;
    }

    public static function processPendingQueue($limit = 20)
    {
        $rows = ORM::for_table('wifizone_payment_queue')
            ->where('status', 'pending')
            ->where_lt('attempts', 5)
            ->order_by_asc('id')
            ->limit($limit)
            ->find_many();
        foreach ($rows as $row) {
            self::activateQueued($row);
        }
    }

    private static function activateQueued($row)
    {
        $row->status = 'processing';
        $row->attempts = (int) $row->attempts + 1;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        try {
            $customer = ORM::for_table('tbl_customers')->find_one($row->customer_id);
            $plan = ORM::for_table('tbl_plans')->find_one($row->plan_id);
            if ($customer && $plan) {
                Package::rechargeUser($customer->id, $row->router, $plan->id, $row->gateway, $row->reference);
                $row->status = 'done';
                self::sendReceipt($customer, $plan, $row);
            } else {
                throw new Exception('Customer or plan missing');
            }
        } catch (Throwable $e) {
            $row->status = 'pending';
            $row->last_error = $e->getMessage();
        }
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
    }

    public static function sendReceipt($customer, $plan, $queueRow)
    {
        $msg = Lang::T('Payment successful') . ': ' . $plan->name_plan . ' — ' . $queueRow->reference;
        try {
            if (class_exists('Message')) {
                if (!empty($customer->phonenumber)) {
                    Message::sendSMS($customer->phonenumber, $msg);
                }
                if (method_exists('Message', 'sendWhatsapp')) {
                    Message::sendWhatsapp($customer->phonenumber, $msg);
                }
            }
        } catch (Throwable $e) {
        }
        WifiZoneAudit::log('payment_receipt', 'customer', $customer->id, ['ref' => $queueRow->reference]);
    }
}
