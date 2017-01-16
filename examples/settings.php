<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: settings.php 11:56 pm
 * @since 11/1/2017
 */

/**
 * Simple autoloader.
 */
spl_autoload_register(function ($class) {

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

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
$group_rank = 'group_rank';

use Ranking\AlgorithmInterface;
use Ranking\Mysql\Object;

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
    }, $ranking_obj->getRowsAtRank($rank, 11));
    print count($names) . ' rows of ' . $table . ' starting at rank = ' . ($rank - count($names) + 1) .
        ' is :' . "\n";
    print 'Rank        name       Score' . "\n";
    print implode("\n", $names) . "\n";
}