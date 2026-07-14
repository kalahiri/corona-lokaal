<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

class ArrayUtils {
    const ONE_DAY = 86400; // 1 day = 60 * 60 * 24 = 86400;

    
    /**
     * Constructor
     */
    public function __construct() {


    }


    /**
     * Get a slice of the array for (a) specific date(s).
     * If endDate is given, it returns the number of days before and including the endDate 
     * If startDate is given, it returns the number of days after and including the startDate
     * $endDate { int } UNIX-timestamp 
     * $startDate { int } UNIX-timestamp 
     */
    public static function getSliceByDate( $array, $beginDate = 0, $endDate = 0, $days = 0 ) {
        $indexStart = false;
        $indexEnd = false;
        $length = true;
        // check whether $beginDate, $endDate and/or $days is set
        if( $endDate == 0 && $days == 0 ) $length = NULL;
        elseif( $beginDate == 0 || $endDate == 0 ) {
            if( $beginDate > 0 ) {
                $endDate = $beginDate + ( $days * self::ONE_DAY ); 
            } else {
                $endDate += self::ONE_DAY;
                $beginDate = $endDate - ( $days  * self::ONE_DAY );
            }
        }

        // We got all the parameters lined up. Let's slice the array!
        $dateStart = date( "Y-m-d", $beginDate );
        $indexStart = array_search( $dateStart, array_column( $array, FileManager::HEADER_DATE ) );
        if ( $length ) {
            $dateEnd = date( "Y-m-d", $endDate );
            $indexEnd = array_search( $dateEnd , array_reverse( array_column( $array, FileManager::HEADER_DATE ), true ) );
            $length =  ( $indexEnd !== false ) ? $indexEnd - $indexStart : NULL;
        }
        return array_slice( $array, $indexStart, $length );
    }


    public static function getCount( $array ) {
        $count = array();
        $keys = array_keys( current( $array ) );
        foreach( $keys as $key ) {
            $count[ $key ] = 0;
        }
        foreach( $array as $row ) {
            foreach( $row as $key => $val ) {
                if( $key == FileManager::HEADER_DATE || ! isset( $count[ $key ] ) ) {
                    $count[ $key ] = $val;
                } else {
                    $count[ $key ] += $val;
                }
            }

        }
        return $count;
    }


    /**
     * Calculates the percentages of values in an array. 
     * If $sum == 0 it uses the count of all values as the $sum ( to be implemented! )
     */
    public static function getPercentages( $array, $sum = 0 ) {
        if ( $sum === 0 ) {
            foreach( $array as $key => $val ) {
                if( $key == FileManager::HEADER_DATE ) continue;
                $sum += $val;   
            }
            $array[ FileManager::HEADER_COUNT ] = $sum;
        }
        foreach( $array as $key => $val ) {
            if( $key === FileManager::HEADER_DATE || $key === FileManager::HEADER_COUNT ) continue;
            $array[ $key ] = ( $val / $sum );   
        }
        return $array;
    }


    public static function combineSeries( $series1, $series2 ) {
        $dates = array_column( $series2, FileManager::HEADER_DATE );
        foreach( $series1 as $key => $val ) {
            $key2 = array_search($key, $dates );
            if ( $key2 !== false ) {
                $series2[ $key2 ][ "prediction" ] = $val;
            }
        }
        return $series2;

    }



    /**
     * Echo the array in csv format to the screen.
     */
    public static function printCsv( $series ) {

        foreach( $series as $key => $row ) {
            if ( is_array( $row ) ) {
                foreach( $row as $key2 => $val ) {
                    echo $key2 . ',' . $val . '<br>';
                }
            } else {
                echo $key . ',' . $row . '<br>';
            }
        }
        return true;

    } 
 

    /**
     * Check if a variable is a not empty array. 
     */
    public static function checkArray( $array ) {
        return is_array( $array ) && ! empty( $array ); 
    }


}
