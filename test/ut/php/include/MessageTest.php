<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';
require_once IZNIK_BASE . '/include/message/Message.php';
require_once IZNIK_BASE . '/include/chat/ChatRoom.php';
require_once IZNIK_BASE . '/include/chat/ChatMessage.php';
require_once IZNIK_BASE . '/include/misc/Location.php';
require_once IZNIK_BASE . '/include/spam/Spam.php';
require_once IZNIK_BASE . '/include/user/User.php';

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

        $grids = $dbhr->preQuery("SELECT * FROM locations_grids WHERE swlng >= 179.1 AND swlng <= 179.3;");
        foreach ($grids as $grid) {
            $sql = "SELECT id FROM locations_grids WHERE MBRTouches (GeomFromText('POLYGON(({$grid['swlng']} {$grid['swlat']}, {$grid['swlng']} {$grid['nelat']}, {$grid['nelng']} {$grid['nelat']}, {$grid['nelng']} {$grid['swlat']}, {$grid['swlng']} {$grid['swlat']}))'), box);";
            $touches = $dbhr->preQuery($sql);
            foreach ($touches as $touch) {
                $dbhm->preExec("INSERT IGNORE INTO locations_grids_touches (gridid, touches) VALUES (?, ?);", [ $grid['id'], $touch['id'] ]);
            }
        }

        # Delete any UT playground messages
        $g = Group::get($dbhr, $dbhm);
        $gid = $g->findByShortName('FreeglePlayground');
        $sql = "DELETE FROM messages_groups WHERE groupid = $gid AND yahooapprovedid < 500;";
        $this->dbhm->preExec($sql);
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

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id1, $already) = $m->save();

        # TAKEN after OFFER - should match
        $msg = str_replace('OFFER: Test item', 'TAKEN: Test item', $msg);
        $msg = str_replace('22 Aug 2015', '22 Aug 2016', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());
        $atts = $m->getPublic();
        assertEquals($id1, $atts['related'][0]['id']);

        # We don't match on messages with outcomes so hack this out out again.
        $this->dbhm->preExec("DELETE FROM messages_outcomes WHERE msgid = $id1;");

        # TAKEN before OFFER - shouldn't match
        $msg = str_replace('22 Aug 2016', '22 Aug 2014', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(0, $m->recordRelated());

        # TAKEN after OFFER but for other item - shouldn't match
        $msg = str_replace('22 Aug 2014', '22 Aug 2016', $msg);
        $msg = str_replace('TAKEN: Test item', 'TAKEN: Something completely different', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(0, $m->recordRelated());

        # TAKEN with similar wording - should match
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'TAKEN: Test items (location)', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());

        error_log(__METHOD__ . " end");
    }

    public function testRelated2() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', '[hertford_freegle] Offered - Grey Driveway Blocks - Hoddesdon', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id1, $already) = $m->save();

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', '[hertford_freegle] Offer - Pedestal Fan - Hoddesdon', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id2, $already) = $m->save();

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', '[hertford_freegle] TAKEN: Grey Driveway Blocks (Hoddesdon)', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());
        $atts = $m->getPublic();
        assertEquals($id1, $atts['related'][0]['id']);

        # We don't match on messages with outcomes so hack this out out again.
        $this->dbhm->preExec("DELETE FROM messages_outcomes WHERE msgid = $id1;");

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'TAKEN: Grey Driveway Blocks (Hoddesdon)', $msg);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        assertEquals(1, $m->recordRelated());
        $atts = $m->getPublic();
        assertEquals($id1, $atts['related'][0]['id']);
        $m1 = new Message($this->dbhr, $this->dbhm, $id1);
        $atts1 = $m1->getPublic();
        self::assertEquals('Taken', $atts1['outcomes'][0]['outcome']);

        error_log(__METHOD__ . " end");
    }

    public function testRelated3() {
        error_log(__METHOD__);

        # Post a message to two groups, mark it as taken on both, make sure that is handled correctly.
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid1 = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm);
        $gid2 = $g->create('testgroup2', Group::GROUP_REUSE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test (Location)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id1, $already) = $m->save();

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test (Location)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup2', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id2, $already) = $m->save();

        $m1 = new Message($this->dbhr, $this->dbhm, $id1);
        $m2 = new Message($this->dbhr, $this->dbhm, $id2);
        assertEquals($gid1, $m1->getGroups(FALSE, TRUE)[0]);
        assertEquals($gid2, $m2->getGroups(FALSE, TRUE)[0]);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'TAKEN: Test (Location)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id3, $already) = $m->save();

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'TAKEN: Test (Location)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup2', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        list($id4, $already) = $m->save();

        $m1 = new Message($this->dbhr, $this->dbhm, $id1);
        $m2 = new Message($this->dbhr, $this->dbhm, $id2);
        assertEquals(Message::OUTCOME_TAKEN, $m1->hasOutcome());
        assertEquals(Message::OUTCOME_TAKEN, $m2->hasOutcome());

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

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $g->setPrivate('lng', 179.15);
        $g->setPrivate('lat', 8.4);

        $m = new Message($this->dbhr, $this->dbhm);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', 'OFFER: Test item (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'testgroup1@yahoogroups.com', $msg);
        list($mid, $already) = $m->save();
        $m = new Message($this->dbhr, $this->dbhm, $mid);
        $atts = $m->getPublic();
        error_log("Public " . var_export($atts, true));

        # Shouldn't be able to see actual location
        assertFalse(array_key_exists('locationid', $atts));
        assertFalse(array_key_exists('location', $atts));
        assertEquals($id, $m->getPrivate('locationid'));

        $goodsubj = "OFFER: Test (Tuvalu High Street)";

        # Test variants which should all get corrected to the same value
        assertEquals($goodsubj, $m->suggestSubject($gid, $goodsubj));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFER:Test (High Street)"));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR Test (High Street)"));
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR - Test  - (High Street)"));
        error_log("--1");
        assertEquals($goodsubj, $m->suggestSubject($gid, "OFFR Test Tuvalu High Street"));
        error_log("--2");
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

        assertEquals("OFFER: Thing need (Tuvalu High Street)", "OFFER: Thing need (Tuvalu High Street)");

        error_log(__METHOD__ . " end");
    }

    public function testMerge() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        # Now from a different email but the same Yahoo UID.  This shouldn't trigger a merge as we should identify
        # them by the UID.
        $msg = $this->unique(file_get_contents('msgs/basic'));
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
        assertEquals(0, count($logs));

        error_log(__METHOD__ . " end");
    }

    public function testHebrew() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Basic test', '=?windows-1255?B?UkU6IE1hdGFub3MgTGFFdnlvbmltIFB1cmltIDIwMTYg7sf6yMzw5Q==?=
