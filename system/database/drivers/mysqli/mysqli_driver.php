<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_mysqli_driver extends CI_DB
{
    public $dbdriver = "mysqli";
    public $compress = false;
    public $delete_hack = true;
    public $stricton = NULL;
    protected $_escape_char = "`";
    protected $_mysqli = NULL;

    public function db_connect($persistent = false)
    {
        if( $this->hostname[0] === "/" ) 
        {
            $hostname = NULL;
            $port = NULL;
            $socket = $this->hostname;
        }
        else
        {
            $hostname = ($persistent === true ? "p:" . $this->hostname : $this->hostname);
            $port = (empty($this->port) ? NULL : $this->port);
            $socket = NULL;
        }

        $client_flags = ($this->compress === true ? MYSQLI_CLIENT_COMPRESS : 0);
        $this->_mysqli = mysqli_init();
        $this->_mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        if( isset($this->stricton) ) 
        {
            if( $this->stricton ) 
            {
                $this->_mysqli->options(MYSQLI_INIT_COMMAND, "SET SESSION sql_mode = CONCAT(@@sql_mode, \",\", \"STRICT_ALL_TABLES\")");
            }
            else
            {
                $this->_mysqli->options(MYSQLI_INIT_COMMAND, "SET SESSION sql_mode =\n\t\t\t\t\tREPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(\n\t\t\t\t\t@@sql_mode,\n\t\t\t\t\t\"STRICT_ALL_TABLES,\", \"\"),\n\t\t\t\t\t\",STRICT_ALL_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_ALL_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_TRANS_TABLES,\", \"\"),\n\t\t\t\t\t\",STRICT_TRANS_TABLES\", \"\"),\n\t\t\t\t\t\"STRICT_TRANS_TABLES\", \"\")");
            }

        }

        if( is_array($this->encrypt) ) 
        {
            $ssl = array(  );
            empty($this->encrypt["ssl_key"]) or $ssl["key"] = $this->encrypt["ssl_key"];
            empty($this->encrypt["ssl_cert"]) or $ssl["cert"] = $this->encrypt["ssl_cert"];
            empty($this->encrypt["ssl_ca"]) or $ssl["ca"] = $this->encrypt["ssl_ca"];
            empty($this->encrypt["ssl_capath"]) or $ssl["capath"] = $this->encrypt["ssl_capath"];
            empty($this->encrypt["ssl_cipher"]) or $ssl["cipher"] = $this->encrypt["ssl_cipher"];
            if( !empty($ssl) ) 
            {
                if( isset($this->encrypt["ssl_verify"]) ) 
                {
                    if( $this->encrypt["ssl_verify"] ) 
                    {
                        defined("MYSQLI_OPT_SSL_VERIFY_SERVER_CERT") and $this->_mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
                    }
                    else
                    {
                        if( defined("MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT") ) 
                        {
                            $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                        }

                    }

                }

                $client_flags |= MYSQLI_CLIENT_SSL;
                $this->_mysqli->ssl_set((isset($ssl["key"]) ? $ssl["key"] : NULL), (isset($ssl["cert"]) ? $ssl["cert"] : NULL), (isset($ssl["ca"]) ? $ssl["ca"] : NULL), (isset($ssl["capath"]) ? $ssl["capath"] : NULL), (isset($ssl["cipher"]) ? $ssl["cipher"] : NULL));
            }

        }

        if( $this->_mysqli->real_connect($hostname, $this->username, $this->password, $this->database, $port, $socket, $client_flags) ) 
        {
            if( $client_flags & MYSQLI_CLIENT_SSL && version_compare($this->_mysqli->client_info, "5.7.3", "<=") && empty($this->_mysqli->query("SHOW STATUS LIKE 'ssl_cipher'")->fetch_object()->Value) ) 
            {
                $this->_mysqli->close();
                $message = "MySQLi was configured for an SSL connection, but got an unencrypted connection instead!";
                log_message("error", $message);
                return ($this->db_debug ? $this->display_error($message, "", true) : false);
            }

            return $this->_mysqli;
        }

        return false;
    }

    public function reconnect()
    {
        if( $this->conn_id !== false && $this->conn_id->ping() === false ) 
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

        if( $this->conn_id->select_db($database) ) 
        {
            $this->database = $database;
            $this->data_cache = array(  );
            return true;
        }

        return false;
    }

    protected function _db_set_charset($charset)
    {
        return $this->conn_id->set_charset($charset);
    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        $this->data_cache["version"] = $this->conn_id->server_info;
        return $this->data_cache["version"];
    }

    protected function _execute($sql)
    {
        return $this->conn_id->query($this->_prep_query($sql));
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
        $this->conn_id->autocommit(false);
        return (is_php("5.5") ? $this->conn_id->begin_transaction() : $this->simple_query("START TRANSACTION"));
    }

    protected function _trans_commit()
    {
        if( $this->conn_id->commit() ) 
        {
            $this->conn_id->autocommit(true);
            return true;
        }

        return false;
    }

    protected function _trans_rollback()
    {
        if( $this->conn_id->rollback() ) 
        {
            $this->conn_id->autocommit(true);
            return true;
        }

        return false;
    }

    protected function _escape_str($str)
    {
        return $this->conn_id->real_escape_string($str);
    }

    public function affected_rows()
    {
        return $this->conn_id->affected_rows;
    }

    public function insert_id()
    {
        return $this->conn_id->insert_id;
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
        if( !empty($this->_mysqli->connect_errno) ) 
        {
            return array( "code" => $this->_mysqli->connect_errno, "message" => $this->_mysqli->connect_error );
        }

        return array( "code" => $this->conn_id->errno, "message" => $this->conn_id->error );
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
        $this->conn_id->close();
    }

}


