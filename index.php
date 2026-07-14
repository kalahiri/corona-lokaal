<?php
/**
 * This file is written by Kalahiri in 2020-2022 
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


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL); 







// set script versions
$version = '2.5.3';
$versionCss = '2.4.2';


$minify = JavascriptMinifier::MINIFIED;
//$minify = JavascriptMinifier::ORIGINAL_SOURCE_CODE;








Statistics::addVisitor();

$updating = FileUtils::readCookie( 'updating' );
if ( $updating !== false && $updating > 0 ) {
    $sleep = ( $updating == 1 ) ? '9' : '5'; 
    echo '<!doctype html><html lang="nl"><head>
    <title>' . DOMAIN_TITLE . '</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <link rel="shortcut icon" href="' . BASE_URL . 'assets/favicon.ico" type="image/x-icon">
    <link rel="icon" href="' . BASE_URL . 'assets/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="' . BASE_URL . 'assets/apple-touch-icon.png">
    <link rel="apple-touch-icon-precomposed" href="' . BASE_URL . 'assets/apple-touch-icon.png">
    <meta http-equiv="refresh" content="' . $sleep . '">
    </head><body><p>Een ogenblik graag. De gegevens worden nu geüpdatet...</p>
    <p>De pagina laadt automatisch over enkele momenten.</p>
    </body></html>';
    exit();
}

/*
$mobileDetect = new MobileDetect();
$isMobile = $mobileDetect->isMobile();
// TESTING
$isMobile = true;
*/

/* *** OUD 
$cityModel = new CityModel();
$topCities = $cityModel->getTopCities();
$cityList = $cityModel->getListOfCities();
$citymap = $cityModel->getMap();
$citymapVaccinatedUpdateDate = $cityModel->getMapVaccinatedPerCity();
// */

$reportDate = new ReportDate();
$today = $reportDate->getReportDate();


$countryModel = new CountryModel();
$countryModel->setToday( $today );
// $data = $countryModel->getChartData( true, false, true );
// var_dump( $data[0] ); exit();


/** DEPRECATED:  $today = $countryModel->getToday(); */
$todayUnused = $countryModel->getToday();





$todayTotalsPerDay = $countryModel->getCountTodayFromTotalsPerDay();
/** DEPRECATED:  $todayLCPS = $countryModel->getLCPSHospitalCountToday(); */


$lcpsModel = new LcpsModel( $today );
$todayLCPS = $lcpsModel->getLCPSHospitalCountToday();


// $todayNICE = $countryModel->getNICEToday();
// $todayVaccinated = $countryModel->getVaccinated();


$wasteWaterModel = new WastewaterModel();
// $wasteWaterModel->getSeriesSortedByLocation();
// $wasteWaterModel->getPlantLocations();
//$test = $wasteWaterModel->getChartDataTotals( false );var_dump( $test );exit();
$dateLastUpdateWasteWaterSourceFile = $wasteWaterModel->getDateSourceFile(); // date laste update
$todayTotalsPerDay["wastewater"] = $wasteWaterModel->getLatestValue();



$storing = $countryModel->getStoring();
$storingSolved = $countryModel->getStoringSolved();


$infectionRadarModel = new InfectionRadarModel( );
//$seriesInfectionRadar = $infectionRadarModel->getChartDataPositiveTests();
$todayTotalsPerDay["infectionRadarPositiveTests"] = $infectionRadarModel->getLatestValue();

//$seriesInfectionRadar = $infectionRadarModel->getChartDataPositiveTests();
//var_dump( $seriesInfectionRadar );
//exit();
//var_dump( $todayTotalsPerDay );
//exit();


$location = 'Nederland';
$plant = 'Nederland';
$wasteWaterView = true;
$positiveTestsView = false;
$infectionRadarPositiveTestsView = true;

$wasteWaterChecked = 'checked';
$wasteWaterBubblesChecked = '';
$positiveTestsChecked = '';
$vaccinatedPerCityChecked = '';
$hospitalizedPerCityChecked = '';


// var_dump( $_GET ); exit();



if( isset( $_GET["location"] ) ) {
    //$positiveTestsView = true;
    //$positiveTestsChecked = 'checked';
    $location = filter_var( substr( $_GET["location"], 0, 35 ), FILTER_SANITIZE_STRING );
    if( 'Nederland' !== $location ) {
        $infectionRadarPositiveTestsView = false;
    }
}
if( isset( $_GET["plant"] ) ) {
    $positiveTestsView = false;
    $wasteWaterView = true;
    $wasteWaterChecked = 'checked';
    $plant = filter_var( substr( $_GET["plant"], 0, 35 ), FILTER_SANITIZE_STRING );
}

function formatNumber( $value, $decimals = 0 ) {
    return number_format( $value, $decimals, ',', '.' );
}

$scriptsModules = 'assets/script/modules/';


$jsMinifier = new JavascriptMinifier( $version );
$jsMinifier->addScriptFile( 'chartjs-plugin-annotation.0.5.7.edit.kalahiri.0.2.js', 'assets/chart/' );
$jsMinifier->addScriptFile( 'module.namespaces.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.interaction-utils.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.display-utils.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.data-utils.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.charts.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.maps.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.texts.' . $version . '.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.main.' . $version . '.js', $scriptsModules );
//$jsMinifier->addScriptFile( $scripts[ $version ]['myscript'] );
$jsMinifier->setOutputScriptFile( 'assets/script/script.' . $version . '.js' );
$jsMinifier->createOutputfile();





