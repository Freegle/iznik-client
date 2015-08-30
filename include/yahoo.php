<?php

require_once("/etc/iznik.conf");
require_once(BASE_DIR . '/lib/openid.php');

class Yahoo
{
    private $dbhr;
    private $dbhm;

    function __construct(PDO $dbhr, PDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        return ($this);
    }

    function login()
    {
        $loginurl = "https://{$_SERVER['HTTP_HOST']}/yahoologin";
        $openid = new LightOpenID;
        $openid->realm = "https://{$_SERVER['HTTP_HOST']}";
        $openid->returnUrl = $loginurl;

        try
        {
            #error_log("Validate " . var_export($_REQUEST, true));
            if (($openid->validate()) && ($openid->identity != 'https://open.login.yahooapis.com/openid20/user_profile/xrds'))
            {
                // We are logged in.  Store session information
                #error_log("We are logged in with Yahoo");
                $_SESSION['loggedin'] = true;
                $_SESSION['openidid'] = htmlspecialchars($openid->identity);
                $_SESSION['openidattr'] = $openid->getAttributes();
            } else if (!$openid->mode) {
                #error_log("Not logged in with Yahoo");
                $_SESSION['sesstype'] = 'Yahoo';
                $openid->identity = 'https://me.yahoo.com';
                $openid->required = array('contact/email', 'namePerson', 'namePerson/first', 'namePerson/last');
                $url = $openid->authUrl() . "&key=Iznik";
                return array(NULL, array('ret' => 1, 'redirect' => $url), true, false);
            } else {
                #error_log("Dunno where we are with Yahoo");
            }
        }
        catch (Exception $e)
        {
            var_dump($e);
        }

        return (NULL);
    }
}