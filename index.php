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

Statistics::addVisitor();

// first check if updates are available
if ( FileManager::shouldWasteWaterBeUpdated() ) {
    FileManager::getRemoteWasteWaterFile();
}
if ( FileManager::isUpdateSourceFileAvailable() ) {
    FileManager::getRemoteFile();
//    FileManager::getRemoteCasusFile();
}

if ( FileManager::shouldNiceBeUpdated() ) {
    FileManager::getRemoteNICEFile();
}

$cityModel = new CityModel();
$topCities = $cityModel->getTopCities();
$cityList = $cityModel->getListOfCities();

$countryModel = new CountryModel();
$today = $countryModel->getToday();
$todayNICE = $countryModel->getNICEToday();

$wasteWaterModel = new WastewaterModel();
$wasteWaterModel->getSeriesSortedByLocation();
$wasteWaterModel->getPlantLocations();
$dateLastUpdateWasteWaterSourceFile = $wasteWaterModel->getDateSourceFile(); // date laste update

$location = 'Nederland';
$plant = 'Nederland';
$wasteWaterView = false;
$positiveTestsView = false;
$wasteWaterChecked = '';
$positiveTestsChecked = 'checked';

if( isset( $_GET["location"] ) ) {
    $positiveTestsView = true;
    $positiveTestsChecked = 'checked';
    $location = filter_var( substr( $_GET["location"], 0, 35 ), FILTER_SANITIZE_STRING );
}
if( isset( $_GET["plant"] ) ) {
    $positiveTestsView = false;
    $wasteWaterView = true;
    $wasteWaterChecked = 'checked';
    $plant = filter_var( substr( $_GET["plant"], 0, 35 ), FILTER_SANITIZE_STRING );
}

?><!doctype html>
<html lang="nl">

