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

