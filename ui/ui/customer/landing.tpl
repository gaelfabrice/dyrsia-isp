{assign var=companyName value=$_c['CompanyName']|default:'DYRSIA'}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$companyName} - Gestion de Wifi Zone</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{$app_url}/ui/ui/images/favicon.png" />
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="{$app_url}/ui/ui/images/apple-touch-icon.png" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">
    <style>
        {literal}
        :root {
            --bg: #030712;
            --text: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(148, 163, 184, 0.2);
            --brand: #22c55e;
            --brand2: #06b6d4;
            --card: rgba(15, 23, 42, 0.65);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body {
            margin: 0;
            overflow-x: hidden;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(34, 197, 94, 0.14), transparent),
                radial-gradient(circle at 90% 10%, rgba(6, 182, 212, 0.1), transparent 40%),
                var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        a { color: var(--brand2); text-decoration: none; }
        a:hover { color: #67e8f9; }
        .container { width: min(1180px, 100%); margin: 0 auto; padding: 0 20px; }
        .site-header {
            position: sticky; top: 0; z-index: 200;
            border-bottom: 1px solid var(--line);
            background: rgba(3, 7, 18, 0.9);
            backdrop-filter: blur(14px);
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 12px 0;
        }
        .brand {
            display: inline-flex; align-items: center; gap: 10px;
            font-weight: 900; font-size: 18px; color: var(--text); text-decoration: none;
            flex-shrink: 0;
        }
        .brand-icon {
            width: 38px; height: 38px; border-radius: 11px;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            display: flex; align-items: center; justify-content: center;
            color: #021014; flex-shrink: 0;
        }
        .brand-icon .fa { font-size: 18px; line-height: 1; }
        .nav-links {
            display: flex; align-items: center; justify-content: flex-end;
            gap: 8px; flex-wrap: wrap; flex: 1;
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 8px 14px; border-radius: 999px; font-weight: 700; font-size: 12px;
            border: 1px solid var(--line); color: var(--text);
            background: rgba(255, 255, 255, 0.05);
            cursor: pointer; text-decoration: none; white-space: nowrap;
        }
        .btn:hover { color: #fff; border-color: rgba(34, 197, 94, 0.45); text-decoration: none; }
        .btn-primary {
            border: 0; color: #021014;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
        }
        .btn-primary:hover { color: #021014; filter: brightness(1.06); }
        .btn-ghost { background: transparent; }
        .hero { padding: 56px 0 48px; text-align: center; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 700;
            border: 1px solid rgba(34, 197, 94, 0.35);
            background: rgba(34, 197, 94, 0.1); color: #bbf7d0; margin-bottom: 22px;
        }
        .hero h1 {
            font-size: clamp(32px, 5.5vw, 54px); line-height: 1.08;
            letter-spacing: -1.5px; margin: 0 0 18px; font-weight: 900; color: #fff;
        }
        .hero p {
            max-width: 700px; margin: 0 auto 28px; color: var(--muted);
            font-size: 17px; line-height: 1.7;
        }
        .hero-cta {
            display: flex; gap: 12px; justify-content: center;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .rating { color: var(--muted); font-size: 14px; margin: 0; }
        .rating strong { color: #fde047; }
        .section { padding: 64px 0; }
        .section-band {
            padding: 64px 0;
            background: rgba(255, 255, 255, 0.03);
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }
        .section-head { text-align: center; max-width: 640px; margin: 0 auto 40px; }
        .section-head h2 {
            font-size: clamp(26px, 4vw, 34px); letter-spacing: -0.5px;
            margin: 0 0 10px; font-weight: 900;
        }
        .section-head p { color: var(--muted); margin: 0; font-size: 16px; }
        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .feat-card {
            padding: 24px;
            border-radius: 18px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(34, 197, 94, 0.05), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .feat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand), var(--brand2));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.35s ease;
        }
        .feat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(34, 197, 94, 0.35);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
        }
        .feat-card:hover::before {
            transform: scaleX(1);
        }
        .feat-card-highlight {
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(6, 182, 212, 0.1), transparent 55%),
                linear-gradient(165deg, rgba(6, 182, 212, 0.14) 0%, rgba(13, 20, 36, 0.94) 100%);
            border-color: rgba(6, 182, 212, 0.3);
        }
        .feat-card-highlight::before {
            background: linear-gradient(90deg, var(--brand2), #22d3ee);
        }
        .feat-card-highlight:hover {
            border-color: rgba(6, 182, 212, 0.5);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
        }
        .feat-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: linear-gradient(135deg, var(--brand2), #0891b2);
            color: #021014;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        .feat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: linear-gradient(145deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.08));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.18);
        }
        .feat-card-icon .fa {
            font-size: 21px;
            color: var(--brand);
            line-height: 1;
        }
        .feat-card-icon-accent {
            background: linear-gradient(145deg, rgba(6, 182, 212, 0.22), rgba(6, 182, 212, 0.08));
            box-shadow: inset 0 0 0 1px rgba(6, 182, 212, 0.2);
        }
        .feat-card-icon-accent .fa {
            color: var(--brand2);
        }
        .feat-card h3 {
            margin: 0 0 10px;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
        }
        .feat-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }
        .feat-logos {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid rgba(148, 163, 184, 0.12);
        }
        .feat-logos img {
            height: 30px;
            width: auto;
            object-fit: contain;
            border-radius: 7px;
            opacity: 0.92;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .feat-logos img:hover {
            opacity: 1;
            transform: scale(1.05);
        }
        @media (max-width: 991px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .step { text-align: center; padding: 12px 10px; }
        .step-num {
            width: 48px; height: 48px; border-radius: 50%; margin: 0 auto 14px;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            color: #021014; font-weight: 900; font-size: 20px;
            display: flex; align-items: center; justify-content: center;
        }
        .step h3 { margin: 0 0 8px; font-size: 16px; }
        .step p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.55; }
        /* Solutions physiques */
        .section-overline {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 12px;
        }
        .solutions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .solution-card {
            padding: 28px;
            border-radius: 18px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(34, 197, 94, 0.05), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .solution-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand), var(--brand2));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.35s ease;
        }
        .solution-card:hover {
            transform: translateY(-5px);
            border-color: rgba(34, 197, 94, 0.35);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
        }
        .solution-card:hover::before {
            transform: scaleX(1);
        }
        .solution-icon {
            width: 54px;
            height: 54px;
            border-radius: 15px;
            background: linear-gradient(145deg, rgba(34, 197, 94, 0.2), rgba(6, 182, 212, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.18);
        }
        .solution-icon .fa {
            font-size: 24px;
            color: var(--brand);
            line-height: 1;
        }
        .solution-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
        }
        .solution-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.65;
        }
        .solutions-cta {
            text-align: center;
            margin-top: 36px;
        }
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 26px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 15px;
            color: #fff;
            background: linear-gradient(135deg, #25d366, #128c7e);
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .btn-whatsapp:hover {
            color: #fff;
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 12px 30px rgba(37, 211, 102, 0.45);
        }
        .btn-whatsapp .wa-icon {
            flex-shrink: 0;
        }
        @media (max-width: 880px) {
            .solutions-grid {
                grid-template-columns: 1fr;
                max-width: 460px;
                margin: 0 auto;
            }
        }
        .demo-panel {
            padding: 36px 28px; border-radius: 22px; text-align: center;
            background:
                radial-gradient(120% 100% at 50% 0%, rgba(6, 182, 212, 0.08), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
        }
        .demo-panel .demo-icon {
            width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 14px;
            background: rgba(6, 182, 212, 0.15);
            display: flex; align-items: center; justify-content: center;
        }
        .demo-panel .demo-icon .fa { font-size: 26px; color: var(--brand2); }
        .demo-panel h2 { margin: 0 0 10px; font-size: 24px; }
        .demo-panel p { color: var(--muted); max-width: 520px; margin: 0 auto 18px; font-size: 15px; }
        .demo-showcase {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
            gap: 28px;
            align-items: stretch;
            padding: 32px;
            border-radius: 24px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(6, 182, 212, 0.1), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.85) 0%, rgba(13, 20, 36, 0.95) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
        }
        .demo-mockup {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #0b1220;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
        }
        .demo-mockup-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        }
        .demo-dot { width: 10px; height: 10px; border-radius: 50%; }
        .demo-dot.red { background: #ef4444; }
        .demo-dot.yellow { background: #f59e0b; }
        .demo-dot.green { background: #22c55e; }
        .demo-mockup-title {
            margin-left: 8px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }
        .demo-mockup-body {
            display: grid;
            grid-template-columns: 180px 1fr;
            min-height: 280px;
        }
        .demo-sidebar {
            padding: 18px 14px;
            border-right: 1px solid rgba(148, 163, 184, 0.1);
            background: rgba(255, 255, 255, 0.02);
        }
        .demo-sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .demo-sidebar-item.active {
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
        }
        .demo-sidebar-item .fa { width: 16px; text-align: center; }
        .demo-main { padding: 18px; }
        .demo-kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .demo-kpi {
            padding: 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .demo-kpi small {
            display: block;
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }
        .demo-kpi strong {
            font-size: 20px;
            color: #fff;
        }
        .demo-chart {
            height: 120px;
            border-radius: 14px;
            background:
                linear-gradient(180deg, rgba(34, 197, 94, 0.18), transparent 70%),
                rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            overflow: hidden;
        }
        .demo-chart::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 18px;
            height: 3px;
            background: linear-gradient(90deg, var(--brand), var(--brand2));
            border-radius: 999px;
            transform: skewY(-3deg);
        }
        .demo-router-card {
            margin-top: 14px;
            padding: 14px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.2);
        }
        .demo-router-card .fa-server { color: var(--brand2); font-size: 22px; }
        .demo-router-meta strong { display: block; color: #fff; font-size: 13px; }
        .demo-router-meta span { color: var(--muted); font-size: 11px; }
        .demo-status {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
        }
        .demo-access {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .demo-access h2 {
            margin: 0 0 10px;
            font-size: 24px;
            text-align: left;
        }
        .demo-access p {
            margin: 0 0 18px;
            text-align: left;
            max-width: none;
        }
        .demo-credentials {
            padding: 18px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.14);
            margin-bottom: 18px;
        }
        .demo-credentials p {
            margin: 0 0 10px;
            font-size: 14px;
            color: #cbd5e1;
            text-align: left;
        }
        .demo-credentials p:last-child { margin-bottom: 0; }
        .demo-credentials code {
            padding: 3px 8px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
            font-weight: 700;
        }
        .demo-note {
            margin-top: 12px;
            font-size: 12px;
            color: var(--muted);
            text-align: left;
        }
        @media (max-width: 900px) {
            .demo-showcase {
                grid-template-columns: 1fr;
            }
            .demo-mockup-body {
                grid-template-columns: 1fr;
            }
            .demo-sidebar {
                display: none;
            }
        }
        .pricing-wrap {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px; align-items: start;
        }
        .calc-card, .provision-card {
            padding: 28px; border-radius: 22px;
            background: var(--card); border: 1px solid var(--line);
        }
        .calc-card h3, .provision-card h3 { margin: 0 0 6px; font-size: 20px; }
        .sub { color: var(--muted); font-size: 14px; margin: 0 0 20px; }
        .field { margin-bottom: 16px; }
        .field label {
            display: block; font-size: 12px; font-weight: 700;
            color: #cbd5e1; margin-bottom: 7px;
        }
        .field input {
            width: 100%; height: 44px; padding: 0 14px;
            border-radius: 12px; border: 1px solid var(--line);
            background: #0f172a; color: #f8fafc; font-size: 14px;
            -webkit-text-fill-color: #f8fafc;
        }
        .field input::placeholder { color: #64748b; }
        .field input:focus {
            outline: none; border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }
        .subdomain-row { display: flex; align-items: stretch; }
        .subdomain-row input {
            border-radius: 12px 0 0 12px; border-right: 0; flex: 1; min-width: 0;
        }
        .subdomain-suffix {
            display: flex; align-items: center; padding: 0 12px;
            border: 1px solid var(--line); border-left: 0;
            border-radius: 0 12px 12px 0;
            background: rgba(255, 255, 255, 0.05);
            color: var(--muted); font-weight: 700; font-size: 13px; white-space: nowrap;
        }
        .range-wrap input[type=range] { width: 100%; accent-color: var(--brand); }
        .range-val {
            display: flex; justify-content: space-between;
            font-size: 12px; color: var(--muted); margin-top: 6px;
        }
        .fee-box {
            margin-top: 20px; padding: 20px; border-radius: 16px;
            background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.22);
            text-align: center;
        }
        .fee-box .amount { font-size: 36px; font-weight: 900; color: var(--brand); line-height: 1; }
        .fee-box .per { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .fee-breakdown { margin-top: 12px; font-size: 13px; color: var(--muted); text-align: left; }
        .admin-card {
            max-width: 460px; margin: 36px auto 0; padding: 26px;
            border-radius: 20px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(34, 197, 94, 0.05), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14); text-align: center;
        }
        .admin-card h3 { margin: 0 0 8px; font-size: 18px; }
        .admin-card p { color: var(--muted); font-size: 14px; margin: 0 0 18px; }
        .footer {
            padding: 32px 0; text-align: center; color: var(--muted);
            font-size: 13px; border-top: 1px solid var(--line);
        }
        .btn-block { width: 100%; }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            align-items: stretch;
        }
        .pricing-card {
            position: relative;
            padding: 28px 24px;
            border-radius: 22px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(34, 197, 94, 0.05), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
            border-color: rgba(34, 197, 94, 0.35);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
        }
        .pricing-card.pricing-featured {
            border: 2px solid var(--brand);
            background: linear-gradient(180deg, rgba(34, 197, 94, 0.08) 0%, var(--card) 100%);
            transform: scale(1.02);
        }
        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            color: #021014;
            white-space: nowrap;
        }
        .pricing-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
        }
        .pricing-header h3 {
            margin: 0 0 12px;
            font-size: 20px;
            font-weight: 800;
        }
        .pricing-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 6px;
        }
        .pricing-price .amount {
            font-size: 32px;
            font-weight: 900;
            color: var(--brand);
            line-height: 1;
        }
        .pricing-price .period {
            font-size: 14px;
            color: var(--muted);
        }
        .pricing-note {
            margin: 10px 0 0;
            font-size: 12px;
            color: var(--muted);
            font-style: italic;
        }
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
            flex: 1;
        }
        .pricing-features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            font-size: 14px;
            color: #cbd5e1;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .pricing-features li:last-child {
            border-bottom: 0;
        }
        .pricing-features li .fa {
            color: var(--brand);
            margin-top: 3px;
            flex-shrink: 0;
        }
        .pricing-card .btn {
            margin-top: auto;
        }
        @media (min-width: 768px) {
            .nav-links { flex-wrap: nowrap; }
        }
        @media (max-width: 991px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 420px;
                margin: 0 auto;
            }
            .pricing-card.pricing-featured {
                transform: none;
                order: -1;
            }
        }
        @media (max-width: 767px) {
            .nav { flex-wrap: wrap; justify-content: center; }
            .brand { width: 100%; justify-content: center; }
            .nav-links { width: 100%; justify-content: center; flex-wrap: wrap; }
        }
        /* Reassurance Card */
        .reassurance-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 28px;
            margin-bottom: 32px;
            border-radius: 22px;
            background:
                radial-gradient(120% 100% at 0% 0%, rgba(34, 197, 94, 0.06), transparent 55%),
                linear-gradient(165deg, rgba(30, 41, 59, 0.8) 0%, rgba(13, 20, 36, 0.92) 100%);
            border: 1px solid rgba(148, 163, 184, 0.14);
        }
        .reassurance-badge {
            position: absolute;
            top: -11px;
            left: 24px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
        .reassurance-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            border-radius: 16px;
            background: rgba(6, 182, 212, 0.12);
        }
        .reassurance-icon > .fa-cloud {
            font-size: 32px;
            color: var(--brand2);
        }
        .reassurance-icon .lock-overlay {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reassurance-icon .lock-overlay .fa-lock {
            font-size: 10px;
            color: #021014;
        }
        .reassurance-content {
            flex: 1;
        }
        .reassurance-content h3 {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
        }
        .savings-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.1);
            color: var(--brand);
            margin-bottom: 10px;
        }
        .reassurance-content p {
            margin: 0;
            font-size: 14px;
            color: var(--muted);
            line-height: 1.65;
        }
        @media (max-width: 640px) {
            .reassurance-card {
                flex-direction: column;
                text-align: center;
                padding: 32px 20px 24px;
            }
            .reassurance-badge {
                left: 50%;
                transform: translateX(-50%);
            }
            .savings-tag {
                margin-top: 4px;
            }
        }
        {/literal}
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="nav">
                <a class="brand" href="#top">
                    <span class="brand-icon"><i class="fa fa-bolt"></i></span>
                    <span>{$companyName}</span>
                </a>
                <div class="nav-links">
                    <a class="btn btn-ghost" href="#features">Fonctionnalités</a>
                    <a class="btn btn-ghost" href="#solutions">Solutions</a>
                    <a class="btn btn-ghost" href="#pricing">Tarifs</a>
                    <a class="btn btn-ghost" href="{Text::url('faq')}"><i class="fa fa-question-circle"></i> FAQ</a>
                    <a class="btn" href="{Text::url('provision')}"><i class="fa fa-rocket"></i> Essai gratuit</a>
                    <a class="btn btn-primary" href="{Text::url('admin/')}"><i class="fa fa-lock"></i> Admin</a>
                </div>
            </nav>
        </div>
    </header>

    <main id="top">
        <section class="container hero">
            <span class="pill"><i class="fa fa-shield"></i> La référence des Wifi Zone en Afrique</span>
            <h1>Gérez votre Wifi Zone, Sans aucune limite.<br>Gérez tous vos routeurs en même temps</h1>
            <p>Pilotez vos opérations Hotspot, PPPoE et IP statique, automatisez vos encaissements et développez votre activité d'accès internet avec une solution de gestion tout-en-un. Obtenez vos gains rapidement.</p>
            <div class="hero-cta">
                <a class="btn btn-primary" href="{Text::url('provision')}"><i class="fa fa-play-circle"></i> Démarrer l'essai gratuit</a>
                <a class="btn" href="#demo"><i class="fa fa-video-camera"></i> Voir la démo</a>
            </div>
            <p class="rating"><strong>★★★★★</strong> Noté 4.9/5 par plus de 150 gérants de Wifi Zone</p>
        </section>

        <section class="section container" id="features">
            <div class="section-head">
                <span class="section-overline">Fonctionnalités</span>
                <h2>Tout pour gérer votre réseau sans prise de tête</h2>
                <p>Une suite d'outils puissants pensés pour maximiser vos revenus et automatiser votre Wifi Zone.</p>
            </div>
            <div class="features-grid">
                <article class="feat-card">
                    <div class="feat-card-icon"><i class="fa fa-credit-card"></i></div>
                    <h3>Encaissez en 1 clic via Mobile Money</h3>
                    <p>Vos clients choisissent leur forfait, payent instantanément par Mobile Money, et reçoivent leur accès internet automatiquement.</p>
                </article>
                <article class="feat-card">
                    <div class="feat-card-icon"><i class="fa fa-wifi"></i></div>
                    <h3>Vente au ticket et abonnements mensuels</h3>
                    <p>Gérez les clients de rue (Hotspot) et les abonnés à domicile (PPPoE fibre/antenne) sur un seul écran. Déconnexion automatique dès que le forfait expire.</p>
                </article>
                <article class="feat-card feat-card-highlight">
                    <span class="feat-badge">Exclusif</span>
                    <div class="feat-card-icon feat-card-icon-accent"><i class="fa fa-globe"></i></div>
                    <h3>Zéro frais d'IP publique</h3>
                    <p>Pilotez, redémarrez et configurez vos routeurs MikroTik à distance depuis votre téléphone. Économisez le coût d'un abonnement IP fixe chez votre opérateur.</p>
                </article>
                <article class="feat-card">
                    <div class="feat-card-icon"><i class="fa fa-bar-chart"></i></div>
                    <h3>Suivez vos recettes en direct</h3>
                    <p>Visualisez vos gains quotidiens, identifiez vos forfaits les plus vendus et suivez le nombre d'utilisateurs actifs grâce à des graphiques simples et clairs.</p>
                </article>
                <article class="feat-card">
                    <div class="feat-card-icon"><i class="fa fa-shield"></i></div>
                    <h3>Bloquez le partage de tickets</h3>
                    <p>Protégez votre bande passante. Associez automatiquement chaque ticket à l'appareil de l'acheteur pour empêcher les clients de partager un seul code.</p>
                </article>
                <article class="feat-card">
                    <div class="feat-card-icon"><i class="fa fa-bolt"></i></div>
                    <h3>Déploiement MikroTik en 5 minutes</h3>
                    <p>Personnalisez votre page de connexion (Portail Captif) à votre image et injectez nos scripts d'automatisation dans votre routeur en quelques clics.</p>
                </article>
            </div>
        </section>

        <section class="section-band" id="solutions">
            <div class="container">
                <div class="section-head">
                    <span class="section-overline">Solutions physiques</span>
                    <h2>Au-delà du logiciel : Un accompagnement de A à Z</h2>
                    <p>Vous lancez votre projet ? Nous vous fournissons la technique et les équipements.</p>
                </div>
                <div class="solutions-grid">
                    <article class="solution-card">
                        <div class="solution-icon"><i class="fa fa-cube"></i></div>
                        <h3>Achat de matériel sécurisé</h3>
                        <p>Ne prenez aucun risque sur vos équipements. Nous vous guidons vers les meilleurs routeurs MikroTik et antennes Ubiquiti ou TP-Link au meilleur prix du marché local.</p>
                    </article>
                    <article class="solution-card">
                        <div class="solution-icon"><i class="fa fa-map-o"></i></div>
                        <h3>Étude de projet Wifi Zone</h3>
                        <p>Vous avez le quartier, nous avons l'expertise. Nous analysons votre zone de couverture pour optimiser le placement de vos antennes et maximiser la portée de votre signal.</p>
                    </article>
                    <article class="solution-card">
                        <div class="solution-icon"><i class="fa fa-terminal"></i></div>
                        <h3>Conseil &amp; Configuration</h3>
                        <p>Configuration complète de vos scripts, optimisation fine de votre bande passante et sécurisation totale de votre réseau MikroTik contre le piratage de tickets.</p>
                    </article>
                </div>
                <div class="solutions-cta">
                    <a class="btn-whatsapp" href="https://wa.me/237600000000?text=Bonjour%2C%20je%20souhaite%20un%20accompagnement%20pour%20mon%20projet%20Wifi%20Zone" target="_blank" rel="noopener">
                        <svg class="wa-icon" viewBox="0 0 32 32" width="22" height="22" aria-hidden="true">
                            <path fill="currentColor" d="M16.04 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.26.6 4.46 1.73 6.4L3.2 28.8l6.56-1.72a12.74 12.74 0 0 0 6.28 1.64h.01c7.05 0 12.8-5.74 12.8-12.8 0-3.42-1.33-6.63-3.75-9.05a12.7 12.7 0 0 0-9.06-3.67zm0 23.45h-.01a10.6 10.6 0 0 1-5.4-1.48l-.39-.23-4.02 1.05 1.07-3.92-.25-.4a10.6 10.6 0 0 1-1.62-5.66c0-5.87 4.78-10.65 10.66-10.65 2.85 0 5.52 1.11 7.53 3.12a10.57 10.57 0 0 1 3.12 7.54c0 5.87-4.78 10.63-10.66 10.63zm5.85-7.97c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.5-.16-.72.16-.21.32-.82 1.03-1.01 1.25-.18.21-.37.24-.69.08-.32-.16-1.36-.5-2.58-1.6-.96-.85-1.6-1.9-1.79-2.22-.18-.32-.02-.5.14-.66.15-.14.32-.37.48-.56.16-.18.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.38-.26-.62-.52-.54-.72-.55l-.61-.01c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.66 0 1.56 1.14 3.07 1.3 3.28.16.21 2.25 3.43 5.44 4.81.76.33 1.35.52 1.81.67.76.24 1.46.21 2 .13.61-.09 1.9-.78 2.16-1.53.27-.74.27-1.38.19-1.52-.08-.13-.29-.21-.61-.37z"/>
                        </svg>
                        Discuter sur WhatsApp
                    </a>
                </div>
            </div>
        </section>

        <section class="section container" id="demo">
            <div class="demo-showcase">
                <div class="demo-mockup" aria-hidden="true">
                    <div class="demo-mockup-bar">
                        <span class="demo-dot red"></span>
                        <span class="demo-dot yellow"></span>
                        <span class="demo-dot green"></span>
                        <span class="demo-mockup-title">Dashboard ISP — Wifi Zone Manager</span>
                    </div>
                    <div class="demo-mockup-body">
                        <div class="demo-sidebar">
                            <div class="demo-sidebar-item active"><i class="fa fa-dashboard"></i> Tableau de bord</div>
                            <div class="demo-sidebar-item"><i class="fa fa-wifi"></i> Hotspot</div>
                            <div class="demo-sidebar-item"><i class="fa fa-exchange"></i> PPPoE</div>
                            <div class="demo-sidebar-item"><i class="fa fa-server"></i> Routeurs</div>
                            <div class="demo-sidebar-item"><i class="fa fa-money"></i> Finances</div>
                        </div>
                        <div class="demo-main">
                            <div class="demo-kpi-row">
                                <div class="demo-kpi">
                                    <small>Clients actifs</small>
                                    <strong>128</strong>
                                </div>
                                <div class="demo-kpi">
                                    <small>Recettes du jour</small>
                                    <strong>24 500 F</strong>
                                </div>
                                <div class="demo-kpi">
                                    <small>Routeurs en ligne</small>
                                    <strong>3/3</strong>
                                </div>
                            </div>
                            <div class="demo-chart"></div>
                            <div class="demo-router-card">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <i class="fa fa-server"></i>
                                    <div class="demo-router-meta">
                                        <strong>MikroTik RB750Gr3 — Zone Nord</strong>
                                        <span>Tunnel VPN sécurisé • Dernière sync il y a 2 min</span>
                                    </div>
                                </div>
                                <span class="demo-status">Connecté</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="demo-access">
                    <h2>Découvrez la plateforme en action</h2>
                    <p>Explorez un tableau de bord complet avec des données fictives générées automatiquement. Idéal pour une démonstration sans connecter de routeur réel.</p>
                    <div class="demo-credentials">
                        <p><strong>Identifiant :</strong> <code>Demo</code></p>
                        <p><strong>Mot de passe :</strong> <code>wifizone</code></p>
                    </div>
                    <a class="btn btn-primary" href="{Text::url('admin/')}"><i class="fa fa-sign-in"></i> Se connecter à la démo</a>
                    <p class="demo-note">Les statistiques changent à chaque nouvelle adresse IP pour simuler une activité réaliste. Aucune synchronisation MikroTik n'est effectuée.</p>
                </div>
            </div>
        </section>

        <section class="section container" id="pricing">
            <div class="section-head">
                <span class="section-overline">Tarifs</span>
                <h2>Une tarification simple et transparente</h2>
                <p>Choisissez l'offre adaptée à votre activité. Aucun frais caché.</p>
            </div>

            <!-- Élément de réassurance - Tunnel VPN -->
            <div class="reassurance-card">
                <span class="reassurance-badge"><i class="fa fa-check-circle"></i> Inclus sans frais</span>
                <div class="reassurance-icon">
                    <i class="fa fa-cloud"></i>
                    <span class="lock-overlay"><i class="fa fa-lock"></i></span>
                </div>
                <div class="reassurance-content">
                    <h3>🔥 Inclus dans tous nos plans : Tunnel de gestion à distance sécurisé</h3>
                    <span class="savings-tag"><i class="fa fa-tag"></i> Économisez le coût d'une IP publique</span>
                    <p>Oubliez les frais mensuels des opérateurs mobiles pour obtenir une IP fixe. Grâce à notre tunnel VPN sécurisé inclus, contrôlez, redémarrez et configurez vos équipements MikroTik où que vous soyez dans le monde.</p>
                </div>
            </div>

            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Mode Démo</h3>
                        <div class="pricing-price">
                            <span class="amount">0 F CFA</span>
                            <span class="period">/ 5 jours</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> Exploration complète du tableau de bord</li>
                        <li><i class="fa fa-check"></i> Aucun routeur réel connectable (mode simulation)</li>
                        <li><i class="fa fa-check"></i> Idéal pour tester l'interface avant de se lancer</li>
                    </ul>
                    <a class="btn btn-block" href="{Text::url('provision&intent=demo')}"><i class="fa fa-play-circle"></i> Essai Gratuit</a>
                </div>

                <div class="pricing-card pricing-featured">
                    <span class="pricing-badge">Le plus populaire</span>
                    <div class="pricing-header">
                        <h3>Forfait Business</h3>
                        <div class="pricing-price">
                            <span class="amount">{$isp_settings.business_price|default:5000|number_format:0} F CFA</span>
                            <span class="period">/ mois</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> Jusqu'à 3 routeurs inclus</li>
                        <li><i class="fa fa-check"></i> Commission de 10% sur vos ventes Hotspot et PPPoE</li>
                        <li><i class="fa fa-check"></i> Configuration simplifiée (nous gérons les encaissements)</li>
                        <li><i class="fa fa-check"></i> Idéal pour les petites installations de quartier</li>
                    </ul>
                    <a class="btn btn-primary btn-block" href="{Text::url('provision&intent=business')}"><i class="fa fa-rocket"></i> Souscrire</a>
                </div>

                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Forfait Pro</h3>
                        <div class="pricing-price">
                            <span class="amount">{$isp_settings.pro_price_per_router|default:2000|number_format:0} F CFA</span>
                            <span class="period">/ routeur / mois</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> 0% de commission sur toutes vos ventes</li>
                        <li><i class="fa fa-check"></i> Intégration de votre propre API de paiement</li>
                        <li><i class="fa fa-check"></i> Reversement direct et automatique sur votre compte</li>
                        <li><i class="fa fa-check"></i> Conçu pour les FAI locaux et les techniciens à fort volume</li>
                    </ul>
                    <a class="btn btn-block" href="{Text::url('provision&intent=pro')}"><i class="fa fa-bolt"></i> Choisir Pro</a>
                </div>
            </div>

            <div class="admin-card" id="admin-login">
                <h3><i class="fa fa-lock"></i> Accès Admin</h3>
                <p>Entrez votre sous-domaine pour accéder à votre tableau de bord.</p>
                <div class="field">
                    <div class="subdomain-row">
                        <input type="text" id="admin-subdomain" placeholder="monisp" pattern="[a-z0-9\-]+">
                        <span class="subdomain-suffix" id="admin-subdomain-suffix">.dyrsia.com</span>
                    </div>
                </div>
                <a class="btn btn-primary btn-block" href="{Text::url('admin')}" id="admin-access-btn" data-tenant-base="{Text::url('dashboard_tenant=')}"><i class="fa fa-sign-in"></i> Accéder au Dashboard</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {$smarty.now|date_format:"%Y"} {$companyName}. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
    {literal}
    (function () {
        // Admin access
        var adminBtn = document.getElementById('admin-access-btn');
        var adminSub = document.getElementById('admin-subdomain');
        if (adminBtn && adminSub) {
            adminBtn.addEventListener('click', function (e) {
                var s = adminSub.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
                if (s) {
                    e.preventDefault();
                    var base = adminBtn.getAttribute('data-tenant-base') || '';
                    window.location.href = base + encodeURIComponent(s);
                }
            });
        }

    })();
    {/literal}
    </script>
</body>
</html>
