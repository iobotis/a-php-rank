<?php
/**
 * @author Ioannis Botis
 * @date 19/1/2017
 * @version: Basic.php 7:39 pm
 * @since 19/1/2017
 */

namespace Ranking\PDO;

use Ranking\Object as RankObject;


class Basic extends BasicAbstract
{
    public function getScoreSelectQuery()
    {
        return <<<SQL
SELECT score FROM users
SQL;

    }

    public function getScoreAliasName()
    {
        return "score";
    }

    public function getRankSelectQuery()
    {
        return <<<SQL
SELECT rank FROM users
SQL;
    }

    public function getRankColumnName(){
        return "rank";
    }

    public function getModelSelectQuery()
    {
        return <<<SQL
SELECT * FROM users
SQL;
    }

    public function getRankAliasName()
    {
        return "rank";
    }

    public function getConditionQuery(RankObject $rankModel)
    {
        $attributes = $rankModel->getAttributes();
        return <<<SQL
WHERE `name` = "{$attributes["name"]}"
SQL;

    }

    public function getConditionQueryValues(RankObject $rankModel)
    {

    }

    public function getRankColumnUpdate()
    {
        return <<<SQL
UPDATE users 
SQL;

    }
}