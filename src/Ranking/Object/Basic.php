<?php
/**
 * @author Ioannis Botis
 * @date 17/1/2017
 * @version: Basic.php 10:28 πμ
 * @since 17/1/2017
 */

namespace Ranking\Object;

use Ranking\Object\BasicAbstract;
use Ranking\ModelInterface;

class Basic extends BasicAbstract
{

    protected $unranked_objects;
    protected $score_property;

    /**
     * Basic constructor.
     * The most simple scoring algorithm would be if the object already has a score property.
     *
     * @param string $score_property
     */
    public function __construct($score_property = 'score')
    {
        $this->score_property = $score_property;
    }

    protected function getAllObjects()
    {
        return $this->unranked_objects;
    }

    public function setAllObjects($objects)
    {
        $this->unranked_objects = $objects;
    }

    protected function getObject(ModelInterface $rankModel)
    {
        $attributes = $rankModel->getAttributes();

        foreach ($this->objects as $object) {
            if ($this->objectMatchesAttributes($object->element, $attributes)) {
                return $object;
            }
        }
        return null;
    }

    protected function getObjectScore($object)
    {
        return $object->{$this->score_property};
    }

    private function objectMatchesAttributes($object, $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if (!isset($object->{$key}) || $object->{$key} != $attribute) {
                return false;
            }
        }
        return true;
    }
}