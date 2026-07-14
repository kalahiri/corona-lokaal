<?php
/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */


class ColorUtils {


    static public function ()
    // color the track
    for (i = 0; i < steps; i++) {
        context.beginPath();
        context.rect(width*i, 0, width, height);
        context.fillStyle = `hsl(${hslColor.hue},${hslColor.sat}%,${hslColor.light}%)`;
        context.fill();

        hslColor.sat += lightIncrease;
        hslColor.light += lightIncrease;
    } 

    // place the ticks
    for (i = 1; i < steps ; i++) {
        context.beginPath();
        context.rect(width*i-1, 0, 2, height);
        context.fillStyle = tick;
        context.fill();
    }





    static public function createGradient($start_color, $end_color, $size, $thickness ) {

    
      
    
      $start_r  = hexdec( substr( $start_color, 0, 2));
    
      $start_g  = hexdec( substr( $start_color, 2, 2));
    
      $start_b  = hexdec( substr( $start_color, 4, 2));
    
      
    
      $end_r  = hexdecsubstr$end_color, 0, 2));
    
      $end_g = hexdecsubstr$end_color, 2, 2));
    
      $end_b = hexdecsubstr$end_color, 4, 2));
    
      
    
      for ($i=0;$i$size$i++) {
    
        $red = round$start_r - ($start_r$end_r) * ($i / ($size-1)));
    
        $green = round$start_g - ($start_g$end_g) * ($i / ($size-1)));
    
        $blue = round$start_b - ($start_b$end_b) * ($i / ($size-1)));
    
        $color = imagecolorallocate$img, $red, $green, $blue);
    
        if ($orientation=="vertical") {
    
          for ($k=0;$k$thickness$k++)
    
            imagesetpixel$img, $k, $i, $color);
    
        } else {
    
          for ($k=0;$k$thickness$k++)
    
            imagesetpixel$img, $i, $k, $color);
    
        }
    
      }
    
      
    
      return  $img
    
    } 
    




    //   TODO: IS JAVASCRIPT. REWRITE TOT PHP
  /**
    * Convert a hex RGB color string to HSL color values
    *   
    * @param { string } $hexColor '#ff9900'
    * @return { object } hslColor with hue, sat and lum property
    */
    /*
    static public function RGBToHSL( $hexColor ) {

       // convert $hexColor to decimal and fractions of 1.
       let r,g,b;
       if ($hexColor.length == 4) {
           r = $hexColor[1] + $hexColor[1];
           g = $hexColor[2] + $hexColor[2];
           b = $hexColor[3] + $hexColor[3];
       } else {
           r = $hexColor[1] + $hexColor[2];
           g = $hexColor[3] + $hexColor[4];
           b = $hexColor[5] + $hexColor[6];
       }
       r = parseInt( r, 16 ) / 255;
       g = parseInt( g, 16 ) / 255;
       b = parseInt( b, 16 ) / 255;
     
       // Find greatest and smallest channel values
       let cmin = Math.min(r,g,b),
           cmax = Math.max(r,g,b),
           delta = cmax - cmin,
           h,s,l;
    
       // Calculate hue
       if ( delta == 0 ) h = 0;
       else if ( cmax == r ) h = ( ( g - b ) / delta ) % 6;
       else if ( cmax == g ) h = ( b - r ) / delta + 2;
       else h = ( r - g ) / delta + 4;
       h = Math.round( h * 60 );  
       if ( h < 0 ) h += 360;

       // Calculate lightness
       l = ( cmax + cmin ) / 2;

       // Calculate saturation
       s = ( delta == 0 ) ? 0 : delta / (1 - Math.abs(2 * l - 1));
       
       // Multiply l and s by 100 to create percentage
       s = + ( s * 100 ).toFixed( 1 );
       l = + ( l * 100 ).toFixed( 1 );

       return { hue: h, sat: s, light: l };

   }
   */


 



    
}


