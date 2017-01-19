<?php
/**
 * @author Ioannis Botis
 * @date 19/1/2017
 * @version: BasicAbstract.php 6:18 pm
 * @since 19/1/2017
 */

namespace Ranking\PDO;
use Ranking\AlgorithmInterface;
use Ranking\ModelInterface;
use Ranking\Object as RankObject;


abstract class BasicAbstract implements AlgorithmInterface
{

    /**
     * @var \PDO
     */
    protected $connection;


    public function __construct(\PDO $pdo_connection)
    {
        $this->connection = $pdo_connection;
    }

    /**
     * Get the select query to get the score.
     * @return string
     */
    abstract public function getScoreSelectQuery();

    /**
     * Get the score s alias name.
     *
     * @return string
     */
    abstract public function getScoreAliasName();

    /**
     * Get the query to select the rank.
     *
     * @return string
     */
    abstract public function getRankSelectQuery();

    /**
     * Get the rank alias name.
     *
     * @return string
     */
    abstract public function getRankAliasName();

    /**
     * Get the column name used to rank.
     * If none exists, null is returned.
     *
     * @return string|null;
     */
    abstract public function getRankColumnName();

    /**
     * Get the query to select.
     *
     * @return string
     */
    abstract public function getModelSelectQuery();

    /**
     * Get the condition to query for a row results from our model.
     * @param RankObject $rankModel
     * @return mixed
     */
    abstract public function getConditionQuery(RankObject $rankModel);

    /**
     * Get the condition values to supply to our query.
     *
     * @param RankObject $rankModel
     * @return mixed
     */
    abstract public function getConditionQueryValues(RankObject $rankModel);

    /**
     * Get the rank column update query.
     *
     * @return mixed
     */
    abstract public function getRankColumnUpdate();

    public function getScore(ModelInterface $rankModel)
    {
        $query = $this->getScoreSelectQuery() . ' ' . $this->getConditionQuery($rankModel);
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        // if rank is NULL, then continue try to get it with a count.
        if ($row[$this->getScoreAliasName()] > 0) {
            return $row[$this->getScoreAliasName()];
        }

        return null;
    }

    public function getRank(ModelInterface $rankModel)
    {
        $query = $this->getRankSelectQuery() . ' ' . $this->getConditionQuery($rankModel);
        $stmt = $this->connection->prepare($query);

        $stmt->execute();
        $row = $stmt->fetch();
        // if rank is NULL, then continue try to get it with a count.
        if ($row[$this->getRankAliasName()] > 0) {
            return $row[$this->getRankAliasName()];
        }

        return null;
    }

    public function getRowsAtRank($rank, $total = 1)
    {
        $query = $this->getModelSelectQuery() .
            ' ORDER BY ' . $this->getRankAliasName() . ' ASC ' .
            'LIMIT ' . $rank . ',' . $total;
        $stmt = $this->connection->prepare($query);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $algorithm = $this;

        $objects = array_map(function ($row) use (&$algorithm) {
            $mysqlRow = new RankObject($algorithm);
            $mysqlRow->setAttributes($row);
            return $mysqlRow;
        }, $rows);

        return $objects;
    }

    public function isReady()
    {
        if (!$this->getRankColumnName()) {
            return true;
        }

        $query = "SELECT count(*) as notranked" .
            " FROM `users` WHERE {$this->getRankColumnName()} IS NULL";
        $result = $this->connection->query($query);
        if (!$result) {
            $this->throwError();
        }
        $row = $result->fetch();
        if ($row['notranked'] > 0) {
            return false;
        }
        return true;
    }

    public function run()
    {
        if (!$this->getRankColumnName()) {
            return true;
        }
        // Lets update the rank column based on the score value.
        $query = $this->getRankColumnUpdate() . " SET {$this->getRankColumnName()} = @r:= (@r+1)" .
            " ORDER BY {$this->getScoreAliasName()} DESC;";

        $res = $this->connection->query("SET @r=0; ");
        if (!$res) {
            $this->throwError();
        }
        $res = $this->connection->query($query);
        if (!$res) {
            $this->throwError();
        }
        return true;
    }

}