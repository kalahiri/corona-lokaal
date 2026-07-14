<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the data for the country
 */
class InfectionRadarModel extends BaseModel {

    const INHABITANTS_NL = 17475415;    // CBS 2021: https://www.cbs.nl/nl-nl/visualisaties/dashboard-bevolking/leeftijd/jongeren
    const INHABITANTS_NL_12_AND_OLDER =  15335841; // 17475415 - 2139574

    private $seriesWeeklyPositiveTests;

    private $reportDateTime;    // Date Time Object of report date, new

    
    /**
     * Constructor
     */
    public function __construct( ) {

        /*
        $this->reportDateTime = new DateTime();
        $this->reportDateTime ->setTimezone( new DateTimeZone( self::TIME_ZONE ) ); 
        $this->reportDateTime ->setTimestamp( $today[ "today" ] );
        // var_dump( $today); 
        if( empty( $this->reportDateTime ) ) {
            new LogException( 'Empty date of today', __LINE__  );
        }
        */
    }









    /**
     * Check if the (new) data for today is available
     */
    public function isInfectionRadarPositiveTestsUpdated() {
        $this->getWeeklyPositiveTestsSeries();
        //$series = $this->seriesLCPS;

        $now = time();
        $row = end( $this->seriesWeeklyPositiveTests );
        //$timestampLastLCPS = strtotime( $row[ FileManager::HEADER_DATE ] );

        // Check if data for today is available
        if( date( "Y-m-d", $now ) == $row[ FileManager::HEADER_DATE ]  
                && isset( $row[ FileManager::HEADER_HOSPITAL_INTAKE ] )
                && isset( $row[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] ) ) {
            return true;
        }
        return false;

    }



