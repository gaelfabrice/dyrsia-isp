{include file="sections/header.tpl"}

<style>
{literal}
.rr-page {
    --rr-bg: transparent;
    --rr-card: rgba(15, 23, 42, 0.92);
    --rr-soft: rgba(30, 41, 59, 0.55);
    --rr-text: #e2e8f0;
    --rr-heading: #ffffff;
    --rr-muted: #94a3b8;
    --rr-line: rgba(148, 163, 184, 0.16);
    --rr-brand: #2563eb;
    --rr-success: #16a34a;
    --rr-warning: #d97706;
    --rr-danger: #dc2626;
    --rr-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
    color: var(--rr-text);
    margin: 0 -15px;
    padding: 4px 15px 30px;
}
body.theme-light .rr-page {
    --rr-card: #ffffff;
    --rr-soft: #f1f5f9;
    --rr-text: #1e293b;
    --rr-heading: #0f172a;
    --rr-muted: #64748b;
    --rr-line: #e7ebf0;
    --rr-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
}
.rr-page * { box-sizing: border-box; }

/* Hero */
.rr-hero { display: flex; align-items: center; gap: 16px; margin: 6px 0 22px; }
.rr-hero-ic {
    width: 52px; height: 52px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff; font-size: 24px;
    box-shadow: 0 10px 24px rgba(59, 130, 246, 0.35);
    flex-shrink: 0;
}
.rr-hero h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.02em; color: var(--rr-heading); }
.rr-hero p { margin: 3px 0 0; font-size: 13.5px; color: var(--rr-muted); }

/* Card shell */
.rr-card {
    background: var(--rr-card);
    border: 1px solid var(--rr-line);
    border-radius: 22px;
    box-shadow: var(--rr-shadow);
    overflow: hidden;
}

