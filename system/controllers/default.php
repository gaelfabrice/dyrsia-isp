<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

if (!empty($_GET['nux-mac']) || !empty($_GET['nux-ip']) || !empty($_GET['nux-router'])) {
    $handler = 'login';
} else {
    $handler = 'welcome';
}
include($root_path . File::pathFixer('system/controllers/' . $handler . '.php'));