<?php

/**
 * Class Clearpay_Clearpay_Model_Resource_Concurrency_Collection
 */
class Clearpay_Clearpay_Model_Resource_Concurrency_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('clearpay/concurrency');
    }

    /**
     * Delete all items in the table
     *
     * @return Clearpay_Clearpay_Model_Resource_Concurrency_Collection
     */
    public function truncate()
    {
        foreach ($this->getItems() as $item) {
            $item->delete();
        }
        return $this;
    }
}
