<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class CityModel {
    const LIST_OF_CITIES_CACHE_FILE = "data/list-of-cities.php";
    const SERIES_CACHE_FILE = "data/sorted-series-cache.php";
    const RIVM_SOURCE_CSV_FILE = 'data/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_SOURCE_CSV_DELIMITER = ';';




    
    /**
     * Constructor
     */
    public function __construct() {
        if ( FileManager::isUpdateSourceFileAvailable() ) {
            echo "DOWNLOADING UPDATE! ";
            FileManager::getRemoteFile();
        }
    }


    public function getChartData( $city, $ajax = true ) {
        $cities = $this->getListOfCities();
        if ( ! in_array( $city, $cities ) ) {
            exit();
        } 

        $cityData = $this->readCityData( $city );

        $labels = $this->prepareLabels( $cityData );

        $reported = $this->prepareDataSerie( array_column( $cityData, 2 ) );
        $hospitalized = $this->prepareDataSerie( array_column( $cityData, 3 ) );
        $deceased = $this->prepareDataSerie( array_column( $cityData, 4 ) );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', $city );
            $ajaxResponse->send();
        }
    }



    /**
     * get the labels from the source data. 
     * format the data to be used by chart.js
     */
    private function prepareLabels( $cityData ) {
        $dates = array_column( $cityData, 0 );
        $labels = array_map( function ($row) {
            return date( "d/n", strtotime ( $row ) );
        }, $dates );
        array_shift( $labels ); // We report differences with day before, so lose the first element of the array
        return $labels;
    }


    /**
     * change the cumulative values to daily add values
     * format the data to be used by chart.js
     */
    private function prepareDataSerie( $dataset ) {
        // NOTICE: the RIVM dataset for 20/03 is probably corrupt. 
        // So set it to the value of next day to remove strange effect in graph
        $dataset[7] = $dataset[8];

        // create an array with values of the precious day so we can substract those values from today
        $dataset_next_day = $dataset;
        array_shift( $dataset_next_day ); // shift the array one to the left to create 
        
        // substract the value with the value of the previus day
        $data = array_map( function ($today, $tomorrow) {
            if ( isset( $tomorrow ) )
            return $tomorrow - $today ;
        }, $dataset, $dataset_next_day );
        array_pop( $data );  // last value equals NULL so discard
        return $data;
    }


    /**
     * Get the data for a specific city.
     */
    private function readCityData( $city ) {
        $series = FileManager::readSeries();
        $cityData = array_filter( $series, function ($row) use ($city) {
            return $row[1] == $city;
        });
       return $cityData;
    }


    /**
     * Get a list of available cities.
     * First, check if a cached version is available.
     * If not get them from the source file.  
     */
    public function getListOfCities() {
        return FileManager::getListOfCities();
    }





    /**
     * Read the complete dataset from cache or from RIVM source file
     */
    /* DEPRECATED
    private function readSeries() {
        if( file_exists( self::SERIES_CACHE_FILE ) && is_readable( self::SERIES_CACHE_FILE ) ) {
            require( self::SERIES_CACHE_FILE );
        } else {
            // read the RIVM source file
            $header = NULL;
            $series = array();
            $search = array( "'", "\"");
            $replace = array( "", "");
            if (($handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r')) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER )) !== FALSE) {
                    // first line is the header
                    if( ! $header ) {
                        $fieldsCount = count( $row );
                        $header = array( $row[0],$row[2], $row[4], $row[5], $row[6] );
                    } else {
                        // add a line with data to our data array
                        if( count( $row ) == $fieldsCount && ! empty( $row[2] ) ) {
                            $series[] = array( substr( $row[0], 0, 10 ) , str_replace( $search, $replace , $row[2] ) , $row[4], $row[5], $row[6] ) ;
                        }
                    }
                }
                fclose($handle);
            }

            // Sort the data
            $cityname  = array_column($series, 1);
            $date = array_column($series, 0);
            array_multisort($cityname, SORT_ASC, $date, SORT_ASC, $series);
            // Save series to cache file
            file_put_contents ( self::SERIES_CACHE_FILE , '<?php $series = ' . var_export( $series, true ) . ";", LOCK_EX );
        }
        return $series;
    }
    */







    /**
     * Create a list of cities from the RIVM source file 
     */
    /* DEPRECATED 
    private function readListOfCities() {

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
                                    $data = str_replace( array( "'", "\""), array( "", ""), $data );
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
    */


}
