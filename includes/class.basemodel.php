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
    const SERIES_ALL_SORTED_BY_DATE = 'SERIES_ALL_SORTED_BY_DATE';
    protected $seriesSortedByDate;
    protected $seriesSortedByCity;

    
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
            return date( "j/n", strtotime ( $row ) );
        }, $dataset );
        array_shift( $labels ); // We report differences with day before, so lose the first element of the array
        return $labels;
    }


    /**
     * Change the cumulative values to daily growth values.
     * Format the data to be used by chart.js
     */
    protected function prepareDataSerie( $dataset ) {
        // create an array with values of the previous day so we can substract those values from today
        $dataset_next_day = $dataset;
        array_shift( $dataset_next_day ); // shift the array one to the left to create 
        
        // substract the value with the value of the previous day
        $data = array_map( function ($today, $tomorrow) {
            if ( isset( $tomorrow ) )
            return $tomorrow - $today ;
        }, $dataset, $dataset_next_day );
        array_pop( $data );  // last value equals NULL so discard
        return $data;
    }


    /**
     * Get data series sorted by date
     */
    protected function getSeriesSortedByDate() {
        if ( ! empty( $this->seriesSortedByDate ) ) return;

        $this->seriesSortedByDate  = FileManager::readFromCache( 'series', FileManager::CACHE_FILE_SERIES_SORTED_BY_DATE ); 
        if ( $this->seriesSortedByDate !== false ) return;

        $this->seriesSortedByDate = $this->getSortedSeries( 'ByDate' );
        return;
    }

    
    /**
     * Get data series sorted by city
     */
    protected function getSeriesSortedByCity( ) {
        if ( ! empty( $this->seriesSortedByCity ) ) return;

        $this->seriesSortedByCity  = FileManager::readFromCache( 'series', FileManager::CACHE_FILE_SERIES_SORTED_BY_CITY ); 
        if ( $this->seriesSortedByCity !== false ) return;

        $this->seriesSortedByCity = $this->getSortedSeries( 'ByCity' );
        return;
    }


    /**
     * Get data series and save them to cache
     */
    private function getSortedSeries( $type ) {
        // get and sort the data from source file
        $series = FileManager::readCsvRIVM( FileManager::SERIES_WITHOUT_PROVINCE );

        $cityname = array_column( $series, FileManager::HEADER_CITY_NAME );
        $date = array_column( $series, FileManager::HEADER_DATE );

        // sort for cache file Sorted By Date.
        array_multisort( $date, SORT_ASC, $cityname, SORT_ASC, $series );
        FileManager::saveToCache( 'series', $series, FileManager::CACHE_FILE_SERIES_SORTED_BY_DATE );
        if(  $type == 'ByDate' ) $return = $series; 

        // sort for cache file Sorted By City.
        array_multisort( $cityname, SORT_ASC, $date, SORT_ASC, $series );
        FileManager::saveToCache( 'series', $series, FileManager::CACHE_FILE_SERIES_SORTED_BY_CITY );
        if(  $type == 'ByCity' ) $return = $series;
        
        return $return;

    }


}