<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/message/Message.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/misc/Stats.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class statsTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->dbhm->exec("DELETE FROM groups WHERE nameshort = 'testgroup';");
        $this->dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        # Create a group with one message and one member.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_REUSE);

        # Test set members.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        error_log("Created user $uid");
        $u->addEmail('test@test.com');
        $u->addMembership($g->getId(), User::ROLE_MEMBER);
        $rc = $g->setMembers([
            [
                'uid' => $uid,
                'email' => 'test@test.com',
                'yahooModeratorStatus' => 'OWNER',
                'yahooPostingStatus' => 'MODERATED',
                'yahooDeliveryType' => 'DIGEST'
            ]
        ], MembershipCollection::APPROVED);
        assertEquals(0, $rc['ret']);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('FreeglePlayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals($gid, $m->getGroups()[0]);
        error_log("Created message $id on $gid");

        # Now send the same message again; this should replace the first, but shouldn't appear in the counts as a
        # separate message.
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals($gid, $m->getGroups()[0]);
        error_log("Created message $id on $gid");

        # Need to be a mod to see all.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u = User::get($this->dbhr, $this->dbhm, $uid);
        $u->setPrivate('systemrole', User::SYSTEMROLE_MODERATOR);
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));
        assertTrue($u->login('testpw'));

        # Now generate stats for today
        $s = new Stats($this->dbhr, $this->dbhm, $gid);
        $date = date ("Y-m-d", strtotime("today"));
        error_log("Generate for $date");
        $s->generate($date);

        $stats = $s->get($date);
        assertEquals(1, $stats['ApprovedMessageCount']);
        assertEquals(1, $stats['ApprovedMemberCount']);

        assertEquals([ 'FDv2' => 1 ], $stats['PostMethodBreakdown']);
        assertEquals([ 'Other' => 1 ], $stats['MessageBreakdown']);
        assertEquals([ 'DIGEST' => 1 ], $stats['YahooDeliveryBreakdown']);
        assertEquals([ 'MODERATED' => 1 ], $stats['YahooPostingBreakdown']);

        $multistats = $s->getMulti($date, [ $gid ], "30 days ago", "tomorrow");
        var_dump($multistats);
        assertEquals([
            [
                'date' => $date,
                'count' => 1
            ]
        ], $multistats['ApprovedMessageCount']);
        assertEquals([
            [
                'date' => $date,
                'count' => 1
            ]
        ], $multistats['ApprovedMemberCount']);
        assertEquals([
            [
                'date' => $date,
                'count' => 0
            ]
        ], $multistats['SpamMemberCount']);
        assertEquals([
            [
                'date' => $date,
                'count' => 0
            ]
        ], $multistats['SpamMessageCount']);

        # Now yesterday - shouldn't be any
        $s = new Stats($this->dbhr, $this->dbhm, $gid);
        $date = date ("Y-m-d", strtotime("yesterday"));
        $stats = $s->get($date);
        assertEquals(0, $stats['ApprovedMessageCount']);
        assertEquals(0, $stats['ApprovedMemberCount']);
        assertEquals([], $stats['PostMethodBreakdown']);
        assertEquals([], $stats['MessageBreakdown']);

        error_log(__METHOD__ . " end");
    }
}

