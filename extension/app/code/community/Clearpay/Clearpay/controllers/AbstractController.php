<?php

/**
 * Class AbstractController
 */
abstract class AbstractController extends Mage_Core_Controller_Front_Action
{
    /**
     * CLEARPAY_CODE
     */
    const CLEARPAY_CODE = 'clearpay';

    /**
     * @var integer $statusCode
     */
    protected $statusCode = 200;

    /**
     * @var string $errorMessage
     */
    protected $errorMessage = '';

    /**
     * @var string $errorDetail
     */
    protected $errorDetail = '';

    /**
     * @var string $headers
     */
    protected $headers;

    /**
     * @var string $format
     */
    protected $format = 'json';

    /**
     * Configure redirection
     *
     * @param bool   $error
     * @param null   $url
     * @param string $error_message
     * @return AbstractController
     */
    public function redirect($error = true, $url = null, $error_message = '')
    {

        if (!is_null($url)) {
            if (strpos($url, "http") === false) {
                $url = Mage::getUrl($url);
            }
            return $this->_redirectUrl($url);
        }

        if ($error) {
            $errorUrl = Mage::getUrl($this->config['urlKO']);
            if ($error_message != '') {
                $errorUrl .= "?error_message=" . $error_message;
            }
            return $this->_redirectUrl($errorUrl);
        }
        return $this->_redirectUrl(Mage::getUrl($this->config['urlOK']));
    }

    /**
     * Save log in SQL database
     *
     * @param string $message
     */
    public function saveLog($message)
    {
        try {
            Mage::log($message, null, 'clearpay.log', true);
        } catch (Exception $exception) {
            // Do nothing
        }
    }
}
