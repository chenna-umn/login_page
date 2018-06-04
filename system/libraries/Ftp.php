<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_FTP
{
    public $hostname = "";
    public $username = "";
    public $password = "";
    public $port = 21;
    public $passive = true;
    public $debug = false;
    protected $conn_id = NULL;

    public function __construct($config = array(  ))
    {
        empty($config) or $this->initialize($config);
        log_message("info", "FTP Class Initialized");
    }

    public function initialize($config = array(  ))
    {
        foreach( $config as $key => $val ) 
        {
            if( isset($this->$key) ) 
            {
                $this->$key = $val;
            }

        }
        $this->hostname = preg_replace("|.+?://|", "", $this->hostname);
    }

    public function connect($config = array(  ))
    {
        if( 0 < count($config) ) 
        {
            $this->initialize($config);
        }

        if( false === ($this->conn_id = @ftp_connect($this->hostname, $this->port)) ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_connect");
            }

            return false;
        }

        if( !$this->_login() ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_login");
            }

            return false;
        }

        if( $this->passive === true ) 
        {
            ftp_pasv($this->conn_id, true);
        }

        return true;
    }

    protected function _login()
    {
        return @ftp_login($this->conn_id, $this->username, $this->password);
    }

    protected function _is_conn()
    {
        if( !is_resource($this->conn_id) ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_no_connection");
            }

            return false;
        }

        return true;
    }

    public function changedir($path, $suppress_debug = false)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        $result = @ftp_chdir($this->conn_id, $path);
        if( $result === false ) 
        {
            if( $this->debug === true && $suppress_debug === false ) 
            {
                $this->_error("ftp_unable_to_changedir");
            }

            return false;
        }

        return true;
    }

    public function mkdir($path, $permissions = NULL)
    {
        if( $path === "" || !$this->_is_conn() ) 
        {
            return false;
        }

        $result = @ftp_mkdir($this->conn_id, $path);
        if( $result === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_mkdir");
            }

            return false;
        }

        if( $permissions !== NULL ) 
        {
            $this->chmod($path, (int) $permissions);
        }

        return true;
    }

    public function upload($locpath, $rempath, $mode = "auto", $permissions = NULL)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        if( !file_exists($locpath) ) 
        {
            $this->_error("ftp_no_source_file");
            return false;
        }

        if( $mode === "auto" ) 
        {
            $ext = $this->_getext($locpath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode === "ascii" ? FTP_ASCII : FTP_BINARY);
        $result = @ftp_put($this->conn_id, $rempath, $locpath, $mode);
        if( $result === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_upload");
            }

            return false;
        }

        if( $permissions !== NULL ) 
        {
            $this->chmod($rempath, (int) $permissions);
        }

        return true;
    }

    public function download($rempath, $locpath, $mode = "auto")
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        if( $mode === "auto" ) 
        {
            $ext = $this->_getext($rempath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode === "ascii" ? FTP_ASCII : FTP_BINARY);
        $result = @ftp_get($this->conn_id, $locpath, $rempath, $mode);
        if( $result === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_download");
            }

            return false;
        }

        return true;
    }

    public function rename($old_file, $new_file, $move = false)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        $result = @ftp_rename($this->conn_id, $old_file, $new_file);
        if( $result === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_" . (($move === false ? "rename" : "move")));
            }

            return false;
        }

        return true;
    }

    public function move($old_file, $new_file)
    {
        return $this->rename($old_file, $new_file, true);
    }

    public function delete_file($filepath)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        $result = @ftp_delete($this->conn_id, $filepath);
        if( $result === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_delete");
            }

            return false;
        }

        return true;
    }

    public function delete_dir($filepath)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        $filepath = preg_replace("/(.+?)\\/*\$/", "\\1/", $filepath);
        $list = $this->list_files($filepath);
        if( !empty($list) ) 
        {
            $i = 0;
            for( $c = count($list); $i < $c; $i++ ) 
            {
                if( !preg_match("#/\\.\\.?\$#", $list[$i]) && !@ftp_delete($this->conn_id, $list[$i]) ) 
                {
                    $this->delete_dir($filepath . $list[$i]);
                }

            }
        }

        if( @ftp_rmdir($this->conn_id, $filepath) === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_delete");
            }

            return false;
        }

        return true;
    }

    public function chmod($path, $perm)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        if( @ftp_chmod($this->conn_id, $perm, $path) === false ) 
        {
            if( $this->debug === true ) 
            {
                $this->_error("ftp_unable_to_chmod");
            }

            return false;
        }

        return true;
    }

    public function list_files($path = ".")
    {
        return ($this->_is_conn() ? ftp_nlist($this->conn_id, $path) : false);
    }

    public function mirror($locpath, $rempath)
    {
        if( !$this->_is_conn() ) 
        {
            return false;
        }

        if( $fp = @opendir($locpath) ) 
        {
            if( !$this->changedir($rempath, true) && (!$this->mkdir($rempath) || !$this->changedir($rempath)) ) 
            {
                return false;
            }

            while( false !== ($file = readdir($fp)) ) 
            {
                if( is_dir($locpath . $file) && $file[0] !== "." ) 
                {
                    $this->mirror($locpath . $file . "/", $rempath . $file . "/");
                }
                else
                {
                    if( $file[0] !== "." ) 
                    {
                        $ext = $this->_getext($file);
                        $mode = $this->_settype($ext);
                        $this->upload($locpath . $file, $rempath . $file, $mode);
                    }

                }

            }
            return true;
        }

        return false;
    }

    protected function _getext($filename)
    {
        return (($dot = strrpos($filename, ".")) === false ? "txt" : substr($filename, $dot + 1));
    }

    protected function _settype($ext)
    {
        return (in_array($ext, array( "txt", "text", "php", "phps", "php4", "js", "css", "htm", "html", "phtml", "shtml", "log", "xml" ), true) ? "ascii" : "binary");
    }

    public function close()
    {
        return ($this->_is_conn() ? @ftp_close($this->conn_id) : false);
    }

    protected function _error($line)
    {
        $CI =& get_instance();
        $CI->lang->load("ftp");
        show_error($CI->lang->line($line));
    }

}


