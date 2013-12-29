<?php

require_once dirname(__FILE__).'/../lib/bitmm-psp-client.php';

class Bitmm_Bitcoin_Model_Bitcoin extends Mage_Payment_Model_Method_Abstract
{
    protected $_code                   = 'bitmm';
    protected $_isInitializeNeeded     = TRUE;
    protected $_canUseCheckout         = TRUE;
    protected $_canUseInternal         = FALSE;
    protected $_canUseForMultishipping = FALSE;

    protected $bitmmclient = NULL;

    /**
     * Config instance
     * @var Mage_Bitcoin_Model_Config
     */
    protected $_config = NULL;
    protected $_order;

    /**
     * Runs Bitcoin module
     */
    public function _construct ()
    {
      $this->bitmmclient =  new Bitmymoney_Payment(Mage::getStoreConfig('payment/bitmm/merchantid'),
						   Mage::getStoreConfig('payment/bitmm/apikey'));
	
      parent::_construct();
      $this->_init('bitmmbitcoin/bitcoin');

    }

    /**
     * Whether method is available for specified currency
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return TRUE;
    }

    /**
     * Return Order place redirect url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bitmmbitcoin/payment/redirect', array('_secure' => TRUE));
    }

    public function getBitcoinCheckoutFormFields()
    {
      $checkout = Mage::getSingleton('checkout/session');
      $last_order_id = $checkout->getLastRealOrderId();
      $order = Mage::getModel('sales/order')->loadByIncrementId($last_order_id);
      $order_id = $order->getIncrementId();

      /* payment information */
      $description = 'Test';
      $url_success = Mage::getUrl('checkout/onepage/success');
      /** @TODO use callback success */
      $callback_success = NULL;
      $amount_eur = $order->getBaseGrandTotal();
      $url_failure = Mage::getUrl('bitmmbitcoin/payment/failure?order_id=' . $order_id);
      /** @TODO use callback failure */
      $callback_failure = NULL;
      //$url_failure = Mage::getUrl('bitmmbitcoin/payment/report?status=failure&order_id=' . $order_id);
      
      /** @TODO use nonce */
      $nonce = NULL;
      $this->bitmmclient =  new Bitmymoney_Payment(Mage::getStoreConfig('payment/bitmm/merchantid'),
      						   Mage::getStoreConfig('payment/bitmm/apikey'));
      $response = $this->bitmmclient->startPayment($amount_eur,
						   $description,
						   $url_success,
						   $callback_success, 
						   $order_id,
						   $url_failure,
						   $callback_failure,
						   $nonce
						   );
      return $response;
    }

    /**
     * Instantiate state and set it to state object
     * @param $paymentAction
     * @param object $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(FALSE);
    }

    /**
     * Check whether payment method can be used
     * @param $quote
     * @return boolean
     */
    public function isAvailable($quote = NULL)
    {
        return Mage::getStoreConfig('payment/bitmm/active');
    }

    /**
     * Get order model
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }
}
