<?php

mail("geeks@ilovefreegle.org", "Iznik: Problem with incoming.php, will retry ID " . $argv[2], "This means incoming mails are not reaching the DB, but should be queued in exim.\n\n" . var_export($argv, true) . "\n\n" . var_export($_ENV, true), NULL, "-fsupport@modtools.org");

?>

