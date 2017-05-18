<?php
require_once(IZNIK_BASE . '/mailtemplates/header.php');
require_once(IZNIK_BASE . '/mailtemplates/footer.php');

function story_newsletter() {
    $siteurl = "https://" . USER_SITE . "?src=storynewsletter";
    $imgurl = "https://" . USER_SITE . "/images/story.png";
    $storyurl = "https://" . USER_SITE . "/stories" . "?src=storynewsletter";
    $give = "https://" . USER_SITE . '/give' . "?src=storynewsletter";
    $find = "https://" . USER_SITE . '/find' . "?src=storynewsletter";

    $html = <<<EOT
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
                    <tr>
                        <td align="center">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container">
                                <tbody>
                                    <tr>
                                        <td colspan="2">
                                            <table width="90%" cellpadding="00" cellspacing="0" align="left" border="0">
                                                <tr>
                                                    <td>                                                           
                                                        <a href="$siteurl">
                                                            <img src="$imgurl?m=0" class="imgClass" />
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>               
                                        </td>    
                                    <tr>
                                        <td colspan="2" class="mobile" align="left" valign="top">
                                            <h2>We love your stories!</h2>
                                            <p>It's great to hear why people freegle - and here are some recent tales from other freeglers.</p>
                                            <p>Be inspired - tell us your story, or get freegling!</p>
                                        </td>                                       
                                    </tr>    
                                    <tr>
                                        <td colspan="2" class="mobile" align="left" valign="top">
                                            <table width="100%">
                                                <tr>
                                                    <td width="150">
                                                        <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                            <tr>
                                                                <td width="90%" height="36" bgcolor="#00A1CB" align="center" valign="middle"
                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                        line-height:18px; border-radius:3px;">
                                                                    <a href="$storyurl" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">Tell us your story!</a>
                                                                </td>
                                                            </tr>
                                                        </table>               
                                                    </td>    
                                                    <td width="150">
                                                        <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                            <tr>
                                                                <td width="90%" height="36" bgcolor="#61AE24" align="center" valign="middle"
                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                        line-height:18px; border-radius:3px;">
                                                                    <a href="$give" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Give&nbsp;something&nbsp;</a>
                                                                </td>
                                                            </tr>
                                                        </table>               
                                                    </td>    
                                                    <td width="150">
                                                        <table class="button" width="90%" cellpadding="0" cellspacing="0" align="left" border="0">
                                                            <tr>
                                                                <td width="90%" height="36" bgcolor="#61AE24" align="center" valign="middle"
                                                                    style="font-family: Century Gothic, Arial, sans-serif; font-size: 16px; color: #ffffff;
                                                                        line-height:18px; border-radius:3px;">
                                                                    <a href="$find" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Find&nbsp;something&nbsp;</a>
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
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
EOT;

    return($html);
}
