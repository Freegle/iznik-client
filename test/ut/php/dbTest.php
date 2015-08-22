<?php

require_once 'IznikTest.php';
require_once BASE_DIR . '/include/db.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class dbTest extends IznikTest {
    /** @var $dbhr LoggedPDO */
    /** @var $dbhm LoggedPDO */
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        assertNotNull($this->dbhr);
        assertNotNull($this->dbhm);

        $rc = $this->dbhm->exec('CREATE TABLE `test` (`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
        assertEquals(0, $rc);
    }

    protected function tearDown() {
        $rc = $this->dbhm->exec('DROP TABLE test;');
        assertEquals(0, $rc);

        parent::tearDown ();
    }

    public function __construct() {
    }

    public function testBasic() {
        error_log(__METHOD__);

        $tables = $this->dbhm->query('SHOW COLUMNS FROM test;')->fetchAll();
        assertEquals('id', $tables[0]['Field']);
        assertGreaterThan(0, $this->dbhm->getWaitTime());

        error_log(__METHOD__ . " end");
    }

    public function testInsert() {
        error_log(__METHOD__);

        $rc = $this->dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        $id1 = $this->dbhm->lastInsertId();
        $rc = $this->dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        $id2 = $this->dbhm->lastInsertId();
        assertGreaterThan($id1, $id2);

        error_log(__METHOD__ . " end");
    }

    public function testTransaction() {
        error_log(__METHOD__);

        $rc = $this->dbhm->beginTransaction();

        $rc = $this->dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        assertGreaterThan(0, $this->dbhm->lastInsertId());

        $rc = $this->dbhm->commit();
        assertTrue($rc);

        error_log(__METHOD__ . " end");
    }
}

