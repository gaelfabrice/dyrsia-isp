<?php

class cron_monitor
{
    public function getWidget()
    {
        global $UPLOAD_PATH,$ui;

        $lastRun = WifiZoneOps::getCronLastRunTimestamp();
        if ($lastRun > 0) {
            $ui->assign('run_date', date('Y-m-d h:i:s A', $lastRun));
        }

        return $ui->fetch('widget/cron_monitor.tpl');
    }
}