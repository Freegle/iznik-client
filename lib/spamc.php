<?php
/**
Class spamc
@author Micah Stevens, September 2008
@version 0.3
== BEGIN LICENSE ==

Licensed under the terms of any of the following licenses at your
choice:

- GNU General Public License Version 2 or later (the "GPL")
http://www.gnu.org/licenses/gpl.html

@copyright GPL
== END LICENSE ==


== USAGE ==

Create instance:
$filter = new spamc();
Configure client:
$filter->host = 'localhost';
$filter->user = 'myspamuser';
$filter->command = 'REPORT'

Filter data - The filter function will return true or false depending on whether the execution was successful. This does not indicate whether
the filter determined if the data was spam or not.

if (!$filter->filter($data_to_be_filtered)) {
print_r($filter->err);
} else {
print_r($filter->result);
}

Configuration Vars:
host - spamd hostname (default: localhost)
port - spamd port (default: 783)
timeout - network timeout. (default: 30seconds)
user - spamassassin user
command - type of request to make:
CHECK         --  Just check if the passed message is spam or not and reply as
described below. Filter returns true/false based on server response.

SYMBOLS       --  Check if message is spam or not, and return score plus list
of symbols hit. Filter returns true/false based on server response.

REPORT        --  Check if message is spam or not, and return score plus report
Filter returns true/false based on server response.

REPORT_IFSPAM --  Check if message is spam or not, and return score plus report
if the message is spam. Filter returns true/false based on server response.

SKIP          --  For compatibility only. Always returns true. No reponse is provided.

PING          --  Return a confirmation that spamd is alive. Filter returns
true/false based on server response. No filtering is done and no report
is provided.

PROCESS       --  Process this message as described above and return modified
message. Filter returns true/false based on server response.



If successful, result of the filter is returned in the 'result' array.

VERSION  	-- Server protocol version
RESPONSE_CODE	-- Response code. 0 is success, >0 is an error.
RESPONSE_STRING -- Response string. EX_OK is success, otherwise error string is provided.
CONTENT_LENGTH 	-- Size of data sent to server. (Only valid if command is PROCESS, otherwise 0)
REPORT		-- Report from server. This format depends on the command issue and may be empty.
SPAM		-- Bool, reports filter decision. (depends on command issued)
SCORE		-- reported spam score
MAX		-- Max spam score as configured on the server.

If unsuccessful, the 'err' variable will contain error information.


See http://spamassassin.apache.org/full/3.0.x/dist/spamd/PROTOCOL for details.

 */

class spamc
{
    public $port = 783;
    public $timeout = 30;
    public $host = 'localhost';
    public $user = '';
    public $command = 'PING';
    public $err = '';

    public $result = array('VERSION' => 0,
        'RESPONSE_CODE' => 255,
        'RESPONSE_STRING' => '',
        'CONTENT_LENGTH' => 0,
        'REPORT' => '',
        'SPAM' => false,
        'SCORE' => '',
        'MAX' => '');

    private $connection;
    private $response = array();
    private $errstr, $errno, $out;


    private function _parseHeader($line) {
        preg_match('/^(SPAMD\/)(\d*\.\d*)\s(\d*)\s(.*)/', $line, $matches);
        if ($matches[1] == 'SPAMD/') { // okay, we talked to a spamd server
            $this->result = array('VERSION'=>$matches[2],
                'RESPONSE_CODE'=>$matches[3],
                'RESPONSE_STRING'=>$matches[4]);
            return true;
        } else {
            $this->err = $this->result['RESPONSE_STRING']."(".$this->result['RESPONSE_CODE'].")";
            return false;
        }

    }
    public function filter($data)
    {

        // how long? skip if just a ping.
        if ($this->command != 'PING') {
            $size = strlen($data);
            $size = $size + 2; // have to add 2 to take care of the /r/n sent to the server.
        }

        // connect to the server.
        $fp = fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);
        if (!$fp) {
            //return array('ERROR'=>"$errstr ($errno)");
            $this->err = $this->errstr." (".$thius->errno.")";
            return false;
        }

        $this->out = $this->command." SPAMC/1.2\r\n";
        $this->out .= "Content-length: $size\r\n";
        $this->out .= "User: spamfilter\r\n";
        $this->out .= "\r\n";
        $this->out .= $data;
        $this->out .= "\r\n";

