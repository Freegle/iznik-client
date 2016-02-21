<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/Message.php';
require_once IZNIK_BASE . '/include/misc/Location.php';
require_once IZNIK_BASE . '/include/spam/Spam.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class messageTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE users_emails.email IN ('test@test.com', 'test2@test.com');");
        $dbhm->preExec("DELETE users, users_logins FROM users INNER JOIN users_logins ON users.id = users_logins.userid WHERE uid IN ('testid', '1234');");
        $dbhm->preExec("DELETE FROM users WHERE fullname = 'Test User';");
        $dbhm->preExec("DELETE FROM users WHERE firstname = 'Test' AND lastname = 'User';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup1';");
        $dbhm->preExec("DELETE FROM groups WHERE nameshort = 'testgroup2';");

        # We test around Tuvalu.  If you're setting up Tuvalu Freegle you may need to change that.
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 8.3 AND swlat <= 8.7;");
        $dbhm->preExec("DELETE FROM locations_grids WHERE swlat >= 179.1 AND swlat <= 179.3;");
        $dbhm->preExec("DELETE FROM locations WHERE name LIKE 'Tuvalu%';");
        for ($swlat = 8.3; $swlat <= 8.6; $swlat += 0.1) {
            for ($swlng = 179.1; $swlng <= 179.3; $swlng += 0.1) {
                $nelat = $swlat + 0.1;
                $nelng = $swlng + 0.1;

                # Use lng, lat order for geometry because the OSM data uses that.
                $dbhm->preExec("INSERT IGNORE INTO locations_grids (swlat, swlng, nelat, nelng, box) VALUES (?, ?, ?, ?, GeomFromText('POLYGON(($swlng $swlat, $nelng $swlat, $nelng $nelat, $swlng $nelat, $swlng $swlat))'));",
                    [
                        $swlat,
                        $swlng,
                        $nelat,
                        $nelng
                    ]);
            }
        }
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testSetFromIP() {
        error_log(__METHOD__);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->setFromIP('8.8.8.8');
        assertEquals('google-public-dns-a.google.com', $m->getFromhost());

        error_log(__METHOD__ . " end");

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

        # TAKEN with similar wording - should match
        $msg = file_get_contents('msgs/basic');
        $msg = str_replace('Basic test', 'TAKEN: Test thing', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());

        error_log(__METHOD__ . " end");
    }

    public function testNoSender() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/nosender');
        $msg = str_replace('Basic test', 'OFFER: Test item', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $rc = $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertFalse($rc);

        error_log(__METHOD__ . " end");
    }

    public function testSuggest() {
        error_log(__METHOD__);

        $l = new Location($this->dbhr, $this->dbhm);
        $id = $l->create(NULL, 'Tuvalu High Street', 'Road', 'POINT(179.2167 8.53333)');

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = new Group($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.4);

        $m = new Message($this->dbhr, $this->dbhm);

        $msg = file_get_contents('msgs/basic');
        $msg = str_replace('Basic test', 'OFFER: Test item (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'testgroup1@yahoogroups.com', $msg);
        $mid = $m->save();
        $m = new Message($this->dbhr, $this->dbhm, $mid);
        $atts = $m->getPublic();
        error_log("Public " . var_export($atts, true));
        assertEquals($id, $atts['locationid']);
        assertEquals($id, $atts['location']['id']);

        $goodsubj = "OFFER: Test (Tuvalu High Street)";

        # Test variants which should all get corrected to the same value
        assertEquals($goodsubj, $m->suggestSubject($gid, $goodsubj));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR Test (High Street)"));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR - Test  - (High Street)"));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR Test Tuvalu High Street"));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR Test Tuvalu HIGH STREET"));
        assertEquals("OFFER: test (Tuvalu High Street)", $m->suggestSubject($gid, "OFFR TEST Tuvalu HIGH STREET"));

        # Test per-group keywords
        $g->setSettings([
            'keywords' => [
                'offer' => 'Offered'
            ]
        ]);
        $keywords = $g->getSetting('keywords', []);
        error_log("After set " . var_export($keywords, TRUE));

        assertEquals("Offered: Test (Tuvalu High Street)", $m->suggestSubject($gid,$goodsubj));

        error_log(__METHOD__ . " end");
    }


    public function testMerge() {
        error_log(__METHOD__);

        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = new Group($this->dbhr, $this->dbhm, $gid);

        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        # Now from a different email but the same YahooID, triggering a merge.
        $msg = file_get_contents('msgs/basic');
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $msg = str_ireplace('test@test.com', 'test2@test.com', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from2@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        # Check the merge happened.  Can't use findlog as we hide merge logs.
        $this->waitBackground();
        $fromuser = $m->getFromuser();
        $sql = "SELECT * FROM logs WHERE user = ? AND type = 'User' AND subtype = 'Merged';";
        $logs = $this->dbhr->preQuery($sql, [ $fromuser ]);
        assertEquals(1, count($logs));

        error_log(__METHOD__ . " end");
    }

    // For manual testing
//    public function testSpecial() {
//        error_log(__METHOD__);
//
//        $msg = file_get_contents('msgs/special');
//
//        $m = new Message($this->dbhr, $this->dbhm);
//        $rc = $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
//        assertTrue($rc);
//        $id = $m->save();
//        $m = new Message($this->dbhr, $this->dbhm, $id);
//        error_log("IP " . $m->getFromIP());
//        $s = new Spam($this->dbhr, $this->dbhm);
//        $s->check($m);
//
//
//        error_log(__METHOD__ . " end");
//    }
//
}

