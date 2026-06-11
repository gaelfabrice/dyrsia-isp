{include file="sections/header.tpl"}

{assign var=hs_title value=$_c['hotspot_page_title']|default:'yoyo'}
{assign var=hs_tagline value=$_c['hotspot_page_tagline']|default:''}
{assign var=hs_api_url value=$_c['hotspot_api_url']|default:'https://wifizones.org'}
{assign var=hs_router value=$_c['hotspot_login_router']|default:''}
{assign var=hs_color value=$_c['hotspot_login_color']|default:'green'}
{assign var=hs_shape value=$_c['hotspot_card_shape']|default:'rounded'}
{assign var=hs_display value=$_c['hotspot_card_display']|default:'auto'}
{assign var=hs_banner value=$_c['hotspot_banner_text']|default:''}
{assign var=hs_name value=$_c['hotspot_name']|default:'hotspot1'}
{assign var=hs_interface value=$_c['hotspot_interface']|default:'bridge'}
{assign var=hs_profile value=$_c['hotspot_profile']|default:'hsprof1'}
{assign var=hs_dns value=$_c['hotspot_dns_name']|default:'hotspot.monreseau.net'}
{assign var=hs_cookie value=$_c['hotspot_cookie_lifetime']|default:'1d'}
{assign var=hs_idle value=$_c['hotspot_idle_timeout']|default:'00:10:00'}
{assign var=hs_pool_mode value=$_c['hotspot_pool_mode']|default:'new'}
{assign var=hs_pool_name value=$_c['hotspot_pool_name']|default:'hs-pool'}
{assign var=hs_pool_range value=$_c['hotspot_pool_range']|default:'10.5.50.2-10.5.50.254'}
{assign var=hs_keepalive value=$_c['hotspot_keepalive_timeout']|default:'00:00:30'}
{assign var=hs_smtp value=$_c['hotspot_smtp_server']|default:'0.0.0.0'}
{assign methods explode(',', $_c['hotspot_login_methods']|default:'chap')}

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
    <div class="hs-wizard-wrap">
        <div class="hs-wizard-main">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-wifi"></i> {Lang::T('Hotspot')} — Assistant multi-étapes</h3>
                </div>
                <div class="box-body">
                    <div class="hs-step-indicators">
                        <span id="hs-indicator-1" class="active">1. Personnalisation</span>
                        <span id="hs-indicator-2">2. Configuration réseau</span>
                        <span id="hs-indicator-3">3. Finalisation</span>
                    </div>

                    {* ——— Étape 1 : Hotspot Settings ——— *}
                    <div id="hs-step-1">
                        <h4><i class="fa fa-magic"></i> {Lang::T('Hotspot Settings')}</h4>
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
                                <p class="help-block">URL du <strong>serveur DYRSIA</strong> (votre Mac/PC), <em>pas</em> l'IP du MikroTik. Ex. routeur <code>10.0.0.3</code> → mettez <code>http://10.0.0.2:8080</code> (IP du Mac sur le même réseau/VPN). Le walled-garden est créé automatiquement à l'envoi.</p>
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
                            <label class="col-md-3 control-label">{Lang::T('Color Scheme')}</label>
                            <div class="col-md-9">
                                <select name="hotspot_login_color" class="form-control">
                                    <option value="green" {if $hs_color eq 'green'}selected{/if}>Green</option>
                                    <option value="blue" {if $hs_color eq 'blue'}selected{/if}>Blue</option>
                                    <option value="red" {if $hs_color eq 'red'}selected{/if}>Red</option>
                                    <option value="dark" {if $hs_color eq 'dark'}selected{/if}>Dark</option>
                                    <option value="light" {if $hs_color eq 'light'}selected{/if}>Light</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Hotspot Card Shape')}</label>
                            <div class="col-md-9">
                                <select name="hotspot_card_shape" class="form-control">
                                    <option value="rounded" {if $hs_shape eq 'rounded'}selected{/if}>Rounded</option>
                                    <option value="square" {if $hs_shape eq 'square'}selected{/if}>Square</option>
                                    <option value="pill" {if $hs_shape eq 'pill'}selected{/if}>Pill</option>
                                </select>
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
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Panneau_publicitaire')}</label>
                            <div class="col-md-9">
                                <input type="text" name="hotspot_banner_text" class="form-control" value="{$hs_banner}" placeholder="Laisser vide pour désactiver">
                            </div>
                        </div>
                    </div>

                    {* ——— Étape 2 : Hotspot Setup Wizard ——— *}
                    <div id="hs-step-2" style="display:none;">
                        <h4><i class="fa fa-wrench"></i> {Lang::T('Hotspot Setup Wizard')}</h4>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom du Hotspot</label>
                            <div class="col-md-8">
                                <input name="hotspot_name" class="form-control" value="{$hs_name}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Interface</label>
                            <div class="col-md-8">
                                <select name="hotspot_interface" class="form-control">
                                    <option value="bridge" {if $hs_interface eq 'bridge'}selected{/if}>bridge</option>
                                    <option value="wlan1" {if $hs_interface eq 'wlan1'}selected{/if}>wlan1</option>
                                    <option value="wlan2" {if $hs_interface eq 'wlan2'}selected{/if}>wlan2</option>
                                    <option value="ether1" {if $hs_interface eq 'ether1'}selected{/if}>ether1</option>
                                    <option value="ether2" {if $hs_interface eq 'ether2'}selected{/if}>ether2</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom de profil</label>
                            <div class="col-md-8">
                                <input name="hotspot_profile" class="form-control" value="{$hs_profile}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">DNS Name</label>
                            <div class="col-md-8">
                                <input name="hotspot_dns_name" class="form-control" value="{$hs_dns}" placeholder="hotspot.monreseau.net">
                                <p class="help-block">Nom DNS du portail sur le MikroTik. À l'envoi, une entrée DNS statique <code>nom → IP du serveur</code> est créée (ex. <code>hotspot.monreseau.net → 10.0.0.2</code>).</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Méthodes de login</label>
                            <div class="col-md-8">
                                <label><input type="checkbox" name="hotspot_login_methods[]" value="chap" {if in_array('chap', $methods)}checked{/if}> HTTP CHAP</label><br>
                                <label><input type="checkbox" name="hotspot_login_methods[]" value="pap" {if in_array('pap', $methods)}checked{/if}> HTTP PAP</label><br>
                                <label><input type="checkbox" name="hotspot_login_methods[]" value="cookie" {if in_array('cookie', $methods)}checked{/if}> MAC Cookie</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Cookie Lifetime</label>
                            <div class="col-md-8">
                                <input name="hotspot_cookie_lifetime" class="form-control" value="{$hs_cookie}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Idle Timeout</label>
                            <div class="col-md-8">
                                <input name="hotspot_idle_timeout" class="form-control" value="{$hs_idle}">
                            </div>
                        </div>
                        <hr>
                        <h5><i class="fa fa-list"></i> Pool d'adresses IP</h5>
                        <div class="form-group">
                            <div class="col-md-12">
                                <label><input type="radio" name="hotspot_pool_mode" value="new" {if $hs_pool_mode neq 'existing'}checked{/if}> Nouveau pool</label><br>
                                <label><input type="radio" name="hotspot_pool_mode" value="existing" {if $hs_pool_mode eq 'existing'}checked{/if}> Pool existant</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Nom du nouveau pool</label>
                            <div class="col-md-8">
                                <input name="hotspot_pool_name" class="form-control" value="{$hs_pool_name}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Plage d'adresses IP</label>
                            <div class="col-md-8">
                                <input name="hotspot_pool_range" class="form-control" value="{$hs_pool_range}">
                            </div>
                        </div>
                        <h5><i class="fa fa-cog"></i> Paramètres avancés</h5>
                        <div class="form-group">
                            <label class="col-md-4 control-label">Keepalive Timeout</label>
                            <div class="col-md-8">
                                <input name="hotspot_keepalive_timeout" class="form-control" value="{$hs_keepalive}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label">SMTP Server</label>
                            <div class="col-md-8">
                                <input name="hotspot_smtp_server" class="form-control" value="{$hs_smtp}">
                            </div>
                        </div>
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
                                <a href="{Text::url('settings/hotspot&download_login=1')}" class="btn btn-info">
                                    <i class="fa fa-download"></i> Download Login.html
                                </a>
                                <button type="submit" name="send_mikrotik" value="1" id="hs-send-mikrotik-btn" class="btn btn-warning"
                                    onclick="return confirm('Envoyer la configuration (pool + paramètres) vers le routeur sélectionné ?');">
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
                    <iframe id="hs-real-preview" src="about:blank" data-title="{$hs_title|escape:'html'}" data-router="{$hs_router|escape:'html'}" style="width:100%;height:100%;border:0;border-radius:36px;background:#0a0c15;" title="Aperçu login hotspot"></iframe>
                </div>
            </div>
        </aside>
    </div>
