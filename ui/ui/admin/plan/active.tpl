{include file="sections/header.tpl"}

<style>
{literal}
.ac-page {
    --ac-bg: transparent;
    --ac-card: #ffffff;
    --ac-text: #1e293b;
    --ac-heading: #0f172a;
    --ac-muted: #64748b;
    --ac-line: #e2e8f0;
    --ac-brand: #2563eb;
    --ac-success: #10b981;
    --ac-success-bg: rgba(16, 185, 129, 0.12);
    --ac-danger: #ef4444;
    --ac-danger-bg: rgba(239, 68, 68, 0.12);
    --ac-info: #3b82f6;
    --ac-info-bg: rgba(59, 130, 246, 0.12);
    --ac-shadow: 0 10px 40px rgba(15, 23, 42, 0.06);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--ac-text);
    margin: -15px -15px 0;
    padding: 0 15px 28px;
}
body.theme-dark .ac-page {
    --ac-card: rgba(15, 23, 42, 0.92);
    --ac-text: #f8fafc;
    --ac-heading: #ffffff;
    --ac-muted: #94a3b8;
    --ac-line: rgba(148, 163, 184, 0.18);
    --ac-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
}
.ac-page * { box-sizing: border-box; }

.ac-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .ac-kpi-grid { grid-template-columns: 1fr; } }

.ac-kpi {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--ac-card);
    border: 1px solid var(--ac-line);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: var(--ac-shadow);
}
.ac-kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.ac-kpi-icon.active { background: var(--ac-success-bg); color: var(--ac-success); }
.ac-kpi-icon.expired { background: var(--ac-danger-bg); color: var(--ac-danger); }
.ac-kpi-icon.total { background: var(--ac-info-bg); color: var(--ac-info); }
.ac-kpi-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--ac-heading);
    line-height: 1;
}
.ac-kpi-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ac-muted);
    margin-top: 4px;
}

.ac-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 20px;
}
.ac-search-form {
    flex: 1;
    min-width: 240px;
    max-width: 520px;
    margin: 0;
}
.ac-search {
    width: 100%;
    padding: 12px 18px 12px 44px;
    border-radius: 999px;
    border: 1px solid var(--ac-line);
    background: var(--ac-card);
    color: var(--ac-text);
    font-size: 14px;
    box-shadow: var(--ac-shadow);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 1.156a5 5 0 1 1 0-10 5 5 0 0 1 0 10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 16px center;
}
.ac-search:focus {
    outline: none;
    border-color: var(--ac-brand);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.ac-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    border: 0;
    border-radius: 999px;
    background: var(--ac-brand);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    white-space: nowrap;
}
.ac-btn:hover { opacity: 0.95; color: #fff; }

.ac-card {
    background: var(--ac-card);
    border: 1px solid var(--ac-line);
    border-radius: 16px;
    box-shadow: var(--ac-shadow);
    overflow: hidden;
}
.ac-table-wrap { overflow-x: auto; }
.ac-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 1200px;
}
.ac-table th {
    text-align: left;
    padding: 14px 16px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ac-muted);
    border-bottom: 1px solid var(--ac-line);
    background: rgba(248, 250, 252, 0.8);
    white-space: nowrap;
}
body.theme-dark .ac-table th { background: rgba(2, 6, 23, 0.35); }
.ac-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--ac-line);
    color: var(--ac-text);
    vertical-align: middle;
}
.ac-table tr:last-child td { border-bottom: 0; }
.ac-table tr:hover td { background: rgba(37, 99, 235, 0.03); }
body.theme-dark .ac-table tr:hover td { background: rgba(37, 99, 235, 0.08); }
.ac-table a.ac-username,
.ac-table a.ac-plan-link {
    color: var(--ac-brand) !important;
    background: transparent !important;
    background-color: transparent !important;
    box-shadow: none !important;
    padding: 0;
    border: 0;
    font-weight: 600;
    text-decoration: none;
    display: inline;
}
body.theme-dark .ac-table a.ac-username,
body.theme-dark .ac-table a.ac-plan-link {
    color: #60a5fa !important;
}
.ac-table a.ac-username:hover,
.ac-table a.ac-plan-link:hover {
    text-decoration: underline;
    background: transparent !important;
}
.ac-table td.ac-td-user,
.ac-table td.ac-td-plan {
    background-color: transparent !important;
}

.ac-toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.ac-btn-outline {
    background: transparent;
    border: 1px solid var(--ac-line);
    color: var(--ac-text);
    box-shadow: none;
}
.ac-btn-outline:hover {
    border-color: var(--ac-brand);
    color: var(--ac-brand);
    background: rgba(37, 99, 235, 0.06);
}

