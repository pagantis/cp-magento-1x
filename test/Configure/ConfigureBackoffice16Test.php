<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Common\AbstractConfigure16;

/**
 * Class ConfigureBackoffice16Test
 * @package Test\Configure
 *
 * @group magento-configure-backoffice-16
 */
class ConfigureBackoffice16Test extends AbstractConfigure16
{
    /**
     * testConfigureBackoffice
     */
    public function testConfigureBackoffice()
    {
        $this->getBackofficeLoggedIn();
        $this->goToSystemConfig();
        $this->goToShippingMethodsAndSeeFedEx();
        $this->disableFedEx();
        $this->goToSystemConfig();
        $this->goToPaymentMethodsAndSeeClearpay();
        $this->configureAndSave();
        $this->webDriver->quit();
    }
}
