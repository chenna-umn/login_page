<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Cache_dummy extends CI_Driver
{
    public function get($id)
    {
        return false;
    }

    public function save($id, $data, $ttl = 60, $raw = false)
    {
        return true;
    }

    public function delete($id)
    {
        return true;
    }

    public function increment($id, $offset = 1)
    {
        return true;
    }

    public function decrement($id, $offset = 1)
    {
        return true;
    }

    public function clean()
    {
        return true;
    }

    public function cache_info($type = NULL)
    {
        return false;
    }

    public function get_metadata($id)
    {
        return false;
    }

    public function is_supported()
    {
        return true;
    }

}


