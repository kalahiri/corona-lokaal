<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the data for the country
 */
class LcpsModel extends BaseModel {

    const INHABITANTS_NL = 17475415;    // CBS 2021: https://www.cbs.nl/nl-nl/visualisaties/dashboard-bevolking/leeftijd/jongeren
    const INHABITANTS_NL_12_AND_OLDER =  15335841; // 17475415 - 2139574

    private $totalsSortedByDate;
    private $totalsNICEHospitalAdmission;
    private $seriesLCPS;
    private $seriesNICE;

    private $reportDateTime;    // Date Time Object of report date, new
    private $reportDateTimeYesterday;    // Date Time Object of de day before report date, new

    
    /**
     * Constructor
     */
    public function __construct( $today ) {
        $this->reportDateTime = new DateTime();
        $this->reportDateTime ->setTimezone( new DateTimeZone( self::TIME_ZONE ) ); 
        $this->reportDateTime ->setTimestamp( $today[ "today" ] ); 
        if( empty( $this->reportDateTime ) ) {
            new LogException( 'Empty date of today', __LINE__  );
        }
        $this->reportDateTimeYesterday = clone $this->reportDateTime;
        $this->reportDateTimeYesterday->modify('-1 day');
    }




    /**
     * Get the seven days average of number of positive tests.
     * Used for daily update tweets
     * 
     * @var days {integer}: number of days in the past to calculate seven days average.
     */
    public function getSevenDayAverageHospital( $days = 0, $type = self::LCPS ) {
        $days = abs( intval( $days ) );
        $average = array();
        $sevenDays = 7;

        $date = clone $this->reportDateTime;
        $date->sub( new DateInterval( 'P'. $days . 'D' ) );
        $key1 = $date->format( "Y-m-d" );
        $date->sub( new DateInterval( 'P' . $sevenDays . 'D' ) );
        $key2 = $date->format( "Y-m-d" );

        // Calculate average hsopital admissions. Switch the source of data
        switch( $type ) {
 
            case self::LCPS:
                $this->getLCPSSeries();
                if( ! $this->checkArray( $this->seriesLCPS ) ) {
                    new LogException( 'Empty series LCPS', __LINE__  );
                    return false;
                }


                // interpoleer empty values
                $series = array_slice( $this->seriesLCPS, -50, null, true );   // no need to copy whole array;
                $startArrayDate = new DateTime( reset( $series )[FileManager::HEADER_DATE] );
                $notNull = true;
                $iDate = new DateTime( end( $series ) [ FileManager::HEADER_DATE ] ) ;
                $endWhileDate = new DateTime( "2022-03-01" );
                $endDate = new DateTime();  // define var to prevent warning in editor
                do {
                    if( is_null( $series[ $iDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITAL_INTAKE ] ) ) {
                        $notNull = false;
                    } else {
                        if( $notNull ) {
                                $endDate = new DateTime( $iDate->format( "Y-m-d" ) );
                        } else {
                            $notNull = true;
                            $startDate = new DateTime( $iDate->format( "Y-m-d" ) );
                            $startValue = $series[ $startDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITAL_INTAKE ];
                            $endValue = $series[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITAL_INTAKE ];
                            $interval = $startDate->diff( $endDate )->d;

                            for( $offset = 1; $offset < $interval + 1; $offset++ ){
                                $endDate->modify( '-1 day' );
                                $series[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITAL_INTAKE ]
                                    = ( ( ( $startValue - $endValue ) / $interval ) * $offset ) + $endValue;
                                if( 7 == (int) $endDate->format('N') ) { // simplified correction for the sunday hospital intake: only .75 of daily average 
                                    $series[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITAL_INTAKE ] *= 0.75;
                                }
                            }
                        }
                    }
                    $iDate->modify( '-1 day' );
                } while( $iDate > $startArrayDate && $iDate > $endWhileDate );

                // calculate average
                $offset  = array_search( $key1, array_column( $series, FileManager::HEADER_DATE ) ) - ( $sevenDays - 1 );
                $slice = array_slice( $series, $offset, $sevenDays );
                $column = array_column( $slice, FileManager::HEADER_HOSPITAL_INTAKE );
                $average[ FileManager::HEADER_HOSPITALIZED ] = (int) round( array_sum( $column ) / $sevenDays );
            break;
            case self::RIVM:
                $average[ FileManager::HEADER_HOSPITALIZED ] = round( ( $this->totalsSortedByDate[ $key1 ][ FileManager::HEADER_HOSPITALIZED ]
                - $this->totalsSortedByDate[ $key2 ][ FileManager::HEADER_HOSPITALIZED ] ) / $sevenDays );
        }

        return $average;

    }






