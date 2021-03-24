<?php

/**
 * Class Clearpay_Clearpay_Block_Product_Simulator
 */
class Clearpay_Clearpay_Block_Product_Simulator extends Mage_Catalog_Block_Product_View
{
    /**
     * JS CDN URL
     */
    const CLEARPAY_JS_CDN_URL = 'https://js.sandbox.afterpay.com/afterpay-1.x.js';

    /**
     * @var Mage_Catalog_Model_Product $_product
     */
    protected $_product;

    /**
     * Form constructor
     */
    protected function _construct()
    {
        $node = Mage::getConfig()->getNode();
        $config = Mage::getStoreConfig('payment/clearpay');
        $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
        echo '<!-- APVersion:'. (string)$node->modules->Clearpay_Clearpay->version.
            ' MG:'.Mage::getVersion().
            ' Env:'.$config['clearpay_environment'].
            ' MId:'.$config['clearpay_merchant_id'].
            ' Region:'.$config['clearpay_api_region'].
            ' Lang:'.substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2).
            ' Enabled:'.$config['active'].
            ' A_Countries:'.$extraConfig['ALLOWED_COUNTRIES'].
            ' R_Cat:'.(string)$config['clearpay_exclude_category'].
            ' -->';
        $allowedCountries = json_decode($extraConfig['ALLOWED_COUNTRIES']);
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2);
        $localeISOCode = Mage::app()->getLocale()->getLocaleCode();
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $categoryRestriction = $this->isProductRestricted($config['clearpay_exclude_category']);
        if (in_array(strtoupper($locale), $allowedCountries) &&
            $config['active'] === '1' &&
            !empty($config['clearpay_merchant_id']) &&
            !empty($config['clearpay_secret_key']) &&
            !$categoryRestriction
        ) {
            $priceSelector = $config['clearpay_price_selector'];
            if ($priceSelector == 'default' || empty($priceSelector)) {
                $priceSelector = '[id^=\'product-price\'] .price';
            }
            $positionSelector = $config['clearpay_position_selector'];
            if ($positionSelector == 'default' || empty($positionSelector)) {
                $positionSelector = '.price-info';
            }
            $this->assign(
                array(
                    'SDK_URL' => self::CLEARPAY_JS_CDN_URL,
                    'ISO_COUNTRY_CODE' => $localeISOCode,
                    'CURRENCY' => $currency,
                    'CLEARPAY_MIN_AMOUNT' => $config['clearpay_min_amount'],
                    'CLEARPAY_MAX_AMOUNT' => $config['clearpay_max_amount'],
                    'PRICE_SELECTOR' => $priceSelector,
                    'PRICE_SELECTOR_CONTAINER' => $positionSelector
                )
            );

            // check symlinks
            $classCoreTemplate = Mage::getConfig()->getBlockClassName('core/template');
            $simulatorTemplate = new $classCoreTemplate;
            $simulator = $simulatorTemplate->setTemplate('clearpay/product/simulator.phtml')->toHtml();
            if ($simulator == '') {
                $this->_allowSymlinks = true;
            }
        }
        parent::_construct();
    }

    /**
     * Devuelve el current product cuando estamos en ficha de producto
     *
     * @return Mage_Catalog_Model_Product|mixed
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('current_product');
        }

        return $this->_product;
    }

    /**
     * @param string $clearpayRestrictedCategories
     * @return bool
     */
    private function isProductRestricted($clearpayRestrictedCategories = '')
    {
        $product = $this->getProduct();
        $productCategories = $product->getCategoryIds();
        if (empty($clearpayRestrictedCategories)) {
            return false;
        }
        $clearpayRestrictedCategories = explode(",", $clearpayRestrictedCategories);
        return (bool) count(array_intersect($productCategories, $clearpayRestrictedCategories));
    }
}
