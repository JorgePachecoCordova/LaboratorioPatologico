<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Informe citológico {{ $report->report_number }}</title>
<style>
@page{size:A4;margin:15mm 17mm}
*{box-sizing:border-box}
body{margin:0;background:#eef2f4;color:#26344a;font:10pt/1.5 Arial,"Segoe UI",sans-serif}
.actions{max-width:210mm;margin:14px auto;text-align:right}
.actions button{background:#147a4b;color:#fff;border:0;border-radius:7px;padding:10px 18px;font:600 13px "Segoe UI",sans-serif;cursor:pointer}
.cy-sheet{width:210mm;max-width:100%;min-height:280mm;margin:0 auto 24px;padding:12mm 16mm 16mm;background:#fff;box-shadow:0 5px 25px #16274012}
.header{display:grid;grid-template-columns:112px minmax(0,1fr);gap:20px;align-items:center}
.logo{width:112px;height:100px;object-fit:contain}
.hospital{text-align:center;font-family:Cambria,Georgia,"Times New Roman",serif;color:#243a60;font-size:14pt;font-weight:700;line-height:1.4;letter-spacing:.15px}
.meta{display:flex;justify-content:space-between;gap:16px;margin-top:12px;font-size:9pt;color:#34445e}
.meta strong{font-weight:600}.meta span{white-space:nowrap}
.registration-number{margin:6px 16mm 0 0;text-align:right;color:#b32832;font:700 12pt/1.3 Arial,sans-serif;letter-spacing:1px;white-space:nowrap}
.title{margin:19px 0 23px;text-align:center;font-family:Cambria,Georgia,"Times New Roman",serif;font-size:20pt;line-height:1.25;color:#243a60;letter-spacing:.8px;font-weight:700}
.identity{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px}
.datum{display:flex;gap:8px;align-items:baseline;font-size:9.5pt;min-width:0}
.datum.patient{grid-column:1/-1;display:grid;grid-template-columns:auto minmax(0,1fr) auto 65px}
.datum.full{grid-column:1/-1;display:grid;grid-template-columns:124px minmax(0,1fr)}
.datum label{white-space:nowrap;color:#536079;font-weight:400}
.datum .value{flex:1;min-width:0;padding:0 2px 3px;border-bottom:1px dotted #b0b6c1;color:#252f40;overflow-wrap:anywhere;white-space:pre-wrap}
.datum.patient .value{font-weight:600}
.section{margin-top:22px}
.section h2{display:inline-block;margin:0 0 10px;padding-bottom:2px;border-bottom:1px solid #77849c;font:700 11pt/1.3 Cambria,Georgia,serif;letter-spacing:.3px;color:#2c4267}
.content{padding:0 7px 0 18px;white-space:pre-wrap;overflow-wrap:anywhere;font:10pt/1.55 Arial,"Segoe UI",sans-serif;color:#222e3c;min-height:24px;text-align:left}
.note .content{min-height:18px}
.footer{display:grid;grid-template-columns:1fr 1fr;gap:35px;margin:25px 12px 0;align-items:end;break-inside:avoid}
.date{font:italic 9.5pt/1.6 Cambria,Georgia,serif;color:#465570}.date p{margin:5px 0 0}
.signature{text-align:center;padding-top:22mm;font-size:10pt;color:#374862}
.signature strong{display:block;margin:0 12px;padding-top:7px;border-top:1px solid #7b879a;font-family:Cambria,Georgia,serif;font-style:normal;font-weight:700;letter-spacing:.3px}
.signature span{display:block;font-size:9pt;margin-top:4px}
.header,.identity,.datum{break-inside:avoid}.section h2{break-after:avoid}.content{orphans:3;widows:3}
@media print{body{background:#fff}.actions{display:none}.cy-sheet{width:100%;max-width:none;min-height:0;margin:0;padding:0;box-shadow:none}}
@media screen and (max-width:700px){.cy-sheet{padding:22px}.header{grid-template-columns:80px minmax(0,1fr);gap:12px}.logo{width:80px}.hospital{font-size:12pt}.meta{flex-wrap:wrap}.datum{font-size:9pt}.identity{gap:10px}.datum.full{grid-template-columns:110px minmax(0,1fr)}}
</style></head>
<body>
<div class="actions"><button type="button" onclick="window.print()">Imprimir informe</button></div>
<main class="cy-sheet">
<header class="header"><img class="logo" src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte"><div><div class="hospital">SERVICIO DE DIAGNÓSTICO MÉDICO<br>EN ANATOMÍA PATOLÓGICA Y CITOLOGÍA</div><div class="meta"><span><strong>Número Informe:</strong> {{ $report->report_number }}</span><span><strong>Fecha Solicitud:</strong> {{ $report->request_date?->format('d/m/Y') }}</span></div></div></header>
@if($report->registration_number)<p class="registration-number" aria-label="Número de registro">N.º {{ $report->registration_number }}</p>@endif
<h1 class="title">INFORME CITOLÓGICO</h1>
<section class="identity" aria-label="Datos del paciente">
<div class="datum patient"><label>Paciente:</label><span class="value">{{ $report->patient_name }}</span><label>Edad:</label><span class="value">{{ $report->patient_age ?? '' }}</span></div>
<div class="datum"><label>Servicio:</label><span class="value">{{ $report->service ?? '' }}</span></div><div class="datum"><label>Médico Solicitante:</label><span class="value">{{ $report->physician_name ?? 'NINGUNO' }}</span></div>
<div class="datum full"><label>Tipo de muestra:</label><span class="value">{{ $report->sample_type }}</span></div>
<div class="datum full"><label>Diagnóstico Clínico:</label><span class="value">{{ $report->clinical_diagnosis }}</span></div>
<div class="datum full"><label>Datos Clínicos:</label><span class="value">{{ $report->clinical_data }}</span></div>
</section>
<section class="section"><h2>DESCRIPCIÓN:</h2><div class="content">{{ $report->description }}</div></section>
<section class="section conclusion"><h2>CONCLUSIÓN:</h2><div class="content">{{ $report->conclusion }}</div></section>
<section class="section note"><h2>NOTA:</h2><div class="content">{{ $report->note }}</div></section>
<footer class="footer"><div class="date">{{ $report->report_date?->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}<p>Potosí, {{ $report->report_date?->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p></div><div class="signature"><strong>ANATOMOPATÓLOGO</strong><span>Firma y Sello</span></div></footer>
</main></body></html>
