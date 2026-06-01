{include file="sections/header.tpl"}

<style>
{literal}
.instance-stat{border-radius:14px;padding:16px;background:#0f172a;color:#fff;margin-bottom:14px}.instance-stat h3{margin:0;font-weight:900}.instance-stat p{margin:4px 0 0;color:#cbd5e1}.status-tabs{margin-bottom:16px}.status-tabs .btn{margin-right:6px;margin-bottom:6px}.state-badge{display:inline-block;padding:5px 10px;border-radius:999px;color:#fff;font-weight:800;font-size:11px;text-transform:uppercase}.state-badge.trial{background:#f97316}.state-badge.active{background:#16a34a}.state-badge.grace{background:#d97706}.state-badge.expired{background:#dc2626}.state-badge.none{background:#64748b}
{/literal}
</style>

<div class="row">
    <div class="col-md-3"><div class="instance-stat"><h3>{$subscription_stats.total|default:0}</h3><p>{Lang::T('Total')}</p></div></div>
    <div class="col-md-3"><div class="instance-stat"><h3>{$subscription_stats.trial|default:0}</h3><p>Trial</p></div></div>
    <div class="col-md-3"><div class="instance-stat"><h3>{$subscription_stats.active|default:0}</h3><p>Active</p></div></div>
    <div class="col-md-3"><div class="instance-stat"><h3>{$subscription_stats.expired|default:0}</h3><p>Expired</p></div></div>
</div>

<div class="status-tabs">
    <a class="btn {if !$status_filter}btn-primary{else}btn-default{/if}" href="{Text::url('superadmin/instances')}">{Lang::T('All')}</a>
    <a class="btn {if $status_filter eq 'trial'}btn-primary{else}btn-default{/if}" href="{Text::url('superadmin/instances&status=trial')}">Trial</a>
    <a class="btn {if $status_filter eq 'active'}btn-primary{else}btn-default{/if}" href="{Text::url('superadmin/instances&status=active')}">Active</a>
    <a class="btn {if $status_filter eq 'grace'}btn-primary{else}btn-default{/if}" href="{Text::url('superadmin/instances&status=grace')}">Grace</a>
    <a class="btn {if $status_filter eq 'expired'}btn-primary{else}btn-default{/if}" href="{Text::url('superadmin/instances&status=expired')}">Expired</a>
</div>

<div class="panel panel-primary panel-hovered panel-stacked mb30">
    <div class="panel-heading"><i class="fa fa-building-o"></i> {Lang::T('Instances')}</div>
    <div class="panel-body no-padding">
        <table class="table table-bordered table-striped table-condensed">
            <thead>
                <tr>
                    <th>{Lang::T('Business')}</th>
                    <th>{Lang::T('Subdomain')}</th>
                    <th>Admin</th>
                    <th>Email</th>
                    <th>{Lang::T('Plan')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Trial End')}</th>
                    <th>{Lang::T('Subscription End')}</th>
                    <th>{Lang::T('Routers')}</th>
                    <th>{Lang::T('Actions')}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $instances as $i}
                {assign var=state value=$i.subscription_status|default:'none'}
                <tr class="{if $state eq 'expired'}danger{elseif $state eq 'grace'}warning{/if}">
                    <td><strong>{$i.business_name|escape}</strong></td>
                    <td><a href="{Text::url('dashboard_tenant=')}{$i.slug|escape}" target="_blank">{$i.slug|escape}</a></td>
                    <td>{$i.admin_fullname|default:$i.admin_username}</td>
                    <td>{$i.admin_email}</td>
                    <td>{if $i.subscription_plan}{$i.subscription_plan|upper}{else}-{/if}</td>
                    <td><span class="state-badge {$state}">{$state}</span></td>
                    <td>{$i.trial_end|default:'-'}</td>
                    <td>{$i.subscription_end|default:'-'}</td>
                    <td>{$i.subscribed_routers|default:0}</td>
                    <td>
                        <form method="post" action="{Text::url('superadmin/subscription-action')}" style="display:inline">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <input type="hidden" name="admin_id" value="{$i.admin_user_id}">
                            <input type="hidden" name="do" value="extend">
                            <button type="submit" class="btn btn-success btn-xs">+30j</button>
                        </form>
                        <form method="post" action="{Text::url('superadmin/subscription-action')}" style="display:inline">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <input type="hidden" name="admin_id" value="{$i.admin_user_id}">
                            <input type="hidden" name="do" value="suspend">
                            <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Suspendre cette instance ?')">Suspendre</button>
                        </form>
                    </td>
                </tr>
            {foreachelse}
                <tr><td colspan="10" class="text-center">{Lang::T('No data')}</td></tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>

{include file="sections/footer.tpl"}
