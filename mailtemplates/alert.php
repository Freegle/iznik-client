<?php
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function alert_tpl($toname, $domain, $logo, $subject, $htmlsummary, $unsub, $click, $beacon)
{
    $siteurl = "https://$domain";
    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>$subject</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--<![endif]-->

    <style type="text/css">

        .ReadMsgBody { width: 100%; background-color: #F6F6F6; }
        .ExternalClass { width: 100%; background-color: #F6F6F6; }
        body { width: 100%; background-color: #f6f6f6; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; font-family: Arial, Times, serif }
        table { border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }

        @-ms-viewport{ width: device-width; }
        
        .button {
            max-width: 100px !important;
        }

        @media only screen and (max-width: 639px){
            .wrapper{ width:100%;  padding: 0 !important; }
        }

        @media only screen and (max-width: 480px){
            .centerClass{ margin:0 auto !important; }
            .imgClass{ width:100% !important; height:auto; }
            *[class="mobileOff"] { width: 0px !important; display: none !important; }
            *[class*="mobileOn"] { display: block !important; max-height:none !important; }
        }

    </style>

    <!--[if gte mso 15]>
    <style type="text/css">
        table { font-size:1px; line-height:0; mso-margin-top-alt:1px;mso-line-height-rule: exactly; }
        * { mso-line-height-rule: exactly; }
    </style>
    <![endif]-->

</head>
<body marginwidth="0" marginheight="0" leftmargin="0" topmargin="0" style="background-color:#F7F5EB; font-family:Arial,serif; margin:0; padding:0; min-width: 100%; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;">

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
                                                            <!-- Start Content -->
                                                            <table class="mobileOff" width="120" cellpadding="0" cellspacing="0" border="0" class="container" align="left">
                                                                <tr>
                                                                    <td width="120" style="font-size:12px; line-height:18px;">
                                                                        <a href="$siteurl">
                                                                            <img src="$logo" width="100" height="100" style="border-radius:3px; margin:0; padding:0; border:none; display:block;" alt="" class="imgClass" />
                                                                            <img src="$beacon" width="1" height="1" />
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
                                                            <!-- Start Content -->
                                                        </td>
                                                        <td class="mobile" valign="top">

                                                            <!-- Start Content -->
                                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container" align="right">
                                                                <tr>
                                                                    <td>
                                                                        <h1>$subject</h1>
                                                                        <p>Dear $toname,</p>
                                                                    </td>    
                                                                </tr>                                                                    
                                                                <tr>
                                                                    <td>
EOT;
    if ($click) {
        $html .= <<<EOT
                                                                        <p style="color: red;">Please let us know that you got this by clicking here:</p>
            
                                                                        <!-- Start Button -->
                                                                        <table class="button" width="50%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                            <tr>
                                                                                <td width="50%" height="36" bgcolor="#377615" align="center" valign="middle"
                                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                        line-height:18px; border-radius:3px;">
                                                                                    <a href="$click" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">I got this</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        <!-- End Button -->
                                                                    </td>    
EOT;
    }
    $html .= <<<EOT
                                                                </tr>
                                                                <tr>
                                                                    <td width="100%" align="left" class="mobile" style="font-family: Century Gothic, Arial, sans-serif; font-size:14px; line-height:20px;">
                                                                        $htmlsummary                                                                     
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                            </table>
                                                            <!-- Start Content -->
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- Start Container  -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Wrapper  -->
                                </td>
                            </tr>
                        </table>
                        <!-- Start Container  -->
                    </td>
                </tr>
                <tr>
                    <td height="10" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                </tr>
           </table>
            <!-- End Wrapper  -->
            <table width="95%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#F7F5EB">
                <tr>
                    <td style="padding-left: 10px; color: grey; font-size:10px;">
                        <p>Sorry if you receive this several times.  We have to try a variety of ways to make sure we don't miss anyone.  This includes use of web beacons.</p>
                    </td>
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