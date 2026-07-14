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
require_once( "config.php" );


$cityModel = new CityModel();

if( isset( $_GET["location"] ) ) {
    $location = filter_var( substr( $_GET["location"], 0, 35 ), FILTER_SANITIZE_STRING );
    $location = str_replace( "_", " ", $location );
    $cityData = $cityModel->getChartData( $location, false );

    $previous = array();
    $csv = "";
    $row = current( $cityData );
    foreach( $row as $key => $val ) {
        $previous[ $key ] = $val;
        $csv.= $key . ',';
    }
    //var_dump( $previous ); exit();

    $csv .= PHP_EOL;
    foreach( $cityData  as $row ) {
        foreach( $row as $key => $val ) {
            if ( $key == "reported" || $key == "hospitalized" || $key == "deceased" ) {
            $increase = $val - $previous[ $key ];
            $previous[ $key ] = $val;
            $csv.= $increase . ',';
            } else {
                $csv.= $val . ',';
            }
        }
        $csv .= PHP_EOL;
    }

    $date = date( '.Y.m.d' ); 
    $filename = 'csv.' . str_replace( " ", "_", $location ) . $date . '.corona-lokaal.nl.csv';
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $csv;
    exit();


}



?>
<!doctype html>
<html lang="nl">

<head>
    <title>Corona Lokaal | CSV bestand</title>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="keywords" content="actuele,interactieve, kaart,informatie,corona,gemeente,updates,grafiek,lokaal,per plaats,covid-19">
    <meta name="description" content="Corona Lokaal | CSV bestand - Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta name="copyright" content="(c)2020 kalahiri">
    <meta name="publisher" content="@kalahiri">
    <meta name="robots" content="ALL">

    <link rel="shortcut icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="stylesheet" href="<?php echo BASE_URL . FileUtils::addTimeStamp('assets/styles.css'); ?>" type="text/css" media="all" />

	<style>
        @font-face {
            font-family: "Exo2";
            src: url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.woff") format("woff"), /* Modern Browsers */
                 url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.woff2") format("woff2"), /* Modern Browsers */
                 url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.ttf");
        }
        @font-face {
            font-family: "Font Awesome Solid";
            src: url("<?php echo BASE_URL ?>assets/fonts/Font_Awesome_5.8_Free_Solid.woff") format("woff");
        }
        @font-face {
            font-family: "Font Awesome Brands";
            src: url("<?php echo BASE_URL ?>assets/fonts/Font_Awesome_5.8_Brands_Regular.woff") format("woff");
        }
    </style>
</head>

<body>
    <div id="main-container">
        <!-- heading -->
        <div id="heading">
            <div>
                
            <img alt="logo" src="<?php echo BASE_URL ?>assets/favicon.svg">
            <h1>Corona Lokaal | CSV bestand</h1>
            </div>
        </div>
        <div class="location-title">Download het CSV-bestand van de rioolwatergrafieken en de ziekenhuisopnames: 
            <button value="download" onClick="window.location='<?php echo BASE_URL ?>csv/download/wastewaterhospitalisation'">download</button>
        </div>
        <div style="clear:both;"></div>
    </div>
</body>
</html>
