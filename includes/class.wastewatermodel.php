<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

// use dokuwiki\Parsing\ParserMode\File;

/**
 * Handle the selection of city
 */
class WastewaterModel {
    const ONE_DAY = 86400;
    // private $rawSeriesWasteWater;
    private $seriesWasteWater;
    private $seriesWasteWaterInterpolated;
    private $seriesSortedByLocation;
    private $limitMaxValue = 9700;  // If RWZI measures higher value, skip that measurement for national chart
    private $mostRecenteMeasurement;
    // Split info if qe process only recent measurements and combine lateron with older calculations.
    private $splitDate;  // Init in constructor. Date to split the source series. To minimize usage of resources we calculate only most recent values 
    private $splitDateInterval;   // We need some overlap in oldest and more recent part of the dataset to get the averaging and other
                                    // calculations right. To be on the safe side, we take large overlap ( like 21 days )
    private $plantLocations;
    private $rwziProperties;
    private $rwziPropertiesInhabitantsProvinceRwzi;
    private $maxValue = 3000; // 2000; $maxValue = 1000; // 2200;
    private $colorStops = array(   // gradiënten // color conversion: https://htmlcolors.com/hex-to-hsl
            [ "stop" => 0, "h" => 0, "s" => 0, "l" => 100 ], // "#ffffff"
            [ "stop" => 0.00000001, "h" => 60, "s" => 100, "l" => 94 ],  // "#ffffe0"
            [ "stop" => 0.06, "h" => 51, "s" => 93, "l" => 89 ], // "#fdf5c8"
            [ "stop" => 0.12, "h" => 40, "s" => 99, "l" => 65 ], // "#fec44f"
            [ "stop" => 0.32, "h" => 26, "s" => 85, "l" => 50 ], // "#ec7014"
            [ "stop" => 0.8, "h" => 0, "s" => 100, "l" => 25 ], // "#800000"
            [ "stop" => 1.0, "h" => 0, "s" => 100, "l" => 25 ], // "#800000"
        );
    private $colorCategory = array( // kleur per categorie // color conversion: https://htmlcolors.com/hex-to-hsl
        [ "stop" => 0, "h" => 0, "s" => 0, "l" => 100 ], // "#ffffff"
        [ "stop" => 0.03333, "h" => 51, "s" => 93, "l" => 89 ],  // '#fdf5c8' => hsl(51, 93%, 89%)
        [ "stop" => 0.13333, "h" => 42, "s" => 99, "l" => 70 ], // '#fed066' => hsl(42, 99%, 70%)
        [ "stop" => 0.26667, "h" => 30, "s" => 77, "l" => 55 ], // '#e58d35' => hsl(30, 77%, 55%)
        [ "stop" => 0.43333, "h" => 12, "s" => 78, "l" => 45 ], // '#cc3d19' => hsl(12, 78%, 45%)
        [ "stop" => 0.6, "h" => 3, "s" => 78, "l" => 35], //  '#a11a14' => hsl(3, 78%, 35%)
        [ "stop" => 0.8, "h" => 0, "s" => 100, "l" => 17 ], // '#580000' => hsl(0, 100%, 17%)
        [ "stop" => 1.0, "h" => 0, "s" => 0, "l" => 0 ], // '#000000' => hsl(0, 0%, 0%)
    );

/*

    private $colorCategory = array( // kleur per categorie // color conversion: https://htmlcolors.com/hex-to-hsl
        [ "stop" => 0, "h" => 0, "s" => 0, "l" => 100 ], // "#ffffff"
        [ "stop" => 0.00000001, "h" => 51, "s" => 93, "l" => 89 ],  // '#fdf5c8' => hsl(51, 93%, 89%)
        [ "stop" => 0.06, "h" => 42, "s" => 99, "l" => 70 ], // '#fed066' => hsl(42, 99%, 70%)
        [ "stop" => 0.15, "h" => 30, "s" => 77, "l" => 55 ], // '#e58d35' => hsl(30, 77%, 55%)
        [ "stop" => 0.25, "h" => 12, "s" => 78, "l" => 45 ], // '#cc3d19' => hsl(12, 78%, 45%)
        [ "stop" => 0.50, "h" => 3, "s" => 78, "l" => 35], //  '#a11a14' => hsl(3, 78%, 35%)
        [ "stop" => 0.75, "h" => 0, "s" => 100, "l" => 17 ], // '#580000' => hsl(0, 100%, 17%)
        [ "stop" => 1.0, "h" => 0, "s" => 0, "l" => 0 ], // '#000000' => hsl(0, 0%, 0%)
    );
*/





    /**
     * Constructor
     */
    public function __construct() {
        $this->splitDate = new DateTimeImmutable( '-120 days' );    // Date to split the source series. To minimize usage of resources we calculate only most recent values 
        $this->splitDateInterval = new DateInterval( "P21D" );    // We need some overlap in oldest and more recent part of the dataset to get the averaging and other
    }







