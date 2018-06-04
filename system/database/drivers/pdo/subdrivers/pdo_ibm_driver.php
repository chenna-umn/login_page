<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_ibm_driver extends CI_DB_pdo_driver
{
    public $subdriver = "ibm";

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "ibm:";
            if( empty($this->hostname) && empty($this->HOSTNAME) && empty($this->port) && empty($this->PORT) ) 
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

            $this->dsn .= "DRIVER=" . ((isset($this->DRIVER) ? "{" . $this->DRIVER . "}" : "{IBM DB2 ODBC DRIVER}")) . ";";
            if( isset($this->DATABASE) ) 
            {
                $this->dsn .= "DATABASE=" . $this->DATABASE . ";";
            }
            else
            {
                if( !empty($this->database) ) 
                {
                    $this->dsn .= "DATABASE=" . $this->database . ";";
                }

            }

            if( isset($this->HOSTNAME) ) 
            {
                $this->dsn .= "HOSTNAME=" . $this->HOSTNAME . ";";
            }
            else
            {
                $this->dsn .= "HOSTNAME=" . ((empty($this->hostname) ? "127.0.0.1;" : $this->hostname . ";"));
            }

            if( isset($this->PORT) ) 
            {
                $this->dsn .= "PORT=" . $this->port . ";";
            }
            else
            {
                if( !empty($this->port) ) 
                {
                    $this->dsn .= ";PORT=" . $this->port . ";";
                }

            }

            $this->dsn .= "PROTOCOL=" . ((isset($this->PROTOCOL) ? $this->PROTOCOL . ";" : "TCPIP;"));
        }

    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT \"tabname\" FROM \"syscat\".\"tables\"\n\t\t\tWHERE \"type\" = 'T' AND LOWER(\"tabschema\") = " . $this->escape(strtolower($this->database));
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
        {
            $sql .= " AND \"tabname\" LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SELECT \"colname\" FROM \"syscat\".\"columns\"\n\t\t\tWHERE LOWER(\"tabschema\") = " . $this->escape(strtolower($this->database)) . "\n\t\t\t\tAND LOWER(\"tabname\") = " . $this->escape(strtolower($table));
    }

    public function field_data($table)
    {
        $sql = "SELECT \"colname\" AS \"name\", \"typename\" AS \"type\", \"default\" AS \"default\", \"length\" AS \"max_length\",\n\t\t\t\tCASE \"keyseq\" WHEN NULL THEN 0 ELSE 1 END AS \"primary_key\"\n\t\t\tFROM \"syscat\".\"columns\"\n\t\t\tWHERE LOWER(\"tabschema\") = " . $this->escape(strtolower($this->database)) . "\n\t\t\t\tAND LOWER(\"tabname\") = " . $this->escape(strtolower($table)) . "\n\t\t\tORDER BY \"colno\"";
        return (($query = $this->query($sql)) !== false ? $query->result_object() : false);
    }

    protected function _update($table, $values)
    {
        $this->qb_limit = false;
        $this->qb_orderby = array(  );
        return parent::_update($table, $values);
    }

    protected function _delete($table)
    {
        $this->qb_limit = false;
        return parent::_delete($table);
    }

    protected function _limit($sql)
    {
        $sql .= " FETCH FIRST " . ($this->qb_limit + $this->qb_offset) . " ROWS ONLY";
        return ($this->qb_offset ? "SELECT * FROM (" . $sql . ") WHERE rownum > " . $this->qb_offset : $sql);
    }

}


