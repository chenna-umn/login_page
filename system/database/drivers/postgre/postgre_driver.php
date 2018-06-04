<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_postgre_driver extends CI_DB
{
    public $dbdriver = "postgre";
    public $schema = "public";
    protected $_random_keyword = array( "RANDOM()", "RANDOM()" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( !empty($this->dsn) ) 
        {
            return NULL;
        }

        $this->dsn === "" or if( strpos($this->hostname, "/") !== false ) 
{
    $this->port = "";
}

        $this->hostname === "" or if( !empty($this->port) && ctype_digit($this->port) ) 
{
    $this->dsn .= "port=" . $this->port . " ";
}

        if( $this->username !== "" ) 
        {
            $this->dsn .= "user=" . $this->username . " ";
            $this->password === NULL or             $this->dsn = rtrim($this->dsn);
        }

        $this->database === "" or foreach( array( "connect_timeout", "options", "sslmode", "service" ) as $key ) 
{
    if( isset($this->$key) && is_string($this->$key) && $this->$key !== "" ) 
    {
        $this->dsn .= $key . "='" . $this->$key . "' ";
    }

}
    }

    public function db_connect($persistent = false)
    {
        $this->conn_id = ($persistent === true ? pg_pconnect($this->dsn) : pg_connect($this->dsn));
        if( $this->conn_id !== false ) 
        {
            if( $persistent === true && pg_connection_status($this->conn_id) === PGSQL_CONNECTION_BAD && pg_ping($this->conn_id) === false ) 
            {
                return false;
            }

            empty($this->schema) or $this->simple_query("SET search_path TO " . $this->schema . ",public");
        }

        return $this->conn_id;
    }

    public function reconnect()
    {
        if( pg_ping($this->conn_id) === false ) 
        {
            $this->conn_id = false;
        }

    }

    protected function _db_set_charset($charset)
    {
        return pg_set_client_encoding($this->conn_id, $charset) === 0;
    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        if( !$this->conn_id || ($pg_version = pg_version($this->conn_id)) === false ) 
        {
            return false;
        }

        return (isset($pg_version["server"]) ? $this->data_cache["version"] : parent::version());
    }

    protected function _execute($sql)
    {
        return pg_query($this->conn_id, $sql);
    }

    protected function _trans_begin()
    {
        return (bool) pg_query($this->conn_id, "BEGIN");
    }

    protected function _trans_commit()
    {
        return (bool) pg_query($this->conn_id, "COMMIT");
    }

    protected function _trans_rollback()
    {
        return (bool) pg_query($this->conn_id, "ROLLBACK");
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
        return pg_escape_string($this->conn_id, $str);
    }

    public function escape($str)
    {
        if( is_php("5.4.4") && (is_string($str) || is_object($str) && method_exists($str, "__toString")) ) 
        {
            return pg_escape_literal($this->conn_id, $str);
        }

        if( is_bool($str) ) 
        {
            return ($str ? "TRUE" : "FALSE");
        }

        return parent::escape($str);
    }

    public function affected_rows()
    {
        return pg_affected_rows($this->result_id);
    }

    public function insert_id()
    {
        $v = pg_version($this->conn_id);
        $v = (isset($v["server"]) ? $v["server"] : 0);
        $table = (0 < func_num_args() ? func_get_arg(0) : NULL);
        $column = (1 < func_num_args() ? func_get_arg(1) : NULL);
        if( $table === NULL && "8.1" <= $v ) 
        {
            $sql = "SELECT LASTVAL() AS ins_id";
        }
        else
        {
            if( $table !== NULL ) 
            {
                if( $column !== NULL && "8.0" <= $v ) 
                {
                    $sql = "SELECT pg_get_serial_sequence('" . $table . "', '" . $column . "') AS seq";
                    $query = $this->query($sql);
                    $query = $query->row();
                    $seq = $query->seq;
                }
                else
                {
                    $seq = $table;
                }

                $sql = "SELECT CURRVAL('" . $seq . "') AS ins_id";
            }
            else
            {
                return pg_last_oid($this->result_id);
            }

        }

        $query = $this->query($sql);
        $query = $query->row();
        return (int) $query->ins_id;
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT \"table_name\" FROM \"information_schema\".\"tables\" WHERE \"table_schema\" = '" . $this->schema . "'";
        if( $prefix_limit !== false && $this->dbprefix !== "" ) 
        {
            return $sql . " AND \"table_name\" LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SELECT \"column_name\"\n\t\t\tFROM \"information_schema\".\"columns\"\n\t\t\tWHERE LOWER(\"table_name\") = " . $this->escape(strtolower($table));
    }

    public function field_data($table)
    {
        $sql = "SELECT \"column_name\", \"data_type\", \"character_maximum_length\", \"numeric_precision\", \"column_default\"\n\t\t\tFROM \"information_schema\".\"columns\"\n\t\t\tWHERE LOWER(\"table_name\") = " . $this->escape(strtolower($table));
        if( ($query = $this->query($sql)) === false ) 
        {
            return false;
        }

        $query = $query->result_object();
        $retval = array(  );
        $i = 0;
        for( $c = count($query); $i < $c; $i++ ) 
        {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->column_name;
            $retval[$i]->type = $query[$i]->data_type;
            $retval[$i]->max_length = (0 < $query[$i]->character_maximum_length ? $query[$i]->character_maximum_length : $query[$i]->numeric_precision);
            $retval[$i]->default = $query[$i]->column_default;
        }
        return $retval;
    }

    public function error()
    {
        return array( "code" => "", "message" => pg_last_error($this->conn_id) );
    }

    public function order_by($orderby, $direction = "", $escape = NULL)
    {
        $direction = strtoupper(trim($direction));
        if( $direction === "RANDOM" ) 
        {
            if( !is_float($orderby) && ctype_digit((string) $orderby) ) 
            {
                $orderby = (1 < $orderby ? (double) "0." . $orderby : (double) $orderby);
            }

            if( is_float($orderby) ) 
            {
                $this->simple_query("SET SEED " . $orderby);
            }

            $orderby = $this->_random_keyword[0];
            $direction = "";
            $escape = false;
        }

        return parent::order_by($orderby, $direction, $escape);
    }

    protected function _update($table, $values)
    {
        $this->qb_limit = false;
        $this->qb_orderby = array(  );
        return parent::_update($table, $values);
    }

    protected function _update_batch($table, $values, $index)
    {
        $ids = array(  );
        foreach( $values as $key => $val ) 
        {
            $ids[] = $val[$index]["value"];
            foreach( array_keys($val) as $field ) 
            {
                if( $field !== $index ) 
                {
                    $final[$val[$field]["field"]][] = "WHEN " . $val[$index]["value"] . " THEN " . $val[$field]["value"];
                }

            }
        }
        $cases = "";
        foreach( $final as $k => $v ) 
        {
            $cases .= $k . " = (CASE " . $val[$index]["field"] . "\n" . implode("\n", $v) . "\n" . "ELSE " . $k . " END), ";
        }
        $this->where($val[$index]["field"] . " IN(" . implode(",", $ids) . ")", NULL, false);
        return "UPDATE " . $table . " SET " . substr($cases, 0, -2) . $this->_compile_wh("qb_where");
    }

    protected function _delete($table)
    {
        $this->qb_limit = false;
        return parent::_delete($table);
    }

    protected function _limit($sql)
    {
        return $sql . " LIMIT " . $this->qb_limit . (($this->qb_offset ? " OFFSET " . $this->qb_offset : ""));
    }

    protected function _close()
    {
        pg_close($this->conn_id);
    }

}


