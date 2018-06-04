<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_informix_forge extends CI_DB_pdo_forge
{
    protected $_rename_table = "RENAME TABLE %s TO %s";
    protected $_unsigned = array( "SMALLINT" => "INTEGER", "INT" => "BIGINT", "INTEGER" => "BIGINT", "REAL" => "DOUBLE PRECISION", "SMALLFLOAT" => "DOUBLE PRECISION" );
    protected $_default = ", ";

    protected function _alter_table($alter_type, $table, $field)
    {
        if( $alter_type === "CHANGE" ) 
        {
            $alter_type = "MODIFY";
        }

        return parent::_alter_table($alter_type, $table, $field);
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
            case "BYTE":
            case "TEXT":
            case "BLOB":
            case "CLOB":
                $attributes["UNIQUE"] = false;
                if( isset($attributes["DEFAULT"]) ) 
                {
                    unset($attributes["DEFAULT"]);
                }

                return NULL;
        }
    }

    protected function _attr_unique(&$attributes, &$field)
    {
        if( !empty($attributes["UNIQUE"]) && $attributes["UNIQUE"] === true ) 
        {
            $field["unique"] = " UNIQUE CONSTRAINT " . $this->db->escape_identifiers($field["name"]);
        }

    }

    protected function _attr_auto_increment(&$attributes, &$field)
    {
    }

}


