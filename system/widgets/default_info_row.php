<?php

class default_info_row
{
    public function getWidget()
    {
        global $config, $ui;

        if ($config['enable_balance'] == 'yes') {
            $first_day = date('Y-m-01');
            $today = date('Y-m-d');

            // আপনার টেবিল অনুযায়ী 'amount' এর বদলে 'price' 
            // এবং 'date' এর বদলে 'recharged_on' ব্যবহার করা হয়েছে।
            $cb = ORM::for_table('tbl_transactions')
                ->whereGte('recharged_on', $first_day)
                ->where('type', 'Income') 
                ->sum('price'); 

            $cb = ($cb) ? $cb : 0;

            $ui->assign('cb', $cb);
            $ui->assign('start_date', $first_day);
            $ui->assign('current_date', $today);
        }

        return $ui->fetch('widget/default_info_row.tpl');
    }
}