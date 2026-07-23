{include file="sections/header.tpl"}

<style>
{literal}
.plan-momo-backdrop {
    position: fixed; inset: 0; background: rgba(2, 6, 23, 0.72);
    display: none; align-items: center; justify-content: center; z-index: 10050; padding: 16px;
}
.plan-momo-backdrop.show { display: flex; }
.plan-momo-modal {
    width: min(420px, 100%); background: #111827; border: 1px solid rgba(56, 189, 248, 0.25);
    border-radius: 18px; padding: 22px; color: #f8fafc; box-shadow: 0 24px 60px rgba(0,0,0,.45);
}
.plan-momo-modal h4 { margin: 0 0 8px; font-size: 18px; font-weight: 800; }
.plan-momo-modal p { margin: 0 0 14px; color: #94a3b8; font-size: 13px; }
.plan-momo-amount {
    font-size: 28px; font-weight: 800; margin: 8px 0 16px; color: #38bdf8;
}
.plan-momo-label { display: block; font-size: 12px; font-weight: 700; color: #94a3b8; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
.plan-momo-phone-wrap { display: flex; gap: 8px; margin-bottom: 14px; }
.plan-momo-prefix { padding: 10px 12px; border-radius: 10px; background: #0f172a; border: 1px solid #334155; color: #cbd5e1; font-weight: 700; }
.plan-momo-phone-wrap input {
    flex: 1; border-radius: 10px; border: 1px solid #334155; background: #020617; color: #f8fafc;
    padding: 10px 12px; font-size: 15px;
}
.plan-momo-actions { display: flex; gap: 10px; justify-content: flex-end; }
.plan-momo-actions button { border: 0; border-radius: 999px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
.plan-momo-cancel { background: transparent; color: #94a3b8; border: 1px solid #334155 !important; }
.plan-momo-pay { background: linear-gradient(95deg, #2563eb, #38bdf8); color: #fff; }
.plan-momo-gateway-badge {
    display: inline-block; margin-bottom: 10px; padding: 4px 10px; border-radius: 999px;
    background: rgba(34,197,94,.12); border: 1px solid rgba(74,222,128,.35); color: #4ade80; font-size: 12px; font-weight: 700;
}
{/literal}
</style>

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Confirm')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}plan/recharge-post" id="plan-recharge-confirm-form">
                    <center><b>{Lang::T('Customer')}</b></center>
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            <b>{Lang::T('Username')}</b> <span class="pull-right">{$cust['username']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Name')}</b> <span class="pull-right">{$cust['fullname']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Phone Number')}</b> <span class="pull-right">{$cust['phonenumber']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Email')}</b> <span class="pull-right">{$cust['email']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Address')}</b> <span class="pull-right">{$cust['address']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Balance')}</b> <span
                                class="pull-right">{Lang::moneyFormat($cust['balance'])}</span>
                        </li>
                    </ul>
                    <center><b>{Lang::T('Plan')}</b></center>
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            <b>{Lang::T('Plan Name')}</b> <span class="pull-right">{$plan['name_plan']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Location')}</b> <span
                                class="pull-right">{if $plan['is_radius']}Radius{else}{$plan['routers']}{/if}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Type')}</b> <span
                                class="pull-right">{if $plan['prepaid'] eq 'yes'}Prepaid{else}Postpaid{/if}
                                {$plan['type']}</span>
                        </li>
                        <tr>
                            <td>{Lang::T('Bandwidth')}</td>
                            <td api-get-text="{Text::url('')}autoload/bw_name/{$plan['id_bw']}"></td>
                        </tr>
                        <li class="list-group-item">
                            <b>{Lang::T('Plan Price')}</b> <span
                                class="pull-right">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Plan Validity')}</b> <span class="pull-right">{$plan['validity']}
                                {$plan['validity_unit']}</span>
                        </li>
                        <li class="list-group-item">
                            <b>{Lang::T('Payment via')}</b>
                            <span class="pull-right">
                                {if $is_mobile_money_recharge}
                                    <strong>{$mobile_gateway_label}</strong>
                                    <input type="hidden" name="using" value="mobile_money">
                                {elseif $using eq 'zero'}
                                    <strong>{$_c['currency_code']} 0</strong>
                                    <input type="hidden" name="using" value="zero">
                                {elseif $using eq 'cash'}
                                    <strong>{Lang::T('Cash')}</strong>
                                    <input type="hidden" name="using" value="cash">
                                {elseif $using eq 'balance'}
                                    <strong>{Lang::T('Customer Balance')}</strong>
                                    <input type="hidden" name="using" value="balance">
                                {else}
                                    <strong>{$using|ucwords}</strong>
                                    <input type="hidden" name="using" value="{$using|escape}">
                                {/if}
                            </span>
                        </li>
                    </ul>
                    <center><b>{Lang::T('Total')}</b></center>
                    <ul class="list-group list-group-unbordered">
                        {if $tax}
                            <li class="list-group-item">
                                <b>{Lang::T('Tax')}</b> <span class="pull-right">{Lang::moneyFormat($tax)}</span>
                            </li>
                            {if $using neq 'zero' and $add_cost != 0}
                                {foreach $bills as $k => $v}
                                    {if strpos($v, ':') === false}
                                        <li class="list-group-item">
                                            <b>{$k}</b> <span class="pull-right">
                                                {Lang::moneyFormat($v)}
                                                <sup title="recurring">∞</sup>
                                            </span>
                                        </li>
                                    {else}
                                        {assign var="exp" value=explode(':',$v)}
                                        {if $exp[1]>0}
                                            <li class="list-group-item">
                                                <b>{$k}</b> <span class="pull-right">
                                                    <sup title="{$exp[1]} more times">({$exp[1]}x) </sup>
                                                    {Lang::moneyFormat($exp[0])}
                                                </span>
                                            </li>
                                        {/if}
                                    {/if}
                                {/foreach}
                                <li class="list-group-item">
                                    <b>{Lang::T('Additional Cost')}</b> <span
                                        class="pull-right"><b>{Lang::moneyFormat($add_cost)}</b></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{$plan['name_plan']}</b> <span
                                        class="pull-right">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Total')}</b> <small>({Lang::T('Plan Price')}
                                        +{Lang::T('Additional Cost')})</small><span class="pull-right"
                                        style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{Lang::moneyFormat($plan['price']+$add_cost+$tax)}</span>
                                </li>
                            {else}
                                <li class="list-group-item">
                                    <b>{Lang::T('Total')}</b> <small>({Lang::T('Plan Price')} + {Lang::T('Tax')})</small><span
                                        class="pull-right"
                                        style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price']+$tax)}{/if}</span>
                                </li>
                            {/if}
                        {else}
                            {if $using neq 'zero' and $add_cost != 0}
                                {foreach $bills as $k => $v}
                                    {if strpos($v, ':') === false}
                                        <li class="list-group-item">
                                            <b>{$k}</b> <span class="pull-right">
                                                {Lang::moneyFormat($v)}
                                                <sup title="recurring">∞</sup>
                                            </span>
                                        </li>
                                    {else}
                                        {assign var="exp" value=explode(':',$v)}
                                        {if $exp[1]>0}
                                            <li class="list-group-item">
                                                <b>{$k}</b> <span class="pull-right">
                                                    <sup title="{$exp[1]} more times">({$exp[1]}x) </sup>
                                                    {Lang::moneyFormat($exp[0])}
                                                </span>
                                            </li>
                                        {/if}
                                    {/if}
                                {/foreach}
                                <li class="list-group-item">
                                    <b>{Lang::T('Additional Cost')}</b> <span
                                        class="pull-right"><b>{Lang::moneyFormat($add_cost)}</b></span>
                                </li>
                                <li class="list-group-item">
                                    <b>{$plan['name_plan']}</b> <span
                                        class="pull-right">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>{Lang::T('Total')}</b> <small>({Lang::T('Plan Price')}
                                        +{Lang::T('Additional Cost')})</small><span class="pull-right"
                                        style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{Lang::moneyFormat($plan['price']+$add_cost)}</span>
                                </li>
                            {else}
                                <li class="list-group-item">
                                    <b>{Lang::T('Total')}</b> <span class="pull-right" id="plan-recharge-total-display"
                                        style="font-size: large; font-weight:bolder; font-family: 'Courier New', Courier, monospace; ">{if $using eq 'zero'}{Lang::moneyFormat(0)}{else}{Lang::moneyFormat($plan['price'])}{/if}</span>
                                </li>
                            {/if}
                        {/if}
                    </ul>
                    <input type="hidden" name="id_customer" value="{$cust['id']}">
                    <input type="hidden" name="plan" value="{$plan['id']}">
                    <input type="hidden" name="server" value="{$server}">
                    <input type="hidden" name="stoken" value="{App::getToken()}">
                    <center>
                        <button class="btn btn-success" type="submit" id="plan-recharge-submit-btn">{Lang::T('Recharge')}</button><br>
                        <a class="btn btn-link" href="{Text::url('')}plan/recharge">{Lang::T('Cancel')}</a>
                    </center>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="planMomoBackdrop" class="plan-momo-backdrop" aria-hidden="true">
    <div class="plan-momo-modal" role="dialog" aria-modal="true">
        <span class="plan-momo-gateway-badge" id="planMomoGateway">{$mobile_gateway_label}</span>
        <h4>Paiement Mobile Money</h4>
        <p>Confirmez le paiement sur le téléphone du client après validation.</p>
        <div class="plan-momo-amount" id="planMomoAmount"></div>
        <label class="plan-momo-label" for="planMomoPhone">Numéro Mobile Money</label>
        <div class="plan-momo-phone-wrap">
            <span class="plan-momo-prefix" id="planMomoPrefix">+237</span>
            <input type="tel" id="planMomoPhone" inputmode="numeric" autocomplete="tel-national" placeholder="677123456" maxlength="9">
        </div>
        <p id="planMomoHint" style="font-size:12px;color:#64748b;margin:0 0 12px;"></p>
        <div class="plan-momo-actions">
            <button type="button" class="plan-momo-cancel" id="planMomoCancel">Annuler</button>
            <button type="button" class="plan-momo-pay" id="planMomoPay"><i class="fa fa-mobile"></i> Payer</button>
        </div>
    </div>
</div>

<script>
window.PLAN_RECHARGE_CONFIRM = {
    isMobileMoney: {if $is_mobile_money_recharge}true{else}false{/if},
    collectUrl: {Text::url('plan/recharge-momo-collect')|@json_encode nofilter},
    statusUrl: {Text::url('plan/recharge-momo-status')|@json_encode nofilter},
    csrfToken: {$csrf_token|@json_encode nofilter},
    customerId: {$cust['id']|default:0},
    planId: {$plan['id']|default:0},
    server: {$server|@json_encode nofilter},
    amount: {$recharge_total|default:0},
    currency: {$_c['currency_code']|@json_encode nofilter},
    gatewayLabel: {$mobile_gateway_label|@json_encode nofilter},
    defaultPhone: {$cust['phonenumber']|@json_encode nofilter},
    planName: {$plan['name_plan']|@json_encode nofilter}
};
</script>
<script src="{$app_url}/ui/ui/scripts/plan-recharge-confirm.js?v=2"></script>

{include file="sections/footer.tpl"}
