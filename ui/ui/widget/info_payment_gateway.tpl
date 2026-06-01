<div class="box box-solid exceptional-box gateway-box">
    <div class="box-header">
        <div class="header-icon">
            <i class="fa fa-credit-card"></i> 
        </div>
        <h3 class="box-title">
            {Lang::T('Payment Gateway')}: <span class="gateway-list">{str_replace(',',', ',$_c['payment_gateway'])}</span>
        </h3>
    </div>
</div>

<style>
/* ==========================================
   GATEWAY BOX STYLE - COMPACT 4px STRIP
========================================== */
.gateway-box {
    background: #ffffff !important;
    color: #333;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    /* ৪ পিক্সেল চিকন পিঙ্ক ইনসেট স্ট্রিপ */
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* ওপরে সেই সিগনেচার পিঙ্ক-ব্লু গ্রেডিয়েন্ট স্ট্রিপ */
.gateway-box::before {
    content: "";
    position: absolute;
    left: 20%; top: 0; width: 60%; height: 4px;
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px;
    z-index: 10;
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .gateway-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* আইকন স্টাইল (গোল্ডেন/হলুদ আইকন) */
.gateway-box .header-icon i {
    font-size: 24px !important; 
    color: #FFD700 !important;
    -webkit-text-fill-color: #FFD700 !important;
    margin-right: 12px;
    display: inline-block;
    vertical-align: middle;
}

.gateway-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
}

.gateway-box .box-title {
    font-size: 15px !important;
    font-weight: 600;
    margin: 0;
}

.gateway-list {
    color: #ff2e93; /* গেটওয়েগুলোর নাম পিঙ্ক কালারে হাইলাইট হবে */
    font-weight: bold;
}

body.dark-mode .gateway-list {
    color: #ff5dab;
}

/* মাউস রাখলে হোভার ইফেক্ট (সাদা হওয়া বন্ধ) */
body.dark-mode .gateway-box:hover {
    background: #161b2e !important;
}
</style>