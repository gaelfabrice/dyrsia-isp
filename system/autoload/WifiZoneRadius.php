<?php

class WifiZoneRadius
{
    public static function wizardStatus()
    {
        $status = ['radius_tables' => false, 'plans_synced' => 0];
        try {
            ORM::for_table('nas', 'radius')->find_one();
            $status['radius_tables'] = true;
        } catch (Exception $e) {
        }
        return $status;
    }

    public static function syncPlansToRadius()
    {
        if (!self::wizardStatus()['radius_tables']) {
            return 0;
        }
        $count = 0;
        $plans = ORM::for_table('tbl_plans')->where('type', 'Radius')->find_many();
        foreach ($plans as $plan) {
            try {
                $exists = ORM::for_table('radgroupreply', 'radius')
                    ->where('groupname', $plan->name_plan)
                    ->find_one();
                if (!$exists) {
                    $g = ORM::for_table('radgroupreply', 'radius')->create();
                    $g->groupname = $plan->name_plan;
                    $g->attribute = 'Mikrotik-Rate-Limit';
                    $g->op = ':=';
                    $g->value = ($plan->rate_down ?? '10M') . '/' . ($plan->rate_up ?? '10M');
                    $g->save();
                    $count++;
                }
            } catch (Exception $e) {
            }
        }
        return $count;
    }
}
