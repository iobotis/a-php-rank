# a-php-rank

Description
-----------

This library provides an interface to rank object.
Included you can find classes PHP Ranking system for MySQL tables.

Ranking Mysql row
-----------------
To rank a mysql row, the best way is to provide a score for it and let the algorithm
rank the row based on that score. Though, sometimes it is not possible to have a score row,
e.g when you have a lot of different groups of rows to rank and score can be different based on the group selected.

Group ranking
-------------
Different ranks can exist for an object based on conditions and different scores can exist
for each group.
The best way to implement this is to create a different ranking class that implements the AlgorithmInterface
for each group.

Examples
--------
Mysql table users(
varchar name,
int score,
int rank,
int group_rank
)
1. Rank rows by score.
```php

$simple_ranking = new SimpleRanking($mysqli, 'users, 'score', 'rank');

if (!$simple_ranking->isReady()) {
    $simple_ranking->run();
}

$object = new Object($ranking_obj);

$object->setAttributes(array('name' => 'Bob));

$rank = $object->getRank();

$score = $object->getScore();
    `
```

2. Rank a group of rows by score.
```php
$advanced_ranking = new AdvancedRanking($mysqli, 'users, 'score', 'group_rank');

// select only names that contain "s".
$advanced_ranking->excludeByColumn('name', $condition, 'LIKE');
$advanced_ranking->altOrderByColumn('name', 'DESC');

if (!$advanced_ranking->isReady()) {
    $advanced_ranking->run();
}`