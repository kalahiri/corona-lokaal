<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Site Utils
 */
class SiteUtils {



public static function createSiteMap() {
	
    $dateTimeComment=gmdate("D, j M Y H:i:s",time()); // UNIX-timestamp is always GMT

    $dateTime= new DateTime( );
    $formattedDateTime = $dateTime->format( DateTimeInterface::W3C );

    // Maak de inhoud van het XML bestand
    $xmlContents="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <urlset
        xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">
        <!-- corona-lokaal sitemap generator 1.0 - https://www.corona-lokaal.nl -->
        <!-- sitemap generated on $dateTimeComment -->

        <url>
            <loc>https://www.corona-lokaal.nl/</loc>
            <lastmod>$formattedDateTime</lastmod>
            <changefreq>daily</changefreq>
        </url>
        <url>
            <loc>https://www.corona-lokaal.nl/locatie/Nederland</loc>
            <lastmod>$formattedDateTime</lastmod>
            <changefreq>daily</changefreq>
        </url>";
    
    $cityModel = new CityModel();
    $listOfCities = $cityModel->getListOfCities();
    foreach( $listOfCities as $row ) {
        $xmlContents.="
        <url>
            <loc>https://www.corona-lokaal.nl/gemeente/" . rawurlencode( $row["city"] ) . "</loc>
            <lastmod>" . $formattedDateTime . "</lastmod>
            <changefreq>daily</changefreq>
        </url>";
    }

    // Add dokuwiki  <url>
    $dokuWiki = array(  'index', 'rioolwatermetingen', 'introductie_rioolwatermetingen', 'positieve_testen',
                        'landelijke_grafiek_rioolwatermetingen', 'rioolwatermetingen:vroegsignaal',
                        'rioolwatermetingen:model' ,'ziekenhuisopnames', 'rioolwatermetingen:grafiek', 
                        'vaccins', 'vaccins:pfizer' );
    foreach( $dokuWiki as $page ) {
        $xmlContents.="
        <url>
            <loc>https://www.corona-lokaal.nl/wiki/doku.php/" . rawurlencode( $page ) . "</loc>
            <lastmod>" . $formattedDateTime . "</lastmod>
            <changefreq>daily</changefreq>
        </url>";
    }

    $xmlContents.="
    </urlset>";

    file_put_contents(  'sitemap.xml' , $xmlContents, LOCK_EX );
    return true;
    
    }





}
