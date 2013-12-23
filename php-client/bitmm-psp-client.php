<?php


class Bitmymoney_Payment {
    protected $base_url;
    protected $api_key;

    public function __construct(
            $api_key, $base_url='https://bitmymoney.com/secure/pay') {
        $this->api_key = $api_key;
        if (substr($base_url, strlen($base_url) - 1) == '/') {
            $base_url = substr($base_url, 0, strlen($base_url) - 1);
        }
        $this->base_url = $base_url;
    }

    public function startPayment(
            $amount_eur,
            $description,
            $url_success,
            $callback_success,
            $merchant_id,
            $order_id=NULL,
            $url_failure=NULL,
            $callback_failure=NULL,
            $nonce=NULL) {
        $amount_eur = $this->normalizeAmount($amount_eur);
        $sign_fields = array(
            'amount_eur', 'description', 'url_success', 'merchant_id','nonce');
        $data = array('amount_eur' => $amount_eur,
            'description' => $description,
            'url_success' => $url_success,
            'callback_success' => $callback_success,
            'merchant_id' => $merchant_id,
            'order_id' => $order_id,
            'url_failure' => $url_failure,
            'callback_failure' => $callback_failure,
            'nonce' => $nonce);

        $path = '/start/';
        $response = $this->sendRequest($path, $data, $sign_fields);
        if (!is_null($nonce)) {
            $response['nonce'] = $nonce;
        }
        $fields =  array('url_pay', 'btc_address', 'url_qrcode', 'url_status');
        if ($this->verifySignature($response, $fields)) {
            return $response;
        } else {
            return 0;
        }
    }

    public function transactionStatus($txid, $nonce=NULL) {
        $path = sprintf("/tx/%s/status/",$txid);
        $data =  array('nonce' => $nonce);
        $response = $this->sendRequest($path, $data);
        $signdata = $response;
        $signdata['nonce']= $nonce;
        $this->verifySignature(
            $signdata,
            array('status', 'amount_btc', 'amount_received', 'txid'));
        /* user can convert to float or use bcmath */
        return array(
            'status' => $response['status'],
            'amount_btc' => $response['amount_btc'],
            'amount_received' => $response['amount_received'],
            'txid' => $response['txid'],
            'sign' => $response['sign']);
    }

    public function priceBTC($amount_eur, $decimals=5) {
        $amount_eur = $this->normalizeAmount($amount_eur);
        $data = array(
            'amount_eur' => $amount_eur,
            'decimals'   => $decimals);
        return $this->sendRequest('/price_btc/', $data=$data);
    }

    private function sendRequest($path, $data=NULL, $sign_fields=NULL) {
        if (!is_null($sign_fields)) {
            $sign_values = array();
            foreach($sign_fields as $field) {
                $sign_values[] = $data[$field];
            }
            if (array_key_exists('nonce', $sign_fields)) {
                $sign_values[] = $data['nonce'];
                unset($data['nonce']);
            }
            $sign_data = implode($sign_values);
            $sign = hash_hmac('sha256', $sign_data, $this->api_key);
            $data['sign'] = $sign;
            $query = http_build_query($data);
        } else {
            if (is_null($data)) {
                $query = "";
            }
            else {
                $query = http_build_query($data);
            }
        }
        $url = $this->base_url . $path;
        if ($query) {
            $url .= '?' . $query;
        }
        $ch = curl_init();
        /* @TODO add more checks for certificate etc */
        curl_setopt_array(
            $ch,
            array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url));
        if(! $result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        return json_decode($result,true);
    }


    private function verifySignature($data, $fields) {
        $signvalues = array();
        foreach ($fields as $field) {
            $signvalues[] = $data[$field];
        }
        if (array_key_exists('nonce',$data)) {
            $nonce = $data['nonce'];
            if (!is_null($nonce)) {
                $signvalues[] = $data['nonce'];
            }
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
