<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author jb
 */
interface RankingAlgorithmInterface {
    
    public function isReady();

    public function run();
    
    public function getRank($primary_key);
}

?>
