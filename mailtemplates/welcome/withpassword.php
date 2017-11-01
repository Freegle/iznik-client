<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function welcome_password($domain, $logo, $email, $password) {
    $siteurl = "https://$domain";
    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Welcome to Freegle!</title>
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
                                                            <td width="150" class="mobileOff" style="vertical-align: top">
                                                                <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                    <tr>
                                                                        <td>                                                           
                                                                            <a href="$siteurl">
                                                                                <img src="$logo" width="100" height="100" style="border-radius:3px; margin:0; padding:0; border:none; display:block;" alt="" class="imgClass" />
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                </table>               
                                                            </td>    
                                                            <td>
                                                                <h1>Thanks for joining Freegle!</h1>
EOT;

    if ($password) {
        $html .= <<<EOT
                                                                <p>Here's your password: <b>$password</b>.</p>
EOT;
    }

    $html .= <<<EOT
                                                                <p>We could give you a ten page long Terms of Use.  Like you'd read that.</p>
                                                                <p>As far as we're concerned, it's simple:</p>
                                                                <ol>
                                                                    <li>Use Freegle to pass on unwanted items direct to others.</li>
                                                                    <li>Everything must be free and legal.</li>
                                                                    <li>Be nice to other freeglers, and they'll be nice to you.</li>
                                                                    <li>We don't see or check items offered - see our <a href="$siteurl/disclaimer">disclaimer</a>.</li>
                                                                    <li>Freegle groups may have additional rules - check your local group for details.</li>
                                                                    <li>Please <a href="$siteurl/disclaimer">keep yourself safe and watch out for scams</a>.</li>
                                                                    <li>Objectionable content or behaviour will not be tolerated.</li>
                                                                    <li>If you see anything inappropriate, suspicious or just dodgy then please <a href="$siteurl/help">report it</a>.</li>
                                                                    <li>You must be aged 12 or over, as there is user-generated content.</li>
                                                                    <li>We won’t sell your email address to anyone else - see <a href="$siteurl/privacy">privacy page</a>.</li>
                                                                    <li>We will email you posts, replies, events, newsletters, etc - control these in <a href="$siteurl/settings">Settings</a>.</li>
                                                                    <li>For some Freegle local groups, you are likely to be obliged to adhere to <a href="https://policies.yahoo.com/ie/en/yahoo/terms/utos/index.htm" target="_blank" rel="noopener" data-realurl="true">Yahoo! Groups Terms of Service</a> or those of <a href="http://www.norfolkfreegle.org/Home/Terms" target="_blank" rel="noopener" data-realurl="true">Norfolk Freegle</a>.</li>
                                                                    <li>We will make your post public to publicise Freegle but not your email address - see <a href="$siteurl/privacy">privacy page</a>.</li>
                                                                </ol>
    
                                                                <p>Happy freegling!</p>  
                                                                <p>You might also like these quick instructions.</p>
                                                                
                                                                <h2>How to post an OFFER</h2>
                                                                
                                                                <ol>
                                                                <li>Go to https://www.ilovefreegle.org and click on 'Give stuff'.</li>
                                                                <li>Either click on 'Find your location' or put your postcode into the box on the right.</li>
                                                                <li>You will see, lower down the screen, a suggestion of which group to post on. If this is incorrect, click on the down arrow and it will bring up a list of groups you are a member of.</li>
                                                                <li>Click on 'Next'.</li>
                                                                <li>Click the 'Add photos' button to add a photo if you have one.</li>
                                                                <li>Fill in the appropriate info in the various boxes.</li>
                                                                <li>Click 'Next'.</li>
                                                                <li>Fill in your email address.</li>
                                                                <li>That's it.</li>
                                                                </ol>
                                                                
                                                                <h2>How to post a WANTED</h2>
                                                                
                                                                <ol>
                                                                <li>Go to https://www.ilovefreegle.org and click on 'Find stuff'.</li>
                                                                <li>Either click on 'Find your location' or put your postcode into the box on the right.</li>
                                                                <li>You will see, lower down the screen, a suggestion of which group to post on. If this is incorrect, click on the down arrow and it will bring up a list of groups you are a member of.</li>
                                                                <li>Click on 'Next'.</li>
                                                                <li>Either stick what you are looking for in the 'Search' bar or click 'Post a WANTED.</li>
                                                                <li>Fill in the appropriate info in the various boxes.</li>
                                                                <li>Click 'Next'.</li>
                                                                <li>Fill in your email address.</li>
                                                                <li>That's it.
                                                                </ol>
                                                                <table width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                                                <tr>
                                                                                    <td width="50%" height="36" bgcolor="#377615" align="center" valign="middle"
                                                                                        style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                                            line-height:18px; border-radius:3px;">
                                                                                        <a href="{{post}}" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Freegle&nbsp;something!&nbsp;</a>
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
                                                        <tr>
                                                            <td colspan="2" style="color: grey; font-size:10px;">
                                                                <p>This mail was sent to $email.  You can change your settings by clicking <a href="$siteurl/settings">here</a>.</p>
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