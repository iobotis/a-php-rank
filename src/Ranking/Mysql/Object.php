<?php
/**
 * @author Ioannis Botis
 * @date 11/1/2017
 * @version: Object.php 6:06 pm
 * @since 11/1/2017
 */

namespace Ranking\Mysql;

use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;

/**
 * Class Object
 * This usually is a mysql row or view.
 *
 * @package Ranking\Mysql
 */
class Object implements ModelInterface
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
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}