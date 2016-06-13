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
Return-Path: <bounce@direct.ilovefreegle.org>
Message-ID: removed
Date: removed
Subject: [testgroup] OFFER: Test item (location)
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
    <title>OFFER: Test item (location)</title>    <meta name=3D"viewport" c=
ontent=3D"width=3Ddevice-width, initial-scale=3D1, maximum-scale=3D1, user-=
scalable=3D0" />
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
:none;"><!-- Start Background -->
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
                                                    <tbody>
                                                        <tr>
                                                            <td width=3D"15=
0" class=3D"mobileOff">
                                                                <table clas=
s=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" align=3D"lef=
t" border=3D"0">
                                                                    <tr>
                                                                        <td=
>                                                          =20
                                                                           =
 <a href=3D"https://users.ilovefreegle.org">
                                                                           =
     <img src=3D"https://iznik.modtools.org/images/user_logo.png" width=3D"=
100" height=3D"100" style=3D"border-radius:3px; margin:0; padding:0; border=
:none; display:block;" alt=3D"" class=3D"imgClass" />
                                                                           =
 </a>
                                                                        </t=
d>
                                                                    </tr>
                                                                </table>   =
           =20
                                                            </td>   =20
                                                            <td>
                                                                <p>You've r=
eceived this mail because you're a member of <a href=3D"{{visit}}">testgrou=
p</a>.</p>
                                                                <table>
                                                                    <tr>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#377615" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{post}}" target=3D"_blank" alias=3D"" style=3D"fon=
t-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: =
#ffffff;">&nbsp;Freegle&nbsp;something!&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                         </=
td>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#377615" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{visit}}" target=3D"_blank" alias=3D"" style=3D"fo=
nt-family: Century Gothic, Arial, sans-serif; text-decoration: none; color:=
 #ffffff;">&nbsp;Browse&nbsp;the&nbsp;group&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                        </t=
d>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#336666" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{unsubscribe}}" target=3D"_blank" alias=3D"" style=
=3D"font-family: Century Gothic, Arial, sans-serif; text-decoration: none; =
color: #ffffff;">&nbsp;Unsubscribe&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                         </=
td>
                                                                    </tr>  =
                                                                 =20
                                                                </table>
                                                            </td>
                                                        </tr>       =20
                                                        <tr>
                                                            <td height=3D"2=
0" style=3D"font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                        </tr>
                                                        <tr>
                                                            <td colspan=3D"=
2">
                                                                <font color=
=3Dgray><hr></font>
                                                            </td>
                                                        </tr>       =20
                                                        <tr>
                                                            <td colspan=3D"=
2" class=3D"mobile" align=3D"center" valign=3D"top">
                                                                    <table =
width=3D"100%">
        <tr>
            <td colspan=3D"2">
                <p>
                    <a href=3D"https://direct.ilovefreegle.org/login.php?ac=
tion=3Dmygroups&subaction=3Ddisplaypost&groupid=
=3D999&digest=3D999">OFFER: Test item (location)</a>
                </p>               =20
            </td>
        </tr>   =20
        <tr>
            <td width=3D"75%">
                Hey {{username}}.<br /><br />
                <span style=3D"color: gray">Posted by Test User on Mon, 13t=
h June 5:05pm.</span>=20

            </td>
            <td width=3D"25%">            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <table class=3D"button" width=3D"300" cellpadding=3D"0" cel=
lspacing=3D"0" align=3D"left" border=3D"0">
                    <tr>                 =20
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"https://direct.ilovefreegle.org/logi=
n.php?action=3Dmygroups&subaction=3Ddisplaypost&g=
roupid=3D999&digest=3D999" target=3D"_blank" alias=3D"" style=3D"font-famil=
y: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff=
;">&nbsp;Reply&nbsp;via&nbsp;Web&nbsp;</a>
                        </td>
                        <td width=3D"10%">&nbsp;</td>
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"mailto:test@test.com?subject=3DRe%3A=
+OFFER%3A+Test+item+%28location%29" target=3D"_blank" alias=3D"" style=3D"f=
ont-family: Century Gothic, Arial, sans-serif; text-decoration: none; color=
: #ffffff;">&nbsp;Reply&nbsp;via&nbsp;Email&nbsp;</a>
                        </td>
                    </tr>
                </table>   =20
            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <font color=3Dgray><hr></font>
            </td>
        </tr>       =20
    </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan=3D"=
