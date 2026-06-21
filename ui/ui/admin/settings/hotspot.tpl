{include file="sections/header.tpl"}

{assign var=hs_wizard_step value=$smarty.get.step|default:'1'}
{if $hs_wizard_step lt 1 or $hs_wizard_step gt 3}{assign var=hs_wizard_step value='1'}{/if}

{assign var=hs_title value=$_c['hotspot_page_title']|default:'yoyo'}
{assign var=hs_tagline value=$_c['hotspot_page_tagline']|default:''}
{assign var=hs_api_url value=$_c['hotspot_api_url']|default:'https://wifizones.org'}
{assign var=hs_router value=$_c['hotspot_login_router']|default:''}
{assign var=hs_display value=$_c['hotspot_card_display']|default:'auto'}
{assign var=hs_name value=$_c['hotspot_name']|default:''}
{assign var=hs_interface value=$_c['hotspot_interface']|default:''}
{assign var=hs_profile value=$_c['hotspot_profile']|default:'default'}
{assign var=hs_dns value=$_c['hotspot_dns_name']|default:''}
{assign var=hs_local value=$_c['hotspot_local_address']|default:'10.0.0.1/24'}
{assign var=hs_masquerade value=$_c['hotspot_masquerade']|default:'1'}
{assign var=hs_address_pool value=$_c['hotspot_address_pool']|default:''}
{assign var=hs_pool_name value=$_c['hotspot_pool_name']|default:''}
{assign var=hs_pool_range value=$_c['hotspot_pool_range']|default:'10.0.0.1-10.0.0.254'}
{assign var=hs_dns_server value=$_c['hotspot_dns_server']|default:'8.8.8.8'}
{assign var=hs_smtp value=$_c['hotspot_smtp_server']|default:'0.0.0.0'}
{assign var=hs_cookie value=$_c['hotspot_cookie_lifetime']|default:'1d 00:00:00'}
{assign var=hs_idle value=$_c['hotspot_idle_timeout']|default:'00:10:00'}
{assign var=hs_keepalive value=$_c['hotspot_keepalive_timeout']|default:'00:00:30'}
{assign var=hs_address_per_mac value=$_c['hotspot_address_per_mac']|default:'1'}
{assign var=hs_login value=','|cat:($_c['hotspot_login_methods']|default:'http-chap,mac-cookie')|cat:','}

