<?php

class SpreadsheetReader {

    public static function parseFile($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File not found or not readable: $filePath");
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new Exception("Cannot open file: $filePath");
        }
        $header = fread($handle, 4);
        fclose($handle);

        if ($header === "PK\x03\x04" || $header === "PK\x05\x06") {
            return self::parseXLSX($filePath);
        }

        if ($header === "\xD0\xCF\x11\xE0") {
            return self::parseXLS($filePath);
        }

        throw new Exception("Unsupported file format (magic: " . bin2hex($header) . ")");
    }

    // ──────────────────────────────────────────────
    //  XLSX (Open XML) Parser
    // ──────────────────────────────────────────────

    public static function parseXLSX($filePath) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Cannot open XLSX file (not a valid ZIP archive)");
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
            }
        }

        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXmlContent === false) {
            throw new Exception("No worksheet found in XLSX file");
        }

        $xml = simplexml_load_string($sheetXmlContent);
        if ($xml === false) {
            throw new Exception("Cannot parse worksheet data");
        }

        $ns = $xml->getNamespaces(true);
        $mainNs = $ns[''] ?? '';

        $sheetData = $xml->sheetData;
        if ($mainNs && !$sheetData) {
            $sheetData = $xml->children($mainNs)->sheetData;
        }
        if (!$sheetData) {
            throw new Exception("No sheet data found");
        }

        $rows = $sheetData->row;
        if (!$rows || count($rows) === 0) {
            throw new Exception("No rows found in worksheet");
        }

        $data = [];
        $headers = [];
        $rowIndex = 0;

        foreach ($rows as $row) {
            $cells = $mainNs ? $row->children($mainNs) : $row->children();
            $cellList = [];
            foreach ($cells->c as $cell) {
                $cellList[] = $cell;
            }

            $rowData = [];
            foreach ($cellList as $cell) {
                $type = (string)$cell['t'];
                if ($type === 's' && isset($cell->v)) {
                    $idx = (int)$cell->v;
                    $rowData[] = $sharedStrings[$idx] ?? '';
                } elseif (isset($cell->v)) {
                    $rowData[] = (string)$cell->v;
                } else {
                    $rowData[] = '';
                }
            }

            if ($rowIndex === 0) {
                if (!empty($rowData)) {
                    $rowData[0] = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $rowData[0]);
                }
                $headers = array_map(function($h) { return trim(strtolower($h)); }, $rowData);
            } else {
                if (!empty($rowData)) {
                    $assoc = [];
                    foreach ($headers as $i => $h) {
                        $assoc[$h] = $rowData[$i] ?? '';
                    }
                    if (!isset($assoc['password'])) $assoc['password'] = '';
                    if (!isset($assoc['phone'])) $assoc['phone'] = '';
                    if (!isset($assoc['parent_phone'])) $assoc['parent_phone'] = '';

                    $un = trim($assoc['username'] ?? '');
                    $fn = trim($assoc['first_name'] ?? '');
                    $ln = trim($assoc['last_name'] ?? '');
                    if (!empty($un) || !empty($fn) || !empty($ln)) {
                        $data[] = $assoc;
                    }
                }
            }
            $rowIndex++;
        }

        return $data;
    }

    // ──────────────────────────────────────────────
    //  XLS (Binary OLE2/BIFF) Parser
    // ──────────────────────────────────────────────

    public static function parseXLS($filePath) {
        $ole = new OLEReader($filePath);
        $wbStream = $ole->getWorkbookStream();
        if ($wbStream === null) {
            throw new Exception("No Workbook stream found in XLS file");
        }
        $biff = new BIFFParser($wbStream);
        return $biff->parse();
    }

    // ──────────────────────────────────────────────
    //  XLS Template Generator
    // ──────────────────────────────────────────────

    public static function generateXLSX(array $headers, array $rows): string {
        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
            throw new Exception("Cannot create XLSX file");
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets>
</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/></font>
  </fonts>
  <fills count="2">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
  </fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
  </cellXfs>
</styleSheet>');

        $allStrings = array_merge($headers);
        foreach ($rows as $row) {
            $allStrings = array_merge($allStrings, array_values($row));
        }
        $allStrings = array_values(array_unique($allStrings));
        $stringIndex = array_flip($allStrings);

        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($allStrings) . '" uniqueCount="' . count($allStrings) . '">';
        foreach ($allStrings as $s) {
            $ssXml .= '<si><t>' . htmlspecialchars($s, ENT_XML1) . '</t></si>';
        }
        $ssXml .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>';
        $colLetters = ['A','B','C','D','E','F','G','H','I','J'];

        foreach ([$headers] as $ri => $rowData) {
            $sheetXml .= '<row r="' . ($ri + 1) . '">';
            foreach ($rowData as $ci => $val) {
                $col = $colLetters[$ci] ?? 'A';
                $idx = $stringIndex[$val];
                $sheetXml .= '<c r="' . $col . ($ri + 1) . '" t="s"><v>' . $idx . '</v></c>';
            }
            $sheetXml .= '</row>';
        }

        foreach ($rows as $ri => $rowData) {
            $rn = $ri + 2;
            $sheetXml .= '<row r="' . $rn . '">';
            foreach ($rowData as $ci => $val) {
                $col = $colLetters[$ci] ?? 'A';
                $idx = $stringIndex[$val];
                $sheetXml .= '<c r="' . $col . $rn . '" t="s"><v>' . $idx . '</v></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        $content = file_get_contents($tmp);
        unlink($tmp);
        return $content;
    }
}

// ──────────────────────────────────────────────
//  OLE2 (Compound Document) Reader
// ──────────────────────────────────────────────

class OLEReader {
    private $data;
    private $numBigBlockDepot;
    private $rootStartBlock;

    private const OLE_IDENTIFIER = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
    private const SMALL_BLOCK_SIZE = 64;
    private const BIG_BLOCK_SIZE = 512;

    public function __construct($filePath) {
        $this->data = file_get_contents($filePath);
        if ($this->data === false) {
            throw new Exception("Cannot read file");
        }
        $this->parseHeader();

        // We'll find and return the data lazily in getWorkbookStream
    }

    private function parseHeader() {
        $ident = substr($this->data, 0, 8);
        if ($ident !== self::OLE_IDENTIFIER) {
            throw new Exception("Not a valid OLE2 file");
        }

        $this->numBigBlockDepot = self::getInt4($this->data, 44);
        $this->rootStartBlock = self::getInt4($this->data, 68);
    }

    public function getWorkbookStream() {
        $rootEntry = $this->readDirectoryEntry($this->rootStartBlock);
        $satReader = new OLESatReader($this->data);
        $sat = $satReader->readStandardSat();

        // Find the Workbook or Book stream in the directory
        $workbookStart = null;
        $workbookSize = 0;

        $entry = $rootEntry;
        $child = $entry['child'] ?? -2;
        if ($child !== -2) {
            // Navigate the directory tree: root -> * -> Workbook
            $entries = $this->readAllDirectoryEntries($sat);
            foreach ($entries as $e) {
                $name = $e['name'];
                if ($name === 'Workbook' || $name === 'Book') {
                    $workbookStart = $e['startBlock'];
                    $workbookSize = $e['size'];
                    break;
                }
            }
        }

        if ($workbookStart === null) {
            return null;
        }

        return $this->readStream($workbookStart, $workbookSize, $sat);
    }

    private function readAllDirectoryEntries($sat) {
        $entries = [];
        $block = $this->rootStartBlock;
        while ($block !== -2) {
            $offset = ($block + 1) * self::BIG_BLOCK_SIZE;
            for ($i = 0; $i < 4; $i++) {
                $entryOffset = $offset + $i * 128;
                if ($entryOffset + 128 > strlen($this->data)) break;
                $entry = $this->parseDirectoryEntry($entryOffset);
                if ($entry['name'] === '' || $entry['type'] === 0) continue;
                $entries[] = $entry;
            }
            $block = $sat[$block] ?? -2;
        }
        return $entries;
    }

    private function readDirectoryEntry($block) {
        $offset = ($block + 1) * self::BIG_BLOCK_SIZE;
        return $this->parseDirectoryEntry($offset);
    }

    private function parseDirectoryEntry($offset) {
        $name = '';
        $nameRaw = substr($this->data, $offset, 64);
        $nameLen = self::getInt2($this->data, $offset + 64);
        if ($nameLen > 0) {
            $name = mb_convert_encoding(substr($nameRaw, 0, $nameLen - 2), 'UTF-8', 'UTF-16LE');
        }

        return [
            'name' => $name,
            'type' => ord($this->data[$offset + 66]),
            'child' => self::getInt4($this->data, $offset + 76),
            'left' => self::getInt4($this->data, $offset + 68),
            'right' => self::getInt4($this->data, $offset + 72),
            'startBlock' => self::getInt4($this->data, $offset + 116),
            'size' => self::getInt4($this->data, $offset + 120),
        ];
    }

    private function readStream($startBlock, $size, $sat) {
        $data = '';
        $remaining = $size;
        $block = $startBlock;
        while ($block !== -2 && $remaining > 0) {
            $offset = ($block + 1) * self::BIG_BLOCK_SIZE;
            $chunk = substr($this->data, $offset, min($remaining, self::BIG_BLOCK_SIZE));
            $data .= $chunk;
            $remaining -= self::BIG_BLOCK_SIZE;
            $block = $sat[$block] ?? -2;
        }
        return $data;
    }

    private static function getInt4($data, $offset) {
        $bytes = substr($data, $offset, 4);
        if (strlen($bytes) < 4) return 0;
        return unpack('V', $bytes)[1];
    }

    private static function getInt2($data, $offset) {
        $bytes = substr($data, $offset, 2);
        if (strlen($bytes) < 2) return 0;
        return unpack('v', $bytes)[1];
    }
}

// ──────────────────────────────────────────────
//  OLE SAT (Sector Allocation Table) Reader
// ──────────────────────────────────────────────

class OLESatReader {
    private $data;

    public const BIG_BLOCK_SIZE = 512;

    public function __construct($data) {
        $this->data = $data;
    }

    public function readStandardSat() {
        $numSat = self::getInt4($this->data, 44);
        $sat = [];

        // Read the first 109 SAT entries from the header
        for ($i = 0; $i < 109; $i++) {
            $entry = self::getInt4($this->data, 76 + $i * 4);
            if ($entry === -1 || $entry === 0xFFFFFFFE) break;
            $sat[] = $entry;
        }

        // Read additional SAT blocks via the XSAT chain
        $xsatStart = self::getInt4($this->data, 60);
        $xsatCount = self::getInt4($this->data, 56);
        $xsatBlock = $xsatStart;

        for ($xs = 0; $xs < $xsatCount; $xs++) {
            if ($xsatBlock === -2 || $xsatBlock === -1) break;
            $offset = ($xsatBlock + 1) * self::BIG_BLOCK_SIZE;
            for ($i = 0; $i < 127; $i++) {
                $entry = self::getInt4($this->data, $offset + $i * 4);
                if ($entry === -1 || $entry === 0xFFFFFFFE) break;
                $sat[] = $entry;
                if ($entry === -2) break;
            }
            // Read next XSAT block from the last entry
            $nextXsat = self::getInt4($this->data, $offset + 127 * 4);
            $xsatBlock = ($nextXsat === -1 || $nextXsat === 0xFFFFFFFE) ? -2 : $nextXsat;
        }

        return $sat;
    }

    private static function getInt4($data, $offset) {
        $bytes = substr($data, $offset, 4);
        if (strlen($bytes) < 4) return -1;
        $val = unpack('V', $bytes)[1];
        // Handle signed ints
        if ($val >= 0x80000000) {
            return $val - 0x100000000;
        }
        if ($val >= 0xFFFFFFF0) {
            return $val - 0x100000000;
        }
        return $val;
    }
}

// ──────────────────────────────────────────────
//  BIFF8 (Binary Excel) Parser
// ──────────────────────────────────────────────

class BIFFParser {
    private $data;
    private $pos = 0;
    private $sharedStrings = [];
    private $dataRows = [];
    private $headers = [];

    // BIFF record types
    private const BOF = 0x0809;
    private const EOF = 0x000A;
    private const SST = 0x00FC;
    private const LABELSST = 0x00FD;
    private const LABEL = 0x0204;
    private const RK = 0x027E;
    private const MULRK = 0x00BD;
    private const NUMBER = 0x0203;
    private const ROW = 0x0208;
    private const DIMENSION = 0x0200;
    private const BOOLERR = 0x0205;
    private const BLANK = 0x0201;
    private const MULBLANK = 0x00BE;
    private const STRING = 0x0207;
    private const FORMULA = 0x0006;
    private const FORMULA_ALT = 0x0406;
    private const SHEET = 0x0085;
    private const CONTINUE = 0x003C;
    private const INDEX = 0x020B;
    private const MERGEDCELLS = 0x00E5;

    public function __construct($workbookData) {
        $this->data = $workbookData;
    }

    public function parse() {
        $currentSheet = -1;
        $sheetIndex = 0;
        $inSheet = false;
        $colMax = 0;

        while ($this->pos < strlen($this->data)) {
            $recType = $this->getUInt16();
            $recLen = $this->getUInt16();
            $recEnd = $this->pos + $recLen;

            if ($recType === self::BOF) {
                // Check if this is a sheet BOF (substream type at pos+4)
                if ($recLen >= 4) {
                    $substreamType = $this->getUInt16At($this->pos);
                    if ($substreamType === 0x0010) {
                        // Worksheet BOF
                        $inSheet = true;
                        $currentSheet = $sheetIndex;
                    } elseif ($substreamType === 0x0005) {
                        // Workbook globals BOF
                        $inSheet = false;
                    }
                }
            } elseif ($recType === self::SHEET && $recLen >= 6) {
                $sheetIndex++;
            } elseif ($recType === self::SST) {
                $this->readSST($recLen);
            } elseif ($recType === self::CONTINUE && !empty($this->sharedStrings)) {
                // CONTINUE records can follow SST; skip them for simplicity
            } elseif ($inSheet && $recType === self::DIMENSION) {
                // Skip dimension, just note we're in a sheet
            } elseif ($inSheet && $recType === self::ROW) {
                // Row record - we don't need to process it
            } elseif ($inSheet && $recType === self::INDEX) {
                // Skip index
            } elseif ($inSheet && $recType === self::LABELSST) {
                $this->readLabelSST();
            } elseif ($inSheet && $recType === self::LABEL) {
                $this->readLabel();
            } elseif ($inSheet && $recType === self::RK) {
                $this->readRK();
            } elseif ($inSheet && $recType === self::MULRK) {
                $this->readMulRK($recLen);
            } elseif ($inSheet && $recType === self::NUMBER) {
                $this->readNumber();
            } elseif ($inSheet && $recType === self::BOOLERR) {
                $this->readBoolErr();
            } elseif ($inSheet && $recType === self::BLANK) {
                // Skip blank cells - we don't need them
            } elseif ($inSheet && $recType === self::MULBLANK) {
                // Skip multiple blanks
            } elseif ($inSheet && $recType === self::FORMULA || $inSheet && $recType === self::FORMULA_ALT) {
                $this->readFormula();
            } elseif ($inSheet && $recType === self::STRING) {
                $this->readString($recLen);
            } elseif ($inSheet && $recType === self::MERGEDCELLS) {
                // Skip merged cells
            } elseif ($recType === self::EOF) {
                if ($inSheet) {
                    // End of current sheet - we only parse the first sheet
                    break;
                }
            }

            $this->pos = max($this->pos, $recEnd);
        }

        return $this->buildResult();
    }

    private function buildResult() {
        if (empty($this->dataRows)) {
            throw new Exception("No data found in XLS file");
        }

        // Sort rows by row index
        ksort($this->dataRows);

        $result = [];
        $headerRow = true;

        foreach ($this->dataRows as $rowNum => $cells) {
            ksort($cells);

            if ($headerRow) {
                $this->headers = [];
                foreach ($cells as $col => $val) {
                    $this->headers[$col] = trim(strtolower($val));
                    $this->headers[$col] = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $this->headers[$col]);
                }
                $headerRow = false;
                continue;
            }

            $assoc = [];
            foreach ($this->headers as $col => $h) {
                $assoc[$h] = $cells[$col] ?? '';
            }
            if (!isset($assoc['password'])) $assoc['password'] = '';
            if (!isset($assoc['phone'])) $assoc['phone'] = '';
            if (!isset($assoc['parent_phone'])) $assoc['parent_phone'] = '';

            $un = trim($assoc['username'] ?? '');
            $fn = trim($assoc['first_name'] ?? '');
            $ln = trim($assoc['last_name'] ?? '');
            if (!empty($un) || !empty($fn) || !empty($ln)) {
                $result[] = $assoc;
            }
        }

        return $result;
    }

    public function readSST($recLen) {
        $end = $this->pos + $recLen;
        if ($this->pos + 8 > $end) return;

        $numStrings = $this->getUInt32(); // total strings in SST
        $numUnique  = $this->getUInt32(); // number of unique strings

        for ($i = 0; $i < $numUnique && $this->pos < $end; $i++) {
            if ($this->pos + 2 > $end) break;
            $strLen = $this->getUInt16();

            if ($strLen === 0) {
                $this->sharedStrings[] = '';
                continue;
            }

            if ($this->pos + 1 > $end) break;
            $optionFlags = ord($this->data[$this->pos]);
            $this->pos++;
            $isRich = ($optionFlags & 0x08) !== 0;
            $isAsian = ($optionFlags & 0x04) !== 0;

            if ($isRich) {
                if ($this->pos + 2 > $end) break;
                $this->pos += 2; // skip number of rich text runs
            }
            if ($isAsian) {
                if ($this->pos + 4 > $end) break;
                $this->pos += 4; // skip size of Asian phonetic data
            }

            $isUnicode = ($optionFlags & 0x01) !== 0;
            if ($isUnicode) {
                $byteLen = $strLen * 2;
                if ($this->pos + $byteLen > $end) {
                    // Try to handle truncated string
                    $byteLen = $end - $this->pos;
                }
                $raw = substr($this->data, $this->pos, $byteLen);
                $str = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
                $this->pos += $byteLen;
            } else {
                $raw = substr($this->data, $this->pos, $strLen);
                $str = $raw; // Latin-1, compatible with UTF-8
                $this->pos += $strLen;
            }

            $this->sharedStrings[] = $str;
        }
    }

    private function readLabelSST() {
        if ($this->pos + 6 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16(); // index into XF table (skip)
        $sstIndex = $this->getUInt32();

        $val = $this->sharedStrings[$sstIndex] ?? '';
        $this->dataRows[$row][$col] = $val;
    }

    private function readLabel() {
        if ($this->pos + 6 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16();
        $strLen = $this->getUInt16();

        if ($strLen === 0) {
            $this->dataRows[$row][$col] = '';
            return;
        }

        if ($this->pos >= strlen($this->data)) return;
        $optionFlags = ord($this->data[$this->pos]);
        $this->pos++;
        $isUnicode = ($optionFlags & 0x01) !== 0;

        if ($isUnicode) {
            $byteLen = $strLen * 2;
            $raw = substr($this->data, $this->pos, min($byteLen, strlen($this->data) - $this->pos));
            $val = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
            $this->pos += $byteLen;
        } else {
            $val = substr($this->data, $this->pos, $strLen);
            $this->pos += $strLen;
        }

        $this->dataRows[$row][$col] = $val;
    }

    private function readString($recLen) {
        // Inline string (follows a FORMULA record)
        $savePos = $this->pos;
        $strLen = $this->getUInt16();

        if ($strLen === 0) {
            // We need to associate this with the last formula cell
            return;
        }

        if ($this->pos >= strlen($this->data)) return;
        $optionFlags = ord($this->data[$this->pos]);
        $this->pos++;
        $isUnicode = ($optionFlags & 0x01) !== 0;

        if ($isUnicode) {
            $byteLen = $strLen * 2;
            $raw = substr($this->data, $this->pos, min($byteLen, strlen($this->data) - $this->pos));
            $val = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
            $this->pos += $byteLen;
        } else {
            $val = substr($this->data, $this->pos, $strLen);
            $this->pos += $strLen;
        }

        // We don't track which formula this belongs to, so store as best-effort
        $this->pos = $savePos + $recLen;
    }

    private function readRK() {
        if ($this->pos + 6 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16();
        $rkValue = $this->getUInt32();

        $val = $this->decodeRK($rkValue);
        $this->dataRows[$row][$col] = $val;
    }

    private function readMulRK($recLen) {
        if ($this->pos + 6 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $firstCol = $this->getUInt16();

        $end = $this->pos + $recLen;
        $col = $firstCol;

        while ($this->pos + 8 <= $end - 2) { // last 2 bytes = lastCol
            $xf = $this->getUInt16();
            $rkValue = $this->getUInt32();

            $val = $this->decodeRK($rkValue);
            $this->dataRows[$row][$col] = $val;
            $col++;
        }

        // Read last column index
        if ($this->pos + 2 <= $end) {
            $lastCol = $this->getUInt16();
        }
    }

    private function readNumber() {
        if ($this->pos + 10 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16();
        $val = $this->getFloat64();

        $this->dataRows[$row][$col] = $val;
    }

    private function readBoolErr() {
        if ($this->pos + 8 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16();
        $val = ord($this->data[$this->pos]);
        $this->pos++;
        $isError = ord($this->data[$this->pos]);
        $this->pos++;

        $this->dataRows[$row][$col] = $isError ? "#ERR" : ($val ? "TRUE" : "FALSE");
    }

    private function readFormula() {
        if ($this->pos + 12 > strlen($this->data)) return;
        $row = $this->getUInt16();
        $col = $this->getUInt16();
        $xf  = $this->getUInt16();

        // Read the formula result (8 bytes at current position)
        $resultBytes = substr($this->data, $this->pos, 8);
        $this->pos += 8;

        // Check if result is a string (result byte is 0xFF)
        if (ord($resultBytes[0]) === 0xFF && $resultBytes[6] === "\xFF" && $resultBytes[7] === "\xFF") {
            // String result - will come in a subsequent STRING record
            // Store a placeholder
            $this->dataRows[$row][$col] = '';
        } elseif (ord($resultBytes[6]) === 0xFF && ord($resultBytes[7]) === 0xFF) {
            // Boolean or error
            if (ord($resultBytes[0]) === 0) {
                $this->dataRows[$row][$col] = (ord($resultBytes[2]) !== 0) ? "TRUE" : "FALSE";
            } else {
                $this->dataRows[$row][$col] = "#ERR";
            }
        } else {
            // IEEE float
            $val = $this->decodeFloat64($resultBytes);
            $this->dataRows[$row][$col] = $val;
        }
    }

    private function decodeRK($rkValue) {
        $isInteger = ($rkValue & 0x02) !== 0;
        $is100x   = ($rkValue & 0x01) !== 0;

        if ($isInteger) {
            $val = ($rkValue >> 2) | (($rkValue & 0x80000000) ? ~((1 << 30) - 1) : 0);
            if ($is100x) {
                return (int)($val / 100);
            }
            return (float)$val;
        } else {
            // IEEE 754 floating point number stored in top 30 bits
            $sign = ($rkValue >> 31) & 0x01;
            $exponent = (($rkValue >> 23) & 0xFF) - 127 + 1023;
            $mantissa = ($rkValue & 0x007FFFFF) << 29;

            $bits = ($sign << 63) | ($exponent << 52) | $mantissa;
            $val = $this->decodeFloat64FromBits($bits);
            if ($is100x) {
                return $val / 100;
            }
            return $val;
        }
    }

    private function decodeFloat64FromBits($bits) {
        // Unpack a 64-bit double from its bit representation
        $bytes = pack('J', $bits); // J = unsigned long long (64-bit, big endian)
        // Re-pack as little-endian double
        $leBytes = '';
        for ($i = 7; $i >= 0; $i--) {
            $leBytes .= $bytes[$i];
        }
        return unpack('d', $leBytes)[1];
    }

    private function decodeFloat64($bytes) {
        if (strlen($bytes) < 8) return 0.0;
        return unpack('d', $bytes)[1];
    }

    private function getFloat64() {
        $bytes = substr($this->data, $this->pos, 8);
        $this->pos += 8;
        if (strlen($bytes) < 8) return 0.0;
        return unpack('d', $bytes)[1];
    }

    private function getUInt16() {
        if ($this->pos + 2 > strlen($this->data)) return 0;
        $v = unpack('v', substr($this->data, $this->pos, 2))[1];
        $this->pos += 2;
        return $v;
    }

    private function getUInt32() {
        if ($this->pos + 4 > strlen($this->data)) return 0;
        $v = unpack('V', substr($this->data, $this->pos, 4))[1];
        $this->pos += 4;
        return $v;
    }

    private function getUInt16At($pos) {
        if ($pos + 2 > strlen($this->data)) return 0;
        return unpack('v', substr($this->data, $pos, 2))[1];
    }
}
