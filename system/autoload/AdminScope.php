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

    public static function applyTransactionsQuery($query, $admin)
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

    public static function applyTransactionsQueryByAdminId($query, int $adminId)
    {
        if ($adminId <= 0) {
            return $query->where_raw('1 = 0');
        }

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
        $prefix = $columnPrefix !== '' ? $columnPrefix . '.' : '';
        $routerNames = self::routerNames($adminId);
        if ($routerNames === []) {
            return [$prefix . 'admin_id = ?', [$adminId]];
        }

        $placeholders = implode(',', array_fill(0, count($routerNames), '?'));

        return [
            '(' . $prefix . 'admin_id = ? OR ' . $prefix . 'routers IN (' . $placeholders . '))',
            array_merge([$adminId], $routerNames),
        ];
    }
}
