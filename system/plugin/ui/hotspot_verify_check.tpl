<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {Lang::T('Payment Page')}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/plugins/pace.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>
    <style>
        .thank-you-container {
            text-align: center;
            background-color: #f1f1f1;
            padding: 40px;
            border-radius: 10px;
        }

        .thank-you-heading {
            font-size: 32px;
            color: #333;
            animation: thankYouAnimation 2s ease-in-out infinite;
        }

        .thank-you-message {
            font-size: 18px;
            color: #555;
            animation: fadeInAnimation 2s ease-in-out;
        }

        .countdown-timer {
            font-size: 20px;
            color: #333;
        }


        @keyframes thankYouAnimation {
            0% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(180deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeInAnimation {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href=""><b> {$companyName}</b></a>
        </div>
        {if $msg}
        {foreach $msg as $message}
        <div class="callout callout-info" style="margin-bottom: 0!important;">
            {$message}
        </div><br>
        {/foreach}
        {/if}

        <div class="thank-you-container">
            <h1 class="thank-you-heading">Thank You!</h1>
            <p class="thank-you-message">We appreciate your patronage.</p>
        </div>
        <br>
        <div class="login-box-body">
            <p class="login-box-msg">{Lang::T('Package Details')}</p>
            <div class="box box-{if $package['status']=='on'}success{else}danger{/if}">
                <div class="box-body box-profile">
                    <h4 class="text-center">{$package['type']} - {$package['namebp']}</h4>
                    {if $txid && $reference}
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            {Lang::T('Created On')} <span
                                class="pull-right">{Lang::dateAndTimeFormat($package['recharged_on'],$package['recharged_time'])}</span>
                        </li>
                        <li class="list-group-item">
                            {Lang::T('Expires On')} <span
                                class="pull-right">{Lang::dateAndTimeFormat($package['expiration'],
                                $package['time'])}</span>
                        </li>
                        <li class="list-group-item">
                            {Lang::T('Payment Method')} <span class="pull-right">{$package['method']}</span>
                        </li>
                        <li class="list-group-item">
                            {Lang::T('Amount Paid')}<span class="pull-right">{$amountPaid}</span>
                        </li>
                        <li class="list-group-item">
                            {Lang::T('Transaction Ref')}<span class="pull-right">{$reference}</span>
                        </li>
                        <li class="list-group-item">
                            {Lang::T('Transaction ID')}<span class="pull-right">{$txid}</span>
                        </li>
                    </ul>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <script src="ui/ui/scripts/jquery.min.js"></script>
    <script src="ui/ui/scripts/bootstrap.min.js"></script>
    <script src="ui/ui/scripts/adminlte.min.js"></script>
    <script src="ui/ui/scripts/plugins/select2.min.js"></script>
    <script src="ui/ui/scripts/pace.min.js"></script>
    <script src="ui/ui/scripts/custom.js"></script>
</body>

</html>