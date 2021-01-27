<?php

namespace Test\Common;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Clearpay\SeleniumFormUtils\SeleniumHelper;
use Test\Magento16Test;

/**
 * Class AbstractBuy16Test
 *
 * @package Test\Common
 */
abstract class AbstractBuy16 extends Magento16Test
{
    /**
     * Product name in magento 16
     */
    const PRODUCT_NAME = 'Olympus Stylus 750 7.1MP Digital Camera';

    /**
     * Grand Total
     */
    const GRAND_TOTAL = 'Grand Total';

    /**
     * Correct purchase message
     */
    const CORRECT_PURCHASE_MESSAGE = 'Your order has been received';

    /**
     * Canceled purchase message
     */
    const CANCELED_PURCHASE_MESSAGE = 'Your order has been canceled';

    /**
     * Shopping cart message
     */
    const SHOPPING_CART_MESSAGE = 'Shopping Cart';

    /**
     * Empty shopping cart message
     */
    const EMPTY_SHOPPING_CART = 'Shopping Cart is empty';

    /**
     * Clearpay Order Title
     */
    const CLEARPAY_TITLE = 'Clearpay';

    /**
     * Notification route
     */
    const NOTIFICATION_FOLDER = '/clearpay/notify';

    /**
     * Log route
     */
    const LOG_FOLDER = '/clearpay/log/download';

    /**
     * ExtraConfig route
     */
    const CONFIG_FOLDER = '/clearpay/config/';

    /**
     * Buy unregistered
     */
    public function prepareProductAndCheckout()
    {
        $this->goToProductPage();
        $this->addToCart();
    }

    /**
     * testAddToCart
     */
    public function addToCart()
    {
        $addToCartButtonSearch = WebDriverBy::cssSelector('.add-to-cart button');
        $addToCartButtonElement = $this->webDriver->findElement($addToCartButtonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($addToCartButtonElement));
        $addToCartButtonElement->click();
        $cartTotalsSearch = WebDriverBy::className('totals');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementTextContains(
                $cartTotalsSearch,
                self::GRAND_TOTAL
            )
        );
        $checkoutButtonSearch = $cartTotalsSearch->className('btn-proceed-checkout');
        $checkoutButtonElement = $this->webDriver->findElement($checkoutButtonSearch);
        $checkoutButtonElement->click();
        $checkoutStepLoginSearch = WebDriverBy::id('checkout-step-login');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                $checkoutStepLoginSearch
            )
        );
        $this->assertTrue((bool) WebDriverExpectedCondition::visibilityOfElementLocated(
            $checkoutStepLoginSearch
        ));
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

    /**
     * Fill the shipping method information
     */
    public function fillPaymentMethod()
    {
        sleep(5);

        $reviewStepSearch = WebDriverBy::id('p_method_clearpay');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable($reviewStepSearch)
        );
        $this->findById('p_method_clearpay')->click();

        $pgSimulator = WebDriverBy::className('ClearpaySimulator');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                $pgSimulator
            )
        );
        $this->assertTrue((bool) WebDriverExpectedCondition::presenceOfElementLocated(
            $pgSimulator
        ));

        $this->webDriver->executeScript("payment.save()");
        $reviewStepSearch = WebDriverBy::id('review-buttons-container');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($reviewStepSearch)
        );

        $this->assertTrue(
            (bool) WebDriverExpectedCondition::visibilityOfElementLocated($reviewStepSearch)
        );
    }

    /**
     * Fill the shipping method information
     */
    public function fillShippingMethod()
    {
        sleep(5);

        $this->webDriver->executeScript('shippingMethod.save()');

        $checkoutStepPaymentMethodSearch = WebDriverBy::id('checkout-payment-method-load');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepPaymentMethodSearch)
        );
        $this->assertTrue(
            (bool) WebDriverExpectedCondition::visibilityOfElementLocated($checkoutStepPaymentMethodSearch)
        );
    }


    /**
     * Complete order and open Clearpay (redirect or iframe methods)
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function goToClearpay()
    {
        sleep(5);

        $this->webDriver->executeScript('review.save()');
    }

    /**
     * Close previous clearpay session if an user is logged in
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function logoutFromClearpay()
    {
        // Wait the page to render (check the simulator is rendered)
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                WebDriverBy::name('minusButton')
            )
        );
        // Check if user is logged in in Clearpay
        $closeSession = $this->webDriver->findElements(WebDriverBy::name('one_click_return_to_normal'));
        if (count($closeSession) !== 0) {
            //Logged out
            $continueButtonSearch = WebDriverBy::name('one_click_return_to_normal');
            $continueButtonElement = $this->webDriver->findElement($continueButtonSearch);
            $continueButtonElement->click();
        }
    }

    /**
     * Verify That UTF Encoding is working
     */
    public function verifyUTF8()
    {
        $paymentFormElement = WebDriverBy::className('FieldsPreview-desc');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->assertSame(
            $this->configuration['firstname'] . ' ' . $this->configuration['lastname'],
            $this->findByClass('FieldsPreview-desc')->getText()
        );
    }

    /**
     * Check if the purchase was in the myAccount panel and with Processing status
     *
     * @param string $statusText
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function checkLastPurchaseStatus($statusText = 'Processing')
    {
        $accountMenu = WebDriverBy::cssSelector('.main p a');
        $this->clickElement($accountMenu);

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector('.my-account')
            )
        );

        $status = $this->findByCss('.my-account .page-title h1')->getText();
        $this->assertContains($statusText, $status);
    }

    /**
     * Check purchase return message
     *
     * @param string $message
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function checkPurchaseReturn($message = '')
    {
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector('.page-title h1')
            )
        );
        $successMessage = $this->findByCss('.page-title h1');
        $this->assertContains(
            $message,
            $successMessage->getText()
        );
    }

    /**
     * Commit Purchase
     * @throws \Exception
     */
    public function commitPurchase()
    {

        $condition = WebDriverExpectedCondition::titleContains(self::CLEARPAY_TITLE);
        $this->webDriver->wait()->until($condition, $this->webDriver->getCurrentURL());
        $this->assertTrue((bool)$condition, "PR32");

        // complete the purchase with redirect
        SeleniumHelper::finishForm($this->webDriver);
    }

    /**
     * Verify Clearpay
     *
     * @throws \Exception
     */
    public function verifyClearpay()
    {
        $condition = WebDriverExpectedCondition::titleContains(self::CLEARPAY_TITLE);
        $this->webDriver->wait()->until($condition, $this->webDriver->getCurrentURL());
        $this->assertTrue((bool)$condition, $this->webDriver->getCurrentURL());
    }
}