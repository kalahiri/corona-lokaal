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
    const LIST_OF_CITIES_CACHE_FILE = "data/list-of-cities.php";
    const SERIES_CITY_CACHE_FILE = "data/sorted-series-city-cache.php";
    const SERIES_COUNTRY_CACHE_FILE = "data/sorted-series-country-cache.php";
    const RIVM_SOURCE_CSV_FILE = 'data/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_SOURCE_CSV_DELIMITER = ';';
    const RIVM_REMOTE_SOURCE_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_aantallen_gemeente_cumulatief.csv';
    const COOKIE_SIZE_RIVM_REMOTE_SOURCE_CSV_FILE = 'data/cookie-size-rivm-source-csv-file;';

    // replacements to source data to prevent possible breaking code and for usability
    const SEARCH = array( "'", "\"", "s-Gravenhage", "s-Hertogenbosch" );
    const REPLACE = array( "", "", "Den Haag", "Den Bosch" );

    // flags
    const CITY = "city";
    const COUNTRY = "country";

    
    /**
     * Constructor
     */
    public function __construct() {


    }


    /**
     * Check the file size of remote file and compare with our local cache file
     * See: https://thisinterestsme.com/php-get-size-remote-file/
     */
    public static function isUpdateSourceFileAvailable() {





        // TODO: FOR DEVELOPMENT PURPOSES WE TURN AUTO_UPDATE OFF.
        return false;








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
        
        //$cookieFileSize = FileUtils::readCookie( $cookie );
        //return ( $remoteFileSize !== false && ( $remoteFileSize > $cookieFileSize ) ) ? true : false;

    }

    /**
     * Download the RIVM CSV source file
     */
    public static function getRemoteFile() {
        $contents = file_get_contents( self::RIVM_REMOTE_SOURCE_CSV_FILE );
        if( ! $contents ) return false;
        if( rename( self::RIVM_SOURCE_CSV_FILE, self::RIVM_SOURCE_CSV_FILE . ".old." . filmtime ) ) {
            if( file_put_contents( self::RIVM_SOURCE_CSV_FILE, $contents, LOCK_EX ) ) {
                // we got new data. remove existing cache files
                unlink( self::SERIES_CACHE_FILE );
                return true;
            }
        }
        return false;
    }


    /**
     * Read the complete dataset.
     * If cache is not available, read data from RIVM source file and save to cache file.
     */
    public static function readSeries( $type = self::CITY, $overrideCache = false ) {
        if( $type==self::CITY && file_exists( self::SERIES_CITY_CACHE_FILE ) && is_readable( self::SERIES_CITY_CACHE_FILE ) ) {
            require( self::SERIES_CITY_CACHE_FILE );
        } elseif( $type==self::COUNTRY && file_exists( self::SERIES_COUNTRY_CACHE_FILE ) && is_readable( self::SERIES_COUNTRY_CACHE_FILE ) ) {
            require( self::SERIES_COUNTRY_CACHE_FILE );
        } else {
            // read the RIVM source file
            $header = NULL;
            $data = array();
            if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                    // first line is the header
                    if( ! $header ) {
                        $fieldsCount = count( $row );
                        $header = array( $row[0],$row[2], $row[4], $row[5], $row[6] );
                    } else {
                        // add a line with data to our data array
                        if( count( $row ) == $fieldsCount && ! empty( $row[2] ) ) {
                            $data[] = array( substr( $row[0], 0, 10 ) , str_replace( self::SEARCH, self::REPLACE, $row[2] ) , $row[4], $row[5], $row[6] ) ;
                        }
                    }
                }
                fclose($handle);
            }

            // sort the data for country views
            $cityname  = array_column($data, 1);
            $date = array_column($data, 0);
            array_multisort($date, SORT_ASC, $cityname, SORT_ASC,  $data);
            // Save series to cache file
            file_put_contents ( self::SERIES_COUNTRY_CACHE_FILE , '<?php $series = ' . var_export( $data, true ) . ";", LOCK_EX );
            if ( $type == self::COUNTRY ) $series = $data;

            // Sort the data for city views
            array_multisort($cityname, SORT_ASC, $date, SORT_ASC, $data);
            // Save series to cache file
            file_put_contents ( self::SERIES_CITY_CACHE_FILE , '<?php $series = ' . var_export( $data, true ) . ";", LOCK_EX );
            if ( $type == self::CITY ) $series = $data;
        }
        return $series;
    }






    /**
     * Get a list of available cities.
     * First, check if a cached version is available.
     * If not get them from the source file.  
     */
    public static function getListOfCities() {
        if( file_exists( self::LIST_OF_CITIES_CACHE_FILE ) && is_readable( self::LIST_OF_CITIES_CACHE_FILE ) ) {
            require( self::LIST_OF_CITIES_CACHE_FILE );
        } else {
            $cities = self::readListOfCities();
            file_put_contents ( self::LIST_OF_CITIES_CACHE_FILE , '<?php $cities = ' . var_export($cities, true) . ";", LOCK_EX );
        }
        return $cities;
    }


    /**
     * Create a list of cities from the RIVM source file 
     */
    private static function readListOfCities() {

        if( ! file_exists( self::RIVM_SOURCE_CSV_FILE ) || ! is_readable( self::RIVM_SOURCE_CSV_FILE ) ) {
            return FALSE;
        }

        // read the file
        $header = NULL;
        $data = array();
        if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                // first line is the header
                if( ! $header ) {
                    $header = $row;
                    $fieldsCount = count( $header );
                    foreach( $header as $key => $val ) {
                        if ( strpos ( $val, 'name' ) > 0 ) {
                            $index = $key;
                        }
                    }
                } else {
                    // add a line with data to our data array
                    if( count( $row ) == $fieldsCount && ! empty ( $row[ $index ] ) ) {
                            if ( empty( $first ) ) {
                                $first = $row[ $index ];
                            } else {
                                // If we come across a city we already have, we quit.
                                if (  $first == $row[ $index ] ) {
                                    // remove possible breaking characters from city names
                                    $data = str_replace( self::SEARCH, self::REPLACE, $data );
                                    // sort the city names alphabettically before returning the list
                                    natcasesort( $data );
                                    return array_values( $data );
                                }
                            }
                            //$data[] = array_combine($header, $row);
                            $data[] =  $row[ $index ];
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }



}
