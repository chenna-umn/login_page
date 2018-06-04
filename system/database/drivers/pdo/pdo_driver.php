<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_driver extends CI_DB
{
    public $dbdriver = "pdo";
    public $options = array(  );

    public function __construct($params)
    {
        parent::__construct($params);
        if( preg_match("/([^:]+):/", $this->dsn, $match) && count($match) === 2 ) 
        {
            $this->subdriver = $match[1];
        }
        else
        {
            if( preg_match("/([^:]+):/", $this->hostname, $match) && count($match) === 2 ) 
            {
                $this->dsn = $this->hostname;
                $this->hostname = NULL;
                $this->subdriver = $match[1];
            }
            else
            {
                if( in_array($this->subdriver, array( "mssql", "sybase" ), true) ) 
                {
                    $this->subdriver = "dblib";
                }
                else
                {
                    if( $this->subdriver === "4D" ) 
                    {
                        $this->subdriver = "4d";
                    }
                    else
                    {
                        if( !in_array($this->subdriver, array( "4d", "cubrid", "dblib", "firebird", "ibm", "informix", "mysql", "oci", "odbc", "pgsql", "sqlite", "sqlsrv" ), true) ) 
                        {
                            log_message("error", "PDO: Invalid or non-existent subdriver");
                            if( $this->db_debug ) 
                            {
                                show_error("Invalid or non-existent PDO subdriver");
                            }

                        }

                    }

                }

                $this->dsn = NULL;
            }

        }

    }

    public function db_connect($persistent = false)
    {
        if( $persistent === true ) 
        {
            $this->options[PDO::ATTR_PERSISTENT] = true;
        }

        try
        {
            return new PDO($this->dsn, $this->username, $this->password, $this->options);
        }
        catch( PDOException $e ) 
        {
            if( $this->db_debug && empty($this->failover) ) 
            {
                $this->display_error($e->getMessage(), "", true);
            }

            return false;
        }
    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        try
        {
            $this->data_cache["version"] = $this->conn_id->getAttribute(PDO::ATTR_SERVER_VERSION);
            return $this->data_cache["version"];
        }
        catch( PDOException $e ) 
        {
            return parent::version();
        }
    }

    protected function _execute($sql)
    {
        return $this->conn_id->query($sql);
    }

    protected function _trans_begin()
    {
        return $this->conn_id->beginTransaction();
    }

    protected function _trans_commit()
    {
        return $this->conn_id->commit();
    }

    protected function _trans_rollback()
    {
        return $this->conn_id->rollBack();
    }

    protected function _escape_str($str)
    {
        $str = $this->conn_id->quote($str);
        return ($str[0] === "'" ? substr($str, 1, -1) : $str);
    }

    public function affected_rows()
    {
        return (is_object($this->result_id) ? $this->result_id->rowCount() : 0);
    }

    public function insert_id($name = NULL)
    {
        return $this->conn_id->lastInsertId($name);
    }

    protected function _field_data($table)
    {
        return "SELECT TOP 1 * FROM " . $this->protect_identifiers($table);
    }

    public function error()
    {
        $error = array( "code" => "00000", "message" => "" );
        $pdo_error = $this->conn_id->errorInfo();
        if( empty($pdo_error[0]) ) 
        {
            return $error;
        }

        $error["code"] = (isset($pdo_error[1]) ? $pdo_error[0] . "/" . $pdo_error[1] : $pdo_error[0]);
        if( isset($pdo_error[2]) ) 
        {
            $error["message"] = $pdo_error[2];
        }

        return $error;
    }

    protected function _truncate($table)
    {
        return "TRUNCATE TABLE " . $table;
    }

}


