<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe {{ $pathologyResult->sample->pathologyRequest->request_number }}</title>
    <style>
        @page{size:letter portrait;margin:14mm 16mm}
        *{box-sizing:border-box}
        body{margin:0;background:#f3f6f5;color:#20382c;font:12px/1.35 "Segoe UI",Arial,sans-serif}
        .toolbar{max-width:216mm;margin:16px auto;text-align:right}
        .toolbar button{border:0;border-radius:8px;padding:10px 20px;background:#12804e;color:#fff;font:600 13px "Segoe UI",Arial,sans-serif;cursor:pointer}
        .sheet{max-width:216mm;min-height:250mm;margin:0 auto 24px;padding:9mm 12mm;background:#fff;box-shadow:0 6px 28px #173b2414}
        .head{display:grid;grid-template-columns:124px minmax(0,1fr) 150px;align-items:center;gap:12px;padding-bottom:10px}
        .logo{width:120px;height:96px;object-fit:contain}
        .institution{text-align:center}
        .institution h1{margin:0;color:#175d3b;font-size:16px;font-weight:700;line-height:1.25;letter-spacing:.1px}
        .institution h2{margin:4px 0 0;color:#466354;font-size:13px;font-weight:500;line-height:1.4}
        .issued{text-align:right;font-size:11px;line-height:1.6;color:#52685b}
        .issued strong{display:block;margin-bottom:5px;font-size:10px;text-transform:uppercase;letter-spacing:.3px;color:#175d3b}
        table{width:100%;border-collapse:collapse}
        th{text-align:left;font-weight:600}
        .meta{margin-top:8px}
        .meta th,.meta td{padding:6px 8px;vertical-align:top;border-bottom:1px solid #dce6df;font-size:11px}
        .meta th{color:#506b5b;font-weight:600}
        .meta td{color:#20382c;font-weight:600}
        .patient{margin-top:8px;border:0}
        .patient th{padding:6px 8px;background:#f0f6f2;color:#42614f;font-size:10px;text-transform:uppercase;letter-spacing:.2px;border-bottom:1px solid #d8e4dc}
        .patient td{padding:7px 8px;font-size:12px;font-weight:600;border-bottom:1px solid #d8e4dc;vertical-align:top;overflow-wrap:anywhere}
        .urban-meta{table-layout:fixed}.urban-meta th:nth-child(1){width:18%}.urban-meta th:nth-child(2){width:17%}.urban-meta th:nth-child(3){width:16%}.urban-meta th:nth-child(4){width:23%}.urban-meta th:nth-child(5){width:26%}
        .urban-name{margin:0;padding:8px 8px;font-size:13px;border-bottom:1px solid #d8e4dc}
        .urban-name strong{margin-right:8px;color:#42614f;font-size:11px;font-weight:600}
        .diagnoses{margin-top:10px;table-layout:fixed}
        .diagnoses th{padding:6px 8px;background:#edf6f0;color:#175d3b;font-size:10px;text-transform:uppercase;letter-spacing:.2px;border-bottom:1px solid #9fbdab}
        .diagnoses td{padding:6px 8px;border-bottom:1px solid #e0e8e3;vertical-align:top;font-size:12px;line-height:1.35;overflow-wrap:anywhere}
        .diagnoses .date{width:110px}.diagnoses .code{width:72px;font-weight:600}
        .section{margin-top:10px;border:1px solid #d8e4dc}
        .section h3{margin:0;padding:8px 10px;background:#f0f6f2;color:#42614f;font-size:10px;font-weight:600;text-transform:uppercase}
        .section div{min-height:48px;padding:11px;white-space:pre-wrap;overflow-wrap:anywhere}
        .gyn-sheet .head,.rural-sheet .head{grid-template-columns:124px minmax(0,1fr) 150px}
        .rural-sheet .institution h2{display:none}
        .gyn-issued{margin:6px 0 10px;text-align:center;color:#52685b;font-size:11px;font-weight:400}
        .gyn-summary{margin-top:8px;border-bottom:1px solid #d8e4dc;break-inside:avoid}
        .gyn-summary-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,.65fr)}
        .gyn-summary-row+.gyn-summary-row{border-top:1px solid #e0e8e3}
        .gyn-summary-field{min-width:0;margin:0;padding:7px 8px}
        .gyn-summary-field dt{margin:0 0 3px;font-size:9px;line-height:1.3;text-transform:uppercase;letter-spacing:.25px;color:#52685b}
        .gyn-summary-field dd{margin:0;font-size:11px;line-height:1.4;font-weight:600;color:#20382c;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
        .gyn-summary-demographics{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.5fr)}
        .gyn-summary-demographics .gyn-summary-field{padding-right:0}
        .rural-sheet .patient th:first-child{width:60%}.rural-sheet .patient th:nth-child(2){width:12%}
        .rural-sheet .meta th{white-space:nowrap}
        tr{break-inside:avoid}.head,.patient,.section{break-inside:avoid}
        @media print{*{-webkit-print-color-adjust:exact;print-color-adjust:exact}body{background:#fff}.toolbar{display:none}.sheet{max-width:none;min-height:0;margin:0;padding:0;box-shadow:none}}
        @media screen and (max-width:700px){.sheet{margin:0;padding:20px}.head{gap:10px;grid-template-columns:65px minmax(0,1fr) 115px}.logo{width:60px}.institution h1{font-size:13px}}
        .sheet .print-signatures{margin-top:10mm}.sheet .print-signature-space{height:16mm}.sheet .print-signature-role{padding-top:2mm}
        .sheet .print-signature-hint{display:none}
        .institution h1{font-family:Cambria,Georgia,"Times New Roman",serif;font-size:18px;line-height:1.25;font-weight:700;letter-spacing:.15px;color:#174b36}
        .institution h2{font-family:Cambria,Georgia,"Times New Roman",serif;font-size:14px;font-weight:500;color:#40594b;margin-top:5px}
        .issued{text-align:center;font-family:Calibri,"Segoe UI",Arial,sans-serif;font-size:11px;font-weight:400;color:#5b6d61;line-height:1.55}
        .issued strong{font-family:"Segoe UI",Arial,sans-serif;text-align:center;text-transform:none;font-size:13px;line-height:1.35;font-weight:700;letter-spacing:0;color:#174b36;margin-bottom:10px;text-wrap:balance}
        .gyn-issued{font-family:Cambria,Georgia,"Times New Roman",serif;font-size:12px;color:#324c3d}
        .urban-name{display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap}
        .urban-patient-name{color:#20382c;font-weight:600}
    </style>
</head>
<body>
@php
    $sample = $pathologyResult->sample;
    $request = $sample->pathologyRequest;
    $patient = $request->patient;
    $cytology = $pathologyResult->cytologyReport;
    $moduleNames = ['U' => 'URBANO', 'R' => 'RURAL', 'G' => 'GINECOLOGÍA'];
    $moduleName = $moduleNames[$request->module] ?? $request->module;
    $requestNumber = filled($request->request_number) && $request->request_number !== '/'
        ? $request->request_number
        : str_pad((string) ($request->sequence_number ?: $request->id), 6, '0', STR_PAD_LEFT).'-'.$request->module;
    $ageDate = $pathologyResult->result_date ?? $request->request_date;
    $age = $ageDate ? $patient?->ageAt($ageDate) : null;
    $codes = $pathologyResult->diagnoses
        ->map(fn ($item) => [$item->code, $item->description])
        ->all();
    if (!$codes && $cytology) {
        $codes[] = [$cytology->sample_adequacy === 'satisfactory' ? 'G8' : 'G7', $cytology->sample_adequacy === 'satisfactory' ? 'MUESTRA SATISFACTORIA PARA EVALUACIÓN' : 'MUESTRA INSATISFACTORIA PARA EVALUACIÓN'];
        if ($cytology->general_category === 'negative') $codes[] = ['I', 'NEGATIVO PARA LESIÓN INTRAEPITELIAL O MALIGNIDAD'];
        if (in_array('inflammation', $cytology->benign_findings ?? [], true)) $codes[] = ['J4', 'CAMBIOS REACTIVOS O INFLAMATORIOS'];
        if ($cytology->epithelial_abnormality) $codes[] = [strtoupper($cytology->epithelial_abnormality), $cytology->epithelial_abnormality_details ?: 'ANORMALIDAD DE CÉLULAS EPITELIALES'];
        if ($cytology->recommendations) $codes[] = ['S', $cytology->recommendations];
    }
    if (!$codes) $codes[] = ['—', $pathologyResult->diagnosis];
@endphp

<div class="toolbar"><button type="button" onclick="window.print()">Imprimir informe</button></div>
<main class="sheet {{ $request->isGynecological() ? 'gyn-sheet' : ($request->isRural() ? 'rural-sheet' : 'urban-sheet') }}">
    <header class="head">
        <img class="logo" src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte">
        <div class="institution">
            <h1>SERVICIO DE DIAGNÓSTICO MÉDICO DE<br>ANATOMÍA PATOLÓGICA – CITOLOGÍA</h1>
            <h2>
                @if($request->isGynecological())
                    Informe Paciente de Consulta Externa
                @elseif($request->isUrban())
                    Resultado Informe Citológico
                @else

                @endif
            </h2>
        </div>
        <div class="issued"><strong>Fecha Emisión de Informe</strong>{{ $pathologyResult->result_date?->locale('es')->translatedFormat('l, j \d\e F \d\e Y') ?? now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</div>
    </header>

    @if($request->isUrban())
        <table class="patient urban-meta"><thead><tr><th>{{ $request->receptionBatch ? 'Número examen' : 'Número solicitud' }}</th><th>Fecha solicitud</th><th>Sector</th><th>Distrito</th><th>Área</th></tr></thead><tbody><tr><td>{{ $requestNumber }}</td><td>{{ $request->request_date?->format('d/m/Y') }}</td><td>{{ $request->sector?->name ?? '—' }}</td><td>{{ $request->district?->name ?? 'URBANO' }}</td><td>{{ $request->area?->name ?? '—' }}</td></tr></tbody></table>
        @if($request->receptionBatch)<p class="gyn-issued">Solicitud de recepción: {{ $request->receptionBatch->display_number }}</p>@endif
        <p class="urban-name"><span><strong>Nombres y apellidos:</strong> <span class="urban-patient-name">{{ $patient?->full_name ?? '—' }}</span></span><span><strong>Edad:</strong> {{ $age ?? '—' }}</span></p>
    @elseif($request->isRural())
        <table class="meta">
            <tr><th>{{ $request->receptionBatch ? 'Núm. solicitud' : 'Núm. informe' }}</th><td>{{ $request->receptionBatch?->display_number ?? $pathologyResult->rural_report_number ?? '-' }}</td><th>Fecha solicitud</th><td>{{ $request->request_date?->format('d/m/Y') }}</td><th>Núm. examen patológico</th><td>{{ $requestNumber }}</td></tr>
            <tr><th>Secretaría regional</th><td>{{ $request->regionalSecretariat?->name ?? 'NINGUNO' }}</td><th>Establecimiento</th><td colspan="3">{{ $request->healthFacility?->name ?? '—' }}</td></tr>
            <tr><th>Distrito</th><td>{{ $request->district?->name ?? 'RURAL' }}</td></tr>
        </table>
        <table class="patient"><tr><th>Nombre</th><th>Edad</th><th>Núm. asegurado</th></tr><tr><td>{{ $patient?->full_name ?? '—' }}</td><td>{{ $age ?? '—' }}</td><td>{{ $pathologyResult->insurance_number ?? '' }}</td></tr></table>
    @else
        <section class="gyn-summary" aria-label="Datos de la solicitud y del paciente">
            <div class="gyn-summary-row">
                <dl class="gyn-summary-field"><dt>N.º de solicitud</dt><dd>{{ $request->receptionBatch?->display_number ?? $requestNumber }}</dd></dl>
                <dl class="gyn-summary-field"><dt>N.º de examen patológico</dt><dd>{{ $requestNumber }}</dd></dl>
                <dl class="gyn-summary-field"><dt>Fecha de solicitud</dt><dd>{{ $request->request_date?->format('d/m/Y') }}</dd></dl>
            </div>
            <div class="gyn-summary-row">
                <dl class="gyn-summary-field"><dt>Nombre del paciente</dt><dd>{{ $patient?->full_name ?? '—' }}</dd></dl>
                <dl class="gyn-summary-field"><dt>Dirección / procedencia</dt><dd>GINECOLOGÍA</dd></dl>
                <div class="gyn-summary-demographics">
                    <dl class="gyn-summary-field"><dt>Edad</dt><dd>{{ $age ?? '—' }}</dd></dl>
                    @if($request->patient_category === 'private')
                        <dl class="gyn-summary-field patient-category"><dt>Tipo de paciente</dt><dd>Particular</dd></dl>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <table class="diagnoses">
        <thead><tr><th class="date">Fecha resultado</th><th class="code">Código</th><th>Descripción del diagnóstico</th></tr></thead>
        <tbody>@foreach($codes as [$code, $description])<tr><td>{{ $pathologyResult->result_date?->format('d/m/Y') }}</td><td class="code">{{ $code }}</td><td>{{ mb_strtoupper($description) }}</td></tr>@endforeach</tbody>
    </table>

    @if(!$cytology)
        @if($pathologyResult->macroscopic_description)<section class="section"><h3>Descripción macroscópica</h3><div>{{ $pathologyResult->macroscopic_description }}</div></section>@endif
        @if($pathologyResult->microscopic_description)<section class="section"><h3>Descripción microscópica</h3><div>{{ $pathologyResult->microscopic_description }}</div></section>@endif
    @endif

    @if(filled($pathologyResult->notes))
        <section class="section comment-section">
            <h3>Comentario</h3>
            <div>{{ $pathologyResult->notes }}</div>
        </section>
    @endif

    <x-report-signatures :signers="$request->isGynecological() ? ['Citotecnólogo(a)', 'Jefe de Laboratorio de Patología'] : [$request->isUrban() ? 'Nombre y firma del responsable' : 'Citotecnólogo(a)']" />
</main>
</body>
</html>
