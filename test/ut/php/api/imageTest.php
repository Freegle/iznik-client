<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/MessageCollection.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class imageAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup';");
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testApproved()
    {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $group1 = $g->create('testgroup', Group::GROUP_REUSE);

        # Create a group with a message on it
        $msg = file_get_contents('msgs/attachment');
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $a = new Message($this->dbhr, $this->dbhm, $id);

        # Should be able to see this message even logged out.
        $ret = $this->call('message', 'GET', [
            'id' => $id,
            'collection' => 'Approved'
        ]);
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);
        assertEquals(2, count($ret['message']['attachments']));
        $img1 = $ret['message']['attachments'][0]['id'];

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'w' => 100
        ], FALSE);

        assertEquals(1124, strlen($ret));

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'h' => 100
        ], FALSE);

        assertEquals(2305, strlen($ret));

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'h' => 100,
            'group' => 1
        ], TRUE);

        error_log("Expect 1 " . var_export($ret, TRUE));
        assertEquals(1, $ret['ret']);

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'h' => 100,
            'newsletter' => 1
        ], TRUE);

        assertEquals(1, $ret['ret']);

        $a->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPost()
    {
        error_log(__METHOD__);

        $data = file_get_contents('images/pan.jpg');
        file_put_contents("/tmp/pan.jpg", $data);

        $ret = $this->call('image', 'POST', [
            'photo' => [
                'tmp_name' => '/tmp/pan.jpg',
                'type' => 'image/jpeg'
            ],
            'identify' => TRUE
        ]);

        var_dump($ret);
        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        $id = $ret['id'];

        # Now rotate.
        $origdata = $this->call('image', 'GET', [
            'id' => $id,
            'w' => 100
        ], FALSE);

        $ret = $this->call('image', 'POST', [
            'id' => $id,
            'rotate' => 90
        ]);

        assertEquals(0, $ret['ret']);

        $newdata = $this->call('image', 'GET', [
            'id' => $id,
            'w' => 100
        ], FALSE);

        error_log("Lengths " . strlen($origdata) . " vs " . strlen($newdata));
        assertNotEquals($origdata, $newdata);

//        $ret = $this->call('image', 'POST', [
//            'id' => $id,
//            'rotate' => 270
//        ]);
//
//        $newdata = $this->call('image', 'GET', [
//            'id' => $id,
//            'w' => 100
//        ], FALSE);
//
//        assertEquals($origdata, $newdata);

        error_log(__METHOD__ . " end");
    }
}
