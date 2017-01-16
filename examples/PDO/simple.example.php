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

use Ranking\PDO\Simple as SimpleAlgorithm;

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

    // get rank for the given value.
    print_my_rank($ranking_obj, $table, $data_row, $row_score, $name);

} catch (Exception $e) {
    print $e->getMessage() . "\n";
    die();
}

echo "Took " . (microtime(true) - $total_time) . "s to complete\n";
