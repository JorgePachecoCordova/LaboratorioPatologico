<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CytologyReport;
use RuntimeException;
use ZipArchive;

class CytologyExcelExporter
{
    public function create(CytologyReport $report): string
    {
        $path = tempnam(sys_get_temp_dir(), 'citologia_');
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
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($report));
        HospitalReportBranding::apply($zip);
        $zip->close();

        return $path;
    }

    private function worksheet(CytologyReport $report): string
    {
        $rows = [
            ['Número de informe', $report->report_number ?? 'SIN NÚMERO'],
            ['Fecha del informe', $report->report_date?->format('d/m/Y') ?? ''],
            ['Paciente', $report->patient_name],
            ['Edad', $report->patient_age !== null ? (string) $report->patient_age : ''],
            ['Servicio', $report->service ?? ''],
            ['Médico solicitante', $report->physician_name ?? ''],
            ['Tipo de muestra', $report->sample_type ?? ''],
            ['Diagnóstico clínico', $report->clinical_diagnosis ?? ''],
            ['Datos clínicos', $report->clinical_data ?? ''],
            ['Descripción', $report->description ?? ''],
            ['Conclusión', $report->conclusion ?? ''],
            ['Nota', $report->note ?? ''],
            ['Número de solicitud', $report->request_number ?? ''],
            ['Fecha de solicitud', $report->request_date?->format('d/m/Y') ?? ''],
            ['N.º de registro', $report->registration_number ?? ''],
            ['Historia clínica', $report->clinical_history_number ?? ''],
            ['Cama', $report->bed_number ?? ''],
            ['Atención', match ($report->care_type) { 'external' => 'CONSULTA EXTERNA', 'hospitalized' => 'HOSPITALIZADO', default => $report->care_type ?? '' }],
            ['Hora de toma', $report->sample_taken_at ? substr((string) $report->sample_taken_at, 0, 5) : ''],
            ['Hora de recepción', $report->sample_received_at ? substr((string) $report->sample_received_at, 0, 5) : ''],
        ];

        $sheetRows = '<row r="1" ht="30" customHeight="1">'
            .$this->cell('A1', 'INFORME CITOLÓGICO', 1)
            .'</row>';

        foreach ($rows as $index => [$label, $value]) {
            $rowNumber = $index + 3;
            $isLong = in_array($label, ['Diagnóstico clínico', 'Datos clínicos', 'Descripción', 'Conclusión', 'Nota'], true);
            $height = $isLong ? '60' : '23';
            $sheetRows .= '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">'
                .$this->cell('A'.$rowNumber, $label, 2)
                .$this->cell('B'.$rowNumber, (string) $value, $isLong ? 4 : 3)
                .'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="24" customWidth="1"/><col min="2" max="2" width="100" customWidth="1"/></cols>'
            .'<sheetData>'.$sheetRows.'</sheetData>'
            .'<mergeCells count="1"><mergeCell ref="A1:B1"/></mergeCells>'
            .'<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.2" footer="0.2"/>'
            .'</worksheet>';
    }

    private function cell(string $reference, string $value, int $style): string
    {
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Informe citológico" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF0B6B3A"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF078844"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD4E2DA"/></left><right style="thin"><color rgb="FFD4E2DA"/></right><top style="thin"><color rgb="FFD4E2DA"/></top><bottom style="thin"><color rgb="FFD4E2DA"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
