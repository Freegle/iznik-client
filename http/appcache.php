<?php
define( 'BASE_DIR', dirname(__FILE__) . '/..' );
require_once(BASE_DIR . '/include/config.php');
require_once(IZNIK_BASE . '/include/misc/scripts.php');

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header("Content-type: text/cache-manifest");
?>
CACHE MANIFEST

# <?php
# We use our script code to get a version number; we get a cache file name which depends
# on the timestamps of the JS code.
#
# v0.4

list ($cachefile, $scripts) = scriptInclude((function($str) { return($str); }));
echo $cachefile;
?>

CACHE:

#images
/images/Chrome.png
/images/Firefox.png
/images/ajax-loader.gif
/images/pageloader.gif
/images/favicon/<?php echo FAVICON_HOME; ?>/smalltile.png
/images/favicon/<?php echo FAVICON_HOME; ?>/android-chrome-192x192.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-57x57.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-60x60.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-72x72.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-76x76.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-114x114.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-120x120.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-144x144.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-152x152.png
/images/favicon/<?php echo FAVICON_HOME; ?>/apple-touch-icon-180x180.png
/images/favicon/<?php echo FAVICON_HOME; ?>/favicon.ico
/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-16x16.png
/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-32x32.png
/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-96x96.png
/images/favicon/<?php echo FAVICON_HOME; ?>/largetile.png
/images/favicon/<?php echo FAVICON_HOME; ?>/mediumtile.png
/images/favicon/<?php echo FAVICON_HOME; ?>/smalltile.png
/images/favicon/<?php echo FAVICON_HOME; ?>/widetile.png
/images/yahoo.png
/manifest.json
https://www.paypalobjects.com/en_GB/i/scr/pixel.gif

#internal HTML documents
/<?php echo FAVICON_HOME; ?>
/<?php echo FAVICON_HOME; ?>/members/pending
/<?php echo FAVICON_HOME; ?>/members/spam
/<?php echo FAVICON_HOME; ?>/messages/pending
/<?php echo FAVICON_HOME; ?>/messages/spam
/user

#style sheets
/css/bootstrap-select.min.css
/css/bootstrap-switch.min.css
/css/datepicker3.css
/css/dd.css
/css/<?php echo FAVICON_HOME; ?>.css
/css/style.css
/js/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css
https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css
https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css
https://www.google.com/uds/api/visualization/1.0/707b4cf11c6a02b0e004d31b539ac729/ui+en,table+en,controls+en,annotationchart+en.css
https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/fonts/glyphicons-halflings-regular.ttf
https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/fonts/glyphicons-halflings-regular.woff

#style sheet images
/images/msdropdown/skin1/dd_arrow.gif
/images/msdropdown/skin1/title-bg.gif
https://fbstatic-a.akamaihd.net/rsrc.php/v2/y9/r/jKEcVPZFk-2.gif
https://fbstatic-a.akamaihd.net/rsrc.php/v2/yD/r/t-wz8gw1xG1.png
https://fbstatic-a.akamaihd.net/rsrc.php/v2/ya/r/3rhSv5V8j3o.gif
https://fbstatic-a.akamaihd.net/rsrc.php/v2/yd/r/Cou7n-nqK52.gif
https://fbstatic-a.akamaihd.net/rsrc.php/v2/ye/r/8YeTNIlTZjm.png
https://fbstatic-a.akamaihd.net/rsrc.php/v2/yq/r/IE9JII6Z1Ys.png

