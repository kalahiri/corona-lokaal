<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the data for the country
 */
class CountryModel extends BaseModel {

    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    public function getData( $ajax = true ) {
        $data = $this->getTotals();
    }


    /**
     * Get the data for the whole country.
     */
    public function getTotals( $ajax= true ) {
        $series = FileManager::readSeries( FileManager::COUNTRY );

        // get the totals per day
        $totals = array_reduce( $series, function( $accumulator, $item ) { 
            if ( ! isset( $accumulator ) || ! is_array( $accumulator ) ) $accumulator = array();
            if ( isset( $accumulator[ $item[0] ] ) && isset( $accumulator[ $item[0] ]["reported"] ) ) {
                $accumulator[ $item[0] ]["reported"] += $item[2];
                $accumulator[ $item[0] ]["hospitalized"] += $item[3];
                $accumulator[ $item[0] ]["deceased"] += $item[4];
            } else {
                $accumulator[ $item[0] ]["reported"] = $item[2];
                $accumulator[ $item[0] ]["hospitalized"] = $item[3];
                $accumulator[ $item[0] ]["deceased"] = $item[4];
            }
            return $accumulator;
        });



        // TODO: CREATE CACHE FILE FOR TOTALS







       $labels = $this->prepareLabels( array_keys( $totals ) );

       $reported = $this->prepareDataSerie( array_column( $totals, 'reported' ));
       $hospitalized = $this->prepareDataSerie( array_column( $totals, 'hospitalized' ));
       $deceased = $this->prepareDataSerie( array_column( $totals, 'deceased' ));

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', array( $reported, $hospitalized, $deceased ));
            $ajaxResponse->addData( 'city', 'Nederland' );
            $ajaxResponse->addData( 'country', true );
            $ajaxResponse->send();
        }
    }


    /**
     * Get the weekly highest growth
     */
    public function getWeekData() {

    }




}
