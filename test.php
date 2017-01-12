<?php

/**
 * Simple autoloader.
 */
spl_autoload_register(function ($class) {

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use Ranking\AlgorithmInterface;
use Ranking\Mysql\MysqlRanking;
use Ranking\Mysql\Column;
/*
 * This is a test file to run from command line.
 */

print "Please choose an action:\n";
print "0. Create tables.\n";
print "1. Create records.\n";
print "2. Update rank column\n";
print "3. Run test 1.\n";
print "4. Run test 2.\n";

$stdin = fopen('php://stdin', 'r');
fscanf($stdin, "%d\n", $number); // reads number from STDIN

$mysqli = new \mysqli("localhost", "ranking", "ranking", "ranking");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    die();
}
echo $mysqli->host_info . "\n";

$table = 'users';
$data_row = 'name';
$rank_row = 'rank';
$row_score = 'score';

$total_time = microtime(true);

if ($number == 0 ) {
    $query = "CREATE TABLE IF NOT EXISTS `" . $table . "` (" .
        "`" . $data_row . "` varchar(120)," .
        "`" . $rank_row . "` int(10) unsigned," .
        "`" . $row_score . "` int(10) unsigned);";
    if (!$mysqli->query($query)) {
        echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
} elseif ($number == 1) {
    $stdin = fopen('php://stdin', 'r');
    print "How many rows to insert?\n";
    fscanf($stdin, "%d\n", $number); // reads number from STDIN
    $total_time = microtime(true);
    $number = intval($number);
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $time = time();
    for ($i = 0; $i < $number; $i++) {
        $name = '';
        for ($j = 0; $j < 12; $j++) {
            $name .= $characters[rand(0, strlen($characters) - 1)];
        }
        $score = rand(0, $number * 100);
        $name = $mysqli->real_escape_string($name);
        $score = $mysqli->real_escape_string($score);
        $query = "INSERT INTO $table(" . $data_row . ",score) VALUES ('$name', '$score')";
        if (!$mysqli->query($query)) {
            echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
        }
        // show some info about records being added.
        if( $time < time() - 10) {
            echo "$i records created.\n";
            $time = time();
        }
    }
} elseif ($number == 2) {
    require_once 'SimpleRanking.php';
    try {
        SimpleRanking::setMySqlConnection($mysqli);
        $simple_ranking = new SimpleRanking($table, $row_score, $rank_row);
        $simple_ranking->run();
    } catch (Exception $e) {
        print $e->getMessage() . "\n";
        die();
    }
} elseif ($number == 3) {

    $stdin = fopen('php://stdin', 'r');
    print "Which name to search?\n";
    fscanf($stdin, "%s\n", $name); // reads number from STDIN
    $total_time = microtime(true);
    try {
        MysqlRanking::setMySqlConnection($mysqli);
        $simple_ranking = new MysqlRanking($table, $row_score, $rank_row);
        // get rank for the given value.
        print_my_rank($simple_ranking, $table, $data_row, $row_score, $name);
    } catch (Exception $e) {
        print $e->getMessage() . "\n";
        die();
    }
} elseif ($number == 4) {
    require_once 'AdvancedRanking.php';
    $stdin = fopen('php://stdin', 'r');
    print "Which name to search?\n";
    fscanf($stdin, "%s\n", $name); // reads number from STDIN
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
}
echo "Took " . (microtime(true) - $total_time) . "s to complete\n";

function print_my_rank(AlgorithmInterface $ranking_obj, $table, $data_row, $row_score, $name) {

    $column = new Column($ranking_obj);

    $column->setAttributes(array('name' => $name));

    $rank = $ranking_obj->getRank($column);

    print 'Rank of ' . $table . ' row with ' . $data_row . ' = ' . $name .
        ' is : ' . $rank . "\n";
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
?>
