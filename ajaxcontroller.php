<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

 /**
 * Register the autoloader for classes.
 * Notice: autoloading doesn't support constants.
 */
require_once( "autoloader.php" );


// Do some security checks


    if ( isset( $_POST ) && isset( $_POST["action"] ) && isset( $_POST["category"] ) )  {

        // Switch to the right class and method to execute the AJAX request
        switch ( $_POST["category"] ) {
            case "location":
                switch ( $_POST["action"] ) {
                    case "get": 
                        if ( isset( $_POST["value"] ) && ! empty( $_POST["value"] ) ) {
                            if ( $_POST["value"] == 'Nederland' ) {
                            $countryModel = new countryModel();
                            $countryModel->getChartData();
                            } else {
                            $cityModel = new cityModel();
                            $cityModel->getChartData( $_POST["value"] );
                            }
                        } 
                        break;
                    default:
                        generalError();
                }
                break;
                default:
                    $this->generalError( 'ERROR: arrived at default in AjaxController with category: ' . $_POST["category"] );
        }
    }

    exit();



    /**
     * Ajax response with general error message. 
     */
    function generalError( $errorMessage = '' ) {
        $ajaxResponse = new AjaxResponse;
        if ( empty( $errorMessage ) ) {
            $errorMessage = 'General ajax-API error in Ajax Controller.';
        }
        $ajaxResponse->die( $errorMessage );
    }