#javascript files
https://connect.facebook.net/en_US/sdk.js
https://apis.google.com/_/scs/apps-static/_/js/k=oz.gapi.en_GB.ocSOssjDg14.O/m=plusone/exm=client/rt=j/sv=1/d=1/ed=1/am=AQ/rs=AGLTcCNoJSipLXsH5J9A9YAfo9pJ-lbwEQ/cb=gapi.loaded_1
https://apis.google.com/_/scs/apps-static/_/js/k=oz.gapi.en_GB.ocSOssjDg14.O/m=client/rt=j/sv=1/d=1/ed=1/am=AQ/rs=AGLTcCNoJSipLXsH5J9A9YAfo9pJ-lbwEQ/cb=gapi.loaded_0
https://plus.google.com/js/client:plusone.js
https://ssl.google-analytics.com/ga.js
/js/lib/binaryajax.js
https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyCdTSJKGWJUOx2pq1Y0f5in5g4kKAO5dgg&libraries=geometry,places,drawing,visualization
https://ajax.googleapis.com/ajax/static/modules/gviz/1.0/core/tooltip.css
https://www.google.com/jsapi?autoload={%27modules%27:[{%27name%27:%27visualization%27,%27version%27:%271%27,%27packages%27:[%27corechart%27,%20%27annotationchart%27]}]}
https://www.google.com/uds/api/visualization/1.0/707b4cf11c6a02b0e004d31b539ac729/format+en,default+en,ui+en,table+en,controls+en,corechart+en,annotationchart+en.I.js
https://cdn.tinymce.com/4/tinymce.min.js
https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js
https://apis.google.com/js/client:platform.js
https://maps.googleapis.com/maps-api-v3/api/js/23/7/common.js
https://maps.googleapis.com/maps-api-v3/api/js/23/7/util.js
https://maps.googleapis.com/maps-api-v3/api/js/23/7/stats.js
js/lib/jquery-1.11.3.js
js/lib/jquery-migrate-1.2.1.js
js/lib/jquery-ui.js
js/lib/underscore.js
js/lib/moment.js
js/lib/FileSaver.min.js
js/lib/ajaxq.js
js/lib/combodate.js
js/lib/jquery-dateFormat.min.js
js/lib/jquery.scrollTo.js
js/lib/exif.js
js/lib/jquery.ui.touch-punch.js
js/lib/backbone-1.1.2.js
js/lib/backbone.collectionView.js
js/lib/backform.js
js/lib/jquery.dotdotdot.min.js
js/lib/json2.js
js/lib/flowtype.js
js/lib/Sortable.js
js/lib/pushstream.js
js/lib/FormRepo.js
js/lib/canvasResize.js
js/lib/notify.js
js/lib/timeago.js
js/lib/jquery.validate.min.js
js/lib/jquery.validate.additional-methods.js
js/lib/jquery.geocomplete.min.js
js/lib/jquery.dd.min.js
js/lib/richMarker.js
js/lib/markerclusterer.min.js
js/lib/placeholders.min.js
js/lib/bootstrap-select.min.js
js/lib/bootstrap-switch.min.js
js/lib/bootstrap-datepicker.js
js/lib/sly.min.js
js/lib/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js
js/lib/bootstrap-datepicker.en-GB.js
js/lib/autosize.js
js/lib/jquery.waypoints.js
js/lib/jquery-show-first.js
js/lib/jquery-visibility.js
js/lib/typeahead.jquery.js
js/iznik/dateshim.js
js/iznik/zombies.js
js/iznik/underscore.js
js/iznik/utility.js
js/iznik/main.js
js/iznik/facebook.js
js/iznik/google.js
js/iznik/accordionpersist.js
js/iznik/selectpersist.js
js/iznik/majax.js
js/iznik/infinite.js
js/models/session.js
js/models/message.js
js/models/group.js
js/models/config/modconfig.js
js/models/config/stdmsg.js
js/models/config/bulkop.js
js/models/spammer.js
js/models/user/user.js
js/models/yahoo/user.js
js/models/membership.js
js/views/plugin.js
js/views/modal.js
js/views/signinup.js
js/views/pages/pages.js
js/views/help.js
js/views/dashboard.js
js/views/user/user.js
js/views/yahoo/user.js
js/views/pages/user/landing.js
js/views/pages/user/find.js
js/views/pages/<?php echo FAVICON_HOME; ?>/landing.js
js/views/pages/<?php echo FAVICON_HOME; ?>/messages.js
js/views/pages/<?php echo FAVICON_HOME; ?>/members_pending.js
js/views/pages/<?php echo FAVICON_HOME; ?>/members_approved.js
js/views/pages/<?php echo FAVICON_HOME; ?>/members_spam.js
js/views/pages/<?php echo FAVICON_HOME; ?>/messages_spam.js
js/views/pages/<?php echo FAVICON_HOME; ?>/messages_pending.js
js/views/pages/<?php echo FAVICON_HOME; ?>/messages_approved.js
js/views/pages/<?php echo FAVICON_HOME; ?>/spammerlist.js
js/views/pages/<?php echo FAVICON_HOME; ?>/support.js
js/views/pages/<?php echo FAVICON_HOME; ?>/settings.js
js/views/group/select.js
js/iznik/router.js
js/lib/binaryajax.js

NETWORK:
*
