<?php

/**
 * We define an interface to the way to get information
 * about the ranking of the records we have.
 * @author Ioannis Botis
 */
interface RankingAlgorithmInterface {
    
    public function isReady();

    public function run();
    
    public function getRank($primary_key);
}

?>
