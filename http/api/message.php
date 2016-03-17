<?php
function message() {
    global $dbhr, $dbhm;

    $me = whoAmI($dbhr, $dbhm);

    $collection = presdef('collection', $_REQUEST, MessageCollection::APPROVED);
    $id = intval(presdef('id', $_REQUEST, NULL));
    $reason = presdef('reason', $_REQUEST, NULL);
    $groupid = intval(presdef('groupid', $_REQUEST, NULL));
    $action = presdef('action', $_REQUEST, NULL);
    $subject = presdef('subject', $_REQUEST, NULL);
    $body = presdef('body', $_REQUEST, NULL);
    $stdmsgid = presdef('stdmsgid', $_REQUEST, NULL);
    $messagehistory = array_key_exists('messagehistory', $_REQUEST) ? filter_var($_REQUEST['messagehistory'], FILTER_VALIDATE_BOOLEAN) : FALSE;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET':
        case 'PUT':
        case 'DELETE': {
            $m = NULL;
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
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'groups' => [],
                        'message' => $m->getPublic($messagehistory, FALSE)
                    ];

                    foreach ($ret['message']['groups'] as &$group) {
                        $g = new Group($dbhr, $dbhm, $group['groupid']);
                        $ret['groups'][$group['groupid']] = $g->getPublic();
                    }

                } else if ($_REQUEST['type'] == 'PUT') {
                    if ($collection == MessageCollection::DRAFT) {
                        # Draft messages are created by users, rather than parsed out from emails.  We might be
                        # creating one, or updating one.
                        if (!$id) {
                            $id = $m->createDraft();
                            $m = new Message($dbhr, $dbhm, $id);
                        }

                        if ($m->getID()) {
                            # Drafts have:
                            # - a locationid
                            # - a type
                            # - an item (which we store in the subject)
                            # - a fromuser if known (we might not have logged in yet)
                            # - a textbody
                            # - one or more attachments
                            $locationid = intval(presdef('locationid', $_REQUEST, NULL));
                            $type = presdef('messagetype', $_REQUEST, NULL);
                            $item = presdef('item', $_REQUEST, NULL);
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
                    } else {
                        $role = $m->getRoleForMessage();
                        if ($role != User::ROLE_OWNER && $role != User::ROLE_MODERATOR) {
                            $ret = ['ret' => 2, 'status' => 'Permission denied'];
                        } else {
                            $subject = presdef('subject', $_REQUEST, NULL);
                            $textbody = presdef('textbody', $_REQUEST, NULL);
                            $htmlbody = presdef('htmlbody', $_REQUEST, NULL);

                            $m->edit($subject, $textbody, $htmlbody);

                            $ret = [
                                'ret' => 0,
                                'status' => 'Success'
                            ];
                        }
                    }
                } else if ($_REQUEST['type'] == 'DELETE') {
                    $role = $m->getRoleForMessage();
                    if ($role != User::ROLE_OWNER && $role != User::ROLE_MODERATOR) {
                        $ret = ['ret' => 2, 'status' => 'Permission denied'];
                    } else {
                        $m->delete($reason);
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success'
                        ];
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
                        $m->spam($groupid);
                        break;
                    case 'JoinAndPost':
                        # This is the mainline case for someone posting a message.  We sign them up to the group if
                        # need be, and then post the message.  We do this without being logged in, because that reduces
                        # friction.  If there is abuse of this, then we will find other ways to block the abuse.
                        $ret = ['ret' => 3, 'status' => 'Failed to post'];

                        $email = presdef('email', $_REQUEST, NULL);
                        $u = new User($dbhr, $dbhm);
                        $uid = $u->findByEmail($email);

                        if (!$uid) {
                            # We don't yet know this user.  Create them.
                            $name = substr($email, 0, strpos($email, '@'));
                            $u->create(NULL, NULL, $name, 'Created to allow post');
                            $eid = $u->addEmail($email, 1);
                        } else {
                            $u = new User($dbhr, $dbhm, $uid);
                            $eid = $u->getIdForEmail($email);
                        }

                        $ret = ['ret' => 4, 'status' => 'Failed to create user or email'];

                        if ($u->getId() && $eid) {
                            # Now we have a user and an email.  We need to make sure they're a member of the
                            # group in question.
                            $eidforgroup = $u->getEmailForGroup($groupid);
                            $ret = ['ret' => 5, 'status' => 'Failed to join group'];
                            $rc = true;

                            if (!$eidforgroup) {
                                # Not a member yet.
                                $rc = $u->addMembership($groupid, User::ROLE_MEMBER, $eid, MembershipCollection::APPROVED);
                            }
                        }

                        break;
                }
            }
        }
    }

    return($ret);
}
