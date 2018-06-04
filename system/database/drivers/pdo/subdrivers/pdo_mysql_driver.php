<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_mysql_driver extends CI_DB_pdo_driver
{
    public $subdriver = "mysql";
    public $compress = false;
    public $stricton = NULL;
    protected $_escape_char = "`";

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "mysql:host=" . ((empty($this->hostname) ? "127.0.0.1" : $this->hostname));
            empty($this->port) or empty($this->database) or empty($this->char_set) or         }
        else
        {
            if( !empty($this->char_set) && strpos($this->dsn, "charset=", 6) === false ) 
            {
                $this->dsn .= ";charset=" . $this->char_set;
            }

        }

    }

    public function db_connect($persistent = false)
    {
        if( isset($this->stricton) ) 
        {
            if( $this->stricton ) 
            {
                $sql = "CONCAT(@@sql_mode, \",\", \"STRICT_ALL_TABLES\")";
            }
            else
            {
                $sql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(\n                                        @@sql_mode,\n                                        \"STRICT_ALL_TABLES,\", \"\"),\n                                        \",STRICT_ALL_TABLES\", \"\"),\n                                        \"STRICT_ALL_TABLES\", \"\"),\n                                        \"STRICT_TRANS_TABLES,\", \"\"),\n                                        \",STRICT_TRANS_TABLES\", \"\"),\n                                        \"STRICT_TRANS_TABLES\", \"\")";
            }

            if( !empty($sql) ) 
            {
                if( empty($this->options[PDO::MYSQL_ATTR_INIT_COMMAND]) ) 
                {
                    $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode = " . $sql;
                }
                else
                {
                    $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] .= ", @@session.sql_mode = " . $sql;
                }

            }

        }

        if( $this->compress === true ) 
        {
            $this->options[PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        if( is_array($this->encrypt) ) 
        {
            $ssl = array(  );
            empty($this->encrypt["ssl_key"]) or $ssl[PDO::MYSQL_ATTR_SSL_KEY] = $this->encrypt["ssl_key"];
            empty($this->encrypt["ssl_cert"]) or $ssl[PDO::MYSQL_ATTR_SSL_CERT] = $this->encrypt["ssl_cert"];
            empty($this->encrypt["ssl_ca"]) or $ssl[PDO::MYSQL_ATTR_SSL_CA] = $this->encrypt["ssl_ca"];
            empty($this->encrypt["ssl_capath"]) or $ssl[PDO::MYSQL_ATTR_SSL_CAPATH] = $this->encrypt["ssl_capath"];
            empty($this->encrypt["ssl_cipher"]) or $ssl[PDO::MYSQL_ATTR_SSL_CIPHER] = $this->encrypt["ssl_cipher"];
            empty($ssl) or         }

        if( ($pdo = parent::db_connect($persistent)) !== false && !empty($ssl) && version_compare($pdo->getAttribute(PDO::ATTR_CLIENT_VERSION), "5.7.3", "<=") && empty($pdo->query("SHOW STATUS LIKE 'ssl_cipher'")->fetchObject()->Value) ) 
        {
            $message = "PDO_MYSQL was configured for an SSL connection, but got an unencrypted connection instead!";
            log_message("error", $message);
            return ($this->db_debug ? $this->display_error($message, "", true) : false);
        }

        return $pdo;
    }

    public function db_select($database = "")
    {
        if( $database === "" ) 
        {
            $database = $this->database;
        }

        if( false !== $this->simple_query("USE " . $this->escape_identifiers($database)) ) 
        {
            $this->database = $database;
            $this->data_cache = array(  );
            return true;
        }

        return false;
    }

    protected function _trans_begin()
    {
        $this->conn_id->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        return $this->conn_id->beginTransaction();
    }

    protected function _trans_commit()
    {
        if( $this->conn_id->commit() ) 
        {
            $this->conn_id->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
            return true;
        }

        return false;
    }

    protected function _trans_rollback()
    {
        if( $this->conn_id->rollBack() ) 
        {
            $this->conn_id->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
            return true;
        }

        return false;
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


