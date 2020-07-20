/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";

/**
 * Set up the charts
 * See documentation for Chart.js: https://www.chartjs.org/docs/latest/
 */
var dataReported = {
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
};
var dataHospitalized = {
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

};
var dataDeceased = {
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

};

/**
 * Draw the charts as soon as the page is loaded.
 */
window.onload = function() {
    // Draw the chart 'Reported'
    var ctxReported = document.getElementById('canvas-reported').getContext('2d');
    window.chartReported = new Chart(ctxReported, {
        type: 'bar',
        data: dataReported,
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
                        precision: 0
                    }
                }]
            }
        }
    });
    // Draw the chart 'Hospitalized'.
    var ctxHospitalized = document.getElementById('canvas-hospitalized').getContext('2d');
    window.chartHospitalized = new Chart(ctxHospitalized, {
        type: 'bar',
        data: dataHospitalized,
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
                        precision: 0
                    }
                }]
            }
        }
    });
    // Draw the chart 'Deceased'.
    var ctxDeceased = document.getElementById('canvas-deceased').getContext('2d');
    window.chartDeceased = new Chart(ctxDeceased, {
        type: 'bar',
        data: dataDeceased,
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
                        precision: 0
                    }
                }]
            }
        }

    });

    setTimeout(function(){ 
        rivmCharts.doAction( 'get', 'location', loc );
        }, 200);

};



// 
( function ( rivmCharts, $ ) {

    let twitter, facebook, linkedin, email,selectLocation, titleH1, zoomSymbolsList;
    rivmCharts.response = {}
    const zoomFactor = 45;
    let zoomIn = false;


    /**
     * Select some elements on the page to be used throughout the script
     */
    $( document ).ready(function() {
        // select some elements
        twitter = document.getElementById('twitter');
        facebook = document.getElementById('facebook');
        linkedin = document.getElementById('linkedin');
        email = document.getElementById('email');
        titleH1 = document.getElementsByTagName("h1")[0];
        selectLocation = document.getElementById('selectLocation');
        zoomSymbolsList = document.querySelectorAll( '.zoom' );
    });
    

    /**
     * Zoom graphs in or out.
     */     
    rivmCharts.zoom = function () {
        if ( event ) event.preventDefault();
        zoomIn = ! zoomIn;
        redrawCharts( zoomIn );
    }


    /**
     * Redraw the charts
     * @param { bool } zoom 
     */
    function redrawCharts( zoom = false ) {
        if ( ! zoom ) {
            // reported
            dataReported.labels = rivmCharts.response.data.chartLabels;
            dataReported.datasets[1].data = rivmCharts.response.data.chartData[0];
            dataReported.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[0] );
            // hospitalized
            dataHospitalized.labels = rivmCharts.response.data.chartLabels;
            dataHospitalized.datasets[1].data = rivmCharts.response.data.chartData[1];
            dataHospitalized.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[1] );
            // deceased
            dataDeceased.labels = rivmCharts.response.data.chartLabels;
            dataDeceased.datasets[1].data = rivmCharts.response.data.chartData[2];
            dataDeceased.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[2] );
        } else {
            let labelLength = rivmCharts.response.data.chartLabels.length;
            // reported
            dataReported.labels = rivmCharts.response.data.chartLabels.slice(labelLength-zoomFactor,labelLength );
            dataReported.datasets[1].data = rivmCharts.response.data.chartData[0].slice(labelLength-zoomFactor,labelLength );
            dataReported.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[0] ).slice(labelLength-zoomFactor,labelLength );
            // hospitalized
            dataHospitalized.labels = rivmCharts.response.data.chartLabels.slice(labelLength-zoomFactor,labelLength );
            dataHospitalized.datasets[1].data = rivmCharts.response.data.chartData[1].slice(labelLength-zoomFactor,labelLength );
            dataHospitalized.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[1] ).slice(labelLength-zoomFactor,labelLength );
            // deceased
            dataDeceased.labels = rivmCharts.response.data.chartLabels.slice(labelLength-zoomFactor,labelLength );
            dataDeceased.datasets[1].data = rivmCharts.response.data.chartData[2].slice(labelLength-zoomFactor,labelLength );
            dataDeceased.datasets[0].data = calculateMovingAverage( rivmCharts.response.data.chartData[2] ).slice(labelLength-zoomFactor,labelLength );
        }

        window.chartReported.options.title.text = 'Nieuw gemelde besmettingen per dag in ' + rivmCharts.response.data.city;
        window.chartHospitalized.options.title.text = 'Nieuw gemelde ziekenhuisopnames per dag in ' + rivmCharts.response.data.city;
        window.chartDeceased.options.title.text = 'Nieuw gemelde overlijdens per dag in ' + rivmCharts.response.data.city;
           
        window.chartReported.update();
        window.chartHospitalized.update();
        window.chartDeceased.update();

        // redraw the zoom-symbols on the charts
        const char = ( zoom ) ? "&#xf010" : "&#xf00e";
        const info = ( zoom ) ? "zoom uit" : "zoom in";
        for (let i = 0; i < zoomSymbolsList.length; ++i) {
            zoomSymbolsList[i].innerHTML = char;
            zoomSymbolsList[i].title = info;
        }
    }


    /**
     * Ajax actions 
     */
    rivmCharts.doAction = function (action, category, values ) {
        if ( event  ) event.preventDefault();
        const title = document.querySelector( '#title' );
        title.innerHTML = "Loading...";
    
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
                        zoomIn = false;
                        redrawCharts( zoomIn );
                        if ( rivmCharts.response.data.city ) {
                        title.innerHTML = rivmCharts.response.data.city + " - bijgewerkt tot en met " 
                                        + rivmCharts.response.data.chartLabels[ rivmCharts.response.data.chartLabels.length - 1 ] 
                                        + " op basis van cijfers RIVM.";
                        }
                        if ( rivmCharts.response.data.country ) {
                            selectLocation.value = 'Nederland';
                        }

                        window.history.pushState('', baseTitle + rivmCharts.response.data.city, baseUrl + 'location/' + rivmCharts.response.data.city);
                        window.document.title = baseTitle + rivmCharts.response.data.city;
                        titleH1.innerHTML = siteName + ' | ' + rivmCharts.response.data.city;

                        // update the share buttons.
                        const shareText = 'Bekijk%20de%20corona-ontwikkelingen%20in%20' 
                                + rivmCharts.response.data.city;
                        const sourceText = '%0A%0ACorona%20Lokaal%20-%0A';
                        const urlText = encodeURIComponent( baseUrl + 'location/' + rivmCharts.response.data.city );
                        twitter.href = 'https://twitter.com/intent/tweet?text=' + shareText + sourceText + '&url=' + urlText;
                        facebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + urlText;
                        linkedin.href = 'https://www.linkedin.com/shareArticle?mini=true&title=' + shareText + '&source=' + sourceText + '&url=' + urlText;
                        email.href = 'mailto:?subject='+ shareText + '&body=' + shareText + sourceText + urlText + '%0A';
                        break;
                    default:
                }
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


}( window.rivmCharts = window.rivmCharts || {}, window.jQuery ) );