/* Toolbar */
.rr-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    padding: 16px 22px;
    border-bottom: 1px solid var(--rr-line);
}
.rr-sel {
    display: inline-flex; align-items: center; gap: 9px;
    font-weight: 700; font-size: 14px; color: var(--rr-text);
    background: var(--rr-soft); border: 1px solid var(--rr-line);
    padding: 9px 16px; border-radius: 12px;
}
.rr-sel i { color: var(--rr-muted); }
.rr-sel.active { color: var(--rr-brand); border-color: rgba(37,99,235,.4); }
.rr-sel.active i { color: var(--rr-brand); }
.rr-actions { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.rr-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 16px; border-radius: 12px;
    font-weight: 700; font-size: 13.5px; cursor: pointer;
    border: 1px solid var(--rr-line); background: var(--rr-soft); color: var(--rr-text);
    transition: all .15s ease; white-space: nowrap;
}
.rr-btn:hover { transform: translateY(-1px); }
.rr-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.rr-btn-primary { background: linear-gradient(135deg, #16a34a, #15803d); border-color: transparent; color: #fff; box-shadow: 0 8px 20px rgba(22,163,74,.3); }
.rr-btn-ghost i { color: var(--rr-muted); }

/* Table */
.rr-table-wrap { overflow-x: auto; }
.rr-table { width: 100%; border-collapse: collapse; min-width: 920px; }
.rr-table thead th {
    text-align: left; padding: 14px 16px;
    font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
    color: var(--rr-muted); border-bottom: 1px solid var(--rr-line);
    white-space: nowrap; background: transparent;
}
.rr-table tbody td {
    padding: 16px; font-size: 14px; color: var(--rr-text);
    border-bottom: 1px solid var(--rr-line); vertical-align: middle;
}
.rr-table tbody tr { transition: background .15s ease; }
.rr-table tbody tr:hover { background: var(--rr-soft); }
.rr-table tbody tr.rr-checked { background: rgba(37,99,235,.07); }
.rr-table tbody tr:last-child td { border-bottom: 0; }
.rr-id { font-weight: 800; color: var(--rr-heading); }
.rr-name { font-weight: 600; color: var(--rr-heading); }
.rr-muted { color: var(--rr-muted); }

/* checkbox */
.rr-check { width: 18px; height: 18px; cursor: pointer; accent-color: var(--rr-brand); }

/* instance badge */
.rr-pill {
    display: inline-block; padding: 4px 11px; border-radius: 999px;
    font-size: 12px; font-weight: 700;
    background: rgba(99,102,241,.14); color: #818cf8; border: 1px solid rgba(99,102,241,.25);
}
body.theme-light .rr-pill { background: #eef2ff; color: #4f46e5; border-color: #e0e7ff; }

/* status */
.rr-status { display: inline-flex; align-items: center; gap: 7px; font-weight: 700; font-size: 13px; }
.rr-status .rr-dots { display: inline-flex; align-items: center; gap: 4px; }
.rr-status .rr-dots i { font-size: 12px; }
.rr-status.warning { color: var(--rr-warning); }
.rr-status.success { color: var(--rr-success); }
.rr-status.danger { color: var(--rr-danger); }
.rr-status.muted { color: var(--rr-muted); }

/* trial */
.rr-trial { display: inline-flex; align-items: center; gap: 7px; color: var(--rr-text); font-size: 13.5px; }
.rr-trial i { color: var(--rr-muted); }

/* action approve pill */
.rr-approve {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 14px; border-radius: 999px;
    font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
    background: rgba(37,99,235,.12); color: #60a5fa; border: 1px solid rgba(37,99,235,.28);
    transition: all .15s ease;
}
body.theme-light .rr-approve { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.rr-approve:hover { background: var(--rr-brand); color: #fff; }
.rr-reject {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 9px; margin-left: 6px;
    color: var(--rr-muted); text-decoration: none; transition: all .15s ease;
}
.rr-reject:hover { background: rgba(220,38,38,.12); color: var(--rr-danger); }

/* footer / pagination */
.rr-foot {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; padding: 16px 22px; border-top: 1px solid var(--rr-line);
}
.rr-foot-info { font-size: 13px; color: var(--rr-muted); }
.rr-pager { display: inline-flex; align-items: center; gap: 6px; }
.rr-pager button {
    min-width: 36px; height: 36px; padding: 0 12px; border-radius: 10px;
    border: 1px solid var(--rr-line); background: var(--rr-soft); color: var(--rr-text);
    font-weight: 700; font-size: 13px; cursor: pointer; transition: all .15s ease;
}
.rr-pager button:hover:not(:disabled) { border-color: var(--rr-brand); }
.rr-pager button.active { background: var(--rr-brand); border-color: var(--rr-brand); color: #fff; }
.rr-pager button:disabled { opacity: .45; cursor: not-allowed; }

.rr-empty { text-align: center; padding: 48px 16px; color: var(--rr-muted); }
.rr-empty i { font-size: 34px; display: block; margin-bottom: 10px; opacity: .5; }
{/literal}
</style>

<div class="rr-page" id="rrPage">
    <div class="rr-hero">
        <div class="rr-hero-ic"><i class="fa fa-user-plus"></i></div>
        <div>
            <h1>{Lang::T('Registration Requests')}</h1>
            <p>{Lang::T('Customer registrations')} &middot; {Lang::T('Manage approvals & pending verifications')}</p>
        </div>
    </div>

    <div class="rr-card">
        <div class="rr-toolbar">
            <span class="rr-sel" id="rrSel"><i class="fa fa-check-circle"></i> <span id="rrSelCount">0</span> {Lang::T('items selected')}</span>
            <div class="rr-actions">
                <button type="button" class="rr-btn rr-btn-ghost" id="rrSelectAll"><i class="fa fa-check-double"></i> {Lang::T('Select all items')}</button>
                <button type="button" class="rr-btn rr-btn-primary" id="rrApprove" disabled><i class="fa fa-check-circle"></i> {Lang::T('Approve')}</button>
                <button type="button" class="rr-btn rr-btn-ghost" id="rrExport"><i class="fa fa-download"></i> {Lang::T('Export')}</button>
            </div>
        </div>

        <div class="rr-table-wrap">
            <table class="rr-table" id="rrTable">
                <thead>
                    <tr>
                        <th style="width:42px"><input type="checkbox" class="rr-check" id="rrCheckAll"></th>
                        <th>ID</th>
                        <th>{Lang::T('Instance')}</th>
                        <th>{Lang::T('Name')}</th>
                        <th>{Lang::T('Email')}</th>
                        <th>{Lang::T('Phone')}</th>
                        <th>{Lang::T('Location')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Trial')}</th>
                        <th>{Lang::T('Action')}</th>
                    </tr>
                </thead>
                <tbody id="rrBody">
                    {foreach $requests as $r}
                        {assign var="loc" value=$r['city']}
                        {if $r['city'] and $r['country']}{assign var="loc" value=$r['city']|cat:', '|cat:$r['country']}{elseif $r['country']}{assign var="loc" value=$r['country']}{/if}
                        <tr data-id="{$r['id']}" data-status="{$r['status']}"
                            data-name="{$r['first_name']|escape} {$r['last_name']|escape}"
                            data-email="{$r['email']|escape}" data-phone="{$r['phone']|escape}"
                            data-instance="{$r['instance_name']|escape}" data-location="{$loc|escape}"
                            data-trial="{if $r['trial_expires_at']}{$r['trial_expires_at']}{/if}">
                            <td><input type="checkbox" class="rr-check rr-row-check"></td>
                            <td><span class="rr-id">{$r['id']}</span></td>
                            <td><span class="rr-pill">{if $r['instance_name']}{$r['instance_name']}{else}Aucune{/if}</span></td>
                            <td><span class="rr-name">{$r['first_name']} {$r['last_name']}</span></td>
                            <td class="rr-muted">{$r['email']}</td>
                            <td>{$r['phone']}</td>
                            <td class="rr-muted">{if $loc}{$loc}{else}&mdash;{/if}</td>
                            <td>
                                {if $r['status'] eq 'pending_approval'}
                                    <span class="rr-status warning"><span class="rr-dots"><i class="fa fa-hourglass-half"></i><i class="fa fa-info-circle"></i></span> pending_approval</span>
                                {elseif $r['status'] eq 'pending_email'}
                                    <span class="rr-status warning"><span class="rr-dots"><i class="fa fa-eye"></i><i class="fa fa-envelope"></i></span> pending_email</span>
                                {elseif $r['status'] eq 'approved_trial'}
                                    <span class="rr-status success"><i class="fa fa-check-circle"></i> approved_trial</span>
                                {elseif $r['status'] eq 'rejected'}
                                    <span class="rr-status danger"><i class="fa fa-times-circle"></i> rejected</span>
                                {else}
                                    <span class="rr-status muted"><i class="fa fa-circle"></i> {$r['status']}</span>
                                {/if}
                            </td>
                            <td>
                                {if $r['trial_expires_at']}
                                    <span class="rr-trial"><i class="fa fa-calendar"></i> {$r['trial_expires_at']}</span>
                                {else}
                                    <span class="rr-muted">&mdash;</span>
                                {/if}
                            </td>
                            <td>
                                {if $r['status'] eq 'pending_approval'}
                                    <a class="rr-approve" href="{Text::url('registration_requests/approve/')}{$r['id']}"><i class="fa fa-check-circle"></i> {Lang::T('Approve')}</a>
                                    <a class="rr-reject" href="{Text::url('registration_requests/reject/')}{$r['id']}" title="{Lang::T('Reject')}" onclick="return confirm('{Lang::T('Reject')} ?');"><i class="fa fa-times"></i></a>
                                {else}
                                    <span class="rr-muted">&mdash;</span>
                                {/if}
                            </td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="10"><div class="rr-empty"><i class="fa fa-inbox"></i>{Lang::T('No Data')}</div></td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <div class="rr-foot">
            <span class="rr-foot-info" id="rrInfo"></span>
            <div class="rr-pager" id="rrPager"></div>
        </div>
    </div>
</div>

<script>
var RR_APPROVE_URL = '{Text::url('registration_requests/approve/')}';
{literal}
(function () {
    var PAGE_SIZE = 5;
    var page = 1;
    var allRows = Array.prototype.slice.call(document.querySelectorAll('#rrBody tr[data-id]'));
    var total = allRows.length;
    var checkAll = document.getElementById('rrCheckAll');
    var selectAllBtn = document.getElementById('rrSelectAll');
    var approveBtn = document.getElementById('rrApprove');
    var exportBtn = document.getElementById('rrExport');
    var selBox = document.getElementById('rrSel');
    var selCount = document.getElementById('rrSelCount');
    var info = document.getElementById('rrInfo');
    var pager = document.getElementById('rrPager');

    function rowCheck(tr) { return tr.querySelector('.rr-row-check'); }

    function visibleRows() {
        var start = (page - 1) * PAGE_SIZE;
        return allRows.slice(start, start + PAGE_SIZE);
    }

    function renderPage() {
        allRows.forEach(function (tr) { tr.style.display = 'none'; });
        visibleRows().forEach(function (tr) { tr.style.display = ''; });
        var start = total ? (page - 1) * PAGE_SIZE + 1 : 0;
        var end = Math.min(page * PAGE_SIZE, total);
        info.textContent = 'Showing ' + start + '-' + end + ' of ' + total + ' results';
        renderPager();
        syncCheckAll();
    }

    function renderPager() {
        var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        var html = '';
        html += '<button data-go="prev"' + (page === 1 ? ' disabled' : '') + '><i class="fa fa-angle-left"></i> Previous</button>';
        for (var i = 1; i <= pages; i++) {
            html += '<button data-go="' + i + '"' + (i === page ? ' class="active"' : '') + '>' + i + '</button>';
        }
        html += '<button data-go="next"' + (page === pages ? ' disabled' : '') + '>Next <i class="fa fa-angle-right"></i></button>';
        pager.innerHTML = html;
        Array.prototype.forEach.call(pager.querySelectorAll('button'), function (b) {
            b.addEventListener('click', function () {
                var go = b.getAttribute('data-go');
                if (go === 'prev') page = Math.max(1, page - 1);
                else if (go === 'next') page = Math.min(pages, page + 1);
                else page = parseInt(go, 10);
                renderPage();
            });
        });
    }

    function selectedRows() {
        return allRows.filter(function (tr) { var c = rowCheck(tr); return c && c.checked; });
    }

    function updateSelection() {
        var sel = selectedRows();
        selCount.textContent = sel.length;
        selBox.classList.toggle('active', sel.length > 0);
        allRows.forEach(function (tr) { var c = rowCheck(tr); if (c) tr.classList.toggle('rr-checked', c.checked); });
        var approvable = sel.filter(function (tr) { return tr.getAttribute('data-status') === 'pending_approval'; });
        approveBtn.disabled = approvable.length === 0;
    }

    function syncCheckAll() {
        var vis = visibleRows();
        var checked = vis.filter(function (tr) { var c = rowCheck(tr); return c && c.checked; });
        checkAll.checked = vis.length > 0 && checked.length === vis.length;
        checkAll.indeterminate = checked.length > 0 && checked.length < vis.length;
    }

    allRows.forEach(function (tr) {
        var c = rowCheck(tr);
        if (c) c.addEventListener('change', function () { updateSelection(); syncCheckAll(); });
    });

    checkAll.addEventListener('change', function () {
        visibleRows().forEach(function (tr) { var c = rowCheck(tr); if (c) c.checked = checkAll.checked; });
        updateSelection();
    });

    selectAllBtn.addEventListener('click', function () {
        var allChecked = selectedRows().length === total && total > 0;
        allRows.forEach(function (tr) { var c = rowCheck(tr); if (c) c.checked = !allChecked; });
        updateSelection();
        syncCheckAll();
    });

    approveBtn.addEventListener('click', function () {
        var ids = selectedRows()
            .filter(function (tr) { return tr.getAttribute('data-status') === 'pending_approval'; })
            .map(function (tr) { return tr.getAttribute('data-id'); });
        if (!ids.length) return;
        if (!confirm('Approve ' + ids.length + ' request(s)?')) return;
        approveBtn.disabled = true;
        approveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ...';
        (function run(i) {
            if (i >= ids.length) { window.location.reload(); return; }
            fetch(RR_APPROVE_URL + ids[i], { credentials: 'same-origin' })
                .then(function () { run(i + 1); })
                .catch(function () { run(i + 1); });
        })(0);
    });

    exportBtn.addEventListener('click', function () {
        var headers = ['ID', 'Instance', 'Name', 'Email', 'Phone', 'Location', 'Status', 'Trial'];
        var lines = [headers.join(',')];
        allRows.forEach(function (tr) {
            var row = [
                tr.getAttribute('data-id'),
                tr.getAttribute('data-instance') || '',
                tr.getAttribute('data-name') || '',
                tr.getAttribute('data-email') || '',
                tr.getAttribute('data-phone') || '',
                tr.getAttribute('data-location') || '',
                tr.getAttribute('data-status') || '',
                tr.getAttribute('data-trial') || ''
            ].map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; });
            lines.push(row.join(','));
        });
        var blob = new Blob(["\uFEFF" + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'registration_requests_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    });

    renderPage();
    updateSelection();
})();
{/literal}
</script>

{include file="sections/footer.tpl"}