<style>
.hs-wizard-wrap { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
.hs-wizard-main { flex: 1 1 480px; min-width: 0; max-width: calc(100% - 340px); }
.hs-wizard-preview { flex: 0 0 316px; width: 316px; max-width: 100%; position: sticky; top: 70px; }
.hs-step-indicators { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.hs-step-indicators span {
    padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    background: #ecf0f5; color: #6b7280;
}
.hs-step-indicators span.active { background: #3c8dbc; color: #fff; }
.hs-step-indicators span.done { background: #00a65a; color: #fff; }
.hs-nav-bar {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f0f0; flex-wrap: wrap; gap: 10px;
}
.hs-final-actions { display: none; flex-wrap: wrap; gap: 8px; align-items: center; }
.hs-phone {
    margin: 0 auto;
    width: 300px;
    height: 610px;
    padding: 12px;
    background: linear-gradient(145deg, #0b0f19, #2b2f3a 48%, #05070b);
    border-radius: 46px;
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 28px 70px rgba(15,23,42,.34), inset 0 0 0 2px rgba(255,255,255,.05);
    position: relative;
}
.hs-phone::before {
    content: "";
    position: absolute;
    left: -3px;
    top: 112px;
    width: 3px;
    height: 58px;
    border-radius: 4px 0 0 4px;
    background: #111827;
}
.hs-phone::after {
    content: "";
    position: absolute;
    right: -3px;
    top: 148px;
    width: 3px;
    height: 82px;
    border-radius: 0 4px 4px 0;
    background: #111827;
}
.hs-phone-notch {
    position: absolute;
    top: 22px;
    left: 50%;
    width: 92px;
    height: 26px;
    margin-left: -46px;
    background: #05070b;
    border-radius: 999px;
    z-index: 2;
    box-shadow: inset 0 -1px 0 rgba(255,255,255,.08);
}
.hs-phone-screen {
    border-radius: 36px; overflow: hidden; height: 586px;
    padding: 10px 10px 14px; font-family: Arial, sans-serif; font-size: 11px;
    position: relative;
    background: #0a0c15;
}
.hs-preview-banner {
    background: rgba(0,0,0,.35); color: #fff; padding: 4px 0; overflow: hidden; white-space: nowrap;
    margin: -10px -10px 8px; font-size: 10px;
}
.hs-banner-track { display: inline-block; animation: hs-marquee 12s linear infinite; padding-left: 100%; }
@keyframes hs-marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}
.hs-preview-title { font-size: 18px; font-weight: 800; margin: 8px 0 4px; line-height: 1.2; }
.hs-preview-tagline { font-size: 11px; margin: 0 0 10px; }
.hs-preview-order { font-size: 9px; opacity: .75; margin-bottom: 8px; }
.hs-preview-pkg {
    padding: 8px; margin-bottom: 6px; border: 1px solid rgba(255,255,255,.14);
    font-size: 10px;
}
.hs-preview-pkg b { display: block; font-size: 11px; }
.hs-preview-input {
    width: 100%; box-sizing: border-box; padding: 8px; margin: 4px 0;
    border-radius: 10px; border: 1px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.08); color: inherit; font-size: 10px;
}
.hs-preview-btn {
    width: 100%; padding: 9px; border: 0; border-radius: 10px; color: #fff;
    font-weight: 700; margin-top: 6px; font-size: 11px;
}
.hs-preview-chat {
    position: absolute; right: 12px; bottom: 14px; width: 36px; height: 36px;
    border-radius: 50%; background: #25d366; color: #fff; align-items: center;
    justify-content: center; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.3);
}
.hs-sync-status { margin: 0 0 16px; padding: 10px 14px; border-radius: 8px; font-size: 13px; display: none; }
.hs-sync-status.loading { display: block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.hs-sync-status.ok { display: block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.hs-sync-status.error { display: block; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.hs-name-picker-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch; }
.hs-name-picker-row select.hs-name-picker { flex: 0 0 42%; min-width: 140px; max-width: 100%; }
.hs-name-picker-row input.hs-name-input { flex: 1 1 180px; min-width: 0; }
@media (max-width: 640px) {
    .hs-name-picker-row select.hs-name-picker { flex: 1 1 100%; }
}
.hs-summary { background: #f9fafb; border-radius: 8px; padding: 12px 16px; }
.hs-summary-row {
    display: flex; justify-content: space-between; gap: 12px;
    padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px;
}
.hs-summary-row:last-child { border-bottom: 0; }
.hs-summary-label { color: #6b7280; font-weight: 600; flex: 0 0 42%; }
.hs-summary-value { text-align: right; color: #111827; word-break: break-word; }
@media (max-width: 991px) {
    .hs-wizard-preview { flex: 1 1 100%; width: 100%; position: static; order: -1; }
    .hs-wizard-main { flex: 1 1 100%; max-width: 100%; }
}
@media (min-width: 1200px) {
    .hs-wizard-main { flex-basis: 560px; max-width: calc(100% - 340px); }
}
</style>

<form method="post" action="{Text::url('settings/hotspot')}" id="hs-wizard-form" class="form-horizontal">
    <input type="hidden" name="send_mikrotik" id="hs-send-mikrotik-field" value="">
    <input type="hidden" name="sync_hotspot_plans" id="hs-sync-plans-field" value="">
    <input type="hidden" name="hs_wizard_step" id="hs_wizard_step" value="{$hs_wizard_step|escape}">
    <div class="hs-wizard-wrap">
        <div class="hs-wizard-main">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-wifi"></i> {Lang::T('Hotspot')} — Assistant multi-étapes</h3>
                </div>
                <div class="box-body">
                    <div class="hs-step-indicators">
                        <span id="hs-indicator-1" class="active">1. Personnalisation portail</span>
                        <span id="hs-indicator-2">2. Hotspot Setup</span>
                        <span id="hs-indicator-3">3. Finalisation</span>
                    </div>

                    {* ——— Étape 1 : Personnalisation portail ——— *}
                    <div id="hs-step-1">
                        <h4><i class="fa fa-magic"></i> Personnalisation portail</h4>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Hotspot Page Title')}</label>
                            <div class="col-md-9">
                                <input type="text" name="hotspot_page_title" class="form-control" value="{$hs_title}" placeholder="Hotspot Page Title">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Description / Tagline')}</label>
                            <div class="col-md-9">
                                <input type="text" name="hotspot_page_tagline" class="form-control" value="{$hs_tagline}" placeholder="Laisser vide pour ne rien afficher">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Hotspot API URL')}</label>
                            <div class="col-md-9">
                                <input type="text" name="hotspot_api_url" class="form-control" value="{$hs_api_url}" placeholder="https://wifizones.org">
                                <p class="help-block">Adresse du <strong>serveur DYRSIA</strong> (PHP), pas du routeur MikroTik. Production : <code>https://wifizones.org</code> — VPN : <code>http://10.0.0.1</code> (serveur). L'IP du routeur (ex. <code>10.0.0.2</code>) va dans <em>Réseau → Routeurs</em> seulement.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Router')}</label>
                            <div class="col-md-9">
                                <select name="hotspot_login_router" id="hotspot_login_router" class="form-control">
                                    {if $routers|@count == 0}
                                        {if $hs_router neq ''}
                                            <option value="{$hs_router|escape}" selected>{$hs_router|escape} (configuré)</option>
                                        {else}
                                            <option value="">{Lang::T('No routers found — add one in Network → Routers')}</option>
                                        {/if}
                                    {else}
                                        {if $hs_router eq ''}
                                            <option value="">{Lang::T('Select router')}</option>
                                        {/if}
                                        {foreach $routers as $r}
                                            <option value="{$r['name']|escape}" {if $hs_router eq $r['name']}selected{/if}>{$r['name']|escape}{if $r['description']} — {$r['description']|escape}{/if}</option>
                                        {/foreach}
                                    {/if}
                                </select>
                                <p class="help-block" style="margin-top:6px;">Le <strong>nom du routeur</strong> (Réseau → Routeurs) doit être <strong>identique</strong> à <em>MikroTik → System → Identity</em>. Les forfaits Hotspot doivent être assignés à ce même nom — aucun nom par défaut n'est utilisé.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Hotspot Card Auto/ Manual Display')}</label>
                            <div class="col-md-9">
                                <select name="hotspot_card_display" class="form-control">
                                    <option value="auto" {if $hs_display eq 'auto'}selected{/if}>Auto</option>
                                    <option value="manual" {if $hs_display eq 'manual'}selected{/if}>Manual</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {* ——— Étape 2 : Hotspot Setup ——— *}
                    <div id="hs-step-2" style="display:none;">
                        <h4><i class="fa fa-wrench"></i> {Lang::T('Hotspot_Setup')}</h4>
                        <p class="text-muted">Les champs se synchronisent automatiquement depuis le routeur sélectionné à l'étape 1.</p>
                        <div id="hs-sync-status" class="hs-sync-status"></div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom du Hotspot</label>
                            <div class="col-md-8">
                                <input name="hotspot_name" id="hotspot_name" class="form-control" value="{$hs_name|escape}" placeholder="Ex. hotspot1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">HotSpot Interface</label>
                            <div class="col-md-8">
                                <select name="hotspot_interface" id="hotspot_interface" class="form-control">
                                    {if $hs_interface neq ''}
                                        <option value="{$hs_interface|escape}" selected>{$hs_interface|escape}</option>
                                    {else}
                                        <option value="">— Sélectionnez un routeur à l'étape 1 —</option>
                                    {/if}
                                </select>
                                <p class="help-block">Interfaces physiques, virtuelles, bridge et SFP détectées sur le MikroTik.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Local address of Network</label>
                            <div class="col-md-8">
                                <input name="hotspot_local_address" id="hotspot_local_address" class="form-control" value="{$hs_local|escape}" placeholder="10.0.0.1/24">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Masquerade Network</label>
                            <div class="col-md-8">
                                <label class="checkbox-inline" style="padding-top:7px;">
                                    <input type="checkbox" name="hotspot_masquerade" id="hotspot_masquerade" value="1" {if $hs_masquerade eq '1'}checked{/if}>
                                    Activer le masquerade (NAT srcnat)
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Address Pool of Network</label>
                            <div class="col-md-8">
                                <input name="hotspot_address_pool" id="hotspot_address_pool" class="form-control" value="{if $hs_address_pool neq ''}{$hs_address_pool|escape}{else}{$hs_pool_range|escape}{/if}" placeholder="10.0.0.1-10.0.0.254">
                                <input type="hidden" name="hotspot_pool_range" id="hotspot_pool_range" value="{$hs_pool_range|escape}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom du pool</label>
                            <div class="col-md-8">
                                <div class="hs-name-picker-row">
                                    <select id="hotspot_pool_name_picker" class="form-control hs-name-picker" aria-label="Pools du routeur">
                                        <option value="">— Synchronisez le routeur —</option>
                                    </select>
                                    <input name="hotspot_pool_name" id="hotspot_pool_name" class="form-control hs-name-input" value="{$hs_pool_name|escape}" placeholder="Nom du pool" autocomplete="off">
                                </div>
                                <p class="help-block">Liste issue du MikroTik (<code>/ip pool</code>) ou saisie manuelle pour un nouveau pool.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">SMTP Server</label>
                            <div class="col-md-8">
                                <input name="hotspot_smtp_server" id="hotspot_smtp_server" class="form-control" value="{$hs_smtp|escape}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">DNS Server</label>
                            <div class="col-md-8">
                                <input name="hotspot_dns_server" id="hotspot_dns_server" class="form-control" value="{$hs_dns_server|escape}" placeholder="8.8.8.8">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">DNS Name</label>
                            <div class="col-md-8">
                                <input name="hotspot_dns_name" id="hotspot_dns_name" class="form-control" value="{$hs_dns|escape}" placeholder="Optionnel — ex. hotspot.monreseau.net">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom de profil</label>
                            <div class="col-md-8">
                                <div class="hs-name-picker-row">
                                    <select id="hotspot_profile_picker" class="form-control hs-name-picker" aria-label="Profils du routeur">
                                        <option value="default">default</option>
                                    </select>
                                    <input name="hotspot_profile" id="hotspot_profile" class="form-control hs-name-input" value="{$hs_profile|escape}" placeholder="default" autocomplete="off">
                                </div>
                                <p class="help-block">Profils hotspot (<code>/ip hotspot profile</code>) : choisissez ou saisissez un nom.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">Login</label>
                            <div class="col-md-8">
                                <p class="help-block" style="margin-top:0;margin-bottom:8px;">Méthodes d'authentification (<code>login-by</code>) appliquées sur le profil MikroTik.</p>
                                <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                    <input type="checkbox" name="hotspot_login_methods[]" value="http-chap" class="hs-login-method"{if $hs_login|strstr:',http-chap,' || $hs_login|strstr:',chap,'} checked="checked"{/if}>
                                    HTTP CHAP
                                </label>
                                <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                    <input type="checkbox" name="hotspot_login_methods[]" value="http-pap" class="hs-login-method"{if $hs_login|strstr:',http-pap,'} checked="checked"{/if}>
                                    HTTP PAP
                                </label>
                                <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                    <input type="checkbox" name="hotspot_login_methods[]" value="mac-cookie" class="hs-login-method"{if $hs_login|strstr:',mac-cookie,' || $hs_login|strstr:',cookie,'} checked="checked"{/if}>
                                    MAC COOKIE
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">HTTP Cookie Lifetime</label>
                            <div class="col-md-8">
                                <input name="hotspot_cookie_lifetime" id="hotspot_cookie_lifetime" class="form-control" value="{$hs_cookie|escape}" placeholder="1d 00:00:00">
                                <p class="help-block">Durée de validité du cookie hotspot (ex. <code>1d 00:00:00</code>).</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Idle Timeout</label>
                            <div class="col-md-8">
                                <input name="hotspot_idle_timeout" id="hotspot_idle_timeout" class="form-control" value="{$hs_idle|escape}" placeholder="00:10:00">
                                <p class="help-block">Déconnexion après inactivité (ex. <code>00:10:00</code>).</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Address Per Mac</label>
                            <div class="col-md-8">
                                <input type="number" min="1" max="255" name="hotspot_address_per_mac" id="hotspot_address_per_mac" class="form-control" value="{$hs_address_per_mac|escape}" placeholder="1">
                                <p class="help-block">Nombre d'adresses IP simultanées par adresse MAC sur le serveur hotspot.</p>
                            </div>
                        </div>

                        <input type="hidden" name="hotspot_pool_mode" value="existing">
                        <input type="hidden" name="hotspot_keepalive_timeout" value="{$hs_keepalive|escape}">
                    </div>

                    {* ——— Étape 3 : Résumé + actions ——— *}
                    <div id="hs-step-3" style="display:none;">
                        <h4><i class="fa fa-check-circle"></i> Résumé de la configuration</h4>
                        <p class="text-muted">Vérifiez vos choix avant d'enregistrer ou d'envoyer vers le routeur.</p>
                        <div id="hs-summary" class="hs-summary"></div>
                    </div>

                    <div class="hs-nav-bar">
                        <button type="button" id="hs-btn-preview" class="btn btn-default" disabled>
                            <i class="fa fa-arrow-left"></i> PREVIEW
                        </button>
                        <div>
                            <button type="button" id="hs-btn-next" class="btn btn-primary">
                                NEXT <i class="fa fa-arrow-right"></i>
                            </button>
                            <div id="hs-final-actions" class="hs-final-actions">
                                <button type="submit" name="save" value="save" class="btn btn-success">
                                    <i class="fa fa-save"></i> Save Changes
                                </button>
                                <button type="submit" value="1" id="hs-sync-plans-btn" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> Sync forfaits
                                </button>
                                <a href="{Text::url('settings/hotspot&download_login=1')}" class="btn btn-info">
                                    <i class="fa fa-download"></i> Download Login.html
                                </a>
                                <button type="submit" value="1" id="hs-send-mikrotik-btn" class="btn btn-warning">
                                    <i class="fa fa-cloud-upload"></i> Send to Mikrotik
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="hs-wizard-preview">
            <p class="text-center text-muted" style="margin-bottom:10px;"><i class="fa fa-mobile"></i> Aperçu login</p>
            <div class="hs-phone">
                <div class="hs-phone-notch"></div>
                <div id="hs-preview-screen" class="hs-phone-screen" style="padding:0;">
                    <iframe id="hs-real-preview" src="{$app_url}/system/uploads/mikrotik_hotspot/login.html?title={$hs_title|escape:'url'}&routername={$hs_router|escape:'url'}" style="width:100%;height:100%;border:0;border-radius:36px;background:#0a0c15;" title="Aperçu login hotspot"></iframe>
                </div>
            </div>
        </aside>
    </div>
</form>

<script src="{$app_url}/ui/ui/scripts/hotspot-wizard.js?2026.06.22a"></script>
<script>
window.HS_FETCH_URL = '{$hs_fetch_url|escape:'javascript'}';
window.HS_INITIAL_ROUTER = '{$hs_router|escape:'javascript'}';
window.HS_INITIAL_STEP = '{$hs_wizard_step|escape:'javascript'}';
document.addEventListener('DOMContentLoaded', function () {
    var titleInput = document.querySelector('input[name="hotspot_page_title"]');
    var preview = document.getElementById('hs-real-preview');
    if (!titleInput || !preview) {
        return;
    }
    var basePreviewUrl = '{$app_url}/system/uploads/mikrotik_hotspot/login.html';
    var previewRouter = '{$hs_router|escape:'javascript'}';
    function updatePreviewUrl() {
        var qs = '?title=' + encodeURIComponent(titleInput.value || '');
        if (previewRouter) {
            qs += '&routername=' + encodeURIComponent(previewRouter);
        }
        preview.src = basePreviewUrl + qs;
    }
    titleInput.addEventListener('input', updatePreviewUrl);
    var routerSelect = document.querySelector('select[name="hotspot_login_router"]');
    if (routerSelect) {
        routerSelect.addEventListener('change', function () {
            previewRouter = routerSelect.value || '';
            updatePreviewUrl();
        });
    }

    var wizardForm = document.getElementById('hs-wizard-form');
    var sendBtn = document.getElementById('hs-send-mikrotik-btn');
    var sendField = document.getElementById('hs-send-mikrotik-field');
    var syncPlansBtn = document.getElementById('hs-sync-plans-btn');
    var syncPlansField = document.getElementById('hs-sync-plans-field');

    function hsRequireRouterSelected(event) {
        var routerSelect = document.getElementById('hotspot_login_router');
        if (routerSelect && !routerSelect.value) {
            event.preventDefault();
            if (window.hsWizardGoToStep) {
                window.hsWizardGoToStep(1);
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Routeur requis',
                    text: 'Sélectionnez un routeur à l\'étape 1 (ex. MK) avant cette action.',
                    confirmButtonText: 'OK'
                });
            }
            return false;
        }
        return true;
    }

    if (wizardForm && syncPlansBtn) {
        syncPlansBtn.addEventListener('click', function () {
            if (syncPlansField) {
                syncPlansField.value = '1';
            }
            if (sendField) {
                sendField.value = '';
            }
        });
    }

    if (wizardForm && sendBtn) {
        sendBtn.addEventListener('click', function () {
            if (sendField) {
                sendField.value = '1';
            }
            if (syncPlansField) {
                syncPlansField.value = '';
            }
        });
        wizardForm.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            var isSendMikrotik = (sendField && sendField.value === '1') || (submitter && submitter.id === 'hs-send-mikrotik-btn');
            var isSyncPlans = (syncPlansField && syncPlansField.value === '1') || (submitter && submitter.id === 'hs-sync-plans-btn');
            if (!isSendMikrotik) {
                if (sendField) {
                    sendField.value = '';
                }
            }
            if (!isSyncPlans && syncPlansField) {
                syncPlansField.value = '';
            }
            if (isSyncPlans) {
                if (!confirm('Synchroniser uniquement les forfaits Hotspot sur le routeur (sans renvoyer login.html) ?')) {
                    event.preventDefault();
                    if (syncPlansField) {
                        syncPlansField.value = '';
                    }
                    return;
                }
                if (!hsRequireRouterSelected(event)) {
                    if (syncPlansField) {
                        syncPlansField.value = '';
                    }
                    return;
                }
                syncPlansBtn.disabled = true;
                syncPlansBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sync forfaits…';
                return;
            }
            if (!isSendMikrotik) {
                return;
            }
            if (!confirm('Envoyer la configuration (pool + paramètres) vers le routeur sélectionné ?')) {
                event.preventDefault();
                if (sendField) {
                    sendField.value = '';
                }
                return;
            }
            if (!hsRequireRouterSelected(event)) {
                if (sendField) {
                    sendField.value = '';
                }
                return;
            }
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi vers MikroTik…';
        });

        // A required field hidden in a previous wizard step blocks submission silently.
        // Jump back to its step and surface the native validation message.
        wizardForm.addEventListener('invalid', function (event) {
            var field = event.target;
            var stepEl = field.closest('[id^="hs-step-"]');
            if (stepEl && window.hsWizardGoToStep) {
                window.hsWizardGoToStep(stepEl.id.replace('hs-step-', ''));
            }
            setTimeout(function () {
                if (typeof field.reportValidity === 'function') {
                    field.reportValidity();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formulaire incomplet',
                        text: 'Sélectionnez un routeur avant d\'envoyer vers MikroTik (Réseau → Routeurs si la liste est vide).',
                        confirmButtonText: 'OK'
                    });
                }
            }, 100);
        }, true);
    }
});
</script>

{include file="sections/footer.tpl"}
