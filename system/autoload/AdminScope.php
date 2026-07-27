<?php

/**
 * Filtres multi-tenant : ventes/recharges visibles par admin propriétaire du routeur.
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
            $parts[] = $prefix . 'routers IN (' . implode(',', array_fill(0, count($routerNames), '?')) . ')';
            $params = array_merge($params, $routerNames);
        }

        if ($planNames !== []) {
            $parts[] = '(' . $prefix . 'admin_id = 0 AND ' . $prefix . 'plan_name IN ('
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
        $routerNames = self::routerNames($adminId);
        if ($routerNames === []) {
            return $query->where('admin_id', $adminId);
        }

        $placeholders = implode(',', array_fill(0, count($routerNames), '?'));

        return $query->where_raw(
            '(admin_id = ? OR routers IN (' . $placeholders . '))',
            array_merge([$adminId], $routerNames)
        );
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

        $router = trim((string) ($row['routers'] ?? ''));
        if ($router !== '' && in_array($router, self::routerNames($adminId), true)) {
            return true;
        }

        $planName = trim((string) ($row['plan_name'] ?? ''));
        if ((int) ($row['admin_id'] ?? 0) === 0 && $planName !== '' && in_array($planName, self::planNames($adminId), true)) {
            return true;
        }

        return false;
    }
}
