<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/message/MessageCollection.php');

$groupname = presdef('groupname', $_REQUEST, NULL);

if (!$groupname) {
    echo "<p>Need to put groupname parameter in.</p>";
} else {
    $g = Group::get($dbhr, $dbhm);
    $gid = $g->findByShortName($groupname);

    if (!$gid) {
        echo "<p>Couldn't find group $groupname.</p>";
    } else {
        $g = Group::get($dbhr, $dbhm, $gid);

        $c = new MessageCollection($dbhr, $dbhm, MessageCollection::APPROVED);
        $ctx = NULL;
        list($groups, $msgs) = $c->get($ctx, 100, [ $gid ]);

        ?>
        <table>
            <tbody>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Map</th>
                    <th>Lat</th>
                    <th>Lng</th>
                    <th>Location ID</th>
                    <th>Mapped Location</th>
                    <th>Postcode</th>
                </tr>
            <?php
            foreach ($msgs as $msg) {
                $loc = pres('locationid', $msg) ? $msg['location']['name'] : '-';
                $locid = pres('locationid', $msg) ? $msg['locationid']  : '-';
                $l = new Location($dbhr, $dbhm, $locid);
                $atts = $l->getPublic();
                $postcode = $atts['postcodeid'];
                $area = $atts['areaid'];

                if ($postcode) {
                    $l = new Location($dbhr, $dbhm, $postcode);
                    $postcode = $l->getPrivate('name');
                }

                if ($area) {
                    $l = new Location($dbhr, $dbhm, $area);
                    $area = $l->getPrivate('name');
                }

                $lat = pres('locationid', $msg) ? round($msg['lat'], 2) : '-';
                $lng = pres('locationid', $msg) ? round($msg['lng'], 2) : '-';
                $img = pres('locationid', $msg) ? "<img src=\"https://maps.google.com/maps/api/staticmap?zoom=12&size=110x110&center=$lat,$lng&maptype=roadmap&sensor=false" : '';

                echo "<tr><td>{$msg['id']}</td><td>" . htmlentities($msg['subject']) . "</td><td>$img</td><td>$lat</td><td>$lng</td><td>$locid</td><td>$loc</td><td>$postcode</td></tr>";
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}