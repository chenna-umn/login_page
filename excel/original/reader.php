<?php 
define("NUM_BIG_BLOCK_DEPOT_BLOCKS_POS", 44);
define("SMALL_BLOCK_DEPOT_BLOCK_POS", 60);
define("ROOT_START_BLOCK_POS", 48);
define("BIG_BLOCK_SIZE", 512);
define("SMALL_BLOCK_SIZE", 64);
define("EXTENSION_BLOCK_POS", 68);
define("NUM_EXTENSION_BLOCK_POS", 72);
define("PROPERTY_STORAGE_BLOCK_SIZE", 128);
define("BIG_BLOCK_DEPOT_BLOCKS_POS", 76);
define("SMALL_BLOCK_THRESHOLD", 4096);
define("SIZE_OF_NAME_POS", 64);
define("TYPE_POS", 66);
define("START_BLOCK_POS", 116);
define("SIZE_POS", 120);
define("IDENTIFIER_OLE", pack("CCCCCCCC", 208, 207, 17, 224, 161, 177, 26, 225));
define("SPREADSHEET_EXCEL_READER_BIFF8", 1536);
define("SPREADSHEET_EXCEL_READER_BIFF7", 1280);
define("SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS", 5);
define("SPREADSHEET_EXCEL_READER_WORKSHEET", 16);
define("SPREADSHEET_EXCEL_READER_TYPE_BOF", 2057);
define("SPREADSHEET_EXCEL_READER_TYPE_EOF", 10);
define("SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET", 133);
define("SPREADSHEET_EXCEL_READER_TYPE_DIMENSION", 512);
define("SPREADSHEET_EXCEL_READER_TYPE_ROW", 520);
define("SPREADSHEET_EXCEL_READER_TYPE_DBCELL", 215);
define("SPREADSHEET_EXCEL_READER_TYPE_FILEPASS", 47);
define("SPREADSHEET_EXCEL_READER_TYPE_NOTE", 28);
define("SPREADSHEET_EXCEL_READER_TYPE_TXO", 438);
define("SPREADSHEET_EXCEL_READER_TYPE_RK", 126);
define("SPREADSHEET_EXCEL_READER_TYPE_RK2", 638);
define("SPREADSHEET_EXCEL_READER_TYPE_MULRK", 189);
define("SPREADSHEET_EXCEL_READER_TYPE_MULBLANK", 190);
define("SPREADSHEET_EXCEL_READER_TYPE_INDEX", 523);
define("SPREADSHEET_EXCEL_READER_TYPE_SST", 252);
define("SPREADSHEET_EXCEL_READER_TYPE_EXTSST", 255);
define("SPREADSHEET_EXCEL_READER_TYPE_CONTINUE", 60);
define("SPREADSHEET_EXCEL_READER_TYPE_LABEL", 516);
define("SPREADSHEET_EXCEL_READER_TYPE_LABELSST", 253);
define("SPREADSHEET_EXCEL_READER_TYPE_NUMBER", 515);
define("SPREADSHEET_EXCEL_READER_TYPE_NAME", 24);
define("SPREADSHEET_EXCEL_READER_TYPE_ARRAY", 545);
define("SPREADSHEET_EXCEL_READER_TYPE_STRING", 519);
define("SPREADSHEET_EXCEL_READER_TYPE_FORMULA", 1030);
define("SPREADSHEET_EXCEL_READER_TYPE_FORMULA2", 6);
define("SPREADSHEET_EXCEL_READER_TYPE_FORMAT", 1054);
define("SPREADSHEET_EXCEL_READER_TYPE_XF", 224);
define("SPREADSHEET_EXCEL_READER_TYPE_BOOLERR", 517);
define("SPREADSHEET_EXCEL_READER_TYPE_FONT", 49);
define("SPREADSHEET_EXCEL_READER_TYPE_PALETTE", 146);
define("SPREADSHEET_EXCEL_READER_TYPE_UNKNOWN", 65535);
define("SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR", 34);
define("SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS", 229);
define("SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS", 25569);
define("SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904", 24107);
define("SPREADSHEET_EXCEL_READER_MSINADAY", 86400);
define("SPREADSHEET_EXCEL_READER_TYPE_HYPER", 440);
define("SPREADSHEET_EXCEL_READER_TYPE_COLINFO", 125);
define("SPREADSHEET_EXCEL_READER_TYPE_DEFCOLWIDTH", 85);
define("SPREADSHEET_EXCEL_READER_TYPE_STANDARDWIDTH", 153);
define("SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT", "%s");

class ExcelReader
{
    private $file_path = NULL;
    private $excel_type = NULL;
    private $uncode = NULL;
    private $excel = NULL;

    public function __construct($excel_path, $encod = "UTF-8")
    {
        $path_parts = pathinfo($excel_path);
        $this->uncode = $encod;
        $ext = $path_parts["extension"];
        if( $ext == "xls" ) 
        {
            $this->excel_type = "2003";
        }
        else
        {
            if( $ext == "xlsx" ) 
            {
                $this->excel_type = "2007";
            }

        }

        $this->file_path = $excel_path;
        $this->getInstant();
    }

    public function getInstant()
    {
        if( is_readable($this->file_path) ) 
        {
            if( $this->excel_type == "2003" ) 
            {
                $this->excel = new Spreadsheet_Excel_Reader($this->file_path, true, "UTF-8");
            }
            else
            {
                if( $this->excel_type == "2007" ) 
                {
                    $this->excel = new SimpleXLSX($this->file_path);
                }
                else
                {
                    throw new Exception(basename($this->file_path) . "not a excel file");
                }

            }

        }
        else
        {
            throw new Exception("file is not readable");
        }

    }

    public function getWorksheetList()
    {
        $List = false;
        if( $this->excel instanceof Spreadsheet_Excel_Reader ) 
        {
            $_obfuscated_30_ = $this->excel->boundsheets;
            foreach( $_obfuscated_30_ as $id => $val ) 
            {
                $List[$val["name"]] = $id;
            }
        }
        else
        {
            if( $this->excel instanceof SimpleXLSX ) 
            {
                $List = $this->excel->worksheetMap;
            }

        }

        return $List;
    }

    public function getWorksheetData($sheetID)
    {
        $fields = array(  );
        $rs = array(  );
        if( !is_int($sheetID) ) 
        {
            $list = $this->getWorksheetList();
            if( isset($list[$sheetID]) ) 
            {
                $sheetID = $list[$sheetID];
            }
            else
            {
                return false;
            }

        }

        if( $this->excel instanceof Spreadsheet_Excel_Reader ) 
        {
            $data = $this->excel->sheets[$sheetID];
            if( true === empty($data["cells"]) ) 
            {
                return false;
            }

            foreach( $data["cells"] as $col => $cell ) 
            {
                if( $col == 1 ) 
                {
                    $fields = $cell;
                }
                else
                {
                    $row = array(  );
                    foreach( $fields as $findex => $fval ) 
                    {
                        $row[$fval] = ($cell[$findex] ? $cell[$findex] : NULL);
                    }
                    $rs[] = $row;
                }

            }
            return $rs;
        }
        else
        {
            if( $this->excel instanceof SimpleXLSX ) 
            {
                $cells = $this->excel->rows($sheetID);
                foreach( $cells as $col => $cell ) 
                {
                    if( $cell[0] != "" ) 
                    {
                        if( $col == 0 ) 
                        {
                            $fields = $cell;
                        }
                        else
                        {
                            $row = array(  );
                            foreach( $fields as $findex => $fval ) 
                            {
                                $row[$fval] = ($cell[$findex] ? $cell[$findex] : NULL);
                            }
                            $rs[] = $row;
                        }

                    }

                }
                return $rs;
            }
            else
            {
                return false;
            }

        }

    }

}


class OLERead
{
    public $data = "";

    public function OLERead()
    {
    }

