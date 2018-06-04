<?php 

class SpreadsheetReader_CSV implements Iterator, Countable
{
    private $Options = array( "Delimiter" => ";", "Enclosure" => "\"" );
    private $Encoding = "UTF-8";
    private $BOMLength = 0;
    private $Handle = false;
    private $Filepath = "";
    private $Index = 0;
    private $CurrentRow = NULL;

    public function __construct($Filepath, array $Options = NULL)
    {
        $this->Filepath = $Filepath;
        if( !is_readable($Filepath) ) 
        {
            throw new Exception("SpreadsheetReader_CSV: File not readable (" . $Filepath . ")");
        }

        @ini_set("auto_detect_line_endings", true);
        $this->Options = array_merge($this->Options, $Options);
        $this->Handle = fopen($Filepath, "r");
        $BOM16 = bin2hex(fread($this->Handle, 2));
        if( $BOM16 == "fffe" ) 
        {
            $this->Encoding = "UTF-16LE";
            $this->BOMLength = 2;
        }
        else
        {
            if( $BOM16 == "feff" ) 
            {
                $this->Encoding = "UTF-16BE";
                $this->BOMLength = 2;
            }

        }

        if( !$this->BOMLength ) 
        {
            fseek($this->Handle, 0);
            $BOM32 = bin2hex(fread($this->Handle, 4));
            if( $BOM32 == "0000feff" ) 
            {
                $this->Encoding = "UTF-32";
                $this->BOMLength = 4;
            }
            else
            {
                if( $BOM32 == "fffe0000" ) 
                {
                    $this->Encoding = "UTF-32";
                    $this->BOMLength = 4;
                }

            }

        }

        fseek($this->Handle, 0);
        $BOM8 = bin2hex(fread($this->Handle, 3));
        if( $BOM8 == "efbbbf" ) 
        {
            $this->Encoding = "UTF-8";
            $this->BOMLength = 3;
        }

        if( $this->BOMLength ) 
        {
            fseek($this->Handle, $this->BOMLength);
        }

        if( !$this->Options["Delimiter"] ) 
        {
            $Semicolon = ";";
            $Tab = "\t";
            $Comma = ",";
            $SemicolonCount = count(fgetcsv($this->Handle, NULL, $Semicolon));
            fseek($this->Handle, $this->BOMLength);
            $TabCount = count(fgetcsv($this->Handle, NULL, $Tab));
            fseek($this->Handle, $this->BOMLength);
            $CommaCount = count(fgetcsv($this->Handle, NULL, $Comma));
            fseek($this->Handle, $this->BOMLength);
            $Delimiter = $Semicolon;
            if( $SemicolonCount < $TabCount || $SemicolonCount < $CommaCount ) 
            {
                $Delimiter = ($TabCount < $CommaCount ? $Comma : $Tab);
            }

            $this->Options["Delimiter"] = $Delimiter;
        }

    }

    public function Sheets()
    {
        return array( basename($this->Filepath) );
    }

    public function ChangeSheet($Index)
    {
        if( $Index == 0 ) 
        {
            $this->rewind();
            return true;
        }

        return false;
    }

    public function rewind()
    {
        fseek($this->Handle, $this->BOMLength);
        $this->CurrentRow = NULL;
        $this->Index = 0;
    }

    public function current()
    {
        if( $this->Index == 0 && is_null($this->CurrentRow) ) 
        {
            $this->next();
            $this->Index--;
        }

        return $this->CurrentRow;
    }

    public function next()
    {
        $this->CurrentRow = array(  );
        if( ($this->Encoding == "UTF-16LE" || $this->Encoding == "UTF-16BE") && !feof($this->Handle) ) 
        {
            $Char = ord(fgetc($this->Handle));
            if( !$Char || $Char == 10 || $Char == 13 ) 
            {
                continue;
            }

            if( $this->Encoding == "UTF-16LE" ) 
            {
                fseek($this->Handle, ftell($this->Handle) - 1);
            }
            else
            {
                fseek($this->Handle, ftell($this->Handle) - 2);
            }

            break;
        }

        $this->Index++;
        $this->CurrentRow = fgetcsv($this->Handle, NULL, $this->Options["Delimiter"], $this->Options["Enclosure"]);
        if( $this->CurrentRow && $this->Encoding != "ASCII" && $this->Encoding != "UTF-8" ) 
        {
            $Encoding = $this->Encoding;
            foreach( $this->CurrentRow as $Key => $Value ) 
            {
                $this->CurrentRow[$Key] = trim(trim(mb_convert_encoding($Value, "UTF-8", $this->Encoding), $this->Options["Enclosure"]));
            }
        }

        return $this->CurrentRow;
    }

    public function key()
    {
        return $this->Index;
    }

    public function valid()
    {
        return $this->CurrentRow || !feof($this->Handle);
    }

    public function count()
    {
        return $this->Index + 1;
    }

}


