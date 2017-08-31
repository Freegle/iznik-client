<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class Schedule extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'created', 'agreed', 'schedule');
    var $settableatts = array('created', 'agreed', 'schedule');

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'schedules', 'schedule', $this->publicatts);
    }

    public function create($schedule) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO schedules (schedule) VALUES (?);", [
            json_encode($schedule)
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhm, $this->dbhm, $id, 'schedules', 'schedule', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic()
    {
        $ret = parent::getPublic();
        $ret['schedule'] = json_decode($ret['schedule'], TRUE);

        $ret['users'] = [];

        $users = $this->dbhr->preQuery("SELECT userid FROM schedules_users WHERE scheduleid = ?;", [ $this->id ]);
        foreach ($users as $user) {
            $ret['users'][] = $user['userid'];
        }

        return($ret);
    }

    public function addUser($userid) {
        $ret = [];

        $this->dbhm->preExec("INSERT INTO schedules_users (userid, scheduleid) VALUES (?, ?);", [
            $userid,
            $this->id
        ]);

        return($ret);
    }

    public function setSchedule($schedule) {
        $this->setPrivate('schedule', json_encode($schedule));
    }
}