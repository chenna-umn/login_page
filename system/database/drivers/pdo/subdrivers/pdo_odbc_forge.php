<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_DB_pdo_odbc_forge extends CI_DB_pdo_forge
{
    protected $_unsigned = false;

    protected function _attr_auto_increment(&$attributes, &$field)
    {
    }

}


