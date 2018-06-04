<?php 

class SpreadsheetReader_XLS implements Iterator, Countable
{
    private $Options = array(  );
    private $Handle = false;
    private $Index = 0;
    private $Error = false;
    private $Sheets = false;
    private $SheetIndexes = array(  );
    private $CurrentSheet = 0;
    private $CurrentRow = array(  );
    private $ColumnCount = 0;
    private $RowCount = 0;
    private $EmptyRow = array(  );

    public function __construct($Filepath, array $Options = NULL)
    {
        if( !is_readable($Filepath) ) 
        {
            throw new Exception("SpreadsheetReader_XLS: File not readable (" . $Filepath . ")");
        }

        if( !class_exists("Spreadsheet_Excel_Reader") ) 
        {
            throw new Exception("SpreadsheetReader_XLS: Spreadsheet_Excel_Reader class not available");
        }

        $this->Handle = new Spreadsheet_Excel_Reader($Filepath, false, "UTF-8");
        if( function_exists("mb_convert_encoding") ) 
        {
            $this->Handle->setUTFEncoder("mb");
        }

        if( empty($this->Handle->sheets) ) 
        {
            $this->Error = true;
        }
        else
        {
            $this->ChangeSheet(0);
        }

    }

    public function __destruct()
    {
        unset($this->Handle);
    }

    public function Sheets()
    {
        if( $this->Sheets === false ) 
        {
            $this->Sheets = array(  );
            $this->SheetIndexes = array_keys($this->Handle->sheets);
            foreach( $this->SheetIndexes as $SheetIndex ) 
            {
                $this->Sheets[] = $this->Handle->boundsheets[$SheetIndex]["name"];
            }
        }

        return $this->Sheets;
    }

    public function ChangeSheet($Index)
    {
        $Index = (int) $Index;
        $Sheets = $this->Sheets();
        if( isset($this->Sheets[$Index]) ) 
        {
            $this->rewind();
            $this->CurrentSheet = $this->SheetIndexes[$Index];
            $this->ColumnCount = $this->Handle->sheets[$this->CurrentSheet]["numCols"];
            $this->RowCount = $this->Handle->sheets[$this->CurrentSheet]["numRows"];
            if( !$this->RowCount && count($this->Handle->sheets[$this->CurrentSheet]["cells"]) ) 
            {
                end($this->Handle->sheets[$this->CurrentSheet]["cells"]);
                $this->RowCount = (int) key($this->Handle->sheets[$this->CurrentSheet]["cells"]);
            }

            if( $this->ColumnCount ) 
            {
                $this->EmptyRow = array_fill(1, $this->ColumnCount, "");
            }
            else
            {
                $this->EmptyRow = array(  );
            }

        }

        return false;
    }

    public function __get($Name)
    {
        switch( $Name ) 
        {
            case "Error":
                return $this->Error;
        }
    }

    public function rewind()
    {
        $this->Index = 0;
    }

    public function current()
    {
        if( $this->Index == 0 ) 
        {
            $this->next();
        }

        return $this->CurrentRow;
    }

    public function next()
    {
        $this->Index++;
        if( $this->Error ) 
        {
            return array(  );
        }

        if( isset($this->Handle->sheets[$this->CurrentSheet]["cells"][$this->Index]) ) 
        {
            $this->CurrentRow = $this->Handle->sheets[$this->CurrentSheet]["cells"][$this->Index];
            if( !$this->CurrentRow ) 
            {
                return array(  );
            }

            $this->CurrentRow = $this->CurrentRow + $this->EmptyRow;
            ksort($this->CurrentRow);
            $this->CurrentRow = array_values($this->CurrentRow);
            return $this->CurrentRow;
        }

        $this->CurrentRow = $this->EmptyRow;
        return $this->CurrentRow;
    }

    public function key()
    {
        return $this->Index;
    }

    public function valid()
    {
        if( $this->Error ) 
        {
            return false;
        }

        return $this->Index <= $this->RowCount;
    }

    public function count()
    {
        if( $this->Error ) 
        {
            return 0;
        }

        return $this->RowCount;
    }

}


