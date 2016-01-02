<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');
require_once(IZNIK_BASE . '/include/message/Collection.php');

class User extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'firstname', 'lastname', 'fullname', 'systemrole');

    # Roles on specific groups
    const ROLE_NONMEMBER = 'Non-member';
    const ROLE_MEMBER = 'Member';
    const ROLE_MODERATOR = 'Moderator';
    const ROLE_OWNER = 'Owner';

    # Role on site
    const SYSTEMROLE_SUPPORT = 'Support';
    const SYSTEMROLE_ADMIN = 'Admin';
    const SYSTEMROLE_USER = 'User';
    const SYSTEMROLE_MODERATOR = 'Moderator';

    const LOGIN_YAHOO = 'Yahoo';
    const LOGIN_FACEBOOK = 'Facebook';
    const LOGIN_GOOGLE = 'Google';
    const LOGIN_NATIVE = 'Native';

    /** @var  $log Log */
    private $log;
    var $user;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users', 'user', $this->publicatts);
        $this->log = new Log($dbhr, $dbhm);
    }

    private function hashPassword($pw) {
        return sha1($pw . PASSWORD_SALT);
    }

    public function login($pw) {
        # TODO Passwords are a complex area.  There is probably something better we could do.
        #
        # TODO lockout
        if ($this->id) {
            $pw = $this->hashPassword($pw);
            $logins = $this->getLogins();
            foreach ($logins as $login) {
                if ($login['type'] == User::LOGIN_NATIVE && $pw == $login['credentials']) {
                    $s = new Session($this->dbhr, $this->dbhm);
                    $s->create($this->id);

                    $l = new Log($this->dbhr, $this->dbhm);
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_LOGIN,
                        'byuser' => $this->id,
                        'text' => 'Using email/password'
                    ]);

                    return (TRUE);
                }
            }
        }

        return(FALSE);
    }

    public function getName() {
        # We may or may not have the knowledge about how the name is split out, depending
        # on the sign-in mechanism.
        if ($this->user['fullname']) {
            return($this->user['fullname']);
        } else {
            return($this->user['firstname'] . ' ' . $this->user['lastname']);
        }
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($firstname, $lastname, $fullname) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        try {
            $rc = $this->dbhm->preExec("INSERT INTO users (firstname, lastname, fullname) VALUES (?, ?, ?)",
                [$firstname, $lastname, $fullname]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'users', 'user', $this->publicatts);
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_CREATED,
                'user' => $id,
                'byuser' => $me ? $me->getId() : NULL,
                'text' => $this->getName()
            ]);

            return($id);
        } else {
            return(NULL);
        }
    }

    public function findByYahooId($id) {
        $users = $this->dbhr->preQuery("SELECT id FROM users WHERE yahooUserId = ?;", [ $id ]);
        if (count($users) == 1) {
            return($users[0]['id']);
        }

        return(NULL);
    }

    public function getEmails() {
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE userid = ?;",
            [$this->id]);
        return($emails);
    }

    public function findByEmail($email) {
        $users = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE email LIKE ?;",
            [ $email ]);
        foreach ($users as $user) {
            return($user['userid']);
        }

        return(NULL);
    }

    public function addEmail($email, $primary = 1)
    {
        # If the email already exists in the table, then that's fine.  But we don't want to use INSERT IGNORE as
        # that scales badly for clusters.
        $rc = 1;
        $sql = "SELECT id FROM users_emails WHERE userid = ? AND email LIKE ?;";
        $emails = $this->dbhm->preQuery($sql, [
            $this->id,
            $email
        ]);

        if (count($emails) == 0) {
            $rc = $this->dbhm->preExec("INSERT INTO users_emails (userid, email, preferred) VALUES (?, ?, ?)",
                [$this->id, $email, $primary]);
        }

        return($rc);
    }

    public function removeEmail($email)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_emails WHERE userid = ? AND email LIKE ?;",
            [$this->id, $email]);
        return($rc);
    }

    private function updateSystemRole($role) {
        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
            $sql = "UPDATE users SET systemrole = ? WHERE id = ? AND systemrole = ?;";
            $this->dbhm->preExec($sql, [ User::SYSTEMROLE_MODERATOR, $this->id, User::SYSTEMROLE_USER ]);
            $this->user['systemrole'] = $this->user['systemrole'] == User::SYSTEMROLE_USER ?
                User::SYSTEMROLE_MODERATOR : $this->user['systemrole'];
        } else if ($this->user['systemrole'] == User::SYSTEMROLE_MODERATOR) {
            # Check that we are still a mod on a group, otherwise we need to demote ourselves.
            $sql = "SELECT id FROM memberships WHERE userid = ? AND role IN (?,?);";
            $roles = $this->dbhr->preQuery($sql, [
                $this->id,
                User::ROLE_MODERATOR,
                User::ROLE_OWNER
            ]);

            if (count($roles) == 0) {
                $sql = "UPDATE users SET systemrole = ? WHERE id = ?;";
                $this->dbhm->preExec($sql, [ User::SYSTEMROLE_USER, $this->id ]);
                $this->user['systemrole'] = User::SYSTEMROLE_USER;
            }
        }
    }

    public function addMembership($groupid, $role = User::ROLE_MEMBER) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->preExec("REPLACE INTO memberships (userid, groupid, role) VALUES (?,?,?);",
            [
                $this->id,
                $groupid,
                $role
            ]);

        # We might need to update the systemrole.
        #
        # Not the end of the world if this fails.
        $this->updateSystemRole($role);

        if ($rc) {
            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_JOINED,
                'user' => $this->id,
                'byuser' => $me ? $me->getId() : NULL,
                'groupid' => $groupid
            ]);
        }

        return($rc);
    }

    public function setMembershipAtt($groupid, $att, $val) {
        $sql = "UPDATE memberships SET $att = ? WHERE groupid = ? AND userid = ?;";
        $rc = $this->dbhm->preExec($sql, [
            $val,
            $groupid,
            $this->id
        ]);

        return($rc);
    }

    public function removeMembership($groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->preExec("DELETE FROM memberships WHERE userid = ? AND groupid = ?;",
            [
                $this->id,
                $groupid
            ]);

        if ($rc) {
            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_LEFT,
                'user' => $this->id,
                'byuser' => $me->getId(),
                'groupid' => $groupid
            ]);
        }

        return($rc);
    }

    public function getMemberships($modonly = FALSE) {
        $ret = [];
        $modq = $modonly ? " AND role IN ('Owner', 'Moderator') " : "";
        $sql = "SELECT groupid, role, configid FROM memberships WHERE userid = ? $modq;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id ]);

        $c = new ModConfig($this->dbhr, $this->dbhm);

        foreach ($groups as $group) {
            $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
            $one = $g->getPublic();
            $one['role'] = $group['role'];
            $one['configid'] = $c->getForGroup($this->id, $group['groupid']);

            $one['mysettings'] = $this->getGroupSettings($group['groupid']);

            if ($one['role'] == User::ROLE_MODERATOR || $one['role'] == User::ROLE_OWNER) {
                # Give a summary of outstanding work.
                $one['work'] = $g->getWorkCounts($one['mysettings']);
            }

            $ret[] = $one;
        }

        return($ret);
    }

    public function getConfigs() {
        $ret = [];
        # We can see configs which
        # - we created
        # - are used by mods on groups on which we are a mod
        # - defaults
        $sql = "(SELECT DISTINCT configid AS id FROM memberships WHERE groupid IN (SELECT groupid FROM memberships WHERE userid = {$this->id} AND role IN ('Moderator', 'Owner')) AND configid IS NOT NULL) UNION (SELECT id FROM mod_configs WHERE createdby = {$this->id} OR `default` = 1);";
        $ids = $this->dbhr->query($sql);

        foreach ($ids as $id) {
            $c = new ModConfig($this->dbhr, $this->dbhm, $id['id']);
            $thisone = $c->getPublic();

            $u = new User($this->dbhr, $this->dbhm, $thisone['createdby']);

            if ($u->getId()) {
                $thisone['createdby'] = $u->getPublic();
            }

            $ret[] = $thisone;
        }

        return($ret);
    }

    public function getModeratorships() {
        $ret = [];
        $groups = $this->dbhr->preQuery("SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner');", [ $this->id ]);
        foreach ($groups as $group) {
            $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
            $ret[] = $g->getPublic();
        }

        return($ret);
    }

    public function isModOrOwner($groupid) {
        $sql = "SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner') AND groupid = ?;";
        #error_log("$sql {$this->id}, $groupid");
        $groups = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid
        ]);

        foreach ($groups as $group) {
            return true;
        }

        return(false);
    }

    public function getLogins() {
        $logins = $this->dbhr->preQuery("SELECT * FROM users_logins WHERE userid = ?;",
            [$this->id]);
        return($logins);
    }

    public function findByLogin($type, $uid) {
        $logins = $this->dbhr->preQuery("SELECT * FROM users_logins WHERE uid = ? AND type = ?;",
            [ $uid, $type]);
        foreach ($logins as $login) {
            return($login['userid']);
        }

        return(NULL);
    }

    public function addLogin($type, $uid, $creds = NULL)
    {
        if ($type == User::LOGIN_NATIVE) {
            # Native login - the uid is the password encrypt the password a bit.
            $creds = $this->hashPassword($creds);
        }

        # If the login with this type already exists in the table, that's fine.
        $rc = $this->dbhm->preExec("INSERT IGNORE INTO users_logins (userid, uid, type, credentials) VALUES (?, ?, ?, ?)",
            [$this->id, $uid, $type, $creds]);
        return($rc);
    }

    public function removeLogin($type, $uid)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_logins WHERE userid = ? AND type = ? AND uid LIKE ?;",
            [$this->id, $type, $uid]);
        return($rc);
    }

    public function getRole($groupid, $overrides = TRUE) {
        # We can have a number of roles on a group
        # - none, we can only see what is member
        # - member, we are a group member and can see some extra info
        # - moderator, we can see most info on a group
        # - owner, we can see everything
        #
        # If our system role is support then we get moderator status; if it's admin we get owner status.
        $role = User::ROLE_NONMEMBER;

        if ($overrides) {
            switch ($this->getPrivate('systemrole')) {
                case User::SYSTEMROLE_SUPPORT:
                    $role = User::ROLE_MODERATOR;
                    break;
                case User::SYSTEMROLE_ADMIN:
                    $role = User::ROLE_OWNER;
                    break;
            }
        }

        # Now find if we have any membership of the group which might also give us a role.
        $membs = $this->dbhr->preQuery("SELECT role FROM memberships WHERE userid = ? AND groupid = ?;", [
                $this->id,
                $groupid
            ]);

        foreach ($membs as $memb) {
            switch ($memb['role']) {
                case 'Moderator':
                    $role = User::ROLE_MODERATOR;
                    break;
                case 'Owner':
                    $role = User::ROLE_OWNER;
                    break;
                case 'Member':
                    # Upgrade from none to member.
                    $role = $role == User::ROLE_NONMEMBER ? User::ROLE_MEMBER : $role;
                    break;
            }
        }

        return($role);
    }

    public function setGroupSettings($groupid, $settings) {
        if ($settings) {
            $sql = "UPDATE memberships SET settings = ? WHERE userid = ? AND groupid = ?;";
            $this->dbhm->preExec($sql, [
                json_encode($settings),
                $this->id,
                $groupid
            ]);
        }

        return([
            'ret' => 0,
            'status' => 'Success'
        ]);
    }

    public function getGroupSettings($groupid) {
        $sql = "SELECT settings FROM memberships WHERE userid = ? AND groupid = ?;";
        $sets = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);
        $settings = NULL;

        foreach ($sets as $set) {
            $settings = $set['settings'];
        }

        return($settings ? json_decode($settings, true) : [
            'showmessages' => 1,
            'showmembers' => 1
        ]);
    }

    public function setRole($role, $groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $l = new Log($this->dbhr, $this->dbhm);
        $l->log([
            'type' => Log::TYPE_USER,
            'byuser' => $me ? $me->getId() : NULL,
            'subtype' => Log::SUBTYPE_ROLE_CHANGE,
            'groupid' => $groupid,
            'user' => $this->id,
            'text' => $role
        ]);

        $sql = "UPDATE memberships SET role = ? WHERE userid = ? AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [
            $role,
            $this->id,
            $groupid
        ]);

        # We might need to update the systemrole.
        #
        # Not the end of the world if this fails.
        $this->updateSystemRole($role);

        return($rc);
    }

    public function getPublic($groupids = NULL, $history = TRUE, $logs = FALSE) {
        $atts = parent::getPublic();

        if ($history) {
            # Add in the message history - from any of the emails associated with this user.
            if ($groupids && count($groupids) > 0) {
                # On these groups
                $groupq = implode(',', $groupids);
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND groupid IN ($groupq) AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            } else {
                # On all groups.
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            }

            $atts['messagehistory'] = $this->dbhr->preQuery($sql, [
                Collection::APPROVED,
                $this->id
            ]);

            foreach ($atts['messagehistory'] as &$hist) {
                $hist['arrival'] = ISODate($hist['arrival']);
                $hist['date'] = ISODate($hist['date']);
            }
        }

        # Add in a count of recent "modmail" type logs which a mod might care about.
        #
        # Exclude the logs which are due to standard message syncing.
        $sql = "SELECT COUNT(*) AS count FROM `logs` WHERE user = ? AND timestamp > ? AND type = 'Message' AND subtype IN ('Rejected', 'Deleted') AND text NOT IN ('Not present on Yahoo');";
        $mysqltime = date ("Y-m-d", strtotime("Midnight 30 days ago"));
        $alarms = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);
        $atts['modmails'] = $alarms[0]['count'];

        if ($logs) {
            # Add in the log entries we have for this user.
            $me = whoAmI($this->dbhr, $this->dbhm);
            $sql = "SELECT DISTINCT * FROM logs WHERE (user = ? OR byuser = ?) AND (text IS NULL OR text NOT IN ('Not present on Yahoo')) ORDER BY id DESC;";
            $logs = $this->dbhr->preQuery($sql, [ $this->id, $this->id ]);
            $atts['logs'] = [];
            $groups = [];
            $users = [];
            $configs = [];

            foreach ($logs as $log) {
                if (pres('byuser', $log)) {
                    if (!pres($log['byuser'], $users)) {
                        $u = new User($this->dbhr, $this->dbhm, $log['byuser']);
                        $users[$log['byuser']] = $u->getPublic(NULL, FALSE, FALSE);
                    }

                    $log['byuser'] = $users[$log['byuser']];
                }

                if (pres('user', $log)) {
                    if (!pres($log['user'], $users)) {
                        $u = new User($this->dbhr, $this->dbhm, $log['user']);
                        $users[$log['user']] = $u->getPublic(NULL, FALSE, FALSE);
                    }

                    $log['user'] = $users[$log['user']];
                }

                if (pres('groupid', $log)) {
                    if (!pres($log['groupid'], $groups)) {
                        $g = new Group($this->dbhr, $this->dbhm, $log['groupid']);
                        $groups[$log['groupid']] = $g->getPublic();
                        $groups[$log['groupid']]['myrole'] = $me ? $me->getRole($log['groupid']) : User::ROLE_NONMEMBER;
                    }

                    if ($groups[$log['groupid']]['myrole'] != User::ROLE_OWNER &&
                        $groups[$log['groupid']]['myrole'] != User::ROLE_MODERATOR) {
                        # We can only see logs for this group if we have a mod role, or if we have appropriate system
                        # rights.  Skip this log.
                        break;
                    }

                    $log['group'] = $groups[$log['groupid']];
                }

                if (pres('configid', $log)) {
                    if (!pres($log['configid'], $configs)) {
                        $c = new ModConfig($this->dbhr, $this->dbhm, $log['configid']);
                        $configs[$log['configid']] = $c->getPublic();
                    }

                    $log['config'] = $configs[$log['configid']];
                }

                if (pres('msgid', $log)) {
                    $g = new Message($this->dbhr, $this->dbhm, $log['msgid']);
                    $log['message'] = $g->getPublic(FALSE);

                    # Prune large attributes.
                    unset($log['message']['textbody']);
                    unset($log['message']['htmlbody']);
                    unset($log['message']['message']);
                }

                $log['timestamp'] = ISODate($log['timestamp']);

                $atts['logs'][] = $log;
            }
        }

        $atts['displayname'] = $this->getName();

        return($atts);
    }

    public function roleMax($role1, $role2) {
        $role = User::ROLE_NONMEMBER;

        if ($role1 == User::ROLE_MEMBER || $role2 == User::ROLE_MEMBER) {
            $role = User::ROLE_MEMBER;
        }

        if ($role1 == User::ROLE_MODERATOR || $role2 == User::ROLE_MODERATOR) {
            $role = User::ROLE_MODERATOR;
        }

        if ($role1 == User::ROLE_OWNER || $role2 == User::ROLE_OWNER) {
            $role = User::ROLE_OWNER;
        }

        return($role);
    }

    public function merge($id1, $id2) {
        # We want to merge two users.  At present we just merge the memberships, emails and logs; we don't try to
        # merge any conflicting settings.
        #
        # Both users might have membership of the same group, including at different levels.
        #error_log("Merge $id2 into $id1");
        $l = new Log($this->dbhr, $this->dbhm);
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->beginTransaction();
        $rollback = FALSE;

        if ($rc) {
            try {
                #error_log("Started transaction");
                $rollback = TRUE;

                # Merge the memberships
                $id2membs = $this->dbhr->preQuery("SELECT * FROM memberships WHERE userid = ?;", [ $id2 ]);
                foreach ($id2membs as $id2memb) {
                    # Jiggery-pokery with $rc for UT purposes.
                    $rc2 = $rc;
                    #error_log("$id2 member of {$id2memb['groupid']} ");
                    $id1membs = $this->dbhr->preQuery("SELECT * FROM memberships WHERE userid = ? AND groupid = ?;", [
                        $id1,
                        $id2memb['groupid']
                    ]);

                    if (count($id1membs) == 0) {
                        # id1 is not already a member.  Just change our id2 membership to id1.
                        #error_log("...$id1 not a member, UPDATE");
                        $rc2 = $this->dbhm->preExec("UPDATE memberships SET userid = ? WHERE userid = ? AND groupid = ?;", [
                            $id1,
                            $id2,
                            $id2memb['groupid']
                        ]);

                        #error_log("Membership UPDATE merge returned $rc2");
                    } else {
                        # id1 is already a member.  Our new membership has the highest role.
                        #error_log("...as is $id1");
                        $role = User::roleMax($id1membs[0]['role'], $id2memb['role']);

                        if ($role != $id1membs[0]['role']) {
                            $rc2 = $this->dbhm->preExec("UPDATE memberships SET role = ? WHERE userid = ? AND groupid = ?;", [
                                $role,
                                $id1,
                                $id2memb['groupid']
                            ]);
                            #error_log("Role update returned $rc2");
                        }

                        if ($rc2) {
                            # Now we just need to delete the id2 one.
                            $rc2 = $this->dbhm->preExec("DELETE FROM memberships WHERE userid = ? AND groupid = ?;", [
                                $id2,
                                $id2memb['groupid']
                            ]);

                            #error_log("Membership DELETE returned $rc2");
                        }
                    }

                    $rc = $rc2 && $rc ? $rc2 : 0;
                }

                # Merge the emails.  Both might have a primary address; id1 wins.  There is a unique index, so there
                # can't be a conflict on email.
                if ($rc) {
                    #error_log("Merge emails");
                    $sql = "UPDATE users_emails SET userid = ?, preferred = 0 WHERE userid = ?;";
                    $rc = $this->dbhm->preExec($sql, [
                        $id1,
                        $id2
                    ]);

                    #error_log("Emails now " . var_export($this->dbhm->preQuery("SELECT * FROM users_emails WHERE userid = $id1;"), true));
                    #error_log("Email merge returned $rc");
                }

                # Merge the logs.  There should be logs both about and by each user, so we can use the rc to check success.
                if ($rc) {
                    $rc = $this->dbhm->preExec("UPDATE logs SET user = ? WHERE user = ?;", [
                        $id1,
                        $id2
                    ]);

                    #error_log("Log merge 1 returned $rc");
                }

                if ($rc) {
                    $rc = $this->dbhm->preExec("UPDATE logs SET byuser = ? WHERE byuser = ?;", [
                        $id1,
                        $id2
                    ]);

                    #error_log("Log merge 2 returned $rc");
                }

                # Merge the fromuser in messages.  There might not be any, and it's not the end of the world
                # if this info isn't correct, so ignore the rc.
                if ($rc) {
                    $this->dbhm->preExec("UPDATE messages SET fromuser = ? WHERE fromuser = ?;", [
                        $id1,
                        $id2
                    ]);
                }

                if ($rc) {
                    # Log the merge - before the delete otherwise we will fail to log it.
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_MERGED,
                        'user' => $id2,
                        'byuser' => $me ? $me->getId() : NULL,
                        'text' => "Merged $id1 into $id2"
                    ]);

                    # Log under both users to make sure we can trace it.
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_MERGED,
                        'user' => $id1,
                        'byuser' => $me ? $me->getId() : NULL,
                        'text' => "Merged $id1 into $id2"
                    ]);

                    # Finally, delete id2.
                    #error_log("Delete $id2");
                    $deleteme = new User($this->dbhr, $this->dbhm, $id2);
                    $rc = $deleteme->delete();
                }

                if ($rc) {
                    # Everything worked.
                    $rollback = FALSE;
                }
            } catch (Exception $e) {
                $rollback = TRUE;
            }
        }

        if ($rollback) {
            # Something went wrong.
            $this->dbhm->rollBack();
            $ret = FALSE;
        } else {
            $ret = $this->dbhm->commit();
       }

        return($ret);
    }

    public function delete() {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->preExec("DELETE FROM users WHERE id = ?;", [$this->id]);
        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_DELETED,
                'user' => $this->id,
                'byuser' => $me ? $me->getId() : NULL,
                'text' => $this->getName()
            ]);
        }

        return($rc);
    }
}