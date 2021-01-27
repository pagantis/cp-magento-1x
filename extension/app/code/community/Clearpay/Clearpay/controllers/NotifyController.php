<?php

require_once(__DIR__.'/../../../../../../lib/Clearpay/autoload.php');
require_once(__DIR__.'/AbstractController.php');

use Afterpay\SDK\HTTP\Request as ClearpayRequest;
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as ClearpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\MerchantAccount as ClearpayMerchant;

/**
 * Class Clearpay_Clearpay_NotifyController
 */
class Clearpay_Clearpay_NotifyController extends AbstractController
{

    /** Concurrency tablename */
    const CONCURRENCY_TABLENAME = 'clearpay_cart_concurrency';

    /** Clearpay orders tablename */
    const ORDERS_TABLE = 'clearpay_order';

    /** Seconds to expire a locked request */
    const CONCURRENCY_TIMEOUT = 5;

    /**
     * mismatch amount threshold in cents
     */
    const MISMATCH_AMOUNT_THRESHOLD = 1;

    /**
     * @var bool $mismatchError
     */
    protected $mismatchError = false;

    /**
     * @var bool $paymentDeclined
     */
    protected $paymentDeclined = false;

    /**
     * @var string $clearpayCapturedPaymentId
     */
    protected $clearpayCapturedPaymentId;

    /**
     * @var string $merchantOrderId
     */
    protected $merchantOrderId;

    /**
     * @var Mage_Sales_Model_Order $merchantOrder
     */
    protected $merchantOrder;

    /**
     * @var string $clearpayOrderId
     */
    protected $clearpayOrderId;

    /**
     * @var Object $clearpayOrder
     */
    protected $clearpayOrder;

    /**
     * @var ClearpayMerchant $clearpayMerchantAccount
     */
    protected $clearpayMerchantAccount;

    /**
     * @var mixed $config
     */
    protected $config;

    /**
     * @var string $origin
     */
    protected $origin;

    /**
     * @var string $token
     */
    protected $token;

    /**
     * Cancel action
     */
    public function cancelAction()
    {
        try {
            $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
            $this->config = array('urlKO' => $extraConfig['URL_KO']);
            $this->merchantOrderId = Mage::app()->getRequest()->getParam('order');
            $this->getMerchantOrder();
            $this->restoreCart();
            return $this->redirect(true, null, $this->getRequest()->getParam('error_message'));
        } catch (Exception $exception) {
            $this->saveLog($exception->getMessage());
            return $this->redirect(true);
        }
    }

    /**
     * Main action of the controller. Dispatch the Notify process
     *
     * @return Mage_Core_Controller_Varien_Action|void
     * @throws Exception
     */
    public function indexAction()
    {
        // Validations
        $jsonResponse = array();
        try {
            $this->prepareVariables();
            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getClearpayOrderId();
            $this->getClearpayOrder();
            $this->validateAmount();
            $this->checkOrderStatus();
        } catch (\Exception $exception) {
            return $this->cancelProcess($exception->getMessage());
        }
        // Process Clearpay Order
        try {
            $this->captureClearpayPayment();
        } catch (\Exception $exception) {
            return $this->cancelProcess($exception->getMessage());
        }

        // Process Merchant Order
        try {
            $this->processMerchantOrder();
        } catch (\Exception $exception) {
            $this->rollbackMerchantOrder();
            return $this->cancelProcess($exception->getMessage());
        }

        try {
            $this->unblockConcurrency($this->merchantOrderId);
        } catch (\Exception $exception) {
            $this->saveLog($exception->getMessage());
        }

        return $this->finishProcess(false);
    }

