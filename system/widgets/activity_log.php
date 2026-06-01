<?php

class activity_log
{
    public function getWidget($data = null)
    {
        global $ui, $admin;

        $per_page = 5;
        $page = isset($_GET['log_page']) ? max((int) $_GET['log_page'], 1) : 1;
        $offset = ($page - 1) * $per_page;
        $scopeSql = wifizone_activity_log_widget_scope_sql();

        $countQuery = ORM::for_table('tbl_logs')->where_raw($scopeSql);
        if (!empty($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $countQuery->where('userid', (int) $admin['id']);
        }
        $total_logs = (int) $countQuery->count();

        $query = ORM::for_table('tbl_logs')
            ->where_raw($scopeSql)
            ->order_by_desc('id');
        if (!empty($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $query->where('userid', (int) $admin['id']);
        }

        $dlog = $query
            ->limit($per_page)
            ->offset($offset)
            ->find_array();

        foreach ($dlog as $i => $row) {
            $dlog[$i] = wifizone_normalize_log_display_row($row);
        }
        $dlog = Lang::translateLogRows($dlog);

        $total_pages = max(1, (int) ceil($total_logs / $per_page));
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $exp_page = isset($_GET['exp_page']) ? max((int) $_GET['exp_page'], 1) : 1;
        $buildPageUrl = function ($logPage) use ($exp_page) {
            $logPage = max(1, (int) $logPage);
            return getUrl('dashboard&log_page=' . $logPage . '&exp_page=' . $exp_page);
        };

        $ui->assign('alog_entries', $dlog);
        $ui->assign('alog_current_page', $page);
        $ui->assign('alog_total_pages', $total_pages);
        $ui->assign('alog_total_entries', $total_logs);
        $ui->assign('alog_prev_url', $buildPageUrl($page - 1));
        $ui->assign('alog_next_url', $buildPageUrl($page + 1));
        $ui->assign('logs_full_url', getUrl('logs/list'));

        return $ui->fetch('widget/activity_log.tpl');
    }
}
