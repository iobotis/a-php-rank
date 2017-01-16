<?php

namespace Ranking\PDO;

use Ranking\ModelInterface;
use Ranking\PDO\Simple;
use Ranking\Mysql\Object;

/**
 * Advanced ranking is the same as Simple ranking with an optional condition and an optional secondary order.
 * This way you can rank mysql rows by group.
 *
 * @author Ioannis Botis
 * @date 30/8/2016
 * @version: AdvancedRanking.php 2:48 pm
 * @since 30/8/2016
 */
class Advanced extends Simple
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
    public function addColumnCondition($column, $value, $op = '=')
    {
        $this->_condition = "`$column` " . $op . " '" . $value . "'";
    }

    /**
     * Set an alternative order in case 2 rows have the same score.
     *
     * @param string $column
     * @param string $op
     */
    public function addAltOrderByColumn($column, $op = 'ASC')
    {
        $this->_secondary_order[] = array(
            'column' => $column,
            'order' => $op
        );
    }

    public function getRank(ModelInterface $rankModel)
    {
        // if rank row is supplied use it to get the rank.
        if (!empty($this->rank_column)) {
            return parent::getRank($rankModel);
        }
        if (!isset($this->_condition) && empty($this->_secondary_order)) {
            return parent::getRank($rankModel);
        }
        $score = $this->getScore($rankModel);

        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->score_column} > " . $score .
            " AND " . $this->_condition;

        $stmt = $this->connection->query($query);
        $row = $stmt->fetch();
        $num_of_greater_score = $row['rank'];

        $attributes = $rankModel->getAttributes();

        $order_statement = array_map(function ($order) use (&$attributes) {
            $op = '<';
            if ($order['order'] == 'DESC') {
                $op = '>';
            }
            return $order["column"] . " $op '" . $attributes[$order["column"]] . "'";
        }, $this->_secondary_order);
        $secondary_order = implode(" AND ", $order_statement) . " ";

        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->score_column} = " . $score .
            " AND $secondary_order" .
            " AND " . $this->_condition;

        $stmt = $this->connection->query($query);
        $row = $stmt->fetch();
        return $num_of_greater_score + $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        // if rank row is supplied use it to get the rank.
        if (!empty($this->rank_row)) {
            return parent::getRowsAtRank($rank, $total);
        }

        if (!isset($this->_condition) && empty($this->_secondary_order)) {
            return parent::getRowsAtRank($rank, $total);
        }

        $order_by = $this->score_column;
        $secondary_order = "";
        if (!empty($this->rank_column)) {
            $order_by = $this->rank_column;
        } elseif (!empty($this->_secondary_order)) {
            $order_statement = array_map(function ($order) {
                return $order["column"] . ' ' . $order['order'];
            }, $this->_secondary_order);
            $secondary_order = "," . implode(",", $order_statement) . " ";
        }
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_condition . " " .
            "ORDER BY {$order_by} DESC " . $secondary_order .
            "LIMIT $rank, $total";

        $res = $this->connection->query($query);
        if (!$res) {
            $this->throwError();
        }
        $rows = $res->fetchAll();

        $algorithm = $this;
        $objects = array_map(function ($row) use (&$algorithm) {
            $mysqlRow = new Object($algorithm);
            $mysqlRow->setAttributes($row);
            return $mysqlRow;
        }, $rows);
        return $objects;
    }

    public function isReady()
    {
        if (!$this->rank_column) {
            return true;
        }

        $query = "SELECT count(*) as notranked" .
            " FROM {$this->table_name} WHERE {$this->rank_column} IS NULL" .
            (!empty($this->_condition) ? " AND " . $this->_condition : "");
        $result = $this->connection->query($query);
        if (!$result) {
            $this->throwError();
        }
        $row = $result->fetch();
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
        if (!$this->rank_column) {
            return true;
        }
        $secondary_order = "";
        if (!empty($this->_secondary_order)) {
            $order_statement = array_map(function ($order) {
                return $order["column"] . ' ' . $order['order'];
            }, $this->_secondary_order);
            $secondary_order = "," . implode(",", $order_statement);
        }
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_column} = @r:= (@r+1)"
            . " WHERE " . $this->_condition .
            " ORDER BY {$this->score_column} DESC" . $secondary_order . ";";

        $this->connection->query("SET @r=0; ");
        $this->connection->query($query);
        return true;
    }

    public function reset()
    {
        unset($this->_condition);
    }
}