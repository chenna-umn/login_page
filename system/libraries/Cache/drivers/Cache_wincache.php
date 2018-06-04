<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Cache_wincache extends CI_Driver
{
    public function __construct()
    {
        if( !$this->is_supported() ) 
        {
            log_message("error", "Cache: Failed to initialize Wincache; extension not loaded/enabled?");
        }

    }

    public function get($id)
    {
        $success = false;
        $data = wincache_ucache_get($id, $success);
        return ($success ? $data : false);
    }

    public function save($id, $data, $ttl = 60, $raw = false)
    {
        return wincache_ucache_set($id, $data, $ttl);
    }

    public function delete($id)
    {
        return wincache_ucache_delete($id);
    }

    public function increment($id, $offset = 1)
    {
        $success = false;
        $value = wincache_ucache_inc($id, $offset, $success);
        return ($success === true ? $value : false);
    }

    public function decrement($id, $offset = 1)
    {
        $success = false;
        $value = wincache_ucache_dec($id, $offset, $success);
        return ($success === true ? $value : false);
    }

    public function clean()
    {
        return wincache_ucache_clear();
    }

    public function cache_info()
    {
        return wincache_ucache_info(true);
    }

    public function get_metadata($id)
    {
        if( $stored = wincache_ucache_info(false, $id) ) 
        {
            $age = $stored["ucache_entries"][1]["age_seconds"];
            $ttl = $stored["ucache_entries"][1]["ttl_seconds"];
            $hitcount = $stored["ucache_entries"][1]["hitcount"];
            return array( "expire" => $ttl - $age, "hitcount" => $hitcount, "age" => $age, "ttl" => $ttl );
        }

        return false;
    }

    public function is_supported()
    {
        return extension_loaded("wincache") && ini_get("wincache.ucenabled");
    }

}


