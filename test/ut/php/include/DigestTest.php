<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikTestCase.php';
require_once IZNIK_BASE . '/include/user/User.php';
require_once IZNIK_BASE . '/include/group/Group.php';
require_once IZNIK_BASE . '/include/mail/Digest.php';
require_once IZNIK_BASE . '/include/mail/MailRouter.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class digestTest extends IznikTestCase {
    private $dbhr, $dbhm;

    private $msgsSent = [];

    protected function setUp()
    {
        parent::setUp();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->msgsSent = [];

        $this->tidy();
    }

    public function sendMock($mailer, $message) {
        $this->msgsSent[] = $message->toString();
    }

    public function testImmediate() {
        error_log(__METHOD__);

        # Mock the actual send
        $mock = $this->getMockBuilder('Digest')
            ->setConstructorArgs([$this->dbhr, $this->dbhm])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        # Create a group with a message on it.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_FREEGLE);
        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $msg = str_replace("Hey", "Hey {{username}}", $msg);

        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants immediate delivery.
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addMembership($gid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::IMMEDIATE);
        $u->setMembershipAtt($gid, 'emailallowed', 1);
        assertGreaterThan(0, $u->addEmail('test2@test.com'));

        # Now test.
        assertEquals(1, $mock->send($gid, Digest::IMMEDIATE));
        assertEquals(1, count($this->msgsSent));

        $expanded = <<<EOT
Return-Path: <noreply@ilovefreegle.org>
Message-ID: removed
Date: removed
Subject: OFFER: Test item (location)
From: Test User <test@test.com>
Reply-To: Test User <test@test.com>
To: Test User <test2@test.com>
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="_=_canonicalised_"
List-Unsubscribe: <mailto:{{unsubscribe}}>


--_=_canonicalised_
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Hey {{username}}.

--_=_canonicalised_
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.=
w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>OFFER: Test item (location)</title>
    <meta name=3D"viewport" content=3D"width=3Ddevice-width, initial-scale=
=3D1, maximum-scale=3D1, user-scalable=3D0" />
    <meta http-equiv=3D"Content-Type" content=3D"text/html; charset=3DUTF-8=
" />
    <!--[if !mso]><!-->
    <meta http-equiv=3D"X-UA-Compatible" content=3D"IE=3Dedge" />
    <!--<![endif]-->

    <style type=3D"text/css">

        .ReadMsgBody { width: 100%; background-color: #F6F6F6; }
        .ExternalClass { width: 100%; background-color: #F6F6F6; }
        body { width: 100%; background-color: #f6f6f6; margin: 0; padding: =
0; -webkit-font-smoothing: antialiased; font-family: Arial, Times, serif }
        table { border-collapse: collapse !important; mso-table-lspace: 0pt=
; mso-table-rspace: 0pt; }

        @-ms-viewport{ width: device-width; }
       =20
        .button {
            max-width: 100px !important;
        }

        @media only screen and (max-width: 639px){
            .wrapper{ width:100%;  padding: 0 !important; }
        }

        @media only screen and (max-width: 480px){
            .centerClass{ margin:0 auto !important; }
            .imgClass{ width:100% !important; height:auto; }
            *[class=3D"mobileOff"] { width: 0px !important; display: none !=
important; }
            *[class*=3D"mobileOn"] { display: block !important; max-height:=
none !important; }
        }

    </style>

    <!--[if gte mso 15]>
    <style type=3D"text/css">
        table { font-size:1px; line-height:0; mso-margin-top-alt:1px;mso-li=
ne-height-rule: exactly; }
        * { mso-line-height-rule: exactly; }
    </style>
    <![endif]-->

</head>
<body marginwidth=3D"0" marginheight=3D"0" leftmargin=3D"0" topmargin=3D"0"=
 style=3D"background-color:#F7F5EB; font-family:Arial,serif; margin:0; padd=
ing:0; min-width: 100%; -webkit-text-size-adjust:none; -ms-text-size-adjust=
:none;">

<!-- Start Background -->
<table width=3D"100%" cellpadding=3D"0" cellspacing=3D"0" border=3D"0" bgco=
lor=3D"#F7F5EB">
    <tr>
        <td width=3D"100%" valign=3D"top" align=3D"center">

            <!-- Start Wrapper  -->
            <table width=3D"95%" cellpadding=3D"0" cellspacing=3D"0" border=
=3D"0" class=3D"wrapper" bgcolor=3D"#FFFFFF">
                <tr>
                    <td height=3D"10" style=3D"font-size:10px; line-height:=
10px;">   </td><!-- Spacer -->
                </tr>
                <tr>
                    <td align=3D"center">

                        <!-- Start Container  -->
                        <table width=3D"100%" cellpadding=3D"0" cellspacing=
=3D"0" border=3D"0" class=3D"container">
                            <tr>
                                <td width=3D"100%" class=3D"mobile" style=
=3D"font-family:arial; font-size:12px; line-height:18px;">
                                    <table width=3D"95%" cellpadding=3D"0" =
cellspacing=3D"0" border=3D"0" class=3D"wrapper" bgcolor=3D"#FFFFFF">
                                        <tr>
                                            <td height=3D"20" style=3D"font=
-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                        </tr>
                                        <tr>
                                            <td align=3D"center">
                                                <table width=3D"95%" cellpa=
dding=3D"0" cellspacing=3D"0" border=3D"0" class=3D"container">
                                                    <tr>
                                                        <td class=3D"mobile=
" align=3D"center" valign=3D"top">
                                                            <table class=3D=
"mobileOff" width=3D"120" cellpadding=3D"0" cellspacing=3D"0" border=3D"0" =
class=3D"container" align=3D"left">
                                                                <tr>
                                                                    <td wid=
th=3D"120" style=3D"font-size:12px; line-height:18px;">
                                                                        <a =
href=3D"https://users.ilovefreegle.org">
                                                                           =
 <img src=3D"https://iznik.modtools.org/images/user_logo.png" width=3D"100"=
 height=3D"100" style=3D"border-radius:3px; margin:0; padding:0; border:non=
e; display:block;" alt=3D"" class=3D"imgClass" />
                                                                        </a=
>   =20
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td hei=
ght=3D"20" style=3D"font-size:10px; line-height:10px;" class=3D"mobileOn"> =
</td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td hei=
ght=3D"20" style=3D"font-size:10px; line-height:10px;" >
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class=3D"mobile=
" align=3D"center" valign=3D"top">
                                                            <table width=3D=
"100%" cellpadding=3D"0" cellspacing=3D"0" border=3D"0" class=3D"container"=
 align=3D"right">
                                                                <tr>
                                                                   =20
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td height=3D"10" style=3D"font-size:10px; line-height:=
10px;"> </td>
                </tr>
           </table><table width=3D"95%" cellpadding=3D"0" cellspacing=3D"0"=
 border=3D"0" class=3D"wrapper" bgcolor=3D"#F7F5EB">
    <tr>
        <td style=3D"padding-left: 10px; color: grey; font-size:10px;">
            <p>You've got this mail because you're a member of Freegle.    =
      <a href=3D"{{unsubscribe}}">Unsubscribe</a></p>        </td>
    </tr>
    <tr>
        <td style=3D"padding-left: 10px; color: grey; font-size:10px;">
            <p>Freegle is registered as a charity with HMRC (ref. XT32865) =
and is run by volunteers.  Which is nice.</p>
        </td>
    </tr>
</table>       </td>
       </tr>
</table>

</body>
</html>

--_=_canonicalised_--

EOT;

        assertEquals($expanded, $this->canonMessage($this->msgsSent[0]));

        error_log(__METHOD__ . " end");
    }
}

