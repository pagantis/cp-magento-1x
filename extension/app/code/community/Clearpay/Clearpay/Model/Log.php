<?php

/**
 * Class Clearpay_Clearpay_Model_Log
 */
class Clearpay_Clearpay_Model_Log extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('clearpay/log');
    }
}
