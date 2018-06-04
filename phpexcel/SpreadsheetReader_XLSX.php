<?php 

class SpreadsheetReader_XLSX implements Iterator, Countable
{
    private $Options = array( "TempDir" => "", "ReturnDateTimeObjects" => false );
    private static $RuntimeInfo = array( "GMPSupported" => false );
    private $Valid = false;
    private $Handle = false;
    private $WorksheetPath = false;
    private $Worksheet = false;
    private $SharedStringsPath = false;
    private $SharedStrings = false;
    private $SharedStringCache = array(  );
    private $WorkbookXML = false;
    private $StylesXML = false;
    private $Styles = array(  );
    private $TempDir = "";
    private $TempFiles = array(  );
    private $CurrentRow = false;
    private $Index = 0;
    private $Sheets = false;
    private $SharedStringCount = 0;
    private $SharedStringIndex = 0;
    private $LastSharedStringValue = NULL;
    private $RowOpen = false;
    private $SSOpen = false;
    private $SSForwarded = false;
    private static $BuiltinFormats = array( "", "0", "0.00", "#,##0", "#,##0.00", "9" => "0%", "10" => "0.00%", "11" => "0.00E+00", "12" => "# ?/?", "13" => "# ??/??", "14" => "mm-dd-yy", "15" => "d-mmm-yy", "16" => "d-mmm", "17" => "mmm-yy", "18" => "h:mm AM/PM", "19" => "h:mm:ss AM/PM", "20" => "h:mm", "21" => "h:mm:ss", "22" => "m/d/yy h:mm", "37" => "#,##0 ;(#,##0)", "38" => "#,##0 ;[Red](#,##0)", "39" => "#,##0.00;(#,##0.00)", "40" => "#,##0.00;[Red](#,##0.00)", "45" => "mm:ss", "46" => "[h]:mm:ss", "47" => "mmss.0", "48" => "##0.0E+0", "49" => "@", "27" => "[\$-404]e/m/d", "30" => "m/d/yy", "36" => "[\$-404]e/m/d", "50" => "[\$-404]e/m/d", "57" => "[\$-404]e/m/d", "59" => "t0", "60" => "t0.00", "61" => "t#,##0", "62" => "t#,##0.00", "67" => "t0%", "68" => "t0.00%", "69" => "t# ?/?", "70" => "t# ??/??" );
    private $Formats = array(  );
    private static $DateReplacements = array( "All" => array( "\\" => "", "am/pm" => "A", "yyyy" => "Y", "yy" => "y", "mmmmm" => "M", "mmmm" => "F", "mmm" => "M", ":mm" => ":i", "mm" => "m", "m" => "n", "dddd" => "l", "ddd" => "D", "dd" => "d", "d" => "j", "ss" => "s", ".s" => "" ), "24H" => array( "hh" => "H", "h" => "G" ), "12H" => array( "hh" => "h", "h" => "G" ) );
    private static $BaseDate = false;
    private static $DecimalSeparator = ".";
    private static $ThousandSeparator = "";
    private static $CurrencyCode = "";
    private $ParsedFormatCache = array(  );

    const CELL_TYPE_BOOL = "b";
    const CELL_TYPE_NUMBER = "n";
    const CELL_TYPE_ERROR = "e";
    const CELL_TYPE_SHARED_STR = "s";
    const CELL_TYPE_STR = "str";
    const CELL_TYPE_INLINE_STR = "inlineStr";
    const SHARED_STRING_CACHE_LIMIT = 50000;