</form>

<script src="{$app_url}/ui/ui/scripts/hotspot-wizard.js?2026.06.11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var titleInput = document.querySelector('input[name="hotspot_page_title"]');
    var preview = document.getElementById('hs-real-preview');
    if (!titleInput || !preview) {
        return;
    }
    var basePreviewUrl = window.location.origin + '/system/uploads/mikrotik_hotspot/login.html';
    var previewRouter = preview.getAttribute('data-router') || '';
    function updatePreviewUrl() {
        if (preview.dataset.suspended === '1') {
            return;
        }
        var qs = '?title=' + encodeURIComponent(titleInput.value || preview.getAttribute('data-title') || '');
        if (previewRouter) {
            qs += '&routername=' + encodeURIComponent(previewRouter);
        }
        preview.src = basePreviewUrl + qs;
    }
    updatePreviewUrl();
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
    if (wizardForm && sendBtn) {
        wizardForm.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            if (!submitter || submitter.name !== 'send_mikrotik') {
                return;
            }
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
                        text: 'Sélectionnez un routeur à l\'étape 1 (ex. MK) avant d\'envoyer vers MikroTik.',
                        confirmButtonText: 'OK'
                    });
                }
                return;
            }
            var sendInput = wizardForm.querySelector('input[type="hidden"][name="send_mikrotik"]');
            if (!sendInput) {
                sendInput = document.createElement('input');
                sendInput.type = 'hidden';
                sendInput.name = 'send_mikrotik';
                wizardForm.appendChild(sendInput);
            }
            sendInput.value = '1';
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi vers MikroTik…';
            // Le serveur PHP intégré est mono-thread : libérer l'iframe pour ne pas bloquer l'envoi.
            preview.dataset.suspended = '1';
            preview.src = 'about:blank';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Envoi en cours',
                    text: 'En général 10 à 30 secondes (écriture directe via API MikroTik). Ne fermez pas l\'onglet.',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: function () { Swal.showLoading(); }
                });
            }
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
