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
"gcm_sender_id": "<?php echo GOOGLE_PROJECT; ?>"
}
