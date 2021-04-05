<?php

require_once(__DIR__.'/../../../../../../lib/Clearpay/autoload.php');

/**
 * Class Clearpay_Clearpay_Helper_MerchantConfiguration
 */
class Clearpay_Clearpay_Helper_MerchantConfiguration extends Mage_Core_Helper_Abstract
{
    /**
     * DEFAULT MIN AMOUNT
     */
    const DEF_MIN_AMOUNT = 1.00;

    /**
     * DEFAULT MAX AMOUNT
     */
    const DEF_MAX_AMOUNT = 1.00;

    /**
     * @var mixed|null $merchantConfig
     */
    protected $merchantConfig;

    /**
     * @var array|null $moduleConfig
     */
    protected $moduleConfig;

    /**
     * Default available countries for the different operational regions
     *
     * @var array
     */
    protected $defaultCountriesPerRegion = array(
        'ES' => '["ES", "FR", "IT"]',
        'GB' => '["GB"]',
        'US' => '["US"]'
    );


    /**
     * MerchantConfiguration constructor.
     */
    public function __construct()
    {
        $this->moduleConfig = $this->getModuleConfig();
        $this->merchantConfig = $this->getMerchantConfiguration();
    }

    /**
     * @return mixed|null
     * @throws \Afterpay\SDK\Exception\InvalidArgumentException
     * @throws \Afterpay\SDK\Exception\NetworkException
     * @throws \Afterpay\SDK\Exception\ParsingException
     */
    private function getMerchantConfiguration()
    {
        $configurationResponse = null;
        $language = substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2);
        if ($this->moduleConfig['active'] &&
            !empty($this->moduleConfig['clearpay_merchant_id']) &&
            !empty($this->moduleConfig['clearpay_secret_key']) &&
            !empty($this->moduleConfig['clearpay_environment']) &&
            !empty($language)
        ) {
            if (!empty($this->moduleConfig['clearpay_merchant_id'])
                && !empty($this->moduleConfig['clearpay_secret_key'])
                && $this->moduleConfig['active']
            ) {
                $merchantAccount = new Afterpay\SDK\MerchantAccount();
                $merchantAccount
                    ->setMerchantId($this->moduleConfig['clearpay_merchant_id'])
                    ->setSecretKey($this->moduleConfig['clearpay_secret_key'])
                    ->setApiEnvironment($this->moduleConfig['clearpay_environment'])
                    ->setCountryCode($this->moduleConfig['clearpay_api_region']);

                $getConfigurationRequest = new Afterpay\SDK\HTTP\Request\GetConfiguration();
                $getConfigurationRequest->setMerchantAccount($merchantAccount);
                $getConfigurationRequest->send();
                $configurationResponse = $getConfigurationRequest->getResponse()->getParsedBody();
            }
        }

        // Update the allowed countries each time the config is required
        if (is_array($configurationResponse) && isset($configurationResponse[0]->activeCountries)) {
            Mage::helper('clearpay/ExtraConfig')->setExtraConfig(
                'ALLOWED_COUNTRIES',
                json_encode($configurationResponse[0]->activeCountries)
            );
        } else {
            $region = $this->moduleConfig['clearpay_api_region'];
            if (!empty($region) and is_string($region) && $region) {
                Mage::helper('clearpay/ExtraConfig')->setExtraConfig(
                    'ALLOWED_COUNTRIES',
                    $this->getCountriesPerRegion($region)
                );
            }
        }

        if (is_array($configurationResponse)) {
            return array_shift($configurationResponse);
        } else {
            return $configurationResponse;
        }
    }

    /**
     * @return mixed
     */
    private function getModuleConfig()
    {
        return Mage::getStoreConfig('payment/clearpay');
    }

    /**
     * @return float
     */
    public function getMinAmount()
    {
        if ($this->merchantConfig!=null && isset($this->merchantConfig->minimumAmount)) {
            $this->setConfigData('clearpay_min_amount', $this->merchantConfig->minimumAmount->amount);
            return $this->merchantConfig->minimumAmount->amount;
        }

        if (isset($this->moduleConfig['clearpay_min_amount']) &&
            $this->moduleConfig['clearpay_min_amount'] != self::DEF_MAX_AMOUNT
        ) {
            return $this->moduleConfig['clearpay_min_amount'];
        }
        return self::DEF_MIN_AMOUNT;
    }

    /**
     * @return float
     */
    public function getMaxAmount()
    {
        if ($this->merchantConfig!=null && isset($this->merchantConfig->maximumAmount)) {
            $this->setConfigData('clearpay_min_amount', $this->merchantConfig->maximumAmount->amount);
            return $this->merchantConfig->maximumAmount->amount;
        }

        if (isset($this->moduleConfig['clearpay_max_amount']) &&
            $this->moduleConfig['clearpay_max_amount'] != self::DEF_MAX_AMOUNT
        ) {
            return $this->moduleConfig['clearpay_max_amount'];
        }
        return self::DEF_MAX_AMOUNT;
    }

    /**
     * @param string $region
     * @return string
     */
    public function getCountriesPerRegion($region = '')
    {
        if (isset($this->defaultCountriesPerRegion[$region])) {
            return $this->defaultCountriesPerRegion[$region];
        }
        return json_encode(array());
    }

    /**
     * Save information from payment configuration
     *
     * @param string $field
     * @param string $value
     *
     * @return mixed
     */
    public function setConfigData($field, $value)
    {
        $path = 'payment/clearpay/'.$field;
        return Mage::getConfig()->saveConfig($path, $value, 'default', 0);
    }
}