/*
$jsMinifier = new JavascriptMinifier( $version );
$jsMinifier->addScriptFile( 'module.namespaces.2.1.0.js', $scriptsModules );
// $jsMinifier->addScriptFile( 'module.interaction-utils.2.1.0.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.display-utils.2.1.0.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.data-utils.2.1.0.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.charts.2.1.0.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.maps.2.1.0.js', $scriptsModules );
$jsMinifier->addScriptFile( 'module.main.2.1.0.js', $scriptsModules );
//$jsMinifier->addScriptFile( $scripts[ $version ]['myscript'] );
$jsMinifier->setOutputScriptFile( 'assets/script/script.2.1.0.js' );
$jsMinifier->createOutputfile();
*/

?><!doctype html>
<html lang="nl">

<head>
    <title><?php echo DOMAIN_TITLE . $location ?></title>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="keywords" content="actuele,interactieve, kaart,informatie,corona,gemeente,updates,grafiek,lokaal,per plaats,covid-19">
    <meta name="description" content="<?php echo DOMAIN_TITLE . $location ?>. Actuele, interactieve kaart met gegevens over de verspreiding van het virus per gemeente in Nederland. De informatie wordt dagelijks geactualiseerd en is samengesteld uit de dagelijks gepubliceerde gegevens van het RIVM">
    <meta name="copyright" content="(c)2020-<?php echo date( "Y" ) ; ?> kalahiri">
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
    <link rel="stylesheet" href="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/css/styles.' . $versionCss . '.css' ); ?>" type="text/css" media="all" />


    <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/map/highmaps.js' ); ?>"></script>
	<!-- <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/map/map.gemeente.2022.geojson.js' ); ?>"></script> -->
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/map/map.provincie.2022.geojson.js' ); ?>"></script>
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.waste-water-plant-locations.js' ); ?>"></script>
	<!-- <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.map-values-per-city.js' ); ?>"></script> -->
    <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.waste-water-map-values-per-province.js' ); ?>"></script>
    <!-- <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.nice.hospitaladmission-map-values-per-province.js' ); ?>"></script> -->
	<script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.list-of-cities.js' ); ?>"></script>


    <script src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/chart/chart.2.9.4.edit.kalahiri.0.2.min.js' ); ?>"></script>
    <?php $jsMinifier->getOutputScripts( $minify ); ?>

	<style>
        @font-face {
            font-family: "Exo2";
            src: url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.woff") format("woff"),
                 url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.woff2") format("woff2"),
                 url("<?php echo BASE_URL ?>assets/fonts/Exo2-ExtraBold.ttf");
                 font-display:fallback
        }
        @font-face {
            font-family: "Font Awesome Solid";
            src: url("<?php echo BASE_URL ?>assets/fonts/Font_Awesome_5.8_Free_Solid.woff") format("woff");
            font-display:block
        }
        @font-face {
            font-family: "Font Awesome Brands";
            src: url("<?php echo BASE_URL ?>assets/fonts/Font_Awesome_5.8_Brands_Regular.woff") format("woff");
            font-display:block
        }
        #popupNotice .popup-contents { padding: 3em }
        #popupNotice .popup-contents p { margin-bottom: 1em }
    </style>
</head>

