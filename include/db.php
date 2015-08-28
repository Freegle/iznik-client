<?php

use Pheanstalk\Pheanstalk;
require_once('config.php');

# Everyone has a custom DB class.  We have ours for two reasons:
# 1) Percona clustering.  That can cause operations to fail due to conflict with other servers.
#    In that case we retry a few times here, and then if that doesn't work - which it may not if we
#    are inside a transaction - then we throw an exception which will cause us to retry the whole API
#    call from scratch.
# 2) Logging.  We keep a log of all SQL operations for debugging purposes.
#
# We use aggregation rather than extension because otherwise we hit issues with PHPUnit, which finds
# it hard to mock PDOs.

$dbconfig = array (
    'host' => 'localhost',
    'user' => SQLUSER,
    'pass' => SQLPASSWORD,
    'database' => SQLDB
);

class DBException extends Exception
{
}

class LoggedPDO {

    protected $_db;
    private $log = '';
    private $tries = 10;
    private $lastLogInsert = NULL;
    private $lastInsert = NULL;
    private $transactionStart = NULL;
    private $dbwaittime = 0;
    private $pheanstalk = NULL;
    private $readconn;

    /**
     * @param LoggedPDO $readconn
     */
    public function setReadconn($readconn)
    {
        $this->readconn = $readconn;
    }

    /**
     * @param null $pheanstalk
     */
    public function setPheanstalk($pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;
    }

    public function __construct($dsn, $username, $password, $options, $readonly = FALSE, LoggedPDO $readconn = NULL)
    {
        $start = microtime(true);
        $this->_db = new PDO($dsn, $username, $password);
        $this->dbwaittime += microtime(true) - $start;

        $this->readonly = $readonly;
        $this->readconn = $readconn;

        return $this;
    }

    public function getWaitTime() {
        return $this->dbwaittime;
    }

    private function maybeLog($sql, $ret, $duration) {
        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
        $timestamp = $d->format("Y-m-d H:i:s.u");

        if ($this->inTransaction()) {
            # Batch it up - we'll log after the commit/rollback.
            $str = $timestamp . " " . number_format($duration, 6) . " " . var_export($ret, true) . " $sql\n";
            $this->log .= $str;
        } else {
            # We don't log outside a transaction - it's not important enough for the perf hit.
        }
    }

    # Our most commonly used method is a combine prepare and execute, wrapped in
    # a retry.  This is SQL injection safe and handles Percona failures.
    public function preExec($sql, $params = NULL, $log = TRUE) {
        return($this->prex($sql, $params, FALSE, $log));
    }

    public function preQuery($sql, $params = NULL, $log = TRUE) {
        return($this->prex($sql, $params, TRUE, $log));
    }

    public function parentPrepare($sql) {
        return($this->_db->prepare($sql));
    }

    private function prex($sql, $params = NULL, $select, $log) {
        $try = 0;
        $ret = NULL;
        $msg = '';
        $worked = false;
        $start = microtime(true);

        do {
            try {
                $sth = $this->parentPrepare($sql);
                $rc = $sth->execute($params);

                if (!$select) {
                    $this->lastInsert = $this->_db->lastInsertId();
                }

                if ($rc) {
                    # For selects we return all the rows found; for updates we return the return value.
                    $ret = $select ? $sth->fetchAll() : $rc;
                    $worked = true;

                    if ($log) {
                        $duration = microtime(true) - $start;
                        $this->maybeLog($sql, NULL, $duration);
                    }
                } else {
                    $msg = var_export($this->_db->errorInfo(), true);
                }

                $try++;
            } catch (Exception $e) {
                if (stripos($e->getMessage(), 'deadlock') !== FALSE) {
                    # It's a Percona deadlock - retry.
                    $try++;
                    $msg = $e->getMessage();
                } else {
                    $msg = "Non-deadlock DB Exception $sql " . $e->getMessage();
                    $try = $this->tries;
                }
            }
        } while (!$worked && $try < $this->tries);

        if ($worked && $try > 1) {
            error_log("prex succeeded after $try for $sql");
        } else if (!$worked)
            $this->giveUp($msg . " for $sql " . var_export($params, true) . " " . var_export($this->_db->errorInfo(), true));

        $this->dbwaittime += microtime(true) - $start;

        return($ret);
    }

    public function parentExec($sql) {
        return($this->_db->exec($sql));
    }

    function retryExec($sql) {
        $try = 0;
        $ret = NULL;
        $msg = '';
        $worked = false;
        $start = microtime(true);

        do {
            try {
                $ret = $this->parentExec($sql);
                $worked = true;
            } catch (Exception $e) {
                error_log("execption " . $e->getMessage());
                if (stripos($e->getMessage(), 'deadlock') !== FALSE) {
                    # It's a Percona deadlock - retry.
                    $try++;
                    $msg = $e->getMessage();
                } else {
                    $msg = "Non-deadlock DB Exception $sql " . $e->getMessage();
                    $try = $this->tries;
                }
            }
        } while (!$worked && $try < $this->tries);

        if ($worked && $try > 0) {
            error_log("retryExec succeeded after $try for $sql");
        } else if (!$worked)
            $this->giveUp($msg);

        $this->dbwaittime += microtime(true) - $start;

        return($ret);
    }

    public function parentQuery($sql) {
        return($this->_db->query($sql));
    }

