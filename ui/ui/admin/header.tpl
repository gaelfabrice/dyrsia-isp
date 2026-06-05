<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title}{if $isp_brand_name} - {$isp_brand_name|escape}{else} - {$_c['CompanyName']}{/if}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />

    <script>
        var appUrl = '{$app_url}';
    </script>

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2-bootstrap.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/plugins/pace.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/summernote/summernote.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/wifizones.css?2025.5.22" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/7.css" />

    <script src="{$app_url}/ui/ui/scripts/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
    <style>
span[api-get-text] {
    font-size: 30px !important;
    font-weight: 800 !important;
}

/* ===== BASE SIDEBAR ===== */
.main-sidebar,
.sidebar,
.sidebar-menu {
    background: #0f172a !important;
}

/* remove any white/background conflict */
.sidebar,
.sidebar-menu,
.sidebar-menu li,
.sidebar-menu li a {
    background: transparent !important;
}

/* ===== MENU ITEM ===== */
.sidebar-menu > li > a {
    display: flex;
    align-items: center;
    gap: 10px;
}
/* =========================
   TREEVIEW CARVED STRIP STYLE
========================= */

.treeview-menu > li > a {
    position: relative;
    display: block;
    color: #94a3b8 !important;
    padding: 10px 15px;
    border-radius: 8px;
    margin: 2px 5px;
    transition: all 0.25s ease;
    overflow: hidden;
}

/* =========================
   CARVED STRIP (MAIN EFFECT)
========================= */
.treeview-menu > li > a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 12%;
    width: 4px;
    height: 0%;
    background: #ff2e93;
    border-radius: 10px;
    transition: all 0.25s ease;

    /* carved depth effect */
    box-shadow: inset 0 0 6px rgba(0,0,0,0.6);
}

/* =========================
   HOVER (CARVED ACTIVE FEEL)
========================= */
.treeview-menu > li > a:hover {
    background: #1e293b !important;
    color: #ffffff !important;

    /* carved look */
    box-shadow: inset 4px 0 0 #ff2e93;
    transform: translateX(3px);
}

/* =========================
   ACTIVE (ALWAYS CARVED)
========================= */
.treeview-menu > li.active > a {
    background: #1e293b !important;
    color: #ffffff !important;

    /* deep carved inset */
    box-shadow: inset 4px 0 0 #ff2e93, inset 0 0 10px rgba(0,0,0,0.3);
}

/* active strip */
.treeview-menu > li.active > a::before {
    height: 70%;
}

/* ===== HOVER (SAME AS ACTIVE) ===== */
.sidebar-menu > li > a:hover {
    background: #1e293b !important;
    color: #ffffff !important;
    transform: translateX(4px);

    /* same active strip */
    box-shadow: inset 4px 0 0 #ff2e93;
}

/* ===== ACTIVE ===== */
.sidebar-menu > li.active > a {
    background: #1e293b !important;
    color: #ffffff !important;
    box-shadow: inset 4px 0 0 #ff2e93;
}

/* =====================================
   SUBMENU (SAME DARK AS MAIN)
===================================== */
.treeview-menu {
    background: #0f172a !important;
    padding-left: 5px;
}

.treeview-menu > li > a {
    color: #94a3b8 !important;
    padding: 10px 15px;
    border-radius: 6px;
}

/* submenu hover */
.treeview-menu > li > a:hover {
    background: #1e293b !important;
    color: #ffffff !important;
}

/* submenu active */
.treeview-menu > li.active > a {
    background: #1e293b !important;
    color: #ffffff !important;
}

/* =====================================
   AUTO ICON COLOR SYSTEM (UNLIMITED)
===================================== */

