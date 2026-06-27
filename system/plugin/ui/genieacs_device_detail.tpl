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
/* ===================== DEVICE DETAIL — DESIGN SYSTEM ===================== */
* { box-sizing: border-box; }
.bk-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; font-family: 'DM Sans', sans-serif; margin-bottom: 16px; }
.bk-card-header { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; background: #fafcff; }
.bk-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bk-card-title { font-size: 13px; font-weight: 700; color: #0f172a; }
.bk-card-subtitle { font-size: 11px; color: #94a3b8; }
.bk-card-body { padding: 18px; }
.bk-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 5px; }
.bk-input { width: 100%; height: 36px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f172a; background: #f8fafc; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
.bk-input:focus { border-color: #0271c6; background: #fff; box-shadow: 0 0 0 3px rgba(2,113,198,0.10); }
.bk-input::placeholder { color: #cbd5e1; }
.bk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; white-space: nowrap; box-sizing: border-box; }
.bk-btn-primary { background: #0271c6; color: white; box-shadow: 0 2px 8px rgba(2,113,198,0.25); }
.bk-btn-primary:hover { background: #0359a0; color: white; text-decoration: none; }
.bk-btn-success { background: #16a34a; color: white; }
.bk-btn-success:hover { background: #15803d; color: white; text-decoration: none; }
.bk-btn-warning { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.bk-btn-warning:hover { background: #fde68a; text-decoration: none; }
.bk-btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.bk-btn-danger:hover { background: #fecaca; text-decoration: none; }
.bk-btn-ghost { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.bk-btn-ghost:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }
.bk-btn-sm { height: 30px; padding: 0 12px; font-size: 12px; border-radius: 6px; }
.bk-btn-icon { height: 30px; width: 30px; padding: 0; border-radius: 7px; }
.bk-form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.bk-form-group:last-child { margin-bottom: 0; }
.bk-help { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.bk-input-wrap { position: relative; display: flex; align-items: center; }
.bk-input-wrap .bk-input { padding-right: 38px; }
.bk-eye-btn { position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 14px; padding: 0; }
.bk-eye-btn:hover { color: #475569; }

/* ---- Status badges ---- */
.dd-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.dd-online { background: #dcfce7; color: #15803d; }
.dd-offline { background: #fee2e2; color: #dc2626; }
.dd-pulse { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.dd-pulse-g { background: #22c55e; animation: ddPG 2s infinite; }
.dd-pulse-r { background: #ef4444; animation: ddPR 2s infinite; }
@keyframes ddPG { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.6)} 70%{box-shadow:0 0 0 5px rgba(34,197,94,0)} }
@keyframes ddPR { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.6)}  70%{box-shadow:0 0 0 5px rgba(239,68,68,0)} }

/* Badge Device Detail */
.dd-badge-detail { display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px; }

/* Tombol Kembali — header mobile only & action bar desktop only */
.dd-back-header { display:none;align-items:center;gap:5px;height:28px;padding:0 10px;border-radius:20px;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;font-size:11px;font-weight:600;font-family:'DM Sans',sans-serif;flex-shrink:0;text-decoration:none;transition:all 0.12s;white-space:nowrap; }
.dd-back-header:hover { background:#e2e8f0;color:#0f172a;text-decoration:none; }
.dd-back-desktop { display:inline-flex; }

/* Desktop: pill circle disembunyikan — ikon langsung tampil */
.dd-pill-circle { display:none;width:28px;height:28px;border-radius:50%;align-items:center;justify-content:center;flex-shrink:0; }

/* ---- Breadcrumb ---- */
.dd-bc { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; font-family: 'DM Sans', sans-serif; margin-top: 4px; }
.dd-bc a { color: #0271c6; text-decoration: none; }
.dd-bc a:hover { text-decoration: underline; }

/* ---- Action bar — pill buttons desktop, icon+label mobile ---- */
.dd-action-bar { display: flex; align-items: center; justify-content: flex-end; padding: 8px 18px; gap: 6px; background: #fafcff; border-bottom: 1px solid #f1f5f9; }
.dd-action-bar-divider { width: 1px; height: 18px; background: #e2e8f0; margin: 0 2px; flex-shrink: 0; }

/* Desktop: pill transparan dengan ikon + teks */
.dd-pill-btn { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border-radius: 20px; border: 1px solid transparent; background: transparent; font-size: 12px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all 0.12s; text-decoration: none; white-space: nowrap; flex-shrink: 0; }
.dd-pill-btn svg { flex-shrink: 0; }
.dd-pill-btn.refresh { color: #0271c6; }
.dd-pill-btn.refresh:hover { background: #eff6ff; border-color: #bfdbfe; text-decoration: none; }
.dd-pill-btn.summon { color: #16a34a; }
.dd-pill-btn.summon:hover { background: #f0fdf4; border-color: #bbf7d0; text-decoration: none; }
.dd-pill-btn.reboot { color: #dc2626; }
.dd-pill-btn.reboot:hover { background: #fee2e2; border-color: #fecaca; text-decoration: none; }
.dd-pill-btn.back { color: #64748b; border: 1px solid #e2e8f0; background: #f8fafc; }
.dd-pill-btn.back:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }

/* Pill dengan teks untuk tombol di dalam card header (edit, refresh) */
.dd-action-btn { display: inline-flex; align-items: center; gap: 5px; height: 28px; padding: 0 10px; border-radius: 20px; border: none; background: transparent; cursor: pointer; font-size: 11px; font-weight: 600; font-family: 'DM Sans', sans-serif; transition: all 0.12s; text-decoration: none; flex-shrink: 0; white-space: nowrap; }
.dd-action-btn.edit { color: #7c3aed; border: 1px solid #ddd6fe; background: #f5f3ff; }
.dd-action-btn.edit:hover { background: #ede9fe; }
.dd-action-btn.refresh-sm { color: #0271c6; border: 1px solid #bfdbfe; background: #eff6ff; }
.dd-action-btn.refresh-sm:hover { background: #dbeafe; }
.dd-action-btn.refresh { color: #0271c6; border: 1px solid #bfdbfe; background: #eff6ff; }
.dd-action-btn.refresh:hover { background: #dbeafe; }

/* ---- Info grid — 3 column ---- */
.dd-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.dd-info-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 12px 14px; display: flex; align-items: center; gap: 10px; }
.dd-info-icon { width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #0271c6; flex-shrink: 0; }
.dd-info-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.dd-info-value { font-size: 13px; font-weight: 600; color: #0f172a; word-break: break-word; margin-top: 2px; min-width: 0; }
.dd-info-content { min-width: 0; flex: 1; }
/* Last Inform selalu full width di semua breakpoint */
.dd-info-last { grid-column: 1 / -1; }

/* Badge value responsive — bisa wrap teks */
.dd-badge-ip, .dd-badge-mac, .dd-badge-user, .dd-badge-plain,
.dd-badge-temp-ok, .dd-badge-temp-warm, .dd-badge-temp-hot,
.dd-badge-rx-good, .dd-badge-rx-fair, .dd-badge-rx-poor, .dd-badge-rx-na {
    max-width: 100%; word-break: break-all; white-space: normal; }

/* ---- Value Badges (same as gd-badge-* in devices page) ---- */
.dd-badge-rx-good { display:inline-flex;align-items:center;gap:4px;background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-rx-fair { display:inline-flex;align-items:center;gap:4px;background:#fef3c7;border:1px solid #fde68a;color:#d97706;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-rx-poor { display:inline-flex;align-items:center;gap:4px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-rx-na   { display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;border:1px solid #e2e8f0;color:#94a3b8;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-ip      { display:inline-flex;align-items:center;gap:4px;background:#f0f9ff;border:1px solid #bae6fd;color:#0284c7;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-user    { display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #bfdbfe;color:#0271c6;font-size:12px;font-weight:700;border-radius:6px;padding:2px 9px; }
.dd-badge-mac     { display:inline-flex;align-items:center;gap:4px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-temp-ok   { display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-temp-warm { display:inline-flex;align-items:center;gap:4px;background:#fef3c7;border:1px solid #fde68a;color:#d97706;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-temp-hot  { display:inline-flex;align-items:center;gap:4px;background:#fee2e2;border:1px solid #fecaca;color:#dc2626;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-badge-pon-gpon  { display:inline-flex;align-items:center;gap:4px;background:#1d4ed8;color:#fff;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px; }
.dd-badge-pon-epon  { display:inline-flex;align-items:center;gap:4px;background:#ede9fe;border:1px solid #ddd6fe;color:#7c3aed;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px; }
.dd-badge-pon-eth   { display:inline-flex;align-items:center;gap:4px;background:#ffedd5;border:1px solid #fed7aa;color:#c2410c;font-size:11px;font-weight:700;border-radius:6px;padding:2px 8px; }
.dd-badge-plain   { display:inline-flex;align-items:center;gap:4px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px; }
.dd-badge-device  { display:inline-flex;align-items:center;gap:5px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;font-size:12px;font-weight:700;border-radius:6px;padding:3px 9px; }
.dd-badge-iface   { display:inline-flex;align-items:center;gap:4px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:11px;font-weight:600;border-radius:6px;padding:2px 8px;font-family:'DM Mono',monospace; }
.dd-mono { font-family: 'DM Mono', monospace; font-size: 11px; }
.dd-muted { color: #94a3b8; font-size: 11px; }

/* ---- WiFi Info rows ---- */
.dd-wifi-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
.dd-wifi-row:last-child { border-bottom: none; }
.dd-wifi-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.dd-wifi-value { font-size: 13px; font-weight: 500; color: #334155; text-align: right; }
.dd-pass-group { display: flex; align-items: center; gap: 6px; }
.dd-device-badge { display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; border: 1px solid #bfdbfe; color: #0271c6; font-size: 11px; font-weight: 700; border-radius: 6px; padding: 2px 8px; }

/* ---- Tags ---- */
.dd-tags-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
.dd-tag { display: inline-flex; align-items: center; gap: 5px; background: #eff6ff; border: 1px solid #bfdbfe; color: #0271c6; font-size: 12px; font-weight: 600; border-radius: 6px; padding: 3px 10px; }
.dd-tag-loc { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
.dd-no-tag { font-size: 12px; color: #cbd5e1; font-style: italic; }

/* Tags section di dalam WiFi card */
.dd-tags-section { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e2e8f0; }
.dd-tags-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }

/* ---- Toggle ---- */
.dd-toggle-row { display: flex; align-items: center; gap: 10px; }
.dd-toggle { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.dd-toggle input { display: none; }
.dd-toggle-track { position: absolute; inset: 0; background: #e2e8f0; border-radius: 12px; cursor: pointer; transition: background 0.2s; }
.dd-toggle input:checked + .dd-toggle-track { background: #0271c6; }
.dd-toggle-thumb { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: left 0.2s; pointer-events: none; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.dd-toggle input:checked ~ .dd-toggle-thumb { left: 22px; }
.dd-toggle-lbl { font-size: 12px; color: #64748b; }

/* ---- Admin grid ---- */
.dd-admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.dd-admin-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.dd-admin-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 12px 14px; }
.dd-admin-item label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 5px; }
.dd-admin-item span { font-size: 13px; font-weight: 600; color: #0f172a; }

/* ---- Connected Users table ---- */
.dd-table-wrap { overflow-x: auto; }
.dd-table { width: 100%; border-collapse: collapse; font-family: 'DM Sans', sans-serif; font-size: 12px; }
.dd-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.dd-table thead th { padding: 9px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; text-align: left; white-space: nowrap; }
.dd-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.1s; }
.dd-table tbody tr:last-child { border-bottom: none; }
.dd-table tbody tr:hover { background: #f8fafc; }
.dd-table tbody td { padding: 9px 12px; color: #334155; }
.dd-conn { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 20px; font-size: 10px; font-weight: 700; }
.dd-conn-24 { background: #dbeafe; color: #1d4ed8; }
.dd-conn-5  { background: #ede9fe; color: #7c3aed; }
.dd-conn-lan { background: #dcfce7; color: #15803d; }
.dd-conn-unk { background: #f1f5f9; color: #64748b; }

/* ---- User mobile cards ---- */
.dd-user-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 8px; }
.dd-user-card:last-child { margin-bottom: 0; }
.dd-user-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.dd-user-row { display: flex; justify-content: space-between; font-size: 12px; padding: 3px 0; border-bottom: 1px solid #f1f5f9; }
.dd-user-row:last-child { border-bottom: none; }
.dd-user-key { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }

/* ---- Layout ---- */
.dd-section-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.dd-count { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; background: #0271c6; color: white; border-radius: 10px; font-size: 11px; font-weight: 700; padding: 0 6px; }
.dd-empty { padding: 36px 24px; text-align: center; font-family: 'DM Sans', sans-serif; }
.dd-empty i { font-size: 36px; color: #e2e8f0; display: block; margin-bottom: 10px; }
.dd-empty p { font-size: 13px; color: #94a3b8; margin: 0; }

/* ---- Modal engine ---- */
@keyframes ddModalIn   { from{opacity:0;transform:translateY(-12px) scale(0.98)} to{opacity:1;transform:translateY(0) scale(1)} }
@keyframes ddSlideUp   { from{transform:translateY(100%);opacity:0.6} to{transform:translateY(0);opacity:1} }
@keyframes ddSpin      { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
@keyframes ddCheckCircle { from{stroke-dashoffset:166} to{stroke-dashoffset:0} }
@keyframes ddCheckMark   { from{stroke-dashoffset:48}  to{stroke-dashoffset:0} }
@keyframes ddCheckScale  { 0%{transform:scale(0.8);opacity:0} 60%{transform:scale(1.1)} 100%{transform:scale(1);opacity:1} }
.dd-modal-spinner { display:inline-block;width:48px;height:48px;border:3px solid #e2e8f0;border-top-color:#0271c6;border-radius:50%;animation:ddSpin 0.7s linear infinite; }
.dd-check-svg { width:72px;height:72px;animation:ddCheckScale 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards; }
.dd-check-circle { fill:none;stroke:#16a34a;stroke-width:4;stroke-dasharray:166;stroke-dashoffset:166;stroke-linecap:round;animation:ddCheckCircle 0.5s ease-in-out 0.1s forwards; }
.dd-check-mark   { fill:none;stroke:#16a34a;stroke-width:5;stroke-dasharray:48;stroke-dashoffset:48;stroke-linecap:round;stroke-linejoin:round;animation:ddCheckMark 0.35s ease-in-out 0.5s forwards; }

/* ---- Sheet/Form Modal — center di desktop, slide-up di mobile ---- */
#ddSheetOverlay { position:fixed;inset:0;z-index:9994;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px; }
#ddSheetCard { background:white;border-radius:16px;width:100%;max-width:520px;max-height:90vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);display:flex;flex-direction:column;animation:ddModalIn 0.25s cubic-bezier(0.34,1.56,0.64,1); }
.dd-sheet-header { padding:18px 22px;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#0271c6,#0359a0);flex-shrink:0; }
.dd-sheet-title { font-size:15px;font-weight:700;color:white;font-family:'DM Sans',sans-serif;display:flex;align-items:center;gap:8px; }
.dd-sheet-close { width:30px;height:30px;border-radius:8px;border:none;background:rgba(255,255,255,0.2);color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s;flex-shrink:0; }
.dd-sheet-close:hover { background:rgba(255,255,255,0.3); }
.dd-sheet-body { padding:20px 22px;flex:1;overflow-y:auto; }
.dd-sheet-footer { padding:14px 22px;border-top:1px solid #f1f5f9;display:flex;gap:8px;justify-content:flex-end;background:#fafcff;flex-shrink:0; }

/* ---- Notification modal (center) ---- */
#ddModalOverlay { position:fixed;inset:0;z-index:9995;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px; }
#ddModalCard { background:white;border-radius:16px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);display:flex;flex-direction:column;animation:ddModalIn 0.25s cubic-bezier(0.34,1.56,0.64,1); }
#ddModalHeader { padding:18px 22px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0; }
#ddModalTitle { font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;display:flex;align-items:center;gap:8px;color:white; }
#ddModalClosebtn { width:30px;height:30px;border-radius:8px;border:none;background:rgba(255,255,255,0.2);color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background 0.15s;flex-shrink:0; }
#ddModalBody { padding:22px;font-family:'DM Sans',sans-serif; }
#ddModalFooter { padding:14px 22px;border-top:1px solid #f1f5f9;display:flex;gap:8px;justify-content:flex-end;background:#fafcff;flex-shrink:0; }

/* ===================== TABLET ===================== */
@media (max-width:991px) {
    .dd-info-grid { grid-template-columns: repeat(2, 1fr); }
}

/* ===================== MOBILE ===================== */
@media (max-width: 767px) {
    .bk-card-header { padding: 12px 14px; flex-wrap: nowrap; }
    .bk-card-body { padding: 12px 14px; }
    /* Info grid mobile: 2 kolom, layout horizontal compact */
    .dd-info-grid { grid-template-columns: 1fr 1fr; gap: 6px; padding: 8px; }
    .dd-info-item { padding: 8px 10px; flex-direction: row; align-items: center; gap: 8px; }
    .dd-info-icon { width: 26px; height: 26px; border-radius: 7px; font-size: 11px; flex-shrink: 0; }
    .dd-info-label { font-size: 9px; margin-bottom: 2px; }
    .dd-info-value { font-size: 10px; margin-top: 0; word-break: break-all; }
    /* Last inform full width */
    .dd-info-item.dd-info-last { grid-column: 1 / -1; }
    .dd-section-row { grid-template-columns: 1fr; gap: 12px; }
    .dd-admin-grid { grid-template-columns: 1fr; gap: 8px; }
    .dd-admin-grid-4 { grid-template-columns: 1fr 1fr; gap: 8px; }
    .dd-table-wrap { display: none; }
    .dd-users-mobile { display: block !important; }
    /* Action bar mobile: 3 tombol rata, ikon bulat + teks horizontal, border pemisah */
    .dd-action-bar { justify-content: stretch; padding: 0; gap: 0; }
    .dd-action-bar-divider { display: none; }
    .dd-back-desktop { display: none !important; }
    .dd-back-header { display: flex; }
    .dd-pill-btn { flex: 1; flex-direction: row; gap: 7px; height: auto; padding: 12px 4px; border-radius: 0; border: none !important; border-right: 1px solid #f1f5f9 !important; font-size: 12px; font-weight: 600; justify-content: center; }
    .dd-pill-btn:last-of-type, .dd-pill-btn.back { border-right: none !important; }
    .dd-pill-circle { display: flex; }
    #ddModalOverlay { align-items: flex-end !important; padding: 0 !important; }
    #ddModalCard { border-radius: 20px 20px 0 0 !important; max-width: 100% !important; animation: ddSlideUp 0.32s cubic-bezier(0.32,0.72,0,1) !important; }
    #ddModalFooter { flex-direction: column-reverse; }
    #ddModalFooter .bk-btn { width:100%;justify-content:center; }
    /* Sheet slide-up di mobile */
    #ddSheetOverlay { align-items: flex-end !important; padding: 0 !important; }
    #ddSheetCard { border-radius: 20px 20px 0 0 !important; max-width: 100% !important; max-height: 85vh !important; animation: ddSlideUp 0.32s cubic-bezier(0.32,0.72,0,1) !important; }
    .dd-sheet-footer { flex-direction: column-reverse; }
    .dd-sheet-footer .bk-btn { width:100%;justify-content:center; }
    .dd-sheet-body { padding:16px; }
    .bk-card-subtitle { display: none; }
}
@media (min-width: 768px) { .dd-users-mobile { display: none !important; } }
{/literal}
</style>

<!-- ===================== DEVICE HEADER CARD ===================== -->
<div class="bk-card">
    <!-- Header: identitas device + tombol Kembali sejajar dengan badge -->
    <div class="bk-card-header">
        <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
            <div class="bk-card-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="14" width="20" height="7" rx="2" stroke="#7c3aed" stroke-width="2"/>
                    <circle cx="6" cy="17.5" r="1" fill="#7c3aed"/>
                    <circle cx="10" cy="17.5" r="1" fill="#7c3aed"/>
                    <path d="M12 14V10" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
                    <path d="M8.5 7.5C9.5 6.5 10.7 6 12 6s2.5.5 3.5 1.5" stroke="#7c3aed" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                    <path d="M6 5C7.6 3.4 9.7 2.5 12 2.5s4.4.9 6 2.5" stroke="#7c3aed" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                </svg>
            </div>
            <div style="min-width:0;flex:1;">
                <!-- Baris 1: username + online + tombol Kembali (mobile) semuanya sejajar -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;">
                    <span class="dd-badge-user" style="font-size:13px;padding:3px 10px;flex-shrink:0;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;">
                            <circle cx="12" cy="8" r="4" stroke="#0271c6" stroke-width="2"/>
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        {$device.pppoe_username|default:$device.ppp_username|default:'Unknown Device'}
                    </span>
                    {if $device.status == 'online'}
                        <span class="dd-status dd-online" style="flex-shrink:0;"><span class="dd-pulse dd-pulse-g"></span> Online</span>
                    {else}
                        <span class="dd-status dd-offline" style="flex-shrink:0;"><span class="dd-pulse dd-pulse-r"></span> Offline</span>
                    {/if}
                    <!-- Tombol Kembali: mobile only, sejajar di baris yang sama -->
                    <a href="{$_url}plugin/genieacs_devices" class="dd-back-header" title="Kembali" style="margin-left:auto;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><polyline points="15 18 9 12 15 6" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Kembali
                    </a>
                </div>
                <!-- Baris 2: breadcrumb -->
                <div class="dd-bc" style="margin-top:5px;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><rect x="2" y="3" width="7" height="7" rx="1" stroke="#0271c6" stroke-width="2"/><rect x="15" y="3" width="7" height="7" rx="1" stroke="#0271c6" stroke-width="2"/><rect x="2" y="14" width="7" height="7" rx="1" stroke="#0271c6" stroke-width="2"/><rect x="15" y="14" width="7" height="7" rx="1" stroke="#0271c6" stroke-width="2"/></svg>
                    <a href="{$_url}plugin/genieacs_devices">Devices</a>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><polyline points="9 18 15 12 9 6" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>Detail</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar: desktop = pill row, mobile = 3 tombol icon bulat + teks -->
    <div class="dd-action-bar">
        <!-- Refresh -->
        <button class="dd-pill-btn refresh" onclick="ddRefreshDevice()" title="Refresh Device">
            <span class="dd-pill-circle" style="background:#eff6ff;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-2.1-5.7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/><polyline points="21 3 21 9 15 9" stroke="#0271c6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Refresh
        </button>
        <div class="dd-action-bar-divider"></div>
        <!-- Summon -->
        <button class="dd-pill-btn summon" onclick="ddSummonDevice()" title="Summon Device">
            <span class="dd-pill-circle" style="background:#f0fdf4;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            Summon
        </button>
        <div class="dd-action-bar-divider"></div>
        <!-- Reboot -->
        <button class="dd-pill-btn reboot" onclick="ddRebootDevice()" title="Reboot Device">
            <span class="dd-pill-circle" style="background:#fee2e2;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="2" x2="12" y2="12" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            Reboot
        </button>
        <div class="dd-action-bar-divider"></div>
        <!-- Kembali: desktop only -->
        <a href="{$_url}plugin/genieacs_devices" class="dd-pill-btn back dd-back-desktop" title="Kembali ke Devices">
            <span class="dd-pill-circle" style="background:#f1f5f9;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="15 18 9 12 15 6" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Kembali
        </a>
    </div>
</div>

<!-- ===================== DEVICE INFORMATION ===================== -->
<div class="bk-card">
    <div class="bk-card-header">
        <div class="bk-card-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#0271c6" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="#0271c6"/></svg>
        </div>
        <div>
            <div class="bk-card-title">Device Information</div>
            <div class="bk-card-subtitle">Parameter dari ACS server</div>
        </div>
    </div>
    <div class="bk-card-body">
        <div class="dd-info-grid">
            {assign var="info_order" value=['ppp_username','pppoe_username','pppoe_ip','ip','ppp_mac','mac_address','model','pon_type','rx_power','serial_number','sn','vendor','manufacturer','ppp_uptime','uptime','temperature']}
            {foreach $info_order as $key}
                {if isset($device[$key]) && !in_array($key, ['status','id_raw','tags','lokasi','last_inform','wifi_ssid_2g','wifi_ssid_5g','all_tags'])}
                {assign var="value" value=$device[$key]}
                <div class="dd-info-item">
                    <div class="dd-info-icon"
                        {if $key == 'rx_power'}style="background:#f0fdf4;color:#16a34a;"
                        {elseif $key == 'pppoe_ip' || $key == 'ip'}style="background:#f0f9ff;color:#0284c7;"
                        {elseif $key == 'ppp_username' || $key == 'pppoe_username'}style="background:#eff6ff;color:#0271c6;"
                        {elseif $key == 'temperature'}style="background:#fff7ed;color:#c2410c;"
                        {elseif $key == 'pon_type'}style="background:#ede9fe;color:#7c3aed;"
                        {elseif $key == 'ppp_uptime' || $key == 'uptime'}style="background:#eff6ff;color:#0271c6;"
                        {elseif $key == 'ppp_mac' || $key == 'mac_address'}style="background:#f8fafc;color:#475569;"
                        {elseif $key == 'serial_number' || $key == 'sn'}style="background:#faf5ff;color:#7c3aed;"
                        {elseif $key == 'vendor' || $key == 'manufacturer'}style="background:#f0fdf4;color:#16a34a;"
                        {else}style="background:#eff6ff;color:#0271c6;"{/if}>
                        {if $key == 'rx_power'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {elseif $key == 'ppp_uptime' || $key == 'uptime'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'pon_type'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M18.36 6.64A9 9 0 1 1 5.64 6.64" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="2" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'pppoe_ip' || $key == 'ip'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="2"/></svg>
                        {elseif $key == 'ppp_mac' || $key == 'mac_address'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="2"/><path d="M6 12h.01M10 12h.01M14 12h.01M18 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'ppp_username' || $key == 'pppoe_username'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'model'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><line x1="8" y1="21" x2="16" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="17" x2="12" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'vendor' || $key == 'manufacturer'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/></svg>
                        {elseif $key == 'serial_number' || $key == 'sn'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {elseif $key == 'temperature'}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" stroke="currentColor" stroke-width="2"/></svg>
                        {else}<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>{/if}
                    </div>
                    <div class="dd-info-content" style="min-width:0;flex:1;">
                        <div class="dd-info-label">{$key|replace:'_':' '|ucwords}</div>
                        <div class="dd-info-value" style="margin-top:2px;">
                            {if $key == 'pppoe_username' || $key == 'ppp_username'}
                                {if $value && $value != 'N/A'}
                                    <span class="dd-badge-user"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="#0271c6" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/></svg>{$value}</span>
                                {else}<span class="dd-muted">—</span>{/if}
                            {elseif $key == 'pppoe_ip' || $key == 'ip'}
                                {if $value && $value != 'N/A'}
                                    <span class="dd-badge-ip"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#0284c7" stroke-width="2"/><line x1="2" y1="12" x2="22" y2="12" stroke="#0284c7" stroke-width="2"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="#0284c7" stroke-width="2"/></svg>{$value}</span>
                                {else}<span class="dd-muted">—</span>{/if}
                            {elseif $key == 'ppp_mac' || $key == 'mac_address'}
                                {if $value && $value != 'N/A'}
                                    <span class="dd-badge-mac"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="2" stroke="#475569" stroke-width="2"/></svg>{$value}</span>
                                {else}<span class="dd-muted">—</span>{/if}
                            {elseif $key == 'rx_power'}
                                {if $value && $value != 'N/A'}
                                    {assign var="rx_v" value=floatval($value)}
                                    {if $rx_v >= -20}<span class="dd-badge-rx-good"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="#15803d" stroke-width="2" stroke-linecap="round"/></svg>{$value} dBm</span>
                                    {elseif $rx_v >= -25}<span class="dd-badge-rx-fair"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="#d97706" stroke-width="2" stroke-linecap="round"/></svg>{$value} dBm</span>
                                    {else}<span class="dd-badge-rx-poor"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/></svg>{$value} dBm</span>{/if}
                                {else}<span class="dd-badge-rx-na">N/A</span>{/if}
                            {elseif $key == 'temperature'}
                                {if $value && $value != 'N/A'}
                                    {assign var="temp_v" value=floatval($value)}
                                    {assign var="temp_clean" value=$value|replace:'°C':''}
                                    {assign var="temp_clean" value=$temp_clean|replace:'C':''}
                                    {if $temp_v < 60}<span class="dd-badge-temp-ok"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" stroke="#16a34a" stroke-width="2"/></svg>{$temp_clean|trim}°C</span>
                                    {elseif $temp_v < 75}<span class="dd-badge-temp-warm"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" stroke="#d97706" stroke-width="2"/></svg>{$temp_clean|trim}°C</span>
                                    {else}<span class="dd-badge-temp-hot"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" stroke="#dc2626" stroke-width="2"/></svg>{$temp_clean|trim}°C</span>{/if}
                                {else}<span class="dd-muted">—</span>{/if}
                            {elseif $key == 'pon_type'}
                                {if $value == 'GPON'}<span class="dd-badge-pon-gpon">{$value}</span>
                                {elseif $value == 'EPON'}<span class="dd-badge-pon-epon">{$value}</span>
                                {elseif $value == 'Ethernet' || $value == 'ETHERNET'}<span class="dd-badge-pon-eth">{$value}</span>
                                {else}<span class="dd-badge-plain">{$value|default:'N/A'}</span>{/if}
                            {else}
                                <span class="dd-badge-plain">{$value|default:'N/A'}</span>
                            {/if}
                        </div>
                    </div>
                </div>
                {/if}
            {/foreach}
            <!-- Last Inform — selalu full width -->
            <div class="dd-info-item dd-info-last" style="grid-column:1/-1;">
                <div class="dd-info-icon" style="background:#f0fdf4;color:#16a34a;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#16a34a" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="6" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/><line x1="3" y1="10" x2="21" y2="10" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div class="dd-info-content" style="min-width:0;flex:1;">
                    <div class="dd-info-label">Last Inform</div>
                    <div class="dd-info-value" style="margin-top:2px;">
                        <span class="dd-badge-plain"><svg width="9" height="9" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#475569" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="#475569" stroke-width="2" stroke-linecap="round"/></svg> {$device.last_inform}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== WiFi+Tags (kiri) | Web Admin (kanan) ===================== -->
<div class="dd-section-row">

    <!-- KIRI: WiFi Information + Device Tags digabung -->
    <div class="bk-card" style="margin-bottom:0;">
        <div class="bk-card-header" style="justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="bk-card-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 12.55a11 11 0 0 1 14.08 0" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/><path d="M1.42 9a16 16 0 0 1 21.16 0" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="20" r="1" fill="#16a34a"/></svg>
                </div>
                <div class="bk-card-title">WiFi Information</div>
            </div>
            <button class="dd-action-btn edit" onclick="ddOpenWifiSheet()" title="Edit WiFi Settings">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Edit
            </button>
        </div>
        <div class="bk-card-body" style="padding:14px 18px;">
            {foreach $wifi_info as $wifi_key => $wifi_value}
                {if !in_array($wifi_key, ['wifi_2g_enabled','wifi_5g_enabled'])}
                <div class="dd-wifi-row">
                    <span class="dd-wifi-label">{$wifi_key|replace:'_':' '|ucwords}</span>
                    {if strpos($wifi_key, 'password') !== false}
                        <div class="dd-pass-group">
                            <span id="wifi-password" style="display:none;font-family:'DM Mono',monospace;font-size:12px;">{$wifi_value|default:'N/A'}</span>
                            <span id="wifi-password-hidden" style="color:#94a3b8;">••••••••</span>
                            <button class="dd-action-btn" onclick="togglePassword()" style="width:28px;height:28px;color:#94a3b8;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" id="password-icon-svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                        </div>
                    {elseif strpos($wifi_key, 'total') !== false || strpos($wifi_key, 'connected') !== false}
                        <span class="dd-device-badge">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="2" stroke="#0271c6" stroke-width="2"/><line x1="9" y1="18" x2="15" y2="18" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/></svg>
                            {$wifi_value|default:0} devices
                        </span>
                    {else}
                        <span class="dd-wifi-value">{$wifi_value|default:'N/A'}</span>
                    {/if}
                </div>
                {/if}
            {/foreach}

            <!-- Device Tags — digabung di bawah WiFi dengan garis pemisah -->
            <div class="dd-tags-section">
                <div class="dd-tags-section-header">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke="#d97706" stroke-width="2"/><circle cx="7" cy="7" r="1.5" fill="#d97706"/></svg>
                        <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Device Tags</span>
                    </div>
                    <button class="dd-action-btn edit" onclick="ddOpenTagsSheet()" title="Edit Tags">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Edit
                    </button>
                </div>
                <div id="tags-display">
                    {assign var="tags_to_display" value=$device.all_tags|default:$device.tags}
                    {if $tags_to_display && $tags_to_display != 'N/A'}
                        <div class="dd-tags-wrap">
                            {if strpos($tags_to_display, ', ') !== false}{assign var="tags_array" value=", "|explode:$tags_to_display}
                            {elseif strpos($tags_to_display, ',') !== false}{assign var="tags_array" value=","|explode:$tags_to_display}
                            {else}{if $device.lokasi && $device.lokasi != 'N/A'}{assign var="tags_array" value=[$device.tags, $device.lokasi]}
                            {else}{assign var="tags_array" value=[$tags_to_display]}{/if}{/if}
                            {foreach $tags_array as $tag}{if $tag|trim != ''}
                                {if $tag@index == 1}
                                    <span class="dd-tag dd-tag-loc">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" stroke="#16a34a" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="#16a34a" stroke-width="2"/></svg>
                                        {$tag|trim}
                                    </span>
                                {else}
                                    <span class="dd-tag">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="#0271c6" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/></svg>
                                        {$tag|trim}
                                    </span>
                                {/if}
                            {/if}{/foreach}
                        </div>
                    {else}<span class="dd-no-tag">No tags assigned</span>{/if}
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN: Web Admin Credentials — grid 2x2 -->
    <div class="bk-card" style="margin-bottom:0;">
        <div class="bk-card-header" style="justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="bk-card-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#dc2626" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <div class="bk-card-title">Web Admin</div>
                    <div class="bk-card-subtitle">Router credentials</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <button class="dd-action-btn refresh-sm" onclick="ddRefreshWebAdmin()" title="Refresh Web Admin">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-2.1-5.7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/><polyline points="21 3 21 9 15 9" stroke="#0271c6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Refresh
                </button>
                <button class="dd-action-btn edit" onclick="ddOpenAdminSheet()" title="Edit Credentials">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Edit
                </button>
            </div>
        </div>
        <div class="bk-card-body">
            <div class="dd-admin-grid">
                <div class="dd-admin-item">
                    <label>Super Admin User</label>
                    <span>{$web_admin.super_username}</span>
                </div>
                <div class="dd-admin-item">
                    <label>Super Admin Pass</label>
                    <div class="dd-pass-group">
                        <span id="super-pass-display" style="display:none;font-family:'DM Mono',monospace;font-size:12px;">{$web_admin.super_password}</span>
                        <span id="super-pass-hidden" style="font-size:13px;font-weight:600;">{if $web_admin.super_password}••••••••{else}(empty){/if}</span>
                        {if $web_admin.super_password}
                        <button class="dd-action-btn" onclick="toggleSuperPassDisplay()" style="width:24px;height:24px;color:#94a3b8;">
                            <svg id="super-pass-icon" width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        {/if}
                    </div>
                </div>
                <div class="dd-admin-item">
                    <label>User Username</label>
                    <span>{$web_admin.user_username}</span>
                </div>
                <div class="dd-admin-item">
                    <label>User Password</label>
                    <div class="dd-pass-group">
                        <span id="user-pass-display" style="display:none;font-family:'DM Mono',monospace;font-size:12px;">{$web_admin.user_password}</span>
                        <span id="user-pass-hidden" style="font-size:13px;font-weight:600;">{if $web_admin.user_password}••••••••{else}(empty){/if}</span>
                        {if $web_admin.user_password}
                        <button class="dd-action-btn" onclick="toggleUserPassDisplay()" style="width:24px;height:24px;color:#94a3b8;">
                            <svg id="user-pass-icon" width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ===================== CONNECTED USERS ===================== -->
<div class="bk-card">
    <div class="bk-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="bk-card-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="#059669" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="#059669" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="#059669" stroke-width="2" stroke-linecap="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="#059669" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="bk-card-title">Connected Users</div>
                <span class="dd-count">{count($connected_users)}</span>
            </div>
        </div>
        <button class="dd-action-btn refresh" onclick="ddRefreshUsers()" title="Refresh Users">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-2.1-5.7" stroke="#0271c6" stroke-width="2" stroke-linecap="round"/><polyline points="21 3 21 9 15 9" stroke="#0271c6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Refresh
        </button>
    </div>
    <div class="bk-card-body" style="padding:0;">
        {if count($connected_users) > 0}
        <div class="dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Device Name</th><th>IP Address</th><th>MAC Address</th><th>Connection</th><th>Interface</th></tr></thead>
                <tbody>
                    {foreach $connected_users as $user}
                    <tr>
                        <td>
                            <span class="dd-badge-device">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="2" stroke="#7c3aed" stroke-width="2"/><line x1="12" y1="18" x2="12" y2="18" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/></svg>
                                {if $user.hostname && $user.hostname != ''}{$user.hostname}{else}Unknown{/if}
                            </span>
                        </td>
                        <td><span class="dd-badge-ip"><svg width="10" height="10" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#0284c7" stroke-width="2"/><line x1="2" y1="12" x2="22" y2="12" stroke="#0284c7" stroke-width="1.5"/><path d="M12 3a13 13 0 0 1 3 9 13 13 0 0 1-3 9 13 13 0 0 1-3-9 13 13 0 0 1 3-9z" stroke="#0284c7" stroke-width="1.5"/></svg>{$user.ip}</span></td>
                        <td><span class="dd-badge-mac">{$user.mac}</span></td>
                        <td>
                            {if $user.connection_type == 'WiFi 2.4GHz'}<span class="dd-conn dd-conn-24">2.4GHz</span>
                            {elseif $user.connection_type == 'WiFi 5GHz'}<span class="dd-conn dd-conn-5">5GHz</span>
                            {elseif $user.connection_type == 'Ethernet'}<span class="dd-conn dd-conn-lan">LAN</span>
                            {else}<span class="dd-conn dd-conn-unk">Unknown</span>{/if}
                        </td>
                        <td>
                            <span class="dd-badge-iface">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="14" rx="2" stroke="#475569" stroke-width="2"/><line x1="8" y1="21" x2="16" y2="21" stroke="#475569" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="17" x2="12" y2="21" stroke="#475569" stroke-width="2" stroke-linecap="round"/></svg>
                                {$user.interface}
                            </span>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="dd-users-mobile" style="padding:12px;">
            {foreach $connected_users as $user}
            <div class="dd-user-card">
                <div class="dd-user-head">
                    <span class="dd-badge-device">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="2" stroke="#7c3aed" stroke-width="2"/><line x1="12" y1="18" x2="12" y2="18" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/></svg>
                        {if $user.hostname && $user.hostname != ''}{$user.hostname}{else}Unknown{/if}
                    </span>
                    {if $user.connection_type == 'WiFi 2.4GHz'}<span class="dd-conn dd-conn-24">2.4G</span>
                    {elseif $user.connection_type == 'WiFi 5GHz'}<span class="dd-conn dd-conn-5">5G</span>
                    {elseif $user.connection_type == 'Ethernet'}<span class="dd-conn dd-conn-lan">LAN</span>
                    {else}<span class="dd-conn dd-conn-unk">Unknown</span>{/if}
                </div>
                <div class="dd-user-row"><span class="dd-user-key">IP</span><span class="dd-badge-ip" style="font-size:11px;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><circle cx="12" cy="12" r="9" stroke="#0284c7" stroke-width="2"/><line x1="2" y1="12" x2="22" y2="12" stroke="#0284c7" stroke-width="1.5"/><path d="M12 3a13 13 0 0 1 3 9 13 13 0 0 1-3 9 13 13 0 0 1-3-9 13 13 0 0 1 3-9z" stroke="#0284c7" stroke-width="1.5"/></svg>{$user.ip}</span></div>
                <div class="dd-user-row"><span class="dd-user-key">MAC</span><span class="dd-badge-mac" style="font-size:10px;"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><rect x="2" y="6" width="20" height="12" rx="2" stroke="#475569" stroke-width="2"/><path d="M6 12h.01M10 12h.01M14 12h.01M18 12h.01" stroke="#475569" stroke-width="2" stroke-linecap="round"/></svg>{$user.mac}</span></div>
                <div class="dd-user-row"><span class="dd-user-key">Interface</span><span class="dd-badge-iface" style="font-size:11px;">{$user.interface}</span></div>
            </div>
            {/foreach}
        </div>
        {else}
        <div class="dd-empty" style="padding:36px;">
            <i class="ph ph-user-minus"></i><p>No connected users found</p>
        </div>
        {/if}
    </div>
</div>

<!-- ===================== BOTTOM SHEET OVERLAY (shared) ===================== -->
<div id="ddSheetOverlay" onclick="if(event.target===this)ddCloseSheet()">
    <div id="ddSheetCard">
        <div class="dd-sheet-header">
            <div class="dd-sheet-title" id="ddSheetTitle"></div>
            <button class="dd-sheet-close" onclick="ddCloseSheet()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18" stroke="white" stroke-width="2.5" stroke-linecap="round"/><line x1="6" y1="6" x2="18" y2="18" stroke="white" stroke-width="2.5" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="dd-sheet-body" id="ddSheetBody"></div>
        <div class="dd-sheet-footer" id="ddSheetFooter"></div>
    </div>
</div>

<!-- ===================== NOTIFICATION MODAL (shared center) ===================== -->
<div id="ddModalOverlay" onclick="if(event.target===this&&ddModalClosable)ddCloseModal()">
    <div id="ddModalCard">
        <div id="ddModalHeader">
            <div id="ddModalTitle"></div>
            <button id="ddModalClosebtn" onclick="ddCloseModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18" stroke="white" stroke-width="2.5" stroke-linecap="round"/><line x1="6" y1="6" x2="18" y2="18" stroke="white" stroke-width="2.5" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div id="ddModalBody"></div>
        <div id="ddModalFooter"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
var ddBaseUrl = '{$_url}';
var ddDeviceId = '{$device_id_raw}';
{literal}
// ===================== MODAL ENGINE =====================
var ddModalClosable = true;

function ddOpenModal(opts) {
    var overlay  = document.getElementById('ddModalOverlay');
    var header   = document.getElementById('ddModalHeader');
    var title    = document.getElementById('ddModalTitle');
    var body     = document.getElementById('ddModalBody');
    var footer   = document.getElementById('ddModalFooter');
    var closeBtn = document.getElementById('ddModalClosebtn');

    header.style.background = opts.headerColor || 'linear-gradient(135deg,#0271c6,#0359a0)';
    title.innerHTML  = (opts.titleIcon ? '<i class="' + opts.titleIcon + '"></i> ' : '') + (opts.title || '');
    body.innerHTML   = opts.body   || '';
    footer.innerHTML = opts.footer || '';
    ddModalClosable  = opts.closable !== false;
    closeBtn.style.display = ddModalClosable ? 'flex' : 'none';

    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function ddCloseModal() {
    document.getElementById('ddModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function ddShowLoading(title, subtitle) {
    ddOpenModal({
        title: title || 'Mohon Tunggu...',
        titleIcon: 'ph ph-circle-notch',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        closable: false,
        body: '<div style="text-align:center;padding:16px 0;">' +
              '<div class="dd-modal-spinner" style="margin:0 auto 16px;"></div>' +
              '<p style="color:#64748b;font-size:13px;margin:0;">' + (subtitle || 'Memproses permintaan...') + '</p>' +
              '</div>',
        footer: ''
    });
}

function ddShowSuccess(title, subtitle, onOk) {
    var footer = '<button class="bk-btn bk-btn-success" onclick="' + (onOk || 'ddCloseModal()') + '"><i class="ph ph-check" style="font-size:13px;"></i> OK</button>';
    var hdr = document.getElementById('ddModalHeader');
    var ttl = document.getElementById('ddModalTitle');
    var bdy = document.getElementById('ddModalBody');
    var ftr = document.getElementById('ddModalFooter');
    if (hdr) hdr.style.background = 'linear-gradient(135deg,#16a34a,#15803d)';
    if (ttl) ttl.innerHTML = '<i class="ph ph-check-circle"></i> ' + title;
    if (bdy) bdy.innerHTML =
        '<div style="text-align:center;padding:8px 0;">' +
        '<svg class="dd-check-svg" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto 16px;">' +
        '<circle class="dd-check-circle" cx="26" cy="26" r="24"/>' +
        '<path class="dd-check-mark" d="M14 26 l8 8 l16 -16"/>' +
        '</svg>' +
        '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px;">' + title + '</p>' +
        (subtitle ? '<p style="font-size:12px;color:#64748b;margin:0;">' + subtitle + '</p>' : '') +
        '</div>';
    if (ftr) ftr.innerHTML = footer;
    ddModalClosable = true;
    document.getElementById('ddModalClosebtn').style.display = 'flex';
}

function ddShowError(title, subtitle) {
    var hdr = document.getElementById('ddModalHeader');
    var ttl = document.getElementById('ddModalTitle');
    var bdy = document.getElementById('ddModalBody');
    var ftr = document.getElementById('ddModalFooter');
    if (hdr) hdr.style.background = 'linear-gradient(135deg,#dc2626,#b91c1c)';
    if (ttl) ttl.innerHTML = '<i class="ph ph-warning"></i> ' + title;
    if (bdy) bdy.innerHTML =
        '<div style="text-align:center;padding:8px 0;">' +
        '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
        '<i class="ph ph-warning"></i></div>' +
        '<p style="font-size:14px;color:#334155;margin:0;">' + (subtitle || '') + '</p>' +
        '</div>';
    if (ftr) ftr.innerHTML = '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()">Tutup</button>';
    ddModalClosable = true;
    document.getElementById('ddModalClosebtn').style.display = 'flex';
}

// ===================== BOTTOM SHEET ENGINE =====================
function ddOpenSheet(opts) {
    document.getElementById('ddSheetTitle').innerHTML = (opts.icon ? '<i class="' + opts.icon + '"></i> ' : '') + (opts.title || '');
    document.getElementById('ddSheetBody').innerHTML   = opts.body   || '';
    document.getElementById('ddSheetFooter').innerHTML = opts.footer || '';
    document.getElementById('ddSheetOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function ddCloseSheet() {
    document.getElementById('ddSheetOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { ddCloseModal(); ddCloseSheet(); }
});

// ===================== PASSWORD TOGGLES =====================
function togglePassword() {
    var s = document.getElementById('wifi-password');
    var h = document.getElementById('wifi-password-hidden');
    var i = document.getElementById('password-icon');
    if (s.style.display === 'none') { s.style.display='inline'; h.style.display='none'; i.className='ph ph-eye-slash'; }
    else { s.style.display='none'; h.style.display='inline'; i.className='ph ph-eye'; }
}
function toggleSuperPassDisplay() {
    var s = document.getElementById('super-pass-display');
    var h = document.getElementById('super-pass-hidden');
    var i = document.getElementById('super-pass-icon');
    if (s.style.display === 'none') { s.style.display='inline'; h.style.display='none'; i.className='ph ph-eye-slash'; }
    else { s.style.display='none'; h.style.display='inline'; i.className='ph ph-eye'; }
}
function toggleUserPassDisplay() {
    var s = document.getElementById('user-pass-display');
    var h = document.getElementById('user-pass-hidden');
    var i = document.getElementById('user-pass-icon');
    if (s.style.display === 'none') { s.style.display='inline'; h.style.display='none'; i.className='ph ph-eye-slash'; }
    else { s.style.display='none'; h.style.display='inline'; i.className='ph ph-eye'; }
}

// ===================== REFRESH DEVICE =====================
function ddRefreshDevice() {
    ddOpenModal({
        title: 'Refresh Device?',
        titleIcon: 'ph ph-arrows-clockwise',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#0271c6;">' +
              '<i class="ph ph-arrows-clockwise"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Refresh semua parameter device?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Data akan diperbarui dari ACS server.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="ddCloseModal();ddDoRefresh()"><i class="ph ph-arrows-clockwise" style="font-size:13px;"></i> Refresh</button>'
    });
}

function ddDoRefresh() {
    var texts = ['Menghubungi ACS server...', 'Memperbarui parameter...', 'Menyinkronkan data...'];
    ddOpenModal({
        title: 'Refreshing...',
        titleIcon: 'ph ph-arrows-clockwise',
        headerColor: 'linear-gradient(135deg,#0271c6,#0359a0)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="dd-modal-spinner" style="margin:0 auto 20px;"></div>' +
              '<p id="ddRefreshText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });
    var idx = 1;
    var done = false;
    var ajaxDone = false; var ajaxResult = null; var minDone = false;
    function nextText() {
        if (done) return;
        if (idx >= texts.length) idx = 0;
        var el = document.getElementById('ddRefreshText');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function() { var e = document.getElementById('ddRefreshText'); if (e) { e.textContent = texts[idx]; e.style.opacity = '1'; } idx++; if (!done) setTimeout(nextText, 1800); }, 300);
    }
    setTimeout(nextText, 1800);
    setTimeout(function() { minDone = true; tryShowRefreshResult(); }, 3000);
    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_devices/refresh', type: 'GET', data: { device_id: ddDeviceId }, dataType: 'json',
        success: function(r) { ajaxResult = r; ajaxDone = true; tryShowRefreshResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryShowRefreshResult(); }
    });
    function tryShowRefreshResult() {
        if (!ajaxDone || !minDone) return;
        done = true;
        if (ajaxResult && ajaxResult.success) {
            ddShowSuccess('Refresh Berhasil!', 'Memuat ulang halaman...');
            setTimeout(function() { window.location.reload(true); }, 1500);
        } else {
            ddShowError('Refresh Gagal', (ajaxResult && ajaxResult.error) || 'Gagal berkomunikasi dengan server');
        }
    }
}

// ===================== SUMMON DEVICE =====================
function ddSummonDevice() {
    ddOpenModal({
        title: 'Summon Device?',
        titleIcon: 'ph ph-bell-ringing',
        headerColor: 'linear-gradient(135deg,#16a34a,#15803d)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#16a34a;">' +
              '<i class="ph ph-bell-ringing"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Panggil perangkat ini?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Sistem akan menghubungi perangkat dan memperbarui data terbaru.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-success" onclick="ddCloseModal();ddDoSummonSequence()"><i class="ph ph-bell-ringing" style="font-size:13px;"></i> Ya, Panggil!</button>'
    });
}

function ddDoSummonSequence() {
    var texts = ['Mengirim sinyal ke perangkat...', 'Menunggu respons ONU...', 'Mengautentikasi sesi TR-069...'];
    ddOpenModal({
        title: 'Summoning...',
        titleIcon: 'ph ph-bell-ringing',
        headerColor: 'linear-gradient(135deg,#16a34a,#15803d)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="dd-modal-spinner" style="border-top-color:#16a34a;margin:0 auto 20px;"></div>' +
              '<p id="ddSummonText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });
    var idx = 1; var done = false; var ajaxDone = false; var ajaxResult = null; var animDone = false;
    function nextText() {
        if (idx >= texts.length) { animDone = true; tryShowSummonResult(); return; }
        var el = document.getElementById('ddSummonText');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function() { var e = document.getElementById('ddSummonText'); if (e) { e.textContent = texts[idx]; e.style.opacity = '1'; } idx++; setTimeout(nextText, 1600); }, 300);
    }
    setTimeout(nextText, 1600);
    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_devices/summon', type: 'GET', data: { device_id: ddDeviceId }, dataType: 'json', timeout: 15000,
        success: function(r) { ajaxResult = r; ajaxDone = true; tryShowSummonResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryShowSummonResult(); }
    });
    function tryShowSummonResult() {
        if (!ajaxDone || !animDone) return;
        if (ajaxResult && ajaxResult.success) {
            ddShowSuccess('Summon Berhasil!', 'Memuat ulang halaman...');
            setTimeout(function() { window.location.reload(true); }, 2000);
        } else {
            var errMsg = (ajaxResult && ajaxResult.error) ? ajaxResult.error.replace('Failed to send connection request: ', '') : 'Perangkat offline atau tidak dapat dijangkau.';
            ddShowError('Summon Gagal!', ddDeviceId + ': ' + errMsg);
        }
    }
}

function ddSummonGoGreen() {
    ddShowSuccess('Summon Berhasil!', 'Memuat ulang halaman...');
    setTimeout(function() { window.location.reload(true); }, 2000);
}

// ===================== REBOOT DEVICE =====================
function ddRebootDevice() {
    ddOpenModal({
        title: 'Reboot Device?',
        titleIcon: 'ph ph-power',
        headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
              '<i class="ph ph-power"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Reboot perangkat ini?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Perangkat akan restart. Koneksi terputus sementara.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-danger" onclick="ddCloseModal();ddDoReboot()"><i class="ph ph-power" style="font-size:13px;"></i> Ya, Reboot!</button>'
    });
}

function ddDoReboot() {
    var texts = ['Mengirim perintah reboot...','Memutus koneksi perangkat...','Menunggu restart...'];
    ddOpenModal({
        title: 'Rebooting...',
        titleIcon: 'ph ph-power',
        headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="dd-modal-spinner" style="border-top-color:#dc2626;margin:0 auto 20px;"></div>' +
              '<p id="ddRebootText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });
    var idx = 1; var done = false; var ajaxDone = false; var ajaxResult = null; var minDone = false;
    function nextText() {
        if (done) return;
        if (idx >= texts.length) idx = 0;
        var el = document.getElementById('ddRebootText');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function() { var e = document.getElementById('ddRebootText'); if (e) { e.textContent = texts[idx]; e.style.opacity = '1'; } idx++; if (!done) setTimeout(nextText, 1600); }, 300);
    }
    setTimeout(nextText, 1600);
    setTimeout(function() { minDone = true; tryRebootResult(); }, 3000);
    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_devices/reboot', type: 'GET', data: { device_id: ddDeviceId }, dataType: 'json',
        success: function(r) { ajaxResult = r; ajaxDone = true; tryRebootResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryRebootResult(); }
    });
    function tryRebootResult() {
        if (!ajaxDone || !minDone) return;
        done = true;
        if (ajaxResult && ajaxResult.success) {
            ddShowSuccess('Reboot Berhasil!', 'Perintah reboot berhasil dikirim ke perangkat.');
        } else {
            var errMsg = (ajaxResult && ajaxResult.error) ? ajaxResult.error.replace('Failed to send connection request: ', '') : 'Perangkat offline atau tidak dapat dijangkau.';
            ddShowError('Reboot Gagal', ddDeviceId + ': ' + errMsg);
        }
    }
}

// ===================== REFRESH USERS =====================
function ddRefreshUsers() {
    ddShowLoading('Refresh Users...', 'Memperbarui daftar perangkat terhubung');
    $.ajax({
        url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/refresh-users',
        type: 'GET', dataType: 'json',
        success: function(r) {
            if (r && r.success) { ddShowSuccess('Berhasil!', 'Memuat ulang...'); setTimeout(function(){ location.reload(); }, 1200); }
            else { ddShowError('Gagal', r.error || 'Gagal refresh users'); }
        },
        error: function() { ddShowError('Gagal', 'Gagal berkomunikasi dengan server'); }
    });
}

// ===================== REFRESH WEB ADMIN =====================
function ddRefreshWebAdmin() {
    var texts = ['Menghubungi perangkat...','Menunggu koneksi TR-069...','Mengambil parameter admin...'];
    ddOpenModal({
        title: 'Refresh Web Admin',
        titleIcon: 'ph ph-arrows-clockwise',
        headerColor: 'linear-gradient(135deg,#d97706,#b45309)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="dd-modal-spinner" style="border-top-color:#d97706;margin:0 auto 20px;"></div>' +
              '<p id="ddAdminRefreshText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });
    var idx = 1; var done = false;
    function nextText() {
        if (done) return;
        if (idx >= texts.length) idx = 0;
        var el = document.getElementById('ddAdminRefreshText');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function() { var e = document.getElementById('ddAdminRefreshText'); if (e) { e.textContent = texts[idx]; e.style.opacity = '1'; } idx++; if (!done) setTimeout(nextText, 1800); }, 300);
    }
    setTimeout(nextText, 1800);

    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/summon', type: 'GET', dataType: 'json',
        success: function(r) {
            if (r && r.success) {
                setTimeout(function() {
                    done = true;
                    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/refresh-webadmin', type: 'GET', dataType: 'json',
                        success: function(r2) {
                            if (r2 && r2.success) {
                                setTimeout(function() {
                                    ddShowSuccess('Web Admin Diperbarui!', 'Memuat ulang...');
                                    setTimeout(function() { location.reload(); }, 1200);
                                }, 1500);
                            } else { ddShowError('Gagal', r2.error || 'Gagal refresh web admin'); }
                        },
                        error: function() { ddShowError('Gagal', 'Gagal mengambil parameter web admin'); }
                    });
                }, 5000);
            } else { done = true; ddShowError('Gagal', r.error || 'Gagal menghubungi perangkat'); }
        },
        error: function() { done = true; ddShowError('Gagal', 'Gagal summon perangkat'); }
    });
}

// ===================== WIFI SHEET =====================
function ddOpenWifiSheet() {
    var ssidValue = '';
    {/literal}
    {foreach $wifi_info as $k => $v}
        {if strpos($k, 'ssid') !== false && strpos($k, '2g') !== false}
    ssidValue = '{$v|escape:"javascript"}';
        {/if}
    {/foreach}
    {literal}
    ddOpenSheet({
        title: 'Update WiFi Settings',
        icon: 'ph ph-wifi-high',
        body: '<div class="bk-form-group">' +
              '<label class="bk-label"><i class="ph ph-broadcast" style="font-size:10px;color:#7c3aed;"></i> New SSID *</label>' +
              '<input type="text" id="ws-ssid" class="bk-input" value="' + ssidValue + '" placeholder="Nama WiFi" required>' +
              '<span class="bk-help">5GHz otomatis menambahkan suffix "-5G"</span>' +
              '</div>' +
              '<div class="bk-form-group">' +
              '<label class="bk-label"><i class="ph ph-lock" style="font-size:10px;color:#7c3aed;"></i> New Password</label>' +
              '<div class="bk-input-wrap">' +
              '<input type="password" id="ws-pass" class="bk-input" placeholder="Kosongkan jika tidak ingin mengubah" minlength="8">' +
              '<button type="button" class="bk-eye-btn" onclick="ddToggleWsPass()"><i class="ph ph-eye" id="ws-pass-icon"></i></button>' +
              '</div>' +
              '<span class="bk-help">Min 8 karakter</span>' +
              '</div>' +
              '<div class="bk-form-group">' +
              '<label class="bk-label"><i class="ph ph-shield-check" style="font-size:10px;color:#7c3aed;"></i> Force WPA/WPA2 Security</label>' +
              '<div class="dd-toggle-row">' +
              '<label class="dd-toggle"><input type="checkbox" id="ws-force"><span class="dd-toggle-track"></span><span class="dd-toggle-thumb"></span></label>' +
              '<span class="dd-toggle-lbl">Enable untuk paksa WPA/WPA2</span>' +
              '</div>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseSheet()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="ddSubmitWifi()"><i class="ph ph-floppy-disk" style="font-size:13px;"></i> Update WiFi</button>'
    });
}

function ddToggleWsPass() {
    var i = document.getElementById('ws-pass');
    var ic = document.getElementById('ws-pass-icon');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'ph ph-eye-slash'; }
    else { i.type = 'password'; ic.className = 'ph ph-eye'; }
}

function ddSubmitWifi() {
    var ssid  = document.getElementById('ws-ssid').value.trim();
    var pass  = document.getElementById('ws-pass').value.trim();
    var force = document.getElementById('ws-force').checked;
    if (!ssid) { alert('SSID wajib diisi'); return; }
    if (pass && pass.length < 8) { alert('Password minimal 8 karakter'); return; }

    var confirmHTML = '<strong>WiFi 2.4G:</strong> ' + ssid + '<br><strong>WiFi 5G:</strong> ' + ssid + '-5G<br>';
    confirmHTML += pass ? '<strong>Password:</strong> ' + pass : '<strong>Password:</strong> <em>Tidak diubah</em>';
    if (force) confirmHTML += '<br><strong>Security:</strong> WPA/WPA2';

    ddCloseSheet();
    ddOpenModal({
        title: 'Konfirmasi Update WiFi?',
        titleIcon: 'ph ph-wifi-high',
        headerColor: 'linear-gradient(135deg,#7c3aed,#6d28d9)',
        body: '<div style="padding:4px 0;">' + confirmHTML + '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="ddCloseModal();ddDoUpdateWifi(\'' + ssid.replace(/'/g, "\\'") + '\',\'' + pass.replace(/'/g, "\\'") + '\',' + (force?'true':'false') + ')"><i class="ph ph-floppy-disk" style="font-size:13px;"></i> Ya, Update!</button>'
    });
}

function ddDoUpdateWifi(ssid, pass, force) {
    var texts = ['Menghubungi router...','Memperbarui pengaturan WiFi...','Menyimpan konfigurasi...'];
    ddOpenModal({
        title: 'Mengupdate WiFi...',
        titleIcon: 'ph ph-wifi-high',
        headerColor: 'linear-gradient(135deg,#7c3aed,#6d28d9)',
        closable: false,
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div class="dd-modal-spinner" style="border-top-color:#7c3aed;margin:0 auto 20px;"></div>' +
              '<p id="ddWifiText" style="font-size:13px;color:#64748b;margin:0;min-height:20px;transition:opacity 0.3s;">' + texts[0] + '</p>' +
              '</div>',
        footer: ''
    });
    var idx = 1; var done = false; var ajaxDone = false; var ajaxResult = null; var minDone = false;
    function nextText() {
        if (done) return;
        if (idx >= texts.length) idx = 0;
        var el = document.getElementById('ddWifiText');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function() { var e = document.getElementById('ddWifiText'); if (e) { e.textContent = texts[idx]; e.style.opacity = '1'; } idx++; if (!done) setTimeout(nextText, 1800); }, 300);
    }
    setTimeout(nextText, 1800);
    setTimeout(function() { minDone = true; tryWifiResult(); }, 3000);
    $.ajax({
        url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/update-wifi',
        type: 'POST', data: { ssid: ssid, password: pass, force_security: force ? 1 : 0 }, dataType: 'json', timeout: 30000,
        success: function(r) { ajaxResult = r; ajaxDone = true; tryWifiResult(); },
        error:   function()  { ajaxResult = null; ajaxDone = true; tryWifiResult(); }
    });
    function tryWifiResult() {
        if (!ajaxDone || !minDone) return;
        done = true;
        if (ajaxResult && ajaxResult.success) {
            ddOpenModal({
                title: 'WiFi Diperbarui!',
                titleIcon: 'ph ph-check-circle',
                headerColor: 'linear-gradient(135deg,#16a34a,#15803d)',
                closable: false,
                body: '<div style="text-align:center;padding:8px 0;">' +
                      '<div class="dd-modal-spinner" style="border-top-color:#16a34a;margin:0 auto 16px;"></div>' +
                      '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 4px;">WiFi berhasil diperbarui!</p>' +
                      '<p style="font-size:12px;color:#64748b;margin:0;">Menghubungi perangkat untuk sinkronisasi data...</p>' +
                      '</div>',
                footer: ''
            });
            ddAutoSummonAfterWifi(0);
        } else {
            ddShowError('Update Gagal', (ajaxResult && ajaxResult.error) || 'Gagal memperbarui pengaturan WiFi');
        }
    }
}

function ddAutoSummonAfterWifi(retryCount) {
    var maxRetries = 2;
    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_devices/summon', type: 'GET', data: { device_id: ddDeviceId }, dataType: 'json', timeout: 15000,
        success: function(r) {
            if (r && r.success) {
                setTimeout(function() {
                    $.ajax({ url: ddBaseUrl + 'plugin/genieacs_devices/refresh', type: 'GET', data: { device_id: ddDeviceId }, dataType: 'json',
                        success: function() { ddShowSuccess('Selesai!', 'Memuat ulang...'); setTimeout(function() { window.location.reload(true); }, 1500); },
                        error:   function() { ddShowSuccess('WiFi Diperbarui', 'Sinkronisasi gagal. Memuat ulang...'); setTimeout(function() { window.location.reload(true); }, 1500); }
                    });
                }, 8000);
            } else {
                if (retryCount < maxRetries) { setTimeout(function() { ddAutoSummonAfterWifi(retryCount + 1); }, 5000); }
                else { ddShowSuccess('WiFi Diperbarui', 'Halaman akan dimuat ulang.'); setTimeout(function() { window.location.reload(true); }, 1500); }
            }
        },
        error: function() {
            if (retryCount < maxRetries) { setTimeout(function() { ddAutoSummonAfterWifi(retryCount + 1); }, 5000); }
            else { ddShowSuccess('WiFi Diperbarui', 'Memuat ulang...'); setTimeout(function() { window.location.reload(true); }, 1500); }
        }
    });
}

// ===================== TAGS SHEET =====================
function ddOpenTagsSheet() {
    var allTags = '{/literal}{$device.all_tags|default:""}{literal}';
    var tag1Val = '{/literal}{$device.tags|default:""}{literal}';
    var tag2Val = '{/literal}{$device.lokasi|default:""}{literal}';
    var t1 = '', t2 = '';
    if (allTags && allTags !== 'N/A' && allTags !== '') {
        var arr = allTags.split(',');
        t1 = arr.length > 0 ? arr[0].trim() : '';
        t2 = arr.length > 1 ? arr[1].trim() : '';
    } else {
        if (tag1Val && tag1Val !== 'N/A') t1 = tag1Val;
        if (tag2Val && tag2Val !== 'N/A') t2 = tag2Val;
    }
    ddOpenSheet({
        title: 'Edit Device Tags',
        icon: 'ph ph-tag',
        body: '<div class="bk-form-group">' +
              '<label class="bk-label"><i class="ph ph-user" style="font-size:10px;color:#0271c6;"></i> Username Tag *</label>' +
              '<input type="text" id="ts-tag1" class="bk-input" placeholder="Customer username" value="' + t1 + '">' +
              '</div>' +
              '<div class="bk-form-group">' +
              '<label class="bk-label"><i class="ph ph-map-pin" style="font-size:10px;color:#d97706;"></i> Lokasi Tag</label>' +
              '<input type="text" id="ts-tag2" class="bk-input" placeholder="Lokasi / area" value="' + t2 + '">' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseSheet()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="ddSubmitTags()"><i class="ph ph-floppy-disk" style="font-size:13px;"></i> Simpan Tags</button>'
    });
}

function ddSubmitTags() {
    var t1 = document.getElementById('ts-tag1').value.trim();
    var t2 = document.getElementById('ts-tag2').value.trim();
    if (!t1) { alert('Username tag wajib diisi'); return; }
    var tags = [t1];
    if (t2) tags.push(t2);
    ddCloseSheet();
    ddShowLoading('Menyimpan Tags...', 'Mohon tunggu');
    $.ajax({
        url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/update-tags',
        type: 'POST', data: { tags: tags }, dataType: 'json',
        success: function(r) {
            if (r && r.success) { ddShowSuccess('Tags Diperbarui!', 'Memuat ulang...'); setTimeout(function() { location.reload(); }, 1200); }
            else { ddShowError('Gagal', r.error || 'Gagal menyimpan tags'); }
        },
        error: function() { ddShowError('Gagal', 'Gagal berkomunikasi dengan server'); }
    });
}

// ===================== ADMIN SHEET =====================
function ddOpenAdminSheet() {
    var su = '{/literal}{$web_admin.super_username|default:"admin"|escape:"javascript"}{literal}';
    var uu = '{/literal}{$web_admin.user_username|default:"user"|escape:"javascript"}{literal}';
    ddOpenSheet({
        title: 'Edit Web Admin Credentials',
        icon: 'ph ph-lock',
        body: '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">' +
              '<div class="bk-form-group"><label class="bk-label">Super Admin Username</label><input type="text" id="as-suser" class="bk-input" value="' + su + '"></div>' +
              '<div class="bk-form-group"><label class="bk-label">Super Admin Password</label><div class="bk-input-wrap"><input type="password" id="as-spass" class="bk-input" placeholder="Enter password"><button type="button" class="bk-eye-btn" onclick="ddToggleAsPass(\'as-spass\',\'as-spass-icon\')"><i class="ph ph-eye" id="as-spass-icon"></i></button></div></div>' +
              '<div class="bk-form-group"><label class="bk-label">User Username</label><input type="text" id="as-uuser" class="bk-input" value="' + uu + '"></div>' +
              '<div class="bk-form-group"><label class="bk-label">User Password</label><div class="bk-input-wrap"><input type="password" id="as-upass" class="bk-input" placeholder="Enter password"><button type="button" class="bk-eye-btn" onclick="ddToggleAsPass(\'as-upass\',\'as-upass-icon\')"><i class="ph ph-eye" id="as-upass-icon"></i></button></div></div>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseSheet()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-primary" onclick="ddSubmitAdmin()"><i class="ph ph-floppy-disk" style="font-size:13px;"></i> Update Credentials</button>'
    });
}

function ddToggleAsPass(inputId, iconId) {
    var i = document.getElementById(inputId);
    var ic = document.getElementById(iconId);
    if (i.type === 'password') { i.type = 'text'; ic.className = 'ph ph-eye-slash'; }
    else { i.type = 'password'; ic.className = 'ph ph-eye'; }
}

function ddSubmitAdmin() {
    var su = document.getElementById('as-suser').value.trim();
    var sp = document.getElementById('as-spass').value;
    var uu = document.getElementById('as-uuser').value.trim();
    var up = document.getElementById('as-upass').value;
    if (!su || !uu) { alert('Username tidak boleh kosong'); return; }
    ddCloseSheet();
    ddOpenModal({
        title: 'Update Credentials?',
        titleIcon: 'ph ph-lock',
        headerColor: 'linear-gradient(135deg,#dc2626,#b91c1c)',
        body: '<div style="text-align:center;padding:8px 0;">' +
              '<div style="width:64px;height:64px;border-radius:16px;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#dc2626;">' +
              '<i class="ph ph-lock"></i></div>' +
              '<p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Update router login credentials?</p>' +
              '<p style="font-size:12px;color:#94a3b8;margin:0;">Pastikan username dan password sudah benar.</p>' +
              '</div>',
        footer: '<button class="bk-btn bk-btn-ghost" onclick="ddCloseModal()"><i class="ph ph-x" style="font-size:13px;"></i> Batal</button>' +
                '<button class="bk-btn bk-btn-danger" onclick="ddCloseModal();ddDoUpdateAdmin(\'' + su.replace(/'/g,"\\'") + '\',\'' + sp.replace(/'/g,"\\'") + '\',\'' + uu.replace(/'/g,"\\'") + '\',\'' + up.replace(/'/g,"\\'") + '\')"><i class="ph ph-floppy-disk" style="font-size:13px;"></i> Ya, Update!</button>'
    });
}

function ddDoUpdateAdmin(su, sp, uu, up) {
    ddShowLoading('Updating Credentials...', 'Mohon tunggu');
    $.ajax({
        url: ddBaseUrl + 'plugin/genieacs_device_detail/' + ddDeviceId + '/update-admin',
        type: 'POST',
        data: { super_username: su, super_password: sp, user_username: uu, user_password: up },
        dataType: 'json',
        success: function(r) {
            if (r && r.success) { ddShowSuccess('Credentials Diperbarui!', 'Memuat ulang...'); setTimeout(function() { location.reload(); }, 1200); }
            else { ddShowError('Gagal', r.error || 'Gagal update credentials'); }
        },
        error: function() { ddShowError('Gagal', 'Gagal berkomunikasi dengan server'); }
    });
}
{/literal}
</script>

{include file="sections/footer.tpl"}
