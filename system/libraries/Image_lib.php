<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Image_lib
{
    public $image_library = "gd2";
    public $library_path = "";
    public $dynamic_output = false;
    public $source_image = "";
    public $new_image = "";
    public $width = "";
    public $height = "";
    public $quality = 90;
    public $create_thumb = false;
    public $thumb_marker = "_thumb";
    public $maintain_ratio = true;
    public $master_dim = "auto";
    public $rotation_angle = "";
    public $x_axis = "";
    public $y_axis = "";
    public $wm_text = "";
    public $wm_type = "text";
    public $wm_x_transp = 4;
    public $wm_y_transp = 4;
    public $wm_overlay_path = "";
    public $wm_font_path = "";
    public $wm_font_size = 17;
    public $wm_vrt_alignment = "B";
    public $wm_hor_alignment = "C";
    public $wm_padding = 0;
    public $wm_hor_offset = 0;
    public $wm_vrt_offset = 0;
    protected $wm_font_color = "#ffffff";
    protected $wm_shadow_color = "";
    public $wm_shadow_distance = 2;
    public $wm_opacity = 50;
    public $source_folder = "";
    public $dest_folder = "";
    public $mime_type = "";
    public $orig_width = "";
    public $orig_height = "";
    public $image_type = "";
    public $size_str = "";
    public $full_src_path = "";
    public $full_dst_path = "";
    public $file_permissions = 420;
    public $create_fnc = "imagecreatetruecolor";
    public $copy_fnc = "imagecopyresampled";
    public $error_msg = array(  );
    protected $wm_use_drop_shadow = false;
    public $wm_use_truetype = false;

    public function __construct($props = array(  ))
    {
        if( 0 < count($props) ) 
        {
            $this->initialize($props);
        }

        ini_set("gd.jpeg_ignore_warning", 1);
        log_message("info", "Image Lib Class Initialized");
    }

    public function clear()
    {
        $props = array( "thumb_marker", "library_path", "source_image", "new_image", "width", "height", "rotation_angle", "x_axis", "y_axis", "wm_text", "wm_overlay_path", "wm_font_path", "wm_shadow_color", "source_folder", "dest_folder", "mime_type", "orig_width", "orig_height", "image_type", "size_str", "full_src_path", "full_dst_path" );
        foreach( $props as $val ) 
        {
            $this->$val = "";
        }
        $this->image_library = "gd2";
        $this->dynamic_output = false;
        $this->quality = 90;
        $this->create_thumb = false;
        $this->thumb_marker = "_thumb";
        $this->maintain_ratio = true;
        $this->master_dim = "auto";
        $this->wm_type = "text";
        $this->wm_x_transp = 4;
        $this->wm_y_transp = 4;
        $this->wm_font_size = 17;
        $this->wm_vrt_alignment = "B";
        $this->wm_hor_alignment = "C";
        $this->wm_padding = 0;
        $this->wm_hor_offset = 0;
        $this->wm_vrt_offset = 0;
        $this->wm_font_color = "#ffffff";
        $this->wm_shadow_distance = 2;
        $this->wm_opacity = 50;
        $this->create_fnc = "imagecreatetruecolor";
        $this->copy_fnc = "imagecopyresampled";
        $this->error_msg = array(  );
        $this->wm_use_drop_shadow = false;
        $this->wm_use_truetype = false;
    }

    public function initialize($props = array(  ))
    {
        if( 0 < count($props) ) 
        {
            foreach( $props as $key => $val ) 
            {
                if( property_exists($this, $key) ) 
                {
                    if( in_array($key, array( "wm_font_color", "wm_shadow_color" ), true) ) 
                    {
                        if( preg_match("/^#?([0-9a-f]{3}|[0-9a-f]{6})\$/i", $val, $matches) ) 
                        {
                            $val = (strlen($matches[1]) === 6 ? "#" . $matches[1] : "#" . $matches[1][0] . $matches[1][0] . $matches[1][1] . $matches[1][1] . $matches[1][2] . $matches[1][2]);
                        }
                        else
                        {
                            continue;
                        }

                    }
                    else
                    {
                        if( in_array($key, array( "width", "height" ), true) && !ctype_digit((string) $val) ) 
                        {
                            continue;
                        }

                    }

                    $this->$key = $val;
                }

            }
        }

        if( $this->source_image === "" ) 
        {
            $this->set_error("imglib_source_image_required");
            return false;
        }

        if( !function_exists("getimagesize") ) 
        {
            $this->set_error("imglib_gd_required_for_props");
            return false;
        }

        $this->image_library = strtolower($this->image_library);
        if( ($full_source_path = realpath($this->source_image)) !== false ) 
        {
            $full_source_path = str_replace("\\", "/", $full_source_path);
        }
        else
        {
            $full_source_path = $this->source_image;
        }

        $x = explode("/", $full_source_path);
        $this->source_image = end($x);
        $this->source_folder = str_replace($this->source_image, "", $full_source_path);
        if( !$this->get_image_properties($this->source_folder . $this->source_image) ) 
        {
            return false;
        }

        if( $this->new_image === "" ) 
        {
            $this->dest_image = $this->source_image;
            $this->dest_folder = $this->source_folder;
        }
        else
        {
            if( strpos($this->new_image, "/") === false && strpos($this->new_image, "\\") === false ) 
            {
                $this->dest_image = $this->new_image;
                $this->dest_folder = $this->source_folder;
            }
            else
            {
                if( !preg_match("#\\.(jpg|jpeg|gif|png)\$#i", $this->new_image) ) 
                {
                    $this->dest_image = $this->source_image;
                    $this->dest_folder = $this->new_image;
                }
                else
                {
                    $x = explode("/", str_replace("\\", "/", $this->new_image));
                    $this->dest_image = end($x);
                    $this->dest_folder = str_replace($this->dest_image, "", $this->new_image);
                }

                $this->dest_folder = realpath($this->dest_folder) . "/";
            }

        }

        if( $this->create_thumb === false || $this->thumb_marker === "" ) 
        {
            $this->thumb_marker = "";
        }

        $xp = $this->explode_name($this->dest_image);
        $filename = $xp["name"];
        $file_ext = $xp["ext"];
        $this->full_src_path = $this->source_folder . $this->source_image;
        $this->full_dst_path = $this->dest_folder . $filename . $this->thumb_marker . $file_ext;
        if( $this->maintain_ratio === true && ($this->width !== 0 || $this->height !== 0) ) 
        {
            $this->image_reproportion();
        }

        if( $this->width === "" ) 
        {
            $this->width = $this->orig_width;
        }

        if( $this->height === "" ) 
        {
            $this->height = $this->orig_height;
        }

        $this->quality = trim(str_replace("%", "", $this->quality));
        if( $this->quality === "" || $this->quality === 0 || !ctype_digit($this->quality) ) 
        {
            $this->quality = 90;
        }

        is_numeric($this->x_axis) or is_numeric($this->y_axis) or if( $this->wm_overlay_path !== "" ) 
{
    $this->wm_overlay_path = str_replace("\\", "/", realpath($this->wm_overlay_path));
}

        if( $this->wm_shadow_color !== "" ) 
        {
            $this->wm_use_drop_shadow = true;
        }
        else
        {
            if( $this->wm_use_drop_shadow === true && $this->wm_shadow_color === "" ) 
            {
                $this->wm_use_drop_shadow = false;
            }

        }

        if( $this->wm_font_path !== "" ) 
        {
            $this->wm_use_truetype = true;
        }

        return true;
    }

    public function resize()
    {
        $protocol = ($this->image_library === "gd2" ? "image_process_gd" : "image_process_" . $this->image_library);
        return $this->$protocol("resize");
    }

    public function crop()
    {
        $protocol = ($this->image_library === "gd2" ? "image_process_gd" : "image_process_" . $this->image_library);
        return $this->$protocol("crop");
    }

    public function rotate()
    {
        $degs = array( 90, 180, 270, "vrt", "hor" );
        if( $this->rotation_angle === "" || !in_array($this->rotation_angle, $degs) ) 
        {
            $this->set_error("imglib_rotation_angle_required");
            return false;
        }

        if( $this->rotation_angle === 90 || $this->rotation_angle === 270 ) 
        {
            $this->width = $this->orig_height;
            $this->height = $this->orig_width;
        }
        else
        {
            $this->width = $this->orig_width;
            $this->height = $this->orig_height;
        }

        if( $this->image_library === "imagemagick" || $this->image_library === "netpbm" ) 
        {
            $protocol = "image_process_" . $this->image_library;
            return $this->$protocol("rotate");
        }

        return ($this->rotation_angle === "hor" || $this->rotation_angle === "vrt" ? $this->image_mirror_gd() : $this->image_rotate_gd());
    }

    public function image_process_gd($action = "resize")
    {
        $v2_override = false;
        if( $this->dynamic_output === false && $this->orig_width === $this->width && $this->orig_height === $this->height ) 
        {
            if( $this->source_image !== $this->new_image && @copy($this->full_src_path, $this->full_dst_path) ) 
            {
                chmod($this->full_dst_path, $this->file_permissions);
            }

            return true;
        }

        if( $action === "crop" ) 
        {
            $this->orig_width = $this->width;
            $this->orig_height = $this->height;
            if( $this->gd_version() !== false ) 
            {
                $gd_version = str_replace("0", "", $this->gd_version());
                $v2_override = $gd_version == 2;
            }

        }
        else
        {
            $this->x_axis = 0;
            $this->y_axis = 0;
        }

        if( !($src_img = $this->image_create_gd()) ) 
        {
            return false;
        }

        if( $this->image_library === "gd2" && function_exists("imagecreatetruecolor") ) 
        {
            $create = "imagecreatetruecolor";
            $copy = "imagecopyresampled";
        }
        else
        {
            $create = "imagecreate";
            $copy = "imagecopyresized";
        }

        $dst_img = $create($this->width, $this->height);
        if( $this->image_type === 3 ) 
        {
            imagealphablending($dst_img, false);
            imagesavealpha($dst_img, true);
        }

        $copy($dst_img, $src_img, 0, 0, $this->x_axis, $this->y_axis, $this->width, $this->height, $this->orig_width, $this->orig_height);
        if( $this->dynamic_output === true ) 
        {
            $this->image_display_gd($dst_img);
        }
        else
        {
            if( !$this->image_save_gd($dst_img) ) 
            {
                return false;
            }

        }

        imagedestroy($dst_img);
        imagedestroy($src_img);
        chmod($this->full_dst_path, $this->file_permissions);
        return true;
    }

    public function image_process_imagemagick($action = "resize")
    {
        if( $this->library_path === "" ) 
        {
            $this->set_error("imglib_libpath_invalid");
            return false;
        }

        if( !preg_match("/convert\$/i", $this->library_path) ) 
        {
            $this->library_path = rtrim($this->library_path, "/") . "/convert";
        }

        $cmd = $this->library_path . " -quality " . $this->quality;
        if( $action === "crop" ) 
        {
            $cmd .= " -crop " . $this->width . "x" . $this->height . "+" . $this->x_axis . "+" . $this->y_axis;
        }
        else
        {
            if( $action === "rotate" ) 
            {
                $cmd .= ($this->rotation_angle === "hor" || $this->rotation_angle === "vrt" ? " -flop" : " -rotate " . $this->rotation_angle);
            }
            else
            {
                if( $this->maintain_ratio === true ) 
                {
                    $cmd .= " -resize " . $this->width . "x" . $this->height;
                }
                else
                {
                    $cmd .= " -resize " . $this->width . "x" . $this->height . "\\!";
                }

            }

        }

        $cmd .= " " . escapeshellarg($this->full_src_path) . " " . escapeshellarg($this->full_dst_path) . " 2>&1";
        $retval = 1;
        if( function_usable("exec") ) 
        {
            @exec($cmd, $output, $retval);
        }

        if( 0 < $retval ) 
        {
            $this->set_error("imglib_image_process_failed");
            return false;
        }

        chmod($this->full_dst_path, $this->file_permissions);
        return true;
    }

    public function image_process_netpbm($action = "resize")
    {
        if( $this->library_path === "" ) 
        {
            $this->set_error("imglib_libpath_invalid");
            return false;
        }

        switch( $this->image_type ) 
        {
            case 1:
                $cmd_in = "giftopnm";
                $cmd_out = "ppmtogif";
                break;
            case 2:
                $cmd_in = "jpegtopnm";
                $cmd_out = "ppmtojpeg";
                break;
            case 3:
                $cmd_in = "pngtopnm";
                $cmd_out = "ppmtopng";
                break;
        }
        if( $action === "crop" ) 
        {
            $cmd_inner = "pnmcut -left " . $this->x_axis . " -top " . $this->y_axis . " -width " . $this->width . " -height " . $this->height;
        }
        else
        {
            if( $action === "rotate" ) 
            {
                switch( $this->rotation_angle ) 
                {
                    case 90:
                        $angle = "r270";
                        break;
                    case 180:
                        $angle = "r180";
                        break;
                    case 270:
                        $angle = "r90";
                        break;
                    case "vrt":
                        $angle = "tb";
                        break;
                    case "hor":
                        $angle = "lr";
                        break;
                }
                $cmd_inner = "pnmflip -" . $angle . " ";
            }
            else
            {
                $cmd_inner = "pnmscale -xysize " . $this->width . " " . $this->height;
            }

        }

        $cmd = $this->library_path . $cmd_in . " " . $this->full_src_path . " | " . $cmd_inner . " | " . $cmd_out . " > " . $this->dest_folder . "netpbm.tmp";
        $retval = 1;
        if( function_usable("exec") ) 
        {
            @exec($cmd, $output, $retval);
        }

        if( 0 < $retval ) 
        {
            $this->set_error("imglib_image_process_failed");
            return false;
        }

        copy($this->dest_folder . "netpbm.tmp", $this->full_dst_path);
        unlink($this->dest_folder . "netpbm.tmp");
        chmod($this->full_dst_path, $this->file_permissions);
        return true;
    }

    public function image_rotate_gd()
    {
        if( !($src_img = $this->image_create_gd()) ) 
        {
            return false;
        }

        $white = imagecolorallocate($src_img, 255, 255, 255);
        $dst_img = imagerotate($src_img, $this->rotation_angle, $white);
        if( $this->dynamic_output === true ) 
        {
            $this->image_display_gd($dst_img);
        }
        else
        {
            if( !$this->image_save_gd($dst_img) ) 
            {
                return false;
            }

        }

        imagedestroy($dst_img);
        imagedestroy($src_img);
        chmod($this->full_dst_path, $this->file_permissions);
        return true;
    }

    public function image_mirror_gd()
    {
        if( !($src_img = $this->image_create_gd()) ) 
        {
            return false;
        }

        $width = $this->orig_width;
        $height = $this->orig_height;
        if( $this->rotation_angle === "hor" ) 
        {
            for( $i = 0; $i < $height; $i++ ) 
            {
                $left = 0;
                for( $right = $width - 1; $left < $right; $right-- ) 
                {
                    $cl = imagecolorat($src_img, $left, $i);
                    $cr = imagecolorat($src_img, $right, $i);
                    imagesetpixel($src_img, $left, $i, $cr);
                    imagesetpixel($src_img, $right, $i, $cl);
                    $left++;
                }
            }
        }
        else
        {
            for( $i = 0; $i < $width; $i++ ) 
            {
                $top = 0;
                for( $bottom = $height - 1; $top < $bottom; $bottom-- ) 
                {
                    $ct = imagecolorat($src_img, $i, $top);
                    $cb = imagecolorat($src_img, $i, $bottom);
                    imagesetpixel($src_img, $i, $top, $cb);
                    imagesetpixel($src_img, $i, $bottom, $ct);
                    $top++;
                }
            }
        }

        if( $this->dynamic_output === true ) 
        {
            $this->image_display_gd($src_img);
        }
        else
        {
            if( !$this->image_save_gd($src_img) ) 
            {
                return false;
            }

        }

        imagedestroy($src_img);
        chmod($this->full_dst_path, $this->file_permissions);
        return true;
    }

    public function watermark()
    {
        return ($this->wm_type === "overlay" ? $this->overlay_watermark() : $this->text_watermark());
    }

    public function overlay_watermark()
    {
        if( !function_exists("imagecolortransparent") ) 
        {
            $this->set_error("imglib_gd_required");
            return false;
        }

        $this->get_image_properties();
        $props = $this->get_image_properties($this->wm_overlay_path, true);
        $wm_img_type = $props["image_type"];
        $wm_width = $props["width"];
        $wm_height = $props["height"];
        $wm_img = $this->image_create_gd($this->wm_overlay_path, $wm_img_type);
        $src_img = $this->image_create_gd($this->full_src_path);
        $this->wm_vrt_alignment = strtoupper($this->wm_vrt_alignment[0]);
        $this->wm_hor_alignment = strtoupper($this->wm_hor_alignment[0]);
        if( $this->wm_vrt_alignment === "B" ) 
        {
            $this->wm_vrt_offset = $this->wm_vrt_offset * -1;
        }

        if( $this->wm_hor_alignment === "R" ) 
        {
            $this->wm_hor_offset = $this->wm_hor_offset * -1;
        }

        $x_axis = $this->wm_hor_offset + $this->wm_padding;
        $y_axis = $this->wm_vrt_offset + $this->wm_padding;
        if( $this->wm_vrt_alignment === "M" ) 
        {
            $y_axis += $this->orig_height / 2 - $wm_height / 2;
        }
        else
        {
            if( $this->wm_vrt_alignment === "B" ) 
            {
                $y_axis += $this->orig_height - $wm_height;
            }

        }

        if( $this->wm_hor_alignment === "C" ) 
        {
            $x_axis += $this->orig_width / 2 - $wm_width / 2;
        }
        else
        {
            if( $this->wm_hor_alignment === "R" ) 
            {
                $x_axis += $this->orig_width - $wm_width;
            }

        }

        if( $wm_img_type === 3 && function_exists("imagealphablending") ) 
        {
            @imagealphablending($src_img, true);
        }

        $rgba = imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp);
        $alpha = ($rgba & 2130706432) >> 24;
        if( 0 < $alpha ) 
        {
            imagecopy($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height);
        }
        else
        {
            imagecolortransparent($wm_img, imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp));
            imagecopymerge($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height, $this->wm_opacity);
        }

        if( $this->image_type === 3 ) 
        {
            imagealphablending($src_img, false);
            imagesavealpha($src_img, true);
        }

        if( $this->dynamic_output === true ) 
        {
            $this->image_display_gd($src_img);
        }
        else
        {
            if( !$this->image_save_gd($src_img) ) 
            {
                return false;
            }

        }

        imagedestroy($src_img);
        imagedestroy($wm_img);
        return true;
    }

    public function text_watermark()
    {
        if( !($src_img = $this->image_create_gd()) ) 
        {
            return false;
        }

        if( $this->wm_use_truetype === true && !file_exists($this->wm_font_path) ) 
        {
            $this->set_error("imglib_missing_font");
            return false;
        }

        $this->get_image_properties();
        if( $this->wm_vrt_alignment === "B" ) 
        {
            $this->wm_vrt_offset = $this->wm_vrt_offset * -1;
        }

        if( $this->wm_hor_alignment === "R" ) 
        {
            $this->wm_hor_offset = $this->wm_hor_offset * -1;
        }

        if( $this->wm_use_truetype === true ) 
        {
            if( empty($this->wm_font_size) ) 
            {
                $this->wm_font_size = 17;
            }

            if( function_exists("imagettfbbox") ) 
            {
                $temp = imagettfbbox($this->wm_font_size, 0, $this->wm_font_path, $this->wm_text);
                $temp = $temp[2] - $temp[0];
                $fontwidth = $temp / strlen($this->wm_text);
            }
            else
            {
                $fontwidth = $this->wm_font_size - $this->wm_font_size / 4;
            }

            $fontheight = $this->wm_font_size;
            $this->wm_vrt_offset += $this->wm_font_size;
        }
        else
        {
            $fontwidth = imagefontwidth($this->wm_font_size);
            $fontheight = imagefontheight($this->wm_font_size);
        }

        $x_axis = $this->wm_hor_offset + $this->wm_padding;
        $y_axis = $this->wm_vrt_offset + $this->wm_padding;
        if( $this->wm_use_drop_shadow === false ) 
        {
            $this->wm_shadow_distance = 0;
        }

        $this->wm_vrt_alignment = strtoupper($this->wm_vrt_alignment[0]);
        $this->wm_hor_alignment = strtoupper($this->wm_hor_alignment[0]);
        if( $this->wm_vrt_alignment === "M" ) 
        {
            $y_axis += $this->orig_height / 2 + $fontheight / 2;
        }
        else
        {
            if( $this->wm_vrt_alignment === "B" ) 
            {
                $y_axis += $this->orig_height - $fontheight - $this->wm_shadow_distance - $fontheight / 2;
            }

        }

        if( $this->wm_hor_alignment === "R" ) 
        {
            $x_axis += $this->orig_width - $fontwidth * strlen($this->wm_text) - $this->wm_shadow_distance;
        }
        else
        {
            if( $this->wm_hor_alignment === "C" ) 
            {
                $x_axis += floor(($this->orig_width - $fontwidth * strlen($this->wm_text)) / 2);
            }

        }

        if( $this->wm_use_drop_shadow ) 
        {
            $x_shad = $x_axis + $this->wm_shadow_distance;
            $y_shad = $y_axis + $this->wm_shadow_distance;
            $drp_color = str_split(substr($this->wm_shadow_color, 1, 6), 2);
            $drp_color = imagecolorclosest($src_img, hexdec($drp_color[0]), hexdec($drp_color[1]), hexdec($drp_color[2]));
            if( $this->wm_use_truetype ) 
            {
                imagettftext($src_img, $this->wm_font_size, 0, $x_shad, $y_shad, $drp_color, $this->wm_font_path, $this->wm_text);
            }
            else
            {
                imagestring($src_img, $this->wm_font_size, $x_shad, $y_shad, $this->wm_text, $drp_color);
            }

        }

        $txt_color = str_split(substr($this->wm_font_color, 1, 6), 2);
        $txt_color = imagecolorclosest($src_img, hexdec($txt_color[0]), hexdec($txt_color[1]), hexdec($txt_color[2]));
        if( $this->wm_use_truetype ) 
        {
            imagettftext($src_img, $this->wm_font_size, 0, $x_axis, $y_axis, $txt_color, $this->wm_font_path, $this->wm_text);
        }
        else
        {
            imagestring($src_img, $this->wm_font_size, $x_axis, $y_axis, $this->wm_text, $txt_color);
        }

        if( $this->image_type === 3 ) 
        {
            imagealphablending($src_img, false);
            imagesavealpha($src_img, true);
        }

        if( $this->dynamic_output === true ) 
        {
            $this->image_display_gd($src_img);
        }
        else
        {
            $this->image_save_gd($src_img);
        }

        imagedestroy($src_img);
        return true;
    }

    public function image_create_gd($path = "", $image_type = "")
    {
        if( $path === "" ) 
        {
            $path = $this->full_src_path;
        }

        if( $image_type === "" ) 
        {
            $image_type = $this->image_type;
        }

        switch( $image_type ) 
        {
            case 1:
                if( !function_exists("imagecreatefromgif") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_gif_not_supported" ));
                    return false;
                }

                return imagecreatefromgif($path);
            case 2:
                if( !function_exists("imagecreatefromjpeg") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_jpg_not_supported" ));
                    return false;
                }

                return imagecreatefromjpeg($path);
            case 3:
                if( !function_exists("imagecreatefrompng") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_png_not_supported" ));
                    return false;
                }

                return imagecreatefrompng($path);
            default:
                $this->set_error(array( "imglib_unsupported_imagecreate" ));
        }
        return false;
    }

    public function image_save_gd($resource)
    {
        switch( $this->image_type ) 
        {
            case 1:
                if( !function_exists("imagegif") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_gif_not_supported" ));
                    return false;
                }

                if( !@imagegif($resource, $this->full_dst_path) ) 
                {
                    $this->set_error("imglib_save_failed");
                    return false;
                }

                break;
            case 2:
                if( !function_exists("imagejpeg") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_jpg_not_supported" ));
                    return false;
                }

                if( !@imagejpeg($resource, $this->full_dst_path, $this->quality) ) 
                {
                    $this->set_error("imglib_save_failed");
                    return false;
                }

                break;
            case 3:
                if( !function_exists("imagepng") ) 
                {
                    $this->set_error(array( "imglib_unsupported_imagecreate", "imglib_png_not_supported" ));
                    return false;
                }

                if( !@imagepng($resource, $this->full_dst_path) ) 
                {
                    $this->set_error("imglib_save_failed");
                    return false;
                }

                break;
            default:
                $this->set_error(array( "imglib_unsupported_imagecreate" ));
                return false;
        }
        return true;
    }

    public function image_display_gd($resource)
    {
        header("Content-Disposition: filename=" . $this->source_image . ";");
        header("Content-Type: " . $this->mime_type);
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
        switch( $this->image_type ) 
        {
            case 1:
                imagegif($resource);
                break;
            case 2:
                imagejpeg($resource, NULL, $this->quality);
                break;
            case 3:
                imagepng($resource);
                break;
            default:
                echo "Unable to display the image";
                break;
        }
    }

    public function image_reproportion()
    {
        if( $this->width === 0 && $this->height === 0 || $this->orig_width === 0 || $this->orig_height === 0 || !ctype_digit((string) $this->width) && !ctype_digit((string) $this->height) || !ctype_digit((string) $this->orig_width) || !ctype_digit((string) $this->orig_height) ) 
        {
            return NULL;
        }

        $this->width = (int) $this->width;
        $this->height = (int) $this->height;
        if( $this->master_dim !== "width" && $this->master_dim !== "height" ) 
        {
            if( 0 < $this->width && 0 < $this->height ) 
            {
                $this->master_dim = ($this->orig_height / $this->orig_width - $this->height / $this->width < 0 ? "width" : "height");
            }
            else
            {
                $this->master_dim = ($this->height === 0 ? "width" : "height");
            }

        }
        else
        {
            if( $this->master_dim === "width" && $this->width === 0 || $this->master_dim === "height" && $this->height === 0 ) 
            {
                return NULL;
            }

        }

        if( $this->master_dim === "width" ) 
        {
            $this->height = (int) ceil(($this->width * $this->orig_height) / $this->orig_width);
        }
        else
        {
            $this->width = (int) ceil(($this->orig_width * $this->height) / $this->orig_height);
        }

    }

    public function get_image_properties($path = "", $return = false)
    {
        if( $path === "" ) 
        {
            $path = $this->full_src_path;
        }

        if( !file_exists($path) ) 
        {
            $this->set_error("imglib_invalid_path");
            return false;
        }

        $vals = getimagesize($path);
        if( $vals === false ) 
        {
            $this->set_error("imglib_invalid_image");
            return false;
        }

        $types = array( 1 => "gif", 2 => "jpeg", 3 => "png" );
        $mime = (isset($types[$vals[2]]) ? "image/" . $types[$vals[2]] : "image/jpg");
        if( $return === true ) 
        {
            return array( "width" => $vals[0], "height" => $vals[1], "image_type" => $vals[2], "size_str" => $vals[3], "mime_type" => $mime );
        }

        list($this->orig_width, $this->orig_height, $this->image_type, $this->size_str) = $vals;
        $this->mime_type = $mime;
        return true;
    }

    public function size_calculator($vals)
    {
        if( !is_array($vals) ) 
        {
            return NULL;
        }

        $allowed = array( "new_width", "new_height", "width", "height" );
        foreach( $allowed as $item ) 
        {
            if( empty($vals[$item]) ) 
            {
                $vals[$item] = 0;
            }

        }
        if( $vals["width"] === 0 || $vals["height"] === 0 ) 
        {
            return $vals;
        }

        if( $vals["new_width"] === 0 ) 
        {
            $vals["new_width"] = ceil(($vals["width"] * $vals["new_height"]) / $vals["height"]);
        }
        else
        {
            if( $vals["new_height"] === 0 ) 
            {
                $vals["new_height"] = ceil(($vals["new_width"] * $vals["height"]) / $vals["width"]);
            }

        }

        return $vals;
    }

    public function explode_name($source_image)
    {
        $ext = strrchr($source_image, ".");
        $name = ($ext === false ? $source_image : substr($source_image, 0, 0 - strlen($ext)));
        return array( "ext" => $ext, "name" => $name );
    }

    public function gd_loaded()
    {
        if( !extension_loaded("gd") ) 
        {
            return function_exists("dl") && @dl("gd.so");
        }

        return true;
    }

    public function gd_version()
    {
        if( function_exists("gd_info") ) 
        {
            $gd_version = @gd_info();
            return preg_replace("/\\D/", "", $gd_version["GD Version"]);
        }

        return false;
    }

    public function set_error($msg)
    {
        $CI =& get_instance();
        $CI->lang->load("imglib");
        if( is_array($msg) ) 
        {
            foreach( $msg as $val ) 
            {
                $msg = ($CI->lang->line($val) === false ? $val : $CI->lang->line($val));
                $this->error_msg[] = $msg;
                log_message("error", $msg);
            }
        }
        else
        {
            $msg = ($CI->lang->line($msg) === false ? $msg : $CI->lang->line($msg));
            $this->error_msg[] = $msg;
            log_message("error", $msg);
        }

    }

    public function display_errors($open = "<p>", $close = "</p>")
    {
        return (0 < count($this->error_msg) ? $open . implode($close . $open, $this->error_msg) . $close : "");
    }

}