    public function __construct($Filepath, array $Options = NULL)
    {
        if( !is_readable($Filepath) ) 
        {
            throw new Exception("SpreadsheetReader_XLSX: File not readable (" . $Filepath . ")");
        }

        $this->TempDir = (isset($Options["TempDir"]) && is_writable($Options["TempDir"]) ? $Options["TempDir"] : sys_get_temp_dir());
        $this->TempDir = rtrim($this->TempDir, DIRECTORY_SEPARATOR);
        $this->TempDir = $this->TempDir . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR;
        $Zip = new ZipArchive();
        $Status = $Zip->open($Filepath);
        if( $Status !== true ) 
        {
            throw new Exception("SpreadsheetReader_XLSX: File not readable (" . $Filepath . ") (Error " . $Status . ")");
        }

        if( $Zip->locateName("xl/workbook.xml") !== false ) 
        {
            $this->WorkbookXML = new SimpleXMLElement($Zip->getFromName("xl/workbook.xml"));
        }

        if( $Zip->locateName("xl/sharedStrings.xml") !== false ) 
        {
            $this->SharedStringsPath = $this->TempDir . "xl" . DIRECTORY_SEPARATOR . "sharedStrings.xml";
            $Zip->extractTo($this->TempDir, "xl/sharedStrings.xml");
            $this->TempFiles[] = $this->TempDir . "xl" . DIRECTORY_SEPARATOR . "sharedStrings.xml";
            if( is_readable($this->SharedStringsPath) ) 
            {
                $this->SharedStrings = new XMLReader();
                $this->SharedStrings->open($this->SharedStringsPath);
                $this->PrepareSharedStringCache();
            }

        }

        $Sheets = $this->Sheets();
        foreach( $this->Sheets as $Index => $Name ) 
        {
            if( $Zip->locateName("xl/worksheets/sheet" . $Index . ".xml") !== false ) 
            {
                $Zip->extractTo($this->TempDir, "xl/worksheets/sheet" . $Index . ".xml");
                $this->TempFiles[] = $this->TempDir . "xl" . DIRECTORY_SEPARATOR . "worksheets" . DIRECTORY_SEPARATOR . "sheet" . $Index . ".xml";
            }

        }
        $this->ChangeSheet(0);
        if( $Zip->locateName("xl/styles.xml") !== false ) 
        {
            $this->StylesXML = new SimpleXMLElement($Zip->getFromName("xl/styles.xml"));
            if( $this->StylesXML && $this->StylesXML->cellXfs && $this->StylesXML->cellXfs->xf ) 
            {
                foreach( $this->StylesXML->cellXfs->xf as $Index => $XF ) 
                {
                    if( $XF->attributes()->applyNumberFormat || 0 == (int) $XF->attributes()->numFmtId ) 
                    {
                        $FormatId = (int) $XF->attributes()->numFmtId;
                        $this->Styles[] = $FormatId;
                    }
                    else
                    {
                        $this->Styles[] = 0;
                    }

                }
            }

            if( $this->StylesXML->numFmts && $this->StylesXML->numFmts->numFmt ) 
            {
                foreach( $this->StylesXML->numFmts->numFmt as $Index => $NumFmt ) 
                {
                    $this->Formats[(int) $NumFmt->attributes()->numFmtId] = (string) $NumFmt->attributes()->formatCode;
                }
            }

            unset($this->StylesXML);
        }

        $Zip->close();
        if( !self::$BaseDate ) 
        {
            self::$BaseDate = new DateTime();
            self::$BaseDate->setTimezone(new DateTimeZone("UTC"));
            self::$BaseDate->setDate(1900, 1, 0);
            self::$BaseDate->setTime(0, 0, 0);
        }

        if( !self::$DecimalSeparator && !self::$ThousandSeparator && !self::$CurrencyCode ) 
        {
            $Locale = localeconv();
            self::$DecimalSeparator = $Locale["decimal_point"];
            self::$ThousandSeparator = $Locale["thousands_sep"];
            self::$CurrencyCode = $Locale["int_curr_symbol"];
        }

        if( function_exists("gmp_gcd") ) 
        {
            self::$RuntimeInfo["GMPSupported"] = true;
        }

    }

