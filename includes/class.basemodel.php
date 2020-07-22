<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Shared methods for models
 */
class BaseModel {

    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    /**
     * get the labels from the source data. 
     * format the data to be used by chart.js
     */
    protected function prepareLabels( $dataset ) {
        $labels = array_map( function ($row) {
            return date( "d/n", strtotime ( $row ) );
        }, $dataset );
        array_shift( $labels ); // We report differences with day before, so lose the first element of the array
        return $labels;
    }


    /**
     * Change the cumulative values to daily growth values.
     * Format the data to be used by chart.js
     */
    protected function prepareDataSerie( $dataset ) {
        // NOTICE: the RIVM dataset for 20/03 is probably corrupt. 
        // So set it to the value of next day to remove strange effect in graph
        //$dataset[7] = $dataset[8];

        // create an array with values of the precious day so we can substract those values from today
        $dataset_next_day = $dataset;
        array_shift( $dataset_next_day ); // shift the array one to the left to create 
        
        // substract the value with the value of the previus day
        $data = array_map( function ($today, $tomorrow) {
            if ( isset( $tomorrow ) )
            return $tomorrow - $today ;
        }, $dataset, $dataset_next_day );
        array_pop( $data );  // last value equals NULL so discard
        return $data;
    }





}
