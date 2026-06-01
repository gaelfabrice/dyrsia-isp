{if $_c['disable_voucher'] != 'yes' && ($stocks['unused']>0 || $stocks['used']>0)}
<div class="box box-solid exceptional-box voucher-stock-box">
    <div class="box-header">
        <div class="header-icon-large"><i class="fa fa-ticket"></i></div>
        <h3 class="box-title">Vouchers Stock</h3>
    </div>
    
    <div class="box-body no-padding">
        <div class="table-responsive">
            <table class="table table-hover voucher-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Package Name')}</th>
                        <th class="text-center">Unused</th>
                        <th class="text-center">Used</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $plans as $stok}
                        <tr>
                            <td class="package-name">{$stok['name_plan']}</td>
                            <td class="text-center"><span class="badge badge-unused">{$stok['unused']}</span></td>
                            <td class="text-center"><span class="badge badge-used">{$stok['used']}</span></td>
                        </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>Total Stock</strong></td>
                        <td class="text-center"><strong>{$stocks['unused']}</strong></td>
                        <td class="text-center"><strong>{$stocks['used']}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
{/if}

<style>
/* ==========================================
   VOUCHER STOCK BOX - EXCEPTIONAL STYLE
========================================== */
.voucher-stock-box {
    background: #ffffff !important;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* ৪ পিক্সেল চিকন পিঙ্ক-ব্লু স্ট্রিপ */
.voucher-stock-box::before {
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
body.dark-mode .voucher-stock-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* হেডার এবং আইকন */
.voucher-stock-box .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.voucher-stock-box .header-icon-large i {
    font-size: 26px !important;
    color: #ff2e93 !important;
    margin-right: 12px;
}

.voucher-stock-box .box-title {
    font-size: 16px !important;
    font-weight: 700 !important;
}

/* টেবিল ডিজাইন */
.voucher-table {
    margin-bottom: 0 !important;
}

.voucher-table thead th {
    background: rgba(0,0,0,0.02);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    padding: 12px 20px !important;
}

body.dark-mode .voucher-table thead th {
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
}

.voucher-table td {
    padding: 12px 20px !important;
    vertical-align: middle !important;
    border-top: 1px solid rgba(0,0,0,0.03) !important;
}

.package-name {
    font-weight: 600;
    color: #555;
}

body.dark-mode .package-name {
    color: #ddd;
}

/* ব্যাজ স্টাইল */
.badge-unused {
    background: rgba(0, 208, 132, 0.1) !important;
    color: #00d084 !important;
    border: 1px solid rgba(0, 208, 132, 0.2);
    font-weight: 700;
}

.badge-used {
    background: rgba(255, 46, 147, 0.1) !important;
    color: #ff2e93 !important;
    border: 1px solid rgba(255, 46, 147, 0.2);
    font-weight: 700;
}

/* টোটাল রো */
.total-row {
    background: rgba(0, 188, 212, 0.05);
}

body.dark-mode .total-row {
    background: rgba(0, 188, 212, 0.1);
}
</style>