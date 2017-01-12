<?php
/**
 * @author Ioannis Botis <ioannis.botis@interactivedata.com>
 * @date 12/1/2017
 * @version: advanced.example.php 12:08 μμ
 * @since 12/1/2017
 */

require_once 'settings.php';

use Ranking\AlgorithmInterface;
use Ranking\Mysql\AdvancedRanking;
use Ranking\Mysql\Column;

// Select a random name to search for.
$sql = <<<SQL
SELECT name
  FROM $table
  WHERE `name` LIKE '%s%'
 ORDER BY RAND() ASC
 LIMIT 1
SQL;

$res = $mysqli->query($sql);
if (!$res) {
    throw new Exception("Query rows failed: (" . $mysqli->errno . ") " . $mysqli->error);
}
$row = $res->fetch_assoc();
$name = $row['name'];

$total_time = microtime(true);
try {
    AdvancedRanking::setMySqlConnection($mysqli);
    $advanced_ranking = new AdvancedRanking($table, $row_score, $rank_row);
    print "Only searching at names containing 's' \n";
    $advanced_ranking->excludeByColumn( 'name' , '%s%', 'LIKE' );
    print_my_rank($advanced_ranking, $table, $data_row, $row_score, $name);
} catch (Exception $e) {
    print $e->getMessage() . "\n";
    die();
}

$mysqli->close();

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";

function print_my_rank(AlgorithmInterface $ranking_obj, $table, $data_row, $row_score, $name) {

    $column = new Column($ranking_obj);

    $column->setAttributes(array('name' => $name));

    $rank = $column->getRank();

    $score = $column->getScore();

    print 'Rank of ' . $table . ' row with ' . $data_row . ' = ' . $name .
        ' is : ' . $rank . "\n";
    print 'Score is ' . $score . "\n\n";
    // List 5 rows before and 5 following the current rank.
    $rank -= 6;
    if( $rank < 0 ) {
        $rank = 0;
    }
    $names = array_map(function($arr) use (&$rank, $data_row, $row_score) {
        return ++$rank . ' : ' . $arr[ $data_row ] . ' : ' . $arr[ $row_score ];
    }, $ranking_obj->getRowsAtRank($rank, 11));
    print count($names) . ' rows of ' . $table . ' starting at rank = ' . ($rank - count($names) + 1) .
        ' is :' . "\n";
    print 'Rank        name       Score' . "\n";
    print implode("\n", $names) . "\n";
}
