<?php

function digest_message($msg, $msgid, $fdgroupid) {
    $text = htmlentities($msg['textbody']);
    $text = nl2br($text);
    $date = date("D, jS F g:ha");
    $replyweb = "https://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaypost&msgid=$msgid&groupid=$fdgroupid&digest=$fdgroupid";
    $replyemail = "mailto:{$msg['fromaddr']}?subject=" . urlencode("Re: " . $msg['subject']);

    $html = <<<EOT
    <table width="100%">
        <tr>
            <td colspan="2">
                <p>
                    <a href="https://direct.ilovefreegle.org/login.php?action=mygroups&subaction=displaypost&msgid=$msgid&groupid=$fdgroupid&digest=$fdgroupid">{$msg['subject']}</a>
                </p>                
            </td>
        </tr>    
        <tr>
            <td width="75%">
                $text<br /><br />
                <span style="color: gray">Posted by {$msg['fromname']} on $date.</span> 

            </td>
            <td width="25%">
EOT;
    if (count($msg['attachments']) > 0) {
        # Just put the first one in.
        $html .= '<img width="125px" style="border-radius:3px; margin:0; padding:0; border:none; display:block;" src="' . $msg['attachments'][0]['path'] . '" />';
    }

    $html .= <<<EOT
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table class="button" width="200" cellpadding="0" cellspacing="10" align="left" border="0">
                    <tr>
                        <td width="50%" height="24" bgcolor="#377615" align="center" valign="middle"
                            style="font-family: Century Gothic, Arial, sans-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href="$replyweb" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Reply&nbsp;via&nbsp;Web&nbsp;</a>
                        </td>
                        <td width="50%" height="24" bgcolor="#377615" align="center" valign="middle"
                            style="font-family: Century Gothic, Arial, sans-serif; font-size: 12px; color: #ffffff;
                                line-height:18px; border-radius:3px;">
                            <a href="$replyemail" target="_blank" alias="" style="font-family: Century Gothic, Arial, sans-serif; text-decoration: none; color: #ffffff;">&nbsp;Reply&nbsp;via&nbsp;Email&nbsp;</a>
                        </td>
                    </tr>
                </table>           
            </td>
        </tr>
    </table>
EOT;
    
    return($html);
}