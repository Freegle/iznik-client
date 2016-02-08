<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/db.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class dbTest extends IznikTestCase {
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

        $this->dbhm->exec('DROP TABLE IF EXISTS test;');
        $rc = $this->dbhm->exec('CREATE TABLE `test` (`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
        assertEquals(0, $rc);
        $rc = $this->dbhm->exec('ALTER TABLE  `test` ADD  `val` INT NOT NULL ;');
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

        $this->dbhm->setErrorLog(TRUE);
        $tables = $this->dbhm->retryQuery('SHOW COLUMNS FROM test;')->fetchAll();
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
        assertTrue($this->dbhm->inTransaction());

        $rc = $this->dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        assertGreaterThan(0, $this->dbhm->lastInsertId());

        $tables = $this->dbhm->query('SHOW COLUMNS FROM test;')->fetchAll();
        assertEquals('id', $tables[0]['Field']);

        $rc = $this->dbhm->rollBack();
        assertTrue($rc);
        assertFalse($this->dbhm->inTransaction());

        $counts = $this->dbhm->preQuery("SELECT COUNT(*) AS count FROM test;");
        assertEquals(0, $counts[0]['count']);

        $rc = $this->dbhm->beginTransaction();
        assertTrue($this->dbhm->inTransaction());

        $rc = $this->dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        assertGreaterThan(0, $this->dbhm->lastInsertId());

        $tables = $this->dbhm->query('SHOW COLUMNS FROM test;')->fetchAll();
        assertEquals('id', $tables[0]['Field']);

        $rc = $this->dbhm->commit();
        assertTrue($rc);
        assertFalse($this->dbhm->inTransaction());

        $counts = $this->dbhm->preQuery("SELECT COUNT(*) AS count FROM test;");
        assertEquals(1, $counts[0]['count']);

        error_log(__METHOD__ . " end");
    }

    public function testBackground() {
        error_log(__METHOD__);

        # Test creation of the Pheanstalk.
        $this->dbhm->background('INSERT INTO test VALUES ();');

        # Mock the put to work.
        $mock = $this->getMockBuilder('Pheanstalk\Pheanstalk')
            ->disableOriginalConstructor()
            ->setMethods(array('put'))
            ->getMock();
        $mock->method('put')->willReturn(true);
        $this->dbhm->setPheanstalk($mock);
        $this->dbhm->background('INSERT INTO test VALUES ();');

        # Mock the put to fail.
        $mock->method('put')->will($this->throwException(new Exception()));
        $this->dbhm->background('INSERT INTO test VALUES ();');

        error_log(__METHOD__ . " end");
    }

    public function exceptionUntil() {
        error_log("exceptionUntil count " . $this->count);
        $this->count--;
        if ($this->count > 0) {
            error_log("Exception");
            throw new Exception('Faked deadlock exception');
        } else {
            error_log("No exception");
            return TRUE;
        }
    }

    public function falseAfter() {
        error_log("falseAfter count " . $this->count);
        if ($this->count == 0) {
            error_log("false");
            return(false);
        } else {
            $this->count--;
            return 1;
        }
    }

    public function falseUntil() {
        error_log("falseUntil count " . $this->count);
        if ($this->count == 0) {
            error_log("false");
            return(true);
        } else {
            $this->count--;
            return(false);
        }
    }

    public function testQueryRetries() {
        error_log(__METHOD__);

        # We mock up the query to throw an exception, to test retries.
        #
        # First a non-deadlock exception
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentQuery'))
            ->getMock();
        $mock->method('parentQuery')->will($this->throwException(new Exception()));

        $worked = false;

        try {
            $mock->retryQuery('SHOW COLUMNS FROM test;');
        } catch (DBException $e) {
            $worked = true;
            assertContains('Non-deadlock', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that never gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentQuery'))
            ->getMock();
        $mock->method('parentQuery')->will($this->throwException(new Exception('Faked deadlock exception')));
        $worked = false;

        try {
            $mock->retryQuery('SHOW COLUMNS FROM test;');
        } catch (DBException $e) {
            $worked = true;
            assertEquals('Unexpected database error Faked deadlock exception', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentQuery'))
            ->getMock();
        $this->count = 5;
        $mock->method('parentQuery')->will($this->returnCallback(function() {
            return($this->exceptionUntil());
        }));
        $worked = false;

        $mock->retryQuery('SHOW COLUMNS FROM test;');

        # Now a failure in the return code
        error_log("query returns false");
        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ), TRUE))
            ->setMethods(array('parentQuery', 'errorInfo'))
            ->getMock();
        $this->count = 5;
        $mock->method('parentQuery')->will($this->returnCallback(function() {
            return($this->falseUntil());
        }));
        $mock->method('errorInfo')->willReturn('Test server has gone away');

        $worked = false;

        $mock->retryQuery('SHOW COLUMNS FROM test;');

        error_log(__METHOD__ . " end");
    }

    public function testExecRetries() {
        error_log(__METHOD__);

        # We mock up the query to throw an exception, to test retries.
        #
        # First a non-deadlock exception
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentExec'))
            ->getMock();
        $mock->method('parentExec')->will($this->throwException(new Exception()));

        $worked = false;

        try {
            $mock->retryExec('INSERT INTO test VALUES ();');
        } catch (DBException $e) {
            $worked = true;
            assertContains('Non-deadlock', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that never gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentExec'))
            ->getMock();
        $mock->method('parentExec')->will($this->throwException(new Exception('Faked deadlock exception')));
        $worked = false;

        try {
            $mock->retryExec('INSERT INTO test VALUES ();');
        } catch (DBException $e) {
            $worked = true;
            assertEquals('Unexpected database error Faked deadlock exception', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->disableOriginalConstructor()
            ->setMethods(array('parentExec'))
            ->getMock();
        $this->count = 5;
        $mock->method('parentExec')->will($this->returnCallback(function() {
            return($this->exceptionUntil());
        }));

        $mock->retryExec('INSERT INTO test VALUES ();');

        # Now a failure in the return code
        error_log("query returns false");
        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ), TRUE))
            ->setMethods(array('parentExec', 'errorInfo'))
            ->getMock();
        $this->count = 5;
        $mock->method('parentExec')->will($this->returnCallback(function() {
            return($this->falseUntil());
        }));
        $mock->method('errorInfo')->willReturn('Test server has gone away');

        $mock->retryExec('INSERT INTO test VALUES ();');

        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ), TRUE))
            ->setMethods(array('executeStatement', 'getErrorInfo'))
            ->getMock();
        $this->count = 2;
        $mock->method('executeStatement')->will($this->returnCallback(function() {
            return($this->falseUntil());
        }));
        error_log("Test gone away");
        $mock->method('getErrorInfo')->willReturn('Test server has gone away');
        $mock->preExec('INSERT INTO test VALUES ();');

        error_log(__METHOD__ . " end");
    }

    public function testTransactionFailed() {
        error_log(__METHOD__);

        # We get partway through a transaction, then kill it off to provoke a commit failure.  This tests that
        # we notice if the server dies during a transaction; PDO is suspect in this area.

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        $dbhm = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => FALSE
        ), FALSE, $this->dbhr);

        $dbhm->setTries(0);
        $rc = $dbhm->beginTransaction();
        assertTrue($rc);
        $rc = $dbhm->exec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);

        $id = $dbhm->lastInsertId();

        $ps = $dbhm->query("SELECT CONNECTION_ID() AS connid;")->fetchAll();
        $connid = $ps[0]['connid'];
        error_log("ConnID is $connid");

        $this->dbhr->exec("KILL $connid;");

        try {
            error_log("Commit first");
            $rc = $dbhm->commit();
            assertFalse($rc);
        } catch (Exception $e) {
            # Don't expect an exception
            assertFalse(TRUE);
        }

        $ids = $this->dbhr->query("SELECT * FROM test WHERE id = $id;");
        foreach ($ids as $id) {
            # Shouldn't be there.
            assertFalse(TRUE);
        }

        error_log(__METHOD__ . " end");
    }

    # Oddly, the constructor doesn't get covered, so call it again.
    public function testConstruct() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        assertNotNull($this->dbhm->__construct($dsn, $dbconfig['user'], $dbconfig['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ), TRUE));

        assertEquals(3, count($this->dbhm->errorInfo()));

        error_log(__METHOD__ . " end");
    }

    public function testPrex() {
        error_log(__METHOD__);

        $rc = $this->dbhm->preExec('INSERT INTO test VALUES ();');
        assertEquals(1, $rc);
        $ids = $this->dbhm->preQuery('SELECT * FROM test WHERE id > ?;', array(0));
        assertEquals(1, count($ids));

        error_log(__METHOD__ . " end");
    }

    public function prepareUntil() {
        error_log("prepareUntil count " . $this->count);
        $this->count--;
        if ($this->count > 0) {
            error_log("Exception");
            throw new Exception('Faked deadlock exception');
        } else {
            error_log("No exception");
            return $this->dbhm->parentPrepare($this->sql);
        }
    }

    public function testPrexRetries() {
        error_log(__METHOD__);

        $dbconfig = array (
            'host' => '127.0.0.1',
            'user' => SQLUSER,
            'pass' => SQLPASSWORD,
            'database' => SQLDB
        );

        $dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

        # We mock up the query to throw an exception, to test retries.
        #
        # First a non-deadlock exception
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => FALSE
            ), TRUE))
            ->setMethods(array('parentPrepare'))
            ->getMock();
        $mock->method('parentPrepare')->will($this->throwException(new Exception()));

        $worked = false;

        try {
            $mock->preQuery('SHOW COLUMNS FROM test;');
        } catch (DBException $e) {
            $worked = true;
            assertContains('Non-deadlock', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that never gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => FALSE
            ), TRUE))
            ->setMethods(array('parentPrepare'))
            ->getMock();
        $mock->method('parentPrepare')->will($this->throwException(new Exception('Faked deadlock exception')));
        $worked = false;

        try {
            $mock->preQuery('SHOW COLUMNS FROM test;');
        } catch (DBException $e) {
            $worked = true;
            assertContains('Unexpected database error Faked deadlock exception', $e->getMessage());
        }
        assertTrue($worked);

        # Now a deadlock that gets resolved
        $mock = $this->getMockBuilder('LoggedPDO')
            ->setConstructorArgs(array($dsn, $dbconfig['user'], $dbconfig['pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => FALSE
            ), TRUE))
            ->setMethods(array('parentPrepare'))
            ->getMock();
        $this->count = 5;
        $this->sql = 'SHOW COLUMNS FROM test;';
        $mock->method('parentPrepare')->will($this->returnCallback(function() {
            return($this->prepareUntil());
        }));

        $mock->preQuery($this->sql);

        error_log(__METHOD__ . " end");
    }
}

