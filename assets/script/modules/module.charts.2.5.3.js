/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";

/**
 * Corona Lokaal Charts Namespace for all charts
 * Namespace: CL.charts
 */

( function ( charts, CL ) {
    // const hideChartReported = true;


    let containerWasteWater, containerInfectionRadarPositiveTests, containerHospitalized; // container for some charts. Can slide up and down
    let wastewater = {};    // holds chart data for waste water charts
    let infectionRadarPositiveTests = {};    // holds chart data for infection radar positive tests  charts
    let city = {};      // holds data for charts for a city
    let hospitalized;
    let chartLabels = [];   // holds the chart labels.
    // let allCharts; 
    let canvasReported,canvasHospitalized,canvasDeceased,canvasWasteWater,canvasInfectionRadarPositiveTests;
    let allCanvasses, allCharts; // array of all the charts
    let yMaxGlobal; // holds max for the y-axis (usually the max country value), so when clicking through waste water charts of provinces or country, the scale of y-axis doesn't change.
    //let delayChartWasteWater = true; // boolean to delay showing waste water chart if it is visible from the start. 

    // zoom control
    let chartSettingsZoomDays,chartResetButton,chartAnnotationsButton,zoomSymbolsList;
    let zoomDays = 50;
    let zoomIn = true;
    let zoomLevel = -1;
    let showAnnotations = 1;




    // some settings for navigating the map using touchscreens

 
    // Setup charts configurations. See documentation for Chart.js: https://www.chartjs.org/docs/latest/configuration/
    function getDefaultChartSettings() {
        // for small screens
        let aspectRatio = ( window.innerWidth < 600 ) ? 1.7 : 2;
        return {
            type:'bar',
            options:{
                tag:'',     // edit kalahiri. Tag can be used to find the chart by tag
                annotation: { // https://github.com/chartjs/chartjs-plugin-annotation/tree/1ab782afce943456f958cac33f67edc5d6eab278
                    annotations: [],
                },
                responsive:true,
                maintainAspectRatio:true,
                aspectRatio:aspectRatio,
                title:{
                    display:true,
                    text:''
                },
                legend:{
                    display:false
                },
                tooltips:{
                    mode:'index',
                    intersect:true,
                    filter:function ( tooltipItem, data ) {
                        if ( data.datasets[tooltipItem.datasetIndex].label == 'hidden' ) return false;
                        return true;
                    },

                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if( data.datasets[ 1 ].label === dataWasteWater.datasets[1].label ) {
                                    label += Math.round(tooltipItem.yLabel );
                                } else {
                                    label += tooltipItem.yLabel;
                                }
                                return label;
                            }
                        }
                },
                scales:{
                    xAxes:[{
                        type: 'category',
                    }],
                    yAxes:[{
                        afterTickToLabelConversion: function( scale ) {
                            if( scale.options.ticks.max === undefined ) return; // flag so update knows a special action has to be taken: remove highest y-axis tick
                            // set the last (highest value) tick to null so it does not display.
                            scale.ticks[0] = null;
                            // need to do the same thing for this similiar array which is used internally
                            scale.ticksAsNumbers[0] = null;
                        },
                        ticks:{
                            precision:0,
                            suggestedMin:0,
                            min:0, // force only positive values
                            // beginAtZero:true
                            //steps:10,
                            //stepValue:5,
                           //  max:4000
                        }
                    }]
                },
            }
        };
    }
    let dataReported = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(0,69,102,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe meldingen per dag',
            backgroundColor:'rgba(54,156,210,0.5)'
        }]
    };
    let dataHospitalized = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(240,0,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe ziekenhuisopnames per dag',
            backgroundColor:'rgba(54,156,210,0.5)'
        }]
    };
    let dataDeceased = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(0,0,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'nieuwe overlijdens per dag',
            backgroundColor:'rgba(151,72,6,0.3)'
        }]
    };
    let dataWasteWater = {
        datasets:[{
            type:'line',
            label:'hidden',
            borderColor:'rgb(0,58,105)',
            borderWidth:2,
            fill:false,
            pointRadius:0,
            spanGaps:true
        },{
            type:'bar',
            label:'aantal virusdeeltjes per 100.000 inwoners',
            backgroundColor:'rgba(0,58,105,0.5)'
        }]
    };
    let dataInfectionRadarPositiveTests = {
        datasets:[{
            type:'line',
            label:'7-daags gemiddelde',
            borderColor:'rgba(100,40,0,0.8  )', //'rgba(240,100,0,1)',
            borderWidth:2,
            fill:false,
            pointRadius:0
        },{
            type:'bar',
            label:'percentage positieve tests',
            backgroundColor:'rgba(77,125,165,0.6)'
        }]
    };




    let annotations = [ // https://github.com/chartjs/chartjs-plugin-annotation/tree/1ab782afce943456f958cac33f67edc5d6eab278
        {
            value: "1/1",
            index: 293,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true, // true: between ticks before the selected value or index, false: on the tick of the selected value or index'
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2021",
        },
        {
            value: "23/3",
            index: 9,
            zoomLevel:1,
            heightAdjust:0,
            beforeTick: true,
            text: "'Intelligente lockdown'",
        },
        {
            value: "1/6",
            index: 80,
            zoomLevel:1,
            heightAdjust:40,
            beforeTick: true,
            text: "Versoepelen",
        },
        {
            value: "1/6",
            index: 80,
            zoomLevel:1,
            heightAdjust:55,
            beforeTick: true,
            text: "'intelligente lockdown'",
        },
        {
            value: "20/9",
            index: 190,
            zoomLevel:3,
            heightAdjust:40,
            beforeTick: true,
            text: "Horeca uurtje eerder dicht",
        },
        {
            value: "14/10",
            index: 214,
            zoomLevel:1,
            heightAdjust:20,
            text: "'Gedeeltelijke lockdown'",
        },
        {
            value: "15/12",
            index: 274,
            zoomLevel:2,
            heightAdjust:40,
            text: "'Harde lockdown'",
        },
        {
            value:"6/1",
            index:298,
            zoomLevel:2,
            heightAdjust:60,
            text: "start vaccinaties",
        },
        {
            value:"23/1",
            index:315,
            zoomLevel:1,
            heightAdjust:40,
            text:"Avondklok",
        },
        {
            value: "15/2",
            index: 338,
            zoomLevel:2,
            heightAdjust:20,
            text: "Alfa-variant dominant",
        },
        {
            value:"28/4",
            index:410,
            zoomLevel:2,
            beforeTick: true,
            heightAdjust:0,
            text:"Einde avondklok, terrassen open",
        },
        {
            value: "25/6",
            index: 468,
            zoomLevel:1,
            heightAdjust:20,
            text: "'Dansen met Janssen'",
        },
        {
            value: "23/6",
            index: 466,
            zoomLevel:2,
            heightAdjust:60,
            text: "Delta-variant dominant",
        },
        {
            value: "9/7",
            index: 482,
            zoomLevel:2,
            heightAdjust:0,
            text: "Nachtleven weer dicht",
        },
        {
            value: "29/8",
            index: 533,
            zoomLevel:3,
            heightAdjust:20,
            text: "85% van 18+ gevaccineerd",
        },
        {
            value: "25/9",
            index: 550,
            zoomLevel:1,
            heightAdjust: 40,
            beforeTick:true,
            text: "Invoering 3G",
        },
        {
            value: "25/9",
            index: 550,
            zoomLevel:2,
            heightAdjust: 55,
            beforeTick:true,
            text: "Einde 1,5 meter",
        },
        {
            value: "18/11",
            index: 614,
            zoomLevel:3,
            heightAdjust: 0,
            text: "Start boostercampagne",
        },
        {
            value: "14/11",
            index: 610,
            zoomLevel:2,
            heightAdjust: 20,
            beforeTick:true,
            text: "Horeca om 20.00 uur dicht",
        },
        {
            value: "28/11",
            index: 624,
            zoomLevel:2,
            heightAdjust: 40,
            beforeTick:true,
            text: "Avond-lockdown",
        },
        {
            value: "19/12",
            index: 645,
            zoomLevel: 1,
            heightAdjust: 60,
            beforeTick: true,
            text: "Omikron-lockdown",
        },
        {
            value: "28/12",
            index: 654,
            zoomLevel: 2,
            heightAdjust: 40,
            text: "Omikron dominant",
        },
        {
            value: "1/1",
            index: 658,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2022",
        },
        {
            value: "10/1",
            index: 667,
            zoomLevel:3,
            heightAdjust: 20,
            beforeTick:true,
            text: "Scholen open",
        },
        {
            value: "15/1",
            index: 672,
            zoomLevel:3,
            heightAdjust: 40,
            beforeTick:true,
            text: "Winkels open",
        },
        {
            value: "26/1",
            index: 683,
            zoomLevel:2,
            heightAdjust: 0,
            beforeTick:true,
            text: "Alles open tot 22.00",
        },
        {
            value: "18/2",
            index: 706,
            zoomLevel:3,
            heightAdjust:40,
            beforeTick:true,
            text: "Alles open",
        },
        {
            value: "18/2",
            index: 706,
            zoomLevel:3,
            heightAdjust:55,
            beforeTick:true,
            text: "tot 01.00",
        },
        {
            value: "25/2",
            index: 713,
            zoomLevel:2,
            heightAdjust: 0,
            beforeTick:true,
            text: "Alles open",
        },
        {
            value: "27/2",
            index: 715,
            zoomLevel:3,
            heightAdjust: 20,
            beforeTick:true,
            text: "Carnaval",
        },
        {
            value: "23/3",
            index: 739,
            zoomLevel:1,
            heightAdjust: 10,
            beforeTick:true,
            text: "Einde",
        },
        {
            value: "23/3",
            index: 739,
            zoomLevel:1,
            heightAdjust: 25,
            beforeTick:true,
            text: "coronamaatregelen",
        },
        {
            value: "1/1",
            index: 1023,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2023",
        },
        {
            value: "1/1",
            index: 1388,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2024",
        },
        {
            value: "1/1",
            index: 1754,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2025",
        },
        {
            value: "1/1",
            index: 2119,
            zoomLevel: 0,
            heightAdjust:0,
            beforeTick: true,
            borderColor: 'rgba(0,0,0,0.3)',
            fontColor: "#999",
            text: "2026",
        },

    ];


    /**
     * DOM content is loaded so select elements and add event listeners
     */
    charts.initDOMLoaded = function() {
        canvasWasteWater = document.getElementById('canvas-waste-water');
        canvasReported = document.getElementById('canvas-reported');
        canvasHospitalized = document.getElementById('canvas-hospitalized');
        canvasDeceased = document.getElementById('canvas-deceased');
        canvasInfectionRadarPositiveTests = document.getElementById('canvas-infection-radar-positive-tests');
        allCanvasses = [ canvasReported, canvasHospitalized, canvasDeceased, canvasWasteWater, canvasInfectionRadarPositiveTests ];
        // allCanvasses = [ canvasWasteWater, canvasHospitalized, canvasDeceased ];

        zoomSymbolsList = document.querySelectorAll('.zoom-container .zoom');  // array of magnifier symbols
        chartSettingsZoomDays = document.getElementById('chart-setting-zoom-days' );  // input setting zoom days
        chartResetButton = document.querySelectorAll('.zoom-container .chart-reset');  // array of chart reset buttons
        //chartAnnotationsButton = document.querySelectorAll('.zoom-container .chart-annotations');  // array of chart reset buttons
        chartAnnotationsButton = document.getElementById('chart-annotations-button');  // array of chart reset buttons

        containerWasteWater = document.getElementById('container-waste-water'); // container with chart for waste water chart.
        containerHospitalized = document.getElementById('container-hospitalized'); // container with chart for waste water chart.
        containerInfectionRadarPositiveTests = document.getElementById('container-infection-radar-positive-tests'); // container with chart for infection radar positive tests.

        // get the cookies to determine some settings
        const cookieZoomDays = CL.dataUtils.getCookie( 'zoomDays' );
        if ( cookieZoomDays > 2  && cookieZoomDays < 250 ) {
            zoomDays = cookieZoomDays;
        }
        chartSettingsZoomDays.value = zoomDays;

        // add chart settings box on click settings icon.
        /*
        document.getElementById('submit-chart-settings').addEventListener( 'click',function () {
            setZoomDays( chartSettingsZoomDays.value );
        });
        */

        // add zoom action to charts.
        document.querySelectorAll( '.zoom' ).forEach( item => {
            item.addEventListener('click',function() {
                charts.zoom();
            });
        });


        document.getElementById('save-button').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            const canvasElement = document.getElementById( 'canvas-waste-water' );
            CL.interactions.saveCanvasAsImage( "grafiek.rioolwaterwaarden", canvasElement );
            const targetPopup = event.target.closest( '.popup' );
            CL.display.popupToggle ( targetPopup.id );
        });

        document.getElementById('label-chart-setting-zoom-days').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            chartSettingsZoomDays.focus();
            chartSettingsZoomDays.select();
        });
        chartSettingsZoomDays.addEventListener( 'click', ( event ) => {
            chartSettingsZoomDays.focus();
            chartSettingsZoomDays.select();
        });
        chartSettingsZoomDays.addEventListener( 'blur', function( event ) {
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
        });
        chartSettingsZoomDays.addEventListener( 'keydown', function( event ) {
            if ( 'Tab' === event.key ) {
                event.preventDefault();
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            }
            if ( 'Enter' === event.key ) {
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle ( targetPopup.id );
            }
        });

        // chartAnnotationsButton.forEach( item => {
        chartAnnotationsButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            showAnnotations++
            if( showAnnotations > 2 ) showAnnotations = 0;
            syncAllCharts();
        });

        // add reset action to charts.
        chartResetButton.forEach( item => {
            item.addEventListener('click',function() {
                reset();
            });
        });
        document.getElementById('total-chart-button').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            reset();
            const targetPopup = event.target.closest( '.popup' );
            CL.display.popupToggle ( targetPopup.id );
        });

        document.getElementById('toggle-chart-hospitalized').addEventListener( 'click', ( event ) => {
            event.preventDefault();
            CL.display.slideUpDown( containerHospitalized, 'toggle', 400, 100, containerWasteWater ) ;
        });



        // add chart settings box on click settings icon.
        document.querySelectorAll( '.chart-settings' ).forEach( item => {
            item.addEventListener( 'click',event => {
                CL.display.popupToggle ( 'popupChartSettings',event.clientX,event.clientY );

            });
        });

        /*
        chartSettingsZoomDays.addEventListener( 'keypress',function( event ) {
            if ( 'Enter' === event.key ) {
                const targetPopup = event.target.closest( '.popup' );
                CL.display.popupToggle( targetPopup.id );
                setZoomDays( chartSettingsZoomDays.value );
                chartSettingsZoomDays.blur();
            }
        });
        */


        // add close action to waste water chart so it can be hidden
        let buttonCloseWasteWaterChart = document.getElementById( 'close-chart');
        //console.log( buttonCloseWasteWaterChart );
        if( buttonCloseWasteWaterChart ) {
            buttonCloseWasteWaterChart.addEventListener('click',event => {
                // const target = event.target.closest( '.canvas-container' );
                // console.log( target );
                CL.maps.hide( 'waste-water' );
                // CL.display.popupToggle ( targetPopup.id );
            });
        }

        allCanvasses.forEach( function( canvas ) {
            if( canvas ) {
                canvas.addEventListener("wheel", zoomAction);
                canvas.addEventListener('mousedown', event => { panAction( event, 'start' ) });
                canvas.addEventListener('mousemove', event => { panAction( event, 'panning' ) });
                canvas.addEventListener('mouseup', event => { panAction( event, 'end' ) });
                canvas.addEventListener('touchstart', event => { panAction( event, 'start' ) });
                canvas.addEventListener('touchmove', event => { panAction( event, 'panning' ) });
                canvas.addEventListener('touchend', event => { panAction( event, 'end' ) });

                canvas.addEventListener('mousemove', interactionHelp );

            }
        });

    }; // DOMContentLoaded


    /**
     * Event window loaded: All elements for teh webpage are loaded
     * Draw the charts as soon as the page is loaded.
     */
    charts.initWindowLoaded = function() {
        // create the background color for the charts
        /*
        Chart.register({
            id: 'custom_canvas_background_color_white',
            beforeDraw:function (chart )  {
                const ctx = chart.canvas.getContext('2d');
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                if( ctx.canvas.id == 'canvas-waste-water') {
                    ctx.fillStyle = '#fff7e9';
                } else {
                    ctx.fillStyle = '#fff';
                }
                ctx.fillRect(0, 0, chart.width, chart.height);
                ctx.restore();
            }
        });
        */


        Chart.plugins.register({
            beforeDraw:function ( chartInstance ) {
                const ctx = chartInstance.chart.ctx;
                if( ctx.canvas.id == 'canvas-waste-water') {
                    ctx.fillStyle = '#fff7e9';
                } else if( ctx.canvas.id == 'canvas-infection-radar-positive-tests') {
                    ctx.fillStyle = '#fff'; // '#f2f9ff';
                } else {
                    ctx.fillStyle = '#fff';
                }
                ctx.fillRect(0,0,chartInstance.chart.width,chartInstance.chart.height);
            }
        });
    
    


        // draw the charts 'Waste Water'
        const chartWasteWaterSettings = Object.assign({},getDefaultChartSettings(),{ data:dataWasteWater });
        window.chartWasteWater = new Chart( canvasWasteWater.getContext('2d'),chartWasteWaterSettings );
        window.chartWasteWater.options.tag = 'chartWasteWater'; // used to find the chart by tag name.
        window.chartWasteWater.update();
        // draw the chart 'Reported'
        const chartReportedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataReported });
        window.chartReported = new Chart( canvasReported.getContext('2d'),chartReportedSettings );
        // draw the chart 'Hospitalized'.
        const chartHospitalizedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataHospitalized });
        window.chartHospitalized = new Chart( canvasHospitalized.getContext('2d'),chartHospitalizedSettings );
        // draw the chart 'Deceased'.
        const chartDeceasedSettings = Object.assign({},getDefaultChartSettings(),{ data:dataDeceased });
        window.chartDeceased = new Chart( canvasDeceased.getContext('2d'),chartDeceasedSettings );
        // draw the chart 'InfectionRadar PositiveTests'.
        const chartInfectionRadarPositiveTestsSettings = Object.assign({},getDefaultChartSettings(),{ data:dataInfectionRadarPositiveTests });
        window.chartInfectionRadarPositiveTests = new Chart( canvasInfectionRadarPositiveTests.getContext('2d'),chartInfectionRadarPositiveTestsSettings );
        window.chartInfectionRadarPositiveTests.options.layout.padding.left = 20; // set some padding to line up the different charts.
        window.chartInfectionRadarPositiveTests.update();
        // allCharts = [ window.chartReported, window.chartHospitalized, window.chartDeceased, window.chartWasteWater ];
        allCharts = [ window.chartWasteWater, window.chartHospitalized, window.chartDeceased, window.chartInfectionRadarPositiveTests ];



        // click assist "klik dit eens aan!"
        setTimeout( function(){
            const clickAssist = document.getElementById('click-assist');
            if( null !== clickAssist ) clickAssist.style.display = 'none';
        }, 3000 );

    }; // windowLoaded




    function selectAnnotationsToShow() {
        let annotationsArray = [];
        annotations.filter( values => zoomLevel >= values.zoomLevel ).forEach( function( values ) {
            let annotation = {
                drawTime: "afterDatasetsDraw",
                type: "line",
                mode: "vertical",
                scaleID: "x-axis-0",
                value: "22/1",
                beforeTick: false,
                borderWidth: 1,
                borderColor: "red",
                label: {
                        enabled: true,
                        position: "top",
                        backgroundColor: 'rgba(255,255,255,0.75)',
                        fontSize: 10,
                        fontColor: "#777",
                        yAdjust:-20
                      },
            };

            annotation.label.content = values.text;
            annotation.index = values.index;
            annotation.heightAdjust = values.heightAdjust + 20;
            if( typeof values.beforeTick !== 'undefined' ) annotation.beforeTick = values.beforeTick;
            if( typeof values.borderColor !== 'undefined' ) annotation.borderColor = values.borderColor;
            if( typeof values.fontColor !== 'undefined' ) annotation.label.fontColor = values.fontColor;

            annotationsArray.push( annotation );
        });
        allCharts.forEach( chart => chart.config.options.annotation.annotations = annotationsArray );
    }








    /**
     * reset all charts to their original ( = full ) time period
     */
    function reset() {
        syncAllCharts( { min: 0, max: city.data.chartLabels.length - 1 } );
        zoomIn = false;
        redrawZoomSymbols();
    }


    /**
     * Zoom charts in or out.
     */
    charts.zoom = function () {
        zoomIn = ! zoomIn;
        showAnnotations = 0;
        if( zoomIn ) {
            let min = city.data.chartLabels.length - zoomDays;
            syncAllCharts( { min: min, max: city.data.chartLabels.length - 1 } );
        } else {
            syncAllCharts( { min: 0, max: city.data.chartLabels.length - 1 }  );
        }
        redrawZoomSymbols(); // redraw the zoom-symbols on the charts
    };


    /**
     * redraw the zoom-symbols on the charts
     */
    function redrawZoomSymbols() {
        const char = ( zoomIn ) ? "&#xf010" :"&#xf00e";
        const info = ( zoomIn ) ? "zoom uit van begin maart 2020 tot nu" :"zoom in en laat alleen de laatste periode zien. \nHet aantal dagen is bij het radartje in te stellen";
        for ( let i = 0; i < zoomSymbolsList.length; ++i ) {
            zoomSymbolsList[i].innerHTML = char;
            zoomSymbolsList[i].title = info;
        }
    }


    /**
     * Set the number of days for the zoom function to zoom in to.
     * @param { integer } days
     */
    function setZoomDays( days = 60 ) {
        days = Math.round( +days );
        if( days < 2 ) {
            days = 2;
        }
        if( days > city.data.chartLabels.length - 1 ) {
            days = city.data.chartLabels.length - 1;
        }
        zoomDays = days;
        CL.dataUtils.setCookie( 'zoomDays',zoomDays );
        let min = city.data.chartLabels.length - zoomDays;
        syncAllCharts( { min: min, max: city.data.chartLabels.length - 1 }  );
        chartSettingsZoomDays.value = zoomDays; // set form field with new value
    }





    /**
     * Zoom in or out on the charts.
     * Currently only available through movement of the mouse wheel
     * 
     * @param {event} event 
     */
    function zoomAction (event) {
        // console.log( 'zoomin' );
        if (event.cancelable) {
            event.preventDefault();
        }
        // Firefox always fires wheel event twice: First without delta and right after once with delta properties.
        if (typeof event.deltaY === 'undefined') {
            return;
        }
        yMaxGlobal = undefined;

         // allCharts = [ window.chartReported, window.chartHospitalized, window.chartDeceased, window.chartWasteWater ];
      
        // const labelLength = window.chartReported.config.data.labels.length - 1;
        const labelLength = allCharts[1].config.data.labels.length - 1;
        // console.log( labelLength );
        // let scale = window.chartReported.scales['x-axis-0'];
        let scale = allCharts[1].scales['x-axis-0'];
        // console.log( scale );
        const currentValues = {
            length: scale.options.ticks.maxIndex - scale.options.ticks.minIndex,
            min: scale.options.ticks.minIndex,
            max: scale.options.ticks.maxIndex
        }
        // console.log( currentValues );
        let wheelMove = ( event.deltaY > 0 ) ? -1 : 1;  // zoom in : 1, zoom out : -1
        // determine mouse position on mouse wheel action
        let mousePosition = panzoomPositionOnChart( allCharts[1], event );
        if ( ! mousePosition.onChart ) return false; // mouse click outside chart
        let steps = Math.round( currentValues.length / 10 );
        steps = Math.max( steps, 5 );

        // calculate new values for zoom in or zoom out
        let newValues = {}
        if ( wheelMove < 0 ) {   // zoom out
            newValues.length = Math.round( currentValues.length + steps );
            if( mousePosition.position == 'center' ) { // mouse position: center
                newValues.min =  currentValues.min - Math.round( steps / 2 );
            } else if( mousePosition.position == 'left' ) { // mouse position: left
                newValues.min = currentValues.min;
            } else { // mouse position: right
                newValues.min = currentValues.min - steps;
            }
        } else {  // zoom in
            newValues.length = Math.round( currentValues.length - steps );
            if( mousePosition.position == 'center' ) { // mouse position: center
                newValues.min =  currentValues.min + Math.round( steps / 2 );
            } else if( mousePosition.position == 'left' ) { // mouse position: left
                newValues.min = currentValues.min;
            } else { // mouse position: right
                newValues.min = currentValues.min + steps;
            }
        }
        newValues = panzoomRangeLimiter( newValues.min, newValues.length, labelLength );
        // console.log( newValues );
        syncAllCharts( newValues );
        return;
    }

    let chartArea = {};
    let pan = {
        panning: false
    };
    /**
     * Panning of the charts to the left or the right.
     * At the moment only available on mouse click/movement
     * 
     * @param {*} event 
     * @param {*} status 
     */
    const currentTouchPosition = {}
    function panAction( event, status ) {
        if ('MouseEvent' === event.constructor.name && event.cancelable) {
            event.preventDefault();
        }
        if ( 'TouchEvent' === event.constructor.name && event.touches.length > 1) {  // Ignore multi ('two-fingers') touch events
            return;
        }
        yMaxGlobal = undefined;


        if( 'panning' == status && pan.panning ) {
            if( 'TouchEvent' === event.constructor.name ) {
                if( 'touchmove' === event.type ) {
                    event.movementX = event.targetTouches[0].pageX - currentTouchPosition.PageX;
                    //event.movementY = touch.pageY - previousTouch.pageY;
                    currentTouchPosition.PageX = event.targetTouches[0].pageX;
                    event.buttons = 1;
                } else {
                    return;
                }
            }
            if( event.buttons == 1 ) {
                pan.deltaX += event.movementX; // sum of mousemouvement
                if( Math.abs(pan.deltaX) > chartArea.barWidth ) { // mousemovent large enough? => panning
                    let deltaIndex = Math.round( pan.deltaX / chartArea.barWidth );
                    let min =  pan.currentValues.min - deltaIndex;
                    let newValues = panzoomRangeLimiter( min, pan.currentValues.length, pan.labelLength );

                    syncAllCharts( newValues );
                    pan.currentValues = newValues;
                    pan.deltaX = 0;
                }
                return;
            } else {
                status = 'end';
            }
        }
        if( 'start' == status ) {
            // console.log( event );
            let mousePosition = panzoomPositionOnChart( allCharts[1], event );
            //console.log( mousePosition );
            //console.log( event );
            if( 'TouchEvent' === event.constructor.name ) {
                currentTouchPosition.PageX = event.targetTouches[0].pageX;
            }
            //console.log( mousePosition );
            if ( ! mousePosition.onChart ) return false; // mouse click outside chart
            event.target.style.cursor = 'ew-resize';

            pan.labelLength = allCharts[1].config.data.labels.length - 1;
            pan.scale = allCharts[1].scales['x-axis-0'];
            pan.currentValues = {
                length: pan.scale.options.ticks.maxIndex - pan.scale.options.ticks.minIndex,
                min: pan.scale.options.ticks.minIndex,
                max: pan.scale.options.ticks.maxIndex
            }
            chartArea.barWidth = ( allCharts[1].chartArea.right - allCharts[1].chartArea.left ) / pan.currentValues.length;
            pan.panning = true;
            pan.offsetX = event.offsetX;
            pan.deltaX = 0;
        }
        if( 'end' == status ) {
            event.target.style.cursor = 'auto'; // reset cursor
            chartArea = {};
            pan = {
                panning: false
            };
        }
        return;
    }


    function interactionHelp( event ) {
        if ('MouseEvent' === event.constructor.name && event.cancelable) {
            event.preventDefault();
        }
        if ( 'TouchEvent' === event.constructor.name && event.touches.length > 1) {  // Ignore multi ('two-fingers') touch events
            return;
        }
        allCharts.forEach( (item) => {
            if( event.target == item.canvas ) {
                CL.display.popupShowShort('popup-chart-help',0,0,false,
                        event.target, event.sourceCapabilities.firesTouchEvents );
                // One time only so remove the eventlisteners after first call.
                item.canvas.removeEventListener('mousemove', interactionHelp );
            }
        });
        /*
        allCharts.forEach( (item) => {
            if( event.target == item.canvas ) {
                const boundingClientRect = item.canvas.getBoundingClientRect();
                const left = boundingClientRect.left + ( boundingClientRect.width * 0.1 );
                const top = boundingClientRect.top + ( boundingClientRect.height * 0.2 );
                let popupShort;
                // event.sourceCapabilities.firesTouchEvents == true if touch event has fired
                popupShort = ( event.sourceCapabilities.firesTouchEvents )
                            ? document.getElementById( 'popup-chart-help-mobile' )
                            : document.getElementById( 'popup-chart-help' );
                popupShort.style.left = left + 'px';
                popupShort.style.top = top + 'px';
                popupShort.style.display = 'block';
                popupShort.classList.add('is-visible');
                setTimeout( function() {
                    popupShort.classList.remove('is-visible');
                    setTimeout( function() {
                        popupShort.style.display = 'none';
                    },2000);
                },3000 );
            }
        });
        // One time only so remove the eventlisteners after first call.
        allCanvasses.forEach( function( canvas ) {
            if( canvas ) {
                canvas.removeEventListener('mousemove', interactionHelp );
            }
        });
        */
    }


    /**
     * Get the position of the mouse on a chart. 
     * Is it on the chart area? If yes, it is on the right, center or on the left
     * 
     * @param {chart} chart 
     * @param {event} event
     * 
     * @return { bool } onChart is true if the mouse is on the chart area 
     * @return { enum } position is the position on the chart: 'left', 'center' or 'right'  
     */
    function panzoomPositionOnChart( chart, event ) {
        //console.log( event.target.getBoundingClientRect() );
        // console.log( event );
        //console.log( '----' );
        if( 'TouchEvent' === event.constructor.name ) {

            const rect = event.target.getBoundingClientRect();
            event.offsetX = event.targetTouches[0].clientX - rect.left;
            event.offsetY = event.targetTouches[0].clientY - rect.top;
            //console.log( rect.top );

        }

        //console.log( event );
        const chartArea = {}
        chartArea.left = chart.chartArea.left;
        chartArea.right = chart.chartArea.right;
        chartArea.top = chart.chartArea.top;
        chartArea.width = chart.chartArea.right - chart.chartArea.left;
        chartArea.height = chart.chartArea.bottom - chart.chartArea.top;
        chartArea.center = {
            x: chartArea.width / 2,
            margin: chartArea.width / 5,    // margin around center is also center
        } 
        let mousePosition = {
            x: event.offsetX - chartArea.left,
            y: event.offsetY - chartArea.top,
        }
        if( mousePosition.x < 0 || mousePosition.x > chartArea.width ||
            mousePosition.y < 0 || mousePosition.y > chartArea.height  ) {
            return { onChart: false, position: '' };         // check if click outside chart area
        }
        if ( mousePosition.x < chartArea.center.x - chartArea.center.margin ) {
            mousePosition.relative = 'left';
        } else if ( mousePosition.x > chartArea.center.x + chartArea.center.margin ) {
            mousePosition.relative = 'right';
        } else mousePosition.relative = 'center';
        return { onChart: true, position: mousePosition.relative };  
    }

            

    /**
     * To prevent the zooming and panning going of the scale.
     * 
     * @param {int} min 
     * @param {int} length 
     * @param {int} maxLength
     * 
     * @return { int } min: the min value of the scale of the chart
     * @return { int } max: the max value of the scale of the chart
     * @return { int } length: the length of the scale of the chart
     */
    function panzoomRangeLimiter( min, length, maxLength ) {
        const values = {};
        length = Math.max( length, 5 );
        values.min = Math.max( min, 0 );
        values.max = values.min + length;
        if( values.max > maxLength ) {
            values.max = maxLength;
            values.min = values.max - length;
            values.min = Math.max( values.min, 0 );
        }
        values.length = values.max - values.min;
        return values;
    }


    /**
     * Synchronize all charts if user zooms or pans one of the charts.
     * @param {object} values containing values.min and values.max to set the scale of the x-axis of the charts
     * @param {bool} animate true / false if charts update should be animated.
     *  
     */                          
    function syncAllCharts( values = {}, animate = true, animationDuration = 200 ) {
        
        allCharts.forEach( function( chart, index ) {
            //if( hideChartReported && 0 == index ) return; // 'return' means 'continue' in a foreach loop
            if( undefined != values.min && undefined != values.max ) {
                let yMax;
                if( 'chartWasteWater' !== chart.config.options.tag ) { // this for all but chart waste water. 
                    // set new max for y-axes depending on new slice of the values
                    //yMax = Math.round( 1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) );
                    yMax = 1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) ;
                    yMax = Math.max( 2.1,1.1 * Math.max( ...chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ) ) );
                } else { // this for chart waste water. 
                    yMax = 0;
                    chart.config.data.datasets[1].data.slice( values.min, values.max + 1 ).forEach( (value) => { 
                        if ( value > yMax ) {
                            yMax = value;
                        }
                    });
                    // console.log( 'value yMax raw: ' + yMax );
                    yMax = Math.max( Math.round( 1.1 * yMax ), 300 );



                    // Try to keep the scale of the y-axis consistent for chart of different regions (province, country) for easy comparison.
                    // Only if one goes to National Chart, the scale of the y-axis is frozen. On zoom or pan the y-axis is released again.
                    /*
                    if( chart.config.options.title.text.length > 0 && chart.config.options.title.text.includes( 'Nederland' ) ) {
                        yMaxGlobal = yMax;
                    }
                    */
                    if( yMaxGlobal > 300 ) yMax = yMaxGlobal; // yMaxGlobal to keep the scale of y-axis the same for chart of different regions  





                }
                
                if( yMax ===  Number.NEGATIVE_INFINITY ) {  // i.e. when waste water chart is not shown.
                    return;
                }
                chart.scales['y-axis-0'].options.ticks.max = yMax;
                
                // set new min and max to determine the slice to create chart from.
                let scale = chart.scales['x-axis-0'];
                scale.options.ticks.minIndex = values.min;
                scale.options.ticks.maxIndex = values.max;
            }


            // Annotations
            let zoomLevelNew;
            if( 0 == index ) { // add annotations to the first chart ( allCharts[0] )
                zoomLevelNew = ( 0 == showAnnotations ) ? 0 : calculateZoomLevel( allCharts[1].scales['x-axis-0'].options.ticks);
            } else {
                zoomLevelNew = ( 2 == showAnnotations ) ? calculateZoomLevel( allCharts[1].scales['x-axis-0'].options.ticks) : 0;
            }
            if( zoomLevelNew != zoomLevel ) {
                zoomLevel = zoomLevelNew;
                selectAnnotationsToShow();
            }
            // Update all charts
            if( animate ) {
                chart.update({
                    duration: animationDuration,
                    easing: 'easeOutQuad',
                });
            } else chart.update(0);
            chart.scales['y-axis-0'].options.ticks.max = undefined; // clear max value to not interfere with update with different city / dataset
       });
    }


    /**
     * Calculates the zoomlevel depening on the number of bars in the visible chart
     * Zoomlevel determines what annotations to show
     * @param { obj } ticks 
     * @return { int } zoomLevel
     */
    function calculateZoomLevel( ticks ) {
        // if ( window.innerWidth < 600 ) return 0; // small screens
        let numberOfBars = ticks.maxIndex - ticks.minIndex;
        return ( numberOfBars > 100) ? ( numberOfBars > 200) ? 1 : 2 : 3;
    }


    /**
     * Synchronize all charts if user zooms or pans one of the charts.
     * @param {int} id 
     */         
    /*                 
    function syncAllCharts( id, min, max ) {
        allCharts.forEach( function( chart ) {
            if( id != chart.id ) {
                chart.zoomScale('x', {min: min, max: max}, 'default');
                chart.update();
            }
        });
    }
    */



    /**
     * Get the name and type of the location if available from the chart data
     */
    charts.getLocation = function() {
        let location ={};
        location.name = ( city.data && city.data.province ) ? city.data.province.pv_name : false;
        location.region = ( city.data && city.data.type && city.data.type.region )
                                        ? city.data.type.region : false;
        return location;
    };


    /**
     * Get the name and type of the waste water location if available from the chart data
     */
    charts.getWastewaterLocation = function() {
        let location ={};
        location.name = ( wastewater.data && wastewater.data.plant && wastewater.data.plant.name )
                                        ? wastewater.data.plant.name : false;
        location.region = ( wastewater.data && wastewater.data.type && wastewater.data.type.region )
                                        ? wastewater.data.type.region : false;
        return location;
    };





    /**
     * Get a specific label from the array of chart labels.
     * Used mainly to get the last available date as being the day the data last updated. Called from CL.main.  
     * @param {enum} type 
     * @param {int} position 
     */
    charts.getChartLabel = function( type, position ) {
        switch( type ) {
            case 'city': 
                if( position < 0 ) return city.data.chartLabels[ city.data.chartLabels.length - 1 ];
            break;
            default: return 0
        }
    };




    /**
     * Get the waste water chart data if available
     */
    charts.getwastewaterData = function() {
        return ( wastewater.data ) ? wastewater.data : false;
    };


    /**
     * Toggle waste water chart: Show or hide the waste water chart. Called from CL.map and CL.main
     * @param {bool} toggle 
     */
    charts.toggleWasteWater = function( toggle = true, onPageLoad = false ) {

        let slideTimer,loadTimer;
        containerWasteWater.classList.remove( 'hide' );
        if( toggle ) {
            if( onPageLoad ) {
                //slideTimer = 750;
                loadTimer = 500;
            } else {
                //slideTimer = 1;
                loadTimer = 500;
            }
            containerWasteWater.style.display = 'block';
            // containerWasteWater.style.overflow = 'visible';
            setTimeout( function() {
                //containerWasteWater.style.maxHeight = ( window.innerWidth < 600 ) ? '400px' : '280px';
                let heightVal;
                if( window.innerWidth > 2000 ) {
                    heightVal = window.innerWidth / 4;
                } else if( window.innerWidth > 1000 ) {
                heightVal = window.innerWidth / 3;
                } else if( window.innerWidth > 600 ) {
                    heightVal = window.innerWidth / 2;
                } else  heightVal = window.innerWidth * 0.7;
                // containerWasteWater.style.maxHeight = window.innerWidth / 4 ) ? '400px' : '280px';
                containerWasteWater.style.maxHeight = Math.round( heightVal ) + 'px';
                if( ! wastewater.data ) {
                    setTimeout( function() {
                        if( ! plant ) plant = 'Nederland';
                        CL.dataUtils.ajaxRequest( 'get', 'plant', plant );
                    }, loadTimer );
                }
            }, 1 );
            setTimeout( function() {
                containerWasteWater.style.overflow = 'visible';
                let scrollToTop = ( window.innerWidth > 600 ) ? true : false;
                CL.display.scrollToTop( scrollToTop );
            }, 500 );
        } else {
                containerWasteWater.style.maxHeight = '1px';
                containerWasteWater.style.overflow = 'hidden';
                setTimeout( function() {
                    containerWasteWater.style.display = 'none';
                }, 400 );
            }
        return;

    };








    /**
     * Set the data received by an ajax-request to the charts.
     * @param {enum} type 
     * @param {array-object} data 
     */
    charts.setChartData = function( type, data ) {
        const zoom = ( chartLabels.length == 0 ) ? true : false;


        // console.log( "setChartData" );
        // console.log( "type: " + type );
        // console.log( "chartLabels" );
        // console.log( chartLabels );
        // console.log( data.chartData );


        switch( type ) {
            case 'wastewater':
                const labelAverage = '7-daags gemiddelde';
                wastewater.data = Object.assign( {}, data );


                if ( wastewater.data && wastewater.data.chartWasteWaterData
                            && wastewater.data.chartWasteWaterData.length > 0 ) {
                             // && city.data.chartLabels && city.data.chartLabels.length > 0 ) {
                    let mappedData = mapWasteWaterData( chartLabels, wastewater.data );   // map the available waste water data-points to the days of the chart labels
                    dataWasteWater.labels = chartLabels;
                    dataWasteWater.datasets[1].data = mappedData;

                    // The second series is a line and can be set to 'seven day average' or to the same values as first series. 
                    if ( 'province' === wastewater.data.type.region || 'country' === wastewater.data.type.region ) { // provincie or country
                        dataWasteWater.datasets[0].data = calculateMovingAverage( dataWasteWater.datasets[1].data );
                        window.chartWasteWater.data.datasets[0].label = labelAverage;

                    } else  {  // rwzi
                        dataWasteWater.datasets[0].data = mappedData;
                    }
                    window.chartWasteWater.options.title.text = 'Virusdeeltjes in waterzuivering ' + wastewater.data.plant.name;



                    

                    // some explanation in the tooltip for country chart
                    if( 'country' === wastewater.data.type.region 
                                && wastewater.data.chartWasteWaterFactor 
                                && wastewater.data.chartWasteWaterFactor.length > 0 ) {
                        const maxRwziFactor = Math.max( ...wastewater.data.chartWasteWaterFactor );
                        //let seriesBaseIndex = ( zoom ) ? chartLabels.length - zoomFactor :0;
                        let showPercentageIndex = dataWasteWater.datasets[0].data.length - 25;
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem, data ) {
                            if( labelAverage === data.datasets[ tooltipItem.datasetIndex ].label ) return;
                            if ( tooltipItem.index > showPercentageIndex ) {
                                // const i = tooltipItem.index + seriesBaseIndex;
                                const i = tooltipItem.index;
                                const val = Math.round( ( wastewater.data.chartWasteWaterFactor[ i ] / maxRwziFactor ) * 100 );
                                if( val > 100 ) return;
                                return ["(met een betrouwbaarheid van " + val + "%)"];
                            }
                        };

                        // set different colors for bars according to reliability of the value
                        dataWasteWater.datasets[1].backgroundColor = [];
                        let currentPercentage;
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            if( i > dataWasteWater.datasets[1].data.length - 30 ) { // check only the last 30 values
                                currentPercentage= wastewater.data.chartWasteWaterFactor[ i ] / maxRwziFactor;
                                if( currentPercentage < 0.6 ) {
                                    dataWasteWater.datasets[0].data[ i ] = null;
                                    if( currentPercentage < 0.2 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.15)';
                                    } else if( currentPercentage < 0.4 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.2)';
                                    } else {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.3)';
                                    }
                                }
                            }

                        }
                    } else if ( 'province' === wastewater.data.type.region 
                                    && wastewater.data.chartWasteWaterReliabilities
                                    && wastewater.data.chartWasteWaterReliabilities.length > 0 ) {
                        // console.log( dataWasteWater.datasets[0].data.length );
                        let showPercentageIndex = dataWasteWater.datasets[0].data.length - 20;
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem, data ) {
                            if( labelAverage === data.datasets[ tooltipItem.datasetIndex ].label ) return;  // return label for serie 'average' unchanged.  
                            if ( tooltipItem.index > showPercentageIndex && wastewater.data.chartWasteWaterReliabilities[ tooltipItem.index ] ) {
                                return ["(met een betrouwbaarheid van " 
                                            + Math.round( wastewater.data.chartWasteWaterReliabilities[ tooltipItem.index ] ) + "%)"];
                            }
                        };
                        // set different colors for bars according to reliability of the value
                        dataWasteWater.datasets[1].backgroundColor = [];
                        let currentPercentage;
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            if( i > dataWasteWater.datasets[1].data.length - 30 ) { // check only the last 30 values
                                currentPercentage= wastewater.data.chartWasteWaterReliabilities[ i ];
                                //console.log( wastewater.data.chartWasteWaterReliabilities[ i ] );
                                if( currentPercentage < 60 ) {
                                    dataWasteWater.datasets[0].data[ i ] = null;
                                    if( currentPercentage < 5 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.0)';
                                        dataWasteWater.datasets[1].data[ i ] = null;

                                    } else if( currentPercentage < 20 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.15)';
                                    } else if( currentPercentage < 40 ) {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.25)';
                                    } else {
                                        dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.35)';
                                    }
                                }
                            }
                        }


                    } else {
                        dataWasteWater.datasets[1].backgroundColor = [];
                        for( let i = 0; i < dataWasteWater.datasets[1].data.length; i++ ) {
                            dataWasteWater.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                        }
                        window.chartWasteWater.options.tooltips.callbacks.afterLabel = function( tooltipItem ) { return; };
                    }
                    window.chartWasteWater.update();
                }
            break;
            case 'hospitalized':
                city.data = Object.assign( {}, data );
                chartLabels = city.data.chartLabels;

                // hospitalized
                dataHospitalized.labels = chartLabels;
                dataHospitalized.datasets[1].data = city.data.chartData;
                if ( 'country' === city.data.type.region ) {
                    let dataInterpolated = getDataInterpolated( dataHospitalized.datasets[1].data );
                    dataHospitalized.datasets[0].data = calculateMovingAverage( dataInterpolated );
                   // dataHospitalized.datasets[0].data = calculateMovingAverage( dataHospitalized.datasets[1].data );
                } else { // do not use most recent day for average. It is not complete yet
                    dataHospitalized.datasets[0].data = calculateMovingAverage( dataHospitalized.datasets[1].data )
                                                                  .slice( 0,chartLabels.length - 1 );
                }
   
                // NICE-Data for the most recent days are not complete,so we use some special settings
                if ( 'province' === city.data.type.region ) {
                    const last = dataHospitalized.datasets[1].data.length - 2;

                    // some explanation in the tooltip
                    window.chartHospitalized.options.tooltips.callbacks.afterLabel = function(tooltipItem) {
                        if ( last == tooltipItem.index ) {
                            return ["Deze dag kan nog (behoorlijk) worden aangevuld.","Zie ook de toelichting bij 'ziekenhuisopnames'"];
                        }
                    };
                    // last bar different color
                    dataHospitalized.datasets[1].backgroundColor = [];
                    for( let i = 0; i < dataHospitalized.datasets[1].data.length; i++ ) {
                        if ( i == last ) {
                            dataHospitalized.datasets[1].backgroundColor[ i ] = 'rgba(100,100,100,0.3)';
                        } else {
                            dataHospitalized.datasets[1].backgroundColor[ i ] = 'rgba(54,156,210,0.5)';
                        }
                    }
                } else {
                    dataHospitalized.datasets[1].backgroundColor = 'rgba(54,156,210,0.5)';
                    window.chartHospitalized.options.tooltips.callbacks.afterLabel = Chart.defaults.global.tooltips.callbacks.afterLabel;
                }
                // console.log( city.data );
                if ( 'province' === city.data.type.region ) {
                    window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in ' + city.data.province.pv_name;
                }
                if ( 'country' === city.data.type.region ) {
                    window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in Nederland';
                }
                break;






                case 'infectionRadarPositiveTests':
                    const labelAverage1 = '7-daags gemiddelde';
                    infectionRadarPositiveTests.data = Object.assign( {}, data );

                    if( 'province' == infectionRadarPositiveTests.data.city ) {
                        infectionRadarPositiveTestsView = false;
                        CL.display.slideUpDown( containerInfectionRadarPositiveTests, 'hide', 400, 100, containerWasteWater ) ;
                    } else { 
                            infectionRadarPositiveTestsView = true;
                            CL.display.slideUpDown( containerInfectionRadarPositiveTests, 'show', 400, 100, containerWasteWater ) ;
                    }

                        
                    if ( infectionRadarPositiveTests.data && infectionRadarPositiveTests.data.chartInfectionRadarPostiveTestsData
                        && infectionRadarPositiveTests.data.chartInfectionRadarPostiveTestsData.length > 0 ) {


                        const mapped = mapInfectionRadarPositiveTestsData( chartLabels, infectionRadarPositiveTests.data );   // map the available waste water data-points to the days of the chart labels
                        dataInfectionRadarPositiveTests.labels = chartLabels;
                        dataInfectionRadarPositiveTests.datasets[1].data =mapped.data;
                        dataInfectionRadarPositiveTests.datasets[1].flags =mapped.flags;

                        // real, not interpolated, data bar different color
                        dataInfectionRadarPositiveTests.datasets[1].backgroundColor = [];
                        for( let i = 0; i < dataInfectionRadarPositiveTests.datasets[1].data.length; i++ ) {
                            if ( mapped.flags[i] ) {
                                dataInfectionRadarPositiveTests.datasets[1].backgroundColor[ i ] = 'rgba(0,58,105,0.5)';
                            } else {
                                dataInfectionRadarPositiveTests.datasets[1].backgroundColor[ i ] = 'rgba(77,125,165,0.6)';
                            }
                        }

                        
                        //dataInfectionRadarPositiveTests.datasets[0].data =mappedData;
                        dataInfectionRadarPositiveTests.datasets[0].data = calculateMovingAverage( dataInfectionRadarPositiveTests.datasets[1].data )
                        .slice( 0,chartLabels.length - 1 );

                        //dataInfectionRadarPositiveTests.datasets[1].backgroundColor = 'rgba(54,156,210,0.5)';
                        window.chartInfectionRadarPositiveTests.options.tooltips.callbacks.afterLabel = Chart.defaults.global.tooltips.callbacks.afterLabel;
                        //window.chartInfectionRadarPositiveTests.title.text = 'Percentage nieuw gemelde positieve tests per dag in Nederland';
                        window.chartInfectionRadarPositiveTests.options.title.text = 'Percentage positieve testen Nederland';
                    }
                    break;
        }

       let values;
        if ( zoom ) {
            values= {
                min: chartLabels.length - zoomDays,
                max: chartLabels.length
            };
        } else {
            let scale = allCharts[1].scales['x-axis-0'];
            values= {
                min: scale.options.ticks.minIndex,
                max: scale.options.ticks.maxIndex
            };
        }
        syncAllCharts( values, true, 400 );
        redrawZoomSymbols(); // redraw the zoom-symbols on the charts

        if ( false ) { //city.data.country !== true && ! city.data.validHospitalData && city.data.city != 'Vlieland' ) {
            document.getElementById('canvas-hospitalized-text').innerHTML = 'Deze data moet nog worden geüpdatet.';
            // document.getElementById('canvas-hospitalized-text').innerHTML = 'Vanwege de gemeentelijke herindeling worden<br>deze gegevens later vandaag geüpdatet.';
        } else {
            document.getElementById('canvas-hospitalized-text').innerHTML = '';
        }
    }

 

    /**
     * Set all charts to Nederland
     */
    charts.resetCharts = function( event ) {
        if (event && event.cancelable) {
            event.preventDefault();
        }
        CL.dataUtils.ajaxRequest( 'get','location','Nederland' );
        CL.dataUtils.ajaxRequest( 'get','plant','Nederland' );
        CL.dataUtils.ajaxRequest( 'get', 'infectionRadar', 'positiveTests' );
        /*
        if ( wastewater.data ) {
            setTimeout( function() { CL.dataUtils.ajaxRequest( 'get','plant','Nederland' ); }, 700 );
        }
        */
    };

  
    /**
     * Map the available waste water data-points to the days of the chart labels
     * for Nederland:
     * If the value == NULL we have to change it to NaN for the graph
     * This function had a more complex task but could now be much simpler
     * for RWZI location:
     * In general,only one or two measurements a week is available.
     * We would like to use the same labels as the other charts,which have daily values.
     * So we need to map the waste water data-points to all the days in the chart labels.
     */
    function mapWasteWaterData( labels,data ) {
        let mappedData = [];
        if(  'country' === data.type.region ) { // data.plant.code == 0 ) {   // Nederland
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        } else if( 'province' === data.type.region ) { // provincie
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        } else {    // lokale rwzi
            let i = 0;
            labels.forEach( function( item ) {
                if( data.chartWasteWaterLabels[ i ] && item == data.chartWasteWaterLabels[ i ] ) {
                    if ( data.chartWasteWaterData[ i ]  === null ) {
                        mappedData.push( NaN );
                    } else {
                        mappedData.push( Math.round( data.chartWasteWaterData[ i ] )  );
                    }
                    i++;
                } else {
                    mappedData.push( NaN );
                }
            });
        }
        return mappedData;
    }
    

    /**
     * Map the available infection radar positive tests data-points to the days of the chart labels
     * for Nederland:
     * If there is no value or the value == NULL we have to change it to NaN for the graph
     */
    function mapInfectionRadarPositiveTestsData( labels, data ) {
        let mappedData = [];
        let mappedFlags = [];
        let i = 0;

        labels.forEach( function( item ) {
            if( data.chartInfectionRadarPostiveTestsLabels[ i ] ) {
                if( null == data.chartInfectionRadarPostiveTestsData[ i ] ) {
                    mappedData.push( null );
                    mappedFlags.push( false );
                } else {
                    mappedData.push( data.chartInfectionRadarPostiveTestsData[ i ]  );
                    // LET OP: We draaien de flag om!!!!!!!! Dat sluit beter aan voor de grafiek
                    mappedFlags.push( ! data.chartInfectionRadarPostiveTestsDataFlags[ i ] );
                }
                i++;
            } else {
                mappedData.push( null );
                mappedFlags.push( false );
            }
        });

        return { data: mappedData, flags: mappedFlags };
    }









    /**
     * Calculate moving average of a dataset.
     * Default is a moving average over 7 days.
     * @param { array } dataset
     * @param { int } days
     */
    function calculateMovingAverage( dataset, days = 7 ) {
        let avg = new Array( dataset.length );
        let i,j;
        for ( i = dataset.length - 1; i > days - 1 ; i-- ){
            if( null === dataset[i] ) {
                avg[i] = 'NaN';
            } else {
                let sum = 0;
                for ( j = 0; j < days; j++ ) {
                    sum += dataset[i-j];
                }
                //  round to two decimals creates smoother graph
                avg[i] = Math.round( 100 * sum/days ) / 100;
            }
        }
        return avg;
    }



    /**
     * 
     * Notice: On sundays the hospital intake is only 0.75 of the average!
     * 
     * @param {*} sourceArray 
     */
    function getDataInterpolated( sourceArray ) {
        let dataset = deepCopy( sourceArray );
        let start,end;
        let notNull = true;
        let foundFirstValueEntry = false;

        for( let i = dataset.length -1; i > 700; i-- ) {
            if( foundFirstValueEntry || null != dataset[i] ) {
                foundFirstValueEntry = true;    // Soms is de meest recente waarde in het bestand null, omdat de lcps cijfers van gisteren vandaag pas geupdatet zijn.
                if( null == dataset [i] ) { 
                    notNull = false; // dit zijn de dagen die geïnterpoleerd moeten worden, dus we moeten door naar de eerstevolgende dag met een waarde.
                } else {
                    if( notNull ) {
                        end = i;
                    } else {
                        notNull = true;
                        start = i;

                        let startValue = dataset[ start ];
                        let endValue = dataset[ end ];
                        let interval = end - start; // - 1;

                        for( let offset = 1; offset < interval + 1; offset++ ){
                            dataset [ end - offset ] = 
                                ( ( ( ( startValue - endValue ) / interval ) * offset ) + endValue );
                                // simplified correction for the sunday hospital intake: only .75 of daily average 
                                if( 1 == offset ) dataset [ end - offset ] *= 0.75;
                        }
                    }
                }
            }
        }
        return dataset ;
    }



    /**
     * 
     * @param {*} sourceVariable 
     * @param {*} type 
     */
    function deepCopy( sourceVariable, type = '' ) {
        if( undefined === sourceVariable ) {
            console.log( 'deepCopy undefined' );
            return undefined;
        }
        return JSON.parse(JSON.stringify( sourceVariable ) )  ;
    }




}( window.CL.charts = window.CL.charts || {}, window.CL ) );
/** Corona Lokaal Charts Namespace / Namespace: CLchart */


