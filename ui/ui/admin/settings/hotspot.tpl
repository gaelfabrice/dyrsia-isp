    {include file="sections/header.tpl"}

    {assign var=hs_wizard_step value=$smarty.get.step|default:'1'}
    {if $hs_wizard_step lt 1 or $hs_wizard_step gt 4}{assign var=hs_wizard_step value='1'}{/if}

    {assign var=hs_title value=$_c['hotspot_page_title']|default:'yoyo'}
    {assign var=hs_tagline value=$_c['hotspot_page_tagline']|default:''}
    {assign var=hs_contact value=$hs_contact|default:'Assistance'}
    {assign var=hs_contact_phone value=$hs_contact_phone|default:''}
    {assign var=hs_api_url value=$_c['hotspot_api_url']|default:'https://wifizones.org'}
    {assign var=hs_router value=$hs_router|default:''}
    {assign var=hs_display value=$_c['hotspot_card_display']|default:'auto'}
    {assign var=hs_name value=$_c['hotspot_name']|default:''}
    {assign var=hs_interface value=$_c['hotspot_interface']|default:'bridge-hotspot'}
    {assign var=hs_bridge_ports value=$_c['hotspot_bridge_ports']|default:'wlan1,ether3'}
    {assign var=hs_lan_bridge value=$_c['lan_bridge_name']|default:'bridge-lan'}
    {assign var=hs_trunk_enabled value='0'}
    {assign var=hs_trunk_ports value=$_c['lan_trunk_bridge_ports']|default:'ether2,ether3,ether4,ether5'}
    {assign var=hs_wan_interface value=$_c['lan_wan_interface']|default:'ether1'}
    {assign var=hs_management_bridge value=$_c['lan_management_bridge_name']|default:'bridge-management'}
    {assign var=hs_management_interface value=$_c['lan_management_interface']|default:'ether2'}
    {assign var=hs_management_address value=$_c['lan_management_address']|default:'192.168.88.1/24'}
    {assign var=hs_hotspot_access_ports value=$_c['lan_hotspot_access_ports']|default:'ether3,wlan1'}
    {assign var=hs_pppoe_access_ports value=$_c['lan_pppoe_access_ports']|default:''}
    {assign var=hs_trunk_uplink_ports value=$_c['lan_trunk_uplink_ports']|default:'ether4'}
    {assign var=hs_unused_ports value=$_c['lan_unused_ports']|default:'pwr-line1'}
    {assign var=hs_vlan_id value=$_c['hotspot_vlan_id']|default:'10'}
    {assign var=hs_vlan_iface value=$_c['hotspot_vlan_interface']|default:''}
    {assign var=hs_profile value=$_c['hotspot_profile']|default:'default'}
    {assign var=hs_dns value=$_c['hotspot_dns_name']|default:''}
    {assign var=hs_local value=$_c['hotspot_local_address']|default:'10.10.0.1/24'}
    {assign var=hs_masquerade value=$_c['hotspot_masquerade']|default:'1'}
    {assign var=hs_address_pool value=$_c['hotspot_address_pool']|default:''}
    {assign var=hs_pool_name value=$_c['hotspot_pool_name']|default:''}
    {assign var=hs_pool_range value=$_c['hotspot_pool_range']|default:'10.10.0.10-10.10.0.254'}
    {assign var=hs_dns_server value=$_c['hotspot_dns_server']|default:'8.8.8.8'}
    {assign var=hs_smtp value=$_c['hotspot_smtp_server']|default:'0.0.0.0'}
    {assign var=hs_cookie value=$_c['hotspot_cookie_lifetime']|default:'1d 00:00:00'}
    {assign var=hs_idle value=$_c['hotspot_idle_timeout']|default:'00:10:00'}
    {assign var=hs_keepalive value=$_c['hotspot_keepalive_timeout']|default:'00:00:30'}
    {assign var=hs_address_per_mac value=$_c['hotspot_address_per_mac']|default:'1'}
    {assign var=hs_login value=','|cat:($_c['hotspot_login_methods']|default:'http-pap,mac-cookie')|cat:','}
    {assign var=hs_use_radius value=$_c['hotspot_use_radius']|default:'1'}
    {assign var=ps_gateway value=$_c['pppoe_setup_gateway']|default:'10.10.10.1/24'}
    {assign var=ps_vlan_id value=$_c['pppoe_setup_vlan_id']|default:'20'}
    {assign var=ps_vlan_iface value=$_c['pppoe_setup_vlan_interface']|default:''}
    {assign var=ps_pool_name value=$_c['pppoe_setup_pool_name']|default:'pppoe-pool'}
    {assign var=ps_pool_range value=$_c['pppoe_setup_pool_range']|default:'10.10.10.2-10.10.10.254'}
    {assign var=ps_profile_default value=$_c['pppoe_setup_profile_default']|default:'default'}
    {assign var=ps_profile_expire value=$_c['pppoe_setup_profile_expire']|default:'EXPIRE'}
    {assign var=ps_expire_rate value=$_c['pppoe_setup_expire_rate_limit']|default:''}
    {assign var=ps_dns value=$_c['pppoe_setup_dns_servers']|default:'8.8.8.8,1.1.1.1'}
    {assign var=ps_dns_remote value=$_c['pppoe_setup_dns_allow_remote']|default:'1'}
    {assign var=ps_service value=$_c['pppoe_setup_service_name']|default:'internet'}
    {assign var=ps_iface value=$_c['pppoe_setup_server_interface']|default:''}
    {assign var=ps_one_session value=$_c['pppoe_setup_one_session']|default:'1'}
    {assign var=ps_mru value=$_c['pppoe_setup_max_mru']|default:'1480'}
    {assign var=ps_mtu value=$_c['pppoe_setup_max_mtu']|default:'1480'}
    {assign var=ps_expired_list value=$_c['pppoe_setup_expired_list']|default:'pppoe-expired'}
    {assign var=ps_nat_iface value=$_c['pppoe_setup_nat_interface']|default:'ether1'}
    {assign var=ps_nat value=$_c['pppoe_setup_nat_masquerade']|default:'1'}
    {assign var=ps_on_hotspot value=$_c['pppoe_setup_on_hotspot']|default:'0'}

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
    .hs-trunk-panel { display: none; margin-bottom: 8px; }
    .hs-trunk-panel.active { display: block; }
    .hs-router-port-picker { margin: 12px 0 8px; max-width: 100%; overflow-x: auto; }
    .hs-rb-chassis {
        position: relative;
        min-width: 340px;
        background: linear-gradient(165deg, #e8e8e8 0%, #cfcfcf 32%, #b8b8b8 68%, #a8a8a8 100%);
        border: 1px solid #8b9199;
        border-radius: 7px;
        box-shadow: 0 12px 32px rgba(15,23,42,.16), inset 0 1px 0 rgba(255,255,255,.9), inset 0 -3px 0 rgba(0,0,0,.08);
        overflow: hidden;
    }
    .hs-rb-vents {
        position: absolute; top: 8px; right: 14px; display: flex; gap: 3px; opacity: .45;
    }
    .hs-rb-vents span {
        display: block; width: 22px; height: 3px; border-radius: 1px;
        background: repeating-linear-gradient(90deg, #6b7280 0 2px, transparent 2px 4px);
    }
    .hs-rb-top {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 10px 16px 8px;
        background: linear-gradient(180deg, #f8f8f8, #e5e5e5);
        border-bottom: 1px solid #b8b8b8;
    }
    .hs-rb-brand-wrap { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .hs-rb-logo {
        width: 34px; height: 34px; border-radius: 4px; flex-shrink: 0;
        background: linear-gradient(145deg, #ef4444 0%, #b91c1c 100%);
        color: #fff; font-weight: 900; font-size: 18px; line-height: 34px; text-align: center;
        box-shadow: inset 0 -2px 0 rgba(0,0,0,.2), 0 1px 2px rgba(0,0,0,.15);
        font-family: Arial, Helvetica, sans-serif;
    }
    .hs-rb-brand-text { min-width: 0; }
    .hs-rb-brand { display: block; color: #1f2937; font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .hs-rb-model { display: block; color: #6b7280; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
    .hs-rb-status { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .hs-rb-status-led {
        width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
        box-shadow: 0 0 6px rgba(34,197,94,.75); animation: hs-led-pulse 2s ease-in-out infinite;
    }
    @keyframes hs-led-pulse { 0%,100% { opacity: 1; } 50% { opacity: .45; } }
    .hs-rb-status-label { font-size: 10px; color: #6b7280; font-weight: 700; text-transform: uppercase; }
    .hs-rb-faceplate {
        position: relative;
        padding: 20px 18px 16px;
        background: linear-gradient(180deg, #d1d1d1 0%, #b0b0b0 48%, #9a9a9a 100%);
        border-top: 1px solid rgba(255,255,255,.4);
        box-shadow: inset 0 2px 8px rgba(0,0,0,.12);
    }
    .hs-rb-faceplate::before,
    .hs-rb-faceplate::after {
        content: ""; position: absolute; top: 10px; width: 7px; height: 7px; border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #e5e7eb, #6b7280 70%);
        box-shadow: inset 0 1px 2px rgba(0,0,0,.35);
    }
    .hs-rb-faceplate::before { left: 10px; }
    .hs-rb-faceplate::after { right: 10px; }
    .hs-rb-faceplate-title {
        font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
        color: #4b5563; margin-bottom: 12px;
    }
    .hs-rb-port-row {
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 6px 4px;
    }
    .hs-rb-port-group {
        position: relative; margin-top: 14px;
        display: flex; align-items: flex-end; gap: 5px; padding: 8px 10px 6px;
        border-radius: 5px;
        background: linear-gradient(180deg, rgba(0,0,0,.03), rgba(0,0,0,.08));
        border: 1px solid rgba(0,0,0,.1);
        box-shadow: inset 0 2px 5px rgba(0,0,0,.15), 0 1px 0 rgba(255,255,255,.25);
    }
    .hs-rb-port-group.wan-group {
        margin-right: 12px; padding-right: 14px;
        border-right: 2px solid rgba(0,0,0,.14);
    }
    .hs-rb-port-group.lan-group::before {
        content: "LAN"; position: absolute; margin-top: -18px; margin-left: 2px;
        font-size: 8px; font-weight: 800; letter-spacing: .14em; color: #4b5563; opacity: .7;
    }
    .hs-rb-port-group.wan-group::before {
        content: "WAN"; position: absolute; margin-top: -18px; margin-left: 2px;
        font-size: 8px; font-weight: 800; letter-spacing: .14em; color: #92400e; opacity: .8;
    }
    .hs-port-btn {
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        padding: 4px 3px 2px; border: none; background: transparent; cursor: pointer;
        border-radius: 4px; transition: background .15s, transform .1s;
    }
    .hs-port-btn:hover:not(:disabled):not(.wan) { background: rgba(59,130,246,.08); }
    .hs-port-btn:active:not(:disabled):not(.wan) { transform: scale(.96); }
    .hs-port-btn:disabled { cursor: default; }
    .hs-port-btn.selected { background: rgba(34,197,94,.1); }
    .hs-rj45-stack { display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .hs-rj45-led {
        width: 7px; height: 7px; border-radius: 50%; background: #1f2937;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.08);
        transition: background .15s, box-shadow .15s;
    }
    .hs-port-btn.selected .hs-rj45-led {
        background: #4ade80; box-shadow: 0 0 6px rgba(74,222,128,.95), inset 0 0 2px rgba(255,255,255,.4);
    }
    .hs-port-btn.wan .hs-rj45-led {
        background: #fbbf24; box-shadow: 0 0 6px rgba(251,191,36,.9), inset 0 0 2px rgba(255,255,255,.35);
    }
    .hs-rj45-housing {
        padding: 3px 4px 2px;
        background: linear-gradient(180deg, #9ca3af, #6b7280);
        border: 1px solid #4b5563;
        border-radius: 3px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.25), 0 1px 2px rgba(0,0,0,.2);
    }
    .hs-rj45-jack {
        width: 38px; height: 32px; position: relative;
        background: linear-gradient(180deg, #3a3a3a 0%, #1a1a1a 42%, #0d0d0d 100%);
        border: 1px solid #000; border-radius: 2px 2px 1px 1px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,.65), inset 0 -1px 0 rgba(255,255,255,.08);
    }
    .hs-rj45-clip {
        position: absolute; top: -5px; left: 50%; margin-left: -10px;
        width: 20px; height: 6px;
        background: linear-gradient(180deg, #6b7280 0%, #374151 100%);
        border: 1px solid #111; border-radius: 2px 2px 0 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
    }
    .hs-rj45-clip::after {
        content: ""; position: absolute; left: 3px; right: 3px; top: 1px; height: 2px;
        background: rgba(255,255,255,.12); border-radius: 1px;
    }
    .hs-rj45-pins {
        position: absolute; left: 5px; right: 5px; bottom: 5px; height: 12px;
        background:
            repeating-linear-gradient(90deg,
                #b8860b 0 2px, #ffd700 2px 3px, #8b6914 3px 4px, transparent 4px 5px);
        border-radius: 0 0 1px 1px;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.4);
        opacity: .92;
    }
    .hs-rj45-jack::before {
        content: ""; position: absolute; left: 3px; right: 3px; top: 6px; height: 8px;
        background: linear-gradient(180deg, #111 0%, #000 100%);
        border-radius: 1px;
        box-shadow: inset 0 2px 3px rgba(0,0,0,.8);
    }
    .hs-port-btn.selected .hs-rj45-jack {
        box-shadow: inset 0 2px 4px rgba(0,0,0,.65), inset 0 -1px 0 rgba(255,255,255,.08), 0 0 0 2px rgba(34,197,94,.6);
    }
    .hs-port-btn.wan .hs-rj45-jack {
        box-shadow: inset 0 2px 4px rgba(0,0,0,.65), inset 0 -1px 0 rgba(255,255,255,.08), 0 0 0 2px rgba(245,158,11,.5);
    }
    .hs-port-btn.selected .hs-rj45-housing {
        background: linear-gradient(180deg, #86efac, #22c55e);
        border-color: #15803d;
    }
    .hs-port-btn.wan .hs-rj45-housing {
        background: linear-gradient(180deg, #fcd34d, #d97706);
        border-color: #b45309;
    }
    .hs-port-label { font-size: 9px; font-weight: 800; color: #374151; letter-spacing: .03em; font-family: Consolas, Monaco, monospace; }
    .hs-port-btn.selected .hs-port-label { color: #15803d; }
    .hs-port-btn.wan .hs-port-label { color: #b45309; }
    .hs-port-role { font-size: 8px; color: #6b7280; text-transform: uppercase; font-weight: 700; letter-spacing: .04em; }
    .hs-port-btn.selected .hs-port-role { color: #16a34a; }
    .hs-port-btn.wan .hs-port-role { color: #d97706; }
    .hs-port-picker-legend {
        display: flex; flex-wrap: wrap; gap: 16px; margin-top: 14px; font-size: 12px; color: #64748b;
    }
    .hs-port-picker-legend span { display: inline-flex; align-items: center; gap: 7px; }
    .hs-legend-jack {
        display: inline-block; width: 18px; height: 14px; border-radius: 2px;
        background: linear-gradient(180deg, #9ca3af, #6b7280);
        border: 1px solid #4b5563;
        position: relative; vertical-align: middle;
    }
    .hs-legend-jack::before {
        content: ""; position: absolute; inset: 3px 2px 2px;
        background: #111; border-radius: 1px;
    }
    .hs-legend-jack.selected {
        background: linear-gradient(180deg, #86efac, #22c55e);
        border-color: #15803d;
        box-shadow: 0 0 0 1px rgba(34,197,94,.35);
    }
    .hs-legend-jack.wan {
        background: linear-gradient(180deg, #fcd34d, #d97706);
        border-color: #b45309;
    }
    .hs-legend-led {
        width: 8px; height: 8px; border-radius: 50%; display: inline-block;
        background: #374151; box-shadow: inset 0 1px 1px rgba(0,0,0,.4);
    }
    .hs-legend-led.on { background: #4ade80; box-shadow: 0 0 4px rgba(74,222,128,.8); }
    .hs-legend-led.wan { background: #fbbf24; box-shadow: 0 0 4px rgba(251,191,36,.8); }
    .hs-port-picker-empty {
        padding: 24px 16px; text-align: center; color: #64748b; font-size: 13px;
        border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;
    }
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
    .hs-ppp-hero {
        display: flex; align-items: center; gap: 14px;
        background: linear-gradient(120deg, #0f172a 0%, #1e3a5f 60%, #155e75 100%);
        border-radius: 14px; padding: 16px 20px; margin-bottom: 14px;
        box-shadow: 0 10px 26px rgba(15,23,42,.18);
    }
    .hs-ppp-hero-icon {
        flex: 0 0 46px; width: 46px; height: 46px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(56,189,248,.16); border: 1px solid rgba(56,189,248,.4);
        color: #7dd3fc; font-size: 20px;
    }
    .hs-ppp-hero-text h4 { margin: 0 0 3px; color: #f8fafc; font-size: 17px; font-weight: 800; }
    .hs-ppp-hero-text p { margin: 0; color: #94a3b8; font-size: 12.5px; line-height: 1.45; }
    .hs-ppp-flow {
        display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
        margin: 0 0 18px; padding: 10px 14px;
        background: #f0f9ff; border: 1px dashed #7dd3fc; border-radius: 10px;
        font-size: 11.5px; color: #0c4a6e;
    }
    .hs-ppp-flow-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff; border: 1px solid #bae6fd; border-radius: 999px;
        padding: 4px 11px; font-weight: 700; color: #075985; white-space: nowrap;
    }
    .hs-ppp-flow-chip i { color: #0ea5e9; }
    .hs-ppp-flow-arrow { color: #38bdf8; font-size: 13px; }
    .hs-ppp-grid { display: flex; flex-wrap: wrap; gap: 14px; }
    .hs-ppp-card {
        flex: 1 1 calc(50% - 7px); min-width: 260px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 0; overflow: hidden;
        box-shadow: 0 2px 10px rgba(15,23,42,.05);
        transition: box-shadow .2s, border-color .2s;
    }
    .hs-ppp-card:hover { box-shadow: 0 8px 22px rgba(15,23,42,.1); border-color: #cbd5e1; }
    .hs-ppp-card.wide { flex: 1 1 100%; }
    .hs-ppp-card-head {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(180deg, #fafbfd, #f5f7fa);
    }
    .hs-ppp-card-icon {
        width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 14px;
    }
    .hs-ppp-card-head h5 { margin: 0; font-size: 13.5px; font-weight: 800; color: #0f172a; letter-spacing: .01em; }
    .hs-ppp-card-head small { display: block; margin-top: 1px; font-size: 11px; color: #94a3b8; font-weight: 500; }
    .hs-ppp-ico-blue   { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .hs-ppp-ico-green  { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .hs-ppp-ico-purple { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
    .hs-ppp-ico-cyan   { background: #ecfeff; color: #0891b2; border: 1px solid #a5f3fc; }
    .hs-ppp-ico-amber  { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .hs-ppp-ico-red    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .hs-ppp-card-body { padding: 14px 16px 16px; }
    .hs-ppp-field { margin-bottom: 12px; }
    .hs-ppp-field:last-child { margin-bottom: 0; }
    .hs-ppp-field > label {
        display: block; margin: 0 0 5px; font-size: 11px; font-weight: 700;
        color: #475569; text-transform: uppercase; letter-spacing: .05em;
    }
    .hs-ppp-field .form-control {
        border-radius: 9px; border-color: #dbe3ec; box-shadow: none;
        height: 38px; font-size: 13px; transition: border-color .15s, box-shadow .15s;
    }
    .hs-ppp-field .form-control:focus {
        border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,.15);
    }
    .hs-ppp-field .hs-ppp-hint { margin: 5px 0 0; font-size: 11px; color: #94a3b8; }
    .hs-ppp-cols { display: flex; gap: 10px; }
    .hs-ppp-cols > .hs-ppp-field { flex: 1; margin-bottom: 0; }
    .hs-ppp-field > label.hs-ppp-switch,
    .hs-ppp-switch {
        display: flex; align-items: center; gap: 10px; cursor: pointer;
        padding: 9px 12px; margin: 0; border-radius: 10px;
        background: #f8fafc; border: 1px solid #e8eef5;
        font-weight: 600; color: #334155; user-select: none;
        font-size: 12.5px !important;
        text-transform: none !important; letter-spacing: normal !important;
        position: relative;
    }
    .hs-ppp-switch input { position: absolute; opacity: 0; pointer-events: none; }
    .hs-ppp-switch .hs-ppp-track {
        flex-shrink: 0; width: 36px; height: 20px; border-radius: 999px;
        background: #cbd5e1; position: relative; transition: background .2s;
    }
    .hs-ppp-switch .hs-ppp-track::after {
        content: ""; position: absolute; top: 2px; left: 2px;
        width: 16px; height: 16px; border-radius: 50%; background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.3); transition: left .2s;
    }
    .hs-ppp-switch input:checked + .hs-ppp-track { background: #10b981; }
    .hs-ppp-switch input:checked + .hs-ppp-track::after { left: 18px; }
    .hs-ppp-switch input:focus-visible + .hs-ppp-track { box-shadow: 0 0 0 3px rgba(16,185,129,.25); }
    @media (max-width: 700px) {
        .hs-ppp-card { flex-basis: 100%; }
        .hs-ppp-cols { flex-direction: column; gap: 12px; }
    }
    body.theme-dark .hs-ppp-card,
    body.dark-mode .hs-ppp-card {
        background: #111827; border-color: #1f2937;
        box-shadow: 0 2px 10px rgba(0,0,0,.3);
    }
    body.theme-dark .hs-ppp-card:hover,
    body.dark-mode .hs-ppp-card:hover { border-color: #334155; box-shadow: 0 8px 22px rgba(0,0,0,.4); }
    body.theme-dark .hs-ppp-card-head,
    body.dark-mode .hs-ppp-card-head {
        background: linear-gradient(180deg, #16202f, #131c2a); border-bottom-color: #1f2937;
    }
    body.theme-dark .hs-ppp-card-head h5,
    body.dark-mode .hs-ppp-card-head h5 { color: #e2e8f0; }
    body.theme-dark .hs-ppp-field > label,
    body.dark-mode .hs-ppp-field > label { color: #94a3b8; }
    body.theme-dark .hs-ppp-switch,
    body.dark-mode .hs-ppp-switch { background: #0f172a; border-color: #1f2937; color: #cbd5e1; }
    body.theme-dark .hs-ppp-flow,
    body.dark-mode .hs-ppp-flow { background: rgba(14,165,233,.08); border-color: rgba(56,189,248,.35); color: #bae6fd; }
    body.theme-dark .hs-ppp-flow-chip,
    body.dark-mode .hs-ppp-flow-chip { background: #0f172a; border-color: rgba(56,189,248,.3); color: #7dd3fc; }
    .hs-step-indicators span.hs-step-pppoe { display: none; }
    .hs-step-indicators.trunk-mode span.hs-step-pppoe { display: inline-block; }
    .hs-step-indicators.trunk-mode #hs-indicator-3 { display: none; }
    .hs-step-indicators.trunk-mode #hs-indicator-3-pppoe { display: inline-block; }
    .hs-step-indicators #hs-indicator-3-pppoe { display: none; }
    .hs-step-indicators #hs-indicator-4 { display: none; }
    .hs-step-indicators.trunk-mode #hs-indicator-4 { display: inline-block; }
    .hs-final-actions .hs-trunk-only { display: none; }
    .hs-final-actions.trunk-mode .hs-trunk-only { display: inline-block; }
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
        <input type="hidden" name="send_full" id="hs-send-full-field" value="">
        <input type="hidden" name="send_pppoe_only" id="hs-send-pppoe-field" value="">
        <input type="hidden" name="sync_hotspot_plans" id="hs-sync-plans-field" value="">
        <input type="hidden" name="pppoe_setup_router" id="pppoe_setup_router" value="{$hs_router|escape}">
        <input type="hidden" name="pppoe_setup_bridge_name" id="pppoe_setup_bridge_name" value="{$hs_lan_bridge|escape}">
        <input type="hidden" name="hs_wizard_step" id="hs_wizard_step" value="{$hs_wizard_step|escape}">
        <input type="hidden" name="hotspot_login_router_persist" id="hotspot_login_router_persist" value="{$hs_router|escape}">
        <div class="hs-wizard-wrap">
            <div class="hs-wizard-main">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-wifi"></i> {Lang::T('Hotspot')} — Assistant multi-étapes</h3>
                    </div>
                    <div class="box-body">
                        <div class="hs-step-indicators" id="hs-step-indicators">
                            <span id="hs-indicator-1" class="active">1. Personnalisation portail</span>
                            <span id="hs-indicator-2">2. Hotspot Setup</span>
                            <span id="hs-indicator-3">3. Finalisation</span>
                            <span id="hs-indicator-3-pppoe" class="hs-step-pppoe">3. PPPoE Setup</span>
                            <span id="hs-indicator-4">4. Finalisation</span>
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
                                            <option value="">{Lang::T('No routers found — add one in Network → Routers')}</option>
                                        {else}
                                            <option value="">{Lang::T('Select router')}</option>
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
                            <input type="hidden" name="lan_trunk_enabled" value="0">
                            <input type="hidden" name="pppoe_setup_on_hotspot" value="0">
                            <div class="form-group">
                                <label class="col-md-3 control-label">Ports Hotspot (bridge)</label>
                                <div class="col-md-9">
                                    <input name="hotspot_bridge_ports" id="hotspot_bridge_ports" class="form-control" value="{$hs_bridge_ports|escape}" placeholder="wlan1,ether3">
                                    <p class="help-block">Interfaces placées dans <code>bridge-hotspot</code> (séparées du PPPoE).</p>
                                </div>
                            </div>
<div id="hs-step1-sync-status" class="hs-sync-status"></div>
                        </div>

                        {* ——— Étape 2 : Hotspot Setup ——— *}
                        <div id="hs-step-2" style="display:none;">
                            <h4><i class="fa fa-wrench"></i> {Lang::T('Hotspot_Setup')}</h4>
                            <p class="text-muted">Les champs se synchronisent automatiquement depuis le routeur sélectionné à l'étape 1.</p>
                            <div id="hs-sync-status" class="hs-sync-status"></div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">{Lang::T('Description / Tagline')}</label>
                                <div class="col-md-8">
                                    <input type="text" name="hotspot_page_tagline" class="form-control" value="{$hs_tagline|escape}" placeholder="Ex. Haut débit • Illimité — laisser vide pour masquer">
                                    <p class="help-block">Sous-titre affiché sous le titre sur la page captive.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label">Contact</label>
                                <div class="col-md-8">
                                    <input type="text" name="hotspot_contact" class="form-control" value="{$hs_contact|escape}" placeholder="Ex. Assistance, Support technique">
                                    <p class="help-block">Libellé affiché à côté de l'icône téléphone sur le portail (remplace « WhatsApp Assistance »).</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label">Numéro de contact</label>
                                <div class="col-md-8">
                                    <input type="tel" name="hotspot_contact_phone" class="form-control" value="{$hs_contact_phone|escape}" placeholder="Ex. 677123456 ou +237677123456">
                                    <p class="help-block">Numéro cliquable pour lancer un appel (<code>tel:</code>). Laisser vide pour masquer le bouton contact.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Nom du Hotspot</label>
                                <div class="col-md-8">
                                    <input name="hotspot_name" id="hotspot_name" class="form-control" value="{$hs_name|escape}" placeholder="Ex. hotspot1">
                                </div>
                            </div>
                            <div class="form-group" id="hs-classic-interface-group">
                                <label class="col-md-4 control-label">HotSpot Interface</label>
                                <div class="col-md-8">
                                    <select name="hotspot_interface" id="hotspot_interface" class="form-control">
                                        {if $hs_interface neq ''}
                                            <option value="{$hs_interface|escape}" selected>{$hs_interface|escape}</option>
                                        {else}
                                            <option value="">— Sélectionnez un routeur à l'étape 1 —</option>
                                        {/if}
                                    </select>
                                    <p class="help-block hs-classic-if-help">Interface directe (sans trunk VLAN).</p>
                                    <p class="help-block hs-trunk-if-help" style="display:none;">En mode trunk : remplie automatiquement avec l'interface VLAN Hotspot.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label">Local address of Network</label>
                                <div class="col-md-8">
                                    <input name="hotspot_local_address" id="hotspot_local_address" class="form-control" value="{$hs_local|escape}" placeholder="10.10.0.1/24">
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
                                    <p class="help-block">Profils hotspot (<code>/ip hotspot profile</code>) : doit correspondre au profil du serveur (ex. <code>Dyrsia-hotspot</code>). À la sync, DYRSIA applique les réglages au profil réellement utilisé par le serveur hotspot.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Login</label>
                                <div class="col-md-8">
                                    <p class="help-block" style="margin-top:0;margin-bottom:8px;">Authentification portail captif : <strong>HTTP PAP</strong> + <strong>MAC COOKIE</strong> (activés par défaut). CHAP désactivé.</p>
                                    <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                        <input type="checkbox" name="hotspot_login_methods[]" value="http-pap" class="hs-login-method"{if $hs_login|strstr:',http-pap,' || (!$hs_login|strstr:',http-pap,' && !$hs_login|strstr:',mac-cookie,' && !$hs_login|strstr:',cookie,')} checked="checked"{/if}>
                                        HTTP PAP
                                    </label>
                                    <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                        <input type="checkbox" name="hotspot_login_methods[]" value="mac-cookie" class="hs-login-method"{if $hs_login|strstr:',mac-cookie,' || $hs_login|strstr:',cookie,' || (!$hs_login|strstr:',http-pap,' && !$hs_login|strstr:',mac-cookie,' && !$hs_login|strstr:',cookie,')} checked="checked"{/if}>
                                        MAC COOKIE
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label">HTTP Cookie Lifetime</label>
                                <div class="col-md-8">
                                    <input name="hotspot_cookie_lifetime" id="hotspot_cookie_lifetime" class="form-control" value="{$hs_cookie|escape}" placeholder="4h ou 04:00:00">
                                    <p class="help-block">Durée du cookie MAC (ex. <code>4h</code>, <code>04:00:00</code>, <code>1d</code>). Synchronisé sur le profil MikroTik actif.</p>
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

                            <div class="form-group">
                                <label class="col-md-4 control-label">Auth RADIUS</label>
                                <div class="col-md-8">
                                    <label class="checkbox-inline" style="display:block;margin:0 0 6px;padding-left:0;">
                                        <input type="checkbox" name="hotspot_use_radius" id="hotspot_use_radius" value="1"{if $hs_use_radius != '0'} checked="checked"{/if}>
                                        Activer RADIUS (DYRSIA + MikroTik)
                                    </label>
                                    <p class="help-block">
                                        Coché par défaut : active FreeRADIUS dans DYRSIA, applique <code>use-radius=yes</code>,
                                        crée le client <code>/radius</code> sur le MikroTik et synchronise le secret NAS.
                                        Requis pour respecter l'expiration des forfaits.
                                    </p>
                                </div>
                            </div>
                            <div class="form-group" id="hs-radius-secret-group">
                                <label class="col-md-4 control-label">Secret RADIUS</label>
                                <div class="col-md-8">
                                    <input type="password" name="hotspot_radius_secret" id="hotspot_radius_secret" class="form-control"
                                        value="{$_c['hotspot_radius_secret']|default:''|escape}" placeholder="Auto (NAS) si vide"
                                        onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'" autocomplete="new-password">
                                    <p class="help-block">
                                        Secret partagé MikroTik ↔ DYRSIA. Laissé vide : réutilise ou génère l'entrée NAS du routeur.
                                        Serveur RADIUS = IP de <strong>Hotspot API URL</strong> (ex. <code>10.0.0.2</code>, pas l'IP du routeur).
                                    </p>
                                </div>
                            </div>

                            <input type="hidden" name="hotspot_pool_mode" value="existing">
                            <input type="hidden" name="hotspot_keepalive_timeout" value="{$hs_keepalive|escape}">
                        </div>


                        {* ——— Étape finale : Résumé + actions ——— *}
                        <div id="hs-step-4" style="display:none;">
                            <h4><i class="fa fa-check-circle"></i> Résumé de la configuration</h4>
                            <p class="text-muted">Vérifiez vos choix avant d'enregistrer ou d'envoyer vers le routeur.</p>
                            <div id="hs-summary" class="hs-summary"></div>
                        </div>

                        <div class="hs-nav-bar">
                            <button type="button" id="hs-btn-preview" class="btn btn-default" disabled>
                                <i class="fa fa-arrow-left"></i> Précédent
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
                                    <button type="submit" value="1" id="hs-send-mikrotik-btn" class="btn btn-warning" title="Envoi rapide de login.html (pool et forfaits : boutons Save / Sync forfaits)">
                                        <i class="fa fa-cloud-upload"></i> Send login.html
                                    </button>
                                    <button type="submit" value="1" id="hs-send-full-btn" class="btn btn-default" title="Pool, profils, forfaits, walled-garden — plus long">
                                        <i class="fa fa-cogs"></i> Send complet
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
                        <iframe id="hs-real-preview" src="{$hs_preview_url|escape:'html'}&title={$hs_title|escape:'url'}&routername={$hs_router|escape:'url'}&ts={$hs_login_preview_ts|default:0}" style="width:100%;height:100%;border:0;border-radius:36px;background:#0a0c15;" title="Aperçu login hotspot"></iframe>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    <script src="{$app_url}/ui/ui/scripts/hotspot-wizard.js?2026.07.22"></script>
    <script>
    window.HS_FETCH_URL = '{$hs_fetch_url|escape:'javascript'}';
    window.HS_PPPOE_FETCH_URL = '{$hs_pppoe_fetch_url|escape:'javascript'}';
    window.HS_PERSIST_ROUTER_URL = '{$hs_persist_router_url|escape:'javascript'}';
    window.HS_ALLOWED_ROUTERS = {$hs_allowed_routers_json|default:'[]' nofilter};
    window.HS_INITIAL_ROUTER = '{$hs_router|escape:'javascript'}';
    window.HS_INITIAL_STEP = '{$hs_wizard_step|escape:'javascript'}';
    document.addEventListener('DOMContentLoaded', function () {
        var titleInput = document.querySelector('input[name="hotspot_page_title"]');
        var preview = document.getElementById('hs-real-preview');
        if (!titleInput || !preview) {
            return;
        }
        var basePreviewUrl = '{$hs_preview_url|escape:'javascript'}';
        var previewRouter = '{$hs_router|escape:'javascript'}';
        var previewTs = '{$hs_login_preview_ts|default:0|escape:'javascript'}';
        function updatePreviewUrl() {
            var activeRouter = (window.hsGetPersistedRouter && window.hsGetPersistedRouter()) || previewRouter || '';
            var qs = '?title=' + encodeURIComponent(titleInput.value || '');
            var taglineInput = document.querySelector('input[name="hotspot_page_tagline"]');
            if (taglineInput && taglineInput.value) {
                qs += '&tagline=' + encodeURIComponent(taglineInput.value);
            }
            var contactInput = document.querySelector('input[name="hotspot_contact"]');
            if (contactInput && contactInput.value) {
                qs += '&contact=' + encodeURIComponent(contactInput.value);
            }
            var contactPhoneInput = document.querySelector('input[name="hotspot_contact_phone"]');
            if (contactPhoneInput && contactPhoneInput.value) {
                qs += '&contact_phone=' + encodeURIComponent(contactPhoneInput.value);
            }
            if (activeRouter) {
                qs += '&routername=' + encodeURIComponent(activeRouter);
            }
            if (previewTs) {
                qs += '&ts=' + encodeURIComponent(previewTs);
            }
            preview.src = basePreviewUrl + qs;
        }
        window.hsUpdatePreviewRouter = function (routerName) {
            previewRouter = routerName || '';
            updatePreviewUrl();
        };
        titleInput.addEventListener('input', updatePreviewUrl);
        ['hotspot_page_tagline', 'hotspot_contact', 'hotspot_contact_phone'].forEach(function (name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (el) {
                el.addEventListener('input', updatePreviewUrl);
                el.addEventListener('change', updatePreviewUrl);
            }
        });
        var routerSelect = document.querySelector('select[name="hotspot_login_router"]');
        if (routerSelect) {
            routerSelect.addEventListener('change', function () {
                previewRouter = routerSelect.value || '';
                updatePreviewUrl();
            });
        }
        var wizardForm = document.getElementById('hs-wizard-form');
        if (wizardForm && window.hsRestoreRouterSelection) {
            window.hsRestoreRouterSelection();
        }

        var sendBtn = document.getElementById('hs-send-mikrotik-btn');
        var sendFullBtn = document.getElementById('hs-send-full-btn');
        var sendPppoeBtn = document.getElementById('hs-send-pppoe-btn');
        var sendFullField = document.getElementById('hs-send-full-field');
        var sendPppoeField = document.getElementById('hs-send-pppoe-field');
        var sendField = document.getElementById('hs-send-mikrotik-field');
        var syncPlansBtn = document.getElementById('hs-sync-plans-btn');
        var syncPlansField = document.getElementById('hs-sync-plans-field');

        function hsRequireRouterSelected(event) {
            var routerName = (window.hsGetPersistedRouter && window.hsGetPersistedRouter()) || '';
            var routerSelect = document.getElementById('hotspot_login_router');
            if (routerSelect && routerName && !routerSelect.value) {
                routerSelect.value = routerName;
            }
            if (!routerName) {
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

        if (wizardForm) {
            wizardForm.addEventListener('submit', function () {
                var sel = document.getElementById('hotspot_login_router');
                var persist = document.getElementById('hotspot_login_router_persist');
                if (sel && persist && !sel.value && persist.value) {
                    sel.value = persist.value;
                }
            });
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
                if (sendFullField) {
                    sendFullField.value = '';
                }
                if (sendPppoeField) {
                    sendPppoeField.value = '';
                }
                if (syncPlansField) {
                    syncPlansField.value = '';
                }
            });
        }
        if (wizardForm && sendFullBtn) {
            sendFullBtn.addEventListener('click', function () {
                if (sendField) {
                    sendField.value = '1';
                }
                if (sendFullField) {
                    sendFullField.value = '1';
                }
                if (sendPppoeField) {
                    sendPppoeField.value = '';
                }
                if (syncPlansField) {
                    syncPlansField.value = '';
                }
            });
        }
        if (wizardForm && sendPppoeBtn) {
            sendPppoeBtn.addEventListener('click', function () {
                if (sendField) {
                    sendField.value = '1';
                }
                if (sendFullField) {
                    sendFullField.value = '';
                }
                if (sendPppoeField) {
                    sendPppoeField.value = '1';
                }
                if (syncPlansField) {
                    syncPlansField.value = '';
                }
            });
        }
        if (wizardForm && (sendBtn || sendFullBtn || sendPppoeBtn)) {
            wizardForm.addEventListener('submit', function (event) {
                var submitter = event.submitter;
                var isSendMikrotik = (sendField && sendField.value === '1') || (submitter && (submitter.id === 'hs-send-mikrotik-btn' || submitter.id === 'hs-send-full-btn' || submitter.id === 'hs-send-pppoe-btn'));
                var isSendFull = (sendFullField && sendFullField.value === '1') || (submitter && submitter.id === 'hs-send-full-btn');
                var isSendPppoe = (sendPppoeField && sendPppoeField.value === '1') || (submitter && submitter.id === 'hs-send-pppoe-btn');
                var isSyncPlans = (syncPlansField && syncPlansField.value === '1') || (submitter && submitter.id === 'hs-sync-plans-btn');
                if (!isSendMikrotik) {
                    if (sendField) {
                        sendField.value = '';
                    }
                    if (sendFullField) {
                        sendFullField.value = '';
                    }
                    if (sendPppoeField) {
                        sendPppoeField.value = '';
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
                if (!confirm(isSendPppoe
                    ? 'Le PPPoE se déploie via Paramètres → PPPoE Setup (bridge-pppoe). Continuer quand même ?'
                    : (isSendFull
                        ? 'Envoi complet Hotspot vers le routeur (bridge-hotspot uniquement, sans PPPoE) ?'
                        : 'Envoyer login.html vers le routeur ? (envoi rapide, quelques secondes)'))) {
                    event.preventDefault();
                    if (sendField) {
                        sendField.value = '';
                    }
                    if (sendFullField) {
                        sendFullField.value = '';
                    }
                    if (sendPppoeField) {
                        sendPppoeField.value = '';
                    }
                    return;
                }
                if (!hsRequireRouterSelected(event)) {
                    if (sendField) {
                        sendField.value = '';
                    }
                    if (sendFullField) {
                        sendFullField.value = '';
                    }
                    if (sendPppoeField) {
                        sendPppoeField.value = '';
                    }
                    return;
                }
                if (sendBtn) {
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi login.html…';
                }
                if (sendFullBtn) {
                    sendFullBtn.disabled = true;
                    sendFullBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi complet…';
                }
                if (sendPppoeBtn) {
                    sendPppoeBtn.disabled = true;
                    sendPppoeBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi PPPoE…';
                }
            });

            // A required field hidden in a previous wizard step blocks submission silently.
            // Jump back to its step and surface the native validation message.
            wizardForm.addEventListener('invalid', function (event) {
                var field = event.target;
                var stepEl = field.closest('[id^="hs-step-"]');
                if (stepEl && window.hsWizardGoToPanel) {
                    window.hsWizardGoToPanel(stepEl.id);
                } else if (stepEl && window.hsWizardGoToStep) {
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
