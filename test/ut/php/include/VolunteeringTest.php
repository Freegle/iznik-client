<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Volunteering.php';
require_once IZNIK_BASE . '/include/user/User.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class volunteeringTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $g = Group::get($dbhr, $dbhm);
        $this->groupid = $g->create('testgroup', Group::GROUP_FREEGLE);
        $dbhm->preExec("DELETE FROM volunteering WHERE title = 'Test vacancy';");
        $dbhm->preExec("DELETE FROM volunteering WHERE title LIKE 'Test volunteering%';");
    }

    protected function tearDown() {
        parent::tearDown ();
        $this->dbhm->preExec("DELETE FROM volunteering WHERE title = 'Test vacancy';");
        $this->dbhm->preExec("DELETE FROM volunteering WHERE title LIKE 'Test volunteering%';");
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        # Create an opportunity and check we can read it back.
        $c = new Volunteering($this->dbhm, $this->dbhm);
        $id = $c->create(NULL, 'Test vacancy', FALSE, 'Test location', NULL, NULL, NULL, NULL, NULL, NULL);
        assertNotNull($id);

        $c->addGroup($this->groupid);
        $start = ISODate('@' . (time()+600));
        $end = ISODate('@' . (time()+600));
        $c->addDate($start, $end, NULL);

        $atts = $c->getPublic();
        assertEquals('Test vacancy', $atts['title']);
        assertEquals('Test location', $atts['location']);
        assertEquals(1, count($atts['groups']));
        assertEquals($this->groupid, $atts['groups'][0]['id']);
        assertEquals(1, count($atts['dates']));
        assertEquals($start, $atts['dates'][0]['start']);
        assertEquals($start, $atts['dates'][0]['end']);

        # Check that a user sees what we want them to see.
        $u = User::get($this->dbhm, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');

        # Not in the right group - shouldn't see.
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, TRUE, FALSE, $ctx);
        assertEquals(0, count($volunteerings));

        # Right group - shouldn't see pending.
        $u->addMembership($this->groupid);
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, TRUE, FALSE, $ctx);
        assertEquals(0, count($volunteerings));

        # Mark not pending - should see.
        $c->setPrivate('pending', 0);
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, FALSE, FALSE, $ctx);
        error_log("Got when not pending " . var_export($volunteerings, TRUE));
        assertEquals(1, count($volunteerings));
        assertEquals($id, $volunteerings[0]['id']);

        # Remove things.
        $c->removeDate($atts['dates'][0]['id']);
        $c->removeGroup($this->groupid);

        $c = new Volunteering($this->dbhm, $this->dbhm, $id);
        $atts = $c->getPublic();
        assertEquals(0, count($atts['groups']));
        assertEquals(0, count($atts['dates']));

        # Delete event - shouldn't see it.
        $c->addGroup($this->groupid);
        $c->delete();
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, TRUE, FALSE, $ctx);
        assertEquals(0, count($volunteerings));

        error_log(__METHOD__ . " end");
    }

    public function testExpire() {
        error_log(__METHOD__);

        # Test one with a date.
        $c = new Volunteering($this->dbhm, $this->dbhm);
        $id = $c->create(NULL, 'Test vacancy', FALSE, 'Test location', NULL, NULL, NULL, NULL, NULL, NULL);
        assertNotNull($id);
        $c->addGroup($this->groupid);

        $start = ISODate('@' . (time()-600));
        $end = ISODate('@' . (time()-600));
        $c->addDate($start, $end, NULL);
        $start = ISODate('@' . (time()+600));
        $end = ISODate('@' . (time()+600));
        $did = $c->addDate($start, $end, NULL);
        $c->setPrivate('pending', 0);

        # Should see it as not yet expired.
        $u = User::get($this->dbhm, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');
        $u->addMembership($this->groupid);
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, FALSE, FALSE, $ctx);
        assertEquals(1, count($volunteerings));

        $this->dbhm->preExec("DELETE FROM volunteering_dates WHERE id = $did;");

        # Should now expire
        $c->expire($id);
        $volunteerings = $c->listForUser($uid, FALSE, FALSE, $ctx);
        assertEquals(0, count($volunteerings));

        # Now test one with no date.
        $id = $c->create(NULL, 'Test vacancy', FALSE, 'Test location', NULL, NULL, NULL, NULL, NULL, NULL);
        assertNotNull($id);
        $c->addGroup($this->groupid);
        $c->setPrivate('pending', 0);

        # Should see it as not yet expired.
        $u = User::get($this->dbhm, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');
        $u->addMembership($this->groupid);
        $ctx = NULL;
        $volunteerings = $c->listForUser($uid, FALSE, FALSE, $ctx);
        assertEquals(1, count($volunteerings));

        # Now make it old enough to expire.
        $c->setPrivate('added', '2017-01-01');
        $c->expire($id);
        $volunteerings = $c->listForUser($uid, FALSE, FALSE, $ctx);
        assertEquals(0, count($volunteerings));

        error_log(__METHOD__ . " end");
    }
}


