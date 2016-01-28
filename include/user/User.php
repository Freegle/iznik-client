<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');

class User extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'firstname', 'lastname', 'fullname', 'systemrole', 'settings', 'yahooid');

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

    public function findByYahooUserId($id) {
        $users = $this->dbhr->preQuery("SELECT id FROM users WHERE yahooUserId = ?;", [ $id ]);
        if (count($users) == 1) {
            return($users[0]['id']);
        }

        return(NULL);
    }

    public function getEmails() {
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE userid = ? ORDER BY preferred DESC;",
            [$this->id]);
        return($emails);
    }

    public function getEmailPreferred() {
        $emails = $this->dbhr->preQuery("SELECT * FROM users_emails WHERE userid = ? AND preferred = 1;",
            [$this->id]);
        return(count($emails) == 0 ? NULL : $emails[0]['email']);
    }

    public function getEmailForGroup($groupid) {
        $emails = $this->dbhr->preQuery("SELECT emailid FROM memberships WHERE userid = ? AND groupid = ?;", [
            $this->id,
            $groupid
        ]);

        foreach ($emails as $email) {
            return($email['emailid']);
        }

        return(NULL);
    }

    public function getIdForEmail($email) {
        # Email is a unique key but conceivably we could be called with an email for another user.
        $ids = $this->dbhr->preQuery("SELECT id, userid FROM users_emails WHERE email LIKE ?;", [
            $email
        ]);

        foreach ($ids as $id) {
            return($id);
        }

        return(NULL);
    }

    public function findByEmail($email) {
        $users = $this->dbhr->preQuery("SELECT userid FROM users_emails WHERE email LIKE ?;",
            [ $email ]);

        foreach ($users as $user) {
            return($user['userid']);
        }

        return(NULL);
    }

    public function findByYahooId($id) {
        $users = $this->dbhr->preQuery("SELECT id FROM users WHERE yahooid LIKE ?;",
            [ $id ]);

        foreach ($users as $user) {
            return($user['id']);
        }

        return(NULL);
    }

    public function addEmail($email, $primary = 1)
    {
        # If the email already exists in the table, then that's fine.  But we don't want to use INSERT IGNORE as
        # that scales badly for clusters.
        $sql = "SELECT id FROM users_emails WHERE userid = ? AND email LIKE ?;";
        $emails = $this->dbhm->preQuery($sql, [
            $this->id,
            $email
        ]);

        if (count($emails) == 0) {
            $this->dbhm->preExec("INSERT IGNORE INTO users_emails (userid, email, preferred) VALUES (?, ?, ?)",
                [$this->id, $email, $primary]);
            $rc = $this->dbhm->lastInsertId();
        } else {
            $rc = $emails[0]['id'];
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

    public function addMembership($groupid, $role = User::ROLE_MEMBER, $emailid = NULL, $collection = MembershipCollection::APPROVED) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        # Check if we're banned
        $sql = "SELECT * FROM users_banned WHERE userid = ? AND groupid = ?;";
        $banneds = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid
        ]);

        foreach ($banneds as $banned) {
            return(FALSE);
        }

        $rc = $this->dbhm->preExec("REPLACE INTO memberships (userid, groupid, role, emailid, collection) VALUES (?,?,?,?,?);", [
            $this->id,
            $groupid,
            $role,
            $emailid,
            $collection
        ]);

        # Record the operation for abuse detection.
        $this->dbhm->preExec("INSERT INTO memberships_history (userid, groupid, collection) VALUES (?,?,?);", [
            $this->id,
            $groupid,
            $collection
        ]);

        # We might need to update the systemrole.
        #
        # Not the end of the world if this fails.
        $this->updateSystemRole($role);

        if ($rc) {
            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => $collection == MembershipCollection::PENDING ? Log::SUBTYPE_APPLIED : Log::SUBTYPE_JOINED,
                'user' => $this->id,
                'byuser' => $me ? $me->getId() : NULL,
                'groupid' => $groupid
            ]);
        }

        # Check whether this user now counts as a possible spammer.
        $s = new Spam($this->dbhr, $this->dbhm);
        $s->checkUser($this->id);

        return($rc);
    }

    public function isPending($groupid) {
        $ret = false;
        $sql = "SELECT userid FROM memberships WHERE userid = ? AND groupid = ? AND collection = ?;";
        $membs = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid,
            MembershipCollection::PENDING
        ]);

        return(count($membs) > 0);
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

    public function removeMembership($groupid, $ban = FALSE) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $meid = $me ? $me->getId() : NULL;

        $rc = $this->dbhm->preExec("DELETE FROM memberships WHERE userid = ? AND groupid = ?;",
            [
                $this->id,
                $groupid
            ]);

        if ($rc) {
            if ($this->user['yahooUserId']) {
                # This is a user on Yahoo.  We must try to remove them from the group on there too, via the plugin.
                $sql = "SELECT email FROM users_emails INNER JOIN users ON users_emails.userid = users.id AND users.id = ?;";
                $emails = $this->dbhr->preQuery($sql, [ $this->id ]);
                $email = count($emails) > 0 ? $emails[0]['email'] : NULL;

                if ($ban) {
                    $type = $this->isPending($groupid) ? 'BanPendingMember' : 'BanApprovedMember';
                } else {
                    $type = $this->isPending($groupid) ? 'RemovePendingMember' : 'RemoveApprovedMember';
                }

                # It would be odd for them to be on Yahoo with no email but handle it anyway.
                if ($email) {
                    $p = new Plugin($this->dbhr, $this->dbhm);
                    $p->add($groupid, [
                        'type' => $ban ? 'BanApprovedMember' : 'RemoveApprovedMember',
                        'id' => $this->user['yahooUserId'],
                        'email' => $email
                    ]);
                }
            }

            if ($ban) {
                $sql = "INSERT IGNORE INTO users_banned (userid, groupid, byuser) VALUES (?,?,?);";
                $this->dbhm->preExec($sql, [
                    $this->id,
                    $groupid,
                    $meid
                ]);
            }

            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_LEFT,
                'user' => $this->id,
                'byuser' => $meid,
                'groupid' => $groupid,
                'text' => $ban ? "via ban" : NULL
            ]);
        }


        return($rc);
    }

    public function getMemberships($modonly = FALSE) {
        $ret = [];
        $modq = $modonly ? " AND role IN ('Owner', 'Moderator') " : "";
        $sql = "SELECT groupid, role, configid, CASE WHEN namefull IS NOT NULL THEN namefull ELSE nameshort END AS namedisplay FROM memberships INNER JOIN groups ON groups.id = memberships.groupid WHERE userid = ? $modq ORDER BY LOWER(namedisplay) ASC;";
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
        $me = whoAmI($this->dbhr, $this->dbhm);

        # We can see configs which
        # - we created
        # - are used by mods on groups on which we are a mod
        # - defaults
        $sql = "(SELECT DISTINCT configid AS id, userid, groupid FROM memberships WHERE groupid IN (SELECT groupid FROM memberships WHERE userid = {$this->id} AND role IN ('Moderator', 'Owner')) AND configid IS NOT NULL) UNION (SELECT id, NULL, NULL FROM mod_configs WHERE createdby = {$this->id} OR `default` = 1);";
        $ids = $this->dbhr->query($sql);

        foreach ($ids as $id) {
            $c = new ModConfig($this->dbhr, $this->dbhm, $id['id']);
            $thisone = $c->getPublic(FALSE);

            if ($thisone['createdby'] == $me->getId()) {
                $thisone['cansee'] = ModConfig::CANSEE_CREATED;
            } else if ($thisone['default']) {
                $thisone['cansee'] = ModConfig::CANSEE_DEFAULT;
            } else {
                $thisone['cansee'] = ModConfig::CANSEE_SHARED;
                $u = new User($this->dbhr, $this->dbhm, $id['userid']);
                $g = new Group($this->dbhr, $this->dbhm, $id['groupid']);
                $thisone['sharedby'] = $u->getPublic(NULL, FALSE);
                $thisone['sharedon'] = $g->getPublic();
            }

            $u = new User($this->dbhr, $this->dbhm, $thisone['createdby']);

            if ($u->getId()) {
                $thisone['createdby'] = $u->getPublic(NULL, FALSE);
            }

            $ret[] = $thisone;
        }

        return($ret);
    }

    public function getModeratorships() {
        $ret = [];
        $groups = $this->dbhr->preQuery("SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner');", [ $this->id ]);
        foreach ($groups as $group) {
            $ret[] = $group['groupid'];
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

        $sql = "SELECT settings, role FROM memberships WHERE userid = ? AND groupid = ?;";
        $sets = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);

        # Defaults match memberships ones in Group.php.
        $settings = [
            'showmessages' => 1,
            'showmembers' => 1
        ];

        foreach ($sets as $set) {
            if ($set['settings']) {
                $settings = json_decode($set['settings'], TRUE);

                if ($set['role'] == User::ROLE_OWNER || $set['role'] == User::ROLE_MODERATOR) {
                    $c = new ModConfig($this->dbhr, $this->dbhm);
                    $settings['configid'] = $c->getForGroup($this->id, $groupid);
                }
            }
        }

        return($settings);
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

    public function getPublic($groupids = NULL, $history = TRUE, $logs = FALSE, &$ctx = NULL, $comments = TRUE) {
        $atts = parent::getPublic();

        $atts['settings'] = presdef('settings', $atts, NULL) ? json_decode($atts['settings'], TRUE) : [];
        $me = whoAmI($this->dbhr, $this->dbhm);
        $systemrole = $me ? $me->getPrivate('systemrole') : User::SYSTEMROLE_USER;

        if ($history) {
            # Add in the message history - from any of the emails associated with this user.
            if ($groupids && count($groupids) > 0) {
                # On these groups
                $groupq = implode(',', $groupids);
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND groupid IN ($groupq) AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            } else {
                # On all groups.
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            }

            $atts['messagehistory'] = $this->dbhr->preQuery($sql, [
                MessageCollection::APPROVED,
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
        $mysqltime = date("Y-m-d", strtotime("Midnight 30 days ago"));
        $alarms = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);
        $atts['modmails'] = $alarms[0]['count'];

        if ($logs) {
            # Add in the log entries we have for this user.  We exclude some logs of little interest to mods.
            # - creation - either of ourselves or others during syncing.
            # - deletion of users due to syncing
            $me = whoAmI($this->dbhr, $this->dbhm);
            $startq = $ctx ? " AND id < {$ctx['id']} " : '';
            $sql = "SELECT DISTINCT * FROM logs WHERE (user = ? OR byuser = ?) $startq AND NOT (type = 'User' AND subtype = 'Created') AND (text IS NULL OR text NOT IN ('Not present on Yahoo', 'Sync of whole membership list')) ORDER BY id DESC LIMIT 50;";
            $logs = $this->dbhr->preQuery($sql, [ $this->id, $this->id ]);
            $atts['logs'] = [];
            $groups = [];
            $users = [];
            $configs = [];

            if (!$ctx) {
                $ctx = [ 'id' => 0 ];
            }

            foreach ($logs as $log) {
                $ctx['id'] = $ctx['id'] == 0 ? $log['id'] : min($ctx['id'], $log['id']);

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

                        if ($g->getId()) {
                            $groups[$log['groupid']] = $g->getPublic();
                            $groups[$log['groupid']]['myrole'] = $me ? $me->getRole($log['groupid']) : User::ROLE_NONMEMBER;
                        }
                    }

                    if ($g->getId() &&
                        $groups[$log['groupid']]['myrole'] != User::ROLE_OWNER &&
                        $groups[$log['groupid']]['myrole'] != User::ROLE_MODERATOR) {
                        # We can only see logs for this group if we have a mod role, or if we have appropriate system
                        # rights.  Skip this log.
                        break;
                    }

                    $log['group'] = presdef($log['groupid'], $groups, NULL);
                }

                if (pres('configid', $log)) {
                    if (!pres($log['configid'], $configs)) {
                        $c = new ModConfig($this->dbhr, $this->dbhm, $log['configid']);

                        if ($c->getId()) {
                            $configs[$log['configid']] = $c->getPublic();
                        }
                    }

                    if (pres($log['configid'], $configs)) {
                        $log['config'] = $configs[$log['configid']];
                    }
                }

                if (pres('stdmsgid', $log)) {
                    $s = new StdMessage($this->dbhr, $this->dbhm, $log['stdmsgid']);
                    $log['stdmsg'] = $s->getPublic();
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

        if ($this->id == $me->getId()) {
            # Add in private attributes for our own entry.
            $atts['email'] = $me->getEmailPreferred();
            $atts['emails'] = $me->getEmails();
        }

        if ($comments) {
            $atts['comments'] = $this->getComments();
        }

        if ($this->user['suspectcount'] > 0) {
            # This user is flagged as suspicious.  The memberships are visible iff the currently logged in user
            # - has a system role which allows it
            # - is a mod on a group which this user is also on.
            $visible = $systemrole == User::SYSTEMROLE_ADMIN || $systemrole == User::SYSTEMROLE_SUPPORT;
            $memberof = [];

            if (!$visible) {
                # Check the groups.
                $sql = "SELECT memberships.*, groups.nameshort, groups.namefull FROM memberships INNER JOIN groups ON memberships.groupid = groups.id WHERE userid = ?;";
                $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
                foreach ($groups as $group) {
                    $role = $me ? $me->getRole($group['groupid']) : User::ROLE_NONMEMBER;
                    $name = $group['namefull'] ? $group['namefull'] : $group['nameshort'];

                    $memberof[] = [
                        'id' => $group['groupid'],
                        'namedisplay' => $name,
                        'added' => ISODate($group['added'])
                    ];

                    if ($role == User::ROLE_OWNER || $role == User::ROLE_MODERATOR) {
                        $visible = TRUE;
                    }
                }
            }

            if ($visible) {
                $atts['suspectcount'] = $this->user['suspectcount'];
                $atts['suspectreason'] = $this->user['suspectreason'];
                $atts['memberof'] = $memberof;
            }
        }

        if (!array_key_exists('memberof', $atts) && ($systemrole == User::ROLE_OWNER || $systemrole == User::ROLE_MODERATOR)) {
            # We haven't provided the complete list; get the recent ones (which preserves some privacy for the user but
            # allows us to spot abuse) and any which are on our groups.
            $modids = array_merge([0], $me->getModeratorships());
            $sql = "SELECT memberships.*, groups.nameshort, groups.namefull FROM memberships INNER JOIN groups ON memberships.groupid = groups.id WHERE userid = ? AND (DATEDIFF(NOW(), added) <= 31 OR memberships.groupid IN (" . implode(',', $modids) . "));";
            $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
            $memberof = [];

            foreach ($groups as $group) {
                $name = $group['namefull'] ? $group['namefull'] : $group['nameshort'];

                $memberof[] = [
                    'id' => $group['groupid'],
                    'namedisplay' => $name,
                    'added' => ISODate($group['added'])
                ];
            }

            $atts['memberof'] = $memberof;
        }

        if ($systemrole == User::ROLE_OWNER || $systemrole == User::ROLE_MODERATOR) {
            # As well as being a member of a group, they might have joined and left, or applied and been rejected.
            # This is useful info for moderators.  If the user is suspicious then return the complete list; otherwise
            # just the recent ones.
            $groupq = ($groupids && count($groupids) > 0) ? (" AND groupid IN (" . implode(',', $groupids) . ") ") : '';
            $sql = "SELECT memberships_history.*, groups.nameshort, groups.namefull FROM memberships_history INNER JOIN groups ON memberships_history.groupid = groups.id WHERE userid = ? $groupq ORDER BY added DESC;";
            $membs = $this->dbhr->preQuery($sql, [ $this->id ]);
            foreach ($membs as &$memb) {
                $name = $memb['namefull'] ? $memb['namefull'] : $memb['nameshort'];
                $memb['namedisplay'] = $name;
                $memb['added'] = ISODate($memb['added']);
                $memb['id'] = $memb['groupid'];
                unset($memb['groupid']);
            }

            $atts['applied'] = $membs;
        }

        if ($systemrole == User::ROLE_MODERATOR ||
            $systemrole == User::SYSTEMROLE_ADMIN ||
            $systemrole == User::SYSTEMROLE_SUPPORT) {
            # Also fetch whether they're on the spammer list.
            $sql = "SELECT * FROM spam_users WHERE userid = ?;";
            $spammers = $this->dbhr->preQuery($sql, [ $this->id ]);
            foreach ($spammers as $spammer) {
                $spammer['added'] = ISODate($spammer['added']);
                $atts['spammer'] = $spammer;
            }
        }

        return($atts);
    }

    public function isAdminOrSupport() {
        return($this->user['systemrole'] == User::SYSTEMROLE_ADMIN || $this->user['systemrole'] == User::SYSTEMROLE_SUPPORT);
    }

    public function isModerator() {
        return($this->user['systemrole'] == User::SYSTEMROLE_ADMIN ||
            $this->user['systemrole'] == User::SYSTEMROLE_SUPPORT ||
            $this->user['systemrole'] == User::SYSTEMROLE_MODERATOR);
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

    public function roleMin($role1, $role2) {
        $role = User::ROLE_OWNER;

        if ($role1 == User::ROLE_MODERATOR || $role2 == User::ROLE_MODERATOR) {
            $role = User::ROLE_MODERATOR;
        }

        if ($role1 == User::ROLE_MEMBER || $role2 == User::ROLE_MEMBER) {
            $role = User::ROLE_MEMBER;
        }

        if ($role1 == User::ROLE_NONMEMBER || $role2 == User::ROLE_NONMEMBER) {
            $role = User::ROLE_NONMEMBER;
        }

        return($role);
    }

    public function merge($id1, $id2) {
        # We want to merge two users.  At present we just merge the memberships, emails and logs; we don't try to
        # merge any conflicting settings.
        #
        # Both users might have membership of the same group, including at different levels.
        #
        # A useful query to find foreign key references is of this form:
        #
        # USE information_schema; SELECT * FROM KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'iznik' AND REFERENCED_TABLE_NAME = 'users';
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

                if ($rc) {
                    # Merge other foreign keys where success is less important.
                    $this->dbhm->preExec("UPDATE locations_excluded SET userid = ? WHERE userid = ?;", [$id1, $id2]);
                    $this->dbhm->preExec("UPDATE spam_users SET userid = ? WHERE userid = ?;", [$id1, $id2]);
                    $this->dbhm->preExec("UPDATE spam_users SET byuserid = ? WHERE byuserid = ?;", [$id1, $id2]);
                    $this->dbhm->preExec("UPDATE users_banned SET userid = ? WHERE userid = ?;", [$id1, $id2]);
                    $this->dbhm->preExec("UPDATE users_banned SET byuser = ? WHERE byuser = ?;", [$id1, $id2]);
                    $this->dbhm->preExec("UPDATE users_logins SET userid = ? WHERE userid = ?;", [$id1, $id2]);
                }

                # Merge attributes we want to keep if we have them in id2 but not id1.  Some will have unique
                # keys, so update to delete them.
                foreach (['fullname', 'firstname', 'lastname', 'yahooUserId', 'yahooid', 'yahooUserId'] as $att) {
                    $users = $this->dbhm->preQuery("SELECT $att FROM users WHERE id = ?;", [ $id2 ]);
                    foreach ($users as $user) {
                        $this->dbhm->preExec("UPDATE users SET $att = NULL WHERE id = ?;", [ $id2 ]);
                        $this->dbhm->preExec("UPDATE users SET $att = ? WHERE id = ?;", [ $user[$att], $id1 ]);
                    }
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
                #error_log("Merge messages, current rc $rc");
                if ($rc) {
                    $this->dbhm->preExec("UPDATE messages SET fromuser = ? WHERE fromuser = ?;", [
                        $id1,
                        $id2
                    ]);
                }

                # Merge the history
                #error_log("Merge history, current rc $rc");
                if ($rc) {
                    $this->dbhm->preExec("UPDATE messages_history SET fromuser = ? WHERE fromuser = ?;", [
                        $id1,
                        $id2
                    ]);
                    $this->dbhm->preExec("UPDATE memberships_history SET userid = ? WHERE userid = ?;", [
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
                    #error_log("Merged $id1 into $id2");
                    $deleteme = new User($this->dbhr, $this->dbhm, $id2);
                    $rc = $deleteme->delete();
                }

                if ($rc) {
                    # Everything worked.
                    $rollback = FALSE;
                }
            } catch (Exception $e) {
                error_log("Merge exception " . $e->getMessage());
                $rollback = TRUE;
            }
        }

        if ($rollback) {
            # Something went wrong.
            #error_log("Merge failed, rollback");
            $this->dbhm->rollBack();
            $ret = FALSE;
        } else {
            #error_log("Merge worked, commit");
            $ret = $this->dbhm->commit();
       }

        return($ret);
    }

    # Default mailer is to use the standard PHP one, but this can be overridden in UT.
    private function mailer() {
        call_user_func_array('mail', func_get_args());
    }

    private function maybeMail($groupid, $subject, $body, $stdmsgid) {
        if ($subject) {
            # We have a mail to send.
            $to = $this->getEmailPreferred();
            $g = new Group($this->dbhr, $this->dbhm, $groupid);
            $atts = $g->getPublic();

            $me = whoAmI($this->dbhr, $this->dbhm);

            $name = $me->getName();

            # We can do a simple substitution in the from name.
            $name = str_replace('$groupname', $atts['namedisplay'], $name);

            $headers = "From: \"$name\" <" . $g->getModsEmail() . ">\r\n";

            $this->mailer(
                $to,
                $subject,
                $body,
                $headers,
                "-f" . $g->getModsEmail()
            );

            $s = new StdMessage($this->dbhr, $this->dbhm, $stdmsgid);
            $bcc = $s->getBcc();

            if ($bcc) {
                $bcc = str_replace('$groupname', $atts['nameshort'], $bcc);

                $this->mailer(
                    $bcc,
                    $subject,
                    $body,
                    $headers,
                    "-f" . $g->getModsEmail()
                );
            }
        }
    }

    public function mail($groupid, $subject, $body, $stdmsgid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_MAILED,
            'user' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $subject,
            'stdmsgid' => $stdmsgid
        ]);

        $this->maybeMail($groupid, $subject, $body, $stdmsgid);
    }

    public function reject($groupid, $subject, $body, $stdmsgid) {
        # No need for a transaction - if things go wrong, the member will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => $subject ? Log::SUBTYPE_REJECTED : Log::SUBTYPE_DELETED,
            'msgid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'user' => $this->getId(),
            'groupid' => $groupid,
            'text' => $subject,
            'stdmsgid' => $stdmsgid
        ]);

        $sql = "SELECT * FROM memberships WHERE userid = ? AND groupid = ? AND collection = ?;";
        $members = $this->dbhr->preQuery($sql, [ $this->id, $groupid, MembershipCollection::PENDING ]);
        foreach ($members as $member) {
            if (pres('yahooreject', $member)) {
                # We can trigger rejection by email - do so.
                $this->mailer($member['yahooreject'], "My name is Iznik and I reject this member", "", NULL, '-f' . MODERATOR_EMAIL);
            }

            if (pres('yahooUserId', $this->user)) {
                $sql = "SELECT email FROM users_emails INNER JOIN users ON users_emails.userid = users.id AND users.id = ?;";
                $emails = $this->dbhr->preQuery($sql, [ $this->id ]);
                $email = count($emails) > 0 ? $emails[0]['email'] : NULL;

                # It would be odd for them to be on Yahoo with no email but handle it anyway.
                if ($email) {
                    $p = new Plugin($this->dbhr, $this->dbhm);
                    $p->add($groupid, [
                        'type' => 'RejectPendingMember',
                        'id' => $this->user['yahooUserId'],
                        'email' => $email
                    ]);
                }
            }
        }

        $sql = "DELETE FROM memberships WHERE userid = ? AND groupid = ? AND collection = ?;";
        $this->dbhr->preExec($sql, [ $this->id, $groupid, MembershipCollection::PENDING ]);

        $this->maybeMail($groupid, $subject, $body, $stdmsgid);
    }

    public function approve($groupid, $subject, $body, $stdmsgid) {
        # No need for a transaction - if things go wrong, the member will remain in pending, which is the correct
        # behaviour.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_APPROVED,
            'msgid' => $this->id,
            'user' => $this->getId(),
            'byuser' => $me ? $me->getId() : NULL,
            'groupid' => $groupid,
            'stdmsgid' => $stdmsgid,
            'text' => $subject
        ]);

        $sql = "SELECT * FROM memberships WHERE userid = ? AND groupid = ? AND collection = ?;";
        $members = $this->dbhr->preQuery($sql, [ $this->id, $groupid, MembershipCollection::PENDING ]);
        foreach ($members as $member) {
            if (pres('yahooapprove', $member)) {
                # We can trigger approval by email - do so.
                $this->mailer($member['yahooapprove'], "My name is Iznik and I approvethis member", "", NULL, '-f' . MODERATOR_EMAIL);
            }

            if (pres('yahooUserId', $this->user)) {
                $sql = "SELECT email FROM users_emails INNER JOIN users ON users_emails.userid = users.id AND users.id = ?;";
                $emails = $this->dbhr->preQuery($sql, [ $this->id ]);
                $email = count($emails) > 0 ? $emails[0]['email'] : NULL;

                # It would be odd for them to be on Yahoo with no email but handle it anyway.
                if ($email) {
                    $p = new Plugin($this->dbhr, $this->dbhm);
                    $p->add($groupid, [
                        'type' => 'ApprovePendingMember',
                        'id' => $this->user['yahooUserId'],
                        'email' => $email
                    ]);
                }
            }
        }

        $sql = "UPDATE memberships SET collection = ? WHERE userid = ? AND groupid = ?;";
        $this->dbhm->preExec($sql, [
            MembershipCollection::APPROVED,
            $this->id,
            $groupid
        ]);

        $this->maybeMail($groupid, $subject, $body, $stdmsgid);
    }

    function hold($groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE memberships SET heldby = ? WHERE userid = ? AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $me->getId(), $this->id, $groupid ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_HOLD,
                'msgid' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }
    }

    function release($groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE memberships SET heldby = NULL WHERE userid = ? AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $this->id, $groupid ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_RELEASE,
                'msgid' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }
    }

    public function getComments() {
        # We can only see comments on groups on which we have mod status.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $groupids = $me ? $me->getModeratorships() : [];
        $groupids = count($groupids) == 0 ? [0] : $groupids;

        $sql = "SELECT * FROM users_comments WHERE userid = ? AND groupid IN (" . implode(',', $groupids) . ") ORDER BY date DESC;";
        $comments = $this->dbhr->preQuery($sql, [ $this->id ]);

        foreach ($comments as &$comment) {
            $comment['date'] = ISODate($comment['date']);

            if (pres('byuserid', $comment)) {
                $u = new User($this->dbhr, $this->dbhm, $comment['byuserid']);

                # Don't ask for comments to stop stack overflow.
                $ctx = NULL;
                $comment['byuser'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE);
            }
        }

        return($comments);
    }

    public function getComment($id) {
        # We can only see comments on groups on which we have mod status.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $groupids = $me ? $me->getModeratorships() : [];
        $groupids = count($groupids) == 0 ? [0] : $groupids;

        $sql = "SELECT * FROM users_comments WHERE id = ? AND groupid IN (" . implode(',', $groupids) . ") ORDER BY date DESC;";
        $comments = $this->dbhr->preQuery($sql, [ $id ]);

        foreach ($comments as &$comment) {
            $comment['date'] = ISODate($comment['date']);

            if (pres('byuserid', $comment)) {
                $u = new User($this->dbhr, $this->dbhm, $comment['byuserid']);
                $comment['byuser'] = $u->getPublic();
            }

            return($comment);
        }

        return(NULL);
    }

    public function addComment($groupid, $user1 = NULL, $user2 = NULL, $user3 = NULL, $user4 = NULL, $user5 = NULL,
                               $user6 = NULL, $user7 = NULL, $user8 = NULL, $user9 = NULL, $user10 = NULL,
                               $user11 = NULL, $byuserid = NULL, $checkperms = TRUE) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        # By any supplied user else logged in user if any.
        $byuserid = $byuserid ? $byuserid : ($me ? $me->getId() : NULL);

        # Can only add comments for a group on which we're a mod.
        $rc = NULL;
        $groups = $checkperms ? ($me ? $me->getModeratorships() : [0]) : [ $groupid ];
        foreach ($groups as $modgroupid) {
            if ($groupid == $modgroupid) {
                $sql = "INSERT INTO users_comments (userid, groupid, byuserid, user1, user2, user3, user4, user5, user6, user7, user8, user9, user10, user11) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
                $this->dbhm->preExec($sql, [
                    $this->id,
                    $groupid,
                    $byuserid,
                    $user1, $user2, $user3, $user4, $user5, $user6, $user7, $user8, $user9, $user10, $user11
                ]);
                $rc = $this->dbhm->lastInsertId();
            }
        }

        return($rc);
    }

    public function editComment($id, $user1 = NULL, $user2 = NULL, $user3 = NULL, $user4 = NULL, $user5 = NULL,
                               $user6 = NULL, $user7 = NULL, $user8 = NULL, $user9 = NULL, $user10 = NULL,
                               $user11 = NULL) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        # Update to logged in user if any.
        $byuserid = $me ? $me->getId() : NULL;

        # Can only edit comments for a group on which we're a mod.  This code isn't that efficient but it doesn't
        # happen often.
        $rc = NULL;
        $groups = $me ? $me->getModeratorships() : [0];
        foreach ($groups as $modgroupid) {
            $sql = "SELECT id FROM users_comments WHERE id = ? AND groupid = ?;";
            $comments = $this->dbhr->preQuery($sql, [ $id, $modgroupid ]);
            foreach ($comments as $comment) {
                $sql = "UPDATE users_comments SET byuserid = ?, user1 = ?, user2 = ?, user3 = ?, user4 = ?, user5 = ?, user6 = ?, user7 = ?, user8 = ?, user9 = ?, user10 = ?, user11 = ? WHERE id = ?;";
                $rc = $this->dbhm->preExec($sql, [
                    $byuserid,
                    $user1, $user2, $user3, $user4, $user5, $user6, $user7, $user8, $user9, $user10, $user11,
                    $comment['id']
                ]);
            }
        }

        return($rc);
    }

    public function deleteComment($id) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        # Can only delete comments for a group on which we're a mod.
        $rc = FALSE;
        $groups = $me ? $me->getModeratorships() : [];
        foreach ($groups as $modgroupid) {
            $rc = $this->dbhm->preExec("DELETE FROM users_comments WHERE id = ? AND groupid = ?;", [ $id, $modgroupid ]);
        }

        return($rc);
    }

    public function deleteComments() {
        $me = whoAmI($this->dbhr, $this->dbhm);

        # Can only delete comments for a group on which we're a mod.
        $rc = FALSE;
        $groups = $me ? $me->getModeratorships() : [];
        foreach ($groups as $modgroupid) {
            $rc = $this->dbhm->preExec("DELETE FROM users_comments WHERE userid = ? AND groupid = ?;", [ $this->id, $modgroupid ]);
        }

        return($rc);
    }

    public function delete($groupid = NULL, $subject = NULL, $body = NULL, $stdmsgid = NULL) {
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

            $this->maybeMail($groupid, $subject, $body, $stdmsgid);
        }

        return($rc);
    }
}