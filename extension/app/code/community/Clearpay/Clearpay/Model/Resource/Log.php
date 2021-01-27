<?php

/**
 * Class Clearpay_Clearpay_Model_Resource_Log
 */
class Clearpay_Clearpay_Model_Resource_Log extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('clearpay/log', 'id');
    }
}
