<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Handle the selection of city
 */
class FileManager {

    // TODO: Name cache files consistently.
    // cache files
    const CACHE_FILE_LIST_OF_CITIES= "data/cache/cache.list-of-cities.php";
    const CACHE_JAVASCRIPT_FILE_LIST_OF_CITIES= "data/cache/cache.list-of-cities.js";
    const CACHE_FILE_SERIES_SORTED_BY_CITY = "data/cache/cache.series-sorted-by-city.php";
    const CACHE_FILE_NICE_HOSPITAL_ADMISSION_SERIES_SORTED_BY_CITY = "data/cache/cache.nice.hospitaladmission-series-sorted-by-city.php";
    const CACHE_FILE_NICE_HOSPITAL_ADMISSION_SERIES_SORTED_BY_PROVINCE = "data/cache/cache.nice.hospitaladmission-series-sorted-by-province.php";
    const CACHE_JAVASCRIPT_FILE_HOSPITAL_ADMISSION_MAP_VALUES_PER_PROVINCE = "data/cache/cache.nice.hospitaladmission-map-values-per-province.js";
    const CACHE_FILE_SERIES_SORTED_BY_DATE = "data/cache/cache.series-sorted-by-date.php";
    const CACHE_FILE_TOTALS_FROM_FILE_TOTALS_PER_DAY = "data/cache/cache.totals-from-file-totals-per-day.php";
    const CACHE_FILE_VACCINATED_PER_CITY = "data/cache/cache.vaccinated-per-city.php";
    const TOTALS_BY_DATE_CACHE_FILE = "data/cache/cache.totals-sorted-by-date.php";
    const SERIES_TOP_REPORTED_CITY_CACHE_FILE = "data/cache/cache.top-reported-cities.php";
    const CACHE_FILE_INHABITANTS_PER_CITY = "data/cache/cache.inhabitants-per-cities.php";
    const CACHE_JAVASCRIPT_FILE_WASTE_WATER_PLANT_LOCATIONS = "data/cache/cache.waste-water-plant-locations.js";
    const CACHE_JAVASCRIPT_FILE_WASTE_WATER_MAP_VALUES_PER_PROVINCE = "data/cache/cache.waste-water-map-values-per-province.js";
    const CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE = "data/cache/cache.waste-water-series-per-province-sorted-by-date.php";
    const CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT = "data/cache/cache.waste-water-series-per-province-sorted-by-date-persistent.php";
    const CACHE_WASTE_WATER_SERIES_SORTED_BY_LOCATION = "data/cache/cache.waste-water-series-sorted-by-location.php";
    const CACHE_WASTE_WATER_TOTALS_BY_WEEK_NUMBER = "data/cache/cache.waste-water-totals-sorted-by-week-number.php";
    const CACHE_WASTE_WATER_TOTALS_BY_DAY = "data/cache/cache.waste-water-totals-sorted-by-day.php";
    const CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT = "data/cache/cache.waste-water-totals-sorted-by-day-persistent.php";
    const CACHE_WASTE_WATER_TOTALS_MOST_RECENT_VALUE = "data/cache/cache.waste-water-totals-most_recent_value.php";
    const CACHE_JAVASCRIPT_FILE_MAP_VALUES = "data/cache/cache.map-values-per-city.js";
    const CACHE_JAVASCRIPT_FILE_MAP_VALUES_HOSPITAL_ADMISSION_PER_HUNDRED_THOUSAND = "data/cache/cache.map-values-per-city-hospital-admission-per-hundred-thousand.js";
    const CACHE_FILE_MAP_VALUES = "data/cache/cache.map-values-per-city.php";
    const CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES = "data/cache/cache.map-values-vaccinated-per-city.js";
    const CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES = "data/cache/cache.map-values-vaccinated-per-neighbourhood.js";
    const CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES = "data/cache/cache.map-values-vaccinated-per-city.php";
    const CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE = "data/cache/cache.map-values-vaccinated-per-city-last-update-date.php";
    const CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES = "data/cache/cache.map-values-vaccinated-per-neighbourhood.php";
    const CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES_LAST_UPDATE_DATE = "data/cache/cache.map-values-vaccinated-per-neighbourhood-last-update-date.php";
    const CACHE_FILE_NICE_NEW_INTAKE = "data/cache/cache.nice_new_intake.php";
    const CACHE_FILE_NICE_HOSPITAL_ADMISSION = "data/cache/cache.nice_hospital_admission.php";
    const CACHE_GGD_CITY_PARTS_REPORT = "data/cache/cache.map-values-{cityname}.js";
    const CACHE_RIVM_VACCINATED_FILE = 'data/cache/cache.vaccinated.php';
    // const CACHE_RIVM_INFECTED_PERCENTAGE_FILE = 'data/cache/cache.infected-percentage.php';
    const CACHE_RIVM_GGD_NUMBER_OF_TESTS = 'data/cache/cache.rivm_ggd_number_of_tests.php';
    const CACHE_INFECTION_RADAR_POSITIVE_TESTS = 'data/cache/cache.infectionradar-positive-tests.php';
    const CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES = "data/cache/cache.infection-radar-positive-tests-most-recent-values.php";

    const CACHE_DIR = 'data/cache/';

    // source csv-files
    const RIVM_SOURCE_CSV_FILE = 'data/source_files/gemeenten/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_SOURCE_CASUS_CSV_FILE = 'data/source_files/casus/COVID-19_casus_landelijk.csv';
    const RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE = 'data/source_files/gemeentenperdag/COVID-19_aantallen_gemeente_per_dag.csv';
    const RIVM_SOURCE_TOTALS_PER_DAY_UP_TO_20211003_CSV_FILE = 'data/source_files/gemeentenperdag/COVID-19_aantallen_gemeente_per_dag_tm_03102021.csv';
    const RIVM_SOURCE_VACCINATED_PER_CITY_CSV_FILE = 'data/source_files/vaccin/pergemeente/COVID-19_vaccinatiegraad_per_gemeente_per_week_leeftijd.csv';
    const RIVM_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE = 'data/source_files/vaccin/perwijk/COVID-19_vaccinatiegraad_per_wijk_per_week_leeftijd.csv';
    const RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE = 'data/source_files/gemeentenziekenhuisopnamesnice/COVID-19_ziekenhuisopnames.csv';
    const RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_UP_TO_20211003_CSV_FILE = 'data/source_files/gemeentenziekenhuisopnamesnice/COVID-19_ziekenhuisopnames_tm_03102021.csv';
    const RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE = 'data/source_files/uitgevoerdetesten/COVID-19_uitgevoerde_testen.csv';
    const RIVM_SOURCE_WASTE_WATER_CSV_FILE = 'data/source_files/rioolwater/COVID-19_rioolwaterdata.csv';
    const RIVM_SOURCE_CSV_DELIMITER = ';';
    const CBS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/inwoners/CBS-aantal_inwoners_per_gemeente.{version}.csv';
    const NICE_SOURCE_NEW_INTAKE_JSON_FILE = 'data/source_files/nice/nice_new_intake.json';
    const NICE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE = 'data/source_files/nice/nice_hospital_admission.json';
    const LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE = 'data/source_files/lcps/covid-19.csv';
    const LCPS_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE = 'data/source_files/lcps/covid-19-lcps-hospital-admissions-country-historical.csv';
    const RIVM_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE = 'data/source_files/lcps/covid-19-rivm-hospital-admissions-country-historical.csv';
    const RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE = 'data/source_files/infectieradar-positieve-testen/COVID-19_infectieradar_positieve_testuitslagen_per_week.csv';
    const GGD_CITY_SOURCE_CSV_FILE = 'data/source_files/stadsdelen/{cityname}/ggd.besmettingen.{cityname}.csv';
    const CITY_PARTS_INHABITANTS_SOURCE_CSV_FILE = 'data/source_files/stadsdelen/{cityname}/inwoners.stadsdelen.{cityname}.csv';
    const WASTE_WATER_PLANTS_PROPERTIES_CSV_FILE = 'data/source_files/rioolwater/rwzi.properties.csv';
    //const WASTE_WATER_PLANTS_INHABITANTS_PROVINCE_PROPERTIES_CSV_FILE = 'data/source_files/rioolwater/rwzi.inwoners.verdeling.regio.provincie.2021.csv';
    const WASTE_WATER_PLANTS_INHABITANTS_PROVINCE_PROPERTIES_CSV_FILE = 'data/source_files/rioolwater/rwzi.inwoners.verdeling.regio.provincie.2025.csv';
    const CBS_SOURCE_CITY_PROPERTIES_CSV_FILE = 'data/source_files/assets/cities.sorted.by.gemcode.2023.csv';
    const RIVM_VACCINATED_JSON_FILE = 'data/source_files/vaccin/rivm/gevaccineerd.json';
    const RIVM_VACCINATED_FOR_CALCULATIONS_FILE = 'data/source_files/vaccin/rivm/gevaccineerd.csv';
    // const RIVM_INFECTED_PERCENTAGE_JSON_FILE = 'data/source_files/percentagepositief/percentagepositief.json';
    const OWID_SOURCE_VACCINATED_CSV_FILE = 'data/source_files/vaccin/owid/owid-vaccination-update.csv';
    const CORONA_DASHBOARD_DATA_JSON_FILE = 'data/source_files/coronadashboard/dashboard_data.{date}.json';




