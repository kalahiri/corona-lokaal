<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class WastewaterModel {
    private $rawSeriesWasteWater;
    private $plantLocations;
    private $maxValue = 2200;
    private $colorStops = array(
            [ "stop" => 0, "h" => 0, "s" => 0, "l" => 100 ], // "#ffffff"
            [ "stop" => 0.00000001, "h" => 60, "s" => 100, "l" => 94 ],  // "#ffffe0"
            [ "stop" => 0.06, "h" => 51, "s" => 93, "l" => 89 ], // "#fdf5c8"
            [ "stop" => 0.12, "h" => 40, "s" => 99, "l" => 65 ], // "#fec44f"
            [ "stop" => 0.32, "h" => 26, "s" => 85, "l" => 50 ], // "#ec7014"
            [ "stop" => 0.8, "h" => 0, "s" => 100, "l" => 25 ], // "#800000"
            [ "stop" => 1.0, "h" => 0, "s" => 100, "l" => 25 ], // "#800000"
        );
    private $seriesSortedByLocation;
    private $totalsSortedByWeekNumber;

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    /**
     * Get the data to create the charts for a specific waste water plant
     */
    public function getChartData( $plantName, $ajax = true ) {
        $this->getSeriesSortedByLocation();
        if ( ! in_array( $plantName, array_column( $this->seriesSortedByLocation, FileManager::HEADER_RWZI_NAME ) ) ) {
            exit();
        } 

        $plantData = array_filter( $this->seriesSortedByLocation, function ( $row ) use ( $plantName ) {
            return $row[ FileManager::HEADER_RWZI_NAME ] == $plantName;
        });
        $firstRow = reset( $plantData );
        $plant = array( "code" => $firstRow[ FileManager::HEADER_RWZI_CODE ],
                    "name" => $firstRow[ FileManager::HEADER_RWZI_NAME ] );

        $labels = $this->prepareLabels( array_column( $plantData, FileManager::HEADER_DATE ) );
        $rnaValues = array_column( $plantData, FileManager::HEADER_RNA );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartWasteWaterLabels' , $labels );
            $ajaxResponse->addData( 'chartWasteWaterData', $rnaValues );
            $ajaxResponse->addData( 'plant', $plant );
            $ajaxResponse->send();
        }
    }
 

    /**
     * Get the labels (x-axis) and format the data to be used by chart.js
     */
    private function prepareLabels( $dataset ) {
        $labels = array_map( function ($row) {
            return date( "j/n", strtotime ( $row ) );
        }, $dataset );
        return $labels;
    }


    /**
     * Get the locations and the most recent measuremnt. 
     * Determine the color of the location to be used on the map.
     * Save it to a cache file. 
     */
    public function getPlantLocations() {
        if( FileManager::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS ) ) {
           return true;
        }
        $this->getRawSeriesWasteWater();
        $this->plantLocations = array();
        $i=0;
        $currentLocation = 0;
        foreach( $this->rawSeriesWasteWater as $row ) {
            if( $row[ FileManager::HEADER_REPRESENTATIVE_MEASUREMENT ] 
                            && $currentLocation != $row[ FileManager::HEADER_RWZI_CODE ] ) {
                $currentLocation =  $row[ FileManager::HEADER_RWZI_CODE ];
                $this->plantLocations[ $i ]["code"] = $row[ FileManager::HEADER_RWZI_CODE ];
                $this->plantLocations[ $i ]["name"] = $row[ FileManager::HEADER_RWZI_NAME ];
                $this->plantLocations[ $i ]["date"] = date( 'j/n', strtotime( $row[ FileManager::HEADER_DATE_MEASUREMENT ] ) );
                $this->plantLocations[ $i ]["rna"] = $row[ FileManager::HEADER_RNA ];
                $this->plantLocations[ $i ]["x"] = $row[ FileManager::HEADER_RWZI_X ];
                $this->plantLocations[ $i ]["y"] = - $row[ FileManager::HEADER_RWZI_Y ];
                $this->plantLocations[ $i ]["z"] = 1;
                $this->plantLocations[ $i ]["color"] = $this->valueToColor( $row[ FileManager::HEADER_RNA ] );
                $i++;
            }
        }
        $rna = array_column( $this->plantLocations, 'rna' );
        array_multisort( $rna, SORT_ASC, $this->plantLocations ); // sort the data to get the high value bubbles in the map on top if overlapping
        FileManager::saveToCache( 'plantLocations', $this->plantLocations, FileManager::CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS , $type = 'javascript' );
        return true;
    }


    /**
     * Get the raw data from the source file and sort it by location (ASC) and date (DESC)
     */
    private function getRawSeriesWasteWater() {
        if( ! empty( $this->rawSeriesWasteWater ) ) return;
        $this->rawSeriesWasteWater = FileManager::readCsvWasteWater();
        $locations  = array_column( $this->rawSeriesWasteWater, FileManager::HEADER_RWZI_CODE );
        $date  = array_column( $this->rawSeriesWasteWater, FileManager::HEADER_DATE_MEASUREMENT );
        array_multisort( $locations, SORT_ASC, $date, SORT_DESC, $this->rawSeriesWasteWater );
        return;
    }


    /**
     * Match a value to a (gradient) color.
     * The color scale is determined by array 'colorStops' containing the color points on a scale of 0 to 1. 
     */
    private function valueToColor( $value, $opacity = 0.9 ) {
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


    /**
     * Get the date of the publication date of the sourcefile. 
     * The waste water file is updated once a week, so it is important to show from which date the data is.
     */
    public function getDateSourceFile() {
        $timestamp = FileUtils::readCookie( FileManager::COOKIE_LAST_FETCH_WASTE_WATER );
        return date( 'j/n', $timestamp );
    }


    /**
     * Get all measurements sorted by location (ASC) and date (ASC) from cache file.
     * If cache file is not available, read source file and create a new cache file
     */
    public function getSeriesSortedByLocation( ) {
        if ( ! empty( $this->seriesSortedByLocation ) ) return;
        if( FileManager::isFileAvailable( FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION ) ) {
            $this->seriesSortedByLocation = FileManager::readFromCache( 'series', FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION ); 
            return;
        }
        $this->getRawSeriesWasteWater();
        $this->seriesSortedByLocation = array();
        $i=0;
        foreach( $this->rawSeriesWasteWater as $row ) {
            if( $row[ FileManager::HEADER_REPRESENTATIVE_MEASUREMENT ] ) {
                // check for double records!!
                if( isset(  $this->seriesSortedByLocation[$i-1][ FileManager::HEADER_DATE ] ) 
                    && $this->seriesSortedByLocation[$i-1][ FileManager::HEADER_DATE ] == $row[ FileManager::HEADER_DATE_MEASUREMENT ]
                    &&  $this->seriesSortedByLocation[$i-1][ FileManager::HEADER_RWZI_CODE ] == $row[ FileManager::HEADER_RWZI_CODE ]
                    && $this->seriesSortedByLocation[$i-1][ FileManager::HEADER_RNA ] == $row[ FileManager::HEADER_RNA ] 
                ) continue;

                $this->seriesSortedByLocation[$i][ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE_MEASUREMENT ];
                $this->seriesSortedByLocation[$i][ FileManager::HEADER_RWZI_CODE ] = $row[ FileManager::HEADER_RWZI_CODE ];
                $this->seriesSortedByLocation[$i][ FileManager::HEADER_RWZI_NAME ] = $row[ FileManager::HEADER_RWZI_NAME ];
                $this->seriesSortedByLocation[$i][ FileManager::HEADER_WEEK_NUMBER ] = date("o-W", strtotime( $row[ FileManager::HEADER_DATE_MEASUREMENT ] ) );
                $this->seriesSortedByLocation[$i][ FileManager::HEADER_RNA ] = $row[ FileManager::HEADER_RNA ];
                $i++;
            }
        }

        $date = array_column( $this->seriesSortedByLocation, FileManager::HEADER_DATE );
        $location = array_column( $this->seriesSortedByLocation, FileManager::HEADER_RWZI_CODE );
        array_multisort( $location, SORT_ASC, $date, SORT_ASC, $this->seriesSortedByLocation );

        FileManager::saveToCache( 'series', $this->seriesSortedByLocation, FileManager::CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION );
        return true;
    }
  

   /**
     * Get the totals sorted by week number.
     * Save it to a cache file. 
     *
     * DEPRECATED
     * 
     */
    /*
    public function getTotalsSortedByDate() {
        if( FileManager::isFileAvailable( FileManager::CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER ) ) {
            $this->totalsSortedByWeekNumber = FileManager::readFromCache( 'totals', FileManager::CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER );
        } else {
            $this->getSeriesSortedByLocation();
            $seriesSortedByWeekNumber = $this->seriesSortedByLocation;

            $week= array_column( $seriesSortedByWeekNumber, FileManager::HEADER_WEEK_NUMBER );
            array_multisort( $week, SORT_ASC, $seriesSortedByWeekNumber );

            // get the totals per week by adding all rows with same weeknumber using array_reduce();
            $this->totalsSortedByWeekNumber = array_reduce( $seriesSortedByWeekNumber, function( $accumulator, $item ) { 
                if ( ! isset( $accumulator ) || ! is_array( $accumulator ) ) $accumulator = array();
                if ( isset( $accumulator[ $item[ FileManager::HEADER_WEEK_NUMBER ] ] ) ) {
                    $accumulator[ $item[FileManager::HEADER_WEEK_NUMBER] ][FileManager::HEADER_RNA] += $item[FileManager::HEADER_RNA];
                    $accumulator[ $item[FileManager::HEADER_WEEK_NUMBER] ]["number_of_locations"] += 1;
                } else {
                    $accumulator[ $item[FileManager::HEADER_WEEK_NUMBER] ][FileManager::HEADER_RNA] = $item[FileManager::HEADER_RNA];
                    $accumulator[ $item[FileManager::HEADER_WEEK_NUMBER] ]["number_of_locations"] = 1;
                }
                return $accumulator;
            });

            FileManager::saveToCache( 'totals', $this->totalsSortedByWeekNumber, FileManager::CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER );
        }
        return true;
    }
    */


    /**
     * Get the data of all waste water plants to create the charts for all the waste water plants together.
     * First, create seperate datasets with all measuremnts for each waste water plant
     * Second, interpolate all these measuremnts to create a daily (interpolated) value per waste water plant.
     * Third, get for each day the average of all these plants for the whole country.
     * Fourth, save the result to a cache file.
     * Fifth, get the data ready for chart.js and return it via an AJAX response.
     */ 
    public function getChartDataTotals( $ajax = true ) {
        if( FileManager::isFileAvailable( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY ) ) {
            $totalsSortedByDay = FileManager::readFromCache( 'totals', FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY );
        } else {
            $this->getRawSeriesWasteWater();
            $seriesTotalsMapped = array();
            $seriesAll = array();
            $seriesAllValues = array();
            $seriesTotals = array();
            $currentRwziCode = 0;
            $i=-1;
            foreach( $this->rawSeriesWasteWater as $row ) {
                if ( $row[ FileManager::HEADER_RWZI_CODE ] != $currentRwziCode ) {
                    $currentRwziCode = $row[ FileManager::HEADER_RWZI_CODE ];
                    $i++;
                    $seriesAll[ $i ] = array( 
                        FileManager::HEADER_RWZI_CODE => $row[ FileManager::HEADER_RWZI_CODE ],
                        FileManager::HEADER_RWZI_NAME => $row[ FileManager::HEADER_RWZI_NAME ] 
                    );
                }
                $timestamp = strtotime( $row[ FileManager::HEADER_DATE_MEASUREMENT ] );
                $seriesAll[ $i ][ "dataset" ][ "$timestamp" ] = $row[ FileManager::HEADER_RNA ];
            }
            foreach( $seriesAll as $key => $serie ) {
                $seriesAllValues  = array_merge( $seriesAllValues, $this->getInterpolatedLocation( $serie["dataset"] ) );
            }

            $date = array_column( $seriesAllValues, FileManager::HEADER_DATE );
            array_multisort( $date, SORT_ASC, $seriesAllValues );

            $oneDay = 86400;
            for ( $date=strtotime( "today" ); $date > 1584144000; $date -= $oneDay ) {
                $key = date( "Y-m-d", $date );
                $seriesTotals[ $key ][ FileManager::HEADER_RNA ] = NULL;
                $seriesTotals[ $key ][ "count" ] = 0;
            }

            foreach( $seriesAllValues as $row ) {
                if( ! is_null( $row[ FileManager::HEADER_RNA ] ) ) {
                    $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_RNA ] += $row[ FileManager::HEADER_RNA ];
                    $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ "count" ] += 1;
                }
            }

            $totalsSortedByDay=array();
            $i=0;
            foreach( $seriesTotals as $key => $row ) {
                $totalsSortedByDay[$i][ FileManager::HEADER_DATE ] = $key;
                if( $row["count"] > 20 ) {
                    $totalsSortedByDay[$i][ FileManager::HEADER_RNA ] = $row[ FileManager::HEADER_RNA ] / $row["count"];
                } else {
                    $totalsSortedByDay[$i][ FileManager::HEADER_RNA ] = NULL;
                }
                $i++;
            }

            $date = array_column( $totalsSortedByDay, FileManager::HEADER_DATE );
            array_multisort( $date, SORT_ASC, $totalsSortedByDay );
            FileManager::saveToCache( 'totals', $totalsSortedByDay, FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY );
        }

        $labels = $this->prepareLabels( array_column( $totalsSortedByDay, FileManager::HEADER_DATE ) );
        $rnaValues = array_column( $totalsSortedByDay, FileManager::HEADER_RNA );
        $plant = array( "code" => 0, "name" => 'Nederland' );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartWasteWaterLabels' , $labels );
            $ajaxResponse->addData( 'chartWasteWaterData', $rnaValues );
            $ajaxResponse->addData( 'plant', $plant );
            $ajaxResponse->send();
        }
    }


    /**
     * Map a dataset with measurements to all days since march 14th 2020.
     * The most recent day, is the day of the most recent measurement.
     * Missing data points will be interpolated.
     */
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


}
