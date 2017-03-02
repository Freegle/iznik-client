<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function donation_thank($name, $email, $visiturl, $settingsurl) {
    $logo = USERLOGO;
    $vols = VOLUNTEERS_ADDR;

    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Thank you for supporting Freegle</title>
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
                                                                            <a href="$visiturl">
                                                                                <img src="$logo" width="100" height="100" style="border-radius:3px; margin:0; padding:0; border:none; display:block;" alt="" class="imgClass" />
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                </table>               
                                                            </td>    
                                                            <td>
                                                                <h1>Thank you for donating to Freegle!</h1>
                                                                <p>It's very kind of you to donate to us.  We realise that there are a lot of charities asking for your help.</p>
                                                                <p>If you'd like to support us more without it costing you a penny, you can use <b>Give As You Live</b> - when you shop online, they donate.  Click <a href="https://www.giveasyoulive.com/join/freegle">here</a> to learn more.</p>
                                                                <p>And if you ever want to set up a regular donation of &pound;1 each month, you can do so <a href="https://uk.virginmoneygiving.com/donation-web/charity?charityId=1005493&frequencyType=M&stop_mobi=yes">here</a>.</p>
                                                                <p>Freegle is run by volunteers - if you'd like to volunteer with us then drop us a line at <a href="mailto:$vols">$vols</a> with a bit about yourself.</a>
                                                                <p>But most importantly, <a href="$visiturl">keep freegling</a>!  The planet and your neighbours will be grateful.</p>
                                                            </td>
                                                        </tr>        
                                                        <tr>
                                                            <td height="20" style="font-size:10px; line-height:10px;"> </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2">
                                                                <font color=gray><hr></font>
                                                            </td>
                                                        </tr>        
                                                        <tr>
                                                            <td colspan="2" style="color: grey; font-size:10px;">
                                                                <p>This mail was sent to $email.  You can change your settings by clicking <a href="$settingsurl">here</a>.</p>
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
                    <td height="10" style="font-size:10px; line-height:10px;"> </td>
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