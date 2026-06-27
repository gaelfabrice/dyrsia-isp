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

<!-- ===================== SELECT2 OVERRIDE (match backup.tpl) ===================== -->
<style>
{literal}
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        height: 36px !important; border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important; background: #f8fafc !important;
        display: flex !important; align-items: center !important;
        padding: 0 36px 0 10px !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0271c6 !important;
        box-shadow: 0 0 0 3px rgba(2,113,198,0.12) !important;
        background: #fff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important; font-size: 13px !important;
        font-family: 'DM Sans', sans-serif !important;
        font-weight: 400 !important; line-height: 36px !important; padding: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        width: 36px !important; height: 36px !important;
        top: 0 !important; right: 0 !important;
        display: flex !important; align-items: center !important; justify-content: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b { display: none !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow::after {
        content: '' !important; display: block !important; width: 16px !important; height: 16px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256'%3E%3Cpath fill='%2364748b' d='M213.66 101.66l-80 80a8 8 0 0 1-11.32 0l-80-80a8 8 0 0 1 11.32-11.32L128 164.69l74.34-74.35a8 8 0 0 1 11.32 11.32z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-size: contain !important; transition: transform 0.2s !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow::after {
        transform: rotate(180deg) !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256'%3E%3Cpath fill='%230271c6' d='M213.66 101.66l-80 80a8 8 0 0 1-11.32 0l-80-80a8 8 0 0 1 11.32-11.32L128 164.69l74.34-74.35a8 8 0 0 1 11.32 11.32z'/%3E%3C/svg%3E") !important;
    }
    .select2-container--default .select2-dropdown {
        border: 1px solid #e2e8f0 !important; border-radius: 12px !important;
        box-shadow: 0 8px 24px -4px rgba(0,0,0,0.12), 0 2px 8px -2px rgba(0,0,0,0.06) !important;
        margin-top: 6px !important; overflow: hidden !important; background: #fff !important;
    }
    .select2-container--default .select2-results__options {
        max-height: 220px !important; padding: 6px !important; overflow-y: auto !important;
    }
    .select2-container--default .select2-results__option {
        padding: 9px 12px !important; font-size: 13px !important;
        font-family: 'DM Sans', sans-serif !important; font-weight: 500 !important;
        color: #334155 !important; border-radius: 8px !important; margin: 1px 0 !important;
        transition: background 0.12s !important; cursor: pointer !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: #eff6ff !important; color: #0271c6 !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background: #dbeafe !important; color: #0271c6 !important; font-weight: 600 !important;
    }

    /* ===================== GCS COMPONENT LIBRARY ===================== */
    /* Reuse bk-* classes from backup.tpl for full consistency */

    .bk-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; font-family: 'DM Sans', sans-serif; margin-bottom: 16px; }
    .bk-card-header { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; background: #fafcff; }
    .bk-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bk-card-title { font-size: 13px; font-weight: 700; color: #0f172a; }
    .bk-card-subtitle { font-size: 11px; color: #94a3b8; }
    .bk-card-body { padding: 18px; }
    .bk-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 5px; }
    .bk-help { font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.5; }
    .bk-help a { color: #0271c6; font-weight: 600; text-decoration: none; }
    .bk-input { width: 100%; height: 36px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f172a; background: #f8fafc; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
    .bk-input:focus { border-color: #0271c6; background: #fff; box-shadow: 0 0 0 3px rgba(2,113,198,0.10); }
    .bk-input::placeholder { color: #cbd5e1; }
    .bk-select { width: 100%; height: 36px; padding: 0 32px 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f172a; background: #f8fafc; outline: none; cursor: pointer; box-sizing: border-box; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256'%3E%3Cpath fill='%2364748b' d='M213.66 101.66l-80 80a8 8 0 0 1-11.32 0l-80-80a8 8 0 0 1 11.32-11.32L128 164.69l74.34-74.35a8 8 0 0 1 11.32 11.32z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; transition: border-color 0.15s, box-shadow 0.15s; }
    .bk-select:focus { border-color: #0271c6; background-color: #fff; box-shadow: 0 0 0 3px rgba(2,113,198,0.10); }
    .bk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; white-space: nowrap; box-sizing: border-box; }
    .bk-btn-primary { background: #0271c6; color: white; box-shadow: 0 2px 8px rgba(2,113,198,0.25); }
    .bk-btn-primary:hover { background: #0359a0; color: white; text-decoration: none; }
    .bk-btn-success { background: #16a34a; color: white; box-shadow: 0 2px 8px rgba(22,163,74,0.25); }
    .bk-btn-success:hover { background: #15803d; color: white; text-decoration: none; }
    .bk-btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .bk-btn-danger:hover { background: #fecaca; color: #dc2626; text-decoration: none; }
    .bk-btn-warning { background: #eff6ff; color: #0271c6; border: 1px solid #bfdbfe; }
    .bk-btn-warning:hover { background: #dbeafe; color: #0271c6; text-decoration: none; }
    .bk-btn-ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .bk-btn-ghost:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }
    .bk-btn-sm { height: 30px; padding: 0 12px; font-size: 12px; border-radius: 6px; }
    .bk-divider { border: none; border-top: 1px solid #f1f5f9; margin: 16px 0; }
    .bk-note { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #0271c6; border-radius: 8px; padding: 14px 16px; font-size: 12px; color: #475569; line-height: 1.7; margin-bottom: 12px; }
    .bk-toggle-wrap { display: flex; align-items: center; gap: 10px; }
    .bk-toggle { position: relative; width: 40px; height: 22px; flex-shrink: 0; }
    .bk-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .bk-toggle-slider { position: absolute; inset: 0; background: #e2e8f0; border-radius: 22px; cursor: pointer; transition: background 0.2s; }
    .bk-toggle-slider:before { content: ''; position: absolute; width: 16px; height: 16px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
    .bk-toggle input:checked + .bk-toggle-slider { background: #0271c6; }
    .bk-toggle input:checked + .bk-toggle-slider:before { transform: translateX(18px); }
    .bk-toggle-label { font-size: 13px; font-weight: 600; color: #0f172a; }
    .bk-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .bk-form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
    .bk-form-group:last-child { margin-bottom: 0; }
    .bk-empty { padding: 40px 20px; text-align: center; color: #94a3b8; font-size: 13px; }
    .bk-empty i { font-size: 36px; margin-bottom: 10px; display: block; color: #cbd5e1; }
    .bk-table { width: 100%; border-collapse: collapse; font-family: 'DM Sans', sans-serif; font-size: 13px; }
    .bk-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .bk-table thead th { padding: 10px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; text-align: left; white-space: nowrap; }
    .bk-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: all 0.12s ease; position: relative; }
    .bk-table tbody tr:last-child { border-bottom: none; }
    .bk-table tbody tr:hover { background: #f8fafc; box-shadow: inset 3px 0 0 #0271c6; }
    .bk-table tbody td { padding: 12px 14px; color: #334155; vertical-align: middle; }
    .bk-action-group { display: flex; gap: 6px; flex-wrap: wrap; }

    /* Host badge */
    .gcs-host-badge { display: inline-flex; align-items: center; gap: 5px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 9px; font-size: 12px; font-weight: 500; color: #475569; font-family: 'DM Mono', monospace; }
    .gcs-host-badge i { color: #94a3b8; font-size: 11px; }

    /* Port badge */
    .gcs-port-badge { display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; color: #334155; font-family: 'DM Mono', monospace; letter-spacing: 0.02em; }
    .gcs-port-badge i { color: #64748b; font-size: 10px; }

    /* Response ping badge */
    .gcs-ping { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: 'DM Mono', monospace; }
    .gcs-ping-fast { background: #dcfce7; color: #15803d; }
    .gcs-ping-medium { background: #fef3c7; color: #d97706; }
    .gcs-ping-slow { background: #fee2e2; color: #dc2626; }
    .gcs-ping-none { background: #f1f5f9; color: #94a3b8; }

    /* ===================== GCS-SPECIFIC STYLES ===================== */

    /* Stat Cards */
    .gcs-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
    .gcs-stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; transition: box-shadow 0.15s; }
    .gcs-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .gcs-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px; }
    .gcs-stat-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
    .gcs-stat-value { font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1; margin-top: 2px; }
    .gcs-stat-desc { font-size: 11px; color: #94a3b8; margin-top: 4px; }

    /* Static color bottom accent per card */
    .gcs-stat-blue  { border-bottom: 3px solid #0271c6; }
    .gcs-stat-green { border-bottom: 3px solid #16a34a; }
    .gcs-stat-red   { border-bottom: 3px solid #dc2626; }
    .gcs-stat-yellow { border-bottom: 3px solid #d97706; }

    /* Quick Action Buttons */
    .gcs-quick-action { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: all 0.15s; font-family: 'DM Sans', sans-serif; margin-bottom: 6px; width: 100%; text-align: left; }
    .gcs-quick-action:last-child { margin-bottom: 0; }
    .gcs-quick-action:hover { background: #f8fafc; border-color: #cbd5e1; }
    .gcs-qa-icon { width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; }
    .gcs-qa-text { flex: 1; }
    .gcs-qa-title { font-size: 13px; font-weight: 600; color: #0f172a; display: block; }
    .gcs-qa-desc { font-size: 11px; color: #94a3b8; display: block; }
    .gcs-qa-arrow { color: #cbd5e1; font-size: 12px; }

    /* Gauge Container */
    .gcs-gauge-row { display: flex; justify-content: space-around; padding: 6px 0 12px; }
    .gcs-gauge-item { text-align: center; }
    .gcs-gauge-item canvas { display: block; margin: 0 auto; }
    .gcs-gauge-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 6px; }
    .gcs-gauge-value { font-size: 13px; font-weight: 700; color: #0f172a; }
    .gcs-gauge-detail { font-size: 10px; color: #94a3b8; }
    .gcs-gauge-footer { display: flex; gap: 14px; justify-content: center; padding-top: 10px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
    .gcs-gauge-footer-item { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 5px; }

    /* Activity Feed */
    .gcs-activity-item { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f8fafc; }
    .gcs-activity-item:last-child { border-bottom: none; }
    .gcs-activity-dot { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 10px; margin-top: 1px; }
    .gcs-activity-text { flex: 1; font-size: 12px; color: #475569; line-height: 1.4; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gcs-activity-time { font-size: 11px; color: #94a3b8; white-space: nowrap; margin-top: 1px; }

    /* Status Badge */
    .gcs-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .gcs-status-online { background: #dcfce7; color: #15803d; }
    .gcs-status-offline { background: #fee2e2; color: #dc2626; }
    .gcs-status-testing { background: #eff6ff; color: #0271c6; }
    .gcs-pulse { width: 6px; height: 6px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .gcs-pulse-green { background: #22c55e; animation: gcsPulseGreen 2s infinite; }
    .gcs-pulse-red { background: #ef4444; animation: gcsPulseRed 2s infinite; }
    @keyframes gcsPulseGreen { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.6)} 70%{box-shadow:0 0 0 6px rgba(34,197,94,0)} }
    @keyframes gcsPulseRed { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.6)} 70%{box-shadow:0 0 0 6px rgba(239,68,68,0)} }

    /* Protocol badge */
    .gcs-proto { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .gcs-proto-https { background: #dcfce7; color: #15803d; }
    .gcs-proto-http { background: #dbeafe; color: #0271c6; }

    /* Priority star */
    .gcs-star { cursor: pointer; font-size: 15px; transition: transform 0.15s; }
    .gcs-star:hover { transform: scale(1.2); }
    .gcs-star-on { color: #f59e0b; }
    .gcs-star-off { color: #cbd5e1; }

    /* Device count badge */
    .gcs-device-badge { display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #0271c6; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
    .gcs-device-badge:hover { background: #dbeafe; }

    /* Response time */
    .gcs-response { font-size: 12px; font-weight: 600; color: #64748b; }
    .gcs-response.fast { color: #16a34a; }
    .gcs-response.medium { color: #d97706; }
    .gcs-response.slow { color: #dc2626; }

    /* Priority row highlight */
    .gcs-priority-row { background: linear-gradient(to right, #fffbeb, transparent) !important; }

    /* Filters bar */
    .gcs-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; background: #fafcff; }
    .gcs-filter-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 130px; }
    .gcs-filter-group.wide { flex: 2; min-width: 200px; }

    /* Pagination */
    .gcs-pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-top: 1px solid #f1f5f9; background: #fafcff; gap: 10px; }
    .gcs-pagination-info { font-size: 12px; color: #64748b; white-space: nowrap; }
    .gcs-pagination-right { display: flex; align-items: center; gap: 10px; }
    .gcs-page-btns { display: flex; gap: 4px; align-items: center; }
    .gcs-page-btn { height: 30px; min-width: 30px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; border-radius: 7px; font-size: 12px; font-weight: 500; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; transition: all 0.1s; text-decoration: none; }
    .gcs-page-btn:hover { background: #f1f5f9; color: #0f172a; text-decoration: none; }
    .gcs-page-btn.active { background: #0271c6; color: white; border-color: #0271c6; }
    .gcs-page-btn.disabled { opacity: 0.4; pointer-events: none; }
    .gcs-per-page { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }

    /* Loading Overlay */
    #gcs-loading { position: fixed; inset: 0; background: rgba(15,23,42,0.45); backdrop-filter: blur(3px); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .gcs-loading-box { background: white; border-radius: 14px; padding: 28px 36px; display: flex; flex-direction: column; align-items: center; gap: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
    .gcs-spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0271c6; border-radius: 50%; animation: gcsSpin 0.7s linear infinite; }
    .gcs-loading-text { font-size: 13px; font-weight: 600; color: #0f172a; }
    @keyframes gcsSpin { to { transform: rotate(360deg); } }

    /* Notification */
    #gcs-notif-container { position: fixed; top: 72px; right: 20px; z-index: 9990; display: flex; flex-direction: column; gap: 8px; width: 320px; }
    .gcs-notif { background: white; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 12px 14px; display: flex; align-items: flex-start; gap: 10px; animation: gcsNotifIn 0.25s ease; }
    .gcs-notif-icon { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; margin-top: 1px; }
    .gcs-notif-text { flex: 1; font-size: 12px; color: #334155; line-height: 1.5; }
    .gcs-notif-close { cursor: pointer; color: #94a3b8; font-size: 14px; line-height: 1; flex-shrink: 0; }
    .gcs-notif-close:hover { color: #475569; }
    .gcs-notif.success .gcs-notif-icon { background: #dcfce7; color: #16a34a; }
    .gcs-notif.error .gcs-notif-icon { background: #fee2e2; color: #dc2626; }
    .gcs-notif.warning .gcs-notif-icon { background: #fef3c7; color: #d97706; }
    .gcs-notif.info .gcs-notif-icon { background: #dbeafe; color: #0271c6; }
    @keyframes gcsNotifIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }

    /* Mobile card layout for server list */
    .gcs-mobile-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 10px; }
    .gcs-mobile-card.priority { border-left: 3px solid #f59e0b; }
    .gcs-mobile-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .gcs-mobile-card-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f8fafc; font-size: 12px; }
    .gcs-mobile-card-row:last-child { border-bottom: none; }
    .gcs-mobile-card-label { color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.04em; }
    .gcs-mobile-card-actions { display: flex; gap: 6px; margin-top: 12px; flex-wrap: wrap; }

    /* Custom modal overlay (match uninstall/update modals) */
    .gcs-modal-overlay { position: fixed; inset: 0; z-index: 9995; background: rgba(15,23,42,0); backdrop-filter: blur(0px); display: none; align-items: center; justify-content: center; padding: 16px; transition: background 0.3s ease, backdrop-filter 0.3s ease; }
    .gcs-modal-overlay.open { display: flex; background: rgba(15,23,42,0.55); backdrop-filter: blur(3px); }
    .gcs-modal-card { background: white; border-radius: 16px; width: 100%; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; display: flex; flex-direction: column; animation: gcsModalIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .gcs-modal-card.sm { max-width: 460px; }
    .gcs-modal-card.md { max-width: 560px; }
    .gcs-modal-card.lg { max-width: 720px; }
    @keyframes gcsModalIn { from { opacity:0; transform:translateY(-12px) scale(0.98); } to { opacity:1; transform:translateY(0) scale(1); } }
    .gcs-modal-header { padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
    .gcs-modal-header.blue { background: linear-gradient(135deg, #0271c6, #0359a0); color: white; }
    .gcs-modal-header.red { background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; }
    .gcs-modal-header.green { background: linear-gradient(135deg, #16a34a, #15803d); color: white; }
    .gcs-modal-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .gcs-modal-subtitle { font-size: 11px; opacity: 0.8; margin-top: 2px; }
    .gcs-modal-close { width: 30px; height: 30px; border-radius: 8px; border: none; background: rgba(255,255,255,0.2); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: background 0.15s; flex-shrink: 0; }
    .gcs-modal-close:hover { background: rgba(255,255,255,0.3); }
    .gcs-modal-body { padding: 22px; overflow-y: auto; flex: 1; }
    .gcs-modal-footer { padding: 14px 22px; border-top: 1px solid #f1f5f9; display: flex; gap: 8px; justify-content: flex-end; flex-shrink: 0; background: #fafcff; }

    /* Warning box inside modals */
    .gcs-warning-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 14px 16px; display: flex; gap: 12px; margin-bottom: 16px; }
    .gcs-warning-icon { width: 36px; height: 36px; border-radius: 8px; background: #fed7aa; display: flex; align-items: center; justify-content: center; color: #c2410c; font-size: 16px; flex-shrink: 0; }
    .gcs-warning-content strong { font-size: 13px; font-weight: 700; color: #c2410c; display: block; margin-bottom: 4px; }
    .gcs-warning-content p { font-size: 12px; color: #9a3412; margin: 0; line-height: 1.6; }

    /* Delete list grid */
    .gcs-delete-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .gcs-delete-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #475569; padding: 8px 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; }
    .gcs-delete-item i { color: #dc2626; font-size: 13px; flex-shrink: 0; }

    /* Section header */
    .gcs-section-header { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.07em; display: flex; align-items: center; gap: 6px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 14px; }

    /* Progress log */
    .gcs-log-box { background: #0f172a; border-radius: 10px; padding: 14px; max-height: 200px; overflow-y: auto; font-family: 'DM Mono', monospace; font-size: 12px; line-height: 1.7; }
    .gcs-log-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px; }
    .gcs-log-item.success { color: #4ade80; }
    .gcs-log-item.error { color: #f87171; }
    .gcs-log-item.info { color: #93c5fd; }

    /* Version compare box */
    .gcs-version-box { display: flex; align-items: center; justify-content: center; gap: 20px; padding: 20px; background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #bfdbfe; border-radius: 12px; margin-bottom: 16px; text-align: center; }
    .gcs-version-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; display: block; margin-bottom: 4px; }
    .gcs-version-num { font-size: 22px; font-weight: 700; color: #1e40af; display: block; }
    .gcs-version-num.new { color: #16a34a; }

    /* Responsive */
    @media (max-width: 991px) {
        .gcs-stat-grid { grid-template-columns: repeat(2, 1fr); }
        .gcs-filter-bar { flex-wrap: wrap; }
        .gcs-filter-group { min-width: calc(50% - 8px); }
    }
    @media (max-width: 767px) {
        .gcs-stat-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .gcs-stat-value { font-size: 22px; }
        .bk-form-row { grid-template-columns: 1fr; }
        .gcs-modal-card.sm, .gcs-modal-card.md, .gcs-modal-card.lg { max-width: 100%; }
        .gcs-modal-footer { flex-direction: column-reverse; }
        .gcs-modal-footer .bk-btn { width: 100%; justify-content: center; }
        .gcs-filter-group { min-width: 100%; }
        .gcs-delete-grid { grid-template-columns: 1fr; }
        .gcs-quick-action { margin-bottom: 6px; }
    }
    @media (max-width: 480px) {
        .gcs-stat-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
        .gcs-stat-card { padding: 12px; }
        .gcs-stat-icon { width: 36px; height: 36px; font-size: 16px; }
    }
    .swal2-container { z-index: 999999 !important; }

    /* Lock background scroll when modal open */
    html.modal-open { overflow: hidden; }

    /* ===================== MENU GRID ===================== */
    .gcs-menu-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
    }
    .gcs-menu-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 6px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: 'DM Sans', sans-serif;
        text-align: center;
    }
    .gcs-menu-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .gcs-menu-item:active { transform: translateY(0); }
    .gcs-menu-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex-shrink: 0;
    }
    .gcs-menu-label {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
        line-height: 1.3;
    }
    .gcs-menu-danger { border-color: #e2e8f0 !important; }
    .gcs-menu-danger:hover { background: #fff5f5 !important; border-color: #fca5a5 !important; }

    /* Equal height row fix */
    .gcs-equal-row { display: flex; flex-wrap: wrap; }
    .gcs-equal-row > [class*="col-"] { display: flex; flex-direction: column; }
    .gcs-equal-row > [class*="col-"] > .bk-card { flex: 1; }

    /* ===================== MOBILE RESPONSIVE ===================== */
    @media (max-width: 767px) {

        /* Stat grid: 2x2 */
        .gcs-stat-grid { grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
        .gcs-stat-card { padding: 14px 12px 12px; border-radius: 12px; gap: 10px; }
        .gcs-stat-icon { width: 36px; height: 36px; border-radius: 9px; font-size: 16px; }
        .gcs-stat-value { font-size: 22px; }
        .gcs-stat-label { font-size: 10px; }
        .gcs-stat-desc { font-size: 10px; }

        /* Equal row stack vertical */
        .gcs-equal-row { display: block; }
        .gcs-equal-row > [class*="col-"] { margin-bottom: 10px; }
        .gcs-equal-row > [class*="col-"]:last-child { margin-bottom: 0; }

        /* Menu 3 kolom lebih nyaman */
        .gcs-menu-grid { grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .gcs-menu-item { padding: 12px 4px; border-radius: 10px; }
        .gcs-menu-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 18px; }
        .gcs-menu-label { font-size: 11px; font-weight: 600; }

        /* bk-card tighter */
        .bk-card { border-radius: 12px; margin-bottom: 0; }
        .bk-card-header { padding: 12px 14px; }
        .bk-card-icon { width: 30px; height: 30px; }
        .bk-card-title { font-size: 13px; }
        .bk-card-subtitle { font-size: 10px; }
        .bk-card-body { padding: 14px; }

        /* Gauge compact */
        .gcs-gauge-row { justify-content: space-around; padding: 4px 0 8px; }
        .gcs-gauge-item canvas { width: 64px !important; height: 64px !important; }
        .gcs-gauge-label { font-size: 10px; margin-top: 4px; }
        .gcs-gauge-value { font-size: 12px; }
        .gcs-gauge-detail { font-size: 9px; }
        .gcs-gauge-footer { gap: 12px; padding-top: 8px; }
        .gcs-gauge-footer-item { font-size: 10px; }

        /* Activity feed */
        .gcs-activity-item { padding: 8px 0; gap: 8px; }
        .gcs-activity-text { font-size: 12px; }
        .gcs-activity-time { font-size: 10px; }
        .gcs-activity-dot { width: 18px; height: 18px; flex-shrink: 0; }

        /* Filter bar stack */
        .gcs-filter-bar { flex-direction: column; gap: 8px; padding: 12px; }
        .gcs-filter-group, .gcs-filter-group.wide { min-width: 100%; flex: none; }
        .gcs-filter-group label[style*="visibility:hidden"] { display: none; }
        .gcs-filter-group[style*="flex:none"] { min-width: 100% !important; }
        .gcs-filter-group[style*="flex:none"] .bk-btn { width: 100%; justify-content: center; }

        /* Pagination mobile */
        .gcs-pagination { flex-wrap: wrap; padding: 10px 14px; }
        .gcs-pagination-info { font-size: 11px; width: 100%; }
        .gcs-pagination-right { width: 100%; justify-content: space-between; }
        .gcs-page-btn { height: 28px; min-width: 28px; font-size: 11px; }
        .gcs-per-page { font-size: 11px; }

        /* Notification full width */
        #gcs-notif-container { left: 10px; right: 10px; width: auto; }

        /* Mobile server cards */
        .gcs-mobile-card { border-radius: 10px; padding: 12px; }
        .gcs-mobile-card-row { font-size: 12px; padding: 5px 0; }
        .gcs-mobile-card-label { font-size: 10px; }
        .gcs-mobile-card-actions { gap: 6px; margin-top: 10px; }
        .gcs-mobile-card-actions .bk-btn { flex: 1; justify-content: center; }

        /* Modal bottom sheet - smooth slide up */
        .gcs-modal-overlay { align-items: flex-end !important; padding: 0; }
        .gcs-modal-card {
            border-radius: 20px 20px 0 0;
            max-height: 92vh;
            animation: gcsModalSlideUp 0.35s cubic-bezier(0.32, 0.72, 0, 1);
        }
        @keyframes gcsModalSlideUp {
            from { transform: translateY(100%); opacity: 0.6; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .gcs-modal-card.sm, .gcs-modal-card.md, .gcs-modal-card.lg { max-width: 100%; }
        .gcs-modal-header { padding: 16px 18px; }
        .gcs-modal-body {
            padding: 16px 18px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        .gcs-modal-footer { padding: 12px 18px; flex-direction: column-reverse; }
        .gcs-modal-footer .bk-btn { width: 100%; justify-content: center; }
        .bk-form-row { grid-template-columns: 1fr; }
        .gcs-delete-grid { grid-template-columns: 1fr; gap: 5px; }
    }

    @media (max-width: 400px) {
        .gcs-stat-grid { gap: 8px; }
        .gcs-stat-card { padding: 12px 10px 10px; }
        .gcs-stat-value { font-size: 20px; }
        .gcs-menu-icon { width: 36px; height: 36px; font-size: 16px; }
        .gcs-menu-label { font-size: 10px; }
    }
{/literal}
</style>

<!-- ===================== LOADING OVERLAY ===================== -->
<div id="gcs-loading" style="display:none;">
    <div class="gcs-loading-box">
        <div class="gcs-spinner"></div>
        <div class="gcs-loading-text" id="gcs-loading-text">Processing...</div>
    </div>
</div>

<!-- ===================== NOTIFICATION CONTAINER ===================== -->
<div id="gcs-notif-container"></div>

<!-- ===================== STAT CARDS ===================== -->
<div class="gcs-stat-grid" id="stats-cards">

    <!-- Total Servers -->
    <div class="gcs-stat-card gcs-stat-blue">
        <div class="gcs-stat-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
            <i class="ph ph-hard-drives" style="color:#0271c6;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="gcs-stat-label">Total Servers</div>
            <div class="gcs-stat-value">{$server_count}</div>
            <div class="gcs-stat-desc">Server terkonfigurasi</div>
        </div>
    </div>

    <!-- Online Servers -->
    <div class="gcs-stat-card gcs-stat-green">
        <div class="gcs-stat-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
            <i class="ph ph-check-circle" style="color:#16a34a;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="gcs-stat-label">Online</div>
            <div class="gcs-stat-value">{$online_servers}</div>
            {if $online_servers == $server_count}
                <div class="gcs-stat-desc">Semua terhubung</div>
            {else}
                <div class="gcs-stat-desc">{$online_servers} dari {$server_count} server</div>
            {/if}
        </div>
    </div>

    <!-- Offline Servers -->
    <div class="gcs-stat-card gcs-stat-red">
        <div class="gcs-stat-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
            <i class="ph ph-x-circle" style="color:#dc2626;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="gcs-stat-label">Offline</div>
            <div class="gcs-stat-value">{$offline_servers}</div>
            {if $offline_servers == 0}
                <div class="gcs-stat-desc">Tidak ada masalah</div>
            {else}
                <div class="gcs-stat-desc" style="color:#dc2626;font-weight:600;">{$offline_servers} server bermasalah</div>
            {/if}
        </div>
    </div>

    <!-- Total Devices -->
    <div class="gcs-stat-card gcs-stat-yellow">
        <div class="gcs-stat-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
            <i class="ph ph-device-mobile" style="color:#d97706;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="gcs-stat-label">Total Devices</div>
            <div class="gcs-stat-value" id="total-devices-count">{$total_devices}</div>
            <div class="gcs-stat-desc">Dari semua server</div>
        </div>
    </div>

</div>

<!-- ===================== SECOND ROW: Menu + Host Server + Activity ===================== -->
<div class="row gcs-equal-row" style="margin-bottom:16px;">

    <!-- Menu (compact icon grid) -->
    <div class="col-md-4" style="margin-bottom:16px;">
        <div class="bk-card" style="margin-bottom:0;">
            <div class="bk-card-header">
                <div class="bk-card-icon" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                    <i class="ph ph-squares-four" style="font-size:16px;color:#0271c6;"></i>
                </div>
                <div>
                    <div class="bk-card-title">Menu</div>
                    <div class="bk-card-subtitle">Shortcut aksi cepat</div>
                </div>
            </div>
            <div class="bk-card-body" style="padding:14px;">
                <div class="gcs-menu-grid">
                    <button class="gcs-menu-item" onclick="window.location.href='{$_url}plugin/genieacs_parameters'">
                        <div class="gcs-menu-icon" style="background:#fefce8;">
                            <i class="ph ph-sliders-horizontal" style="color:#d97706;"></i>
                        </div>
                        <span class="gcs-menu-label">Parameters</span>
                    </button>
                    <button class="gcs-menu-item" onclick="showDeviceMappingModal()">
                        <div class="gcs-menu-icon" style="background:#f5f3ff;">
                            <i class="ph ph-database" style="color:#7c3aed;"></i>
                        </div>
                        <span class="gcs-menu-label">Device Cache</span>
                    </button>
                    <button class="gcs-menu-item" onclick="testAllConnections()">
                        <div class="gcs-menu-icon" style="background:#f0fdf4;">
                            <i class="ph ph-plugs-connected" style="color:#16a34a;"></i>
                        </div>
                        <span class="gcs-menu-label">Test All</span>
                    </button>
                    <button class="gcs-menu-item" onclick="acsOpenLicenseModal()">
                        <div class="gcs-menu-icon" style="background:#fdf4ff;">
                            <i class="ph ph-key" style="color:#a21caf;"></i>
                        </div>
                        <span class="gcs-menu-label">License</span>
                    </button>
                    <button class="gcs-menu-item" id="btn-check-update" onclick="checkForUpdate()">
                        <div class="gcs-menu-icon" style="background:#eff6ff;">
                            <i class="ph ph-arrows-clockwise" style="color:#0271c6;"></i>
                        </div>
                        <span class="gcs-menu-label" id="update-status">Update</span>
                    </button>
                    <button class="gcs-menu-item gcs-menu-danger" onclick="showUninstallModal()">
                        <div class="gcs-menu-icon" style="background:#fee2e2;">
                            <i class="ph ph-trash" style="color:#dc2626;"></i>
                        </div>
                        <span class="gcs-menu-label" style="color:#dc2626;">Uninstall</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Host Server Gauges -->
    <div class="col-md-4" style="margin-bottom:16px;">
        <div class="bk-card" style="margin-bottom:0;">
            <div class="bk-card-header">
                <div class="bk-card-icon" style="background:linear-gradient(135deg,#e0f2fe,#bae6fd);">
                    <i class="ph ph-desktop-tower" style="font-size:16px;color:#0284c7;"></i>
                </div>
                <div style="flex:1;">
                    <div class="bk-card-title">Host Server</div>
                    <div class="bk-card-subtitle">Local system resources</div>
                </div>
                <button onclick="refreshLocalStats()" style="width:28px;height:28px;border-radius:7px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">
                    <i class="ph ph-arrows-clockwise" id="local-refresh-icon" style="font-size:13px;"></i>
                </button>
            </div>
            <div class="bk-card-body">
                <div class="gcs-gauge-row">
                    <div class="gcs-gauge-item">
                        <canvas id="cpuGauge" width="72" height="72"></canvas>
                        <div class="gcs-gauge-label">CPU</div>
                        <div class="gcs-gauge-value" id="cpu-text">{$local_stats.cpu_usage}%</div>
                    </div>
                    <div class="gcs-gauge-item">
                        <canvas id="memGauge" width="72" height="72"></canvas>
                        <div class="gcs-gauge-label">RAM</div>
                        <div class="gcs-gauge-value" id="mem-text">{$local_stats.mem_usage}%</div>
                        <div class="gcs-gauge-detail" id="mem-detail">{$local_stats.mem_used_gb}/{$local_stats.mem_total_gb}GB</div>
                    </div>
                    <div class="gcs-gauge-item">
                        <canvas id="diskGauge" width="72" height="72"></canvas>
                        <div class="gcs-gauge-label">Disk</div>
                        <div class="gcs-gauge-value" id="disk-text">{$local_stats.disk_usage}%</div>
                        <div class="gcs-gauge-detail" id="disk-detail">{$local_stats.disk_used_gb}/{$local_stats.disk_total_gb}GB</div>
                    </div>
                </div>
                <div class="gcs-gauge-footer">
                    <div class="gcs-gauge-footer-item">
                        <i class="ph ph-gauge" style="color:#0284c7;font-size:13px;"></i>
                        <span id="load-info">Load: {$local_stats.load_1min}</span>
                    </div>
                    <div class="gcs-gauge-footer-item">
                        <i class="ph ph-clock" style="color:#16a34a;font-size:13px;"></i>
                        <span id="uptime-info">{$local_stats.uptime_string}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="col-md-4" style="margin-bottom:16px;">
        <div class="bk-card" style="margin-bottom:0;">
            <div class="bk-card-header">
                <div class="bk-card-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
                    <i class="ph ph-clock-countdown" style="font-size:16px;color:#d97706;"></i>
                </div>
                <div style="flex:1;">
                    <div class="bk-card-title">Recent Activity</div>
                    <div class="bk-card-subtitle">Connection events log</div>
                </div>
                <button onclick="refreshActivity()" style="width:28px;height:28px;border-radius:7px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">
                    <i class="ph ph-arrows-clockwise" id="activity-refresh-icon" style="font-size:13px;"></i>
                </button>
            </div>
            <div class="bk-card-body" style="padding:14px 18px;max-height:240px;overflow-y:auto;" id="activity-feed">
                <div id="activity-list">
                    <div class="gcs-activity-item">
                        <div class="gcs-activity-dot" style="background:#dbeafe;"><i class="ph ph-info" style="color:#0271c6;font-size:10px;"></i></div>
                        <div class="gcs-activity-text">Loading activities...</div>
                        <div class="gcs-activity-time">now</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ===================== FILTER BAR ===================== -->
<div class="bk-card" style="margin-bottom:16px;">
    <div class="gcs-filter-bar">
        <div class="gcs-filter-group wide">
            <label class="bk-label"><i class="ph ph-magnifying-glass" style="font-size:10px;"></i> Search</label>
            <div style="position:relative;">
                <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                <input type="text" class="bk-input" id="serverSearch" placeholder="Search by name, host, or IP..." style="padding-left:32px;">
            </div>
        </div>
        <div class="gcs-filter-group">
            <label class="bk-label"><i class="ph ph-dot-outline" style="font-size:10px;"></i> Status</label>
            <select class="bk-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="online">Online Only</option>
                <option value="offline">Offline Only</option>
            </select>
        </div>
        <div class="gcs-filter-group">
            <label class="bk-label"><i class="ph ph-lock" style="font-size:10px;"></i> Protocol</label>
            <select class="bk-select" id="protocolFilter">
                <option value="">All Protocols</option>
                <option value="https">HTTPS</option>
                <option value="http">HTTP</option>
            </select>
        </div>
        <div class="gcs-filter-group">
            <label class="bk-label"><i class="ph ph-star" style="font-size:10px;"></i> Priority</label>
            <select class="bk-select" id="priorityFilter">
                <option value="">All Servers</option>
                <option value="priority">Priority Only</option>
                <option value="normal">Normal Only</option>
            </select>
        </div>
        <div class="gcs-filter-group" style="flex:none;min-width:auto;">
            <label class="bk-label" style="visibility:hidden;">.</label>
            <button class="bk-btn bk-btn-danger" id="clearFiltersBtn" onclick="clearFilters()" style="height:36px;white-space:nowrap;">
                <i class="ph ph-x" style="font-size:13px;"></i> Clear
            </button>
        </div>
    </div>
</div>

<!-- ===================== MAIN SERVER TABLE ===================== -->
<div class="bk-card">
    <div class="bk-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="bk-card-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                <i class="ph ph-cloud" style="font-size:16px;color:#7c3aed;"></i>
            </div>
            <div>
                <div class="bk-card-title">GenieACS Server Manager</div>
                <div class="bk-card-subtitle">{$server_count} servers configured</div>
            </div>
        </div>
        <button class="bk-btn bk-btn-primary" onclick="showAddServerModal()">
            <i class="ph ph-plus" style="font-size:13px;"></i> Add Server
        </button>
    </div>

    <!-- Desktop Table -->
    <div style="overflow-x:auto;display:block;" class="hidden-xs">
        <table class="bk-table" id="serverTableDesktop">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;"><i class="ph ph-star" title="Priority"></i></th>
                    <th style="width:44px;">ID</th>
                    <th>Server Name</th>
                    <th>Host</th>
                    <th style="width:80px;">Port</th>
                    <th style="width:90px;">Protocol</th>
                    <th style="width:110px;">Status</th>
                    <th style="width:90px;">Devices</th>
                    <th style="width:110px;">Response</th>
                    <th style="width:130px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $servers as $server}
                <tr class="server-row {if $server->is_priority}gcs-priority-row{/if}" data-server-id="{$server->id}">
                    <td style="text-align:center;">
                        <i class="ph {if $server->is_priority}ph-star-fill gcs-star gcs-star-on{else}ph-star gcs-star gcs-star-off{/if} priority-toggle"
                            data-server-id="{$server->id}" onclick="togglePriority({$server->id})"></i>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;font-family:'DM Mono',monospace;">{$server->id}</td>
                    <td>
                        <div style="display:flex;flex-direction:column;gap:3px;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">{$server->name}</span>
                                {if $server->is_priority}
                                    <span style="font-size:9px;font-weight:700;background:#fef3c7;color:#d97706;padding:1px 6px;border-radius:4px;text-transform:uppercase;letter-spacing:0.04em;">Priority</span>
                                {/if}
                            </div>
                            <span style="font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:4px;">
                                <i class="ph ph-user" style="font-size:10px;"></i> {$server->username}
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="gcs-host-badge">
                            <i class="ph ph-monitor"></i> {$server->host}
                        </span>
                    </td>
                    <td>
                        <span class="gcs-port-badge">
                            <i class="ph ph-plug"></i> {if $server->use_ssl}443{else}{$server->port}{/if}
                        </span>
                    </td>
                    <td>
                        {if $server->use_ssl}
                            <span class="gcs-proto gcs-proto-https"><i class="ph ph-lock" style="font-size:10px;"></i> HTTPS</span>
                        {else}
                            <span class="gcs-proto gcs-proto-http"><i class="ph ph-lock-open" style="font-size:10px;"></i> HTTP</span>
                        {/if}
                    </td>
                    <td id="status-{$server->id}">
                        {if $server->is_connected}
                            <span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>
                        {else}
                            <span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>
                        {/if}
                    </td>
                    <td>
                        <span class="gcs-device-badge" id="device-count-{$server->id}" onclick="refreshDeviceCount({$server->id})" title="Click to refresh">
                            <i class="ph ph-spinner" style="font-size:10px;animation:gcsSpin 1s linear infinite;"></i>
                        </span>
                    </td>
                    <td>
                        {if $server->last_response_time > 0}
                            {if $server->last_response_time <= 100}
                                <span class="gcs-ping gcs-ping-fast"><i class="ph ph-wifi-high" style="font-size:11px;"></i> {$server->last_response_time|round:0}ms</span>
                            {elseif $server->last_response_time <= 500}
                                <span class="gcs-ping gcs-ping-medium"><i class="ph ph-wifi-medium" style="font-size:11px;"></i> {$server->last_response_time|round:0}ms</span>
                            {else}
                                <span class="gcs-ping gcs-ping-slow"><i class="ph ph-wifi-low" style="font-size:11px;"></i> {$server->last_response_time|round:0}ms</span>
                            {/if}
                        {else}
                            <span class="gcs-ping gcs-ping-none"><i class="ph ph-wifi-slash" style="font-size:11px;"></i> —</span>
                        {/if}
                    </td>
                    <td>
                        <div class="bk-action-group" style="justify-content:flex-end;">
                            <button class="bk-btn bk-btn-ghost bk-btn-sm" onclick="testConnection({$server->id})" title="Test Connection">
                                <i class="ph ph-plugs-connected" style="font-size:12px;"></i>
                            </button>
                            <button class="bk-btn bk-btn-ghost bk-btn-sm" onclick="editServer({$server->id})" title="Edit">
                                <i class="ph ph-pencil" style="font-size:12px;"></i>
                            </button>
                            <button class="bk-btn bk-btn-ghost bk-btn-sm" onclick="viewServerDevices({$server->id})" title="View Devices" style="color:#7c3aed;">
                                <i class="ph ph-devices" style="font-size:12px;"></i>
                            </button>
                            <button class="bk-btn bk-btn-danger bk-btn-sm" onclick="deleteServer({$server->id})" title="Delete">
                                <i class="ph ph-trash" style="font-size:12px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="visible-xs" style="padding:10px;">
        {foreach $servers as $server}
        <div class="gcs-mobile-card {if $server->is_priority}priority{/if} mobile-server-card" data-server-id="{$server->id}">
            <div class="gcs-mobile-card-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="ph {if $server->is_priority}ph-star-fill gcs-star-on{else}ph-star gcs-star-off{/if} priority-toggle"
                        data-server-id="{$server->id}" onclick="togglePriority({$server->id})" style="font-size:15px;cursor:pointer;"></i>
                    <span style="font-size:13px;font-weight:700;color:#0f172a;">{$server->name}</span>
                </div>
                <div id="status-mobile-{$server->id}">
                    {if $server->is_connected}
                        <span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>
                    {else}
                        <span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>
                    {/if}
                </div>
            </div>
            <div class="gcs-mobile-card-row">
                <span class="gcs-mobile-card-label">Host</span>
                <span style="font-size:12px;color:#334155;">{$server->host}</span>
            </div>
            <div class="gcs-mobile-card-row">
                <span class="gcs-mobile-card-label">Port</span>
                <span style="font-size:12px;color:#334155;">{if $server->use_ssl}443{else}{$server->port}{/if}</span>
            </div>
            <div class="gcs-mobile-card-row">
                <span class="gcs-mobile-card-label">Protocol</span>
                {if $server->use_ssl}
                    <span class="gcs-proto gcs-proto-https"><i class="ph ph-lock" style="font-size:10px;"></i> HTTPS</span>
                {else}
                    <span class="gcs-proto gcs-proto-http"><i class="ph ph-lock-open" style="font-size:10px;"></i> HTTP</span>
                {/if}
            </div>
            <div class="gcs-mobile-card-row">
                <span class="gcs-mobile-card-label">Devices</span>
                <span class="gcs-device-badge" id="device-count-mobile-{$server->id}" onclick="refreshDeviceCount({$server->id})">
                    <i class="ph ph-spinner" style="font-size:10px;animation:gcsSpin 1s linear infinite;"></i>
                </span>
            </div>
            <div class="gcs-mobile-card-row">
                <span class="gcs-mobile-card-label">Response</span>
                <span class="gcs-response" id="response-time-mobile-{$server->id}">
                    {if $server->last_response_time > 0}{$server->last_response_time|round:0}ms{else}—{/if}
                </span>
            </div>
            <div class="gcs-mobile-card-actions">
                <button class="bk-btn bk-btn-ghost bk-btn-sm" onclick="testConnection({$server->id})"><i class="ph ph-plugs-connected" style="font-size:12px;"></i></button>
                <button class="bk-btn bk-btn-ghost bk-btn-sm" onclick="editServer({$server->id})"><i class="ph ph-pencil" style="font-size:12px;"></i></button>
                <button class="bk-btn bk-btn-warning bk-btn-sm" onclick="viewServerDevices({$server->id})"><i class="ph ph-devices" style="font-size:12px;"></i></button>
                <button class="bk-btn bk-btn-danger bk-btn-sm" onclick="deleteServer({$server->id})"><i class="ph ph-trash" style="font-size:12px;"></i></button>
            </div>
        </div>
        {/foreach}
    </div>

    <!-- Pagination Footer -->
    <div class="gcs-pagination">
        <div class="gcs-pagination-info">
            Showing {($current_page-1)*$per_page+1}–{min($current_page*$per_page, $server_count)} of {$server_count} servers
        </div>
        <div class="gcs-pagination-right">
            <div class="gcs-per-page">
                Show
                <select class="bk-select" id="perPageSelector" style="width:64px;height:28px;font-size:12px;padding:0 24px 0 8px;">
                    <option value="10" {if $per_page == 10}selected{/if}>10</option>
                    <option value="20" {if $per_page == 20}selected{/if}>20</option>
                    <option value="50" {if $per_page == 50}selected{/if}>50</option>
                    <option value="100" {if $per_page == 100}selected{/if}>100</option>
                </select>
            </div>
            <div class="gcs-page-btns">
                {if $current_page > 1}
                    <a class="gcs-page-btn" href="{$_url}plugin/genieacs_manager?page={$current_page-1}&per_page={$per_page}">
                        <i class="ph ph-caret-left" style="font-size:11px;"></i>
                    </a>
                {else}
                    <span class="gcs-page-btn disabled"><i class="ph ph-caret-left" style="font-size:11px;"></i></span>
                {/if}

                {for $i=1 to $total_pages}
                    {if $i <= 2 || $i > $total_pages-2 || ($i >= $current_page-1 && $i <= $current_page+1)}
                        <a class="gcs-page-btn {if $i == $current_page}active{/if}"
                            href="{$_url}plugin/genieacs_manager?page={$i}&per_page={$per_page}">{$i}</a>
                    {elseif $i == 3 || $i == $total_pages-2}
                        <span class="gcs-page-btn disabled" style="letter-spacing:1px;">···</span>
                    {/if}
                {/for}

                {if $current_page < $total_pages}
                    <a class="gcs-page-btn" href="{$_url}plugin/genieacs_manager?page={$current_page+1}&per_page={$per_page}">
                        <i class="ph ph-caret-right" style="font-size:11px;"></i>
                    </a>
                {else}
                    <span class="gcs-page-btn disabled"><i class="ph ph-caret-right" style="font-size:11px;"></i></span>
                {/if}
            </div>
        </div>
    </div>
</div>

<!-- ===================== ADD/EDIT SERVER MODAL ===================== -->
<div class="gcs-modal-overlay" id="serverModal">
    <div class="gcs-modal-card md">
        <div class="gcs-modal-header blue">
            <div>
                <div class="gcs-modal-title"><i class="ph ph-cloud-arrow-up"></i> <span id="serverModalTitle">Add ACS Server</span></div>
                <div class="gcs-modal-subtitle">Configure GenieACS server connection</div>
            </div>
            <button class="gcs-modal-close" onclick="closeServerModal()">×</button>
        </div>
        <div class="gcs-modal-body">
            <form id="serverForm">
                <input type="hidden" id="server_id" name="id" value="0">

                <div class="bk-form-group">
                    <label class="bk-label"><i class="ph ph-tag" style="font-size:10px;"></i> Server Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" class="bk-input" id="server_name" name="name" required placeholder="Main ACS Server">
                    <div class="bk-help">Friendly name to identify this server</div>
                </div>

                <div class="bk-form-row">
                    <div class="bk-form-group">
                        <label class="bk-label"><i class="ph ph-globe" style="font-size:10px;"></i> Host / IP <span style="color:#dc2626;">*</span></label>
                        <input type="text" class="bk-input" id="server_host" name="host" required placeholder="192.168.1.1 or acs.example.com">
                        <div class="bk-help">IP address or domain name</div>
                    </div>
                    <div class="bk-form-group">
                        <label class="bk-label"><i class="ph ph-plug" style="font-size:10px;"></i> Port <span style="color:#dc2626;">*</span></label>
                        <input type="number" class="bk-input" id="server_port" name="port" value="7557" required min="1" max="65535">
                        <div class="bk-help">Default: 7557 (HTTP) or 443 (HTTPS)</div>
                    </div>
                </div>

                <hr class="bk-divider">

                <div class="gcs-section-header"><i class="ph ph-shield-check" style="font-size:12px;"></i> Authentication</div>

                <div class="bk-form-row">
                    <div class="bk-form-group">
                        <label class="bk-label"><i class="ph ph-user" style="font-size:10px;"></i> Username <span style="color:#dc2626;">*</span></label>
                        <input type="text" class="bk-input" id="server_username" name="username" required placeholder="admin">
                        <div class="bk-help">GenieACS admin username</div>
                    </div>
                    <div class="bk-form-group">
                        <label class="bk-label"><i class="ph ph-lock" style="font-size:10px;"></i> Password <span style="color:#dc2626;">*</span></label>
                        <div style="position:relative;">
                            <input type="password" class="bk-input" id="server_password" name="password" required placeholder="••••••••">
                            <button type="button" onclick="togglePassword()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);border:none;background:none;cursor:pointer;color:#94a3b8;padding:0;line-height:1;" title="Show/Hide">
                                <i class="ph ph-eye" id="password-toggle-icon" style="font-size:15px;"></i>
                            </button>
                        </div>
                        <div class="bk-help">GenieACS admin password</div>
                    </div>
                </div>
            </form>
        </div>
        <div class="gcs-modal-footer">
            <button type="button" class="bk-btn bk-btn-ghost" onclick="closeServerModal()">
                <i class="ph ph-x" style="font-size:13px;"></i> Cancel
            </button>
            <button type="button" class="bk-btn bk-btn-warning" onclick="testAndSaveServer()">
                <i class="ph ph-plugs-connected" style="font-size:13px;"></i> Test Connection
            </button>
            <button type="button" class="bk-btn bk-btn-primary" onclick="saveServer()">
                <i class="ph ph-floppy-disk" style="font-size:13px;"></i> Save Server
            </button>
        </div>
    </div>
</div>

<!-- ===================== DEVICE MAPPING MODAL ===================== -->
<div class="gcs-modal-overlay" id="deviceMappingModal">
    <div class="gcs-modal-card lg">
        <div class="gcs-modal-header blue">
            <div>
                <div class="gcs-modal-title"><i class="ph ph-database"></i> Device Cache Management</div>
                <div class="gcs-modal-subtitle">View and manage device-to-user mappings</div>
            </div>
            <button class="gcs-modal-close" onclick="closeModal('deviceMappingModal')">×</button>
        </div>
        <div class="gcs-modal-body" style="padding:0;">
            <!-- Search bar -->
            <div style="padding:14px 16px 12px;border-bottom:1px solid #f1f5f9;background:#fafcff;display:flex;flex-direction:column;gap:8px;">
                <!-- Baris 1: Search + Clear -->
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="position:relative;flex:1;">
                        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none;"></i>
                        <input type="text" class="bk-input" id="mappingSearch" placeholder="Search username..." style="padding-left:32px;">
                    </div>
                    <button class="bk-btn bk-btn-ghost" onclick="clearMappingFilters()" title="Reset filter" style="flex-shrink:0;">
                        <i class="ph ph-arrows-counter-clockwise" style="font-size:13px;"></i>
                    </button>
                </div>
                <!-- Baris 2: Server filter -->
                <select class="bk-select" id="serverFilter">
                    <option value="">All Servers</option>
                </select>
            </div>
            <div style="overflow-x:auto;">
                <table class="bk-table">
                    <thead>
                        <tr>
                            <th style="width:50px;" class="hidden-xs">ID</th>
                            <th>User</th>
                            <th>Device</th>
                            <th class="hidden-xs">Server</th>
                            <th class="hidden-xs">Updated</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="mappingTableBody">
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">
                            <i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:18px;display:block;margin-bottom:8px;"></i>
                            Loading mappings...
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="gcs-pagination">
                <div class="gcs-pagination-info" id="mappingInfo">Showing 0 to 0 of 0 entries</div>
                <div class="gcs-page-btns" id="mappingPagination"></div>
            </div>
        </div>
        <div class="gcs-modal-footer">
            <button type="button" class="bk-btn bk-btn-ghost" onclick="closeModal('deviceMappingModal')">
                <i class="ph ph-x" style="font-size:13px;"></i> Close
            </button>
            <button type="button" class="bk-btn bk-btn-danger" onclick="clearAllMappings()">
                <i class="ph ph-trash" style="font-size:13px;"></i> Clear All Cache
            </button>
            <button type="button" class="bk-btn bk-btn-primary" onclick="loadDeviceMappings()">
                <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- ===================== UNINSTALL MODAL ===================== -->
<div class="gcs-modal-overlay" id="uninstallModalOverlay">
    <div class="gcs-modal-card sm">
        <div class="gcs-modal-header red">
            <div>
                <div class="gcs-modal-title"><i class="ph ph-warning-circle"></i> Uninstall GenieACS Manager</div>
                <div class="gcs-modal-subtitle">This action cannot be undone</div>
            </div>
            <button class="gcs-modal-close" onclick="closeUninstallModal()" id="btn-cancel-uninstall-x">×</button>
        </div>
        <div class="gcs-modal-body">
            <div class="gcs-warning-box">
                <div class="gcs-warning-icon"><i class="ph ph-warning" style="font-size:18px;"></i></div>
                <div class="gcs-warning-content">
                    <strong>Peringatan!</strong>
                    <p>Tindakan ini akan menghapus GenieACS Manager secara permanen beserta semua datanya.</p>
                </div>
            </div>

            <div class="gcs-section-header"><i class="ph ph-trash" style="font-size:12px;"></i> Yang Akan Dihapus</div>
            <div class="gcs-delete-grid" style="margin-bottom:18px;">
                <div class="gcs-delete-item"><i class="ph ph-hard-drives"></i> Konfigurasi ACS server</div>
                <div class="gcs-delete-item"><i class="ph ph-device-mobile"></i> Data & mapping device</div>
                <div class="gcs-delete-item"><i class="ph ph-sliders-horizontal"></i> Konfigurasi parameter</div>
                <div class="gcs-delete-item"><i class="ph ph-key"></i> History password & WebAdmin</div>
                <div class="gcs-delete-item"><i class="ph ph-file-code"></i> File environment (.env)</div>
                <div class="gcs-delete-item"><i class="ph ph-clock"></i> File cron synchronization</div>
                <div class="gcs-delete-item"><i class="ph ph-code"></i> Semua file plugin & template</div>
                <div class="gcs-delete-item"><i class="ph ph-paint-brush"></i> Custom UI modifications</div>
            </div>

            <div class="bk-form-group">
                <label class="bk-label"><i class="ph ph-check-circle" style="font-size:10px;"></i> Ketik <code style="background:#fef2f2;color:#dc2626;padding:1px 6px;border-radius:4px;font-family:'DM Mono',monospace;font-size:12px;">UNINSTALL</code> untuk konfirmasi</label>
                <input type="text" class="bk-input" id="uninstall_confirm" placeholder="Ketik UNINSTALL disini" autocomplete="off">
            </div>

            <div id="uninstall-progress-section" style="display:none;">
                <div class="gcs-section-header"><i class="ph ph-terminal" style="font-size:12px;"></i> Progress Uninstall</div>
                <div class="gcs-log-box" id="uninstall-log-content"></div>
            </div>
        </div>
        <div class="gcs-modal-footer">
            <button type="button" class="bk-btn bk-btn-ghost" onclick="closeUninstallModal()" id="btn-cancel-uninstall">
                <i class="ph ph-x" style="font-size:13px;"></i> Batal
            </button>
            <button type="button" class="bk-btn bk-btn-danger" onclick="runUninstall()" id="btn-run-uninstall" style="background:#dc2626;color:white;border:none;">
                <i class="ph ph-trash" style="font-size:13px;"></i> Uninstall Plugin
            </button>
        </div>
    </div>
</div>

<!-- ===================== UPDATE MODAL ===================== -->
<div class="gcs-modal-overlay" id="updateModalOverlay">
    <div class="gcs-modal-card sm">
        <div class="gcs-modal-header blue">
            <div>
                <div class="gcs-modal-title"><i class="ph ph-arrows-clockwise"></i> GenieACS Manager Update</div>
                <div class="gcs-modal-subtitle">Check & apply latest update</div>
            </div>
            <button class="gcs-modal-close" onclick="closeUpdateModal()">×</button>
        </div>
        <div class="gcs-modal-body">
            <!-- Checking -->
            <div id="gcs-update-checking" style="text-align:center;padding:30px 0;display:none;">
                <div class="gcs-spinner" style="margin:0 auto 14px;"></div>
                <div style="font-size:14px;font-weight:600;color:#0f172a;">Checking for updates...</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Connecting to GitHub</div>
            </div>
            <!-- Version info -->
            <div id="gcs-update-version-info" style="display:none;">
                <div class="gcs-version-box">
                    <div><span class="gcs-version-label">Installed</span><span class="gcs-version-num" id="current-version">—</span></div>
                    <i class="ph ph-arrow-right" style="color:#94a3b8;font-size:18px;"></i>
                    <div><span class="gcs-version-label">Latest</span><span class="gcs-version-num new" id="new-version">—</span></div>
                </div>
            </div>
            <!-- Changelog -->
            <div id="gcs-update-changelog" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:14px;">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">Changelog</div>
                <ul id="gcs-changelog-list" style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:6px;"></ul>
            </div>
            <!-- Status -->
            <div id="gcs-update-status" style="text-align:center;padding:14px;font-size:13px;color:#64748b;">
                <i class="ph ph-magnifying-glass"></i> Checking for updates...
            </div>
            <!-- Progress log -->
            <div id="update-log-content" style="display:none;" class="gcs-log-box" style="margin-top:12px;"></div>
        </div>
        <div class="gcs-modal-footer">
            <button onclick="closeUpdateModal()" id="btn-cancel-update" class="bk-btn bk-btn-ghost">Close</button>
            <button id="btn-run-update" onclick="runUpdate()" class="bk-btn bk-btn-primary" style="display:none;">
                <i class="ph ph-download-simple" style="font-size:13px;"></i> Install Update
            </button>
        </div>
    </div>
</div>

<!-- ===================== LICENSE MODAL ===================== -->
<div class="gcs-modal-overlay" id="acs-license-modal">
    <div class="gcs-modal-card sm">
        <div class="gcs-modal-header" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;">
            <div>
                <div class="gcs-modal-title"><i class="ph ph-key"></i> GenieACS Manager License</div>
                <div class="gcs-modal-subtitle">Manage your plugin license</div>
            </div>
            <button class="gcs-modal-close" onclick="acsCloseLicenseModal()">×</button>
        </div>
        <div class="gcs-modal-body">
            <div id="acs-license-status-box" style="display:none;margin-bottom:16px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;border:1px solid transparent;"></div>
            <div class="bk-form-group">
                <label class="bk-label"><i class="ph ph-key" style="font-size:10px;"></i> License Key</label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="acs-license-key-input" placeholder="EXO-XXXX-XXXX-XXXX" class="bk-input" style="font-family:'DM Mono',monospace;letter-spacing:1px;">
                    <button onclick="acsSaveLicenseKey()" id="acs-btn-save-license" class="bk-btn bk-btn-primary" style="flex-shrink:0;">
                        <i class="ph ph-floppy-disk" style="font-size:13px;"></i> Save
                    </button>
                </div>
                <div class="bk-help"><i class="ph ph-info" style="font-size:10px;"></i> Contact ExodiaForb Plugin to obtain your license key</div>
            </div>
            <button onclick="acsCheckLicenseStatus()" id="acs-btn-check-license" class="bk-btn bk-btn-ghost" style="width:100%;">
                <i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Check License Status
            </button>
        </div>
    </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
    // Smarty vars extracted to JS (must be outside literal)
    var _url = '{$_url}';
    var _localStats = {
        cpu: {$local_stats.cpu_usage},
        mem: {$local_stats.mem_usage},
        disk: {$local_stats.disk_usage},
        memUsed: '{$local_stats.mem_used_gb}',
        memTotal: '{$local_stats.mem_total_gb}',
        diskUsed: '{$local_stats.disk_used_gb}',
        diskTotal: '{$local_stats.disk_total_gb}',
        load1: '{$local_stats.load_1min}',
        uptime: '{$local_stats.uptime_string}'
    };
</script>
<script>
{literal}
    var editMode = false;

    // Helper: build colored ping badge
    function buildPingBadge(ms) {
        ms = parseFloat(ms);
        if (!ms || isNaN(ms)) return '<span class="gcs-ping gcs-ping-none"><i class="ph ph-wifi-slash" style="font-size:11px;"></i> —</span>';
        if (ms <= 100) return '<span class="gcs-ping gcs-ping-fast"><i class="ph ph-wifi-high" style="font-size:11px;"></i> ' + Math.round(ms) + 'ms</span>';
        if (ms <= 500) return '<span class="gcs-ping gcs-ping-medium"><i class="ph ph-wifi-medium" style="font-size:11px;"></i> ' + Math.round(ms) + 'ms</span>';
        return '<span class="gcs-ping gcs-ping-slow"><i class="ph ph-wifi-low" style="font-size:11px;"></i> ' + Math.round(ms) + 'ms</span>';
    }

    // ---- Modal helpers ----
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.documentElement.classList.add('modal-open');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        // Only remove lock if no other modals are open
        var anyOpen = document.querySelector('.gcs-modal-overlay.open');
        if (!anyOpen) {
            document.documentElement.classList.remove('modal-open');
        }
    }

    // Close modal on overlay click
    document.querySelectorAll('.gcs-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    // ---- Loading ----
    function showLoading(msg) {
        document.getElementById('gcs-loading-text').textContent = msg || 'Processing...';
        document.getElementById('gcs-loading').style.display = 'flex';
    }
    function hideLoading() { document.getElementById('gcs-loading').style.display = 'none'; }

    // ---- Notifications ----
    function showNotification(message, type) {
        type = type || 'info';
        var icons = { success:'ph-check-circle', error:'ph-x-circle', warning:'ph-warning-circle', info:'ph-info' };
        var notif = document.createElement('div');
        notif.className = 'gcs-notif ' + type;
        notif.innerHTML = '<div class="gcs-notif-icon"><i class="ph ' + (icons[type]||'ph-info') + '"></i></div>' +
            '<div class="gcs-notif-text">' + message + '</div>' +
            '<div class="gcs-notif-close" onclick="this.parentElement.remove()"><i class="ph ph-x"></i></div>';
        document.getElementById('gcs-notif-container').appendChild(notif);
        setTimeout(function() { if (notif.parentElement) { notif.style.opacity='0'; notif.style.transform='translateX(20px)'; notif.style.transition='all 0.3s'; setTimeout(function(){notif.remove();},300); } }, 5000);
    }

    // ---- Server CRUD ----
    function showAddServerModal() {
        editMode = false;
        document.getElementById('serverModalTitle').textContent = 'Add ACS Server';
        document.getElementById('serverForm').reset();
        document.getElementById('server_id').value = 0;
        openModal('serverModal');
    }

    function closeServerModal() { closeModal('serverModal'); }

    function editServer(id) {
        editMode = true;
        document.getElementById('serverModalTitle').textContent = 'Edit ACS Server';
        $.ajax({
            url: _url + 'plugin/genieacs_manager/get-server',
            type: 'GET', data: { id: id }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#server_id').val(response.server.id);
                    $('#server_name').val(response.server.name);
                    $('#server_host').val(response.server.host);
                    $('#server_port').val(response.server.port);
                    $('#server_username').val(response.server.username);
                    $('#server_password').val(response.server.password);
                    openModal('serverModal');
                } else { Swal.fire('Error!', response.message, 'error'); }
            },
            error: function() { Swal.fire('Error!', 'Failed to load server data', 'error'); }
        });
    }

    function saveServer() {
        var formData = $('#serverForm').serialize();
        var url = editMode ? _url + 'plugin/genieacs_manager/edit-server' : _url + 'plugin/genieacs_manager/add-server';
        $.ajax({
            url: url, type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    closeModal('serverModal');
                    Swal.fire('Success!', response.message, 'success').then(() => location.reload());
                } else { Swal.fire('Error!', response.message, 'error'); }
            },
            error: function() { Swal.fire('Error!', 'Failed to save server', 'error'); }
        });
    }

    function deleteServer(id) {
        Swal.fire({ title: 'Delete Server?', text: 'This will remove the server and all related data!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it!' }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: _url + 'plugin/genieacs_manager/delete-server', type: 'GET', data: { id: id }, dataType: 'json',
                    success: function(response) {
                        if (response.success) { Swal.fire('Deleted!', response.message, 'success').then(() => location.reload()); }
                        else { Swal.fire('Error!', response.message, 'error'); }
                    },
                    error: function() { Swal.fire('Error!', 'Failed to delete server', 'error'); }
                });
            }
        });
    }

    function testConnection(id) {
        var statusCell = $('#status-' + id);
        statusCell.html('<span class="gcs-status gcs-status-testing"><i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:10px;"></i> Testing...</span>');
        var serverName = $('.server-row[data-server-id="' + id + '"] td:eq(2)').text().trim();
        logActivity('info', 'Testing connection to ' + serverName, id);
        $.ajax({
            url: _url + 'plugin/genieacs_manager/test-connection', type: 'GET', data: { id: id }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusCell.html('<span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>');
                    $('#status-mobile-' + id).html('<span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>');
                    logActivity('success', serverName + ' is online', id);
                    if (response.response_time) { var rt=response.response_time; $('#response-time-'+id).html(buildPingBadge(rt)); $('#response-time-mobile-'+id).text(rt+'ms'); }
                } else {
                    statusCell.html('<span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>');
                    $('#status-mobile-' + id).html('<span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>');
                    logActivity('error', serverName + ' is offline', id);
                }
            },
            error: function() {
                statusCell.html('<span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>');
                logActivity('error', 'Failed to test ' + serverName, id);
            }
        });
    }

    function viewServerDevices(serverId) {
        $.ajax({
            url: _url + 'plugin/genieacs_devices/set-server', type: 'POST', data: { server_id: serverId }, dataType: 'json',
            success: function(response) {
                if (response.success) window.location.href = _url + 'plugin/genieacs_devices';
                else Swal.fire('Error!', 'Failed to set server', 'error');
            },
            error: function() { Swal.fire('Error!', 'Failed to communicate with server', 'error'); }
        });
    }

    function testAndSaveServer() {
        var formData = $('#serverForm').serialize();
        showLoading('Testing connection...');
        $.ajax({
            url: _url + 'plugin/genieacs_manager/test-connection-temp', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    Swal.fire({ title: 'Connection Successful!', text: response.message, icon: 'success', showCancelButton: true, confirmButtonText: 'Save Server', cancelButtonText: 'Cancel' }).then((result) => { if (result.isConfirmed) saveServer(); });
                } else { Swal.fire('Connection Failed!', response.message, 'error'); }
            },
            error: function() { hideLoading(); Swal.fire('Error!', 'Connection test failed', 'error'); }
        });
    }

    function togglePassword() {
        var input = document.getElementById('server_password');
        var icon = document.getElementById('password-toggle-icon');
        if (input.type === 'password') { input.type = 'text'; icon.className = 'ph ph-eye-slash'; }
        else { input.type = 'password'; icon.className = 'ph ph-eye'; }
    }

    // ---- Priority ----
    function togglePriority(serverId) {
        $.ajax({
            url: _url + 'plugin/genieacs_manager/toggle-priority', type: 'POST', data: { server_id: serverId }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('.priority-toggle[data-server-id="' + serverId + '"]').each(function() {
                        if (response.is_priority) { $(this).removeClass('ph-star gcs-star-off').addClass('ph-star-fill gcs-star-on'); }
                        else { $(this).removeClass('ph-star-fill gcs-star-on').addClass('ph-star gcs-star-off'); }
                    });
                    setTimeout(() => location.reload(), 500);
                }
            }
        });
    }

    // ---- Test All ----
    function testAllConnections() {
        showLoading('Testing all server connections...');
        logActivity('info', 'Started testing all connections', null);
        $.ajax({
            url: _url + 'plugin/genieacs_manager/test-all', type: 'GET', dataType: 'json',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    var s = response.summary;
                    showNotification('Tested ' + s.total + ' servers: ' + s.online + ' online, ' + s.offline + ' offline', s.offline > 0 ? 'warning' : 'success');
                    logActivity(s.offline > 0 ? 'warning' : 'success', 'Test: ' + s.online + '/' + s.total + ' online', null);
                    response.results.forEach(function(r) {
                        var html = r.success ?
                            '<span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>' :
                            '<span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>';
                        $('#status-' + r.id).html(html);
                    });
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function() { hideLoading(); showNotification('Failed to test connections', 'error'); logActivity('error', 'Failed to test all connections', null); }
        });
    }

    // ---- Device Counts ----
    function loadDeviceCounts() {
        var serverIds = [];
        $('.server-row:visible').each(function() { var id = $(this).data('server-id'); if (id) serverIds.push(id); });
        $('.mobile-server-card:visible').each(function() { var id = $(this).data('server-id'); if (id && serverIds.indexOf(id) === -1) serverIds.push(id); });
        if (!serverIds.length) return;
        $.ajax({
            url: _url + 'plugin/genieacs_manager/batch-device-counts-optimized', type: 'POST',
            data: { server_ids: serverIds }, dataType: 'json', timeout: 15000,
            success: function(response) {
                if (response.success) {
                    for (var sid in response.results) {
                        var count = response.results[sid].count || '-';
                        $('#device-count-' + sid).html('<i class="ph ph-device-mobile" style="font-size:10px;"></i> ' + count);
                        $('#device-count-mobile-' + sid).html('<i class="ph ph-device-mobile" style="font-size:10px;"></i> ' + count);
                    }
                    updateTotalDevicesCount();
                }
            },
            error: function() {}
        });
    }

    function refreshDeviceCount(serverId) {
        $('#device-count-' + serverId).html('<i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:10px;"></i>');
        $.ajax({
            url: _url + 'plugin/genieacs_manager/get-device-count', type: 'GET',
            data: { server_id: serverId, force: true }, dataType: 'json', timeout: 10000,
            success: function(r) {
                if (r.success) {
                    var html = '<i class="ph ph-device-mobile" style="font-size:10px;"></i> ' + r.count;
                    $('#device-count-' + serverId).html(html);
                    $('#device-count-mobile-' + serverId).html(html);
                    showNotification('Device count refreshed: ' + r.count, 'success');
                    updateTotalDevicesCount();
                } else { $('#device-count-' + serverId).text('-'); }
            },
            error: function() { $('#device-count-' + serverId).text('—'); showNotification('Failed to refresh device count', 'error'); }
        });
    }

    function updateTotalDevicesCount() {
        var total = 0, counted = [];
        $('.gcs-device-badge:visible').each(function() {
            var text = $(this).text().replace(/[^0-9]/g,'').trim();
            var id = $(this).attr('id');
            if (text && !isNaN(parseInt(text)) && counted.indexOf(id) === -1) { total += parseInt(text); counted.push(id); }
        });
        $('#total-devices-count').text(total);
    }

    // ---- Auto Check Status ----
    var serverStatusHistory = {}, statusUpdatePending = {};
    function autoCheckServerStatus() {
        var servers = [];
        $('.server-row:visible').each(function() { servers.push($(this).data('server-id')); });
        var batchSize = 3, index = 0;
        function processBatch() {
            var batch = servers.slice(index, index + batchSize);
            batch.forEach(function(sid) { checkServerStatus(sid); });
            index += batchSize;
            if (index < servers.length) setTimeout(processBatch, 2000);
        }
        processBatch();
    }

    function checkServerStatus(serverId) {
        if (statusUpdatePending[serverId]) return;
        statusUpdatePending[serverId] = true;
        $.ajax({
            url: _url + 'plugin/genieacs_manager/test-connection', type: 'GET',
            data: { id: serverId, silent: true }, dataType: 'json', timeout: 8000,
            success: function(r) {
                handleStatusResponse(serverId, r.success);
                if (r.response_time > 0) { $('#response-time-' + serverId).html(buildPingBadge(r.response_time)); }
                statusUpdatePending[serverId] = false;
            },
            error: function() { handleStatusResponse(serverId, false); statusUpdatePending[serverId] = false; }
        });
    }

    function handleStatusResponse(serverId, isOnline) {
        if (!serverStatusHistory[serverId]) serverStatusHistory[serverId] = { current: null, previous: null, changeCount: 0, lastChange: Date.now() };
        var history = serverStatusHistory[serverId];
        var currentStatus = isOnline ? 'online' : 'offline';
        var displayedStatus = $('#status-' + serverId).find('.gcs-status-online').length > 0 ? 'online' : 'offline';
        if (currentStatus !== displayedStatus) {
            if (currentStatus !== history.current) {
                history.previous = history.current; history.current = currentStatus; history.changeCount++;
                if (Date.now() - history.lastChange > 10000 || history.changeCount >= 3) {
                    updateServerStatus(serverId, isOnline); history.changeCount = 0; history.lastChange = Date.now();
                }
            }
        } else { history.changeCount = 0; history.current = currentStatus; }
    }

    function updateServerStatus(serverId, isOnline) {
        var html = isOnline ?
            '<span class="gcs-status gcs-status-online"><span class="gcs-pulse gcs-pulse-green"></span> Online</span>' :
            '<span class="gcs-status gcs-status-offline"><span class="gcs-pulse gcs-pulse-red"></span> Offline</span>';
        $('#status-' + serverId).html(html);
        $('#status-mobile-' + serverId).html(html);
    }

    // ---- Filters ----
    function applyFilters() {
        var search = $('#serverSearch').val().toLowerCase();
        var status = $('#statusFilter').val();
        var protocol = $('#protocolFilter').val();
        var priority = $('#priorityFilter').val();
        var visible = 0, total = 0;

        $('.server-row').each(function() {
            total++;
            var $r = $(this);
            var name = $r.find('td:eq(2)').text().toLowerCase();
            var host = $r.find('td:eq(3)').text().toLowerCase();
            var isOnline = $r.find('.gcs-status-online').length > 0;
            var isHttps = $r.find('.gcs-proto-https').length > 0;
            var isPriority = $r.hasClass('gcs-priority-row');
            var ms = !search || name.indexOf(search) > -1 || host.indexOf(search) > -1;
            var mst = !status || (status === 'online' && isOnline) || (status === 'offline' && !isOnline);
            var mp = !protocol || (protocol === 'https' && isHttps) || (protocol === 'http' && !isHttps);
            var mpr = !priority || (priority === 'priority' && isPriority) || (priority === 'normal' && !isPriority);
            if (ms && mst && mp && mpr) { $r.show(); visible++; } else { $r.hide(); }
        });
        $('.mobile-server-card').each(function() {
            var $c = $(this);
            var name = $c.find('.gcs-modal-title strong, span[style*="font-weight:700"]').text().toLowerCase();
            var isOnline = $c.find('.gcs-status-online').length > 0;
            var isHttps = $c.find('.gcs-proto-https').length > 0;
            var isPriority = $c.hasClass('priority');
            var ms = !search || name.indexOf(search) > -1;
            var mst = !status || (status === 'online' && isOnline) || (status === 'offline' && !isOnline);
            var mp = !protocol || (protocol === 'https' && isHttps) || (protocol === 'http' && !isHttps);
            var mpr = !priority || (priority === 'priority' && isPriority) || (priority === 'normal' && !isPriority);
            if (ms && mst && mp && mpr) $c.show(); else $c.hide();
        });
    }

    function clearFilters() {
        showLoading('Clearing filters...');
        setTimeout(function() {
            $('#serverSearch,#statusFilter,#protocolFilter,#priorityFilter').val('');
            localStorage.removeItem('acs_filters');
            applyFilters();
            setTimeout(() => location.reload(), 500);
        }, 400);
    }

    // ---- Activity Feed ----
    var activityLog = [];
    function logActivity(type, message, serverId) {
        activityLog.unshift({ type: type, message: message, serverId: serverId, timestamp: new Date() });
        if (activityLog.length > 50) activityLog.pop();
        localStorage.setItem('acs_activity_log', JSON.stringify(activityLog));
        updateActivityFeed();
    }
    function loadActivityLog() {
        var saved = localStorage.getItem('acs_activity_log');
        if (saved) { try { activityLog = JSON.parse(saved); activityLog.forEach(function(i) { i.timestamp = new Date(i.timestamp); }); } catch(e) {} }
    }
    function updateActivityFeed() {
        var icons = { success:'ph-check-circle', error:'ph-x-circle', warning:'ph-warning-circle', info:'ph-info' };
        var colors = { success:'#dcfce7', error:'#fee2e2', warning:'#fef3c7', info:'#dbeafe' };
        var textColors = { success:'#16a34a', error:'#dc2626', warning:'#d97706', info:'#0271c6' };
        var count = Math.min(activityLog.length, 5);
        var html = '';
        if (!count) {
            html = '<div class="gcs-activity-item"><div class="gcs-activity-dot" style="background:#f1f5f9;"><i class="ph ph-info" style="color:#94a3b8;font-size:10px;"></i></div><div class="gcs-activity-text">No recent activity</div><div class="gcs-activity-time">—</div></div>';
        } else {
            for (var i = 0; i < count; i++) {
                var a = activityLog[i];
                var t = a.type || 'info';
                var secs = Math.floor((new Date() - a.timestamp) / 1000);
                var timeStr = secs < 60 ? 'now' : secs < 3600 ? Math.floor(secs/60)+'m' : secs < 86400 ? Math.floor(secs/3600)+'h' : Math.floor(secs/86400)+'d';
                html += '<div class="gcs-activity-item">' +
                    '<div class="gcs-activity-dot" style="background:' + (colors[t]||'#dbeafe') + ';"><i class="ph ' + (icons[t]||'ph-info') + '" style="color:' + (textColors[t]||'#0271c6') + ';font-size:10px;"></i></div>' +
                    '<div class="gcs-activity-text">' + a.message + '</div>' +
                    '<div class="gcs-activity-time">' + timeStr + '</div>' +
                    '</div>';
            }
        }
        document.getElementById('activity-list').innerHTML = html;
    }
    function refreshActivity() {
        var icon = document.getElementById('activity-refresh-icon');
        icon.style.animation = 'gcsSpin 0.7s linear infinite';
        updateActivityFeed();
        setTimeout(function() { icon.style.animation = ''; }, 700);
    }

    // ---- Local Stats ----
    function drawGauge(ctx, value, size) {
        var cx = size/2, cy = size/2, r = size/2 - 8;
        ctx.clearRect(0, 0, size, size);
        ctx.beginPath(); ctx.arc(cx, cy, r, Math.PI*0.7, Math.PI*2.3); ctx.lineWidth = 7; ctx.strokeStyle = '#e2e8f0'; ctx.stroke();
        var color = value < 60 ? '#22c55e' : value < 80 ? '#f59e0b' : '#ef4444';
        ctx.beginPath(); ctx.arc(cx, cy, r, Math.PI*0.7, Math.PI*0.7 + Math.PI*1.6*value/100);
        ctx.lineWidth = 7; ctx.strokeStyle = color; ctx.lineCap = 'round'; ctx.stroke();
        ctx.fillStyle = '#0f172a'; ctx.font = 'bold 14px DM Sans, sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(Math.round(value)+'%', cx, cy);
    }
    function initServerGauges() {
        ['cpu','mem','disk'].forEach(function(name) {
            var c = document.getElementById(name+'Gauge');
            if (c) drawGauge(c.getContext('2d'), [_localStats.cpu, _localStats.mem, _localStats.disk][["cpu","mem","disk"].indexOf(name)], 72);
        });
    }
    function refreshLocalStats() {
        var icon = document.getElementById('local-refresh-icon');
        icon.style.animation = 'gcsSpin 0.7s linear infinite';
        $.ajax({
            url: _url + 'plugin/genieacs_manager/get-local-stats', type: 'GET', dataType: 'json',
            success: function(r) {
                if (r.success) {
                    var s = r.stats;
                    drawGauge(document.getElementById('cpuGauge').getContext('2d'), s.cpu_usage, 72); $('#cpu-text').text(s.cpu_usage+'%');
                    drawGauge(document.getElementById('memGauge').getContext('2d'), s.mem_usage, 72); $('#mem-text').text(s.mem_usage+'%'); $('#mem-detail').text(s.mem_used_gb+'/'+s.mem_total_gb+'GB');
                    drawGauge(document.getElementById('diskGauge').getContext('2d'), s.disk_usage, 72); $('#disk-text').text(s.disk_usage+'%'); $('#disk-detail').text(s.disk_used_gb+'/'+s.disk_total_gb+'GB');
                    $('#load-info').text('Load: '+s.load_1min); $('#uptime-info').text(s.uptime_string);
                }
                icon.style.animation = '';
            },
            error: function() { icon.style.animation = ''; }
        });
    }

    // ---- Device Mapping ----
    var mappingCurrentPage = 1, mappingSearch = '', mappingServerFilter = '';
    function showDeviceMappingModal() { openModal('deviceMappingModal'); loadDeviceMappings(1); }
    function loadDeviceMappings(page) {
        page = page || mappingCurrentPage; mappingCurrentPage = page;
        $('#mappingTableBody').html('<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;"><i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:16px;"></i></td></tr>');
        $.ajax({
            url: _url + 'plugin/genieacs_manager/get-device-mappings', type: 'GET',
            data: { page: page, search: mappingSearch, server_filter: mappingServerFilter }, dataType: 'json',
            success: function(r) {
                if (r.success) { updateMappingTable(r.mappings); updateMappingPagination(r.pagination); updateServerFilter(r.servers); }
                else { $('#mappingTableBody').html('<tr><td colspan="6" style="text-align:center;color:#dc2626;padding:20px;">Failed to load mappings</td></tr>'); }
            },
            error: function() { $('#mappingTableBody').html('<tr><td colspan="6" style="text-align:center;color:#dc2626;padding:20px;">Error loading mappings</td></tr>'); }
        });
    }
    function updateServerFilter(servers) {
        var s = document.getElementById('serverFilter'); var v = s.value; s.innerHTML = '<option value="">All Servers</option>';
        servers.forEach(function(srv) { s.innerHTML += '<option value="'+srv.id+'">'+srv.name+'</option>'; });
        if (v) s.value = v;
    }
    function updateMappingTable(mappings) {
        if (!mappings.length) { $('#mappingTableBody').html('<tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;"><i class="ph ph-database" style="font-size:24px;display:block;margin-bottom:8px;"></i>No device mappings found</td></tr>'); return; }
        var html = '';
        mappings.forEach(function(m) {
            var serverName = m.server_name || 'Server '+m.server_id;
            var devDisplay = m.vendor ? '<span style="font-size:12px;font-weight:600;color:#16a34a;">'+m.vendor+'</span><br><span style="font-size:11px;color:#94a3b8;">'+m.device_id.substring(0,25)+'...</span>' : '<span style="font-size:12px;color:#64748b;">'+m.device_id.substring(0,35)+'</span>';
            var updated = new Date(m.last_updated).toLocaleString();
            html += '<tr><td class="hidden-xs" style="color:#94a3b8;font-size:12px;">'+m.id+'</td>' +
                '<td><span style="font-size:13px;font-weight:600;color:#0271c6;">'+m.username+'</span></td>' +
                '<td title="'+m.device_id+'">'+devDisplay+'</td>' +
                '<td class="hidden-xs"><span style="font-size:11px;background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:20px;">'+serverName+'</span></td>' +
                '<td class="hidden-xs"><span style="font-size:11px;color:#94a3b8;">'+updated+'</span></td>' +
                '<td style="text-align:right;"><button class="bk-btn bk-btn-danger bk-btn-sm" onclick="deleteMapping('+m.id+')"><i class="ph ph-trash" style="font-size:11px;"></i></button></td></tr>';
        });
        $('#mappingTableBody').html(html);
    }
    function updateMappingPagination(p) {
        var start = (p.current_page-1)*p.per_page+1, end = Math.min(p.current_page*p.per_page, p.total_count);
        $('#mappingInfo').text('Showing '+start+' to '+end+' of '+p.total_count+' entries');
        var html = '';
        if (p.total_pages > 1) {
            if (p.current_page > 1) html += '<a class="gcs-page-btn" href="#" onclick="loadDeviceMappings('+(p.current_page-1)+');return false;"><i class="ph ph-caret-left" style="font-size:11px;"></i></a>';
            for (var i = 1; i <= p.total_pages; i++) html += '<a class="gcs-page-btn '+(i===p.current_page?'active':'')+'" href="#" onclick="loadDeviceMappings('+i+');return false;">'+i+'</a>';
            if (p.current_page < p.total_pages) html += '<a class="gcs-page-btn" href="#" onclick="loadDeviceMappings('+(p.current_page+1)+');return false;"><i class="ph ph-caret-right" style="font-size:11px;"></i></a>';
        }
        $('#mappingPagination').html(html);
    }
    function clearMappingFilters() { $('#mappingSearch,#serverFilter').val(''); mappingSearch=''; mappingServerFilter=''; loadDeviceMappings(1); }
    function deleteMapping(id) {
        Swal.fire({ title:'Delete Mapping?', text:'This will clear device cache for this user', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', confirmButtonText:'Yes, delete!' }).then((r) => {
            if (r.isConfirmed) $.ajax({ url: _url + 'plugin/genieacs_manager/delete-device-mapping', type:'POST', data:{id:id}, dataType:'json', success:function(res){ if(res.success){Swal.fire('Deleted!',res.message,'success'); loadDeviceMappings();}else{Swal.fire('Error!',res.message,'error');} }, error:function(){Swal.fire('Error!','Failed to delete mapping','error');} });
        });
    }
    function clearAllMappings() {
        Swal.fire({ title:'Clear All Mappings?', text:'This will delete ALL device cache entries.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', confirmButtonText:'Yes, clear all!' }).then((r) => {
            if (r.isConfirmed) $.ajax({ url: _url + 'plugin/genieacs_manager/clear-all-mappings', type:'POST', dataType:'json', success:function(res){ if(res.success){Swal.fire('Cleared!',res.message,'success'); loadDeviceMappings();}else{Swal.fire('Error!',res.message,'error');} }, error:function(){Swal.fire('Error!','Failed to clear mappings','error');} });
        });
    }
    $('#mappingSearch').on('keyup', function() { mappingSearch=$(this).val(); loadDeviceMappings(1); });
    $('#serverFilter').on('change', function() { mappingServerFilter=$(this).val(); loadDeviceMappings(1); });

    // ---- Uninstall ----
    function showUninstallModal() {
        $('#uninstall_confirm').val('');
        $('#uninstall-progress-section').hide();
        $('#uninstall-log-content').html('');
        $('#btn-run-uninstall').prop('disabled',false).html('<i class="ph ph-trash" style="font-size:13px;"></i> Uninstall Plugin');
        $('#btn-cancel-uninstall').show();
        $('#btn-cancel-uninstall-x').show();
        openModal('uninstallModalOverlay');
    }
    function closeUninstallModal() { closeModal('uninstallModalOverlay'); }
    function runUninstall() {
        if ($('#uninstall_confirm').val().trim() !== 'UNINSTALL') { Swal.fire('Konfirmasi Diperlukan','Ketik UNINSTALL untuk melanjutkan','warning'); return; }
        Swal.fire({ title:'Konfirmasi Terakhir', html:'<p>Apakah Anda yakin?</p><p><strong>Tidak dapat dibatalkan!</strong></p>', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', confirmButtonText:'Ya, Uninstall!', cancelButtonText:'Batal' }).then((r) => { if (r.isConfirmed) executeUninstall(); });
    }
    function executeUninstall() {
        $('#btn-run-uninstall').prop('disabled',true).html('<i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:13px;"></i> Uninstalling...');
        $('#btn-cancel-uninstall').hide(); $('#btn-cancel-uninstall-x').hide();
        $('#uninstall-progress-section').show();
        addUninstallLog('Memulai proses uninstall...','info');
        $.ajax({
            url: _url + 'plugin/genieacs_manager/uninstall', type:'POST', dataType:'json',
            success:function(r){
                if(r.success){
                    if(r.details) r.details.forEach(function(d,i){setTimeout(function(){addUninstallLog(d,'success');},i*300);});
                    var delay=(r.details?r.details.length*300:0)+1000;
                    setTimeout(function(){closeUninstallModal();Swal.fire({title:'Berhasil!',text:r.message,icon:'success',confirmButtonText:'Ke Dashboard'}).then(()=>window.location.href= _url + 'dashboard');},delay);
                } else { addUninstallLog('Error: '+r.message,'error'); $('#btn-run-uninstall').prop('disabled',false).html('<i class="ph ph-trash" style="font-size:13px;"></i> Coba Lagi'); $('#btn-cancel-uninstall').show(); }
            },
            error:function(x,s,e){ addUninstallLog('Koneksi error: '+e,'error'); $('#btn-run-uninstall').prop('disabled',false).html('<i class="ph ph-trash" style="font-size:13px;"></i> Coba Lagi'); $('#btn-cancel-uninstall').show(); }
        });
    }
    function addUninstallLog(msg,type){
        var icons={success:'ph-check',error:'ph-x','info':'ph-info'};
        var item='<div class="gcs-log-item '+type+'"><i class="ph '+(icons[type]||'ph-info')+'" style="font-size:11px;flex-shrink:0;margin-top:2px;"></i><span>'+msg+'</span></div>';
        var box=document.getElementById('uninstall-log-content'); box.innerHTML+=item; box.scrollTop=box.scrollHeight;
    }

    // ---- Update ----
    function checkForUpdate() {
        document.getElementById('update-status').textContent = 'Checking...';
        openModal('updateModalOverlay');
        document.getElementById('gcs-update-checking').style.display = 'block';
        document.getElementById('gcs-update-version-info').style.display = 'none';
        document.getElementById('gcs-update-changelog').style.display = 'none';
        document.getElementById('gcs-update-status').style.display = 'none';
        document.getElementById('update-log-content').style.display = 'none';
        document.getElementById('btn-run-update').style.display = 'none';
        $.ajax({
            url: _url + 'plugin/genieacs_manager/check-update', type:'GET', dataType:'json',
            success:function(r){
                document.getElementById('gcs-update-checking').style.display='none';
                document.getElementById('gcs-update-status').style.display='block';
                document.getElementById('gcs-update-version-info').style.display='block';
                document.getElementById('current-version').textContent=r.current_version||'—';
                document.getElementById('new-version').textContent=r.latest_version||'—';
                if(r.has_update){
                    document.getElementById('gcs-update-status').innerHTML='<span style="color:#16a34a;font-weight:600;"><i class="ph ph-check-circle"></i> Update tersedia!</span>';
                    document.getElementById('btn-run-update').style.display='inline-flex';
                    if(r.changelog&&r.changelog.length){
                        var cl=document.getElementById('gcs-update-changelog'); cl.style.display='block';
                        document.getElementById('gcs-changelog-list').innerHTML=r.changelog.map(function(c){return '<li style="display:flex;align-items:flex-start;gap:8px;padding:6px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#475569;margin-bottom:4px;"><i class="ph ph-check-circle" style="color:#16a34a;font-size:13px;flex-shrink:0;margin-top:1px;"></i>'+c+'</li>';}).join('');
                    }
                    $('#btn-check-update .gcs-qa-desc').text('Update available!').css('color','#16a34a');
                } else {
                    document.getElementById('gcs-update-status').innerHTML='<span style="color:#16a34a;"><i class="ph ph-check-circle"></i> '+r.message+'</span>';
                    $('#btn-check-update .gcs-qa-desc').text('Up to date');
                }
                var icon=$('#btn-check-update .gcs-qa-icon i');
                icon.removeClass().addClass('ph ph-arrows-clockwise');
            },
            error:function(){
                document.getElementById('gcs-update-checking').style.display='none';
                document.getElementById('gcs-update-status').style.display='block';
                document.getElementById('gcs-update-status').innerHTML='<span style="color:#dc2626;"><i class="ph ph-x-circle"></i> Failed to check updates</span>';
            }
        });
    }
    function closeUpdateModal(){ closeModal('updateModalOverlay'); }
    function runUpdate(){
        var log=document.getElementById('update-log-content'); log.style.display='block'; log.innerHTML='';
        document.getElementById('btn-run-update').disabled=true;
        document.getElementById('btn-run-update').innerHTML='<i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;"></i> Installing...';
        var steps=[{action:'backup',label:'Creating backup...'},{action:'download',label:'Downloading update...'},{action:'install',label:'Installing files...'},{action:'cleanup',label:'Cleaning up...'}];
        var index=0;
        function showNext(){
            if(index>=steps.length) return;
            var step=steps[index++];
            var item=document.createElement('div');
            item.className='gcs-log-item info';
            item.innerHTML='<i class="ph ph-info" style="font-size:11px;flex-shrink:0;margin-top:2px;"></i><span>'+step.label+'</span>';
            log.appendChild(item); log.scrollTop=log.scrollHeight;
            $.ajax({
                url: _url + 'plugin/genieacs_manager/run-update', type:'POST', data:{action:step.action}, dataType:'json',
                success:function(r){
                    item.className='gcs-log-item '+(r.success?'success':'error');
                    item.querySelector('i').className='ph '+(r.success?'ph-check':'ph-x')+' ';
                    if(r.success){ if(index<steps.length){setTimeout(showNext,500);}else{log.innerHTML+='<div class="gcs-log-item success"><i class="ph ph-check-circle" style="font-size:11px;"></i><span>Update selesai! Reload halaman...</span></div>'; setTimeout(()=>location.reload(),2000);} }
                    else { log.innerHTML+='<div class="gcs-log-item error"><i class="ph ph-x-circle" style="font-size:11px;"></i><span>'+r.message+'</span></div>'; document.getElementById('btn-run-update').disabled=false; document.getElementById('btn-run-update').innerHTML='<i class="ph ph-download-simple"></i> Retry'; }
                },
                error:function(x,s,e){ item.className='gcs-log-item error'; log.innerHTML+='<div class="gcs-log-item error"><i class="ph ph-x-circle" style="font-size:11px;"></i><span>Connection error: '+e+'</span></div>'; document.getElementById('btn-run-update').disabled=false; document.getElementById('btn-run-update').innerHTML='<i class="ph ph-download-simple"></i> Retry'; }
            });
        }
        showNext();
    }

    // ---- License ----
    function acsLoadCurrentLicenseKey() {
        $.get(window.location.href.split('?')[0]+'?_route=plugin/genieacs_manager&action=get_license_key', function(d){ if(d.success&&d.license_key) $('#acs-license-key-input').val(d.license_key); });
    }
    function acsSaveLicenseKey() {
        var key=$('#acs-license-key-input').val().trim(), btn=$('#acs-btn-save-license'), box=$('#acs-license-status-box');
        if(!key){ box.show().css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fecaca'}).html('<i class="ph ph-x-circle" style="margin-right:6px;"></i>License key is required'); return; }
        btn.prop('disabled',true).html('<i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;"></i> Saving...');
        $.post(window.location.href,{action:'save_license_key',license_key:key},function(d){
            btn.prop('disabled',false).html('<i class="ph ph-floppy-disk"></i> Save'); box.show();
            if(d.success){box.css({'background':'#f0fdf4','color':'#15803d','border':'1px solid #bbf7d0'}).html('<i class="ph ph-check-circle" style="margin-right:6px;"></i>License key saved successfully'); acsCheckLicenseStatus();}
            else{box.css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fecaca'}).html('<i class="ph ph-x-circle" style="margin-right:6px;"></i>'+(d.error||'Failed to save'));}
        },'json').fail(function(){btn.prop('disabled',false).html('<i class="ph ph-floppy-disk"></i> Save');box.show().css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fecaca'}).html('<i class="ph ph-x-circle" style="margin-right:6px;"></i>Request failed');});
    }
    function acsCheckLicenseStatus() {
        var btn=$('#acs-btn-check-license'), box=$('#acs-license-status-box');
        btn.prop('disabled',true).html('<i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;font-size:13px;"></i> Checking...');
        box.show().css({'background':'#f8fafc','color':'#475569','border':'1px solid #e2e8f0'});
        var steps=['Looking up license key...','Connecting to license server...','Verifying domain & credentials...','Finalizing validation...'];
        steps.forEach(function(s,i){setTimeout(function(){if(btn.prop('disabled'))box.html('<span style="display:inline-flex;align-items:center;gap:8px;"><i class="ph ph-spinner" style="animation:gcsSpin 1s linear infinite;color:#0271c6;font-size:13px;"></i><span>'+s+'</span></span>');},i*600);});
        setTimeout(function(){
            $.get(window.location.href.split('?')[0]+'?_route=plugin/genieacs_manager&action=check_license_status',function(d){
                btn.prop('disabled',false).html('<i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Check License Status');
                if(d.valid){box.css({'background':'#f0fdf4','color':'#15803d','border':'1px solid #bbf7d0'}).html('<i class="ph ph-check-circle" style="margin-right:6px;"></i>License VALID — '+(d.client_name||'')+(d.expired_at?' | Expires: '+d.expired_at:' | Unlimited'));}
                else{box.css({'background':'#fef2f2','color':'#dc2626','border':'1px solid #fecaca'}).html('<i class="ph ph-x-circle" style="margin-right:6px;"></i>'+(d.message||'License invalid'));}
            },'json').fail(function(){btn.prop('disabled',false).html('<i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Check License Status');box.css({'background':'#fff7ed','color':'#92400e','border':'1px solid #fed7aa'}).html('<i class="ph ph-warning-circle" style="margin-right:6px;"></i>Could not connect to license server');});
        },2400);
    }
    function acsOpenLicenseModal() { openModal('acs-license-modal'); acsLoadCurrentLicenseKey(); acsCheckLicenseStatus(); }
    function acsCloseLicenseModal() { closeModal('acs-license-modal'); }

    // ---- Per Page Selector ----
    $('#perPageSelector').on('change', function() {
        showLoading('Changing page size...');
        setTimeout(function() { window.location.href = _url + 'plugin/genieacs_manager?page=1&per_page=' + $('#perPageSelector').val(); }, 300);
    });

    // ---- Keyboard shortcuts ----
    $(document).keydown(function(e) {
        if (e.altKey && e.keyCode === 65) { e.preventDefault(); showAddServerModal(); }
        if (e.altKey && e.keyCode === 84) { e.preventDefault(); testAllConnections(); }
        if (e.keyCode === 27) {
            document.querySelectorAll('.gcs-modal-overlay.open').forEach(function(m){ closeModal(m.id); });
        }
    });

    // ---- Auto-save filters ----
    $('#serverSearch,#statusFilter,#protocolFilter,#priorityFilter').on('change keyup', function() {
        var f={search:$('#serverSearch').val(),status:$('#statusFilter').val(),protocol:$('#protocolFilter').val(),priority:$('#priorityFilter').val()};
        var has=f.search||f.status||f.protocol||f.priority;
        if(has) localStorage.setItem('acs_filters',JSON.stringify(f)); else localStorage.removeItem('acs_filters');
        applyFilters();
    });

    function loadSavedFilters() {
        var saved=localStorage.getItem('acs_filters');
        if(saved){try{var f=JSON.parse(saved);if(f.search)$('#serverSearch').val(f.search);if(f.status)$('#statusFilter').val(f.status);if(f.protocol)$('#protocolFilter').val(f.protocol);if(f.priority)$('#priorityFilter').val(f.priority);applyFilters();}catch(e){localStorage.removeItem('acs_filters');}}
    }

    // ---- Init ----
    $(document).ready(function() {
        initServerGauges();
        loadActivityLog();
        updateActivityFeed();
        loadSavedFilters();

        setTimeout(function() { loadDeviceCounts(); }, 500);
        setTimeout(function() { autoCheckServerStatus(); }, 2000);
        setTimeout(function() { refreshLocalStats(); }, 1000);

        setInterval(function() { loadDeviceCounts(); }, 600000);
        setInterval(function() { if (!document.hidden) autoCheckServerStatus(); }, 120000);
        setInterval(function() { refreshLocalStats(); }, 10000);
        setInterval(function() { updateActivityFeed(); }, 30000);
        setInterval(function() { $.get(_url + 'plugin/genieacs_manager/clean-cache'); }, 600000);
    });
{/literal}
</script>

{include file="sections/footer.tpl"}
