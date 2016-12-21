<?php

require_once(IZNIK_BASE . '/include/utils.php');
require_once(IZNIK_BASE . '/include/misc/Entity.php');
require_once(IZNIK_BASE . '/include/misc/Search.php');

class Item extends Entity
{
    /** @var  $dbhm LoggedPDO */
    var $publicatts = array('id', 'name', 'popularity', 'weight', 'updated', 'suggestfromphoto', 'suggestfromtypeahead');
    var $settableatts = array('name', 'popularity', 'weight');

    /** @var  $log Log */
    private $log;

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL)
    {
        $this->fetch($dbhr, $dbhm, $id, 'items', 'item', $this->publicatts);
        $this->s = new Search($dbhr, $dbhm, 'items_index', 'itemid', 'popularity', 'words', 'categoryid', NULL);
    }

    /**
     * @param LoggedPDO $dbhm
     */
    public function setDbhm($dbhm)
    {
        $this->dbhm = $dbhm;
    }

    public function create($name) {
        try {
            $rc = $this->dbhm->preExec("INSERT INTO items (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);", [ $name ]);
            $id = $this->dbhm->lastInsertId();
        } catch (Exception $e) {
            $id = NULL;
            $rc = 0;
        }

        if ($rc && $id) {
            $this->fetch($this->dbhr, $this->dbhm, $id, 'items', 'item', $this->publicatts);

            # Add into the search index.
            $this->index();
            return($id);
        } else {
            return(NULL);
        }
    }

    public function index() {
        $this->s->delete($this->id);
        $this->s->add($this->id, $this->item['name'], $this->item['popularity'], NULL);
    }

    public function typeahead($query) {
        $ctx = NULL;
        $results = $this->s->search($query, $ctx, 10);
        foreach ($results as &$result) {
            $i = new Item($this->dbhr, $this->dbhm, $result['id']);

            if ($i->getPrivate('suggestfromtypeahead')) {
                $result['item'] = $i->getPublic();
            }
        }
        return($results);
    }

    public function getWeightless($userid) {
        $sql = "SELECT items.id FROM items LEFT JOIN items_weights ON items.id = items_weights.itemid AND items_weights.userid = ? WHERE (items.weight IS NULL OR items.weight = 0) AND userid IS NULL ORDER BY popularity DESC LIMIT 1;";
        $items = $this->dbhr->preQuery($sql, [
            $userid,
        ]);

        $id = count($items) == 1 ? $items[0]['id'] : NULL;

        return($id);
    }

    public function setWeight($userid, $weight) {
        if ($this->id) {
            $this->dbhm->preExec("REPLACE INTO items_weights (userid, itemid, weight) VALUES (?, ?, ?);", [
                $userid,
                $this->id,
                $weight
            ]);
        }
    }

    public function findFromPhoto($query) {
        $items = $this->dbhr->preQuery("SELECT * FROM items WHERE name = ? AND suggestfromphoto = 1 ORDER BY popularity DESC limit 1;", [ $query ]);
        return($items);
    }

    public function delete() {
        # Remove from the search index.
        $this->s->delete($this->id);

        $rc = $this->dbhm->preExec("DELETE FROM items WHERE id = ?;", [$this->id]);

        return($rc);
    }
}