<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function digest_multiple($available, $availablesumm, $unavailable, $siteurl, $domain, $logo, $groupname, $subject, $fromname, $reply) {
    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>$subject</title>
EOT;

    $html .= mail_header();
    $html .= <<<EOT
<!-- Start Background -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F7F5EB">
    <tr>
        <td width="100%" valign="top" align="center">

            <!-- Start Wrapper  -->
            <table width="95%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                <tr>
                    <td height="10" style="font-size:10px; line-height:10px;">   </td><!-- Spacer -->
                </tr>
                <tr>
                    <td align="center">

                        <!-- Start Container  -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container">
                            <tr>
                                <td width="100%" class="mobile" style="font-family:arial; font-size:12px; line-height:18px;">
                                    <table width="95%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                                        <tr>
                                            <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <table width="95%" cellpadding="0" cellspacing="0" border="0" class="container">
                                                    <tbody>
                                                        <tr>
                                                            <td width="150" class="mobileOff">
                                                                <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                    <tr>
                                                                        <td>                                                           
                                                                            <a href="$siteurl">
                                                                                <img src="$logo" style="width: 100px; height: 100px; border-radius:3px; margin:0; padding:0; border:none; display:block;" alt="" class="imgClass" />
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                </table>               
                                                            </td>    
                                                            <td>
                                                                <p>You've received this mail because you're a member of <a href="{{visit}}">$groupname</a>.</p>
                                                                <table width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                                <tr>
                                                                                    <td width="50%" height="36" bgcolor="#377615" align="center" valign="middle"
                                                                                        style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                            line-height:18px; border-radius:3px;">
                                                                                        <a href="{{post}}" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Freegle&nbsp;something!&nbsp;</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                         </td>
                                                                        <td>
                                                                            <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                                <tr>
                                                                                    <td width="50%" height="36" bgcolor="#377615" align="center" valign="middle"
                                                                                        style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                            line-height:18px; border-radius:3px;">
                                                                                        <a href="{{visit}}" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Browse&nbsp;the&nbsp;group&nbsp;</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td>
                                                                            <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                                <tr>
                                                                                    <td width="50%" height="36" bgcolor="#336666" align="center" valign="middle"
                                                                                        style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                            line-height:18px; border-radius:3px;">
                                                                                        <a href="{{unsubscribe}}" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Unsubscribe&nbsp;</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                         </td>
                                                                    </tr>                                                                    
                                                                </table>
                                                            </td>
                                                        </tr>        
                                                        <tr>
                                                            <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2">
                                                                <font color=gray><hr></font>
                                                            </td>
                                                        </tr>        
EOT;

    if ($available != '') {
        $html .= '<tr><td colspan="2" class="mobile" valign="top">';
        $html .= '<h2><span style="color:green;">New Posts</span></h2>';
        $html .= "<p>Here's what people are freegling since we last mailed you.</p></td></tr>";
        $html .= '<tr><td colspan="2"><strong>' . $availablesumm . '</strong></td></tr>';
        $html .= '<tr><td colspan="2"><p>Scroll down for details and to reply.</p></td></tr>';
        $html .= '<tr><td colspan="2"><font color=gray><hr></font></td></tr>';
        $html .= '<tr><td colspan="2">' . $available . '</td></tr>';
    }

    if ($unavailable != '') {
        $html .= '<tr><td colspan="2" class="mobile" valign="top">';
        $html .= '<h2><span style=\"color:green\">Missed Posts</span></h2></td></tr>';
        $html .= '<tr><td colspan="2"><p>These posts came and went since your last mail.  If this happens a lot, click <a href=\"$siteurl/settings\">here</a> and choose more frequent mails.</p></td></tr>';
        $html .= '<tr><td colspan="2">' . $unavailable . '</td></tr>';
    }
    
    $html .= <<<EOT
                                                        <tr>
                                                            <td colspan="2" style="color: grey; font-size:10px;">
                                                                <p>This mail was sent to {{email}}.  You are set to receive updates for $groupname {{frequency}}.</p>
                                                                <p>You can change your settings by clicking <a href="$siteurl/settings">here</a>, or turn these mails off by emailing <a href="mailto:{{noemail}}">{{noemail}}</a></p>
                                                                <p>Freegle is registered as a charity with HMRC (ref. XT32865) and is run by volunteers. Which is nice.</p> 
                                                            </td>
                                                        </tr>        
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
                    <td height=\"10\" style=\"font-size:10px; line-height:10px;\"> </td>
                </tr>
           </table>
       </td>
       </tr>
</table>

</body>
</html>
EOT;

    return($html);
}