2" style=3D"color: grey; font-size:10px;">
                                                                <p>This mai=
l was sent to {{email}}.  You are set to receive updates for testgroup {{fr=
equency}}.</p>
                                                                <p>You can =
change your settings by clicking <a href=3D"https://direct.ilovefreegle.org=
/login.php?action=3Dmysettings">here</a>, or turn these mails off by emaili=
ng <a href=3D"mailto:{{noemail}}">{{noemail}}</a>.</p>
                                                                <p>Freegle =
is registered as a charity with HMRC (ref. XT32865) and is run by volunteer=
s. Which is nice.</p>=20
                                                            </td>
                                                        </tr>       =20
                                                    </tbody>
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
           </table>
       </td>
       </tr>
</table>

</body>
</html>

--_=_canonicalised_--

EOT;

        assertEquals($expanded, $this->canonMessage($this->msgsSent[0]));

        error_log(__METHOD__ . " end");
    }

    public function testSend() {
        error_log(__METHOD__);

        # Actual send for coverage.
        $d = new Digest($this->dbhr, $this->dbhm);

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
        assertGreaterThan(0, $u->addEmail('test@blackhole.io'));

        # Now test.
        assertEquals(1, $d->send($gid, Digest::IMMEDIATE));

        error_log(__METHOD__ . " end");
    }

    public function testMultiple() {
        error_log(__METHOD__);

        # Mock the actual send
        $mock = $this->getMockBuilder('Digest')
            ->setConstructorArgs([$this->dbhr, $this->dbhm])
            ->setMethods(array('sendOne'))
            ->getMock();
        $mock->method('sendOne')->will($this->returnCallback(function($mailer, $message) {
            return($this->sendMock($mailer, $message));
        }));

        # Create a group with two messages on it, one taken.
        $g = new Group($this->dbhr, $this->dbhm);
        $gid = $g->create("testgroup", Group::GROUP_FREEGLE);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test item (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'OFFER: Test thing (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        $msg = $this->unique(file_get_contents('msgs/basic'));
        $msg = str_replace("FreeglePlayground", "testgroup", $msg);
        $msg = str_replace('Basic test', 'TAKEN: Test item (location)', $msg);
        $r = new MailRouter($this->dbhr, $this->dbhm);
        $id = $r->received(Message::YAHOO_APPROVED, 'from@test.com', 'to@test.com', $msg, $gid);
        assertNotNull($id);
        error_log("Created message $id");
        $rc = $r->route();
        assertEquals(MailRouter::APPROVED, $rc);

        # Create a user on that group who wants digest.
        $u = new User($this->dbhr, $this->dbhm);
        $uid = $u->create(NULL, NULL, 'Test User');
        $u->addMembership($gid);
        $u->setMembershipAtt($gid, 'emailfrequency', Digest::HOUR1);
        $u->setMembershipAtt($gid, 'emailallowed', 1);
        assertGreaterThan(0, $u->addEmail('test2@test.com'));

        # Now test.
        assertEquals(1, $mock->send($gid, Digest::HOUR1));
        assertEquals(1, count($this->msgsSent));

        $expanded = <<<EOT
Return-Path: <bounce@direct.ilovefreegle.org>
Message-ID: removed
Date: removed
Subject: [testgroup] What's New (1 message) - Test thing
From: testgroup <testgroup-owner@yahoogroups.com>
Reply-To: testgroup <testgroup-owner@yahoogroups.com>
To: Test User <test2@test.com>
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="_=_canonicalised_"
List-Unsubscribe: <mailto:{{unsubscribe}}>


--_=_canonicalised_
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

OFFER: Test thing (location):
https://direct.ilovefreegle.org/login.php?action=3Dmygroups&subaction=3Ddis=
playpost&groupid=3D999&digest=3D999

OFFER: Test item (location) (post completed, no longer active)


--_=_canonicalised_
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.=
w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>[testgroup] What's New (1 message) - Test thing</title>    <meta=
 name=3D"viewport" content=3D"width=3Ddevice-width, initial-scale=3D1, maxi=
mum-scale=3D1, user-scalable=3D0" />
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
:none;"><!-- Start Background -->
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
                                                    <tbody>
                                                        <tr>
                                                            <td width=3D"15=
0" class=3D"mobileOff">
                                                                <table clas=
s=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" align=3D"lef=
t" border=3D"0">
                                                                    <tr>
                                                                        <td=
>                                                          =20
                                                                           =
 <a href=3D"https://users.ilovefreegle.org">
                                                                           =
     <img src=3D"https://iznik.modtools.org/images/user_logo.png" style=3D"=
width: 100px; height: 100px; border-radius:3px; margin:0; padding:0; border=
:none; display:block;" alt=3D"" class=3D"imgClass" />
                                                                           =
 </a>
                                                                        </t=
d>
                                                                    </tr>
                                                                </table>   =
           =20
                                                            </td>   =20
                                                            <td>
                                                                <p>You've r=
eceived this mail because you're a member of <a href=3D"{{visit}}">testgrou=
p</a>.</p>
                                                                <table>
                                                                    <tr>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#377615" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{post}}" target=3D"_blank" alias=3D"" style=3D"fon=
t-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: =
#ffffff;">&nbsp;Freegle&nbsp;something!&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                         </=
td>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#377615" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{visit}}" target=3D"_blank" alias=3D"" style=3D"fo=
nt-family: Century Gothic, Arial, sans-serif; text-decoration: none; color:=
 #ffffff;">&nbsp;Browse&nbsp;the&nbsp;group&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                        </t=
d>
                                                                        <td=
>
                                                                           =
 <table class=3D"button" width=3D"90%" cellpadding=3D"0" cellspacing=3D"0" =
align=3D"left" border=3D"0">
                                                                           =
     <tr>
                                                                           =
         <td width=3D"50%" height=3D"36" bgcolor=3D"#336666" align=3D"cente=
r" valign=3D"middle"
                                                                           =
             style=3D"font-family: Century Gothic, Arial, sans-serif; font-=
size: 16px; color: #ffffff;
                                                                           =
                 line-height:18px; border-radius:3px;">
                                                                           =
             <a href=3D"{{unsubscribe}}" target=3D"_blank" alias=3D"" style=
=3D"font-family: Century Gothic, Arial, sans-serif; text-decoration: none; =
color: #ffffff;">&nbsp;Unsubscribe&nbsp;</a>
                                                                           =
         </td>
                                                                           =
     </tr>
                                                                           =
 </table>
                                                                         </=
td>
                                                                    </tr>  =
                                                                 =20
                                                                </table>
                                                            </td>
                                                        </tr>       =20
                                                        <tr>
                                                            <td height=3D"2=
0" style=3D"font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                        </tr>
                                                        <tr>
                                                            <td colspan=3D"=
2">
                                                                <font color=
=3Dgray><hr></font>
                                                            </td>
                                                        </tr>        <tr><t=
d colspan=3D"2" class=3D"mobile" valign=3D"top"><h2><span style=3D"color:gr=
een;">New Posts</span></h2><p>Here's what people are freegling since we las=
t mailed you.</p></td></tr><tr><td colspan=3D"2"><strong>OFFER: Test thing =
(location)<br /></strong></td></tr><tr><td colspan=3D"2"><p>Scroll down for=
 details and to reply (we used to put links here but they don't work reliab=
ly).</p></td></tr><tr><td colspan=3D"2"><font color=3Dgray><hr></font></td>=
</tr><tr><td colspan=3D"2">    <table width=3D"100%">
        <tr>
            <td colspan=3D"2">
                <p>
                    <a href=3D"https://direct.ilovefreegle.org/login.php?ac=
tion=3Dmygroups&subaction=3Ddisplaypost&groupid=
=3D999&digest=3D999">OFFER: Test thing (location)</a>
                </p>               =20
            </td>
        </tr>   =20
        <tr>
            <td width=3D"75%">
                Hey.<br /><br />
                <span style=3D"color: gray">Posted by Test User on Mon, 13t=
h June 5:05pm.</span>=20

            </td>
            <td width=3D"25%">            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <table class=3D"button" width=3D"300" cellpadding=3D"0" cel=
lspacing=3D"0" align=3D"left" border=3D"0">
                    <tr>                 =20
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"https://direct.ilovefreegle.org/logi=
n.php?action=3Dmygroups&subaction=3Ddisplaypost&g=
roupid=3D999&digest=3D999" target=3D"_blank" alias=3D"" style=3D"font-famil=
y: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff=
;">&nbsp;Reply&nbsp;via&nbsp;Web&nbsp;</a>
                        </td>
                        <td width=3D"10%">&nbsp;</td>
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"mailto:test@test.com?subject=3DRe%3A=
+OFFER%3A+Test+thing+%28location%29" target=3D"_blank" alias=3D"" style=3D"=
font-family: Century Gothic, Arial, sans-serif; text-decoration: none; colo=
r: #ffffff;">&nbsp;Reply&nbsp;via&nbsp;Email&nbsp;</a>
                        </td>
                    </tr>
                </table>   =20
            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <font color=3Dgray><hr></font>
            </td>
        </tr>       =20
    </table></td></tr><tr><td colspan=3D"2" class=3D"mobile" valign=3D"top"=
><h2><span style=3D\"color:green\">Missed Posts</span></h2></td></tr><tr><t=
d colspan=3D"2"><p>These posts came and went since your last mail.  If this=
 happens a lot, click <a href=3D\"https://direct.ilovefreegle.org/login.php=
?action=3Dmysettings\">here</a> and choose more frequent mails.</p></td></t=
r><tr><td colspan=3D"2">    <table width=3D"100%">
        <tr>
            <td colspan=3D"2">
                <p>
                    <a href=3D"https://direct.ilovefreegle.org/login.php?ac=
tion=3Dmygroups&subaction=3Ddisplaypost&groupid=
=3D999&digest=3D999">OFFER: Test item (location)</a>
                </p>               =20
            </td>
        </tr>   =20
        <tr>
            <td width=3D"75%">
                Hey.<br /><br />
                <span style=3D"color: gray">Posted by Test User on Mon, 13t=
h June 5:05pm.</span>=20

            </td>
            <td width=3D"25%">            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <table class=3D"button" width=3D"300" cellpadding=3D"0" cel=
lspacing=3D"0" align=3D"left" border=3D"0">
                    <tr>                 =20
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"https://direct.ilovefreegle.org/logi=
n.php?action=3Dmygroups&subaction=3Ddisplaypost&g=
roupid=3D999&digest=3D999" target=3D"_blank" alias=3D"" style=3D"font-famil=
y: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff=
;">&nbsp;Reply&nbsp;via&nbsp;Web&nbsp;</a>
                        </td>
                        <td width=3D"10%">&nbsp;</td>
                        <td width=3D"45%" height=3D"24" bgcolor=3D"#377615"=
 align=3D"center" valign=3D"middle"
                            style=3D"font-family: Century Gothic, Arial, sa=
ns-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href=3D"mailto:test@test.com?subject=3DRe%3A=
+OFFER%3A+Test+item+%28location%29" target=3D"_blank" alias=3D"" style=3D"f=
ont-family: Century Gothic, Arial, sans-serif; text-decoration: none; color=
: #ffffff;">&nbsp;Reply&nbsp;via&nbsp;Email&nbsp;</a>
                        </td>
                    </tr>
                </table>   =20
            </td>
        </tr>
        <tr>
            <td colspan=3D"2">
                <font color=3Dgray><hr></font>
            </td>
        </tr>       =20
    </table></td></tr>                                                     =
   <tr>
                                                            <td colspan=3D"=
2" style=3D"color: grey; font-size:10px;">
                                                                <p>This mai=
l was sent to {{email}}.  You are set to receive updates for testgroup {{fr=
equency}}.</p>
                                                                <p>You can =
change your settings by clicking <a href=3D"https://direct.ilovefreegle.org=
/login.php?action=3Dmysettings">here</a>, or turn these mails off by emaili=
ng <a href=3D"mailto:{{noemail}}">{{noemail}}</a>.</p>
                                                                <p>Freegle =
is registered as a charity with HMRC (ref. XT32865) and is run by volunteer=
s. Which is nice.</p>=20
                                                            </td>
                                                        </tr>       =20
                                                    </tbody>
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
                    <td height=3D\"10\" style=3D\"font-size:10px; line-heig=
ht:10px;\"> </td>
                </tr>
           </table>
       </td>
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

