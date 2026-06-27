{include file="sections/header.tpl"}

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Google Fonts: DM Sans + DM Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<!-- Icons: Phosphor Icons (modern, clean) -->
<script src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
<!-- Network Mapping Custom CSS -->
<link rel="stylesheet" href="system/plugin/ui/css/network_mapping.css" />

<style>
{literal}
/* ===================== GCS DEVICES — DESIGN SYSTEM ===================== */
* { box-sizing: border-box; }

/* ---- bk-* base ---- */
.bk-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; font-family: 'DM Sans', sans-serif; margin-bottom: 16px; }
.bk-card-header { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; background: #fafcff; }
.bk-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bk-card-title { font-size: 13px; font-weight: 700; color: #0f172a; }
.bk-card-subtitle { font-size: 11px; color: #94a3b8; }
.bk-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 5px; }
.bk-input { width: 100%; height: 36px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f172a; background: #f8fafc; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
.bk-input:focus { border-color: #0271c6; background: #fff; box-shadow: 0 0 0 3px rgba(2,113,198,0.10); }
.bk-input::placeholder { color: #cbd5e1; }
.bk-select { width: 100%; height: 36px; padding: 0 32px 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f172a; background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256'%3E%3Cpath fill='%2364748b' d='M213.66 101.66l-80 80a8 8 0 0 1-11.32 0l-80-80a8 8 0 0 1 11.32-11.32L128 164.69l74.34-74.35a8 8 0 0 1 11.32 11.32z'/%3E%3C/svg%3E") no-repeat right 10px center / 14px; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s; }
.bk-select:focus { border-color: #0271c6; background-color: #fff; box-shadow: 0 0 0 3px rgba(2,113,198,0.10); }
.bk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; white-space: nowrap; box-sizing: border-box; }
.bk-btn-primary { background: #0271c6; color: white; box-shadow: 0 2px 8px rgba(2,113,198,0.25); }
.bk-btn-primary:hover { background: #0359a0; color: white; text-decoration: none; }
.bk-btn-success { background: #16a34a; color: white; box-shadow: 0 2px 8px rgba(22,163,74,0.25); }
.bk-btn-success:hover { background: #15803d; color: white; text-decoration: none; }
.bk-btn-warning { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.bk-btn-warning:hover { background: #fde68a; color: #d97706; text-decoration: none; }
.bk-btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.bk-btn-danger:hover { background: #fecaca; color: #dc2626; text-decoration: none; }
.bk-btn-ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.bk-btn-ghost:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }
.bk-btn-sm { height: 30px; padding: 0 12px; font-size: 12px; border-radius: 6px; }

/* ---- Stat Cards ---- */
.gd-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.gd-stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; transition: box-shadow 0.15s; }
.gd-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.gd-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px; }
.gd-stat-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.gd-stat-value { font-size: 24px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1; margin-top: 2px; }
.gd-stat-sub { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.gd-stat-blue   { border-bottom: 3px solid #0271c6; }
.gd-stat-indigo { border-bottom: 3px solid #4f46e5; }
.gd-stat-green  { border-bottom: 3px solid #16a34a; }
.gd-stat-red    { border-bottom: 3px solid #dc2626; }
.gd-stat-yellow { border-bottom: 3px solid #d97706; }

/* Progress mini bar inside stat */
.gd-mini-bar { height: 3px; border-radius: 2px; background: #f1f5f9; margin-top: 6px; overflow: hidden; }
.gd-mini-fill { height: 100%; border-radius: 2px; transition: width 1.5s ease-in-out; }

/* Server stat card — fullwidth left col */
.gd-server-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    grid-column: span 1;
}

/* ---- Filter Bar ---- */
.gd-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; background: #fafcff; }
.gd-filter-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 130px; }
.gd-filter-group.wide { flex: 2; min-width: 200px; }
.gd-filter-group.auto { flex: none; min-width: auto; }

/* ---- Table ---- */
.gd-table-wrap { overflow-x: auto; }
.gd-table { width: 100%; border-collapse: collapse; font-family: 'DM Sans', sans-serif; font-size: 12px; }
.gd-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.gd-table thead th { padding: 10px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; text-align: left; white-space: nowrap; }
.gd-table thead th.center { text-align: center; }
.gd-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: all 0.12s; cursor: pointer; }
.gd-table tbody tr:last-child { border-bottom: none; }
.gd-table tbody tr:hover { background: #f8fafc; box-shadow: inset 3px 0 0 #0271c6; }
.gd-table tbody td { padding: 10px 12px; color: #334155; vertical-align: middle; }
.gd-table tbody td.center { text-align: center; }
.selectable-text { user-select: text; }

/* ---- Status badges ---- */
.gd-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.gd-status-online  { background: #dcfce7; color: #15803d; }
.gd-status-offline { background: #fee2e2; color: #dc2626; }
.gd-pulse { width: 6px; height: 6px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.gd-pulse-green { background: #22c55e; animation: gdPulseGreen 2s infinite; }
.gd-pulse-red   { background: #ef4444; animation: gdPulseRed 2s infinite; }
@keyframes gdPulseGreen { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.6)} 70%{box-shadow:0 0 0 5px rgba(34,197,94,0)} }
@keyframes gdPulseRed   { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.6)}  70%{box-shadow:0 0 0 5px rgba(239,68,68,0)} }

/* ---- PON badge ---- */
.gd-pon { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 20px; font-size: 10px; font-weight: 700; }
.gd-pon-gpon     { background: #1d4ed8; color: #ffffff; }
.gd-pon-epon     { background: #ede9fe; color: #7c3aed; }
.gd-pon-ethernet { background: #ffedd5; color: #c2410c; }

/* ---- RX Power ---- */
.gd-rx { font-size: 11px; font-weight: 700; font-family: 'DM Mono', monospace; }
.gd-rx-good   { color: #16a34a; }
.gd-rx-fair   { color: #d97706; }
.gd-rx-poor   { color: #dc2626; }
.gd-rx-na     { color: #94a3b8; }

/* Colored value badges */
.gd-badge-rx-good { display:inline-flex;align-items:center;gap:4px;background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-rx-fair { display:inline-flex;align-items:center;gap:4px;background:#fef3c7;border:1px solid #fde68a;color:#d97706;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-rx-poor { display:inline-flex;align-items:center;gap:4px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-rx-na   { display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;border:1px solid #e2e8f0;color:#94a3b8;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-ip      { display:inline-flex;align-items:center;gap:4px;background:#f0f9ff;border:1px solid #bae6fd;color:#0284c7;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-user    { display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #bfdbfe;color:#0271c6;font-size:12px;font-weight:700;border-radius:6px;padding:2px 9px;font-family:'DM Sans',monospace; }
.gd-badge-temp-ok   { display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-temp-warm { display:inline-flex;align-items:center;gap:4px;background:#fef3c7;border:1px solid #fde68a;color:#d97706;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.gd-badge-temp-hot  { display:inline-flex;align-items:center;gap:4px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }

/* ---- Misc chips ---- */
.gd-tag-chip { display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; border: 1px solid #dbeafe; color: #0271c6; font-size: 11px; font-weight: 600; border-radius: 6px; padding: 2px 8px; }
.gd-loc-chip { display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; border: 1px solid #dcfce7; color: #16a34a; font-size: 11px; font-weight: 600; border-radius: 6px; padding: 2px 8px; }
.gd-mono { font-family: 'DM Mono', monospace; font-size: 11px; color: #475569; }
.gd-muted { color: #94a3b8; font-size: 11px; }

/* ---- Action buttons in table — transparent, colored icon ---- */
.gd-action-group { display: flex; gap: 4px; justify-content: flex-end; }
.gd-action-btn { width: 30px; height: 30px; border-radius: 7px; border: none; background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 15px; transition: all 0.12s; text-decoration: none; }
.gd-action-btn.view   { color: #0271c6; }
.gd-action-btn.view:hover   { background: #eff6ff; color: #0271c6; }
.gd-action-btn.summon { color: #16a34a; }
.gd-action-btn.summon:hover { background: #f0fdf4; color: #16a34a; }
.gd-action-btn.reboot { color: #dc2626; }
.gd-action-btn.reboot:hover { background: #fee2e2; color: #dc2626; }

/* ---- Mobile device cards ---- */
.gd-mobile-list { display: none; padding: 12px; }
.gd-mobile-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; overflow: hidden; }
.gd-mobile-card.online-card  { border-left: 3px solid #22c55e; }
.gd-mobile-card.offline-card { border-left: 3px solid #ef4444; }
.gd-mobile-card-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px 8px; }
.gd-mobile-card-body { padding: 0 14px 10px; }
.gd-mobile-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f8fafc; font-size: 12px; }
.gd-mobile-row:last-child { border-bottom: none; }
.gd-mobile-key { font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.04em; }
.gd-mobile-val { font-size: 12px; color: #334155; font-weight: 500; text-align: right; max-width: 65%; word-break: break-word; }
.gd-mobile-actions { display: flex; border-top: 1px solid #f1f5f9; }
.gd-mobile-action-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 10px 4px; font-size: 12px; font-weight: 600; font-family: 'DM Sans', sans-serif; background: transparent; border: none; cursor: pointer; transition: background 0.12s; text-decoration: none; border-right: 1px solid #f1f5f9; }
.gd-mobile-action-btn.view   { color: #0271c6; background: #f0f7ff; border-top: 1px solid #e2e8f0; }
.gd-mobile-action-btn.summon { color: #16a34a; background: #f0fdf4; border-top: 1px solid #e2e8f0; }
.gd-mobile-action-btn.reboot { color: #dc2626; background: #fff5f5; border-top: 1px solid #e2e8f0; }
.gd-mobile-action-btn:last-child { border-right: none; }
.gd-mobile-action-btn i { font-size: 14px; }
.gd-mobile-action-btn.view   { color: #0271c6; }
.gd-mobile-action-btn.view:hover   { background: #eff6ff; text-decoration: none; }
.gd-mobile-action-btn.summon { color: #16a34a; }
.gd-mobile-action-btn.summon:hover { background: #f0fdf4; }
.gd-mobile-action-btn.reboot { color: #dc2626; }
.gd-mobile-action-btn.reboot:hover { background: #fee2e2; }

/* ---- Pagination ---- */
.gd-pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-top: 1px solid #f1f5f9; background: #fafcff; flex-wrap: wrap; gap: 10px; }
.gd-pag-info { font-size: 12px; color: #64748b; }
.gd-pag-right { display: flex; align-items: center; gap: 8px; }
.gd-pag-btns { display: flex; gap: 4px; }
.gd-pag-btn { height: 30px; min-width: 30px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; border-radius: 7px; font-size: 12px; font-weight: 500; font-family: 'DM Sans', sans-serif; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; transition: all 0.1s; text-decoration: none; }
.gd-pag-btn:hover { background: #f1f5f9; color: #0f172a; text-decoration: none; }
.gd-pag-btn.active { background: #0271c6; color: white; border-color: #0271c6; }
.gd-pag-btn.disabled { opacity: 0.4; pointer-events: none; }

/* ---- Empty / Error states ---- */
.gd-empty { padding: 56px 24px; text-align: center; font-family: 'DM Sans', sans-serif; }
.gd-empty > i { font-size: 48px; color: #e2e8f0; display: block; margin-bottom: 14px; }
.gd-empty h4 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 6px; }
.gd-empty p { font-size: 13px; color: #94a3b8; margin: 0 0 18px; }
.gd-empty-actions { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.gd-empty-actions .bk-btn i { font-size: 13px; color: inherit; display: inline; margin-bottom: 0; }

.gd-error-box { padding: 32px 24px; text-align: center; font-family: 'DM Sans', sans-serif; }
.gd-error-icon { width: 64px; height: 64px; border-radius: 16px; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; color: #dc2626; position: relative; }
.gd-error-badge { position: absolute; bottom: -4px; right: -4px; width: 20px; height: 20px; border-radius: 50%; background: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 10px; color: white; border: 2px solid white; }
.gd-error-detail { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #94a3b8; justify-content: center; margin-top: 6px; }

/* ---- Sync warning banner ---- */
.gd-sync-warn { display: inline-flex; align-items: center; gap: 8px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 8px 14px; font-size: 12px; color: #c2410c; font-family: 'DM Sans', sans-serif; }

/* Swal override */
.swal2-container { z-index: 999999 !important; }

/* ===================== TABLET ===================== */
@media (max-width: 991px) {
    .gd-stat-grid { grid-template-columns: repeat(2, 1fr); }
    .gd-server-card { grid-column: 1 / -1; flex-direction: row !important; align-items: center !important; }
}

/* ===================== MOBILE ===================== */
@media (max-width: 767px) {

    /* --- Header: title kiri, tombol kanan dalam 1 baris rapi --- */
    .bk-card-header { flex-wrap: nowrap; padding: 12px 14px; gap: 8px; align-items: center; }
    .bk-card-header > div:first-child { min-width: 0; flex: 1; }
    .bk-card-title { font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bk-card-subtitle { display: none; }

    /* Header aksi: semua jadi icon-only, 1 baris */
    .gd-header-actions { flex-wrap: nowrap; gap: 6px; flex-shrink: 0; }
    .gd-header-actions .bk-btn { height: 32px; width: 32px; padding: 0; border-radius: 8px; }
    .gd-header-actions .bk-btn span.btn-text { display: none; }
    .gd-header-actions .bk-btn i { font-size: 14px; }
    .gd-badge-count { display: none; }

    /* Refresh button khusus: tetap ada text tapi compact */
    #refreshButton { width: auto !important; padding: 0 10px !important; }
    #refreshButton #refreshButtonText { display: none; }

    /* --- Stat grid: server card fullwidth, 3 kartu di bawah dalam grid 3-col --- */
    .gd-stat-grid {
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        padding: 12px;
    }
    .gd-server-card {
        grid-column: 1 / -1;
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px;
        padding: 12px 14px;
    }
    .gd-server-card > div { width: 100%; }
    .gd-stat-icon { width: 34px; height: 34px; font-size: 15px; border-radius: 8px; }
    /* 3 stat cards bawah: compact tanpa icon, teks saja */
    .gd-stat-card:not(.gd-server-card) { flex-direction: column; align-items: flex-start; gap: 4px; padding: 10px 12px; border-radius: 12px; }
    .gd-stat-card:not(.gd-server-card) .gd-stat-icon { display: none; }
    .gd-stat-value { font-size: 22px; }
    .gd-stat-label { font-size: 10px; }
    .gd-stat-sub { font-size: 10px; }

    /* --- Filter bar: 2 kolom grid (search fullwidth, filter 2 col) --- */
    .gd-filter-bar { padding: 12px; gap: 8px; flex-direction: column; }
    .gd-filter-group { flex: none; min-width: 100%; }
    .gd-filter-group.wide { min-width: 100%; }

    /* Status + RX Power → 2 kolom sejajar */
    .gd-filter-row-2col {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .gd-filter-row-2col .gd-filter-group { min-width: 0 !important; flex: 1; }

    /* Clear button fullwidth */
    .gd-filter-group.auto { min-width: 100%; }
    .gd-filter-group.auto .bk-btn { width: 100%; justify-content: center; }

    /* Label filter lebih compact */
    .bk-label { font-size: 10px; margin-bottom: 3px; }

    /* Show mobile cards, hide desktop table */
    .gd-mobile-list { display: block; }
    .gd-table-wrap { display: none; }

    /* Pagination compact */
    .gd-pagination { flex-direction: column; align-items: flex-start; padding: 10px 12px; gap: 8px; }
    .gd-pag-right { width: 100%; justify-content: space-between; }
    .gd-pag-info { font-size: 11px; }
}
{/literal}
</style>

<!-- ===================== PAGE WRAPPER ===================== -->
<div class="bk-card" style="margin-bottom:16px;">

    <!-- ===================== CARD HEADER ===================== -->
    <div class="bk-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="bk-card-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                <i class="ph ph-devices" style="font-size:16px;color:#7c3aed;"></i>
            </div>
            <div>
                <div class="bk-card-title" style="font-size:14px;">ACS Device</div>
                <div class="bk-card-subtitle">Manage &amp; monitor all registered devices</div>
            </div>
        </div>
        <div class="gd-header-actions" style="display:flex;align-items:center;gap:8px;">
            <a href="{$_url}plugin/genieacs_manager" class="bk-btn bk-btn-ghost bk-btn-sm">
                <i class="ph ph-hard-drives" style="font-size:13px;"></i>
                <span class="btn-text">Manage Servers</span>
            </a>
            <button class="bk-btn bk-btn-warning bk-btn-sm" onclick="forceSync()" title="Force sync from ACS servers">
                <i class="ph ph-arrow-clockwise" style="font-size:13px;"></i>
                <span class="btn-text">Force Sync</span>
            </button>
            <button id="refreshButton" class="bk-btn bk-btn-danger bk-btn-sm" onclick="refreshDevices()" title="Refresh Devices">
                <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i>
                <span id="refreshButtonText">Refresh Offline</span>
            </button>
        </div>
    </div>

    <!-- ===================== STAT CARDS ===================== -->
    <div class="gd-stat-grid">

        <!-- Active Server + Device Count -->
        <div class="gd-stat-card gd-stat-blue gd-server-card">
            <div style="width:100%;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <div class="gd-stat-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);flex-shrink:0;">
                        <i class="ph ph-hard-drives" style="color:#0271c6;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <div class="gd-stat-label">Active Server</div>
                            <span style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #bfdbfe;color:#0271c6;font-size:11px;font-weight:700;border-radius:20px;padding:2px 8px;font-family:'DM Sans',sans-serif;flex-shrink:0;">
                                <i class="ph ph-device-mobile" style="font-size:10px;"></i>
                                <span class="count-up" data-target="{$device_count}">0</span> devices
                            </span>
                        </div>
                        <div style="font-size:14px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{$current_server->name}</div>
                        <div style="margin-top:3px;">
                            {if $current_server->is_connected}
                                <span class="gd-status gd-status-online"><span class="gd-pulse gd-pulse-green"></span> Connected</span>
                            {else}
                                <span class="gd-status gd-status-offline"><span class="gd-pulse gd-pulse-red"></span> Disconnected</span>
                            {/if}
                        </div>
                    </div>
                </div>
                <select id="quickServerSwitch" class="bk-select" onchange="changeServer()">
                    {foreach $servers as $server}
                        <option value="{$server->id}" {if $server->id == $selected_server_id}selected{/if}>{$server->name}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <!-- Online -->
        <div class="gd-stat-card gd-stat-green">
            <div class="gd-stat-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                <i class="ph ph-check-circle" style="color:#16a34a;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="gd-stat-label">Online</div>
                <div class="gd-stat-value count-up" data-target="{$online_count|default:0}">0</div>
                <div class="gd-mini-bar"><div class="gd-mini-fill" style="width:{$online_percentage|default:0}%;background:#16a34a;"></div></div>
                <div class="gd-stat-sub">{$online_percentage|default:0}% active</div>
            </div>
        </div>

        <!-- Offline -->
        <div class="gd-stat-card gd-stat-red">
            <div class="gd-stat-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
                <i class="ph ph-x-circle" style="color:#dc2626;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="gd-stat-label">Offline</div>
                <div class="gd-stat-value count-up" data-target="{$offline_count|default:0}">0</div>
                <div class="gd-mini-bar"><div class="gd-mini-fill" style="width:{$offline_percentage|default:0}%;background:#dc2626;"></div></div>
                <div class="gd-stat-sub">{$offline_percentage|default:0}% inactive</div>
            </div>
        </div>

        <!-- Warning -->
        <div class="gd-stat-card gd-stat-yellow">
            <div class="gd-stat-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
                <i class="ph ph-warning" style="color:#d97706;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="gd-stat-label">Warning</div>
                <div class="gd-stat-value count-up" data-target="{$warning_count|default:0}">0</div>
                <div class="gd-mini-bar"><div class="gd-mini-fill" style="width:{$warning_percentage|default:0}%;background:#d97706;"></div></div>
                <div class="gd-stat-sub">RX &lt; -25dBm</div>
            </div>
        </div>

    </div>

    <!-- ===================== FILTER BAR ===================== -->
    <form method="GET" action="index.php" style="display:contents;">
        <input type="hidden" name="_route" value="plugin/genieacs_devices">
        <div class="gd-filter-bar">

            <div class="gd-filter-group wide">
                <label class="bk-label"><i class="ph ph-magnifying-glass" style="font-size:10px;"></i> Search</label>
                <div style="position:relative;">
                    <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                    <input type="text" id="ajaxSearchInput" name="search" class="bk-input" placeholder="Search by username, IP, serial..." value="{$smarty.get.search|default:''}" style="padding-left:32px;">
                </div>
                <small id="searchStatus" style="font-size:11px;color:#94a3b8;margin-top:2px;"></small>
            </div>

            <div class="gd-filter-row-2col" style="display:contents;">

            <div class="gd-filter-group">
                <label class="bk-label"><i class="ph ph-dot-outline" style="font-size:10px;"></i> Status</label>
                <select name="status" id="statusFilter" class="bk-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="online"  {if $smarty.get.status == 'online'}selected{/if}>Online</option>
                    <option value="offline" {if $smarty.get.status == 'offline'}selected{/if}>Offline</option>
                </select>
            </div>

            <div class="gd-filter-group">
                <label class="bk-label"><i class="ph ph-wave-sine" style="font-size:10px;"></i> RX Power</label>
                <select name="rx_power" id="rxPowerFilter" class="bk-select" onchange="this.form.submit()">
                    <option value="">All RX Power</option>
                    <option value="good" {if $smarty.get.rx_power == 'good'}selected{/if}>🟢 Bagus (≥ -20 dBm)</option>
                    <option value="fair" {if $smarty.get.rx_power == 'fair'}selected{/if}>🟡 Sedang (-21 to -25 dBm)</option>
                    <option value="poor" {if $smarty.get.rx_power == 'poor'}selected{/if}>🔴 Buruk (&lt; -25 dBm)</option>
                </select>
            </div>

            </div>

            {if $available_locations && count($available_locations) > 0}
            <div class="gd-filter-group">
                <label class="bk-label"><i class="ph ph-map-pin" style="font-size:10px;"></i> Location</label>
                <select name="location" id="locationFilter" class="bk-select" onchange="this.form.submit()">
                    <option value="">All Locations</option>
                    {foreach $available_locations as $location}
                        <option value="{$location}" {if $smarty.get.location == $location}selected{/if}>{$location}</option>
                    {/foreach}
                </select>
            </div>
            {/if}

            <div class="gd-filter-group auto">
                <label class="bk-label" style="visibility:hidden;">.</label>
                <a href="javascript:void(0)" onclick="clearAllFilters()" id="clearFiltersBtn"
                    class="bk-btn bk-btn-danger bk-btn-sm"
                    style="{if !$smarty.get.search && !$smarty.get.status && !$smarty.get.rx_power && !$smarty.get.location}display:none;{/if}">
                    <i class="ph ph-x" style="font-size:12px;"></i> Clear
                </a>
            </div>

        </div>
    </form>

    <!-- ===================== MOBILE DEVICE CARDS ===================== -->
    <div class="gd-mobile-list" id="mobileDeviceList">
        {foreach $devices as $device}
        {* RX level calc *}
        {if $device.rx_power == 'N/A'}
            {assign var="rx_level" value="na"}
        {elseif strpos($device.rx_power, '-') !== false}
            {assign var="rx_value" value=floatval($device.rx_power)}
            {if $rx_value >= -20}{assign var="rx_level" value="good"}
            {elseif $rx_value >= -25}{assign var="rx_level" value="fair"}
            {else}{assign var="rx_level" value="poor"}{/if}
        {else}
            {assign var="rx_level" value="na"}
        {/if}

        <div class="gd-mobile-card {if $device.status == 'online'}online-card{else}offline-card{/if}"
            data-username="{$device.pppoe_username}" data-ip="{$device.ip}"
            data-tags="{$device.tags}" data-lokasi="{$device.lokasi}"
            data-status="{$device.status}" data-rx-level="{$rx_level}" data-rx-value="{$device.rx_power}">

            <div class="gd-mobile-card-head">
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" class="device-checkbox" data-device-id="{$device.id_raw}"
                        data-device-status="{$device.status}" onchange="updateRefreshButton()">
                    {if $device.status == 'online'}
                        <span class="gd-status gd-status-online"><span class="gd-pulse gd-pulse-green"></span> Online</span>
                    {else}
                        <span class="gd-status gd-status-offline"><span class="gd-pulse gd-pulse-red"></span> Offline</span>
                    {/if}
                </div>
                <div style="display:flex;align-items:center;gap:5px;">
                    {foreach $display_params as $param}
                        {if $param->param_key == 'pon_type'}
                            {assign var="pon_val" value=$device.pon_type|default:'N/A'}
                            {if $pon_val == 'GPON'}<span class="gd-pon gd-pon-gpon">{$pon_val}</span>
                            {elseif $pon_val == 'EPON'}<span class="gd-pon gd-pon-epon">{$pon_val}</span>
                            {elseif $pon_val == 'Ethernet' || $pon_val == 'ETHERNET' || $pon_val == 'ethernet'}<span class="gd-pon gd-pon-ethernet">{$pon_val}</span>
                            {else}<span class="gd-muted">{$pon_val}</span>{/if}
                            {break}
                        {/if}
                    {/foreach}
                </div>
            </div>

            <div class="gd-mobile-card-body">
                {assign var="primary_params" value=[]}
                {assign var="shown_params" value=[]}

                {foreach $display_params as $param}
                    {assign var="param_key" value=$param->param_key}
                    {assign var="param_value" value=$device.$param_key|default:'N/A'}

                    {if in_array($param_key, ['ppp_username','pppoe_username','device_id','serial_number'])}
                        {if $param_key == 'ppp_username' || $param_key == 'pppoe_username'}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">{$param->param_label}</span>
                            <span class="gd-mobile-val"><span class="gd-badge-user"><i class="ph ph-user" style="font-size:10px;"></i>{$param_value}</span></span>
                        </div>
                        {assign var="shown_params" value=$shown_params|array_merge:[$param_key,'serial_number']}
                        {/if}
                    {elseif in_array($param_key, ['vendor','manufacturer'])}
                        {if !in_array('vendor_model_shown', $shown_params)}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">Vendor / Model</span>
                            <span class="gd-mobile-val">{$param_value}
                                {foreach $display_params as $mp}{if $mp->param_key == 'model'} / {$device.model|default:'N/A'}{break}{/if}{/foreach}
                            </span>
                        </div>
                        {assign var="shown_params" value=$shown_params|array_merge:['vendor_model_shown','model']}
                        {/if}
                    {elseif in_array($param_key, ['pppoe_ip','tr069_ip','ip'])}
                        {if !in_array('network_shown', $shown_params)}
                        {* IP — baris sendiri *}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">IP Address</span>
                            <span class="gd-mobile-val">
                                {if $param_value != 'N/A' && $param_value != ''}
                                    <span class="gd-badge-ip"><i class="ph ph-wifi-high" style="font-size:10px;"></i>{$param_value}</span>
                                {else}<span class="gd-muted">N/A</span>{/if}
                            </span>
                        </div>
                        {* RX Power — baris sendiri *}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">RX Power</span>
                            <span class="gd-mobile-val">
                                {foreach $display_params as $rp}
                                    {if $rp->param_key == 'rx_power'}
                                        {assign var="rpv" value=$device.rx_power|default:'N/A'}
                                        {if $rpv != 'N/A' && $rpv != ''}
                                            {assign var="rpn" value=floatval($rpv)}
                                            {if $rpn >= -20}<span class="gd-badge-rx-good"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$rpv} dBm</span>
                                            {elseif $rpn >= -25}<span class="gd-badge-rx-fair"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$rpv} dBm</span>
                                            {else}<span class="gd-badge-rx-poor"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$rpv} dBm</span>{/if}
                                        {else}<span class="gd-badge-rx-na">N/A</span>{/if}
                                        {break}
                                    {/if}
                                {/foreach}
                            </span>
                        </div>
                        {assign var="shown_params" value=$shown_params|array_merge:['network_shown','rx_power']}
                        {/if}
                    {elseif in_array($param_key, ['uptime','ppp_uptime'])}
                        {if !in_array('uptime_shown', $shown_params)}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">Uptime</span>
                            <span class="gd-mobile-val">{$param_value}</span>
                        </div>
                        {assign var="shown_params" value=$shown_params|array_merge:['uptime_shown']}
                        {/if}
                    {elseif !in_array($param_key, $shown_params) && !in_array($param_key, ['model','pon_type','serial_number'])}
                        <div class="gd-mobile-row">
                            <span class="gd-mobile-key">{$param->param_label}</span>
                            <span class="gd-mobile-val">
                                {if $param_key == 'rx_power'}
                                    {if $param_value != 'N/A' && $param_value != ''}
                                        {assign var="rpn2" value=floatval($param_value)}
                                        {if $rpn2 >= -20}<span class="gd-badge-rx-good"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>
                                        {elseif $rpn2 >= -25}<span class="gd-badge-rx-fair"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>
                                        {else}<span class="gd-badge-rx-poor"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>{/if}
                                    {else}<span class="gd-badge-rx-na">N/A</span>{/if}
                                {elseif $param_key == 'temperature'}
                                    {if $param_value != 'N/A' && $param_value != ''}
                                        {assign var="temp_val" value=floatval($param_value)}
                                        {if $temp_val < 60}<span class="gd-badge-temp-ok"><i class="ph ph-thermometer" style="font-size:10px;"></i>{$param_value}°C</span>
                                        {elseif $temp_val < 75}<span class="gd-badge-temp-warm"><i class="ph ph-thermometer-hot" style="font-size:10px;"></i>{$param_value}°C</span>
                                        {else}<span class="gd-badge-temp-hot"><i class="ph ph-thermometer-hot" style="font-size:10px;"></i>{$param_value}°C</span>{/if}
                                    {else}<span class="gd-muted">N/A</span>{/if}
                                {else}
                                    {$param_value}
                                {/if}
                            </span>
                        </div>
                    {/if}
                {/foreach}

                {if $device.tags || $device.lokasi}
                <div class="gd-mobile-row">
                    <span class="gd-mobile-key">Tag / Lokasi</span>
                    <span class="gd-mobile-val" style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;">
                        {if $device.tags}<span class="gd-tag-chip"><i class="ph ph-user" style="font-size:10px;"></i>{$device.tags}</span>{/if}
                        {if $device.lokasi}<span class="gd-loc-chip"><i class="ph ph-map-pin" style="font-size:10px;"></i>{$device.lokasi}</span>{/if}
                    </span>
                </div>
                {/if}
            </div>

            <div class="gd-mobile-actions">
                <a href="{$_url}plugin/genieacs_device_detail/{$device.id_raw}" class="gd-mobile-action-btn view">
                    <i class="ph ph-eye"></i> Details
                </a>
                <button class="gd-mobile-action-btn summon" onclick="summonDevice('{$device.device_id|default:$device.id_raw}')">
                    <i class="ph ph-bell"></i> Summon
                </button>
                <button class="gd-mobile-action-btn reboot" onclick="rebootDevice('{$device.device_id|default:$device.id_raw}')">
                    <i class="ph ph-power"></i> Reboot
                </button>
            </div>
        </div>
        {/foreach}
        {if $device_count == 0 && !$error}
        <div class="gd-empty">
            <i class="ph ph-device-mobile-slash"></i>
            <h4>No Devices Found</h4>
            <p>There are currently no devices registered in the GenieACS server.</p>
            <div class="gd-empty-actions">
                <button class="bk-btn bk-btn-ghost" onclick="location.reload()">
                    <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh Page
                </button>
                <button class="bk-btn bk-btn-ghost" onclick="window.location.href='{$_url}plugin/genieacs_manager'">
                    <i class="ph ph-hard-drives" style="font-size:13px;"></i> Check Server
                </button>
            </div>
        </div>
        {/if}
    </div>

    <!-- ===================== DESKTOP TABLE ===================== -->
    <div class="gd-table-wrap">
        <table id="deviceTable" class="gd-table">
            <thead>
                <tr>
                    <th style="width:36px;" class="center">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    </th>
                    <th style="width:80px;" class="center">Status</th>
                    {foreach $display_params as $param}
                        <th {if $param->param_label|strstr:'Serial' || $param->param_label|strstr:'SN'}style="width:100px;"
                            {elseif $param->param_label|strstr:'Manufac' || $param->param_label|strstr:'Vendor'}style="width:80px;"
                            {elseif $param->param_label|strstr:'Model'}style="width:70px;"
                            {elseif $param->param_label == 'PON' || $param->param_label|strstr:'PON'}style="width:60px;"class="center"
                            {elseif $param->param_label|strstr:'RX' || $param->param_label|strstr:'Power'}style="width:90px;"
                            {elseif $param->param_label|strstr:'Device ID' || $param->param_label|strstr:' ID'}style="width:120px;"{/if}>
                            {$param->param_label}
                        </th>
                    {/foreach}
                    <th style="width:80px;">Tag</th>
                    <th style="width:80px;">Lokasi</th>
                    <th style="width:90px;">Last Inform</th>
                    <th style="width:90px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $devices as $device}
                <tr>
                    <td class="center">
                        <input type="checkbox" class="device-checkbox"
                            data-device-id="{$device.id_raw}"
                            data-device-status="{$device.status}"
                            onchange="updateRefreshButton()">
                    </td>
                    <td class="center">
                        {if $device.status == 'online'}
                            <span class="gd-status gd-status-online"><span class="gd-pulse gd-pulse-green"></span> Online</span>
                        {else}
                            <span class="gd-status gd-status-offline"><span class="gd-pulse gd-pulse-red"></span> Offline</span>
                        {/if}
                    </td>

                    {foreach $display_params as $param}
                        {assign var="param_key" value=$param->param_key}
                        {assign var="param_value" value=$device.$param_key|default:'N/A'}
                        <td {if in_array($param_key, ['pppoe_username','pppoe_ip','ppp_username','ip','mac_address','ppp_mac','serial_number','sn'])}class="selectable-text"{/if}
                            {if $param->param_label|strstr:'Serial' || $param->param_label|strstr:'SN'}
                                style="max-width:100px;word-break:break-all;font-size:11px;line-height:1.3;"
                            {elseif $param->param_label|strstr:'Manufac' || $param->param_label|strstr:'Vendor' || $param->param_label|strstr:'Model'}
                                style="max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{$param_value}"
                            {/if}>

                            {if $param->param_key == 'pon_type' || $param->param_label == 'Pon Type' || $param->param_label == 'PON tpe' || $param->param_label == 'Pon Mode' || $param->param_label == 'PON'}
                                {if $param_value == 'GPON'}<span class="gd-pon gd-pon-gpon">{$param_value}</span>
                                {elseif $param_value == 'EPON'}<span class="gd-pon gd-pon-epon">{$param_value}</span>
                                {elseif $param_value == 'Ethernet' || $param_value == 'ETHERNET' || $param_value == 'ethernet'}<span class="gd-pon gd-pon-ethernet">{$param_value}</span>
                                {else}<span class="gd-muted">{$param_value}</span>{/if}

                            {elseif $param_key == 'pppoe_username' || $param_key == 'ppp_username'}
                                {if $param_value != 'N/A' && $param_value != ''}
                                    <span class="gd-badge-user"><i class="ph ph-user" style="font-size:10px;"></i>{$param_value}</span>
                                {else}<span class="gd-muted">—</span>{/if}

                            {elseif $param_key == 'pppoe_ip' || $param_key == 'tr069_ip' || $param_key == 'ip'}
                                {if $param_value != 'N/A' && $param_value != ''}
                                    <span class="gd-badge-ip"><i class="ph ph-wifi-high" style="font-size:10px;"></i>{$param_value}</span>
                                {else}<span class="gd-muted">—</span>{/if}

                            {elseif $param_key == 'rx_power'}
                                {if $param_value != 'N/A' && $param_value != ''}
                                    {assign var="rx_value" value=floatval($param_value)}
                                    {if $rx_value >= -20}<span class="gd-badge-rx-good"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>
                                    {elseif $rx_value >= -25}<span class="gd-badge-rx-fair"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>
                                    {else}<span class="gd-badge-rx-poor"><i class="ph ph-chart-bar" style="font-size:10px;"></i>{$param_value} dBm</span>{/if}
                                {else}<span class="gd-badge-rx-na">N/A</span>{/if}

                            {elseif $param_key == 'temperature'}
                                {if $param_value != 'N/A' && $param_value != ''}
                                    {assign var="temp_val" value=floatval($param_value)}
                                    {if $temp_val < 60}<span class="gd-badge-temp-ok"><i class="ph ph-thermometer" style="font-size:10px;"></i>{$param_value}°C</span>
                                    {elseif $temp_val < 75}<span class="gd-badge-temp-warm"><i class="ph ph-thermometer-hot" style="font-size:10px;"></i>{$param_value}°C</span>
                                    {else}<span class="gd-badge-temp-hot"><i class="ph ph-thermometer-hot" style="font-size:10px;"></i>{$param_value}°C</span>{/if}
                                {else}<span class="gd-muted">N/A</span>{/if}

                            {elseif $param->param_label|strstr:'Serial' || $param->param_label|strstr:'Vendor' || $param->param_label|strstr:'Model'}
                                <span class="gd-mono" style="font-size:11px;">{$param_value}</span>

                            {else}
                                <span style="font-size:12px;">{$param_value}</span>
                            {/if}
                        </td>
                    {/foreach}

                    <td>
                        {if $device.tags}
                            <span class="gd-tag-chip"><i class="ph ph-user" style="font-size:10px;"></i> {$device.tags}</span>
                        {else}
                            <span class="gd-muted">—</span>
                        {/if}
                    </td>
                    <td>
                        {if $device.lokasi}
                            <span class="gd-loc-chip"><i class="ph ph-map-pin" style="font-size:10px;"></i> {$device.lokasi}</span>
                        {else}
                            <span class="gd-muted">—</span>
                        {/if}
                    </td>
                    <td><span class="gd-muted" style="font-size:11px;">{$device.last_inform}</span></td>
                    <td>
                        <div class="gd-action-group">
                            <a href="{$_url}plugin/genieacs_device_detail/{$device.id_raw}" class="gd-action-btn view" title="View Details">
                                <i class="ph ph-eye"></i>
                            </a>
                            <button class="gd-action-btn summon" onclick="summonDevice('{$device.device_id|default:$device.id_raw}')" title="Summon Device">
                                <i class="ph ph-bell"></i>
                            </button>
                            <button class="gd-action-btn reboot" onclick="rebootDevice('{$device.device_id|default:$device.id_raw}')" title="Reboot Device">
                                <i class="ph ph-power"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        {* Empty state *}
        {if $device_count == 0 && !$error}
        <div class="gd-empty">
            <i class="ph ph-device-mobile-slash"></i>
            <h4>No Devices Found</h4>
            <p>There are currently no devices registered in the GenieACS server.</p>
            <div class="gd-empty-actions">
                <button class="bk-btn bk-btn-ghost" onclick="location.reload()">
                    <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh Page
                </button>
                <button class="bk-btn bk-btn-ghost" onclick="window.location.href='{$_url}plugin/genieacs_manager'">
                    <i class="ph ph-hard-drives" style="font-size:13px;"></i> Check Server
                </button>
            </div>
        </div>
        {/if}

        {* Error state *}
        {if $error}
        <div class="gd-error-box">
            <div class="gd-error-icon">
                <i class="ph ph-plug"></i>
                <span class="gd-error-badge"><i class="ph ph-x" style="font-size:9px;"></i></span>
            </div>
            <h4 style="font-size:15px;font-weight:700;color:#0f172a;margin:0 0 6px;font-family:'DM Sans',sans-serif;">Connection Failed</h4>
            <p style="font-size:13px;color:#94a3b8;margin:0 0 12px;font-family:'DM Sans',sans-serif;">
                {if strpos($error, 'Could not connect') !== false}Unable to establish connection with GenieACS server
                {elseif strpos($error, 'port') !== false}Server is not responding on the configured port
                {else}{$error}{/if}
            </p>
            <div class="gd-error-detail">
                <i class="ph ph-hard-drives" style="font-size:13px;"></i>
                <span style="font-family:'DM Mono',monospace;font-size:11px;">{$current_server->host}:{$current_server->port}</span>
            </div>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
                <button class="bk-btn bk-btn-danger" onclick="location.reload()">
                    <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Retry Connection
                </button>
                <button class="bk-btn bk-btn-primary" onclick="window.location.href='{$_url}plugin/genieacs_manager'">
                    <i class="ph ph-wrench" style="font-size:13px;"></i> Configure Server
                </button>
            </div>
        </div>
        {/if}

    </div>

    <!-- ===================== PAGINATION ===================== -->
    <div class="gd-pagination">
        <div class="gd-pag-info">
            {if $devices}
                {assign var="pag_start" value=(($current_page-1)*10)+1}
                {assign var="pag_end"   value=min($current_page*10, $total_devices)}
                Showing {$pag_start}–{$pag_end} of {$total_devices} devices
                {if $search_term}<span style="color:#0271c6;font-weight:600;"> · "{$search_term}"</span>{/if}
            {else}
                {if $search_term}No results for "{$search_term}"{else}No devices found{/if}
            {/if}
        </div>

        <div class="gd-pag-right">
            {if isset($sync_warning)}
                <div class="gd-sync-warn">
                    <i class="ph ph-warning" style="font-size:13px;color:#d97706;"></i>
                    {$sync_warning}
                    <button class="bk-btn bk-btn-warning bk-btn-sm" onclick="forceSync()" style="height:26px;padding:0 10px;font-size:11px;">
                        <i class="ph ph-arrow-clockwise" style="font-size:11px;"></i> Sync Now
                    </button>
                </div>
            {/if}

            {if $total_pages > 1}
                {assign var="sp" value=""}
                {if $search_term}{assign var="sp" value="&search="|cat:$search_term|urlencode}{/if}
                {if $status_filter}{assign var="sp" value=$sp|cat:"&status="|cat:$status_filter}{/if}
                {if $rx_power_filter}{assign var="sp" value=$sp|cat:"&rx_power="|cat:$rx_power_filter}{/if}
                {if $location_filter}{assign var="sp" value=$sp|cat:"&location="|cat:$location_filter|urlencode}{/if}

                <div class="gd-pag-btns" id="pagination-controls">
                    {if $current_page > 1}
                        <a href="index.php?_route=plugin/genieacs_devices/list/1{$sp}" class="gd-pag-btn" title="First">
                            <i class="ph ph-caret-double-left" style="font-size:11px;"></i>
                        </a>
                        <a href="index.php?_route=plugin/genieacs_devices/list/{$current_page-1}{$sp}" class="gd-pag-btn" title="Previous">
                            <i class="ph ph-caret-left" style="font-size:11px;"></i>
                        </a>
                    {else}
                        <span class="gd-pag-btn disabled"><i class="ph ph-caret-double-left" style="font-size:11px;"></i></span>
                        <span class="gd-pag-btn disabled"><i class="ph ph-caret-left" style="font-size:11px;"></i></span>
                    {/if}

                    {for $i=max(1,$current_page-2) to min($total_pages,$current_page+2)}
                        {if $i == $current_page}
                            <span class="gd-pag-btn active">{$i}</span>
                        {else}
                            <a href="index.php?_route=plugin/genieacs_devices/list/{$i}{$sp}" class="gd-pag-btn">{$i}</a>
                        {/if}
                    {/for}

                    {if $current_page < $total_pages}
                        <a href="index.php?_route=plugin/genieacs_devices/list/{$current_page+1}{$sp}" class="gd-pag-btn" title="Next">
                            <i class="ph ph-caret-right" style="font-size:11px;"></i>
                        </a>
                        <a href="index.php?_route=plugin/genieacs_devices/list/{$total_pages}{$sp}" class="gd-pag-btn" title="Last">
                            <i class="ph ph-caret-double-right" style="font-size:11px;"></i>
                        </a>
                    {else}
                        <span class="gd-pag-btn disabled"><i class="ph ph-caret-right" style="font-size:11px;"></i></span>
                        <span class="gd-pag-btn disabled"><i class="ph ph-caret-double-right" style="font-size:11px;"></i></span>
                    {/if}
                </div>
            {/if}
        </div>
    </div>

</div>

<!-- ===================== CUSTOM MODALS ===================== -->

<!-- Modal Overlay (shared) -->
<div id="gdModalOverlay" style="position:fixed;inset:0;z-index:9995;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px;">
    <div id="gdModalCard" style="background:white;border-radius:16px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);display:flex;flex-direction:column;animation:gdModalIn 0.25s cubic-bezier(0.34,1.56,0.64,1);">
        <div id="gdModalHeader" style="padding:18px 22px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div id="gdModalTitle" style="font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;display:flex;align-items:center;gap:8px;color:white;"></div>
            <button id="gdModalClosebtn" onclick="gdCloseModal()" style="width:30px;height:30px;border-radius:8px;border:none;background:rgba(255,255,255,0.2);color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background 0.15s;flex-shrink:0;">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div id="gdModalBody" style="padding:22px;font-family:'DM Sans',sans-serif;"></div>
        <div id="gdModalFooter" style="padding:14px 22px;border-top:1px solid #f1f5f9;display:flex;gap:8px;justify-content:flex-end;background:#fafcff;flex-shrink:0;"></div>
    </div>
</div>

<style>
{literal}
@keyframes gdModalIn { from{opacity:0;transform:translateY(-12px) scale(0.98)} to{opacity:1;transform:translateY(0) scale(1)} }
@keyframes gdSpin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
@keyframes gdSlideUp { from{transform:translateY(100%);opacity:0.6} to{transform:translateY(0);opacity:1} }
@keyframes gdCheckCircle { from{stroke-dashoffset:166} to{stroke-dashoffset:0} }
@keyframes gdCheckMark   { from{stroke-dashoffset:48}  to{stroke-dashoffset:0} }
@keyframes gdCheckScale  { 0%{transform:scale(0.8);opacity:0} 60%{transform:scale(1.1)} 100%{transform:scale(1);opacity:1} }
.gd-modal-spinner { display:inline-block;width:48px;height:48px;border:3px solid #e2e8f0;border-top-color:#0271c6;border-radius:50%;animation:gdSpin 0.7s linear infinite; }
.gd-check-svg { width:72px;height:72px;animation:gdCheckScale 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards; }
.gd-check-circle { fill:none;stroke:#16a34a;stroke-width:4;stroke-dasharray:166;stroke-dashoffset:166;stroke-linecap:round;animation:gdCheckCircle 0.5s ease-in-out 0.1s forwards; }
.gd-check-mark   { fill:none;stroke:#16a34a;stroke-width:5;stroke-dasharray:48;stroke-dashoffset:48;stroke-linecap:round;stroke-linejoin:round;animation:gdCheckMark 0.35s ease-in-out 0.5s forwards; }
.gd-progress-bar { height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;margin:12px 0; }
.gd-progress-fill { height:100%;border-radius:4px;background:linear-gradient(90deg,#0271c6,#4f46e5);transition:width 0.3s ease; }
.gd-stat-row { display:flex;justify-content:center;gap:16px;margin-top:16px; }
.gd-stat-box { padding:12px 24px;border-radius:10px;text-align:center; }
.gd-stat-box span { display:block; }
.gd-stat-box .num { font-size:24px;font-weight:700; }
.gd-stat-box .lbl { font-size:11px;margin-top:2px; }
@media(max-width:767px) {
    #gdModalOverlay { align-items:flex-end !important; padding:0 !important; }
    #gdModalCard { border-radius:20px 20px 0 0 !important; max-width:100% !important; animation:gdSlideUp 0.32s cubic-bezier(0.32,0.72,0,1) !important; }
    #gdModalFooter { flex-direction:column-reverse; }
    #gdModalFooter .bk-btn { width:100%;justify-content:center; }
}
{/literal}
</style>

<!-- ===================== JAVASCRIPT ===================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
var gdBaseUrl = '{$_url}';
{literal}
// ===================== MODAL ENGINE =====================
var gdModalCallback = null;

function gdOpenModal(opts) {
    // opts: { title, titleIcon, headerColor, body, footer, closable }
    var overlay = document.getElementById('gdModalOverlay');
    var header  = document.getElementById('gdModalHeader');
    var title   = document.getElementById('gdModalTitle');
    var body    = document.getElementById('gdModalBody');
    var footer  = document.getElementById('gdModalFooter');
    var closeBtn= document.getElementById('gdModalClosebtn');

    header.style.background = opts.headerColor || 'linear-gradient(135deg,#0271c6,#0359a0)';
    title.innerHTML = (opts.titleIcon ? '<i class="' + opts.titleIcon + '"></i> ' : '') + (opts.title || '');
    body.innerHTML  = opts.body   || '';
    footer.innerHTML= opts.footer || '';

    closeBtn.style.display = opts.closable === false ? 'none' : 'flex';
    overlay.style.display  = 'flex';
    document.body.style.overflow = 'hidden';
}

function gdCloseModal() {
    document.getElementById('gdModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
    gdModalCallback = null;
}

// Close on overlay click
document.getElementById('gdModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) gdCloseModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') gdCloseModal();
});

// ===================== LOADING MODAL =====================
function gdShowLoading(title, subtitle) {
    gdOpenModal({
        title: title || 'Mohon Tunggu...',
        titleIcon: 'ph ph-circle-notch',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        closable: false,
        body: '<div style="text-align:center;padding:16px 0;">' +
              '<div class="gd-modal-spinner" style="margin:0 auto 16px;"></div>' +
              '<p style="color:#64748b;font-size:13px;margin:0;">' + (subtitle || 'Memproses permintaan...') + '</p>' +
              '</div>',
        footer: ''
    });
}

// ===================== SELECT ALL =====================
function toggleSelectAll() {
    var isChecked = $('#selectAll').prop('checked');
    $('.device-checkbox:visible').prop('checked', isChecked);
    updateRefreshButton();
}

function updateRefreshButton() {
    var checkedDevices = $('.device-checkbox:checked:visible');
    var button = $('#refreshButton');
    var buttonText = $('#refreshButtonText');
    if (checkedDevices.length > 0) {
        buttonText.text('Refresh Selected (' + checkedDevices.length + ')');
        button.removeClass('bk-btn-danger').addClass('bk-btn-primary');
    } else {
        buttonText.text('Refresh Offline');
        button.removeClass('bk-btn-primary').addClass('bk-btn-danger');
    }
}

// ===================== REFRESH DEVICES =====================
function refreshDevices() {
    var checkedDevices = $('.device-checkbox:checked:visible');
    var devicesToRefresh = [];

    if (checkedDevices.length > 0) {
        checkedDevices.each(function() {
            devicesToRefresh.push({
                id: $(this).data('device-id'),
                status: $(this).data('device-status')
            });
        });
        gdOpenModal({
            title: 'Refresh Selected',
            titleIcon: 'ph ph-arrows-clockwise',
            headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
            body: '<div style="text-align:center;padding:8px 0;">' +
                  '<div style="width:64px;height:64px;border-radius:16px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#0271c6;">' +
                  '<i class="ph ph-arrows-clockwise"></i></div>' +
                  '<p style="font-size:14px;color:#334155;margin:0;">Refresh <strong style="color:#0271c6;">' + devicesToRefresh.length + '</strong> selected device(s)?</p>' +
                  '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0;">Proses ini akan mengirim perintah refresh ke semua device yang dipilih.</p>' +
                  '</div>',
            footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                    '<button class="bk-btn bk-btn-primary" id="gdRefreshSelectedBtn"><i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh Sekarang</button>'
        });
        document.getElementById('gdRefreshSelectedBtn').addEventListener('click', function() {
            gdCloseModal();
            processDeviceRefresh(devicesToRefresh, 'selected');
        });
    } else {
        var scanTexts = [
            'Menghubungi ACS server...',
            'Memindai status perangkat...',
            'Mengecek koneksi TR-069...',
            'Menganalisa data terakhir...'
        ];

        gdOpenModal({
            title: 'Mengecek Device...',
            titleIcon: 'ph ph-arrows-clockwise',
            headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
            closable: false,
            body: '<div style="text-align:center;padding:8px 0;">' +
                  '<div class="gd-modal-spinner" style="margin:0 auto 20px;"></div>' +
                  '<p id="gdScanText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + scanTexts[0] + '</p>' +
                  '</div>',
            footer: ''
        });

        var scanIdx = 1;
        var scanDone = false;
        var scanAjaxDone = false;
        var scanResult = null;

        function nextScanText() {
            if (scanDone) return;
            if (scanIdx >= scanTexts.length) { scanIdx = 0; }
            var el = document.getElementById('gdScanText');
            if (!el) return;
            el.style['opacity'] = '0';
            setTimeout(function() {
                var e = document.getElementById('gdScanText');
                if (e) { e.textContent = scanTexts[scanIdx]; e.style['opacity'] = '1'; }
                scanIdx++;
                if (!scanDone) setTimeout(nextScanText, 1800);
            }, 300);
        }
        setTimeout(nextScanText, 1800);

        var scanMinDone = false;
        setTimeout(function() { scanMinDone = true; tryShowScanResult(); }, 3500);

        $.ajax({
            url: gdBaseUrl + 'plugin/genieacs_devices/get-offline-devices', type: 'GET', dataType: 'json',
            success: function(response) { scanResult = response; scanAjaxDone = true; tryShowScanResult(); },
            error:   function()         { scanResult = null;     scanAjaxDone = true; tryShowScanResult(); }
        });

        function tryShowScanResult() {
            if (!scanAjaxDone || !scanMinDone) return;
            scanDone = true;
            if (scanResult && scanResult.success && scanResult.devices.length > 0) {
                var offlineDevices = scanResult.devices;
                gdOpenModal({
                    title: 'Refresh Offline',
                    titleIcon: 'ph ph-x-circle',
                    headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
                    body: '<div style="text-align:center;padding:8px 0;">' +
                          '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
                          '<i class="ph ph-x-circle"></i></div>' +
                          '<p style="font-size:14px;color:#334155;margin:0;">Ditemukan <strong style="color:#dc2626;">' + offlineDevices.length + '</strong> device offline</p>' +
                          '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0;">Semua device offline akan di-refresh sekaligus.</p>' +
                          '</div>',
                    footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                            '<button class="bk-btn bk-btn-danger" id="gdRefreshAllBtn"><i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh Semua</button>'
                });
                document.getElementById('gdRefreshAllBtn').addEventListener('click', function() {
                    gdCloseModal();
                    processDeviceRefresh(offlineDevices, 'offline');
                });
            } else if (scanResult && scanResult.success && scanResult.devices.length === 0) {
                var hdr  = document.getElementById('gdModalHeader');
                var ttl  = document.getElementById('gdModalTitle');
                var body = document.getElementById('gdModalBody');
                var ftr  = document.getElementById('gdModalFooter');
                if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
                if (ttl) ttl.innerHTML = '<i class="ph ph-check-circle"></i> Semua Online!';
                if (body) {
                    body.innerHTML =
                        '<div style="text-align:center;padding:8px 0;">' +
                        '<svg class="gd-check-svg" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto 16px;">' +
                        '<circle class="gd-check-circle" cx="26" cy="26" r="24"/>' +
                        '<path class="gd-check-mark" d="M14 26 l8 8 l16 -16"/>' +
                        '</svg>' +
                        '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0;">Semua device sedang online!</p>' +
                        '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0;">Tidak ada device yang perlu di-refresh saat ini.</p>' +
                        '</div>';
                }
                if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-success" onclick="gdCloseModal()"><i class="ph ph-check" style="font-size:13px;"></i> OK</button>';
            } else {
                gdOpenModal({
                    title: 'Gagal',
                    titleIcon: 'ph ph-warning',
                    headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
                    body: '<div style="text-align:center;padding:8px 0;">' +
                          '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
                          '<i class="ph ph-warning"></i></div>' +
                          '<p style="font-size:14px;color:#334155;margin:0;">Gagal mengambil daftar device</p>' +
                          '</div>',
                    footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()">Tutup</button>'
                });
            }
        }
    }
}

// ===================== PROCESS REFRESH =====================
function processDeviceRefresh(devices, mode) {
    var currentIndex = 0;
    var successCount = 0;
    var failCount = 0;
    var totalDevices = devices.length;

    function renderProgress() {
        var pct = totalDevices > 0 ? Math.round((currentIndex / totalDevices) * 100) : 0;
        document.getElementById('gdModalBody').innerHTML =
            '<div style="padding:4px 0;">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">' +
            '<span style="font-size:13px;font-weight:600;color:#0f172a;">Memproses device...</span>' +
            '<span style="font-size:12px;color:#64748b;font-family:\'DM Mono\',monospace;">' + currentIndex + ' / ' + totalDevices + '</span>' +
            '</div>' +
            '<div class="gd-progress-bar"><div class="gd-progress-fill" id="gdProgressFill" style="width:' + pct + '%;"></div></div>' +
            '<div style="display:flex;justify-content:space-between;margin-top:4px;">' +
            '<span style="font-size:11px;color:#64748b;">' + pct + '%</span>' +
            '</div>' +
            '<div style="display:flex;gap:10px;margin-top:14px;">' +
            '<div style="flex:1;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;text-align:center;">' +
            '<div style="font-size:20px;font-weight:700;color:#16a34a;" id="gdSuccCount">' + successCount + '</div>' +
            '<div style="font-size:11px;color:#16a34a;font-weight:600;">Berhasil</div></div>' +
            '<div style="flex:1;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:10px;text-align:center;">' +
            '<div style="font-size:20px;font-weight:700;color:#dc2626;" id="gdFailCount">' + failCount + '</div>' +
            '<div style="font-size:11px;color:#dc2626;font-weight:600;">Gagal</div></div>' +
            '</div></div>';
    }

    gdOpenModal({
        title: 'Refreshing Devices',
        titleIcon: 'ph ph-arrows-clockwise',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        closable: false,
        body: '',
        footer: ''
    });
    renderProgress();

    function processNext() {
        if (currentIndex >= totalDevices) {
            // Complete modal
            gdOpenModal({
                title: 'Refresh Selesai',
                titleIcon: 'ph ph-check-circle',
                headerColor: 'linear-gradient(135deg,#16a34a,#15803d)',
                closable: false,
                body: '<div style="text-align:center;padding:8px 0;">' +
                      '<div style="width:64px;height:64px;border-radius:16px;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;color:#16a34a;">' +
                      '<i class="ph ph-check-circle"></i></div>' +
                      '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 16px;">Proses refresh selesai</p>' +
                      '<div style="display:flex;gap:12px;justify-content:center;">' +
                      '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 24px;text-align:center;">' +
                      '<div style="font-size:28px;font-weight:700;color:#16a34a;">' + successCount + '</div>' +
                      '<div style="font-size:11px;color:#16a34a;font-weight:600;">Berhasil</div></div>' +
                      '<div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 24px;text-align:center;">' +
                      '<div style="font-size:28px;font-weight:700;color:#dc2626;">' + failCount + '</div>' +
                      '<div style="font-size:11px;color:#dc2626;font-weight:600;">Gagal</div></div>' +
                      '</div></div>',
                footer: '<button class="bk-btn bk-btn-primary" onclick="gdCloseModal();location.reload();"><i class="ph ph-check" style="font-size:13px;"></i> Selesai</button>'
            });
            return;
        }
        var device = devices[currentIndex];
        renderProgress();
        $.ajax({
            url: gdBaseUrl + 'plugin/genieacs_devices/refresh', type: 'GET', data: { device_id: device.id }, dataType: 'json', timeout: 30000,
            success: function(r) { if (r && r.success) { successCount++; } else { failCount++; } currentIndex++; processNext(); },
            error: function() { failCount++; currentIndex++; processNext(); }
        });
    }
    processNext();
}

// ===================== FORCE SYNC =====================
function forceSync() {
    gdOpenModal({
        title: 'Force Sync',
        titleIcon: 'ph ph-arrow-clockwise',
        headerColor: 'linear-gradient(135deg,#d97706,#b45309)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#fef3c7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#d97706;">' +
              '<i class="ph ph-arrow-clockwise"></i></div>' +
              '<p style="font-size:14px;color:#334155;margin:0;font-weight:600;">Sinkronisasi semua device?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0;">Proses ini akan mengambil ulang semua data device dari ACS server.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-warning" onclick="gdCloseModal();gdDoForceSync()"><i class="ph ph-arrow-clockwise" style="font-size:13px;"></i> Ya, Sync Sekarang</button>'
    });
}

function gdDoForceSync() {
    var texts = [
        'Menghubungi ACS server...',
        'Mengambil daftar perangkat...',
        'Memvalidasi data TR-069...',
        'Menyinkronkan parameter ONU...',
        'Menyimpan perubahan ke database...'
    ];

    gdOpenModal({
        title: 'Sinkronisasi...',
        titleIcon: 'ph ph-arrow-clockwise',
        headerColor: 'linear-gradient(135deg,#d97706,#b45309)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="gd-modal-spinner" style="border-top-color:#d97706;margin:0 auto 20px;"></div>' +
              '<p id="gdSyncText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });

    var idx = 1;
    var syncDone = false;
    var ajaxDone = false;
    var ajaxResult = null;

    function nextText() {
        if (syncDone) return;
        if (idx >= texts.length) { idx = 0; }
        var el = document.getElementById('gdSyncText');
        if (!el) return;
        el.style['opacity'] = '0';
        setTimeout(function() {
            var e = document.getElementById('gdSyncText');
            if (e) { e.textContent = texts[idx]; e.style['opacity'] = '1'; }
            idx++;
            if (!syncDone) setTimeout(nextText, 1800);
        }, 300);
    }
    setTimeout(nextText, 1800);

    var minDone = false;
    setTimeout(function() { minDone = true; tryShowResult(); }, 4000);

    $.ajax({
        url: 'index.php?_route=plugin/genieacs_devices/force-sync', type: 'GET', dataType: 'json', timeout: 60000,
        success: function(r) { ajaxResult = r; ajaxDone = true; tryShowResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryShowResult(); }
    });

    function tryShowResult() {
        if (!ajaxDone || !minDone) return;
        syncDone = true;
        showSyncResult();
    }

    function showSyncResult() {
        if (!ajaxDone) return;
        if (ajaxResult && ajaxResult.success) {
            var hdr  = document.getElementById('gdModalHeader');
            var ttl  = document.getElementById('gdModalTitle');
            var body = document.getElementById('gdModalBody');
            var ftr  = document.getElementById('gdModalFooter');
            if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
            if (ttl) ttl.innerHTML = '<i class="ph ph-check-circle"></i> Sync Berhasil!';
            if (body) {
                body.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<svg class="gd-check-svg" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto 16px;">' +
                    '<circle class="gd-check-circle" cx="26" cy="26" r="24"/>' +
                    '<path class="gd-check-mark" d="M14 26 l8 8 l16 -16"/>' +
                    '</svg>' +
                    '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0;">Semua device berhasil disinkronisasi!</p>' +
                    '</div>';
            }
            if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-success" onclick="gdCloseModal();location.reload();"><i class="ph ph-check" style="font-size:13px;"></i> OK</button>';
        } else {
            gdOpenModal({
                title: 'Sync Gagal',
                titleIcon: 'ph ph-warning',
                headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
                body: '<div style="text-align:center;padding:8px 0;">' +
                      '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
                      '<i class="ph ph-warning"></i></div>' +
                      '<p style="font-size:14px;color:#334155;margin:0;">' + ((ajaxResult && ajaxResult.error) || 'Gagal berkomunikasi dengan server') + '</p>' +
                      '</div>',
                footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()">Tutup</button>'
            });
        }
    }
}

// ===================== SUMMON =====================
function summonDevice(deviceId) {
    gdOpenModal({
        title: 'Summon Device?',
        titleIcon: 'ph ph-bell-ringing',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#0271c6;">' +
              '<i class="ph ph-bell-ringing"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Summon perangkat ini?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Perangkat akan dipanggil untuk melakukan koneksi TR-069 ke ACS server.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="gdCloseModal();executeSummonSequence(\'' + deviceId + '\')"><i class="ph ph-bell-ringing" style="font-size:13px;"></i> Ya, Summon</button>'
    });
}

function executeSummonSequence(deviceId) {
    var texts = [
        'Mengirim sinyal ke perangkat...',
        'Menunggu respons dari ONU...',
        'Mengautentikasi sesi TR-069...'
    ];

    gdOpenModal({
        title: 'Summoning...',
        titleIcon: 'ph ph-bell-ringing',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="gd-modal-spinner" style="margin:0 auto 20px;"></div>' +
              '<p id="gdSummonText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });

    var ajaxDone = false;
    var ajaxResult = null;
    var animDone = false;

    $.ajax({
        url: gdBaseUrl + 'plugin/genieacs_devices/summon',
        type: 'GET', data: { device_id: deviceId }, dataType: 'json', timeout: 15000,
        success: function(r) { ajaxResult = r; ajaxDone = true; tryShowSummonResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryShowSummonResult(); }
    });

    var idx = 1;
    function nextText() {
        if (idx >= texts.length) {
            animDone = true;
            tryShowSummonResult();
            return;
        }
        var el = document.getElementById('gdSummonText');
        if (!el) return;
        el.style['opacity'] = '0';
        setTimeout(function() {
            var e = document.getElementById('gdSummonText');
            if (e) { e.textContent = texts[idx]; e.style['opacity'] = '1'; }
            idx++;
            setTimeout(nextText, 1600);
        }, 300);
    }
    setTimeout(nextText, 1600);

    function tryShowSummonResult() {
        if (!ajaxDone || !animDone) return;
        var hdr  = document.getElementById('gdModalHeader');
        var ttl  = document.getElementById('gdModalTitle');
        var body = document.getElementById('gdModalBody');
        var ftr  = document.getElementById('gdModalFooter');
        if (ajaxResult && ajaxResult.success) {
            if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
            if (ttl) ttl.innerHTML = '<i class="ph ph-check-circle"></i> Summon Berhasil!';
            if (body) {
                body.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<svg class="gd-check-svg" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto 16px;">' +
                    '<circle class="gd-check-circle" cx="26" cy="26" r="24"/>' +
                    '<path class="gd-check-mark" d="M14 26 l8 8 l16 -16"/>' +
                    '</svg>' +
                    '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px;">Perangkat berhasil di-summon!</p>' +
                    '<p style="font-size:12px;color:#64748b;margin:0;">Memuat ulang halaman...</p>' +
                    '</div>';
            }
            setTimeout(function() { window.location.reload(true); }, 2000);
        } else {
            if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#dc2626,#b91c1c)';
            if (ttl) ttl.innerHTML = '<i class="ph ph-warning"></i> Summon Gagal!';
            if (body) {
                body.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
                    '<i class="ph ph-warning"></i></div>' +
                    '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Perangkat tidak dapat dihubungi!</p>' +
                    '<p style="font-size:12px;color:#94a3b8;margin:0;">' + deviceId + ': ' + ((ajaxResult && ajaxResult.error) ? ajaxResult.error.replace('Failed to send connection request: ', '') : 'Perangkat offline atau tidak dapat dijangkau.') + '</p>' +
                    '</div>';
            }
            if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Tutup</button>';
        }
    }
}


function executeAutoRefresh(deviceId) {
    // Update langsung elemen modal yang sudah ada — JANGAN buka modal baru
    var hdr = document.getElementById('gdModalHeader');
    var ttl = document.getElementById('gdModalTitle');
    var body = document.getElementById('gdModalBody');
    if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
    if (ttl) ttl.innerHTML = '<i class="ph ph-arrows-clockwise"></i> Mengambil Data...';
    if (body) {
        body.innerHTML =
            '<div style="text-align:center;padding:8px 0;">' +
            '<div class="gd-modal-spinner" style="margin:0 auto 20px;"></div>' +
            '<p style="font-size:13px;color:#64748b;margin:0;">Menarik semua parameter dari perangkat...</p>' +
            '</div>';
    }

    $.ajax({
        url: 'index.php?_route=plugin/genieacs_devices/refresh', type: 'GET', data: { device_id: deviceId }, dataType: 'json',
        success: function(r) {
            var hdr2 = document.getElementById('gdModalHeader');
            var ttl2 = document.getElementById('gdModalTitle');
            var body2 = document.getElementById('gdModalBody');
            if (r && r.success) {
                if (hdr2) hdr2.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
                if (ttl2) ttl2.innerHTML = '<i class="ph ph-check-circle"></i> Sinkronisasi Selesai!';
                if (body2) {
                    body2.innerHTML =
                        '<div style="text-align:center;padding:8px 0;">' +
                        '<div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;border:3px solid #16a34a;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:30px;color:#16a34a;animation:gdModalIn 0.4s cubic-bezier(0.34,1.56,0.64,1);">' +
                        '<i class="ph ph-check-bold"></i></div>' +
                        '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px;">Semua data berhasil disinkronisasi!</p>' +
                        '<p style="font-size:12px;color:#64748b;margin:0;">Memuat ulang halaman...</p>' +
                        '</div>';
                }
                setTimeout(function() { window.location.reload(true); }, 1500);
            } else {
                if (hdr2) hdr2.style['background'] = 'linear-gradient(135deg,#d97706,#b45309)';
                if (ttl2) ttl2.innerHTML = '<i class="ph ph-warning"></i> Gagal';
                if (body2) {
                    body2.innerHTML =
                        '<div style="text-align:center;padding:8px 0;">' +
                        '<div style="width:64px;height:64px;border-radius:16px;background:#fef3c7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#d97706;">' +
                        '<i class="ph ph-warning"></i></div>' +
                        '<p style="font-size:14px;color:#334155;margin:0;">Gagal mengambil data. Halaman akan dimuat ulang...</p>' +
                        '</div>';
                }
                setTimeout(function() { window.location.reload(true); }, 1500);
            }
        },
        error: function() {
            var hdr3 = document.getElementById('gdModalHeader');
            var ttl3 = document.getElementById('gdModalTitle');
            var body3 = document.getElementById('gdModalBody');
            if (hdr3) hdr3.style['background'] = 'linear-gradient(135deg,#d97706,#b45309)';
            if (ttl3) ttl3.innerHTML = '<i class="ph ph-warning"></i> Koneksi Terputus';
            if (body3) {
                body3.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<div style="width:64px;height:64px;border-radius:16px;background:#fef3c7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#d97706;">' +
                    '<i class="ph ph-warning"></i></div>' +
                    '<p style="font-size:14px;color:#334155;margin:0;">Koneksi ke server terputus. Memuat ulang...</p>' +
                    '</div>';
            }
            setTimeout(function() { window.location.reload(true); }, 1500);
        }
    });
}

// ===================== REBOOT =====================
function rebootDevice(deviceId) {
    gdOpenModal({
        title: 'Reboot Device?',
        titleIcon: 'ph ph-power',
        headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
              '<i class="ph ph-power"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Reboot perangkat ini?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Perangkat akan restart. Koneksi akan terputus sementara.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-danger" onclick="gdCloseModal();gdDoReboot(\'' + deviceId + '\')"><i class="ph ph-power" style="font-size:13px;"></i> Ya, Reboot</button>'
    });
}

function gdDoReboot(deviceId) {
    var texts = [
        'Mengirim perintah reboot...',
        'Memutus koneksi perangkat...',
        'Menunggu perangkat restart...'
    ];

    gdOpenModal({
        title: 'Rebooting...',
        titleIcon: 'ph ph-power',
        headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="gd-modal-spinner" style="border-top-color:#dc2626;margin:0 auto 20px;"></div>' +
              '<p id="gdRebootText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });

    var idx = 1;
    var rebootDone = false;
    var ajaxDone = false;
    var ajaxResult = null;

    function nextRebootText() {
        if (rebootDone) return;
        if (idx >= texts.length) { idx = 0; }
        var el = document.getElementById('gdRebootText');
        if (!el) return;
        el.style['opacity'] = '0';
        setTimeout(function() {
            var e = document.getElementById('gdRebootText');
            if (e) { e.textContent = texts[idx]; e.style['opacity'] = '1'; }
            idx++;
            if (!rebootDone) setTimeout(nextRebootText, 1600);
        }, 300);
    }
    setTimeout(nextRebootText, 1600);

    var minDone = false;
    setTimeout(function() { minDone = true; tryShowRebootResult(); }, 3000);

    $.ajax({
        url: 'index.php?_route=plugin/genieacs_devices/reboot', type: 'GET', data: { device_id: deviceId }, dataType: 'json',
        success: function(r) { ajaxResult = r; ajaxDone = true; tryShowRebootResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryShowRebootResult(); }
    });

    function tryShowRebootResult() {
        if (!ajaxDone || !minDone) return;
        rebootDone = true;
        var hdr  = document.getElementById('gdModalHeader');
        var ttl  = document.getElementById('gdModalTitle');
        var body = document.getElementById('gdModalBody');
        var ftr  = document.getElementById('gdModalFooter');
        if (ajaxResult && ajaxResult.success) {
            if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#16a34a,#15803d)';
            if (ttl) ttl.innerHTML = '<i class="ph ph-check-circle"></i> Reboot Berhasil!';
            if (body) {
                body.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<svg class="gd-check-svg" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto 16px;">' +
                    '<circle class="gd-check-circle" cx="26" cy="26" r="24"/>' +
                    '<path class="gd-check-mark" d="M14 26 l8 8 l16 -16"/>' +
                    '</svg>' +
                    '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px;">Perintah reboot berhasil dikirim!</p>' +
                    '<p style="font-size:12px;color:#64748b;margin:0;">Perangkat sedang melakukan restart.</p>' +
                    '</div>';
            }
            if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-success" onclick="gdCloseModal()"><i class="ph ph-check" style="font-size:13px;"></i> OK</button>';
        } else {
            if (hdr) hdr.style['background'] = 'linear-gradient(135deg,#dc2626,#b91c1c)';
            if (ttl) ttl.innerHTML = '<i class="ph ph-warning"></i> Reboot Gagal';
            if (body) {
                body.innerHTML =
                    '<div style="text-align:center;padding:8px 0;">' +
                    '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
                    '<i class="ph ph-warning"></i></div>' +
                    '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Perangkat tidak dapat dihubungi!</p>' +
                    '<p style="font-size:12px;color:#94a3b8;margin:0;">' + deviceId + ': ' + ((ajaxResult && ajaxResult.error) ? ajaxResult.error.replace('Failed to send connection request: ', '') : 'Perangkat offline atau tidak dapat dijangkau.') + '</p>' +
                    '</div>';
            }
            if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-ghost" onclick="gdCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Tutup</button>';
        }
    }
}

// ===================== CHANGE SERVER =====================
function changeServer() {
    var serverId = $('#quickServerSwitch').val() || $('#serverSelector').val();
    gdShowLoading('Switching Server...', 'Menghubungkan ke server baru...');
    $.ajax({
        url: 'index.php?_route=plugin/genieacs_devices/set-server', type: 'POST', data: { server_id: serverId }, dataType: 'json',
        success: function(r) { window.location.href = 'index.php?_route=plugin/genieacs_devices'; },
        error:   function()  { window.location.href = 'index.php?_route=plugin/genieacs_devices'; }
    });
}

// ===================== MISC =====================
function viewDeviceDetail(deviceId) { window.location.href = 'index.php?_route=plugin/genieacs_device_detail/' + deviceId; }
function clearSearch() { $('#searchInput').val(''); }
function clearAllFilters() {
    $('#ajaxSearchInput').val(''); $('#searchInput').val('');
    $('#statusFilter').val(''); $('#rxPowerFilter').val('');
    if ($('#locationFilter').length) $('#locationFilter').val('');
    window.location.href = 'index.php?_route=plugin/genieacs_devices';
}

function animateCountUp() {
    $('.count-up').each(function() {
        var $this = $(this), target = parseInt($this.data('target')), duration = 1500, steps = 60,
            stepDuration = duration / steps, increment = target / steps, current = 0;
        var timer = setInterval(function() {
            current += increment;
            if (current >= target) { current = target; clearInterval(timer); }
            $this.text(Math.floor(current));
        }, stepDuration);
    });
    setTimeout(function() { $('.gd-mini-fill').css('transition','width 1.5s ease-in-out'); }, 100);
}

// Row click → detail
$(document).on('click', '#deviceTable tbody tr', function(e) {
    if ($(e.target).closest('.device-checkbox, .gd-action-btn').length) return;
    if (window.getSelection().toString().length > 0) return;
    var deviceId = $(this).find('.device-checkbox').data('device-id');
    if (deviceId) window.location.href = 'index.php?_route=plugin/genieacs_device_detail/' + deviceId;
});

// AJAX search
var searchTimeout;
$('#ajaxSearchInput').on('input', function() {
    clearTimeout(searchTimeout);
    var term = $(this).val();
    searchTimeout = setTimeout(function() {
        if (term.length >= 2) {
            $('#searchStatus').text('Searching...');
            $.ajax({ url: 'index.php?_route=plugin/genieacs_devices/ajax-search', type: 'GET', data: { q: term, page: 1 }, dataType: 'json',
                success: function(r) {
                    if (r.success) { updatePagination(r.page, r.total_pages, term); $('#searchStatus').text(r.total + ' results'); }
                }
            });
        } else if (term.length === 0) { $('#searchStatus').text(''); }
    }, 400);
});

function updatePagination(currentPage, totalPages, searchTerm) {
    var html = '';
    if (currentPage > 1) {
        html += '<a href="javascript:void(0)" onclick="performAjaxSearchPage(\'' + searchTerm + '\',1)" class="gd-pag-btn"><i class="ph ph-caret-double-left" style="font-size:11px;"></i></a>';
        html += '<a href="javascript:void(0)" onclick="performAjaxSearchPage(\'' + searchTerm + '\',' + (currentPage-1) + ')" class="gd-pag-btn"><i class="ph ph-caret-left" style="font-size:11px;"></i></a>';
    } else {
        html += '<span class="gd-pag-btn disabled"><i class="ph ph-caret-double-left" style="font-size:11px;"></i></span>';
        html += '<span class="gd-pag-btn disabled"><i class="ph ph-caret-left" style="font-size:11px;"></i></span>';
    }
    for (var i = Math.max(1,currentPage-2); i <= Math.min(totalPages,currentPage+2); i++) {
        if (i === currentPage) html += '<span class="gd-pag-btn active">' + i + '</span>';
        else html += '<a href="javascript:void(0)" onclick="performAjaxSearchPage(\'' + searchTerm + '\',' + i + ')" class="gd-pag-btn">' + i + '</a>';
    }
    if (currentPage < totalPages) {
        html += '<a href="javascript:void(0)" onclick="performAjaxSearchPage(\'' + searchTerm + '\',' + (currentPage+1) + ')" class="gd-pag-btn"><i class="ph ph-caret-right" style="font-size:11px;"></i></a>';
        html += '<a href="javascript:void(0)" onclick="performAjaxSearchPage(\'' + searchTerm + '\',' + totalPages + ')" class="gd-pag-btn"><i class="ph ph-caret-double-right" style="font-size:11px;"></i></a>';
    } else {
        html += '<span class="gd-pag-btn disabled"><i class="ph ph-caret-right" style="font-size:11px;"></i></span>';
        html += '<span class="gd-pag-btn disabled"><i class="ph ph-caret-double-right" style="font-size:11px;"></i></span>';
    }
    $('#pagination-controls').html(html);
}

function performAjaxSearchPage(searchTerm, page) {
    $.ajax({ url: 'index.php?_route=plugin/genieacs_devices/ajax-search', type: 'GET', data: { q: searchTerm, page: page }, dataType: 'json',
        success: function(r) { if (r.success) { updatePagination(r.page, r.total_pages, searchTerm); } }
    });
}

$(document).ready(function() { animateCountUp(); });
{/literal}
</script>

{include file="sections/footer.tpl"}
