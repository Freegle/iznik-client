<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Twitter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class twitterTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->tidy();
    }

    public function testBasic() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $t = new Twitter($this->dbhr, $this->dbhm, $gid);
        $data = file_get_contents(IZNIK_BASE . '/test/ut/php/images/chair.jpg');
        $t->tweet('Test - ignore', $data);

        error_log(__METHOD__ . " end");
    }
}

