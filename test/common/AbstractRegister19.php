<?php

namespace Test\Common;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Magento19Test;

/**
 * Class AbstractRegister19
 * @package Test\Register
 */
abstract class AbstractRegister19 extends Magento19Test
{
    /**
     * String
     */
    const TITLE = 'Madison Island';

    /**
     * OpenMagento page
     */
    public function openMagento()
    {
        $this->webDriver->get($this->magentoUrl);

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::TITLE
            )
        );

        $this->assertEquals(self::TITLE, $this->webDriver->getTitle());
    }

    /**
     * Go to my account page
     */
    public function goToAccountPage()
    {
        $footerSearch = WebDriverBy::className('footer');
        $linkText = $title = strtoupper('My Account');
        $accountLinkSearch = $footerSearch->partialLinkText($linkText);
        $accountLinkElement = $this->webDriver->findElement($accountLinkSearch);
        $accountLinkElement->click();
        $createAccountButton = WebDriverBy::partialLinkText(strtoupper('Create an Account'));
        $condition = WebDriverExpectedCondition::elementToBeClickable($createAccountButton);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * Go To account create
     */
    public function goToAccountCreate()
    {
        $createAccountButton = WebDriverBy::partialLinkText(strtoupper('Create an Account'));
        $createAccountElement = $this->webDriver->findElement($createAccountButton);
        $createAccountElement->click();
        $firstNameSearch = WebDriverBy::id('firstname');
        $condition = WebDriverExpectedCondition::presenceOfElementLocated($firstNameSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }
}
