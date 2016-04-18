<?php
define('IZNIK_BASE', dirname(__FILE__));
require_once('/etc/iznik.conf');

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header('Content-type: application/json');
?>
{
    "name": "<?php echo SITE_NAME; ?>",
    "short_name": "<?php echo SITE_NAME; ?>",
    "gcm_sender_id": "<?php echo GOOGLE_PROJECT; ?>",
    "icons": [
        {
            "src": "/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-96x96.png",
            "sizes": "96x96",
            "type": "image/png"
        },
        {
            "src": "/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-144x144.png",
            "sizes": "144x144",
            "type": "image/png"
        },
        {
            "src": "/images/favicon/<?php echo FAVICON_HOME; ?>/favicon-180x180.png",
            "sizes": "180x180",
            "type": "image/png"
        }
    ],
    "background_color": "#d6e9c6",
    "start_url": "/<?php echo MANIFEST_STARTURL; ?>",
    "display": "standalone",
    "orientation": "portrait"
}
