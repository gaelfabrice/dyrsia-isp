<div class="box box-solid exceptional-box theme-panel-expired">
    <div class="box-header">
        <div class="header-icon">
            <i class="fa fa-user-times"></i>
        </div>
        <h3 class="box-title">{Lang::T('Customer Expiry Status')}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-sm btn-icon-only" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
            <a href="{Text::url('dashboard&refresh')}" class="btn btn-sm btn-icon-only">
                <i class="fa fa-refresh"></i>
            </a>
        </div>
    </div>

    <div class="box-body">
        <div class="expiry-stats-bar">
    <div class="stat-box expired-bg">
        <i class="fa fa-user-times"></i>
        <span class="stat-text">Total Expired: <b>{$already_expired|count}</b></span>
    </div>
    <div class="stat-box coming-bg">
        <i class="fa fa-clock-o"></i>
        <span class="stat-text">Total Coming: <b>{$coming_expired|count}</b></span>
    </div>
</div>
        <div class="filter-search-container">
            <select id="exp-filter" onchange="changeExpiredFilter(this)" class="custom-select">
                <option value="username">{Lang::T('Username')}</option>
                <option value="fullname">{Lang::T('Full Name')}</option>
                <option value="phone">{Lang::T('Phone')}</option>
                <option value="email">{Lang::T('Email')}</option>
            </select>
            <div class="search-wrapper">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="exp-search" placeholder="{Lang::T('Search customers...')}" oninput="searchExpired(this)">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover custom-exp-table" id="exp-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Already Expired')}</th>
                        <th>{Lang::T('Package')}</th>
                        <th>{Lang::T('Coming Expired')}</th>
                        <th>{Lang::T('Package')}</th>
                        <th>{Lang::T('Router / Location')}</th>
                    </tr>
                </thead>
                <tbody>
                    {assign var="max_rows" value=max(count($already_expired), count($coming_expired))}
                    {section name=i loop=$max_rows}
                    <tr class="exp-row"
                        data-username="{$already_expired[i]->username|default:''} {$coming_expired[i]->username|default:''}"
                        data-fullname="{$already_expired[i]->fullname|default:''} {$coming_expired[i]->fullname|default:''}"
                        data-phone="{$already_expired[i]->phonenumber|default:''} {$coming_expired[i]->phonenumber|default:''}"
                        data-email="{$already_expired[i]->email|default:''} {$coming_expired[i]->email|default:''}">
                        
                        <td>
                            {if isset($already_expired[i])}
                            <a href="{Text::url('customers/view/',$already_expired[i]->id)}" class="user-link expired-red">
                                {$already_expired[i]->username}
                            </a>
                            <br>
                            <small class="expired-count status-time" data-expiration="{$already_expired[i]->expiration}T{$already_expired[i]->time}"></small>
                            {/if}
                        </td>

                        <td class="package-cell red-text">
                            {if isset($already_expired[i])}
                                {$already_expired[i]->namebp}
                            {/if}
                        </td>

                        <td>
                            {if isset($coming_expired[i])}
                            <a href="{Text::url('customers/view/',$coming_expired[i]->id)}" class="user-link coming-orange">
                                {$coming_expired[i]->username}
                            </a>
                            <br>
                            <small class="coming-count status-time" data-expiration="{$coming_expired[i]->expiration}T{$coming_expired[i]->time}"></small>
                            {/if}
                        </td>

                        <td class="package-cell orange-text">
                            {if isset($coming_expired[i])}
                                {$coming_expired[i]->namebp}
                            {/if}
                        </td>

                        <td class="router-cell">
                            {if isset($already_expired[i])}
                                <i class="fa fa-server"></i> {$already_expired[i]->routers}
                            {elseif isset($coming_expired[i])}
                                <i class="fa fa-server"></i> {$coming_expired[i]->routers}
                            {/if}
                        </td>
                    </tr>
                    {/section}
                </tbody>
            </table>
        </div>

        <div class="custom-pagination">
            <a href="{$exp_prev_url}"
               class="pag-btn prev-next {if $exp_current_page <= 1}disabled{/if}"
               {if $exp_current_page <= 1}onclick="return false;"{/if}>
                <i class="fa fa-chevron-left"></i> Prev
            </a>

            <div class="page-indicator">
                <span class="current-page">{$exp_current_page}</span>
                <span class="divider">/</span>
                <span class="total-pages">{$max_pages}</span>
            </div>

            <a href="{$exp_next_url}"
               class="pag-btn prev-next {if $exp_current_page >= $max_pages}disabled{/if}"
               {if $exp_current_page >= $max_pages}onclick="return false;"{/if}>
                Next <i class="fa fa-chevron-right"></i>
            </a>
        </div>
    </div>
    </div>

