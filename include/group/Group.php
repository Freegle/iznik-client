<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class Group extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'nameshort', 'namefull', 'nameabbr', 'namedisplay', 'settings', 'type', 'logo',
        'onyahoo');

    const GROUP_REUSE = 'Reuse';
    const GROUP_FREEGLE = 'Freegle';
    const GROUP_OTHER = 'Other';

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'groups', 'group', $this->publicatts);

        $this->log = new Log($dbhr, $dbhm);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($shortname, $type) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO groups (nameshort, type) VALUES (?, ?)", [$shortname, $type]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'groups', 'group', $this->publicatts);
            $this->log->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_CREATED,
                'groupid' => $id,
                'text' => $shortname
            ]);

            return($id);
        } else {
            return(NULL);
        }
    }

    public function getModsEmail() {
        return($this->group['nameshort'] . "-owner@yahoogroups.com");
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM groups WHERE id = ?;", [$this->id]);
        if ($rc) {
            $this->log->log([
                'type' => Log::TYPE_GROUP,
                'subtype' => Log::SUBTYPE_DELETED,
                'groupid' => $this->id
            ]);
        }

        return($rc);
    }

    public function findByShortName($name) {
        $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE nameshort LIKE ?;",
            [$name]);
        foreach ($groups as $group) {
            return($group['id']);
        }

        return(NULL);
    }

    public function getWorkCounts() {
        $ret = [
            'pending' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Pending' AND messages_groups.deleted = 0;", [
                $this->id
            ])[0]['count'],
            'spam' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Spam' AND messages_groups.deleted = 0;", [
                $this->id
            ])[0]['count'],
            'plugin' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM plugin WHERE groupid = ?;", [
                $this->id
            ])[0]['count']
        ];

        return($ret);
    }

    public function getPublic() {
        $atts = parent::getPublic();

        # Add in derived properties.
        $atts['namedisplay'] = $atts['namefull'] ? $atts['namefull'] : $atts['nameshort'];
        $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = {$this->id};";
        $counts = $this->dbhr->preQuery($sql);
        $atts['membercount'] = $counts[0]['count'];

        return($atts);
    }

    public function getMembers() {
        $ret = [];
        $sql = "SELECT userid FROM memberships WHERE groupid = ?;";
        $members = $this->dbhr->preQuery($sql, [ $this->id ]);
        foreach ($members as $member) {
            $u = new User($this->dbhr, $this->dbhm, $member['userid']);
            $thisone = $u->getPublic(NULL, FALSE);
            $thisone['emails'] = $u->getEmails();
            $thisone['yahooDeliveryType'] = $u->getPrivate('yahooDeliveryType');
            $thisone['yahooPostingStatus'] = $u->getPrivate('yahooPostingStatus');
            $thisone['role'] = $u->getRole($this->id);
            $ret[] = $thisone;
        }

        return($ret);
    }

    public function setMembers($members) {
        # This is used to set the whole of the membership list for a group.  It's only used when the group is
        # mastered on Yahoo, rather than by us.
        #
        # We do this inside a transaction because it would be a horrible situation if we deleted half the members
        # and left the group mangled.
        $rollback = true;
        if ($this->dbhm->beginTransaction()) {
            try {
                $sql = "DELETE FROM memberships WHERE groupid = {$this->id};";

                # If this doesn't work we'd get an exception
                $this->dbhm->exec($sql);

                $u = new User($this->dbhm, $this->dbhm);

                foreach ($members as $member) {
                    # First check if we already know about this user.
                    $uid = $u->findByEmail($member['email']);

                    if (!$uid) {
                        # We don't - create them.
                        $uid = $u->create(NULL, NULL, $member['name']);
                        $u = new User($this->dbhm, $this->dbhm, $uid);
                        $u->addEmail($member['email']);
                    } else {
                        $u = new User($this->dbhm, $this->dbhm, $uid);
                    }

                    $role = $u->getRole($this->id);

                    if ($role == User::ROLE_NONMEMBER) {
                        # We don't know this user as a member of this group yet; add them
                        $rollback = !$u->addMembership($this->id);
                    }

                    if (!$rollback) {
                        # Even if we already had them as a member, their role might have changed
                        $role = User::ROLE_MEMBER;
                        if (pres('yahooModeratorStatus', $member)) {
                            if ($member['yahooModeratorStatus'] == 'MODERATOR') {
                                $role = User::ROLE_MODERATOR;
                            } else if ($member['yahooModeratorStatus'] == 'OWNER') {
                                $role = User::ROLE_OWNER;
                            }
                        }

                        $rollback = !$u->setRole($role, $this->id);
                    }

                    // Cheat code coverage by putting on the same line.
                    if ($rollback) { break; }
                }
            } catch (Exception $e) {
                $rollback = TRUE;
            }

            if ($rollback) {
                # Something went wrong.
                $this->dbhm->rollBack();
            } else {
                $rollback = !$this->dbhm->commit();
            }
        }

        return(!$rollback);
    }

    private function getKey($message) {
        # Both pending and approved messages have unique IDs, though they are only unique within pending and approved,
        # not between them.
        #
        # It would be nice to believe in a world where Message-ID was unique.
        $key = NULL;
        if (pres('yahoopendingid', $message)) {
            $key = "P-{$message['yahoopendingid']}";
        } else if (pres('yahooapprovedid', $message)) {
            $key = "A-{$message['yahooapprovedid']}";
        }

        return($key);
    }

    public function correlate($collections, $messages) {
        # Check whether any of the messages in $messages are not present on the server or vice-versa.
        $missingonserver = [];
        $supplied = [];
        $missingonclient = [];
        $cs = [];

        # First find messages which are missing on the server, i.e. present in $messages but not
        # present in any of $collections.
        foreach ($collections as $collection)
        {
            $c = new Collection($this->dbhr, $this->dbhm, $collection);
            $cs[] = $c;
        }

        foreach ($messages as $message) {
            $key = $this->getKey($message);
            $supplied[$key] = true;

            $missing = true;

            foreach ($cs as $c) {
                /** @var Collection $c */
                $id = NULL;

                switch (($c->getCollection())) {
                    case Collection::APPROVED:
                        $id = $c->findByYahooApprovedId($this->id, $message['yahooapprovedid']);
                        break;
                    case Collection::PENDING:
                        $id = $c->findByYahooPendingId($this->id, $message['yahoopendingid']);
                        break;
                }

                if ($id) {
                    $missing = false;
                }
            }

            if ($missing) {
                $missingonserver[] = $message;
            }
        }

        # Now find messages which are missing on the client, i.e. present in $collections but not present in
        # $messages.
        /** @var Collection $c */
        foreach ($cs as $c) {
            $sql = "SELECT id, fromaddr, yahoopendingid, yahooapprovedid, subject, date FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ?;";
            $ourmsgs = $this->dbhr->preQuery(
                $sql,
                [
                    $this->id,
                    $c->getCollection()
                ]
            );

            foreach ($ourmsgs as $msg) {
                $key = $this->getKey($msg);
                if (!array_key_exists($key, $supplied)) {
                    $missingonclient[] = [
                        'id' => $msg['id'],
                        'email' => $msg['fromaddr'],
                        'subject' => $msg['subject'],
                        'collection' => $c->getCollection(),
                        'date' => ISODate($msg['date'])
                    ];
                }
            }
        }

        return([$missingonserver, $missingonclient]);
    }
}