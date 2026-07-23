<?php

class graph_monthly_sales
{
    public function getWidget()
    {
        global $CACHE_PATH, $ui, $admin;

        if (DemoShowcase::isActive($admin ?? null)) {
            $ui->assign('monthlySales', DemoShowcase::monthlySalesData());
            return $ui->fetch('widget/graph_monthly_sales.tpl');
        }

        $cacheKey = AdminScope::isScoped($admin ?? null)
            ? ('_' . AdminScope::adminId($admin))
            : '_all';
        $cacheMSfile = $CACHE_PATH . File::pathFixer('/monthlySales' . $cacheKey . '.temp');
        //Cache for 12 hours
        if (file_exists($cacheMSfile) && time() - filemtime($cacheMSfile) < 43200) {
            $monthlySales = json_decode(file_get_contents($cacheMSfile), true);
        } else {
            // Query to retrieve monthly data
            $resultsQuery = ORM::for_table('tbl_transactions')
                ->select_expr('MONTH(recharged_on)', 'month')
                ->select_expr('SUM(price)', 'total')
                ->where_raw("YEAR(recharged_on) = YEAR(CURRENT_DATE())")
                ->where_not_equal('method', 'Customer - Balance')
                ->where_not_equal('method', 'Recharge Balance - Administrator')
                ->group_by_expr('MONTH(recharged_on)');
            if (AdminScope::isScoped($admin ?? null)) {
                AdminScope::applyTransactionsQuery($resultsQuery, $admin);
            }
            $results = $resultsQuery->find_many();

            // Create an array to hold the monthly sales data
            $monthlySales = array();

            // Iterate over the results and populate the array
            foreach ($results as $result) {
                $month = $result->month;
                $totalSales = $result->total;

                $monthlySales[$month] = array(
                    'month' => $month,
                    'totalSales' => $totalSales
                );
            }

            // Fill in missing months with zero sales
            for ($month = 1; $month <= 12; $month++) {
                if (!isset($monthlySales[$month])) {
                    $monthlySales[$month] = array(
                        'month' => $month,
                        'totalSales' => 0
                    );
                }
            }

            // Sort the array by month
            ksort($monthlySales);

            // Reindex the array
            $monthlySales = array_values($monthlySales);
            file_put_contents($cacheMSfile, json_encode($monthlySales));
        }

        $ui->assign('monthlySales', $monthlySales);
        return $ui->fetch('widget/graph_monthly_sales.tpl');
    }
}
