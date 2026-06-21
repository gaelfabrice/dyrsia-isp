<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Hotspot Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$ui->assign('_admin', $admin);

function services_scoped_router_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function services_scoped_plan_query($admin)
{
    $query = ORM::for_table('tbl_plans');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

/**
 * Push plan changes to the device driver (MikroTik, etc.) without aborting DB save on failure.
 *
 * @return string|null Error message when sync failed, null on success.
 */
function services_ensure_pppoe_expire_plans($admin)
{
    global $_app_stage;

    if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
        return;
    }

    $routerQuery = ORM::for_table('tbl_plans')
        ->distinct()
        ->select('routers')
        ->where('type', 'PPPOE')
        ->where('is_radius', 0)
        ->where_not_equal('routers', '');
    if ($admin['user_type'] != 'SuperAdmin') {
        $routerQuery->where('admin_id', $admin['id']);
    }

    $routers = [];
    foreach ($routerQuery->find_many() as $row) {
        $name = trim((string) ($row['routers'] ?? ''));
        if ($name !== '') {
            $routers[$name] = true;
        }
    }

    foreach (array_keys($routers) as $routerName) {
        Mikrotik::ensurePppoeExpiredPlanDb($routerName, $admin);
    }
}

/**
 * Reassign PPPoE plans whose routers field does not match any configured MikroTik.
 *
 * @return int Number of plans updated
 */
function services_reassign_orphan_pppoe_plans($admin)
{
    $validRouterNames = [];
    foreach (services_scoped_router_query($admin)->find_many() as $routerRow) {
        $name = trim((string) $routerRow['name']);
        if ($name !== '') {
            $validRouterNames[] = $name;
        }
    }

    if (empty($validRouterNames)) {
        return 0;
    }

    $targetRouter = count($validRouterNames) === 1 ? $validRouterNames[0] : '';
    $updated = 0;
    $plans = services_scoped_plan_query($admin)->where('type', 'PPPOE')->find_many();

    foreach ($plans as $plan) {
        $planRouter = trim((string) ($plan['routers'] ?? ''));
        $isRadius = !empty($plan['is_radius']);
        if ($isRadius) {
            continue;
        }

        $resolved = $planRouter !== '' ? Mikrotik::resolveRouterRecord($planRouter, $admin) : null;
        if ($resolved) {
            $canonical = trim((string) $resolved['name']);
            if ($canonical !== '' && $planRouter !== $canonical) {
                $plan->routers = $canonical;
                $plan->save();
                $updated++;
            }
            continue;
        }

        if ($targetRouter !== '') {
            $plan->routers = $targetRouter;
            $plan->save();
            $updated++;
        }
    }

    return $updated;
}

function services_sync_plan_to_device($deviceClass, $plan, $action, $oldPlan = null)
{
    global $_app_stage;

    if ($_app_stage == 'demo' || $_app_stage == 'Demo') {
        return null;
    }

    $dvc = Package::getDevice($plan);
    if (!file_exists($dvc)) {
        return Lang::T('Devices Not Found');
    }

    require_once $dvc;
    $driver = new $deviceClass();

    try {
        if ($action === 'add') {
            $driver->add_plan($plan);
        } elseif ($action === 'update' && $oldPlan) {
            $driver->update_plan($oldPlan, $plan);
        } elseif ($action === 'remove') {
            $driver->remove_plan($plan);
        }
        return null;
    } catch (Throwable $e) {
        if (function_exists('_log')) {
            _log('[MikroTik] ' . $action . ' plan "' . ($plan['name_plan'] ?? '') . '": ' . $e->getMessage(), 'Error');
        }
        return class_exists('WifiZoneSecurity')
            ? WifiZoneSecurity::safeExceptionMessage($e)
            : $e->getMessage();
    }
}

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

