<?php

/*
 * This is a test file to run througn command line.
 */

print "Please choose an action:\n";
print "1. Run test 1.\n";
print "2. Create records.\n";
print "3. Update rank column\n";

$stdin = fopen('php://stdin', 'r');
fscanf($stdin, "%d\n", $number); // reads number from STDIN

$mysqli = new mysqli("localhost", "ranking", "ranking", "ranking");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    die();
}
echo $mysqli->host_info . "\n";

$table = 'users';
$rank_row = 'rank';
$row_score = 'score';

if ($number == 2) {
    $stdin = fopen('php://stdin', 'r');
    print "How many rows to insert?\n";
    fscanf($stdin, "%d\n", $number); // reads number from STDIN

    $characters = 'abcdefghijklmnopqrstuvwxyz';
    for ($i = 0; $i < 1000; $i++) {
        $name = '';
        for ($j = 0; $j < 12; $j++) {
            $name .= $characters[rand(0, strlen($characters) - 1)];
        }
        $score = rand(0, 100000);
        $name = $mysqli->real_escape_string($name);
        $score = $mysqli->real_escape_string($score);
        $query = "INSERT INTO $table(name,score) VALUES ('$name', '$score')";
        if (!$mysqli->query($query)) {
            echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
        }
    }
} elseif ($number == 1) {
    require_once 'SimpleRanking.php';
    $id = 101;
    try {
        SimpleRanking::setMySqlConnection($mysqli);
        $simple_ranking = new SimpleRanking($table, $row_score, $rank_row);
        print 'Rank of ' . $table . ' row with id = ' . $id .
                ' is : ' . $simple_ranking->getRank($id) . "\n";
        $counter = 25;
        $names = array_map(function($arr) use (&$counter) {
                    return $counter++ . '.' . $arr['name'];
                }, $simple_ranking->getRowsAtRank(25, 10));
        print '10 rows of ' . $table . ' starting at rank = 25' .
                ' is :' . "\n" . implode("\n", $names) . "\n";
    } catch (Exception $e) {
        print $e->getMessage();
        die();
    }
} elseif ($number == 3) {
    require_once 'SimpleRanking.php';
    try {
        SimpleRanking::setMySqlConnection($mysqli);
        $simple_ranking = new SimpleRanking($table, $row_score, $rank_row);
        $simple_ranking->run();
    } catch (Exception $e) {
        print $e->getMessage();
        die();
    }
}
?>
