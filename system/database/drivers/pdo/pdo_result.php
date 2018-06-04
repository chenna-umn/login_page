<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_result extends CI_DB_result
{
    public function num_rows()
    {
        if( is_int($this->num_rows) ) 
        {
            return $this->num_rows;
        }

        if( 0 < count($this->result_array) ) 
        {
            return $this->num_rows = count($this->result_array);
        }

        if( 0 < count($this->result_object) ) 
        {
            return $this->num_rows = count($this->result_object);
        }

        if( 0 < ($num_rows = $this->result_id->rowCount()) ) 
        {
            return $this->num_rows = $num_rows;
        }

        return $this->num_rows = count($this->result_array());
    }

    public function num_fields()
    {
        return $this->result_id->columnCount();
    }

    public function list_fields()
    {
        $field_names = array(  );
        $i = 0;
        for( $c = $this->num_fields(); $i < $c; $i++ ) 
        {
            $field_names[$i] = @$this->result_id->getColumnMeta($i);
            $field_names[$i] = $field_names[$i]["name"];
        }
        return $field_names;
    }

    public function field_data()
    {
        try
        {
            $retval = array(  );
            $i = 0;
            for( $c = $this->num_fields(); $i < $c; $i++ ) 
            {
                $field = $this->result_id->getColumnMeta($i);
                $retval[$i] = new stdClass();
                $retval[$i]->name = $field["name"];
                $retval[$i]->type = $field["native_type"];
                $retval[$i]->max_length = (0 < $field["len"] ? $field["len"] : NULL);
                $retval[$i]->primary_key = (int) (!empty($field["flags"]) && in_array("primary_key", $field["flags"], true));
            }
            return $retval;
        }
        catch( Exception $e ) 
        {
            if( $this->db->db_debug ) 
            {
                return $this->db->display_error("db_unsupported_feature");
            }

            return false;
        }
    }

    public function free_result()
    {
        if( is_object($this->result_id) ) 
        {
            $this->result_id = false;
        }

    }

    protected function _fetch_assoc()
    {
        return $this->result_id->fetch(PDO::FETCH_ASSOC);
    }

    protected function _fetch_object($class_name = "stdClass")
    {
        return $this->result_id->fetchObject($class_name);
    }

}