    /**
     * Get the data to create the charts for a specific waste water plant
     */
    public function getChartData( $plantName, $ajax = true ) {
        $seriesSortedByLocation = $this->getSeriesSortedByLocation();
        if ( ! in_array( $plantName, array_column( $seriesSortedByLocation, FileManager::HEADER_RWZI_NAME ) ) ) {
            exit();
        }



        // filter the series of all plants and return only the series for the requested plant 
        $plantData = array_filter( $seriesSortedByLocation, function ( $row ) use ( $plantName ) {
            return $row[ FileManager::HEADER_RWZI_NAME ] == $plantName;
        });
        unset( $seriesSortedByLocation );

        $firstRow = reset( $plantData ); // read the first row to get some general data about the plant.

        // prepare the data for JSON and javascript
        $plant = array( "code" => $firstRow[ FileManager::HEADER_RWZI_CODE ],
                        "name" => $firstRow[ FileManager::HEADER_RWZI_NAME ],
                        "type" => 'rwzi' );
        $labels = $this->prepareLabels( array_column( $plantData, FileManager::HEADER_DATE ) );
        $rnaValues = array_column( $plantData, FileManager::HEADER_RNA );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartWasteWaterLabels' , $labels );
            $ajaxResponse->addData( 'chartWasteWaterData', $rnaValues );
            $ajaxResponse->addData( 'plant', $plant );
            $ajaxResponse->addData( 'type', array( 'category' => 'wastewater', 'region' => 'rwzi' ) );
            $ajaxResponse->send();
        }
    }
 






    /**
     * Get the labels (x-axis) and format the data to be used by chart.js
     */
    private function prepareLabels( $dataset ) {
        if ( ! ArrayUtils::checkArray( $dataset ) ) {
            new LogException( 'Empty dataset', __LINE__ );
        }
        $labels = array_map( function ($row) {
            return date( "j/n", strtotime ( $row ) );
        }, $dataset );

        if ( ! ArrayUtils::checkArray( $labels ) ) {
            new LogException( 'Empty labels', __LINE__ );
        }
        return $labels;
    }







    /**
     * Get the locations and the most recent measurement. 
     * The rawSeries is already sorted by location ASC and date DESC.
     * Notice that rawSeries only consists of real measurements, so there are no rows with empty measurements.
     * 
     * Determine the color of the location to be used on the map.
     * Save it to a cache file. 
     */
    public function getPlantLocations() {
        if( FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS ) ) {
           return true;
        }
        // vraag me af of persistent hier winst oplevert.
        
        $this->getPropertiesRwzi();
        $rawSeriesWasteWater = $this->getRawSeriesWasteWater();

        $this->plantLocations = array();
        $dateTimeLimit = time() - self::ONE_DAY * 21; // 21 days
        $i=0;
        $currentLocation = 0;
        foreach( $rawSeriesWasteWater as $row ) {

            // get only the first (= most recent) entry of a location. Skip older entries and continue to next location.
            // Skip Woerden (14002), skip Katwoude (12016)
            /*
            if( $currentLocation == $row[ FileManager::HEADER_RWZI_CODE ]
                    || 14002 != $row[ FileManager::HEADER_RWZI_CODE ]
                    || 12016 != $row[ FileManager::HEADER_RWZI_CODE ] ) {
                continue;
            }
            */

            if( $currentLocation != $row[ FileManager::HEADER_RWZI_CODE ] 
                    && 14002 != $row[ FileManager::HEADER_RWZI_CODE ] 
                    && 12016 != $row[ FileManager::HEADER_RWZI_CODE ] ) {
 
                $currentLocation =  $row[ FileManager::HEADER_RWZI_CODE ];
                $this->plantLocations[ $i ]["code"] = $row[ FileManager::HEADER_RWZI_CODE ];
                $this->plantLocations[ $i ]["name"] = $row[ FileManager::HEADER_RWZI_NAME ];
                $timestamp = strtotime( $row[ FileManager::HEADER_DATE ] ) ;
                $this->plantLocations[ $i ]["date"] = date( 'j/n', $timestamp );
                $this->plantLocations[ $i ]["rna"] = $row[ FileManager::HEADER_RNA ];
                if( isset( $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ] ) ) {
                    $this->plantLocations[ $i ]["x"] = $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ][ FileManager::HEADER_RWZI_X ];
                    $this->plantLocations[ $i ]["y"] = - $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ][ FileManager::HEADER_RWZI_Y ];
                }
                $this->plantLocations[ $i ]["z"] = 1;
                // older than timelimit (three weeks) means measurement is coonsidered ooutdated. => set color (almost) invisible
                $opacity = ( $timestamp > $dateTimeLimit ) ? 0.9 : 0.1; 
                // Deprecated: color by gradient: $this->plantLocations[ $i ]["color"] = $this->valueToColorGradient( $row[ FileManager::HEADER_RNA ] );
                $this->plantLocations[ $i ]["color"] = $this->valueToColorCategory( $row[ FileManager::HEADER_RNA ], $opacity );
                $i++;
            }
        }
        unset( $rawSeriesWasteWater );

        if ( ! ArrayUtils::checkArray( $this->plantLocations ) ) {
            new LogException( 'Empty plant locations', __LINE__ );
        } else {
            array_multisort( array_column( $this->plantLocations, 'rna' ), SORT_ASC, $this->plantLocations ); // sort the data to get the high value bubbles in the map on top if overlapping
            FileManager::saveToCache( $this->plantLocations, FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS , $type = 'javascript', 'plantLocations' );
        }
        return;
    }





    

    /**
     * Get the raw data from the source file and sort it by location (ASC) and date (DESC)
     */
    private function getRawSeriesWasteWater( $onlyRecent = false ) {
        $rawSeriesWasteWater = FileManager::readCsvWasteWater();
        if ( ! ArrayUtils::checkArray( $rawSeriesWasteWater ) ) {
            new LogException( 'Empty raw series waste water reading CVS file', __LINE__ );
        }

        $date  = array_column( $rawSeriesWasteWater, FileManager::HEADER_DATE );
        array_multisort( $date, SORT_DESC, $rawSeriesWasteWater );

        // There are two choices: calculate only recent partor calculate all.
        // If we calculate only most recent part,we need to combine later on with older part.
        // So we can only choose to calculate recent if the oldest part is available in a persistent cache file.
        // If we calculate only the most recent part we need to slice the data set
        // Get a slice of the raw data. The complete dataset had grown very large, so we keep the 
        // first part in a persistent cache file and calculate each day only the most recent part

        // The following files can be split into recent and old / persistent part
        // FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE = "data/cache/cache.waste-water-series-per-province-sorted-by-date.php";
        // FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY = "data/cache/cache.waste-water-totals-sorted-by-day.php";
        if( $onlyRecent ) {
            $sliceDateForProcessing = $this->splitDate->sub( $this->splitDateInterval );

            $date  = array_column( $rawSeriesWasteWater, FileManager::HEADER_DATE );
            $key = array_search($sliceDateForProcessing->format( "Y-m-d" ), $date );
            $rawSeriesWasteWater = array_slice( $rawSeriesWasteWater, 0, $key );
            unset( $date );

        }
        // sort
        $location  = array_column( $rawSeriesWasteWater, FileManager::HEADER_RWZI_CODE );
        $date  = array_column( $rawSeriesWasteWater, FileManager::HEADER_DATE );
        array_multisort( $location, SORT_ASC, $date, SORT_DESC, $rawSeriesWasteWater );
        unset( $location, $date );

        // check for double entries. It has happened.
        foreach( $rawSeriesWasteWater as $key => $row ) {
            if( isset(  $rawSeriesWasteWater[ $key - 1 ][ FileManager::HEADER_DATE ] ) 
                            && $rawSeriesWasteWater[ $key - 1 ][ FileManager::HEADER_DATE ] == $row[ FileManager::HEADER_DATE ]
                            && $rawSeriesWasteWater[ $key - 1 ][ FileManager::HEADER_RWZI_CODE ] == $row[ FileManager::HEADER_RWZI_CODE ]
                            && $rawSeriesWasteWater[ $key - 1 ][ FileManager::HEADER_RNA ] == $row[ FileManager::HEADER_RNA ] 
            ) {
                unset( $rawSeriesWasteWater[ $key ] );
            }
        }

        return $rawSeriesWasteWater;
    }








    /**
     * Get the cache file with series sorted by loacation ASC and date ASC.
     */
    public function getSeriesSortedByLocation( ) {
        if( FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION ) ) {
            $seriesSortedByLocation = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION ); 
            if ( ! ArrayUtils::checkArray( $seriesSortedByLocation ) ) {
                new LogException( 'Empty series sorted by location reading cache file ' 
                                        . FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION, __LINE__ );
            }
            return $seriesSortedByLocation;
        }
        // vraag me af of hier winst te halen valt.
        //$processOnlyRecentDates = FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION_PERSISTENT ) ? true : false;
        $seriesSortedByLocation = $this->getRawSeriesWasteWater();

        $date = array_column( $seriesSortedByLocation, FileManager::HEADER_DATE );
        $location = array_column( $seriesSortedByLocation, FileManager::HEADER_RWZI_CODE );
        array_multisort( $location, SORT_ASC, $date, SORT_ASC, $seriesSortedByLocation );
        unset( $date, $location );

        if ( ! ArrayUtils::checkArray( $seriesSortedByLocation ) ) {
            new LogException( 'Empty series sorted by location', __LINE__ );
        } else {
            FileManager::saveToCache( $seriesSortedByLocation, FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION );
        }
        return $seriesSortedByLocation;
    }










    /**
     * 
     */
    private function getPropertiesInhabitantsProvinceRwzi() {
        if( ! empty( $this->rwziPropertiesInhabitantsProvinceRwzi ) ) return true;
        $this->rwziPropertiesInhabitantsProvinceRwzi = FileManager::readCsvWastewaterPlantsInhabitantsProvinceProperties();
        if( ! ArrayUtils::checkArray( $this->rwziPropertiesInhabitantsProvinceRwzi  ) ) {
            new LogException( 'Empty RWZI inhabitants province properties reading CSV file ', __LINE__ );
            return false;
        }
        return true;
    }










   /**
     * Creates interpolated series from the raw datasets.
     * Filters out the very high values exceeding the max limit (currently at 8000 since Omikron)
     * 
     * Interpolated series are used to aggregate the data to higher levels, like per province or national.
     * 
     * Currently only used by getChartAndMapDataPerProvince() to calculate data per province 
     * 
     * Skip Woerden (14002)
     * Skip Katwoude (12016)
     */
    public function getSeriesWasteWater( $onlyRecent = false ) {
        $rawSeriesWasteWater = $this->getRawSeriesWasteWater( $onlyRecent );
        $seriesWasteWater = array();

        // Skip unrealistic high values. It distorts the national chart too much.
        // Split array in sub series per station
        $currentRwziCode = -1;
        foreach( $rawSeriesWasteWater as $row ) {
            // skip Woerden, skip Katwoude
            if(  14002 == $row[ FileManager::HEADER_RWZI_CODE ] || 12016 == $row[ FileManager::HEADER_RWZI_CODE ] ) {
                continue;
            }
            // skip vaules above max limit
            if( isset($row[ FileManager::HEADER_RNA ] ) && $row[ FileManager::HEADER_RNA ] > $this->limitMaxValue ) {
                continue;
            }

            // split the raw series into sub series of measurements per station.
            if ( $row[ FileManager::HEADER_RWZI_CODE ] != $currentRwziCode ) { // new rwzi station
                $currentRwziCode = $row[ FileManager::HEADER_RWZI_CODE ];
                $seriesWasteWater[ $currentRwziCode ] = array( 
                    FileManager::HEADER_RWZI_CODE => $row[ FileManager::HEADER_RWZI_CODE ],
                    FileManager::HEADER_RWZI_NAME => $row[ FileManager::HEADER_RWZI_NAME ],
                );
            }
            $seriesWasteWater[ $currentRwziCode ][ "dataset" ][ $row[ FileManager::HEADER_DATE ] ] = $row[ FileManager::HEADER_RNA ];
        }
        unset( $rawSeriesWasteWater );
        return $seriesWasteWater;
    }






















    /**
     * Get the data of all waste water plants to create the charts for all the waste water plants together.
     * First, create seperate datasets with all measurements for each waste water plant
     * Second, interpolate all these measuremnts to create a daily (interpolated) value per waste water plant.
     * Third, get for each day the average of all these plants for the whole country.
     * Fourth, save the result to a cache file.
     * Fifth, get the data ready for chart.js and return it via an AJAX response.
     */ 
    public function getChartDataTotals( $ajax = true ) {
        // if ( false ) {
        // /* 
        if( FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY ) ) {
            $totalsSortedByDay = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY );
            if ( ! ArrayUtils::checkArray( $totalsSortedByDay ) ) {
                new LogException( 'Empty totals Sorted By Day reading cache file ' . FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY, __LINE__ );
            }
            //*/
        } else {
            $processOnlyRecentDates = FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT ) ? true : false;
            $extrapolateDays = 1;

            $this->getPropertiesRwzi();
            $rawSeriesWasteWater = $this->getRawSeriesWasteWater( $processOnlyRecentDates );
            $seriesAll = array();
            $currentRwziCode = 0;
            $i=-1;

            

            /** Skip unrealistic high values. It distorts the national chart too much **/
            // Skipping RWZI Woerden (14002) and RWZI Katwoude (12016). It distorts the national chart too much
            // Skipping individual points
            foreach( $rawSeriesWasteWater as $key => $row ) {
                if( isset($row[ FileManager::HEADER_RWZI_CODE ] ) 
                            && ( 14002 == $row[ FileManager::HEADER_RWZI_CODE] || 12016 == $row[ FileManager::HEADER_RWZI_CODE] ) ) {
                    unset( $rawSeriesWasteWater[ $key ] );
                    continue;
                }
                if( isset($row[ FileManager::HEADER_RNA ] ) && $row[ FileManager::HEADER_RNA ] > $this->limitMaxValue ) {
                    unset( $rawSeriesWasteWater[ $key ] );
                }
            }


            /** create seperate datasets with all measurements for each waste water plant **/
            $maxRwziFactor = 0;
            foreach( $rawSeriesWasteWater as $row ) {
                if ( array_key_exists( $row[ FileManager::HEADER_RWZI_CODE ] , $this->rwziProperties ) ) {
                    if ( $row[ FileManager::HEADER_RWZI_CODE ] != $currentRwziCode ) {
                        $currentRwziCode = $row[ FileManager::HEADER_RWZI_CODE ];
                        $i++;
                        $seriesAll[ $i ] = array( 
                            FileManager::HEADER_RWZI_CODE => $row[ FileManager::HEADER_RWZI_CODE ],
                            FileManager::HEADER_RWZI_NAME => $row[ FileManager::HEADER_RWZI_NAME ],
                            FileManager::HEADER_RWZI_FACTOR => 
                                $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ] [ FileManager::HEADER_INHABITANTS ] 
                        );
                        $maxRwziFactor += $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ] [ FileManager::HEADER_INHABITANTS ];
                    }
                    $seriesAll[ $i ][ "dataset" ][ $row[ FileManager::HEADER_DATE ] ] = $row[ FileManager::HEADER_RNA ];
                }
            }
            unset( $rawSeriesWasteWater ); // UNSETW



            /** TESTING */
            /** Calculate the difference in measurements if 1, 2, 3, 4, or 5 days apart. */
            /*

            $i=0;
            $k=0;
            $maxNumberOfDays = 5;
            $previous = array();
            $error = array();
            $endDate = new DateTime(  );
            foreach( $seriesAll as $series ) {

                $keyDate = new DateTime( "2022-01-01" );

                for( $j = 0; $j < $maxNumberOfDays + 1; $j++ ) {
                    $previous[ $j ] = NULL;
                }
                do {
                    $keyDateFormat = $keyDate->format( "Y-m-d" );
                    //echo $keyDateFormat . ';';
                    if( isset( $series[ "dataset" ][ $keyDateFormat] ) ) {
                        $current = $series[ "dataset" ][ $keyDateFormat ];
                        //echo $series[ "dataset" ][ $keyDateFormat ] . ';';
                        foreach( $previous as $key => $value ) {
                            if( ! is_null( $value ) ) {
                                $diff = $value - $current;
                                // $error[ $keyDateFormat ][ $key ][0] = 'checl;';
                                // var_dump( $error[ $keyDateFormat ][ $key ] );
                                $count = ( isset( $error[ $keyDateFormat ][ $key ] ) ) ? count( $error[ $keyDateFormat ][ $key ] ) + 1 : 0;
                                //$count = count( $error[ $keyDateFormat ][ $key ] );
                                // echo '<br>'.'['.$keyDateFormat.']'.'['.$key.']';var_dump( $count );
                                // $error[ $keyDateFormat ][ $key ][0] = 'checl;';
                                if( $current != 0 ) {
                                    $error[ $keyDateFormat ][ $key ][ $count ] =  $diff / $current;
                                }
                                //echo $error[ $keyDateFormat ][ $key ][ $count ];
                                //echo $diff .';' . ( $diff / $current );
                                $k++;
                            }
                        }
                    } else {
                        $current = NULL;
                    }

                    foreach( $previous as $key => $value ) {
                            $previous[ $key + 1 ] = $value;
                    }
                    unset( $previous[ $maxNumberOfDays + 1 ] );
                    $previous[ 1 ] = $current;

                    // echo '<br>';
                    $keyDate->modify( '+1 day' );
                } while( $keyDate < $endDate );
 
            }

            $errorSubTotals = array();
            foreach( $error as $row ) {
                foreach( $row as $key => $arr ) {
                    if( isset( $errorSubTotals[ $key ] ) ) {
                    $errorSubTotals[ $key ] = array_merge( $errorSubTotals[$key ], $arr );
                    } else {
                        $errorSubTotals[ $key ] = $arr;
                    }
                }
            }
            //var_dump( $errorSubTotals  );

            ksort( $errorSubTotals );
            foreach( $errorSubTotals as $key => $sub ) {
                $count = count( $sub );
                $sum = 0;
                foreach ($sub as $value){
                    $sum += abs( $value );
                }

                //$sum = array_sum( $sub );
                $avg = round( $sum * 100 / $count , 1);
                //var_dump(  $sum );
                //var_dump( count( $sub ) );
                echo 'dag '.$key . ': ' . $avg .'%<br>';
            }

            exit();
            //*/











            /** Interpolate measurements for each individual waste water plant **/
            // At this point we got all the measurements per station.
            // Now we interpolate days with no measurements between measurements
            // The RwziCode is add to the function for error detection: If error, we know at which station.
            foreach( $seriesAll as $key => $serie ) {
                $seriesAll[ $key ]["interpolated"] = $this->getInterpolatedLocation( 
                            $serie["dataset"], $seriesAll[ $key ][ FileManager::HEADER_RWZI_CODE ] 
                        );
            }
            // var_dump( array_slice( $seriesAll, 0, 1 ) ); exit(); 




            /** Extrapolate measurements for each individual waste water plant **/
            // We've got the interpolated data voor de dates without measurement.
            // But after the last measuremnt we have no data. 
            // Use extrapolation for one day to add a day to the series.
            //$j=0;
            // $mostRecenteMeasurement is de datum met de laatste echte meting.
            // Na die datum moeten we de series voor de grafiek sowieso afkappen
            // Let op dat we een DateTimeImmutable pakken, want anders verandert de datum nog mee 
            // met de loop daarna waar $date->modify( '+1 day' ) staat!!!
            $mostRecenteMeasurement = new DateTimeImmutable( '2020-01-01' );
            foreach( $seriesAll as $key => $serie ) {
                // check the date with the most recent measurement
                $firstSet = reset( $serie["interpolated"] );
                $date = new DateTime( $firstSet[ FileManager::HEADER_DATE ] );
                if( $date > $mostRecenteMeasurement ) {
                    $mostRecenteMeasurement = DateTimeImmutable::createFromMutable( $date );
                }
                // extrapolate the series by copying the most recent measurement one day ahead
                for( $i = 0; $i < $extrapolateDays; $i++ ) {
                    $date->modify( '+1 day' );
                    $newKey = $date->format( "Y-m-d" );
                    $newVal = $firstSet[ FileManager::HEADER_RNA ];
                    $extrapolateItem = array(
                        FileManager::HEADER_DATE => $newKey,
                        FileManager::HEADER_RNA => $newVal
                    );
                    array_unshift( $seriesAll[ $key ]["interpolated"], $extrapolateItem );
                }

            }
            // var_dump( $mostRecenteMeasurement ); exit(); 
            // var_dump( array_slice( $seriesAll, 0, 1 ) ); exit(); 



            /** Sum all series together for the national chart **/
            // We got a complete data series per station.
            // Now it is time to put the data together to create the national chart 
            $chartData = array();
            $startDate = new DateTime( '2020-09-07' );
            foreach( $seriesAll as $key => $serie ) {
               // if( $serie[FileManager::HEADER_RWZI_CODE ] != 13041 ) continue;
                $factor = $serie[ FileManager::HEADER_RWZI_FACTOR ];
                foreach( $serie["interpolated"] as $row ) {
                    $date = new DateTime( $row[ FileManager::HEADER_DATE ] );
                    if( $date < $startDate ) continue; 
                    if( $date > $mostRecenteMeasurement ) continue; // Don't use values beyond the last real measurement.
                    if( ! is_null( $row[ FileManager::HEADER_RNA ] ) ) {
                        if( ! isset( $chartData[ $row[ FileManager::HEADER_DATE ] ] ) ) {
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ];
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] = $factor * $row[ FileManager::HEADER_RNA ];
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RWZI_FACTOR ] = $factor;
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][FileManager::HEADER_COUNT ] = 1;
                        } else {
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] += $factor * $row[ FileManager::HEADER_RNA ];
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RWZI_FACTOR ] += $factor;
                            $chartData[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_COUNT ]++;
                        }
                    }
                    
                }
            }
            // var_dump( $chartData ); exit();

            // just a check if all is well
            if ( ! ArrayUtils::checkArray( $chartData ) ) {
                new LogException( 'Empty chart data', __LINE__ );
            }
            // Om de aantallen metingen te zien:
            // sort( $chartData ) ;
            // echo( 'only recent: ' . $processOnlyRecentDates );
            // var_dump( array_slice( $chartData, 0, 100 ) );
             
            // var_dump( $chartData );
            // exit();


            /** If only recent values are calculated, combine them with the older calculations */
            // First, get hold of the older data. We already checked it is available
            if( $processOnlyRecentDates ) {
                $totalsSortedByDayPersistent = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT );
            }




            /** Create the final dataset for the national chart **/
            // We have all the data per date. Now create the final dataset:
            // Divide values by station-count for that day, 
            // remove values if station count is less than 60% of total stations
            // and som small tweakings
            $totalsSortedByDay = array();
            $start = new DateTime( );
            $splitDate = ( $processOnlyRecentDates ) ? $this->splitDate : new DateTime( '1970-01-01' );
            $end = new DateTime( '2020-03-14' );
            $diff = $end->diff( $start );
            $interval = DateInterval::createFromDateString('-1 day');
            $period = new DatePeriod($start, $interval, $diff->days);
    
            //$dateWithManyStations = new DateTime( '2020-10-01' );  // do not use data before this date.
            $i=0;
            foreach ( $period as $date ) {
                $key = $date->format( "Y-m-d" );
                if( $processOnlyRecentDates && $date < $splitDate ) { // lets get the previous calculated values
                    $totalsSortedByDay[ $key ] = $totalsSortedByDayPersistent[ $key ];
                } else {    // Let's get the new calculated data from recent dates.
                    $totalsSortedByDay[ $key ][ FileManager::HEADER_DATE ] = $key;
                    $totalsSortedByDay[ $key ][ FileManager::HEADER_RNA ] = NULL;
                    $totalsSortedByDay[ $key ][ FileManager::HEADER_RWZI_FACTOR ] = 0;
                    $totalsSortedByDay[ $key ][ FileManager::HEADER_COUNT ] = 0;
                    if ( key_exists( $key, $chartData ) ) {
                        $totalsSortedByDay[ $key ][ FileManager::HEADER_RNA ]
                                        = $chartData[ $key ][ FileManager::HEADER_RNA ] / $chartData[ $key ][ FileManager::HEADER_RWZI_FACTOR ];
                        $totalsSortedByDay[ $key ][ FileManager::HEADER_RWZI_FACTOR ] = round( $chartData[ $key ][ FileManager::HEADER_RWZI_FACTOR ], 2 );
                        $totalsSortedByDay[ $key ][ FileManager::HEADER_COUNT ] = $chartData[ $key ][ FileManager::HEADER_COUNT ];
                    }
                }
            }
            unset( $chartData );
            /** Echo data in csv format to use in excel for example **/
            /*
            $output = '';
            foreach( $totalsSortedByDay as $row ) {
                foreach( $row as $value ) {
                    $output .= $value . ';';
                }
                $output .= '<br>';
            }
            echo str_replace( '.', ',', $output);
            //var_dump($totalsSortedByDay );
            exit();
            */


            // var_dump( reset( $totalsSortedByDay ) ); exit();


            /** Save values to cache files */
            // not really necessary to sort for the chart, but to be consistent with other (cache) files
            $date = array_column( $totalsSortedByDay, FileManager::HEADER_DATE );
            array_multisort( $date, SORT_ASC, $totalsSortedByDay );
            if ( ! ArrayUtils::checkArray( $totalsSortedByDay ) ) {
                new LogException( 'Empty totals sorted by day' , __LINE__ );
            } else {
                FileManager::saveToCache( $totalsSortedByDay, FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY );
                FileManager::saveToCache( $totalsSortedByDay, FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT );

                // get the most recent value taking the $this->limitMaxValue into account
                $totalsSortedByDay = array_values( $totalsSortedByDay );

                $key = count( $totalsSortedByDay ) - 1;
                while( $totalsSortedByDay[ $key ][ FileManager::HEADER_RWZI_FACTOR]  / $maxRwziFactor < 0.6 ) {
                    $key--;
                }
                $totalsSortedByDay[ $key ][ "average" ] = array_sum( array_column( array_slice( $totalsSortedByDay, $key-6, 7 ), FileManager::HEADER_RNA ) ) / 7 ;
                
                FileManager::saveToCache( $totalsSortedByDay[ $key ], FileManager::CACHE_WASTE_WATER_TOTALS_MOST_RECENT_VALUE );
            }
        }   // end creating cache files



        if ( $ajax ) {
            $labels = $this->prepareLabels( array_column( $totalsSortedByDay, FileManager::HEADER_DATE ) );
            $rnaValues = array_column( $totalsSortedByDay, FileManager::HEADER_RNA );
            $percentages = array_column( $totalsSortedByDay, FileManager::HEADER_RWZI_FACTOR );
            $plant = array( "code" => 0, "name" => 'Nederland' );

            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartWasteWaterLabels' , $labels );
            $ajaxResponse->addData( 'chartWasteWaterData', $rnaValues );
            $ajaxResponse->addData( 'chartWasteWaterFactor', $percentages );
            $ajaxResponse->addData( 'plant', $plant );
            $ajaxResponse->addData( 'type', array( 'category' => 'wastewater', 'region' => 'country' ) );
            $ajaxResponse->send();
        } else { 
            return $totalsSortedByDay;
        }
    }


    /**
     * Get the lastest relevant value of the national total waste water values.
     * Take into account the reliability of the measurements. Only 
     * values larger than $this->limitMaxValue are allowed.
     */
    public function getLatestValue() {
        if( FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_TOTALS_MOST_RECENT_VALUE ) ) {
            $totalsMostRecentValue = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_TOTALS_MOST_RECENT_VALUE );
            if ( ! ArrayUtils::checkArray( $totalsMostRecentValue ) ) {
                new LogException( 'Empty totals Sorted By Day reading cache file ' . FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY, __LINE__ );
            }
            return $totalsMostRecentValue;
        } else {
            return false;
        }
    }










    /** 
     * Calculate the standard deviation of array elements
     */ 
    private function getStandardDeviation($arr) {
        $num_of_elements = count($arr);
        $variance = 0.0;
        // calculating mean using array_sum() method
        $average = array_sum( $arr ) / $num_of_elements;
        // sum of squares of differences between all numbers and means.
        foreach($arr as $i) {
            $variance += pow( ( $i - $average ), 2 );
        }
        return (float) sqrt( $variance / $num_of_elements );
    }
      





    /**
     * Get the data of all waste water plants PER PROVINCE to create the charts and map 
     * for all the waste water plants IN THE PROVINCE together.
     * First, create seperate datasets with all measurements for each waste water plant
     * Second, interpolate all these measuremnts to create a daily (interpolated) value per waste water plant.
     * Third, get for each day the average of all these plants for the whole country.
     * Fourth, save the result to a cache file.
     * Fifth, get the data ready for chart.js and return it via an AJAX response.
     */ 
    public function getChartAndMapDataPerProvince( $requestedProvinceCode = 'all',  $ajax = true ) {
        // if ( false ) {
        // /* 
        if( FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE ) 
            && FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_MAP_VALUES_PER_PROVINCE ) )
        {
            $seriesProvinceSortedByDate = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE );
            if ( ! ArrayUtils::checkArray( $seriesProvinceSortedByDate ) ) {
                new LogException( 'Empty series province sorted by date reading cache file ' . FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE, __LINE__ );
            }
        //    */
        } else {
            $processOnlyRecentDates = FileUtils::isFileAvailable( FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT ) ? true : false;
            $seriesWasteWater = $this->getSeriesWasteWater( $processOnlyRecentDates );
            // $rwziCodeColumn = array_column( $seriesWasteWater, FileManager::HEADER_RWZI_CODE );
            // var_dump( $rwziCodeColumn );

            $this->getPropertiesRwzi();
            foreach( $seriesWasteWater as $key => $row ) {
                $rwziCode = $row[ FileManager::HEADER_RWZI_CODE ];
                $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_X ] = $this->rwziProperties[ $rwziCode ][ FileManager::HEADER_RWZI_X ];
                $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_Y ] = $this->rwziProperties[ $rwziCode ][ FileManager::HEADER_RWZI_Y ];
            }
            
            // get all stations in the vicinity
            $vicinityFactor = 20000; // 20 kilometer
            foreach( $seriesWasteWater as $key => $row ) {
                foreach( $this->rwziProperties as $rwziProp ) {
                    // if( $rwziProp[ FileManager::HEADER_RWZI_CODE ] == $row[ FileManager::HEADER_RWZI_CODE ] ) continue;
                    $deltaX = abs( $rwziProp[ FileManager::HEADER_RWZI_X ] - $row[ FileManager::HEADER_RWZI_X ] );
                    $deltaY = abs( $rwziProp[ FileManager::HEADER_RWZI_Y ] - $row[ FileManager::HEADER_RWZI_Y ] );
                    if( $deltaY < $vicinityFactor && $deltaX < $vicinityFactor ) {
                        $seriesWasteWater[ $key ]["vicinity"][] = $rwziProp[ FileManager::HEADER_RWZI_CODE ];
                    }
                    
                }
            }

            // get all the measurements of vicinity stations for xx days.
            $todayDateTime = new DateTimeImmutable();
            $limitDateTime = $todayDateTime->modify( '-120 days' );
            $tTable_025 = array(0,12.706,4.303,3.182,2.776,2.571,2.447,2.365,2.306,2.262,2.228,2.201,2.179,2.160,2.145,2.131,2.120,2.110,2.101,2.093,2.086,2.080,2.074,2.069,2.064,2.060,2.056,2.052,2.048,2.045,2.042,2.000,1.980,1.962,1.960);

            $tTable_005 = array(0,63.656,9.925,5.841,4.604,4.032,3.707,3.499,3.355,3.250,3.169,3.106,3.055,3.012,2.977,2.947,2.921,2.898,2.878,2.861,2.845,2.831,2.819,2.807,2.797,2.787,2.779,2.771,2.763,2.756,2.750,2.660,2.617,2.581,2.576);
            $tTable_001 = array(0,318.289,22.328,10.214,7.173,5.894,5.208,4.785,4.501,4.297,4.144,4.025,3.930,3.852,3.787,3.733,3.686,3.646,3.610,3.579,3.552,3.527,3.505,3.485,3.467,3.450,3.435,3.421,3.408,3.396,3.385,3.232,3.160,3.098,3.091 );
            $tTable_0005 = array(0,636.578,31.600,12.924,8.610,6.869,5.959,5.408,5.041,4.781,4.587,4.437,4.318,4.221,4.140,4.073,4.015,3.965,3.922,3.883,3.850,3.819,3.792,3.768,3.745,3.725,3.707,3.689,3.674,3.660,3.646,3.460,3.373,3.300,3.291 );

                $teller = 0;
                $teller2 = 0;
                $outliers = array();
            foreach( $seriesWasteWater as $key => $row ) {
                foreach( $row["dataset"] as $date => $measurement ) {

                    $currentDateTime = new DateTime( $date );
                    //if( $currentDateTime < $limitDateTime ) break; // no need to go more than 30 days back
                    $currentDateKey = $currentDateTime->format( 'Y-m-d' );
                    $currentDateTime->modify( '+2 days' );
                    //$keyDateTime = $currentDateTime->modify( '+1 day' );
                    $keyDateTime = $currentDateTime;
                    $keyDate = $keyDateTime->format( 'Y-m-d' );
                    for( $i=0; $i < 3; $i++ ) {
                        $keyDateTime->modify( '-1 day' );
                        $keyDate = $keyDateTime->format( 'Y-m-d' );
                        foreach( $row[ "vicinity" ] as $stationKey ) {
                            if(  isset( $seriesWasteWater[ $stationKey ] ) ) {
                                // if(  isset( $seriesWasteWater[ $stationKey ]["dataset"][ $keyDate ] ) ) {
                                if(  isset( $seriesWasteWater[ $stationKey ]["dataset"][ $keyDate ] ) ) {
                                    //var_dump( $seriesWasteWater[ $stationKey ]["dataset"][ $keyDate ] );
                                   // $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][] = $keyDate;
                                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][] =  $seriesWasteWater[ $stationKey ]["dataset"][ $keyDate ];
                                }
                            }
                            //exit();
                        }
                    }
                    // Check this for calculating 95% Confidence Interval: https://www.statisticshowto.com/probability-and-statistics/confidence-interval/#CISample
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "sampleSize" ] 
                            = count( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ] );
                    $tKey = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "sampleSize" ] - 1;
                        //
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "average"] 
                            = array_sum( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ] )
                            / $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "sampleSize" ];
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "standardDeviation"] 
                            = $this->getStandardDeviation( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ] );



                    // outlier
                    $outlierFactor = 3; // ( factor * stdDev ) +/- average = upper/lower limit for outliers.
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierUpperLimit" ] 
                            = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "average"] 
                            + ( $outlierFactor * $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "standardDeviation"] );
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierLowerLimit" ] 
                            = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "average"] 
                            - ( $outlierFactor * $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "standardDeviation"] );
                    if( $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] > $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierUpperLimit" ] ) {


                            /*
                         $seriesWasteWater[ $key ][ "datasetOutlier" ][ $currentDateKey ] = NULL;
                                //= $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] . ' is above outlier limit ' 
                                //.  round( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierUpperLimit" ] );
                        $outlier[] = $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_NAME ] .': ' . $currentDateKey  .' => ' 
                                        // . $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] . ';' 
                                        . $seriesWasteWater[ $key ][ "datasetOutlier" ][ $date ]  . ' at an average of  '
                                        . round( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $date ][ "average"] ) . '<br>';

                                        */
                    
                    } else if( $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] <  $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierLowerLimit" ] ) {
                        // $seriesWasteWater[ $key ][ "datasetOutlier" ][ $currentDateKey ] = NULL;
                        //= $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] . ' is below outlier limit '
                        //. $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "outlierLowerLimit" ];
                    } else {
                        $seriesWasteWater[ $key ][ "datasetOutlier" ][ $currentDateKey ] = $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ];
                    
                    }


                    


                    // *** TESTING: skip the outliers elimination:
                   // $seriesWasteWater[ $key ][ "datasetOutlier" ][ $currentDateKey ] = $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ];










                    // Alfa en betrouwbaarheidsinterval
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "tValue" ] 
                                = ( isset( $tTable[ $tKey ] ) ) ? $tTable_0005[ $tKey ] : end( $tTable_0005 );   
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLimitInterval" ]
                            = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "tValue" ] 
                            * ( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "standardDeviation"]
                            / sqrt( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "sampleSize" ] ) ); 
                    
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaUpperLimit" ] 
                            = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "average"] 
                            + $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLimitInterval" ];
                    $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLowerLimit" ] 
                            = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "average"] 
                            - $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLimitInterval" ];
                    if( $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] >  $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaUpperLimit" ] ) {
                        $seriesWasteWater[ $key ][ "datasetSmooth" ][ $currentDateKey ] 
                                = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaUpperLimit" ];
                                //= $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] . ' is above limit ' 
                                //.  $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaUpperLimit" ];
                    } else if( $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] <  $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLowerLimit" ] ) {
                        $seriesWasteWater[ $key ][ "datasetSmooth" ][ $currentDateKey ] 
                        = $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLowerLimit" ];
                        // = $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ] . ' is below limit '
                        // . $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $currentDateKey ][ "alfaLowerLimit" ];
                    } else {
                        $seriesWasteWater[ $key ][ "datasetSmooth" ][ $currentDateKey ] = $seriesWasteWater[ $key ][ "dataset" ][ $currentDateKey ];
                   
                    }

                }

                /*
                // Garmerwolde: '1033'
                if( '1033'  == $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_CODE ] ) {
                    var_dump( $seriesWasteWater[ $key ][ "datasetOutlier" ] );

                }
                */

                /*
                echo $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_NAME ] . PHP_EOL . '<br>';


                // Amsterdam West: '11014'
                // Leeuwarden: '2011'
                if( '11014'  == $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_CODE ] ) {
                    var_dump( $seriesWasteWater[ $key ][ "vicinity" ] );
                    foreach( $seriesWasteWater[ $key ][ "dataset" ] as $date => $value ) {

                        echo $date .';' . $value . ';' .  round( $seriesWasteWater[ $key ][ "datasetSmooth" ][ $date ] ) . ';' . 
                        $seriesWasteWater[ $key ][ "datasetOutlier" ][ $date ]  .';' .
                        round( $seriesWasteWater[ $key ][ "vicinity" ][ "dataset" ][ $date ][ "average"] ) . '<br>';


                        $teller++;
                        if( $teller > 30 ) { 

                            $teller = 0;
                        break;
                        }
                    }
                }



                // var_dump( array_slice( $seriesWasteWater[ $key ][ "dataset" ], 0, 60) );
                // var_dump( $seriesWasteWater[ $key ][ "datasetSmooth" ] );

                $teller2++;
                //if( $teller2 > 5 ) break;
                //break;
                */
            }






            /*
            var_dump( $outlier );
            exit();

            // We hebben nu alle metingen in de buurt van de dagen rond de meting.
            // Om de outliers eruit te vissen, gaan we een 95% Confidence Interval bepalen.
            // Normaal zou je uitgaan van een normaal verdeling
            // Maar omdat we de dataset kleiner is dan 30 en de Standaard Deviation niet bekend is
            // pakken we de t-distributie (een beetje aangepaste normaal distributie)
            // Omdat de vrijheidsgraden altijd boven de 10 liggen gebruiken we 2.2 ( Bij normaal-dist = 1,96 )
            // voor een betrouwbaarheids interval van 95%  
            
            
            
            // var_dump( $this->rwziProperties );
            var_dump( $seriesWasteWater[ '1008' ] );
            var_dump( $seriesWasteWater[ '1008' ] );
            var_dump( $seriesWasteWater[ '1008' ][ "datasetSmooth" ] );

            exit();
*/






            /** Interpolate measurements for each individual waste water plant **/
            // At this point we got all the measurements per station.
            // Now we interpolate days with no measurements between measurements
            // The RwziCode is add to the function for error detection: If error, we know at which station.
            foreach( $seriesWasteWater as $key => $serie ) {
                if( isset( $serie["datasetOutlier"] ) ) {
                $seriesWasteWater[ $key ]["interpolated"] = $this->getInterpolatedLocation( 
                            $serie["datasetOutlier"], $seriesWasteWater[ $key ][ FileManager::HEADER_RWZI_CODE ] 
                        );
                    }
            }




           /** Extrapolate measurements for each individual waste water plant **/
            // We've got the interpolated data voor de dates without measurement.
            // But after the last measuremnt we have no data. 
            // Use extrapolation for one day to add a day to the series.
            $extrapolateDays = 1;
            // $mostRecenteMeasurement = new DateTime( '2020-01-01' );
            foreach( $seriesWasteWater as $key => $serie ) {
                $firstSet = reset( $serie["interpolated"] );
                $date = new DateTime( $firstSet[ FileManager::HEADER_DATE ] );
                for( $i = 0; $i < $extrapolateDays; $i++ ) {
                    $date->modify( '+1 day' );
                    $newKey = $date->format( "Y-m-d" );
                    $newVal = $firstSet[ FileManager::HEADER_RNA ];
                    $extrapolateItem = array(
                        FileManager::HEADER_DATE => $newKey,
                        FileManager::HEADER_RNA => $newVal
                    );
                    array_unshift( $seriesWasteWater[ $key ]["interpolated"], $extrapolateItem );
                }

            }
            



            /** Group the rwzi's together for each province  */
            $this->getPropertiesInhabitantsProvinceRwzi();


            /** Group the rwzi's together for each province  */
            $seriesProvinces = array();
            $startDate = new DateTime( '2020-09-07' );  // first measurement, Skip possible earlier measurements
            foreach( $this->rwziPropertiesInhabitantsProvinceRwzi as $row ) {
                // slip woerden (14002) and skip Katwoude (12016).
                if( 14002 == $row[ FileManager::HEADER_RWZI_CODE ] || 12016 == $row[ FileManager::HEADER_RWZI_CODE ] ) {
                    continue;
                }
                $provinceCode = $row[ FileManager::HEADER_PROVINCE_CODE ];
                $aandeel = $row[ FileManager::HEADER_RWZI_PART_PERCENTAGE ];
                $inhabitants = $row[ FileManager::HEADER_INHABITANTS ];
                $rwzi_code = $row[ FileManager::HEADER_RWZI_CODE ];
                // some waste water plants only have old measurements. Using split processing, they are not avaiable in the slice of recent series.
                if( ! isset( $seriesWasteWater [ $rwzi_code ] ) ) {
                    // echo( ' empty rwzi_code: ' . $rwzi_code );
                    continue;
                }  

                // add values of station for its part to the province
                //*** TODO: THIS BLOCK OF CODE CAN BE MADE MORE COMPACT!!
                if( ! isset( $seriesProvinces[ $provinceCode ] ) ) {
                    // The series for this province are not yet created. Let's do so.
                    $seriesProvinces[ $provinceCode ] = array();
                    $seriesProvinces[ $provinceCode ][ FileManager::HEADER_PROVINCE_CODE ] = $provinceCode;
                    $seriesProvinces[ $provinceCode ][ FileManager::HEADER_INHABITANTS ] = $aandeel * $inhabitants;
                    // add values to this province
                    foreach( $seriesWasteWater [ $rwzi_code ][ "interpolated" ] as $data ) {
                        if( is_null( $data[ FileManager::HEADER_RNA ] ) ) continue; // if NULL we continue, because we don't want to count the factor added for that day
                        $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] = $data[ FileManager::HEADER_DATE ];
                        $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] = $data[ FileManager::HEADER_RNA ] * $aandeel * $inhabitants;
                        $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RWZI_FACTOR ] = $aandeel * $inhabitants;
                    }
                } else {
                    // if the province already has a record in the series, add instead of create
                    $seriesProvinces[ $provinceCode ][ FileManager::HEADER_INHABITANTS ] += $aandeel * $inhabitants;

                    // add values to this province
                    foreach( $seriesWasteWater [ $rwzi_code ][ "interpolated" ] as $data ) {
                        
                        if( NULL === $data[ FileManager::HEADER_RNA ] ) continue;

                        if( ! isset ( $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] ) ) {
                            // The date does not exist yet,so create the record for this date
                            $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] = $data[ FileManager::HEADER_DATE ];
                            $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] = $data[ FileManager::HEADER_RNA ] * $aandeel * $inhabitants;
                            $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RWZI_FACTOR ] = $aandeel * $inhabitants;
                        } else {
                            // The dte already exists in the series fr this province, so add the new data to this record.
                            $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] += $data[ FileManager::HEADER_RNA ] * $aandeel * $inhabitants;
                            $seriesProvinces[ $provinceCode ][ "data" ][ $data[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RWZI_FACTOR ] += $aandeel * $inhabitants;
                        }
                    }
                }
            }






            // var_dump( $seriesWasteWater [1008] ); exit();
            // var_dump( array_slice( $seriesWasteWater [1008]["interpolated"], 0, 10 ) ) ; exit();







            // just an error check
            if ( ! ArrayUtils::checkArray( $seriesProvinces ) ) {
                new LogException( 'Empty chart data series provinces', __LINE__ );
            }
            




            //$dateWithManyStations = new DateTime( '2020-10-01' );  // do not check reliability before this date.
            foreach( $seriesProvinces as $provinceCode => $province ) { // iterate through all provinces



                krsort( $seriesProvinces[$provinceCode][ 'data' ] );
                //array_shift( $seriesProvinces[$provinceCode][ 'data' ] );
                $firstSet = reset( $seriesProvinces[$provinceCode][ 'data' ] );
                $date = new DateTime( $firstSet[ FileManager::HEADER_DATE ] );
                $dateKey = $date->format( "Y-m-d" );




                // delete the last item, since this is only extrapolated data and not a single real measurement.
                unset( $seriesProvinces[$provinceCode][ 'data' ][ $dateKey ] ); 




                foreach( $seriesProvinces[$provinceCode][ 'data' ] as $index => $row ) {    // iterate through all days/measurements
                    $seriesProvinces[ $provinceCode ][ 'data' ][ $index ][ FileManager::HEADER_VALUE_RELIABILITY ]
                                        = (int) 100 * round( $row[ FileManager::HEADER_RWZI_FACTOR ] / $seriesProvinces[ $provinceCode ][ FileManager::HEADER_INHABITANTS ], 2);
                    if( $row[ FileManager::HEADER_RWZI_FACTOR ] == 0 ) {
                        echo "RWZI factor is nul. Kan eigenlijk niet " . __LINE__;
                        var_dump( $province );
                        var_dump( $index );
                        var_dump( $row );
                    }
                    $seriesProvinces[ $provinceCode ][ 'data' ][ $index ][ FileManager::HEADER_RNA ]
                                        = (int) round( $row[ FileManager::HEADER_RNA ] / $row[ FileManager::HEADER_RWZI_FACTOR ] );
                }

            }






            /** If only recent values are calculated, combine them with the older calculations */
            // First, get hold of the older data. We already checked it is available
            if( $processOnlyRecentDates ) {
                $seriesProvinceSortedByDatePersistent = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT );
                // var_dump( array_slice( $seriesProvinceSortedByDatePersistent, 0, 1 ) );
                // exit();
            }

            /** Create the final dataset for the chart for each province **/
            // We have all the data per date. Now create the final dataset:
            // Divide values by station-count for that day, 
            //remove values if station count is less than 60% of total stations
            // and some small tweakings
            $seriesProvinceSortedByDate = array();
            foreach( $seriesProvinces as $provinceCode => $seriesProv ) {
                $start = new DateTime( );
                $splitDate = ( $processOnlyRecentDates ) ? $this->splitDate : new DateTime( '1970-01-01' );
                $end = new DateTime( '2020-03-14' );        // start of the pandemic measurements
                $diff = $end->diff( $start );
                $interval = DateInterval::createFromDateString('-1 day');
                $period = new DatePeriod( $start, $interval, $diff->days );
        
                $i=0;
                foreach ( $period as $date ) {
                    $key = $date->format( "Y-m-d" );
                    if( $processOnlyRecentDates && $date < $splitDate ) { // lets get the previous calculated values
                        $seriesProvinceSortedByDate[ $provinceCode ][ $key ] = $seriesProvinceSortedByDatePersistent[ $provinceCode ][ $key ];
                    } else {    // Let's get the new calculated data from recent dates.
                        $seriesProvinceSortedByDate[ $provinceCode ][ $key ][ FileManager::HEADER_DATE ] = $key;
                        if( isset( $seriesProvinces[ $provinceCode ][ 'data' ][ $key ] ) ) {
                        $seriesProvinceSortedByDate[$provinceCode][ $key ][ FileManager::HEADER_RNA ] 
                                                =  $seriesProvinces[ $provinceCode ][ 'data' ][ $key ][ FileManager::HEADER_RNA ];
                            $seriesProvinceSortedByDate[$provinceCode][ $key ][ FileManager::HEADER_VALUE_RELIABILITY ] 
                                                = $seriesProvinces[ $provinceCode ][ 'data' ][ $key ][ FileManager::HEADER_VALUE_RELIABILITY ];
                        } else {
                            $seriesProvinceSortedByDate[$provinceCode][ $key ][ FileManager::HEADER_RNA ] =  NULL;
                            $seriesProvinceSortedByDate[$provinceCode][ $key ][ FileManager::HEADER_VALUE_RELIABILITY ] = 0;
                        }
                    }
                }
            }
            unset( $seriesProvinces );  // no longer needed

            //var_dump( $seriesProvinceSortedByDate[ "PV20" ] ); exit();
            // var_dump( array_slice( $seriesProvinceSortedByDate, 0, 1 ) );
            // exit();


            // create the values for the map.
            $minReliability = 60;  // 70%
            $mapValues = array();
            $i=0;
            foreach( $seriesProvinceSortedByDate as $provinceCode => $seriesProv ) {

                $mapValues[ $i ][ "pv_code" ] = $provinceCode;
                $mapValues[ $i ][ "pv_name" ] = $this->getProvinceByCode( $provinceCode );

                $seriesProv = array_values( $seriesProv );
                $daysToAverage = 7;
                $key = 0;
                // while( is_null( $seriesProv[ $key ][ FileManager::HEADER_RNA ] ) ) {
                while( $seriesProv[ $key ][ FileManager::HEADER_VALUE_RELIABILITY ] < $minReliability ) {
                    $key++;
                }
                $slice = array_slice( $seriesProv, $key, $daysToAverage );
                //echo '<br>&nbsp;<br>' . $this->getProvinceByCode( $provinceCode ) . '<br>';
                $sum = array( 'value' => 0, 'reliability' => 0 );
                foreach( $slice as $row ) {
                    // var_dump( $row );
                    $sum['value'] += $row[ FileManager::HEADER_RNA ];
                    $sum['reliability'] += $row[ FileManager::HEADER_VALUE_RELIABILITY ];
                }
                
                $mapValues[ $i ][ "value" ] = (int) round( $sum['value'] / $daysToAverage, 0 );
                $mapValues[ $i ][ "reliability" ] = (int) round( $sum['reliability'] / $daysToAverage, 0 );
                $i++;
            }
            FileManager::saveToCache( $mapValues, FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_MAP_VALUES_PER_PROVINCE, 'js', 'mapDataWasteWaterPerProvince' );
            unset( $mapValues );

            // create the values for the charts.
            // Sort the values by date ASC. This is the way the data goes to chart.js to create the charts
            foreach( $seriesProvinceSortedByDate as $key => $provinceSortedByDate ) {
                $date = array_column( $provinceSortedByDate, FileManager::HEADER_DATE );
                array_multisort( $date, SORT_ASC, $seriesProvinceSortedByDate[ $key ] );
            }
            FileManager::saveToCache( $seriesProvinceSortedByDate, FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE );
            FileManager::saveToCache( $seriesProvinceSortedByDate, FileManager::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT );


        }

        // select the province like $requestedProvinceCode = 'PV31';
        $requestedProvinceName= $this->getProvinceByCode( $requestedProvinceCode );

        if( false == $requestedProvinceName ) return;
        $requestedSeries = $seriesProvinceSortedByDate[ $requestedProvinceCode ];

        if ( $ajax ) {
            $labels = $this->prepareLabels( array_column( $requestedSeries, FileManager::HEADER_DATE ) );
            $rnaValues = array_column( $requestedSeries, FileManager::HEADER_RNA );
            $reliabilities = array_column( $requestedSeries, FileManager::HEADER_VALUE_RELIABILITY );
            $province = array(  "code" => $requestedProvinceCode,
                                "name" => $requestedProvinceName );

            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartWasteWaterLabels' , $labels );
            $ajaxResponse->addData( 'chartWasteWaterData', $rnaValues );
            $ajaxResponse->addData( 'chartWasteWaterReliabilities', $reliabilities );
            $ajaxResponse->addData( 'plant', $province );
            $ajaxResponse->addData( 'type', array( 'category' => 'wastewater', 'region' => 'province' ) );
            $ajaxResponse->send();
        } else { 
            return $requestedSeries;
        }

    }



    public function checkIfProvince( $plantName ) {
        $provinces = array(
            "PV20" => "Groningen",
            "PV21" => "Friesland",
            "PV22" => "Drenthe",
            "PV23" => "Overijssel",
            "PV24" => "Flevoland",
            "PV25" => "Gelderland",
            "PV26" => "Utrecht",
            "PV27" => "Noord-Holland",
            "PV28" => "Zuid-Holland",
            "PV29" => "Zeeland",
            "PV30" => "Noord-Brabant",
            "PV31" => "Limburg"
        );
        return in_array( $plantName, $provinces );
    }



    private function getProvinceByCode( $code ) {
        $provinces = array(
            "PV20" => "Groningen",
            "PV21" => "Friesland",
            "PV22" => "Drenthe",
            "PV23" => "Overijssel",
            "PV24" => "Flevoland",
            "PV25" => "Gelderland",
            "PV26" => "Utrecht",
            "PV27" => "Noord-Holland",
            "PV28" => "Zuid-Holland",
            "PV29" => "Zeeland",
            "PV30" => "Noord-Brabant",
            "PV31" => "Limburg"
        );
        return ( array_key_exists( $code, $provinces ) ) ? $provinces[ $code ] : false;
    }



    /**
     * NIEUWE OPZET!!!! In de nieuwe opzet is de loop over de dagen via de officiele objecten
     * De loop met timestamps gaf elders een issue omdat door de overschakeling naar wintertijd 
     * er een uur extra in de dag zat.
     * Het nieuwe idee werkt zeker, maar ik heb geen zin het nu af te maken.
     * 
     * 
     * 
     */
    public function getInterpolatedLocation( $dataset, $rwziCode ) {
        $interpolated = array();
        $start = new DateTime( key( $dataset ) );
        $end = new DateTime( '2020-03-14' );
        $diff = $end->diff( $start );
        $dateInterval = DateInterval::createFromDateString('-1 day');
        $period = new DatePeriod($start, $dateInterval, $diff->days);
        //var_dump( $dataset ); exit();

        $i=0;$loggedException = '';
        $interval = 0;
        foreach ( $period as $date ) {
            $key = $date->format( "Y-m-d" );
            if( isset( $dataset[ $key ] ) ) {
                $interpolated[ $i ][ FileManager::HEADER_DATE ] = $key;
                $interpolated[ $i ][ FileManager::HEADER_RNA ] = $dataset[ $key ];
                $y1 = $dataset[ $key ];
                $x1 = $key;
                $y2 = next( $dataset );
                $x2 = key( $dataset);
                $nextDate = new DateTime( $x2 );
                $interval = $nextDate->diff( $date )->days;
            } else {
                $interpolated[ $i ][ FileManager::HEADER_DATE ] = $key;
                if( empty( $x2 ) ) {
                    $interpolated[ $i ][ FileManager::HEADER_RNA ] = NULL;
                } else {
                    if ( $interval == 0 && $loggedException != $rwziCode ) {
                        new LogException( 'delen door nul, waarschijnlijk een leeg veld "NULL" in het bronbestand bij RWZI code:' . $rwziCode, __LINE__ );
                        $loggedException =  $rwziCode;
                        //var_dump( $dataset[ $key ] ); 
                        /*
                        var_dump( $period );
                        var_dump( $key );
                        var_dump( $dataset[ $key ]);
                        var_dump( $interval );
                        exit();
                        */
                    }
                    $interpolated[ $i ][ FileManager::HEADER_RNA ] = ( ( $nextDate->diff( $date )->days ) / $interval ) * ( $y1 - $y2 ) + $y2;
                }
            }
            $i++;
        }
        return $interpolated;
    }


    /**
     * Get the properties of each waste water plant, including number of inhabitants and coordinates
     */
    public function getPropertiesRwzi() {
        if( ! empty( $this->rwziProperties ) ) return true;
        $props = FileManager::readCsvWastewaterPlantsProperties();
        if( ! ArrayUtils::checkArray( $props ) ) {
            new LogException( 'Empty RWZI properties reading CSV file ', __LINE__ );
            return false;
        }
        $this->rwziProperties = array();
        foreach( $props as $row ) {
            $this->rwziProperties[ $row[ FileManager::HEADER_RWZI_CODE ] ] = $row;
        }
        return true;
    }





    /**
     * Match a value to a category and its color.
     * The color scale is determined by array 'colorStops' containing the color points on a scale of 0 to 1. 
     */
    private function valueToColorCategory( $value, $opacity = 0.9 ) {
        $value = ( $value / $this->maxValue );
        if ( $value >= 1 ) {
            $row = end( $this->colorCategory );
            return 'hsla(' . $row["h"] . ',' . $row["s"] . '%,' . $row["l"] . '%,' . $opacity . ')';
        }

        foreach( $this->colorCategory as $key => $stop ) {
            if( $value <  $stop["stop"] ) { 
                $key--;
                break;
            }
        }
        $hue = $this->colorCategory[ $key ]["h"];
        $sat = $this->colorCategory[ $key ]["s"];
        $light =  $this->colorCategory[ $key ]["l"];
        return 'hsla(' . $hue . ',' . $sat . '%,' . $light . '%,' . $opacity . ')';
    }



























    

