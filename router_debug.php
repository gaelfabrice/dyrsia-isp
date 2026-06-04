<?php
/**
 * Router Connection Debug Tool
 * Access this file directly: https://yoursite.com/router_debug.php
 * DELETE THIS FILE AFTER DEBUGGING
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Router Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; }
        .info { background: #cce5ff; color: #004085; padding: 10px; border-radius: 4px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
        input, button { padding: 10px; margin: 5px 0; }
        input { width: 100%; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #0056b3; }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>🔧 Router Connection Debugger</h1>
    
    <div class="card">
        <h2>1. System Check</h2>
        <?php
        echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
        echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
        echo "<p><strong>Current Script:</strong> " . __FILE__ . "</p>";
        
        // Check PEAR2 library
        $pear2Paths = [
            __DIR__ . '/system/autoload/PEAR2/Autoload.php',
            __DIR__ . '/system/autoload/PEAR2/Net/RouterOS/Client.php',
        ];
        
        echo "<h3>PEAR2 Library Check:</h3>";
        $allFound = true;
        foreach ($pear2Paths as $path) {
            $exists = file_exists($path);
            $status = $exists ? '✅' : '❌';
            $class = $exists ? 'success' : 'error';
            echo "<div class='$class'>$status " . basename($path) . " - " . ($exists ? 'Found' : 'MISSING') . "</div>";
            if (!$exists) $allFound = false;
        }
        
        if (!$allFound) {
            echo "<div class='warning' style='margin-top:10px'>";
            echo "<strong>⚠️ PEAR2 library is missing!</strong><br>";
            echo "The MikroTik API library is not installed. You need to redeploy the application with all files.";
            echo "</div>";
        }
        ?>
    </div>
    
    <?php if ($allFound): ?>
    <div class="card">
        <h2>2. Test Router Connection</h2>
        <form method="post">
            <label>Router IP:</label>
            <input type="text" name="ip" value="<?= htmlspecialchars($_POST['ip'] ?? '10.0.0.3') ?>" required>
            
            <label>API Port:</label>
            <input type="number" name="port" value="<?= htmlspecialchars($_POST['port'] ?? '8728') ?>" required>
            
            <label>Username:</label>
            <input type="text" name="user" value="<?= htmlspecialchars($_POST['user'] ?? 'admin') ?>" required>
            
            <label>Password:</label>
            <input type="password" name="pass" value="<?= htmlspecialchars($_POST['pass'] ?? '') ?>">
            
            <button type="submit" name="test">🚀 Test Connection</button>
        </form>
        
        <?php
        if (isset($_POST['test'])) {
            $ip = trim($_POST['ip']);
            $port = (int) $_POST['port'];
            $user = trim($_POST['user']);
            $pass = $_POST['pass'];
            
            echo "<h3>Test Results:</h3>";
            
            // Test 1: Socket
            echo "<p><strong>Socket Test ($ip:$port):</strong></p>";
            $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
            if ($socket) {
                echo "<div class='success'>✅ TCP connection successful</div>";
                fclose($socket);
                
                // Test 2: PEAR2 API
                echo "<p><strong>API Connection Test:</strong></p>";
                try {
                    require_once __DIR__ . '/system/autoload/PEAR2/Autoload.php';
                    \PEAR2\Autoload::initialize(__DIR__ . '/system/autoload');
                    
                    $client = new \PEAR2\Net\RouterOS\Client($ip, $user, $pass, $port, false, 10);
                    
                    // Get identity
                    $request = new \PEAR2\Net\RouterOS\Request('/system/identity/print');
                    $response = $client->sendSync($request);
                    $identity = $response->getProperty('name');
                    
                    echo "<div class='success'>✅ API Connection successful!</div>";
                    echo "<div class='info'><strong>Router Name:</strong> " . htmlspecialchars($identity) . "</div>";
                    
                    // List users
                    $usersRequest = new \PEAR2\Net\RouterOS\Request('/user/print');
                    $usersResponse = $client->sendSync($usersRequest);
                    echo "<p><strong>Users on MikroTik:</strong></p><pre>";
                    foreach ($usersResponse as $u) {
                        echo "- " . $u->getProperty('name') . " (group: " . $u->getProperty('group') . ")\n";
                    }
                    echo "</pre>";
                    
                } catch (\PEAR2\Net\RouterOS\SocketException $e) {
                    echo "<div class='error'>❌ Socket Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                } catch (\PEAR2\Net\RouterOS\DataFlowException $e) {
                    if ($e->getCode() === \PEAR2\Net\RouterOS\DataFlowException::CODE_INVALID_CREDENTIALS) {
                        echo "<div class='error'>❌ Invalid username or password!</div>";
                    } else {
                        echo "<div class='error'>❌ DataFlow Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                }
                
            } else {
                echo "<div class='error'>❌ Cannot connect to $ip:$port - $errstr (Error $errno)</div>";
                echo "<div class='warning'>";
                echo "<strong>Possible causes:</strong><br>";
                echo "- MikroTik API service not enabled<br>";
                echo "- Firewall blocking port $port<br>";
                echo "- WireGuard tunnel not connected<br>";
                echo "- Wrong IP address";
                echo "</div>";
            }
        }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>3. Directory Structure</h2>
        <pre><?php
        $dirs = [
            'system/autoload/PEAR2',
            'system/autoload/PEAR2/Net',
            'system/autoload/PEAR2/Net/RouterOS',
            'system/devices',
        ];
        foreach ($dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $exists = is_dir($path);
            echo ($exists ? '✅' : '❌') . " $dir\n";
            if ($exists && $dir === 'system/autoload/PEAR2/Net/RouterOS') {
                $files = glob($path . '/*.php');
                foreach ($files as $f) {
                    echo "   └─ " . basename($f) . "\n";
                }
            }
        }
        ?></pre>
    </div>
    
    <div class="warning" style="margin-top: 20px;">
        <strong>⚠️ Security:</strong> Delete this file (router_debug.php) after debugging!
    </div>
</body>
</html>
