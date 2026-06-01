{include file="sections/header.tpl"}

<style>
{literal}
.sa-stat{border-radius:14px;padding:16px;background:#0f172a;color:#fff;margin-bottom:14px}.sa-stat h3{margin:0;font-weight:900}.sa-stat p{margin:4px 0 0;color:#cbd5e1}.sa-badge{display:inline-block;padding:5px 10px;border-radius:999px;color:#fff;font-size:11px;font-weight:800;text-transform:uppercase}.sa-badge.trial{background:#f97316}.sa-badge.active{background:#16a34a}.sa-badge.grace{background:#d97706}.sa-badge.expired{background:#dc2626}
{/literal}
</style>

<div class="row">
    <div class="col-md-3"><div class="sa-stat"><h3>{$subscription_stats.total|default:0}</h3><p>{Lang::T('Total')}</p></div></div>
    <div class="col-md-3"><div class="sa-stat"><h3>{$subscription_stats.trial|default:0}</h3><p>Trial</p></div></div>
    <div class="col-md-3"><div class="sa-stat"><h3>{$subscription_stats.active|default:0}</h3><p>Active</p></div></div>
    <div class="col-md-3"><div class="sa-stat"><h3>{$subscription_stats.expired|default:0}</h3><p>Expired</p></div></div>
</div>

<div class="panel panel-primary panel-hovered panel-stacked mb30">
    <div class="panel-heading">{Lang::T('Admin Subscriptions')} <a href="{Text::url('superadmin/instances')}" class="btn btn-default btn-xs pull-right">{Lang::T('View Instances')}</a></div>
    <div class="panel-body no-padding">
        <table class="table table-bordered table-striped table-condensed">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Trial End</th>
                    <th>Subscription End</th>
                    <th>Grace End</th>
                    <th>Routers</th>
                    <th>{Lang::T('Manage')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $subscriptions as $s}
                    <tr class="{if $s.status eq 'expired'}danger{elseif $s.status eq 'grace'}warning{/if}">
                        <td>{$s.admin_fullname|default:$s.admin_username}</td>
                        <td>{$s.admin_email}</td>
                        <td>{if $s.plan_type}{$s.plan_type|upper}{else}-{/if}</td>
                        <td><span class="sa-badge {$s.status}">{$s.status}</span></td>
                        <td>{$s.trial_end|default:'-'}</td>
                        <td>{$s.subscription_end|default:'-'}</td>
                        <td>{$s.grace_end|default:'-'}</td>
                        <td>{$s.routers_count}</td>
                        <td>
                            <form method="post" action="{Text::url('superadmin/subscription-action')}" style="display:inline">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <input type="hidden" name="admin_id" value="{$s.admin_id}">
                                <input type="hidden" name="do" value="suspend">
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Suspendre ?')">Suspendre</button>
                            </form>
                            <form method="post" action="{Text::url('superadmin/subscription-action')}" style="display:inline">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <input type="hidden" name="admin_id" value="{$s.admin_id}">
                                <input type="hidden" name="do" value="extend">
                                <button type="submit" class="btn btn-success btn-xs">Prolonger +30j</button>
                            </form>
                        </td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="9" class="text-center">{Lang::T('No data')}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>

{include file="sections/footer.tpl"}