    /**
     * Read the Infection Radar Positive Tests series from the source file
     */
    public function getWeeklyPositiveTestsSeries() {
        if( ArrayUtils::checkArray( $this->seriesWeeklyPositiveTests ) ) return;
        $series = FileManager::readInfectionRadarPositiveTestsFile();
        array_multisort( array_column( $series, FileManager::HEADER_DATE ), SORT_DESC, $series );

        // All measurements are weekly averages dated at the beginnen of the week.
        // For the chart, I would like to date the weekly average in the middle of the weekly period the average is taken from.
        // So I need to shift all dates 3.5 days ( = 4 days == modify( '+3 days' ) ) ahead.
        foreach( $series as $row ) {
            $date = new DateTime( $row[ FileManager::HEADER_DATE ]  );
            $date->modify('+3 days');
            $key = $date->format( 'Y-m-d' );
            //var_dump( $key );
            //exit();
            $this->seriesWeeklyPositiveTests[ $key ][ FileManager::HEADER_DATE ] = $key; 
            $this->seriesWeeklyPositiveTests[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = $row[ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ]; 
        }
        //var_dump( $this->seriesWeeklyPositiveTests );
        //exit();
    }



    /**
     * Get the data for the chart
     */
    public function getChartDataPositiveTests( $ajax = true, $createCacheOnly = false ) {
        if( FileUtils::isFileAvailable( FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS ) ) {
            $seriesPositiveTests = FileManager::readFromCache( FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS );
            if ( ! ArrayUtils::checkArray( $seriesPositiveTests) ) {
                new LogException( 'Empty Series Infection Radar Positive Tests reading cache file ' . FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS, __LINE__ );
            }
        } else {

            // get the raw data
            $this->getWeeklyPositiveTestsSeries();

            // First get the most recent values before modifying the raw data into a chart series 
            foreach( array_slice( $this->seriesWeeklyPositiveTests, 0, 2 ) as $row ) {
                $mostRecentValues[] = $row;
            }
            $mostRecentValues[ 0 ][ "groeipercentage" ] = ( $mostRecentValues[ 0 ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE] - $mostRecentValues[ 1 ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] ) 
                                            / $mostRecentValues[ 1 ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ];
            FileManager::saveToCache( $mostRecentValues, FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES );
            
            // Interpolate weekly measurements to get daily data
            $seriesPositiveTests = $this->getInterpolatedWeeklyPositiveTests();
            
            if ( ! ArrayUtils::checkArray( $seriesPositiveTests ) ) {
                new LogException( 'Empty $seriesPositiveTests' , __LINE__ );
            } else {
                // create the chart series
                //complete the array with  missing dates at the start of the pandemic
                $seriesPositiveTests = $this->getRestOfDates( $seriesPositiveTests );
                FileManager::saveToCache( $seriesPositiveTests, FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS );
                if( $createCacheOnly ) return true;

            }
        }   // end creating cache files

       
        $labels = $this->prepareLabels(  array_column( $seriesPositiveTests , FileManager::HEADER_DATE ) );
        $positiveTests = array_column( $seriesPositiveTests , FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE );
        $interpolated = array_column( $seriesPositiveTests , FileManager::HEADER_INTERPOLATED );

        if( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsLabels' , $labels );
            //$ajaxResponse->addData( 'chartLabelsFullDate' , array_column( $seriesPositiveTests , FileManager::HEADER_DATE ) );
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsData', $positiveTests );
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsDataFlags', $interpolated );
            $ajaxResponse->addData( 'city', 'Nederland' );
            $ajaxResponse->addData( 'type', array( 'category' => 'infectionRadarPositiveTests', 'region' => 'country' ) );
            $ajaxResponse->send();
        } else {
            return array( $labels, $positiveTests, $interpolated  );
        }

        return;

    }



    public function hideChartDataPositiveTests( $ajax = true ) {
        if( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsLabels' , '' );
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsData', '' );
            $ajaxResponse->addData( 'chartInfectionRadarPostiveTestsDataFlags', '' );
            $ajaxResponse->addData( 'city', 'province' );
            $ajaxResponse->addData( 'type', array( 'category' => 'infectionRadarPositiveTests', 'region' => 'province' ) );
            $ajaxResponse->send();
        } else {
            return array( '' );
        }

        return;
    }




    /**
     * Get the lastest relevant value of the national infection radar positive tests values.
     * Take into account the reliability of the measurements. Only 
     * values larger than $this->limitMaxValue are allowed.
     */
    public function getLatestValue() {
        if( FileUtils::isFileAvailable( FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES ) ) {
            $totalsMostRecentValue = FileManager::readFromCache( FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES );
            if ( ! ArrayUtils::checkArray( $totalsMostRecentValue ) ) {
                new LogException( 'Empty totals Sorted By Day reading cache file ' . FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES , __LINE__ );
            }
            return $totalsMostRecentValue;
        } else {
            return false;
        }
    }








    public function getRestOfDates( $dataset ) {
        // get the last date in the series. Since the series is sorted DESC, we get the first available date.
        // Vul dat data van het begin van de pandemie tot aan de eerste dag met daadwerkelijke meetgegevens vanInfectieRadar
        $end = new DateTime( end( $dataset )[ FileManager::HEADER_DATE ] );
        $end->modify('-1 day');
        $pandemicStart = new DateTime( "2020-03-14" ); // get the first date of the pandemic data
        $diff = $end->diff( $pandemicStart );
        $dateInterval = DateInterval::createFromDateString('-1 day');
        $period = new DatePeriod($end, $dateInterval, $diff->days);
        foreach ( $period as $date ) {
            $currentDate = $date->format( "Y-m-d" );
                // create new element
                $row = array( 
                    FileManager::HEADER_DATE => $currentDate,
                    FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE => null,
                    FileManager::HEADER_INTERPOLATED  => false
                );
                array_push( $dataset, $row );

        }
        array_multisort( array_column( $dataset, FileManager::HEADER_DATE ), SORT_ASC, $dataset );



        // Vul dat data van de meest recente dag met daadwerkelijke meetgegevens van InfectieRadar tot aan de dag van vandaag
        $start = new DateTime( );
        $end = new DateTime( end( $dataset )[ FileManager::HEADER_DATE ] );
        $end->modify('+1 day');  // De laatste dag van end hebben we in de dataset, dus die hoeven we niet toe te voegen. Schuif dus een dag verder.
        $diff = $end->diff( $start );
        $dateInterval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod( $end, $dateInterval, $diff->days);

        foreach ( $period as $date ) {
            $currentDate = $date->format( "Y-m-d" );
                // create new element
                $row = array( 
                    FileManager::HEADER_DATE => $currentDate,
                    FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE => null,
                    FileManager::HEADER_INTERPOLATED  => false
                );
                array_push( $dataset, $row );
        }

        return $dataset;
    }





    /**
     * Interpolate Infection Radar Weekly Positive Tests to get daily values
     */
    public function getInterpolatedWeeklyPositiveTests() {
            $dataset = $this->seriesWeeklyPositiveTests;
            $interpolated = array();

            $start = new DateTime( current( $dataset )[ FileManager::HEADER_DATE ]  ); // get the first date in the series. Since the series is sorted DESC, we get the last available date.
            $end = new DateTime( end( $dataset )[ FileManager::HEADER_DATE ] );
            $diff = $end->diff( $start );
            $dateInterval = DateInterval::createFromDateString('-1 day');
            $period = new DatePeriod($start, $dateInterval, $diff->days);
    
            $i=0;
            $interval = 0;
            foreach ( $period as $date ) {
                $key = $date->format( "Y-m-d" );
                if( isset( $dataset[ $key ] ) ) {
                    $interpolated[ $i ][ FileManager::HEADER_DATE ] = $dataset[ $key ][ FileManager::HEADER_DATE ];
                    $interpolated[ $i ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = $dataset[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ];
                    $interpolated[ $i ][ FileManager::HEADER_INTERPOLATED ] = false;
                    $y1 = $dataset[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ];
                    $x1 = $dataset[ $key ][ FileManager::HEADER_DATE ];

                    // find next abailable date 
                    $tryDate = new DateTime( $key );
                    for( $j=0; $j<40; $j++ ) {
                        $tryDate->modify(('-1 day') );
                        $tryKey = $tryDate->format( "Y-m-d" );
                        if( isset( $dataset[ $tryKey ] ) ) {
                            $y2 = $dataset[ $tryKey ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ];
                            $x2 = $dataset[ $tryKey ][ FileManager::HEADER_DATE ];
                            break;
                        }    
                    }

                    $nextDate = new DateTime( $x2 );
                    $interval = $nextDate->diff( $date )->days;
                } else {
                    $interpolated[ $i ][ FileManager::HEADER_DATE ] = $key;
                    $interpolated[ $i ][ FileManager::HEADER_INTERPOLATED ] = true;
                    if( empty( $x2 ) ) {
                        $interpolated[ $i ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = NULL;
                    } else {
                        if ( $interval == 0 ) {
                            new LogException( 'delen door nul, waarschijnlijk een leeg veld "NULL" in het bronbestand infecti radar positieve tests', __LINE__ );
                        }
                        $interpolated[ $i ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] 
                                    = round( ( ( ( $nextDate->diff( $date )->days ) / $interval ) * ( $y1 - $y2 ) + $y2 ), 3);
                    }
                }
                $i++;
            }

            return $interpolated;
        }
    









}