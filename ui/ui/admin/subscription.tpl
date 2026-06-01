{include file="sections/header.tpl"}

<style>
{literal}
.sub-status{padding:18px;border-radius:14px;margin-bottom:18px;border:1px solid #e5e7eb}
.sub-status.trial{background:#fff7ed;border-color:#fed7aa}.sub-status.active{background:#ecfdf5;border-color:#bbf7d0}.sub-status.grace{background:#fffbeb;border-color:#fde68a}.sub-status.expired{background:#fef2f2;border-color:#fecaca}
.sub-badge{display:inline-block;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:800;text-transform:uppercase}
.sub-badge.trial{background:#f97316;color:#fff}.sub-badge.active{background:#16a34a;color:#fff}.sub-badge.grace{background:#d97706;color:#fff}.sub-badge.expired{background:#dc2626;color:#fff}
.plan-card{border:1px solid #e5e7eb;border-radius:14px;padding:18px;height:100%;background:#fff}
.plan-card strong{font-size:22px}.stat-box{border-radius:14px;padding:16px;background:#0f172a;color:#fff;margin-bottom:14px}.stat-box h3{margin:0;font-weight:900}.stat-box p{margin:4px 0 0;color:#cbd5e1}
{/literal}
</style>

<div class="sub-status {$subscription->status}">
    <span class="sub-badge {$subscription->status}">
        {if $subscription->status eq 'trial'}Trial{elseif $subscription->status eq 'active'}Active{elseif $subscription->status eq 'grace'}Grace{else}Expired{/if}
    </span>
    <h3 style="margin:10px 0 6px">{Lang::T('Current Subscription')}</h3>
    {if $subscription->status eq 'trial'}
        <p><strong>Mode Trial 7 jours</strong> — {$subscription_days_remaining} jour(s) restant(s). Ajout de routeur non autorisé pendant la période d'essai.</p>
    {elseif $subscription->status eq 'active'}
        <p><strong>Abonnement actif</strong> — Plan {$subscription->plan_type|upper}, expire le {$subscription->subscription_end}.</p>
    {elseif $subscription->status eq 'grace'}
        <p><strong>Période de grâce</strong> — expire le {$subscription->grace_end}.</p>
    {else}
        <p><strong>Abonnement expiré</strong> — veuillez souscrire pour activer le compte complet.</p>
    {/if}
</div>

<div class="row">
    <div class="col-md-3"><div class="stat-box"><h3>{$subscription_stats.routers|default:$router_count}</h3><p>{Lang::T('Routers')}</p></div></div>
    <div class="col-md-3"><div class="stat-box"><h3>{$subscription_stats.router_limit|default:'-'}</h3><p>{Lang::T('Router Limit')}</p></div></div>
    <div class="col-md-3"><div class="stat-box"><h3>{$subscription_stats.invoice_total|default:0|number_format:2}</h3><p>{Lang::T('Invoices')}</p></div></div>
    <div class="col-md-3"><div class="stat-box"><h3>{$subscription_stats.paid_total|default:0|number_format:2}</h3><p>{Lang::T('Paid')}</p></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="plan-card">
            <h3>Business</h3>
            <strong>{$subscription_settings.business_price|number_format:2}</strong>
            <p class="text-muted">Maximum 3 routers inclus.</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="plan-card">
            <h3>PRO</h3>
            <strong>{$subscription_settings.pro_price_per_router|number_format:2}</strong>
            <p class="text-muted">Prix par routeur, adapté aux réseaux évolutifs.</p>
        </div>
    </div>
</div>

<div class="panel panel-primary panel-hovered panel-stacked mb30" style="margin-top:18px">
    <div class="panel-heading">{Lang::T('Subscribe to a Plan')}</div>
    <div class="panel-body">
        <form method="post" action="{Text::url('admin/subscription-post')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="form-group">
                <label>{Lang::T('Plan')}</label>
                <select name="plan_type" id="plan_type" class="form-control" required>
                    <option value="business">Business - {$subscription_settings.business_price|number_format:2} ({Lang::T('max 3 routers')})</option>
                    <option value="pro">PRO - {$subscription_settings.pro_price_per_router|number_format:2} / {Lang::T('router')}</option>
                </select>
            </div>
            <div class="form-group" id="routers_count_group" style="display:none">
                <label>{Lang::T('Number of routers')}</label>
                <input type="number" min="1" name="routers_count" class="form-control" value="{if $router_count > 0}{$router_count}{else}1{/if}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-credit-card"></i> Payer / Souscrire</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Invoices')}</div>
            <div class="panel-body no-padding">
                <table class="table table-striped table-condensed">
                    <thead><tr><th>#</th><th>{Lang::T('Plan')}</th><th>{Lang::T('Amount')}</th><th>{Lang::T('Status')}</th><th>{Lang::T('Date')}</th></tr></thead>
                    <tbody>
                    {foreach $subscription_invoices as $invoice}
                        <tr><td>{$invoice.invoice_no}</td><td>{$invoice.plan_type|upper}</td><td>{$invoice.amount|number_format:2}</td><td><span class="label {if $invoice.status eq 'paid'}label-success{elseif $invoice.status eq 'unpaid'}label-warning{else}label-default{/if}">{$invoice.status}</span></td><td>{$invoice.created_at}</td></tr>
                    {foreachelse}
                        <tr><td colspan="5" class="text-center">{Lang::T('No data')}</td></tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Payment History')}</div>
            <div class="panel-body no-padding">
                <table class="table table-striped table-condensed">
                    <thead><tr><th>{Lang::T('Amount')}</th><th>{Lang::T('Method')}</th><th>{Lang::T('Reference')}</th><th>{Lang::T('Status')}</th><th>{Lang::T('Date')}</th></tr></thead>
                    <tbody>
                    {foreach $subscription_payments as $payment}
                        <tr><td>{$payment.amount|number_format:2}</td><td>{$payment.method}</td><td>{$payment.reference}</td><td><span class="label {if $payment.status eq 'paid'}label-success{elseif $payment.status eq 'pending'}label-warning{else}label-danger{/if}">{$payment.status}</span></td><td>{$payment.created_at}</td></tr>
                    {foreachelse}
                        <tr><td colspan="5" class="text-center">{Lang::T('No data')}</td></tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var plan = document.getElementById('plan_type');
    var group = document.getElementById('routers_count_group');
    function toggle(){ group.style.display = plan.value === 'pro' ? 'block' : 'none'; }
    if(plan && group){ plan.addEventListener('change', toggle); toggle(); }
})();
</script>

{include file="sections/footer.tpl"}
