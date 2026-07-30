<?php

/**
 * SuperAdmin "login as user" (admin or customer) with safe exit.
 */
class Impersonate
{
    public static function isActive()
    {
        return !empty($_SESSION['impersonator']) && is_array($_SESSION['impersonator']);
    }

    public static function info()
    {
        return $_SESSION['impersonator'] ?? null;
    }

    public static function requireSuperAdmin($admin)
    {
        if (!$admin || ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
    }

    /**
     * While impersonating, force the acting admin row (not the SuperAdmin cookie/session).
     *
     * @param object|array|null $admin
     * @return object|array|null
     */
    public static function resolveActingAdmin($admin)
    {
        if (!self::isActive()) {
            return $admin;
        }
        $imp = self::info();
        $mode = $imp['mode'] ?? '';
        if ($mode === 'customer') {
            return null;
        }
        if ($mode !== 'admin') {
            return $admin;
        }
        $targetId = (int) ($imp['target_id'] ?? 0);
        if ($targetId < 1) {
            return $admin;
        }
        $currentId = 0;
        if (is_array($admin)) {
            $currentId = (int) ($admin['id'] ?? 0);
        } elseif (is_object($admin)) {
            $currentId = (int) ($admin->id ?? 0);
        }
        if ($currentId === $targetId) {
            return $admin;
        }
        $_SESSION['aid'] = $targetId;
        $row = ORM::for_table('tbl_users')->find_one($targetId);
        if (!$row) {
            return $admin;
        }
        $_SESSION['user_type'] = $row->user_type;
        if ((int) Admin::getID() !== $targetId) {
            Admin::setCookie($targetId);
        }
        return $row;
    }

    /**
     * @param object|array|null $admin
     * @return array|null
     */
    public static function adminToArray($admin)
    {
        if (!$admin) {
            return null;
        }
        if (is_array($admin)) {
            return $admin;
        }
        if (is_object($admin) && method_exists($admin, 'as_array')) {
            return $admin->as_array();
        }
        return (array) $admin;
    }

    public static function startAsAdmin($superAdmin, $targetAdminId)
    {
        $targetAdminId = (int) $targetAdminId;
        $target = ORM::for_table('tbl_users')->find_one($targetAdminId);
        if (!$target || $target->status !== 'Active') {
            throw new RuntimeException(Lang::T('Account Not Found'));
        }
        if ($target->user_type === 'SuperAdmin' && (int) $superAdmin['id'] !== $targetAdminId) {
            throw new RuntimeException(Lang::T('Cannot impersonate another Super Administrator'));
        }

        self::storeImpersonator($superAdmin, 'admin', [
            'target_id' => $targetAdminId,
            'target_label' => $target->fullname . ' (' . $target->username . ')',
        ]);

        User::removeCookie();
        unset($_SESSION['uid']);

        $tenantRow = Tenant::findTenantForUserId($targetAdminId);
        if ($tenantRow) {
            $tenantObj = Tenant::findById((int) $tenantRow['id']);
            if ($tenantObj) {
                Tenant::setCurrent($tenantObj);
            }
        } else {
            Tenant::setCurrent(null);
        }

        $_SESSION['aid'] = $targetAdminId;
        $_SESSION['user_type'] = $target->user_type;
        Admin::setCookie($targetAdminId);
        $target->last_login = date('Y-m-d H:i:s');
        $target->save();

        _log(
            '[Impersonation] SuperAdmin ' . $superAdmin['username'] . ' → admin ' . $target->username,
            'Impersonation',
            (int) $superAdmin['id']
        );
    }

    public static function startAsCustomer($superAdmin, $customerId)
    {
        $customerId = (int) $customerId;
        $customer = ORM::for_table('tbl_customers')->find_one($customerId);
        if (!$customer) {
            throw new RuntimeException(Lang::T('Customer not found'));
        }

        self::storeImpersonator($superAdmin, 'customer', [
            'target_id' => $customerId,
            'target_label' => $customer->fullname . ' (' . $customer->username . ')',
        ]);

        Admin::removeCookie();
        unset($_SESSION['aid'], $_SESSION['user_type']);
        Tenant::setCurrent(null);

        $_SESSION['uid'] = $customerId;
        User::setCookie($customerId);
        $customer->last_login = date('Y-m-d H:i:s');
        $customer->save();

        _log(
            '[Impersonation] SuperAdmin ' . $superAdmin['username'] . ' → customer ' . $customer->username,
            'Impersonation',
            (int) $superAdmin['id']
        );
    }

    public static function exitToSuperAdmin()
    {
        if (!self::isActive()) {
            return false;
        }
        $imp = $_SESSION['impersonator'];
        $superId = (int) ($imp['superadmin_id'] ?? 0);
        $mode = $imp['mode'] ?? '';

        User::removeCookie();
        unset($_SESSION['uid']);

        if ($superId > 0) {
            $super = ORM::for_table('tbl_users')->find_one($superId);
            if ($super) {
                $_SESSION['aid'] = $superId;
                $_SESSION['user_type'] = $super->user_type;
                Admin::setCookie($superId);
                Tenant::setCurrent(null);
            }
        }

        unset($_SESSION['impersonator']);

        if ($superId > 0) {
            $username = ORM::for_table('tbl_users')->find_one($superId)->username ?? $superId;
            _log('[Impersonation] Exit to SuperAdmin ' . $username . ' (was ' . $mode . ')', 'Impersonation', $superId);
        }

        return true;
    }

    /**
     * Comptes impersonnables : liste par défaut (q vide) ou recherche (q ≥ 2 caractères).
     *
     * @return array{admins: array<int, array>, customers: array<int, array>}
     */
    public static function searchTargets($q, $adminLimit = 80, $customerLimit = 150, $searchLimit = 20)
    {
        $q = trim((string) $q);
        if (strlen($q) >= 2) {
            $like = '%' . $q . '%';
            $admins = ORM::for_table('tbl_users')
                ->where('status', 'Active')
                ->where_not_equal('user_type', 'SuperAdmin')
                ->where_raw('(username LIKE ? OR fullname LIKE ? OR email LIKE ?)', [$like, $like, $like])
                ->order_by_asc('username')
                ->limit((int) $searchLimit)
                ->find_array();
            $customers = ORM::for_table('tbl_customers')
                ->where_raw('(username LIKE ? OR fullname LIKE ? OR email LIKE ? OR phonenumber LIKE ?)', [$like, $like, $like, $like])
                ->order_by_asc('username')
                ->limit((int) $searchLimit)
                ->find_array();
        } else {
            $admins = ORM::for_table('tbl_users')
                ->where('status', 'Active')
                ->where_not_equal('user_type', 'SuperAdmin')
                ->order_by_asc('username')
                ->limit((int) $adminLimit)
                ->find_array();
            $customers = ORM::for_table('tbl_customers')
                ->order_by_asc('username')
                ->limit((int) $customerLimit)
                ->find_array();
        }

        return ['admins' => $admins, 'customers' => $customers];
    }

    public static function assignUi($ui)
    {
        if (!self::isActive() || empty($ui)) {
            return;
        }
        $imp = self::info();
        $ui->assign('impersonation_active', true);
        $ui->assign('impersonation_mode', $imp['mode'] ?? '');
        $ui->assign('impersonation_label', $imp['target_label'] ?? '');
        $ui->assign('impersonation_exit_url', getUrl('impersonate/exit'));
    }

    private static function storeImpersonator($superAdmin, $mode, array $extra)
    {
        $_SESSION['impersonator'] = array_merge([
            'superadmin_id' => (int) $superAdmin['id'],
            'superadmin_username' => $superAdmin['username'],
            'mode' => $mode,
            'started_at' => date('Y-m-d H:i:s'),
        ], $extra);
    }
}
