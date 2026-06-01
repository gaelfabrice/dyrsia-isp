<?php

_admin();
if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_customer_registration_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    instance_name VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    otp_code VARCHAR(10) DEFAULT NULL,
    otp_expires_at DATETIME DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    status VARCHAR(30) DEFAULT 'pending_email',
    trial_expires_at DATETIME DEFAULT NULL,
    license_period VARCHAR(30) DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $routes['1'] ?? 'list';

if ($action == 'approve') {
    $id = intval($routes['2'] ?? 0);
    $request = ORM::for_table('tbl_customer_registration_requests')->find_one($id);
    if (!$request || $request->status != 'pending_approval') {
        r2(getUrl('registration_requests'), 'e', Lang::T('Request not ready for approval'));
    }
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->instance_name));
    if ($username == '') {
        $username = strtolower(strtok($request->email, '@'));
    }
    $baseUsername = $username;
    $i = 1;
    while (ORM::for_table('tbl_customers')->where('username', $username)->find_one()) {
        $username = $baseUsername . $i;
        $i++;
    }
    $customer = ORM::for_table('tbl_customers')->create();
    $customer->username = $username;
    $pwd = $request->password;
    $customer->password = (strlen($pwd) === 40 && preg_match('/^[a-f0-9]{40}$/i', $pwd))
        ? $pwd
        : Password::_crypt($pwd);
    $customer->fullname = trim($request->first_name . ' ' . $request->last_name);
    $customer->email = $request->email;
    $customer->phonenumber = $request->phone;
    $customer->address = $request->address;
    $customer->city = $request->city;
    $customer->state = $request->country;
    $customer->account_type = 'Personal';
    $customer->service_type = 'Hotspot';
    $customer->status = 'Active';
    $customer->created_by = $admin['id'];
    $customer->created_at = date('Y-m-d H:i:s');
    $customer->save();

    $request->status = 'approved_trial';
    $request->trial_expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    $request->customer_id = $customer->id();
    $request->updated_at = date('Y-m-d H:i:s');
    $request->save();

    Message::sendEmail($request->email, Lang::T('Your account has been approved'), '<p>Your account has been approved.</p><p>Username: <b>' . $username . '</b></p><p>Your free trial is valid for 7 days.</p>');
    r2(getUrl('registration_requests'), 's', Lang::T('Request approved and customer created'));
}

if ($action == 'reject') {
    $id = intval($routes['2'] ?? 0);
    $request = ORM::for_table('tbl_customer_registration_requests')->find_one($id);
    if ($request) {
        $request->status = 'rejected';
        $request->updated_at = date('Y-m-d H:i:s');
        $request->save();
    }
    r2(getUrl('registration_requests'), 's', Lang::T('Request rejected'));
}

$requests = ORM::for_table('tbl_customer_registration_requests')->order_by_desc('created_at')->find_many();
$ui->assign('_title', Lang::T('Registration Requests'));
$ui->assign('_system_menu', 'registration_requests');
$ui->assign('requests', $requests);
$ui->display('admin/registration_requests.tpl');
