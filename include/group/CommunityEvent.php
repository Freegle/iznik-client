<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class CommunityEvent extends Entity
{
    /** @var  $dbhm LoggedPDO */
    public $publicatts = [ 'id', 'userid', 'pending', 'title', 'location', 'contactname', 'contactphone', 'contactemail', 'description', 'added'];
    public $settableatts = [ 'pending', 'title', 'location', 'contactname', 'contactphone', 'contactemail', 'description' ];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'communityevents', 'event', $this->publicatts);
    }

    public function create($userid, $title, $location, $contactname, $contactphone, $contactemail, $description) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO communityevents (`userid`, `pending`, `title`, `location`, `contactname`, `contactphone`, `contactemail`, `description`) VALUES (?,1,?,?,?,?,?,?);", [
            $userid, $title, $location, $contactname, $contactphone, $contactemail, $description
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'communityevents', 'event', $this->publicatts);
        }

        return($id);
    }

    public function addDate($start, $end) {
        $this->dbhm->preExec("INSERT INTO communityevents_dates (eventid, start, end) VALUES (?, ?, ?);" , [
            $this->id,
            $start,
            $end
        ]);
    }

    public function removeDate($id) {
        $this->dbhm->preExec("DELETE FROM communityevents_dates WHERE id = ?;" , [
            $id
        ]);
    }

    public function addGroup($groupid) {
        $this->dbhm->preExec("INSERT INTO communityevents_groups (eventid, groupid) VALUES (?, ?);" , [
            $this->id,
            $groupid
        ]);
    }

    public function removeGroup($id) {
        $this->dbhm->preExec("DELETE FROM communityevents_groups WHERE eventid = ? AND groupid = ?;" , [
            $this->id,
            $id
        ]);
    }

    public function listForUser($userid) {
        $ret = [];

        $mysqltime = date("Y-m-d H:i:s", time());
        $sql = "SELECT communityevents.id FROM communityevents INNER JOIN communityevents_groups ON communityevents_groups.eventid = communityevents.id AND groupid IN (SELECT groupid FROM memberships WHERE userid = ?) AND deleted = 0 INNER JOIN communityevents_dates ON communityevents_dates.eventid = communityevents.id AND end >= ? ORDER BY end ASC LIMIT 20;";
        $events = $this->dbhr->preQuery($sql, [
            $userid,
            $mysqltime
        ]);

        foreach ($events as $event) {
            $e = new CommunityEvent($this->dbhr, $this->dbhm, $event['id']);
            $atts = $e->getPublic();
            $atts['canmodify'] = $e->canModify($userid);
            
            $ret[] = $atts;
        }

        return($ret);
    }
    
    public function getPublic() {
        $atts = parent::getPublic();
        $atts['groups'] = [];
        
        $groups = $this->dbhr->preQuery("SELECT * FROM communityevents_groups WHERE eventid = ?", [ $this->id ]);

        foreach ($groups as $group) {
            $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
            $atts['groups'][] = $g->getPublic();
        }

        $atts['dates'] = $this->dbhr->preQuery("SELECT * FROM communityevents_dates WHERE eventid = ?", [ $this->id ]);
        
        foreach ($atts['dates'] as &$date) {
            $date['start'] = ISODate($date['start']);
            $date['end'] = ISODate($date['end']);
        }

        return($atts);
    }

    public function canModify($userid) {
        # We can modify events which we created, or where we are a mod on any of the groups on which this event
        # appears, or if we're support/admin.
        $canmodify = $this->event['userid'] == $userid;
        #error_log("Check user {$this->event['userid']}, $userid");
        $u = new User($this->dbhr, $this->dbhm, $userid);

        if (!$canmodify) {
            $groups = $this->dbhr->preQuery("SELECT * FROM communityevents_groups WHERE eventid = ?", [ $this->id ]);
            foreach ($groups as $group) {
                #error_log("Check for group {$group['groupid']} " . $u->isAdminOrSupport() . ", " . $u->isModOrOwner($group['groupid']));
                if ($u->isAdminOrSupport() || $u->isModOrOwner($group['groupid'])) {
                    $canmodify = TRUE;
                }
            }
        }

        return($canmodify);
    }

    public function delete() {
        $this->dbhm->preExec("UPDATE communityevents SET deleted = 1 WHERE id = ?;", [ $this->id ]);
    }
}

