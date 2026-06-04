{assign var=companyName value=$_c['CompanyName']|default:'DYRSIA'}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$companyName} - ISP Billing</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
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
        .integrations {
            padding: 40px 0; text-align: center;
            border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.02);
        }
        .integrations p {
            color: var(--muted); font-size: 12px; text-transform: uppercase;
            letter-spacing: 0.12em; margin: 0 0 18px;
        }
        .logo-row {
            display: flex; flex-wrap: wrap; justify-content: center;
            gap: 14px; align-items: center;
        }
        .logo-chip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--line); font-weight: 800; font-size: 14px;
        }
        .logo-chip .fa { color: var(--brand); }
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
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
        }
        .feat {
            padding: 24px; border-radius: 20px;
            background: var(--card); border: 1px solid var(--line);
        }
        .feat-icon {
            width: 46px; height: 46px; border-radius: 12px;
            background: rgba(34, 197, 94, 0.14);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .feat-icon .fa { font-size: 20px; color: var(--brand); line-height: 1; }
        .feat h3 { margin: 0 0 8px; font-size: 17px; }
        .feat p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.6; }
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
        .demo-panel {
            padding: 36px 28px; border-radius: 22px; text-align: center;
            background: var(--card); border: 1px solid var(--line);
        }
        .demo-panel .demo-icon {
            width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 14px;
            background: rgba(6, 182, 212, 0.15);
            display: flex; align-items: center; justify-content: center;
        }
        .demo-panel .demo-icon .fa { font-size: 26px; color: var(--brand2); }
        .demo-panel h2 { margin: 0 0 10px; font-size: 24px; }
        .demo-panel p { color: var(--muted); max-width: 520px; margin: 0 auto 18px; font-size: 15px; }
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
            border-radius: 20px; background: var(--card);
            border: 1px solid var(--line); text-align: center;
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
            background: var(--card);
            border: 1px solid var(--line);
            display: flex;
            flex-direction: column;
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
                    <a class="btn btn-ghost" href="#features">Features</a>
                    <a class="btn btn-ghost" href="#pricing">Tarifs</a>
                    <a class="btn btn-ghost" href="{Text::url('faq')}"><i class="fa fa-question-circle"></i> FAQ</a>
                    <a class="btn" href="{Text::url('provision')}"><i class="fa fa-rocket"></i> Essai Gratuit</a>
                    <a class="btn btn-primary" href="{Text::url('admin/')}"><i class="fa fa-lock"></i> Admin</a>
                </div>
            </nav>
        </div>
    </header>

    <main id="top">
        <section class="container hero">
            <span class="pill"><i class="fa fa-shield"></i> Trusted by 150+ ISPs in Africa</span>
            <h1>ISP Billing.<br>Scale Without Limits.</h1>
            <p>Streamline Hotspot, PPPoE and Static IP operations, automate workflows, and grow your internet service business with an all-in-one management solution.</p>
            <div class="hero-cta">
                <a class="btn btn-primary" href="{Text::url('provision')}"><i class="fa fa-play-circle"></i> Start 14 Days FREE Trial</a>
                <a class="btn" href="#demo"><i class="fa fa-video-camera"></i> Watch Demo</a>
            </div>
            <p class="rating"><strong>★★★★★</strong> Rated 4.9/5 by 150+ ISP owners</p>
        </section>

        <section class="integrations">
            <div class="container">
                <p>Natively integrated with</p>
                <div class="logo-row">
                    <span class="logo-chip"><i class="fa fa-server"></i> MikroTik</span>
                    <span class="logo-chip"><i class="fa fa-mobile"></i> M-PESA</span>
                    <span class="logo-chip"><i class="fa fa-credit-card"></i> PayHero</span>
                </div>
            </div>
        </section>

        <section class="section container" id="features">
            <div class="section-head">
                <h2>Enterprise Features, Zero Clutter</h2>
                <p>Everything you need to run a profitable Hotspot or PPPoE network, neatly organized in a blazing-fast interface.</p>
            </div>
            <div class="features-grid">
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-database"></i></div>
                    <h3>Dedicated Architecture</h3>
                    <p>Your own isolated database. Financial and client data never mingled for maximum security and speed.</p>
                </article>
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-mobile"></i></div>
                    <h3>Flawless STK Push</h3>
                    <p>Customers select a package, enter their PIN, and get internet instantly. Automated reconciliation via PayHero.</p>
                </article>
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-wifi"></i></div>
                    <h3>PPPoE and Hotspot Sync</h3>
                    <p>Manage home fiber and street hotspots from one dashboard. Auto-disconnect when subscriptions expire.</p>
                </article>
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-link"></i></div>
                    <h3>Smart IP Bindings</h3>
                    <p>Bypass hotspot auth for Smart TVs, consoles, and admin devices with simple MAC/IP binding.</p>
                </article>
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-bar-chart"></i></div>
                    <h3>Real-Time Analytics</h3>
                    <p>Track daily revenue, top packages, and active users with interactive dashboard charts.</p>
                </article>
                <article class="feat">
                    <div class="feat-icon"><i class="fa fa-magic"></i></div>
                    <h3>Hotspot Wizard</h3>
                    <p>Customize login pages, deploy pools, and push configuration to MikroTik in minutes.</p>
                </article>
            </div>
        </section>

        <section class="section-band" id="how-it-works">
            <div class="container">
                <div class="section-head">
                    <h2>From Setup to Scaling in Minutes</h2>
                    <p>Launch your automated ISP in four simple steps.</p>
                </div>
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <h3>Deploy Instance</h3>
                        <p>Fill out the form below. We provision a dedicated environment for your business.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <h3>Sync MikroTik</h3>
                        <p>Add your router IP and API credentials to establish a secure connection.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <h3>Connect M-Pesa</h3>
                        <p>Enable instant STK Push and payment reconciliation with PayHero.</p>
                    </div>
                    <div class="step">
                        <div class="step-num">4</div>
                        <h3>Automate and Scale</h3>
                        <p>Set your packages and let the platform handle renewals and reporting.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section container" id="demo">
            <div class="demo-panel">
                <div class="demo-icon"><i class="fa fa-play-circle"></i></div>
                <h2>Product Demo</h2>
                <p>Discover how hotspot billing, PPPoE management, and payment automation work together in one panel.</p>
                <a class="btn btn-primary" href="#deploy">Get Started Free</a>
            </div>
        </section>

        <section class="section container" id="pricing">
            <div class="section-head">
                <h2>Tarification Simple et Transparente</h2>
                <p>Choisissez l'offre adaptée à votre activité. Aucun frais caché.</p>
            </div>
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Mode Démo</h3>
                        <div class="pricing-price">
                            <span class="amount">0 F CFA</span>
                            <span class="period">/ 14 jours</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> Exploration complète du tableau de bord</li>
                        <li><i class="fa fa-check"></i> Aucun routeur réel connectable (mode simulation)</li>
                        <li><i class="fa fa-check"></i> Idéal pour tester l'interface avant de se lancer</li>
                    </ul>
                    <a class="btn btn-block" href="{Text::url('provision')}"><i class="fa fa-play-circle"></i> Essai Gratuit</a>
                </div>

                <div class="pricing-card pricing-featured">
                    <span class="pricing-badge">Le plus populaire</span>
                    <div class="pricing-header">
                        <h3>Mode Business</h3>
                        <div class="pricing-price">
                            <span class="amount">2 500 F CFA</span>
                            <span class="period">/ mois</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> Jusqu'à 3 routeurs inclus</li>
                        <li><i class="fa fa-check"></i> Commission de 10% sur vos ventes Hotspot et PPPoE</li>
                        <li><i class="fa fa-check"></i> Configuration simplifiée (nous gérons les encaissements)</li>
                        <li><i class="fa fa-check"></i> Idéal pour les petites installations de quartier</li>
                    </ul>
                    <a class="btn btn-primary btn-block" href="{Text::url('order/package')}"><i class="fa fa-rocket"></i> Souscrire</a>
                </div>

                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Mode Pro</h3>
                        <div class="pricing-price">
                            <span class="amount">10 000 F CFA</span>
                            <span class="period">/ mois</span>
                        </div>
                        <p class="pricing-note">Puis 8 000 F CFA / mois pour le 2ème routeur</p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa fa-check"></i> 0% de commission sur toutes vos ventes</li>
                        <li><i class="fa fa-check"></i> Intégration de votre propre API de paiement (CamPay, MyCoolPay, etc.)</li>
                        <li><i class="fa fa-check"></i> Reversement direct et automatique sur votre compte</li>
                        <li><i class="fa fa-check"></i> Conçu pour les FAI locaux et les techniciens à fort volume</li>
                    </ul>
                    <a class="btn btn-block" href="{Text::url('order/package')}"><i class="fa fa-bolt"></i> Choisir Pro</a>
                </div>
            </div>

            <div class="admin-card" id="admin-login">
                <h3><i class="fa fa-lock"></i> Accès Admin</h3>
                <p>Entrez votre sous-domaine pour accéder à votre tableau de bord.</p>
                <div class="field">
                    <div class="subdomain-row">
                        <input type="text" id="admin-subdomain" placeholder="monisp" pattern="[a-z0-9-]+">
                        <span class="subdomain-suffix" id="admin-subdomain-suffix">.dyrsia.com</span>
                    </div>
                </div>
                <a class="btn btn-primary btn-block" href="{Text::url('admin')}" id="admin-access-btn" data-tenant-base="{Text::url('dashboard_tenant=')}"><i class="fa fa-sign-in"></i> Accéder au Dashboard</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {$smarty.now|date_format:"%Y"} {$companyName}. All rights reserved.</p>
        </div>
    </footer>

    <script>
    {literal}
    (function () {
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
