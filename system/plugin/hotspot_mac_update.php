<?php
// ==================== MENU REGISTER =====================
register_menu(
    "Hotspot MAC Update",
    true,
    "hotspot_mac_update",
    'AFTER_MESSAGE',
    'glyphicon glyphicon-refresh',
    '',
    'info',
    ['SuperAdmin']
);

function hotspot_mac_update() {
    global $db, $ui;

    $admin = Admin::_info();
    if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => Lang::T('You do not have permission to access this page'),
            ]);
            exit;
        }
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }
    
    try {
        $db = ORM::get_db();

        foreach (['mac_used' => 'INT NOT NULL DEFAULT 0', 'mac_limit' => 'INT NOT NULL DEFAULT 5'] as $column => $definition) {
            $columns = $db->query("SHOW COLUMNS FROM tbl_customers LIKE '" . $column . "'")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($columns)) {
                $db->exec("ALTER TABLE tbl_customers ADD COLUMN " . $column . " " . $definition);
            }
        }

        // MAC HISTORY TABLE (VERY IMPORTANT)
        $db->exec("
            CREATE TABLE IF NOT EXISTS tbl_mac_update_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                old_mac VARCHAR(50) DEFAULT NULL,
                new_mac VARCHAR(50) DEFAULT NULL,
                updated_by VARCHAR(100) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        error_log("MAC update tables checked/created");

    } catch (Exception $e) {
        error_log("MAC DB Error: " . $e->getMessage());
    }

    // =========================
    // GET request: Show TPL + history
    // =========================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        // =========================
        // Load history
        // =========================
        $mac_history = $db->query("
            SELECT h.id, h.phone, h.old_mac, h.new_mac, h.updated_by, h.updated_at,
                   c.username AS customer_name,
                   c.mac_used, c.mac_limit
            FROM tbl_mac_update_history h
            LEFT JOIN tbl_customers c ON h.customer_id = c.id
            ORDER BY h.updated_at DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        // =========================
        // IMPORTANT: MENU FIX
        // =========================
        $ui->assign('_admin', $admin);
        $ui->assign('_system_menu', 'hotspot_mac_update');
        $ui->assign('_title', 'Hotspot MAC Management');

        // =========================
        // USER LIMIT DATA (global stats)
        // =========================
        $statsRow = $db->query("
            SELECT COALESCE(SUM(mac_used), 0) AS mac_used, COALESCE(SUM(mac_limit), 0) AS mac_limit
            FROM tbl_customers
        ")->fetch(PDO::FETCH_ASSOC);

        $userData = [
            'mac_used' => (int) ($statsRow['mac_used'] ?? 0),
            'mac_limit' => (int) ($statsRow['mac_limit'] ?? 0),
        ];

        $ui->assign('user', $userData);
        $ui->assign('mac_history', $mac_history);

        $ui->display('[plugin]hotspot_mac_update.tpl');
        return;
    }

    // =========================
    // POST request: MAC Update API
    // =========================
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';
    
    // --- Action: Increase Limit ---
    if ($action === 'limit') {
        $phone = $_POST['phone'] ?? '';
        $add   = (int)($_POST['add_limit'] ?? 0);

        if (!$phone || $add <= 0) {
            echo json_encode(["status"=>"error","message"=>"Invalid input"]);
            exit;
        }

        $phone = preg_replace('/[^0-9]/','',$phone);
        if (substr($phone,0,2) === "01") $phone = "88".$phone;

        $db->prepare("UPDATE tbl_customers SET mac_limit = mac_limit + ? WHERE phonenumber = ?")
           ->execute([$add, $phone]);
           
           $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'MAC Limit Increase',
    "Limit +{$add} for {$phone}",
    $_SESSION['uid'] ?? 0,
    $ip
]);

        echo json_encode(["status"=>"success","message"=>"Limit increased successfully"]);
        exit;
    }

    // --- Action: Decrease Limit ---
    if ($action === 'decrease_limit') {
        $phone = $_POST['phone'] ?? '';
        $subtract = (int)($_POST['subtract_limit'] ?? 0);

        if (!$phone || $subtract <= 0) {
            echo json_encode(["status" => "error", "message" => "Invalid input"]);
            exit;
        }

        $phone = preg_replace('/[^0-9]/','',$phone);
        if (substr($phone,0,2) === "01") $phone = "88".$phone;

        $stmt = $db->prepare("SELECT mac_used, mac_limit FROM tbl_customers WHERE phonenumber = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["status"=>"error","message"=>"User not found"]);
            exit;
        }
        
        $newLimit = max($user['mac_used'], $user['mac_limit'] - $subtract);
        $db->prepare("UPDATE tbl_customers SET mac_limit=? WHERE phonenumber=?")->execute([$newLimit, $phone]);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'MAC Limit Decrease',
    "Limit -{$subtract} for {$phone} (New: {$newLimit})",
    $_SESSION['uid'] ?? 0,
    $ip
]);

        echo json_encode(["status"=>"success","message"=>"Limit decreased successfully"]);
        exit;
    }

    // --- Action: Reset MAC Count ---
    if ($action === 'reset_mac') {
        $phone = $_POST['phone'] ?? '';
        if (!$phone) {
            echo json_encode(["status"=>"error","message"=>"Phone number is required"]);
            exit;
        }

        $phone = preg_replace('/[^0-9]/','',$phone);
        if (substr($phone,0,2) === "01") $phone = "88".$phone;

        $stmt = $db->prepare("SELECT id FROM tbl_customers WHERE phonenumber=?");
        $stmt->execute([$phone]);
        if (!$stmt->fetch()) {
            echo json_encode(["status"=>"error","message"=>"User not found"]);
            exit;
        }

        $defaultLimit = 5;
        $db->prepare("UPDATE tbl_customers SET mac_used = 0, mac_limit = ? WHERE phonenumber=?")
           ->execute([$defaultLimit, $phone]);
           
           $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'MAC Reset',
    "MAC reset for {$phone} (Limit: {$defaultLimit})",
    $_SESSION['uid'] ?? 0,
    $ip
]);

        echo json_encode(["status"=>"success","message"=>"MAC count reset successful", "mac_limit" => $defaultLimit]);
        exit;
    }

    // --- Action: Reset All Users ---
    if ($action === 'reset_all') {
        $defaultLimit = 5;
        $db->prepare("UPDATE tbl_customers SET mac_used = 0, mac_limit = ?")->execute([$defaultLimit]);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'MAC Reset All',
    "All users MAC reset (Limit: {$defaultLimit})",
    $_SESSION['uid'] ?? 0,
    $ip
]);

        echo json_encode(["status"=>"success","message"=>"All users MAC count reset successful"]);
        exit;
    }

    // --- Action: Delete History ---
    if ($action === 'delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM tbl_mac_update_history WHERE id IN ($in)")->execute($ids);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$count = count($ids);

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'MAC History Delete',
    "Deleted {$count} MAC history records",
    $_SESSION['uid'] ?? 0,
    $ip
]);
        
        echo json_encode(["status"=>"success","message"=>"History deleted successfully"]);
        exit;
    }

    // ==========================================
    // MAIN MAC UPDATE LOGIC
    // ==========================================
    $phone = $_POST['phone'] ?? '';
    $mac   = $_POST['mac'] ?? '';

    if (!$phone || !$mac) {
        echo json_encode(["status"=>"error","message"=>"Missing phone or MAC data"]);
        exit;
    }

    if (substr($phone,0,2) === "01") $phone = "88".$phone;

    $macClone = strtoupper($mac);
    if(strlen($macClone)==12 && strpos($macClone, ':')===false){
        $macClone = implode(':', str_split($macClone,2));
    }

    try {
        $stmt = $db->prepare("SELECT * FROM tbl_customers WHERE phonenumber = :phone");
        $stmt->execute(['phone'=>$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) throw new Exception("User not found in database");

        $userId      = $user['id'];
        $oldUsername = $user['username'];
        
        // MAC LIMIT CHECK
        if ($user['mac_used'] >= $user['mac_limit']) {
            throw new Exception("Update limit reached. Please contact support to increase your device update limit. Thank you.");
        }

        // DUPLICATE CHECK
        if ($oldUsername === $macClone) {
            echo json_encode([
                "status"=>"success",
                "message"=>"MAC is already updated. No further action needed.",
                "mikrotik_log"=>[]
            ]);
            exit;
        }

        // GET ACTIVE PLAN
        $activePlans = $db->prepare("
    SELECT ur.*, 
           p.device, 
           p.name_plan,
           ur.method,
           ur.recharged_time,
           ur.time AS exp_time
    FROM tbl_user_recharges ur
    LEFT JOIN tbl_plans p ON ur.plan_id = p.id
    WHERE ur.customer_id=:uid 
      AND ur.status='on'
    LIMIT 1
");
        $activePlans->execute(['uid'=>$userId]);
        $plan = $activePlans->fetch(PDO::FETCH_ASSOC);

        if (!$plan) throw new Exception("No active plan found for this user");

        $planProfile = trim($plan['name_plan']);
        // ================= FINAL COMMENT BUILD =================
$fullname = $user['fullname'] ?? '';
$phone    = $user['phonenumber'] ?? '';
$address  = $user['address'] ?? '';

// SAFE TIME BUILD
$created_raw = ($plan['recharged_on'] ?? date('Y-m-d')) . ' ' . ($plan['recharged_time'] ?? '00:00:00');
$expired_raw = ($plan['expiration'] ?? date('Y-m-d')) . ' ' . ($plan['time'] ?? '23:59:59');

// FORMAT 24H
$created_fmt = date('d-M-Y H:i:s', strtotime($created_raw));
$expired_fmt = date('d-M-Y H:i:s', strtotime($expired_raw));
// METHOD SAFE
$method = $plan['method'] ?? 'N/A';

// COMMENT
$comment = "Name: {$fullname} | Phone: {$phone} | Address: {$address} | Created: {$created_fmt} | Expired: {$expired_fmt} | Method: {$method}";
        if(empty($planProfile)) throw new Exception("Plan profile is missing");

        $mikrotik_log = [];
        $db->beginTransaction();

        // ---- DB UPDATE ----
        $db->prepare("UPDATE tbl_customers SET username=?, password=? WHERE id=?")
            ->execute([$macClone, $macClone, $userId]);

        $db->prepare("UPDATE tbl_user_recharges SET username=? WHERE customer_id=?")
            ->execute([$macClone, $userId]);

        $db->prepare("UPDATE tbl_user_recharges SET username=? WHERE username=?")
            ->execute([$macClone, $oldUsername]);

        // ---- SAVE HISTORY ----
        $db->prepare("
            INSERT INTO tbl_mac_update_history 
            (customer_id, phone, old_mac, new_mac, updated_by, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([
            $userId,
            $phone,
            $oldUsername,
            $macClone,
            $_SESSION['username'] ?? 'Admin'
        ]);
        
        // ---- INCREASE MAC USED COUNT ----
        $db->prepare("UPDATE tbl_customers SET mac_used = mac_used + 1 WHERE id=?")->execute([$userId]);

        // ---- DEVICE SYNC (SAFE - NO DELETE, NO DUPLICATE) ----
if (!empty($plan['device'])) {
    $deviceFile = __DIR__."/../devices/{$plan['device']}.php";

    if(file_exists($deviceFile)){
        require_once $deviceFile;
        $deviceClass = $plan['device'];

        if(class_exists($deviceClass)){
            $obj = new $deviceClass();

            // 🔥 BUILD SYNC USER
            $sync_user = $user;
            $sync_user['username'] = $macClone;
            $sync_user['password'] = $macClone;
            $sync_user['comment']  = $comment;

            // ✅ ONLY DISCONNECT SESSION (NO DELETE)
            if(method_exists($obj,'disconnect_user')){
                $obj->disconnect_user(['username'=>$oldUsername], $plan);
                $mikrotik_log[] = "Session disconnected only";
            }

            // ✅ ONLY UPDATE USER (NO CREATE)
            if(method_exists($obj,'sync_customer')){
                $obj->sync_customer($sync_user, $plan);
                $mikrotik_log[] = "User updated only (no duplicate)";
            } else {
                $mikrotik_log[] = "Error: sync_customer not found";
            }

        } else {
            $mikrotik_log[] = "Error: Device class not found";
        }
    } else {
        $mikrotik_log[] = "Error: Device driver file not found";
    }
}
        
        // ================= LOG INSERT =================
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db->prepare("
    INSERT INTO tbl_logs (date, type, description, userid, ip)
    VALUES (NOW(), ?, ?, ?, ?)
")->execute([
    'Hotspot MAC Update',
    "MAC Updated: {$oldUsername} → {$macClone} (Phone: {$phone})",
    $_SESSION['uid'] ?? 0,
    $ip
]);

        $db->commit();

        echo json_encode([
            "status"=>"success",
            "message"=>"Success: Mac Update Successfully",
            "profile"=>$planProfile,
            "mikrotik_log"=>$mikrotik_log
        ]);

    } catch(Exception $e){
        if($db->inTransaction()) $db->rollBack();
        echo json_encode([
            "status"=>"error",
            "message"=>$e->getMessage()
        ]);
    }
    exit;
}
?>