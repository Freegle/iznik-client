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
            "src": "/images/favicon/favicon-96x96.png",
            "sizes": "96x96",
            "type": "image/png"
        },
        {
            "src": "/images/favicon/favicon-144x144.png",
            "sizes": "144x144",
            "type": "image/png"
        },
        {
            "src": "/images/favicon/favicon-180x180.png",
            "sizes": "180x180",
            "type": "image/png"
        }
    ],
    "start_url": "/modtools",
    "display": "standalone",
    "orientation": "portrait"
}