    // remote source files
    // Metadata: https://data.rivm.nl/meta/srv/eng/catalog.search;jsessionid=15F1A98515ACD0D3A80034B564423C81#/metadata/1c0fcd57-1102-4620-9cfa-441e93ea5604
    const RIVM_REMOTE_SOURCE_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_aantallen_gemeente_cumulatief.csv';
    const RIVM_REMOTE_SOURCE_CASUS_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_casus_landelijk.csv';
    const RIVM_REMOTE_SOURCE_TOTALS_PER_DAY_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_aantallen_gemeente_per_dag.csv';
    const RIVM_REMOTE_SOURCE_VACCINATED_PER_CITY_CSV_FILE = 'https://data.rivm.nl/data/covid-19/COVID-19_vaccinatiegraad_per_gemeente_per_week_leeftijd.csv';
    const RIVM_REMOTE_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE = 'https://data.rivm.nl/data/covid-19/COVID-19_vaccinatiegraad_per_wijk_per_week_leeftijd.csv';
    const RIVM_REMOTE_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_ziekenhuisopnames.csv';
    const RIVM_REMOTE_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_uitgevoerde_testen.csv';
    const RIVM_REMOTE_SOURCE_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_infectieradar_positieve_testuitslagen_per_week.csv';
    const NICE_REMOTE_SOURCE_NEW_INTAKE_JSON_FILE = 'https://stichting-nice.nl/covid-19/public/new-intake/'; 
    // NICE URL's naar data over IC-opnames haal je uit dit script: https://www.stichting-nice.nl/js/covid-19.js?version=1596281515062
    const NICE_REMOTE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE = 'https://stichting-nice.nl/covid-19/public/zkh/new-intake/';
    // NICE URL's naar data over ziekenhuisopnames haal je uit dit script: https://www.stichting-nice.nl/js/covid-19-zkh.js?version=1600276221011
    const RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE = 'https://data.rivm.nl/covid-19/COVID-19_rioolwaterdata.csv';
    // More info about waste ater data file: https://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/a2960b68-9d3f-4dc3-9485-600570cd52b9?tab=relations
    // CBS source information on number of inhabitants is not automaticallty updated.
    // The data can be found here: https://opendata.cbs.nl/statline/portal.html?_catalog=CBS&_la=nl&tableId=70072ned&_theme=232
    // OUD const LCPS_REMOTE_SOURCE_HOSPITAL_COUNT_CSV_FILE = 'https://lcps.nu/wp-content/uploads/covid-19.csv';
    const LCPS_REMOTE_SOURCE_HOSPITAL_COUNT_CSV_FILE = 'https://lcps.nu/wp-content/uploads/covid-19-datafeed.csv';
    // Check LCPS page for more info: https://lcps.nu/datafeed/
    const OWID_REMOTE_SOURCE_VACCINATED_CSV_FILE = 'https://raw.githubusercontent.com/owid/covid-19-data/master/public/data/latest/owid-covid-latest.csv';
   




    // Cookies
    const COOKIE_LAST_CHECK_RIVM = 'lastCheckRIVM';
    const COOKIE_LAST_CHECK_RIVM_TOTALS_PER_DAY = 'lastCheckRIVMTotalsPerDayFile';
    const COOKIE_LAST_CHECK_RIVM_VACCINATIONS_PER_CITY = 'lastCheckRIVMVaccinationsPerCity';
    const COOKIE_LAST_CHECK_RIVM_VACCINATIONS_PER_NEIGHBOURHOOD = 'lastCheckRIVMVaccinationsPerNeighbourhood';
    const COOKIE_LAST_CHECK_RIVM_NICE_HOSPITAL_ADMISSION = 'lastCheckRIVM-NICEHospitalAdmission';
    const COOKIE_LAST_CHECK_RIVM_GGD_NUMBER_OF_TESTS = 'lastCheckRIVM-GGDNumberOfTests';
    const COOKIE_LAST_CHECK_NICE = 'lastChecKNICE';
    const COOKIE_LAST_CHECK_WASTE_WATER = 'lastCheckWasteWater';
    const COOKIE_LAST_CHECK_INFECTION_RADAR_POSITIVE_TESTS = 'lastCheckInfectionRadarPositiveTests';
    const COOKIE_LAST_CHECK_NICE_HOSPITAL_ADMISSION = 'lastCheckNICEHospitalAdmission';
    const COOKIE_LAST_CHECK_LCPS_HOSPITAL_DATA = 'lastCheckLCPSHospitalCount';
    const COOKIE_LAST_CHECK_OWID_VACCINATED = 'lastCheckOWIDVaccinated';

    const COOKIE_LAST_FETCH_RIVM = 'lastFetchRIVM';
    const COOKIE_LAST_FETCH_RIVM_CASUS = 'lastFetchRIVMCasusFile';
    const COOKIE_LAST_FETCH_RIVM_TOTALS_PER_DAY = 'lastFetchRIVMTotalsPerDayFile';
    const COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_CITY = 'lastFetchRIVMVaccinationsPerCity';
    const COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_NEIGHBOURHOOD = 'lastFetchRIVMVaccinationsPerNeighbourhood';
    const COOKIE_LAST_FETCH_RIVM_NICE_HOSPITAL_ADMISSION = 'lastFetchRIVM-NICEHospitalAdmission';
    const COOKIE_LAST_FETCH_RIVM_GGD_NUMBER_OF_TESTS = 'lastFetchRIVM-GGDNumberOfTests';
    const COOKIE_LAST_FETCH_NICE = 'lastFetchNICEintakeICU';
    const COOKIE_LAST_FETCH_WASTE_WATER = 'lastFetchWasteWater';
    const COOKIE_LAST_FETCH_INFECTION_RADAR_POSITIVE_TESTS = 'lastFetchInfectionRadarPositiveTests';
    const COOKIE_LAST_FETCH_NICE_HOSPITAL_ADMISSION = 'lastFetchNICEHospitalAdmission';
    const COOKIE_LAST_FETCH_LCPS_HOSPITAL_DATA = 'lastFetchLCPSHospitalCount';
    const COOKIE_LAST_FETCH_RIVM_VACCINATED = 'lastFetchRIVMVaccinated';
    //const COOKIE_LAST_FETCH_RIVM_INFECTED_PERCENTAGE = 'lastFetchRIVMVaccinated';
    const COOKIE_LAST_FETCH_OWID_VACCINATED = 'lastFetchOWIDVaccinated';
    const COOKIE_MALFUNCTION_RIVM_UPDATE = 'malfunctionRIVMUpdate';
    const COOKIE_MALFUNCTION_RIVM_UPDATE_SOLVED = 'malfunctionRIVMUpdateSolved';



    // replacements to source data to prevent possible breaking code and for usability
    const SEARCH = array( "'", "\"", "s-Gravenhage", "s-Hertogenbosch", "s Hertogenbosch", "Hengelo (O.)" );
    const REPLACE = array( "", "", "Den Haag", "Den Bosch", "Den Bosch", "Hengelo" );

    // flags
   // const LIST_OF_CITIES = "LIST_OF_CITIES";
    const SERIES_ALL = "SERIES_ALL";
    const SERIES_WITHOUT_PROVINCE = "SERIES_WITHOUT_PROVINCE";
    const SERIES_SORTED_BY_CITY = "SERIES_SORTED_BY_CITY";
    const SERIES_SORTED_BY_DATE = "SERIES_SORTED_BY_DATE";
    const HOSPITAL_ADMISSION_NOTIFICATION = "hospital admission notification";
    const HOSPITAL_ADMISSION_ADMISSION_DATE = "hospital admission admission date";
  //  const SERIES_NICE_HOSPITAL_ADMISSION = "SERIES_NICE_HOSPITAL_ADMISSION";
  //  const SERIES_NICE_INTAKE_ICU = "SERIES_NICE_INTAKE_ICU";

    const USE_RIVM = "use_rivm";
    const USE_NICE = "use_nice";
    const USE_LCPS = "use_lcps";

    const SOURCE_TOTALS_BY_DAY = "source_totals_by_day";
    const SOURCE_CUMULATIVE = "source_cumulative";

    const HOSPITAL_ADMISSIONS_UPDATE = "hospital_admissions_update";
    const HOSPITAL_ADMISSIONS_NICE_UPDATE = "hospital_admissions_nice_update";
    const RIVM_DAILY_UPDATE = "rivm_daily_update";
    const WASTE_WATER_UPDATE = "waste_water_update";
    const INFECTION_RADAR_POSITIVE_TESTS_UPDATE = "infection_radar_positive_tests_update";
    const GGD_NUMBER_OF_TESTS_UPDATE = "ggd_number_of_tests_update";
    const TOTALS_PER_DAY_UPDATE = "totals_per_day_update";
    const LCPS_PER_DAY_UPDATE = "lcps_per_day_update";
    const VACCINATION_PER_CITY_UPDATE = "vaccination_per_city_update";
    const VACCINATION_PER_NEIGHBOURHOOD_UPDATE = "vaccination_per_neighbourhood_update";

    const AGEGROUP_12_TO_17 = "agegroup_12_to_17";
    const AGEGROUP_12_AND_ABOVE = "agegroup_12_and_above";
    const AGEGROUP_18_AND_ABOVE = "agegroup_18_and_above";

    const JAVASCRIPT = "javascript";
    const PHP = "php";

