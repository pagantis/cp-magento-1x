<?php

namespace Test\Common;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Clearpay\SeleniumFormUtils\SeleniumHelper;
use Test\Magento19Test;

/**
 * Class AbstractBuy19
 *
 * @package Test\Common
 */
abstract class AbstractBuy19 extends Magento19Test
{
    /**
     * Color of jacket
     */
    const COLOR = 'White';

    /**
     * Size of jacket
     */
    const SIZE = 'S';

    /**
     * Number of products
     */
    const QTY = '1';

    /**
     * Grand Total
     */
    const GRAND_TOTAL = 'GRAND TOTAL';

    /**
     * Product name
     */
    const PRODUCT_NAME = 'Linen Blazer';

    /**
     * Correct purchase message
     */
    const CORRECT_PURCHASE_MESSAGE = 'YOUR ORDER HAS BEEN RECEIVED.';

    /**
     * Canceled purchase message
     */
    const CANCELED_PURCHASE_MESSAGE = 'YOUR ORDER HAS BEEN CANCELED.';

    /**
     * Shopping cart message
     */
    const SHOPPING_CART_MESSAGE = 'SHOPPING CART';

    /**
     * Empty shopping cart message
     */
    const EMPTY_SHOPPING_CART = 'SHOPPING CART IS EMPTY';

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
        $this->selectColorAndSize();
        $this->addToCart();
    }

    /**
     * testAddToCart
     */
    public function addToCart()
    {
        $addToCartButtonSearch = WebDriverBy::className('add-to-cart-buttons');
        $addToCartButtonElement = $this->webDriver->findElement($addToCartButtonSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($addToCartButtonElement));
        $this->webDriver->executeScript("productAddToCartForm.submit(this)", array());
        $cartTotalsSearch = WebDriverBy::className('cart-totals');
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
     * selectColorAndSize
     */
    public function selectColorAndSize()
    {
        $colorWhiteSearch = WebDriverBy::className('option-white');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $colorWhiteSearch
            )
        );
        $colorWhiteElement = $this->webDriver->findElement($colorWhiteSearch);
        $colorWhiteElement->click();

        $optionSmallSearch = WebDriverBy::className('option-s');

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $optionSmallSearch
            )
        );
        $optionSmallElement = $this->webDriver->findElement($optionSmallSearch);
        $optionSmallElement->click();
        $colorSelectorLabelSearch = WebDriverBy::id('select_label_color');
        $colorSelectorLabelElement = $this->webDriver->findElement($colorSelectorLabelSearch);
        $color = $colorSelectorLabelElement->getText();
        $this->assertSame(self::COLOR, $color);
        $sizeLabelSearch = WebDriverBy::id('select_label_size');
        $sizeLabelElement = $this->webDriver->findElement($sizeLabelSearch);
        $size = $sizeLabelElement->getText();
        $this->assertSame(self::SIZE, $size);

        $this->findById('qty')->clear()->sendKeys(self::QTY);
    }

    /**
     * Go to the product page
     */
    public function goToProductPage()
    {
        $this->webDriver->get($this->magentoUrl);
        /** @var WebDriverBy $productGrid */
        $productGridSearch = WebDriverBy::className('products-grid');
        /** @var WebDriverBy $productLink */
        $productLinkSearch = $productGridSearch->linkText(strtoupper(self::PRODUCT_NAME));

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $productLinkSearch
            )
        );
        $productLinkElement = $this->webDriver->findElement($productLinkSearch);
        $this->webDriver->executeScript("arguments[0].scrollIntoView(true);", array($productLinkElement));
        sleep(3);
        $productLinkElement->click();
        $this->assertSame(
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

        $this->findById('s_method_flatrate_flatrate')->click();
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
        $accountMenu = WebDriverBy::cssSelector('.account-cart-wrapper a.skip-link.skip-account');
        $this->clickElement($accountMenu);

        $myAccountMenu = WebDriverBy::cssSelector('#header-account .first a');
        $this->clickElement($myAccountMenu);

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector('.box-account.box-recent')
            )
        );

        $status = $this->findByCss('.box-account.box-recent .data-table.orders .first .status em')->getText();
        $this->assertTrue(($status == $statusText));
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