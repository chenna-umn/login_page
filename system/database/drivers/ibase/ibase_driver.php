<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_ibase_driver extends CI_DB
{
    public $dbdriver = "ibase";
    protected $_random_keyword = array( "RAND()", "RAND()" );
    protected $_ibase_trans = NULL;

    public function db_connect($persistent = false)
    {
        return ($persistent === true ? ibase_pconnect($this->hostname . ":" . $this->database, $this->username, $this->password, $this->char_set) : ibase_connect($this->hostname . ":" . $this->database, $this->username, $this->password, $this->char_set));
    }

    public function version()
    {
        if( isset($this->data_cache["version"]) ) 
        {
            return $this->data_cache["version"];
        }

        if( $service = ibase_service_attach($this->hostname, $this->username, $this->password) ) 
        {
            $this->data_cache["version"] = ibase_server_info($service, IBASE_SVC_SERVER_VERSION);
            ibase_service_detach($service);
            return $this->data_cache["version"];
        }

        return false;
    }

    protected function _execute($sql)
    {
        return ibase_query((isset($this->_ibase_trans) ? $this->_ibase_trans : $this->conn_id), $sql);
    }

    protected function _trans_begin()
    {
        if( ($trans_handle = ibase_trans($this->conn_id)) === false ) 
        {
            return false;
        }

        $this->_ibase_trans = $trans_handle;
        return true;
    }

    protected function _trans_commit()
    {
        if( ibase_commit($this->_ibase_trans) ) 
        {
            $this->_ibase_trans = NULL;
            return true;
        }

        return false;
    }

    protected function _trans_rollback()
    {
        if( ibase_rollback($this->_ibase_trans) ) 
        {
            $this->_ibase_trans = NULL;
            return true;
        }

        return false;
    }

    public function affected_rows()
    {
        return ibase_affected_rows($this->conn_id);
    }

    public function insert_id($generator_name, $inc_by = 0)
    {
        return ibase_gen_id("\"" . $generator_name . "\"", $inc_by);
    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT TRIM(\"RDB\$RELATION_NAME\") AS TABLE_NAME FROM \"RDB\$RELATIONS\" WHERE \"RDB\$RELATION_NAME\" NOT LIKE 'RDB\$%' AND \"RDB\$RELATION_NAME\" NOT LIKE 'MON\$%'";
        if( $prefix_limit !== false && $this->dbprefix !== "" ) 
        {
            return $sql . " AND TRIM(\"RDB\$RELATION_NAME\") AS TABLE_NAME LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SELECT TRIM(\"RDB\$FIELD_NAME\") AS COLUMN_NAME FROM \"RDB\$RELATION_FIELDS\" WHERE \"RDB\$RELATION_NAME\" = " . $this->escape($table);
    }

    public function field_data($table)
    {
        $sql = "SELECT \"rfields\".\"RDB\$FIELD_NAME\" AS \"name\",\n\t\t\t\tCASE \"fields\".\"RDB\$FIELD_TYPE\"\n\t\t\t\t\tWHEN 7 THEN 'SMALLINT'\n\t\t\t\t\tWHEN 8 THEN 'INTEGER'\n\t\t\t\t\tWHEN 9 THEN 'QUAD'\n\t\t\t\t\tWHEN 10 THEN 'FLOAT'\n\t\t\t\t\tWHEN 11 THEN 'DFLOAT'\n\t\t\t\t\tWHEN 12 THEN 'DATE'\n\t\t\t\t\tWHEN 13 THEN 'TIME'\n\t\t\t\t\tWHEN 14 THEN 'CHAR'\n\t\t\t\t\tWHEN 16 THEN 'INT64'\n\t\t\t\t\tWHEN 27 THEN 'DOUBLE'\n\t\t\t\t\tWHEN 35 THEN 'TIMESTAMP'\n\t\t\t\t\tWHEN 37 THEN 'VARCHAR'\n\t\t\t\t\tWHEN 40 THEN 'CSTRING'\n\t\t\t\t\tWHEN 261 THEN 'BLOB'\n\t\t\t\t\tELSE NULL\n\t\t\t\tEND AS \"type\",\n\t\t\t\t\"fields\".\"RDB\$FIELD_LENGTH\" AS \"max_length\",\n\t\t\t\t\"rfields\".\"RDB\$DEFAULT_VALUE\" AS \"default\"\n\t\t\tFROM \"RDB\$RELATION_FIELDS\" \"rfields\"\n\t\t\t\tJOIN \"RDB\$FIELDS\" \"fields\" ON \"rfields\".\"RDB\$FIELD_SOURCE\" = \"fields\".\"RDB\$FIELD_NAME\"\n\t\t\tWHERE \"rfields\".\"RDB\$RELATION_NAME\" = " . $this->escape($table) . "\n\t\t\tORDER BY \"rfields\".\"RDB\$FIELD_POSITION\"";
        return (($query = $this->query($sql)) !== false ? $query->result_object() : false);
    }

    public function error()
    {
        return array( "code" => ibase_errcode(), "message" => ibase_errmsg() );
    }

    protected function _update($table, $values)
    {
        $this->qb_limit = false;
        return parent::_update($table, $values);
    }

    protected function _truncate($table)
    {
        return "DELETE FROM " . $table;
    }

    protected function _delete($table)
    {
        $this->qb_limit = false;
        return parent::_delete($table);
    }

    protected function _limit($sql)
    {
        if( stripos($this->version(), "firebird") !== false ) 
        {
            $select = "FIRST " . $this->qb_limit . (($this->qb_offset ? " SKIP " . $this->qb_offset : ""));
        }
        else
        {
            $select = "ROWS " . (($this->qb_offset ? $this->qb_offset . " TO " . ($this->qb_limit + $this->qb_offset) : $this->qb_limit));
        }

        return preg_replace("`SELECT`i", "SELECT " . $select, $sql, 1);
    }

    protected function _insert_batch($table, $keys, $values)
    {
        return ($this->db_debug ? $this->display_error("db_unsupported_feature") : false);
    }

    protected function _close()
    {
        ibase_close($this->conn_id);
    }

}


