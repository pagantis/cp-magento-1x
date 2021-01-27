<?php

namespace Test;

use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class Basic16Test
 * @package Test
 *
 * @group magento-basic-16
 */
class Basic16Test extends Magento16Test
{
    /**
     * String
     */
    const TITLE = 'Home page';

    /**
     * String
     */
    const BACKOFFICE_TITLE = 'Log into Magento Admin Page';

    /**
     * testMagentoOpen
     */
    public function testMagentoOpen()
    {
        $this->webDriver->get($this->magentoUrl);
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::TITLE
            )
        );

        $this->assertEquals(self::TITLE, $this->webDriver->getTitle());
        $this->quit();
    }

    /**
     * testBackofficeOpen
     */
    public function testBackofficeOpen()
    {
        $this->webDriver->get($this->magentoUrl.self::BACKOFFICE_FOLDER);
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::BACKOFFICE_TITLE
            )
        );

        $this->assertContains(self::BACKOFFICE_TITLE, $this->webDriver->getTitle());
        $this->quit();
    }
}