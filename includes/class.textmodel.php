<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of text and images
 */
class TextModel {
    private $baseUrl;

    /**
     * Constructor
     */
    public function __construct() {
        require_once( 'config.php' );   // so we can use global constants in config.php
        $this->baseUrl = Constants::BASE_URL; 
        $this->baseUrl = Constants::BASE_URL; 
    }



    /**
     * Get the data to create the charts for a specific waste water plant
     */
    public function getTextData( $textId, $ajax = true ) {
        $id = ( 0 === strpos( $textId, 'qena-question-' ) ) ? ValidateUtils::filterInt( str_replace( 'qena-question-', '', $textId ) ) : false;
        if( false === $id ) return false;
        $sourcefile = str_replace( '{number}', $id, FileManager::TEXT_FILE_Q_EN_A );

        $rawText = FileManager::readText( $sourcefile );

        if( false === $rawText ) return false;

        // convert raw text

        // convert images:
        $pattern = '|src="([^"]+)"|';
        $count = preg_match_all( $pattern, $rawText, $matches );
        $search = array( '[basepath]' );
        $replace = array( '' );
        $images = array();
        foreach( $matches[1] as $imageSource ) {
            $images[] = $this->baseUrl . fileUtils::addTimeStamp( '.' . str_replace( $search, $replace, $imageSource ) );
        }
        $textData = str_replace( $matches[1], $images, $rawText );


        // convert Id.
        $textId = str_replace( 'question', 'answer', $textId );
        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'textData' , $textData );

            $ajaxResponse->addData( 'textId', $textId );
            $ajaxResponse->addData( 'type', array( 'category' => 'text', 'region' => '' ) );
            $ajaxResponse->send();
        }
    }
 





}