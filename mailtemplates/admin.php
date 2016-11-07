<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function admin_tpl($groupname, $toname, $email, $siteurl, $logo, $subject, $message, $post, $unsub, $visit)
{
    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>ADMIN: $subject</title>
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

                                    <!-- Start Wrapper  -->
                                    <table width="95%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                                        <tr>
                                            <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                        </tr>
                                        <tr>
                                            <td align="center">

                                                <!-- Start Container  -->
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
                                                                    <td height="20" style="font-size:10px; line-height:10px;">
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
                                                                                    <a href="$post" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Freegle&nbsp;something!&nbsp;</a>
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
                                                                                    <a href="$visit" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Browse&nbsp;the&nbsp;group&nbsp;</a>
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
                                                                                    <a href="$unsub" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Unsubscribe&nbsp;</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                     </td>
                                                                </tr>                                                                    
                                                            </table>
                                                        </td>
                                                     </tr>
                                                     <td>
                                                        <td class="mobile" valign="top">

                                                            <!-- Start Content -->
                                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container" align="right">
                                                                <tr>
                                                                    <td width="100%" align="left" class="htmlsummary mobile" style="font-family: Century Gothic, Arial, sans-serif; font-size:14px; line-height:20px;">
                                                                        <h1>$subject</h1>
                                                                        $message                                                                
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td>
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
                    <td height="10" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                </tr>
           </table>
EOT;

    if ($unsub) {
        $html .= footer($unsub);
    }

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