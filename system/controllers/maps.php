<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 * by https://t.me/ibnux
 **/

_admin();
$ui->assign('_system_menu', 'map');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'customer';
}

$ui->assign('xheader', '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">');
$ui->assign('xfooter', '<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>');

function map_scoped_router_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

switch ($action) {
    case 'customer':
        $search = trim((string) _req('search'));
        $mapData = MapGeo::buildCustomerMapData($admin, $search);

        $ui->assign('search', $search);
        $ui->assign('map_points', $mapData['points']);
        $ui->assign('map_center', $mapData['center']);
        $ui->assign('customers_without_coords', $mapData['without_coords']);
        $ui->assign('map_stats', $mapData['stats']);
        $ui->assign('_title', Lang::T('Customer Geo Location Information'));
        $ui->display('admin/maps/customers.tpl');
        break;

    case 'routers':
        $name = trim((string) _req('name'));
        $query = map_scoped_router_query($admin)->where_not_equal('coordinates', '')->order_by_desc('id');
        $query->selects(['id', 'name', 'coordinates', 'description', 'coverage', 'enabled']);
        if ($name !== '') {
            $query->where_like('name', '%' . $name . '%');
        }
        $routers = [];
        foreach ($query->find_many() as $router) {
            $coords = MapGeo::parseCoordinates($router->coordinates);
            if (!$coords) {
                continue;
            }
            $routers[] = [
                'id' => (int) $router->id,
                'name' => (string) $router->name,
                'description' => (string) $router->description,
                'coverage' => (float) $router->coverage,
                'enabled' => (int) $router->enabled,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'coordinates' => $coords['lat'] . ',' . $coords['lng'],
            ];
        }

        $ui->assign('name', $name);
        $ui->assign('d', $routers);
        $ui->assign('map_center', MapGeo::centerFromPoints($routers));
        $ui->assign('_title', Lang::T('Routers Geo Location Information'));
        $ui->display('admin/maps/routers.tpl');
        break;

    case 'odp':
        $name = trim((string) _req('name'));
        $query = ORM::for_table('tbl_odps')->where_not_equal('coordinates', '')->order_by_desc('id');
        $query->selects(['id', 'name', 'port_amount', 'coordinates', 'address', 'attenuation', 'coverage']);
        if ($name !== '') {
            $query->where_like('name', '%' . $name . '%');
        }
        $odps = [];
        foreach ($query->find_many() as $odp) {
            $coords = MapGeo::parseCoordinates($odp->coordinates);
            if (!$coords) {
                continue;
            }
            $odps[] = [
                'id' => (int) $odp->id,
                'name' => (string) $odp->name,
                'port_amount' => (int) $odp->port_amount,
                'attenuation' => (string) $odp->attenuation,
                'address' => (string) $odp->address,
                'coverage' => (float) $odp->coverage,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'coordinates' => $coords['lat'] . ',' . $coords['lng'],
            ];
        }

        $ui->assign('name', $name);
        $ui->assign('d', $odps);
        $ui->assign('map_center', MapGeo::centerFromPoints($odps));
        $ui->assign('_title', Lang::T('ODP Geo Location Information'));
        $ui->display('admin/maps/odps.tpl');
        break;

    default:
        r2(getUrl('maps/customer'), 'e', 'action not defined');
        break;
}
