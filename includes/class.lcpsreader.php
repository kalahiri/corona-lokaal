<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

class LcpsReader {
    const URL = 'https://lcps.nu';

    const MONTHS = array( "januari" => "January", "februari" => "February", "maart" => "March",
        "april" => "April", "mei" => "May", "juni" => "June", "juli" => "July", "augustus" => "August",
        "september" => "September", "oktober" => "October", "november" => "November", "december" => "December"
    );

    private $webPage;



    public function __construct() {
        // nothing to do.
    }

    /**
     * Get the required numbers by scraping the news article on the homepage.
     * Unfortunately, we have to do this because the datafeed is not always updated 
     */
    public function getNumbers() {
        $todayLCPS= array(); 
        $errors = false;

        // read the source page
        $this->readWebpage();
        $domParser = new DomParser( $this->webPage );

        // get date
        $nodeHeader = $domParser->selectByClass( 'post-header' );
        $nodeDate = $domParser->selectByClass( 'meta', 'span', $nodeHeader );
        $rawText = $domParser->toString( $nodeDate );
        $dateElements = explode( ' ', $rawText );
        $timestamp = strtotime( $dateElements[0] . ' ' . self::MONTHS[ $dateElements[1] ] . ' ' . $dateElements[2]  );
        if ( $timestamp < 1577836800 ) {
            new LogException( 'Invalid timstamp: should be at least in the year 2020 ' );
            $errors = true;
        }
        $todayLCPS[ FileManager::HEADER_DATE ] = date( "d-m-Y", $timestamp );

        // get IC and hospital counts
        $title = $domParser->selectByClass( 'title', 'h3', $nodeHeader );
        $rawText = $domParser->toString( $title );
        $textElements = explode( ' ', $rawText );
        foreach( $textElements as $textElement ) {
            if ( filter_var($textElement, FILTER_VALIDATE_INT) ) {
                if( ! isset( $todayLCPS[ FileManager::HEADER_INTENSIVE_CARED ] ) ) {
                    $todayLCPS[ FileManager::HEADER_INTENSIVE_CARED ] = (int) $textElement;
                } else if( ! isset( $todayLCPS[ FileManager::HEADER_HOSPITALIZED ] ) ) {
                    $todayLCPS[ FileManager::HEADER_HOSPITALIZED ] = (int) $textElement;
                }
            }
        }
        if( ! isset( $todayLCPS[ FileManager::HEADER_INTENSIVE_CARED ] ) 
                                    &&  ! isset( $todayLCPS[ FileManager::HEADER_HOSPITALIZED] ) ) {
            new LogException( 'Missing number for ICU Count or Hospital Count.', __LINE__ );
            $errors = true;
        }

        // get IC and hospital intake numbers
        $body = $domParser->selectByClass( 'excerpt', 'div', $nodeHeader );
        $rawText = $domParser->toString( $body );
        $x1 = strpos( $rawText, 'nieuwe' ); 
        if( ! $x1 ) {
            new LogException( 'Unable to match words to find new hospital and ICU intake $x1', __LINE__ );
            $errors = true;
        } else {
            $rawText = substr( $rawText, $x1 );
            $x2 = strpos( $rawText, 'opnames' );
            if( ! $x2 ) {
                new LogException( 'Unable to match words to find new hospital and ICU intake $x2', __LINE__ );
                $errors = true;
            } else {
                $rawText = substr( $rawText, $x2 );
                $textElements = explode( ' ', $rawText );
                foreach( $textElements as $textElement ) {
                    if ( filter_var($textElement, FILTER_VALIDATE_INT) ) {
                        if( ! isset( $todayLCPS[ FileManager::HEADER_HOSPITAL_INTAKE ] ) ) {
                            $todayLCPS[ FileManager::HEADER_HOSPITAL_INTAKE ] = (int) $textElement;
                        } else if( ! isset( $todayLCPS[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] ) ) {
                            $todayLCPS[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] = (int) $textElement;
                            break;
                        }
                    }
                }
            }
        }

        // We got all the numbers, put them in a CSV string
        $csv = $todayLCPS[ FileManager::HEADER_DATE ] . ',' 
                . $todayLCPS[ FileManager::HEADER_INTENSIVE_CARED ]  . ',' 
                . '0,'
                . $todayLCPS[ FileManager::HEADER_HOSPITALIZED ]  . ',' 
                . $todayLCPS[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ]   . ',' 
                . $todayLCPS[ FileManager::HEADER_HOSPITAL_INTAKE ] . PHP_EOL;

        if ( $errors ) {
            $todayLCPS["notification"] = 'Er zijn fouten! Dit is de CSV tot nu toe: ' . $csv 
                                    . '<br>De LCPS source file wordt niet geüpdatet.';
        } else {
            // Check if an entry already exists for the current date. If so, we cannot update the file.
            $check = $this->checkIfTodayExists( $todayLCPS );
            if ( $check ) {
                $todayLCPS["notification"] = 'De LCPS source file heeft al waardes voor '. $todayLCPS[ FileManager::HEADER_DATE ] 
                                    . '. Dit is de CSV van vandaag: ' . $csv;
            } else {
                $this->backupLcpsSourceFile();
                if( $this->updateLcpsSourceFile( $csv ) ) {
                    $todayLCPS["notification"] = 'De LCPS source file is succesvol geüpdatet';
                }
            }
        }

        // return the numbers and notification
        return $todayLCPS;
    }


