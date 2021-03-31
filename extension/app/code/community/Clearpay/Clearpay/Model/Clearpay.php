<?php

/**
 * Class Clearpay_Clearpay_Model_Clearpay
 */
class Clearpay_Clearpay_Model_Clearpay extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment Method features common for all payment methods
     *
     * @var bool
     */
    protected $_isGateway                  = false;
    protected $_canOrder                   = true;
    protected $_canAuthorize               = false;
    protected $_canCapture                 = false;
    protected $_canCapturePartial          = false;
    protected $_canCaptureOnce             = false;
    protected $_canRefund                  = true;
    protected $_canRefundInvoicePartial    = true;
    protected $_canVoid                    = false;
    protected $_canUseInternal             = false;
    protected $_canUseCheckout             = true;
    protected $_canUseForMultishipping     = false;
    protected $_isInitializeNeeded         = true;
    protected $_canFetchTransactionInfo    = false;
    protected $_canReviewPayment           = true;
    protected $_canCreateBillingAgreement  = false;
    protected $_canManageRecurringProfiles = false;

    /**
     * @var string
     */
    protected $_code  = 'clearpay';

    /**
     * @var string
     */
    protected $_formBlockType = 'clearpay/checkout_clearpay';

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $paymentDetailArray = Mage::app()->getRequest()->getPost('paymentdetail');
        $paymentDetail = $paymentDetailArray[0];
        $this->getCheckout()->setPaymentMethodDetail($paymentDetail);
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function assignData($data)
    {
        $this->getInfoInstance();

        return $this;
    }

    /**
     * @return $this
     */
    public function validate()
    {
        parent::validate();

        $this->getInfoInstance();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('clearpay/payment', array('_secure' => false));
    }

    /**
     * @param Mage_Sales_Model_Quote $quote = null
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($this->getConfigData('enabled') == 'no') {
            return false;
        }

        $config = Mage::getStoreConfig('payment/clearpay');
        $minAmount = $config['clearpay_min_amount'];
        $maxAmount = $config['clearpay_max_amount'];

        if ($quote && floor($quote->getBaseGrandTotal()) < $minAmount) {
            return false;
        }

        if ($quote && floor($quote->getBaseGrandTotal()) > $maxAmount && $maxAmount != '0') {
            return false;
        }

        $publicKey = $this->getConfigData('clearpay_merchant_id');
        $privateKey = $this->getConfigData('clearpay_secret_key');

        if (!$publicKey || !$privateKey) {
            return false;
        }

        return parent::isAvailable();
    }

    /**
     * @param Varien_Object $payment
     * @param               $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
//        $url = $this->getApiAdapter()->getApiRouter()->getRefundUrl($payment);
//        $helper = $this->helper();
//
//        $helper->log('Refunding order url: ' . $url . ' amount: ' . $amount, Zend_Log::DEBUG);
//
//        if( $amount == 0 ) {
//            $helper->log("Zero amount refund is detected, skipping Clearpay API Refunding");
//            return $this;
//        }
//
//        //Ver 1 needs Merchant Reference variable
//        $body = $this->getApiAdapter()->buildRefundRequest($amount, $payment);
//
//        $response = $this->_sendRequest($url, $body, 'POST');
//        $resultObject = json_decode($response, true);
//
//        if (isset($resultObject['errorId']) || isset($resultObject['errorCode'])) {
//            throw Mage::exception(
//                'Clearpay_Clearpay',
//                $helper->__('Clearpay API Error: %s', $resultObject['message'])
//            );
//        }
//
//        $helper->log("refund results:\n" . print_r($resultObject, true), Zend_Log::DEBUG);

//        Mage::log("message", null, 'clearpay.log', true);
        var_dump("llega");
        die;
        return $this;
    }

}
