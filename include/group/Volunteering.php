<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/user/User.php');

class Volunteering extends Entity
{
    /** @var  $dbhm LoggedPDO */
    public $publicatts = [ 'id', 'userid', 'pending', 'title', 'location', 'online', 'contactname', 'contactphone', 'contactemail', 'contacturl', 'description', 'added', 'timecommitment' ];
    public $settableatts = [ 'userid', 'pending', 'title', 'location', 'online', 'contactname', 'contactphone', 'contactemail', 'contacturl', 'description', 'added', 'timecommitment' ];
    var $volunteering;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'volunteering', 'volunteering', $this->publicatts);
    }

    public function create($userid, $title, $online, $location, $contactname, $contactphone, $contactemail, $contacturl, $description, $timecommitment) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO volunteering (`userid`, `pending`, `title`, `online`, `location`, `contactname`, `contactphone`, `contactemail`, `contacturl`, `description`, `timecommitment`) VALUES (?,1,?,?,?,?,?,?,?,?,?);", [
            $userid, $title, $online === NULL ? FALSE : $online, $location, $contactname, $contactphone, $contactemail, $contacturl, $description, $timecommitment
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'volunteering', 'volunteering', $this->publicatts);
        }

        return($id);
    }

    public function addDate($start, $end, $applyby) {
        $this->dbhm->preExec("INSERT INTO volunteering_dates (volunteeringid, start, end, applyby) VALUES (?, ?, ?, ?);" , [
            $this->id,
            $start,
            $end,
            $applyby
        ]);
        return($this->dbhm->lastInsertId());
    }

    public function removeDate($id) {
        $this->dbhm->preExec("DELETE FROM volunteering_dates WHERE id = ?;" , [
            $id
        ]);
    }

    public function addGroup($groupid) {
        # IGNORE as we have a unique key on volunteering/group.
        $this->dbhm->preExec("INSERT IGNORE INTO volunteering_groups (volunteeringid, groupid) VALUES (?, ?);" , [
            $this->id,
            $groupid
        ]);
    }

    public function removeGroup($id) {
        $this->dbhm->preExec("DELETE FROM volunteering_groups WHERE volunteeringid = ? AND groupid = ?;" , [
            $this->id,
            $id
        ]);
    }

    public function listForUser($userid, $pending, $systemwide, &$ctx) {
        $ret = [];
        $pendingq = $pending ? " AND pending = 1 " : " AND pending = 0 ";
        $roleq = $pending ? " AND role IN ('Owner', 'Moderator') " : '';
        $ctxq = $ctx ? " AND volunteering.id < '{$ctx['id']}' " : '';

        $mysqltime = date("Y-m-d H:i:s", time());

        # Get the ones for our group.
        $sql = "SELECT volunteering.id, volunteering.pending, volunteering_dates.end, volunteering_groups.groupid FROM volunteering INNER JOIN volunteering_groups ON volunteering_groups.volunteeringid = volunteering.id AND groupid IN (SELECT groupid FROM memberships WHERE userid = ? $roleq) LEFT JOIN volunteering_dates ON volunteering_dates.volunteeringid = volunteering.id WHERE (applyby IS NULL OR applyby >= ?) AND (end IS NULL OR end >= ?) AND deleted = 0 AND expired = 0 $pendingq $ctxq ORDER BY id DESC LIMIT 20;";
        $volunteerings = $this->dbhr->preQuery($sql, [
            $userid,
            $mysqltime,
            $mysqltime
        ]);

        # Get the national ones, for display or approval.
        $me = whoAmI($this->dbhr, $this->dbhm);
        if (!$pending || ($me && $me->hasPermission(User::PERM_NATIONAL_VOLUNTEERS))) {
            $sql = "SELECT volunteering.id, volunteering.pending, volunteering_dates.end, volunteering_dates.applyby AS groupid FROM volunteering LEFT JOIN volunteering_groups ON volunteering_groups.volunteeringid = volunteering.id AND deleted = 0 AND expired = 0 LEFT JOIN volunteering_dates ON volunteering_dates.volunteeringid = volunteering.id WHERE groupid IS NULL AND deleted = 0 AND expired = 0 $pendingq $ctxq ORDER BY id DESC LIMIT 20;";
            $volunteerings = array_merge($volunteerings, $this->dbhr->preQuery($sql));
        }

        $u = User::get($this->dbhr, $this->dbhm, $userid);

        foreach ($volunteerings as $volunteering) {
            if ((!$volunteering['pending'] || $volunteering['groupid'] === NULL || $u->activeModForGroup($volunteering['groupid'])) &&
                (!pres('applyby', $volunteering) || time() < strtotime($volunteering['applyby'])) &&
                (!pres('end', $volunteering) || time() < strtotime($volunteering['end']))
            ) {
                $ctx['id'] = $volunteering['id'];
                $e = new Volunteering($this->dbhr, $this->dbhm, $volunteering['id']);
                $atts = $e->getPublic();
                $atts['canmodify'] = $e->canModify($userid);

                $ret[] = $atts;
            }
        }

        return($ret);
    }

    public function listForGroup($pending, $groupid = NULL, &$ctx) {
        $ret = [];
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        # We can only see pending volunteerings if we're an owner/mod.
        # We might be called for a specific groupid; if not then use logged in user's groups.
        $pendingq = $pending ? " AND pending = 1 " : " AND pending = 0 ";
        $roleq = $pending ? (" AND groupid IN (SELECT groupid FROM memberships WHERE userid = " . intval($myid) . " AND role IN ('Owner', 'Moderator')) ") : '';
        $groupq = $groupid ? (" AND groupid = " . intval($groupid)) : (" AND groupid IN (SELECT groupid FROM memberships WHERE userid = " . intval($myid) . ") ");
        $ctxq = $ctx ? " AND volunteering.id < {$ctx['id']} " : '';

        $mysqltime = date("Y-m-d H:i:s", time());
        $sql = "SELECT volunteering.id, volunteering_dates.applyby, volunteering_dates.end FROM volunteering INNER JOIN volunteering_groups ON volunteering_groups.volunteeringid = volunteering.id $groupq $roleq AND deleted = 0 AND expired = 0 LEFT JOIN volunteering_dates ON volunteering_dates.volunteeringid = volunteering.id $pendingq $ctxq ORDER BY id DESC LIMIT 20;";
        $volunteerings = $this->dbhr->preQuery($sql, [
            $mysqltime,
            $mysqltime
        ]);

        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : $me;

        foreach ($volunteerings as $volunteering) {
            if ((!pres('applyby', $volunteering) || time() < strtotime($volunteering['applyby'])) &&
                (!pres('end', $volunteering) || time() < strtotime($volunteering['end']))
            ) {
                $ctx['id'] = $volunteering['id'];
                $e = new Volunteering($this->dbhr, $this->dbhm, $volunteering['id']);
                $atts = $e->getPublic();

                $atts['canmodify'] = $e->canModify($myid);

                $ret[] = $atts;
            }
        }

        return($ret);
    }

    public function getPublic() {
        $atts = parent::getPublic();
        $atts['groups'] = [];

        $groups = $this->dbhr->preQuery("SELECT * FROM volunteering_groups WHERE volunteeringid = ?", [ $this->id ]);

        foreach ($groups as $group) {
            $g = Group::get($this->dbhr, $this->dbhm, $group['groupid']);
            $atts['groups'][] = $g->getPublic();
        }

        $atts['dates'] = $this->dbhr->preQuery("SELECT * FROM volunteering_dates WHERE volunteeringid = ? ORDER BY end ASC", [ $this->id ]);

        foreach ($atts['dates'] as &$date) {
            $date['start'] = ISODate($date['start']);
            $date['end'] = ISODate($date['end']);
        }

        if ($atts['userid']) {
            $u = User::get($this->dbhr, $this->dbhm, $atts['userid']);
            $atts['user'] = $u->getPublic();
        }

        unset($atts['userid']);

        # Ensure leading 0 not stripped.
        $atts['contactphone'] = pres('contactphone', $atts) ? "{$atts['contactphone']} " : NULL;

        return($atts);
    }

    public function canModify($userid) {
        # We can modify volunteerings which we created, or where we are a mod on any of the groups on which this volunteering
        # appears, or if we're support/admin.
        $canmodify = $this->volunteering['userid'] == $userid;
        #error_log("Check user {$this->volunteering['userid']}, $userid");
        $u = User::get($this->dbhr, $this->dbhm, $userid);

        #error_log("Can mod? $canmodify");
        if (!$canmodify) {
            $groups = $this->dbhr->preQuery("SELECT * FROM volunteering_groups WHERE volunteeringid = ?;", [ $this->id ]);
            #error_log("\"SELECT * FROM volunteering_groups WHERE volunteeringid = {$this->id};");
            foreach ($groups as $group) {
                #error_log("Check for group {$group['groupid']} " . $u->isAdminOrSupport() . ", " . $u->isModOrOwner($group['groupid']));
                if ($u->isAdminOrSupport() || $u->isModOrOwner($group['groupid'])) {
                    $canmodify = TRUE;
                }
            }
        }

        return($canmodify);
    }

    public function expire() {
        # If an opportunities has any dates, then check that at least one is in the future, otherwise it's expired.  Some
        # opportunities run indefinitely.
        $ids = $this->dbhr->preQuery("SELECT DISTINCT volunteeringid FROM volunteering INNER JOIN volunteering_dates ON volunteering.id = volunteering_dates.volunteeringid WHERE end <= NOW() AND expired = 0;");

        foreach ($ids as $id) {
            $futures = $this->dbhr->preQuery("SELECT volunteeringid FROM volunteering_dates WHERE volunteeringid = ? AND end > NOW();", [
                $id['volunteeringid']
            ]);

            if (count($futures) === 0) {
                $this->dbhm->preExec("UPDATE volunteering SET expired = 1 WHERE id = ?;", [
                    $id['volunteeringid']
                ]);
            }
        }
    }

    public function delete() {
        $this->dbhm->preExec("UPDATE volunteering SET deleted = 1 WHERE id = ?;", [ $this->id ]);
    }
}

