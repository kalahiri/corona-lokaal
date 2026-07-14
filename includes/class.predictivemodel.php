<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Link de rioolwatermetingen aan de ziekenhuisbesmettingen?
 * 
 */
class PredictiveModel {
    const CASUS_SOURCE_DIR = 'data/source_files/casus/'; 
    const SOURCE_BASE_FILE_NAME = 'COVID-19_casus_landelijk.';
    const CACHE_BASE_FILE_NAME = 'cache.totals.' . self::SOURCE_BASE_FILE_NAME;
    const ONE_DAY = 86400; // 1 day = 60 * 60 * 24 = 86400;



    private $seriesCasus;
    private $ageDistribution = array();
    private $hospitalRisc = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // nothing to do.
    }


    /**
     * Get the age distribution of positive tested individuals for each day
     * from the casus file. Calculate a seven day average for each day ( using the six days before).
     */
    public function getAgeDistributionReported() {
        $this->getSeriesCasus();    // get the casus series

        // Iterate over all the individuals. Count the age group and sex of the individuals for each day 
        $currentDate = '';
        $i=-1;
        $hospitalized = array();
        foreach( $this->seriesCasus as $casus ) {
            // skip some unsusual categories in the casus file
            if( $casus[ FileManager::HEADER_AGEGROUP ] == "Unknown" 
                || $casus[ FileManager::HEADER_SEX ] == "Unknown"
                || $casus[ FileManager::HEADER_AGEGROUP ] == "<50" ) continue;
            if( $currentDate != $casus[ FileManager::HEADER_DATE ] ) {
                $i++;
                $currentDate = $casus[ FileManager::HEADER_DATE ];
                $dailyDistribution[$i][ FileManager::HEADER_DATE ] = $casus[ FileManager::HEADER_DATE ];
                $dailyDistribution[$i][ "count" ] = 0;
            }
            $dailyDistribution[$i][ "count" ]++;
            // count the age group
            $ageGroup = $casus[ FileManager::HEADER_AGEGROUP ];
            if ( ! isset( $dailyDistribution[$i][ $ageGroup ] ) ) { 
                $dailyDistribution[$i][ $ageGroup ] = 1;
            } else { 
                $dailyDistribution[$i][ $ageGroup ]++; 
            }

            // count the hospital admissions FOR ALL DAYS TOGETHER!
            // There are too few hospital admission to get good estimates for hospital risc. 
            if( ! isset( $hospitalized[ $ageGroup ] ) ) {
                $hospitalized[ $ageGroup ][ FileManager::HEADER_REPORTED ] = 1;
                $hospitalized[ $ageGroup ][ FileManager::HEADER_HOSPITALIZED ] = 0;
            } else {
                $hospitalized[ $ageGroup ][ FileManager::HEADER_REPORTED ]++;
            }
            if ( $casus[ FileManager::HEADER_HOSPITALIZED ] ) {
                $hospitalized[ $ageGroup ][ FileManager::HEADER_HOSPITALIZED ]++;
            }

            // count the sex
            $sex = $casus[ FileManager::HEADER_SEX ];
            if ( ! isset( $dailyDistribution[$i][ $sex ] ) ) { 
                $dailyDistribution[$i][ $sex ] = 1;
            } else { 
                $dailyDistribution[$i][ $sex ]++; 
            }
        }
        $this->progress( "De leeftijdsverdeling en sekse-verdeling per dag op basis van de casus file is uitgerekend." );

        $this->hospitalRisc = array();
        foreach( $hospitalized  as $key => $row ) {
            $this->hospitalRisc[ $key ] = $row[ FileManager::HEADER_HOSPITALIZED ] / $row[ FileManager::HEADER_REPORTED ];
        }
        ksort( $this->hospitalRisc );
        $this->progress( "Het risico om in het ziekenhuis te belanden is per leeftijdscategorie uitgerekend. Dat gaat niet per dag omdat er te weinig ziekenhuisopnames zijn. Het risico is over alle dagen in het gehele casusbestand uitgerekend." );


        // Now we have the age and sex distribution per day
        // create an array with seven day averages in percentages
        // use the six previous days to calculate seven day average

        $lastDate = strtotime( end( $dailyDistribution )[ FileManager::HEADER_DATE ] );
        for( $i = $lastDate; $i > 1585699200; $i-= self::ONE_DAY ) { // 1585699200 == 01-04-2020 // 1602288000 = 10 oktober?
            $lastWeekAgeDistribution = ArrayUtils::getSliceByDate( $dailyDistribution, 0, $i, 7 );
            $count = ArrayUtils::getCount( $lastWeekAgeDistribution );
            $this->ageDistribution[ $count[ FileManager::HEADER_DATE ] ] = ArrayUtils::getPercentages( $count, $count[ FileManager::HEADER_COUNT ] );
        }
        $this->progress( "We hebben nu een zevendaags gemiddelde van de leeftijds- en sekseverdeling per dag." );

        // we have the seven days average age and sex-distribution in percentages for each day in $this->ageDistribution;. 
        return true;

    } 


    public function estimateHospitalByWasteWater( $wasteWaterSeries ) {

        $wasteWaterByAge = array();
        $hospitalEstimate = array();
        // iterate over all the available days in the wastewater series 
        foreach( $wasteWaterSeries as $date => $wasteWaterDay ) {
        
            // continue if not all the data available for that day
            if ( ! isset( $this->ageDistribution[ $date ] ) 
                            || is_null( $wasteWaterDay[ FileManager::HEADER_RNA ] ) ) continue;
            
            // iterate over all agegroups for each wastewater day 
            foreach( $this->ageDistribution[ $date ] as $key => $val ) {
                if ( $key == FileManager::HEADER_DATE || $key == FileManager::HEADER_COUNT 
                        || $key == "Female" || $key == "Male" ) continue;
                $wasteWaterByAge[ $date ][ $key ] = $wasteWaterDay[ FileManager::HEADER_RNA ] * $val * $this->hospitalRisc[ $key ];
            }
           



        }
        $this->progress( "De leeftijdsverdeling per dag is vermenigvuldigd met de rioolwatermeting en het risico op een ziekenhuisopname." );



        foreach( $wasteWaterByAge as $date => $row ) {
            $hospitalEstimates[ $date ] = 0;
            foreach( $row as $val ) {
                $hospitalEstimates[ $date ] += $val;
            }
            //$hospitalEstimates[ $date ] = $hospitalEstimates[ $date ] * 100;
        }
        $this->progress( "De uitkomsten per leeftijdsgroep zijn per dag bij elkaar opgeteld. 
            Per dag hebben we nu één getal die een correlatie zou moeten hebben met het aantal ziekenhuisopnames." );

/*
            foreach( $wasteWaterByAge as $date => $row ) {
                foreach( $row as $val ) {
                    $hospitalEstimates[ $date ] *= $this->wasteWaterConstant;
                }
            }

*/

        return $hospitalEstimates;




    }














    public function getSeriesAgeGroups() {
        $seriesAgeGroups = array();
        $last = end( $this->seriesCasus );
        $endDay = strtotime( $last["date"] );
        for( $day = $endDay; $day > 1593741600; $day -= self::ONE_DAY ) {
            $date = date( "Y-m-d", $day );
            $slice = $this->getSliceByDate( $this->seriesCasus, 0, $day, 3 );  
            $seriesAgeGroups[ $date ] = $this->getTotalsByAgeGroup( $slice );
        }
        return $seriesAgeGroups;
    }


    private function getTotalsByAgeGroup( $series ) {
        $data = array();
        $count = count ( $series );
        foreach( $series as $row ) {
            $agegroup = $row[ 'agegroup' ];
            if ( isset( $data[ $agegroup ] ) ) {
                $data[ $agegroup ]++;
            } else {
                $data[ $agegroup ] = 1;
            }
        }
        $result = array();
        $i = 0;
        foreach( $data as $key => $val ) {
            $result[ $i ][ 'agegroup' ] = $key;
            $result[ $i ][ 'total' ] = $val;
            $result[ $i ][ 'percentage' ] = $val/$count;
            $i++;
        }
        return $result;
    }





    public function getHospitalSeries( $daysToAverage = 3 ) {

        $series = FileManager::readNICEHospitalAdmissionJson()[0];
        //var_dump( $series ); exit();
        $average = array();
        foreach( $series as $key => $row ) {
            //var_dump( $row ); exit();
            $val = 0;
            $div = 0;
            for( $i=0; $i < $daysToAverage; $i++ ) {
                if( ! isset( $series[ $key ]["value"] ) ) continue;
                $key2 = $key - $i;
                if( isset( $series[ $key2 ]["value"] ) ) { 
                   $val += $series[ $key2 ]["value"];
                   $div++;
                }
                $average[ $key ][ FileManager::HEADER_DATE ] = $row[ FileManager::HEADER_DATE ];
                $average[ $key ]["value"] = $val/$div;
            }
        }
        return $average;


}












    /**
     * get the casus series from the source file
     * $timestamp: { int } get the casus file of a specific date. If not given, get the most recent file.
     * $overwrite: { boolean } if a new series is requested, the existing seriesCasus can be 'overwritten'.
     */
    public function getSeriesCasus( $timestamp = 0, $overwrite = false ) {
        if ( ! empty( $this->seriesCasus ) && is_array( $this->seriesCasus ) ) return true;
        $sourcefile = self::CASUS_SOURCE_DIR . self::SOURCE_BASE_FILE_NAME;
        if ( $timestamp == 0 && FileManager::isFileAvailable( $sourcefile . 'csv' ) ) {
            $sourcefile .= 'csv';
        } else {
            if ( $timestamp == 0 ) $timestamp = time(); 
            $sourcefile .= date( "Ymd", $timestamp ) . '.csv';
            if ( ! FileManager::isFileAvailable( $sourcefile ) ) return false;
        }
        $series = $this->readSourceCasusFile( $sourcefile );
        if( ! ArrayUtils::checkArray( $series ) ) {
            $this->progress( "lege series van casus file.");
            return false;
        } 
        $this->seriesCasus = $series;
        // pak alleen vanaf 1 juli 2020 omdat vanaf die datum het testen pas voor iedereen werd opengesteld.
        //$this->seriesCasus = $this->getSliceByDate( $series, 1593568800 );  
        // return ( ! empty( $this->seriesCasus ) && is_array( $this->seriesCasus ) );
        return true;
    }


    /**
     * Read the RIVM csv source file and return data in an array
     * Not all data is read from file. Only 'date', 'reported', 'hospitalized', 'deceased'
     */
    private function readSourceCasusFile( $sourceFile ) {
        $this->progress( "reading casus file " . $sourceFile ); 
        $data = array();
        if ( ( $handle = fopen( $sourceFile, 'r' ) ) !== FALSE ) {
            $header = array( FileManager::HEADER_DATE, FileManager::HEADER_AGEGROUP, FileManager::HEADER_SEX,
                                                FileManager::HEADER_HOSPITALIZED );
            while ( ( $row = fgetcsv($handle, 1000, ';' ) ) !== FALSE ) {
                if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                    $fieldsCount = count( $row );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount )  {
                        $hospitalized = ( $row[6] == 'Yes' ) ? true : false;
                        $data[] = array_combine( $header, array( (string) $row[1], (string) $row[3], (string) $row[4], $hospitalized ) );
                    }
                }
            }
            fclose($handle);
        }
        $date = array_column( $data, 'date' );
        array_multisort( $date, SORT_ASC, $data );
        return $data;
    }




    /**
     * Get the age distribution of patients in the hospitals.
     * UNUSED AT THE MOMENT!
     * TODO: The IC patients have to be added: https://www.stichting-nice.nl/covid-19-op-de-ic.jsp
     * 
     * Pak alle drie de balkjes van de "Leeftijdsdistributie van patiënten met verdachte of bewezen 
     * COVID-19 status op de verpleegafdelingen" van https://www.stichting-nice.nl/covid-19-op-de-zkh.jsp
     * voor de verpleegafdelingen. Daarin ontbreken nog de IC opnames. Haal die uit het groene balkje
     * "Leeftijdsdistributie van IC-patiënten met verdachte of bewezen COVID-19 naar uitkomst" op 
     * https://www.stichting-nice.nl/covid-19-op-de-ic.jsp
     * 
     */
    /* THIS WORKS AS EXPECTED, BUT I'VE CHOSEN A DIFFERENT PATH, SO NOT NEEDED AT THE MOMENT.
    private function getNICEAgeDistribution() {
        $contents = file_get_contents( 'https://www.stichting-nice.nl//covid-19/public/zkh/age-distribution-status/' );
        try {
            if( ! $contents ) throw new Exception('NICE data unavailable');
        } catch(Exception $e) {
            trigger_error($e->getMessage(), E_USER_NOTICE);
            $debugUtils = new DebugUtils;
            $debugUtils->write( $e->getMessage() );
            exit();
        }
        $data = json_decode( $contents );
        $niceDistribution = array();
        foreach( $data as $subarray ) {
            foreach( $subarray as $row ) {
                $key = $row[0];
                $val = $row[1];
                if ( isset( $niceDistribution[ $key ] ) ) {
                    $niceDistribution[ $key ] += $val;
                } else {
                    $niceDistribution[ $key ] = $val;
                }
            }
        }

        $rawDistribution["date"] = date( "Y-m-d" );
        $rawDistribution["0-19"] = $niceDistribution["<20"];
        $rawDistribution["20-29"] = $niceDistribution["20 - 24"] + $niceDistribution["25 - 29"];
        $rawDistribution["30-39"] = $niceDistribution["30 - 34"] + $niceDistribution["35 - 39"];
        $rawDistribution["40-49"] = $niceDistribution["40 - 44"] + $niceDistribution["45 - 49"];
        $rawDistribution["50-59"] = $niceDistribution["50 - 54"] + $niceDistribution["55 - 59"];
        $rawDistribution["60-69"] = $niceDistribution["60 - 64"] + $niceDistribution["65 - 69"];
        $rawDistribution["70-79"] = $niceDistribution["70 - 74"] + $niceDistribution["75 - 79"];
        $rawDistribution["80-89"] = $niceDistribution["80 - 84"] + $niceDistribution["85 - 89"];
        $rawDistribution["90+"] = $niceDistribution[">90"];
        $this->ageDistribution = ArrayUtils::getPercentages( $rawDistribution );
        return $this->ageDistribution;


    }
    */


    private function progress( $message ) {
        echo $message . '<br>';
    }

}
