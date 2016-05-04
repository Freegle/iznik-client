<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/..');
}
require_once UT_DIR . '/IznikWebTestCase.php';

use Facebook\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote;
use Facebook\WebDriver\Remote\DesiredCapabilities;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class homeTest extends IznikWebTestCase
{
    public function testBasic()
    {
        error_log(__METHOD__);

        // Test that when we're logged out we see the sign in link, and the give/find links.
        $this->driver->get(USER_TEST_SITE);
        $this->waitLoad();
        $this->driver->findElement(WebDriverBy::xpath("//a[@href='/give/whereami']"));
        $this->driver->findElement(WebDriverBy::xpath("//a[@href='/find/whereami']"));
        $this->driver->findElement(WebDriverBy::className('js-signin'))->isDisplayed();

        error_log(__METHOD__ . " end");
    }

    public function testGive()
    {
        error_log(__METHOD__);

        $this->driver->get(USER_TEST_SITE);
        $this->driver->switchTo();
        $this->waitLoad();
        $this->driver->findElement(WebDriverBy::xpath("//a[@href='/give/whereami']"))->click();
        sleep(2);

        # Next page - enter postcode.
        $this->driver->findElement(WebDriverBy::className("js-postcode"))->click();
        $this->driver->getKeyboard()->sendKeys('PR3 3TN');
        sleep(2);
        $this->driver->findElement(WebDriverBy::className("tt-suggestion"))->click();
        sleep(2);
        $this->driver->findElement(WebDriverBy::className("js-next"))->isDisplayed();
        $this->driver->findElement(WebDriverBy::className("js-next"))->click();
        sleep(2);

        # Uploading files is hard, so don't.
        $this->driver->findElement(WebDriverBy::className("tt-input"))->click();
        $this->driver->getKeyboard()->sendKeys('a thing');
        $this->driver->findElement(WebDriverBy::className("js-description"))->click();
        $this->driver->getKeyboard()->sendKeys("This comes from automated testing.  Please don't reply.");
        sleep(2);
        $this->driver->findElement(WebDriverBy::className("js-next"))->isDisplayed();
        $this->driver->findElement(WebDriverBy::className("js-next"))->click();
        sleep(2);

        # Add email
        $email = 'test-' . rand() . '@blackhole.io';
        $this->driver->findElement(WebDriverBy::className("js-email"))->click();
        $this->driver->getKeyboard()->sendKeys($email);
        sleep(2);
        $this->driver->findElement(WebDriverBy::className("js-next"))->isDisplayed();
        $this->driver->findElement(WebDriverBy::className("js-next"))->click();
        sleep(5);

        error_log(__METHOD__ . " end");
    }
}
