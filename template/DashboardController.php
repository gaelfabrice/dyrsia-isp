<?php
// system/controllers/DashboardController.php
// À adapter selon votre structure

class DashboardController extends Controller
{
    public function actionIndex()
    {
        // ==================== VARIABLES DE BASE ====================
        $smarty = $this->smarty;
        
        // Profil utilisateur
        $smarty->assign('_profile', $_SESSION['profile']);
        $smarty->assign('_route', 'dashboard');
        $smarty->assign('_subroute', '');
        $smarty->assign('app_name', $this->config['app_name']);
        $smarty->assign('app_version', $this->config['version']);
        $smarty->assign('currency', $this->config['currency']);
        
        // ==================== KPI CARDS ====================
        // Total administrateurs
        $total_admin = DB::queryFirstField("SELECT COUNT(*) FROM rm_users WHERE user_type IN ('SuperAdmin', 'Admin')");
        $smarty->assign('total_admin', $total_admin);
        
        // Clients actifs (Hotspot + PPPoE)
        $active_customers = DB::queryFirstField("
            SELECT COUNT(DISTINCT username) FROM radacct 
            WHERE acctstoptime IS NULL OR acctstoptime = '0000-00-00 00:00:00'
        ");
        $smarty->assign('active_customers', $active_customers);
        
        // Ventes du jour
        $sales_today = DB::queryFirstField("
            SELECT SUM(amount) FROM rm_invoices 
            WHERE DATE(date) = CURDATE() AND status = 'paid'
        ");
        $smarty->assign('sales_today', $sales_today ?: 0);
        
        // Routeurs offline (vérification via MikroTik API)
        $offline_routers = $this->getOfflineRoutersCount();
        $smarty->assign('offline_routers', $offline_routers);
        
        // ==================== SERVICES STATUS ====================
        // Hotspot actifs/expirés
        $hotspot_active = DB::queryFirstField("
            SELECT COUNT(*) FROM rm_users 
            WHERE service_type = 'hotspot' AND enabled = 1 AND (expiration > NOW() OR expiration IS NULL)
        ");
        $hotspot_expired = DB::queryFirstField("
            SELECT COUNT(*) FROM rm_users 
            WHERE service_type = 'hotspot' AND enabled = 1 AND expiration < NOW() AND expiration IS NOT NULL
        ");
        $smarty->assign('hotspot_active', $hotspot_active ?: 0);
        $smarty->assign('hotspot_expired', $hotspot_expired ?: 0);
        
        // PPPoE actifs/expirés
        $pppoe_active = DB::queryFirstField("
            SELECT COUNT(*) FROM rm_users 
            WHERE service_type = 'pppoe' AND enabled = 1 AND (expiration > NOW() OR expiration IS NULL)
        ");
        $pppoe_expired = DB::queryFirstField("
            SELECT COUNT(*) FROM rm_users 
            WHERE service_type = 'pppoe' AND enabled = 1 AND expiration < NOW() AND expiration IS NOT NULL
        ");
        $smarty->assign('pppoe_active', $pppoe_active ?: 0);
        $smarty->assign('pppoe_expired', $pppoe_expired ?: 0);
        
        $smarty->assign('total_active', $hotspot_active + $pppoe_active);
        $smarty->assign('total_expired', $hotspot_expired + $pppoe_expired);
        
        // ==================== VOUCHERS STOCK ====================
        $voucher_stock = DB::query("
            SELECT package, 
                   SUM(CASE WHEN status = 'unused' THEN 1 ELSE 0 END) as unused,
                   SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used
            FROM rm_vouchers
            GROUP BY package
        ");
        $smarty->assign('voucher_stock', $voucher_stock);
        
        $total_unused = DB::queryFirstField("SELECT COUNT(*) FROM rm_vouchers WHERE status = 'unused'");
        $total_used = DB::queryFirstField("SELECT COUNT(*) FROM rm_vouchers WHERE status = 'used'");
        $smarty->assign('total_unused', $total_unused ?: 0);
        $smarty->assign('total_used', $total_used ?: 0);
        
        // ==================== GRAPHIQUES ====================
        // Traffic des 7 derniers jours
        $traffic_data = DB::query("
            SELECT 
                DATE(acctstarttime) as date,
                SUM(acctinputoctets)/1048576 as download_mb,
                SUM(acctoutputoctets)/1048576 as upload_mb
            FROM radacct
            WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(acctstarttime)
            ORDER BY date
        ");
        
        $labels = [];
        $download = [];
        $upload = [];
        foreach ($traffic_data as $day) {
            $labels[] = date('D', strtotime($day['date']));
            $download[] = round($day['download_mb'], 0);
            $upload[] = round($day['upload_mb'], 0);
        }
        $smarty->assign('traffic_labels', json_encode($labels));
        $smarty->assign('traffic_download', json_encode($download));
        $smarty->assign('traffic_upload', json_encode($upload));
        
        // ==================== ACTIVITY LOG ====================
        $logs = DB::query("
            SELECT 
                log_id, username, action, message, sid, 
                TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago,
                created_at
            FROM rm_activity_log
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        $activity_logs = [];
        foreach ($logs as $log) {
            $minutes = $log['minutes_ago'];
            if ($minutes < 60) {
                $time_ago = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($minutes < 1440) {
                $hours = floor($minutes / 60);
                $time_ago = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } else {
                $days = floor($minutes / 1440);
                $time_ago = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            }
            
            // Déterminer le type
            $type = 'info';
            if (strpos($log['action'], 'login') !== false) $type = 'login';
            if (strpos($log['action'], 'logout') !== false) $type = 'logout';
            if (strpos($log['message'], 'failed') !== false || strpos($log['message'], 'error') !== false) $type = 'error';
            
            $activity_logs[] = [
                'time_ago' => $time_ago,
                'type' => $type,
                'username' => $log['username'],
                'message' => $log['message'] ?: $log['action'],
                'sid' => $log['sid']
            ];
        }
        $smarty->assign('activity_logs', $activity_logs);
        
        // ==================== PAGINATION ====================
        $total_entries = DB::queryFirstField("SELECT COUNT(*) FROM rm_activity_log");
        $current_page = (int)($_GET['page'] ?? 1);
        $per_page = 10;
        $total_pages = ceil($total_entries / $per_page);
        
        $smarty->assign('total_entries', $total_entries);
        $smarty->assign('current_page', $current_page);
        $smarty->assign('total_pages', $total_pages);
        
        $pagination_pages = [];
        for ($i = 1; $i <= min(5, $total_pages); $i++) {
            $pagination_pages[] = ['num' => $i, 'active' => ($i == $current_page)];
        }
        if ($total_pages > 5) {
            $pagination_pages[] = ['num' => '...', 'active' => false];
            $pagination_pages[] = ['num' => $total_pages, 'active' => false];
        }
        $smarty->assign('pagination_pages', $pagination_pages);
        
        // ==================== SLOTS PLUGINS ====================
        $smarty->assign('menu_reports', $this->runHook('menu_reports'));
        $smarty->assign('menu_customers', $this->runHook('menu_customers'));
        $smarty->assign('menu_services', $this->runHook('menu_services'));
        $smarty->assign('menu_plans', $this->runHook('menu_plans'));
        $smarty->assign('menu_after_plans', $this->runHook('menu_after_plans'));
        $smarty->assign('menu_maps', $this->runHook('menu_maps'));
        $smarty->assign('menu_message', $this->runHook('menu_message'));
        $smarty->assign('menu_network', $this->runHook('menu_network'));
        $smarty->assign('menu_after_networks', $this->runHook('menu_after_networks'));
        $smarty->assign('menu_radius', $this->runHook('menu_radius'));
        $smarty->assign('menu_after_radius', $this->runHook('menu_after_radius'));
        $smarty->assign('menu_notification', $this->runHook('menu_notification'));
        $smarty->assign('menu_settings', $this->runHook('menu_settings'));
        $smarty->assign('menu_after_settings', $this->runHook('menu_after_settings'));
        
        // ==================== CONFIGURATION ====================
        $smarty->assign('vouchers_enabled', $this->config['vouchers_enabled'] ?? true);
        $smarty->assign('enable_coupons', $this->config['enable_coupons'] ?? false);
        $smarty->assign('enable_balance', $this->config['enable_balance'] ?? true);
        $smarty->assign('radius_enable', $this->config['radius_enable'] ?? false);
        
        // ==================== RÉSEAU ====================
        $smarty->assign('lan_interface', $this->config['lan_interface'] ?? 'ether1 - 192.168.1.1/24');
        $smarty->assign('wan_interface', $this->config['wan_interface'] ?? 'ether2 - DHCP Client');
        $smarty->assign('dns_servers', $this->config['dns_servers'] ?? '8.8.8.8 / 1.1.1.1');
        $smarty->assign('network_usage', $this->getNetworkUsage());
        
        // ==================== RESELLER ====================
        $smarty->assign('reseller_plan', $_SESSION['reseller_plan'] ?? 'Standard');
        $smarty->assign('commission_rate', $this->config['commission_rate'] ?? 15);
        $smarty->assign('active_resellers', DB::queryFirstField("SELECT COUNT(*) FROM rm_users WHERE user_type = 'Reseller' AND enabled = 1"));
        $smarty->assign('total_commission', DB::queryFirstField("SELECT SUM(commission) FROM rm_commission WHERE MONTH(date) = MONTH(NOW())"));
        
        // ==================== DATE RANGE ====================
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $smarty->assign('start_date', strtotime($start_date));
        $smarty->assign('end_date', strtotime($end_date));
        
        // ==================== CRON CHECK ====================
        $last_cron = DB::queryFirstField("SELECT value FROM rm_settings WHERE setting = 'cron_last_run'");
        $cron_last_run = $last_cron ? (time() - strtotime($last_cron)) : 7200;
        $smarty->assign('cron_last_run', $cron_last_run);
        
        // ==================== RENDU FINAL ====================
        $this->setView('dashboard_modern.tpl');
        $this->render();
    }
    
    private function getOfflineRoutersCount()
    {
        $routers = DB::query("SELECT id, name, ip_address, api_port, api_username, api_password FROM nas");
        $offline = 0;
        
        foreach ($routers as $router) {
            if (!$this->checkMikrotikConnection($router)) {
                $offline++;
            }
        }
        return $offline;
    }
    
    private function checkMikrotikConnection($router)
    {
        // Test de connexion via socket ou API
        $fp = @fsockopen($router['ip_address'], $router['api_port'] ?: 8728, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }
    
    private function getNetworkUsage()
    {
        // Calcul basé sur le trafic total / capacité max
        $total_traffic = DB::queryFirstField("
            SELECT SUM(acctinputoctets + acctoutputoctets) / 1073741824 as total_gb
            FROM radacct
            WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $max_capacity = 10000; // 10 TB par mois
        $usage = ($total_traffic / $max_capacity) * 100;
        return min(100, round($usage, 0));
    }
}
?>