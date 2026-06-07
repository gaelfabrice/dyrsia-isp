<?php

AdminSubscription::ensureSchema();
$ui->assign('_title', Lang::T('Welcome'));
$ui->assign('isp_settings', AdminSubscription::settings());
$ui->display('customer/landing.tpl');