    /**
     * Get the seven days average of number of positive tests.
     * @$days {integer}: number of days in the past to calculate seven days average.
     */
    public function getSevenDayAverage( $days = 0, $type = self::LCPS ) {
        $days = abs( intval( $days ) );
        $average = array();
        $sevenDays = 7;
        if( empty( $this->totalsSortedByDate ) ) {
            $this->getTotalsSortedByDate();
        }
        $date = clone $this->reportDateTime;
        $date->sub( new DateInterval( 'P'. $days . 'D' ) );
        $key1 = $date->format( "Y-m-d" );
        $date->sub( new DateInterval( 'P' . $sevenDays . 'D' ) );
        $key2 = $date->format( "Y-m-d" );

        // Because we use cumulative values here, this trick works to get the average:
        $average[ FileManager::HEADER_REPORTED ] = round( ( $this->totalsSortedByDate[ $key1 ][ FileManager::HEADER_REPORTED ]
                        - $this->totalsSortedByDate[ $key2 ][ FileManager::HEADER_REPORTED ] ) / $sevenDays );
        $average[ FileManager::HEADER_DECEASED ] = round( ( $this->totalsSortedByDate[ $key1 ][ FileManager::HEADER_DECEASED ]
                        - $this->totalsSortedByDate[ $key2 ][ FileManager::HEADER_DECEASED ] ) / $sevenDays );

        // Calculate average hsopital admissions. Switch the source of data
        switch( $type ) {
 
            case self::LCPS:
                $this->getLCPSSeries();
                if( ! ArrayUtils::checkArray( $this->seriesLCPS ) ) {
                    new LogException( 'Empty series LCPS', __LINE__  );
                    return false;
                }
                $offset  = array_search( $key1, array_column( $this->seriesLCPS, FileManager::HEADER_DATE ) ) - 6;
                $slice = array_slice( $this->seriesLCPS, $offset, $sevenDays );
                $column = array_column( $slice, FileManager::HEADER_HOSPITAL_INTAKE );
                $average[ FileManager::HEADER_HOSPITALIZED ] = (int) round( array_sum( $column ) / $sevenDays );
 
            break;
            case self::RIVM:
                $average[ FileManager::HEADER_HOSPITALIZED ] = round( ( $this->totalsSortedByDate[ $key1 ][ FileManager::HEADER_HOSPITALIZED ]
                - $this->totalsSortedByDate[ $key2 ][ FileManager::HEADER_HOSPITALIZED ] ) / $sevenDays );
        }

        return $average;

    }















    /**
     * Get the latest LCPS data on current hospital and IC occupation.
     * Cache not necessary as it is a small csv file.
     */
    public function getLCPSHospitalCountToday() {
        $this->getLCPSSeries();
        if( ! ArrayUtils::checkArray( $this->seriesLCPS ) ) {
            new LogException( 'Empty series LCPS', __LINE__  );
            return false;
        }

        
        // Er staat een foutief aantal in het bestand. Het LCPS lijkt die waarde niet te gaancorrigeren, dus doe ik het zo. 
        // 75 is een inschatting tussen 78 en 71
        $this->seriesLCPS["2023-08-07"]["hospitalized"] = 75;
        $hospitalCountToday = array();

        // get the totals for report date (last report date. Could be today or some days ago)
        $date = clone $this->reportDateTime;
        $key = $date->format( 'Y-m-d' );
        if( $key !== false && ! empty( $key ) 
                        && isset( $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] ) ) {
            $hospitalCountToday["today"] = $this->seriesLCPS[ $key ];
        }


