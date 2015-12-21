<?php

require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/group/Group.php');
require_once(IZNIK_BASE . '/include/misc/Location.php');
require_once(IZNIK_BASE . '/include/message/Collection.php');

$groupname = presdef('groupname', $_REQUEST, NULL);

if (!$groupname) {
    echo "<p>Need to put groupname parameter in.</p>";
} else {
    $g = new Group($dbhr, $dbhm);
    $gid = $g->findByShortName($groupname);

    if (!$gid) {
        echo "<p>Couldn't find group $groupname.</p>";
    } else {
        $g = new Group($dbhr, $dbhm, $gid);

        $c = new Collection($dbhr, $dbhm, Collection::APPROVED);
        list($groups, $msgs) = $c->get(0, 100, [ $gid ]);

        ?>
        <table>
            <tbody>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Mapped Location</th>
                    <th>Map</th>
                    <th>Lat</th>
                    <th>Lng</th>
                    <th>Location ID</th>
                    <th>Area</th>
                    <th>Postcode</th>
                </tr>
            <?php
            foreach ($msgs as $msg) {
                $loc = pres('locationid', $msg) ? $msg['location']['name'] : '-';
                $locid = pres('locationid', $msg) ? $msg['locationid']  : '-';
                $area = '-';
                $postcode = '-';
                $lat = pres('locationid', $msg) ? round($msg['lat'], 2) : '-';
                $lng = pres('locationid', $msg) ? round($msg['lng'], 2) : '-';
                $img = pres('locationid', $msg) ? "<img src=\"https://maps.google.com/maps/api/staticmap?zoom=12&size=110x110&center=$lat,$lng&maptype=roadmap&sensor=false" : '';

                echo "<tr><td>{$msg['id']}</td><td>" . htmlentities($msg['subject']) . "</td><td>$loc</td><td>$img</td><td>$lat</td><td>$lng</td><td>$locid</td><td>$area</td><td>$postcode</td></tr>";
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}