{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-success">

            <div class="panel-heading d-flex justify-content-between align-items-center">

                <!-- Left side: PPPoE Online + icon + count + refresh -->
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:15px;">
                        <i class="glyphicon glyphicon-random" style="margin-right:6px;"></i>
                        PPPoE Online Users
                    </span>

                    <span class="label label-success" style="font-size:12px;">
                        {$online_users|@count} Online
                    </span>

                    <button id="refreshBtn" class="btn btn-xs btn-primary">
                        <i class="glyphicon glyphicon-refresh"></i> Refresh
                    </button>
                </div>

                <!-- Right side: Search -->
                <div style="display:flex; align-items:center; gap:5px;">
                    <input type="text" id="searchBox" class="form-control input-sm"
                           placeholder="Search # / Name / IP / MAC..." style="width:220px;">
                    <button class="btn btn-xs btn-primary" onclick="triggerSearch()">
                        <i class="glyphicon glyphicon-search"></i> Search
                    </button>
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
                        {foreach $online_users as $i => $user}
                        <tr>
                            <td>{$i+1}</td>
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
                            <td>{$user.service_type|default:'PPPoE'}</td>

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
                        </tr>
                        {/foreach}
                    </tbody>
                </table>

                {else}
                    <div class="alert alert-warning">
                        No online PPPoE users found.
                    </div>
                {/if}

            </div>
        </div>
    </div>
</div>

<!-- Traffic Modal -->
<div class="modal fade" id="trafficModal" tabindex="-1" aria-labelledby="trafficModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="trafficModalLabel"><i class="bi bi-graph-up"></i> User Traffic</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                aria-label="Close" onclick="closeTrafficModal()"></button>
      </div>

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

      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-pink px-5" data-bs-dismiss="modal" onclick="closeTrafficModal()">Close</button>
      </div>

    </div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
  #trafficModal .modal-content { border-radius: 1rem; overflow: hidden; }
  .btn-pink { background-color: #ff69b4; color: white; border: none; }
  .btn-pink:hover { background-color: #ff85c1; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{literal}
<script>
// Refresh
document.getElementById('refreshBtn').addEventListener('click', function(){ location.reload(); });

// Search
function triggerSearch(){
    var value = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll("#userTable tbody tr").forEach(function(row){
        var show = Array.from(row.cells).some(td => td.innerText.toLowerCase().includes(value));
        row.style.display = show ? "" : "none";
    });
}
document.getElementById('searchBox').addEventListener('keyup', function(e){ if(e.key==='Enter') triggerSearch(); });

// Disconnect
function disconnectUser(routerId, username, service){
    if(!confirm('Disconnect ' + username + '?')) return;
    fetch('index.php?_route=plugin/disconnect_user&router_id='+routerId+'&user_id='+username+'&service_type='+service)
    .then(res=>res.json())
    .then(data=>{
        if(data.status){ alert(username+' disconnected!'); location.reload(); }
        else { alert('Error: '+data.msg); }
    }).catch(err=>alert('AJAX error: '+err));
}

// Traffic Modal
var trafficChart = null;
var trafficInterval = null;
function openTrafficModal(routerId, username, service){
    document.getElementById('modalUsername').innerText=username;
    document.getElementById('modalService').innerText=service;
    document.getElementById('modalRouter').innerText=routerId;

    $('#trafficModal').modal('show');

const ctx = document.getElementById('trafficGraph').getContext('2d');

trafficChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Download',
                data: [],
                borderColor: 'green',
                fill: false,
                tension: 0.2
            },
            {
                label: 'Upload',
                data: [],
                borderColor: 'red',
                fill: false,
                tension: 0.2
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + formatSpeed(context.raw);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return formatSpeed(value);
                    }
                }
            }
        }
    }
});

    updateModalTraffic(routerId, username, service);
    trafficInterval=setInterval(()=>updateModalTraffic(routerId, username, service),2000);
}

function closeTrafficModal(){
    clearInterval(trafficInterval);
    if(trafficChart){ trafficChart.destroy(); trafficChart=null; }
    $('#trafficModal').modal('hide');
}

function updateModalTraffic(routerId, username, service){
    fetch('index.php?_route=plugin/get_user_traffic&router_id='+routerId+'&username='+username+'&service_type='+service)
    .then(res=>res.json())
    .then(data=>{
        // Modal-এ human-readable display
document.getElementById('modalTx').innerText = formatSpeed(data.tx_rate);
document.getElementById('modalRx').innerText = formatSpeed(data.rx_rate);

        if(!trafficChart) return;

        if(trafficChart.data.labels.length>20){
            trafficChart.data.labels.shift();
trafficChart.data.datasets[0].label = 'Upload (' + formatSpeed(data.tx_rate) + ')';
trafficChart.data.datasets[1].label = 'Download (' + formatSpeed(data.rx_rate) + ')';
        }

        trafficChart.data.labels.push(new Date().toLocaleTimeString());
        trafficChart.data.datasets[0].data.push(data.tx_rate);
        trafficChart.data.datasets[1].data.push(data.rx_rate);

        // এখানে tooltip-এ human-readable দেখাবে
trafficChart.options.plugins.tooltip.callbacks = {
    label: function(context) {
        let val = context.raw;
        return context.dataset.label + ': ' + formatSpeed(val);
    }
};

        trafficChart.update();
    });
}

// Helper function, bps কে human-readable এ convert করবে
function formatSpeed(bytesPerSec) {
    if (bytesPerSec === 0) return '0 bps';
    const k = 1024;
    const sizes = ['bps','Kbps','Mbps','Gbps','Tbps'];
    const i = Math.floor(Math.log(bytesPerSec) / Math.log(k));
    return parseFloat((bytesPerSec / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
{/literal}

<script>
    window.addEventListener('DOMContentLoaded', function () {
        var portalLink = "#";
        $('#version').html('PPPoE Online Users Plugin | Ver: 1.0 | by: <a href="' + portalLink + '">Dyrsia</a>');
    });
</script>
{include file="sections/footer.tpl"}