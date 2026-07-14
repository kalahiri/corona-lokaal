/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";






( function ( interactions, CL ) {


    // some settings for navigating the map using touchscreens
    /*
    CL.touchscreen = false;
    window.clicked = false;
    window.clickTimer = 0;
    */


/**
 * Download Chart as an Image
 */

 interactions.saveCanvasAsImage = function( imageName, canvasElement ) {
    const imageDataURL = canvasElement.toDataURL('image/png');
    const datestring =  new Date().toISOString();
    const datetimestring =  'D' + datestring.substring(0,datestring.length-5).replaceAll( '-','.').replaceAll( ':','.').replaceAll( 'T','.T');

    const downloadImage = document.createElement("a");
    document.body.appendChild(downloadImage);
    downloadImage.setAttribute( "download", imageName + "." + datetimestring + ".corona-lokaal.nl.png" );
    downloadImage.href = imageDataURL;
    downloadImage.click();
    downloadImage.remove();

};











}( window.CL.interactions = window.CL.interactions || {}, window.CL ) );


