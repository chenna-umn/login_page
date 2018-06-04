<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_cubrid_forge extends CI_DB_pdo_forge
{
    protected $_create_database = false;
    protected $_drop_database = false;
    protected $_create_table_keys = true;
    protected $_drop_table_if = "DROP TABLE IF EXISTS";
    protected $_unsigned = array( "SHORT" => "INTEGER", "SMALLINT" => "INTEGER", "INT" => "BIGINT", "INTEGER" => "BIGINT", "BIGINT" => "NUMERIC", "FLOAT" => "DOUBLE", "REAL" => "DOUBLE" );

    protected function _alter_table($alter_type, $table, $field)
    {
        if( in_array($alter_type, array( "DROP", "ADD" ), true) ) 
        {
            return parent::_alter_table($alter_type, $table, $field);
        }

        $sql = "ALTER TABLE " . $this->db->escape_identifiers($table);
        $sqls = array(  );
        $i = 0;
        for( $c = count($field); $i < $c; $i++ ) 
        {
            if( $field[$i]["_literal"] !== false ) 
            {
                $sqls[] = $sql . " CHANGE " . $field[$i]["_literal"];
            }
            else
            {
                $alter_type = (empty($field[$i]["new_name"]) ? " MODIFY " : " CHANGE ");
                $sqls[] = $sql . $alter_type . $this->_process_column($field[$i]);
            }

        }
        return $sqls;
    }

    protected function _process_column($field)
    {
        $extra_clause = (isset($field["after"]) ? " AFTER " . $this->db->escape_identifiers($field["after"]) : "");
        if( empty($extra_clause) && isset($field["first"]) && $field["first"] === true ) 
        {
            $extra_clause = " FIRST";
        }

        return $this->db->escape_identifiers($field["name"]) . ((empty($field["new_name"]) ? "" : " " . $this->db->escape_identifiers($field["new_name"]))) . " " . $field["type"] . $field["length"] . $field["unsigned"] . $field["null"] . $field["default"] . $field["auto_increment"] . $field["unique"] . $extra_clause;
    }

    protected function _attr_type(&$attributes)
    {
        switch( strtoupper($attributes["TYPE"]) ) 
        {
            case "TINYINT":
                $attributes["TYPE"] = "SMALLINT";
                $attributes["UNSIGNED"] = false;
                return NULL;
            case "MEDIUMINT":
                $attributes["TYPE"] = "INTEGER";
                $attributes["UNSIGNED"] = false;
                return NULL;
            case "LONGTEXT":
                $attributes["TYPE"] = "STRING";
                return NULL;
        }
    }

    protected function _process_indexes($table)
    {
        $sql = "";
        $i = 0;
        for( $c = count($this->keys); $i < $c; $i++ ) 
        {
            if( is_array($this->keys[$i]) ) 
            {
                $i2 = 0;
                for( $c2 = count($this->keys[$i]); $i2 < $c2; $i2++ ) 
                {
                    if( !isset($this->fields[$this->keys[$i][$i2]]) ) 
                    {
                        unset($this->keys[$i][$i2]);
                        continue;
                    }

                }
            }
            else
            {
                if( !isset($this->fields[$this->keys[$i]]) ) 
                {
                    unset($this->keys[$i]);
                    continue;
                }

            }

            is_array($this->keys[$i]) or $this->keys[$i] = array( $this->keys[$i] );
            $sql .= ",\n\tKEY " . $this->db->escape_identifiers(implode("_", $this->keys[$i])) . " (" . implode(", ", $this->db->escape_identifiers($this->keys[$i])) . ")";
        }
        $this->keys = array(  );
        return $sql;
    }

}


