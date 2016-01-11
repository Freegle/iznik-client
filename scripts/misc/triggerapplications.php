<?php

for ($i = 0; $i < 100; $i++) {
    $tag = time() . "-$i";
    $headers = "From: application-$tag@iznik.modtools.org>\r\n";
    mail("freegleplayground-subscribe@yahoogroups.com", "Please let me join", "Pretty please", $headers, "-fapplication-$tag@iznik.modtools.org");
}