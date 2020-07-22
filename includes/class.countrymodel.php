<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the data for the country
 */
class CountryModel extends BaseModel {

    private $seriesSortedByDate;
    private $totalsSortedByDate;
    

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    /**
     * Get the data for the whole country.
     */
    public function getChartData( $ajax= true ) {
        if ( empty( $this->totalsSortedByDate ) ) {
            $this->getTotalsSortedByDate();
        }

        $labels = $this->prepareLabels( array_keys( $this->totalsSortedByDate ) );

        $reported = $this->prepareDataSerie( array_column( $this->totalsSortedByDate, FileManager::HEADER_REPORTED ));
        $hospitalized = $this->prepareDataSerie( array_column( $this->totalsSortedByDate, FileManager::HEADER_HOSPITALIZED ));
        $deceased = $this->prepareDataSerie( array_column( $this->totalsSortedByDate, FileManager::HEADER_DECEASED ));

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', 'Nederland' );
            $ajaxResponse->addData( 'country', true );
            $ajaxResponse->send();
        }
    }


    /**
     * Get the growth numbers of today
     */
    public function getToday() {
        if ( empty( $this->totalsSortedByDate ) ) {
            $this->getTotalsSortedByDate();
        }
        $today = end( $this->totalsSortedByDate );
        $yesterday = prev( $this->totalsSortedByDate );
        
        $increase["reported"] = $today["reported"] - $yesterday["reported"];
        $increase["hospitalized"] = $today["hospitalized"] - $yesterday["hospitalized"];
        $increase["deceased"] = $today["deceased"] - $yesterday["deceased"];

        return $increase;




    }


    private function getTotalsSortedByDate() {
        if( FileManager::isCacheAvailable( FileManager::TOTALS_BY_DATE_CACHE_FILE ) ) {
            $this->totalsSortedByDate = FileManager::readFromCache( 'totals', FileManager::TOTALS_BY_DATE_CACHE_FILE );
        } else {
            if ( empty( $this->seriesSortedOnDate ) ) {
                $this->seriesSortedOnDate = FileManager::readSeries( FileManager::SERIES_SORTED_BY_DATE );
            }

            // get the totals per day
            $this->totalsSortedByDate = array_reduce( $this->seriesSortedOnDate, function( $accumulator, $item ) { 
                if ( ! isset( $accumulator ) || ! is_array( $accumulator ) ) $accumulator = array();
                if ( isset( $accumulator[ $item[ FileManager::HEADER_DATE ] ] ) 
                                && isset( $accumulator[ $item[ FileManager::HEADER_DATE ] ]["reported"] ) ) {
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_REPORTED] += $item[FileManager::HEADER_REPORTED];
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_HOSPITALIZED] += $item[FileManager::HEADER_HOSPITALIZED];
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_DECEASED] += $item[FileManager::HEADER_DECEASED];
                } else {
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_REPORTED] = $item[FileManager::HEADER_REPORTED];
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_HOSPITALIZED] = $item[FileManager::HEADER_HOSPITALIZED];
                    $accumulator[ $item[FileManager::HEADER_DATE] ][FileManager::HEADER_DECEASED] = $item[FileManager::HEADER_DECEASED];
                }
                return $accumulator;
            });

            FileManager::saveToCache( 'totals', $this->totalsSortedByDate, FileManager::TOTALS_BY_DATE_CACHE_FILE );
        }
        return true;
    }





}
