<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\{HospitalReportBranding, PathologyResultExcelExporter, CytologyExcelExporter};

function checkReport(string $path, string $expected, bool $compact = false): void
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('Missing workbook');
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!str_ends_with($name, '.xml') && !str_ends_with($name, '.rels')) continue;
        $doc = new DOMDocument();
        if (!$doc->loadXML($zip->getFromIndex($i))) throw new RuntimeException('Invalid XML: '.$name);
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    foreach (['HOSPITAL DANIEL BRACAMONTE', $expected, 'rIdLogo', 'showGridLines="0"', 'fitToWidth="1"'] as $text) {
        if (!str_contains($sheet, $text)) throw new RuntimeException('Missing '.$text);
    }
    if ($zip->getFromName('xl/media/hospital.png') !== file_get_contents(public_path('img/logo-hospital.png'))) throw new RuntimeException('Incorrect image');
    $doc = new DOMDocument(); $doc->loadXML($sheet);
    $refs = [];
    foreach ($doc->getElementsByTagName('c') as $cell) {
        $ref = $cell->getAttribute('r');
        if (isset($refs[$ref])) throw new RuntimeException('Duplicate cell '.$ref);
        $refs[$ref] = true;
    }
    $drawing = new DOMDocument(); $drawing->loadXML($zip->getFromName('xl/drawings/drawing1.xml'));
    $size = $drawing->getElementsByTagName('ext')->item(0);
    $minimumSize = $compact ? 30 : 100;
    if ((int) $size->getAttribute('cx') < $minimumSize * 9525 || (int) $size->getAttribute('cy') < $minimumSize * 9525) throw new RuntimeException('Hospital logo is too small');
    if ($compact) {
        if ($doc->getElementsByTagName('pane')->length) throw new RuntimeException('Consolidated report must scroll freely');
        $headingHeight = 0;
        foreach ($doc->getElementsByTagName('row') as $row) if ((int) $row->getAttribute('r') <= 9) $headingHeight += (float) $row->getAttribute('ht');
        if ($headingHeight > 160) throw new RuntimeException('Consolidated heading is too tall');
        if (!str_contains($zip->getFromName('xl/workbook.xml'), '!$9:$9')) throw new RuntimeException('Only column headers should repeat in print');
    }
    $height = 0;
    foreach ($doc->getElementsByTagName('row') as $row) if ((int) $row->getAttribute('r') <= 3) $height += (float) $row->getAttribute('ht') * 4 / 3;
    $top = (int) $drawing->getElementsByTagName('rowOff')->item(0)->nodeValue;
    if ($top + (int) $size->getAttribute('cy') > $height * 9525 + 1) throw new RuntimeException('Logo overlaps report body');
    $zip->close();
    if (in_array('--preview', $_SERVER['argv'], true)) {
        static $previewNumber = 0;
        $dir = dirname(__DIR__, 2).'/.private-report-review';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        copy($path, $dir.'/branding-'.(++$previewNumber).'.xlsx');
    }
    unlink($path);
}

$exporter = new PathologyResultExcelExporter();
checkReport($exporter->createStatistical('REPORTE ESTADÍSTICO', ['N.º', 'PACIENTE', 'DIAGNÓSTICO'], [[1, 'PACIENTE DE PRUEBA', str_repeat('Diagnóstico con descripción extensa. ', 14)]]), 'REPORTE ESTADÍSTICO');
checkReport($exporter->createStatistical('REPORTE VACÍO', ['N.º', 'PACIENTE'], []), 'REPORTE VACÍO');
checkReport($exporter->createConsolidated(collect()), 'REPORTE GENERAL DE RESULTADOS', compact: true);
$report = new App\Models\CytologyReport(['patient_name' => 'PACIENTE DE PRUEBA', 'description' => 'DESCRIPCIÓN']);
checkReport((new CytologyExcelExporter())->create($report), 'INFORME CITOLÓGICO');

// Comprueba la composición individual sin consultar ni modificar pacientes reales.
$path = tempnam(sys_get_temp_dir(), 'branding_check_');
$zip = new ZipArchive(); $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
foreach (['contentTypes' => '[Content_Types].xml', 'rootRelationships' => '_rels/.rels', 'workbook' => 'xl/workbook.xml', 'workbookRelationships' => 'xl/_rels/workbook.xml.rels', 'styles' => 'xl/styles.xml'] as $method => $part) {
    $zip->addFromString($part, (new ReflectionMethod($exporter, $method))->invoke($exporter));
}
$rows = [['Número de solicitud', '000003-G'], ['Paciente', 'PACIENTE DE PRUEBA'], ['C.I.', '0012345'], ['Tipo de muestra', 'BIOPSIA'], ['Diagnóstico', str_repeat('Descripción extensa con acentos y & caracteres. ', 20)], ['Patólogo responsable', 'RESPONSABLE']];
$zip->addFromString('xl/worksheets/sheet1.xml', (new ReflectionMethod($exporter, 'worksheet'))->invoke($exporter, $rows));
HospitalReportBranding::apply($zip); $zip->close();
checkReport($path, 'RESULTADOS Y DIAGNÓSTICO');
echo "PASS: cinco reportes XLSX, XML válido, imagen incrustada, datos conservados, impresión y celdas únicas.\n";
