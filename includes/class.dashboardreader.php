<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */


// JSON string coronadashboard: https://coronadashboard.rijksoverheid.nl/json/NL.json

// history corona dashboard data files at mzelst: https://github.com/mzelst/covid-19/tree/master/data-rivm/dashboard-data

class DashboardReader extends WebPageReader {
    const URL_VACCINATIES = 'https://coronadashboard.rijksoverheid.nl/landelijk/vaccinaties';
    //const URL_VACCINATIES = 'https://coronadashboard.rijksoverheid.nl';
    const FLAG_VACCINES = 'vaccines';
    const FLAG_INFECTION_PERCENTAGE = 'infection_percentage';

    private $rawDashboardData;
    private $vaccinesAdministeredDashboardData;
    private $vaccinesPlannedDashboardData;
    private $seriesVaccinated;


    public function __construct( $url = self::URL_VACCINATIES ) {
        $this->setUrlWebPage( $url );
        $this->readWebpage();
    }


    /**
     * Check for new data on the number of vaccinated people. If so, safe update to local source file
     */
    public function getRemoteDataVaccinated() {

        $this->parseDashboardData(); // get new values from dashboard
        // var_dump( $this->rawDashboardData ); exit();

        // Try searching for the 'vaccine' keyword directly in the data object 
        $this->vaccinesAdministeredDashboardData = $this->getSubarray( $this->rawDashboardData->props, 'vaccine_administered_total' );
        // var_dump( $this->vaccinesAdministeredDashboardData ); exit();

        $vaccinaties = $this->vaccinesAdministeredDashboardData->last_value;
        $newVaccinated =  array ( 
            FileManager::HEADER_DATE => date( "Y-m-d", $vaccinaties->date_of_insertion_unix ),
            FileManager::HEADER_VACCINATED_TOTAL_DOSES => $vaccinaties->estimated,
            FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME => false,
            FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED => $vaccinaties->reported,
            FileManager::HEADER_VACCINATED_PERSONS_FULLY => 0,
            FileManager::HEADER_VACCINATED_PERSONS_JANSSEN => 0,
            FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE => 0,
            FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED => 0,
            FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES => 0
        );   



        // get existing vaccination source file from disk
        $this->seriesVaccinated = FileManager::readRIVMVaccinated();
        //var_dump( $this->seriesVaccinated ); exit();
        if( false === $this->seriesVaccinated || empty( $this->seriesVaccinated ) || ! is_array( $this->seriesVaccinated ) ) {
            new LogException( 'error reading source file number of vaccinated people', __LINE__ );
            return false;
        }

        array_multisort( array_column( $this->seriesVaccinated, FileManager::HEADER_DATE ), SORT_ASC, $this->seriesVaccinated );
        // find the key for a possible entry for this date 
        $key = array_search( $newVaccinated[ FileManager::HEADER_DATE ], 
                                            array_column( $this->seriesVaccinated, FileManager::HEADER_DATE ) );
        if( false !== $key ) {  // We already got an entry.
            //Check if we have new values
            if(  $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] == $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES ]
                        && $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ] == $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ] ) {
                return false; // No new values
            }
        } else {    // new entry, so get a new key.    
            $key = count( $this->seriesVaccinated );
        }

        $this->seriesVaccinated[ $key ][ FileManager::HEADER_DATE ] = $newVaccinated[ FileManager::HEADER_DATE ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ] = $newVaccinated[ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ];
        if( ! isset( $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] ) ) {
            $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] = 0;
        }
        return FileManager::saveRIVMVaccinated( $this->seriesVaccinated );

    }


    /**
     * Estimate an update for days the RIVM does not give an update 
     */
    public function estimateDataVaccinated() {

        $realisticPercentageOfWeekPlanning = .9;
        
        //echo( "check" ); exit();
        //check day of the week. RIVM updates on tuesdays and thursdays. Check if no fresh data is available.
        $now = time();
        $today = datetimeUtils::getDayOfTheWeek( $now );
        if( (int) date( "H", $now ) < 15 ) return false; // only update after 15:00 hours
        // if( empty( $today["dayOfTheWeek"] ) || $today["dayOfTheWeek"] == 2 || $today["dayOfTheWeek"] == 4 ) return false;
        if( empty( $today["dayOfTheWeek"] ) || $today["dayOfTheWeek"] == 2  ) return false;
        if( empty( $this->vaccinesAdministeredDashboardData ) ) return false; // no data available
        // var_dump( $this->vaccinesAdministeredDashboardData->last_value ); exit();
        if( date( "Y-m-d", $this->vaccinesAdministeredDashboardData->last_value->date_of_insertion_unix ) == date( "Y-m-d", $now ) ) return false; // we got fresh dashboard data

        $calcValues = new stdClass();

        //echo( "check" );

        // get the values of the beginning of the week. Use the sub function to subtract a DateInterval.
        $currentDate = new DateTime();
        if( 1 == $today["dayOfTheWeek"] ) $today["dayOfTheWeek"] = 8;
        $interval = 'P' . ( $today["dayOfTheWeek"] - 1 ) . 'D';
        $startWeekDateTime = $currentDate->sub( new DateInterval( $interval ) );
        $startWeekDate = $startWeekDateTime->format('Y-m-d');
        $seriesVaccinatedReverse = array_reverse( $this->seriesVaccinated );
        foreach( $seriesVaccinatedReverse as $key=>$row ) {
            if( $row[FileManager::HEADER_DATE] == $startWeekDate ) {
                $calcValues->startWeek = $row[FileManager::HEADER_VACCINATED_TOTAL_DOSES];
                break;
            }
        }
        // get the last values / row
        $calcValues->last = end( $this->seriesVaccinated )[FileManager::HEADER_VACCINATED_TOTAL_DOSES] ;
        // get the current week planning
        $this->vaccinesPlannedDashboardData = $this->getSubarray( $this->rawDashboardData->props, 'vaccine_administered_planned' );
        $calcValues->weekPlanning = $realisticPercentageOfWeekPlanning * $this->vaccinesPlannedDashboardData->last_value->doses;
        // get days left
        $calcValues->daysLeft = 9 - $today["dayOfTheWeek"];

        $calcValues->newEstimate = (int) ( $calcValues->last + ( ( $calcValues->weekPlanning 
                    - ( $calcValues->last - $calcValues->startWeek ) ) / $calcValues->daysLeft ) );
        // var_dump( $calcValues ); exit();
        $newVaccinated =  array ( 
            FileManager::HEADER_DATE => date( "Y-m-d", $now ),
            FileManager::HEADER_VACCINATED_TOTAL_DOSES => $calcValues->newEstimate,
            FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME => true,
            FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED => 0,
            FileManager::HEADER_VACCINATED_PERSONS_FULLY => 0,
            FileManager::HEADER_VACCINATED_PERSONS_JANSSEN => 0,
            FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE => 0,
            FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED => 0,
            FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES => 0
        );


        $key = count( $this->seriesVaccinated );
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_DATE ] = $newVaccinated[ FileManager::HEADER_DATE ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ] = $newVaccinated[ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_FULLY ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ] = $newVaccinated[ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ];
        $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ] = $newVaccinated[ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ];
        if( ! isset( $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] ) ) {
            $this->seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] = 0;
        }
        return FileManager::saveRIVMVaccinated( $this->seriesVaccinated );

    }


    /**
     * Parse the Corona Dashboard and get all the data from it.
     * Save the raw data to disk if we have a new version.
     */
    private function parseDashboardData() {
         // get dashboard data from script
        $domParser = new DomParser( $this->webPage );
        $node = $domParser->selectByText( 'pageProps' );
        // var_dump( $node );
        if( empty( $node ) || false === $node ) {
            new LogException( "No date object: pageProps found", __LINE__ );
            return false;
        }
        //$data = json_decode( file_get_contents( 'data/source_files/coronadashboard/dashboard_data.20210203.json' ) );
        $this->rawDashboardData = json_decode( $node->nodeValue );
        if( ! isset( $this->rawDashboardData->props->pageProps->lastGenerated ) ) {
            new LogException( "Error reading Corona Dashboard data", __LINE__ );
            return false;
        }

        // Do we have new data? If yes, save to disk.
        $currentOnDisk = FileManager::readCoronaDashboardData();
        if( $currentOnDisk === false 
            || ! ( isset ( $currentOnDisk->props->pageProps->lastGenerated ) )
            || $this->rawDashboardData->props->pageProps->lastGenerated > $currentOnDisk->props->pageProps->lastGenerated ) { 
            return FileManager::saveCoronaDashboardData( $this->rawDashboardData );
        }
        return false;
    }


    /**
     * Get a specifiek sub-array from a array or object
     * 
     * @var { stdClass-object or array } $array
     * @var { string } $key is the name of the key to search for
     * @var
     * 
     * @return { object } the sub-array or sub-object or false if not found
     */
    private function getSubarray( $array, $key, $firstOnly = true ) {
        $string = json_encode( $array );
        $pos = strpos( $string, '"'. $key . '"');
        if( false === $pos ) {
            new LogException( 'key not found in array or object', __LINE__ );
            return false;
        }
        $string = substr( $string, $pos );

        $characters = str_split( $string );
        $count = 0;
        $first = true;
        foreach( $characters as $index => $char ) {
            // echo $index . ' - ' . $char . ' =  ' . $count . '<br>';
            if( $char === '}' ) {
                $count--;
            }
            if( $char === '{' ) { 
                $count++;
                $first = false;
            }
            if( $count === 0 && ! $first ) { 
                $pos = $index + 1;
                break;
            }
        }
        $string = '{' . substr( $string, 0, $pos ) . '}';

        $object = json_decode( $string );
        return ( is_null( $object ) ) ? false : $object->$key;
    }

    
    /**
     * Set the first hook on the webpage to search for to get to the values we want to get
     * 
     * @var hook { string } The keyword or key phrase to search for in the page to get started.
     */
    // UNUSED AT THE MOMENT
    public function setPrimaryHook( $hook ) {
        $this->primaryHook = $hook;
    }



}