    public function __destruct()
    {
        foreach( $this->TempFiles as $TempFile ) 
        {
            @unlink($TempFile);
        }
        if( 2 < strlen($this->TempDir) ) 
        {
            @rmdir($this->TempDir . "xl" . DIRECTORY_SEPARATOR . "worksheets");
            @rmdir($this->TempDir . "xl");
            @rmdir($this->TempDir);
        }

        if( $this->Worksheet && $this->Worksheet instanceof XMLReader ) 
        {
            $this->Worksheet->close();
            unset($this->Worksheet);
        }

        unset($this->WorksheetPath);
        if( $this->SharedStrings && $this->SharedStrings instanceof XMLReader ) 
        {
            $this->SharedStrings->close();
            unset($this->SharedStrings);
        }

        unset($this->SharedStringsPath);
        if( isset($this->StylesXML) ) 
        {
            unset($this->StylesXML);
        }

        if( $this->WorkbookXML ) 
        {
            unset($this->WorkbookXML);
        }

    }

    public function Sheets()
    {
        if( $this->Sheets === false ) 
        {
            $this->Sheets = array(  );
            foreach( $this->WorkbookXML->sheets->sheet as $Index => $Sheet ) 
            {
                $Attributes = $Sheet->attributes("r", true);
                foreach( $Attributes as $Name => $Value ) 
                {
                    if( $Name == "id" ) 
                    {
                        $SheetID = (int) str_replace("rId", "", (string) $Value);
                        break;
                    }

                }
                $this->Sheets[$SheetID] = (string) $Sheet["name"];
            }
            ksort($this->Sheets);
        }

        return array_values($this->Sheets);
    }

    public function ChangeSheet($Index)
    {
        $RealSheetIndex = false;
        $Sheets = $this->Sheets();
        if( isset($Sheets[$Index]) ) 
        {
            $SheetIndexes = array_keys($this->Sheets);
            $RealSheetIndex = $SheetIndexes[$Index];
        }

        $TempWorksheetPath = $this->TempDir . "xl/worksheets/sheet" . $RealSheetIndex . ".xml";
        if( $RealSheetIndex !== false && is_readable($TempWorksheetPath) ) 
        {
            $this->WorksheetPath = $TempWorksheetPath;
            $this->rewind();
            return true;
        }

        return false;
    }

    private function PrepareSharedStringCache()
    {
        while( $this->SharedStrings->read() ) 
        {
            if( $this->SharedStrings->name == "sst" ) 
            {
                $this->SharedStringCount = $this->SharedStrings->getAttribute("count");
                break;
            }

        }
        if( !$this->SharedStringCount || self::SHARED_STRING_CACHE_LIMIT < $this->SharedStringCount && self::SHARED_STRING_CACHE_LIMIT !== NULL ) 
        {
            return false;
        }

        $CacheIndex = 0;
        $CacheValue = "";
        while( $this->SharedStrings->read() ) 
        {
            switch( $this->SharedStrings->name ) 
            {
                case "si":
                    if( $this->SharedStrings->nodeType == XMLReader::END_ELEMENT ) 
                    {
                        $this->SharedStringCache[$CacheIndex] = $CacheValue;
                        $CacheIndex++;
                        $CacheValue = "";
                    }

                    break;
                case "t":
                    if( $this->SharedStrings->nodeType == XMLReader::END_ELEMENT ) 
                    {
                        continue;
                    }

                    $CacheValue .= $this->SharedStrings->readString();
                    break;
            }
        }
        $this->SharedStrings->close();
        return true;
    }

