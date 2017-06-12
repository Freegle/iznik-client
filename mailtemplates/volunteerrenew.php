<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function volunteering_renew($domain, $logo, $title, $url) {
    $siteurl = "https://$domain";

    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>$title</title>
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
                                                        <td class="mobile" align="left" valign="top">
                                                            <h1>Your Volunteer Opportunity: $title</h1>
                                                            <p>Please can you let us know whether this volunteer opportunity is still active?  If we don't hear from you, we'll stop showing it soon.</p>
                                                            <table>
                                                                <tbody>
                                                                    <tr>
                                                                        <td width="280" class="mobile" style="font-size:14px; line-height:20px;">
                                                                            <table width="170" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                                <tr>
                                                                                    <td width="170" height="36" bgcolor="#00008B" align="center" valign="middle"
                                                                                        style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                            line-height:18px; border-radius:3px;">
                                                                                        <a href="$url" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">Let us know</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>                                                    
                                                                </tbody>
                                                            </table>
                                                            <p>If that's not clickable, copy and paste this: $url</p>
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

    $html .= footer(NULL);

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