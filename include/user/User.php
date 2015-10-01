<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/session/Session.php');

class User extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'firstname', 'lastname', 'fullname', 'settings', 'systemrole');

    # Roles on specific groups
    const ROLE_NONMEMBER = 'Non-member';
    const ROLE_MEMBER = 'Member';
    const ROLE_MODERATOR = 'Moderator';
    const ROLE_OWNER = 'Owner';

    # Role on site
    const ROLE_SUPPORT = 'Support';
    const ROLE_ADMIN = 'Admin';

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
                'groupid' => $id,
                'text' => $this->getName()
            ]);

            return($id);
        } else {
            return(NULL);
        }
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
        # If the email already exists in the table, then that's fine.
        $rc = $this->dbhm->preExec("INSERT IGNORE INTO users_emails (userid, email, `primary`) VALUES (?, ?, ?)",
            [$this->id, $email, $primary]);
        return($rc);
    }

    public function removeEmail($email)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_emails WHERE userid = ? AND email LIKE ?;",
            [$this->id, $email]);
        return($rc);
    }

    public function addMembership($groupid, $role = User::ROLE_MEMBER) {
        $rc = $this->dbhm->preExec("REPLACE INTO memberships (userid, groupid, role) VALUES (?,?,?);",
            [
                $this->id,
                $groupid,
                $role
            ]);

        if ($rc) {
            $l = new Log($this->dbhr, $this->dbhm);
            $l->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_JOINED,
                'user' => $this->id,
                'groupid' => $groupid
            ]);
        }

        return($rc);
    }

    public function removeMembership($groupid) {
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
                'groupid' => $groupid
            ]);
        }

        return($rc);
    }

    public function getMemberships() {
        $ret = [];
        $groups = $this->dbhr->preQuery("SELECT groupid, role FROM memberships WHERE userid = ?;", [ $this->id ]);
        foreach ($groups as $group) {
            $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
            $one = $g->getPublic();
            $one['role'] = $group['role'];

            if ($one['role'] == User::ROLE_MODERATOR || $one['role'] == User::ROLE_OWNER) {
                # Give a summary of outstanding work.
                $one['work'] = $g->getWorkCounts();
            }

            $ret[] = $one;
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
        $groups = $this->dbhr->preQuery("SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner') AND groupid = ?;", [
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

    public function getRole($groupid) {
        # We can have a number of roles on a group
        # - none, we can only see what is member
        # - member, we are a group member and can see some extra info
        # - moderator, we can see most info on a group
        # - owner, we can see everything
        #
        # If our system role is support then we get moderator status; if it's admin we get owner status.
        $role = User::ROLE_NONMEMBER;

        switch ($this->getPrivate('systemrole')) {
            case User::ROLE_SUPPORT:
                $role = User::ROLE_MODERATOR;
                break;
            case User::ROLE_ADMIN:
                $role = User::ROLE_OWNER;
                break;
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

    public function setRole($role, $groupid) {
        error_log("Set role for $groupid to $role");
        $sql = "UPDATE memberships SET role = ? WHERE userid = ? AND groupid = ?;";
        $this->dbhm->preExec($sql, [
            $role,
            $this->id,
            $groupid
        ]);
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users WHERE id = ?;", [$this->id]);
        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_DELETED,
                'user' => $this->id,
                'text' => $this->getName()
            ]);
        }

        return($rc);
    }
}