    private function GetSharedString($Index)
    {
        if( (self::SHARED_STRING_CACHE_LIMIT === NULL || 0 < self::SHARED_STRING_CACHE_LIMIT) && !empty($this->SharedStringCache) ) 
        {
            if( isset($this->SharedStringCache[$Index]) ) 
            {
                return $this->SharedStringCache[$Index];
            }

            return "";
        }

        if( $Index < $this->SharedStringIndex ) 
        {
            $this->SSOpen = false;
            $this->SharedStrings->close();
            $this->SharedStrings->open($this->SharedStringsPath);
            $this->SharedStringIndex = 0;
            $this->LastSharedStringValue = NULL;
            $this->SSForwarded = false;
        }

        if( $this->SharedStringIndex == 0 && !$this->SharedStringCount ) 
        {
            while( $this->SharedStrings->read() ) 
            {
                if( $this->SharedStrings->name == "sst" ) 
                {
                    $this->SharedStringCount = $this->SharedStrings->getAttribute("uniqueCount");
                    break;
                }

            }
        }

        if( $this->SharedStringCount && $this->SharedStringCount <= $Index ) 
        {
            return "";
        }

        if( $Index == $this->SharedStringIndex && $this->LastSharedStringValue !== NULL ) 
        {
            return $this->LastSharedStringValue;
        }

        while( $this->SharedStringIndex <= $Index ) 
        {
            if( $this->SSForwarded ) 
            {
                $this->SSForwarded = false;
            }
            else
            {
                $ReadStatus = $this->SharedStrings->read();
                if( !$ReadStatus ) 
                {
                    break;
                }

            }

            if( $this->SharedStrings->name == "si" ) 
            {
                if( $this->SharedStrings->nodeType == XMLReader::END_ELEMENT ) 
                {
                    $this->SSOpen = false;
                    $this->SharedStringIndex++;
                }
                else
                {
                    $this->SSOpen = true;
                    if( $this->SharedStringIndex < $Index ) 
                    {
                        $this->SSOpen = false;
                        $this->SharedStrings->next("si");
                        $this->SSForwarded = true;
                        $this->SharedStringIndex++;
                        continue;
                    }

                    break;
                }

            }

        }
        $Value = "";
        if( $this->SSOpen && $this->SharedStringIndex == $Index ) 
        {
            while( $this->SharedStrings->read() ) 
            {
                switch( $this->SharedStrings->name ) 
                {
                    case "t":
                        if( $this->SharedStrings->nodeType == XMLReader::END_ELEMENT ) 
                        {
                            continue;
                        }

                        $Value .= $this->SharedStrings->readString();
                        break;
                    case "si":
                        if( $this->SharedStrings->nodeType == XMLReader::END_ELEMENT ) 
                        {
                            $this->SSOpen = false;
                            $this->SSForwarded = true;
                            break 2;
                        }

                        break;
                }
            }
        }

        if( $Value ) 
        {
            $this->LastSharedStringValue = $Value;
        }

        return $Value;
    }

