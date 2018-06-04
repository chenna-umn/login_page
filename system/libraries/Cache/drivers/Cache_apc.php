<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Cache_apc extends CI_Driver
{
    public function __construct()
    {
        if( !$this->is_supported() ) 
        {
            log_message("error", "Cache: Failed to initialize APC; extension not loaded/enabled?");
        }

    }

    public function get($id)
    {
        $success = false;
        $data = apc_fetch($id, $success);
        if( $success === true ) 
        {
            return (is_array($data) ? unserialize($data[0]) : $data);
        }

        return false;
    }

    public function save($id, $data, $ttl = 60, $raw = false)
    {
        $ttl = (int) $ttl;
        return apc_store($id, ($raw === true ? $data : array( serialize($data), time(), $ttl )), $ttl);
    }

    public function delete($id)
    {
        return apc_delete($id);
    }

    public function increment($id, $offset = 1)
    {
        return apc_inc($id, $offset);
    }

    public function decrement($id, $offset = 1)
    {
        return apc_dec($id, $offset);
    }

    public function clean()
    {
        return apc_clear_cache("user");
    }

    public function cache_info($type = NULL)
    {
        return apc_cache_info($type);
    }

    public function get_metadata($id)
    {
        $success = false;
        $stored = apc_fetch($id, $success);
        if( $success === false || count($stored) !== 3 ) 
        {
            return false;
        }

        list($data, $time, $ttl) = $stored;
        return array( "expire" => $time + $ttl, "mtime" => $time, "data" => unserialize($data) );
    }

    public function is_supported()
    {
        return extension_loaded("apc") && ini_get("apc.enabled");
    }

}


