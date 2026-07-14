<?php
/**
 * This file is written by Kalahiri in 2017 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Get the localized DateTime strings. 
 * PHP's DateTime object is not suited for this, so it is not used or extended
 */

class DateTimeUtils {
    private static $dagen=Array(
		"","maandag","dinsdag","woensdag","donderdag","vrijdag","zaterdag", "zondag");

    private static $maanden=Array("","januari", "februari", "maart", "april", "mei", 
                "juni", "juli", "augustus", "september", "oktober", "november", "december");

 
    /*
     * Maakt de tijd als "23:17"
     */
    static public function getTime($timestamp) {
        return date("H:i",$timestamp);
    }
    
    /*
     * Maakt de tijd als "23:17:51"
     */
    static public function getFullTime($timestamp) {
        return date("H:i:s",$timestamp);
    }
   
    /**
     * Maakt datumstring als "zaterdag, 28 maart 2016"
     */
    static public function getFullDate($timestamp, $comma = ',' ) {
        $d=date("N",$timestamp);
        $dag= self::$dagen[$d];
        return $dag . $comma . " " . self::getDate( $timestamp );
    }
    
    /**
     * Maakt datumstring als "28 maart 2016"
     */
    static public function getDate($timestamp) {
        $m=date("n",$timestamp);
        $maand= self::$maanden[$m];
        return date("j ",$timestamp).$maand.date(" Y",$timestamp);
    }

    /**
     * Maakt de maand met jaar als "maart 2016"
     */
    static public function getMonthAndYear($timestamp) {
        $m=date("n",$timestamp);
        $maand= self::$maanden[$m];
        return $maand.date(" Y",$timestamp);
    }

    /**
     * Maakt de datum als "28 maart"
     */
    static public function getDayMonth($timestamp) {
        $m = date( "n", $timestamp );
        $j = date( "j", $timestamp );
        $maand= self::$maanden[ $m ];
        return $j . ' ' . $maand;
    }
    
    /**
     * Maakt de datum als "28-03-2016"
     */
    static public function getShortDate($timestamp) {
        return date("d-m-Y", $timestamp);
    }

    /**
     * Geeft de dag in de week
     */
    static public function getDayOfTheWeek($timestamp) {
        $dayOfTheWeek = date("N", $timestamp );
        $dayName = self::$dagen[ $dayOfTheWeek ];
        return array( 'dayOfTheWeek' => $dayOfTheWeek, 'dayName' => $dayName );
    } 

    
    /**
     * Maakt complete datumtijd array
     */
    static public function getDateTimeArray($timestamp) {
        $datetime=array();
        $d=date("N",$timestamp);
        $datetime["day_noun"]= self::$dagen[$d];
	$datetime["day"] = date("d", $timestamp);
	$datetime["month"] = date("n", $timestamp);
        $m=date("n",$timestamp);
	$datetime["month_noun"] = self::$maanden[$m];
	$datetime["year"] = date("Y", $timestamp);
	$datetime["hour"] = date("G", $timestamp);
	$datetime["minute"] = date("i", $timestamp);
        return $datetime;
    }

}