try {
    $db = ORM::getDb();
    foreach (['tbl_plans', 'tbl_pool', 'tbl_port_pool'] as $table) {
        $columns = $db->query("SHOW COLUMNS FROM $table LIKE 'admin_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columns)) {
            ORM::raw_execute("ALTER TABLE $table ADD COLUMN admin_id INT NULL DEFAULT NULL AFTER id");
            ORM::raw_execute("UPDATE $table SET admin_id = " . intval($admin['id']) . " WHERE admin_id IS NULL");
        }
    }
} catch (Exception $e) {
}

switch ($action) {
    case 'sync':
        set_time_limit(-1);
        if ($routes['2'] == 'hotspot') {
            if (DemoShowcase::isActive($admin)) {
                r2(getUrl('services/hotspot'), 'w', 'Compte vitrine démo : synchronisation MikroTik désactivée.');
            }
            $plans = services_scoped_plan_query($admin)->where('type', 'Hotspot')->find_many();
            $routerNames = [];
            foreach ($plans as $plan) {
                $routerName = trim((string) ($plan['routers'] ?? ''));
                if ($routerName !== '' && strcasecmp($routerName, 'radius') !== 0) {
                    $routerNames[$routerName] = true;
                }
            }
            $log = '';
            foreach (array_keys($routerNames) as $routerName) {
                $router = services_scoped_router_query($admin)->where('name', $routerName)->find_one();
                if (!$router) {
                    $log .= "FAILED : routeur « {$routerName} » introuvable<br>";
                    continue;
                }
                try {
                    $password = class_exists('Mikrotik')
                        ? Mikrotik::routerPassword($router['password'])
                        : $router['password'];
                    $client = Mikrotik::getClient($router['ip_address'], $router['username'], $password, 20);
                    if (!$client) {
                        $log .= "FAILED : {$routerName} — connexion MikroTik impossible<br>";
                        continue;
                    }
                    $result = Mikrotik::syncHotspotPlans($client, $routerName, $admin);
                    if (empty($result['ok'])) {
                        $log .= "FAILED : {$routerName} — " . htmlspecialchars(implode(' | ', $result['errors'] ?? ['erreur']), ENT_QUOTES, 'UTF-8') . "<br>";
                    } else {
                        $log .= "DONE : {$routerName} — "
                            . (int) ($result['upserted'] ?? 0)
                            . ' forfait(s), '
                            . (int) ($result['removed'] ?? 0)
                            . " ancien(s) supprimé(s)<br>";
                    }
                } catch (Throwable $e) {
                    $log .= "FAILED : {$routerName} — " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
                }
            }
            if ($log === '') {
                $log = 'Aucun forfait Hotspot avec routeur MikroTik configuré.';
            }
            r2(getUrl('services/hotspot'), 's', $log);
        } else if ($routes['2'] == 'pppoe') {
            $reassigned = services_reassign_orphan_pppoe_plans($admin);
            $log = '';
            if ($reassigned > 0) {
                $log .= "INFO : {$reassigned} forfait(s) PPPoE réassigné(s) au routeur configuré.<br>";
            }

            global $config;
            $routers = [];
            $plans = services_scoped_plan_query($admin)->where('type', 'PPPOE')->find_many();
            foreach ($plans as $plan) {
                $routerName = trim((string) ($plan['routers'] ?? ''));
                if ($routerName !== '' && strcasecmp($routerName, 'radius') !== 0) {
                    $routers[$routerName] = true;
                }
            }

            if (empty($routers)) {
                r2(getUrl('services/pppoe'), 'w', 'Aucun forfait PPPoE avec routeur MikroTik configuré.');
            }

            foreach (array_keys($routers) as $routerName) {
                $router = services_scoped_router_query($admin)->where('name', $routerName)->find_one();
                if (!$router) {
                    $log .= "FAILED : routeur « " . htmlspecialchars($routerName, ENT_QUOTES, 'UTF-8') . " » introuvable<br>";
                    continue;
                }
                try {
                    $routerPassword = $router['password'];
                    if (function_exists('lcg_decrypt')) {
                        $routerPassword = rtrim(lcg_decrypt($routerPassword));
                    } elseif (class_exists('Encryption') && method_exists('Encryption', 'decrypt')) {
                        $routerPassword = rtrim(Encryption::decrypt($routerPassword));
                    }
                    $client = Mikrotik::getClient($router['ip_address'], $router['username'], $routerPassword, 60);
                    if (!$client) {
                        $log .= "FAILED : connexion MikroTik « " . htmlspecialchars($routerName, ENT_QUOTES, 'UTF-8') . " »<br>";
                        continue;
                    }
                    $bridgeName = trim((string) ($config['pppoe_setup_bridge'] ?? 'bridge-pppoe'));
                    $sync = Mikrotik::fullPppoeRouterSync($client, $routerName, $admin, $bridgeName);
                    $planSync = $sync['plans'] ?? [];
                    $secretSync = $sync['secrets'] ?? [];
                    $log .= "DONE : "
                        . htmlspecialchars($routerName, ENT_QUOTES, 'UTF-8')
                        . ' — '
                        . (int) ($planSync['upserted'] ?? 0)
                        . ' profil(s), '
                        . (int) ($secretSync['synced'] ?? 0)
                        . ' client(s), '
                        . (int) ($secretSync['removed'] ?? 0)
                        . ' secret(s) orphelin(s) supprimé(s)';
                    if (!empty($secretSync['disconnected'])) {
                        $log .= ', ' . (int) $secretSync['disconnected'] . ' session(s) coupée(s)';
                    }
                    if (!empty($sync['firewall']['added'])) {
                        $log .= ', firewall anti-contournement activé';
                    }
                    if (!empty($sync['captive']['ok'])) {
                        $log .= ', captive expiré OK';
                    }
                    if (!empty($sync['suspensions']['enforced'])) {
                        $log .= ', ' . (int) $sync['suspensions']['enforced'] . ' expiré(s) suspendu(s)';
                    }
                    $log .= '<br>';
                    foreach ($sync['errors'] ?? [] as $err) {
                        $log .= "WARN : " . htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') . "<br>";
                    }
                } catch (Throwable $e) {
                    $log .= "FAILED : "
                        . htmlspecialchars($routerName, ENT_QUOTES, 'UTF-8')
                        . " — "
                        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                        . "<br>";
                } catch (Exception $e) {
                    $log .= "FAILED : "
                        . htmlspecialchars($routerName, ENT_QUOTES, 'UTF-8')
                        . " — "
                        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                        . "<br>";
                }
            }
            r2(getUrl('services/pppoe'), 's', $log);
        }
        r2(getUrl('services/hotspot'), 'w', 'Unknown command');
    case 'pppoe-deploy-captive':
        set_time_limit(180);
        if ($_app_stage == 'Demo') {
            r2(getUrl('services/pppoe'), 'e', 'You cannot perform this action in Demo mode');
        }
        $routerName = trim((string) _post('router'));
        if ($routerName === '') {
            r2(getUrl('services/pppoe'), 'e', 'Sélectionnez un routeur PPPoE.');
        }
        $router = services_scoped_router_query($admin)->where('name', $routerName)->find_one();
        if (!$router) {
            r2(getUrl('services/pppoe'), 'e', Lang::T('Router not found'));
        }
        try {
            $routerPassword = $router['password'];
            if (function_exists('lcg_decrypt')) {
                $routerPassword = rtrim(lcg_decrypt($routerPassword));
            } elseif (class_exists('Encryption') && method_exists('Encryption', 'decrypt')) {
                $routerPassword = rtrim(Encryption::decrypt($routerPassword));
            }
            $client = Mikrotik::getClient($router['ip_address'], $router['username'], $routerPassword, 15);
            if (!$client) {
                r2(getUrl('services/pppoe'), 'e', 'Connexion MikroTik impossible.');
            }
            $portalUrl = Mikrotik::buildPppoeCaptivePortalUrl($routerName, $config);
            $backendUrl = Mikrotik::resolvePppoeCaptiveBackendUrl($config);
            if ($portalUrl === '' || $backendUrl === '') {
                r2(
                    getUrl('services/pppoe'),
                    'e',
                    'URL backend captive introuvable. Settings → Hotspot → Hotspot API URL : '
                    . 'http://10.0.0.2:8080 (VPN dev Mac) ou https://wifizones.org (prod). '
                    . 'Ne pas utiliser localhost.'
                );
            }
            $expiredPlan = Mikrotik::ensurePppoeExpiredPlan($client, $routerName, $admin);
            $planSync = Mikrotik::syncPppoePlans($client, $routerName, $admin);
            $captive = Mikrotik::ensurePppoeExpiredCaptive($client, $portalUrl, $backendUrl, $routerName);
            $suspensions = Mikrotik::syncExpiredPppoeSuspensions($client, $routerName, $admin);
            if (empty($planSync['ok']) || empty($captive['ok']) || empty($expiredPlan['ok'])) {
                $errors = array_merge($planSync['errors'] ?? [], $captive['errors'] ?? [], $expiredPlan['errors'] ?? [], $suspensions['errors'] ?? []);
                r2(getUrl('services/pppoe'), 'e', 'Déploiement PPPoE partiel : ' . implode(' | ', $errors));
            }
            r2(
                getUrl('services/pppoe'),
                's',
                'PPPoE déployé sur « '
                . $routerName
                . ' » : '
                . (int) ($planSync['upserted'] ?? 0)
                . ' profil(s), EXPIRE lié à '
                . (int) ($expiredPlan['linked'] ?? 0)
                . ' forfait(s), '
                . (int) ($suspensions['enforced'] ?? 0)
                . ' client(s) expiré(s) suspendu(s), captive OK. Portail : '
                . $portalUrl
            );
        } catch (Throwable $e) {
            r2(getUrl('services/pppoe'), 'e', 'Échec déploiement PPPoE : ' . $e->getMessage());
        }
        break;
    case 'hotspot':
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'Hotspot')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->where('tbl_plans.type', 'Hotspot')->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'Hotspot');
        if ($admin['user_type'] != 'SuperAdmin') {
            $query->where('tbl_plans.admin_id', $admin['id']);
        }

        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::injectPlansList($ui, 'Hotspot', 20, $append_url);
        } else {
            $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);
            $ui->assign('d', $d);
        }
        run_hook('view_list_plans'); #HOOK
        $ui->display('admin/hotspot/list.tpl');
        break;
    case 'add':
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = services_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        run_hook('view_add_plan'); #HOOK
        $ui->display('admin/hotspot/add.tpl');
        break;

    case 'edit':
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikHotspot';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_plan'); #HOOK
            $ui->display('admin/hotspot/edit.tpl');
        } else {
            r2(getUrl('services/hotspot'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_plan'); #HOOK
            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    throw new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/hotspot'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;
case 'hotspot-bulk-delete':
        $ids = _post('ids');
        if (empty($ids) || !is_array($ids)) {
            r2(getUrl('services/hotspot'), 'e', "Please select at least one item!");
        }
        foreach ($ids as $id) {
                $d = ORM::for_table('tbl_plans')->find_one($id);
                if ($d) {
                    run_hook('delete_plan'); #HOOK
                    $dvc = Package::getDevice($d);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            // মাইক্রোটিক বা ডিভাইস থেকে প্ল্যান রিমুভ করা
                            (new $d['device'])->remove_plan($d);
                        }
                    }
                    // ডাটাবেজ থেকে ডিলিট করা
                    $d->delete();
                }
            }
            r2(getUrl('services/hotspot'), 's', Lang::T('Data Deleted Successfully'));
        break;
    case 'add-post':
        $name = _post('name');
        $plan_type = _post('plan_type'); //Personal / Business
        $radius = _post('radius');
        $typebp = _post('typebp');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }
        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->where('type', 'Hotspot')->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }

        run_hook('add_plan'); #HOOK

        if ($msg == '') {
            // Create new plan
            $d = ORM::for_table('tbl_plans')->create();
            $d->admin_id = $admin['id'];
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->type = 'Hotspot';
            $d->typebp = $typebp;
            $d->plan_type = $plan_type;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $syncError = services_sync_plan_to_device($d['device'], $d, 'add');
            if ($syncError) {
                r2(
                    getUrl('services/edit/') . $d->id(),
                    'w',
                    Lang::T('Data Created Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/edit/') . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $id = _post('id');
        $name = _post('name');
        $plan_type = _post('plan_type');
        $id_bw = _post('id_bw');
        $typebp = _post('typebp');
        $price = _post('price');
        $price_old = _post('price_old');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $plan_expired = _post('plan_expired', '0');
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $routers = _post('routers');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');
        $expired_date = _post('expired_date');
        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($price_old <= $price) {
            $price_old = '';
        }

        run_hook('edit_plan'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];

            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->price_old = $price_old;
            $d->typebp = $typebp;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->plan_type = $plan_type;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $syncError = services_sync_plan_to_device($d['device'], $d, 'update', $old);
            if ($syncError) {
                r2(
                    getUrl('services/edit/') . $d->id(),
                    'w',
                    Lang::T('Data Updated Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/hotspot'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/edit/') . $id, 'e', $msg);
        }
        break;

    case 'pppoe':
        $ui->assign('_title', Lang::T('PPPOE Plans'));

        $name = _post('name');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'PPPOE')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $ui->assign('mikrotik_routers', services_scoped_router_query($admin)->order_by_asc('name')->find_many());
        global $config;
        $pppoeBackend = Mikrotik::resolvePppoeCaptiveBackendUrl(is_array($config) ? $config : []);
        $ui->assign('pppoe_captive_backend', $pppoeBackend);
        $ui->assign(
            'pppoe_portal_base',
            $pppoeBackend !== ''
                ? rtrim($pppoeBackend, '/') . '/index.php?_route=plugin/pppoe_portal&router='
                : rtrim(APP_URL, '/') . '/index.php?_route=plugin/pppoe_portal&router='
        );
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'PPPOE');
        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::injectPlansList($ui, 'PPPoE', 20, $append_url);
        } else {
            services_ensure_pppoe_expire_plans($admin);
            $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);
            $ui->assign('d', $d);
        }
        run_hook('view_list_ppoe'); #HOOK
        $ui->display('admin/pppoe/list.tpl');
        break;

    case 'pppoe-add':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = services_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        $ui->assign('pppoe_pool_fetch_url', getUrl('autoload/router-pools'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        run_hook('view_add_ppoe'); #HOOK
        $ui->display('admin/pppoe/add.tpl');
        break;

    case 'pppoe-edit':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikPppoe';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = services_scoped_router_query($admin)->find_many();
            }
            $ui->assign('r', $r);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_ppoe'); #HOOK
            $ui->display('admin/pppoe/edit.tpl');
        } else {
            r2(getUrl('services/pppoe'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'pppoe-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (Mikrotik::isPppoeSystemExpirePlan($d)) {
                r2(getUrl('services/pppoe'), 'e', Lang::T('PPPoE system EXPIRE plan cannot be deleted'));
            }
            run_hook('delete_ppoe'); #HOOK

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    throw new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/pppoe'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;
        
        case 'pppoe-bulk-delete':

    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];

    if (!is_array($ids) || empty($ids)) {
        r2(getUrl('services/pppoe'), 'e', 'No items selected');
    }

    foreach ($ids as $id) {

        $d = ORM::for_table('tbl_plans')->find_one($id);

        if ($d) {
            if (Mikrotik::isPppoeSystemExpirePlan($d)) {
                continue;
            }
            run_hook('delete_ppoe');

            $dvc = Package::getDevice($d);

            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                }
            }

            $d->delete();
        }
    }

    r2(getUrl('services/pppoe'), 's', 'Selected plans deleted');

