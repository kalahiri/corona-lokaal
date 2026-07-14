<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

class WebPageReader {
    protected $webPage;
    protected $urlWebPage;

    const MONTHS = array( "januari" => "January", "februari" => "February", "maart" => "March",
        "april" => "April", "mei" => "May", "juni" => "June", "juli" => "July", "augustus" => "August",
        "september" => "September", "oktober" => "October", "november" => "November", "december" => "December"
    );
    
    public function __construct() {
        // nothing to do.
    }


    /**
     * return the web page
     */
    public function getWebPage() {
        $this->readWebpage();
        return $this->webPage;
    }

    
    /**
     * Set the webpage to read
     */
    public function setUrlWebPage( $url ) {
        if( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            new LogException( "Invalid URL", __LINE__ );
            return false;
        }
        $this->urlWebPage = $url;
        return true;
    }

 
    /**
     * Get contents of the remote website
     */
    public function readWebpage() {
        if ( isset( $this->webPage ) && ! empty( $this->webPage ) ) return true;
        if ( ! filter_var( $this->urlWebPage, FILTER_VALIDATE_URL ) ) {
            new LogException( 'Error reading webpage. No valid URL given.', __LINE__ );
            return false;
        }
        // FileUtils::downloadRemoteFile( $this->urlWebPage, 'data/cache/cache.webPage.txt' );
        $this->webPage = FileUtils::getRemoteFile( $this->urlWebPage );
        // exit();
        // $this->webPage = file_get_contents( 'data/cache/cache.webPage.txt' );
       // $this->webPage = file_get_contents( $this->urlWebPage );
        if ( empty( $this->webPage ) || $this->webPage === false ) {
            new LogException( 'Error reading webpage ' . $url, __LINE__ );
            return false;
        }
        return true;
    }




}