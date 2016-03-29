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
            $ret['path'] = "https://" . IMAGE_DOMAIN . $this->getPath();
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
        #error_log("Create att for $msgid len " . strlen($data));
        $rc = $this->dbhm->preExec("INSERT INTO messages_attachments (`msgid`, `contenttype`, `data`) VALUES (?, ?, ?);", [
            $msgid,
            $ct,
            $data
        ]);

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($id) {
            $this->id = $id;
            $this->contentType = $ct;
        }

        return($id);
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
        $data = $this->getData();
        $base64 = base64_encode($data);

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
            $this->dbhm->preExec("UPDATE messages_attachments SET identification = ? WHERE id = ?;", [ $json_response, $this->id ]);
            $rsp = json_decode($json_response, TRUE);
            #error_log("Identified {$this->id} by Google $json_response for $r_json");

            if (array_key_exists('responses', $rsp) && count($rsp['responses']) > 0 && array_key_exists('labelAnnotations', $rsp['responses'][0])) {
                $rsps = $rsp['responses'][0]['labelAnnotations'];
                $i = new Item($this->dbhr, $this->dbhm);

                foreach ($rsps as $rsp) {
                    $found = $i->find($rsp['description']);
                    $wasfound = FALSE;
                    foreach ($found as $item) {
                        $this->dbhm->background("INSERT INTO messages_attachments_items (attid, itemid) VALUES ({$this->id}, {$item['id']});");
                        $wasfound = TRUE;
                    }

                    if (!$wasfound) {
                        # Record items which were suggested but not considered as items by us.  This allows us to find common items which we ought to
                        # add.
                        #
                        # This is usually because they're too vague.
                        $url = "https://" . IMAGE_DOMAIN . "/img_{$this->id}.jpg";
                        $this->dbhm->background("INSERT INTO items_non (name, lastexample) VALUES (" . $this->dbhm->quote($rsp['description']) . ", " . $this->dbhm->quote($url) . ") ON DUPLICATE KEY UPDATE popularity = popularity + 1, lastexample = " . $this->dbhm->quote($url) . ";");
                    }

                    $items = array_merge($items, $found);
                }
            }
       }

        curl_close($curl);

        return($items);
    }
}