break;

    case 'pppoe-add-post':
        $name = _post('name_plan');
        $plan_type = _post('plan_type');
        $radius = _post('radius');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool_mode = _post('pool_mode') === 'new' ? 'new' : 'existing';
        $pool = $pool_mode === 'new'
            ? trim((string) _post('pool_name_new'))
            : trim((string) _post('pool_existing'));
        $pool_range = trim((string) _post('pool_range'));
        $pool_local_ip = trim((string) _post('pool_local_ip'));
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');


        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if ($pool_mode === 'new' && $pool_range === '' && empty($radius) && $routers !== '') {
            $msg .= Lang::T('Range IP') . ' — ' . Lang::T('Required for a new pool on MikroTik') . '<br>';
        }
        if (strtoupper(trim((string) $name)) === 'EXPIRE') {
            $msg .= Lang::T('The EXPIRE plan name is reserved for the system plan') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            if (empty($radius) && $routers !== '') {
                try {
                    $pool = Mikrotik::ensureRouterIpPool($routers, $pool, $pool_range, $pool_local_ip, $admin, $pool_mode);
                } catch (Throwable $e) {
                    r2(getUrl('services/pppoe-add'), 'e', $e->getMessage());
                }
            }
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);
            $d = ORM::for_table('tbl_plans')->create();
            $d->admin_id = $admin['id'];
            $d->type = 'PPPOE';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->pool = $pool;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->save();

            if (!$d->is_radius && !empty($d->routers) && strtoupper(trim((string) $d->name_plan)) !== 'EXPIRE') {
                Mikrotik::ensurePppoeExpiredPlanDb($d->routers, $admin);
                $expirePlan = ORM::for_table('tbl_plans')
                    ->where('type', 'PPPOE')
                    ->where('routers', $d->routers)
                    ->where('name_plan', 'EXPIRE')
                    ->find_one();
                if ($expirePlan && (int) $d->plan_expired !== (int) $expirePlan->id) {
                    $d->plan_expired = (int) $expirePlan->id;
                    $d->save();
                }
            }

            $syncError = services_sync_plan_to_device($d['device'], $d, 'add');
            if ($syncError) {
                r2(
                    getUrl('services/pppoe'),
                    'w',
                    Lang::T('Data Created Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/pppoe'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/pppoe-add'), 'e', $msg);
        }
        break;

    case 'edit-pppoe-post':
        $id = _post('id');
        $plan_type = _post('plan_type');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $price_old = _post('price_old');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $plan_expired = _post('plan_expired');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($price_old <= $price) {
            $price_old = '';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $isSystemExpire = $d && Mikrotik::isPppoeSystemExpirePlan($d);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        if (!$isSystemExpire && strtoupper(trim((string) $name)) === 'EXPIRE') {
            $msg .= Lang::T('The EXPIRE plan name is reserved for the system plan') . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);

            if ($isSystemExpire) {
                $name = 'EXPIRE';
                $enabled = 1;
                $price = 0;
                $plan_expired = 0;
            }

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->price_old = $price_old;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->save();

            $syncError = services_sync_plan_to_device($d['device'], $d, 'update', $old);
            if ($syncError) {
                r2(
                    getUrl('services/pppoe-edit/') . $id,
                    'w',
                    Lang::T('Data Updated Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/pppoe'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/pppoe-edit/') . $id, 'e', $msg);
        }
        break;
    case 'balance':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_list_balance'); #HOOK
        $ui->display('admin/balance/list.tpl');
        break;
    case 'balance-add':
        $ui->assign('_title', Lang::T('Balance Plans'));
        run_hook('view_add_balance'); #HOOK
        $ui->display('admin/balance/add.tpl');
        break;
    case 'balance-edit':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        $ui->assign('d', $d);
        run_hook('view_edit_balance'); #HOOK
        $ui->display('admin/balance/edit.tpl');
        break;
    case 'balance-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_balance'); #HOOK
            $d->delete();
            r2(getUrl('services/balance'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;
    case 'balance-edit-post':
        $id = _post('id');
        $name = _post('name');
        $price = _post('price');
        $price_old = _post('price_old');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        if ($price_old <= $price) {
            $price_old = '';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $d->name_plan = $name;
            $d->price = $price;
            $d->enabled = $enabled;
            $d->price_old = $price_old;
            $d->prepaid = 'yes';
            $d->save();

            r2(getUrl('services/balance'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/balance-edit/') . $id, 'e', $msg);
        }
        break;
    case 'balance-add-post':
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $d = ORM::for_table('tbl_plans')->create();
            $d->admin_id = $admin['id'];
            $d->type = 'Balance';
            $d->name_plan = $name;
            $d->id_bw = 0;
            $d->price = $price;
            $d->validity = 0;
            $d->validity_unit = 'Months';
            $d->routers = '';
            $d->pool = '';
            $d->enabled = $enabled;
            $d->prepaid = 'yes';
            $d->save();

            r2(getUrl('services/balance'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/balance-add'), 'e', $msg);
        }
        break;
    case 'vpn':
        $ui->assign('_title', Lang::T('VPN Plans'));

        $name = _post('name');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'VPN')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'VPN');
        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);

        $ui->assign('d', $d);
        run_hook('view_list_vpn'); #HOOK
        $ui->display('admin/vpn/list.tpl');
        break;

    case 'vpn-add':
        $ui->assign('_title', Lang::T('VPN Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = services_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        run_hook('view_add_vpn'); #HOOK
        $ui->display('admin/vpn/add.tpl');
        break;

    case 'vpn-edit':
        $ui->assign('_title', Lang::T('VPN Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikVpn';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = services_scoped_router_query($admin)->find_many();
            }
            $ui->assign('r', $r);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'VPN')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'VPN')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_vpn'); #HOOK
            $ui->display('admin/vpn/edit.tpl');
        } else {
            r2(getUrl('services/vpn'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'vpn-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_vpn'); #HOOK

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    throw new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/vpn'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;
        
        case 'vpn-bulk-delete':

    $ids = _post('ids');

    if (empty($ids) || !is_array($ids)) {
        r2(getUrl('services/vpn'), 'e', 'No items selected');
    }

    foreach ($ids as $id) {

        $d = ORM::for_table('tbl_plans')->find_one($id);

        if ($d) {

            run_hook('delete_vpn');

            $dvc = Package::getDevice($d);

            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                }
            }

            $d->delete();
        }
    }

    r2(getUrl('services/vpn'), 's', 'Selected plans deleted');

    break;

    case 'vpn-add-post':
        $name = _post('name_plan');
        $plan_type = _post('plan_type');
        $radius = _post('radius');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');


        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_vpn'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);
            $d = ORM::for_table('tbl_plans')->create();
            $d->admin_id = $admin['id'];
            $d->type = 'VPN';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->pool = $pool;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->save();

            $syncError = services_sync_plan_to_device($d['device'], $d, 'add');
            if ($syncError) {
                r2(
                    getUrl('services/vpn'),
                    'w',
                    Lang::T('Data Created Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/vpn'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/vpn-add'), 'e', $msg);
        }
        break;

    case 'edit-vpn-post':
        $id = _post('id');
        $plan_type = _post('plan_type');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $price_old = _post('price_old');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $plan_expired = _post('plan_expired');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if($price_old<=$price){
            $price_old = '';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_vpn'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->price_old = $price_old;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->save();

            $syncError = services_sync_plan_to_device($d['device'], $d, 'update', $old);
            if ($syncError) {
                r2(
                    getUrl('services/vpn-edit/') . $id,
                    'w',
                    Lang::T('Data Updated Successfully')
                    . '. '
                    . Lang::T('MikroTik sync failed — plan saved in database; sync again when the router is reachable')
                    . ': '
                    . $syncError
                );
            }
            r2(getUrl('services/vpn'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/vpn-edit/') . $id, 'e', $msg);
        }
        break;
    default:
        $ui->display('admin/404.tpl');
}
