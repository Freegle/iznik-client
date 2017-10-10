<?php

$csv = "https://docs.google.com/a/ehibbert.org.uk/spreadsheets/d/17DbcfvDjHwDFj3_F4iBprKHDaN_fne-bPI94sllVrfY/export?format=csv&id=17DbcfvDjHwDFj3_F4iBprKHDaN_fne-bPI94sllVrfY&gid=1066003612";

$data = file_get_contents($csv);

define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');

global $dbhr, $dbhm;
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Sharing for London</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/glyphicons.css">
    <link rel="stylesheet" href="/css/glyphicons-social.css">
    <link rel="stylesheet" href="/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="/css/bootstrap-switch.min.css">
    <link rel="stylesheet" href="/css/bootstrap-dropmenu.min.css">
    <link rel="stylesheet" href="/css/bootstrap-notifications.min.css">
    <link rel="stylesheet" href="/css/datepicker3.css">
    <link rel="stylesheet" href="/js/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="/css/dd.css">
    <link rel="stylesheet" href="/css/fileinput.css" />

    <link rel="stylesheet" type="text/css" href="/css/style.css?a=199">
    <!--[if lt IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/ie-only.css">
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="/css/user.css?a=154">
    <!--[if lt IE 9]>
    <script src="/js/lib/html5shiv.js"></script>
    <script src="/js/lib/respond.min.js"></script>
    <![endif]-->

    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="HandheldFriendly" content="true">

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="/js/lib/jquery.js"></script>
    <script type="text/javascript" src="js/lib/require.js"></script>
    <script type="text/javascript" src="js/requirejs-setup.js"></script>
    <script type="text/javascript">
        console.log("Script");
        $(document).ready(function() {
            console.log("Ready");
            require(['gmaps', 'richMarker'], function() {
                var mapWidth = $('#map').width();
                console.log("Map width", mapWidth);

                var x = 0;
                var y = 0;

                $('#map').height(window.innerHeight - 100);

                var mapOptions = {
                    mapTypeControl      : false,
                    streetViewControl   : false,
                    center              : new google.maps.LatLng(51.5074, 0.1278),
                    panControl          : mapWidth > 400,
                    zoomControl         : mapWidth > 400,
                    zoom                : 11
                };

                var map = new google.maps.Map($('#map').get()[0], mapOptions);

                var geocoder = new google.maps.Geocoder();

                <?php

                $rows = str_getcsv($data, "\n");

                foreach ($rows as $row) {
                $fields = str_getcsv($row);
                $name = $fields[1];

                if ($name != 'Trimmed Name') {
                $location = $fields[3];

                if ($name && $location) {
                error_log("$name at $location");
                $locs = $dbhr->preQuery("SELECT lat,lng FROM locations WHERE name LIKE ? AND type = 'Polygon' LIMIT 1;", [
                    $location
                ]);

                foreach ($locs as $loc) {
                    ?>
//                    var richMarker = new RichMarker({
//                        map: map,
//                        position: new google.maps.LatLng(<?php //echo $loc['lat']; ?>//, <?php //echo $loc['lng']; ?>//)
//                    });
//
//                    richMarker.setContent('<div style="background: white; font-size: 14pt;"><p><?php //echo $name; ?>//<p></div>');

                    function genMarker(name, lat, lng, loc) {
                        var info = new google.maps.InfoWindow({
                            content: '<div style="background: white; font-size: 14pt;"><p>OFFER: ' + name + ' (' + loc + ')<p></div>'
                        });

                        var marker = new google.maps.Marker({
                            map: map,
                            position: new google.maps.LatLng(lat + x, lng + y),
                            icon: '/images/truck.png',
                            draggable: true
                        });

                        x += 0.005;
                        y += 0.005;

                        info.open(map, marker);
                    }

                    genMarker("<?php echo $name; ?>", <?php echo $loc['lat']; ?>, <?php echo $loc['lng']; ?>, "<?php echo $location; ?>");
                    <?php
                }
                }
                }
                }
                ?>
            });
        });
    </script>
<!--    <script src="/js/lib/bootstrap.min.js" />-->
</head>
<body style="margin-top: 0px">
    <noscript>
        <h1>Please enable Javascript</h1>
    </noscript>
    <div id="bodyEnvelope">
        <div id="bodyContent" class="nopad">
            <h1>Sharing for London</h1>
            <div class="row">
                <div class="col-xs-12">
                    <div id="map" />
                </div>
            </div>
        </div>
    </div>
</body>
