<?php
/**
 * @author Ioannis Botis
 * @date 16/1/2017
 * @version: simple.example.php 7:53 μμ
 * @since 16/1/2017
 */

require_once __DIR__ . '/../settings.php';

$dsn = "mysql:host=localhost;dbname=ranking;charset=utf8";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, 'ranking', 'ranking', $opt);

use Ranking\AlgorithmInterface;
use Ranking\PDO\Simple as SimpleAlgorithm;
use Ranking\Mysql\Object;

// Select a random name to search for.
$sql = <<<SQL
SELECT name
  FROM $table
 ORDER BY RAND() ASC
 LIMIT 1
SQL;

$stmt = $pdo->query($sql);
$row = $stmt->fetch();
$name = $row['name'];

$total_time = microtime(true);
try {
    $ranking_obj = new SimpleAlgorithm($pdo, $table, $row_score, $rank_row);

    if (!$ranking_obj->isReady()) {
        $ranking_obj->run();
    }

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
    }, $ranking_obj->getRowsAtRank($rank, 11));
    print count($names) . ' rows of ' . $table . ' starting at rank = ' . ($rank - count($names) + 1) .
        ' is :' . "\n";
    print 'Rank        name       Score' . "\n";
    print implode("\n", $names) . "\n";

} catch (Exception $e) {
    print $e->getMessage() . "\n";
    die();
}

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";
