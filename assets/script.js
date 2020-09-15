/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";


// Set up the charts. See documentation for Chart.js: https://www.chartjs.org/docs/latest/
const defaultChartSettings = {
    type: 'bar',
    options: {
        responsive: true,
        title: {
            display: true,
            text: ''
        },
        legend: {
            display: false
        },
        tooltips: {
            mode: 'index',
            intersect: true
        },
        scales: {
            yAxes: [{
                ticks: {
                    precision: 0,
                    suggestedMin: 0,
                    // force only positive values
                    // min: 0, 
                    // beginAtZero: true
                }
            }]
        }
    }
} 
let dataReported = {
    datasets: [{
        type: 'line',
        label: '7-daags gemiddelde',
        borderColor: 'rgba(0,69,102,1)',
        borderWidth: 2,
        fill: false,
        pointRadius: 0
    }, {
        type: 'bar',
        label: 'nieuwe meldingen per dag',
        backgroundColor: 'rgba(54, 156, 210, 0.5)'
    }]
}
let dataHospitalized = {
    datasets: [{
        type: 'line',
        label: '7-daags gemiddelde',
        borderColor: 'rgba(240, 0,0, 1)',
        borderWidth: 2,
        fill: false,
        pointRadius: 0
    }, {
        type: 'bar',
        label: 'nieuwe ziekehuisopnames per dag',
        backgroundColor: 'rgba(54, 156, 210, 0.5)'
    }]
}
let dataDeceased = {
    datasets: [{
        type: 'line',
        label: '7-daags gemiddelde',
        borderColor: 'rgba(0, 0, 0, 1)',
        borderWidth: 2,
        fill: false,
        pointRadius: 0
    }, {
        type: 'bar',
        label: 'nieuwe overlijdens per dag',
        backgroundColor: 'rgba(151, 72, 6, 0.3)'
    }]
}
let dataWasteWater = {
    datasets: [{
        type: 'line',
        label: 'geïnterpoleerde waarde:',
        borderColor: 'rgb(0, 58, 105)',
        borderWidth: 2,
        fill: false,
        pointRadius: 0,
        spanGaps: true
    }, {
        type: 'bar',
        label: 'meting aantal virusdeeltjes per milliliter:',
        backgroundColor: 'rgba(0, 58, 105, 0.5)'
    }]
}

// some settings for navigating the map using touchscreens
window.touchscreen = false;
window.clicked = false;
window.clickTimer = 0;

// create the waste water bubbles in the map
const seriesBubbles =  {   
    type: 'mapbubble',
    name: 'virusdeeltjes in rioolwater',
    id: 'waste-water-bubbles',
    dataLabels: {
        enabled: false,
    },
    events: {
        click: function( event ) {
            rivmCharts.doAction( 'get', 'plant', event.point.name );
        }
    },
    data: [],
    maxSize: '4%',
    tooltip: {
        distance: 70,
        style: {
            color: '#666',
            lineHeight: '20px',
        },
        pointFormatter: function(){
            var s = '<span style="font-weight:bold;font-size:14px;"> ' + this.name
            + '</span><br><span style="color:#666;font-size:12px;">• '+ this.rna + ' virusdeeltjes (RNA) per ml, meting van '+ this.date + '</span><br>';
            return s;
        }
    },
    marker: {
        lineColor: '#666',
        lineWidth: 1,
        states: {
            hover: {
                lineWidth: 2,
                fillColor: '#369cd2',
                lineColor: '#369cd2'
            }
        }
    },
}


/**
 * Draw the charts as soon as the page is loaded.
 */
