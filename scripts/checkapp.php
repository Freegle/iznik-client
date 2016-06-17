<?php

require_once('../include/config.php');
exec('curl -sL -w "%{http_code}\\n" "https://' . MONIT_HOST . '" -o /dev/null -m 20  --connect-timeout 20', $op, $rc);
exit($rc);