    public function retryQuery($sql) {
        $try = 0;
        $ret = NULL;
        $worked = false;
        $start = microtime(true);
        $msg = '';

        do {
            try {
                $ret = $this->parentQuery($sql);
                $worked = true;
            } catch (Exception $e) {
                if (stripos($e->getMessage(), 'deadlock') !== FALSE) {
                    # Retry.
                    $try++;
                    $msg = $e->getMessage();
                } else {
                    $msg = "Non-deadlock DB Exception $sql " . $e->getMessage();
                    $try = $this->tries;
                }
            }
        } while (!$worked && $try < $this->tries);

        if ($worked && $try > 0) {
            error_log("retryQuery succeeded after $try");
        } else if (!$worked)
            $this->giveUp($msg); // No brace because of coverage oddity

        #error_log("Query took " . (microtime(true) - $start) . " $sql" );
        $this->dbwaittime += microtime(true) - $start;

        return($ret);
    }

    private function reallyLog($sql, $ret, $duration) {
        # This actually logs our SQL.  For now we log into a table in SQL itself, though later we might
        # move to a better logging system.  If we do, remember that we're also using this as a way
        # of checking that the commit worked - see below.
        $ret = $ret ? $ret : 'NULL';
        $id = isset($_SESSION) && pres('uid', $_SESSION) ? $_SESSION['uid'] : 'NULL';
        $sql2 = "INSERT INTO logs_sql (`result`, `duration`, `statement`, `user`) VALUES ($ret, $duration, " . $this->quote($sql) . ", $id);";
        $ret = $this->retryExec($sql2);

        if (!$ret)
            throw new DBException('Failed to log SQL'); // No brace because of coverage oddity

        # We can't use PHP's lastInsertId because it doesn't work well if used multiple times within a transaction.
        $sql = "SELECT LAST_INSERT_ID() as lastid;";
        $lasts = $this->retryQuery($sql);
        foreach ($lasts as $last) {
            $this->lastLogInsert = $last['lastid'];
        }
    }

    public function inTransaction() {
        return($this->_db->inTransaction()) ;
    }

    public function quote($str) {
        return($this->_db->quote($str));
    }

    public function errorInfo() {
        return($this->_db->errorInfo());
    }

    public function rollBack() {
        return($this->_db->rollBack());
    }

    public function beginTransaction() {
        $this->transactionStart = microtime(true);
        $ret = $this->_db->beginTransaction ();

        $this->maybeLog("START TRANSACTION;", $ret, microtime(true) - $this->transactionStart);
        $this->dbwaittime += microtime(true) - $this->transactionStart;

        return($ret);
    }

    function commit() {
        # We log the SQL operations involved in this transaction within the transaction.
        # That means we can check after the commit whether it worked.  We do this because the
        # PDO commit call is not trustworthy - it can return true even if the commit fails.
        # See for example https://bugs.php.net/bug.php?id=66528
        #
        # There is a suggestion that doing query('COMMIT') works, but we've not tried that,
        # and it is good to be very careful about whether transactions have worked, even if
        # there's a perf cost.

        # We log the commit with a success because if it doesn't work the log isn't there.
        $this->reallyLog($this->log, 0, microtime(true) - $this->transactionStart);
        $this->log = '';

        $time = microtime(true);
        $this->_db->commit();
        $this->dbwaittime += microtime(true) - $time;

        # Now check whether this log exists.  If it did, we know for sure that the commit
        # took effect.  Do this from a separate read connection which can't be within
        # the context of our transaction (which, if the commit failed, might still be active
        # and therefore might return uncommitted data).
        $sql = "SELECT id FROM logs_sql WHERE id = {$this->lastLogInsert};";
        $logs = $this->readconn->retryQuery($sql);
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($logs as $log) {
            # It does - the commit worked.
            #error_log("Found log - commit worked");
            $this->reallyLog("COMMIT;", 0, microtime(true) - $time);
            return true;
        }

        # It doesn't; the commit failed.
        error_log("No log id " . $this->lastLogInsert . " - commit failed");
        throw new DBException('Commit failed - log not found', 1);
    }

    public function exec ($sql, $log = true)    {
        $time = microtime(true);
        $ret = $this->retryExec($sql);
        $this->lastInsert = $this->_db->lastInsertId();

        if ($log) {
            $this->maybeLog($sql, $ret, microtime(true) - $time);
        }

        return($ret);
    }

    public function query($sql, $log = true) {
        $time = microtime(true);
        $ret = $this->retryQuery($sql);
        $duration = microtime(true) - $time;

        if ($log && $this->inTransaction()) {
            # We only log read operations inside a transaction - it's pretty much free to do so given
            # that we buffer them to a string and will be doing a commit later.
            $this->maybeLog($sql, NULL, $duration);
        }

        return($ret);
    }

    public function lastInsertId() {
        return($this->lastInsert);
    }

    public function background($sql) {
        try {
            # This SQL needs executing, but not in the foreground, and it's not the end of the
            # world if we drop it, or duplicate it.
            if (!$this->pheanstalk) {
                $this->pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
            }

            $rc = $this->pheanstalk->put(json_encode(array(
                'type' => 'sql',
                'queued' => time(),
                'sql' => $sql,
                'ttr' => 300
            )));
        } catch (Exception $e) {
            error_log("Beanstalk exception " . $e->getMessage());
            $rc = 0;
        }

        if (!$rc) {
            error_log("Couldn't background $sql");
        }
    }

    private function giveUp($msg) {
        error_log("DB: give up $msg");
        throw new DBException("Unexpected database error $msg", 999);
    }
}

# We have two handles; one for reads, and one for writes, which we need because we might have a complex
# DB architecture where the master is split out from a replicated copy.
$dsn = "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8";

$dbhr = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    // PDO::ATTR_PERSISTENT => true, // Persistent connections seem to result in a leak - show status like 'Threads%'; shows an increasing number
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
), TRUE);

$dbhm = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    // PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
), FALSE, $dbhr);
