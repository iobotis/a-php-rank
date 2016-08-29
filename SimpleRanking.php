<?php

include 'RankingAlgorithmInterface.php';

/**
 * SimpleRanking is simple way to rank a table based on a score row of that
 * table.
 *
 * @author Ioannis Botis
 */
class SimpleRanking implements RankingAlgorithmInterface {

    protected $table_name;
    protected $row_score;
    protected $rank_row;
    protected static $mysqli_connection;

    public function __construct($table, $row_score, $rank_row) {
        $this->table_name = $table;
        $this->rank_row = $rank_row;
        $this->row_score = $row_score;
    }

    /**
     * Define the default mysql connection to be used for this object.
     * @param mysqli object $mysqli_connection
     * @throws Exception
     */
    public static function setMySqlConnection($mysqli_connection) {
        if (!is_a($mysqli_connection, 'mysqli') || $mysqli_connection->connect_errno) {
            throw new Exception("Failed to connect to MySQL: (" . $mysqli_connection->connect_errno . ") " . $mysqli_connection->connect_error);
        }
        self::$mysqli_connection = $mysqli_connection;
    }

    public function getRank($column, $value) {
        $column = self::$mysqli_connection->real_escape_string($column);
        $value = self::$mysqli_connection->real_escape_string($value);
        $query = "SELECT count(*) as rank" .
                " FROM {$this->table_name} WHERE {$this->row_score} > " .
                "( SELECT {$this->row_score} FROM {$this->table_name} WHERE `" . $column . "` = '$value' )";
        $res = self::$mysqli_connection->query($query);
        if (!$res) {
            throw new Exception("Query failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
        $row = $res->fetch_assoc();
        return $row['rank'];
    }

    public function getRowsAtRank($rank, $total = 1) {
        $rank = intval(self::$mysqli_connection->real_escape_string($rank));
        $total = intval(self::$mysqli_connection->real_escape_string($total));
        $query = "SELECT * " .
                "FROM {$this->table_name} " .
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

    public function isReady() {
        return true;
    }

    public function run() {
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
        return TRUE;
    }

}

?>
