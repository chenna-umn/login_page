<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_utility extends CI_DB_utility
{
    protected function _backup($params = array(  ))
    {
        return $this->db->display_error("db_unsupported_feature");
    }

}


