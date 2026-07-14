<?php
/**
 * This file is written by Kalahiri in 2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Combines and minifies javascript files and modules.
 */
class JavascriptMinifier {
        // multi-line comments
    private $regex = array();
    private $inputFiles = array();
    private $outputFile;
    private $outputFileMinified;
    private $version;


    const MINIFIED = 'minified';
    const CONCATENATED = 'concatenated';
    const ORIGINAL_SOURCE_CODE = 'original_source_code';
    

    
    public function __construct( $version ) {
        $this->version = $version;
    }
 
 
    public function addScriptFile( $filename, $path  = '' ) {
        $file = $this->createFullFilename( $filename, $path );
        if( ! file_exists( $file ) ) {
            new LogException( 'javascript file ' . $file . ' not found.', __LINE__ );
            return false;
        }
        $this->inputFiles[] = $file;
    }

    public function setOutputScriptFile( $filename, $path = '' ) {
        $this->outputFile = $this->createFullFilename( $filename, $path );
        $this->outputFileMinified = substr( $this->outputFile, 0, -3 ) . '.min.js';
    }




    public function createOutputfile() {
        if( ! $this->shouldNewFileBeCreated() ) return true;

        // concatenate files
        $completeScript = '/*! Parts:'. PHP_EOL
                            . ' * 1. chartjs plugin for annotation (edited)' . PHP_EOL
                            . ' * 2. main script for corona-lokaal.nl ' . PHP_EOL
                            . ' */' . PHP_EOL
                            . '"use strict";' . PHP_EOL;
        $copyright = '/*!' . PHP_EOL 
                        . '* Script for corona-lokaal.nl' . PHP_EOL 
                        . '* https://www.corona-lokaal.nl/' . $this->outputFile . PHP_EOL 
                        . '* Version: ' . $this->version . PHP_EOL 
                        . '*' . PHP_EOL 
                        . '* Copyright 2020-' . date( "Y" ) . ' Kalahiri' . PHP_EOL 
                        . '* Released and may be used under the GPLv3 license' . PHP_EOL 
                        . '* https://www.gnu.org/licenses/gpl-3.0.html' . PHP_EOL 
                        . ' */' . PHP_EOL;

        foreach( $this->inputFiles as $key => $file ) {
            $module = file_get_contents( $file );
            $pos = strpos( $module, '"use strict"' );
            if( false !== $pos ) {
                $module = substr( $module, $pos + 13 );
            }
            $completeScript .= $module;
            if( 0 == $key ) $completeScript .= $copyright;
        }

        //return file_put_contents( $this->outputFile, $completeScript );
        file_put_contents( $this->outputFile, $completeScript );
        $minifiedScript = $this->minify( $completeScript );
        $this->outputFileMinified = substr( $this->outputFile, 0, -3 ) . '.min.js';
        file_put_contents( $this->outputFileMinified, $minifiedScript );

        return true;

    }



    public function getOutputScripts( $type = self::MINIFIED ) {
        switch ( $type ) {
            case self::MINIFIED:
                echo '<script src="'. BASE_URL . FileUtils::addTimeStamp( $this->outputFileMinified ) . '"></script>' . PHP_EOL;
            break;
            case self::CONCATENATED:
                echo '<script src="'. BASE_URL . FileUtils::addTimeStamp( $this->outputFile ) . '"></script>' . PHP_EOL;
            break;
            case self::ORIGINAL_SOURCE_CODE:
                foreach( $this->inputFiles as $file ) {
                    echo '<script src="'. BASE_URL . FileUtils::addTimeStamp( $file ) . '"></script>' . PHP_EOL;
                }
            break;
        }
        return true;
    }




    private function shouldNewFileBeCreated() {
        if ( ! file_exists( $this->outputFile ) ) return true;

        $outputCreationTime = filemtime( $this->outputFile );
        foreach( $this->inputFiles as $file ) {
            if( filemtime( $file ) > $outputCreationTime ) return true;
        }
        return false;

    }


    private function createFullFilename( $filename, $path ) {
        if( empty( $filename ) ) return false;
        if( ! empty( $path ) && ( '/' !== substr( $path,-1,1 ) ) ) $path .= '/';
        return  $path . $filename ;
    }



    private function minify( $script ) {
        return  JShrinkMinifier::minify( $script ); // source: https://github.com/tedious/JShrink



/* Oude oplossing // Naar een idee van https://github.com/matthiasmullie/minify/blob/master/src/JS.php
        $protectMarker = '---MINIFY---PROTECTED---';
        $protect = array(
            '|://|'
        );
        $mark = array( ':/'. $protectMarker . '/');
        $protected = preg_replace( $protect, $mark, $script );


        //preg_match('/".*?"/', $protected, $matches);

        // uniform line endings, make them all line feed
        $content = str_replace(array("\r\n", "\r"), "\n", $protected);

        // collapse all non-line feed whitespace into a single space
        $content = preg_replace('/[^\S\n]+/', ' ', $content);

        // strip leading & trailing whitespace
        $content = str_replace(array(" \n", "\n "), "\n", $content);

        // collapse consecutive line feeds into just 1
        //$content = preg_replace('/\n+/', "\n", $content);

        $search = array( 
            '/\n?\/\*(.*?)\*\/\n?/s', // comment_multi_line
            '/\/\/.*$/m',  // comment_single_line
            '/[^\S\n]+/'
        );
        $replace = array( '', '', ' ' );
        $minified = preg_replace( $search, $replace, $content );
        return str_replace( array( $protectMarker), '', $minified );

        */
    }


    
}
