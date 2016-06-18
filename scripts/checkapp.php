<?php

require_once('/etc/iznik.conf');
$cmd = 'curl -sL -w "%{http_code}\\n" "https://' . MONIT_HOST . '" -o /dev/null -m 60  --connect-timeout 60';
error_log("Check $cmd");
exec($cmd, $op, $rc);
error_log("Returned $rc and " . var_export($op, TRUE));
exit($rc);