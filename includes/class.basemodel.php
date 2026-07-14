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
    // const ONE_DAY = 86400; // 24 * 60 * 60 in seconds
    const TIME_ZONE = 'Europe/Amsterdam';
    const NICE = 'nice';
    const LCPS = 'lcps';
    const RIVM = 'rivm';

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
        if ( ! $this->checkArray( $dataset ) ) {
            new LogException( 'Empty dataset' );
        }
        $labels = array_map( function ($row) {
            return date( "j/n", strtotime ( $row ) );
        }, $dataset );
        // OLD: cumulative: array_shift( $labels ); // We report differences with day before, so lose the first element of the array

        if ( ! $this->checkArray( $labels ) ) {
            new LogException( 'Empty labels' );
        }
        return $labels;
    }


    /**
     * Change the cumulative values to daily growth values.
     * Format the data to be used by chart.js
     */
    protected function prepareDataSerie( $dataset ) {
        if ( ! $this->checkArray( $dataset ) ) {
            new LogException( 'Empty dataset' );
        }
        // create an array with values of the previous day so we can substract those values from today
        $dataset_next_day = $dataset;
        array_shift( $dataset_next_day ); // shift the array one to the left to create 
        
        // substract the value with the value of the previous day
        $data = array_map( function ($today, $tomorrow) {
            if ( isset( $tomorrow ) )
            return $tomorrow - $today ;
        }, $dataset, $dataset_next_day );
        array_pop( $data );  // last value equals NULL so discard

        if ( ! $this->checkArray( $data ) ) {
            new LogException( 'Empty data' );
        }
        return $data;
    }



    /**
     * make corrections for some administrative problems within the RIVM file
     * - deceased for a couple of cities need correction
     */
    protected function makeCorrections( $series ) {
        $corrections_20200926 = array (
            166=>7,
            150=>3,
            177=>3,
            160=>2,
            175=>2,
            148=>1,
            193=>1,
            1708=>1,
            1896=>1
        );
        $citycode = array_column( $series, FileManager::HEADER_CITY_CODE );
        $date = array_column( $series, FileManager::HEADER_DATE );
        array_multisort( $date, SORT_DESC, $citycode, SORT_ASC, $series );
        foreach( $series as $key => $row ) {
            if ( $row[ FileManager::HEADER_DATE ] == "2020-09-25" ) break;
            $index = $row[ FileManager::HEADER_CITY_CODE ];
            if ( isset( $corrections_20200926[ $index ] ) ) {
                $series[ $key ][ FileManager::HEADER_DECEASED ] -= $corrections_20200926[ $index ];
            }
        }

/*        
        $date = array_column( $series, FileManager::HEADER_DATE );
        $key = array_search( "2022-02-08", $date );
        if( $key > 0 ) {
            do {
               $series[ $key ][ FileManager::HEADER_REPORTED ] = "0";
               // $series[ $key ]["reported"] = "0";
              // var_dump( $series[ $key ][ FileManager::HEADER_DATE ] );
               $key++;
            } while ( $series[ $key ][ FileManager::HEADER_DATE ] == "2022-02-08" );
        }
*/



        return $series;
    }


    /**
     * Check if a variable is a not empty array. 
     */
    protected function checkArray( $array ) {
        return is_array( $array ) && ! empty( $array ); 
    }



}