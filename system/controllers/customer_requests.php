<?php

function ensure_customer_requests_table()
{
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
}

ensure_customer_requests_table();
$do = $routes['1'] ?? 'form';

switch ($do) {
    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            r2(getUrl('customer_requests'), 'e', Lang::T('Invalid request'));
        }
        $email = trim(_post('email'));
        $phone = trim(_post('phone'));
        $password = _post('password');
        $confirm = _post('confirm_password');
        if ($email == '' || $phone == '' || $password == '' || $password !== $confirm) {
            r2(getUrl('customer_requests'), 'e', Lang::T('Please complete the form correctly'));
        }
        $exists = ORM::for_table('tbl_customers')->where('email', $email)->find_one();
        if ($exists) {
            r2(getUrl('portal'), 'e', Lang::T('Account already exists'));
        }
        $otp = (string) rand(100000, 999999);
        $request = ORM::for_table('tbl_customer_registration_requests')->create();
        $request->first_name = trim(_post('first_name'));
        $request->last_name = trim(_post('last_name'));
        $request->city = trim(_post('city'));
        $request->country = trim(_post('country'));
        $request->address = trim(_post('address'));
        $request->phone = $phone;
        $request->email = $email;
        $request->instance_name = trim(_post('instance_name'));
        $request->password = Password::_crypt($password);
        $request->otp_code = $otp;
        $request->otp_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $request->status = 'pending_email';
        $request->save();
        $sent = Message::sendEmail($email, Lang::T('Verify Your Email'), '<h2>Verify Your Email</h2><p>Your verification code is:</p><h1 style="letter-spacing:10px;color:#ef4444;">' . $otp . '</h1><p>This code expires in 15 minutes.</p>');
        if (!$sent) {
            $request->delete();
            r2(getUrl('customer_requests'), 'e', Lang::T('OTP email could not be sent. Please ask the administrator to check SMTP settings.'));
        }
        r2(getUrl('customer_requests/verify/' . $request->id()), 's', Lang::T('Account created! Please verify your email.'));
        break;

    case 'verify':
        $id = intval($routes['2'] ?? 0);
        $request = ORM::for_table('tbl_customer_registration_requests')->find_one($id);
        if (!$request) {
            r2(getUrl('customer_requests'), 'e', Lang::T('Request not found'));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (strtotime($request->otp_expires_at) < time()) {
                r2(getUrl('customer_requests/verify/' . $id), 'e', Lang::T('Verification code expired'));
            }
            if (_post('otp_code') != $request->otp_code) {
                r2(getUrl('customer_requests/verify/' . $id), 'e', Lang::T('Wrong Verification code'));
            }
            $existingCustomer = ORM::for_table('tbl_customers')->where('email', $request->email)->find_one();
            if ($existingCustomer) {
                $request->email_verified = 1;
                $request->status = 'approved_trial';
                $request->customer_id = $existingCustomer->id();
                $request->updated_at = date('Y-m-d H:i:s');
                $request->save();
                r2(getUrl('portal'), 's', Lang::T('Email verified. You can login now.'));
            }
            $request->email_verified = 1;
            $request->status = 'pending_approval';
            $request->updated_at = date('Y-m-d H:i:s');
            $request->save();
            r2(getUrl('customer_requests/verify/' . $id), 's', Lang::T('Email verified. Your registration request is pending approval.'));
        }
        $ui->assign('request', $request);
        $ui->assign('_title', Lang::T('Verify Your Email'));
        $ui->display('customer/register_verify.tpl');
        break;

    default:
        $ui->assign('_title', Lang::T('Create Account'));
        $ui->display('customer/register_request_new.tpl');
        break;
}
