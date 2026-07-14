/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";


/**
 * Corona Lokaal Maps Namespace for all maps
 * Namespace: CL.maps
 */
( function ( maps ) {

    // Some Default Config for the Maps
    const legend= {
        //enabled:false,
        floating:false,
        backgroundColor:'#ffffff',
        verticalAlign:'bottom',
        //margin: '4em',
        borderWidth:0,
        alignColumns:true,
        valueDecimals:0,
        itemStyle:{
            fontSize:'80%',
            fontWeight:'normal',
            fontStyle:'none',
            color:'gray',
            textDecoration:'none'
        },
        itemMarginTop:0,
        itemMarginBottom:0,
        margin:10,
        padding:0,
        width:'80%',
        align: 'left',
        x: 40,
        y: ( window.innerWidth < 600 ) ? 0 : 12,
    };
    const mapNavigation = {
            enabled:true,
            buttonOptions:{
                verticalAlign:'bottom'
            },
            enableDoubleClickZoom:false
    };
    const plotOptions = {
        series:{
            states:{
                inactive:{
                    opacity:1
                }
            }
        }
    };
    
    // create the waste water bubbles in the map
    const seriesBubbles = {
        type:'mapbubble',
        name:'virusdeeltjes in rioolwater',
        id:'waste-water-bubbles',
        dataLabels:{
            enabled:false
        },
        events:{
            click:function( event ) {
                CL.dataUtils.ajaxRequest( 'get','plant',event.point.name );
            }
        },
        data:[],
        minSize:'2%',
        maxSize:'2%',
        tooltip:{
            distance:70,
            style:{
                color:'#666',
                lineHeight:'20px'
            },
            pointFormatter:function(){
                let s = '<span style="font-weight:bold;font-size:14px;"> ' + this.name
                    + '</span><br><span style="color:#666;font-size:12px;">• '+ this.rna
                    + ' coronavirusdeeltjes per 100.000 inwoners, meting van '+ this.date + '</span><br>';
                return s;
            }
        },
        marker:{
            lineColor:'#666',
            lineWidth:1,
            states:{
                hover:{
                    lineWidth:2,
                    fillColor:'#369cd2',
                    lineColor:'#369cd2'
                }
            }
        }
    };

    let mapContainerPositiveTests,mapContainerVaccinatedPerCity,mapContainerHospitalizedPerCity,mapContainerWasteWaterPerProvince;
    let zoomInBubbles = 100;
    let titleText;

    // DOM content is loaded so select elements and add event listeners
    maps.initDOMLoaded = function() {
        mapContainerPositiveTests = document.getElementById('map-container-positive-tests');
        mapContainerVaccinatedPerCity = document.getElementById('map-container-vaccinated-per-city');
        mapContainerHospitalizedPerCity = document.getElementById('map-container-hospitalized-per-city');
        mapContainerWasteWaterPerProvince = document.getElementById('map-container-waste-water-per-province');

        document.getElementById('chkbx-vaccinated-per-city').addEventListener( 'click',function () {
            mapOptionsClicked( 'vaccinated-per-city' );
        });
        document.getElementById('chkbx-positive-tests').addEventListener( 'click',function () {
            mapOptionsClicked( 'positive-tests' );
        });
        document.getElementById('chkbx-hospitalized-per-city').addEventListener( 'click',function () {
            mapOptionsClicked( 'hospitalized-per-city' );
        });
        document.getElementById('chkbx-waste-water').addEventListener( 'click',function () {
            mapOptionsClicked( 'waste-water' );
        });
        document.getElementById('chkbx-waste-water-bubbles').addEventListener( 'click',function () {
            mapOptionsClicked( 'waste-water-bubbles' );
        });


        // add vaccinatiegraad per wijk on click button.
        const buttonPerNeighbourhood = document.getElementById('button-vaccinatiegraad-per-wijk');
        if( undefined !== buttonPerNeighbourhood && null !==  buttonPerNeighbourhood) {
            buttonPerNeighbourhood.addEventListener('click', function( event ) {
                maps.toggleVaccinesPerNeighbourhood( true, event );
            });
        }


    };


    /**
     * init onload
     */
    maps.initWindowLoaded = function() {
        // checkboxes and titles
        window.chckbxWasteWater = document.getElementById( 'chkbx-waste-water' );
        window.chckbxWasteWaterBubbles = document.getElementById( 'chkbx-waste-water-bubbles' );
        window.chckbxPositiveTests = document.getElementById( 'chkbx-positive-tests' );
        window.chckbxVaccinatedPerCity = document.getElementById( 'chkbx-vaccinated-per-city' );
        window.chckbxHospitalizedPerCity = document.getElementById( 'chkbx-hospitalized-per-city' );
        let titleText = ( window.chckbxPositiveTests.checked ) ? 'Positieve testen afgelopen 7 dagen per 100.000 inwoners' :'';
        if ( window.chckbxPositiveTests.checked && window.chckbxWasteWater.checked ) titleText += ' /<br>';
        titleText += ( window.chckbxWasteWater.checked ) ? 'Virusdeeltjes in het rioolwater per 100.000 inwoners' :'';


        titleText = 'Rioolwaterwaarden per provincie';
        // create maps
        maps.createMapWasteWaterProvince();
        // maps.createMapPositiveTests( titleText ); // not used anymore
        setTimeout( function() { // not everything at once,so delay to ensure smooth animations
            // maps.createMapVaccinatedPerCity(); // not used anymore
            // maps.createMapHospitalizedPerCity(); // not used anymore
            // maps.createMapVaccinatedPerNeigbourhood(); // not used anymore
        },1500 );

        // create the waste water bubbles if checked
        seriesBubbles.data = plantLocations;
        if( window.chckbxWasteWater.checked ) {
            titleText += '<br>en per rioolwaterzuivering';
            // window.mapNLWasteWater.addSeries( seriesBubbles,true);
        }

        setTimeout( function() { // not everything at once,so delay to ensure smooth animations
            document.getElementById('numberWasteWaterLocations').innerHTML = plantLocations.length;
        },2000 );


        /*
        var cont = document.getElementById('map-container-positive-tests');
        var size = document.getElementById('map-container-positive-tests').getClientRects()[0];
        console.log( size[0] );
        document.getElementById('feedback').innerHTML = 'h: ' + size.height + ', w: ' + size.width ;
        cont.style.border = '1px solid #000';
*/


    };


/*
    maps.moveLegend = function( type ) {
        return;
        switch( type ) {
            case 'up': 
                document.querySelectorAll('.highcharts-legend').forEach(function(item) {
                    var xforms = item.transform.baseVal; 
                        // Find the right transform type ( SVGTransform.SVG_TRANSFORM_TRANSLATE )
                    for (var i=0; i<xforms.numberOfItems; ++i){
                        // Get this part as an SVGTransform
                        var xform = xforms.getItem(i);
                        if( SVGTransform.SVG_TRANSFORM_TRANSLATE == xform.type ) { // https://www.w3.org/TR/SVG2/coords.html
                            let x = xform.matrix.e;
                            let y = xform.matrix.f;
                            y -= 50
                            console.log( x + ', ' + y ); // https://www.w3.org/TR/SVG/coords.html#InterfaceSVGTransform
                            xform.setTranslate(x, y);
                        }
                    }
                });
            break;
        }  
            
    };
*/




    /**
     * Blink the area of the selected city on the map
     * @param {string} cityName
     */
    let blinking = {};
    const blinkColor = '#369cd2';
    maps.blinkCity = function( cityName ) {

        // we have no data per city any longer, so return immediately
        return;

        if ( cityName == 'Nederland' ) return;
        blinking.index = window.mapNL.series[0].points.findIndex((p) => p.cityname === cityName );
        if ( undefined === blinking.index ) return;
        blinking.baseColor =  window.mapNL.series[0].points[ blinking.index ].color;
        let blink = false;
        blinking.intervalId = setInterval( function(){
            window.mapNL.series[0].points[ blinking.index ].update({
                color : (blink) ? blinking.baseColor : blinkColor,
            });
            blink = ! blink;
        },300);
        setTimeout( function(){ maps.clearblinkCity(); },4000 );
    };


    /**
     * Clear the blinking started by blinkingCity();
     */
    maps.clearblinkCity = function() {
        if ( undefined === blinking.intervalId ) return;
        clearInterval( blinking.intervalId );
        window.mapNL.series[0].points[ blinking.index ].update({
            color :blinking.baseColor
        });
    };


    /**
     * Is the waste water checkbox checked?
     */
    maps.isWasteWaterVisible = function() {
        return window.chckbxWasteWater.checked;
    };


    /**
     * controlthe waste water bubbles from outside.
     * Used by close button on waste water chart.
     * @param {enum} type 
     */
    maps.hide = function( type ) {
        switch( type ) {
            case 'waste-water':
                window.chckbxWasteWater.checked = false;
                mapOptionsClicked( type );
            break;
        }
    }


    /**
     * interactivity of the checkboxes beneath the map
     */
    function mapOptionsClicked( type ) {
        let positiveTestsChecked = window.chckbxPositiveTests.checked;
        let wasteWaterChecked = window.chckbxWasteWater.checked;
        let wasteWaterBubblesChecked = window.chckbxWasteWaterBubbles.checked;
        let vaxxedChecked = window.chckbxVaccinatedPerCity.checked;
        let hospitalizedChecked = window.chckbxHospitalizedPerCity.checked;

        mapContainerVaccinatedPerCity.classList.add( 'hide' );
        mapContainerHospitalizedPerCity.classList.add( 'hide' );
        mapContainerPositiveTests.classList.add( 'hide' );
        mapContainerWasteWaterPerProvince.classList.add( 'hide' );

        window.chckbxPositiveTests.checked = false;
        window.chckbxVaccinatedPerCity.checked = false;
        window.chckbxHospitalizedPerCity.checked = false;
        window.chckbxWasteWater.checked  = false;

        if( ! wasteWaterChecked && ! hospitalizedChecked ) {
            wasteWaterChecked = true;
            type = 'waste-water';
        }

        switch( type ) {
            case 'waste-water-bubbles':
                toggleBubbles( wasteWaterBubblesChecked );
                wasteWaterChecked = true;
//                if( wasteWaterBubblesChecked ) wasteWaterChecked = true;
            case 'waste-water' :
            if ( wasteWaterChecked ) {
                mapContainerWasteWaterPerProvince.classList.remove( 'hide' );
                window.chckbxWasteWater.checked = true;
            } else {
                if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                    window.mapNL.series[0].update({ data:mapData });
                }
                mapContainerPositiveTests.classList.remove( 'hide' );
                window.chckbxPositiveTests.checked = true;
            }
            let titleText = 'Rioolwaterwaarden per provincie';
            if( wasteWaterBubblesChecked ) titleText += '<br>en per rioolwaterzuivering';
            window.mapNLWasteWater.title.update({ text:titleText });
            window.mapNLWasteWater.redraw();




                //mapContainerPositiveTests.classList.remove( 'hide' );
                //window.chckbxPositiveTests.checked = true;
            break;

            case 'positive-tests' :
                if ( positiveTestsChecked ) { 
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    window.chckbxPositiveTests.checked = true;
                }
                else { 
                    window.mapNL.series[0].update({ data:[] });
                }
                mapContainerPositiveTests.classList.remove( 'hide' );
                break;
            case 'vaccinated-per-city' :
                if ( vaxxedChecked ) {
                    mapContainerVaccinatedPerCity.classList.remove( 'hide' );
                    window.chckbxVaccinatedPerCity.checked = true;
                } else {
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    mapContainerPositiveTests.classList.remove( 'hide' );
                    window.chckbxPositiveTests.checked = true;
                }
                return;
            case 'hospitalized-per-city' : 
                if ( hospitalizedChecked ) {
                    mapContainerHospitalizedPerCity.classList.remove( 'hide' );
                    window.chckbxHospitalizedPerCity.checked = true;
                } else {
                    if( window.mapNL.series[0].points[ 0 ].citycode == undefined ) {
                        window.mapNL.series[0].update({ data:mapData });
                    }
                    mapContainerPositiveTests.classList.remove( 'hide' );
                    window.chckbxPositiveTests.checked = true;
                }
                return;
        }
        /*
        let titleText = ( positiveTestsChecked ) ? 'Positieve testen afgelopen 7 dagen per 100.000 inwoners' :'';
        if ( positiveTestsChecked && wasteWaterChecked ) titleText += ' /<br>';
        titleText += ( wasteWaterChecked ) ? 'Virusdeeltjes in het rioolwater per 100.000 inwoners' :'';
        window.mapNL.title.update({ text:titleText });
        window.mapNL.redraw();
        */
    }


    /**
     * Add or remove waste water bubbles.
     * use param 'toggle' to force bubbles visible (true) or hidden (false)
     */
    function toggleBubbles( toggle = null ) {
        let seriesBubblesToggle = ( window.mapNLWasteWater.series.length == 1 ) ? true :false;
        if( toggle === true ) seriesBubblesToggle = true;
        else if( toggle === false ) seriesBubblesToggle = false;

        if ( seriesBubblesToggle && window.mapNLWasteWater.series.length == 1 ) {
           //CL.display.scrollToTop();
            setTimeout( function() {
                window.mapNLWasteWater.addSeries( seriesBubbles,true );
            },1 );
            //CL.charts.toggleWasteWater( true );
        }
        if ( ! seriesBubblesToggle && window.mapNLWasteWater.series.length == 2 ) {
            window.mapNLWasteWater.get('waste-water-bubbles').remove();
           // CL.charts.toggleWasteWater( false );
        }
        CL.main.redrawTitles();
    }


    /**
     * Zoom bubbles:Enlarge the bubbles if user zooms in.
     * Notice:zoom factor is other way around. Zoom in means smaller values. ;-)
     */
    function zoomBubbles( currentZoomStatus = 100 ) {
        if ( window.mapNLWasteWater.series.length == 1 ) return true;  // no active bubbles
        
        if ( currentZoomStatus < 25  && 25 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'6%',
                maxSize:'6%'
            }, true );
            zoomInBubbles = 25;
        } else if ( currentZoomStatus > 25 && currentZoomStatus < 50  && 50 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'4%',
                maxSize:'4%'
            }, true );
            zoomInBubbles = 50;
        } else if ( currentZoomStatus > 50 && 100 != zoomInBubbles ) {
            window.mapNLWasteWater.series[1].update({
                minSize:'2%',
                maxSize:'2%',
            },true );
            zoomInBubbles = 100;
        }
        return;
    }























    /******* THE MAPS ******* THE MAPS ******* THE MAPS ******* THE MAPS *******/



   /** 
     * create the map positive tests
     */

    maps.createMapHospitalizedPerCity = function() {
        console.log( 'mapDataHospitalizedPerProvince' );
        console.log( mapDataHospitalizedPerProvince );
        if ( ! mapDataHospitalizedPerProvince ) return;
        const factor = 1;
        window.mapNLHospitalized = Highcharts.mapChart('map-container-hospitalized-per-city',{
            chart:{ map:'provincie' },
            title:{
                text:'Ziekenhuisopnames per provincie',
                style:{ fontFamily:'arial',fontSize:'12px',color:'#666',fontWeight:'bold' }
            },
            credits:{
                text:today + ' corona-lokaal.nl',
                href:''
            },
            legend,
            mapNavigation,
            xAxis:{
                events:{
                    afterSetExtremes:function(event){
                        if( "setExtremes" == event.type ) {
                            let currentZoomStatus = Math.round( 100 *  ( event.max - event.min) / ( event.dataMax - event.dataMin)  );
                                zoomBubbles( currentZoomStatus );
                        }
                    }
                }
            },
            colors:['#ffffff','#fdf5c8','#fed066','#e58d35','#cc3d19','#a11a14','#580000','#000000'],
            colorAxis:{
            dataClassColor:'category',
            dataClasses:[{ to:5*factor },
                { from:5*factor,to:20*factor },
                { from:20*factor,to:40*factor },
                { from:40*factor,to:60*factor },
                { from:60*factor,to:80*factor },
                { from:80*factor,to:100*factor},
                { from:100*factor,to:120*factor},
                { from:120*factor }],
/*
                dataClasses:[{ to:5*factor },
                    { from:5*factor,to:10*factor },
                    { from:10*factor,to:20*factor },
                    { from:20*factor,to:30*factor },
                    { from:30*factor,to:40*factor },
                    { from:40*factor,to:50*factor },
                    { from:50*factor,to:75*factor},
                    { from:75*factor }],
*/


            // max:1000,min:0,startOnTick:false,endOnTick:false
            },
            plotOptions,
            series:[{
                data: mapDataHospitalizedPerProvince,
                // data:[],
                joinBy:['statcode','pv_code'],
                name:'Ziekenhuisopnames',
                id:'hospital-admission-per-province-map',
                borderColor:'#bbb',
                borderWidth:0.4,
                dataLabels:{ enabled:false },
                states:{
                    hover:{ color:'#369cd2' }
                },
                events:{
                    click:function( event ) {
                        if ( ! window.CL.touchscreen ) {
                            // domContentStart = performance.now();
                            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                        } else { // create a 'double touch' action for touchscreens ( a single 'touch' equals mouse hover on PC's )
                            if ( window.clicked == event.point.pv_code ) {
                                clearTimeout( window.clickTimer );
                                // domContentStart = performance.now();
                                CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                                window.clicked = false;
                            } else {
                                window.clicked = event.point.pv_code;
                                window.clickTimer = setTimeout( function(){
                                    window.clicked = false;
                                },500 );
                            }
                        }
                    }
                },
                tooltip:{
                    distance:70,
                    style:{ color:'#666',lineHeight:'20px' },
                    pointFormatter:function(){
                        let s =  '<span style="font-weight:bold;font-size:14px;"> ' + this.pv_name + '</span>'
                        + '<br><span style="color:#666;font-size:12px;">• '+ this.daily_intake
                        + ' gemiddeld over zeven dagen</span>'
                        + '<br><span style="color:#666;font-size:12px;">• Dat is '+ this.value
                        + ' per miljoen inwoners.</span><br>'
                        if ( window.CL.touchscreen ) {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">dubbel klik de provincie om de grafieken van ' + this.cityname  + ' te bekijken</span><br>';
                        } else {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(klik voor de grafieken)</span><br>';
                        }
                        return s;
                    }
                }

            }]   // series
        }); 
    };    // create the map positive tests










   /** 
     * create the map positive tests
     */
    maps.createMapWasteWaterProvince = function() {
        const factor = 1;
        window.mapNLWasteWater = Highcharts.mapChart('map-container-waste-water-per-province',{
            chart:{ map:'provincie' },
            title:{
                text: 'Rioolwaterwaarden per provincie',
                style:{ fontFamily:'arial',fontSize:'12px',color:'#666',fontWeight:'bold' }
            },
            credits:{
                text:today + ' corona-lokaal.nl',
                href:''
            },
            legend,
            mapNavigation,
            xAxis:{
                events:{
                    afterSetExtremes:function(event){
                        if( "setExtremes" == event.type ) {
                            let currentZoomStatus = Math.round( 100 *  ( event.max - event.min) / ( event.dataMax - event.dataMin)  );
                                zoomBubbles( currentZoomStatus );
                        }
                    }
                }
            },
            colors:['#ffffff','#fdf5c8','#fed066','#e58d35','#cc3d19','#a11a14','#580000','#000000'],
            colorAxis:{
            dataClassColor:'category',


            dataClasses:[{ to:100*factor },
                { from:100*factor,to:400*factor },
                { from:400*factor,to:800*factor },
                { from:800*factor,to:1300*factor },
                { from:1300*factor,to:1800*factor },
                { from:1800*factor,to:2400*factor},
                { from:2400*factor,to:3000*factor},
                { from:3000*factor }],


/*
            dataClasses:[{ to:1 },
                { from:1,to:6*factor },
                { from:6*factor,to:15*factor },
                { from:15*factor,to:25*factor },
                { from:25*factor,to:50*factor },
                { from:50*factor,to:75*factor },
                { from:75*factor,to:100*factor},
                { from:100*factor }],
*/
            // max:1000,min:0,startOnTick:false,endOnTick:false
            },
            plotOptions,
            series:[{
                data: mapDataWasteWaterPerProvince,
                // data:[],
                joinBy:['statcode','pv_code'],
                name:'Virusniveau rioolwater',
                id:'waste-water-per-province-map',
                borderColor:'#bbb',
                borderWidth:0.4,
                dataLabels:{ enabled:false },
                states:{
                    hover:{ color:'#369cd2' }
                },
                events:{
                    click:function( event ) {
                        if ( ! window.CL.touchscreen ) {
                            // domContentStart = performance.now();
                            CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                            CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', event.point.pv_code );
                        } else { // create a 'double touch' action for touchscreens ( a single 'touch' equals mouse hover on PC's )
                            if ( window.clicked == event.point.pv_code ) {
                                clearTimeout( window.clickTimer );
                                // domContentStart = performance.now();
                                CL.dataUtils.ajaxRequest( 'get', 'hospitalized', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'plant', event.point.pv_code );
                                CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', event.point.pv_code );
                                window.clicked = false;
                            } else {
                                window.clicked = event.point.pv_code;
                                window.clickTimer = setTimeout( function(){
                                    window.clicked = false;
                                },500 );
                            }
                        }
                    }
                },
                tooltip:{
                    distance:70,
                    style:{ color:'#666',lineHeight:'20px' },
                    pointFormatter:function(){
                        let s =  '<span style="font-weight:bold;font-size:14px;"> ' + this.pv_name
                        + '</span><br><span style="color:#666;font-size:12px;">• '+ this.value
                        + '  gemiddeld over zeven dagen'
                        + '</span><br><span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(betrouwbaarheid ' + this.reliability + '%)</span><br>';
                        if ( window.CL.touchscreen ) {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">dubbel klik de provincie om de grafieken van ' + this.pv_name  + ' te bekijken</span><br>';
                        } else {
                            s += '<span style="color:#fff;font-size:10px;">• </span><span style="color:#666;font-size:10px;">(klik voor de grafieken)</span><br>';
                        }
                        return s;
                    }
                }

            }]   // series
        }); 
    };    // create the map positive tests








}( window.CL.maps = window.CL.maps || {} ) );
/** Corona Lokaal Maps Namespace / Namespace: CLmap */




