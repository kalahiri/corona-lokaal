<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */




/** 
 * Autoloads the classes at the time they are called in the project.
 * 
 * This observer function is automatically called when a new object of $className has to be created.
 * Notice: check the right namespace if file containing the class is not found 
 */
spl_autoload_register( function( $className ) {


    


    // create the file name out of the name of the requested class (last part) 
    $className = strtolower( $className );
    $file = "includes/class." .  $className . ".php";


    /**
     * Check if file exists or throw exception
     */
    try {
        if ( ! file_exists( $file ) ) {
            throw new \Exception( $e_message );
        }
        require_once( $file );
    } 
    // File not found
    catch ( \Exception $e ) {
        $stackTrace = $e->getTrace();
        $EOL = '<br>&nbsp;<br>';

        echo '<pre>Fatal Autoloader Exception' . $EOL 
        . 'The required file could not be found in ' 
            . basename($stackTrace[1]["file"]) . ' at line ' . $stackTrace[1]["line"] . '.' . $EOL
        . 'Requested class: ' . esc_html( $className ) . $EOL
        . 'Requested file: ' . esc_html( $file ) . $EOL
        . 'Probably in ' . $stackTrace[1]["file"] . ' at line ' . $stackTrace[1]["line"] . '.' . $EOL . $EOL
        . 'Stacktrace: ' . $EOL;
        var_dump( $stackTrace );
        echo '</pre>';
        wp_die();
        exit();
    }



});