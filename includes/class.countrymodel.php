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
    private $totalsSortedByDate;
    private $reportDate;

    
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

        // get the timestamp of today (12 o'clock midnight)
        $date = new DateTime(key( array_slice( $this->totalsSortedByDate, -1 ) ), new DateTimeZone('UTC') );
        $this->reportDate = $date->format('U');
        $increase["today"] = $this->reportDate;
        return $increase;
    }

  
    /**
     * Get the totals by day from the source file.
     * Save it to a cache file. 
     */
    private function getTotalsSortedByDate() {
        if( FileManager::isFileAvailable( FileManager::TOTALS_BY_DATE_CACHE_FILE ) ) {
            $this->totalsSortedByDate = FileManager::readFromCache( 'totals', FileManager::TOTALS_BY_DATE_CACHE_FILE );
        } else {
            if( empty( $seriesCountrySortedByDate ) ) {
                // get the data from source file and sort it by date ASC and city ASC
                $seriesCountrySortedByDate = FileManager::readCsvRIVM( FileManager::SERIES_ALL );
                 $cityname  = array_column( $seriesCountrySortedByDate, FileManager::HEADER_CITY_NAME );
                $date = array_column( $seriesCountrySortedByDate, FileManager::HEADER_DATE );
                array_multisort( $date, SORT_ASC, $cityname, SORT_ASC,  $seriesCountrySortedByDate);
            }

            // get the totals per day by adding all rows with same date using array_reduce();
            $this->totalsSortedByDate = array_reduce( $seriesCountrySortedByDate, function( $accumulator, $item ) { 
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


    /**
     * get several sets of toals by day and by city.
     * This can be used to make animations of the map over a period of time.
     */
    public function getSetsOfTotalsByDay( $startdate, $endate = 0, $numberOfDays = 3 ) {
        $series = $this->getSeriesSortedByDate();
    }


    /**
     * Get the new patients in ICU of today
     */
    public function getNICEtoday() {
        $seriesNice = $this->getNICESeries();
        if ( ! is_array( $seriesNice ) ) return false;
        $date = date( "Y-m-d", $this->reportDate );
        return array( "icuIntake" => $seriesNice[ $date ] , "today" => $this->reportDate );
    }


    /**
     * Get NICE data series and save them to cache
     */
    private function getNICESeries() {
        if( FileManager::isFileAvailable( FileManager::CACHE_FILE_NICE_NEW_INTAKE ) ) {
            $seriesNice = FileManager::readFromCache( 'seriesNice', FileManager::CACHE_FILE_NICE_NEW_INTAKE ); 
            if ( $seriesNice !== false ) return $seriesNice;
        }
        // No cache available so read the source file.
        $series = FileManager::readNICEJson( $this->reportDate );
        if ( ! is_array( $series ) ) return false;

        $yesterday = $this->reportDate - 86400;
        $todayDate = date( "Y-m-d", $this->reportDate );
        $yesterdayDate = date( "Y-m-d", $yesterday );
        $seriesYesterday = FileManager::readNICEJson( $yesterday );

        $keyToday = array_search ( $todayDate , array_column( $series[0], "date" ) );
        $keyYesterday = array_search ( $yesterdayDate , array_column( $seriesYesterday[0], "date" ) );
 
        if ( $keyToday == false || ! isset( $series[0][ $keyToday ]["value"] ) ) return false;

        $valueToday = $series[0][ $keyToday ]["value"] + $series[1][ $keyToday ]["value"];
        if ( $seriesYesterday != false && $keyYesterday != false && isset( $series[0][ $keyYesterday ]["value"] ) ) {
            // we have older data to calculate the total intake of past 24 hours
            $valueToday += $series[0][ $keyYesterday ]["value"] + $series[1][ $keyYesterday ]["value"];
            $valueYesterday = $seriesYesterday[0][ $keyYesterday ]["value"] + $seriesYesterday[1][ $keyYesterday ]["value"];
            $valueToday -=  $valueYesterday;
        }
        $seriesNice = array( $todayDate=> $valueToday );
        FileManager::saveToCache( 'seriesNice', $seriesNice, FileManager::CACHE_FILE_NICE_NEW_INTAKE );
        return $seriesNice;
        
    }


}