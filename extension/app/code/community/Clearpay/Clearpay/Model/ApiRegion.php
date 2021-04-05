<?php

/**
 * Class Clearpay_Clearpay_Model_ApiRegion
 */
class Clearpay_Clearpay_Model_ApiRegion
{
    /**
     * EU
     */
    const EU = 'ES';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('clearpay')->__(' Europe'),
                'value' => self::EU,
            )
        );
    }
}
