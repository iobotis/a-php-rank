<?php

/**
 * @author Ioannis Botis
 * @date 17/1/2017
 */

namespace Ranking\Object;

use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;
use Ranking\Object;


abstract class Basic implements AlgorithmInterface
{
    protected $score_property;
    protected $rank_property;

    protected $objects;
    private $isReady = false;

    abstract public function getAllObjects();

    abstract public function getObject(ModelInterface $rankModel);

    //abstract public function getObjectScore($object);

    public function construct($score_property, $rank_property)
    {
        $this->score_property = $score_property;
        $this->rank_property = $rank_property;
    }

    public function getScore(ModelInterface $rankModel)
    {
        $object = $this->getObject($rankModel);
        return $object->{$this->score_property};
    }

    public function getRank(ModelInterface $rankModel)
    {
        $object = $this->getObject($rankModel);
        return $object->{$this->rank_property};
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        // @todo return Ranking\Object.
        return array_slice($this->objects, $rank - 1, $total);
    }

    public function isReady()
    {
        return $this->isReady;
    }

    public function run()
    {
        $this->objects = $this->getAllObjects();
        $property = $this->score_property;
        usort($this->objects, function($a, $b) use (&$property)
        {
            return strcmp($a->{$property}, $b->{$property});
        });
        $this->isReady = true;
    }
}