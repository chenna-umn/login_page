<?php 
defined("BASEPATH") or exit( "No direct script access allowed" );
if( !function_exists("form_open") ) 
{
function form_open($action = "", $attributes = array(  ), $hidden = array(  ))
{
    $CI =& get_instance();
    if( !$action ) 
    {
        $action = $CI->config->site_url($CI->uri->uri_string());
    }
    else
    {
        if( strpos($action, "://") === false ) 
        {
            $action = $CI->config->site_url($action);
        }

    }

    $attributes = _attributes_to_string($attributes);
    if( stripos($attributes, "method=") === false ) 
    {
        $attributes .= " method=\"post\"";
    }

    if( stripos($attributes, "accept-charset=") === false ) 
    {
        $attributes .= " accept-charset=\"" . strtolower(config_item("charset")) . "\"";
    }

    $form = "<form action=\"" . $action . "\"" . $attributes . ">\n";
    if( is_array($hidden) ) 
    {
        foreach( $hidden as $name => $value ) 
        {
            $form .= "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . html_escape($value) . "\" />" . "\n";
        }
    }

    if( $CI->config->item("csrf_protection") === true && strpos($action, $CI->config->base_url()) !== false && !stripos($form, "method=\"get\"") ) 
    {
        if( false !== ($noise = $CI->security->get_random_bytes(1)) ) 
        {
            list(, $noise) = unpack("c", $noise);
        }
        else
        {
            $noise = mt_rand(-128, 127);
        }

        $prepend = $append = "";
        if( $noise < 0 ) 
        {
            $prepend = str_repeat(" ", abs($noise));
        }
        else
        {
            if( 0 < $noise ) 
            {
                $append = str_repeat(" ", $noise);
            }

        }

        $form .= sprintf("%s<input type=\"hidden\" name=\"%s\" value=\"%s\" />%s%s", $prepend, $CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash(), $append, "\n");
    }

    return $form;
}

}

if( !function_exists("form_open_multipart") ) 
{
function form_open_multipart($action = "", $attributes = array(  ), $hidden = array(  ))
{
    if( is_string($attributes) ) 
    {
        $attributes .= " enctype=\"multipart/form-data\"";
    }
    else
    {
        $attributes["enctype"] = "multipart/form-data";
    }

    return form_open($action, $attributes, $hidden);
}

}

if( !function_exists("form_hidden") ) 
{
function form_hidden($name, $value = "", $recursing = false)
{
    static $form = NULL;
    if( $recursing === false ) 
    {
        $form = "\n";
    }

    if( is_array($name) ) 
    {
        foreach( $name as $key => $val ) 
        {
            form_hidden($key, $val, true);
        }
        return $form;
    }
    else
    {
        if( !is_array($value) ) 
        {
            $form .= "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . html_escape($value) . "\" />\n";
        }
        else
        {
            foreach( $value as $k => $v ) 
            {
                $k = (is_int($k) ? "" : $k);
                form_hidden($name . "[" . $k . "]", $v, true);
            }
        }

        return $form;
    }

}

}

if( !function_exists("form_input") ) 
{
function form_input($data = "", $value = "", $extra = "")
{
    $defaults = array( "type" => "text", "name" => (is_array($data) ? "" : $data), "value" => $value );
    return "<input " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}

}

if( !function_exists("form_password") ) 
{
function form_password($data = "", $value = "", $extra = "")
{
    is_array($data) or $data["type"] = "password";
    return form_input($data, $value, $extra);
}

}

if( !function_exists("form_upload") ) 
{
function form_upload($data = "", $value = "", $extra = "")
{
    $defaults = array( "type" => "file", "name" => "" );
    is_array($data) or $data["type"] = "file";
    return "<input " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}

}

if( !function_exists("form_textarea") ) 
{
function form_textarea($data = "", $value = "", $extra = "")
{
    $defaults = array( "name" => (is_array($data) ? "" : $data), "cols" => "40", "rows" => "10" );
    if( !is_array($data) || !isset($data["value"]) ) 
    {
        $val = $value;
    }
    else
    {
        $val = $data["value"];
        unset($data["value"]);
    }

    return "<textarea " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . ">" . html_escape($val) . "</textarea>\n";
}

}

if( !function_exists("form_multiselect") ) 
{
function form_multiselect($name = "", $options = array(  ), $selected = array(  ), $extra = "")
{
    $extra = _attributes_to_string($extra);
    if( stripos($extra, "multiple") === false ) 
    {
        $extra .= " multiple=\"multiple\"";
    }

    return form_dropdown($name, $options, $selected, $extra);
}

}

