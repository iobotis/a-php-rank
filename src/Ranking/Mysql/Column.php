<?php
/**
 * @author Ioannis Botis <ioannis.botis@interactivedata.com>
 * @date 11/1/2017
 * @version: Column.php 6:06 μμ
 * @since 11/1/2017
 */

namespace Ranking\Mysql;

use Ranking\RankInterface;

class Column implements RankInterface
{

    public function getScore()
    {
        return 7;
    }
}