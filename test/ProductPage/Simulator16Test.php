<?php

namespace Test\ProductPage;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Magento16Test;

/**
 * Class Simulator16Test
 * @package Test\ProductPage
 *
 * @group magento-product-page-16
 */
class Simulator16Test extends Magento16Test
{
    /**
     * Product name in magento 16
     */
    const PRODUCT_NAME = 'Olympus Stylus 750 7.1MP Digital Camera';

    /**
     * testSimulatorDivExists
     */
    public function testSimulatorDivExists()
    {
        $this->goToProductPage();

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
        $productLinkSearch = WebDriverBy::partialLinkText(self::PRODUCT_NAME);

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
