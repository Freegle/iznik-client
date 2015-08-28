<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/Entity.php');

class User extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'firstname', 'lastname', 'fullname', 'settings', 'systemrole');

    const ROLE_USER = 'User';
    const ROLE_MODERATOR = 'Moderator';
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
                'group' => $id,
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
        # If the email already exists in the table, the insert will fail.
        try {
            $rc = $this->dbhm->preExec("INSERT INTO users_emails (userid, email, `primary`) VALUES (?, ?, ?)",
                [$this->id, $email, $primary]);
            return($rc);
        } catch (DBException $e) {
            return(false);
        }
    }

    public function removeEmail($email)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_emails WHERE userid = ? AND email LIKE ?;",
            [$this->id, $email]);
        return($rc);
    }

    public function getLogins() {
        $emails = $this->dbhr->preQuery("SELECT * FROM users_logins WHERE userid = ?;",
            [$this->id]);
        return($emails);
    }

    public function findByLogin($type, $uid) {
        $logins = $this->dbhr->preQuery("SELECT * FROM users_logins WHERE uid = ? AND type = ?;",
            [ $uid, $type]);
        foreach ($logins as $login) {
            return($login['userid']);
        }

        return(NULL);
    }

    public function addLogin($type, $uid)
    {
        # If the login with this type already exists in the table, the insert will fail.
        try {
            $rc = $this->dbhm->preExec("INSERT INTO users_logins (userid, uid, type) VALUES (?, ?, ?)",
                [$this->id, $uid, $type]);
            return($rc);
        } catch (DBException $e) {
            return(false);
        }
    }

    public function removeLogin($type, $uid)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_logins WHERE userid = ? AND type = ? AND uid LIKE ?;",
            [$this->id, $type, $uid]);
        return($rc);
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