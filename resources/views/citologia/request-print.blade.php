<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de examen citológico {{ $report->request_number }}</title>
    <style>
        @page{size:A4;margin:12mm}body{font-family:Arial,sans-serif;color:#34445e;font-size:12px;margin:0}.actions{text-align:right;margin-bottom:10px}.actions button{border:0;border-radius:6px;background:#177b4b;color:#fff;padding:8px 15px;font-weight:bold}.header{display:grid;grid-template-columns:120px 1fr 110px;align-items:center;text-align:center}.logo{width:105px}.hospital{font-size:17px;font-weight:bold;line-height:1.4;color:#294a72}.number{color:#b23c48;font-size:17px;font-weight:bold}.title{text-align:center;font-size:22px;font-weight:bold;margin:20px 0;color:#294a72}.row{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin:10px 0}.line{border-bottom:1px dotted #627089;min-height:20px;padding:0 5px;color:#111}.label{font-weight:bold}.wide{margin:10px 0}.checks{display:grid;grid-template-columns:1fr 1fr;gap:7px 45px;margin:15px 35px}.check{display:flex;align-items:center;gap:8px}.box{width:14px;height:14px;border:1px solid #536582;display:inline-grid;place-items:center;color:#111}.footer{margin-top:45px}.date-line{margin-top:65px}@media print{.actions{display:none}}
        /* Evita que nombres o datos clínicos extensos salgan del ancho de la hoja. */
        *{box-sizing:border-box}body{overflow-wrap:anywhere}.row>div{min-width:0}.line{max-width:100%;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word}
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()"><x-ui-icon name="print" /> Imprimir solicitud</button></div>
<div class="header">
    <img class="logo" src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte">
    <div class="hospital">SERVICIO DE DIAGNÓSTICO MÉDICO<br>EN ANATOMÍA PATOLÓGICA Y CITOLOGÍA</div>
    <div class="number">N.º {{ $report->request_number }}</div>
</div>
<div class="title">SOLICITUD DE EXAMEN CITOLÓGICO</div>
<div class="row"><div><span class="label">Paciente:</span><div class="line">{{ $report->patient_name }}</div><div style="margin-top:7px">Atención: {{ match ($report->care_type) { 'external' => 'CONSULTA EXTERNA', 'hospitalized' => 'HOSPITALIZADO', default => $report->care_type ?? '—' } }}</div></div><div><div><span class="label">Edad:</span><span class="line">{{ $report->patient_age }}</span></div><div><span class="label">N.º registro:</span><span class="line">{{ $report->registration_number }}</span></div></div></div>
<div class="row"><div><span class="label">Servicio:</span><div class="line">{{ $report->service }}</div></div><div><span class="label">Cama:</span><span class="line">{{ $report->bed_number }}</span><br><span class="label">Historia clínica:</span><span class="line">{{ $report->clinical_history_number }}</span></div></div>
<div class="wide"><span class="label">Datos clínicos:</span><div class="line">{{ $report->clinical_data }}</div><div class="line"></div></div>
<div class="wide"><span class="label">Diagnóstico presuntivo:</span><div class="line">{{ $report->clinical_diagnosis }}</div></div>
<div class="wide"><span class="label">Médico solicitante:</span><span class="line">{{ $report->physician_name }}</span></div>
<div class="row"><div><span class="label">Hora de toma de la muestra:</span><div class="line">{{ $report->sample_taken_at ? substr($report->sample_taken_at,0,5) : '' }}</div></div><div><span class="label">Hora de recepción:</span><div class="line">{{ $report->sample_received_at ? substr($report->sample_received_at,0,5) : '' }}</div></div></div>
<div class="wide label">Tipo de muestra:</div>
@php($types = ['LIQUIDO PLEURAL','LIQUIDO ASCITICO','LIQUIDO PERICARDICO','LIQUIDO CEFALORRAQUIDEO','LIQUIDO INTRAQUISTICO','LIQUIDO OVARICO','CITOLOGIA SERIADA DE ESPUTO','LAVADO BRONQUIAL','SECRECION MAMARIA','P.A.A.F. DE MAMA','P.A.A.F. DE TIROIDES','P.A.A.F. DE PULMON','P.A.A.F. DE PARTES BLANDAS','CITOLOGIA DE ORINA','RASPADO CUTANEO','OTROS'])
<div class="checks">@foreach($types as $type)<div class="check"><span class="box">{{ str_contains($report->sample_type, $type) ? '✓' : '' }}</span>{{ $type }}</div>@endforeach</div>
<x-report-signatures :signers="['MÉDICO SOLICITANTE']" :date="'Potosí, '.($report->request_date?->locale('es')->translatedFormat('d \d\e F \d\e Y') ?? '')" />
</body>
</html>


