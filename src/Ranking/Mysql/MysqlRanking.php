<?php

namespace Ranking\Mysql;

use Ranking\AlgorithmInterface;
use Ranking\RankInterface;

/**
 * SimpleRanking is simple way to rank a table based on a score row of that
 * table.
 *
 * @author Ioannis Botis
 */
class MysqlRanking implements AlgorithmInterface
{

    protected $table_name;
    protected $row_score;
    protected $rank_row;
    protected static $mysqli_connection;

    public function __construct($table, $row_score, $rank_row)
    {
        $this->table_name = $table;
        $this->rank_row = $rank_row;
        $this->row_score = $row_score;
    }

    /**
     * Define the default mysql connection to be used for this object.
     * @param mysqli object $mysqli_connection
     * @throws Exception
     */
    public static function setMySqlConnection($mysqli_connection)
    {
        if (!is_a($mysqli_connection, 'mysqli') || $mysqli_connection->connect_errno) {
            throw new Exception("Failed to connect to MySQL: (" . $mysqli_connection->connect_errno . ") " . $mysqli_connection->connect_error);
        }
        self::$mysqli_connection = $mysqli_connection;
    }

    public function getRank(RankInterface $rankModel)
    {
        $attributes = $rankModel->getAttributes();

        $query = "SELECT count(*) as rank,@score" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " .
            "@score:=" . $this->getScoreSQL($rankModel);

        if ($stmt = self::$mysqli_connection->prepare($query)) {
            $stmt->bind_param("s", $attributes['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row['@score']) {
                throw new Exception('Row does not exist.');
            }
            return (int)($row['rank'] + 1);
        } else {
            throw new Exception("Query failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
    }

    protected function getScoreSQL($rankModel)
    {
        return "( SELECT score FROM users WHERE `name` = ? )";
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $rank = intval(self::$mysqli_connection->real_escape_string($rank));
        $total = intval(self::$mysqli_connection->real_escape_string($total));
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
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

    public function getScore(RankInterface $rankModel)
    {
        $attributes = $rankModel->getAttributes();

        $query = "SELECT score FROM users WHERE `name` = ?";

        if ($stmt = self::$mysqli_connection->prepare($query)) {
            $stmt->bind_param("s", $attributes['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row['score']) {
                throw new Exception('Row does not exist.');
            }
            return (int)($row['score']);
        } else {
            throw new Exception("Query failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
    }

    public function isReady()
    {
        return true;
    }

    public function run()
    {
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_row} = @r:= (@r+1)" .
            " ORDER BY {$this->row_score} DESC;";
        $res = self::$mysqli_connection->query("SET @r=0; ");
        if (!$res) {
            throw new Exception("Rank update failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
        $res = self::$mysqli_connection->query($query);
        if (!$res) {
            throw new Exception("Rank update failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
        return true;
    }

    public function __destruct()
    {
        self::$mysqli_connection->close();
    }

}

?>
