<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_sqlite_forge extends CI_DB_forge
{
    protected $_create_table_if = false;
    protected $_unsigned = false;
    protected $_null = "NULL";

    public function create_database($db_name)
    {
        return true;
    }

    public function drop_database($db_name)
    {
        if( !file_exists($this->db->database) || !@unlink($this->db->database) ) 
        {
            return ($this->db->db_debug ? $this->db->display_error("db_unable_to_drop") : false);
        }

        if( !empty($this->db->data_cache["db_names"]) ) 
        {
            $key = array_search(strtolower($this->db->database), array_map("strtolower", $this->db->data_cache["db_names"]), true);
            if( $key !== false ) 
            {
                unset($this->db->data_cache["db_names"][$key]);
            }

        }

        return true;
    }

    protected function _alter_table($alter_type, $table, $field)
    {
        if( $alter_type === "DROP" || $alter_type === "CHANGE" ) 
        {
            return false;
        }

        return parent::_alter_table($alter_type, $table, $field);
    }

    protected function _process_column($field)
    {
        return $this->db->escape_identifiers($field["name"]) . " " . $field["type"] . $field["auto_increment"] . $field["null"] . $field["unique"] . $field["default"];
    }

    protected function _attr_type(&$attributes)
    {
        switch( strtoupper($attributes["TYPE"]) ) 
        {
            case "ENUM":
            case "SET":
                $attributes["TYPE"] = "TEXT";
                return NULL;
        }
    }

    protected function _attr_auto_increment(&$attributes, &$field)
    {
        if( !empty($attributes["AUTO_INCREMENT"]) && $attributes["AUTO_INCREMENT"] === true && stripos($field["type"], "int") !== false ) 
        {
            $field["type"] = "INTEGER PRIMARY KEY";
            $field["default"] = "";
            $field["null"] = "";
            $field["unique"] = "";
            $field["auto_increment"] = " AUTOINCREMENT";
            $this->primary_keys = array(  );
        }

    }

}


