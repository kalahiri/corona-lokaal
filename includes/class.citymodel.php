<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class CityModel extends BaseModel {

    private $seriesSortedOnCity;
    private $listOfCities;

    
    /**
     * Constructor
     */
    public function __construct() {
        if ( FileManager::isUpdateSourceFileAvailable() ) {
            FileManager::getRemoteFile();
        }
    }

    /**
     * Get the data to create graphs
     */
    public function getChartData( $city, $ajax = true ) {
        if ( empty( $this->listOfCities ) ) {
            $this->listOfCities = FileManager::readListOfCities();
        }
        if ( ! in_array( $city, array_column( $this->listOfCities, FileManager::HEADER_CITY_NAME ) ) ) {
            exit();
        } 

        $cityData = $this->readCityData( $city );

        $labels = $this->prepareLabels( array_column( $cityData, FileManager::HEADER_DATE ) );
        $reported = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_REPORTED ) );
        $hospitalized = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_HOSPITALIZED ) );
        $deceased = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_DECEASED ) );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', $city );
            $ajaxResponse->send();
        }
    }
  

    /**
     * Get the data for a specific city.
     * Select the rows where (city name == $city) out of the complete dataset.
     * return this subset of rows as a new array. 
     */
    private function readCityData( $city ) {
        $series = FileManager::readSeries( FileManager::SERIES_SORTED_BY_CITY );
        $cityData = array_filter( $series, function ($row) use ($city) {
            return $row[ FileManager::HEADER_CITY_NAME ] == $city;
        });
       return $cityData;
    }


    /**
     * Get a list of available cities.
     * First, check if a cached version is available.
     * If not get them from the source file.  
     */
    public function getListOfCities() {
        if ( empty( $this->listOfCities ) ) {
            $this->listOfCities = FileManager::readListOfCities();
        }
        return $this->listOfCities;
    }


    /**
     * Get a list of cities with highest increase of reported cases
     */
    public function getTopCities() {
        if( false ) { //FileManager::isCacheAvailable( FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE ) ) {
            $seriesDifference = FileManager::readFromCache( 'seriesDifference', FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
        } else {
            $end = $this->getLastDate();
            $seriesAverageEnd = $this->getAverage( $end, FileManager::HEADER_REPORTED );
            $seriesAverageWeekBefore = $this->getAverage( $end - ( 7 * 86400 ), FileManager::HEADER_REPORTED, 7 ); //week before = 7*24*60*60.
            $seriesDifference = $this->sortTopCities( $seriesAverageEnd, $seriesAverageWeekBefore );
            FileManager::saveToCache( 'seriesDifference', $seriesDifference, FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
        }
        $slice = array_slice ( $seriesDifference , 0, 10 , true );
        $topCities = array();
        foreach( $slice as $row ) {
            $key = $row[FileManager::HEADER_CITY_NAME];
            $topCities[ $key ] = round( $row[FileManager::HEADER_REPORTED] * 10 ) / 10;
        }
        return $topCities;
    }



    public function getInhabitants() {
        FileManager::readNumberOfInhabitants();
    }


    /**
     * Calculate difference of values between two identical arrays
     * Notice: If difference is smaller than a $threshold factor the difference
     *         is considered to be insignificant and set to zero.
     */
    private function sortTopCities( $seriesEnd, $seriesBegin, $threshold = 1.2, $sorted = 'desc' ) {
        $i=0;
        $diff = array();

        $list = FileManager::readNumberOfInhabitants();
        $keys = array_column($list, FileManager::HEADER_CITY_CODE );
        $listOfInhabitants =  array_combine( $keys, $list );
        unset( $list, $keys );

        $header = array( FileManager::HEADER_CITY_CODE, FileManager::HEADER_CITY_NAME, FileManager::HEADER_REPORTED );
        foreach( $seriesEnd as $key => $row ) {
            
            $cityCode = $row[FileManager::HEADER_CITY_CODE];
            $cityName = $row[FileManager::HEADER_CITY_NAME];
            $inhabitants = $listOfInhabitants[ $row[FileManager::HEADER_CITY_CODE] ][ FileManager::HEADER_INHABITANTS];
            $startVal = $seriesBegin[ $key ][FileManager::HEADER_REPORTED];
            $endVal = $seriesEnd[ $key ][FileManager::HEADER_REPORTED];

            if ( $endVal - $startVal < $threshold ) $rankingVal = 0;
            else $rankingVal = ( ( ( $endVal - $startVal ) / $inhabitants ) * 100000 );

            $diff[] = array_combine( $header, array( $cityCode, $cityName, $rankingVal ) );
        }
        // sort the data
        $reportedIncrease  = array_column($diff, FileManager::HEADER_REPORTED );
        array_multisort( $reportedIncrease, SORT_DESC, $diff );
       // var_dump( $diff  ); exit();
        return $diff;
    }


    /**
     * Get average starting from starting from $startingDate to $days later.
     * Notice: the series contain cumulative values, while we are interested in average of growth
     * Notice: The average is calculated over growth in $days, so we need $days + 1 values. 
     */
    private function getAverage( $endDate, $column, $days = 3 ){
        // Get the necessary values ready
        $startDate =  $endDate - ( ( $days + 1 ) * 86400 ); // $days*24*60*60;
        $dateColumn = array_column ( $this->seriesSortedOnDate, FileManager::HEADER_DATE );
        $startIndex = array_search( date( 'Y-m-d', $startDate ), $dateColumn );
        $endIndex = array_search( date( 'Y-m-d', $endDate ) , $dateColumn );

        // Get begin slice
        $slice =  array_slice ( $this->seriesSortedOnDate , $startIndex, NULL, true );
        $baseValue = array();
        $date = $slice[ $startIndex ][ FileManager::HEADER_DATE ];
        foreach( $slice as $row ){
            if ( $row[ FileManager::HEADER_DATE ] != $date ) break;
            $baseValue[ $row[FileManager::HEADER_CITY_CODE] ] = $row[FileManager::HEADER_REPORTED];
        }

        // Get end slice and calculate the difference and divide by $days
        $slice =  array_slice ( $this->seriesSortedOnDate , $endIndex, NULL, true );
        $endValue = array();
        $header = array( FileManager::HEADER_CITY_CODE, FileManager::HEADER_CITY_NAME, FileManager::HEADER_REPORTED );
        $date = $slice[ $endIndex ][ FileManager::HEADER_DATE ];
        foreach( $slice as $row ){
            if ( $row[ FileManager::HEADER_DATE ] != $date ) break; 
            $key = $row[ FileManager::HEADER_CITY_CODE  ];
            $val = $row[ FileManager::HEADER_REPORTED ];
            $endValue[] = array_combine( $header, array( $row[ FileManager::HEADER_CITY_CODE ],
                    $row[ FileManager::HEADER_CITY_NAME ], ( $val - $baseValue[ $key ] ) / $days ) );
        }
        return $endValue;
    }


    /**
     * Get the last (most recent) date in the data set.
     */
    public function getLastDate() {
        if ( empty( $this->seriesSortedOnDate ) ) {
            $this->seriesSortedOnDate = FileManager::readSeries( FileManager::SERIES_SORTED_BY_DATE );
        }
        $lastDate= end( $this->seriesSortedOnDate )[ FileManager::HEADER_DATE ];
        return strtotime( $lastDate );
    }






}
