<?php
/**
 * This file is written by Kalahiri in 2020
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */


class FileUtils {
    const COOKIE_PATH = "data/cookies/";


   /**
     * Use last modification timestamp of a file as a file fingerprint.
     * This can be used to reload only files that have been changed and cache 
     * the unchanged files on the users system. 
     */
    static public function addTimeStamp( $file ) {
        if ( ! file_exists( $file ) ) return $file;
        $mTime = filemtime( $file );
        $pathinfo = pathinfo( $file ); 
        return $pathinfo["dirname"] . '/' . $pathinfo["filename"] . '.' . $mTime  . '.' . $pathinfo["extension"];
    }

 
    /**
     * Places a server cookie. Existing cookie with same name is overwritten.
     * 
     * @var cookie { string } name of cookie
     * @var value { mixed } value or content of cookie to be set.
     * @var cookie_path { string } optional to specify a specific cookiefolder
     * @return { boolean } true on succes.
     */
    static public function writeCookie( $cookie,$value, $cookie_path = self::COOKIE_PATH ) {
        $cookie= $cookie_path . "cookie." . $cookie . ".php";
        $result = file_put_contents ($cookie, $value); 
        return ( $result !== false ) ? true : false;
    }

    /**
     * Reads the contents of a server cookie.
     * 
     * @var cookie { string } name of cookie
     * @var cookie_path { string } optional to specify a specific cookiefolder
     * 
     * @return { mixed } the content (value) of the cookie or false on failure.
     */
    static public function readCookie( $cookie, $cookie_path = self::COOKIE_PATH) {
        $cookie= $cookie_path . "cookie." . $cookie . ".php";
        return ( file_exists( $cookie ) ) ? file_get_contents( $cookie ) : false;
    }

    /**
     * Deletes a server cookie.
     * 
     * @var cookie { string } name of cookie
     * @var cookie_path { string } optional to specify a specific cookiefolder
     * 
     * @return { boolean } true on succes.
     */
    static public function deleteCookie( $cookie, $cookie_path = self::COOKIE_PATH ) {
        $cookie = $cookie_path . "cookie." . $cookie . ".php";
        return ( file_exists( $cookie ) ) ? unlink( $cookie ) : true;
    }


    /**
     * Check whether a file is available and readable
     */
    public static function isFileAvailable( $file ) {
        return file_exists( $file ) && is_readable( $file );
    }
    

    /**
     * Archive a file by adding date stamp or UNIX timestamp to filename.
     * If renaming to a file that already exists, it will be overwritten.
     * 
     * @var file { string } filename including path.
     * @var timestamp { boolean } if true, a timestamp is added. Default only file date is used.
     * @return { boolean } true on success
     */
    public static function archiveFile( $file, $timestamp = false ) {
        if ( ! file_exists( $file ) ) return $file;
        $newFile = self::getArchiveFileName( $file, $timestamp );
        return ( rename( $file, $newFile ) ) ? $newFile : false;
    }

    /**
     * Archive a copy of a file by adding date stamp or UNIX timestamp to filename.
     * 
     * @var file { string } filename including path.
     * @var timestamp { boolean } if true, a timestamp is added. Default only file date is used.
     * @var overwrite { boolean } if true, an existing file will be overwritten.
     * @return { boolean } true on success
     */
    public static function archiveCopyFile( $file, $timestamp = false, $overwrite = false ) {
        if ( ! file_exists( $file ) ) return $file;
        $newFile = self::getArchiveFileName( $file, $timestamp );
        if( ! $overwrite && self::isFileAvailable( $newFile ) ) return false;
        return copy( $file, $newFile );
    }

    /**
     * Create the archive file name by adding date stamp or UNIX timestamp to filename.
     * Used bij self::archiveFile() and self::archiveCopyFile()
     * 
     * @var file { string } filename including path.
     * @var timestamp { boolean } if true, a timestamp is added. Default only file date is used.
     * @return { string } the created name for the archive file
     */
    public static function getArchiveFileName( $file, $timestamp ) {
        $archiveStamp = ( $timestamp ) ? filemtime( $file ) : date( 'Ymd', filemtime( $file ) );
        $pathinfo = pathinfo( $file ); 
        $length = strlen( $pathinfo["extension"] );
        return substr( $file, 0, - $length ) . $archiveStamp . "." . $pathinfo["extension"];
    }



    /**
     * Download (large) remote files.
     * 
     * @var remoteFile { string } full URL to remote file
     * @var destinationFile { string } destination file name including full path
     * @return { int|boolean } the total count of bytes copied, or false on failure.
     */
    public static function downloadRemoteFile( $remoteFile, $destinationFile, $cURL = true ) {
        if( $cURL ) { // use cURL library.
            $handleDestination = fopen( $destinationFile, "w+");
            $handleCurl = curl_init( $remoteFile );
            curl_setopt( $handleCurl, CURLOPT_FILE, $handleDestination );
            curl_exec( $handleCurl );
            $httpStatusCode = curl_getinfo( $handleCurl, CURLINFO_HTTP_CODE );
            curl_close( $handleCurl );
            fclose( $handleDestination );
            $result = ( $httpStatusCode == 200 ) ? true : false;
        } else {    // use PHP streams functions. Not always working with thight SSL sites
            $handleSource = fopen( $remoteFile, 'r' );
            $handleDestination = fopen( $destinationFile, 'w' );
            $result = stream_copy_to_stream( $handleSource, $handleDestination );
            fclose( $handleSource );
            fclose( $handleDestination );
        }
        return $result;
    }



