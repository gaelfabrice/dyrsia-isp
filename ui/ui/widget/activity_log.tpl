<div class="box box-solid exceptional-box activity-box">
    <div class="box-header">
        <div class="header-icon-large"><i class="fa fa-history"></i></div>
        <h3 class="box-title">
            <a href="{$logs_full_url}" style="color: inherit; text-decoration: none;">{Lang::T('Activity Log')}</a>
        </h3>
    </div>
    
    <div class="box-body no-padding">
        <ul class="activity-timeline">
            {if $alog_entries|@count > 0}
                {foreach $alog_entries as $dl}
                    <li>
                        <i class="fa fa-circle timeline-point"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fa fa-clock-o"></i> {Lang::timeElapsed($dl['date'],true)}</span>
                            <p class="description">{$dl['description']|escape}</p>
                        </div>
                    </li>
                {/foreach}
            {else}
                <li class="text-muted" style="padding:12px 0 0 25px;border:0;">{Lang::T('No activity recorded yet')}</li>
            {/if}
        </ul>
    </div>

    <div class="pagination-container">
    <a href="{$alog_prev_url}"
       class="page-link-simple {if $alog_current_page <= 1}disabled{/if}"
       {if $alog_current_page <= 1}aria-disabled="true" onclick="return false;"{/if}>
       <i class="fa fa-angle-left"></i> {Lang::T('Prev')}
    </a>

    <span class="page-info" title="{$alog_total_entries} {Lang::T('entries')}">
        {Lang::T('Page')} {$alog_current_page} / {$alog_total_pages}
        <small class="alog-page-meta">({$alog_total_entries} {Lang::T('entries')})</small>
    </span>

    <a href="{$alog_next_url}"
       class="page-link-simple {if $alog_current_page >= $alog_total_pages}disabled{/if}"
       {if $alog_current_page >= $alog_total_pages}aria-disabled="true" onclick="return false;"{/if}>
       {Lang::T('Next')} <i class="fa fa-angle-right"></i>
    </a>
</div>
</div>

<style>
/* ==========================================
   ACTIVITY BOX STYLE - 4px STRIP
========================================== */
.activity-box {
    background: #ffffff !important;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

.activity-box::before {
    content: "";
    position: absolute;
    left: 20%; top: 0; width: 60%; height: 4px;
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px;
    z-index: 10;
}

body.dark-mode .activity-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* Header & Icon */
.activity-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.header-icon-large i {
    font-size: 26px !important;
    color: #ff2e93 !important;
    margin-right: 12px;
}

.activity-box .box-title {
    font-size: 16px !important;
    font-weight: 700 !important;
}

/* Timeline Style */
.activity-timeline {
    list-style: none;
    padding: 20px;
    margin: 0;
    position: relative;
}

.activity-timeline li {
    position: relative;
    padding-bottom: 15px;
    padding-left: 25px;
    border-left: 1px solid rgba(0,0,0,0.1);
}

body.dark-mode .activity-timeline li {
    border-left: 1px solid rgba(255,255,255,0.1);
}

.timeline-point {
    position: absolute;
    left: -5px;
    top: 5px;
    font-size: 10px !important;
    color: #ff2e93;
}

.timeline-item .time {
    font-size: 11px;
    color: #888;
    display: block;
    margin-bottom: 3px;
}

.timeline-item .description {
    font-size: 13px;
    margin: 0;
    line-height: 1.4;
}

/* Pagination Style */
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: rgba(0,0,0,0.02);
    border-top: 1px solid rgba(0,0,0,0.05);
}

body.dark-mode .pagination-container {
    background: rgba(255,255,255,0.02);
    border-top: 1px solid rgba(255,255,255,0.05);
}

.page-link-simple {
    padding: 6px 15px;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

body.dark-mode .page-link-simple {
    background: #1c2536;
    border-color: #343a40;
    color: #eee;
}

.page-link-simple:hover:not(.disabled) {
    background: #ff2e93;
    color: #fff;
    border-color: #ff2e93;
}

.page-link-simple.disabled,
.page-link-simple.is-disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.page-info {
    font-size: 14px;
    font-weight: 700;
    color: #ff2e93;
    background: rgba(255, 46, 147, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
}

body.dark-mode .page-info {
    color: #ff5dab;
    background: rgba(255, 93, 171, 0.15);
}

.page-info .alog-page-meta {
    font-weight: 500;
    font-size: 11px;
    opacity: 0.85;
    margin-left: 4px;
}
</style>
