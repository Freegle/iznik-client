<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class Polls extends Entity
{
    /** @var  $dbhm LoggedPDO */
    public $publicatts = [ 'id', 'name', 'active', 'template'];
    public $settableatts = [ 'name', 'active', 'template' ];
    var $event;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'polls', 'poll', $this->publicatts);
    }

    public function create($name, $active, $template, $logintype = NULL) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO polls (`name`, `active`, `template`, `logintype`) VALUES (?,?,?,?);", [
            $name, $active, $template, $logintype
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'polls', 'poll', $this->publicatts);
        }

        return($id);
    }

    public function getForUser($userid) {
        $ret = NULL;
        $lastid = NULL;

        do {
            # Get first one we've not replied to.
            $lastq = $lastid ? " AND polls.id > $lastid " : '';

            $sql = "SELECT id, logintype FROM polls LEFT JOIN polls_users ON polls.id = polls_users.pollid AND userid = ? WHERE (polls_users.pollid IS NULL OR response IS NULL) $lastq ORDER BY polls.date DESC LIMIT 1;";
            $polls = $this->dbhr->preQuery($sql, [ $userid ]);

            # Keep looking while we're still finding some.
            $cont = count($polls) > 0;

            if ($cont) {
                $lastid = $polls[0]['id'];

                # Can we return this one?
                $logintype = $polls[0]['logintype'];
                if ($logintype) {
                    # We need to check the login type against those for this user.
                    $u = User::get($this->dbhr, $this->dbhm, $userid);
                    $logins = $u->getLogins();

                    foreach ($logins as $login) {
                        if ($login['type'] == $logintype) {
                            $ret = $polls[0]['id'];
                        }
                    }
                } else {
                    # No need to check the login type, so this will do.
                    $ret = $polls[0]['id'];
                }
            }
        } while (!$ret && $cont);

        return($ret);
    }

    public function shown($userid) {
        $this->dbhm->preExec("INSERT INTO polls_users (pollid, userid) VALUES (?, ?) ON DUPLICATE KEY UPDATE shown = 1;", [
            $this->id,
            $userid
        ]);
    }

    public function response($userid, $response) {
        $this->dbhm->preExec("INSERT INTO polls_users (pollid, userid, response) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE response = ?;", [
            $this->id,
            $userid,
            $response,
            $response
        ]);
    }
}

