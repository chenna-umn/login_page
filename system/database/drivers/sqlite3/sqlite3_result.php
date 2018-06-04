<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_sqlite3_result extends CI_DB_result
{
    public function num_fields()
    {
        return $this->result_id->numColumns();
    }

    public function list_fields()
    {
        $field_names = array(  );
        $i = 0;
        for( $c = $this->num_fields(); $i < $c; $i++ ) 
        {
            $field_names[] = $this->result_id->columnName($i);
        }
        return $field_names;
    }

    public function field_data()
    {
        static $data_types = NULL;
        $retval = array(  );
        $i = 0;
        for( $c = $this->num_fields(); $i < $c; $i++ ) 
        {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $this->result_id->columnName($i);
            $type = $this->result_id->columnType($i);
            $retval[$i]->type = (isset($data_types[$type]) ? $data_types[$type] : $type);
            $retval[$i]->max_length = NULL;
        }
        return $retval;
    }

    public function free_result()
    {
        if( is_object($this->result_id) ) 
        {
            $this->result_id->finalize();
            $this->result_id = NULL;
        }

    }

    protected function _fetch_assoc()
    {
        return $this->result_id->fetchArray(SQLITE3_ASSOC);
    }

    protected function _fetch_object($class_name = "stdClass")
    {
        if( ($row = $this->result_id->fetchArray(SQLITE3_ASSOC)) === false ) 
        {
            return false;
        }

        if( $class_name === "stdClass" ) 
        {
            return (object) $row;
        }

        $class_name = new $class_name();
        foreach( array_keys($row) as $key ) 
        {
            $class_name->$key = $row[$key];
        }
        return $class_name;
    }

    public function data_seek($n = 0)
    {
        return (0 < $n ? false : $this->result_id->reset());
    }

}