.ac-modal .modal-content {
    background: var(--ac-card);
    border: 1px solid var(--ac-line);
    border-radius: 16px;
    color: var(--ac-text);
    box-shadow: var(--ac-shadow);
}
.ac-modal .modal-header {
    border-bottom: 1px solid var(--ac-line);
    padding: 18px 22px;
}
.ac-modal .modal-title {
    font-weight: 800;
    color: var(--ac-heading);
}
.ac-modal .modal-body { padding: 22px; }
.ac-modal .modal-footer {
    border-top: 1px solid var(--ac-line);
    padding: 16px 22px;
}
.ac-modal .form-control,
.ac-modal .select2-container--bootstrap .select2-selection {
    border-radius: 10px !important;
    border: 1px solid var(--ac-line) !important;
    background: rgba(248, 250, 252, 0.9) !important;
    color: var(--ac-text) !important;
    min-height: 42px;
}
body.theme-dark .ac-modal .form-control,
body.theme-dark .ac-modal .select2-container--bootstrap .select2-selection {
    background: rgba(2, 6, 23, 0.55) !important;
}
.ac-modal label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ac-muted);
    margin-bottom: 6px;
}
.ac-modal .form-group { margin-bottom: 18px; }
.ac-modal .close { color: var(--ac-muted); opacity: 1; }

.ac-table a { color: var(--ac-brand); font-weight: 600; text-decoration: none; }
.ac-table a:hover { text-decoration: underline; }

.ac-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.ac-status.on {
    background: var(--ac-success-bg);
    color: #059669;
}
body.theme-dark .ac-status.on { color: #34d399; }
.ac-status.off {
    background: var(--ac-danger-bg);
    color: #dc2626;
}
body.theme-dark .ac-status.off { color: #f87171; }
.ac-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
}

.ac-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--ac-brand);
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
}
.ac-edit:hover { text-decoration: underline; color: var(--ac-brand); }

.ac-pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 18px;
    border-top: 1px solid var(--ac-line);
    flex-wrap: wrap;
}
.ac-pagination .pagination {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ac-pagination .pagination > li > a,
.ac-pagination .pagination > li > span {
    border-radius: 10px !important;
    border: 1px solid var(--ac-line) !important;
    background: var(--ac-card) !important;
    color: var(--ac-text) !important;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 14px;
}
.ac-pagination .pagination > .active > a {
    background: var(--ac-brand) !important;
    border-color: var(--ac-brand) !important;
    color: #fff !important;
}
.ac-pagination .pagination > .disabled > a { opacity: 0.45; }
.ac-page-info {
    font-size: 13px;
    font-weight: 600;
    color: var(--ac-muted);
}

.ac-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--ac-muted);
}
.ac-empty i { font-size: 36px; opacity: 0.35; display: block; margin-bottom: 10px; }

.ac-sync {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid var(--ac-line);
    background: var(--ac-card);
    color: var(--ac-muted);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 16px;
}
.ac-sync:hover { color: var(--ac-brand); border-color: var(--ac-brand); }
{/literal}
</style>