window.onload = function() {
    // create the background color for the charts
    Chart.plugins.register({
        beforeDraw: function ( chartInstance ) {
            const ctx = chartInstance.chart.ctx;
            if( ctx.canvas.id == 'canvas-waste-water') {
                ctx.fillStyle = '#fff7e9';
            } else {
                ctx.fillStyle = '#fff';
            }
            ctx.fillRect(0, 0, chartInstance.chart.width, chartInstance.chart.height);
        }
    });

    // check for touchscreens
    window.addEventListener("touchstart", function onFirstTouch() {
        window.touchscreen = true;
        window.removeEventListener('touchstart', onFirstTouch, false);
    });

    // add close action to all popups so they can be closed 
    document.querySelectorAll( '.click-to-close' ).forEach( item => {
        item.addEventListener('click', event => {
            const targetPopup = event.target.closest( '.popup' );
            rivmCharts.popupToggle ( targetPopup.id );
        });
    });

    // draw the chart 'Waste Water'
    const ctxWasteWater = document.getElementById('canvas-waste-water').getContext('2d');
    const chartWasteWaterSettings = Object.assign({}, defaultChartSettings, { data: dataWasteWater });
    window.chartWasteWater = new Chart( ctxWasteWater, chartWasteWaterSettings );
    // draw the chart 'Reported'
    const ctxReported = document.getElementById('canvas-reported').getContext('2d');
    const chartReportedSettings = Object.assign({}, defaultChartSettings, { data: dataReported });
    window.chartReported = new Chart( ctxReported, chartReportedSettings );
    // draw the chart 'Hospitalized'.
    const ctxHospitalized = document.getElementById('canvas-hospitalized').getContext('2d');
    const chartHospitalizedSettings = Object.assign({}, defaultChartSettings, { data: dataHospitalized });
    window.chartHospitalized = new Chart( ctxHospitalized, chartHospitalizedSettings );
    // draw the chart 'Deceased'.
    const ctxDeceased = document.getElementById('canvas-deceased').getContext('2d');
    const chartDeceasedSettings = Object.assign({}, defaultChartSettings, { data: dataDeceased });
    window.chartDeceased = new Chart( ctxDeceased, chartDeceasedSettings );
   
    // get the data for the current location / waste water plant
    rivmCharts.doAction( 'get', 'location', loc );
    if ( wasteWaterView) {
        setTimeout( function() { rivmCharts.doAction( 'get', 'plant', plant ) }, 500 );
    }

    // some settings for the map
    window.chckbxWasteWater = document.getElementById( 'chkbx-waste-water' );
    window.chckbxPositiveTests = document.getElementById( 'chkbx-positive-tests' );
    let titleText = ( window.chckbxPositiveTests.checked ) ? 'Positieve testen afgelopen week per 100.000 inwoners' : '';
    if ( window.chckbxPositiveTests.checked && window.chckbxWasteWater.checked ) titleText += ' /<br>';
    titleText += ( window.chckbxWasteWater.checked ) ? 'Virusdeeltjes in het rioolwater per locatie' : '';

    // create the map
    window.mapNL = Highcharts.mapChart('map-container', {
        chart: {
            map: 'gemeente',
        },
        title: {
            text: titleText,
            style: {
                fontFamily: 'arial',
                fontSize: '12px',
                color: '#666',
                fontWeight: 'bold'
            }
        },
        credits: {
            text: today + ' corona-lokaal.nl',
            href: ''
        },
        legend: { 
            enabled:false,
        },
        mapNavigation: {
            enabled: true,
            buttonOptions: {
                verticalAlign: 'bottom'
            },
            enableDoubleClickZoom: false,
        },
        colorAxis: {
            stops: [
                [0, '#ffffff'],
                [0.00000001, '#ffffe0'],
                [0.06, '#fdf5c8'],
                [0.12, '#fec44f'],
                [0.32, '#ec7014'],
                [0.8, '#800000'],
                [1.0, '#800000']
            ],
            min: 0,
            max: 160,
            startOnTick: false,
            endOnTick: false,
        },
        plotOptions: {
            series: {
                states: {
                    inactive: {
                        opacity: 1
                    }
                }
            }
        },
        series: [{ 
            data: ( window.chckbxPositiveTests.checked ) ? mapData : [],
            joinBy: ['gemnr', 'citycode'], 
            name: 'Nieuwe positieve testen',
            id: 'positive-tests-map',
            borderColor: '#aaaaaa',  // default #cccccc
            borderWidth: 0.6, // default 1
            dataLabels: {
                enabled: false,
            },
            states: {
                hover: {
                    color: '#369cd2',  
                }
            },
            events: {
                click: function( event ) {
                    if ( ! window.touchscreen ) {
                        rivmCharts.doAction( 'get', 'location', event.point.cityname );
                    } else { // create a 'double touch' action for touchscreens ( a single 'touch' equals mouse hover on PC's )
                        if ( window.clicked == event.point.citycode ) {
                            clearTimeout( window.clickTimer );
                            rivmCharts.doAction( 'get', 'location', event.point.cityname );
                            window.clicked = false;
                        } else {
                            window.clicked = event.point.citycode;
                            window.clickTimer = setTimeout( function(){
                                window.clicked = false;
                            }, 500 );
                        }
                    }
                }
            },
            tooltip: {
                distance: 70,
                style: {
                    color: '#666',
                    lineHeight: '20px',
                },
                pointFormatter: function(){
                    var s =  '<span style="font-weight:bold;font-size:14px;"> ' + this.cityname 
                    + '</span><br><span style="color:#666;font-size:12px;">• '+ this.reported 
                    + ' positieve testen afgelopen zeven dagen</span><br>'
                    + '<span style="color:#666;font-size:12px;">• ' 
                    + this.value + ' positieve testen per 100.000 inwoners</span><br>';
                    if ( window.touchscreen ) {
                        s += 'Dubbel klik de gemeente om de grafieken van ' + this.cityname  + ' te bekijken.<br>';
                    }
                    return s;
                }
            }
        }]   // series
    }); // Highmap

    seriesBubbles.data = plantLocations;
    if( window.chckbxWasteWater.checked ) {
        window.mapNL.addSeries( seriesBubbles, true);
        $( "#container-waste-water" ).fadeIn( 400 );
    }
            
    setTimeout( function() { 
        $( '#favo' ).fadeIn(200);
        document.getElementById('numberWasteWaterLocations').innerHTML = plantLocations.length;
    }, 2000 );
};  // window.onload



