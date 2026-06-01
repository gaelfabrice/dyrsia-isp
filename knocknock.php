<?php
require('routeros_api.class.php');

$API = new RouterosAPI();
$hostname = '62.12.118.94';
$username = 'knocknock';
$password = '2wsx3edc';

if ($API->connect($hostname, $username, $password)) {
    $data = $API->comm('/system/identity/print');
    print_r($data);
    $API->disconnect();
} else {
    echo "Unable to connect to API.";
}
?>
