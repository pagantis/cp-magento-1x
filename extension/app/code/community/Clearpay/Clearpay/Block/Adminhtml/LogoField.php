<?php

/**
 * Class Clearpay_Clearpay_Block_Adminhtml_LogoField
 */
class Clearpay_Clearpay_Block_Adminhtml_LogoField extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for payment solution
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '<div class="adminCPLogo '. substr(Mage::app()->getLocale()->getLocaleCode(), -2, 2) . '" 
            style="width:700px; height:100px; background:url(/skin/frontend/base/default/images/clearpay/logo.png) no-repeat 0 2px; background-size: 150px;">
              <p class="description">'.$this->__("Buy now, pay later - Enjoy interest-free payments").'</p>
              <p class="description"><a href="https://bo.clearpay.com" target="_blank">'.$this->__("Login to the Clearpay panel").'</a>&nbsp;
              <a href="https://developer.clearpay.com/platforms/#magento-1-x" target="_blank">'.$this->__("Documentation").'</a></p></div>';
    }
}
