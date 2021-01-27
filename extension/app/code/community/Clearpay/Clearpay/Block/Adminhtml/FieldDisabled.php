<?php

require_once(__DIR__.'/../../../../../../../lib/Clearpay/autoload.php');

/**
 * Class Clearpay_Clearpay_Block_Adminhtml_FieldDisabled
 */
class Clearpay_Clearpay_Block_Adminhtml_FieldDisabled extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /** @var Clearpay_Clearpay_Helper_MerchantConfiguration $merchantConfiguration */
    protected $merchantConfiguration;

    protected function _getElementHtml($element)
    {
        $element->setReadonly(true);
        if ($element->getId() === 'payment_clearpay_clearpay_min_amount') {
            $element->setValue($this->getMinAmount());
        } elseif ($element->getId() === 'payment_clearpay_clearpay_max_amount') {
            $element->setValue($this->getMaxAmount());
        }

        return parent::_getElementHtml($element);
    }

    /**
     * @return Clearpay_Clearpay_Helper_MerchantConfiguration
     */
    public function getMerchantConfiguration()
    {
        return $this->merchantConfiguration;
    }

    /**
     * @param Clearpay_Clearpay_Helper_MerchantConfiguration $merchantConfiguration
     */
    public function setMerchantConfiguration($merchantConfiguration)
    {
        $this->merchantConfiguration = $merchantConfiguration;
    }

    //CLASS METHODS
    /**
     * @return float|string
     */
    public function getMinAmount()
    {
        if ($this->merchantConfiguration === null) {
            $this->merchantConfiguration = new Clearpay_Clearpay_Helper_MerchantConfiguration();
        }

        return $this->merchantConfiguration->getMinAmount();
    }

    /**
     * @return float|string
     */
    public function getMaxAmount()
    {
        if ($this->merchantConfiguration === null) {
            $this->merchantConfiguration = new Clearpay_Clearpay_Helper_MerchantConfiguration();
        }

        return $this->merchantConfiguration->getMaxAmount();
    }
}
