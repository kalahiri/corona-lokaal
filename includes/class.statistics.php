<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class Statistics {

    const RAW_FILE_VISITORS = "data/statistics/raw.visitors.";
    const STATS_FILE_VISITORS = "data/statistics/stats.visitors.";
    const DIR_STATISTICS = "data/statistics/";

    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }

    
    /**
     * Add a visitor to the raw log file
     */
    public static function addVisitor() {
        $now = time();
        $today = date( 'Ymd', time() );
        $ip = ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) ? md5( $_SERVER['REMOTE_ADDR'] ) : 'empty:ip:address';
            file_put_contents ( self::RAW_FILE_VISITORS . $today . '.php' ,  $now . ';' . $ip . PHP_EOL, FILE_APPEND | LOCK_EX );
    }


    /**
     * get stats.
     * Try the cache files first. If not available, use the raw log files and save a cache for next time.
     * Show only stats for last 14 days (= two weeks).
     */
    public static function getStats() {
        $today = date( 'Ymd', time() );
        $i = 0;
        $stats = array();
        $files = scandir( self::DIR_STATISTICS, SCANDIR_SORT_DESCENDING );
        foreach( $files as $file ) {
            $parts = explode( '.', $file );
            if ( isset( $parts[3] ) && $parts[0] == 'raw' && $parts[3] == 'php' ) {
                $timestamp = strtotime( $parts[2] );
                $date =  date( "d-m-Y", $timestamp );
                $cacheFile = self::STATS_FILE_VISITORS . $parts[2] . '.php';
                $dayTotals = FileManager::readFromCache( 'dayTotals', $cacheFile );
                if ( $dayTotals != false && ! empty( $dayTotals["visits"] ) ) {
                    $stats[ $date ] = $dayTotals;
                } else {
                    $rawVisits = self::readCsvStats( $file );
                    $visits = self::removeBots( $rawVisits );
                    $uniqueVisitors = self::getUniqueVisitors( $visits );
                    sort( $uniqueVisitors );

                    $stats[ $date ]["raw"] = count( $rawVisits );
                    $stats[ $date ]["visits"] = count( $visits );
                    $stats[ $date ]["unique"] = count( $uniqueVisitors );
                    if ( $today != $parts[2] ) {
                        FileManager::saveToCache( 'dayTotals', $stats[ $date ], $cacheFile, $type = 'php' );
                    }
                }
                $i++;
                if( $i > 14 ) break;
            }
        }
        return $stats;
    }


    /**
     * Reads the CBS csv source file and returns the data 
     */
    private function readCsvStats( $csvFile ) {
        // Check if the source file is available
        $header = array( 'timestamp', 'ipcode' );
        $data = array();
        if (($handle = fopen( self::DIR_STATISTICS . $csvFile , 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ';' )) !== FALSE) {
                if( count( $row ) == 2 ) {
                    $data[] = array_combine($header, array( $row[0], $row[1] ) );
                }
            }
            fclose($handle);
        }
        return $data;
    }


    /**
     * Removes ppossible bots from visitors log.
     * First, calculate some kind of 'bot score' to determine what is a bot and what not.
     *      Typical bot-behaviour is multiple refresh of the index-page in short period of time.
     * Second, create a list of possible bots and filter them out of the list of visitors.
     */
    private function removeBots( $rawVisits ) {
        // Calculate a bot score
        $previous = array( "timestamp" => 0, "ipcode" => 0 );
        $botScore = array();
        foreach( $rawVisits as $visit ) {
            if ( $visit["ipcode"] == $previous["ipcode"] 
                    && ( $visit["timestamp"] - $previous["timestamp"] ) < 10 ) {
                        $ipcode = $visit["ipcode"];
                        $botScore[ $ipcode ] = ( isset ( $botScore[ $ipcode ] ) ) ? $botScore[ $ipcode ] + 1 : 1;
            }
            $previous = $visit;
        }
        // Filter the bots out of raw visitors
        $bots = array();
        foreach ( $botScore as $key => $val ) {
            if ( $val > 3 ) {
                $bots[] = $key;
            }
        }
        // Remove bots from visitors list
        $visitors = array();
        foreach( $rawVisits as $visit ) {
            if( in_array( $visit["ipcode"] , $bots ) ) continue;
            $visitors[] = $visit;
        }
        return $visitors;


    }


    private function getUniqueVisitors( $visitors ) {
        $uniqueVisitors = array();
        foreach( $visitors as $visitor ) {
            if( array_key_exists ( $visitor["ipcode"] , $uniqueVisitors ) ) continue;
            $uniqueVisitors[ $visitor["ipcode"] ] = 1;
        }
        return array_keys( $uniqueVisitors );
    }

}

