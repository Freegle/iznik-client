<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/group/Facebook.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class socialactionsAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testBasic()
    {
        error_log(__METHOD__);

        # Log in as a mod of the Playground group, which has a Facebook page.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $ids = GroupFacebook::listForGroup($this->dbhr, $this->dbhm, $gid);

        foreach ($ids as $uid) {
            # Delete the last share so that there will be at least one.
            $this->dbhm->preExec("DELETE FROM groups_facebook_shares WHERE groupid = $gid ORDER BY date DESC LIMIT 1;");
            $this->dbhm->preExec("UPDATE groups_facebook SET valid = 1 WHERE groupid = $gid");

            $u->addMembership($gid, User::ROLE_MODERATOR);

            assertTrue($u->login('testpw'));

            # Now we're talking.
            $orig = $this->call('socialactions', 'GET', []);
            assertEquals(0, $orig['ret']);
            assertGreaterThan(0, count($orig['socialactions']));

            $ret = $this->call('socialactions', 'POST', [
                'id' => $orig['socialactions'][0]['id'],
                'uid' => $uid
            ]);

            assertEquals(0, $ret['ret']);

            # Shouldn't show in list of groups now.
            $ret = $this->call('socialactions', 'GET', []);
            error_log("Shouldn't show in " . var_export($ret, TRUE));
            assertEquals(0, $ret['ret']);

            assertTrue(count($ret['socialactions']) == 0 || $ret['socialactions'][0]['id'] != $orig['socialactions'][0]['id']);

            # Force a failure for coverage.
            $tokens = $this->dbhr->preQuery("SELECT * FROM groups_facebook WHERE groupid = $gid;");
            $this->dbhm->preExec("UPDATE groups_facebook SET token = 'a' WHERE groupid = $gid");

            $ret = $this->call('socialactions', 'POST', [
                'id' => $orig['socialactions'][0]['id'],
                'uid' => $uid,
                'dedup' => TRUE
            ]);

            $this->dbhm->preExec("UPDATE groups_facebook SET token = '{$tokens[0]['token']}' WHERE groupid = $gid");

            assertEquals(0, $ret['ret']);

            # Get again for coverage.
            $ret = $this->call('socialactions', 'GET', []);
            assertEquals(0, $ret['ret']);
        }

        error_log(__METHOD__ . " end");
    }

    public function testHide()
    {
        error_log(__METHOD__);

        # Log in as a mod of the Playground group, which has a Facebook page.
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create('Test', 'User', 'Test User');
        assertGreaterThan(0, $u->addLogin(User::LOGIN_NATIVE, NULL, 'testpw'));

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->findByShortName('FreeglePlayground');

        $ids = GroupFacebook::listForGroup($this->dbhr, $this->dbhm, $gid);

        foreach ($ids as $uid) {
            # Delete the last share so that there will be at least one.
            $this->dbhm->preExec("DELETE FROM groups_facebook_shares WHERE groupid = $gid ORDER BY date DESC LIMIT 1;");
            $this->dbhm->preExec("UPDATE groups_facebook SET valid = 1 WHERE groupid = $gid");

            $u->addMembership($gid, User::ROLE_MODERATOR);

            assertTrue($u->login('testpw'));

            # Now we're talking.
            $orig = $this->call('socialactions', 'GET', []);
            assertEquals(0, $orig['ret']);
            assertGreaterThan(0, count($orig['socialactions']));

            $ret = $this->call('socialactions', 'POST', [
                'id' => $orig['socialactions'][0]['id'],
                'uid' => $uid,
                'action' => 'Hide'
            ]);

            assertEquals(0, $ret['ret']);

            # Shouldn't show in list of groups now.
            $ret = $this->call('socialactions', 'GET', []);
            assertEquals(0, $ret['ret']);

            assertTrue(count($ret['socialactions']) == 0 || $ret['socialactions'][0]['id'] != $orig['socialactions'][0]['id']);
        }

        error_log(__METHOD__ . " end");
    }
}
