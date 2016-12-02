<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/IznikAPITestCase.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class eventsAPITest extends IznikAPITestCase
{
    public $dbhr, $dbhm;

    protected function setUp()
    {
        parent::setUp();

        /** @var LoggedPDO $dbhr */
        /** @var LoggedPDO $dbhm */
        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown()
    {
        error_log("Tear_down");
        parent::tearDown();
    }

    public function __construct()
    {
    }

    public function testBasic()
    {
        error_log(__METHOD__);

        $u = User::get($this->dbhr, $this->dbhm);
        $this->uid = $u->create(NULL, NULL, 'Test User');

        if (EVENTLOG) {
            $events = [
                [
                    'timestamp' => 1465293055786,
                    'route' => '/modtools/give/whereami',
                    'target' => 'window',
                    'event' => 'DOM-f',
                    'viewx' => 1920,
                    'viewy' => 995,
                    'data' => <<<EOT
            <noscript>
                &lt;h1&gt;Please enable Javascript&lt;/h1&gt;

                &lt;p&gt;We'd love to do a version
                    which was accessible to people who don't use Javascript, but we do not have the volunteer resources to do that.
                    If you'd like to help with skills or funding, please &lt;a href="mailto:edward@ehibbert.org.uk"&gt;mail us&lt;/a&gt;.&lt;/p&gt;
            </noscript>
            
            <div id="fb-root"></div>
            <div id="bodyEnvelope">
                <div id="bodyContent" class="nopad">
                </div>
            </div>
        
<iframe name="oauth2relay528127644" id="oauth2relay528127644" src="https://accounts.google.com/o/oauth2/postmessageRelay?parent=https%3A%2F%2Fiznik.ilovefreegle.org&amp;jsh=m%3B%2F_%2Fscs%2Fapps-static%2F_%2Fjs%2Fk%3Doz.gapi.en_GB.25Y7lnxKb2g.O%2Fm%3D__features__%2Fam%3DAQ%2Frt%3Dj%2Fd%3D1%2Frs%3DAGLTcCPEU9m2tlomd5HvwkQQMeNa2qSFng#rpctoken=701812476&amp;forcesecure=1" tabindex="-1" style="width: 1px; height: 1px; position: absolute; top: -100px;"></iframe>
EOT
                ]
            ];

            error_log("POST");
            $ret = $this->call('event', 'POST', [
                'events' => $events,
                'userid' => $this->uid
            ]);
            assertEquals(0, $ret['ret']);

            # Post again, which should trigger dedeplication of data.
            $this->waitBackground();

            error_log("POST 2");
            $ret = $this->call('event', 'POST', [
                'events' => $events,
                'dedup' => true
            ]);
            assertEquals(0, $ret['ret']);

            $sessid = $ret['session'];
            error_log("Got session $sessid " . var_export($ret, TRUE));

            $this->waitBackground();

            # Can't get it back without being a sysadmin.
            error_log("Get back - fail");
            $ret = $this->call('event', 'GET', [
                'sessionid' => $sessid
            ]);
            assertEquals(2, $ret['ret']);

            error_log("Get back - fail again");
            $ret = $this->call('event', 'GET', [
            ]);
            assertEquals(2, $ret['ret']);

            $this->user = User::get($this->dbhr, $this->dbhm, $this->uid);
            $this->user->addEmail('test@test.com');
            $pw = randstr(32);
            assertGreaterThan(0, $this->user->addLogin(User::LOGIN_NATIVE, NULL, $pw));
            $u->setPrivate('systemrole', User::SYSTEMROLE_SUPPORT);
            assertTrue($this->user->login($pw));

            error_log("Get back - should work");
            $ret = $this->call('event', 'GET', [
                'sessionid' => $sessid
            ]);
            assertEquals(0, $ret['ret']);
            assertEquals(2, count($ret['events']));
            assertEquals($ret['events'][0]['data'], $ret['events'][1]['data']);

            error_log("Get back - should work again");
            $ret = $this->call('event', 'GET', [
            ]);
            error_log("Get returned " . count($ret['sessions']));

            #error_log(var_export($ret, TRUE));
            assertEquals(0, $ret['ret']);
            assertGreaterThan(1, count($ret['sessions']));
            $found = FALSE;

            foreach ($ret['sessions'] as $session) {
                if ($session['id'] == $sessid) {
                    $found = TRUE;
                }
            }

            assertTrue($found);

            # Now get via userid
            $ret = $this->call('event', 'GET', [
                'userid' => $this->uid
            ]);
            error_log("Get returned " . count($ret['sessions']));

            #error_log(var_export($ret, TRUE));
            assertEquals(0, $ret['ret']);
            assertEquals(1, count($ret['sessions']));
        }

        error_log(__METHOD__ . " end");
    }
}
