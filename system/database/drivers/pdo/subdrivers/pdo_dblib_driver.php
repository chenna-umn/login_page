<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_dblib_driver extends CI_DB_pdo_driver
{
    public $subdriver = "dblib";
    protected $_random_keyword = array( "NEWID()", "RAND(%d)" );
    protected $_quoted_identifier = NULL;

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = $params["subdriver"] . ":host=" . ((empty($this->hostname) ? "127.0.0.1" : $this->hostname));
            if( !empty($this->port) ) 
            {
                $this->dsn .= ((DIRECTORY_SEPARATOR === "\\" ? "," : ":")) . $this->port;
            }

            empty($this->database) or empty($this->char_set) or empty($this->appname) or         }
        else
        {
            if( !empty($this->char_set) && strpos($this->dsn, "charset=", 6) === false ) 
            {
                $this->dsn .= ";charset=" . $this->char_set;
            }

            $this->subdriver = "dblib";
        }

    }

    public function db_connect($persistent = false)
    {
        if( $persistent === true ) 
        {
            log_message("debug", "dblib driver doesn't support persistent connections");
        }

        $this->conn_id = parent::db_connect(false);
        if( !is_object($this->conn_id) ) 
        {
            return $this->conn_id;
        }

        $query = $this->query("SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi");
        $query = $query->row_array();
        $this->_quoted_identifier = (empty($query) ? false : (bool) $query["qi"]);
        $this->_escape_char = ($this->_quoted_identifier ? "\"" : array( "[", "]" ));
        return $this->conn_id;
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT " . $this->escape_identifiers("name") . " FROM " . $this->escape_identifiers("sysobjects") . " WHERE " . $this->escape_identifiers("type") . " = 'U'";
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
        {
            $sql .= " AND " . $this->escape_identifiers("name") . " LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql . " ORDER BY " . $this->escape_identifiers("name");
    }

    protected function _list_columns($table = "")
    {
        return "SELECT COLUMN_NAME\n\t\t\tFROM INFORMATION_SCHEMA.Columns\n\t\t\tWHERE UPPER(TABLE_NAME) = " . $this->escape(strtoupper($table));
    }

    public function field_data($table)
    {
        $sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, COLUMN_DEFAULT\n\t\t\tFROM INFORMATION_SCHEMA.Columns\n\t\t\tWHERE UPPER(TABLE_NAME) = " . $this->escape(strtoupper($table));
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
            $retval[$i]->name = $query[$i]->COLUMN_NAME;
            $retval[$i]->type = $query[$i]->DATA_TYPE;
            $retval[$i]->max_length = (0 < $query[$i]->CHARACTER_MAXIMUM_LENGTH ? $query[$i]->CHARACTER_MAXIMUM_LENGTH : $query[$i]->NUMERIC_PRECISION);
            $retval[$i]->default = $query[$i]->COLUMN_DEFAULT;
        }
        return $retval;
    }

    protected function _update($table, $values)
    {
        $this->qb_limit = false;
        $this->qb_orderby = array(  );
        return parent::_update($table, $values);
    }

    protected function _delete($table)
    {
        if( $this->qb_limit ) 
        {
            return "WITH ci_delete AS (SELECT TOP " . $this->qb_limit . " * FROM " . $table . $this->_compile_wh("qb_where") . ") DELETE FROM ci_delete";
        }

        return parent::_delete($table);
    }

    protected function _limit($sql)
    {
        $limit = $this->qb_offset + $this->qb_limit;
        if( version_compare($this->version(), "9", ">=") && $this->qb_offset && !empty($this->qb_orderby) ) 
        {
            $orderby = $this->_compile_order_by();
            $sql = trim(substr($sql, 0, strrpos($sql, $orderby)));
            if( count($this->qb_select) === 0 ) 
            {
                $select = "*";
            }
            else
            {
                $select = array(  );
                $field_regexp = ($this->_quoted_identifier ? "(\"[^\\\"]+\")" : "(\\[[^\\]]+\\])");
                $i = 0;
                for( $c = count($this->qb_select); $i < $c; $i++ ) 
                {
                    $select[] = (preg_match("/(?:\\s|\\.)" . $field_regexp . "\$/i", $this->qb_select[$i], $m) ? $m[1] : $this->qb_select[$i]);
                }
                $select = implode(", ", $select);
            }

            return "SELECT " . $select . " FROM (\n\n" . preg_replace("/^(SELECT( DISTINCT)?)/i", "\\1 ROW_NUMBER() OVER(" . trim($orderby) . ") AS " . $this->escape_identifiers("CI_rownum") . ", ", $sql) . "\n\n) " . $this->escape_identifiers("CI_subquery") . "\nWHERE " . $this->escape_identifiers("CI_rownum") . " BETWEEN " . ($this->qb_offset + 1) . " AND " . $limit;
        }

        return preg_replace("/(^\\SELECT (DISTINCT)?)/i", "\\1 TOP " . $limit . " ", $sql);
    }

    protected function _insert_batch($table, $keys, $values)
    {
        if( version_compare($this->version(), "10", ">=") ) 
        {
            return parent::_insert_batch($table, $keys, $values);
        }

        return ($this->db_debug ? $this->display_error("db_unsupported_feature") : false);
    }

}


