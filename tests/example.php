<?php
$apikey = 'test';
$data = 'message';

/* http://www.php.net/hash_hmac */
$sign = hash_hmac('sha256', $data, $apikey);
printf("%d\n",$sign == '61d947ddeaabcbbfc1681b542fa62fcc96350bd2866eeae0fd6d0693b37d4cb7');

$nonce = 928374651;
$sign = hash_hmac('sha256', $data . $nonce, $apikey);
printf("%d\n",$sign == '69bc013d59f5faef10cec9752b95c262f4a4d7fa73428b3ff21850d26c1c0de3');

/* http://www.php.net/http_build_query */
$data = array( 'amount_eur' => '118.70',
	       'decimals'   => 5);
$query = http_build_query($data);

$url = 'https://bitmymoney.com/secure/pay/price_btc?'.$query;

/* http://www.php.net/curl_init */
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $url
));
if( ! $result = curl_exec($ch)) { 
  trigger_error(curl_error($ch)); 
} 
else {
  printf("%s\n",$result);
}
curl_close($ch);
