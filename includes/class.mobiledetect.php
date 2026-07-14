<?php
/**
 * This file is written by Kalahiri in 2019 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

 /**
  * Detects if a device is a mobile or a desktop.
  * Uses external class Mobile_Detect.php from http://mobiledetect.net
  */

class MobileDetect {
    const USER_PREFERENCE_COOKIE = 'user_preference_device_type';
    const SESSION_COOKIE = 'ismobile';
    const LAYOUT_MOBILE = true;
    const LAYOUT_DESKTOP = false;
    
    const COOKIE_VALUE_MOBILE = 2;
    const COOKIE_VALUE_DESKTOP = 1;



    /**
     * Force a layout by setting a cookie 
     * @var bool $useMobile true for mobile layout, false for desktop layout
     */
    public function forceMobileLayout( $useMobile ) {
        $userPref = ( $useMobile ) ? self::COOKIE_VALUE_MOBILE : self::COOKIE_VALUE_DESKTOP;
        $this->saveUserPreference( $userPref );
    }


    /**
     * Check if user device is mobiel or desktop.
     * Perform check only once per session and save outcome in $_SESSION.
     */
    public function isMobile() {
        // check if we detected device already during this SESSION
        if( isset( $_SESSION[ self::SESSION_COOKIE ] ) && ! empty( $_SESSION[ self::SESSION_COOKIE ] ) ) {
            if( self::COOKIE_VALUE_MOBILE == $_SESSION[ self::SESSION_COOKIE ] ) return self::LAYOUT_MOBILE;
            if( self::COOKIE_VALUE_DESKTOP == $_SESSION[ self::SESSION_COOKIE ] ) return self::LAYOUT_DESKTOP;
        }
 
        /*
        * $detect = new Mobile_Detect;
        * $detect->isMobile() : true/false  Any mobile device (phones or tablets).
        * $detect->isTablet() : true/false  Any tablet device.
        * $detect->isMobile() && ! $detect->isTablet() : true/false  Exclude tablets.
        * $detect->isiOS() : true/false   Check for a iOS platform 
        * $detect->isAndroidOS() : true/false   Check for a Android platform 
        */
        require_once( 'includes/libs/Mobile_Detect.php');
        $detect = new Mobile_Detect();
        $isMobile = $detect->isMobile() && ! $detect->isTablet(); // mobile devices but no tablets

        // save detection result for duration of session
        $userPref = ( $isMobile ) ? self::COOKIE_VALUE_MOBILE : self::COOKIE_VALUE_DESKTOP; 
        $this->saveUserPreference( $userPref );

        return $isMobile;
    }


    /**
     * Check if user has set preference for mobile or dekstop layout
     * User can force the mobile or desktop version. This setting is saved in a cookie.
     * 
     * @return false no preference has been set; 
     * @return 1 is desktop layout; 
     * @return 2 is mobile layout; 
     */    
    public function getUserPreference() {
        if ( isset($_COOKIE[ self::USER_PREFERENCE_COOKIE ] ) && ! empty( $_COOKIE[ self::USER_PREFERENCE_COOKIE ] ) ) {
            return  (int) $_COOKIE[ self::USER_PREFERENCE_COOKIE ];
        } else return false;
    }


    /**
     * Set the cookie containing the user preference for mobile or desktop layout
     * Cookie will be kept for 30 * 24 * 3600 seconds is 30 days. 
     * 
     * @var int $val is self::COOKIE_VALUE_MOBILE (=2) or self::COOKIE_VALUE_DESKTOP (=1)
     */
    public function saveUserPreference( $val ) {
        return setcookie( self::USER_PREFERENCE_COOKIE, (string) $val, time() + 30 * 24 * 3600, "/" );
    }


    /**
     * Make sure the value is one of the valid user preferences available.
     * 
     * @var mixed   $rawValue can be anything 
     * @return int  a valid user preference code.If input value is not a valid code, 
     *              self::COOKIE_VALUE_DESKTOP is returned
     */
    public function filterValidUserPreference( $rawValue ) {
        return ( self::COOKIE_VALUE_MOBILE == (int) $rawValue ) 
                        ? self::COOKIE_VALUE_MOBILE : self::COOKIE_VALUE_DESKTOP;
    }


}
