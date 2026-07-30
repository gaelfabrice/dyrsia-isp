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
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .alert-warning { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<?php
$appRoot = realpath(dirname(__DIR__));
$configFile = $appRoot . DIRECTORY_SEPARATOR . 'config.php';
$sourceDir = $appRoot . DIRECTORY_SEPARATOR . 'pages_template';
$targetDir = $appRoot . DIRECTORY_SEPARATOR . 'pages';

// Check if config.php exists
$configExists = file_exists($configFile);

function copyDir($src, $dst) {
    $dir = opendir($src);
    if (!$dir) {
        throw new Exception("Cannot open directory: $src");
    }

    if (!file_exists($dst)) {
        if (!mkdir($dst, 0777, true)) {
            throw new Exception("Failed to create directory: $dst");
        }
    }

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                if (!copy($src . '/' . $file, $dst . '/' . $file)) {
                    throw new Exception("Failed to copy $src/$file to $dst/$file");
                }
            }
        }
    }
    closedir($dir);
}

function removeDir($dir) {
    if (!is_dir($dir)) return;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object == '.' || $object == '..') continue;
        if (is_dir($dir . '/' . $object))
            removeDir($dir . '/' . $object);
        else
            if (!unlink($dir . '/' . $object)) {
                throw new Exception("Failed to delete file: $dir/$object");
            }
    }
    if (!rmdir($dir)) {
        throw new Exception("Failed to remove directory: $dir");
    }
}

try {
    if ($appRoot && is_dir($sourceDir)) {
        copyDir($sourceDir, $targetDir);
        removeDir($sourceDir);
    }
} catch (Exception $e) {
    echo '<p class="text-warning">Pages template: ', htmlspecialchars($e->getMessage()), '</p>';
}
?>
<body style='background-color: #FBFBFB;'>
    <div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>
        <div class="span12">
            <h4>Wifizone Installer</h4>
            
            <?php if (!$configExists): ?>
            <div class="alert-danger">
                <strong>⚠️ config.php is missing!</strong>
                <p>The file <code><?php echo $configFile; ?></code> was not found.</p>
                <p>Please go back to <a href="step3.php">Step 3</a> and complete the database configuration, or create the config.php file manually.</p>
            </div>
            <?php else: ?>
            
            <p>
                <strong>✅ Congratulations!</strong><br>
                You have successfully installed the application!<br><br>
                <span class="text-danger">Important steps:<br>
                    <ol>
                        <li>Activate <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/Cron-Jobs" target="_blank">Cronjob</a> for Expired and Reminder.</li>
                        <li>Check <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja" target="_blank">how the system Works</a></li>
                        <li><a href="https://github.com/hotspotbilling/phpnuxbill/wiki#login-page-mikrotik" target="_blank">How to link Mikrotik Login</a></li>
                    </ol>
                </span><br><br>
                To Login Admin Portal:<br>
                Use this link -
                <?php
                require_once dirname(__DIR__) . '/system/autoload/SuperAdminAccount.php';
                $cururl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $appurl = str_replace('/install/step5.php', '', $cururl);
                $appurl = str_replace('/system', '', $appurl);
                echo '<a href="' . $appurl . '/admin">' . $appurl . '/admin</a>';
                ?>
                <br>
                Username: <strong>Fab610</strong><br>
                Password (initial par défaut) : <strong><?php echo htmlspecialchars(SuperAdminAccount::DEFAULT_INITIAL_PASSWORD, ENT_QUOTES, 'UTF-8'); ?></strong>
                — si vous en avez choisi un autre à l'étape 3, utilisez celui-là. Changez-le après la première connexion.<br><br>
                <div class="alert-warning">
                    <strong>🔒 Security:</strong> For security, delete the <code>install</code> directory after installation is complete.
                </div>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer">Copyright &copy; 2026 Groupe Dyrsia. All Rights Reserved<br /><br /></div>
</body>

</html>