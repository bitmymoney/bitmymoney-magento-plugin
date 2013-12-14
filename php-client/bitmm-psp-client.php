<?php


class Bitmymoney_Payment
{
  protected $base_url;
  protected $api_key;

  public function __construct($api_key) {
    $this->api_key = $api_key;
    $this->base_url = 'https://bitmymoney.com/secure/pay/';
    // doesn't work
    //$this->base_url='http://test.bitmymoney.com/';
  }

  public function startPayment($amount_eur, 
			       $description,
			       $url_success, 
			       $callback_success,
			       $merchant_id, 

			       $order_id=NULL, 
			       $url_failure=NULL,
			       $callback_failure=NULL, 
			       $nonce=NULL) {
    $amount_eur = $this->normalizeAmount($amount_eur);
    $sign_fields = array('amount_eur', 'description', 'url_success', 'merchant_id');
    $data = array('amount_eur' => $amount_eur, 
		  'description' => $description,
		  'url_success' => $url_success, 
		  'callback_success' => $callback_success,
		  'merchant_id' => $merchant_id, 
		  'order_id' => $order_id,
		  'url_failure' => $url_failure, 
		  'callback_failure' => $callback_failure,
		  'nonce' => $nonce);
 
    $response = $this->sendRequest('/start/',$data, $sign_fields);    
    $fields =  array('url_pay', 'btc_address', 'url_qrcode', 'url_status');
    if ($this->verifySignature($response, $fields)) {
      return $response;
    }
    else {
      return 0;
    }
  }

  public function price_btc($amount_eur, $decimals=5) {
    $amount_eur = $this->normalizeAmount($amount_eur);
    $data = array('amount_eur' => $amount_eur,
		  'decimals'   => $decimals);
    return $this->sendRequest('/price_btc/',$data=$data);
  }

  public function sendRequest($path, $data, $sign_fields=NULL) {
    if ($sign_fields) {
      $sign_values = array();
      foreach($sign_fields as $field) {
	$sign_values[] = $data[$field];
      }
      if (array_key_exists('nonce',$data)) {
	$sign_values[] = $data['nonce'];
	unset($data['nonce']);
      }
      $sign_data = implode($sign_values);
      $sign = hash_hmac('sha256', $sign_data, $this->api_key);
      $data['sign'] = $sign;
    }
    $query = http_build_query($data);
    $url = $this->base_url . $path . '?' . $query;
    echo $url."\n";
    $ch = curl_init();
    /* @TODO add more checks for certificate etc */
    curl_setopt_array($ch, array(
				 CURLOPT_RETURNTRANSFER => 1,
				 CURLOPT_URL => $url
				 ));
    if( ! $result = curl_exec($ch)) { 
      trigger_error(curl_error($ch)); 
    }
    print_r($result);
    return json_decode($result,true);
  }
   

  private function verifySignature($data, $fields) {
    $signvalues = array();
    foreach ($fields as $field) {
      $signvalues[] = $data[$field];
    }
    if (array_key_exists('nonce',$data)) {
	$signvalues[] = $data['nonce'];
    }
    $sign_data = implode($signvalues);
    $sign = hash_hmac('sha256', $sign_data, $this->api_key);
    
    if ($sign !== $data['sign']) {
      trigger_error('sign error');
    }
    return 1;
  }

  private function normalizeAmount($amount) {
    /* @TODO add checks */
    return $amount;
  }
  
}