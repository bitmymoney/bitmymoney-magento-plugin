<?php

require_once dirname(__FILE__).'/../lib/bitmm-psp-client.php';

class Bitmm_Bitcoin_PaymentController extends Mage_Core_Controller_Front_Action
{
  protected $bitmmclient;

  public function _construct ()
  {
    /** @TODO put in helper */
    /** @TODO add check if merchant id and api key are set */
   
    parent::_construct();
  }
    /**
     * Redirect user to Bitmm payment page
     */
    public function redirectAction()
    {
      $model = Mage::getModel('bitmmbitcoin/bitcoin');
      $response = $model->getBitcoinCheckoutFormFields();
      if (!$response) {
	Mage::throwException('Error: Connection to bitmymoney failed');  
      }      

      $html = '<html><body>';
      $html.= $this->__('You will be redirected to Bitmymoney.com');
      $html.= '<script type="text/javascript">setTimeout(function() { 
document.location.href = \''.$response['url_pay'].'\';}, 2000);</script>';
      $html.= '</body></html>';
      
      $this->getResponse()->setBody($html);
    }
  
    public function failureAction()
    {
      /* @TODO maybe implement this instead of the checkout/onepage/failure */
    }

    /* callbacks from bitmymoney */
    public function reportAction()
    {
      $bitmmclient =  new Bitmymoney_Payment(Mage::getStoreConfig('payment/bitmm/merchantid'),
					     Mage::getStoreConfig('payment/bitmm/apikey'));
      $post_data = file_get_contents("php://input");

      /* debug */
      Mage::log("postdata=".$post_data);

      /* is www-form-urlencoded post data */
      parse_str($post_data, $data);

      if ($bitmmclient->verifySignature($data,
					array('txid',
					      'order_id',
					      'amount_eur',
					      'status'))) {
	
	$order_id = $data['order_id'];
	$status = $data['status'];
	$txid = $data['txid'];
	$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
	
	switch ($status) {
	case Bitmymoney_Payment::BITMM_SUCCESS:
	  $order->getPayment()->
	    registerCaptureNotification($order->getBaseGrandTotal());
	  $order->getPayment()->setTransactionId($txid);
	  $order->save();
	  break;
	case Bitmymoney_Payment::BITMM_CANCELLED:
	  $order->registerCancellation('Payment cancelled', TRUE)->save();
	  break;
	}

      }
    }
}