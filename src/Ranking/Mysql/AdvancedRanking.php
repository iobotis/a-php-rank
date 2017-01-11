<?php
require_once 'SimpleRanking.php';
/**
 * @author Ioannis Botis
 * @date 30/8/2016
 * @version: AdvancedRanking.php 2:48 μμ
 * @since 30/8/2016
 */
class AdvancedRanking extends SimpleRanking
{
    private $_additional_where;

    public function excludeByColumn($column, $value, $op = '=') {
        $this->_additional_where = "`$column` " . $op . " '" . self::$mysqli_connection->real_escape_string($value) . "'";
    }

    public function getRank($column, $value) {
        if(!isset($this->_additional_where)){
            return parent::getRank($column, $value);
        }
        $column = self::$mysqli_connection->real_escape_string($column);
        $value = self::$mysqli_connection->real_escape_string($value);
        $query = "SELECT count(*) as rank,@score" .
            " FROM {$this->table_name} WHERE {$this->row_score} > " .
            "@score:=( SELECT {$this->row_score} FROM {$this->table_name} WHERE `" . $column . "` = ? ) AND " . $this->_additional_where;

        if( $stmt = self::$mysqli_connection->prepare($query) ) {
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if( !$row['@score'] ) {
                throw new Exception('Row does not exist.');
            }
            return $row['rank'] + 1;
        }
        else {
            throw new Exception("Query failed: (" . self::$mysqli_connection->errno . ") " . self::$mysqli_connection->error);
        }
    }

    public function getRowsAtRank($rank, $total = 1) {
        if(!isset($this->_additional_where)){
            return parent::getRowsAtRank($rank, $total);
        }
        $rank = intval(self::$mysqli_connection->real_escape_string($rank));
        $total = intval(self::$mysqli_connection->real_escape_string($total));
        $query = "SELECT * " .
            "FROM `{$this->table_name}` " .
            "WHERE " . $this->_additional_where . " ".
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

    public function reset() {
        unset($this->_additional_where);
    }
}