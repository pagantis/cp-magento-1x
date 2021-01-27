<?php

namespace Test\Configure;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Common\AbstractConfigure19;

/**
 * Class ConfigureBackoffice19Test
 * @package Test\Configure
 *
 * @group magento-configure-backoffice-19
 */
class ConfigureBackoffice19Test extends AbstractConfigure19
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