<style>
/* ==========================================
   EXCEPTIONAL BOX STYLING (6px Radius)
========================================== */
.exceptional-box {
    background: #ffffff !important;
    color: #333;
    border-radius: 6px !important;
    margin-bottom: 15px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* Pink to Blue Strip */
.exceptional-box::before {
    content: "";
    position: absolute;
    left: 20%; top: 0; width: 60%; height: 4px;
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px;
    z-index: 10;
}

body.dark-mode .exceptional-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* Big Yellow Icon */
.exceptional-box .header-icon i {
    font-size: 30px !important;
    color: #FFD700 !important;
    -webkit-text-fill-color: #FFD700 !important;
    margin-right: 10px;
}

/* Green Buttons */
.btn-icon-only {
    background: transparent !important;
    border: none !important;
    color: #00d084 !important;
    font-size: 16px !important;
    padding: 5px;
}

.exceptional-box .box-header {
    border-bottom: 1px solid rgba(0,0,0,0.03);
    padding: 15px 20px;
    display: flex; align-items: center;
}

.exceptional-box .box-title {
    font-weight: 700; font-size: 17px; margin: 0;
}

/* ==========================================
   FILTER & SEARCH STYLING
========================================== */
.filter-search-container {
    display: flex; gap: 10px; padding: 15px;
}

.custom-select, #exp-search {
    border-radius: 6px;
    border: 1px solid #ddd;
    padding: 8px 12px;
}

.search-wrapper {
    position: relative; flex: 1;
}

.search-icon {
    position: absolute; left: 10px; top: 11px; color: #999;
}

#exp-search {
    width: 100%; padding-left: 30px;
}

body.dark-mode .custom-select, 
body.dark-mode #exp-search {
    background: #1c2536 !important;
    border-color: #2d3748 !important;
    color: #fff !important;
}

/* ==========================================
   TABLE & PAGINATION
========================================== */
.custom-exp-table thead th {
    background: rgba(0,0,0,0.02);
    border-bottom: 2px solid rgba(0,0,0,0.05);
    font-size: 12px; text-transform: uppercase;
}

body.dark-mode .custom-exp-table thead th {
    background: rgba(255,255,255,0.02);
}

