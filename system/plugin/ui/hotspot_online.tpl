{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-success">

            <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">

                <div style="display:flex; align-items:center; gap:10px;">
<span style="font-size:15px;">
    <i class="fa fa-wifi" style="margin-right:6px;"></i>
    Hotspot Online Users
</span>

                    <span class="label label-success" style="font-size:12px;">
                        {$online_users|@count} Online
                    </span>

                    <button id="refreshBtn" class="btn btn-xs btn-primary">
                        <i class="glyphicon glyphicon-refresh"></i> Refresh
                    </button>
                </div>

<div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">

    <!-- Search Row -->
    <div style="display:flex; align-items:center; gap:5px;">
        <input type="text" id="searchBox" class="form-control input-sm" 
               placeholder="Search # / Name / IP / MAC..." 
               style="width:220px; height:34px;">

        <button class="btn btn-sm btn-primary d-flex align-items-center" 
                style="height:34px; padding:0 12px;" 
                onclick="triggerSearch()">
            <i class="glyphicon glyphicon-search" style="margin-right:5px;"></i>
            Search
        </button>
    </div>

    <!-- নিচে Name + Number button -->
    <div style="display:flex; gap:5px;">
        
        <button class="btn btn-xs btn-info d-flex align-items-center" 
                style="padding:3px 8px; font-size:12px;" 
                onclick="sortByName()">
            <i class="glyphicon glyphicon-user" style="margin-right:3px;"></i>
            Name
        </button>

        <button class="btn btn-xs btn-warning d-flex align-items-center" 
                style="padding:3px 8px; font-size:12px;" 
                onclick="sortByNumber()">
            <i class="glyphicon glyphicon-earphone" style="margin-right:3px;"></i>
            Number
        </button>

    </div>

</div>

            </div>

            <div class="panel-body table-responsive">

                {if $online_users|@count > 0}

                <table class="table table-striped table-hover" id="userTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Router</th>
                            <th>Username</th>
                            <!-- নতুন কলাম -->
                            <th>Full Name</th>
                            <th>Address</th>
                            <th>IP</th>
                            <th>MAC</th>
                            <th>Service Type</th>
                            <th>Status</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Total</th>
                            <th>Uptime</th>
                            <th>Live Traffic</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        {assign var="i" value=1}

                        {foreach $online_users as $user}
                            {if $user.service_type == 'Hotspot'}

                            <tr>
                                <td>{$i}</td>
                                <td>{$user.router_name}</td>
<td>
    <a href="?_route=customers/viewu/{$user.name|urlencode}">
        {$user.name}
    </a>
</td>
                                <!-- কাস্টমার ডাটা বসানো হলো -->
                                <td>{$user.fullname|default:'-'}</td>
                                <td>{$user.address|default:'-'}</td>
                                <td>{$user.ip}</td>
                                <td>{$user.mac}</td>
                                <td>{$user.service_type|default:'Hotspot'}</td>

                                <td>
                                    {assign var="stat" value=$user.status|default:'on'}
                                    {if $stat == 'on'}
                                        <span class="label label-success">Online</span>
                                    {elseif $stat == 'off'}
                                        <span class="label label-danger">Offline</span>
                                    {elseif $stat == 'expired'}
                                        <span class="label label-warning">Expired</span>
                                    {else}
                                        <span class="label label-success">Online</span>
                                    {/if}
                                </td>

                                <td>{formatBytes($user.download)}</td>
                                <td>{formatBytes($user.upload)}</td>
                                <td>{formatBytes($user.total)}</td>
                                <td>{$user.uptime}</td>

<td>
    <button class="btn btn-xs btn-success d-flex align-items-center" 
            onclick="openTrafficModal('{$user.router_id}', '{$user.name}', '{$user.service_type}')">
        <i class="bi bi-graph-up me-1"></i> <!-- Traffic Icon -->
        Traffic
    </button>
</td>

<td>
    <button class="btn btn-xs btn-danger d-flex align-items-center" 
            onclick="disconnectUser('{$user.router_id}', '{$user.name}', '{$user.service_type}')">
        <i class="bi bi-power me-1"></i> <!-- Disconnect Icon -->
        Disconnect
    </button>
</td>
                                </td>
                            </tr>

                            {assign var="i" value=$i+1}
                            {/if}
                        {/foreach}
                    </tbody>
                </table>

                {else}
                    <div class="alert alert-warning">
                        No Hotspot users online.
                    </div>
                {/if}

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="trafficModal" tabindex="-1" aria-labelledby="trafficModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">

      <!-- Header -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="trafficModalLabel"><i class="bi bi-graph-up"></i> User Traffic</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeTrafficModal()"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- User Info -->
        <div class="row mb-3 text-center">
          <div class="col-md-4"><strong>Username:</strong> <span id="modalUsername">-</span></div>
          <div class="col-md-4"><strong>Service:</strong> <span id="modalService">-</span></div>
          <div class="col-md-4"><strong>Router:</strong> <span id="modalRouter">-</span></div>
        </div>

        <hr>

<!-- Upload / Download Stats -->
<div class="row text-center mb-3">
  <div class="col-md-6 mb-3">
    <div class="p-3 rounded shadow-sm bg-light">
      <h6 class="mb-2">Download</h6>
      <p id="modalTx" class="text-success fw-bold" style="font-size:2rem;">0 B/s</p>
    </div>
  </div>
  <div class="col-md-6 mb-3">
    <div class="p-3 rounded shadow-sm bg-light">
      <h6 class="mb-2">Upload</h6>
      <p id="modalRx" class="text-danger fw-bold" style="font-size:2rem;">0 B/s</p>
    </div>
  </div>
</div>

        <!-- Graph -->
        <div class="row">
          <div class="col-12 mb-3">
            <canvas id="trafficGraph" height="100"></canvas>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-pink px-5" data-bs-dismiss="modal" onclick="closeTrafficModal()">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  #trafficModal .modal-content { border-radius: 1rem; overflow: hidden; }
  .btn-pink { background-color:#ff69b4; color:white; border:none; }
  .btn-pink:hover{ background-color:#ff85c1; }
</style>

{literal}
<script>
// Refresh button
document.getElementById('refreshBtn').addEventListener('click', function(){ location.reload(); });

// Live search
function triggerSearch(){
    var value = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll("#userTable tbody tr").forEach(function(row){
        var show = Array.from(row.cells).some(td=>td.innerText.toLowerCase().includes(value));
        row.style.display = show ? "" : "none";
    });
}

// Enter key
document.getElementById('searchBox').addEventListener('keyup', function(e){ if(e.key==='Enter') triggerSearch(); });

// Chart
let ctx = document.getElementById('trafficGraph').getContext('2d');
let trafficChart = new Chart(ctx, {
    type: 'line',
    data: { 
        labels: [], 
        datasets: [
            { label:'Download', data:[], borderColor:'green', fill:false, tension:0.2 },
            { label:'Upload', data:[], borderColor:'red', fill:false, tension:0.2 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values){
                        return formatBits(value); // এখন y-axis bps/kbps/mbps/gbps দেখাবে
                    }
                }
            }
        }
    }
});

let trafficInterval = null;
let prevTraffic = {};

// Open Modal
function openTrafficModal(routerId, username, service){
    document.getElementById('modalUsername').innerText = username;
    document.getElementById('modalService').innerText = service;
    document.getElementById('modalRouter').innerText = routerId;
    $('#trafficModal').modal('show');
    updateModalTraffic(routerId, username, service);
    if(trafficInterval) clearInterval(trafficInterval);
    trafficInterval = setInterval(()=>updateModalTraffic(routerId, username, service), 2000);
}

// Close Modal
function closeTrafficModal(){
    clearInterval(trafficInterval);
    trafficChart.data.labels = [];
    trafficChart.data.datasets[0].data = [];
    trafficChart.data.datasets[1].data = [];
    trafficChart.update();
    $('#trafficModal').modal('hide');
}

// Update traffic
function updateModalTraffic(routerId, username, service){
    fetch('index.php?_route=plugin/hotspot_online_ui&ajax=1')
    .then(res=>res.json())
    .then(users=>{
        let user = users.find(u=>u.router_id==routerId && u.name==username);
        if(!user) return;

        let now = Date.now();

        if(!prevTraffic[user.id]){
            prevTraffic[user.id] = {
                upload: user.upload,
                download: user.download,
                time: now
            };
            return;
        }

        let prev = prevTraffic[user.id];
        let timeDiff = (now - prev.time) / 1000;

        let txRate = (user.upload - prev.upload) / timeDiff;
        let rxRate = (user.download - prev.download) / timeDiff;

        prevTraffic[user.id] = {
            upload: user.upload,
            download: user.download,
            time: now
        };

document.getElementById('modalTx').innerText = formatBits(txRate);
document.getElementById('modalRx').innerText = formatBits(rxRate);

        let timeLabel = new Date().toLocaleTimeString();

        if(trafficChart.data.labels.length>20){
            trafficChart.data.labels.shift();
            trafficChart.data.datasets[0].data.shift();
            trafficChart.data.datasets[1].data.shift();
        }

        trafficChart.data.labels.push(timeLabel);
trafficChart.data.datasets[0].data.push(txRate); // upload
trafficChart.data.datasets[1].data.push(rxRate); // download
trafficChart.update();
    });
}
// Format bytes
function formatBits(bytesPerSec){
    if(bytesPerSec === 0) return '0 bps';
    let bits = bytesPerSec * 8; // bytes → bits
    let units = ['bps','Kbps','Mbps','Gbps','Tbps'];
    let i = Math.floor(Math.log(bits)/Math.log(1000));
    i = Math.min(i, units.length - 1);
    return (bits / Math.pow(1000,i)).toFixed(2) + ' ' + units[i];
}
// Disconnect user
function disconnectUser(routerId, username, service){
    if(!confirm(`Are you sure you want to disconnect ${username}?`)) return;

    fetch(`index.php?_route=plugin/disconnect_user&router_id=${routerId}&user_id=${username}&service_type=${service}`)
    .then(res => res.json())
    .then(data => {
        if(data.status){
            alert('User disconnected!');
            location.reload();
        } else {
            alert('Error: ' + data.msg);
        }
    }).catch(err=>{
        alert('AJAX error: '+err);
    });
}
</script>
{/literal}

<script>
    window.addEventListener('DOMContentLoaded', function () {
        var portalLink = "#";
        $('#version').html('Hotspot Online Users Plugin | Ver: 1.0 | by: <a href="' + portalLink + '">Dyrsia</a>');
    });
</script>

<script>
// Existing search & traffic JS ...

// Place these functions here
function sortByName() {
    let table = document.getElementById("userTable").getElementsByTagName("tbody")[0];
    let rows = Array.from(table.rows);
    rows.sort((a, b) => a.cells[2].innerText.toLowerCase().localeCompare(b.cells[2].innerText.toLowerCase()));
    rows.forEach(row => table.appendChild(row));
}

function sortByNumber() {
    let table = document.getElementById("userTable").getElementsByTagName("tbody")[0];
    let rows = Array.from(table.rows);
    rows.sort((a, b) => parseInt(a.cells[0].innerText) - parseInt(b.cells[0].innerText));
    rows.forEach(row => table.appendChild(row));
}
</script>

{include file="sections/footer.tpl"}