/* loop 8 colors */
.sidebar-menu > li:nth-child(8n+1) i { color: #22c55e !important; }
.sidebar-menu > li:nth-child(8n+2) i { color: #3b82f6 !important; }
.sidebar-menu > li:nth-child(8n+3) i { color: #f59e0b !important; }
.sidebar-menu > li:nth-child(8n+4) i { color: #ef4444 !important; }
.sidebar-menu > li:nth-child(8n+5) i { color: #a855f7 !important; }
.sidebar-menu > li:nth-child(8n+6) i { color: #06b6d4 !important; }
.sidebar-menu > li:nth-child(8n+7) i { color: #f97316 !important; }
.sidebar-menu > li:nth-child(8n+8) i { color: #10b981 !important; }

/* =====================================
   ADMINLTE OVERRIDE FIX
===================================== */
body.theme-dark .main-sidebar,
body.theme-dark .sidebar,
body.theme-dark .sidebar-menu {
    background: #0f172a !important;
}

/* prevent white flash */
body.theme-dark .sidebar-menu li,
body.theme-dark .sidebar-menu li a {
    background: transparent !important;
}

/* =====================================
   SMOOTH UI
===================================== */
.sidebar-menu > li > a,
.treeview-menu > li > a {
    transition: all 0.25s ease;
}
/* =========================
   BRIGHT BOX SIDEBAR ITEMS
========================= */

.sidebar-menu > li {
    margin: 7px 8px;
}

/* MAIN BOX */
.sidebar-menu > li > a {
    position: relative;
    display: block;

    /* brighter base */
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);

    padding: 12px 15px;
    border-radius: 10px;

    color: #e2e8f0 !important;

    transition: all 0.25s ease;
    overflow: hidden;

    /* soft depth */
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}

/* =========================
   LEFT STRIP
========================= */
.sidebar-menu > li > a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 15%;
    width: 4px;
    height: 0%;
    background: linear-gradient(180deg, #ff2e93, #3b82f6);
    border-radius: 10px;
    transition: all 0.25s ease;
}

/* =========================
   HOVER (BRIGHT + GLOW)
========================= */
.sidebar-menu > li > a:hover {
    background: rgba(255,255,255,0.10) !important;
    color: #ffffff !important;

    transform: translateX(4px);

    /* glow effect */
    box-shadow: 0 8px 20px rgba(0,0,0,0.35),
                0 0 12px rgba(59,130,246,0.15),
                inset 4px 0 0 #ff2e93;
}

/* hover strip */
.sidebar-menu > li > a:hover::before {
    height: 70%;
}

/* =========================
   ACTIVE (STRONG + BRIGHT)
========================= */
.sidebar-menu > li.active > a {
    background: rgba(255,255,255,0.12) !important;
    color: #ffffff !important;

    box-shadow: 0 10px 25px rgba(0,0,0,0.40),
                0 0 15px rgba(255,46,147,0.15),
                inset 4px 0 0 #ff2e93;
}

/* active strip */
.sidebar-menu > li.active > a::before {
    height: 70%;
}

/* =========================
   SUBMENU BRIGHT FIX
========================= */
.treeview-menu > li > a {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    color: #cbd5e1 !important;
}

.treeview-menu > li > a:hover {
    background: rgba(255,255,255,0.10) !important;
    color: #ffffff !important;
}
/* =========================
   SIDEBAR ICON BIG SIZE FIX
========================= */
.sidebar-menu > li > a i {
    font-size: 20px !important;   /* আগে 14–16 থাকলে এখন বড় */
    width: 26px;
    text-align: center;
    display: inline-block;
}
.sidebar-menu > li > a:hover {
    background: linear-gradient(135deg, rgba(59,130,246,0.35), rgba(255,46,147,0.35)) !important;
    color: #ffffff !important;

    transform: translateX(4px);

    box-shadow: 0 8px 18px rgba(0,0,0,0.25),
                0 0 10px rgba(59,130,246,0.10),
                inset 4px 0 0 rgba(255,46,147,0.8);
}
.sidebar-menu > li.active > a {
    background: linear-gradient(135deg, rgba(59,130,246,0.40), rgba(255,46,147,0.40)) !important;
    color: #ffffff !important;

    box-shadow: 0 10px 22px rgba(0,0,0,0.28),
                0 0 12px rgba(255,46,147,0.10),
                inset 4px 0 0 rgba(255,46,147,0.85);
}
.treeview-menu {
    background: #0f172a !important;
    padding-left: 5px;
}

/* =========================
   TREE MENU BASE
========================= */
.treeview-menu > li > a {
    position: relative;
    display: block;

    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);

    color: #cbd5e1 !important;

    padding: 10px 15px;
    border-radius: 8px;
    margin: 2px 5px;

    transition: all 0.25s ease;
    overflow: hidden;
}

/* =========================
   HOVER (BLUE + PINK GLOSSY)
========================= */
.treeview-menu > li > a:hover {
    background: linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(255,46,147,0.25)
    ) !important;

    color: #ffffff !important;

    transform: translateX(3px);

    box-shadow: inset 3px 0 0 rgba(255,46,147,0.9),
                0 6px 14px rgba(0,0,0,0.25);
}

/* =========================
   ACTIVE (BLUE + PINK GLOW)
========================= */
.treeview-menu > li.active > a {
    background: linear-gradient(
        135deg,
        rgba(59,130,246,0.35),
        rgba(255,46,147,0.35)
    ) !important;

    color: #ffffff !important;

    box-shadow: inset 4px 0 0 rgba(255,46,147,0.9),
                0 8px 18px rgba(0,0,0,0.28);
}
.main-header .navbar,
.main-header {
    background: #0f172a !important;
    border-bottom: 1px solid rgba(255,255,255,0.06) !important;
}

/* logo area (company name bar) */
.main-header .logo {
    background: #0f172a !important;
    color: #ffffff !important;
    border-right: 1px solid rgba(255,255,255,0.06);
}

/* 3-dot / menu buttons area */
.navbar-custom-menu,
.navbar-nav > li > a {
    background: transparent !important;
    color: #e2e8f0 !important;
}

/* hover same as sidebar */
.navbar-nav > li > a:hover {
    background: linear-gradient(135deg, rgba(59,130,246,0.25), rgba(255,46,147,0.25)) !important;
    color: #ffffff !important;
}

/* sidebar toggle button (3 line / hamburger) */
.sidebar-toggle {
    color: #ffffff !important;
}

/* icons in top bar */
.navbar i {
    color: #e2e8f0 !important;
}
.main-footer {
    background: #0f172a !important;
    color: #e2e8f0 !important;
    border-top: 1px solid rgba(255,255,255,0.06) !important;
    padding: 10px 15px;
}

/* footer links */
.main-footer a {
    color: #60a5fa !important;
    transition: 0.25s ease;
}

/* hover effect */
.main-footer a:hover {
    color: #ff2e93 !important;
    text-decoration: none;
}

/* version text */
#version {
    color: #94a3b8 !important;
}
.toggle-container,
.toggle-icon {
    color: #ffffff !important;
    background: transparent !important;
}

/* ===== Header navbar layout (search + menu align) ===== */
.main-header .navbar {
    display: flex;
    align-items: stretch;
    min-height: 50px;
    margin-left: 0;
}

.main-header .navbar .sidebar-toggle {
    display: flex;
    align-items: center;
    padding: 15px;
    flex-shrink: 0;
}

.navbar-custom-menu {
    float: none !important;
    margin-left: auto;
    display: flex;
    align-items: center;
    height: 50px;
}

.navbar-custom-menu > .navbar-nav {
    display: flex !important;
    flex-direction: row;
    align-items: center;
    float: none !important;
    margin: 0;
    height: 100%;
}

.navbar-custom-menu > .navbar-nav > li {
    display: flex;
    align-items: center;
    float: none !important;
    position: static;
}

.navbar-custom-menu > .navbar-nav > li > a {
    display: flex !important;
    align-items: center;
    gap: 6px;
    height: 50px;
    padding: 0 12px !important;
    line-height: 1.2;
}

.navbar-custom-menu .user-image {
    width: 28px;
    height: 28px;
    margin-right: 4px;
}

/* Legacy .wrap broke search button position in header */
.navbar .wrap,
.navbar-custom-menu .wrap {
    width: auto !important;
    position: static !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;
    z-index: auto !important;
    text-align: inherit !important;
}

.navbar-custom-menu button.search,
.navbar-custom-menu #openSearch.search {
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
    border: none !important;
    background: transparent !important;
    color: #e2e8f0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

.navbar-custom-menu #openSearch.search:hover {
    color: #ffffff !important;
    background: transparent !important;
}

.search-overlay {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 9999 !important;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.55) !important;
}

.search-overlay.is-open {
    display: flex !important;
}

.search-overlay .search-container {
    margin: 0 16px;
}

.isp-brand-badge {
    display: flex;
    align-items: center;
    padding: 8px 12px !important;
}
.isp-brand-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    color: #ecfdf5;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.35), rgba(6, 182, 212, 0.25));
    border: 1px solid rgba(34, 197, 94, 0.45);
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.isp-sidebar-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 12px 14px 8px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 800;
    color: #ecfdf5;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.28);
    line-height: 1.3;
    word-break: break-word;
}
.isp-sidebar-brand .fa {
    color: #4ade80;
    flex-shrink: 0;
}
.impersonation-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 16px;
    background: linear-gradient(90deg, #b45309, #d97706);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1200;
}
.impersonation-bar a.btn { color: #78350f !important; font-weight: 800; }
    </style>
    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition sidebar-mini theme-light {if $_kolaps}sidebar-collapse{/if}">
    <div class="wrapper">
        {if $impersonation_active}
        <div class="impersonation-bar">
            <span><i class="fa fa-user-secret"></i> {Lang::T('Viewing as')}: <strong>{$impersonation_label|escape}</strong></span>
            <a href="{$impersonation_exit_url}" class="btn btn-xs btn-warning">{Lang::T('Exit impersonation')}</a>
        </div>
        {/if}
        <header class="main-header">
            <a href="{Text::url('dashboard')}" class="logo">
                <span class="logo-mini"><b>DYR</b>SIA</span>
                <span class="logo-lg">{if $isp_brand_name}{$isp_brand_name|escape}{else}{$_c['CompanyName']}{/if}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" onclick="return setKolaps()">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        {if $isp_brand_name}
                        <li class="hidden-xs isp-brand-badge">
                            <span class="isp-brand-label" title="{Lang::T('Your ISP')}"><i class="fa fa-building-o"></i> {$isp_brand_name|escape}</span>
                        </li>
                        {/if}
                        {if $_admin['user_type'] eq 'SuperAdmin' && $withdrawal_pending_count|default:0 > 0}
                        <li class="dropdown" id="wdNotifyDropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Alertes retraits">
                                <i class="fa fa-bell"></i>
                                <span class="label label-danger" style="position:absolute;top:8px;right:4px;font-size:10px;padding:2px 5px">{$withdrawal_pending_count}</span>
                            </a>
                            <ul class="dropdown-menu" style="min-width:320px;max-height:360px;overflow-y:auto">
                                <li class="dropdown-header">Demandes de retrait en attente</li>
                                {foreach $withdrawal_notifications|default:[] as $wn}
                                <li>
                                    <a href="{Text::url('finance/reversement')}&notification={$wn->id}">
                                        <i class="fa fa-circle text-danger" style="font-size:8px"></i>
                                        {$wn->message|escape}
                                    </a>
                                </li>
                                {/foreach}
                                <li class="divider"></li>
                                <li><a href="{Text::url('finance/reversement')}"><strong>Voir tout — Reversement</strong></a></li>
                            </ul>
                        </li>
                        {elseif $_admin['user_type'] eq 'SuperAdmin'}
                        <li>
                            <a href="{Text::url('finance/reversement')}" title="Reversement">
                                <i class="fa fa-bell-o"></i>
                            </a>
                        </li>
                        {/if}
                        <li class="header-search-toggle">
                            <a href="#" id="openSearch" role="button" aria-label="{Lang::T('Search Users')}">
                                <i class="fa fa-search"></i>
                            </a>
                        </li>
                        <li>
                            <a class="toggle-container" href="#" role="button" aria-label="Theme" onclick="toggleTheme(); return false;">
                                <i class="fa fa-moon-o toggle-icon" id="toggleIcon"></i>
                            </a>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="{Lang::T('Language')}">
                                <i class="fa fa-flag-o"></i>
                                <span class="hidden-xs">{if $current_language eq 'french'}FR{else}EN{/if}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="dropdown-header">{Lang::T('Language')}</li>
                                <li{if $current_language eq 'english'} class="active"{/if}>
                                    <a href="{Text::url('language/set&lang=english')}">
                                        {if $current_language eq 'english'}<i class="fa fa-check text-success"></i> {/if}
                                        English
                                    </a>
                                </li>
                                <li{if $current_language eq 'french'} class="active"{/if}>
                                    <a href="{Text::url('language/set&lang=french')}">
                                        {if $current_language eq 'french'}<i class="fa fa-check text-success"></i> {/if}
                                        Français
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                    onerror="this.src='{$app_url}/{$UPLOAD_PATH}/admin.default.png'" class="user-image"
                                    alt="Avatar">
                                <span class="hidden-xs">{$_admin['fullname']}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                        onerror="this.src='{$app_url}/{$UPLOAD_PATH}/admin.default.png'" class="img-circle"
                                        alt="Avatar">
                                    <p>
                                        {$_admin['fullname']}
                                        <small>{Lang::T($_admin['user_type'])}{if $isp_brand_name} · {$isp_brand_name|escape}{/if}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{Text::url('settings/change-password')}"><i
                                                    class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{Text::url('settings/users-view/', $_admin['id'])}">
                                                <i class="ion ion-person"></i> {Lang::T('My Account')}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{Text::url('logout')}" class="btn btn-default btn-flat"><i
                                                class="ion ion-power"></i> {Lang::T('Logout')}</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <div id="searchOverlay" class="search-overlay" aria-hidden="true">
            <div class="search-container">
                <input type="text" id="searchTerm" class="searchTerm"
                    placeholder="{Lang::T('Search Users')}" autocomplete="off">
                <div id="searchResults" class="search-results"></div>
                <button type="button" id="closeSearch" class="cancelButton">{Lang::T('Cancel')}</button>
            </div>
        </div>
        <aside class="main-sidebar">
            <section class="sidebar">
                {if $isp_brand_name}
                <div class="isp-sidebar-brand">
                    <i class="fa fa-building-o"></i>
                    <span>{$isp_brand_name|escape}</span>
                </div>
                {/if}
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'dashboard' }class="active" {/if}>
                        <a href="{Text::url('dashboard')}">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    <li class="{if $_system_menu eq 'reports'}active{/if} treeview">
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
                            <a href="#">
                                <i class="ion ion-clipboard"></i> <span>{Lang::T('Reports')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                        {/if}
                        <ul class="treeview-menu">
                            <li {if $_routes[1] eq 'reports' }class="active" {/if}><a
                                    href="{Text::url('reports')}">{Lang::T('Daily Reports')}</a></li>
                            <li {if $_routes[1] eq 'activation' }class="active" {/if}><a
                                    href="{Text::url('reports/activation')}">{Lang::T('Activation History')}</a></li>
                            <li {if $_routes[1] eq 'data-usage' }class="active" {/if}><a
                                    href="{Text::url('reports/data-usage')}">{Lang::T('Data Usage')}</a></li>
                            {$_MENU_REPORTS}
                        </ul>
                    </li>
                    {$_MENU_AFTER_REPORTS}
                    <li class="{if $_system_menu eq 'customers' || $_routes[0] eq 'customers' || $_system_menu eq 'quickadd'}active{/if} treeview">
                        <a href="#">
                            <i class="fa fa-users"></i> <span>{Lang::T('Customer Management')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_system_menu eq 'customers' }class="active" {/if}>
                                <a href="{Text::url('customers')}">
                                    <i class="fa fa-user"></i>
                                    <span>{Lang::T('Customer')}</span>
                                </a>
                            </li>
                            <li {if $_system_menu eq 'quickadd' }class="active" {/if}>
                                <a href="{Text::url('plugin/quickadd')}">
                                    <i class="ion ion-person-add"></i>
                                    <span>{Lang::T('Add Customer')}</span>
                                </a>
                            </li>
                            <li {if $_routes[1] eq 'olt_management' }class="active" {/if}>
                                <a href="{Text::url('plugin/olt_management')}">
                                    <i class="ion ion-network"></i>
                                    <span>{Lang::T('OLT Management')}</span>
                                </a>
                            </li>
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin','Agent'])}
                                <li {if $_routes[1] eq 'users' }class="active" {/if}>
                                    <a href="{Text::url('settings/users')}">
                                        <i class="ion ion-person-stalker"></i>
                                        <span>{Lang::T('Administrator Users')}</span>
                                    </a>
                                </li>
                            {/if}
                            {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active}
                                <li {if $_routes[0] eq 'impersonate' }class="active" {/if}>
                                    <a href="{Text::url('impersonate')}">
                                        <i class="fa fa-user-secret"></i>
                                        <span>{Lang::T('Login as user')}</span>
                                    </a>
                                </li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[0] eq 'registration_requests' }class="active" {/if}>
                                    <a href="{Text::url('registration_requests')}">
                                        <i class="ion ion-person-add"></i>
                                        <span>{Lang::T('Registration Requests')}</span>
                                    </a>
                                </li>
                            {/if}
                            {$_MENU_CUSTOMERS}
                        </ul>
                    </li>
                    {if !in_array($_admin['user_type'],['Report'])}
                        <li class="{if $_system_menu eq 'monitoring'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-line-chart"></i>
                                <span>{Lang::T('Monitoring')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_system_menu eq 'monitoring' && ($_routes[1] eq '' || !$_routes[1]) }class="active" {/if}>
                                    <a href="{Text::url('monitoring')}">{Lang::T('Overview')}</a>
                                </li>
                                <li {if $_routes[1] eq 'expiry' }class="active" {/if}>
                                    <a href="{Text::url('monitoring/expiry')}">{Lang::T('Customer Expiry Status')}</a>
                                </li>
                            </ul>
                        </li>
                        <li class="{if $_system_menu eq 'finance' || $_routes[1] eq 'withdrawals' || $_routes[1] eq 'reversement'}active menu-open{/if} treeview">
                            <a href="{Text::url('finance')}">
                                <i class="fa fa-credit-card"></i>
                                <span>{Lang::T('Finance')}</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu" style="{if $_system_menu eq 'finance' || $_routes[1] eq 'withdrawals' || $_routes[1] eq 'reversement'}display:block{/if}">
                                <li {if $_system_menu eq 'finance' && ($_routes[1] eq '' || !$_routes[1])}class="active"{/if}>
                                    <a href="{Text::url('finance')}">{Lang::T('Overview')}</a>
                                </li>
                                {if $_admin['user_type'] eq 'Admin'}
                                <li {if $_routes[1] eq 'withdrawals'}class="active"{/if}>
                                    <a href="{Text::url('finance/withdrawals')}"><i class="fa fa-money"></i> Demande de retrait</a>
                                </li>
                                {/if}
                                {if $_admin['user_type'] eq 'SuperAdmin'}
                                <li {if $_routes[1] eq 'reversement'}class="active"{/if}>
                                    <a href="{Text::url('finance/reversement')}"><i class="fa fa-exchange"></i> Reversement{if $withdrawal_pending_count|default:0 > 0} <span class="label label-danger" style="font-size:10px">{$withdrawal_pending_count}</span>{/if}</a>
                                </li>
                                {/if}
                            </ul>
                        </li>
                        <li class="{if $_routes[0] eq 'plan' || $_routes[0] eq 'coupons'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-ticket"></i> <span>{Lang::T('Services')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{Text::url('plan/list')}">{Lang::T('Active Customers')}</a></li>
                                {if $_c['disable_voucher'] != 'yes'}
                                    <li {if $_routes[1] eq 'refill' }class="active" {/if}><a
                                            href="{Text::url('plan/refill')}">{Lang::T('Refill Customer')}</a></li>
                                {/if}
                                {if $_c['disable_voucher'] != 'yes'}
                                    <li {if $_routes[1] eq 'voucher' }class="active" {/if}><a
                                            href="{Text::url('plan/voucher')}">{Lang::T('Vouchers')}</a></li>
                                {/if}
                                {if $_c['enable_coupons'] == 'yes'}
                                    <li {if $_routes[0] eq 'coupons' }class="active" {/if}><a
                                            href="{Text::url('coupons')}">{Lang::T('Coupons')}</a></li>
                                {/if}
                                <li {if $_routes[1] eq 'recharge' }class="active" {/if}><a
                                        href="{Text::url('plan/recharge')}">{Lang::T('Recharge Customer')}</a></li>
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[1] eq 'deposit' }class="active" {/if}><a
                                            href="{Text::url('plan/deposit')}">{Lang::T('Refill Balance')}</a></li>
                                {/if}
                                {$_MENU_SERVICES}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_SERVICES}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'services'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-cube"></i> <span>{Lang::T('Internet Plan')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'pppoe' }class="active" {/if}><a
                                        href="{Text::url('services/pppoe')}">PPPOE</a></li>
                                <li {if $_routes[1] eq 'hotspot' }class="active" {/if}><a
                                        href="{Text::url('services/hotspot')}">Hotspot Plan</a></li>
                                <li {if $_routes[1] eq 'vpn' }class="active" {/if}><a href="{Text::url('services/vpn')}">VPN</a>
                                </li>
                                <li {if $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{Text::url('bandwidth/list')}">Bandwidth</a></li>
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[1] eq 'balance' }class="active" {/if}><a
                                            href="{Text::url('services/balance')}">{Lang::T('Customer Balance')}</a></li>
                                {/if}
                                {$_MENU_PLANS}
                                {$_MENU_AFTER_PLANS}
                            </ul>
                        </li>
                    {/if}
                    <li class="{if in_array($_routes[0], ['maps'])}active{/if} treeview">
                        <a href="#">
                            <i class="fa fa-map-marker"></i> <span>{Lang::T('Maps')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[1] eq 'customer' }class="active" {/if}><a
                                    href="{Text::url('maps/customer')}">{Lang::T('Customer')}</a></li>
                            <li {if $_routes[1] eq 'routers' }class="active" {/if}><a
                                    href="{Text::url('maps/routers')}">{Lang::T('Routers')}</a></li>
                            <li {if $_routes[1] eq 'odp' }class="active" {/if}><a
                                    href="{Text::url('maps/odp')}">{Lang::T('ODPs')}</a></li>
                            {$_MENU_MAPS}
                        </ul>
                    </li>
                    {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active}
                        <li class="{if $_system_menu eq 'message'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-android-chat"></i> <span>{Lang::T('Send Message')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'send' }class="active" {/if}><a
                                        href="{Text::url('message/send')}">{Lang::T('Single Customer')}</a></li>
                                <li {if $_routes[1] eq 'send_bulk' }class="active" {/if}><a
                                        href="{Text::url('message/send_bulk')}">{Lang::T('Bulk Customers')}</a></li>
                                {$_MENU_MESSAGE}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_MESSAGE}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'network'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-network"></i> <span>{Lang::T('Network')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'routers' and $_routes[1] eq '' }class="active" {/if}><a
                                        href="{Text::url('routers')}">Routers</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'list' }class="active" {/if}><a
                                        href="{Text::url('pool/list')}">IP Pool</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'port' }class="active" {/if}><a
                                        href="{Text::url('pool/port')}">Port Pool</a></li>
                                <li {if $_routes[0] eq 'odp' and $_routes[1] eq '' }class="active" {/if}><a
                                        href="{Text::url('odp')}">ODP List</a></li>
                                {$_MENU_NETWORK}
                            </ul>
                        </li>
                        {$_MENU_AFTER_NETWORKS}
                        {if $_c['radius_enable']}
                            <li class="{if $_system_menu eq 'radius'}active{/if} treeview">
                                <a href="#">
                                    <i class="fa fa-database"></i> <span>{Lang::T('Radius')}</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li {if $_routes[0] eq 'radius' and $_routes[1] eq 'nas-list' }class="active" {/if}><a
                                            href="{Text::url('radius/nas-list')}">{Lang::T('Radius NAS')}</a></li>
                                    {$_MENU_RADIUS}
                                </ul>
                            </li>
                        {/if}
                        {$_MENU_AFTER_RADIUS}
                        <li class="{if $_system_menu eq 'pages' || ($_system_menu eq 'settings' && ($_routes[1] eq 'notifications' || $_routes[1] eq 'smtp')) || ($_routes[0] eq 'plugin' && $_routes[1] eq 'whatsappGateway')}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-android-notifications"></i> <span>{Lang::T("Notification")}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'notifications' }class="active" {/if}><a
                                        href="{Text::url('settings/notifications')}">{Lang::T('Sms notification')}</a></li>
                                <li {if $_routes[1] eq 'notifications' }class="active" {/if}><a
                                        href="{Text::url('settings/notifications')}">{Lang::T('Email Notification')}</a></li>
                                <li {if $_routes[1] eq 'notifications' }class="active" {/if}><a
                                        href="{Text::url('settings/notifications')}">{Lang::T('Telegram notification')}</a></li>
                                <li {if $_routes[1] eq 'notifications' }class="active" {/if}><a
                                        href="{Text::url('settings/notifications')}">{Lang::T('User notification')}</a></li>
                                <li {if $_routes[1] eq 'smtp' }class="active" {/if}><a
                                        href="{Text::url('settings/smtp')}">Serveur SMTP</a></li>
                                {$_MENU_NOTIFICATION}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_PAGES}
                    {if $_admin['user_type'] eq 'Admin' || ($_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active)}
                        <li class="{if $_system_menu eq 'isp_reseller' || ($_routes[0] eq 'admin' && $_routes[1] eq 'subscription') || ($_routes[0] eq 'superadmin' && in_array($_routes[1], ['isp-settings','admin-subscriptions','instances']))}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-sitemap"></i>
                                <span>{Lang::T('ISP Reseller Plan')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                {if $_admin['user_type'] eq 'Admin'}
                                    <li {if $_routes[0] eq 'admin' && $_routes[1] eq 'subscription'}class="active" {/if}>
                                        <a href="{Text::url('admin/subscription')}">{Lang::T('My Subscription')}</a>
                                    </li>
                                {/if}
                                {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active}
                                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'isp-settings'}class="active" {/if}><a
                                            href="{Text::url('superadmin/isp-settings')}">{Lang::T('ISP Settings')}</a></li>
                                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'admin-subscriptions'}class="active" {/if}><a
                                            href="{Text::url('superadmin/admin-subscriptions')}">{Lang::T('Admin Subscriptions')}</a></li>
                                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'instances'}class="active" {/if}><a
                                            href="{Text::url('superadmin/instances')}">{Lang::T('Instances')}</a></li>
                                {/if}
                            </ul>
                        </li>
                    {/if}
                    <li
                        class="{if $_system_menu eq 'settings' || $_system_menu eq 'paymentgateway' }active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-gear-a"></i> <span>{Lang::T('Settings')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[0] eq 'settings' && $_routes[1] eq 'hotspot'}class="active" {/if}>
                                    <a href="{Text::url('settings/hotspot')}">{Lang::T('Hotspot Settings')}</a>
                                </li>
                            {/if}
                            {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active}
                                <li {if $_routes[1] eq 'app' }class="active" {/if}><a
                                        href="{Text::url('settings/app')}">{Lang::T('General Settings')}</a></li>
                                <li {if $_routes[1] eq 'miscellaneous' }class="active" {/if}><a
                                        href="{Text::url('settings/miscellaneous')}">{Lang::T('Miscellaneous')}</a></li>
                                <li {if $_routes[1] eq 'maintenance' }class="active" {/if}><a
                                        href="{Text::url('settings/maintenance')}">{Lang::T('Maintenance Mode')}</a></li>
                                <li {if $_routes[0] eq 'widgets' }class="active" {/if}><a
                                            href="{Text::url('widgets')}">{Lang::T('Widgets')}</a></li>
                                <li {if $_routes[1] eq 'devices' }class="active" {/if}><a
                                        href="{Text::url('settings/devices')}">{Lang::T('Devices')}</a></li>
                            {/if}
                            {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active}
                                <li {if $_routes[1] eq 'dbstatus' }class="active" {/if}><a
                                        href="{Text::url('settings/dbstatus')}">{Lang::T('Backup/Restore')}</a></li>
                                <li {if $_system_menu eq 'paymentgateway' }class="active" {/if}>
                                    <a href="{Text::url('paymentgateway')}">
                                        <span class="text">{Lang::T('Payment Gateway')}</span>
                                    </a>
                                </li>
                                {$_MENU_SETTINGS}
                                <li {if $_routes[0] eq 'pluginmanager' }class="active" {/if}>
                                    <a href="{Text::url('pluginmanager')}"><i class="glyphicon glyphicon-tasks"></i>
                                        {Lang::T('Plugin Manager')}</a>
                                </li>
                            {/if}
                        </ul>
                    </li>
                    {$_MENU_AFTER_SETTINGS}
                </ul>
            </section>
        </aside>

        {if $_c['maintenance_mode'] == 1}
            <div class="notification-top-bar">
                <p>{Lang::T('The website is currently in maintenance mode, this means that some or all functionality may be
                unavailable to regular users during this time.')}<small> &nbsp;&nbsp;<a
                            href="{Text::url('settings/maintenance')}">{Lang::T('Turn Off')}</a></small></p>
            </div>
        {/if}

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    {$_title}
                </h1>
            </section>

            <section class="content">
                {if isset($notify)}
                    <script>
                        // Display SweetAlert toast notification
                        Swal.fire({
                            icon: '{if $notify_t == "s"}success{else}error{/if}',
                            title: '{$notify}',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    </script>
                {/if}
<script>
(function () { 
    function applyTheme(theme) {
        document.body.classList.remove("theme-light", "theme-dark", "dark-mode");
        document.body.classList.add(theme);
        if (theme === "theme-dark") {
            document.body.classList.add("dark-mode");
        }
        var icon = document.getElementById("toggleIcon");
        if (icon) {
            icon.className = theme === "theme-dark" ? "fa fa-sun-o toggle-icon" : "fa fa-moon-o toggle-icon";
        }
    }

    applyTheme(localStorage.getItem("theme") || "theme-light");

    window.toggleTheme = function () {
        var nextTheme = document.body.classList.contains("theme-dark") ? "theme-light" : "theme-dark";
        localStorage.setItem("theme", nextTheme);
        applyTheme(nextTheme);
    };
})();
</script>

