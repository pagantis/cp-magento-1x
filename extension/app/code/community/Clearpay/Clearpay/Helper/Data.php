<?php

/**
 * Class Clearpay_Clearpay_Helper_Data
 */
class Clearpay_Clearpay_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) Mage::getStoreConfig('payment/clearpay/active');
    }
}