    private function FormatValue($Value, $Index)
    {
        if( !is_numeric($Value) ) 
        {
            return $Value;
        }

        if( isset($this->Styles[$Index]) && $this->Styles[$Index] !== false ) 
        {
            $Index = $this->Styles[$Index];
            if( $Index == 0 ) 
            {
                return $this->GeneralFormat($Value);
            }

            $Format = array(  );
            if( isset($this->ParsedFormatCache[$Index]) ) 
            {
                $Format = $this->ParsedFormatCache[$Index];
            }

            if( !$Format ) 
            {
                $Format = array( "Code" => false, "Type" => false, "Scale" => 1, "Thousands" => false, "Currency" => false );
                if( isset(self::$BuiltinFormats[$Index]) ) 
                {
                    $Format["Code"] = self::$BuiltinFormats[$Index];
                }
                else
                {
                    if( isset($this->Formats[$Index]) ) 
                    {
                        $Format["Code"] = $this->Formats[$Index];
                    }

                }

                if( $Format["Code"] ) 
                {
                    $Sections = explode(";", $Format["Code"]);
                    $Format["Code"] = $Sections[0];
                    switch( count($Sections) ) 
                    {
                        case 2:
                            if( $Value < 0 ) 
                            {
                                $Format["Code"] = $Sections[1];
                            }

                            break;
                        case 3:
                        case 4:
                            if( $Value < 0 ) 
                            {
                                $Format["Code"] = $Sections[1];
                            }
                            else
                            {
                                if( $Value == 0 ) 
                                {
                                    $Format["Code"] = $Sections[2];
                                }

                            }

                            break;
                    }
                }

                $Format["Code"] = trim(preg_replace("{^\\[[[:alpha:]]+\\]}i", "", $Format["Code"]));
                if( substr($Format["Code"], -1) == "%" ) 
                {
                    $Format["Type"] = "Percentage";
                }
                else
                {
                    if( preg_match("{^(\\[\\\$[[:alpha:]]*-[0-9A-F]*\\])*[hmsdy]}i", $Format["Code"]) ) 
                    {
                        $Format["Type"] = "DateTime";
                        $Format["Code"] = trim(preg_replace("{^(\\[\\\$[[:alpha:]]*-[0-9A-F]*\\])}i", "", $Format["Code"]));
                        $Format["Code"] = strtolower($Format["Code"]);
                        $Format["Code"] = strtr($Format["Code"], self::$DateReplacements["All"]);
                        if( strpos($Format["Code"], "A") === false ) 
                        {
                            $Format["Code"] = strtr($Format["Code"], self::$DateReplacements["24H"]);
                        }
                        else
                        {
                            $Format["Code"] = strtr($Format["Code"], self::$DateReplacements["12H"]);
                        }

                    }
                    else
                    {
                        if( $Format["Code"] == "[\$EUR ]#,##0.00_-" ) 
                        {
                            $Format["Type"] = "Euro";
                        }
                        else
                        {
                            $Format["Code"] = preg_replace("{_.}", "", $Format["Code"]);
                            $Format["Code"] = preg_replace("{\\\\}", "", $Format["Code"]);
                            $Format["Code"] = str_replace(array( "\"", "*" ), "", $Format["Code"]);
                            if( strpos($Format["Code"], "0,0") !== false || strpos($Format["Code"], "#,#") !== false ) 
                            {
                                $Format["Thousands"] = true;
                            }

                            $Format["Code"] = str_replace(array( "0,0", "#,#" ), array( "00", "##" ), $Format["Code"]);
                            $Scale = 1;
                            $Matches = array(  );
                            if( preg_match("{(0|#)(,+)}", $Format["Code"], $Matches) ) 
                            {
                                $Scale = pow(1000, strlen($Matches[2]));
                                $Format["Code"] = preg_replace(array( "{0,+}", "{#,+}" ), array( "0", "#" ), $Format["Code"]);
                            }

                            $Format["Scale"] = $Scale;
                            if( preg_match("{#?.*\\?\\/\\?}", $Format["Code"]) ) 
                            {
                                $Format["Type"] = "Fraction";
                            }
                            else
                            {
                                $Format["Code"] = str_replace("#", "", $Format["Code"]);
                                $Matches = array(  );
                                if( preg_match("{(0+)(\\.?)(0*)}", preg_replace("{\\[[^\\]]+\\]}", "", $Format["Code"]), $Matches) ) 
                                {
                                    list(, $Integer, $DecimalPoint, $Decimals) = $Matches;
                                    $Format["MinWidth"] = strlen($Integer) + strlen($DecimalPoint) + strlen($Decimals);
                                    $Format["Decimals"] = $Decimals;
                                    $Format["Precision"] = strlen($Format["Decimals"]);
                                    $Format["Pattern"] = "%0" . $Format["MinWidth"] . "." . $Format["Precision"] . "f";
                                }

                            }

                            $Matches = array(  );
                            if( preg_match("{\\[\\\$(.*)\\]}u", $Format["Code"], $Matches) ) 
                            {
                                list($CurrFormat, $CurrCode) = $Matches;
                                $CurrCode = explode("-", $CurrCode);
                                if( $CurrCode ) 
                                {
                                    $CurrCode = $CurrCode[0];
                                }

                                if( !$CurrCode ) 
                                {
                                    $CurrCode = self::$CurrencyCode;
                                }

                                $Format["Currency"] = $CurrCode;
                            }

                            $Format["Code"] = trim($Format["Code"]);
                        }

                    }

                }

                $this->ParsedFormatCache[$Index] = $Format;
            }

            if( $Format ) 
            {
                if( $Format["Code"] == "@" ) 
                {
                    return (string) $Value;
                }

                if( $Format["Type"] == "Percentage" ) 
                {
                    if( $Format["Code"] === "0%" ) 
                    {
                        $Value = round(100 * $Value, 0) . "%";
                    }
                    else
                    {
                        $Value = sprintf("%.2f%%", round(100 * $Value, 2));
                    }

                }
                else
                {
                    if( $Format["Type"] == "DateTime" ) 
                    {
                        $Days = (int) $Value;
                        if( 60 < $Days ) 
                        {
                            $Days--;
                        }

                        $Time = $Value - (int) $Value;
                        $Seconds = 0;
                        if( $Time ) 
                        {
                            $Seconds = (int) ($Time * 86400);
                        }

                        $Value = clone self::$BaseDate;
                        $Value->add(new DateInterval("P" . $Days . "D" . (($Seconds ? "T" . $Seconds . "S" : ""))));
                        if( !$this->Options["ReturnDateTimeObjects"] ) 
                        {
                            $Value = $Value->format($Format["Code"]);
                        }

                    }
                    else
                    {
                        if( $Format["Type"] == "Euro" ) 
                        {
                            $Value = "EUR " . sprintf("%1.2f", $Value);
                        }
                        else
                        {
                            if( $Format["Type"] == "Fraction" && $Value != (int) $Value ) 
                            {
                                $Integer = floor(abs($Value));
                                $Decimal = fmod(abs($Value), 1);
                                $Decimal *= pow(10, strlen($Decimal) - 2);
                                $DecimalDivisor = pow(10, strlen($Decimal));
                                if( self::$RuntimeInfo["GMPSupported"] ) 
                                {
                                    $GCD = gmp_strval(gmp_gcd($Decimal, $DecimalDivisor));
                                }
                                else
                                {
                                    $GCD = self::GCD($Decimal, $DecimalDivisor);
                                }

                                $AdjDecimal = $DecimalPart / $GCD;
                                $AdjDecimalDivisor = $DecimalDivisor / $GCD;
                                if( strpos($Format["Code"], "0") !== false || strpos($Format["Code"], "#") !== false || substr($Format["Code"], 0, 3) == "? ?" ) 
                                {
                                    $Value = ((($Value < 0 ? "-" : "")) . $Integer ? $Integer . " " : "" . $AdjDecimal . "/" . $AdjDecimalDivisor);
                                }
                                else
                                {
                                    $AdjDecimal += $Integer * $AdjDecimalDivisor;
                                    $Value = (($Value < 0 ? "-" : "")) . $AdjDecimal . "/" . $AdjDecimalDivisor;
                                }

                            }
                            else
                            {
                                $Value = $Value / $Format["Scale"];
                                if( !empty($Format["MinWidth"]) && $Format["Decimals"] ) 
                                {
                                    if( $Format["Thousands"] ) 
                                    {
                                        $Value = number_format($Value, $Format["Precision"], self::$DecimalSeparator, self::$ThousandSeparator);
                                    }
                                    else
                                    {
                                        $Value = sprintf($Format["Pattern"], $Value);
                                    }

                                    $Value = preg_replace("{(0+)(\\.?)(0*)}", $Value, $Format["Code"]);
                                }

                            }

                            if( $Format["Currency"] ) 
                            {
                                $Value = preg_replace("", $Format["Currency"], $Value);
                            }

                        }

                    }

                }

            }

            return $Value;
        }

        return $Value;
    }

