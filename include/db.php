<?php

use Pheanstalk\Pheanstalk;
require_once('config.php');
require_once(IZNIK_BASE . '/include/utils.php');

# Everyone has a custom DB class.  We have ours primarily for Percona clustering.  That can cause operations
# to fail due to conflict with other servers. In that case we retry a few times here, and then if that doesn't
# work - which it may not if we are inside a transaction - then we throw an exception which will cause us to
# retry the whole API call from scratch.
#
# We also do some caching of queries in this class, using Redis.   Queries made on the $dbhr handle can use this cache,
# which is no more than a certain period out of date, and is invalidated if a modification operation is made on
# this session.  That means that if we make a modification in our session, we'll see the most up to date data, but
# otherwise we can use cached data which will be no more than a bit out of date. This is very beneficial for
# performance since it keeps the many queries local to the application server, and reduces the load on the DB servers.
# TODO We could cache at a user, group, message level, which would enable us to do more intelligent cache
# invalidation than this.
#
# We use aggregation rather than extension because otherwise we hit issues with PHPUnit, which finds
# it hard to mock PDOs.

$dbconfig = array (
    'host' => SQLHOST,
    'port' => SQLPORT,
    'user' => SQLUSER,
    'pass' => SQLPASSWORD,
    'database' => SQLDB
);

class DBException extends Exception
{
}

class DBResults implements Iterator {
    # This is our own class which we can construct from a PDOStatement but still be able to serialise.
    private $position = 0;

    private $querying = false;

    public function __construct($results, $sql, $expiry) {
        $this->sql = $sql;
        $this->expiry = $expiry;
        $this->time = microtime(TRUE);

        if ($results) {
            $this->array = $results;
        } else {
            $this->array = [];
        }
    }

    public function checkValid($lastmodop) {
        # We are passed the time of the last mod op on this session, if any.  If that is later than this
        # time, we assume our cache is invalid.
        #
        # If we've not had any mod ops on this session then assume all our results are invalid.  This is
        # because we might (for example) create a user with an email address, cache a search for that email,
        # delete the user, delete the session, start a new session, search for that email address and get
        # a result back, which would be wrong.  Ok, that's not really an example, it's what the UT does
        # all the time.
        $valid = $lastmodop && floatval($lastmodop) < $this->time;
        #error_log("Check valid " . microtime(TRUE) . " vs " . $this->time . " and $lastmodop = $valid");
        return($valid);
    }

    public function checkExpired() {
        #error_log("Check expired " . microtime(TRUE) . " vs " . $this->time);
        return(time() - $this->time > LoggedPDO::CACHE_EXPIRY * 0.75);
    }

    public function checkQuerying() {
        return $this->querying;
    }

    public function setQuerying($b = true) {
        $this->querying = $b;
    }

    public function getExpiry() {
        return $this->expiry;
    }

    public function getSQL() {
        return($this->sql);
    }

    public function fetchAll() {
        return($this->array);
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->array[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->array[$this->position]);
    }
}

class LoggedPDO {

    protected $_db;
    private $inTransaction = FALSE;
    private $tries = 10;
    public  $errorLog = FALSE;
    private $lastInsert = NULL;
    private $transactionStart = NULL;
    private $dbwaittime = 0;
    private $cachetime = 0;
    private $cachequeries = 0;
    private $cachehits = 0;
    private $pheanstalk = NULL;
    private $readconn;
    private $querying = false;

    const DUPLICATE_KEY = 1062;
    const MAX_LOG_SIZE = 100000;
    const CACHE_MAX_SIZE = 50000;
    const CACHE_EXPIRY = 45;  // We expect session polls every 30s, so this should cover that.

    /**
     * @param int $tries
     */
    public function setTries($tries)
    {
        $this->tries = $tries;
    }

    /**
     * @param boolean $errorLog
     */
    public function setErrorLog($errorLog)
    {
        $this->errorLog = $errorLog;
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
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->readonly = $readonly;
        $this->readconn = $readconn;

        $this->_db = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $this->dbwaittime += microtime(true) - $start;

        $this->cache = NULL;

        return $this;
    }

    private function getRedis() {
        if (!$this->cache) {
            $this->cache = new Redis();
            $this->cache->pconnect(REDIS_CONNECT);
        }

        return($this->cache);
    }
    public function getWaitTime() {
        return $this->dbwaittime;
    }

    public function getCacheTime() {
        return $this->cachetime;
    }

    public function getCacheQueries() {
        return $this->cachequeries;
    }

    public function getCacheHits() {
        return $this->cachehits;
    }

