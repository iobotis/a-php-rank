<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: advanced.example.php 11:08 pm
 * @since 11/1/2017
 */

require_once 'settings.php';

use Ranking\AlgorithmInterface;
use Ranking\Mysql\AdvancedRanking;
use Ranking\Mysql\Object;

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

$rank_row = 'group_rank';

$total_time = microtime(true);
try {
    $advanced_ranking = new AdvancedRanking($mysqli, $table, $row_score, $rank_row);

    $advanced_ranking->excludeByColumn('name', $condition, 'LIKE');

    $advanced_ranking->altOrderByColumn('name', 'DESC');

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

function print_my_rank(AlgorithmInterface $ranking_obj, $table, $data_row, $row_score, $name)
{

    $object = new Object($ranking_obj);

    $object->setAttributes(array('name' => $name));

    $rank = $object->getRank();

    $score = $object->getScore();

    print 'Rank of ' . $table . ' row with ' . $data_row . ' = ' . $name .
        ' is : ' . $rank . "\n";
    print 'Score is ' . $score . "\n\n";
    // List 5 rows before and 5 following the current rank.
    $rank -= 6;
    if ($rank < 0) {
        $rank = 0;
    }
    $names = array_map(function ($model) use (&$rank, $data_row, $row_score) {
        $arr = $model->getAttributes();
        return ++$rank . ' : ' . $arr[$data_row] . ' : ' . $arr[$row_score];
    }, $ranking_obj->getRowsAtRank($rank, 100));
    print count($names) . ' rows of ' . $table . ' starting at rank = ' . ($rank - count($names) + 1) .
        ' is :' . "\n";
    print 'Rank        name       Score' . "\n";
    print implode("\n", $names) . "\n";
}
