<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Error - DYRSIA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{$app_url}/ui/ui/images/favicon.png" />
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="{$app_url}/ui/ui/images/apple-touch-icon.png" />

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">

    <style>
        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }
    </style>

</head>

<body class="hold-transition skin-blue">
    <div class="container">

        <section class="content">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div class="box box-danger box-solid">
                        <section class="content-header">
                            <h1 class="text-center">
                                {$error_title}
                            </h1>
                        </section>
                        <div class="box-body" style="font-size: larger;">
                            <center>
                            <img src="{$app_url}/ui/ui/images/error.png" class="img-responsive hidden-sm hidden-xs"></center>
                            <br>
                            {$error_message}
                            <br>
                            Mikrotik troubleshooting: <br>
                            <ul>
                                <li>{Lang::T('Make sure you use API Port, Default 8728')}</li>
                                <li>{Lang::T('Make sure Username and Password are correct')}</li>
                                <li>{Lang::T('Make sure your hosting not blocking port to external')}</li>
                                <li>{Lang::T('Make sure your Mikrotik accessible from DYRSIA')}</li>
                                <li>IP privée (ex. 10.x) : connectez le PC/serveur DYRSIA au même VPN ou réseau que le routeur</li>
                                <li>Sur le MikroTik : <code>/ip service enable api</code> (port 8728) ou <code>/ip service enable api-ssl</code> (port 8729)</li>
                                <li>Erreur « not a compatible RouterOS service » : mauvais port, API désactivée, ou IP privée (10.x) inaccessible depuis le serveur cloud</li>
                                <li>Testez depuis <strong>Réseau → Routeurs → Test connexion</strong> avant de créer un forfait</li>
                            </ul>
                            {Lang::T('If you just update DYRSIA from upload files, try click Update')} Database
                        </div>
                        <div class="box-footer">
                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                <a href="./update.php?step=4" style="color: black;" class="btn btn-info btn-sm btn-block">{Lang::T('Update')} Database</a>
                                <a href="{$app_url}/update.php" style="color: black;" class="btn btn-success btn-sm btn-block">{Lang::T('Update DYRSIA')}</a>
                            </div>
                            <br>
                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                            <a href="https://github.com/hotspotbilling/phpwifizones/discussions" target="_blank"
                            class="btn btn-success btn-sm btn-block" style="color: black;">{Lang::T('Ask Github Community')}</a>
                                <a href="https://t.me/phpwifizones" target="_blank"
                                    class="btn btn-primary btn-sm btn-block">{Lang::T('Ask Telegram Community')}</a>
                            </div>
                            <br><br>
                            <a href="javascript:history.back()" onclick="history.back()"
                                class="btn btn-warning btn-block">{Lang::T('Back')}</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <img src="{$app_url}/ui/ui/images/error.png" class="img-responsive hidden-md hidden-lg">
                </div>
            </div>
        </section>
    </div>
</body>

</html>