    # Our most commonly used method is a combine prepare and execute, wrapped in
    # a retry.  This is SQL injection safe and handles Percona failures.
    public function preExec($sql, $params = NULL, $log = TRUE) {
        return($this->prex($sql, $params, FALSE, $log));
    }

    public function preQuery($sql, $params = NULL, $log = FALSE) {
        return($this->prex($sql, $params, TRUE, $log));
    }

    public function parentPrepare($sql) {
        return($this->_db->prepare($sql));
    }

    public function getErrorInfo($sth) {
        # Split into function for UT
        return($sth->errorInfo());
    }

    public function executeStatement($sth, $params) {
        # Split into function for UT
        return($sth->execute($params));
    }

    private function cacheKey($sql, $params) {
        return("sql" . md5($sql . var_export($params, TRUE)));
    }

    private function sessionKey() {
        return("sqlsess" . session_id());
    }

    private function prex($sql, $params = NULL, $select, $log) {
        $try = 0;
        $ret = NULL;
        $msg = '';
        $worked = false;
        $start = microtime(true);

        do {
            $gotcache = FALSE;
            $cachekey = $this->cacheKey($sql, $params);

            if (preg_match('/INSERT|REPLACE|UPDATE|DELETE/', $sql)) {
                # Ok, this is a modification op.  Zap our SQL cache.
                #error_log("Invalidate cache with $sql");
                $rc = $this->getRedis()->setex($this->sessionKey(), LoggedPDO::CACHE_EXPIRY, microtime(TRUE));
            } else if ($this->readonly) {
                # This is a readonly connection, so it's acceptable for the data to be slightly out of date.  We can
                # query our redis cache.
                $this->cachequeries++;

                $cachestart = microtime(true);
                #error_log("Read only query $cachekey $sql");
                $mget = $this->getRedis()->mget([$cachekey, $this->sessionKey()]);
                #error_log("Got keys " . var_export($mget, TRUE));

                if ($mget[0]) {
                    $cached = unserialize(gzuncompress($mget[0]));
                    #error_log("Got from cache");
                    $cachedsql = $cached->getSQL();
                    #error_log("Got cached SQL $cachedsql");

                    if ($sql == $cachedsql) {
                        #error_log("Matches");
                        #error_log("Found $cachekey in cache, expired?" .  $cached->checkExpired());

                        # We must check whether this cache entry has been invalidated by a subsequent modification op
                        # within this session.
                        if ($cached->checkValid($mget[1])) {
                            #error_log("Valid");
                            if (!$cached->checkExpired()) {
                                #error_log("Found valid entry in cache $cachekey");
                                $gotcache = TRUE;
                                $ret = $cached->fetchAll();
                                $this->cachehits++;
                            } else {
                                # Our cache entry is past its expiry time.
                                #error_log("Expired");
                                if ($cached->checkQuerying()) {
                                    # We are already updating our cache entry, in another request.  We will use this
                                    # until that has completed - otherwise we might have a car crash where many requests
                                    # all decide to refresh the same data at the same time.
                                    $gotcache = TRUE;
                                    $ret = $cached->fetchAll();
                                    $this->cachehits++;
                                    #error_log("Already updating cache entry $cachekey");
                                } else {
                                    # We are the first to notice that this has expired.  Take one for the team and
                                    # refresh it.
                                    #error_log("Refresh expired cache $cachekey");
                                    $cached->setQuerying(true);
                                    $tocache = gzcompress(serialize($cached));
                                    $cachestart = microtime(true);
                                    $rc = $this->getRedis()->setex($cachekey, LoggedPDO::CACHE_EXPIRY, $tocache);
                                    $this->cachetime += microtime(true) - $cachestart;
                                    #error_log("Time to refresh expired cache " . (microtime(true) - $cachestart));
                                    #if ($rc) {
                                    #    error_log("Return $rc replaced in cache $cachekey size " . strlen(serialize($cached)) . " $rc " . $this->cache->getLastError());
                                    #} else {
                                    #    error_log("Failed to store $rc " . $this->cache->getLastError());
                                    #}
                                }
                            }
                        }
                    }
                }

                #error_log("Time to query cache " . (microtime(true) - $cachestart));
                $this->cachetime += microtime(true) - $cachestart;
            }

            if (!$gotcache) {
                #error_log("Not got from cache $sql");
                try {
                    $sth = $this->parentPrepare($sql);
                    $rc = $this->executeStatement($sth, $params);

                    if (!$select) {
                        $this->lastInsert = $this->_db->lastInsertId();
                    }

                    if ($rc) {
                        # For selects we return all the rows found; for updates we return the return value.
                        $ret = $select ? $sth->fetchAll() : $rc;
                        $worked = true;
                        
                        if ($select) {
                            # Convert to our results to store in the cache.  We can store something in the cache
                            # even if this is not a readonly connection - once we've read it, we might as well
                            # have the most up to date value.
                            try {
                                $tocache = new DBResults($ret, $sql, LoggedPDO::CACHE_EXPIRY);
                            } catch (Exception $e) { error_log("Failed " . $e->getMessage());}

                            # Store this result in the cache
                            $tocache = gzcompress(serialize($tocache));
                            #error_log("Consider store $cachekey " . strlen($tocache));
                            if (strlen($tocache) < LoggedPDO::CACHE_MAX_SIZE) {
                                $cachestart = microtime(true);
                                $this->getRedis()->setex($cachekey, LoggedPDO::CACHE_EXPIRY, $tocache);
                                $this->cachetime += microtime(true) - $cachestart;
                            }
                        }

                        if ($log) {
                            $duration = microtime(true) - $start;
                        }
                    } else {
                        $msg = var_export($this->getErrorInfo($sth), true);
                        if (stripos($msg, 'has gone away') !== FALSE) {
                            # This can happen if we have issues with the DB, e.g. one server dies or the connection is
                            # timed out.  We re-open the connection and try again.
                            $try++;
                            $this->_db = NULL;
                            $this->_db = new PDO($this->dsn, $this->username, $this->password);
                        }
                    }

                    $try++;
                } catch (Exception $e) {
                    if (stripos($e->getMessage(), 'deadlock') !== FALSE) {
                        # It's a Percona deadlock - retry.
                        $try++;
                        $msg = $e->getMessage();
                    } else {
                        $msg = "Non-deadlock DB Exception " . $e->getMessage() . " $sql";
                        error_log($msg);
                        $try = $this->tries;
                    }
                }
            } else {
                #error_log("Got from cache");
                $worked = TRUE;
            }
        } while (!$worked && $try < $this->tries);

        if ($worked && $try > 1) {
            error_log("prex succeeded after $try for $sql");
        } else if (!$worked)
            $this->giveUp($msg . " for $sql " . var_export($params, true) . " " . var_export($this->_db->errorInfo(), true));

        $this->dbwaittime += microtime(true) - $start;

        if ($log && SQLLOG) {
            $mysqltime = date("Y-m-d H:i:s", time());
            $duration = microtime(true) - $start;
            $logret = $select ? count($ret) : ("$ret:" . $this->lastInsert);

            if (isset($_SESSION)) {
                $logparams = var_export($params, TRUE);
                $logparams = substr($logparams, 0, LoggedPDO::MAX_LOG_SIZE);
                $logsql = "INSERT INTO logs_sql (userid, date, duration, session, request, response) VALUES (" .
                    presdef('id', $_SESSION, 'NULL') .
                    ", '$mysqltime', $duration, " .
                    $this->quote(session_id()) . "," .
                    $this->quote($sql . ", " . $this->quote($logparams)) . "," .
                    $this->quote($logret) . ");";
                $this->background($logsql);
            }
        }

        if ($this->errorLog) {
            error_log(presdef('call',$_REQUEST, ''). " " . round(((microtime(true) - $start) * 1000), 2) . "ms for $sql " . var_export($params, TRUE));
        }

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

                if ($ret !== FALSE) {
                    $worked = true;

                    # This is a modification op, so clear our cache.
                    $this->getRedis()->setex($this->sessionKey(), LoggedPDO::CACHE_EXPIRY, microtime(TRUE));
                } else {
                    $msg = var_export($this->errorInfo(), true);
                    $try++;
                    if (stripos($msg, 'has gone away') !== FALSE) {
                        # This can happen if we have issues with the DB, e.g. one server dies or the connection is
                        # timed out.  We re-open the connection and try again.
                        $this->_db = NULL;
                        $this->_db = new PDO($this->dsn, $this->username, $this->password);
                    }
                }
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

                if ($ret !== FALSE) {
                    $worked = true;
                } else {
                    $try++;
                    $msg = var_export($this->errorInfo(), true);
                    if (stripos($msg, 'has gone away') !== FALSE) {
                        # This can happen if we have issues with the DB, e.g. one server dies or the connection is
                        # timed out.  We re-open the connection and try again.
                        $this->_db = NULL;
                        $this->_db = new PDO($this->dsn, $this->username, $this->password);
                    }
                }
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

    public function inTransaction() {
        return($this->inTransaction) ;
    }

    public function quote($str) {
        return($this->_db->quote($str));
    }

    public function errorInfo() {
        return($this->_db ? $this->_db->errorInfo() : 'No DB handle');
    }

    public function rollBack() {
        $this->inTransaction = FALSE;

        $time = microtime(true);
        $rc = $this->_db->rollBack();
        $duration = microtime(true) - $time;
        $mysqltime = date("Y-m-d H:i:s", time());

        if (SQLLOG) {
            $myid = defined('_SESSION') ? presdef('id', $_SESSION, 'NULL') : 'NULL';
            $logsql = "INSERT INTO logs_sql (userid, date, duration, session, request, response) VALUES ($myid, '$mysqltime', $duration, " . $this->quote(session_id()) . "," . $this->quote('ROLLBACK;') . "," . $this->quote($rc) . ");";
            $this->background($logsql);
        }

        return($rc);
    }

    public function beginTransaction() {
        $this->inTransaction = TRUE;
        $this->transactionStart = microtime(true);
        $ret = $this->_db->beginTransaction();
        $duration = microtime(true) - $this->transactionStart;
        $this->dbwaittime += $duration;

        if (SQLLOG) {
            $mysqltime = date("Y-m-d H:i:s", time());
            $myid = defined('_SESSION') ? presdef('id', $_SESSION, 'NULL') : 'NULL';
            $logsql = "INSERT INTO logs_sql (userid, date, duration, session, request, response) VALUES ($myid, '$mysqltime', $duration, " . $this->quote(session_id()) . "," . $this->quote('BEGIN TRANSACTION;') . "," . $this->quote($ret . ":" . $this->lastInsert) . ");";
            $this->background($logsql);
        }

        return($ret);
    }

    function commit() {
        $time = microtime(true);
        # PDO's commit() isn't reliable - it can return true
        $this->_db->query('COMMIT;');
        $rc = $this->_db->errorCode() == '0000';

        # ...but issue it anyway to get the states in sync
        $this->_db->commit();
        $duration = microtime(true) - $time;

        $this->dbwaittime += $duration;

        if (SQLLOG) {
            $mysqltime = date("Y-m-d H:i:s", time());
            $myid = defined('_SESSION') ? presdef('id', $_SESSION, 'NULL') : 'NULL';
            $logsql = "INSERT INTO logs_sql (userid, date, duration, session, request, response) VALUES ($myid, '$mysqltime', $duration, " . $this->quote(session_id()) . "," . $this->quote('COMMIT;') . "," . $this->quote($rc) . ");";
            $this->background($logsql);
        }

        $this->inTransaction = FALSE;

        return($rc);
    }

    public function exec ($sql, $log = true)    {
        $time = microtime(true);
        $ret = $this->retryExec($sql);
        $this->lastInsert = $this->_db->lastInsertId();

        if ($log && SQLLOG) {
            $mysqltime = date("Y-m-d H:i:s", time());
            $duration = microtime(true) - $time;
            $myid = defined('_SESSION') ? presdef('id', $_SESSION, 'NULL') : 'NULL';
            $logsql = "INSERT INTO logs_sql (userid, date, duration, session, request, response) VALUES ($myid, '$mysqltime', $duration, " . $this->quote(session_id()) . "," . $this->quote($sql) . "," . $this->quote($ret . ":" . $this->lastInsert) . ");";
            $this->background($logsql);
        }

        return($ret);
    }

    public function query($sql) {
        $ret = $this->retryQuery($sql);
        return($ret);
    }

    public function lastInsertId() {
        return($this->lastInsert);
    }

    public function background($sql) {
        $count = 0;
        do {
            $done = FALSE;
            try {
                # This SQL needs executing, but not in the foreground, and it's not the end of the
                # world if we drop it, or duplicate it.
                if (!$this->pheanstalk) {
                    $this->pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
                }

                $id = $this->pheanstalk->put(json_encode(array(
                    'type' => 'sql',
                    'queued' => time(),
                    'sql' => $sql,
                    'ttr' => 300
                )));
                #error_log("Backgroupd $id for $sql");
                $done = TRUE;
            } catch (Exception $e) {
                # Try again in case it's a temporary error.
                error_log("Beanstalk exception " . $e->getMessage() . " on sql of len " . strlen($sql));
                $this->pheanstalk = NULL;
                $count++;
            }
        } while (!$done && $count < 10);
    }

    private function giveUp($msg) {
        throw new DBException("Unexpected database error $msg", 999);
    }
}

# We have two handles; one for reads, and one for writes, which we need because we might have a complex
# DB architecture where the master is split out from a replicated copy.
#
# Don't use persistent connections as they don't play nice - PDO can use a connection which was already
# closed.  It's possible that our retrying would handle this ok, but we should only rely on that if
# we've tested it better and we need the perf gain.
$dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['database']};charset=utf8";

$dbhr = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
), TRUE);

$dbhm = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
), FALSE, $dbhr);
