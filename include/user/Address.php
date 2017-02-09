<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/mailtemplates/story.php');

class Address extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'line1', 'line2', 'line3', 'line4', 'town', 'county', 'postcodeid', 'instructions');
    var $settableatts = array('line1', 'line2', 'line3', 'line4', 'town', 'county', 'postcodeid', 'instructions');

    const ASK_OUTCOME_THRESHOLD = 3;
    const ASK_OFFER_THRESHOLD = 5;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users_addresses', 'address', $this->publicatts);
    }

    public function create($userid, $line1, $line2, $line3, $line4, $town, $county, $postcodeid, $instructions) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO users_addresses (userid, line1, line2, line3, line4, town, county, postcodeid, instructions) VALUES (?,?,?,?,?,?,?,?,?);", [
            $userid,
            $line1, 
            $line2, 
            $line3, 
            $line4, 
            $town, 
            $county, 
            $postcodeid,
            $instructions
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhr, $this->dbhm, $id, 'users_addresses', 'address', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic()
    {
        $ret = parent::getPublic();

        # Mask out address - even when showing to the logged in user we don't want to show it all.  Remember that
        # support staff can impersonate.
        $atts = $this->settableatts;
        unset($atts['postcodeid']);

        foreach ($atts as $key => $val) {
            $len = strlen($val);
            $ret[$key] = substr($val, 0, 1) . str_repeat('*', $len - 2) . substr($val, $len - 1, 1);
        }

        if (pres('postcodeid', $ret)) {
            $l = new Location($this->dbhr, $this->dbhm, $ret['postcodeid']);
            $ret['postcode'] = $l->getPublic();
            unset($ret['postcodeid']);
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