    public function read($sFileName)
    {
        if( !is_readable($sFileName) ) 
        {
            $this->error = 1;
            return false;
        }

        $this->data = @file_get_contents($sFileName);
        if( !$this->data ) 
        {
            $this->error = 2;
            return false;
        }

        if( substr($this->data, 0, 8) != IDENTIFIER_OLE ) 
        {
            $this->error = 3;
            return false;
        }

        $this->numBigBlockDepotBlocks = getint4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
        $this->sbdStartBlock = getint4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
        $this->rootStartBlock = getint4d($this->data, ROOT_START_BLOCK_POS);
        $this->extensionBlock = getint4d($this->data, EXTENSION_BLOCK_POS);
        $this->numExtensionBlocks = getint4d($this->data, NUM_EXTENSION_BLOCK_POS);
        $bigBlockDepotBlocks = array(  );
        $pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
        $bbdBlocks = $this->numBigBlockDepotBlocks;
        if( $this->numExtensionBlocks != 0 ) 
        {
            $bbdBlocks = (BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS) / 4;
        }

        for( $i = 0; $i < $bbdBlocks; $i++ ) 
        {
            $bigBlockDepotBlocks[$i] = getint4d($this->data, $pos);
            $pos += 4;
        }
        for( $j = 0; $j < $this->numExtensionBlocks; $j++ ) 
        {
            $pos = ($this->extensionBlock + 1) * BIG_BLOCK_SIZE;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, BIG_BLOCK_SIZE / 4 - 1);
            for( $i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++ ) 
            {
                $bigBlockDepotBlocks[$i] = getint4d($this->data, $pos);
                $pos += 4;
            }
            $bbdBlocks += $blocksToRead;
            if( $bbdBlocks < $this->numBigBlockDepotBlocks ) 
            {
                $this->extensionBlock = getint4d($this->data, $pos);
            }

        }
        $pos = 0;
        $index = 0;
        $this->bigBlockChain = array(  );
        for( $i = 0; $i < $this->numBigBlockDepotBlocks; $i++ ) 
        {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * BIG_BLOCK_SIZE;
            for( $j = 0; $j < BIG_BLOCK_SIZE / 4; $j++ ) 
            {
                $this->bigBlockChain[$index] = getint4d($this->data, $pos);
                $pos += 4;
                $index++;
            }
        }
        $pos = 0;
        $index = 0;
        $sbdBlock = $this->sbdStartBlock;
        $this->smallBlockChain = array(  );
        while( $sbdBlock != -2 ) 
        {
            $pos = ($sbdBlock + 1) * BIG_BLOCK_SIZE;
            for( $j = 0; $j < BIG_BLOCK_SIZE / 4; $j++ ) 
            {
                $this->smallBlockChain[$index] = getint4d($this->data, $pos);
                $pos += 4;
                $index++;
            }
            $sbdBlock = $this->bigBlockChain[$sbdBlock];
        }
        $block = $this->rootStartBlock;
        $pos = 0;
        $this->entry = $this->__readData($block);
        $this->__readPropertySets();
    }

    public function __readData($bl)
    {
        $block = $bl;
        $pos = 0;
        $data = "";
        while( $block != -2 ) 
        {
            $pos = ($block + 1) * BIG_BLOCK_SIZE;
            $data = $data . substr($this->data, $pos, BIG_BLOCK_SIZE);
            $block = $this->bigBlockChain[$block];
        }
        return $data;
    }

    public function __readPropertySets()
    {
        $offset = 0;
        while( $offset < strlen($this->entry) ) 
        {
            $d = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
            $nameSize = ord($d[SIZE_OF_NAME_POS]) | ord($d[SIZE_OF_NAME_POS + 1]) << 8;
            $type = ord($d[TYPE_POS]);
            $startBlock = getint4d($d, START_BLOCK_POS);
            $size = getint4d($d, SIZE_POS);
            $name = "";
            for( $i = 0; $i < $nameSize; $i++ ) 
            {
                $name .= $d[$i];
            }
            $name = str_replace("", "", $name);
            $this->props[] = array( "name" => $name, "type" => $type, "startBlock" => $startBlock, "size" => $size );
            if( strtolower($name) == "workbook" || strtolower($name) == "book" ) 
            {
                $this->wrkbook = count($this->props) - 1;
            }

            if( $name == "Root Entry" ) 
            {
                $this->rootentry = count($this->props) - 1;
            }

            $offset += PROPERTY_STORAGE_BLOCK_SIZE;
        }
    }

    public function getWorkBook()
    {
        if( $this->props[$this->wrkbook]["size"] < SMALL_BLOCK_THRESHOLD ) 
        {
            $rootdata = $this->__readData($this->props[$this->rootentry]["startBlock"]);
            $streamData = "";
            $block = $this->props[$this->wrkbook]["startBlock"];
            $pos = 0;
            while( $block != -2 ) 
            {
                $pos = $block * SMALL_BLOCK_SIZE;
                $streamData .= substr($rootdata, $pos, SMALL_BLOCK_SIZE);
                $block = $this->smallBlockChain[$block];
            }
            return $streamData;
        }

        $numBlocks = $this->props[$this->wrkbook]["size"] / BIG_BLOCK_SIZE;
        if( $this->props[$this->wrkbook]["size"] % BIG_BLOCK_SIZE != 0 ) 
        {
            $numBlocks++;
        }

        if( $numBlocks == 0 ) 
        {
            return "";
        }

        $streamData = "";
        $block = $this->props[$this->wrkbook]["startBlock"];
        $pos = 0;
        while( $block != -2 ) 
        {
            $pos = ($block + 1) * BIG_BLOCK_SIZE;
            $streamData .= substr($this->data, $pos, BIG_BLOCK_SIZE);
            $block = $this->bigBlockChain[$block];
        }
        return $streamData;
    }

}


class Spreadsheet_Excel_Reader
{
    public $colnames = array(  );
    public $colindexes = array(  );
    public $standardColWidth = 0;
    public $defaultColWidth = 0;
    public $boundsheets = array(  );
    public $formatRecords = array(  );
    public $fontRecords = array(  );
    public $xfRecords = array(  );
    public $colInfo = array(  );
    public $rowInfo = array(  );
    public $sst = array(  );
    public $sheets = array(  );
    public $data = NULL;
    public $_ole = NULL;
    public $_defaultEncoding = "UTF-8";
    public $_defaultFormat = SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT;
    public $_columnsFormat = array(  );
    public $_rowoffset = 1;
    public $_coloffset = 1;
    public $dateFormats = array( "14" => "m/d/Y", "15" => "M-d-Y", "16" => "d-M", "17" => "M-Y", "18" => "h:i a", "19" => "h:i:s a", "20" => "H:i", "21" => "H:i:s", "22" => "d/m/Y H:i", "45" => "i:s", "46" => "H:i:s", "47" => "i:s.S" );
    public $numberFormats = array( "1" => "0", "2" => "0.00", "3" => "#,##0", "4" => "#,##0.00", "5" => "\$#,##0;(\$#,##0)", "6" => "\$#,##0;[Red](\$#,##0)", "7" => "\$#,##0.00;(\$#,##0.00)", "8" => "\$#,##0.00;[Red](\$#,##0.00)", "9" => "0%", "10" => "0.00%", "11" => "0.00E+00", "37" => "#,##0;(#,##0)", "38" => "#,##0;[Red](#,##0)", "39" => "#,##0.00;(#,##0.00)", "40" => "#,##0.00;[Red](#,##0.00)", "41" => "#,##0;(#,##0)", "42" => "\$#,##0;(\$#,##0)", "43" => "#,##0.00;(#,##0.00)", "44" => "\$#,##0.00;(\$#,##0.00)", "48" => "##0.0E+0" );
    public $colors = array( "#000000", "#FFFFFF", "#FF0000", "#00FF00", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#000000", "#FFFFFF", "#FF0000", "#00FF00", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#800000", "#008000", "#000080", "#808000", "#800080", "#008080", "#C0C0C0", "#808080", "#9999FF", "#993366", "#FFFFCC", "#CCFFFF", "#660066", "#FF8080", "#0066CC", "#CCCCFF", "#000080", "#FF00FF", "#FFFF00", "#00FFFF", "#800080", "#800000", "#008080", "#0000FF", "#00CCFF", "#CCFFFF", "#CCFFCC", "#FFFF99", "#99CCFF", "#FF99CC", "#CC99FF", "#FFCC99", "#3366FF", "#33CCCC", "#99CC00", "#FFCC00", "#FF9900", "#FF6600", "#666699", "#969696", "#003366", "#339966", "#003300", "#333300", "#993300", "#993366", "#333399", "#333333", "#000000", "#FFFFFF", "67" => "#000000", "77" => "#000000", "78" => "#FFFFFF", "79" => "#000000", "80" => "#FFFFFF", "81" => "#000000", "32767" => "#000000" );
    public $lineStyles = array( "", "Thin", "Medium", "Dashed", "Dotted", "Thick", "Double", "Hair", "Medium dashed", "Thin dash-dotted", "Medium dash-dotted", "Thin dash-dot-dotted", "Medium dash-dot-dotted", "Slanted medium dash-dotted" );
    public $lineStylesCss = array( "Thin" => "1px solid", "Medium" => "2px solid", "Dashed" => "1px dashed", "Dotted" => "1px dotted", "Thick" => "3px solid", "Double" => "double", "Hair" => "1px solid", "Medium dashed" => "2px dashed", "Thin dash-dotted" => "1px dashed", "Medium dash-dotted" => "2px dashed", "Thin dash-dot-dotted" => "1px dashed", "Medium dash-dot-dotted" => "2px dashed", "Slanted medium dash-dotte" => "2px dashed" );

    public function myHex($d)
    {
        if( $d < 16 ) 
        {
            return "0" . dechex($d);
        }

        return dechex($d);
    }

    public function dumpHexData($data, $pos, $length)
    {
        $info = "";
        for( $i = 0; $i <= $length; $i++ ) 
        {
            $info .= (($i == 0 ? "" : " ")) . $this->myHex(ord($data[$pos + $i])) . ((31 < ord($data[$pos + $i]) ? "[" . $data[$pos + $i] . "]" : ""));
        }
        return $info;
    }

    public function getCol($col)
    {
        if( is_string($col) ) 
        {
            $col = strtolower($col);
            if( array_key_exists($col, $this->colnames) ) 
            {
                $col = $this->colnames[$col];
            }

        }

        return $col;
    }

    public function val($row, $col, $sheet = 0)
    {
        $col = $this->getCol($col);
        if( array_key_exists($row, $this->sheets[$sheet]["cells"]) && array_key_exists($col, $this->sheets[$sheet]["cells"][$row]) ) 
        {
            return $this->sheets[$sheet]["cells"][$row][$col];
        }

        return "";
    }

    public function value($row, $col, $sheet = 0)
    {
        return $this->val($row, $col, $sheet);
    }

    public function info($row, $col, $type = "", $sheet = 0)
    {
        $col = $this->getCol($col);
        if( array_key_exists("cellsInfo", $this->sheets[$sheet]) && array_key_exists($row, $this->sheets[$sheet]["cellsInfo"]) && array_key_exists($col, $this->sheets[$sheet]["cellsInfo"][$row]) && array_key_exists($type, $this->sheets[$sheet]["cellsInfo"][$row][$col]) ) 
        {
            return $this->sheets[$sheet]["cellsInfo"][$row][$col][$type];
        }

        return "";
    }

    public function type($row, $col, $sheet = 0)
    {
        return $this->info($row, $col, "type", $sheet);
    }

    public function raw($row, $col, $sheet = 0)
    {
        return $this->info($row, $col, "raw", $sheet);
    }

    public function rowspan($row, $col, $sheet = 0)
    {
        $val = $this->info($row, $col, "rowspan", $sheet);
        if( $val == "" ) 
        {
            return 1;
        }

        return $val;
    }

    public function colspan($row, $col, $sheet = 0)
    {
        $val = $this->info($row, $col, "colspan", $sheet);
        if( $val == "" ) 
        {
            return 1;
        }

        return $val;
    }

    public function hyperlink($row, $col, $sheet = 0)
    {
        $link = $this->sheets[$sheet]["cellsInfo"][$row][$col]["hyperlink"];
        if( $link ) 
        {
            return $link["link"];
        }

        return "";
    }

    public function rowcount($sheet = 0)
    {
        return $this->sheets[$sheet]["numRows"];
    }

    public function colcount($sheet = 0)
    {
        return $this->sheets[$sheet]["numCols"];
    }

    public function colwidth($col, $sheet = 0)
    {
        return $this->colInfo[$sheet][$col]["width"] / 9142 * 200;
    }

    public function colhidden($col, $sheet = 0)
    {
        return $this->colInfo[$sheet][$col]["hidden"];
    }

    public function rowheight($row, $sheet = 0)
    {
        return $this->rowInfo[$sheet][$row]["height"];
    }

    public function rowhidden($row, $sheet = 0)
    {
        return $this->rowInfo[$sheet][$row]["hidden"];
    }

    public function style($row, $col, $sheet = 0, $properties = "")
    {
        $css = "";
        $font = $this->font($row, $col, $sheet);
        if( $font != "" ) 
        {
            $css .= "font-family:" . $font . ";";
        }

        $align = $this->align($row, $col, $sheet);
        if( $align != "" ) 
        {
            $css .= "text-align:" . $align . ";";
        }

        $height = $this->height($row, $col, $sheet);
        if( $height != "" ) 
        {
            $css .= "font-size:" . $height . "px;";
        }

        $bgcolor = $this->bgColor($row, $col, $sheet);
        if( $bgcolor != "" ) 
        {
            $bgcolor = $this->colors[$bgcolor];
            $css .= "background-color:" . $bgcolor . ";";
        }

        $color = $this->color($row, $col, $sheet);
        if( $color != "" ) 
        {
            $css .= "color:" . $color . ";";
        }

        $bold = $this->bold($row, $col, $sheet);
        if( $bold ) 
        {
            $css .= "font-weight:bold;";
        }

        $italic = $this->italic($row, $col, $sheet);
        if( $italic ) 
        {
            $css .= "font-style:italic;";
        }

        $underline = $this->underline($row, $col, $sheet);
        if( $underline ) 
        {
            $css .= "text-decoration:underline;";
        }

        $bLeft = $this->borderLeft($row, $col, $sheet);
        $bRight = $this->borderRight($row, $col, $sheet);
        $bTop = $this->borderTop($row, $col, $sheet);
        $bBottom = $this->borderBottom($row, $col, $sheet);
        $bLeftCol = $this->borderLeftColor($row, $col, $sheet);
        $bRightCol = $this->borderRightColor($row, $col, $sheet);
        $bTopCol = $this->borderTopColor($row, $col, $sheet);
        $bBottomCol = $this->borderBottomColor($row, $col, $sheet);
        if( $bLeft != "" && $bLeft == $bRight && $bRight == $bTop && $bTop == $bBottom ) 
        {
            $css .= "border:" . $this->lineStylesCss[$bLeft] . ";";
        }
        else
        {
            if( $bLeft != "" ) 
            {
                $css .= "border-left:" . $this->lineStylesCss[$bLeft] . ";";
            }

            if( $bRight != "" ) 
            {
                $css .= "border-right:" . $this->lineStylesCss[$bRight] . ";";
            }

            if( $bTop != "" ) 
            {
                $css .= "border-top:" . $this->lineStylesCss[$bTop] . ";";
            }

            if( $bBottom != "" ) 
            {
                $css .= "border-bottom:" . $this->lineStylesCss[$bBottom] . ";";
            }

        }

        if( $bLeft != "" && $bLeftCol != "" ) 
        {
            $css .= "border-left-color:" . $bLeftCol . ";";
        }

        if( $bRight != "" && $bRightCol != "" ) 
        {
            $css .= "border-right-color:" . $bRightCol . ";";
        }

        if( $bTop != "" && $bTopCol != "" ) 
        {
            $css .= "border-top-color:" . $bTopCol . ";";
        }

        if( $bBottom != "" && $bBottomCol != "" ) 
        {
            $css .= "border-bottom-color:" . $bBottomCol . ";";
        }

        return $css;
    }

    public function format($row, $col, $sheet = 0)
    {
        return $this->info($row, $col, "format", $sheet);
    }

    public function formatIndex($row, $col, $sheet = 0)
    {
        return $this->info($row, $col, "formatIndex", $sheet);
    }

    public function formatColor($row, $col, $sheet = 0)
    {
        return $this->info($row, $col, "formatColor", $sheet);
    }

    public function xfRecord($row, $col, $sheet = 0)
    {
        $xfIndex = $this->info($row, $col, "xfIndex", $sheet);
        if( $xfIndex != "" ) 
        {
            return $this->xfRecords[$xfIndex];
        }

    }

    public function xfProperty($row, $col, $sheet, $prop)
    {
        $xfRecord = $this->xfRecord($row, $col, $sheet);
        if( $xfRecord != NULL ) 
        {
            return $xfRecord[$prop];
        }

        return "";
    }

    public function align($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "align");
    }

