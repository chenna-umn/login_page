<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Cache_file extends CI_Driver
{
    protected $_cache_path = NULL;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->helper("file");
        $path = $CI->config->item("cache_path");
        $this->_cache_path = ($path === "" ? APPPATH . "cache/" : $path);
    }

    public function get($id)
    {
        $data = $this->_get($id);
        return (is_array($data) ? $data["data"] : false);
    }

    public function save($id, $data, $ttl = 60, $raw = false)
    {
        $contents = array( "time" => time(), "ttl" => $ttl, "data" => $data );
        if( write_file($this->_cache_path . $id, serialize($contents)) ) 
        {
            chmod($this->_cache_path . $id, 416);
            return true;
        }

        return false;
    }

    public function delete($id)
    {
        return (is_file($this->_cache_path . $id) ? unlink($this->_cache_path . $id) : false);
    }

    public function increment($id, $offset = 1)
    {
        $data = $this->_get($id);
        if( $data === false ) 
        {
            $data = array( "data" => 0, "ttl" => 60 );
        }
        else
        {
            if( !is_int($data["data"]) ) 
            {
                return false;
            }

        }

        $new_value = $data["data"] + $offset;
        return ($this->save($id, $new_value, $data["ttl"]) ? $new_value : false);
    }

    public function decrement($id, $offset = 1)
    {
        $data = $this->_get($id);
        if( $data === false ) 
        {
            $data = array( "data" => 0, "ttl" => 60 );
        }
        else
        {
            if( !is_int($data["data"]) ) 
            {
                return false;
            }

        }

        $new_value = $data["data"] - $offset;
        return ($this->save($id, $new_value, $data["ttl"]) ? $new_value : false);
    }

    public function clean()
    {
        return delete_files($this->_cache_path, false, true);
    }

    public function cache_info($type = NULL)
    {
        return get_dir_file_info($this->_cache_path);
    }

    public function get_metadata($id)
    {
        if( !is_file($this->_cache_path . $id) ) 
        {
            return false;
        }

        $data = unserialize(file_get_contents($this->_cache_path . $id));
        if( is_array($data) ) 
        {
            $mtime = filemtime($this->_cache_path . $id);
            if( !(isset($data["ttl"]) && isset($data["time"])) ) 
            {
                return false;
            }

            return array( "expire" => $data["time"] + $data["ttl"], "mtime" => $mtime );
        }

        return false;
    }

    public function is_supported()
    {
        return is_really_writable($this->_cache_path);
    }

    protected function _get($id)
    {
        if( !is_file($this->_cache_path . $id) ) 
        {
            return false;
        }

        $data = unserialize(file_get_contents($this->_cache_path . $id));
        if( 0 < $data["ttl"] && $data["time"] + $data["ttl"] < time() ) 
        {
            unlink($this->_cache_path . $id);
            return false;
        }

        return $data;
    }

}


