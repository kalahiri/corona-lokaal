<?php
/**
 * This file is written by Kalahiri in 2020-2021 
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

    private $seriesVaccinatedPerCity;

    
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
        $firstCharacter = substr( $city, 0, 1);
        $seriesOneCharacter = $this->getSeriesSortedByCity( $firstCharacter );
        $cityData = array_filter( $seriesOneCharacter, function ( $row ) use ($city) {
            return $row[ FileManager::HEADER_CITY_NAME ] == $city;
        });

        $hospitalizedCumulative = array_column( $cityData, FileManager::HEADER_HOSPITALIZED );
        $validHospitalData = ( end( $hospitalizedCumulative ) > 0 ? true : false );

       // var_dump( $cityData );


        $labels = $this->prepareLabels( array_column( $cityData, FileManager::HEADER_DATE ) );
        $reported = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_REPORTED ) );
        $deceased = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_DECEASED ) );
        $hospitalized = $this->prepareDataSerie( array_column( $cityData, FileManager::HEADER_HOSPITALIZED ) );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', $city );
            $ajaxResponse->addData( 'validHospitalData', $validHospitalData );
            $ajaxResponse->send();
        } else {
            return $cityData;
        }
    }
 

    /**
     * Get a list of cities with highest increase of reported cases in one week per 100.000 citizens
     * This list is based on the same dataset as the values and colors of the map.
     */
    public function getTopCities() {
        if( FileUtils::isFileAvailable( FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE ) ) {
            $this->listOfTopCities = FileManager::readFromCache( FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
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
        FileManager::saveToCache( $this->listOfTopCities, FileManager::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
        return $this->listOfTopCities;
    }


    /**
     * Get data ready for the map of the Netherlands and to create the list of top cities.
     */
    public function getMap( $ajax = true ) {

        if( FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES ) 
            && FileUtils::isFileAvailable( FileManager::CACHE_FILE_MAP_VALUES ) ) {
                $this->mapValues = FileManager::readFromCache( FileManager::CACHE_FILE_MAP_VALUES );
            return;
        }

        $this->getSeriesSortedByDate(); // get the series if not yet available.
        $dateColumn = array_column ( $this->seriesSortedByDate, FileManager::HEADER_DATE );

        // find last date in series.
        $lastDate= end(  $this->seriesSortedByDate )[ FileManager::HEADER_DATE ];
        $lastDateTime = new DateTime( $lastDate , new DateTimeZone( self::TIME_ZONE ) );

        // select last reported cases for all cities ( i.e. get an array with reported cases of 352 cities ) 
        $dateTime = clone $lastDateTime;
        $startIndex = array_search( $dateTime->format( 'Y-m-d' ), $dateColumn );
        $dateTime->modify('-1 days');
        $endIndex = array_search( $dateTime->format( 'Y-m-d' ), $dateColumn );
        $today = array_slice ( $this->seriesSortedByDate, $startIndex, $startIndex - $endIndex, true );

        // select reported cases for all cities from a week ago ( i.e. get an array with reported cases of 352 cities ) 
        $dateTime = clone $lastDateTime;
        $dateTime->modify('-6 days');
        $startIndex = array_search( $dateTime->format( 'Y-m-d' ), $dateColumn );
        $endIndex = 0;
        $lastWeek = array_slice ( $this->seriesSortedByDate, 0, $startIndex - $endIndex, true );

        // maak de gegevens van vorige makkelijk opzoekbaar to substract
        $lastWeekReported = array_combine( array_column( $lastWeek, FileManager::HEADER_CITY_CODE ), array_column( $lastWeek, FileManager::HEADER_REPORTED ));




        $this->getInhabitantsPerCity(); // to create $this->listOfInhabitants 








        // TODO HERINDELING GEVACCINEERD!!
 $this->getSeriesVaccinatedPerCity();
    //    var_dump( $this->seriesVaccinatedPerCity ); exit();













        // substract the last reported cases with the reported cases from last week. 
        // add other data , like vaccination coverage.
        $dataset = array();
        $i=0;
        foreach( $today as $row ) {
            $citycode = $row[ FileManager::HEADER_CITY_CODE ];
            $dataset[$i]["citycode"] = $citycode;
            $dataset[$i]["cityname"] = $row[ FileManager::HEADER_CITY_NAME ];
            $dataset[$i]["reported"] = $row[ FileManager::HEADER_REPORTED ] - $lastWeekReported[ $citycode ];
            if( isset( $this->listOfInhabitants[ $citycode ][FileManager::HEADER_INHABITANTS] ) ) {
                $value = round( 1000000 * ( $row[ FileManager::HEADER_REPORTED ] - $lastWeekReported[ $citycode ] )
                                            / $this->listOfInhabitants[ $citycode ][FileManager::HEADER_INHABITANTS] ) / 10;
            } else {
                new LogException( 'citycode not found', __LINE__ );
                $value = -1;
            }
            $dataset[$i]["value"] = ( $value < 0 ) ? 0 : $value;
            if( isset( $this->seriesVaccinatedPerCity[ $citycode ] ) ) {
                $dataset[$i][ "vaxxed" ] =  $this->seriesVaccinatedPerCity[ $citycode ];
            } else $dataset[$i][ "vaxxed" ] = '-';
            $i++;
        }
        
        // save the dataset to cache.
        FileManager::saveToCache( $dataset, FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES, FileManager::JAVASCRIPT, 'mapData' );
        FileManager::saveToCache( $dataset, FileManager::CACHE_FILE_MAP_VALUES );
        $this->mapValues = $dataset;
        
        return;
    }


    /**
     * Get a slice of the array for (a) specific date(s).
     * If endDate is given, it returns the number of days before and including the endDate 
     * If startDate is given, it returns the number of days after and including the startDate
     * $endDate { int } UNIX-timestamp 
     * $startDate { int } UNIX-timestamp 
     */
    /*
    private function getSlice( $dateTime, $days = 1 ) {
            $this->getSeriesSortedByDate();
            var_dump( array_slice ( $this->seriesSortedByDate , 150, -100 ) ) ; exit();

            $dateColumn = array_column ( $this->seriesSortedByDate, FileManager::HEADER_DATE );
            $startIndex = array_search( $dateTime->format( 'Y-m-d' ), $dateColumn );

            echo PHP_EOL . PHP_EOL. '------ nieuw run' . PHP_EOL;
            var_dump( $startIndex );

            var_dump( $this->seriesSortedByDate[ $startIndex ] );
            var_dump( $dateTime);
            var_dump( $dateTime->format( 'Y-m-d' ) );
            echo '-------';
            $dateTime->modify('-' . $days . ' days');
            var_dump( $dateTime);
            var_dump( $dateTime->format( 'Y-m-d' ) );
            echo '-------';
            $endIndex = array_search( $dateTime->format( 'Y-m-d' ), $dateColumn );
            var_dump( $endIndex ); 
            var_dump( $this->seriesSortedByDate[ $endIndex ] );

            $length = ( $endIndex ) ? ( $startIndex - $endIndex ) : NULL;
            var_dump( $length );

            return  array_slice ( $this->seriesSortedByDate , $startIndex, $length , true );
    }
    */


    /**
     * Get a list of available cities.
     * First, check if a cached version is available.
     * If not get them from the source file.
     * 
     * List of cities is retrieved from source file: 
     * FileManager::RIVM_SOURCE_CSV_FILE  = 'data/source_files/gemeenten/COVID-19_aantallen_gemeente_cumulatief.csv'
     */
    public function getListOfCities() {
        if ( ! empty( $this->listOfCities ) ) return $this->listOfCities;

        // get cache if available
        $this->listOfCities = FileManager::readFromCache( FileManager::CACHE_FILE_LIST_OF_CITIES ); 
        if ( $this->checkArray($this->listOfCities) ) return $this->listOfCities;
        
        // get source and create new cache
        $this->getInhabitantsPerCity();
        // var_dump( $this->listOfInhabitants ); exit();

        $i=0;
        foreach( $this->listOfInhabitants as $row ) {
            if( $row[ FileManager::HEADER_CITY_NAME ] == "'s-Gravenhage" ) $row[ FileManager::HEADER_CITY_NAME ]  = 'Den Haag';
            if( $row[ FileManager::HEADER_CITY_NAME ] == "'s-Hertogenbosch" ) $row[ FileManager::HEADER_CITY_NAME ]  = 'Den Bosch';
            $this->listOfCities[ $i ][ FileManager::HEADER_CITY_CODE ] = $row[ FileManager::HEADER_CITY_CODE ];
            $this->listOfCities[ $i ][ FileManager::HEADER_CITY_NAME ] = $row[ FileManager::HEADER_CITY_NAME ];
            $i++;
        }
        array_multisort( array_column( $this->listOfCities, FileManager::HEADER_CITY_NAME ), SORT_ASC, $this->listOfCities );



        FileManager::saveToCache( $this->listOfCities, FileManager::CACHE_JAVASCRIPT_FILE_LIST_OF_CITIES, FileManager::JAVASCRIPT, 'cities' );
        FileManager::saveToCache( $this->listOfCities, FileManager::CACHE_FILE_LIST_OF_CITIES );
        return $this->listOfCities;

        /*
        $this->getSeriesSortedByDate();
        $i=0;
        $firstCode = 0;
        foreach( $this->seriesSortedByDate as $row ) {
            if( $firstCode == $row[ FileManager::HEADER_CITY_CODE ] ) break;
            if( 0 == $firstCode ) $firstCode = $row[ FileManager::HEADER_CITY_CODE ];
            $this->listOfCities[ $i ][ FileManager::HEADER_CITY_CODE ] = $row[ FileManager::HEADER_CITY_CODE ];
            $this->listOfCities[ $i ][ FileManager::HEADER_CITY_NAME ] = $row[ FileManager::HEADER_CITY_NAME ];
            $i++;
        }

        FileManager::saveToCache( $this->listOfCities, FileManager::CACHE_JAVASCRIPT_FILE_LIST_OF_CITIES, FileManager::JAVASCRIPT, 'cities' );
        FileManager::saveToCache( $this->listOfCities, FileManager::CACHE_FILE_LIST_OF_CITIES );
        return $this->listOfCities;
        */
    }


    /**
     * Gets the list of inhabitants per city
     * @return { array } Array with $key = citycode and $val = number of inhabitants.
     * First check if a cache file is available. If not, read the data from CBS source file
     * 
     * Inhabitants per city is retrieved from source file: 
     * FileManager::CBS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/inwoners/CBS-aantal_inwoners_per_gemeente.2022.csv';
     */
    private function getInhabitantsPerCity() {
        if ( ! empty( $this->listOfInhabitants ) ) return;

        $this->listOfInhabitants = FileManager::readFromCache( FileManager::CACHE_FILE_INHABITANTS_PER_CITY ) ;
        if ( $this->listOfInhabitants !== false ) return;

        $list = FileManager::readCsvCBS();
        $keys = array_column($list, FileManager::HEADER_CITY_CODE );
        $this->listOfInhabitants = array_combine( $keys, $list );
        FileManager::saveToCache( $this->listOfInhabitants, FileManager::CACHE_FILE_INHABITANTS_PER_CITY );
        return;
    }

    
    /**
     * Get the last (most recent) date in the data set.
     */
    /*
    public function getLastDate() {
        $this->getSeriesSortedByDate();

    }
*/










    /***** READ DATA FROM SOURCE FILES *****/

   
    /**
     * Get data series sorted by city
     */
    public function getSeriesSortedByCity( $character, $createCacheFile = true ) {
        $cacheFile = substr( FileManager::CACHE_FILE_SERIES_SORTED_BY_CITY, 0, -4 ) . "." . $character . ".php";
        $seriesOneCharacter = FileManager::readFromCache( $cacheFile ); 
        if ( $seriesOneCharacter === false && $createCacheFile ) {
            $this->getSortedSeries( 'ByCity' );
            $seriesOneCharacter = FileManager::readFromCache( $cacheFile );
            if ( $seriesOneCharacter === false ) return false;
        } 
        return $seriesOneCharacter;
    }

    /**
     * Get data series sorted by date
     */
    protected function getSeriesSortedByDate() {
        if ( ! empty( $this->seriesSortedByDate ) ) return;

        $this->seriesSortedByDate  = FileManager::readFromCache( FileManager::CACHE_FILE_SERIES_SORTED_BY_DATE ); 
        if ( is_array( $this->seriesSortedByDate ) ) return;

        $this->seriesSortedByDate = $this->getSortedSeries( 'ByDate' );
        return;
    }


 
    /**
     * Get data series from the RIVM source file and save them to cache
     * - one series contains data for city charts. These are split in 26 individual cache files,
     *      each sorted on the first letter of the city name
     * - the other series contains the last seven days of
     * 
     * Source file: FileManager::RIVM_SOURCE_CSV_FILE  = 'data/source_files/gemeenten/COVID-19_aantallen_gemeente_cumulatief.csv'
     */
    //protected function getSortedSeries() {
    public function getSortedSeries( $type ) {
        // get and sort the data from source file
        $series = FileManager::readCsvRIVM( FileManager::SERIES_WITHOUT_PROVINCE );



        $series = $this->makeCorrections( $series );


        //var_dump( array_slice( $series, 0,1000 ) ); exit();


        $series = $this->gemeentelijkeHerindeling( $series );

        // sort and save cache per first character
        $date = array_column( $series, FileManager::HEADER_DATE );
        $cityname = array_column( $series, FileManager::HEADER_CITY_NAME );
        array_multisort( $cityname, SORT_ASC, $date, SORT_ASC, $series );
        unset( $cityname, $date );

        $series = $this->injectNiceHospitalData( $series );


       // var_dump( array_slice( $series, 0,1000 ) ); exit();


        // Qua snelheid maakt het allemaal weinig uit. Deze implementatie vind ik het meest logisch
        $current = 'A';
        $start = 0;
        $basename = substr( FileManager::CACHE_FILE_SERIES_SORTED_BY_CITY, 0, -4 );
        foreach($series as $key => $row) {
            if( $current != substr( $row[ FileManager::HEADER_CITY_NAME] , 0, 1)  ) {
                $cacheFile = $basename .  '.' . $current . '.php';
                FileManager::saveToCache( array_slice( $series, $start,$key-$start ), $cacheFile );
                $start = $key;
                $current = $row[ FileManager::HEADER_CITY_NAME][0];
            }
        }
        $cacheFile = $basename .  '.' . $current . '.php';
        FileManager::saveToCache( array_slice( $series, $start ), $cacheFile );

        // sort for cache file Sorted By Date.
        $date = array_column( $series, FileManager::HEADER_DATE );
        $cityname = array_column( $series, FileManager::HEADER_CITY_NAME );
        array_multisort( $date, SORT_ASC, $cityname, SORT_ASC, $series );
        unset( $date, $cityname );

        $lastDate= end(  $series )[ FileManager::HEADER_DATE ];
        $lastDateTime = new DateTime( $lastDate , new DateTimeZone('Europe/Amsterdam' ) );
        $lastDateTime->modify('-7 days');
        $date = array_column( $series, FileManager::HEADER_DATE );
        $startIndex = array_search( $lastDateTime->format( 'Y-m-d' ), $date );
        $sliceLastWeek = array_slice(  $series , $startIndex );

        FileManager::saveToCache( $sliceLastWeek , FileManager::CACHE_FILE_SERIES_SORTED_BY_DATE );
        return ( $type == 'ByDate') ? $sliceLastWeek : true;

    }


   /**
     * Inject the NICE hospital admissions in the data series for each city.
     * 
     * The RIVM hospital admissions are incomplete, so we use the NICE data instead.
     * First we read the RIVM series, then we replace the hospital admission by the NICE data
     * and then we go ahead processing the cache files.
     */
    public function injectNiceHospitalData( $series ) {
        $seriesNice = FileManager::readRIVM_NICEHospitalAdmissionCsv();
        array_multisort(    array_column( $seriesNice, FileManager::HEADER_CITY_CODE ), SORT_ASC,
                            array_column( $seriesNice, FileManager::HEADER_DATE ), SORT_ASC, $seriesNice);
        // $cityName = array_column( $seriesNice, FileManager::HEADER_CITY_NAME );
/*
        $name = '';
        foreach( $seriesNice as $key => $row ) {
            if( $name != $row ["city"] ) {
                 echo $row ["city"] . '<br>';
                 $name = $row ["city"];
            }
        }
        foreach( $series as $row ) {
            if( $name != $row ["city"] ) {
                 echo $row ["city"] . '<br>';
                 $name = $row ["city"];
            }
        }
        exit();

        var_dump( array_slice( $seriesNice, 0, 1000 ) );
        var_dump( array_slice( $series, 0, 1000 ) );
        */

        //check if we have matching series
        // If not, the update is probably not available yet.
        // If no update yet, set all values to zero, so a notice can be displayed that data has to be updated.
        $endSeries = end( $series );
        $endSeriesNice = end( $seriesNice );
        $dateSeries = new DateTime( $endSeries[ FileManager::HEADER_DATE ] );
        $dateSeriesNice = new DateTime( $endSeriesNice[ FileManager::HEADER_DATE ] );
        $interval = $dateSeries->diff( $dateSeriesNice );
        if( $interval->d != 1 ) {   // Nice Data is always one day behind. If not yet available, set all to zero. 
            foreach( $series as $key => $row ) {
                $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] = 0;
            }
            // echo( 'no valid update yet' );
            return $series;
        }



        $seriesNice = $this->gemeentelijkeHerindelingZiekenhuisopnamesNice( $seriesNice );
        array_multisort(    array_column( $seriesNice, FileManager::HEADER_CITY_NAME ), SORT_ASC,
                            array_column( $seriesNice, FileManager::HEADER_DATE ), SORT_ASC, $seriesNice);


        // We have updated data, so lets start replacing teh hospital data in the $series by the $seriesNice (NICE data)
        $matchingError = 0;
        $keyNice = 0;
        $currentCityCode = 0;
        $date = new DateTime('2020-03-13');
        $date->modify('+1 day');    // shift one day since the NICE file lags by one day. 
        $startDate = $date->format('Y-m-d');
        foreach( $series as $key => $row ) { // iterate through all entries in $series
            // if( $keyNice  > 130000 ) exit();
            // Zet alles voor de gemeentelijke herindeling 
            //if( $currentCityCode !== $row[ FileManager::HEADER_CITY_CODE ] ) {


            if( $currentCityCode !== $row[ FileManager::HEADER_CITY_CODE ] ) { // new city, loose first value to shift the data
                if( $row[ FileManager::HEADER_DATE ] < $startDate ) { 
                    continue;
                }
                //echo "shift $currentCityCode<br>";
                $currentCityCode = $row[ FileManager::HEADER_CITY_CODE ];
                $sum = 0;
            }
            if( isset( $seriesNice[ $keyNice ] ) ) {
                while ( $row[ FileManager::HEADER_CITY_CODE ] !== $seriesNice[ $keyNice ][ FileManager::HEADER_CITY_CODE ] ) {
                    // echo 'matching error<br>';
                    $keyNice++;
                    if( ! isset( $seriesNice[ $keyNice ] ) ) {
                        new LogException( 'Offset not found in RIVM-NICE hospital admission - file '  
                            . FileManager::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE
                            . ' probably not updatet', __LINE__ );
                        break 2;
                    }
                }
                $sum += $seriesNice[ $keyNice ][ FileManager::HEADER_HOSPITALIZED ];
                $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] = $sum;

/*
                echo $series[ $key ][ FileManager::HEADER_CITY_CODE ] . ' - ' .
                     $series[ $key ][ FileManager::HEADER_CITY_NAME ] . ' - ' .
                     $seriesNice[ $keyNice ][ FileManager::HEADER_CITY_NAME ]  . ' - ' .
                     $series[ $key ][ FileManager::HEADER_DATE ] . ' - ' .
                     $seriesNice[ $keyNice ][ FileManager::HEADER_DATE ] . ' - ' .
                     $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] . '<br>';
                     if( $keyNice  > 1000 ) exit();
*/

                $keyNice++;
            }

        }
        return $series;
    }
















    /*** GEMEENTELIJKE HERINDELINGEN *** GEMEENTELIJKE HERINDELINGEN *** GEMEENTELIJKE HERINDELINGEN *** */



    private function gemeentelijkeHerindeling( $series ) {
        array_multisort(    array_column( $series, FileManager::HEADER_CITY_CODE ), SORT_ASC,
                            array_column( $series, FileManager::HEADER_DATE ), SORT_ASC, $series );
        $cityCode = array_column( $series, FileManager::HEADER_CITY_CODE );
        // var_dump( array_slice( $series, 0, 2000, true ) ); exit();
        $keysToUnsetArray = array();
        $seriesToAddArray = array();

        /* GEMEENTELIJKE HERINDELING JANUARI 2021 */


        // Haaren is in vier stukken gesplitst. De vier delen zijn bij vier verschillende gemeenten gevoegd. 
        // Find Haaren
        $currentCityCode = 788; // Haaren
        $nextCityCode = 794; // next city in de gesorteerde lijst is Helmond. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key; // next city in de gesorteerde lijst is Boxtel.
        $seriesHaaren = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        // find Vught (865) en tel het deel van Haaren erbij op.
        $key = array_search( 865, $cityCode );
        $slice = array_slice( $series, $key, 300, true );   // only 300 entries (days) before haaren was split up.
        $factor = 0.40;
        foreach( $slice as $key => $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            if ( ! isset( $seriesHaaren[ $date ] ) ) continue;
            $series[ $key ][ FileManager::HEADER_REPORTED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_REPORTED ] );
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_HOSPITALIZED ] );
            $series[ $key ][ FileManager::HEADER_DECEASED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_DECEASED ] ); 
        }
        // find Boxtel (757) en tel het deel van Haaren erbij op.
        $key = array_search( 757, $cityCode );
        $slice = array_slice( $series, $key, 300, true );   // only 300 entries (days) before haaren was split up.
        $factor = 0.15;
        foreach( $slice as $key => $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            if ( ! isset( $haaren[ $date ] ) ) continue;
            $series[ $key ][ FileManager::HEADER_REPORTED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_REPORTED ] );
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_HOSPITALIZED ] );
            $series[ $key ][ FileManager::HEADER_DECEASED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_DECEASED ] ); 
        }
        // find Tilburg en tel het deel van Haaren erbij op.
        $key = array_search( 855, $cityCode );
        $slice = array_slice( $series, $key, 300, true );
        $factor = 0.10;
        foreach( $slice as $key => $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            if ( ! isset( $haaren[ $date ] ) ) continue;
            $series[ $key ][ FileManager::HEADER_REPORTED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_REPORTED ] );
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_HOSPITALIZED ] );
            $series[ $key ][ FileManager::HEADER_DECEASED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_DECEASED ] ); 
        }
        // find Oisterwijk en tel het deel van Haaren erbij op.
        $key = array_search( 824, $cityCode );
        $slice = array_slice( $series, $key, 300, true );
        $factor = 0.35;
        foreach( $slice as $key => $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            if ( ! isset( $haaren[ $date ] ) ) continue;
            $series[ $key ][ FileManager::HEADER_REPORTED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_REPORTED ] );
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_HOSPITALIZED ] );
            $series[ $key ][ FileManager::HEADER_DECEASED ] += (int) round( $factor * $seriesHaaren[ $date ] [ FileManager::HEADER_DECEASED ] ); 
        }
        unset( $seriesHaaren );




        // Nieuwe gemeente Eemsdelta: Samenvoeging Appingedam (3), Delfzijl (10) en Loppersum (24)
        $seriesEemsdelta = array();
        foreach( $series as $key => $row ) {
            if( $row[ FileManager::HEADER_CITY_CODE ] > 24 ) break;

            if( $row[ FileManager::HEADER_CITY_CODE ] == 3 ||
                                    $row[ FileManager::HEADER_CITY_CODE ] == 10 ||
                                    $row[ FileManager::HEADER_CITY_CODE ] == 24  ) {
                if( ! isset( $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] ) ) {
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1979;
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Eemsdelta';
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ];
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ]; 
                } else {
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
                    $seriesEemsdelta[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 

                }
                $keysToUnsetArray[] = $key;
            }
        }




        /* GEMEENTELIJKE HERINDELING JANUARI 2022 */

        // samenvoeging beemster (370) bij purmerend (439) 
        // Find Beemster
        $currentCityCode = 370; // Beemster
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( 373, $cityCode ) - $key; // next city in de gesorteerde lijst is Bergen NH.
        $seriesBeemster = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        // Find purmerend en tel Beemster erbij op.
        $currentCityCode = 439; // Purmerend
        $key = array_search( $currentCityCode, $cityCode );
        foreach( $seriesBeemster as $row ) {
            if ( $row[ FileManager::HEADER_DATE ] !== $series[ $key ][ FileManager::HEADER_DATE ] ) {
                new LogException( 'datums komen niet overeen bij gemeentelijke herindeling');
                continue;
            }  
            $series[ $key ][ FileManager::HEADER_REPORTED ] += $row [ FileManager::HEADER_REPORTED ];
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += $row [ FileManager::HEADER_HOSPITALIZED ];
            $series[ $key ][ FileManager::HEADER_DECEASED ] += $row [ FileManager::HEADER_DECEASED ];
            $key++;
        }




        // samenvoeging  Boxmeer (756), Grave (786), Mill en Sint Hubert (815), Cuijk (1684) en Sint Anthonis (1702)
        // tot de nieuwe gemeente Land van Cuijk (1982)
        $seriesLandVanCuijk = array();

        // Find Boxmeer
        $currentCityCode = 756; // Boxmeer
        $nextCityCode = 757; // // next city in de gesorteerde lijst is Boxtel. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1982;
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Land van Cuijk';
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ];
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Find Grave
        $currentCityCode = 786; // Grave
        $nextCityCode = 788; // // next city in de gesorteerde lijst is Haaren. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Find Mill en Sint Hubert (815)
        $currentCityCode = 815; // Mill en Sint Hubert (815)
        $nextCityCode = 820; // // next city in de gesorteerde lijst is Nuenen. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Find Cuijk (1684)
        $currentCityCode = 1684; // Cuijk (1684)
        $nextCityCode = 1685; // // next city in de gesorteerde lijst is Landerd. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Sint Anthonis (1702)
        $currentCityCode = 1702; // Sint Anthonis (1702)
        $nextCityCode = 1705; // // next city in de gesorteerde lijst is Lingewaard (1705). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesLandVanCuijk[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }





        // samenvoeging Heerhugowaard (398) en Langedijk (416)
        // tot de nieuwe gemeente Dijk en Waard (1980) 
        $seriesDijkEnWaard = array();
        // Find Heerhugowaard
        $currentCityCode = 398; // Heerhugowaard 
        $nextCityCode = 399; // // next city in de gesorteerde lijst is Heilo. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1980;
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Dijk en Waard';
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ];
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Find Langedijk (416)
        $currentCityCode = 416; // Langedijk
        $nextCityCode = 417; // // next city in de gesorteerde lijst is Laren. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesDijkEnWaard[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesDijkEnWaard[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesDijkEnWaard[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }






        // samenvoeging Landerd (1685) en Uden (856)
        // tot de nieuwe gemeente Maashorst (1991)
        $seriesMaashorst = array();
        // Find Heerhugowaard
        $currentCityCode = 1685; // Landerd (1685)
        $nextCityCode = 1690; // next city in de gesorteerde lijst is De Wolden. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1991;
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Maashorst';
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ];
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ]; 
        }
        // Find Uden (856)
        $currentCityCode = 856; // Uden
        $nextCityCode = 858; // next city in de gesorteerde lijst is Valkenswaard. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( $nextCityCode, $cityCode ) - $key;
        $seriesCurrentCity = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        foreach( $seriesCurrentCity as $row ) {
            $date = $row[ FileManager::HEADER_DATE ];
            $seriesMaashorst[ $date ] [ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $seriesMaashorst[ $date ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            $seriesMaashorst[ $date ] [ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ]; 
        }




        /* GEMEENTELIJKE HERINDELING 24 MAART 2022 */

        // samenvoeging Weesp (0457) en Amsterdam (363)
        // Find Weesp
        $currentCityCode = 457; // Weesp (0457) 
        $key = array_search( $currentCityCode, $cityCode );
        $length = array_search( 473, $cityCode ) - $key; // next city in de gesorteerde lijst is Zandvoort (0473). We should stop there
        $seriesWeesp = array_slice( $series, $key, $length, true );
        for( $i = $key; $i < $key + $length; $i++) {
            $keysToUnsetArray[] = $i;
        }
        // Find Amsterdam en tel Weesp erbij op.
        $currentCityCode = 363; // Amsterdam
        $key = array_search( $currentCityCode, $cityCode );
        foreach( $seriesWeesp as $row ) {
            if ( $row[ FileManager::HEADER_DATE ] !== $series[ $key ][ FileManager::HEADER_DATE ] ) {
                new LogException( 'datums komen niet overeen bij gemeentelijke herindeling');
                continue;
            }  
            $series[ $key ][ FileManager::HEADER_REPORTED ] += $row [ FileManager::HEADER_REPORTED ];
            $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += $row [ FileManager::HEADER_HOSPITALIZED ];
            $series[ $key ][ FileManager::HEADER_DECEASED ] += $row [ FileManager::HEADER_DECEASED ];
            $key++;
        }





        // remove old cities and data
        foreach( $keysToUnsetArray as $key ) {
            unset( $series[ $key ] );
        }

        // add new cities and data
        foreach( $seriesEemsdelta as $row ) $series[] = $row;
        unset( $seriesEemsdelta );

        foreach( $seriesLandVanCuijk as $row ) $series[] = $row;
        unset( $seriesLandVanCuijk );

        foreach( $seriesDijkEnWaard as $row ) $series[] = $row;
        unset( $seriesDijkEnWaard );

        foreach($seriesMaashorst as $row ) $series[] = $row;
        unset( $seriesMaashorst );

        


        return $series;

    }







    /**
     * Convert hospitalizations from old cities to newly formed cities
     * This is only temporarily neccessary as the RIVM will convert the complete 
     * source file by the end of january. 
     */
    private function gemeentelijkeHerindelingZiekenhuisopnamesNice( $series ) {
        $cityCode = array_column( $series, FileManager::HEADER_CITY_CODE );
        // var_dump( array_slice( $series, 0, 2000, true ) ); exit();
        $keysToUnsetArray = array();
        $seriesToAddArray = array();


        /* GEMEENTELIJKE HERINDELING JANUARI 2022 */

        // samenvoeging beemster (370) bij purmerend (439) 
        // Find Beemster
        $currentCityCode = 370; // Beemster
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( 373, $cityCode ) - $key; // next city in de gesorteerde lijst is Bergen NH.
            $seriesBeemster = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            // Find purmerend en tel Beemster erbij op.
            $currentCityCode = 439; // Purmerend
            $key = array_search( $currentCityCode, $cityCode );
            foreach( $seriesBeemster as $row ) {
                if ( $row[ FileManager::HEADER_DATE ] !== $series[ $key ][ FileManager::HEADER_DATE ] ) {
                    new LogException( 'datums komen niet overeen bij gemeentelijke herindeling');
                    continue;
                }  
                $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += $row [ FileManager::HEADER_HOSPITALIZED ];
                $key++;
            }
        }



        // samenvoeging  Boxmeer (756), Grave (786), Mill en Sint Hubert (815), Cuijk (1684) en Sint Anthonis (1702)
        // tot de nieuwe gemeente Land van Cuijk (1982)
        $seriesLandVanCuijk = array();
        // Find Boxmeer
        $currentCityCode = 756; // Boxmeer
        $nextCityCode = 757; // // next city in de gesorteerde lijst is Boxtel. We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1982;
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Land van Cuijk';
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
        }
        // Find Grave
        $currentCityCode = 786; // Grave (786)
        $nextCityCode = 794; // // next city in de gesorteerde lijst is Helmond (794). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
        }
        // Find Mill en Sint Hubert (815)
        $currentCityCode = 815; // Mill en Sint Hubert (815)
        $nextCityCode = 820; // // next city in de gesorteerde lijst is Nuenen (820). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
        }
        // Find Cuijk (1684)
        $currentCityCode = 1684; // Cuijk (1684)
        $nextCityCode = 1685; // // next city in de gesorteerde lijst is Landerd (1685). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
        }
        // Sint Anthonis (1702)
        $currentCityCode = 1702; // Sint Anthonis (1702)
        $nextCityCode = 1705; // // next city in de gesorteerde lijst is Lingewaard (1705). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesLandVanCuijk[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
        }







        // samenvoeging Heerhugowaard (398) en Langedijk (416)
        // tot de nieuwe gemeente Dijk en Waard (1980) 
        $seriesDijkEnWaard = array();
        // Find Heerhugowaard
        $currentCityCode = 398; // Heerhugowaard 
        $nextCityCode = 399; // // next city in de gesorteerde lijst is Heilo (399). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
                $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1980;
                $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Dijk en Waard';
                $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );

            // Find Langedijk (416)
            $currentCityCode = 416; // Langedijk
            $nextCityCode = 417; // // next city in de gesorteerde lijst is Laren (417). We should stop there
            $key = array_search( $currentCityCode, $cityCode );
            if( false !== $key ) {
                $length = array_search( $nextCityCode, $cityCode ) - $key;
                $seriesCurrentCity = array_slice( $series, $key, $length, true );
                for( $i = $key; $i < $key + $length; $i++) {
                    $keysToUnsetArray[] = $i;
                }
                foreach( $seriesCurrentCity as $row ) {
                    $seriesDijkEnWaard[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
                }
                unset( $seriesCurrentCity );
            }
        }






        // samenvoeging Landerd (1685) en Uden (856)
        // tot de nieuwe gemeente Maashorst (1991)
        $seriesMaashorst = array();
        // Find Heerhugowaard
        $currentCityCode = 1685; // Landerd (1685)
        $nextCityCode = 1690; // next city in de gesorteerde lijst is De Wolden (1690). We should stop there
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( $nextCityCode, $cityCode ) - $key;
            $seriesCurrentCity = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            foreach( $seriesCurrentCity as $row ) {
                $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ]; 
                $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_CODE ] = 1991;
                $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_CITY_NAME ] = 'Maashorst';
                $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED]; 
            }
            unset( $seriesCurrentCity );
            // Find Uden (856)
            $currentCityCode = 856; // Uden
            $nextCityCode = 858; // next city in de gesorteerde lijst is Valkenswaard (858). We should stop there
            $key = array_search( $currentCityCode, $cityCode );
            if( false !== $key ) {
                $length = array_search( $nextCityCode, $cityCode ) - $key;
                $seriesCurrentCity = array_slice( $series, $key, $length, true );
                for( $i = $key; $i < $key + $length; $i++) {
                    $keysToUnsetArray[] = $i;
                }
                foreach( $seriesCurrentCity as $row ) {
                    $seriesMaashorst[ $row[ FileManager::HEADER_DATE ] ] [ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED]; 
                }
                unset( $seriesCurrentCity );
            }
        }


        
        /* GEMEENTELIJKE HERINDELING 24 MAART 2022 */


        // samenvoeging Weesp (0457) en Amsterdam (363)
        // Find wEESP
        $currentCityCode = 457; // Weesp
        $key = array_search( $currentCityCode, $cityCode );
        if( false !== $key ) {
            $length = array_search( 473, $cityCode ) - $key; // next city in de gesorteerde lijst is Zandvoort (0473)
            $seriesWeesp = array_slice( $series, $key, $length, true );
            for( $i = $key; $i < $key + $length; $i++) {
                $keysToUnsetArray[] = $i;
            }
            // Find Amsterdam en tel Weesp erbij op.
            $currentCityCode = 363; // Amsterdam
            $key = array_search( $currentCityCode, $cityCode );
            foreach( $seriesWeesp as $row ) {
                if ( $row[ FileManager::HEADER_DATE ] !== $series[ $key ][ FileManager::HEADER_DATE ] ) {
                    new LogException( 'datums komen niet overeen bij gemeentelijke herindeling');
                    continue;
                }  
                $series[ $key ][ FileManager::HEADER_HOSPITALIZED ] += $row [ FileManager::HEADER_HOSPITALIZED ];
                $key++;
            }
        }







        // remove old cities and data
        foreach( $keysToUnsetArray as $key ) {
            unset( $series[ $key ] );
        }


        foreach( $seriesLandVanCuijk as $row ) $series[] = $row;
        unset( $seriesLandVanCuijk );

        foreach( $seriesDijkEnWaard as $row ) $series[] = $row;
        unset( $seriesDijkEnWaard );

        foreach($seriesMaashorst as $row ) $series[] = $row;
        unset( $seriesMaashorst );


        return $series;

    }



    private function gemeentelijkeHerindelingVaccinaties( $dataset ) {
        //$this->getInhabitantsPerCity(); // so we can use $this->listOfInhabitants;

        $rawlist = FileManager::readCsvCBS('2021');
        $listInhabitantsOld = array();
        foreach( $rawlist as $row ) {
            $listInhabitantsOld[ $row[ FileManager::HEADER_CITY_CODE ] ] = $row[ FileManager::HEADER_INHABITANTS ];
        }
        // samenvoeging beemster (370) bij purmerend (439) 
        // check if beemster (370) exists
        if( isset( $dataset[370]["citycode"] ) ) {
            $key1 = 370;
            $key2 = 439;

            $totalInhabitants = $listInhabitantsOld[ $key1  ] + $listInhabitantsOld[ $key2 ];
            $dataset[ $key2 ]["value"] = strval( round( ( ( $dataset[ $key2 ]["value"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["value"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants) );
            $dataset[ $key2 ]["agegroup_18_and_above"] = strval( round( ( ($dataset[ $key2 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants ) );
            $dataset[ $key2 ]["agegroup_12_to_17"] = strval( round( ( ($dataset[ $key2 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants ) );
            unset( $dataset[ $key1 ] );
        }



        // samenvoeging  Boxmeer (756), Grave (786), Mill en Sint Hubert (815), Cuijk (1684) en Sint Anthonis (1702)
        // tot de nieuwe gemeente Land van Cuijk (1982)
        if( isset( $dataset[756]["citycode"] ) ) {
            $key1 = 756;
            $key2 = 786;
            $key3 = 815;
            $key4 = 1684;
            $key5 = 1702;

            $keyTotal = 1982;

            $totalInhabitants = $listInhabitantsOld[ $key1  ] + $listInhabitantsOld[ $key2 ] +
                                        $listInhabitantsOld[ $key3 ] + $listInhabitantsOld[ $key4 ] +
                                        $listInhabitantsOld[ $key5  ];
            $dataset[ $keyTotal ][ 'citycode' ] = $keyTotal;
            $dataset[ $keyTotal ][ 'cityname' ] = 'Land van Cuijk';
            $dataset[ $keyTotal ]["value"] = strval( round( ( 
                   ( $dataset[ $key1 ]["value"] * $listInhabitantsOld[ $key1 ] ) +
                   ($dataset[ $key2 ]["value"] * $listInhabitantsOld[ $key2 ] ) +
                   ( $dataset[ $key3 ]["value"] * $listInhabitantsOld[ $key3 ] ) +
                   ($dataset[ $key4 ]["value"] * $listInhabitantsOld[ $key4 ] ) +
                   ( $dataset[ $key5 ]["value"] * $listInhabitantsOld[ $key5 ] ) )
                                / $totalInhabitants) );
            $dataset[ $keyTotal ]["agegroup_18_and_above"] = strval( round( (
                 ($dataset[ $key1 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key1 ] ) +
                 ($dataset[ $key2 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key2 ] ) +
                 ($dataset[ $key3 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key3 ] ) +
                 ($dataset[ $key4 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key4 ] ) +
                 ($dataset[ $key5 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key5 ] ) )
                             / $totalInhabitants ) );
            $dataset[ $keyTotal ]["agegroup_12_to_17"] = strval( round( ( 
                    ($dataset[ $key1 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key1 ] ) +
                    ($dataset[ $key2 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key2 ] ) +
                    ($dataset[ $key3 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key3 ] ) +
                    ($dataset[ $key4 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key4 ] ) +
                    ($dataset[ $key5 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key5 ] ) )
                            / $totalInhabitants ) );
            unset( $dataset[ $key1 ],$dataset[ $key2 ],$dataset[ $key3],$dataset[ $key4 ],$dataset[ $key5 ] );
        }

        // samenvoeging Heerhugowaard (398) en Langedijk (416)
        // tot de nieuwe gemeente Dijk en Waard (1980)
        if( isset( $dataset[398]["citycode"] ) ) {
            $key1 = 398;
            $key2 = 416;
            $keyTotal = 1980;

            $totalInhabitants = $listInhabitantsOld[ $key1  ] + $listInhabitantsOld[ $key2 ];
            $dataset[ $keyTotal ][ 'citycode' ] = $keyTotal;
            $dataset[ $keyTotal ][ 'cityname' ] = 'Dijk en Waard';
            $dataset[ $keyTotal ]["value"] = strval( round( ( 
                   ( $dataset[ $key1 ]["value"] * $listInhabitantsOld[ $key1 ] ) +
                   ($dataset[ $key2 ]["value"] * $listInhabitantsOld[ $key2 ] ) )
                                / $totalInhabitants) );
            $dataset[ $keyTotal ]["agegroup_18_and_above"] = strval( round( (
                 ($dataset[ $key1 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key1 ] ) +
                 ($dataset[ $key2 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key2 ] ) )
                             / $totalInhabitants ) );
            $dataset[ $keyTotal ]["agegroup_12_to_17"] = strval( round( ( 
                    ($dataset[ $key1 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key1 ] ) +
                    ($dataset[ $key2 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key2 ] ) )
                            / $totalInhabitants ) );
            unset( $dataset[ $key1 ], $dataset[ $key2 ] );
        }


        // samenvoeging Landerd (1685) en Uden (856)
        // tot de nieuwe gemeente Maashorst (1991)
        if( isset( $dataset[1685]["citycode"] ) ) {
            $key1 = 1685;
            $key2 = 856;
            $keyTotal = 1991;

            $totalInhabitants = $listInhabitantsOld[ $key1  ] + $listInhabitantsOld[ $key2 ];
            $dataset[ $keyTotal ][ 'citycode' ] = $keyTotal;
            $dataset[ $keyTotal ][ 'cityname' ] = 'Dijk en Waard';
            $dataset[ $keyTotal ]["value"] = strval( round( ( 
                   ( $dataset[ $key1 ]["value"] * $listInhabitantsOld[ $key1 ] ) +
                   ($dataset[ $key2 ]["value"] * $listInhabitantsOld[ $key2 ] ) )
                                / $totalInhabitants) );
            $dataset[ $keyTotal ]["agegroup_18_and_above"] = strval( round( (
                 ($dataset[ $key1 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key1 ] ) +
                 ($dataset[ $key2 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key2 ] ) )
                             / $totalInhabitants ) );
            $dataset[ $keyTotal ]["agegroup_12_to_17"] = strval( round( ( 
                    ($dataset[ $key1 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key1 ] ) +
                    ($dataset[ $key2 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key2 ] ) )
                            / $totalInhabitants ) );
            unset( $dataset[ $key1 ], $dataset[ $key2 ] );
        }


        /* GEMEENTELIJKE HERINDELING 24 MAART 2022 */


        // samenvoeging Weesp (0457) en Amsterdam (363)
        // check if Weesp (457) exists
        if( isset( $dataset[457]["citycode"] ) ) {
            $key1 = 457;
            $key2 = 363;

            $totalInhabitants = $listInhabitantsOld[ $key1  ] + $listInhabitantsOld[ $key2 ];
            $dataset[ $key2 ]["value"] = strval( round( ( ( $dataset[ $key2 ]["value"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["value"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants) );
            $dataset[ $key2 ]["agegroup_18_and_above"] = strval( round( ( ($dataset[ $key2 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["agegroup_18_and_above"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants ) );
            $dataset[ $key2 ]["agegroup_12_to_17"] = strval( round( ( ($dataset[ $key2 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key2 ] ) +
                            ( $dataset[ $key1 ]["agegroup_12_to_17"] * $listInhabitantsOld[ $key1 ] ) ) / $totalInhabitants ) );
            unset( $dataset[ $key1 ] );
        }





        return( $dataset );

    }















    











    /**
     * Get hospital addmission per city for the last two weeks per 100.000 inhabitants
     * To be used in map to compare with vaccination coverage.
     */
    public function getMapHospitalAdmissionsLastWeeks() {
        if( FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES_HOSPITAL_ADMISSION_PER_HUNDRED_THOUSAND ) ) {
            return;
        }
        
        $seriesNice = FileManager::readRIVM_NICEHospitalAdmissionCsv();
        array_multisort(    array_column( $seriesNice, FileManager::HEADER_CITY_CODE ), SORT_ASC,
                            array_column( $seriesNice, FileManager::HEADER_DATE ), SORT_ASC, $seriesNice);
                            
   
        $seriesNice = $this->gemeentelijkeHerindelingZiekenhuisopnamesNice( $seriesNice );
        array_multisort(array_column( $seriesNice, FileManager::HEADER_DATE ), SORT_ASC,
                        array_column( $seriesNice, FileManager::HEADER_CITY_NAME ), SORT_ASC,
                        $seriesNice);

        //check if we have matching series. Slice array to last 14 days.
        $keyDate = new DateTime( end( $seriesNice )[ FileManager::HEADER_DATE ] );
        $keyDate->modify("-14 days");
        $needle = $keyDate->format('Y-m-d');

        $startIndex = array_search($needle, array_column( $seriesNice, FileManager::HEADER_DATE ));
        $subSeries = array_slice( $seriesNice, $startIndex );

        $sum = array();
        $this->getInhabitantsPerCity();
        foreach( $subSeries as $row ) {
            if( isset( $sum[ $row[ FileManager::HEADER_CITY_CODE ] ] ) ) {
                $sum[ $row[ FileManager::HEADER_CITY_CODE ] ][ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED ];
            } else {
                $sum[ $row[ FileManager::HEADER_CITY_CODE ] ][ "citycode" ] = $row[ FileManager::HEADER_CITY_CODE ];
                $sum[ $row[ FileManager::HEADER_CITY_CODE ] ][ "cityname" ] = $row[ FileManager::HEADER_CITY_NAME ];
                $sum[ $row[ FileManager::HEADER_CITY_CODE ] ][ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED ];
            }
        }

        // add vaccination coverage and calculate hospirtal addmissions per 100.000 
        $this->getSeriesVaccinatedPerCity();
        foreach( $sum as $key => $row ) {
            if ( isset( $this->listOfInhabitants[ $key ] ) ) {
                $sum[ $key ][ FileManager::HEADER_VALUE ] = round(
                            ( $row[ FileManager::HEADER_HOSPITALIZED ] / 
                            $this->listOfInhabitants[ $key ] [ FileManager::HEADER_INHABITANTS ] ) * 100000, 1);
            }
            if( isset( $this->seriesVaccinatedPerCity[ $key ] ) ) {
                $sum[ $key ][ "vaxxed" ] =  $this->seriesVaccinatedPerCity[ $key ];
            }
        }

        $sum =  array_values($sum);
        FileManager::saveToCache( $sum, FileManager::CACHE_JAVASCRIPT_FILE_MAP_VALUES_HOSPITAL_ADMISSION_PER_HUNDRED_THOUSAND, FileManager::JAVASCRIPT, 'mapDataHospitalAdmissionPerCityPerHundredThousand', true );

    }


    private function getSeriesVaccinatedPerCity() {
        if( ! empty( $this->seriesVaccinatedPerCity ) ) {
            return true;
        }

        $this->getMapVaccinatedPerCity();
        $rawSeriesVaccinatedPerCity = FileManager::readFromCache( FileManager::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES );
        //var_dump( $rawSeriesVaccinatedPerCity ); exit();
        
        if( ! $this->checkArray( $rawSeriesVaccinatedPerCity ) ) {
            new LogException( 'Empty series \'vaccinated per city map\' reading cache file', __LINE__  );
            return false;
        }
        foreach( $rawSeriesVaccinatedPerCity as $row ) {
            $this->seriesVaccinatedPerCity[ $row[ "citycode" ] ] = $row[ FileManager::HEADER_VALUE ];
        }
        return true;
    }










    //************ VACCINS PER CITY  ******************/
    /**
     * Get VACCINATION PER CITY data ready for the map of the Netherlands.
     */
    public function getMapVaccinatedPerCity() {
        if( FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES ) ) {
            return;
        }
        $rawSeriesvaccinatedPerCity = FileManager::readVaccinationsPerCityFile();

        //prepare series for map
        $dataset = array();
        foreach( $rawSeriesvaccinatedPerCity as $row ) {
            $citycode = $row[ FileManager::HEADER_CITY_CODE ];
            if( ! isset( $dataset[ $citycode ] ) ) {
                $dataset[ $citycode ]["citycode"] = $row[ FileManager::HEADER_CITY_CODE ];
                $dataset[ $citycode ]["cityname"] = $row[ FileManager::HEADER_CITY_NAME ];
                //$dataset[ $citycode ]["date"] = $row[ FileManager::HEADER_DATE ];
            }
            // AGEGROUP_12_AND_ABOVE wordt 'value'
            if( FileManager::AGEGROUP_12_AND_ABOVE == $row[ FileManager::HEADER_AGEGROUP ] ) $row[ FileManager::HEADER_AGEGROUP ] = FileManager::HEADER_VALUE;
            $dataset[ $citycode ][  $row[ FileManager::HEADER_AGEGROUP ] ] = 
                ( strlen( $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] ) > 2 )  // '>=95' is a possible value. We only want '95' in that case.
                    ? substr( $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] , -2)
                    : $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ];
        }

         // check for strange values, like the 9999 in Schiermonnikoog
        foreach( $dataset as $key => $row ) {
            if( $row[ FileManager::HEADER_VALUE ] > $row[ FileManager::AGEGROUP_18_AND_ABOVE ] ) {
                $dataset[$key][ FileManager::HEADER_VALUE ] = $dataset[$key][ FileManager::AGEGROUP_18_AND_ABOVE ];
            }
        }

        $dataset = $this->gemeentelijkeHerindelingVaccinaties( $dataset );

        //$dataset = array_values($dataset);
        $city  = array_column( $dataset, "cityname" );
        array_multisort( $city, SORT_ASC, $dataset );

        FileManager::saveToCache( $dataset, FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES, FileManager::JAVASCRIPT, 'mapDataVaccinated' );
        FileManager::saveToCache( $dataset, FileManager::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES );

        // Add the update date to cache file.
        $timestampUpdateDate = strtotime( end( $rawSeriesvaccinatedPerCity )[ FileManager::HEADER_DATE ] );
        $updateDate = date( "j/n/y", $timestampUpdateDate );
        FileManager::saveToCache( $updateDate, FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES, FileManager::JAVASCRIPT, 'mapDataVaccinatedUpdateDate', true );
        FileManager::saveToCache( $timestampUpdateDate, FileManager::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE );
        
        return;
    }


    public function getMapVaccinatedPerCityLastUpdateDate() {
        $timestampUpdateDate = FileManager::readFromCache( FileManager::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE );
        return date( "j/n/y", $timestampUpdateDate );
    }








    //************ VACCINS PER NEIGHBOURHOOD  ******************/
    /**
     * Get VACCINATION PER NEIGHBOURHOOD data ready for the map of the Netherlands.
     */
    public function getMapVaccinatedPerNeighbourhood() {



        
        return;

        /*
        if( FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES ) ) {
            return;
        }
        */




        $rawSeriesvaccinatedPerNeigbourhood = FileManager::readVaccinationsPerNeighbourhoodFile();

        //prepare series for map
        $dataset = array();
        foreach( $rawSeriesvaccinatedPerNeigbourhood as $row ) {
            $citycode = $row[ FileManager::HEADER_NEIGHBOURHOOD_CODE ];
            if( ! isset( $dataset[ $citycode ] ) ) {
                $dataset[ $citycode ]["citycode"] = $row[ FileManager::HEADER_CITY_CODE ];
                $dataset[ $citycode ]["cityname"] = $row[ FileManager::HEADER_CITY_NAME ];
                $dataset[ $citycode ]["wkcode"] = $row[ FileManager::HEADER_NEIGHBOURHOOD_CODE ];
                $dataset[ $citycode ]["wkname"] = $row[ FileManager::HEADER_NEIGHBOURHOOD_NAME ];
            }
            // AGEGROUP_12_AND_ABOVE wordt 'value'
            if( FileManager::AGEGROUP_12_AND_ABOVE == $row[ FileManager::HEADER_AGEGROUP ] ) $row[ FileManager::HEADER_AGEGROUP ] = FileManager::HEADER_VALUE;
            $dataset[ $citycode ][  $row[ FileManager::HEADER_AGEGROUP ] ] = 
                ( strlen( $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] ) > 2 )  // '>=95' is a possible value. We only want '95' in that case.
                    ? substr( $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] , -2)
                    : $row[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ];
        }

        //var_dump( $dataset ); exit();
        // check for strange values, like the 9999 in Schiermonnikoog
        foreach( $dataset as $key => $row ) {
            if( $row[ FileManager::HEADER_VALUE ] > $row[ FileManager::AGEGROUP_18_AND_ABOVE ] ) {
                $dataset[$key][ FileManager::HEADER_VALUE ] = $dataset[$key][ FileManager::AGEGROUP_18_AND_ABOVE ];
            }
        }


        $dataset = array_values($dataset);
        $city  = array_column( $dataset, "cityname" );
        $neighbourhood  = array_column( $dataset, "wkname" );
        array_multisort( $city, SORT_ASC, $neighbourhood, SORT_ASC, $dataset );

        FileManager::saveToCache( $dataset, FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES, FileManager::JAVASCRIPT, 'mapDataVaccinatedPerNeighbourhood' );
        FileManager::saveToCache( $dataset, FileManager::CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES );

        // Add the update date to cache file.
        $timestampUpdateDate = strtotime( end( $rawSeriesvaccinatedPerNeigbourhood )[ FileManager::HEADER_DATE ] );
        $updateDate = date( "j/n/y", $timestampUpdateDate );
        FileManager::saveToCache( $updateDate, FileManager::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES, FileManager::JAVASCRIPT, 'mapDataVaccinatedPerNeighbourhoodUpdateDate', true );
        FileManager::saveToCache( $timestampUpdateDate, FileManager::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE );
        
        return;
    }

    public function getMapVaccinatedPerNeighbourhoodLastUpdateDate() {
        $timestampUpdateDate = FileManager::readFromCache( FileManager::CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES_LAST_UPDATE_DATE );
        return date( "j/n/y", $timestampUpdateDate );
    }




}
