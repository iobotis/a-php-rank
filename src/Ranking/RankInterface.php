<?php
/**
 * @author Ioannis Botis
 * @date 10/1/2017
 * @version: RankModel.php 5:48 μμ
 * @since 10/1/2017
 */

namespace Ranking;

/**
 * Interface RankInterface
 * This represents an object that is ranked.
 *
 * @package Ranking
 */
interface RankInterface
{

    /**
     * RankInterface constructor.
     * This functions as a service to calculate the score.
     *
     * @param AlgorithmInterface $ranking
     */
    public function __construct(AlgorithmInterface $ranking);

    /**
     * Provide all the attributes needed by the algorithm.
     *
     * @return mixed
     */
    public function getAttributes();

    /**
     * Get the score of our Object.
     *
     * @return integer
     */
    public function getScore();

    /**
     * Get the rank of our Object.
     *
     * @return integer
     */
    public function getRank();
}