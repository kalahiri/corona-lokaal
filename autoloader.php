<?php
/**
 * This file is written by Kalahiri in 2020 
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 * 
 */


ini_set('memory_limit', '768M');

// blocklist
//if( in_array($_SERVER["REMOTE_ADDR"],$blocklist );
// if( '84.241.198.42' == $_SERVER["REMOTE_ADDR"] ) {
// if( '2001:1c00:1621:1800:602f:6a1a:f578:627f' == $_SERVER["REMOTE_ADDR"] ) {
if( isset( $_SERVER["REMOTE_ADDR"] ) && '192.33.238.48' == $_SERVER["REMOTE_ADDR"] ) {
    $logwriter = new LogWriter();
    $logwriter->write( 'BLOCKLIST *****************************\n');
    foreach( $_SERVER as $key => $val ) {
        $logwriter->write( $key . ' => ' . $val );
    }
    $logwriter->write( '*****************************\n');
    http_response_code(403);
    header('HTTP/1.0 403 Forbidden', true, 403);
    die('403 - Forbidden');
}



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
    if ( ! file_exists( $file ) ) {
        throw new LogException( 'class '. $className . ' not found in ' . $file );
    }
    require_once( $file );


});