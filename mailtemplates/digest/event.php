<?php

function digest_event($event, $start, $end) {
    $start = date("D, jS F g:ia", strtotime($start));
    $end = date("D, jS F g:ia", strtotime($end));

    $html  = '<table><tbody>';

    $html .= "<tr><td><b>$start</b></td><td>&nbsp;</td><td><b><span style=\"color: green\">" . htmlspecialchars($event['title']) . "</span></b></td><td align=right><span style=\"color: green\">" . htmlspecialchars($event['location']) . "</font></td></tr>";
    $html .= "<tr><td width=20%>to<b> $end</b><td colspan=2></td></tr>";
    $html .= "<tr><td colspan=4>&nbsp;</td></tr>";
    $html .= "<tr><td><em>Contact details:</em><br />";

    if (pres('contactname', $event)) { $html .= '&nbsp;&nbsp;' . htmlspecialchars($event['contactname']) . "<br>"; }
    if (pres('contactphone', $event)) { $html .= '&nbsp;&nbsp;' . htmlspecialchars($event['contactphone']) . "<br>"; }
    if (pres('contactemail', $event)) { $html .= '&nbsp;&nbsp;' . '<a href="mailto:' . $event['contactemail'] . '">' . htmlspecialchars($event['contactemail']) . "</a><br>"; }

    $text = htmlentities($event['description']);
    $text = nl2br($text);
    $html .= "</td><td>&nbsp;</td><td colspan=2>$text</td></tr>";

    $html .= '<tr><td colspan=4>&nbsp;</td></tr>';

    if (pres('url', $event))
    {
        $html .= '<tr><td colspan=4>For more details see <a href="' . $event['url'] . '">' . $event['url'] . '</a>.</td></tr>';
    }

    $html .= '<tr><td colspan=4><span style="color: green"><hr></span></td></tr>';
    $html .= '</tbody></table>';
    
    return($html);
}