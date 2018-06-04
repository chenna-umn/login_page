<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_Cache
{
    public $CI = NULL;
    public $db = NULL;

    public function __construct(&$db)
    {
        $this->CI =& get_instance();
        $this->db =& $db;
        $this->CI->load->helper("file");
        $this->check_path();
    }

    public function check_path($path = "")
    {
        if( $path === "" ) 
        {
            if( $this->db->cachedir === "" ) 
            {
                return $this->db->cache_off();
            }

            $path = $this->db->cachedir;
        }

        $path = (realpath($path) ? rtrim(realpath($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : rtrim($path, "/") . "/");
        if( !is_dir($path) ) 
        {
            log_message("debug", "DB cache path error: " . $path);
            return $this->db->cache_off();
        }

        if( !is_really_writable($path) ) 
        {
            log_message("debug", "DB cache dir not writable: " . $path);
            return $this->db->cache_off();
        }

        $this->db->cachedir = $path;
        return true;
    }

    public function read($sql)
    {
        $segment_one = ($this->CI->uri->segment(1) == false ? "default" : $this->CI->uri->segment(1));
        $segment_two = ($this->CI->uri->segment(2) == false ? "index" : $this->CI->uri->segment(2));
        $filepath = $this->db->cachedir . $segment_one . "+" . $segment_two . "/" . md5($sql);
        if( !is_file($filepath) || false === ($cachedata = file_get_contents($filepath)) ) 
        {
            return false;
        }

        return unserialize($cachedata);
    }

    public function write($sql, $object)
    {
        $segment_one = ($this->CI->uri->segment(1) == false ? "default" : $this->CI->uri->segment(1));
        $segment_two = ($this->CI->uri->segment(2) == false ? "index" : $this->CI->uri->segment(2));
        $dir_path = $this->db->cachedir . $segment_one . "+" . $segment_two . "/";
        $filename = md5($sql);
        if( !is_dir($dir_path) && !@mkdir($dir_path, 488) ) 
        {
            return false;
        }

        if( write_file($dir_path . $filename, serialize($object)) === false ) 
        {
            return false;
        }

        chmod($dir_path . $filename, 416);
        return true;
    }

    public function delete($segment_one = "", $segment_two = "")
    {
        if( $segment_one === "" ) 
        {
            $segment_one = ($this->CI->uri->segment(1) == false ? "default" : $this->CI->uri->segment(1));
        }

        if( $segment_two === "" ) 
        {
            $segment_two = ($this->CI->uri->segment(2) == false ? "index" : $this->CI->uri->segment(2));
        }

        $dir_path = $this->db->cachedir . $segment_one . "+" . $segment_two . "/";
        delete_files($dir_path, true);
    }

    public function delete_all()
    {
        delete_files($this->db->cachedir, true, true);
    }

}


