<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

//error_reporting (0);
$appurl = $_POST['appurl'];
$db_host = $_POST['dbhost'];
$db_user = $_POST['dbuser'];
$db_pass = $_POST['dbpass'];
$db_name = $_POST['dbname'];
$cn = '0';
$configError = false;
$configContent = '';

try {
    $dbh = new pdo(
        "mysql:host=$db_host;dbname=$db_name",
        "$db_user",
        "$db_pass",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    $cn = '1';
} catch (PDOException $ex) {
    $cn = '0';
}

if ($cn == '1') {
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $configContent = '<?php

$protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || $_SERVER["SERVER_PORT"] == 443) ? "https://" : "http://";
$host = $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

// Live, Dev, Demo
$_app_stage = "Live";

// Database PHPNuxBill
$db_host	    = "' . $db_host . '";
$db_user        = "' . $db_user . '";
$db_pass    	= "' . $db_pass . '";
$db_name	    = "' . $db_name . '";

// Database Radius
$radius_host	    = "' . $db_host . '";
$radius_user        = "' . $db_user . '";
$radius_pass    	= "' . $db_pass . '";
$radius_name	    = "' . $db_name . '";

if($_app_stage!="Live"){
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
}else{
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}';
    } else {
        $configContent = '<?php
$protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || $_SERVER["SERVER_PORT"] == 443) ? "https://" : "http://";
$host = $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

// Live, Dev, Demo
$_app_stage = "Live";

// Database PHPNuxBill
$db_host	    = "' . $db_host . '";
$db_user        = "' . $db_user . '";
$db_pass	    = "' . $db_pass . '";
$db_name	    = "' . $db_name . '";

if($_app_stage!="Live"){
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
}else{
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}';
    }
    
    $wConfig = dirname(__DIR__) . '/config.php';
    $wConfigDist = dirname(__DIR__) . '/config.php.dist';
    $wConfigSample = dirname(__DIR__) . '/config.sample.php';
    
    // Try multiple methods to create the config file
    $configWritten = false;
    
    // Method 0: If config.php.dist exists, copy it first (helps with permissions)
    if (!$configWritten && !file_exists($wConfig)) {
        if (file_exists($wConfigDist)) {
            @copy($wConfigDist, $wConfig);
            @chmod($wConfig, 0666);
        } elseif (file_exists($wConfigSample)) {
            @copy($wConfigSample, $wConfig);
            @chmod($wConfig, 0666);
        }
    }
    
    // Method 1: Standard fopen
    if (!$configWritten) {
        $fh = @fopen($wConfig, 'w');
        if ($fh) {
            fwrite($fh, $configContent);
            fclose($fh);
            $configWritten = true;
        }
    }
    
    // Method 2: file_put_contents
    if (!$configWritten) {
        if (@file_put_contents($wConfig, $configContent) !== false) {
            $configWritten = true;
        }
    }
    
    // Method 3: Try creating with different permissions
    if (!$configWritten) {
        @touch($wConfig);
        @chmod($wConfig, 0666);
        if (@file_put_contents($wConfig, $configContent) !== false) {
            $configWritten = true;
        }
    }
    
    // Method 4: Try using shell command
    if (!$configWritten) {
        $escaped = escapeshellarg($configContent);
        @exec("echo $escaped > " . escapeshellarg($wConfig) . " 2>&1", $output, $returnCode);
        if ($returnCode === 0 && file_exists($wConfig)) {
            $configWritten = true;
        }
    }
    
    if (!$configWritten) {
        $configError = true;
    }
    
    // Import database regardless of config file status
    $sql = file_get_contents('phpnuxbill.sql');
    $qr = $dbh->exec($sql);
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $sql = file_get_contents('radius.sql');
        $qrs = $dbh->exec($sql);
    }
} else {
    header("location: step3.php?_error=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Wifizone Installer</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet' />
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .config-box { background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .config-code { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto; }
        .alert-warning { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .alert-success { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .btn-copy { background: #5bc0de; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-bottom: 10px; }
        .btn-copy:hover { background: #46b8da; }
        .step-info { background: #e7f3fe; border-left: 4px solid #2196F3; padding: 12px; margin: 10px 0; }
    </style>
</head>

<body style='background-color: #FBFBFB;'>
    <div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>

        <div class="span12">
            <h4>Wifizone Installer</h4>
            <?php
            if ($cn == '1' && !$configError) {
            ?>
                <div class="alert-success">
                    <strong>✅ Config File Created and Database Imported Successfully!</strong>
                </div>
                <form action="step5.php" method="post">
                    <fieldset>
                        <legend>Click Continue</legend>
                        <button type='submit' class='btn btn-primary'>Continue</button>
                    </fieldset>
                </form>
            <?php
            } elseif ($cn == '1' && $configError) {
            ?>
                <div class="alert-warning">
                    <strong>⚠️ Database imported successfully, but config.php could not be created automatically.</strong>
                    <p>This is usually due to file permissions. Please create the file manually.</p>
                </div>
                
                <div class="config-box">
                    <h5>📄 Create the file <code>config.php</code> in the root directory with this content:</h5>
                    
                    <div class="step-info">
                        <strong>Option 1 - Via SSH:</strong><br>
                        <code>nano <?php echo dirname(__DIR__); ?>/config.php</code><br>
                        Then paste the content below and save (Ctrl+X, Y, Enter)
                    </div>
                    
                    <div class="step-info">
                        <strong>Option 2 - Via File Manager (aaPanel):</strong><br>
                        Create a new file named <code>config.php</code> in <code><?php echo dirname(__DIR__); ?></code>
                    </div>
                    
                    <button class="btn-copy" onclick="copyConfig()">📋 Copy Content</button>
                    <div class="config-code" id="configContent"><?php echo htmlspecialchars($configContent); ?></div>
                </div>
                
                <form action="step5.php" method="post" style="margin-top: 20px;">
                    <fieldset>
                        <legend>After creating config.php manually, click Continue</legend>
                        <button type='submit' class='btn btn-primary btn-lg'>Continue Installation →</button>
                    </fieldset>
                </form>
                
                <script>
                function copyConfig() {
                    var content = document.getElementById('configContent').innerText;
                    navigator.clipboard.writeText(content).then(function() {
                        alert('Config content copied to clipboard!');
                    }).catch(function() {
                        // Fallback for older browsers
                        var textarea = document.createElement('textarea');
                        textarea.value = content;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        alert('Config content copied to clipboard!');
                    });
                }
                </script>
            <?php
            } elseif ($cn == '2') {
            ?>
                <div class="alert-warning">
                    <p><strong>MySQL Connection was successful.</strong> An error occurred while importing the database.</p>
                    <p>Please refer to manual installation in the website github.com/ibnux/phpnuxbill/wiki or Contact Telegram @ibnux for help.</p>
                </div>
            <?php
            } else {
            ?>
                <div class="alert-warning">
                    <p><strong>❌ MySQL Connection Failed.</strong></p>
                    <p>Please go back and check your database credentials.</p>
                    <a href="step3.php" class="btn btn-default">← Back to Step 3</a>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <div class="footer">Copyright &copy; 2026 Groupe Dyrsia. All Rights Reserved<br /><br /></div>
</body>

</html>