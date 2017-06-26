<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');

use LinkPreview\LinkPreview;

class Preview extends Entity
{
    /** @var  $dbhm LoggedPDO */
    public $publicatts = [ 'id', 'url', 'title', 'description', 'image', 'invalid', 'spam'];

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'link_previews', 'link', $this->publicatts);
    }

    public function create($url) {
        $id = NULL;

        if (checkSpamhaus($url)) {
            $rc = $this->dbhm->preExec("INSERT INTO link_previews(`url`, `spam`) VALUES (?,1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);", [
                $url
            ]);
        } else {
            try {
                $linkPreview = new LinkPreview($url);
                $parsed = $linkPreview->getParsed();
                $rc = NULL;

                if (count($parsed) == 0) {
                    $rc = $this->dbhm->preExec("INSERT INTO link_previews(`url`, `invalid`) VALUES (?,1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);", [
                        $url
                    ]);
                } else {
                    foreach ($parsed as $parserName => $link) {
                        $title = $link->getTitle();
                        $desc = $link->getDescription();
                        $pic = $link->getImage();
                        $rc = $this->dbhm->preExec("INSERT INTO link_previews(`url`, `title`, `description`, `image`) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);", [
                            $url,
                            $title ? $title : NULL,
                            $desc ? $desc : NULL,
                            $pic ? $pic : NULL
                        ]);

                    }
                }
            } catch (Exception $e) {
                $rc = $this->dbhm->preExec("INSERT INTO link_previews(`url`, `invalid`) VALUES (?,1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);", [
                    $url
                ]);
            }
        }

        if ($rc) {
            $id = $this->dbhm->lastInsertId();
            $this->fetch($this->dbhr, $this->dbhm, $id, 'link_previews', 'link', $this->publicatts);
        }

        return($id);
    }

    public function get($url) {
        # Doing a select first allows caching and previews DB locks.
        $links = $this->dbhr->preQuery("SELECT id FROM link_previews WHERE url = ?;", [
            $url
        ]);

        if (count($links) > 0) {
            $this->fetch($this->dbhr, $this->dbhm, $links[0]['id'], 'link_previews', 'link', $this->publicatts);
            $id = $links[0]['id'];
        } else {
            $id = $this->create($url);
        }

        return($id);
    }
}

