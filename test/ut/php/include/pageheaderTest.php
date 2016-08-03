<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once(UT_DIR . '/../../include/config.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class pageheaderTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testUser() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = USER_SITE;

        $_SERVER['REQUEST_URI'] = 'something';
        $this->expectOutputRegex('/.*<meta property="og:title" content="' . SITE_NAME . '"\/>./');
        $this->expectOutputRegex('/.*<script.*/');
        include(IZNIK_BASE . '/include/misc/pageheader.php');

        error_log(__METHOD__ . " end");
    }

    public function testGroup() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = USER_SITE;

        $_SERVER['REQUEST_URI'] = '/explore/FreeglePlayground';
        $this->expectOutputRegex('/.*<meta property="og:title" content="FreeglePlayground"\/>./');
        include(IZNIK_BASE . '/include/misc/pageheader.php');

        error_log(__METHOD__ . " end");
    }

    public function testMessage() {
        error_log(__METHOD__);

        $_SERVER['HTTP_HOST'] = USER_SITE;

        $msgs = $this->dbhr->preQuery("SELECT messages.id, subject FROM messages_attachments INNER JOIN messages ON messages.id = messages_attachments.msgid ORDER BY arrival ASC LIMIT 1;");
        $id = $msgs[0]['id'];
        error_log("Test with message $id");
        $_SERVER['REQUEST_URI'] = "/message/$id";
        $subj = $msgs[0]['subject'];
        $subj = preg_replace('/^\[.*?\]\s*/', '', $subj);
        $subj = preg_replace('/\[.*Attachment.*\]\s*/', '', $subj);        
        $re = '/.*<meta property="og:title" content="' . preg_quote($subj) . '"\/>./';
        error_log($re);
        $this->expectOutputRegex($re);
        include(IZNIK_BASE . '/include/misc/pageheader.php');

        error_log(__METHOD__ . " end");
    }
}

