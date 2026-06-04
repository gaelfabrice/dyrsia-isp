<?php
/**
 * MikroTik Connection Diagnostic Tool
 * Upload this file to your server and access it via browser
 * DELETE THIS FILE AFTER USE FOR SECURITY
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration - MODIFY THESE VALUES
$MIKROTIK_IP = '10.0.0.3';
$MIKROTIK_PORT = 8728;
$MIKROTIK_USER = 'admin';  // Change to your API user
$MIKROTIK_PASS = '';       // Change to your API password

?>
<!DOCTYPE html>
<html>
<head>
    <title>MikroTik Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .test { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #cce5ff; border: 1px solid #b8daff; color: #004085; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
        form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        input { padding: 8px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔧 MikroTik Connection Diagnostic</h1>
    
    <form method="post">
        <h3>Connection Parameters</h3>
        <label>IP Address:</label>
        <input type="text" name="ip" value="<?= htmlspecialchars($_POST['ip'] ?? $MIKROTIK_IP) ?>" required>
        
        <label>API Port:</label>
        <input type="number" name="port" value="<?= htmlspecialchars($_POST['port'] ?? $MIKROTIK_PORT) ?>" required>
        
        <label>Username:</label>
        <input type="text" name="user" value="<?= htmlspecialchars($_POST['user'] ?? $MIKROTIK_USER) ?>" required>
        
        <label>Password:</label>
        <input type="password" name="pass" value="<?= htmlspecialchars($_POST['pass'] ?? $MIKROTIK_PASS) ?>">
        
        <br><br>
        <button type="submit">🚀 Run Diagnostic</button>
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = trim($_POST['ip']);
    $port = (int) $_POST['port'];
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];
    
    echo "<h2>Diagnostic Results</h2>";
    
    // Test 1: Network connectivity
    echo "<div class='test info'><strong>Test 1: Network Ping</strong></div>";
    $pingResult = shell_exec("ping -c 2 -W 2 $ip 2>&1");
    if (strpos($pingResult, 'bytes from') !== false || strpos($pingResult, '64 bytes') !== false) {
        echo "<div class='test success'>✅ Ping OK - Host is reachable</div>";
    } else {
        echo "<div class='test error'>❌ Ping FAILED - Host may be unreachable or ICMP blocked</div>";
    }
    echo "<pre>" . htmlspecialchars($pingResult) . "</pre>";
    
    // Test 2: TCP Socket
    echo "<div class='test info'><strong>Test 2: TCP Socket Connection (Port $port)</strong></div>";
    $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
    if ($socket) {
        echo "<div class='test success'>✅ Socket OK - Port $port is open and accepting connections</div>";
        fclose($socket);
    } else {
        echo "<div class='test error'>❌ Socket FAILED - $errstr (Error $errno)</div>";
        echo "<div class='test info'>Check: MikroTik API service enabled? Firewall rules? WireGuard tunnel up?</div>";
    }
    
    // Test 3: PEAR2 RouterOS API
    echo "<div class='test info'><strong>Test 3: RouterOS API Connection</strong></div>";
    
    $autoloadPath = __DIR__ . '/system/autoload/PEAR2/Net/RouterOS/Autoload.php';
    if (!file_exists($autoloadPath)) {
        echo "<div class='test error'>❌ PEAR2 library not found at: $autoloadPath</div>";
    } else {
        require_once $autoloadPath;
        
        try {
            $client = new \PEAR2\Net\RouterOS\Client($ip, $user, $pass, $port, false, 10);
            
            // Get router identity
            $request = new \PEAR2\Net\RouterOS\Request('/system/identity/print');
            $response = $client->sendSync($request);
            $identity = $response->getProperty('name');
            
            // Get router version
            $request2 = new \PEAR2\Net\RouterOS\Request('/system/resource/print');
            $response2 = $client->sendSync($request2);
            $version = $response2->getProperty('version');
            $board = $response2->getProperty('board-name');
            
            echo "<div class='test success'>✅ API Connection SUCCESSFUL!</div>";
            echo "<div class='test success'>";
            echo "<strong>Router Identity:</strong> " . htmlspecialchars($identity) . "<br>";
            echo "<strong>RouterOS Version:</strong> " . htmlspecialchars($version) . "<br>";
            echo "<strong>Board:</strong> " . htmlspecialchars($board) . "<br>";
            echo "</div>";
            
            // Test 4: Check API users
            echo "<div class='test info'><strong>Test 4: API Users on MikroTik</strong></div>";
            $usersRequest = new \PEAR2\Net\RouterOS\Request('/user/print');
            $usersResponse = $client->sendSync($usersRequest);
            
            echo "<div class='test success'>Users configured on MikroTik:</div>";
            echo "<pre>";
            foreach ($usersResponse as $userEntry) {
                $uname = $userEntry->getProperty('name');
                $ugroup = $userEntry->getProperty('group');
                echo "- $uname (group: $ugroup)\n";
            }
            echo "</pre>";
            
        } catch (\PEAR2\Net\RouterOS\DataFlowException $e) {
            if ($e->getCode() === \PEAR2\Net\RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
                echo "<div class='test error'>❌ Invalid username or password!</div>";
                echo "<div class='test info'>The user '$user' either doesn't exist or has wrong password.</div>";
            } else {
                echo "<div class='test error'>❌ DataFlow Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } catch (\PEAR2\Net\RouterOS\SocketException $e) {
            echo "<div class='test error'>❌ Socket Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='test info'>This usually means the API service is not responding correctly.</div>";
        } catch (Exception $e) {
            echo "<div class='test error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    
    // Server info
    echo "<div class='test info'><strong>Server Information</strong></div>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "\n";
    echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
    echo "</pre>";
}
?>

<div class="test info">
    <strong>⚠️ Security Warning:</strong> Delete this file after use!
</div>
</body>
</html>
