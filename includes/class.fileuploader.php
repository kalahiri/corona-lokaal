<?php
/**
 * This file is written by Kalahiri in 2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

/**
$_FILES
    array(5) {
    ["name"]=>
    string(11) "smiley2.jpg"
    ["type"]=>
    string(24) "application/octet-stream"   // FileDrop heeft altijd dit Type, dus daar hebben we niets aan.
    ["tmp_name"]=>
    string(24) "C:\xampp\tmp\phpB20D.tmp"
    ["error"]=>
    int(0)
    ["size"]=>
    int(203964)
  }
 */



/**
 * Class to manage the uploading process of files like images or pdf.
 */
class FileUploader {
    private $userId = 9999999999;
    private $uploadedFiles = array();
    private $uploadedFileList= array();
    private $isMetaData = false;
    private $logWriter;
    const DIR_TMP_UPLOAD = "assets/tmp.upload/";
    const DIR_ROOT = "";

    //FLAGS
    const IMAGE = "image";


    // HEADERS
    const DROP_AREA_INDEX = 'dropAreaIndex';
    const FILE_INDEX = 'fileIndex';
    const USER_ID = 'userId';
    const FILENAME = 'filename';
    const EXTENSION = 'extension';


    public function __construct( $userId = 9999999999 ) {
        $this->logWriter = new LogWriter( 'logfile.fileUpload.php' );
        $this->setUserId( $userId );
    }


    /**
     * Get a specific ID for each user to save meta information on the upload process
     */
    public function setUserId( $userId = 9999999999 ) {
        $this->userId = $userId;
    }


    /**
     * Upload a file to a temporary location to be processed later on.
     * The filename starts with  a timestamp (using microseconds) so the order of the uploads can be known.
     */
    public function uploadFile( $fileInfo , $fileMetaData = '', $type = self::IMAGE ) {
        $this->logWriter->write(' We are in FileUploader::uploadFile()' );
        if( UPLOAD_ERR_OK !== $fileInfo["error"] ) {
            new LogException( 'Error File Upload', __LINE__ );
            return false;
        }
        $extension = $this->checkFileType( $fileInfo , $type= self::IMAGE );
        if( false == $extension ) {
            new LogException( 'Extension Error File Upload', __LINE__ );
            return false;
        }
        $fileUploadName = $this->createFileUploadName( $fileMetaData ) . '.' . $extension;
        $this->logWriter->write(' file Upload Name is ' . $fileUploadName );
        return move_uploaded_file( $fileInfo['tmp_name'], $fileUploadName );
    }



    private function createFileUploadName( $fileMetaData ) {
        $timeId = str_replace('.','', sprintf('%.6f', microtime(TRUE)));
        if( is_array( $fileMetaData ) && isset( $fileMetaData[ self::FILE_INDEX ] ) //) {
                    && is_numeric( $fileMetaData[ self::FILE_INDEX ] ) && is_numeric( $fileMetaData[ self::DROP_AREA_INDEX ] ) )  {
            $fileIndex = (int) $fileMetaData[ self::FILE_INDEX ]; 
            $fileIndex = ( $fileIndex > -1 && $fileIndex < 999 ) ? $fileIndex : 'N/A';
            $dropAreaIndex = (int) $fileMetaData[ self::DROP_AREA_INDEX ];
            $dropAreaIndex = ( $dropAreaIndex > -1 && $dropAreaIndex < 99 ) ? $dropAreaIndex : 'N/A';
            $fileUploadName = Constants::DIR_ROOT . self::DIR_TMP_UPLOAD . 'time-' . $timeId . '_' . self::DROP_AREA_INDEX . '-' . $dropAreaIndex . '_' . self::FILE_INDEX . '-' . $fileIndex .'_userId-' . $this->userId;
            $this->isMetaData = true;
        } else {
            $timeId = str_replace('.','', sprintf('%.6f', microtime(TRUE)));
            $fileUploadName = Constants::DIR_ROOT . self::DIR_TMP_UPLOAD . 'time-' . $timeId .'_userId-'. $this->userId;
        }
        return $fileUploadName ;
    }


