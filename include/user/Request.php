<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/user/User.php');
require_once(IZNIK_BASE . '/include/user/Address.php');
require_once(IZNIK_BASE . '/mailtemplates/requests/business_cards.php');
require_once(IZNIK_BASE . '/mailtemplates/requests/business_cards_mods.php');

class Request extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'userid', 'type', 'date', 'completed', 'addressid', 'to', 'completedby');
    var $settableatts = array('completed', 'addressid');

    const TYPE_BUSINESS_CARDS = 'BusinessCards';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'users_requests', 'request', $this->publicatts);
    }

    public function create($userid, $type, $addressid, $to) {
        $id = NULL;

        $rc = $this->dbhm->preExec("INSERT INTO users_requests (userid, type, addressid, `to`) VALUES (?,?,?,?);", [
            $userid,
            $type,
            $addressid,
            $to
        ]);

        if ($rc) {
            $id = $this->dbhm->lastInsertId();

            if ($id) {
                $this->fetch($this->dbhm, $this->dbhm, $id, 'users_requests', 'request', $this->publicatts);
            }
        }

        return($id);
    }

    public function getPublic($getaddress = TRUE)
    {
        $ret = parent::getPublic();

        if ($getaddress && pres('addressid', $ret)) {
            $a = new Address($this->dbhr, $this->dbhm, $ret['addressid']);

            # We can see the address when we're allowed to see a request.
            $ret['address'] = $a->getPublic(FALSE);
        }

        unset($ret['addressid']);

        $u = User::get($this->dbhr, $this->dbhm, $ret['userid']);
        $ret['user'] = $u->getPublic();
        $ret['user']['email'] = $u->getEmailPreferred();
        unset($ret['userid']);

        $ret['date'] = ISODate($ret['date']);

        return($ret);
    }

    public function listForUser($userid) {
        $ret = [];

        $requests = $this->dbhr->preQuery("SELECT id FROM users_requests WHERE userid = ?;", [
            $userid
        ]);

        foreach ($requests as $request) {
            $r = new Request($this->dbhr, $this->dbhm, $request['id']);
            $ret[] = $r->getPublic(FALSE);
        }

        return($ret);
    }

    public function listOutstanding() {
        $ret = [];

        $requests = $this->dbhr->preQuery("SELECT id FROM users_requests WHERE completed IS NULL;");

        foreach ($requests as $request) {
            $r = new Request($this->dbhr, $this->dbhm, $request['id']);
            $ret[] = $r->getPublic(TRUE);
        }

        return($ret);
    }

    public function listRecent() {
        $ret = [];

        $mysqltime = date("Y-m-d", strtotime("Midnight 30 days ago"));
        $requests = $this->dbhr->preQuery("SELECT completedby, COUNT(*) AS count FROM users_requests WHERE completed IS NOT NULL AND completedby IS NOT NULL AND completed > ? GROUP BY completedby", [
            $mysqltime
        ]);

        foreach ($requests as $request) {
            $u = User::get($this->dbhr, $this->dbhm, $request['completedby']);
            $ctx = NULL;
            $thisone = $u->getPublic(NULL, FALSE, NULL, $ctx, FALSE, FALSE, FALSE, FALSE, FALSE);
            $ret[] = [
                'user' => $thisone,
                'count' => $request['count']
            ];
        }

        return($ret);
    }

    public function sendIt($mailer, $message) {
        $mailer->send($message);
    }

    public function completed($userid) {
        $this->dbhm->preExec("UPDATE users_requests SET completed = NOW(), completedby = ? WHERE id = ?;", [
            $userid,
            $this->id
        ]);

        switch ($this->request['type']) {
            case Request::TYPE_BUSINESS_CARDS: {
                $u = User::get($this->dbhr, $this->dbhm, $this->request['userid']);

                try {
                    $html = business_cards($u->getName(), $u->getEmailPreferred());

                    $message = Swift_Message::newInstance()
                        ->setSubject("Your cards are on their way...")
                        ->setFrom([NOREPLY_ADDR => 'Freegle'])
                        ->setReturnPath($u->getBounce())
                        ->setTo([ $u->getEmailPreferred() => $u->getName() ])
                        ->setBody("Thanks for asking for some cards to promote Freegle.  They should now be on their way.  Please allow a week or so for them to arrive.")
                        ->addPart($html, 'text/html');

                    list ($transport, $mailer) = getMailer();
                    $this->sendIt($mailer, $message);

                    $this->notifyMods();
                } catch (Exception $e) {}

                break;
            }
        }
    }

    public function notifyMods() {
        try {
            error_log("Notify mods for {$this->request['userid']}");
            $u = User::get($this->dbhr, $this->dbhm, $this->request['userid']);
            $html = business_cards_mods($u->getName(), $u->getEmailPreferred());

            $membs = $u->getMemberships();

            foreach ($membs as $memb) {
                if ($u->getPrivate('systemrole') == User::SYSTEMROLE_USER) {
                    error_log("...group {$memb['id']}");
                    $g = Group::get($this->dbhr, $this->dbhm, $memb['id']);

                    $message = Swift_Message::newInstance()
                        ->setSubject("We've sent some Freegle business cards to someone on your group")
                        ->setFrom([SUPPORT_ADDR => 'Freegle'])
                        ->setTo([ $g->getModsEmail() => $g->getPrivate('nameshort') . ' Volunteers' ])
                        ->setBody("When your members mark an item as TAKEN/RECEIVED on Freegle Direct, we sometimes ask them if they'd like business cards so that they can promote Freegle.  We have a few national volunteers who send these out.  We've recently sent cards to " . $u->getName() . " (" . $u->getEmailPreferred() . ")")
                        ->addPart($html, 'text/html');

                    list ($transport, $mailer) = getMailer();
                    $this->sendIt($mailer, $message);

                    $this->dbhm->preExec("UPDATE users_requests SET notifiedmods = NOW() WHERE id = ?;", [
                        $this->id
                    ]);
                }
            }
        } catch (Exception $e) {}
    }

    public function delete() {
        $rc = $this->dbhm->preExec("DELETE FROM users_requests WHERE id = ?;", [ $this->id ]);
        return($rc);
    }
}