    /**
     * Find and init variables needed to process payment
     *
     * @throws Exception
     */
    public function prepareVariables()
    {
        $this->merchantOrderId = Mage::app()->getRequest()->getParam('order');
        $this->token = Mage::app()->getRequest()->getParam('token');
        if ($this->merchantOrderId == '') {
            throw new \Exception('No quote found');
        }

        if ($this->token == '') {
            throw new \Exception('Unable to find token parameter on return url');
        }

        try {
            $config = Mage::getStoreConfig('payment/clearpay');
            $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
            $this->config = array(
                'urlOK' => $extraConfig['URL_OK'],
                'urlKO' => $extraConfig['URL_KO'],
                'publicKey' => $config['clearpay_merchant_id'],
                'privateKey' => $config['clearpay_secret_key'],
                'environment' => $config['clearpay_environment'],
                'region' => $config['clearpay_api_region'],
            );

            $countryCode = $this->getClearpayOrderCountryCode();
            $this->clearpayMerchantAccount = new ClearpayMerchant();
            $this->clearpayMerchantAccount
                ->setMerchantId($this->config['publicKey'])
                ->setSecretKey($this->config['privateKey'])
                ->setApiEnvironment($this->config['environment'])
            ;
            if (!is_null($countryCode)) {
                $this->clearpayMerchantAccount->setCountryCode($countryCode);
            }
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Check the concurrency of the purchase
     *
     * @throws Exception
     */
    public function checkConcurrency()
    {
        $this->unblockConcurrency();
        $this->blockConcurrency($this->merchantOrderId);
    }

    /**
     * Retrieve the merchant order by id
     *
     * @throws Exception
     */
    public function getMerchantOrder()
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $this->merchantOrder = Mage::getModel('sales/order')->loadByIncrementId($this->merchantOrderId);
        } catch (Exception $exception) {
            throw new \Exception('Merchant order not found');
        }
    }

