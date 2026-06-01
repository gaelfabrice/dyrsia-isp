{include file="sections/header.tpl"}

<div class="wz-orbit-page">
    <div class="wz-orbit-hero" style="margin-bottom:20px;">
        <div class="wz-orbit-hero-copy">
            <span class="wz-orbit-kicker">{Lang::T('Monitoring')}</span>
            <h2>{Lang::T('Customer Expiry Status')}</h2>
            <p><a href="{Text::url('monitoring')}">&larr; {Lang::T('Back to Monitoring')}</a></p>
        </div>
    </div>
    {$customer_expiry_widget}
</div>

{include file="sections/footer.tpl"}
