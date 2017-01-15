<?php
/**
 * @author Ioannis Botis
 * @date 15/1/2017
 */

namespace Ranking\PDO;

use Ranking\AlgorithmInterface;

class SimpleAlgorithm implements AlgorithmInterface
{

    private $_connection;

    public function __construct(\PDO $pdo_connection, $table, $row_score, $rank_row = null)
    {
        $this->_connection = $pdo_connection;
        $this->table_name = $table;
        $this->rank_row = $rank_row;
        $this->row_score = $row_score;
    }

    public function getScore(ModelInterface $rankModel)
    {
        // TODO: Implement getScore() method.
    }
}