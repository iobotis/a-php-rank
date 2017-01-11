<?php

/**
 * We define an interface to the way to get information
 * about the ranking of the records we have.
 * @author Ioannis Botis
 */
interface RankingAlgorithmInterface {

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
     * @param RankModel $rankModel the value the column has.
     * @return int the rank of the row.
     */
    public function getRank(RankModel $rankModel);

    /**
     * Find the rows at a specific rank.
     * @param int $rank
     * @param int $total
     * @return RankModel[]
     */
    public function getRowsAtRank($rank, $total = 1);
}

?>
