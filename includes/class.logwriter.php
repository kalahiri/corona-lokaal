<?php
/**
 * This file is written by Kalahiri in 2017 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Create and write a log file.
 */
class LogWriter {
    // Var are public so they can be set directly from the page where class is instantiated.
    public $mLogFileName;
    private $useOneLine;
    private $phpMode;
    
    public function __construct($mLogFileName = "logfile.php", $useOneLine = true ) {
        $this->mLogFileName=$mLogFileName;
        if ( ! file_exists($this->mLogFileName)) {
            file_put_contents ($this->mLogFileName,"<?php exit(); // read protection\n" );
        }
        $this->useOneLine = $useOneLine;
    }
 
    /**
     * Write a comment to a logFile
     * Standard use like $logFile->write($logText);
     * $flag can be used to overwrite existing file. 
     */
    
    public function write($logText,$flag = FILE_APPEND) {
        date_default_timezone_set('Europe/Amsterdam');
        $timestamp = time();
        $datetime=DateTimeUtils::getShortDate($timestamp)." ".DateTimeUtils::getFullTime($timestamp);
        if ( $this->useOneLine ) {
            $putText = $datetime . "\t" . $logText . PHP_EOL;
        } else {
            $putText = "\n/*\n"; 
            $putText .= $datetime . "\n" . $logText;
            $putText .= "\n*/\n";
        }
        return file_put_contents ($this->mLogFileName,$putText,$flag );
    }
    
}
