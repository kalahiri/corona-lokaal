<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class CityPartModel extends BaseModel {
    private $listOfCities;
    private $listOfInhabitants;
    private $listOfTopCities;
    private $mapValues;

    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    public function getChartData( $cityname = 'denhaag' ) {
        $cachefile = str_replace( '{cityname}', $cityname, FileManager::CACHE_GGD_CITY_PARTS_REPORT );
        if( FileManager::isFileAvailable( $cachefile ) ) {
           


            
        }


        // get series
        $seriesGGD = FileManager::readGGDCityPartsCsv( $cityname );
        if ( $seriesGGD == false ) return false;

        $data = array();
        $data["week"] = $seriesGGD[0][ FileManager::HEADER_WEEK_NUMBER ];

        /*
        $labels = array_column( $seriesGGD, FileManager::HEADER_CITY_NAME );
        $labelBreak = '';
        foreach( $labels as $label ) {
            if ( $labelBreak == $label ) break;
            if ( empty( $labelBreak ) ) $labelBreak = $label;
            if ( $label == "Leidschenveen-Ypenburg" ) $label = "L'veen-Y'burg";
            $data["labels"][] = $label;
        }
        */


        $break = NULL;
        foreach( $seriesGGD as $row  ) {
            if ( $break == $row[ FileManager::HEADER_CITY_CODE ] ) break;
            if ( empty( $break ) ) $break = $row[ FileManager::HEADER_CITY_CODE ];
            $data["citycode"][] = $row[ FileManager::HEADER_CITY_CODE ];
            if ( $row[ FileManager::HEADER_CITY_NAME ] == "Leidschenveen-Ypenburg" ) $row[ FileManager::HEADER_CITY_NAME ] = "L'veen-Y'burg";
            $data["labels"][] = $row[ FileManager::HEADER_CITY_NAME ];
        }



        $i=-1;
        $week = '';
        foreach( $seriesGGD as $row ) {
            if ( $week != $row[ FileManager::HEADER_WEEK_NUMBER ] ) {
                $week = $row[ FileManager::HEADER_WEEK_NUMBER ];
                $i++;
            }
            $data["ReportedData"][ $i ][] = $row[ FileManager::HEADER_REPORTED ];
            $data["PercentageData"][ $i ][] = round( 100 * $row[ FileManager::HEADER_REPORTED ] 
                    / $row[ FileManager::HEADER_NUMBER_OF_TESTS ], 1 );


            /*
            $cityCode = $row[ FileManager::HEADER_CITY_CODE ];
            $people = $inhabitants[ $cityCode ];
            $data["PercentageData"][ $i ][] = $row[ FileManager::HEADER_REPORTED ] / $people ;
            */


//        const chartReportedData = [[10,3,6,8,2,6,7,2],[20,10,15,18,3,10,9,15]];

        }
        

        //var_dump( $data ); exit();



        $inhabitants = FileManager::readInhabitantsCityPartsCsv( $cityname );


        // var_dump( $seriesGGD ); exit();
        // var_dump( $inhabitants ); exit();
       return $data;

    }


    public function getMapData( $cityname = 'denhaag' ) {

/*
        const mapData = [
            {"citycode":1,"cityname":"Loosduinen","reported":37,"value":76},
            {"citycode":2,"cityname":"Escamp","reported":180,"value":143},
            {"citycode":3,"cityname":"Segbroek","reported":76,"value":123},
            {"citycode":4,"cityname":"Scheveningen","reported":43,"value":75},
            {"citycode":5,"cityname":"Centrum","reported":215,"value":205},
            {"citycode":6,"cityname":"Laak","reported":79,"value":177},
            {"citycode":7,"cityname":"Haagse Hout","reported":48,"value":104},
            {"citycode":8,"cityname":"Leidschenveen-Ypenburg","reported":69,"value":142}
            ];

*/





        return true;
    }








}
