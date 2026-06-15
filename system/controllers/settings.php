<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Settings'));
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (in_array($action, ['app', 'app-post', 'miscellaneous', 'miscellaneous-post', 'maintenance', 'maintenance-post', 'devices', 'dbstatus']) && $admin['user_type'] != 'SuperAdmin') {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

function settings_scoped_router_query($admin)
{
    $query = ORM::for_table('tbl_routers');
    if ($admin['user_type'] != 'SuperAdmin') {
        $query->where('admin_id', $admin['id']);
    }
    return $query;
}

function settings_users_base_query($admin)
{
    if ($admin['user_type'] == 'SuperAdmin') {
        return ORM::for_table('tbl_users');
    }
    if ($admin['user_type'] == 'Admin') {
        return ORM::for_table('tbl_users')->where_any_is([
            ['user_type' => 'Report'],
            ['user_type' => 'Agent'],
            ['user_type' => 'Sales'],
            ['id' => $admin['id']],
        ]);
    }
    return ORM::for_table('tbl_users')->where_any_is([
        ['id' => $admin['id']],
        ['root' => $admin['id']],
    ]);
}

function settings_users_apply_search($query, $search)
{
    if ($search != '') {
        $query->where_raw(
            '(`username` LIKE ? OR `email` LIKE ? OR `fullname` LIKE ? OR `phone` LIKE ?)',
            ["%$search%", "%$search%", "%$search%", "%$search%"]
        );
    }
    return $query;
}

switch ($action) {
    case 'smtp':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $smtpDefaults = [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_ssltls' => 'tls',
            'mail_from' => '',
            'mail_reply_to' => ''
        ];
        foreach ($smtpDefaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_c', $config);
        $ui->display('admin/settings/smtp.tpl');
        break;
    case 'smtp-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/smtp'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $smtpHost = trim(_post('smtp_host'));
        $smtpPort = trim(_post('smtp_port'));
        $smtpUser = trim(_post('smtp_user'));
        $smtpPass = trim(_post('smtp_pass'));
        $smtpSecure = trim(_post('smtp_ssltls'));
        $mailFrom = trim(_post('mail_from'));
        $mailReplyTo = trim(_post('mail_reply_to'));
        $testEmail = trim(_post('test_email'));
        if ($smtpHost == '' || !preg_match('/^[a-zA-Z0-9.-]+$/', $smtpHost)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid SMTP server'));
        }
        if ($smtpPort == '' || !ctype_digit($smtpPort) || intval($smtpPort) < 1 || intval($smtpPort) > 65535) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid SMTP port'));
        }
        if (!in_array($smtpSecure, ['tls', 'ssl', ''], true)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid SMTP security mode'));
        }
        if ($smtpUser == '') {
            r2(getUrl('settings/smtp'), 'e', Lang::T('SMTP login is required'));
        }
        if ($smtpPass == '') {
            r2(getUrl('settings/smtp'), 'e', Lang::T('SMTP password is required'));
        }
        if ($mailFrom != '' && !Validator::Email($mailFrom)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid sender email'));
        }
        if ($mailReplyTo != '' && !Validator::Email($mailReplyTo)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid reply-to email'));
        }
        if ($testEmail != '' && !Validator::Email($testEmail)) {
            r2(getUrl('settings/smtp'), 'e', Lang::T('Invalid test email'));
        }
        $smtpKeys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_ssltls', 'mail_from', 'mail_reply_to'];
        foreach ($smtpKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if ($d) {
                $d->value = $value;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = $key;
                $d->value = $value;
                $d->save();
            }
            $config[$key] = $value;
        }
        if (!empty(_post('test_email'))) {
            $sent = Message::sendEmail($testEmail, Lang::T('SMTP Test Email'), Lang::T('Your SMTP server is configured correctly.'));
            if (!$sent) {
                r2(getUrl('settings/smtp'), 'e', Lang::T('SMTP settings saved, but test email could not be sent. Please check SMTP login, password and Gmail App Password.'));
            }
        }
        _log('[' . $admin['username'] . ']: ' . Lang::T('SMTP Settings Saved Successfully'), $admin['user_type'], $admin['id']);
        r2(getUrl('settings/smtp'), 's', Lang::T('Settings Saved Successfully'));
        break;
    case 'docs':
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'docs_clicked')->find_one();
        if ($d) {
            $d->value = 'yes';
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'docs_clicked';
            $d->value = 'yes';
            $d->save();
        }
        r2(APP_URL . '/docs');
        break;
    case 'devices':
        $files = scandir($DEVICE_PATH);
        $devices = [];
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $dev = pathinfo($file, PATHINFO_FILENAME);
                require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . $file;
                $dvc = new $dev;
                if (method_exists($dvc, 'description')) {
                    $arr = $dvc->description();
                    $arr['file'] = $dev;
                    $devices[] = $arr;
                } else {
                    $devices[] = [
                        'title' => $dev,
                        'description' => '',
                        'author' => 'unknown',
                        'url' => [],
                        'file' => $dev
                    ];
                }
            }
        }
        $ui->assign('devices', $devices);
        $ui->display('admin/settings/devices.tpl');
        break;
    case 'app':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        if (!empty(_get('testWa'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
            }
            $result = Message::sendWhatsapp(_get('testWa'), Lang::T('wifizones Test Whatsapp'));
            r2(getUrl('settings/app') . '#collapseWhatsappNotification', 's', Lang::T('Test Whatsapp has been send') . '<br>Result: ' . $result);
        }
        if (!empty(_get('testSms'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app') . '#collapseSMSNotification', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            $result = Message::sendSMS(_get('testSms'), Lang::T('wifizones Test SMS'));
            r2(getUrl('settings/app') . '#collapseSMSNotification', 's', Lang::T('Test SMS has been send') . '<br>Result: ' . $result);
        }
        if (!empty(_get('testEmail'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app') . '#collapseEmailNotification', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            Message::sendEmail(_get('testEmail'), Lang::T('wifizones Test Email'), Lang::T('wifizones Test Email Body'));
            r2(getUrl('settings/app') . '#collapseEmailNotification', 's', Lang::T('Test Email has been send'));
        }
        if (!empty(_get('testTg'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app') . '#collapseTelegramNotification', 'e', Lang::T('You cannot perform this action in Demo mode'));
            }
            $result = Message::sendTelegram(Lang::T('wifizones Test Telegram'));
            r2(getUrl('settings/app') . '#collapseTelegramNotification', 's', Lang::T('Test Telegram has been send') . '<br>Result: ' . $result);
        }

        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png?' . time();
        } else {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.default.png';
        }
        $ui->assign('logo', $logo);

        if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
        } else {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
        }

        if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
        } else {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
        }

        if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
        } else {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
        }

        $ui->assign('login_logo', $login_logo);
        $ui->assign('wallpaper', $wallpaper);
        $ui->assign('favicon', $favicon);

        $themes = [];
        $files = scandir('ui/themes/');
        foreach ($files as $file) {
            if (is_dir('ui/themes/' . $file) && !in_array($file, ['.', '..'])) {
                $themes[] = $file;
            }
        }

        $template_files = glob('ui/ui/customer/login-custom-*.tpl');
        $templates = [];

        foreach ($template_files as $file) {
            $parts = explode('-', basename($file, '.tpl'));
            $template_identifier = $parts[2] ?? 'unknown';
            $templates[] = [
                'filename' => basename($file),
                'value' => $template_identifier,
                'name' => str_replace('_', ' ', ucfirst($template_identifier))
            ];
        }

        $r = settings_scoped_router_query($admin)->find_many();
        $ui->assign('r', $r);
        if (function_exists("shell_exec")) {
            $php = trim(shell_exec('which php'));
            if (empty($php)) {
                $php = 'php';
            }
        } else {
            $php = 'php';
        }
        if (empty($config['api_key'])) {
            $config['api_key'] = sha1(uniqid(rand(), true));
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'api_key')->find_one();
            if ($d) {
                $d->value = $config['api_key'];
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'api_key';
                $d->value = $config['api_key'];
                $d->save();
            }
        }
        if (empty($config['mikrotik_sms_command'])) {
            $config['mikrotik_sms_command'] = "/tool sms send";
        }
        $ui->assign('template_files', $templates);
        $ui->assign('_c', $config);
        $ui->assign('php', $php);
        $ui->assign('dir', str_replace('controllers', '', __DIR__));
        $ui->assign('themes', $themes);
        run_hook('view_app_settings'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/app.tpl');
        break;

    case 'app-post':

        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
        }

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');

        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/app'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $company = _post('CompanyName');
        $custom_tax_rate = filter_var(_post('custom_tax_rate'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (preg_match('/[^0-9.]/', $custom_tax_rate)) {
            r2(getUrl('settings/app'), 'e', 'Special characters are not allowed in tax rate');
            die();
        }
        run_hook('save_settings'); #HOOK
        if (!empty($_FILES['logo']['name'])) {
            if (function_exists('imagecreatetruecolor')) {
                if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png'))
                    unlink($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png');
                File::resizeCropImage($_FILES['logo']['tmp_name'], $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png', 1078, 200, 100);
                if (file_exists($_FILES['logo']['tmp_name']))
                    unlink($_FILES['logo']['tmp_name']);
            } else {
                r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
            }
        }
        if (!empty($_POST['general']) && $company == '') {
            r2(getUrl('settings/app'), 'e', Lang::T('All field is required'));
        } else {
            if ($radius_enable) {
                try {
                    require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "Radius.php";
                    (new Radius())->getTableNas()->find_many();
                } catch (Exception $e) {
                    $ui->assign("error_title", "RADIUS Error");
                    $ui->assign("error_message", "Radius table not found.<br><br>" .
                        $e->getMessage() .
                        "<br><br>Download <a href=\"https://raw.githubusercontent.com/hotspotbilling/phpwifizones/Development/install/radius.sql\">here</a> or <a href=\"https://raw.githubusercontent.com/hotspotbilling/phpwifizones/master/install/radius.sql\">here</a> and import it to database.<br><br>Check config.php for radius connection details");
                    $ui->display('admin/error.tpl');
                    die();
                }
            }
            // Save all settings including tax system
            $_POST['man_fields_email'] = isset($_POST['man_fields_email']) ? 'yes' : 'no';
            $_POST['man_fields_fname'] = isset($_POST['man_fields_fname']) ? 'yes' : 'no';
            $_POST['man_fields_address'] = isset($_POST['man_fields_address']) ? 'yes' : 'no';
            $_POST['man_fields_custom'] = isset($_POST['man_fields_custom']) ? 'yes' : 'no';
            $enable_session_timeout = isset($_POST['enable_session_timeout']) ? 1 : 0;
            $_POST['enable_session_timeout'] = $enable_session_timeout;
            $_POST['notification_reminder_1day'] = isset($_POST['notification_reminder_1day']) ? 'yes' : 'no';
            $_POST['notification_reminder_3days'] = isset($_POST['notification_reminder_3days']) ? 'yes' : 'no';
            $_POST['notification_reminder_7days'] = isset($_POST['notification_reminder_7days']) ? 'yes' : 'no';

            // hide dashboard
            $_POST['hide_mrc'] = _post('hide_mrc', 'no');
            $_POST['hide_tms'] = _post('hide_tms', 'no');
            $_POST['hide_al'] = _post('hide_al', 'no');
            $_POST['hide_uet'] = _post('hide_uet', 'no');
            $_POST['hide_vs'] = _post('hide_vs', 'no');
            $_POST['hide_pg'] = _post('hide_pg', 'no');
            $_POST['hide_aui'] = _post('hide_aui', 'no');

            // Login page post
            $login_page_title = _post('login_page_head');
            $login_page_description = _post('login_page_description');
            $login_Page_template = _post('login_Page_template');
            $login_page_type = _post('login_page_type');
            $csrf_token = _post('csrf_token');

            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/app'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }

            if ($login_page_type == 'custom' && (empty($login_Page_template) || empty($login_page_title) || empty($login_page_description))) {
                r2(getUrl('settings/app'), 'e', 'Please fill all required fields');
                return;
            }

            if (strlen($login_page_title) > 25) {
                r2(getUrl('settings/app'), 'e', 'Login page title must not exceed 25 characters');
                return;
            }
            if (strlen($login_page_description) > 100) {
                r2(getUrl('settings/app'), 'e', 'Login page description must not exceed 50 characters');
                return;
            }

            $image_paths = [];
            $allowed_types = ['image/jpeg', 'image/png'];

            if ($_FILES['login_page_favicon']['name'] != '') {
                $favicon_type = $_FILES['login_page_favicon']['type'];
                if (in_array($favicon_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_favicon']['name'])) {
                    $extension = pathinfo($_FILES['login_page_favicon']['name'], PATHINFO_EXTENSION);
                    $favicon_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('favicon_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_favicon']['tmp_name'], $favicon_path, 16, 16, 100);
                    $_POST['login_page_favicon'] = basename($favicon_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_favicon']['tmp_name']))
                        unlink($_FILES['login_page_favicon']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Favicon must be a JPG, JPEG, or PNG image.');
                }
            }

            if ($_FILES['login_page_wallpaper']['name'] != '') {
                $wallpaper_type = $_FILES['login_page_wallpaper']['type'];
                if (in_array($wallpaper_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_wallpaper']['name'])) {
                    $extension = pathinfo($_FILES['login_page_wallpaper']['name'], PATHINFO_EXTENSION);
                    $wallpaper_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('wallpaper_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_wallpaper']['tmp_name'], $wallpaper_path, 1920, 1080, 100);
                    $_POST['login_page_wallpaper'] = basename($wallpaper_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_wallpaper']['tmp_name']))
                        unlink($_FILES['login_page_wallpaper']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Wallpaper must be a JPG, JPEG, or PNG image.');
                }
            }

            if ($_FILES['login_page_logo']['name'] != '') {
                $logo_type = $_FILES['login_page_logo']['type'];
                if (in_array($logo_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_logo']['name'])) {
                    $extension = pathinfo($_FILES['login_page_logo']['name'], PATHINFO_EXTENSION);
                    $logo_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('logo_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_logo']['tmp_name'], $logo_path, 300, 60, 100);
                    $_POST['login_page_logo'] = basename($logo_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_logo']['tmp_name']))
                        unlink($_FILES['login_page_logo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Logo must be a JPG, JPEG, or PNG image.');
                }
            }
            foreach ($_POST as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }
            _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type'], $admin['id']);

            $panel = preg_replace('/[^a-zA-Z0-9_]/', '', _post('settings_panel'));
            $panelHash = $panel ? '#' . $panel : '';
            r2(getUrl('settings/app') . $panelHash, 's', Lang::T('Settings Saved Successfully'));
        }
        break;

    case 'localisation':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $folders = [];
        $files = scandir('system/lan/');
        foreach ($files as $file) {
            if (is_file('system/lan/' . $file) && !in_array($file, ['index.html', 'country.json', '.DS_Store'])) {
                $file = str_replace(".json", "", $file);
                $folders[$file] = '';
            }
        }
        $ui->assign('lani', $folders);
        $lans = Lang::getIsoLang();
        foreach ($lans as $lan => $val) {
            if (isset($folders[$lan])) {
                unset($lans[$lan]);
            }
        }
        $ui->assign('lan', $lans);
        $timezonelist = Timezone::timezoneList();
        $ui->assign('tlist', $timezonelist);
        $canEditTimezone = ($admin['user_type'] === 'SuperAdmin');
        $tenantRow = Tenant::findTenantForUserId((int) $admin['id']);
        $effectiveTimezone = $config['timezone'];
        if ($tenantRow && !empty($tenantRow['timezone'])) {
            $effectiveTimezone = $tenantRow['timezone'];
        }
        $tenantCountry = $tenantRow ? MobileMoneyCountry::resolve($tenantRow['country_code'] ?? '') : null;
        $ui->assign('can_edit_timezone', $canEditTimezone);
        $ui->assign('effective_timezone', $effectiveTimezone);
        $ui->assign('tenant_country_name', $tenantCountry['name'] ?? '');
        $ui->assign('xjq', ' $("#tzone").select2(); ');
        run_hook('view_localisation'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/localisation.tpl');
        break;

    case 'localisation-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/localisation'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/localisation'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $tzone = _post('tzone');
        $date_format = _post('date_format');
        $country_code_phone = _post('country_code_phone');
        $lan = _post('lan');
        run_hook('save_localisation'); #HOOK
        if ($date_format == '' or $lan == '') {
            r2(getUrl('settings/localisation'), 'e', Lang::T('All field is required'));
        }

        $isSuperAdmin = ($admin['user_type'] === 'SuperAdmin');
        if (!$isSuperAdmin) {
            $tenantRow = Tenant::findTenantForUserId((int) $admin['id']);
            if ($tenantRow && !empty($tenantRow['timezone'])) {
                $tzone = $tenantRow['timezone'];
            } elseif ($tzone === '') {
                $tzone = $config['timezone'];
            }
        } elseif ($tzone === '') {
            r2(getUrl('settings/localisation'), 'e', Lang::T('All field is required'));
        }

        if ($isSuperAdmin && $tzone !== '') {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'timezone')->find_one();
            if ($d) {
                $d->value = $tzone;
                $d->save();
            }
            $config['timezone'] = $tzone;
            date_default_timezone_set($tzone);
            $currentTenant = Tenant::current();
            if ($currentTenant) {
                Tenant::updateTenantTimezone((int) $currentTenant['id'], $tzone);
            }
        }

        $d = ORM::for_table('tbl_appconfig')->where('setting', 'date_format')->find_one();
            $d->value = $date_format;
            $d->save();

            $dec_point = _post('dec_point');
            if (strlen($dec_point) == 1) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'dec_point')->find_one();
                $d->value = $dec_point;
                $d->save();
            }

            $thousands_sep = _post('thousands_sep');
            if (strlen($thousands_sep) == 1) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'thousands_sep')->find_one();
                $d->value = $thousands_sep;
                $d->save();
            }

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'country_code_phone')->find_one();
            if ($d) {
                $d->value = $country_code_phone;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'country_code_phone';
                $d->value = $country_code_phone;
                $d->save();
            }

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'radius_plan')->find_one();
            if ($d) {
                $d->value = _post('radius_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'radius_plan';
                $d->value = _post('radius_plan');
                $d->save();
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_plan')->find_one();
            if ($d) {
                $d->value = _post('hotspot_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'hotspot_plan';
                $d->value = _post('hotspot_plan');
                $d->save();
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'pppoe_plan')->find_one();
            if ($d) {
                $d->value = _post('pppoe_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'pppoe_plan';
                $d->value = _post('pppoe_plan');
                $d->save();
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_plan')->find_one();
            if ($d) {
                $d->value = _post('vpn_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'vpn_plan';
                $d->value = _post('vpn_plan');
                $d->save();
            }

            $currency_code = _post('currency_code');
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'currency_code')->find_one();
            $d->value = $currency_code;
            $d->save();

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'language')->find_one();
            $d->value = $lan;
            $d->save();
            _log('[' . $admin['username'] . ']: ' . 'Settings Saved Successfully', $admin['user_type'], $admin['id']);
            r2(getUrl('settings/localisation'), 's', 'Settings Saved Successfully');
        break;

    case 'users':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $search = trim((string) _req('search'));

        $total_admins = (int) settings_users_apply_search(settings_users_base_query($admin), $search)->count();
        $superadmin_count = (int) settings_users_apply_search(settings_users_base_query($admin), $search)
            ->where('user_type', 'SuperAdmin')->count();
        $administrators_count = (int) settings_users_apply_search(settings_users_base_query($admin), $search)
            ->where('user_type', 'Admin')->count();

        $query = settings_users_apply_search(settings_users_base_query($admin), $search)->order_by_asc('id');
        $d = Paginator::findMany($query, ['search' => $search]);

        $admins = [];
        foreach ($d as $k) {
            if (!empty($k['root'])) {
                $admins[] = $k['root'];
            }
        }
        if (count($admins) > 0) {
            $adms = ORM::for_table('tbl_users')->where_in('id', $admins)->findArray();
            unset($admins);
            foreach ($adms as $adm) {
                $admins[$adm['id']] = $adm['fullname'];
            }
        }
        $ui->assign('admins', $admins);
        Tenant::ensureUserTenantColumn();
        $ui->assign('tenant_names', Tenant::tenantNamesMap());
        $ui->assign('tenant_slugs', Tenant::tenantSlugsMap());
        $ui->assign('tenant_domain_suffix', Tenant::domainSuffix());
        $ui->assign('total_admins', $total_admins);
        $ui->assign('superadmin_count', $superadmin_count);
        $ui->assign('administrators_count', $administrators_count);
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        run_hook('view_list_admin'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/admin/list.tpl');
        break;

    case 'users-add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_title', Lang::T('Add User'));
        $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
        Tenant::ensureUserTenantColumn();
        $ui->assign('isp_tenants', Tenant::listForSelect());
        $ui->assign('creator_isp', Tenant::findTenantForUserId((int) $admin['id']));
        $ui->assign('requires_isp_choices', in_array($admin['user_type'], ['SuperAdmin'], true));
        $ui->display('admin/admin/add.tpl');
        break;
    case 'users-view':
        $ui->assign('_title', Lang::T('Edit User'));
        $id = $routes['2'];
        if (empty($id)) {
            $id = $admin['id'];
        }
        //allow see himself
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->where('id', $id)->find_array()[0];
        } else {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                // Super Admin can see anyone
                $d = ORM::for_table('tbl_users')->where('id', $id)->find_array()[0];
            } else if ($admin['user_type'] == 'Agent') {
                // Agent can see Sales
                $d = ORM::for_table('tbl_users')->where_any_is([['root' => $admin['id']], ['id' => $id]])->find_array()[0];
            }
        }
        if ($d) {
            run_hook('view_edit_admin'); #HOOK
            if ($d['user_type'] == 'Sales') {
                $ui->assign('agent', ORM::for_table('tbl_users')->where('id', $d['root'])->find_array()[0]);
            }
            $ui->assign('d', $d);
            $ui->assign('_title', $d['username']);
            $csrf_token = Csrf::generateAndStoreToken();
            $ui->assign('csrf_token', $csrf_token);
            $ui->display('admin/admin/view.tpl');
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;
    case 'users-edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Edit User'));
        $id = $routes['2'];
        if (empty($id)) {
            $id = $admin['id'];
        }
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->find_one($id);
        } else {
            if ($admin['user_type'] == 'SuperAdmin') {
                $d = ORM::for_table('tbl_users')->find_one($id);
                $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
            } else if ($admin['user_type'] == 'Admin') {
                $d = ORM::for_table('tbl_users')->where_any_is([
                    ['user_type' => 'Report'],
                    ['user_type' => 'Agent'],
                    ['user_type' => 'Sales']
                ])->find_one($id);
                $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
            } else {
                // Agent cannot move Sales to other Agent
                $ui->assign('agents', ORM::for_table('tbl_users')->where('id', $admin['id'])->find_many());
                $d = ORM::for_table('tbl_users')->where('root', $admin['id'])->find_one($id);
            }
        }
        if ($d) {
            if (isset($routes['3']) && $routes['3'] == 'deletePhoto') {
                if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                    if (file_exists($UPLOAD_PATH . $d['photo']) && strpos($d['photo'], 'default') === false) {
                        unlink($UPLOAD_PATH . $d['photo']);
                        if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                            unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                        }
                    }
                    $d->photo = '/admin.default.png';
                    $d->save();
                    $ui->assign('notify_t', 's');
                    $ui->assign('notify', 'You have successfully deleted the photo');
                } else {
                    $ui->assign('notify_t', 'e');
                    $ui->assign('notify', 'No photo found to delete');
                }
            }
            $ui->assign('id', $id);
            $ui->assign('d', $d);
            Tenant::ensureUserTenantColumn();
            $ui->assign('isp_tenants', Tenant::listForSelect());
            $ui->assign('user_isp', Tenant::findTenantForUserId((int) $d['id']));
            $ui->assign('creator_isp', Tenant::findTenantForUserId((int) $admin['id']));
            $ui->assign('requires_isp_choices', in_array($admin['user_type'], ['SuperAdmin'], true));
            run_hook('view_edit_admin'); #HOOK
            $csrf_token = Csrf::generateAndStoreToken();
            $ui->assign('csrf_token', $csrf_token);
            $ui->display('admin/admin/edit.tpl');
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users'), 'e', 'You cannot perform this action in Demo mode');
        }
        $id = $routes['2'];
        if (($admin['id']) == $id) {
            r2(getUrl('settings/users'), 'e', 'Sorry You can\'t delete yourself');
        }
        $d = ORM::for_table('tbl_users')->find_one($id);
        if ($d) {
            if ($admin['user_type'] != 'SuperAdmin') {
                if (!in_array($d['user_type'], ['Report', 'Agent', 'Sales'], true)) {
                    r2(getUrl('settings/users'), 'e', Lang::T('You do not have permission to access this page'));
                }
            }
            run_hook('delete_admin'); #HOOK
            if ($d['user_type'] == 'Admin') {
                Tenant::deleteInstanceForAdmin((int) $d['id']);
            }
            $d->delete();
            r2(getUrl('settings/users'), 's', Lang::T('User deleted Successfully'));
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users-add'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/users-add'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $user_type = _post('user_type');
        $phone = _post('phone');
        $email = _post('email');
        $city = _post('city');
        $subdistrict = _post('subdistrict');
        $ward = _post('ward');
        $send_notif = _post('send_notif');
        $root = _post('root');
        $ispBusinessName = trim(_post('isp_business_name'));
        if ($ispBusinessName === '') {
            $ispBusinessName = trim(_post('tenant_id'));
        }
        $msg = '';
        if (Validator::Length($username, 45, 2) == false) {
            $msg .= Lang::T('Username should be between 3 to 45 characters') . '<br>';
        }
        if (Validator::Length($fullname, 45, 2) == false) {
            $msg .= Lang::T('Full Name should be between 3 to 45 characters') . '<br>';
        }
        if (!Validator::Length($password, 1000, 5)) {
            $msg .= Lang::T('Password should be minimum 6 characters') . '<br>';
        }

        $tenantResolved = Tenant::resolveTenantForNewUser($ispBusinessName, $user_type, $admin);
        if (!$tenantResolved['ok']) {
            $msg .= $tenantResolved['msg'] . '<br>';
        }

        $d = ORM::for_table('tbl_users')->where('username', $username)->find_one();
        if ($d) {
            $msg .= Lang::T('Account already axist') . '<br>';
        }
        $date_now = date("Y-m-d H:i:s");
        run_hook('add_admin'); #HOOK
        if ($msg == '') {
            $passwordC = Password::_crypt($password);
            $d = ORM::for_table('tbl_users')->create();
            $d->username = $username;
            $d->fullname = $fullname;
            $d->password = $passwordC;
            $d->user_type = $user_type;
            $d->phone = $phone;
            $d->email = $email;
            $d->city = $city;
            $d->subdistrict = $subdistrict;
            $d->ward = $ward;
            $d->status = 'Active';
            $d->creationdate = $date_now;
            if ($admin['user_type'] == 'Agent') {
                // Prevent hacking from form
                $d->root = $admin['id'];
            } else if ($user_type == 'Sales') {
                $d->root = $root;
            }
            Tenant::assignUserTenant($d, $tenantResolved['tenant']);
            $d->save();
            if ($user_type == 'Admin') {
                AdminSubscription::ensureTrial((int) $d->id());
            }

            if ($send_notif == 'wa') {
                Message::sendWhatsapp(Lang::phoneFormat($phone), Lang::T('Hello, Your account has been created successfully.') . "\nUsername: $username\nPassword: $password\n\n" . $config['CompanyName']);
            } else if ($send_notif == 'sms') {
                Message::sendSMS($phone, Lang::T('Hello, Your account has been created successfully.') . "\nUsername: $username\nPassword: $password\n\n" . $config['CompanyName']);
            }

            _log('[' . $admin['username'] . ']: ' . "Created $user_type <b>$username</b>", $admin['user_type'], $admin['id']);
            r2(getUrl('settings/users'), 's', Lang::T('Account Created Successfully'));
        } else {
            r2(getUrl('settings/users-add'), 'e', $msg);
        }
        break;

    case 'users-edit-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users-edit/'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/users-edit/'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $user_type = _post('user_type');
        $phone = _post('phone');
        $email = _post('email');
        $city = _post('city');
        $subdistrict = _post('subdistrict');
        $ward = _post('ward');
        $status = _post('status');
        $root = _post('root');
        $ispBusinessName = trim(_post('isp_business_name'));
        if ($ispBusinessName === '') {
            $ispBusinessName = trim(_post('tenant_id'));
        }
        $msg = '';
        if (Validator::Length($username, 45, 2) == false) {
            $msg .= Lang::T('Username should be between 3 to 45 characters') . '<br>';
        }
        if (Validator::Length($fullname, 45, 2) == false) {
            $msg .= Lang::T('Full Name should be between 3 to 45 characters') . '<br>';
        }
        if ($password != '') {
            if (!Validator::Length($password, 1000, 5)) {
                $msg .= Lang::T('Password should be minimum 6 characters') . '<br>';
            }
            if ($password != $cpassword) {
                $msg .= Lang::T('Passwords does not match') . '<br>';
            }
        }

        $id = _post('id');
        $editUserType = _post('user_type');
        if (($admin['id']) == $id) {
            $editUserType = ORM::for_table('tbl_users')->find_one($id)->user_type ?? $editUserType;
        }
        $tenantResolved = Tenant::resolveTenantForNewUser($ispBusinessName, $editUserType, $admin);
        if (!$tenantResolved['ok']) {
            $msg .= $tenantResolved['msg'] . '<br>';
        }
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->find_one($id);
        } else {
            if ($admin['user_type'] == 'SuperAdmin') {
                $d = ORM::for_table('tbl_users')->find_one($id);
            } else if ($admin['user_type'] == 'Admin') {
                $d = ORM::for_table('tbl_users')->where_any_is([
                    ['user_type' => 'Report'],
                    ['user_type' => 'Agent'],
                    ['user_type' => 'Sales']
                ])->find_one($id);
            } else {
                $d = ORM::for_table('tbl_users')->where('root', $admin['id'])->find_one($id);
            }
        }
        if (!$d) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['username'] != $username) {
            $c = ORM::for_table('tbl_users')->where('username', $username)->find_one();
            if ($c) {
                $msg .= "<b>$username</b> " . Lang::T('Account already axist') . '<br>';
            }
        }
        run_hook('edit_admin'); #HOOK
        if ($msg == '') {
            $previousStatus = $d->status;
            if (!empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
                if (function_exists('imagecreatetruecolor')) {
                    $hash = md5_file($_FILES['photo']['tmp_name']);
                    $subfolder = substr($hash, 0, 2);
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $imgPath = $folder . $hash . '.jpg';
                    if (!file_exists($imgPath)) {
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                    }
                    if (!file_exists($imgPath . '.thumb.jpg')) {
                        if (_post('faceDetect') == 'yes') {
                            try {
                                $detector = new svay\FaceDetector();
                                $detector->setTimeout(5000);
                                $detector->faceDetect($imgPath);
                                $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                            } catch (Exception $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            } catch (Throwable $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            }
                        } else {
                            File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                        }
                    }
                    if (file_exists($imgPath)) {
                        if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                            if (file_exists($UPLOAD_PATH . $d['photo'])) {
                                unlink($UPLOAD_PATH . $d['photo']);
                                if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                                    unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                                }
                            }
                        }
                        $d->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name']))
                        unlink($_FILES['photo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }

            $d->username = $username;
            if ($password != '') {
                $password = Password::_crypt($password);
                $d->password = $password;
            }

            $d->fullname = $fullname;
            if (($admin['id']) != $id) {
                $user_type = _post('user_type');
                $d->user_type = $user_type;
            }
            $d->phone = $phone;
            $d->email = $email;
            $d->city = $city;
            $d->subdistrict = $subdistrict;
            $d->ward = $ward;
            if (isset($_POST['status'])) {
                $d->status = $status;
            }

            if ($admin['user_type'] == 'Agent') {
                // Prevent hacking from form
                $d->root = $admin['id'];
            } else if ($user_type == 'Sales') {
                $d->root = $root;
            }

            if ($admin['user_type'] === 'SuperAdmin' && ($admin['id']) != $id) {
                Tenant::assignUserTenant($d, $tenantResolved['tenant']);
            } elseif ($admin['user_type'] !== 'SuperAdmin') {
                Tenant::assignUserTenant($d, $tenantResolved['tenant']);
            }

            $d->save();

            $deactivatedUser = ($admin['id'] != $id)
                && isset($_POST['status'])
                && $status === 'Inactive'
                && $previousStatus === 'Active';
            if ($deactivatedUser) {
                Admin::revokeSessions((int) $id);
                _log(
                    '[' . $admin['username'] . ']: ' . Lang::T('Deactivated user account') . ' ' . $username,
                    'Activity',
                    (int) $admin['id']
                );
            } else {
                _log('[' . $admin['username'] . ']: ' . $username . ' ' . Lang::T('User Updated Successfully'), $admin['user_type'], $admin['id']);
            }
            r2(getUrl('settings/users-view/') . $id, 's', 'User Updated Successfully');
        } else {
            r2(getUrl('settings/users-edit/') . $id, 'e', $msg);
        }
        break;

    case 'change-password':
        run_hook('view_change_password'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/change-password.tpl');
        break;

    case 'change-password-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/change-password'), 'e', 'You cannot perform this action in Demo mode');
        }
        $password = _post('password');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/change-password'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        if ($password != '') {
            $d = ORM::for_table('tbl_users')->where('username', $admin['username'])->find_one();
            run_hook('change_password'); #HOOK
            if ($d) {
                $d_pass = $d['password'];
                if (Password::_verify($password, $d_pass) == true) {
                    $npass = _post('npass');
                    $cnpass = _post('cnpass');
                    if (!Validator::Length($npass, 15, 5)) {
                        r2(getUrl('settings/change-password'), 'e', 'New Password must be 6 to 14 character');
                    }
                    if ($npass != $cnpass) {
                        r2(getUrl('settings/change-password'), 'e', 'Both Password should be same');
                    }

                    $npass = Password::_crypt($npass);
                    $d->password = $npass;
                    $d->save();

                    _msglog('s', Lang::T('Password changed successfully, Please login again'));
                    _log('[' . $admin['username'] . ']: Password changed successfully', $admin['user_type'], $admin['id']);

                    r2(getUrl('admin'));
                } else {
                    r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
                }
            } else {
                r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
            }
        } else {
            r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
        }
        break;

    case 'notifications':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        run_hook('view_notifications'); #HOOK
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . "notifications.json")) {
            $ui->assign('_json', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.json'), true));
        } else {
            $ui->assign('_json', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true));
        }

        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_default', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true));
        $ui->display('admin/settings/notifications.tpl');
        break;
    case 'notifications-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/notifications'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/notifications'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        file_put_contents($UPLOAD_PATH . "/notifications.json", json_encode($_POST));
        r2(getUrl('settings/notifications'), 's', Lang::T('Settings Saved Successfully'));
        break;
    case 'hotspot':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $syncHotspotPlansForRouter = function ($targetRouter) {
            $targetRouter = trim((string) $targetRouter);
            if ($targetRouter === '') {
                return 0;
            }

            $validRouterNames = [];
            foreach (settings_scoped_router_query($GLOBALS['admin'])->find_many() as $routerRow) {
                $validRouterNames[] = trim((string) $routerRow['name']);
            }

            $updated = 0;
            $plans = ORM::for_table('tbl_plans')
                ->where('type', 'Hotspot')
                ->where('enabled', 1)
                ->find_many();

            foreach ($plans as $plan) {
                $planRouter = trim((string) $plan['routers']);
                if ($planRouter === $targetRouter) {
                    continue;
                }

                $isOrphanRouter = $planRouter === '' || !in_array($planRouter, $validRouterNames, true);
                $singleRouterSite = count($validRouterNames) === 1;

                if ($isOrphanRouter || $singleRouterSite) {
                    $plan->routers = $targetRouter;
                    $plan->save();
                    $updated++;
                }
            }

            if ($updated > 0) {
                foreach (glob('system/cache/hotspot_plan_*.json') ?: [] as $cacheFile) {
                    @unlink($cacheFile);
                }
            }

            return $updated;
        };

        $hotspotKeys = [
            'hotspot_page_title',
            'hotspot_page_tagline',
            'hotspot_api_url',
            'hotspot_login_router',
            'hotspot_login_color',
            'hotspot_card_shape',
            'hotspot_card_display',
            'hotspot_plan_order',
            'hotspot_banner_text',
            'hotspot_help_title',
            'hotspot_help_text',
            'hotspot_help_whatsapp',
            'hotspot_help_whatsapp_label',
            'hotspot_chat_service',
            'hotspot_name',
            'hotspot_interface',
            'hotspot_profile',
            'hotspot_dns_name',
            'hotspot_pool_mode',
            'hotspot_pool_name',
            'hotspot_pool_range',
            'hotspot_login_methods',
            'hotspot_cookie_lifetime',
            'hotspot_idle_timeout',
            'hotspot_keepalive_timeout',
            'hotspot_smtp_server'
        ];

        $buildHotspotLoginHtml = function () use ($UPLOAD_PATH, &$config) {
            $loginDir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';
            $defaultLoginFile = $loginDir . DIRECTORY_SEPARATOR . 'login.html';
            $templateLoginFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'mikrotik-hotspot-login.html';
            $templateAssetDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';

            if (!is_file($defaultLoginFile) && is_file($templateLoginFile)) {
                if (!is_dir($loginDir)) {
                    @mkdir($loginDir, 0755, true);
                }
                @copy($templateLoginFile, $defaultLoginFile);
                if (is_dir($templateAssetDir)) {
                    foreach (['MTN.png', 'orange.png'] as $assetName) {
                        $src = $templateAssetDir . DIRECTORY_SEPARATOR . $assetName;
                        $dst = $loginDir . DIRECTORY_SEPARATOR . $assetName;
                        if (is_file($src) && !is_file($dst)) {
                            @copy($src, $dst);
                        }
                    }
                }
            }

            if (file_exists($defaultLoginFile) || is_file($templateLoginFile)) {
                $htmlSourceFile = is_file($templateLoginFile) ? $templateLoginFile : $defaultLoginFile;
                $html = file_get_contents($htmlSourceFile);
                if ($html === false) {
                    return null;
                }
                $title = $config['hotspot_page_title'] ?? $config['CompanyName'] ?? 'Hotspot';
                $tagline = trim($config['hotspot_page_tagline'] ?? '');
                $apiUrl = trim($config['hotspot_api_url'] ?? 'https://wifizones.org');
                if ($apiUrl === '') {
                    $apiUrl = 'https://wifizones.org';
                }
                $hotspotRouterName = trim((string) ($config['hotspot_login_router'] ?? ''));
                $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $safeTagline = htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8');

                $finalizeHotspotLoginHtml = function ($html) use ($config, $hotspotRouterName, $apiUrl) {
                    $embeddedPlans = [];
                    try {
                        $plansQuery = ORM::for_table('tbl_plans')
                            ->where('type', 'Hotspot')
                            ->where('enabled', 1);
                        if ($hotspotRouterName !== '') {
                            $plansQuery->where('routers', $hotspotRouterName);
                        }
                        foreach ($plansQuery->find_many() as $plan) {
                            $planId = (int) ($plan['id'] ?? 0);
                            $planName = (string) ($plan['name_plan'] ?? $plan['name'] ?? '');
                            $price = (string) ($plan['price'] ?? '');
                            $validity = trim((string) ($plan['validity'] ?? '') . ' ' . (string) ($plan['validity_unit'] ?? ''));
                            $paymentLink = rtrim($apiUrl, '/') . '/index.php?_route=plugin/hotspot_pay&routername=' . rawurlencode($hotspotRouterName) . '&planid=' . $planId . '&amount=' . rawurlencode($price);
                            $embeddedPlans[] = [
                                'planid' => $planId,
                                'planname' => $planName,
                                'price' => $price,
                                'currency' => $config['currency_code'] ?? 'Fcfa',
                                'validity' => $validity,
                                'paymentlink' => $paymentLink,
                                'routername' => $hotspotRouterName,
                            ];
                        }
                    } catch (Exception $e) {
                        $embeddedPlans = [];
                    }
                    $embeddedPlansJs = 'const HOTSPOT_EMBEDDED_PLANS = ' . json_encode($embeddedPlans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
                    if (strpos($html, 'const HOTSPOT_EMBEDDED_PLANS') === false) {
                        $html = str_replace(
                            'let CLIENT_MAC = \'\';',
                            'let CLIENT_MAC = \'\';' . "\n    " . $embeddedPlansJs,
                            $html
                        );
                    } else {
                        $html = preg_replace('/const HOTSPOT_EMBEDDED_PLANS = .*?;/s', $embeddedPlansJs, $html, 1);
                    }
                    $html = Mikrotik::patchHotspotLoginHelpSection($html, [
                        'title' => $config['hotspot_help_title'] ?? '',
                        'text' => $config['hotspot_help_text'] ?? '',
                        'whatsapp' => $config['hotspot_help_whatsapp'] ?? '',
                        'whatsapp_label' => $config['hotspot_help_whatsapp_label'] ?? '',
                    ]);
                    $html = MobileMoneyGateway::repairHotspotLoginHtml($html);
                    $minLines = array_filter(
                        array_map('ltrim', preg_split('/\r\n|\r|\n/', $html)),
                        static function ($line) {
                            return $line !== '';
                        }
                    );

                    return implode("\n", $minLines);
                };

                $isModernHotspotLogin = strpos($html, 'window.HOTSPOT_INLINE_MD5') !== false
                    && strpos($html, 'function fetchHotspotEndpoint') !== false;

                if ($isModernHotspotLogin) {
                    $html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $safeTitle . ' | Portail Haut Débit</title>', $html, 1);
                    $html = preg_replace('/<span data-hotspot-title>.*?<\/span>/is', '<span data-hotspot-title>' . $safeTitle . '</span>', $html, 1);
                    if ($safeTagline !== '') {
                        $html = preg_replace('/<p\s+data-hotspot-tagline>[^<]*<\/p>/is', '<p data-hotspot-tagline>' . $safeTagline . '</p>', $html, 1);
                    }
                    $taglineHtml = $safeTagline !== '' ? "\n        " . '<p>' . $safeTagline . '</p>' : '';
                    $html = preg_replace('/<div class="hero">\s*<h1>(.*?)<\/h1>\s*(?:<p>.*?<\/p>)?/is', '<div class="hero">' . "\n        " . '<h1>$1</h1>' . $taglineHtml, $html, 1);
                    $html = preg_replace('/const APP_URL = .*?;/s', 'const APP_URL = ' . json_encode(rtrim($apiUrl, '/')) . ';', $html, 1);
                    $routerNameJs = 'const HOTSPOT_ROUTER_NAME = ' . json_encode($hotspotRouterName) . ';';
                    if (preg_match('/const HOTSPOT_ROUTER_NAME = .*?;/s', $html)) {
                        $html = preg_replace('/const HOTSPOT_ROUTER_NAME = .*?;/s', $routerNameJs, $html, 1);
                    } else {
                        $html = preg_replace('/const ROUTER_ID = .*?;/s', 'const ROUTER_ID = 2;' . "\n    " . $routerNameJs, $html, 1);
                    }

                    return $finalizeHotspotLoginHtml($html);
                }

                $html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $safeTitle . ' | Portail Haut Débit</title>', $html, 1);
                if (strpos($html, 'HOTSPOT_INLINE_MD5') === false) {
                    $md5File = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'md5.js';
                    if (file_exists($md5File)) {
                        $md5Code = file_get_contents($md5File);
                        if ($md5Code !== false) {
                            $html = str_replace('</head>', '<script>window.HOTSPOT_INLINE_MD5=true;' . $md5Code . '</script>' . "\n</head>", $html);
                        }
                    }
                }
                $html = preg_replace('/<span data-hotspot-title>.*?<\/span>/is', '<span data-hotspot-title>' . $safeTitle . '</span>', $html, 1);
                $taglineHtml = $safeTagline !== '' ? "\n        " . '<p>' . $safeTagline . '</p>' : '';
                $html = preg_replace('/<div class="hero">\s*<h1>(.*?)<\/h1>\s*(?:<p>.*?<\/p>)?/is', '<div class="hero">' . "\n        " . '<h1>$1</h1>' . $taglineHtml, $html, 1);
                $html = preg_replace('/const APP_URL = .*?;/s', 'const APP_URL = ' . json_encode(rtrim($apiUrl, '/')) . ';', $html, 1);
                $html = preg_replace('/const ROUTER_ID = .*?;/s', 'const ROUTER_ID = 2;', $html, 1);
                $routerNameJs = 'const HOTSPOT_ROUTER_NAME = ' . json_encode($hotspotRouterName) . ';';
                if (preg_match('/const HOTSPOT_ROUTER_NAME = .*?;/s', $html)) {
                    $html = preg_replace('/const HOTSPOT_ROUTER_NAME = .*?;/s', $routerNameJs, $html, 1);
                } else {
                    $html = preg_replace('/const ROUTER_ID = .*?;/s', 'const ROUTER_ID = 2;' . "\n    " . $routerNameJs, $html, 1);
                }
                $embeddedPlans = [];
                try {
                    $plansQuery = ORM::for_table('tbl_plans')
                        ->where('type', 'Hotspot')
                        ->where('enabled', 1);
                    if ($hotspotRouterName !== '') {
                        $plansQuery->where('routers', $hotspotRouterName);
                    }
                    $hotspotPlans = $plansQuery->find_many();
                    foreach ($hotspotPlans as $plan) {
                        $planId = (int) ($plan['id'] ?? 0);
                        $planName = (string) ($plan['name_plan'] ?? $plan['name'] ?? '');
                        $price = (string) ($plan['price'] ?? '');
                        $validity = trim((string) ($plan['validity'] ?? '') . ' ' . (string) ($plan['validity_unit'] ?? ''));
                        $paymentLink = rtrim($apiUrl, '/') . '/index.php?_route=plugin/hotspot_pay&routername=' . rawurlencode($hotspotRouterName) . '&planid=' . $planId . '&amount=' . rawurlencode($price);
                        $embeddedPlans[] = [
                            'planid' => $planId,
                            'planname' => $planName,
                            'price' => $price,
                            'currency' => $config['currency_code'] ?? 'Fcfa',
                            'validity' => $validity,
                            'paymentlink' => $paymentLink,
                            'routername' => $hotspotRouterName,
                        ];
                    }
                } catch (Exception $e) {
                    $embeddedPlans = [];
                }
                $embeddedPlansJs = 'const HOTSPOT_EMBEDDED_PLANS = ' . json_encode($embeddedPlans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
                if (strpos($html, 'const HOTSPOT_EMBEDDED_PLANS') === false) {
                    $html = str_replace(
                        'let CLIENT_MAC = \'\';',
                        'let CLIENT_MAC = \'\';' . "\n    " . $embeddedPlansJs,
                        $html
                    );
                } else {
                    $html = preg_replace('/const HOTSPOT_EMBEDDED_PLANS = .*?;/s', $embeddedPlansJs, $html, 1);
                }
                if (strpos($html, 'function hotspotApiBases') === false) {
                    $html = MobileMoneyGateway::patchHotspotApiBases($html);
                }
                if (strpos($html, 'const HOTSPOT_EMBEDDED_PLANS') === false) {
                    $html = str_replace(
                        'let CLIENT_MAC = \'\';',
                        'let CLIENT_MAC = \'\';' . "\n    " . $embeddedPlansJs,
                        $html
                    );
                }
                $portalActionsHtml = "\n    <div class=\"voucher-section\">\n        <button type=\"button\" id=\"toggleVoucherBtn\" class=\"btn-secondary voucher-toggle\">🎫 J'AI UN CODE</button>\n    </div>\n\n    <div class=\"recover-section\">\n        <button type=\"button\" id=\"recoverPlanBtn\" class=\"btn-secondary\">🔑 RÉCUPÉRER MON FORFAIT</button>\n    </div>\n\n    <div class=\"plans-section\">\n        <div class=\"section-title\">🎯 CHOISISSEZ VOTRE FORFAIT</div>\n        <div id=\"packagesList\" class=\"packages-list\">\n            <div style=\"text-align:center; padding:0.8rem;\">⏳ Chargement des offres...</div>\n        </div>\n    </div>\n\n    <div class=\"divider\">\n        <div class=\"divider-line\"></div>\n        <span class=\"divider-text\">Déjà un compte</span>\n        <div class=\"divider-line\"></div>\n    </div>\n\n    ";
                if (strpos($html, 'voucher-section') === false) {
                    $html = preg_replace(
                        '/\s*<!-- Liste des forfaits -->\s*<div class="plans-section">.*?<div class="divider">.*?<\/div>\s*<!-- Formulaire de connexion -->\s*(<form id="loginForm".*?<\/form>)/is',
                        $portalActionsHtml . '$1',
                        $html,
                        1
                    );
                } else {
                    $html = preg_replace('/\s*<div class="voucher-section">.*?<div class="divider">.*?<\/div>\s*/is', $portalActionsHtml, $html, 1);
                }
                // Normalisation idempotente du séparateur « Déjà un compte ».
                // Les builds précédents (regex non-greedy) laissaient un fragment
                // orphelin à chaque sauvegarde, d'où l'accumulation. On réduit toute
                // la série (séparateur + fragments orphelins) à UN seul séparateur propre.
                $cleanDivider = "<div class=\"divider\">\n<div class=\"divider-line\"></div>\n<span class=\"divider-text\">Déjà un compte</span>\n<div class=\"divider-line\"></div>\n</div>";
                $html = preg_replace(
                    '/<div class="divider">\s*<div class="divider-line"><\/div>\s*<span class="divider-text">Déjà un compte<\/span>\s*<div class="divider-line"><\/div>\s*<\/div>(?:\s*<span class="divider-text">Déjà un compte<\/span>\s*<div class="divider-line"><\/div>\s*<\/div>)*/su',
                    $cleanDivider,
                    $html,
                    1
                );
                $html = str_replace('placeholder="Code voucher / identifiant"', 'placeholder="Identifiant"', $html);
                if (strpos($html, 'HOTSPOT_SWAL_FALLBACK') === false) {
                    $swalFallback = <<<'HTML'
<style>.hotspot-modal-backdrop{position:fixed;inset:0;z-index:99999;background:rgba(2,6,23,.72);display:flex;align-items:center;justify-content:center;padding:18px}.hotspot-modal{width:min(92vw,380px);background:#111827;color:#f8fafc;border:1px solid rgba(16,185,129,.35);border-radius:24px;padding:22px;box-shadow:0 25px 80px rgba(0,0,0,.45);font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}.hotspot-modal h3{margin:0 0 10px;font-size:20px}.hotspot-modal p{margin:0 0 14px;color:#cbd5e1;line-height:1.45}.hotspot-modal input{width:100%;box-sizing:border-box;padding:14px;border-radius:14px;border:1px solid rgba(148,163,184,.28);background:#020617;color:#fff;font-size:16px;outline:none}.hotspot-modal-error{display:none;margin-top:9px;color:#fca5a5;font-size:13px}.hotspot-modal-actions{display:flex;gap:10px;margin-top:18px}.hotspot-modal-actions button{flex:1;padding:12px;border:0;border-radius:999px;font-weight:800}.hotspot-modal-cancel{background:#374151;color:#fff}.hotspot-modal-confirm{background:linear-gradient(135deg,#10b981,#22c55e);color:#021014}</style><script>window.HOTSPOT_SWAL_FALLBACK=true;(function(){if(window.Swal&&typeof window.Swal.fire==='function')return;window.Swal={fire:function(a,b,c){return new Promise(function(resolve){var opts=typeof a==='object'?a:{title:a||'',text:b||'',icon:c||''};var backdrop=document.createElement('div');backdrop.className='hotspot-modal-backdrop';var html=String(opts.html||'').replace(/<script[\s\S]*?<\/script>/gi,'');backdrop.innerHTML='<div class="hotspot-modal"><h3></h3><p></p><div class="hotspot-modal-html"></div><input style="display:none"><div class="hotspot-modal-error"></div><div class="hotspot-modal-actions"><button type="button" class="hotspot-modal-cancel"></button><button type="button" class="hotspot-modal-confirm"></button></div></div>';var box=backdrop.firstChild,title=box.querySelector('h3'),text=box.querySelector('p'),htmlBox=box.querySelector('.hotspot-modal-html'),input=box.querySelector('input'),err=box.querySelector('.hotspot-modal-error'),cancel=box.querySelector('.hotspot-modal-cancel'),ok=box.querySelector('.hotspot-modal-confirm');title.textContent=opts.title||'';text.textContent=opts.text||opts.inputPlaceholder||'';text.style.display=text.textContent?'block':'none';htmlBox.innerHTML=html;if(opts.input){input.style.display='block';input.type=opts.input==='tel'?'tel':'text';input.placeholder=opts.inputPlaceholder||'';if(opts.inputAttributes){Object.keys(opts.inputAttributes).forEach(function(k){input.setAttribute(k,opts.inputAttributes[k]);});}}else{input.parentNode.removeChild(input);}cancel.textContent=opts.cancelButtonText||'Annuler';ok.textContent=opts.confirmButtonText||'OK';cancel.style.display=opts.showCancelButton===false?'none':'block';function close(result){document.body.removeChild(backdrop);resolve(result);}cancel.onclick=function(){close({isConfirmed:false,isDismissed:true,value:input?input.value:null});};ok.onclick=function(){var value=input?input.value:null;if(input&&typeof opts.inputValidator==='function'){var message=opts.inputValidator(value);if(message){err.textContent=message;err.style.display='block';return;}}close({isConfirmed:true,isDismissed:false,value:value});};document.body.appendChild(backdrop);setTimeout(function(){if(input)input.focus();},50);if(opts.timer){setTimeout(function(){if(document.body.contains(backdrop))close({isConfirmed:true,isDismissed:false,value:null});},opts.timer);}});}};})();</script>
HTML;
                    $html = str_replace('</head>', $swalFallback . "\n</head>", $html);
                }
                if (strpos($html, '.btn-secondary') === false) {
                    $html = preg_replace(
                        '/<\/style>/i',
                        ".voucher-section,\n        .recover-section {\n            margin: 16px 0;\n        }\n        .voucher-form {\n            display: none;\n            margin-top: 12px;\n        }\n        .voucher-form.open {\n            display: block;\n        }\n        .btn-secondary {\n            width: 100%;\n            padding: 14px;\n            border-radius: 44px;\n            border: 1px solid rgba(16,185,129,0.35);\n            background: rgba(16,185,129,0.08);\n            color: #a7f3d0;\n            font-weight: 700;\n            cursor: pointer;\n        }\n        .btn-secondary:hover {\n            background: rgba(16,185,129,0.14);\n        }\n        .recover-hint {\n            margin-top: 8px;\n            font-size: 0.72rem;\n            line-height: 1.4;\n            color: #94a3b8;\n            text-align: center;\n        }\n    </style>",
                        $html,
                        1
                    );
                }
                if (strpos($html, 'logHotspotError') === false) {
                    $html = preg_replace(
                        '/\/\* ===== CONNEXION ===== \*\/\s*document\.getElementById\(\'loginBtn\'\)\.addEventListener\(\'click\', \(\) => \{\s*const form = document\.getElementById\(\'loginForm\'\);\s*if \(form\) form\.submit\(\);\s*\}\);/s',
                        "/* ===== CONNEXION ===== */\n    function showVoucherError(message, targetId) {\n        const errDiv = document.getElementById(targetId || 'formError');\n        if (!errDiv) return;\n        errDiv.textContent = message;\n        errDiv.style.opacity = '1';\n        errDiv.style.display = 'block';\n    }\n\n    function fillAndSubmitLogin(username, password) {\n        const form = document.getElementById('loginForm');\n        const usernameInput = document.getElementById('loginUsername');\n        const passwordInput = document.getElementById('loginPassword');\n        if (usernameInput) usernameInput.value = username || '';\n        if (passwordInput) passwordInput.value = password || username || '';\n        if (form) form.submit();\n    }\n\n    function packageHtml(pkg) {\n        if (!pkg) return '';\n        return '<div style=\"text-align:left;margin-top:10px\"><b>Forfait:</b> ' + escapeHtml(pkg.name || '') + '<br><b>Prix:</b> ' + escapeHtml(String(pkg.price || '')) + '<br><b>Validité:</b> ' + escapeHtml(pkg.validity || '') + '</div>';\n    }\n\n    async function validateAndConnect(endpoint, body, errorId) {\n        const response = await fetch(APP_URL + '/index.php?_route=plugin/' + endpoint, {\n            method: 'POST',\n            headers: {'Content-Type': 'application/x-www-form-urlencoded'},\n            body: body\n        });\n        const data = await response.json();\n        if (!data.success) {\n            showVoucherError(data.message || 'Validation impossible', errorId);\n            return;\n        }\n        await Swal.fire({title: data.message || 'Validation réussie', html: packageHtml(data.package) + '<br>Connexion en cours...', icon: 'success', timer: 1800, showConfirmButton: false});\n        fillAndSubmitLogin(data.username, data.password || data.username);\n    }\n\n    function logHotspotError(message, code) {\n        fetch(APP_URL + '/index.php?_route=plugin/hotspot_log', {\n            method: 'POST',\n            headers: {'Content-Type': 'application/x-www-form-urlencoded'},\n            body: 'message=' + encodeURIComponent(message) +\n                '&code=' + encodeURIComponent(code || '') +\n                '&mac=' + encodeURIComponent(CLIENT_MAC || '$(mac)') +\n                '&ip=' + encodeURIComponent('$(ip)') +\n                '&router=' + encodeURIComponent(HOTSPOT_ROUTER_NAME || '$(identity)')\n        }).catch(function() {});\n    }\n\n    document.getElementById('toggleVoucherBtn').addEventListener('click', () => {\n        document.getElementById('voucherForm').classList.toggle('open');\n        const input = document.getElementById('voucherCode');\n        if (input) input.focus();\n    });\n\n    document.getElementById('voucherLoginBtn').addEventListener('click', () => {\n        const voucher = document.getElementById('voucherCode').value.trim();\n        if (!voucher) return showVoucherError('Veuillez saisir votre voucher.', 'voucherError');\n        validateAndConnect('hotspot_voucher_check', 'voucher=' + encodeURIComponent(voucher), 'voucherError').catch(() => showVoucherError('Erreur de validation voucher.', 'voucherError'));\n    });\n\n    document.getElementById('recoverPlanBtn').addEventListener('click', () => {\n        document.getElementById('recoverForm').classList.toggle('open');\n        const input = document.getElementById('recoverPhone');\n        if (input) input.focus();\n    });\n\n    document.getElementById('recoverSubmitBtn').addEventListener('click', () => {\n        const phone = document.getElementById('recoverPhone').value.replace(/\\D/g, '');\n        if (phone.length !== 9) return showVoucherError('Le numéro doit contenir 9 chiffres.', 'recoverError');\n        validateAndConnect('hotspot_recover_plan', 'phone=' + encodeURIComponent(phone), 'recoverError').catch(() => showVoucherError('Erreur de récupération.', 'recoverError'));\n    });\n\n    document.getElementById('loginBtn').addEventListener('click', () => {\n        const username = document.getElementById('loginUsername').value.trim();\n        const password = document.getElementById('loginPassword').value.trim();\n        if (!username || !password) return showVoucherError('Identifiant et mot de passe requis.', 'formError');\n        validateAndConnect('hotspot_account_check', 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password), 'formError').catch(() => showVoucherError('Erreur de validation compte.', 'formError'));\n    });",
                        $html,
                        1
                    );
                    $html = str_replace(
                        "errDiv.style.display = 'block';",
                        "errDiv.style.display = 'block';\n                logHotspotError(errorMsg, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                        $html
                    );
                }
                $html = preg_replace(
                    "/document\\.getElementById\\('toggleVoucherBtn'\\)\\.addEventListener\\('click', \\(\\) => \\{.*?\\n    \\}\\);/s",
                    "document.getElementById('toggleVoucherBtn').addEventListener('click', async () => {\n        const result = await Swal.fire({\n            title: \"🎫 J'AI UN CODE\",\n            input: 'text',\n            inputPlaceholder: 'Entrez votre code voucher',\n            showCancelButton: true,\n            confirmButtonText: 'Valider',\n            cancelButtonText: 'Annuler',\n            inputValidator: (value) => !value || !value.trim() ? 'Veuillez saisir votre voucher.' : undefined\n        });\n        if (result.isConfirmed) {\n            validateAndConnect('hotspot_voucher_check', 'voucher=' + encodeURIComponent(result.value.trim()), 'formError').catch(() => Swal.fire('Erreur', 'Erreur de validation voucher.', 'error'));\n        }\n    });",
                    $html,
                    1
                );
                $html = preg_replace(
                    "/document\\.getElementById\\('voucherLoginBtn'\\)\\.addEventListener\\('click', \\(\\) => \\{.*?\\n    \\}\\);\\s*/s",
                    "",
                    $html,
                    1
                );
                $html = preg_replace(
                    "/document\\.getElementById\\('recoverPlanBtn'\\)\\.addEventListener\\('click', \\(\\) => \\{.*?\\n    \\}\\);/s",
                    "document.getElementById('recoverPlanBtn').addEventListener('click', async () => {\n        const result = await Swal.fire({\n            title: '🔑 RÉCUPÉRER MON FORFAIT',\n            input: 'tel',\n            inputPlaceholder: 'Numéro de téléphone (9 chiffres)',\n            inputAttributes: {maxlength: 9, inputmode: 'numeric'},\n            showCancelButton: true,\n            confirmButtonText: 'Récupérer',\n            cancelButtonText: 'Annuler',\n            inputValidator: (value) => {\n                const phone = String(value || '').replace(/\\D/g, '');\n                if (phone.length !== 9) return 'Le numéro doit contenir 9 chiffres.';\n                return undefined;\n            }\n        });\n        if (result.isConfirmed) {\n            const phone = String(result.value || '').replace(/\\D/g, '');\n            validateAndConnect('hotspot_recover_plan', 'phone=' + encodeURIComponent(phone), 'formError').catch(() => Swal.fire('Erreur', 'Erreur de récupération.', 'error'));\n        }\n    });",
                    $html,
                    1
                );
                $html = preg_replace(
                    "/document\\.getElementById\\('recoverSubmitBtn'\\)\\.addEventListener\\('click', \\(\\) => \\{.*?\\n    \\}\\);\\s*/s",
                    "",
                    $html,
                    1
                );
                $html = preg_replace(
                    "/body:\\s*'routername=' \\+ encodeURIComponent\\('\\$\\(identity\\)'\\)/s",
                    "body: 'routername=' + encodeURIComponent((typeof HOTSPOT_ROUTER_NAME !== 'undefined' && HOTSPOT_ROUTER_NAME) ? HOTSPOT_ROUTER_NAME : '$(identity)')",
                    $html,
                    1
                );
                $html = str_replace(
                    "fetch(APP_URL + '/index.php?_route=plugin/hotspot_plan', {",
                    "fetchHotspotEndpoint('hotspot_plan', {",
                    $html
                );
                $html = str_replace(
                    "fetch(APP_URL + '/index.php?_route=plugin/' + endpoint, {",
                    "fetchHotspotEndpoint(endpoint, {",
                    $html
                );
                $html = str_replace(
                    "async function validateAndConnect(endpoint, body, errorId) {\n        const response = await fetchHotspotEndpoint(endpoint, {",
                    "async function validateAndConnect(endpoint, body, errorId) {\n        const response = await fetchHotspotEndpoint(endpoint, {",
                    $html
                );
                $html = str_replace(
                    "logHotspotError(errorMsg, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    "logHotspotError(message, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    $html
                );
                $html = str_replace(
                    "logHotspotError(message, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    "logHotspotError(errorMsg, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    $html
                );
                $html = str_replace(
                    "errDiv.style.display = 'block';\n                logHotspotError(errorMsg, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    "errDiv.style.display = 'block';\n        if (typeof message !== 'undefined') logHotspotError(message, document.getElementById('loginUsername') ? document.getElementById('loginUsername').value : '');",
                    $html
                );
                $html = preg_replace(
                    "/(function showVoucherError\\(message, targetId\\) \\{.*?errDiv\\.style\\.display = 'block';\\s*)logHotspotError\\(errorMsg,/s",
                    "$1logHotspotError(message,",
                    $html,
                    1
                );
                $html = preg_replace(
                    "/(\\(function showMikrotikError\\(\\) \\{.*?errDiv\\.style\\.display = 'block';\\s*)logHotspotError\\(message,/s",
                    "$1logHotspotError(errorMsg,",
                    $html,
                    1
                );
                if (strpos($html, 'function prepareMikrotikLogin') === false) {
                    $html = str_replace(
                        '/* ===== CONNEXION ===== */',
                        "/* ===== CONNEXION ===== */\n    function prepareMikrotikLogin(form) {\n        if (!form) return false;\n        const passwordInput = form.querySelector('input[name=\"password\"]');\n        const chapId = '$(chap-id)';\n        const chapChallenge = '$(chap-challenge)';\n        const hasChap = chapId && chapChallenge && chapId.indexOf('$(') !== 0 && chapChallenge.indexOf('$(') !== 0;\n        if (hasChap && passwordInput && !passwordInput.dataset.chapDone) {\n            if (typeof hexMD5 === 'function') {\n                passwordInput.value = hexMD5(chapId + passwordInput.value + chapChallenge);\n                passwordInput.dataset.chapDone = '1';\n            }\n        }\n        return true;\n    }",
                        $html
                    );
                }
                $html = preg_replace(
                    "/document\\.getElementById\\('loginBtn'\\)\\.addEventListener\\('click', \\(\\) => \\{\\s*const form = document\\.getElementById\\('loginForm'\\);\\s*if \\(form\\) form\\.submit\\(\\);\\s*\\}\\);/s",
                    "document.getElementById('loginBtn').addEventListener('click', () => {\n        const form = document.getElementById('loginForm');\n        if (form && prepareMikrotikLogin(form)) form.submit();\n    });\n    const loginFormEl = document.getElementById('loginForm');\n    if (loginFormEl) {\n        loginFormEl.addEventListener('submit', function(event) {\n            if (!prepareMikrotikLogin(loginFormEl)) event.preventDefault();\n        });\n    }",
                    $html,
                    1
                );
                $html = str_replace(
                    'if (form) form.submit();',
                    'if (form && prepareMikrotikLogin(form)) form.submit();',
                    $html
                );
                $html = MobileMoneyGateway::repairHotspotLoginHtml($html);
                return $finalizeHotspotLoginHtml($html);
            }
            return null;
        };

        if (!empty(_get('download_login'))) {
            $renderedLoginHtml = $buildHotspotLoginHtml();
            if ($renderedLoginHtml !== null) {
                header('Content-Type: text/html; charset=utf-8');
                header('Content-Disposition: attachment; filename="login.html"');
                echo $renderedLoginHtml;
                exit;
            }
            $title = $config['hotspot_page_title'] ?? $config['CompanyName'] ?? 'Hotspot';
            $tagline = $config['hotspot_page_tagline'] ?? 'Connect to the internet';
            $apiUrl = getUrl('plugin/hotspot_plan');
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                . '</title><style>'
                . 'body{margin:0;font-family:Inter,Arial,sans-serif;background:#030712;color:#f8fafc;min-height:100vh;display:flex;align-items:center;justify-content:center}'
                . '.card{width:min(92%,430px);background:rgba(15,23,42,.86);border:1px solid rgba(148,163,184,.2);border-radius:24px;padding:28px;box-shadow:0 30px 80px rgba(0,0,0,.35)}'
                . 'h1{margin:0 0 8px;font-size:28px;font-weight:900}p{color:#94a3b8;margin:0 0 16px}'
                . '.input{width:100%;padding:13px;margin:8px 0;border-radius:14px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#fff;box-sizing:border-box}'
                . '.btn{width:100%;padding:13px;margin-top:12px;border:0;border-radius:999px;background:linear-gradient(135deg,#22c55e,#06b6d4);color:#021014;font-weight:700;cursor:pointer}'
                . '.packages{display:grid;gap:10px;margin:16px 0}'
                . 'a.pkg{display:block;padding:12px;border:1px solid rgba(255,255,255,.14);border-radius:14px;background:rgba(255,255,255,.07);color:inherit;text-decoration:none;transition:.15s}'
                . 'a.pkg:hover{border-color:rgba(34,197,94,.55);background:rgba(34,197,94,.12)}'
                . 'a.pkg b{display:block;font-size:14px;margin-bottom:4px}'
                . '.pkg-hint{font-size:11px;color:#94a3b8;margin:0 0 10px}'
                . '</style></head><body><div class="card"><h1>'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                . '</h1><p>'
                . htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8')
                . '</p><p class="pkg-hint">Choisissez un forfait pour payer et activer votre accès.</p>'
                . '<div id="packages" class="packages">Chargement des forfaits...</div>'
                . '<form name="login" action="$(link-login-only)" method="post">'
                . '<input type="hidden" name="dst" value="$(link-orig)"><input type="hidden" name="popup" value="true">'
                . '<input class="input" name="username" type="text" placeholder="Code voucher / identifiant">'
                . '<input class="input" name="password" type="password" placeholder="Mot de passe">'
                . '<button class="btn" type="submit">Se connecter</button></form></div><script>'
                . 'fetch(' . json_encode($apiUrl) . ',{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"routername="+encodeURIComponent("$(identity)")})'
                . '.then(function(r){return r.json()})'
                . '.then(function(j){var box=document.getElementById("packages");'
                . 'if(!j.data||!j.data.length){box.innerHTML="<div class=\"pkg\">Aucun forfait actif pour ce routeur.</div>";return}'
                . 'box.innerHTML=j.data.map(function(p){'
                . 'var url=p.paymentlink+(p.paymentlink.indexOf("?")>=0?"&":"?")+"mac=$(mac)&ip=$(ip)";'
                . 'return "<a class=\\\"pkg\\\" href=\\\""+url+"\\\"><b>"+p.planname+"</b>"+p.currency+" "+p.price+" &mdash; "+p.validity+"</a>"'
                . '}).join("")})'
                . '.catch(function(){document.getElementById("packages").innerHTML="Forfaits indisponibles (vérifiez l\'URL du serveur)."})'
                . '</script></body></html>';
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="login.html"');
            echo $html;
            exit;
        }

        $saveHotspotSettings = function () use ($hotspotKeys, &$config) {
            foreach ($hotspotKeys as $key) {
                $value = $key == 'hotspot_login_methods' ? implode(',', $_POST[$key] ?? []) : ($_POST[$key] ?? '');
                // Wizard step 3 hides the router field; an empty POST must not wipe the saved router.
                if ($key === 'hotspot_login_router' && trim((string) $value) === '') {
                    continue;
                }
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
                $config[$key] = $value;
            }
        };

        $writeHotspotLoginHtml = function () use ($buildHotspotLoginHtml, $UPLOAD_PATH) {
            $renderedLoginHtml = $buildHotspotLoginHtml();
            if ($renderedLoginHtml === null) {
                return false;
            }
            $loginDir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';
            if (!is_dir($loginDir)) {
                @mkdir($loginDir, 0755, true);
            }
            return file_put_contents($loginDir . DIRECTORY_SEPARATOR . 'login.html', $renderedLoginHtml) !== false;
        };

        if (_post('save') == 'save') {
            $saveHotspotSettings();
            $selectedRouter = trim((string) ($config['hotspot_login_router'] ?? ''));
            $syncedPlans = 0;
            if ($selectedRouter !== '') {
                $syncedPlans = $syncHotspotPlansForRouter($selectedRouter);
            }
            $writeHotspotLoginHtml();
            $planCount = 0;
            if ($selectedRouter !== '') {
                $planCount = (int) ORM::for_table('tbl_plans')
                    ->where('type', 'Hotspot')
                    ->where('enabled', 1)
                    ->where('routers', $selectedRouter)
                    ->count();
            }
            if ($selectedRouter !== '' && $planCount === 0) {
                r2(
                    getUrl('settings/hotspot'),
                    'w',
                    'Paramètres enregistrés, mais aucun forfait Hotspot actif pour « '
                    . $selectedRouter
                    . ' ». Créez ou activez des forfaits Hotspot assignés à ce routeur.'
                );
            }
            if ($syncedPlans > 0) {
                r2(
                    getUrl('settings/hotspot'),
                    's',
                    Lang::T('Settings Saved Successfully')
                    . ' — '
                    . $syncedPlans
                    . ' forfait(s) Hotspot réassigné(s) au routeur « '
                    . $selectedRouter
                    . ' ».'
                );
            }
            r2(getUrl('settings/hotspot'), 's', Lang::T('Settings Saved Successfully'));
        }

        if (_post('send_mikrotik') == '1') {
            @set_time_limit(120);
            @ini_set('max_execution_time', '120');
            @ini_set('default_socket_timeout', '30');
            @ignore_user_abort(true);
            $saveHotspotSettings();
            $routerName = trim((string) ($config['hotspot_login_router'] ?? ''));
            if ($routerName === '') {
                $savedRouter = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_login_router')->find_one();
                $routerName = trim((string) ($savedRouter['value'] ?? ''));
                if ($routerName !== '') {
                    $config['hotspot_login_router'] = $routerName;
                }
            }
            if ($routerName === '') {
                r2(
                    getUrl('settings/hotspot'),
                    'e',
                    'Aucun routeur sélectionné. À l\'étape 1 de l\'assistant, choisissez votre routeur (ex. MK) ou ajoutez un routeur dans Réseau → Routeurs.'
                );
            }
            if ($routerName !== '') {
                $syncHotspotPlansForRouter($routerName);
            }
            $renderedLoginHtml = $buildHotspotLoginHtml();
            if ($renderedLoginHtml === null) {
                r2(getUrl('settings/hotspot'), 'e', 'Échec de l\'envoi vers MikroTik : fichier login.html introuvable');
            }
            $loginDir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';
            if (!is_dir($loginDir)) {
                @mkdir($loginDir, 0755, true);
            }
            file_put_contents($loginDir . DIRECTORY_SEPARATOR . 'login.html', $renderedLoginHtml);
            $mikrotik = null;
            if ($routerName !== '') {
                $mikrotik = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
                if (!$mikrotik) {
                    $mikrotik = ORM::for_table('tbl_routers')->where('description', $routerName)->find_one();
                }
                if (!$mikrotik) {
                    $routerIp = explode(':', $routerName)[0];
                    if ($routerIp !== '') {
                        $mikrotik = ORM::for_table('tbl_routers')->where_like('ip_address', $routerIp . '%')->find_one();
                    }
                }
            }
            if (!$mikrotik) {
                r2(getUrl('settings/hotspot'), 'e', 'Échec de l\'envoi vers MikroTik : ' . Lang::T('Router not found') . ' (' . ($routerName !== '' ? $routerName : 'aucun routeur sélectionné') . ')');
            }
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/hotspot'), 'e', 'You cannot perform this action in Demo mode');
            }
            if (strlen($renderedLoginHtml) > 4000) {
                $apiUrlForCheck = trim((string) ($config['hotspot_api_url'] ?? ''));
                $routerFetchUrls = Mikrotik::buildHotspotLoginFetchUrls($apiUrlForCheck, APP_URL);
                if (empty($routerFetchUrls)) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'login.html fait '
                        . strlen($renderedLoginHtml)
                        . ' octets : le routeur doit le télécharger via HTTP. Renseignez Hotspot API URL avec l\'IP LAN du Mac joignable par le MikroTik (ex. http://10.0.0.2:8080 — pas localhost ni l\'IP du routeur).'
                    );
                }
            }
            try {
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password'], 60);
                if (!$client) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Connexion MikroTik impossible : compte démo ou synchronisation routeur désactivée pour cet utilisateur.'
                    );
                }
                if (($config['hotspot_pool_mode'] ?? 'new') !== 'existing') {
                    $poolName = $config['hotspot_pool_name'] ?: 'hs-pool';
                    $poolRange = $config['hotspot_pool_range'] ?: '10.5.50.2-10.5.50.254';
                    Mikrotik::setPool($client, $poolName, $poolRange);
                }
                $fetchTs = time();
                $apiUrlForFetch = Mikrotik::normalizeHotspotBackendApiUrl(trim((string) ($config['hotspot_api_url'] ?? '')));
                if ($apiUrlForFetch === '') {
                    r2(getUrl('settings/hotspot'), 'e', 'Hotspot API URL requise (ex. http://10.0.0.1 pour le VPS WireGuard, port 80).');
                }
                $wgResult = Mikrotik::ensureHotspotWalledGarden($client, $apiUrlForFetch);
                if (empty($wgResult['ok'])) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Échec walled-garden : ' . implode(' | ', $wgResult['errors'] ?? ['erreur inconnue'])
                    );
                }
                foreach (['wifizones.org', 'www.wifizones.org'] as $wgHost) {
                    $apiHostCheck = parse_url($apiUrlForFetch, PHP_URL_HOST);
                    if ($apiHostCheck !== $wgHost) {
                        Mikrotik::ensureHotspotWalledGarden($client, 'https://' . $wgHost);
                    }
                }
                $wgExtras = Mikrotik::ensureHotspotCaptiveExtrasWalledGarden($client, APP_URL);
                if (empty($wgExtras['ok'])) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Walled-garden WhatsApp / site public : ' . implode(' | ', $wgExtras['errors'] ?? ['erreur inconnue'])
                    );
                }
                $apiHostForDns = parse_url($apiUrlForFetch, PHP_URL_HOST);
                $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
                if ($dnsName !== '' && $apiHostForDns && filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    $dnsResult = Mikrotik::ensureHotspotDnsStatic($client, $dnsName, $apiHostForDns);
                    if (empty($dnsResult['ok'])) {
                        r2(
                            getUrl('settings/hotspot'),
                            'e',
                            'DNS statique hotspot non configuré (' . $dnsName . ' → ' . $apiHostForDns . ') : '
                            . implode(' | ', $dnsResult['errors'] ?? ['erreur inconnue'])
                        );
                    }
                }
                $apiPort = (int) (parse_url($apiUrlForFetch, PHP_URL_PORT) ?: ((parse_url($apiUrlForFetch, PHP_URL_SCHEME) === 'https') ? 443 : 80));
                $captiveApiUrl = rtrim($apiUrlForFetch, '/');
                $hotspotServerName = trim((string) ($config['hotspot_name'] ?? ''));
                $hotspotListenIp = Mikrotik::getHotspotServerAddress($client, $hotspotServerName);
                if ($hotspotListenIp === '') {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'IP du serveur hotspot introuvable sur le MikroTik. Vérifiez /ip hotspot print (interface) '
                        . 'et /ip address print. Nom hotspot configuré : « '
                        . ($hotspotServerName !== '' ? $hotspotServerName : 'vide')
                        . ' » — doit correspondre à la colonne NAME dans /ip hotspot print.'
                    );
                }
                if ($apiHostForDns && filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    $natResult = Mikrotik::ensureHotspotApiNatProxy($client, $hotspotListenIp, $apiHostForDns, $apiPort);
                    if (empty($natResult['ok']) || empty($natResult['captive_url'])) {
                        r2(
                            getUrl('settings/hotspot'),
                            'e',
                            'Proxy NAT hotspot API échoué : ' . implode(' | ', $natResult['errors'] ?? ['erreur inconnue'])
                            . '. Test routeur : /tool fetch url="http://10.0.0.1/index.php?_route=plugin/hotspot_plan" mode=http'
                        );
                    }
                    $captiveApiUrl = $natResult['captive_url'];
                } elseif (!filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    foreach (['wifizones.org', 'www.wifizones.org'] as $wgHost) {
                        if ($apiHostForDns !== $wgHost) {
                            Mikrotik::ensureHotspotWalledGarden($client, 'https://' . $wgHost);
                        }
                    }
                }
                try {
                    $renderedLoginHtml = Mikrotik::patchHotspotLoginCaptiveApi($renderedLoginHtml, $captiveApiUrl, $dnsName, APP_URL);
                    $uploadId = 'DYRSIA_UPLOAD_' . date('YmdHis', $fetchTs);
                    $renderedLoginHtml = preg_replace('/<!--\s*DYRSIA_UPLOAD_[^>]*-->\s*/', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                    $renderedLoginHtml = preg_replace('/<meta\s+name=["\']dyrsia-generated-at["\'][^>]*>\s*/i', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                    $renderedLoginHtml = str_replace('</head>', '<meta name="dyrsia-generated-at" content="' . date('c', $fetchTs) . '">' . "\n" . '<!-- ' . $uploadId . ' -->' . "\n" . '</head>', $renderedLoginHtml);
                    file_put_contents($loginDir . DIRECTORY_SEPARATOR . 'login.html', $renderedLoginHtml);
                } catch (Throwable $e) {
                    r2(getUrl('settings/hotspot'), 'e', 'Erreur traitement HTML: ' . $e->getMessage());
                }
                $loginFetchUrls = Mikrotik::buildHotspotLoginFetchUrls($apiUrlForFetch, APP_URL, $fetchTs);
                if (empty($loginFetchUrls)) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Hotspot API URL invalide pour le routeur. Utilisez l’IP LAN de ce Mac, ex. http://192.168.1.240:8080 (pas localhost).'
                    );
                }
                $fetchPreflight = Mikrotik::verifyHotspotFetchUrl($loginFetchUrls[0], 6);
                if ($fetchPreflight !== true) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Échec de l\'envoi vers MikroTik : ' . $fetchPreflight
                        . '. Vérifiez que le serveur écoute sur 0.0.0.0 et que le pare-feu autorise le port 8080.'
                    );
                }
                $fetchBases = array_values(array_unique(array_filter([
                    rtrim($apiUrlForFetch, '/'),
                    rtrim(APP_URL, '/'),
                ], function ($fetchBase) {
                    return Mikrotik::isRouterFetchableUrl($fetchBase);
                })));
                $assetFetchUrlsByName = [];
                $assetFetchBase = $fetchBases[0] ?? '';
                if ($assetFetchBase !== '') {
                    foreach (['MTN.png', 'orange.png'] as $assetName) {
                        $assetUrl = $assetFetchBase . '/system/uploads/mikrotik_hotspot/' . rawurlencode($assetName) . '?ts=' . $fetchTs;
                        if (Mikrotik::isRouterFetchableUrl($assetUrl)) {
                            $assetFetchUrlsByName[$assetName] = [$assetUrl];
                        }
                    }
                }

                $deployResult = Mikrotik::deployHotspotLoginHtml($client, $renderedLoginHtml, $loginFetchUrls);
                if (empty($deployResult['ok'])) {
                    $sendErrors = $deployResult['errors'] ?? ['échec inconnu'];
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Échec de l\'envoi vers MikroTik : login.html non envoyé (' . implode(' | ', $sendErrors)
                        . '). Vérifiez que le MikroTik peut joindre '
                        . ($fetchBases[0] ?? 'votre serveur')
                        . ' (HTTP/HTTPS, pas le port dev 8080). Test routeur : /tool fetch url="'
                        . ($loginFetchUrls[0] ?? '')
                        . '" dst-path=hotspot/login.html mode=http'
                    );
                }
                $sentPath = (string) ($deployResult['path'] ?? 'hotspot/login.html');
                $deployMethod = (string) ($deployResult['method'] ?? 'api');
                $routerLoginSize = Mikrotik::getRouterFileSize($client, $sentPath);
                if ($routerLoginSize <= 0) {
                    r2(
                        getUrl('settings/hotspot'),
                        'e',
                        'Échec de l\'envoi vers MikroTik : ' . $sentPath . ' absent après envoi. Vérifiez /file print sur le routeur.'
                    );
                }

                $hotspotAssetDir = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot';
                foreach (['MTN.png', 'orange.png'] as $assetName) {
                    try {
                        $assetSource = $hotspotAssetDir . DIRECTORY_SEPARATOR . $assetName;
                        if (!is_file($assetSource)) {
                            continue;
                        }
                        $assetBinary = file_get_contents($assetSource);
                        if ($assetBinary === false) {
                            continue;
                        }
                        $assetFetchUrls = $assetFetchUrlsByName[$assetName] ?? [];
                        Mikrotik::deployHotspotAssetFile($client, $assetName, $assetBinary, $assetFetchUrls);
                    } catch (Throwable $e) {
                        // Ignore asset deployment errors - login.html is the critical file
                    }
                }
                $dnsNote = '';
                if ($dnsName !== '' && !empty($apiHostForDns) && filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    $dnsNote = ' DNS ' . $dnsName . ' → ' . $apiHostForDns . '.';
                }
                $captiveNote = ($captiveApiUrl !== rtrim($apiUrlForFetch, '/'))
                    ? ' API captive : ' . $captiveApiUrl . ' (proxy NAT vers ' . $apiHostForDns . ').'
                    : '';
                r2(
                    getUrl('settings/hotspot'),
                    's',
                    'Envoi réussi vers MikroTik (' . $routerName . ') : pool, '
                    . $sentPath
                    . ' (' . $deployMethod . ', ' . $routerLoginSize . ' octets), walled-garden OK.'
                    . ' ID: ' . $uploadId . '.'
                    . $dnsNote
                    . $captiveNote
                );
            } catch (Throwable $e) {
                r2(getUrl('settings/hotspot'), 'e', 'Échec de l\'envoi vers MikroTik (' . $routerName . ') : ' . $e->getMessage());
            } catch (Exception $e) {
                r2(getUrl('settings/hotspot'), 'e', 'Échec de l\'envoi vers MikroTik (' . $routerName . ') : ' . $e->getMessage());
            }
        }

        $routers = settings_scoped_router_query($admin)->order_by_asc('name')->find_many();
        $writeHotspotLoginHtml();
        MobileMoneyGateway::syncHotspotCaptivePaymentUi();
        $ui->assign('_title', Lang::T('Hotspot Settings'));
        $ui->assign('routers', $routers);
        $ui->assign('hs_api_suggested', rtrim(APP_URL, '/'));
        $ui->assign(
            'hs_walled_garden_script',
            Mikrotik::hotspotCaptiveWalledGardenRouterOsScript(
                APP_URL,
                trim((string) ($config['hotspot_api_url'] ?? ''))
            )
        );
        $ui->assign('_c', $config);
        $ui->display('admin/settings/hotspot.tpl');
        break;
    case 'dbstatus':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $dbc = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($result = $dbc->query('SHOW TABLE STATUS')) {
            $tables = array();
            while ($row = $result->fetch_array()) {
                $tables[$row['Name']]['rows'] = ORM::for_table($row["Name"])->count();
                $tables[$row['Name']]['name'] = $row["Name"];
            }
            $ui->assign('tables', $tables);
            run_hook('view_database'); #HOOK
            $ui->display('admin/settings/dbstatus.tpl');
        }
        break;

    case 'dbbackup':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!in_array($admin['user_type'], ['SuperAdmin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $tables = $_POST['tables'];
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename="phpwifizones_' . count($tables) . '_tables_' . date('Y-m-d_H_i') . '.json"');
        header('Content-Transfer-Encoding: binary');
        $array = [];
        foreach ($tables as $table) {
            $array[$table] = ORM::for_table($table)->find_array();
        }
        echo json_encode($array);
        break;
    case 'dbrestore':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!in_array($admin['user_type'], ['SuperAdmin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if (file_exists($_FILES['json']['tmp_name'])) {
            $suc = 0;
            $fal = 0;
            $json = json_decode(file_get_contents($_FILES['json']['tmp_name']), true);
            try {
                ORM::raw_execute("SET FOREIGN_KEY_CHECKS=0;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                ORM::raw_execute("SET GLOBAL FOREIGN_KEY_CHECKS=0;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            foreach ($json as $table => $records) {
                ORM::raw_execute("TRUNCATE $table;");
                foreach ($records as $rec) {
                    try {
                        $t = ORM::for_table($table)->create();
                        foreach ($rec as $k => $v) {
                            $t->set($k, $v);
                        }
                        if ($t->save()) {
                            $suc++;
                        } else {
                            $fal++;
                        }
                    } catch (Throwable $e) {
                        $fal++;
                    } catch (Exception $e) {
                        $fal++;
                    }
                }
            }
            try {
                ORM::raw_execute("SET FOREIGN_KEY_CHECKS=1;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                ORM::raw_execute("SET GLOBAL FOREIGN_KEY_CHECKS=1;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            if (file_exists($_FILES['json']['tmp_name']))
                unlink($_FILES['json']['tmp_name']);
            r2(getUrl('settings/dbstatus'), 's', "Restored $suc success $fal failed");
        } else {
            r2(getUrl('settings/dbstatus'), 'e', 'Upload failed');
        }
        break;
    case 'language':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        run_hook('view_add_language'); #HOOK
        if (file_exists($lan_file)) {
            $ui->assign('langs', json_decode(file_get_contents($lan_file), true));
        } else {
            $ui->assign('langs', []);
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/language-add.tpl');
        break;

    case 'lang-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/language'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        file_put_contents($lan_file, json_encode($_POST, JSON_PRETTY_PRINT));
        r2(getUrl('settings/language'), 's', Lang::T('Translation saved Successfully'));
        break;

    case 'maintenance':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (_post('save') == 'save') {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/maintenance'), 'e', 'You cannot perform this action in Demo mode');
            }
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/maintenance'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            $status = isset($_POST['maintenance_mode']) ? 1 : 0; // Checkbox returns 1 if checked, otherwise 0
            $force_logout = isset($_POST['maintenance_mode_logout']) ? 1 : 0; // Checkbox returns 1 if checked, otherwise 0
            $date = isset($_POST['maintenance_date']) ? $_POST['maintenance_date'] : null;

            $settings = [
                'maintenance_mode' => $status,
                'maintenance_mode_logout' => $force_logout,
                'maintenance_date' => $date
            ];

            foreach ($settings as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }

            r2(getUrl('settings/maintenance'), 's', Lang::T('Settings Saved Successfully'));
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_c', $config);
        $ui->assign('_title', Lang::T('Maintenance Mode Settings'));
        $ui->display('admin/settings/maintenance-mode.tpl');
        break;

    case 'miscellaneous':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }
        if (_post('save') == 'save') {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/miscellaneous'), 'e', 'You cannot perform this action in Demo mode');
            }
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/miscellaneous'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            foreach ($_POST as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }

            r2(getUrl('settings/miscellaneous'), 's', Lang::T('Settings Saved Successfully'));
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_c', $config);
        $ui->assign('_title', Lang::T('Miscellaneous Settings'));
        $ui->display('admin/settings/miscellaneous.tpl');
        break;

    default:
        $ui->display('admin/404.tpl');
}
