<?php
/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Converter for different geographical coordinates systems
 * 
 * Source: https://www.gpscoordinaten.nl/converteer-rd-coordinaten.php
 */
class GeoConverter {
    private $X0;
    private $Y0;
    private $lat0;
    private $lng0;
    private $latpqK = array();
    private $lngpqL= array();
    private $XpqR = array();
    private $YpqS = array();

    /**
     * Constructor: init some constants needed for conversion
     */
    public function __construct() {
        $this->X0 = 155e3;
        $this->Y0 = 463e3;
        $this->lat0 = 52.1551744;
        $this->lng0 = 5.38720621;

        $this->latpqK[1]["p"] = 0;
        $this->latpqK[1]["q"] = 1;
        $this->latpqK[1]["K"] = 3235.65389;
        $this->latpqK[2]["p"] = 2;
        $this->latpqK[2]["q"] = 0;
        $this->latpqK[2]["K"] = -32.58297;
        $this->latpqK[3]["p"] = 0;
        $this->latpqK[3]["q"] = 2;
        $this->latpqK[3]["K"] = -0.2475;
        $this->latpqK[4]["p"] = 2;
        $this->latpqK[4]["q"] = 1;
        $this->latpqK[4]["K"] = -0.84978;
        $this->latpqK[5]["p"] = 0;
        $this->latpqK[5]["q"] = 3;
        $this->latpqK[5]["K"] = -0.0665;
        $this->latpqK[6]["p"] = 2;
        $this->latpqK[6]["q"] = 2;
        $this->latpqK[6]["K"] = -0.01709;
        $this->latpqK[7]["p"] = 1;
        $this->latpqK[7]["q"] = 0;
        $this->latpqK[7]["K"] = -0.00738;
        $this->latpqK[8]["p"] = 4;
        $this->latpqK[8]["q"] = 0;
        $this->latpqK[8]["K"] = 0.0053;
        $this->latpqK[9]["p"] = 2;
        $this->latpqK[9]["q"] = 3;
        $this->latpqK[9]["K"] = -3.9e-4;
        $this->latpqK[10]["p"] = 4;
        $this->latpqK[10]["q"] = 1;
        $this->latpqK[10]["K"] = 3.3e-4;
        $this->latpqK[11]["p"] = 1;
        $this->latpqK[11]["q"] = 1;
        $this->latpqK[11]["K"] = -1.2e-4;
        
        $this->lngpqL[1]["p"] = 1;
        $this->lngpqL[1]["q"] = 0;
        $this->lngpqL[1]["K"] = 5260.52916;
        $this->lngpqL[2]["p"] = 1;
        $this->lngpqL[2]["q"] = 1;
        $this->lngpqL[2]["K"] = 105.94684;
        $this->lngpqL[3]["p"] = 1;
        $this->lngpqL[3]["q"] = 2;
        $this->lngpqL[3]["K"] = 2.45656;
        $this->lngpqL[4]["p"] = 3;
        $this->lngpqL[4]["q"] = 0;
        $this->lngpqL[4]["K"] = -0.81885;
        $this->lngpqL[5]["p"] = 1;
        $this->lngpqL[5]["q"] = 3;
        $this->lngpqL[5]["K"] = 0.05594;
        $this->lngpqL[6]["p"] = 3;
        $this->lngpqL[6]["q"] = 1;
        $this->lngpqL[6]["K"] = -0.05607;
        $this->lngpqL[7]["p"] = 0;
        $this->lngpqL[7]["q"] = 1;
        $this->lngpqL[7]["K"] = 0.01199;
        $this->lngpqL[8]["p"] = 3;
        $this->lngpqL[8]["q"] = 2;
        $this->lngpqL[8]["K"] = -0.00256;
        $this->lngpqL[9]["p"] = 1;
        $this->lngpqL[9]["q"] = 4;
        $this->lngpqL[9]["K"] = 0.00128;
        $this->lngpqL[10]["p"] = 0;
        $this->lngpqL[10]["q"] = 2;
        $this->lngpqL[10]["K"] = 2.2e-4;
        $this->lngpqL[11]["p"] = 2;
        $this->lngpqL[11]["q"] = 0;
        $this->lngpqL[11]["K"] = -2.2e-4;
        $this->lngpqL[12]["p"] = 5;
        $this->lngpqL[12]["q"] = 0;
        $this->lngpqL[12]["K"] = 2.6e-4;
        
        $this->XpqR[1]["p"] = 0;
        $this->XpqR[1]["q"] = 1;
        $this->XpqR[1]["R"] = 190094.945;
        $this->XpqR[2]["p"] = 1;
        $this->XpqR[2]["q"] = 1;
        $this->XpqR[2]["R"] = -11832.228;
        $this->XpqR[3]["p"] = 2;
        $this->XpqR[3]["q"] = 1;
        $this->XpqR[3]["R"] = -114.221;
        $this->XpqR[4]["p"] = 0;
        $this->XpqR[4]["q"] = 3;
        $this->XpqR[4]["R"] = -32.391;
        $this->XpqR[5]["p"] = 1;
        $this->XpqR[5]["q"] = 0;
        $this->XpqR[5]["R"] = -0.705;
        $this->XpqR[6]["p"] = 3;
        $this->XpqR[6]["q"] = 1;
        $this->XpqR[6]["R"] = -2.34;
        $this->XpqR[7]["p"] = 1;
        $this->XpqR[7]["q"] = 3;
        $this->XpqR[7]["R"] = -0.608;
        $this->XpqR[8]["p"] = 0;
        $this->XpqR[8]["q"] = 2;
        $this->XpqR[8]["R"] = -0.008;
        $this->XpqR[9]["p"] = 2;
        $this->XpqR[9]["q"] = 3;
        $this->XpqR[9]["R"] = 0.148;

        $this->YpqS[1]["p"] = 1;
        $this->YpqS[1]["q"] = 0;
        $this->YpqS[1]["S"] = 309056.544;
        $this->YpqS[2]["p"] = 0;
        $this->YpqS[2]["q"] = 2;
        $this->YpqS[2]["S"] = 3638.893;
        $this->YpqS[3]["p"] = 2;
        $this->YpqS[3]["q"] = 0;
        $this->YpqS[3]["S"] = 73.077;
        $this->YpqS[4]["p"] = 1;
        $this->YpqS[4]["q"] = 2;
        $this->YpqS[4]["S"] = -157.984;
        $this->YpqS[5]["p"] = 3;
        $this->YpqS[5]["q"] = 0;
        $this->YpqS[5]["S"] = 59.788;
        $this->YpqS[6]["p"] = 0;
        $this->YpqS[6]["q"] = 1;
        $this->YpqS[6]["S"] = 0.433;
        $this->YpqS[7]["p"] = 2;
        $this->YpqS[7]["q"] = 2;
        $this->YpqS[7]["S"] = -6.439;
        $this->YpqS[8]["p"] = 1;
        $this->YpqS[8]["q"] = 1;
        $this->YpqS[8]["S"] = -0.032;
        $this->YpqS[9]["p"] = 0;
        $this->YpqS[9]["q"] = 4;
        $this->YpqS[9]["S"] = 0.092;
        $this->YpqS[10]["p"] = 1;
        $this->YpqS[10]["q"] = 4;
        $this->YpqS[10]["S"] = -0.054;

    }



