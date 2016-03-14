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

        $g = new Group($this->dbhr, $this->dbhm);
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
        error_log(var_export($ret, true));
        assertEquals(0, $ret['ret']);
        assertEquals($id, $ret['message']['id']);
        assertEquals(2, count($ret['message']['attachments']));
        $img1 = $ret['message']['attachments'][0]['id'];

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'w' => 100
        ], FALSE);

        assertEquals(1031, strlen($ret));

        $ret = $this->call('image', 'GET', [
            'id' => $img1,
            'h' => 100
        ], FALSE);

        assertEquals(1961, strlen($ret));

        $a->delete();
        $g->delete();

        error_log(__METHOD__ . " end");
    }

    public function testPut()
    {
        error_log(__METHOD__);

        $data = file_get_contents('images/chair.jpg');
        file_put_contents(IZNIK_BASE . "/http/uploads/chair.jpg", $data);

        $ret = $this->call('image', 'PUT', [
            'filename' => 'chair.jpg',
            'identify' => TRUE
        ]);

        assertEquals(0, $ret['ret']);
        assertNotNull($ret['id']);
        var_dump($ret);
        assertEquals('chair', $ret['items'][1]['name']);

        # Get coverage for the upload call.  Don't test it properly, as all it does is call a 3rd party component.
        $ret = $this->call('upload', 'POST', [
        ]);

        error_log("Upload returned " . var_export($ret, TRUE));
        assertEquals(NULL, $ret);

        error_log(__METHOD__ . " end");
    }
}
