<?php

# This class does searching for strings containing multiple words.  The matching includes typos and
# phonetic similarities.
#
# The tables must already exist.

class Search
{
    CONST Depth = 200;
    CONST WeightExact = 30;
    CONST WeightStartsWith = 10;
    CONST WeightSoundsLike = 7;
    CONST WeightTypo = 3;
    CONST Comfort = 100;
    CONST Limit = 10;
    private $dbhr;
    private $dbhm;
    private $table;
    private $idatt;
    private $sortatt;
    private $wordtab;

    # Common words to remove before indexing, because they are so generic that they
    # wouldn't be useful in indexing.  This reduces the index size .

    private $common = array(
        'the', 'old', 'new', 'please', 'thanks', 'with', 'offer', 'taken', 'wanted', 'received', 'attachment', 'offered', 'and',
        'freegle', 'freecycle', 'for'
    );

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $table, $idatt, $sortatt, $wordtab, $filtatt)
    {
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
        $this->table = $table;
        $this->idatt = $idatt;
        $this->sortatt = $sortatt;
        $this->wordtab = $wordtab;
        $this->filtatt = $filtatt;
    }

    private function getWords($string)
    {
        # Remove all punctuation
        $string = preg_replace('/[^a-z0-9]+/i', ' ', strtolower($string));

        # Get words
        $words = preg_split('/\s+/', $string);

        # Filter
        $words = array_diff($words, $this->common);

        $ret = [];

        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $ret[] = $word;
            }
        }

        return($ret);
    }

    private function getWordIdExact($word) {
        $id = NULL;
        $sql = "SELECT id from {$this->wordtab} WHERE `word` = ?;";
        $words = $this->dbhr->preQuery($sql, [
            $word
        ]);

        foreach ($words as $aword) {
            $id = $aword['id'];
        }

        if (!$id) {
            # Not found - add it
            $sql = "INSERT INTO {$this->wordtab} (`word`, `firstthree`, `soundex`) VALUES (?,?, SUBSTRING(SOUNDEX(?), 1, 10));";
            $rc = $this->dbhm->preExec($sql, [
                $word,
                substr($word, 0, 3),
                $word
            ]);
            if ($rc) {
                $id = $this->dbhm->lastInsertId();
            }
        }

        # Failed to insert.
        return $id;
    }

    private function getWordsExact($word, $limit) {
        # We do this rather than a subquery because we can't apply a limit in a subquery, and the workarounds for doing
        # so are too complex for maintenance.
        $sql = "SELECT id FROM {$this->wordtab} WHERE `word` LIKE ? ORDER BY popularity LIMIT $limit;";
        $res = array();
        $ids = $this->dbhr->preQuery($sql, [
            $word
        ]);
        foreach ($ids as $id) {
            $res[] = $id['id'];
        }
        return(count($res) > 0 ? implode(',', $res) : '0');
    }

    private function getWordsStartsWith($word, $limit) {
        $sql = "SELECT id FROM {$this->wordtab} WHERE `word` LIKE ? ORDER BY popularity LIMIT $limit;";
        $res = array();
        $ids = $this->dbhr->preQuery($sql, [
            $word . '%'
        ]);
        foreach ($ids as $id) {
            $res[] = $id['id'];
        }
        return(count($res) > 0 ? implode(',', $res) : '0');
    }

    private function getWordsSoundsLike($word, $limit) {
        $sql = "SELECT id FROM {$this->wordtab} WHERE `soundex` = SUBSTRING(SOUNDEX(?), 1, 10) ORDER BY popularity LIMIT $limit;";
        $res = array();
        $ids = $this->dbhr->preQuery($sql, [
            $word
        ]);
        foreach ($ids as $id) {
            $res[] = $id['id'];
        }
        return(count($res) > 0 ? implode(',', $res) : '0');
    }

    private function getWordsTypo($word, $limit) {
        # We assume they got the first letter right.  This is purely to speed up this calculation and stop it scanning
        # as many rows.
        $sql = "SELECT id FROM {$this->wordtab} WHERE `word` LIKE ? AND damlevlim(`word`, ?, " . strlen($word) . ") < 2 ORDER BY popularity DESC LIMIT $limit;";
        $res = array();
        $ids = $this->dbhr->preQuery($sql,
            [
                substr($word, 0, 1) . '%',
                $word
            ]);
        foreach ($ids as $id) {
            $res[] = $id['id'];
        }
        return(count($res) > 0 ? implode(',', $res) : '0');
    }

    public function add($extid, $string, $sortval, $filtval)
    {
        $words = $this->getWords($string);

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $id = $this->getWordIdExact($word);
                if ($id) {
                    # We use a - value because MySQL doesn't support DESC indexing.
                    $sql = "INSERT IGNORE INTO {$this->table} (`{$this->idatt}`, `wordid`, `{$this->sortatt}`, `{$this->filtatt}`) VALUES (?,?,?,?);";
                    $rc = $this->dbhm->preExec($sql, [
                        $extid,
                        $id,
                        -$sortval,
                        $filtval
                    ]);

                    $sql = "UPDATE {$this->wordtab} SET popularity = -(SELECT COUNT(*) FROM {$this->table} WHERE `wordid` = ?) WHERE `id` = ?;";
                    $rc = $this->dbhm->preExec($sql, [
                        $id,
                        $id
                    ]);
                }
            }
        }
    }

    private function getWord($id, $tag) {
        $words = $this->dbhr->preQuery("SELECT * FROM words WHERE id = ?;", [ $id ]);
        return([
            'id' => $words[0]['id'],
            'word' => $words[0]['word'],
            'type' => $tag
        ]);
    }

    private function processResults(&$results, $batch, $word, $tag, $weight)
    {
        $added = 0;
        foreach ($batch as $result) {
            if (!array_key_exists($result[$this->idatt], $results)) {
                $results[$result[$this->idatt]] = [
                    'count' => $weight,
                    'searchword' => $word,
                    'items' => [
                        [
                            'item' => $result,
                            'tag' => $tag,
                            'matchedon' => $this->getWord($result['wordid'], $tag)
                        ]
                    ]
                ];
                $added++;
            } else if ($word != $results[$result[$this->idatt]]['searchword']) {
                # If we have encountered the same result for a different word, it counts extra.
                $results[$result[$this->idatt]]['items'][] = [
                    'item' => $result,
                    'tag' => $tag,
                    'matchedon' => $this->getWord($result['wordid'], $tag)
                ];
                $results[$result[$this->idatt]]['count'] += $weight;
            }
        }
    }

    public function search($string, &$context, $limit = Search::Limit, $restrict = NULL, $filts = NULL)
    {
        if (empty($restrict)) {
            $exclude = NULL;
        }

        if ($restrict) {
            $exclfilt = " AND {$this->idatt} IN (" . implode(',', $restrict) . ") ";
        } else {
            $exclfilt = '';
        }

        if ($filts) {
            foreach ($filts as &$filt) {
                $filt = $this->dbhr->quote($filt);
            }

            $filtfilt = " AND {$this->filtatt} IN (" . implode(',', $filts) . ") ";
        } else {
            $filtfilt = "";
        }

        # We get search results from different ways of searching.  That means we need to return a context that
        # tracks where we got to on the different sources of info.
        $words = $this->getWords($string);

        $results = array();

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                # Check for exact matches even for short words
                $startq = pres('Exact', $context) ? " AND {$this->idatt} > {$context['Exact']} " : "";
                $sql = "SELECT DISTINCT {$this->idatt}, {$this->sortatt}, wordid FROM {$this->table} WHERE `wordid` IN (" . $this->getWordsExact($word, $limit * Search::Depth) . ") $exclfilt $startq $filtfilt ORDER BY ?,? LIMIT " . $limit * Search::Depth . ";";
                #error_log(" $sql  {$this->sortatt} {$this->idatt}");
                $batch = $this->dbhr->preQuery($sql, [
                    $this->sortatt,
                    $this->idatt
                ]);
                $this->processResults($results, $batch, $word, 'Exact', Search::WeightExact);
            }

            if (strlen($word) >= 2) {
                # Check for starts matches with two characters
                $startq = pres('StartsWith', $context) ? " AND {$this->idatt} > {$context['StartsWith']} " : "";
                $sql = "SELECT DISTINCT {$this->idatt}, {$this->sortatt}, wordid FROM {$this->table} WHERE `wordid` IN (" . $this->getWordsStartsWith($word, $limit * Search::Depth) . ") $exclfilt $startq $filtfilt ORDER BY ?,? LIMIT " . $limit * Search::Depth . ";";
                $batch = $this->dbhr->preQuery($sql, [
                    $this->sortatt,
                    $this->idatt
                ]);
                $this->processResults($results, $batch, $word, 'StartsWith', Search::WeightStartsWith);
            }

            if (strlen($word) > 2) {
                # Ignore short words.  We search for two other kinds of word matches
                # - a sounds like
                # - a distance using
                #
                # Add in sounds like.  We add these in because it's quick, and some of the extra matches might be
                # better matches overall than some of the ones we already have.
                $startq = pres('SoundsLike', $context) ? " AND {$this->idatt} > {$context['SoundsLike']} " : "";
                $sql = "SELECT DISTINCT {$this->idatt}, {$this->sortatt}, wordid FROM {$this->table} WHERE `wordid` IN (" . $this->getWordsSoundsLike($word, $limit * Search::Depth) . ") $exclfilt $startq $filtfilt ORDER BY ?,? LIMIT " . $limit * Search::Depth . ";";
                $batch = $this->dbhr->preQuery($sql, [
                    $this->sortatt,
                    $this->idatt
                ]);
                $this->processResults($results, $batch, $word, 'SoundsLike', Search::WeightSoundsLike);

                if (count($results) < $limit * Search::Comfort) {
                    # We still didn't find enough to be comfortable that we have a decent set of matches.
                    # Search for typos.  This is slow, so we need to stick a limit on it.
                    $startq = pres('Typo', $context) ? " AND {$this->idatt} > {$context['Typo']} " : "";
                    $sql = "SELECT DISTINCT {$this->idatt}, {$this->sortatt}, wordid FROM {$this->table} WHERE `wordid` IN (" . $this->getWordsTypo($word, $limit * Search::Depth) . ") $exclfilt $startq $filtfilt ORDER BY ?,? LIMIT " . $limit * Search::Depth . ";";
                    $batch = $this->dbhr->preQuery($sql, [
                        $this->sortatt,
                        $this->idatt
                    ]);
                    $this->processResults($results, $batch, $word, 'Typo', Search::WeightTypo);
                }
            }
        }

        # We now have an array of ids and the number of words they matched and the
        # attribute to sort on.  We want to sort by the weighted count of matched words,
        # and within that the external sort attribute.
        uasort($results, function ($a, $b) {
            if ($a['count'] == $b['count']) {
                # Same weighted count; sort descending by sort att
                return (intval($a['items'][0]['item'][$this->sortatt]) < intval($b['items'][0]['item'][$this->sortatt]) ? 1 : -1);
            } else {
                # Sort by count.
                return($b['count'] - $a['count']);
            }
        });

        # Now apply the limit.
        $ret = array();
        $count = 0;
        $retcont = $context;

        while ($count < $limit && count($results) > 0) {
            $key = key($results);

            # Find max value of the id att to return in the context.
            $thislot = $results[$key]['items'];
            $ret[] = [
                'id' => $key,
                'matchedon' => $thislot[0]['matchedon']
            ];

            foreach ($thislot as $thisone) {
                $results[$key][] = $thisone['item'];

                $retcont[$thisone['tag']] = pres($thisone['tag'], $retcont) ?
                    max($retcont[$thisone['tag']], $thisone['item'][$this->idatt]) : $thisone['item'][$this->idatt];
            }

            unset($results[$key]);
            $count++;
        }

        $context = $retcont;

        return ($ret);
    }

    public function delete($extid)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->idatt} = $extid;";
        $this->dbhm->exec($sql);
    }
}