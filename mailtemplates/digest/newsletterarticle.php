<?php

function newsletter_article($article) {
    $html = '';
    if ($article['type'] == Newsletter::TYPE_HEADER) {
        $html = <<<EOT
    <table width="100%">
        <tr>
EOT;
        if (pres('photo', $article)) {
            $html .= <<<EOT
            <td width="100%">
                <img style="border-radius:3px; margin:0; padding:0; border:none; display:block; width: 100%;" src="{$article['photo']['path']}" />
            </td>
         </tr>
         <tr>   
            <td width="100%">
                {$article['html']}
            </tr>
EOT;
        } else {
            $html .= <<<EOT
            <td width="100%">
                {$article['html']}
            </tr>            
EOT;
        }

        $html .= <<<EOT
        </tr>        
        <tr>
            <td colspan="2">
                <font color=gray><hr></font>
            </td>
        </tr>        
    </table>
EOT;
    } else {
            $html = <<<EOT
    <table width="95%">
        <tr>
EOT;

            if (pres('photo', $article)) {
                $html .= <<<EOT
                <td width="30%">
                    <img style="border-radius:3px; margin:0; padding:0; border:none; display:block;" width="250" src="{$article['photo']['path']}" />
                </td>
                <td width="70%">
                    <span style="color: green">
                    {$article['html']}
                    </span>
                </td>
EOT;
            } else {
                $html .= <<<EOT
                <td width="100%">
                    <span style="color: green">
                    {$article['html']}
                    </span>
                </td>
EOT;
            }

            $html .= <<<EOT
        </tr>
        <tr>
            <td colspan="2">
                <font color=gray><hr></font>
            </td>
        </tr>        
    </table>
EOT;
    }

    return($html);
}