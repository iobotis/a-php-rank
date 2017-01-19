<?php
/**
 * @author Ioannis Botis
 * @date 19/1/2017
 * @version: basic.php 8:41 pm
 * @since 19/1/2017
 */
require_once __DIR__ . '/../settings.php';

$dsn = "mysql:host=localhost;dbname=ranking;charset=utf8";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, 'ranking', 'ranking', $opt);

use Ranking\Object;
use Ranking\PDO\Basic as BasicAlgorithm;

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
    $ranking_obj = new BasicAlgorithm($pdo);

    $object = new Object($ranking_obj);

    $object->setAttributes(array('name' => $name));

    $score = $object->getScore();

    $rank = $object->getRank();

    echo $score . " : " . $rank . "\n";

    if (!$ranking_obj->isReady()) {
        $ranking_obj->run();
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