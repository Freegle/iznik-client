<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/session/Session.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/spam/Spam.php');
require_once(IZNIK_BASE . '/include/config/ModConfig.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');
require_once(IZNIK_BASE . '/include/user/Notifications.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/mailtemplates/modtools/verifymail.php');
require_once(IZNIK_BASE . '/mailtemplates/welcome/withpassword.php');
require_once(IZNIK_BASE . '/mailtemplates/welcome/forgotpassword.php');
require_once(IZNIK_BASE . '/lib/wordle/functions.php');

class User extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'firstname', 'lastname', 'fullname', 'systemrole', 'settings', 'yahooid', 'yahooUserId', 'newslettersallowed');

    # Roles on specific groups
    const ROLE_NONMEMBER = 'Non-member';
    const ROLE_MEMBER = 'Member';
    const ROLE_MODERATOR = 'Moderator';
    const ROLE_OWNER = 'Owner';

    const HAPPY = 'Happy';
    const FINE = 'Fine';
    const UNHAPPY = 'Unhappy';

    # Role on site
    const SYSTEMROLE_SUPPORT = 'Support';
    const SYSTEMROLE_ADMIN = 'Admin';
    const SYSTEMROLE_USER = 'User';
    const SYSTEMROLE_MODERATOR = 'Moderator';

    const LOGIN_YAHOO = 'Yahoo';
    const LOGIN_FACEBOOK = 'Facebook';
    const LOGIN_GOOGLE = 'Google';
    const LOGIN_NATIVE = 'Native';
    const LOGIN_LINK = 'Link';

    /** @var  $log Log */
    private $log;
    var $user;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->log = new Log($dbhr, $dbhm);
        $this->notif = new Notifications($dbhr, $dbhm);
        
        $this->fetch($dbhr, $dbhm, $id, 'users', 'user', $this->publicatts);
    }

    # Override fetch for caching purposes.
    function fetch(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $table, $name, $publicatts)
    {
        if ($id) {
            if (pres('cache', $_SESSION) && $id == pres('id', $_SESSION)) {
                # We're getting our own user.  This is very common, even within a single API call, so cache it.
                #error_log(session_id() . " got me? " . var_export(pres('me', $_SESSION['cache']), TRUE));
                if (!pres('me', $_SESSION['cache'])) {
                    parent::fetch($dbhr, $dbhm, $id, 'users', 'user', $this->publicatts);
                    $_SESSION['cache']['me'] = $this->user;
                    #error_log(session_id() . " stored me " . $_SESSION['cache']['me']);
                } else {
                    parent::fetch($dbhr, $dbhm, NULL, 'users', 'user', $this->publicatts);
                    $this->id = $id;
                    $this->user = $_SESSION['cache']['me'];
                }
            } else {
                # Some other user - just fetch.
                parent::fetch($dbhr, $dbhm, $id, 'users', 'user', $this->publicatts);
            }
        } else {
            parent::fetch($dbhr, $dbhm, NULL, 'users', 'user', $this->publicatts);
        }
    }

    public function hashPassword($pw) {
        return sha1($pw . PASSWORD_SALT);
    }

    public function login($pw, $force = FALSE) {
        # TODO lockout
        if ($this->id) {
            $pw = $this->hashPassword($pw);
            $logins = $this->getLogins(TRUE);
            foreach ($logins as $login) {
                if ($force || ($login['type'] == User::LOGIN_NATIVE && $pw == $login['credentials'])) {
                    $s = new Session($this->dbhr, $this->dbhm);
                    $s->create($this->id);

                    # Anyone who has logged in to our site has given RIPA consent.
                    $this->dbhm->preExec("UPDATE users SET ripaconsent = 1 WHERE id = ?;",
                        [
                            $this->id
                        ]);

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

    public function linkLogin($key) {
        $ret = FALSE;

        if (presdef('id', $_SESSION, NULL) != $this->id) {
            # We're not already logged in as this user.
            $sql = "SELECT * FROM users_logins WHERE userid = ? AND type = ? AND credentials = ?;";
            $logins = $this->dbhr->preQuery($sql, [ $this->id, User::LOGIN_LINK, $key ]);
            foreach ($logins as $login) {
                # We found a match - log them in.
                $s = new Session($this->dbhr, $this->dbhm);
                $s->create($this->id);

                $l = new Log($this->dbhr, $this->dbhm);
                $l->log([
                    'type' => Log::TYPE_USER,
                    'subtype' => Log::SUBTYPE_LOGIN,
                    'byuser' => $this->id,
                    'text' => 'Using link'
                ]);

                $ret = TRUE;
            }
        }

        return($ret);
    }

    public function getToken() {
        $s = new Session($this->dbhr, $this->dbhm);
        return($s->getToken($this->id));
    }

    public function getName() {
        # We may or may not have the knowledge about how the name is split out, depending
        # on the sign-in mechanism.
        $name = NULL;
        if ($this->user['fullname']) {
            $name = $this->user['fullname'];
        } else {
            $name = $this->user['firstname'] . ' ' . $this->user['lastname'];
        }

        # Make sure we don't return an email if somehow one has snuck in.
        $name = strpos($name, '@') !== FALSE ? substr($name, 0, strpos($name, '@')) : $name;
        return($name);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($firstname, $lastname, $fullname, $reason = '', $yahooUserId = NULL, $yahooid = NULL) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        try {
            $rc = $this->dbhm->preExec("INSERT INTO users (firstname, lastname, fullname, yahooUserId, yahooid) VALUES (?, ?, ?, ?, ?)",
                [$firstname, $lastname, $fullname, $yahooUserId, $yahooid]);
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
                'text' => $this->getName() . " #$id " . $reason
            ]);

            return($id);
        } else {
            return(NULL);
        }
    }

    public function inventPassword() {
        $lengths  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/distinct_word_lengths.json'), true);
        $bigrams  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/word_start_bigrams.json'), true);
        $trigrams = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/trigrams.json'), true);

        $pw = '';
        
        do {
            $length = \Wordle\array_weighted_rand($lengths);
            $start  = \Wordle\array_weighted_rand($bigrams);
            $pw .= \Wordle\fill_word($start, $length, $trigrams);
        } while (strlen($pw) < 6);

        $pw = strtolower($pw);
        return($pw);
    }

    public function findByYahooUserId($id) {
        # Take care not to pick up empty or null else that will cause is to overmerge.
        $users = $this->dbhr->preQuery("SELECT id FROM users WHERE yahooUserId = ? AND yahooUserId IS NOT NULL AND LENGTH(yahooUserId) > 0;", [ $id ]);
        if (count($users) == 1) {
            return($users[0]['id']);
        }

        return(NULL);
    }

    public function getEmails() {
        # Don't return canon - don't need it on the client.
        $sql = "SELECT id, userid, email, preferred, added, validated FROM users_emails WHERE userid = ? ORDER BY preferred DESC, email ASC;";
        #error_log("$sql, {$this->id}");
        $emails = $this->dbhr->preQuery($sql, [$this->id]);
        return($emails);
    }

    public function getEmailPreferred() {
        # This gets the email address which we think the user actually uses.  So we pay attention to:
        # - the preferred flag, which gets set by end user action
        # - the date added, as most recently added emails are most likely to be right
        # - exclude our own invented mails
        $emails = $this->dbhr->preQuery("SELECT id, userid, email, preferred, added, validated FROM users_emails WHERE userid = ? ORDER BY preferred DESC, added DESC;",  
            [$this->id]);
        $ret = NULL;

        foreach ($emails as $email) {
            if (!ourDomain($email['email'])) {
                $ret = $email['email'];
                break;
            } 
        }
        
        return($ret);
    }

    public function getAnEmailId() {
        $emails = $this->dbhr->preQuery("SELECT id FROM users_emails WHERE userid = ? ORDER BY preferred DESC;",
            [$this->id]);
        return(count($emails) == 0 ? NULL : $emails[0]['id']);
    }

    public function isApprovedMember($groupid) {
        $membs = $this->dbhr->preQuery("SELECT id FROM memberships WHERE userid = ? AND groupid = ?;", [ $this->id, $groupid ]);
        return(count($membs) > 0);
    }

    public function getEmailForYahooGroup($groupid, $oursonly = FALSE) {
        # Any of the emails will do.
        $emails = $this->getEmailsForYahooGroup($groupid, $oursonly);
        $eid = count($emails) > 0 ? $emails[0][0] : NULL;
        $email = count($emails) > 0 ? $emails[0][1] : NULL;
        return([$eid, $email]);
    }

    public function getEmailsForYahooGroup($groupid, $oursonly = FALSE) {
        $emailq = "";

        if ($oursonly) {
            # We are looking for a group email which we host.
            foreach (explode(',', OURDOMAINS) as $domain) {
                $emailq .= $emailq == "" ? " email LIKE '%$domain'" : " OR email LIKE '%$domain'";
            }

            $emailq = " AND ($emailq)";
        }

        $sql = "SELECT memberships_yahoo.emailid, users_emails.email FROM memberships_yahoo INNER JOIN memberships ON memberships.id = memberships_yahoo.membershipid INNER JOIN users_emails ON memberships_yahoo.emailid = users_emails.id WHERE memberships.userid = ? AND groupid = ? $emailq;";
        #error_log($sql . ", {$this->id}, $groupid");
        $emails = $this->dbhr->preQuery($sql, [
            $this->id,
            $groupid
        ]);

        $ret = [];
        foreach ($emails as $email) {
            $ret[] = [ $email['emailid'], $email['email'] ];
        }

        return($ret);
    }

    public function getIdForEmail($email) {
        # Email is a unique key but conceivably we could be called with an email for another user.
        $ids = $this->dbhr->preQuery("SELECT id, userid FROM users_emails WHERE canon = ?;", [
            User::canonMail($email)
        ]);

        foreach ($ids as $id) {
            return($id);
        }

        return(NULL);
    }

    public function getEmailById($id) {
        # Email is a unique key but conceivably we could be called with an email for another user.
        $emails = $this->dbhr->preQuery("SELECT email FROM users_emails WHERE id = ?;", [
            User::canonMail($id)
        ]);

        $ret = NULL;

        foreach ($emails as $email) {
            $ret = $email['email'];
        }

        return($ret);
    }

    public function findByEmail($email) {
        # Take care not to pick up empty or null else that will cause is to overmerge.
        #
        # Use canon to match - that handles variant TN addresses or % addressing.
        $users = $this->dbhr->preQuery("SELECT userid FROM users_emails WHERE canon = ? AND canon IS NOT NULL AND LENGTH(canon) > 0;",
            [ User::canonMail($email) ]);

        foreach ($users as $user) {
            return($user['userid']);
        }

        return(NULL);
    }

    public function findByYahooId($id) {
        # Take care not to pick up empty or null else that will cause is to overmerge.
        $users = $this->dbhr->preQuery("SELECT id FROM users WHERE yahooid = ? AND yahooid IS NOT NULL AND LENGTH(yahooid) > 0;",
            [ $id ]);

        foreach ($users as $user) {
            return($user['id']);
        }

        return(NULL);
    }

    public static function canonMail($email) {
        # Canonicalise TN addresses.
        if (preg_match('/(.*)\-(.*)(@user.trashnothing.com)/', $email, $matches)) {
            $email = $matches[1] . $matches[3];
        }

        # Remove plus addressing, which is sometimes used by spammers as a trick, except for Facebook where it
        # appears to be genuinely used for routing to distinct users.
        if (preg_match('/(.*)\+(.*)(@.*)/', $email, $matches) && strpos($email, '@proxymail.facebook.com') === FALSE) {
            $email = $matches[1] . $matches[3];
        }

        # Remove dots, which are ignored by gmail and can therefore be used to give the appearance of separate
        # emails.
        $email = str_replace('.', '', $email);

        return($email);
    }

    public function addEmail($email, $primary = 1, $changeprimary = TRUE)
    {
        if (stripos($email, '-owner@yahoogroups.co') !== FALSE) {
            # We don't allow people to add Yahoo owner addresses as the address of an individual user.
            $rc = NULL;
        } else {
            # If the email already exists in the table, then that's fine.  But we don't want to use INSERT IGNORE as
            # that scales badly for clusters.
            $canon = User::canonMail($email);

            # Don't cache - lots of emails so don't want to flood the query cache.
            $sql = "SELECT SQL_NO_CACHE id, preferred FROM users_emails WHERE userid = ? AND email = ?;";
            $emails = $this->dbhm->preQuery($sql, [
                $this->id,
                $email
            ]);

            if (count($emails) == 0) {
                $sql = "INSERT IGNORE INTO users_emails (userid, email, preferred, canon, backwards) VALUES (?, ?, ?, ?, ?)";
                $rc = $this->dbhm->preExec($sql,
                    [$this->id, $email, $primary, $canon, strrev($canon)]);
                $rc = $this->dbhm->lastInsertId();

                if ($rc && $primary) {
                    # Make sure no other email is flagged as primary
                    $this->dbhm->preExec("UPDATE users_emails SET preferred = 0 WHERE userid = ? AND id != ?;", [
                        $this->id,
                        $rc
                    ]);
                }
            } else {
                $rc = $emails[0]['id'];

                if ($changeprimary && $primary != $emails[0]['preferred']) {
                    # Change in status.
                    $this->dbhm->preExec("UPDATE users_emails SET preferred = ? WHERE id = ?;", [
                        $primary,
                        $rc
                    ]);
                }

                if ($primary) {
                    # Make sure no other email is flagged as primary
                    $this->dbhm->preExec("UPDATE users_emails SET preferred = 0 WHERE userid = ? AND id != ?;", [
                        $this->id,
                        $rc
                    ]);
                }
            }
        }

        return($rc);
    }

    public function removeEmail($email)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_emails WHERE userid = ? AND email = ?;",
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

        # We don't want to use REPLACE INTO because the membershipid is a foreign key in some tables (such as
        # memberships_yahoo), and if the membership already exists, then this would cause us to delete and re-add it,
        # which would result in the row in the child table being deleted.
        #
        #error_log("Add membership {$this->id} to $groupid with $emailid");
        $rc = $this->dbhm->preExec("INSERT INTO memberships (userid, groupid, role, collection) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), role = ?;", [
            $this->id,
            $groupid,
            $role,
            $collection,
            $role
        ]);
        $membershipid = $this->dbhm->lastInsertId();
        #error_log("Insert returned $rc membership $membershipid");

        if ($rc && $emailid) {
            $sql = "REPLACE INTO memberships_yahoo (membershipid, role, emailid, collection) VALUES (?,?,?,?);";
            $rc = $this->dbhm->preExec($sql, [
                $membershipid,
                $role,
                $emailid,
                $collection
            ]);
        }

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

    public function getMembershipAtt($groupid, $att) {
        $sql = "SELECT * FROM memberships WHERE groupid = ? AND userid = ?;";
        $val = NULL;
        $membs = $this->dbhr->preQuery($sql , [ $groupid, $this->id ]);
        foreach ($membs as $memb) {
            $val = presdef($att, $memb, NULL);
        }

        return($val);
    }

    public function setMembershipAtt($groupid, $att, $val) {
        Session::clearSessionCache();
        $sql = "UPDATE memberships SET $att = ? WHERE groupid = ? AND userid = ?;";
        $rc = $this->dbhm->preExec($sql, [
            $val,
            $groupid,
            $this->id
        ]);

        return($rc);
    }

    public function setYahooMembershipAtt($groupid, $emailid, $att, $val) {
        $sql = "UPDATE memberships_yahoo SET $att = ? WHERE membershipid = (SELECT id FROM memberships WHERE userid = ? AND groupid = ?) AND emailid = ?;";
        $rc = $this->dbhm->preExec($sql, [
            $val,
            $this->id,
            $groupid,
            $emailid
        ]);

        return($rc);
    }

    public function removeMembership($groupid, $ban = FALSE, $spam = FALSE) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        $meid = $me ? $me->getId() : NULL;

        # Trigger removal of any Yahoo memberships first.
        $sql = "SELECT email FROM users_emails LEFT JOIN memberships_yahoo ON users_emails.id = memberships_yahoo.emailid INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id AND memberships.groupid = ? WHERE users_emails.userid = ?;";
        $emails = $this->dbhr->preQuery($sql, [ $groupid, $this->id ]);
        #error_log("$sql, $groupid, {$this->id}");

        foreach ($emails as $email) {
            #error_log("Remove #$groupid {$email['email']}");
            if ($ban) {
                $type = 'BanApprovedMember';
            } else {
                $type = $this->isPending($groupid) ? 'RemovePendingMember' : 'RemoveApprovedMember';
            }

            # It would be odd for them to be on Yahoo with no email but handle it anyway.
            if ($email['email']) {
                $g = new Group($this->dbhr, $this->dbhm, $groupid);
                $p = new Plugin($this->dbhr, $this->dbhm);
                $p->add($groupid, [
                    'type' => $type,
                    'email' => $email['email']
                ]);
                
                if (ourDomain($email['email'])) {
                    # This is an email address we host, so we can email an unsubscribe request.  We do both this and
                    # the plugin work because Yahoo is as flaky as all get out.
                    for ($i = 0; $i < 10; $i++) {
                        list ($transport, $mailer) = getMailer();
                        $message = Swift_Message::newInstance()
                            ->setSubject('Please release me')
                            ->setFrom([$email['email']])
                            ->setTo($g->getGroupUnsubscribe())
                            ->setDate(time())
                            ->setBody('Let me go');
                        $mailer->send($message);
                    }
                }
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
            'text' => $spam ? "Autoremoved spammer" : ($ban ? "via ban" : NULL)
        ]);

        # Now remove the membership.
        $rc = $this->dbhm->preExec("DELETE FROM memberships WHERE userid = ? AND groupid = ?;",
            [
                $this->id,
                $groupid
            ]);

        return($rc);
    }

    public function getMemberships($modonly = FALSE, $grouptype = NULL) {
        $ret = [];
        $modq = $modonly ? " AND role IN ('Owner', 'Moderator') " : "";
        $typeq = $grouptype ? (" AND `type` = " . $this->dbhr->quote($grouptype)) : '';
        $sql = "SELECT groupid, role, configid, CASE WHEN namefull IS NOT NULL THEN namefull ELSE nameshort END AS namedisplay FROM memberships INNER JOIN groups ON groups.id = memberships.groupid AND groups.publish = 1 WHERE userid = ? $modq $typeq ORDER BY LOWER(namedisplay) ASC;";
        $groups = $this->dbhr->preQuery($sql, [ $this->id ]);

        $c = new ModConfig($this->dbhr, $this->dbhm);

        $cache = new Redis();
        $cache->pconnect(REDIS_CONNECT);

        foreach ($groups as $group) {
            $g = NULL;
            # We cache the groups in redis - there are a lot of ops involved in getting all of our groups.
            $cachekey = "group-{$group['groupid']}";
            $cached = $cache->get($cachekey);
            if ($cached) {
                $one = json_decode($cached, TRUE);
            } else {
                $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
                $one = $g->getPublic();
                $cache->setex($cachekey, REDIS_TTL, json_encode($one));
            }

            $one['role'] = $group['role'];
            $one['configid'] = $c->getForGroup($this->id, $group['groupid']);

            $one['mysettings'] = $this->getGroupSettings($group['groupid']);

            # We only need finding out how much work there is if we are interested in seeing it.
            $showmessages = !array_key_exists('showmessages', $one['mysettings']) || $one['mysettings']['showmessages'];
            $showmembers = !array_key_exists('showmembers', $one['mysettings']) || $one['mysettings']['showmembers'];

            if ((($one['role'] == User::ROLE_MODERATOR || $one['role'] == User::ROLE_OWNER)) &&
                ($showmessages || $showmembers)) {
                if (!$g) {
                    # We need to have an actual group object for this.
                    $g = new Group($this->dbhr, $this->dbhm, $group['groupid']);
                }

                # Give a summary of outstanding work.
                $one['work'] = $g->getWorkCounts($one['mysettings'], $this->id);
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
        $modships = $me ? $this->getModeratorships() : [];
        $modships = count($modships) > 0 ? $modships : [0];

        $sql = "SELECT DISTINCT * FROM ((SELECT configid AS id FROM memberships WHERE groupid IN (" . implode(',', $modships) . ") AND role IN ('Owner', 'Moderator') AND configid IS NOT NULL) UNION (SELECT id FROM mod_configs WHERE createdby = {$this->id} OR `default` = 1)) t;";
        $ids = $this->dbhr->preQuery($sql);
        $cache = new Redis();
        $cache->pconnect(REDIS_CONNECT);

        foreach ($ids as $id) {
            # We cache the configs in redis - there are a lot of ops involved in getting the config and all its standard messages.
            $cachekey = "modconfig-{$id['id']}";
            $cached = $cache->get($cachekey);
            if ($cached) {
                $thisone = json_decode($cached, TRUE);
            } else {
                $c = new ModConfig($this->dbhr, $this->dbhm, $id['id']);
                $thisone = $c->getPublic(FALSE);
                $cache->setex($cachekey, REDIS_TTL, json_encode($thisone));
            }

            if ($me) {
                if ($thisone['createdby'] == $me->getId()) {
                    $thisone['cansee'] = ModConfig::CANSEE_CREATED;
                } else if ($thisone['default']) {
                    $thisone['cansee'] = ModConfig::CANSEE_DEFAULT;
                } else {
                    # Need to find out who shared it
                    $sql = "SELECT userid, groupid FROM memberships WHERE groupid IN (" . implode(',', $modships) . ") AND userid != {$this->id} AND role IN ('Moderator', 'Owner') AND configid = {$id['id']};";
                    $shareds = $this->dbhr->preQuery($sql);

                    foreach ($shareds as $shared) {
                        $thisone['cansee'] = ModConfig::CANSEE_SHARED;
                        $u = new User($this->dbhr, $this->dbhm, $shared['userid']);
                        $g = new Group($this->dbhr, $this->dbhm, $shared['groupid']);
                        $ctx = NULL;
                        $thisone['sharedby'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE);
                        $thisone['sharedon'] = $g->getPublic();
                    }
                }
            }

            $u = new User($this->dbhr, $this->dbhm, $thisone['createdby']);

            if ($u->getId()) {
                $ctx = NULL;
                $thisone['createdby'] = $u->getPublic(NULL, FALSE, FALSE, $ctx, FALSE, FALSE, FALSE);

                # Remove their email list - which might be long - to save space.
                unset($thisone['createdby']['emails']);
            }

            $ret[] = $thisone;
        }

        return($ret);
    }

    public function getModeratorships() {
        # This gets called a lot - cache it.
        $key = "memberships.{$this->id}";
        if (!pres($key, $_SESSION['cache'])) {
            $ret = [];
            $groups = $this->dbhr->preQuery("SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Moderator', 'Owner');", [ $this->id ]);
            foreach ($groups as $group) {
                $ret[] = $group['groupid'];
            }

            $_SESSION['cache'][$key] = $ret;
        }

        return($_SESSION['cache'][$key]);
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

    public function getLogins($credentials = TRUE) {
        $logins = $this->dbhr->preQuery("SELECT * FROM users_logins WHERE userid = ?;",
            [$this->id]);

        foreach ($logins as &$login) {
            if (!$credentials) {
                unset($login['credentials']);
            }
            $login['added'] = ISODate($login['added']);
            $login['lastaccess'] = ISODate($login['lastaccess']);
        }

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
        $rc = $this->dbhm->preExec("INSERT INTO users_logins (userid, uid, type, credentials) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE credentials = ?;",
            [$this->id, $uid, $type, $creds, $creds]);

        # If we add a login, we might be about to log in.
        # TODO This is a bit hacky.  
        global $sessionPrepared;
        $sessionPrepared = FALSE;

        return($rc);
    }

    public function removeLogin($type, $uid)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_logins WHERE userid = ? AND type = ? AND uid = ?;",
            [$this->id, $type, $uid]);
        return($rc);
    }

    public function getRoleForGroup($groupid, $overrides = TRUE) {
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

    public function moderatorForUser($userid) {
        # There are times when we want to check whether we can administer a user, but when we are not immediately
        # within the context of a known group.  We can administer a user when:
        # - they're only a user themselves
        # - we are a mod on one of the groups on which they are a member.
        $u = new User($this->dbhr, $this->dbhm, $userid);

        $usermemberships = [];
        $groups = $this->dbhr->preQuery("SELECT groupid FROM memberships WHERE userid = ? AND role IN ('Member');", [ $userid ]);
        foreach ($groups as $group) {
            $usermemberships[] = $group['groupid'];
        }

        $mymodships = $this->getModeratorships();

        # Is there any group which we mod and which they are a member of?
        #error_log("Compare groups " . var_export($usermemberships, TRUE) . " vs " . var_export($mymodships, TRUE));
        $canmod = count(array_intersect($usermemberships, $mymodships)) > 0;

        return($canmod);
    }

    public function setGroupSettings($groupid, $settings) {
        $sql = "UPDATE memberships SET settings = ? WHERE userid = ? AND groupid = ?;";
        return($this->dbhm->preExec($sql, [
            json_encode($settings),
            $this->id,
            $groupid
        ]));
    }

    public function getGroupSettings($groupid) {

        $sql = "SELECT settings, role, emailfrequency FROM memberships WHERE userid = ? AND groupid = ?;";
        $sets = $this->dbhr->preQuery($sql, [ $this->id, $groupid ]);

        # Defaults match memberships ones in Group.php.
        $defaults = [
            'showmessages' => 1,
            'showmembers' => 1,
            'showchat' => 1,
            'pushnotify' => 1
        ];

        $settings = $defaults;

        foreach ($sets as $set) {
            if ($set['settings']) {
                $settings = json_decode($set['settings'], TRUE);

                if ($set['role'] == User::ROLE_OWNER || $set['role'] == User::ROLE_MODERATOR) {
                    $c = new ModConfig($this->dbhr, $this->dbhm);
                    $settings['configid'] = $c->getForGroup($this->id, $groupid);
                }
            }

            foreach ($defaults as $key => $val) {
                if (!array_key_exists($key, $settings)) {
                    $settings[$key] = $val;
                }
            }

            $settings['emailfrequency'] = $set['emailfrequency'];
        }

        return($settings);
    }

    public function setRole($role, $groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);
        
        Session::clearSessionCache();

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

    public function getPublic($groupids = NULL, $history = TRUE, $logs = FALSE, &$ctx = NULL, $comments = TRUE, $memberof = TRUE, $applied = TRUE, $modmailsonly = FALSE) {
        $atts = parent::getPublic();

        $atts['settings'] = presdef('settings', $atts, NULL) ? json_decode($atts['settings'], TRUE) : [ 'dummy' => TRUE ];
        $me = whoAmI($this->dbhr, $this->dbhm);
        $systemrole = $me ? $me->getPrivate('systemrole') : User::SYSTEMROLE_USER;

        if ($history) {
            # Add in the message history - from any of the emails associated with this user.
            $atts['messagehistory'] = [];
            $sql = NULL;

            if ($groupids && count($groupids) > 0) {
                # On these groups
                $groupq = implode(',', $groupids);
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND groupid IN ($groupq) AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            } else if ($systemrole == User::SYSTEMROLE_SUPPORT || $systemrole == User::SYSTEMROLE_ADMIN) {
                # We can see all groups.
                $sql = "SELECT messages.id, messages.arrival, messages.date, messages.subject, messages.type, DATEDIFF(NOW(), messages.date) AS daysago, messages_groups.groupid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.collection = ? AND fromuser = ? AND messages_groups.deleted = 0 ORDER BY messages.arrival DESC;";
            }

            if ($sql) {
                $atts['messagehistory'] = $this->dbhr->preQuery($sql, [
                    MessageCollection::APPROVED,
                    $this->id
                ]);

                foreach ($atts['messagehistory'] as &$hist) {
                    $hist['arrival'] = ISODate($hist['arrival']);
                    $hist['date'] = ISODate($hist['date']);
                }
            }
        }

        # Add in a count of recent "modmail" type logs which a mod might care about.
        #
        # Exclude the logs which are due to standard message syncing.
        $modships = $me ? $me->getModeratorships() : [];
        $modships = count($modships) == 0 ? [0] : $modships;
        $modmailq = " AND ((type = 'Message' AND subtype IN ('Rejected', 'Deleted', 'Replied')) OR (type = 'User' AND subtype IN ('Mailed', 'Rejected', 'Deleted'))) AND text NOT IN ('Not present on Yahoo','Received later copy of message with same Message-ID')";
        $sql = "SELECT COUNT(*) AS count FROM `logs` WHERE user = ? AND timestamp > ? $modmailq AND groupid IN (" . implode(',', $modships) . ");";
        $mysqltime = date("Y-m-d", strtotime("Midnight 30 days ago"));
        $modmails = $this->dbhr->preQuery($sql, [ $this->id, $mysqltime ]);
        #error_log("$sql, {$this->id}, $mysqltime");
        $atts['modmails'] = $modmails[0]['count'];

        if ($logs) {
            # Add in the log entries we have for this user.  We exclude some logs of little interest to mods.
            # - creation - either of ourselves or others during syncing.
            # - deletion of users due to syncing
            $me = whoAmI($this->dbhr, $this->dbhm);
            $startq = $ctx ? " AND id < {$ctx['id']} " : '';
            $modq = $modmailsonly ? $modmailq : '';
            $sql = "SELECT DISTINCT * FROM logs WHERE (user = ? OR byuser = ?) $startq AND NOT (type = 'User' AND subtype IN('Created', 'Merged', 'YahooConfirmed')) AND (text IS NULL OR text NOT IN ('Not present on Yahoo', 'Sync of whole membership list','Received later copy of message with same Message-ID')) $modq ORDER BY id DESC LIMIT 50;";
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
                            $groups[$log['groupid']]['myrole'] = $me ? $me->getRoleForGroup($log['groupid']) : User::ROLE_NONMEMBER;
                        }
                    }

                    if ($g->getId() &&
                        $groups[$log['groupid']]['myrole'] != User::ROLE_OWNER &&
                        $groups[$log['groupid']]['myrole'] != User::ROLE_MODERATOR) {
                        # We can only see logs for this group if we have a mod role, or if we have appropriate system
                        # rights.  Skip this log.
                        continue;
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
                    $m = new Message($this->dbhr, $this->dbhm, $log['msgid']);

                    if ($m->getID()) {
                        $log['message'] = $m->getPublic(FALSE);
                    } else {
                        # The message has been deleted.
                        $log['message'] = [
                            'id' => $log['msgid'],
                            'deleted' => true
                        ];

                        # See if we can find out why.
                        $sql = "SELECT * FROM logs WHERE msgid = ? AND type = 'Message' AND subtype = 'Deleted' ORDER BY id DESC LIMIT 1;";
                        $deletelogs = $this->dbhr->preQuery($sql, [ $log['msgid'] ]);
                        foreach ($deletelogs as $deletelog) {
                            $log['message']['deletereason'] = $deletelog['text'];
                        }
                    }

                    # Prune large attributes.
                    unset($log['message']['textbody']);
                    unset($log['message']['htmlbody']);
                    unset($log['message']['message']);
                }

                $log['timestamp'] = ISODate($log['timestamp']);

                $atts['logs'][] = $log;
            }

            # Get merge history
            $ids = [ $this->id ];
            $merges = [];
            do {
                $added = FALSE;
                $sql = "SELECT * FROM logs WHERE type = 'User' AND subtype = 'Merged' AND user IN (" . implode(',', $ids) . ");";
                $logs = $this->dbhr->preQuery($sql);
                foreach ($logs as $log) {
                    #error_log("Consider merge log {$log['text']}");
                    if (preg_match('/Merged (.*) into (.*?) \((.*)\)/', $log['text'], $matches)) {
                        #error_log("Matched " . var_export($matches, TRUE));
                        #error_log("Check ids {$matches[1]} and {$matches[2]}");
                        foreach ([ $matches[1], $matches[2] ] as $id) {
                            if (!in_array($id, $ids, TRUE)) {
                                $added = TRUE;
                                $ids[] = $id;
                                $merges[] = [ 'timestamp' => ISODate($log['timestamp']), 'from' => $matches[1], 'to' => $matches[2], 'reason' => $matches[3] ];
                            }
                        }
                    }
                }
            } while ($added);

            $atts['merges'] = $merges;
        }

        $atts['displayname'] = $this->getName();

        if ($me && $this->id == $me->getId()) {
            # Add in private attributes for our own entry.
            $atts['emails'] = $me->getEmails();
            $atts['email'] = $me->getEmailPreferred();
        }

        if ($me && $me->isModerator()) {
            # Mods can see email settings, no matter which group.
            $atts['onholidaytill'] = $this->user['onholidaytill'] ? ISODate($this->user['onholidaytill']) : NULL;
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

            # Check the groups.
            $sql = "SELECT memberships.*, memberships_yahoo.emailid, groups.nameshort, groups.namefull, groups.type FROM memberships LEFT JOIN memberships_yahoo ON memberships.id = memberships_yahoo.membershipid INNER JOIN groups ON memberships.groupid = groups.id WHERE userid = ?;";
            $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
            foreach ($groups as $group) {
                $role = $me ? $me->getRoleForGroup($group['groupid']) : User::ROLE_NONMEMBER;
                $name = $group['namefull'] ? $group['namefull'] : $group['nameshort'];

                $memberof[] = [
                    'id' => $group['groupid'],
                    'membershipid' => $group['id'],
                    'namedisplay' => $name,
                    'nameshort' => $group['nameshort'],
                    'added' => ISODate($group['added']),
                    'collection' => $group['collection'],
                    'role' => $group['role'],
                    'emailid' => $group['emailid'],
                    'emailfrequency' => $group['emailfrequency'],
                    'eventsallowed' => $group['eventsallowed'],
                    'type' => $group['type']
                ];

                if ($role == User::ROLE_OWNER || $role == User::ROLE_MODERATOR) {
                    $visible = TRUE;
                }
            }

            if ($visible) {
                $atts['suspectcount'] = $this->user['suspectcount'];
                $atts['suspectreason'] = $this->user['suspectreason'];
                $atts['memberof'] = $memberof;
            }
        }

        $box = NULL;

        if ($memberof && !array_key_exists('memberof', $atts) &&
            ($systemrole == User::ROLE_MODERATOR || $systemrole == User::SYSTEMROLE_ADMIN || $systemrole == User::SYSTEMROLE_SUPPORT)) {
            # We haven't provided the complete list; get the recent ones (which preserves some privacy for the user but
            # allows us to spot abuse) and any which are on our groups.
            $addmax = ($systemrole == User::SYSTEMROLE_ADMIN || $systemrole == User::SYSTEMROLE_SUPPORT) ? PHP_INT_MAX : 31;
            $modids = array_merge([0], $me->getModeratorships());
            $sql = "SELECT DISTINCT memberships.*, memberships_yahoo.emailid, groups.nameshort, groups.namefull, groups.lat, groups.lng, groups.type FROM memberships LEFT JOIN memberships_yahoo ON memberships.id = memberships_yahoo.membershipid INNER JOIN groups ON memberships.groupid = groups.id WHERE userid = ? AND (DATEDIFF(NOW(), memberships.added) <= $addmax OR memberships.groupid IN (" . implode(',', $modids) . "));";
            $groups = $this->dbhr->preQuery($sql, [ $this->id ]);
            $memberof = [];

            foreach ($groups as $group) {
                $name = $group['namefull'] ? $group['namefull'] : $group['nameshort'];

                $memberof[] = [
                    'id' => $group['groupid'],
                    'membershipid' => $group['id'],
                    'namedisplay' => $name,
                    'nameshort' => $group['nameshort'],
                    'added' => ISODate($group['added']),
                    'collection' => $group['collection'],
                    'role' => $group['role'],
                    'emailid' => $group['emailid'],
                    'emailfrequency' => $group['emailfrequency'],
                    'eventsallowed' => $group['eventsallowed'],
                    'type' => $group['type']
                ];

                if ($group['lat'] && $group['lng']) {
                    $box = [
                        'swlat' => $box == NULL ? $group['lat'] : min($group['lat'], $box['swlat']),
                        'swlng' => $box == NULL ? $group['lng'] : min($group['lng'], $box['swlng']),
                        'nelng' => $box == NULL ? $group['lng'] : max($group['lng'], $box['nelng']),
                        'nelat' => $box == NULL ? $group['lat'] : max($group['lat'], $box['nelat'])
                    ];
                }
            }

            $atts['memberof'] = $memberof;
        }

        if ($applied &&
            $systemrole == User::ROLE_MODERATOR ||
            $systemrole == User::SYSTEMROLE_ADMIN ||
            $systemrole == User::SYSTEMROLE_SUPPORT) {
            # As well as being a member of a group, they might have joined and left, or applied and been rejected.
            # This is useful info for moderators.  If the user is suspicious then return the complete list; otherwise
            # just the recent ones.
            $groupq = ($groupids && count($groupids) > 0) ? (" AND (DATEDIFF(NOW(), added) <= 31 OR groupid IN (" . implode(',', $groupids) . ")) ") : ' AND DATEDIFF(NOW(), added) <= 31 ';
            $sql = "SELECT DISTINCT memberships_history.*, groups.nameshort, groups.namefull, groups.lat, groups.lng FROM memberships_history INNER JOIN groups ON memberships_history.groupid = groups.id WHERE userid = ? $groupq ORDER BY added DESC;";
            $membs = $this->dbhr->preQuery($sql, [ $this->id ]);
            foreach ($membs as &$memb) {
                $name = $memb['namefull'] ? $memb['namefull'] : $memb['nameshort'];
                $memb['namedisplay'] = $name;
                $memb['added'] = ISODate($memb['added']);
                $memb['id'] = $memb['groupid'];
                unset($memb['groupid']);

                if ($memb['lat'] && $memb['lng']) {
                    $box = [
                        'swlat' => $box == NULL ? $memb['lat'] : min($memb['lat'], $box['swlat']),
                        'swlng' => $box == NULL ? $memb['lng'] : min($memb['lng'], $box['swlng']),
                        'nelng' => $box == NULL ? $memb['lng'] : max($memb['lng'], $box['nelng']),
                        'nelat' => $box == NULL ? $memb['lat'] : max($memb['lat'], $box['nelat'])
                    ];
                }
            }

            $atts['applied'] = $membs;
            $atts['activearea'] = $box;
            $atts['activedistance'] = $box ? round(Location::getDistance($box['swlat'], $box['swlng'], $box['nelat'], $box['nelng'])) : NULL;
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

    public static function getSessions($dbhr, $dbhm, $id) {
        $e = new Events($dbhr, $dbhm);
        $sessions = $e->listSessions($id);
        return($sessions);
    }

    public function isAdmin() {
        return($this->user['systemrole'] == User::SYSTEMROLE_ADMIN);
    }

    public function isAdminOrSupport() {
        return($this->user['systemrole'] == User::SYSTEMROLE_ADMIN || $this->user['systemrole'] == User::SYSTEMROLE_SUPPORT);
    }

    public function isModerator() {
        return($this->user['systemrole'] == User::SYSTEMROLE_ADMIN ||
            $this->user['systemrole'] == User::SYSTEMROLE_SUPPORT ||
            $this->user['systemrole'] == User::SYSTEMROLE_MODERATOR);
    }

    public function systemRoleMax($role1, $role2) {
        $role = User::SYSTEMROLE_USER;

        if ($role1 == User::SYSTEMROLE_MODERATOR || $role2 == User::SYSTEMROLE_MODERATOR) {
            $role = User::SYSTEMROLE_MODERATOR;
        }

        if ($role1 == User::SYSTEMROLE_SUPPORT|| $role2 == User::SYSTEMROLE_SUPPORT) {
            $role = User::SYSTEMROLE_SUPPORT;
        }

        if ($role1 == User::SYSTEMROLE_ADMIN || $role2 == User::SYSTEMROLE_ADMIN) {
            $role = User::SYSTEMROLE_ADMIN;
        }

        return($role);
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

    public function merge($id1, $id2, $reason) {
        error_log("Merge $id1, $id2, $reason"); ;
        # We want to merge two users.  At present we just merge the memberships, comments, emails and logs; we don't try to
        # merge any conflicting settings.
        #
        # Both users might have membership of the same group, including at different levels.
        #
        # A useful query to find foreign key references is of this form:
        #
        # USE information_schema; SELECT * FROM KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'iznik' AND REFERENCED_TABLE_NAME = 'users';
        #
        # We avoid too much use of quoting in preQuery/preExec because quoted numbers can't use a numeric index and therefore
        # perform slowly.
        #error_log("Merge $id2 into $id1");
        $l = new Log($this->dbhr, $this->dbhm);
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->beginTransaction();
        $rollback = FALSE;

        if ($rc) {
            try {
                #error_log("Started transaction");
                $rollback = TRUE;

                # Merge the top-level memberships
                $id2membs = $this->dbhr->preQuery("SELECT * FROM memberships WHERE userid = $id2;");
                foreach ($id2membs as $id2memb) {
                    # Jiggery-pokery with $rc for UT purposes.
                    $rc2 = $rc;
                    #error_log("$id2 member of {$id2memb['groupid']} ");
                    $id1membs = $this->dbhr->preQuery("SELECT * FROM memberships WHERE userid = $id1 AND groupid = {$id2memb['groupid']};");

                    if (count($id1membs) == 0) {
                        # id1 is not already a member.  Just change our id2 membership to id1.
                        #error_log("...$id1 not a member, UPDATE");
                        $rc2 = $this->dbhm->preExec("UPDATE memberships SET userid = $id1 WHERE userid = $id2 AND groupid = {$id2memb['groupid']};");

                        #error_log("Membership UPDATE merge returned $rc2");
                    } else {
                        # id1 is already a member, so we really have to merge.
                        #
                        # Our new membership has the highest role.
                        $id1memb = $id1membs[0];
                        #error_log("...as is $id1");
                        $role = User::roleMax($id1memb['role'], $id2memb['role']);

                        if ($role != $id1memb['role']) {
                            $rc2 = $this->dbhm->preExec("UPDATE memberships SET role = ? WHERE userid = $id1 AND groupid = {$id2memb['groupid']};", [
                                $role
                            ]);
                        }

                        if ($rc2) {
                            #  Our added date should be the older of the two.
                            $date = min(strtotime($id1memb['added']), strtotime($id2memb['added']));
                            $mysqltime = date("Y-m-d H:i:s", $date);
                            $rc2 = $this->dbhm->preExec("UPDATE memberships SET added = ? WHERE userid = $id1 AND groupid = {$id2memb['groupid']};", [
                                $mysqltime
                            ]);
                        }

                        # There are several attributes we want to take the non-NULL version.
                        foreach (['configid', 'settings', 'heldby'] as $key) {
                            if ($id2memb[$key]) {
                                if ($rc2) {
                                    $rc2 = $this->dbhm->preExec("UPDATE memberships SET $key = ? WHERE userid = $id1 AND groupid = {$id2memb['groupid']};", [
                                        $id2memb[$key]
                                    ]);
                                }
                            }
                        }

                        # Now move any id2 Yahoo memberships over to refer to id1 before we delete it.
                        # This might result in duplicates so we use IGNORE.
                        $id2membs = $this->dbhm->preQuery("SELECT id, groupid FROM memberships WHERE userid = $id2;");
                        #error_log("Memberships for $id2 " . var_export($id2membs, true));
                        foreach ($id2membs as $id2memb) {
                            $rc2 = $rc;

                            $id1membs = $this->dbhm->preQuery("SELECT id FROM memberships WHERE userid = ? AND groupid = ?;", [
                                $id1,
                                $id2memb['groupid']
                            ]);

                            #error_log("Memberships for $id1 on {$id2memb['groupid']} " . var_export($id1membs, true));

                            foreach ($id1membs as $id1memb) {
                                $rc2 = $this->dbhm->preExec("UPDATE IGNORE memberships_yahoo SET membershipid = ? WHERE membershipid = ?;", [
                                    $id1memb['id'],
                                    $id2memb['id']
                                ]) ;
                                #error_log("$rc2 from UPDATE IGNORE memberships_yahoo SET membershipid = {$id1memb['id']} WHERE membershipid = {$id2memb['id']};");
                            }

                            if ($rc2) {
                                $rc2 = $this->dbhm->preExec("DELETE FROM memberships_yahoo WHERE membershipid = ?;", [
                                    $id2memb['id']
                                ]);
                                #error_log("$rc2 from delete {$id2memb['id']}");
                            }

                            $rc = $rc2 && $rc ? $rc2 : 0;
                        }

                        if ($rc2) {
                            # Now we just need to delete the id2 one.
                            $rc2 = $this->dbhm->preExec("DELETE FROM memberships WHERE userid = $id2 AND groupid = {$id2memb['groupid']};");
                        }
                    }

                    $rc = $rc2 && $rc ? $rc2 : 0;
                }

                # Merge the emails.  Both might have a primary address; id1 wins.  There is a unique index, so there
                # can't be a conflict on email.
                if ($rc) {
                    $primary = NULL;
                    $sql = "SELECT * FROM users_emails WHERE userid = $id2 AND preferred = 1;";
                    $emails = $this->dbhr->preQuery($sql);
                    foreach ($emails as $email) {
                        $primary = $email['id'];
                    }

                    $sql = "SELECT * FROM users_emails WHERE userid = $id1 AND preferred = 1;";
                    $emails = $this->dbhr->preQuery($sql);
                    foreach ($emails as $email) {
                        $primary = $email['id'];
                    }

                    #error_log("Merge emails");
                    $sql = "UPDATE users_emails SET userid = $id1, preferred = 0 WHERE userid = $id2;";
                    $rc = $this->dbhm->preExec($sql);

                    if ($primary) {
                        $sql = "UPDATE users_emails SET preferred = 1 WHERE id = $primary;";
                        $rc = $this->dbhm->preExec($sql);
                    }

                    #error_log("Emails now " . var_export($this->dbhm->preQuery("SELECT * FROM users_emails WHERE userid = $id1;"), true));
                    #error_log("Email merge returned $rc");
                }

                if ($rc) {
                    # Merge other foreign keys where success is less important.  For some of these there might already
                    # be entries, so we do an IGNORE.
                    $this->dbhm->preExec("UPDATE locations_excluded SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE spam_users SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE spam_users SET byuserid = $id1 WHERE byuserid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE users_banned SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE users_banned SET byuser = $id1 WHERE byuser = $id2;");
                    $this->dbhm->preExec("UPDATE users_logins SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE users_comments SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE users_comments SET byuserid = $id1 WHERE byuserid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE sessions SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE users_push_notifications SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE chat_roster SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE chat_rooms SET user1 = $id1 WHERE user1 = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE chat_rooms SET user2 = $id1 WHERE user2 = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE chat_messages SET userid = $id1 WHERE userid = $id2;");
                    $this->dbhm->preExec("UPDATE IGNORE users_searches SET userid = $id1 WHERE userid = $id2;");
                }

                # Merge attributes we want to keep if we have them in id2 but not id1.  Some will have unique
                # keys, so update to delete them.
                foreach (['fullname', 'firstname', 'lastname', 'yahooUserId', 'yahooid'] as $att) {
                    $users = $this->dbhm->preQuery("SELECT $att FROM users WHERE id = $id2;");
                    foreach ($users as $user) {
                        $this->dbhm->preExec("UPDATE users SET $att = NULL WHERE id = $id2;");

                        if ($att != 'fullname') {
                            $this->dbhm->preExec("UPDATE users SET $att = ? WHERE id = $id1 AND $att IS NULL;", [$user[$att]]);
                        } else if (stripos($user[$att], 'fbuser') === FALSE) {
                            # We don't want to overwrite a name with FBUser.
                            $this->dbhm->preExec("UPDATE users SET $att = ? WHERE id = $id1;", [$user[$att]]);
                        }
                    }
                }

                # Merge the logs.  There should be logs both about and by each user, so we can use the rc to check success.
                if ($rc) {
                    $rc = $this->dbhm->preExec("UPDATE logs SET user = $id1 WHERE user = $id2;");

                    #error_log("Log merge 1 returned $rc");
                }

                if ($rc) {
                    $rc = $this->dbhm->preExec("UPDATE logs SET byuser = $id1 WHERE byuser = $id2;");

                    #error_log("Log merge 2 returned $rc");
                }

                # Merge the fromuser in messages.  There might not be any, and it's not the end of the world
                # if this info isn't correct, so ignore the rc.
                #error_log("Merge messages, current rc $rc");
                if ($rc) {
                    $this->dbhm->preExec("UPDATE messages SET fromuser = $id1 WHERE fromuser = $id2;");
                }

                # Merge the history
                #error_log("Merge history, current rc $rc");
                if ($rc) {
                    $this->dbhm->preExec("UPDATE messages_history SET fromuser = $id1 WHERE fromuser = $id2;");
                    $this->dbhm->preExec("UPDATE memberships_history SET userid = $id1 WHERE userid = $id2;");
                }

                # Merge the systemrole.
                $u1s = $this->dbhr->preQuery("SELECT systemrole FROM users WHERE id = $id1;");
                foreach ($u1s as $u1) {
                    $u2s = $this->dbhr->preQuery("SELECT systemrole FROM users WHERE id = $id2;");
                    foreach ($u2s as $u2) {
                        $rc = $this->dbhm->preExec("UPDATE users SET systemrole = ? WHERE id = $id1;", [
                            $this->systemRoleMax($u1['systemrole'], $u2['systemrole'])
                        ]);
                    }
                }

                if ($rc) {
                    # Log the merge - before the delete otherwise we will fail to log it.
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_MERGED,
                        'user' => $id2,
                        'byuser' => $me ? $me->getId() : NULL,
                        'text' => "Merged $id1 into $id2 ($reason)"
                    ]);

                    # Log under both users to make sure we can trace it.
                    $l->log([
                        'type' => Log::TYPE_USER,
                        'subtype' => Log::SUBTYPE_MERGED,
                        'user' => $id1,
                        'byuser' => $me ? $me->getId() : NULL,
                        'text' => "Merged $id1 into $id2 ($reason)"
                    ]);

                    # Finally, delete id2.
                    #error_log("Delete $id2");
                    error_log("Merged $id1 < $id2, $reason");
                    $deleteme = new User($this->dbhr, $this->dbhm, $id2);
                    $rc = $deleteme->delete(NULL, NULL, NULL, FALSE);
                }

                if ($rc) {
                    # Everything worked.
                    $rollback = FALSE;

                    # We might have merged ourself!
                    if (pres('id', $_SESSION) == $id2) {
                        $_SESSION['id'] = $id1;
                    }
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

    private function maybeMail($groupid, $subject, $body, $action) {
        if ($body) {
            # We have a mail to send.
            list ($eid, $to) = $this->getEmailForYahooGroup($groupid);

            # If this is one of our domains, then we should send directly to the preferred email, to avoid
            # the mail coming back to us and getting added into a chat.
            if (!$to || ourDomain($to)) {
                $to = $this->getEmailPreferred();
            }

            if ($to) {
                $g = new Group($this->dbhr, $this->dbhm, $groupid);
                $atts = $g->getPublic();

                $me = whoAmI($this->dbhr, $this->dbhm);

                # Find who to send it from.  If we have a config to use for this group then it will tell us.
                $name = $me->getName();
                $c = new ModConfig($this->dbhr, $this->dbhm);
                $cid = $c->getForGroup($me->getId(), $groupid);
                $c = new ModConfig($this->dbhr, $this->dbhm, $cid);
                $fromname = $c->getPrivate('fromname');
                $name = ($fromname == 'Groupname Moderator') ? '$groupname Moderator' : $name;

                # We can do a simple substitution in the from name.
                $name = str_replace('$groupname', $atts['namedisplay'], $name);

                $headers = "From: \"$name\" <" . $g->getModsEmail() . ">\r\n";
                $bcc = $c->getBcc($action);

                if ($bcc) {
                    $bcc = str_replace('$groupname', $atts['nameshort'], $bcc);
                    $headers .= "Bcc: $bcc\r\n";
                }

                $this->mailer(
                    $to,
                    $subject,
                    $body,
                    $headers,
                    "-f" . $g->getModsEmail()
                );
            }
        }
    }

    public function mail($groupid, $subject, $body, $stdmsgid, $action = NULL) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_MAILED,
            'user' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $subject,
            'groupid' => $groupid,
            'stdmsgid' => $stdmsgid
        ]);

        $this->maybeMail($groupid, $subject, $body, $action);
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

        $this->notif->notifyGroupMods($groupid);

        $this->maybeMail($groupid, $subject, $body, 'Reject Member');
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

        $sql = "SELECT memberships_yahoo.* FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE userid = ? AND groupid = ? AND memberships_yahoo.collection = ?;";
        $members = $this->dbhr->preQuery($sql, [ $this->id, $groupid, MembershipCollection::PENDING ]);

        foreach ($members as $member) {
            if (pres('yahooapprove', $member)) {
                # We can trigger approval by email - do so.  Yahoo is sluggish so we send multiple times.
                for ($i = 0; $i < 10; $i++) {
                    $this->mailer($member['yahooapprove'], "My name is Iznik and I approve this member", "", NULL, '-f' . MODERATOR_EMAIL);
                }
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

        $this->notif->notifyGroupMods($groupid);

        $this->maybeMail($groupid, $subject, $body, 'Approve Member');
    }

    public function markYahooApproved($groupid) {
        # Move a member from pending to approved in response to a Yahoo notification mail.
        #
        # Note that we will not always have a pending member application.  For example, suppose we have an
        # existing Yahoo membership with an email address which isn't one of ours; then when we post a message
        # we will trigger an application with one we do host, which will then get confirmed.
        #
        # Perhaps we can get a notification mail for a member not in Pending because their application hasn't been
        # sync'd to us, but this is less of an issue as we will not have work which we are pestering mods to do.
        # We'll pick them up on the next sync or when they post.
        #
        # No need for a transaction - if things go wrong, the member will remain in pending, which is recoverable.
        $sql = "SELECT * FROM memberships WHERE userid = ? AND groupid = ? AND collection = ?;";
        $members = $this->dbhr->preQuery($sql, [ $this->id, $groupid, MembershipCollection::PENDING ]);

        foreach ($members as $member) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_APPROVED,
                'msgid' => $this->id,
                'user' => $this->getId(),
                'groupid' => $groupid,
                'text' => 'Move from Pending to Approved after Yahoo notification mail'
            ]);

            # Set the membership to be approved.
            $sql = "UPDATE memberships SET collection = ? WHERE userid = ? AND groupid = ?;";
            $this->dbhm->preExec($sql, [
                MembershipCollection::APPROVED,
                $this->id,
                $groupid
            ]);
        }

        # The Yahoo membership should always exist as we'll have created it when we triggered the application.
        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_YAHOO_JOINED,
            'user' => $this->getId(),
            'groupid' => $groupid
        ]);

        $sql = "UPDATE memberships_yahoo SET collection = ? WHERE membershipid = (SELECT id FROM memberships WHERE userid = ? AND groupid = ?);";
        $rc = $this->dbhm->preExec($sql, [
            MembershipCollection::APPROVED,
            $this->id,
            $groupid
        ]);
    }

    function hold($groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE memberships SET heldby = ? WHERE userid = ? AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $me->getId(), $this->id, $groupid ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_HOLD,
                'user' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }

        $this->notif->notifyGroupMods($groupid);
    }

    function release($groupid) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $sql = "UPDATE memberships SET heldby = NULL WHERE userid = ? AND groupid = ?;";
        $rc = $this->dbhm->preExec($sql, [ $this->id, $groupid ]);

        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_USER,
                'subtype' => Log::SUBTYPE_RELEASE,
                'user' => $this->id,
                'byuser' => $me ? $me->getId() : NULL
            ]);
        }

        $this->notif->notifyGroupMods($groupid);
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

    public function split($email, $yahooid, $yahoouserid) {
        # We want to ensure that the current user has no reference to these values.
        #
        # This will leave logs pointing to the old user, but there's no way to recover that.
        $me = whoAmI($this->dbhr, $this->dbhm);
        $l = new Log($this->dbhr, $this->dbhm);
        if ($email) {
            $this->removeEmail($email);
        }

        if ($yahooid && $this->user['yahooid'] && $this->user['yahooid'] == $yahooid) {
            $this->setPrivate('yahooid', NULL);
        }

        if ($yahoouserid && $this->user['yahooUserId'] && $this->user['yahooUserId'] == $yahoouserid) {
            $this->setPrivate('yahooUserId', NULL);
        }

        $l->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_SPLIT,
            'user' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => "Split $email, YID $yahooid, YUID $yahoouserid"
        ]);
    }

    public function welcome($email, $password) {
        $html = welcome_password(USER_SITE, USERLOGO, $email, $password);

        $message = Swift_Message::newInstance()
            ->setSubject("Welcome to " . SITE_NAME . "!")
            ->setFrom([NOREPLY_ADDR => SITE_NAME])
            ->setTo($email)
            ->setBody("Thanks for joining"  . SITE_NAME . "!  Here's your password: $password.")
            ->addPart($html, 'text/html');

        list ($transport, $mailer) = getMailer();
        $mailer->send($message);
    }

    public function forgotPassword($email) {
        $link = $this->loginLink(USER_SITE, $this->id, '/settings');
        $html = forgot_password(USER_SITE, USERLOGO, $email, $link);

        $message = Swift_Message::newInstance()
            ->setSubject("Forgot your password?")
            ->setFrom([NOREPLY_ADDR => SITE_NAME])
            ->setTo($email)
            ->setBody("To set a new password, just log in here: $link")
            ->addPart($html, 'text/html');

        list ($transport, $mailer) = getMailer();
        $mailer->send($message);
    }

    public function verifyEmail($email) {
        # If this is one of our current emails, then we can just make it the primary.
        $emails = $this->getEmails();
        $handled = FALSE;

        foreach ($emails as $anemail) {
            if ($anemail['email'] == $email) {
                # It's one of ours already; make sure it's flagged as primary.
                $this->addEmail($email, 1);
                $handled = TRUE;
            }
        }

        if (!$handled) {
            # This email is new to this user.  It may or may not currently be in use for another user.  Either
            # way we want to send a verification mail.
            $headers = "From: ModTools <" . NOREPLY_ADDR . ">\nContent-Type: multipart/alternative; boundary=\"_I_Z_N_I_K_\"\nMIME-Version: 1.0";
            $canon = User::canonMail($email);
            $key = uniqid();
            $sql = "INSERT INTO users_emails (email, canon, validatekey, backwards) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE validatekey = ?;";
            $this->dbhm->preExec($sql,
                [$email, $canon, $key, strrev($canon), $key]);
            $confirm = "https://" . $_SERVER['HTTP_HOST'] . "/modtools/settings/confirmmail/" . urlencode($key);
            $this->mailer($email, "Please verify your email", modtools_verify_email($email, $confirm), $headers, "-f" . NOREPLY_ADDR);
        }

        return($handled);
    }

    public function confirmEmail($key) {
        $rc = FALSE;
        $sql = "SELECT * FROM users_emails WHERE validatekey = ?;";
        $mails = $this->dbhr->preQuery($sql, [ $key ]);
        $me = whoAmI($this->dbhr, $this->dbhm);

        foreach ($mails as $mail) {
            if ($mail['userid'] && $mail['userid'] != $me->getId()) {
                # This email belongs to another user.  But we've confirmed that it is ours.  So merge.
                $this->merge($this->id, $mail['userid'], "Verified ownership of email {$mail['email']}");
            }

            $this->dbhm->preExec("UPDATE users_emails SET userid = ?, validated = NOW() WHERE id = ?;", [ $this->id, $mail['id']]);
            $this->addEmail($mail['email'], 1);
            $rc = TRUE;
        }

        return($rc);
    }

    public function inventEmail() {
        # An invented email is one on our domain that doesn't give away too much detail, but isn't just a string of
        # numbers (ideally).  We may already have one.
        $email = NULL;
        $emails = $this->getEmails();
        foreach ($emails as $thisemail) {
            if (strpos($thisemail['email'], USER_DOMAIN ) !== FALSE) {
                $email = $thisemail['email'];
            }
        }

        if (!$email) {
            # If they have a Yahoo ID, that'll do nicely - it's public info.
            $yahooid = $this->getPrivate('yahooid');

            if ($yahooid) {
                $email = $yahooid . '-' . $this->id . '@' . USER_DOMAIN;
            } else {
                # Their own email might already be of that nature, which would be lovely.
                $personal = [];
                $email = $this->getEmailPreferred();

                if ($email) {
                    foreach (['firstname', 'lastname', 'fullname'] as $att) {
                        $words = explode(' ', $this->user[$att]);
                        foreach ($words as $word) {
                            if (stripos($email, $word) !== FALSE) {
                                # Unfortunately not - it has some personal info in it.
                                $email = NULL;
                            }
                        }
                    }
                }

                if ($email) {
                    # We have an email which is fairly anonymous.  Use the LHS.
                    $p = strpos($email, '@');
                    $email = substr($email, 0, $p) . '-' . $this->id . '@' . USER_DOMAIN;
                } else {
                    # We can't make up something similar to their existing email address.
                    $lengths  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/distinct_word_lengths.json'), true);
                    $bigrams  = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/word_start_bigrams.json'), true);
                    $trigrams = json_decode(file_get_contents(IZNIK_BASE . '/lib/wordle/data/trigrams.json'), true);
                    $length = \Wordle\array_weighted_rand($lengths);
                    $start  = \Wordle\array_weighted_rand($bigrams);
                    $email = strtolower(\Wordle\fill_word($start, $length, $trigrams)) . '-' . $this->id . '@' . USER_DOMAIN;
                }
            }
        }

        return($email);
    }

    public function triggerYahooApplication($groupid) {
        $g = new Group($this->dbhr, $this->dbhm, $groupid);
        $email = $this->inventEmail();
        $emailid = $this->addEmail($email, 0);
        #error_log("Added email $email id $emailid");

        # We might already have a membership with an email which isn't one of ours.  If so, we don't want to
        # trash that membership by turning it into a pending one.
        if (!$this->isApprovedMember($groupid)) {
            # Set up a pending membership - will be converted to approved when we process the approval notification.
            $this->addMembership($groupid, User::ROLE_MEMBER, $emailid, MembershipCollection::PENDING);
        }

        $headers = "From: $email>\r\n";

        # Yahoo is not very reliable; if we subscribe multiple times it seems to be more likely to react.
        for ($i = 0; $i < 10; $i++) {
            list ($transport, $mailer) = getMailer();
            $message = Swift_Message::newInstance()
                ->setSubject('Please let me join')
                ->setFrom([$email])
                ->setTo($g->getGroupSubscribe())
                ->setDate(time())
                ->setBody('Pretty please');
            $mailer->send($message);
        }

        $this->log->log([
            'type' => Log::TYPE_USER,
            'subtype' => Log::SUBTYPE_YAHOO_APPLIED,
            'user' => $this->id,
            'groupid' => $groupid,
            'text' => $email
        ]);

        return($email);
    }

    public function submitYahooQueued($groupid) {
        # Get an email address we can use on the group.
        $submitted = 0;
        list ($eid, $email) = $this->getEmailForYahooGroup($groupid, TRUE);
        #error_log("Got email $email for {$this->id} on $groupid, eid $eid");

        if ($email) {
            $sql = "SELECT msgid FROM messages_groups INNER JOIN messages ON messages_groups.msgid = messages.id WHERE groupid = ? AND collection = ? AND messages_groups.deleted = 0 AND messages.fromuser = ?;";
            $msgs = $this->dbhr->preQuery($sql, [
                $groupid,
                MessageCollection::QUEUED_YAHOO_USER,
                $this->id
            ]);

            foreach ($msgs as $msg) {
                $m = new Message($this->dbhr, $this->dbhm, $msg['msgid']);
                $m->submit($this, $email, $groupid);
                $submitted++;
            }
        }

        return($submitted);
    }

    public function delete($groupid = NULL, $subject = NULL, $body = NULL, $log = TRUE) {
        $me = whoAmI($this->dbhr, $this->dbhm);

        $rc = $this->dbhm->preExec("DELETE FROM users WHERE id = ?;", [$this->id]);

        if ($rc && $log) {
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

    public function getUnsubLink($domain, $id) {
        return(User::loginLink($domain, $id, "/unsubscribe/$id"));
    }

    public function listUnsubscribe($domain, $id) {
        # Generates the value for the List-Unsubscribe header field.
        $ret = "<mailto:unsubscribe-$id@" . USER_SITE . ">, <" . $this->getUnsubLink($domain, $id) .">";
        return($ret);
    }

    public function loginLink($domain, $id, $url = '/') {
        # Get a per-user link we can use to log in without a password.
        $key = NULL;
        $sql = "SELECT * FROM users_logins WHERE userid = ? AND type = ?;";
        $logins = $this->dbhr->preQuery($sql, [ $id, User::LOGIN_LINK ]);
        foreach ($logins as $login) {
            $key = $login['credentials'];
        }

        if (!$key) {
            $key = randstr(32);
            $rc = $this->dbhm->preExec("INSERT INTO users_logins (userid, type, credentials) VALUES (?,?,?);", [
                $id,
                User::LOGIN_LINK,
                $key
            ]);

            # If this didn't work, we still return an URL - worst case they'll have to sign in.
            $key = $rc ? $key : NULL;
        }

        $p = strpos($url, '?');
        $url = $p === FALSE ? ("https://$domain$url?u=$id&k=$key") : ("https://" . SITE_HOST . "$url&u=$id&k=$key");

        return($url);
    }

    public function sendOurMails($g) {
        $sendit = FALSE;
        $groupid = $g->getId();

        #error_log("On Yahoo? " . $g->getPrivate('onyahoo'));

        if ($g->getPrivate('onyahoo')) {
            # We don't want to send out mails to users who are members directly on Yahoo, only
            # for ones which have joined through this platform or its predecessor.
            #
            # We can check this in the Yahoo group membership table to check the email they use
            # for membership.  However it might not be up to date because that relies on mods
            # using ModTools.
            #
            # So if we don't find anything in there, then we check whether this user has any
            # emails which we host.  That tells us whether they've joined any groups via our
            # platform, which tells us whether it's reasonable to send them emails.
            $membershipmail = $this->getEmailForYahooGroup($groupid, TRUE)[1];
            #error_log("Membership mail $membershipmail");

            if ($membershipmail) {
                # They have a membership on Yahoo with one of our addresses.
                $sendit = TRUE;
            } else {
                # They don't have a membership on Yahoo with one of our addresses.  If we have sync'd our
                # membership fairly recently, then we can rely on that and it means that we shouldn't send
                # it.
                $lastsync = $g->getPrivate('lastyahoomembersync');
                $lastsync = $lastsync ? strtotime($lastsync) : NULL;
                $age = $lastsync ? ((time() - $lastsync) / 3600) : NULL;
                #error_log("Last sync $age");

                if (!$age || $age > 7 * 24) {
                    # We don't have a recent sync, because the mods aren't using ModTools regularly.
                    #
                    # Use email for them having any of ours as an approximation.
                    $emails = $this->getEmails();
                    foreach ($emails as $anemail) {
                        if (ourDomain($anemail['email'])) {
                            $sendit = TRUE;
                        }
                    }
                }
            }

            if ($sendit) {
                # We might be on holiday.
                $hol = $this->getPrivate('onholidaytill');
                $till = $hol ? strtotime($hol) : 0;
                #error_log("Holiday $till vs " . time());

                $sendit = time() > $till;
            }

            #error_log("Sendit? $sendit");
            return($sendit);
        }
    }

    public function getMembershipHistory() {
        # We get this from our logs.
        $sql = "SELECT * FROM logs WHERE user = ? AND `type` = 'User' ORDER BY id DESC;";
        $logs = $this->dbhr->preQuery($sql, [ $this->id ]);

        $ret = [];
        foreach ($logs as $log) {
            $thisone = NULL;
            switch ($log['subtype']) {
                case Log::SUBTYPE_JOINED:
                case Log::SUBTYPE_APPROVED:
                case Log::SUBTYPE_REJECTED:
                case Log::SUBTYPE_APPLIED:
                case Log::SUBTYPE_LEFT:
                case Log::SUBTYPE_YAHOO_APPLIED:
                case Log::SUBTYPE_YAHOO_JOINED:
                {
                    $thisone = $log['subtype'];
                    break;
                }
            }

            #error_log("{$log['subtype']} gives $thisone {$log['groupid']}");
            if ($thisone && $log['groupid']) {
                $g = new Group($this->dbhr, $this->dbhm, $log['groupid']);
                $ret[] = [
                    'timestamp' => ISODate($log['timestamp']),
                    'type' => $thisone,
                    'group' => $g->getPublic(),
                    'text' => $log['text']
                ];
            }
        }

        return($ret);
    }

    public function search($search, $ctx)
    {
        $id = presdef('id', $ctx, 0);
        $ctx = $ctx ? $ctx : [];
        $q = $this->dbhr->quote("$search%");
        $backwards = strrev($search);
        $qb = $this->dbhr->quote("$backwards%");

        $sql = "SELECT DISTINCT userid FROM
                ((SELECT userid FROM users_emails WHERE email LIKE $q OR backwards LIKE $qb) UNION
                (SELECT id AS userid FROM users WHERE fullname LIKE $q) UNION
                (SELECT id AS userid FROM users WHERE yahooid LIKE $q) UNION
                (SELECT userid FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE yahooAlias LIKE $q)) t WHERE userid > ? ORDER BY userid ASC";
        $users = $this->dbhr->preQuery($sql, [$id]);

        $ret = [];
        foreach ($users as $user) {
            $ctx['id'] = $user['userid'];

            $u = new User($this->dbhr, $this->dbhm, $user['userid']);
            $thisone = $u->getPublic();

            # We might not have the emails.
            $thisone['email'] = $u->getEmailPreferred();
            $thisone['emails'] = $u->getEmails();

            # We also want the Yahoo details.  Get them all in a single query for performance.
            $sql = "SELECT memberships.id AS membershipid, memberships_yahoo.* FROM memberships_yahoo INNER JOIN memberships ON memberships.id = memberships_yahoo.membershipid WHERE userid = ?;";
            $membs = $this->dbhr->preQuery($sql, [ $user['userid']]);
            foreach ($thisone['memberof'] as &$member) {
                foreach ($membs as $memb) {
                    if ($memb['membershipid'] == $member['membershipid']) {
                        foreach (['yahooAlias', 'yahooPostingStatus', 'yahooDeliveryType'] as $att) {
                            $member[$att] = $memb[$att];
                        }
                    }
                }
            }

            $thisone['membershiphistory'] = $u->getMembershipHistory();
            $thisone['sessions'] = $u->getSessions($this->dbhr, $this->dbhm, $user['userid']);

            $thisone['logins'] = $u->getLogins(FALSE);

            $ret[] = $thisone;
        }

        return($ret);
    }

    public function setPrivate($att, $val) {
        if (presdef('id', $_SESSION, NULL) == $this->id) {
            # We cache our user details in our session
            Session::clearSessionCache();
        }
        
        parent::setPrivate($att, $val);
    }
}