<?php

/**
 * Class Clearpay_Clearpay_Block_Cart_Message
 */
class Clearpay_Clearpay_Block_Cart_Cart extends Mage_Core_Block_Template
{
    /**
     * JS CDN URL
     */
    const CLEARPAY_JS_CDN_URL = 'https://js.sandbox.afterpay.com/afterpay-1.x.js';

    /**
     * Form constructor
     */
    protected function _construct()
    {
        $config = Mage::getStoreConfig('payment/clearpay');
        $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2);
        $localeISOCode = Mage::app()->getLocale()->getLocaleCode();
        $allowedCountries = json_decode($extraConfig['ALLOWED_COUNTRIES']);
        $checkoutSession = Mage::getModel('checkout/session');
        $quote = $checkoutSession->getQuote();
        $amount = $quote->getGrandTotal();
        $productCategories = array();
        $cart = Mage::getModel('checkout/cart')->getQuote();
        foreach ($cart->getAllVisibleItems() as $item) {
            $magentoProduct = $item->getProduct();
            $productCategories = array_merge($productCategories, $magentoProduct->getCategoryIds());
        }
        $clearpayRestrictedCategories = $config['clearpay_exclude_category'];
        if (!empty($clearpayRestrictedCategories)) {
            $clearpayRestrictedCategories = explode(",", $clearpayRestrictedCategories);
            $categoryRestriction = (bool) count(array_intersect($productCategories, $clearpayRestrictedCategories));
        }

        if (in_array(strtoupper($locale), $allowedCountries) &&
            $config['active'] === '1' &&
            !empty($config['clearpay_merchant_id']) &&
            !empty($config['clearpay_secret_key']) &&
            !$categoryRestriction
        ) {
            $desc1 = $this->__('With Clearpay you can receive your order now and pay in 4 interest-free');
            $desc1 .= ' ' . $this->__('equal fortnightly payments.');
            $desc1 .= ' ' . $this->__('Available to customers in the United Kingdom with a debit or credit card.');
            $desc2 = $this->__('When you click ”Checkout with Clearpay”');
            $desc2 .= ' ' . $this->__('you will be redirected to Clearpay to complete your order.');
            $version = explode('.', Mage::getVersion());
            $version = $version[1];
            $positionSelector = $config['clearpay_cart_position_selector'];
            if ($positionSelector == 'default' || empty($positionSelector)) {
                $positionSelector = '.cart-totals button.btn-proceed-checkout';
                if ($version < "9") {
                    $positionSelector = '.cart-collaterals .checkout-types';
                } else if ($version > "9") {
                    $positionSelector = '#shopping-cart-totals-table';
                }
            }

            $variables = array(
                'SDK_URL' => self::CLEARPAY_JS_CDN_URL,
                'CLEARPAY_MIN_AMOUNT' => $config['clearpay_min_amount'],
                'CLEARPAY_MAX_AMOUNT' => $config['clearpay_max_amount'],
                'AMOUNT' => $amount,
                'DESCRIPTION_TEXT_ONE' => $desc1,
                'DESCRIPTION_TEXT_TWO' => $desc2,
                'ISO_COUNTRY_CODE' => $localeISOCode,
                'POSITION_SELECTOR' => $positionSelector,
                'VERSION' => $version
            );
            $template = $this->setTemplate('clearpay/cart/cart.phtml');
            $template->assign($variables);
            if ($template->toHtml() == '') {
                $template->_allowSymlinks = true;
            }
            echo ($template->toHtml());
        }
        parent::_construct();
    }

    /**
     * @param array $variables
     */
    public function loadTemplate($variables = array())
    {
        $classCoreTemplate = Mage::getConfig()->getBlockClassName('core/template');
        $errorTemplate = new $classCoreTemplate;
        $errorTemplate->assign($variables);
        $html = $errorTemplate->setTemplate('clearpay/checkout/error.phtml')->toHtml();

        if ($html == '') {
            $errorTemplate->_allowSymlinks = true;
            $html = $errorTemplate->setTemplate('clearpay/checkout/error.phtml')->toHtml();
        }
        echo($html);
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
