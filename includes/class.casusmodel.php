<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class CasusModel extends BaseModel {
    const CASUS_SOURCE_DIR = 'data/source_files/casus/'; 
    const SOURCE_BASE_FILE_NAME = 'COVID-19_casus_landelijk.';
    const CACHE_BASE_FILE_NAME = 'cache.totals.' . self::SOURCE_BASE_FILE_NAME;
    
    /**
     * Constructor
     */
    public function __construct() {
        ini_set('memory_limit', '256M');

    }

    /**
     * Create all the cache files from RIVM source files if they do not exist yet.
     */
    private function createCacheFiles() {

        $date = strtotime('01-07-2020');    // first day we have the source file 
        $day = 24 * 60 * 60;
        $dateString = date('Ymd', $date );
        while ( file_exists( self::CASUS_SOURCE_DIR . self::SOURCE_BASE_FILE_NAME . $dateString . '.csv' ) ) {
            $cachefile = self::CASUS_SOURCE_DIR . self::CACHE_BASE_FILE_NAME . $dateString . '.php';
            if( ! FileManager::isFileAvailable( $cachefile ) ) {
                // read the RIVM csv source file
                $dataset = $this->readSourceCasusFile( self::CASUS_SOURCE_DIR . self::SOURCE_BASE_FILE_NAME . $dateString . '.csv' );

                // get the totals per day by adding all rows with same date using array_reduce();
                $totals = array_reduce( $dataset, function( $accumulator, $item ) { 
                    if ( ! isset( $accumulator ) || ! is_array( $accumulator ) ) $accumulator = array();
                    
                    // available keys: 'date', 'reported', 'hospitalized', 'deceased'.

                    // create key for different time formats
                    $dateObj = DateTime::createFromFormat('Y-m-d', $item[ 'date' ] );
                    if ( ! $dateObj ) {
                        $dateObj = DateTime::createFromFormat('d-m-Y', $item[ 'date' ] );
                        if ( ! $dateObj ) {
                            echo 'error reading date'; exit();
                        }
                    }
                    $date =  date( "Y-m-d", $dateObj->getTimestamp() );
                    /*
                    $date = DateTime::createFromFormat('Y-m-d', $item[ 'date' ] );
                    if ( $date->getTimestamp() < 1000 ) {   // no valid date, try other format
                        $date = DateTime::createFromFormat('d-m-y', $item[ 'date' ] );
                        if ( $date->getTimestamp() < 1000 ) {  
                            echo 'error reading date'; exit();
                        }
                    }
                    $date = date( "Y-m-d", strtotime( $item[ 'date' ] ) );
                    */

                    if ( isset( $accumulator[ $date ] ) ) {
                        $accumulator[ $date ] += 1;
                    } else {
                        $accumulator[ $date ] = 1;

                    }
                    return $accumulator;
                });
                unset( $dataset );

                FileManager::saveToCache( 'totals', $totals, $cachefile, $type = 'php' );
            }

            $date += $day;
            $dateString = date('Ymd', $date );
        }

    }


    private function calculateDifference( $fileNew, $fileOld, $daysInThePast = 14 ){
        $totalsNew =  FileManager::readFromCache( 'totals', $fileNew );
        $totalsOld =  FileManager::readFromCache( 'totals', $fileOld );
        $i = 0;
        $daysInThePast -= 2;
        $firstDate = '';
        //krsort ( $totalsNew );
       // $debug = ( strpos( $fileNew, '20200725' ) !== false ) ? true : false;
        foreach( array_reverse($totalsNew) as $key => $valNew ) {
            if ( empty( $firstDate ) ) {
                $firstDate = $key;
                $diff = 0;            
            }

            $valOld = ( isset( $totalsOld[ $key ] )) ? $totalsOld[ $key ] : 0;
            $diff += $valNew - $valOld;

            //if ($debug) {
            //    echo $key . ' * ' . $valNew . ' - ' . $valOld . '<br>';
            //}

            if ( $i > $daysInThePast ) break;   // We only collect data of last 14 days.
            $i++;            
        }
        return $diff;
    }


    /**
     * Create chartJS data from a casus series
     */
    public function getChartData( $series, $ajax = false ) {
        $labels = $this->prepareLabels( array_column( $series, FileManager::HEADER_DATE ) );
        //$reported = $this->prepareDataSerie( array_column( $series, FileManager::HEADER_REPORTED ));
        //$hospitalized = $this->prepareDataSerie( array_column( $series, FileManager::HEADER_HOSPITALIZED ));
        //$deceased = $this->prepareDataSerie( array_column( $series,  FileManager::HEADER_DECEASED ));

        if( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', 'Nederland' );
            $ajaxResponse->addData( 'country', true );
            $ajaxResponse->send();
        } else {
            return array(
                "labels" => $labels,
                FileManager::HEADER_REPORTED => array_column( $series, FileManager::HEADER_REPORTED ),
                FileManager::HEADER_HOSPITALIZED => array_column( $series, FileManager::HEADER_HOSPITALIZED ),
                FileManager::HEADER_DECEASED => array_column( $series,  FileManager::HEADER_DECEASED ),
            );

        }
    }








    public function getTotalsByDay( $series ) {
        $date = array_column( $series, FileManager::HEADER_DATE );
        array_multisort( $date, SORT_ASC, $series );


        $result = array();
        $currentDay = '';
        $i = -1;
        foreach( $series as $row ) {
            if( $currentDay != $row [ FileManager::HEADER_DATE ] ) {

                $currentDay = $row [ FileManager::HEADER_DATE ];
                $i++;
                $result[$i][ FileManager::HEADER_DATE ] = $row [ FileManager::HEADER_DATE ];
                $result[$i][ FileManager::HEADER_REPORTED ] = 0;
                $result[$i][ FileManager::HEADER_HOSPITALIZED ] = 0;
                $result[$i][ FileManager::HEADER_DECEASED ] = 0;

            }

            $result[$i][ FileManager::HEADER_REPORTED ]++;
            $result[$i][ FileManager::HEADER_HOSPITALIZED ] += $row [ FileManager::HEADER_HOSPITALIZED ];
            $result[$i][ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ];

        }
        return $result;
        
    }


    public function compareDayTotals( $day1, $day2 ) {
        $series1 = FileManager::readCsvCasus( $day1 );
        $series1 = $this->getTotalsByDay( $series1 );

        $series2 = FileManager::readCsvCasus( $day2 );
        $series2 = $this->getTotalsByDay( $series2 );

        $diff=array();
        $i=0;
        $dates = array_column( $series2, FileManager::HEADER_DATE );
        foreach( $series1 as $row ) {
            $date = $row [ FileManager::HEADER_DATE ];
            $diff[ $i ][ FileManager::HEADER_DATE] = $row[ FileManager::HEADER_DATE ] ;
            $diff[ $i ][ FileManager::HEADER_REPORTED ] = $row[ FileManager::HEADER_REPORTED ] ;
            $diff[ $i ][ FileManager::HEADER_HOSPITALIZED ] = $row[ FileManager::HEADER_HOSPITALIZED ];
            $diff[ $i ][ FileManager::HEADER_DECEASED ] = $row[ FileManager::HEADER_DECEASED ];
            
            $index = array_search( $date, $dates );
            if ( $index !== false && isset( $series2[ $index ][ FileManager::HEADER_REPORTED ] ) ) {
                $diff[ $i ][ FileManager::HEADER_REPORTED ] -= $series2[ $index ][ FileManager::HEADER_REPORTED ];
                $diff[ $i ][ FileManager::HEADER_HOSPITALIZED ] -=  $series2[ $index ][ FileManager::HEADER_HOSPITALIZED ];
                $diff[ $i ][ FileManager::HEADER_DECEASED ] -=  $series2[ $index ][ FileManager::HEADER_DECEASED ];
            }
            $i++;
        }

        /*
        foreach( $diff as $row) {
            echo $row[FileManager::HEADER_DATE] . ',' . $row[FileManager::HEADER_REPORTED] . ',' .
            $row[FileManager::HEADER_HOSPITALIZED ] . ',' . $row[FileManager::HEADER_DECEASED] . '<br>';
        }
        */

        $sum = $this->sumSeries( $diff );
        $this->printToScreen( $this->sumSeries( $diff ), 'Totaal' ); 
        $this->printToScreen( $this->sumSeries( array_slice( $diff, -21 ) ), 'Laatste 3 weken' );
        $this->printToScreen( $this->sumSeries( array_slice( $diff, -61 ) ), 'Laatste 2 maanden' );
        $this->printToScreen( $this->sumSeries( array_slice( $diff, -92 ) ), 'Laatste 3 maanden' );
        $this->printToScreen( $this->sumSeries( array_slice( $diff, -122 ) ), 'Laatste 4 maanden' );
    
    }


    private function sumSeries( $series ) {
        $sum = array( 
            FileManager::HEADER_REPORTED => 0,
            FileManager::HEADER_HOSPITALIZED => 0,
            FileManager::HEADER_DECEASED => 0,
        );
        foreach( $series as $row ) {
            $sum[ FileManager::HEADER_REPORTED ] += $row[ FileManager::HEADER_REPORTED ];
            $sum[ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED ];
            $sum[ FileManager::HEADER_DECEASED ] += $row[ FileManager::HEADER_DECEASED ];
        }
        return $sum;
    }



    /**
     * Read the RIVM csv source file and return data in an array
     * Not all data is read from file. Only 'date', 'reported', 'hospitalized', 'deceased'
     */
    private function readSourceCasusFile( $sourceFile ) {
        $data = array();
        if (($handle = fopen( $sourceFile, 'r')) !== FALSE) {
            $header = array( 'date', 'reported', 'hospitalized', 'deceased' );
            while (($row = fgetcsv($handle, 1000, ';' )) !== FALSE) {
                if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                    $fieldsCount = count( $row );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount )  {
                        $data[] = array_combine( $header, array( $row[1],$row[2],$row[6],$row[7] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }


    private function printToScreen( $sum, $title ) {
        echo '<p>' . $title . ':<br>- new reported: ' . $sum[ FileManager::HEADER_REPORTED ] . '<br>'
            . '- new hospitalized: ' . $sum[ FileManager::HEADER_HOSPITALIZED ] . '<br>'
            . '- new deceased: ' . $sum[ FileManager::HEADER_DECEASED ] . '</p>';

    }

    private function printCsv( $series ) {
        foreach( $series as $row ) {
            foreach( $row as $val ) {
                echo $val . ',';
            }
            echo '<br>';
        }


    } 


}
