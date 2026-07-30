<?php

require dirname(__DIR__) . '/init.php';

echo "=== USERS ===\n";
foreach (ORM::for_table('tbl_users')->where_in('user_type', ['Admin', 'SuperAdmin'])->find_many() as $u) {
    echo "id={$u->id} user={$u->username} type={$u->user_type}\n";
}

echo "\n=== ROUTERS ===\n";
foreach (ORM::for_table('tbl_routers')->find_many() as $r) {
    echo "id={$r->id} name={$r->name} admin_id={$r->admin_id}\n";
}

echo "\n=== GLOBAL HOTSPOT (no _admin_) ===\n";
foreach (ORM::for_table('tbl_appconfig')->where_like('setting', 'hotspot_%')->find_many() as $c) {
    if (strpos($c->setting, '_admin_') === false) {
        echo "{$c->setting}=" . substr((string) $c->value, 0, 100) . "\n";
    }
}

echo "\n=== SCOPED hotspot_page_title ===\n";
foreach (ORM::for_table('tbl_appconfig')->where_like('setting', 'hotspot_page_title_admin_%')->find_many() as $c) {
    echo "{$c->setting}={$c->value}\n";
}

echo "\n=== SCOPED login router ===\n";
foreach (ORM::for_table('tbl_appconfig')->where_like('setting', 'hotspot_login_router_admin_%')->find_many() as $c) {
    echo "{$c->setting}={$c->value}\n";
}