.user-link { font-weight: 700; text-decoration: none; }
.expired-red { color: #dc3545 !important; }
.coming-orange { color: #FFA500 !important; }
.status-time { font-size: 11px; color: #888; }

.custom-pagination {
    display: flex; justify-content: center; gap: 8px; padding: 20px;
}

.pag-btn {
    padding: 6px 12px; border-radius: 4px;
    border: 1px solid #ddd; color: #666; text-decoration: none;
}

.pag-btn.active {
    background: #ff2e93; color: #fff; border-color: #ff2e93;
}

.pag-btn.disabled { opacity: 0.5; pointer-events: none; }

body.dark-mode .pag-btn {
    background: #232a42; border-color: #2d3748; color: #a0aec0;
}
/* ==========================================
   ULTIMATE DARK MODE INPUT FIX
========================================== */
/* ডার্ক মোডে সার্চ এবং ফিল্টার বক্সের হোভার ও ক্লিক ইফেক্ট ফিক্স */
body.dark-mode #exp-filter,
body.dark-mode #exp-search,
body.dark-mode .custom-select {
    background-color: #232a42 !important;
    color: #ffffff !important;
}

/* মাউস নিলে বা ক্লিক করলে (Focus/Active) যাতে সাদা না হয় */
body.dark-mode #exp-filter:hover,
body.dark-mode #exp-search:hover,
body.dark-mode #exp-filter:focus,
body.dark-mode #exp-search:focus,
body.dark-mode #exp-filter:active,
body.dark-mode #exp-search:active {
    background-color: #2a3446 !important; /* সামান্য উজ্জ্বল ডার্ক */
    color: #ffffff !important;
    border-color: #ff2e93 !important; /* সিগনেচার পিঙ্ক বর্ডার */
    outline: none !important;
    box-shadow: 0 0 8px rgba(255, 46, 147, 0.3) !important;
}

/* ড্রপডাউন অপশনগুলোকেও ডার্ক করা */
body.dark-mode #exp-filter option {
    background-color: #161b2e !important;
    color: #ffffff !important;
}

/* পুরো প্যানেল হোভার ফিক্স (আবারও নিশ্চিত করার জন্য) */
body.dark-mode .exceptional-box:hover,
body.dark-mode .theme-panel-expired:hover {
    background-color: #161b2e !important;
}
.custom-pagination {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 15px !important;
    padding: 20px 0 !important;
    width: 100% !important;
    clear: both !important;
}

.page-indicator {
    font-weight: bold;
    font-size: 16px;
    color: inherit;
}

.current-page {
    color: #ff2e93 !important; /* পিঙ্ক হাইলাইট */
}

.pag-btn.prev-next {
    border: 1px solid #00d084 !important;
    color: #00d084 !important;
    padding: 5px 15px !important;
    border-radius: 20px !important;
    text-decoration: none !important;
}

.pag-btn.disabled {
    border-color: #ccc !important;
    color: #ccc !important;
    pointer-events: none;
}
/* Expiry Stats Bar - Single Line Style */
.expiry-stats-bar {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 15px;
    background: rgba(0,0,0,0.02);
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.stat-box {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
}

.stat-box i {
    margin-right: 8px;
    font-size: 16px;
}

/* কালার সেট */
.expired-bg {
    background: #ffebee !important;
    color: #d32f2f !important;
    border: 1px solid #ffcdd2;
}

.coming-bg {
    background: #fff3e0 !important;
    color: #ef6c00 !important;
    border: 1px solid #ffe0b2;
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .expired-bg {
    background: rgba(211, 47, 47, 0.2) !important;
    border-color: rgba(211, 47, 47, 0.3);
    color: #ff8a80 !important;
}

body.dark-mode .coming-bg {
    background: rgba(239, 108, 0, 0.2) !important;
    border-color: rgba(239, 108, 0, 0.3);
    color: #ffb74d !important;
}

body.dark-mode .expiry-stats-bar {
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
</style>

<script>
{literal}
function applyFilter() {
    const filter = document.getElementById('exp-filter').value.toLowerCase();
    const query = document.getElementById('exp-search').value.toLowerCase();
    document.querySelectorAll('.exp-row').forEach(tr => {
        const data = (tr.getAttribute('data-' + filter) || '').toLowerCase();
        tr.style.display = data.includes(query) ? '' : 'none';
    });
}
function changeExpiredFilter() { applyFilter(); }
function searchExpired() { applyFilter(); }

function updateCounts() {
    const now = new Date();
    document.querySelectorAll('.status-time').forEach(el => {
        const expTime = new Date(el.getAttribute('data-expiration'));
        if (!expTime || isNaN(expTime)) return;
        const diff = el.classList.contains('expired-count') ? (now - expTime) : (expTime - now);
        
        if (diff > 0) {
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff / 3600000) % 24);
            const m = Math.floor((diff / 60000) % 60);
            const s = Math.floor((diff / 1000) % 60);
            
            if(el.classList.contains('expired-count'))
                el.innerText = `Expired ${d}d ${h}h ${m}m ago`;
            else
                el.innerText = `In ${d}d ${h}h ${m}m ${s}s`;
        } else {
            el.innerText = "Processing...";
        }
    });
}
setInterval(updateCounts, 1000);
updateCounts();
{/literal}
</script>