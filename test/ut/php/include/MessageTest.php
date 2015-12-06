<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTest.php';
require_once IZNIK_BASE . '/include/message/Message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class messageTest extends IznikTest {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_emails WHERE email IN ('test@test.com', 'test2@test.com'));");
        $dbhm->preExec("DELETE FROM users WHERE id in (SELECT userid FROM users_logins WHERE uid IN ('testid', '1234'));");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup1';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup2';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testRelated() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/basic');
        $msg = str_replace('Basic test', 'OFFER: Test item', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $id1 = $m->save();

        # TAKEN after OFFER - should match
        $msg = str_replace('OFFER: Test item', 'TAKEN: Test item', $msg);
        $msg = str_replace('22 Aug 2015', '22 Aug 2016', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());
        $atts = $m->getPublic();
        assertEquals($id1, $atts['related'][0]['id']);

        # TAKEN before OFFER - shouldn't match
        $msg = str_replace('22 Aug 2016', '22 Aug 2014', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(0, $m->recordRelated());

        # TAKEN after OFFER but for other item - shouldn't match
        $msg = str_replace('22 Aug 2014', '22 Aug 2016', $msg);
        $msg = str_replace('TAKEN: Test item', 'TAKEN: Something else', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(0, $m->recordRelated());

        error_log(__METHOD__ . " end");
    }
}

