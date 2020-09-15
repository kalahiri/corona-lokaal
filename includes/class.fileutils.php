<?php
/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */


class FileUtils {
    const COOKIE_PATH = "data/cookies/";


   /**
     * Use last modification timestamp of a file as a file fingerprint.
     * This can be used to reload only files that have been changed and cache 
     * the unchanged files on the users system. 
     */
    static public function addTimeStamp( $file ) {
        if ( ! file_exists( $file ) ) return $file;
        $mTime = filemtime( $file );
        $pathinfo = pathinfo( $file ); 
        return $pathinfo["dirname"] . '/' . $pathinfo["filename"] . '.' . $mTime  . '.' . $pathinfo["extension"];
    }

 
    /**
     * Places a server cookie. Existing cookie with same name is overwritten.
     * 
     * $cookie = name of cookie
     * $value = value or contect of cookie to be set.
     * $cookie_path is optional to specify a specific cookiefolder
     * 
     * returns true on succes.
     */
    static public function writeCookie( $cookie,$value, $cookie_path = self::COOKIE_PATH ) {
        $cookie= $cookie_path . "cookie." . $cookie . ".php";
        $success = file_put_contents ($cookie, $value); 
        return ($success !== false) ? true : false;
    }

    /**
     * Reads the contents of a server cookie.
     * 
     * $cookie = name of cookie
     * $cookie_path is optional to specify a specific cookiefolder
     * 
     * returns the content (value) of the cookie or false.
     */
    static public function readCookie( $cookie, $cookie_path = self::COOKIE_PATH) {
        $cookie= $cookie_path . "cookie." . $cookie . ".php";
        if( file_exists( $cookie ) ) {
            return file_get_contents( $cookie );
        }
        return false;
    }

    
    
    
    
    
    
}