    // Uniform headers for data in arrays and in cache files 
    const HEADER_DATE = 'date';
    const HEADER_DATE_PREVIOUS = 'date_previous';
    const HEADER_DATE_TIMESTAMP = 'date_timestamp';
    const HEADER_COUNTRY = 'country';
    const HEADER_COUNTRY_CODE = 'country_code';
    const HEADER_CITY_CODE = 'code';
    const HEADER_CITY_NAME = 'city';
    const HEADER_NEIGHBOURHOOD_CODE = 'wk_code';
    const HEADER_NEIGHBOURHOOD_NAME = 'wk_name';
    const HEADER_PROVINCE = 'province';
    const HEADER_PROVINCE_CODE = 'province_code';
    const HEADER_PROVINCE_NAME ='province_name';
    const HEADER_REPORTED = 'reported';
    const HEADER_HOSPITALIZED = 'hospitalized';
    const HEADER_HOSPITALIZED_PER_HUNDRED_THOUSAND = 'hospitalized_per_hundred_thousand';
    const HEADER_INTENSIVE_CARED = 'intensive-cared';
    const HEADER_INTENSIVE_CARED_INTERNATIONAL = 'intensive_cared_international';
    const HEADER_HOSPITAL_INTAKE = 'hospital_intake';
    const HEADER_INTENSIVE_CARE_INTAKE = 'intensive_care_intake';
    const HEADER_DECEASED = 'deceased';
    const HEADER_AGEGROUP = 'agegroup';
    const HEADER_SEX = 'sex';
    const HEADER_INHABITANTS = 'inhabitants';
    const HEADER_NUMBER_OF_TESTS = 'number_of_tests';
    const HEADER_POSITIVE_TESTS = 'positive_tests';
    const HEADER_POSITIVE_TESTS_PERCENTAGE = 'positive_tests_percentage';
    const HEADER_GGD = 'ggd';
    const HEADER_COUNT = 'count';
    const HEADER_VACCINATED_TOTAL_DOSES = 'total_doses';
    const HEADER_VACCINATED_TOTAL_DOSES_ESTIMATED_BY_ME = 'total_doses_estimated_by_me';
    const HEADER_VACCINATED_TOTAL_DOSES_REPORTED = 'total_doses_reported';
    const HEADER_VACCINATED_PERSONS_FULLY = 'persons_fully_vaccinated';
    const HEADER_VACCINATED_PERSONS_SINGLE_DOSE = 'persons_single_dose';
    const HEADER_VACCINATED_ESTIMATE_PERSONS_SINGLE_DOSE_FULLY_VAXXED = 'estimate_persons_single_dose_fully_vaxxed';
    const HEADER_VACCINATED_PERSONS_TWO_DOSES = 'persons_two_doses';
    const HEADER_VACCINATED_PERSONS_AT_LEAST_ONE_DOSE = 'persons_at_least_one_dose';
    const HEADER_VACCINATED_PERSONS_JANSSEN = 'persons_vaccinated_janssen';
    const HEADER_VACCINATED_PERCENTAGE = 'vaccinated_percentage';
    const HEADER_VACCINATED_PERCENTAGE_EU = 'eu_vaccinated_percentage';
    const HEADER_VALUE = 'value';
    const HEADER_INTERPOLATED = 'interpolated';


    // Uniform headers for rioolwater data
    const HEADER_DATE_MEASUREMENT = 'date_measurement';
    const HEADER_RWZI_CODE = 'RWZI_code';
    const HEADER_RWZI_NAME = 'RWZI_name';
    const HEADER_RWZI_X = 'RWZI_x';
    const HEADER_RWZI_Y = 'RWZI_y';
    const HEADER_RWZI_FACTOR = 'RWZI_factor';
    const HEADER_RWZI_PART_PERCENTAGE = 'aandeel_percentage';
    const HEADER_VALUE_RELIABILITY = 'value_reliability';
    const HEADER_RNA = 'RNA_per_ml';
    const HEADER_RNA_FLOW = 'RNA_flow_per_100.000';
    const HEADER_REPRESENTATIVE_MEASUREMENT = 'representative_measurement';
    const HEADER_WEEK_NUMBER = 'week_number';
    const HEADER_RWZI_CAPACITY_136 = 'bio_capaciteit_136';
    const HEADER_RWZI_CAPACITY_54 = 'bio_capaciteit_54';

    const RNA_FLOW_CONSTANT = 100000000000;


    const TEXT_FILE_Q_EN_A = 'assets/texts/qena.answer.{number}.html';
 
