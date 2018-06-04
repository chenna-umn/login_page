<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("create_captcha") ) 
{
function create_captcha($data = "", $img_path = "", $img_url = "", $font_path = "")
{
    $defaults = array( "word" => "", "img_path" => "", "img_url" => "", "img_width" => "150", "img_height" => "30", "font_path" => "", "expiration" => 7200, "word_length" => 8, "font_size" => 16, "img_id" => "", "pool" => "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", "colors" => array( "background" => array( 255, 255, 255 ), "border" => array( 153, 102, 102 ), "text" => array( 204, 153, 153 ), "grid" => array( 255, 182, 182 ) ) );
    foreach( $defaults as $key => $val ) 
    {
        if( !is_array($data) && empty($key) ) 
        {
            ${$key} = $val;
        }
        else
        {
            ${$key} = (isset($data[$key]) ? $data[$key] : $val);
        }

    }
    if( $img_path === "" || $img_url === "" || !is_dir($img_path) || !is_really_writable($img_path) || !extension_loaded("gd") ) 
    {
        return false;
    }

    $now = microtime(true);
    $current_dir = @opendir($img_path);
    while( $filename = @readdir($current_dir) ) 
    {
        if( in_array(substr($filename, -4), array( ".jpg", ".png" )) && str_replace(array( ".jpg", ".png" ), "", $filename) + $expiration < $now ) 
        {
            @unlink($img_path . $filename);
        }

    }
    @closedir($current_dir);
    if( empty($word) ) 
    {
        $word = "";
        $pool_length = strlen($pool);
        $rand_max = $pool_length - 1;
        if( function_exists("random_int") ) 
        {
            try
            {
                for( $i = 0; $i < $word_length; $i++ ) 
                {
                    $word .= $pool[random_int(0, $rand_max)];
                }
            }
            catch( Exception $e ) 
            {
                $word = "";
            }
        }

    }

    if( empty($word) ) 
    {
        if( 256 < $pool_length ) 
        {
            return false;
        }

        $security = get_instance()->security;
        if( ($bytes = $security->get_random_bytes($pool_length)) !== false ) 
        {
            for( $byte_index = $word_index = 0; $word_index < $word_length; $word_index++ ) 
            {
                if( $byte_index === $pool_length ) 
                {
                    $i = 0;
                    if( $i < 5 ) 
                    {
                        if( ($bytes = $security->get_random_bytes($pool_length)) === false ) 
                        {
                            continue;
                        }

                        $byte_index = 0;
                        break;
                    }

                    if( $bytes === false ) 
                    {
                        $word = "";
                        break;
                    }

                }

                list(, $rand_index) = unpack("C", $bytes[$byte_index++]);
                if( $rand_max < $rand_index ) 
                {
                    continue;
                }

                $word .= $pool[$rand_index];
            }
        }

    }

    if( empty($word) ) 
    {
        for( $i = 0; $i < $word_length; $i++ ) 
        {
            $word .= $pool[mt_rand(0, $rand_max)];
        }
    }
    else
    {
        if( !is_string($word) ) 
        {
            $word = (string) $word;
        }

    }

    $length = strlen($word);
    $angle = (6 <= $length ? mt_rand(0 - ($length - 6), $length - 6) : 0);
    $x_axis = mt_rand(6, 360 / $length - 16);
    $y_axis = (0 <= $angle ? mt_rand($img_height, $img_width) : mt_rand(6, $img_height));
    $im = (function_exists("imagecreatetruecolor") ? imagecreatetruecolor($img_width, $img_height) : imagecreate($img_width, $img_height));
    is_array($colors) or foreach( array_keys($defaults["colors"]) as $key ) 
{
    is_array($colors[$key]) or $colors[$key] = $defaults["colors"][$key];
    $colors[$key] = imagecolorallocate($im, $colors[$key][0], $colors[$key][1], $colors[$key][2]);
}
    ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $colors["background"]);
    $theta = 1;
    $thetac = 7;
    $radius = 16;
    $circles = 20;
    $points = 32;
    $i = 0;
    for( $cp = $circles * $points - 1; $i < $cp; $i++ ) 
    {
        $theta += $thetac;
        $rad = $radius * $i / $points;
        $x = $rad * cos($theta) + $x_axis;
        $y = $rad * sin($theta) + $y_axis;
        $theta += $thetac;
        $rad1 = $radius * ($i + 1) / $points;
        $x1 = $rad1 * cos($theta) + $x_axis;
        $y1 = $rad1 * sin($theta) + $y_axis;
        imageline($im, $x, $y, $x1, $y1, $colors["grid"]);
        $theta -= $thetac;
    }
    $use_font = $font_path !== "" && file_exists($font_path) && function_exists("imagettftext");
    if( $use_font === false ) 
    {
        5 < $font_size and $x = mt_rand(0, $img_width / ($length / 3));
        $y = 0;
    }
    else
    {
        30 < $font_size and $x = mt_rand(0, $img_width / ($length / 1.5));
        $y = $font_size + 2;
    }

    for( $i = 0; $i < $length; $i++ ) 
    {
        if( $use_font === false ) 
        {
            $y = mt_rand(0, $img_height / 2);
            imagestring($im, $font_size, $x, $y, $word[$i], $colors["text"]);
            $x += $font_size * 2;
        }
        else
        {
            $y = mt_rand($img_height / 2, $img_height - 3);
            imagettftext($im, $font_size, $angle, $x, $y, $colors["text"], $font_path, $word[$i]);
            $x += $font_size;
        }

    }
    imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $colors["border"]);
    $img_url = rtrim($img_url, "/") . "/";
    if( function_exists("imagejpeg") ) 
    {
        $img_filename = $now . ".jpg";
        imagejpeg($im, $img_path . $img_filename);
    }
    else
    {
        if( function_exists("imagepng") ) 
        {
            $img_filename = $now . ".png";
            imagepng($im, $img_path . $img_filename);
        }
        else
        {
            return false;
        }

    }

    $img = "<img " . (($img_id === "" ? "" : "id=\"" . $img_id . "\"")) . " src=\"" . $img_url . $img_filename . "\" style=\"width: " . $img_width . "; height: " . $img_height . "; border: 0;\" alt=\" \" />";
    ImageDestroy($im);
    return array( "word" => $word, "time" => $now, "image" => $img, "filename" => $img_filename );
}

}


