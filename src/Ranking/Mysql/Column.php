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

/**
 * Class Column
 *
 *
 * @package Ranking\Mysql
 */
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
        return $this->ranking->getScore($this);
    }

    public function getRank()
    {
        return $this->ranking->getRank($this);
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Usually this is the column id.
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}