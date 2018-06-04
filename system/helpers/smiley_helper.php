<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("smiley_js") ) 
{
function smiley_js($alias = "", $field_id = "", $inline = true)
{
    static $do_setup = true;
    $r = "";
    if( $alias !== "" && !is_array($alias) ) 
    {
        $alias = array( $alias => $field_id );
    }

    if( $do_setup === true ) 
    {
        $do_setup = false;
        $m = array(  );
        if( is_array($alias) ) 
        {
            foreach( $alias as $name => $id ) 
            {
                $m[] = "\"" . $name . "\" : \"" . $id . "\"";
            }
        }

        $m = "{" . implode(",", $m) . "}";
        $r .= "\t\t\tvar smiley_map = " . $m . ";\n\n\t\t\tfunction insert_smiley(smiley, field_id) {\n\t\t\t\tvar el = document.getElementById(field_id), newStart;\n\n\t\t\t\tif ( ! el && smiley_map[field_id]) {\n\t\t\t\t\tel = document.getElementById(smiley_map[field_id]);\n\n\t\t\t\t\tif ( ! el)\n\t\t\t\t\t\treturn false;\n\t\t\t\t}\n\n\t\t\t\tel.focus();\n\t\t\t\tsmiley = \" \" + smiley;\n\n\t\t\t\tif ('selectionStart' in el) {\n\t\t\t\t\tnewStart = el.selectionStart + smiley.length;\n\n\t\t\t\t\tel.value = el.value.substr(0, el.selectionStart) +\n\t\t\t\t\t\t\t\t\tsmiley +\n\t\t\t\t\t\t\t\t\tel.value.substr(el.selectionEnd, el.value.length);\n\t\t\t\t\tel.setSelectionRange(newStart, newStart);\n\t\t\t\t}\n\t\t\t\telse if (document.selection) {\n\t\t\t\t\tdocument.selection.createRange().text = smiley;\n\t\t\t\t}\n\t\t\t}";
    }
    else
    {
        if( is_array($alias) ) 
        {
            foreach( $alias as $name => $id ) 
            {
                $r .= "smiley_map[\"" . $name . "\"] = \"" . $id . "\";\n";
            }
        }

    }

    return ($inline ? "<script type=\"text/javascript\" charset=\"utf-8\">/*<![CDATA[ */" . $r . "// ]]></script>" : $r);
}

}

if( !function_exists("get_clickable_smileys") ) 
{
function get_clickable_smileys($image_url, $alias = "")
{
    if( is_array($alias) ) 
    {
        $smileys = $alias;
    }
    else
    {
        if( false === ($smileys = _get_smiley_array()) ) 
        {
            return false;
        }

    }

    $image_url = rtrim($image_url, "/") . "/";
    $used = array(  );
    foreach( $smileys as $key => $val ) 
    {
        if( isset($used[$smileys[$key][0]]) ) 
        {
            continue;
        }

        $link[] = "<a href=\"javascript:void(0);\" onclick=\"insert_smiley('" . $key . "', '" . $alias . "')\"><img src=\"" . $image_url . $smileys[$key][0] . "\" alt=\"" . $smileys[$key][3] . "\" style=\"width: " . $smileys[$key][1] . "; height: " . $smileys[$key][2] . "; border: 0;\" /></a>";
        $used[$smileys[$key][0]] = true;
    }
    return $link;
}

}

if( !function_exists("parse_smileys") ) 
{
function parse_smileys($str = "", $image_url = "", $smileys = NULL)
{
    if( $image_url === "" || !is_array($smileys) && false === ($smileys = _get_smiley_array()) ) 
    {
        return $str;
    }

    $image_url = rtrim($image_url, "/") . "/";
    foreach( $smileys as $key => $val ) 
    {
        $str = str_replace($key, "<img src=\"" . $image_url . $smileys[$key][0] . "\" alt=\"" . $smileys[$key][3] . "\" style=\"width: " . $smileys[$key][1] . "; height: " . $smileys[$key][2] . "; border: 0;\" />", $str);
    }
    return $str;
}

}

if( !function_exists("_get_smiley_array") ) 
{
function _get_smiley_array()
{
    static $_smileys = NULL;
    if( !is_array($_smileys) ) 
    {
        if( file_exists(APPPATH . "config/smileys.php") ) 
        {
            include(APPPATH . "config/smileys.php");
        }

        if( file_exists(APPPATH . "config/" . ENVIRONMENT . "/smileys.php") ) 
        {
            include(APPPATH . "config/" . ENVIRONMENT . "/smileys.php");
        }

        if( empty($smileys) || !is_array($smileys) ) 
        {
            $_smileys = array(  );
            return false;
        }

        $_smileys = $smileys;
    }

    return $_smileys;
}

}


