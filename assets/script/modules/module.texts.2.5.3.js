/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";

/**
 * Corona Lokaal Texts Namespace for all extra texts
 * Namespace: CL.texts
 */

( function ( texts, CL ) {
    let qenaTextContainer;

    /**
     * DOM content is loaded so select elements and add event listeners
     */
    texts.initDOMLoaded = function() {
        qenaTextContainer = document.getElementById('qena-container');
       // canvasWasteWater = document.getElementById('canvas-waste-water');
       // canvasReported = document.getElementById('canvas-reported');

        // allCanvasses = [ canvasWasteWater, canvasHospitalized, canvasDeceased ];

        //zoomSymbolsList = document.querySelectorAll('.zoom-container .zoom');  // array of magnifier symbols
       // chartSettingsZoomDays = document.getElementById('chart-setting-zoom-days' );  // input setting zoom days
     

        // add chart settings box on click settings icon.
        //document.getElementById('submit-chart-settings').addEventListener( 'click',function () {
        //    setZoomDays( chartSettingsZoomDays.value );
        //});

        // add zoom action to charts.
        document.querySelectorAll( '.click-text-container' ).forEach( item => {
            item.addEventListener('click',function() {
                CL.texts.getTextData( item );
            });
        });

    }; // DOMContentLoaded


    /**
     * Event window loaded: All elements for the webpage are loaded

     */
    texts.initWindowLoaded = function() {


    }; // windowLoaded


    
    /**
     * Get the requested text by Ajax-request.
     */
    texts.getTextData = function( item ) {
        let textId = item.id.replace( 'question', 'answer' ); 
        let currentAnswer = document.getElementById( textId );
        if( ! currentAnswer.classList.contains( 'hide' ) ) {
            showTextElement( '' );
            return;
        }

        // check if text already there.
        if( currentAnswer.textContent.length > 100 ) {
            showTextElement( currentAnswer );
            return;
        }

        // get text
        CL.dataUtils.ajaxRequest( 'get', 'text', item.id );
    }


    /**
     * 
     * @param { html-element } element to be shown. If empty all elements are/stay hidden
     */
    function showTextElement( element ) {
        // first, close all open answers
        document.querySelectorAll( '.qena-answer' ).forEach( item => item.classList.add( 'hide' ));
        document.querySelectorAll( '.qena-question .fa-solid' ).forEach( item => item.innerHTML = '&#xf13a;' );
        if ( element ) {
            element.classList.remove( 'hide' ); // show only this specific answer
            let questionId = element.id.replace( 'answer', 'question' ); 
            let question = document.querySelector( '#' + questionId + ' .fa-solid' );
            if( question ) question.innerHTML = '&#xf139;';
        }

    }


    /**
     * Set the data received by an ajax-request to the charts.
     * @param {enum} type 
     * @param {array-object} data 
     */
    texts.setTextData = function( data ) {
        let currentAnswer = document.getElementById( data.textId );
        currentAnswer.innerHTML = data.textData;
            showTextElement( currentAnswer );
    }

 
    /**
     * Get the requested text by Ajax-request.
     */
    texts.enlargeImage = function( image ) {
        let popupImage = document.querySelector( '#popupEnlargedImage .popup-contents');
        popupImage.innerHTML = "<img src='" + image.src + "' alt=''>";
        window.CL.display.popupToggle( 'popupEnlargedImage', 0, 0, true );
    }



}( window.CL.texts = window.CL.texts || {}, window.CL ) );
/** Corona Lokaal Texts Namespace */


