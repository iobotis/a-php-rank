<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: advanced.example.php 11:08 pm
 * @since 11/1/2017
 */

require_once 'settings.php';

use Ranking\Mysqli\AdvancedRanking;

$condition = '%s%';
// Select a random name to search for.
$sql = <<<SQL
SELECT name
  FROM $table
  WHERE `name` LIKE '$condition'
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
    $advanced_ranking = new AdvancedRanking($mysqli, $table, $row_score);

    // select only names that contain "s".
    $advanced_ranking->excludeByColumn('name', $condition, 'LIKE');

    $advanced_ranking->addAltOrderByColumn('name');

    if (!$advanced_ranking->isReady()) {
        $advanced_ranking->run();
    }

    print "Only searching at names containing 's' \n";
    print_my_rank($advanced_ranking, $table, $data_row, $row_score, $name);
} catch (Exception $e) {
    print $e->getMessage() . "\n";
    die();
}

$mysqli->close();

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";

