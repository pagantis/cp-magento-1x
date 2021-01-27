<?php

/**
 * Class Clearpay_Clearpay_Model_Resource_Order
 */
class Clearpay_Clearpay_Model_Resource_Concurrency extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('clearpay/concurrency', 'id');
    }
}