/**
 * Main part to handle interactivity. We set this in a separate namespace
 */
( function ( rivmCharts, $ ) {
    let twitter, facebook, linkedin, email, selectLocation, titleH1, zoomSymbolsList,
                containerWasteWater, seriesBubblesToggle;
    rivmCharts.response = {}
    let wastewater = {}
    let city = {}
    let chartLabels = [];
    const zoomFactor = 45;
    let zoomIn = true;
    let scrollToTop = true;
    let blinking = {}
    
     // select some elements on the page to be used throughout the script
    window.addEventListener('DOMContentLoaded', () => {
        twitter = document.getElementById('twitter');
        facebook = document.getElementById('facebook');
        linkedin = document.getElementById('linkedin');
        email = document.getElementById('email');
        titleH1 = document.getElementsByTagName("h1")[0];
        selectLocation = document.getElementById('selectLocation');
        zoomSymbolsList = document.querySelectorAll( '.zoom' );

        seriesBubblesToggle = ( wasteWaterView ) ? true : false;

        // place the waste water chart on the right position (responsive)
        containerWasteWater = document.getElementById('container-waste-water');
        positionWasteWaterChart();
        window.addEventListener("resize", positionWasteWaterChart );

    });

    /**
     * Blink the area of the selected city on the map
     * @param {string} cityName 
     */
    function blinkCity( cityName ) {
        if ( cityName == 'Nederland' ) return;
        blinking.index = window.mapNL.series[0].points.findIndex((p) => p.cityname === cityName );
        if ( blinking.index == null ) return;
        blinking.baseColor =  window.mapNL.series[0].points[ blinking.index ].color;
        const blinkColor = '#369cd2';
        let blink = false;
        blinking.intervalId = setInterval( function(){ 
            let updateColor;
            if (blink) {
                updateColor = blinking.baseColor;
                blink = false;
            } else {
                updateColor = blinkColor;
                blink = true;
            }
            window.mapNL.series[0].points[ blinking.index ].update({
                color : updateColor 
            }); 
        }, 300);
        setTimeout( function(){ clearblinkCity(); }, 3000 );
    }

    /**
     * Clear the blinking started by blinkingCity();
     */
    function clearblinkCity() {
        if ( blinking.intervalId == null ) return;
        clearInterval( blinking.intervalId );
        window.mapNL.series[0].points[ blinking.index ].update({
            color : blinking.baseColor 
        }); 
    }

    /**
     * interactivity of the checkboxes beneath the map
     */
    rivmCharts.mapOptionsClicked  = function ( type ) {
        let pt = window.chckbxPositiveTests.checked;
        let ww = window.chckbxWasteWater.checked;
        switch( type ) {
            case 'waste-water' :
                toggleBubbles();
                break;
            case 'positive-tests' :
                if ( pt ) window.mapNL.series[0].update({ data: mapData });
                else window.mapNL.series[0].update({ data: [] });
                break;
        }
        let titleText = ( pt ) ? 'Positieve testen afgelopen week per 100.000 inwoners' : '';
        if ( pt && ww ) titleText += ' /<br>';
        titleText += ( ww ) ? 'Virusdeeltjes in het rioolwater per locatie' : '';
        window.mapNL.title.update({ text: titleText });
        window.mapNL.redraw();
    }


   /**
     * Add or remove waste water bubbles.
     * use param 'toggle' to force bubbles visible (true) or hidden (false) 
     */     
    function toggleBubbles( toggle = null ) {
        seriesBubblesToggle = ( window.mapNL.series.length == 1 ) ? true : false;
        if( toggle === true ) seriesBubblesToggle = true;
        else if( toggle === false ) seriesBubblesToggle = false;

        if ( seriesBubblesToggle && window.mapNL.series.length == 1 ) {
            window.mapNL.addSeries( seriesBubbles, true );
            $( "#container-waste-water" ).fadeIn( 400 );
            if( ! wastewater.data ) {
                setTimeout( function() { rivmCharts.doAction( 'get', 'plant', plant ) }, 1000 );
            }
        }
        if ( ! seriesBubblesToggle && window.mapNL.series.length == 2 ) { 
            window.mapNL.get('waste-water-bubbles').remove();
            $( "#container-waste-water" ).fadeOut( 400 );
        }
        redrawTitles();
    }


    /**
     * Zoom charts in or out.
     */     
    rivmCharts.zoom = function () {
        if ( event ) event.preventDefault();
        zoomIn = ! zoomIn;
        redrawCharts( 'all', zoomIn );
    }


    /**
     * Redraw the charts
     * @param { bool } zoom 
     */
    function redrawCharts( type= 'all', zoom = true ) {
            // waste water
        if ( type != 'city' && wastewater.data && wastewater.data.chartWasteWaterData 
                            && wastewater.data.chartWasteWaterData.length > 0 
                            && city.data.chartLabels && city.data.chartLabels.length > 0 ) {

            chartLabels = city.data.chartLabels;
            let mappedData = mapWasteWaterData( chartLabels, wastewater.data );   // map the available waste water data-points to the days of the chart labels
            if ( ! zoom ) {
                dataWasteWater.labels = chartLabels;
                dataWasteWater.datasets[0].data = mappedData;
                dataWasteWater.datasets[1].data = mappedData;
            } else {
                dataWasteWater.labels = chartLabels.slice( chartLabels.length - zoomFactor, chartLabels.length );
                let slicedData = mappedData.slice( chartLabels.length - zoomFactor, chartLabels.length );
                dataWasteWater.datasets[1].data = Array.from( slicedData );
                slicedData[0] = getInterpolatedSlice( mappedData, chartLabels.length - zoomFactor );
                dataWasteWater.datasets[0].data = slicedData;
            }
            window.chartWasteWater.options.title.text = 'Virusdeeltjes in waterzuivering ' + wastewater.data.plant.name;
            window.chartWasteWater.update();
        }
        if( type != 'wastewater' && city.data.chartData.length > 0 && city.data.chartLabels.length > 0 ) {
            chartLabels = Array.from ( city.data.chartLabels );
            if ( ! zoom ) {
                // reported
                dataReported.labels = chartLabels
                dataReported.datasets[1].data = city.data.chartData[0];
                dataReported.datasets[0].data = calculateMovingAverage( city.data.chartData[0] );
                // hospitalized
                dataHospitalized.labels = chartLabels
                dataHospitalized.datasets[1].data = city.data.chartData[1];
                dataHospitalized.datasets[0].data = calculateMovingAverage( city.data.chartData[1] );
                // deceased
                dataDeceased.labels = chartLabels
                dataDeceased.datasets[1].data = city.data.chartData[2];
                dataDeceased.datasets[0].data = calculateMovingAverage( city.data.chartData[2] );
            } else {
                let labelLength = chartLabels.length;
                 // reported
                dataReported.labels = chartLabels.slice( labelLength-zoomFactor,labelLength );
                dataReported.datasets[1].data = city.data.chartData[0].slice(labelLength-zoomFactor,labelLength );
                dataReported.datasets[0].data = calculateMovingAverage( city.data.chartData[0] ).slice(labelLength-zoomFactor,labelLength );
                // hospitalized
                dataHospitalized.labels = chartLabels.slice( labelLength-zoomFactor,labelLength );
                dataHospitalized.datasets[1].data = city.data.chartData[1].slice(labelLength-zoomFactor,labelLength );
                dataHospitalized.datasets[0].data = calculateMovingAverage( city.data.chartData[1] ).slice(labelLength-zoomFactor,labelLength );
                // deceased
                dataDeceased.labels = chartLabels.slice( labelLength-zoomFactor,labelLength );
                dataDeceased.datasets[1].data = city.data.chartData[2].slice(labelLength-zoomFactor,labelLength );
                dataDeceased.datasets[0].data = calculateMovingAverage( city.data.chartData[2] ).slice(labelLength-zoomFactor,labelLength );
            }

            window.chartReported.options.title.text = 'Nieuw gemelde besmettingen per dag in ' + city.data.city;
            window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in ' + city.data.city;
            window.chartDeceased.options.title.text = 'Nieuw gemelde overlijdens per dag in ' + city.data.city;
            
            window.chartReported.update();
            window.chartHospitalized.update();
            window.chartDeceased.update();
        }

        // redraw the zoom-symbols on the charts
        const char = ( zoom ) ? "&#xf010" : "&#xf00e";
        const info = ( zoom ) ? "zoom uit van begin maart tot nu" : "zoom in tot de laatste zes weken";
        for (let i = 0; i < zoomSymbolsList.length; ++i) {
            zoomSymbolsList[i].innerHTML = char;
            zoomSymbolsList[i].title = info;
        }
    }


    function redrawTitles() {
        let tmpTitle = city.data.city;
        if ( seriesBubblesToggle && wastewater.data && wastewater.data.plant && wastewater.data.plant.name ) {
            tmpTitle += '/' + wastewater.data.plant.name;
        }
        title.innerHTML = tmpTitle + " - bijgewerkt tot en met " 
                + city.data.chartLabels[ city.data.chartLabels.length - 1 ] 
                        + " op basis van cijfers RIVM.";
                selectLocation.value = city.data.city;
    
        if ( city.data.country ) {
            selectLocation.value = 'Nederland';
        }

        let url = baseUrl + 'location/';
        url += ( city.data.city ) ? city.data.city : 'Nederland';
        if ( seriesBubblesToggle && wastewater.data &&  wastewater.data.plant && wastewater.data.plant.name ) url += '/plant/' + wastewater.data.plant.name;
        window.history.pushState('', baseTitle + city.data.city, url );
        window.document.title = baseTitle + city.data.city;
        titleH1.innerHTML = siteName + ' | ' + city.data.city;

        // share buttons.
        const shareText = 'Bekijk%20de%20corona-ontwikkelingen%20in%20' + city.data.city;
        const sourceText = '%0A%0ACorona%20Lokaal%20-%0A';
        const urlText = encodeURIComponent( baseUrl + 'location/' + city.data.city );
        twitter.href = 'https://twitter.com/intent/tweet?text=' + shareText + sourceText + '&url=' + urlText;
        facebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + urlText;
        linkedin.href = 'https://www.linkedin.com/shareArticle?mini=true&title=' + shareText + '&source=' + sourceText + '&url=' + urlText;
        email.href = 'mailto:?subject='+ shareText + '&body=' + shareText + sourceText + urlText + '%0A';

    }

    /**
     * Map the available waste water data-points to the days of the chart labels
     * In general, only one measurement a week is available. 
     * We would like to use the same labels as the other charts, which have daily values.
     * So we need to map teh waste water data-points to all the days in the chart labels.  
     */
    function mapWasteWaterData( labels, data ) {
        let mappedData = [];
        labels.forEach( function( item ) {
            if( data.chartWasteWaterLabels.includes( item ) ) {
                let index = data.chartWasteWaterLabels.indexOf( item );
                mappedData.push( data.chartWasteWaterData[ index ] );
            } else { 
                mappedData.push( NaN );
            }
        })
        return mappedData;
    }

    /**
     * Get the missing starting point if the data is sliced.
     * Chartjs interpolates the graph in case of missing data. but it will not generate
     * a first missing value if we have sliced the array. So we need to interpolate 
     * the values to create this fist value after slicing the data-array. 
     */
    function getInterpolatedSlice( mappedData, index ) {
        let slice = mappedData.slice( index, mappedData.length );
        let end = slice.findIndex(function(value) {
                return ! isNaN( value );
        });
        const endVal = slice[ end ];

        slice = mappedData.slice( 0, index ).reverse();
        let start = slice.findIndex(function(value) {
            return ! isNaN( value );
        });
        const startVal = slice[ start ];
        let gap = start + end + 1;
        let increase = endVal - startVal;
        return endVal - ( ( increase / gap ) * end ); // value of interpolated point
    }


    /**
     * Set all charts to Nederland
     */
    rivmCharts.resetCharts = function() {
        rivmCharts.doAction( 'get', 'location', 'Nederland' );
        if ( wastewater.data ) {
            setTimeout( function() { rivmCharts.doAction( 'get', 'plant', 'Nederland' ) }, 500 );
        }
    }


    /**
     * Ajax actions
     * TODO: https://medium.com/coding-design/writing-better-ajax-8ee4a7fb95f
     */
    rivmCharts.doAction = function (action, category, values ) {
        if ( event  ) event.preventDefault();
        const title = document.querySelector( '#title' );
        title.innerHTML = "Loading...";
        clearblinkCity();
    
        // create the parameter string for the AJAX-request.
        var params = "action=" + action + "&category=" + category + "&value=" + values;
        console.log( "params: "+ params );

        // Send the AJAX-request and handle the response.
        $.post( ajaxUrl, params ).then( function( response ) {
            // console.log("Data Loaded: " + response );
            rivmCharts.response = JSON.parse( response );
            if ( rivmCharts.response.success ) {
                switch ( action ) {
                    case 'get' :
                        if ( rivmCharts.response.data.plant ) {
                            if ( scrollToTop ) $('html,body').animate({ scrollTop: 0 }, 'slow');  // only scroll to top on desktops / large screens
                            wastewater.data = Object.assign( {}, rivmCharts.response.data );
                            redrawCharts( 'wastewater', zoomIn );
                        }
                        if ( rivmCharts.response.data.city || rivmCharts.response.data.country ) {
                            $('html,body').animate({ scrollTop: 0 }, 'slow');   // scroll to top
                            city.data = Object.assign( {}, rivmCharts.response.data );
                            redrawCharts( 'city', zoomIn );
                            if ( city.data.city && ! city.data.country ) {
                                    blinkCity( city.data.city );
                            }
                        }
                        redrawTitles();
                        break;
                }
            } else {
                title.innerHTML = "Er ging iets mis. Probeer het opnieuw.";
            }
        });
    }

    /**
     * Calculate moving average of a dataset. 
     * Default is a moving average over 7 days.
     * 
     * @param { array } dataset 
     * @param { int } days 
     */
    function calculateMovingAverage( dataset, days = 7 ) {
        let avg = new Array( dataset.length );
        let i, j;
        for ( i = dataset.length - 1; i > days - 1 ; i-- ){
            let sum = 0;
            for ( j = 0; j < days; j++ ) {
                sum += dataset[i-j];
            }
            avg[i] = Math.round( 100 * sum/days ) / 100;
        }
        return avg;
    }


    /**
     * Toggle a popup between visible and invisible
     * @param { string } id 
     */
    rivmCharts.popupToggle = function( id ) {
        const popup = document.getElementById( id );
        if( popup.classList.contains( 'is-visible' ) ) {
            popup.classList.remove( 'is-visible' );
            setTimeout( function(){
                popup.style.display = 'none';
            }, 600 );
        } else {
            popup.style.display = 'block';
            const newTop = 20 + window.scrollY ;
            popup.style.top = newTop  + 'px';
            popup.classList.add('is-visible');
        }   
    }


    /** 
     * responsive: move waste water container on smaller screens
     */
    function positionWasteWaterChart() {
        var w = window.innerWidth;
        const chartsContainer = document.getElementById('charts-container-wrapper');
        if ( w < 600 ) { 
            chartsContainer.appendChild( containerWasteWater );
            scrollToTop = false;
        }
        else {
            chartsContainer.prepend( containerWasteWater );
            scrollToTop = true;
        }
    }


}( window.rivmCharts = window.rivmCharts || {}, window.jQuery ) );