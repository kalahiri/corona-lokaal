<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */


/**
 * Class to create a uniform response to an ajax request 
 */
class AjaxResponse {
    private $error = array();
    private $data = array();

    public function __construct() {
        // empty
    }


    /**
     * Add an error to the response
     */
    public function addError( $string ) {
        $this->error[] = $string;
    }


    /**
     * Add some data (string) to the ajax response
     */
    public function addData( $key, $value ) {
        $this->data[$key] = $value;
    }


    /**
     * Add the result from a function, like a database-query
     * directly to the ajax response. 
     */
    public function addResult( $result ) {
        if ( $result === false ) {
            $this->addError( $result );
        }
    }


    /**
     * Send error message and abort script
     */
    public function die( $string ) {
        $this->error[] = $string;
        $this->send();
    }


    /**
     * echo the ajax response
     */
    public function send( $exit = true ) {
        // header("Content-Type: application/json; charset=UTF-8");
        echo json_encode( $this->toArray() );
        if ( $exit ) exit();
        return true;
    }


    /**
     * encode the ajax response to a JSON string 
     */
    public function encode() {
        return json_encode( $this->toArray() );
    }

    
    /**
     * Check if some error has been added to the ajax response
     */
    public function hasErrors() {
        return count( $this->error ) ? true : false;
    }


    /**
     * Convert the ajax response object to an array. This is necessary to encode
     * it as a JSON string, as json_encode cannot encode objects directly. 
     */
    private function toArray() {
        if ( $this->hasErrors() ) {
            $success = 0;
            $this->data = array();  // empty the data if there are errors
        } else {
            $success = 1;
        }
        return array (
            "error" => $this->error,
            "data" => $this->data,
            "success" => $success
        );
    }


}