    /**
     * Download (large) remote files.
     * 
     * @var remoteFile { string } full URL to remote file
     * @var destinationFile { string } destination file name including full path
     * @return { int|boolean } the total count of bytes copied, or false on failure.
     */
    public static function getRemoteFile( $remoteFile, $cURL = true ) {
        if( $cURL ) { // use cURL library.
            $handleCurl = curl_init( $remoteFile );
            curl_setopt( $handleCurl, CURLOPT_RETURNTRANSFER, true );
            $result = curl_exec( $handleCurl );
            $httpStatusCode = curl_getinfo( $handleCurl, CURLINFO_HTTP_CODE );
            curl_close( $handleCurl );
            if( $httpStatusCode != 200 ) {
                new LogException( 'error reading remote file', __LINE__ );
            }
        } else {    // use PHP streams functions. Not always working with thight SSL sites
            $result = file_get_contents( $remoteFile );
        }
        return $result;
    }





/*
    public static function downloadRemoteFile( $remoteFile, $destinationFile ) {
        $fp = fopen($destinationFile, "w+");

        $ch = curl_init( $remoteFile);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        $st_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        return ( $st_code == 200 ) ? true : false;
    }
    */


    /**
     * Get the file size of a remote file.
     * 
     * @var string remoteFile: full URL to remote file
     * @return int|boolean : the filesize or false on failure.
     */
    public static function getRemoteFileSize( $remoteFile, $cURL = true ) {
        if( $cURL ) { // use cURL library.
            $handleCurl = curl_init();
            curl_setopt( $handleCurl, CURLOPT_URL, $remoteFile );
            curl_setopt( $handleCurl, CURLOPT_HEADER, TRUE );
            curl_setopt( $handleCurl, CURLOPT_NOBODY, TRUE ); // remove body
            curl_setopt( $handleCurl, CURLOPT_RETURNTRANSFER, TRUE );
            $headers = curl_exec( $handleCurl );
            // var_dump( $headers ); exit();
            // $httpCode = curl_getinfo( $handleCurl, CURLINFO_HTTP_CODE );
            $remoteFileSize = curl_getinfo( $handleCurl, CURLINFO_CONTENT_LENGTH_DOWNLOAD );
            curl_close( $handleCurl );
        } else {    // use PHP streams functions. Not always working with thight SSL sites
            /*
            stream_context_set_default( [
                'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
            */
            $headers = get_headers( $remoteFile, 1 );
            // var_dump( $headers ); exit();
            $headers = array_change_key_case( $headers );  //Convert keys to lower to prevent possible mistakes
            $remoteFileSize = ( isset( $headers['content-length'] ) ) ? $headers['content-length'] : false;
        }
        return $remoteFileSize;
    }




/*  cURL testing: get headers and response code
    public static function http_response($url, $getInfo = CURLINFO_CONTENT_LENGTH_DOWNLOAD )
    {
        $status = null; $wait = 3;
            $time = microtime(true);
            $expire = $time + $wait;
    
            // we fork the process so we don't have to wait for a timeout
                // we are the parent
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                var_dump( $head );
                //$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                //$httpCode = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD );
                $returnInfo = curl_getinfo($ch, $getInfo );
                curl_close($ch);
               
                if(!$head)
                {
                    return FALSE;
                }
                return $returnInfo;
 
        }
*/




    /**
     * Check if size of remote file is larger than local file.
     * This is useful if updates are only nexessary if remote file is larger.  
     * 
     * @return { boolean } true if (new) remote file size is larger than most recent downloaded file  
     */
    public static function checkRemoteFileSize( $remoteFile, $localFile ) {
        $remoteFileSize = self::getRemoteFileSize( $remoteFile );
        $localFileSize = ( file_exists( $localFile ) ) ? filesize( $localFile ) : 0;
        $debugUtils = new DebugUtils();
        $debugUtils->write( 'remote file: ' . $remoteFile );
        $debugUtils->write( 'check remote filesize: ' . $remoteFileSize . ' - local filesize: ' . $localFileSize );
        if( ( $remoteFileSize !== false && ( $remoteFileSize > $localFileSize ) ) ) {
            $debugUtils->write( 'download: true' );
        } else {
            $debugUtils->write( 'download: false' );
        }
        return ( $remoteFileSize !== false && ( $remoteFileSize > $localFileSize ) );
    }


    /**
     * Get the file size of a local file. It checks if the file is available and readable
     * 
     * @return int filesize in bytes. If no files available it returns 0.
     */
    public static function getFileSize( $localFile ) {
        return ( self::isFileAvailable( $localFile ) ) ? filesize( $localFile ) : 0;
    }


    /**
     * Detect the delimiter in a csv file.
     * 
     * @var delimiters { array } The possible delimiters to check for
     * @var handle { file handle } The file handle
     * @return { string } the found delimiter or false if none is found
     */
    public static function detectDelimiter( $handle, $delimiters = array( ",", ";" ) ) {
        $csvFirstLine = fgets( $handle );
        $delimitersCount = array();
        foreach ( $delimiters as $delimiter ) {
            $delimitersCount[ $delimiter ] = count(str_getcsv( $csvFirstLine, $delimiter ) );
        }
        rewind( $handle );  // rewind the source file to get it ready to be parsed by calling method/function
        if( array_sum( $delimitersCount ) <= count( $delimitersCount ) ) return false;   // no delimiter found
        return array_search( max( $delimitersCount ), $delimitersCount );
    }





    
    
}


