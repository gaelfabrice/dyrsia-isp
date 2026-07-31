<?php

/**
 * Filtres multi-tenant : ventes/recharges par admin_id (propriétaire courant du routeur).
 * Les ventes portail (admin_id = 0) sont rattachées via nom de routeur / forfait.
 */
class AdminScope
{
    public static function isScoped($admin): bool
    {
        return ($admin['user_type'] ?? '') !== 'SuperAdmin';
    }

    public static function adminId($admin): int
    {
        return (int) ($admin['id'] ?? 0);
    }

    public static function routerNames(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        $names = [];
        foreach (ORM::for_table('tbl_routers')->where('admin_id', $adminId)->find_many() as $router) {
            $name = trim((string) ($router->name ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
            $description = trim((string) ($router->description ?? ''));
            if ($description !== '') {
                $names[] = $description;
            }
            $ip = trim(explode(':', (string) ($router->ip_address ?? ''))[0]);
            if ($ip !== '') {
                $names[] = $ip;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Alias routeur (nom, description, IP) pour matcher tbl_transactions.routers.
     *
     * @param array<string, mixed>|object $routerRow
     * @return array<int, string>
     */
    public static function routerAliasesFromRow($routerRow): array
    {
        $row = is_array($routerRow)
            ? $routerRow
            : (is_object($routerRow) && method_exists($routerRow, 'as_array') ? $routerRow->as_array() : (array) $routerRow);
        $names = [];
        foreach (['name', 'description'] as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $names[] = $value;
            }
        }
        $ip = trim(explode(':', (string) ($row['ip_address'] ?? ''))[0]);
        if ($ip !== '') {
            $names[] = $ip;
        }

        return array_values(array_unique($names));
    }

    /**
     * Après création / transfert de routeur : aligner admin_id des ventes historiques sur le propriétaire actuel.
     *
     * @param array<string, mixed>|object $routerRow
     * @return array{transactions: int, recharges: int}
     */
    public static function syncFinancialRecordsAdminIdForRouter($routerRow, int $adminId): array
    {
        $counts = ['transactions' => 0, 'recharges' => 0];
        if ($adminId <= 0) {
            return $counts;
        }
        $aliases = self::routerAliasesFromRow($routerRow);
        if ($aliases === []) {
            return $counts;
        }
        $placeholders = implode(',', array_fill(0, count($aliases), '?'));

        foreach (ORM::for_table('tbl_transactions')
            ->where_raw('routers IN (' . $placeholders . ')', $aliases)
            ->find_many() as $trx) {
            if ((int) ($trx->admin_id ?? 0) === $adminId) {
                continue;
            }
            $trx->admin_id = $adminId;
            $trx->save();
            $counts['transactions']++;
        }

        foreach (ORM::for_table('tbl_user_recharges')
            ->where_raw('routers IN (' . $placeholders . ')', $aliases)
            ->find_many() as $recharge) {
            if ((int) ($recharge->admin_id ?? 0) === $adminId) {
                continue;
            }
            $recharge->admin_id = $adminId;
            $recharge->save();
            $counts['recharges']++;
        }

        return $counts;
    }

    /** @return array<int, string> */
    public static function planNames(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        $names = [];
        foreach (ORM::for_table('tbl_plans')
            ->where('admin_id', $adminId)
            ->where_in('type', ['Hotspot', 'PPPOE'])
            ->find_many() as $plan) {
            $name = trim((string) ($plan->name_plan ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private static function transactionScopeSql(int $adminId, string $columnPrefix = ''): array
    {
        $prefix = $columnPrefix !== '' ? $columnPrefix . '.' : '';
        $routerNames = self::routerNames($adminId);
        $planNames = self::planNames($adminId);

        $parts = [$prefix . 'admin_id = ?'];
        $params = [$adminId];

        if ($routerNames !== []) {
            $parts[] = '(' . $prefix . 'admin_id = 0 AND ' . $prefix . 'routers IN ('
                . implode(',', array_fill(0, count($routerNames), '?')) . '))';
            $params = array_merge($params, $routerNames);
        }

        if ($planNames !== []) {
            $parts[] = '(' . $prefix . 'admin_id = 0 AND ' . $prefix . 'plan_name IN ('
                . implode(',', array_fill(0, count($planNames), '?')) . '))';
            $params = array_merge($params, $planNames);
        }

        return ['(' . implode(' OR ', $parts) . ')', $params];
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private static function rechargeScopeSql(int $adminId, string $columnPrefix = ''): array
    {
        $prefix = $columnPrefix !== '' ? $columnPrefix . '.' : '';
        $routerNames = self::routerNames($adminId);
        $planNames = self::planNames($adminId);

        $parts = [$prefix . 'admin_id = ?'];
        $params = [$adminId];

        if ($routerNames !== []) {
            $parts[] = '(' . $prefix . 'admin_id = 0 AND ' . $prefix . 'routers IN ('
                . implode(',', array_fill(0, count($routerNames), '?')) . '))';
            $params = array_merge($params, $routerNames);
        }

        if ($planNames !== []) {
            $parts[] = '(' . $prefix . 'admin_id = 0 AND ' . $prefix . 'namebp IN ('
                . implode(',', array_fill(0, count($planNames), '?')) . '))';
            $params = array_merge($params, $planNames);
        }

        return ['(' . implode(' OR ', $parts) . ')', $params];
    }

    public static function applyPlansQuery($query, $admin, $adminIdColumn = 'admin_id')
    {
        $adminId = self::adminId($admin);
        if ($adminId > 0) {
            $query->where($adminIdColumn, $adminId);
        }

        return $query;
    }

    public static function applyRoutersQuery($query, $admin)
    {
        $adminId = self::adminId($admin);
        if ($adminId > 0) {
            $query->where('admin_id', $adminId);
        }

        return $query;
    }

    public static function applyTransactionsQuery($query, $admin)
    {
        if (!self::isScoped($admin)) {
            return $query;
        }

        $adminId = self::adminId($admin);
        [$sql, $params] = self::transactionScopeSql($adminId);

        return $query->where_raw($sql, $params);
    }

    public static function applyTransactionsQueryByAdminId($query, int $adminId)
    {
        if ($adminId <= 0) {
            return $query->where_raw('1 = 0');
        }

        [$sql, $params] = self::transactionScopeSql($adminId);

        return $query->where_raw($sql, $params);
    }

    public static function applyRechargesQuery($query, $admin)
    {
        if (!self::isScoped($admin)) {
            return $query;
        }

        $adminId = self::adminId($admin);
        [$sql, $params] = self::rechargeScopeSql($adminId);

        return $query->where_raw($sql, $params);
    }

    /** @return array{0:string,1:array<int,mixed>} SQL fragment + bound params */
    public static function transactionsSqlFilter(int $adminId, string $columnPrefix = ''): array
    {
        return self::transactionScopeSql($adminId, $columnPrefix);
    }

    /**
     * Une transaction appartient-elle au périmètre admin (portail captif admin_id=0 inclus) ?
     *
     * @param array<string, mixed>|object $trx
     */
    public static function transactionOwnedByAdmin($trx, $admin): bool
    {
        if (!self::isScoped($admin)) {
            return true;
        }

        $row = is_array($trx) ? $trx : (method_exists($trx, 'as_array') ? $trx->as_array() : (array) $trx);
        $adminId = self::adminId($admin);
        if ((int) ($row['admin_id'] ?? 0) === $adminId) {
            return true;
        }

        if ((int) ($row['admin_id'] ?? 0) !== 0) {
            return false;
        }

        $router = trim((string) ($row['routers'] ?? ''));
        if ($router !== '' && in_array($router, self::routerNames($adminId), true)) {
            return true;
        }

        $planName = trim((string) ($row['plan_name'] ?? ''));
        if ($planName !== '' && in_array($planName, self::planNames($adminId), true)) {
            return true;
        }

        return false;
    }
}