        fwrite($fp, $this->out);
        while (!feof($fp)) {
            $this->response[] = fgets($fp, 128);
        }
        fclose($fp);


        // we should have our response, so look at the first line
        $line = array_shift($this->response);

        // process header

        switch ($this->command) {
            case 'CHECK':
                $this->_parseHeader($line);
                if ($this->result['RESPONSE_CODE'] > 0) {
                    // there was an error. Report and return.
                    return false;
                    break;
                }  // no error, continue.

                // check only returns one line, so parse it and return.
                $line = array_shift($this->response);
                preg_match("/^(Spam:)\s(True|False)\s;\s(-?\d?\.?\d*)/", $line, $matches);
                if ($matches[2] == 'True') {
                    $this->result['SPAM'] = true;
                } else {
                    $this->result['SPAM'] = false;
                }
                $this->result['SCORE'] = $matches[3];
                $this->result['MAX'] = array_key_exists(4, $matches) ? $matches[4] : 1000;
                break;
            case 'SYMBOLS':
                $this->_parseHeader($line);
                if ($this->result['RESPONSE_CODE'] > 0) {
                    // there was an error. Report and return.
                    return false;
                    break;
                }  // no error, continue.

                $line = array_shift($this->response);
                preg_match('/^(Spam:)\s(True|False)\s;\s(-?\d?\.?\d*)\s\/\s(-?\d?\.?\d*)/', $line, $matches);
                if ($matches[2] == 'True') {
                    $this->result['SPAM'] = true;
                } else {
                    $this->result['SPAM'] = false;
                }
                $this->result['SCORE'] = $matches[3];
                $this->result['MAX'] = $matches[4];
                foreach($this->response as $line) {
                    $this->result['REPORT'] .= $line;
                }
                break;
            case 'REPORT':
                $this->_parseHeader($line);
                if ($this->result['RESPONSE_CODE'] > 0) {
                    // there was an error. Report and return.
                    return false;
                    break;
                }  // no error, continue.

                $line = array_shift($this->response);
                preg_match('/^(Spam:)\s(True|False)\s;\s(-?\d?\.?\d*)\s\/\s(-?\d?\.?\d*)/', $line, $matches);
                if ($matches[2] == 'True') {
                    $this->result['SPAM'] = true;
                } else {
                    $this->result['SPAM'] = false;
                }
                $this->result['SCORE'] = $matches[3];
                $this->result['MAX'] = $matches[4];
                foreach($this->response as $line) {
                    $this->result['REPORT'] .= $line;
                }
                break;
            case "REPORT_IFSPAM":
                $this->_parseHeader($line);
                if ($this->result['RESPONSE_CODE'] > 0) {
                    // there was an error. Report and return.
                    return false;
                    break;
                }  // no error, continue.

                $line = array_shift($this->response);
                preg_match('/^(Spam:)\s(True|False)\s;\s(-?\d?\.?\d*)\s\/\s(-?\d?\.?\d*)/', $line, $matches);
                if ($matches[2] == 'True') {
                    $this->result['SPAM'] = true;
                } else {
                    $this->result['SPAM'] = false;
                }
                $this->result['SCORE'] = $matches[3];
                $this->result['MAX'] = $matches[4];
                if ($this->result['SPAM'] == 'TRUE') {
                    foreach($this->response as $line) {
                        $this->result['REPORT'] .= $line;
                    }
                }
                break;
            case "PROCESS":
                $this->_parseHeader($line);
                if ($this->result['RESPONSE_CODE'] > 0) {
                    // there was an error. Report and return.
                    return false;
                    break;
                }  // no error, continue.

                $line = array_shift($this->response);
                preg_match('/^(Content-length:)\s(\d*)/', $line, $matches);
                $this->result['CONTENT_LENGTH'] = $matches[2];
                foreach($this->response as $line) {
                    $this->result['REPORT'] .= $line;
                }
                break;
            case "SKIP":
                return true;
                break;
            case "PING":
                return $this->_parseHeader($line);
                break;
            default:
                $this->err = "INVALID COMMAND\n";
                return false;
                break;
        }

        return true;
    }
}
