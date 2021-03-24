<?php

/**
 * Class Clearpay_Clearpay_Model_Observer
 */
class Clearpay_Clearpay_Model_Observer
{
    /**
     * Cancel Orders after Expiration
     */
    public function cancelPendingOrders()
    {
        try {
            $orders = Mage::getModel('sales/order')->getCollection();
            $orders->getSelect()->join(
                array('p' => $orders->getResource()->getTable('sales/order_payment')),
                'p.parent_id = main_table.entity_id',
                array()
            );
            $orders
                ->addFieldToFilter('status', 'pending_payment')
                ->addFieldToFilter('method', 'clearpay')
                ->addFieldToFilter('created_at', array(
                    'from'     => strtotime('-7 days', time()),
                    'to'       => strtotime('-60 minutes', time()),
                    'datetime' => true
                ))
            ;

            foreach ($orders as $order) {
                if ($order->canCancel()) {
                    try {
                        $order->cancel();
                        $order->getStatusHistoryCollection(true);
                        $history = $order->addStatusHistoryComment('Order Expired in Clearpay', false);
                        $history->setIsCustomerNotified(false);
                        $order->save();
                    } catch (\Exception $exception) {
                        Mage::logException($exception);
                    }
                }
            }
        } catch (\Exception $exception) {
            Mage::logException($exception);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function paymentMethodIsActive(Varien_Event_Observer $observer)
    {
        $method = $observer->getMethodInstance();
        $result = $observer->getResult();
        $result->isAvailable = true;
        if ($method->getCode() == 'clearpay') {
            $config = Mage::getStoreConfig('payment/clearpay');
            $extraConfig = Mage::helper('clearpay/ExtraConfig')->getExtraConfig();
            $locale = substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2);
            $allowedCountries = json_decode($extraConfig['ALLOWED_COUNTRIES']);
            $minAmount = $config['clearpay_min_amount'];
            $maxAmount = $config['clearpay_max_amount'];
            $checkoutSession = Mage::getModel('checkout/session');
            $quote = $checkoutSession->getQuote();
            if (!in_array(strtoupper($locale), $allowedCountries)) {
                $addressCollection = $quote->getAddressesCollection();
                $addressData = $addressCollection->getData();
                for ($i = 0; $i <= count($addressData); $i++) {
                    if (isset($addressData[$i]) && array_search('shipping', $addressData[$i])) {
                        $locale = $addressData[$i]['country_id'];
                    }
                }
            }
            $amount = $quote->getGrandTotal();
            $categoryRestriction = false;
            $productCategories = array();
            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllVisibleItems() as $item) {
                $magentoProduct = $item->getProduct();
                $productCategories = array_merge($productCategories, $magentoProduct->getCategoryIds());
            }
            $clearpayRestrictedCategories = $config['clearpay_exclude_category'];
            if (!empty($clearpayRestrictedCategories)) {
                $clearpayRestrictedCategories = explode(",", $clearpayRestrictedCategories);
                $categoryRestriction = (bool) count(array_intersect($productCategories, $clearpayRestrictedCategories));
            }
            if (!in_array(strtoupper($locale), $allowedCountries) ||
                (int)$amount < (int)$minAmount ||
                ((int)$amount > (int)$maxAmount && (int)$maxAmount !== 0) ||
                $categoryRestriction) {
                $result = $observer->getResult();
                $result->isAvailable = false;
            }
        }
    }
}
