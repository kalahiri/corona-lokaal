<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

class ReportDate {


    public function __construct( ) {

    }


    /**
     * Determine the correct last report date.
     * Since not all data files are updated on the same day, it is unclear what the last report date should be.
     * By scanning the cache folder, we can see what the last update has been. 
     * 
     * !!! NOTICE: If the cache is renewed without new data, the last report date is not reliable!!!
     * 
     */
    public function getReportDate() {
        $cacheFiles = FileManager::getCacheFileDates();
        $reportDate = array( 'timestamp' => 0, 'today' => 0, 'datetime' => '1970-1-1' );
        foreach( $cacheFiles as $cacheFile ) {
            if( FileManager::CACHE_FILE_TOTALS_FROM_FILE_TOTALS_PER_DAY == $cacheFile["filename"] 
                                || FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY == $cacheFile["filename"] ) {
                if( $cacheFile[ "timestamp" ] > $reportDate[ "timestamp" ] ) {
                    $reportDate[ "timestamp" ] = $cacheFile[ "timestamp" ] ;
                    $reportDate[ "today" ] = $cacheFile[ "timestamp" ] ;
                    $reportDate[ "datetime" ] = $cacheFile["datetime"] ;
                }
            }
        }
        // $reportDate[ "timestamp" ] =	1683504000;
        // $reportDate[ "today" ]  = 	1683504000;
        // $reportDate[ "datetime" ] = "2023-05-09";
        return( $reportDate );

    }





}