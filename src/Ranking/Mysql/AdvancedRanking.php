<?php

namespace Ranking\Mysql;

use Ranking\ModelInterface;
use Ranking\Mysql\SimpleRanking;

/**
 * @author Ioannis Botis
 * @date 30/8/2016
 * @version: AdvancedRanking.php 2:48 pm
 * @since 30/8/2016
 */
class AdvancedRanking extends SimpleRanking
{
    private $_condition;
    private $_secondary_order;

    public function excludeByColumn($column, $value, $op = '=')
    {
        $this->_condition = "`$column` " . $op . " '" . $this->getMySqlConnection()->real_escape_string($value) . "'";
    }

    public function altOrderByColumn($column, $op = 'ASC')
    {
        $this->_secondary_order = "$column " . $op;
    }

    public function getRank(RankInterface $rankModel)
    {
        if (!isset($this->_condition)) {
            return parent::getRank($rankModel);
        }
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " . $this->getScore($rankModel) .
            " AND " . $this->_condition;

        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $res->fetch_assoc();
        return $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        if (!isset($this->_condition)) {
            return parent::getRowsAtRank($rank, $total);
        }
        $rank = intval($this->getMySqlConnection()->real_escape_string($rank));
        $total = intval($this->getMySqlConnection()->real_escape_string($total));
        $order_by = $this->row_score;
        if (!empty($this->rank_row)) {
            $order_by = $this->rank_row;
        }
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_condition . " " .
            "ORDER BY {$order_by} DESC " .
            "LIMIT $rank, $total";
        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        if ($res->num_rows === 0) {
            return array();
        }
        $rows = array();
        while ($row = $res->fetch_assoc()) {
            $mysqlRow = new Object($this);
            $mysqlRow->setAttributes($row);
            $rows[] = $mysqlRow;
        }
        return $rows;
    }

    public function isReady()
    {
        if (!$this->rank_row) {
            return true;
        }

        $query = "SELECT count(*) as notranked" .
            " FROM {$this->table_name} WHERE {$this->rank_row} IS NULL" .
            " AND " . $this->_condition;
        $result = $this->getMySqlConnection()->query($query);
        if (!$result) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $result->fetch_assoc();
        if ($row['notranked'] > 0) {
            return false;
        }
        return true;
    }

    public function run()
    {
        // If no condition defined, use the simple method.
        if (!isset($this->_condition) && !isset($this->_secondary_order)) {
            return parent::run();
        }

        // if no rank column used, you cannot run the algorithm.
        if (!$this->rank_row) {
            return true;
        }
        $secondary_order = "";
        if (isset($this->_secondary_order)) {
            $secondary_order = "," . $this->_secondary_order;
        }
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_row} = @r:= (@r+1)"
            . " WHERE " . $this->_condition .
            " ORDER BY {$this->row_score} DESC" . $secondary_order . ";";

        $res = $this->getMySqlConnection()->query("SET @r=0; ");
        if (!$res) {
            throw new \Exception("Rank update failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new \Exception("Rank update failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        return true;
    }

    public function reset()
    {
        unset($this->_condition);
    }
}