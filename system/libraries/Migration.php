<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Migration
{
    protected $_migration_enabled = false;
    protected $_migration_type = "sequential";
    protected $_migration_path = NULL;
    protected $_migration_version = 0;
    protected $_migration_table = "migrations";
    protected $_migration_auto_latest = false;
    protected $_migration_regex = NULL;
    protected $_error_string = "";

    public function __construct($config = array(  ))
    {
        if( !in_array(get_class($this), array( "CI_Migration", config_item("subclass_prefix") . "Migration" ), true) ) 
        {
            return NULL;
        }

        foreach( $config as $key => $val ) 
        {
            $this->{"_" . $key} = $val;
        }
        log_message("info", "Migrations Class Initialized");
        if( $this->_migration_enabled !== true ) 
        {
            show_error("Migrations has been loaded but is disabled or set up incorrectly.");
        }

        $this->_migration_path !== "" or $this->_migration_path = rtrim($this->_migration_path, "/") . "/";
        $this->lang->load("migration");
        $this->load->dbforge();
        if( empty($this->_migration_table) ) 
        {
            show_error("Migrations configuration file (migration.php) must have \"migration_table\" set.");
        }

        $this->_migration_regex = ($this->_migration_type === "timestamp" ? "/^\\d{14}_(\\w+)\$/" : "/^\\d{3}_(\\w+)\$/");
        if( !in_array($this->_migration_type, array( "sequential", "timestamp" )) ) 
        {
            show_error("An invalid migration numbering type was specified: " . $this->_migration_type);
        }

        if( !$this->db->table_exists($this->_migration_table) ) 
        {
            $this->dbforge->add_field(array( "version" => array( "type" => "BIGINT", "constraint" => 20 ) ));
            $this->dbforge->create_table($this->_migration_table, true);
            $this->db->insert($this->_migration_table, array( "version" => 0 ));
        }

        if( $this->_migration_auto_latest === true && !$this->latest() ) 
        {
            show_error($this->error_string());
        }

    }

    public function version($target_version)
    {
        $current_version = $this->_get_version();
        if( $this->_migration_type === "sequential" ) 
        {
            $target_version = sprintf("%03d", $target_version);
        }
        else
        {
            $target_version = (string) $target_version;
        }

        $migrations = $this->find_migrations();
        if( 0 < $target_version && !isset($migrations[$target_version]) ) 
        {
            $this->_error_string = sprintf($this->lang->line("migration_not_found"), $target_version);
            return false;
        }

        if( $current_version < $target_version ) 
        {
            $method = "up";
        }
        else
        {
            if( $target_version < $current_version ) 
            {
                $method = "down";
                krsort($migrations);
            }
            else
            {
                return true;
            }

        }

        $pending = array(  );
        foreach( $migrations as $number => $file ) 
        {
            if( $method === "up" ) 
            {
                if( $number <= $current_version ) 
                {
                    continue;
                }

                if( $target_version < $number ) 
                {
                    break;
                }

            }
            else
            {
                if( $current_version < $number ) 
                {
                    continue;
                }

                if( $number <= $target_version ) 
                {
                    break;
                }

            }

            if( $this->_migration_type === "sequential" ) 
            {
                if( isset($previous) && 1 < abs($number - $previous) ) 
                {
                    $this->_error_string = sprintf($this->lang->line("migration_sequence_gap"), $number);
                    return false;
                }

                $previous = $number;
            }

            include_once($file);
            $class = "Migration_" . ucfirst(strtolower($this->_get_migration_name(basename($file, ".php"))));
            if( !class_exists($class, false) ) 
            {
                $this->_error_string = sprintf($this->lang->line("migration_class_doesnt_exist"), $class);
                return false;
            }

            if( !is_callable(array( $class, $method )) ) 
            {
                $this->_error_string = sprintf($this->lang->line("migration_missing_" . $method . "_method"), $class);
                return false;
            }

            $pending[$number] = array( $class, $method );
        }
        foreach( $pending as $number => $migration ) 
        {
            log_message("debug", "Migrating " . $method . " from version " . $current_version . " to version " . $number);
            $migration[0] = new $migration[0]();
            call_user_func($migration);
            $current_version = $number;
            $this->_update_version($current_version);
        }
        if( $current_version != $target_version ) 
        {
            $current_version = $target_version;
            $this->_update_version($current_version);
        }

        log_message("debug", "Finished migrating to " . $current_version);
        return $current_version;
    }

    public function latest()
    {
        $migrations = $this->find_migrations();
        if( empty($migrations) ) 
        {
            $this->_error_string = $this->lang->line("migration_none_found");
            return false;
        }

        $last_migration = basename(end($migrations));
        return $this->version($this->_get_migration_number($last_migration));
    }

    public function current()
    {
        return $this->version($this->_migration_version);
    }

    public function error_string()
    {
        return $this->_error_string;
    }

    public function find_migrations()
    {
        $migrations = array(  );
        foreach( glob($this->_migration_path . "*_*.php") as $file ) 
        {
            $name = basename($file, ".php");
            if( preg_match($this->_migration_regex, $name) ) 
            {
                $number = $this->_get_migration_number($name);
                if( isset($migrations[$number]) ) 
                {
                    $this->_error_string = sprintf($this->lang->line("migration_multiple_version"), $number);
                    show_error($this->_error_string);
                }

                $migrations[$number] = $file;
            }

        }
        ksort($migrations);
        return $migrations;
    }

    protected function _get_migration_number($migration)
    {
        return (sscanf($migration, "%[0-9]+", $number) ? $number : "0");
    }

    protected function _get_migration_name($migration)
    {
        $parts = explode("_", $migration);
        array_shift($parts);
        return implode("_", $parts);
    }

    protected function _get_version()
    {
        $row = $this->db->select("version")->get($this->_migration_table)->row();
        return ($row ? $row->version : "0");
    }

    protected function _update_version($migration)
    {
        $this->db->update($this->_migration_table, array( "version" => $migration ));
    }

    public function __get($var)
    {
        return get_instance()->$var;
    }

}


