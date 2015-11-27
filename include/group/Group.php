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
            'pending' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Pending' AND messages_groups.deleted = 0 AND messages.heldby IS NULL;", [
                $this->id
            ])[0]['count'],
            'spam' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Spam' AND messages_groups.deleted = 0 AND messages.heldby IS NULL;", [
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
        $atts['lastyahoomembersync'] = ISODate($this->group['lastyahoomembersync']);
        $atts['lastyahoomessagesync'] = ISODate($this->group['lastyahoomessagesync']);

        $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = {$this->id} AND role IN ('Owner', 'Moderator');";
        $counts = $this->dbhr->preQuery($sql);
        $atts['nummods'] = $counts[0]['count'];

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
        # First make sure we have users set up for all the new members; we do this first because it doesn't need
        # to be inside the transaction, and it reduces the length of time the transaction is extant.
        #
        # We do this inside a transaction because it would be a horrible situation if we deleted half the members
        # and left the group mangled.
        $rollback = true;

        $u = new User($this->dbhm, $this->dbhm);

        foreach ($members as &$memb) {
            if (pres('email', $memb)) {
                # First check if we already know about this user.
                $uid = $u->findByEmail($memb['email']);

                if (!$uid) {
                    # We don't - create them.
                    preg_match('/(.*)@/', $memb['email'], $matches);
                    $name = presdef('name', $memb, $matches[1]);
                    $uid = $u->create(NULL, NULL, $name);
                    $u = new User($this->dbhm, $this->dbhm, $uid);
                    $u->addEmail($memb['email']);
                } else {
                    $u = new User($this->dbhm, $this->dbhm, $uid);
                }

                $u->setPrivate('yahooUserId', $memb['yahooUserId']);

                # Remember the uid for inside the transaction below.
                $memb['uid'] = $uid;
            }
        }

        # First save off any configs used by existing moderators, so that we can restore them after the member sync.
        $sql = "SELECT userid, configid FROM memberships WHERE groupid = ? AND role IN ('Moderator', 'Owner') AND configid IS NOT NULL;";
        $oldmods = $this->dbhr->preQuery($sql, [ $this->id ]);

        if ($this->dbhm->beginTransaction()) {
            try {
                # If this doesn't work we'd get an exception
                $sql = "DELETE FROM memberships WHERE groupid = {$this->id};";
                $rc = $this->dbhm->exec($sql);
                $bulksql = '';

                for ($count = 0; $count < count($members); $count++) {
                    $member = $members[$count];
                    if (pres('uid', $member)) {
                        $role = User::ROLE_MEMBER;
                        if (pres('yahooModeratorStatus', $member)) {
                            if ($member['yahooModeratorStatus'] == 'MODERATOR') {
                                $role = User::ROLE_MODERATOR;
                            } else if ($member['yahooModeratorStatus'] == 'OWNER') {
                                $role = User::ROLE_OWNER;
                            }
                        }

                        # Use a single SQL statement rather than the usual methods for performance reasons.  And then
                        # batch them up into groups because that performs better in a cluster.
                        $yps = presdef('yahooPostingStatus', $member, NULL);
                        $ydt = presdef('yahooDeliveryType', $member, NULL);

                        # Use REPLACE rather than INSERT because the input data might have duplicate memberships.
                        $sql = "REPLACE INTO memberships (userid, groupid, role, yahooPostingStatus, yahooDeliveryType) VALUES (" .
                            "{$member['uid']}, {$this->id}, '{$role}', " . $this->dbhm->quote($yps) .
                            ", " . $this->dbhm->quote($ydt) . ");";
                        $bulksql .= $sql;

                        if ($count > 0 && $count % 1000 == 0) {
                            $rc = $this->dbhm->exec($bulksql);
                            $rollback = !$rc;
                            $bulksql = '';

                            // Cheat code coverage by putting on the same line.
                            if ($rollback) { break; }
                        }
                    }
                }

                if ($bulksql != '') {
                    $rc = $this->dbhm->exec($bulksql);
                    $rollback = !$rc;
                }

                foreach ($oldmods as $mod) {
                    $sql = "UPDATE memberships SET configid = ? WHERE groupid = ? AND userid = ?;";
                    $rollback = !$this->dbhm->preExec($sql, [
                        $mod['configid'],
                        $this->id,
                        $mod['userid']
                    ]);

                    if ($rollback) { break; }
                }

                $this->dbhm->preExec("UPDATE groups SET lastyahoomembersync = NOW() WHERE id = ?;", [ $this->id ]);

                $mods = "SELECT * FROM memberships WHERE groupid = {$this->id} AND role in ('Moderator', 'Owner');";
            } catch (Exception $e) {
                $rollback = TRUE;
            }

            if ($rollback) {
                # Something went wrong.
                $this->dbhm->rollBack();
            } else {
                $rollback = !$this->dbhm->commit();
            }
            $mods = "SELECT * FROM memberships WHERE groupid = {$this->id} AND role in ('Moderator', 'Owner');";
            error_log(var_export($this->dbhm->query($mods)->fetchAll(), true));
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

            if ($collection = Collection::APPROVED) {
                $this->dbhm->preExec("UPDATE groups SET lastyahoomessagesync = NOW() WHERE id = ?;", [
                    $this->id
                ]);
            }
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