    public function GeneralFormat($Value)
    {
        if( is_numeric($Value) ) 
        {
            $Value = (double) $Value;
        }

        return $Value;
    }

    public function rewind()
    {
        if( $this->Worksheet instanceof XMLReader ) 
        {
            $this->Worksheet->close();
        }
        else
        {
            $this->Worksheet = new XMLReader();
        }

        $this->Worksheet->open($this->WorksheetPath);
        $this->Valid = true;
        $this->RowOpen = false;
        $this->CurrentRow = false;
        $this->Index = 0;
    }

    public function current()
    {
        if( $this->Index == 0 && $this->CurrentRow === false ) 
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
        if( !$this->RowOpen ) 
        {
            while( $this->Valid = $this->Worksheet->read() ) 
            {
                if( $this->Worksheet->name == "row" ) 
                {
                    $RowSpans = $this->Worksheet->getAttribute("spans");
                    if( $RowSpans ) 
                    {
                        $RowSpans = explode(":", $RowSpans);
                        $CurrentRowColumnCount = $RowSpans[1];
                    }
                    else
                    {
                        $CurrentRowColumnCount = 0;
                    }

                    if( 0 < $CurrentRowColumnCount ) 
                    {
                        $this->CurrentRow = array_fill(0, $CurrentRowColumnCount, "");
                    }

                    $this->RowOpen = true;
                    break;
                }

            }
        }

        if( $this->RowOpen ) 
        {
            $MaxIndex = 0;
            $CellCount = 0;
            $CellHasSharedString = false;
            while( $this->Valid = $this->Worksheet->read() ) 
            {
                switch( $this->Worksheet->name ) 
                {
                    case "row":
                        if( $this->Worksheet->nodeType == XMLReader::END_ELEMENT ) 
                        {
                            $this->RowOpen = false;
                            break 2;
                        }

                        break;
                    case "c":
                        if( $this->Worksheet->nodeType == XMLReader::END_ELEMENT ) 
                        {
                            continue;
                        }

                        $StyleId = (int) $this->Worksheet->getAttribute("s");
                        $Index = $this->Worksheet->getAttribute("r");
                        $Letter = preg_replace("{[^[:alpha:]]}S", "", $Index);
                        $Index = self::IndexFromColumnLetter($Letter);
                        if( $this->Worksheet->getAttribute("t") == self::CELL_TYPE_SHARED_STR ) 
                        {
                            $CellHasSharedString = true;
                        }
                        else
                        {
                            $CellHasSharedString = false;
                        }

                        $this->CurrentRow[$Index] = "";
                        $CellCount++;
                        if( $MaxIndex < $Index ) 
                        {
                            $MaxIndex = $Index;
                        }

                        break;
                    case "v":
                    case "is":
                        if( $this->Worksheet->nodeType == XMLReader::END_ELEMENT ) 
                        {
                            continue;
                        }

                        $Value = $this->Worksheet->readString();
                        if( $CellHasSharedString ) 
                        {
                            $Value = $this->GetSharedString($Value);
                        }

                        if( $Value !== "" && $StyleId && isset($this->Styles[$StyleId]) ) 
                        {
                            $Value = $this->FormatValue($Value, $StyleId);
                        }
                        else
                        {
                            if( $Value ) 
                            {
                                $Value = $this->GeneralFormat($Value);
                            }

                        }

                        $this->CurrentRow[$Index] = $Value;
                        break;
                }
            }
            if( $CellCount < $MaxIndex + 1 ) 
            {
                $this->CurrentRow = $this->CurrentRow + array_fill(0, $MaxIndex + 1, "");
                ksort($this->CurrentRow);
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

    public static function IndexFromColumnLetter($Letter)
    {
        $Powers = array(  );
        $Letter = strtoupper($Letter);
        $Result = 0;
        $i = strlen($Letter) - 1;
        for( $j = 0; 0 <= $i; $j++ ) 
        {
            $Ord = ord($Letter[$i]) - 64;
            if( 26 < $Ord ) 
            {
                return false;
            }

            $Result += $Ord * pow(26, $j);
            $i--;
        }
        return $Result - 1;
    }

    public static function GCD($A, $B)
    {
        $A = abs($A);
        $B = abs($B);
        if( $A + $B == 0 ) 
        {
            return 0;
        }

        $C = 1;
        while( 0 < $A ) 
        {
            $C = $A;
            $A = $B % $A;
            $B = $C;
        }
        return $C;
    }

}


