<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

abstract class CI_Session_driver implements SessionHandlerInterface
{
    protected $_config = NULL;
    protected $_fingerprint = NULL;
    protected $_lock = false;
    protected $_session_id = NULL;
    protected $_success = NULL;
    protected $_failure = NULL;

    public function __construct(&$params)
    {
        $this->_config =& $params;
        if( is_php("7") ) 
        {
            $this->_success = true;
            $this->_failure = false;
        }
        else
        {
            $this->_success = 0;
            $this->_failure = -1;
        }

    }

    protected function _cookie_destroy()
    {
        return setcookie($this->_config["cookie_name"], NULL, 1, $this->_config["cookie_path"], $this->_config["cookie_domain"], $this->_config["cookie_secure"], true);
    }

    protected function _get_lock($session_id)
    {
        $this->_lock = true;
        return true;
    }

    protected function _release_lock()
    {
        if( $this->_lock ) 
        {
            $this->_lock = false;
        }

        return true;
    }

    protected function _fail()
    {
        ini_set("session.save_path", config_item("sess_save_path"));
        return $this->_failure;
    }

}


