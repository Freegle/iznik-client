<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');

class Supporters
{
    # These match the table names
    const APPROVED = 'messages_approved';
    const PENDING = 'messages_pending';
    const SPAM = 'messages_spam';

    /** @var  $dbhr LoggedPDO */
    public $dbhr;
    /** @var  $dbhm LoggedPDO */
    public $dbhm;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->dbhm->preExec("DELETE FROM supporters WHERE name = 'Obediah Clutterbuck';");
    }

    function get() {
        $this->dbhm->preExec("INSERT INTO supporters (type, name) VALUES ('Supporter', 'Obediah Clutterbuck');");
        $sql = "SELECT * FROM supporters WHERE anonymous = 0 AND type != 'Buyer';";
        $ret = [
            'Wowzer' => [],
            'Front Page' => [],
            'Supporter' => []
        ];

        $supps = $this->dbhr->preQuery($sql, []);

        foreach ($supps as $supp) {
            if ($supp['display']) {
                $name = $supp['display'];
            } else if ($supp['name']) {
                $name = $supp['name'];
            } else {
                $name = substr($supp['email'], 0, strpos($supp['email'], '@'));
            }

            $ret[$supp['type']][] = [
                'name' => $name
            ];
        }

        # Shuffle so that people get a fair shout.
        shuffle($ret['Wowzer']);
        shuffle($ret['Front Page']);
        shuffle($ret['Supporter']);

        return ($ret);
    }
}