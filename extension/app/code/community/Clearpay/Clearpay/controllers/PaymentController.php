<?php

require_once(__DIR__.'/../../../../../../lib/Clearpay/autoload.php');
require_once(__DIR__.'/AbstractController.php');

use Afterpay\SDK\HTTP\Request\CreateCheckout;
use Afterpay\SDK\MerchantAccount as ClearpayMerchantAccount;

/**
 * Class Clearpay_Clearpay_PaymentController
 */
class Clearpay_Clearpay_PaymentController extends AbstractController
{
    /**
     * @var integer $magentoOrderId
     */
    protected $magentoOrderId;

    /**
     * @var Mage_Sales_Model_Order $magentoOrder
     */
    protected $magentoOrder;

    /**
     * @var Mage_Customer_Model_Session $customer
     */
    protected $customer;

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $order;

    /**
     * @var Mage_Sales_Model_Order_Item $itemCollection
     */
    protected $itemCollection;

    /**
     * @var Mage_Sales_Model_Order $addressCollection
     */
    protected $addressCollection;

    /**
     * @var String $publicKey
     */
    protected $publicKey;

    /**
     * @var String $privateKey
     */
    protected $privateKey;

    /**
     * @var String $environment
     */
    protected $environment;

    /**
     * @var String $currency
     */
    protected $currency;

    /**
     * @var string magentoOrderData
     */
    protected $magentoOrderData;

    /**
     * @var mixed $addressData
     */
    protected $addressData;

    /**
     * @var string $redirectOkUrl
     */
    protected $redirectOkUrl;

    /**
     * @var string $notificationOkUrl
     */
    protected $notificationOkUrl;

    /**
     * @var string $cancelUrl
     */
    protected $cancelUrl;

    /**
     * @var string $urlToken
     */
    protected $urlToken;

    /**
     * @var array $allowedCountries
     */
    protected $allowedCountries;

    /**
     * Find and init variables needed to process payment
     */
    public function prepareVariables()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $this->magentoOrderId = $checkoutSession->getLastRealOrderId();

        $salesOrder = Mage::getModel('sales/order');
        $this->magentoOrder = $salesOrder->loadByIncrementId($this->magentoOrderId);

        $mageCore = Mage::helper('core');
        $this->magentoOrderData = json_decode($mageCore->jsonEncode($this->magentoOrder->getData()), true);
        $this->urlToken = strtoupper(md5(uniqid(rand(), true)));
        $this->redirectOkUrl = Mage::getUrl(
            'clearpay/notify',
            array(
                '_query' => array(
                    'token' => $this->urlToken,
                    'origin' => 'redirect',
                    'order' => $this->magentoOrderData['increment_id']
                )
            )
        );
        $this->notificationOkUrl = Mage::getUrl(
            'clearpay/notify',
            array(
                '_query' => array(
                    'token' => $this->urlToken,
                    'origin' => 'notification',
                    'order' => $this->magentoOrderData['increment_id']
                )
            )
        );
        $this->cancelUrl = Mage::getUrl(
            'clearpay/notify/cancel',
            array('_query' => array('token' => $this->urlToken, 'order' => $this->magentoOrderData['increment_id']))
        );

        $this->itemCollection = $this->magentoOrder->getAllVisibleItems();
        $addressCollection = $this->magentoOrder->getAddressesCollection();
        $this->addressData = $addressCollection->getData();

        $customerSession = Mage::getSingleton('customer/session');
        $this->customer = $customerSession->getCustomer();

