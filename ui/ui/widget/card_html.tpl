<div class="box box-solid exceptional-box dynamic-card-box">
    <div class="box-header">
        <div class="header-icon-large"><i class="fa fa-th-large"></i></div>
        <h3 class="box-title">{$card_header}</h3>
    </div>
    
    <div class="box-body dynamic-body">
        {$card_body}
    </div>
</div>

<style>
/* ==========================================
   DYNAMIC CARD STYLE - 4px STRIP
========================================== */
.dynamic-card-box {
    background: #ffffff !important;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    /* হোয়াইট এবং ডার্ক মোডে সেইম পিঙ্ক ইনসেট স্ট্রিপ */
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* ৪ পিক্সেল চিকন পিঙ্ক-ব্লু গ্রেডিয়েন্ট স্ট্রিপ */
.dynamic-card-box::before {
    content: "" !important;
    position: absolute !important;
    left: 20% !important; 
    top: 0 !important; 
    width: 60% !important; 
    height: 4px !important;
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px !important;
    z-index: 100 !important;
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .dynamic-card-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* হেডার এবং আইকন */
.dynamic-card-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.header-icon-large i {
    font-size: 26px !important;
    color: #ff2e93 !important; /* আপনার থিম পিঙ্ক */
    margin-right: 12px;
}

.dynamic-card-box .box-title {
    font-size: 16px !important;
    font-weight: 700 !important;
    margin: 0;
}

/* বডি প্যাডিং */
.dynamic-body {
    padding: 20px !important;
}

body.dark-mode .dynamic-body {
    color: #eeeeee;
}
</style>