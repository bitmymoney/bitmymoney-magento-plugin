<?php
require_once '../php-client/bitmm-psp-client.php';

/* see https://bitmymoney.com/secure/pay/merchant/ */
$api_key = 'test';
$merchant_id = 666;

$client = new Bitmymoney_Payment($api_key);

/* test get btc price */
echo $client->priceBtc(100.20,5);
$transaction =  $client->startPayment(101,
					  'Iets',
					  'http://127.0.0.1:8000',
					  'http://127.0.0.1:8000',
					  $merchant_id);

print_r($transaction);

preg_match("/\/tx\/([A-Za-z0-9]+)\/$/", $transaction['url_pay'], $matches);
$txid=$matches[1];
print_r($client->transactionStatus($txid));


$order_id = "124";
$url_failure = 'http://127.0.0.1:8000';
$callback_failure = 'http://127.0.0.1:8000';

/* @todo get a real random number */

$nonce =  193283;
$transaction =  $client->startPayment(101,
					  'Software',
					  'http://127.0.0.1:8000',
					  'http://127.0.0.1:8000',
					  $merchant_id,

					  $order_id,
					  $url_failure,
					  $callback_failure,
					  $nonce);

print_r($transaction);
if (preg_match("/\/tx\/([A-Za-z0-9]+)\/$/", $transaction['url_pay'], $matches)) {
    $txid=$matches[1];
    print_r($client->transactionStatus($txid, $nonce));
}
