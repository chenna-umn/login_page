<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("directory_map") ) 
{
function directory_map($source_dir, $directory_depth = 0, $hidden = false)
{
    if( $fp = @opendir($source_dir) ) 
    {
        $filedata = array(  );
        $new_depth = $directory_depth - 1;
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        while( false !== ($file = readdir($fp)) ) 
        {
            if( $file === "." || $file === ".." || $hidden === false && $file[0] === "." ) 
            {
                continue;
            }

            is_dir($source_dir . $file) and if( ($directory_depth < 1 || 0 < $new_depth) && is_dir($source_dir . $file) ) 
{
    $filedata[$file] = directory_map($source_dir . $file, $new_depth, $hidden);
}
else
{
    $filedata[] = $file;
}

        }
        closedir($fp);
        return $filedata;
    }

    return false;
}

}


