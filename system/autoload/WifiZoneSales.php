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

        return round(
            self::sumQueryPrices(
                self::incomeBaseQuery($admin)->where('recharged_on', $day)
            ) + self::sumHotspotPaymentsIncome($admin, $day, $day),
            2
        );
    }

    public static function sumIncomeForPeriod($admin, string $startDate, string $endDate): float
    {
        return round(
            self::sumQueryPrices(
                self::incomeBaseQuery($admin)
                    ->where_gte('recharged_on', $startDate)
                    ->where_lte('recharged_on', $endDate)
            ) + self::sumHotspotPaymentsIncome($admin, $startDate, $endDate),
            2
        );
    }

    /**
     * Date métier d'un paiement hotspot (payment_date puis created_date).
     */
    private static function hotspotPaymentBusinessDate($payment): string
    {
        $row = is_array($payment) ? $payment : (method_exists($payment, 'as_array') ? $payment->as_array() : []);
        foreach (['payment_date', 'created_date'] as $field) {
            $raw = trim((string) ($row[$field] ?? ''));
            if ($raw === ''
                || $raw === '0000-00-00'
                || $raw === '0000-00-00 00:00:00'
                || str_starts_with($raw, '0000-00-00')) {
                continue;
            }
            $ts = strtotime($raw);
            if ($ts !== false && $ts > 0) {
                return date('Y-m-d', $ts);
            }
        }

        return '';
    }

    /**
     * Ventes Hotspot CamPay présentes dans tbl_hotspot_payments mais absentes (ou orphelines) de tbl_transactions.
     */
    public static function sumHotspotPaymentsIncome($admin, string $dateFrom, string $dateTo, bool $onlyWithoutTransaction = false): float
    {
        self::ensureHotspotPluginLoaded();
        if (!function_exists('hotspot_payments_query_for_admin')) {
            return 0.0;
        }

        $query = hotspot_payments_query_for_admin($admin)
            ->where('transaction_status', 'paid');

        $total = 0.0;
        foreach ($query->find_many() as $payment) {
            $businessDate = self::hotspotPaymentBusinessDate($payment);
            if ($businessDate === '' || $businessDate < $dateFrom || $businessDate > $dateTo) {
                continue;
            }

            $amount = self::parseAmount($payment->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $linkedTrx = self::findTransactionByHotspotPaymentId((int) ($payment->id ?? 0));
            if ($linkedTrx) {
                if ($onlyWithoutTransaction) {
                    continue;
                }
                if (class_exists('AdminScope') && AdminScope::transactionOwnedByAdmin($linkedTrx, $admin)) {
                    continue;
                }
            }

            $total += $amount;
        }

        return round($total, 2);
    }

    private static function ensureHotspotPluginLoaded(): void
    {
        if (function_exists('hotspot_payments_query_for_admin')) {
            return;
        }

        $plugin = dirname(__DIR__) . '/plugin/hotspot.php';
        if (is_file($plugin)) {
            require_once $plugin;
        }
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
            $total += self::rowSaleAmount($row);
        }

        return round($total, 2);
    }

    /**
     * Montant d'une vente tbl_transactions (fallback tbl_hotspot_payments si price = 0).
     *
     * @param array<string, mixed> $row
     */
    public static function rowSaleAmount(array $row): float
    {
        $price = self::parseAmount($row['price'] ?? 0);
        if ($price > 0) {
            return $price;
        }

        if (preg_match('/hotspot_payment:(\d+)/', (string) ($row['note'] ?? ''), $paymentMatch)) {
            $payment = ORM::for_table('tbl_hotspot_payments')->find_one((int) $paymentMatch[1]);
            if ($payment && (string) ($payment->transaction_status ?? '') === 'paid') {
                return self::parseAmount($payment->amount ?? 0);
            }
        }

        return $price;
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
