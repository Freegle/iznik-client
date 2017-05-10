<?php

function digest_volunteering($volunteering) {
    $html  = '<table><tbody>';

    $html .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td colspan=3><b><span style=\"color: green\">" . htmlspecialchars($volunteering['title']) . "</span></b></td><td align=right><span style=\"color: green\">" . htmlspecialchars($volunteering['location']) . "</font></td></tr>";
    $html .= "<tr><td colspan=5>&nbsp;</td></tr>";
    $html .= "<tr><td><em>Contact details:</em><br />";

    if (pres('contactname', $volunteering)) { $html .= '&nbsp;&nbsp;' . htmlspecialchars($volunteering['contactname']) . "<br>"; }
    if (pres('contactphone', $volunteering)) { $html .= '&nbsp;&nbsp;' . htmlspecialchars($volunteering['contactphone']) . "<br>"; }
    if (pres('contactemail', $volunteering)) { $html .= '&nbsp;&nbsp;' . '<a href="mailto:' . $volunteering['contactemail'] . '">' . htmlspecialchars($volunteering['contactemail']) . "</a><br>"; }
    if (pres('contacturl', $volunteering)) { $html .= '&nbsp;&nbsp;' . '<a href="' . $volunteering['contacturl'] . '">' . htmlspecialchars($volunteering['contacturl']) . "</a><br>"; }

    $text = htmlentities($volunteering['description']);
    $text = nl2br($text);
    $html .= "</td><td>&nbsp;</td><td colspan=3>$text</td></tr>";
    $text = "Time commitment: " . htmlentities($volunteering['timecommitment']);
    $text = nl2br($text);
    $html .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td colspan=3>$text</td></tr>";

    $html .= '<tr><td colspan=5>&nbsp;</td></tr>';

    if (pres('url', $volunteering))
    {
        $html .= '<tr><td colspan=5>For more details see <a href="' . $volunteering['url'] . '">' . $volunteering['url'] . '</a>.</td></tr>';
    }

    $html .= '<tr><td colspan=5><span style="color: green"><hr></span></td></tr>';
    $html .= '</tbody></table>';

    return($html);
}