=?windows-1255?B?yfog7MjgxuHA6cnwxOnt?=', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::PENDING, $rc);

        error_log(__METHOD__ . " end");
    }
    
    public function testPrune() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/prune'));
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertGreaterThan(0, strlen($m->getMessage()));

        $msg = $this->unique(file_get_contents('msgs/prune2'));
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        error_log("Pruned to " . $m->getMessage());
        assertLessThan(20000, strlen($m->getMessage()));

        error_log(__METHOD__ . " end");
    }

    public function testReverseSubject() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('TAKEN: Test item (location)', $m->reverseSubject());

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $msg = str_replace('Basic test', '[StevenageFreegle] OFFER: Ninky nonk train and night garden characters St NIcks [1 Attachment]', $msg);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('TAKEN: Ninky nonk train and night garden characters St NIcks', $m->reverseSubject());

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g = Group::get($this->dbhr, $this->dbhm, $gid);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_ireplace('freegleplayground', 'testgroup1', $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $id);

        assertEquals('TAKEN: Test item (location)', $m->reverseSubject());

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $msg = str_replace('Basic test', 'Bexley Freegle OFFER: compost bin (Bexley DA5)', $msg);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('TAKEN: compost bin (Bexley DA5)', $m->reverseSubject());

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $msg = str_replace('Basic test', 'OFFER/CYNNIG: Windows 95 & 98 on DVD (Criccieth LL52)', $msg);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('TAKEN: Windows 95 & 98 on DVD (Criccieth LL52)', $m->reverseSubject());

        error_log(__METHOD__ . " end");
    }

    public function testStripQuoted() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals("Ok, here's a reply.", $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text2'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals("Ok, here's a reply.", $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text3'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals('Ok, here\'s a reply.

And something after it.', $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text4'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals('Replying.', $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text5'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals("Ok, here's a reply.", $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text6'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals("Ok, here's a reply.", $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text7'));
        $msg = str_replace('USER_SITE', USER_SITE, $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals("Ok, here's a reply with https://" . USER_SITE ." an url and https://" . USER_SITE, $stripped);

        $msg = $this->unique(file_get_contents('msgs/notif_reply_text8'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $stripped = $m->stripQuoted();
        assertEquals('Ok, here\'s a reply.', $stripped);

        error_log(__METHOD__ . " end");
    }
    
    public function testCensor() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/phonemail'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        $atts = $m->getPublic();
        assertEquals('Hey. xxx xxx and xxx@xxx.com.', $atts['textbody']);

        error_log(__METHOD__ . " end");
    }

    public function testModmail() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/modmail'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->getPrivate('modmail'));

        error_log(__METHOD__ . " end");
    }

    public function testAutoReply() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertFalse($m->isAutoreply());;

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Subject: Basic test', 'Subject: Out of the office', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->isAutoreply());

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Hey.', 'I aim to respond within', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->isAutoreply());

        $msg = $this->unique(file_get_contents('msgs/autosubmitted'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->isAutoreply());

        error_log(__METHOD__ . " end");
    }

    public function testBounce() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertFalse($m->isBounce());;

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Subject: Basic test', 'Subject: Mail delivery failed', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->isBounce());

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('Hey.', '550 No such user', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::EMAIL, 'from@test.com', 'to@test.com', $msg);
        assertTrue($m->isBounce());

        error_log(__METHOD__ . " end");
    }

    public function testAutoRepost() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_FREEGLE);
        $g->setPrivate('onyahoo', 1);

        $m = new Message($this->dbhr, $this->dbhm);

        $email = 'ut-' . rand() . '@' . USER_DOMAIN;

        # Put two messages on the group - one eligible for autorepost, the other not yet.
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('test@test.com', $email, $msg);
        $msg = str_replace('Basic test', 'OFFER: Test not due (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $email = 'ut-' . rand() . '@' . USER_DOMAIN;
        $id1 = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        $m = new Message($this->dbhr, $this->dbhm, $id1);
        $m->setPrivate('sourceheader', 'Platform');
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/attachment'));
        $msg = str_replace('test@test.com', $email, $msg);
        $msg = str_replace('Test att', 'OFFER: Test due (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id2 = $r->received(Message::YAHOO_APPROVED, $email, 'to@test.com', $msg);
        error_log("Due message $id2");
        $m = new Message($this->dbhr, $this->dbhm, $id2);
        $m->setPrivate('sourceheader', 'Platform');
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $m = new Message($this->dbhr, $this->dbhm);

        # Should get nothing - first message is not due and too old to generate a warning.
        error_log("Expect nothing");
        list ($count, $warncount) = $m->autoRepostGroup(Group::GROUP_FREEGLE, '2016-03-01', $gid);
        assertEquals(0, $count);
        assertEquals(0, $warncount);

        # Call when repost not due.  First one should cause a warning only.
        error_log("Expect warning for $id2");
        $mysqltime = date("Y-m-d H:i:s", strtotime('35 hours ago'));
        $this->dbhm->preExec("UPDATE messages_groups SET arrival = '$mysqltime' WHERE msgid = ?;", [ $id2 ]);

        list ($count, $warncount) = $m->autoRepostGroup(Group::GROUP_FREEGLE, '2016-01-01', $gid);
        assertEquals(0, $count);
        assertEquals(1, $warncount);

        # Again - no action.
        error_log("Expect nothing");
        list ($count, $warncount) = $m->autoRepostGroup(Group::GROUP_FREEGLE, '2016-01-01', $gid);
        assertEquals(0, $count);
        assertEquals(0, $warncount);

        $m2 = new Message($this->dbhr, $this->dbhm, $id2);
        $_SESSION['id'] = $m2->getFromuser();
        $atts = $m2->getPublic();
        self::assertEquals(FALSE, $atts['canrepost']);
        self::assertEquals(TRUE, $atts['willautorepost']);

        # Make the message and warning look longer ago.  Then call - should cause a repost.
        error_log("Expect repost");
        $mysqltime = date("Y-m-d H:i:s", strtotime('49 hours ago'));
        $this->dbhm->preExec("UPDATE messages_groups SET arrival = '$mysqltime' WHERE msgid = ?;", [ $id2 ]);
        $this->dbhm->preExec("UPDATE messages_groups SET lastautopostwarning = '2016-01-01' WHERE msgid = ?;", [ $id2 ]);

        $m2 = new Message($this->dbhr, $this->dbhm, $id2);
        $atts = $m2->getPublic();
        error_log("Can repost {$atts['canrepost']} {$atts['canrepostat']}");
        self::assertEquals(TRUE, $atts['canrepost']);

        list ($count, $warncount) = $m->autoRepostGroup(Group::GROUP_FREEGLE, '2016-01-01', $gid);
        assertEquals(1, $count);
        assertEquals(0, $warncount);

        $this->waitBackground();
        $uid = $m2->getFromuser();
        $u = new User($this->dbhr, $this->dbhm, $uid);
        $atts = $u->getPublic(NULL, FALSE, TRUE);
        $log = $this->findLog('Message', 'Autoreposted', $atts['logs']);
        self::assertNotNull($log);

        error_log(__METHOD__ . " end");
    }

    public function testChaseup() {
        error_log(__METHOD__);

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup', Group::GROUP_FREEGLE);
        $g->setPrivate('onyahoo', 1);

        $m = new Message($this->dbhr, $this->dbhm);

        $email = 'ut-' . rand() . '@' . USER_DOMAIN;

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('test@test.com', $email, $msg);
        $msg = str_replace('Basic test', 'OFFER: Test (Tuvalu High Street)', $msg);
        $msg = str_ireplace('freegleplayground', 'testgroup', $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $mid = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertNotNull($mid);
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);
        $m = new Message($this->dbhr, $this->dbhm, $mid);

        # Create a reply
        $u = User::get($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $r = new ChatRoom($this->dbhr, $this->dbhm);
        $rid = $r->createConversation($m->getFromuser(), $uid);

        $c = new ChatMessage($this->dbhr, $this->dbhm);
        $cid = $c->create($rid, $uid, "Test reply", ChatMessage::TYPE_DEFAULT, $mid);

        # Chaseup - expect none as too recent.
        $count = $m->chaseUp(Group::GROUP_FREEGLE, '2016-03-01', $gid);
        assertEquals(0, $count);

        # Make it older.
        $mysqltime = date("Y-m-d H:i:s", strtotime('96 hours ago'));
        $this->dbhm->preExec("UPDATE messages_groups SET arrival = ? WHERE msgid = ?;", [
            $mysqltime,
            $mid
        ]);
        $c = new ChatMessage($this->dbhr, $this->dbhm, $cid);
        $c->setPrivate('date', $mysqltime);

        # Chaseup again - should get one.
        $count = $m->chaseUp(Group::GROUP_FREEGLE, '2016-03-01', $gid);
        assertEquals(1, $count);

        # And again - shouldn't, as the last chaseup was too recent.
        $count = $m->chaseUp(Group::GROUP_FREEGLE, '2016-03-01', $gid);
        assertEquals(0, $count);

        error_log(__METHOD__ . " end");
    }

    public function testTN() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/tnatt1'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $m->save();
        $atts = $m->getAttachments();
        assertEquals(1, count($atts));
        $m->delete();

        $msg = $this->unique(file_get_contents('msgs/tnatt2'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        $m->save();
        $atts = $m->getAttachments();
        assertEquals(1, count($atts));
        $m->delete();

        error_log(__METHOD__ . " end");
    }

    public function testIncludeArea() {
        error_log(__METHOD__);

        $l = new Location($this->dbhr, $this->dbhm);
        $areaid = $l->create(NULL, 'Tuvalu Central', 'Polygon', 'POLYGON((179.21 8.53, 179.21 8.54, 179.22 8.54, 179.22 8.53, 179.21 8.53, 179.21 8.53))', 0);
        assertNotNull($areaid);
        $pcid = $l->create(NULL, 'TV13', 'Postcode', 'POLYGON((179.2 8.5, 179.3 8.5, 179.3 8.6, 179.2 8.6, 179.2 8.5))');
        $fullpcid = $l->create(NULL, 'TV13 1HH', 'Postcode', 'POINT(179.2167 8.53333)', 0);
        $locid = $l->create(NULL, 'Tuvalu High Street', 'Road', 'POINT(179.2167 8.53333)', 0);

        $m = new Message($this->dbhr, $this->dbhm);
        $id = $m->createDraft();
        $m = new Message($this->dbhr, $this->dbhm, $id);

        $m->setPrivate('locationid', $fullpcid);
        $m->setPrivate('type', Message::TYPE_OFFER);
        $m->setPrivate('textbody', 'Test');

        $items = $this->dbhr->preQuery("SELECT * FROM items ORDER BY id ASC LIMIT 1;");

        foreach ($items as $item) {
            $m->addItem($item['id']);
        }

        $g = Group::get($this->dbhr, $this->dbhm);
        $gid = $g->create('testgroup1', Group::GROUP_REUSE);
        $g->setSettings([ 'includearea' => FALSE ]);

        $m->constructSubject($gid);
        self::assertEquals('OFFER: xmas decorations (TV13)', $m->getSubject());

        $g->setSettings([ 'includepc' => FALSE ]);
        $m->constructSubject($gid);
        self::assertEquals('OFFER: xmas decorations (Tuvalu Central)', $m->getSubject());

        error_log(__METHOD__ . " end");
    }

    public function testTNShow() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace('test@test.com', 'test@user.trashnothing.com', $msg);

        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'test@user.trashnothing.com', 'to@test.com', $msg);
        list($id1, $already) = $m->save();
        $atts = $m->getPublic();
        assertTrue($m->canSee($atts));
        $m->delete();

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
//        list($id, $already) = $m->save();
//        $m = new Message($this->dbhr, $this->dbhm, $id);
//        error_log("IP " . $m->getFromIP());
//        $s = new Spam($this->dbhr, $this->dbhm);
//        $s->check($m);
//
//
//        error_log(__METHOD__ . " end");
//    }

//    public function testType() {
//        $m = new Message($this->dbhr, $this->dbhm, 8153598);
//        error_log(Message::determineType($m->getSubject()));
//    }

}

