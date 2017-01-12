<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: create.table.php 11:59 pm
 * @since 11/1/2017
 */

require_once 'settings.php';

$query = "CREATE TABLE IF NOT EXISTS `" . $table . "` (" .
    "`" . $data_row . "` varchar(120)," .
    "`" . $rank_row . "` int(10) unsigned," .
    "`" . $row_score . "` int(10) unsigned);";
if (!$mysqli->query($query)) {
    echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

//Insert 10000 rows.
$total_time = microtime(true);
$number = 1000;
$characters = 'abcdefghijklmnopqrstuvwxyz';
$time = time();

$rows = array();
for ($i = 0; $i < $number; $i++) {
    $name = '';
    for ($j = 0; $j < 12; $j++) {
        $name .= $characters[rand(0, strlen($characters) - 1)];
    }
    $score = rand(0, $number * 100);
    $name = $mysqli->real_escape_string($name);
    $score = $mysqli->real_escape_string($score);
    $rows[] = "('$name', '$score')";

    // show some info about records being added.
    if( $time < time() - 10) {
        echo "$i records created.\n";
        $time = time();
    }
}

$query = "INSERT INTO $table(" . $data_row . ",score) VALUES " . implode(',', $rows);
if (!$mysqli->query($query)) {
    echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

$mysqli->close();

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";