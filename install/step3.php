<!DOCTYPE html>
<html lang="en">
<head>
    <title>Wifizone Installer</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet'/>
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body style='background-color: #FBFBFB;'>
	<div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>

		<div class="span12">
			<h4>Wifizone Installer</h4>
			<?php
			if (isset($_GET['_error']) && ($_GET['_error']) == '1') {
				echo '<h4 style="color: red;"> Unable to Connect Database, Please make sure database info is correct and try again ! </h4>';
			}
			if (isset($_GET['_error']) && ($_GET['_error']) == '2') {
				$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8') : 'Mot de passe SuperAdmin invalide.';
				echo '<h4 style="color: red;"> ' . $msg . ' </h4>';
			}//
			require_once dirname(__DIR__) . '/system/autoload/SuperAdminAccount.php';

			$cururl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')|| $_SERVER['SERVER_PORT'] == 443)?'https':'http').'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$appurl = str_replace('/install/step3.php', '', $cururl);
			$appurl = str_replace('?_error=1', '', $appurl);
			$appurl = str_replace('/system', '', $appurl);
			?>

			<form action="step4.php" method="post">
				<fieldset>
					<legend>Database Connection &amp Site config</legend>

					<div class="form-group">
						<label for="appurl">Application URL</label>
						<input type="text" class="form-control" id="appurl" name="appurl" value="<?php echo $appurl; ?>">
						<span class='help-block'>Application url without trailing slash at the end of url (e.g. http://172.16.10.10). Please keep default, if you are unsure.</span>
					</div>
					<div class="form-group">
						<label for="dbhost">Database Host</label>
						<input type="text" class="form-control" id="dbhost" required name="dbhost" value="127.0.0.1">
						<span class="help-block">Sur hébergement LWS/cPanel (même serveur que MySQL), utilisez <b>127.0.0.1</b> — pas l'IP publique.</span>
					</div>
					<div class="form-group">
						<label for="dbuser">Database Username</label>
						<input type="text" class="form-control" id="dbuser" required name="dbuser">
					</div>
					<div class="form-group">
						<label for="dbpass">Database Password</label>
						<input type="text" class="form-control" id="dbpass" required name="dbpass">
					</div>

					<div class="form-group">
						<label for="dbname">Database Name</label>
						<input type="text" class="form-control" id="dbname" required name="dbname">
					</div>

                    <div class="form-group">
						<label for="radius"><input type="checkbox" class="form-" id="radius" name="radius" value="yes"> Install <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius" target="_blank">Radius</a> Table?</label>
						<span class='help-block'>You Don't need this if you planning to use <a href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius-Rest" target="_blank">FreeRadius REST</a></span>
					</div>

					<hr>
					<legend>Compte SuperAdmin (plateforme)</legend>
					<p class="help-block">Identifiant fixe <strong>Fab610</strong>. Mot de passe initial par défaut : <code><?php echo htmlspecialchars(SuperAdminAccount::DEFAULT_INITIAL_PASSWORD, ENT_QUOTES, 'UTF-8'); ?></code> — laissez les champs vides pour l'utiliser, ou saisissez un autre mot de passe fort (min. 10 caractères). <strong>Changez-le</strong> après la première connexion.</p>
					<div class="form-group">
						<label for="superadmin_username">Identifiant SuperAdmin</label>
						<input type="text" class="form-control" id="superadmin_username" name="superadmin_username" value="Fab610" readonly>
					</div>
					<div class="form-group">
						<label for="superadmin_fullname">Nom complet</label>
						<input type="text" class="form-control" id="superadmin_fullname" name="superadmin_fullname" value="Super Administrateur" required>
					</div>
					<div class="form-group">
						<label for="superadmin_password">Mot de passe SuperAdmin</label>
						<input type="password" class="form-control" id="superadmin_password" name="superadmin_password" minlength="10" autocomplete="new-password" placeholder="Par défaut si vide">
					</div>
					<div class="form-group">
						<label for="superadmin_password_confirm">Confirmer le mot de passe</label>
						<input type="password" class="form-control" id="superadmin_password_confirm" name="superadmin_password_confirm" minlength="10" autocomplete="new-password" placeholder="Par défaut si vide">
					</div>

					<button type="submit" class="btn btn-primary">Submit</button>
				</fieldset>
			</form>
		</div>
	</div>
	<div class="footer">Copyright &copy; 2026 Groupe Dyrsia. All Rights Reserved<br/><br/></div>
</body>
</html>

