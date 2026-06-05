{include file="sections/header.tpl"}

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}

{assign dtipe value="dashboard_`$tipeUser`"}

<div class="wz-orbit-page">
    <div class="wz-orbit-hero">
        <div class="wz-orbit-hero-copy">
            <span class="wz-orbit-kicker">{Lang::T('Command Center')}</span>
            <h2>{Lang::T('Dashboard')}</h2>
            <p>{Lang::T('A clear, fast and ergonomic control room for customers, services, network and business activity.')}</p>
        </div>
        <div class="wz-orbit-actions">
            <span class="btn btn-default"><i class="fa fa-calendar"></i> {$start_date} &nbsp; {$current_date}</span>
            {if !$admin_subscription || $admin_subscription->status neq 'trial'}
                <a href="{Text::url('routers/add')}" class="btn btn-default"><i class="fa fa-server"></i> {Lang::T('Add Router')}</a>
            {/if}
            <a href="{Text::url('customers/add')}" class="btn btn-primary"><i class="fa fa-user-plus"></i> {Lang::T('New Customer')}</a>
            <a href="{Text::url('plan/recharge')}" class="btn btn-default"><i class="fa fa-bolt"></i> {Lang::T('Recharge')}</a>
        </div>
    </div>

    {if $demo_showcase_active|default:false}
        <div class="alert alert-info" style="margin:18px 0;border-radius:12px;">
            <strong><i class="fa fa-eye"></i> Mode démonstration</strong> — Données fictives générées pour la présentation. Aucun routeur réel n'est synchronisé.
        </div>
    {/if}

    {if $admin_subscription}
        <style>
            {literal}
            .subscription-status-card{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-radius:14px;margin:18px 0;border:1px solid rgba(148,163,184,.25);box-shadow:0 8px 22px rgba(15,23,42,.08)}
            .subscription-status-card.trial{background:#fff7ed;border-color:#fed7aa;color:#7c2d12}
            .subscription-status-card.active{background:#ecfdf5;border-color:#bbf7d0;color:#064e3b}
            .subscription-status-card.grace{background:#fffbeb;border-color:#fde68a;color:#78350f}
            .subscription-status-card.expired{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
            .subscription-badge{display:inline-block;padding:5px 12px;border-radius:999px;font-weight:800;font-size:12px;text-transform:uppercase;background:rgba(0,0,0,.08)}
            .subscription-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
            @media(max-width:767px){.subscription-status-card{display:block}.subscription-actions{margin-top:12px}}
            {/literal}
        </style>
        <div class="subscription-status-card {$admin_subscription->status}">
            <div>
                <span class="subscription-badge">
                    {if $admin_subscription->status eq 'trial'}Trial{elseif $admin_subscription->status eq 'active'}Active{elseif $admin_subscription->status eq 'grace'}Grace{else}Expired{/if}
                </span>
                <div style="margin-top:8px">
            {if $admin_subscription->status eq 'trial'}
                <strong>Mode Démo ({$admin_demo_trial_days|default:5} jours)</strong> — {$admin_subscription_days_remaining} jour(s) restant(s). Ajout de routeur non autorisé pendant la période d'essai.
            {elseif $admin_subscription->status eq 'active'}
                <strong>Abonnement actif</strong> — Plan {$admin_subscription->plan_type|upper}, expire le {$admin_subscription->subscription_end}.
            {elseif $admin_subscription->status eq 'grace'}
                <strong>Période de grâce</strong> — expire le {$admin_subscription->grace_end}.
            {else}
                <strong>Abonnement expiré</strong> — veuillez souscrire pour activer le compte complet.
            {/if}
                </div>
            </div>
            <div class="subscription-actions">
                <a href="{Text::url('admin/subscription')}" class="btn btn-primary"><i class="fa fa-credit-card"></i> Payer / Souscrire</a>
            </div>
        </div>
    {/if}

    {assign rows explode(".", $_c[$dtipe])}
    {assign pos 1}
    {foreach $rows as $cols}
        {if $cols == 12}
            <div class="row wz-orbit-row">
                <div class="col-md-12 wz-orbit-col">
                    {showWidget widgets=$widgets pos=$pos}
                </div>
            </div>
            {assign pos value=$pos+1}
        {else}
            {assign colss explode(",", $cols)}
            <div class="row wz-orbit-row">
                {foreach $colss as $c}
                    <div class="col-md-{$c} wz-orbit-col">
                        {showWidget widgets=$widgets pos=$pos}
                    </div>
                    {assign pos value=$pos+1}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>

{if $_c['new_version_notify'] != 'disable'}
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            $.getJSON("./version.json?" + Math.random(), function(data) {
                var localVersion = data.version;
                $('#version').html('Version: ' + localVersion);
            }).fail(function() {
                $('#version').html('Version: DYRSIA ISP');
            });
        });
    </script>
{/if}

{include file="sections/footer.tpl"}