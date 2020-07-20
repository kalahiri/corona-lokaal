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

$location = 'Nederland';
if (isset ( $_GET["location"] )) {
    // Sanitize input
    $location = filter_var( substr( $_GET["location"], 0, 35 ), FILTER_SANITIZE_STRING );
}
$cityModel = new CityModel();
$cityList = $cityModel->getListOfCities();

// $rankingModel = new RankingModel();


?><!doctype html>
<html>

<head>
    <title><?php echo DOMAIN_TITLE . $location ?></title>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="referrer" content="no-referrer">
            
    <meta name="keywords" content="corona,updates,grafiek,lokaal,per plaats">
    <meta name="description" content="<?php echo DOMAIN_TITLE . $location ?>">
    <meta name="copyright" content="(c)2020 kalahiri">
    <meta name="publisher" content=kalahiri">
    <meta name="robots" content="ALL">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@kalahiri">
    <meta name="twitter:title" content="<?php echo DOMAIN_TITLE . $location ?>">
    <meta name="twitter:description" content="<?php echo DOMAIN_TITLE . $location ?>">
    <meta name="twitter:image" content="">
    <meta property="og:url" content="<?php echo BASE_URL; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?php echo DOMAIN_TITLE . $location ?>" />
    <meta property="og:description" content="<?php echo DOMAIN_TITLE . $location ?>" />
    <meta property="og:image" content="" />

    <link rel="shortcut icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">

    <link rel="stylesheet" href="<?php echo BASE_URL ?>assets/styles.css" type='text/css' media='all' />
	<script src="<?php echo BASE_URL ?>Chart.js-2.9.3/Chart.min.js"></script>
	<script src="<?php echo BASE_URL ?>Chart.js-2.9.3/utils.js"></script>
	<script src="<?php echo BASE_URL ?>assets/script.jquery.latest.min.js"></script>
	<script src="<?php echo BASE_URL ?>assets/script.js"></script>
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
        <div class="wrapper">

            <!-- heading -->
            <div id="heading">
                <img src="<?php echo BASE_URL ?>assets/favicon.svg">
                <h1>Corona Lokaal | <?php echo $location ?></h1>
            </div>
            <div class="location-title">Kies plaatsnaam: 
                <select id="selectLocation" onChange="rivmCharts.doAction( 'get', 'location', this.value );">
                        <option value="Nederland" <?php
                                    if( "Nederland" == $location ) echo 'selected="selected"'; 
                                ?>>Nederland</option>
                    <?php foreach( $cityList as $city ) : ?>
                        <option value="<?php echo $city ?>" <?php
                                    if( $city == $location ) echo 'selected="selected"'; 
                                ?>><?php echo $city ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="location-title" id="title"></div>
            <div style="clear:both;"></div>

            <!-- Charts -->
            <div class="canvas-container">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e</div>
                <canvas id="canvas-reported"></canvas>
            </div>
            <div class="canvas-container"">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e</div>
                <canvas id="canvas-hospitalized"></canvas>
            </div>
            <div class="canvas-container">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e</div>
                <canvas id="canvas-deceased"></canvas>
            </div>

            <!-- info container -->
            <div class="canvas-container info-container">
                <p class="notice">Opmerkingen:</p>
                    <ul>
                        <li>Bekijk de <a href="" onClick="rivmCharts.doAction( 'get', 'location', 'Nederland' )">landelijke cijfers</a>.</li>
                        <li>Negatieve waarden zijn het gevolg van administratieve correcties bij het RIVM</li>
                        <li>Bronbestanden zijn te vinden bij het <a target="_blank" href="https://data.rivm.nl/covid-19/">RIVM</a>.</li> 
                        <li>Broncode van deze site vind je <a target="_blank" href="">hier</a> en is vrij te gebruiken.</li> 
                    </ul>
                </p>
                <div id="share-container">Deel via: 
                    <a id="twitter" href="" rel="nofollow" target="_blank" title="deel dit artikel via twitter">
                        <span class="fa-brands">&#xf099</span>
                    </a>
                    <a  id="facebook" href="" rel="nofollow" target="_blank" title="deel dit artikel via facebook">
                        <span class="fa-brands">&#xf09a</span>
                    </a>
                    <a id="linkedin" href="" rel="nofollow" target="_blank" title="deel dit artikel via linkedin">
                        <span class="fa-brands">&#xf08c</span>
                    </a>
                    <a id="email" href="" rel="nofollow" target="_blank" title="deel dit artikel via e-mail">
                        <span class="fa-solid">&#xf0e0</span>
                    </a>
                </div>
            </div>
            <div style="clear:both"></div>


        </div>  <!-- .wrapper -->
    </div> <!-- #main-container -->

	<script>
        const ajaxUrl = "<?php echo BASE_URL ?>ajaxcontroller.php";
        const baseUrl = "<?php echo BASE_URL ?>";
        let loc = "<?php echo $location ?>";
        let siteName = "<?php echo SITE_NAME ?>";
        let baseTitle = "<?php echo DOMAIN_TITLE ?>";
    </script>
    
</body>
</html>