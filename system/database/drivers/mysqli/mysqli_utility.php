<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_mysqli_utility extends CI_DB_utility
{
    protected $_list_databases = "SHOW DATABASES";
    protected $_optimize_table = "OPTIMIZE TABLE %s";
    protected $_repair_table = "REPAIR TABLE %s";

    protected function _backup($params = array(  ))
    {
        if( count($params) === 0 ) 
        {
            return false;
        }

        extract($params);
        $output = "";
        if( $foreign_key_checks === false ) 
        {
            $output .= "SET foreign_key_checks = 0;" . $newline;
        }

        foreach( (array) $tables as $table ) 
        {
            if( in_array($table, (array) $ignore, true) ) 
            {
                continue;
            }

            $query = $this->db->query("SHOW CREATE TABLE " . $this->db->escape_identifiers($this->db->database . "." . $table));
            if( $query === false ) 
            {
                continue;
            }

            $output .= "#" . $newline . "# TABLE STRUCTURE FOR: " . $table . $newline . "#" . $newline . $newline;
            if( $add_drop === true ) 
            {
                $output .= "DROP TABLE IF EXISTS " . $this->db->protect_identifiers($table) . ";" . $newline . $newline;
            }

            $i = 0;
            $result = $query->result_array();
            foreach( $result[0] as $val ) 
            {
                if( $i++ % 2 ) 
                {
                    $output .= $val . ";" . $newline . $newline;
                }

            }
            if( $add_insert === false ) 
            {
                continue;
            }

            $query = $this->db->query("SELECT * FROM " . $this->db->protect_identifiers($table));
            if( $query->num_rows() === 0 ) 
            {
                continue;
            }

            $i = 0;
            $field_str = "";
            for( $is_int = array(  ); $field = $query->result_id->fetch_field(); $i++ ) 
            {
                $is_int[$i] = in_array(strtolower($field->type), array( "tinyint", "smallint", "mediumint", "int", "bigint" ), true);
                $field_str .= $this->db->escape_identifiers($field->name) . ", ";
            }
            $field_str = preg_replace("/, \$/", "", $field_str);
            foreach( $query->result_array() as $row ) 
            {
                $val_str = "";
                $i = 0;
                foreach( $row as $v ) 
                {
                    if( $v === NULL ) 
                    {
                        $val_str .= "NULL";
                    }
                    else
                    {
                        $val_str .= ($is_int[$i] === false ? $this->db->escape($v) : $v);
                    }

                    $val_str .= ", ";
                    $i++;
                }
                $val_str = preg_replace("/, \$/", "", $val_str);
                $output .= "INSERT INTO " . $this->db->protect_identifiers($table) . " (" . $field_str . ") VALUES (" . $val_str . ");" . $newline;
            }
            $output .= $newline . $newline;
        }
        if( $foreign_key_checks === false ) 
        {
            $output .= "SET foreign_key_checks = 1;" . $newline;
        }

        return $output;
    }

}


