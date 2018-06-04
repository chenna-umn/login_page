<?php 
$limit = ini_get('memory_limit');
//ini_set('memory_limit', -1);
// ... do heavy stuff
ini_set('memory_limit', $limit);
date_default_timezone_set("Asia/Kolkata");
define("ENVIRONMENT", (isset($_SERVER["CI_ENV"]) ? $_SERVER["CI_ENV"] : "development"));
switch( ENVIRONMENT ) 
{
    case "development":
        error_reporting(-1);
        ini_set("display_errors", 1);
        break;
    case "testing":
    case "production":
        ini_set("display_errors", 0);
        if( version_compare(PHP_VERSION, "5.3", ">=") ) 
        {
            error_reporting(32767 & ~8 & ~8192 & ~2048 & ~1024 & ~16384);
        }
        else
        {
            error_reporting(32767 & ~8 & ~2048 & ~1024);
        }

        break;
    default:
        header("HTTP/1.1 503 Service Unavailable.", true, 503);
        echo "The application environment is not set correctly.";
        exit( 1 );
}
$system_path = "system";
$application_folder = "application";
$view_folder = "";
if( defined("STDIN") ) 
{
    chdir(dirname(__FILE__));
}

if( ($_temp = realpath($system_path)) !== false ) 
{
    $system_path = $_temp . DIRECTORY_SEPARATOR;
}
else
{
    $system_path = strtr(rtrim($system_path, "/\\"), "/\\", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

if( !is_dir($system_path) ) 
{
    header("HTTP/1.1 503 Service Unavailable.", true, 503);
    echo "Your system folder path does not appear to be set correctly. Please open the following file and correct this: " . pathinfo(__FILE__, PATHINFO_BASENAME);
    exit( 3 );
}

define("SELF", pathinfo(__FILE__, PATHINFO_BASENAME));
define("BASEPATH", $system_path);
define("FCPATH", dirname(__FILE__) . DIRECTORY_SEPARATOR);
define("SYSDIR", basename(BASEPATH));
if( is_dir($application_folder) ) 
{
    if( ($_temp = realpath($application_folder)) !== false ) 
    {
        $application_folder = $_temp;
    }
    else
    {
        $application_folder = strtr(rtrim($application_folder, "/\\"), "/\\", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
    }

}
else
{
    if( is_dir(BASEPATH . $application_folder . DIRECTORY_SEPARATOR) ) 
    {
        $application_folder = BASEPATH . strtr(trim($application_folder, "/\\"), "/\\", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
    }
    else
    {
        header("HTTP/1.1 503 Service Unavailable.", true, 503);
        echo "Your application folder path does not appear to be set correctly. Please open the following file and correct this: " . SELF;
        exit( 3 );
    }

}

define("APPPATH", $application_folder . DIRECTORY_SEPARATOR);
if( !isset($view_folder[0]) && is_dir(APPPATH . "views" . DIRECTORY_SEPARATOR) ) 
{
    $view_folder = APPPATH . "views";
}
else
{
    if( is_dir($view_folder) ) 
    {
        if( ($_temp = realpath($view_folder)) !== false ) 
        {
            $view_folder = $_temp;
        }
        else
        {
            $view_folder = strtr(rtrim($view_folder, "/\\"), "/\\", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        }

    }
    else
    {
        if( is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR) ) 
        {
            $view_folder = APPPATH . strtr(trim($view_folder, "/\\"), "/\\", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        }
        else
        {
            header("HTTP/1.1 503 Service Unavailable.", true, 503);
            echo "Your view folder path does not appear to be set correctly. Please open the following file and correct this: " . SELF;
            exit( 3 );
        }

    }

}

define("VIEWPATH", $view_folder . DIRECTORY_SEPARATOR);
require_once(BASEPATH . "core/CodeIgniter.php");

