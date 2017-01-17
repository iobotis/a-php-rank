<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: basic.example.php 10:24 pm
 * @since 17/1/2017
 */

/**
 * Simple autoloader.
 */
spl_autoload_register(function ($class) {

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/../../src/';

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use Ranking\Object\Basic;
use Ranking\Object;

class Example extends Basic {
    // Override score function and set our own.
    protected function getObjectScore($object)
    {
        return $object->{$this->score_property} + $object->friends * 18;
    }
}

$ranking = new Example('score');

$data = file_get_contents('data.json');
$objects = json_decode($data);

$ranking->setAllObjects($objects);

if(!$ranking->isReady()) {
    $ranking->run();
}

$model = new Object($ranking);

$model->setAttributes(array('id' => 1));

$rank = $model->getRank();

print 'Rank is ' . $rank . "\n";
print 'Score is ' . $model->getScore() . "\n\n";

// List 5 rows before and 5 following the current rank.
$rank -= 6;
if ($rank <= 0) {
    $rank = 1;
}
$names = array_map(function ($model) use (&$rank) {
    $arr = $model->getAttributes();
    return $rank++ . '    :   ' . $arr['id'] . '   :   ' . $arr['name'] . '   :   ' . $model->getScore();
}, $ranking->getRowsAtRank($rank, 11));
print count($names) . ' rows of objects' . ' starting at rank = ' . ($rank - count($names)) .
    ' is :' . "\n";
print 'Rank     id      name       Score' . "\n";
print implode("\n", $names) . "\n";