    /**
     * Check the file size of remote file and compare with our local cache file
     * See: https://thisinterestsme.com/php-get-size-remote-file/
     */
    public static function isUpdateSourceFileAvailable( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            $lastFetched = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM );
            $lastChecked = FileUtils::readCookie( self::COOKIE_LAST_CHECK_RIVM );
            if ( date('j',$now) == date('j',$lastFetched ) ) return false;  // only one update each day.
            $hour = date('H',$now);
            if( $hour > 13 && $hour < 16 ) { //updates usually appear in this time slot.
                $checkInterval = 180;  // 60*3 is three minutes 
            } else {
                $checkInterval = 1800;  // 60*30 is half hour 
            }
            if ( ( $now - $lastChecked ) < $checkInterval ) return false; 
        }

        // Do the check
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_CSV_FILE, self::RIVM_SOURCE_CSV_FILE );
    }


    /**
     * Download the RIVM CSV source file.
     * This is also where the cache files are deleted. 
     * If no cache files are available later on, they are recreated (and thus updated) automatically.
     */
    public static function getRemoteFile() {
        $currentFileSize = FileUtils::getFileSize( self::RIVM_SOURCE_CSV_FILE );
        if( file_exists( self::RIVM_SOURCE_CSV_FILE ) ) { // archive current file
            if( ! FileUtils::archiveFile( self::RIVM_SOURCE_CSV_FILE ) ) return false;
        }
        if( ! FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_CSV_FILE, self::RIVM_SOURCE_CSV_FILE ) ) return false;
        $newFileSize = FileUtils::getFileSize( self::RIVM_SOURCE_CSV_FILE );  
        if( $newFileSize <= $currentFileSize ) {
            echo 'error: downloaded file too small. Try to download again';
            return false;
        }
        self::emptyCache( self::RIVM_DAILY_UPDATE ); // empty cache.
        SiteUtils::createSiteMap(); // update sitemap ( probably nothing changed, but just in case... )
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM, time() );
        return true;
    }


   /**
     * Reads the RIVM csv source file and returns the data
     *  $type determines which columns are added to the returned data.
     * 
     * Source file: data/source_files/gemeenten/COVID-19_aantallen_gemeente_cumulatief.csv
     */
    public static function readCsvRIVM( $type = self::SERIES_ALL ) {
        if( ! FileUtils::isFileAvailable( self::RIVM_SOURCE_CSV_FILE ) ) return false;
        $data = array();
        if ( ( $handle = fopen( self::RIVM_SOURCE_CSV_FILE, 'r' ) ) !== false ) {
            $header = NULL;
            $fieldsCount = NULL;
            switch( $type ) {
                case self::SERIES_WITHOUT_PROVINCE:
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                        self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    while( ( $row = fgetcsv( $handle, 1000, SELF::RIVM_SOURCE_CSV_DELIMITER ) ) !== false ) {
                        if( ! isset( $fieldsCount ) || empty( $fieldsCount ) ) { // first line is the header
                            $fieldsCount = count( $row );
                        } else {  // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount && ! empty( $row[3] ) ) {
                                $data[] = array_combine( $header, 
                                            array( 
                                                substr( $row[1], 0, 10 ) , (int) substr( $row[3],2 ),
                                                str_replace( self::SEARCH, self::REPLACE, $row[4] ),
                                                $row[6], 0, $row[8] 
                                            )
                                        );
                            }
                        }
                    }
                    if( '8' !== self::checkFileVersion( $handle, self::RIVM_SOURCE_CSV_DELIMITER ) ) {
                        new LogException( 'databestand <b>' . self::RIVM_SOURCE_CSV_FILE . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
                    }
                break;
                case self::SERIES_ALL:
                default:
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                            self::HEADER_PROVINCE, 
                            self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    while( ( $row = fgetcsv( $handle, 1000, self::RIVM_SOURCE_CSV_DELIMITER ) ) !== false ) {
                        if( ! isset($fieldsCount) || empty( $fieldsCount ) ) { // first line is the header
                            $fieldsCount = count( $row );
                        } else { // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount )  {
                                $data[] = array_combine( $header, 
                                    array(
                                        substr( $row[2], 0, 10 ), (int) substr( $row[3],2 ),
                                        str_replace( self::SEARCH, self::REPLACE, $row[4] ),
                                        $row[5],$row[6], 0, $row[8] 
                                    )
                                );
                            }
                        }
                    }
            }
             self::checkFileVersion( $handle, self::RIVM_SOURCE_CSV_DELIMITER );
            fclose( $handle );
        }
        return $data;
    }


    /**
     * Reads the version number from the data file.
     * If the version number changes, the data structure of the data file has changed.
     * 
     * @var handle { file pointer } the data file
     * @var delimiter { string } the delimiter to split csv data
     * 
     * @return the version number. Return -1 if no version number was found
     */
    private static function checkFileVersion( $handle, $delimiter ) {
        rewind( $handle );  // set the file pointer at the beginning of the data file
        $row = fgetcsv( $handle, 1000, $delimiter );
        $row = fgetcsv( $handle, 1000, $delimiter ); // get the second line. The first line contains headers.
        return ( false != $row ) ? $row[0] : -1; // version number is the first field. return -1 als er geen versienummer gevonden is.
    }





    public static function readCacheDir() {
        $cacheFiles = array_slice( scandir( self::CACHE_DIR ), 2 ); // lose the . and ..
        return $cacheFiles;
    }

    public static function getCacheFileDates() {
        $cacheFiles = self::readCacheDir();
        $fileDates = array();
        $i = 0;
        foreach( $cacheFiles as $cacheFile ) {
            $currentFile = self::CACHE_DIR . $cacheFile;
            $fileDates[ $i ][ 'filename' ] = $currentFile;
            $fileDates[ $i ]['timestamp'] = filemtime( $currentFile );
            $fileDates[ $i ]['datetime'] = date( "Y-m-d", $fileDates[ $i ]['timestamp'] );
            $i++;
        }
        return $fileDates;
    }






    /**
     * Empty cache files / delete cache files / unlink cache files
     * 
     * @var type { flag }   self::RIVM_DAILY_UPDATE: empty almost complete  for daily update
     *                      self::HOSPITAL_ADMISSIONS: empty series to renew caches with hospital admission data
     */
    public static function emptyCache( $type = self::HOSPITAL_ADMISSIONS_UPDATE ) {
        $cacheFiles = array_slice( scandir( self::CACHE_DIR ), 2 ); // lose the . and ..
        switch( $type ) {
            case self::RIVM_DAILY_UPDATE:
                foreach( $cacheFiles as $cacheFile ) {
                    $currentFile = self::CACHE_DIR . $cacheFile;
                    if( $currentFile ==  self::CACHE_FILE_INHABITANTS_PER_CITY 
                            || $currentFile == self::CACHE_FILE_LIST_OF_CITIES 
                            || $currentFile == self::CACHE_JAVASCRIPT_FILE_LIST_OF_CITIES 
                            || $currentFile == self::CACHE_FILE_TOTALS_FROM_FILE_TOTALS_PER_DAY
                            || $currentFile == self::CACHE_FILE_NICE_NEW_INTAKE
                            || $currentFile == self::CACHE_FILE_NICE_HOSPITAL_ADMISSION
                            || $currentFile == self::CACHE_RIVM_VACCINATED_FILE
                            || $currentFile == self::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES
                            || $currentFile == self::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES
                            || $currentFile == self::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE
                            || $currentFile == self::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES
                            || $currentFile == self::CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES_LAST_UPDATE_DATE
                            || $currentFile == self::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT
                            || $currentFile == self::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT 
                            
                            ) continue; // no need to recreate cache file each day.
                    if( strpos( $currentFile, 'cache' ) !== false
                            && strpos( $currentFile, 'waste-water' ) == false
                            && file_exists( $currentFile ) ) {
                        unlink( $currentFile );
                    }
                }
            break;
            case self::INFECTION_RADAR_POSITIVE_TESTS_UPDATE:
                $target = self::CACHE_INFECTION_RADAR_POSITIVE_TESTS;
                if( file_exists( $target ) ) unlink( $target );
                $target = self::CACHE_INFECTION_RADAR_POSITIVE_TESTS_MOST_RECENT_VALUES;
                if( file_exists( $target ) ) unlink( $target );
            break;
            case self::WASTE_WATER_UPDATE:
                $target = 'waste-water'; 
                foreach( $cacheFiles as $cacheFile ) {
                    $currentFile = self::CACHE_DIR . $cacheFile;
                    if( $currentFile == self::CACHE_WASTE_WATER_SERIES_PER_PROVINCE_SORTED_BY_DATE_PERSISTENT
                            || $currentFile == self::CACHE_WASTE_WATER_TOTALS_BY_DAY_PERSISTENT 
                    ) continue; // no need to recreate the persistent cache files each day.)
                    if( strpos( $currentFile, $target ) !== false && file_exists( $currentFile ) ) {
                        unlink( $currentFile );
                    }
                }
            break;
            case self::HOSPITAL_ADMISSIONS_UPDATE:
                $target = substr( FileManager::CACHE_FILE_SERIES_SORTED_BY_CITY, 1, -4 ); 
                foreach( $cacheFiles as $cacheFile ) {
                    $currentFile = self::CACHE_DIR . $cacheFile;
                    if( strpos( $currentFile, $target ) !== false && file_exists( $currentFile ) ) {
                        unlink( $currentFile );
                    }
                }
                $currentFile = self::CACHE_DIR . self::CACHE_JAVASCRIPT_FILE_MAP_VALUES_HOSPITAL_ADMISSION_PER_HUNDRED_THOUSAND;
                if( file_exists( $currentFile ) ) {
                    unlink( $currentFile );
                }
            break;
            case self::GGD_NUMBER_OF_TESTS_UPDATE:
                $target = self::CACHE_RIVM_GGD_NUMBER_OF_TESTS;
                if( file_exists( $target ) ) unlink( $target );
                $target = self::TOTALS_BY_DATE_CACHE_FILE;
                if( file_exists( $target ) ) unlink( $target );
            break;
            case self::TOTALS_PER_DAY_UPDATE:
                $target = self::TOTALS_BY_DATE_CACHE_FILE;
                if( file_exists( $target ) ) unlink( $target );
                $target = 'nice.hospitaladmission'; 
                foreach( $cacheFiles as $cacheFile ) {
                    $currentFile = self::CACHE_DIR . $cacheFile;
                    if( strpos( $currentFile, $target ) !== false && file_exists( $currentFile ) ) {
                        unlink( $currentFile );
                    }
                }
            break;
            case self::LCPS_PER_DAY_UPDATE: // Er is een (tussentijdse) update van LCPS cijfers, update alleen de Totals By Date Cache File 
                $target = self::TOTALS_BY_DATE_CACHE_FILE;
                if( file_exists( $target ) ) unlink( $target );
            break;
            case self::VACCINATION_PER_CITY_UPDATE:
                $target = self::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES;
                if( file_exists( $target ) ) unlink( $target );
                $target = self::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_CITY_MAP_VALUES;
                if( file_exists( $target ) ) unlink( $target );
                $target =  self::CACHE_FILE_VACCINATED_PER_CITY_MAP_VALUES_LAST_UPDATE_DATE;
                if( file_exists( $target ) ) unlink( $target );
            break;
            case self::VACCINATION_PER_NEIGHBOURHOOD_UPDATE:
                $target = self::CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES;
                if( file_exists( $target ) ) unlink( $target );
                $target = self::CACHE_JAVASCRIPT_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES;
                if( file_exists( $target ) ) unlink( $target );
                $target =  self::CACHE_FILE_VACCINATED_PER_NEIGHBOURHOOD_MAP_VALUES_LAST_UPDATE_DATE;
                if( file_exists( $target ) ) unlink( $target );
            break;
            case self::HOSPITAL_ADMISSIONS_NICE_UPDATE:
                $target = 'nice.hospitaladmission'; 
                foreach( $cacheFiles as $cacheFile ) {
                    $currentFile = self::CACHE_DIR . $cacheFile;
                    if( strpos( $currentFile, $target ) !== false && file_exists( $currentFile ) ) {
                        unlink( $currentFile );
                    }
                }
            break;
        }
        return true;
    }
    

    /**
     * Check whether Totals By Day file should be updated.
     * This should be done after the cumulative file has been updated.
     */
    public static function shouldTotalsPerDayBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            // Check if we already updated today. If so, return false;
            $lastFetchedTotalsByDay = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM_TOTALS_PER_DAY );
            if ( date( 'j', $now ) == date( 'j', $lastFetchedTotalsByDay ) ) return false;

            // Check if cumulatiev file has been updated. The cumulative file should be updated first.
            $lastFetchedCumulative = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM );
            if ( date( 'j', $now ) != date( 'j', $lastFetchedCumulative ) ) return false;
        }
        // Do the check. If new Totals By Day file is available and file size is larger, we have a new update.
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM_TOTALS_PER_DAY, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_TOTALS_PER_DAY_CSV_FILE,
                                                self::RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE );
    }


    /**
     * Download the RIVM CSV TOTALS PER DAY source file.
     */
    public static function getRemoteTotalsPerDayFile() {
        FileUtils::archiveFile( self::RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE );
        $result = FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_TOTALS_PER_DAY_CSV_FILE, 
                            self::RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE );
        if( $result === false ) return false;
        self::emptyCache( self::RIVM_DAILY_UPDATE ); // empty cache.
        if( file_exists( self::CACHE_FILE_TOTALS_FROM_FILE_TOTALS_PER_DAY ) ) {
            unlink( self::CACHE_FILE_TOTALS_FROM_FILE_TOTALS_PER_DAY );
        }
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_TOTALS_PER_DAY, time() );
        return true;
    }


    /**
     * Read totals per day file
     * 
     * @var onlyLastPart {bool} if true, read only the data for the last week.
     */
    public static function readCsvTotalsPerDay( $timestamp = 0, $onlyLastPart = true ) {
        if ( $timestamp > 0 ) {
            $date = date( 'Ymd', $timestamp );
            $sourceFile = substr( self::RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        } else { 
            $sourceFile = self::RIVM_SOURCE_TOTALS_PER_DAY_CSV_FILE;

        }
        // unused at the moment, but maybe we need it at a later date.
        $sourceArchiveFile = self::RIVM_SOURCE_TOTALS_PER_DAY_UP_TO_20211003_CSV_FILE; 
        if( ! FileUtils::isFileAvailable( $sourceFile ) || ! FileUtils::isFileAvailable( $sourceArchiveFile ) ) {
            new LogException( 'One of the two source files for Totals Per Day not available ', __LINE__ );
            return false;
        }

        $header = NULL;
        $fieldsCount = NULL;
        $delimiter = NULL;
        $data = array();

        if( ! $onlyLastPart ) {
            // first, read the archive source file
            if( ( $handle = fopen( $sourceArchiveFile, 'r' ) ) !== FALSE ) {
                $delimiter = FileUtils::detectDelimiter( $handle );
                if( $delimiter === false ) {
                    new LogException( 'No delimiter found', __LINE__ );
                    return false;
                }
                while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                    if( is_null( $header ) || ! $header ) { // first line is the header
                        $fieldsCount = count( $row );
                        $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                    } else { // add a line of csv-data to our data array
                        if( count( $row ) == $fieldsCount ) {
                            $data[] = array_combine($header, array( $row[2], $row[3], $row[4], (int) $row[10],
                                                                 0, (int) $row[11] ) );
                        }
                    }
                }
                if( '7' !== self::checkFileVersion( $handle, self::RIVM_SOURCE_CSV_DELIMITER ) ) {
                    new LogException( 'databestand <b>' . $sourceFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
                }
                fclose($handle);
            }

        }

        // Second, read the second file containing recent updates
        $lastPart = ! $onlyLastPart;
        $skipFirstline = ( $onlyLastPart ) ? false : true; // skip the header in second source file. We already have it
        $lastPartDateTime = new DateTime( 'now', new DateTimeZone( BaseModel::TIME_ZONE ) );
        $lastPartDate = $lastPartDateTime->modify( '-7 days' )->format("Y-m-d");

        if( ( $handle = fopen( $sourceFile, 'r' ) ) !== false ) {
            if( is_null( $delimiter ) ) {
                $delimiter = FileUtils::detectDelimiter( $handle );
                if( $delimiter === false ) {
                    new LogException( 'No delimiter found', __LINE__ );
                    return false;
                }
            }

            while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                if( $skipFirstline ) {  // first line is the header
                    $skipFirstline = false;
                    continue;
                }
                if( is_null( $header ) || ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                            self::HEADER_REPORTED, self::HEADER_HOSPITALIZED, self::HEADER_DECEASED );
                } else { // add a line of csv-data to our data array
                    if( ! $lastPart && $lastPartDate == $row[2] ) $lastPart = true; // We have come to the last part 
                    if( count( $row ) == $fieldsCount && $lastPart ) {
                        $data[] = array_combine($header, array( $row[2], $row[3], $row[4], (int) $row[10],
                                                             0, (int) $row[11] ) );
                    }
                }
            }
            if( '7' !== self::checkFileVersion( $handle, self::RIVM_SOURCE_CSV_DELIMITER ) ) {
                new LogException( 'databestand <b>' . $sourceFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }

        // var_dump( array_slice( $data, -50) ); exit();


        return $data;
    }








    


   /**
     * Check whether RIVM_NICE Hospital Admission file should be updated.
     * This should be done after the cumulative file has been updated.
     */
    public static function shouldRIVM_NICEHospitalAdmissionBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            // Check if we already updated today. If so, return false;
            $lastFetched = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM_NICE_HOSPITAL_ADMISSION );
            if ( date( 'j', $now ) == date( 'j', $lastFetched ) ) return false;

            // Check if cumulative file has been updated. If not, 
            // return false. The cumulative file should be updated first.
            $lastFetchedCumulative = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM );
            if ( date( 'j', $now ) != date( 'j', $lastFetchedCumulative ) ) return false;
        }
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM_NICE_HOSPITAL_ADMISSION, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE,
                                    self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE );
    }

    
    /**
     * Download the RIVM_NICE Hospital Admission source file.
     */
    public static function getRIVM_NICEHospitalAdmissionFile() {
        FileUtils::archiveFile( self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE );
        $result = FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE, 
                    self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE );
        if( $result !== false ) {
            self::emptyCache( self::HOSPITAL_ADMISSIONS_NICE_UPDATE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_NICE_HOSPITAL_ADMISSION, time() );
            return true;
        }
        return false;
    }


    /**
     * Read RIVM hospital admission (data from NICE) csv file
     */
    public static function readRIVM_NICEHospitalAdmissionCsv( $type = self::HOSPITAL_ADMISSION_ADMISSION_DATE, $timestamp = 0 ) {
        // get the right file
        if ( $timestamp > 0 ) {
            $date = date( 'Ymd', $timestamp );
            $sourceFile = substr( self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        } else { 
            $sourceFile = self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_CSV_FILE;
        }
        $sourceArchiveFile = self::RIVM_SOURCE_NICE_HOSPITAL_ADMISSION_UP_TO_20211003_CSV_FILE; 
        if( ! FileUtils::isFileAvailable( $sourceFile ) || ! FileUtils::isFileAvailable( $sourceArchiveFile ) ) {
            new LogException( 'One of the two source files for RIVM NICE hospital admissiony not available ', __LINE__ );
            return false;
        }
        // read the file
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();

        // first, read the archive source file
        if( ( $handle = fopen( $sourceArchiveFile, 'r' ) ) !== FALSE ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            switch( $type ) {
                case self::HOSPITAL_ADMISSION_NOTIFICATION :
                    while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                        if( ! $header ) { // first line is the header
                            $fieldsCount = count( $row );
                            $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                                    self::HEADER_HOSPITALIZED );
                        } else { // add a line of csv-data to our data array
                            // empty( $row[3] ) is if no city-code is given. Per day there is a rest-category if city is unknown.
                            if( $row[2] < "2020-03-13" || empty( $row[3] ) || count( $row ) != $fieldsCount ) continue;
                            $data[] = array_combine($header, array( $row[2], (int) substr( $row[3],2 ),
                            str_replace( self::SEARCH, self::REPLACE, $row[4] ), (int) $row[7] ) );
                        }
                    }
                break;
                case self::HOSPITAL_ADMISSION_ADMISSION_DATE :
                default:
                    while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                        if( ! $header ) { // first line is the header
                            $fieldsCount = count( $row );
                            $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                                    self::HEADER_HOSPITALIZED );
                        } else { // add a line of csv-data to our data array
                            // empty( $row[3] ) is if no city-code is given. Per day there is a rest-category if city is unknown.
                            if( $row[2] < "2020-03-13" || empty( $row[3] ) || count( $row ) != $fieldsCount ) continue;
                            $data[] = array_combine($header, array( $row[2], (int) substr( $row[3],2 ),
                            str_replace( self::SEARCH, self::REPLACE, $row[4] ), (int) $row[8] ) );
                        }
                    }
            }
            if( '7' !== self::checkFileVersion( $handle, $delimiter ) ) {
                new LogException( 'databestand <b>' . $sourceArchiveFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }

        // Second, read the second file containing recent updates
        if( ( $handle = fopen( $sourceFile, 'r' ) ) !== FALSE ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            $firstline = true;
            switch( $type ) {
                case self::HOSPITAL_ADMISSION_NOTIFICATION :
                    while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                        if( ! $header ) { // first line is the header
                            $fieldsCount = count( $row );
                            $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                                    self::HEADER_HOSPITALIZED );
                        } else { // add a line of csv-data to our data array
                            // empty( $row[3] ) is if no city-code is given. Per day there is a rest-category if city is unknown.
                            if( $row[2] < "2020-03-13" || empty( $row[3] ) || count( $row ) != $fieldsCount ) continue;
                            $data[] = array_combine($header, array( $row[2], (int) substr( $row[3],2 ),
                            str_replace( self::SEARCH, self::REPLACE, $row[4] ), (int) $row[7] ) );
                        }
                    }
                break;
                case self::HOSPITAL_ADMISSION_ADMISSION_DATE :
                default:
                    while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                        if( $firstline ) {  // first line is the header
                            $firstline = false;
                            continue;
                        }
                        // add a line of csv-data to our data array
                        // empty( $row[3] ) is if no city-code is given. Per day there is a rest-category if city is unknown.
                        if( empty( $row[3] ) || count( $row ) != $fieldsCount ) continue;
                        $data[] = array_combine($header, array( $row[2], (int) substr( $row[3],2 ),
                        str_replace( self::SEARCH, self::REPLACE, $row[4] ), (int) $row[8] ) );
                    }
            }
            if( '7' !== self::checkFileVersion( $handle, $delimiter ) ) {
                new LogException( 'databestand <b>' . $sourceFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }
        return $data;
    }




    /**
     * Read the list of cities and their properties froma static CBS source file.
     * At the moment only the province code is read toigether with the citycode.
     */
    public static function readCityPropertiesCsv(){ 
                // Check if the source file is available
                if( ! FileUtils::isFileAvailable( self::CBS_SOURCE_CITY_PROPERTIES_CSV_FILE ) ) {
                    return false;
                }
                $header = NULL;
                $fieldsCount = NULL;
                $data = array();
                if( ( $handle = fopen( self::CBS_SOURCE_CITY_PROPERTIES_CSV_FILE, 'r' ) ) !== false ) {
                    $delimiter = FileUtils::detectDelimiter( $handle );
                    if( $delimiter === false ) {
                        new LogException( 'No delimiter found', __LINE__ );
                        return false;
                    }
                    while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== false ) {
                        if( ! $header ) { // first line is the header
                            $fieldsCount = count( $row );
                            $header = array( self::HEADER_CITY_CODE, self::HEADER_PROVINCE_CODE );
                        } else { // add a line of csv-data to our data array
                            if( count( $row ) == $fieldsCount ) {
                                $data[] = array_combine($header, array( (int) $row[0], (int) $row[3] ) );
                            }
                        }
                    }
                    fclose($handle);
                }
                return $data;
            }











            


    /**
     * Check whether RIVM INFECTION RADAR POSITIVE TESTS file should be updated.
     */
    public static function shouldInfectionRadarPositiveTestsBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            // Check if we already updated today. If so, return false;
            $lastFetchedInectionRadarPositiveTests = FileUtils::readCookie( self::COOKIE_LAST_FETCH_INFECTION_RADAR_POSITIVE_TESTS );
            if ( date( 'j', $now ) == date( 'j', $lastFetchedInectionRadarPositiveTests ) ) return false;
        }
        // No check from the outside if we have new data. So download always.
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_INFECTION_RADAR_POSITIVE_TESTS, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE,
                                                                self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE );
    }


    /**
     * Download the RIVM INFECTION RADAR POSITIVE TESTS source file.
     */
    public static function getRemoteInfectionRadarPositiveTestsFile() {
        if( file_exists( self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE ) ) { // archive current file
            if( ! FileUtils::archiveFile( self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE ) ) return false;
        }
        if( ! FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE,
                                        self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE ) ) return false;
        self::emptyCache( self::INFECTION_RADAR_POSITIVE_TESTS_UPDATE ); // empty cache.
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_INFECTION_RADAR_POSITIVE_TESTS, time() );
        return true;
    }



    /**
     * Read the RIVM INFECTION RADAR POSITIVE TESTS file
     */
    public static function readInfectionRadarPositiveTestsFile( $timestamp = 0 ) {
        if ( $timestamp > 0 ) {
            $date = date( 'Ymd', $timestamp );
            $sourceFile = substr( self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        } else { 
            $sourceFile = self::RIVM_INFECTION_RADAR_POSITIVE_TESTS_CSV_FILE;
        }
        if( ! FileUtils::isFileAvailable( $sourceFile ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( $sourceFile, 'r' ) ) !== false ) {
                $delimiter = FileUtils::detectDelimiter( $handle );
                if( $delimiter === false ) {
                    new LogException( 'No delimiter found', __LINE__ );
                    return false;
                }
            while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_POSITIVE_TESTS_PERCENTAGE );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        // fucking RIVM outputs decimals somtimes with comma and sometimes using the dot. So we need to check.
                        $data[] = array_combine( $header, array( $row[2], (float) str_replace( ',', '.', $row[3] ) ) );
                    }
                }
            }
            if( '1' !== self::checkFileVersion( $handle, $delimiter ) ) {
                new LogException( 'databestand <b>' . $sourceFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }

        return $data;
    }



    







































    








    /**
     * Check whether RIVM VACCINATED PER CITY file should be updated.
     */
    public static function shouldVaccinationsPerCityBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            // Check if we already updated today. If so, return false;
            $lastFetchedVaccinationsPerCity = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_CITY );
            if ( date( 'j', $now ) == date( 'j', $lastFetchedVaccinationsPerCity ) ) return false;
        }
        // No check from the outside if we have new data. So download always.
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM_VACCINATIONS_PER_CITY, $now );
        return true;
    }


    /**
     * Download the RIVM VACCINATED PER CITY source file.
     */
    public static function getRemoteVaccinationsPerCityFile() {
        FileUtils::archiveFile( self::RIVM_SOURCE_VACCINATED_PER_CITY_CSV_FILE );
        $result = FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_VACCINATED_PER_CITY_CSV_FILE, 
                            self::RIVM_SOURCE_VACCINATED_PER_CITY_CSV_FILE );
        if( $result === false ) { 
            new LogException( 'error downloading file', __LINE__ );
            return false;
        }
        self::emptyCache( self::VACCINATION_PER_CITY_UPDATE ); // empty cache.
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_CITY, time() );
        return true;
    }


    /**
     * Read the RIVM VACCINATED PER CITY file
     */
    public static function readVaccinationsPerCityFile( $timestamp = 0 ) {
        if ( $timestamp > 0 ) {
            $date = date( 'Ymd', $timestamp );
            $sourceFile = substr( self::RIVM_SOURCE_VACCINATED_PER_CITY_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        } else { 
            $sourceFile = self::RIVM_SOURCE_VACCINATED_PER_CITY_CSV_FILE;
        }
        if( ! FileUtils::isFileAvailable( $sourceFile ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( $sourceFile, 'r' ) ) !== false ) {
                $delimiter = FileUtils::detectDelimiter( $handle );
                if( $delimiter === false ) {
                    new LogException( 'No delimiter found', __LINE__ );
                    return false;
                }
            while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, 
                                    self::HEADER_VACCINATED_PERSONS_AT_LEAST_ONE_DOSE, 
                                    self::HEADER_VACCINATED_PERSONS_FULLY, self::HEADER_AGEGROUP );
                } else { // add a line of csv-data to our data array
                    if( false === strpos( $row[4], 'GM' ) ) continue;   // Read only 'gemeente' and not 'veiligheidsregio'
                    switch( $row[9] ) {
                        case '12+':
                            $agegroup = self::AGEGROUP_12_AND_ABOVE;
                        break;
                        case '18+':
                            $agegroup = self::AGEGROUP_18_AND_ABOVE;
                        break;
                        case '12-17':
                            $agegroup = self::AGEGROUP_12_TO_17;
                        break;
                    }
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine( $header, array( $row[2], (int) substr( $row[4],2 ),
                                            str_replace( self::SEARCH, self::REPLACE, $row[5] ),
                                            $row[7], $row[8], $agegroup ) );
                    }


                    // Met extra column: fout van RIVM
                    /*
                    if( false === strpos( $row[5], 'GM' ) ) continue;   // Read only 'gemeente' and not 'veiligheidsregio'
                    switch( $row[10] ) {
                        case '12+':
                            $agegroup = self::AGEGROUP_12_AND_ABOVE;
                        break;
                        case '18+':
                            $agegroup = self::AGEGROUP_18_AND_ABOVE;
                        break;
                        case '12-17':
                            $agegroup = self::AGEGROUP_12_TO_17;
                        break;
                    }
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine( $header, array( $row[2], (int) substr( $row[5],2 ),
                                            str_replace( self::SEARCH, self::REPLACE, $row[6] ),
                                            $row[8], $row[9], $agegroup ) );
                    }
                    */
                }
            }
            if( '3' !== self::checkFileVersion( $handle, $delimiter ) ) {
                new LogException( 'databestand <b>' . $sourceFile . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }
        return $data;
    }



    



















   /**
     * Check whether RIVM VACCINATED PER NEIGHBOURHOOD file should be updated.
     */
    public static function shouldVaccinationsPerNeighbourhoodBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            // Check if we already updated today. If so, return false;
            $lastFetchedVaccinationsPerNeigbourhood = FileUtils::readCookie( self::COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_NEIGHBOURHOOD );
            if ( date( 'j', $now ) == date( 'j', $lastFetchedVaccinationsPerNeigbourhood ) ) return false;
        }
        // No check from the outside if we have new data. So download always.
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM_VACCINATIONS_PER_NEIGHBOURHOOD, $now );
        return true;
    }


    /**
     * Download the RIVM VACCINATED PER NEIGHBOURHOOD source file.
     */
    public static function getRemoteVaccinationsPerNeighbourhoodFile() {

        // TESTING
        return false;


        FileUtils::archiveFile( self::RIVM_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE );
        $result = FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE, 
                            self::RIVM_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE );
        if( $result === false ) { 
            new LogException( 'error downloading file', __LINE__ );
            return false;
        }
        self::emptyCache( self::VACCINATION_PER_NEIGHBOURHOOD_UPDATE ); // empty cache.
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_VACCINATIONS_PER_NEIGHBOURHOOD, time() );
        return true;
    }


    /**
     * Read the RIVM VACCINATED PER NEIGHBOURHOOD file
     */
    public static function readVaccinationsPerNeighbourhoodFile( $timestamp = 0 ) {
        if ( $timestamp > 0 ) {
            $date = date( 'Ymd', $timestamp );
            $sourceFile = substr( self::RIVM_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE, 0, -4 ) . "." . $date . ".csv";
        } else { 
            $sourceFile = self::RIVM_SOURCE_VACCINATED_PER_NEIGHBOURHOOD_CSV_FILE;
        }
        if( ! FileUtils::isFileAvailable( $sourceFile ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( $sourceFile, 'r' ) ) !== false ) {
                $delimiter = FileUtils::detectDelimiter( $handle );
                if( $delimiter === false ) {
                    new LogException( 'No delimiter found', __LINE__ );
                    return false;
                }
            while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_CITY_CODE, self::HEADER_CITY_NAME,
                                    self::HEADER_NEIGHBOURHOOD_CODE, self::HEADER_NEIGHBOURHOOD_NAME,
                                    self::HEADER_VACCINATED_PERSONS_AT_LEAST_ONE_DOSE, 
                                    self::HEADER_VACCINATED_PERSONS_FULLY, self::HEADER_AGEGROUP );
                } else { // add a line of csv-data to our data array
                    if( false === strpos( $row[5], 'WK' ) ) continue;   // Read only 'wijk' and not 'gemeente' of 'veiligheidsregio'
                    switch( $row[10] ) {
                        case '12+':
                            $agegroup = self::AGEGROUP_12_AND_ABOVE;
                        break;
                        case '18+':
                            $agegroup = self::AGEGROUP_18_AND_ABOVE;
                        break;
                        case '12-17':
                            $agegroup = self::AGEGROUP_12_TO_17;
                        break;
                    }
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine( $header, array( $row[2], $row[3],
                                            str_replace( self::SEARCH, self::REPLACE, $row[4] ),
                                            $row[5],$row[6],
                                            $row[8], $row[9], $agegroup ) );
                            /*
                            $data[] = array_combine( $header, array( $row[2], (int) substr( $row[3],2 ),
                                str_replace( self::SEARCH, self::REPLACE, $row[4] ),
                                (int) substr( $row[5],2 ),$row[6],
                                $row[8], $row[9], $agegroup ) );
                            */
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }







































    /**
    * It is not clear when waste water measurements are updated.
    * At this time, it is only once a week, most of the time on tuesdays.
    * But RIVM also states updates could happen daily.
    */
    public static function shouldWasteWaterBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            $lastChecked = FileUtils::readCookie( self::COOKIE_LAST_CHECK_WASTE_WATER );
            $now = time();
            $checkInterval = 3600;  // 60*60 = one hour 
            if ( ( $now - $lastChecked ) < $checkInterval ) return false;
        }
        // Do the check
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_WASTE_WATER, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE , 
                                                        self::RIVM_SOURCE_WASTE_WATER_CSV_FILE  );
    }


    /**
     * Download the RIVM CSV waste water source file.
     */
    public static function getRemoteWasteWaterFile() {
        if( file_exists( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) ) { // archive current file
            if( ! FileUtils::archiveFile( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) ) return false;
        }
        if( ! FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_WASTE_WATER_CSV_FILE,
                                         self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) ) return false;
        self::emptyCache( self::WASTE_WATER_UPDATE ); // empty cache.
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_WASTE_WATER, time() );
        return true;
    }


    /**
     * Reads the RIVM csv waste water souorce file and returns the data 
     */
    public static function readCsvWasteWater() {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( self::RIVM_SOURCE_WASTE_WATER_CSV_FILE, 'r' ) ) !== false ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== false ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_RWZI_CODE, self::HEADER_RWZI_NAME,
                                    self::HEADER_RNA );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        //$row[10] = ( $row[10] == "TRUE" ) ? true : false;
                        $x = ( is_numeric( $row[5] ) ) ? intVal( ( (float) $row[5] ) / 100000000000 ) : NULL;
                        $data[] = array_combine($header, array( $row[2], (int) $row[3], 
                                            str_replace( self::SEARCH, self::REPLACE, ucwords( strtolower( $row[4] ) ) ),
                                            $x ) );
                    }
                }
            }
            if( '2' !== self::checkFileVersion( $handle, $delimiter ) ) {
                new LogException( 'databestand <b>' . self::RIVM_SOURCE_WASTE_WATER_CSV_FILE . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
            }
            fclose($handle);
        }
        return $data;
    }


    /**
     * Reads the properties of the waste water plants and returns the data 
     */
    public static function readCsvWastewaterPlantsProperties() {
           // Check if the source file is available
           if( ! FileUtils::isFileAvailable( self::WASTE_WATER_PLANTS_PROPERTIES_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( self::WASTE_WATER_PLANTS_PROPERTIES_CSV_FILE, 'r' ) ) !== FALSE ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv($handle, 4000, $delimiter ) ) !== false ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array(    self::HEADER_RWZI_CODE, 
                                        self::HEADER_RWZI_X, self::HEADER_RWZI_Y, self::HEADER_INHABITANTS,
                                        self::HEADER_PROVINCE_CODE );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount && ! empty( $row[1] ) ) {
                        $data[] = array_combine($header, array( $row[1], 
                                                        (int) $row[3], (int) $row[4], (int) $row[5],
                                                        $row[22] ) );
                    }
                }
            }
            fclose( $handle );
        }
        return $data;
    }


    /**
     * Reads the properties like inhabitants en province of the waste water plants and returns the data 
     */
    public static function readCsvWastewaterPlantsInhabitantsProvinceProperties() {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::WASTE_WATER_PLANTS_INHABITANTS_PROVINCE_PROPERTIES_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( self::WASTE_WATER_PLANTS_INHABITANTS_PROVINCE_PROPERTIES_CSV_FILE, 'r' ) ) !== FALSE ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv($handle, 1000, $delimiter ) ) !== false ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array(    self::HEADER_RWZI_CODE, 
                                        self::HEADER_INHABITANTS,
                                        self::HEADER_RWZI_PART_PERCENTAGE,
                                        self::HEADER_PROVINCE_CODE );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount && ! empty( $row[0] ) ) {
                        $data[] = array_combine($header, array( $row[0], 
                                                        (int) $row[2], floatval( $row[7] ),
                                                        $row[5] ) );
                    }
                }
            }
            fclose( $handle );
        }
        return $data;
    }



    /**
     * Check if the source file with Number Of Tests done by the GGD is updated
     * 
     */
    public static function shouldNumberOfTestsGGDBeUpdated( $forcedUpdate = false ) {
        $now = time();
        if ( ! $forcedUpdate ) {
            $lastChecked = FileUtils::readCookie( self::COOKIE_LAST_CHECK_RIVM_GGD_NUMBER_OF_TESTS );
            $now = time();
            $checkInterval = 3600;  // 60*60 = one hour 
            if ( ( $now - $lastChecked ) < $checkInterval ) return false;
        }
        // Do the check
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_RIVM_GGD_NUMBER_OF_TESTS, $now );
        return FileUtils::checkRemoteFileSize( self::RIVM_REMOTE_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE , 
                                                        self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE );
    }


    /**
     * Download the RIVM GGD CSV Number Of Tests source file.
     */
    public static function getNumberOfTestsGGDFile() {
        if( file_exists( self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE ) ) { // archive current file
            if( ! FileUtils::archiveFile( self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE ) ) return false;
        }
        if( ! FileUtils::downloadRemoteFile( self::RIVM_REMOTE_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE,
                                         self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE ) ) return false;
        self::emptyCache( self::GGD_NUMBER_OF_TESTS_UPDATE ); // empty cache.
        FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_GGD_NUMBER_OF_TESTS, time() );
        return true;
    }
    
    public static function readNumberOfTestsGGD() {
       // Check if the source file is available
       if( ! FileUtils::isFileAvailable( self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE ) ) {
        return false;
    }
    $header = NULL;
    $fieldsCount = NULL;
    $data = array();
    if( ( $handle = fopen( self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE, 'r' ) ) !== FALSE ) {
        $delimiter = FileUtils::detectDelimiter( $handle );
        if( $delimiter === false ) {
            new LogException( 'No delimiter found', __LINE__ );
            return false;
        }
        while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
            if( ! $header ) { // first line is the header
                $fieldsCount = count( $row );
                $header = array( self::HEADER_DATE, self::HEADER_NUMBER_OF_TESTS, self::HEADER_POSITIVE_TESTS );
            } else { // add a line of csv-data to our data array
                if( count( $row ) == $fieldsCount ) {
                    $data[] = array_combine( $header, array( $row[2], (int) $row[5], (int) $row[6] ) );
                }
            }
        }
        if( '2' !== self::checkFileVersion( $handle, $delimiter ) ) {
            new LogException( 'databestand <b>' . self::RIVM_SOURCE_GGD_NUMBER_OF_TESTS_CSV_FILE . '</b> heeft afwijkende structuur. Ga naar het bestand en kijk wat de nieuwe indeling is.', __LINE__ );
        }
        fclose( $handle );
    }
    return $data;
}

















    /**
     * NICE source data is updated every 15 minutes. To keep it in sync with daily updates
     * from RIVM, we update only once a day after 14.00 o'clock. 
    */
    public static function shouldNICEIcuIntakeBeUpdated( $forcedUpdate = false ) {
        $lastFetchedNICE = FileUtils::readCookie( self::COOKIE_LAST_FETCH_NICE );
        $lastCheckedNICE = ( ! $forcedUpdate ) ? FileUtils::readCookie( self::COOKIE_LAST_CHECK_NICE ) : 0;
        $checkPoint = strtotime( "13:59:00" );
        $now = time();
        if ( $now < $checkPoint ) return false; // only check for update after 14.00 hours.
        if ( date( 'j', $checkPoint) == date( 'j', $lastFetchedNICE ) ) return false;  // only update once a day.
        return true;
    }


    /**
     * Download the NICE JSON source file on *ICU intake*.
     */
    public static function getRemoteNICEIcuIntakeFile( $actionTime = 0 ) {
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_NICE, time() );
        $contents = file_get_contents( self::NICE_REMOTE_SOURCE_NEW_INTAKE_JSON_FILE );
        if( ! $contents ) return false;
        if( file_put_contents( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE, $contents, LOCK_EX ) ) {
            if( file_exists( self::CACHE_FILE_NICE_NEW_INTAKE ) ) unlink( self::CACHE_FILE_NICE_NEW_INTAKE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_NICE, time() );
            return true;
        }
        return false;
    }
    
    /**
     * Reads the NICE json source file on *ICU intake* and returns the data
     * $day { timestamp } fetch the source file of that day
     */
    public static function readNICEIcuIntakeJson( $dateTime = false ) {
        if ( false == $dateTime ) {
            $sourceFile = self::NICE_SOURCE_NEW_INTAKE_JSON_FILE;
        } else {
            $date = $dateTime->format( 'Ymd' );
            $sourceFile = substr( self::NICE_SOURCE_NEW_INTAKE_JSON_FILE , 0, -5 ) . "." . $date . ".json";
        }
        if( FileUtils::isFileAvailable( $sourceFile ) ) {
            return json_decode( file_get_contents( $sourceFile ), true );
        }
        return false;
    }



    /**
     * Download the NICE JSON source file on *hospital admission*
     */
    public static function getRemoteNICEHospitalAdmissionFile( $createBackup = false ) {
        $contents = file_get_contents( self::NICE_REMOTE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE );
        if( file_put_contents( self::NICE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE, $contents, LOCK_EX ) ) {
            if( file_exists( self::CACHE_FILE_NICE_HOSPITAL_ADMISSION ) ) unlink( self::CACHE_FILE_NICE_HOSPITAL_ADMISSION );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_NICE_HOSPITAL_ADMISSION, time() );
            return true;
        }
        return false;
    }
    
    /**
     * Reads the NICE json source file on *hospital admission* and returns the data
     * $day { timestamp } fetch the source file of that day
     */
    public static function readNICEHospitalAdmissionJson( $dateTime = false ) {
        if ( false == $dateTime ) {
            $sourceFile = self::NICE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE;
        } else {
            $date = $dateTime->format('Ymd');
            $sourceFile = substr( self::NICE_SOURCE_HOSPITAL_ADMISSION_JSON_FILE, 0, -5 ) . "." . $date . ".json";
        }
        if( FileUtils::isFileAvailable( $sourceFile ) ) {
            return json_decode( file_get_contents( $sourceFile ), true );
        }
        return false;
    }











    /**
     * LCPS DATA
     * Check if we should update LCPS data
     */
    public static function shouldLCPSBeUpdated( $forcedUpdate = false ) {
        $lastFetchedLCPS = FileUtils::readCookie( self::COOKIE_LAST_FETCH_LCPS_HOSPITAL_DATA );
        $lastCheckedLCPS = ( ! $forcedUpdate ) ? FileUtils::readCookie( self::COOKIE_LAST_CHECK_LCPS_HOSPITAL_DATA ) : 0;
        $checkPoint = 21312340; // strtotime( "13:59:00" );
        $now = time();
        if ( $now < $checkPoint ) return false; // only check for update after 14.00 hours.
        if ( date( 'j', $checkPoint) == date( 'j', $lastFetchedLCPS ) ) return false;  // only update once a day.
        return true;
    }

    /**
     * Is a new datafeed available at lcps.nu?
     */
    public static function isNewLCPSFileAvailable( $forcedUpdate = false ) {
        FileUtils::writeCookie( self::COOKIE_LAST_CHECK_LCPS_HOSPITAL_DATA, time() );
        return FileUtils::checkRemoteFileSize( self::LCPS_REMOTE_SOURCE_HOSPITAL_COUNT_CSV_FILE, 
                                                self::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE );
    }

    /**
     * Download the remote LCPS data file
     */
    public static function getRemoteLCPSFile() {
        FileUtils::archiveFile( self::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE );
        $result = FileUtils::downloadRemoteFile( self::LCPS_REMOTE_SOURCE_HOSPITAL_COUNT_CSV_FILE, 
                                self::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE );
        if( $result !== false ) {
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_LCPS_HOSPITAL_DATA, time() );
            return true;
        }
        return false;
    }


    /**
     * Reads the LCPS CSV file and return the data 
     */
    public static function readLCPSHospitalCountCsv( $cropped = true, $onlyContagious = false ) {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if (($handle = fopen( self::LCPS_SOURCE_HOSPITAL_COUNT_CSV_FILE, 'r')) !== FALSE) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            } if( $onlyContagious ) {
                while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE) {
                    if( ! $header ) { // first line is the header
                        $fieldsCount = count( $row );
                        $header = array( self::HEADER_DATE, self::HEADER_INTENSIVE_CARED, self::HEADER_INTENSIVE_CARED_INTERNATIONAL, 
                                self::HEADER_HOSPITALIZED, self::HEADER_INTENSIVE_CARE_INTAKE, self::HEADER_HOSPITAL_INTAKE );
                    } else { // add a line of csv-data to our data array
                        if( count( $row ) == $fieldsCount ) {
                            $data[] = array_combine($header, array( $row[0], (int) $row[3],(int) $row[4], (int) $row[7], (int) $row[10], (int) $row[11] ) );
                        }
                        if( $cropped && '12-12-2022' == $row[0] ) break;
                    }
                }


            } else {
                while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE) {
                    if( ! $header ) { // first line is the header
                        $fieldsCount = count( $row );
                        $header = array( self::HEADER_DATE, self::HEADER_INTENSIVE_CARED, self::HEADER_INTENSIVE_CARED_INTERNATIONAL, 
                                self::HEADER_HOSPITALIZED, self::HEADER_INTENSIVE_CARE_INTAKE, self::HEADER_HOSPITAL_INTAKE );
                    } else { // add a line of csv-data to our data array
                        if( count( $row ) == $fieldsCount ) {
                            $data[] = array_combine($header, array( $row[0], (int) $row[3] + (int) $row[5],(int) $row[4], (int) $row[7] + (int) $row[8], (int) $row[10], (int) $row[11] ) );
                        }
                        if( $cropped && '12-12-2022' == $row[0] ) break;
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }



    /**
     * Reads the RIVM Histocial Hospital Admissions CSV file and return the data 
     */
    public static function readLCPSHistoricalHospitalCountCsv() {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::LCPS_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if (($handle = fopen( self::LCPS_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE, 'r')) !== FALSE) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_INTENSIVE_CARED, self::HEADER_INTENSIVE_CARED_INTERNATIONAL, 
                            self::HEADER_HOSPITALIZED, self::HEADER_INTENSIVE_CARE_INTAKE, self::HEADER_HOSPITAL_INTAKE );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[0], (int) $row[1], (int) $row[2], (int) $row[4], (int) $row[5], (int) $row[6] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }








    /**
     * Reads the RIVM Histocial Hospital Admissions CSV file and return the data 
     */
    public static function readRIVMHistoricalHospitalCountCsv() {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::RIVM_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if (($handle = fopen( self::RIVM_HISTORICAL_SOURCE_HOSPITAL_COUNT_CSV_FILE, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_HOSPITAL_INTAKE );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[0], (int) $row[1] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }

















    /**
     * Reads the CBS csv source file and returns the data 
     */
    public static function readCsvCBS( $version = '2022.2' ) {
        $sourceFile = str_replace( '{version}', $version, self::CBS_INHABITANTS_SOURCE_CSV_FILE );
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( $sourceFile ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if ( ( $handle = fopen( $sourceFile, 'r')) !== FALSE) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_CITY_CODE, self::HEADER_CITY_NAME, self::HEADER_INHABITANTS );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) == $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[0], $row[1], $row[2] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }



    




   /**
    * Read the corona dashboard values and retrun the data.
    */
    public static function readCoronaDashboardData() {
        $filename = str_replace( '{date}', date("Ymd"), FileManager::CORONA_DASHBOARD_DATA_JSON_FILE );
        if ( ! FileUtils::isFileAvailable( $filename ) ) return false;
        return json_decode( file_get_contents( $filename ) );
    }


   /**
    * Save the corona dashboard values to disk.
    * A seperate file is created for each day..
    * If a file for that day already exists, it will be overwritten by default.
    * 
    * @var data { json } the JSON string holding the raw corona dashboard data.
    * @var overwrite { boolean } default: overwrite existing version of the daily file.
    */
    public static function saveCoronaDashboardData( $data ) {
       $filename = str_replace( '{date}', date("Ymd"), FileManager::CORONA_DASHBOARD_DATA_JSON_FILE );
       return file_put_contents( $filename , json_encode( $data ), LOCK_EX );
   }







    /**
     * Read the RIVM Vaccinated file and return the data 
     */
    public static function readRIVMVaccinated() {
        if ( ! FileUtils::isFileAvailable( self::RIVM_VACCINATED_JSON_FILE ) ) return false;
        return json_decode( file_get_contents( self::RIVM_VACCINATED_JSON_FILE ), true );
    }

    /**
     * Save the RIVM Vaccinated file 
     */
    public static function saveRIVMVaccinated( $series ) {
        FileUtils::archiveFile( self::RIVM_VACCINATED_JSON_FILE );
        if( file_put_contents( self::RIVM_VACCINATED_JSON_FILE,  json_encode( $series ), LOCK_EX ) ) {
            if( file_exists( self::CACHE_RIVM_VACCINATED_FILE ) ) unlink( self::CACHE_RIVM_VACCINATED_FILE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_VACCINATED, time() );
            return true;
        }
        return false;
    }





    /**
     * Reads the RIVM GGD Percentage Positive Tests file and return the data 
     */
    /* DEPRECATED
    public static function readRIVMInfectionPercentage() {
        if ( ! FileUtils::isFileAvailable( self::RIVM_INFECTED_PERCENTAGE_JSON_FILE ) ) return false;
        return json_decode( file_get_contents( self::RIVM_INFECTED_PERCENTAGE_JSON_FILE ), true );
    }
*/
    /**
     * Reads the RIVM GGD Percentage Positive Tests file and return the data 
     */
    /*DEPRECATED
    public static function saveRIVMInfectionPercentage( $series ) {
        if( file_put_contents( self::RIVM_INFECTED_PERCENTAGE_JSON_FILE,  json_encode( $series ), LOCK_EX ) ) {
            if( file_exists( self::CACHE_RIVM_INFECTED_PERCENTAGE_FILE ) ) unlink( self::CACHE_RIVM_INFECTED_PERCENTAGE_FILE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_RIVM_INFECTED_PERCENTAGE, time() );
            return true;
        }
        return false;
    }

*/







    /**
     * Download the OWID CSV Vaccinated source file.
     */
    public static function saveCsvOWIDVaccinated( $data ) {
        if( file_exists( self::OWID_SOURCE_VACCINATED_CSV_FILE ) ) { // archive current file
            if( ! FileUtils::archiveFile( self::OWID_SOURCE_VACCINATED_CSV_FILE ) ) return false;
        }
        if( file_put_contents( self::OWID_SOURCE_VACCINATED_CSV_FILE, $data ) ) {
            if( file_exists( self::CACHE_RIVM_VACCINATED_FILE ) ) unlink( self::CACHE_RIVM_VACCINATED_FILE );
            FileUtils::writeCookie( self::COOKIE_LAST_FETCH_OWID_VACCINATED, time() );
            return true;
        }
        return false;
    }

    /**
     * Read OWID CSV Vaccinated source file and return the data 
     */
    public static function readCsvOWIDVaccinated() {
        // Check if the source file is available
        if( ! FileUtils::isFileAvailable( self::OWID_SOURCE_VACCINATED_CSV_FILE ) ) {
            return false;
        }
        $header = NULL;
        $fieldsCount = NULL;
        $data = array();
        if( ( $handle = fopen( self::OWID_SOURCE_VACCINATED_CSV_FILE, 'r' ) ) !== false ) {
            $delimiter = FileUtils::detectDelimiter( $handle );
            if( $delimiter === false ) {
                new LogException( 'No delimiter found', __LINE__ );
                return false;
            }
            while( ( $row = fgetcsv( $handle, 3000, $delimiter ) ) !== false ) {
                if( ! $header ) { // first line is the header
                    $fieldsCount = count( $row );
                    $header = array( self::HEADER_DATE, self::HEADER_COUNTRY_CODE, self::HEADER_COUNTRY,
                            self::HEADER_INHABITANTS, self::HEADER_VACCINATED_TOTAL_DOSES, self::HEADER_VACCINATED_PERSONS_FULLY );
                } else { // add a line of csv-data to our data array
                    if( count( $row ) === $fieldsCount ) {
                        $data[] = array_combine($header, array( $row[3], $row[0], $row[2], (int) $row[44], (int) $row[34], (int) $row[36] ) );
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }















    
   

    /**
     * Save an array to a cache file.
     * handles several file types: javascript and php.
     * 
     * @var type { enum: javascript, php } the type of cache file 
     * @var varName { string } the name of the variable in case of a javascript cache file
     * @return { int|boolean } the number of bytes that were written to the cache file, or false on failure.
     * 
     * Notice: After several tests it is clear that JSON is best compared to serialize or PHP array in a file or serialize
     */
    public static function saveToCache( $series, $cacheFile, $type = 'php', $varName = '', $append = false ) {
        switch ( $type ) {
            case 'js':
            case 'javascript':
                if( $append ) return file_put_contents( $cacheFile , PHP_EOL . 'var ' . $varName . ' = ' . json_encode( $series ) . ";", FILE_APPEND | LOCK_EX );
                return file_put_contents( $cacheFile , 'var ' . $varName . ' = ' . json_encode( $series ) . ";", LOCK_EX );
            break;
                case 'csv':
                    if( $handle = fopen( $cacheFile, 'w' ) ) {
                        fputcsv( $handle, array_keys( current($series) ), ';' );
                        foreach( $series as $row ) {
                            fputcsv($handle, $row, ';');
                        }
                        fclose( $handle );
                    } else return false;
                    return true;
            break;
            case 'php':
            default:
                return file_put_contents( $cacheFile,  json_encode( $series ), LOCK_EX );
        }
    }


    /**
     * Read an array from a cache file including the name of the array.
     * 
     * @return { array } return the array from cache file or false|null on failure
     */
    public static function readFromCache( $cacheFile ) {
        if ( ! FileUtils::isFileAvailable( $cacheFile ) ) return false;
        return json_decode( file_get_contents( $cacheFile ), true );
    }
    
    /**
     * Read aa textfile .
     * 
     * @return { array } return the array from cache file or false|null on failure
     */
    public static function readText( $textFile ) {
        if ( ! FileUtils::isFileAvailable( $textFile ) ) return false;
        return file_get_contents( $textFile );
    }

    


}
