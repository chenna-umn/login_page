<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("force_download") ) 
{
function force_download($filename = "", $data = "", $set_mime = false)
{
    if( $filename === "" || $data === "" ) 
    {
        return NULL;
    }

    if( $data === NULL ) 
    {
        if( !@is_file($filename) || ($filesize = @filesize($filename)) === false ) 
        {
            return NULL;
        }

        $filepath = $filename;
        $filename = explode("/", str_replace(DIRECTORY_SEPARATOR, "/", $filename));
        $filename = end($filename);
    }
    else
    {
        $filesize = strlen($data);
    }

    $mime = "application/octet-stream";
    $x = explode(".", $filename);
    $extension = end($x);
    if( $set_mime === true ) 
    {
        if( count($x) === 1 || $extension === "" ) 
        {
            return NULL;
        }

        $mimes =& get_mimes();
        if( isset($mimes[$extension]) ) 
        {
            $mime = (is_array($mimes[$extension]) ? $mimes[$extension][0] : $mimes[$extension]);
        }

    }

    if( count($x) !== 1 && isset($_SERVER["HTTP_USER_AGENT"]) && preg_match("/Android\\s(1|2\\.[01])/", $_SERVER["HTTP_USER_AGENT"]) ) 
    {
        $x[count($x) - 1] = strtoupper($extension);
        $filename = implode(".", $x);
    }

    if( $data === NULL && ($fp = @fopen($filepath, "rb")) === false ) 
    {
        return NULL;
    }

    if( ob_get_level() !== 0 && @ob_end_clean() === false ) 
    {
        @ob_clean();
    }

    header("Content-Type: " . $mime);
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Expires: 0");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $filesize);
    header("Cache-Control: private, no-transform, no-store, must-revalidate");
    if( $data !== NULL ) 
    {
        exit( $data );
    }

    while( !feof($fp) && ($data = fread($fp, 1048576)) !== false ) 
    {
        echo $data;
    }
    fclose($fp);
    exit();
}

}


