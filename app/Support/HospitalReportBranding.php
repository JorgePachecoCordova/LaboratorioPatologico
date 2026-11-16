<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Encabezado institucional e imagen incrustada, sin enlaces externos. */
final class HospitalReportBranding
{
    public static function apply(ZipArchive $zip, bool $compact = false): void
    {
        // ZipArchive solo permite leer las entradas nuevas después de guardar.
        $path = $zip->filename;
        $zip->close();
        if ($zip->open($path) !== true) throw new \RuntimeException('No se pudo preparar el encabezado del reporte.');
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $doc = new DOMDocument();
        $doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('s', $ns);
        $lastColumn = 'B';
        $lastRow = 1;
        foreach ($xpath->query('//s:c') as $cell) {
            preg_match('/([A-Z]+)(\d+)/', $cell->getAttribute('r'), $match);
            if (strlen($match[1]) > strlen($lastColumn) || (strlen($match[1]) === strlen($lastColumn) && strcmp($match[1], $lastColumn) > 0)) $lastColumn = $match[1];
            $lastRow = max($lastRow, (int) $match[2] + 4);
        }
        // Reserve space for the logo across existing columns without changing
        // the widths of the report's data table.
        $columnIndex = static function (string $name): int {
            $index = 0;
            foreach (str_split($name) as $letter) $index = $index * 26 + ord($letter) - 64;
            return $index;
        };
        $columnName = static function (int $index): string {
            $name = '';
            while ($index > 0) { $index--; $name = chr(65 + $index % 26).$name; $index = intdiv($index, 26); }
            return $name;
        };
        $columnCount = $columnIndex($lastColumn);
        $widths = array_fill(1, $columnCount, 64.0);
        foreach ($xpath->query('//s:cols/s:col') as $column) {
            for ($i = max(1, (int) $column->getAttribute('min')); $i <= min($columnCount, (int) $column->getAttribute('max')); $i++) {
                $widths[$i] = (float) $column->getAttribute('width') * 7 + 5;
            }
        }
        $pageWidth = $lastColumn === 'B' ? 700 : 1020;
        $printScale = min(1, $pageWidth / max(1, array_sum($widths)));
        $targetLogoSize = min(240, max(144, (int) round(144 / $printScale)));
        if ($compact) $targetLogoSize = 48;
        $reservedWidth = 0;
        $logoColumns = 0;
        do {
            $reservedWidth += $widths[++$logoColumns];
        } while ($reservedWidth < $targetLogoSize + 20 && $logoColumns < $columnCount - 1);
        $logoSize = max(40, min($targetLogoSize, (int) $reservedWidth - 16));
        $textColumn = $columnName($logoColumns + 1);
        $headerRowHeight = (int) ceil(($logoSize + 16) * .75 / 3);
        $stackedHeader = array_sum($widths) < 520;
        $headerRows = $stackedHeader ? 5 : 4;
        if ($stackedHeader) { $reservedWidth = array_sum($widths); $textColumn = 'A'; $lastRow++; }

        $styles = new DOMDocument();
        $styles->loadXML($zip->getFromName('xl/styles.xml'));
        $formats = $styles->getElementsByTagName('cellXfs')->item(0);
        $fonts = $styles->getElementsByTagName('fonts')->item(0);
        $headerStyles = [];
        foreach ($compact ? [12, 10, 9] : [18, 14, 11] as $size) {
            $fontId = $fonts->getElementsByTagName('font')->length;
            $fragment = $styles->createDocumentFragment();
            $fragment->appendXML('<font xmlns="'.$ns.'"><b/><sz val="'.$size.'"/><color rgb="FF175D3B"/><name val="Calibri"/></font>');
            $fonts->appendChild($fragment);
            $headerStyles[] = $formats->getElementsByTagName('xf')->length;
            $fragment = $styles->createDocumentFragment();
            $fragment->appendXML('<xf xmlns="'.$ns.'" numFmtId="0" fontId="'.$fontId.'" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="'.($stackedHeader ? 'center' : 'left').'" vertical="center" wrapText="1" indent="'.($stackedHeader ? '0' : '1').'"/></xf>');
            $formats->appendChild($fragment);
        }
        $fonts->setAttribute('count', (string) $fonts->getElementsByTagName('font')->length);
        $formats->setAttribute('count', (string) $formats->getElementsByTagName('xf')->length);
        $shift = static fn (string $ref): string => preg_replace_callback('/([A-Z]+)(\d+)/', static fn ($m) => $m[1].((int) $m[2] + $headerRows), $ref);
        foreach ($xpath->query('//s:row') as $row) $row->setAttribute('r', (string) ((int) $row->getAttribute('r') + $headerRows));
        foreach ($xpath->query('//s:c') as $cell) $cell->setAttribute('r', $shift($cell->getAttribute('r')));
        foreach ($xpath->query('//s:mergeCell | //s:autoFilter') as $node) $node->setAttribute('ref', $shift($node->getAttribute('ref')));
        foreach ($xpath->query('//s:pane') as $pane) {
            $pane->setAttribute('ySplit', (string) ((int) $pane->getAttribute('ySplit') + $headerRows));
            $pane->setAttribute('topLeftCell', $shift($pane->getAttribute('topLeftCell')));
        }
        foreach ($xpath->query('//s:sheetView') as $view) $view->setAttribute('showGridLines', '0');
        $data = $xpath->query('//s:sheetData')->item(0);
        $header = $stackedHeader ? '<row xmlns="'.$ns.'" r="1" ht="'.ceil(($logoSize + 16)*.75).'" customHeight="1"/>' : '';
        foreach ([1 => 'HOSPITAL DANIEL BRACAMONTE', 2 => 'LABORATORIO DE PATOLOGÍA', 3 => 'Potosí · Bolivia'] as $r => $text) {
            $rowNumber = $r + ($stackedHeader ? 1 : 0);
            $height = $stackedHeader ? [1 => 42, 2 => 32, 3 => 24][$r] : $headerRowHeight;
            $header .= '<row xmlns="'.$ns.'" r="'.$rowNumber.'" ht="'.$height.'" customHeight="1"><c r="'.$textColumn.$rowNumber.'" s="'.$headerStyles[$r-1].'" t="inlineStr"><is><t>'.$text.'</t></is></c></row>';
        }
        $header .= '<row xmlns="'.$ns.'" r="'.$headerRows.'" ht="9" customHeight="1"/>';
        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML($header);
        $data->insertBefore($fragment, $data->firstChild);
        $merges = $xpath->query('//s:mergeCells')->item(0);
        if (!$merges) { $merges = $doc->createElementNS($ns, 'mergeCells'); $doc->documentElement->insertBefore($merges, $xpath->query('//s:pageMargins | //s:pageSetup')->item(0)); }
        $headerMerges = $stackedHeader ? ['A1:'.$lastColumn.'1', 'A2:'.$lastColumn.'2', 'A3:'.$lastColumn.'3', 'A4:'.$lastColumn.'4']
            : ['A1:'.$columnName($logoColumns).'3', ...($textColumn !== $lastColumn ? [$textColumn.'1:'.$lastColumn.'1', $textColumn.'2:'.$lastColumn.'2', $textColumn.'3:'.$lastColumn.'3'] : [])];
        foreach ($headerMerges as $ref) {
            $merge = $doc->createElementNS($ns, 'mergeCell');
            $merge->setAttribute('ref', $ref);
            $merges->appendChild($merge);
        }
        $merges->setAttribute('count', (string) $merges->childNodes->length);
        $setup = $xpath->query('//s:pageSetup')->item(0);
        if (!$setup) {
            $setup = $doc->createElementNS($ns, 'pageSetup');
            $doc->documentElement->appendChild($setup);
        }
        foreach (['paperSize' => '9', 'orientation' => $lastColumn === 'B' ? 'portrait' : 'landscape', 'fitToWidth' => '1', 'fitToHeight' => '0'] as $key => $value) $setup->setAttribute($key, $value);
        $sheetPr = $xpath->query('//s:sheetPr')->item(0);
        if (!$sheetPr) {
            $sheetPr = $doc->createElementNS($ns, 'sheetPr');
            $doc->documentElement->insertBefore($sheetPr, $doc->documentElement->firstChild);
        }
        if (!$xpath->query('s:pageSetUpPr', $sheetPr)->length) $sheetPr->appendChild($doc->createElementNS($ns, 'pageSetUpPr'));
        $xpath->query('s:pageSetUpPr', $sheetPr)->item(0)->setAttribute('fitToPage', '1');
        $drawing = $doc->createElementNS($ns, 'drawing');
        $drawing->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:id', 'rIdLogo');
        $doc->documentElement->appendChild($drawing);
        $zip->addFromString('xl/worksheets/sheet1.xml', $doc->saveXML());
        $book = new DOMDocument();
        $book->loadXML($zip->getFromName('xl/workbook.xml'));
        $names = $book->getElementsByTagName('definedNames')->item(0);
        if (!$names) { $names = $book->createElementNS($ns, 'definedNames'); $book->documentElement->appendChild($names); }
        while ($names->firstChild) $names->removeChild($names->firstChild);
        $name = $book->getElementsByTagName('sheet')->item(0)->getAttribute('name');
        $quoted = "'".str_replace("'", "''", $name)."'!";
        $tableHeaderRow = $xpath->query('//s:autoFilter')->item(0)?->getAttribute('ref');
        preg_match('/[A-Z]+(\d+)/', $tableHeaderRow ?? '', $tableHeaderMatch);
        $printTitles = $compact && isset($tableHeaderMatch[1]) ? '$'.$tableHeaderMatch[1].':$'.$tableHeaderMatch[1] : '$1:$'.((int) ($xpath->query('//s:pane')->item(0)?->getAttribute('ySplit') ?? ($headerRows + 1)));
        foreach (['_xlnm.Print_Area' => $quoted.'$A$1:$'.$lastColumn.'$'.$lastRow, '_xlnm.Print_Titles' => $quoted.$printTitles] as $key => $value) {
            $entry = $book->createElementNS($ns, 'definedName'); $entry->setAttribute('name', $key); $entry->setAttribute('localSheetId', '0'); $entry->appendChild($book->createTextNode($value)); $names->appendChild($entry);
        }
        $zip->addFromString('xl/workbook.xml', $book->saveXML());
        $fills = $styles->getElementsByTagName('fills')->item(0);
        $fillId = $fills->childNodes->length;
        $fill = $styles->createDocumentFragment();
        $fill->appendXML('<fill xmlns="'.$ns.'"><patternFill patternType="solid"><fgColor rgb="FFF0F6F2"/><bgColor indexed="64"/></patternFill></fill>');
        $fills->appendChild($fill);
        $fills->setAttribute('count', (string) $fills->childNodes->length);
        $formats = $styles->getElementsByTagName('cellXfs')->item(0);
        foreach ([2, 4] as $index) {
            $format = $formats->childNodes->item($index);
            $format->setAttribute('fillId', (string) $fillId);
            $format->setAttribute('applyFill', '1');
        }
        // Los títulos largos también se ajustan dentro del área de impresión.
        $formats->childNodes->item(1)->getElementsByTagName('alignment')->item(0)->setAttribute('wrapText', '1');
        $zip->addFromString('xl/styles.xml', $styles->saveXML());
        $types = $zip->getFromName('[Content_Types].xml');
        $types = str_replace('</Types>', '<Default Extension="png" ContentType="image/png"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>', $types);
        $zip->addFromString('[Content_Types].xml', $types);
        $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', '<Relationships xmlns="'.$rels.'"><Relationship Id="rIdLogo" Type="'.$office.'/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', '<Relationships xmlns="'.$rels.'"><Relationship Id="rIdImage" Type="'.$office.'/image" Target="../media/hospital.png"/></Relationships>');
        $image = public_path('img/logo-hospital.png');
        [$width, $height] = getimagesize($image);
        $scale = min($logoSize / $width, $logoSize / $height);
        $cx = (int) round($width * $scale * 9525); $cy = (int) round($height * $scale * 9525);
        $zip->addFile($image, 'xl/media/hospital.png');
        $logoLeft = (int) round(($reservedWidth - $width * $scale) / 2 * 9525);
        $logoTop = (int) round((($stackedHeader ? ceil(($logoSize + 16)*.75) * 4 / 3 : $headerRowHeight * 4) - $height * $scale) / 2 * 9525);
        $zip->addFromString('xl/drawings/drawing1.xml', '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>'.$logoLeft.'</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>'.$logoTop.'</xdr:rowOff></xdr:from><xdr:ext cx="'.$cx.'" cy="'.$cy.'"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Hospital Daniel Bracamonte"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip xmlns:r="'.$office.'" r:embed="rIdImage"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>');
    }
}
