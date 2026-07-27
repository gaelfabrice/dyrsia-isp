<?php

class voucher_stocks
{
    private function allowedVoucherGenerators($admin)
    {
        if (!$admin || $admin['user_type'] == 'SuperAdmin') {
            return null;
        }
        $adminId = (int) $admin['id'];
        $rootId = !empty($admin['root']) ? (int) $admin['root'] : $adminId;
        $ids = [$adminId, $rootId];
        foreach (ORM::for_table('tbl_users')->select('id')->where('root', $rootId)->findArray() as $user) {
            $ids[] = (int) $user['id'];
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function applyVoucherScope($query, $admin)
    {
        $ids = $this->allowedVoucherGenerators($admin);
        if ($ids !== null) {
            $query->where_in('generated_by', $ids);
        }
        return $query;
    }

    public function getWidget()
    {
        global $CACHE_PATH,$ui,$admin;
        $cacheKey = ($admin && !empty($admin['id'])) ? ('Admin' . (int) $admin['id']) : 'Guest';
        $cacheStocksfile = $CACHE_PATH . File::pathFixer('/VoucherStocks_' . $cacheKey . '.temp');
        $cachePlanfile = $CACHE_PATH . File::pathFixer('/VoucherPlans_' . $cacheKey . '.temp');
        //Cache for 5 minutes
        if (file_exists($cacheStocksfile) && time() - filemtime($cacheStocksfile) < 600) {
            $stocks = json_decode(file_get_contents($cacheStocksfile), true);
            $plans = json_decode(file_get_contents($cachePlanfile), true);
        } else {
            // Count stock
            $plansQuery = ORM::for_table('tbl_plans')->select('id')->select('name_plan');
            AdminScope::applyPlansQuery($plansQuery, $admin);
            $tmp = $plansQuery->find_many();
            $plans = array();
            $stocks = array("used" => 0, "unused" => 0);
            $n = 0;
            foreach ($tmp as $plan) {
                $unusedQuery = ORM::for_table('tbl_voucher')
                    ->where('id_plan', $plan['id'])
                    ->where('status', 0);
                $usedQuery = ORM::for_table('tbl_voucher')
                    ->where('id_plan', $plan['id'])
                    ->where('status', 1);
                $unused = $this->applyVoucherScope($unusedQuery, $admin)->count();
                $used = $this->applyVoucherScope($usedQuery, $admin)->count();
                if ($unused > 0 || $used > 0) {
                    $plans[$n]['name_plan'] = $plan['name_plan'];
                    $plans[$n]['unused'] = $unused;
                    $plans[$n]['used'] = $used;
                    $stocks["unused"] += $unused;
                    $stocks["used"] += $used;
                    $n++;
                }
            }
            file_put_contents($cacheStocksfile, json_encode($stocks));
            file_put_contents($cachePlanfile, json_encode($plans));
        }
        $ui->assign('stocks', $stocks);
        $ui->assign('plans', $plans);
        return $ui->fetch('widget/voucher_stocks.tpl');
    }
}