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
    private $listOfCities;
    private $listOfInhabitants;
    private $listOfTopCities;
    private $mapValues;

    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    /**
     * Get the data to create the charts for a specific city
     */
    public function getChartData( $city, $ajax = true ) {
        if ( empty( $this->listOfCities ) ) {
            $this->listOfCities = $this->getListOfCities();
        }
        if ( ! in_array( $city, array_column( $this->listOfCities, FileManager::HEADER_CITY_NAME ) ) ) {
            exit();
        } 

        // Get all data for one specific city
        $this->getSeriesSortedByCity();
        $cityData = array_filter( $this->seriesSortedByCity, function ($row) use ($city) {
            return $row[ FileManager::HEADER_CITY_NAME ] == $city;
        });

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
     * Get a list of cities with highest increase of reported cases in one week per 100.000 citizens
     * This list is based on the same dataset as the values and colors of the map.
     */
    public function getTopCities() {
        if( FileManager::isFileAvailable( FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE ) ) {
            $this->listOfTopCities = FileManager::readFromCache( 'topCities', FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
            return $this->listOfTopCities;
        }

        if ( empty( $this->mapValues ) ) {
            $this->getMap( false );
        }
        $reportedIncrease  = array_column( $this->mapValues, 'value' );
        array_multisort( $reportedIncrease, SORT_DESC, $this->mapValues );
        $this->listOfTopCities = array();
        $slice = array_slice( $this->mapValues , 0, 10 , true );
        foreach( $slice as $row ) {
            $key = $row[ 'cityname' ];
            $this->listOfTopCities[ $key ] = round( $row[ 'value' ] * 10 ) / 10;
        }
        FileManager::saveToCache( 'topCities', $this->listOfTopCities, FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
        return $this->listOfTopCities;
    }


    /**
     * Get data ready for the map of the Netherlands and to create the list of top cities.
     */
    public function getMap( $ajax = true ) {
        if( FileManager::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES ) 
            && FileManager::isFileAvailable( FileManager::CACHE_FILE_MAP_VALUES ) ) {
                $this->mapValues = FileManager::readFromCache( 'mapData', FileManager::CACHE_FILE_MAP_VALUES );
            return;
        }
        $today = $this->getSlice( $this->getLastDate(), 0, 1 );
        $lastWeek = $this->getSlice( $this->getLastDate() - 604800, 0, 1 );  // One week = 7*24*60*60
        $lastWeekReported = array_combine( array_column( $lastWeek, FileManager::HEADER_CITY_CODE ), array_column( $lastWeek, FileManager::HEADER_REPORTED ));

        $this->getInhabitantsPerCity();

        $dataset = array();
        $i=0;
        foreach( $today as $row ) {
            $citycode = $row[ FileManager::HEADER_CITY_CODE ];
            $dataset[$i]["citycode"] = $citycode;
            $dataset[$i]["cityname"] = $row[ FileManager::HEADER_CITY_NAME ];
            $dataset[$i]["reported"] = $row[ FileManager::HEADER_REPORTED ] - $lastWeekReported[ $citycode ];
            $value = round( 1000000 * ( $row[ FileManager::HEADER_REPORTED ] - $lastWeekReported[ $citycode ] )
                                            / $this->listOfInhabitants[ $citycode ][FileManager::HEADER_INHABITANTS] ) / 10;
            $dataset[$i]["value"] = ( $value < 0 ) ? 0 : $value;
            $i++;
        }
        FileManager::saveToCache( 'mapData', $dataset, FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES, 'javascript' );
        FileManager::saveToCache( 'mapData', $dataset, FileManager::CACHE_FILE_MAP_VALUES, 'php' );
        $this->mapValues = $dataset;
        return;
    }


    /**
     * Get max value of the map to set the colors.
     * Values can be greater than max value, but the color will be max value color.
     * $offset can be used to add to the max value with a fixed amount.
     */
    /* NOT USED AT THE MOMENT. MAX VALUE FIXED IN SCRIPT AT 120 */
    public function getMaxMap( $offset = 10 ) {
        if ( empty( $this->listOfTopCities ) ) {
            $this->getTopCities();
        }
        $maxVal = 0;
        foreach( array_slice( $this->listOfTopCities, 1, 5 ) as $mapValue ) {
            $maxVal += $mapValue;
        }
        $maxMap = (int) round( $maxVal / 5 ) + $offset;
        return ( $maxMap > 50 ) ? $maxMap : 50;
    }


    /**
     * Get a slice of the array for (a) specific date(s).
     * If endDate is given, it returns the number of days before and including the endDate 
     * If startDate is given, it returns the number of days after and including the startDate
     * $endDate { int } UNIX-timestamp 
     * $startDate { int } UNIX-timestamp 
     */
    private function getSlice( $startDate = 0, $endDate = 0, $days = 1 ) {
            if ( empty( $startDate ) ) {
                $startDate =  $endDate - ( $days * 86400 ); // $days*24*60*60;
            }
            if ( empty( $endDate ) ) {
                $endDate =  $startDate + ( $days * 86400 ); // $days*24*60*60;
            }
            $this->getSeriesSortedByDate();

            $dateColumn = array_column ( $this->seriesSortedByDate, FileManager::HEADER_DATE );
            $startIndex = array_search( date( 'Y-m-d', $startDate ), $dateColumn );
            $endIndex = array_search( date( 'Y-m-d', $endDate ) , $dateColumn );
            $length = ( $endIndex ) ? ( $endIndex - $startIndex ) : NULL;
            return  array_slice ( $this->seriesSortedByDate , $startIndex, $length , true );
    }


    /**
     * Get a list of available cities.
     * First, check if a cached version is available.
     * If not get them from the source file.  
     */
    public function getListOfCities() {
        if ( ! empty( $this->listOfCities ) ) return $this->listOfCities;
        
        $this->listOfCities = FileManager::readFromCache( 'cities', FileManager::CACHE_FILE_LIST_OF_CITIES ); 
        if ( $this->listOfCities !== false ) return $this->listOfCities;

        $this->listOfCities = FileManager::readCsvRIVM( FileManager::LIST_OF_CITIES );

        // sort by city name
        $cityname  = array_column($this->listOfCities, FileManager::HEADER_CITY_NAME );
        array_multisort($cityname, SORT_ASC, $this->listOfCities);
        FileManager::saveToCache( 'cities', $this->listOfCities, FileManager::CACHE_FILE_LIST_OF_CITIES );
       
        return $this->listOfCities;
    }


    /**
     * Gets the list of inhabitants per city
     * @return { array } Array with $key = citycode and $val = number of inhabitants.
     * First check if a cache file is available. If not, read the data from CBS source file
     */
    private function getInhabitantsPerCity() {
        if ( ! empty( $this->listOfInhabitants ) ) return;

        $this->listOfInhabitants = FileManager::readFromCache( 'inhabitants', FileManager::CACHE_FILE_INHABITANTS_PER_CITY ) ;
        if ( $this->listOfInhabitants !== false ) return;

        $list = FileManager::readCsvCBS();
        $keys = array_column($list, FileManager::HEADER_CITY_CODE );
        $this->listOfInhabitants = array_combine( $keys, $list );
        FileManager::saveToCache( 'inhabitants', $this->listOfInhabitants, FileManager::CACHE_FILE_INHABITANTS_PER_CITY );
        return;
    }

    
    /**
     * Get the last (most recent) date in the data set.
     */
    public function getLastDate() {
        $this->getSeriesSortedByDate();
        $lastDate= end(  $this->seriesSortedByDate )[ FileManager::HEADER_DATE ];
        return strtotime( $lastDate );
    }


}
