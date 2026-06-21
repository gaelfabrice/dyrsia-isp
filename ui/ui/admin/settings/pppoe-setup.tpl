{include file="sections/header.tpl"}

{assign var=ps_router value=$_c['pppoe_setup_router']|default:''}
{assign var=ps_bridge value=$_c['pppoe_setup_bridge_name']|default:'bridge-pppoe'}
{assign var=ps_ports value=$_c['pppoe_setup_bridge_ports']|default:'ether2,ether3,ether4,ether5'}
{assign var=ps_gateway value=$_c['pppoe_setup_gateway']|default:'10.10.10.1/24'}
{assign var=ps_pool_name value=$_c['pppoe_setup_pool_name']|default:'pppoe-pool'}
{assign var=ps_pool_range value=$_c['pppoe_setup_pool_range']|default:'10.10.10.2-10.10.10.254'}
{assign var=ps_profile_default value=$_c['pppoe_setup_profile_default']|default:'default'}
{assign var=ps_profile_expire value=$_c['pppoe_setup_profile_expire']|default:'EXPIRE'}
{assign var=ps_expire_rate value=$_c['pppoe_setup_expire_rate_limit']|default:''}
{assign var=ps_dns value=$_c['pppoe_setup_dns_servers']|default:'8.8.8.8,1.1.1.1'}
{assign var=ps_dns_remote value=$_c['pppoe_setup_dns_allow_remote']|default:'1'}
{assign var=ps_service value=$_c['pppoe_setup_service_name']|default:'internet'}
{assign var=ps_iface value=$_c['pppoe_setup_server_interface']|default:'bridge-pppoe'}
{assign var=ps_one_session value=$_c['pppoe_setup_one_session']|default:'1'}
{assign var=ps_mru value=$_c['pppoe_setup_max_mru']|default:'1480'}
{assign var=ps_mtu value=$_c['pppoe_setup_max_mtu']|default:'1480'}
{assign var=ps_expired_list value=$_c['pppoe_setup_expired_list']|default:'pppoe-expired'}
{assign var=ps_nat_iface value=$_c['pppoe_setup_nat_interface']|default:'ether1'}
{assign var=ps_nat value=$_c['pppoe_setup_nat_masquerade']|default:'1'}

