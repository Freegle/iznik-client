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

        $_SERVER['REQUEST_URI'] = '/group/FreeglePlayground';
        $this->expectOutputRegex('/.*<meta property="og:title" content="Freegle Playground"\/>./');
        include(IZNIK_BASE . '/include/misc/pageheader.php');

        $msgs = $this->dbhr->preQuery("SELECT messages.id, subject FROM messages INNER JOIN messages_attachments ON messages.id = messages_attachments.msgid ORDER BY arrival DESC LIMIT 1;");
        $id = $msgs[0]['id'];
        error_log("Test with message $id");
        $_SERVER['REQUEST_URI'] = "/message/$id";
        $re = '/.*<meta property="og:title" content="' . preg_quote($msgs[0]['subject']) . '"\/>./';
        error_log($re);
        $this->expectOutputRegex($re);
        include(IZNIK_BASE . '/include/misc/pageheader.php');

        error_log(__METHOD__ . " end");
    }
}