    public function moveFile( $filename, $newfilename, $directory, $resize = true ) {
        $result = rename( Constants::DIR_ROOT . self::DIR_TMP_UPLOAD . $filename, $directory . $newfilename );
        
        if( $result && $resize ) {
            $sourceImageFile = $directory . $newfilename;
            if( ! file_exists( $sourceImageFile ) ) return false;
            $imageSize = getimagesize( $sourceImageFile );
            if( 1200 !== $imageSize[0] || 800 !==  $imageSize[1] || "image/png" !== $imageSize["mime"] ) {
                new LogException( 'Invalid File Size or Mime Type', __LINE__ );
                return false;
            }

            // create new image to be the card and to copy the map in. Set background to white
            $destinationImage = imagecreatetruecolor(1320, 660);
            $white = imagecolorallocate( $destinationImage, 255, 255, 255);
            imagefill( $destinationImage, 0, 0, $white );

            // copy source image into card and crop at the same time: Keep only the map, remove title and legend
            $sourceImage = imagecreatefrompng( $sourceImageFile );
            imagecopy(
                $destinationImage,
                $sourceImage,
                55,     // destination x Should be 60, but looks then slightly off centered.
                0,      // destination y
                0,      // source x
                70,     // source y
                1200,   // source width
                660     // source height
            );

            // Save image to final destination
            $saved = imagejpeg( 
                            $destinationImage,  // images
                            Constants::DIR_PRODUCT_IMAGES . Constants::CORONA_TWITTER_CARD_JPG, // path and filename
                            100 // quality: 100 is max
                        );

            // Destroy image in memory. No longer necessary from PHP 8.0
            imagedestroy($destinationImage);
            imagedestroy($sourceImage);

            return $saved;

        }
                    
        return $result;
    }





    /**
     * Delete all files of this user in temporary upload directory 
     */
    public function clearCache() {
        foreach( $this->getUploadedFiles() as $file ) {
            unlink( Constants::DIR_ROOT . self::DIR_TMP_UPLOAD . $file );
        }
        return true;
    }



    /**
     * Check the type of the uploaded file
     * @return string the right extension for the type to add to a filename 
     */
    private function checkFileType( $fileInfo , $type= self::IMAGE ) {
        switch ( $type ) {
            case self::IMAGE:
                $imageInfo = getimagesize( $fileInfo['tmp_name'] );
                $mimeType = ( isset( $imageInfo["mime"] ) ) ? $imageInfo["mime"] : "";
                if( $mimeType == "image/jpeg") return "jpg";
                if( $mimeType == "image/png") return "png";
                return false;
            break;
        }
        return false;
    }

    public function countImagesUploaded() {
        return count( $this->getUploadedFiles() );
    }


    /**
     * Get all images file names of the current user in the temporary upload folder
     */
    private function getUploadedFiles() {
        if( empty( $this->uploadedFiles ) ) {
            $cacheFiles = scandir( self::DIR_ROOT . self::DIR_TMP_UPLOAD );
            foreach( $cacheFiles as $cacheFile ) {
                if( strpos( $cacheFile, '_userId-' . $this->userId ) ) $this->uploadedFiles[] = $cacheFile;
            }
        }
        return $this->uploadedFiles;
    }




    public function getUploadedFileList() {
        if( empty( $this->uploadedFileList ) ) {
            $this->getUploadedFiles();
            foreach( $this->uploadedFiles as $key => $filename ) {
                $this->uploadedFileList[$key][ self::FILENAME ] = $filename;
                $this->uploadedFileList[$key][ self::EXTENSION ] = pathinfo($filename, PATHINFO_EXTENSION );
                $filename = pathinfo($filename, PATHINFO_FILENAME);
                $keyValuePairs = explode( '_', $filename );
                foreach( $keyValuePairs as $keyValuePair ) {
                    $onePair = explode('-', $keyValuePair );
                    $this->uploadedFileList[$key][ $onePair[0] ] = $onePair[1];
                }
            }
        }
        return $this->uploadedFileList;
    }




}