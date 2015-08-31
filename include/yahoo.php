<?php

require_once("/etc/iznik.conf");
require_once(IZNIK_BASE . '/lib/openid.php');

class Yahoo
{
    private $dbhr;
    private $dbhm;

    /** @var  $openid LightOpenID */
    private $openid;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->openid = new LightOpenID;
        $this->openid->realm = "https://{$_SERVER['HTTP_HOST']}";
        $loginurl = "https://{$_SERVER['HTTP_HOST']}/yahoologin";
        $this->openid->returnUrl = $loginurl;
        
        return ($this);
    }
    
    /**
     * @param mixed $openid
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;
    }

    function login()
    {
        try
        {
            #error_log("Validate " . var_export($_REQUEST, true));
            if (($this->openid->validate()) && ($this->openid->identity != 'https://open.login.yahooapis.com/openid20/user_profile/xrds'))
            {
                // We are logged in.  Store session information
                #error_log("We are logged in with Yahoo");
                $_SESSION['loggedin'] = true;
                $_SESSION['openidid'] = htmlspecialchars($this->openid->identity);
                $_SESSION['openidattr'] = $this->openid->getAttributes();
            } else if (!$this->openid->mode) {
                #error_log("Not logged in with Yahoo");
                $_SESSION['sesstype'] = 'Yahoo';
                $this->openid->identity = 'https://me.yahoo.com';
                $this->openid->required = array('contact/email', 'namePerson', 'namePerson/first', 'namePerson/last');
                $url = $this->openid->authUrl() . "&key=Iznik";
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