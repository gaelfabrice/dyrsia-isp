{assign var=companyName value=$_c['CompanyName']|default:'DYRSIA'}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FAQ - {$companyName}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
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
        .container { width: min(900px, 100%); margin: 0 auto; padding: 0 20px; }
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

        .hero { padding: 56px 0 40px; text-align: center; }
        .hero h1 {
            font-size: clamp(28px, 5vw, 42px); line-height: 1.15;
            letter-spacing: -1px; margin: 0 0 16px; font-weight: 900; color: #fff;
        }
        .hero p {
            max-width: 600px; margin: 0 auto; color: var(--muted);
            font-size: 16px; line-height: 1.7;
        }

        .faq-section { padding: 40px 0 60px; }
        .faq-category {
            margin-bottom: 40px;
        }
        .faq-category-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }
        .faq-category-title .emoji {
            font-size: 24px;
        }

        .accordion {
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            background: var(--card);
            margin-bottom: 12px;
        }
        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            cursor: pointer;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            color: var(--text);
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            transition: background 0.2s;
        }
        .accordion-header:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .accordion-header .question {
            flex: 1;
            padding-right: 16px;
        }
        .accordion-header .icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(34, 197, 94, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.3s, background 0.3s;
        }
        .accordion-header .icon .fa {
            font-size: 12px;
            color: var(--brand);
            transition: transform 0.3s;
        }
        .accordion.open .accordion-header .icon {
            background: var(--brand);
        }
        .accordion.open .accordion-header .icon .fa {
            color: #021014;
            transform: rotate(180deg);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
        }
        .accordion.open .accordion-content {
            max-height: 500px;
        }
        .accordion-body {
            padding: 0 20px 20px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.75;
        }
        .accordion-body strong {
            color: var(--text);
        }
        .accordion-body ol {
            margin: 12px 0;
            padding-left: 20px;
        }
        .accordion-body ol li {
            margin-bottom: 8px;
        }
        .accordion-body code {
            background: rgba(34, 197, 94, 0.1);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
            color: var(--brand);
        }

        .cta-box {
            margin-top: 48px;
            padding: 32px;
            border-radius: 20px;
            background: var(--card);
            border: 1px solid var(--line);
            text-align: center;
        }
        .cta-box h3 {
            margin: 0 0 10px;
            font-size: 22px;
        }
        .cta-box p {
            color: var(--muted);
            margin: 0 0 20px;
            font-size: 15px;
        }

        .footer {
            padding: 32px 0; text-align: center; color: var(--muted);
            font-size: 13px; border-top: 1px solid var(--line);
        }

        @media (max-width: 767px) {
            .nav { flex-wrap: wrap; justify-content: center; }
            .brand { width: 100%; justify-content: center; }
            .nav-links { width: 100%; justify-content: center; flex-wrap: wrap; }
            .accordion-header { padding: 16px; font-size: 14px; }
            .accordion-body { padding: 0 16px 16px; }
        }
        {/literal}
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="nav">
                <a class="brand" href="{Text::url('welcome')}">
                    <span class="brand-icon"><i class="fa fa-bolt"></i></span>
                    <span>{$companyName}</span>
                </a>
                <div class="nav-links">
                    <a class="btn btn-ghost" href="{Text::url('welcome')}#features">Features</a>
                    <a class="btn btn-ghost" href="{Text::url('welcome')}#pricing">Tarifs</a>
                    <a class="btn" href="{Text::url('faq')}"><i class="fa fa-question-circle"></i> FAQ</a>
                    <a class="btn btn-primary" href="{Text::url('admin/')}"><i class="fa fa-lock"></i> Admin</a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="container hero">
            <h1>Questions Fréquentes (FAQ)</h1>
            <p>Tout ce que vous devez savoir pour lancer et rentabiliser votre Wi-Fi Zone au Cameroun.</p>
        </section>

        <section class="container faq-section">

            <!-- Configuration & Matériel -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <span class="emoji">🛠️</span> Configuration & Matériel
                </h2>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">De quel matériel ai-je besoin pour utiliser Dyrsia ISP ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            Vous avez simplement besoin d'une <strong>connexion Internet</strong> (Camtel, Orange, MTN, etc.) et d'un <strong>routeur de marque MikroTik</strong> (par exemple, le modèle <code>hEX lite</code>, <code>hAP ac2</code> ou un <code>Cloud Core Router</code> selon la taille de votre réseau).
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Comment installer le script Dyrsia sur mon MikroTik ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            C'est <strong>ultra-simple</strong> et cela prend moins de 5 minutes :
                            <ol>
                                <li>Connectez-vous à votre routeur via le logiciel <strong>Winbox</strong>.</li>
                                <li>Ouvrez un <strong>New Terminal</strong>.</li>
                                <li>Copiez-collez le <strong>script unique</strong> généré dans votre tableau de bord Dyrsia ISP, puis appuyez sur <strong>Entrée</strong>.</li>
                            </ol>
                            Votre routeur est instantanément connecté et configuré !
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paiements & Mobile Money -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <span class="emoji">💰</span> Paiements & Mobile Money
                </h2>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Comment mes clients achètent-ils leurs tickets Wi-Fi ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            Lorsqu'un client se connecte à votre réseau Wi-Fi, une <strong>page d'accueil s'affiche automatiquement</strong> sur son téléphone. Il choisit son forfait, saisit son numéro <strong>MTN Mobile Money</strong> ou <strong>Orange Money</strong>, valide le paiement avec son code secret, et son <strong>code d'accès Internet s'affiche immédiatement</strong> à l'écran.
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Comment fonctionne le reversement en Mode Business (2 500 F / mois) ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            En <strong>Mode Business</strong>, Dyrsia ISP gère les encaissements pour vous. Nous collectons les paiements de vos clients et nous vous <strong>reversons la totalité de vos gains</strong> (déduction faite des 10% de commission) directement sur votre compte <strong>MTN MoMo</strong> ou <strong>Orange Money</strong> chaque semaine ou chaque mois selon votre choix.
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Comment fonctionne l'API de paiement en Mode Pro (10 000 F / mois) ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            C'est le <strong>mode préféré des professionnels</strong>. Vous intégrez votre propre clé API (via un agrégateur local comme <strong>CamPay</strong>, <strong>MyCoolPay</strong>, <strong>Monetbil</strong>, etc.). Ainsi, <strong>0% de commission</strong> n'est prélevé par Dyrsia : l'argent de vos clients est directement versé sur votre propre compte Mobile Money <strong>sans aucun intermédiaire</strong>.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fonctionnalités & Gestion -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <span class="emoji">📈</span> Fonctionnalités & Gestion
                </h2>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Puis-je gérer des abonnements mensuels par câble ou antenne (PPPoE) ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <strong>Oui !</strong> Notre plateforme gère parfaitement le <strong>Hotspot</strong> (connexion par ticket Wi-Fi) et le <strong>PPPoE</strong> (abonnements résidentiels sécurisés). Vous pouvez attribuer une bande passante fixe (ex: 5 Mbps) à un voisin et <strong>suspendre automatiquement</strong> son accès si son abonnement mensuel expire.
                        </div>
                    </div>
                </div>

                <div class="accordion">
                    <button class="accordion-header">
                        <span class="question">Que se passe-t-il après les 5 jours d'essai du Mode Démo ?</span>
                        <span class="icon"><i class="fa fa-chevron-down"></i></span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            Le Mode Démo vous permet d'<strong>explorer gratuitement</strong> l'ensemble du tableau de bord sans connecter de routeur réel. Après 5 jours, pour commencer à vendre des tickets et connecter vos équipements au Cameroun, il vous suffit de <strong>choisir entre la formule Business ou Pro</strong>.
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Box -->
            <div class="cta-box">
                <h3>Une autre question ?</h3>
                <p>Contactez-nous sur WhatsApp ou lancez votre essai gratuit de 5 jours.</p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a class="btn" href="https://wa.me/237690000000" target="_blank"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                    <a class="btn btn-primary" href="{Text::url('provision')}"><i class="fa fa-rocket"></i> Essai Gratuit</a>
                </div>
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
    (function() {
        var accordions = document.querySelectorAll('.accordion');
        accordions.forEach(function(accordion) {
            var header = accordion.querySelector('.accordion-header');
            header.addEventListener('click', function() {
                var isOpen = accordion.classList.contains('open');
                // Fermer tous les autres dans la même catégorie
                var category = accordion.closest('.faq-category');
                category.querySelectorAll('.accordion.open').forEach(function(openAcc) {
                    if (openAcc !== accordion) {
                        openAcc.classList.remove('open');
                    }
                });
                // Toggle l'état
                accordion.classList.toggle('open', !isOpen);
            });
        });
    })();
    {/literal}
    </script>
</body>
</html>
