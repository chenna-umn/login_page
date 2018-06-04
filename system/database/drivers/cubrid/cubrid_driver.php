<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_cubrid_driver extends CI_DB
{
    public $dbdriver = "cubrid";
    public $auto_commit = true;
    protected $_escape_char = "`";
    protected $_random_keyword = array( "RANDOM()", "RANDOM(%d)" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( preg_match("/^CUBRID:[^:]+(:[0-9][1-9]{0,4})?:[^:]+:[^:]*:[^:]*:(\\?.+)?\$/", $this->dsn, $matches) ) 
        {
            if( stripos($matches[2], "autocommit=off") !== false ) 
            {
                $this->auto_commit = false;
            }

        }
        else
        {
            empty($this->port) or         }

    }

    public function db_connect($persistent = false)
    {
        if( preg_match("/^CUBRID:[^:]+(:[0-9][1-9]{0,4})?:[^:]+:([^:]*):([^:]*):(\\?.+)?\$/", $this->dsn, $matches) ) 
        {
            $func = ($persistent !== true ? "cubrid_connect_with_url" : "cubrid_pconnect_with_url");
            return ($matches[2] === "" && $matches[3] === "" && $this->username !== "" && $this->password !== "" ? $func($this->dsn, $this->username, $this->password) : $func($this->dsn));
        }

        $func = ($persistent !== true ? "cubrid_connect" : "cubrid_pconnect");
        return ($this->username !== "" ? $func($this->hostname, $this->port, $this->database, $this->username, $this->password) : $func($this->hostname, $this->port, $this->database));
    }

    public function reconnect()
    {
        if( cubrid_ping($this->conn_id) === false ) 
        {
            $this->conn_id = false;
        }

    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        return (!$this->conn_id || ($version = cubrid_get_server_info($this->conn_id)) === false ? false : $this->data_cache["version"]);
    }

    protected function _execute($sql)
    {
        return cubrid_query($sql, $this->conn_id);
    }

    protected function _trans_begin()
    {
        if( ($autocommit = cubrid_get_autocommit($this->conn_id)) === NULL ) 
        {
            return false;
        }

        if( $autocommit === true ) 
        {
            return cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_FALSE);
        }

        return true;
    }

    protected function _trans_commit()
    {
        if( !cubrid_commit($this->conn_id) ) 
        {
            return false;
        }

        if( $this->auto_commit && !cubrid_get_autocommit($this->conn_id) ) 
        {
            return cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_TRUE);
        }

        return true;
    }

    protected function _trans_rollback()
    {
        if( !cubrid_rollback($this->conn_id) ) 
        {
            return false;
        }

        if( $this->auto_commit && !cubrid_get_autocommit($this->conn_id) ) 
        {
            cubrid_set_autocommit($this->conn_id, CUBRID_AUTOCOMMIT_TRUE);
        }

        return true;
    }

    protected function _escape_str($str)
    {
        return cubrid_real_escape_string($str, $this->conn_id);
    }

    public function affected_rows()
    {
        return cubrid_affected_rows();
    }

    public function insert_id()
    {
        return cubrid_insert_id($this->conn_id);
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SHOW TABLES";
        if( $prefix_limit !== false && $this->dbprefix !== "" ) 
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

    public function error()
    {
        return array( "code" => cubrid_errno($this->conn_id), "message" => cubrid_error($this->conn_id) );
    }

    protected function _from_tables()
    {
        if( !empty($this->qb_join) && 1 < count($this->qb_from) ) 
        {
            return "(" . implode(", ", $this->qb_from) . ")";
        }

        return implode(", ", $this->qb_from);
    }

    protected function _close()
    {
        cubrid_close($this->conn_id);
    }

}