if( !function_exists("form_dropdown") ) 
{
function form_dropdown($data = "", $options = array(  ), $selected = array(  ), $extra = "")
{
    $defaults = array(  );
    if( is_array($data) ) 
    {
        if( isset($data["selected"]) ) 
        {
            $selected = $data["selected"];
            unset($data["selected"]);
        }

        if( isset($data["options"]) ) 
        {
            $options = $data["options"];
            unset($data["options"]);
        }

    }
    else
    {
        $defaults = array( "name" => $data );
    }

    is_array($selected) or is_array($options) or if( empty($selected) ) 
{
    if( is_array($data) ) 
    {
        if( isset($data["name"]) && isset($_POST[$data["name"]]) ) 
        {
            $selected = array( $_POST[$data["name"]] );
        }

    }
    else
    {
        if( isset($_POST[$data]) ) 
        {
            $selected = array( $_POST[$data] );
        }

    }

}

    $extra = _attributes_to_string($extra);
    $multiple = (1 < count($selected) && stripos($extra, "multiple") === false ? " multiple=\"multiple\"" : "");
    $form = "<select " . rtrim(_parse_form_attributes($data, $defaults)) . $extra . $multiple . ">\n";
    foreach( $options as $key => $val ) 
    {
        $key = (string) $key;
        if( is_array($val) ) 
        {
            if( empty($val) ) 
            {
                continue;
            }

            $form .= "<optgroup label=\"" . $key . "\">\n";
            foreach( $val as $optgroup_key => $optgroup_val ) 
            {
                $sel = (in_array($optgroup_key, $selected) ? " selected=\"selected\"" : "");
                $form .= "<option value=\"" . html_escape($optgroup_key) . "\"" . $sel . ">" . (string) $optgroup_val . "</option>\n";
            }
            $form .= "</optgroup>\n";
        }
        else
        {
            $form .= "<option value=\"" . html_escape($key) . "\"" . ((in_array($key, $selected) ? " selected=\"selected\"" : "")) . ">" . (string) $val . "</option>\n";
        }

    }
    return $form . "</select>\n";
}

}

if( !function_exists("form_checkbox") ) 
{
function form_checkbox($data = "", $value = "", $checked = false, $extra = "")
{
    $defaults = array( "type" => "checkbox", "name" => (!is_array($data) ? $data : ""), "value" => $value );
    if( is_array($data) && array_key_exists("checked", $data) ) 
    {
        $checked = $data["checked"];
        if( $checked == false ) 
        {
            unset($data["checked"]);
        }
        else
        {
            $data["checked"] = "checked";
        }

    }

    if( $checked == true ) 
    {
        $defaults["checked"] = "checked";
    }
    else
    {
        unset($defaults["checked"]);
    }

    return "<input " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}

}

if( !function_exists("form_radio") ) 
{
function form_radio($data = "", $value = "", $checked = false, $extra = "")
{
    is_array($data) or $data["type"] = "radio";
    return form_checkbox($data, $value, $checked, $extra);
}

}

if( !function_exists("form_submit") ) 
{
function form_submit($data = "", $value = "", $extra = "")
{
    $defaults = array( "type" => "submit", "name" => (is_array($data) ? "" : $data), "value" => $value );
    return "<input " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}

}

if( !function_exists("form_reset") ) 
{
function form_reset($data = "", $value = "", $extra = "")
{
    $defaults = array( "type" => "reset", "name" => (is_array($data) ? "" : $data), "value" => $value );
    return "<input " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . " />\n";
}

}

if( !function_exists("form_button") ) 
{
function form_button($data = "", $content = "", $extra = "")
{
    $defaults = array( "name" => (is_array($data) ? "" : $data), "type" => "button" );
    if( is_array($data) && isset($data["content"]) ) 
    {
        $content = $data["content"];
        unset($data["content"]);
    }

    return "<button " . _parse_form_attributes($data, $defaults) . _attributes_to_string($extra) . ">" . $content . "</button>\n";
}

}

if( !function_exists("form_label") ) 
{
function form_label($label_text = "", $id = "", $attributes = array(  ))
{
    $label = "<label";
    if( $id !== "" ) 
    {
        $label .= " for=\"" . $id . "\"";
    }

    $label .= _attributes_to_string($attributes);
    return $label . ">" . $label_text . "</label>";
}

}

if( !function_exists("form_fieldset") ) 
{
function form_fieldset($legend_text = "", $attributes = array(  ))
{
    $fieldset = "<fieldset" . _attributes_to_string($attributes) . ">\n";
    if( $legend_text !== "" ) 
    {
        return $fieldset . "<legend>" . $legend_text . "</legend>\n";
    }

    return $fieldset;
}

}

if( !function_exists("form_fieldset_close") ) 
{
function form_fieldset_close($extra = "")
{
    return "</fieldset>" . $extra;
}

}

if( !function_exists("form_close") ) 
{
function form_close($extra = "")
{
    return "</form>" . $extra;
}

}

if( !function_exists("form_prep") ) 
{
function form_prep($str)
{
    return html_escape($str, true);
}

}

if( !function_exists("set_value") ) 
{
function set_value($field, $default = "", $html_escape = true)
{
    $CI =& get_instance();
    $value = (isset($CI->form_validation) && is_object($CI->form_validation) && $CI->form_validation->has_rule($field) ? $CI->form_validation->set_value($field, $default) : $CI->input->post($field, false));
    isset($value) or return ($html_escape ? html_escape($value) : $value);
}

}

