<?php

/**
 * Class Clearpay_Clearpay_Model_Iframe
 */
class Clearpay_Clearpay_Model_YesNo
{
    /**
     * YES
     */
    const YES = 1;

    /**
     * NO
     */
    const NO = 0;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('clearpay')->__(' Yes'),
                'value' => self::YES,
            ),
            array(
                'label' => Mage::helper('clearpay')->__(' No'),
                'value' => self::NO,
            )
        );
    }
}
