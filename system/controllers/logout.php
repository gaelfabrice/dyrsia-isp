<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

run_hook('customer_logout'); #HOOK
if (session_status() == PHP_SESSION_NONE) session_start();
$wasAdmin = !empty($_SESSION['aid']);
$wasCustomer = !empty($_SESSION['uid']);
if ($wasAdmin) {
    $logoutAdmin = Admin::_info();
    if ($logoutAdmin) {
        $row = is_array($logoutAdmin) ? $logoutAdmin : $logoutAdmin->as_array();
        _log(($row['username'] ?? 'admin') . ' ' . Lang::T('Logout Successful'), 'Activity', (int) ($row['id'] ?? 0));
    }
}
Admin::removeCookie();
User::removeCookie();
session_destroy();
$logoutTarget = $wasAdmin ? 'admin' : ($wasCustomer ? 'login' : 'admin');
_alert(Lang::T('Logout Successful'), 'info', $logoutTarget);
