{include file="sections/header.tpl"}

<style>
/* ১. জেনারেল লেআউট */
.content-wrapper, .content { padding: 10px !important; }
.dashboard-row { display: flex !important; flex-wrap: wrap !important; margin: 0 -8px 20px -8px !important; }
.dashboard-col { width: 25% !important; padding: 8px !important; box-sizing: border-box !important; }

@media (max-width: 992px) { .dashboard-col { width: 50% !important; } }
@media (max-width: 576px) { .dashboard-col { width: 100% !important; } }

/* ২. স্ট্যাটাস কার্ডস */
.dashboard-card {
    background: #ffffff; border-radius: 16px; height: 95px;
    display: flex; align-items: center; position: relative;
    overflow: hidden; border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.left-strip { position: absolute; left: 0; top: 15%; width: 4px; height: 70%; border-radius: 0 10px 10px 0; }
.card-icon { width: 55px; height: 55px; font-size: 38px !important; margin: 0 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.card-text small { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; display: block; letter-spacing: 0.5px; }
.card-text h4 { margin: 0; font-size: 26px; font-weight: 800; color: #111; }

/* ৩. রেসপন্সিভ অ্যাকশন বার */
.action-bar {
    display: flex; justify-content: space-between; align-items: center;
    background: #fff; padding: 15px; border-radius: 16px; margin-bottom: 20px;
    border: 1px solid #eee; flex-wrap: wrap; gap: 12px;
}
.flex-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
@media (max-width: 768px) {
    .action-bar { flex-direction: column; align-items: stretch; }
    .flex-group { width: 100%; }
    .flex-group input, .flex-group select { flex: 1; }
}

.btn-custom { height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: none; color: #fff; padding: 0 18px; font-weight: 600; cursor: pointer; }
.btn-filter { background: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); }
.btn-refresh { background: #6366f1; width: 42px; }
.btn-delete { background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }

/* ৪. ডার্ক মোড সেটিংস */
body.dark-mode .dashboard-card, body.dark-mode .action-bar, body.dark-mode .main-card, body.dark-mode .modal-box {
    background: #161b2e !important; border-color: #232a42 !important;
}
body.dark-mode .card-text h4, body.dark-mode h3 { color: #fff !important; }
body.dark-mode .form-control { background: #0d0f14 !important; border-color: #2e3754 !important; color: #fff !important; }
</style>

<div class="container-fluid">
    <h3 style="font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
        <i class="ion ion-wifi" style="color: #ff2e93;"></i> Hotspot Login Management
    </h3>

    <div class="dashboard-row">
        <div class="dashboard-col">
            <div class="dashboard-card" style="box-shadow: inset 4px 0 0 #3b82f6;">
                <div class="left-strip" style="background: #3b82f6;"></div>
                <div class="card-icon" style="color: #3b82f6;"><i class="ion ion-ios-list"></i></div>
                <div class="card-text"><small>Total</small><h4>{$total_requests|default:0}</h4></div>
            </div>
        </div>
        <div class="dashboard-col">
            <div class="dashboard-card" style="box-shadow: inset 4px 0 0 #10b981;">
                <div class="left-strip" style="background: #10b981;"></div>
                <div class="card-icon" style="color: #10b981;"><i class="ion ion-paper-airplane"></i></div>
                <div class="card-text"><small>Today</small><h4>{$today_requests|default:0}</h4></div>
            </div>
        </div>
        <div class="dashboard-col">
            <div class="dashboard-card" style="box-shadow: inset 4px 0 0 #f59e0b;">
                <div class="left-strip" style="background: #f59e0b;"></div>
                <div class="card-icon" style="color: #f59e0b;"><i class="ion ion-load-a"></i></div>
                <div class="card-text"><small>Pending</small><h4>{$pending_count|default:0}</h4></div>
            </div>
        </div>
        <div class="dashboard-col">
            <div class="dashboard-card" style="box-shadow: inset 4px 0 0 #8b5cf6;">
                <div class="left-strip" style="background: #8b5cf6;"></div>
                <div class="card-icon" style="color: #8b5cf6;"><i class="ion ion-checkmark-circled"></i></div>
                <div class="card-text"><small>Approved</small><h4>{$approved_count|default:0}</h4></div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <form class="flex-group" method="GET">
            <input type="hidden" name="_route" value="plugin/hotspot_login_request">
            <input type="text" name="search" class="form-control" style="width: 200px; border-radius: 10px;" placeholder="Search MAC/Phone..." value="{$smarty.get.search|default:''}">
            <select name="status" class="form-control" style="width: 130px; border-radius: 10px;">
                <option value="">All Status</option>
                <option value="pending" {if $smarty.get.status=='pending'}selected{/if}>Pending</option>
                <option value="approved" {if $smarty.get.status=='approved'}selected{/if}>Approved</option>
            </select>
            <button type="submit" class="btn-custom btn-filter">Filter</button>
        </form>
        <div class="flex-group">
            <button type="button" class="btn-custom btn-refresh" onclick="location.reload()"><i class="fa fa-refresh"></i></button>
            <button type="button" class="btn-custom btn-delete" onclick="deleteSelected()">Delete Selected</button>
        </div>
    </div>

    <div class="main-card" style="background: #fff; border-radius: 20px; padding: 10px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <div class="table-responsive">
            <table class="table table-hover" style="vertical-align: middle;">
                <thead>
                    <tr style="color: #64748b; font-size: 11px; text-transform: uppercase;">
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>Router</th>
                        <th>MAC Address</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Time Info</th>
                        <th>Expires On</th>
                        <th>Approved By</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody style="font-size: 13px;">
                    {foreach $requests as $r}
                    <tr>
                        <td><input type="checkbox" class="selectRequest" value="{$r.id}"></td>
                        <td style="font-weight: 700; color: #3b82f6;">{$r.router}</td>
                        <td><code style="background: #f1f5f9; padding: 3px 7px; border-radius: 6px; color: #475569;">{$r.mac}</code></td>
                        <td>{$r.phone|default:'-'}</td>
                        <td>
                            {if $r.status == 'pending'}
                                <span class="label" style="background:#fff7ed; color:#c2410c; border:1px solid #fdba74; border-radius:20px; padding:3px 12px;">Pending</span>
                            {else}
                                <span class="label" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:20px; padding:3px 12px;">Approved</span>
                            {/if}
                        </td>
                        <td><span class="badge" style="background: #334155; padding: 5px 10px;">{$r.plan_name|default:'-'}</span></td>
                        <td style="color: #888; font-size: 12px;">{$r.pending_at}</td>
                        <td>{if $r.expiration}<b class="text-danger">{$r.expiration}</b>{else}-{/if}</td>
                        <td>
    {if $r.approved_by}
        <span style="font-weight:600; color:#10b981;">{$r.approved_by}</span>
    {else}
        -
    {/if}
</td>
                        <td class="text-right">
                            {if $r.status == 'pending'}
                            <button class="btn btn-primary btn-sm" style="border-radius: 8px; font-weight: 600;" onclick="openApproveModal({$r.id})">Approve</button>
                            {/if}
                        </td>
                    </tr>
                    {foreachelse}
                    <tr><td colspan="9" class="text-center text-muted" style="padding: 50px 0;">No login requests found.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="approveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; backdrop-filter: blur(8px);">
    <div class="modal-box" style="width: 90%; max-width: 400px; margin: 10% auto; padding: 30px; border-radius: 24px; background: #fff; text-align: center;">
        <div style="font-size: 65px; color: #10b981; margin-bottom: 15px;"><i class="ion ion-checkmark-circled"></i></div>
        <h3 style="font-weight: 800; margin-bottom: 25px; color: #1e293b;">Activate User</h3>
        <input type="hidden" id="req_id">
        <div class="form-group text-left" style="margin-bottom: 15px;">
            <label style="font-weight: 600; color: #64748b; margin-bottom: 8px; display: block;">Customer Name</label>
            <input type="text" id="cust_name" class="form-control" style="height: 45px; border-radius: 12px;" placeholder="Enter Name">
        </div>
        <div class="form-group">
    <label>Address</label>
    <input type="text" id="cust_address" name="address" class="form-control" placeholder="Enter Address">
</div>
        <div class="form-group text-left" style="margin-bottom: 20px;">
            <label style="font-weight: 600; color: #64748b; margin-bottom: 8px; display: block;">Select Service Plan</label>
            <select id="plan_id" class="form-control" style="height: 45px; border-radius: 12px;">
                <option value="">-- Choose a Plan --</option>
                {foreach $plans as $p}
                <option value="{$p.id}">{$p.name_plan} - {$_c['currency_code']} {$p.price}</option>
                {/foreach}
            </select>
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-success" style="flex: 1.5; height: 48px; font-weight: 700; border-radius: 14px;" onclick="approveUser()">ACTIVATE NOW</button>
            <button class="btn btn-default" style="flex: 1; height: 48px; border-radius: 14px;" onclick="closeModal()">CANCEL</button>
        </div>
    </div>
</div>

<script>
function openApproveModal(id){ 
    document.getElementById('approveModal').style.display='block'; 
    document.getElementById('req_id').value=id; 
}
function closeModal(){ 
    document.getElementById('approveModal').style.display='none'; 
}
function approveUser(){
    let id = document.getElementById('req_id').value;
    let plan = document.getElementById('plan_id').value;
    let name = document.getElementById('cust_name').value || 'New User';
    
    // ১. অ্যাড্রেস ইনপুট ফিল্ড থেকে ভ্যালু সংগ্রহ
    let address = document.getElementById('cust_address').value || ''; 

    if(!plan) return alert("Please select a plan first!");

    let fd = new FormData();
    fd.append('activate_request', 1);
    fd.append('request_id', id);
    fd.append('plan_id', plan);
    fd.append('fullname', name);
    
    // ২. FormData-তে অ্যাড্রেসটি পাঠিয়ে দেওয়া
    fd.append('address', address); 

    fetch('', { method:'POST', body: fd }).then(res=>res.json()).then(d=>{
        if(d.status==='success') location.reload(); else alert(d.message);
    });
}
document.getElementById('selectAll').addEventListener('change', function(){
    document.querySelectorAll('.selectRequest').forEach(c => c.checked = this.checked);
});
function deleteSelected(){
    let ids = Array.from(document.querySelectorAll('.selectRequest:checked')).map(c => c.value);
    if(!ids.length) return alert("Please select at least one record.");
    if(!confirm("Are you sure?")) return;
    let fd = new FormData(); fd.append('delete_requests', 1); fd.append('request_ids', ids.join(','));
    fetch('', { method:'POST', body: fd }).then(() => location.reload());
}
</script>

{include file="sections/footer.tpl"}