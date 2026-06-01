{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading"><i class="fa fa-bar-chart"></i> {Lang::T('Data Usage')}</div>
            <div class="panel-body">
                <div class="row">
                    {if $_admin['user_type'] eq 'SuperAdmin'}
                    <div class="col-md-2">
                        <label>{Lang::T('Admin')}</label>
                        <select id="admin-id" class="form-control">
                            <option value="">{Lang::T('All Admins')}</option>
                            {foreach $admins as $a}
                                <option value="{$a.id}">{$a.fullname|default:$a.username}</option>
                            {/foreach}
                        </select>
                    </div>
                    {/if}
                    <div class="col-md-2">
                        <label>{Lang::T('Router')}</label>
                        <select id="router" class="form-control">
                            <option value="">{Lang::T('All Routers')}</option>
                            {foreach $routers as $r}
                                <option value="{$r.name}">{$r.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>{Lang::T('User')}</label>
                        <input type="text" id="q" class="form-control" placeholder="Username, name, phone">
                    </div>
                    <div class="col-md-2">
                        <label>{Lang::T('Start Date')}</label>
                        <input type="date" id="start-date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>{Lang::T('End Date')}</label>
                        <input type="date" id="end-date" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" onclick="loadUsage()"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4"><div class="small-box bg-aqua"><div class="inner"><h3 id="total-download">0</h3><p>{Lang::T('Download')}</p></div><div class="icon"><i class="fa fa-download"></i></div></div></div>
    <div class="col-md-4"><div class="small-box bg-red"><div class="inner"><h3 id="total-upload">0</h3><p>{Lang::T('Upload')}</p></div><div class="icon"><i class="fa fa-upload"></i></div></div></div>
    <div class="col-md-4"><div class="small-box bg-green"><div class="inner"><h3 id="total-combined">0</h3><p>{Lang::T('Total')}</p></div><div class="icon"><i class="fa fa-exchange"></i></div></div></div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Usage Chart')}</div>
            <div class="panel-body"><canvas id="usage-chart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Top Users Today')}</div>
            <div class="panel-body no-padding">
                <table class="table table-striped table-condensed"><thead><tr><th>#</th><th>User</th><th>Download</th></tr></thead><tbody id="top-users"><tr><td colspan="3" class="text-center">Loading...</td></tr></tbody></table>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default panel-hovered panel-stacked mb30">
    <div class="panel-heading">{Lang::T('Usage Details')}</div>
    <div class="panel-body no-padding">
        <table class="table table-bordered table-striped"><thead><tr><th>{Lang::T('Date')}</th><th>{Lang::T('Username')}</th><th>{Lang::T('Status')}</th><th>{Lang::T('Download')}</th><th>{Lang::T('Upload')}</th><th>{Lang::T('Total')}</th></tr></thead><tbody id="usage-rows"><tr><td colspan="6" class="text-center">{Lang::T('No data')}</td></tr></tbody></table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let usageChart = null;
function bytesFromMb(mb) { return Number(mb || 0) * 1048576; }
function formatBytes(bytes) { if (!bytes) return '0 Bytes'; const units = ['Bytes','KB','MB','GB','TB']; let i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), 4); return (bytes / Math.pow(1024, i)).toFixed(i ? 2 : 0) + ' ' + units[i]; }
function apiBase(extra) {
    let url = '{Text::url('reports/data-usage-api')}' + extra;
    const adminEl = document.getElementById('admin-id');
    if (adminEl && adminEl.value) url += '&admin_id=' + encodeURIComponent(adminEl.value);
    return url;
}
function loadTopUsers() {
    fetch(apiBase('&get_top_users=1')).then(r => r.json()).then(res => {
        let html = '';
        if (res.status === 'success' && res.top_users && res.top_users.length) {
            res.top_users.forEach((u, i) => html += '<tr><td>'+(i+1)+'</td><td>'+u.username+'</td><td>'+u.download_formatted+'</td></tr>');
        } else html = '<tr><td colspan="3" class="text-center">No data</td></tr>';
        document.getElementById('top-users').innerHTML = html;
    });
}
function loadUsage() {
    const q = document.getElementById('q').value;
    const router = document.getElementById('router').value;
    const sd = document.getElementById('start-date').value;
    const ed = document.getElementById('end-date').value;
    fetch(apiBase('&q=' + encodeURIComponent(q) + '&router=' + encodeURIComponent(router) + '&start_date=' + encodeURIComponent(sd) + '&end_date=' + encodeURIComponent(ed))).then(r => r.json()).then(res => {
        let rows = '', labels = [], downloads = [], uploads = [], td = 0, tu = 0;
        if (res.status === 'success' && res.data && res.data.length) {
            res.data.slice().reverse().forEach(x => { labels.push(x.date + ' ' + x.username); downloads.push(x.metrics.raw_download_mb); uploads.push(x.metrics.raw_upload_mb); td += bytesFromMb(x.metrics.raw_download_mb); tu += bytesFromMb(x.metrics.raw_upload_mb); });
            res.data.forEach(x => rows += '<tr><td>'+x.date+'</td><td>'+x.username+'</td><td>'+x.status+'</td><td>'+x.metrics.download+'</td><td>'+x.metrics.upload+'</td><td>'+x.metrics.total+'</td></tr>');
        } else rows = '<tr><td colspan="6" class="text-center">No data</td></tr>';
        document.getElementById('usage-rows').innerHTML = rows;
        document.getElementById('total-download').innerText = formatBytes(td);
        document.getElementById('total-upload').innerText = formatBytes(tu);
        document.getElementById('total-combined').innerText = formatBytes(td + tu);
        if (usageChart) usageChart.destroy();
        usageChart = new Chart(document.getElementById('usage-chart').getContext('2d'), { type: 'bar', data: { labels, datasets: [{ label: 'Download MB', data: downloads, backgroundColor: '#3c8dbc' }, { label: 'Upload MB', data: uploads, backgroundColor: '#dd4b39' }] }, options: { responsive: true } });
    });
}
document.addEventListener('DOMContentLoaded', function(){ const t = new Date(); const s = new Date(t.getFullYear(),0,1); document.getElementById('start-date').value = s.toISOString().slice(0,10); document.getElementById('end-date').value = t.toISOString().slice(0,10); loadTopUsers(); loadUsage(); });
</script>

{include file="sections/footer.tpl"}
