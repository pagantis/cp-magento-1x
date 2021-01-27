<?php

/**
 * Class Clearpay_Clearpay_Model_Environment
 */
class Clearpay_Clearpay_Model_Environment
{
    /**
     * SANDBOX
     */
    const SANDBOX = 'sandbox';

    /**
     * PRODUCTION
     */
    const PRODUCTION = 'production';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('clearpay')->__(' Sandbox'),
                'value' => self::SANDBOX,
            ),
            array(
                'label' => Mage::helper('clearpay')->__(' Production'),
                'value' => self::PRODUCTION,
            )
        );
    }
}
