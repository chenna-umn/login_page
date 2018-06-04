<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_pgsql_driver extends CI_DB_pdo_driver
{
    public $subdriver = "pgsql";
    public $schema = "public";
    protected $_random_keyword = array( "RANDOM()", "RANDOM()" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "pgsql:host=" . ((empty($this->hostname) ? "127.0.0.1" : $this->hostname));
            empty($this->port) or empty($this->database) or if( !empty($this->username) ) 
{
    $this->dsn .= ";username=" . $this->username;
    empty($this->password) or }

        }

    }

    public function db_connect($persistent = false)
    {
        $this->conn_id = parent::db_connect($persistent);
        if( is_object($this->conn_id) && !empty($this->schema) ) 
        {
            $this->simple_query("SET search_path TO " . $this->schema . ",public");
        }

        return $this->conn_id;
    }

    public function insert_id($name = NULL)
    {
        if( $name === NULL && version_compare($this->version(), "8.1", ">=") ) 
        {
            $query = $this->query("SELECT LASTVAL() AS ins_id");
            $query = $query->row();
            return $query->ins_id;
        }

        return $this->conn_id->lastInsertId($name);
    }

    public function is_write_type($sql)
    {
        if( preg_match("#^(INSERT|UPDATE).*RETURNING\\s.+(\\,\\s?.+)*\$#is", $sql) ) 
        {
            return false;
        }

        return parent::is_write_type($sql);
    }

    public function escape($str)
    {
        if( is_bool($str) ) 
        {
            return ($str ? "TRUE" : "FALSE");
        }

        return parent::escape($str);
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

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT \"table_name\" FROM \"information_schema\".\"tables\" WHERE \"table_schema\" = '" . $this->schema . "'";
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
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

}


