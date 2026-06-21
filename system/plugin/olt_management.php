<?php

if (defined('WIFIZONE_OLT_PLUGIN_LOADED')) {
    return;
}
define('WIFIZONE_OLT_PLUGIN_LOADED', true);

function olt_management()
{
    global $ui, $routes, $admin;

    if (!in_array($admin['user_type'] ?? '', ['SuperAdmin', 'Admin'], true)) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
    }

    if (class_exists('WifiZoneFfthSchema')) {
        WifiZoneFfthSchema::install();
    }

    $action = $routes['2'] ?? 'list';

    if ($action === 'add-post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) _post('name'));
        $brand = trim((string) _post('brand'));
        $model = trim((string) _post('model'));
        $ip = trim((string) _post('ip_address'));
        $totalPorts = max(1, (int) _post('total_ports'));
        $address = trim((string) _post('address'));
        $description = trim((string) _post('description'));

        if ($name === '') {
            r2(getUrl('plugin/olt_management'), 'e', Lang::T('All field is required'));
        }
        if (ORM::for_table('tbl_olt')->where('name', $name)->find_one()) {
            r2(getUrl('plugin/olt_management'), 'e', Lang::T('Name Already Exist'));
        }

        $row = ORM::for_table('tbl_olt')->create();
        $row->name = $name;
        $row->brand = $brand;
        $row->model = $model;
        $row->ip_address = $ip;
        $row->total_ports = $totalPorts;
        $row->used_ports = 0;
        $row->address = $address;
        $row->description = $description;
        $row->status = 'Active';
        $row->save();

        r2(getUrl('plugin/olt_management'), 's', Lang::T('Data Created Successfully'));
    }

    if ($action === 'delete') {
        $id = (int) ($routes['3'] ?? 0);
        $row = ORM::for_table('tbl_olt')->find_one($id);
        if ($row) {
            ORM::for_table('tbl_olt_ports')->where('olt_id', $id)->delete_many();
            $row->delete();
        }
        r2(getUrl('plugin/olt_management'), 's', Lang::T('Data Deleted Successfully'));
    }

    $ui->assign('_title', Lang::T('OLT_Management'));
    $ui->assign('_system_menu', 'services');
    $ui->assign('olts', ORM::for_table('tbl_olt')->order_by_desc('id')->find_many());
    $ui->display('admin/olt/list.tpl');
}
