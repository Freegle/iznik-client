<?php
#
# Sync memberships.  The client calls the memberships API call, which queues them up in memberships_yahoo_dump,
# and we processed them here into the main tables.  This offloads the expensive processing from the app
# servers, and also serialises it so that it doesn't swamp the system.
#
require_once dirname(__FILE__) . '/../../include/config.php';
require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/db.php');
require_once(IZNIK_BASE . '/include/group/Group.php');

$lockh = lockScript(basename(__FILE__));

$g = Group::get($dbhr, $dbhm);
$g->processSetMembers();

unlockScript($lockh);