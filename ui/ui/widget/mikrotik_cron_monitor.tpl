{if $_c['router_check'] && count($routeroffs)> 0}
<div class="box box-solid exceptional-box offline-router-box">
    <div class="box-header">
        <div class="header-icon-large"><i class="fa fa-microchip"></i></div>
        <h3 class="box-title">{Lang::T('Routers Offline')}</h3>
    </div>
    
    <div class="box-body no-padding">
        <div class="table-responsive">
            <table class="table table-hover offline-table">
                <tbody>
                    {foreach $routeroffs as $ros}
                        <tr>
                            <td class="router-name">
                                <a href="{Text::url('routers/edit/',$ros['id'])}">
                                    <i class="fa fa-server"></i> {$ros['name']}
                                </a>
                            </td>
                            <td class="router-time text-right">
                                <span class="label label-danger-outline" data-toggle="tooltip" title="{Lang::dateTimeFormat($ros['last_seen'])}">
                                    <i class="fa fa-clock-o"></i> {Lang::timeElapsed($ros['last_seen'])}
                                </span>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
{/if}

<style>
/* ==========================================
   OFFLINE ROUTER BOX - EXCEPTIONAL STYLE
========================================== */
.offline-router-box {
    background: #ffffff !important;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    /* অফলাইন বক্সে লাল শ্যাডো */
    box-shadow: inset 0 4px 0 #ff4500, 0 6px 15px rgba(255, 69, 0, 0.1) !important;
}

/* ৪ পিক্সেল চিকন রেড-অরেঞ্জ গ্রেডিয়েন্ট স্ট্রিপ */
.offline-router-box::before {
    content: "" !important;
    position: absolute !important;
    left: 20% !important; 
    top: 0 !important; 
    width: 60% !important; 
    height: 4px !important;
    background: linear-gradient(90deg, #ff4500 0%, #ff8c00 100%) !important;
    border-radius: 0 0 10px 10px !important;
    z-index: 100 !important;
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .offline-router-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff4500, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* হেডার এবং আইকন */
.offline-router-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255, 69, 0, 0.1);
}

.offline-router-box .header-icon-large i {
    font-size: 26px !important;
    color: #ff4500 !important;
    margin-right: 12px;
}

.offline-router-box .box-title {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #ff4500 !important;
}

/* টেবিল স্টাইল */
.offline-table {
    margin-bottom: 0 !important;
}

.offline-table td {
    padding: 12px 20px !important;
    vertical-align: middle !important;
    border-top: 1px solid rgba(0,0,0,0.03) !important;
}

body.dark-mode .offline-table td {
    border-top: 1px solid rgba(255,255,255,0.05) !important;
}

.router-name a {
    color: #333;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}

.router-name a i {
    margin-right: 5px;
    color: #ff4500;
}

body.dark-mode .router-name a {
    color: #eee;
}

.router-name a:hover {
    color: #ff4500;
}

/* টাইম লেবেল */
.label-danger-outline {
    background: rgba(255, 69, 0, 0.1);
    color: #ff4500;
    border: 1px solid rgba(255, 69, 0, 0.2);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
</style>