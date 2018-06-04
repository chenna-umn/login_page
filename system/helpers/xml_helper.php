<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("xml_convert") ) 
{
function xml_convert($str, $protect_all = false)
{
    $temp = "__TEMP_AMPERSANDS__";
    $str = preg_replace("/&#(\\d+);/", $temp . "\\1;", $str);
    if( $protect_all === true ) 
    {
        $str = preg_replace("/&(\\w+);/", $temp . "\\1;", $str);
    }

    $str = str_replace(array( "&", "<", ">", "\"", "'", "-" ), array( "&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;" ), $str);
    $str = preg_replace("/" . $temp . "(\\d+);/", "&#\\1;", $str);
    if( $protect_all === true ) 
    {
        return preg_replace("/" . $temp . "(\\w+);/", "&\\1;", $str);
    }

    return $str;
}

}


