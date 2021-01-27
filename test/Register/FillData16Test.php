<?php

namespace Test\Register;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Common\AbstractRegister16;

/**
 * Class FillData16Test
 * @package Test\Register
 *
 * @group magento-fill-data-16
 */
class FillData16Test extends AbstractRegister16
{
    /**
     * Complete info
     */
    public function testFillData()
    {
        $this->openMagento();
        $this->goToAccountPage();
        $this->login();
        $this->goToAddressBookAndFillAddress();
        $this->quit();
    }

    /**
     * Login
     */
    public function login()
    {
        $this->findById('email')->clear()->sendKeys($this->configuration['email']);
        $this->findById('pass')->clear()->sendKeys($this->configuration['password']);
        $this->findById('login-form')->submit();
        $condition = WebDriverExpectedCondition::titleIs('My Account');
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * Go to Address book
     */
    public function goToAddressBookAndFillAddress()
    {
        $linkText = 'Edit Address';
        $this->findByPartialLinkText($linkText)->click();
        try {
            $this->findById('firstname')->clear()->sendKeys($this->configuration['firstname']);
            $this->findById('telephone')->clear()->sendKeys($this->configuration['phone']);
            $this->findById('street_1')->clear()->sendKeys($this->configuration['street']);
            $this->findById('country')->sendKeys($this->configuration['country']);
            $this->findById('city')->clear()->sendKeys($this->configuration['city']);
            $this->findById('region_id')->sendKeys($this->configuration['city']);
            $this->findById('zip')->sendKeys($this->configuration['zip']);
            $this->findById('form-validate')->submit();

            $successMessage = WebDriverBy::className('success-msg');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($successMessage);
            $this->webDriver->wait()->until($condition);
        } catch (\Exception $exception) {
            $changeAddressButton = WebDriverBy::partialLinkText('Change Billing Address');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($changeAddressButton);
            $this->webDriver->wait()->until($condition);
        }
        $this->assertTrue((bool) $condition);
    }
}