/*** UNUSED AND DEPRECATED *** UNUSED AND DEPRECATED *** UNUSED AND DEPRECATED *** UNUSED AND DEPRECATED ***  */








    /**
     * Creates interpolated series from the raw datasets.
     * Filters out the very high values exceeding the max limit (currently at 8000 since Omikron)
     * 
     * Interpolated series are used to aggregate the data to higher levels, like per province or national.
     * 
     * Skip Woerden (14002)
     * Skip Katwoude (12016)
     */
    private function getSeriesInterpolated() {
        $rawSeriesWasteWater = $this->getRawSeriesWasteWater();
        $this->seriesWasteWaterInterpolated = array();

        // Skip unrealistic high values. It distorts the national chart too much.
        // Split array in sub series per station
        $currentRwziCode = -1;
        foreach( $rawSeriesWasteWater as $row ) {
            // skip Woerden, skip Katwoude
            if(  14002 == $row[ FileManager::HEADER_RWZI_CODE ] || 12016 == $row[ FileManager::HEADER_RWZI_CODE ] ) {
                continue;
            }
            // skip vules above max limit
            if( isset($row[ FileManager::HEADER_RNA ] ) && $row[ FileManager::HEADER_RNA ] > $this->limitMaxValue ) {
                continue;
            }

            // split the raw series into sub series of measurements per station.
            if ( $row[ FileManager::HEADER_RWZI_CODE ] != $currentRwziCode ) { // new rwzi station
                $currentRwziCode = $row[ FileManager::HEADER_RWZI_CODE ];
                $seriesWasteWaterInterpolated[ $currentRwziCode ] = array( 
                    FileManager::HEADER_RWZI_CODE => $row[ FileManager::HEADER_RWZI_CODE ],
                    FileManager::HEADER_RWZI_NAME => $row[ FileManager::HEADER_RWZI_NAME ],
                );
            }
            $seriesWasteWaterInterpolated[ $currentRwziCode ][ "dataset" ][ $row[ FileManager::HEADER_DATE ] ] = $row[ FileManager::HEADER_RNA ];
        }
        unset( $rawSeriesWasteWater );

        // Interpolate days with no measurements between measurements
        // The RwziCode is add to the function for error detection: If error, we know at which station.
        foreach( $seriesWasteWaterInterpolated as $key => $serie ) {
            $seriesWasteWaterInterpolated[ $key ]["interpolated"] = $this->getInterpolatedLocation( 
                    $serie["dataset"], 
                    $seriesWasteWaterInterpolated[ $key ][ FileManager::HEADER_RWZI_CODE ] 
            );
            unset( $seriesWasteWaterInterpolated[ $key ]["dataset"] );
        }

        return $seriesWasteWaterInterpolated;
    }


      


    /**
     * Get the date of the publication date of the sourcefile. 
     * The waste water file is updated once a week, so it is important to show from which date the data is.
     * *** THIS DATE IS UNUSED AT THE MOMENT ***
     */
    public function getDateSourceFile() {
        $timestamp = FileUtils::readCookie( FileManager::COOKIE_LAST_FETCH_WASTE_WATER );
        if ( $timestamp == false || $timestamp < 1000 ) {
            new LogException( 'Empty cookie last fetch waste water ' . FileManager::COOKIE_LAST_FETCH_WASTE_WATER, __LINE__ );
        }
        return date( 'j/n', $timestamp );
    }




    /**
     * TEST method for reading the values from the JSON file of the CoronaDashboard 
     * This method is NOT in use for any function within corona-lokaal.nl
     */
    public function readCoronaDashboardJsonFile() {
        $jsonArray = FileManager::readFromCache( 'data/source_files/coronadashboard/coronadashboard.NL.20220510.json' );
        // var_dump( $jsonArray["sewer"]["values"] );
        $coronadashboardSewerSeries = array();
        $rawSewerSeries = $jsonArray["sewer"]["values"];

        //$chck = array_column( $rawSewerSeries, "date_unix");
        //var_dump( $chck ); exit();

        array_multisort( array_column( $rawSewerSeries, "date_unix"), SORT_DESC, $rawSewerSeries );
        //var_dump( $rawSewerSeries ); exit();
        foreach( $rawSewerSeries as $row ) {
            $dateKey = date( "Y-m-d", $row["date_unix"] );
            $coronadashboardSewerSeries[ $dateKey ][ FileManager::HEADER_DATE ] = $dateKey;
            $coronadashboardSewerSeries[ $dateKey ][ FileManager::HEADER_RNA_FLOW ] = $row["average"];

            echo $dateKey . ';' .  $row["average"] . '<br>';

            if( '2022-04-15' === $dateKey ) break;
        }
       // var_dump( $coronadashboardSewerSeries ); 
        exit();




    }





    private function calculateSevenDaysAverage( $series ) {
        $daysToAverage = 7; 
        $average = array();
        $i = 0;
        foreach( $series as $key => $row ) {
            if( "2020-09-10" == $key ) break;   // first available entry
            if ( ! is_null( $row[ FileManager::HEADER_RNA ] ) ) {
                if( empty( $firstIndex ) ) $firstIndex = $i;
                $startIndex = $i - floor( $daysToAverage / 2 );
                $days = $daysToAverage;
                if( $startIndex - $firstIndex < 0 ) {
                    $startIndex = $i - floor( $daysToAverage / 4 );
                    $days = floor( $daysToAverage / 2 );
                }
                if( $startIndex - $firstIndex >= 0 ) {
                    $series[ $key ][ FileManager::HEADER_RNA ] = 
                        ( array_sum( array_column( array_slice( $series, $startIndex , $days ), FileManager::HEADER_RNA ) ) / $days );
                }
            }
            $i++;
        }
        return $series;
    }






    /**
     * Map a dataset with measurements to all days since march 14th 2020.
     * The most recent day, is the day of the most recent measurement.
     * Missing data points will be interpolated.
     */
    /* DEPRECATED
    public function getInterpolatedLocation( $dataset ) {
        $interpolated = array();
        $endDate = key( $dataset );
        $oneDay = 86400;
        $i=0;
        for ( $date=$endDate; $date > 1584144000; $date -= $oneDay ) {
            $key = date( "Y-m-d", $date );
            if( isset( $dataset[ $date ] ) ) {
                $interpolated[ $i ][ FileManager::HEADER_DATE ] = $key;
                $interpolated[ $i ][ FileManager::HEADER_RNA ] = $dataset[ $date ];
                $y1 = $dataset[ $date ];
                $x1 = $date;
                $y2 = next( $dataset );
                $x2 = key( $dataset);
            } else {
                $interpolated[ $i ][ FileManager::HEADER_DATE ] = $key;
                if( empty( $x2 ) ) {
                    $interpolated[ $i ][ FileManager::HEADER_RNA ] = NULL;
                } else {
                    $interpolated[ $i ][ FileManager::HEADER_RNA ] = ( ( $date - $x2 ) / ( $x1 - $x2 ) ) * ( $y1 - $y2 ) + $y2;
                }
            }
            $i++;
        }
        return $interpolated;
    } 

*/
    








    /**
     * Match a value to a (gradient) color.
     * The color scale is determined by array 'colorStops' containing the color points on a scale of 0 to 1. 
     */
    private function valueToColorGradient( $value, $opacity = 0.9 ) {
        $value = ( $value / $this->maxValue );
        if ( $value >= 1 ) {
            $row = end( $this->colorStops );
            return 'hsla(' . $row["h"] . ',' . $row["s"] . '%,' . $row["l"] . '%,' . $opacity . ')';
        }

        $end = count( $this->colorStops ) - 1;
        $begin = $end - 1;
        foreach( $this->colorStops as $key => $stop ) {
            if( $value <  $stop["stop"] ) { 
                $end = $key;
                $begin = $key - 1;
                break;
            }
        }
        $interval = $this->colorStops[ $end ]["stop"] - $this->colorStops[ $begin ]["stop"] ;
        $positionInInterval = ( $value - $this->colorStops[ $begin ]["stop"] ) / $interval;

        $hue = round( ( $positionInInterval * ( $this->colorStops[ $end ]["h"] - $this->colorStops[ $begin ]["h"] ) ) + $this->colorStops[ $begin ]["h"], 2 );
        $sat = round( ( $positionInInterval * ( $this->colorStops[ $end ]["s"] - $this->colorStops[ $begin ]["s"] ) ) + $this->colorStops[ $begin ]["s"], 2 );
        $light = round( ( $positionInInterval * ( $this->colorStops[ $end ]["l"] - $this->colorStops[ $begin ]["l"] ) ) + $this->colorStops[ $begin ]["l"], 2 );

        return 'hsla(' . $hue . ',' . $sat . '%,' . $light . '%,' . $opacity . ')';
    }







}
