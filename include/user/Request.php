<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Address.php');

class Request extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'userid', 'type', 'date', 'completed', 'addressid');
    var $settableatts = array('completed', 'addressid');

    const TYPE_BUSINESS_CARDS = 'BusinessCards';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users_requests', 'request', $this->publicatts);
    }

    public function create($userid, $type, $addressid) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO users_requests (userid, type, addressid) VALUES (?,?,?);", [
            $userid,
            $type,
            $addressid
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhr, $this->dbhm, $id, 'users_requests', 'request', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic()
    {
        $ret = parent::getPublic();

        if (pres('addressid', $ret)) {
            $a = new Address($this->dbhr, $this->dbhm, $ret['addressid']);

            # We can see the address when we're allowed to see a request.
            $ret['address'] = $a->getPublic(FALSE);

            unset($ret['addressid']);
        }

        return($ret);
    }

    public function listForUser($userid) {
        $ret = [];

        $requests = $this->dbhr->preQuery("SELECT id FROM users_requests WHERE userid = ? AND completed IS NULL;", [
            $userid
        ]);

        foreach ($requests as $request) {
            $r = new Request($this->dbhr, $this->dbhm, $request['id']);
            $ret[] = $r->getPublic();
        }

        return($ret);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users_requests WHERE id = ?;", [ $this->id ]);
        return($rc);
    }
}