    public function bgColor($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "bgColor");
    }

    public function borderLeft($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "borderLeft");
    }

    public function borderRight($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "borderRight");
    }

    public function borderTop($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "borderTop");
    }

    public function borderBottom($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "borderBottom");
    }

    public function borderLeftColor($row, $col, $sheet = 0)
    {
        return $this->colors[$this->xfProperty($row, $col, $sheet, "borderLeftColor")];
    }

    public function borderRightColor($row, $col, $sheet = 0)
    {
        return $this->colors[$this->xfProperty($row, $col, $sheet, "borderRightColor")];
    }

    public function borderTopColor($row, $col, $sheet = 0)
    {
        return $this->colors[$this->xfProperty($row, $col, $sheet, "borderTopColor")];
    }

    public function borderBottomColor($row, $col, $sheet = 0)
    {
        return $this->colors[$this->xfProperty($row, $col, $sheet, "borderBottomColor")];
    }

    public function fontRecord($row, $col, $sheet = 0)
    {
        $xfRecord = $this->xfRecord($row, $col, $sheet);
        if( $xfRecord != NULL ) 
        {
            $font = $xfRecord["fontIndex"];
            if( $font != NULL ) 
            {
                return $this->fontRecords[$font];
            }

        }

    }

    public function fontProperty($row, $col, $sheet = 0, $prop)
    {
        $font = $this->fontRecord($row, $col, $sheet);
        if( $font != NULL ) 
        {
            return $font[$prop];
        }

        return false;
    }

    public function fontIndex($row, $col, $sheet = 0)
    {
        return $this->xfProperty($row, $col, $sheet, "fontIndex");
    }

    public function color($row, $col, $sheet = 0)
    {
        $formatColor = $this->formatColor($row, $col, $sheet);
        if( $formatColor != "" ) 
        {
            return $formatColor;
        }

        $ci = $this->fontProperty($row, $col, $sheet, "color");
        return $this->rawColor($ci);
    }

    public function rawColor($ci)
    {
        if( $ci != 32767 && $ci != "" ) 
        {
            return $this->colors[$ci];
        }

        return "";
    }

    public function bold($row, $col, $sheet = 0)
    {
        return $this->fontProperty($row, $col, $sheet, "bold");
    }

    public function italic($row, $col, $sheet = 0)
    {
        return $this->fontProperty($row, $col, $sheet, "italic");
    }

    public function underline($row, $col, $sheet = 0)
    {
        return $this->fontProperty($row, $col, $sheet, "under");
    }

    public function height($row, $col, $sheet = 0)
    {
        return $this->fontProperty($row, $col, $sheet, "height");
    }

    public function font($row, $col, $sheet = 0)
    {
        return $this->fontProperty($row, $col, $sheet, "font");
    }

    public function dump($row_numbers = false, $col_letters = false, $sheet = 0, $table_class = "excel")
    {
        $out = "<table class=\"" . $table_class . "\" cellspacing=0>";
        if( $col_letters ) 
        {
            $out .= "<thead>\n\t<tr>";
            if( $row_numbers ) 
            {
                $out .= "\n\t\t<th>&nbsp</th>";
            }

            for( $i = 1; $i <= $this->colcount($sheet); $i++ ) 
            {
                $style = "width:" . $this->colwidth($i, $sheet) * 1 . "px;";
                if( $this->colhidden($i, $sheet) ) 
                {
                    $style .= "display:none;";
                }

                $out .= "\n\t\t<th style=\"" . $style . "\">" . strtoupper($this->colindexes[$i]) . "</th>";
            }
            $out .= "</tr></thead>\n";
        }

        $out .= "<tbody>\n";
        for( $row = 1; $row <= $this->rowcount($sheet); $row++ ) 
        {
            $rowheight = $this->rowheight($row, $sheet);
            $style = "height:" . $rowheight * 4 / 3 . "px;";
            if( $this->rowhidden($row, $sheet) ) 
            {
                $style .= "display:none;";
            }

            $out .= "\n\t<tr style=\"" . $style . "\">";
            if( $row_numbers ) 
            {
                $out .= "\n\t\t<th>" . $row . "</th>";
            }

            for( $col = 1; $col <= $this->colcount($sheet); $col++ ) 
            {
                $rowspan = $this->rowspan($row, $col, $sheet);
                $colspan = $this->colspan($row, $col, $sheet);
                for( $i = 0; $i < $rowspan; $i++ ) 
                {
                    for( $j = 0; $j < $colspan; $j++ ) 
                    {
                        if( 0 < $i || 0 < $j ) 
                        {
                            $this->sheets[$sheet]["cellsInfo"][$row + $i][$col + $j]["dontprint"] = 1;
                        }

                    }
                }
                if( !$this->sheets[$sheet]["cellsInfo"][$row][$col]["dontprint"] ) 
                {
                    $style = $this->style($row, $col, $sheet);
                    if( $this->colhidden($col, $sheet) ) 
                    {
                        $style .= "display:none;";
                    }

                    $out .= "\n\t\t<td style=\"" . $style . "\"" . ((1 < $colspan ? " colspan=" . $colspan : "")) . ((1 < $rowspan ? " rowspan=" . $rowspan : "")) . ">";
                    $val = $this->val($row, $col, $sheet);
                    if( $val == "" ) 
                    {
                        $val = "&nbsp;";
                    }
                    else
                    {
                        $val = htmlentities($val, ENT_NOQUOTES, $this->_defaultEncoding);
                        $link = $this->hyperlink($row, $col, $sheet);
                        if( $link != "" ) 
                        {
                            $val = "<a href=\"" . $link . "\">" . $val . "</a>";
                        }

                    }

                    $out .= "<nobr>" . nl2br($val) . "</nobr>";
                    $out .= "</td>";
                }

            }
            $out .= "</tr>\n";
        }
        $out .= "</tbody></table>";
        return $out;
    }

    public function read16bitstring($data, $start)
    {
        for( $len = 0; 0 < ord($data[$start + $len]) + ord($data[$start + $len + 1]); $len++ ) 
        {
        }
        return substr($data, $start, $len);
    }

    public function _format_value($format, $num, $f)
    {
        if( !$f && $format == "%s" || $f == 49 || $format == "GENERAL" ) 
        {
            return array( "string" => $num, "formatColor" => NULL );
        }

        $parts = split(";", $format);
        $pattern = $parts[0];
        if( 2 < count($parts) && $num == 0 ) 
        {
            $pattern = $parts[2];
        }

        if( 1 < count($parts) && $num < 0 ) 
        {
            $pattern = $parts[1];
            $num = abs($num);
        }

        $color = "";
        $matches = array(  );
        $color_regex = "/^\\[(BLACK|BLUE|CYAN|GREEN|MAGENTA|RED|WHITE|YELLOW)\\]/i";
        if( preg_match($color_regex, $pattern, $matches) ) 
        {
            $color = strtolower($matches[1]);
            $pattern = preg_replace($color_regex, "", $pattern);
        }

        $pattern = preg_replace("/_./", "", $pattern);
        $pattern = preg_replace("/\\\\/", "", $pattern);
        $pattern = preg_replace("/\"/", "", $pattern);
        $pattern = preg_replace("/\\#/", "0", $pattern);
        $has_commas = preg_match("/,/", $pattern);
        if( $has_commas ) 
        {
            $pattern = preg_replace("/,/", "", $pattern);
        }

        if( preg_match("/\\d(\\%)([^\\%]|\$)/", $pattern, $matches) ) 
        {
            $num = $num * 100;
            $pattern = preg_replace("/(\\d)(\\%)([^\\%]|\$)/", "\$1%\$3", $pattern);
        }

        $number_regex = "/(\\d+)(\\.?)(\\d*)/";
        if( preg_match($number_regex, $pattern, $matches) ) 
        {
            list(, $left, $dec, $right) = $matches;
            if( $has_commas ) 
            {
                $formatted = number_format($num, strlen($right));
            }
            else
            {
                $sprintf_pattern = "%1." . strlen($right) . "f";
                $formatted = sprintf($sprintf_pattern, $num);
            }

            $pattern = preg_replace($number_regex, $formatted, $pattern);
        }

        return array( "string" => $pattern, "formatColor" => $color );
    }

    public function Spreadsheet_Excel_Reader($file = "", $store_extended_info = true, $outputEncoding = "")
    {
        $this->_ole = new OLERead();
        $this->setUTFEncoder("iconv");
        if( $outputEncoding != "" ) 
        {
            $this->setOutputEncoding($outputEncoding);
        }

        for( $i = 1; $i < 245; $i++ ) 
        {
            $name = strtolower(((1 <= ($i - 1) / 26 ? chr(($i - 1) / 26 + 64) : "")) . chr(($i - 1) % 26 + 65));
            $this->colnames[$name] = $i;
            $this->colindexes[$i] = $name;
        }
        $this->store_extended_info = $store_extended_info;
        if( $file != "" ) 
        {
            $this->read($file);
        }

    }

    public function setOutputEncoding($encoding)
    {
        $this->_defaultEncoding = $encoding;
    }

    public function setUTFEncoder($encoder = "iconv")
    {
        $this->_encoderFunction = "";
        if( $encoder == "iconv" ) 
        {
            $this->_encoderFunction = (function_exists("iconv") ? "iconv" : "");
        }
        else
        {
            if( $encoder == "mb" ) 
            {
                $this->_encoderFunction = (function_exists("mb_convert_encoding") ? "mb_convert_encoding" : "");
            }

        }

    }

    public function setRowColOffset($iOffset)
    {
        $this->_rowoffset = $iOffset;
        $this->_coloffset = $iOffset;
    }

    public function setDefaultFormat($sFormat)
    {
        $this->_defaultFormat = $sFormat;
    }

    public function setColumnFormat($column, $sFormat)
    {
        $this->_columnsFormat[$column] = $sFormat;
    }

    public function read($sFileName)
    {
        $res = $this->_ole->read($sFileName);
        if( $res === false ) 
        {
            if( $this->_ole->error == 1 ) 
            {
                throw new Exception("The filename " . $sFileName . " is not readable");
            }

            if( $this->_ole->error == 1 ) 
            {
                throw new Exception("The filename " . $sFileName . " can't be opened");
            }

            if( $this->_ole->error == 3 ) 
            {
                throw new Exception("The file format " . $sFileName . " is invaid");
            }

        }

        $this->data = $this->_ole->getWorkBook();
        $this->_parse();
    }

    public function _parse()
    {
        $pos = 0;
        $data = $this->data;
        $code = v($data, $pos);
        $length = v($data, $pos + 2);
        $version = v($data, $pos + 4);
        $substreamType = v($data, $pos + 6);
        $this->version = $version;
        if( $version != SPREADSHEET_EXCEL_READER_BIFF8 && $version != SPREADSHEET_EXCEL_READER_BIFF7 ) 
        {
            return false;
        }

        if( $substreamType != SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS ) 
        {
            return false;
        }

        $pos += $length + 4;
        $code = v($data, $pos);
        $length = v($data, $pos + 2);
        while( $code != SPREADSHEET_EXCEL_READER_TYPE_EOF ) 
        {
            switch( $code ) 
            {
                case SPREADSHEET_EXCEL_READER_TYPE_SST:
                    $spos = $pos + 4;
                    $limitpos = $spos + $length;
                    $uniqueStrings = $this->_GetInt4d($data, $spos + 4);
                    $spos += 8;
                    for( $i = 0; $i < $uniqueStrings; $i++ ) 
                    {
                        if( $spos == $limitpos ) 
                        {
                            $opcode = v($data, $spos);
                            $conlength = v($data, $spos + 2);
                            if( $opcode != 60 ) 
                            {
                                return -1;
                            }

                            $spos += 4;
                            $limitpos = $spos + $conlength;
                        }

                        $numChars = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                        $spos += 2;
                        $optionFlags = ord($data[$spos]);
                        $spos++;
                        $asciiEncoding = ($optionFlags & 1) == 0;
                        $extendedString = ($optionFlags & 4) != 0;
                        $richString = ($optionFlags & 8) != 0;
                        if( $richString ) 
                        {
                            $formattingRuns = v($data, $spos);
                            $spos += 2;
                        }

                        if( $extendedString ) 
                        {
                            $extendedRunLength = $this->_GetInt4d($data, $spos);
                            $spos += 4;
                        }

                        $len = ($asciiEncoding ? $numChars : $numChars * 2);
                        if( $spos + $len < $limitpos ) 
                        {
                            $retstr = substr($data, $spos, $len);
                            $spos += $len;
                        }
                        else
                        {
                            $retstr = substr($data, $spos, $limitpos - $spos);
                            $bytesRead = $limitpos - $spos;
                            $charsLeft = $numChars - (($asciiEncoding ? $bytesRead : $bytesRead / 2));
                            $spos = $limitpos;
                            while( 0 < $charsLeft ) 
                            {
                                $opcode = v($data, $spos);
                                $conlength = v($data, $spos + 2);
                                if( $opcode != 60 ) 
                                {
                                    return -1;
                                }

                                $spos += 4;
                                $limitpos = $spos + $conlength;
                                $option = ord($data[$spos]);
                                $spos += 1;
                                if( $asciiEncoding && $option == 0 ) 
                                {
                                    $len = min($charsLeft, $limitpos - $spos);
                                    $retstr .= substr($data, $spos, $len);
                                    $charsLeft -= $len;
                                    $asciiEncoding = true;
                                }
                                else
                                {
                                    if( !$asciiEncoding && $option != 0 ) 
                                    {
                                        $len = min($charsLeft * 2, $limitpos - $spos);
                                        $retstr .= substr($data, $spos, $len);
                                        $charsLeft -= $len / 2;
                                        $asciiEncoding = false;
                                    }
                                    else
                                    {
                                        if( !$asciiEncoding && $option == 0 ) 
                                        {
                                            $len = min($charsLeft, $limitpos - $spos);
                                            for( $j = 0; $j < $len; $j++ ) 
                                            {
                                                $retstr .= $data[$spos + $j] . chr(0);
                                            }
                                            $charsLeft -= $len;
                                            $asciiEncoding = false;
                                        }
                                        else
                                        {
                                            $newstr = "";
                                            for( $j = 0; $j < strlen($retstr); $j++ ) 
                                            {
                                                $newstr = $retstr[$j] . chr(0);
                                            }
                                            $retstr = $newstr;
                                            $len = min($charsLeft * 2, $limitpos - $spos);
                                            $retstr .= substr($data, $spos, $len);
                                            $charsLeft -= $len / 2;
                                            $asciiEncoding = false;
                                        }

                                    }

                                }

                                $spos += $len;
                            }
                        }

                        $retstr = ($asciiEncoding ? $retstr : $this->_encodeUTF16($retstr));
                        if( $richString ) 
                        {
                            $spos += 4 * $formattingRuns;
                        }

                        if( $extendedString ) 
                        {
                            $spos += $extendedRunLength;
                        }

                        $this->sst[] = $retstr;
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FILEPASS:
                    return false;
                case SPREADSHEET_EXCEL_READER_TYPE_NAME:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMAT:
                    $indexCode = v($data, $pos + 4);
                    if( $version == SPREADSHEET_EXCEL_READER_BIFF8 ) 
                    {
                        $numchars = v($data, $pos + 6);
                        if( ord($data[$pos + 8]) == 0 ) 
                        {
                            $formatString = substr($data, $pos + 9, $numchars);
                        }
                        else
                        {
                            $formatString = substr($data, $pos + 9, $numchars * 2);
                        }

                    }
                    else
                    {
                        $numchars = ord($data[$pos + 6]);
                        $formatString = substr($data, $pos + 7, $numchars * 2);
                    }

                    $this->formatRecords[$indexCode] = $formatString;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FONT:
                    $height = v($data, $pos + 4);
                    $option = v($data, $pos + 6);
                    $color = v($data, $pos + 8);
                    $weight = v($data, $pos + 10);
                    $under = ord($data[$pos + 14]);
                    $font = "";
                    $numchars = ord($data[$pos + 18]);
                    if( (ord($data[$pos + 19]) & 1) == 0 ) 
                    {
                        $font = substr($data, $pos + 20, $numchars);
                    }
                    else
                    {
                        $font = substr($data, $pos + 20, $numchars * 2);
                        $font = $this->_encodeUTF16($font);
                    }

                    $this->fontRecords[] = array( "height" => $height / 20, "italic" => ($option & 2), "color" => $color, "under" => $under != 0, "bold" => $weight == 700, "font" => $font, "raw" => $this->dumpHexData($data, $pos + 3, $length) );
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_PALETTE:
                    $colors = ord($data[$pos + 4]) | ord($data[$pos + 5]) << 8;
                    for( $coli = 0; $coli < $colors; $coli++ ) 
                    {
                        $colOff = $pos + 2 + $coli * 4;
                        $colr = ord($data[$colOff]);
                        $colg = ord($data[$colOff + 1]);
                        $colb = ord($data[$colOff + 2]);
                        $this->colors[7 + $coli] = "#" . $this->myhex($colr) . $this->myhex($colg) . $this->myhex($colb);
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_XF:
                    $fontIndexCode = (ord($data[$pos + 4]) | ord($data[$pos + 5]) << 8) - 1;
                    $fontIndexCode = max(0, $fontIndexCode);
                    $indexCode = ord($data[$pos + 6]) | ord($data[$pos + 7]) << 8;
                    $alignbit = ord($data[$pos + 10]) & 3;
                    $bgi = (ord($data[$pos + 22]) | ord($data[$pos + 23]) << 8) & 16383;
                    $bgcolor = $bgi & 127;
                    $align = "";
                    if( $alignbit == 3 ) 
                    {
                        $align = "right";
                    }

                    if( $alignbit == 2 ) 
                    {
                        $align = "center";
                    }

                    $fillPattern = (ord($data[$pos + 21]) & 252) >> 2;
                    if( $fillPattern == 0 ) 
                    {
                        $bgcolor = "";
                    }

                    $xf = array(  );
                    $xf["formatIndex"] = $indexCode;
                    $xf["align"] = $align;
                    $xf["fontIndex"] = $fontIndexCode;
                    $xf["bgColor"] = $bgcolor;
                    $xf["fillPattern"] = $fillPattern;
                    $border = ord($data[$pos + 14]) | ord($data[$pos + 15]) << 8 | ord($data[$pos + 16]) << 16 | ord($data[$pos + 17]) << 24;
                    $xf["borderLeft"] = $this->lineStyles[$border & 15];
                    $xf["borderRight"] = $this->lineStyles[($border & 240) >> 4];
                    $xf["borderTop"] = $this->lineStyles[($border & 3840) >> 8];
                    $xf["borderBottom"] = $this->lineStyles[($border & 61440) >> 12];
                    $xf["borderLeftColor"] = ($border & 8323072) >> 16;
                    $xf["borderRightColor"] = ($border & 1065353216) >> 23;
                    $border = ord($data[$pos + 18]) | ord($data[$pos + 19]) << 8;
                    $xf["borderTopColor"] = $border & 127;
                    $xf["borderBottomColor"] = ($border & 16256) >> 7;
                    if( array_key_exists($indexCode, $this->dateFormats) ) 
                    {
                        $xf["type"] = "date";
                        $xf["format"] = $this->dateFormats[$indexCode];
                        if( $align == "" ) 
                        {
                            $xf["align"] = "right";
                        }

                    }
                    else
                    {
                        if( array_key_exists($indexCode, $this->numberFormats) ) 
                        {
                            $xf["type"] = "number";
                            $xf["format"] = $this->numberFormats[$indexCode];
                            if( $align == "" ) 
                            {
                                $xf["align"] = "right";
                            }

                        }
                        else
                        {
                            $isdate = false;
                            $formatstr = "";
                            if( 0 < $indexCode ) 
                            {
                                if( isset($this->formatRecords[$indexCode]) ) 
                                {
                                    $formatstr = $this->formatRecords[$indexCode];
                                }

                                if( $formatstr != "" ) 
                                {
                                    $tmp = preg_replace("/\\;.*/", "", $formatstr);
                                    $tmp = preg_replace("/^\\[[^\\]]*\\]/", "", $tmp);
                                    if( preg_match("/[^hmsday\\/\\-:\\s\\\\,AMP]/i", $tmp) == 0 ) 
                                    {
                                        $isdate = true;
                                        $formatstr = $tmp;
                                        $formatstr = str_replace(array( "AM/PM", "mmmm", "mmm" ), array( "a", "F", "M" ), $formatstr);
                                        $formatstr = preg_replace("/(h:?)mm?/", "\$1i", $formatstr);
                                        $formatstr = preg_replace("/mm?(:?s)/", "i\$1", $formatstr);
                                        $formatstr = preg_replace("/(^|[^m])m([^m]|\$)/", "\$1n\$2", $formatstr);
                                        $formatstr = preg_replace("/(^|[^m])m([^m]|\$)/", "\$1n\$2", $formatstr);
                                        $formatstr = str_replace("mm", "m", $formatstr);
                                        $formatstr = preg_replace("/(^|[^d])d([^d]|\$)/", "\$1j\$2", $formatstr);
                                        $formatstr = str_replace(array( "dddd", "ddd", "dd", "yyyy", "yy", "hh", "h" ), array( "l", "D", "d", "Y", "y", "H", "g" ), $formatstr);
                                        $formatstr = preg_replace("/ss?/", "s", $formatstr);
                                    }

                                }

                            }

                            if( $isdate ) 
                            {
                                $xf["type"] = "date";
                                $xf["format"] = $formatstr;
                                if( $align == "" ) 
                                {
                                    $xf["align"] = "right";
                                }

                            }
                            else
                            {
                                if( preg_match("/[0#]/", $formatstr) ) 
                                {
                                    $xf["type"] = "number";
                                    if( $align == "" ) 
                                    {
                                        $xf["align"] = "right";
                                    }

                                }
                                else
                                {
                                    $xf["type"] = "other";
                                }

                                $xf["format"] = $formatstr;
                                $xf["code"] = $indexCode;
                            }

                        }

                    }

                    $this->xfRecords[] = $xf;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR:
                    $this->nineteenFour = ord($data[$pos + 4]) == 1;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET:
                    $rec_offset = $this->_GetInt4d($data, $pos + 4);
                    $rec_typeFlag = ord($data[$pos + 8]);
                    $rec_visibilityFlag = ord($data[$pos + 9]);
                    $rec_length = ord($data[$pos + 10]);
                    if( $version == SPREADSHEET_EXCEL_READER_BIFF8 ) 
                    {
                        $chartype = ord($data[$pos + 11]);
                        if( $chartype == 0 ) 
                        {
                            $rec_name = substr($data, $pos + 12, $rec_length);
                        }
                        else
                        {
                            $rec_name = $this->_encodeUTF16(substr($data, $pos + 12, $rec_length * 2));
                        }

                    }
                    else
                    {
                        if( $version == SPREADSHEET_EXCEL_READER_BIFF7 ) 
                        {
                            $rec_name = substr($data, $pos + 11, $rec_length);
                        }

                    }

                    $this->boundsheets[] = array( "name" => $rec_name, "offset" => $rec_offset );
                    break;
            }
            $pos += $length + 4;
            $code = ord($data[$pos]) | ord($data[$pos + 1]) << 8;
            $length = ord($data[$pos + 2]) | ord($data[$pos + 3]) << 8;
        }
        foreach( $this->boundsheets as $key => $val ) 
        {
            $this->sn = $key;
            $this->_parsesheet($val["offset"]);
        }
        return true;
    }

    public function _parsesheet($spos)
    {
        $cont = true;
        $data = $this->data;
        $code = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
        $length = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
        $version = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
        $substreamType = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;
        if( $version != SPREADSHEET_EXCEL_READER_BIFF8 && $version != SPREADSHEET_EXCEL_READER_BIFF7 ) 
        {
            return -1;
        }

        if( $substreamType != SPREADSHEET_EXCEL_READER_WORKSHEET ) 
        {
            return -2;
        }

        $spos += $length + 4;
        while( $cont ) 
        {
            $lowcode = ord($data[$spos]);
            if( $lowcode == SPREADSHEET_EXCEL_READER_TYPE_EOF ) 
            {
                break;
            }

            $code = $lowcode | ord($data[$spos + 1]) << 8;
            $length = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
            $spos += 4;
            $this->sheets[$this->sn]["maxrow"] = $this->_rowoffset - 1;
            $this->sheets[$this->sn]["maxcol"] = $this->_coloffset - 1;
            unset($this->rectype);
            switch( $code ) 
            {
                case SPREADSHEET_EXCEL_READER_TYPE_DIMENSION:
                    if( !isset($this->numRows) ) 
                    {
                        if( $length == 10 || $version == SPREADSHEET_EXCEL_READER_BIFF7 ) 
                        {
                            $this->sheets[$this->sn]["numRows"] = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                            $this->sheets[$this->sn]["numCols"] = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;
                        }
                        else
                        {
                            $this->sheets[$this->sn]["numRows"] = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                            $this->sheets[$this->sn]["numCols"] = ord($data[$spos + 10]) | ord($data[$spos + 11]) << 8;
                        }

                    }

                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS:
                    $cellRanges = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    for( $i = 0; $i < $cellRanges; $i++ ) 
                    {
                        $fr = ord($data[$spos + 8 * $i + 2]) | ord($data[$spos + 8 * $i + 3]) << 8;
                        $lr = ord($data[$spos + 8 * $i + 4]) | ord($data[$spos + 8 * $i + 5]) << 8;
                        $fc = ord($data[$spos + 8 * $i + 6]) | ord($data[$spos + 8 * $i + 7]) << 8;
                        $lc = ord($data[$spos + 8 * $i + 8]) | ord($data[$spos + 8 * $i + 9]) << 8;
                        if( 0 < $lr - $fr ) 
                        {
                            $this->sheets[$this->sn]["cellsInfo"][$fr + 1][$fc + 1]["rowspan"] = $lr - $fr + 1;
                        }

                        if( 0 < $lc - $fc ) 
                        {
                            $this->sheets[$this->sn]["cellsInfo"][$fr + 1][$fc + 1]["colspan"] = $lc - $fc + 1;
                        }

                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_RK:
                case SPREADSHEET_EXCEL_READER_TYPE_RK2:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $rknum = $this->_GetInt4d($data, $spos + 6);
                    $numValue = $this->_GetIEEE754($rknum);
                    $info = $this->_getCellDetails($spos, $numValue, $column);
                    $this->addcell($row, $column, $info["string"], $info);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABELSST:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $xfindex = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    $index = $this->_GetInt4d($data, $spos + 6);
                    $this->addcell($row, $column, $this->sst[$index], array( "xfIndex" => $xfindex ));
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULRK:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $colFirst = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $colLast = ord($data[($spos + $length) - 2]) | ord($data[($spos + $length) - 1]) << 8;
                    $columns = $colLast - $colFirst + 1;
                    $tmppos = $spos + 4;
                    for( $i = 0; $i < $columns; $i++ ) 
                    {
                        $numValue = $this->_GetIEEE754($this->_GetInt4d($data, $tmppos + 2));
                        $info = $this->_getCellDetails($tmppos - 4, $numValue, $colFirst + $i + 1);
                        $tmppos += 6;
                        $this->addcell($row, $colFirst + $i, $info["string"], $info);
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NUMBER:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $tmp = unpack("ddouble", substr($data, $spos + 6, 8));
                    if( $this->isDate($spos) ) 
                    {
                        $numValue = $tmp["double"];
                    }
                    else
                    {
                        $numValue = $this->createNumber($spos);
                    }

                    $info = $this->_getCellDetails($spos, $numValue, $column);
                    $this->addcell($row, $column, $info["string"], $info);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA:
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA2:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    if( ord($data[$spos + 6]) == 0 && ord($data[$spos + 12]) == 255 && ord($data[$spos + 13]) == 255 ) 
                    {
                        $previousRow = $row;
                        $previousCol = $column;
                    }
                    else
                    {
                        if( ord($data[$spos + 6]) == 1 && ord($data[$spos + 12]) == 255 && ord($data[$spos + 13]) == 255 ) 
                        {
                            if( ord($this->data[$spos + 8]) == 1 ) 
                            {
                                $this->addcell($row, $column, "TRUE");
                            }
                            else
                            {
                                $this->addcell($row, $column, "FALSE");
                            }

                        }
                        else
                        {
                            if( ord($data[$spos + 6]) == 2 && ord($data[$spos + 12]) == 255 && ord($data[$spos + 13]) == 255 ) 
                            {
                            }
                            else
                            {
                                if( ord($data[$spos + 6]) == 3 && ord($data[$spos + 12]) == 255 && ord($data[$spos + 13]) == 255 ) 
                                {
                                    $this->addcell($row, $column, "");
                                }
                                else
                                {
                                    $tmp = unpack("ddouble", substr($data, $spos + 6, 8));
                                    if( $this->isDate($spos) ) 
                                    {
                                        $numValue = $tmp["double"];
                                    }
                                    else
                                    {
                                        $numValue = $this->createNumber($spos);
                                    }

                                    $info = $this->_getCellDetails($spos, $numValue, $column);
                                    $this->addcell($row, $column, $info["string"], $info);
                                }

                            }

                        }

                    }

                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOOLERR:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $string = ord($data[$spos + 6]);
                    $this->addcell($row, $column, $string);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_STRING:
                    if( $version == SPREADSHEET_EXCEL_READER_BIFF8 ) 
                    {
                        $xpos = $spos;
                        $numChars = ord($data[$xpos]) | ord($data[$xpos + 1]) << 8;
                        $xpos += 2;
                        $optionFlags = ord($data[$xpos]);
                        $xpos++;
                        $asciiEncoding = ($optionFlags & 1) == 0;
                        $extendedString = ($optionFlags & 4) != 0;
                        $richString = ($optionFlags & 8) != 0;
                        if( $richString ) 
                        {
                            $formattingRuns = ord($data[$xpos]) | ord($data[$xpos + 1]) << 8;
                            $xpos += 2;
                        }

                        if( $extendedString ) 
                        {
                            $extendedRunLength = $this->_GetInt4d($this->data, $xpos);
                            $xpos += 4;
                        }

                        $len = ($asciiEncoding ? $numChars : $numChars * 2);
                        $retstr = substr($data, $xpos, $len);
                        $xpos += $len;
                        $retstr = ($asciiEncoding ? $retstr : $this->_encodeUTF16($retstr));
                    }
                    else
                    {
                        if( $version == SPREADSHEET_EXCEL_READER_BIFF7 ) 
                        {
                            $xpos = $spos;
                            $numChars = ord($data[$xpos]) | ord($data[$xpos + 1]) << 8;
                            $xpos += 2;
                            $retstr = substr($data, $xpos, $numChars);
                        }

                    }

                    $this->addcell($previousRow, $previousCol, $retstr);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_ROW:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $rowInfo = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8 & 32767;
                    if( 0 < ($rowInfo & 32768) ) 
                    {
                        $rowHeight = -1;
                    }
                    else
                    {
                        $rowHeight = $rowInfo & 32767;
                    }

                    $rowHidden = (ord($data[$spos + 12]) & 32) >> 5;
                    $this->rowInfo[$this->sn][$row + 1] = array( "height" => $rowHeight / 20, "hidden" => $rowHidden );
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_DBCELL:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULBLANK:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $cols = $length / 2 - 3;
                    for( $c = 0; $c < $cols; $c++ ) 
                    {
                        $xfindex = ord($data[$spos + 4 + $c * 2]) | ord($data[$spos + 5 + $c * 2]) << 8;
                        $this->addcell($row, $column + $c, "", array( "xfIndex" => $xfindex ));
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABEL:
                    $row = ord($data[$spos]) | ord($data[$spos + 1]) << 8;
                    $column = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $this->addcell($row, $column, substr($data, $spos + 8, ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8));
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_EOF:
                    $cont = false;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_HYPER:
                    $row = ord($this->data[$spos]) | ord($this->data[$spos + 1]) << 8;
                    $row2 = ord($this->data[$spos + 2]) | ord($this->data[$spos + 3]) << 8;
                    $column = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
                    $column2 = ord($this->data[$spos + 6]) | ord($this->data[$spos + 7]) << 8;
                    $linkdata = array(  );
                    $flags = ord($this->data[$spos + 28]);
                    $udesc = "";
                    $ulink = "";
                    $uloc = 32;
                    $linkdata["flags"] = $flags;
                    if( 0 < ($flags & 1) ) 
                    {
                        if( ($flags & 20) == 20 ) 
                        {
                            $uloc += 4;
                            $descLen = ord($this->data[$spos + 32]) | ord($this->data[$spos + 33]) << 8;
                            $udesc = substr($this->data, $spos + $uloc, $descLen * 2);
                            $uloc += 2 * $descLen;
                        }

                        $ulink = $this->read16bitstring($this->data, $spos + $uloc + 20);
                        if( $udesc == "" ) 
                        {
                            $udesc = $ulink;
                        }

                    }

                    $linkdata["desc"] = $udesc;
                    $linkdata["link"] = $this->_encodeUTF16($ulink);
                    for( $r = $row; $r <= $row2; $r++ ) 
                    {
                        for( $c = $column; $c <= $column2; $c++ ) 
                        {
                            $this->sheets[$this->sn]["cellsInfo"][$r + 1][$c + 1]["hyperlink"] = $linkdata;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_DEFCOLWIDTH:
                    $this->defaultColWidth = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_STANDARDWIDTH:
                    $this->standardColWidth = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_COLINFO:
                    $colfrom = ord($data[$spos + 0]) | ord($data[$spos + 1]) << 8;
                    $colto = ord($data[$spos + 2]) | ord($data[$spos + 3]) << 8;
                    $cw = ord($data[$spos + 4]) | ord($data[$spos + 5]) << 8;
                    $cxf = ord($data[$spos + 6]) | ord($data[$spos + 7]) << 8;
                    $co = ord($data[$spos + 8]);
                    for( $coli = $colfrom; $coli <= $colto; $coli++ ) 
                    {
                        $this->colInfo[$this->sn][$coli + 1] = array( "width" => $cw, "xf" => $cxf, "hidden" => $co & 1, "collapsed" => ($co & 4096) >> 12 );
                    }
                    break;
                default:
                    break;
            }
            $spos += $length;
        }
        if( !isset($this->sheets[$this->sn]["numRows"]) ) 
        {
            $this->sheets[$this->sn]["numRows"] = $this->sheets[$this->sn]["maxrow"];
        }

        if( !isset($this->sheets[$this->sn]["numCols"]) ) 
        {
            $this->sheets[$this->sn]["numCols"] = $this->sheets[$this->sn]["maxcol"];
        }

    }

    public function isDate($spos)
    {
        $xfindex = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
        return $this->xfRecords[$xfindex]["type"] == "date";
    }

    public function _getCellDetails($spos, $numValue, $column)
    {
        $xfindex = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5]) << 8;
        $xfrecord = $this->xfRecords[$xfindex];
        $type = $xfrecord["type"];
        $format = $xfrecord["format"];
        $formatIndex = $xfrecord["formatIndex"];
        $fontIndex = $xfrecord["fontIndex"];
        $formatColor = "";
        $rectype = "";
        $string = "";
        $raw = "";
        if( isset($this->_columnsFormat[$column + 1]) ) 
        {
            $format = $this->_columnsFormat[$column + 1];
        }

        if( $type == "date" ) 
        {
            $rectype = "date";
            $utcDays = floor($numValue - (($this->nineteenFour ? SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904 : SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS)));
            $utcValue = $utcDays * SPREADSHEET_EXCEL_READER_MSINADAY;
            $dateinfo = gmgetdate($utcValue);
            $raw = $numValue;
            $fractionalDay = $numValue - floor($numValue) + 1E-07;
            $totalseconds = floor(SPREADSHEET_EXCEL_READER_MSINADAY * $fractionalDay);
            $secs = $totalseconds % 60;
            $totalseconds -= $secs;
            $hours = floor($totalseconds / (60 * 60));
            $mins = floor($totalseconds / 60) % 60;
            $string = date($format, mktime($hours, $mins, $secs, $dateinfo["mon"], $dateinfo["mday"], $dateinfo["year"]));
        }
        else
        {
            if( $type == "number" ) 
            {
                $rectype = "number";
                $formatted = $this->_format_value($format, $numValue, $formatIndex);
                $string = $formatted["string"];
                $formatColor = $formatted["formatColor"];
                $raw = $numValue;
            }
            else
            {
                if( $format == "" ) 
                {
                    $format = $this->_defaultFormat;
                }

                $rectype = "unknown";
                $formatted = $this->_format_value($format, $numValue, $formatIndex);
                $string = $formatted["string"];
                $formatColor = $formatted["formatColor"];
                $raw = $numValue;
            }

        }

        return array( "string" => $string, "raw" => $raw, "rectype" => $rectype, "format" => $format, "formatIndex" => $formatIndex, "fontIndex" => $fontIndex, "formatColor" => $formatColor, "xfIndex" => $xfindex );
    }

    public function createNumber($spos)
    {
        $rknumhigh = $this->_GetInt4d($this->data, $spos + 10);
        $rknumlow = $this->_GetInt4d($this->data, $spos + 6);
        $sign = ($rknumhigh & 2147483648) >> 31;
        $exp = ($rknumhigh & 2146435072) >> 20;
        $mantissa = 1048576 | $rknumhigh & 1048575;
        $mantissalow1 = ($rknumlow & 2147483648) >> 31;
        $mantissalow2 = $rknumlow & 2147483647;
        $value = $mantissa / pow(2, 20 - ($exp - 1023));
        if( $mantissalow1 != 0 ) 
        {
            $value += 1 / pow(2, 21 - ($exp - 1023));
        }

        $value += $mantissalow2 / pow(2, 52 - ($exp - 1023));
        if( $sign ) 
        {
            $value = -1 * $value;
        }

        return $value;
    }

    public function addcell($row, $col, $string, $info = NULL)
    {
        $this->sheets[$this->sn]["maxrow"] = max($this->sheets[$this->sn]["maxrow"], $row + $this->_rowoffset);
        $this->sheets[$this->sn]["maxcol"] = max($this->sheets[$this->sn]["maxcol"], $col + $this->_coloffset);
        $this->sheets[$this->sn]["cells"][$row + $this->_rowoffset][$col + $this->_coloffset] = $string;
        if( $this->store_extended_info && $info ) 
        {
            foreach( $info as $key => $val ) 
            {
                $this->sheets[$this->sn]["cellsInfo"][$row + $this->_rowoffset][$col + $this->_coloffset][$key] = $val;
            }
        }

    }

    public function _GetIEEE754($rknum)
    {
        if( ($rknum & 2) != 0 ) 
        {
            $value = $rknum >> 2;
        }
        else
        {
            $sign = ($rknum & 2147483648) >> 31;
            $exp = ($rknum & 2146435072) >> 20;
            $mantissa = 1048576 | $rknum & 1048572;
            $value = $mantissa / pow(2, 20 - ($exp - 1023));
            if( $sign ) 
            {
                $value = -1 * $value;
            }

        }

        if( ($rknum & 1) != 0 ) 
        {
            $value /= 100;
        }

        return $value;
    }

    public function _encodeUTF16($string)
    {
        $result = $string;
        if( $this->_defaultEncoding ) 
        {
            switch( $this->_encoderFunction ) 
            {
                case "iconv":
                    $result = iconv("UTF-16LE", $this->_defaultEncoding, $string);
                    break;
                case "mb_convert_encoding":
                    $result = mb_convert_encoding($string, $this->_defaultEncoding, "UTF-16LE");
                    break;
            }
        }

        return $result;
    }

    public function _GetInt4d($data, $pos)
    {
        $value = ord($data[$pos]) | ord($data[$pos + 1]) << 8 | ord($data[$pos + 2]) << 16 | ord($data[$pos + 3]) << 24;
        if( 4294967294 <= $value ) 
        {
            $value = -2;
        }

        return $value;
    }

}


class SimpleXLSX
{
    private $worksheets = NULL;
    private $hyperlinks = NULL;
    private $package = NULL;
    private $sharedstrings = NULL;
    public $worksheetMap = NULL;

    const SCHEMA_OFFICEDOCUMENT = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument";
    const SCHEMA_RELATIONSHIP = "http://schemas.openxmlformats.org/package/2006/relationships";
    const SCHEMA_SHAREDSTRINGS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings";
    const SCHEMA_WORKSHEETRELATION = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet";

    public function __construct($filename)
    {
        $this->_unzip($filename);
        $this->_parse();
    }

    public function worksheet($worksheet_id)
    {
        if( isset($this->worksheets[$worksheet_id]) ) 
        {
            $ws = $this->worksheets[$worksheet_id];
            if( isset($ws->hyperlinks) ) 
            {
                $this->hyperlinks = array(  );
                foreach( $ws->hyperlinks->hyperlink as $hyperlink ) 
                {
                    $this->hyperlinks[(string) $hyperlink["ref"]] = (string) $hyperlink["display"];
                }
            }

            return $ws;
        }
        else
        {
            trigger_error("Worksheet " . $worksheet_id . " not found.", 512);
            return false;
        }

    }

    public function getWorksheetIDbyName($name)
    {
        if( false == empty($this->worksheetMap[$name]) ) 
        {
            return (int) $this->worksheetMap[$name];
        }

        return false;
    }

    public function rows($worksheet_id = 1)
    {
        $rows = array(  );
        $curR = 0;
        if( ($ws = $this->worksheet($worksheet_id)) === false ) 
        {
            return false;
        }

        foreach( $ws->sheetData->row as $row ) 
        {
            $curC = 0;
            foreach( $row->c as $c ) 
            {
                $rows[$curR][$curC] = $this->value($c);
                $curC++;
            }
            $curR++;
        }
        return $rows;
    }

    public function rowsEx($worksheet_id = 1)
    {
        $rows = array(  );
        $curR = 0;
        if( ($ws = $this->worksheet($worksheet_id)) === false ) 
        {
            return false;
        }

        foreach( $ws->sheetData->row as $row ) 
        {
            $curC = 0;
            foreach( $row->c as $c ) 
            {
                $rows[$curR][$curC] = array( "name" => (string) $c["r"], "value" => $this->value($c), "href" => $this->href($c) );
                $curC++;
            }
            $curR++;
        }
        return $rows;
    }

    public function value($cell)
    {
        $dataType = (string) $cell["t"];
        switch( $dataType ) 
        {
            case "s":
                if( (string) $cell->v != "" ) 
                {
                    $value = $this->sharedstrings[intval($cell->v)];
                }
                else
                {
                    $value = "";
                }

                break;
            case "b":
                $value = (string) $cell->v;
                if( $value == "0" ) 
                {
                    $value = false;
                }
                else
                {
                    if( $value == "1" ) 
                    {
                        $value = true;
                    }
                    else
                    {
                        $value = (bool) $cell->v;
                    }

                }

                break;
            case "inlineStr":
                $value = $this->_parseRichText($cell->is);
                break;
            case "e":
                if( (string) $cell->v != "" ) 
                {
                    $value = (string) $cell->v;
                }
                else
                {
                    $value = "";
                }

                break;
            default:
                $value = (string) $cell->v;
                if( is_numeric($value) && $dataType != "s" ) 
                {
                    if( $value == (int) $value ) 
                    {
                        $value = (int) $value;
                    }
                    else
                    {
                        if( $value == (double) $value ) 
                        {
                            $value = (double) $value;
                        }
                        else
                        {
                            if( $value == (double) $value ) 
                            {
                                $value = (double) $value;
                            }

                        }

                    }

                }

        }
        return $value;
    }

    public function href($cell)
    {
        return (isset($this->hyperlinks[(string) $cell["r"]]) ? $this->hyperlinks[(string) $cell["r"]] : "");
    }

    public function _unzip($filename)
    {
        $this->datasec = array(  );
        $this->package = array( "filename" => $filename, "mtime" => filemtime($filename), "size" => filesize($filename), "comment" => "", "entries" => array(  ) );
        $oF = fopen($filename, "rb");
        $vZ = fread($oF, $this->package["size"]);
        fclose($oF);
        $aE = explode("PK\x05\x06", $vZ);
        $aP = unpack("x16/v1CL", $aE[1]);
        if( !isset($aP["CL"]) ) 
        {
            throw new Exception((string) $filename . " is not a vaid file");
        }

        $this->package["comment"] = substr($aE[1], 18, $aP["CL"]);
        $this->package["comment"] = strtr($this->package["comment"], array( "\r\n" => "\n", "\r" => "\n" ));
        $aE = explode("PK\x01\x02", $vZ);
        $aE = explode("PK\x03\x04", $aE[0]);
        array_shift($aE);
        foreach( $aE as $vZ ) 
        {
            $aI = array(  );
            $aI["E"] = 0;
            $aI["EM"] = "";
            $aP = unpack("v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL/v1EFL", $vZ);
            $bE = false;
            $nF = $aP["FNL"];
            $mF = $aP["EFL"];
            if( $aP["GPF"] & 8 ) 
            {
                $aP1 = unpack("V1CRC/V1CS/V1UCS", substr($vZ, -12));
                $aP["CRC"] = $aP1["CRC"];
                $aP["CS"] = $aP1["CS"];
                $aP["UCS"] = $aP1["UCS"];
                $vZ = substr($vZ, 0, -12);
            }

            $aI["N"] = substr($vZ, 26, $nF);
            if( substr($aI["N"], -1) == "/" ) 
            {
                continue;
            }

            $aI["P"] = dirname($aI["N"]);
            $aI["P"] = ($aI["P"] == "." ? "" : $aI["P"]);
            $aI["N"] = basename($aI["N"]);
            $vZ = substr($vZ, 26 + $nF + $mF);
            if( strlen($vZ) != $aP["CS"] ) 
            {
                $aI["E"] = 1;
                $aI["EM"] = "Compressed size is not equal with the value in header information.";
            }
            else
            {
                if( $bE ) 
                {
                    $aI["E"] = 5;
                    $aI["EM"] = "File is encrypted, which is not supported from this class.";
                }
                else
                {
                    switch( $aP["CM"] ) 
                    {
                        case 0:
                            break;
                        case 8:
                            $vZ = gzinflate($vZ);
                            break;
                        case 12:
                            if( !extension_loaded("bz2") ) 
                            {
                                if( strtoupper(substr(PHP_OS, 0, 3)) == "WIN" ) 
                                {
                                    @dl("php_bz2.dll");
                                }
                                else
                                {
                                    @dl("bz2.so");
                                }

                            }

                            if( extension_loaded("bz2") ) 
                            {
                                $vZ = bzdecompress($vZ);
                            }
                            else
                            {
                                $aI["E"] = 7;
                                $aI["EM"] = "PHP BZIP2 extension not available.";
                            }

                            break;
                        default:
                            $aI["E"] = 6;
                            $aI["EM"] = "De-/Compression method " . $aP["CM"] . " is not supported.";
                    }
                    if( !$aI["E"] ) 
                    {
                        if( $vZ === false ) 
                        {
                            $aI["E"] = 2;
                            $aI["EM"] = "Decompression of data failed.";
                        }
                        else
                        {
                            if( strlen($vZ) != $aP["UCS"] ) 
                            {
                                $aI["E"] = 3;
                                $aI["EM"] = "Uncompressed size is not equal with the value in header information.";
                            }
                            else
                            {
                                if( crc32($vZ) != $aP["CRC"] ) 
                                {
                                    $aI["E"] = 4;
                                    $aI["EM"] = "CRC32 checksum is not equal with the value in header information.";
                                }

                            }

                        }

                    }

                }

            }

            $aI["D"] = $vZ;
            $aI["T"] = mktime(($aP["FT"] & 63488) >> 11, ($aP["FT"] & 2016) >> 5, ($aP["FT"] & 31) << 1, ($aP["FD"] & 480) >> 5, $aP["FD"] & 31, (($aP["FD"] & 65024) >> 9) + 1980);
            $this->package["entries"][] = array( "data" => $aI["D"], "error" => $aI["E"], "error_msg" => $aI["EM"], "name" => $aI["N"], "path" => $aI["P"], "time" => $aI["T"] );
        }
    }

    public function getPackage()
    {
        return $this->package;
    }

    public function getEntryData($name)
    {
        $dir = dirname($name);
        $name = basename($name);
        foreach( $this->package["entries"] as $entry ) 
        {
            if( $entry["path"] == $dir && $entry["name"] == $name ) 
            {
                return $entry["data"];
            }

        }
    }

    public function _parse()
    {
        $this->sharedstrings = array(  );
        $this->worksheets = array(  );
        $this->worksheetMap = array(  );
        $relations = simplexml_load_string($this->getEntryData("_rels/.rels"));
        foreach( $relations->Relationship as $rel ) 
        {
            if( $rel["Type"] == SimpleXLSX::SCHEMA_OFFICEDOCUMENT ) 
            {
                $workbookRelations = simplexml_load_string($this->getEntryData(dirname($rel["Target"]) . "/_rels/" . basename($rel["Target"]) . ".rels"));
                $workbookRelations->registerXPathNamespace("rel", SimpleXLSX::SCHEMA_RELATIONSHIP);
                $sharedStringsPath = $workbookRelations->xpath("rel:Relationship[@Type='" . SimpleXLSX::SCHEMA_SHAREDSTRINGS . "']");
                $sharedStringsPath = (string) $sharedStringsPath[0]["Target"];
                $xmlStrings = simplexml_load_string($this->getEntryData(dirname($rel["Target"]) . "/" . $sharedStringsPath));
                if( isset($xmlStrings) && isset($xmlStrings->si) ) 
                {
                    foreach( $xmlStrings->si as $val ) 
                    {
                        if( isset($val->t) ) 
                        {
                            $this->sharedstrings[] = (string) $val->t;
                        }
                        else
                        {
                            if( isset($val->r) ) 
                            {
                                $this->sharedstrings[] = $this->_parseRichText($val);
                            }

                        }

                    }
                }

                $xmlStrings = simplexml_load_string($this->getEntryData("xl\\workbook.xml"));
                if( isset($xmlStrings) && isset($xmlStrings->sheets) ) 
                {
                    foreach( $xmlStrings->sheets->sheet as $sheet ) 
                    {
                        $arrt = $sheet->attributes();
                        $this->worksheetMap[(string) $arrt["name"]] = (string) $arrt["sheetId"];
                    }
                }

                foreach( $workbookRelations->Relationship as $workbookRelation ) 
                {
                    if( $workbookRelation["Type"] == SimpleXLSX::SCHEMA_WORKSHEETRELATION ) 
                    {
                        $this->worksheets[str_replace("rId", "", (string) $workbookRelation["Id"])] = simplexml_load_string($this->getEntryData(dirname($rel["Target"]) . "/" . dirname($workbookRelation["Target"]) . "/" . basename($workbookRelation["Target"])));
                    }

                }
                break;
            }

        }
        ksort($this->worksheets);
    }

    private function _parseRichText($is = NULL)
    {
        $value = array(  );
        if( isset($is->t) ) 
        {
            $value[] = (string) $is->t;
        }
        else
        {
            foreach( $is->r as $run ) 
            {
                $value[] = (string) $run->t;
            }
        }

        return implode(" ", $value);
    }

}

function GetInt4d($data, $pos)
{
    $value = ord($data[$pos]) | ord($data[$pos + 1]) << 8 | ord($data[$pos + 2]) << 16 | ord($data[$pos + 3]) << 24;
    if( 4294967294 <= $value ) 
    {
        $value = -2;
    }

    return $value;
}

function gmgetdate($ts = NULL)
{
    $k = array( "seconds", "minutes", "hours", "mday", "wday", "mon", "year", "yday", "weekday", "month", 0 );
    return array_comb($k, split(":", gmdate("s:i:G:j:w:n:Y:z:l:F:U", (is_null($ts) ? time() : $ts))));
}

function array_comb($array1, $array2)
{
    $out = array(  );
    foreach( $array1 as $key => $value ) 
    {
        $out[$value] = $array2[$key];
    }
    return $out;
}

function v($data, $pos)
{
    return ord($data[$pos]) | ord($data[$pos + 1]) << 8;
}


