{include file="sections/header.tpl"}

<style>
{literal}
.au-page {
    --au-card: #ffffff;
    --au-text: #1e293b;
    --au-heading: #0f172a;
    --au-muted: #64748b;
    --au-line: #e2e8f0;
    --au-brand: #2563eb;
    --au-success: #10b981;
    --au-success-bg: rgba(16, 185, 129, 0.12);
    --au-info: #3b82f6;
    --au-info-bg: rgba(59, 130, 246, 0.12);
    --au-warn: #d97706;
    --au-warn-bg: rgba(245, 158, 11, 0.15);
    --au-shadow: 0 10px 40px rgba(15, 23, 42, 0.06);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--au-text);
    margin: 0 -15px 0;
    padding: 0 15px 28px;
}
body.theme-dark .au-page {
    --au-card: rgba(15, 23, 42, 0.92);
    --au-text: #f8fafc;
    --au-heading: #ffffff;
    --au-muted: #94a3b8;
    --au-line: rgba(148, 163, 184, 0.18);
    --au-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
}
.au-page * { box-sizing: border-box; }

.au-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .au-kpi-grid { grid-template-columns: 1fr; } }

.au-kpi {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--au-card);
    border: 1px solid var(--au-line);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: var(--au-shadow);
}
.au-kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    background: var(--au-info-bg);
    color: var(--au-info);
}
.au-kpi-icon.super { color: var(--au-warn); background: var(--au-warn-bg); }
.au-kpi-icon.admin { color: var(--au-info); background: var(--au-info-bg); }
.au-kpi-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--au-heading);
    line-height: 1;
}
.au-kpi-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--au-muted);
    margin-top: 4px;
}

.au-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 20px;
}
.au-search-form {
    flex: 1 1 280px;
    min-width: 240px;
    max-width: 560px;
    margin: 0;
    display: flex;
    align-items: stretch;
    background: var(--au-card);
    border: 1px solid var(--au-line);
    border-radius: 999px;
    overflow: hidden;
    box-shadow: var(--au-shadow);
}
.au-toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    margin-left: auto;
}
.au-search {
    flex: 1;
    border: 0;
    padding: 12px 18px 12px 44px;
    background: transparent;
    color: var(--au-text);
    font-size: 14px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 1.156a5 5 0 1 1 0-10 5 5 0 0 1 0 10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 16px center;
}
.au-search:focus { outline: none; }
.au-search-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 20px;
    border: 0;
    border-left: 1px solid var(--au-line);
    background: var(--au-brand);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    white-space: nowrap;
}
.au-search-btn:hover { opacity: 0.95; }

.au-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border: 0;
    border-radius: 999px;
    background: var(--au-success) !important;
    background-color: var(--au-success) !important;
    color: #fff !important;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
    white-space: nowrap;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.au-btn-add:hover,
.au-btn-add:focus {
    opacity: 0.95;
    color: #fff !important;
    background: var(--au-success) !important;
    background-color: var(--au-success) !important;
}

.au-card {
    background: var(--au-card);
    border: 1px solid var(--au-line);
    border-radius: 16px;
    box-shadow: var(--au-shadow);
    overflow: hidden;
}
.au-table-wrap { overflow-x: auto; }
.au-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 1100px;
}
.au-table th {
    text-align: left;
    padding: 14px 16px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--au-muted);
    border-bottom: 1px solid var(--au-line);
    background: rgba(248, 250, 252, 0.85);
    white-space: nowrap;
}
body.theme-dark .au-table th { background: rgba(2, 6, 23, 0.35); }
.au-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--au-line);
    color: var(--au-text);
    vertical-align: middle;
}
.au-table tr:last-child td { border-bottom: 0; }
.au-table tr:hover td { background: rgba(37, 99, 235, 0.03); }
body.theme-dark .au-table tr:hover td { background: rgba(37, 99, 235, 0.08); }

