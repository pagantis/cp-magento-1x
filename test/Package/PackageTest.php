<?php

namespace Test\ProductPage;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Test\Magento19Test;

/**
 * Class PackageTest
 * @package Test\PackageTest
 *
 * @group magento-package
 */
class PackageTest extends Magento19Test
{
    /**
     * Backoffice Title
     */
    const BACKOFFICE_TITLE = 'Log into Magento Admin Page';

    /**
     * Logged in
     */
    const BACKOFFICE_LOGGED_IN_TITLE = 'Dashboard / Magento Admin';

    /**
     * Configuration System
     */
    const BACKOFFICE_CONFIGURATION_TITLE = 'Configuration / System';

    /**
     * Edit extension page
     */
    const PACKAGE_EDITOR_TITLE = 'Edit Extension / Package Extensions / Magento Connect / System / Magento Admin';

    /**
     * Docker container name
     */
    const DOCKER_CONTAINER = 'magento19-test';

    /**
     * Vendor Folder
     */
    const VENDOR_FOLDER = 'lib/Clearpay';

    /**
     * @var release-version
     */
    protected $release;

    /**
     * testSimulatorDivExists
     */
    public function testGeneratePackage()
    {
        $this->createProdDependencies();
        $this->goToBackofficeLoggedIn();
        $this->goToModuleGenerator();
        $this->loadLocalPackage();
        $this->updateReleaseInfo();
        $this->saveAndCreatePackage();
        $this->copyOutSidePackage();
        $this->quit();
    }

    /**
     * Copy package outside docker
     */
    public function copyOutSidePackage()
    {
        exec(
            'docker cp ' .
            self::DOCKER_CONTAINER .
            ':/clearpay/var/connect/Clearpay_Clearpay-'
            . $this->release .
            '.tgz Clearpay_v' . $this->release . '.tgz'
        );
    }

    public function saveAndCreatePackage()
    {
        $this->webDriver->executeScript("createPackage()");

        $successMessage = WebDriverBy::className('success-msg');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($successMessage);
        $this->webDriver->wait()->until($condition);
    }

    /**
     * Delete dev dependencies
     */
    public function createProdDependencies()
    {
        $composerJsonFile = json_decode(file_get_contents('composer.json'), true);
        $composerJsonFile['config']['vendor-dir'] = 'extension/' . self::VENDOR_FOLDER . 'Prod';
        file_put_contents('composer.json', json_encode(
            $composerJsonFile,
            JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
        ));
        exec('composer install --no-dev');
        $composerJsonFile['config']['vendor-dir'] = 'extension/' . self::VENDOR_FOLDER;
        file_put_contents('composer.json', json_encode(
            $composerJsonFile,
            JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
        ));

        exec('docker exec -it '. self::DOCKER_CONTAINER .' rm -rf /clearpay/' . self::VENDOR_FOLDER);
        exec(
            'docker cp ' .
            'extension/' . self::VENDOR_FOLDER .
            'Prod ' .
            self::DOCKER_CONTAINER .
            ':/clearpay/' .
            self::VENDOR_FOLDER
        );
        exec('docker exec -it '. self::DOCKER_CONTAINER .' chown -R www-data /clearpay/');
        exec('rm -rf extension/' . self::VENDOR_FOLDER . 'Prod');
    }

    /**
     * Updated the release Info
     */
    public function updateReleaseInfo()
    {
        $this->findById('connect_extension_edit_tabs_release_info')->click();
        $versionInput = WebDriverBy::name('version');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($versionInput);
        $this->webDriver->wait()->until($condition);

        $this->updateReleaseVersion();
        $this->updateReleaseNotes();
    }

    /**
     * Update the release version
     */
    public function updateReleaseVersion()
    {
        if (false !== getenv('CIRCLE_TAG')) {
            $this->release = str_replace('v', '', getenv('CIRCLE_TAG'));
        }

        if (empty($this->release)) {
            $this->release = '0.0.0';
        }

        $this->findByName('version')->clear()->sendKeys($this->release);
    }

    /**
     * Update the release notes
     */
    public function updateReleaseNotes()
    {
        if (false !== getenv('RELEASE_NOTES')) {
            $notes = getenv('RELEASE_NOTES');
        }

        if (empty($notes)) {
            $notes = '* Development package';
        }

        $this->findByName('notes')->clear()->sendKeys($notes);
    }

    /**
     *
     */
    public function loadLocalPackage()
    {
        $this->findById('connect_extension_edit_tabs_load_local_package')->click();
        $localPackage = WebDriverBy::className('even');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($localPackage);
        $this->webDriver->wait()->until($condition);

        $this->findByClass('even')->click();
        $successMessage = WebDriverBy::className('success-msg');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($successMessage);
        $this->webDriver->wait()->until($condition);
    }

    /**
     * loginToBackoffice
     */
    public function loginToBackoffice()
    {
        //Fill the username and password
        $this->findById('username')->sendKeys($this->configuration['backofficeUsername']);
        $this->findById('login')->sendKeys($this->configuration['backofficePassword']);

        //Submit form:
        $form = $this->findById('loginForm');
        $form->submit();

        //Verify
        $this->webDriver->executeScript('closeMessagePopup()');
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::BACKOFFICE_LOGGED_IN_TITLE
            )
        );

        $this->assertContains(self::BACKOFFICE_LOGGED_IN_TITLE, $this->webDriver->getTitle());
    }

    /**
     * getBackOffice
     */
    public function goToBackOffice()
    {
        $this->webDriver->get($this->magentoUrl.self::BACKOFFICE_FOLDER);
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::BACKOFFICE_TITLE
            )
        );
        $this->assertContains(self::BACKOFFICE_TITLE, $this->webDriver->getTitle());
    }

    /**
     * getBackofficeLoggedIn
     */
    public function goToBackofficeLoggedIn()
    {
        $this->goToBackOffice();
        $this->loginToBackoffice();
    }

    /**
     * goToSystemConfig
     */
    public function goToSystemConfig()
    {
        $this->findByLinkText('System')->click();
        $this->findByLinkText('Configuration')->click();

        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::titleContains(
                self::BACKOFFICE_CONFIGURATION_TITLE
            )
        );

        $this->assertContains(self::BACKOFFICE_CONFIGURATION_TITLE, $this->webDriver->getTitle());
    }

    /**
     * goToSystemConfig
     */
    public function goToModuleGenerator()
    {
        $this->findByLinkText('System')->click();
        $this->findByLinkText('Magento Connect')->click();
        $this->findByLinkText('Package Extensions')->click();

        WebDriverExpectedCondition::titleContains(
            self::PACKAGE_EDITOR_TITLE
        );

        $this->assertEquals(self::PACKAGE_EDITOR_TITLE, $this->webDriver->getTitle());
    }
}
