<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: Column.php 6:06 pm
 * @since 11/1/2017
 */

namespace Ranking\Mysql;

use Ranking\AlgorithmInterface;
use Ranking\RankInterface;

class Column implements RankInterface
{

    protected $attributes = array();
    protected $ranking;

    public function __construct(AlgorithmInterface $ranking)
    {
        $this->ranking = $ranking;
    }

    public function getScore()
    {
        return 7; //return $this->ranking->getScore();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }
    public function getAttributes()
    {
        return $this->attributes;
    }
}