        // get the totals for day before report date
        $key = $date->modify("-1 day")->format( 'Y-m-d' );
            if( isset( $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] ) ) {
                $hospitalCountToday["earlier"] = $this->seriesLCPS[ $key ];
            }

        // get the totals for a week before report date
        $key = $date->modify("-6 day")->format( 'Y-m-d' );
        if( isset( $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] ) ) {
            $hospitalCountToday["week_before"] = $this->seriesLCPS[ $key ];
        }

        // get most recent published totals in LCPS series. 
        $hospitalCountToday["end"] = end( $this->seriesLCPS );

        return $hospitalCountToday;

    }







    /**
     * Check if the (new) data for today is available
     */
    public function isLCPSUpdated() {
        $this->getLCPSSeries();
        //$series = $this->seriesLCPS;

        $now = time();
        $row = end( $this->seriesLCPS );
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
     * Read the LCPS series from the LCPS source file
     */
    public function getLCPSSeries() {
        if( ArrayUtils::checkArray( $this->seriesLCPS ) ) return;

        $seriesHistorical = FileManager::readRIVMHistoricalHospitalCountCsv();
        if( ! $this->checkArray( $seriesHistorical ) ) {
            new LogException( 'Empty RIVM Historical Hospital count reading CSV' );
        }
        /*
        $seriesHistorical = array();
        foreach( $seriesHistoricalRaw as $key => $row ) {
            $seriesHistorical[ $row[ FileManager::HEADER_DATE] ] = $row[ FileManager::HEADER_HOSPITAL_INTAKE ];
        }
        */
        

        $series = FileManager::readLCPSHospitalCountCsv();
        if( ! $this->checkArray( $series) ) {
            new LogException( 'Empty LCPS hospital count reading CSV' );
        }

        // per 12-12-2021 heeft het LCPS de administratie gewijzigd en ook historische data verwijderd.
        // Voor een consistent beeld, pakken we dus de eerder gepubliceerde gegevens van vóór 12-12-2021
        $seriesHistoricalLCPS = FileManager::readLCPSHistoricalHospitalCountCsv();
        if( ! $this->checkArray( $series) ) {
            new LogException( 'Empty LCPS *HISTORICAL* hospital count reading CSV' );
        }
        array_push( $series, ...$seriesHistoricalLCPS );

        // Sort the data and change date-format to default date format
        $this->seriesLCPS = array();
        foreach( $series as $key => $row ) {
            $newDateFormat = new DateTime( $row[ FileManager::HEADER_DATE ] );
            $newDateKey = $newDateFormat->format("Y-m-d" );

            $this->seriesLCPS[ $newDateKey ] = $row;
            $this->seriesLCPS[ $newDateKey ][FileManager::HEADER_DATE] = $newDateKey;
            if( ! empty( $seriesHistorical[ $newDateKey ][ FileManager::HEADER_HOSPITAL_INTAKE] ) ) {
                $this->seriesLCPS[ $newDateKey ][ FileManager::HEADER_HOSPITAL_INTAKE]
                        = $seriesHistorical[ $newDateKey ][ FileManager::HEADER_HOSPITAL_INTAKE];
            }
        }

        // add the historical data from NICE registration. Before 27/9/20 there was no LCPS
        $i = 0;
        foreach( $seriesHistorical as $key => $row ) {
            $this->seriesLCPS[ $row[ FileManager::HEADER_DATE] ][ FileManager::HEADER_HOSPITAL_INTAKE ] 
                        =  $row[ FileManager::HEADER_HOSPITAL_INTAKE ];
        }
        // var_dump( $this->seriesLCPS );exit();
        array_multisort( array_column( $this->seriesLCPS, FileManager::HEADER_DATE ), SORT_ASC, $this->seriesLCPS );


        // interpolate missing data
        $dataset = array_values( $this->seriesLCPS );

        array_multisort( array_column( $dataset, FileManager::HEADER_DATE ), SORT_DESC, $dataset );
        $endCheckDate = new DateTime( '2022-04-10' );

        $keyDate = new DateTime( reset( $dataset )[ FileManager::HEADER_DATE ] );
        $i = 0;
        do {


            $key = $keyDate->format( "Y-m-d" );
            if( $dataset[$i][ FileManager::HEADER_DATE ] ==  $key ) {
                $this->seriesLCPS[ $key ][ FileManager::HEADER_DATE ] = $key;
                $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITAL_INTAKE ] = $dataset[ $i ][ FileManager::HEADER_HOSPITAL_INTAKE ];
                //$this->seriesLCPS[ $key ][ FileManager::HEADER_INTERPOLATED ] = false;
                $i++;
                $keyDate->modify( '-1 day');
            } else {

                $startDate = new DateTime( $dataset[ $i - 1][ FileManager::HEADER_DATE ] );
                $endDate = new DateTime( $dataset[ $i ][ FileManager::HEADER_DATE ] );
                $interval = $startDate->diff( $endDate )->days;
                $offset =  $keyDate->diff( $endDate )->days;

                $this->seriesLCPS[ $key ][ FileManager::HEADER_DATE ] = $key;
                $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] 
                            = $this->calculateInterpolatedValue( $dataset[ $i - 1][ FileManager::HEADER_INTENSIVE_CARED ],
                                $dataset[ $i ][ FileManager::HEADER_INTENSIVE_CARED ], $interval, $offset );
                $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ] 
                            = $this->calculateInterpolatedValue( $dataset[ $i - 1][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ],
                                $dataset[ $i ][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ], $interval, $offset );
                $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITALIZED ] 
                            = $this->calculateInterpolatedValue( $dataset[ $i - 1][ FileManager::HEADER_HOSPITALIZED ],
                                $dataset[ $i ][ FileManager::HEADER_HOSPITALIZED ], $interval, $offset );
                $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] 
                            = $this->calculateInterpolatedValue( $dataset[ $i - 1][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ],
                                $dataset[ $i ][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ], $interval, $offset );
                $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITAL_INTAKE ] 
                            = $this->calculateInterpolatedValue( $dataset[ $i - 1][ FileManager::HEADER_HOSPITAL_INTAKE ],
                                $dataset[ $i ][ FileManager::HEADER_HOSPITAL_INTAKE ], $interval, $offset );

                $this->seriesLCPS[ $key ][ FileManager::HEADER_INTERPOLATED ] = true;
                $keyDate->modify( '-1 day');
            }



        } while( $keyDate != $endCheckDate );

        //var_dump( $this->seriesLCPS );
        //exit();
       
        // add some historical corrections
        $key = '2021-06-19';
            $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] = 208;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITALIZED ] = 315;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] = 0;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITAL_INTAKE ] = 17;
        $key = '2021-06-20';
            $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARED ] = 207;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITALIZED ] = 315;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] = 5;
            $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITAL_INTAKE ] = 9;


        array_multisort( array_column( $this->seriesLCPS, FileManager::HEADER_DATE ), SORT_ASC, $this->seriesLCPS );



        // var_dump( $this->seriesLCPS ); exit();
        return;
    }

    // tijdelijke functie om allee de besmettelijke patiënten in het ziekenhuis op te halen.
    // Straks zijn alle getallen alleen over besmettelijken en dan is deze functie niet meer nodig.
    public function getLCPSSeriesOnlyContagious() {
        $day = $this->reportDateTime->format( "Y-m-d" );
        $series = FileManager::readLCPSHospitalCountCsv( true, true );
        if( ! $this->checkArray( $series) ) {
            new LogException( 'Empty LCPS hospital count reading CSV' );
        }
        $seriesLCPS = array();
        foreach( $series as $key => $row ) {
            $newDateFormat = new DateTime( $row[ FileManager::HEADER_DATE ] );
            $newDateKey = $newDateFormat->format("Y-m-d" );

            $seriesLCPS[ $newDateKey ] = $row;
            $seriesLCPS[ $newDateKey ][FileManager::HEADER_DATE] = $newDateKey;
        }
        if( isset( $seriesLCPS[ $day ] ) ) {
            return $seriesLCPS[ $day ];
        }
        return false;

    }







    private function calculateInterpolatedValue( $startValue, $endValue, $interval, $offset ) {
        return NULL;
        // Dit werkt ptima, maar voor de grafiek zet ik het toch maar op NULL = 'geen data'.
        return (int) round( ( ( ( $startValue - $endValue ) / $interval ) * $offset ) + $endValue );
    }




















    /**
     * Get the totals by day from the RIVM source file.
     * Save it to a cache file. 
     * Used for the charts for the whole country
     */
    private function getTotalsSortedByDate( $type = FileManager::USE_LCPS ) {

                $this->getLCPSSeries();
                if( ! ArrayUtils::checkArray( $this->seriesLCPS ) ) {
                    new LogException( 'Empty series LCPS', __LINE__  );
                    return false;
                }
                foreach( $this->totalsSortedByDate as $key => $row ) {
                    if(  isset( $this->seriesLCPS[ $key ] ) ) {
                        $this->totalsSortedByDate[ $key ][FileManager::HEADER_HOSPITALIZED] 
                                = $this->seriesLCPS[ $key ][ FileManager::HEADER_HOSPITAL_INTAKE ];
                    } else {
                        //new LogException( 'Unextpected empty value LCPS', __LINE__  );
                        //return false;
                    }
                }
   

            // remove all entries before 2020-03-14
            $key = array_search( "2020-03-14", array_keys( $this->totalsSortedByDate ));
            $this->totalsSortedByDate = array_slice( $this->totalsSortedByDate, $key );

            if( ! ArrayUtils::checkArray( $this->totalsSortedByDate ) ) {
                new LogException( 'Empty totals sorted by date', __LINE__  );
            } else {
                FileManager::saveToCache( $this->totalsSortedByDate, FileManager::TOTALS_BY_DATE_CACHE_FILE );
            }

 
        return true;
    }



























    /**
     * Read the alternative TOTALS BY DAY file and get the SEVEN DAYS AVERAGE on reported cases
     * 
     * NOT USED AT THE MOMENT!!!!
     */
    public function getSevenDayAverageFromTotalsPerDay( $updateCache = false ) {
        $series = FileManager::readCsvTotalsPerDay();
        if( ! $this->checkArray( $series ) ) {
            new LogException( 'Empty series reading CSV file totals per day', __LINE__  );
            return false;
        }

        // to be sure it is sorted
        array_multisort( array_column( $series, FileManager::HEADER_DATE ), SORT_ASC, $series );

        $average = array();
        $date = clone $this->reportDateTime;
        $date->sub( new DateInterval( 'P6D' ) );
        $day6 = $date->format( "Y-m-d" );       // inclusief vandaag zijn dat zeven dagen.
        $date->sub( new DateInterval( 'P1D' ) );    // dezelfde dag als vandaag een week geleden 
        $day7 = $date->format( "Y-m-d" );
        $date->sub( new DateInterval( 'P6D' ) );    // en weer inclusief deze dag zijn dat zeven dagen een week geleden.
        $day13 = $date->format( "Y-m-d" );

        $date = array_column( $series, FileManager::HEADER_DATE );

        $key6 = array_search( $day6, $date );
        $key7 = array_search( $day7, $date );
        $key13 = array_search( $day13, $date );
        $count = 0;
        if( $key6 ) {
            $slice = array_slice( $series, $key6 );
            foreach( $slice as $row ) {
                $count+= $row[ FileManager::HEADER_REPORTED ]; 
            }
            $average["sevenDaysLastWeek"] = $count / 7;
        }
        $count = 0;
        if( $key7 && $key13 ) {
            $slice = array_slice( $series, $key13, $key7 - $key13 );
            foreach( $slice as $row ) {
                $count+= $row[ FileManager::HEADER_REPORTED ]; 
            }
            $average["sevenDaysWeekBefore"] = $count / 7;
        }


        //var_dump( $average );

        echo 'de gemiddeldes kloppen, maar nog verder uitwerken, want nu zijn ze niet nodig';



        

        exit();
    }












    /**
     * Count totals per day for all the available dates. 
     */
    public function getCountAllFromTotalsPerDay() {
        $series = FileManager::readCsvTotalsPerDay();
        if( ! $this->checkArray( $series ) ) {
            new LogException( 'Empty series reading CSV file totals per day', __LINE__  );
            return false;
        }

        // to be sure it is sorted
        array_multisort( array_column( $series, FileManager::HEADER_DATE ), SORT_ASC, $series );

        $currentDate = '';
        $totalsPerDay = array();
        foreach( $series as $row ) {
            if( $currentDate != $row[ FileManager::HEADER_DATE ] ) {
                // new date
                if( isset( $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ] ) 
                        && isset( $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] ) ) {
                            $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
                            $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED ];
                            $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ];
                } else {
                    $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ];
                    $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ];
                    $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED ];
                    $totalsPerDay[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ];
                }

            }
        }
        return array_values( $totalsPerDay) ;


    }


    /**
     * Get the NICE hospital admissions and total them per day. 
     */
    public function getRIVM_NICECountryTotals() {
        if( ArrayUtils::checkArray( $this->seriesNICE ) ) return;

        $series = FileManager::readRIVM_NICEHospitalAdmissionCsv( FileManager::HOSPITAL_ADMISSION_NOTIFICATION );
        array_multisort( array_column( $series, FileManager::HEADER_DATE ), SORT_ASC, $series );

        $seriesTotals = array();
        foreach( $series as $row ) {
            if( isset( $seriesTotals[ $row[ FileManager::HEADER_DATE ] ] ) 
                    && isset( $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] ) ) {
                        $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED ];
            } else {
                $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ];
                $seriesTotals[ $row[ FileManager::HEADER_DATE ] ][ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED ];
            }
        }
        unset( $series );

        // shift the array one day ahead, because NICE lags one day in their data file.
        $this->seriesNICE = array();
        foreach( $seriesTotals as $key => $row ) {
            $date = new DateTime( $key );
            $newKey = $date->modify( '+1 day' )->format( "Y-m-d" );
            $this->seriesNICE[ $newKey ] = $row;
            $this->seriesNICE[ $newKey ][ FileManager::HEADER_DATE ] = $newKey;
        }

        return;
    }









    /**
     * 
     */
    public function getVaccinated(){
        if( FileUtils::isFileAvailable( FileManager::CACHE_RIVM_VACCINATED_FILE ) ) {
            $vaccinated = FileManager::readFromCache( FileManager::CACHE_RIVM_VACCINATED_FILE ); 
            if( $this->checkArray( $vaccinated ) ) {
                return $vaccinated;
            }
        }

        // No cache file available. Create new one.
        $percentageToGetSecondDose = .6;    // percentage of given doses that are their second dose. 
        $dailyIncreaseJanssen = 500;    // number of daily given doses JANSSEN. 
        $latest = array();
        $seriesVaccinated = FileManager::readRIVMVaccinated();

        // copy series to a csv file for separate calculations in excel
        FileManager::saveToCache( $seriesVaccinated, FileManager::RIVM_VACCINATED_FOR_CALCULATIONS_FILE, 'csv' );
        

        // *** FIND THE REFERENCE DATA: last known date with values on fully vaccinated and number of janssen
        // find the date with reference data
        array_multisort( array_column( $seriesVaccinated, FileManager::HEADER_DATE ), SORT_DESC, $seriesVaccinated );
        foreach( $seriesVaccinated as $key => $row ) {
            if( $row[FileManager::HEADER_VACCINATED_TOTAL_DOSES] > 1000 ) {
                break;
            }
        }
        // find the key of the reference data in the series. Assume it has the largest value for fully vaccinated persons.
        $vaccinatedFully = array_column( $seriesVaccinated, FileManager::HEADER_VACCINATED_PERSONS_FULLY );
        $keyMaxVaccinatedFully =  array_keys( $vaccinatedFully, max( $vaccinatedFully ) )[0];
        unset( $vaccinatedFully );
        $valuesLastReference = $seriesVaccinated[ $keyMaxVaccinatedFully ];
        $estimateSingleDoseFullyVaxxed = $valuesLastReference[ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ];
        // days passed sinds reference date
        $referenceDate = new DateTime( $valuesLastReference[ FileManager::HEADER_DATE ] );
        $today = new DateTime();
        $daysPassed = $today->diff($referenceDate)->format("%a");


        // *** START CALCULATIONS
        /** berekeningen in tekst uitgelegd
            total_doses_today = getal uit het dashboard
            total_janssen_today = janssen_reference + ( daily_increase * number_of_days_passed )

            total_doses_no_janssen_today = total_doses_today - total_janssen_today
            total_doses_no_janssen_reference = total_doses_reference - total_jansen_reference

            total_two_doses_no_janssen = total_two_doses_reference + ( ( total_doses_no_janssen_today - total_doses_no_janssen_reference ) * percentage )
            total_at_least_one_dose_no_janssen = total_doses_no_janssen_today - total_two_doses_no_janssen;

            fully_vaccinated_today = total_two_doses_no_janssen + total_janssen_today + estimateSingleDoseFullyVaxxed;
            at_least_one_dose_today = total_at_least_one_dose_no_janssen + total_janssen_today;
        */

        // totaal prikken vandaag = gewoon van het dashboard
        $total_doses_today = $seriesVaccinated[$key][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ];
        // totaal prikken janssen vandaag = doses janssen referentie + ( dagelijkse toename * aantal dagen )
        $total_janssen_today = $valuesLastReference[ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN ] + ( $dailyIncreaseJanssen * $daysPassed );

        // totaal prikken zonder janssen vandaag = totaal prikken vandaag - totaal prikken janssen vandaag;
        $total_doses_no_janssen_today = $total_doses_today - $total_janssen_today;
        // totaal prikken zonder janssen referentie = totaal prikken referentie - totaal prikken janssen referentie;
        $total_doses_no_janssen_reference = $valuesLastReference[ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] 
                        - $valuesLastReference[ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN ];

        // totaal twee doses zonder janssen = referentie value + ( ( totaal zonder J nu  - totaal zonder J reference ) * percentage )
        $total_two_doses_no_janssen_today = $valuesLastReference[ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ]
                        + ( ( $total_doses_no_janssen_today - $total_doses_no_janssen_reference ) * $percentageToGetSecondDose );
        //  totaal tenminste één dosis zonder janssen = totaal doses zonder janssen nu - totaal TWEE doses zonder janssen
        $total_at_least_one_dose_no_janssen_today = $total_doses_no_janssen_today - $total_two_doses_no_janssen_today;
        $total_one_dose_no_janssen_today = $total_at_least_one_dose_no_janssen_today - $total_two_doses_no_janssen_today;

        // volledige gevaccineerd = totaal TWEE doses zonder janssen nu + totaal janssen nu
        $total_fully_vaccinated_today = $total_two_doses_no_janssen_today + $total_janssen_today + $estimateSingleDoseFullyVaxxed;
        // tenminste ÉÉN dosis = totaal tenminste één dosis zonder janssen nu + totaal janssen nu
        $total_at_least_one_dose_today = $total_at_least_one_dose_no_janssen_today + $total_janssen_today;


        // aantal prikken als percentage van de bevolking
        $total_doses_today_percentage = $total_doses_today * 100 / self::INHABITANTS_NL;
        $total_at_least_one_dose_today_percentage = $total_at_least_one_dose_today * 100 / self::INHABITANTS_NL;
        $total_fully_vaccinated_today_percentage = $total_fully_vaccinated_today * 100 / self::INHABITANTS_NL;
        $total_fully_vaccinated_today_percentage_12_and_older = $total_fully_vaccinated_today * 100 / self::INHABITANTS_NL_12_AND_OLDER;



        // *** CREATE SERIES FOR CACHE FILE
        $vaccinated["NL"][ FileManager::HEADER_DATE ] = date( "j/n", strtotime( $seriesVaccinated[$key][ FileManager::HEADER_DATE ] ) );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] = $this->getValuesWithUncertainty( $total_doses_today );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ] = $this->getValuesWithUncertainty( $total_one_dose_no_janssen_today );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_AT_LEAST_ONE_DOSE ] = $this->getValuesWithUncertainty( $total_at_least_one_dose_today );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERCENTAGE ] = (int) round( $total_doses_today_percentage );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_AT_LEAST_ONE_DOSE ]["percentage"] = number_format( $total_at_least_one_dose_today_percentage, 1, ',', '.' );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] = $this->getValuesWithUncertainty( $total_fully_vaccinated_today );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ]["percentage"] = number_format( $total_fully_vaccinated_today_percentage, 1, ',', '.' );
        $vaccinated["NL"][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ]["percentage_12+"] = number_format( $total_fully_vaccinated_today_percentage_12_and_older, 1, ',', '.' );

        // estimate doses increase since last update
        $vaccinated["NL"]["total_doses_difference"]["value"] = 
                        $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] 
                        - $seriesVaccinated[ $key+1 ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ];

        // EU average
        array_multisort( array_column( $seriesVaccinated, FileManager::HEADER_VACCINATED_PERCENTAGE_EU ), SORT_DESC, $seriesVaccinated );
        $vaccinated["EU"][ FileManager::HEADER_DATE ] = date( "j/n", strtotime( $seriesVaccinated[0][ FileManager::HEADER_DATE ] ) );
        $vaccinated["EU"][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] = number_format( $seriesVaccinated[0][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ], 1, ',', '.' );

        FileManager::saveToCache( $vaccinated, FileManager::CACHE_RIVM_VACCINATED_FILE );
        return $vaccinated;
    }


    private function getValuesWithUncertainty( $value ) : array {
        $percentageUp = .005;
        $percentageDown = 0.02;
        $data["value"] =  (int) $value;
        $data["valueLow"] = number_format( ( $value * (1 - $percentageDown ) ) / 1000000 , 1, ',', '.' );
        $data["valueHigh"] = number_format( ( $value * (1 + $percentageUp ) ) / 1000000 , 1, ',', '.' );
        return $data;
    }



    
    public function getNumberOfTestsGGD() {
        $series = FileManager::readNumberOfTestsGGD();
        array_multisort( array_column( $series, FileManager::HEADER_DATE ), SORT_DESC, $series );
        $sumPerDay = array();
        $date = '';
        $i=0;
        foreach( $series as $row ) {
            if( $date !== $row[ FileManager::HEADER_DATE ] ) {
                $date = $row[ FileManager::HEADER_DATE ];
                $sumPerDay[ $date ][FileManager::HEADER_DATE] = $row[ FileManager::HEADER_DATE ];
                $sumPerDay[ $date ][FileManager::HEADER_NUMBER_OF_TESTS ] = $row[ FileManager::HEADER_NUMBER_OF_TESTS ];
                $sumPerDay[ $date ][FileManager::HEADER_POSITIVE_TESTS ] = $row[ FileManager::HEADER_POSITIVE_TESTS ];
                $i++;
                if( $i > 15 ) break;
            } else {
                $sumPerDay[ $date ][FileManager::HEADER_NUMBER_OF_TESTS ] += $row[ FileManager::HEADER_NUMBER_OF_TESTS ];
                $sumPerDay[ $date ][FileManager::HEADER_POSITIVE_TESTS ] += $row[ FileManager::HEADER_POSITIVE_TESTS ];
            }

        }

        // Display laatste 14 dagen van GGD cijfers
/*
        foreach( $sumPerDay as $row ) {
            echo $row[FileManager::HEADER_DATE] . ';' . $row[FileManager::HEADER_POSITIVE_TESTS ] . '<br>';
        }
        exit();
*/

        // seven days number of tests/ number positive / percentage positive
        $slice = array_slice( $sumPerDay, 0, 7 );
        $sumPerWeek[ 0 ][FileManager::HEADER_DATE] = date( "j/n", strtotime( current( $slice )[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 0 ][FileManager::HEADER_DATE_PREVIOUS] = date( "j/n", strtotime( end( $slice )[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 0 ][FileManager::HEADER_NUMBER_OF_TESTS ] = array_sum( array_column( $slice, FileManager::HEADER_NUMBER_OF_TESTS ) );
        $sumPerWeek[ 0 ][FileManager::HEADER_POSITIVE_TESTS ] = array_sum( array_column( $slice, FileManager::HEADER_POSITIVE_TESTS ) );
        $sumPerWeek[ 0 ][FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = 
                    round( $sumPerWeek[ 0 ][FileManager::HEADER_POSITIVE_TESTS ] * 100 /  $sumPerWeek[ 0 ][FileManager::HEADER_NUMBER_OF_TESTS ] , 1 );

        // seven days of the week before
        $slice = array_slice( $sumPerDay, 7, 7 );
        $sumPerWeek[ 1 ][FileManager::HEADER_DATE] = date( "j/n", strtotime( current( $slice )[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 1 ][FileManager::HEADER_DATE_PREVIOUS] = date( "j/n", strtotime( end( $slice )[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 1 ][FileManager::HEADER_NUMBER_OF_TESTS ] = array_sum( array_column( $slice, FileManager::HEADER_NUMBER_OF_TESTS ) );
        $sumPerWeek[ 1 ][FileManager::HEADER_POSITIVE_TESTS ] = array_sum( array_column( $slice, FileManager::HEADER_POSITIVE_TESTS ) );
        $sumPerWeek[ 1 ][FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = 
                    round( $sumPerWeek[ 1 ][FileManager::HEADER_POSITIVE_TESTS ] * 100 /  $sumPerWeek[ 1 ][FileManager::HEADER_NUMBER_OF_TESTS ] , 1 );
        
        // only from most recent day
        $row = reset( $sumPerDay );
        $sumPerWeek[ 2 ][FileManager::HEADER_DATE] = date( "j/n", strtotime( $row[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 2 ][FileManager::HEADER_DATE_PREVIOUS] = date( "j/n", strtotime( $row[FileManager::HEADER_DATE] ) );
        $sumPerWeek[ 2 ][FileManager::HEADER_NUMBER_OF_TESTS ] = $row[FileManager::HEADER_NUMBER_OF_TESTS];
        $sumPerWeek[ 2 ][FileManager::HEADER_POSITIVE_TESTS ] = $row [FileManager::HEADER_POSITIVE_TESTS];
        $sumPerWeek[ 2 ][FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] = 
                    round( $sumPerWeek[ 2 ][FileManager::HEADER_POSITIVE_TESTS ] * 100 /  $sumPerWeek[ 2 ][FileManager::HEADER_NUMBER_OF_TESTS ] , 1 );

        return $sumPerWeek; 
    }


    public function setStoring( $val = false ) {
        return FileUtils::writeCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE, $val );
    }

    public function getStoring() {
        return FileUtils::readCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE );
    }

    public function toggleStoring() {
        $val = ! FileUtils::readCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE );
        return $this->setStoring( $val );
    }

    public function setStoringSolved( $val = false ) {
        return FileUtils::writeCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE_SOLVED, $val );
    }

    public function getStoringSolved() {
        return FileUtils::readCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE_SOLVED );
    }

    public function toggleStoringSolved() {
        $val = ! FileUtils::readCookie( FileManager::COOKIE_MALFUNCTION_RIVM_UPDATE_SOLVED );
        return $this->setStoringSolved( $val );
    }


}