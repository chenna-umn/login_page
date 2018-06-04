<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_cubrid_driver extends CI_DB_pdo_driver
{
    public $subdriver = "cubrid";
    protected $_escape_char = "`";
    protected $_random_keyword = array( "RANDOM()", "RANDOM(%d)" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "cubrid:host=" . ((empty($this->hostname) ? "127.0.0.1" : $this->hostname));
            empty($this->port) or empty($this->database) or empty($this->char_set) or         }

    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SHOW TABLES";
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
        {
            return $sql . " LIKE '" . $this->escape_like_str($this->dbprefix) . "%'";
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SHOW COLUMNS FROM " . $this->protect_identifiers($table, true, NULL, false);
    }

    public function field_data($table)
    {
        if( ($query = $this->query("SHOW COLUMNS FROM " . $this->protect_identifiers($table, true, NULL, false))) === false ) 
        {
            return false;
        }

        $query = $query->result_object();
        $retval = array(  );
        $i = 0;
        for( $c = count($query); $i < $c; $i++ ) 
        {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->Field;
            sscanf($query[$i]->Type, "%[a-z](%d)", $retval[$i]->type, $retval[$i]->max_length);
            $retval[$i]->default = $query[$i]->Default;
            $retval[$i]->primary_key = (int) ($query[$i]->Key === "PRI");
        }
        return $retval;
    }

    protected function _truncate($table)
    {
        return "TRUNCATE " . $table;
    }

    protected function _from_tables()
    {
        if( !empty($this->qb_join) && 1 < count($this->qb_from) ) 
        {
            return "(" . implode(", ", $this->qb_from) . ")";
        }

        return implode(", ", $this->qb_from);
    }

}


