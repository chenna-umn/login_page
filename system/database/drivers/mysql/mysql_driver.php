<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_mysql_driver extends CI_DB
{
    public $dbdriver = "mysql";
    public $compress = false;
    public $delete_hack = true;
    public $stricton = NULL;
    protected $_escape_char = "`";

    public function __construct($params)
    {
        parent::__construct($params);
        if( !empty($this->port) ) 
        {
            $this->hostname .= ":" . $this->port;
        }

    }

    public function db_connect($persistent = false)
    {
        $client_flags = ($this->compress === false ? 0 : MYSQL_CLIENT_COMPRESS);
        if( $this->encrypt === true ) 
        {
            $client_flags = $client_flags | MYSQL_CLIENT_SSL;
        }

        $this->conn_id = ($persistent === true ? mysql_pconnect($this->hostname, $this->username, $this->password, $client_flags) : mysql_connect($this->hostname, $this->username, $this->password, true, $client_flags));
        if( $this->database !== "" && !$this->db_select() ) 
        {
            log_message("error", "Unable to select database: " . $this->database);
            return ($this->db_debug === true ? $this->display_error("db_unable_to_select", $this->database) : false);
        }

        if( isset($this->stricton) && is_resource($this->conn_id) ) 
        {
            if( $this->stricton ) 
            {
                $this->simple_query("SET SESSION sql_mode = CONCAT(@@sql_mode, \",\", \"STRICT_ALL_TABLES\")");
            }
            else
            {
                $this->simple_query("SET SESSION sql_mode =\n\t\t\t\t\tREPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(\n\t\t\t\t\t@@sql_mode,\n\t\t\t\t\t\"STRICT_ALL_TABLES,\", \"\"),\n\t\t\t\t\t\",STRICT_ALL_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_ALL_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_TRANS_TABLES,\", \"\"),\n\t\t\t\t\t\",STRICT_TRANS_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_TRANS_TABLES\", \"\")");
            }

        }

        return $this->conn_id;
    }

    public function reconnect()
    {
        if( mysql_ping($this->conn_id) === false ) 
        {
            $this->conn_id = false;
        }

    }

    public function db_select($database = "")
    {
        if( $database === "" ) 
        {
            $database = $this->database;
        }

        if( mysql_select_db($database, $this->conn_id) ) 
        {
            $this->database = $database;
            $this->data_cache = array(  );
            return true;
        }

        return false;
    }

    protected function _db_set_charset($charset)
    {
        return mysql_set_charset($charset, $this->conn_id);
    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        if( !$this->conn_id || ($version = mysql_get_server_info($this->conn_id)) === false ) 
        {
            return false;
        }

        $this->data_cache["version"] = $version;
        return $this->data_cache["version"];
    }

    protected function _execute($sql)
    {
        return mysql_query($this->_prep_query($sql), $this->conn_id);
    }

    protected function _prep_query($sql)
    {
        if( $this->delete_hack === true && preg_match("/^\\s*DELETE\\s+FROM\\s+(\\S+)\\s*\$/i", $sql) ) 
        {
            return trim($sql) . " WHERE 1=1";
        }

        return $sql;
    }

    protected function _trans_begin()
    {
        $this->simple_query("SET AUTOCOMMIT=0");
        return $this->simple_query("START TRANSACTION");
    }

    protected function _trans_commit()
    {
        if( $this->simple_query("COMMIT") ) 
        {
            $this->simple_query("SET AUTOCOMMIT=1");
            return true;
        }

        return false;
    }

    protected function _trans_rollback()
    {
        if( $this->simple_query("ROLLBACK") ) 
        {
            $this->simple_query("SET AUTOCOMMIT=1");
            return true;
        }

        return false;
    }

    protected function _escape_str($str)
    {
        return mysql_real_escape_string($str, $this->conn_id);
    }

    public function affected_rows()
    {
        return mysql_affected_rows($this->conn_id);
    }

    public function insert_id()
    {
        return mysql_insert_id($this->conn_id);
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SHOW TABLES FROM " . $this->escape_identifiers($this->database);
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
        return array( "code" => mysql_errno($this->conn_id), "message" => mysql_error($this->conn_id) );
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
        @mysql_close($this->conn_id);
    }

}


