<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/message/Attachment.php';
require_once IZNIK_BASE . '/include/user/User.php';


/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AttachmentTest extends IznikTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
    }

    public function __construct() {
    }

    public function testIdentify() {
        error_log(__METHOD__);

        $data = file_get_contents('images/chair.jpg');
        $a = new Attachment($this->dbhr, $this->dbhm);
        $attid = $a->create(NULL, 'image/jpeg', $data);
        assertNotNull($attid);

        $a = new Attachment($this->dbhr, $this->dbhm, $attid);

        $idents = $a->identify();
        error_log(var_export($idents, TRUE));
        assertEquals('chair', $idents[0]['name']);

        error_log(__METHOD__ . " end");
    }

    public function testArchive() {
        error_log(__METHOD__);

        $data = file_get_contents('images/chair.jpg');
        $a = new Attachment($this->dbhr, $this->dbhm);
        $attid = $a->create(NULL, 'image/jpeg', $data);
        assertNotNull($attid);

        # Fake an archive
        $a->archive();
        $dat2 = $a->getData();

        # TODO We're not archiving images yet, so this doesn't work.  But we are keeping the code.
        #assertEquals($data, $dat2);

        error_log(__METHOD__ . " end");
    }

    public function testHash() {
        error_log(__METHOD__);

        $data = file_get_contents('images/chair.jpg');
        $a = new Attachment($this->dbhr, $this->dbhm, NULL, Attachment::TYPE_GROUP);
        $attid1 = $a->create(NULL, 'image/jpeg', $data);
        assertNotNull($attid1);

        $data = file_get_contents('images/chair.jpg');
        $a = new Attachment($this->dbhr, $this->dbhm, NULL, Attachment::TYPE_GROUP);
        $attid2 = $a->create(NULL, 'image/jpeg', $data);
        assertNotNull($attid1);

        $a1 = new Attachment($this->dbhr, $this->dbhm, $attid1, Attachment::TYPE_GROUP);
        $a2 = new Attachment($this->dbhr, $this->dbhm, $attid2, Attachment::TYPE_GROUP);
        assertEquals($a1->getHash(), $a2->getHash());

        error_log(__METHOD__ . " end");
    }
}

