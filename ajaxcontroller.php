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

// first check if update is available
if ( FileManager::isUpdateSourceFileAvailable() ) {
    FileManager::getRemoteFile();
    FileManager::getRemoteCasusFile();
}
// first check if NICE data should be updated.
if ( FileManager::shouldNiceBeUpdated() ) {
    FileManager::getRemoteNICEFile();
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
                        if ( $_POST["value"] == 'Nederland' ) {
                            $wasteWaterModel->getChartDataTotals();
                        } else {
                            $plant = ValidateUtils::filterString( substr( $_POST["value"], 0, 35 ) ); // Sanitize input
                            $wasteWaterModel->getChartData( $plant );
                        }
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
function generalError( $errorMessage = '' ) {
    $ajaxResponse = new AjaxResponse;
    if ( empty( $errorMessage ) ) {
        $errorMessage = 'General ajax-API error in Ajax Controller.';
    }
    $ajaxResponse->die( $errorMessage );
}

