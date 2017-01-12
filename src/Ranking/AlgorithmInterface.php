<?php

namespace Ranking;
/**
 * We define an interface to the way to get information
 * about the ranking of the records we have.
 * This is used as a service and defines our ranking algorith.
 *
 * @author Ioannis Botis
 */
interface AlgorithmInterface
{

    /**
     * Depending on the algorithm used for ranking, maybe the ranking is not
     * yet ready.
     * @return bool True if ranking is ready, false if not.
     */
    public function isReady();

    /**
     * Optional process that needs to run to recalculate the rank.
     * @return void.
     */
    public function run();

    /**
     * Find the rank of a specific row.
     * @param ModelInterface $rankModel the value the column has.
     * @return int the rank of the row.
     */
    public function getRank(ModelInterface $rankModel);

    /**
     * Find the rows at a specific rank.
     * @param int $rank
     * @param int $total
     * @return ModelInterface[]
     */
    public function getRowsAtRank($rank, $total = 1);

    /**
     * @param ModelInterface $rankModel
     * @return mixed
     */
    public function getScore(ModelInterface $rankModel);
}

?>