    public function gps2X( $b, $c) {
        $a = 0;
        $dlat = 0.36 * ($b - $this->lat0);
        $dlng = 0.36 * ($c - $this->lng0);
        for ($i = 1; 10 > $i; $i++) {
          $a += $this->XpqR[$i]["R"] * pow($dlat, $this->XpqR[$i]["p"]) * pow($dlng, $this->XpqR[$i]["q"]);
        }
        return $this->X0 + $a;
    }


    public function gps2Y($b, $c) {
        $a = 0;
        $dlat = 0.36 * ($b - $this->lat0);
        $dlng = 0.36 * ($c - $this->lng0);
        for ($i = 1; 11 > $i; $i++) {
          $a += $this->YpqS[$i]["S"] * pow($dlat, $this->YpqS[$i]["p"]) * pow($dlng, $this->YpqS[$i]["q"]);
          }
        return $this->Y0 + $a;
    }


    /**
     * Converts Rijksdriehoekscoordinaten (RDC) to latitude
     */
    public function RDC2lat($b, $c) {
        $a = 0;
        $dX = 1e-5 * ($b - $this->X0);
        $dY = 1e-5 * ($c - $this->Y0);
        for ($i = 1; 12 > $i; $i++) {
          $a += $this->latpqK[$i]["K"] * pow($dX, $this->latpqK[$i]["p"]) * pow($dY, $this->latpqK[$i]["q"]);
          }
        return $this->lat0 + $a / 3600;
    }


    /**
     * Converts Rijksdriehoekscoordinaten (RDC) to longitude
     */
    public function RDC2long($b, $c) {
        $a = 0;
        $dX = 1e-5 * ($b - $this->X0);
        $dY = 1e-5 * ($c - $this->Y0);
        for ($i = 1; 13 > $i; $i++) {
          $a += $this->lngpqL[$i]["K"] * pow($dX, $this->lngpqL[$i]["p"]) * pow($dY, $this->lngpqL[$i]["q"]);
          }
        return $this->lng0 + $a / 3600;
    }


    /**
     * Converts Rijksdriehoekscoordinaten (RDC) to standard latitude and longitude values
     */
    public function RDC2latlong( $x , $y ) {
        $latitude = $this->RDC2lat( $x, $y );
        $longitude = $this->RDC2long( $x, $y );
        return array( "lat" => $latitude, "long" => $longitude );
    }
    
    
    
}


