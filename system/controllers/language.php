<?php

/**
 * WifiZone — language switch (English default, French optional)
 */

$action = $routes[1] ?? 'set';
$lang = strtolower(_get('lang', 'english'));

if ($action === 'set') {
  if (Admin::getID()) {
    _admin(false);
  } elseif (User::getID()) {
    _auth(false);
  }

  if (wifizone_apply_language($lang)) {
    $label = $lang === 'french' ? 'Français' : 'English';
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    if ($back === '' || strpos($back, 'language/set') !== false) {
      $back = Admin::getID() ? getUrl('dashboard') : getUrl('home');
    }
    r2($back, 's', Lang::T('Language') . ': ' . $label);
  }
}

r2(Admin::getID() ? getUrl('dashboard') : getUrl('home'), 'e', 'Invalid language');
