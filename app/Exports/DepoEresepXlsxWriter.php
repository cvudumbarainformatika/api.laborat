<?php

namespace App\Exports;

use RuntimeException;
use XMLWriter;
use ZipArchive;

/**
 * Writer XLSX sederhana yang menulis sheet langsung ke XML/ZIP.
 * PhpSpreadsheet menyimpan index setiap cell di RAM, sehingga tidak cocok
 * untuk export rincian puluhan ribu baris pada limit PHP 128 MB.
 */
class DepoEresepXlsxWriter
{
    public static function write(DepoEresepExport $export): string
    {
        $cacheDir = storage_path('framework/cache');
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            throw new RuntimeException('Direktori cache export tidak dapat dibuat.');
        }

        $token = bin2hex(random_bytes(8));
        $workDir = $cacheDir . '/depo-eresep-' . $token;
        if (!mkdir($workDir, 0775, true)) {
            throw new RuntimeException('Direktori sementara export tidak dapat dibuat.');
        }

        $sheetPath = $workDir . '/sheet1.xml';
        $xlsxPath = $cacheDir . '/list-e-resep-' . $token . '.xlsx';

        try {
            self::writeSheet($sheetPath, $export);
            self::zipWorkbook($xlsxPath, $sheetPath);
            return $xlsxPath;
        } finally {
            if (is_file($sheetPath)) {
                unlink($sheetPath);
            }
            foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml'] as $unused) {
                // XML statis ditulis langsung ke ZIP, tidak ada file yang perlu dibersihkan.
            }
            if (is_dir($workDir)) {
                rmdir($workDir);
            }
        }
    }

    private static function writeSheet(string $path, DepoEresepExport $export): void
    {
        $writer = new XMLWriter();
        if (!$writer->openUri($path)) {
            throw new RuntimeException('Sheet XLSX tidak dapat dibuat.');
        }
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $writer->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $writer->startElement('sheetData');

        $rowNumber = 1;
        self::writeRow($writer, $rowNumber++, $export->headings(), true, $export->headings());
        foreach ($export->generator() as $row) {
            self::writeRow($writer, $rowNumber++, $row, false, $export->headings());
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private static function writeRow(XMLWriter $writer, int $rowNumber, array $values, bool $heading, array $headings): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        foreach (array_values($values) as $index => $value) {
            $coordinate = self::columnName($index + 1) . $rowNumber;
            $writer->startElement('c');
            $writer->writeAttribute('r', $coordinate);
            if ($heading) {
                $writer->writeAttribute('s', '1');
                $writer->writeAttribute('t', 'inlineStr');
                $writer->startElement('is');
                $writer->writeElement('t', (string) $value);
                $writer->endElement();
            } elseif (self::isNumericColumn($index, count($headings)) && is_numeric($value)) {
                $writer->writeElement('v', (string) $value);
            } else {
                $writer->writeAttribute('t', 'inlineStr');
                $writer->startElement('is');
                $writer->writeElement('t', (string) ($value ?? ''));
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
    }

    private static function isNumericColumn(int $index, int $columnCount): bool
    {
        if ($index === 0) {
            return true;
        }
        return $columnCount > 16 && in_array($index, [21, 22, 23], true);
    }

    private static function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $number = intdiv($number - 1, 26);
        }
        return $name;
    }

    private static function zipWorkbook(string $xlsxPath, string $sheetPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuat.');
        }
        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>
XML);
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyFont="1"/></cellXfs></styleSheet>
XML);
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();
    }
}
