<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$dsn = "mysql:host={$dbconfig['host']};dbname=modtools;charset=utf8";

$dbhold = new PDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dsn = "mysql:host={$dbconfig['host']};dbname=ilovefreegle;charset=utf8";

$dbhf = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$dsn = "mysql:host={$dbconfig['host']};dbname=republisher;charset=utf8";

$dbhd = new LoggedPDO($dsn, $dbconfig['user'], $dbconfig['pass'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => FALSE
));

$g = new Group($dbhr, $dbhm);

# First get groups from ModTools
$oldgroups = $dbhold->query("SELECT * FROM groups WHERE groupname != '';");
foreach ($oldgroups as $group) {
    $type = Group::GROUP_OTHER;

    if (intval($group['freeglegroupid'])) {
        $type = Group::GROUP_FREEGLE;
    } else if (intval($group['reusegroup'])) {
        $type = Group::GROUP_REUSE;
    }

    $g->create(
        $group['groupname'],
        $type
    );

    $id = $g->findByShortName($group['groupname']);
    $g = new Group($dbhr, $dbhm, $id);

    $settings = [];

    $settings['keywords'] = [
        'offer' => $group['offerkeyword'],
        'taken' => $group['takenkeyword'],
        'wanted' => $group['wantedkeyword'],
        'received' => $group['receivedkeyword']
    ];
    $settings['spammers'] = [
        'check' => intval($group['checkspammers']),
        'remove' => intval($group['removespammers'])
    ];
    $settings['joiners'] = [
        'check' => intval($group['checkjoiners']),
        'threshold' => intval($group['joinerthreshold'])
    ];
    $settings['duplicates'] = [
        'check' => 1,
        'offer' => intval($group['offerdupperiod']),
        'taken' => intval($group['takendupperiod']),
        'wanted' => intval($group['wanteddupperiod']),
        'received' => intval($group['receiveddupperiod'])
    ];
    $settings['autoapprove'] = [
        'members' => intval($group['autoapprove'])
    ];

    # If it's a Freegle group pick up the lat/lng.
    if ($group['freeglegroupid']) {
        $sql = "SELECT * FROM perch_groups WHERE groupURL LIKE '%{$group['groupname']}';";
        $fgroups = $dbhf->preQuery($sql, []);
        foreach ($fgroups as $fgroup) {
            $g->setPrivate('lat', $fgroup['groupLatitude']);
            $g->setPrivate('lng', $fgroup['groupLongitude']);
            $g->setPrivate('type', 'Freegle');
        }
    }

    $g->setPrivate('settings', json_encode($settings));
}

# Now get FD groups not on ModTools
$sql = "SELECT * FROM groups WHERE grouppublish = 1;";
$fgroups = $dbhd->query($sql);

foreach ($fgroups as $fgroup) {
    $g->create(
        $fgroup['groupname'],
        Group::GROUP_FREEGLE
    );

    $id = $g->findByShortName($fgroup['groupname']);
    $g = new Group($dbhr, $dbhm, $id);

    if (intval($group['freeglegroupid'])) {
        $type = Group::GROUP_FREEGLE;
    } else if (intval($group['reusegroup'])) {
        $type = Group::GROUP_REUSE;
    }

    $settings = json_decode($g->getPublic()['settings'], true);

    foreach (['offerkeyword', 'takenkeyword', 'wantedkeyword', 'receivedkeyword'] as $attr) {
        $settings['keywords'][$attr] = $fgroup[$attr];
    }

    $settings['reposts'] = [
        'offer' => intval($fgroup['repostoffer']),
        'wanted' => intval($fgroup['repostwanted']),
        'max' => intval($fgroup['maxreposts'])
    ];

    $settings['crossposts'] = [
        'offer' => intval($fgroup['crosspostoffer']),
        'wanted' => intval($fgroup['crosspostwanted'])
    ];

    $settings['map'] = [
        'zoom' => intval($fgroup['defaultmapzoom']),
        'offer' => !intval($fgroup['dontmapoffer']),
        'wanted' => !intval($fgroup['dontmapwanted']),
        'hint' => $fgroup['mapsearchhint'],
        'distance' => intval($fgroup['mapdistance'])
    ];

    $settings['chaseups'] = [
        'interested' => [
            'enabled' => intval($fgroup['interestedin'])
        ],
        'messages' => [
            'enabled' => intval($fgroup['chaseupenabled'])
        ],
        'idle' => [
            'enabled' => intval($fgroup['chaseupidle']),
            'message' => $fgroup['chaseupidlefbmail']
        ]
    ];

    $settings['social'] = [
        'repostcentral' => intval($fgroup['allowrepost'])
    ];

    $settings['branding'] = [
        'logo' => $fgroup['grouplogo'],
        'description' => $fgroup['groupdescription']
    ];

    error_log("Set FD settings for {$fgroup['groupname']} id $id");
    $g->setPrivate('settings', json_encode($settings));
}

