<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

class OWIDReader extends WebPageReader {
    const URL_VACCINATIES = 'https://raw.githubusercontent.com/owid/covid-19-data/master/public/data/latest/owid-covid-latest.csv';


    public function __construct( $url = self::URL_VACCINATIES ) {
        $this->setUrlWebPage( $url );
        $this->readWebpage(); 
    }


    /**
     * Get the remote file containing vaccine data and save the file to disk 
     */
    public function getRemoteDataVaccinated() {
        if( strlen( $this->webPage ) > 50000 ) { // check size if we have an acceptable update
            return FileManager::saveCsvOWIDVaccinated( $this->webPage );
        }
        return false;
    }


    /**
     * 
     */
    public function calculateVaccinatedEUAverage() {
        $euCountryCodes = array("AUT","BEL","BGR","CYP","CZE","DEU","DNK","ESP","EST","FIN","FRA","GRC","HRV","HUN","IRL","ITA","LTU","LUX","LVA","MLT","NLD","POL","PRT","ROU","SVK","SVN","SWE");
        $series = FileManager::readCsvOWIDVaccinated();
        foreach( $series as $key => $row ) {
            if( ! in_array( $row[ FileManager::HEADER_COUNTRY_CODE ], $euCountryCodes ) ) {
                unset( $series[ $key ] );
            }
        }
        if( count( $series ) !== 27 ) {
            new LogException( 'Number of EU countries incorrect', __LINE__ );
            return false;
        }
        $euPercentage = round( 100 * array_sum( array_column( $series, FileManager::HEADER_VACCINATED_TOTAL_DOSES ) ) /
                                        array_sum( array_column( $series, FileManager::HEADER_INHABITANTS ) ), 2 ); 
        $date = max( array_column( $series, FileManager::HEADER_DATE ) );
        unset( $series );

        // update the local source file
        $latest = array();
        $seriesVaccinated = FileManager::readRIVMVaccinated();
        $key = array_search( $date, array_column( $seriesVaccinated, FileManager::HEADER_DATE ) );
        if( false !== $key ) {  // We already got an entry
            //Check if we have new values
            if( $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] == $euPercentage ) {
                return false; // No new values
            }
            // update series with new value
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] = $euPercentage;
        } else {
            // create new entry in series
            $key = count( $seriesVaccinated );
            $seriesVaccinated[ $key ][ FileManager::HEADER_DATE ] = $date;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME ] = false;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_TOTAL_DOSES_REPORTED ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_FULLY ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_JANSSEN ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_SINGLE_DOSE ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERSONS_TWO_DOSES ] = 0;
            $seriesVaccinated[ $key ][ FileManager::HEADER_VACCINATED_PERCENTAGE_EU ] = $euPercentage;
        }
        return FileManager::saveRIVMVaccinated( $seriesVaccinated ); 

    }



}