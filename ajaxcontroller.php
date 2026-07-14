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

// echo json_encode( $_POST ); 

if ( FileUtils::readCookie( 'updating' ) ) {
    updating();
}

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
                            $location = ValidateUtils::filterString( substr( $_POST["value"], 0, 35 ) ); // Sanitize input
                            $cityModel->getChartData( $location );
                        }
                    } 
                    break;
                default:
                    generalError();
            }
        break;
        case "plant":
            switch ( $_POST["action"] ) {
                case "get":
                    if ( isset( $_POST["value"] ) && ! empty( $_POST["value"] ) ) {
                        $wasteWaterModel = new WastewaterModel();
                        if ( 'Nederland' == $_POST["value"] || 'false' ==  $_POST["value"] ) { // Nederland
                            $wasteWaterModel->getChartDataTotals();
                        } else { 
                            if( 'PV' == substr( $_POST["value"], 0, 2 ) ) { // province
                                $plant = ValidateUtils::filterString( substr( $_POST["value"], 0, 35 ) ); // Sanitize input
                                $wasteWaterModel->getChartAndMapDataPerProvince( $plant );
                            } else {    // rioolwaterstation
                               $plant = ValidateUtils::filterString( substr( $_POST["value"], 0, 35 ) ); // Sanitize input
                               $wasteWaterModel->getChartData( $plant );
                            }
                        }
                    }
                    break;
                default:
                    generalError();
            }
        break;
        case "hospitalized":
            switch ( $_POST["action"] ) {
                case "get":
                    if ( isset( $_POST["value"] ) && ! empty( $_POST["value"] ) ) {
                        $provinceModel = new ProvinceModel();
                            if( 'PV' == substr( $_POST["value"], 0, 2 ) ) { // province
                                $province = ValidateUtils::filterString( substr( $_POST["value"], 0, 4 ) ); // Sanitize input
                                $provinceModel->getChartData( $province );
                            } else generalError();
                        }
                    break;
                default:
                    generalError();
            }
        break;
        case "text":
            switch ( $_POST["action"] ) {
                case "get":
                    if ( isset( $_POST["value"] ) && ! empty( $_POST["value"] ) ) {
                        $textModel = new TextModel();
                        $textId = ValidateUtils::filterString( substr( $_POST["value"], 0, 25 ) ); // Sanitize input
                        $textModel->getTextData( $textId );
                    }
                break;
                default:
                    generalError();
            }

        break;
        case "infectionRadar":
            switch ( $_POST["action"] ) {
                case "get":
                    if ( isset( $_POST["value"] ) && ! empty( $_POST["value"] ) ) {
                        $infectionRadarModel = new InfectionRadarModel();
                        if ( 'positiveTests' == $_POST["value"]  ) { // Get the Positive Tests of the Infection Radar
                            $infectionRadarModel->getChartDataPositiveTests();
                        } else {
                            $infectionRadarModel->hideChartDataPositiveTests();
                        }
                    } else {
                        generalError();
                    }
                    break;
                default:
                    generalError();
            }
        break;
        default:
            generalError( 'ERROR: arrived at default in AjaxController with category: ' . $_POST["category"] );
    }
}


/**
 * Ajax response with general error message. 
 */
function updating() {
    $ajaxResponse = new AjaxResponse;
    $ajaxResponse->addData( "updating", 1 );
    $ajaxResponse->send();
}

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

