{if $run_date}
    {assign var="current_time" value=$smarty.now}
    {assign var="run_time" value=strtotime($run_date)}
    
    {if $current_time - $run_time > 3600}
        <div class="box box-solid exceptional-box cron-warning">
            <div class="box-header">
                <div class="header-icon"><i class="fa fa-clock-o" style="color: #ffc107 !important; -webkit-text-fill-color: #ffc107 !important;"></i></div>
                <h3 class="box-title">{Lang::T('Cron has not run for over 1 hour. Please check your setup.')}</h3>
            </div>
        </div>
    {else}
        <div class="box box-solid exceptional-box cron-success">
            <div class="box-header">
                <div class="header-icon"><i class="fa fa-check-circle" style="color: #28a745 !important; -webkit-text-fill-color: #28a745 !important;"></i></div>
                <h3 class="box-title">{Lang::T('Cron Job last ran on')}: {$run_date}</h3>
            </div>
        </div>
    {/if}
{else}
    <div class="box box-solid exceptional-box cron-danger">
        <div class="box-header">
            <div class="header-icon"><i class="fa fa-warning" style="color: #dc3545 !important; -webkit-text-fill-color: #dc3545 !important;"></i></div>
            <h3 class="box-title">{Lang::T('Cron appear not been setup, please check your cron setup.')}</h3>
        </div>
    </div>
{/if}

<style>
/* ==========================================
   CRON BOX STYLE - COMPACT 4px STRIP
========================================== */
.exceptional-box {
    background: #ffffff !important;
    color: #333;
    border-radius: 6px !important;
    margin-bottom: 15px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
}

/* অন্যান্য বক্সের মতো চিকন ৪ পিক্সেল ইনসেট স্ট্রিপ */
.cron-success { box-shadow: inset 0 4px 0 #28a745, 0 6px 15px rgba(0,0,0,0.05) !important; }
.cron-warning { box-shadow: inset 0 4px 0 #ffc107, 0 6px 15px rgba(0,0,0,0.05) !important; }
.cron-danger  { box-shadow: inset 0 4px 0 #dc3545, 0 6px 15px rgba(0,0,0,0.05) !important; }

/* ডার্ক মোড ফিক্স */
body.dark-mode .exceptional-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
}

/* ডার্ক মোডেও ৪ পিক্সেল চিকন স্ট্রিপ */
body.dark-mode .cron-success { box-shadow: inset 0 4px 0 #1e7e34, 0 10px 25px rgba(0,0,0,0.3) !important; }
body.dark-mode .cron-warning { box-shadow: inset 0 4px 0 #b38f00, 0 10px 25px rgba(0,0,0,0.3) !important; }
body.dark-mode .cron-danger  { box-shadow: inset 0 4px 0 #a71d2a, 0 10px 25px rgba(0,0,0,0.3) !important; }

.exceptional-box .header-icon i {
    font-size: 28px !important; 
    margin-right: 12px;
    display: inline-block;
    vertical-align: middle;
}

.exceptional-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
}

.exceptional-box .box-title {
    font-size: 15px !important;
    font-weight: 600;
    margin: 0;
}
</style>