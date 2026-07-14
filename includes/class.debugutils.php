<?php
/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Tools for debugging. Writes all results to a logfile.
 * 
 * - Show all errors
 * - time duration of operations
 * - check memory usage and peak memory usage
 */
class DebugUtils extends LogWriter {
    private $startTime;

    /**
     * Create a logfile on construction
     * Also turn on error reporting if $showErrors == true.
     */
    public function __construct($showErrors = true, $mLogFileName = "logfile-debug.php") {
        parent::__construct( $mLogFileName );
        if ( $showErrors ) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
        $this->getCallingMethod();
    }


    public function startTimer() {
        $this->startTime = microtime( true );
    }

    public function stopTimer() {
        if ( ! isset( $this->startTime ) || empty( $this->startTime ) ) {
            $this->write( 'Please call startTimer() before using stopTimer()' );
        }
        $duration = microtime( true ) - $this->startTime;
        $this->write( 'Timer duration: ' . $this->printFormattedNumber( $duration, 6 ) );

    }



    /**
     * Dumps the memory peak usage.
     * Call this function at or near the end of a script.
     */
    public function getMemoryPeakUsage() {
        $memPeakUsage = (float) memory_get_peak_usage();
        $this->write( 'Peak memory usage: ' . $this->printFormattedNumber( $memPeakUsage ) );
    }


    /**
     * Dumps the memory usage of a script in a cookie 
     * Call this function at a point in the script where 
     * the memory usage has te be known.
     * Notice: This method requires the FileUtils to write a cookie.
     */
    public function getMemoryUsage() {
        $memUsage = memory_get_usage();
        $this->write( 'Memory usage: ' . $this->printFormattedNumber( $memUsage ) );
    }


    /**
     * Formats (larges) numbers to a more readable form.
     */
    private function printFormattedNumber( $number, $decimals = 2 ) {
        return number_format ( $number , $decimals , ',', '.' );
    }


    private function getCallingMethod() {
        // Create an exception 
        $ex = new Exception(); 
        
        // Call getTrace() function 
        $trace = $ex->getTrace(); 
        
        // Position 0 would be the line 
        // that called this function 
        if( isset($trace[1]["file"] ) ) {
            $pathParts = pathinfo( $trace[1]["file"] );
            $file = $pathParts['basename'];
        } else {
            $file = "unknown";
        }
        $class = ( isset($trace[2]["class"] ) ) ? $trace[2]["class"] : "unknown";
        $method = ( isset($trace[2]["function"] ) ) ? $trace[2]["function"] : "unknown";
        //var_dump( $trace ); exit();
        $this->write( 'Called from file: ' . $file . ', class: ' . $class . ', method: ' . $method ); 

    }


    
}


