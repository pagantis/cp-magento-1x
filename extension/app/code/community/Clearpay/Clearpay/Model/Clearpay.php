<?php
require_once(__DIR__.'/../../../../../../lib/Clearpay/autoload.php');

/**
 * Class Clearpay_Clearpay_Model_Clearpay
 */
class Clearpay_Clearpay_Model_Clearpay extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @var string
     */
    protected $_code  = 'clearpay';

    /**
     * @var string
     */
    protected $_formBlockType = 'clearpay/checkout_clearpay';

     /** Payment Method features common for all payment methods
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    protected $_canRefund                  = true;
    protected $_canRefundInvoicePartial    = true;

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

        if ($quote && floor($quote->getBaseGrandTotal())>$maxAmount && $maxAmount != '0') {
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
        $clearpayRefund = $this->createRefundObject();
        // ---- needed values ----
        $transactionId = '';
        $order = $payment->getOrder();
        if ($order->hasInvoices()) {
            $oInvoiceCollection = $order->getInvoiceCollection();
            foreach ($oInvoiceCollection as $oInvoice) {
                $transactionId = $oInvoice->getTransactionId();
            }
        }
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        // ------------------------
        $clearpayRefund->setOrderId($transactionId);
        $clearpayRefund->setRequestId(md5(uniqid(rand(), true)));
        $clearpayRefund->setAmount(
            $amount,
            $currencyCode
        );
        $clearpayRefund->setMerchantReference($order->getId());


        if ($clearpayRefund->send()) {
            if ($clearpayRefund->getResponse()->isSuccessful()) {
                return $this;
            }
            $parsedBody = $clearpayRefund->getResponse()->getParsedBody();
            $message = "Clearpay Full Refund Error: " . $parsedBody->errorCode . '-> ' . $parsedBody->message;
            Mage::log($message, 3, 'clearpay.log', true);
        }

        return $this;
    }

    /**
     * Construct the Refunds Object based on the configuration and Refunds type
     * @return Afterpay\SDK\HTTP\Request\CreateRefund
     */
    private function createRefundObject()
    {

        $publicKey = $this->getConfigData('clearpay_merchant_id');
        $secretKey = $this->getConfigData('clearpay_secret_key');
        $environment = $this->getConfigData('clearpay_environment');

        $merchantAccount = new Afterpay\SDK\MerchantAccount();
        $merchantAccount
            ->setMerchantId($publicKey)
            ->setSecretKey($secretKey)
            ->setApiEnvironment($environment)
            ->setCountryCode('ES')
        ;

        $clearpayRefund = new Afterpay\SDK\HTTP\Request\CreateRefund();
        $clearpayRefund->setMerchantAccount($merchantAccount);

        return $clearpayRefund;
    }
}
