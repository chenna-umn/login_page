<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_firebird_driver extends CI_DB_pdo_driver
{
    public $subdriver = "firebird";
    protected $_random_keyword = array( "RAND()", "RAND()" );

    public function __construct($params)
    {
        parent::__construct($params);
        if( empty($this->dsn) ) 
        {
            $this->dsn = "firebird:";
            if( !empty($this->database) ) 
            {
                $this->dsn .= "dbname=" . $this->database;
            }
            else
            {
                if( !empty($this->hostname) ) 
                {
                    $this->dsn .= "dbname=" . $this->hostname;
                }

            }

            empty($this->char_set) or empty($this->role) or         }
        else
        {
            if( !empty($this->char_set) && strpos($this->dsn, "charset=", 9) === false ) 
            {
                $this->dsn .= ";charset=" . $this->char_set;
            }

        }

    }

    protected function _list_tables($prefix_limit = false)
    {
        $sql = "SELECT \"RDB\$RELATION_NAME\" FROM \"RDB\$RELATIONS\" WHERE \"RDB\$RELATION_NAME\" NOT LIKE 'RDB\$%' AND \"RDB\$RELATION_NAME\" NOT LIKE 'MON\$%'";
        if( $prefix_limit === true && $this->dbprefix !== "" ) 
        {
            return $sql . " AND \"RDB\$RELATION_NAME\" LIKE '" . $this->escape_like_str($this->dbprefix) . "%' " . sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    protected function _list_columns($table = "")
    {
        return "SELECT \"RDB\$FIELD_NAME\" FROM \"RDB\$RELATION_FIELDS\" WHERE \"RDB\$RELATION_NAME\" = " . $this->escape($table);
    }

    public function field_data($table)
    {
        $sql = "SELECT \"rfields\".\"RDB\$FIELD_NAME\" AS \"name\",\n\t\t\t\tCASE \"fields\".\"RDB\$FIELD_TYPE\"\n\t\t\t\t\tWHEN 7 THEN 'SMALLINT'\n\t\t\t\t\tWHEN 8 THEN 'INTEGER'\n\t\t\t\t\tWHEN 9 THEN 'QUAD'\n\t\t\t\t\tWHEN 10 THEN 'FLOAT'\n\t\t\t\t\tWHEN 11 THEN 'DFLOAT'\n\t\t\t\t\tWHEN 12 THEN 'DATE'\n\t\t\t\t\tWHEN 13 THEN 'TIME'\n\t\t\t\t\tWHEN 14 THEN 'CHAR'\n\t\t\t\t\tWHEN 16 THEN 'INT64'\n\t\t\t\t\tWHEN 27 THEN 'DOUBLE'\n\t\t\t\t\tWHEN 35 THEN 'TIMESTAMP'\n\t\t\t\t\tWHEN 37 THEN 'VARCHAR'\n\t\t\t\t\tWHEN 40 THEN 'CSTRING'\n\t\t\t\t\tWHEN 261 THEN 'BLOB'\n\t\t\t\t\tELSE NULL\n\t\t\t\tEND AS \"type\",\n\t\t\t\t\"fields\".\"RDB\$FIELD_LENGTH\" AS \"max_length\",\n\t\t\t\t\"rfields\".\"RDB\$DEFAULT_VALUE\" AS \"default\"\n\t\t\tFROM \"RDB\$RELATION_FIELDS\" \"rfields\"\n\t\t\t\tJOIN \"RDB\$FIELDS\" \"fields\" ON \"rfields\".\"RDB\$FIELD_SOURCE\" = \"fields\".\"RDB\$FIELD_NAME\"\n\t\t\tWHERE \"rfields\".\"RDB\$RELATION_NAME\" = " . $this->escape($table) . "\n\t\t\tORDER BY \"rfields\".\"RDB\$FIELD_POSITION\"";
        return (($query = $this->query($sql)) !== false ? $query->result_object() : false);
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
            $select = "FIRST " . $this->qb_limit . ((0 < $this->qb_offset ? " SKIP " . $this->qb_offset : ""));
        }
        else
        {
            $select = "ROWS " . ((0 < $this->qb_offset ? $this->qb_offset . " TO " . ($this->qb_limit + $this->qb_offset) : $this->qb_limit));
        }

        return preg_replace("`SELECT`i", "SELECT " . $select, $sql);
    }

    protected function _insert_batch($table, $keys, $values)
    {
        return ($this->db_debug ? $this->display_error("db_unsupported_feature") : false);
    }

}