<body>
    <div id="main-container">
        
        <!-- info boxes -->
        <div id="popupNotice" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
            <?php $showNotice = true; ?>
                <h3>Einde website</h3>
                <p>Deze website stopt op 19 juli 2026. De uitbraken van het virus zijn een stuk kleiner en uit de bezoekersaantallen blijkt dat er nog maar weinig belangstelling is voor de corona-cijfers. </p>
                <p>Tegelijkertijd is de rapportage van de cijfers bij het RIVM duidelijk verbeterd. Bezoekers van deze site kunnen daarom in het vervolg goed terecht bij <a href="https://www.rivm.nl/rioolwateronderzoek/covid-19" target="_blank">de overzichten</a> van het RIVM.
                </p>
                <p>Dank voor de aandacht en de positieve feedback van de afgelopen jaren.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupSurveillanceMode" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Surveillance Mode</h3>
                <p>Omdat het testbeleid is aangepast en testen bij de GGD niet meer nodig is, 
                    moeten we teruggrijpen op 'achtergrond-metingen'
                     om de verspreiding van het virus in de gaten te houden, 
                     bijvoorbeeld door het aantal virusdeeltjes in het rioolwater te meten.</p>
                <p>In jargon noemt men dit 'surveillance', dus gaat deze site nu ook in 
                    <i>surveillance mode</i>.</p>
                <p>Hopelijk hebben we in de toekomst geen last meer van dit coronavirus, 
                    maar als zich een nieuwe golf aandient, hopen we dat met deze <i>surveillance mode</i> 
                    op tijd te ontdekken.</p>

            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupChartInfectionRadarPositiveTests" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Percentage positieve testen</h3>
                <p> 
                Een groot aantal mensen in het land helpt het RIVM door te melden als ze luchtwegklachten hebben.
                Zo kan het RIVM de verspreiding van luchtweginfecties, waaronder het coronavirus, in de gaten houden.
                Het RIVM <a href="https://www.infectieradar.nl/welcome" target="_blank">zoekt</a> nog meer vrijwilligers.
                </p>

                <p><b>Positieve coronatesten</b><br>
                    Eén van de onderdelen van deze zogeheten 'infectieradar' is dat deelnemers een corona-zelftest doen als ze
                    luchtwegklachten hebben. Het resultaat daarvan delen ze met het RIVM. Het RIVM bepaalt vervolgens wekelijks 
                    het percentage coronatesten met een positieve uitslag.
                </p><p>
                    De geschiedenis laat zien dat dit een goede indicator is van de mate waarin het virus door het land gaat.  
                    Om die reden is de grafiek van het percentage positieve testen sinds begin augustus 2024 op deze site opgenomen.
                </p><p><b>Grafiek</b><br>
                    Het percentage positieve testen wordt alleen voor heel Nederland bepaald.
                    Er is daarom geen grafiek per provincie beschikbaar.
                </p><p>
                    Het RIVM <a href="https://www.infectieradar.nl/results" target="_blank">publiceert</a> elke week op woensdag
                    het wekelijks gemiddelde van het aantal positieve testen van 
                    de week daarvoor (maandag tot en met zondag). Er wordt per week dus maar één cijfer gepubliceerd, maar voor de grafiek
                    wordt per dag het logische verloop uitgerekend van het ene weekcijfer naar het volgende weekcijfer.
                    Daardoor ontstaat een duidelijk beeld hoe de epidemie verloopt en is het goed te vergelijken met de grafiek 
                    van de rioolwatermetingen.    
                </p><p>
                    De dagen met de daadwerkelijk gepubliceerde cijfers hebben een donkerder staafje in de grafiek. 
                    De lichtere balkjes betekenen dat die dagen rekenkundig door deze website zijn uitgerekend.
                </p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupChartHospitalized" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Ziekenhuisopnames</h3>
                <p>De gegevens over ziekenhuis&shy;opnames komen van de 
                    ziekenhuizen zelf via twee verschillende bronnen.
                    De ziekenhuisgegevens per provincie komen van 
                    <a href="https://www.stichting-nice.nl" target="_blank">Stichting NICE</a>. 
                    De landelijke cijfers komen van het <a href="https://lcps.nu" target="_blank">LCPS</a>.</p>
                    <p>De grafiek per provincie geeft het aantal ziekenhuis&shy;opnames
                    weer per dag dat de ziekenhuis&shy;opname daadwerkelijk plaatsvond.
                    De ziekenhuisopnames van de laatste paar dagen zijn nog niet
                    volledig en worden in de dagen daarna nog aangevuld. De grafiek voor heel Nederland van het LCPS is
                    wel direct compleet. De grafieken worden op werkdagen bijgewerkt.</p>
                <p>Het is me nog niet duidelijk of de twee ziekenhuizen 
                    (het Flevo&shy;ziekenhuis in Almere en de Tergooi&shy;ziekenhuizen in Hilversum) 
                    inmiddels wel meedoen met de raportage via NICE. Eerder leverden ze 
                    om technische redenen geen gegevens aan. Het kan een verklaring zijn waarom in die regio's
                    de grafiek met ziekenhuisopnames lager kan uitvallen dan verwacht mag worden.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupWasteWater" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Rioolwatermetingen</h3>
                <p>Op de kaart kan je de riool&shy;water&shy;metingen per rioolwaterzuivering zichtbaar maken. Als je dat
                    onder de kaart aanvinkt, verschijnen er extra cirkeltjes in de kaart. De cirkeltjes geven 
                    de locatie van de rioolwaterzuiveringen aan. Door op een cirkeltje
                    te klikken, worden de meet&shy;waarden van dat rioolwaterstation in de grafiek weer&shy;gegeven. 
                    Er worden op dit moment metingen
                    gedaan bij <span id="numberWasteWaterLocations"></span> rioolwater&shy;zuiveringen.</p>
                <p>De meting geeft het gemiddeld aantal virusdeeltjes (RNA) per 100.000 inwoners
                    weer. Een groot deel van de besmette mensen heeft ook virus&shy;deeltjes in de ontlasting.
                    Het aantal virus&shy;deeltjes in het rioolwater is daarom een maat voor het aantal 
                    besmette personen in de regio van de riool&shy;water&shy;zuivering. 
                    <p><b>Schommelingen</b><br>
                    In de praktijk blijken de riool&shy;water&shy;metingen nogal wispelturig. Daarom is het lastig 
                    om per rioolwaterzuivering een uitspraak te doen over het aantal besmettingen. 
                    Maar als je metingen van meerdere rioolwaterstations samenneemt, 
                middelen de schommelingen zich uit en ontstaat er een scherper beeld. Door de 
                rioolwatermetingen zowel landelijk als per provincie te berekenen, houden we 
                ook zicht op de regionale verspreiding van het virus.</p>
                
                <p><b>Niveaus</b><br>
                    De meetwaarden van het RIVM zijn voor de grafiek allemaal door 
                    100.000.000.000 gedeeld om de grafiek leesbaar te houden. 
                    Het RIVM heeft voor de gevonden waarden sinds Omikron geen update van de risico&shy;niveaus gepubliceerd.
                    De praktijk leert dat waardes boven twee- à drieduizend zeer hoog zijn. 
                    De komende tijd kan de schaal mogelijk nog worden aangepast als we meer
                    leren over de betekenis van de rioolwaterwaarden.</p>
                <p>De kleur van een cirkeltje op de kaart is een maat voor het aantal
                    virus&shy;deeltjes op die locatie. De kleuren&shy;categorieën die worden gebruikt 
                    zijn dezelfde als die bij de positieve testen worden gebruikt
                    om de ernst van de gevonden waarde weer te geven. De kleuren 
                    zijn als volgt ingedeeld. (wit) 0,
                    <span class="dot" style="background-color:#fdf5c8"></span> 0 tot 100,
                    <span class="dot" style="background-color:#fed066"></span> 100 tot 400,
                    <span class="dot" style="background-color:#e58d35"></span> 400 tot 800, 
                    <span class="dot" style="background-color:#cc3d19"></span> 800 tot 1300, 
                    <span class="dot" style="background-color:#a11a14"></span> 1300 tot 1800, 
                    <span class="dot" style="background-color:#580000"></span> 2400 tot 3000, 
                    <span class="dot" style="background-color:#000000"></span> hoger dan 3000.
                    <!--
                    <span class="dot" style="background-color:#fdf5c8"></span> 0 tot 60,
                    <span class="dot" style="background-color:#fed066"></span> 60 tot 150,
                    <span class="dot" style="background-color:#e58d35"></span> 150 tot 250, 
                    <span class="dot" style="background-color:#cc3d19"></span> 250 tot 500, 
                    <span class="dot" style="background-color:#a11a14"></span> 500 tot 750, 
                    <span class="dot" style="background-color:#580000"></span> 750 tot 1000, 
                    <span class="dot" style="background-color:#000000"></span> hoger dan 1000.
                    -->
                </p>
                <p><b>Betrouwbaarheid</b><br>
                    Er wordt eens per week op 133 van de ruim 300 afvalwaterzuiveringen een nieuwe meting gedaan.
                    Dat betekent dat ca. 40% van Nederland gemeten wordt. De rioolwatermetingen zijn hiermee minder betrouwbaar geworden, 
                    maar die 133 wekelijkse metingen zijn waarschijnlijk voldoende als vroegsignalering voor een mogelijke coronagolf.

                    Niet alle meetstations voeren hun metingen op dezelfde dag uit. Daarom kunnen de meest recente dagen
                    later nog flink wijzigen als meer metingen door het RIVM worden toegevoegd. Als je met de muis over
                    de laatste dagen in de grafiek gaat, zie je de betrouwbaarheid van de waarde voor die dag.
                    Als de betrouwbaarheid voor een dag minder dan 60% is, wordt die dag (nog) niet 
                    in de grafiek getoond.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupAbout" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Informatie</h3>
                <p>Op deze website vind je de actuele informatie over de verspreiding van het virus per provincie
                    in Nederland. De informatie wordt eens per week op woensdagen geactualiseerd en is samen&shy;gesteld uit de 
                    gepubliceerde gegevens van het <a target="_blank" href="https://data.rivm.nl/covid-19/">RIVM</a>.</p>
                    <p>Je kan de grafieken en de kaart in- en uitzoomen door met je muiswieltje te draaien. 
                        Door je linkermuistoets ingedrukt te houden, kan je de grafieken en de kaart verschuiven.</p>
                <p>Op de kaart kan je de rioolwater&shy;metingen van het RIVM zichtbaar maken door dat onder de kaart 
                    aan te vinken. </p>
                <p>Alle afbeeldingen op deze website zijn vrij te gebruiken
                    zonder dat bron&shy;vermelding noodzakelijk is. De gegevens zijn van het RIVM, LCPS en stichting NICE.</p>
                <p>Voor vragen of opmerkingen ben ik bereikbaar via e-mail: <a href="mailto:*****@corona-lokaal.nl">
                    *******@corona-lokaal.nl</a>.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupFavorite" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <h3>Favorieten</h3>
                    <p>Voeg deze pagina toe aan je favorieten met de toetsen&shy;combinatie 'CTRL-D' of sleep de pagina met je muis
                        naar je favorietenmap.</p>
            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>
        <div id="popupEnlargedImage" class="popup">
            <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">

            </div>
            <div class="popup-close-button click-to-close"><input type="button" value="OK"></div>
        </div>


        <div id="popupChartSettings" class="popup popup-menu">
        <div class="close fa-solid short click-to-close" title="close">&#xf00d;</div>
            <div class="popup-contents">
                <a id="label-chart-setting-zoom-days"  class="menu-item" href="">Aantal dagen x-as: <input type="text" id="chart-setting-zoom-days" value=""></a>
                <a id="total-chart-button" class="menu-item total-chart" href="" title="gehele grafiek">Toon gehele grafiek</a>
                <a id="chart-annotations-button" class="menu-item chart-annotationst" href="" title="toon wel of geen labels in de grafiek">Labels in grafiek</a>
                <a id="save-button" class="menu-item" href="">Download png image</a>
            </div>
            <!--  <div id="submit-chart-settings" class="popup-close-button click-to-close"><input type="button" value="OK"></div> -->
        </div>


        <div id="popup-chart-help" class="popup-short">
            Gebruik muiswiel om te zoomen.<br>
            Gebruik linkermuisknop om te schuiven. 
        </div>
        <div id="popup-chart-help-mobile" class="popup-short">
            Sleep grafiek om te schuiven. 
        </div>



        <!-- heading -->
        <div id="heading">
            <div class="click" id="logo-click" title="Klik hier voor de landelijke grafiek">
                <img alt="logo" src="<?php echo BASE_URL ?>assets/favicon.svg">
                <h1>Corona Lokaal | <?php echo $location ?></h1>
            </div>
            <div id="menu">
                <span id="wiki-vaccins" class="fa-solid" title="Bekijk wat de Amerikaanse toezichthouder over de vaccins zegt." onClick="window.open('<?php echo BASE_URL ?>/wiki/doku.php/vaccins', 'vaccins');">&#xf48e;</span> 
                <span id="wiki" class="icon" title="Bekijk de uitgebreide uitleg over deze site." onClick="window.open('<?php echo BASE_URL ?>/wiki', '_blank');"><img src="<?php echo BASE_URL ?>assets/manual.svg" alt=""></span>
                <span id="about" class="fa-solid" title="Algemene informatie over deze site" onClick="window.CL.display.popupToggle( 'popupAbout' );">&#xf05a;</span> 
            </div>
        </div>
                        <div id="subheading">
                            <p id="latest-update-date">bijgewerkt tot en met <?php echo date( "j/n", $today[ "today" ] ); ?></p>
                            <p>surveillance mode
                                <span class="span-relative"><span class="fa-solid info-click subheading-fa-info-character" 
                                        title="klik voor een toelichting over de surveillance mode"
                                        onClick="window.CL.display.popupToggle('popupSurveillanceMode');">
                                        &#xf05a;
                                    </span></span>
                            </p>
                        </div>



                        

        <!-- city name search box -->
        <div class="hide">
            <div class="location-search-container">Typ plaatsnaam: 
                <div id="location-search">
                        <input id="location-search-input">
                        <div id="location-search-list"><ul></ul></div>
                </div>
            </div>
            <div class="location-title" id="title"></div>
            <div style="clear:both"></div>
        </div>





        <!-- Charts -->
        <div class="column-wrapper float-left" id="charts-container-wrapper">
            <div class="canvas-container slide-up-down" id="container-waste-water">
                <div class="zoom-container">
                    <span class="fa-solid zoom" title="zoom in">&#xf00e;</span>
                    <!-- <span class="close fa-solid short" id="close-chart" title="close">&#xf00d;</span> -->
                    <!-- <span class="fa-solid zoom" title="zoom uit">&#xf00e;</span> -->
                    <span class="fa-solid chart-settings" title="menu">
                        <svg version="1.1" class="highcharts-root" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                            <g class="highcharts-button highcharts-contextbutton" stroke-linecap="round" transform="translate(0,0)">
                                <title>Chart context menu</title>
                                <path fill="#888888" d="M 6 6.5 L 20 6.5 M 6 11.5 L 20 11.5 M 6 16.5 L 20 16.5" class="highcharts-button-symbol" data-z-index="1" stroke="#888888" stroke-width="3"></path>
                                <text x="0" data-z-index="1" y="12" style="color:#333333;cursor:pointer;font-weight:normal;fill:#333333;"></text>
                            </g>
                        </svg>
                    </span>&nbsp;
                   <!-- <span class="fa-solid chart-menu" title="menu">&#xf0c9;</span>&nbsp; -->
                    <!--  <span class="fa-solid chart-settings" title="grafiek instellingen">&#xf013;</span>&nbsp; -->
                    <!-- <span class="fa-solid chart-annotations" title="Laat gebeurtenissen in de grafiek zien">&#xf05a;</span>&nbsp; -->

                </div>
                <canvas id="canvas-waste-water"></canvas>
            </div>

            <div class="canvas-container hide" id="canvas-container-reported">
                <!-- <div id="click-assist" style="position:absolute;z-index:100;right:38px;top:20px">
                    <img src="<?php echo BASE_URL ?>assets/klik_dit_eens_aan_5.png" style="width:70px;height:auto;" alt="">
                </div> -->
                <div class="zoom-container">
                    <!-- <span class="fa-solid chart-reset" title="reset grafieken">&#xf2ea;</span>-->
                </div>
                <canvas id="canvas-reported"></canvas>
            </div>
            <div class="canvas-container" id="container-infection-radar-positive-tests">
                <div class="zoom-container">
                    <span class="fa-solid zoom" title="zoom in">&#xf00e;</span>
                </div>
                <canvas id="canvas-infection-radar-positive-tests"></canvas>
            </div>
            <div class="canvas-container slide-up-down-hide" id="container-hospitalized" style="visibility:hidden;max-height:0px">
                <div class="zoom-container">
                    <span class="fa-solid zoom" title="zoom in">&#xf00e;</span>
                </div>
                <canvas id="canvas-hospitalized"></canvas>
                <div class="canvas-text" id="canvas-hospitalized-text"></div>
            </div>
            <div class="canvas-container hide">
                <div class="zoom-container">
                    <span class="fa-solid zoom" title="zoom in">&#xf00e;</span>
                </div>
                <canvas id="canvas-deceased"></canvas>
            </div>


            
             <!-- Vaccins -->
             <div id="vaccinations-container" class="vaccins hide">
                <p>Update vaccinaties</p>
                <ul>
                    <li> </li>
                    <li></li>
                </ul>
            </div>
            
 
        
            <div id="qena-container" class="info-container brown"> 
                <h3>Veel gestelde vragen over de rioolwatermetingen</h3>
                <p>Er zijn verschillende vragen over de rioolwatermetingen die vaak terugkomen. Een aantal heb ik voorgelegd aan 
                    het RIVM en verwerkt in onderstaande vragen en antwoorden.
                </p> 
                <h4 id="qena-question-1" class="qena-question click-text-container">Hoe betrouwbaar of nauwkeurig zijn
                             de metingen? &nbsp;<span class="fa-solid">&#xf13a;</span></h4>
                <div id="qena-answer-1" class= "qena-answer hide"></div>

                <h4 id="qena-question-4" class="qena-question click-text-container">Kan je de rioolwatermetingen
                    omrekenen naar het aantal besmette personen? &nbsp;<span class="fa-solid">&#xf13a;</span></h4>
                <div id="qena-answer-4" class= "qena-answer hide"></div>

                <h4 id="qena-question-2" class="qena-question click-text-container">Waarom lijken de metingen 
                    zo achter te lopen? &nbsp;<span class="fa-solid">&#xf13a;</span></h4>
                <div id="qena-answer-2" class= "qena-answer hide"></div>
            <!--
                <h4 id="qena-question-3" class="qena-question click-text-container">Wat is er in Woerden
                    aan de hand? &nbsp;<span class="fa-solid">&#xf13a;</span></h4>
                <div id="qena-answer-3" class= "qena-answer hide"></div>
            -->


            </div><!-- #qena-text-container -->

        </div><!-- .column-wrapper .float-left  #charts-container-wrapper -->




        <div id="column-wrapper-right" class="column-wrapper float-right"> 
            <!-- Map -->
            <div id="map-container-vaccinated-per-city" class="map-container hide"></div>
            <div id="map-container-hospitalized-per-city" class="map-container hide"></div>
            <div id="map-container-waste-water-per-province" class="map-container"></div>
            <div id="map-container-positive-tests" class="map-container hide"></div>

            <div id="map-subcontainer">
                <label for="chkbx-vaccinated-per-city" class="hide">
                    <input type="checkbox" id="chkbx-vaccinated-per-city" <?php
                        echo $vaccinatedPerCityChecked; ?>>vaccinatie
                </label>
                <label for="chkbx-positive-tests" class="hide">
                    <input type="checkbox" id="chkbx-positive-tests" <?php
                        echo $positiveTestsChecked; ?>>testen
                </label>
                
                <label for="chkbx-hospitalized-per-city" class="hide">
                    <input type="checkbox" id="chkbx-hospitalized-per-city" <?php
                        echo $hospitalizedPerCityChecked; ?>>ziekenhuis
                </label>
               
                <label for="chkbx-waste-water">
                    <input type="checkbox" id="chkbx-waste-water" <?php
                        echo $wasteWaterChecked; ?>>rioolwater
                </label>
                <label for="chkbx-waste-water-bubbles">
                    <input type="checkbox" id="chkbx-waste-water-bubbles" <?php
                        echo $wasteWaterBubblesChecked; ?>>rioolwaterstations
                </label>
                <span class="fa-solid info-sup" onClick="window.CL.display.popupToggle('popupWasteWater');">
                    &#xf05a;
                </span>
            </div>
            <div style="font-size:80%;line-height:1em;color:#777;padding:0 2em" class="hide">
                <p>
                * De rioolwaarden in de kaart geven nu het zevendaags gemiddelde weer.
                Gisteren was dat het gemiddelde van de laatste drie dagen.<br>
                * De kleurenschaal op de kaart met rioolwaarden is aangepast vergeleken met de kaart van gisteren.
                De schaal is uitgerekt omdat er sinds Omikron hogere waarden in het riool worden gemeten. 
                Lees meer in de <a href='#' onClick="window.CL.display.popupToggle('popupWasteWater');">toelichting</a>. 
                </p>
            </div>


            <!-- info container -->
            <div id="info-container-country-overview" class="info-container">
                <p class="notice today">
                    <a href="#" onClick="window.CL.charts.resetCharts();" 
                        title="Klik hier voor de landelijke grafiek.">Landelijk overzicht</a> bijgewerkt tot en met <?php
                        echo date( "j/n", $today["today"] ); ?>:</p>
                <?php
                
                



                
                /******* LANDELIJKE CIJFERS ************************/  
                
                
                
                ?>
                <div class="divTable">
                    <div class="divTableRow">


                        <div class="divTableCell tableCellLeft">
                            <ul>

                        <?php // waste water
                            if( isset( $todayTotalsPerDay["wastewater"] ) 
                                            && isset( $todayTotalsPerDay["wastewater"][ FileManager::HEADER_RNA] ) ): ?>
                            <li>virusniveau rioolwater: <?php echo formatNumber( $todayTotalsPerDay["wastewater"][ FileManager::HEADER_RNA] ); ?></li>
                            <li>zevendaags gemiddelde: <?php echo formatNumber( $todayTotalsPerDay["wastewater"][ "average" ] ); ?></li>
                        <?php endif; 
                        // var_dump( $todayTotalsPerDay );
                            if ( is_array( $todayTotalsPerDay  ) && is_array( $todayTotalsPerDay["infectionRadarPositiveTests"])
                                && isset( $todayTotalsPerDay["infectionRadarPositiveTests"][0][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ] ) ) : ?>
                            <li>percentage positieve testen: <?php echo formatNumber( $todayTotalsPerDay["infectionRadarPositiveTests"][0][ FileManager::HEADER_POSITIVE_TESTS_PERCENTAGE ], 1 );
                            ?>%<span class="span-relative"><span 
                                                        class="fa-solid info-click" 
                                                        title="klik voor een toelichting over het percentage positieve testen"
                                                        onClick="window.CL.display.popupToggle('popupChartInfectionRadarPositiveTests');">
                                                        &#xf05a;
                                                    </span></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        </div>










                        <div class="divTableCell tableCellRight">
                            <ul>




                        <?php // Hospital intake
                            if ( is_array( $todayLCPS )
                                                && isset( $todayLCPS["today"][ FileManager::HEADER_HOSPITAL_INTAKE ] ) ) : ?>
                                <li><?php echo $todayLCPS["today"][ FileManager::HEADER_HOSPITAL_INTAKE ];
                                    ?> ziekenhuisopnames <span class="span-relative"><span 
                                                                class="fa-solid info-click" 
                                                                title="klik voor een toelichting over de ziekenhuisopnames"
                                                                onClick="window.CL.display.popupToggle('popupChartHospitalized');">
                                                                &#xf05a;
                                                            </span></span>
                                </li>
                        <?php endif; ?>


                        <?php   // ICU intake
                            if ( is_array( $todayLCPS ) 
                                && isset( $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ] ) ) : ?>
                                <li><?php echo $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ];
                                         ?> IC-opnames</li>
                        <?php endif; ?>



                        <?php   // deceased 
                        /* *** DEPRECATED
                            if ( is_array( $todayTotalsPerDay ) 
                                        && isset( $todayTotalsPerDay[ FileManager::HEADER_DECEASED ] ) ) : ?>
                                <li><?php echo $todayTotalsPerDay[ FileManager::HEADER_DECEASED ]; ?> overlijdens</li>
                        <?php endif; 
                        */   ?>


                            </ul>
                        </div>
                    </div>
                </div>
                <?php /******* EINDE LANDELIJKE CIJFERS ************************/  
                
                

                    
                    
                    
                    
                    
                    
                
                
                /******* TOELICHTING LANDELIJKE CIJFERS ************************/ 




                        /******* TOELICHTING ZIEKENHUISCIJFERS ************************/ 


                    if ( is_array( $todayLCPS ) 
                                && isset( $todayLCPS["today"][ FileManager::HEADER_HOSPITALIZED ] )
                                && isset( $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED ] ) ) :
                        ?>
                        <p class="lcps">Er liggen <?php echo formatNumber( ( $todayLCPS["today"][ FileManager::HEADER_HOSPITALIZED ]
                                        + $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED ] ) );
                        ?> mensen met COVID in het ziekenhuis<?php
                        


                        //*** Ziekenhuisbezetting vergelijken met eerdere dagen

                        // Helpers cijfers vergelijken met gisteren en vorige week
                        $numbersYesterdayAvailable = ( isset ( $todayLCPS["earlier"] ) ) ? true : false;  
                        $numbersLastWeekAvailable = ( isset ( $todayLCPS["week_before"] ) ) ? true : false;  
                        
                        // haakje openenen
                        if( $numbersYesterdayAvailable || $numbersLastWeekAvailable ): ?> (<?php endif; 

                        // vergeleken met gisteren
                        if( $numbersYesterdayAvailable ) :
                                $diff = ( $todayLCPS["today"][ FileManager::HEADER_HOSPITALIZED ] + $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED ] )
                                                    - ( $todayLCPS["earlier"][ FileManager::HEADER_HOSPITALIZED ] + $todayLCPS["earlier"][ FileManager::HEADER_INTENSIVE_CARED ] );
                                if( 0 == $diff ) echo 'evenveel als';
                                else if ( $diff > 0 ) echo abs( $diff ) . ' meer dan';
                                else echo abs( $diff ) . ' minder dan';
                                ?> gisteren<?php
                        endif;

                        // als de cijfers van zowel gisteren als vorige week beschikbaar zijn: 'en'
                        if( $numbersYesterdayAvailable && $numbersLastWeekAvailable ): ?> en <?php endif; 

                        // vergeleken met vorige week
                        if( isset ( $todayLCPS["week_before"] ) ) :
                                $diff = ( $todayLCPS["today"][ FileManager::HEADER_HOSPITALIZED ] + $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED ] )
                                                    - ( $todayLCPS["week_before"][ FileManager::HEADER_HOSPITALIZED ] + $todayLCPS["week_before"][ FileManager::HEADER_INTENSIVE_CARED ] );
                                if( 0 == $diff ) echo 'evenveel als';
                                else if ( $diff > 0 ) echo abs( $diff ) . ' meer dan';
                                else echo abs( $diff ) . ' minder dan'; 
                        ?> zeven dagen geleden<?php
                        endif;

                        // haakje sluiten
                        if( $numbersYesterdayAvailable || $numbersLastWeekAvailable ): ?>)<?php endif; 

                        //*** einde vergelijking ziekenhuisbezetting vergelijken met eerdere dagen
                        
                        
                        // hoeveel op de IC?
                        ?>, waarvan <?php  
                        echo $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED ] ?> op de IC. <?php




                        // IC in het buitenland
                        if( $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ] > 1 ) : 
                        ?>Daarnaast liggen er <?php echo $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ] ?>
                        mensen in Duitsland op de IC.<?php endif; 
                        if( $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ] == 1 ) : 
                            ?>Daarnaast ligt er <?php echo $todayLCPS["today"][ FileManager::HEADER_INTENSIVE_CARED_INTERNATIONAL ] ?>
                            persoon in Duitsland op de IC.<?php endif; 
                                // Einde IC buitenland
                            ?>
                        </p>


                    <?php 
                        else : // Nog geen update van ziekenhuiscijfers 
                        ?>

                        <p class="lcps"><i><br>Ziekenhuisopnames worden sinds 1 juni 2024 niet meer door het LCPS of het RIVM geüpdatet.
                                    De grafiek is daarom verborgen. <a href="" id="toggle-chart-hospitalized">Klik hier</a> om de grafiek weer te geven.</i></p> 
                       <!-- 
                       <p class="lcps">Let op: Er is op <?php echo date( "j/n", $today["today"] ); ?> 
                                nog geen update van de landelijke ziekenhuiscijfers gegeven. 

                            <?php
                            if ( is_array( $todayLCPS["end"] ) 
                                && isset( $todayLCPS["end"][ FileManager::HEADER_DATE ] )) :

                                        $lastKnownLCPSDateTime = new DateTime( $todayLCPS["end"][ FileManager::HEADER_DATE ] );
                                        $lastKnownLCPSDate = $lastKnownLCPSDateTime->format("j/n");
                                        ?>
                                Meest recente cijfers zijn van <?php echo  $lastKnownLCPSDate; ?>: 
                                <?php echo  $todayLCPS["end"][ FileManager::HEADER_HOSPITAL_INTAKE ]; ?> nieuwe ziekenhuisopnames ~ 
                                <?php echo $todayLCPS["end"][ FileManager::HEADER_INTENSIVE_CARE_INTAKE ]; ?> nieuwe IC-opnames ~  
                                <?php echo formatNumber( $todayLCPS["end"][ FileManager::HEADER_HOSPITALIZED ] 
                                        + $todayLCPS["end"][ FileManager::HEADER_INTENSIVE_CARED ] ); ?> mensen in het ziekenhuis,
                                waarvan <?php echo formatNumber( $todayLCPS["end"][ FileManager::HEADER_INTENSIVE_CARED ] ); ?> op de IC.
                            
                            <?php
                            endif;
                            ?>
                        </p>
                        -->

                     <!--   <p class="lcps"><i>Het LCPS <a href="https://lcps.nu" target="_blank">meldt</a> dat het is gestopt met de registratie van het aantal ziekenhuisopnames.
                            Om die reden zijn er geen actuele opnamecijfers meer.</i></p>
                        -->
                    <?php endif; 
                    



                ?>
                        <p class="lcps"><i><br>De gegevens op deze site worden op woensdagen bijgewerkt.</i></p>
                
                <?php 
                
                
                /******* STORINGSMELDINGEN ************************/ 
                
        
                    if( $storing ) : ?>
                    <p class="lcps">&nbsp;</p>
                    <!-- <p class="lcps"><i><b>Let op: vanwege kerst en de jaarwisseling zal het RIVM 
                        geen update van de rioolwatermetingen publiceren op 25 december, 26 december en 1 januari. 
                        Ook kan er in de daaropvolgende dagen enige vertraging zijn in de rioolwatercijfers.</b></i></p> -->
                    <!--  <p class="lcps"><i>Let op: Minister Kuipers heeft besloten dat er vanaf 1 januari minder vaak metingen in het rioolwater worden uitgevoerd.
                        Dit kan betekenen dat de rioolwatergrafiek minder betrouwbaar wordt en minder vaak wordt bijgewerkt.</i></p>
                    -->
                    <p class="lcps"><i>Let op: Er is vandaag nog geen update van de rioolwatermetingen gegeven.
                        Het is onbekend wat de reden hiervoor is. Zodra nieuwe rioolwatermetingen beschikbaar komen,
                        wordt de website bijgewerkt.</i></p>
                    <?php endif; ?>
                    <?php if( $storingSolved ) : ?>
                        <p class="lcps"><i>Let op: De technische storing van gisteren is opgelost.</i></p>
                    <?php endif; ?>

               
                </div>


            <!-- share buttons -->
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



        </div><!-- .column-wrapper right -->
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
       // let positiveTestView = <?php echo ( $positiveTestsView ) ? 'true' : 'false'; ?>;
        let wasteWaterView = <?php echo ( $wasteWaterView ) ? 'true' : 'false'; ?>;
        let infectionRadarPositiveTestsView = <?php echo ( $infectionRadarPositiveTestsView ) ? 'true' : 'false'; ?>;
        <?php if( $showNotice ) : ?> window.CL.display.popupToggle( 'popupNotice' ); <?php endif; ?>
    </script>

    <script defer src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'assets/map/exporting.js' ); ?>"></script>
    <!-- <script defer src="<?php echo BASE_URL . FileUtils::addTimeStamp( 'data/cache/cache.map-values-per-city-hospital-admission-per-hundred-thousand.js' ); ?>"></script> -->
    
</body>
</html>