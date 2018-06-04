<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_odbc_forge extends CI_DB_forge
{
    protected $_create_table_if = false;
    protected $_drop_table_if = false;
    protected $_unsigned = false;

    protected function _attr_auto_increment(&$attributes, &$field)
    {
    }

}


