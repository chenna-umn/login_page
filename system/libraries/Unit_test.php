<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );

class CI_Unit_test
{
    public $active = true;
    public $results = array(  );
    public $strict = false;
    protected $_template = NULL;
    protected $_template_rows = NULL;
    protected $_test_items_visible = array( "test_name", "test_datatype", "res_datatype", "result", "file", "line", "notes" );

    public function __construct()
    {
        log_message("info", "Unit Testing Class Initialized");
    }

    public function set_test_items($items)
    {
        if( !empty($items) && is_array($items) ) 
        {
            $this->_test_items_visible = $items;
        }

    }

    public function run($test, $expected = true, $test_name = "undefined", $notes = "")
    {
        if( $this->active === false ) 
        {
            return false;
        }

        if( in_array($expected, array( "is_object", "is_string", "is_bool", "is_true", "is_false", "is_int", "is_numeric", "is_float", "is_double", "is_array", "is_null", "is_resource" ), true) ) 
        {
            $result = $expected($test);
            $extype = str_replace(array( "true", "false" ), "bool", str_replace("is_", "", $expected));
        }
        else
        {
            $result = ($this->strict === true ? $test === $expected : $test == $expected);
            $extype = gettype($expected);
        }

        $back = $this->_backtrace();
        $report = array( "test_name" => $test_name, "test_datatype" => gettype($test), "res_datatype" => $extype, "result" => ($result === true ? "passed" : "failed"), "file" => $back["file"], "line" => $back["line"], "notes" => $notes );
        $this->results[] = $report;
        return $this->report($this->result(array( $report )));
    }

    public function report($result = array(  ))
    {
        if( count($result) === 0 ) 
        {
            $result = $this->result();
        }

        $CI =& get_instance();
        $CI->load->language("unit_test");
        $this->_parse_template();
        $r = "";
        foreach( $result as $res ) 
        {
            $table = "";
            foreach( $res as $key => $val ) 
            {
                if( $key === $CI->lang->line("ut_result") ) 
                {
                    if( $val === $CI->lang->line("ut_passed") ) 
                    {
                        $val = "<span style=\"color: #0C0;\">" . $val . "</span>";
                    }
                    else
                    {
                        if( $val === $CI->lang->line("ut_failed") ) 
                        {
                            $val = "<span style=\"color: #C00;\">" . $val . "</span>";
                        }

                    }

                }

                $table .= str_replace(array( "{item}", "{result}" ), array( $key, $val ), $this->_template_rows);
            }
            $r .= str_replace("{rows}", $table, $this->_template);
        }
        return $r;
    }

    public function use_strict($state = true)
    {
        $this->strict = (bool) $state;
    }

    public function active($state = true)
    {
        $this->active = (bool) $state;
    }

    public function result($results = array(  ))
    {
        $CI =& get_instance();
        $CI->load->language("unit_test");
        if( count($results) === 0 ) 
        {
            $results = $this->results;
        }

        $retval = array(  );
        foreach( $results as $result ) 
        {
            $temp = array(  );
            foreach( $result as $key => $val ) 
            {
                if( !in_array($key, $this->_test_items_visible) ) 
                {
                    continue;
                }

                if( in_array($key, array( "test_name", "test_datatype", "res_datatype", "result" ), true) && false !== ($line = $CI->lang->line(strtolower("ut_" . $val), false)) ) 
                {
                    $val = $line;
                }

                $temp[$CI->lang->line("ut_" . $key, false)] = $val;
            }
            $retval[] = $temp;
        }
        return $retval;
    }

    public function set_template($template)
    {
        $this->_template = $template;
    }

    protected function _backtrace()
    {
        $back = debug_backtrace();
        return array( "file" => (isset($back[1]["file"]) ? $back[1]["file"] : ""), "line" => (isset($back[1]["line"]) ? $back[1]["line"] : "") );
    }

    protected function _default_template()
    {
        $this->_template = "\n" . "<table style=\"width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;\">{rows}" . "\n</table>";
        $this->_template_rows = "\n\t<tr>\n\t\t" . "<th style=\"text-align: left; border-bottom:1px solid #CCC;\">{item}</th>" . "\n\t\t" . "<td style=\"border-bottom:1px solid #CCC;\">{result}</td>" . "\n\t</tr>";
    }

    protected function _parse_template()
    {
        if( $this->_template_rows !== NULL ) 
        {
            return NULL;
        }

        if( $this->_template === NULL || !preg_match("/\\{rows\\}(.*?)\\{\\/rows\\}/si", $this->_template, $match) ) 
        {
            $this->_default_template();
        }
        else
        {
            $this->_template_rows = $match[1];
            $this->_template = str_replace($match[0], "{rows}", $this->_template);
        }

    }

}

function is_true($test)
{
    return $test === true;
}

function is_false($test)
{
    return $test === false;
}


