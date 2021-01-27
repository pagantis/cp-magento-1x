<?php

namespace Test\Buy;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Common\AbstractBuy16;

/**
 * @group magento-buy-unregistered-16
 */
class BuyUnregistered16Test extends AbstractBuy16
{
    const AMOUNT = '161.94';

    /**
     * Test Buy unregistered
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws \Exception
     */
    public function testBuyUnregistered16()
    {
        $this->prepareProductAndCheckout();
        $this->selectGuestAndContinue();
        $this->fillBillingInformation();
        $this->fillShippingMethod();
        $this->fillPaymentMethod();
        $this->goToClearpay();
        $this->verifyClearpay();
        $this->commitPurchase();
        $this->checkPurchaseReturn(self::CORRECT_PURCHASE_MESSAGE);
        $this->quit();
    }

    /**
     * Fill the billing information
     */
    public function fillBillingInformation()
    {
        // Fill the form
        $this->findById('billing:firstname')->sendKeys($this->configuration['firstname']);
        $this->findById('billing:lastname')->sendKeys($this->configuration['lastname']);
        $this->findById('billing:email')->sendKeys($this->configuration['email']);
        $this->findById('billing:street1')->sendKeys($this->configuration['street']);
        $this->findById('billing:city')->sendKeys($this->configuration['city']);
        $this->findById('billing:postcode')->sendKeys($this->configuration['zip']);
        $this->findById('billing:country_id')->sendKeys($this->configuration['country']);
        $this->findById('billing:region_id')->sendKeys($this->configuration['city']);
        $this->findById('billing:telephone')->sendKeys($this->configuration['phone']);

        //Continue to shipping, in this case shipping == billing
        $this->webDriver->executeScript('billing.save()');

        sleep(10);
        //Verify
        $checkoutStepShippingMethodSearch = WebDriverBy::id('checkout-shipping-method-load');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepShippingMethodSearch)
        );
        $this->assertTrue(
            (bool) WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepShippingMethodSearch)
        );
    }

    /**
     * Select checkout as guest and continue
     */
    public function selectGuestAndContinue()
    {
        //Checkout As Guest
        $formListSearch = WebDriverBy::className('form-list');
        $checkoutAsGuestSearch = $formListSearch->id('login:guest');
        $checkoutAsGuestElement = $this->webDriver->findElement($checkoutAsGuestSearch);
        $checkoutAsGuestElement->click();

        //Continue
        $continueButtonSearch = WebDriverBy::id('onepage-guest-register-button');
        $continueButtonElement = $this->webDriver->findElement($continueButtonSearch);
        $continueButtonElement->click();

        //Verify
        $checkoutStepBillingSearch = WebDriverBy::id('checkout-step-billing');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepBillingSearch)
        );
        $this->assertTrue(
            (bool) WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepBillingSearch)
        );
    }
}
