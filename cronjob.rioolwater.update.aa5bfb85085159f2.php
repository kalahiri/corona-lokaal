<?php
/**
 * This file is written by Kalahiri in 2024 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

/**
 * 
 * LET OP VOOR CRONJOBS!
 * 
 * Cronjobs starten vanuit een ander path dan de gebruikelijke home directory van de website.
 * Het probleem is dat daardoor allerlei externe bestanden (classes!) in het script vanwege een relative path niet gevonden worden.
 * include() en require() lopen dan dus vast!
 * 
 * Om die reden moet je bij de cronjob ook een nieuwe 'working directory' meegeven zodat de relative paths weer werken. 
 * Hert veranderen van de working directory (command 'cd /home/coronalo/domains/corona-lokaal.nl/public_html/') geef je in dezelfe opdracht
 * mee met het starten van het script ( command 'php /home/coronalo/domains/corona-lokaal.nl/public_html/cronjob.php' ).
 * 
 * Je voegt de commanda's samen met '&&'. Dus dan wordt het:
 * 
 * cd /home/coronalo/domains/corona-lokaal.nl/public_html/ && php /home/coronalo/domains/corona-lokaal.nl/public_html/cronjob.php
 * 
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



// Only update Waste Water file if available
function tryRefreshWasteWater() {
    if( FileManager::shouldWasteWaterBeUpdated( true ) ) {
        FileUtils::writeCookie( "updating", 1 );
        if( FileManager::getRemoteWasteWaterFile() ) {  // gelukt
            $statusMessage = "- Bestand <b>rioolwater</b> opgehaald." . PHP_EOL;

            // Update downloaded, let's renew the cache files
            FileUtils::writeCookie( "updating", 2 );
            $result = FileManager::emptyCache( FileManager::TOTALS_PER_DAY_UPDATE );
            if( $result) {
                $statusMessage .= "<br> - Cache is verwijderd."  . PHP_EOL;
            } else {
                $statusMessage .= "<br> - Let op: Er ging iets mis met het verwijderen van de cache."  . PHP_EOL;
            }

            // to create cache files
            $wasteWaterModel = new WastewaterModel();
            $wasteWaterModel->getSeriesSortedByLocation();
            $wasteWaterModel->getPlantLocations();
            $wasteWaterModel->getChartDataTotals( false );
            $dateLastUpdateWasteWaterSourceFile = $wasteWaterModel->getDateSourceFile(); // date laste update
            $mostRecentNationalWasteWater = $wasteWaterModel->getLatestValue();
            $wasteWaterModel->getChartAndMapDataPerProvince();

            $statusMessage .= "<br> - Cache is opnieuw aangemaakt."  . PHP_EOL;

            return $statusMessage;
        }  
        // niet gelukt. ergens iets fout gegaan
        return "Er is iets misgegaan bij het ophalen van de update van rioolwater.csv";
    }
    // geen update beschikbaar
    return "Er is geen update beschikbaar."; // geen update beschikbaar
}

// Try to download the update if an update is available.
$statusMessage = (string) tryRefreshWasteWater();

// reset the update cookie and make the website available again for visistors 
FileUtils::writeCookie("updating",0);



// mail the result of the cronjob updating process
$htmlContent = '<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
    <p>' .  $statusMessage . '</p>
</body>
</html>';

$plainTextContent = strip_tags( $statusMessage );



// Mail the result of the update process to me.
$mailer = new Mailer();

//mailer init

$mailer->setSendFromEmailHost( 'mail.corona-lokaal.nl' );  // Set the SMTP server to send through
$mailer->setSendFromEmailDisplayname( 'Corona Lokaal' ); // SMTP username
$mailer->setSendFromEmailAddress( '**********@corona-lokaal.nl' ); // SMTP username
$mailer->setSendFromEmailPassword( '*************' ); // SMTP password
$mailer->setSendFromContactPage( 'https://www.corona-lokaal.nl/' );


$mailer->initMailer();


// create email and email contents
$mailer->addRecipient( "******", "*********");
$mailer->setSubject( "rioolwater update");
$mailer->setHtmlContent( $htmlContent );
$mailer->setPlainTextContent( $plainTextContent );
$mailer->send();

