<?php

/**
 * @author Ioannis Botis
 * @date 17/1/2017
 */

namespace Ranking\Object;

use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;
use Ranking\Object;

/**
 * Class BasicAbstract
 * @package Ranking\Object
 */
abstract class BasicAbstract implements AlgorithmInterface
{
    const SCORE_PROPERTY = 'score';
    const RANK_PROPERTY = 'rank';

    /**
     * Our objects sorted based on their score.
     * The objects are saved in the form:
     * {
     *    "element": the actual object sorted,
     *    "rank": its rank,
     *    "score": its score
     * }
     *
     * @var array[]
     */
    protected $objects;
    private $isReady = false;

    /**
     * An abstract method to get all objects that we are trying to rank.
     *
     * @return mixed
     */
    abstract protected function getAllObjects();

    /**
     * An abstract method to get an object from a ranking model.
     * This way you can define your own way to get an object from a ranking model.
     *
     * @param ModelInterface $rankModel
     * @return mixed
     */
    abstract protected function getObject(ModelInterface $rankModel);

    /**
     * A function to get the score from an object.
     * This way you can define your own way to get the score from an object.
     *
     * @param $object
     * @return integer
     */
    abstract protected function getObjectScore($object);

    public function getScore(ModelInterface $rankModel)
    {
        $object = $this->getObject($rankModel);
        return $object->{self::SCORE_PROPERTY};
    }

    public function getRank(ModelInterface $rankModel)
    {
        $object = $this->getObject($rankModel);
        return $object->{self::RANK_PROPERTY};
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $objects = array_slice($this->objects, $rank - 1, $total);

        $algorithm = $this;
        $ranking_objects = array_map(function ($object) use (&$algorithm) {
            $ranking_object = new Object($algorithm);
            $ranking_object->setAttributes((array)$object->element);
            return $ranking_object;
        }, $objects);

        return $ranking_objects;
    }

    public function isReady()
    {
        return $this->isReady;
    }

    public function run()
    {
        $objects = $this->getAllObjects();
        $ranking = $this;

        // @todo first calculate score and then rank the elements.

        // sort based on the score function.
        usort($objects, function ($a, $b) use (&$ranking) {
            return $ranking->getObjectScore($a) < $ranking->getObjectScore($b);
        });

        $this->objects = array();
        foreach ($objects as $key => $object) {
            $this->objects[] = $this->_createInternalObject($object, $this->getObjectScore($object), $key + 1);
        }

        $this->isReady = true;
    }

    private function _createInternalObject($initial_object, $score, $rank)
    {
        return (object) array(
            'element' => $initial_object,
            self::RANK_PROPERTY => $rank,
            self::SCORE_PROPERTY => $score
        );
    }
}