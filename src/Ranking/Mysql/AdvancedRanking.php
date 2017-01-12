<?php

namespace Ranking\Mysql;

use Ranking\RankInterface;
use Ranking\Mysql\SimpleRanking;

/**
 * @author Ioannis Botis
 * @date 30/8/2016
 * @version: AdvancedRanking.php 2:48 pm
 * @since 30/8/2016
 */
class AdvancedRanking extends SimpleRanking
{
    private $_additional_where;

    public function excludeByColumn($column, $value, $op = '=')
    {
        $this->_additional_where = "`$column` " . $op . " '" . $this->getMySqlConnection()->real_escape_string($value) . "'";
    }

    public function getRank(RankInterface $rankModel)
    {
        if (!isset($this->_additional_where)) {
            return parent::getRank($rankModel);
        }
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " . $this->getScore($rankModel) .
            " AND " . $this->_additional_where;

        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $res->fetch_assoc();
        return $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        if (!isset($this->_additional_where)) {
            return parent::getRowsAtRank($rank, $total);
        }
        $rank = intval($this->getMySqlConnection()->real_escape_string($rank));
        $total = intval($this->getMySqlConnection()->real_escape_string($total));
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_additional_where . " " .
            "ORDER BY {$this->row_score} DESC " .
            "LIMIT $rank, $total";
        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        if ($res->num_rows === 0) {
            return array();
        }
        $rows = array();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function reset()
    {
        unset($this->_additional_where);
    }
}