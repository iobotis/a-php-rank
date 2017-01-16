<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: simple.example.php 11:58 pm
 * @since 11/1/2017
 */

require_once 'settings.php';


use Ranking\Mysql\SimpleRanking;


// Select a random name to search for.
$sql = <<<SQL
SELECT name
  FROM $table
 ORDER BY RAND() ASC
 LIMIT 1
SQL;

$res = $mysqli->query($sql);
if (!$res) {
    throw new \Exception("Query rows failed: (" . $mysqli->errno . ") " . $mysqli->error);
}
$row = $res->fetch_assoc();
$name = $row['name'];

$total_time = microtime(true);
try {
    $simple_ranking = new SimpleRanking($mysqli, $table, $row_score, $rank_row);

    if (!$simple_ranking->isReady()) {
        $simple_ranking->run();
    }
    // get rank for the given value.
    print_my_rank($simple_ranking, $table, $data_row, $row_score, $name);
} catch (Exception $e) {
    print $e->getMessage() . "\n";
    die();
}

$mysqli->close();

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";

