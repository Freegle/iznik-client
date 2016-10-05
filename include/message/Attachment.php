<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Log.php');
require_once(IZNIK_BASE . '/include/message/Item.php');

use Jenssegers\ImageHash\ImageHash;

# This is a base class
class Attachment
{
    /** @var  $dbhr LoggedPDO */
    private $dbhr;
    /** @var  $dbhm LoggedPDO */
    private $dbhm;
    private $id, $table, $contentType, $hash;

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    
    const TYPE_MESSAGE = 'Message';
    const TYPE_GROUP = 'Group';
    const TYPE_NEWSLETTER = 'Newsletter';

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    public static function getPath($id, $type = Attachment::TYPE_MESSAGE, $thumb = FALSE) {
        # We serve up our attachment names as though they are files.
        # When these are fetched it will go through image.php
        switch ($type) {
            case Attachment::TYPE_MESSAGE: $name = 'img'; break;
            case Attachment::TYPE_GROUP: $name = 'gimg'; break;
            case Attachment::TYPE_NEWSLETTER: $name = 'nimg'; break;
        }

        $name = $thumb ? "t$name" : $name;

        return("https://" . IMAGE_DOMAIN . "/{$name}_$id.jpg");
    }

    public function getPublic() {
        $ret = array(
            'id' => $this->id,
            'hash' => $this->hash
        );

        if (stripos($this->contentType, 'image') !== FALSE) {
            # It's an image.  That's the only type we support.
            $ret['path'] = Attachment::getPath($this->id, $this->type);
            $ret['paththumb'] = Attachment::getPath($this->id, $this->type, TRUE);
        }

        return($ret);
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL, $type = Attachment::TYPE_MESSAGE)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->id = $id;
        $this->type = $type;
        
        switch ($type) {
            case Attachment::TYPE_MESSAGE: $this->table = 'messages_attachments'; $this->idatt = 'msgid'; break;
            case Attachment::TYPE_GROUP: $this->table = 'groups_images'; $this->idatt = 'groupid'; break;
            case Attachment::TYPE_NEWSLETTER: $this->table = 'newsletters_images'; $this->idatt = 'articleid'; break;
        }

        if ($id) {
            $sql = "SELECT contenttype, hash FROM {$this->table} WHERE id = ?;";
            $atts = $this->dbhr->preQuery($sql, [$id]);
            foreach ($atts as $att) {
                $this->contentType = $att['contenttype'];
                $this->hash = $att['hash'];
            }
        }
    }

    public function create($id, $ct, $data) {
        # We generate a perceptual hash.  This allows us to spot duplicate or similar images later.
        $hasher = new ImageHash;
        $img = @imagecreatefromstring($data);
        $hash = $img ? $hasher->hash($img) : NULL;

        $rc = $this->dbhm->preExec("INSERT INTO {$this->table} (`{$this->idatt}`, `contenttype`, `data`, `hash`) VALUES (?, ?, ?, ?);", [
            $id,
            $ct,
            $data,
            $hash
        ]);

        $imgid = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($imgid) {
            $this->id = $imgid;
            $this->contentType = $ct;
        }

        return($imgid);
    }

    public function getById($id) {
        $sql = "SELECT id FROM {$this->table} WHERE {$this->idatt} = ? AND (data IS NOT NULL OR archived = 1) ORDER BY id;";
        $atts = $this->dbhr->preQuery($sql, [$id]);
        $ret = [];
        foreach ($atts as $att) {
            $ret[] = new Attachment($this->dbhr, $this->dbhm, $att['id']);
        }

        return($ret);
    }

    public function archive() {
        $rc = file_put_contents(IZNIK_BASE . "/http/attachments/img_{$this->id}.jpg", $this->getData());
        error_log("$rc for img_{$this->id}.jpg");
        if ($rc) {
            $sql = "UPDATE messages_attachments SET archived = 1, data = NULL WHERE id = {$this->id};";
            $this->dbhm->exec($sql);
        }

        return($rc);
    }

    public function getData() {
        $ret = NULL;

        # Use dbhm to bypass query cache as this data is too large to cache.
        $sql = "SELECT * FROM {$this->table} WHERE id = ?;";
        $datas = $this->dbhm->preQuery($sql, [$this->id]);
        foreach ($datas as $data) {
            if ($data['archived']) {
                # This attachment has been archived out of our database, to our archive host.  This happens to
                # older attachments to save space in the DB.
                #
                # We fetch the data - not using SSL as we don't need to, and that host might not have a cert.  And
                # we put it back in the DB, because we are probably going to fetch it again.
                $ret = @file_get_contents('http://' . IMAGE_ARCHIVED_DOMAIN . "/img_{$this->id}.jpg");
                $this->dbhm->preExec("UPDATE {$this->table} SET data = ?, archived = 0 WHERE id = ?;", [
                    $ret,
                    $this->id
                ]);
                error_log("Dearchived {$this->id}");
            } else {
                $ret = $data['data'];
            }
        }

        return($ret);
    }

    public function identify() {
        # Identify objects in an attachment using Google Vision API.  Only for messages.
        $items = [];
        if ($this->type == Attachment::TYPE_MESSAGE) {
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

            if ($status) {
                $this->dbhm->preExec("UPDATE messages_attachments SET identification = ? WHERE id = ?;", [ $json_response, $this->id ]);
                $rsp = json_decode($json_response, TRUE);
                #error_log("Identified {$this->id} by Google $json_response for $r_json");

                if (array_key_exists('responses', $rsp) && count($rsp['responses']) > 0 && array_key_exists('labelAnnotations', $rsp['responses'][0])) {
                    $rsps = $rsp['responses'][0]['labelAnnotations'];
                    $i = new Item($this->dbhr, $this->dbhm);

                    foreach ($rsps as $rsp) {
                        $found = $i->findFromPhoto($rsp['description']);
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
        }

        return($items);
    }

    public function setPrivate($att, $val) {
        $rc = $this->dbhm->preExec("UPDATE {$this->table} SET `$att` = ? WHERE id = {$this->id};", [$val]);
    }
}