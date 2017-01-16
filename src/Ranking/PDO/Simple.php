<?php
/**
 * @author Ioannis Botis
 * @date 15/1/2017
 */

namespace Ranking\PDO;

use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;
use Ranking\Object;

class Simple implements AlgorithmInterface
{

    /**
     * @var \PDO
     */
    protected $connection;
    protected $table_name;
    protected $score_column;
    protected $rank_column;
    private $_use_between_condition = false;

    public function __construct(\PDO $pdo_connection, $table, $score_column, $rank_column = null)
    {
        $this->connection = $pdo_connection;
        $this->table_name = $table;
        $this->score_column = $score_column;
        $this->rank_column = $rank_column;
    }

    protected function throwError()
    {
        $errors = $this->connection->errorInfo();
        throw new \Exception("Query failed: (" . $errors[1] . ") " . $errors[2]);
    }

    public function getScore(ModelInterface $rankModel)
    {
        $attributes = $rankModel->getAttributes();

        $condition = implode(' AND ', array_map(
            function ($k) {
                return "`$k`" . '= ?';
            },
            array_keys($attributes)
        ));

        $query = "SELECT {$this->score_column} FROM {$this->table_name} WHERE " . $condition;

        if ($stmt = $this->connection->prepare($query)) {
            $stmt->execute(array_values($attributes));
            $row = $stmt->fetch();
            if (!$row['score']) {
                throw new \Exception('Row does not exist.');
            }
            return (int)($row['score']);
        } else {
            $this->throwError();
        }
    }

    public function getRank(ModelInterface $rankModel)
    {
        // if rank row is supplied use it to get the rank.
        if (!empty($this->rank_column)) {
            $query = "SELECT {$this->rank_column} FROM {$this->table_name} WHERE `name` = ? ";
            $attributes = $rankModel->getAttributes();
            if ($stmt = $this->connection->prepare($query)) {
                $stmt->execute(array_values($attributes));
                $row = $stmt->fetch();
                // if rank is NULL, then continue try to get it with a count.
                if ($row[$this->rank_column] > 0) {
                    return $row[$this->rank_column];
                }
            } else {
                $this->throwError();
            }
        }
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->score_column} > " . $this->getScore($rankModel);
        $stmt = $this->connection->query($query);
        $row = $stmt->fetch();

        if (!$row) {
            $this->throwError();
        }
        return $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $order_by = $this->score_column;
        if (!empty($this->rank_column) && $this->_use_between_condition) {
            $order_by = $this->rank_column;
            $lastrank = $rank + $total;
            $query = "SELECT * " .
                "FROM `{$this->table_name}` " .
                "WHERE {$order_by} BETWEEN $rank AND $lastrank " .
                "ORDER BY {$order_by} DESC ";
        } else {
            $query = "SELECT * " .
                "FROM `{$this->table_name}` " .
                "ORDER BY {$order_by} DESC " .
                "LIMIT $rank, $total";
        }

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
            " FROM {$this->table_name} WHERE {$this->rank_column} IS NULL";
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
        if (!$this->rank_column) {
            return true;
        }
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_column} = @r:= (@r+1)" .
            " ORDER BY {$this->score_column} DESC;";
        $res = $this->connection->query("SET @r=0; ");
        if (!$res) {
            $this->throwError();
        }
        $res = $this->connection->query($query);
        if (!$res) {
            $this->throwError();
        }
        return true;
    }
}