<?php
/**
 * This file is written by Kalahiri in 2020-2021 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class ProvinceModel extends BaseModel {
    private $listOfProvinces;
    private $listOfInhabitants;
    private $mapValues;


    
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }


    /**
     * Get the data to create the charts for a specific province
     */
    public function getChartData( $provinceCode, $ajax = true ) {
        if ( empty( $this->listOfProvinces ) ) {
            $this->getListOfProvinces();
        }

        // check the requested province. If not valid, return.
        $provinceKey = ValidateUtils::filterInt( substr( $provinceCode, 2,2) );
        if ( false == $provinceKey || ! isset( $this->listOfProvinces[ $provinceKey ] ) ) return false;  // check if request is a valid province

        // get the requested series from the dataset containing all provinces
        $seriesProvinces = $this->getSeriesSortedByProvince();
        $provinceData = $seriesProvinces[ $provinceKey ];
        unset( $seriesProvinces );

        // prepare the series. create the right format for JSON and javascript.
        $province = array( 
                'pv_code' => 'PV' . $this->listOfProvinces[ $provinceKey ][ FileManager::HEADER_PROVINCE_CODE ],
                'pv_name' => $this->listOfProvinces[ $provinceKey ][ FileManager::HEADER_PROVINCE_NAME ] 
            );
        $labels = $this->prepareLabels( array_column( $provinceData, FileManager::HEADER_DATE ) );
        array_shift( $labels );
        $hospitalized = array_column( $provinceData, FileManager::HEADER_HOSPITALIZED );

        if ( $ajax ) {
            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addData( 'chartLabels' , $labels );
            $ajaxResponse->addData( 'chartData', $hospitalized );
            $ajaxResponse->addData( 'province', $province );
            $ajaxResponse->addData( 'type', array( 'category' => 'hospitalized', 'region' => 'province' ) );
            $ajaxResponse->send();
        } else {
            return $provinceData;
        }
    }
 


   
    /**
     * Get data series sorted by province
     */
    public function getSeriesSortedByProvince() {
        // if( false ) {
        
           if( FileUtils::isFileAvailable( FileManager::CACHE_FILE_NICE_HOSPITAL_ADMISSION_SERIES_SORTED_BY_PROVINCE )
                && FileUtils::isFileAvailable( FileManager::CACHE_JAVASCRIPT_FILE_HOSPITAL_ADMISSION_MAP_VALUES_PER_PROVINCE ) ) {
        // */
            return FileManager::readFromCache( FileManager::CACHE_FILE_NICE_HOSPITAL_ADMISSION_SERIES_SORTED_BY_PROVINCE );
        }


        // get the properties per province
        $provinceProperties = FileManager::readCityPropertiesCsv();
        $provinceCodePerCity = array_column( $provinceProperties, FileManager::HEADER_PROVINCE_CODE, FileManager::HEADER_CITY_CODE );







        // get the raw hospital admissions
        $seriesNice = FileManager::readRIVM_NICEHospitalAdmissionCsv();
        array_multisort( array_column( $seriesNice, FileManager::HEADER_DATE, SORT_ASC), $seriesNice );


        // Maak eerst eenlege array om de ziekenhuisopnames en later de rioolwaterwaarden in te zetten.
        // Het systeem ging ooit uit van de arrays in het aangeleverde bestand, maar het RICM laat steeds vaker data weg
        // een daardoor vallen er gaten in de array die nauwelijks goed te herstellen zijn.
        // Nu hebben we het daarom maar omgedraaid en maken we eerst zelf een lege array
        // en waar het bestand van het RIVM een waarde heeft, vullen we die in.
        // Alleen de laatste datum in het bestand wordt gebruikt als meest recente meetpunt.
        $simpelArrayProvinces = array(20,21,22,23,24,25,26,27,28,29,30,31);
        $datetimeLastDate = new DateTimeImmutable( end( $seriesNice )["date"] );    // eind datum van de array

        $seriesSumPerProvince = array(); // De array die we moeten maken per provincie en een veld voor elke datum.
        foreach( $simpelArrayProvinces as $provCode ) { // maak voor elke provincie een array met datums.
            $datetimeStart = new DateTime ( '2020-03-12' ); // start datum van de array
            $interval = $datetimeStart->diff( $datetimeLastDate );  // bepaal het aantal dagen tussen einddatum en start datum
            for( $i=1; $i <= $interval->days; $i++ ) {  // maak voor elke dag een veld in de array
                $datetimeStart->modify ( '+1 day' );
                $keyDate = $datetimeStart->format( "Y-m-d" );
                $seriesSumPerProvince[ $provCode ][ $keyDate ][ FileManager::HEADER_DATE ] = $keyDate;
                // Zet het aantal ziekenhuisopnames op 0. Als we later het bestand van het RIVM nalopen, kunnen we de echte waarde invullen als die gegeven wordt.
                $seriesSumPerProvince[ $provCode ][ $keyDate ][FileManager::HEADER_HOSPITALIZED] = 0;   
            }

        }
    

        //var_dump( $seriesSumPerProvince ); exit();


        // Ales het goed is is de (lege) basisarray van alle dagen per provincie nu beschikbaar.
        // We kunnen nu de RICM bestanden erbij pakken en de juiste waarden invullen als het RIV< een waarde in het bestand heeft gepubliceerd.


        // sum the hosptal admission per province
        // The NICE hospital-admissions are a day behind, so we shift them a day forward and fill the first date with a dummy.
        foreach( $seriesNice as $row ) {
            $provCode = $provinceCodePerCity[ $row[ FileManager::HEADER_CITY_CODE ] ];
            if( ! isset( $seriesSumPerProvince[ $provCode ] ) ) {   // add the first record as a dummy.
                echo "error";   // Dit zou noit meer moeten voorkomen, want we hebben al op voorhand een leeg basis array gecreeerd.
            }
            $date = new DateTime( $row[ FileManager::HEADER_DATE ] );
            // $keyDate = $date->modify( '+1 day')->format("Y-m-d");   // shift all entries one day. Nice is always a day behind
            $keyDate = $date->format("Y-m-d");   // NO, we do that no more. No shift one day ahead.
            $seriesSumPerProvince[ $provCode ][ $keyDate ][ FileManager::HEADER_HOSPITALIZED ] += $row[ FileManager::HEADER_HOSPITALIZED ];
        }
        unset( $seriesNice );


        // Voor de most recent days there is not always an update available. Vul de series aan tot vandaag met 'NULL' als waarde. 
        foreach( $seriesSumPerProvince as $provCode => $series ) {
            $datetimeLastDate = new DateTime( end( $series )[ FileManager::HEADER_DATE ] );
            $datetimeNow = new DateTime();
            $interval = $datetimeNow->diff( $datetimeLastDate );
            for( $i=1; $i <= $interval->days; $i++ ) {
                $datetimeLastDate->modify ( '+1 day' );
                $keyDate = $datetimeLastDate->format( "Y-m-d" );
                $seriesSumPerProvince[ $provCode ][ $keyDate ][ FileManager::HEADER_DATE ] = $keyDate;
                $seriesSumPerProvince[ $provCode ][ $keyDate ][FileManager::HEADER_HOSPITALIZED] = NULL;
            }

        }
        // var_dump( $seriesSumPerProvince[ 20 ] ); exit(); 


        // *** create map values.
        // First get the number of inhabitants per province
        $this->getInhabitantsPerCity();
        $inhabitantsPerProvince = array();
        foreach( $this->listOfInhabitants  as $key => $row ) {
            $provCode = $provinceCodePerCity[ $key ];
            if( ! isset( $inhabitantsPerProvince[ $provCode ] ) ) {
                $inhabitantsPerProvince[ $provCode ] = $row[ FileManager::HEADER_INHABITANTS ];
            } else {
                $inhabitantsPerProvince[ $provCode ] += $row[ FileManager::HEADER_INHABITANTS ];
            }
        }
        // create the hospital admission values for the map.
        $mapValues = array();
        $i=0;
        foreach( $seriesSumPerProvince as $provinceCode => $seriesProv ) {
            $provinceCodeFull = 'PV' . $provinceCode;
            $mapValues[ $i ][ "pv_code" ] = $provinceCodeFull;
            $mapValues[ $i ][ "pv_name" ] = $this->getProvinceByCode( $provinceCodeFull );
            $mapValues[ $i ]["daily_intake" ] = 0;

            // $slice = array_slice( $seriesProv, -8, 7 );    // do not use the last day as it is still very incomplete 
            $slice = array_slice( $seriesProv, -21 );    // we get all last data
            $slice =  array_reverse($slice, true );
            // var_dump( $slice ); 
            $k = 0;
            foreach( $slice as $row ) {
                if( is_null( $row[ FileManager::HEADER_HOSPITALIZED ] ) ) continue;
                $k++;
                if( $k > 7 ) break;
                $mapValues[ $i ]["daily_intake" ] += $row[ FileManager::HEADER_HOSPITALIZED ];
            }
            // var_dump( $mapValues[ $i ] ); exit();
            $mapValues[ $i ]["value" ] = round( 1000000 * $mapValues[ $i ]["daily_intake" ]
                                                                        / $inhabitantsPerProvince[ $provinceCode ], 1);
            $mapValues[ $i ]["daily_intake" ] = round( $mapValues[ $i ]["daily_intake" ] / 7, 1 );
            $i++;
        }
        FileManager::saveToCache( $mapValues, FileManager::CACHE_JAVASCRIPT_FILE_HOSPITAL_ADMISSION_MAP_VALUES_PER_PROVINCE, 'js', 'mapDataHospitalizedPerProvince' );
        unset( $mapValues );





        // *** create chart series 
        $seriesPerProvince = array();
        foreach( $seriesSumPerProvince as $key => $seriesProvince ) {
            $seriesPerProvince[ $key ] = array_values( $seriesProvince );
        }
        //var_dump( $seriesPerProvince ); exit();




        


        FileManager::saveToCache( $seriesPerProvince, FileManager::CACHE_FILE_NICE_HOSPITAL_ADMISSION_SERIES_SORTED_BY_PROVINCE );

        return $seriesPerProvince;
    }
    






    /**
     * Gets the list of inhabitants per city
     * @return { array } Array with $key = citycode and $val = number of inhabitants.
     * First check if a cache file is available. If not, read the data from CBS source file
     * 
     * Inhabitants per city is retrieved from source file: 
     * FileManager::CBS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/inwoners/CBS-aantal_inwoners_per_gemeente.2022.csv';
     */
    private function getInhabitantsPerCity() {
        if ( ! empty( $this->listOfInhabitants ) ) return;

        $this->listOfInhabitants = FileManager::readFromCache( FileManager::CACHE_FILE_INHABITANTS_PER_CITY ) ;
        if ( $this->listOfInhabitants !== false ) return;

        $list = FileManager::readCsvCBS();
        $keys = array_column($list, FileManager::HEADER_CITY_CODE );
        $this->listOfInhabitants = array_combine( $keys, $list );
        FileManager::saveToCache( $this->listOfInhabitants, FileManager::CACHE_FILE_INHABITANTS_PER_CITY );
        return;
    }

 
    /**
     * Create a list of provinces and store it in a class variable $this->listOfProvinces
     * 
     * @return {bool} true;
     * 
     * More or less the same as $this->getProvinceByCode, so these could be integrated.
     */
    private function getListOfProvinces() {
        if( ArrayUtils::checkArray( $this->listOfProvinces ) ) return;
        $simpelArray = array(
            "PV20" => "Groningen",
            "PV21" => "Friesland",
            "PV22" => "Drenthe",
            "PV23" => "Overijssel",
            "PV24" => "Flevoland",
            "PV25" => "Gelderland",
            "PV26" => "Utrecht",
            "PV27" => "Noord-Holland",
            "PV28" => "Zuid-Holland",
            "PV29" => "Zeeland",
            "PV30" => "Noord-Brabant",
            "PV31" => "Limburg"
        );
        $this->listOfProvinces = array();
        foreach( $simpelArray as $key => $province ) {
            $code = (int) substr( $key, 2 );
            $this->listOfProvinces[ $code ][ FileManager::HEADER_PROVINCE_CODE ] = $code;
            $this->listOfProvinces[ $code ][ FileManager::HEADER_PROVINCE_NAME ] = $province;
        }
        return true;
    }



    /**
     * Get a province by its code. 
     * 
     * More or less the same as $this->getListOfProvinces(), so these could be integrated.
     */
    private function getProvinceByCode( $code ) {
        $provinces = array(
            "PV20" => "Groningen",
            "PV21" => "Friesland",
            "PV22" => "Drenthe",
            "PV23" => "Overijssel",
            "PV24" => "Flevoland",
            "PV25" => "Gelderland",
            "PV26" => "Utrecht",
            "PV27" => "Noord-Holland",
            "PV28" => "Zuid-Holland",
            "PV29" => "Zeeland",
            "PV30" => "Noord-Brabant",
            "PV31" => "Limburg"
        );
        return ( array_key_exists( $code, $provinces ) ) ? $provinces[ $code ] : false;
    }



    



}
