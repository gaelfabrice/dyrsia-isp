<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{$text|escape} - {$_c['CompanyName']|escape}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">
    <meta http-equiv="refresh" content="{$time}; url={$url|escape:'url'}">
    <style>
        {literal}
        :root {
            --bg: #030712;
            --card: rgba(15, 23, 42, 0.82);
            --line: rgba(148, 163, 184, 0.18);
            --text: #f8fafc;
            --muted: #94a3b8;
            --brand: #22c55e;
            --brand2: #06b6d4;
        }
        * { box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Inter, system-ui, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }
        .wz-alert-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
        }
        .wz-alert-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 55% at 50% -10%, rgba(34, 197, 94, 0.12), transparent 55%),
                radial-gradient(circle at 85% 15%, rgba(6, 182, 212, 0.1), transparent 35%),
                radial-gradient(circle at 10% 90%, rgba(34, 197, 94, 0.06), transparent 40%);
            pointer-events: none;
        }
        .wz-alert-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 36px 32px 28px;
            text-align: center;
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(12px);
        }
        .wz-alert-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 22px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 30px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.04);
        }
        .wz-alert-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 5px 10px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        .wz-alert-title {
            margin: 0 0 10px;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.4px;
            color: #fff;
            line-height: 1.3;
        }
        .wz-alert-sub {
            margin: 0 0 24px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }
        .wz-alert-progress {
            height: 4px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.15);
            overflow: hidden;
            margin-bottom: 22px;
        }
        .wz-alert-progress-bar {
            height: 100%;
            width: 100%;
            border-radius: inherit;
            transform-origin: left center;
            transition: width 1s linear;
        }
        .wz-alert-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 46px;
            border: 0;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            color: #021014;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            box-shadow: 0 10px 28px rgba(34, 197, 94, 0.22);
        }
        .wz-alert-btn:hover,
        .wz-alert-btn:focus {
            filter: brightness(1.06);
            color: #021014;
            text-decoration: none;
        }
        .wz-alert-btn .countdown {
            opacity: 0.85;
            font-weight: 800;
        }
        .wz-alert-footer {
            position: relative;
            z-index: 1;
            margin-top: 28px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }

        /* Variantes */
        .wz-alert-success {
            --brand: #22c55e;
            --brand2: #06b6d4;
        }
        .wz-alert-success .wz-alert-icon {
            color: #4ade80;
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.28);
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.06);
        }
        .wz-alert-success .wz-alert-badge {
            color: #86efac;
            background: rgba(34, 197, 94, 0.12);
        }
        .wz-alert-success .wz-alert-progress-bar {
            background: linear-gradient(90deg, #22c55e, #06b6d4);
        }

        .wz-alert-info {
            --brand: #38bdf8;
            --brand2: #6366f1;
        }
        .wz-alert-info .wz-alert-icon {
            color: #7dd3fc;
            background: rgba(56, 189, 248, 0.12);
            border-color: rgba(56, 189, 248, 0.28);
            box-shadow: 0 0 0 8px rgba(56, 189, 248, 0.06);
        }
        .wz-alert-info .wz-alert-badge {
            color: #bae6fd;
            background: rgba(56, 189, 248, 0.12);
        }
        .wz-alert-info .wz-alert-progress-bar {
            background: linear-gradient(90deg, #38bdf8, #6366f1);
        }
        .wz-alert-info .wz-alert-btn {
            color: #fff;
            box-shadow: 0 10px 28px rgba(56, 189, 248, 0.22);
        }

        .wz-alert-danger {
            --brand: #f87171;
            --brand2: #fb923c;
        }
        .wz-alert-danger .wz-alert-icon {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.28);
            box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.06);
        }
        .wz-alert-danger .wz-alert-badge {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.12);
        }
        .wz-alert-danger .wz-alert-progress-bar {
            background: linear-gradient(90deg, #ef4444, #f97316);
        }
        .wz-alert-danger .wz-alert-btn {
            color: #fff;
            box-shadow: 0 10px 28px rgba(239, 68, 68, 0.22);
        }

        .wz-alert-warning {
            --brand: #fbbf24;
            --brand2: #f97316;
        }
        .wz-alert-warning .wz-alert-icon {
            color: #fcd34d;
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.28);
            box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.06);
        }
        .wz-alert-warning .wz-alert-badge {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.12);
        }
        .wz-alert-warning .wz-alert-progress-bar {
            background: linear-gradient(90deg, #f59e0b, #f97316);
        }
        .wz-alert-warning .wz-alert-btn {
            color: #1c1917;
            box-shadow: 0 10px 28px rgba(245, 158, 11, 0.22);
        }

        @media (max-width: 480px) {
            .wz-alert-card {
                padding: 28px 22px 22px;
                border-radius: 20px;
            }
            .wz-alert-title {
                font-size: 20px;
            }
        }
        {/literal}
    </style>
</head>
<body class="wz-alert-page wz-alert-{$type|escape}">
    <main class="wz-alert-card">
        <div class="wz-alert-icon" id="alertIcon" aria-hidden="true">
            {if $type == 'success'}
                <i class="fa fa-check"></i>
            {elseif $type == 'info'}
                <i class="fa fa-sign-out"></i>
            {elseif $type == 'danger'}
                <i class="fa fa-times"></i>
            {elseif $type == 'warning'}
                <i class="fa fa-exclamation-triangle"></i>
            {else}
                <i class="fa fa-info-circle"></i>
            {/if}
        </div>
        <span class="wz-alert-badge">
            {if $type == 'success'}{Lang::T('Success')}
            {elseif $type == 'info'}{Lang::T('Information')}
            {elseif $type == 'danger'}{Lang::T('Error')}
            {elseif $type == 'warning'}{Lang::T('Warning')}
            {else}{ucwords($type|escape)}{/if}
        </span>
        <h1 class="wz-alert-title">{$text|escape}</h1>
        <p class="wz-alert-sub">{Lang::T('Redirecting automatically in')} <strong id="countText">{$time}</strong> {Lang::T('seconds')}.</p>
        <div class="wz-alert-progress" role="progressbar" aria-valuemin="0" aria-valuemax="{$time}" aria-valuenow="{$time}">
            <div class="wz-alert-progress-bar" id="progressBar"></div>
        </div>
        <a href="{$url|escape:'url'}" id="button" class="wz-alert-btn">
            {Lang::T('Continue now')}
            <span class="countdown">(<span id="countBtn">{$time}</span>)</span>
        </a>
    </main>
    <footer class="wz-alert-footer">{$_c['CompanyName']|escape}</footer>

    <script>
        {literal}
        (function () {
            var total = {/literal}{$time}{literal};
            var remaining = total;
            var progressBar = document.getElementById('progressBar');
            var countText = document.getElementById('countText');
            var countBtn = document.getElementById('countBtn');
            var progressWrap = progressBar ? progressBar.parentElement : null;

            function tick() {
                remaining--;
                if (countBtn) countBtn.textContent = Math.max(remaining, 0);
                if (countText) countText.textContent = Math.max(remaining, 0);
                if (progressBar) {
                    var pct = total > 0 ? (Math.max(remaining, 0) / total) * 100 : 0;
                    progressBar.style.width = pct + '%';
                }
                if (progressWrap) {
                    progressWrap.setAttribute('aria-valuenow', Math.max(remaining, 0));
                }
                if (remaining > 0) {
                    setTimeout(tick, 1000);
                }
            }

            if (progressBar) {
                progressBar.style.width = '100%';
            }
            setTimeout(tick, 1000);
        })();
        {/literal}
    </script>
</body>
</html>
