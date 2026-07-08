<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/
class Report
{
    /**
     * Build the filtered transaction list used by the "by-date" report views.
     *
     * Reads the tps/mts/rts/plns/sd/ed/ts/te request parameters (falling back to the
     * full set of enum types / distinct methods / routers / plans and the billing
     * reset-day window), then returns the matching rows together with the resolved
     * date range so callers can assign them to the template or render a PDF.
     *
     * @param array $config app config (uses reset_day)
     * @return array{rows:array,total:float,sd:string,ed:string,ts:string,te:string,mdate:string}
     */
    public static function filterTransactionsByDate($config)
    {
        $mdate = date('Y-m-d');
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $reset_day = $config['reset_day'];
        if (empty($reset_day)) {
            $reset_day = 1;
        }
        //first day of month
        if (date("d") >= $reset_day) {
            $start_date = date('Y-m-' . $reset_day);
        } else {
            $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
        }
        $tps = !empty($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = !empty($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = !empty($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = !empty($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');

        $query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('id');
        if (count($tps) > 0) {
            $query->where_in('type', $tps);
        }
        if (count($mts) > 0) {
            if (count($mts) != count($methods)) {
                foreach ($mts as $mt) {
                    $query->where_like('method', "$mt - %");
                }
            }
        }
        if (count($rts) > 0) {
            $query->where_in('routers', $rts);
        }
        if (count($plns) > 0) {
            $query->where_in('plan_name', $plns);
        }

        return [
            'rows' => $query->find_array(),
            'total' => $query->sum('price'),
            'sd' => $sd,
            'ed' => $ed,
            'ts' => $ts,
            'te' => $te,
            'mdate' => $mdate,
        ];
    }

    /**
     * Shared CSS used by the transaction report PDFs.
     *
     * @return string
     */
    public static function pdfStyle()
    {
        return '<style>
			#page-wrap { width: 100%; margin: 0 auto; }
			#header { text-align: center; position: relative; color: black; font: bold 15px Helvetica, Sans-Serif; margin-top: 10px; margin-bottom: 10px;}

			#address { width: 300px; float: left; }
			#logo { text-align: right; float: right; position: relative; margin-top: 15px; border: 5px solid #fff; overflow: hidden; }

			#customers
			{
			font-family: Helvetica, sans-serif;
			width:100%;
			border-collapse:collapse;
			}
			#customers td, #customers th
			{
			font-size:0.8em;
			border:1px solid #98bf21;
			padding:3px 5px 2px 5px;
			}
			#customers th
			{
			font-size:0.8em;
			text-align:left;
			padding-top:5px;
			padding-bottom:4px;
			background-color:#A7C942;
			color:#fff;
			}
			#customers tr.alt td
			{
			color:#000;
			background-color:#EAF2D3;
			}
			</style>';
    }
}
