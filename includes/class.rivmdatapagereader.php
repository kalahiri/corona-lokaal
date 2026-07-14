<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

class RivmDataPageReader extends WebPageReader {
    const URL_RIVM_DATA_PAGE = 'https://data.rivm.nl/covid-19/';


    public function __construct( $url = self::URL_RIVM_DATA_PAGE ) {
        $this->setUrlWebPage( $url );
        $this->readWebpage();
        // var_dump( $this->webPage );
    }


    /**
     * Get the remote web page and see if there are notifications 
     */
    public function getNotifications() {

        if( strlen( $this->webPage ) < 400 ) { // check size if we have an acceptable update
            return false;
        }
        return $this->parseWebpage();
    }



    /**
     * Parse the Web Page and get the relevant data from it.
     */
    private function parseWebpage() {
       $domParser = new DomParser( $this->webPage );
       $nodeList = $domParser->selectByTag( 'p', NULL, DomParser::RETURN_NODE_LIST );

        $result = '';
        foreach( $nodeList as $node ) {
            //if( strpos( $node->textContent, 'open data worden om 15.15 uur gepubliceerd' ) !== false ) continue;
            if( strlen( trim( $node->textContent ) ) < 5 ) continue;
            $result .= isset( $node->nodeValue ) ? $node->nodeValue . '<br>' : '';

/*
            $result .= ( isset( $node->firstChild->textContent ) && ! empty( $node->firstChild->textContent )
                                                                  && strlen( $node->firstChild->textContent ) > 10 ) 
                            ? $node->firstChild->textContent . ' ' : $node->textContent  . ' ';
                            */
        }

        $result = str_replace( array( 'De open data worden om 15.15 uur gepubliceerd.','The open data will be published at 15:15 hours.'),array('',''),$result);
       
        // remove weird characters like &emsp; from $result by stripping all non-ASCII characters from the input string. 
        return trim( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $result) );

    }





}