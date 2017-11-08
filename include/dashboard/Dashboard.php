<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/misc/Stats.php');

# This gives us a summary of what we need to know for this user
class Dashboard {
    private $dbhr;
    private $dbhm;
    private $me;
    private $stats;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $me) {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->me = $me;
        $this->stats = new Stats($dbhr, $dbhm);
    }

    public function get($systemwide, $allgroups, $groupid, $authorityid, $region, $type, $start = '30 days ago') {
        $groupids = [];

        # Get the possible groups.
        if ($systemwide) {
            $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE publish = 1;");
            foreach ($groups as $group) {
                $groupids[] = $group['id'];
            }
        } else if ($region) {
            $groups = $this->dbhr->preQuery("SELECT groups.id FROM groups WHERE region LIKE ?;;", [ $region ]);
            foreach ($groups as $group) {
                $groupids[] = $group['id'];
            }
        } else if ($authorityid) {
            # We use groups where the core area overlaps.
            $groups = $this->dbhr->preQuery("SELECT groups.id FROM groups INNER JOIN authorities ON ST_Intersects(GeomFromText(polyofficial), COALESCE(simplified, polygon)) WHERE polyofficial IS NOT NULL AND authorities.id = ?;", [ $authorityid ]);
            foreach ($groups as $group) {
                $groupids[] = $group['id'];
            }
        } else if ($groupid) {
            $groupids[] = $groupid;
        } else if ($this->me && $allgroups) {
            $groupids = $this->me->getModeratorships();
        }

        $groupids = count($groupids) == 0 ? [0] : $groupids;
        if ($type) {
            # Filter by type
            $groups = $this->dbhr->preQuery("SELECT id FROM groups WHERE id IN (" . implode(',', $groupids) . ") AND type = ?;", [ $type ]);
            $groupids = [0];
            foreach ($groups as $group) {
                $groupids[] = $group['id'];
            }
        }

        $ret = $this->stats->getMulti(date ("Y-m-d"), $groupids, $start, "today", $systemwide);

        if ($groupid) {
            if ($this->me && $this->me->isModerator()) {
                # For specific groups we return info about when mods were last active.
                $mods = $this->dbhr->preQuery("SELECT userid FROM memberships WHERE groupid = ? AND role IN ('Moderator', 'Owner');", [ $groupid ]);
                $active = [];
                foreach ($mods as $mod) {
                    # A mod counts as active if they perform activity for this group on here, or if we know that they have
                    # approved a message (which might be on Yahoo).
                    $logs = $this->dbhr->preQuery("SELECT MAX(timestamp) AS lastactive FROM logs WHERE groupid = ? AND byuser = ?;", [ $groupid, $mod['userid']] );
                    $lastactive = $logs[0]['lastactive'];

                    if (!$lastactive) {
                        $approved = $this->dbhr->preQuery("SELECT MAX(arrival) AS lastactive FROM messages_groups WHERE groupid = ? AND approvedby = ?;", [ $groupid, $mod['userid']] );
                        $lastactive = $approved[0]['lastactive'];
                    }

                    $u = User::get($this->dbhr, $this->dbhm, $mod['userid']);

                    $active[$mod['userid']] = [
                        'displayname' => $u->getName(),
                        'lastactive' => $lastactive ? ISODate($lastactive) : NULL
                    ];
                }

                usort($active, function($mod1, $mod2) {
                    return(strcmp($mod2['lastactive'], $mod1['lastactive']));
                });

                $ret['modinfo'] = $active;
            }
        }

        # We also want to get the recent outcomes, where we know them.
        $mysqltime = date("Y-m-d", strtotime("Midnight 30 days ago"));
        foreach ([Message::TYPE_OFFER, Message::TYPE_WANTED] as $type) {
            $outcomes = $this->dbhr->preQuery("SELECT messages_outcomes.outcome, COUNT(*) AS count FROM messages_groups INNER JOIN messages_outcomes ON messages_outcomes.msgid = messages_groups.msgid WHERE messages_groups.arrival >= ? AND groupid IN (" . implode(',', $groupids) . ") AND msgtype = ? GROUP BY outcome;", [
                $mysqltime,
                $type
            ]);

            $ret['Outcomes'][$type] = $outcomes;
        }

        if ($groupid) {
            # Also get the donations this year.
            $mysqltime = date("Y-m-d H:i:s", strtotime("midnight 1st January this year"));

            $sql = "SELECT SUM(GrossAmount) AS total FROM users_donations INNER JOIN memberships ON users_donations.userid = memberships.userid AND memberships.groupid = ? WHERE users_donations.timestamp > '$mysqltime' AND payer != 'ppgfukpay@paypalgivingfund.org';";
            $donations = $this->dbhr->preQuery($sql, [ $groupid ]);
            $ret['donationsthisyear'] = $donations[0]['total'];

            # ...and this month
            $mysqltime = date("Y-m-d H:i:s", strtotime("midnight first day of this month"));

            $sql = "SELECT SUM(GrossAmount) AS total FROM users_donations INNER JOIN memberships ON users_donations.userid = memberships.userid AND memberships.groupid = ? WHERE users_donations.timestamp > '$mysqltime' AND payer != 'ppgfukpay@paypalgivingfund.org';";
            $donations = $this->dbhr->preQuery($sql, [ $groupid ]);
            $ret['donationsthismonth'] = $donations[0]['total'];
        }

        # eBay stats
        $ret['eBay'] = $this->dbhr->preQuery("SELECT * FROM ebay_favourites ORDER BY timestamp ASC;");
        foreach ($ret['eBay'] as &$e) {
            $e['timestamp'] = ISODate($e['timestamp']);
        }

        # Aviva stats
        $top20 = $this->dbhr->preQuery("SELECT * FROM `aviva_votes` ORDER BY votes DESC LIMIT 20;");
        $history = $this->dbhr->preQuery("SELECT * FROM aviva_history ORDER BY timestamp ASC");
        $ours = $this->dbhr->preQuery("SELECT * FROM aviva_votes WHERE project = '17-1949';");

        $ret['aviva'] = [
            'ourposition' => count($history) ? $history[count($history) - 1]['position'] : 0,
            'ourvotes' => count($history) ? $history[count($history) - 1]['votes'] : 0,
            'history' => $history,
            'top20' => $top20
        ];

        # Pre-render.
        $pres = $this->dbhr->preQuery("SELECT MIN(retrieved) AS min FROM prerender;");
        $ret['prerender'] = ISODate($pres[0]['min']);

        return($ret);
    }
}