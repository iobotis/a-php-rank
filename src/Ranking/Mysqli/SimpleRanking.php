<?php

namespace Ranking\Mysqli;

use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;
use Ranking\Object;

/**
 * SimpleRanking is simple way to rank a table based on a score row of that
 * table.
 *
 * @author Ioannis Botis
 */
class SimpleRanking implements AlgorithmInterface
{

    protected $table_name;
    protected $score_column;
    protected $rank_column;
    protected $mysqli_connection;
    private $_use_between_condition = false;

    /**
     * SimpleRanking constructor.
     *
     * @param $mysqli_connection
     * @param string $table the table or view that we need to rank.
     * @param string $score_column the column used to hold the score.
     * @param string $rank_column
     */
    public function __construct($mysqli_connection, $table, $score_column, $rank_column = null)
    {
        $this->table_name = $table;
        $this->rank_column = $rank_column;
        $this->score_column = $score_column;
        $this->setMySqlConnection($mysqli_connection);
    }

    /**
     * Define the default mysql connection to be used for this object.
     * @param mysqli object $mysqli_connection
     * @throws Exception
     */
    public function setMySqlConnection($mysqli_connection)
    {
        if (!is_a($mysqli_connection, 'mysqli') || $mysqli_connection->connect_errno) {
            throw new \Exception("Failed to connect to MySQL: (" . $mysqli_connection->connect_errno . ") " . $mysqli_connection->connect_error);
        }
        $this->mysqli_connection = $mysqli_connection;
    }

    protected function getMySqlConnection()
    {
        return $this->mysqli_connection;
    }

    public function useBetweenCondition()
    {
        $this->_use_between_condition = true;
    }

    public function getRank(ModelInterface $rankModel)
    {
        // if rank row is supplied use it to get the rank.
        if (!empty($this->rank_column)) {
            $query = "SELECT {$this->rank_column} FROM {$this->table_name} WHERE `name` = ? ";
            $attributes = $rankModel->getAttributes();
            if ($stmt = $this->getMySqlConnection()->prepare($query)) {
                $stmt->bind_param("s", $attributes['name']);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                // if rank is NULL, then continue try to get it with a count.
                if ($row[$this->rank_column] > 0) {
                    return $row[$this->rank_column];
                }
            } else {
                throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
            }
        }
        $query = "SELECT count(*) as rank" .
            " FROM {$this->table_name} WHERE {$this->score_column} > " . $this->getScore($rankModel);
        $result = $this->getMySqlConnection()->query($query);

        if (!$result) {
            throw new \Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $result->fetch_assoc();
        return $row['rank'] + 1;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $rank = intval($this->getMySqlConnection()->real_escape_string($rank));
        $total = intval($this->getMySqlConnection()->real_escape_string($total));

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

        if ($stmt = $this->getMySqlConnection()->prepare($query)) {
            //$stmt->bind_param("s", $attributes['name']);
            call_user_func_array(
                array($stmt, 'bind_param'),
                array_merge(
                    array(str_repeat('s', count($attributes))),
                    $this->refValues($attributes)
                )
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row['score']) {
                throw new \Exception('Row does not exist.');
            }
            return (int)($row['score']);
        } else {
            throw new \Exception("Query failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
    }

    protected function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    public function isReady()
    {
        if (!$this->rank_column) {
            return true;
        }

        $query = "SELECT count(*) as notranked" .
            " FROM {$this->table_name} WHERE {$this->rank_column} IS NULL";
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
        if (!$this->rank_column) {
            return true;
        }
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_column} = @r:= (@r+1)" .
            " ORDER BY {$this->score_column} DESC;";
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

}

?>
