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

    /** Convertit price (varchar) en montant numérique. */
    public static function parseAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        $normalized = preg_replace('/[^\d.,-]/', '', $raw);
        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0.0;
        }
        if (strpos($normalized, ',') !== false && strpos($normalized, '.') === false) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public static function businessDate($timestamp = null): string
    {
        return date('Y-m-d', $timestamp ?? time());
    }

    /**
     * Requête de base pour les revenus affichés (Finance / dashboard).
     */
    public static function incomeBaseQuery($admin)
    {
        $query = ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator');

        if (class_exists('AdminScope') && AdminScope::isScoped($admin)) {
            AdminScope::applyTransactionsQuery($query, $admin);
        }

        return $query;
    }

    public static function sumIncomeForDay($admin, ?string $day = null): float
    {
        $day = $day ?: self::businessDate();

        return self::sumQueryPrices(
            self::incomeBaseQuery($admin)->where('recharged_on', $day)
        );
    }

    public static function sumIncomeForPeriod($admin, string $startDate, string $endDate): float
    {
        return self::sumQueryPrices(
            self::incomeBaseQuery($admin)
                ->where_gte('recharged_on', $startDate)
                ->where_lte('recharged_on', $endDate)
        );
    }

    /**
     * @param iterable|array $rows rows with id, price, note, username, plan_name, method, routers, recharged_on, recharged_time
     * @return array<int, array<string, mixed>>
     */
    public static function dedupeSaleRows($rows): array
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
        $unique = [];

        foreach ($list as $row) {
            $note = (string) ($row['note'] ?? '');
            if (preg_match('/hotspot_payment:(\d+)/', $note, $m)) {
                $key = 'hp:' . $m[1];
                if (!isset($seenPayment[$key])
                    || self::parseAmount($row['price'] ?? 0) > self::parseAmount($seenPayment[$key]['price'] ?? 0)) {
                    $seenPayment[$key] = $row;
                }
                continue;
            }

            $method = (string) ($row['method'] ?? '');
            if (stripos($method, 'CamPay') !== false || stripos($method, 'MyPVit') !== false) {
                $fp = self::gatewayFingerprint($row);
                if (!isset($seenFingerprint[$fp])
                    || self::parseAmount($row['price'] ?? 0) > self::parseAmount($seenFingerprint[$fp]['price'] ?? 0)) {
                    $seenFingerprint[$fp] = $row;
                }
                continue;
            }

            $unique[] = $row;
        }

        foreach ($seenPayment as $row) {
            $unique[] = $row;
        }
        foreach ($seenFingerprint as $row) {
            $unique[] = $row;
        }

        usort($unique, static function ($a, $b) {
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        return $unique;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function gatewayFingerprint(array $row): string
    {
        $method = (string) ($row['method'] ?? '');

        return implode('|', [
            (string) ($row['username'] ?? ''),
            (string) ($row['plan_name'] ?? ''),
            $method,
            (string) ($row['routers'] ?? ''),
            (string) ($row['recharged_on'] ?? ''),
            substr((string) ($row['recharged_time'] ?? ''), 0, 8),
        ]);
    }

    /**
     * @param iterable|array $rows rows with id, price, note, username, plan_name, method, routers, recharged_on, recharged_time
     */
    public static function sumUniquePrices($rows): float
    {
        $total = 0.0;
        foreach (self::dedupeSaleRows($rows) as $row) {
            $total += self::parseAmount($row['price'] ?? 0);
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
     * Supprime les doublons CamPay/Hotspot (garde la ligne au montant le plus élevé).
     *
     * @return array{deleted:int, kept:int}
     */
    public static function purgeDuplicateHotspotSales($dryRun = true): array
    {
        $rows = ORM::for_table('tbl_transactions')
            ->where_in('type', ['Hotspot', 'PPPOE'])
            ->order_by_asc('id')
            ->find_many();

        $keepIds = [];
        foreach (self::dedupeSaleRows($rows) as $row) {
            $keepIds[(int) ($row['id'] ?? 0)] = true;
        }

        $deleteIds = [];
        foreach ($rows as $t) {
            $id = (int) ($t->id ?? 0);
            if ($id > 0 && !isset($keepIds[$id])) {
                $deleteIds[] = $id;
            }
        }

        if (!$dryRun && $deleteIds !== []) {
            foreach (array_chunk($deleteIds, 100) as $chunk) {
                ORM::for_table('tbl_transactions')
                    ->where_in('id', $chunk)
                    ->delete_many();
            }
        }

        return ['deleted' => count($deleteIds), 'kept' => count($keepIds)];
    }
}
