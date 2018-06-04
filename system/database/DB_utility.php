<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

abstract class CI_DB_utility
{
    protected $db = NULL;
    protected $_list_databases = false;
    protected $_optimize_table = false;
    protected $_repair_table = false;

    public function __construct(&$db)
    {
        $this->db =& $db;
        log_message("info", "Database Utility Class Initialized");
    }

    public function list_databases()
    {
        if( isset($this->db->data_cache["db_names"]) ) 
        {
            return $this->db->data_cache["db_names"];
        }

        if( $this->_list_databases === false ) 
        {
            return ($this->db->db_debug ? $this->db->display_error("db_unsupported_feature") : false);
        }

        $this->db->data_cache["db_names"] = array(  );
        $query = $this->db->query($this->_list_databases);
        if( $query === false ) 
        {
            return $this->db->data_cache["db_names"];
        }

        $i = 0;
        $query = $query->result_array();
        for( $c = count($query); $i < $c; $i++ ) 
        {
            $this->db->data_cache["db_names"][] = current($query[$i]);
        }
        return $this->db->data_cache["db_names"];
    }

    public function database_exists($database_name)
    {
        return in_array($database_name, $this->list_databases());
    }

    public function optimize_table($table_name)
    {
        if( $this->_optimize_table === false ) 
        {
            return ($this->db->db_debug ? $this->db->display_error("db_unsupported_feature") : false);
        }

        $query = $this->db->query(sprintf($this->_optimize_table, $this->db->escape_identifiers($table_name)));
        if( $query !== false ) 
        {
            $query = $query->result_array();
            return current($query);
        }

        return false;
    }

    public function optimize_database()
    {
        if( $this->_optimize_table === false ) 
        {
            return ($this->db->db_debug ? $this->db->display_error("db_unsupported_feature") : false);
        }

        $result = array(  );
        foreach( $this->db->list_tables() as $table_name ) 
        {
            $res = $this->db->query(sprintf($this->_optimize_table, $this->db->escape_identifiers($table_name)));
            if( is_bool($res) ) 
            {
                return $res;
            }

            $res = $res->result_array();
            $res = current($res);
            $key = str_replace($this->db->database . ".", "", current($res));
            $keys = array_keys($res);
            unset($res[$keys[0]]);
            $result[$key] = $res;
        }
        return $result;
    }

    public function repair_table($table_name)
    {
        if( $this->_repair_table === false ) 
        {
            return ($this->db->db_debug ? $this->db->display_error("db_unsupported_feature") : false);
        }

        $query = $this->db->query(sprintf($this->_repair_table, $this->db->escape_identifiers($table_name)));
        if( is_bool($query) ) 
        {
            return $query;
        }

        $query = $query->result_array();
        return current($query);
    }

    public function csv_from_result($query, $delim = ",", $newline = "\n", $enclosure = "\"")
    {
        if( !is_object($query) || !method_exists($query, "list_fields") ) 
        {
            show_error("You must submit a valid result object");
        }

        $out = "";
        foreach( $query->list_fields() as $name ) 
        {
            $out .= $enclosure . str_replace($enclosure, $enclosure . $enclosure, $name) . $enclosure . $delim;
        }
        $out = substr($out, 0, 0 - strlen($delim)) . $newline;
        while( $row = $query->unbuffered_row("array") ) 
        {
            $line = array(  );
            foreach( $row as $item ) 
            {
                $line[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $item) . $enclosure;
            }
            $out .= implode($delim, $line) . $newline;
        }
        return $out;
    }

    public function xml_from_result($query, $params = array(  ))
    {
        if( !is_object($query) || !method_exists($query, "list_fields") ) 
        {
            show_error("You must submit a valid result object");
        }

        foreach( array( "root" => "root", "element" => "element", "newline" => "\n", "tab" => "\t" ) as $key => $val ) 
        {
            if( !isset($params[$key]) ) 
            {
                $params[$key] = $val;
            }

        }
        extract($params);
        get_instance()->load->helper("xml");
        $xml = "<" . $root . ">" . $newline;
        while( $row = $query->unbuffered_row() ) 
        {
            $xml .= $tab . "<" . $element . ">" . $newline;
            foreach( $row as $key => $val ) 
            {
                $xml .= $tab . $tab . "<" . $key . ">" . xml_convert($val) . "</" . $key . ">" . $newline;
            }
            $xml .= $tab . "</" . $element . ">" . $newline;
        }
        return $xml . "</" . $root . ">" . $newline;
    }

    public function backup($params = array(  ))
    {
        if( is_string($params) ) 
        {
            $params = array( "tables" => $params );
        }

        $prefs = array( "tables" => array(  ), "ignore" => array(  ), "filename" => "", "format" => "gzip", "add_drop" => true, "add_insert" => true, "newline" => "\n", "foreign_key_checks" => true );
        if( 0 < count($params) ) 
        {
            foreach( $prefs as $key => $val ) 
            {
                if( isset($params[$key]) ) 
                {
                    $prefs[$key] = $params[$key];
                }

            }
        }

        if( count($prefs["tables"]) === 0 ) 
        {
            $prefs["tables"] = $this->db->list_tables();
        }

        if( !in_array($prefs["format"], array( "gzip", "zip", "txt" ), true) ) 
        {
            $prefs["format"] = "txt";
        }

        if( $prefs["format"] === "gzip" && !function_exists("gzencode") || $prefs["format"] === "zip" && !function_exists("gzcompress") ) 
        {
            if( $this->db->db_debug ) 
            {
                return $this->db->display_error("db_unsupported_compression");
            }

            $prefs["format"] = "txt";
        }

        if( $prefs["format"] === "zip" ) 
        {
            if( $prefs["filename"] === "" ) 
            {
                $prefs["filename"] = ((count($prefs["tables"]) === 1 ? $prefs["tables"] : $this->db->database)) . date("Y-m-d_H-i", time()) . ".sql";
            }
            else
            {
                if( preg_match("|.+?\\.zip\$|", $prefs["filename"]) ) 
                {
                    $prefs["filename"] = str_replace(".zip", "", $prefs["filename"]);
                }

                if( !preg_match("|.+?\\.sql\$|", $prefs["filename"]) ) 
                {
                    $prefs["filename"] .= ".sql";
                }

            }

            $CI =& get_instance();
            $CI->load->library("zip");
            $CI->zip->add_data($prefs["filename"], $this->_backup($prefs));
            return $CI->zip->get_zip();
        }

        if( $prefs["format"] === "txt" ) 
        {
            return $this->_backup($prefs);
        }

        if( $prefs["format"] === "gzip" ) 
        {
            return gzencode($this->_backup($prefs));
        }

    }

}


