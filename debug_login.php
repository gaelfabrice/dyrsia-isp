<?php
/**
 * Diagnostic login DYRSIA - À SUPPRIMER APRÈS USAGE
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnostic DYRSIA Login</h2><pre>";

// 1. Config
if (!file_exists('config.php')) {
    die("ERREUR: config.php introuvable");
}
require_once 'config.php';

echo "✓ config.php chargé\n";
echo "DB_HOST: $db_host\n";
echo "DB_NAME: $db_name\n";
echo "DB_USER: $db_user\n\n";

// 2. Connexion DB
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connexion MySQL OK\n\n";
} catch (PDOException $e) {
    die("ERREUR MySQL: " . $e->getMessage());
}

// 3. Table tbl_users
try {
    $stmt = $pdo->query("SELECT id, username, user_type, status, LEFT(password, 20) as pwd_start FROM tbl_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "=== tbl_users ===\n";
    if (count($users) == 0) {
        echo "⚠ TABLE VIDE - Aucun utilisateur!\n";
    } else {
        foreach ($users as $u) {
            echo "id={$u['id']} | user={$u['username']} | type={$u['user_type']} | status={$u['status']} | pwd_start={$u['pwd_start']}...\n";
        }
    }
    echo "\n";
} catch (PDOException $e) {
    echo "ERREUR tbl_users: " . $e->getMessage() . "\n\n";
}

// 4. Colonnes tbl_users
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM tbl_users");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "=== Colonnes tbl_users ===\n";
    echo implode(", ", $cols) . "\n\n";
    
    if (!in_array('login_token', $cols)) {
        echo "⚠ COLONNE login_token MANQUANTE!\n\n";
    }
} catch (PDOException $e) {
    echo "ERREUR colonnes: " . $e->getMessage() . "\n\n";
}

// 5. Table tbl_logs
try {
    $pdo->query("SELECT 1 FROM tbl_logs LIMIT 1");
    echo "✓ tbl_logs existe\n\n";
} catch (PDOException $e) {
    echo "⚠ tbl_logs: " . $e->getMessage() . "\n\n";
}

// 6. runtime_error.log
$logFile = __DIR__ . '/system/uploads/runtime_error.log';
if (file_exists($logFile)) {
    echo "=== Dernières erreurs (runtime_error.log) ===\n";
    $lines = file($logFile);
    $last = array_slice($lines, -30);
    echo htmlspecialchars(implode("", $last));
} else {
    echo "Pas de fichier runtime_error.log\n";
}

// 7. Test mot de passe admin
$adminPwd = 'd033e22ae348aeb5660fc2140aec35850c4da997';
$testPwd = sha1('admin');
echo "\n=== Test password ===\n";
echo "SHA1('admin') = $testPwd\n";
echo "Attendu       = $adminPwd\n";
echo ($testPwd === $adminPwd) ? "✓ Hash OK\n" : "⚠ Hash différent!\n";

echo "</pre>";
echo "<p style='color:red;font-weight:bold'>SUPPRIMER CE FICHIER APRÈS DIAGNOSTIC!</p>";
