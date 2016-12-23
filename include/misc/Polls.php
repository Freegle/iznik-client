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

    public function create($name, $active, $template) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO polls (`name`, `active`, `template`) VALUES (?,?,?);", [
            $name, $active, $template
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'polls', 'poll', $this->publicatts);
        }

        return($id);
    }

    public function getForUser($userid) {
        # Get first one we've not replied to.
        $sql = "SELECT id FROM polls LEFT JOIN polls_users ON polls.id = polls_users.pollid AND userid = ? WHERE polls_users.pollid IS NULL ORDER BY polls.date DESC LIMIT 1;";
        $polls = $this->dbhr->preQuery($sql, [ $userid ]);

        return(count($polls) == 0 ? NULL : $polls[0]['id']);
    }

    public function response($userid, $response) {
        $this->dbhm->preExec("REPLACE INTO polls_users (pollid, userid, response) VALUES (?, ?, ?);", [
            $this->id,
            $userid,
            $response
        ]);
    }
}

