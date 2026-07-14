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


if( isset( $_GET ) && isset( $_GET["download"] ) ) {

    $hospitalizationTotals = FileManager::readFromCache( FileManager::TOTALS_BY_DATE_CACHE_FILE );


    // interpoleer empty values
    $startArrayDate = new DateTime( "2020-10-01", new DateTimeZone( 'Europe/Amsterdam' ) );
    $notNull = true;
    $iDate = new DateTime( array_key_last( $hospitalizationTotals ) );
    //var_dump( $iDate ); 
    $endDate = new DateTime();  // define var to prevent warning in editor
    do {
        if( is_null( $hospitalizationTotals[ $iDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITALIZED ] ) ) {
            $notNull = false;
        } else {
            if( $notNull ) {
                    $endDate = new DateTime( $iDate->format( "Y-m-d" ) );
            } else {
                // vul het begin en het eind van de series aan met interpolatie velden voor datums waar geen gegevens voor zijn. 
                $notNull = true;
                $startDate = new DateTime( $iDate->format( "Y-m-d" ) ); 
                $startValue = $hospitalizationTotals[ $startDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITALIZED ];
                if( ! isset( $hospitalizationTotals[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITALIZED ] ) ) continue;
                $endValue = $hospitalizationTotals[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_HOSPITALIZED ];
                $interval = $startDate->diff( $endDate )->d;
                for( $offset = 1; $offset < $interval + 1; $offset++ ){
                    $endDate->modify( '-1 day' );
                    $hospitalizationTotals[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_INTERPOLATED ]
                        = ( ( ( $startValue - $endValue ) / $interval ) * $offset ) + $endValue;
                    if( 7 == (int) $endDate->format('N') ) { // simplified correction for the sunday hospital intake: only .75 of daily average 
                        $hospitalizationTotals[ $endDate->format( "Y-m-d" ) ][ FileManager::HEADER_INTERPOLATED ] *= 0.75;
                    }
                }
            }
        }
        $iDate->modify( '-1 day' );
    } while( $iDate > $startArrayDate );



    





    // Haal rioolwatermetingen op
    $wasteWaterTotals = FileManager::readFromCache( FileManager::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT );
    // Check wat de laatste dag is waar we nog gegevens van hebben.
    $lastDateWasteWater = new DateTime( end( $wasteWaterTotals )[ FileManager::HEADER_DATE ], new DateTimeZone( 'Europe/Amsterdam') );
    $lastDateHospital = new DateTime( array_key_last( $hospitalizationTotals ) , new DateTimeZone( 'Europe/Amsterdam') );
    $lastDate = ( $lastDateHospital > $lastDateWasteWater ) ? $lastDateHospital : $lastDateWasteWater;





    // Get Infection Radar Positive Tests data
    $infectionRadarPositiveTestsCacheSeries = FileManager::readFromCache( FileManager::CACHE_INFECTION_RADAR_POSITIVE_TESTS );
    $infectionRadarPositiveTests = array();
    foreach( $infectionRadarPositiveTestsCacheSeries as $row ) {
        $key = $row[ FileManager::HEADER_DATE ];
        $infectionRadarPositiveTests[ $key ] = $row;
    }






    // Voeg de gegevens van rioolwater en ziekenhuis samen
    $lastDate->modify( '+1 day');   // tel er 1 dag bij op omdat de while vergelijkt met < $lastDate en de lastDate moet er wel bij.
   //  var_dump( $lastDate ); // exit();
    $datetime = new DateTime( "2020-10-01", new DateTimeZone( 'Europe/Amsterdam') );
    $series = array();
    do {
        $key = $datetime->format( "Y-m-d" );
        // waste water
        $series[ $key ] = isset( $wasteWaterTotals[ $key ] ) ? $wasteWaterTotals[ $key ] : NULL;
        // hospital admissions
        if( isset( $hospitalizationTotals[ $key ] ) ) {
            $series[ $key ][ "hospital_admission" ] = isset( $hospitalizationTotals[ $key ][ FileManager::HEADER_HOSPITALIZED ] )
                        ? $hospitalizationTotals[ $key ][ FileManager::HEADER_HOSPITALIZED ] : NULL;
            $series[ $key ][ "hospital_interpolated" ] = isset( $hospitalizationTotals[ $key ][ FileManager::HEADER_INTERPOLATED ] )
                        ? $hospitalizationTotals[ $key ][ FileManager::HEADER_INTERPOLATED ] : $hospitalizationTotals[ $key ][ FileManager::HEADER_HOSPITALIZED ];
        }
        // infection radar positive tests
        if( isset( $infectionRadarPositiveTests[ $key ] ) ) {
            if( isset( $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_INTERPOLATED ] ) ) {
                if( NULL == $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] ) {
                    $series[ $key ][ "infection_radar_positive_tests_percentage" ] = NULL;
                } else {
                    $series[ $key ][ "infection_radar_positive_tests_percentage" ]
                        = ( $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_INTERPOLATED ] ) 
                                    ? NULL : $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ];
                }
            } 
            else $series[ $key ][ "infection_radar_positive_tests_percentage" ] = NULL;

            $series[ $key ][ "infection_radar_positive_tests_percentage_interpolated" ] 
                        = isset( $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] )
                                    ? $infectionRadarPositiveTests[ $key ][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] : NULL;

        }
        $datetime->modify( '+1 day');
    } while ( $datetime <= $lastDate );
    $lastDate->modify( '-1 day');   // Zet de lastDate weer terug maar de echte lastDate


    // Voorbereiden voor csv indeling.
    $header =  array( FileManager::HEADER_DATE, FileManager::HEADER_RNA,
                    FileManager::HEADER_RWZI_FACTOR, "RWZI_count", "hospital_admission", "hospital_interpolated",
                    "infection_radar_positive_tests_percentage", "infection_radar_positive_tests_percentage_interpolated"  );
    $delimiter = ';';

   // Als je het csv bestand wil wegschrijven naar lokaal bestand
   /*
    $fp = fopen('file.csv', 'w');
    fputcsv($fp, $header, $delimiter);  // schrijf de header
    foreach ($series as $row) {
        fputcsv($fp, $row, $delimiter);     // schrijf de gegevens
    }
    fclose($fp);
    */
 
    $csv = "";
    foreach( $header as $val ) {
        $csv.= $val . $delimiter;
    }
    $csv .= PHP_EOL;

    foreach( $series as $row ) {
        if( is_array( $row ) ) {
            foreach( $row as $val ) {
                $csv.= $val . $delimiter;
            }
        }
        $csv .= PHP_EOL;
    }

    // Output het csv bestand
    $filename = 'cijfers.landelijke.grafieken.corona-lokaal.nl.' . $lastDate->format( "Y.m.d" ) . '.csv';
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $csv;
    exit();


}

