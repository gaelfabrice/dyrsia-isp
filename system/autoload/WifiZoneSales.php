<?php

/**
 * Agrégation des ventes sans compter les recharges CamPay/Hotspot en double.
 */
class WifiZoneSales
{
    /**
     * Marqueur stocké dans tbl_transactions.note pour lier 1 paiement hotspot → 1 vente.
     */
    public static function hotspotPaymentNote($paymentId): string
    {
        return 'hotspot_payment:' . (int) $paymentId;
    }

    public static function findTransactionByHotspotPaymentId($paymentId)
    {
        $paymentId = (int) $paymentId;
        if ($paymentId <= 0) {
            return null;
        }

        return ORM::for_table('tbl_transactions')
            ->where_like('note', self::hotspotPaymentNote($paymentId) . '%')
            ->order_by_asc('id')
            ->find_one();
    }

    /**
     * @param iterable|array $rows rows with id, price, note, username, plan_name, method, routers, recharged_on, recharged_time
     */
    public static function sumUniquePrices($rows): float
    {
        $list = [];
        foreach ($rows as $row) {
            $list[] = is_array($row) ? $row : (method_exists($row, 'as_array') ? $row->as_array() : (array) $row);
        }
        usort($list, static function ($a, $b) {
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $seenPayment = [];
        $seenFingerprint = [];
        $total = 0.0;
        foreach ($list as $row) {
            $note = (string) ($row['note'] ?? '');
            if (preg_match('/hotspot_payment:(\d+)/', $note, $m)) {
                $key = 'hp:' . $m[1];
                if (isset($seenPayment[$key])) {
                    continue;
                }
                $seenPayment[$key] = true;
            } else {
                $method = (string) ($row['method'] ?? '');
                if (stripos($method, 'CamPay') !== false || stripos($method, 'MyPVit') !== false) {
                    $fp = implode('|', [
                        (string) ($row['username'] ?? ''),
                        (string) ($row['plan_name'] ?? ''),
                        (string) ($row['price'] ?? ''),
                        $method,
                        (string) ($row['routers'] ?? ''),
                        (string) ($row['recharged_on'] ?? ''),
                        substr((string) ($row['recharged_time'] ?? ''), 0, 8),
                    ]);
                    if (isset($seenFingerprint[$fp])) {
                        continue;
                    }
                    $seenFingerprint[$fp] = true;
                }
            }
            $total += (float) ($row['price'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Somme des prix d'une requête tbl_transactions, en ignorant les doublons hotspot/CamPay.
     */
    public static function sumQueryPrices($query): float
    {
        $rows = $query
            ->select('id')
            ->select('price')
            ->select('note')
            ->select('username')
            ->select('plan_name')
            ->select('method')
            ->select('routers')
            ->select('recharged_on')
            ->select('recharged_time')
            ->find_array();

        return self::sumUniquePrices($rows ?: []);
    }

    /**
     * Supprime les doublons CamPay/Hotspot (garde la plus ancienne ligne).
     *
     * @return array{deleted:int, kept:int}
     */
    public static function purgeDuplicateHotspotSales($dryRun = true): array
    {
        $rows = ORM::for_table('tbl_transactions')
            ->where_in('type', ['Hotspot', 'PPPOE'])
            ->order_by_asc('id')
            ->find_many();

        $seenPayment = [];
        $seenFingerprint = [];
        $deleteIds = [];
        $kept = 0;

        foreach ($rows as $t) {
            $note = (string) ($t->note ?? '');
            $method = (string) ($t->method ?? '');
            $isGateway = (stripos($method, 'CamPay') !== false || stripos($method, 'MyPVit') !== false);

            if (preg_match('/hotspot_payment:(\d+)/', $note, $m)) {
                $key = 'hp:' . $m[1];
                if (isset($seenPayment[$key])) {
                    $deleteIds[] = (int) $t->id;
                    continue;
                }
                $seenPayment[$key] = (int) $t->id;
                $kept++;
                continue;
            }

            if ($isGateway) {
                $fp = implode('|', [
                    (string) $t->username,
                    (string) $t->plan_name,
                    (string) $t->price,
                    $method,
                    (string) $t->routers,
                    (string) $t->recharged_on,
                    substr((string) $t->recharged_time, 0, 8),
                ]);
                if (isset($seenFingerprint[$fp])) {
                    $deleteIds[] = (int) $t->id;
                    continue;
                }
                $seenFingerprint[$fp] = (int) $t->id;
            }
            $kept++;
        }

        if (!$dryRun && $deleteIds !== []) {
            foreach (array_chunk($deleteIds, 100) as $chunk) {
                ORM::for_table('tbl_transactions')
                    ->where_in('id', $chunk)
                    ->delete_many();
            }
        }

        return ['deleted' => count($deleteIds), 'kept' => $kept];
    }
}
