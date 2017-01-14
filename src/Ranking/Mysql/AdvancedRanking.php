<?php

namespace Ranking\Mysql;

use Ranking\ModelInterface;
use Ranking\Mysql\SimpleRanking;

/**
 * Advanced ranking is the same as Simple ranking with an optional condition and an optional seconndary order.
 * This way you can rank mysql rows by group.
 *
 * @author Ioannis Botis
 * @date 30/8/2016
 * @version: AdvancedRanking.php 2:48 pm
 * @since 30/8/2016
 */
class AdvancedRanking extends SimpleRanking
{
    private $_condition;
    private $_secondary_order = array();

    /**
     * Set a condition for a specific column.
     *
     * @param string $column the column name.
     * @param string $value column condition value.
     * @param string $op mysql condition operator(= > < LIKE etc).
     */
    public function excludeByColumn($column, $value, $op = '=')
    {
        $this->_condition = "`$column` " . $op . " '" . $this->getMySqlConnection()->real_escape_string($value) . "'";
    }

    /**
     * Set an alternative order in case 2 rows have the same score.
     * 
     * @param string $column
     * @param string $op
     */
    public function altOrderByColumn($column, $op = 'ASC')
    {
        $this->_secondary_order[] = array(
            'column' => $column,
            'order' => $op
        );
    }

    public function getRank(ModelInterface $rankModel)
    {
        if (!isset($this->_condition) && empty($this->_secondary_order)) {
            return parent::getRank($rankModel);
        }
        $score = $this->getScore($rankModel);
        
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " . $score .
            //" AND {$this->_secondary_order}" .
            " AND " . $this->_condition;

        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $res->fetch_assoc();
        $num_of_greater_score = $row['rank'];

        $attributes = $rankModel->getAttributes();

        $order_statement = array_map(function ($order) use (&$attributes) {
            $op = '<';
            if($order['order'] == 'ASC') {
                $op = '>';
            }
            return  $order["column"] . " $op '" . $attributes[$order["column"]] . "'";
        }, $this->_secondary_order);
        $secondary_order = implode(" AND ", $order_statement) . " ";

        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->row_score} = " . $score .
            " AND $secondary_order" .
            " AND " . $this->_condition;

        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $res->fetch_assoc();
        return $num_of_greater_score + $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        if (!isset($this->_condition)) {
            return parent::getRowsAtRank($rank, $total);
        }
        $rank = intval($this->getMySqlConnection()->real_escape_string($rank));
        $total = intval($this->getMySqlConnection()->real_escape_string($total));
        $order_by = $this->row_score;
        $secondary_order = "";
        if (!empty($this->rank_row)) {
            $order_by = $this->rank_row;
        } elseif (!empty($this->_secondary_order)) {
            $order_statement = array_map(function ($order) {
                return  $order["column"] . ' ' . $order['order'];
            }, $this->_secondary_order);
            $secondary_order = "," . implode(",", $order_statement) . " ";
        }
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_condition . " " .
            "ORDER BY {$order_by} DESC " . $secondary_order .
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
        if (!isset($this->_condition) && empty($this->_secondary_order)) {
            return parent::run();
        }

        // if no rank column used, you cannot run the algorithm.
        if (!$this->rank_row) {
            return true;
        }
        $secondary_order = "";
        if (!empty($this->_secondary_order)) {
            $order_statement = array_map(function ($order) {
                return  $order["column"] . ' ' . $order['order'];
            }, $this->_secondary_order);
            $secondary_order = "," . implode(",", $order_statement);
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