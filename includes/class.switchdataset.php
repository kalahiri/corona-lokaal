<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */

/**
 * Selects the dataset to be used, from which source.
 * Some source files regurlarly contain errors. Which this class
 * the site is able to switch to an alternative source if available. 
 */
class SwitchDataset {
    private $dailyNumbersRIVM;
    private $chartNationalHospitalAdmissions;
    private $nationalOverviewHospitalSituation;

    const LCPS = 'lcps';
    const NICE = 'nice';
    const RIVM = 'rivm';
    const RIVM_DAILY_TOTALS = 'rivm_daily_totals';
    const RIVM_CUMULATIVE = 'rivm_cumulative';


    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do.
    }

    public function setHospitalSituation( $source =  ) {

    }





}