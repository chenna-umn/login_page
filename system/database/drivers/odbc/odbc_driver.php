<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_odbc_driver extends CI_DB_driver
{
    public $dbdriver = "odbc";
    public $schema = "public";
    protected $_escape_char = "";
    protected $_like_escape_str = " {escape '%s'} ";
    protected $_random_keyword = array( "RND()", "RND(%d)" );
    private $odbc_result = NULL;
    private $binds = array(  );

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = $this->hostname;
        }

    }

    public function db_connect($persistent = false)
    {
        return ($persistent === true ? odbc_pconnect($this->dsn, $this->username, $this->password) : odbc_connect($this->dsn, $this->username, $this->password));
    }

    public function compile_binds($sql, $binds)
    {
        if( empty($binds) || empty($this->bind_marker) || strpos($sql, $this->bind_marker) === false ) 
        {
            return $sql;
        }

        if( !is_array($binds) ) 
        {
            $binds = array( $binds );
            $bind_count = 1;
        }
        else
        {
            $binds = array_values($binds);
            $bind_count = count($binds);
        }

        $ml = strlen($this->bind_marker);
        if( $c = preg_match_all("/'[^']*'|\"[^\"]*\"/i", $sql, $matches) ) 
        {
            $c = preg_match_all("/" . preg_quote($this->bind_marker, "/") . "/i", str_replace($matches[0], str_replace($this->bind_marker, str_repeat(" ", $ml), $matches[0]), $sql, $c), $matches, PREG_OFFSET_CAPTURE);
            if( $bind_count !== $c ) 
            {
                return $sql;
            }

        }
        else
        {
            if( ($c = preg_match_all("/" . preg_quote($this->bind_marker, "/") . "/i", $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count ) 
            {
                return $sql;
            }

        }

        if( $this->bind_marker !== "?" ) 
        {
            do
            {
                $c--;
                $sql = substr_replace($sql, "?", $matches[0][$c][1], $ml);
            }
            while( $c !== 0 );
        }

        if( false !== ($this->odbc_result = odbc_prepare($this->conn_id, $sql)) ) 
        {
            $this->binds = array_values($binds);
        }

        return $sql;
    }

    protected function _execute($sql)
    {
        if( !isset($this->odbc_result) ) 
        {
            return odbc_exec($this->conn_id, $sql);
        }

        if( $this->odbc_result === false ) 
        {
            return false;
        }

        if( true === ($success = odbc_execute($this->odbc_result, $this->binds)) ) 
        {
            $this->is_write_type($sql) or             $this->binds = array(  );
            return $success;
        }

        $this->odbc_result = NULL;
    }

    protected function _trans_begin()
    {
        return odbc_autocommit($this->conn_id, false);
    }

    protected function _trans_commit()
    {
        if( odbc_commit($this->conn_id) ) 
        {
            odbc_autocommit($this->conn_id, true);
            return true;
        }

        return false;
    }

    protected function _trans_rollback()
    {
        if( odbc_rollback($this->conn_id) ) 
        {
            odbc_autocommit($this->conn_id, true);
            return true;
        }

        return false;
    }

    public function is_write_type($sql)
    {
        if( preg_match("#^(INSERT|UPDATE).*RETURNING\\s.+(\\,\\s?.+)*\$#is", $sql) ) 
        {
            return false;
        }

        return parent::is_write_type($sql);
    }

    protected function _escape_str($str)
    {
        $this->display_error("db_unsupported_feature");
    }

    public function affected_rows()
    {
        return odbc_num_rows($this->result_id);
    }

    public function insert_id()
    {
        return ($this->db_debug ? $this->display_error("db_unsupported_feature") : false);
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . $this->schema . "'";
        if( $prefix_limit !== false && $this->dbprefix !== "" ) 
        {
            return $sql . " AND table_name LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SHOW COLUMNS FROM " . $table;
    }

    protected function _field_data($table)
    {
        return "SELECT TOP 1 FROM " . $table;
    }

    public function error()
    {
        return array( "code" => odbc_error($this->conn_id), "message" => odbc_errormsg($this->conn_id) );
    }

    protected function _close()
    {
        odbc_close($this->conn_id);
    }

}


