<?php
/**
 * This file is written by Kalahiri in 2017 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Throw a new exception
 * Trigger a notice warning and write the exception to the logfile
 */
class LogException extends Exception {
    private $stacktrace;
    private $lineNumber;


    /**
     *  Trigger a warning with level notice and write the error to a log file. 
     */ 
    public function __construct($message, $line = false, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->stacktrace = $this->getTrace();
        $this->lineNumber = $line;
        trigger_error( $this->getErrorMessage( $message ), E_USER_NOTICE );
        if( $this->isErrorLevelNotice() ) {
            echo '</span><br>&nbsp;</br>' . PHP_EOL;    // close the html error notice on screen if E_NOTICES are displayed
        }
        $this->writeToLog( $message );
    }


    /**
     * Write the exception to the log file.
     */
    private function writeToLog( $message ) {
        $logWriter = new LogWriter();
        $logWriter->write( 'Notice: ' . $this->getErrorMessage( $message, false ) );
    }


    /**
     * Construct and format the error message, one for html and one for log file.
     */
    private function getErrorMessage( $message, $html = true ) {
        $linePart = ( $this->lineNumber !== false ) ? ' on line ' . $this->lineNumber : '';
        if( ! isset( $this->stacktrace[0] ) ) return $message . ' in main file' . $linePart;

        if ( $html ) {
            return $message . '<br>&nbsp;<br>Exception triggered at <b>'
                            . $this->stacktrace[0]["class"] . '::' . $this->stacktrace[0]["function"] . '()</b>'
                            . $linePart 
                            . '<br>called from '
                            . $this->stacktrace[0]["file"] . ' on line '
                            . $this->stacktrace[0]["line"]
                            . '<span style="display:none">' . PHP_EOL . '<br>error handler';
        } else {
            return  strip_tags( $message ) 
                            . '. Exception triggered at '
                            . $this->stacktrace[0]["class"] . '::' . $this->stacktrace[0]["function"] . '()'
                            . $linePart 
                            . ' called from '
                            . $this->stacktrace[0]["file"] . ' on line '
                            . $this->stacktrace[0]["line"]; 
            
        }
    }

    /**
     * Get the current error level.
     * 
     * Error levels are set as an integer. This method decompiles
     * the integer in human readable error reporting levels.
     */
    private function getCurrentErrorLevel() {
        $currentLevel = error_reporting();
        $errorLevels = array(
            E_ALL => 'E_ALL',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            E_DEPRECATED => 'E_DEPRECATED',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_STRICT => 'E_STRICT',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_NOTICE => 'E_NOTICE',
            E_PARSE => 'E_PARSE',
            E_WARNING => 'E_WARNING',
            E_ERROR => 'E_ERROR');
        //$result = '';
        $levels = array();
        foreach( $errorLevels as $number => $name ) {
            if( ( $currentLevel & $number ) == $number) {
                // $result .= ( $result != '' ? ',' : '' ) . $name;
                $levels[] = $name;

            }
        }
        // return $result;
        return $levels;
    }


    /**
     * Check whether error reporting E_NOTICE is set.
     */
    private function isErrorLevelNotice() {
        $levels = $this->getCurrentErrorLevel();
        return in_array( 'E_NOTICE', $levels );
    }


}