<style>
{literal}
.ps-page { max-width: 1180px; margin: 0 auto 32px; }
.ps-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0891b2 48%, #0284c7 100%);
    color: #fff; border-radius: 18px 18px 0 0; padding: 26px 30px;
    box-shadow: 0 20px 50px rgba(8, 145, 178, .28);
}
.ps-hero h2 { margin: 0; font-weight: 800; font-size: 26px; letter-spacing: -.02em; }
.ps-hero p { margin: 8px 0 0; color: rgba(255,255,255,.82); font-size: 14px; line-height: 1.5; max-width: 720px; }
.ps-hero-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.ps-hero-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
}
.ps-shell {
    background: #fff; border-radius: 0 0 18px 18px; padding: 24px 28px 28px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .1);
}
.ps-layout { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
.ps-main { flex: 1 1 560px; min-width: 0; }
.ps-aside { flex: 0 0 300px; width: 300px; max-width: 100%; position: sticky; top: 72px; }
.ps-section {
    border: 2px solid #e5e7eb; border-radius: 16px; padding: 20px 22px;
    margin-bottom: 16px; background: #fff; transition: box-shadow .2s;
}
.ps-section:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, .06); }
.ps-section.bridge { background: #f0fdfa; border-color: #99f6e4; }
.ps-section.pool { background: #eff6ff; border-color: #bfdbfe; }
.ps-section.profile { background: #faf5ff; border-color: #e9d5ff; }
.ps-section.dns { background: #f0fdf4; border-color: #bbf7d0; }
.ps-section.server { background: #fff7ed; border-color: #fed7aa; }
.ps-section.firewall { background: #fef2f2; border-color: #fecaca; }
.ps-section.router { background: #f8fafc; border-color: #cbd5e1; }
.ps-section-head {
    display: flex; align-items: center; gap: 12px; margin-bottom: 18px;
}
.ps-section-num {
    width: 28px; height: 28px; border-radius: 8px; display: flex;
    align-items: center; justify-content: center; font-size: 12px; font-weight: 800;
    background: rgba(15, 23, 42, .08); color: #334155; flex-shrink: 0;
}
.ps-section-title { margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; letter-spacing: .02em; }
.ps-section-title i { margin-right: 4px; opacity: .75; }
.ps-field { margin-bottom: 14px; }
.ps-field:last-child { margin-bottom: 0; }
.ps-field label {
    display: block; font-size: 11px; text-transform: uppercase;
    letter-spacing: .06em; color: #64748b; font-weight: 800; margin-bottom: 6px;
}
.ps-field .form-control {
    height: 46px; border-radius: 12px; border: 2px solid #e2e8f0;
    box-shadow: none; font-size: 14px; transition: border-color .15s, box-shadow .15s;
}
.ps-field .form-control:focus {
    border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8, 145, 178, .12);
}
.ps-field-row { display: flex; flex-wrap: wrap; gap: 12px; }
.ps-field-row .ps-field { flex: 1 1 140px; min-width: 0; }
.ps-help { margin: 6px 0 0; font-size: 12px; color: #64748b; line-height: 1.45; }
.ps-toggle {
    display: flex; align-items: center; gap: 10px; min-height: 46px;
    padding: 10px 14px; border-radius: 12px; border: 2px solid #e2e8f0; background: #fff;
    cursor: pointer; user-select: none;
}
.ps-toggle input { margin: 0; width: 18px; height: 18px; accent-color: #0891b2; }
.ps-toggle span { font-size: 13px; color: #334155; font-weight: 600; }
.ps-sync-status {
    display: none; margin-top: 14px; padding: 12px 14px; border-radius: 12px;
    font-size: 13px; font-weight: 600; align-items: center; gap: 8px;
}
.ps-sync-status.loading { display: flex; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.ps-sync-status.ok { display: flex; background: #ecfdf5; color: #047857; border: 1px solid #86efac; }
.ps-sync-status.error { display: flex; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.ps-port-hints {
    margin-top: 8px; padding: 8px 12px; border-radius: 10px;
    background: rgba(8, 145, 178, .08); color: #0e7490; font-size: 12px; font-weight: 600;
}
.ps-port-hints:empty { display: none; }
.ps-summary-card {
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    color: #e2e8f0; border-radius: 16px; padding: 20px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, .25);
}
.ps-summary-card h3 {
    margin: 0 0 4px; font-size: 16px; font-weight: 800; color: #fff;
}
.ps-summary-card .ps-summary-sub { font-size: 12px; color: #94a3b8; margin-bottom: 16px; }
.ps-diagram {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px; padding: 14px; margin-bottom: 16px; font-size: 11px; line-height: 1.6;
}
.ps-diagram-node {
    display: inline-block; padding: 4px 10px; border-radius: 8px;
    background: rgba(8, 145, 178, .25); color: #67e8f9; font-weight: 700; margin: 2px 0;
}
.ps-diagram-arrow { color: #64748b; text-align: center; padding: 2px 0; }
.ps-summary-row {
    display: flex; justify-content: space-between; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,.08); font-size: 12px;
}
.ps-summary-row:last-child { border-bottom: 0; }
.ps-summary-label { color: #94a3b8; flex: 0 0 42%; }
.ps-summary-value { text-align: right; color: #f1f5f9; font-weight: 600; word-break: break-word; }
.ps-actions-bar {
    display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; padding-top: 20px;
    border-top: 2px solid #f1f5f9;
}
.ps-actions-bar .btn {
    height: 48px; border-radius: 12px; font-weight: 800; padding: 0 20px;
    display: inline-flex; align-items: center; gap: 8px;
}
.ps-btn-save {
    background: linear-gradient(135deg, #0f766e, #0891b2); border: 0; color: #fff;
}
.ps-btn-save:hover, .ps-btn-save:focus { color: #fff; opacity: .92; }
.ps-btn-send {
    background: linear-gradient(135deg, #059669, #10b981); border: 0; color: #fff;
}
.ps-btn-send:hover, .ps-btn-send:focus { color: #fff; opacity: .92; }
.ps-btn-sync {
    background: #fff; border: 2px solid #cbd5e1; color: #334155;
}
.ps-btn-sync:hover { background: #f8fafc; border-color: #94a3b8; }
@media (max-width: 991px) {
    .ps-aside { flex: 1 1 100%; width: 100%; position: static; order: -1; }
    .ps-main { flex: 1 1 100%; }
}
body.theme-dark .ps-shell,
body.dark-mode .ps-shell { background: #1e293b; }
body.theme-dark .ps-section,
body.dark-mode .ps-section { background: #0f172a; border-color: #334155; }
body.theme-dark .ps-field .form-control,
body.dark-mode .ps-field .form-control { background: #1e293b; border-color: #475569; color: #e2e8f0; }
body.theme-dark .ps-section-title,
body.dark-mode .ps-section-title { color: #f1f5f9; }
{/literal}
</style>

<div class="ps-page">
    <div class="ps-hero">
        <h2><i class="fa fa-plug"></i> PPPoE Setup</h2>
        <p>Configurez et déployez le serveur PPPoE MikroTik depuis DYRSIA — équivalent du script <code style="background:rgba(0,0,0,.2);padding:2px 8px;border-radius:6px;">mikrotik-pppoe-setup.rsc</code>.</p>
        <div class="ps-hero-badges">
            <span class="ps-hero-badge"><i class="fa fa-sitemap"></i> Bridge ether2–5</span>
            <span class="ps-hero-badge"><i class="fa fa-database"></i> Pool 10.10.10.0/24</span>
            <span class="ps-hero-badge"><i class="fa fa-cloud-upload"></i> Sync API MikroTik</span>
        </div>
    </div>

    <div class="ps-shell">
        <form method="post" action="{Text::url('settings/pppoe-setup')}" id="pppoe-setup-form">
            {csrf_field()}

            <div class="ps-layout">
                <div class="ps-main">

                    <div class="ps-section router">
                        <div class="ps-section-head">
                            <span class="ps-section-num">●</span>
                            <h3 class="ps-section-title"><i class="fa fa-server"></i> Routeur cible</h3>
                        </div>
                        <div class="ps-field">
                            <label>Routeur MikroTik</label>
                            <select name="pppoe_setup_router" id="pppoe_setup_router" class="form-control" required>
                                <option value="">— Sélectionner un routeur —</option>
                                {foreach $routers as $r}
                                    <option value="{$r['name']|escape}" {if $ps_router eq $r['name']}selected{/if}>{$r['name']|escape}{if $r['description']} — {$r['description']|escape}{/if}</option>
                                {/foreach}
                            </select>
                            <p class="ps-help">Identique à <strong>Réseau → Routeurs</strong> et à l'identité MikroTik (ex. RB2011).</p>
                        </div>
                        <div id="ps-sync-status" class="ps-sync-status"></div>
                    </div>

                    <div class="ps-section bridge">
                        <div class="ps-section-head">
                            <span class="ps-section-num">0</span>
                            <h3 class="ps-section-title"><i class="fa fa-sitemap"></i> Bridge &amp; réseau LAN</h3>
                        </div>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Nom du bridge</label>
                                <input name="pppoe_setup_bridge_name" id="pppoe_setup_bridge_name" class="form-control ps-live" value="{$ps_bridge|escape}" placeholder="bridge-pppoe">
                            </div>
                            <div class="ps-field">
                                <label>Passerelle PPPoE</label>
                                <input name="pppoe_setup_gateway" id="pppoe_setup_gateway" class="form-control ps-live" value="{$ps_gateway|escape}" placeholder="10.10.10.1/24">
                            </div>
                        </div>
                        <div class="ps-field">
                            <label>Ports membres (virgules)</label>
                            <input name="pppoe_setup_bridge_ports" id="pppoe_setup_bridge_ports" class="form-control ps-live" value="{$ps_ports|escape}" placeholder="ether2,ether3,ether4,ether5">
                            <div id="ps-port-hints" class="ps-port-hints"></div>
                        </div>
                    </div>

                    <div class="ps-section pool">
                        <div class="ps-section-head">
                            <span class="ps-section-num">1</span>
                            <h3 class="ps-section-title"><i class="fa fa-database"></i> Pool IP clients</h3>
                        </div>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Nom du pool</label>
                                <input name="pppoe_setup_pool_name" id="pppoe_setup_pool_name" class="form-control ps-live" value="{$ps_pool_name|escape}">
                            </div>
                            <div class="ps-field">
                                <label>Plage IP</label>
                                <input name="pppoe_setup_pool_range" id="pppoe_setup_pool_range" class="form-control ps-live" value="{$ps_pool_range|escape}">
                            </div>
                        </div>
                    </div>

                    <div class="ps-section profile">
                        <div class="ps-section-head">
                            <span class="ps-section-num">2</span>
                            <h3 class="ps-section-title"><i class="fa fa-user-circle"></i> Profils PPP</h3>
                        </div>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Profil par défaut</label>
                                <input name="pppoe_setup_profile_default" id="pppoe_setup_profile_default" class="form-control ps-live" value="{$ps_profile_default|escape}">
                            </div>
                            <div class="ps-field">
                                <label>Profil EXPIRE</label>
                                <input name="pppoe_setup_profile_expire" id="pppoe_setup_profile_expire" class="form-control ps-live" value="{$ps_profile_expire|escape}">
                            </div>
                        </div>
                        <div class="ps-field">
                            <label>Rate-limit EXPIRE</label>
                            <input name="pppoe_setup_expire_rate_limit" id="pppoe_setup_expire_rate_limit" class="form-control ps-live" value="{$ps_expire_rate|escape}" placeholder="(aucun — suspension firewall)">
                            <p class="help-block">Le profil <strong>EXPIRE</strong> n'utilise pas de rate-limit : l'accès Internet est suspendu via la liste firewall <code>pppoe-expired</code> (session PPPoE maintenue).</p>
                        </div>
                    </div>

                    <div class="ps-section dns">
                        <div class="ps-section-head">
                            <span class="ps-section-num">3</span>
                            <h3 class="ps-section-title"><i class="fa fa-globe"></i> DNS</h3>
                        </div>
                        <div class="ps-field">
                            <label>Serveurs DNS</label>
                            <input name="pppoe_setup_dns_servers" id="pppoe_setup_dns_servers" class="form-control ps-live" value="{$ps_dns|escape}">
                        </div>
                        <label class="ps-toggle">
                            <input type="checkbox" name="pppoe_setup_dns_allow_remote" id="pppoe_setup_dns_allow_remote" value="1" class="ps-live-check" {if $ps_dns_remote eq '1'}checked{/if}>
                            <span>Allow remote requests (DNS pour les clients)</span>
                        </label>
                    </div>

                    <div class="ps-section server">
                        <div class="ps-section-head">
                            <span class="ps-section-num">4</span>
                            <h3 class="ps-section-title"><i class="fa fa-bolt"></i> Serveur PPPoE</h3>
                        </div>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Service name</label>
                                <input name="pppoe_setup_service_name" id="pppoe_setup_service_name" class="form-control ps-live" value="{$ps_service|escape}" placeholder="internet">
                            </div>
                            <div class="ps-field">
                                <label>Interface serveur</label>
                                <input name="pppoe_setup_server_interface" id="pppoe_setup_server_interface" class="form-control ps-live" value="{$ps_iface|escape}">
                            </div>
                        </div>
                        <label class="ps-toggle" style="margin-bottom:14px;">
                            <input type="checkbox" name="pppoe_setup_one_session" id="pppoe_setup_one_session" value="1" class="ps-live-check" {if $ps_one_session eq '1'}checked{/if}>
                            <span>One session per host</span>
                        </label>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Max MRU</label>
                                <input name="pppoe_setup_max_mru" id="pppoe_setup_max_mru" class="form-control ps-live" value="{$ps_mru|escape}">
                            </div>
                            <div class="ps-field">
                                <label>Max MTU</label>
                                <input name="pppoe_setup_max_mtu" id="pppoe_setup_max_mtu" class="form-control ps-live" value="{$ps_mtu|escape}">
                            </div>
                        </div>
                    </div>

                    <div class="ps-section firewall">
                        <div class="ps-section-head">
                            <span class="ps-section-num">5–6</span>
                            <h3 class="ps-section-title"><i class="fa fa-shield"></i> Firewall &amp; NAT</h3>
                        </div>
                        <div class="ps-field-row">
                            <div class="ps-field">
                                <label>Liste clients expirés</label>
                                <input name="pppoe_setup_expired_list" id="pppoe_setup_expired_list" class="form-control ps-live" value="{$ps_expired_list|escape}">
                            </div>
                            <div class="ps-field">
                                <label>Interface NAT (WAN)</label>
                                <input name="pppoe_setup_nat_interface" id="pppoe_setup_nat_interface" class="form-control ps-live" value="{$ps_nat_iface|escape}" placeholder="ether1">
                            </div>
                        </div>
                        <label class="ps-toggle">
                            <input type="checkbox" name="pppoe_setup_nat_masquerade" id="pppoe_setup_nat_masquerade" value="1" class="ps-live-check" {if $ps_nat eq '1'}checked{/if}>
                            <span>Masquerade NAT vers Internet</span>
                        </label>
                    </div>

                    <div class="ps-actions-bar">
                        <button type="submit" name="save" value="save" class="btn ps-btn-save">
                            <i class="fa fa-save"></i> Enregistrer
                        </button>
                        <button type="submit" name="send_mikrotik" value="1" class="btn ps-btn-send" id="ps-send-mikrotik">
                            <i class="fa fa-cloud-upload"></i> Envoyer vers MikroTik
                        </button>
                        <button type="button" class="btn ps-btn-sync" id="ps-sync-btn">
                            <i class="fa fa-refresh"></i> Sync routeur
                        </button>
                    </div>
                </div>

                <aside class="ps-aside">
                    <div class="ps-summary-card">
                        <h3><i class="fa fa-eye"></i> Aperçu déploiement</h3>
                        <p class="ps-summary-sub">Résumé live avant envoi API</p>
                        <div class="ps-diagram">
                            <div class="ps-diagram-node" id="ps-diag-ports">ether2,3,4,5</div>
                            <div class="ps-diagram-arrow">↓ bridge</div>
                            <div class="ps-diagram-node" id="ps-diag-bridge">bridge-pppoe</div>
                            <div class="ps-diagram-arrow">↓ PPPoE « internet »</div>
                            <div class="ps-diagram-node" id="ps-diag-pool">10.10.10.0/24</div>
                            <div class="ps-diagram-arrow">↓ NAT</div>
                            <div class="ps-diagram-node" id="ps-diag-wan">ether1 → Internet</div>
                        </div>
                        <div id="ps-summary-list">
                            <div class="ps-summary-row"><span class="ps-summary-label">Routeur</span><span class="ps-summary-value" id="ps-sum-router">—</span></div>
                            <div class="ps-summary-row"><span class="ps-summary-label">Bridge</span><span class="ps-summary-value" id="ps-sum-bridge">—</span></div>
                            <div class="ps-summary-row"><span class="ps-summary-label">Pool</span><span class="ps-summary-value" id="ps-sum-pool">—</span></div>
                            <div class="ps-summary-row"><span class="ps-summary-label">Service</span><span class="ps-summary-value" id="ps-sum-service">—</span></div>
                            <div class="ps-summary-row"><span class="ps-summary-label">Profils</span><span class="ps-summary-value" id="ps-sum-profiles">—</span></div>
                            <div class="ps-summary-row"><span class="ps-summary-label">NAT</span><span class="ps-summary-value" id="ps-sum-nat">—</span></div>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

<script src="{$app_url}/ui/ui/scripts/pppoe-setup.js?2026.06.21b"></script>
<script>
window.PPPOE_FETCH_URL = '{$pppoe_fetch_url|escape:'javascript'}';
window.PPPOE_INITIAL_ROUTER = '{$ps_router|escape:'javascript'}';
</script>

{include file="sections/footer.tpl"}
