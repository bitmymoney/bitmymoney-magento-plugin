<?php
require_once '../php-client/bitmm-psp-client.php';

$api_key = 'test';
$merchant_id = 666;

$client = new Bitmymoney_Payment($api_key);
//echo $client->price_btc(100.20,5);
echo $client->startPayment(101,'Iets','http://127.0.0.1:8000',
			   'http://127.0.0.1:8000',
			   $merchant_id);





