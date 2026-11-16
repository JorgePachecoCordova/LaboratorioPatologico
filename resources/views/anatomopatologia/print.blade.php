<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe anatomopatológico {{ $report->report_number }}</title>
    <style>
        :root { --blue: #294d7a; --red: #b33139; --ink: #263238; }
        * { box-sizing: border-box; }
        /* El margen de página se repite en cada hoja y evita que una continuación quede pegada al borde. */
        @page {
            size: A4 portrait;
            margin: 10mm;
            @top-left { content: ""; }
            @top-center { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-center { content: ""; }
            @bottom-right { content: ""; }
        }
        body { margin: 0; color: var(--ink); font: 11pt Arial, sans-serif; background: #eef2f3; }
        .toolbar { position: sticky; top: 0; z-index: 2; padding: 10px; text-align: right; background: #fff; border-bottom: 1px solid #ddd; }
        .toolbar button { padding: 10px 18px; border: 0; border-radius: 7px; color: #fff; background: #11894f; font-weight: 600; cursor: pointer; }
        .page { width: 210mm; min-height: 297mm; margin: 14px auto; padding: 9mm 13mm 12mm; background: #fff; box-shadow: 0 3px 18px #0002; }
        .header { display: grid; grid-template-columns: 26mm 1fr 32mm; gap: 4mm; align-items: center; }
        .logo { width: 25mm; max-height: 22mm; object-fit: contain; }
        .title { text-align: center; color: var(--blue); font-size: 12pt; font-weight: 700; letter-spacing: .2px; }
        .number { color: var(--red); text-align: right; font-size: 11pt; font-weight: 700; }
        h1 { margin: -1.5mm 0 4mm; color: var(--blue); text-align: center; font-size: 16pt; letter-spacing: .4px; }
        .data-grid { display: grid; grid-template-columns: 1.65fr .7fr; gap: 2mm 8mm; }
        .field { min-height: 8mm; padding: 0 1mm 1.5mm; border-bottom: 1px dotted #8794a2; break-inside: avoid-page; page-break-inside: avoid; }
        .field.long-field { break-inside: auto; page-break-inside: auto; }
        .field.wide { grid-column: 1 / -1; min-height: 10mm; }
        .label { color: #334d6c; font-size: 8.5pt; font-weight: 600; }
        .value { margin-top: 1mm; white-space: pre-wrap; overflow-wrap: anywhere; }
        .section { margin-top: 4mm; break-inside: avoid-page; page-break-inside: avoid; }
        .section.long-section { break-inside: auto; page-break-inside: auto; }
        .section h2 { margin: 0 0 3mm; padding-bottom: 1.5mm; color: var(--blue); border-bottom: 1.5px solid var(--blue); font-size: 13pt; letter-spacing: .4px; break-after: avoid-page; page-break-after: avoid; }
        .section .content { white-space: pre-wrap; line-height: 1.3; overflow-wrap: anywhere; orphans: 3; widows: 3; }
        /* Justificación de párrafos: conserva saltos, colapsa espacios repetidos
           y deja la última línea sin estirar. */
        .field.wide .value, .section .content {
            white-space: pre-line;
            text-align: justify;
            text-align-last: left;
            hyphens: auto;
            overflow-wrap: break-word;
            word-break: normal;
            word-spacing: normal;
            letter-spacing: normal;
            orphans: 3;
            widows: 3;
        }
        /* Reserva espacio real para la firma, junto con la fecha, después del texto.
           El padding permanece dentro del bloque aunque pase a la siguiente hoja. */
        .footer { display: grid; grid-template-columns: 1fr 58mm; gap: 15mm; align-items: end; margin-top: 0; padding-top: 22mm; color: var(--blue); break-inside: avoid-page; page-break-inside: avoid; }
        .signature { padding-top: 3mm; border-top: 1px dotted #596b7d; text-align: center; font-weight: 700; }
        .cap-annex { break-before: page; page-break-before: always; }
        .cap-identity { margin:4mm 0 6mm; padding-bottom:3mm; border-bottom:1px solid #8794a2; line-height:1.5; }
        .cap-item { margin:0 0 2.5mm; line-height:1.3; }
        .cap-item dt { font-weight:700; break-after:avoid; }
        .cap-item dd { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; orphans:3; widows:3; }
        @media screen and (max-width: 800px) {
            .page { width: 100%; min-height: auto; margin: 0; padding: 18px; box-shadow: none; }
            .header { grid-template-columns: 70px 1fr; }
            .number { grid-column: 1 / -1; padding-top: 0; }
            .data-grid { grid-template-columns: 1fr; }
            .field.wide { grid-column: auto; }
        }
        @media print {
            body { background: #fff; font-size: 10pt; line-height: 1.15; }
            .logo { width: 20mm; max-height: 18mm; }
            .title { font-size: 11pt; }
            h1 { margin: -1.5mm 0 3mm; font-size: 15pt; }
            .data-grid { gap: 1.5mm 8mm; }
            .field { min-height: 0; padding-bottom: 1mm; }
            .field.wide { min-height: 0; }
            .value { margin-top: .5mm; }
            .section { margin-top: 3mm; }
            .section h2 { margin-bottom: 1.5mm; padding-bottom: 1mm; font-size: 11pt; }
            .section .content { line-height: 1.15; }
            .footer { margin-top: 0; padding-top: 22mm; font-size: 9pt; }
            .toolbar { display: none; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .data-grid { break-inside: auto; }
            .field { break-inside: avoid-page; page-break-inside: avoid; }
            .field.long-field { break-inside: auto; page-break-inside: auto; }
            .section { break-inside: avoid-page; page-break-inside: avoid; }
            .section.long-section { break-inside: auto; page-break-inside: auto; }
            .section h2 { break-after: avoid-page; page-break-after: avoid; }
            .footer { break-inside: avoid-page; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Imprimir informe</button></div>
    <main class="page">
        <header class="header">
            <img class="logo" src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte">
            <div class="title">SERVICIO DE DIAGNÓSTICO MÉDICO<br>EN ANATOMÍA PATOLÓGICA</div>
            <div class="number">N.º {{ $report->report_number }}</div>
        </header>

        <h1>INFORME ANATOMOPATOLÓGICO</h1>

        <section class="data-grid">
            <div class="field"><div class="label">Paciente</div><div class="value">{{ $report->patient_name }}</div></div>
            <div class="field"><div class="label">Edad</div><div class="value">{{ $report->patient_age }}</div></div>
            <div class="field"><div class="label">Servicio</div><div class="value">{{ $report->service ?: '—' }}</div></div>
            <div class="field"><div class="label">Médico solicitante</div><div class="value">{{ $report->physician_name }}</div></div>
            <div class="field wide {{ mb_strlen((string) $report->clinical_diagnosis) > 1400 ? 'long-field' : '' }}"><div class="label">Diagnóstico clínico</div><div class="value">{{ $report->clinical_diagnosis ?: '—' }}</div></div>
            <div class="field wide {{ mb_strlen((string) $report->anatomical_data) > 1400 ? 'long-field' : '' }}"><div class="label">Datos anatómicos relevantes</div><div class="value">{{ $report->anatomical_data ?: '—' }}</div></div>
            @if($report->important_data)
                <div class="field wide {{ mb_strlen((string) $report->important_data) > 1400 ? 'long-field' : '' }}"><div class="label">Datos importantes</div><div class="value">{{ $report->important_data }}</div></div>
            @endif
        </section>

        <section class="section macro {{ mb_strlen((string) $report->macroscopic_description) > 1800 ? 'long-section' : '' }}">
            <h2>MACROSCÓPICO</h2>
            <div class="content">{{ $report->macroscopic_description }}</div>
        </section>
        <section class="section micro {{ mb_strlen((string) $report->microscopic_description) > 1800 ? 'long-section' : '' }}">
            <h2>MICROSCÓPICO</h2>
            <div class="content">{{ $report->microscopic_description }}</div>
        </section>
        <section class="section diagnosis {{ mb_strlen((string) $report->diagnosis) > 1800 ? 'long-section' : '' }}">
            <h2>CONCLUSIÓN</h2>
            <div class="content">{{ $report->diagnosis }}</div>
        </section>

        @if(filled($report->notes))
        <section class="section report-notes {{ mb_strlen((string) $report->notes) > 1800 ? 'long-section' : '' }}">
            <div class="content"><strong>NOTA:</strong> {{ $report->notes }}</div>
        </section>
        @endif

        <x-report-signatures :signers="['MÉDICO PATÓLOGO']" :date="'Potosí, '.($report->report_date?->locale('es')->translatedFormat('d \d\e F \d\e Y') ?? '')" />
    </main>
    @if($report->cap_protocol_enabled)
    <section class="page cap-annex" aria-label="Protocolo CAP">
        <header class="header">
            <img class="logo" src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte">
            <div class="title">SERVICIO DE DIAGNÓSTICO MÉDICO<br>EN ANATOMÍA PATOLÓGICA</div>
            <div aria-hidden="true"></div>
        </header>
        <h1>PROTOCOLO CAP</h1>
        <div class="cap-identity"><strong>Paciente:</strong> {{ $report->patient_name }}<br><strong>Registro:</strong> {{ $report->registration_number ?: $report->report_number }} · <strong>Fecha:</strong> {{ $report->report_date?->format('d/m/Y') }}</div>
        <dl>
        @foreach(\App\Support\AnatomopathologyCapProtocol::FIELDS as $key => $label)
            @if(filled($report->cap_protocol[$key] ?? null))
            <div class="cap-item"><dt>{{ mb_strtoupper($label) }}:</dt><dd>{{ $report->cap_protocol[$key] }}</dd></div>
            @endif
        @endforeach
        @foreach($report->cap_protocol['custom'] ?? [] as $item)
            <div class="cap-item"><dt>{{ $item['title'] }}:</dt><dd>{{ $item['value'] }}</dd></div>
        @endforeach
        </dl>

    </section>
    @endif
</body>
@if(request()->boolean('auto'))
<script>window.addEventListener('load', () => window.print());</script>
@endif
</html>
