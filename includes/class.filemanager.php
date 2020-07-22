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
    // cache files
    const CACHE_FILE_LIST_OF_CITIES= "data/cache/cache.list-of-cities.php";
    const CACHE_FILE_SERIES_SORTED_BY_CITY = "data/cache/cache.series-sorted-by-city.php";
    const CACHE_FILE_SERIES_SORTED_BY_DATE = "data/cache/cache.series-sorted-by-date.php";
    const TOTALS_BY_DATE_CACHE_FILE = "data/cache/cache.totals-sorted-by-date.php";
    const SERIES_TOP_REPORTED_CITY_CACHE_FILE = "data/cache/cache.top-reported-cities.php";
    const CACHE_FILE_INHABITANTS_PER_CITY = "data/cache/cache.inhabitants-per-cities.php";

    // source csv-files
    const RIVM_SOURCE_CSV_FILE = 'data/source_files/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_SOURCE_CSV_DELIMITER = ';';
    const RIVM_REMOTE_SOURCE_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_aantallen_gemeente_cumulatief.csv';
    const CBS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/CBS-aantal_inwoners_per_gemeente.csv';
    const CBS_INHABITANTS_SOURCE_CSV_FILE_DELIMITER = ';';

    // replacements to source data to prevent possible breaking code and for usability
    const SEARCH = array( "'", "\"", "s-Gravenhage", "s-Hertogenbosch" );
    const REPLACE = array( "", "", "Den Haag", "Den Bosch" );

    // flags
    const SERIES_SORTED_BY_CITY = "city";
    const SERIES_SORTED_BY_DATE = "SERIES_SORTED_BY_DATE";

    // Uniform headers for data in arrays and in cache files 
    const HEADER_DATE = 'date';
    const HEADER_CITY_CODE = 'code';
    const HEADER_CITY_NAME = 'city';
    const HEADER_REPORTED = 'reported';
    const HEADER_HOSPITALIZED= 'hospitalized';
    const HEADER_DECEASED = 'deceased';
    const HEADER_INHABITANTS = 'inhabitants';

 
    /**
     * Check the file size of remote file and compare with our local cache file
     * See: https://thisinterestsme.com/php-get-size-remote-file/
     */
    public static function isUpdateSourceFileAvailable() {
        // only check maximum once in half hour
        $lastChecked = FileUtils::readCookie( "lastChecked" );
        $now = time();
        if ( ( $now - $lastChecked ) < 1800 ) return false;  // 60*30 is half hour 

        // Do the check
        FileUtils::writeCookie( "lastChecked", $now );
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
     * Download the RIVM CSV source file
     */
    public static function getRemoteFile() {
        $contents = file_get_contents( self::RIVM_REMOTE_SOURCE_CSV_FILE );
        if( ! $contents ) return false;
        if( rename( self::RIVM_SOURCE_CSV_FILE, self::RIVM_SOURCE_CSV_FILE . "." . filemtime( self::RIVM_SOURCE_CSV_FILE ) . ".csv" ) ) {
            if( file_put_contents( self::RIVM_SOURCE_CSV_FILE, $contents, LOCK_EX ) ) {
                // we got new data. delete existing cache files
                if( file_exists( self::CACHE_FILE_LIST_OF_CITIES) ) unlink( self::CACHE_FILE_LIST_OF_CITIES);
                if( file_exists( self::CACHE_FILE_SERIES_SORTED_BY_CITY ) ) unlink( self::CACHE_FILE_SERIES_SORTED_BY_CITY );
                if( file_exists( self::CACHE_FILE_SERIES_SORTED_BY_DATE ) ) unlink( self::CACHE_FILE_SERIES_SORTED_BY_DATE );
                if( file_exists( self::TOTALS_BY_DATE_CACHE_FILE ) ) unlink( self::TOTALS_BY_DATE_CACHE_FILE );
                if( file_exists( self::SERIES_TOP_REPORTED_CITY_CACHE_FILE ) ) unlink( self::SERIES_TOP_REPORTED_CITY_CACHE_FILE );
                if( file_exists( self::CACHE_FILE_INHABITANTS_PER_CITY ) ) unlink( self::CACHE_FILE_INHABITANTS_PER_CITY );
                return true;
            }
        }
        return false;
    }


    /**
     * Read the complete dataset.
     * If cache is not available, read data from RIVM source file and save to cache file.
     * @type: if type=CITY the data is sorted on city name ASC. If type=SERIES_SORTED_BY_DATE the data is sorted on date ASC.
     */
    public static function readSeries( $type = self::SERIES_SORTED_BY_CITY, $overrideCache = false ) {
        // If cache file is available, return cache
        if( $type==self::SERIES_SORTED_BY_CITY && self::isCacheAvailable( self::CACHE_FILE_SERIES_SORTED_BY_CITY ) ) {
            return self::readFromCache( 'series', self::CACHE_FILE_SERIES_SORTED_BY_CITY );
        } 
        elseif( $type==self::SERIES_SORTED_BY_DATE && self::isCacheAvailable( self::CACHE_FILE_SERIES_SORTED_BY_DATE ) ) {
            return self::readFromCache( 'series', self::CACHE_FILE_SERIES_SORTED_BY_DATE );
        } else {  // No cache available, so let's parse the source file and save data to cache files.
            $header = NULL;
            $data = array();
            if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                    // first line is the header
                    if( ! $header ) {
                        $fieldsCount = count( $row );
                        // $header = array( $row[0],$row[2], $row[4], $row[5], $row[6] );
                        $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                        self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    } else {
                        // add a line with data to our data array
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
                fclose($handle);
            }

            // sort the data for country views
            $cityname  = array_column($data, self::HEADER_CITY_NAME );
            $date = array_column($data, self::HEADER_DATE );
            array_multisort($date, SORT_ASC, $cityname, SORT_ASC,  $data);
            self::saveToCache( 'series', $data, self::CACHE_FILE_SERIES_SORTED_BY_DATE );
            if ( $type == self::SERIES_SORTED_BY_DATE ) $series = $data;  // if type SERIES_SORTED_BY_DATE this is the series we want to return.

            // Sort the data for city views
            array_multisort( $cityname, SORT_ASC, $date, SORT_ASC, $data);
            self::saveToCache( 'series', $data, self::CACHE_FILE_SERIES_SORTED_BY_CITY );
            if ( $type == self::SERIES_SORTED_BY_CITY ) $series = $data; // if type CITY this is the series we want to return. 
        }
        return $series;
    }


    /**
     * Create a list of cities from the RIVM source file 
     */
    public static function readListOfCities() {
        // If cache file is available, return cache
        if( self::isCacheAvailable( self::CACHE_FILE_LIST_OF_CITIES) ) {
            return self::readFromCache( 'cities', self::CACHE_FILE_LIST_OF_CITIES) ;
        } 

        // Check if the source file is available
        if( ! file_exists( self::RIVM_SOURCE_CSV_FILE ) || ! is_readable( self::RIVM_SOURCE_CSV_FILE ) ) {
            return false;
        }

        // read the file
        $header = NULL;
        $data = array();
        if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                // first line is the header
                if( ! $header ) {
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_CITY_CODE, self::HEADER_CITY_NAME );
                } else {
                    // add a line with data to our data array
                    if( count( $row ) == $fieldsCount && ! empty( $row[2] ) ) {
                            if ( empty( $firstCityName ) ) {
                                $firstCityName = $row[2];
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
            fclose($handle);
        }
        // sort the data by city name
        $cityname  = array_column($data, self::HEADER_CITY_NAME );
        array_multisort($cityname, SORT_ASC, $data);
        self::saveToCache( 'cities', $data, self::CACHE_FILE_LIST_OF_CITIES);
        return $data;
    }


    /**
     * Create a list of number of inhabitants per city from the CBS source file 
     */
    public function readNumberOfInhabitants() {
        // If cache file is available, return cache
        if( self::isCacheAvailable( self::CACHE_FILE_INHABITANTS_PER_CITY ) ) {
            return self::readFromCache( 'inhabitants', self::CACHE_FILE_INHABITANTS_PER_CITY ) ;
        } 
        // Check if the source file is available
        if( ! file_exists( self::CBS_INHABITANTS_SOURCE_CSV_FILE) || ! is_readable( self::CBS_INHABITANTS_SOURCE_CSV_FILE ) ) {
            return false;
        }

        // read the file
        $header = NULL;
        $data = array();
        $i=0;
        if (($handle = fopen( self::CBS_INHABITANTS_SOURCE_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, SELF::CBS_INHABITANTS_SOURCE_CSV_FILE_DELIMITER )) !== FALSE) {
                // first line is the header
                if( ! $header ) {
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, self::HEADER_INHABITANTS );
                } else {
                    // add a line with data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[0], $row[1], $row[2] ) );
                    }
                }
            }
            fclose($handle);
        }
        self::saveToCache( 'inhabitants', $data, self::CACHE_FILE_INHABITANTS_PER_CITY );
        return $data;
    }










    /**
     * Check whether a cache file is available
     */
    public function isCacheAvailable( $cacheFile ) {
        return file_exists( $cacheFile ) && is_readable( $cacheFile ) ;
    }


    /**
     * Save an array to a cache file including the name of the array.
     */
    public function saveToCache( $varName, $series, $cacheFile ) {
        // TODO: check if allowed cachefile
        file_put_contents ( $cacheFile , '<?php $cache = array(); $cache["' . $varName . '"] = ' . var_export( $series, true ) . ";", LOCK_EX );
    }


    /**
     * Read an array from a cache file including the name of the array.
     */
    public function readFromCache( $varName, $cacheFile ) {
        if ( ! self::isCacheAvailable( $cacheFile ) ) return false;
        require( $cacheFile );
        return $cache[ $varName ];
    }


}
