<?php 

class SpreadsheetReader implements SeekableIterator, Countable
{
    private $Options = array( "Delimiter" => "", "Enclosure" => "\"" );
    private $Index = 0;
    private $Handle = array(  );
    private $Type = false;

    const TYPE_XLSX = "XLSX";
    const TYPE_XLS = "XLS";
    const TYPE_CSV = "CSV";
    const TYPE_ODS = "ODS";

    public function __construct($Filepath, $OriginalFilename = false, $MimeType = false)
    {
        if( !is_readable($Filepath) ) 
        {
            throw new Exception("SpreadsheetReader: File (" . $Filepath . ") not readable");
        }

        $DefaultTZ = @date_default_timezone_get();
        if( $DefaultTZ ) 
        {
            date_default_timezone_set($DefaultTZ);
        }

        if( !empty($OriginalFilename) && !is_scalar($OriginalFilename) ) 
        {
            throw new Exception("SpreadsheetReader: Original file (2nd parameter) path is not a string or a scalar value.");
        }

        if( !empty($MimeType) && !is_scalar($MimeType) ) 
        {
            throw new Exception("SpreadsheetReader: Mime type (3nd parameter) path is not a string or a scalar value.");
        }

        if( !$OriginalFilename ) 
        {
            $OriginalFilename = $Filepath;
        }

        $Extension = strtolower(pathinfo($OriginalFilename, PATHINFO_EXTENSION));
        switch( $MimeType ) 
        {
            case "text/csv":
            case "text/comma-separated-values":
            case "text/plain":
                $this->Type = self::TYPE_CSV;
                break;
            case "application/vnd.ms-excel":
            case "application/msexcel":
            case "application/x-msexcel":
            case "application/x-ms-excel":
            case "application/vnd.ms-excel":
            case "application/x-excel":
            case "application/x-dos_ms_excel":
            case "application/xls":
            case "application/xlt":
            case "application/x-xls":
                if( in_array($Extension, array( "csv", "tsv", "txt" )) ) 
                {
                    $this->Type = self::TYPE_CSV;
                }
                else
                {
                    $this->Type = self::TYPE_XLS;
                }

                break;
            case "application/vnd.oasis.opendocument.spreadsheet":
            case "application/vnd.oasis.opendocument.spreadsheet-template":
                $this->Type = self::TYPE_ODS;
                break;
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.template":
            case "application/xlsx":
            case "application/xltx":
                $this->Type = self::TYPE_XLSX;
                break;
            case "application/xml":
                break;
        }
        if( !$this->Type ) 
        {
            switch( $Extension ) 
            {
                case "xlsx":
                case "xltx":
                case "xlsm":
                case "xltm":
                    $this->Type = self::TYPE_XLSX;
                    break;
                case "xls":
                case "xlt":
                    $this->Type = self::TYPE_XLS;
                    break;
                case "ods":
                case "odt":
                    $this->Type = self::TYPE_ODS;
                    break;
                default:
                    $this->Type = self::TYPE_CSV;
                    break;
            }
        }

        if( $this->Type == self::TYPE_XLS ) 
        {
            self::Load(self::TYPE_XLS);
            $this->Handle = new SpreadsheetReader_XLS($Filepath);
            if( $this->Handle->Error ) 
            {
                $this->Handle->__destruct();
                if( is_resource($ZipHandle = zip_open($Filepath)) ) 
                {
                    $this->Type = self::TYPE_XLSX;
                    zip_close($ZipHandle);
                }
                else
                {
                    $this->Type = self::TYPE_CSV;
                }

            }

        }

        switch( $this->Type ) 
        {
            case self::TYPE_XLSX:
                self::Load(self::TYPE_XLSX);
                $this->Handle = new SpreadsheetReader_XLSX($Filepath);
                break;
            case self::TYPE_CSV:
                self::Load(self::TYPE_CSV);
                $this->Handle = new SpreadsheetReader_CSV($Filepath, $this->Options);
                break;
            case self::TYPE_XLS:
                break;
            case self::TYPE_ODS:
                self::Load(self::TYPE_ODS);
                $this->Handle = new SpreadsheetReader_ODS($Filepath, $this->Options);
                break;
        }
    }

    public function Sheets()
    {
        return $this->Handle->Sheets();
    }

    public function ChangeSheet($Index)
    {
        return $this->Handle->ChangeSheet($Index);
    }

    private static function Load($Type)
    {
        if( !in_array($Type, array( self::TYPE_XLSX, self::TYPE_XLS, self::TYPE_CSV, self::TYPE_ODS )) ) 
        {
            throw new Exception("SpreadsheetReader: Invalid type (" . $Type . ")");
        }

        if( !class_exists("SpreadsheetReader_" . $Type, false) ) 
        {
            require(dirname(__FILE__) . DIRECTORY_SEPARATOR . "SpreadsheetReader_" . $Type . ".php");
        }

    }

    public function rewind()
    {
        $this->Index = 0;
        if( $this->Handle ) 
        {
            $this->Handle->rewind();
        }

    }

    public function current()
    {
        if( $this->Handle ) 
        {
            return $this->Handle->current();
        }

    }

    public function next()
    {
        if( $this->Handle ) 
        {
            $this->Index++;
            return $this->Handle->next();
        }

    }

    public function key()
    {
        if( $this->Handle ) 
        {
            return $this->Handle->key();
        }

    }

    public function valid()
    {
        if( $this->Handle ) 
        {
            return $this->Handle->valid();
        }

        return false;
    }

    public function count()
    {
        if( $this->Handle ) 
        {
            return $this->Handle->count();
        }

        return 0;
    }

    public function seek($Position)
    {
        if( !$this->Handle ) 
        {
            throw new OutOfBoundsException("SpreadsheetReader: No file opened");
        }

        $CurrentIndex = $this->Handle->key();
        if( $CurrentIndex != $Position ) 
        {
            if( $Position < $CurrentIndex || is_null($CurrentIndex) || $Position == 0 ) 
            {
                $this->rewind();
            }

            while( $this->Handle->valid() && $this->Handle->key() < $Position ) 
            {
                $this->Handle->next();
            }
            if( !$this->Handle->valid() ) 
            {
                throw new OutOfBoundsException("SpreadsheetError: Position " . $Position . " not found");
            }

        }

    }

}