$random = rand();

?>
<!doctype html>
<html lang="nl">

<head>
    <title>Corona Lokaal | Download CSV bestand</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="keywords" content="actuele,interactieve, kaart,informatie,corona,gemeente,updates,grafiek,lokaal,per plaats,covid-19">
    <meta name="description" content="Corona Lokaal | Download CSV bestand - Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta name="copyright" content="(c)2022 kalahiri">
    <meta name="publisher" content="@kalahiri">
    <meta name="robots" content="ALL">

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

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
        .download-container {
            max-width:600px;
            padding: 1em 0;
            line-height:150%;
            font-size: 1em;
        }
        .download-title {
            margin:1em 0;
        }
        .download-form{
            text-align:center;
            margin:2em 0;
        }
        .description-container {
            max-width:1000px;
            padding: 1em 0;
            line-height:150%;
            font-size: 1em;
        }
        .description-container .label {
            min-width:100px;
            padding-right:1em;
        }
        .description-container table tr td {
            padding:0.3em 0;
            vertical-align: top;
        }
        .description-container table tr td p {
            font-size:1em;
            margin:0 0 1em 0;
        }
    </style>
</head>

<body>
    <div id="main-container">
        <!-- heading -->
        <div id="heading">
            <div>
                <img alt="logo" src="<?php echo BASE_URL ?>assets/favicon.svg">
                <h1>Corona Lokaal | Download CSV bestand</h1>
            </div>
        </div>
        <div class="download-container">
            <div class="download-title">Download het CSV-bestand van de landelijke rioolwatergrafiek,
                        de landelijke ziekenhuisopnames en het percentage positieve testen. 
                <form class="download-form" action="<?php echo BASE_URL ?>downloadcsv/" method="GET">
                    <input type="hidden" name="download" value="wastewater-lcps-csv"> 
                    <input type="hidden" name="val" value="<?php echo $random; ?>"> 
                    <input type="submit" value="download">
                </form>
            </div>
            <div style="clear:both;"></div>
        </div>

        <div class="description-container">
            <h3>toelichting gegevens</h3>
            <table>
                <tr>
                    <td class="label"><p>date</p></td>
                    <td class="description">datum</td>
                </tr>
                <tr>
                    <td class="label"><p>RNA_per_ml</p></td>
                    <td><p>aantal virusdeeltjes per ml. 
                        Voor de leesbaarheid is het aantal gedeeld door 100 miljard.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>RWZI_factor</p></td>
                    <td><p>het deel van de rioolwaterzuiveringen waarvan de waarde voor die dag
                        bekend is uitgedrukt in aantal personen in het totale verzorgingsgebied
                        van de rioolwaterzuiveringen bij elkaar.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>RWZI_count</p></td>
                    <td><p>het aantal rioolwaterzuiveringen waarvan de waarde voor die dag bekend is.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>hospital_admission</p></td>
                    <td><p>het aantal ziekenhuisopnames dat op die dag door het LCPS is gemeld.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>hospital_interpolated</p></td>
                    <td><p>hetzelfde als de vorige waarbij de lege velden zijn geïnterpoleerd.
                        Vanaf 16 april 2022 is het LCPS gestopt met het rapporteren van opnamecijfers in de weekenden.
                        </p><p>
                        Interpolatie is in principe lineair, maar op zondag is 75% van die waarde genomen. 
                        De opnames bleken op zondag altijd substantieel lager te liggen dan op andere dagen. 
                        75% van de geïnterpoleerde waarde lijkt een goede benadering op te leveren.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>infection_radar_positive_tests_percentage</p></td>
                    <td><p>het percentage positieve testen zoals dat door de infectieradar van het RIVM gepubliceerd wordt.
                        Als datum wordt het midden van de meetperiode aangehouden, dwz het midden van de periode van maandag
                        tot en met de daaropvolgende zondag.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label"><p>infection_radar_positive_tests_percentage_interpolated</p></td>
                    <td><p>hetzelfde als de vorige waarbij de lege velden tussen de publicatiedatums zijn geïnterpoleerd. 
                        Het RIVM publiceert het percentage positieve testen op dit moment eens per week. De interpolatie is lineair.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
