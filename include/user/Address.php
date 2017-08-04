<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/misc/PAF.php');

class Address extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'pafid', 'to', 'instructions');
    var $settableatts = array('pafid', 'fo', 'instructions');

    const ASK_OUTCOME_THRESHOLD = 3;
    const ASK_OFFER_THRESHOLD = 5;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users_addresses', 'address', $this->publicatts);
    }

    public function create($userid, $pafid, $instructions = NULL) {
        $id = NULL;

        $rc = $this->dbhm->preExec("REPLACE INTO users_addresses (userid, pafid, instructions) VALUES (?,?,?);", [
            $userid,
            $pafid,
            $instructions
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhm, $this->dbhm, $id, 'users_addresses', 'address', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic()
    {
        $ret = parent::getPublic();

        $atts = $this->settableatts;

        if (pres('pafid', $ret)) {
            $p = new PAF($this->dbhr, $this->dbhm);
            $ret['singleline'] = $p->getSingleLine($ret['pafid']);
            $ret['multiline'] = $p->getFormatted($ret['pafid'], "\n");

            $pcs = $this->dbhr->preQuery("SELECT postcodeid FROM paf_addresses WHERE id = ?;", [
                $ret['pafid']
            ]);

            foreach ($pcs as $pc) {
                $l = new Location($this->dbhr, $this->dbhm, $pc['postcodeid']);
                $ret['postcode'] = $l->getPublic();
            }
        }

        return($ret);
    }

    public function listForUser($userid) {
        $ret = [];

        $addresses = $this->dbhr->preQuery("SELECT id FROM users_addresses WHERE userid = ?;", [
            $userid
        ]);

        foreach ($addresses as $address) {
            $a = new Address($this->dbhr, $this->dbhm, $address['id']);
            $ret[] = $a->getPublic();
        }

        return($ret);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users_addresses WHERE id = ?;", [ $this->id ]);
        return($rc);
    }
}