<head>
    <title><?php echo DOMAIN_TITLE . $location ?></title>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="keywords" content="actuele,interactieve, kaart,informatie,corona,gemeente,updates,grafiek,lokaal,per plaats,covid-19">
    <meta name="description" content="<?php echo DOMAIN_TITLE . $location ?>. Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta name="copyright" content="(c)2020 kalahiri">
    <meta name="publisher" content="@kalahiri">
    <meta name="robots" content="ALL">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@kalahiri">
    <meta name="twitter:title" content="<?php echo DOMAIN_TITLE . $location ?>">
    <meta name="twitter:description" content="<?php echo DOMAIN_TITLE . $location ?>. Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta name="twitter:image" content="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/corona-lokaal-nederland.jpg' ); ?>">
    <meta property="og:url" content="<?php echo BASE_URL; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?php echo DOMAIN_TITLE . $location ?>" />
    <meta property="og:description" content="<?php echo DOMAIN_TITLE . $location ?>. Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta property="og:image" content="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/corona-lokaal-nederland.jpg' ); ?>" />

    <link rel="shortcut icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo BASE_URL ?>assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" href="<?php echo BASE_URL ?>assets/apple-touch-icon.png">
    <link rel="stylesheet" href="<?php echo BASE_URL . FileUtils::addTimeStamp('assets/styles.css'); ?>" type="text/css" media="all" />


	<script src="<?php echo BASE_URL ?>assets/chart/Chart.min.js"></script>
	<script src="<?php echo BASE_URL ?>assets/script.jquery.latest.min.js"></script>
    <script src="<?php echo BASE_URL ?>assets/map/highmaps.js"></script>
    <script src="<?php echo BASE_URL ?>assets/map/exporting.js"></script>
    
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/map/map.gemeente.2020.geojson.js'); ?>"></script>
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/script.js' ); ?>"></script>
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.waste-water-plant-locations.js' ); ?>"></script>
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.map-values-per-city.js' ); ?>"></script>

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
        <div id="popupWasteWater" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
                <div class="popup-contents">
                    <h3>Rioolwatermetingen</h3>
                    <p>Op de kaart kan je de rioolwatermetingen van het RIVM zichtbaar maken. Als je dat
                        onder de kaart aanvinkt, verschijnt er ook een extra grafiek met de waarden van de 
                        rioolwatermetingen. Door op een cirkeltje op de kaart te klikken, worden de 
                        meetwaarden van dat punt in de grafiek weergegeven. Er worden op dit moment metingen
                        gedaan bij <span id="numberWasteWaterLocations"></span> rioolwaterzuiveringen.</p>
                    <p>De meting geeft het gemiddeld aantal virusdeeltjes (RNA) per milliliter over 24 uur
                        weer. Een groot deel van de besmette mensen heeft ook virusdeeltjes in de ontlasting.
                        Het aantal virusdeeltjes in het rioolwater is daarom een maat voor het aantal 
                        besmette personen in de regio van de rioolwaterzuivering. Het is de bedoeling dat met
                        de rioolwatermetingen de trend eerder zichtbaar wordt dan met de testen.</p>
                    <p>In het voorjaar van 2020 zijn waarden van 4000 tot ruim 9000 RNA per mL geconstateerd. 
                        Na de lockdown zijn ook al weer waardes boven de 2000 aangetroffen.</p>
                    <p>De kleur van de locatie is een maat voor het aantal virusdeeltjes. De kleur 
                        verloopt afhankelijk van de meting van wit ( nul virus deeljtes ) via geel en oranje 
                        naar rood. Vanaf 1760 RNA per mL is het maximale rood bereikt. De schaal van de kleuren
                        kan nog worden aangepast als er meer metingen komen en we meer weten wat de 
                        betekenis van die metingen in de praktijk is.</p>   
                    <p>De rioolwatermetingen worden eens per week op dinsdag door het RIVM geüpdatet.</p>
                </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupAbout" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Informatie</h3>
                <p>Op deze website vind je de actuele informatie over de verspreiding van het virus per gemeente
                    in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks
                    gepubliceerde gegevens van het <a target="_blank" href="https://data.rivm.nl/covid-19/">RIVM</a>.</p>
                <p>Voeg deze pagina toe aan je favorieten met de toetsencombinatie 'CTRL-D' of sleep de pagina 
                    met je muis naar je favorietenmap.</p>
                <p>Op de kaart kan je de rioolwatermetingen van het RIVM zichtbaar maken door dat onder de kaart 
                    aan te vinken. De rioolwatermetingen worden eens per week op dinsdag door het RIVM geupdatet.</p>
                <p>In de grafieken worden soms negatieve waarden weergegeven. Die zijn het gevolg van
                    administratieve correcties bij het RIVM.</p>
                <p>Broncode van dit project vind je op <a target="_blank" href="https://github.com/kalahiri/corona-lokaal">
                    github</a> en is vrij te gebruiken. Alle afbeeldingen op deze website zijn vrij te gebruiken
                    zonder dat bronvermelding noodzakelijk is. De gegevens zijn van het RIVM.</p>
                <p>Voor vragen of opmerkingen ben ik bereikbaar via e-mail: <a href="mailto:reinout@corona-lokaal.nl">
                    reinout@corona-lokaal.nl</a> of via twitter <a href="https://twitter.com/kalahiri" target="_blank">
                    @kalahiri</a>.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupFavorite" class="popup">
        <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Favorieten</h3>
                    <p>Voeg deze pagina toe aan je favorieten met de toetsencombinatie 'CTRL-D' of sleep de pagina met je muis
                        naar je favorietenmap.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>

        <!-- heading -->
        <div id="heading">
            <div class="click" title="Klik hier voor de landelijke grafiek" onClick ="rivmCharts.resetCharts();">
                   
            <img alt="logo" src="<?php echo BASE_URL ?>assets/favicon.svg">
            <h1>Corona Lokaal | <?php echo $location ?></h1>
            </div>
            <div id="menu">
                <span id="favo" class="fa-solid" onClick="rivmCharts.popupToggle( 'popupFavorite' );">&#xf005;</span>
                <span id="about" class="fa-solid" onClick="rivmCharts.popupToggle( 'popupAbout' );">&#xf05a;</span> 
            </div>
        </div>
        <div class="location-title">Kies plaatsnaam: 
            <select id="selectLocation" onChange="rivmCharts.doAction( 'get', 'location', this.value );">
                    <option value="Nederland"<?php
                                if( "Nederland" == $location ) echo ' selected="selected"'; 
                            ?>>Nederland</option>
                <?php foreach( $cityList as $city ) : 
                    ?><option value="<?php echo $city["city"] ?>"<?php
                                if( $city["city"] == $location ) echo ' selected="selected"'; 
                            ?>><?php echo $city["city"] ?></option><?php
                endforeach; ?>
            </select>
        </div>
        <div class="location-title" id="title"></div>
        <div style="clear:both;"></div>

        <!-- Charts -->
        <div class="column-wrapper float-left" id="charts-container-wrapper">
            <div class="canvas-container" id="container-waste-water">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e;</div>
                <canvas id="canvas-waste-water"></canvas>
            </div>
            <div class="canvas-container">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e;</div>
                <canvas id="canvas-reported"></canvas>
            </div>
            <div class="canvas-container">
                <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e;</div>
                <canvas id="canvas-hospitalized"></canvas>
            </div>
            <div class="canvas-container">
                    <div class="fa-solid zoom" title="zoom in" onClick="rivmCharts.zoom();">&#xf00e;</div>
                    <canvas id="canvas-deceased"></canvas>
            </div>
        </div>

        <div class="column-wrapper float-right"> 
            <!-- Map -->
            <div id="map-container"></div>
            <div id="map-subcontainer">
                <label for="chkbx-positive-tests">
                    <input type="checkbox" id="chkbx-positive-tests" <?php
                        echo $positiveTestsChecked;  
                        ?> onclick='rivmCharts.mapOptionsClicked( "positive-tests" );'>positieve testen
                </label>
                <label for="chkbx-waste-water">
                    <input type="checkbox" id="chkbx-waste-water" <?php
                        echo $wasteWaterChecked; 
                        ?> onclick='rivmCharts.mapOptionsClicked( "waste-water" );'>
                        rioolwatermetingen van <?php echo $dateLastUpdateWasteWaterSourceFile; ?>
                </label>
                <span class="fa-solid info-sup" onClick="rivmCharts.popupToggle('popupWasteWater');">
                    <sup>&#xf05a;</sup>
                </span>
            </div>

            <!-- info container -->
            <div class="info-container">
                <p class="notice today">Cijfers uit het 
                    <a href="" onClick="rivmCharts.doAction( 'get', 'location', 'Nederland' )"  
                        title="Klik hier voor de landelijke grafiek.">landelijk overzicht</a> van <?php
                        echo date( "j/n", $today["today"] ); ?>:</p>
                    <div class="divTable">
                        <div class="divTableRow">
                            <div class="divTableCell tableCellLeft">
                                <ul>
                                    <li><?php echo $today["reported"]; ?> nieuwe besmettingen</li>
                                    <li><?php echo $today["hospitalized"]; ?> nieuwe ziekenhuisopnames</li>
                                </ul>
                            </div>
                            <div class="divTableCell tableCellRight">
                                <ul><?php if ( is_array( $todayNICE ) && $todayNICE["icuIntake"] !== false
                                            && $today["today"] == $todayNICE["today"] ) : ?>
                                    <li><?php echo $todayNICE["icuIntake"] ?> nieuwe IC-opnames</li>
                                            <?php endif; ?>
                                    <li><?php echo $today["deceased"]; ?> nieuwe overlijdens</li>
                                </ul>
                            </div>
                        </div>
                    </div>



                <p class="notice">Gemeenten met hoogste aantal nieuw gemelde besmettingen per 100.000 inwoners in de afgelopen zeven dagen:</p>
                <table class="info">
                    <tr>
                        <td class="left">
                            <ul>
                                <?php $i = 0; foreach( $topCities as $key => $val ) : ?>
                                <li><a href="" onClick="rivmCharts.doAction( 'get', 'location', '<?php echo $key ?>' )"
                                    title="<?php echo $key ?> had afgelopen week <?php echo $val ?> nieuw&#010;gemelde besmettingen per 100.000 inwoners.&#010;Klik voor de grafiek."><?php echo $key ?></a></li>
                                <?php if ( $i == 4 ) : ?>
                            </ul>
                        </td>
                        <td>
                            <ul>
                                <?php endif; 
                                $i++; 
                                endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                </table>





                <div id="share-container">Deel via: 
                    <a id="twitter" href="" rel="nofollow" target="_blank" title="deel dit artikel via twitter">
                        <span class="fa-brands">&#xf099;</span>
                    </a>
                    <a  id="facebook" href="" rel="nofollow" target="_blank" title="deel dit artikel via facebook">
                        <span class="fa-brands">&#xf09a;</span>
                    </a>
                    <a id="linkedin" href="" rel="nofollow" target="_blank" title="deel dit artikel via linkedin">
                        <span class="fa-brands">&#xf08c;</span>
                    </a>
                    <a id="email" href="" rel="nofollow" target="_blank" title="deel dit artikel via e-mail">
                        <span class="fa-solid">&#xf0e0;</span>
                    </a>
                </div>
            </div> <!-- .info-container -->


        </div>
        <div style="clear:both"></div>

    </div> <!-- #main-container -->

    <script>
        const ajaxUrl = "<?php echo BASE_URL ?>ajaxcontroller.php";
        const baseUrl = "<?php echo BASE_URL ?>";
        let loc = "<?php echo $location ?>";
        let plant = "<?php echo $plant ?>";
        let today = "<?php echo date( "j/n/y", $today["today"] ); ?>";
        const todayFileFormat = "<?php echo date( "d-m-Y", $today["today"] ); ?>";
        let siteName = "<?php echo SITE_NAME ?>";
        let baseTitle = "<?php echo DOMAIN_TITLE ?>";
        let positiveTestView = <?php echo ( $positiveTestsView ) ? 'true' : 'false'; ?>;
        let wasteWaterView = <?php echo ( $wasteWaterView ) ? 'true' : 'false'; ?>;
    </script>

</body>
</html>