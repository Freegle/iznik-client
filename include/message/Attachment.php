<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/message/Item.php');

# This is a base class
class Attachment
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id, $contentType;

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    public function getPath() {
        # We serve up our attachment names as though they are files.
        # When these are fetched it will go through image.php
        return("/img_{$this->id}.jpg");
    }

    public function getPublic() {
        $ret = array(
            'id' => $this->id
        );

        if (stripos($this->contentType, 'image') !== FALSE) {
            # It's an image.  That's the only type we support.
            $ret['path'] = $this->getPath();
        }

        return($ret);
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->id = $id;

        if ($id) {
            $sql = "SELECT contenttype FROM messages_attachments WHERE id = ?;";
            $atts = $this->dbhr->preQuery($sql, [$id]);
            foreach ($atts as $att) {
                $this->contentType = $att['contenttype'];
            }
        }
    }

    public function create($msgid, $ct, $data) {
        $rc = $this->dbhm->preExec("INSERT INTO messages_attachments (`msgid`, `contenttype`, `data`) VALUES (?, ?, ?);", [
            $msgid,
            $ct,
            $data
        ]);

        return($rc ? $this->dbhm->lastInsertId() : NULL);
    }

    public static function getById($dbhr, $dbhm, $id) {
        $sql = "SELECT id FROM messages_attachments WHERE msgid = ? ORDER BY id;";
        $atts = $dbhr->preQuery($sql, [$id]);
        $ret = [];
        foreach ($atts as $att) {
            $ret[] = new Attachment($dbhr, $dbhm, $att['id']);
        }

        return($ret);
    }

    public function getData() {
        $ret = NULL;

        $sql = "SELECT data FROM messages_attachments WHERE id = ?;";
        $datas = $this->dbhr->preQuery($sql, [$this->id]);
        foreach ($datas as $data) {
            $ret = $data['data'];
        }

        return($ret);
    }

    public function identify() {
        # Identify objects in an attachment using Google Vision API.
        $base64 = base64_encode($this->getData());

        $r_json ='{
			  	"requests": [
					{
					  "image": {
					    "content":"' . $base64. '"
					  },
					  "features": [
					      {
					      	"type": "LABEL_DETECTION",
							"maxResults": 20
					      }
					  ]
					}
				]
			}';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://vision.googleapis.com/v1/images:annotate?key=' . GOOGLE_VISION_KEY);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $r_json);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $items = [];

        if ($status) {
            $rsp = json_decode($json_response, TRUE);
            $rsps = $rsp['responses'][0]['labelAnnotations'];
            $i = new Item($this->dbhr, $this->dbhm);

            foreach ($rsps as $rsp) {
                $items = array_merge($items, $i->find($rsp['description']));
            }
        }

        curl_close($curl);

        return($items);
    }
}