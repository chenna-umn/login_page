<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_pgsql_forge extends CI_DB_pdo_forge
{
    protected $_drop_table_if = "DROP TABLE IF EXISTS";
    protected $_unsigned = array( "INT2" => "INTEGER", "SMALLINT" => "INTEGER", "INT" => "BIGINT", "INT4" => "BIGINT", "INTEGER" => "BIGINT", "INT8" => "NUMERIC", "BIGINT" => "NUMERIC", "REAL" => "DOUBLE PRECISION", "FLOAT" => "DOUBLE PRECISION" );
    protected $_null = "NULL";

    public function __construct(&$db)
    {
        parent::__construct($db);
        if( version_compare($this->db->version(), "9.0", ">") ) 
        {
            $this->create_table_if = "CREATE TABLE IF NOT EXISTS";
        }

    }

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
                return false;
            }

            if( version_compare($this->db->version(), "8", ">=") && isset($field[$i]["type"]) ) 
            {
                $sqls[] = $sql . " ALTER COLUMN " . $this->db->escape_identifiers($field[$i]["name"]) . " TYPE " . $field[$i]["type"] . $field[$i]["length"];
            }

            if( !empty($field[$i]["default"]) ) 
            {
                $sqls[] = $sql . " ALTER COLUMN " . $this->db->escape_identifiers($field[$i]["name"]) . " SET DEFAULT " . $field[$i]["default"];
            }

            if( isset($field[$i]["null"]) ) 
            {
                $sqls[] = $sql . " ALTER COLUMN " . $this->db->escape_identifiers($field[$i]["name"]) . (($field[$i]["null"] === true ? " DROP NOT NULL" : " SET NOT NULL"));
            }

            if( !empty($field[$i]["new_name"]) ) 
            {
                $sqls[] = $sql . " RENAME COLUMN " . $this->db->escape_identifiers($field[$i]["name"]) . " TO " . $this->db->escape_identifiers($field[$i]["new_name"]);
            }

            if( !empty($field[$i]["comment"]) ) 
            {
                $sqls[] = "COMMENT ON COLUMN " . $this->db->escape_identifiers($table) . "." . $this->db->escape_identifiers($field[$i]["name"]) . " IS " . $field[$i]["comment"];
            }

        }
        return $sqls;
    }

    protected function _attr_type(&$attributes)
    {
        if( isset($attributes["CONSTRAINT"]) && stripos($attributes["TYPE"], "int") !== false ) 
        {
            $attributes["CONSTRAINT"] = NULL;
        }

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
        }
    }

    protected function _attr_auto_increment(&$attributes, &$field)
    {
        if( !empty($attributes["AUTO_INCREMENT"]) && $attributes["AUTO_INCREMENT"] === true ) 
        {
            $field["type"] = ($field["type"] === "NUMERIC" ? "BIGSERIAL" : "SERIAL");
        }

    }

}