/* Override global sticky first-column styles (wifizones.css) */
.au-table th:first-child,
.au-table td:first-child {
    position: static !important;
    background: transparent !important;
    background-color: transparent !important;
}
.au-table td.au-td-user,
.au-table td.au-td-user .au-username {
    background: transparent !important;
    background-color: transparent !important;
    box-shadow: none !important;
}
.au-table .au-username {
    color: var(--au-brand) !important;
    font-weight: 600;
    text-decoration: none;
    display: inline;
}
body.theme-dark .au-table .au-username,
body.dark-mode .au-table .au-username {
    color: #60a5fa !important;
}
.au-table a.au-username:hover {
    text-decoration: underline;
    background: transparent !important;
}
.au-subdomain {
    color: var(--au-brand) !important;
    font-weight: 600;
    text-decoration: none;
    background: transparent !important;
}
body.theme-dark .au-subdomain { color: #60a5fa !important; }
.au-subdomain:hover { text-decoration: underline; }

.au-badge {
    display: inline-flex;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.au-badge.superadmin {
    background: var(--au-warn-bg);
    color: #b45309;
}
body.theme-dark .au-badge.superadmin { color: #fbbf24; }
.au-badge.admin {
    background: var(--au-info-bg);
    color: #1d4ed8;
}
body.theme-dark .au-badge.admin { color: #60a5fa; }
.au-badge.other {
    background: rgba(148, 163, 184, 0.15);
    color: var(--au-muted);
}

.au-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--au-brand);
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    background: transparent !important;
}
body.theme-dark .au-edit { color: #60a5fa; }
.au-edit:hover { text-decoration: underline; }

.au-manage-actions {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.au-delete {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #dc2626 !important;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    background: transparent !important;
    background-color: transparent !important;
}
body.theme-dark .au-delete,
body.dark-mode .au-delete {
    color: #f87171 !important;
}
.au-delete:hover {
    text-decoration: underline;
    color: #b91c1c !important;
}

.au-id {
    font-size: 12px;
    color: var(--au-muted);
    font-weight: 600;
}

.au-pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 18px;
    border-top: 1px solid var(--au-line);
    flex-wrap: wrap;
}
.au-pagination .pagination {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.au-pagination .pagination > li > a,
.au-pagination .pagination > li > span {
    border-radius: 10px !important;
    border: 1px solid var(--au-line) !important;
    background: var(--au-card) !important;
    color: var(--au-text) !important;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 14px;
}
.au-pagination .pagination > .active > a {
    background: var(--au-brand) !important;
    border-color: var(--au-brand) !important;
    color: #fff !important;
}
.au-pagination .pagination > .disabled > a { opacity: 0.45; }
.au-page-info {
    font-size: 13px;
    font-weight: 600;
    color: var(--au-muted);
}

.au-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--au-muted);
}
.au-empty i { font-size: 36px; opacity: 0.35; display: block; margin-bottom: 10px; }
{/literal}
</style>

<div class="au-page" id="auPage">

    <div class="au-kpi-grid">
        <div class="au-kpi">
            <div class="au-kpi-icon"><i class="fa fa-users"></i></div>
            <div>
                <div class="au-kpi-value">{$total_admins|default:0}</div>
                <div class="au-kpi-label">{Lang::T('Total_Admins')}</div>
            </div>
        </div>
        <div class="au-kpi">
            <div class="au-kpi-icon super"><i class="fa fa-shield"></i></div>
            <div>
                <div class="au-kpi-value">{$superadmin_count|default:0}</div>
                <div class="au-kpi-label">{Lang::T('SuperAdmin')}</div>
            </div>
        </div>
        <div class="au-kpi">
            <div class="au-kpi-icon admin"><i class="fa fa-user"></i></div>
            <div>
                <div class="au-kpi-value">{$administrators_count|default:0}</div>
                <div class="au-kpi-label">{Lang::T('Administrators')}</div>
            </div>
        </div>
    </div>

    <div class="au-toolbar">
        <form method="get" class="au-search-form" id="auSearchForm">
            <input type="hidden" name="_route" value="settings/users">
            <input type="search" class="au-search" name="search" value="{$search|escape:'html'}"
                placeholder="{Lang::T('Search_admin_users_placeholder')}" id="auSearchInput">
            <button type="submit" class="au-search-btn"><i class="fa fa-filter"></i> {Lang::T('Search')}</button>
        </form>
        <div class="au-toolbar-actions">
            <a href="{Text::url('settings/users-add')}" class="au-btn-add">
                <i class="fa fa-plus"></i> {Lang::T('Add_New_Administrator')}
            </a>
        </div>
    </div>

    <div class="au-card">
        <div class="au-table-wrap">
            <table class="au-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Phone')}</th>
                        <th>{Lang::T('Email')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('ISP___Business_Name')}</th>
                        <th>{Lang::T('Location')}</th>
                        <th>{Lang::T('Desired_Subdomain')}</th>
                        <th>{Lang::T('Last Login')}</th>
                        <th>{Lang::T('Manage')}</th>
                        <th>ID</th>
                    </tr>
                </thead>
                <tbody>
                    {if $d|@count gt 0}
                    {foreach $d as $ds}
                    <tr>
                        <td class="au-td-user">
                            <a href="{Text::url('settings/users-edit/', $ds.id)}" class="au-username">{$ds.username|escape}</a>
                        </td>
                        <td>{if $ds.fullname}{$ds.fullname|escape}{else}—{/if}</td>
                        <td>{if $ds.phone}{$ds.phone|escape}{else}—{/if}</td>
                        <td>{if $ds.email}{$ds.email|escape}{else}—{/if}</td>
                        <td>
                            {if $ds.user_type eq 'SuperAdmin'}
                            <span class="au-badge superadmin">{$ds.user_type}</span>
                            {elseif $ds.user_type eq 'Admin'}
                            <span class="au-badge admin">{$ds.user_type}</span>
                            {else}
                            <span class="au-badge other">{$ds.user_type}</span>
                            {/if}
                        </td>
                        <td>{if $ds.tenant_id && $tenant_names[$ds.tenant_id]}{$tenant_names[$ds.tenant_id]|escape}{else}—{/if}</td>
                        <td>
                            {if $ds.city || $ds.subdistrict || $ds.ward}
                            {$ds.city|escape}{if $ds.subdistrict && $ds.city}, {/if}{$ds.subdistrict|escape}{if $ds.ward && ($ds.city || $ds.subdistrict)}, {/if}{$ds.ward|escape}
                            {else}—{/if}
                        </td>
                        <td>
                            {if $ds.tenant_id && $tenant_slugs[$ds.tenant_id]}
                            <a href="http://{$tenant_slugs[$ds.tenant_id]|escape}{$tenant_domain_suffix|escape}" target="_blank" rel="noopener" class="au-subdomain">{$tenant_slugs[$ds.tenant_id]|escape}{$tenant_domain_suffix|escape}</a>
                            {else}—{/if}
                        </td>
                        <td>{if $ds.last_login}{Lang::timeElapsed($ds.last_login)}{else}—{/if}</td>
                        <td>
                            <div class="au-manage-actions">
                                <a href="{Text::url('settings/users-edit/', $ds.id)}" class="au-edit">
                                    <i class="fa fa-pencil"></i> {Lang::T('Edit')}
                                </a>
                                {if in_array($_admin['user_type'], ['SuperAdmin','Admin']) && $ds.id != $_admin.id}
                                    {if $_admin['user_type'] eq 'SuperAdmin' || $ds.user_type eq 'Report' || $ds.user_type eq 'Agent' || $ds.user_type eq 'Sales'}
                                    <a href="{Text::url('settings/users-delete/', $ds.id)}" class="au-delete"
                                        onclick="return ask(this, '{Lang::T('Delete_this_administrator_')}')">
                                        <i class="fa fa-trash"></i> {Lang::T('Delete')}
                                    </a>
                                    {/if}
                                {/if}
                            </div>
                        </td>
                        <td><span class="au-id">{$ds.id}</span></td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr>
                        <td colspan="11">
                            <div class="au-empty">
                                <i class="fa fa-user-secret"></i>
                                {Lang::T('No_administrators_found')}
                            </div>
                        </td>
                    </tr>
                    {/if}
                </tbody>
            </table>
        </div>
        <div class="au-pagination">
            {if $paginator}
            <span class="au-page-info">{Lang::T('Page')} {$paginator['page']} / {$paginator['count']}</span>
            {/if}
            {include file="pagination.tpl"}
        </div>
    </div>
</div>

{literal}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('auSearchInput');
    var form = document.getElementById('auSearchForm');
    var timer;
    if (input && form) {
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 500);
        });
    }
});
</script>
{/literal}

{include file="sections/footer.tpl"}
