<?php

$csrf_token = Csrf::generateAndStoreToken();
$ui->assign('csrf_token', $csrf_token);
$ui->assign('_title', Lang::T('Customer Portal'));
$ui->display('customer/login.tpl');
