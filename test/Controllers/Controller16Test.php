<?php

namespace Test\Controllers;

use Httpful\Request;
use Httpful\Mime;
use Test\Magento16Test;

/**
 * Class Controller61Test
 * @package Test
 *
 * @group magento-controllers-16
 */
class Controller16Test extends Magento16Test
{
    /**
     * log route
     */
    const LOG_FOLDER = '/clearpay/log/download';

    /**
     * config route
     */
    const CONFIG_FOLDER = '/clearpay/config/';

    protected $configs = array(
        "CLEARPAY_TITLE",
        "CLEARPAY_SIMULATOR_DISPLAY_TYPE",
        "CLEARPAY_SIMULATOR_DISPLAY_SKIN",
        "CLEARPAY_SIMULATOR_DISPLAY_POSITION",
        "CLEARPAY_SIMULATOR_START_INSTALLMENTS",
        "CLEARPAY_SIMULATOR_CSS_POSITION_SELECTOR",
        "CLEARPAY_SIMULATOR_DISPLAY_CSS_POSITION",
        "CLEARPAY_SIMULATOR_CSS_PRICE_SELECTOR",
        "CLEARPAY_SIMULATOR_CSS_QUANTITY_SELECTOR",
        "CLEARPAY_FORM_DISPLAY_TYPE",
        "CLEARPAY_DISPLAY_MIN_AMOUNT",
        "CLEARPAY_DISPLAY_MAX_AMOUNT",
        "URL_OK",
        "URL_KO",
    );

    /**
     * Test testLogDownload
     */
    public function testLogDownload()
    {
        $logUrl = $this->magentoUrl.self::LOG_FOLDER.'?secret='.$this->configuration['secretKey'];
        $response = Request::get($logUrl)->expects('json')->send();
        $this->assertEquals(2, count($response->body));
        $this->quit();
    }

    /**
     * Test testSetConfig
     */
    public function testSetConfig()
    {
        $notifyUrl = $this->magentoUrl.self::CONFIG_FOLDER.'post?secret='.$this->configuration['secretKey'];
        $body = array('CLEARPAY_TITLE' => 'changed');
        $response = Request::post($notifyUrl)
            ->body($body, Mime::FORM)
            ->expectsJSON()
            ->send();
        $this->assertEquals('changed', $response->body->CLEARPAY_TITLE);
        $this->quit();
    }

    /**
     * Test testGetConfig
     */
    public function testGetConfigs()
    {
        $notifyUrl = $this->magentoUrl.self::CONFIG_FOLDER.'get?secret='.$this->configuration['secretKey'];
        $response = Request::get($notifyUrl)->expects('json')->send();

        foreach ($this->configs as $config) {
            $this->assertArrayHasKey($config, (array) $response->body);
        }
        $this->quit();
    }
}
