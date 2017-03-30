<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);
    $myid = $me ? $me->getId() : NULL;

    $collection = presdef('collection', $_REQUEST, MessageCollection::APPROVED);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $id = presdef('id', $_REQUEST, NULL);

    if ($id && substr($id, 0, 1) == 'L') {
        # This is a legacy ID used for migrating old links from earlier versions.  Map it to the real id.
        #
        # The groupid is probably legacy too, so we need to get the real one.
        $g = Group::get($dbhr, $dbhm, $groupid);
        $sql = "SELECT msgid FROM messages_groups WHERE groupid = ? AND yahooapprovedid = ?;";
        $msgs = $dbhr->preQuery($sql, [
            $g->getId(),
            intval(substr($id, 1))
        ]);

        foreach ($msgs as $msg) {
            $id = $msg['msgid'];
        }
    }

    $reason = presdef('reason', $_REQUEST, NULL);
    $action = presdef('action', $_REQUEST, NULL);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);
    $stdmsgid = presdef('stdmsgid', $_REQUEST, NULL);
    $messagehistory = array_key_exists('messagehistory', $_REQUEST) ? filter_var($_REQUEST['messagehistory'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $localonly = array_key_exists('localonly', $_REQUEST) ? filter_var($_REQUEST['localonly'], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $userid = intval(presdef('userid', $_REQUEST, NULL));
    $userid = $userid ? $userid : NULL;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET':
        case 'PUT':
        case 'DELETE': {
            $m = new Message($dbhr, $dbhm, $id);

            if ((!$m->getID() && $collection != MessageCollection::DRAFT) || $m->getDeleted()) {
                $ret = ['ret' => 3, 'status' => 'Message does not exist'];
                $m = NULL;
            } else {
                switch ($collection) {
                    case MessageCollection::APPROVED:
                    case MessageCollection::DRAFT:
                        # No special checks for approved or draft - we could even be logged out.
                        break;
                    case MessageCollection::PENDING:
                    case MessageCollection::REJECTED:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            $groups = $m->getGroups();
                            if (count($groups) == 0 || !$groupid || !$me->isModOrOwner($m->getGroups()[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                    case MessageCollection::SPAM:
                        if (!$me) {
                            $ret = ['ret' => 1, 'status' => 'Not logged in'];
                            $m = NULL;
                        } else {
                            $groups = $m->getGroups();
                            if (count($groups) == 0 || !$groupid || !$me->isModOrOwner($groups[0])) {
                                $ret = ['ret' => 2, 'status' => 'Permission denied'];
                                $m = NULL;
                            }
                        }
                        break;
                    default:
                        # If they don't say what they're doing properly, they can't do it.
                        $m = NULL;
                        $ret = [ 'ret' => 101, 'status' => 'Bad collection' ];
                        break;
                }
            }

            if ($m) {
                if ($_REQUEST['type'] == 'GET') {
                    $atts = $m->getPublic($messagehistory, FALSE);
                    $cansee = $m->canSee($atts);

                    $ret = [
                        'ret' => 2,
                        'status' => 'Permission denied'
                    ];

                    if ($cansee) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'groups' => [],
                            'message' => $atts
                        ];

                        foreach ($ret['message']['groups'] as &$group) {
                            # The groups info returned in the message is not enough - doesn't include settings, for
                            # example.
                            $g = Group::get($dbhr, $dbhm, $group['groupid']);
                            $ret['groups'][$group['groupid']] = $g->getPublic();
                        }
                    }
                } else if ($_REQUEST['type'] == 'PUT') {
                    if ($collection == MessageCollection::DRAFT) {
                        # Draft messages are created by users, rather than parsed out from emails.  We might be
                        # creating one, or updating one.
                        $locationid = intval(presdef('locationid', $_REQUEST, NULL));

                        $ret = [ 'ret' => 3, 'Missing location - client error' ];

                        if ($locationid) {
                            if (!$id) {
                                $id = $m->createDraft();
                                $m = new Message($dbhr, $dbhm, $id);

                                # Record the last message we created in our session.  We use this to give access to
                                # this message even if we're not logged in - for example when setting the FOP after
                                # message submission.
                                $_SESSION['lastmessage'] = $id;
                            } else {
                                # The message should be ours.
                                $sql = "SELECT * FROM messages_drafts WHERE msgid = ? AND session = ? OR (userid IS NOT NULL AND userid = ?);";
                                $drafts = $dbhr->preQuery($sql, [ $id, session_id(), $myid ]);
                                $m = NULL;
                                foreach ($drafts as $draft) {
                                    $m = new Message($dbhr, $dbhm, $draft['msgid']);
                                }
                            }

                            if ($m) {
                                # Drafts have:
                                # - a locationid
                                # - a groupid (optional)
                                # - a type
                                # - an item
                                # - a subject constructed from the type, item and location.
                                # - a fromuser if known (we might not have logged in yet)
                                # - a textbody
                                # - zero or more attachments
                                if ($groupid) {
                                    $dbhm->preExec("UPDATE messages_drafts SET groupid = ? WHERE msgid = ?;", [$groupid, $m->getID()]);
                                }

                                $type = presdef('messagetype', $_REQUEST, NULL);

                                # Associated the item with the message.
                                $item = presdef('item', $_REQUEST, NULL);
                                $i = new Item($dbhr, $dbhm);
                                $itemid = $i->create($item);
                                $m->addItem($itemid);

                                $fromuser = $me ? $me->getId() : NULL;
                                $textbody = presdef('textbody', $_REQUEST, NULL);
                                $attachments = presdef('attachments', $_REQUEST, []);
                                $m->setPrivate('locationid', $locationid);
                                $m->setPrivate('type', $type);
                                $m->setPrivate('subject', $item);
                                $m->setPrivate('fromuser', $fromuser);
                                $m->setPrivate('textbody', $textbody);
                                $m->setPrivate('fromip', presdef('REMOTE_ADDR', $_SERVER, NULL));
                                $m->replaceAttachments($attachments);

                                $ret = [
                                    'ret' => 0,
                                    'status' => 'Success',
                                    'id' => $id
                                ];
                            }
                        }
                    }
                } else if ($_REQUEST['type'] == 'DELETE') {
                    $role = $m->getRoleForMessage();
                    if ($role != User::ROLE_OWNER && $role != User::ROLE_MODERATOR) {
                        $ret = ['ret' => 2, 'status' => 'Permission denied'];
                    } else {
                        $m->delete($reason, NULL, NULL, NULL, NULL, $localonly);
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
                    }
                }
            }
        }
        break;

        case 'PATCH': {
            $m = new Message($dbhr, $dbhm, $id);
            $ret = ['ret' => 3, 'status' => 'Message does not exist'];

            if ($m->getID()) {
                $role = $m->getRoleForMessage();

                # We might not be logged in - but might still have permissions to modify this message if
                # we created it.
                if ($role != User::ROLE_OWNER && $role != User::ROLE_MODERATOR && $id != presdef('lastmessage', $_SESSION, NULL)) {
                    $ret = ['ret' => 2, 'status' => 'Permission denied'];
                } else {
                    $subject = presdef('subject', $_REQUEST, NULL);
                    $msgtype = presdef('msgtype', $_REQUEST, NULL);
                    $item = presdef('item', $_REQUEST, NULL);
                    $location = presdef('location', $_REQUEST, NULL);
                    $textbody = presdef('textbody', $_REQUEST, NULL);
                    $htmlbody = presdef('htmlbody', $_REQUEST, NULL);
                    $fop = array_key_exists('FOP', $_REQUEST) ? $_REQUEST['FOP'] : NULL;
                    $attachments = presdef('attachments', $_REQUEST, []);

                    $ret = [
                        'ret' => 0,
                        'status' => 'Success'
                    ];

                    if ($subject || $textbody || $htmlbody || $msgtype || $item || $location) {
                        $rc = $m->edit($subject, $textbody, $htmlbody, $msgtype, $item, $location);
                        $ret = $rc ? $ret : [ 'ret' => 2, 'status' => 'Edit failed' ];
                    }

                    if ($fop !== NULL) {
                        $m->setFOP($fop);
                    }

                    if ($attachments) {
                        $m->replaceAttachments($attachments);
                    }

                }
            }
        }
        break;

        case 'POST': {
            $m = new Message($dbhr, $dbhm, $id);
            $ret = ['ret' => 2, 'status' => 'Permission denied'];
            $role = $m ? $m->getRoleForMessage() : User::ROLE_NONMEMBER;

            if ($role == User::ROLE_MODERATOR || $role == User::ROLE_OWNER) {
                $ret = [ 'ret' => 0, 'status' => 'Success' ];

                switch ($action) {
                    case 'Delete':
                        # The delete call will handle any rejection on Yahoo if required.
                        $m->delete($reason, NULL, $subject, $body, $stdmsgid);
                        break;
                    case 'Reject':
                        if (!$m->isPending($groupid)) {
                            $ret = ['ret' => 3, 'status' => 'Message is not pending'];
                        } else {
                            $m->reject($groupid, $subject, $body, $stdmsgid);
                        }
                        break;
                    case 'Approve':
                        if (!$m->isPending($groupid)) {
                            $ret = ['ret' => 3, 'status' => 'Message is not pending'];
                        } else {
                            $m->approve($groupid, $subject, $body, $stdmsgid);
                        }
                        break;
                    case 'Reply':
                        $m->reply($groupid, $subject, $body, $stdmsgid);
                        break;
                    case 'Hold':
                        $m->hold();
                        break;
                    case 'Release':
                        $m->release();
                        break;
                    case 'NotSpam':
                        $m->notSpam();
                        $r = new MailRouter($dbhr, $dbhm);
                        $r->route($m, TRUE);
                        break;
                    case 'Spam':
                        # Don't trust normal mods to categorise this correctly.  Often they will mark a message from
                        # a scammer trying to extort as spam, though the message itself is ok.  This tends to poison
                        # our filter.
                        if ($me->isAdminOrSupport()) {
                            $m->spam($groupid);
                        } else {
                            $m->delete("Categorised as spam by moderator");
                        }
                        break;
                    case 'JoinAndPost':
                        # This is the mainline case for someone posting a message.  We find the nearest group, sign
                        # them up if need be, and then post the message.  We do this without being logged in, because
                        # that reduces friction.  If there is abuse of this, then we will find other ways to block the
                        # abuse.
                        #
                        # The message we have in hand should be nobody else's
                        $ret = ['ret' => 3, 'status' => 'Not our message'];
                        $sql = "SELECT * FROM messages_drafts WHERE msgid = ? AND (session = ? OR (userid IS NOT NULL AND userid = ?));";
                        $drafts = $dbhr->preQuery($sql, [$id, session_id(), $myid]);
                        $newuser = NULL;
                        $pw = NULL;
                        #error_log("$sql, $id, " . session_id() . ", $myid");

                        foreach ($drafts as $draft) {
                            $m = new Message($dbhr, $dbhm, $draft['msgid']);

                            if (!$draft['groupid']) {
                                # No group specified.  Find the group nearest the location.
                                $l = new Location($dbhr, $dbhm, $m->getPrivate('locationid'));
                                $ret = ['ret' => 4, 'status' => 'No nearby groups found'];
                                $nears = $l->groupsNear(200);
                            } else {
                                # A preferred group for this message.
                                $nears = [ $draft['groupid'] ];
                            }

                            // @codeCoverageIgnoreStart
                            if (defined('USER_GROUP_OVERRIDE') && !pres('ignoregroupoverride', $_REQUEST)) {
                                # We're in testing mode
                                $g = new Group($dbhr, $dbhm);
                                $nears = [ $g->findByShortName(USER_GROUP_OVERRIDE) ];
                            }
                            // @codeCoverageIgnoreEnd

                            if (count($nears) > 0) {
                                $groupid = $nears[0];

                                # Now we know which group we'd like to post on.  Make sure we have a user set up.
                                $email = presdef('email', $_REQUEST, NULL);
                                $u = User::get($dbhr, $dbhm);
                                $uid = $u->findByEmail($email);

                                if (!$uid) {
                                    # We don't yet know this user.  Create them.
                                    $name = substr($email, 0, strpos($email, '@'));
                                    $newuser = $u->create(NULL, NULL, $name, 'Created to allow post');

                                    # Create a password and mail it to them.  Also log them in and return it.  This
                                    # avoids us having to ask the user for a password, though they can change it if
                                    # they like.  Less friction.
                                    $pw = $u->inventPassword();
                                    $u->addLogin(User::LOGIN_NATIVE, $newuser, $pw);
                                    $eid = $u->addEmail($email, 1);
                                    $u->login($pw);
                                    $u->welcome($email, $pw);
                                } else {
                                    $u = User::get($dbhr, $dbhm, $uid);
                                    $eid = $u->getIdForEmail($email)['id'];

                                    if ($u->getEmailPreferred() != $email) {
                                        # The email specified is the one they currently want to use - make sure it's
                                        $u->addEmail($email, 1, TRUE);
                                    }
                                }

                                $ret = ['ret' => 5, 'status' => 'Failed to create user or email'];

                                if ($u->getId() && $eid) {
                                    # Now we have a user and an email.  We need to make sure they're a member of the
                                    # group in question.
                                    $g = Group::get($dbhr, $dbhm, $groupid);
                                    $fromemail = NULL;

                                    if ($g->getPrivate('onyahoo')) {
                                        # We need to make sure we're a member of the Yahoo group with an email address
                                        # we host (so that replies come back here).
                                        list ($eidforgroup, $emailforgroup) = $u->getEmailForYahooGroup($groupid, TRUE, TRUE);

                                        $ret = ['ret' => 6, 'status' => 'Failed to join group'];
                                        if (!$eidforgroup || !$u->isApprovedMember($groupid)) {
                                            # Not a member yet.  We need to sign them up to the Yahoo group before we
                                            # can send it.  This may result in more applications to Yahoo - but dups are
                                            # ok.
                                            $ret = [
                                                'ret' => 0,
                                                'status' => 'Queued for group membership',
                                                'appliedemail' => $m->queueForMembership($u, $groupid),
                                                'groupid' => $groupid
                                            ];
                                        } else {
                                            # Now we have a user who is a member of the appropriate group.
                                            #
                                            # We're good to go.  Make sure we submit with the email that is a group member
                                            # rather than the one they supplied.
                                            $fromemail = $u->getEmailById($eidforgroup);
                                        }
                                    } else {
                                        # This group is hosted here.  There's less to do in that case.
                                        if (!$u->isApprovedMember($groupid)) {
                                            # Join the group.
                                            $u->addMembership($groupid);
                                        }

                                        # We want the message to come from one of our emails rather than theirs, so
                                        # that replies come back to us and privacy is maintained.
                                        $fromemail = $u->inventEmail();
                                    }

                                    $m->constructSubject($groupid);

                                    if ($fromemail) {
                                        # Make sure it's attached to this group.
                                        $postcoll = $u->postToCollection($groupid);
                                        $dbhm->preExec("INSERT IGNORE INTO messages_groups (msgid, groupid, collection,arrival, msgtype) VALUES (?,?,?,NOW(),?);", [
                                            $draft['msgid'],
                                            $groupid,
                                            $postcoll,
                                            $m->getType()
                                        ]);

                                        $ret = ['ret' => 7, 'status' => 'Failed to submit'];

                                        if ($m->submit($u, $fromemail, $groupid)) {
                                            # We sent it.
                                            $ret = ['ret' => 0, 'status' => 'Success', 'groupid' => $groupid ];

                                            if ($postcoll == MessageCollection::APPROVED) {
                                                # We index now; for pending messages we index when they are approved.
                                                $m->index();
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $ret['newuser'] = $newuser;
                        $ret['newpassword'] = $pw;

                        break;
                    case 'BackToDraft':
                    case 'RejectToDraft':
                        # This is a message which has been rejected or reposted, which we are now going to edit.
                        $ret = ['ret' => 3, 'status' => 'Message does not exist'];
                        $sql = "SELECT * FROM messages WHERE id = ?;";
                        $msgs = $dbhr->preQuery($sql, [ $id ]);

                        foreach ($msgs as $msg) {
                            $m = new Message($dbhr, $dbhm, $id);
                            $ret = ['ret' => 4, 'status' => 'Failed to edit message'];

                            $role = $m->getRoleForMessage();

                            if ($role == User::ROLE_MODERATOR || $role = User::ROLE_OWNER) {
                                $rc = $m->backToDraft();

                                if ($rc) {
                                    $ret = ['ret' => 0, 'status' => 'Success', 'messagetype' => $m->getType() ];
                                }
                            }
                        }
                        break;
                }
            }

            # Other actions which we can do on our own messages.
            if ($myid == $m->getFromuser()) {
                if ($userid) {
                    $r = new ChatRoom($dbhr, $dbhm);
                    $rid = $r->createConversation($myid, $userid);
                    $cm = new ChatMessage($dbhr, $dbhm);
                }

                switch ($action) {
                    case 'Promise':
                        $m->promise($userid);
                        $mid = $cm->create($rid, $myid, NULL, ChatMessage::TYPE_PROMISED, $id);
                        $ret = ['ret' => 0, 'status' => 'Success', 'id' => $mid];
                        break;
                    case 'Renege':
                        $m->renege($userid);
                        $mid = $cm->create($rid, $myid, NULL, ChatMessage::TYPE_RENEGED, $id);
                        $ret = ['ret' => 0, 'status' => 'Success', 'id' => $mid];
                        break;
                    case 'OutcomeIntended':
                        # Ignore duplicate attempts by user to supply an outcome.
                        if (!$m->hasOutcome()) {
                            $outcome = presdef('outcome', $_REQUEST, NULL);
                            $m->intendedOutcome($outcome);
                        }
                        break;
                    case 'Outcome':
                        # Ignore duplicate attempts by user to supply an outcome.
                        if (!$m->hasOutcome()) {
                            $outcome = presdef('outcome', $_REQUEST, NULL);
                            $h = presdef('happiness', $_REQUEST, NULL);
                            $happiness = NULL;

                            switch ($h) {
                                case User::HAPPY:
                                case User::FINE:
                                case User::UNHAPPY:
                                    $happiness = $h;
                                    break;
                            }

                            $comment = presdef('comment', $_REQUEST, NULL);

                            $ret = ['ret' => 1, 'status' => 'Odd action'];

                            switch ($outcome) {
                                case Message::OUTCOME_TAKEN: {
                                    if ($m->getType() == Message::TYPE_OFFER) {
                                        $m->mark($outcome, $comment, $happiness, $userid);
                                        $ret = ['ret' => 0, 'status' => 'Success'];
                                    };
                                    break;
                                }
                                case Message::OUTCOME_RECEIVED: {
                                    if ($m->getType() == Message::TYPE_WANTED) {
                                        $m->mark($outcome, $comment, $happiness, $userid);
                                        $ret = ['ret' => 0, 'status' => 'Success'];
                                    };
                                    break;
                                }
                                case Message::OUTCOME_WITHDRAWN: {
                                    $m->withdraw($comment, $happiness, $userid);
                                    $ret = ['ret' => 0, 'status' => 'Success'];
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }
    }

    return($ret);
}