if( !function_exists("set_select") ) 
{
function set_select($field, $value = "", $default = false)
{
    $CI =& get_instance();
    if( isset($CI->form_validation) && is_object($CI->form_validation) && $CI->form_validation->has_rule($field) ) 
    {
        return $CI->form_validation->set_select($field, $value, $default);
    }

    if( ($input = $CI->input->post($field, false)) === NULL ) 
    {
        return ($default === true ? " selected=\"selected\"" : "");
    }

    $value = (string) $value;
    if( is_array($input) ) 
    {
        foreach( $input as &$v ) 
        {
            if( $value === $v ) 
            {
                return " selected=\"selected\"";
            }

        }
        return "";
    }
    else
    {
        return ($input === $value ? " selected=\"selected\"" : "");
    }

}

}

if( !function_exists("set_checkbox") ) 
{
function set_checkbox($field, $value = "", $default = false)
{
    $CI =& get_instance();
    if( isset($CI->form_validation) && is_object($CI->form_validation) && $CI->form_validation->has_rule($field) ) 
    {
        return $CI->form_validation->set_checkbox($field, $value, $default);
    }

    $value = (string) $value;
    $input = $CI->input->post($field, false);
    if( is_array($input) ) 
    {
        foreach( $input as &$v ) 
        {
            if( $value === $v ) 
            {
                return " checked=\"checked\"";
            }

        }
        return "";
    }
    else
    {
        if( $CI->input->method() === "post" ) 
        {
            return ($input === $value ? " checked=\"checked\"" : "");
        }

        return ($default === true ? " checked=\"checked\"" : "");
    }

}

}

if( !function_exists("set_radio") ) 
{
function set_radio($field, $value = "", $default = false)
{
    $CI =& get_instance();
    if( isset($CI->form_validation) && is_object($CI->form_validation) && $CI->form_validation->has_rule($field) ) 
    {
        return $CI->form_validation->set_radio($field, $value, $default);
    }

    $value = (string) $value;
    $input = $CI->input->post($field, false);
    if( is_array($input) ) 
    {
        foreach( $input as &$v ) 
        {
            if( $value === $v ) 
            {
                return " checked=\"checked\"";
            }

        }
        return "";
    }
    else
    {
        if( $CI->input->method() === "post" ) 
        {
            return ($input === $value ? " checked=\"checked\"" : "");
        }

        return ($default === true ? " checked=\"checked\"" : "");
    }

}

}

if( !function_exists("form_error") ) 
{
function form_error($field = "", $prefix = "", $suffix = "")
{
    if( false === ($OBJ =& _get_validation_object()) ) 
    {
        return "";
    }

    return $OBJ->error($field, $prefix, $suffix);
}

}

if( !function_exists("validation_errors") ) 
{
function validation_errors($prefix = "", $suffix = "")
{
    if( false === ($OBJ =& _get_validation_object()) ) 
    {
        return "";
    }

    return $OBJ->error_string($prefix, $suffix);
}

}

if( !function_exists("_parse_form_attributes") ) 
{
function _parse_form_attributes($attributes, $default)
{
    if( is_array($attributes) ) 
    {
        foreach( $default as $key => $val ) 
        {
            if( isset($attributes[$key]) ) 
            {
                $default[$key] = $attributes[$key];
                unset($attributes[$key]);
            }

        }
        if( 0 < count($attributes) ) 
        {
            $default = array_merge($default, $attributes);
        }

    }

    $att = "";
    foreach( $default as $key => $val ) 
    {
        if( $key === "value" ) 
        {
            $val = html_escape($val);
        }
        else
        {
            if( $key === "name" && !strlen($default["name"]) ) 
            {
                continue;
            }

        }

        $att .= $key . "=\"" . $val . "\" ";
    }
    return $att;
}

}

if( !function_exists("_attributes_to_string") ) 
{
function _attributes_to_string($attributes)
{
    if( empty($attributes) ) 
    {
        return "";
    }

    if( is_object($attributes) ) 
    {
        $attributes = (array) $attributes;
    }

    if( is_array($attributes) ) 
    {
        $atts = "";
        foreach( $attributes as $key => $val ) 
        {
            $atts .= " " . $key . "=\"" . $val . "\"";
        }
        return $atts;
    }
    else
    {
        if( is_string($attributes) ) 
        {
            return " " . $attributes;
        }

        return false;
    }

}

}

if( !function_exists("_get_validation_object") ) 
{
function &_get_validation_object()
{
    $CI =& get_instance();
    $return = false;
    if( false !== ($object = $CI->load->is_loaded("Form_validation")) ) 
    {
        if( !isset($CI->$object) || !is_object($CI->$object) ) 
        {
            return $return;
        }

        return $CI->$object;
    }

    return $return;
}

}


