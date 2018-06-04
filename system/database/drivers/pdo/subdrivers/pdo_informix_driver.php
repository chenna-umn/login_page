<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_informix_driver extends CI_DB_pdo_driver
{
    public $subdriver = "informix";
    protected $_random_keyword = array( "ASC", "ASC" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "informix:";
            if( empty($this->hostname) && empty($this->host) && empty($this->port) && empty($this->service) ) 
            {
                if( isset($this->DSN) ) 
                {
                    $this->dsn .= "DSN=" . $this->DSN;
                }
                else
                {
                    if( !empty($this->database) ) 
                    {
                        $this->dsn .= "DSN=" . $this->database;
                    }

                }

                return NULL;
            }

            if( isset($this->host) ) 
            {
                $this->dsn .= "host=" . $this->host;
            }
            else
            {
                $this->dsn .= "host=" . ((empty($this->hostname) ? "127.0.0.1" : $this->hostname));
            }

            if( isset($this->service) ) 
            {
                $this->dsn .= "; service=" . $this->service;
            }
            else
            {
                if( !empty($this->port) ) 
                {
                    $this->dsn .= "; service=" . $this->port;
                }

            }

            empty($this->database) or empty($this->server) or $this->dsn .= "; protocol=" . ((isset($this->protocol) ? $this->protocol : "onsoctcp")) . "; EnableScrollableCursors=1";
        }

    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT \"tabname\" FROM \"systables\"\n\t\t\tWHERE \"tabid\" > 99 AND \"tabtype\" = 'T' AND LOWER(\"owner\") = " . $this->escape(strtolower($this->username));
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
        {
            $sql .= " AND \"tabname\" LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        if( strpos($table, ".") !== false ) 
        {
            sscanf($table, "%[^.].%s", $owner, $table);
        }
        else
        {
            $owner = $this->username;
        }

        return "SELECT \"colname\" FROM \"systables\", \"syscolumns\"\n\t\t\tWHERE \"systables\".\"tabid\" = \"syscolumns\".\"tabid\"\n\t\t\t\tAND \"systables\".\"tabtype\" = 'T'\n\t\t\t\tAND LOWER(\"systables\".\"owner\") = " . $this->escape(strtolower($owner)) . "\n\t\t\t\tAND LOWER(\"systables\".\"tabname\") = " . $this->escape(strtolower($table));
    }

    public function field_data($table)
    {
        $sql = "SELECT \"syscolumns\".\"colname\" AS \"name\",\n\t\t\t\tCASE \"syscolumns\".\"coltype\"\n\t\t\t\t\tWHEN 0 THEN 'CHAR'\n\t\t\t\t\tWHEN 1 THEN 'SMALLINT'\n\t\t\t\t\tWHEN 2 THEN 'INTEGER'\n\t\t\t\t\tWHEN 3 THEN 'FLOAT'\n\t\t\t\t\tWHEN 4 THEN 'SMALLFLOAT'\n\t\t\t\t\tWHEN 5 THEN 'DECIMAL'\n\t\t\t\t\tWHEN 6 THEN 'SERIAL'\n\t\t\t\t\tWHEN 7 THEN 'DATE'\n\t\t\t\t\tWHEN 8 THEN 'MONEY'\n\t\t\t\t\tWHEN 9 THEN 'NULL'\n\t\t\t\t\tWHEN 10 THEN 'DATETIME'\n\t\t\t\t\tWHEN 11 THEN 'BYTE'\n\t\t\t\t\tWHEN 12 THEN 'TEXT'\n\t\t\t\t\tWHEN 13 THEN 'VARCHAR'\n\t\t\t\t\tWHEN 14 THEN 'INTERVAL'\n\t\t\t\t\tWHEN 15 THEN 'NCHAR'\n\t\t\t\t\tWHEN 16 THEN 'NVARCHAR'\n\t\t\t\t\tWHEN 17 THEN 'INT8'\n\t\t\t\t\tWHEN 18 THEN 'SERIAL8'\n\t\t\t\t\tWHEN 19 THEN 'SET'\n\t\t\t\t\tWHEN 20 THEN 'MULTISET'\n\t\t\t\t\tWHEN 21 THEN 'LIST'\n\t\t\t\t\tWHEN 22 THEN 'Unnamed ROW'\n\t\t\t\t\tWHEN 40 THEN 'LVARCHAR'\n\t\t\t\t\tWHEN 41 THEN 'BLOB/CLOB/BOOLEAN'\n\t\t\t\t\tWHEN 4118 THEN 'Named ROW'\n\t\t\t\t\tELSE \"syscolumns\".\"coltype\"\n\t\t\t\tEND AS \"type\",\n\t\t\t\t\"syscolumns\".\"collength\" as \"max_length\",\n\t\t\t\tCASE \"sysdefaults\".\"type\"\n\t\t\t\t\tWHEN 'L' THEN \"sysdefaults\".\"default\"\n\t\t\t\t\tELSE NULL\n\t\t\t\tEND AS \"default\"\n\t\t\tFROM \"syscolumns\", \"systables\", \"sysdefaults\"\n\t\t\tWHERE \"syscolumns\".\"tabid\" = \"systables\".\"tabid\"\n\t\t\t\tAND \"systables\".\"tabid\" = \"sysdefaults\".\"tabid\"\n\t\t\t\tAND \"syscolumns\".\"colno\" = \"sysdefaults\".\"colno\"\n\t\t\t\tAND \"systables\".\"tabtype\" = 'T'\n\t\t\t\tAND LOWER(\"systables\".\"owner\") = " . $this->escape(strtolower($this->username)) . "\n\t\t\t\tAND LOWER(\"systables\".\"tabname\") = " . $this->escape(strtolower($table)) . "\n\t\t\tORDER BY \"syscolumns\".\"colno\"";
        return (($query = $this->query($sql)) !== false ? $query->result_object() : false);
    }

    protected function _update($table, $values)
    {
        $this->qb_limit = false;
        $this->qb_orderby = array(  );
        return parent::_update($table, $values);
    }

    protected function _truncate($table)
    {
        return "TRUNCATE TABLE ONLY " . $table;
    }

    protected function _delete($table)
    {
        $this->qb_limit = false;
        return parent::_delete($table);
    }

    protected function _limit($sql)
    {
        $select = "SELECT " . (($this->qb_offset ? "SKIP " . $this->qb_offset : "")) . "FIRST " . $this->qb_limit . " ";
        return preg_replace("/^(SELECT\\s)/i", $select, $sql, 1);
    }

}


