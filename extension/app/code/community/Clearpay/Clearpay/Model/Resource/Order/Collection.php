<?php

/**
 * Class Clearpay_Clearpay_Model_Resource_Order_Collection
 */
class Clearpay_Clearpay_Model_Resource_Order_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('clearpay/order');
    }
}
