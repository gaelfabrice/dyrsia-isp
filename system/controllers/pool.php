<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikPppoe' . '.php';

try {
    $db = ORM::getDb();
    foreach (['tbl_pool', 'tbl_port_pool'] as $table) {
        $columns = $db->query("SHOW COLUMNS FROM $table LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            ORM::raw_execute("ALTER TABLE $table ADD COLUMN admin_id INT NULL DEFAULT NULL AFTER id");
            ORM::raw_execute("UPDATE $table SET admin_id = " . intval($admin['id']) . " WHERE admin_id IS NULL");
        }
    }
} catch (Exception $e) {
}

function pool_scoped_query($table, $admin)
{
    $query = ORM::for_table($table);
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function pool_scoped_router_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

switch ($action) {
    case 'list':

        $name = _post('name');
        if ($name != '') {
            $query = pool_scoped_query('tbl_pool', $admin)->where_like('pool_name', '%' . $name . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = pool_scoped_query('tbl_pool', $admin)->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_pool'); #HOOK
        $ui->display('admin/pool/list.tpl');
        break;

    case 'add':
        $r = pool_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_pool'); #HOOK
        $ui->display('admin/pool/add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = pool_scoped_query('tbl_pool', $admin)->where('id', $id)->find_one();
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_edit_pool'); #HOOK
            $ui->display('admin/pool/edit.tpl');
        } else {
            r2(getUrl('pool/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('delete_pool'); #HOOK
        $d = pool_scoped_query('tbl_pool', $admin)->where('id', $id)->find_one();
        if ($d) {
            if ($d['routers'] != 'radius') {
                try {
                    (new MikrotikPppoe())->remove_pool($d);
                } catch (Throwable $e) {
                    r2(getUrl('pool/list'), 'e', Lang::T('MikroTik connection failed') . ': ' . $e->getMessage());
                }
            }
            $d->delete();

            r2(getUrl('pool/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'sync':
        $pools = pool_scoped_query('tbl_pool', $admin)->find_many();
        $log = '';
        foreach ($pools as $pool) {
            if ($pool['routers'] != 'radius') {
                try {
                    (new MikrotikPppoe())->update_pool($pool, $pool);
                    $log .= 'DONE: ' . $pool['pool_name'] . ': ' . $pool['range_ip'] . '<br>';
                } catch (Throwable $e) {
                    $log .= 'ERROR: ' . $pool['pool_name'] . ': ' . $e->getMessage() . '<br>';
                }
            }
        }
        r2(getUrl('pool/list'), 's', $log);
        break;
    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $local_ip = _post('local_ip');
        $routers = _post('routers');
        run_hook('add_pool'); #HOOK
        $msg = '';
        if (Validator::Length($name, 30, 2) == false) {
            $msg .= 'Name should be between 3 to 30 characters' . '<br>';
        }
        if ($ip_address == '' or $routers == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = pool_scoped_query('tbl_pool', $admin)->where('pool_name', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Pool Name Already Exist') . '<br>';
        }
        if ($msg == '') {
            $b = ORM::for_table('tbl_pool')->create();
            $b->admin_id = $admin['id'];
            $b->local_ip = $local_ip;
            $b->pool_name = $name;
            $b->range_ip = $ip_address;
            $b->routers = $routers;
            $b->save();
            if ($routers != 'radius') {
                try {
                    (new MikrotikPppoe())->add_pool($b);
                } catch (Throwable $e) {
                    r2(getUrl('pool/list'), 'w', Lang::T('Pool saved locally, but MikroTik sync failed') . ': ' . $e->getMessage());
                }
            }
            r2(getUrl('pool/list'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('pool/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $local_ip = _post('local_ip');
        $ip_address = _post('ip_address');
        $routers = _post('routers');
        run_hook('edit_pool'); #HOOK
        $msg = '';

        if ($ip_address == '' or $routers == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $id = _post('id');
        $d = pool_scoped_query('tbl_pool', $admin)->where('id', $id)->find_one();
        $old = pool_scoped_query('tbl_pool', $admin)->where('id', $id)->find_one();
        if (!$d) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($msg == '') {
            $d->local_ip = $local_ip;
            $d->range_ip = $ip_address;
            $d->routers = $routers;
            $d->save();

            if ($routers != 'radius') {
                try {
                    (new MikrotikPppoe())->update_pool($old, $d);
                } catch (Throwable $e) {
                    r2(getUrl('pool/list'), 'w', Lang::T('Pool saved locally, but MikroTik sync failed') . ': ' . $e->getMessage());
                }
            }

            r2(getUrl('pool/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('pool/edit/') . $id, 'e', $msg);
        }
        break;

    case 'port':

        $name = _post('name');
        if ($name != '') {
            $query = pool_scoped_query('tbl_port_pool', $admin)->where_like('pool_name', '%' . $name . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = pool_scoped_query('tbl_port_pool', $admin)->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_port'); #HOOK
        $ui->display('admin/port/list.tpl');
        break;

    case 'add-port':
        $r = pool_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_port'); #HOOK
        $ui->display('admin/port/add.tpl');
        break;

    case 'edit-port':
        $id  = $routes['2'];
        $d = pool_scoped_query('tbl_port_pool', $admin)->where('id', $id)->find_one();
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_edit_port'); #HOOK
            $ui->display('admin/port/edit.tpl');
        } else {
            r2(getUrl('pool/port'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete-port':
        $id  = $routes['2'];
        run_hook('delete_port'); #HOOK
        $d = pool_scoped_query('tbl_port_pool', $admin)->where('id', $id)->find_one();
        if ($d) {
            $d->delete();

            r2(getUrl('pool/port'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'sync':
        $pools = pool_scoped_query('tbl_port_pool', $admin)->find_many();
        $log = '';
        foreach ($pools as $pool) {
            if ($pool['routers'] != 'radius') {
                (new MikrotikPppoe())->update_pool($pool, $pool);
                $log .= 'DONE: ' . $pool['port_name'] . ': ' . $pool['range_port'] . '<br>';
            }
        }
        r2(getUrl('pool/list'), 's', $log);
        break;
    case 'add-port-post':
        $name = _post('name');
        $port_range = _post('port_range');
        $public_ip = _post('public_ip');
        $routers = _post('routers');
        run_hook('add_pool'); #HOOK
        $msg = '';
        if (Validator::Length($name, 30, 2) == false) {
            $msg .= 'Name should be between 3 to 30 characters' . '<br>';
        }
        if ($port_range == '' or $routers == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = pool_scoped_query('tbl_port_pool', $admin)->where('routers', $routers)->find_one();
        if ($d) {
            $msg .= Lang::T('Routers already have ports, each router can only have 1 port range!') . '<br>';
        }
        if ($msg == '') {
            $b = ORM::for_table('tbl_port_pool')->create();
            $b->admin_id = $admin['id'];
            $b->public_ip = $public_ip;
            $b->port_name = $name;
            $b->range_port = $port_range;
            $b->routers = $routers;
            $b->save();
            r2(getUrl('pool/port'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('pool/add-port'), 'e', $msg);
        }
        break;


    case 'edit-port-post':
        $name = _post('name');
        $public_ip = _post('public_ip');
        $range_port = _post('range_port');
        $routers = _post('routers');
        run_hook('edit_port'); #HOOK
        $msg = '';
        $msg = '';
        if (Validator::Length($name, 30, 2) == false) {
            $msg .= 'Name should be between 3 to 30 characters' . '<br>';
        }
        if ($range_port == '' or $routers == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $id = _post('id');
        $d = pool_scoped_query('tbl_port_pool', $admin)->where('id', $id)->find_one();
        $old = pool_scoped_query('tbl_port_pool', $admin)->where('id', $id)->find_one();
        if (!$d) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($msg == '') {
            $d->port_name = $name;
            $d->public_ip = $public_ip;
            $d->range_port = $range_port;
            $d->routers = $routers;
            $d->save();

            r2(getUrl('pool/port'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('pool/edit-port/') . $id, 'e', $msg);
        }
        break;

    default:
        r2(getUrl('pool/list/'), 's', '');
}
