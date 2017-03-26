<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/user/Request.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RequestTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
    }

    public function __construct() {
    }

    public function testCentral() {
        error_log(__METHOD__);

        $r = $this->getMockBuilder('Request')
            ->setConstructorArgs([ $this->dbhr, $this->dbhm ])
            ->setMethods(array('sendIt'))
            ->getMock();
        $r->method('sendIt')->willReturn(TRUE);

        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');

        $rid = $r->create($uid, Request::TYPE_BUSINESS_CARDS, NULL, NULL);
        $r->completed($uid);

        error_log(__METHOD__ . " end");
    }
}

