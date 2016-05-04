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
}
