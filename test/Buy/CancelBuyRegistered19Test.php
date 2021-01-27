<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Clearpay\SeleniumFormUtils\SeleniumHelper;
use Test\Common\AbstractBuy19;

/**
 * Class CancelBuyRegisteredTest
 * @package Test\Buy
 *
 * @group magento-cancel-buy-registered-19
 */
class CancelBuyRegistered19Test extends AbstractBuy19
{
    /**
     * Test Buy Registered
     */
    public function testCancelBuyRegistered()
    {
        $this->prepareProductAndCheckout();
        $this->login();
        $this->fillBillingInformation();
        $this->fillShippingMethod();
        $this->fillPaymentMethod();
        $this->goToClearpay();
        $this->cancelPurchase();
        $this->checkPurchaseReturn(self::SHOPPING_CART_MESSAGE);
        $this->quit();
    }

    /**
     * Fill the billing information
     */
    public function fillBillingInformation()
    {
        $this->webDriver->executeScript('billing.save()');
        $checkoutStepShippingMethodSearch = WebDriverBy::id('checkout-shipping-method-load');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepShippingMethodSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * Login
     */
    public function login()
    {
        $this->findById('login-email')->clear()->sendKeys($this->configuration['email']);
        $this->findById('login-password')->clear()->sendKeys($this->configuration['password']);
        $this->findById('login-form')->submit();

        $billingAddressSelector = WebDriverBy::id('billing-address-select');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($billingAddressSelector);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * Cancel Purchase
     * @throws \Exception
     */
    public function cancelPurchase()
    {
        // complete the purchase with redirect
        SeleniumHelper::cancelForm($this->webDriver);
    }

}