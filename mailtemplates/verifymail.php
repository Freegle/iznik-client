<?php

function verify_email($email, $confirm, $logo) {
    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

    <title>Email Verification</title>
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

        @media only screen and (max-width: 639px){
            .wrapper{ width:100%;  padding: 0 !important; }
        }

        @media only screen and (max-width: 480px){
            .centerClass{ margin:0 auto !important; }
            .imgClass{ width:100% !important; height:auto; }
            .wrapper{ width:320px; padding: 0 !important; }
            .header{ width:320px; padding: 0 !important; background-image: url(https://placehold.it/320x400) !important; }
            .container{ width:300px;  padding: 0 !important; }
            .mobile{ width:300px; display:block; padding: 0 !important; text-align:center !important;}
            .mobile50{ width:300px; padding: 0 !important; text-align:center; }
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
<body marginwidth="0" marginheight="0" leftmargin="0" topmargin="0" style="background-color:#F6F6F6; font-family:Arial,serif; margin:0; padding:0; min-width: 100%; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;">

<!--[if !mso]><!-- -->
<img style="min-width:640px; display:block; margin:0; padding:0" class="mobileOff" width="640" height="1" src="images/spacer.gif">
<!--<![endif]-->

<!-- Start Background -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F6F6F6">
    <tr>
        <td width="100%" valign="top" align="center">

            <!-- Start Wrapper  -->
            <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                <tr>
                    <td height="10" style="font-size:10px; line-height:10px;">   </td><!-- Spacer -->
                </tr>
                <tr>
                    <td align="center">

                        <!-- Start Container  -->
                        <table width="600" cellpadding="0" cellspacing="0" border="0" class="container">
                            <tr>
                                <td width="600" class="mobile" style="font-family:arial; font-size:12px; line-height:18px;">

                                    <!-- Start Wrapper  -->
                                    <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                                        <tr>
                                            <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                        </tr>
                                        <tr>
                                            <td align="center">

                                                <!-- Start Container  -->
                                                <table width="600" cellpadding="0" cellspacing="0" border="0" class="container">
                                                    <tr>
                                                        <td width="300" class="mobile" align="center" valign="top">

                                                            <!-- Start Content -->
                                                            <table width="280" cellpadding="0" cellspacing="0" border="0" class="container" align="left">
                                                                <tr>
                                                                    <td width="280" class="mobile" style="font-size:12px; line-height:18px;">
                                                                        <img src="$logo" width="280" height="280" style="margin:0; padding:20; border:none; display:block;" alt="" class="imgClass" />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;" class="mobileOn"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;" >
                                                                </tr>
                                                            </table>
                                                            <!-- Start Content -->
                                                        </td>
                                                        <td width="300" class="mobile" align="center" valign="top">

                                                            <!-- Start Content -->
                                                            <table width="280" cellpadding="0" cellspacing="0" border="0" class="container" align="right">
                                                                <tr>
                                                                    <td width="280" align="left" class="mobile" style="font-family: Century Gothic, Arial, sans-serif; font-size:20px; line-height:26px; font-weight:bold;">
                                                                        Please confirm your email
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td width="280" align="left" class="mobile" style="font-family: Century Gothic, Arial, sans-serif; font-size:14px; line-height:20px;">
                                                                        <p>Someone, probably you, has said that <b>$email</b> is their email address.<p>
                                                                        <p>If this was you, please click on the link below to verify the address; if this wasn't you,
                                                                        please just ignore this mail.</p>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td height="20" style="font-size:10px; line-height:10px;"> </td><!-- Spacer -->
                                                                </tr>
                                                                <tr>
                                                                    <td width="280" class="mobile" style="font-size:14px; line-height:20px;">

                                                                        <!-- Start Button -->
                                                                        <table width="170" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                            <tr>
                                                                                <td width="170" height="36" bgcolor="#00008B" align="center" valign="middle"
                                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                        line-height:18px; border-radius:3px;">
                                                                                    <a href="$confirm" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">Yes, this is my email</a>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <p>If that's not clickable, copy and paste this: $confirm</p>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        <!-- End Button -->

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

        </td>
    </tr>
</table>
<!-- End Background -->

</body>
</html>
EOT;

return($html);
}