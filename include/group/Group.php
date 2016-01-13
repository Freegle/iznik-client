<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

class Group extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'nameshort', 'namefull', 'nameabbr', 'namedisplay', 'settings', 'type', 'logo',
        'onyahoo', 'trial', 'licensed', 'licenseduntil');

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
            # Check for duplicate.  Might still occur in a timing window but in that rare case we'll get an exception
            # and catch that, failing the call.
            $groups = $this->dbhm->preQuery("SELECT id FROM groups WHERE nameshort LIKE ?;", [ $shortname ]);
            foreach ($groups as $group) {
                return(NULL);
            }

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

    public function getWorkCounts($mysettings) {
        # Depending on our group settings we might not want to show this work as primary; "other" work is displayed
        # less prominently in the client.
        #error_log("Getworkcounts " . error_log(var_export($mysettings, true)));
        $pend = !array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'] ? 'pending' : 'pendingother';
        $spam = !array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'] ? 'spam' : 'spamother';

        $ret = [
            $pend => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Pending' AND messages_groups.deleted = 0 AND messages.heldby IS NULL;", [
                $this->id
            ])[0]['count'],
            $spam => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = 'Spam' AND messages_groups.deleted = 0 AND messages.heldby IS NULL;", [
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
        $atts['settings'] = json_decode($atts['settings'], true);

        $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = {$this->id} AND role IN ('Owner', 'Moderator');";
        $counts = $this->dbhr->preQuery($sql);
        $atts['nummods'] = $counts[0]['count'];

        return($atts);
    }

    public function getMembers($limit = 10, $search = NULL, &$ctx = NULL, $searchid = NULL) {
        $ret = [];

        $date = $ctx == NULL ? NULL : $this->dbhr->quote(date("Y-m-d", $ctx['Added']));
        $addq = $ctx == NULL ? '' : (" AND (memberships.added < $date OR memberships.added = $date AND memberships.id < " . $this->dbhr->quote($ctx['id']) . ") ");
        # TODO We ought to search on firstname/lastname too, and handle word splits.  But this is sufficient for ModTools.
        $searchq = $search == NULL ? '' : (" AND (users_emails.email LIKE " . $this->dbhr->quote("%$search%") . " OR users.fullname LIKE " . $this->dbhr->quote("%$search%") . ") ");
        $searchq = $searchid ? (" AND users.id = " . $this->dbhr->quote($searchid) . " ") : $searchq;

        $sql = "SELECT DISTINCT memberships.* FROM memberships INNER JOIN users_emails ON memberships.userid = users_emails.userid INNER JOIN users ON users.id = memberships.userid WHERE groupid = ? $addq $searchq ORDER BY memberships.added DESC, memberships.id DESC LIMIT $limit;";
        $members = $this->dbhr->preQuery($sql, [ $this->id ]);

        $ctx = [ 'Added' => NULL ];

        foreach ($members as $member) {
            $u = new User($this->dbhr, $this->dbhm, $member['userid']);
            $thisone = $u->getPublic(NULL, FALSE);
            $thisone['userid'] = $thisone['id'];
            $thisepoch = strtotime($member['added']);

            if ($ctx['Added'] == NULL || $thisepoch < $ctx['Added']) {
                $ctx['Added'] = $thisepoch;
            }

            $ctx['id'] = $member['id'];

            # We want to return both the email used on this group and any others we have.
            $emails = $u->getEmails();
            $emailid = $u->getEmailForGroup($this->id);
            $email = NULL;
            $others = [];
            foreach ($emails as $anemail) {
                if ($anemail['id'] == $emailid) {
                    $email = $anemail['email'];
                } else {
                    $others[] = $anemail;
                }
            }

            $thisone['joined'] = ISODate($member['added']);

            # Defaults match ones in User.php
            $thisone['settings'] = $member['settings'] ? json_decode($member['settings'], TRUE) : [
                'showmessages' => 1,
                'showmembers' => 1
            ];
            $thisone['settings']['configid'] = $member['configid'];
            $thisone['email'] = $email;
            $thisone['groupid'] = $this->id;
            $thisone['otheremails'] = $others;
            $thisone['yahooDeliveryType'] = $u->getPrivate('yahooDeliveryType');
            $thisone['yahooPostingStatus'] = $u->getPrivate('yahooPostingStatus');
            $thisone['role'] = $u->getRole($this->id);

            $ret[] = $thisone;
        }

        return($ret);
    }

    public function setMembers($members, $collection) {
        $ret = [
            'ret' => 0,
            'status' => 'Success'
        ];

        if (!$members) { return($ret); }
        #$this->dbhm->setErrorLog(TRUE);

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

        # The input might have duplicate members; find out how many are distinct, save off the uid, and work out
        # the role.
        $distinctmembers = [];
        $roles = [];

        foreach ($members as &$memb) {
            if (pres('email', $memb)) {
                # First check if we already know about this user.
                $uid = $u->findByEmail($memb['email']);

                if (!$uid) {
                    # We don't - create them.
                    preg_match('/(.*)@/', $memb['email'], $matches);
                    $name = presdef('name', $memb, $matches[1]);
                    $uid = $u->create(NULL, NULL, $name);
                } else {
                    $u = new User($this->dbhm, $this->dbhm, $uid);
                }

                $memb['emailid'] = $u->addEmail($memb['email']);

                if (pres('yahooUserId', $memb)) {
                    $u->setPrivate('yahooUserId', $memb['yahooUserId']);
                }

                # Remember the uid for inside the transaction below.
                $memb['uid'] = $uid;
                $distinctmembers[$uid] = TRUE;

                # Get the role.  We might have the same underlying user who is a member using multiple email addresses
                # so we need to take the max role that they have.
                $thisrole = User::ROLE_MEMBER;
                if (pres('yahooModeratorStatus', $memb)) {
                    if ($memb['yahooModeratorStatus'] == 'MODERATOR') {
                        $thisrole = User::ROLE_MODERATOR;
                    } else if ($memb['yahooModeratorStatus'] == 'OWNER') {
                        $thisrole = User::ROLE_OWNER;
                    }
                }

                $role = pres($uid, $roles) ? $u->roleMax($roles[$uid], $thisrole) : $thisrole;

                $roles[$uid] = $role;
            }
        }

        $distinct = count($distinctmembers);
        $distinctmembers = NULL;

        $me = whoAmI($this->dbhr, $this->dbhm);
        $myrole = $me ? $me->getRole($this->id) : User::ROLE_NONMEMBER;

        if ($this->dbhm->beginTransaction()) {
            try {
                # If this doesn't work we'd get an exception
                $sql = "UPDATE memberships SET syncdelete = 1 WHERE groupid = {$this->id} AND collection = '$collection';";
                $this->dbhm->exec($sql);
                $bulksql = '';
                $tried = 0;

                for ($count = 0; $count < count($members); $count++) {
                    $member = $members[$count];
                    if (pres('uid', $member)) {
                        $tried++;
                        $role = $roles[$member['uid']];

                        # Use a single SQL statement rather than the usual methods for performance reasons.  And then
                        # batch them up into groups because that performs better in a cluster.
                        $yps = presdef('yahooPostingStatus', $member, NULL);
                        $ydt = presdef('yahooDeliveryType', $member, NULL);

                        # Make sure the membership is present.  We don't want to REPLACE as that might lose settings.
                        # We also don't want to just do INSERT IGNORE as that doesn't perform well in clusters.
                        $sql = "SELECT id, role FROM memberships WHERE userid = ? AND groupid = ?;";
                        $membs = $this->dbhm->preQuery($sql, [ $member['uid'], $this->id] );
                        if (count($membs) == 0) {
                            $sql = "INSERT IGNORE INTO memberships (userid, groupid, emailid, collection) VALUES ({$member['uid']}, {$this->id}, {$member['emailid']}, '$collection');";
                            $bulksql .= $sql;
                            $membs = [
                                [
                                    'role' => User::ROLE_MEMBER
                                ]
                            ];
                        }

                        # If we are promoting a member, then we can only promote as high as we are.  This prevents
                        # moderators setting owner status.
                        if ($role == User::ROLE_OWNER &&
                            $myrole != User::ROLE_OWNER &&
                            $membs[0]['role'] != User::ROLE_OWNER) {
                            $role = User::ROLE_MODERATOR;
                        }

                        # Now update with new settings.  Also set syncdelete so that we know this member still exists
                        # in the input data and therefore doesn't need deleting.
                        #
                        # This will have the effect of moving members between collections if required.
                        $added = pres('date', $member) ? ("'" . date ("Y-m-d", strtotime($member['date'])) . "'"): 'NULL';

                        $sql = "UPDATE memberships SET role = '$role', collection = '$collection', yahooPostingStatus = " . $this->dbhm->quote($yps) .
                               ", yahooDeliveryType = " . $this->dbhm->quote($ydt) . ", emailid = {$member['emailid']}, added = $added, syncdelete = 0 WHERE userid = " .
                                "{$member['uid']} AND groupid = {$this->id};";
                        $bulksql .= $sql;

                        # If this is a mod/owner, make sure the systemrole reflects that.
                        if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                            $sql = "UPDATE users SET systemrole = 'Moderator' WHERE id = {$member['uid']} AND systemrole = 'User';";
                            $bulksql .= $sql;
                        }

                        if ($count > 0 && $count % 1000 == 0) {
                            # Do a chunk of work.  If this doesn't work correctly we'll end up with fewer members
                            # and fail the count below.  Or we'll have incorrect settings until the next sync, but
                            # that's ok - better than failing it.
                            $this->dbhm->exec($bulksql);
                            $bulksql = '';
                        }
                    }
                }

                if ($bulksql != '') {
                    # Do remaining SQL.  If this fails then we'll fail the count check below.
                    $this->dbhm->exec($bulksql);
                }

                # Delete any residual members.  If this fails we have old members left over - so no need to rollback.
                #
                # We need to log these deletes so that we can see why memberships disappear.
                $todeletes = $this->dbhm->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND collection = '$collection' AND syncdelete = 1;", [ $this->id ]);
                $meid = $me ? $me->getId() : NULL;
                foreach ($todeletes as $todelete) {
                    $this->log->log([
                        'type' => Log::TYPE_GROUP,
                        'subtype' => Log::SUBTYPE_LEFT,
                        'user' => $todelete['userid'],
                        'byuser' => $meid,
                        'groupid' => $this->id,
                        'text' => 'Sync of whole membership list'
                    ]);
                }

                $this->dbhm->preExec("DELETE FROM memberships WHERE groupid = ? AND collection = '$collection' AND syncdelete = 1;", [ $this->id ]);

                # Now do a check on the number of members.  It should match the distinct number; if not then
                # something has gone wrong and we should abort.
                $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = ? AND collection = '$collection' ";
                $counts = $this->dbhm->preQuery($sql, [ $this->id ]);
                $count = $counts[0]['count'];

                $rollback = ($count != $distinct);

                if (!$rollback && $collection == MessageCollection::APPROVED) {
                    # Record the sync.  If this fails it's not worth a rollback.
                    $this->dbhm->preExec("UPDATE groups SET lastyahoomembersync = NOW() WHERE id = ?;", [$this->id]);
                }
            } catch (Exception $e) {
                error_log("Exception" . $e->getMessage());
                $rollback = TRUE;
            }

            if ($rollback) {
                # Something went wrong.
                $this->dbhm->rollBack();
                $ret = [
                    'ret' => 2,
                    'status' => 'Failed, for some reason'
                ];
            } else {
                $rollback = !$this->dbhm->commit();

                if ($rollback) {
                    $ret = [
                        'ret' => 3,
                        'status' => 'Commit failed'
                    ];
                }
            }
        }

        return($ret);
    }

    public function setSettings($settings)
    {
        $str = json_encode($settings);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->dbhm->preExec("UPDATE groups SET settings = ? WHERE id = ?;", [ $str, $this->id ]);
        $this->log->log([
            'type' => Log::TYPE_GROUP,
            'subtype' => Log::SUBTYPE_EDIT,
            'groupid' => $this->id,
            'byuser' => $me ? $me->getId() : NULL,
            'text' => $this->getEditLog([
                'settings' => $settings
            ])
        ]);

        return(true);
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
        $missingonserver = [];
        $missingonclient = [];

        # Check whether any of the messages in $messages are not present on the server or vice-versa.
        $supplied = [];
        $cs = [];

        # First find messages which are missing on the server, i.e. present in $messages but not
        # present in any of $collections.
        foreach ($collections as $collection) {
            $c = new MessageCollection($this->dbhr, $this->dbhm, $collection);
            $cs[] = $c;

            if ($collection = MessageCollection::APPROVED) {
                $this->dbhm->preExec("UPDATE groups SET lastyahoomessagesync = NOW() WHERE id = ?;", [
                    $this->id
                ]);
            }
        }

        if ($messages) {
            foreach ($messages as $message) {
                $key = $this->getKey($message);
                $supplied[$key] = true;

                $missing = true;

                foreach ($cs as $c) {
                    /** @var Collection $c */
                    $id = NULL;

                    switch (($c->getCollection())) {
                        case MessageCollection::APPROVED:
                            $id = $c->findByYahooApprovedId($this->id, $message['yahooapprovedid']);
                            break;
                        case MessageCollection::PENDING:
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
        }

        # Now find messages which are missing on the client, i.e. present in $collections but not present in
        # $messages.
        /** @var Collection $c */
        foreach ($cs as $c) {
            $sql = "SELECT id, fromaddr, yahoopendingid, yahooapprovedid, subject, date FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ? AND messages_groups.deleted = 0;";
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

        return ([$missingonserver, $missingonclient]);
    }

    public function getConfirmKey() {
        $key = randstr(32);
        $sql = "UPDATE groups SET confirmkey = ? WHERE id = ?;";
        $rc = $this->dbhm->preExec($sql, [ $key, $this->id ]);
        return($key);
    }
}