    /**
     * Find Clearpay Order Id
     *
     * @throws Exception
     */
    private function getClearpayOrderId()
    {
        try {
            $model = Mage::getModel('clearpay/order');
            $model->load($this->token, 'token');

            $this->clearpayOrderId = $model->getClearpayOrderId();
            if (is_null($this->clearpayOrderId)) {
                throw new \Exception(
                    'Clearpay Order not found on database table clearpay_order with token' . $this->token
                );
            }
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Find Clearpay country code
     *
     * @throws Exception
     */
    private function getClearpayOrderCountryCode()
    {
        try {
            $model = Mage::getModel('clearpay/order');
            $model->load($this->token, 'token');

            $countryCode = $model->getCountryCode();
            if (is_null($countryCode)) {
                throw new \Exception(
                    'Clearpay country code not found on database table clearpay_order with token:' . $this->token
                );
            }
            return $countryCode;
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Find Clearpay Order in Orders Server using Clearpay SDK
     *
     * @throws Exception
     */
    private function getClearpayOrder()
    {
        $getOrderRequest = new ClearpayRequest();
        $getOrderRequest
            ->setMerchantAccount($this->clearpayMerchantAccount)
            ->setUri("/v1/orders/" . $this->clearpayOrderId)
        ;
        $getOrderRequest->send();

        if ($getOrderRequest->getResponse()->getHttpStatusCode() >= 400) {
            throw new \Exception($this->__('Unable to retrieve order from Clearpay:') . $this->clearpayOrderId);
        }
        $this->clearpayOrder = $getOrderRequest->getResponse()->getParsedBody();
    }

    /**
     * Check that the merchant order and the order in Clearpay have the same amount to prevent hacking
     *
     * @throws Exception
     */
    public function validateAmount()
    {
        $totalAmount = (string) $this->parseAmount($this->clearpayOrder->totalAmount->amount);
        $merchantAmount = (string) $this->parseAmount($this->merchantOrder->getGrandTotal());
        if ($totalAmount != $merchantAmount) {
            $numberClearpayAmount = (integer) $this->parseAmount((100 * $this->clearpayOrder->totalAmount->amount));
            $numberMerchantAmount = (integer) $this->parseAmount((100 * $this->merchantOrder->getGrandTotal()));
            $amountDff =  $numberMerchantAmount - $numberClearpayAmount;
            if (abs($amountDff) > self::MISMATCH_AMOUNT_THRESHOLD) {
                $this->mismatchError = true;
                $amountMismatchError = 'Amount mismatch in PrestaShop Cart #'. $this->merchantOrderId .
                    ' compared with Clearpay Order: ' . $this->clearpayOrderId .
                    '. The Cart in PrestaShop has an amount of ' . $merchantAmount . ' and in Clearpay ' . $totalAmount;

                $this->saveLog($amountMismatchError);
                throw new \Exception($amountMismatchError);
            }
        }
    }

    /**
     * Check that the merchant order was not previously processes and is ready to be paid
     *
     * @throws Exception
     */
    public function checkOrderStatus()
    {
        try {
            $model = Mage::getModel('clearpay/order');
            $model->load($this->token, 'token');

            $completed = $model->getCompletd();
            if ($completed === true) {
                $this->saveLog(
                    'Clearpay order: ' . $this->clearpayOrderId. ' was already processed'
                );
                $this->finishProcess(false);
            }

            $status = $this->merchantOrder->getStatus();
            if ($status !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                throw new \Exception(
                    'The order:' . $this->merchantOrderId . ' is not in the correct status: ' . $status
                );
            }
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Confirm the order in Clearpay
     *
     * @throws Exception
     */
    private function captureClearpayPayment()
    {
        $immediatePaymentCaptureRequest = new ClearpayImmediatePaymentCaptureRequest(
            array(
                'token' => $this->clearpayOrder->token,
                'merchantReference' => $this->config['publicKey']
            )
        );
        $immediatePaymentCaptureRequest->setMerchantAccount($this->clearpayMerchantAccount);
        $immediatePaymentCaptureRequest->send();
        if ($immediatePaymentCaptureRequest->getResponse()->getHttpStatusCode() >= 400) {
            $this->paymentDeclined = true;
            throw new \Exception(
                $this->__('Clearpay capture payment error, order token: ') . $this->token . '. ' .
                $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->errorCode
            );
        }
        $this->clearpayCapturedPaymentId = $immediatePaymentCaptureRequest->getResponse()->getParsedBody()->id;
        if (!$immediatePaymentCaptureRequest->getResponse()->isApproved()) {
            $this->paymentDeclined = true;
            throw new \Exception(
                $this->__('Clearpay capture payment error, the payment was not processed successfully')
            );
        }
    }

    /**
     * Process the merchant order and notify client
     *
     * @throws Exception
     */
    public function processMerchantOrder()
    {
        try {
            if ($this->merchantOrder->canInvoice()) {
                $invoice = $this->merchantOrder->prepareInvoice();
                if ($invoice->getGrandTotal() > 0) {
                    $invoice
                        ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE)
                        ->register();
                    $this->merchantOrder->addRelatedObject($invoice);
                    $payment = $this->merchantOrder->getPayment();
                    $payment->setCreatedInvoice($invoice);
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
                }
            }
            $this->merchantOrder->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                Mage_Sales_Model_Order::STATE_PROCESSING,
                'clearpayOrderId: ' . $this->clearpayCapturedPaymentId. ' ' .
                'clearpayOrderToken: '. $this->token,
                true
            );
            $this->merchantOrder->save();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        try {
            $this->merchantOrder->sendNewOrderEmail();

            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tableName = Mage::getSingleton('core/resource')->getTableName(self::ORDERS_TABLE);
            $sql = 'UPDATE ' . $tableName . ' SET completed = 1 where token = \'' . $this->token . '\'';
            $conn->query($sql);
        } catch (\Exception $exception) {
            $this->saveLog($exception->getMessage());
        }


        $this->saveLog(
            'Clearpay Order CONFIRMED' .
            '. Clearpay OrderId=' .  $this->clearpayCapturedPaymentId .
            '. Prestashop OrderId=' . $this->module->currentOrder
        );
    }

    /**
     * Leave the merchant order as it was previously
     *
     * @throws Exception
     */
    public function rollbackMerchantOrder()
    {
        try {
            $this->merchantOrder->setState(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                'Rollback merchant order with: 
                 clearpayOrderId: ' . $this->clearpayCapturedPaymentId. ' ' .
                ' and clearpayOrderToken: '. $this->token,
                false
            );
            $this->merchantOrder->save();
        } catch (\Exception $exception) {
            $this->saveLog('Error on Clearpay rollback Transaction: ' .
                '. Clearpay OrderId=' . $this->clearpayOrderId .
                '. Prestashop CartId=' . $this->merchantOrderId .
                '. Prestashop OrderId=' . $this->merchantOrderId .
                $exception->getMessage());
        }
    }

    /**
     * Lock the concurrency to prevent duplicated inputs
     * @param $orderId
     *
     * @throws Exception
     */
    protected function blockConcurrency($orderId)
    {
        try {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tableName = Mage::getSingleton('core/resource')->getTableName(self::CONCURRENCY_TABLENAME);
            $sql = "INSERT INTO  " . $tableName . "  VALUE ('" . $orderId. "'," . time() . ")";
            $conn->query($sql);
        } catch (Exception $exception) {
            $this->saveLog($exception->getMessage());
        }

        return true;
    }

    /**
     * @param null $orderId
     *
     * @throws Exception
     */
    private function unblockConcurrency($orderId = null)
    {
        try {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tableName = Mage::getSingleton('core/resource')->getTableName(self::CONCURRENCY_TABLENAME);
            if ($orderId == null) {
                $sql = "DELETE FROM " . $tableName . " WHERE timestamp <" . (time() - self::CONCURRENCY_TIMEOUT);
            } else {
                $sql = "DELETE FROM " . $tableName . " WHERE id  ='" . $orderId."'";
            }
            $conn->query($sql);
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        return true;
    }

    /**
     * @param null $amount
     * @return string
     */
    public function parseAmount($amount = null)
    {
        return number_format(
            round($amount, 2, PHP_ROUND_HALF_UP),
            2,
            '.',
            ''
        );
    }

    /**
     * Do all the necessary actions to cancel the confirmation process in case of error
     * 1. Unblock concurrency
     * 2. Restore cart
     * 3. Save log
     *
     * @param string $message
     * @throws Exception
     */
    public function cancelProcess($message = '')
    {
        $this->unblockConcurrency($this->merchantOrderId);
        $this->restoreCart();
        $this->saveLog($message);
        return $this->finishProcess(true);
    }

    /**
     * Restore the cart of the order
     */
    private function restoreCart()
    {
        try {
            if ($this->merchantOrder) {
                $cart = Mage::getSingleton('checkout/cart');
                $items = $this->merchantOrder->getItemsCollection();
                if ($cart->getItemsCount() <= 0) {
                    foreach ($items as $item) {
                        $cart->addOrderItem($item);
                    }
                    $cart->save();
                }
            }
        } catch (Exception $exception) {
            // Do nothing
        }
    }

    /**
     * Redirect the request to the e-commerce or show the output in json
     *
     * @param bool $error
     */
    public function finishProcess($error = true)
    {
        $parameters = array();
        if ($this->mismatchError) {
            $parameters["clearpay_mismatch"] = "true";
        }
        if ($this->paymentDeclined) {
            $parameters["clearpay_declined"] = "true";
            $parameters["clearpay_reference_id"] = $this->clearpayCapturedPaymentId;
        }

        $url = ($error)? $this->config['urlKO'] : $this->config['urlOK'];
        if (count($parameters) > 0) {
            $url .= "?";
            foreach ($parameters as $key => $value) {
                $url .= $key . '=' . $value . '&';
            }
        }
        return $this->redirect($error, $url);
    }
}