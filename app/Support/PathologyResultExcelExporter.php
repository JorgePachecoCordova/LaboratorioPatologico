<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PathologyResult;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class PathologyResultExcelExporter
{
    /** @param array<int, string> $headers @param array<int, array<int, string|int>> $rows */
    public function createStatistical(string $title, array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'reporte_citologia_');
        if ($path === false) throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path); throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->statisticalWorksheet($title, $headers, $rows));
        HospitalReportBranding::apply($zip);
        $zip->close();
        return $path;
    }

    /** @param array<int, string> $headers @param array<int, array<int, string|int>> $rows */
    private function statisticalWorksheet(string $title, array $headers, array $rows): string
    {
        $letters = range('A', 'Z');
        $lastColumn = $letters[count($headers) - 1];
        $widths = array_map(static function ($header) {
            if (str_contains($header, 'DIAGNÓSTICO')) return 62;
            if (str_contains($header, 'NOMBRES') || str_contains($header, 'ESTABLECIMIENTO')) return 32;
            if (str_contains($header, 'MUNICIPIO')) return 27;
            return 14;
        }, $headers);
        if (!str_contains(implode(' ', $headers), 'DIAGNÓSTICO')) {
            $widths[0] = 30;
            if (str_contains($headers[1] ?? '', 'MUNICIPIO')) $widths[1] = 27;
        }
        $sheetRows = '<row r="1" ht="46" customHeight="1">'.$this->cell('A1', $title, 1).'</row><row r="2" ht="46" customHeight="1">';
        foreach ($headers as $column => $header) $sheetRows .= $this->cell($letters[$column].'2', $header, 2);
        $sheetRows .= '</row>';
        foreach ($rows as $index => $values) {
            $lines = 1;
            foreach ($values as $column => $value) {
                foreach (preg_split('/\R/u', (string) $value) ?: [''] as $line) $lines = max($lines, (int) ceil(mb_strlen($line) / max(6, $widths[$column] * .8)));
            }
            $row = $index + 3; $sheetRows .= '<row r="'.$row.'" ht="'.min(409, max(30, $lines * 16 + 12)).'" customHeight="1">';
            foreach ($values as $column => $value) {
                $reference = $letters[$column].$row;
                $sheetRows .= is_int($value) || is_float($value)
                    ? '<c r="'.$reference.'" s="3"><v>'.$value.'</v></c>'
                    : $this->cell($reference, (string) $value, 3);
            }
            $sheetRows .= '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.implode('', array_map(fn ($width, $column) => '<col min="'.($column + 1).'" max="'.($column + 1).'" width="'.$width.'" customWidth="1"/>', $widths, array_keys($widths))).'</cols><sheetData>'.$sheetRows.'</sheetData>'
            .'<autoFilter ref="A2:'.$lastColumn.max(2, count($rows) + 2).'"/><mergeCells count="1"><mergeCell ref="A1:'.$lastColumn.'1"/></mergeCells>'
            .'<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>';
    }

    /** @param Collection<int, PathologyResult> $results */
    public function createConsolidated(Collection $results, string $period = 'Todas las gestiones', string $classification = 'Todos los resultados'): string
    {
        $rows = $results->values()->flatMap(function (PathologyResult $result, int $index): array {
            $request = $result->sample->pathologyRequest;
            $data = [
                (string) ($index + 1),
                $request->receptionBatch?->display_number ?? '',
                $request->request_number ?? '',
                $result->result_date?->format('d/m/Y') ?? '',
                $request->patient?->full_name ?? '',
                $request->patient?->identity_document ?? '',
                $request->healthFacility?->name ?? '',
                $result->is_positive ? 'POSITIVO' : 'NEGATIVO',
            ];
            $diagnoses = $result->diagnoses->pluck('description')->filter()->values();
            if ($diagnoses->isEmpty()) {
                $diagnoses = collect(preg_split('/\R/u', $result->diagnosis ?? '') ?: [])
                    ->map(fn ($line) => trim($line))->filter()->values();
            }
            if ($diagnoses->isEmpty()) $diagnoses = collect(['Sin diagnóstico registrado']);
            return $diagnoses->map(fn ($diagnosis) => [...$data, $diagnosis])->all();
        })->all();

        $path = tempnam(sys_get_temp_dir(), 'reporte_resultados_');
        if ($path === false) throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path); throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook(max(5, count($rows) + 5)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->consolidatedWorksheet($rows, $period, $classification, $results->count()));
        HospitalReportBranding::apply($zip, compact: true);
        $zip->close();
        return $path;
    }

    /** @param array<int, array<int, string>> $rows */
    private function consolidatedWorksheet(array $rows, string $period, string $classification, int $resultCount): string
    {
        $headers = ['N.º resultado', 'SOLICITUD', 'EXAMEN', 'FECHA RESULTADO', 'PACIENTE', 'C.I.', 'ESTABLECIMIENTO', 'CLASIFICACIÓN', 'DIAGNÓSTICO'];
        $sheetRows = '<row r="1" ht="24" customHeight="1">'.$this->cell('A1', 'REPORTE GENERAL DE RESULTADOS', 1).'</row>';
        $sheetRows .= '<row r="2" ht="0" hidden="1" customHeight="1">'.$this->cell('A2', 'Hospital Daniel Bracamonte · Laboratorio de Patología', 2).'</row>';
        $sheetRows .= '<row r="3" ht="18" customHeight="1">'.$this->cell('A3', 'Período de solicitud: '.$period.' · '.$classification, 2).'</row>';
        $sheetRows .= '<row r="4" ht="18" customHeight="1">'.$this->cell('A4', 'Resultados: '.$resultCount.' · Un diagnóstico por fila · Emisión: '.now()->format('d/m/Y'), 3).'</row>';
        $sheetRows .= '<row r="5" ht="30" customHeight="1">';
        foreach ($headers as $column => $header) $sheetRows .= $this->cell(chr(65 + $column).'5', $header, 2);
        $sheetRows .= '</row>';
        $widths = [12, 18, 14, 15, 25, 13, 29, 17, 57];
        foreach ($rows as $index => $values) {
            $lines = 1;
            foreach ($values as $column => $value) {
                $wrapped = wordwrap($value, max(5, (int) floor($widths[$column] * 0.78) - 2), "\n", true);
                $lines = max($lines, count(explode("\n", $wrapped)));
            }
            $height = min(409, max(18, $lines * 14 + 4));
            $rowNumber = $index + 6; $sheetRows .= '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">';
            foreach ($values as $column => $value) $sheetRows .= $this->cell(chr(65 + $column).$rowNumber, $value, 3);
            $sheetRows .= '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<cols>'.implode('', array_map(fn ($width, $column) => '<col min="'.($column + 1).'" max="'.($column + 1).'" width="'.$width.'" customWidth="1"/>', $widths, array_keys($widths))).'</cols>'
            .'<sheetData>'.$sheetRows.'</sheetData><autoFilter ref="A5:I'.max(5, count($rows) + 5).'"/><mergeCells count="4"><mergeCell ref="A1:I1"/><mergeCell ref="A2:I2"/><mergeCell ref="A3:I3"/><mergeCell ref="A4:I4"/></mergeCells>'
            .'<printOptions horizontalCentered="1"/>'
            .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.15" footer="0.15"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'<headerFooter><oddFooter>&amp;LPatología&amp;RPágina &amp;P de &amp;N</oddFooter></headerFooter></worksheet>';
    }

    public function create(PathologyResult $result): string
    {
        $sample = $result->sample;
        $request = $sample->pathologyRequest;
        $patient = $request->patient;
        $module = ['U' => 'URBANO', 'R' => 'RURAL', 'G' => 'GINECOLOGÍA'][$request->module] ?? $request->module;
        $diagnoses = $result->diagnoses
            ->values()
            ->map(fn ($item, $index): array => ['Diagnóstico codificado '.($index + 1), trim($item->code.' - '.$item->description)])
            ->all();
        $normalize = fn ($text) => mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $text)), 'UTF-8');
        $codedDescriptions = $result->diagnoses->pluck('description')->map($normalize)->all();
        $conclusions = collect(preg_split('/\R/u', $result->diagnosis ?? '') ?: [])
            ->map(fn ($line) => trim($line))->filter()
            ->reject(fn ($line) => in_array($normalize($line), $codedDescriptions, true))
            ->unique($normalize)->values()
            ->map(fn ($line, $index): array => ['Diagnóstico / conclusión '.($index + 1), $line])
            ->all();

        $rows = [
            ['Número de solicitud', $request->receptionBatch?->display_number ?? $request->request_number ?? ''],
            ['Número de examen', $request->request_number ?? ''],
            ['Módulo', $module],
            ['Fecha de solicitud', $request->request_date?->format('d/m/Y') ?? ''],
            ['Fecha del resultado', $result->result_date?->format('d/m/Y') ?? ''],
            ['Paciente', $patient?->full_name ?? ''],
            ['C.I.', $patient?->identity_document ?? ''],
            ['Edad', ($result->result_date ?? $request->request_date) ? (string) ($patient?->ageAt($result->result_date ?? $request->request_date) ?? '') : ''],
            ['Establecimiento', $request->healthFacility?->name ?? ''],
            ['Secretaría regional', $request->regionalSecretariat?->name ?? ''],
            ['Municipio', $request->municipality?->name ?? ''],
            ['Distrito', $request->district?->name ?? ''],
            ['Área', $request->area?->name ?? ''],
            ['Sector', $request->sector?->name ?? ''],
            ['Médico solicitante', $request->requestingPhysician?->full_name ?? $request->requesting_physician_name ?? ''],
            ['Tipo de muestra', $sample->sample_type ?? ''],
            ['Número de muestra', (string) $sample->sample_number],
            ...$diagnoses,
            ...$conclusions,
            ...(!$diagnoses && !$conclusions ? [['Diagnóstico', 'Sin diagnóstico registrado']] : []),
            ['Descripción macroscópica', $result->macroscopic_description ?? ''],
            ['Descripción microscópica', $result->microscopic_description ?? ''],
            ['Comentario', $result->notes ?? ''],
            ['Patólogo responsable', $result->pathologist?->full_name ?? ''],
            ['Registrado por', $result->recorder?->name ?? 'Sin autor identificado'],
            ['Última validación por', $result->validator?->name ?? ''],
            ['Versión del resultado', (string) $result->revision],
            ['Estado', $result->status === PathologyResult::STATUS_VALIDATED ? 'VALIDADO' : mb_strtoupper($result->status)],
        ];

        $path = tempnam(sys_get_temp_dir(), 'resultado_');
        if ($path === false) {
            throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($rows));
        HospitalReportBranding::apply($zip);
        $zip->close();

        return $path;
    }

    /** @param array<int, array{0: string, 1: string}> $rows */
    private function worksheet(array $rows): string
    {
        $longLabels = ['Diagnósticos codificados', 'Diagnóstico / conclusión', 'Descripción macroscópica', 'Descripción microscópica', 'Comentario'];
        $sheetRows = '<row r="1" ht="30" customHeight="1">'.$this->cell('A1', 'RESULTADO PATOLÓGICO', 1).'</row>';
        $merges = ['A1:B1'];
        $row = 2;
        $sections = ['Número de solicitud' => 'DATOS DE LA SOLICITUD', 'Paciente' => 'IDENTIFICACIÓN DEL PACIENTE', 'Establecimiento' => 'PROCEDENCIA Y MÉDICO SOLICITANTE', 'Tipo de muestra' => 'MUESTRA ANALIZADA', 'Patólogo responsable' => 'RESPONSABLE Y ESTADO DEL INFORME'];
        $diagnosisSection = false;
        foreach ($rows as [$label, $value]) {
            $section = $sections[$label] ?? null;
            if (!$diagnosisSection && str_starts_with($label, 'Diagnóstico')) {
                $section = 'RESULTADOS Y DIAGNÓSTICO';
                $diagnosisSection = true;
            }
            if ($section) {
                $row++;
                $sheetRows .= '<row r="'.$row.'" ht="25" customHeight="1">'.$this->cell('A'.$row, $section, 2).'</row>';
                $merges[] = 'A'.$row.':B'.$row;
            }
            $row++;
            $long = in_array($label, $longLabels, true) || str_starts_with($label, 'Diagnóstico');
            $lines = array_sum(array_map(fn ($line) => max(1, (int) ceil(mb_strlen($line) / 64)), preg_split('/\R/u', $value) ?: ['']));
            $height = min(409, max(30, $lines * 16 + 12, (int) ceil(mb_strlen($label) / 24) * 16 + 12));
            $sheetRows .= '<row r="'.$row.'" ht="'.$height.'" customHeight="1">'
                .$this->cell('A'.$row, $label, 2)
                .$this->cell('B'.$row, $value, $long ? 4 : 3)
                .'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="29" customWidth="1"/><col min="2" max="2" width="72" customWidth="1"/></cols>'
            .'<sheetData>'.$sheetRows.'</sheetData><mergeCells count="'.count($merges).'">'.implode('', array_map(fn ($ref) => '<mergeCell ref="'.$ref.'"/>', $merges)).'</mergeCells>'
            .'<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.2" footer="0.2"/>'
            .'</worksheet>';
    }

    private function cell(string $reference, string $value, int $style): string
    {
        $value = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$value.'</t></is></c>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(?int $printLastRow = null): string
    {
        $printNames = $printLastRow === null ? '' : '<definedNames><definedName name="_xlnm.Print_Area" localSheetId="0">\'Resultado patológico\'!$A$1:$H$'.$printLastRow.'</definedName><definedName name="_xlnm.Print_Titles" localSheetId="0">\'Resultado patológico\'!$1:$5</definedName></definedNames>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Resultado patológico" sheetId="1" r:id="rId1"/></sheets>'.$printNames.'</workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF0B6B3A"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF078844"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FFD4E2DA"/></left><right style="thin"><color rgb="FFD4E2DA"/></right><top style="thin"><color rgb="FFD4E2DA"/></top><bottom style="thin"><color rgb="FFD4E2DA"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
