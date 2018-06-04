<?php 

class SpreadsheetReader_ODS implements Iterator, Countable
{
    private $Options = array( "TempDir" => "", "ReturnDateTimeObjects" => false );
    private $ContentPath = "";
    private $Content = false;
    private $Sheets = false;
    private $CurrentRow = NULL;
    private $CurrentSheet = 0;
    private $Index = 0;
    private $TableOpen = false;
    private $RowOpen = false;

    public function __construct($Filepath, array $Options = NULL)
    {
        if( !is_readable($Filepath) ) 
        {
            throw new Exception("SpreadsheetReader_ODS: File not readable (" . $Filepath . ")");
        }

        $this->TempDir = (isset($Options["TempDir"]) && is_writable($Options["TempDir"]) ? $Options["TempDir"] : sys_get_temp_dir());
        $this->TempDir = rtrim($this->TempDir, DIRECTORY_SEPARATOR);
        $this->TempDir = $this->TempDir . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR;
        $Zip = new ZipArchive();
        $Status = $Zip->open($Filepath);
        if( $Status !== true ) 
        {
            throw new Exception("SpreadsheetReader_ODS: File not readable (" . $Filepath . ") (Error " . $Status . ")");
        }

        if( $Zip->locateName("content.xml") !== false ) 
        {
            $Zip->extractTo($this->TempDir, "content.xml");
            $this->ContentPath = $this->TempDir . "content.xml";
        }

        $Zip->close();
        if( $this->ContentPath && is_readable($this->ContentPath) ) 
        {
            $this->Content = new XMLReader();
            $this->Content->open($this->ContentPath);
            $this->Valid = true;
        }

    }

    public function __destruct()
    {
        if( $this->Content && $this->Content instanceof XMLReader ) 
        {
            $this->Content->close();
            unset($this->Content);
        }

        if( file_exists($this->ContentPath) ) 
        {
            @unlink($this->ContentPath);
            unset($this->ContentPath);
        }

    }

    public function Sheets()
    {
        if( $this->Sheets === false ) 
        {
            $this->Sheets = array(  );
            if( $this->Valid ) 
            {
                $this->SheetReader = new XMLReader();
                $this->SheetReader->open($this->ContentPath);
                while( $this->SheetReader->read() ) 
                {
                    if( $this->SheetReader->name == "table:table" ) 
                    {
                        $this->Sheets[] = $this->SheetReader->getAttribute("table:name");
                        $this->SheetReader->next();
                    }

                }
                $this->SheetReader->close();
            }

        }

        return $this->Sheets;
    }

    public function ChangeSheet($Index)
    {
        $Index = (int) $Index;
        $Sheets = $this->Sheets();
        if( isset($Sheets[$Index]) ) 
        {
            $this->CurrentSheet = $Index;
            $this->rewind();
            return true;
        }

        return false;
    }

    public function rewind()
    {
        if( 0 < $this->Index ) 
        {
            $this->Content->close();
            $this->Content->open($this->ContentPath);
            $this->Valid = true;
            $this->TableOpen = false;
            $this->RowOpen = false;
            $this->CurrentRow = NULL;
        }

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
        $this->Index++;
        $this->CurrentRow = array(  );
        if( !$this->TableOpen ) 
        {
            $TableCounter = 0;
            $SkipRead = false;
            while( $this->Valid = $SkipRead || $this->Content->read() ) 
            {
                if( $SkipRead ) 
                {
                    $SkipRead = false;
                }

                if( $this->Content->name == "table:table" && $this->Content->nodeType != XMLReader::END_ELEMENT ) 
                {
                    if( $TableCounter == $this->CurrentSheet ) 
                    {
                        $this->TableOpen = true;
                        break;
                    }

                    $TableCounter++;
                    $this->Content->next();
                    $SkipRead = true;
                }

            }
        }

        if( $this->TableOpen && !$this->RowOpen ) 
        {
            while( $this->Valid = $this->Content->read() ) 
            {
                switch( $this->Content->name ) 
                {
                    case "table:table":
                        $this->TableOpen = false;
                        $this->Content->next("office:document-content");
                        $this->Valid = false;
                        break 2;
                    case "table:table-row":
                        if( $this->Content->nodeType != XMLReader::END_ELEMENT ) 
                        {
                            $this->RowOpen = true;
                            break 2;
                        }

                        break;
                }
            }
        }

        if( $this->RowOpen ) 
        {
            $LastCellContent = "";
            while( $this->Valid = $this->Content->read() ) 
            {
                switch( $this->Content->name ) 
                {
                    case "table:table-cell":
                        if( $this->Content->nodeType == XMLReader::END_ELEMENT || $this->Content->isEmptyElement ) 
                        {
                            if( $this->Content->nodeType == XMLReader::END_ELEMENT ) 
                            {
                                $CellValue = $LastCellContent;
                            }
                            else
                            {
                                if( $this->Content->isEmptyElement ) 
                                {
                                    $LastCellContent = "";
                                    $CellValue = $LastCellContent;
                                }

                            }

                            $this->CurrentRow[] = $LastCellContent;
                            if( $this->Content->getAttribute("table:number-columns-repeated") !== NULL ) 
                            {
                                $RepeatedColumnCount = $this->Content->getAttribute("table:number-columns-repeated");
                                if( 1 < $RepeatedColumnCount ) 
                                {
                                    $this->CurrentRow = array_pad($this->CurrentRow, (count($this->CurrentRow) + $RepeatedColumnCount) - 1, $LastCellContent);
                                }

                            }

                        }
                        else
                        {
                            $LastCellContent = "";
                        }

                    case "text:p":
                        if( $this->Content->nodeType != XMLReader::END_ELEMENT ) 
                        {
                            $LastCellContent = $this->Content->readString();
                        }

                        break;
                    case "table:table-row":
                        $this->RowOpen = false;
                        break 2;
                }
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
        return $this->Valid;
    }

    public function count()
    {
        return $this->Index + 1;
    }

}


