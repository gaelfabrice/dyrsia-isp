<style>
/* =========================
   BREADCRUMB - SAME TO SAME FOR BOTH THEMES
========================= */
.breadcrumb {
    /* হোয়াইট মোড (বেস ডিজাইন) */
    background: #ffffff !important;
    color: #1a202c !important;
    border-radius: 12px !important;
    padding: 12px 20px !important; 
    height: 60px !important; /* উচ্চতা একদম ফিক্সড করে দেওয়া হলো */
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 15px !important;
    position: relative !important;
    overflow: hidden !important;
    border: 1px solid rgba(0,0,0,0.06) !important;
    
    /* সেই সিগনেচার পিঙ্ক ইনসেট শ্যাডো */
    box-shadow: inset 4px 0 0 #ff2e93, 0 4px 10px rgba(0,0,0,0.05) !important;
    margin-bottom: 20px !important;
}

/* কমন স্ট্রিপ ডিজাইন (হোয়াইট ও ডার্ক উভয়ের জন্য হুবহু এক) */
.breadcrumb::before {
    content: "" !important;
    position: absolute !important;
    left: 0 !important;
    top: 15% !important;
    width: 4px !important;
    height: 70% !important;
    background: linear-gradient(180deg, #ff2e93, #3b82f6) !important;
    border-radius: 0 10px 10px 0 !important;
    z-index: 10 !important;
    box-shadow: inset 1px 1px 2px rgba(0,0,0,0.2) !important;
}

/* =========================
   DARK THEME - ONLY COLOR CHANGE (Size stays Same)
========================= */
body.dark-mode .breadcrumb,
body.dark .breadcrumb,
body.theme-dark .breadcrumb {
    background: #161b2e !important; /* ড্যাশবোর্ড বক্সের ডার্ক কালার */
    color: #f8fafc !important;
    border: 1px solid #232a42 !important;
    
    /* সাইজ ও স্ট্রিপ হোয়াইটের মতো হুবহু এক রাখতে ইনসেট শ্যাডো */
    box-shadow: inset 4px 0 0 #ff2e93, 0 4px 10px rgba(0,0,0,0.3) !important;
}

/* Text Styling */
.breadcrumb li {
    list-style: none !important;
    color: inherit !important;
    font-size: 16px !important;
    font-weight: 500 !important;
}

.breadcrumb li:nth-child(1),
.breadcrumb li:nth-child(2) {
    font-size: 18px !important;
    font-weight: 700 !important;
}

/* Responsive */
@media(max-width: 600px) {
    .breadcrumb {
        height: auto !important; /* মোবাইলে অটো হাইট */
        flex-direction: column !important;
        padding: 15px 10px !important;
    }
    .breadcrumb::before {
        display: none !important;
    }
}
</style>
<ol class="breadcrumb">
    <li>
        <i class="fa fa-calendar" style="color: #ff2e93; margin-right: 5px;"></i> 
        {date('01 M Y')}
    </li> 

    <li style="margin-left: 10px;">
        <i class="fa fa-calendar" style="color: #ff2e93; margin-right: 5px;"></i> 
        {date('01 M Y', strtotime('first day of next month'))}
    </li>

    {if $_c['enable_balance'] == 'yes' && in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
        <li onclick="window.location.href = '{Text::url('customers&search=&order=balance&filter=Active&orderby=desc')}'" style="cursor: pointer; margin-left: 15px;">
            <i class="fa fa-calendar" style="color: #ff2e93; margin-right: 5px;"></i>
            {Lang::T('Monthly Balance')} <sup>{$_c['currency_code']}</sup>
            <b style="color: #ff2e93;">{number_format($cb, 0, $_c['dec_point'], $_c['thousands_sep'])}</b>
        </li>
    {/if}
</ol>