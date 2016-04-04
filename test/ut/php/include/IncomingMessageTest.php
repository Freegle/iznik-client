<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/message/Message.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class IncomingMessageTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $dbhm->preExec("DELETE users, users_emails FROM users INNER JOIN users_emails ON users.id = users_emails.userid WHERE users_emails.email IN ('test@test.com', 'test2@test.com');");
        $dbhm->exec("DELETE FROM users WHERE yahooUserId = '420816297';");
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $t = "TestUser" . microtime(true) . "@test.com";
        $msg = str_replace('From: "Test User" <test@test.com>', 'From: "' . $t . '" <test@test.com>', $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('Basic test', $m->getSubject());
        assertEquals($t, $m->getFromname());
        assertEquals('test@test.com', $m->getFromaddr());
        assertEquals('Hey.', $m->getTextbody());
        assertEquals('from@test.com', $m->getEnvelopefrom());
        assertEquals('to@test.com', $m->getEnvelopeto());
        assertEquals("<HTML><HEAD>
<STYLE id=eMClientCss>
blockquote.cite { margin-left: 5px; margin-right: 0px; padding-left: 10px; padding-right:0px; border-left: 1px solid #cccccc }
blockquote.cite2 {margin-left: 5px; margin-right: 0px; padding-left: 10px; padding-right:0px; border-left: 1px solid #cccccc; margin-top: 3px; padding-top: 0px; }
.plain pre, .plain tt { font-family: monospace; font-size: 100%; font-weight: normal; font-style: normal; white-space: pre-wrap; }
a img { border: 0px; }body {font-family: Tahoma;font-size: 12pt;}
.plain pre, .plain tt {font-family: Tahoma;font-size: 12pt;}</STYLE>
</HEAD>
<BODY>Hey.</BODY></HTML>", $m->getHtmlbody());
        assertEquals(0, count($m->getParsedAttachments()));
        assertEquals(Message::TYPE_OTHER, $m->getType());
        assertEquals('FDv2', $m->getSourceheader());

        # Save it
        list($id, $already) = $m->save();
        assertNotNull($id);

        # Read it back
        unset($m);
        $m = new Message($this->dbhr, $this->dbhm, $id);
        assertEquals('Basic test', $m->getSubject());
        assertEquals('Basic test', $m->getHeader('subject'));
        assertEquals($t, $m->getFromname());
        assertEquals('test@test.com', $m->getFromaddr());
        assertEquals('Hey.', $m->getTextbody());
        assertEquals('from@test.com', $m->getEnvelopefrom());
        assertEquals('to@test.com', $m->getEnvelopeto());
        assertEquals("<HTML><HEAD>
<STYLE id=eMClientCss>
blockquote.cite { margin-left: 5px; margin-right: 0px; padding-left: 10px; padding-right:0px; border-left: 1px solid #cccccc }
blockquote.cite2 {margin-left: 5px; margin-right: 0px; padding-left: 10px; padding-right:0px; border-left: 1px solid #cccccc; margin-top: 3px; padding-top: 0px; }
.plain pre, .plain tt { font-family: monospace; font-size: 100%; font-weight: normal; font-style: normal; white-space: pre-wrap; }
a img { border: 0px; }body {font-family: Tahoma;font-size: 12pt;}
.plain pre, .plain tt {font-family: Tahoma;font-size: 12pt;}</STYLE>
</HEAD>
<BODY>Hey.</BODY></HTML>", $m->getHtmlbody());
        $m->delete();

        error_log(__METHOD__ . " end");
    }

    public function testAttachment() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/attachment');
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('MessageMaker', $m->getSourceheader());

        # Check the parsed attachments
        $atts = $m->getParsedAttachments();
        assertEquals(2, count($atts));
        assertEquals('g4g220x194.png', $atts[0]->getFilename());
        assertEquals('image/png', $atts[0]->getContentType());
        assertEquals('g4g160.png', $atts[1]->getFilename());
        assertEquals('image/png', $atts[1]->getContentType());

        # Save it
        list($id, $already) = $m->save();
        assertNotNull($id);

        # Check the saved attachments
        $atts = $m->getAttachments();
        assertEquals('image/png', $atts[0]->getContentType());
        assertEquals(7975, strlen($atts[0]->getData()));
        assertEquals('image/png', $atts[1]->getContentType());
        assertEquals(9695, strlen($atts[1]->getData()));

        # Check the returned attachments
        $atts = $m->getPublic();
        assertEquals(2, count($atts['attachments']));

        $m->delete();

        error_log(__METHOD__ . " end");
    }

    public function testEmbedded() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/inlinephoto');
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);

        # Check the parsed inline images
        $imgs = $m->getInlineimgs();
        assertEquals(2, count($imgs));

        # Save it and check they show up as attachments
        list($id, $already) = $m->save();
        $atts = Attachment::getById($this->dbhr, $this->dbhm, $id);
        assertEquals(2, count($atts));

        $m->delete();

        # Test invalid embedded image
        $msg = str_replace("https://www.google.co.uk/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png", "http://google.com", $msg);
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);

        # Check the parsed inline images - should be none
        $imgs = $m->getInlineimgs();
        assertEquals(0, count($imgs));

        # Save it and check they don't show up as attachments
        list($id, $already) = $m->save();
        $atts = Attachment::getById($this->dbhr, $this->dbhm, $id);
        assertEquals(0, count($atts));

        error_log(__METHOD__ . " end");
    }

    public function testPending() {
        error_log(__METHOD__);

        $msg = file_get_contents('msgs/approve');
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_PENDING, 'from@test.com', 'to@test.com', $msg);

        assertEquals('Test pending', $m->getSubject());
        assertEquals('Test User', $m->getFromname());
        assertEquals('test@ilovefreegle.org', $m->getFromaddr());
        assertEquals('Test', $m->getTextbody());
        assertEquals('from@test.com', $m->getEnvelopefrom());
        assertEquals('to@test.com', $m->getEnvelopeto());
        assertEquals('FDv2', $m->getSourceheader());
        assertEquals('freegleplayground-accept-ow3u5p5maxnf4tpmee4hjnr0wsla@yahoogroups.com', $m->getYahooapprove());
        assertEquals('freegleplayground-reject-ow3u5p5maxnf4tpmee4hjnr0wsla@yahoogroups.com', $m->getYahooreject());

        $m->delete();

        error_log(__METHOD__ . " end");
    }

    public function testTN() {
        error_log(__METHOD__);

        $msg = $this->unique(file_get_contents('msgs/tn'));
        $m = new Message($this->dbhr, $this->dbhm);
        $m->parse(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg);
        assertEquals('20065945', $m->getTnpostid());

        # Save it
        list($id, $already) = $m->save();
        assertNotNull($id);

        $m->delete();

        error_log(__METHOD__ . " end");
    }

    public function testType() {
        error_log(__METHOD__);

        assertEquals(Message::TYPE_OFFER, Message::determineType('OFFER: item (location)'));
        assertEquals(Message::TYPE_WANTED, Message::determineType('[Group]WANTED: item'));

        error_log(__METHOD__ . " end");
    }
}

