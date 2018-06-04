<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_oci_forge extends CI_DB_pdo_forge
{
    protected $_create_database = false;
    protected $_create_table_if = false;
    protected $_drop_database = false;
    protected $_unsigned = false;

    protected function _alter_table($alter_type, $table, $field)
    {
        if( $alter_type === "DROP" ) 
        {
            return parent::_alter_table($alter_type, $table, $field);
        }

        if( $alter_type === "CHANGE" ) 
        {
            $alter_type = "MODIFY";
        }

        $sql = "ALTER TABLE " . $this->db->escape_identifiers($table);
        $sqls = array(  );
        $i = 0;
        for( $c = count($field); $i < $c; $i++ ) 
        {
            if( $field[$i]["_literal"] !== false ) 
            {
                $field[$i] = "\n\t" . $field[$i]["_literal"];
            }
            else
            {
                $field[$i]["_literal"] = "\n\t" . $this->_process_column($field[$i]);
                if( !empty($field[$i]["comment"]) ) 
                {
                    $sqls[] = "COMMENT ON COLUMN " . $this->db->escape_identifiers($table) . "." . $this->db->escape_identifiers($field[$i]["name"]) . " IS " . $field[$i]["comment"];
                }

                if( $alter_type === "MODIFY" && !empty($field[$i]["new_name"]) ) 
                {
                    $sqls[] = $sql . " RENAME COLUMN " . $this->db->escape_identifiers($field[$i]["name"]) . " TO " . $this->db->escape_identifiers($field[$i]["new_name"]);
                }

            }

        }
        $sql .= " " . $alter_type . " ";
        $sql .= (count($field) === 1 ? $field[0] : "(" . implode(",", $field) . ")");
        array_unshift($sqls, $sql);
        return $sql;
    }

    protected function _attr_auto_increment(&$attributes, &$field)
    {
    }

    protected function _attr_type(&$attributes)
    {
        switch( strtoupper($attributes["TYPE"]) ) 
        {
            case "TINYINT":
                $attributes["TYPE"] = "NUMBER";
                return NULL;
            case "MEDIUMINT":
                $attributes["TYPE"] = "NUMBER";
                return NULL;
            case "INT":
                $attributes["TYPE"] = "NUMBER";
                return NULL;
            case "BIGINT":
                $attributes["TYPE"] = "NUMBER";
                return NULL;
        }
    }

}