        $moduleConfig = Mage::getStoreConfig('payment/clearpay');
        $this->publicKey = $moduleConfig['clearpay_merchant_id'];
        $this->privateKey = $moduleConfig['clearpay_secret_key'];
        $this->environment = $moduleConfig['clearpay_environment'];
        $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
        $this->allowedCountries = json_decode($extraConfig['ALLOWED_COUNTRIES']);
        $this->currency = Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Default Action controller, launch in a new purchase show Clearpay Form
     */
    public function indexAction()
    {
        $this->prepareVariables();
        if ($this->magentoOrder->getStatus() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            return $this->redirect(true, $this->redirectOkUrl . '&error_message=Wrong order Status');
        }

        $node = Mage::getConfig()->getNode();
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2);
        $email = $this->customer->email ? $this->customer->email : $this->magentoOrderData['customer_email'];
        $fullName = null;
        $telephone = null;
        $userAddress = null;
        $orderShippingAddress = null;
        $orderBillingAddress = null;
        $mgShippingAddress = null;
        $mgBillingAddress = null;
        $shippingFirstName = '';
        $shippingLastName = '';
        $shippingTelephone = null;
        $shippingAddress = null;
        $shippingCity = null;
        $shippingPostCode = null;
        $shippingCountryId = null;
        $mgBillingAddress = null;
        $billingFirstName = '';
        $billingLastName = '';
        $billingTelephone = null;
        $billingAddress = null;
        $billingCity = null;
        $billingPostCode = null;
        $billingCountryId = null;
        try {
            for ($i = 0; $i <= count($this->addressData); $i++) {
                if (isset($this->addressData[$i]) && array_search('shipping', $this->addressData[$i])) {
                    $mgShippingAddress = $this->addressData[$i];
                    $shippingFirstName = (!empty($mgShippingAddress['firstname']))?$mgShippingAddress['firstname']:'';
                    $shippingLastName = (!empty($mgShippingAddress['lastname']))?$mgShippingAddress['lastname']:'';
                    $shippingTelephone = (!empty($mgShippingAddress['telephone']))?$mgShippingAddress['telephone']:'';
                    $shippingAddress = (!empty($mgShippingAddress['street']))?$mgShippingAddress['street']:'';
                    $shippingCity = (!empty($mgShippingAddress['city']))?$mgShippingAddress['city']:'';
                    $shippingPostCode = (!empty($mgShippingAddress['postcode']))?$mgShippingAddress['postcode']:'';
                    $shippingCountryId = (!empty($mgShippingAddress['country_id']))?$mgShippingAddress['country_id']:'';
                }
                if (isset($this->addressData[$i]) && array_search('billing', $this->addressData[$i])) {
                    $mgBillingAddress = $this->addressData[$i];
                    $billingFirstName = $mgBillingAddress['firstname'];
                    $billingLastName = $mgBillingAddress['lastname'];
                    $billingTelephone = $mgBillingAddress['telephone'];
                    $billingAddress = $mgBillingAddress['street'];
                    $billingCity = $mgBillingAddress['city'];
                    $billingPostCode = $mgBillingAddress['postcode'];
                    $billingCountryId = $mgBillingAddress['country_id'];
                }
            }
            \Afterpay\SDK\Model::setAutomaticValidationEnabled(true);
            $createCheckoutRequest = new CreateCheckout();
            $clearpayMerchantAccount = new ClearpayMerchantAccount();
            $clearpayMerchantAccount
                ->setMerchantId($this->publicKey)
                ->setSecretKey($this->privateKey)
                ->setApiEnvironment($this->environment);

            if (!in_array(strtoupper($locale), $this->allowedCountries)) {
                $locale = $shippingCountryId;
            }

            $clearpayMerchantAccount->setCountryCode($locale);
            $createCheckoutRequest
                ->setMerchant(array(
                    'redirectConfirmUrl' => $this->redirectOkUrl,
                    'redirectCancelUrl' => $this->cancelUrl
                ))
                ->setMerchantAccount($clearpayMerchantAccount)
                ->setTotalAmount(
                    $this->parseAmount($this->magentoOrder->getGrandTotal()),
                    $this->currency
                )
                ->setTaxAmount(
                    $this->parseAmount(
                        $this->magentoOrder->getGrandTotal() - $this->magentoOrder->getTaxAmount()
                    ),
                    $this->currency
                )
                ->setConsumer(array(
                    'phoneNumber' => (!empty($shippingTelephone))?$shippingTelephone:$billingTelephone. '',
                    'givenNames' => (!empty($shippingFirstName))?$shippingFirstName:$billingFirstName.'',
                    'surname' => (!empty($shippingLastName))?$shippingLastName:$billingLastName,
                    'email' => $email
                ))
                ->setBilling(array(
                    'name' => $billingFirstName . ' ' . $billingLastName,
                    'line1' => $billingAddress,
                    'suburb' => $billingCity,
                    'state' => '',
                    'postcode' => $billingPostCode,
                    'countryCode' => $billingCountryId,
                    'phoneNumber' => $billingTelephone . ''
                ));
            if(!empty($shippingFirstName) && !empty($shippingTelephone))
            {
                $createCheckoutRequest
                    ->setShipping(array(
                        'name' => $shippingFirstName . ' ' . ((!empty($shippingLastName))?$shippingLastName:''),
                        'line1' => $shippingAddress,
                        'suburb' => $shippingCity,
                        'state' => '',
                        'postcode' => $shippingPostCode,
                        'countryCode' => $shippingCountryId,
                        'phoneNumber' => $shippingTelephone
                    ));
            }
            $createCheckoutRequest
                ->setShippingAmount(
                    $this->parseAmount($this->magentoOrder->getShippingAmount()),
                    $this->currency
                )
                ->setCourier(array(
                    'shippedAt' => '',
                    'name' => (string)$this->magentoOrder->getShippingMethod().' ',
                    'tracking' => '',
                    'priority' => 'STANDARD'
                ));


            $discountAmount = $this->magentoOrder->getDiscountAmount();
            if (!empty($discountAmount) && $discountAmount > 0) {
                $createCheckoutRequest->setDiscounts(array(
                    array(
                        'displayName' => 'Shop discount',
                        'amount' => array($this->parseAmount($discountAmount), $this->currency)
                    )
                ));
            }

            $products = array();
            foreach ($this->itemCollection as $item) {
                $products[] = array(
                    'name' => $item->getName().' ',
                    'sku' => '',
                    'quantity' => (int) $item->getQtyToShip(),
                    'price' => array(
                        'amount' => $this->parseAmount($item->getRowTotalInclTax()),
                        'currency' => $this->currency
                    )
                );
            }
            $createCheckoutRequest->setItems($products);
            $createCheckoutRequest->setMerchantReference($this->magentoOrderId);

            $header = 'Magento 1.x/' . (string)$node->modules->Clearpay_Clearpay->version
                . '(Magento/' . Mage::getVersion() . '; PHP/' . phpversion() . '; Merchant/' . $this->publicKey
                . ') ' . Mage::getBaseUrl();
            $createCheckoutRequest->addHeader('User-Agent', $header);
            $createCheckoutRequest->addHeader('Country', $shippingCountryId);
            if ($createCheckoutRequest->isValid()) {
                $createCheckoutRequest->send();
                $errorMessage = 'empty response';
                if ($createCheckoutRequest->getResponse()->getHttpStatusCode() >= 400
                    || isset($createCheckoutRequest->getResponse()->getParsedBody()->errorCode)
                ) {
                    if (isset($createCheckoutRequest->getResponse()->getParsedBody()->message)) {
                        $errorMessage = $createCheckoutRequest->getResponse()->getParsedBody()->message;
                    }
                    $errorMessage .= 'Error received when trying to create a order: ' .
                        '. Status code: ' . $createCheckoutRequest->getResponse()->getHttpStatusCode();
                    $this->saveLog($errorMessage);
                    return $this->redirect(true, $this->cancelUrl . '&error_message=' . $errorMessage);
                } else {
                    try {
                        $url = $createCheckoutRequest->getResponse()->getParsedBody()->redirectCheckoutUrl;
                        $this->insertOrderControl(
                            $this->magentoOrderId,
                            $createCheckoutRequest->getResponse()->getParsedBody()->token,
                            $this->urlToken,
                            $locale
                        );
                    } catch (\Exception $exception) {
                        $this->saveLog($exception->getMessage());
                        return $this->redirect(true, $this->cancelUrl . '&error_message=' . $exception->getMessage());
                    }
                }
            } else {
                $this->saveLog(json_encode($createCheckoutRequest->getValidationErrors()));
                return $this->redirect(
                    true,
                    $this->cancelUrl . '&error_message=' . json_encode($createCheckoutRequest->getValidationErrors())
                );
            }
        } catch (Exception $exception) {
            $this->saveLog($exception->getMessage());
            return $this->redirect(true, $this->cancelUrl . '&error_message=' . $exception->getMessage());
        }

        try {
            return $this->redirect(false, $url);
        } catch (Exception $exception) {
            $this->saveLog($exception->getMessage());
            return $this->redirect(true, $this->cancelUrl . '&error_message=' . $exception->getMessage());
        }
    }

    /**
     * Create a record in AbstractController::Clearpay ORDERS_TABLE to match the merchant order with the clearpay order
     *
     * @param string $magentoOrderId
     * @param string $clearpayOrderId
     * @param string $token
     * @param string $countryId
     *
     * @throws Exception
     */
    private function insertOrderControl($magentoOrderId, $clearpayOrderId, $token, $countryId)
    {
        $model = Mage::getModel('clearpay/order');
        $model->setData(array(
            'clearpay_order_id' => $clearpayOrderId,
            'mg_order_id' => $magentoOrderId,
            'token' => $token,
            'country_code' => $countryId,
        ));
        $model->save();
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
}
