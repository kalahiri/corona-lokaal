<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */


class ValidateUtils {

    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Filter input supposed to be string.
     */
    public static function filterString( $string, $preserveNewLine = false ) { 
        $string = trim( $string );
        if ( $preserveNewLine ) $string = str_replace( "\n", "[NEWLINE]", $string );
        $string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );
        if ( $preserveNewLine ) $string =str_replace( "[NEWLINE]", "\n", $string );
        return $string;
    } 

    /**
     * Filter input supposed to be a HTML text.
     */
    public static function filterHtml( $string ) {
        // TODO
    } 


    /**
     * Filter input supposed to be a boolean.
     * Returns TRUE for "1", "true", "on" and "yes". Returns FALSE otherwise.
     */
    public static function filterBool( $bool ) {
        return filter_var( $bool, FILTER_VALIDATE_BOOLEAN );
    } 


    /**
     * Filter input supposed to be integer.
     * Returns the value of the integer if it is an integer, else returns false.
     */
    public static function filterInt( $int, $noNegativeValues = true ) {
        if ( $noNegativeValues ) {
            return filter_var( $int, FILTER_VALIDATE_INT, array( "options"=>array("min_range"=> 0) ) );
        } else {
            return filter_var( $int, FILTER_VALIDATE_INT );
        }
    } 


    /**
     * Filter input supposed to be string containing a hex color value.
     */
    public static function filterHexColor( $hexColor ) {
        return self::validateHexColor( $hexColor ) ? strtoupper( $hexColor ) : false;

    }


    /**
     * Validate input supposed to be string containing a hex color value.
     * ctype_xdigit() returns true if only hex characters in a string
     */
    public static function validateHexColor( $hexColor ) {
        if ( ! is_string( $hexColor ) ) return false;   // is it a string?
        if ( strpos( $hexColor, '#' ) !== 0 ) return false; // Does it start with '#'?
        $hexColor = ltrim( $hexColor, '#' );    // Strip the '#'.
        return ctype_xdigit( $hexColor ) && ( strlen( $hexColor ) == 3 || strlen( $hexColor ) == 6 );
    }

    
    /**
     * There is no built-in function to validate the datetime format used by MySQL.
     * So we use this method to do the job.
     */
    public static function validateDate( $date, $format = self::DATETIME_FORMAT ) {
        $dateObject = \DateTime::createFromFormat( $format, $date );
        return $dateObject && $dateObject->format( $format ) == $date;
    }


    /**
     * There is no built-in function to filter the datetime format used by MySQL.
     * So we use this method to do the job.
     */
    public static function filterDate( $date, $format = self::DATETIME_FORMAT ) {
        $dateObject = \DateTime::createFromFormat( $format, $date );
        return $dateObject->format( $format );
    }

}