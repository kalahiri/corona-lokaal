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
$topCities = $cityModel->getTopCities();
// var_dump( $topCities ); exit();

$countryModel = new CountryModel();
$today = $countryModel->getToday();

$location = 'Nederland';
if (isset ( $_GET["location"] )) {
    // Sanitize input
    $location = filter_var( substr( $_GET["location"], 0, 35 ), FILTER_SANITIZE_STRING );
}

$cityList = $cityModel->getListOfCities();


?><!doctype html>
<html>

<head>
    <title><?php echo DOMAIN_TITLE . $location ?></title>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>assets/corona-lokaal-nederland.png">
    <meta property="og:url" content="<?php echo BASE_URL; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?php echo DOMAIN_TITLE . $location ?>" />
    <meta property="og:description" content="<?php echo DOMAIN_TITLE . $location ?>" />
    <meta property="og:image" content="<?php echo BASE_URL; ?>assets/corona-lokaal-nederland.png" />

    <link rel="shortcut icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">

    <link rel="stylesheet" href="<?php echo BASE_URL ?>assets/styles.css?v=<?php echo filemtime( 'assets/styles.css' )?>" type='text/css' media='all' />
	<script src="<?php echo BASE_URL ?>Chart.js-2.9.3/Chart.min.js"></script>
	<script src="<?php echo BASE_URL ?>assets/script.jquery.latest.min.js"></script>
	<script src="<?php echo BASE_URL ?>assets/script.js?v=<?php echo filemtime( 'assets/script.js' )?>"></script>
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

        <div id="popupAbout" class="popup">
            <div class="close fa-solid short"  onClick="$('#popupAbout').fadeToggle(200);" title="close">&#xf00d</div>
            <div class="popup-contents">
                <h3>Informatie</h3><p>
                <p>De informatie op deze site is samengesteld uit de dagelijks gepubliceerde gegevens van het <a target="_blank" href="https://data.rivm.nl/covid-19/">RIVM</a>.</p>
                <p>De lijst met 'grootste toename besmettingen' kijkt naar de procentuele gemiddelde toename over een week.
                    Als de stijging procentueel groot is, maar in absolute getallen klein, wordt die gemeente niet in de lijst opgenomen.</p>
                <p>Negatieve waarden zijn het gevolg van administratieve correcties bij het RIVM.</p>
                <p>Het project is pas net gestart, daarom kan het zijn dat er fouten voorkomen.</p>
                <p>Broncode van dit project vind je op <a target="_blank" href="https://github.com/kalahiri/corona-lokaal">github</a> en is vrij te gebruiken.</li> 
                <p>Voor vragen of tips ben ik bereikbaar via twitter: <a href="https://twitter.com/kalahiri" target="_blank">kalahiri</a>.</p>
            </div>
            <div class="popup-close-button" onClick="$('#popupAbout').fadeToggle(200);"><input type="button" value="OK"></div>
        </div>


        <!-- heading -->
        <div id="heading">
            <div class="click" title="Klik hier voor de landelijke grafiek" onClick ="rivmCharts.doAction( 'get', 'location', 'Nederland' )">
                   
            <img src="<?php echo BASE_URL ?>assets/favicon.svg">
            <h1>Corona Lokaal | <?php echo $location ?></h1>
            </div>
            <div id="about" class="fa-solid" onClick="$('#popupAbout').fadeToggle(200);">&#xf05a</div> 
        </div>
        <div class="location-title">Kies plaatsnaam: 
            <select id="selectLocation" onChange="rivmCharts.doAction( 'get', 'location', this.value );">
                    <option value="Nederland" <?php
                                if( "Nederland" == $location ) echo 'selected="selected"'; 
                            ?>>Nederland</option>
                <?php foreach( $cityList as $city ) : ?>
                    <option value="<?php echo $city["city"] ?>" <?php
                                if( $city["city"] == $location ) echo 'selected="selected"'; 
                            ?>><?php echo $city["city"] ?></option>
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
        <p class="notice today">Cijfers uit het <a href="" onClick="rivmCharts.doAction( 'get', 'location', 'Nederland' )"  title="Klik hier voor de landelijke grafiek.">landelijk overzicht</a>:</p><ul>
        <li> <?php echo $today["reported"]; ?> nieuwe besmettingen</li>
        <li> <?php echo $today["hospitalized"]; ?> nieuwe ziekenhuisopnames</li>
        <li> <?php echo $today["deceased"]; ?> nieuwe overlijdens</li>
        </ul></p>
            <p class="notice">Gemeenten met grootste stijging besmettingen per 100.000 inwoners:</p>
            <div style="width:100%">
                <div class="float-left">
                    <ul>
                    <?php $i = 0; foreach( $topCities as $key => $val ) : ?>
                        <li><a href="" onClick="rivmCharts.doAction( 'get', 'location', '<?php echo $key ?>' )"
                        title="Klik hier voor de grafiek van <?php echo $key ?>."><?php echo $key ?></a></li>
                        <?php if ( $i == 4 ) : ?>
                                        </ul>
                                    </div>
                                    <div  class="float-right">
                                        <ul>
                        <?php endif; 
                        $i++; 
                        endforeach; ?>
                    </ul>
                </div>
                <div style="clear:both;"></div>
            </div>

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