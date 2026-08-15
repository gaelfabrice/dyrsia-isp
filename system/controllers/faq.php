<?php

/**
 * FAQ Page Controller
 * Questions Fréquentes pour Dyrsia ISP
 */

$ui->assign('_title', 'FAQ - Questions Fréquentes');
$ui->assign('public_contact', WifiZoneCore::publicContactInfo());
$ui->display('customer/faq.tpl');
