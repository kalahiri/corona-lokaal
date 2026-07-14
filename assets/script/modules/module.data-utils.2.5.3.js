/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";



/**
 * Data Handling Object: - cookies, ajax
 * Namespace: dataUtils
 */
( function ( dataUtils, CL ) {
    
    /**
     * Gets the value or values of a javascript cookie.
     * @param { string } key:The name of the cookie.
     * @return:the value of the cookie. If key is empty,return all the cookies as an object.
     */
    dataUtils.getCookie = function( key = '' ) {
        // console.log( document.cookie);
        const cookies = {};
        document.cookie.split(';').forEach( function( item ) {
            let [name,value] = item.split('=');
            if( ( undefined !== name ) && ( undefined !== value ) ) {
                name = name.trim();
                cookies[ name ] = value.trim();
            }
        });
        return ( '' === key ) ? cookies :cookies[ key ];
    };

    /**
     * Write a javascript cookie with value pair key and value.
     * Multiple cookies can be set by calling this function multiple times.
     * Notice:The cookie is standard set for one year.
     * @param { String } key
     * @param { Mixed } value
     */
    dataUtils.setCookie = function( key,value ) {
        let cookieString = '';
        let cookies = dataUtils.getCookie();
        cookies[ key ] = value;
        Object.keys( cookies ).forEach( function( key ) {
            cookieString += key + '=' + cookies[ key ] + '; ';
        });
        const expireDateTime = new Date();
        expireDateTime.setFullYear( expireDateTime.getFullYear() + 1 );
        cookieString += 'expires=' + expireDateTime.toUTCString() + '; path=/';
        document.cookie = cookieString;
    };

    /**
     * Ajax Requests
     * TODO:https://medium.com/coding-design/writing-better-ajax-8ee4a7fb95f
     */
    dataUtils.ajaxRequest = function ( action, category, values ) {
        // let zoomIn = true; // tijdelijke definitie. Hoort bij CLchart
        CL.maps.clearblinkCity();  // remove blinking before new city selected

        if ( event ) event.preventDefault(); // TODO:change onclicks to event listeners
        const title = document.querySelector( '#title' );
        title.innerHTML = "Loading...";
        //clearblinkCity();

        const formData = new FormData();
        formData.append( 'action', action );
        formData.append( 'category', category );
        formData.append( 'value', values );
        formData.append( 'referrer', window.location.href );
        // console.log( "ajax params:" );console.log( formData );

        // send AJAX request
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then( function( headers ) { // read headers
            if( 200 === headers.status ) return headers.text();
            else console.log( 'error fetching data: ' + headers.status  );
        }) 
        .then( function( responseJSON ) { // if headers == OK, read body
            let response;
            // console.log( responseJSON );
            try { 
                response = JSON.parse( responseJSON );
                 //console.log( response.chartLabels );
            } catch ( exception ) {
                if( ! responseJSON ) console.log( '>>> Empty response from server: probably returned \'false\'');
                console.log( 'Unexpected response from server: \n' + responseJSON );
                console.log( "Exception: " + exception );
                return;
            }
            CL.main.resolveAjaxRequest( action,response );
        });
    };


}( window.CL.dataUtils = window.CL.dataUtils || {}, window.CL ) );
/** Data Handling & Support Methods / Namespace: dataUtils */