<div class="ac-page" id="acPage">

    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
    <a class="ac-sync" href="{Text::url('plan/sync')}"
        onclick="return ask(this, '{Lang::T('This will sync dan send Customer active package to Mikrotik')}?')">
        <i class="fa fa-refresh"></i> {Lang::T('Sync')}
    </a>
    {/if}

    <div class="ac-kpi-grid">
        <div class="ac-kpi">
            <div class="ac-kpi-icon active"><i class="fa fa-check"></i></div>
            <div>
                <div class="ac-kpi-value">{$active_count|default:0}</div>
                <div class="ac-kpi-label">{Lang::T('Active')}</div>
            </div>
        </div>
        <div class="ac-kpi">
            <div class="ac-kpi-icon expired"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="ac-kpi-value">{$expired_count|default:0}</div>
                <div class="ac-kpi-label">{Lang::T('Expired')}</div>
            </div>
        </div>
        <div class="ac-kpi">
            <div class="ac-kpi-icon total"><i class="fa fa-users"></i></div>
            <div>
                <div class="ac-kpi-value">{$total_count|default:0}</div>
                <div class="ac-kpi-label">{Lang::T('Total_Clients')}</div>
            </div>
        </div>
    </div>

    <div class="ac-toolbar">
        <form method="post" action="{Text::url('plan/list/')}" class="ac-search-form" id="acSearchForm">
            {if $show}<input type="hidden" name="show" value="{$show}">{/if}
            {if $router}<input type="hidden" name="router" value="{$router}">{/if}
            {if $plan}<input type="hidden" name="plan" value="{$plan}">{/if}
            {if $status}<input type="hidden" name="status" value="{$status}">{/if}
            {if $type}<input type="hidden" name="type" value="{$type}">{/if}
            <input type="search" class="ac-search" name="search" value="{$search|escape:'html'}"
                placeholder="{Lang::T('Search_active_customers_placeholder')}" id="acSearchInput">
        </form>
        <div class="ac-toolbar-actions">
            {if $voucher_refill_enabled|default:false}
            <button type="button" class="ac-btn ac-btn-outline" data-toggle="modal" data-target="#acRefillModal">
                <i class="fa fa-ticket"></i> {Lang::T('Refill_Customer')}
            </button>
            {/if}
            <a href="{Text::url('plan/recharge')}" class="ac-btn">
                <i class="fa fa-plus"></i> {Lang::T('Recharge_Account')}
            </a>
        </div>
    </div>

    <div class="ac-card">
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Address')}</th>
                        <th>{Lang::T('Phone Number')}</th>
                        <th>{Lang::T('Plan')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Method')}</th>
                        <th>{Lang::T('Router_Name')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $d|@count gt 0}
                    {foreach $d as $ds}
                    <tr>
                        <td class="ac-td-user">
                            {if $ds.customer_id == '0'}
                            <a href="{Text::url('plan/voucher/&search=')}{$ds.username}" class="ac-username">{$ds.username}</a>
                            {else}
                            <a href="{Text::url('customers/view/')}{$ds.customer_id}" class="ac-username">{$ds.username}</a>
                            {/if}
                        </td>
                        <td>{if $ds.fullname}{$ds.fullname}{else}—{/if}</td>
                        <td>{if $ds.address}{$ds.address}{else}—{/if}</td>
                        <td>{if $ds.phonenumber}{$ds.phonenumber}{else}—{/if}</td>
                        <td class="ac-td-plan">
                            {if $ds.type == 'Hotspot'}
                            <a href="{Text::url('services/edit/')}{$ds.plan_id}" class="ac-plan-link">{$ds.namebp}</a>
                            {elseif $ds.type == 'PPPOE'}
                            <a href="{Text::url('services/pppoe-edit/')}{$ds.plan_id}" class="ac-plan-link">{$ds.namebp}</a>
                            {elseif $ds.type == 'VPN'}
                            <a href="{Text::url('services/vpn-edit/')}{$ds.plan_id}" class="ac-plan-link">{$ds.namebp}</a>
                            {else}
                            {$ds.namebp}
                            {/if}
                        </td>
                        <td>{$ds.type}</td>
                        <td>{Lang::dateAndTimeFormat($ds.recharged_on,$ds.recharged_time)}</td>
                        <td>{Lang::dateAndTimeFormat($ds.expiration,$ds.time)}</td>
                        <td>{if $ds.method}{$ds.method}{else}—{/if}</td>
                        <td>{if $ds.routers}{$ds.routers}{else}—{/if}</td>
                        <td>
                            {if $ds.status == 'on'}
                            <span class="ac-status on"><span class="ac-status-dot"></span> {Lang::T('Active')}</span>
                            {else}
                            <span class="ac-status off"><i class="fa fa-exclamation-circle"></i> {Lang::T('Expired')}</span>
                            {/if}
                        </td>
                        <td>
                            <a href="{Text::url('plan/edit/')}{$ds.id}" class="ac-edit">
                                <i class="fa fa-pencil"></i> {Lang::T('Edit')}
                            </a>
                            {if $ds.status == 'off' && $_c['extend_expired']}
                            <a href="javascript:void(0)" onclick="extend('{$ds.id}')" class="ac-edit" style="margin-left:8px">
                                {Lang::T('Extend')}
                            </a>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr>
                        <td colspan="12">
                            <div class="ac-empty">
                                <i class="fa fa-inbox"></i>
                                {Lang::T('No_customers_found')}
                            </div>
                        </td>
                    </tr>
                    {/if}
                </tbody>
            </table>
        </div>
        <div class="ac-pagination">
            {if $paginator}
            <span class="ac-page-info">{Lang::T('Page')} {$paginator['page']} / {$paginator['count']}</span>
            {/if}
            {include file="pagination.tpl"}
        </div>
    </div>

    {if $voucher_refill_enabled|default:false}
    <div class="modal fade ac-modal" id="acRefillModal" tabindex="-1" role="dialog" aria-labelledby="acRefillLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="acRefillLabel"><i class="fa fa-ticket"></i> {Lang::T('Refill_Account')}</h4>
                </div>
                <form method="post" action="{Text::url('plan/refill-post')}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="personSelect">{Lang::T('Select Account')}</label>
                            <select id="personSelect" class="form-control select2" name="id_customer" style="width:100%"
                                data-placeholder="{Lang::T('Select a customer')}..."></select>
                        </div>
                        <div class="form-group">
                            <label for="acVoucherCode">{Lang::T('Code Voucher')}</label>
                            <input type="text" class="form-control" id="acVoucherCode" name="code"
                                placeholder="{Lang::T('Enter voucher code here')}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="ac-btn ac-btn-outline" data-dismiss="modal">{Lang::T('Cancel')}</button>
                        <button type="submit" class="ac-btn"
                            onclick="return ask(this, '{Lang::T('Continue_the_Refill_process')}?')">
                            <i class="fa fa-check"></i> {Lang::T('Recharge')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {/if}
</div>

{literal}
<script>
function extend(idP) {
    var res = prompt("Extend for many days?", "3");
    if (res) {
        if (confirm("Extend for " + res + " days?")) {
            window.location.href = "{/literal}{Text::url('plan/extend/')}{literal}" + idP + "/" + res + "{/literal}{Text::isQA('? or &')}stoken={App::getToken()}{literal}";
        }
    }
}
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('acSearchInput');
    var form = document.getElementById('acSearchForm');
    var timer;
    if (input && form) {
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 500);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
    }
    {/literal}{if $open_refill|default:false}$('#acRefillModal').modal('show');{/if}{literal}
});
</script>
{/literal}

{include file="sections/footer.tpl"}
