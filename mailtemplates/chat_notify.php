<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function chat_notify($domain, $logo, $fromname, $fromid, $reply, $htmlsummary, $unsub, $image) {
    $siteurl = "https://$domain";

    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>$fromname</title>
EOT;
    $html .= mail_header();
    $html .= <<<EOT
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F7F5EB">
    <tr>
        <td width="100%" valign="top" align="center">
            <table width="95%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                <tr>
                    <td height="10" style="font-size:10px; line-height:10px;">   </td><!-- Spacer -->
                </tr>
                <tr>
                    <td align="center">
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
                                                    <tr>
                                                        <td class="mobile" align="center" valign="top">
                                                            <table class="mobileOff" width="120" cellpadding="0" cellspacing="0" border="0" class="container" align="left">
                                                                <tr>
                                                                    <td width="120" style="font-size:12px; line-height:18px;">
                                                                        <a href="$siteurl">
                                                                            <img src="$logo" width="100" height="100" style="border-radius:3px; margin:0; padding:0; border:none; display:block;" alt="" class="imgClass" />
                                                                        </a>    
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;" class="mobileOn"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;" >
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="mobile" align="center" valign="top">
                                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container" align="right">
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td width="100%" align="left" class="mobile" style="font-family: Century Gothic, Arial, sans-serif; font-size:14px; line-height:20px;">
                                                                        <p style="color: red">Please vote for Freegle to win up to £7000 of much-needed funding.  Save us as your favourite eBay charity - click <a href="http://freegle.it/eBay">here</a>.</p>                                                                    
                                                                        $htmlsummary                                                                     
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="color: darkgreen; font-size:12px; line-height:18px;">
                                                                        You can respond by just replying to this email - but it works 
                                                                        better if you reply on the site using this button:
                                                                    </td>
                                                                </tr>    
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td width="100%" class="mobile" style="font-size:14px; line-height:20px;">
                                                                        <table class="button" width="50%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                            <tr>
                                                                                <td width="50%" height="36" bgcolor="#377615" align="center" valign="middle"
                                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                        line-height:18px; border-radius:3px;">
                                                                                    <a href="$reply" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">Reply</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="color: grey; font-size:11px; line-height:18px;">
                                                                        In case we need to know, this message was sent by user #$fromid. 
                                                                    </td>
                                                                </tr>    
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
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
                    <td height="10" style="font-size:10px; line-height:10px;"> </td>
                </tr>
           </table>
EOT;

    $html .= footer($unsub);

    $html .= <<<EOT
       </td>
       </tr>
</table>
<!-- End Background -->

</body>
</html>
EOT;

    return($html);
}