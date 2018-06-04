<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_cubrid_utility extends CI_DB_utility
{
    public function list_databases()
    {
        if( isset($this->db->data_cache["db_names"]) ) 
        {
            return $this->db->data_cache["db_names"];
        }

        $this->db->data_cache["db_names"] = cubrid_list_dbs($this->db->conn_id);
        return $this->db->data_cache["db_names"];
    }

    protected function _backup($params = array(  ))
    {
        return $this->db->display_error("db_unsupported_feature");
    }

}


