<?php

/**
 * Class Clearpay_Clearpay_Model_Concurrency
 */
class Clearpay_Clearpay_Model_Concurrency extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('clearpay/concurrency');
    }
}
