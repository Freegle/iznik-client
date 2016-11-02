<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/MembershipCollection.php');

class Group extends Entity
{
    # We have a cache of users, because we create users a _lot_, and this can speed things up significantly by avoiding
    # hitting the DB.
    static $cache = [];
    static $cacheDeleted = [];
    const CACHE_SIZE = 100;
    
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'nameshort', 'namefull', 'nameabbr', 'namedisplay', 'settings', 'type', 'logo',
        'onyahoo', 'onhere', 'trial', 'licenserequired', 'licensed', 'licenseduntil', 'membercount', 'lat', 'lng',
        'profile', 'cover', 'onmap', 'tagline', 'legacyid', 'showonyahoo', 'external');

    const GROUP_REUSE = 'Reuse';
    const GROUP_FREEGLE = 'Freegle';
    const GROUP_OTHER = 'Other';
    const GROUP_UT = 'UnitTest';

    const FILTER_NONE = 0;
    const FILTER_WITHCOMMENTS = 1;

    /** @var  $log Log */
    private $log;

    public $defaultSettings;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'groups', 'group', $this->publicatts);

        if ($id && !$this->id) {
            # We were passed an id, but didn't find the group.  See if the id is a legacyid.
            #
            # This assumes that the legacy and current ids don't clash.  Which they don't.  So that's a good assumption.
            $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE legacyid = ?;", [ $id ]);
            foreach ($groups as $group) {
                $this->fetch($dbhr, $dbhm, $group['id'], 'groups', 'group', $this->publicatts);
            }
        }

        $this->defaultSettings = [
            'showchat' => 1,
            'communityevents' => 1,
            'autoapprove' => [
                'members' => 0,
                'messages' => 0
            ], 'duplicates' => [
                'check' => 1,
                'offer' => 7,
                'taken' => 7,
                'wanted' => 14,
                'received' => 14
            ], 'spammers' => [
                'check' => $this->group['type'] == Group::GROUP_FREEGLE,
                'remove' => $this->group['type'] == Group::GROUP_FREEGLE,
                'chatreview' => $this->group['type'] == Group::GROUP_FREEGLE
            ], 'joiners' => [
                'check' => 1,
                'threshold' => 5
            ], 'keywords' => [
                'OFFER' => 'OFFER',
                'TAKEN' => 'TAKEN',
                'WANTED' => 'WANTED',
                'RECEIVED' => 'RECEIVED'
            ], 'reposts' => [
                'offer' => 2,
                'wanted' => 14,
                'max' => 10
            ]
        ];

        if (!$this->group['settings'] || strlen($this->group['settings']) == 0) {
            $this->group['settings'] = json_encode($this->defaultSettings);
        }

        $this->log = new Log($dbhr, $dbhm);
    }

    public static function get(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $gsecache = TRUE) {
        if ($id) {
            # We cache the constructed group.
            if ($gsecache && array_key_exists($id, Group::$cache) && Group::$cache[$id]->getId() == $id) {
                # We found it.
                #error_log("Found $id in cache");

                # @var Group
                $g = Group::$cache[$id];

                if (!Group::$cacheDeleted[$id]) {
                    # And it's not zapped - so we can use it.
                    #error_log("Not zapped");
                    return ($g);
                } else {
                    # It's zapped - so refetch.  It's important that we do this using the original DB handles, because
                    # whatever caused us to zap the cache might have done a modification operation which in turn
                    # zapped the SQL read cache.
                    #error_log("Zapped, refetch " . $id);
                    $g->fetch($g->dbhr, $g->dbhm, $id, 'groups', 'group', $g->publicatts);

                    if (!$g->group['settings'] || strlen($g->group['settings']) == 0) {
                        $g->group['settings'] = json_encode($g->defaultSettings);
                    }

                    Group::$cache[$id] = $g;
                    Group::$cacheDeleted[$id] = FALSE;
                    return($g);
                }
            }
        }

        # Not cached.
        #error_log("$id not in cache");
        $g = new Group($dbhr, $dbhm, $id);

        if ($id && count(Group::$cache) < Group::CACHE_SIZE) {
            # Store for next time
            #error_log("store $id in cache");
            Group::$cache[$id] = $g;
            Group::$cacheDeleted[$id] = FALSE;
        }

        return($g);
    }

    public static function clearCache($id = NULL) {
        # Remove this group from our cache.
        #error_log("Clear $id from cache");
        if ($id) {
            Group::$cacheDeleted[$id] = TRUE;
        } else {
            Group::$cache = [];
            Group::$cacheDeleted = [];
        }
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function getDefaults() {
        return($this->defaultSettings);
    }

    public function create($shortname, $type) {
        try {
            # Check for duplicate.  Might still occur in a timing window but in that rare case we'll get an exception
            # and catch that, failing the call.
            $groups = $this->dbhm->preQuery("SELECT id FROM groups WHERE nameshort = ?;", [ $shortname ]);
            foreach ($groups as $group) {
                return(NULL);
            }

            $rc = $this->dbhm->preExec("INSERT INTO groups (nameshort, type, founded) VALUES (?, ?, NOW())", [$shortname, $type]);
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

    public function getMods() {
        $sql = "SELECT users.id FROM users INNER JOIN memberships ON users.id = memberships.userid AND memberships.groupid = ? AND role IN ('Owner', 'Moderator');";
        $mods = $this->dbhr->preQuery($sql, [ $this->id ]);
        $ret = [];
        foreach ($mods as $mod) {
            $ret[] = $mod['id'];
        }
        return($ret);
    }

    public function getModsEmail() {
        return($this->group['nameshort'] . "-owner@yahoogroups.com");
    }

    public function getGroupEmail() {
        return($this->group['nameshort'] . "@yahoogroups.com");
    }

    public function getGroupSubscribe() {
        return($this->group['nameshort'] . "-subscribe@yahoogroups.com");
    }

    public function getGroupUnsubscribe() {
        return($this->group['nameshort'] . "-unsubscribe@yahoogroups.com");
    }

    public function getGroupNoEmail() {
        return($this->group['nameshort'] . "-nomail@yahoogroups.com");
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
        $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE nameshort = ?;",
            [$name]);
        foreach ($groups as $group) {
            return($group['id']);
        }

        return(NULL);
    }

    public function getWorkCounts($mysettings, $myid) {
        # Depending on our group settings we might not want to show this work as primary; "other" work is displayed
        # less prominently in the client.
        #error_log("Getworkcounts " . error_log(var_export($mysettings, true)));
        $showmessages = !array_key_exists('showmessages', $mysettings) || $mysettings['showmessages'];
        $showmembers = !array_key_exists('showmembers', $mysettings) || $mysettings['showmembers'];
        $spam = $showmessages ? 'spam' : 'spamother';

        # We only want to show spam messages upto 31 days old to avoid seeing too many, especially on first use.
        #
        # See also MessageCollection.
        $mysqltime = date ("Y-m-d", strtotime("Midnight 31 days ago"));
        $eventsqltime = date("Y-m-d H:i:s", time());

        $ret = [
            'pending' => $showmessages ? $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ? AND messages_groups.deleted = 0 AND messages.heldby IS NULL AND messages.deleted IS NULL;", [
                $this->id,
                MessageCollection::PENDING
            ])[0]['count'] : 0,
            'pendingother' => $showmessages ? $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ? AND messages_groups.deleted = 0 AND messages.heldby IS NOT NULL;", [
                $this->id,
                MessageCollection::PENDING
            ])[0]['count'] : 0,
            $spam => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ? AND messages.arrival >= '$mysqltime' AND messages_groups.deleted = 0 " . ($showmessages ? "AND messages.heldby IS NULL" : "") . ";", [
                $this->id,
                MessageCollection::SPAM
            ])[0]['count'],
            'pendingmembers' => $showmembers ? $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM memberships WHERE groupid = ? AND collection = ? AND memberships.heldby IS NULL;", [
                $this->id,
                MembershipCollection::PENDING
            ])[0]['count'] : 0,
            'pendingmembersother' => $showmembers ? $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM memberships WHERE groupid = ? AND collection = ? AND memberships.heldby IS NOT NULL;", [
                $this->id,
                MembershipCollection::PENDING
            ])[0]['count'] : 0,
            'pendingevents' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM communityevents INNER JOIN communityevents_dates ON communityevents_dates.eventid = communityevents.id INNER JOIN communityevents_groups ON communityevents.id = communityevents_groups.eventid WHERE communityevents_groups.groupid = ? AND communityevents.pending = 1 AND communityevents.deleted = 0 AND end >= ?;", [
                $this->id,
                $eventsqltime
            ])[0]['count'],
            'spammembers' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM users INNER JOIN memberships ON memberships.groupid = ? AND memberships.userid = users.id WHERE suspectcount > 0;", [
                $this->id
            ])[0]['count'],
            'plugin' => $this->dbhr->preQuery("SELECT COUNT(*) AS count FROM plugin WHERE groupid = ?;", [
                $this->id
            ])[0]['count'],
        ];
        return($ret);
    }

    public function getPublic() {
        $atts = parent::getPublic();

        # Add in derived properties.
        $atts['namedisplay'] = $atts['namefull'] ? $atts['namefull'] : $atts['nameshort'];
        $atts['lastyahoomembersync'] = ISODate($this->group['lastyahoomembersync']);
        $atts['lastyahoomessagesync'] = ISODate($this->group['lastyahoomessagesync']);
        $atts['settings'] = array_merge($this->defaultSettings, json_decode($atts['settings'], true));
        $atts['founded'] = ISODate($this->group['founded']);

        if (MODTOOLS) {
            $sql = "SELECT COUNT(*) AS count FROM memberships WHERE groupid = {$this->id} AND role IN ('Owner', 'Moderator');";
            $counts = $this->dbhr->preQuery($sql);
            $atts['nummods'] = $counts[0]['count'];
        }

        foreach (['trial', 'licensed', 'licenseduntil'] as $datefield) {
            $atts[$datefield] = $atts[$datefield] ? ISODate($atts[$datefield]) : NULL;
        }

        # Images
        $atts['profile'] = $atts['profile'] ? Attachment::getPath($atts['profile'], Attachment::TYPE_GROUP) : NULL;
        $atts['cover'] = $atts['cover'] ? Attachment::getPath($atts['cover'], Attachment::TYPE_GROUP) : NULL;

        return($atts);
    }

    public function exportYahoo($groupid) {
        $members = $this->dbhr->preQuery("SELECT members FROM memberships_yahoo_dump WHERE groupid = ?;", [ $groupid ]);
        foreach ($members as $member) {
            return(json_decode($member['members'], TRUE));
        }

        return(NULL);
    }

    public function getMembers($limit = 10, $search = NULL, &$ctx = NULL, $searchid = NULL, $collection = MembershipCollection::APPROVED, $groupids = NULL, $yps = NULL, $ydt = NULL, $ops = NULL, $filter = Group::FILTER_NONE) {
        $ret = [];
        $groupids = $groupids ? $groupids : ($this->id ? [ $this-> id ] : NULL);

        if ($search) {
            # Remove wildcards - people put them in, but that's not how it works.
            $search = str_replace('*', '', $search);
        }

        $date = $ctx == NULL ? NULL : $this->dbhr->quote(date("Y-m-d H:i:s", $ctx['Added']));
        $addq = $ctx == NULL ? '' : (" AND (memberships.added < $date OR memberships.added = $date AND memberships.id < " . $this->dbhr->quote($ctx['id']) . ") ");
        $groupq = $groupids ? " memberships.groupid IN (" . implode(',', $groupids) . ") " : " 1=1 ";
        $ypsq = $yps ? (" AND memberships_yahoo.yahooPostingStatus = " . $this->dbhr->quote($yps)) : '';
        $ydtq = $ydt ? (" AND memberships_yahoo.yahooDeliveryType = " . $this->dbhr->quote($ydt)) : '';
        $opsq = $ops ? (" AND memberships.ourPostingStatus = " . $this->dbhr->quote($ydt)) : '';

        switch ($filter) {
            case Group::FILTER_WITHCOMMENTS:
                $filterq = ' INNER JOIN users_comments ON users_comments.userid = memberships.userid ';
                $filterq = $groupids ? ("$filterq AND users_comments.groupid IN (" . implode(',', $groupids) . ") ") : $filterq;
                break;
            default:
                $filterq = '';
                break;
        }

        # Collection filter.  If we're searching on a specific id then don't put it in.
        $collectionq = '';
        if (!$searchid) {
            if ($collection == MembershipCollection::SPAM) {
                # This collection is handled separately; we use the suspectcount field.
                #
                # This is to avoid moving members into a spam collection and then having to remember whether they
                # came from Pending or Approved.
                $collectionq = ' AND suspectcount > 0 ';
            } else if ($collection) {
                $collectionq = ' AND memberships.collection = ' . $this->dbhr->quote($collection) . ' ';
            }
        }

        $sqlpref = "SELECT DISTINCT memberships.*, memberships_yahoo.emailid, memberships_yahoo.yahooAlias, 
              memberships_yahoo.yahooPostingStatus, memberships_yahoo.yahooDeliveryType, memberships_yahoo.yahooapprove, 
              memberships_yahoo.yahooreject, memberships_yahoo.joincomment FROM memberships 
              LEFT JOIN memberships_yahoo ON memberships.id = memberships_yahoo.membershipid 
              LEFT JOIN users_emails ON memberships.userid = users_emails.userid 
              INNER JOIN users ON users.id = memberships.userid 
              $filterq";

        if ($search) {
            # We're searching.  It turns out to be more efficient to get the userids using the indexes, and then
            # get the rest of the stuff we need.
            $q = $this->dbhr->quote("$search%");
            $sql = "$sqlpref 
              WHERE users.id IN (SELECT * FROM (
                (SELECT userid FROM users_emails WHERE email LIKE $q) UNION
                (SELECT id FROM users WHERE id = " . $this->dbhr->quote($search) . ") UNION
                (SELECT id FROM users WHERE fullname LIKE $q) UNION
                (SELECT id FROM users WHERE yahooid LIKE $q) UNION
                (SELECT userid FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id WHERE yahooAlias LIKE $q)
              ) t) AND 
              $groupq $collectionq $addq $ypsq $ydtq $opsq";
        } else {
            $searchq = $searchid ? (" AND users.id = " . $this->dbhr->quote($searchid) . " ") : '';
            $sql = "$sqlpref WHERE $groupq $collectionq $addq $searchq $ypsq $ydtq $opsq";
        }

        $sql .= " ORDER BY memberships.added DESC, memberships.id DESC LIMIT $limit;";

        $members = $this->dbhr->preQuery($sql);
        #error_log($sql);

        $ctx = [ 'Added' => NULL ];

        foreach ($members as $member) {
            $u = User::get($this->dbhr, $this->dbhm, $member['userid']);
            $thisone = $u->getPublic($groupids, TRUE);
            #error_log("{$member['userid']} has " . count($thisone['comments']));

            # We want to return an id of the membership, because the same user might be pending on two groups, and
            # a userid of the user's id.
            $thisone['userid'] = $thisone['id'];
            $thisone['id'] = $member['id'];

            $thisepoch = strtotime($member['added']);

            if ($ctx['Added'] == NULL || $thisepoch < $ctx['Added']) {
                $ctx['Added'] = $thisepoch;
            }

            $ctx['id'] = $member['id'];

            # We want to return both the email used on this group and any others we have.
            $emails = $u->getEmails();
            $email = NULL;
            $others = [];
            foreach ($emails as $anemail) {
                if ($anemail['id'] == $member['emailid']) {
                    $email = $anemail['email'];
                }

                $others[] = $anemail;
            }
            
            $thisone['joined'] = ISODate($member['added']);

            # Defaults match ones in User.php
            $thisone['settings'] = $member['settings'] ? json_decode($member['settings'], TRUE) : [
                'showmessages' => 1,
                'showmembers' => 1,
                'pushnotify' => 1
            ];

            $thisone['settings']['configid'] = $member['configid'];
            $thisone['email'] = $email;
            $thisone['groupid'] = $member['groupid'];
            $thisone['otheremails'] = $others;
            $thisone['yahooDeliveryType'] = $member['yahooDeliveryType'];
            $thisone['yahooPostingStatus'] = $member['yahooPostingStatus'];
            $thisone['yahooAlias'] = $member['yahooAlias'];
            $thisone['role'] = $u->getRoleForGroup($member['groupid']);
            $thisone['joincomment'] = $member['joincomment'];
            $thisone['emailfrequency'] = $member['emailfrequency'];
            $thisone['ourPostingStatus'] = $member['ourPostingStatus'];

            $thisone['heldby'] = $member['heldby'];

            if (pres('heldby', $thisone)) {
                $u = User::get($this->dbhr, $this->dbhm, $thisone['heldby']);
                $thisone['heldby'] = $u->getPublic();
            }

            $ret[] = $thisone;
        }

        return($ret);
    }
    
    private function getYahooRole($memb) {
        $yahoorole = User::ROLE_MEMBER;
        if (pres('yahooModeratorStatus', $memb)) {
            if ($memb['yahooModeratorStatus'] == 'MODERATOR') {
                $yahoorole = User::ROLE_MODERATOR;
            } else if ($memb['yahooModeratorStatus'] == 'OWNER') {
                $yahoorole = User::ROLE_OWNER;
            }
        }
        
        return($yahoorole);
    }

    public function queueSetMembers($members, $synctime) {
        # This is used for Approved members only, and will be picked up by a background script which calls
        # setMembers.  This is used to move this expensive processing off the application server.
        $this->dbhm->preExec("REPLACE INTO memberships_yahoo_dump (groupid, members, lastupdated, synctime) VALUES (?,?,NOW(),?);", [$this->id, json_encode($members), $synctime]);
    }

    public function processSetMembers($groupid = NULL) {
        # This is called from the background script.  It's serialised, so we don't need to worry about other
        # copies.
        $sql = $groupid ? "SELECT * FROM memberships_yahoo_dump WHERE groupid = $groupid;" : "SELECT * FROM memberships_yahoo_dump WHERE lastprocessed IS NULL OR lastupdated > lastprocessed AND backgroundok = 1;";
        $groups = $this->dbhr->preQuery($sql);
        $count = 0;

        foreach ($groups as $group) {
            $g = Group::get($this->dbhm, $this->dbhm, $group['groupid']);
            try {
                # Use master for sync to avoid caching, which can break our sync process.
                error_log("Sync group " . $g->getPrivate('nameshort') . " $count / " . count($groups) . " time {$group['synctime']}");
                $g->setMembers(json_decode($group['members'], TRUE),  MembershipCollection::APPROVED, $group['synctime']);
                $this->dbhm->preExec("UPDATE memberships_yahoo_dump SET lastprocessed = NOW() WHERE groupid = ?;", [ $group['groupid']]);
            } catch (Exception $e) {
                error_log("Sync failed with " . $e->getMessage());
            }

            $count++;
        }
    }

    public function setMembers($members, $collection, $synctime = NULL) {
        # This is used to set the whole of the membership list for a group.  It's only used when the group is
        # mastered on Yahoo, rather than by us.
        #
        # Slightly surprisingly, we don't need to do this in a transaction.  This is because:
        # - adding memberships on here which exist on Yahoo is fine to fail halfway through, we've just got some
        #   of them which is better than we were before we started, and the remainder will get added on the next
        #   sync.
        # - updating membership details from Yahoo to here is similarly fine
        # - deleting memberships on here which are no longer on Yahoo is a single statement, but even if that
        #   failed partway through it would still be fine; we'd have removed some of them which is better than
        #   nothing, and the remainder would get removed on the next sync.
        #
        # So as long as we only return a success when it's worked, we don't need to be in a transaction.  This is
        # good as it would be a large transaction and would hit lock timeouts.
        $ret = [
            'ret' => 0,
            'status' => 'Success'
        ];

        $synctime = $synctime ? $synctime : date("Y-m-d H:i:s", time());

        # Really don't want to remove all members by mistake, so don't allow it.
        if (!$members && $collection == MembershipCollection::APPROVED) { return($ret); }

        try {
            #$this->dbhm->setErrorLog(TRUE);

            # First make sure we have users set up for all the new members.  The input might have duplicate members;
            # save off the uid, and work out the role.
            $u = User::get($this->dbhm, $this->dbhm);
            $overallroles = [];
            $count = 0;

            #$news = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id AND groupid = {$this->id};");
            #error_log("Yahoo membs before scan" . var_export($news, TRUE));

            error_log("Scan members {$this->group['nameshort']}");
            foreach ($members as &$memb) {
                # Long
                set_time_limit(60);

                if (pres('email', $memb)) {
                    # First check if we already know about this user.  This is a good time to pick up duplicates -
                    # the same yahooid or yahooUserId means this is the same user, so we should merge.
                    #
                    # If the merge fails for some reason we'd still want to continue the sync.
                    $yuid = presdef('yahooid', $memb, NULL) ? $u->findByYahooId($memb['yahooid']) : NULL;
                    $yiduid = presdef('yahooUserId', $memb, NULL) ? $u->findByYahooUserId($memb['yahooUserId']) : NULL;
                    $emailinfo = $u->getIdForEmail($memb['email']);
                    $emailid = $emailinfo ? $emailinfo['userid'] : NULL;

                    $reason = "SetMembers {$this->group['nameshort']} - YahooId " . presdef('yahooid', $memb, '') . " = $yuid, YahooUserId " . presdef('yahooUserId', $memb, '') . " = $yiduid, Email {$memb['email']} = $emailid";

                    # Now merge any different ones.
                    if ($emailid && $yuid && $emailid != $yuid) {
                        $mergerc = $u->merge($emailid, $yuid, $reason);
                        #error_log($reason);
                    }

                    if ($emailid && $yiduid && $emailid != $yiduid && $yiduid != $yuid) {
                        $mergerc = $u->merge($emailid, $yiduid, $reason);
                        #error_log($reason);
                    }

                    # Pick a non-null one.
                    $uid = $emailid ? $emailid : ($yuid ? $yuid : $yiduid);
                    #error_log("uid $uid yuid $yuid yiduid $yiduid");

                    if (!$uid) {
                        # We don't - create them.
                        preg_match('/(.*)@/', $memb['email'], $matches);
                        $name = presdef('name', $memb, $matches[1]);
                        $uid = $u->create(NULL, NULL, $name, "During SetMembers for {$this->group['nameshort']}", presdef('yahooUserId', $memb, NULL), presdef('yahooid', $memb, NULL));
                        #error_log("Create $uid will have email " . presdef('email', $memb, '') . " yahooid " . presdef('yahooid', $memb, ''));
                    } else {
                        $u = User::get($this->dbhr, $this->dbhm, $uid);
                    }

                    # Make sure that the email is associated with this user.  Note that this may be required even
                    # if we succeeded in our findByEmail above, as that may have found a different email with the
                    # same canon value.
                    #
                    # Don't flag it as a primary email otherwise we might override the one we have.
                    $memb['emailid'] = $u->addEmail($memb['email'], 0, FALSE);

                    if (pres('yahooUserId', $memb)) {
                        $u->setPrivate('yahooUserId', $memb['yahooUserId']);
                    }

                    # If we don't have a yahooid for this user, update it.  If we already have one, then stick with it
                    # to avoid updating a user with an old Yahoo id
                    if (pres('yahooid', $memb) && !$u->getPrivate('yahooid')) {
                        $u->setPrivate('yahooid', $memb['yahooid']);
                    }

                    # Remember the uid for later below.
                    $memb['uid'] = $uid;
                    $distinctmembers[$uid] = TRUE;

                    # Get the role.  We might have the same underlying user who is a member using multiple email addresses
                    # so we need to take the max role that they have.
                    $yahoorole = $this->getYahooRole($memb);
                    $overallrole = pres($uid, $overallroles) ? $u->roleMax($overallroles[$uid], $yahoorole) : $yahoorole;
                    $overallroles[$uid] = $overallrole;
                }

                $count++;

                if ($count % 1000 == 0) {
                    error_log("...$count");
                }
            }

            #$news = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id AND groupid = {$this->id};");
            #error_log("Yahoo membs after scan" . var_export($news, TRUE));

            error_log("Scanned members {$this->group['nameshort']}");

            $me = whoAmI($this->dbhr, $this->dbhm);
            $myrole = $me ? $me->getRoleForGroup($this->id) : User::ROLE_NONMEMBER;
            #error_log("myrole in setGroup $myrole id " . $me->getId() . " from " . $me->getRoleForGroup($this->id) . " session " . $_SESSION['id']);

            # Save off the list of members which currently exist, so that after we've processed the ones which currently
            # exist, we can remove any which should no longer be present.
            #
            # We only want the members upto the point where the sync started, otherwise we might remove a member who has
            # just joined.
            $mysqltime = date("Y-m-d H:i:s", strtotime($synctime));
            $this->dbhm->preExec("DROP TEMPORARY TABLE IF EXISTS syncdelete; CREATE TEMPORARY TABLE syncdelete (emailid INT UNSIGNED, PRIMARY KEY idkey(emailid));");
            $sql = "INSERT INTO syncdelete (SELECT DISTINCT memberships_yahoo.emailid FROM memberships_yahoo INNER JOIN memberships ON memberships.id = memberships_yahoo.membershipid WHERE groupid = {$this->id} AND memberships_yahoo.collection = '$collection' AND memberships_yahoo.added < '$mysqltime');";
            $this->dbhm->preExec($sql);

            #$resid = $this->dbhm->preQuery("SELECT memberships_yahoo.id, emailid FROM memberships_yahoo WHERE emailid IN (SELECT emailid FROM syncdelete);");
            #error_log("Syncdelete at start" . var_export($resid, TRUE));

            $bulksql = '';
            $tried = 0;

            error_log("Update members {$this->group['nameshort']} role $myrole");

            for ($count = 0; $count < count($members); $count++) {
                # Long
                set_time_limit(60);

                $member = $members[$count];
                #error_log("Update member " . var_export($member, TRUE));

                if (pres('uid', $member)) {
                    $tried++;
                    $overallrole = $overallroles[$member['uid']];

                    # Use a single SQL statement rather than the usual methods for performance reasons.  And then
                    # batch them up into groups because that performs better in a cluster.
                    $yps = presdef('yahooPostingStatus', $member, NULL);
                    $ydt = presdef('yahooDeliveryType', $member, NULL);
                    $yahooAlias = presdef('yahooAlias', $member, NULL);
                    $joincomment = pres('joincomment', $member) ? $this->dbhm->quote($member['joincomment']) : 'NULL';

                    # Get any existing Yahoo membership for this user with this email.
                    $sql = "SELECT memberships_yahoo.* FROM memberships_yahoo INNER JOIN memberships ON memberships.id = memberships_yahoo.membershipid WHERE userid = ? AND groupid = ? AND emailid = ?;";
                    $yahoomembs = $this->dbhm->preQuery($sql, [
                        $member['uid'], 
                        $this->id, 
                        $member['emailid']
                    ]);
                    #error_log("$sql, {$member['uid']}, {$this->id}, {$member['emailid']}");
                    
                    $new = count($yahoomembs) == 0;

                    $added = pres('date', $member) ? ("'" . date("Y-m-d H:i:s", strtotime($member['date'])) . "'") : 'NULL';

                    if ($member['emailid']) {
                        if ($new) {
                            # Make sure the top-level and the Yahoo memberships are both present.
                            # We don't want to REPLACE as that might lose settings.
                            # We also don't want to just do INSERT IGNORE without having checked first as that doesn't
                            # perform well in clusters.
                            $bulksql .= "INSERT IGNORE INTO memberships (userid, groupid, collection) VALUES ({$member['uid']}, {$this->id}, '$collection');";
                            $bulksql .= "INSERT IGNORE INTO memberships_yahoo (membershipid, emailid, collection) VALUES ((SELECT id FROM memberships WHERE userid = {$member['uid']} AND groupid = {$this->id}), {$member['emailid']}, '$collection');";
                            
                            # Default the Yahoo membership to a user.
                            $yahoomembs = [
                                [
                                    'role' => User::ROLE_MEMBER,
                                    'collection' => $collection
                                ]
                            ];

                            # Make sure we have a history entry.
                            $sql = "SELECT * FROM memberships_history WHERE userid = ? AND groupid = ?;";
                            $hists = $this->dbhr->preQuery($sql, [$member['uid'], $this->id]);

                            if (count($hists) == 0) {
                                $bulksql .= "INSERT INTO memberships_history (userid, groupid, collection, added) VALUES ({$member['uid']},{$this->id},'$collection',$added);";
                            }
                        }

                        # If we are promoting a member, then we can only promote as high as we are.  This prevents
                        # moderators setting owner status.
                        if ($overallrole == User::ROLE_OWNER &&
                            $myrole != User::ROLE_OWNER &&
                            $yahoomembs[0]['role'] != User::ROLE_OWNER
                        ) {
                            $overallrole = User::ROLE_MODERATOR;
                        }

                        # Now update with any new settings.  Having this if test looks a bit clunky but it means that
                        # when resyncing a group where most members have not changed settings, we can avoid many UPDATEs.
                        #
                        # This will have the effect of moving members between collections if required.
                        $yahoorole = $this->getYahooRole($memb);
                        
                        if ($new ||
                            $yahoomembs[0]['role'] != $yahoorole || $yahoomembs[0]['collection'] != $collection || $yahoomembs[0]['yahooPostingStatus'] != $yps || $yahoomembs[0]['yahooDeliveryType'] != $ydt || $yahoomembs[0]['joincomment'] != $joincomment || $yahoomembs[0]['emailid'] != $member['emailid'] || $yahoomembs[0]['added'] != $added || $yahoomembs[0]['yahooAlias'] != $yahooAlias)
                        {
                            $bulksql .=  "UPDATE memberships SET role = '$overallrole', collection = '$collection', added = $added WHERE userid = " .
                                "{$member['uid']} AND groupid = {$this->id};";
                            $sql = "UPDATE memberships_yahoo SET role = '$yahoorole', collection = '$collection', yahooPostingStatus = " . $this->dbhm->quote($yps) . ", yahooAlias = " . $this->dbhm->quote($yahooAlias) .
                                ", yahooDeliveryType = " . $this->dbhm->quote($ydt) . ", joincomment = $joincomment, added = $added WHERE membershipid = (SELECT id FROM memberships WHERE userid = " .
                                "{$member['uid']} AND groupid = {$this->id}) AND emailid = {$member['emailid']};";
                            $bulksql .= $sql;
                        }

                        # If this is a mod/owner, make sure the systemrole reflects that.
                        if ($overallrole == User::ROLE_MODERATOR || $overallrole == User::ROLE_OWNER) {
                            $sql = "UPDATE users SET systemrole = 'Moderator' WHERE id = {$member['uid']} AND systemrole = 'User';";
                            User::clearCache($member['uid']);
                            $bulksql .= $sql;
                        }

                        # Record that this membership still exists by deleting their id from the temp table
                        #error_log("Delete from syncdelete " . var_export($member, TRUE));
                        $sql = "DELETE FROM syncdelete WHERE emailid = {$member['emailid']};";
                        $bulksql .= $sql;

                        if ($count > 0 && $count % 1000 == 0) {
                            # Do a chunk of work.  If this doesn't work correctly we'll end up with fewer members
                            # and fail the count below.  Or we'll have incorrect settings until the next sync, but
                            # that's ok - better than failing it.
                            #error_log($bulksql);
                            #error_log("Execute batch $count {$this->group['nameshort']}");
                            $this->dbhm->exec($bulksql);
                            #error_log("Executed batch $count {$this->group['nameshort']}");
                            $bulksql = '';
                        }
                    }
                }
            }

            if ($bulksql != '') {
                # Do remaining SQL.  If this fails then we'll fail the count check below.
                #error_log("Bulksql $bulksql");
                $this->dbhm->exec($bulksql);
            }

            error_log("Updated members {$this->group['nameshort']}");

            #$news = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id AND groupid = {$this->id};");
            #error_log("Yahoo membs after update" . var_export($news, TRUE));

            # Delete any residual Yahoo memberships.
            #$resid = $this->dbhm->preQuery("SELECT memberships_yahoo.id, emailid FROM memberships_yahoo WHERE emailid IN (SELECT emailid FROM syncdelete) AND membershipid IN (SELECT id FROM memberships WHERE groupid = ? AND collection = ?);", [$this->id, $collection]);
            #error_log(var_export($resid, TRUE));
            $rc = $this->dbhm->preExec("DELETE FROM memberships_yahoo WHERE emailid IN (SELECT emailid FROM syncdelete) AND membershipid IN (SELECT id FROM memberships WHERE groupid = ? AND collection = ?);", [$this->id, $collection]);
            #error_log("Deleted $rc Yahoo Memberships");

            #$news = $this->dbhm->preQuery("SELECT * FROM memberships_yahoo INNER JOIN memberships ON memberships_yahoo.membershipid = memberships.id AND groupid = {$this->id};");
            #error_log("Yahoo membs delete Yahoo " . var_export($news, TRUE));

            # Now that we've deleted the Yahoo membership, see if this means that we no longer have any Yahoo
            # memberships on this group (recall that we might have multiple Yahoo memberships with different email
            # addresses for the same group).  If so, then we want to delete the overall membership, and also log
            # the deletes so that we can see why memberships disappear.
            $todeletes = $this->dbhm->preQuery("SELECT memberships.id, memberships.userid FROM memberships LEFT JOIN memberships_yahoo ON memberships.id = memberships_yahoo.membershipid WHERE membershipid IS NULL AND groupid = ? AND memberships.collection = ?;", [$this->id, $collection]);
            #error_log("Overall to delete " . var_export($todeletes, TRUE));
            #error_log("Delete overall memberships " . count($todeletes));
            $meid = $me ? $me->getId() : NULL;
            foreach ($todeletes as $todelete) {
                # Long
                set_time_limit(60);

                if ($collection == MembershipCollection::APPROVED) {
                    # No point logging removal of pending members - that's normal.
                    $this->log->log([
                        'type' => Log::TYPE_GROUP,
                        'subtype' => Log::SUBTYPE_LEFT,
                        'user' => $todelete['userid'],
                        'byuser' => $meid,
                        'groupid' => $this->id,
                        'text' => "Sync of whole $collection membership list"
                    ]);
                }

                $this->dbhm->preExec("DELETE FROM memberships WHERE id = ?;", [ $todelete['id'] ]);
            }

            # Having logged them, delete them.
            $this->dbhm->preExec("DROP TEMPORARY TABLE syncdelete;");

            error_log("Tidied members {$this->group['nameshort']}");

            if ($collection == MessageCollection::APPROVED) {
                # Record the sync.
                $this->dbhm->preExec("UPDATE groups SET lastyahoomembersync = NOW() WHERE id = ?;", [$this->id]);
                Group::clearCache($this->id);
            }
        } catch (Exception $e) {
            $ret = [ 'ret' => 2, 'status' => "Sync failed with " . $e->getMessage() ];
            error_log(var_export($ret, TRUE));
        }

        return($ret);
    }

    public function setSettings($settings)
    {
        $str = json_encode($settings);
        $me = whoAmI($this->dbhr, $this->dbhm);
        $this->dbhm->preExec("UPDATE groups SET settings = ? WHERE id = ?;", [ $str, $this->id ]);
        Group::clearCache($this->id);
        $this->group['settings'] = $str;
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

    public function getSetting($key, $def) {
        $settings = json_decode($this->group['settings'], true);
        return(array_key_exists($key, $settings) ? $settings[$key] : $def);
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
        $pending = FALSE;
        $approved = FALSE;

        foreach ($collections as $collection) {
            # We can get called with Spam for either an approved or a pending correlate; we want to
            # know which it is.
            if ($collection == MessageCollection::APPROVED) {
                $approved = TRUE;
            }
            if ($collection == MessageCollection::PENDING) {
                $pending = TRUE;
            }

            $c = new MessageCollection($this->dbhr, $this->dbhm, $collection);
            $cs[] = $c;

            if ($collection = MessageCollection::APPROVED) {
                $this->dbhm->preExec("UPDATE groups SET lastyahoomessagesync = NOW() WHERE id = ?;", [
                    $this->id
                ]);
                Group::clearCache($this->id);
            }
        }

        if ($messages) {
            foreach ($messages as $message) {
                $key = $this->getKey($message);
                $supplied[$key] = true;
                $id = NULL;

                # Don't use the collection to find it, as it could be in spam.
                if (pres('yahooapprovedid', $message)) {
                    $id = $c->findByYahooApprovedId($this->id, $message['yahooapprovedid']);
                } else if (pres('yahoopendingid', $message)) {
                    $id = $c->findByYahooPendingId($this->id, $message['yahoopendingid']);
                }

                if (!$id) {
                    $missingonserver[] = $message;
                }
            }
        }

        # Now find messages which are missing on the client, i.e. present in $collections but not present in
        # $messages.
        /** @var MessageCollection $c */
        foreach ($cs as $c) {
            $sql = "SELECT id, source, fromaddr, yahoopendingid, yahooapprovedid, subject, date, messageid FROM messages INNER JOIN messages_groups ON messages.id = messages_groups.msgid AND messages_groups.groupid = ? AND messages_groups.collection = ? AND messages_groups.deleted = 0;";
            $ourmsgs = $this->dbhr->preQuery(
                $sql,
                [
                    $this->id,
                    $c->getCollection()[0]
                ]
            );

            foreach ($ourmsgs as $msg) {
                $key = $this->getKey($msg);
                if (!array_key_exists($key, $supplied)) {
                    # We check where the message came from to decide whether to return it.  This is because we
                    # might have a message currently in spam from YAHOO_APPROVED, and we might be doing a
                    # correlate on pending, and we don't want to return that message as missing.
                    #
                    # We could have a message in originally in Pending which we have later received because it's been
                    # approved elsewhere, in which case we'll have updated the source to Approved, but we want to
                    # include that.
                    $source = $msg['source'];
                    #error_log("Consider {$msg['id']} missing on client $pending, $approved, $source");
                    if (($pending && ($source == Message::YAHOO_PENDING || ($msg['yahoopendingid'] && $source == Message::YAHOO_APPROVED))) ||
                        ($approved && $source == Message::YAHOO_APPROVED)) {
                        $missingonclient[] = [
                            'id' => $msg['id'],
                            'email' => $msg['fromaddr'],
                            'subject' => $msg['subject'],
                            'collection' => $c->getCollection()[0],
                            'date' => ISODate($msg['date']),
                            'messageid' => $msg['messageid'],
                            'yahoopendingid' => $msg['yahoopendingid'],
                            'yahooapprovedid' => $msg['yahooapprovedid']
                        ];
                    }
                }
            }
        }

        return ([$missingonserver, $missingonclient]);
    }

    public function getConfirmKey() {
        $key = NULL;

        # Don't reset the key each time, otherwise we can have timing windows where the key is reset, thereby
        # invalidating an invitation which is in progress.
        $groups = $this->dbhr->preQuery("SELECT confirmkey FROM groups WHERE id = ?;" , [ $this->id ]);
        foreach ($groups as $group) {
            $key = $group['confirmkey'];
        }

        if (!$key) {
            $key = randstr(32);
            $sql = "UPDATE groups SET confirmkey = ? WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $key, $this->id ]);
            Group::clearCache($this->id);
        }

        return($key);
    }

    public function createVoucher() {

        do {
            $voucher = randstr(20);
            $sql = "INSERT INTO vouchers (voucher) VALUES (?);";
            $rc = $this->dbhm->preExec($sql, [ $voucher ]);
        } while (!$rc);

        return($voucher);
    }

    public function redeemVoucher($voucher) {
        $ret = FALSE;
        $me = whoAmI($this->dbhr, $this->dbhm);
        $myid = $me ? $me->getId() : NULL;

        $sql = "SELECT * FROM vouchers WHERE voucher = ? AND used IS NULL;";
        $vs = $this->dbhr->preQuery($sql , [ $voucher ]);

        foreach ($vs as $v) {
            $this->dbhm->beginTransaction();

            $sql = "UPDATE groups SET publish = 1, licensed = CURDATE(), licenseduntil = CASE WHEN licenseduntil > CURDATE() THEN licenseduntil + INTERVAL 1 YEAR ELSE CURDATE() + INTERVAL 1 YEAR END WHERE id = ?;";
            $rc = $this->dbhm->preExec($sql, [ $this->id ]);
            Group::clearCache($this->id);

            if ($rc) {
                $sql = "UPDATE vouchers SET used = NOW(), userid = ?, groupid = ? WHERE id = ?;";
                $rc = $this->dbhm->preExec($sql, [
                    $myid,
                    $this->id,
                    $v['id']
                ]);

                if ($rc) {
                    $rc = $this->dbhm->commit();

                    if ($rc) {
                        $ret = TRUE;
                        $this->log->log([
                            'type' => Log::TYPE_GROUP,
                            'subtype' => Log::SUBTYPE_LICENSED,
                            'groupid' => $this->id,
                            'text' => "Using voucher $voucher"
                        ]);
                    }
                }
            }
        }

        return($ret);
    }

    public function onYahoo() {
        return($this->group['onyahoo']);
    }

   public function listByType($type) {
       $me = whoAmI($this->dbhr, $this->dbhm);
       $typeq = $type ? "type = ?" : '1=1';
        $sql = "SELECT id, nameshort, namefull, lat, lng, poly, onhere, onyahoo, onmap, external, showonyahoo, profile, tagline FROM groups WHERE $typeq AND publish = 1 AND listable = 1 ORDER BY CASE WHEN namefull IS NOT NULL THEN namefull ELSE nameshort END;";
        $groups = $this->dbhr->preQuery($sql, [ $type ]);
        foreach ($groups as &$group) {
            $group['namedisplay'] = $group['namefull'] ? $group['namefull'] : $group['nameshort'];
            $group['profile'] = $group['profile'] ? Attachment::getPath($group['profile'], Attachment::TYPE_GROUP) : NULL;

            if (!$me || !$me->isModerator()) {
                $group['polygon'] = NULL;
            }
        }

        return($groups);
   }
}