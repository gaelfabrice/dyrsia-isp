{include file="sections/header.tpl"}

<style>
/* ১. টাইট লেআউট কন্ট্রোল */
.dashboard-row {
    display: flex !important;
    flex-wrap: wrap !important;
    margin-left: -3px !important;
    margin-right: -3px !important;
}

.dashboard-col {
    padding-left: 3px !important;
    padding-right: 3px !important;
    margin-bottom: 10px;
    box-sizing: border-box !important;
}

/* ২. হটস্পট ওভারভিউ স্টাইল কার্ড */
.pay-stat-card, .action-card {
    min-height: 95px;
    background: #ffffff !important;
    border-radius: 12px;
    padding: 12px 14px !important;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

/* সিগনেচার লেফট স্ট্রিপ (Hotspot Style) */
.pay-left-strip {
    position: absolute;
    left: 0;
    top: 15%;
    width: 4px;
    height: 70%;
    border-radius: 0 10px 10px 0;
    z-index: 10;
}

.pay-card-top {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.pay-card-icon {
    font-size: 32px !important;
    line-height: 1 !important;
    display: flex !important;
    align-items: center;
}

.pay-card-text small {
    font-size: 12px !important;
    font-weight: 800;
    text-transform: uppercase;
    color: #8898aa;
}

.pay-stat-card h4 {
    font-size: 30px !important;
    margin: 8px 0 0 2px !important;
    font-weight: 800;
    color: #32325d;
}

/* ৩. ইনপুট ও বাটন ডিজাইন */
.card-form-input { 
    width: 100%; 
    margin-top: 8px; 
    padding: 8px 12px; 
    border-radius: 8px; 
    border: 1px solid #e0e0e0; 
    font-size: 13px;
}
.card-btn { 
    margin-top: 10px; 
    width: 100%; 
    padding: 8px; 
    border-radius: 8px; 
    border: none; 
    font-weight: 700; 
    color: #fff; 
    text-transform: uppercase;
    cursor: pointer;
}

/* টেবিল বক্সের ডিজাইন - উপরে ফুল স্ট্রিপ সহ */
.table-card-modern {
    background: #ffffff !important;
    border-radius: 14px !important;
    position: relative;
    overflow: hidden !important;
    border: 1px solid rgba(0,0,0,0.06) !important;
    padding: 25px 20px 20px 20px; /* উপরে স্ট্রিপের জন্য সামান্য গ্যাপ রাখা হয়েছে */
    box-shadow: 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* টেবিল বক্সের একদম উপরের ফুল উইডথ স্ট্রিপ */
.table-card-modern::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;       /* বাম থেকে ডানে পুরোটা */
    height: 5px;       /* স্ট্রিপের পুরুত্ব */
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 14px 14px 0 0; /* বক্সের কোণার সাথে মিল রেখে কার্ভ */
    z-index: 10;
}

/* ডার্ক মোড সাপোর্ট */
body.dark-mode .pay-stat-card, body.dark-mode .action-card, body.dark-mode .table-card-modern { 
    background: #161b2e !important; 
    border-color: #232a42; 
}
body.dark-mode h4, body.dark-mode .pay-stat-card h4 { color: #ffffff !important; }
body.dark-mode .card-form-input { background: #1e293b !important; color: #fff !important; border-color: #334155 !important; }
/* সার্চ বক্স ডার্ক মোড ফিক্স */
body.dark-mode #historySearch {
    background: #1e293b !important;
    color: #ffffff !important;
    border-color: #334155 !important;
}

body.dark-mode #historySearch::placeholder {
    color: #94a3b8 !important; /* প্লেসহোল্ডার কালার হালকা করা */
}

/* টেবিল হেডার আইকন এনিমেশন (ঐচ্ছিক) */
.history-title-icon {
    font-size: 20px;
    background: linear-gradient(90deg, #ff2e93, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-right: 10px;
}
/* রিফ্রেশ বাটন স্টাইল */
.btn-refresh {
    background: #f6f9fc;
    border: 1px solid #e0e0e0;
    color: #5e72e4;
    padding: 8px 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 700;
    font-size: 12px;
}

.btn-refresh:hover {
    background: #5e72e4;
    color: #fff;
    box-shadow: 0 4px 10px rgba(94, 114, 228, 0.2);
}

/* আইকন কালার ম্যাচ */
.icon-match {
    color: #ff2e93;
    font-size: 26px !important;
    vertical-align: middle;
}

/* সার্চ বক্স ডার্ক মোড ফিক্স */
body.dark-mode #historySearch {
    background: #1e293b !important;
    color: #ffffff !important;
    border-color: #334155 !important;
}
body.dark-mode .btn-refresh {
    background: #232a42;
    border-color: #334155;
    color: #8965e0;
}
/* ডার্ক মোডে হেডিং এবং টেক্সট কালার ফিক্স */
body.dark-mode h3, 
body.dark-mode h4, 
body.dark-mode .history-title-text { 
    color: #ffffff !important; 
}

body.dark-mode .pay-card-text small {
    color: #a0aec0 !important; /* হালকা গ্রে যাতে ডার্ক মোডে চোখে না লাগে */
}

/* টাইটেল আইকন ডার্ক মোডে উজ্জ্বল দেখানোর জন্য */
body.dark-mode .icon-match {
    color: #ff4da6 !important; /* সামান্য উজ্জ্বল পিঙ্ক */
    text-shadow: 0 0 8px rgba(255, 77, 166, 0.3);
}
</style>

<div class="container-fluid">
    <h3 style="font-weight: 800; margin-bottom: 20px; color: #32325d;">
        <i class="ion ion-wifi" style="color: #ff2e93;"></i> MAC Management
    </h3>

    <div class="row dashboard-row">
        <div class="col-md-4 col-xs-12 dashboard-col">
            <div class="pay-stat-card" style="box-shadow: inset 4px 0 0 #f5365c;">
                <div class="pay-left-strip" style="background: #f5365c;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#f5365c;"><i class="ion ion-ios-cloud-download"></i></div>
                    <div class="pay-card-text"><small>Used MAC</small></div>
                </div>
                <h4>{$user.mac_used}</h4>
            </div>
        </div>

        <div class="col-md-4 col-xs-12 dashboard-col">
            <div class="pay-stat-card" style="box-shadow: inset 4px 0 0 #2dce89;">
                <div class="pay-left-strip" style="background: #2dce89;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#2dce89;"><i class="ion ion-ios-checkmark"></i></div>
                    <div class="pay-card-text"><small>Remaining</small></div>
                </div>
                <h4>{$user.mac_limit - $user.mac_used}</h4>
            </div>
        </div>

        <div class="col-md-4 col-xs-12 dashboard-col">
            <div class="pay-stat-card" style="box-shadow: inset 4px 0 0 #11cdef;">
                <div class="pay-left-strip" style="background: #11cdef;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#11cdef;"><i class="ion ion-ios-speedometer"></i></div>
                    <div class="pay-card-text"><small>Total Limit</small></div>
                </div>
                <h4>{$user.mac_limit}</h4>
            </div>
        </div>
    </div>

    <div class="row dashboard-row" style="margin-top: 10px;">
        <div class="col-md-4 col-xs-12 dashboard-col">
            <form id="limitForm" class="action-card" style="box-shadow: inset 4px 0 0 #5e72e4;">
                <div class="pay-left-strip" style="background: #5e72e4;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#5e72e4; font-size: 24px !important;"><i class="ion ion-plus"></i></div>
                    <div class="pay-card-text"><small>INCREASE LIMIT</small></div>
                </div>
                <input type="text" name="phone" class="card-form-input" placeholder="Phone Number" required>
                <input type="number" name="add_limit" class="card-form-input" placeholder="Amount" required>
                <button type="submit" class="card-btn" style="background: #5e72e4;">Update Limit</button>
            </form>
        </div>

        <div class="col-md-4 col-xs-12 dashboard-col">
            <form id="decreaseForm" class="action-card" style="box-shadow: inset 4px 0 0 #fb6340;">
                <div class="pay-left-strip" style="background: #fb6340;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#fb6340; font-size: 24px !important;"><i class="ion ion-minus"></i></div>
                    <div class="pay-card-text"><small>DECREASE LIMIT</small></div>
                </div>
                <input type="text" name="phone" class="card-form-input" placeholder="Phone Number" required>
                <input type="number" name="subtract_limit" class="card-form-input" placeholder="Amount" required>
                <button type="submit" class="card-btn" style="background: #fb6340;">Subtract Limit</button>
            </form>
        </div>

        <div class="col-md-4 col-xs-12 dashboard-col">
            <form id="resetForm" class="action-card" style="box-shadow: inset 4px 0 0 #8965e0;">
                <div class="pay-left-strip" style="background: #8965e0;"></div>
                <div class="pay-card-top">
                    <div class="pay-card-icon" style="color:#8965e0; font-size: 24px !important;"><i class="ion ion-refresh"></i></div>
                    <div class="pay-card-text"><small>RESET SINGLE USER</small></div>
                </div>
                <input type="text" name="phone" class="card-form-input" placeholder="Phone Number" required>
                <button type="submit" class="card-btn" style="background: #8965e0; margin-top: 48px;">Reset Now</button>
            </form>
        </div>
    </div>

    <div class="action-card" id="resetAllBtn" style="cursor: pointer; text-align: center; padding: 15px !important; margin-top: 10px; border: 1px dashed #f5365c !important;">
        <i class="ion ion-android-sync" style="color: #f5365c; font-size: 24px; vertical-align: middle;"></i>
        <span style="font-weight: 800; color: #f5365c; margin-left: 10px; text-transform: uppercase;">Global Reset (Full History Clear)</span>
    </div>

<div class="table-card-modern" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="ion ion-ios-timer-outline icon-match"></i>
            <h4 style="font-weight: 800; margin: 0; color: #32325d;">MAC Update History</h4>
            
            <button type="button" onclick="location.reload();" class="btn-refresh" title="Refresh Table">
                <i class="ion ion-android-refresh"></i> Refresh
            </button>
        </div>
        
        <div style="position: relative;">
            <i class="ion ion-ios-search" style="position: absolute; left: 12px; top: 9px; color: #8898aa;"></i>
            <input type="text" id="historySearch" placeholder="Search history..." 
                   style="padding: 8px 15px 8px 35px; border-radius: 20px; border: 1px solid #ddd; font-size: 13px; width: 250px; outline: none;">
        </div>
    </div>

        <form id="deleteForm">
            <div class="table-responsive">
                <table class="table table-hover" id="macHistoryTable">
                    <thead style="background: #f6f9fc; font-size: 11px; text-transform: uppercase; color: #8898aa;">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Old MAC</th>
                            <th>New MAC</th>
                            <th>Updated By</th>
                            <th>Date</th>
                            <th>Used</th>
                            <th>Limit</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        {foreach from=$mac_history item=row}
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{$row.id}"></td>
                            <td>{$row.id}</td>
                            <td><b>{$row.customer_name|default:'N/A'}</b></td>
                            <td>{$row.phone}</td>
                            <td><small>{$row.old_mac|default:'None'}</small></td>
                            <td><code style="color: #5e72e4;">{$row.new_mac}</code></td>
                            <td><span class="badge" style="background:#8898aa;">{$row.updated_by}</span></td>
                            <td>{$row.updated_at}</td>
                            <td><span class="label label-danger">{$row.mac_used}</span></td>
                            <td><span class="label label-success">{$row.mac_limit}</span></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-sm btn-danger" style="margin-top: 15px; border-radius: 8px; font-weight: 700;">
                <i class="ion ion-trash-b"></i> Delete Selected
            </button>
        </form>
    </div>
</div>

<script>
// Select All Checkbox
document.getElementById('selectAll').addEventListener('change', function(){
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
});

// Search functionality
document.getElementById('historySearch').addEventListener('input', function(){
    let q = this.value.toLowerCase();
    document.querySelectorAll('#macHistoryTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Form Submissions
const handleForm = (formId, actionName) => {
    document.getElementById(formId).addEventListener('submit', function(e){
        e.preventDefault();
        let fd = new FormData(this); fd.append('action', actionName);
        fetch('', { method:'POST', body:fd }).then(res => res.json()).then(data => { alert(data.message); location.reload(); });
    });
};

handleForm('limitForm', 'limit');
handleForm('decreaseForm', 'decrease_limit');
handleForm('resetForm', 'reset_mac');

// Global Reset
document.getElementById('resetAllBtn').addEventListener('click', function(){
    if(!confirm("Warning: Reset ALL entries?")) return;
    let fd = new FormData(); fd.append('action','reset_all');
    fetch('', { method:'POST', body:fd }).then(res => res.json()).then(data => { alert(data.message); location.reload(); });
});

// Bulk Delete with Selection Check
document.getElementById('deleteForm').addEventListener('submit', function(e){
    e.preventDefault();
    
    // চেক করা হচ্ছে কোনো চেকবক্স সিলেক্ট করা হয়েছে কি না
    let checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
    
    if (checkedBoxes.length === 0) {
        // যদি কোনোটি সিলেক্ট করা না থাকে তবে এই মেসেজটি দিবে
        alert("দয়া করে ডিলিট করার জন্য অন্তত একটি ডাটা সিলেক্ট করুন!");
        return; // কাজ এখানেই থামিয়ে দিবে
    }

    // যদি সিলেক্ট করা থাকে তবে কনফার্মেশন চাইবে
    if(!confirm("আপনি কি নিশ্চিতভাবে " + checkedBoxes.length + " টি ডাটা ডিলিট করতে চান?")) return;
    
    let fd = new FormData(this); 
    fd.append('action','delete');
    
    fetch('', { 
        method:'POST', 
        body:fd 
    }).then(res => res.json()).then(data => { 
        alert(data.message); 
        location.reload(); 
    });
});
</script>

{include file="sections/footer.tpl"}