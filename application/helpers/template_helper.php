<?php 
function pre($arrayData)
{
    echo "<pre>";
    print_r($arrayData);
    echo "</pre>";
}

function getBaseUrl()
{
    $myfile = fopen("projectUrl.txt", "r") or exit( "Unable to open file!" );
    $kbUrl = trim(fgets($myfile));
    fclose($myfile);
    return $kbUrl;
}

function getCurrentUrl()
{
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" || $_SERVER["SERVER_PORT"] == 443 ? "https://" : "http://");
    $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    return $url;
}




