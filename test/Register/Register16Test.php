<?php

namespace Test\Register;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Common\AbstractRegister16;
/**
 * Class Register16Test
 * @package Test\Register
 *
 * @group magento-register-16
 */
class Register16Test extends AbstractRegister16
{
    /**
     * Register into Magento 1
     */
    public function testRegister()
    {
        $this->openMagento();
        $this->goToAccountPage();
        $this->goToAccountCreate();
        $this->fillFormAndSubmit();
        $this->quit();
    }

    /**
     * Fill register form
     */
    public function fillFormAndSubmit()
    {
        $this->findById('firstname')->clear()->sendKeys($this->configuration['firstname']);
        $this->findById('lastname')->clear()->sendKeys($this->configuration['lastname']);
        $this->findById('email_address')->clear()->sendKeys($this->configuration['email']);
        $this->findById('password')->clear()->sendKeys($this->configuration['password']);
        $this->findById('confirmation')->clear()->sendKeys($this->configuration['password']);
        $this->findById('form-validate')->submit();

        try {
            $successMessage = WebDriverBy::className('success-msg');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($successMessage);
            $this->webDriver->wait()->until($condition);
        } catch (\Exception $exception) {
            $errorMessage = WebDriverBy::className('error-msg');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($errorMessage);
            $this->webDriver->wait()->until($condition);
        }

        $this->assertTrue((bool) $condition);
    }
}
