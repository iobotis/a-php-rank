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
        $this->_additional_where = "`$column` " . $op . " '" . self::$mysqli_connection->real_escape_string($value) . "'";
    }

    public function getRank(RankInterface $rankModel)
    {
        if (!isset($this->_additional_where)) {
            return parent::getRank($rankModel);
        }
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " . $this->getScore($rankModel) .
            " AND " . $this->_additional_where;

        $res = self::$mysqli_connection->query($query);
        if (!$res) {
            throw new Exception("Query rows failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
        $row = $res->fetch_assoc();
        return $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        if (!isset($this->_additional_where)) {
            return parent::getRowsAtRank($rank, $total);
        }
        $rank = intval(self::$mysqli_connection->real_escape_string($rank));
        $total = intval(self::$mysqli_connection->real_escape_string($total));
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_additional_where . " " .
            "ORDER BY {$this->row_score} DESC " .
            "LIMIT $rank, $total";
        $res = self::$mysqli_connection->query($query);
        if (!$res) {
            throw new Exception("Query rows failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
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