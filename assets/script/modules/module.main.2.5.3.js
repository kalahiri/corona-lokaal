/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";

var domContentStart;

( function ( main, CL ) {

    // some settings for navigating the map using touchscreens
    CL.touchscreen = false;
    window.clicked = false;
    window.clickTimer = 0;

    let twitter,facebook,linkedin,email,selectLocation,title,titleH1,
                        inputLocationListContainer,inputLocationList;
    let goToTop = true;


    /**
     * DOM content is loaded so select elements and add event listeners
     */
    window.addEventListener('DOMContentLoaded',() => {
        twitter = document.getElementById('twitter');
        facebook = document.getElementById('facebook');
        linkedin = document.getElementById('linkedin');
        email = document.getElementById('email');
        title = document.getElementById('title');
        titleH1 = document.getElementsByTagName("h1")[0];
        selectLocation = document.getElementById('location-search-input');
        
        inputLocationListContainer = document.getElementById('location-search');
        inputLocationList = inputLocationListContainer.querySelector("ul");

        cities.push({"code":0,"city":"Nederland"});
        CL.charts.initDOMLoaded();
        CL.maps.initDOMLoaded();
        CL.texts.initDOMLoaded();

        // place the waste water chart on the right position (responsive)
        goToTop = CL.display.responsivePositions();

        // add chart settings box on click settings icon.
        document.getElementById('logo-click').addEventListener( 'click',function () {
            CL.charts.resetCharts();
        });
        window.addEventListener("resize", CL.display.responsivePositions );
        // check for touchscreens
        window.addEventListener("touchstart",function onFirstTouch() {
            window.CL.touchscreen = true;
            window.removeEventListener('touchstart',onFirstTouch,false);
        });
        // add close action to all popups so they can be closed
        document.querySelectorAll( '.click-to-close' ).forEach( item => {
            item.addEventListener('click',event => {
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            });
        });
        // add chart settings box on click settings icon.
        selectLocation.addEventListener('keyup', function( event ) {
            if ( 'Enter' === event.key ) return;
            createLocationList( this.value );
        });
        selectLocation.addEventListener('click', function() {
            this.select();
        });
        selectLocation.addEventListener('keydown', function( event ) {
            if ( 'Tab' === event.key ) {
                event.preventDefault();
                checkAndLoadLocation( this.value );
            }
        });
        selectLocation.addEventListener('keypress', function( event ) {
            if ( 'Enter' === event.key ) {
                checkAndLoadLocation( this.value );
            }
        });

    }); // DOM content loaded

    
    /**
     * Page is loaded: Draw the charts and the maps as soon as the page is loaded.
     */
    window.addEventListener('load',() => {
        CL.charts.initWindowLoaded();
        CL.maps.initWindowLoaded();
        CL.texts.initWindowLoaded();

        let simpelNamesArray = [
            "Groningen",
            "Friesland",
            "Drenthe",
            "Overijssel",
            "Flevoland",
            "Gelderland",
            "Utrecht",
            "Noord-Holland",
            "Zuid-Holland",
            "Zeeland",
            "Noord-Brabant",
            "Limburg"
        ];
        const simpelCodesArray = [
            "PV20",
            "PV21",
            "PV22",
            "PV23",
            "PV24",
            "PV25",
            "PV26",
            "PV27",
            "PV28",
            "PV29",
            "PV30",
            "PV31"
        ];

        let codeKey = simpelNamesArray.indexOf( loc );
        if( codeKey > -1 ) loc = simpelCodesArray[ codeKey ];
        codeKey = simpelNamesArray.indexOf( plant );
        if( codeKey > -1 ) plant = simpelCodesArray[ codeKey ];


        if( ! plant ) plant = 'Nederland';
        if( 'Nederland' == loc ) {
            CL.dataUtils.ajaxRequest( 'get', 'location', 'Nederland' );
            setTimeout( function() {
                CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', 'positiveTests' );
            }, 500 );
        } else {
            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', loc );
            // Infection Radar is only for whole country available, so


        }
        setTimeout( function() {
            CL.dataUtils.ajaxRequest( 'get', 'plant', plant );
        }, 250 );
    });


    /**
     * Creates a list where the given characters match the first characters of the city name
     * @param { string } chars The (first) characters of the name. 
     */
    function createLocationList( chars = '' ) {
        let list = '';
        let i = 0;
        const filter = chars.trim().toUpperCase();
        if( '' !== filter ) {
            for ( let item of cities ) {
                if ( item.city.substring( 0,filter.length ).toUpperCase() === filter ) {
                    list += '<li data-city="' + item.city + '">' + item.city + '</li>';
                    i++;
                    if ( i > 7 ) break;
                }
            }
        }
        inputLocationList.innerHTML = list;
        inputLocationList.querySelectorAll( 'li' ).forEach( item => {
            item.addEventListener('click',event => {
                CL.dataUtils.ajaxRequest( 'get','location',event.target.dataset.city );
                inputLocationList.innerHTML = '';
            });
        });
    }


    /**
     * Get the first city that matches the given input characters
     * @param { String } chars The (first) characters of the name. 
     */
    function checkAndLoadLocation( chars = '' ) {
        const filter = chars.trim().toUpperCase();
        if( '' !== filter ) {
            for ( let item of cities ) {
                if ( item.city.substring( 0,filter.length ).toUpperCase() === filter ) {
                    CL.dataUtils.ajaxRequest( 'get','location',item.city );
                    selectLocation.blur();
                    inputLocationList.innerHTML = '';
                    return;
                }
            }
        }
    }


    /**
     * Write new titles and share buttons if the chart data has changed
     */
    main.redrawTitles = function() {

        let hospitalizedLocation = CL.charts.getLocation();
        let wasteWaterLocation = CL.charts.getWastewaterLocation();

        // console.log( hospitalizedLocation);

        if( ! hospitalizedLocation.name ) hospitalizedLocation.name = 'Nederland';
        if( ! wasteWaterLocation.name ) wasteWaterLocation.name = 'Nederland';


        let tmpTitle = hospitalizedLocation.name + '/' + wasteWaterLocation.name;

        /*
        title.innerHTML = tmpTitle + " - bijgewerkt tot en met "
                        + CL.charts.getChartLabel( 'city', -1 );
                        + " op basis van cijfers RIVM.";
        selectLocation.value = locationName;
        */

        // set URL and address bar
        let url = baseUrl + 'locatie/' + hospitalizedLocation.name + '/waterzuivering/' + wasteWaterLocation.name;
        window.history.pushState('', baseTitle + tmpTitle, url );
        window.document.title = baseTitle + tmpTitle;
        titleH1.innerHTML = siteName + ' | ' + tmpTitle;

        // share buttons.
        let shareText = 'Bekijk%20de%20corona-ontwikkelingen%20in%20' + hospitalizedLocation.name
                    + '%20met%20waterzuivering%20' + wasteWaterLocation.name;

        const sourceText = '%0A%0ACorona%20Lokaal%20-%0A';
        //const urlText = encodeURIComponent( baseUrl + 'location/' + city.data.city );
        const urlText = encodeURIComponent( url );
        twitter.href = 'https://twitter.com/intent/tweet?text=' + shareText + sourceText + '&url=' + urlText;
        facebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + urlText;
        linkedin.href = 'https://www.linkedin.com/shareArticle?mini=true&title=' + shareText + '&source=' + sourceText + '&url=' + urlText;
        email.href = 'mailto:?subject='+ shareText + '&body=' + shareText + '%0A%0A' + urlText + '%0A%0A%0A';
    };


    /**
     * Handle the response from the Ajax Request
     * @param {JSON} response 
     */
    main.resolveAjaxRequest = function( action, response ) {
        // console.log( 'we are in main.resolveAjaxRequest()' );
        // console.log( response );
        // console.log( response.data );
        if ( response.success ) {
            switch ( action ) {
                case 'get' :
                    let locationName = '';
                    if ( response.data.updating ) {
                        title.innerHTML = "Een moment geduld aub. De site wordt op dit moment geüpdatet. Dit duurt enkele seconden.";
                        break;
                    }
                    if( response.data.type.category == 'text' ) {
                        CL.texts.setTextData( response.data );
                    }


                    if ( response.data.type.category == 'wastewater' ) {

/*
                        if ( goToTop ) {
                            CL.display.scrollToTop();
                        }
*/
                        CL.charts.setChartData( 'wastewater', response.data );
                        //CL.charts.redrawCharts( 'wastewater' );
                    }
                    if ( response.data.type.category == 'hospitalized' ) {
                        CL.charts.setChartData( 'hospitalized', response.data );
                    }
                    if ( response.data.type.category == 'infectionRadarPositiveTests' ) {
                        CL.charts.setChartData( 'infectionRadarPositiveTests', response.data );
                    }
                    if ( response.data.city || response.data.country ) {
                        locationName = response.data.city;
                        CL.display.scrollToTop();
                        CL.charts.setChartData( 'city', response.data );
                        //CL.charts.redrawCharts( 'city' );
                        if ( response.data.city && ! response.data.country ) {
                            CL.maps.blinkCity( locationName );
                        }
                    }
                    main.redrawTitles( locationName );
                    break;
            }
        } else {
            title.innerHTML = "Er ging iets mis. Probeer het opnieuw.";
        }
    };



 



}( window.CL.main = window.CL.main || {}, window.CL ) );



