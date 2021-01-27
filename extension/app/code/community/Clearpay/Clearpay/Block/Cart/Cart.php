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
        $localConfigs = array(
            'ES' => array(
                'currency' => 'EUR',
                'symbol' => '€'
            ),
            'GB' => array(
                'currency' => 'GBP',
                'symbol' => '£'
            ),
            'US' => array(
                'currency' => 'USD',
                'symbol' => '$'
            ),
        );
        $currency = 'EUR';
        $currencySymbol = "€";

        if (isset($localConfigs[$config['clearpay_api_region']])) {
            $currency = $localConfigs[$config['clearpay_api_region']]['currency'];
            $currencySymbol = $localConfigs[$config['clearpay_api_region']]['symbol'];
        }

        $amountWithCurrency = $this->parseAmount($amount/4) . $currencySymbol;
        if ($currency === 'GBP') {
            $amountWithCurrency = $currencySymbol . $this->parseAmount($amount/4);
        }

        //$categoryRestriction = $this->isProductRestricted($config['clearpay_exclude_category']);
        $categoryRestriction = false;
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
            $variables = array(
                'PRICE_TEXT' => $this->__('4 interest-free payments of'),
                'AMOUNT_WITH_CURRENCY' => $amountWithCurrency,
                'DESCRIPTION_TEXT_ONE' => $desc1,
                'DESCRIPTION_TEXT_TWO' => $desc2,
                'ISO_COUNTRY_CODE' => $localeISOCode,
                'MORE_INFO' => $this->__('FIND OUT MORE'),
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
