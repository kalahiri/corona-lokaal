<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class FileManager {

    // TODO: Name cache files consistently.
    // cache files
    const CACHE_FILE_LIST_OF_CITIES= "data/cache/cache.list-of-cities.php";
    const CACHE_FILE_SERIES_SORTED_BY_CITY = "data/cache/cache.series-sorted-by-city.php";
    const CACHE_FILE_SERIES_SORTED_BY_DATE = "data/cache/cache.series-sorted-by-date.php";
    const TOTALS_BY_DATE_CACHE_FILE = "data/cache/cache.totals-sorted-by-date.php";
    const SERIES_TOP_REPORTED_CITY_CACHE_FILE = "data/cache/cache.top-reported-cities.php";
    const CACHE_FILE_INHABITANTS_PER_CITY = "data/cache/cache.inhabitants-per-cities.php";
    const CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS = "data/cache/cache.waste-water-plant-locations.js";
    const CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION = "data/cache/cache.waste-water-series-sorted-by-location.php";
    const CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER = "data/cache/cache.waste-water-totals-sorted-by-week-number.php";
    const CACHE_WASTE_WATER_TOTALS_BY_DAY = "data/cache/cache.waste-water-totals-sorted-by-day.php";

    const CACHE_JAVASCRIPT_FILE_MAP_VALUES = "data/cache/cache.map-values-per-city.js";
    const CACHE_FILE_MAP_VALUES = "data/cache/cache.map-values-per-city.php";
    const CACHE_FILE_NICE_NEW_INTAKE = "data/cache/cache.nice_new_intake.php";

    // source csv-files
    const RIVM_SOURCE_CSV_FILE = 'data/source_files/gemeenten/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_SOURCE_CASUS_CSV_FILE = 'data/source_files/casus/COVID-19_casus_landelijk.csv';
    const RIVM_SOURCE_WASTE_WATER_CSV_FILE = 'data/source_files/rioolwater/COVID-19_rioolwaterdata.csv';
    const RIVM_SOURCE_CSV_DELIMITER = ';';
    const CBS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/inwoners/CBS-aantal_inwoners_per_gemeente.csv';
    const CBS_INHABITANTS_SOURCE_CSV_FILE_DELIMITER = ';';
    const NICE_SOURCE_NEW_INTAKE_JSON_FILE = 'data/source_files/nice/nice_new_intake.json';
   // const NICE_SOURCE_NEW_INTAKE_TOTALS_1300_FILE = 'data/source_files/nice/nice_new_intake_totals_1300.json';

    // remote source files
    const RIVM_REMOTE_SOURCE_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_REMOTE_SOURCE_CASUS_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_casus_landelijk.csv';
    const NICE_REMOTE_SOURCE_NEW_INTAKE_JSON_FILE = 'https://stichting-nice.nl/covid-19/public/new-intake/'; 
    // NICE URL's naar data haal je uit dit script: https://www.stichting-nice.nl/js/covid-19.js?version=1596281515062
    // voor totaal aanwezigen op IC's bijvoorbeeld: https://stichting-nice.nl/covid-19/public/intake-count/
    const RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_rioolwaterdata.csv';
    // More info about waste ater data file: https://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/a2960b68-9d3f-4dc3-9485-600570cd52b9?tab=relations
    // CBS source information on number of inhabitants is not automaticallty updated.
    // The data can be found here: https://opendata.cbs.nl/statline/portal.html?_catalog=CBS&_la=nl&tableId=70072ned&_theme=232

    // Cookies
    const COOKIE_LAST_CHECK_RIVM = 'lastCheckRIVM';
    const COOKIE_LAST_CHECK_NICE = 'lastChecKNICE';
    const COOKIE_LAST_CHECK_WASTE_WATER = 'lastCheckWasteWater';
    const COOKIE_LAST_FETCH_RIVM = 'lastFetchRIVM';
    const COOKIE_LAST_FETCH_RIVM_CASUS = 'lastFetchRIVMCasusFile';
    const COOKIE_LAST_FETCH_NICE = 'lastFetchNICE';
    const COOKIE_LAST_FETCH_WASTE_WATER = 'lastFetchWasteWater';

    // replacements to source data to prevent possible breaking code and for usability
    const SEARCH = array( "'", "\"", "s-Gravenhage", "s-Hertogenbosch" );
    const REPLACE = array( "", "", "Den Haag", "Den Bosch" );

    // flags
    const LIST_OF_CITIES = "LIST_OF_CITIES";
    const SERIES_ALL = "SERIES_ALL";
    const SERIES_WITHOUT_PROVINCE = "SERIES_WITHOUT_PROVINCE";
    const SERIES_SORTED_BY_CITY = "SERIES_SORTED_BY_CITY";
    const SERIES_SORTED_BY_DATE = "SERIES_SORTED_BY_DATE";

    // Uniform headers for data in arrays and in cache files 
    const HEADER_DATE = 'date';
    const HEADER_CITY_CODE = 'code';
    const HEADER_CITY_NAME = 'city';
    const HEADER_PROVINCE = 'province';
    const HEADER_REPORTED = 'reported';
    const HEADER_HOSPITALIZED = 'hospitalized';
    const HEADER_INTENSIVE_CARED = 'intensive-cared';
    const HEADER_DECEASED = 'deceased';
    const HEADER_INHABITANTS = 'inhabitants';
    // Uniform headers for rioolwater data
    const HEADER_DATE_MEASUREMENT = 'date_measurement';
    const HEADER_RWZI_CODE = 'RWZI_code';
    const HEADER_RWZI_NAME = 'RWZI_name';
    const HEADER_RWZI_X = 'RWZI_x';
    const HEADER_RWZI_Y = 'RWZI_y';
    const HEADER_RNA = 'RNA_per_ml';
    const HEADER_REPRESENTATIVE_MEASUREMENT = 'representative_measurement';
    const HEADER_WEEK_NUMBER = 'week_number';


 
    /**
     * Check the file size of remote file and compare with our local cache file
     * See: https://thisinterestsme.com/php-get-size-remote-file/
     */
    public static function isUpdateSourceFileAvailable( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            $lastFetched = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM );
            $lastChecked = FileUtils::readCookie( self::COOKIE_LAST_CHECK_RIVM );
            if ( date('j',$now) == date('j',$lastFetched ) ) return false;  // only one update each day.
            $hour = date('H',$now);
            if( $hour > 13 && $hour < 16 ) { //updates usually appear in this time slot.
                $checkInterval = 180;  // 60*3 is three minutes 
            } else {
                $checkInterval = 1800;  // 60*30 is half hour 
            }
            if ( ( $now - $lastChecked ) < $checkInterval ) return false; 
        }

        // Do the check
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM, $now );
        $headers = get_headers( self::RIVM_REMOTE_SOURCE_CSV_FILE, 1 );
        //Convert the array keys to lower case to prevent possible mistakes
        $headers = array_change_key_case( $headers );
        $remoteFileSize = false;
        if( isset( $headers['content-length'] ) ) {
            $remoteFileSize = $headers['content-length'];
        }
        $localFileSize = ( file_exists( self::RIVM_SOURCE_CSV_FILE  ) ) ? filesize( self::RIVM_SOURCE_CSV_FILE ) : false;
        return ( $remoteFileSize !== false && ( $remoteFileSize > $localFileSize ) ) ? true : false;
    }


    /**
     * Download the RIVM CSV source file.
     * This is also where the cache files are deleted. 
     * If no cache files are available later on, they are recreated (and thus updated) automatically.
     */
    public static function getRemoteFile() {
        $contents = file_get_contents( self::RIVM_REMOTE_SOURCE_CSV_FILE );
        if( ! $contents ) return false;
        $continue = true;
        if( file_exists( self::RIVM_SOURCE_CSV_FILE ) ) {
            $continue = false;
            $date = date( 'Ymd', filemtime( self::RIVM_SOURCE_CSV_FILE ) );
            $filename = substr( self::RIVM_SOURCE_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
            if( rename( self::RIVM_SOURCE_CSV_FILE, $filename ) ) $continue = true;
        }
        if( ! $continue ) return false;
        if( file_put_contents( self::RIVM_SOURCE_CSV_FILE, $contents, LOCK_EX ) ) {
            $cacheDir = 'data/cache/';
            $cacheFiles = scandir( $cacheDir );
            foreach( $cacheFiles as $cacheFile ) {
                if( $cacheDir . $cacheFile ==  FileManager::CACHE_FILE_INHABITANTS_PER_CITY ) continue; // no need to recreate cache file each day.
                if( $cacheDir . $cacheFile ==  FileManager::CACHE_FILE_LIST_OF_CITIES ) continue; // no need to recreate cache file each day.
                if( strpos( $cacheFile, 'cache' ) !== false ){
                    if( file_exists( $cacheDir . $cacheFile ) ) unlink( $cacheDir . $cacheFile );
                }
            }
            SiteUtils::createSiteMap(); // update sitemap ( probably nothing changed, but just in case... )
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM, time() );
            return true;
        }
        return false;
    }


   /**
     * Reads the RIVM csv source file and returns the data
     *  $type determines which columns are added to the returned data.
     */
    public static function readCsvRIVM( $type = SERIES_ALL ) {
        if( ! self::isFileAvailable( self::RIVM_SOURCE_CSV_FILE ) ) {
            return false;
        }

        $data = array();
        if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
            $header = NULL;
            switch( $type ) {
                case self::LIST_OF_CITIES:
                    $header = array( self::HEADER_CITY_CODE, self::HEADER_CITY_NAME );
                    while (($row = fgetcsv($handle, 1000, self::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                        if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                            $fieldsCount = count( $row );
                        } else { // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount && ! empty( $row[ 2 ] ) ) {
                                if ( empty( $firstCityName ) ) {
                                    $firstCityName = $row[ 2 ];
                                } else {
                                    // If we come across a city we already have, we quit.
                                    if (  $firstCityName == $row[ 2 ] ) {
                                        break;
                                    }
                                }
                                $data[] = array_combine( $header, array( (int) substr( $row[1],2 ), str_replace( self::SEARCH, self::REPLACE, $row[2] ) ) );
                            }
                        }
                    }
                    break;
                case self::SERIES_WITHOUT_PROVINCE:
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                        self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                        if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                            $fieldsCount = count( $row );
                        } else {  // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount && ! empty( $row[2] ) ) {
                                $data[] = array_combine( $header, 
                                            array( 
                                                substr( $row[0], 0, 10 ) , (int) substr( $row[1],2 ), str_replace( self::SEARCH, self::REPLACE, $row[2] ),
                                                $row[4], $row[5], $row[6] 
                                            )
                                        );
                            }
                        }
                    }
                    break;
                case self::SERIES_ALL:
                default:
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                            self::HEADER_PROVINCE, 
                            self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    while (($row = fgetcsv($handle, 1000, self::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                        if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                            $fieldsCount = count( $row );
                        } else { // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount )  {
                                $data[] = array_combine( $header, 
                                            array( substr( $row[0], 0, 10 ), (int) substr( $row[1],2 ),
                                                str_replace( self::SEARCH, self::REPLACE, $row[2] ),
                                                $row[3],$row[4], $row[5], $row[6] 
                                            )
                                        );
                            }
                        }
                    }
            }
            fclose($handle);
        }
        return $data;
    }


    /**
     * Download the RIVM CSV CASUS source file.
     * This file is not used actively but is kept for archive
     * TODO: We can use a cronjob to get this.
     */
    public static function getRemoteCasusFile() {
        // check if a new file is available
        $headers = get_headers( self::RIVM_REMOTE_SOURCE_CASUS_CSV_FILE , 1 );
        $headers = array_change_key_case( $headers ); //Convert keys to lowercase to prevent possible mistakes
        $remoteFileSize = false;
        if( isset( $headers['content-length'] ) ) {
            $remoteFileSize = $headers['content-length'];
        }
        $localFileSize = ( file_exists( self::RIVM_SOURCE_CASUS_CSV_FILE  ) ) ? filesize( self::RIVM_SOURCE_CASUS_CSV_FILE ) : false;
        if ( $remoteFileSize !== false && ( $remoteFileSize != $localFileSize ) ) {
            // we got a new file. Let's download it
            $contents = file_get_contents( self::RIVM_REMOTE_SOURCE_CASUS_CSV_FILE );
            if( ! $contents ) return false;
            $date = date( 'Ymd', filemtime( self::RIVM_SOURCE_CASUS_CSV_FILE ) );
            $newFileName = substr( self::RIVM_SOURCE_CASUS_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
            if( rename( self::RIVM_SOURCE_CASUS_CSV_FILE, $newFileName ) ) {
                if ( file_put_contents( self::RIVM_SOURCE_CASUS_CSV_FILE, $contents, LOCK_EX ) ) {
                    FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_CASUS, time() );
                    return true;
                }
            }
        }
        return false;
    }


    /**
    * It is not clear when waste water measurements are updated.
    * At this time, it is only once a week, most of the time on tuesdays.
    * But RIVM also states updates could happen daily.
    */
    public function shouldWasteWaterBeUpdated( $forcedUpdate = false ) {

        //return false;
        // HET RIVM heeft een fout bron bestand. Even niet update dus!!!!









        $now = time();
        if ( ! $forcedUpdate ) {
            $lastChecked = FileUtils::readCookie( self::COOKIE_LAST_CHECK_WASTE_WATER );
            $now = time();
            $checkInterval = 3600;  // 60*60 = one hour 
            if ( ( $now - $lastChecked ) < $checkInterval ) return false;
        }
        
        // Do the check
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_WASTE_WATER, time() );
        $headers = get_headers( self::RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE , 1 );
        $headers = array_change_key_case( $headers ); //Convert keys to lowercase to prevent possible mistakes
        $remoteFileSize = false;
        if( isset( $headers['content-length'] ) ) {
            $remoteFileSize = $headers['content-length'];
        }
        $localFileSize = ( file_exists( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE  ) ) ? filesize( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) : false;
        return ( $remoteFileSize !== false && ( $remoteFileSize > $localFileSize ) ) ? true : false;
    }








    /**
     * Download the RIVM CSV waste water source file.
     */
    public static function getRemoteWasteWaterFile() {

        //return false;
        // HET RIVM heeft een fout bron bestand. Even niet update dus!!!!








        // we got a new file. Let's download it
        $contents = file_get_contents( self::RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE );
        if( ! $contents ) return false;

        $date = date( 'Ymd', filemtime( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) );
        $newFileName = substr( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        if( rename( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE, $newFileName ) ) {
            if ( file_put_contents( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE, $contents, LOCK_EX ) ) {
                $cacheFile = self::CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS;
                if( file_exists( $cacheFile ) ) unlink( $cacheFile );
                $cacheFile = self::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION;
                if( file_exists( $cacheFile ) ) unlink( $cacheFile );
                $cacheFile = self::CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER;
                if( file_exists( $cacheFile ) ) unlink( $cacheFile );
                $cacheFile = self::CACHE_WASTE_WATER_TOTALS_BY_DAY;
                if( file_exists( $cacheFile ) ) unlink( $cacheFile );
                FileUtils::writeCookie( self::COOKIE_LAST_FETCH_WASTE_WATER, time() );
                return true;
            }
        }
        return false;
    }


    /**
     * Reads the RIVM csv waste water souorce file and returns the data 
     */
    public function readCsvWasteWater() {
        // Check if the source file is available
        if( ! self::isFileAvailable( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $data = array();
        if (($handle = fopen( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE_MEASUREMENT, self::HEADER_RWZI_CODE, self::HEADER_RWZI_NAME,
                        self::HEADER_RWZI_X, self::HEADER_RWZI_Y, self::HEADER_RNA, 
                        self::HEADER_REPRESENTATIVE_MEASUREMENT );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $row[10] = ( $row[10] == "TRUE" ) ? true : false;
                        $data[] = array_combine($header, array( $row[0], (int) $row[1], $row[2], (float) $row[3], (float) $row[4], (int) $row[9], $row[10] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;

    }













    /**
     * NICE source data is updated every 15 minutes. To keep it in sync with daily updates
     * from RIVM, we update only once a day after 13.00 o'clock. 
    */
    public function shouldNiceBeUpdated( $forcedUpdate = false ) {
        $lastFetchedNICE = FileUtils::readCookie( self::COOKIE_LAST_FETCH_NICE );
        $lastCheckedNICE = ( ! $forcedUpdate ) ? FileUtils::readCookie( self::COOKIE_LAST_CHECK_NICE ) : 0;
        $checkPoint = strtotime( "13:00:00" );
        $now = time();
        if ( $now < $checkPoint ) return false; // only check for update after 13.00 hours.
        if ( date( 'j', $checkPoint) == date( 'j', $lastFetchedNICE ) ) return false;  // only update once a day.
        $checkInterval = 1800;  // only check for update once every half hour 
        if ( ( $now - $lastCheckedNICE ) < $checkInterval ) return false; 
        return true;
    }

    /**
     * Download the NICE JSON source file
     */
    public static function getRemoteNICEFile() {
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_NICE, time() );
        $contents = file_get_contents( self::NICE_REMOTE_SOURCE_NEW_INTAKE_JSON_FILE );
        if( ! $contents ) return false;
        $continue = true;
        if( file_exists( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE ) ) {
            $continue = false;
            $date = date( 'Ymd', filemtime( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE ) );
            $filename = substr( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE, 0, -5 ) . "." . $date . ".json";
            if( rename( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE, $filename ) ) $continue = true;
        }
        if( ! $continue ) return false;
        if( file_put_contents( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE, $contents, LOCK_EX ) ) {
            if( file_exists( self::CACHE_FILE_NICE_NEW_INTAKE ) ) unlink( self::CACHE_FILE_NICE_NEW_INTAKE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_NICE, time() );
            return true;
        }
        return false;
    }
    
    /**
     * Reads the NICE json source file and returns the data
     * $day { timestamp } fetch the source file of that day
     */
    public function readNICEJson( $timestamp = 0 ) {
        $date = ( $timestamp > 0 ) ? date( 'Ymd', $timestamp ) : '';
        if ( self::isFileAvailable( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE ) 
                    && ( empty( $date ) || $date == date( 'Ymd', filemtime( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE ) ) ) ) {
            return json_decode( file_get_contents( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE ), true );
        }
        $sourceFile = substr( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE , 0, -5 ) . "." . $date . ".json";
        if( ! self::isFileAvailable( $sourceFile ) ) {
            return false;
        }
        return json_decode( file_get_contents( $sourceFile ), true );
    }


 





    /**
     * Reads the CBS csv source file and returns the data 
     */
    public function readCsvCBS() {
        // Check if the source file is available
        if( ! self::isFileAvailable( self::CBS_INHABITANTS_SOURCE_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $data = array();
        if (($handle = fopen( self::CBS_INHABITANTS_SOURCE_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, SELF::CBS_INHABITANTS_SOURCE_CSV_FILE_DELIMITER )) !== FALSE) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, self::HEADER_INHABITANTS );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[0], $row[1], $row[2] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }

    




 


    /**
     * Save an array to a cache file including the name of the array.
     * handles several file types: javascript, php and csv.
     */
    public function saveToCache( $varName, $series, $cacheFile, $type = 'php' ) {
        // TODO: check if allowed cachefile
        switch ( $type ) {
        case 'javascript':
            file_put_contents ( $cacheFile , 'var ' . $varName . ' = ' . json_encode( $series ) . ";", LOCK_EX );
            break;
        case 'csv': //TODO: use fputcsv();
            $csv = '';
            foreach( $series as $row ) {
                foreach( $row as $cell ) {
                    $csv .= trim($cell) . ';';
                }
                $csv .= '
                ';
            }
            file_put_contents ( $cacheFile , $csv , LOCK_EX );
            break;
        case 'php':
        default:
            file_put_contents ( $cacheFile , '<?php $cache = array(); $cache["' . $varName . '"] = ' . var_export( $series, true ) . ";", LOCK_EX );
        }
    }


    /**
     * Read an array from a cache file including the name of the array.
     */
    public function readFromCache( $varName, $cacheFile ) {
        if ( ! self::isFileAvailable( $cacheFile ) ) return false;
        require( $cacheFile );
        return $cache[ $varName ];
    }


    /**
     * Check whether a file is available
     */
    public function isFileAvailable( $file ) {
        return file_exists( $file ) && is_readable( $file );
    }



}
