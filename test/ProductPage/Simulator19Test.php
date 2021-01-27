<?php

namespace Test\ProductPage;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Magento19Test;

/**
 * Class Simulator19Test
 * @package Test\ProductPage
 *
 * @group magento-product-page-19
 */
class Simulator19Test extends Magento19Test
{
    /**
     * Product name in magento 19
     */
    const PRODUCT_NAME = 'Linen Blazer';

    /**
     * testSimulatorDivExists
     */
    public function testSimulatorDivExists()
    {
        $this->goToProductPage();

        sleep(5);
        
        $clearpaySimulator = WebDriverBy::className('ClearpaySimulator');

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                $clearpaySimulator
            )
        );
        $this->assertTrue((bool) WebDriverExpectedCondition::presenceOfElementLocated(
            $clearpaySimulator
        ));

        $this->quit();
    }

    /**
     * Go to the product page
     */
    public function goToProductPage()
    {
        $this->webDriver->get($this->magentoUrl);

        /** @var WebDriverBy $pattialProductLink */
        $productLinkSearch = WebDriverBy::partialLinkText(strtoupper(self::PRODUCT_NAME));

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $productLinkSearch
            )
        );

        $productLinkElement = $this->webDriver->findElement($productLinkSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($productLinkElement));
        $productLinkElement->click();

        $this->assertContains(
            self::PRODUCT_NAME,
            $this->webDriver->getTitle()
        );
    }
}