    /**
     * Get the complete LCPS web page
     */
    public function getWebText() {
        $this->readWebpage();
        return $this->webPage;
    }





   /**
     * Check if a date already exists in the source file. No need to update if it does.
     * @return: true if today already exists.
     */
    private function checkIfTodayExists( $today ) {
        $series = FileManager::readLCPSHospitalCountCsv();
        $dates = array_column( $series, FileManager::HEADER_DATE );
        $key = array_search( $today[ FileManager::HEADER_DATE ], $dates );
        $row = $series[ $key ];
        if ( is_array( $row ) ) {
            if( $today[ FileManager::HEADER_DATE ] === $row[ FileManager::HEADER_DATE ] 
                    && $today[ FileManager::HEADER_INTENSIVE_CARED ] == $row[ FileManager::HEADER_INTENSIVE_CARED ]
                    && $today[ FileManager::HEADER_HOSPITALIZED ] == $row[ FileManager::HEADER_HOSPITALIZED ]
                    && $today[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] ==  $row[ FileManager::HEADER_INTENSIVE_CARE_INTAKE ]
                    && $today[ FileManager::HEADER_HOSPITAL_INTAKE ] == $row[ FileManager::HEADER_HOSPITAL_INTAKE ]
            ) return true;
        }
        return false;
    }


    /**
     * Appends a line with csv data to the local LCPS source file 
     */
    private function updateLcpsSourceFile( $csv ) {
        $series = FileManager::readLCPSHospitalCountCsv();




        return file_put_contents( FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE, $csv, FILE_APPEND | LOCK_EX );
    }


    /**
     * Backup current LCPS source file
     */
    private function backupLcpsSourceFile() {
        if( file_exists( FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE ) ) {
            $date = date( 'Ymd', filemtime( FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE ) );
            $filename = substr( FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
            if( file_exists( $filename ) ) return;
            if( copy( FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE, $filename ) ) return true;
            new LogException( 'Failed to rename '. FileManager::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE 
                                                    . ' to ' . $filename );
            return;
        }
    }


 
    /**
     * Get contents of the remote website
     */
    private function readWebpage() {
        // Only for testing:  
        //    $this->webPage =  file_get_contents( 'tmpWebpage' ); return;
        if ( isset( $this->webPage ) && ! empty( $this->webPage ) ) return;
        $this->webPage = file_get_contents( self::URL );
        if ( $this->webPage !== false && ! empty( $this->webPage ) ) return;
        new LogException( 'Error reading LCPS webpage ' . self::URL, __LINE__ );
    }




}