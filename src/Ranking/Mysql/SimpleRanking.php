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
class SimpleRanking implements AlgorithmInterface
{

    protected $table_name;
    protected $row_score;
    protected $rank_row;
    protected $mysqli_connection;

    /**
     * SimpleRanking constructor.
     * @param $mysqli_connection
     * @param string $table
     * @param string $row_score
     * @param string $rank_row
     */
    public function __construct($mysqli_connection, $table, $row_score, $rank_row = null)
    {
        $this->table_name = $table;
        $this->rank_row = $rank_row;
        $this->row_score = $row_score;
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
            throw new Exception("Failed to connect to MySQL: (" . $mysqli_connection->connect_errno . ") " . $mysqli_connection->connect_error);
        }
        $this->mysqli_connection = $mysqli_connection;
    }

    protected function getMySqlConnection()
    {
        return $this->mysqli_connection;
    }

    public function getRank(RankInterface $rankModel)
    {
        $query = "SELECT count(*) as rank,@score" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " . $this->getScore($rankModel);

        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new Exception("Query rows failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $row = $res->fetch_assoc();
        return $row['rank'] + 1;
    }

    protected function getScoreSQL($rankModel)
    {
        return "( SELECT score FROM users WHERE `name` = ? )";
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $rank = intval($this->getMySqlConnection()->real_escape_string($rank));
        $total = intval($this->getMySqlConnection()->real_escape_string($total));
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
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

    public function getScore(RankInterface $rankModel)
    {
        $attributes = $rankModel->getAttributes();

        $query = "SELECT score FROM users WHERE `name` = ?";

        if ($stmt = $this->getMySqlConnection()->prepare($query)) {
            $stmt->bind_param("s", $attributes['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row['score']) {
                throw new Exception('Row does not exist.');
            }
            return (int)($row['score']);
        } else {
            throw new Exception("Query failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
    }

    public function isReady()
    {
        return true;
    }

    public function run()
    {
        if (!$this->rank_row) {
            return true;
        }
        // Lets update the rank column based on the score value.
        $query = "UPDATE {$this->table_name} SET {$this->rank_row} = @r:= (@r+1)" .
            " ORDER BY {$this->row_score} DESC;";
        $res = $this->getMySqlConnection()->query("SET @r=0; ");
        if (!$res) {
            throw new Exception("Rank update failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        $res = $this->getMySqlConnection()->query($query);
        if (!$res) {
            throw new Exception("Rank update failed: (" . $this->getMySqlConnection()->errno . ") " . $this->getMySqlConnection()->error);
        }
        return true;
    }

}

?>
